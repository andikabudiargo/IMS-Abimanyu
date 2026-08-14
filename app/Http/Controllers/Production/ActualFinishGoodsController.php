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
use App\Exports\ActualFinishGoodsExport;
use App\Imports\ActualFinishGoodsImport;
use Maatwebsite\Excel\Facades\Excel;

class ActualFinishGoodsController extends Controller
{
    private $title;
    private $moduleCode;
    private $codeKey;

    private $whLoading, $whFg, $whFgOt, $whWip;
    private $loadingStatusDone;
    private $whSanding  = '039'; // Gudang Sanding (sumber OT)
    private $whBuffing  = '038'; // Gudang Buffing (sumber FG, tujuan WIP)

    /**
     * Status dokumen:
     *  1 = NEW
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

    public function __construct()
    {
        $this->title      = "Actual Finish Goods";
        $this->moduleCode = "PRDFG";
        $this->codeKey    = "AFG";   // key di master_code untuk generator nomor

        $this->whLoading = '047'; // sumber stok fisik (hasil actual loading)
        $this->whFg      = '007'; // gudang FG
        $this->whFgOt    = '008'; // gudang FG OT
        $this->whWip     = '012'; // gudang WIP

        $this->loadingStatusDone = 4; // status actual loading setelah FG diinput
    }

    // =========================================================================
    // KOLOM DATATABLE
    // =========================================================================

    public function getTableColoumn()
    {
        $kolom = [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=>false,'searchable'=>false],
            ['data'=>'fg_code','name'=>'fg_code','title'=>'FG Number'],
            ['data'=>'fg_date','name'=>'fg_date','title'=>'FG Date'],
            ['data'=>'spraybooth','name'=>'spraybooth','title'=>'From'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created At'],
        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnDetail()
    {
        $kolom = [
            ['data'=>'fg_code','name'=>'fg_code','title'=>'FG Number'],
            ['data'=>'fg_date','name'=>'fg_date','title'=>'FG Date'],
            ['data'=>'spraybooth','name'=>'spraybooth','title'=>'From'],
            ['data'=>'article_code_fg','name'=>'article_code_fg','title'=>'Article Code'],
            ['data'=>'article_desc_fg','name'=>'article_desc_fg','title'=>'Article Desc'],
            ['data'=>'qty_fg','name'=>'qty_fg','title'=>'Qty FG'],
            ['data'=>'qty_ot','name'=>'qty_ot','title'=>'Qty OT'],
            ['data'=>'qty_wip','name'=>'qty_wip','title'=>'Qty WIP'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created At'],
        ];
        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $data['title']       = $this->title;
        $data['subtitle']    = "$this->title";
        $data['kolom']       = $this->getTableColoumn();
        $data['kolomDetail'] = $this->getTableColoumnDetail();
        $data['status']      = $this->statusMap;

         $data['listLocation'] = DB::table('stock_location_master')
        ->whereIn('location_code', ['050','051','052'])
        ->orderBy('location_name')
        ->get();

        return view("production.actualFinishGoods.index", $data);
    }

    // =========================================================================
    // GENERATOR NOMOR
    // =========================================================================

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

        // Format: AFG-ASN-{bulan romawi}-{tahun}-{nomor}
        return "$key-ASN-$monthRoman-$year-$codeNumber";
    }

    private function toRomanMonth(int $month): string
    {
        $romans = [
            1 => 'I',   2 => 'II',  3 => 'III', 4 => 'IV',
            5 => 'V',   6 => 'VI',  7 => 'VII', 8 => 'VIII',
            9 => 'IX',  10 => 'X',  11 => 'XI', 12 => 'XII',
        ];
        return $romans[$month] ?? '';
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    public function create(Request $request)
{
    $data['title']    = "Input $this->title";
    $data['subtitle'] = "Input $this->title";

    // Actual Loading status NEW (1) & belum punya FG aktif
    $data['listLoading'] = DB::table('actual_loading_hdr as alh')
        ->leftJoin('stock_location_master as slm', 'slm.location_code', '=', 'alh.spray_booth')
        ->where('alh.status', 1)
        ->whereNotExists(function ($q) {
            $q->select(DB::raw(1))
              ->from('actual_finish_goods_hdr as afg')
              ->whereColumn('afg.loading_code', 'alh.prod_code')
              ->where('afg.status', '<>', 5);
        })
        ->orderBy('alh.prod_code', 'desc')
        ->select(
            'alh.prod_code',
            'alh.wos_reference',
            'alh.note',
            DB::raw("to_char(alh.loading_date, 'DD-MM-YYYY') as loading_date_fmt"),
            DB::raw("coalesce(slm.location_name, alh.spray_booth) as spray_booth_name")
        )
        ->get();

  $data['listArticleFg'] = DB::table('article')
    ->where('article_type', 'FG')
    ->orderBy('article_alternative_code')
    ->get(['article_code', 'article_alternative_code', 'article_desc', 'uom'])
    ->map(function($r) {
        return [
            'id'       => $r->article_code,
            'text'     => ($r->article_alternative_code ?: $r->article_code) . ' — ' . $r->article_desc,
            'alt_code' => $r->article_alternative_code ?: $r->article_code,
            'desc'     => $r->article_desc,
            'uom'      => $r->uom,
        ];
    })->values();

    $data['listLocation'] = DB::table('stock_location_master')
            ->whereIn('location_code', ['050','051','052'])   // BUFFING PLANT 1 & 2
            ->orderBy('location_name')
            ->get();

    $data['statusPrd'] = 'NEW';
    $data['oEdit']     = false;

    return view("production.actualFinishGoods.create", $data);
}

    /** Ambil artikel dari actual_loading_det utk prod_code terpilih */
    public function articleByLoading(Request $request)
    {
        $prodCode = $request->prod_code;

        $rows = DB::table('actual_loading_det as ald')
            ->leftJoin('article as a', 'a.article_code', '=', 'ald.article_code')
            ->where('ald.prod_code', $prodCode)
            ->select(
                'ald.article_code',
                'a.article_alternative_code',
                'a.article_desc',
                'ald.uom',
                'ald.qty as qty_loading'
            )
            ->orderBy('ald.urutan')
            ->get();

        return response()->json($rows);
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store(Request $request)
    {
        $username = Auth::user()->username;
        $articles = json_decode($request->articles);
        $fgDate   = $request->fgDate;
        $location = $request->location;   // spray_booth = lokasi header (mis. 050/051)
        $note     = $request->note;

        $validation = Validator::make($request->all(), [
            'fgDate'   => 'required',
            'location' => 'required',
        ]);
        if ($validation->fails()) {
            $errs = [];
            foreach ($validation->messages()->getMessages() as $m) { $errs[] = $m; }
            return response()->json(['status'=>0,'title'=>"Save $this->title",'message'=>$errs,'alert'=>'error']);
        }
        if (empty($articles)) {
            return response()->json(['status'=>0,'title'=>"Save $this->title",'message'=>[['Tidak ada artikel yang diinput.']],'alert'=>'error']);
        }

        $fgDateDb = $fgDate ? implode('-', array_reverse(explode('-', $fgDate))) : date('Y-m-d');
        $trDate   = $fgDate ?: date('d-m-Y');
        $now      = date('Y-m-d H:i:s');

        DB::beginTransaction();
        try {
            AppHelpers::resetCode($this->codeKey);
            $fgNumber = $this->getLastCode($this->codeKey);

            DB::table('actual_finish_goods_hdr')->insert([
                'fg_code'       => $fgNumber,
                'loading_code'  => null,
                'wos_reference' => null,
                'spray_booth'   => $location,
                'fg_date'       => $fgDateDb,
                'num_revision'  => 0,
                'status'        => 4,
                'note'          => $note,
                'created_by'    => $username,
                'updated_by'    => $username,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);

            // Kumpulkan baris transfer per (bucket, source_loc)
            // struktur: $lines[bucket][source_loc] = [ ['article_code','qty','uom','note'], ... ]
            $lines = [
                'FG'  => [$this->whLoading => [], $this->whWip => []],
                'OT'  => [$this->whLoading => [], $this->whWip => []],
                'WIP' => [$this->whLoading => []],
            ];

            $urutan = 0; $savedRows = 0;

            foreach ($articles as $val) {
                $ac    = $val->article_code;
                $qtyFg = (float)($val->qty_fg ?? 0);
                $qtyOt = (float)($val->qty_ot ?? 0);
                $need  = $qtyFg + $qtyOt;

                $acLabel = DB::table('article')
                    ->where('article_code', $ac)
                    ->selectRaw("coalesce(article_alternative_code, article_code) || ' — ' || coalesce(article_desc,'') as lbl")
                    ->value('lbl') ?? $ac;

                if ($qtyFg < 0 || $qtyOt < 0) throw new \Exception("Qty FG/OT untuk {$acLabel} tidak boleh negatif.");
                if ($need <= 0) continue;

              $uom = $val->uom ?? DB::table('article')->where('article_code', $ac)->value('uom');

                // stok sumber
                $s047 = (float) DB::table('warehouse_stock')
                    ->where('article_code', $ac)->where('location_number', $this->whLoading)->sum('article_qty');
                $s012 = (float) DB::table('warehouse_stock')
                    ->where('article_code', $ac)->where('location_number', $this->whWip)->sum('article_qty');

                if ($need > $s047 + $s012) {
                    throw new \Exception(
                        "Stok untuk {$acLabel} tidak cukup. Butuh {$need}, tersedia Hasil Loading={$s047} + WIP={$s012}."
                    );
                }

                // Ambil 047 dulu, lalu 012
                $from047 = min($need, $s047);
                $from012 = $need - $from047;

                // WIP sisa hanya jika 047 > need
                $wipQty = ($s047 > $need) ? ($s047 - $need) : 0.0;

                // ── Alokasi FG-first ke sumber ──
                // FG diambil dari 047 dulu, sisa FG dari 012. Lalu OT mengikuti sisa sumber.
                $fg047 = min($qtyFg, $from047);
                $fg012 = $qtyFg - $fg047;

                $sisa047ForOt = $from047 - $fg047;      // sisa kapasitas 047 setelah FG
                $ot047 = min($qtyOt, $sisa047ForOt);
                $ot012 = $qtyOt - $ot047;

                // Simpan detail AFG (qty_wip = sisa yang terdeteksi)
                $urutan++; $savedRows++;
                DB::table('actual_finish_goods_det')->insert([
                    'fg_code'      => $fgNumber,
                    'loading_code' => null,
                    'urutan'       => $urutan,
                    'article_code' => $ac,
                    'uom'          => $uom,
                    'qty_loading'  => $s047,        // snapshot stok loading saat input
                    'qty_wip'      => $wipQty,
                    'qty_fg'       => $qtyFg,
                    'qty_ot'       => $qtyOt,
                    'note'         => $val->note ?? null,
                    'created_by'   => $username,
                    'updated_by'   => $username,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);

                // Masukkan ke bucket transfer
                if ($fg047 > 0) $lines['FG'][$this->whLoading][] = ['article_code'=>$ac,'qty'=>$fg047,'uom'=>$uom,'note'=>$val->note ?? null];
                if ($fg012 > 0) $lines['FG'][$this->whWip][]     = ['article_code'=>$ac,'qty'=>$fg012,'uom'=>$uom,'note'=>$val->note ?? null];
                if ($ot047 > 0) $lines['OT'][$this->whLoading][] = ['article_code'=>$ac,'qty'=>$ot047,'uom'=>$uom,'note'=>$val->note ?? null];
                if ($ot012 > 0) $lines['OT'][$this->whWip][]     = ['article_code'=>$ac,'qty'=>$ot012,'uom'=>$uom,'note'=>$val->note ?? null];
                if ($wipQty > 0) $lines['WIP'][$this->whLoading][] = ['article_code'=>$ac,'qty'=>$wipQty,'uom'=>$uom,'note'=>$val->note ?? null];
            }

            if ($savedRows === 0) throw new \Exception("Tidak ada qty (FG/OT) yang diinput.");

            // ── Buat transfer per (bucket, source, tujuan) ──
            $trf = app(\App\Http\Controllers\TransferStockController::class);

            $locationName = DB::table('stock_location_master')
                ->where('location_code', $location)
                ->value('location_name') ?? $location;

            // definisi tujuan & atribut per bucket
            $bucketMeta = [
                'FG'  => ['to'=>$this->whFg,   'penerima'=>'Delivery', 'noteBase'=>$locationName],
                'OT'  => ['to'=>$this->whFgOt, 'penerima'=>'Delivery', 'noteBase'=>'SANDING'],
                'WIP' => ['to'=>$this->whWip,  'penerima'=>'Produksi', 'noteBase'=>'SISA LOADING KE WIP'],
            ];
            $srcName = [$this->whLoading => 'LOADING PROSES', $this->whWip => 'WIP'];

            $pivotRows = [];

            foreach ($lines as $bucket => $bySrc) {
                foreach ($bySrc as $srcLoc => $bag) {
                    if (empty($bag)) continue;

                    $meta   = $bucketMeta[$bucket];
                    $noteTr = $meta['noteBase'].' (dari '.($srcName[$srcLoc] ?? $srcLoc).')';

                    $res = $trf->createTransferProgrammatically([
                        'trDate'       => $trDate,
                        'locationFrom' => $srcLoc,
                        'locationTo'   => $meta['to'],
                        'note'         => $noteTr,
                        'penerima'     => $meta['penerima'],
                        'refNumber'    => $fgNumber,
                        'articles'     => $bag,
                    ], false);

                    if (!$res['success']) {
                        throw new \Exception("Gagal buat transfer ($noteTr): ".$res['message']);
                    }

                    $pivotRows[] = [
                        'fg_code'    => $fgNumber,
                        'tr_number'  => $res['trNumber'],
                        'bucket'     => $bucket,
                        'source_loc' => $srcLoc,
                        'created_at' => $now,
                    ];
                }
            }

            if ($pivotRows) {
                DB::table('afg_transfer')->insert($pivotRows);
            }

            DB::commit();

            $title   = "Save $this->title";
            $trList  = array_column($pivotRows, 'tr_number');
            $message = "$title $fgNumber is successfully saved".($trList ? ' (Transfer: '.implode(', ', $trList).')' : '');
            \LogActivity::addToLog($title, substr("username: $username Status $message", 0, 250));
            return response()->json(['status'=>1,'title'=>$title,'message'=>$message,'alert'=>'success','fgNumber'=>$fgNumber,'oEdit'=>true]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status'=>0,'title'=>"Save $this->title",'message'=>[[$e->getMessage()]],'alert'=>'error']);
        }
    }

    // =========================================================================
    // HELPER STOK & MOVEMENT
    // =========================================================================
    /** Satu baris KELUAR: kurangi warehouse_stock sumber + movement (min) */
    private function postOut(&$seq, $article, $qty, $fromLoc, $toLoc, $movementType, $transno, $desc, $username)
    {
        if ($qty <= 0) return;
        $now   = date('Y-m-d H:i:s');
        $adesc = DB::table('article')->where('article_code',$article)->value('article_desc');

        DB::table('warehouse_stock')
            ->where('article_code',$article)->where('location_number',$fromLoc)
            ->update([
                'article_qty' => DB::raw('coalesce(article_qty,0) - '.$qty),
                'updated_by'  => $username,
                'updated_at'  => $now,
            ]);

        $this->writeMovement($seq, $article, $adesc, $qty, $fromLoc, 'out',
                             $fromLoc, $toLoc, $movementType, $transno, $desc, $username);
    }

    /** Satu baris MASUK: tambah warehouse_stock tujuan + movement (plus) */
    private function postIn(&$seq, $article, $uom, $qty, $toLoc, $fromLoc, $movementType, $transno, $desc, $username)
    {
        if ($qty <= 0) return;
        $now = date('Y-m-d H:i:s');

        $art   = DB::table('article')->where('article_code',$article)->select('article_desc','article_type')->first();
        $adesc = $art->article_desc ?? null;
        $dept  = $art->article_type ?? null;

        $exists = DB::table('warehouse_stock')
            ->where('article_code',$article)->where('location_number',$toLoc)->exists();

        if ($exists) {
            DB::table('warehouse_stock')
                ->where('article_code',$article)->where('location_number',$toLoc)
                ->update([
                    'article_qty' => DB::raw('coalesce(article_qty,0) + '.$qty),
                    'updated_by'  => $username,
                    'updated_at'  => $now,
                ]);
        } else {
            DB::table('warehouse_stock')->insert([
                'site_code'       => 'HO',
                'article_code'    => $article,
                'location_number' => $toLoc,
                'article_qty'     => $qty,
                'uom'             => $uom,
                'dept_code'       => $dept,
                'created_by'      => $username,
                'updated_by'      => $username,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }

        $this->writeMovement($seq, $article, $adesc, $qty, $toLoc, 'in',
                             $fromLoc, $toLoc, $movementType, $transno, $desc, $username);
    }

    /** Tulis 1 baris warehouse_movement. $direction: 'in'|'out'. Naikkan $seq. */
    private function writeMovement(&$seq, $article, $adesc, $qty, $location, $direction,
                                   $fromLoc, $toLoc, $movementType, $transno, $desc, $username)
    {
        $sign  = ($direction === 'in') ? '+' : '-';
        $today = date('Y-m-d');

        DB::table('warehouse_movement')->insert([
            'movement_code'     => ++$seq,
            'movement_date'     => date('d-m-Y'),
            'site_code'         => 'HO',
            'location_number'   => $location,   // lokasi yg saldonya kena
            'artikel_code'      => $article,
            'artikel_desc'      => $adesc,
            'movement_min'      => ($direction === 'out') ? $qty : 0,
            'movement_plus'     => ($direction === 'in')  ? $qty : 0,
            'movement_from'     => $fromLoc,
            'movement_to'       => $toLoc,
            'movement_type'     => $movementType,
            'movement_transnno' => $transno,
            'movement_desc'     => $desc,
            'last_qty'          => DB::raw("get_last_qty_new('$article','$today','HO','$location') $sign $qty"),
            'created_by'        => $username,
            'created_at'        => date('Y-m-d H:i:s'),
        ]);
    }

    // =========================================================================
    // UN-POSTING (dipakai CANCEL, DESTROY, UPDATE)
    // =========================================================================

    /**
     * Kembalikan warehouse_stock ke posisi sebelum dokumen ini diposting.
     * TIDAK membuat movement baru.
     */
    private function unPosting($transno, $username, $hapusMovement = false, $checkStock = true)
    {
        $now = date('Y-m-d H:i:s');

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
                DB::table('warehouse_stock')
                    ->where('article_code', $art)
                    ->where('location_number', $loc)
                    ->update([
                        'article_qty' => DB::raw('coalesce(article_qty,0) - '.$net),
                        'updated_by'  => $username,
                        'updated_at'  => $now,
                    ]);
            } elseif ($net < 0) {
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
     * TO_DATE(movement_date,'dd-mm-yyyy') lalu movement_code.
     * warehouse_movement memakai movement_code sebagai PK, bukan id.
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

    // =========================================================================
    // SHOW / EDIT
    // =========================================================================

    public function show(Request $request)
    {
        $id       = Crypt::decryptString($request->id);
        $username = Auth::user()->username;

        $data['title']    = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";

        $data['header'] = DB::table('actual_finish_goods_hdr as afg')
            ->leftJoin('stock_location_master as slm', 'slm.location_code', '=', 'afg.spray_booth')
            ->where('afg.id', $id)
            ->select(
                'afg.*',
                DB::raw("to_char(afg.fg_date, 'DD-MM-YYYY') as fg_date_fmt"),
                DB::raw("coalesce(slm.location_name, afg.spray_booth) as spray_booth_name")
            )
            ->first();

        if (!$data['header']) {
            abort(404);
        }

        $fgNumber = $data['header']->fg_code;

        $data['details'] = DB::table('actual_finish_goods_det as afd')
            ->leftJoin('article as a', 'a.article_code', '=', 'afd.article_code')
            ->where('afd.fg_code', $fgNumber)
            ->select(
                'afd.*',
                'a.article_alternative_code',
                'a.article_desc'
            )
            ->orderBy('afd.urutan')
            ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode, $fgNumber, $username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode, $fgNumber, $username);

        $data['oEdit']     = true;
        $data['statusPrd'] = $this->statusMap[$data['header']->status] ?? $data['header']->status;

        return view("production.actualFinishGoods.show", $data);
    }

    public function edit(Request $request)
    {
        $id       = Crypt::decryptString($request->id);
        $username = Auth::user()->username;

        $data['title']    = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['header'] = DB::table('actual_finish_goods_hdr as afg')
            ->leftJoin('stock_location_master as slm', 'slm.location_code', '=', 'afg.spray_booth')
            ->where('afg.id', $id)
            ->select(
                'afg.*',
                DB::raw("to_char(afg.fg_date, 'DD-MM-YYYY') as fg_date_fmt"),
                DB::raw("coalesce(slm.location_name, afg.spray_booth) as spray_booth_name")
            )
            ->first();

        if (!$data['header']) {
            abort(404);
        }

        $fgNumber = $data['header']->fg_code;

        $data['details'] = DB::table('actual_finish_goods_det as afd')
            ->leftJoin('article as a', 'a.article_code', '=', 'afd.article_code')
            ->where('afd.fg_code', $fgNumber)
            ->select(
                'afd.*',
                'a.article_alternative_code',
                'a.article_desc'
            )
            ->orderBy('afd.urutan')
            ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode, $fgNumber, $username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode, $fgNumber, $username);

        $data['oEdit']     = true;
        $data['statusPrd'] = $this->statusMap[$data['header']->status] ?? $data['header']->status;

        return view("production.actualFinishGoods.edit", $data);
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    /**
     * Edit dokumen berstatus NEW: stok lama dibalikin, movement lama dihapus,
     * lalu diposting ulang dengan nomor dokumen yang sama.
     */
    public function update(Request $request)
    {
        $username = Auth::user()->username;
        $fgNumber = $request->fgNumber;
        $fgDate   = $request->fgDate;
        $note     = $request->note;
        $location = $request->location;
        $articles = json_decode($request->articles);
        $now      = date('Y-m-d H:i:s');

        $validation = Validator::make($request->all(), [
            'fgNumber' => 'required',
            'fgDate'   => 'required',
            'location' => 'required',
        ]);
        if ($validation->fails()) {
            $errs = [];
            foreach ($validation->messages()->getMessages() as $m) { $errs[] = $m; }
            return response()->json(['status'=>0,'title'=>"Update $this->title",'message'=>$errs,'alert'=>'error']);
        }
        if (empty($articles)) {
            return response()->json(['status'=>0,'title'=>"Update $this->title",'message'=>[['Tidak ada artikel yang diinput.']],'alert'=>'error']);
        }

        $fgDateDb = $fgDate ? implode('-', array_reverse(explode('-', $fgDate))) : null;
        $trDate   = $fgDate;

        DB::beginTransaction();
        try {
            $hdr = DB::table('actual_finish_goods_hdr')->where('fg_code', $fgNumber)->lockForUpdate()->first();
            if (!$hdr) throw new \Exception("Data $fgNumber tidak ditemukan.");
            if ((int)$hdr->status === 5) throw new \Exception("Dokumen $fgNumber sudah CANCELED dan tidak bisa diedit.");
            if ((int)$hdr->status !== 1) throw new \Exception("Hanya dokumen berstatus NEW yang bisa diedit.");

            // ── Ambil transfer anak, pastikan semua masih NEW ──
            $trList = DB::table('afg_transfer')->where('fg_code', $fgNumber)->pluck('tr_number');

            $notNew = DB::table('transfer_stock_hdr')
                ->whereIn('tr_number', $trList)
                ->where('status', '<>', '1')
                ->pluck('tr_number');
            if ($notNew->isNotEmpty()) {
                throw new \Exception("Tidak bisa edit: transfer ".$notNew->implode(', ')." sudah diproses. Lakukan Cancel AFG untuk mengoreksi.");
            }

            // ── Cancel semua transfer anak (reverse stok + movement) ──
            $trf = app(\App\Http\Controllers\TransferStockController::class);
            foreach ($trList as $trNo) {
                $res = $trf->cancelTransferProgrammatically($trNo, "Edit AFG $fgNumber", false, false);
                if (!$res['success']) throw new \Exception("Gagal reverse transfer $trNo: ".$res['message']);
            }

            // Bersihkan pivot & detail lama
            DB::table('afg_transfer')->where('fg_code', $fgNumber)->delete();
            DB::table('actual_finish_goods_det')->where('fg_code', $fgNumber)->delete();

            // ── Update header ──
            DB::table('actual_finish_goods_hdr')->where('fg_code', $fgNumber)->update([
                'fg_date'      => $fgDateDb,
                'spray_booth'  => $location,
                'note'         => $note,
                'num_revision' => $hdr->num_revision + 1,
                'updated_by'   => $username,
                'updated_at'   => $now,
            ]);

            // ── Bangun ulang bucket (SAMA PERSIS logika store) ──
            $lines = [
                'FG'  => [$this->whLoading => [], $this->whWip => []],
                'OT'  => [$this->whLoading => [], $this->whWip => []],
                'WIP' => [$this->whLoading => []],
            ];
            $urutan = 0; $savedRows = 0;

            foreach ($articles as $val) {
                $ac    = $val->article_code;
                $qtyFg = (float)($val->qty_fg ?? 0);
                $qtyOt = (float)($val->qty_ot ?? 0);
                $need  = $qtyFg + $qtyOt;

                $acLabel = DB::table('article')->where('article_code', $ac)
                    ->selectRaw("coalesce(article_alternative_code, article_code) || ' — ' || coalesce(article_desc,'') as lbl")
                    ->value('lbl') ?? $ac;

                if ($qtyFg < 0 || $qtyOt < 0) throw new \Exception("Qty FG/OT untuk {$acLabel} tidak boleh negatif.");
                if ($need <= 0) continue;

                $uom = $val->uom ?? DB::table('article')->where('article_code', $ac)->value('uom');

                $s047 = (float) DB::table('warehouse_stock')->where('article_code',$ac)->where('location_number',$this->whLoading)->sum('article_qty');
                $s012 = (float) DB::table('warehouse_stock')->where('article_code',$ac)->where('location_number',$this->whWip)->sum('article_qty');

                if ($need > $s047 + $s012) {
                    throw new \Exception("Stok untuk {$acLabel} tidak cukup. Butuh {$need}, tersedia Hasil Loading={$s047} + WIP={$s012}.");
                }

                $from047 = min($need, $s047);
                $wipQty  = ($s047 > $need) ? ($s047 - $need) : 0.0;

                $fg047 = min($qtyFg, $from047);
                $fg012 = $qtyFg - $fg047;
                $sisa047ForOt = $from047 - $fg047;
                $ot047 = min($qtyOt, $sisa047ForOt);
                $ot012 = $qtyOt - $ot047;

                $urutan++; $savedRows++;
                DB::table('actual_finish_goods_det')->insert([
                    'fg_code'=>$fgNumber,'loading_code'=>null,'urutan'=>$urutan,
                    'article_code'=>$ac,'uom'=>$uom,'qty_loading'=>$s047,'qty_wip'=>$wipQty,
                    'qty_fg'=>$qtyFg,'qty_ot'=>$qtyOt,'note'=>$val->note ?? null,
                    'created_by'=>$username,'updated_by'=>$username,'created_at'=>$now,'updated_at'=>$now,
                ]);

                if ($fg047 > 0) $lines['FG'][$this->whLoading][] = ['article_code'=>$ac,'qty'=>$fg047,'uom'=>$uom,'note'=>$val->note ?? null];
                if ($fg012 > 0) $lines['FG'][$this->whWip][]     = ['article_code'=>$ac,'qty'=>$fg012,'uom'=>$uom,'note'=>$val->note ?? null];
                if ($ot047 > 0) $lines['OT'][$this->whLoading][] = ['article_code'=>$ac,'qty'=>$ot047,'uom'=>$uom,'note'=>$val->note ?? null];
                if ($ot012 > 0) $lines['OT'][$this->whWip][]     = ['article_code'=>$ac,'qty'=>$ot012,'uom'=>$uom,'note'=>$val->note ?? null];
                if ($wipQty > 0) $lines['WIP'][$this->whLoading][] = ['article_code'=>$ac,'qty'=>$wipQty,'uom'=>$uom,'note'=>$val->note ?? null];
            }

            if ($savedRows === 0) throw new \Exception("Tidak ada qty (FG/OT) yang diinput.");

            $locationName = DB::table('stock_location_master')->where('location_code',$location)->value('location_name') ?? $location;
            $bucketMeta = [
                'FG'  => ['to'=>$this->whFg,   'penerima'=>'Delivery', 'noteBase'=>$locationName],
                'OT'  => ['to'=>$this->whFgOt, 'penerima'=>'Delivery', 'noteBase'=>'SANDING'],
                'WIP' => ['to'=>$this->whWip,  'penerima'=>'Produksi', 'noteBase'=>'SISA LOADING KE WIP'],
            ];
            $srcName = [$this->whLoading => 'LOADING PROSES', $this->whWip => 'WIP'];

            $pivotRows = [];
            foreach ($lines as $bucket => $bySrc) {
                foreach ($bySrc as $srcLoc => $bag) {
                    if (empty($bag)) continue;
                    $meta   = $bucketMeta[$bucket];
                    $noteTr = $meta['noteBase'].' (dari '.($srcName[$srcLoc] ?? $srcLoc).')';
                    $res = $trf->createTransferProgrammatically([
                        'trDate'=>$trDate,'locationFrom'=>$srcLoc,'locationTo'=>$meta['to'],
                        'note'=>$noteTr,'penerima'=>$meta['penerima'],'refNumber'=>$fgNumber,'articles'=>$bag,
                    ], false);
                    if (!$res['success']) throw new \Exception("Gagal buat transfer ($noteTr): ".$res['message']);
                    $pivotRows[] = ['fg_code'=>$fgNumber,'tr_number'=>$res['trNumber'],'bucket'=>$bucket,'source_loc'=>$srcLoc,'created_at'=>$now];
                }
            }
            if ($pivotRows) DB::table('afg_transfer')->insert($pivotRows);

            DB::commit();
            $title   = "Update $this->title";
            $message = "$title $fgNumber berhasil disimpan, stok disesuaikan";
            \LogActivity::addToLog($title, substr("username: $username Status $message", 0, 250));
            return response()->json(['status'=>1,'title'=>$title,'message'=>$message,'alert'=>'success','fgNumber'=>$fgNumber,'oEdit'=>true]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status'=>0,'title'=>"Update $this->title",'message'=>[[$e->getMessage()]],'alert'=>'error']);
        }
    }

    // =========================================================================
    // CANCEL
    // =========================================================================

    public function cancel(Request $request)
    {
        $username = Auth::user()->username;
        $id       = Crypt::decryptString($request->id);
        $now      = date('Y-m-d H:i:s');
        $title    = "Cancel $this->title";

        DB::beginTransaction();
        try {
            $hdr = DB::table('actual_finish_goods_hdr')->where('id', $id)->lockForUpdate()->first();
            if (!$hdr) throw new \Exception("Dokumen tidak ditemukan.");
            if ((int)$hdr->status === 5) throw new \Exception("Dokumen {$hdr->fg_code} sudah berstatus CANCELED.");

            $fgNumber = $hdr->fg_code;

            $trList = DB::table('afg_transfer')->where('fg_code', $fgNumber)->pluck('tr_number');
            $trf = app(\App\Http\Controllers\TransferStockController::class);
            foreach ($trList as $trNo) {
                $res = $trf->cancelTransferProgrammatically($trNo, "Cancel AFG $fgNumber", false, false);
                if (!$res['success']) throw new \Exception("Gagal cancel transfer $trNo: ".$res['message']);
            }

            DB::table('actual_finish_goods_hdr')->where('id', $id)
                ->update(['status'=>5,'updated_by'=>$username,'updated_at'=>$now]);

            DB::commit();
            $message = "$title $fgNumber berhasil dibatalkan, transfer stok dikembalikan";
            \LogActivity::addToLog($title, substr("username: $username Status $message", 0, 250));
            return redirect()->back()->with(['title'=>$title, 'alert'=>'success', 'message'=>$message]);
        } catch (\Throwable $e) {
            DB::rollBack();
            \LogActivity::addToLog($title, substr("username: $username Status GAGAL: ".$e->getMessage(), 0, 250));
            return redirect()->back()->with(['title'=>$title, 'alert'=>'warning', 'message'=>$e->getMessage()]);
        }
    }
    /**
     * Cancel Actual Finish Goods:
     *  - stok 007/008/012 dikembalikan ke 047
     *  - baris movement dokumen dihapus + last_qty di-recalc
     *  - status FG jadi 5 (CANCELED)
     *  - Actual Loading-nya dibuka lagi (status balik ke 1)
     */
    public function cancel1(Request $request)
    {
        $username = Auth::user()->username;
        $id       = Crypt::decryptString($request->id);
        $now      = date('Y-m-d H:i:s');
        $title    = "Cancel $this->title";

        DB::beginTransaction();
        try {
            $hdr = DB::table('actual_finish_goods_hdr')
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            if (!$hdr) {
                throw new \Exception("Dokumen tidak ditemukan.");
            }
            if ((int) $hdr->status === 5) {
                throw new \Exception("Dokumen {$hdr->fg_code} sudah berstatus CANCELED.");
            }

            $fgNumber    = $hdr->fg_code;
            $loadingCode = $hdr->loading_code;

            // balikin stok + hapus movement + recalc
            $this->unPosting($fgNumber, $username, true);

            DB::table('actual_finish_goods_hdr')
                ->where('id', $id)
                ->update([
                    'status'     => 5,
                    'updated_by' => $username,
                    'updated_at' => $now,
                ]);

            // buka kembali Actual Loading-nya
            if ($loadingCode) {
                DB::table('actual_loading_hdr')
                    ->where('prod_code', $loadingCode)
                    ->where('status', $this->loadingStatusDone)
                    ->update([
                        'status'     => 1,
                        'updated_by' => $username,
                        'updated_at' => $now,
                    ]);
            }

            DB::commit();

            $message = "$title $fgNumber berhasil dibatalkan, stok dikembalikan";
            \LogActivity::addToLog($title, substr("username: $username Status $message", 0, 250));
            return redirect()->back()->with(['title'=>$title, 'alert'=>'success', 'message'=>$message]);

        } catch (\Throwable $e) {
            DB::rollBack();
            \LogActivity::addToLog($title, substr("username: $username Status GAGAL: ".$e->getMessage(), 0, 250));
            return redirect()->back()->with(['title'=>$title, 'alert'=>'warning', 'message'=>$e->getMessage()]);
        }
    }

    public function destroy(Request $request)
    {
        $username = Auth::user()->username;
        $id       = Crypt::decryptString($request->id);
        $now      = date('Y-m-d H:i:s');
        $title    = "Delete $this->title";

        DB::beginTransaction();
        try {
            $hdr = DB::table('actual_finish_goods_hdr')->where('id', $id)->lockForUpdate()->first();
            if (!$hdr) throw new \Exception("Dokumen tidak ditemukan.");
            if ((int)$hdr->status !== 1) throw new \Exception("Hanya dokumen berstatus NEW yang bisa dihapus. Gunakan Cancel untuk dokumen lain.");

            $fgNumber = $hdr->fg_code;

            $trList = DB::table('afg_transfer')->where('fg_code', $fgNumber)->pluck('tr_number');
            $trf = app(\App\Http\Controllers\TransferStockController::class);
            foreach ($trList as $trNo) {
                $res = $trf->cancelTransferProgrammatically($trNo, "Delete AFG $fgNumber", false, false);
                if (!$res['success']) throw new \Exception("Gagal cancel transfer $trNo: ".$res['message']);
            }

            DB::table('afg_transfer')->where('fg_code', $fgNumber)->delete();
            DB::table('actual_finish_goods_det')->where('fg_code', $fgNumber)->delete();
            DB::table('actual_finish_goods_hdr')->where('id', $id)->delete();

            DB::commit();
            $message = "$title $fgNumber Successfully Deleted";
            \LogActivity::addToLog($title, substr("username: $username Status $message", 0, 250));
            return redirect()->back()->with(['title'=>$title, 'alert'=>'success', 'message'=>$message]);
        } catch (\Throwable $e) {
            DB::rollBack();
            \LogActivity::addToLog($title, substr("username: $username Status GAGAL: ".$e->getMessage(), 0, 250));
            return redirect()->back()->with(['title'=>$title, 'alert'=>'warning', 'message'=>$e->getMessage()]);
        }
    }
    // =========================================================================
    // DESTROY
    // =========================================================================

    /**
     * Hapus permanen dokumen berstatus NEW.
     * Stok dikembalikan dulu, Actual Loading-nya dibuka kembali.
     */
    public function destroy1(Request $request)
    {
        $username = Auth::user()->username;
        $id       = Crypt::decryptString($request->id);
        $now      = date('Y-m-d H:i:s');
        $title    = "Delete $this->title";

        DB::beginTransaction();
        try {
            $hdr = DB::table('actual_finish_goods_hdr')
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            if (!$hdr) {
                throw new \Exception("Dokumen tidak ditemukan.");
            }
            if ((int) $hdr->status !== 1) {
                throw new \Exception("Hanya dokumen berstatus NEW yang bisa dihapus. Gunakan Cancel untuk dokumen lain.");
            }

            $fgNumber    = $hdr->fg_code;
            $loadingCode = $hdr->loading_code;

            // $checkStock = false: hapus permanen, jangan diblok kalau stok sudah bergerak
            $this->unPosting($fgNumber, $username, true, false);

            DB::table('actual_finish_goods_det')->where('fg_code', $fgNumber)->delete();
            DB::table('actual_finish_goods_hdr')->where('id', $id)->delete();

            if ($loadingCode) {
                DB::table('actual_loading_hdr')
                    ->where('prod_code', $loadingCode)
                    ->where('status', $this->loadingStatusDone)
                    ->update([
                        'status'     => 1,
                        'updated_by' => $username,
                        'updated_at' => $now,
                    ]);
            }

            DB::commit();

            $message = "$title $fgNumber Successfully Deleted";
            \LogActivity::addToLog($title, substr("username: $username Status $message", 0, 250));
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
    $searchFg       = strtolower($request->searchPrd);
    $fgDate         = $request->prdDate;
    $searchLocation = $request->spraybooth;
    $searchStatus   = $request->searchStatus;

    $fromDate = "";
    $toDate   = "";

    if ($fgDate) {
        $date = explode("to", $fgDate);
        if (count($date) > 1) {
            $fromDate = implode("-", array_reverse(explode("-", trim($date[0]))));
            $toDate   = implode("-", array_reverse(explode("-", trim($date[1]))));
        } else {
            $fromDate = implode("-", array_reverse(explode("-", trim($date[0]))));
            $toDate   = $fromDate;
        }
    }

    $data = DB::table('actual_finish_goods_hdr as afg')
        ->leftJoin('stock_location_master as slm', 'slm.location_code', '=', 'afg.spray_booth')
        ->when($searchFg, function ($query) use ($searchFg) {
            $query->where('afg.fg_code', 'ilike', '%'.$searchFg.'%');
        })
        ->when($fgDate, function ($query) use ($fromDate, $toDate) {
            $query->whereBetween(DB::raw('afg.fg_date'), [$fromDate, $toDate]);
        })
        ->when($searchLocation, function ($query) use ($searchLocation) {
            $query->where('afg.spray_booth', $searchLocation);
        })
        ->when($searchStatus, function ($query) use ($searchStatus) {
            $query->where('afg.status', $searchStatus);
        })
        ->select(
            'afg.id',
            'afg.fg_code',
            DB::raw("to_char(afg.fg_date, 'DD-MM-YYYY') as fg_date"),
            DB::raw("coalesce(slm.location_name, afg.spray_booth) as spraybooth"),
            'afg.status',
            'afg.note',
            'afg.created_by',
            DB::raw("to_char(afg.created_at, 'DD-MM-YYYY HH24:MI') as created_at")
        )
        ->orderBy('afg.id', 'desc')
        ->get();

        return Datatables::of($data)
            ->addColumn('action', function ($data) {
                $buttons = '<div class="d-inline-flex">
                                <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                    <i data-feather="menu"></i>
                                </a>';
                $buttons .= '<div class="dropdown-menu dropdown-menu-right">';

                if (Auth::user()->can('actualFinishGoods-edit') && $data->status == '1') {
                    $buttons .= '<a href="'. route('production.actualFinishGoods.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
                }

                $buttons .= '<a href="'. route('production.actualFinishGoods.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
                                <i data-feather="printer"></i>
                                Print
                            </a>';

                $buttons .= '<a href="'. route('production.actualFinishGoods.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                <i data-feather="list"></i>
                                Detail
                            </a>';

                // ── CANCEL: semua status kecuali yang sudah CANCELED ──
                if (Auth::user()->can('actualFinishGoods-delete') && $data->status != '5') {
                    $buttons .= "<a href='javascript:;'
                        class='dropdown-item'
                        data-size='sm'
                        data-ajax-delete='true'
                        data-confirm='Cancel dokumen ini?|Stok akan dikembalikan ke gudang loading. Lanjutkan?'
                        data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                        data-modal-id='".$data->id."'
                        data-url='". route('production.actualFinishGoods.cancel', ['id'=>Crypt::encryptString($data->id)]) ."'>
                        <i data-feather='x-circle' class='feather-14-red'></i>
                        <span>". __('Cancel') ."</span>
                        </a>";
                }

                if (Auth::user()->can('actualFinishGoods-delete') && $data->status == '1') {
                    $buttons .= "<a href='javascript:;'
                        class='dropdown-item'
                        data-size='sm'
                        data-ajax-delete='true'
                        data-confirm='Are You Sure want to Delete?|This action can not be undone. Do you want to continue?'
                        data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                        data-modal-id='".$data->id."'
                        data-url='". route('production.actualFinishGoods.destroy', ['id'=>Crypt::encryptString($data->id)]) ."'>
                        <i data-feather='trash-2' class='feather-14-red'></i>
                        <span>". __('Delete') ."</span>
                        </a>";
                }

                $buttons .= '</div></div>';
                return $buttons;
            })
            ->addColumn('status', function ($data) {
                $badges = ['badge-primary', 'badge-info', 'badge-success', 'badge-warning', 'badge-danger'];
                $status = ['NEW', 'VALIDATE', 'APPROVED', 'POSTED', 'CANCELED'];
                $idx = $data->status - 1;
                return "<div class='badge ".($badges[$idx] ?? 'badge-secondary')."'>".($status[$idx] ?? $data->status)."</div>";
            })
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    public function listDetail(Request $request)
{
    $searchFg       = strtolower($request->searchPrd);
    $fgDate         = $request->prdDate;
    $searchLocation = $request->spraybooth;
    $searchStatus   = $request->searchStatus;

    $fromDate = "";
    $toDate   = "";

    if ($fgDate) {
        $date = explode("to", $fgDate);
        if (count($date) > 1) {
            $fromDate = implode("-", array_reverse(explode("-", trim($date[0]))));
            $toDate   = implode("-", array_reverse(explode("-", trim($date[1]))));
        } else {
            $fromDate = implode("-", array_reverse(explode("-", trim($date[0]))));
            $toDate   = $fromDate;
        }
    }

    $data = DB::table('actual_finish_goods_det as afd')
        ->leftJoin('actual_finish_goods_hdr as afg', 'afg.fg_code', '=', 'afd.fg_code')
        ->leftJoin('stock_location_master as slm', 'slm.location_code', '=', 'afg.spray_booth')
        ->leftJoin('article as a', 'a.article_code', '=', 'afd.article_code')
        ->when($searchFg, function ($query) use ($searchFg) {
            $query->where('afd.fg_code', 'ilike', '%'.$searchFg.'%');
        })
        ->when($fgDate, function ($query) use ($fromDate, $toDate) {
            $query->whereBetween(DB::raw('afg.fg_date'), [$fromDate, $toDate]);
        })
        ->when($searchLocation, function ($query) use ($searchLocation) {
            $query->where('afg.spray_booth', $searchLocation);
        })
        ->when($searchStatus, function ($query) use ($searchStatus) {
            $query->where('afg.status', $searchStatus);
        })
        ->select(
            'afd.fg_code',
            DB::raw("to_char(afg.fg_date, 'DD-MM-YYYY') as fg_date"),
            DB::raw("coalesce(slm.location_name, afg.spray_booth) as spraybooth"),
            'a.article_alternative_code as article_code_fg',
            'a.article_desc as article_desc_fg',
            'afd.qty_fg',
            'afd.qty_ot',
            'afd.qty_wip',
            'afg.status',
            'afd.note',
            'afg.created_by',
            DB::raw("to_char(afg.created_at, 'DD-MM-YYYY HH24:MI') as created_at")
        )
        ->orderBy('afd.fg_code')
        ->orderBy('afd.urutan')
        ->get();

    return Datatables::of($data)
        ->addColumn('status', function ($data) {
            $badges = ['badge-primary', 'badge-info', 'badge-success', 'badge-warning', 'badge-danger'];
            $status = ['NEW', 'VALIDATE', 'APPROVED', 'POSTED', 'CANCELED'];
            $idx = $data->status - 1;
            return "<div class='badge ".($badges[$idx] ?? 'badge-secondary')."'>".($status[$idx] ?? $data->status)."</div>";
        })
        ->rawColumns(['status'])
        ->make(true);
}

    // =========================================================================
    // PRINT / APPROVE
    // =========================================================================

    public function print(Request $request)
    {
        $id = Crypt::decryptString($request->id);

        $data['header'] = DB::table('actual_finish_goods_hdr as afg')
            ->leftJoin('stock_location_master as slm', 'slm.location_code', '=', 'afg.spray_booth')
            ->where('afg.id', $id)
            ->select(
                'afg.*',
                DB::raw("to_char(afg.fg_date, 'DD-MM-YYYY') as fg_date_fmt"),
                DB::raw("coalesce(slm.location_name, afg.spray_booth) as spray_booth_name")
            )
            ->first();

        if (!$data['header']) {
            abort(404);
        }

        $fgNumber = $data['header']->fg_code;

        $data['details'] = DB::table('actual_finish_goods_det as afd')
            ->leftJoin('article as a', 'a.article_code', '=', 'afd.article_code')
            ->where('afd.fg_code', $fgNumber)
            ->select(
                'afd.*',
                'a.article_alternative_code',
                'a.article_desc'
            )
            ->orderBy('afd.urutan')
            ->get();

        $data['fgNumber'] = $fgNumber;
        $data['no']       = 0;
        $data['title']    = $fgNumber;

        view()->share($data);

        $pdf = PDF::loadView('production.actualFinishGoods.print');
        return $pdf->stream("$fgNumber.pdf");
    }

    public function approve(Request $request)
    {
        $username = Auth::user()->username;
        $fgNumber = $request->fgNumber ?? $request->prdNumber;
        $title    = "Approve $this->title";

        DB::beginTransaction();
        try {
            $statusLevelApproval = Approval::approvalLevelPosition($this->moduleCode, $fgNumber, $username);
            $nextLevel = $statusLevelApproval[0]->next_level;
            $status    = $statusLevelApproval[0]->next_level == $statusLevelApproval[0]->max_level ? '3' : '2';

            $rowAffected = DB::table('actual_finish_goods_hdr')
                ->where('fg_code', $fgNumber)
                ->where('status', '<>', 5)
                ->update([
                    'status'     => $status,
                    'updated_by' => $username,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            if (!$rowAffected) {
                throw new \Exception("Dokumen $fgNumber tidak bisa di-approve (mungkin sudah CANCELED).");
            }

            DB::table('approval_history')->insert([
                'module_code'    => $this->moduleCode,
                'module_number'  => $fgNumber,
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

            $message = "$title $fgNumber is successfully Approve-".$nextLevel;
            \LogActivity::addToLog($title, substr("username: $username Status $message", 0, 250));
            return response()->json(['statusWo'=>$status,'status'=>1,'title'=>$title,'message'=>$message,'alert'=>'success','fgNumber'=>$fgNumber]);

        } catch (\Throwable $e) {
            DB::rollBack();
            \LogActivity::addToLog($title, substr("username: $username Status GAGAL: ".$e->getMessage(), 0, 250));
            return response()->json(['status'=>0,'title'=>$title,'message'=>$e->getMessage(),'alert'=>'warning','fgNumber'=>$fgNumber]);
        }
    }

    /**
 * Daftar Article FG yang tersedia untuk diinput (ada stok di 047 dan/atau 012).
 * Dipakai bersama: template export & validasi import.
 */
private function eligibleFgArticles()
{
    return DB::table('article as a')
        ->where('a.article_type', 'FG')
        ->select(
            'a.article_code',
            'a.article_alternative_code',
            'a.article_desc',
            'a.uom',
            DB::raw("coalesce((select sum(article_qty) from warehouse_stock where article_code=a.article_code and location_number='{$this->whLoading}'),0) as stock_loading"),
            DB::raw("coalesce((select sum(article_qty) from warehouse_stock where article_code=a.article_code and location_number='{$this->whWip}'),0) as stock_wip")
        )
        ->orderBy('a.article_alternative_code')
        ->get()
        ->map(function ($r) {
            $r->stock_loading = max(0, (float) $r->stock_loading);
            $r->stock_wip     = max(0, (float) $r->stock_wip);
            return $r;
        })
        ->filter(function ($r) {
            return ($r->stock_loading + $r->stock_wip) > 0;
        })
        ->values();
}

/**
 * Bentuk nama file: AFG_TANGGAL_(NamaLocation).xlsx
 */
private function buildFgTemplateFilename($fgDate, $locationName)
{
    $datePart = $fgDate ? trim($fgDate) : date('d-m-Y');
    $datePart = str_replace(['/', '\\'], '-', $datePart);

    $locPart = $locationName ? trim($locationName) : 'NoLocation';

    $filename = "AFG_{$datePart}_({$locPart})";
    $filename = preg_replace('/[\\\\\/:*?"<>|]/', '', $filename);
    $filename = preg_replace('/\s+/', ' ', $filename);

    return trim($filename) . '.xlsx';
}

    // =========================================================================
    // EXPORT
    // =========================================================================

    public function export(Request $request)
{
    $location = $request->location;
    $fgDate   = $request->fgDate;

    $locationName = $location
        ? DB::table('stock_location_master')->where('location_code', $location)->value('location_name')
        : null;

    $articles = $this->eligibleFgArticles();

    $filename = $this->buildFgTemplateFilename($fgDate, $locationName);

    return Excel::download(new ActualFinishGoodsExport($articles, $location, $locationName), $filename);
}

public function importExcel(Request $request)
{
    $this->validate($request, [
        'file' => 'required|mimes:xls,xlsx',
    ]);

    $title = "Import $this->title";

    $sheets = Excel::toCollection(new ActualFinishGoodsImport(), $request->file('file'));
    $rows   = $sheets->first() ?? collect();

    if ($rows->isEmpty()) {
        return response()->json(['status'=>0,'title'=>$title,'message'=>[['File kosong / tidak ada baris data.']],'alert'=>'error']);
    }

    $eligible = $this->eligibleFgArticles()->keyBy(function ($r) {
        return strtoupper(trim($r->article_alternative_code));
    });

    $dataDetail = [];
    $errors     = [];
    $baris      = 1;

    foreach ($rows as $row) {
        $baris++;

        $codeInput = strtoupper(trim((string) ($row['article_code'] ?? '')));
        if ($codeInput === '') continue;

        $rawFg = trim((string) ($row['qty_fg'] ?? '0'));
        $rawOt = trim((string) ($row['qty_ot'] ?? '0'));

        if ($rawFg !== '' && !preg_match('/^[0-9]*\.?[0-9]*$/', $rawFg)) {
            $errors[] = "Baris $baris: Qty FG tidak valid ('$rawFg')";
            continue;
        }
        if ($rawOt !== '' && !preg_match('/^[0-9]*\.?[0-9]*$/', $rawOt)) {
            $errors[] = "Baris $baris: Qty OT tidak valid ('$rawOt')";
            continue;
        }

        $qtyFg = (float) $rawFg;
        $qtyOt = (float) $rawOt;

        if (($qtyFg + $qtyOt) <= 0) continue;

        $article = $eligible->get($codeInput);
        if (!$article) {
            $errors[] = "Baris $baris: Article Code '$codeInput' tidak terdaftar / stok tidak tersedia";
            continue;
        }

        $dataDetail[] = [
            'article_code'             => $article->article_code,
            'article_alternative_code' => $article->article_alternative_code,
            'article_desc'             => $article->article_desc,
            'uom'                      => $article->uom,
            'qty_fg'                   => $qtyFg,
            'qty_ot'                   => $qtyOt,
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