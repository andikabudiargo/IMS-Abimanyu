<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use DataTables;
use DB;
use AppHelpers;

/**
 * STOCK CONSUMPTION
 *
 * Alur: DRAFT(1) → POSTED(4) | CANCELED(5)
 *
 * - CREATE  : siapapun yg punya akses.
 * - EDIT    : hanya status DRAFT, hanya pembuat / Superuser / accounting.
 * - POSTING : hanya Superuser / accounting. Akun jurnal (debit/credit)
 *             DITENTUKAN OTOMATIS berdasarkan article_type (CM1/CM2) —
 *             tidak ada input COA manual di form.
 *             Saat posting: movement OUT terbentuk, stok dikurangi, jurnal dibuat.
 *             Nomor voucher jurnal (kas_hdr.voucher_number) = sc_number
 *             (di-reuse langsung, tidak generate nomor baru).
 * - CANCEL  : DRAFT → siapa saja yg berwenang (pembuat / super / acc).
 *             POSTED → hanya Superuser / accounting; movement + jurnal di-reverse.
 * - REVISI  : snapshot ke *_hist, delete-insert detail. Tidak ada reverse movement
 *             karena movement hanya terbentuk saat posting.
 *
 * CATATAN REFACTOR (lihat histori diskusi):
 * - coa_code TIDAK LAGI diinput/dipilih user. Akun debit (beban) & credit
 *   (persediaan) ditentukan otomatis lewat resolveConsumptionCoa() /
 *   resolveInventoryCoa() berdasarkan article_type tiap baris.
 * - Nomor voucher jurnal = sc_number (Opsi A) — tidak generate nomor
 *   terpisah lagi, sehingga counter master_code tidak lagi rebutan antara
 *   pembuatan dokumen SC dan posting jurnal.
 * - last_qty pada movement dihitung di PHP (bukan raw SQL interpolation)
 *   agar semua parameter lewat query binding.
 */
class StockConsumptionController extends Controller
{
    private string $title      = "Stock Consumption";
    private string $moduleCode = "SCO";
    private string $siteCode   = 'HO';
    private string $movementType = 'CONSUMPTION';

    // ============================================================
    // KOLOM DATATABLE
    // ============================================================
    public function getTableColoumn(): string
    {
        return json_encode([
            ['data'=>'action',       'name'=>'action',       'title'=>'Action','orderable'=>false,'searchable'=>false],
            ['data'=>'sc_number',    'name'=>'sc_number',    'title'=>'Number'],
            ['data'=>'sc_date',      'name'=>'sc_date',      'title'=>'Date'],
            ['data'=>'status',       'name'=>'status',       'title'=>'Status','orderable'=>false,'searchable'=>false],
            ['data'=>'location_name','name'=>'location_name','title'=>'Location'],
            ['data'=>'note',         'name'=>'note',         'title'=>'Note'],
            ['data'=>'created_by',   'name'=>'created_by',   'title'=>'Created By'],
            ['data'=>'created_at',   'name'=>'created_at',   'title'=>'Created Date'],
            ['data'=>'authorized_by','name'=>'authorized_by','title'=>'Posted By','orderable'=>false,'searchable'=>false],
        ], true);
    }

    public function getTableColoumnDetail(): string
    {
        return json_encode([
            ['data'=>'sc_number',                'name'=>'sc_number',                'title'=>'Number'],
            ['data'=>'sc_date',                  'name'=>'sc_date',                  'title'=>'Date'],
            ['data'=>'article_alternative_code', 'name'=>'article_alternative_code', 'title'=>'Article Code'],
            ['data'=>'article_desc',             'name'=>'article_desc',             'title'=>'Article Desc'],
            ['data'=>'qty',                      'name'=>'qty',                      'title'=>'Qty'],
            ['data'=>'uom',                      'name'=>'uom',                      'title'=>'UOM'],
            ['data'=>'note',                     'name'=>'note',                     'title'=>'Note'],
            ['data'=>'status',                   'name'=>'status',                   'title'=>'Status'],
            ['data'=>'location_name',            'name'=>'location_name',            'title'=>'Location'],
            ['data'=>'created_by',               'name'=>'created_by',               'title'=>'Created By'],
            ['data'=>'created_at',               'name'=>'created_at',               'title'=>'Created Date'],
            ['data'=>'authorized_by',            'name'=>'authorized_by',            'title'=>'Posted By'],
        ], true);
    }

    // ============================================================
    // GENERATE NOMOR (dipakai hanya untuk sc_number — jurnal reuse sc_number)
    // ============================================================
    public function getLastCode(string $key, ?string $date = null, ?string $username = null): string
    {
        $username = $username ?? optional(Auth::user())->username ?? 'system';
        $months   = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];

        try {
            $ref = $date
                ? (preg_match('/^\d{2}-\d{2}-\d{4}$/', $date)
                    ? \Carbon\Carbon::createFromFormat('d-m-Y', $date)
                    : \Carbon\Carbon::parse($date))
                : now();
        } catch (\Exception $e) {
            $ref = now();
        }

        $year  = $ref->year;
        $month = $ref->month;

        if ($year === now()->year && $month === now()->month) {
            $newCode = DB::selectOne(
                "UPDATE master_code SET code_number=code_number+1,updated_by=?,updated_at=now() WHERE code_key=? RETURNING code_number",
                [$username, $key]
            )->code_number;
        } else {
            DB::select("SELECT pg_advisory_xact_lock(hashtext(?))", ["{$key}-backdate-{$year}-{$month}"]);
            $like   = sprintf('%s/%d/%s/%%', $key, $year, $months[$month - 1]);
            $maxSeq = (int) DB::selectOne(
                "SELECT COALESCE(MAX((split_part(sc_number,'/',4))::int),0) AS mx FROM stock_consumption_hdr WHERE sc_number LIKE ?",
                [$like]
            )->mx;
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
        $data['title']      = $this->title;
        $data['subtitle']   = $this->title;
        $data['kolom']      = $this->getTableColoumn();
        $data['kolomDetail']= $this->getTableColoumnDetail();
        $data['status']     = ['1'=>'DRAFT','4'=>'POSTED','5'=>'CANCELED'];
        $data['locations']  = DB::table('stock_location_master')->orderBy('location_name')->get();

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

        $data['title']    = "Create {$this->title}";
        $data['subtitle'] = "Create {$this->title}";
        $data['oEdit']    = false;

        $data['locations'] = DB::table('stock_location_master')
            ->when(!$privileged, fn($q) => $q->where(fn($s) =>
                $s->whereIn('dept_code', $userDepts)->orWhere('location_code','011')
            ))
            ->orderBy('location_name')->get();

        $data['currentDateValue'] = date('d-m-Y');

        return view("stockConsumption.create", $data);
    }

    // ============================================================
    // STORE — simpan sebagai DRAFT
    // ============================================================
    public function store(Request $request)
    {
        $username = Auth::user()->username;
        $title    = "Save {$this->title}";

        $scDate   = $request->scDate;
        $location = $request->location;
        $note     = $request->note;
        $articles = json_decode($request->articles, true) ?? [];

        $errors = [];
        if (!$scDate)         $errors[] = "Date harus diisi";
        if (!$location)       $errors[] = "Location harus dipilih";
        if (!$note)           $errors[] = "Notes harus diisi";
        if (empty($articles)) $errors[] = "Artikel harus diisi";
        if ($errors) {
            return response()->json(['status'=>0,'title'=>$title,'message'=>$errors,'alert'=>'error']);
        }

        $deptCode = DB::table('stock_location_master')->where('location_code',$location)->value('dept_code');

        DB::beginTransaction();
        try {
            DB::select("SELECT pg_advisory_xact_lock(hashtext(?))", [$this->moduleCode]);
            AppHelpers::resetCode($this->moduleCode);
            $scNumber = $this->getLastCode($this->moduleCode, $scDate, $username);

            DB::table('stock_consumption_hdr')->insert([
                'sc_number'    => $scNumber,
                'sc_date'      => $scDate,
                'location_code'=> $location,
                'note'         => $note,
                'status'       => '1',
                'dept_code'    => $deptCode,
                'created_by'   => $username,
                'updated_by'   => $username,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            $this->insertDetails($scNumber, $articles, $username);

            DB::commit();
            \LogActivity::addToLog($title, "username: $username Save $scNumber successfully saved");
            return response()->json([
                'status'   => 1, 'title' => $title,
                'message'  => "$title $scNumber is successfully saved",
                'alert'    => 'success', 'scNumber' => $scNumber, 'oEdit' => true,
                'redirect_url' => route('stockConsumption.show', ['id' => Crypt::encryptString($scNumber)]),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status'=>0,'title'=>$title,'message'=>[$e->getMessage()],'alert'=>'error']);
        }
    }

    // ── INSERT DETAIL helper ─────────────────────────────────────
    private function insertDetails(string $scNumber, array $articles, string $username): void
    {
        $rows = [];
        foreach ($articles as $val) {
            $ac  = is_array($val) ? ($val['article_code'] ?? null) : ($val->article_code ?? null);
            $qty = is_array($val) ? ($val['qty']  ?? 0)  : ($val->qty  ?? 0);
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
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
        }
        if ($rows) DB::table('stock_consumption_det')->insert($rows);
    }

    // ============================================================
    // EDIT (hanya DRAFT)
    // ============================================================
    public function edit(Request $request)
    {
        return $this->showEdit($request->id, $request->editReason);
    }

    public function showEdit($key, $editReason = null)
    {
        $scNumber   = Crypt::decryptString($key);
        $user       = Auth::user();
        $username   = $user->username;
        $privileged = $user->hasAnyRole(['Superuser','accounting','finance']);
        $userDepts  = DB::table('user_dept')->where('username',$username)->pluck('dept')->toArray();

        $data['title']    = "Edit {$this->title}";
        $data['subtitle'] = "Edit {$this->title}";
        $data['oEdit']    = true;

        $hdr = DB::table('stock_consumption_hdr')->where('sc_number', $scNumber)->first();
        if (!$hdr) {
            return redirect()->back()->with(['title'=>'Edit','alert'=>'warning','message'=>'Data tidak ditemukan']);
        }
        if ($hdr->status != '1') {
            $lbl = $this->statusLabel($hdr->status);
            return redirect()->back()->with(['title'=>'Edit','alert'=>'warning','message'=>"Dokumen berstatus $lbl, hanya DRAFT yang bisa diedit."]);
        }

        $data['header']  = $hdr;
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
            ->when(!$privileged, fn($q) => $q->where(fn($s) =>
                $s->whereIn('dept_code', $userDepts)->orWhere('location_code','011')
            ))
            ->orderBy('location_name')->get();

        $data['statusTr']   = $this->statusLabel($hdr->status);
        $data['editReason'] = $editReason;
        $data['currentDateValue'] = date('d-m-Y');

        return view("stockConsumption.edit", $data);
    }

    // ============================================================
    // UPDATE — snapshot + delete-insert detail (tanpa reverse movement)
    // ============================================================
    public function update(Request $request)
    {
        $username   = Auth::user()->username;
        $title      = "Save {$this->title}";
        $scNumber   = $request->scNumber;
        $scDate     = $request->scDate;
        $location   = $request->location;
        $note       = $request->note;
        $editReason = $request->editReason;
        $articles   = json_decode($request->articles, true) ?? [];

        $hdr = DB::table('stock_consumption_hdr')->where('sc_number', $scNumber)->first();
        if (!$hdr) {
            return response()->json(['status'=>0,'title'=>$title,'message'=>['Data tidak ditemukan'],'alert'=>'error']);
        }
        if ($hdr->status != '1') {
            return response()->json(['status'=>0,'title'=>$title,
                'message'=>["Dokumen berstatus {$this->statusLabel($hdr->status)}, hanya DRAFT yang bisa diedit."],
                'alert'=>'error']);
        }

        $errors = [];
        if (!$scDate)         $errors[] = "Date harus diisi";
        if (!$location)       $errors[] = "Location harus dipilih";
        if (!$note)           $errors[] = "Notes harus diisi";
        if (empty($articles)) $errors[] = "Artikel harus diisi";
        if ($errors) {
            return response()->json(['status'=>0,'title'=>$title,'message'=>$errors,'alert'=>'error']);
        }

        $deptCode = DB::table('stock_location_master')->where('location_code',$location)->value('dept_code');

        DB::beginTransaction();
        try {
            $rev = $this->snapshotHistory($hdr, $username, $editReason);

            // delete-insert detail — tidak perlu reverse movement (belum ada, masih DRAFT)
            DB::table('stock_consumption_det')->where('sc_number', $scNumber)->delete();
            $this->insertDetails($scNumber, $articles, $username);

            DB::table('stock_consumption_hdr')->where('sc_number', $scNumber)->update([
                'sc_date'      => $scDate,
                'location_code'=> $location,
                'note'         => $note,
                'status'       => '1',
                'num_revision' => $rev,
                'dept_code'    => $deptCode,
                'updated_by'   => $username,
                'updated_at'   => now(),
            ]);

            DB::commit();
            \LogActivity::addToLog($title, "username: $username $scNumber updated (rev $rev)");
            return response()->json([
                'status'  => 1, 'title' => $title,
                'message' => "$title $scNumber is successfully updated",
                'alert'   => 'success', 'scNumber' => $scNumber, 'oEdit' => true,
                'redirect_url' => route('stockConsumption.show', ['id' => Crypt::encryptString($scNumber)]),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status'=>0,'title'=>$title,'message'=>[$e->getMessage()],'alert'=>'error']);
        }
    }

    // ── Snapshot header + detail ke tabel history ────────────────
    private function snapshotHistory($hdr, string $username, ?string $reason = null): int
    {
        $rev = (int)($hdr->num_revision ?? 0) + 1;

        DB::table('stock_consumption_hdr_hist')->insert([
            'sc_number'    => $hdr->sc_number,
            'num_revision' => $rev,
            'sc_date'      => $hdr->sc_date,
            'location_code'=> $hdr->location_code,
            'note'         => $hdr->note,
            'status'       => $hdr->status,
            'edit_reason'  => $reason ?? '-',
            'revised_by'   => $username,
            'revised_at'   => now(),
        ]);

        $rows = [];
        foreach (DB::table('stock_consumption_det')->where('sc_number', $hdr->sc_number)->get() as $d) {
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
    // POSTING — hanya Superuser/accounting. COA otomatis, tidak ada input.
    // ============================================================
    public function posting(Request $request)
    {
        $user     = Auth::user();
        $username = $user->username;
        $title    = "Posting {$this->title}";

        if (!($user->hasAnyRole(['Superuser','accounting']) || $user->can('stockConsumption-posting'))) {
            return redirect()->back()->with(['title'=>$title,'alert'=>'warning','message'=>'Anda tidak berwenang posting']);
        }

        $scNumber = Crypt::decryptString($request->id);

        $hdr = DB::table('stock_consumption_hdr')->where('sc_number', $scNumber)->first();
        if (!$hdr) {
            return redirect()->back()->with(['title'=>$title,'alert'=>'warning','message'=>'Data tidak ditemukan']);
        }
        if ($hdr->status == '4') {
            return redirect()->back()->with(['title'=>$title,'alert'=>'warning','message'=>"$title gagal: sudah diposting"]);
        }
        if ($hdr->status == '5') {
            return redirect()->back()->with(['title'=>$title,'alert'=>'warning','message'=>"$title gagal: sudah dicancel"]);
        }
        if ($hdr->status != '1') {
            return redirect()->back()->with(['title'=>$title,'alert'=>'warning','message'=>"$title gagal: status tidak valid"]);
        }

        DB::beginTransaction();
        try {
            $result = $this->processPosting($hdr, $username);
            if (!$result['success']) {
                DB::rollBack();
                $msg = is_array($result['message']) ? implode(' | ', $result['message']) : $result['message'];
                return redirect()->back()->with(['title'=>$title,'alert'=>'warning','message'=>$msg]);
            }

            DB::table('stock_consumption_hdr')->where('sc_number', $scNumber)->update([
                'status'        => '4',
                'kas_number'    => $result['kas_number'],
                'total_amount'  => $result['total_amount'],
                'authorized_by' => $username,
                'authorized_at' => now(),
                'updated_by'    => $username,
                'updated_at'    => now(),
            ]);

            DB::commit();
            $message = "$title $scNumber Successfully Posted";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['title'=>$title,'alert'=>'success','message'=>$message]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with(['title'=>$title,'alert'=>'warning','message'=>$e->getMessage()]);
        }
    }

    // ── Inti posting: movement + stok + jurnal ───────────────────
    private function processPosting(object $hdr, string $username): array
    {
        try {
            $lines = $this->resolveLines($hdr);
        } catch (\RuntimeException $e) {
            return ['success'=>false,'message'=>[$e->getMessage()]];
        }

        $stockLoc = $this->getStockLocation($hdr->location_code);
        $physLoc  = $hdr->location_code;

        $this->lockMovementSequence();
        $seq         = (int) DB::table('warehouse_movement')->max('movement_code');
        $movRows     = [];
        $totalAmount = 0.0;

        foreach ($lines as $line) {
            $price        = $this->getAvgPrice($line['article_code'], $stockLoc);
            $totalAmount += $line['qty'] * $price;

            $movRows[] = $this->buildMovement(
                ++$seq, $hdr, $line, $stockLoc, $physLoc, $price, $username
            );
        }

        if ($movRows) DB::table('warehouse_movement')->insert($movRows);

        foreach ($lines as $line) {
            $this->recalculateMovementAndStock($line['article_code'], $stockLoc, $hdr->sc_date);
        }

        $kasNumber = $this->postJournal($hdr, $lines, $totalAmount, $username);

        return ['success'=>true,'kas_number'=>$kasNumber,'total_amount'=>$totalAmount];
    }

    // ── Aggregate detail ke baris movement (qty sudah base UOM) ──
    private function resolveLines(object $hdr): array
    {
        $details = DB::table('stock_consumption_det')
            ->leftJoin('article','article.article_code','=','stock_consumption_det.article_code')
            ->where('stock_consumption_det.sc_number', $hdr->sc_number)
            ->select('stock_consumption_det.*','article.article_desc','article.uom as article_uom','article.article_type')
            ->get();

        if ($details->isEmpty()) {
            throw new \RuntimeException("Consumption {$hdr->sc_number} gagal: tidak ada detail");
        }

        // Guard eksplisit — pesan jelas kalau ada artikel di luar scope CM1/CM2
        $invalidTypes = $details->pluck('article_type')->unique()
            ->reject(fn($t) => in_array($t, ['CM1', 'CM2']));
        if ($invalidTypes->isNotEmpty()) {
            throw new \RuntimeException(
                "Consumption {$hdr->sc_number} berisi artikel di luar scope (tipe: " . $invalidTypes->implode(', ') . "). "
                . "Stock Consumption hanya untuk CM1/CM2 — RMP/RMNP diproses lewat Actual Loading/Finish Goods."
            );
        }

        $bag = [];
        foreach ($details as $d) {
            $base = $this->toBaseQty($d->article_code, (float)$d->qty, (string)$d->uom);
            if ($base <= 0) continue;
            $c = $d->article_code;
            if (!isset($bag[$c])) {
                $bag[$c] = [
                    'article_code' => $c,
                    'article_desc' => $d->article_desc ?? '',
                    'article_type' => $d->article_type,
                    'uom'          => $d->article_uom ?? $d->uom ?? 'PCS',
                    'qty'          => 0.0,
                    'notes'        => [],
                ];
            }
            $bag[$c]['qty'] += $base;
            if ($d->note) $bag[$c]['notes'][] = $d->note;
        }

        return array_values($bag);
    }

    private function buildMovement(int $seq, object $hdr, array $line, string $locationNumber, string $movementFrom, float $price, string $username): array
    {
        $qty  = $line['qty'];
        $desc = trim(($hdr->note ?? '') . (!empty($line['notes']) ? ' ['.implode(', ', array_unique($line['notes'])).']' : ''));

        // Dihitung di PHP lewat query binding — hindari raw string interpolation ke SQL.
        $prevQty = (float) DB::selectOne(
            "SELECT get_last_qty_new(?, ?, ?, ?) AS q",
            [$line['article_code'], $hdr->sc_date, $this->siteCode, $locationNumber]
        )->q;

        return [
            'movement_code'     => $seq,
            'movement_date'     => \Carbon\Carbon::createFromFormat('d-m-Y', $hdr->sc_date)->format('d-m-Y'),
            'artikel_code'      => $line['article_code'],
            'artikel_desc'      => $line['article_desc'],
            'movement_min'      => $qty,
            'movement_plus'     => 0,
            'movement_price'    => $price,
            'movement_transnno' => $hdr->sc_number,
            'movement_type'     => $this->movementType,
            'movement_desc'     => $desc,
            'movement_from'     => $movementFrom,
            'movement_to'       => null,
            'partner_type'      => 'CONS',
            'created_by'        => $username,
            'created_at'        => now(),
            'site_code'         => $this->siteCode,
            'location_number'   => $locationNumber,
            'last_qty'          => $prevQty - $qty,
        ];
    }

    // ── Jurnal ke kas_hdr/kas_det — akun ditentukan otomatis by article_type ──
    private function postJournal(object $hdr, array $lines, float $total, string $username): string
    {
        // Opsi A: nomor voucher = nomor SC, tidak generate nomor baru.
        $voucherNumber = $hdr->sc_number;
        $voucherDate   = \Carbon\Carbon::createFromFormat('d-m-Y', $hdr->sc_date)->format('d-m-Y');
        $desc          = "{$hdr->sc_number} - " . ($hdr->note ?: 'Stock Consumption');

        DB::table('kas_hdr')->insert([
            'voucher_number' => $voucherNumber,
            'voucher_type'   => 'SCO',
            'voucher_date'   => $voucherDate,
            'receive_from'   => null,
            'paid_to'        => null,
            'amount'         => $total,
            'period'         => (int) \Carbon\Carbon::createFromFormat('d-m-Y', $hdr->sc_date)->format('n'),
            'year'           => \Carbon\Carbon::createFromFormat('d-m-Y', $hdr->sc_date)->format('Y'),
            'note'           => $hdr->note,
            'status'         => '3',
            'created_by'     => $username,
            'updated_by'     => $username,
            'created_at'     => now(),
            'updated_at'     => now(),
            'description'    => $desc,
            'invoice_date'   => null,
            'tax_number'     => null,
        ]);

        $debitByAccount  = [];
        $creditByAccount = [];

        foreach ($lines as $line) {
            $price  = $this->getAvgPrice($line['article_code'], $this->getStockLocation($hdr->location_code));
            $amount = $line['qty'] * $price;

            $debitAcc  = $this->resolveConsumptionCoa($line['article_type'] ?? '');
            $creditAcc = $this->resolveInventoryCoa($line['article_type'] ?? '');

            $debitByAccount[$debitAcc]   = ($debitByAccount[$debitAcc] ?? 0) + $amount;
            $creditByAccount[$creditAcc] = ($creditByAccount[$creditAcc] ?? 0) + $amount;
        }

        foreach ($debitByAccount as $account => $amount) {
            DB::table('kas_det')->insert([
                'voucher_number' => $voucherNumber,
                'account'        => $account,
                'description'    => $desc,
                'cost_center'    => $hdr->dept_code ?? null,
                'debit'          => $amount,
                'credit'         => 0,
                'reference'      => $hdr->sc_number,
                'created_by'     => $username,
                'updated_by'     => $username,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        foreach ($creditByAccount as $account => $amount) {
            DB::table('kas_det')->insert([
                'voucher_number' => $voucherNumber,
                'account'        => $account,
                'description'    => $desc,
                'cost_center'    => $hdr->dept_code ?? null,
                'debit'          => 0,
                'credit'         => $amount,
                'reference'      => $hdr->sc_number,
                'created_by'     => $username,
                'updated_by'     => $username,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        return $voucherNumber;
    }

    private function reverseJournal(?string $kasNumber): void
    {
        if (!$kasNumber) return;
        DB::table('kas_det')->where('voucher_number', $kasNumber)->delete();
        DB::table('kas_hdr')->where('voucher_number', $kasNumber)->delete();
    }

    // ============================================================
    // CANCEL
    // ============================================================
    public function cancel(Request $request)
    {
        $user     = Auth::user();
        $username = $user->username;
        $title    = "Cancel {$this->title}";
        $scNumber = Crypt::decryptString($request->id);

        $hdr = DB::table('stock_consumption_hdr')->where('sc_number', $scNumber)->first();
        if (!$hdr) {
            return response()->json(['status'=>0,'title'=>$title,'message'=>['Data tidak ditemukan'],'alert'=>'error']);
        }
        if ($hdr->status == '5') {
            return response()->json(['status'=>0,'title'=>$title,'message'=>['Sudah dicancel'],'alert'=>'warning']);
        }

        $isCreator = ($hdr->created_by === $username);
        $isAccSup  = $user->hasAnyRole(['Superuser','accounting']);

        if ($hdr->status == '4') {
            if (!$isAccSup) {
                return response()->json(['status'=>0,'title'=>$title,'message'=>['Anda tidak berwenang cancel dokumen POSTED'],'alert'=>'warning']);
            }
        } else {
            if (!$isCreator && !$isAccSup) {
                return response()->json(['status'=>0,'title'=>$title,'message'=>['Anda tidak berwenang cancel dokumen ini'],'alert'=>'warning']);
            }
        }

        DB::beginTransaction();
        try {
            if ($hdr->status == '4') {
                // reverse movement + jurnal
                $stockLoc = $this->getStockLocation($hdr->location_code);
                $articles = DB::table('stock_consumption_det')->where('sc_number',$scNumber)->pluck('article_code')->unique()->toArray();

                DB::table('warehouse_movement')
                    ->where('movement_transnno', $scNumber)
                    ->where('movement_type', $this->movementType)
                    ->delete();

                foreach ($articles as $ac) {
                    $this->recalculateMovementAndStock($ac, $stockLoc, $hdr->sc_date);
                }

                $this->reverseJournal($hdr->kas_number);
            }

            $newNote = trim(($hdr->note ?? '') . ';(Cancel by '.$username.')');
            DB::table('stock_consumption_hdr')->where('sc_number', $scNumber)->update([
                'status'     => '5',
                'note'       => $newNote,
                'kas_number' => null,
                'updated_by' => $username,
                'updated_at' => now(),
            ]);

            DB::commit();
            $message = "$title $scNumber Successfully Canceled";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return response()->json(['status'=>1,'title'=>$title,'message'=>$message,'alert'=>'success']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status'=>0,'title'=>$title,'message'=>[$e->getMessage()],'alert'=>'error']);
        }
    }

    // ============================================================
    // SHOW
    // ============================================================
    public function show(Request $request)
    {
        $scNumber = Crypt::decryptString($request->id);
        $user     = Auth::user();
        $username = $user->username;
        $canPost  = $user->hasAnyRole(['Superuser','accounting']) || $user->can('stockConsumption-posting');

        $hdr = DB::table('stock_consumption_hdr')
            ->leftJoin('stock_location_master as loc','loc.location_code','=','stock_consumption_hdr.location_code')
            ->leftJoin('users as uCreate','uCreate.username','=','stock_consumption_hdr.created_by')
            ->leftJoin('users as uAuth','uAuth.username','=','stock_consumption_hdr.authorized_by')
            ->where('stock_consumption_hdr.sc_number', $scNumber)
            ->select(
                'stock_consumption_hdr.*',
                'loc.location_name',
                'uCreate.name as created_name',
                'uAuth.name as authorized_name',
                DB::raw('(select count(*) from stock_consumption_det where sc_number = stock_consumption_hdr.sc_number) as sum_row'),
                DB::raw('(select sum(qty) from stock_consumption_det where sc_number = stock_consumption_hdr.sc_number) as sum_qty')
            )
            ->first();

        if (!$hdr) {
            return redirect()->back()->with(['title'=>'Detail','alert'=>'warning','message'=>'Data tidak ditemukan']);
        }

        $data['title']    = "Detail {$this->title}";
        $data['subtitle'] = "Detail {$this->title}";
        $data['header']   = $hdr;
        $data['details']  = DB::table('stock_consumption_det')
            ->leftJoin('article','article.article_code','=','stock_consumption_det.article_code')
            ->where('stock_consumption_det.sc_number', $scNumber)
            ->select('stock_consumption_det.*','article.article_alternative_code','article.article_desc')
            ->orderBy('stock_consumption_det.id')
            ->get();

        $data['statusTr'] = $this->statusLabel($hdr->status);
        $data['canPost']  = $canPost;

        return view("stockConsumption.show", $data);
    }

    // ============================================================
    // LIST (DataTables)
    // ============================================================
    public function list(Request $request)
    {
        $user       = Auth::user();
        $username   = $user->username;
        $userDepts  = DB::table('user_dept')->where('username',$username)->pluck('dept')->toArray();
        $privileged = $user->hasAnyRole(['Superuser','accounting']);
        $canPost    = $user->hasAnyRole(['Superuser','accounting']) || $user->can('stockConsumption-posting');

        $searchNo     = strtolower((string) $request->searchNo);
        $searchStatus = $request->searchStatus;
        $searchLoc    = $request->searchLoc;
        $scDate       = $request->scDate;

        $fromDate = $toDate = "";
        if ($scDate) {
            $d = explode("to", $scDate);
            $fromDate = implode("/", array_reverse(explode("-", trim($d[0]))));
            $toDate   = count($d) > 1 ? implode("/", array_reverse(explode("-", trim($d[1])))) : $fromDate;
        }

        $query = DB::table('stock_consumption_hdr')
            ->leftJoin('stock_location_master as loc','loc.location_code','=','stock_consumption_hdr.location_code')
            ->where(function ($q) use ($searchNo,$searchStatus,$searchLoc,$scDate,$fromDate,$toDate) {
                if ($searchNo)     $q->where('stock_consumption_hdr.sc_number','ilike',"%$searchNo%");
                if ($searchStatus) $q->where('stock_consumption_hdr.status',$searchStatus);
                if ($searchLoc)    $q->where('stock_consumption_hdr.location_code',$searchLoc);
                if ($scDate)       $q->whereBetween(DB::raw("to_date(stock_consumption_hdr.sc_date,'DD-MM-YYYY')"),[$fromDate,$toDate]);
            });

        if (!$privileged) {
            $query->where(function ($q) use ($userDepts,$username) {
                $q->whereIn('loc.dept_code',$userDepts)->orWhere('stock_consumption_hdr.created_by',$username);
            });
        }

        $query->select(
            'stock_consumption_hdr.id',
            'stock_consumption_hdr.sc_number',
            'stock_consumption_hdr.sc_date',
            'stock_consumption_hdr.status',
            'stock_consumption_hdr.note',
            'stock_consumption_hdr.created_by',
            'stock_consumption_hdr.created_at',
            'stock_consumption_hdr.authorized_by',
            'stock_consumption_hdr.dept_code',
            'loc.location_name'
        )->orderBy('stock_consumption_hdr.created_at','desc');

        return DataTables::of($query)
            ->addColumn('action', function ($row) use ($canPost, $username) {
                $encId = Crypt::encryptString($row->sc_number);
                $st    = $row->status;
                $isCreator = ($row->created_by === $username);

                $b  = '<div class="d-inline-flex"><a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown"><i data-feather="menu"></i></a>';
                $b .= '<div class="dropdown-menu dropdown-menu-right">';
                $b .= '<a href="'.route('stockConsumption.show',['id'=>$encId]).'" class="dropdown-item"><i data-feather="eye"></i><span> Detail</span></a>';

                if ($st == '1') {
                    $b .= '<a href="'.route('stockConsumption.edit',['id'=>$encId]).'" class="dropdown-item"><i data-feather="edit-2"></i><span> Edit</span></a>';
                }

                // POSTING: hanya dari halaman detail — link ke show
                if ($st == '1' && $canPost) {
                    $b .= '<a href="'.route('stockConsumption.show',['id'=>$encId]).'" class="dropdown-item text-success"><i data-feather="check-circle"></i><span> Posting</span></a>';
                }

                if ($st != '5' && ($isCreator || $canPost)) {
                    $b .= "<a href='javascript:;' class='dropdown-item text-danger' data-ajax-delete='true'
                                data-confirm='Batalkan konsumsi ini?|Stok & jurnal (jika sudah posting) akan dikembalikan.'
                                data-url='".route('stockConsumption.cancel',['id'=>$encId])."'>
                                <i data-feather='x-circle'></i><span> Cancel</span></a>";
                }

                $b .= '</div></div>';
                return $b;
            })
            ->editColumn('status', function ($row) {
                $cfg = [
                    '1' => ['badge-primary',  'DRAFT'],
                    '4' => ['badge-success',  'POSTED'],
                    '5' => ['badge-danger',   'CANCELED'],
                ];
                [$cls, $lbl] = $cfg[$row->status] ?? ['badge-secondary', $row->status];
                return "<div class='badge $cls'>$lbl</div>";
            })
            ->rawColumns(['action','status'])
            ->make(true);
    }

    public function listDetail(Request $request)
    {
        $user       = Auth::user();
        $username   = $user->username;
        $userDepts  = DB::table('user_dept')->where('username', $username)->pluck('dept')->toArray();
        $privileged = $user->hasAnyRole(['Superuser','accounting','finance']);

        $searchNo     = strtolower((string) $request->searchNo);
        $searchStatus = $request->searchStatus;
        $searchLoc    = $request->searchLoc;
        $scDate       = $request->scDate;

        $fromDate = $toDate = "";
        if ($scDate) {
            $d = explode("to", $scDate);
            $fromDate = implode("/", array_reverse(explode("-", trim($d[0]))));
            $toDate   = count($d) > 1 ? implode("/", array_reverse(explode("-", trim($d[1])))) : $fromDate;
        }

        $query = DB::table('stock_consumption_det')
            ->leftJoin('stock_consumption_hdr', 'stock_consumption_hdr.sc_number', '=', 'stock_consumption_det.sc_number')
            ->leftJoin('article', 'article.article_code', '=', 'stock_consumption_det.article_code')
            ->leftJoin('stock_location_master as loc', 'loc.location_code', '=', 'stock_consumption_hdr.location_code')
            ->where(function ($q) use ($searchNo, $searchStatus, $searchLoc, $scDate, $fromDate, $toDate) {
                if ($searchNo)     $q->where('stock_consumption_det.sc_number', 'ilike', "%$searchNo%");
                if ($searchStatus) $q->where('stock_consumption_hdr.status', $searchStatus);
                if ($searchLoc)    $q->where('stock_consumption_hdr.location_code', $searchLoc);
                if ($scDate)       $q->whereBetween(DB::raw("to_date(stock_consumption_hdr.sc_date,'DD-MM-YYYY')"), [$fromDate, $toDate]);
            });

        if (!$privileged) {
            $query->where(function ($q) use ($userDepts, $username) {
                $q->whereIn('loc.dept_code', $userDepts)->orWhere('stock_consumption_hdr.created_by', $username);
            });
        }

        $data = $query->select(
                'stock_consumption_hdr.sc_number',
                'stock_consumption_hdr.sc_date',
                'stock_consumption_hdr.status',
                'stock_consumption_hdr.created_by',
                'stock_consumption_hdr.created_at',
                'stock_consumption_hdr.authorized_by',
                'stock_consumption_det.id',
                'stock_consumption_det.qty',
                'stock_consumption_det.uom',
                'stock_consumption_det.note',
                'stock_consumption_det.article_code',
                'article.article_alternative_code',
                'article.article_desc',
                'loc.location_name'
            )
            ->orderBy('stock_consumption_det.id')
            ->get();

        return DataTables::of($data)
            ->editColumn('status', function ($row) {
                $cfg = [
                    '1' => ['badge-primary',  'DRAFT'],
                    '4' => ['badge-success',  'POSTED'],
                    '5' => ['badge-danger',   'CANCELED'],
                ];
                [$cls, $lbl] = $cfg[$row->status] ?? ['badge-secondary', $row->status];
                return "<div class='badge $cls'>$lbl</div>";
            })
            ->rawColumns(['status'])
            ->make(true);
    }

    // ============================================================
    // ARTICLE BY LOCATION
    // ============================================================
    public function articleByLocation(Request $request)
    {
        $stockLoc = $this->getStockLocation($request->location);
        $scNumber = $request->scNumber;

        $existingCodes = [];
        if ($scNumber) {
            $existingCodes = DB::table('stock_consumption_det')
                ->where('sc_number', $scNumber)
                ->pluck('article_code')
                ->toArray();
        }

        return DB::table('article as a')
            ->leftJoin('warehouse_stock as s', function ($join) use ($stockLoc) {
                $join->on('s.article_code', '=', 'a.article_code')
                     ->where('s.site_code', $this->siteCode)
                     ->where('s.location_number', $stockLoc);
            })
            ->where('a.status', '1')
            ->whereIn('a.article_type', ['CM1', 'CM2'])   // batasi scope modul
            ->where(function ($q) use ($existingCodes) {
                $q->where(DB::raw('coalesce(s.article_qty,0)'), '>', 0);
                if (!empty($existingCodes)) {
                    $q->orWhereIn('a.article_code', $existingCodes);
                }
            })
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
    // HELPERS
    // ============================================================
    private array $stockLocationCache = [];

    private function getStockLocation(string $code): string
    {
        if (!array_key_exists($code, $this->stockLocationCache)) {
            $parent = DB::table('stock_location_master')->where('location_code',$code)->value('parent_location');
            $this->stockLocationCache[$code] = $parent ?: $code;
        }
        return $this->stockLocationCache[$code];
    }

    private function getAvgPrice(string $articleCode, string $location): float
    {
        return (float)(DB::table('warehouse_stock')
            ->where('site_code',$this->siteCode)->where('article_code',$articleCode)->where('location_number',$location)
            ->value('avg_price') ?? 0);
    }

    private function toBaseQty(string $articleCode, float $qty, string $uom): float
    {
        $r = DB::selectOne(
            "SELECT ? * COALESCE(uom_conversion(?, (SELECT uom FROM article WHERE article_code = ?)), 1) AS q",
            [$qty, $uom, $articleCode]
        );
        return (float)($r->q ?? $qty);
    }

    private function updateWarehouseStock(string $articleCode, string $location, float $qty): void
    {
        DB::table('warehouse_stock')
            ->where('site_code',$this->siteCode)->where('article_code',$articleCode)->where('location_number',$location)
            ->update(['article_qty' => $qty]);
    }

    private function recalculateAvgPrice(string $articleCode, string $location): void
    {
        $movements = DB::table('warehouse_movement')
            ->where('artikel_code',$articleCode)->where('location_number',$location)->where('site_code',$this->siteCode)
            ->orderBy(DB::raw("TO_DATE(movement_date,'DD-MM-YYYY')"),'asc')->orderBy('movement_code','asc')
            ->select('movement_min','movement_plus','movement_price')->get();

        $qty = 0.0; $avg = 0.0;
        foreach ($movements as $m) {
            $plus = (float)$m->movement_plus; $min = (float)$m->movement_min;
            if ($plus > 0) {
                $nq  = $qty + $plus;
                $avg = $nq > 0 ? (($qty * $avg) + ($plus * (float)$m->movement_price)) / $nq : $avg;
                $qty = $nq;
            }
            if ($min > 0) $qty -= $min;
        }
        DB::table('warehouse_stock')
            ->where('site_code',$this->siteCode)->where('article_code',$articleCode)->where('location_number',$location)
            ->update(['avg_price' => $avg]);
    }

    private function recalculateMovementAndStock(string $articleCode, string $location, string $fromDate): void
    {
        $fromDate = preg_match('/^\d{2}-\d{2}-\d{4}$/', $fromDate)
            ? \Carbon\Carbon::createFromFormat('d-m-Y', $fromDate)->format('Y-m-d')
            : \Carbon\Carbon::parse($fromDate)->format('Y-m-d');

        $balanceBefore = (float)DB::selectOne(
            "SELECT get_last_qty_new(?, TO_CHAR(TO_DATE(?, 'YYYY-MM-DD') - INTERVAL '1 day', 'YYYY-MM-DD'), ?, ?) AS bal",
            [$articleCode, $fromDate, $this->siteCode, $location]
        )->bal;

        $movements = DB::table('warehouse_movement')
            ->where('artikel_code',$articleCode)->where('location_number',$location)->where('site_code',$this->siteCode)
            ->where(DB::raw("TO_DATE(movement_date,'DD-MM-YYYY')"),'>=',DB::raw("TO_DATE('$fromDate','YYYY-MM-DD')"))
            ->whereNotIn('movement_type',['RETURN-CANCEL','RETURN-REVERSE'])
            ->where('movement_type','NOT LIKE','CANCEL %')
            ->where('movement_type','NOT LIKE','DELETE%')
            ->where('movement_type','NOT LIKE','REVISI %')
            ->whereNotExists(fn($q)=>$q->select(DB::raw(1))->from('stock_adjustment_hdr')
                ->whereColumn('stock_adjustment_hdr.adj_code','warehouse_movement.movement_transnno')
                ->where('stock_adjustment_hdr.adj_type','OPENING BALANCE'))
            ->orderBy(DB::raw("TO_DATE(movement_date,'DD-MM-YYYY')"),'asc')->orderBy('movement_code','asc')
            ->select('movement_code','movement_min','movement_plus')->get();

        if ($movements->isEmpty()) {
            $this->updateWarehouseStock($articleCode, $location, $balanceBefore);
            $this->recalculateAvgPrice($articleCode, $location);
            return;
        }

        $running = $balanceBefore;
        foreach ($movements as $mov) {
            $running = $running - (float)$mov->movement_min + (float)$mov->movement_plus;
            DB::table('warehouse_movement')->where('movement_code',$mov->movement_code)->update(['last_qty'=>$running]);
        }

        $latest = (float)DB::table('warehouse_movement')
            ->where('artikel_code',$articleCode)->where('location_number',$location)->where('site_code',$this->siteCode)
            ->orderBy(DB::raw("TO_DATE(movement_date,'DD-MM-YYYY')"),'desc')->orderBy('movement_code','desc')
            ->value('last_qty');

        $this->updateWarehouseStock($articleCode, $location, $latest);
        $this->recalculateAvgPrice($articleCode, $location);
    }

    private function statusLabel(string $status): string
    {
        return ['1'=>'DRAFT','4'=>'POSTED','5'=>'CANCELED'][$status] ?? $status;
    }

    private function resolveConsumptionCoa(string $articleType): string
    {
        return match ($articleType) {
            'CM1' => '5000.33',
            'CM2' => '5000.34',
            default => throw new \RuntimeException(
                "Article type '$articleType' tidak didukung di Stock Consumption. RMP/RMNP diproses lewat Actual Loading/Finish Goods."
            ),
        };
    }

    private function resolveInventoryCoa(string $articleType): string
    {
        return match ($articleType) {
            'CM1' => '1100.32.1',
            'CM2' => '1100.32.2',
            default => throw new \RuntimeException(
                "Article type '$articleType' tidak didukung di Stock Consumption. RMP/RMNP diproses lewat Actual Loading/Finish Goods."
            ),
        };
    }
}