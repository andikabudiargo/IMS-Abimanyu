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

class DnGeneralController extends Controller
{
    private $title;
    private $moduleCode;
    private $decimalPlaces;
    public function __construct()
    {
        $this->title = "DN General";
        $this->moduleCode = "DN-GENERAL";
        $this->decimalPlaces = config('globalParam.decimal');
    }

    public function getTableColoumn()
    {
        $kolom =
        [
            ['data' => 'action',         'name' => 'action',         'title' => 'action', 'orderable' => false, 'searchable' => false],
            ['data' => 'tdn_number',     'name' => 'tdn_number',     'title' => 'Number'],
            ['data' => 'status',         'name' => 'status',         'title' => 'Status'],
            ['data' => 'dn_type',        'name' => 'dn_type',        'title' => 'Type'],
            ['data' => 'delivery_date',  'name' => 'delivery_date',  'title' => 'Delivery Date'],
            ['data' => 'perihal',        'name' => 'perihal',        'title' => 'Perihal'],
            ['data' => 'customer_name',  'name' => 'customer_name',  'title' => 'Customer'],
            ['data' => 'note',           'name' => 'note',           'title' => 'Note'],
            ['data' => 'created_by',     'name' => 'created_by',     'title' => 'Created By'],
            ['data' => 'created_at',     'name' => 'created_at',     'title' => 'Created Date'],
            ['data' => 'updated_by',     'name' => 'updated_by',     'title' => 'Updated By'],
            ['data' => 'updated_at',     'name' => 'updated_at',     'title' => 'Updated Date'],
        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnDetail()
    {
        $kolom =
        [
            ['data' => 'tdn_number',              'name' => 'tdn_number',              'title' => 'Delivery Number'],
            ['data' => 'dn_type',                 'name' => 'dn_type',                 'title' => 'Type'],
            ['data' => 'delivery_date',           'name' => 'delivery_date',           'title' => 'Delivery Date'],
            ['data' => 'perihal',                 'name' => 'perihal',                 'title' => 'Perihal'],
            // ['data'=>'customer_id','name'=>'customer_id','title'=>'Customer'],
            ['data' => 'customer_name',           'name' => 'customer_name',           'title' => 'Customer'],
            ['data' => 'article_alternative_code','name' => 'article_alternative_code','title' => 'Article Code'],
            ['data' => 'article_desc',            'name' => 'article_desc',            'title' => 'Description'],
            ['data' => 'qty',                     'name' => 'qty',                     'title' => 'Qty'],
            ['data' => 'uom',                     'name' => 'uom',                     'title' => 'UOM'],
            ['data' => 'stock_on_send',           'name' => 'stock_on_send',           'title' => 'Stock Saat Kirim'],
            ['data' => 'status',                  'name' => 'status',                  'title' => 'Status'],
            ['data' => 'note',                    'name' => 'note',                    'title' => 'Note'],
            ['data' => 'created_by',              'name' => 'created_by',              'title' => 'Created By'],
            ['data' => 'created_at',              'name' => 'created_at',              'title' => 'Created Date'],
            ['data' => 'updated_by',              'name' => 'updated_by',              'title' => 'Updated By'],
            ['data' => 'updated_at',              'name' => 'updated_at',              'title' => 'Updated Date'],
        ];
        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $username =  Auth::user()->username;
        $data['title'] = "$this->title";
        $data['kolom'] = $this->getTableColoumn();
        $data['kolomDetail'] = $this->getTableColoumnDetail();
        $data['status'] = ['1'=>'OPEN','2'=>'SO','3'=>'CLOSED'];

        $data['customers'] = DB::table('third_party')
        //->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        return view("dnGeneral.index",$data);
    }

    public function getLastCode($key)
    {
        DB::table('master_code')
        ->where('code_key',$key)
        ->update([
            'code_number' => DB::raw('code_number + 1'),
            'updated_by' => Auth::user()->username,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $newCode = DB::table('master_code')
        ->where('code_key',$key)
        ->value('code_number'); 

        $newCode = str_pad($newCode,5,"0", STR_PAD_LEFT);
        $month = str_pad(date('n'),2,"0", STR_PAD_LEFT);
        $year = date('y');
        $prNumber="$key-$year-$month-$newCode";
        
        return $prNumber;
    }

    public function create(Request $request)
{
    $data['title']    = "Create $this->title";
    $data['subtitle'] = "Create $this->title";

    // Supplier (untuk Return RM)
    $data['suppliers'] = DB::table('third_party')
        ->where('third_party_type', 'supp')
        ->orderBy('nama')
        ->get();

    // Customer (untuk Return OT)
    $data['customers'] = DB::table('third_party')
        ->where('third_party_type', 'cust')
        ->orderBy('nama')
        ->get();

    // Semua (untuk Other)
    $data['allParties'] = DB::table('third_party')
        ->orderBy('nama')
        ->get();

    $data['currentDate'] = date('d-m-Y');

    return view("dnGeneral.create", $data);
}

/*
|--------------------------------------------------------------------------
| store() — DN General dengan type (rm / ot / other)
|--------------------------------------------------------------------------
| Tabel: dn_general_hdr, dn_general_det
|
| Kolom yang perlu ada:
| dn_general_hdr : dn_type        varchar(10)
| dn_general_det : article_desc   varchar  (deskripsi manual utk OTHER)
|                  stock_on_send  numeric  (snapshot stok saat dikirim)
|                  location_number varchar (gudang asal: 037/008/011)
|
| Kode artikel manual = 'OTHER' → tidak punya stok, tidak dikurangi, tidak dicek.
| Gudang per type: rm=037, ot=008, other=011
| Qty dipakai apa adanya (TANPA konversi UOM).
*/

public function store(Request $request)
    {
        $username     = Auth::user()->username;
        $articles     = json_decode($request->articles);
        $customerId   = $request->customerId;
        $deliveryDate = $request->deliveryDate;
        $perihal      = $request->perihal;
        $note         = $request->note;
        $dnType       = $request->dnType;          // 'rm' | 'ot' | 'other'
        $status       = '1';
        $tDnNumber    = '';
        $leadCode     = $this->moduleCode;
        $siteCode     = 'HO';

        // Gudang asal berdasarkan type
        $gudangMap = [
            'rm'    => '037',
            'ot'    => '008',
            'other' => '011',
        ];
        $location = isset($gudangMap[$dnType]) ? $gudangMap[$dnType] : null;

        // Validasi field utama
        $validation = Validator::make($request->all(), [
            'deliveryDate' => 'required',
            'customerId'   => 'required',
            'dnType'       => 'required',
        ], [
            'required' => 'The field is required.',
        ]);

        if ($validation->fails()) {
            $error_array = [];
            foreach ($validation->messages()->getMessages() as $field_name => $messages) {
                $error_array[] = $messages;
            }
            return response()->json(['status' => 0, 'message' => $error_array, 'alert' => 'warning']);
        }

        if (!$location) {
            return response()->json(['status' => 0, 'message' => ['Type tidak valid.'], 'alert' => 'warning']);
        }

        // Helper: apakah artikel manual (OTHER)
        $isManual = function ($code) {
            return strtoupper(trim($code)) === 'OTHER';
        };

        // CEK STOCK semua article (kecuali manual)
        $stockErrors = [];
        foreach ($articles as $val) {
            if ($isManual($val->article_code)) {
                continue;
            }

            $articleInfo = DB::table('article')
                ->where('article_code', $val->article_code)
                ->select('article_desc', 'article_alternative_code')
                ->first();

            if (!$articleInfo) {
                $stockErrors[] = "Article {$val->article_code} tidak ditemukan di master.";
                continue;
            }

            $qty = $val->qty;

            $stockNow = DB::table('warehouse_stock')
                ->where('site_code', $siteCode)
                ->where('article_code', $val->article_code)
                ->where('location_number', $location)
                ->value('article_qty') ?? 0;

            if ($stockNow < $qty) {
                $stockErrors[] = "Stock {$articleInfo->article_alternative_code} - {$articleInfo->article_desc} "
                    . "tidak cukup (stock: {$stockNow}, butuh: {$qty})";
            }
        }

        if (!empty($stockErrors)) {
            return response()->json(['status' => 0, 'message' => $stockErrors, 'alert' => 'warning']);
        }

        $hasilUpdate = AppHelpers::resetCode($leadCode);
        $tDnNumber   = $this->getLastCode($leadCode);

        DB::beginTransaction();
        try {
            DB::table('dn_general_hdr')->insert([
                'tdn_number'        => $tDnNumber,
                'customer_id'       => $customerId,
                'delivery_date'     => $deliveryDate,
                'perihal'           => $perihal,
                'note'              => $note,
                'dn_type'           => $dnType,
                'origin_tdn_number' => $tDnNumber,
                'status'            => $status,
                'created_by'        => $username,
                'updated_by'        => $username,
                'created_at'        => date('Y-m-d H:i:s'),
                'updated_at'        => date('Y-m-d H:i:s'),
            ]);

            $dataSet     = [];
            $movementSet = [];

            foreach ($articles as $val) {
                $manual = $isManual($val->article_code);
                $qty    = $val->qty;

                if ($manual) {
                    $dataSet[] = [
                        'tdn_number'      => $tDnNumber,
                        'article_code'    => 'OTHER',
                        'article_desc'    => $val->article_name,
                        'qty'             => $qty,
                        'uom'             => $val->uom,
                        'stock_on_send'   => 0,
                        'location_number' => $location,
                        'created_by'      => $username,
                        'updated_by'      => $username,
                        'created_at'      => date('Y-m-d H:i:s'),
                        'updated_at'      => date('Y-m-d H:i:s'),
                    ];
                    continue;
                }

                $articleInfo = DB::table('article')
                    ->where('article_code', $val->article_code)
                    ->select('article_desc', 'article_alternative_code', 'article_type', 'uom')
                    ->first();

                $stockBefore = DB::table('warehouse_stock')
                    ->where('site_code', $siteCode)
                    ->where('article_code', $val->article_code)
                    ->where('location_number', $location)
                    ->value('article_qty') ?? 0;

                $dataSet[] = [
                    'tdn_number'      => $tDnNumber,
                    'article_code'    => $val->article_code,
                    'article_desc'    => $articleInfo->article_desc,
                    'qty'             => $qty,
                    'uom'             => $val->uom,
                    'stock_on_send'   => $stockBefore,
                    'location_number' => $location,
                    'created_by'      => $username,
                    'updated_by'      => $username,
                    'created_at'      => date('Y-m-d H:i:s'),
                    'updated_at'      => date('Y-m-d H:i:s'),
                ];

                DB::table('warehouse_stock')->updateOrInsert(
                    [
                        'site_code'       => $siteCode,
                        'article_code'    => $val->article_code,
                        'location_number' => $location,
                    ],
                    [
                        'dept_code' => $articleInfo->article_type,
                        'uom'       => $articleInfo->uom,
                    ]
                );

                DB::table('warehouse_stock')
                    ->where('site_code', $siteCode)
                    ->where('article_code', $val->article_code)
                    ->where('location_number', $location)
                    ->update([
                        'article_qty' => DB::raw('coalesce(article_qty,0) - ' . $qty),
                    ]);

                $lastQtyAfter = DB::table('warehouse_stock')
                    ->where('site_code', $siteCode)
                    ->where('article_code', $val->article_code)
                    ->where('location_number', $location)
                    ->value('article_qty') ?? 0;

              // Tentukan movement_from berdasarkan dnType
$movementFrom = $location; // sudah di-set dari gudangMap: rm=037, ot=008, other=011

// Tentukan partner_type berdasarkan dnType
if ($dnType === 'rm') {
    $partnerType = 'SUPP';
} elseif ($dnType === 'ot') {
    $partnerType = 'CUST';
} else {
    // other: cek langsung dari customer_id ke tabel third_party
   $partnerType = strtoupper(
        DB::table('third_party')
            ->where('kode', $customerId)
            ->value('third_party_type')
    );
}

$movementSet[] = [
    'movement_date'     => $deliveryDate,
    'artikel_code'      => $val->article_code,
    'artikel_desc'      => $articleInfo->article_desc,
    'movement_min'      => $qty,
    'movement_plus'     => 0,
    'movement_price'    => 0,
    'movement_transnno' => $tDnNumber,
    'movement_type'     => 'SURAT JALAN UMUM',
    'movement_desc'     => $perihal,
    'movement_from'     => $movementFrom,   // kode gudang asal
    'partner_type'      => $partnerType,    // SUPP / CUST / dari third_party
    'movement_to'       => $customerId,     // tujuan = customer_id
    'created_by'        => $username,
    'created_at'        => date('Y-m-d H:i:s'),
    'site_code'         => $siteCode,
    'location_number'   => $location,
    'last_qty'          => $lastQtyAfter,
];
            }
            DB::table('dn_general_det')->insert($dataSet);

            if (!empty($movementSet)) {
                DB::table('warehouse_movement')->insert($movementSet);
            }

            DB::commit();

            $title   = "Save $this->title";
            $alert   = "success";
            $message = "$title $tDnNumber is successfully saved";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return response()->json([
                'status'    => 1,
                'title'     => $title,
                'message'   => $message,
                'alert'     => $alert,
                'tDnNumber' => $tDnNumber
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            $title   = "Save $this->title";
            $alert   = "warning";
            $message = "$title $tDnNumber is failed to saved";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return response()->json([
                'status'    => 0,
                'title'     => $title,
                'message'   => $message,
                'alert'     => $alert,
                'tDnNumber' => $tDnNumber
            ]);
        }
    }

    public function show(Request $request)
{
    $id       = Crypt::decryptString($request->id);
    $username = Auth::user()->username;

    $data['title']    = "Detail $this->title";
    $data['subtitle'] = "Detail $this->title";

    $dnHdr = DB::table('dn_general_hdr')
        ->leftJoin('third_party', 'third_party.kode', '=', 'dn_general_hdr.customer_id')
        ->where('dn_general_hdr.id', $id)
        ->select(
            'dn_general_hdr.*',
            DB::raw("concat(third_party.kode, ' - ', third_party.nama) as customer_name"),
            'third_party.alamat_kirim_1',
        )
        ->first();

    $details = DB::table('dn_general_det')
        ->leftJoin('article', 'article.article_code', '=', 'dn_general_det.article_code')
        ->where('dn_general_det.tdn_number', $dnHdr->tdn_number)
        ->select(
            'dn_general_det.*',
            DB::raw("coalesce(article.article_alternative_code, dn_general_det.article_code) as article_alternative_code"),
            DB::raw("coalesce(article.article_desc, dn_general_det.article_desc) as article_desc"),
        )
        ->orderBy('dn_general_det.id')
        ->get();

    $statusMap = ['1' => 'NEW', '2' => 'APPROVED', '3' => 'CLOSED', '4' => 'CANCELED'];

    $data['dnHdr']   = $dnHdr;
    $data['details'] = $details;
    $data['status']  = $statusMap[$dnHdr->status] ?? '-';
    $data['no']      = 0;

    return view('dnGeneral.show', $data);
}

    public function edit(Request $request)
{
    $id       = Crypt::decryptString($request->id);
    $username = Auth::user()->username;

    $data['title']    = "Edit $this->title";
    $data['subtitle'] = "Edit $this->title";

    $header = DB::table('dn_general_hdr')->where('id', $id)->first();
    $tDnNumber = $header->tdn_number;

    $data['header']  = $header;
    $data['details'] = DB::table('dn_general_det')
        ->leftJoin('article', 'article.article_code', '=', 'dn_general_det.article_code')
        ->where('dn_general_det.tdn_number', $tDnNumber)
        ->select(
            'dn_general_det.*',
            DB::raw("coalesce(article.article_alternative_code, dn_general_det.article_code) as article_alternative_code"),
            DB::raw("coalesce(article.article_desc, dn_general_det.article_desc) as article_desc_label"),
        )
        ->orderBy('dn_general_det.id')
        ->get();

    // Supplier (untuk Return RM)
    $data['suppliers'] = DB::table('third_party')
        ->where('third_party_type', 'supp')
        ->orderBy('nama')
        ->get();

    // Customer (untuk Return OT)
    $data['customers'] = DB::table('third_party')
        ->where('third_party_type', 'cust')
        ->orderBy('nama')
        ->get();

    // Semua (untuk Other)
    $data['allParties'] = DB::table('third_party')
        ->orderBy('nama')
        ->get();

    $statusMap = ['1' => 'NEW', '2' => 'APPROVED', '3' => 'CLOSED', '4' => 'CANCELED'];
    $data['status'] = $statusMap[$header->status] ?? '-';

    return view('dnGeneral.edit', $data);
}
    
    public function update(Request $request)
{
    $username     = Auth::user()->username;
    $articles     = json_decode($request->articles);
    $tDnNumber    = $request->tDnNumber;
    $customerId   = $request->customerId;
    $deliveryDate = $request->deliveryDate;
    $perihal      = $request->perihal;
    $note         = $request->note;
    $siteCode     = 'HO';

    $validation = Validator::make($request->all(), [
        'deliveryDate' => 'required',
        'customerId'   => 'required',
    ], [
        'required' => 'The field is required.',
    ]);

    if ($validation->fails()) {
        $error_array = [];
        foreach ($validation->messages()->getMessages() as $field_name => $messages) {
            $error_array[] = $messages;
        }
        return response()->json(['status' => 0, 'message' => $error_array, 'alert' => 'warning']);
    }

    // Ambil header untuk tahu dnType & location
    $dnHdr = DB::table('dn_general_hdr')->where('tdn_number', $tDnNumber)->first();
    if (!$dnHdr) {
        return response()->json(['status' => 0, 'message' => ['Data tidak ditemukan.'], 'alert' => 'warning']);
    }

    $dnType    = $dnHdr->dn_type;
    $gudangMap = ['rm' => '037', 'ot' => '008', 'other' => '011'];
    $location  = $gudangMap[$dnType] ?? null;

    $isManual = function ($code) {
        return strtoupper(trim($code)) === 'OTHER';
    };

    // Ambil detail lama (non-manual) untuk reverse stock dulu
    $oldDetails = DB::table('dn_general_det')
        ->where('tdn_number', $tDnNumber)
        ->where('article_code', '!=', 'OTHER')
        ->get();

    // Cek stock artikel BARU (kecuali manual)
    // Stock yang tersedia = stock sekarang + stock lama artikel yang sama (karena nanti di-reverse)
    $stockErrors = [];
    foreach ($articles as $val) {
        if ($isManual($val->article_code)) continue;

        $articleInfo = DB::table('article')
            ->where('article_code', $val->article_code)
            ->select('article_desc', 'article_alternative_code')
            ->first();

        if (!$articleInfo) {
            $stockErrors[] = "Article {$val->article_code} tidak ditemukan di master.";
            continue;
        }

        $stockNow = DB::table('warehouse_stock')
            ->where('site_code', $siteCode)
            ->where('article_code', $val->article_code)
            ->where('location_number', $location)
            ->value('article_qty') ?? 0;

        // Tambahkan kembali qty lama artikel yang sama (karena akan di-reverse)
        $oldQty = $oldDetails->where('article_code', $val->article_code)->sum('qty');
        $stockAvailable = $stockNow + $oldQty;

        if ($stockAvailable < $val->qty) {
            $stockErrors[] = "Stock {$articleInfo->article_alternative_code} - {$articleInfo->article_desc} "
                . "tidak cukup (stock tersedia: {$stockAvailable}, butuh: {$val->qty})";
        }
    }

    if (!empty($stockErrors)) {
        return response()->json(['status' => 0, 'message' => $stockErrors, 'alert' => 'warning']);
    }

    DB::beginTransaction();
    try {
        // 1. Update header
        DB::table('dn_general_hdr')
            ->where('tdn_number', $tDnNumber)
            ->update([
                'customer_id'   => $customerId,
                'delivery_date' => $deliveryDate,
                'perihal'       => $perihal,
                'note'          => $note,
                'updated_by'    => $username,
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);

        // 2. Reverse stock lama (kembalikan dulu semua stock artikel lama non-manual)
        foreach ($oldDetails as $old) {
            $loc = $old->location_number ?? $location;
            DB::table('warehouse_stock')
                ->where('site_code', $siteCode)
                ->where('article_code', $old->article_code)
                ->where('location_number', $loc)
                ->update([
                    'article_qty' => DB::raw('coalesce(article_qty, 0) + ' . $old->qty),
                ]);
        }

        // 3. Soft-delete movement lama
        DB::table('warehouse_movement')
            ->where('movement_transnno', $tDnNumber)
            ->where('movement_type', 'SURAT JALAN UMUM')
            ->delete();

        // 4. Hapus semua detail lama, ganti dengan yang baru
        DB::table('dn_general_det')->where('tdn_number', $tDnNumber)->delete();

        // 5. Tentukan partner_type (sekali saja, tidak perlu per artikel)
        if ($dnType === 'rm') {
            $partnerType = 'SUPP';
        } elseif ($dnType === 'ot') {
            $partnerType = 'CUST';
        } else {
            $partnerType = strtoupper(
                DB::table('third_party')->where('kode', $customerId)->value('third_party_type')
            );
        }

        $dataSet     = [];
        $movementSet = [];

        foreach ($articles as $val) {
            $manual = $isManual($val->article_code);
            $qty    = $val->qty;

            if ($manual) {
                $dataSet[] = [
                    'tdn_number'      => $tDnNumber,
                    'article_code'    => 'OTHER',
                    'article_desc'    => $val->article_name,
                    'qty'             => $qty,
                    'uom'             => $val->uom,
                    'stock_on_send'   => 0,
                    'location_number' => $location,
                    'created_by'      => $username,
                    'updated_by'      => $username,
                    'created_at'      => date('Y-m-d H:i:s'),
                    'updated_at'      => date('Y-m-d H:i:s'),
                ];
                continue;
            }

            $articleInfo = DB::table('article')
                ->where('article_code', $val->article_code)
                ->select('article_desc', 'article_alternative_code', 'article_type', 'uom')
                ->first();

            // Stock sebelum dikurangi (setelah reverse di step 2, ini sudah kembali normal)
            $stockBefore = DB::table('warehouse_stock')
                ->where('site_code', $siteCode)
                ->where('article_code', $val->article_code)
                ->where('location_number', $location)
                ->value('article_qty') ?? 0;

            $dataSet[] = [
                'tdn_number'      => $tDnNumber,
                'article_code'    => $val->article_code,
                'article_desc'    => $articleInfo->article_desc,
                'qty'             => $qty,
                'uom'             => $val->uom,
                'stock_on_send'   => $stockBefore,
                'location_number' => $location,
                'created_by'      => $username,
                'updated_by'      => $username,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ];

            // 6. Kurangi stock baru
            DB::table('warehouse_stock')->updateOrInsert(
                ['site_code' => $siteCode, 'article_code' => $val->article_code, 'location_number' => $location],
                ['dept_code' => $articleInfo->article_type, 'uom' => $articleInfo->uom]
            );

            DB::table('warehouse_stock')
                ->where('site_code', $siteCode)
                ->where('article_code', $val->article_code)
                ->where('location_number', $location)
                ->update(['article_qty' => DB::raw('coalesce(article_qty, 0) - ' . $qty)]);

            $lastQtyAfter = DB::table('warehouse_stock')
                ->where('site_code', $siteCode)
                ->where('article_code', $val->article_code)
                ->where('location_number', $location)
                ->value('article_qty') ?? 0;

            $movementSet[] = [
                'movement_date'     => $deliveryDate,
                'artikel_code'      => $val->article_code,
                'artikel_desc'      => $articleInfo->article_desc,
                'movement_min'      => $qty,
                'movement_plus'     => 0,
                'movement_price'    => 0,
                'movement_transnno' => $tDnNumber,
                'movement_type'     => 'SURAT JALAN UMUM',
                'movement_desc'     => $perihal,
                'movement_from'     => $location,
                'partner_type'      => $partnerType,
                'movement_to'       => $customerId,
                'created_by'        => $username,
                'created_at'        => date('Y-m-d H:i:s'),
                'site_code'         => $siteCode,
                'location_number'   => $location,
                'last_qty'          => $lastQtyAfter,
            ];
        }

        DB::table('dn_general_det')->insert($dataSet);

        if (!empty($movementSet)) {
            DB::table('warehouse_movement')->insert($movementSet);
        }

        DB::commit();

        $title   = "Update $this->title";
        $alert   = 'success';
        $message = "$title $tDnNumber is successfully updated";
        \LogActivity::addToLog($title, "username: $username Status $message");
        return response()->json(['status' => 1, 'title' => $title, 'message' => $message, 'alert' => $alert, 'tDnNumber' => $tDnNumber]);

    } catch (Exception $e) {
        DB::rollBack();
        $title   = "Update $this->title";
        $alert   = 'warning';
        $message = "$title $tDnNumber is failed to update - " . $e->getMessage();
        \LogActivity::addToLog($title, "username: $username Status $message");
        return response()->json(['status' => 0, 'title' => $title, 'message' => $message, 'alert' => $alert, 'tDnNumber' => $tDnNumber]);
    }
}

    public function destroy(Request $request)
{
    $id       = Crypt::decryptString($request->id);
    $username = Auth::user()->username;
    $siteCode = 'HO';

    $dnHdr    = DB::table('dn_general_hdr')->where('id', $id)->first();

    if (!$dnHdr) {
        return redirect()->back()->with([
            'title'   => "Delete $this->title",
            'alert'   => 'warning',
            'message' => 'Data tidak ditemukan.',
        ]);
    }

    $tDnNumber = $dnHdr->tdn_number;
    $dnType    = $dnHdr->dn_type;

    // Gudang asal berdasarkan type (sama seperti store)
    $gudangMap = [
        'rm'    => '037',
        'ot'    => '008',
        'other' => '011',
    ];
    $location = $gudangMap[$dnType] ?? null;

    DB::beginTransaction();
    try {
        // 1. Tandai header sebagai CANCELED (status 4), rename nomor dengan (C)
        $rowAffected = DB::table('dn_general_hdr')
            ->where('id', $id)
            ->update([
                'status'            => '4',
                'tdn_number'        => $tDnNumber . '(C)',
                'origin_tdn_number' => $tDnNumber . '(C)',
                'updated_by'        => $username,
                'updated_at'        => date('Y-m-d H:i:s'),
            ]);

        if ($rowAffected < 1) {
            DB::rollBack();
            return redirect()->back()->with([
                'title'   => "Delete $this->title",
                'alert'   => 'warning',
                'message' => "$this->title $tDnNumber Failed to Cancel - Header not found.",
            ]);
        }

        // 2. Rename tdn_number di detail
        DB::table('dn_general_det')
            ->where('tdn_number', $tDnNumber)
            ->update([
                'tdn_number' => $tDnNumber . '(C)',
                'updated_by' => $username,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        // 3. Ambil detail untuk reverse stock (skip artikel manual OTHER)
        $details = DB::table('dn_general_det as d')
            ->leftJoin('article', 'article.article_code', '=', 'd.article_code')
            ->where('d.tdn_number', $tDnNumber . '(C)')
            ->where('d.article_code', '!=', 'OTHER')
            ->select(
                'd.article_code',
                'd.qty',
                'd.uom',
                'd.location_number',
                'article.article_desc',
                'article.article_type',
            )
            ->get();

        $reverseMovements = [];

        foreach ($details as $det) {
            $loc = $det->location_number ?? $location;

            // 4. Kembalikan stock ke warehouse_stock
            DB::table('warehouse_stock')
                ->where('site_code', $siteCode)
                ->where('article_code', $det->article_code)
                ->where('location_number', $loc)
                ->update([
                    'article_qty' => DB::raw('coalesce(article_qty, 0) + ' . $det->qty),
                ]);

            // 5. Ambil qty setelah dikembalikan
            $lastQtyAfter = DB::table('warehouse_stock')
                ->where('site_code', $siteCode)
                ->where('article_code', $det->article_code)
                ->where('location_number', $loc)
                ->value('article_qty') ?? 0;

            $reverseMovements[] = [
                'movement_date'     => date('d-m-Y'),
                'artikel_code'      => $det->article_code,
                'artikel_desc'      => $det->article_desc,
                'movement_min'      => 0,
                'movement_plus'     => $det->qty,
                'movement_price'    => 0,
                'movement_transnno' => $tDnNumber . '(C)',
                'movement_type'     => 'DELETE DN GENERAL',
                'movement_desc'     => "Delete DN General: $tDnNumber",
                'movement_from'     => null,
                'partner_type'      => null,
                'movement_to'       => $dnHdr->customer_id,
                'created_by'        => $username,
                'created_at'        => date('Y-m-d H:i:s'),
                'site_code'         => $siteCode,
                'location_number'   => $loc,
                'last_qty'          => $lastQtyAfter,
            ];
        }

        // 6. Insert reverse movement
        if (!empty($reverseMovements)) {
            DB::table('warehouse_movement')->insert($reverseMovements);
        }

        // 7. Soft-delete movement asli (rename transnno-nya juga)
        DB::table('warehouse_movement')
            ->where('movement_transnno', $tDnNumber)
            ->where('movement_type', 'SURAT JALAN UMUM')
            ->update([
                'movement_transnno' => $tDnNumber . '(C)',
            ]);

        DB::commit();

        $title   = "Delete $this->title";
        $alert   = 'success';
        $message = "$title $tDnNumber Successfully Canceled";
        \LogActivity::addToLog($title, "username: $username Status $message");
        return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);

    } catch (Exception $e) {
        DB::rollBack();
        $title   = "Delete $this->title";
        $alert   = 'warning';
        $message = "$title $tDnNumber Failed to Cancel - " . $e->getMessage();
        \LogActivity::addToLog($title, "username: $username Status $message");
        return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);
    }
}

    public function destroyOld(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;       
        $tDnHdr = DB::table('temporary_dn_hdr')->where('id',$id)->first();

        $tDnNumber = $tDnHdr->tdn_number;
        $soNumber = $tDnHdr->so_number;

        DB::beginTransaction();
        try {
                $rowAffected=DB::table('temporary_dn_hdr')
                ->where('id',$id)
                ->update(
                    [
                        'status' => '4',
                        'tdn_number' => $tDnNumber."(C)",
                        'so_number' => $soNumber."(C)",
                        'origin_tdn_number' => $tDnNumber."(C)",
                        'reason' => "Cancel",
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                if($rowAffected>0){
                    DB::table('temporary_dn_det')
                    ->where('tdn_number',$tDnNumber)
                    ->update(
                    [
                        'tdn_number' => $tDnNumber."(C)",
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                    );
                    DB::commit();
                    $title ="Delete $this->title";
                    $alert  ="success";
                    $message  = "$title $tDnNumber Successfully Delete";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
                }else{
                    DB::rollBack();
                    $title ="Delete $this->title";
                    $alert  ="warning";
                    $message  = "$title $tDnNumber Failed to Delete";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);

                }

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Delete $this->title";
            $alert  ="warning";
            $message  = "$title $tDnNumber Failed to Delete";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }

    }

    public function closed(Request $request)
    {
        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);
        $tDnNumber = DB::table('temporary_dn_hdr')->where('id',$id)->value('tdn_number');
        $status = '3';
        DB::beginTransaction();
        try {
                $row_affected=DB::table('temporary_dn_hdr')
                ->where('id',$id)
                ->update(
                    [
                        'status' => $status,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );
                
                DB::commit();
                $title ="Close $this->title";
                $alert  ="success";
                $message  = "$title $tDnNumber Successfully Closed";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Close $this->title";
            $alert  ="warning";
            $message  = "$title $tDnNumber Failed to Close";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }
    }

    public function createDn(Request $request)
    {
        $username  = Auth::user()->username;
        $id        = Crypt::decryptString($request->id);
        $siteCode  = $this->siteCode;
        $location  = $this->locationFg;
 
        $tDnHeader = DB::table('temporary_dn_hdr')->where('id', $id)->first();
        $tDnNumber = $tDnHeader->tdn_number;
        $dnDate    = $tDnHeader->delivery_date;
        $soNumber  = $tDnHeader->so_number;
        $poNumber  = DB::table('sales_order_hdr')->where('so_code', $soNumber)->value('po_number');
        $dnNew     = "";
        $pesan     = "";
 
        // Cek apakah semua article di TDN ada di SO
        $cekArticle = DB::table('temporary_dn_det as a')
            ->leftJoin('temporary_dn_hdr as b', 'a.tdn_number', 'b.tdn_number')
            ->leftJoin('sales_order_det as c', function ($join) {
                $join->on('c.so_code', '=', 'b.so_number');
                $join->on('c.article_code', '=', 'a.article_code');
            })
            ->where('a.tdn_number', $tDnNumber)
            ->whereNull('c.article_code')
            ->count();
 
        // Cek apakah qty SO masih ada
        $cekSelisihQuery = DB::select("SELECT count(*) as jumlah from
            (select *,
            (select coalesce((select qty from sales_order_det where so_code = b.so_number and article_code = a.article_code),0)) as qty_so,
            coalesce((select sum(qty) as qty_delivery from delivery_det where delivery_number in (select delivery_number from delivery_hdr where so_number = b.so_number and status not in ('5','7')) and article_code = a.article_code group by article_code),0) as qty_delivery,
            (select coalesce((select qty from sales_order_det where so_code = b.so_number and article_code = a.article_code),0)-
            coalesce((select sum(qty) as qty_delivery from delivery_det where delivery_number in (select delivery_number from delivery_hdr where so_number = b.so_number and status not in ('5','7')) and article_code = a.article_code group by article_code),0)) as qty_selisih
            from temporary_dn_det a
            left join temporary_dn_hdr b on a.tdn_number = b.tdn_number
            where a.tdn_number = '$tDnNumber'
            ) as oki
            where qty_selisih <= 0");
 
        $adaSelisih = $cekSelisihQuery[0]->jumlah;
 
        if ($cekArticle > 0) {
            $pesan .= ", Article di TDN tidak ada di SO";
        }
        if ($adaSelisih > 0) {
            $pesan .= ", QTY SO sudah habis";
        }
 
        if ($pesan != '') {
            $title   = "Create DN $this->title";
            $alert   = "warning";
            $message = "$title dari $tDnNumber Gagal: $pesan";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);
        }
 
        $periodNomor = (int) explode('-', $dnDate)[1];
        $dnNew       = app('App\Http\Controllers\DeliveryController')->getLastCode('DN', $periodNomor);
 
        DB::beginTransaction();
        try {
            // Insert delivery_hdr dari data TDN
            $sqlHdr = "INSERT into delivery_hdr
                (delivery_number, origin_delivery_number, delivery_date, customer_id, so_number, po_number, status, note, created_by, updated_by, created_at, updated_at)
                select
                '$dnNew', '$dnNew', delivery_date, customer_id, so_number, '$poNumber', '4', note, '$username', '$username', '" . date('Y-m-d H:i:s') . "', '" . date('Y-m-d H:i:s') . "'
                from temporary_dn_hdr where tdn_number = '$tDnNumber'";
 
            // Insert delivery_det dari data TDN — qty_so dihitung sisa SO dikurangi DN yang sudah ada (TIDAK termasuk TDN ini karena sudah dikurangi lewat movement)
            $sqlDet = "INSERT into delivery_det
                (delivery_number, article_code, so_number, po_number, qty, uom, created_by, created_at, qty_so)
                select
                '$dnNew',
                a.article_code,
                '$soNumber',
                '$poNumber',
                a.qty,
                a.uom,
                '$username',
                '" . date('Y-m-d H:i:s') . "',
                (select coalesce((select qty from sales_order_det where so_code = b.so_number and article_code = a.article_code),0)
                - coalesce((select sum(qty) from delivery_det where delivery_number in (select delivery_number from delivery_hdr where so_number = b.so_number and status not in ('5','7')) and article_code = a.article_code),0)) as qty_so
                from temporary_dn_det a
                left join temporary_dn_hdr b on b.tdn_number = a.tdn_number
                where a.tdn_number = '$tDnNumber'";
 
            $rowAffected = DB::select($sqlHdr);
 
            if ($rowAffected !== false) {
                DB::select($sqlDet);
 
                // Update referensi warehouse_movement dari TDN ke DN baru — TIDAK kurangi stock lagi
                DB::table('warehouse_movement')
                    ->where('movement_transnno', $tDnNumber)
                    ->where('movement_type', 'TDN')
                    ->update([
                        'movement_transnno' => $dnNew,
                        'movement_type'     => 'Delivery',
                        'movement_desc'     => $dnNew,
                    ]);
 
                // Update TDN jadi CLOSED dan catat nomor DN baru
                DB::table('temporary_dn_hdr')
                    ->where('tdn_number', $tDnNumber)
                    ->update([
                        'status'          => '3',
                        'delivery_number' => $dnNew,
                        'updated_by'      => $username,
                        'updated_at'      => date('Y-m-d H:i:s'),
                    ]);
 
                $idDelivery = DB::table('delivery_hdr')->where('delivery_number', $dnNew)->value('id');
                $idKu       = Crypt::encryptString($idDelivery);
 
                DB::commit();
 
                $title   = "Create DN $this->title";
                $alert   = "success";
                $message = "$title $dnNew from $tDnNumber Successfully Created";
                \LogActivity::addToLog($title, "username: $username Status $message");
                return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message, 'idDelivery' => $idKu]);
 
            } else {
                DB::rollBack();
 
                // Rollback nomor DN yang sudah diambil
                $noAkhirDn = explode('/', $dnNew)[4];
                DB::table('master_code')
                    ->where('code_key', 'DN')
                    ->where('code_number', $noAkhirDn)
                    ->update([
                        'code_number' => $noAkhirDn - 1,
                        'updated_by'  => $username,
                        'updated_at'  => date('Y-m-d H:i:s'),
                    ]);
 
                $title   = "Create DN $this->title";
                $alert   = "warning";
                $message = "$title $dnNew from $tDnNumber Failed - Insert HDR Error";
                \LogActivity::addToLog($title, "username: $username Status $message");
                return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);
            }
 
        } catch (Exception $e) {
            DB::rollBack();
            $title   = "Create DN $this->title";
            $alert   = "warning";
            $message = "$title $dnNew from $tDnNumber Failed - Exception: " . $e->getMessage();
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);
        }
    }

    public function createDnOld(Request $request)
    {

        /*
            pada saat pembuatan DN dari temporary cek dulu QTY SO nya apakah sudah sesuai atau belum
            dengan kedaan pasa saat DN dibuat
            Cek apakah article yang ada di temporary semua ada di SO ?
            Cek apakah qty temporary melebihi sisa SO

        */

        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);
        $tDnHeader = DB::table('temporary_dn_hdr')->where('id',$id)->first();
        $tDnNumber = $tDnHeader->tdn_number; 
        $dnDate = $tDnHeader->delivery_date;
        $soNumber = $tDnHeader->so_number;
        $poNumber = db::table('sales_order_hdr')->where('so_code',$soNumber)->value('po_number');
        $dnNew ="";
        $pesan ="";
        $hasilPosting = "";
        $idDelivery = "";
        $idKu = "";

        /*
            Cek apakah article yang ada di temporary semua ada di SO ?
        */

        $cekArticle = DB::table('temporary_dn_det as a')
        ->leftJoin('temporary_dn_hdr as b','a.tdn_number','b.tdn_number')
        ->leftJoin('sales_order_det as c', function ($join) {
            $join->on('c.so_code', '=', 'b.so_number');
            $join->on('c.article_code', '=', 'a.article_code');
        })
        ->where('a.tdn_number',$tDnNumber)
        ->where('c.article_code','=',null)
        ->count();

        /*
            Cek apakah qty SO masih ada atau sudah kosong
        */

        $cekSelisihQuery = DB::select("SELECT count(*) as jumlah from
        (select *, 
        (select coalesce((select qty from sales_order_det where so_code = b.so_number and article_code = a.article_code),0)) as qty_so,
        coalesce((select sum(qty) as qty_delivery from delivery_det where delivery_number in (select delivery_number from delivery_hdr where so_number = b.so_number and status not in ('5','7')) and article_code = a.article_code group by article_code),0) as qty_delivery,
        (select coalesce((select qty from sales_order_det where so_code = b.so_number and article_code = a.article_code),0)-
        coalesce((select sum(qty) as qty_delivery from delivery_det where delivery_number in (select delivery_number from delivery_hdr where so_number = b.so_number and status not in ('5','7')) and article_code = a.article_code group by article_code),0)) as qty_selisih
        from temporary_dn_det a
        left join temporary_dn_hdr b on a.tdn_number = b.tdn_number
        where a.tdn_number = '$tDnNumber'
        ) as oki
        where qty_selisih <= 0
        ");

        $adaSelisih = $cekSelisihQuery[0]->jumlah;

        if ($cekArticle > 0 ){
            $pesan .= ", Article di DN tidak ada di SO";
        }

        if ($adaSelisih > 0 ){
            $pesan .= ", QTY SO sudah habis";
        }

        // dd($pesan);

        if($pesan == ''){

            $periodNomor=(int)explode('-', $dnDate)[1];
            $dnNew = app('App\Http\Controllers\DeliveryController')->getLastCode('DN',$periodNomor);
            
            DB::beginTransaction();
            try {
                $sqlHdr = "INSERT into delivery_hdr 
                (
                    delivery_number,
                    origin_delivery_number,
                    delivery_date,
                    customer_id,
                    so_number,
                    po_number,
                    status,
                    note,
                    created_by,
                    updated_by,
                    created_at,
                    updated_at
                )
                select 
                '$dnNew',
                '$dnNew',
                delivery_date,
                customer_id,
                so_number,
                '$poNumber',
                '1',
                note,
                '$username',
                '$username',
                '".date('Y-m-d H:i:s')."',
                '".date('Y-m-d H:i:s')."'
                from temporary_dn_hdr where tdn_number = '$tDnNumber'";
            
                $sqlDet="INSERT into delivery_det
                (
                    delivery_number,
                    article_code,
                    so_number,
                    po_number,
                    qty,
                    uom,
                    created_by,
                    created_at,
                    qty_so
                )
                select 
                    '$dnNew',
                    article_code,
                    '$soNumber',
                    '$poNumber',
                    qty,
                    uom,
                    '$username',
                    '".date('Y-m-d H:i:s')."',
                    (select coalesce((select qty from sales_order_det where so_code = b.so_number and article_code = a.article_code),0)-
                    coalesce((select sum(qty) as qty_delivery from delivery_det where delivery_number in (select delivery_number from delivery_hdr where so_number = b.so_number) and article_code = a.article_code group by article_code),0)) as qty_so
                from temporary_dn_det a
                left join temporary_dn_hdr b on b.tdn_number = a.tdn_number
                where a.tdn_number = '$tDnNumber'";

                $rowAffected =  DB::select($sqlHdr);
                if ($rowAffected){
                    DB::select($sqlDet);
                    $row_affected=DB::table('temporary_dn_hdr')
                    ->where('tdn_number',$tDnNumber)
                    ->update(
                        [
                            'status' => '3',
                            'delivery_number' => $dnNew,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );

                    $idDelivery = db::table('delivery_hdr')->where('delivery_number',$dnNew)->value('id');

                    $idKu = Crypt::encryptString($idDelivery);

                    $hasilPosting = app('App\Http\Controllers\DeliveryController')->postingFromOther($idDelivery);
                    
                    DB::commit();
                    $title ="Create DN $this->title";
                    $alert  ="success";
                    $message  = "$title $dnNew from $tDnNumber Successfully Create DN";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message,'hasilPosting'=>$hasilPosting,'idDelivery' => $idKu]);
                }else{
                    DB::rollBack();
                    // 'DN/ASN/24/04/2379
                    $noAkhirDn = explode('/', $dnNew)[4];
                    $row_affected=DB::table('master_code')
                    ->where('code_key','DN')
                    ->where('code_number',$noAkhirDn)
                    ->update(
                        [
                            'code_number' => $noAkhirDn-1,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );

                    $title ="Create DN $this->title";
                    $alert  ="warning";
                    $message  = "$title $dnNew from $tDnNumber Failed Create DN Insert to HDR";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message,'hasil'=>$hasilPosting,'idDelivery' => $idKu]);
                }
            } catch (Exception $e) {
                DB::rollBack();
                $title ="Create DN $this->title";
                $alert  ="warning";
                $message  = "$title $dnNew from $tDnNumber Failed2 Create DN Error Query";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message,'hasil'=>$hasilPosting,'idDelivery' => $idKu]);
            }
        }else{
            $title ="Create DN $this->title";
            $alert  ="warning";
            $message  = "$title $dnNew from $tDnNumber Failed3 Create DN Warning message";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message.$pesan,'hasil'=>$hasilPosting,'idDelivery' => $idKu]);
        }
    }

   public function list(Request $request)
    {
        // status: 1=NEW 2=APPROVED 3=POSTED 4=CANCELED

        $username       = Auth::user()->username;
        $searchDn       = strtolower($request->searchDn);
        $searchStatus   = $request->searchStatus;
        $deliveryDate   = $request->deliveryDate;
        $searchCustomer = $request->searchCustomer;
        $fromDate       = "";
        $toDate         = "";

        if ($deliveryDate) {
            $date = explode("to", $deliveryDate);
            if (count($date) > 1) {
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate   = implode("/", array_reverse(explode("-", trim($date[1]))));
            } else {
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate   = $fromDate;
            }
        }

        $data = DB::table('dn_general_hdr')
            ->leftJoin('third_party', 'third_party.kode', '=', 'dn_general_hdr.customer_id')
            ->where(function ($query) use ($searchDn, $searchStatus, $deliveryDate, $fromDate, $toDate, $searchCustomer) {
                $searchDn ? $query->where('dn_general_hdr.tdn_number', 'ilike', '%' . $searchDn . '%') : '';
                $searchStatus ? $query->where('dn_general_hdr.status', $searchStatus) : '';
                $deliveryDate ? $query->whereBetween(DB::raw("to_date(dn_general_hdr.delivery_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
                $searchCustomer ? $query->where('dn_general_hdr.customer_id', $searchCustomer) : '';
            })
            ->where('dn_general_hdr.status', '!=', '4')
            ->select('dn_general_hdr.*', DB::raw("concat(third_party.kode,'-',third_party.nama) as customer_name"))
            ->orderBy('dn_general_hdr.id', 'desc');   // tanpa ->get() agar Yajra filter di DB

        return Datatables::of($data)
            ->filterColumn('customer_name', function ($query, $keyword) {
                $query->whereRaw("concat(third_party.kode,'-',third_party.nama) ilike ?", ["%{$keyword}%"]);
            })
            ->orderColumn('customer_name', "third_party.nama \$1")
            ->addColumn('action', function ($data) {
                $buttons = '<div class="d-inline-flex">
                                <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                    <i data-feather="menu"></i>
                                </a>';
                $buttons .= '<div class="dropdown-menu dropdown-menu-right">';

                if ($data->status == '1') {
                    $buttons .= '<a href="' . route('dnGeneral.edit', ['id' => Crypt::encryptString($data->id)]) . '" class="dropdown-item">
                                    <i data-feather="file-text"></i> Revisi
                                </a>';
                }

                $buttons .= '<a href="' . route('dnGeneral.print', ['id' => Crypt::encryptString($data->id)]) . '" target="_blank" class="dropdown-item">
                                <i data-feather="printer"></i> Print
                            </a>';

                $buttons .= '<a href="' . route('dnGeneral.show', ['id' => Crypt::encryptString($data->id)]) . '" class="dropdown-item">
                                <i data-feather="list"></i> Detail
                             </a>';

                if ($data->status != '3') {
                    $buttons .= "<a href='javascript:;'
                                    class='dropdown-item'
                                    data-size='sm'
                                    data-ajax-delete='true'
                                    data-confirm='Are You Sure want to Delete?|This action can not be undone. Do you want to continue?'
                                    data-confirm-yes='document.getElementById(\"" . "delete-form-" . $data->id . "\").submit();'
                                    data-modal-id='" . $data->id . "'
                                    id='deleteButton'
                                    data-url='" . route('dnGeneral.destroy', ['id' => Crypt::encryptString($data->id)]) . "'>
                                    <i data-feather='trash-2' class='feather-14-red'></i>
                                    <span>" . __('Cancel') . "</span>
                                </a>";
                }

                $buttons .= '</div></div>';
                return $buttons;
            })
            ->addColumn('status', function ($data) {
                $badges   = ['badge-primary', 'badge-info', 'badge-success', 'badge-warning', 'badge-danger', 'badge-dark', 'badge-secondary', 'badge-secondary'];
                $statusPr = ['NEW', 'APPROVED', 'POSTED', 'CANCELED'];
                return "<div class='badge " . $badges[$data->status - 1] . "'>" . $statusPr[$data->status - 1] . "</div>";
            })
            ->addColumn('dn_type', function ($data) {
                $typeMap = [
                    'rm'    => "<span class='badge badge-danger'>RETURN NG RM</span>",
                    'ot'    => "<span class='badge badge-info'>RETURN OT</span>",
                    'other' => "<span class='badge badge-warning'>OTHER</span>",
                ];
                return $typeMap[$data->dn_type] ?? "<span class='badge badge-secondary'>" . strtoupper($data->dn_type) . "</span>";
            })
            ->rawColumns(['action', 'status', 'dn_type', 'tdn_number'])
            ->make(true);
    }

    public function listDetail(Request $request)
    {
        $username       = Auth::user()->username;
        $searchDn       = strtolower($request->searchDn);
        $searchStatus   = $request->searchStatus;
        $deliveryDate   = $request->deliveryDate;
        $searchCustomer = $request->searchCustomer;
        $fromDate       = "";
        $toDate         = "";

        if ($deliveryDate) {
            $date = explode("to", $deliveryDate);
            if (count($date) > 1) {
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate   = implode("/", array_reverse(explode("-", trim($date[1]))));
            } else {
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate   = $fromDate;
            }
        }

        $data = DB::table('dn_general_det')
            ->leftJoin('dn_general_hdr', 'dn_general_hdr.tdn_number', 'dn_general_det.tdn_number')
            ->leftJoin('article', 'article.article_code', 'dn_general_det.article_code')
            ->leftJoin('third_party', 'third_party.kode', 'dn_general_hdr.customer_id')
            ->where(function ($query) use ($searchDn, $searchStatus, $deliveryDate, $fromDate, $toDate, $searchCustomer) {
                $searchDn ? $query->where('dn_general_hdr.tdn_number', 'ilike', '%' . $searchDn . '%') : '';
                $searchStatus ? $query->where('dn_general_hdr.status', $searchStatus) : '';
                $deliveryDate ? $query->whereBetween(DB::raw("to_date(dn_general_hdr.delivery_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
                $searchCustomer ? $query->where('dn_general_hdr.customer_id', $searchCustomer) : '';
            })
            ->where('dn_general_hdr.status', '!=', '4')
            ->select(
                'dn_general_det.tdn_number',
                'dn_general_det.qty',
                'dn_general_det.uom',
                'dn_general_det.stock_on_send',
                'dn_general_det.created_by',
                'dn_general_det.created_at',
                'dn_general_det.updated_by',
                'dn_general_det.updated_at',
                DB::raw("coalesce(article.article_alternative_code, dn_general_det.article_code) as article_alternative_code"),
                DB::raw("coalesce(article.article_desc, dn_general_det.article_desc) as article_desc"),
                'dn_general_hdr.dn_type',
                'dn_general_hdr.delivery_date',
                'dn_general_hdr.perihal',
                'dn_general_hdr.note',
                'dn_general_hdr.status',
                DB::raw("concat(third_party.kode,'-',third_party.nama) as customer_name")
            )
            ->orderBy('dn_general_det.id')
            ->orderBy('dn_general_det.tdn_number');   // tanpa ->get()

        return Datatables::of($data)
            ->filterColumn('customer_name', function ($query, $keyword) {
                $query->whereRaw("concat(third_party.kode,'-',third_party.nama) ilike ?", ["%{$keyword}%"]);
            })
            ->filterColumn('article_alternative_code', function ($query, $keyword) {
                $query->whereRaw("coalesce(article.article_alternative_code, dn_general_det.article_code) ilike ?", ["%{$keyword}%"]);
            })
            ->filterColumn('article_desc', function ($query, $keyword) {
                $query->whereRaw("coalesce(article.article_desc, dn_general_det.article_desc) ilike ?", ["%{$keyword}%"]);
            })
            ->addColumn('status', function ($data) {
                $badges   = ['badge-primary', 'badge-info', 'badge-success', 'badge-warning', 'badge-danger', 'badge-dark', 'badge-secondary', 'badge-secondary'];
                $statusPr = ['NEW', 'APPROVED', 'POSTED', 'CANCELED'];
                return "<div class='badge " . $badges[$data->status - 1] . "'>" . $statusPr[$data->status - 1] . "</div>";
            })
            ->addColumn('dn_type', function ($data) {
                $typeMap = [
                    'rm'    => "<span class='badge badge-danger'>RETURN NG RM</span>",
                    'ot'    => "<span class='badge badge-info'>RETURN OT</span>",
                    'other' => "<span class='badge badge-warning'>OTHER</span>",
                ];
                return $typeMap[$data->dn_type] ?? "<span class='badge badge-secondary'>" . strtoupper($data->dn_type) . "</span>";
            })
            ->rawColumns(['status', 'dn_type'])
            ->make(true);
    }

    public function print(Request $request)
{
    $id = Crypt::decryptString($request->id);

    $dnHdr = DB::table('dn_general_hdr')
        ->where('id', $id)
        ->first();

    $tDnNumber = $dnHdr->tdn_number;

    $details = DB::table('dn_general_det')
    ->leftJoin('article', 'article.article_code', '=', 'dn_general_det.article_code')
    ->select(
        'dn_general_det.qty',
        'dn_general_det.uom',
        DB::raw("coalesce(article.article_alternative_code, dn_general_det.article_code) as article_alternative_code"),
        DB::raw("coalesce(article.article_desc, dn_general_det.article_desc) as article_desc"),
    )
    ->where('dn_general_det.tdn_number', $tDnNumber)
    ->orderBy('dn_general_det.id')
    ->get();

    $customer = DB::table('third_party')
        ->where('kode', $dnHdr->customer_id)
        ->first();

    $data['dnHdr']    = $dnHdr;
    $data['details']  = $details;
    $data['customer'] = $customer;
    $data['no']       = 0;
    $data['title']    = $tDnNumber;

    return view('dnGeneral.print', $data);
}

    public function getArticle(Request $request){
        
        $custCode = $request->custCode;
        $data= DB::table('article') 
        ->whereIn('article.article_code', function($query) use ($custCode) {
            $query->select('article_code')
            ->from('bom_hdr') 
            ->where('status','3')
            ->where('customer',$custCode);
        })
        ->where('third_party',$custCode)
        ->where('article_type','FG')
        ->orderBy('article_desc')
        ->get();

        $output='';
        $output .='<option value="">Choose article</option>';

        foreach ($data as $row){
            $output .='<option value="'.$row->article_code.'" data-uom="'.$row->uom.'">'.$row->article_alternative_code.'-'. $row->article_desc.'</option>';
        }

        return $output;

    }


    public function articlesByType(Request $request)
{
    $gudangMap = [
        'rm'    => '037',
        'ot'    => '008',
        'other' => '011',
    ];

    $gudang = isset($gudangMap[$request->type]) ? $gudangMap[$request->type] : null;

    if (!$gudang || !$request->customer) return response()->json([]);

    return DB::table('warehouse_stock as s')
        ->join('article as a',     's.article_code', '=', 'a.article_code')
        ->join('third_party as t', 'a.third_party',  '=', 't.kode')
        ->where('s.location_number', $gudang)
        ->where('s.article_qty', '>', 0)
        ->where('t.kode', $request->customer)
        ->select(
            'a.article_code                as code',
            'a.article_alternative_code    as alt_code',  // ← tambah ini
            'a.article_desc                as name',
            's.article_qty                 as qty',
            'a.uom'
        )
        ->orderBy('a.article_alternative_code')
        ->get();
}

}
