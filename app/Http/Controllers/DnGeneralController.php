<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use DB;
use AppHelpers;

class DnGeneralController extends Controller
{
    private $title;
    private $moduleCode;
    private $decimalPlaces;

    /** Site & gudang asal per tipe DN */
    private $siteCode = 'HO';

    private $gudangMap = [
    'rm'    => '037',   // Return NG RM   -> Supplier
    'ot'    => '008',   // Return OT      -> Customer
    'other' => '011',   // Other          -> bebas (third_party)
    'box'   => '011',   // Box Kosong     -> TODO: konfirmasi kode lokasi asli
    'troli' => '011',   // Troli Kosong   -> TODO: konfirmasi kode lokasi asli
    'trial' => '011',   // Trial & Sample -> TODO: konfirmasi kode lokasi asli
    'ms'    => '011',   // Material Support -> TODO: konfirmasi kode lokasi asli
    'cs'    => '011',   // Chemical Support -> TODO: konfirmasi kode lokasi asli
    'lb3'    => '011',   // Chemical Support -> TODO: konfirmasi kode lokasi asli
    'lnb3'  => '011',   // Limbah Non B3  -> TODO: konfirmasi kode lokasi asli
    'rig'   => '011',   // Return Isi Gas -> TODO: konfirmasi kode lokasi asli
];

    /** Jenis movement dipakai konsisten di store/update/destroy */
    const MOVEMENT_TYPE        = 'SURAT JALAN UMUM';
    const MOVEMENT_TYPE_DELETE = 'DELETE SURAT JALAN UMUM';

    /** Kode artikel manual (tanpa stok) */
    const MANUAL_CODE = 'OTHER';

    /** Dept yang nomornya pakai DN-UMUM, selain ini SJ-UMUM. Dept ini juga saling lihat di list. */
    private $deptDnUmum = ['011', '014', '005'];

    /** Peta prefix -> code_key di master_code (counter terpisah) */
    private $codeKeyMap = [
        'DN-UMUM' => 'DN-GENERAL',
        'SJ-UMUM' => 'SJ-GENERAL',
    ];

    /** Role yang bisa lihat semua dept (nama harus PERSIS sama dengan tabel roles) */
    private $rolesSeeAll = ['Superuser', 'Accounting'];

    /** Peta status tunggal untuk seluruh controller */
    private $statusMap = [
        '1' => 'NEW',
        '2' => 'APPROVED',
        '3' => 'CLOSED',
        '4' => 'CANCELED',
    ];

    public function __construct()
    {
        $this->title         = "DN General";
        $this->moduleCode    = "DN-GENERAL";
        $this->decimalPlaces = config('globalParam.decimal');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers — Dept & Visibility
    |--------------------------------------------------------------------------
    */

   /** Ambil SEMUA dept user yang login (dari pivot user_dept), ternormalisasi 3 digit */
private function resolveDepts()
{
    $rows = DB::table('user_dept')
        ->where('username', Auth::user()->username)
        ->pluck('dept')
        ->map(function ($d) {
            $d = trim((string) $d);
            return $d === '' ? null : str_pad($d, 3, '0', STR_PAD_LEFT);
        })
        ->filter()      // buang null
        ->unique()
        ->values()
        ->all();

    return $rows; // array, bisa kosong []
}

/** Prefix nomor: kalau salah satu dept user masuk grup DN-UMUM -> DN-UMUM, selain itu SJ-UMUM */
private function resolvePrefix()
{
    $depts = $this->resolveDepts();

    foreach ($depts as $d) {
        if (in_array($d, $this->deptDnUmum, true)) {
            return 'DN-UMUM';
        }
    }

    return 'SJ-UMUM';
}

/**
 * Dept apa saja yang boleh dilihat user ini.
 * return null = tanpa filter (lihat semua)
 */
private function visibleDepts()
{
    // Superuser & Accounting lihat semua
    if (Auth::user()->hasAnyRole($this->rolesSeeAll)) {
        return null;
    }

    $depts = $this->resolveDepts();

    // Fail-closed: user tanpa dept tidak lihat apa-apa
    if (empty($depts)) {
        return ['__NONE__'];
    }

    // Kalau user punya dept di grup DN-UMUM, seluruh grup DN-UMUM ikut terlihat
    $result = $depts;
    foreach ($depts as $d) {
        if (in_array($d, $this->deptDnUmum, true)) {
            $result = array_merge($result, $this->deptDnUmum);
            break;
        }
    }

    return array_values(array_unique($result));
}

    /**
     * Terapkan filter dept ke query list.
     * Dept diambil dari user pembuat (join users lewat created_by), bukan kolom di dn_general_hdr.
     */
    private function applyDeptFilter($query)
{
    $depts = $this->visibleDepts();

    if ($depts === null) {
        return $query; // Superuser / Accounting: tanpa filter
    }

    $placeholders = implode(',', array_fill(0, count($depts), '?'));

    // created_by cocok dengan user_dept.username; lpad menyamakan '5' vs '005'
    return $query->whereExists(function ($q) use ($placeholders, $depts) {
        $q->select(DB::raw(1))
          ->from('user_dept')
          ->whereColumn('user_dept.username', 'dn_general_hdr.created_by')
          ->whereRaw("lpad(trim(user_dept.dept),3,'0') in ({$placeholders})", $depts);
    });
}

    /**
     * Pastikan header ini boleh diakses user sekarang.
     * Dipakai di show/edit/print/update/destroy/closed supaya tidak bisa ditembus lewat URL.
     */
    private function assertDeptAccess($dnHdr)
{
    $depts = $this->visibleDepts();

    if ($depts === null) {
        return; // Superuser / Accounting
    }

    $creatorDepts = DB::table('user_dept')
        ->where('username', $dnHdr->created_by)
        ->pluck('dept')
        ->map(fn ($d) => str_pad(trim((string) $d), 3, '0', STR_PAD_LEFT))
        ->all();

    // Boleh akses kalau ada irisan antara dept pembuat & dept yang terlihat user
    if (empty(array_intersect($creatorDepts, $depts))) {
        abort(403, 'Anda tidak punya akses ke dokumen ini.');
    }
}

    /*
    |--------------------------------------------------------------------------
    | Helpers — Lain-lain
    |--------------------------------------------------------------------------
    */

    /** true bila artikel manual (OTHER) -> tidak punya stok */
    private function isManualArticle($code)
    {
        return strtoupper(trim((string) $code)) === self::MANUAL_CODE;
    }

    /** Tentukan partner_type dari tipe DN (konstan per-DN, bukan per artikel) */
    private function resolvePartnerType($dnType, $customerId)
    {
        if ($dnType === 'rm') {
            return 'SUPP';
        }
        if ($dnType === 'ot') {
            return 'CUST';
        }

        return strtoupper((string) DB::table('third_party')
            ->where('kode', $customerId)
            ->value('third_party_type'));
    }

    private function statusLabel($status)
    {
        return $this->statusMap[$status] ?? '-';
    }

    private function statusBadgeHtml($status)
    {
        $badges = [
            '1' => 'badge-primary',
            '2' => 'badge-info',
            '3' => 'badge-success',
            '4' => 'badge-danger',
        ];
        $class = $badges[$status] ?? 'badge-secondary';
        return "<div class='badge {$class}'>" . $this->statusLabel($status) . "</div>";
    }

    private function dnTypeBadgeHtml($type)
{
    $map = [
        'rm'    => "<span class='badge badge-danger'>RETURN NG RM</span>",
        'ot'    => "<span class='badge badge-info'>RETURN OT</span>",
        'box'   => "<span class='badge badge-secondary'>BOX KOSONG</span>",
        'troli' => "<span class='badge badge-secondary'>TROLI KOSONG</span>",
        'trial' => "<span class='badge badge-primary'>TRIAL & SAMPLE</span>",
        'ms'    => "<span class='badge badge-light-primary'>MATERIAL SUPPORT</span>",
        'cs'    => "<span class='badge badge-warning'>CHEMICAL SUPPORT</span>",
        'lb3'   => "<span class='badge badge-dark'>LIMBAH B3</span>"
        'lnb3'  => "<span class='badge badge-dark'>LIMBAH NON B3</span>",
        'rig'   => "<span class='badge badge-info'>RETURN ISI GAS</span>",
        'other' => "<span class='badge badge-warning'>OTHER</span>",
    ];
    return $map[$type] ?? "<span class='badge badge-secondary'>" . strtoupper((string) $type) . "</span>";
}

    /** Ambil qty stok terkini sebuah artikel di lokasi tertentu */
    private function currentStock($articleCode, $location)
    {
        return (float) (DB::table('warehouse_stock')
            ->where('site_code', $this->siteCode)
            ->where('article_code', $articleCode)
            ->where('location_number', $location)
            ->value('article_qty') ?? 0);
    }

    

    /*
    |--------------------------------------------------------------------------
    | Kolom DataTables
    |--------------------------------------------------------------------------
    */

    public function getTableColoumn()
    {
        $kolom = [
            ['data' => 'action',        'name' => 'action',        'title' => 'action', 'orderable' => false, 'searchable' => false],
            ['data' => 'tdn_number',    'name' => 'tdn_number',    'title' => 'Number'],
            ['data' => 'status',        'name' => 'status',        'title' => 'Status'],
            ['data' => 'dn_type',       'name' => 'dn_type',       'title' => 'Type'],
            ['data' => 'delivery_date', 'name' => 'delivery_date', 'title' => 'Delivery Date'],
            ['data' => 'perihal',       'name' => 'perihal',       'title' => 'Perihal'],
            ['data' => 'customer_name', 'name' => 'customer_name', 'title' => 'Customer'],
            ['data' => 'note',          'name' => 'note',          'title' => 'Note'],
            ['data' => 'created_by',    'name' => 'created_by',    'title' => 'Created By'],
            ['data' => 'created_at',    'name' => 'created_at',    'title' => 'Created Date'],
            ['data' => 'updated_by',    'name' => 'updated_by',    'title' => 'Updated By'],
            ['data' => 'updated_at',    'name' => 'updated_at',    'title' => 'Updated Date'],
        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnDetail()
    {
        $kolom = [
            ['data' => 'tdn_number',               'name' => 'tdn_number',               'title' => 'Delivery Number'],
            ['data' => 'dn_type',                  'name' => 'dn_type',                  'title' => 'Type'],
            ['data' => 'delivery_date',            'name' => 'delivery_date',            'title' => 'Delivery Date'],
            ['data' => 'perihal',                  'name' => 'perihal',                  'title' => 'Perihal'],
            ['data' => 'customer_name',            'name' => 'customer_name',            'title' => 'Customer'],
            ['data' => 'article_alternative_code', 'name' => 'article_alternative_code', 'title' => 'Article Code'],
            ['data' => 'article_desc',             'name' => 'article_desc',             'title' => 'Description'],
            ['data' => 'qty',                      'name' => 'qty',                      'title' => 'Qty'],
            ['data' => 'uom',                      'name' => 'uom',                      'title' => 'UOM'],
            ['data' => 'stock_on_send',            'name' => 'stock_on_send',            'title' => 'Stock Saat Kirim'],
            ['data' => 'status',                   'name' => 'status',                   'title' => 'Status'],
            ['data' => 'note',                     'name' => 'note',                     'title' => 'Note'],
            ['data' => 'created_by',               'name' => 'created_by',               'title' => 'Created By'],
            ['data' => 'created_at',               'name' => 'created_at',               'title' => 'Created Date'],
            ['data' => 'updated_by',               'name' => 'updated_by',               'title' => 'Updated By'],
            ['data' => 'updated_at',               'name' => 'updated_at',               'title' => 'Updated Date'],
        ];
        return json_encode($kolom, true);
    }

    /*
    |--------------------------------------------------------------------------
    | Views
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $data['title']       = $this->title;
        $data['kolom']       = $this->getTableColoumn();
        $data['kolomDetail'] = $this->getTableColoumnDetail();
        $data['status']      = ['1' => 'NEW', '2' => 'APPROVED', '3' => 'CLOSED', '4' => 'CANCELED'];

        $data['customers'] = DB::table('third_party')
            ->orderBy('nama')
            ->get();

        return view("dnGeneral.index", $data);
    }

    public function create(Request $request)
    {
        $data['title']    = "Create {$this->title}";
        $data['subtitle'] = "Create {$this->title}";

        $data['suppliers'] = DB::table('third_party')
            ->where('third_party_type', 'supp')
            ->orderBy('nama')->get();

        $data['customers'] = DB::table('third_party')
            ->where('third_party_type', 'cust')
            ->orderBy('nama')->get();

        $data['allParties'] = DB::table('third_party')
            ->orderBy('nama')->get();

        $data['currentDate'] = date('d-m-Y');

        return view("dnGeneral.create", $data);
    }

    public function show(Request $request)
    {
        $id = Crypt::decryptString($request->id);

        $data['title']    = "Detail {$this->title}";
        $data['subtitle'] = "Detail {$this->title}";

        $dnHdr = DB::table('dn_general_hdr')
            ->leftJoin('third_party', 'third_party.kode', '=', 'dn_general_hdr.customer_id')
            ->where('dn_general_hdr.id', $id)
            ->select(
                'dn_general_hdr.*',
                DB::raw("concat(third_party.kode, ' - ', third_party.nama) as customer_name"),
                'third_party.alamat_kirim_1'
            )
            ->first();

        if (!$dnHdr) {
            abort(404);
        }

        $this->assertDeptAccess($dnHdr);

        $details = DB::table('dn_general_det')
            ->leftJoin('article', 'article.article_code', '=', 'dn_general_det.article_code')
            ->where('dn_general_det.tdn_number', $dnHdr->tdn_number)
            ->select(
                'dn_general_det.*',
                DB::raw("coalesce(article.article_alternative_code, dn_general_det.article_code) as article_alternative_code"),
                DB::raw("coalesce(article.article_desc, dn_general_det.article_desc) as article_desc")
            )
            ->orderBy('dn_general_det.id')
            ->get();

        $data['dnHdr']   = $dnHdr;
        $data['details'] = $details;
        $data['status']  = $this->statusLabel($dnHdr->status);
        $data['no']      = 0;

        return view('dnGeneral.show', $data);
    }

    public function edit(Request $request)
    {
        $id = Crypt::decryptString($request->id);

        $data['title']    = "Edit {$this->title}";
        $data['subtitle'] = "Edit {$this->title}";

        $header = DB::table('dn_general_hdr')->where('id', $id)->first();

        if (!$header) {
            abort(404);
        }

        $this->assertDeptAccess($header);

        $tDnNumber = $header->tdn_number;

        $data['header']  = $header;
        $data['details'] = DB::table('dn_general_det')
            ->leftJoin('article', 'article.article_code', '=', 'dn_general_det.article_code')
            ->where('dn_general_det.tdn_number', $tDnNumber)
            ->select(
                'dn_general_det.*',
                DB::raw("coalesce(article.article_alternative_code, dn_general_det.article_code) as article_alternative_code"),
                DB::raw("coalesce(article.article_desc, dn_general_det.article_desc) as article_desc_label")
            )
            ->orderBy('dn_general_det.id')
            ->get();

        $data['suppliers'] = DB::table('third_party')
            ->where('third_party_type', 'supp')->orderBy('nama')->get();
        $data['customers'] = DB::table('third_party')
            ->where('third_party_type', 'cust')->orderBy('nama')->get();
        $data['allParties'] = DB::table('third_party')->orderBy('nama')->get();

        $data['status'] = $this->statusLabel($header->status);

        return view('dnGeneral.edit', $data);
    }

    public function print(Request $request)
    {
        $id = Crypt::decryptString($request->id);

        $dnHdr = DB::table('dn_general_hdr')->where('id', $id)->first();

        if (!$dnHdr) {
            abort(404);
        }

        $this->assertDeptAccess($dnHdr);

        $tDnNumber = $dnHdr->tdn_number;

        $details = DB::table('dn_general_det')
            ->leftJoin('article', 'article.article_code', '=', 'dn_general_det.article_code')
            ->select(
                'dn_general_det.qty',
                'dn_general_det.uom',
                DB::raw("coalesce(article.article_alternative_code, dn_general_det.article_code) as article_alternative_code"),
                DB::raw("coalesce(article.article_desc, dn_general_det.article_desc) as article_desc")
            )
            ->where('dn_general_det.tdn_number', $tDnNumber)
            ->orderBy('dn_general_det.id')
            ->get();

        $customer = DB::table('third_party')->where('kode', $dnHdr->customer_id)->first();

        $data['dnHdr']    = $dnHdr;
        $data['details']  = $details;
        $data['customer'] = $customer;
        $data['no']       = 0;
        $data['title']    = $tDnNumber;

        return view('dnGeneral.print', $data);
    }

    /*
    |--------------------------------------------------------------------------
    | Penomoran
    |--------------------------------------------------------------------------
    */

    public function getLastCode($key, $prefix = null, $deliveryDate = null)
    {
        $prefix = $prefix ?? $key;

        $affected = DB::table('master_code')
            ->where('code_key', $key)
            ->update([
                'code_number' => DB::raw('code_number + 1'),
                'updated_by'  => Auth::user()->username,
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);

        // Guard: kalau row tidak ada, jangan diam-diam menghasilkan nomor 00000
        if ($affected < 1) {
            throw new \Exception("Code key '{$key}' tidak ditemukan di master_code.");
        }

        $newCode = DB::table('master_code')->where('code_key', $key)->value('code_number');
        $newCode = str_pad($newCode, 5, "0", STR_PAD_LEFT);

        // Ambil bulan & tahun dari delivery_date (DD-MM-YYYY); fallback ke hari ini
        $parts = $deliveryDate ? explode('-', $deliveryDate) : [];
        if (count($parts) === 3) {
            $month = str_pad($parts[1], 2, "0", STR_PAD_LEFT);
            $year  = substr($parts[2], -2);
        } else {
            $month = str_pad(date('n'), 2, "0", STR_PAD_LEFT);
            $year  = date('y');
        }

        return "{$prefix}-{$year}-{$month}-{$newCode}"; // ex: DN-UMUM-26-07-00001
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $username     = Auth::user()->username;
        $articles     = json_decode($request->articles);
        $customerId   = $request->customerId;
        $deliveryDate = $request->deliveryDate;
        $perihal      = $request->perihal;
        $note         = $request->note;
        $dnType       = $request->dnType;      // 'rm' | 'ot' | 'other'
        $status       = '1';
        $siteCode     = $this->siteCode;
        $location     = $this->gudangMap[$dnType] ?? null;

        // Penomoran: prefix & counter tergantung dept user yang login
       $deptCode = $this->resolveDepts();   // array
$prefix   = $this->resolvePrefix();
$leadCode = $this->codeKeyMap[$prefix];

        $validation = Validator::make($request->all(), [
            'deliveryDate' => 'required',
            'customerId'   => 'required',
            'dnType'       => 'required',
        ], [
            'required' => 'The field is required.',
        ]);

        if ($validation->fails()) {
            $error_array = [];
            foreach ($validation->messages()->getMessages() as $messages) {
                $error_array[] = $messages;
            }
            return response()->json(['status' => 0, 'message' => $error_array, 'alert' => 'warning']);
        }

        if (!$location) {
            return response()->json(['status' => 0, 'message' => ['Type tidak valid.'], 'alert' => 'warning']);
        }

       if (empty($deptCode)) {
    return response()->json([
        'status'  => 0,
        'message' => ['Dept user tidak ter-set. Hubungi IT.'],
        'alert'   => 'warning',
    ]);
}

        if (empty($articles)) {
            return response()->json(['status' => 0, 'message' => ['Minimal 1 artikel harus diisi.'], 'alert' => 'warning']);
        }

        // ── Validasi dasar saja — TANPA cek stok gudang (overstock diizinkan, stok boleh minus) ──
        $validErrors = [];
        foreach ($articles as $val) {
            if ($this->isManualArticle($val->article_code)) {
                continue;
            }

            $qty = (float) $val->qty;
            if ($qty <= 0) {
                $validErrors[] = "Qty untuk {$val->article_code} harus lebih dari 0.";
                continue;
            }

            $exists = DB::table('article')
                ->where('article_code', $val->article_code)
                ->exists();

            if (!$exists) {
                $validErrors[] = "Article {$val->article_code} tidak ditemukan di master.";
            }
        }

        if (!empty($validErrors)) {
            return response()->json(['status' => 0, 'message' => $validErrors, 'alert' => 'warning']);
        }

        AppHelpers::resetCode($leadCode);
        $tDnNumber   = $this->getLastCode($leadCode, $prefix, $deliveryDate);
        $partnerType = $this->resolvePartnerType($dnType, $customerId);

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
            $seq         = (int) DB::table('warehouse_movement')->max('movement_code');

            foreach ($articles as $val) {
                $qty = (float) $val->qty;

                // ── Artikel manual (OTHER): tidak sentuh stok ──
                if ($this->isManualArticle($val->article_code)) {
                    $dataSet[] = [
                        'tdn_number'      => $tDnNumber,
                        'article_code'    => self::MANUAL_CODE,
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

                $stockBefore = $this->currentStock($val->article_code, $location);

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

                // pastikan row stok ada
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

                // kurangi stok — $qty sudah di-cast (float), aman dari injection
                DB::table('warehouse_stock')
                    ->where('site_code', $siteCode)
                    ->where('article_code', $val->article_code)
                    ->where('location_number', $location)
                    ->update(['article_qty' => DB::raw('coalesce(article_qty,0) - ' . $qty)]);

                $lastQtyAfter = $this->currentStock($val->article_code, $location);

                $seq++;
                $movementSet[] = [
                    'movement_code'     => $seq,
                    'movement_date'     => $deliveryDate,
                    'artikel_code'      => $val->article_code,
                    'artikel_desc'      => $articleInfo->article_desc,
                    'movement_min'      => $qty,
                    'movement_plus'     => 0,
                    'movement_price'    => 0,
                    'movement_transnno' => $tDnNumber,
                    'movement_type'     => self::MOVEMENT_TYPE,
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

            $title   = "Save {$this->title}";
            $message = "{$title} {$tDnNumber} is successfully saved";
            \LogActivity::addToLog($title, "username: {$username} Status {$message}");

            return response()->json([
                'status'    => 1,
                'title'     => $title,
                'message'   => $message,
                'alert'     => 'success',
                'tDnNumber' => $tDnNumber,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $title   = "Save {$this->title}";
            $message = "{$title} {$tDnNumber} is failed to saved - " . $e->getMessage();
            \LogActivity::addToLog($title, "username: {$username} Status {$message}");

            return response()->json([
                'status'    => 0,
                'title'     => $title,
                'message'   => $message,
                'alert'     => 'warning',
                'tDnNumber' => $tDnNumber,
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(Request $request)
    {
        $username     = Auth::user()->username;
        $articles     = json_decode($request->articles);
        $tDnNumber    = $request->tDnNumber;
        $customerId   = $request->customerId;
        $deliveryDate = $request->deliveryDate;
        $perihal      = $request->perihal;
        $note         = $request->note;
        $siteCode     = $this->siteCode;

        $validation = Validator::make($request->all(), [
            'deliveryDate' => 'required',
            'customerId'   => 'required',
        ], [
            'required' => 'The field is required.',
        ]);

        if ($validation->fails()) {
            $error_array = [];
            foreach ($validation->messages()->getMessages() as $messages) {
                $error_array[] = $messages;
            }
            return response()->json(['status' => 0, 'message' => $error_array, 'alert' => 'warning']);
        }

        $dnHdr = DB::table('dn_general_hdr')->where('tdn_number', $tDnNumber)->first();
        if (!$dnHdr) {
            return response()->json(['status' => 0, 'message' => ['Data tidak ditemukan.'], 'alert' => 'warning']);
        }

        $this->assertDeptAccess($dnHdr);

        $dnType   = $dnHdr->dn_type;
        $location = $this->gudangMap[$dnType] ?? null;
        if (!$location) {
            return response()->json(['status' => 0, 'message' => ['Type tidak valid.'], 'alert' => 'warning']);
        }

        if (empty($articles)) {
            return response()->json(['status' => 0, 'message' => ['Minimal 1 artikel harus diisi.'], 'alert' => 'warning']);
        }

        // Detail lama non-manual (akan di-reverse stoknya)
        $oldDetails = DB::table('dn_general_det')
            ->where('tdn_number', $tDnNumber)
            ->where('article_code', '!=', self::MANUAL_CODE)
            ->get();

        // ── Validasi dasar saja — TANPA cek stok gudang (overstock diizinkan, stok boleh minus) ──
        $validErrors = [];
        foreach ($articles as $val) {
            if ($this->isManualArticle($val->article_code)) {
                continue;
            }

            $qty = (float) $val->qty;
            if ($qty <= 0) {
                $validErrors[] = "Qty untuk {$val->article_code} harus lebih dari 0.";
                continue;
            }

            $exists = DB::table('article')
                ->where('article_code', $val->article_code)
                ->exists();

            if (!$exists) {
                $validErrors[] = "Article {$val->article_code} tidak ditemukan di master.";
            }
        }

        if (!empty($validErrors)) {
            return response()->json(['status' => 0, 'message' => $validErrors, 'alert' => 'warning']);
        }

        $partnerType = $this->resolvePartnerType($dnType, $customerId);

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

            // 2. Reverse stok lama (kembalikan semua stok artikel lama non-manual)
            foreach ($oldDetails as $old) {
                $loc    = $old->location_number ?? $location;
                $oldQty = (float) $old->qty;
                DB::table('warehouse_stock')
                    ->where('site_code', $siteCode)
                    ->where('article_code', $old->article_code)
                    ->where('location_number', $loc)
                    ->update(['article_qty' => DB::raw('coalesce(article_qty,0) + ' . $oldQty)]);
            }

            // 3. Hapus movement lama (pakai konstanta yang sama dengan store)
            DB::table('warehouse_movement')
                ->where('movement_transnno', $tDnNumber)
                ->where('movement_type', self::MOVEMENT_TYPE)
                ->delete();

            // 4. Hapus detail lama
            DB::table('dn_general_det')->where('tdn_number', $tDnNumber)->delete();

            // 5. Insert ulang detail + movement + potong stok baru
            $dataSet     = [];
            $movementSet = [];
            $seq         = (int) DB::table('warehouse_movement')->max('movement_code');

            foreach ($articles as $val) {
                $qty = (float) $val->qty;

                if ($this->isManualArticle($val->article_code)) {
                    $dataSet[] = [
                        'tdn_number'      => $tDnNumber,
                        'article_code'    => self::MANUAL_CODE,
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

                $stockBefore = $this->currentStock($val->article_code, $location);

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
                    ['site_code' => $siteCode, 'article_code' => $val->article_code, 'location_number' => $location],
                    ['dept_code' => $articleInfo->article_type, 'uom' => $articleInfo->uom]
                );

                DB::table('warehouse_stock')
                    ->where('site_code', $siteCode)
                    ->where('article_code', $val->article_code)
                    ->where('location_number', $location)
                    ->update(['article_qty' => DB::raw('coalesce(article_qty,0) - ' . $qty)]);

                $lastQtyAfter = $this->currentStock($val->article_code, $location);

                $seq++;
                $movementSet[] = [
                    'movement_code'     => $seq,
                    'movement_date'     => $deliveryDate,
                    'artikel_code'      => $val->article_code,
                    'artikel_desc'      => $articleInfo->article_desc,
                    'movement_min'      => $qty,
                    'movement_plus'     => 0,
                    'movement_price'    => 0,
                    'movement_transnno' => $tDnNumber,
                    'movement_type'     => self::MOVEMENT_TYPE,
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

            $title   = "Update {$this->title}";
            $message = "{$title} {$tDnNumber} is successfully updated";
            \LogActivity::addToLog($title, "username: {$username} Status {$message}");

            return response()->json(['status' => 1, 'title' => $title, 'message' => $message, 'alert' => 'success', 'tDnNumber' => $tDnNumber]);
        } catch (\Exception $e) {
            DB::rollBack();
            $title   = "Update {$this->title}";
            $message = "{$title} {$tDnNumber} is failed to update - " . $e->getMessage();
            \LogActivity::addToLog($title, "username: {$username} Status {$message}");

            return response()->json(['status' => 0, 'title' => $title, 'message' => $message, 'alert' => 'warning', 'tDnNumber' => $tDnNumber]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY (cancel + reverse stok)
    |--------------------------------------------------------------------------
    */

    public function destroy(Request $request)
    {
        $id       = Crypt::decryptString($request->id);
        $username = Auth::user()->username;
        $siteCode = $this->siteCode;

        $dnHdr = DB::table('dn_general_hdr')->where('id', $id)->first();
        if (!$dnHdr) {
            return redirect()->back()->with([
                'title'   => "Delete {$this->title}",
                'alert'   => 'warning',
                'message' => 'Data tidak ditemukan.',
            ]);
        }

        $this->assertDeptAccess($dnHdr);

        // Cegah cancel ganda
        if ($dnHdr->status === '4') {
            return redirect()->back()->with([
                'title'   => "Delete {$this->title}",
                'alert'   => 'warning',
                'message' => "{$this->title} {$dnHdr->tdn_number} sudah dibatalkan sebelumnya.",
            ]);
        }

        $tDnNumber = $dnHdr->tdn_number;
        $dnType    = $dnHdr->dn_type;
        $location  = $this->gudangMap[$dnType] ?? null;
        $cancelNo  = $tDnNumber . '(C)';

        DB::beginTransaction();
        try {
            // 1. Header -> CANCELED (status 4), rename nomor
            $rowAffected = DB::table('dn_general_hdr')
                ->where('id', $id)
                ->update([
                    'status'            => '4',
                    'tdn_number'        => $cancelNo,
                    'origin_tdn_number' => $cancelNo,
                    'updated_by'        => $username,
                    'updated_at'        => date('Y-m-d H:i:s'),
                ]);

            if ($rowAffected < 1) {
                DB::rollBack();
                return redirect()->back()->with([
                    'title'   => "Delete {$this->title}",
                    'alert'   => 'warning',
                    'message' => "{$this->title} {$tDnNumber} Failed to Cancel - Header not found.",
                ]);
            }

            // 2. Rename detail
            DB::table('dn_general_det')
                ->where('tdn_number', $tDnNumber)
                ->update([
                    'tdn_number' => $cancelNo,
                    'updated_by' => $username,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            // 3. Ambil detail untuk reverse stok (skip manual OTHER)
            $details = DB::table('dn_general_det as d')
                ->leftJoin('article', 'article.article_code', '=', 'd.article_code')
                ->where('d.tdn_number', $cancelNo)
                ->where('d.article_code', '!=', self::MANUAL_CODE)
                ->select('d.article_code', 'd.qty', 'd.uom', 'd.location_number', 'article.article_desc', 'article.article_type')
                ->get();

            $reverseMovements = [];
            $seq = (int) DB::table('warehouse_movement')->max('movement_code');

            foreach ($details as $det) {
                $loc = $det->location_number ?? $location;
                $qty = (float) $det->qty;

                // 4. Kembalikan stok
                DB::table('warehouse_stock')
                    ->where('site_code', $siteCode)
                    ->where('article_code', $det->article_code)
                    ->where('location_number', $loc)
                    ->update(['article_qty' => DB::raw('coalesce(article_qty,0) + ' . $qty)]);

                $lastQtyAfter = $this->currentStock($det->article_code, $loc);

                $seq++;
                $reverseMovements[] = [
                    'movement_code'     => $seq,
                    'movement_date'     => date('d-m-Y'),
                    'artikel_code'      => $det->article_code,
                    'artikel_desc'      => $det->article_desc,
                    'movement_min'      => 0,
                    'movement_plus'     => $qty,
                    'movement_price'    => 0,
                    'movement_transnno' => $cancelNo,
                    'movement_type'     => self::MOVEMENT_TYPE_DELETE,
                    'movement_desc'     => "Delete DN Umum: {$tDnNumber}",
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

            // 5. Insert reverse movement
            if (!empty($reverseMovements)) {
                DB::table('warehouse_movement')->insert($reverseMovements);
            }

            // 6. Rename movement asli (soft-delete referensi)
            DB::table('warehouse_movement')
                ->where('movement_transnno', $tDnNumber)
                ->where('movement_type', self::MOVEMENT_TYPE)
                ->update(['movement_transnno' => $cancelNo]);

            DB::commit();

            $title   = "Delete {$this->title}";
            $message = "{$title} {$tDnNumber} Successfully Canceled";
            \LogActivity::addToLog($title, "username: {$username} Status {$message}");
            return redirect()->back()->with(['title' => $title, 'alert' => 'success', 'message' => $message]);
        } catch (\Exception $e) {
            DB::rollBack();
            $title   = "Delete {$this->title}";
            $message = "{$title} {$tDnNumber} Failed to Cancel - " . $e->getMessage();
            \LogActivity::addToLog($title, "username: {$username} Status {$message}");
            return redirect()->back()->with(['title' => $title, 'alert' => 'warning', 'message' => $message]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CLOSED (status 3) — untuk dn_general_hdr
    |--------------------------------------------------------------------------
    */

    public function closed(Request $request)
    {
        $username = Auth::user()->username;
        $id       = Crypt::decryptString($request->id);

        $dnHdr = DB::table('dn_general_hdr')->where('id', $id)->first();
        if (!$dnHdr) {
            return redirect()->back()->with([
                'title'   => "Close {$this->title}",
                'alert'   => 'warning',
                'message' => 'Data tidak ditemukan.',
            ]);
        }

        $this->assertDeptAccess($dnHdr);

        $tDnNumber = $dnHdr->tdn_number;

        DB::beginTransaction();
        try {
            DB::table('dn_general_hdr')
                ->where('id', $id)
                ->update([
                    'status'     => '3',
                    'updated_by' => $username,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            DB::commit();
            $title   = "Close {$this->title}";
            $message = "{$title} {$tDnNumber} Successfully Closed";
            \LogActivity::addToLog($title, "username: {$username} Status {$message}");
            return redirect()->back()->with(['title' => $title, 'alert' => 'success', 'message' => $message]);
        } catch (\Exception $e) {
            DB::rollBack();
            $title   = "Close {$this->title}";
            $message = "{$title} {$tDnNumber} Failed to Close - " . $e->getMessage();
            \LogActivity::addToLog($title, "username: {$username} Status {$message}");
            return redirect()->back()->with(['title' => $title, 'alert' => 'warning', 'message' => $message]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DataTables list
    |--------------------------------------------------------------------------
    */

    private function parseDateRange($deliveryDate, &$fromDate, &$toDate)
    {
        $fromDate = $toDate = "";
        if (!$deliveryDate) {
            return;
        }
        $date = explode("to", $deliveryDate);
        $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
        $toDate   = count($date) > 1
            ? implode("/", array_reverse(explode("-", trim($date[1]))))
            : $fromDate;
    }

    public function list(Request $request)
    {
        $searchDn       = strtolower($request->searchDn);
        $searchStatus   = $request->searchStatus;
        $deliveryDate   = $request->deliveryDate;
        $searchCustomer = $request->searchCustomer;
        $this->parseDateRange($deliveryDate, $fromDate, $toDate);

        $data = DB::table('dn_general_hdr')
            ->leftJoin('third_party', 'third_party.kode', '=', 'dn_general_hdr.customer_id')
            ->where(function ($query) use ($searchDn, $searchStatus, $deliveryDate, $fromDate, $toDate, $searchCustomer) {
                if ($searchDn)       $query->where('dn_general_hdr.tdn_number', 'ilike', '%' . $searchDn . '%');
                if ($searchStatus)   $query->where('dn_general_hdr.status', $searchStatus);
                if ($deliveryDate)   $query->whereBetween(DB::raw("to_date(dn_general_hdr.delivery_date,'DD-MM-YYYY')"), [$fromDate, $toDate]);
                if ($searchCustomer) $query->where('dn_general_hdr.customer_id', $searchCustomer);
            })
            ->where('dn_general_hdr.status', '!=', '4')
            ->select('dn_general_hdr.*', DB::raw("concat(third_party.kode,'-',third_party.nama) as customer_name"))
            ->orderBy('dn_general_hdr.id', 'desc');

        // Filter berdasarkan dept user pembuat
        $this->applyDeptFilter($data);

        return \DataTables::of($data)
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
                                    data-confirm-yes='document.getElementById(\"delete-form-" . $data->id . "\").submit();'
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
                return $this->statusBadgeHtml($data->status);
            })
            ->addColumn('dn_type', function ($data) {
                return $this->dnTypeBadgeHtml($data->dn_type);
            })
            ->rawColumns(['action', 'status', 'dn_type', 'tdn_number'])
            ->make(true);
    }

    public function listDetail(Request $request)
    {
        $searchDn       = strtolower($request->searchDn);
        $searchStatus   = $request->searchStatus;
        $deliveryDate   = $request->deliveryDate;
        $searchCustomer = $request->searchCustomer;
        $this->parseDateRange($deliveryDate, $fromDate, $toDate);

        $data = DB::table('dn_general_det')
            ->leftJoin('dn_general_hdr', 'dn_general_hdr.tdn_number', 'dn_general_det.tdn_number')
            ->leftJoin('article', 'article.article_code', 'dn_general_det.article_code')
            ->leftJoin('third_party', 'third_party.kode', 'dn_general_hdr.customer_id')
            ->where(function ($query) use ($searchDn, $searchStatus, $deliveryDate, $fromDate, $toDate, $searchCustomer) {
                if ($searchDn)       $query->where('dn_general_hdr.tdn_number', 'ilike', '%' . $searchDn . '%');
                if ($searchStatus)   $query->where('dn_general_hdr.status', $searchStatus);
                if ($deliveryDate)   $query->whereBetween(DB::raw("to_date(dn_general_hdr.delivery_date,'DD-MM-YYYY')"), [$fromDate, $toDate]);
                if ($searchCustomer) $query->where('dn_general_hdr.customer_id', $searchCustomer);
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
            ->orderBy('dn_general_det.tdn_number');

        // Filter berdasarkan dept user pembuat
        $this->applyDeptFilter($data);

        return \DataTables::of($data)
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
                return $this->statusBadgeHtml($data->status);
            })
            ->addColumn('dn_type', function ($data) {
                return $this->dnTypeBadgeHtml($data->dn_type);
            })
            ->rawColumns(['status', 'dn_type'])
            ->make(true);
    }

    /*
    |--------------------------------------------------------------------------
    | Data artikel untuk dropdown
    |--------------------------------------------------------------------------
    */

    public function getArticle(Request $request)
    {
        $custCode = $request->custCode;

        $data = DB::table('article')
            ->whereIn('article.article_code', function ($query) use ($custCode) {
                $query->select('article_code')
                    ->from('bom_hdr')
                    ->where('status', '3')
                    ->where('customer', $custCode);
            })
            ->where('third_party', $custCode)
            ->where('article_type', 'FG')
            ->orderBy('article_desc')
            ->get();

        $output = '<option value="">Choose article</option>';
        foreach ($data as $row) {
            $output .= '<option value="' . $row->article_code . '" data-uom="' . $row->uom . '">'
                . $row->article_alternative_code . '-' . $row->article_desc . '</option>';
        }

        return $output;
    }

    public function articlesByType(Request $request)
{
    $type   = $request->type;
    $gudang = $this->gudangMap[$type] ?? null;

    // Type yang: (1) tampilkan semua supplier & customer, (2) artikel TIDAK
    // bergantung pada customer yang dipilih (list-nya sama untuk semua)
    $customerIndependentTypes = ['other', 'box', 'troli', 'trial', 'ms', 'cs', 'lb3', 'lnb3', 'rig'];

    if (in_array($type, $customerIndependentTypes, true)) {
        $query = DB::table('article as a')
            ->leftJoin('warehouse_stock as s', function ($join) use ($gudang) {
                $join->on('s.article_code', '=', 'a.article_code')
                    ->where('s.location_number', '=', $gudang);
            })
            ->where('a.status', '1');

       switch ($type) {
    case 'other':
    case 'box':
    case 'troli':
        // Semua artikel aktif (status=1 sudah difilter di atas), kecuali group JS.
        $query->where(function ($q) {
            $q->where('a.group_of_material', '!=', 'JS')
                ->orWhereNull('a.group_of_material');
        });
        break;

    case 'trial':
        // Semua artikel aktif (freeze otomatis ter-exclude karena status=1),
        // kecuali article_type GA.
        $query->where('a.article_type', '!=', 'GA');
        break;

    case 'cs':
        // Hanya article_type CM1
        $query->where('a.article_type', 'CM1');
        break;

    case 'ms':
        // Semua artikel aktif, tanpa filter tambahan
        break;

    case 'lnb3':
        // Hanya group_of_material NB3
        $query->where('a.group_of_material', 'NB3');
        break;

    case 'lb3':
        // Hanya group_of_material B3
        $query->where('a.group_of_material', 'B3');
        break;

    case 'rig':
        // Hanya article_type GA
        $query->where('a.article_type', 'GA');
        break;
}

        return $query->select(
                'a.article_code             as code',
                'a.article_alternative_code as alt_code',
                'a.article_desc             as name',
                DB::raw('coalesce(s.article_qty, 0) as qty'),
                'a.uom'
            )
            ->orderBy('a.article_alternative_code')
            ->get();
    }

    // ── rm & ot: artikel tergantung customer yang dipilih ──
    if (!$request->customer) {
        return response()->json([]);
    }

    if ($type === 'ot') {
    $gudangOt = $this->gudangMap['ot']; // '008'

    return DB::table('article as a')
        ->leftJoin('warehouse_stock as s', function ($join) use ($gudangOt) {
            $join->on('s.article_code', '=', 'a.article_code')
                ->where('s.location_number', '=', $gudangOt);
        })
        ->where('a.article_type', 'FG')
        ->where('a.third_party', $request->customer)
        ->select(
            'a.article_code             as code',
            'a.article_alternative_code as alt_code',
            'a.article_desc             as name',
            DB::raw('coalesce(s.article_qty, 0) as qty'),
            'a.uom'
        )
        ->orderBy('a.article_alternative_code')
        ->get();
}

    // ── rm: berdasarkan gudang + stok + customer ──
    if (!$gudang) {
        return response()->json([]);
    }

    return DB::table('warehouse_stock as s')
        ->join('article as a', 's.article_code', '=', 'a.article_code')
        ->where('s.location_number', $gudang)
        ->where('s.article_qty', '>', 0)
        ->where('a.third_party', $request->customer)
        ->select(
            'a.article_code             as code',
            'a.article_alternative_code as alt_code',
            'a.article_desc             as name',
            's.article_qty              as qty',
            'a.uom'
        )
        ->orderBy('a.article_alternative_code')
        ->get();
}
}