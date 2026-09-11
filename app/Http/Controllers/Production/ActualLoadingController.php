<?php

namespace App\Http\Controllers\Production;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Response;
use App\Permission;
use DataTables;
use DB;
use PDF;
use AppHelpers;
use Approval;
use App\Exports\ActualLoadingExport;
use App\Imports\ActualLoadingImport;
use Maatwebsite\Excel\Facades\Excel;

class ActualLoadingController extends Controller
{
    private $title;
    private $moduleCode;

    /**
     * Status dokumen:
     *  1 = NEW / ON PROCESS
     *  2 = VALIDATE
     *  3 = APPROVED
     *  4 = POSTED
     *  5 = CANCELED
     */
    private $statusMap = [
        '1' => 'NEW',
        '2' => 'VALIDATE',
        '3' => 'APPROVED',
        '4' => 'POSTED',
        '5' => 'CANCELED',
    ];

    private $loadingLocation = '047';   // gudang tujuan Actual Loading

    /**
     * Resolusi lokasi stok akuntansi: kalau booth punya parent (mis. 034 → 059)
     * maka stok & movement dicatat di parent (pool), booth fisik hanya jadi
     * jejak operasi (spray_booth di header, movement_from/to). Pola sama dengan
     * TransferStockController::getStockLocation().
     */
    private array $stockLocationCache = [];

    /** Tanggal (d-m-Y) yang dipakai writeMovement. Di-set store()/update() = loading_date. */
    private ?string $movementDate = null;

    private function resolveStockLocation(string $locationCode): string
    {
        if (array_key_exists($locationCode, $this->stockLocationCache)) {
            return $this->stockLocationCache[$locationCode];
        }

        $parent = DB::table('stock_location_master')
            ->where('location_code', $locationCode)
            ->value('parent_location');

        return $this->stockLocationCache[$locationCode] = ($parent ?: $locationCode);
    }

    /**
     * Kunci alokasi movement_code (dipakai lintas modul). WAJIB dipanggil di
     * dalam transaksi sebelum membaca MAX(movement_code), supaya dua posting
     * paralel (loading + transfer + receiving) tidak bentrok nomornya.
     */
    private function lockMovementSequence(): void
    {
        DB::select("SELECT pg_advisory_xact_lock(hashtext('warehouse_movement_code'))");
    }

    /**
     * Tanda tangan isi dokumen loading — untuk deteksi double-submit.
     * (booth + tanggal + daftar artikel:qty_fresh:qty_repaint terurut)
     */
    private function loadingSignature(?string $sprayBooth, ?string $loadingDateDb, $articles): string
    {
        $parts = collect($articles)->map(function ($v) {
            $ac = is_array($v) ? ($v['article_code'] ?? '') : ($v->article_code ?? '');
            $f  = is_array($v) ? ($v['qty_fresh']   ?? 0)  : ($v->qty_fresh   ?? 0);
            $r  = is_array($v) ? ($v['qty_repaint'] ?? 0)  : ($v->qty_repaint ?? 0);
            return $ac . ':' . (float) $f . ':' . (float) $r;
        })->sort()->values()->implode('|');

        return md5($sprayBooth . '#' . $loadingDateDb . '#' . $parts);
    }

    public function __construct()
    {
        $this->title = "Actual Loading";
        $this->moduleCode = "ALP";
    }

    public function getTableColoumn()
    {
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=> false,'searchable'=>false], //0
            ['data'=>'prod_code','name'=>'prod_code','title'=>'Loading Number'], //2
            ['data'=>'wos_reference','name'=>'wos_reference','title'=>'WOS Date'], //1
            ['data'=>'prod_date','name'=>'prod_date','title'=>'Loading Date'], //3
            ['data'=>'spraybooth','name'=>'spraybooth','title'=>'Spray Booth'], //4
            ['data'=>'status','name'=>'status','title'=>'Status'], //5
            ['data'=>'num_revision','name'=>'num_revision','title'=>'Revision'], //6
            ['data'=>'note','name'=>'note','title'=>'Note'], //7
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'], //8
            ['data'=>'created_at','name'=>'created_at','title'=>'Created At'], //9
        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnDetail()
    {
        $kolom=
        [
            ['data'=>'prod_code','name'=>'prod_code','title'=>'Loading Number'], //0
            ['data'=>'wos_reference','name'=>'wos_reference','title'=>'WOS Date'], //1
            ['data'=>'prod_date','name'=>'prod_date','title'=>'Loading Date'], //2
            ['data'=>'spraybooth','name'=>'spraybooth','title'=>'Spray Booth'], //3
            ['data'=>'article_code_fg','name'=>'article_code_fg','title'=>'Article Code'], //4
            ['data'=>'article_desc_fg','name'=>'article_desc_fg','title'=>'Article Desc'], //5
            ['data'=>'qty_fresh','name'=>'qty_fresh','title'=>'Qty Fresh'], //6
            ['data'=>'qty_repaint','name'=>'qty_repaint','title'=>'Qty Repaint'], //7
            ['data'=>'status','name'=>'status','title'=>'Status'], //8
            ['data'=>'note','name'=>'note','title'=>'Note'] //9
        ];

        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $data['title'] = $this->title;
        $data['subtitle'] = "$this->title";

        $data['kolom'] = $this->getTableColoumn();
        $data['kolomDetail'] = $this->getTableColoumnDetail();

        $data['status'] = $this->statusMap;

        return view("production.actualLoading.index",$data);
    }

    public function getLastCode($key)
    {
        DB::table('master_code')
            ->where('code_key', $key)
            ->update([
                'code_number' => DB::raw('code_number + 1'),
                'updated_by'  => Auth::user()->username,
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);

        $newCode = DB::table('master_code')
            ->where('code_key', $key)
            ->value('code_number');

        $monthRoman = $this->toRomanMonth((int) date('n'));
        $year       = date('Y');
        $codeNumber = str_pad($newCode, 4, '0', STR_PAD_LEFT);

        // Format: ALP-ASN-{bulan romawi}-{tahun}-{codeNumber}
        return "$key-ASN-$monthRoman-$year-$codeNumber";
    }

    /** Konversi bulan (1-12) ke angka romawi (I-XII). */
    private function toRomanMonth(int $month): string
    {
        $romans = [
            1 => 'I',   2 => 'II',  3 => 'III', 4 => 'IV',
            5 => 'V',   6 => 'VI',  7 => 'VII', 8 => 'VIII',
            9 => 'IX',  10 => 'X',  11 => 'XI', 12 => 'XII',
        ];

        return $romans[$month] ?? '';
    }

    public function create(Request $request)
    {
        $data['title'] = "Input $this->title";
        $data['subtitle'] = "Input $this->title";

        $data['sprayBooths'] = DB::table('stock_location_master')
            ->where('location_type', 'booth')
            // sembunyikan booth yang jadi parent (mis. SPRAY BOOTH 5) — user pilih child 5A/5B/5C
            ->whereNotIn('location_code', function ($q) {
                $q->select('parent_location')
                  ->from('stock_location_master')
                  ->whereNotNull('parent_location');
            })
            ->orderBy('location_name')
            ->get();

        $data['statusPrd'] = 'NEW';
        $data['oEdit'] = false;

        return view("production.actualLoading.create",$data);
    }

    /**
     * Ambil daftar FG yang bisa diproduksi di Spray Booth.
     * max_fg = kapasitas dari RM fresh di booth + stok FG jadi di gudang WIP (repaint).
     */
    public function articleBySprayBooth(Request $request)
{
    $fgList = $this->eligibleArticlesForBooth($request->location_code);
    return response()->json($fgList);
}

    /**
     * Detail 1 FG di spray booth terpilih:
     *  - breakdown RM (kebutuhan BOM vs stock di booth)
     *  - breakdown stok FG jadi di gudang ber-type WIP (sumber repaint)
     *
     * Catatan logika status:
     *  - is_limiting : RM ini yang jadi batas kapasitas fresh.
     *                  Kalau RM cuma 1, dia OTOMATIS limiting — itu wajar.
     *  - is_critical : baru dianggap masalah kalau kapasitas 0, ATAU dia limiting
     *                  SEKALIGUS ada RM lain yang kapasitasnya lebih tinggi.
     */
    public function rmDetailBySprayBooth(Request $request)
    {
        // Stok RM dilihat dari pool akuntansi (parent) kalau booth punya parent —
        // setelah konsolidasi, row warehouse_stock ada di parent, bukan di child.
        $locationCode = $this->resolveStockLocation($request->location_code);
        $articleCode  = $request->article_code; // kode article FG

    $wipRows = DB::table('warehouse_stock as ws')
    ->join('article as a', 'a.article_code', '=', 'ws.article_code')
    ->where('ws.article_code', $articleCode)
    ->where('ws.location_number', '012')
    ->select(
        'a.article_code',
        'a.article_alternative_code',
        'a.article_desc',
        'a.uom',
        DB::raw('sum(ws.article_qty) as qty')
    )
    ->groupBy('a.article_code', 'a.article_alternative_code', 'a.article_desc', 'a.uom')
    ->havingRaw('sum(ws.article_qty) > 0')
    ->get();

        $wipTotal = (float) $wipRows->sum('qty');

        // ── 2. Breakdown RM dari BOM aktif ──
        $rows = DB::table('bom_hdr as bh')
            ->join('bom_rm as br', 'br.bom_code', '=', 'bh.bom_code')
            ->join('article as arm', 'arm.article_code', '=', 'br.article_code')
            ->where('bh.status', '3')
            ->where('bh.article_code', $articleCode)
            ->whereIn('arm.article_type', ['RMP', 'RMNP'])
            ->select(
                'arm.article_code',
                'arm.article_alternative_code',
                'arm.article_desc',
                'arm.uom',
                'br.qty as qty_per_fg',
                DB::raw("greatest(coalesce((
                    select sum(ws.article_qty)
                    from warehouse_stock ws
                    where ws.article_code = arm.article_code
                      and ws.location_number = ?
                ),0),0) as stock_qty")
            )
            ->addBinding($locationCode, 'select')
            ->get();

        // FG tanpa BOM RM: kapasitas fresh 0, tapi stok WIP tetap ditampilkan
        if ($rows->isEmpty()) {
            return response()->json([
                'rows'         => [],
                'max_fg_fresh' => 0,
                'wip_rows'     => $wipRows,
                'wip_total'    => $wipTotal,
                'max_fg_total' => $wipTotal,
            ]);
        }

        $rows = $rows->map(function ($r) {
            $perFg     = (float) $r->qty_per_fg;
            $r->max_fg = $perFg > 0 ? max(0, floor(((float) $r->stock_qty) / $perFg)) : 0;
            return $r;
        });

        $overall = (float) $rows->min('max_fg'); // kapasitas fresh sebenarnya
        $best    = (float) $rows->max('max_fg'); // kapasitas tertinggi andai RM lain tak terbatas

        // ada variasi kapasitas antar-RM? kalau tidak, tidak ada yang pantas disebut "penghambat"
        $adaVariasi = ($best > $overall);

        $result = $rows->map(function ($r) use ($overall, $best, $adaVariasi) {
            $perFg = (float) $r->qty_per_fg;
            $stock = (float) $r->stock_qty;
            $maxFg = (float) $r->max_fg;

            $isLimiting = ($maxFg === $overall);
            $isCritical = ($overall <= 0) || ($isLimiting && $adaVariasi);

            $surplusQty = $stock - ($overall * $perFg);

            $deficitQty = null;
            $deficitFg  = null;
            if ($isLimiting && $adaVariasi) {
                $deficitFg  = $best - $maxFg;
                $deficitQty = ($best * $perFg) - $stock;
            }

            return [
                'article_code'             => $r->article_code,
                'article_alternative_code' => $r->article_alternative_code,
                'article_desc'             => $r->article_desc,
                'uom'                      => $r->uom,
                'qty_per_fg'               => $perFg,
                'stock_qty'                => $stock,
                'max_fg'                   => $maxFg,
                'is_limiting'              => $isLimiting,
                'is_critical'              => $isCritical,
                'surplus_qty'              => $surplusQty,
                'deficit_qty'              => $deficitQty,
                'deficit_fg'               => $deficitFg,
            ];
        });

        return response()->json([
            'rows'         => $result,
            'max_fg_fresh' => $overall,
            'wip_rows'     => $wipRows,
            'wip_total'    => $wipTotal,
            'max_fg_total' => $overall + $wipTotal,
        ]);
    }

    public function articleCodeCreate(Request $request)
    {
        $customer = $request->customer;
        $leadingCode = 'FG';

        $lastCode = DB::table('article')
            ->where('third_party','=',$customer)
            ->orderBy('article_alternative_code','DESC')->first();

        if (!$lastCode){
            $newCode = '00001';
        }else{
            $newCode = str_pad(substr($lastCode->article_alternative_code,5)+1, 5, "0", STR_PAD_LEFT);
        }

        $artilceCode = DB::table('third_party')
            ->where('kode',$customer)
            ->select(DB::raw("CONCAT('$leadingCode',inisial,'$newCode') AS new_code"))->value('new_code');

        return Response()->json($artilceCode);
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store(Request $request)
    {
        $username    = Auth::user()->username;
        $articles    = json_decode($request->articles);
        $loadingDate = $request->loadingDate;
        $sprayBooth  = $request->sprayBooth;
        $reference   = $request->reference;
        $note        = $request->note;

        $loadingLocation = $this->loadingLocation;
        $movementType    = 'LOADING';

        $validation = Validator::make($request->all(), [
            'sprayBooth'  => 'required',
            'loadingDate' => 'required',
        ]);
        if ($validation->fails()) {
            $errs = [];
            foreach ($validation->messages()->getMessages() as $m) { $errs[] = $m; }
            return response()->json(['status'=>0,'title'=>"Save $this->title",'message'=>$errs,'alert'=>'error']);
        }
        if (empty($articles)) {
            return response()->json(['status'=>0,'title'=>"Save $this->title",'message'=>[['Tidak ada artikel yang diinput.']],'alert'=>'error']);
        }

        $loadingDateDb = $loadingDate ? implode('-', array_reverse(explode('-', $loadingDate))) : date('Y-m-d');
        $now = date('Y-m-d H:i:s');

        // Movement mengikuti loading_date, bukan hari ini
        $this->movementDate = date('d-m-Y', strtotime($loadingDateDb));

        DB::beginTransaction();
        try {
            // ── Serialkan store() supaya double-submit tidak bikin 2 dokumen ──
            DB::select("SELECT pg_advisory_xact_lock(hashtext(?))", [$this->moduleCode . '-store']);

            // ── Guard idempoten: dokumen dengan isi identik oleh user yang sama
            //    dalam 2 menit terakhir = anggap klik ganda, kembalikan yang lama ──
            $sig = $this->loadingSignature($sprayBooth, $loadingDateDb, $articles);
            $dupe = DB::table('actual_loading_hdr')
                ->where('created_by', $username)
                ->where('spray_booth', $sprayBooth)
                ->where('status', '<>', 5)
                ->where('created_at', '>=', date('Y-m-d H:i:s', strtotime('-120 seconds')))
                ->orderByDesc('id')
                ->get(['prod_code', 'loading_date']);

            foreach ($dupe as $d) {
                $existingArticles = DB::table('actual_loading_det')
                    ->where('prod_code', $d->prod_code)
                    ->get(['article_code', 'qty_fresh', 'qty_repaint'])
                    ->map(fn($x) => (array) $x)
                    ->all();
                $existingSig = $this->loadingSignature($sprayBooth, $loadingDateDb, $existingArticles);
                if ($existingSig === $sig) {
                    DB::commit();
                    \LogActivity::addToLog("Save $this->title", "username: $username double-submit diabaikan, kembali ke {$d->prod_code}");
                    return response()->json([
                        'status'=>1,'title'=>"Save $this->title",
                        'message'=>"$this->title {$d->prod_code} (double-submit diabaikan)",
                        'alert'=>'success','prdNumber'=>$d->prod_code,'oEdit'=>true,
                    ]);
                }
            }

            AppHelpers::resetCode($this->moduleCode);
            $prdNumber = $this->getLastCode($this->moduleCode);

            DB::table('actual_loading_hdr')->insert([
                'prod_code'          => $prdNumber,
                'original_prod_code' => $prdNumber,
                'loading_date'       => $loadingDateDb,
                'spray_booth'        => $sprayBooth,
                'wos_reference'      => $reference,
                'num_revision'       => 0,
                'status'             => 4, // langsung POSTED
                'note'               => $note,
                'created_by'         => $username,
                'updated_by'         => $username,
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);

            // counter movement_code — WAJIB lock dulu (lintas modul)
            $this->lockMovementSequence();
            $seq = (int) DB::table('warehouse_movement')->max('movement_code');

            $urutan   = 0;
            $warnings = [];   // info stok RM yang jadi minus (tidak memblok)
          foreach ($articles as $val) {
    $urutan++;

    $qtyFresh   = max(0, (float)($val->qty_fresh   ?? 0));
    $qtyRepaint = max(0, (float)($val->qty_repaint ?? 0));
    $qtyTotal   = $qtyFresh + $qtyRepaint;

    if ($qtyTotal <= 0) continue;

    // ── TIDAK ADA lagi throw exception untuk over-qty ──
    // Stok RM di booth / FG di WIP boleh jadi minus.
    // Kapasitas hanya dicatat sebagai snapshot info, bukan pemblokir.

    $freshCapacity = $this->freshCapacity($val->article_code, $sprayBooth);
    $wipAvail      = $this->wipAvailable($val->article_code);

    DB::table('actual_loading_det')->insert([
        'prod_code'              => $prdNumber,
        'urutan'                 => $urutan,
        'article_code'           => $val->article_code,
        'uom'                    => $val->uom ?? null,
        'qty'                    => $qtyTotal,
        'qty_fresh'              => $qtyFresh,
        'qty_repaint'            => $qtyRepaint,
        'stock_fresh_snapshot'   => $freshCapacity,   // snapshot kapasitas saat input
        'stock_repaint_snapshot' => $wipAvail,
        'note'                   => $val->note ?? null,
        'created_by'             => $username,
        'updated_by'             => $username,
        'created_at'             => $now,
        'updated_at'             => $now,
    ]);

    // FRESH: RM keluar dari booth (boleh minus) → FG masuk gudang loading
    if ($qtyFresh > 0) {
        $bomRm = $this->getBomRm($val->article_code);
        if ($bomRm->isEmpty()) {
            throw new \Exception(
                "Artikel {$val->article_code} tidak punya BOM RM aktif (status 3). ".
                "Fresh loading akan menambah FG tanpa mengurangi RM — dibatalkan."
            );
        }
        $poolLoc = $this->resolveStockLocation($sprayBooth);
        foreach ($bomRm as $rm) {
            if ((float) $rm->qty_per_fg <= 0) {
                throw new \Exception("BOM {$val->article_code} punya komponen {$rm->article_code} dengan qty per FG 0 — perbaiki BOM dulu.");
            }
            $need = $qtyFresh * (float) $rm->qty_per_fg;
            $have = (float) DB::table('warehouse_stock')
                ->where('site_code', 'HO')
                ->where('article_code', $rm->article_code)
                ->where('location_number', $poolLoc)
                ->sum('article_qty');
            if ($need > $have) {
                $warnings[] = "{$rm->article_code}: butuh " . round($need, 2)
                            . ", stok booth " . round($have, 2)
                            . " → minus " . round($need - $have, 2);
            }
            $this->postOut(
                $seq, $rm->article_code, $need,
                $sprayBooth, $loadingLocation, $movementType, $prdNumber,
                "Fresh RM", $username
            );
        }
        $this->postIn(
            $seq, $val->article_code, $val->uom, $qtyFresh,
            $loadingLocation, $sprayBooth, $movementType, $prdNumber,
            "Fresh RM", $username
        );
    }

    // REPAINT: FG dari WIP → gudang loading
    if ($qtyRepaint > 0) {
        $this->moveRepaintFromWipAllowMinus(
            $seq, $val->article_code, $val->uom, $qtyRepaint,
            $loadingLocation, $movementType, $prdNumber, $username
        );
    }
}

            // Dokumen baru bisa langsung dibuat dengan loadingDate backdate
            // (bukan cuma lewat edit belakangan) — kalau tanggalnya sudah
            // tercakup OPENING BALANCE aktif di lokasi manapun yang tersentuh
            // (biasanya 047), OB harus langsung menyerap saat itu juga.
            $newDocDateYmd = $loadingDateDb;   // sudah format Y-m-d (lihat atas)
            $adj = app(\App\Http\Controllers\StockAdjustmentController::class);
            $newMovRows = DB::table('warehouse_movement')
                ->where('movement_transnno', $prdNumber)
                ->get(['artikel_code', 'location_number', 'movement_plus', 'movement_min']);

            foreach ($newMovRows as $mv) {
                $signed = (float) $mv->movement_plus - (float) $mv->movement_min;
                if (abs($signed) < 0.000001) continue;
                if (!$adj->obBoundaryFor($mv->artikel_code, $mv->location_number, $newDocDateYmd)) continue;

                $adj->absorbIntoLatestOpeningBalance(
                    $mv->artikel_code, $mv->location_number, $signed, $username,
                    "Save Actual Loading {$prdNumber} langsung bertanggal {$loadingDate} (sudah tercakup OB)"
                );
            }

            DB::commit();
            $title   = "Save $this->title";
            $message = "$title $prdNumber is successfully saved";
            if (!empty($warnings)) {
                $message .= "\n⚠ Stok RM booth jadi minus (loading tetap diproses):\n- " . implode("\n- ", $warnings);
            }
            \LogActivity::addToLog($title, "username: $username Status $message");
            return response()->json(['status'=>1,'title'=>$title,'message'=>$message,'alert'=>(!empty($warnings)?'warning':'success'),'warnings'=>$warnings,'prdNumber'=>$prdNumber,'oEdit'=>true]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status'=>0,'title'=>"Save $this->title",'message'=>[[$e->getMessage()]],'alert'=>'error']);
        }
    }

    // =========================================================================
    // HELPER STOK & MOVEMENT
    // =========================================================================

    /** Komponen RM (RMP/RMNP) dari BOM aktif suatu FG + qty per FG */
    private function getBomRm($fgArticle)
    {
        return DB::table('bom_hdr as bh')
            ->join('bom_rm as br', 'br.bom_code', '=', 'bh.bom_code')
            ->join('article as arm', 'arm.article_code', '=', 'br.article_code')
            ->where('bh.status', '3')
            ->where('bh.article_code', $fgArticle)
            ->whereIn('arm.article_type', ['RMP','RMNP'])
            ->select('arm.article_code','arm.uom','br.qty as qty_per_fg')
            ->get();
    }

    /** Kapasitas fresh: berapa FG bisa dibuat dari RM di booth (bottleneck BOM). */
    private function freshCapacity($fgArticle, $sprayBooth)
    {
        $rows = $this->getBomRm($fgArticle);
        if ($rows->isEmpty()) return 0;

        $stockLoc = $this->resolveStockLocation($sprayBooth);

        $maxFg = null;
        foreach ($rows as $rm) {
            $perFg = (float)$rm->qty_per_fg;
            if ($perFg <= 0) continue;

            $have = (float) DB::table('warehouse_stock')
                ->where('article_code', $rm->article_code)
                ->where('location_number', $stockLoc)
                ->sum('article_qty');

            $have    = max(0, $have);                  // saldo minus dianggap 0
            $canMake = max(0, floor($have / $perFg));
            $maxFg   = is_null($maxFg) ? $canMake : min($maxFg, $canMake);
        }
        return (float) max(0, $maxFg ?? 0);
    }

    /** Total FG di gudang WIP, lokasi bersaldo minus dianggap 0. */
    private function wipAvailable($fgArticle)
{
    $qty = (float) DB::table('warehouse_stock')
        ->where('article_code', $fgArticle)
        ->where('location_number', '012')
        ->sum('article_qty');

    return max(0, $qty);
}

    /**
     * Alokasi qty repaint dari beberapa gudang WIP (greedy),
     * DAN langsung catat pasangan OUT+IN per sumber supaya
     * movement_from di sisi IN akurat (bukan hardcode 'WIP').
     */
    /**
 * Versi yang MENGIZINKAN stok WIP minus.
 * Alokasi greedy dari WIP yang ada; kalau kurang, sisa diambil dari
 * lokasi WIP pertama (saldonya jadi minus) — tidak throw exception.
 */
private function moveRepaintFromWipAllowMinus(&$seq, $fgArticle, $uom, $qtyNeeded, $toLoc, $movementType, $transno, $username)
{
    $avail = max(0, (float) DB::table('warehouse_stock')
        ->where('article_code', $fgArticle)
        ->where('location_number', '012')
        ->sum('article_qty'));

    $remaining = $qtyNeeded;

    if ($avail > 0) {
        $take = min($remaining, $avail);
        $this->postOut($seq, $fgArticle, $take, '012', $toLoc, $movementType, $transno, "Repaint dari WIP", $username);
        $this->postIn ($seq, $fgArticle, $uom, $take, $toLoc, '012', $movementType, $transno, "FG Repaint", $username);
        $remaining -= $take;
    }

    // sisa yang belum tercukupi -> tetap dari 012, boleh minus
    if ($remaining > 0) {
        $this->postOut($seq, $fgArticle, $remaining, '012', $toLoc, $movementType, $transno, "FG Repaint", $username);
        $this->postIn ($seq, $fgArticle, $uom, $remaining, $toLoc, '012', $movementType, $transno, "FG Repaint", $username);
    }
}

    /** Satu baris KELUAR: kurangi warehouse_stock sumber + movement (min) */
    private function postOut(&$seq, $article, $qty, $fromLoc, $toLoc, $movementType, $transno, $desc, $username)
    {
        if ($qty <= 0) return;
        $now = date('Y-m-d H:i:s');
        $adesc = DB::table('article')->where('article_code',$article)->value('article_desc');

        // Lokasi stok akuntansi: booth child → parent (pool). $fromLoc tetap fisik
        // untuk jejak movement_from.
        $stockLoc = $this->resolveStockLocation($fromLoc);

        $affected = DB::table('warehouse_stock')
            ->where('article_code',$article)->where('location_number',$stockLoc)
            ->update([
                'article_qty' => DB::raw('coalesce(article_qty,0) - '.$qty),
                'updated_by'  => $username,
                'updated_at'  => $now,
            ]);

        if ($affected === 0) {
            DB::table('warehouse_stock')->insert([
                'site_code'       => 'HO',
                'article_code'    => $article,
                'location_number' => $stockLoc,
                'article_qty'     => -$qty,
                'created_by'      => $username,
                'updated_by'      => $username,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }

        // location_number = pool akuntansi; movement_from/to = jejak operasi (fisik)
        $this->writeMovement($seq, $article, $adesc, $qty, $stockLoc, 'out',
                             $fromLoc, $toLoc, $movementType, $transno, $desc, $username);
    }

    /** Satu baris MASUK: tambah warehouse_stock tujuan + movement (plus) */
    private function postIn(&$seq, $article, $uom, $qty, $toLoc, $fromLoc, $movementType, $transno, $desc, $username)
    {
        if ($qty <= 0) return;
        $now = date('Y-m-d H:i:s');

        $art = DB::table('article')->where('article_code',$article)
            ->select('article_desc','article_type')->first();
        $adesc = $art->article_desc ?? null;
        $dept  = $art->article_type ?? null;

        // Lokasi stok akuntansi: booth child → parent (pool). $fromLoc/$toLoc tetap
        // fisik untuk jejak movement_from/to.
        $stockLoc = $this->resolveStockLocation($toLoc);

        $exists = DB::table('warehouse_stock')
            ->where('article_code',$article)->where('location_number',$stockLoc)->exists();

        if ($exists) {
            DB::table('warehouse_stock')
                ->where('article_code',$article)->where('location_number',$stockLoc)
                ->update([
                    'article_qty' => DB::raw('coalesce(article_qty,0) + '.$qty),
                    'updated_by'  => $username,
                    'updated_at'  => $now,
                ]);
        } else {
            DB::table('warehouse_stock')->insert([
                'site_code'       => 'HO',
                'article_code'    => $article,
                'location_number' => $stockLoc,
                'article_qty'     => $qty,
                'uom'             => $uom,
                'dept_code'       => $dept,
                'created_by'      => $username,
                'updated_by'      => $username,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }

        // location_number = pool akuntansi; movement_from/to = jejak operasi (fisik)
        $this->writeMovement($seq, $article, $adesc, $qty, $stockLoc, 'in',
                             $fromLoc, $toLoc, $movementType, $transno, $desc, $username);
    }

    /** Tulis 1 baris warehouse_movement. $direction: 'in'|'out'. Naikkan $seq. */
    private function writeMovement(&$seq, $article, $adesc, $qty, $location, $direction,
                                   $fromLoc, $toLoc, $movementType, $transno, $desc, $username)
    {
        $sign  = ($direction === 'in') ? '+' : '-';

        // Movement mengikuti loading_date dokumen — BUKAN hari ini. Supaya saat
        // dokumen lama diedit, baris movement tetap di tanggal aslinya (laporan
        // as-of & saldo berjalan tetap konsisten). Di-set store()/update() lewat
        // $this->movementDate; fallback ke hari ini kalau tidak di-set.
        $mvDate    = $this->movementDate ?: date('d-m-Y');   // d-m-Y
        $tsObj     = \DateTime::createFromFormat('d-m-Y', $mvDate);
        $mvDateYmd = $tsObj ? $tsObj->format('Y-m-d') : date('Y-m-d');

        DB::table('warehouse_movement')->insert([
            'movement_code'     => ++$seq,
            'movement_date'     => $mvDate,
            'site_code'         => 'HO',
            'location_number'   => $location,
            'artikel_code'      => $article,
            'artikel_desc'      => $adesc,
            'movement_min'      => ($direction === 'out') ? $qty : 0,
            'movement_plus'     => ($direction === 'in')  ? $qty : 0,
            'movement_from'     => $fromLoc,
            'movement_to'       => $toLoc,
            'movement_type'     => $movementType,
            'movement_transnno' => $transno,
            'movement_desc'     => $desc,
            'last_qty'          => DB::raw("get_last_qty_new('$article','$mvDateYmd','HO','$location') $sign $qty"),
            'created_by'        => $username,
            'created_at'        => date('Y-m-d H:i:s'),
        ]);
    }

    // =========================================================================
    // UN-POSTING (dipakai CANCEL & UPDATE)
    // =========================================================================

    /**
     * Kembalikan warehouse_stock ke posisi sebelum dokumen ini diposting.
     * TIDAK membuat movement baru.
     *
     * @param string $transno        nomor dokumen (movement_transnno)
     * @param bool   $hapusMovement  true = baris movement dihapus + last_qty di-recalc
     * @param bool   $checkStock     true = tolak kalau stok tujuan sudah tidak cukup
     */
    private function unPosting($transno, $username, $hapusMovement = false, $checkStock = true)
    {
        $now = date('Y-m-d H:i:s');

        // Catat dulu artikel+lokasi yang tersentuh dokumen ini. Diambil SEMUA
        // (termasuk yang net-nya 0), karena barisnya tetap hilang sehingga saldo
        // berjalan dokumen lain setelahnya ikut bergeser.
        $pairs = DB::table('warehouse_movement')
            ->where('movement_transnno', $transno)
            ->select('artikel_code', 'location_number')
            ->distinct()
            ->get()
            ->map(function ($r) {
                return [
                    'artikel_code'    => $r->artikel_code,
                    'location_number' => $r->location_number,
                ];
            })
            ->all();

        $nets = DB::table('warehouse_movement')
            ->where('movement_transnno', $transno)
            ->select(
                'artikel_code',
                'location_number',
                DB::raw('sum(movement_plus - movement_min) as net_qty')
            )
            ->groupBy('artikel_code', 'location_number')
            ->havingRaw('sum(movement_plus - movement_min) <> 0')
            ->get();

        foreach ($nets as $row) {
            $net = (float) $row->net_qty;
            $loc = $row->location_number;
            $art = $row->artikel_code;

            // net > 0 artinya dokumen ini dulu MENAMBAH stok di lokasi tsb,
            // jadi sekarang ditarik balik. Pastikan barangnya masih ada.
            if ($net > 0 && $checkStock) {
                $have = (float) DB::table('warehouse_stock')
                    ->where('article_code', $art)
                    ->where('location_number', $loc)
                    ->sum('article_qty');

                if ($have < $net) {
                    throw new \Exception(
                        "Tidak bisa cancel: stok {$art} di gudang {$loc} tinggal {$have}, ".
                        "butuh {$net} untuk dikembalikan. Kemungkinan sudah dipakai transaksi lain."
                    );
                }
            }

            $exists = DB::table('warehouse_stock')
                ->where('article_code', $art)
                ->where('location_number', $loc)
                ->exists();

            if ($exists) {
                // balik arah: net positif dikurangi, net negatif ditambah
                DB::table('warehouse_stock')
                    ->where('article_code', $art)
                    ->where('location_number', $loc)
                    ->update([
                        'article_qty' => DB::raw('coalesce(article_qty,0) - '.$net),
                        'updated_by'  => $username,
                        'updated_at'  => $now,
                    ]);
            } elseif ($net < 0) {
                // lokasi asal sudah tidak punya baris stok -> buat ulang
                $a = DB::table('article')->where('article_code', $art)
                    ->select('uom', 'article_type')->first();

                DB::table('warehouse_stock')->insert([
                    'site_code'       => 'HO',
                    'article_code'    => $art,
                    'location_number' => $loc,
                    'article_qty'     => abs($net),
                    'uom'             => $a->uom ?? null,
                    'dept_code'       => $a->article_type ?? null,
                    'created_by'      => $username,
                    'updated_by'      => $username,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
            }
        }

        if ($hapusMovement) {
            DB::table('warehouse_movement')
                ->where('movement_transnno', $transno)
                ->delete();

            $this->recalcLastQty($pairs);
        }
    }

    /**
     * Hitung ulang last_qty (saldo berjalan) untuk kombinasi artikel+lokasi tertentu.
     *
     * PENTING: urutannya HARUS sama persis dengan get_last_qty_new(), yaitu
     * TO_DATE(movement_date,'dd-mm-yyyy') lalu movement_code. Kalau beda urutan,
     * hasil recalc tidak akan cocok dengan last_qty yang ditulis saat posting baru.
     */
    private function recalcLastQty(array $pairs)
    {
        if (empty($pairs)) {
            return;
        }

        $tuples   = [];
        $bindings = [];
        foreach ($pairs as $p) {
            $tuples[]   = '(?,?)';
            $bindings[] = $p['artikel_code'];
            $bindings[] = $p['location_number'];
        }
        $tupleSql = implode(',', $tuples);

        // warehouse_movement memakai movement_code sebagai PK, bukan id
        DB::statement("
            WITH rekalk AS (
                SELECT movement_code,
                       SUM(movement_plus - movement_min) OVER (
                           PARTITION BY site_code, artikel_code, location_number
                           ORDER BY TO_DATE(movement_date, 'dd-mm-yyyy'),
                                    movement_code
                           ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
                       ) AS saldo
                FROM warehouse_movement
                WHERE (artikel_code, location_number) IN ($tupleSql)
            )
            UPDATE warehouse_movement wm
            SET    last_qty = r.saldo
            FROM   rekalk r
            WHERE  wm.movement_code = r.movement_code
        ", $bindings);
    }

    /**
     * Balikin stok dokumen sebelum diposting ulang saat edit.
     * Movement lama dihapus karena dokumen langsung diposting ulang dengan
     * nomor yang sama — kalau dibiarkan, laporan movement menghitung dobel.
     */
    private function reverseDocumentStock($prdNumber, $username)
    {
        // true  = hapus movement lama
        // false = tidak perlu cek kecukupan stok, karena langsung di-repost
        $this->unPosting($prdNumber, $username, true, false);
    }

    private function applyStockDelta(string $article, string $location, float $delta, string $username): void
    {
        $now = date('Y-m-d H:i:s');
        $aff = DB::table('warehouse_stock')
            ->where('site_code', 'HO')
            ->where('article_code', $article)
            ->where('location_number', $location)
            ->update([
                'article_qty' => DB::raw('coalesce(article_qty,0) + (' . $delta . ')'),
                'updated_by'  => $username,
                'updated_at'  => $now,
            ]);

        if ($aff === 0) {
            $a = DB::table('article')->where('article_code', $article)->select('uom', 'article_type')->first();
            DB::table('warehouse_stock')->insert([
                'site_code'       => 'HO',
                'article_code'    => $article,
                'location_number' => $location,
                'article_qty'     => $delta,
                'uom'             => $a->uom ?? null,
                'dept_code'       => $a->article_type ?? null,
                'created_by'      => $username,
                'updated_by'      => $username,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }
    }

    /**
     * Repost movement dokumen loading TANPA menggeser posisi kronologis.
     *
     * Untuk tiap grup (artikel, lokasi_pool, arah):
     *  - masih ada di versi baru  → UPDATE baris movement_code TERKECIL ke total baru,
     *                               hapus baris surplus grup itu (posisi & tanggal tetap)
     *  - hilang di versi baru     → hapus semua baris grup
     *  - grup baru (artikel baru) → append 1 baris (movement_code = max+1)
     * warehouse_stock disesuaikan pakai delta (total_baru − total_lama) per grup.
     * Terakhir recalcLastQty untuk semua (artikel, lokasi) tersentuh.
     *
     * @param array $lines [ ['article'=>fg, 'qtyFresh'=>x, 'qtyRepaint'=>y], ... ]  qtyTotal > 0
     * @return array daftar peringatan stok minus (tidak memblok)
     */
    private function repostLoadingKeepPosition(string $prdNumber, string $sprayBooth, array $lines, string $username): array
    {
        $warnings   = [];
        $poolBooth  = $this->resolveStockLocation($sprayBooth);
        $loadingLoc = $this->loadingLocation;   // 047 (tanpa parent)
        $wipLoc     = '012';
        $mvDate     = $this->movementDate ?: date('d-m-Y');

        // 1. Target movement per grup "artikel|lokasi|arah" → total qty
        $target = [];
        $addTarget = function ($art, $loc, $dir, $qty, $from, $to, $desc) use (&$target) {
            if ($qty <= 0) return;
            $k = "$art|$loc|$dir";
            if (!isset($target[$k])) {
                $target[$k] = ['art' => $art, 'loc' => $loc, 'dir' => $dir, 'qty' => 0.0,
                               'from' => $from, 'to' => $to, 'desc' => $desc];
            }
            $target[$k]['qty'] += $qty;
        };

        foreach ($lines as $ln) {
            $fg = $ln['article'];
            $qf = (float) ($ln['qtyFresh'] ?? 0);
            $qr = (float) ($ln['qtyRepaint'] ?? 0);

            if ($qf > 0) {
                $bom = $this->getBomRm($fg);
                if ($bom->isEmpty()) {
                    throw new \Exception("Artikel {$fg} tidak punya BOM RM aktif (status 3). Fresh loading dibatalkan.");
                }
                foreach ($bom as $rm) {
                    if ((float) $rm->qty_per_fg <= 0) {
                        throw new \Exception("BOM {$fg} komponen {$rm->article_code} qty per FG 0 — perbaiki BOM dulu.");
                    }
                    $addTarget($rm->article_code, $poolBooth, 'out', $qf * (float) $rm->qty_per_fg,
                        $sprayBooth, $loadingLoc, 'Fresh RM (edit)');
                }
                $addTarget($fg, $loadingLoc, 'in', $qf, $sprayBooth, $loadingLoc, 'Fresh RM (edit)');
            }
            if ($qr > 0) {
                $addTarget($fg, $wipLoc, 'out', $qr, $wipLoc, $loadingLoc, 'FG Repaint (edit)');
                $addTarget($fg, $loadingLoc, 'in', $qr, $wipLoc, $loadingLoc, 'FG Repaint (edit)');
            }
        }

        // 2. Grup movement LAMA dokumen ini
        $oldRows = DB::table('warehouse_movement')
            ->where('movement_transnno', $prdNumber)
            ->orderBy('movement_code')
            ->get();

        $oldGroups = [];
        foreach ($oldRows as $r) {
            $dir = ((float) $r->movement_plus > 0) ? 'in' : 'out';
            // fold lokasi lama ke pool — kalau ada baris nyasar di kode booth fisik
            // (belum ke-migrasi), disatukan dgn grup pool yang benar.
            $foldedLoc = $this->resolveStockLocation($r->location_number);
            $k = "{$r->artikel_code}|{$foldedLoc}|$dir";
            if (!isset($oldGroups[$k])) $oldGroups[$k] = ['codes' => [], 'total' => 0.0];
            $oldGroups[$k]['codes'][] = $r->movement_code;
            $oldGroups[$k]['total']  += ($dir === 'in') ? (float) $r->movement_plus : (float) $r->movement_min;
        }

        $affectedPairs = [];
        $keys = array_values(array_unique(array_merge(array_keys($target), array_keys($oldGroups))));

        $this->lockMovementSequence();
        $seq = (int) DB::table('warehouse_movement')->max('movement_code');

        foreach ($keys as $k) {
            [$art, $loc, $dir] = explode('|', $k);
            $affectedPairs[] = ['artikel_code' => $art, 'location_number' => $loc];

            $oldT = $oldGroups[$k]['total'] ?? 0.0;
            $newT = $target[$k]['qty']      ?? 0.0;

            // stok: out dulu -oldT skrg -newT → += (oldT-newT); in dulu +oldT skrg +newT → += (newT-oldT)
            $stockDelta = ($dir === 'in') ? ($newT - $oldT) : ($oldT - $newT);
            if (abs($stockDelta) > 1e-9) {
                $this->applyStockDelta($art, $loc, $stockDelta, $username);
            }

            if ($newT > 0 && isset($oldGroups[$k])) {
                $codes = $oldGroups[$k]['codes'];
                $keepCode = array_shift($codes);
                DB::table('warehouse_movement')->where('movement_code', $keepCode)->update([
                    'movement_plus'   => ($dir === 'in')  ? $newT : 0,
                    'movement_min'    => ($dir === 'out') ? $newT : 0,
                    'movement_desc'   => $target[$k]['desc'],
                    'movement_date'   => $mvDate,
                    'location_number' => $loc,   // pastikan di pool (fold)
                ]);
                if (!empty($codes)) {
                    DB::table('warehouse_movement')->whereIn('movement_code', $codes)->delete();
                }
            } elseif ($newT > 0) {
                $t = $target[$k];
                $adesc = DB::table('article')->where('article_code', $art)->value('article_desc');
                $this->writeMovement($seq, $art, $adesc, $newT, $loc, $dir,
                    $t['from'], $t['to'], 'LOADING', $prdNumber, $t['desc'], $username);
            } else {
                DB::table('warehouse_movement')->whereIn('movement_code', $oldGroups[$k]['codes'])->delete();
            }

            if ($dir === 'out' && $newT > 0) {
                $have = (float) DB::table('warehouse_stock')->where('site_code', 'HO')
                    ->where('article_code', $art)->where('location_number', $loc)->sum('article_qty');
                if ($have < 0) {
                    $warnings[] = "{$art} @ {$loc}: stok jadi minus " . round($have, 2) . " setelah edit";
                }
            }
        }

        $this->recalcLastQty($affectedPairs);

        return $warnings;
    }

    // =========================================================================
    // SHOW / EDIT
    // =========================================================================

    public function show(Request $request)
    {
        $id = Crypt::decryptString($request->id);
        $username = Auth::user()->username;
        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";

        $data['header'] = DB::table('actual_loading_hdr as alh')
            ->leftJoin('stock_location_master as slm', 'slm.location_code', '=', 'alh.spray_booth')
            ->where('alh.id', $id)
            ->select(
                'alh.*',
                DB::raw("to_char(alh.loading_date, 'DD-MM-YYYY') as loading_date_fmt"),
                DB::raw("coalesce(slm.location_name, alh.spray_booth) as spray_booth_name")
            )
            ->first();

        if (!$data['header']) {
            abort(404);
        }

        $prdNumber = $data['header']->prod_code;

        $data['details'] = DB::table('actual_loading_det as ald')
            ->leftJoin('article as a', 'a.article_code', '=', 'ald.article_code')
            ->where('ald.prod_code', $prdNumber)
            ->select(
                'ald.*',
                'a.article_alternative_code',
                'a.article_desc'
            )
            ->orderBy('ald.urutan')
            ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode, $prdNumber, $username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode, $prdNumber, $username);

        $data['oEdit'] = true;
        $data['statusPrd'] = $this->statusMap[$data['header']->status] ?? $data['header']->status;

        return view("production.actualLoading.show", $data);
    }

    public function edit(Request $request)
    {
        $id = Crypt::decryptString($request->id);
        $username = Auth::user()->username;
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['header'] = DB::table('actual_loading_hdr as alh')
            ->leftJoin('stock_location_master as slm', 'slm.location_code', '=', 'alh.spray_booth')
            ->where('alh.id', $id)
            ->select(
                'alh.*',
                DB::raw("to_char(alh.loading_date, 'DD-MM-YYYY') as loading_date_fmt"),
                DB::raw("coalesce(slm.location_name, alh.spray_booth) as spray_booth_name")
            )
            ->first();

        if (!$data['header']) {
            abort(404);
        }

        $prdNumber = $data['header']->prod_code;

        $data['details'] = DB::table('actual_loading_det as ald')
            ->leftJoin('article as a', 'a.article_code', '=', 'ald.article_code')
            ->where('ald.prod_code', $prdNumber)
            ->select(
                'ald.*',
                'a.article_alternative_code',
                'a.article_desc',
                'a.uom as uom_master'
            )
            ->orderBy('ald.urutan')
            ->get();

        $data['sprayBooths'] = DB::table('stock_location_master')
            ->where('location_type', 'booth')
            // sembunyikan booth yang jadi parent (mis. SPRAY BOOTH 5) — user pilih child 5A/5B/5C
            ->whereNotIn('location_code', function ($q) {
                $q->select('parent_location')
                  ->from('stock_location_master')
                  ->whereNotNull('parent_location');
            })
            ->orderBy('location_name')
            ->get();

        // ── Log histori, dikelompokkan per revisi ──
        $logs = DB::table('actual_loading_log')
            ->where('prod_code', $prdNumber)
            ->orderBy('revision', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $data['history'] = $logs->groupBy('revision')->map(function ($group) {
            return [
                'revision'   => $group->first()->revision,
                'created_by' => $group->first()->created_by,
                'created_at' => $group->first()->created_at,
                'changes'    => $group,
            ];
        })->values();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode, $prdNumber, $username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode, $prdNumber, $username);

        $data['oEdit'] = true;
        $data['statusPrd'] = $this->statusMap[$data['header']->status] ?? $data['header']->status;

        return view("production.actualLoading.edit", $data);
    }

    // =========================================================================
    // CANCEL
    // =========================================================================

    /**
     * Cancel Actual Loading:
     *  - stok gudang loading dikembalikan ke spray booth / gudang WIP asal
     *  - baris movement dokumen dihapus + last_qty di-recalc
     *  - status jadi 5 (CANCELED)
     */
    public function cancel(Request $request)
    {
        $username = Auth::user()->username;
        $id       = Crypt::decryptString($request->id);
        $now      = date('Y-m-d H:i:s');
        $title    = "Cancel $this->title";

        DB::beginTransaction();
        try {
            $hdr = DB::table('actual_loading_hdr')
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            if (!$hdr) {
                throw new \Exception("Dokumen tidak ditemukan.");
            }
            if ((int) $hdr->status === 5) {
                throw new \Exception("Dokumen {$hdr->prod_code} sudah berstatus CANCELED.");
            }

            $prdNumber = $hdr->prod_code;

            // Guard: jangan cancel kalau sudah dipakai dokumen Finish Goods aktif.
            // HAPUS blok ini kalau tabel actual_finish_goods_hdr belum dibuat.
            $adaFg = DB::table('actual_finish_goods_hdr')
                ->where('loading_code', $prdNumber)
                ->where('status', '<>', 5)
                ->value('fg_code');

            if ($adaFg) {
                throw new \Exception(
                    "Tidak bisa cancel: sudah ada Actual Finish Goods {$adaFg} ".
                    "yang mengacu ke dokumen ini. Cancel dokumen FG tersebut terlebih dahulu."
                );
            }

            // Balikin stok + hapus movement + recalc last_qty
            $this->unPosting($prdNumber, $username, true);

            DB::table('actual_loading_hdr')
                ->where('id', $id)
                ->update([
                    'status'     => 5,
                    'updated_by' => $username,
                    'updated_at' => $now,
                ]);

            DB::commit();

            $message = "$title $prdNumber berhasil dibatalkan, stok dikembalikan";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['title'=>$title, 'alert'=>'success', 'message'=>$message]);

        } catch (\Throwable $e) {
            DB::rollBack();
            \LogActivity::addToLog($title, substr("username: $username Status GAGAL: ".$e->getMessage(), 0, 250));
            return redirect()->back()->with(['title'=>$title, 'alert'=>'warning', 'message'=>$e->getMessage()]);
        }
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function update(Request $request)
    {
        $username    = Auth::user()->username;
        $prdNumber   = $request->prdNumber;
        $loadingDate = $request->loadingDate;
        $sprayBooth  = $request->sprayBooth;
        $reference   = $request->reference;
        $note        = $request->note;
        $articles    = json_decode($request->articles);
        $now         = date('Y-m-d H:i:s');

        $loadingLocation = $this->loadingLocation;

        $validation = Validator::make($request->all(), [
            'sprayBooth'  => 'required',
            'loadingDate' => 'required',
        ]);
        if ($validation->fails()) {
            $errs = [];
            foreach ($validation->messages()->getMessages() as $m) { $errs[] = $m; }
            return response()->json(['status'=>0,'title'=>"Update $this->title",'message'=>$errs,'alert'=>'error']);
        }
        if (empty($articles)) {
            return response()->json(['status'=>0,'title'=>"Update $this->title",'message'=>[['Tidak ada artikel yang diinput.']],'alert'=>'error']);
        }

        $loadingDateDb = $loadingDate ? implode('-', array_reverse(explode('-', $loadingDate))) : null;

        DB::beginTransaction();
        try {
            $oldHeader = DB::table('actual_loading_hdr')
                ->where('prod_code', $prdNumber)
                ->lockForUpdate()
                ->first();

            if (!$oldHeader) {
                throw new \Exception("Data $prdNumber tidak ditemukan.");
            }
            if ((int) $oldHeader->status === 5) {
                throw new \Exception("Dokumen $prdNumber sudah CANCELED dan tidak bisa diedit.");
            }
            // Dokumen POSTED (4) boleh diedit — hanya artikel & qty. Stok + movement
            // lama dibalik penuh (reverseDocumentStock) lalu diposting ulang dengan
            // nilai baru, jadi selisihnya otomatis terkoreksi. Status tidak diubah
            // (yang tadinya POSTED tetap POSTED).

            // Movement repost mengikuti loading_date dokumen (efektif), BUKAN hari ini —
            // supaya baris movement tetap di tanggal aslinya walau diedit belakangan.
            $effectiveLoadingDb = $loadingDateDb ?: $oldHeader->loading_date;
            $this->movementDate = date('d-m-Y', strtotime($effectiveLoadingDb));

            $newRevision = $oldHeader->num_revision + 1;
            $changeCount = 0;

            // ── 1. Diff-log field header ──
            $headerFieldsMap = [
                'loading_date'  => ['old' => $oldHeader->loading_date,  'new' => $loadingDateDb, 'label' => 'Loading Date'],
                'spray_booth'   => ['old' => $oldHeader->spray_booth,   'new' => $sprayBooth,    'label' => 'Spray Booth'],
                'wos_reference' => ['old' => $oldHeader->wos_reference, 'new' => $reference,     'label' => 'Referensi WOS'],
                'note'          => ['old' => $oldHeader->note,          'new' => $note,          'label' => 'Note'],
            ];
            foreach ($headerFieldsMap as $val) {
                if ((string)$val['old'] !== (string)$val['new']) {
                    DB::table('actual_loading_log')->insert([
                        'prod_code' => $prdNumber, 'revision' => $newRevision, 'ref_type' => 'hdr',
                        'article_code' => null, 'field_name' => $val['label'],
                        'old_value' => $val['old'], 'new_value' => $val['new'],
                        'created_by' => $username, 'created_at' => $now,
                    ]);
                    $changeCount++;
                }
            }

            // ── 2. Kalau artikel+qty+booth TIDAK berubah, jangan reverse+repost
            //    (menghindari menumpuk baris movement baru untuk perubahan note saja) ──
            $oldDetForSig = DB::table('actual_loading_det')->where('prod_code', $prdNumber)
                ->get(['article_code', 'qty_fresh', 'qty_repaint'])->map(fn($x) => (array) $x)->all();
            $detBerubah = $this->loadingSignature($sprayBooth, $loadingDateDb, $articles)
                        !== $this->loadingSignature($oldHeader->spray_booth, $oldHeader->loading_date, $oldDetForSig);

            $warnings = [];

          if ($detBerubah) {
            // ── 3. Diff-log per artikel + rebuild detail. Movement TIDAK dibalik+
            //    di-append; diperbaiki in-place (repostLoadingKeepPosition) supaya
            //    posisi kronologis baris tetap → saldo berjalan/minus tidak meleset. ──
            $oldDetails = DB::table('actual_loading_det')->where('prod_code', $prdNumber)->get()->keyBy('article_code');
            $seenArticles = [];
            $urutan   = 0;
            $lines    = [];   // untuk repost movement

            DB::table('actual_loading_det')->where('prod_code', $prdNumber)->delete();

            foreach ($articles as $val) {
                $urutan++;
                $articleCode = $val->article_code;
                $seenArticles[] = $articleCode;

                $qtyFresh   = (float)($val->qty_fresh ?? 0);
                $qtyRepaint = (float)($val->qty_repaint ?? 0);
                $qtyTotal   = $qtyFresh + $qtyRepaint;
                $newNote    = $val->note ?? null;

                $old = $oldDetails->get($articleCode);
                if ($old) {
                    foreach ([
                        'Qty Fresh'   => [(float)$old->qty_fresh, $qtyFresh],
                        'Qty Repaint' => [(float)$old->qty_repaint, $qtyRepaint],
                        'Note'        => [$old->note, $newNote],
                    ] as $label => [$oldVal, $newVal]) {
                        if ((string)$oldVal !== (string)$newVal) {
                            DB::table('actual_loading_log')->insert([
                                'prod_code' => $prdNumber, 'revision' => $newRevision, 'ref_type' => 'det',
                                'article_code' => $articleCode, 'field_name' => $label,
                                'old_value' => $oldVal, 'new_value' => $newVal,
                                'created_by' => $username, 'created_at' => $now,
                            ]);
                            $changeCount++;
                        }
                    }
                } else {
                    DB::table('actual_loading_log')->insert([
                        'prod_code' => $prdNumber, 'revision' => $newRevision, 'ref_type' => 'det',
                        'article_code' => $articleCode, 'field_name' => 'Article Added',
                        'old_value' => null, 'new_value' => "Fresh: $qtyFresh, Repaint: $qtyRepaint",
                        'created_by' => $username, 'created_at' => $now,
                    ]);
                    $changeCount++;
                }

                if ($qtyTotal <= 0) continue;

                DB::table('actual_loading_det')->insert([
                    'prod_code' => $prdNumber, 'urutan' => $urutan, 'article_code' => $articleCode,
                    'uom' => $val->uom ?? optional($old)->uom,
                    'qty' => $qtyTotal, 'qty_fresh' => $qtyFresh, 'qty_repaint' => $qtyRepaint,
                    'note' => $newNote,
                    'created_by' => optional($old)->created_by ?? $username, 'updated_by' => $username,
                    'created_at' => optional($old)->created_at ?? $now, 'updated_at' => $now,
                ]);

                $lines[] = ['article' => $articleCode, 'qtyFresh' => $qtyFresh, 'qtyRepaint' => $qtyRepaint];
            }

            // ── artikel yang dihapus saat edit ──
            foreach ($oldDetails as $articleCode => $old) {
                if (!in_array($articleCode, $seenArticles)) {
                    DB::table('actual_loading_log')->insert([
                        'prod_code' => $prdNumber, 'revision' => $newRevision, 'ref_type' => 'det',
                        'article_code' => $articleCode, 'field_name' => 'Article Removed',
                        'old_value' => "Fresh: {$old->qty_fresh}, Repaint: {$old->qty_repaint}", 'new_value' => null,
                        'created_by' => $username, 'created_at' => $now,
                    ]);
                    $changeCount++;
                }
            }

            // ── 4. Repost movement in-place (posisi kronologis dipertahankan) ──
            $warnings = $this->repostLoadingKeepPosition($prdNumber, $sprayBooth, $lines, $username);

            // Backdate/majukan loading_date TETAP DIBOLEHKAN. Kalau menyeberangi
            // anchor OPENING BALANCE aktif di lokasi manapun yang tersentuh
            // dokumen ini (biasanya 047), OB itu ikut menyerap/melepas — sama
            // seperti Delivery — supaya ledger checker tetap sinkron dengan
            // warehouse_stock tanpa OB perlu direvisi manual.
            $oldLoadingDb = substr((string) $oldHeader->loading_date, 0, 10);
            if ($effectiveLoadingDb !== $oldLoadingDb) {
                $adj = app(\App\Http\Controllers\StockAdjustmentController::class);
                $movRows = DB::table('warehouse_movement')
                    ->where('movement_transnno', $prdNumber)
                    ->get(['artikel_code', 'location_number', 'movement_plus', 'movement_min']);

                foreach ($movRows as $mv) {
                    $signed = (float) $mv->movement_plus - (float) $mv->movement_min;
                    if (abs($signed) < 0.000001) continue;

                    $wasCovered = $adj->obBoundaryFor($mv->artikel_code, $mv->location_number, $oldLoadingDb);
                    $isCovered  = $adj->obBoundaryFor($mv->artikel_code, $mv->location_number, $effectiveLoadingDb);
                    if ($wasCovered === $isCovered) continue;

                    $delta = $isCovered ? $signed : -$signed;
                    $adj->absorbIntoLatestOpeningBalance(
                        $mv->artikel_code, $mv->location_number, $delta, $username,
                        "Actual Loading {$prdNumber} tanggal diubah {$oldHeader->loading_date} -> {$effectiveLoadingDb}"
                    );
                }
            }
          } // end if ($detBerubah)

            DB::table('actual_loading_hdr')->where('prod_code', $prdNumber)->update([
                'loading_date'  => $loadingDateDb,
                'spray_booth'   => $sprayBooth,
                'wos_reference' => $reference,
                'note'          => $note,
                'num_revision'  => $changeCount > 0 ? $newRevision : $oldHeader->num_revision,
                'updated_by'    => $username,
                'updated_at'    => $now,
            ]);

            DB::commit();

            $title   = "Update $this->title";
            $message = $changeCount > 0
                ? "$title $prdNumber berhasil disimpan (Revisi $newRevision, $changeCount perubahan, stok disesuaikan)"
                : "$title $prdNumber tidak ada perubahan";
            if (!empty($warnings)) {
                $message .= "\n⚠ Stok RM booth jadi minus (edit tetap diproses):\n- " . implode("\n- ", $warnings);
            }
            \LogActivity::addToLog($title, "username: $username Status $message");

            return response()->json(['status'=>1,'title'=>$title,'message'=>$message,'alert'=>(!empty($warnings)?'warning':'success'),'warnings'=>$warnings,'prdNumber'=>$prdNumber,'oEdit'=>true]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status'=>0,'title'=>"Update $this->title",'message'=>[[$e->getMessage()]],'alert'=>'error']);
        }
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    /**
     * Hapus permanen dokumen berstatus NEW.
     * Stok tetap dikembalikan dulu supaya tidak ada saldo menggantung.
     */
    public function destroy(Request $request)
    {
        $username = Auth::user()->username;
        $id       = Crypt::decryptString($request->id);
        $title    = "Delete $this->title";

        DB::beginTransaction();
        try {
            $hdr = DB::table('actual_loading_hdr')
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            if (!$hdr) {
                throw new \Exception("Dokumen tidak ditemukan.");
            }
            if ((int) $hdr->status !== 1) {
                throw new \Exception("Hanya dokumen berstatus NEW yang bisa dihapus. Gunakan Cancel untuk dokumen lain.");
            }

            $prdNumber = $hdr->prod_code;

            // balikin stok + hapus movement + recalc
            $this->unPosting($prdNumber, $username, true);

            DB::table('actual_loading_det')->where('prod_code', $prdNumber)->delete();
            DB::table('actual_loading_log')->where('prod_code', $prdNumber)->delete();
            DB::table('actual_loading_hdr')->where('id', $id)->delete();

            DB::commit();

            $message = "$title $prdNumber Successfully Deleted";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['title'=>$title, 'alert'=>'success', 'message'=>$message]);

        } catch (\Throwable $e) {
            DB::rollBack();
            \LogActivity::addToLog($title, substr("username: $username Status GAGAL: ".$e->getMessage(), 0, 250));
            return redirect()->back()->with(['title'=>$title, 'alert'=>'warning', 'message'=>$e->getMessage()]);
        }
    }

    // =========================================================================
    // LIST
    // =========================================================================

    public function list(Request $request)
{
    $searchPrd    = strtolower($request->searchPrd);
    $prdDate      = $request->prdDate;
    $wosDate      = $request->wosDate;
    $searchStatus = $request->searchStatus;

    $fromDate = "";
    $toDate   = "";
    if ($prdDate) {
        $date = explode("to", $prdDate);
        if (count($date) > 1) {
            $fromDate = implode("-", array_reverse(explode("-", trim($date[0]))));
            $toDate   = implode("-", array_reverse(explode("-", trim($date[1]))));
        } else {
            $fromDate = implode("-", array_reverse(explode("-", trim($date[0]))));
            $toDate   = $fromDate;
        }
    }

    // ── parsing WOS Date range, hasil akhir format YYYY-MM-DD utk dibandingkan via TO_DATE() ──
    $fromWos = "";
    $toWos   = "";
    if ($wosDate) {
        $wd = explode("to", $wosDate);
        if (count($wd) > 1) {
            $fromWos = implode("-", array_reverse(explode("-", trim($wd[0]))));
            $toWos   = implode("-", array_reverse(explode("-", trim($wd[1]))));
        } else {
            $fromWos = implode("-", array_reverse(explode("-", trim($wd[0]))));
            $toWos   = $fromWos;
        }
    }

    $data = DB::table('actual_loading_hdr')
        ->leftJoin('stock_location_master', 'stock_location_master.location_code', '=', 'actual_loading_hdr.spray_booth')
        ->when($searchPrd, function ($query) use ($searchPrd) {
            $query->where(function ($q) use ($searchPrd) {
                $q->where('actual_loading_hdr.prod_code', 'ilike', '%'.$searchPrd.'%')
                  ->orWhere('actual_loading_hdr.wos_reference', 'ilike', '%'.$searchPrd.'%');
            });
        })
        ->when($prdDate, function ($query) use ($fromDate, $toDate) {
            $query->whereBetween(DB::raw('actual_loading_hdr.loading_date'), [$fromDate, $toDate]);
        })
        ->when($wosDate, function ($query) use ($fromWos, $toWos) {
            $query->whereRaw(
                "TO_DATE(actual_loading_hdr.wos_reference, 'DD-MM-YYYY') BETWEEN ? AND ?",
                [$fromWos, $toWos]
            );
        })
        ->when($searchStatus, function ($query) use ($searchStatus) {
            $query->where('actual_loading_hdr.status', $searchStatus);
        })
        ->select(
            'actual_loading_hdr.id',
            'actual_loading_hdr.prod_code',
            'actual_loading_hdr.wos_reference',
            DB::raw("to_char(actual_loading_hdr.loading_date, 'DD-MM-YYYY') as prod_date"),
            DB::raw("coalesce(stock_location_master.location_name, actual_loading_hdr.spray_booth) as spraybooth"),
            'actual_loading_hdr.status',
            'actual_loading_hdr.num_revision',
            'actual_loading_hdr.note',
            'actual_loading_hdr.created_by',
            DB::raw("to_char(actual_loading_hdr.created_at, 'DD-MM-YYYY HH24:MI') as created_at")
        )
        ->orderBy('actual_loading_hdr.id', 'desc')
        ->get();


        return Datatables::of($data)
            ->addColumn('action', function ($data) {
                $buttons = '<div class="d-inline-flex">
                                <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                    <i data-feather="menu"></i>
                                </a>';
                $buttons .= '<div class="dropdown-menu dropdown-menu-right">';

                if (Auth::user()->can('actualLoading-edit') && in_array($data->status, ['1', '4'])) {
                    $buttons .= '<a href="'. route('production.actualLoading.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
                }

                $buttons .= '<a href="'. route('production.actualLoading.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
                                <i data-feather="printer"></i>
                                Print
                            </a>';

                if (Auth::user()->can('actualLoading-posting') && $data->status == '3') {
                    $buttons .= "<a href='javascript:;'
                        class='dropdown-item'
                        data-size='sm'
                        data-ajax-delete='true'
                        data-confirm='Are You Sure want to post This number?'
                        data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                        data-modal-id='".$data->id."'
                        data-url='". route('production.actualLoading.posting', ['id'=>Crypt::encryptString($data->id)]) ."'>
                        <i data-feather='check' class='feather-14-red'></i>
                        <span>". __('Posting') ."</span>
                        </a>";
                }

                $buttons .= '<a href="'. route('production.actualLoading.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                <i data-feather="list"></i>
                                Detail
                            </a>';

                // ── CANCEL: semua status kecuali yang sudah CANCELED ──
                if (Auth::user()->can('actualLoading-delete') && $data->status == '4') {
                    $buttons .= "<a href='javascript:;'
                        class='dropdown-item'
                        data-size='sm'
                        data-ajax-delete='true'
                        data-confirm='Cancel dokumen ini?|Stok akan dikembalikan ke posisi sebelum dokumen dibuat. Lanjutkan?'
                        data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                        data-modal-id='".$data->id."'
                        data-url='". route('production.actualLoading.cancel', ['id'=>Crypt::encryptString($data->id)]) ."'>
                        <i data-feather='x-circle' class='feather-14-red'></i>
                        <span>". __('Cancel') ."</span>
                        </a>";
                }

                if (Auth::user()->can('actualLoading-delete') && $data->status == '1') {
                    $buttons .= "<a href='javascript:;'
                        class='dropdown-item'
                        data-size='sm'
                        data-ajax-delete='true'
                        data-confirm='Are You Sure want to Delete?|This action can not be undone. Do you want to continue?'
                        data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                        data-modal-id='".$data->id."'
                        data-url='". route('production.actualLoading.destroy', ['id'=>Crypt::encryptString($data->id)]) ."'>
                        <i data-feather='trash-2' class='feather-14-red'></i>
                        <span>". __('Delete') ."</span>
                        </a>";
                }

                $buttons .= '</div></div>';
                return $buttons;
            })
            ->addColumn('status', function ($data) {
                $badges = ['badge-primary', 'badge-info', 'badge-success', 'badge-warning', 'badge-danger'];
                $status = ['ON PROCESS', 'VALIDATE', 'APPROVED', 'POSTED', 'CANCELED'];
                $idx = $data->status - 1;
                return "<div class='badge ".($badges[$idx] ?? 'badge-secondary')."'>".($status[$idx] ?? $data->status)."</div>";
            })
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    public function listDetail(Request $request)
{
    $searchPrd    = strtolower($request->searchPrd);
    $prdDate      = $request->prdDate;
    $wosDate      = $request->wosDate;
    $searchStatus = $request->searchStatus;

    $fromDate = "";
    $toDate   = "";
    if ($prdDate) {
        $date = explode("to", $prdDate);
        if (count($date) > 1) {
            $fromDate = implode("-", array_reverse(explode("-", trim($date[0]))));
            $toDate   = implode("-", array_reverse(explode("-", trim($date[1]))));
        } else {
            $fromDate = implode("-", array_reverse(explode("-", trim($date[0]))));
            $toDate   = $fromDate;
        }
    }

    $fromWos = "";
    $toWos   = "";
    if ($wosDate) {
        $wd = explode("to", $wosDate);
        if (count($wd) > 1) {
            $fromWos = implode("-", array_reverse(explode("-", trim($wd[0]))));
            $toWos   = implode("-", array_reverse(explode("-", trim($wd[1]))));
        } else {
            $fromWos = implode("-", array_reverse(explode("-", trim($wd[0]))));
            $toWos   = $fromWos;
        }
    }

    $data = DB::table('actual_loading_det as ald')
        ->leftJoin('actual_loading_hdr as alh', 'alh.prod_code', '=', 'ald.prod_code')
        ->leftJoin('stock_location_master as slm', 'slm.location_code', '=', 'alh.spray_booth')
        ->leftJoin('article as a', 'a.article_code', '=', 'ald.article_code')
        ->when($searchPrd, function ($query) use ($searchPrd) {
            $query->where(function ($q) use ($searchPrd) {
                $q->where('ald.prod_code', 'ilike', '%'.$searchPrd.'%')
                  ->orWhere('alh.wos_reference', 'ilike', '%'.$searchPrd.'%');
            });
        })
        ->when($prdDate, function ($query) use ($fromDate, $toDate) {
            $query->whereBetween(DB::raw('alh.loading_date'), [$fromDate, $toDate]);
        })
        ->when($wosDate, function ($query) use ($fromWos, $toWos) {
            $query->whereRaw(
                "TO_DATE(alh.wos_reference, 'DD-MM-YYYY') BETWEEN ? AND ?",
                [$fromWos, $toWos]
            );
        })
        ->when($searchStatus, function ($query) use ($searchStatus) {
            $query->where('alh.status', $searchStatus);
        })
        ->select(
            'ald.prod_code',
            'alh.wos_reference',
            DB::raw("to_char(alh.loading_date, 'DD-MM-YYYY') as prod_date"),
            DB::raw("coalesce(slm.location_name, alh.spray_booth) as spraybooth"),
            'a.article_alternative_code as article_code_fg',
            'a.article_desc as article_desc_fg',
            'ald.qty_fresh',
            'ald.qty_repaint',
            'alh.status',
            'ald.note'
        )
        ->orderBy('ald.prod_code')
        ->orderBy('ald.urutan')
        ->get();

    return Datatables::of($data)
        ->addColumn('status', function ($data) {
            $badges = ['badge-primary', 'badge-info', 'badge-success', 'badge-warning', 'badge-danger'];
            $status = ['ON PROCESS', 'VALIDATE', 'APPROVED', 'POSTED', 'CANCELED'];
            $idx = $data->status - 1;
            return "<div class='badge ".($badges[$idx] ?? 'badge-secondary')."'>".($status[$idx] ?? $data->status)."</div>";
        })
        ->rawColumns(['status'])
        ->make(true);
}

    /**
 * Daftar FG yang bisa diproduksi di Spray Booth tertentu.
 * max_fg = kapasitas RM fresh di booth + stok FG repaint di gudang WIP.
 * Dipakai bersama oleh: dropdown artikel (articleBySprayBooth),
 * export template, dan validasi import.
 */
private function eligibleArticlesForBooth($locationCode)
{
    $isBooth = DB::table('stock_location_master')
        ->where('location_code', $locationCode)
        ->where('location_type', 'booth')
        ->exists();

    if (!$isBooth) {
        return collect();
    }

    // Stok RM dilihat dari pool akuntansi (parent) kalau booth punya parent.
    $locationCode = $this->resolveStockLocation($locationCode);

   $fgList = DB::table('bom_hdr as bh')
    ->join('bom_rm as br', 'br.bom_code', '=', 'bh.bom_code')
    ->join('article as arm', 'arm.article_code', '=', 'br.article_code')
    ->join('article as afg', 'afg.article_code', '=', 'bh.article_code')
    ->where('bh.status', '3')
    ->whereIn('arm.article_type', ['RMP', 'RMNP'])
    ->select(
            'afg.article_code',
            'afg.article_alternative_code',
            'afg.article_desc',
            'afg.uom',
            DB::raw("(select string_agg(unit_to,',' order by unit_from) from uom_con_v2 where article_code = afg.article_code) as uom_member"),
            DB::raw("(
                select greatest(coalesce(min(floor(greatest(coalesce(ws.total_qty,0),0) / nullif(br2.qty,0))),0),0)
                from bom_rm br2
                join article arm2
                    on arm2.article_code = br2.article_code
                   and arm2.article_type in ('RMP','RMNP')
                left join (
                    select article_code, sum(article_qty) as total_qty
                    from warehouse_stock
                    where location_number = '$locationCode'
                    group by article_code
                ) ws on ws.article_code = br2.article_code
                where br2.bom_code = bh.bom_code
            ) as stock_rm_fresh"),
          DB::raw("(
    select coalesce(sum(greatest(ws.article_qty,0)),0)
    from warehouse_stock ws
    where ws.article_code = afg.article_code
      and ws.location_number = '012'
) as stock_fg_repaint")
        )
        ->distinct()
        ->orderBy('afg.article_alternative_code')
        ->get();

    return $fgList->map(function ($r) {
        $fresh   = max(0, (float) ($r->stock_rm_fresh   ?? 0));
        $repaint = max(0, (float) ($r->stock_fg_repaint ?? 0));
        $r->max_fg = $fresh + $repaint;
        return $r;
    })->values();
}

/** Stok RM (RMP/RMNP) yang ada di Spray Booth tertentu, qty > 0 */
private function rmStockAtBooth($locationCode)
{
    $locationCode = $this->resolveStockLocation($locationCode);

    return DB::table('warehouse_stock as ws')
        ->join('article as a', 'a.article_code', '=', 'ws.article_code')
        ->where('ws.location_number', $locationCode)
        ->whereIn('a.article_type', ['RMP', 'RMNP'])
        ->select(
            'a.article_code', 'a.article_alternative_code', 'a.article_desc', 'a.uom',
            DB::raw('sum(ws.article_qty) as qty')
        )
        ->groupBy('a.article_code', 'a.article_alternative_code', 'a.article_desc', 'a.uom')
        ->havingRaw('sum(ws.article_qty) > 0')
        ->orderBy('a.article_alternative_code')
        ->get();
}

private function fgStockInWip(array $articleCodes)
{
    if (empty($articleCodes)) {
        return collect();
    }

    return DB::table('warehouse_stock as ws')
        ->join('stock_location_master as slm', 'slm.location_code', '=', 'ws.location_number')
        ->join('article as a', 'a.article_code', '=', 'ws.article_code')
        ->whereIn('ws.article_code', $articleCodes)
        ->where('ws.location_number', '012')   // ⬅ langsung ke parent, bukan location_type='wip'
        ->select(
            'a.article_code', 'a.article_alternative_code', 'a.article_desc', 'a.uom',
            'slm.location_name',
            DB::raw('sum(ws.article_qty) as qty')
        )
        ->groupBy('a.article_code', 'a.article_alternative_code', 'a.article_desc', 'a.uom', 'slm.location_name')
        ->havingRaw('sum(ws.article_qty) > 0')
        ->orderBy('a.article_alternative_code')
        ->get();
}
    // =========================================================================
    // PRINT / APPROVE
    // =========================================================================

    public function print(Request $request)
    {
        $id = Crypt::decryptString($request->id);

        $data['header'] = DB::table('actual_loading_hdr as alh')
            ->leftJoin('stock_location_master as slm', 'slm.location_code', '=', 'alh.spray_booth')
            ->where('alh.id', $id)
            ->select(
                'alh.*',
                DB::raw("to_char(alh.loading_date, 'DD-MM-YYYY') as loading_date_fmt"),
                DB::raw("coalesce(slm.location_name, alh.spray_booth) as spray_booth_name")
            )
            ->first();

        if (!$data['header']) {
            abort(404);
        }

        $prdNumber = $data['header']->prod_code;

        $data['details'] = DB::table('actual_loading_det as ald')
            ->leftJoin('article as a', 'a.article_code', '=', 'ald.article_code')
            ->where('ald.prod_code', $prdNumber)
            ->select(
                'ald.*',
                'a.article_alternative_code',
                'a.article_desc'
            )
            ->orderBy('ald.urutan')
            ->get();

        $data['prdNumber'] = $prdNumber;
        $data['no']    = 0;
        $data['title'] = $prdNumber;

        view()->share($data);

        $pdf = PDF::loadView('production.actualLoading.print');
        return $pdf->stream("$prdNumber.pdf");
    }

    public function approve(Request $request)
    {
        $username  = Auth::user()->username;
        $prdNumber = $request->prdNumber;
        $title     = "Approve $this->title";

        DB::beginTransaction();
        try {
            $hdr = DB::table('actual_loading_hdr')
                ->where('prod_code', $prdNumber)
                ->lockForUpdate()
                ->first();

            if (!$hdr) {
                throw new \Exception("Dokumen $prdNumber tidak ditemukan.");
            }
            if ((int) $hdr->status === 5) {
                throw new \Exception("Dokumen $prdNumber sudah CANCELED, tidak bisa di-approve.");
            }

            $statusLevelApproval = Approval::approvalLevelPosition($this->moduleCode, $prdNumber, $username);
            $nextLevel = $statusLevelApproval[0]->next_level;
            $status    = $statusLevelApproval[0]->next_level == $statusLevelApproval[0]->max_level ? '3' : '2';

            // Dokumen dibuat lewat store() = LANGSUNG POSTED (status 4, stok & movement
            // sudah diterapkan). Approval di sini hanya sign-off administratif —
            // JANGAN mundurkan status ke 2/3 (bikin tombol Posting yang rusak muncul
            // dan dokumen terlihat "belum diposting" padahal stok sudah jalan).
            if ((int) $hdr->status === 4) {
                $status = '4';
            }

            $rowAffected = DB::table('actual_loading_hdr')
                ->where('prod_code', $prdNumber)
                ->where('status', '<>', 5)
                ->update([
                    'status'     => $status,
                    'updated_by' => $username,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            if (!$rowAffected) {
                throw new \Exception("Dokumen $prdNumber tidak bisa di-approve (mungkin sudah CANCELED).");
            }

            DB::table('approval_history')->insert([
                'module_code'    => $this->moduleCode,
                'module_number'  => $prdNumber,
                'username'       => $username,
                'approval_order' => $nextLevel,
                'approval_date'  => date('Y-m-d'),
                'status'         => 1,
                'created_by'     => $username,
                'updated_by'     => $username,
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);

            DB::commit();

            $message = "$title $prdNumber is successfully Approve-".$nextLevel;
            \LogActivity::addToLog($title, "username: $username Status $message");
            return response()->json(['statusWo'=>$status,'status'=>1,'title'=>$title,'message'=>$message,'alert'=>'success','prdNumber'=>$prdNumber]);

        } catch (\Throwable $e) {
            DB::rollBack();
            \LogActivity::addToLog($title, substr("username: $username Status GAGAL: ".$e->getMessage(), 0, 250));
            return response()->json(['status'=>0,'title'=>$title,'message'=>$e->getMessage(),'alert'=>'warning','prdNumber'=>$prdNumber]);
        }
    }

    // =========================================================================
    // EXPORT / IMPORT
    // =========================================================================

    // =========================================================================
// EXPORT / IMPORT  — skema Spray Booth (bukan WOS)
// =========================================================================

/**
 * Download template Excel untuk Actual Loading.
 * Kalau sprayBooth dikirim, sheet diisi daftar FG yang eligible di booth itu
 * (referensi Max FG) supaya user tinggal isi Qty Fresh / Qty Repaint.
 * Tanpa sprayBooth -> template kosong (cuma header).
 */
public function export(Request $request)
{
    $sprayBooth = $request->sprayBooth;
    $wosDate    = $request->wosDate;

    $boothName = $sprayBooth
        ? DB::table('stock_location_master')->where('location_code', $sprayBooth)->value('location_name')
        : null;

    $articles = $sprayBooth ? $this->eligibleArticlesForBooth($sprayBooth) : collect();
    $rmStock  = $sprayBooth ? $this->rmStockAtBooth($sprayBooth) : collect();
    $fgWip    = $sprayBooth ? $this->fgStockInWip($articles->pluck('article_code')->all()) : collect();

    $filename = $this->buildTemplateFilename($wosDate, $boothName);

    return Excel::download(
        new ActualLoadingExport($articles, $rmStock, $fgWip, $sprayBooth, $boothName),
        $filename
    );
}

/**
 * Bentuk nama file: Loading_WOSDATE_(NamaSprayBooth).xlsx
 * Karakter yang tidak valid untuk nama file (/ \ : * ? " < > |) dibuang/diganti.
 */
private function buildTemplateFilename($wosDate, $boothName)
{
    $datePart = $wosDate ? trim($wosDate) : date('d-m-Y');
    $datePart = str_replace(['/', '\\'], '-', $datePart);

    $boothPart = $boothName ? trim($boothName) : 'NoBooth';

    $filename = "WOS_{$boothPart}_{$datePart}";

    $filename = preg_replace('/[\\\\\/:*?"<>|]/', '', $filename);
    $filename = preg_replace('/\s+/', ' ', $filename);

    return trim($filename) . '.xlsx';
}

/**
 * Import Excel Actual Loading.
 * Wajib sprayBooth sudah dipilih di form (sama seperti Location From di Transfer Stock),
 * karena validasi article & Max FG bergantung pada booth yang dipilih.
 */
public function importExcel(Request $request)
{
    $sprayBooth = $request->sprayBooth;

    $this->validate($request, [
        'file' => 'required|mimes:xls,xlsx',
    ]);

    $title = "Import $this->title";

    if (!$sprayBooth) {
        return response()->json(['status'=>0,'title'=>$title,'message'=>[['Pilih Spray Booth terlebih dahulu sebelum import.']],'alert'=>'error']);
    }

    $sheets = Excel::toCollection(new ActualLoadingImport(), $request->file('file'));
    $rows   = $sheets->first() ?? collect();

    if ($rows->isEmpty()) {
        return response()->json(['status'=>0,'title'=>$title,'message'=>[['File kosong / tidak ada baris data.']],'alert'=>'error']);
    }

    $eligible = $this->eligibleArticlesForBooth($sprayBooth)->keyBy(function ($r) {
        return strtoupper(trim($r->article_alternative_code));
    });

    $dataDetail = [];
    $errors     = [];
    $baris      = 1; // baris 1 = header, data mulai baris 2

   foreach ($rows as $row) {
    $baris++;

    $codeInput = strtoupper(trim((string) ($row['article_code'] ?? '')));
    if ($codeInput === '') continue; // baris kosong dilewati

    $rawFresh   = trim((string) ($row['qty_fresh']   ?? '0'));
    $rawRepaint = trim((string) ($row['qty_repaint'] ?? '0'));

    if ($rawFresh !== '' && !preg_match('/^[0-9]*\.?[0-9]*$/', $rawFresh)) {
        $errors[] = "Baris $baris: Qty Fresh tidak valid ('$rawFresh')";
        continue;
    }
    if ($rawRepaint !== '' && !preg_match('/^[0-9]*\.?[0-9]*$/', $rawRepaint)) {
        $errors[] = "Baris $baris: Qty Repaint tidak valid ('$rawRepaint')";
        continue;
    }

    $qtyFresh   = (float) $rawFresh;
    $qtyRepaint = (float) $rawRepaint;

    // ⬇ TIDAK diisi user (qty 0/kosong) → lewati saja, BUKAN error
    if (($qtyFresh + $qtyRepaint) <= 0) {
        continue;
    }

       $article = $eligible->get($codeInput);

    // ── BYPASS: kalau tidak ada di daftar eligible (BOM/stok RM),
    //    coba cari langsung ke master artikel. Kalau ketemu, tetap
    //    diizinkan masuk (max_fg dianggap 0 sebagai info saja). ──
    if (!$article) {
        $master = DB::table('article')
            ->whereRaw('UPPER(article_alternative_code) = ?', [$codeInput])
            ->first();

        if ($master) {
            $article = (object) [
                'article_code'             => $master->article_code,
                'article_alternative_code' => $master->article_alternative_code,
                'article_desc'             => $master->article_desc,
                'uom'                      => $master->uom,
                'max_fg'                   => 0,
            ];
        }
    }

    if (!$article) {
        $errors[] = "Baris $baris: Article Code '$codeInput' tidak terdaftar di master artikel";
        continue;
    }

    $dataDetail[] = [
        'article_code'             => $article->article_code,
        'article_alternative_code' => $article->article_alternative_code,
        'article_desc'             => $article->article_desc,
        'uom'                      => $article->uom,
        'max_fg'                   => $article->max_fg,
        'qty_fresh'                => $qtyFresh,
        'qty_repaint'              => $qtyRepaint,
        'note'                     => $row['note'] ?? null,
    ];
}

    if (count($errors) > 0) {
        return response()->json(['status'=>0,'title'=>$title,'message'=>$errors,'alert'=>'error']);
    }
    if (count($dataDetail) === 0) {
        return response()->json(['status'=>0,'title'=>$title,'message'=>[['Tidak ada baris valid untuk diimport.']],'alert'=>'error']);
    }

    return response()->json([
        'status'     => 1,
        'title'      => $title,
        'message'    => "$title berhasil, " . count($dataDetail) . " baris siap ditambahkan",
        'alert'      => 'success',
        'dataDetail' => $dataDetail,
    ]);
}
}