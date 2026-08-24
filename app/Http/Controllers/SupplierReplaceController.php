<?php

namespace App\Http\Controllers;

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

class SupplierReplaceController extends Controller
{
    /*
        ============================================================
        SUPPLIER REPLACE
        ============================================================
        Menarik data dari SUPPLIER RETURN. Supplier mengirim barang
        pengganti -> barang MASUK kembali ke lokasi yang tercatat di
        return (movement_plus).

        Status header (supplier_replace_hdr.status):
            '1' => OPEN
            '2' => CLOSED
            '3' => CANCELED
        (mengikuti konvensi yang dipakai punyaReplaceAktif() di
         SupplierReturnController: whereNotIn('status',['3']))

        Status supplier_return_hdr yang disentuh modul ini:
            '1' => OPEN     (masih ada sisa qty return yang belum diganti)
            '3' => CLOSED   (semua qty return sudah diganti penuh)
            '4' => CANCELED (tidak disentuh modul ini)

        POLA MOVEMENT (disamakan dgn SupplierReturnController):
        - warehouse_movement sebagai ledger, movement_code sekuensial
          (di-lock via pg_advisory_xact_lock).
        - last_qty diisi get_last_qty_new(...) + qty (barang masuk),
          lalu di-recalc penuh via recalculateMovementAndStock().
        - CANCEL / DELETE / REVISI  => movement DIHAPUS
          (deleteMovementAndRecalc), BUKAN insert reversal.
        - REVISI => dokumen lama di-CANCEL, dibuat dokumen baru "-R<n>",
          yang diambil untuk perhitungan adalah dokumen aktif terbaru.

        VALIDASI anti-over: total qty replace per artikel (akumulasi
        semua dokumen replace non-CANCELED utk return tsb) tidak boleh
        melebihi qty di supplier_return_det.

        CATATAN SETUP:
        - Butuh 1 baris di master_code dengan code_key = 'REC-REPLACE'
          (atau ganti $this->moduleCode sesuai kebutuhan).
        - Butuh tabel:
            supplier_replace_hdr (
                id, replace_number, return_number, replace_date,
                supplier_id, location_number, status, note, reason,
                origin_replace_number, created_by, updated_by,
                created_at, updated_at
            )
            supplier_replace_det (
                id, replace_number, return_number, article_code,
                qty_return, qty, uom, created_by, updated_by,
                created_at, updated_at
            )
    */

    private $title;
    private $moduleCode;
    private $decimalPlaces;
    private $siteCode;
    private $mvType;

    public function __construct()
    {
        $this->title       = "Supplier Replace";
        $this->moduleCode  = "REC-REPLACE";
        $this->decimalPlaces = config('globalParam.decimal');
        $this->siteCode    = 'HO';
        $this->mvType      = 'SUPPLIER REPLACE';
    }

    /* ============================================================
     *  HELPER TANGGAL
     * ============================================================ */
    private function toIsoDate($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $value = str_replace('/', '-', $value);

        $d = \DateTime::createFromFormat('d-m-Y', $value);
        $errors = \DateTime::getLastErrors();
        if (!$d || ($errors && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new \Exception("Format tanggal tidak valid: '{$value}' (harus dd-mm-yyyy)");
        }

        return $d->format('Y-m-d');
    }

    /* ============================================================
     *  KOLOM DATATABLE
     * ============================================================ */
    public function getTableColoumn()
    {
        $kolom =
        [
            ['data'=>'action','name'=>'action','title'=>'Action','orderable'=>false,'searchable'=>false],
            ['data'=>'replace_number','name'=>'replace_number','title'=>'Replace Number'],
            ['data'=>'return_number','name'=>'return_number','title'=>'Return Number'],
            ['data'=>'return_date','name'=>'return_date','title'=>'Return Date'],
            ['data'=>'replace_date','name'=>'replace_date','title'=>'Replace Date'],
            ['data'=>'location_name','name'=>'location_name','title'=>'Location'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'supplier_id','name'=>'supplier_id','title'=>'Supplier Code'],
            ['data'=>'supplier_name','name'=>'supplier_name','title'=>'Supplier'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created Date'],
        ];

        return json_encode($kolom, true);
    }

    public function getTableColoumnDetail()
    {
        $kolom =
        [
            ['data'=>'replace_number','name'=>'replace_number','title'=>'Replace Number'],
            ['data'=>'return_number','name'=>'return_number','title'=>'Return Number'],
            ['data'=>'return_date','name'=>'return_date','title'=>'Return Date'],
            ['data'=>'replace_date','name'=>'replace_date','title'=>'Replace Date'],
            ['data'=>'supplier_id','name'=>'supplier_id','title'=>'Supplier Code'],
            ['data'=>'supplier_name','name'=>'supplier_name','title'=>'Supplier'],
            ['data'=>'article_alternative_code','name'=>'article_alternative_code','title'=>'Article Code'],
            ['data'=>'article_desc','name'=>'article_desc','title'=>'Description'],
            ['data'=>'qty_return','name'=>'qty_return','title'=>'Qty Return'],
            ['data'=>'qty','name'=>'qty','title'=>'Qty Replace'],
            ['data'=>'sisa_qty_return','name'=>'sisa_qty_return','title'=>'Sisa Qty Return'],
            ['data'=>'uom','name'=>'uom','title'=>'UOM'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created Date'],
        ];
        return json_encode($kolom, true);
    }

    /* ============================================================
     *  INDEX
     * ============================================================ */
    public function index(Request $request)
    {
        $data['title']       = "$this->title";
        $data['kolom']       = $this->getTableColoumn();
        $data['kolomDetail'] = $this->getTableColoumnDetail();
        $data['status']      = ['1'=>'OPEN','2'=>'CLOSED'];

        $data['suppliers'] = DB::table('third_party')
            ->where('third_party_type', '=', 'supp')
            ->orderBy('nama')
            ->get();

        return view("supplierReplace.index", $data);
    }

    /* ============================================================
     *  NOMOR DOKUMEN
     * ============================================================ */
    public function getLastCode($key)
    {
        DB::table('master_code')
            ->where('code_key', $key)
            ->update([
                'code_number' => DB::raw('code_number + 1'),
                'updated_by'  => Auth::user()->username,
                'updated_at'  => date('Y-m-d H:i:s')
            ]);

        $newCode = DB::table('master_code')->where('code_key', $key)->value('code_number');

        $newCode = str_pad($newCode, 5, "0", STR_PAD_LEFT);
        $month   = str_pad(date('n'), 2, "0", STR_PAD_LEFT);
        $year    = date('y');
        return "$key-$year-$month-$newCode";
    }

    /* ============================================================
     *  PERHITUNGAN SISA & STATUS
     * ============================================================ */

    /**
     * Sisa qty return TOTAL (semua artikel) yang belum di-replace oleh
     * dokumen replace aktif (status <> '3') untuk return tsb.
     * <= 0 artinya return sudah diganti penuh.
     */
    private function sisaReturn($returnNumber)
    {
        $result = DB::select("
            SELECT COALESCE(SUM(qty),0) - COALESCE(SUM(qty_replace),0) AS sisa_return
            FROM (
                SELECT rd.qty,
                    COALESCE((
                        SELECT SUM(pd.qty)
                        FROM supplier_replace_det pd
                        JOIN supplier_replace_hdr ph ON ph.replace_number = pd.replace_number
                        WHERE pd.return_number = rd.return_number
                          AND pd.article_code  = rd.article_code
                          AND ph.status <> '3'
                    ), 0) AS qty_replace
                FROM supplier_return_det rd
                WHERE rd.return_number = ?
            ) x
        ", [$returnNumber]);

        return (float) ($result[0]->sisa_return ?? 0);
    }

    /**
     * Validasi kuota per-artikel: total replace 1 artikel (akumulasi
     * dokumen replace non-CANCELED) tidak boleh melebihi qty return.
     */
    private function assertNotExceedReturn($returnNumber, $articleCode, $qtyNow, $excludeReplaceNumber = null)
    {
        $qtyReturn = (float) DB::table('supplier_return_det')
            ->where('return_number', $returnNumber)
            ->where('article_code', $articleCode)
            ->sum('qty');

        $qtyReplacedLain = (float) DB::table('supplier_replace_det')
            ->join('supplier_replace_hdr', 'supplier_replace_hdr.replace_number', '=', 'supplier_replace_det.replace_number')
            ->where('supplier_replace_det.return_number', $returnNumber)
            ->where('supplier_replace_det.article_code', $articleCode)
            ->whereNotIn('supplier_replace_hdr.status', ['3'])
            ->when($excludeReplaceNumber, function ($q) use ($excludeReplaceNumber) {
                $q->where('supplier_replace_det.replace_number', '<>', $excludeReplaceNumber);
            })
            ->sum('supplier_replace_det.qty');

        if (($qtyReplacedLain + $qtyNow) > $qtyReturn) {
            $sisa = $qtyReturn - $qtyReplacedLain;
            throw new \Exception("Qty replace artikel {$articleCode} melebihi qty return. Sisa yang boleh di-replace: {$sisa}");
        }
    }

    /** Return CLOSED('3') kalau habis, OPEN('1') kalau masih sisa. Tidak menyentuh return CANCELED('4'). */
    private function applyReturnStatus($returnNumber, $username)
    {
        DB::table('supplier_return_hdr')
            ->where('return_number', $returnNumber)
            ->whereIn('status', ['1', '3'])
            ->update([
                'status'     => ($this->sisaReturn($returnNumber) <= 0) ? '3' : '1',
                'updated_by' => $username,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    /** Replace CLOSED('2') kalau return sudah habis, OPEN('1') kalau masih sisa. */
    private function applyReplaceStatus($returnNumber, $username)
    {
        $status = ($this->sisaReturn($returnNumber) <= 0) ? '2' : '1';

        DB::table('supplier_replace_hdr')
            ->where('return_number', $returnNumber)
            ->whereNotIn('status', ['3'])
            ->update([
                'status'     => $status,
                'updated_by' => $username,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return $status;
    }

    /* ============================================================
     *  CREATE
     * ============================================================ */
    public function create(Request $request)
    {
        $data['title']    = "Create $this->title";
        $data['subtitle'] = "Create $this->title";

        $data['suppliers'] = DB::table('third_party')
            ->where('third_party_type', '=', 'supp')
            ->orderBy('nama')
            ->get();

        $data['currentDate'] = date('d-m-Y');

        return view("supplierReplace.create", $data);
    }

    /* ============================================================
     *  STORE
     * ============================================================ */
    public function store(Request $request)
    {
        $username     = Auth::user()->username;
        $articles     = json_decode($request->articles);
        $replaceDate  = $request->replaceDate;
        $returnNumber = $request->returnNumber;
        $note         = $request->note;
        $leadCode     = $this->moduleCode;

        $validation = Validator::make($request->all(), [
            'returnNumber' => 'required',
            'replaceDate'  => 'required',
        ], ['required' => 'The field is required.']);

        if ($validation->fails()) {
            $error_array = [];
            foreach ($validation->messages()->getMessages() as $field_name => $messages) {
                $error_array[] = $messages;
            }
            return response()->json(['status' => 0, 'title' => "Save $this->title", 'message' => $error_array, 'alert' => 'warning']);
        }

        if (empty($articles)) {
            return response()->json(['status' => 0, 'title' => "Save $this->title", 'message' => 'Article list is empty', 'alert' => 'warning']);
        }

        // Supplier & lokasi diambil DARI RETURN (anti-tamper), bukan dari client.
        $returnHdr = DB::table('supplier_return_hdr')->where('return_number', $returnNumber)->first();
        if (!$returnHdr) {
            return response()->json(['status' => 0, 'title' => "Save $this->title", 'message' => "Return $returnNumber tidak ditemukan", 'alert' => 'warning']);
        }
        if ($returnHdr->status == '4') {
            return response()->json(['status' => 0, 'title' => "Save $this->title", 'message' => "Return $returnNumber sudah dibatalkan", 'alert' => 'warning']);
        }

        $supplierId = $returnHdr->supplier_id;
        $location   = $returnHdr->location_number;

        $hasilUpdate   = AppHelpers::resetCode($leadCode);
        $replaceNumber = $this->getLastCode($leadCode);

        DB::beginTransaction();
        try {
            DB::table('supplier_replace_hdr')->insert([
                'replace_number'        => $replaceNumber,
                'return_number'         => $returnNumber,
                'replace_date'          => $replaceDate,
                'supplier_id'           => $supplierId,
                'location_number'       => $location,
                'status'                => '1',
                'note'                  => $note,
                'origin_replace_number' => $replaceNumber,
                'created_by'            => $username,
                'updated_by'            => $username,
                'created_at'            => date('Y-m-d H:i:s'),
                'updated_at'            => date('Y-m-d H:i:s'),
            ]);

            $dataSet = [];
            foreach ($articles as $val) {
                $dataSet[] = [
                    'replace_number' => $replaceNumber,
                    'return_number'  => $returnNumber,
                    'article_code'   => $val->article_code,
                    'qty_return'     => $val->qty_return,
                    'qty'            => $val->qty,
                    'uom'            => $val->uom,
                    'created_by'     => $username,
                    'updated_by'     => $username,
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_at'     => date('Y-m-d H:i:s'),
                ];
            }
            DB::table('supplier_replace_det')->insert($dataSet);

            // Posting: tambah stok ke lokasi return + movement masuk
            $this->postingReplace($replaceNumber, $username, $replaceDate, $location, $note, $supplierId, $returnNumber, $replaceNumber);

            // Finalisasi status
            $status = $this->applyReplaceStatus($returnNumber, $username);
            $this->applyReturnStatus($returnNumber, $username);

            DB::commit();

            $title   = "Save $this->title";
            $message = "$title $replaceNumber successfully saved & posted";
            \LogActivity::addToLog($title, "username: $username Status $message");

            return response()->json([
                'status'        => 1,
                'statusReplace' => ($status == '2') ? 'CLOSED' : 'OPEN',
                'title'         => $title,
                'message'       => $message,
                'alert'         => 'success',
                'replaceNumber' => $replaceNumber,
                'idKu'          => Crypt::encryptString(DB::table('supplier_replace_hdr')->where('replace_number', $replaceNumber)->value('id')),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $title   = "Save $this->title";
            $message = "$title $replaceNumber failed: " . $e->getMessage();
            \LogActivity::addToLog($title, "username: $username Status $message");
            return response()->json(['status' => 0, 'statusReplace' => 'FAILED', 'title' => $title, 'message' => $message, 'alert' => 'warning', 'replaceNumber' => $replaceNumber, 'idKu' => '']);
        }
    }

    /* ============================================================
     *  POSTING MOVEMENT (barang MASUK) — pola Supplier Return dibalik
     * ============================================================ */
    private function postingReplace($replaceNumber, $username, $replaceDate, $location, $note, $supplierId, $returnNumber, $excludeReplaceNumber = null)
    {
        $siteCode = $this->siteCode;
        $trType   = $this->mvType;

        $detail = DB::table('supplier_replace_det')
            ->leftJoin('article', 'article.article_code', '=', 'supplier_replace_det.article_code')
            ->where('supplier_replace_det.replace_number', $replaceNumber)
            ->where('supplier_replace_det.qty', '<>', 0)
            ->select(
                'supplier_replace_det.*',
                'article.article_type',
                'article.article_desc',
                'article.uom as uom_article',
                'supplier_replace_det.qty as total_qty'
            )
            ->get();

        $this->lockMovementSequence();
        $seq = (int) DB::table('warehouse_movement')->max('movement_code');
        $movementSet    = [];
        $affectedArticles = [];

        foreach ($detail as $val) {
            if (!$val->article_type) {
                throw new \Exception("Article {$val->article_code} tidak ditemukan di master article");
            }

            $qtyBase = (float) $val->total_qty;
            if ($qtyBase <= 0) {
                continue;
            }

            // Validasi kuota ke RETURN (anti-over)
            $this->assertNotExceedReturn($returnNumber, $val->article_code, $qtyBase, $excludeReplaceNumber);

            // Pastikan baris stok ada (biar recalc tidak silent no-op)
            $this->ensureStockRow($val->article_code, $location);

            // Barang pengganti masuk pakai avg_price lokasi saat ini -> valuasi netral
            $avgLama = (float) (DB::table('warehouse_stock')
                ->where('site_code', $siteCode)
                ->where('article_code', $val->article_code)
                ->where('location_number', $location)
                ->value('avg_price') ?? 0);

            $seq++;
            $movementSet[] = [
                'movement_code'     => $seq,
                'movement_date'     => date('d-m-Y', strtotime($replaceDate)),
                'artikel_code'      => $val->article_code,
                'artikel_desc'      => $val->article_desc ?? '',
                'movement_min'      => 0,
                'movement_plus'     => $qtyBase,      // barang MASUK
                'movement_price'    => $avgLama,
                'movement_transnno' => $replaceNumber,
                'movement_type'     => $trType,       // 'SUPPLIER REPLACE'
                'movement_desc'     => ($note ?? '') . " (Replace Supplier ke lokasi {$location}, dari return {$returnNumber})",
                'movement_from'     => $supplierId,   // dari supplier
                'movement_to'       => $location,     // ke gudang lokasi return
                'partner_type'      => 'SUP',
                'created_by'        => $username,
                'created_at'        => date('Y-m-d H:i:s'),
                'site_code'         => $siteCode,
                'location_number'   => $location,
                // last_qty sementara, ditimpa recalculateMovementAndStock
                'last_qty'          => DB::raw("get_last_qty_new('{$val->article_code}','$replaceDate','$siteCode','$location') + $qtyBase"),
            ];

            $affectedArticles[] = $val->article_code;
        }

        if (!empty($movementSet)) {
            DB::table('warehouse_movement')->insert($movementSet);

            foreach (array_unique($affectedArticles) as $articleCode) {
                $this->recalculateMovementAndStock($articleCode, $location, $replaceDate);
            }
        }
    }

    /* ============================================================
     *  SHOW
     * ============================================================ */
    public function show(Request $request)
    {
        $id = Crypt::decryptString($request->id);
        $data['title']    = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";

        $data['header'] = DB::table('supplier_replace_hdr')
            ->leftJoin('third_party', 'third_party.kode', '=', 'supplier_replace_hdr.supplier_id')
            ->leftJoin('supplier_return_hdr', 'supplier_return_hdr.return_number', '=', 'supplier_replace_hdr.return_number')
            ->where('supplier_replace_hdr.id', $id)
            ->select(
                'supplier_replace_hdr.*',
                'supplier_return_hdr.return_date',
                DB::raw("concat(third_party.kode,'-',third_party.nama) as supplier_name")
            )
            ->first();

        $replaceNumber = $data['header']->replace_number;
        $returnNumber  = $data['header']->return_number;

        $data['details'] = DB::table('supplier_replace_det')
            ->leftJoin('article', 'article.article_code', '=', 'supplier_replace_det.article_code')
            ->where('supplier_replace_det.replace_number', $replaceNumber)
            ->orderBy('supplier_replace_det.id')
            ->select(
                'supplier_replace_det.*',
                'article.article_alternative_code',
                'article.article_desc'
            )
            ->get();

        $status = ['OPEN', 'CLOSED', 'CANCELED'];
        $data['status'] = $status[$data['header']->status - 1] ?? 'UNKNOWN';

        return view("supplierReplace.show", $data);
    }

    /* ============================================================
     *  EDIT
     * ============================================================ */
    public function edit(Request $request)
    {
        $id = Crypt::decryptString($request->id);
        $data['title']    = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['header'] = DB::table('supplier_replace_hdr')
            ->leftJoin('supplier_return_hdr', 'supplier_return_hdr.return_number', '=', 'supplier_replace_hdr.return_number')
            ->where('supplier_replace_hdr.id', $id)
            ->select('supplier_replace_hdr.*', 'supplier_return_hdr.return_date')
            ->first();

        $replaceNumber = $data['header']->replace_number;
        $returnNumber  = $data['header']->return_number;
        $supplierId    = $data['header']->supplier_id;

        // Detail: hitung sisa_qty_return dengan MENGECUALIKAN qty dokumen ini
        // supaya baris ini bisa diedit tanpa dianggap over.
        $data['detail'] = DB::table('supplier_replace_det')
            ->leftJoin('supplier_replace_hdr', 'supplier_replace_hdr.replace_number', '=', 'supplier_replace_det.replace_number')
            ->leftJoin('article', 'article.article_code', '=', 'supplier_replace_det.article_code')
            ->where('supplier_replace_det.replace_number', $replaceNumber)
            ->orderBy('supplier_replace_det.id')
            ->select(
                'supplier_replace_det.*',
                'article.article_alternative_code',
                'article.article_desc',
                DB::raw("(SELECT qty FROM supplier_return_det
                          WHERE return_number = supplier_replace_det.return_number
                          AND article_code = supplier_replace_det.article_code) as tot_qty_return"),
                DB::raw("COALESCE(
                    (SELECT ((SELECT SUM(qty) FROM supplier_return_det
                              WHERE return_number = supplier_replace_hdr.return_number
                              AND article_code = supplier_replace_det.article_code) + supplier_replace_det.qty)
                            - SUM(a.qty)
                     FROM supplier_replace_det a
                     WHERE a.replace_number IN (
                        SELECT replace_number FROM supplier_replace_hdr z
                        WHERE z.status NOT IN ('3') AND z.return_number = supplier_replace_hdr.return_number)
                     AND a.article_code = supplier_replace_det.article_code
                    ), 0) as sisa_qty_return")
            )
            ->get();

        $data['suppliers'] = DB::table('third_party')
            ->where('third_party_type', '=', 'supp')
            ->orderBy('nama')
            ->get();

        $status = ['OPEN', 'CLOSED', 'CANCELED'];
        $data['status'] = $status[$data['header']->status - 1] ?? 'UNKNOWN';

        return view("supplierReplace.edit", $data);
    }

    /* ============================================================
     *  UPDATE (delete movement lama + repost — pola Supplier Return)
     * ============================================================ */
    public function update(Request $request)
    {
        $username      = Auth::user()->username;
        $articles      = json_decode($request->articles);
        $replaceNumber = $request->replaceNumber;
        $replaceDate   = $request->replaceDate;
        $note          = $request->note;

        $validation = Validator::make($request->all(), [
            'replaceNumber' => 'required',
            'replaceDate'   => 'required',
        ]);

        if ($validation->fails()) {
            $error_array = [];
            foreach ($validation->messages()->getMessages() as $field_name => $messages) {
                $error_array[] = $messages;
            }
            return response()->json(['status' => 0, 'title' => "Update $this->title", 'message' => $error_array, 'alert' => 'warning']);
        }

        if (empty($articles)) {
            return response()->json(['status' => 0, 'title' => "Update $this->title", 'message' => 'Article list is empty', 'alert' => 'warning']);
        }

        $hdr = DB::table('supplier_replace_hdr')->where('replace_number', $replaceNumber)->first();
        if (!$hdr) {
            return response()->json(['status' => 0, 'title' => "Update $this->title", 'message' => "Replace $replaceNumber tidak ditemukan", 'alert' => 'warning']);
        }
        if ($hdr->status == '3') {
            return response()->json(['status' => 0, 'title' => "Update $this->title", 'message' => "Replace $replaceNumber sudah dibatalkan, tidak bisa diedit", 'alert' => 'warning', 'replaceNumber' => $replaceNumber]);
        }

        $returnNumber = $hdr->return_number;
        $supplierId   = $hdr->supplier_id;
        $location     = $hdr->location_number;

        DB::beginTransaction();
        try {
            // 1. HAPUS movement lama + recalc stok
            $this->deleteMovementAndRecalc($replaceNumber, $location, $hdr->replace_date);

            // 2. UPDATE header
            DB::table('supplier_replace_hdr')
                ->where('replace_number', $replaceNumber)
                ->update([
                    'replace_date' => $replaceDate,
                    'status'       => '1',
                    'note'         => $note,
                    'updated_by'   => $username,
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]);

            // 3. UPDATE detail (delete not exist + upsert)
            $keepKeys = [];
            foreach ($articles as $val) {
                $keepKeys[] = $replaceNumber . $val->article_code;
            }

            DB::table('supplier_replace_det')
                ->whereNotIn(DB::raw("CONCAT(replace_number, article_code)"), $keepKeys)
                ->where('replace_number', $replaceNumber)
                ->delete();

            foreach ($articles as $val) {
                DB::table('supplier_replace_det')->updateOrInsert(
                    ['replace_number' => $replaceNumber, 'article_code' => $val->article_code],
                    [
                        'return_number' => $returnNumber,
                        'qty_return'    => $val->qty_return,
                        'qty'           => $val->qty,
                        'uom'           => $val->uom,
                        'updated_by'    => $username,
                        'updated_at'    => date('Y-m-d H:i:s'),
                        'created_by'    => $username,
                        'created_at'    => date('Y-m-d H:i:s'),
                    ]
                );
            }

            // 4. RE-POST movement baru
            $this->postingReplace($replaceNumber, $username, $replaceDate, $location, $note, $supplierId, $returnNumber, $replaceNumber);

            // 5. Finalisasi status
            $status = $this->applyReplaceStatus($returnNumber, $username);
            $this->applyReturnStatus($returnNumber, $username);

            DB::commit();

            $title   = "Update $this->title";
            $message = "$title $replaceNumber is successfully updated";
            \LogActivity::addToLog($title, "username: $username Status $message");

            return response()->json([
                'status'        => 1,
                'statusReplace' => ($status == '2') ? 'CLOSED' : 'OPEN',
                'title'         => $title,
                'message'       => $message,
                'alert'         => 'success',
                'replaceNumber' => $replaceNumber,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $title   = "Update $this->title";
            $message = "$title $replaceNumber is failed to update: " . $e->getMessage();
            \LogActivity::addToLog($title, "username: $username Status $message");
            return response()->json(['status' => 0, 'statusReplace' => 'FAILED', 'title' => $title, 'message' => $message, 'alert' => 'warning', 'replaceNumber' => $replaceNumber]);
        }
    }

    /* ============================================================
     *  CANCEL (untuk dokumen CLOSED/OPEN) — movement dihapus
     * ============================================================ */
    public function cancel(Request $request)
    {
        $username = Auth::user()->username;
        $id       = Crypt::decryptString($request->id);
        $reason   = $request->reason;

        $header = DB::table('supplier_replace_hdr')->where('id', $id)->first();
        if (!$header) {
            return redirect()->back()->with(['title' => "Cancel $this->title", 'alert' => 'warning', 'message' => 'Document not found']);
        }
        if ($header->status == '3') {
            return redirect()->back()->with(['title' => "Cancel $this->title", 'alert' => 'warning', 'message' => "$header->replace_number sudah dibatalkan sebelumnya"]);
        }

        $replaceNumber = $header->replace_number;
        $returnNumber  = $header->return_number;
        $location      = $header->location_number;

        DB::beginTransaction();
        try {
            // Hapus movement + recalc
            $this->deleteMovementAndRecalc($replaceNumber, $location, $header->replace_date);

            $reasonNote = "(Cancel by {$username}, Reason: {$reason})";
            $newNote    = trim(($header->note ?? '') . ';' . $reasonNote, ';');

            DB::table('supplier_replace_hdr')
                ->where('replace_number', $replaceNumber)
                ->update([
                    'status'     => '3',
                    'reason'     => $reason,
                    'note'       => $newNote,
                    'updated_by' => $username,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            // Return dibebaskan lagi -> mungkin balik OPEN
            $this->applyReturnStatus($returnNumber, $username);
            $this->applyReplaceStatus($returnNumber, $username);

            DB::commit();
            $title   = "Cancel $this->title";
            $message = "$title $replaceNumber Successfully Canceled";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['title' => $title, 'alert' => 'success', 'message' => $message]);

        } catch (\Exception $e) {
            DB::rollBack();
            $title   = "Cancel $this->title";
            $message = "$title $replaceNumber Failed to Cancel: " . $e->getMessage();
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['title' => $title, 'alert' => 'warning', 'message' => $message]);
        }
    }

    /* ============================================================
     *  DESTROY (hard delete untuk dokumen OPEN) — movement dihapus
     * ============================================================ */
    public function destroy(Request $request)
    {
        $username = Auth::user()->username;
        $id       = Crypt::decryptString($request->id);
        $leadCode = $this->moduleCode;

        $header = DB::table('supplier_replace_hdr')
            ->where('id', $id)
            ->whereNotIn('status', ['3'])
            ->first();

        if (!$header) {
            $title   = "Delete $this->title";
            $message = "$title Failed to Delete — document not found or already canceled";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['alert' => 'warning', 'title' => $title, 'message' => $message]);
        }

        $replaceNumber = $header->replace_number;
        $returnNumber  = $header->return_number;
        $location      = $header->location_number;

        // rollback nomor urut master_code (elemen terakhir hasil explode)
        $parts         = explode('-', $replaceNumber);
        $urutan        = (int) end($parts);
        $urutanSebelum = $urutan - 1;

        DB::beginTransaction();
        try {
            // Hapus movement + recalc
            $this->deleteMovementAndRecalc($replaceNumber, $location, $header->replace_date);

            $rowAffected = DB::table('supplier_replace_hdr')->where('replace_number', $replaceNumber)->delete();

            if ($rowAffected > 0) {
                DB::table('supplier_replace_det')->where('replace_number', $replaceNumber)->delete();

                DB::table('master_code')
                    ->where('code_key', $leadCode)
                    ->where('code_number', $urutan)
                    ->update(['code_number' => $urutanSebelum]);

                $this->applyReturnStatus($returnNumber, $username);
                $this->applyReplaceStatus($returnNumber, $username);

                DB::commit();
                $title   = "Delete $this->title";
                $message = "$title $replaceNumber Successfully Deleted";
                \LogActivity::addToLog($title, "username: $username Status $message");
                return redirect()->back()->with(['alert' => 'success', 'title' => $title, 'message' => $message]);
            }

            DB::rollBack();
            $title   = "Delete $this->title";
            $message = "$title $replaceNumber Failed to Delete";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['alert' => 'warning', 'title' => $title, 'message' => $message]);

        } catch (\Exception $e) {
            DB::rollBack();
            $title   = "Delete $this->title";
            $message = "$title $replaceNumber Failed to Delete: " . $e->getMessage();
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['alert' => 'warning', 'title' => $title, 'message' => $message]);
        }
    }

    /* ============================================================
     *  REVISION — dokumen lama di-CANCEL (movement dihapus), dibuat
     *  dokumen baru "-R<n>", diposting ulang. Yang aktif = terbaru.
     * ============================================================ */
    public function revision(Request $request)
    {
        $username = Auth::user()->username;
        $id       = Crypt::decryptString($request->id);
        $reason   = $request->reason;

        $original = DB::table('supplier_replace_hdr')->where('id', $id)->first();
        if (!$original) {
            return redirect()->back()->with(['title' => "Revision $this->title", 'alert' => 'warning', 'message' => 'Document not found']);
        }
        if ($original->status == '3') {
            return redirect()->back()->with(['title' => "Revision $this->title", 'alert' => 'warning', 'message' => 'Canceled document cannot be revised']);
        }

        $recOrigin    = $original->replace_number;
        $trueOrigin   = $original->origin_replace_number ?: $recOrigin;
        $returnNumber = $original->return_number;
        $supplierId   = $original->supplier_id;
        $location     = $original->location_number;
        $note         = $original->note;

        // Nomor revisi berikut
        $numRevision = DB::table('supplier_replace_hdr')
            ->where('origin_replace_number', $trueOrigin)
            ->count();
        $recNew = $trueOrigin . '-R' . $numRevision;
        while (DB::table('supplier_replace_hdr')->where('replace_number', $recNew)->exists()) {
            $numRevision++;
            $recNew = $trueOrigin . '-R' . $numRevision;
        }

        $detailOriginal = DB::table('supplier_replace_det')->where('replace_number', $recOrigin)->get();

        DB::beginTransaction();
        try {
            // 1. Hapus movement dokumen asal
            $this->deleteMovementAndRecalc($recOrigin, $location, $original->replace_date);

            // 2. Header baru (status sementara OPEN)
            $noteBaru = trim(($note ?? '') . "; Revision of {$recOrigin}" . ($reason ? ", reason: {$reason}" : ''), '; ');

            $newId = DB::table('supplier_replace_hdr')->insertGetId([
                'replace_number'        => $recNew,
                'return_number'         => $returnNumber,
                'replace_date'          => $original->replace_date,
                'supplier_id'           => $supplierId,
                'location_number'       => $location,
                'status'                => '1',
                'note'                  => $noteBaru,
                'origin_replace_number' => $trueOrigin,
                'created_by'            => $username,
                'updated_by'            => $username,
                'created_at'            => date('Y-m-d H:i:s'),
                'updated_at'            => date('Y-m-d H:i:s'),
            ]);

            // 3. Copy detail
            $dataSet = [];
            foreach ($detailOriginal as $val) {
                $dataSet[] = [
                    'replace_number' => $recNew,
                    'return_number'  => $val->return_number,
                    'article_code'   => $val->article_code,
                    'qty_return'     => $val->qty_return,
                    'qty'            => $val->qty,
                    'uom'            => $val->uom,
                    'created_by'     => $username,
                    'updated_by'     => $username,
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_at'     => date('Y-m-d H:i:s'),
                ];
            }
            if (!empty($dataSet)) {
                DB::table('supplier_replace_det')->insert($dataSet);
            }

            // 4. Dokumen asal -> CANCELED (dilakukan SEBELUM posting baru supaya
            //    assertNotExceedReturn tidak menghitung dokumen asal).
            DB::table('supplier_replace_hdr')
                ->where('replace_number', $recOrigin)
                ->update([
                    'status'     => '3',
                    'reason'     => $reason,
                    'note'       => trim(($note ?? '') . "; Superseded by revision {$recNew}" . ($reason ? ", reason: {$reason}" : ''), '; '),
                    'updated_by' => $username,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            // 5. Posting dokumen baru
            $this->postingReplace($recNew, $username, $original->replace_date, $location, $noteBaru, $supplierId, $returnNumber, $recNew);

            // 6. Finalisasi status
            $this->applyReplaceStatus($returnNumber, $username);
            $this->applyReturnStatus($returnNumber, $username);

            DB::commit();

            $title   = "Revision $this->title";
            $message = "$title: $recOrigin successfully revised to $recNew";
            \LogActivity::addToLog($title, "username: $username Status $message");

            return redirect()->route('supplierReplace.edit', ['id' => Crypt::encryptString($newId)])
                ->with(['title' => $title, 'alert' => 'success', 'message' => $message]);

        } catch (\Exception $e) {
            DB::rollBack();
            $title   = "Revision $this->title";
            $message = "$title: $recOrigin failed to revise: " . $e->getMessage();
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['title' => $title, 'alert' => 'warning', 'message' => $message]);
        }
    }

    /* ============================================================
     *  MOVEMENT ENGINE (copy dari SupplierReturnController)
     * ============================================================ */
    private function lockMovementSequence(): void
    {
        DB::select("SELECT pg_advisory_xact_lock(hashtext('warehouse_movement_code'))");
    }

    private function ensureStockRow(string $articleCode, string $location): void
    {
        $exists = DB::table('warehouse_stock')
            ->where('site_code', $this->siteCode)
            ->where('article_code', $articleCode)
            ->where('location_number', $location)
            ->exists();

        if (!$exists) {
            $article = DB::table('article')
                ->where('article_code', $articleCode)
                ->select('article_type', 'uom')
                ->first();

            DB::table('warehouse_stock')->insert([
                'site_code'       => $this->siteCode,
                'article_code'    => $articleCode,
                'location_number' => $location,
                'article_qty'     => 0,
                'avg_price'       => 0,
                'dept_code'       => $article->article_type ?? '',
                'uom'             => $article->uom ?? '',
            ]);
        }
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

        $qty = 0.0;
        $avg = 0.0;

        foreach ($movements as $m) {
            $plus = (float) $m->movement_plus;
            $min  = (float) $m->movement_min;

            if ($plus > 0) {
                $price  = (float) $m->movement_price;
                $newQty = $qty + $plus;
                $avg    = $newQty > 0 ? (($qty * $avg) + ($plus * $price)) / $newQty : $avg;
                $qty    = $newQty;
            }
            if ($min > 0) {
                $qty -= $min;
            }
        }

        DB::table('warehouse_stock')
            ->where('site_code', $this->siteCode)
            ->where('article_code', $articleCode)
            ->where('location_number', $location)
            ->update(['avg_price' => $avg]);
    }

    private function updateWarehouseStock(string $articleCode, string $location, float $qty): void
    {
        DB::table('warehouse_stock')
            ->where('site_code', $this->siteCode)
            ->where('article_code', $articleCode)
            ->where('location_number', $location)
            ->update(['article_qty' => $qty]);
    }

    private function recalculateMovementAndStock(string $articleCode, string $location, string $fromDate): void
    {
        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $fromDate)) {
            $fromDate = \Carbon\Carbon::createFromFormat('d-m-Y', $fromDate)->format('Y-m-d');
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) {
            // sudah benar
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
            $running = $running - (float) $mov->movement_min + (float) $mov->movement_plus;
            DB::table('warehouse_movement')
                ->where('movement_code', $mov->movement_code)
                ->update(['last_qty' => $running]);
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

    private function deleteMovementAndRecalc(string $replaceNumber, string $location, string $replaceDate): array
    {
        $affectedArticles = DB::table('warehouse_movement')
            ->where('movement_transnno', $replaceNumber)
            ->where('location_number', $location)
            ->where('site_code', $this->siteCode)
            ->pluck('artikel_code')
            ->unique()
            ->values()
            ->all();

        DB::table('warehouse_movement')
            ->where('movement_transnno', $replaceNumber)
            ->where('location_number', $location)
            ->where('site_code', $this->siteCode)
            ->delete();

        foreach ($affectedArticles as $articleCode) {
            $this->recalculateMovementAndStock($articleCode, $location, $replaceDate);
        }

        return $affectedArticles;
    }

    /* ============================================================
     *  LIST / LIST DETAIL
     * ============================================================ */
    public function list(Request $request)
    {
        $searchReplace  = strtolower($request->searchReplace);
        $searchReturn   = strtolower($request->searchReturn);
        $searchStatus   = $request->searchStatus;
        $replaceDate    = $request->replaceDate;
        $searchSupplier = $request->searchSupplier;
        $fromDate = null;
        $toDate   = null;

        if ($replaceDate) {
            $parts   = explode("to", $replaceDate);
            $rawFrom = trim($parts[0]);
            $rawTo   = isset($parts[1]) ? trim($parts[1]) : $rawFrom;
            try {
                $fromDate = $this->toIsoDate($rawFrom);
                $toDate   = $this->toIsoDate($rawTo);
            } catch (\Exception $e) {
                return response()->json(['draw' => (int) $request->draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => $e->getMessage()]);
            }
        }

        $data = DB::table('supplier_replace_hdr')
            ->leftJoin('third_party', 'third_party.kode', '=', 'supplier_replace_hdr.supplier_id')
            ->leftJoin('supplier_return_hdr', 'supplier_return_hdr.return_number', '=', 'supplier_replace_hdr.return_number')
            ->leftJoin('stock_location_master', 'stock_location_master.location_code', '=', 'supplier_replace_hdr.location_number')
            ->where(function ($query) use ($searchReplace, $searchReturn, $searchStatus, $replaceDate, $fromDate, $toDate, $searchSupplier) {
                $searchReplace  ? $query->where('supplier_replace_hdr.replace_number', 'ilike', '%' . $searchReplace . '%') : '';
                $searchReturn   ? $query->where('supplier_replace_hdr.return_number', 'ilike', '%' . $searchReturn . '%') : '';
                $searchStatus   ? $query->where('supplier_replace_hdr.status', $searchStatus) : '';
                $searchSupplier ? $query->where('supplier_replace_hdr.supplier_id', $searchSupplier) : '';
                $replaceDate    ? $query->whereBetween(DB::raw("to_date(supplier_replace_hdr.replace_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
            })
            ->whereNotIn('supplier_replace_hdr.status', ['3'])
            ->select(
                'supplier_replace_hdr.*',
                'supplier_return_hdr.return_date',
                'third_party.nama as supplier_name',
                'stock_location_master.location_name as location_name'
            )
            ->orderBy('supplier_replace_hdr.id')
            ->get();

        return Datatables::of($data)
            ->addColumn('action', function ($data) {
                $buttons  = '<div class="d-inline-flex"><a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown"><i data-feather="menu"></i></a>';
                $buttons .= '<div class="dropdown-menu dropdown-menu-right">';

                if ($data->status == '1') {
                    $buttons .= '<a href="' . route('supplierReplace.edit', ['id' => Crypt::encryptString($data->id)]) . '" class="dropdown-item"><i data-feather="file-text"></i> Edit</a>';
                }

                $buttons .= '<a href="' . route('supplierReplace.show', ['id' => Crypt::encryptString($data->id)]) . '" class="dropdown-item"><i data-feather="list"></i> Detail</a>';
                $buttons .= '<a href="' . route('supplierReplace.print', ['id' => Crypt::encryptString($data->id)]) . '" target="_blank" class="dropdown-item"><i data-feather="printer"></i> Print</a>';

                // Revision untuk dokumen aktif
                $buttons .= "<a href='javascript:;' id='revisionReasonButton' class='dropdown-item' data-toggle='modal' data-target='#reasonModalRevision' data-href='" . route('supplierReplace.revision', ['id' => Crypt::encryptString($data->id)]) . "'><i data-feather='repeat' class='feather-14'></i> <span>" . __('Revision') . "</span></a>";

                if ($data->status == '2') {
                    // CLOSED -> cancel
                    $buttons .= "<a href='javascript:;' id='cancelReasonButton' class='dropdown-item' data-toggle='modal' data-target='#reasonModalCancel' data-href='" . route('supplierReplace.cancel', ['id' => Crypt::encryptString($data->id)]) . "'><i data-feather='corner-down-left' class='feather-14-red'></i> <span>" . __('Cancel') . "</span></a>";
                }

                if ($data->status == '1') {
                    // OPEN -> hard delete
                    $buttons .= "<a href='javascript:;' class='dropdown-item' data-size='sm' data-ajax-delete='true' data-confirm='Are You Sure want to Delete?|This action can not be undone. Do you want to continue?' data-confirm-yes='document.getElementById(\"delete-form-" . $data->id . "\").submit();' data-modal-id='" . $data->id . "' data-url='" . route('supplierReplace.destroy', ['id' => Crypt::encryptString($data->id)]) . "'><i data-feather='trash-2' class='feather-14-red'></i> <span>" . __('Delete') . "</span></a>";
                }

                $buttons .= '</div></div>';
                return $buttons;
            })
            ->addColumn('status', function ($data) {
                $badges   = ['badge-primary', 'badge-success', 'badge-danger'];
                $statusPr = ['OPEN', 'CLOSED', 'CANCELED'];
                return "<div class='badge " . $badges[$data->status - 1] . "'>" . $statusPr[$data->status - 1] . "</div>";
            })
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    public function listDetail(Request $request)
    {
        $searchReplace  = strtolower($request->searchReplace);
        $searchReturn   = strtolower($request->searchReturn);
        $searchStatus   = $request->searchStatus;
        $replaceDate    = $request->replaceDate;
        $searchSupplier = $request->searchSupplier;
        $fromDate = null;
        $toDate   = null;

        if ($replaceDate) {
            $parts   = explode("to", $replaceDate);
            $rawFrom = trim($parts[0]);
            $rawTo   = isset($parts[1]) ? trim($parts[1]) : $rawFrom;
            try {
                $fromDate = $this->toIsoDate($rawFrom);
                $toDate   = $this->toIsoDate($rawTo);
            } catch (\Exception $e) {
                return response()->json(['draw' => (int) $request->draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => $e->getMessage()]);
            }
        }

        $data = DB::table('supplier_replace_det')
            ->leftJoin('supplier_replace_hdr', 'supplier_replace_hdr.replace_number', '=', 'supplier_replace_det.replace_number')
            ->leftJoin('supplier_return_hdr', 'supplier_return_hdr.return_number', '=', 'supplier_replace_det.return_number')
            ->leftJoin('article', 'article.article_code', '=', 'supplier_replace_det.article_code')
            ->leftJoin('third_party', 'third_party.kode', '=', 'supplier_replace_hdr.supplier_id')
            ->where(function ($query) use ($searchReplace, $searchReturn, $searchStatus, $replaceDate, $fromDate, $toDate, $searchSupplier) {
                $searchReplace  ? $query->where('supplier_replace_hdr.replace_number', 'ilike', '%' . $searchReplace . '%') : '';
                $searchReturn   ? $query->where('supplier_replace_hdr.return_number', 'ilike', '%' . $searchReturn . '%') : '';
                $searchStatus   ? $query->where('supplier_replace_hdr.status', $searchStatus) : '';
                $searchSupplier ? $query->where('supplier_replace_hdr.supplier_id', $searchSupplier) : '';
                $replaceDate    ? $query->whereBetween(DB::raw("to_date(supplier_replace_hdr.replace_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
            })
            ->where('supplier_replace_det.qty', '>', 0)
            ->whereNotIn('supplier_replace_hdr.status', ['3'])
            ->select(
                'supplier_replace_det.replace_number',
                'supplier_replace_det.return_number',
                'supplier_replace_det.article_code',
                'supplier_replace_det.qty',
                'supplier_replace_det.uom',
                'supplier_replace_hdr.note',
                'supplier_replace_hdr.supplier_id',
                'supplier_replace_hdr.replace_date',
                'supplier_replace_hdr.status',
                'supplier_replace_hdr.created_by',
                'supplier_replace_hdr.created_at',
                'supplier_return_hdr.return_date',
                'third_party.nama as supplier_name',
                'article.article_alternative_code',
                'article.article_desc',
                DB::raw("(SELECT qty FROM supplier_return_det
                          WHERE return_number = supplier_replace_det.return_number
                          AND article_code = supplier_replace_det.article_code LIMIT 1) as qty_return"),
                DB::raw("(SELECT qty FROM supplier_return_det
                          WHERE return_number = supplier_replace_det.return_number
                          AND article_code = supplier_replace_det.article_code LIMIT 1)
                         - COALESCE((
                            SELECT SUM(d.qty) FROM supplier_replace_det d
                            JOIN supplier_replace_hdr h ON h.replace_number = d.replace_number
                            WHERE d.return_number = supplier_replace_det.return_number
                            AND d.article_code = supplier_replace_det.article_code
                            AND h.status <> '3'
                         ), 0) as sisa_qty_return")
            )
            ->orderBy('supplier_replace_det.id')
            ->get();

        return Datatables::of($data)
            ->editColumn('qty', function ($data) {
                return number_format((float) $data->qty, 2, '.', '');
            })
            ->addColumn('status', function ($data) {
                $badges   = ['badge-primary', 'badge-success', 'badge-danger'];
                $statusPr = ['OPEN', 'CLOSED', 'CANCELED'];
                return "<div class='badge " . $badges[$data->status - 1] . "'>" . $statusPr[$data->status - 1] . "</div>";
            })
            ->rawColumns(['status'])
            ->make(true);
    }

    /* ============================================================
     *  AJAX: daftar return OPEN untuk supplier + detail artikel + sisa
     * ============================================================ */

    /** Return supplier yang masih OPEN (status 1) untuk supplier terpilih. */
    public function listReturn(Request $request)
    {
        $supplier = $request->value;
        $output   = "";

        $data = DB::table('supplier_return_hdr')
            ->where('supplier_id', $supplier)
            ->where('status', '1')            // hanya yang belum tergganti penuh
            ->orderBy('return_number')
            ->select('return_number', 'return_date')
            ->get();

        if (count($data) > 0) {
            $output .= '<option value="">Choose Return</option>';
            foreach ($data as $row) {
                $output .= '<option value="' . $row->return_number . '" data-date="' . $row->return_date . '">' . $row->return_number . '</option>';
            }
        } else {
            $output .= '<option value="">No open return</option>';
        }

        return $output;
    }

    /** Detail artikel dari return + tot qty return + sisa qty return per artikel. */
    public function returnDetail(Request $request)
    {
        $returnNumber = $request->value;

        $data = DB::select("
            SELECT
                rd.article_code,
                a.article_alternative_code,
                a.article_desc,
                COALESCE(rd.qty,0) as tot_qty_return,
                COALESCE(rd.qty,0) - COALESCE(b.qty,0) as sisa_qty_return,
                rd.uom,
                rd.return_number
            FROM supplier_return_det rd
            LEFT JOIN article a ON a.article_code = rd.article_code
            LEFT JOIN (
                SELECT SUM(pd.qty) as qty, pd.return_number, pd.article_code
                FROM supplier_replace_det pd
                JOIN supplier_replace_hdr ph ON ph.replace_number = pd.replace_number
                WHERE pd.return_number = ?
                AND ph.status <> '3'
                GROUP BY pd.return_number, pd.article_code
            ) b ON b.article_code = rd.article_code AND b.return_number = rd.return_number
            WHERE rd.return_number = ?
            ORDER BY rd.id
        ", [$returnNumber, $returnNumber]);

        return response()->json($data);
    }

    /* ============================================================
     *  PRINT
     * ============================================================ */
    public function print(Request $request)
    {
        $id = Crypt::decryptString($request->id);

        $tHdr = DB::table('supplier_replace_hdr')
            ->leftJoin('supplier_return_hdr', 'supplier_return_hdr.return_number', '=', 'supplier_replace_hdr.return_number')
            ->where('supplier_replace_hdr.id', $id)
            ->select('supplier_replace_hdr.*', 'supplier_return_hdr.return_date')
            ->first();

        if (!$tHdr) {
            abort(404, "Supplier Replace tidak ditemukan");
        }

        $replaceNumber = $tHdr->replace_number;

        $data['title']    = "Print $this->title";
        $data['subtitle'] = "Print $this->title";
        $data['tHdr']     = $tHdr;

        $data['details'] = DB::table('supplier_replace_det')
            ->leftJoin('article', 'article.article_code', '=', 'supplier_replace_det.article_code')
            ->where('supplier_replace_det.replace_number', $replaceNumber)
            ->where('supplier_replace_det.qty', '>', 0)
            ->select(
                'supplier_replace_det.article_code',
                'article.article_alternative_code',
                'article.article_desc',
                'supplier_replace_det.qty',
                'supplier_replace_det.uom'
            )
            ->orderBy('supplier_replace_det.id')
            ->get();

        $data['tDnNumber'] = $replaceNumber;
        $data['tDnDate']   = $tHdr->replace_date;
        $data['tDnNote']   = $tHdr->note;

        $statusPr = ['OPEN', 'CLOSED', 'CANCELED'];
        $data['status'] = $statusPr[$tHdr->status - 1] ?? 'UNKNOWN';
        $data['no'] = 0;

        $data['suppliers'] = DB::table('third_party')
            ->where('third_party_type', '=', 'supp')
            ->where('kode', $tHdr->supplier_id)
            ->first();

        return view('supplierReplace.print', $data);
    }
}