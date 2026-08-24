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
use Approval;

class SupplierReturnController extends Controller
{
    private $title;
    private $moduleCode;
    private $decimalPlaces;
    private $siteCode;
    private $mvType;

    public function __construct()
    {
        $this->title      = "Supplier Return";
        $this->moduleCode = "REC-RETURN";
        $this->decimalPlaces = config('globalParam.decimal');
        $this->siteCode   = 'HO';
        $this->mvType     = 'SUPPLIER RETURN'; // dipakai konsisten di store/update/destroy
    }

    /**
     * Konversi input tanggal dari date-range-picker (format "dd-mm-yyyy" atau "dd/mm/yyyy")
     * ke format ISO 'Y-m-d'.
     */
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

    public function getTableColoumn()
    {
        $kolom =
        [
            ['data'=>'action','name'=>'action','title'=>'Action','orderable'=>false,'searchable'=>false],
            ['data'=>'return_number','name'=>'return_number','title'=>'Return Number'],
            ['data'=>'po_number','name'=>'po_number','title'=>'PO Number'],
            ['data'=>'location_number','name'=>'location_number','title'=>'Location'],
            ['data'=>'return_date','name'=>'return_date','title'=>'Return Date'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'supplier_id','name'=>'supplier_id','title'=>'Supplier Code'],
            ['data'=>'supplier_name','name'=>'supplier_name','title'=>'Supplier'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created Date'],
            ['data'=>'updated_by','name'=>'updated_by','title'=>'Updated By'],
            ['data'=>'updated_at','name'=>'updated_at','title'=>'Updated Date'],
        ];

        return json_encode($kolom, true);
    }

    public function getTableColoumnDetail()
    {
        $kolom =
        [
            ['data'=>'return_number','name'=>'return_number','title'=>'Return Number'],
            ['data'=>'po_number','name'=>'po_number','title'=>'PO Number'],
            ['data'=>'location_number','name'=>'location_number','title'=>'Location'],
            ['data'=>'return_date','name'=>'return_date','title'=>'Return Date'],
            ['data'=>'supplier_id','name'=>'supplier_id','title'=>'Supplier Code'],
            ['data'=>'supplier_name','name'=>'supplier_name','title'=>'Supplier'],
            ['data'=>'article_alternative_code','name'=>'article_alternative_code','title'=>'Article Code'],
            ['data'=>'article_desc','name'=>'article_desc','title'=>'Description'],
            ['data'=>'qty','name'=>'qty','title'=>'Qty Return'],
            ['data'=>'uom','name'=>'uom','title'=>'UOM'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created Date'],
        ];
        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $data['title']  = "$this->title";
        $data['kolom']  = $this->getTableColoumn();
        $data['kolomDetail'] = $this->getTableColoumnDetail();
        $data['status'] = ['1'=>'OPEN','3'=>'CLOSED'];

        $data['suppliers'] = DB::table('third_party')
            ->where('third_party_type', '=', 'sup')
            ->orderBy('nama')
            ->get();

        // Master lokasi -> sesuaikan nama tabel/kolom kalau berbeda
       $data['locations'] = DB::table('stock_location_master')
    ->orderBy('location_name')
    ->select('location_code as location_number', 'location_name')   // alias biar view tidak perlu diubah
    ->get();

        return view("supplierReturn.index", $data);
    }

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

    /**
     * Cek apakah supplier return ini sudah punya Supplier Replace aktif.
     * (kalau modul Replace-nya dibuat menyusul, sama seperti dn_replace_hdr)
     */
    private function punyaReplaceAktif($returnNumber)
    {
        return DB::table('supplier_replace_hdr')
            ->where('return_number', $returnNumber)
            ->whereNotIn('status', ['3'])
            ->exists();
    }

    public function create(Request $request)
    {
        $data['title']    = "Create $this->title";
        $data['subtitle'] = "Create $this->title";

        $data['suppliers'] = DB::table('third_party')
            ->where('third_party_type', '=', 'sup')
            ->orderBy('nama')
            ->get();

        $data['locations'] = DB::table('stock_location_master')
    ->orderBy('location_name')
    ->select('location_code as location_number', 'location_name')
    ->get();

        $data['currentDate'] = date('d-m-Y');

        return view("supplierReturn.create", $data);
    }

    public function store(Request $request)
{
    $username    = Auth::user()->username;
    $articles    = json_decode($request->articles);
    $supplierId  = $request->supplierId;
    $returnDate  = $request->returnDate;
    $poNumber    = $request->poNumber; // opsional, tidak dipakai untuk validasi apapun
    $location    = $request->locationNumber;
    $note        = $request->note;
    $status      = '1';
    $returnNumber = '';
    $leadCode    = $this->moduleCode;

    $siteCode  = $this->siteCode;
    $trType    = $this->mvType;

    Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
        $query  = DB::table($parameters[0]);
        $column = $query->getGrammar()->wrap($parameters[1]);
        return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
    });

    // PO Number sengaja TIDAK divalidasi/required — modul ini tidak bergantung ke PO
    $validation = Validator::make($request->all(), [
        'returnDate'     => 'required',
        'supplierId'     => 'required',
        'locationNumber' => 'required',
    ], ['required' => 'The field is required.']);

    $error_array = [];
    if ($validation->fails()) {
        foreach ($validation->messages()->getMessages() as $field_name => $messages) {
            $error_array[] = $messages;
        }
        return response()->json(['status' => 0, 'message' => $error_array, 'alert' => 'warning']);
    }

    // ── VALIDASI QTY vs STOK: DIBOLEHKAN OVER / MINUS (misal ada kasus stok fisik belum sinkron / retur mendahului adjustment) ──
    // Kalau suatu saat mau dikunci lagi, aktifkan blok ini:
    // $qtyErrors = $this->checkStockQty($articles, $location, null);
    // if (!empty($qtyErrors)) {
    //     return response()->json(['status' => 0, 'message' => $qtyErrors, 'alert' => 'warning']);
    // }

    $hasilUpdate  = AppHelpers::resetCode($leadCode);
    $returnNumber = $this->getLastCode($leadCode);

    DB::beginTransaction();
    try {
        DB::table('supplier_return_hdr')->insert([
            'return_number'        => $returnNumber,
            'supplier_id'          => $supplierId,
            'return_date'          => $returnDate,
            'note'                 => $note,
            'origin_return_number' => $returnNumber,
            'status'               => $status,
            'po_number'            => $poNumber, // tetap disimpan kalau kebetulan diisi manual, tapi tidak wajib & tidak divalidasi
            'location_number'      => $location,
            'created_by'           => $username,
            'updated_by'           => $username,
            'created_at'           => date('Y-m-d H:i:s'),
            'updated_at'           => date('Y-m-d H:i:s'),
        ]);

        $dataSet = [];
        foreach ($articles as $val) {
            $dataSet[] = [
                'return_number' => $returnNumber,
                'article_code'  => $val->article_code,
                'qty'           => $val->qty,
                'uom'           => $val->uom,
                'created_by'    => $username,
                'updated_by'    => $username,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ];
        }
        DB::table('supplier_return_det')->insert($dataSet);

        $this->postingReturn($returnNumber, $username, $returnDate, $location, $note, $supplierId, $poNumber);

        DB::commit();
        $title   = "Save $this->title";
        $alert   = "success";
        $message = "$title $returnNumber is successfully saved & posted";
        \LogActivity::addToLog($title, "username: $username Status $message");
        return response()->json(['status' => 1, 'title' => $title, 'message' => $message, 'alert' => $alert, 'returnNumber' => $returnNumber]);

    } catch (\Exception $e) {
        DB::rollBack();
        $title   = "Save $this->title";
        $alert   = "warning";
        $message = "$title $returnNumber is failed to saved: " . $e->getMessage();
        \LogActivity::addToLog($title, "username: $username Status $message");
        return response()->json(['status' => 0, 'title' => $title, 'message' => $message, 'alert' => $alert, 'returnNumber' => $returnNumber]);
    }
}

    /**
     * Validasi qty retur terhadap stok yang tersedia di lokasi asal.
     * $excludeReturn: nomor return yang sedang diedit (agar stok yang sudah
     * "dikeluarkan" oleh dokumen ini sendiri dikembalikan dulu ke perhitungan).
     */
    private function checkStockQty($articles, $location, $excludeReturn = null)
    {
        $errors = [];
        $siteCode = $this->siteCode;

        foreach ($articles as $val) {
            $articleInfo = DB::table('article')
                ->where('article_code', $val->article_code)
                ->select('article_desc', 'article_alternative_code')
                ->first();

            if (!$articleInfo) {
                $errors[] = "Article {$val->article_code} tidak ditemukan";
                continue;
            }

            if ($val->qty <= 0) {
                $errors[] = "Qty {$articleInfo->article_alternative_code} harus lebih dari 0";
                continue;
            }

            $stokTersedia = (float) (DB::table('warehouse_stock')
                ->where('site_code', $siteCode)
                ->where('article_code', $val->article_code)
                ->where('location_number', $location)
                ->value('article_qty') ?? 0);

            // kalau sedang edit, qty lama dari dokumen ini sudah "keluar" -> kembalikan dulu
            if ($excludeReturn) {
                $qtyLama = (float) DB::table('supplier_return_det')
                    ->where('return_number', $excludeReturn)
                    ->where('article_code', $val->article_code)
                    ->value('qty');
                $stokTersedia += $qtyLama;
            }

            if ($val->qty > $stokTersedia) {
                $errors[] = "Retur {$articleInfo->article_alternative_code} - {$articleInfo->article_desc} melebihi stok di lokasi {$location} (tersedia: {$stokTersedia}, diminta: {$val->qty})";
            }
        }

        return $errors;
    }

    /**
     * Kurangi stok di lokasi asal + catat movement keluar (barang dikirim ke supplier).
     */
    private function postingReturn($returnNumber, $username, $returnDate, $location, $note, $supplierId, $poNumber)
{
    $siteCode  = $this->siteCode;
    $trType    = $this->mvType;

    $detail = DB::table('supplier_return_det')
        ->leftJoin('article', 'article.article_code', '=', 'supplier_return_det.article_code')
        ->where('supplier_return_det.return_number', $returnNumber)
        ->where('supplier_return_det.qty', '<>', 0)
        ->select(
            'supplier_return_det.*',
            'article.article_type',
            'article.article_desc',
            'article.uom as uom_article',
            'supplier_return_det.qty as total_qty'
        )
        ->get();

    $this->lockMovementSequence();   // ← ganti dari max() polos
    $seq = (int) DB::table('warehouse_movement')->max('movement_code');
    $movementSet = [];

    foreach ($detail as $val) {
        if (!$val->article_type) {
            throw new \Exception("Article {$val->article_code} tidak ditemukan di master article");
        }

        $qtyBase = (float) $val->total_qty;

        $avgLama = (float) (DB::table('warehouse_stock')
            ->where('site_code', $siteCode)
            ->where('article_code', $val->article_code)
            ->where('location_number', $location)
            ->value('avg_price') ?? 0);

        $seq++;
        $movementSet[] = [
            'movement_code'     => $seq,
            'movement_date'     => date('d-m-Y', strtotime($returnDate)),
            'artikel_code'      => $val->article_code,
            'artikel_desc'      => $val->article_desc ?? '',
            'movement_min'      => $qtyBase,
            'movement_plus'     => 0,
            'movement_price'    => $avgLama,
            'movement_transnno' => $returnNumber,
            'movement_type'     => $trType,
            'movement_desc'     => ($note ?? '') . " (Retur Supplier dari lokasi {$location})",
            'movement_from'     => $location,
            'movement_to'       => $supplierId,
            'partner_type'      => 'SUP',
            'created_by'        => $username,
            'created_at'        => date('Y-m-d H:i:s'),
            'site_code'         => $siteCode,
            'location_number'   => $location,
            // last_qty sementara, ditimpa recalculateMovementAndStock
            'last_qty'          => DB::raw("get_last_qty_new('{$val->article_code}','$returnDate','$siteCode','$location') - $qtyBase"),
        ];
    }

    if (!empty($movementSet)) {
        DB::table('warehouse_movement')->insert($movementSet);

        // ── Sinkronkan article_qty & avg_price via recalculate (bukan raw update lagi) ──
        $articles = array_unique(array_column($movementSet, 'artikel_code'));
        foreach ($articles as $articleCode) {
            $this->recalculateMovementAndStock($articleCode, $location, $returnDate);
        }
    }
}

    public function show(Request $request)
    {
        $id = Crypt::decryptString($request->id);
        $data['title']    = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";

        $data['headers'] = DB::table('supplier_return_hdr')
            ->leftJoin('third_party', 'third_party.kode', '=', 'supplier_return_hdr.supplier_id')
            ->where('origin_return_number', function ($query) use ($id) {
                $query->select('return_number')->from('supplier_return_hdr')->where('id', $id);
            })
            ->where('supplier_return_hdr.status', '!=', '4')
            ->select(
                'supplier_return_hdr.*',
                DB::raw('(select sum(qty) from supplier_return_det where return_number = supplier_return_hdr.return_number) as sum_qty'),
                DB::raw('(select count(*) from supplier_return_det where return_number = supplier_return_hdr.return_number) as sum_row'),
                DB::raw("concat(kode,'-',nama) as supplier_name")
            )
            ->orderBy('id')
            ->get();

        $returnNumber = $data['headers'][0]->return_number;

        $data['details'] = DB::table('supplier_return_det')
            ->whereIn('supplier_return_det.return_number', function ($query) use ($returnNumber) {
                $query->select('return_number')->from('supplier_return_hdr')->where('origin_return_number', $returnNumber);
            })
            ->leftJoin('article', 'article.article_code', '=', 'supplier_return_det.article_code')
            ->select(
                'supplier_return_det.*',
                DB::raw("concat(article_alternative_code,'-',article_desc) as article")
            )
            ->orderBy('supplier_return_det.return_number')
            ->orderBy('supplier_return_det.id')
            ->get();

        $data['suppliers'] = DB::table('third_party')
            ->where('third_party_type', '=', 'sup')
            ->orderBy('nama')
            ->get();

        $status = ['OPEN', '', 'CLOSED', 'CANCELED'];
        $data['status'] = $status[$data['headers'][0]->status - 1];

        return view("supplierReturn.show", $data);
    }

    public function edit(Request $request)
    {
        $id = Crypt::decryptString($request->id);
        $data['title']    = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['header'] = DB::table('supplier_return_hdr')->where('id', $id)->first();

        $returnNumber = $data['header']->return_number;
        $supplierCode = $data['header']->supplier_id;

        $data['details'] = DB::table('supplier_return_det')
            ->where('return_number', $returnNumber)
            ->orderBy('id')
            ->get();

        // article difilter dari yang tersedia stoknya di lokasi tsb
        $dataQuery = DB::table('article')
            ->where('third_party', $supplierCode)
            ->orderBy('article_desc')
            ->get();

        $output = '<option value="">Choose article</option>';
        foreach ($dataQuery as $row) {
            $output .= '<option value="' . $row->article_code . '" data-uom="' . $row->uom . '">' . $row->article_alternative_code . '-' . $row->article_desc . '</option>';
        }
        $data['articles'] = $output;

        $data['suppliers'] = DB::table('third_party')
            ->where('third_party_type', '=', 'sup')
            ->orderBy('nama')
            ->get();

       $data['locations'] = DB::table('stock_location_master')
    ->orderBy('location_name')
    ->select('location_code as location_number', 'location_name')
    ->get();

        $status = ['OPEN', '', 'CLOSED', 'CANCELED'];
        $data['status'] = $status[$data['header']->status - 1];

        return view("supplierReturn.edit", $data);
    }

    public function update(Request $request)
    {
        $username     = Auth::user()->username;
        $articles     = json_decode($request->articles);
        $returnNumber = $request->returnNumber;
        $poNumber     = $request->poNumber;
        $supplierId   = $request->supplierId;
        $returnDate   = $request->returnDate;
        $location     = $request->locationNumber;
        $note         = $request->note;

        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query  = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $validation = Validator::make($request->all(), [
            'returnDate'     => 'required',
            'supplierId'     => 'required',
            'locationNumber' => 'required',
        ]);

        $error_array = [];
        if ($validation->fails()) {
            foreach ($validation->messages()->getMessages() as $field_name => $messages) {
                $error_array[] = $messages;
            }
            return response()->json(['status' => 0, 'message' => $error_array, 'alert' => 'warning']);
        }

        // ── GUARD status: hanya boleh edit saat OPEN (1) ──
        $hdr = DB::table('supplier_return_hdr')->where('return_number', $returnNumber)->first();
        if (!$hdr) {
            return response()->json(['status' => 0, 'title' => "Edit $this->title", 'message' => "Return $returnNumber tidak ditemukan", 'alert' => 'warning']);
        }
        if ($hdr->status != '1') {
            return response()->json(['status' => 0, 'title' => "Edit $this->title", 'message' => "Return $returnNumber tidak bisa diedit (status bukan OPEN). Cancel dulu jika perlu.", 'alert' => 'warning', 'returnNumber' => $returnNumber]);
        }

        // ── GUARD: tolak edit jika sudah ada Supplier Replace aktif ──
        if ($this->punyaReplaceAktif($returnNumber)) {
            return response()->json([
                'status'  => 0,
                'title'   => "Edit $this->title",
                'message' => "Return $returnNumber tidak bisa diedit: sudah ada Supplier Replace. Cancel Replace-nya dulu.",
                'alert'   => 'warning',
                'returnNumber' => $returnNumber,
            ]);
        }

        $locationLama = $hdr->location_number;
$supplierLama = $hdr->supplier_id;

// ── VALIDASI QTY vs STOK: DIBOLEHKAN OVER / MINUS (sama seperti store) ──
// $qtyErrors = $this->checkStockQty($articles, $location, ($location == $locationLama ? $returnNumber : null));
// if (!empty($qtyErrors)) {
//     return response()->json(['status' => 0, 'message' => $qtyErrors, 'alert' => 'warning']);
// }

       DB::beginTransaction();
try {
    // 1. HAPUS movement lama (bukan insert reversal) + recalc stok lokasi lama
    $this->deleteMovementAndRecalc($returnNumber, $locationLama, $hdr->return_date);

    // 2. UPDATE header
    DB::table('supplier_return_hdr')
        ->where('return_number', $returnNumber)
        ->update([
            'supplier_id'     => $supplierId,
            'return_date'     => $returnDate,
            'note'            => $note,
            'po_number'       => $poNumber,
            'location_number' => $location,
            'updated_by'      => $username,
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

    // 3. UPDATE detail
    $dataSet = [];
    foreach ($articles as $val) {
        $dataSet[] = [$returnNumber . $val->article_code];
    }

    DB::table('supplier_return_det')
        ->whereNotIn(DB::raw("CONCAT(return_number, article_code)"), $dataSet)
        ->where('return_number', $returnNumber)
        ->delete();

    foreach ($articles as $val) {
        DB::table('supplier_return_det')->updateOrInsert(
            ['return_number' => $returnNumber, 'article_code' => $val->article_code],
            [
                'qty'        => $val->qty,
                'uom'        => $val->uom,
                'updated_by' => $username,
                'updated_at' => date('Y-m-d H:i:s'),
                'created_by' => $username,
                'created_at' => date('Y-m-d H:i:s'),
            ]
        );
    }

    // 4. RE-POST movement baru (di lokasi/tanggal baru) — fresh insert, satu set movement aktif
    $this->postingReturn($returnNumber, $username, $returnDate, $location, $note, $supplierId, $poNumber);

    DB::commit();
    $title   = "Save $this->title";
    $alert   = "success";
    $message = "$title $returnNumber is successfully updated";
    \LogActivity::addToLog($title, "username: $username Status $message");
    return response()->json(['status' => 1, 'title' => $title, 'message' => $message, 'alert' => $alert, 'returnNumber' => $returnNumber]);

} catch (\Exception $e) {
    DB::rollBack();
    $title   = "Save $this->title";
    $alert   = "warning";
    $message = "$title $returnNumber is failed to updated: " . $e->getMessage();
    \LogActivity::addToLog($title, "username: $username Status $message");
    return response()->json(['status' => 0, 'title' => $title, 'message' => $message, 'alert' => $alert, 'returnNumber' => $returnNumber]);
}
    }

    public function destroy(Request $request)
    {
        $id            = Crypt::decryptString($request->id);
        $username      = Auth::user()->username;
        $tHdr          = DB::table('supplier_return_hdr')->where('id', $id)->first();
        $returnNumber  = $tHdr->return_number;
        $returnDate    = $tHdr->return_date;
        $location      = $tHdr->location_number;
        $supplierId    = $tHdr->supplier_id;
        $currentStatus = $tHdr->status;

        if ($currentStatus == '4') {
            $title   = "Delete $this->title";
            $alert   = "warning";
            $message = "$title $returnNumber sudah dibatalkan sebelumnya.";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);
        }

        if ($this->punyaReplaceAktif($returnNumber)) {
            $title   = "Delete $this->title";
            $alert   = "warning";
            $message = "$title $returnNumber gagal: sudah ada Supplier Replace untuk return ini. Cancel Replace-nya terlebih dahulu.";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);
        }

        DB::beginTransaction();
try {
    // 1. HAPUS movement (bukan insert CANCEL reversal) + recalc stok lokasi
    $this->deleteMovementAndRecalc($returnNumber, $location, $returnDate);

    // 2. Cancel header & detail
    $rowAffected = DB::table('supplier_return_hdr')
        ->where('id', $id)
        ->update([
            'status'               => '4',
            'return_number'        => $returnNumber . '(C)',
            'origin_return_number' => $returnNumber . '(C)',
            'reason'               => 'Cancel',
            'updated_by'           => $username,
            'updated_at'           => date('Y-m-d H:i:s'),
        ]);

    if ($rowAffected > 0) {
        DB::table('supplier_return_det')
            ->where('return_number', $returnNumber)
            ->update([
                'return_number' => $returnNumber . '(C)',
                'updated_by'    => $username,
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);

        DB::commit();
        $title   = "Delete $this->title";
        $alert   = "success";
        $message = "$title $returnNumber Successfully Deleted";
        \LogActivity::addToLog($title, "username: $username Status $message");
        return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);
    }

    DB::rollBack();
    $title   = "Delete $this->title";
    $alert   = "warning";
    $message = "$title $returnNumber Failed to Delete";
    \LogActivity::addToLog($title, "username: $username Status $message");
    return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);

} catch (\Exception $e) {
    DB::rollBack();
    $title   = "Delete $this->title";
    $alert   = "warning";
    $message = "$title $returnNumber Failed to Delete: " . $e->getMessage();
    \LogActivity::addToLog($title, "username: $username Status $message");
    return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);
}
    }

    public function closed(Request $request)
    {
        $username     = Auth::user()->username;
        $id           = Crypt::decryptString($request->id);
        $returnNumber = DB::table('supplier_return_hdr')->where('id', $id)->value('return_number');

        DB::beginTransaction();
        try {
            DB::table('supplier_return_hdr')
                ->where('id', $id)
                ->update([
                    'status'     => '3',
                    'updated_by' => $username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

            DB::commit();
            $title   = "Close $this->title";
            $alert   = "success";
            $message = "$title $returnNumber Successfully Closed";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);

        } catch (\Exception $e) {
            DB::rollBack();
            $title   = "Close $this->title";
            $alert   = "warning";
            $message = "$title $returnNumber Failed to Close";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);
        }
    }

    private function lockMovementSequence(): void
{
    // Key WAJIB sama persis dengan controller lain yang insert warehouse_movement.
    DB::select("SELECT pg_advisory_xact_lock(hashtext('warehouse_movement_code'))");
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

/**
 * Recalculate last_qty semua movement mulai $fromDate untuk article+location tsb,
 * lalu sinkronkan warehouse_stock ke saldo terkini. Sama persis pola TransferStock.
 *
 * @param string $fromDate boleh 'd-m-Y' atau 'Y-m-d'
 */
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

/**
 * Hapus SEMUA movement milik satu return_number di lokasi tsb (tanpa insert baris pembalik),
 * lalu recalculate stok article-article yang terdampak mulai returnDate.
 * Return: daftar article_code yang terdampak (untuk keperluan re-posting kalau perlu).
 */
private function deleteMovementAndRecalc(string $returnNumber, string $location, string $returnDate): array
{
    $affectedArticles = DB::table('warehouse_movement')
        ->where('movement_transnno', $returnNumber)
        ->where('location_number', $location)
        ->where('site_code', $this->siteCode)
        ->pluck('artikel_code')
        ->unique()
        ->values()
        ->all();

    DB::table('warehouse_movement')
        ->where('movement_transnno', $returnNumber)
        ->where('location_number', $location)
        ->where('site_code', $this->siteCode)
        ->delete();

    foreach ($affectedArticles as $articleCode) {
        $this->recalculateMovementAndStock($articleCode, $location, $returnDate);
    }

    return $affectedArticles;
}

    public function list(Request $request)
    {
        // status: 1=OPEN, 3=CLOSED, 4=CANCELED
        $searchDn       = strtolower($request->searchDn); // return number search
        $searchStatus   = $request->searchStatus;
        $returnDate     = $request->returnDate;
        $searchSupplier = $request->searchSupplier;
        $searchLocation = $request->searchLocation;   // ← filter lokasi
        $fromDate = null;
        $toDate   = null;

        if ($returnDate) {
            $parts   = explode("to", $returnDate);
            $rawFrom = trim($parts[0]);
            $rawTo   = isset($parts[1]) ? trim($parts[1]) : $rawFrom;

            try {
                $fromDate = $this->toIsoDate($rawFrom);
                $toDate   = $this->toIsoDate($rawTo);
            } catch (\Exception $e) {
                return response()->json([
                    'draw' => (int) $request->draw,
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $data = DB::table('supplier_return_hdr')
            ->leftJoin('third_party', 'third_party.kode', '=', 'supplier_return_hdr.supplier_id')
            ->where(function ($query) use ($searchDn, $searchStatus, $returnDate, $fromDate, $toDate, $searchSupplier, $searchLocation) {
                $searchDn ? $query->where('supplier_return_hdr.return_number', 'ilike', '%' . $searchDn . '%') : '';
                $searchStatus ? $query->where('supplier_return_hdr.status', $searchStatus) : '';
                $returnDate ? $query->whereBetween(DB::raw("to_date(return_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
                $searchSupplier ? $query->where('supplier_return_hdr.supplier_id', $searchSupplier) : '';
                $searchLocation ? $query->where('supplier_return_hdr.location_number', $searchLocation) : '';
            })
            ->where('supplier_return_hdr.status', '!=', '4')
            ->select(
                'supplier_return_hdr.*',
                'nama as supplier_name'
            )
            ->orderBy('id')
            ->get();

        return Datatables::of($data)
            ->addColumn('action', function ($data) {
                $buttons = '<div class="d-inline-flex">
                                <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                    <i data-feather="menu"></i>
                                </a>';
                $buttons .= '<div class="dropdown-menu dropdown-menu-right">';

                if ($data->status == '1') {
                    $buttons .= '<a href="' . route('supplierReturn.edit', ['id' => Crypt::encryptString($data->id)]) . '" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
                }

                $buttons .= '<a href="' . route('supplierReturn.show', ['id' => Crypt::encryptString($data->id)]) . '" class="dropdown-item">
                                <i data-feather="list"></i>
                                Detail
                             </a>';

                $buttons .= '<a href="' . route('supplierReturn.print', ['id' => Crypt::encryptString($data->id)]) . '" target="_blank" class="dropdown-item">
                                <i data-feather="printer"></i>
                                Print
                             </a>';

                $buttons .= "<a href='javascript:;'
                                class='dropdown-item'
                                data-size='sm'
                                data-ajax-delete='true'
                                data-confirm='Are You Sure want to Delete?|This action can not be undone. Do you want to continue?'
                                data-confirm-yes='document.getElementById(\"" . "delete-form-" . $data->id . "\").submit();'
                                data-modal-id='" . $data->id . "'
                                id='deleteButton'
                                data-url='" . route('supplierReturn.destroy', ['id' => Crypt::encryptString($data->id)]) . "'>
                                <i data-feather='trash-2' class='feather-14-red'></i>
                                <span>" . __('Cancel') . "</span>
                            </a>";

                $buttons .= '</div></div>';
                return $buttons;
            })
            ->addColumn('status', function ($data) {
                $badges   = ['badge-primary', 'badge-info', 'badge-success', 'badge-warning', 'badge-danger', 'badge-dark', 'badge-secondary', 'badge-secondary'];
                $statusPr = ['OPEN', '', 'CLOSED', 'CANCELED'];
                return "<div class='badge " . $badges[$data->status - 1] . "'>" . $statusPr[$data->status - 1] . "</div>";
            })
            ->rawColumns(['action', 'status', 'return_number'])
            ->make(true);
    }

    // SupplierReturnController.php — tambahkan method ini
public function listDetail(Request $request)
{
    $searchDn       = strtolower($request->searchDn);
    $searchStatus   = $request->searchStatus;
    $returnDate     = $request->returnDate;
    $searchSupplier = $request->searchSupplier;
    $searchLocation = $request->searchLocation;
    $fromDate = null;
    $toDate   = null;

    if ($returnDate) {
        $parts   = explode("to", $returnDate);
        $rawFrom = trim($parts[0]);
        $rawTo   = isset($parts[1]) ? trim($parts[1]) : $rawFrom;

        try {
            $fromDate = $this->toIsoDate($rawFrom);
            $toDate   = $this->toIsoDate($rawTo);
        } catch (\Exception $e) {
            return response()->json([
                'draw' => (int) $request->draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage(),
            ]);
        }
    }

    $data = DB::table('supplier_return_det')
        ->leftJoin('supplier_return_hdr', 'supplier_return_hdr.return_number', 'supplier_return_det.return_number')
        ->leftJoin('article', 'article.article_code', 'supplier_return_det.article_code')
        ->leftJoin('third_party', 'third_party.kode', 'supplier_return_hdr.supplier_id')
        ->where(function ($query) use ($searchDn, $searchStatus, $returnDate, $fromDate, $toDate, $searchSupplier, $searchLocation) {
            $searchDn ? $query->where('supplier_return_hdr.return_number', 'ilike', '%' . $searchDn . '%') : '';
            $searchStatus ? $query->where('supplier_return_hdr.status', $searchStatus) : '';
            $returnDate ? $query->whereBetween(DB::raw("to_date(return_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
            $searchSupplier ? $query->where('supplier_return_hdr.supplier_id', $searchSupplier) : '';
            $searchLocation ? $query->where('supplier_return_hdr.location_number', $searchLocation) : '';
        })
        ->where('supplier_return_hdr.status', '!=', '4')
        ->select(
            'supplier_return_det.*',
            'article_alternative_code',
            'article.article_desc',
            'supplier_return_hdr.status',
            'supplier_return_hdr.return_date',
            'supplier_return_hdr.note',
            'supplier_return_hdr.supplier_id',
            'supplier_return_hdr.po_number',
            'supplier_return_hdr.location_number',
            'third_party.nama as supplier_name'
        )
        ->orderBy('supplier_return_det.id')
        ->get();

    return Datatables::of($data)
        ->addColumn('status', function ($data) {
            $badges   = ['badge-primary', 'badge-info', 'badge-success', 'badge-warning', 'badge-danger', 'badge-dark', 'badge-secondary', 'badge-secondary'];
            $statusPr = ['OPEN', '', 'CLOSED', 'CANCELED'];
            return "<div class='badge " . $badges[$data->status - 1] . "'>" . $statusPr[$data->status - 1] . "</div>";
        })
        ->rawColumns(['status'])
        ->make(true);
}

    /**
     * Apakah return ini SAAT INI masih ter-posting (net qty movement_min > movement_plus reverse).
     */

    public function print(Request $request)
    {
        $id = Crypt::decryptString($request->id);

        $tHdr = DB::table('supplier_return_hdr')->where('id', $id)->first();
        if (!$tHdr) {
            abort(404, "Supplier Return tidak ditemukan");
        }

        $returnNumber = $tHdr->return_number;

        $data['title']    = "Print $this->title";
        $data['subtitle'] = "Print $this->title";
        $data['tHdr']     = $tHdr;

        $data['details'] = DB::table('supplier_return_det')
            ->leftJoin('article', 'article.article_code', '=', 'supplier_return_det.article_code')
            ->where('supplier_return_det.return_number', $returnNumber)
            ->select(
                'supplier_return_det.article_code',
                'article.article_alternative_code',
                'article.article_desc',
                'supplier_return_det.qty',
                'supplier_return_det.uom'
            )
            ->orderBy('supplier_return_det.id')
            ->get();

        $data['tDnNumber']     = $returnNumber;
        $data['tDnDate']       = $tHdr->return_date;
        $data['tDnNote']       = $tHdr->note;
        $data['poNumber']      = $tHdr->po_number ?? '-';
        $data['locationNumber']= $tHdr->location_number ?? '-';

        $statusPr = ['OPEN', '', 'CLOSED', 'CANCELED'];
        $data['status'] = $statusPr[$tHdr->status - 1] ?? 'UNKNOWN';

        $data['no'] = 0;

        $data['suppliers'] = DB::table('third_party')
            ->where('third_party_type', '=', 'sup')
            ->where('kode', $tHdr->supplier_id)
            ->first();

        return view('supplierReturn.print', $data);
    }

    public function getArticle(Request $request)
    {
        $supplierCode = $request->supplierCode;
        $location     = $request->locationNumber;
        $siteCode     = $this->siteCode;

        // article yang punya stok > 0 di lokasi tsb
        $data = DB::table('warehouse_stock')
            ->join('article', 'article.article_code', '=', 'warehouse_stock.article_code')
            ->where('warehouse_stock.site_code', $siteCode)
            ->where('warehouse_stock.location_number', $location)
            ->where('warehouse_stock.article_qty', '>', 0)
            ->when($supplierCode, function ($q) use ($supplierCode) {
                $q->where('article.third_party', $supplierCode);
            })
            ->select(
                'article.article_code',
                'article.article_alternative_code',
                'article.article_desc',
                'article.uom',
                'warehouse_stock.article_qty as stock_available'
            )
            ->orderBy('article.article_desc')
            ->get();

        $output = '<option value="">Choose article</option>';
        foreach ($data as $row) {
            $output .= '<option value="' . $row->article_code . '"'
                     . ' data-uom="' . $row->uom . '"'
                     . ' data-stock="' . $row->stock_available . '">'
                     . $row->article_alternative_code . '-' . $row->article_desc . '</option>';
        }

        return $output;
    }
}