<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use DataTables;
use DB;
use AppHelpers;
use Approval;

/**
 * STOCK CONSUMPTION
 * Menghilangkan (mengkonsumsi) stok di satu lokasi dan membebankannya ke COA biaya.
 *
 * Alur:
 *   NEW(1) --approve L1..L3--> APPROVED(3) --posting--> POSTED(4)
 * Saat POSTING: buat movement OUT, kurangi stok (recalc), insert jurnal ke kas_hdr.
 * Revisi: hanya status NEW, snapshot ke *_hist lalu delete-insert detail (tanpa reverse,
 * karena belum ada movement yg terbentuk sebelum posting — persis semangat Transfer Stock).
 */
class StockConsumptionController extends Controller
{
    private $title;
    private $moduleCode;
    private $siteCode = 'HO';

    // ── ASUMSI JURNAL — sesuaikan dgn skema kas kamu ────────────
    private $journalKey        = 'JV';        // key master_code utk nomor jurnal
    private $movementType      = 'CONSUMPTION';
    private $defaultInvCoa      = '1-1300';    // fallback akun persediaan (Cr) kalau lokasi tak punya mapping

    public function __construct()
    {
        $this->title      = "Stock Consumption";
        $this->moduleCode = "SCO";
    }

    // ============================================================
    // KOLOM DATATABLE
    // ============================================================
    public function getTableColoumn()
    {
        $kolom = [
            ['data'=>'action','name'=>'action','title'=>'Action','orderable'=>false,'searchable'=>false],
            ['data'=>'sc_number','name'=>'sc_number','title'=>'Number'],
            ['data'=>'sc_date','name'=>'sc_date','title'=>'Date'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'location_name','name'=>'location_name','title'=>'Location'],
            ['data'=>'coa_code','name'=>'coa_code','title'=>'COA'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created Date'],
        ];
        return json_encode($kolom, true);
    }

    // ============================================================
    // GENERATE NOMOR (sama pola dgn Transfer Stock)
    // ============================================================
    public function getLastCode($key, $scDate = null, $username = null)
    {
        $username = $username ?? optional(Auth::user())->username ?? 'system';
        $months   = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];

        if (empty($scDate)) {
            $refDate = now();
        } else {
            try {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $scDate)) {
                    $refDate = \Carbon\Carbon::createFromFormat('Y-m-d', $scDate);
                } elseif (preg_match('/^\d{2}-\d{2}-\d{4}$/', $scDate)) {
                    $refDate = \Carbon\Carbon::createFromFormat('d-m-Y', $scDate);
                } else {
                    $refDate = \Carbon\Carbon::parse($scDate);
                }
            } catch (\Exception $e) {
                $refDate = now();
            }
        }

        $year  = $refDate->year;
        $month = $refDate->month;
        $isCurrentPeriod = ($year === now()->year && $month === now()->month);

        if ($isCurrentPeriod) {
            $newCode = DB::selectOne("
                UPDATE master_code
                   SET code_number = code_number + 1, updated_by = ?, updated_at = now()
                 WHERE code_key = ?
             RETURNING code_number
            ", [$username, $key])->code_number;
        } else {
            $lockKey = sprintf('%s-backdate-%d-%d', $key, $year, $month);
            DB::select("SELECT pg_advisory_xact_lock(hashtext(?))", [$lockKey]);

            $prefixLike = sprintf('%s/%d/%s/%%', $key, $year, $months[$month - 1]);
            $maxSeq = (int) DB::selectOne("
                SELECT COALESCE(MAX( (split_part(sc_number, '/', 4))::int ), 0) AS mx
                  FROM stock_consumption_hdr
                 WHERE sc_number LIKE ?
            ", [$prefixLike])->mx;

            $newCode = $maxSeq + 1;
        }

        return sprintf('%s/%s/%s/%04d', $key, $year, $months[$month - 1], $newCode);
    }

    private function lockMovementSequence(): void
    {
        DB::select("SELECT pg_advisory_xact_lock(hashtext('warehouse_movement_code'))");
    }

    // ============================================================
    // INDEX
    // ============================================================
    public function index(Request $request)
    {
        $data['title']    = $this->title;
        $data['subtitle'] = $this->title;
        $data['kolom']    = $this->getTableColoumn();
        $data['status']   = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];
        $data['locations'] = DB::table('stock_location_master')->orderBy('location_name')->get();

        return view("stockConsumption.index", $data);
    }

    // ============================================================
    // CREATE
    // ============================================================
    public function create(Request $request)
    {
        $user       = Auth::user();
        $userDepts  = DB::table('user_dept')->where('username', $user->username)->pluck('dept')->toArray();
        $privileged = $user->hasAnyRole(['Superuser','accounting','finance']);

        $data['title']    = "Create $this->title";
        $data['subtitle'] = "Create $this->title";
        $data['oEdit']    = false;

        $data['locations'] = DB::table('stock_location_master')
            ->when(!$privileged, function ($q) use ($userDepts) {
                $q->where(function ($sub) use ($userDepts) {
                    $sub->whereIn('dept_code', $userDepts)->orWhere('location_code', '011');
                });
            })
            ->orderBy('location_name')
            ->get();

        // ── ASUMSI: tabel COA = chart_of_account (coa_code, coa_name), hanya akun postable ──
        $data['coas'] = DB::table('chart_of_account')
            ->orderBy('coa_code')
            ->select('coa_code', 'coa_name')
            ->get();

        return view("stockConsumption.create", $data);
    }

    // ============================================================
    // ARTICLE BY LOCATION — hanya artikel yang ADA stok di lokasi tsb
    // ============================================================
    public function articleByLocation(Request $request)
    {
        $location = $this->getStockLocation($request->location); // resolve pool jika child

        return DB::table('warehouse_stock as s')
            ->join('article as a', 'a.article_code', '=', 's.article_code')
            ->where('s.site_code', $this->siteCode)
            ->where('s.location_number', $location)
            ->where(DB::raw('coalesce(s.article_qty,0)'), '>', 0)
            ->select(
                'a.article_code',
                'a.article_alternative_code',
                'a.article_desc',
                'a.article_type',
                'a.uom',
                DB::raw('coalesce(s.article_qty,0) as qty'),
                DB::raw("(select string_agg(unit_to,',' order by unit_from) from uom_con_v2 where article_code = a.article_code) as uom_member")
            )
            ->orderBy('a.article_alternative_code')
            ->get();
    }

    // ============================================================
    // STORE — simpan sebagai NEW (belum ada gerak stok / jurnal)
    // ============================================================
    public function store(Request $request)
    {
        $username = Auth::user()->username;
        $title    = "Save $this->title";

        $scDate   = $request->scDate;
        $location = $request->location;
        $coa      = $request->coa;
        $note     = $request->note;
        $articles = json_decode($request->articles, true) ?? [];

        $errors = [];
        if (!$scDate)   $errors[] = "Date harus diisi";
        if (!$location) $errors[] = "Location harus dipilih";
        if (!$coa)      $errors[] = "COA harus dipilih";
        if (empty($articles)) $errors[] = "Artikel harus diisi";
        if ($errors) {
            return response()->json(['status'=>0,'title'=>$title,'message'=>$errors,'alert'=>'error']);
        }

        $deptCode = DB::table('stock_location_master')->where('location_code', $location)->value('dept_code');

        DB::beginTransaction();
        try {
            DB::select("SELECT pg_advisory_xact_lock(hashtext(?))", [$this->moduleCode]);
            AppHelpers::resetCode($this->moduleCode);
            $scNumber = $this->getLastCode($this->moduleCode, $scDate, $username);

            DB::table('stock_consumption_hdr')->insert([
                'sc_number'    => $scNumber,
                'sc_date'      => $scDate,
                'location_code'=> $location,
                'coa_code'     => $coa,
                'note'         => $note,
                'status'       => '1',
                'dept_code'    => $deptCode,
                'created_by'   => $username,
                'updated_by'   => $username,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);

            $this->insertDetails($scNumber, $articles, $username);

            DB::commit();
            \LogActivity::addToLog($title, "username: $username Status Save $scNumber is successfully saved");
            return response()->json([
                'status'=>1,'title'=>$title,
                'message'=>"$title $scNumber is successfully saved",
                'alert'=>'success','scNumber'=>$scNumber,'oEdit'=>true,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            \LogActivity::addToLog($title, "username: $username Save gagal - ".$e->getMessage());
            return response()->json(['status'=>0,'title'=>$title,'message'=>[$e->getMessage()],'alert'=>'error']);
        }
    }

    private function insertDetails(string $scNumber, array $articles, string $username): void
    {
        $rows = [];
        foreach ($articles as $val) {
            $ac  = is_array($val) ? ($val['article_code'] ?? null) : ($val->article_code ?? null);
            $qty = is_array($val) ? ($val['qty']  ?? 0)    : ($val->qty  ?? 0);
            $uom = is_array($val) ? ($val['uom']  ?? null) : ($val->uom  ?? null);
            $nt  = is_array($val) ? ($val['note'] ?? null) : ($val->note ?? null);
            if (!$ac || (float)$qty <= 0) continue;

            $rows[] = [
                'sc_number'    => $scNumber,
                'article_code' => $ac,
                'qty'          => $qty,
                'uom'          => $uom,
                'note'         => $nt,
                'created_by'   => $username,
                'updated_by'   => $username,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ];
        }
        if ($rows) DB::table('stock_consumption_det')->insert($rows);
    }

    // ============================================================
    // EDIT (hanya NEW) — snapshot history + delete-insert detail
    // ============================================================
    public function edit(Request $request)
    {
        return $this->showEdit($request->id, $request->editReason);
    }

    public function showEdit($key, $editReason = null)
    {
        $id       = Crypt::decryptString($key);
        $username = Auth::user()->username;
        $user     = Auth::user();
        $userDepts  = DB::table('user_dept')->where('username', $username)->pluck('dept')->toArray();
        $privileged = $user->hasAnyRole(['Superuser','accounting','finance']);

        $data['title']    = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";
        $data['oEdit']    = true;

        $data['header'] = DB::table('stock_consumption_hdr')->where('id', $id)->first();
        if (!$data['header']) {
            return redirect()->back()->with(['title'=>'Edit','alert'=>'warning','message'=>'Data tidak ditemukan']);
        }
        $scNumber = $data['header']->sc_number;

        $data['details'] = DB::table('stock_consumption_det')
            ->leftJoin('article','article.article_code','=','stock_consumption_det.article_code')
            ->where('stock_consumption_det.sc_number', $scNumber)
            ->select(
                'stock_consumption_det.*',
                'article.article_alternative_code',
                'article.article_desc',
                DB::raw("(select string_agg(unit_to,',' order by unit_from) from uom_con_v2 where article_code = stock_consumption_det.article_code) as uom_member")
            )
            ->orderBy('stock_consumption_det.id')
            ->get();

        $data['locations'] = DB::table('stock_location_master')
            ->when(!$privileged, function ($q) use ($userDepts) {
                $q->where(function ($sub) use ($userDepts) {
                    $sub->whereIn('dept_code', $userDepts)->orWhere('location_code', '011');
                });
            })
            ->orderBy('location_name')->get();

        $data['coas'] = DB::table('chart_of_account')->orderBy('coa_code')->select('coa_code','coa_name')->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode, $scNumber, $username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode, $scNumber, $username);

        $statusTr         = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED'];
        $data['statusTr'] = $statusTr[$data['header']->status - 1];
        $data['editReason'] = $editReason;

        return view("stockConsumption.edit", $data);
    }

    public function update(Request $request)
    {
        $username = Auth::user()->username;
        $title    = "Save $this->title";

        $scNumber   = $request->scNumber;
        $scDate     = $request->scDate;
        $location   = $request->location;
        $coa        = $request->coa;
        $note       = $request->note;
        $editReason = $request->editReason;
        $articles   = json_decode($request->articles, true) ?? [];

        $hdr = DB::table('stock_consumption_hdr')->where('sc_number', $scNumber)->first();
        if (!$hdr) {
            return response()->json(['status'=>0,'title'=>$title,'message'=>['Data tidak ditemukan'],'alert'=>'error']);
        }

        // ── GUARD: hanya NEW yang boleh diedit ──
        if ($hdr->status != '1') {
            $map = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];
            $st  = $map[$hdr->status] ?? $hdr->status;
            $extra = ($hdr->status == '4') ? ' Lakukan Cancel dulu untuk mengoreksi.' : '';
            return response()->json([
                'status'=>0,'title'=>$title,
                'message'=>["Dokumen berstatus $st, hanya NEW yang bisa diedit.$extra"],
                'alert'=>'error',
            ]);
        }

        $errors = [];
        if (!$scDate)   $errors[] = "Date harus diisi";
        if (!$location) $errors[] = "Location harus dipilih";
        if (!$coa)      $errors[] = "COA harus dipilih";
        if (empty($articles)) $errors[] = "Artikel harus diisi";
        if ($errors) {
            return response()->json(['status'=>0,'title'=>$title,'message'=>$errors,'alert'=>'error']);
        }

        $deptCode = DB::table('stock_location_master')->where('location_code', $location)->value('dept_code');

        DB::beginTransaction();
        try {
            // 1) snapshot kondisi lama ke *_hist
            $rev = $this->snapshotHistory($hdr, $username, $editReason);

            // 2) sinkron detail (hapus-insert) — belum ada movement, jadi tak perlu reverse
            DB::table('stock_consumption_det')->where('sc_number', $scNumber)->delete();
            $this->insertDetails($scNumber, $articles, $username);

            // 3) update header, status TETAP NEW
            DB::table('stock_consumption_hdr')->where('sc_number', $scNumber)->update([
                'sc_date'      => $scDate,
                'location_code'=> $location,
                'coa_code'     => $coa,
                'note'         => $note,
                'status'       => '1',
                'num_revision' => $rev,
                'dept_code'    => $deptCode,
                'updated_by'   => $username,
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);

            DB::commit();
            \LogActivity::addToLog($title, "username: $username Status $scNumber successfully updated (rev $rev)");
            return response()->json([
                'status'=>1,'title'=>$title,
                'message'=>"$title $scNumber is successfully updated",
                'alert'=>'success','scNumber'=>$scNumber,'oEdit'=>true,
                'redirect_url'=>route('stockConsumption.show', ['id'=>Crypt::encryptString($hdr->id)]),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            \LogActivity::addToLog($title, "username: $username Update gagal - ".$e->getMessage());
            return response()->json(['status'=>0,'title'=>$title,'message'=>[$e->getMessage()],'alert'=>'error']);
        }
    }

    private function snapshotHistory($hdr, string $username, ?string $reason = null): int
    {
        $rev = (int) ($hdr->num_revision ?? 0) + 1;

        DB::table('stock_consumption_hdr_hist')->insert([
            'sc_number'    => $hdr->sc_number,
            'num_revision' => $rev,
            'sc_date'      => $hdr->sc_date,
            'location_code'=> $hdr->location_code,
            'coa_code'     => $hdr->coa_code,
            'note'         => $hdr->note,
            'status'       => $hdr->status,
            'edit_reason'  => $reason ?? '-',
            'revised_by'   => $username,
            'revised_at'   => date('Y-m-d H:i:s'),
        ]);

        $details = DB::table('stock_consumption_det')->where('sc_number', $hdr->sc_number)->get();
        $rows = [];
        foreach ($details as $d) {
            $rows[] = [
                'sc_number'    => $hdr->sc_number,
                'num_revision' => $rev,
                'article_code' => $d->article_code,
                'qty'          => $d->qty,
                'uom'          => $d->uom,
                'note'         => $d->note,
            ];
        }
        if ($rows) DB::table('stock_consumption_det_hist')->insert($rows);

        return $rev;
    }

    // ============================================================
    // APPROVE — 3 level via Approval master
    // ============================================================
    public function approve(Request $request)
    {
        $username = Auth::user()->username;
        $scNumber = $request->scNumber;
        $title    = "Approve $this->title";

        $pos       = Approval::approvalLevelPosition($this->moduleCode, $scNumber, $username);
        $nextLevel = $pos[0]->next_level;
        $maxLevel  = $pos[0]->max_level;
        $newStatus = ($nextLevel == $maxLevel) ? '3' : '2';   // level terakhir => APPROVED

        DB::beginTransaction();
        try {
            DB::table('stock_consumption_hdr')->where('sc_number', $scNumber)->update([
                'status'     => $newStatus,
                'updated_by' => $username,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            DB::table('approval_history')->insert([
                'module_code'    => $this->moduleCode,
                'module_number'  => $scNumber,
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
            $message = "$title $scNumber successfully Approve-$nextLevel";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return response()->json(['status'=>1,'title'=>$title,'message'=>$message,'alert'=>'success','scNumber'=>$scNumber]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status'=>0,'title'=>$title,'message'=>[$e->getMessage()],'alert'=>'error']);
        }
    }

    // ============================================================
    // POSTING — hanya status APPROVED(3). Movement + kurangi stok + jurnal.
    // ============================================================
    public function posting(Request $request)
    {
        $user     = Auth::user();
        $username = $user->username;
        $id       = Crypt::decryptString($request->id);
        $title    = "Posting $this->title";

        $hdr = DB::table('stock_consumption_hdr')->where('id', $id)->first();
        if (!$hdr) {
            return redirect()->back()->with(['title'=>$title,'alert'=>'warning','message'=>'Data tidak ditemukan']);
        }
        if ($hdr->status == '4') {
            return redirect()->back()->with(['title'=>$title,'alert'=>'warning','message'=>"$title gagal: sudah diposting"]);
        }
        if ($hdr->status == '5') {
            return redirect()->back()->with(['title'=>$title,'alert'=>'warning','message'=>"$title gagal: sudah dicancel"]);
        }
        if ($hdr->status != '3') {
            return redirect()->back()->with(['title'=>$title,'alert'=>'warning','message'=>"$title gagal: dokumen belum APPROVED penuh"]);
        }
        if (!($user->hasAnyRole(['Superuser','accounting']) || $user->can('stockConsumption-posting'))) {
            return redirect()->back()->with(['title'=>$title,'alert'=>'warning','message'=>'Anda tidak berwenang posting']);
        }

        DB::beginTransaction();
        try {
            $result = $this->processPosting($hdr, $username);
            if (!$result['success']) {
                DB::rollBack();
                $msg = is_array($result['message']) ? implode(' | ', $result['message']) : $result['message'];
                return redirect()->back()->with(['title'=>$title,'alert'=>'warning','message'=>$msg]);
            }

            DB::table('stock_consumption_hdr')->where('id', $id)->update([
                'status'        => '4',
                'kas_number'    => $result['kas_number'],
                'total_amount'  => $result['total_amount'],
                'authorized_by' => $username,
                'authorized_at' => date('Y-m-d H:i:s'),
                'updated_by'    => $username,
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);

            DB::commit();
            $message = "$title {$hdr->sc_number} Successfully Posted";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['title'=>$title,'alert'=>'success','message'=>$message]);
        } catch (\Throwable $e) {
            DB::rollBack();
            \LogActivity::addToLog($title, "username: $username Posting gagal - ".$e->getMessage());
            return redirect()->back()->with(['title'=>$title,'alert'=>'warning','message'=>$e->getMessage()]);
        }
    }

    /**
     * Inti posting: movement OUT + recalc stok + jurnal.
     * @return array ['success','kas_number','total_amount','message']
     */
    private function processPosting(object $hdr, string $username): array
    {
        try {
            $lines = $this->resolveConsumptionLines($hdr);
        } catch (\RuntimeException $e) {
            return ['success'=>false,'message'=>[$e->getMessage()]];
        }

        $movementDate = $hdr->sc_date;                       // DD-MM-YYYY
        $stockLoc     = $this->getStockLocation($hdr->location_code); // lokasi akuntansi (pool)
        $physLoc      = $hdr->location_code;                  // lokasi fisik (audit)

        $this->lockMovementSequence();
        $seq = (int) DB::table('warehouse_movement')->max('movement_code');

        $movementRows = [];
        $totalAmount  = 0.0;

        foreach ($lines as $line) {
            $price   = $this->getAvgPrice($line['article_code'], $stockLoc);
            $amount  = $line['qty'] * $price;
            $totalAmount += $amount;
            $line['price']  = $price;
            $line['amount'] = $amount;

            $movementRows[] = $this->buildMovement(
                ++$seq, $hdr, $line, $stockLoc, $physLoc,
                $price, $this->movementDesc($hdr->note, $line), $username, $movementDate
            );
        }

        if (!empty($movementRows)) {
            DB::table('warehouse_movement')->insert($movementRows);
        }

        // recalc stok per artikel di lokasi pool
        foreach ($lines as $line) {
            $this->recalculateMovementAndStock($line['article_code'], $stockLoc, $hdr->sc_date);
        }

        // jurnal
        $kasNumber = $this->postJournal($hdr, $lines, $totalAmount, $username);

        return [
            'success'      => true,
            'kas_number'   => $kasNumber,
            'total_amount' => $totalAmount,
            'message'      => "Consumption {$hdr->sc_number} berhasil diposting",
        ];
    }

    /**
     * Detail -> daftar line (aggregate per artikel, qty sudah dikonversi ke base UOM).
     * line: ['article_code','article_desc','uom','qty','note']
     */
    private function resolveConsumptionLines(object $hdr): array
    {
        $details = DB::table('stock_consumption_det')
            ->leftJoin('article', 'article.article_code', '=', 'stock_consumption_det.article_code')
            ->where('stock_consumption_det.sc_number', $hdr->sc_number)
            ->select(
                'stock_consumption_det.*',
                'article.article_desc',
                'article.uom as article_uom'
            )
            ->get();

        if ($details->isEmpty()) {
            throw new \RuntimeException("Consumption {$hdr->sc_number} gagal: tidak ada detail");
        }

        $bag = [];
        foreach ($details as $d) {
            $qtyBase = $this->toBaseQty($d->article_code, (float)$d->qty, (string)$d->uom);
            if ($qtyBase <= 0) continue;

            $code = $d->article_code;
            if (!isset($bag[$code])) {
                $bag[$code] = [
                    'article_code' => $code,
                    'article_desc' => $d->article_desc ?? '',
                    'uom'          => $d->article_uom ?? $d->uom ?? 'PCS',
                    'qty'          => 0.0,
                    'notes'        => [],
                ];
            }
            $bag[$code]['qty'] += $qtyBase;
            if ($d->note) $bag[$code]['notes'][] = $d->note;
        }

        return array_values($bag);
    }

    private function buildMovement(
        int $seq, object $hdr, array $line, string $locationNumber, string $movementFrom,
        float $price, string $desc, string $username, string $movementDate
    ): array {
        $qty = $line['qty'];
        return [
            'movement_code'     => $seq,
            'movement_date'     => \Carbon\Carbon::createFromFormat('d-m-Y', $hdr->sc_date)->format('d-m-Y'),
            'artikel_code'      => $line['article_code'],
            'artikel_desc'      => $line['article_desc'],
            'movement_min'      => $qty,   // konsumsi = keluar
            'movement_plus'     => 0,
            'movement_price'    => $price,
            'movement_transnno' => $hdr->sc_number,
            'movement_type'     => $this->movementType,
            'movement_desc'     => $desc,
            'movement_from'     => $movementFrom,
            'movement_to'       => null,   // konsumsi tidak punya tujuan lokasi
            'partner_type'      => 'CONS',
            'created_by'        => $username,
            'created_at'        => date('Y-m-d H:i:s'),
            'site_code'         => $this->siteCode,
            'location_number'   => $locationNumber,
            'last_qty'          => DB::raw(
                "get_last_qty_new('{$line['article_code']}','$movementDate','{$this->siteCode}','$locationNumber') - $qty"
            ),
        ];
    }

    private function movementDesc(?string $baseNote, array $line): string
    {
        $desc = (string) ($baseNote ?? '');
        if (!empty($line['notes'])) {
            $desc .= ' [' . implode(', ', array_unique($line['notes'])) . ']';
        }
        return trim($desc);
    }

    // ============================================================
    // JURNAL ke kas_hdr / kas_det
    //   *** SKEMA DI BAWAH INI ASUMSI — sesuaikan dgn tabel kas kamu ***
    //   Dr : coa_code (biaya konsumsi)   sebesar total
    //   Cr : akun persediaan (inventory) sebesar total
    // ============================================================
    private function postJournal(object $hdr, array $lines, float $total, string $username): string
    {
        // nomor jurnal
        AppHelpers::resetCode($this->journalKey);
        $kasNumber = $this->getLastCode($this->journalKey, $hdr->sc_date, $username);

        // akun persediaan (Cr) — ASUMSI mapping di stock_location_master.coa_inventory
        $invCoa = DB::table('stock_location_master')
            ->where('location_code', $hdr->location_code)
            ->value('coa_inventory') ?: $this->defaultInvCoa;

        // konversi sc_date -> Y-m-d untuk kolom tanggal jurnal (sesuaikan tipe kolommu)
        $kasDate = \Carbon\Carbon::createFromFormat('d-m-Y', $hdr->sc_date)->format('Y-m-d');
        $desc    = "Stock Consumption {$hdr->sc_number}" . ($hdr->note ? " - {$hdr->note}" : '');

        DB::table('kas_hdr')->insert([
            'kas_number'  => $kasNumber,
            'kas_date'    => $kasDate,
            'kas_type'    => 'JV',          // journal voucher
            'ref_number'  => $hdr->sc_number,
            'description' => $desc,
            'total'       => $total,
            'created_by'  => $username,
            'updated_by'  => $username,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        DB::table('kas_det')->insert([
            [
                'kas_number'  => $kasNumber,
                'coa_code'    => $hdr->coa_code,  // Dr biaya
                'debit'       => $total,
                'credit'      => 0,
                'description' => $desc,
                'created_by'  => $username,
                'created_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'kas_number'  => $kasNumber,
                'coa_code'    => $invCoa,         // Cr persediaan
                'debit'       => 0,
                'credit'      => $total,
                'description' => $desc,
                'created_by'  => $username,
                'created_at'  => date('Y-m-d H:i:s'),
            ],
        ]);

        return $kasNumber;
    }

    private function reverseJournal(?string $kasNumber): void
    {
        if (!$kasNumber) return;
        // Pilihan: hard delete (di sini) ATAU buat reversing entry. Sesuaikan kebijakanmu.
        DB::table('kas_det')->where('kas_number', $kasNumber)->delete();
        DB::table('kas_hdr')->where('kas_number', $kasNumber)->delete();
    }

    // ============================================================
    // CANCEL / DELETE — reverse movement+jurnal jika sudah POSTED
    // ============================================================
    public function cancel(Request $request)
    {
        $user     = Auth::user();
        $username = $user->username;
        $id       = Crypt::decryptString($request->id);
        $title    = "Cancel $this->title";

        $hdr = DB::table('stock_consumption_hdr')->where('id', $id)->first();
        if (!$hdr) {
            return response()->json(['status'=>0,'title'=>$title,'message'=>['Data tidak ditemukan'],'alert'=>'error']);
        }
        if ($hdr->status == '5') {
            return response()->json(['status'=>0,'title'=>$title,'message'=>["$title gagal: sudah dicancel"],'alert'=>'warning']);
        }

        $isCreator = ($hdr->created_by === $username);
        if ($hdr->status == '4') {
            if (!($user->hasAnyRole(['Superuser','accounting']) || $user->can('stockConsumption-posting'))) {
                return response()->json(['status'=>0,'title'=>$title,'message'=>['Anda tidak berwenang cancel dokumen yang sudah diposting'],'alert'=>'warning']);
            }
        } else {
            if (!($isCreator || $user->hasAnyRole(['Superuser','accounting']))) {
                return response()->json(['status'=>0,'title'=>$title,'message'=>['Anda tidak berwenang cancel dokumen ini'],'alert'=>'warning']);
            }
        }

        DB::beginTransaction();
        try {
            // Hanya status POSTED yang punya efek stok/jurnal utk di-reverse.
            if ($hdr->status == '4') {
                $this->reverseStock($hdr);
                $this->reverseJournal($hdr->kas_number);
            }

            $reason  = "(Cancel by $username)";
            $newNote = trim(($hdr->note ?? '') . ';' . $reason);
            DB::table('stock_consumption_hdr')->where('id', $id)->update([
                'status'     => '5',
                'note'       => $newNote,
                'kas_number' => null,
                'updated_by' => $username,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            DB::commit();
            $message = "$title {$hdr->sc_number} Successfully Canceled";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return response()->json(['status'=>1,'title'=>$title,'message'=>$message,'alert'=>'success']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status'=>0,'title'=>$title,'message'=>[$e->getMessage()],'alert'=>'error']);
        }
    }

    private function reverseStock(object $hdr): void
    {
        $stockLoc = $this->getStockLocation($hdr->location_code);

        $articles = DB::table('stock_consumption_det')
            ->where('sc_number', $hdr->sc_number)
            ->pluck('article_code')->unique()->toArray();

        // hapus movement dokumen ini
        DB::table('warehouse_movement')
            ->where('movement_transnno', $hdr->sc_number)
            ->where('movement_type', $this->movementType)
            ->delete();

        // recalc stok tiap artikel di lokasi pool
        foreach ($articles as $ac) {
            $this->recalculateMovementAndStock($ac, $stockLoc, $hdr->sc_date);
        }
    }

    // ============================================================
    // SHOW
    // ============================================================
    public function show(Request $request)
    {
        $id       = Crypt::decryptString($request->id);
        $username = Auth::user()->username;

        $data['title']    = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";

        $data['header'] = DB::table('stock_consumption_hdr')
            ->leftJoin('stock_location_master as loc', 'loc.location_code', '=', 'stock_consumption_hdr.location_code')
            ->leftJoin('users as uCreate', 'uCreate.username', '=', 'stock_consumption_hdr.created_by')
            ->leftJoin('users as uAuth',   'uAuth.username',   '=', 'stock_consumption_hdr.authorized_by')
            ->where('stock_consumption_hdr.id', $id)
            ->select(
                'stock_consumption_hdr.*',
                'loc.location_name',
                'uCreate.name as created_name',
                'uAuth.name as authorized_name',
                DB::raw('(select count(*) from stock_consumption_det where sc_number = stock_consumption_hdr.sc_number) as sum_row'),
                DB::raw('(select sum(qty)  from stock_consumption_det where sc_number = stock_consumption_hdr.sc_number) as sum_qty')
            )
            ->first();

        if (!$data['header']) {
            return redirect()->back()->with(['title'=>'Detail','alert'=>'warning','message'=>'Data tidak ditemukan']);
        }
        $scNumber = $data['header']->sc_number;

        $data['details'] = DB::table('stock_consumption_det')
            ->leftJoin('article', 'article.article_code', '=', 'stock_consumption_det.article_code')
            ->where('stock_consumption_det.sc_number', $scNumber)
            ->select(
                'stock_consumption_det.*',
                'article.article_alternative_code',
                'article.article_desc'
            )
            ->orderBy('stock_consumption_det.id')
            ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode, $scNumber, $username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode, $scNumber, $username);

        $statusTr        = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED'];
        $data['statusTr'] = $statusTr[$data['header']->status - 1];

        return view("stockConsumption.show", $data);
    }

    // ============================================================
    // LIST (DataTables)
    // ============================================================
    public function list(Request $request)
    {
        $user       = Auth::user();
        $username   = $user->username;
        $userDepts  = DB::table('user_dept')->where('username', $username)->pluck('dept')->toArray();
        $privileged = $user->hasAnyRole(['Superuser','accounting','finance']);
        $canPost    = $user->hasAnyRole(['Superuser','accounting']) || $user->can('stockConsumption-posting');

        $searchNo     = strtolower((string) $request->searchNo);
        $searchStatus = $request->searchStatus;
        $searchLoc    = $request->searchLoc;
        $scDate       = $request->scDate;

        $fromDate = $toDate = "";
        if ($scDate) {
            $d = explode("to", $scDate);
            if (count($d) > 1) {
                $fromDate = implode("/", array_reverse(explode("-", trim($d[0]))));
                $toDate   = implode("/", array_reverse(explode("-", trim($d[1]))));
            } else {
                $fromDate = $toDate = implode("/", array_reverse(explode("-", trim($d[0]))));
            }
        }

        $query = DB::table('stock_consumption_hdr')
            ->leftJoin('stock_location_master as loc', 'loc.location_code', '=', 'stock_consumption_hdr.location_code')
            ->where(function ($q) use ($searchNo,$searchStatus,$searchLoc,$scDate,$fromDate,$toDate) {
                $searchNo     ? $q->where('stock_consumption_hdr.sc_number','ilike','%'.$searchNo.'%') : '';
                $searchStatus ? $q->where('stock_consumption_hdr.status',$searchStatus) : '';
                $searchLoc    ? $q->where('stock_consumption_hdr.location_code',$searchLoc) : '';
                $scDate       ? $q->whereBetween(DB::raw("to_date(stock_consumption_hdr.sc_date,'DD-MM-YYYY')"), [$fromDate,$toDate]) : '';
            });

        if (!$privileged) {
            $query->where(function ($q) use ($userDepts, $username) {
                $q->whereIn('loc.dept_code', $userDepts)
                  ->orWhere('stock_consumption_hdr.created_by', $username);
            });
        }

        $query->select(
            'stock_consumption_hdr.id',
            'stock_consumption_hdr.sc_number',
            'stock_consumption_hdr.sc_date',
            'stock_consumption_hdr.status',
            'stock_consumption_hdr.coa_code',
            'stock_consumption_hdr.note',
            'stock_consumption_hdr.created_by',
            'stock_consumption_hdr.created_at',
            'loc.location_name as location_name'
        )->orderBy('stock_consumption_hdr.created_at', 'desc');

        return DataTables::of($query)
            ->addColumn('action', function ($row) use ($canPost) {
                $encId = Crypt::encryptString($row->id);
                $st    = $row->status;

                $b  = '<div class="d-inline-flex"><a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown"><i data-feather="menu"></i></a>';
                $b .= '<div class="dropdown-menu dropdown-menu-right">';
                $b .= '<a href="'.route('stockConsumption.show',['id'=>$encId]).'" class="dropdown-item"><i data-feather="eye"></i><span>Detail</span></a>';

                if ($st == '1') {
                    $b .= '<a href="'.route('stockConsumption.edit',['id'=>$encId]).'" class="dropdown-item"><i data-feather="edit-2"></i><span>Edit</span></a>';
                }
                if ($st == '3' && $canPost) {
                    $b .= "
                        <form id='posting-form-{$row->id}' action='".route('stockConsumption.posting')."' method='POST' class='d-none'>
                            ".csrf_field()."<input type='hidden' name='id' value='{$encId}'>
                        </form>
                        <a href='javascript:;' class='dropdown-item'
                            onclick=\"if(confirm('Posting konsumsi ini? Stok akan berkurang dan jurnal dibuat.')){document.getElementById('posting-form-{$row->id}').submit();}\">
                            <i data-feather='check-circle' class='feather-14-green'></i><span class='text-success'>Posting</span></a>";
                }
                if ($st != '5') {
                    $b .= "<a href='javascript:;' class='dropdown-item' data-ajax-delete='true'
                                data-confirm='Batalkan konsumsi ini?|Stok & jurnal (jika sudah posting) akan dikembalikan.'
                                data-url='".route('stockConsumption.cancel',['id'=>$encId])."'>
                                <i data-feather='x-circle' class='feather-14-red'></i><span class='text-danger'>Cancel</span></a>";
                }
                $b .= '</div></div>';
                return $b;
            })
            ->editColumn('status', function ($row) {
                $badges   = ['badge-primary','badge-info','badge-warning','badge-success','badge-danger'];
                $statusTr = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED'];
                $i = $row->status - 1;
                return "<div class='badge {$badges[$i]}'>{$statusTr[$i]}</div>";
            })
            ->rawColumns(['action','status'])
            ->make(true);
    }

    // ============================================================
    // HELPERS (dari Transfer Stock)
    // ============================================================
    private array $stockLocationCache = [];

    private function getStockLocation(string $locationCode): string
    {
        if (array_key_exists($locationCode, $this->stockLocationCache)) {
            return $this->stockLocationCache[$locationCode];
        }
        $parent = DB::table('stock_location_master')->where('location_code', $locationCode)->value('parent_location');
        return $this->stockLocationCache[$locationCode] = ($parent ?: $locationCode);
    }

    private function getAvgPrice(string $articleCode, string $location): float
    {
        return (float) DB::table('warehouse_stock')
            ->where('site_code', $this->siteCode)
            ->where('article_code', $articleCode)
            ->where('location_number', $location)
            ->value('avg_price') ?? 0;
    }

    private function toBaseQty(string $articleCode, float $qty, string $uom): float
    {
        $result = DB::selectOne(
            "SELECT ? * COALESCE(uom_conversion(?, (SELECT uom FROM article WHERE article_code = ?)), 1) AS q",
            [$qty, $uom, $articleCode]
        );
        return (float) ($result->q ?? $qty);
    }

    private function updateWarehouseStock(string $articleCode, string $location, float $qty): void
    {
        DB::table('warehouse_stock')
            ->where('site_code', $this->siteCode)
            ->where('article_code', $articleCode)
            ->where('location_number', $location)
            ->update(['article_qty' => $qty]);
    }

    private function recalculateAvgPrice(string $articleCode, string $location): void
    {
        $movements = DB::table('warehouse_movement')
            ->where('artikel_code', $articleCode)
            ->where('location_number', $location)
            ->where('site_code', $this->siteCode)
            ->orderBy(DB::raw("TO_DATE(movement_date,'DD-MM-YYYY')"), 'asc')
            ->orderBy('movement_code', 'asc')
            ->select('movement_min', 'movement_plus', 'movement_price')
            ->get();

        $qty = 0.0; $avg = 0.0;
        foreach ($movements as $m) {
            $plus = (float) $m->movement_plus;
            $min  = (float) $m->movement_min;
            if ($plus > 0) {
                $price  = (float) $m->movement_price;
                $newQty = $qty + $plus;
                $avg    = $newQty > 0 ? (($qty * $avg) + ($plus * $price)) / $newQty : $avg;
                $qty    = $newQty;
            }
            if ($min > 0) { $qty -= $min; }
        }

        DB::table('warehouse_stock')
            ->where('site_code', $this->siteCode)
            ->where('article_code', $articleCode)
            ->where('location_number', $location)
            ->update(['avg_price' => $avg]);
    }

    private function recalculateMovementAndStock(string $articleCode, string $location, string $fromDate): void
    {
        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $fromDate)) {
            $fromDate = \Carbon\Carbon::createFromFormat('d-m-Y', $fromDate)->format('Y-m-d');
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) {
            // ok
        } else {
            $fromDate = \Carbon\Carbon::parse($fromDate)->format('Y-m-d');
        }

        $balanceBefore = (float) DB::selectOne(
            "SELECT get_last_qty_new(?, TO_CHAR(TO_DATE(?, 'YYYY-MM-DD') - INTERVAL '1 day', 'YYYY-MM-DD'), ?, ?) AS bal",
            [$articleCode, $fromDate, $this->siteCode, $location]
        )->bal;

        $movements = DB::table('warehouse_movement')
            ->where('artikel_code', $articleCode)
            ->where('location_number', $location)
            ->where('site_code', $this->siteCode)
            ->where(DB::raw("TO_DATE(movement_date, 'DD-MM-YYYY')"), '>=',
                DB::raw("TO_DATE('$fromDate', 'YYYY-MM-DD')"))
            ->whereNotIn('movement_type', ['RETURN-CANCEL', 'RETURN-REVERSE'])
            ->where('movement_type', 'NOT LIKE', 'CANCEL %')
            ->where('movement_type', 'NOT LIKE', 'DELETE%')
            ->where('movement_type', 'NOT LIKE', 'REVISI %')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('stock_adjustment_hdr')
                  ->whereColumn('stock_adjustment_hdr.adj_code', 'warehouse_movement.movement_transnno')
                  ->where('stock_adjustment_hdr.adj_type', 'OPENING BALANCE');
            })
            ->orderBy(DB::raw("TO_DATE(movement_date, 'DD-MM-YYYY')"), 'asc')
            ->orderBy('movement_code', 'asc')
            ->select('movement_code', 'movement_min', 'movement_plus')
            ->get();

        if ($movements->isEmpty()) {
            $this->updateWarehouseStock($articleCode, $location, $balanceBefore);
            $this->recalculateAvgPrice($articleCode, $location);
            return;
        }

        $running = $balanceBefore;
        foreach ($movements as $mov) {
            $running = $running - (float)$mov->movement_min + (float)$mov->movement_plus;
            DB::table('warehouse_movement')->where('movement_code', $mov->movement_code)->update(['last_qty' => $running]);
        }

        $latestLastQty = (float) DB::table('warehouse_movement')
            ->where('artikel_code', $articleCode)
            ->where('location_number', $location)
            ->where('site_code', $this->siteCode)
            ->orderBy(DB::raw("TO_DATE(movement_date, 'DD-MM-YYYY')"), 'desc')
            ->orderBy('movement_code', 'desc')
            ->value('last_qty');

        $this->updateWarehouseStock($articleCode, $location, $latestLastQty);
        $this->recalculateAvgPrice($articleCode, $location);
    }
}