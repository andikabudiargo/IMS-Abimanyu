<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Collection;
use DataTables;
use DB;
use Excel;
use AppHelpers;
use App\Imports\StockAdjustmentImport;
use App\Exports\StockAdjustmentExport;

/**
 * Stock Adjustment.
 *
 * ── SIKLUS DOKUMEN ───────────────────────────────────────────────────────
 *
 *   1 DRAFT ──edit───────────────> 1 DRAFT            (bebas, tanpa reason)
 *   1/2/3   ──posting───────────> 4 POSTED           (movement di-INSERT)
 *   4       ──edit + reason─────> 6 REVISED          (stok BELUM berubah)
 *   6       ──edit + reason─────> 6 REVISED          (boleh berkali-kali)
 *   6       ──posting───────────> 4 POSTED           (selisih diterapkan)
 *   4 / 6   ──cancel + reason───> 5 CANCELED
 *
 * ── DUA JALUR EDIT, SENGAJA BEDA ─────────────────────────────────────────
 *
 * DRAFT (1/2/3) — stok belum tersentuh sama sekali, jadi mengubah angka di
 * sini tidak ada konsekuensinya. Tanpa reason, tanpa history, tanpa rev_no.
 * Ini cuma dokumen yang belum jadi.
 *
 * POSTED (4) / REVISED (6) — stok SUDAH terlanjur berubah. Edit di sini wajib
 * reason, dicatat diff-nya, dan HANYA mengubah stock_adjustment_det. Stok dan
 * warehouse_movement belum disentuh; dokumen turun ke REVISED dan harus
 * diposting ulang. Posting ulang itulah yang menerapkan SELISIH ke stok dan
 * meng-UPDATE baris movement yang sudah ada (bukan bikin baris baru), sehingga
 * report movement tetap 1 dokumen = 1 baris per artikel.
 *
 * ── KENAPA BASELINE DIBACA DARI warehouse_movement ───────────────────────
 *
 * Saat status REVISED, stock_adjustment_det sudah berisi nilai BARU sementara
 * stok masih mencerminkan nilai LAMA. Nilai lama itu tidak disimpan di mana
 * pun — kecuali di warehouse_movement, yang memang catatan resmi "apa yang
 * sudah masuk stok". Jadi postedBaseline() membaca dari sana, bukan dari det.
 * cancel() juga memakainya, supaya yang dibalik adalah stok yang benar-benar
 * ada, bukan angka pending yang belum pernah diposting.
 */
class StockAdjustmentController extends Controller
{
    // =========================================================================
    //  KONSTANTA
    // =========================================================================

    private const ST_DRAFT     = '1';
    private const ST_VALIDATED = '2';
    private const ST_APPROVED  = '3';
    private const ST_POSTED    = '4';
    private const ST_CANCELED  = '5';
    private const ST_REVISED   = '6';

    /** Belum menyentuh stok → edit bebas, boleh dihapus. */
    private const ST_EDITABLE = [self::ST_DRAFT, self::ST_VALIDATED, self::ST_APPROVED];

    /** Stok sudah terdampak → edit wajib reason, tidak boleh dihapus. */
    private const ST_LIVE = [self::ST_POSTED, self::ST_REVISED];

    private const STATUS_LABEL = [
        self::ST_DRAFT     => 'DRAFT',
        self::ST_VALIDATED => 'VALIDATED',
        self::ST_APPROVED  => 'APPROVED',
        self::ST_POSTED    => 'POSTED',
        self::ST_CANCELED  => 'CANCELED',
        self::ST_REVISED   => 'REVISED',
    ];

    private const STATUS_BADGE = [
        self::ST_DRAFT     => 'badge-primary',
        self::ST_VALIDATED => 'badge-info',
        self::ST_APPROVED  => 'badge-warning',
        self::ST_POSTED    => 'badge-success',
        self::ST_CANCELED  => 'badge-danger',
        self::ST_REVISED   => 'badge-warning',
    ];

    private const ADJ_TYPES = ['SYSTEM CORRECTION', 'OPENING BALANCE', 'FOUND/UNRECORDED', 'OTHER'];

    private const ROLE_PRIVILEGED = ['Superuser', 'accounting', 'finance'];
    private const ROLE_POSTING    = ['Superuser', 'accounting'];

    private const MV_ADJUSTMENT = 'ADJUSTMENT';
    private const MV_CANCEL     = 'CANCEL ADJUSTMENT';
    private const PARTNER_TYPE  = 'ADJ';

    private const REV_HDR_FIELDS = ['adj_date', 'adj_type', 'description', 'note', 'periode'];
    private const REV_DET_FIELDS = ['direction', 'qty_adjustment', 'uom', 'stock_before', 'stock_after', 'notes'];
    private const REV_NUM_FIELDS = ['qty_adjustment', 'stock_before', 'stock_after'];

    private const IMPORT_MAX_ROWS = 2000;
    private const EPSILON         = 0.000001;

    private string $title;
    private string $moduleCode;
    private string $siteCode;

    public function __construct()
    {
        $this->title      = 'Stock Adjustment';
        $this->moduleCode = 'ADJ';
        $this->siteCode   = 'HO';
    }

    // =========================================================================
    //  COLUMN DEFINITIONS
    // =========================================================================

    public function getTableColumn(): string
    {
        return json_encode([
            ['data' => 'action',        'name' => 'action',        'title' => 'Action',        'orderable' => false, 'searchable' => false],
            ['data' => 'adj_code',      'name' => 'adj_code',      'title' => 'Adjustment Code'],
            ['data' => 'rev_no',        'name' => 'rev_no',        'title' => 'Rev.',          'orderable' => false, 'searchable' => false],
            ['data' => 'adj_date',      'name' => 'adj_date',      'title' => 'Date'],
            ['data' => 'adj_type',      'name' => 'adj_type',      'title' => 'Type'],
            ['data' => 'location_name', 'name' => 'location_name', 'title' => 'Location'],
            ['data' => 'description',   'name' => 'description',   'title' => 'Description'],
            ['data' => 'status',        'name' => 'status',        'title' => 'Status',        'orderable' => false, 'searchable' => false],
            ['data' => 'note',          'name' => 'note',          'title' => 'Note',          'visible'   => false],
            ['data' => 'created_by',    'name' => 'created_by',    'title' => 'Created By'],
            ['data' => 'created_at',    'name' => 'created_at',    'title' => 'Created At'],
            ['data' => 'authorized_by', 'name' => 'authorized_by', 'title' => 'Authorized By', 'orderable' => false, 'searchable' => false],
            ['data' => 'authorized_at', 'name' => 'authorized_at', 'title' => 'Authorized At', 'orderable' => false, 'searchable' => false],
            ['data' => 'updated_at',    'name' => 'updated_at',    'title' => 'Updated At'],
        ], true);
    }

    public function getTableColumnDetail(): string
    {
        return json_encode([
            ['data' => 'adj_code',                 'name' => 'adj_code',                 'title' => 'Adjustment Code'],
            ['data' => 'adj_date',                 'name' => 'adj_date',                 'title' => 'Date'],
            ['data' => 'adj_type',                 'name' => 'adj_type',                 'title' => 'Type'],
            ['data' => 'location_name',            'name' => 'location_name',            'title' => 'Location'],
            ['data' => 'description',              'name' => 'description',              'title' => 'Description'],
            ['data' => 'status',                   'name' => 'status',                   'title' => 'Status',          'orderable' => false, 'searchable' => false],
            ['data' => 'direction',                'name' => 'direction',                'title' => 'Direction (-/+)', 'orderable' => false, 'searchable' => false],
            ['data' => 'article_alternative_code', 'name' => 'article_alternative_code', 'title' => 'Article Code'],
            ['data' => 'uom',                      'name' => 'uom',                      'title' => 'UoM'],
            ['data' => 'stock_before',             'name' => 'stock_before',             'title' => 'Stock Before'],
            ['data' => 'qty_adjustment',           'name' => 'qty_adjustment',           'title' => 'Qty Adjustment'],
            ['data' => 'stock_after',              'name' => 'stock_after',              'title' => 'Stock After'],
            ['data' => 'notes',                    'name' => 'notes',                    'title' => 'Notes'],
            ['data' => 'created_by',               'name' => 'created_by',               'title' => 'Created By'],
            ['data' => 'created_at',               'name' => 'created_at',               'title' => 'Created At'],
            ['data' => 'authorized_by',            'name' => 'authorized_by',            'title' => 'Authorized By',   'orderable' => false, 'searchable' => false],
            ['data' => 'authorized_at',            'name' => 'authorized_at',            'title' => 'Authorized At',   'orderable' => false, 'searchable' => false],
        ], true);
    }

    // =========================================================================
    //  INDEX
    // =========================================================================

    public function index(Request $request)
    {
        return view('stockAdjustment.index', [
            'title'       => $this->title,
            'subtitle'    => $this->title,
            'kolom'       => $this->getTableColumn(),
            'kolomDetail' => $this->getTableColumnDetail(),
            'locations'   => DB::table('stock_location_master')->orderBy('location_name')->get(),
            'status'      => self::STATUS_LABEL,
            'types'       => self::ADJ_TYPES,
        ]);
    }

    // =========================================================================
    //  DATATABLE — SUMMARY
    // =========================================================================

    public function list(Request $request)
    {
        $username   = Auth::user()->username;
        $privileged = $this->isPrivileged();
        $canPost    = $this->canPost();

        $query = DB::table('stock_adjustment_hdr as h')
            ->leftJoin('stock_location_master as loc', 'loc.location_code', '=', 'h.location_code')
            ->where('h.status', '<>', self::ST_CANCELED);

        $this->applyFilters($query, $request);

        if (!$privileged) {
            $query->whereIn('loc.dept_code', $this->userDepts());
        }

        $data = $query
            ->select('h.*', 'loc.location_name', 'loc.dept_code as loc_dept')
            ->orderBy('h.id', 'desc')
            ->get();

        return Datatables::of($data)
            ->addColumn('action', fn($row) => $this->rowActions($row, $username, $privileged, $canPost))
            ->addColumn('status', fn($row) => $this->statusBadge($row->status))
            ->addColumn('rev_no', function ($row) {
                $n = (int) ($row->rev_no ?? 0);
                return $n > 0 ? "<span class='badge badge-light-warning'>rev.{$n}</span>" : '';
            })
            ->rawColumns(['action', 'status', 'rev_no'])
            ->make(true);
    }

    private function rowActions(object $row, string $username, bool $privileged, bool $canPost): string
    {
        $st        = (string) $row->status;
        $isCreator = ($row->created_by === $username);
        $enc       = Crypt::encryptString($row->id);

        $isDraft = in_array($st, self::ST_EDITABLE, true);
        $isLive  = in_array($st, self::ST_LIVE, true);

        $b  = '<div class="d-inline-flex">';
        $b .= '<a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown"><i data-feather="menu"></i></a>';
        $b .= '<div class="dropdown-menu dropdown-menu-right">';

        // ── POSTING (baru) — status 1/2/3
        if ($canPost && $isDraft) {
            $b .= $this->confirmItem(
                route('stockAdjustment.posting', ['id' => $enc]), $row->id,
                'text-success', 'check-circle', 'Posting',
                'Yakin ingin posting?|Stok akan berubah setelah posting.'
            );
        }

        // ── POSTING (ulang) — status 6, menerapkan selisih revisi
        if ($canPost && $st === self::ST_REVISED) {
            $b .= $this->confirmItem(
                route('stockAdjustment.posting', ['id' => $enc]), $row->id,
                'text-success', 'refresh-cw', 'Posting Revisi',
                'Posting ulang revisi ini?|Hanya SELISIH terhadap posting sebelumnya yang diterapkan ke stok.'
            );
        }

        // ── EDIT — draft, tanpa reason
        if ($isDraft && ($isCreator || $privileged)) {
            $b .= '<a href="' . route('stockAdjustment.edit', ['id' => $enc]) . '" class="dropdown-item">
                     <i data-feather="edit-2"></i><span>' . __('Edit') . '</span></a>';
        }

        // ── REVISI — sudah kena stok, wajib reason
        if ($isLive && $canPost) {
            $b .= '<a href="' . route('stockAdjustment.edit', ['id' => $enc]) . '" class="dropdown-item text-warning">
                     <i data-feather="edit-3"></i><span>' . __('Revisi') . '</span></a>';
        }

        $b .= '<a href="' . route('stockAdjustment.show', ['id' => $enc]) . '" class="dropdown-item">
                 <i data-feather="list"></i><span>' . __('Detail') . '</span></a>';

        // ── DELETE — hanya draft
        if ($isDraft && ($isCreator || $privileged)) {
            $b .= $this->confirmItem(
                route('stockAdjustment.destroy', ['id' => $enc]), $row->id,
                'text-danger', 'trash-2', 'Delete',
                'Yakin ingin hapus?|Data tidak bisa dikembalikan.'
            );
        }

        // ── CANCEL — status 4 atau 6
        if ($isLive && $canPost) {
            $b .= "<a href='javascript:;' class='dropdown-item text-danger'
                      id='cancelReasonButton' data-toggle='modal' data-target='#reasonModalCancel'
                      data-href='" . route('stockAdjustment.cancel', ['id' => $enc]) . "'>
                      <i data-feather='x-circle' class='feather-14'></i><span>" . __('Cancel') . "</span></a>";
        }

        $b .= '<a href="' . route('stockAdjustment.print', ['id' => $enc]) . '" target="_blank" class="dropdown-item">
                 <i data-feather="printer"></i><span>' . __('Print') . '</span></a>';

        return $b . '</div></div>';
    }

    private function confirmItem(string $url, $id, string $cls, string $icon, string $label, string $confirm): string
    {
        return "<a href='javascript:;' class='dropdown-item {$cls}' data-size='sm' data-ajax-delete='true'
                   data-confirm='{$confirm}'
                   data-confirm-yes='document.getElementById(\"delete-form-{$id}\").submit();'
                   data-modal-id='{$id}' data-url='{$url}'>
                   <i data-feather='{$icon}' class='feather-14'></i><span>" . __($label) . "</span></a>";
    }

    private function statusBadge(?string $status): string
    {
        $b = self::STATUS_BADGE[$status] ?? 'badge-secondary';
        $l = self::STATUS_LABEL[$status] ?? '-';
        return "<div class='badge {$b}'>{$l}</div>";
    }

    // =========================================================================
    //  DATATABLE — DETAIL
    // =========================================================================

    public function listDetail(Request $request)
    {
        $query = DB::table('stock_adjustment_det as d')
            ->leftJoin('stock_adjustment_hdr as h',    'h.adj_code',       '=', 'd.adj_code')
            ->leftJoin('article as a',                 'a.article_code',   '=', 'd.article_code')
            ->leftJoin('stock_location_master as loc', 'loc.location_code', '=', 'h.location_code')
            ->where('h.status', '<>', self::ST_CANCELED);

        $this->applyFilters($query, $request);

        if (!$this->isPrivileged()) {
            $query->whereIn('loc.dept_code', $this->userDepts());
        }

        $data = $query->select(
            'h.adj_code', 'h.adj_date', 'h.adj_type', 'h.description', 'h.status',
            'h.created_by', 'h.created_at', 'h.authorized_by', 'h.authorized_at',
            'd.id', 'd.article_code', 'd.direction', 'd.uom',
            'd.stock_before', 'd.qty_adjustment', 'd.stock_after', 'd.notes',
            'a.article_alternative_code', 'a.article_desc',
            'loc.location_name'
        )->orderBy('d.id')->get();

        return Datatables::of($data)
            ->addColumn('status', fn($row) => $this->statusBadge($row->status))
            ->addColumn('direction', fn($row) => $row->direction === '+'
                ? "<span class='badge badge-success'>Stock In (+)</span>"
                : "<span class='badge badge-danger'>Stock Out (&minus;)</span>")
            ->rawColumns(['status', 'direction'])
            ->make(true);
    }

    /** Filter identik untuk list() dan listDetail() — satu sumber. */
    private function applyFilters($query, Request $request): void
    {
        $searchAdj      = strtolower($request->searchAdj ?? '');
        $searchDesc     = strtolower($request->searchDesc ?? '');
        $searchType     = $request->searchType;
        $searchStatus   = $request->searchStatus;
        $searchLocation = $request->searchLocation;
        $adjDate        = $request->adjDate;

        [$fromDate, $toDate] = $this->parseDateRange($adjDate);

        $query->where(function ($q) use (
            $searchAdj, $searchType, $searchStatus, $adjDate, $fromDate, $toDate, $searchLocation, $searchDesc
        ) {
            if ($searchAdj)      $q->where('h.adj_code',      'ilike', "%{$searchAdj}%");
            if ($searchType)     $q->where('h.adj_type',      $searchType);
            if ($searchStatus)   $q->where('h.status',        $searchStatus);
            if ($searchLocation) $q->where('h.location_code', $searchLocation);
            if ($searchDesc)     $q->where('h.description',   'ilike', "%{$searchDesc}%");
            if ($adjDate) {
                $q->whereBetween(DB::raw("to_date(h.adj_date,'DD-MM-YYYY')"), [$fromDate, $toDate]);
            }
        });
    }

    // =========================================================================
    //  CREATE / STORE
    // =========================================================================

    public function create(Request $request)
    {
        return view('stockAdjustment.create', [
            'title'            => "Create {$this->title}",
            'subtitle'         => "Create {$this->title}",
            'oEdit'            => false,
            'currentDateValue' => date('d-m-Y'),
            'locations'        => $this->allowedLocations(),
            'types'            => self::ADJ_TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $username = Auth::user()->username;
        $title    = "Save {$this->title}";
        $articles = json_decode($request->articles);

        if ($err = $this->validatePayload($request, $articles)) {
            return $this->fail($title, $err);
        }

        AppHelpers::resetCode($this->moduleCode);
        $adjCode = $this->getLastCode($this->moduleCode);

        DB::beginTransaction();
        try {
            DB::table('stock_adjustment_hdr')->insert([
                'adj_code'      => $adjCode,
                'adj_date'      => $request->adjDate,
                'adj_type'      => $request->adjType,
                'location_code' => $request->location,
                'description'   => $request->description,
                'note'          => $request->note,
                'periode'       => $request->periode,
                'direction'     => $this->summarizeDirection($articles),
                'status'        => self::ST_DRAFT,
                'rev_no'        => 0,
                'created_by'    => $username,
                'updated_by'    => $username,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);

            DB::table('stock_adjustment_det')->insert(
                array_map(fn($v) => $this->detRow($adjCode, $v, $username, true), $articles)
            );

            DB::commit();

            $message = "{$title} {$adjCode} berhasil disimpan.";
            $this->logActivity($title, "username: {$username} | {$message}");

            return response()->json([
                'status' => 1, 'title' => $title, 'message' => $message,
                'alert'  => 'success', 'adjCode' => $adjCode, 'oEdit' => true,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail($title, ["{$title} gagal disimpan: " . $e->getMessage()], $username);
        }
    }

    // =========================================================================
    //  SHOW
    // =========================================================================

    public function show(Request $request)
    {
        $id       = Crypt::decryptString($request->id);
        $username = Auth::user()->username;

        $header = DB::table('stock_adjustment_hdr as h')
            ->leftJoin('stock_location_master as loc', 'loc.location_code', '=', 'h.location_code')
            ->where('h.id', $id)
            ->select('h.*', 'loc.location_name')
            ->first();

        if (!$header) {
            return redirect()->back()->with([
                'title' => 'Detail', 'alert' => 'warning', 'message' => 'Data tidak ditemukan.',
            ]);
        }

        $isLive = in_array($header->status, self::ST_LIVE, true);

        $revisions = $this->revisionRows($header->adj_code);

        // Kumpulkan semua article_code yang muncul di revision log,
        // lalu ambil label-nya sekaligus — satu query untuk semua revisi.
        $revArticleCodes = collect();
        foreach ($revisions as $rev) {
            foreach ($rev->changes['detail'] ?? [] as $d) {
                if (!empty($d['article_code'])) {
                    $revArticleCodes->push($d['article_code']);
                }
            }
        }
        $articleMap = $revArticleCodes->unique()->isEmpty() ? [] :
            DB::table('article')
                ->whereIn('article_code', $revArticleCodes->unique()->values()->all())
                ->pluck('article_alternative_code', 'article_code')
                ->map(function ($altCode, $artCode) {
                    return $altCode ?? $artCode;
                })->toArray();

        return view('stockAdjustment.show', [
            'title'      => "Detail {$this->title}",
            'subtitle'   => "Detail {$this->title}",
            'header'     => $header,
            'details'    => $this->detailRows($header->adj_code),
            'revisions'  => $revisions,
            'articleMap' => $articleMap,
            'encId'      => Crypt::encryptString($id),
            'canEdit'    => in_array($header->status, self::ST_EDITABLE, true)
                            && ($header->created_by === $username || $this->isPrivileged()),
            'canRevise'  => $isLive && $this->canPost(),
            'canPost'    => $this->canPost(),
            // Blade: kalau true, tampilkan peringatan bahwa angka di layar
            // belum tercermin di stok sampai diposting ulang.
            'isPending'  => $header->status === self::ST_REVISED,
        ]);
    }

    // =========================================================================
    //  EDIT — satu view, dua mode
    // =========================================================================

    public function edit(Request $request)
    {
        $id       = Crypt::decryptString($request->id);
        $username = Auth::user()->username;

        $header = DB::table('stock_adjustment_hdr')->where('id', $id)->first();

        if (!$header) {
            return $this->backWarn('Edit', 'Data tidak ditemukan.');
        }
        if ($header->status === self::ST_CANCELED) {
            return $this->backWarn('Edit', 'Data sudah CANCELED, tidak bisa diedit.');
        }

        $isRevision = in_array($header->status, self::ST_LIVE, true);

        if ($isRevision && !$this->canPost()) {
            return $this->backWarn('Revisi', 'Hanya Superuser/accounting yang bisa merevisi dokumen yang sudah diposting.');
        }
        if (!$isRevision && $header->created_by !== $username && !$this->isPrivileged()) {
            return $this->backWarn('Edit', 'Anda tidak berwenang mengedit data ini.');
        }

        return view('stockAdjustment.edit', [
            // isRevision → blade wajib: modal reason, Location dikunci,
            // peringatan "perlu posting ulang".
            'title'            => $isRevision ? "Revisi {$this->title}" : "Edit {$this->title}",
            'subtitle'         => $isRevision ? "Revisi {$this->title}" : "Edit {$this->title}",
            'isRevision'       => $isRevision,
            'header'           => $header,
            'details'          => $this->detailRows($header->adj_code, true),
            'revisions'        => $isRevision ? $this->revisionRows($header->adj_code) : collect(),
            'encId'            => Crypt::encryptString($id),
            'locations'        => $this->allowedLocations(),
            'types'            => self::ADJ_TYPES,
            'currentDateValue' => date('d-m-Y'),
        ]);
    }

    // =========================================================================
    //  UPDATE — dispatcher
    // =========================================================================

    /**
     * Satu endpoint, dua perilaku — status di DB yang menentukan, bukan request.
     * Kalau jalurnya dipilih frontend, user bisa mengirim adjCode POSTED lewat
     * jalur draft dan melewati seluruh kontrol revisi.
     */
    public function update(Request $request)
    {
        $hdr = DB::table('stock_adjustment_hdr')->where('adj_code', $request->adjCode)->first();

        if (!$hdr) {
            return $this->fail("Update {$this->title}", ['Data tidak ditemukan.']);
        }
        if ($hdr->status === self::ST_CANCELED) {
            return $this->fail("Update {$this->title}", ['Data sudah CANCELED, tidak bisa diubah.']);
        }

        return in_array($hdr->status, self::ST_LIVE, true)
            ? $this->reviseDocument($request, $hdr)
            : $this->updateDraft($request, $hdr);
    }

    // -------------------------------------------------------------------------
    //  UPDATE — jalur DRAFT (1/2/3): stok belum tersentuh, jadi bebas.
    //  Tanpa reason, tanpa revision log, rev_no tidak naik.
    // -------------------------------------------------------------------------

    private function updateDraft(Request $request, object $hdr)
    {
        $username = Auth::user()->username;
        $title    = "Update {$this->title}";
        $adjCode  = $hdr->adj_code;
        $articles = json_decode($request->articles);

        if ($hdr->created_by !== $username && !$this->isPrivileged()) {
            return $this->fail($title, ['Anda tidak berwenang mengedit data ini.']);
        }
        if ($err = $this->validatePayload($request, $articles)) {
            return $this->fail($title, $err);
        }

        DB::beginTransaction();
        try {
            DB::table('stock_adjustment_hdr')->where('adj_code', $adjCode)->update([
                'adj_date'      => $request->adjDate,
                'adj_type'      => $request->adjType,
                'location_code' => $request->location,
                'description'   => $request->description,
                'note'          => $request->note,
                'periode'       => $request->periode,
                'direction'     => $this->summarizeDirection($articles),
                'status'        => self::ST_DRAFT,
                'updated_by'    => $username,
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);

            $this->syncDetails($adjCode, collect($articles)->keyBy('article_code'), $username);

            DB::commit();

            $message = "{$title} {$adjCode} berhasil diperbarui.";
            $this->logActivity($title, "username: {$username} | {$message}");

            return response()->json([
                'status' => 1, 'title' => $title, 'message' => $message,
                'alert'  => 'success', 'adjCode' => $adjCode, 'oEdit' => true,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail($title, ["{$title} gagal: " . $e->getMessage()], $username);
        }
    }

    // -------------------------------------------------------------------------
    //  UPDATE — jalur REVISI (4/6): stok sudah terdampak.
    //  Wajib reason, dicatat diff-nya, TAPI stok & movement TIDAK disentuh.
    //  Dokumen turun ke REVISED; posting ulang yang menerapkan selisih.
    // -------------------------------------------------------------------------

    private function reviseDocument(Request $request, object $hdr)
    {
        $username = Auth::user()->username;
        $title    = "Revisi {$this->title}";
        $adjCode  = $hdr->adj_code;
        $articles = json_decode($request->articles);
        $reason   = trim((string) $request->reason);

        if (!$this->canPost()) {
            return $this->fail($title, ['Anda tidak berwenang merevisi dokumen yang sudah diposting.']);
        }
        if ($reason === '') {
            return $this->fail($title, ['Alasan revisi harus diisi.']);
        }
        if ($request->location && $request->location !== $hdr->location_code) {
            // Pindah lokasi = reverse di loc lama + apply di loc baru: dua mutasi
            // yang tidak bisa jadi satu baris movement.
            return $this->fail($title, [
                'Perubahan Location tidak bisa lewat revisi. Silakan Cancel lalu buat dokumen baru.',
            ]);
        }
        if ($err = $this->validatePayload($request, $articles, false)) {
            return $this->fail($title, $err);
        }
        if (!$this->toYmd($request->adjDate)) {
            return $this->fail($title, ["Format tanggal tidak valid: {$request->adjDate}"]);
        }

        DB::beginTransaction();
        try {
            $oldDets = DB::table('stock_adjustment_det')
                ->where('adj_code', $adjCode)->get()->keyBy('article_code');
            $newDets = collect($articles)->keyBy('article_code');

            $newHdr = [
                'adj_date'    => $request->adjDate,
                'adj_type'    => $request->adjType,
                'description' => $request->description,
                'note'        => $request->note,
                'periode'     => $request->periode,
            ];

            $diff = $this->buildRevisionDiff($hdr, $newHdr, $oldDets, $newDets);

            if (empty($diff['header']) && empty($diff['detail'])) {
                DB::rollBack();
                return response()->json([
                    'status' => 0, 'title' => $title,
                    'message' => ['Tidak ada perubahan yang terdeteksi.'], 'alert' => 'warning',
                ]);
            }

            $revNo = ((int) ($hdr->rev_no ?? 0)) + 1;

            DB::table('stock_adjustment_hdr')->where('adj_code', $adjCode)->update($newHdr + [
                'direction'  => $this->summarizeDirection($articles),
                'status'     => self::ST_REVISED,   // menunggu posting ulang
                'rev_no'     => $revNo,
                'updated_by' => $username,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $this->syncDetails($adjCode, $newDets, $username);

            DB::table('stock_adjustment_revision_log')->insert([
                'adj_code'   => $adjCode,
                'rev_no'     => $revNo,
                'action'     => 'REVISI',
                'reason'     => $reason,
                'changes'    => json_encode($diff, JSON_UNESCAPED_UNICODE),
                'revised_by' => $username,
                'revised_at' => date('Y-m-d H:i:s'),
            ]);

            DB::commit();

            $message = "{$title} {$adjCode} tersimpan (rev.{$revNo}). "
                     . 'Status REVISED — lakukan Posting Revisi untuk menerapkan ke stok.';
            $this->logActivity($title, "username: {$username} | {$message} Reason: {$reason}");

            return response()->json([
                'status' => 1, 'title' => $title, 'message' => $message,
                'alert'  => 'success', 'adjCode' => $adjCode, 'revNo' => $revNo, 'oEdit' => true,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail($title, ["{$title} gagal: " . $e->getMessage()], $username);
        }
    }

    // =========================================================================
    //  POSTING — dispatcher
    // =========================================================================

    public function posting(Request $request)
    {
        $username = Auth::user()->username;
        $id       = Crypt::decryptString($request->id);
        $title    = "Posting {$this->title}";

        if (!$this->canPost()) {
            return $this->backWarn($title, 'Anda tidak berwenang melakukan posting.');
        }

        $hdr = DB::table('stock_adjustment_hdr')->where('id', $id)->first();
        if (!$hdr)                              return $this->backWarn($title, 'Data tidak ditemukan.');
        if ($hdr->status === self::ST_POSTED)   return $this->backWarn($title, 'Data sudah diposting.');
        if ($hdr->status === self::ST_CANCELED) return $this->backWarn($title, 'Data sudah CANCELED.');

        return $hdr->status === self::ST_REVISED
            ? $this->postRevision($hdr, $username)
            : $this->postFresh($hdr, $username);
    }

    // -------------------------------------------------------------------------
    //  POSTING BARU — status 1/2/3 → 4. Movement di-INSERT.
    // -------------------------------------------------------------------------

    private function postFresh(object $hdr, string $username)
    {
        $title      = "Posting {$this->title}";
        $adjDateYmd = $this->toYmd($hdr->adj_date);

        if (!$adjDateYmd) {
            return $this->backWarn($title, "Format tanggal adjustment tidak valid: {$hdr->adj_date}");
        }

        $details = $this->getPostingDetails($hdr->adj_code, $hdr->location_code);
        if ($details->isEmpty()) {
            return $this->backWarn($title, "{$title} {$hdr->adj_code} gagal: tidak ada detail artikel.");
        }

        DB::beginTransaction();
        try {
            $this->lockStockRows($details->pluck('article_code')->all(), $hdr->location_code);

            $movementSeq = (int) DB::table('warehouse_movement')->max('movement_code');
            $rows        = [];

            foreach ($details as $val) {
                $movementSeq++;
                $rows[] = $this->applyAdjustmentToStock($val, $hdr, $adjDateYmd, $username, $movementSeq);
            }

            DB::table('warehouse_movement')->insert($rows);

            DB::table('stock_adjustment_hdr')->where('id', $hdr->id)->update([
                'status'        => self::ST_POSTED,
                'authorized_by' => $username,
                'authorized_at' => date('Y-m-d H:i:s'),
                'updated_by'    => $username,
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);

            DB::commit();

            $message = "{$title} {$hdr->adj_code} berhasil diposting.";
            $this->logActivity($title, "username: {$username} | {$message}");

            return redirect()->back()->with(['title' => $title, 'alert' => 'success', 'message' => $message]);
        } catch (\Exception $e) {
            DB::rollBack();
            $message = "{$title} {$hdr->adj_code} gagal: " . $e->getMessage();
            $this->logActivity($title, "username: {$username} | {$message}");
            return redirect()->back()->with(['title' => $title, 'alert' => 'warning', 'message' => $message]);
        }
    }

    // -------------------------------------------------------------------------
    //  POSTING ULANG — status 6 → 4. Hanya SELISIH yang diterapkan;
    //  baris movement lama di-UPDATE in-place, bukan ditambah.
    // -------------------------------------------------------------------------

    private function postRevision(object $hdr, string $username)
    {
        $title      = "Posting Revisi {$this->title}";
        $adjCode    = $hdr->adj_code;
        $location   = $hdr->location_code;
        $adjDateYmd = $this->toYmd($hdr->adj_date);

        if (!$adjDateYmd) {
            return $this->backWarn($title, "Format tanggal adjustment tidak valid: {$hdr->adj_date}");
        }

        $details = $this->getPostingDetails($adjCode, $location);
        if ($details->isEmpty()) {
            return $this->backWarn($title, "{$title} {$adjCode} gagal: tidak ada detail artikel.");
        }

        DB::beginTransaction();
        try {
            // Nilai yang BENAR-BENAR sudah masuk stok, dibaca dari movement.
            // stock_adjustment_det sudah berisi nilai baru, jadi tidak bisa
            // dipakai sebagai acuan "sebelumnya".
            $baseline = $this->postedBaseline($adjCode);
            $newDets  = $details->keyBy('article_code');
            $codes    = $baseline->keys()->merge($newDets->keys())->unique()->values();

            $this->lockStockRows($codes->all(), $location);

            $movSeq  = (int) DB::table('warehouse_movement')->max('movement_code');
            $applied = 0;

            foreach ($codes as $code) {
                $new = $newDets->get($code);

                $oldSigned = (float) $baseline->get($code, 0);
                $newSigned = $this->signedQty($new);
                $delta     = $newSigned - $oldSigned;

                if (abs($delta) > self::EPSILON) {
                    $this->ensureStockRow($code, $location, $new);
                    DB::table('warehouse_stock')
                        ->where('site_code', $this->siteCode)
                        ->where('article_code', $code)
                        ->where('location_number', $location)
                        ->update(['article_qty' => DB::raw('coalesce(article_qty,0) + (' . $delta . ')')]);
                    $applied++;
                }

                $movSeq = $this->syncMovementRow($code, $new, $newSigned, $hdr, $username, $movSeq);
            }

            DB::table('stock_adjustment_hdr')->where('id', $hdr->id)->update([
                'status'        => self::ST_POSTED,
                'authorized_by' => $username,
                'authorized_at' => date('Y-m-d H:i:s'),
                'updated_by'    => $username,
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);

            // last_qty movement sesudah titik ini jadi basi kalau tidak dihitung
            // ulang. Bagian yang paling sering terlewat.
            $this->recalcLastQty($codes->all(), $location);

            DB::commit();

            $message = "{$title} {$adjCode} berhasil (rev.{$hdr->rev_no}). "
                     . "{$applied} artikel berubah stoknya.";
            $this->logActivity($title, "username: {$username} | {$message}");

            return redirect()->back()->with(['title' => $title, 'alert' => 'success', 'message' => $message]);
        } catch (\Exception $e) {
            DB::rollBack();
            $message = "{$title} {$adjCode} gagal: " . $e->getMessage();
            $this->logActivity($title, "username: {$username} | {$message}");
            return redirect()->back()->with(['title' => $title, 'alert' => 'warning', 'message' => $message]);
        }
    }

    /**
     * Terapkan satu baris detail ke warehouse_stock + article (cost),
     * kembalikan baris siap-insert untuk warehouse_movement.
     *
     * qty acuan dari saldo HISTORIS pada adjDate (get_last_qty_new), bukan
     * article_qty current — supaya weighted-average dihitung dari posisi stok
     * yang benar secara kronologis. Mutasi article_qty tetap delta (+/-)
     * terhadap saldo current, karena delta berlaku sama di titik manapun.
     */
    private function applyAdjustmentToStock(
        object $val, object $hdr, string $adjDateYmd, string $username, int $movementSeq
    ): array {
        $qty       = (float) $val->qty_adjustment;
        $avgCost   = (float) $val->avg_cost;
        $direction = $val->direction;
        $location  = $hdr->location_code;

        $this->ensureStockRow($val->article_code, $location, $val);

        $qtyAtAdjDate = $this->qtyAt($val->article_code, $adjDateYmd, $location);
        $avgNow       = $this->currentAvg($val->article_code, $location);

        if ($avgCost <= 0) {
            $avgCost = $avgNow;
        }

        $stockQ = fn() => DB::table('warehouse_stock')
            ->where('site_code', $this->siteCode)
            ->where('article_code', $val->article_code)
            ->where('location_number', $location);

        if ($direction === '+') {
            $qtyBaru = $qtyAtAdjDate + $qty;
            $avgBaru = $qtyBaru > 0
                ? (($qtyAtAdjDate * $avgNow) + ($qty * $avgCost)) / $qtyBaru
                : $avgNow;

            $stockQ()->update([
                'article_qty' => DB::raw("coalesce(article_qty,0) + {$qty}"),
                'avg_price'   => $avgBaru,
            ]);

            [$movMin, $movPlus] = [0, $qty];
        } else {
            $stockQ()->update(['article_qty' => DB::raw("coalesce(article_qty,0) - {$qty}")]);
            [$movMin, $movPlus] = [$qty, 0];
        }

        if ($avgCost > 0) {
            DB::table('article')->where('article_code', $val->article_code)->update([
                'lastcost'   => $avgCost,
                'avgcost'    => $avgCost,
                'updated_by' => $username,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return [
            'movement_code'     => $movementSeq,
            'movement_date'     => $hdr->adj_date,
            'artikel_code'      => $val->article_code,
            'artikel_desc'      => $val->article_desc ?? '',
            'movement_min'      => $movMin,
            'movement_plus'     => $movPlus,
            'movement_price'    => $avgCost,
            'movement_transnno' => $hdr->adj_code,
            'movement_type'     => self::MV_ADJUSTMENT,
            'movement_desc'     => trim("{$hdr->adj_type} ({$hdr->description})"),
            'partner_type'      => self::PARTNER_TYPE,
            'uom'               => $val->uom,
            'created_by'        => $username,
            'created_at'        => date('Y-m-d H:i:s'),
            'site_code'         => $this->siteCode,
            'location_number'   => $location,
            'movement_from'     => $direction === '-' ? $location : '-',
            'movement_to'       => $direction === '+' ? $location : '-',
            'last_qty'          => DB::raw(
                "get_last_qty_new('{$val->article_code}','{$adjDateYmd}','{$this->siteCode}','{$location}')"
                . ($direction === '+' ? " + {$qty}" : " - {$qty}")
            ),
        ];
    }

    /**
     * Update / insert / delete satu baris warehouse_movement milik dokumen ini.
     * Return movementSeq terbaru (naik hanya kalau ada insert).
     */
    private function syncMovementRow(
        string $code, ?object $new, float $newSigned, object $hdr, string $username, int $movSeq
    ): int {
        $location = $hdr->location_code;

        $mov = fn() => DB::table('warehouse_movement')
            ->where('movement_transnno', $hdr->adj_code)
            ->where('artikel_code', $code)
            ->where('movement_type', self::MV_ADJUSTMENT);

        // Artikel dibuang dari dokumen → baris movement-nya ikut hilang.
        if (!$new) {
            $mov()->delete();
            return $movSeq;
        }

        $revNo = (int) ($hdr->rev_no ?? 0);
        $desc  = trim("{$hdr->adj_type} ({$hdr->description})");
        if ($revNo > 0) {
            // Penanda halus: angka ini pernah direvisi, telusuri di revision_log.
            // Tidak menambah baris, jadi report movement tetap bersih.
            $desc .= " [rev.{$revNo}]";
        }

        $payload = [
            'movement_date'  => $hdr->adj_date,
            'artikel_desc'   => $new->article_desc ?? '',
            'movement_min'   => $newSigned < 0 ? abs($newSigned) : 0,
            'movement_plus'  => $newSigned > 0 ? $newSigned : 0,
            'movement_price' => $this->currentAvg($code, $location),
            'movement_desc'  => $desc,
            'uom'            => $new->uom,
            'movement_from'  => $newSigned < 0 ? $location : '-',
            'movement_to'    => $newSigned > 0 ? $location : '-',
        ];

        if ($mov()->exists()) {
            $mov()->update($payload);
            return $movSeq;
        }

        $movSeq++;
        DB::table('warehouse_movement')->insert($payload + [
            'movement_code'     => $movSeq,
            'artikel_code'      => $code,
            'movement_transnno' => $hdr->adj_code,
            'movement_type'     => self::MV_ADJUSTMENT,
            'partner_type'      => self::PARTNER_TYPE,
            'site_code'         => $this->siteCode,
            'location_number'   => $location,
            'created_by'        => $username,
            'created_at'        => date('Y-m-d H:i:s'),
            'last_qty'          => 0,   // diisi ulang oleh recalcLastQty()
        ]);

        return $movSeq;
    }

    // =========================================================================
    //  CANCEL — status 4 atau 6
    // =========================================================================

    public function cancel(Request $request)
    {
        $username = Auth::user()->username;
        $id       = Crypt::decryptString($request->id);
        $title    = "Cancel {$this->title}";

        if (!$this->canPost()) {
            return $this->backWarn($title, 'Anda tidak berwenang melakukan cancel.');
        }

        $reason = trim((string) $request->reason);
        if ($reason === '') {
            return $this->backWarn($title, 'Cancel reason harus diisi.');
        }

        $hdr = DB::table('stock_adjustment_hdr')->where('id', $id)->first();
        if (!$hdr) {
            return $this->backWarn($title, 'Data tidak ditemukan.');
        }
        if (!in_array($hdr->status, self::ST_LIVE, true)) {
            return $this->backWarn($title, 'Hanya dokumen POSTED atau REVISED yang bisa dicancel.');
        }

        $adjDateYmd = $this->toYmd($hdr->adj_date);
        if (!$adjDateYmd) {
            return $this->backWarn($title, "Format tanggal adjustment tidak valid: {$hdr->adj_date}");
        }

        $location = $hdr->location_code;

        // PENTING: baca dari warehouse_movement, bukan dari det.
        // Untuk dokumen REVISED, det berisi nilai BARU yang belum pernah masuk
        // stok — membalik nilai itu akan merusak saldo. Yang harus dibalik
        // adalah apa yang benar-benar terposting.
        $baseline = $this->postedBaseline($hdr->adj_code);

        if ($baseline->isEmpty()) {
            return $this->backWarn($title, "{$title} {$hdr->adj_code} gagal: tidak ada movement terposting.");
        }

        DB::beginTransaction();
        try {
            $this->lockStockRows($baseline->keys()->all(), $location);

            $seq  = (int) DB::table('warehouse_movement')->max('movement_code');
            $rows = [];

            $meta = DB::table('article')
                ->whereIn('article_code', $baseline->keys()->all())
                ->get()->keyBy('article_code');

            foreach ($baseline as $code => $signed) {
                $signed = (float) $signed;
                if (abs($signed) < self::EPSILON) continue;

                $art = $meta->get($code);
                $this->ensureStockRow($code, $location, $art);

                // Balik arah: yang dulu masuk sekarang keluar, dan sebaliknya.
                DB::table('warehouse_stock')
                    ->where('site_code', $this->siteCode)
                    ->where('article_code', $code)
                    ->where('location_number', $location)
                    ->update(['article_qty' => DB::raw('coalesce(article_qty,0) - (' . $signed . ')')]);

                $seq++;
                $rows[] = [
                    'movement_code'     => $seq,
                    'movement_date'     => $hdr->adj_date,
                    'artikel_code'      => $code,
                    'artikel_desc'      => $art->article_desc ?? '',
                    'movement_min'      => $signed > 0 ? $signed : 0,
                    'movement_plus'     => $signed < 0 ? abs($signed) : 0,
                    'movement_price'    => $this->currentAvg($code, $location),
                    'movement_transnno' => $hdr->adj_code,
                    'movement_type'     => self::MV_CANCEL,
                    'movement_desc'     => trim("Cancel: {$hdr->adj_type} {$hdr->description}; {$reason}"),
                    'partner_type'      => self::PARTNER_TYPE,
                    'uom'               => $art->uom ?? null,
                    'created_by'        => $username,
                    'created_at'        => date('Y-m-d H:i:s'),
                    'site_code'         => $this->siteCode,
                    'location_number'   => $location,
                    'movement_from'     => $signed > 0 ? $location : '-',
                    'movement_to'       => $signed < 0 ? $location : '-',
                    'last_qty'          => 0,   // diisi ulang oleh recalcLastQty()
                ];
            }

            DB::table('warehouse_movement')->insert($rows);

            $revNo = ((int) ($hdr->rev_no ?? 0)) + 1;

            DB::table('stock_adjustment_hdr')->where('id', $id)->update([
                'status'     => self::ST_CANCELED,
                'rev_no'     => $revNo,
                'note'       => trim(($hdr->note ?? '') . "; (Cancel by {$username}, Reason: {$reason})", '; '),
                'updated_by' => $username,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            // Cancel ikut log yang sama — satu tempat untuk seluruh jejak dokumen.
            DB::table('stock_adjustment_revision_log')->insert([
                'adj_code'   => $hdr->adj_code,
                'rev_no'     => $revNo,
                'action'     => 'CANCEL',
                'reason'     => $reason,
                'changes'    => json_encode([
                    'header' => [[
                        'field' => 'status',
                        'from'  => self::STATUS_LABEL[$hdr->status] ?? $hdr->status,
                        'to'    => 'CANCELED',
                    ]],
                    'detail' => [],
                ], JSON_UNESCAPED_UNICODE),
                'revised_by' => $username,
                'revised_at' => date('Y-m-d H:i:s'),
            ]);

            $this->recalcLastQty($baseline->keys()->all(), $location);

            DB::commit();

            $message = "{$title} {$hdr->adj_code} berhasil dicancel. Reason: {$reason}";
            $this->logActivity($title, "username: {$username} | {$message}");

            return redirect()->back()->with(['title' => $title, 'alert' => 'success', 'message' => $message]);
        } catch (\Exception $e) {
            DB::rollBack();
            $message = "{$title} {$hdr->adj_code} gagal: " . $e->getMessage();
            $this->logActivity($title, "username: {$username} | {$message}");
            return redirect()->back()->with(['title' => $title, 'alert' => 'error', 'message' => $message]);
        }
    }

    // =========================================================================
    //  REVISION DIFF — hanya yang berubah
    // =========================================================================

    /**
     * Bandingkan kondisi lama vs baru; hasilkan diff minimal.
     * Field yang tidak berubah tidak masuk output sama sekali — itu sebabnya
     * log tetap kecil walau dokumennya hasil import ribuan baris.
     */
    private function buildRevisionDiff(object $oldHdr, array $newHdr, Collection $oldDets, Collection $newDets): array
    {
        $diff = ['header' => [], 'detail' => []];

        foreach (self::REV_HDR_FIELDS as $f) {
            $from = $oldHdr->{$f} ?? null;
            $to   = $newHdr[$f]   ?? null;
            if ((string) $from !== (string) $to) {
                $diff['header'][] = ['field' => $f, 'from' => $from, 'to' => $to];
            }
        }

        $codes = $oldDets->keys()->merge($newDets->keys())->unique();

        foreach ($codes as $code) {
            $old = $oldDets->get($code);
            $new = $newDets->get($code);

            if (!$old) {
                $diff['detail'][] = ['article_code' => $code, 'type' => 'ADDED', 'after' => $this->pickDet($new)];
                continue;
            }
            if (!$new) {
                $diff['detail'][] = ['article_code' => $code, 'type' => 'REMOVED', 'before' => $this->pickDet($old)];
                continue;
            }

            $fields = [];
            foreach (self::REV_DET_FIELDS as $f) {
                $from = $old->{$f} ?? null;
                $to   = $new->{$f} ?? null;

                // $oldDets dari PostgreSQL (numeric → string "10.0000"),
                // $newDets dari json_decode (float 10). Tanpa cast numerik,
                // keduanya ke-detect "berubah" padahal sama.
                if (in_array($f, self::REV_NUM_FIELDS, true)) {
                    if (abs((float) $from - (float) $to) > self::EPSILON) {
                        $fields[] = ['field' => $f, 'from' => (float) $from, 'to' => (float) $to];
                    }
                } elseif ((string) $from !== (string) $to) {
                    $fields[] = ['field' => $f, 'from' => $from, 'to' => $to];
                }
            }

            if ($fields) {
                $diff['detail'][] = ['article_code' => $code, 'type' => 'MODIFIED', 'fields' => $fields];
            }
        }

        return $diff;
    }

    private function pickDet($d): array
    {
        return array_intersect_key((array) $d, array_flip(self::REV_DET_FIELDS));
    }

    // =========================================================================
    //  REVISION HISTORY
    // =========================================================================

    public function revisionHistory(Request $request)
    {
        return response()->json(['status' => 1, 'data' => $this->revisionRows($request->adjCode)]);
    }

    private function revisionRows(?string $adjCode): Collection
    {
        if (!$adjCode) return collect();

        return DB::table('stock_adjustment_revision_log')
            ->where('adj_code', $adjCode)
            ->orderBy('rev_no', 'desc')
            ->get()
            ->map(function ($r) {
                $r->changes = json_decode($r->changes, true);
                return $r;
            });
    }

    // =========================================================================
    //  DESTROY — hanya draft
    // =========================================================================

    public function destroy(Request $request)
    {
        $username = Auth::user()->username;
        $id       = Crypt::decryptString($request->id);

        $hdr = DB::table('stock_adjustment_hdr')->where('id', $id)->first();

        if (!$hdr) {
            return $this->backWarn('Delete', 'Data tidak ditemukan.');
        }
        if (!in_array($hdr->status, self::ST_EDITABLE, true)) {
            return $this->backWarn(
                'Delete',
                'Data berstatus ' . (self::STATUS_LABEL[$hdr->status] ?? '-') . ', tidak bisa dihapus. Gunakan Cancel.'
            );
        }
        if ($hdr->created_by !== $username && !$this->isPrivileged()) {
            return $this->backWarn('Delete', 'Anda tidak berwenang menghapus data ini.');
        }

        DB::table('stock_adjustment_hdr')->where('id', $id)->delete();  // detail via CASCADE

        $message = "Delete {$this->title} {$hdr->adj_code} berhasil.";
        $this->logActivity("Delete {$this->title}", "username: {$username} | {$message}");

        return redirect()->back()->with([
            'title' => "Delete {$this->title}", 'alert' => 'success', 'message' => $message,
        ]);
    }

    // =========================================================================
    //  STOCK BEFORE (AJAX)
    // =========================================================================

    /**
     * Saldo sebelum dokumen ini, pada tanggal adjustment.
     *
     * Kalau adjCode dikirim DAN dokumennya sudah punya movement terposting,
     * kontribusi dokumen itu sendiri dikeluarkan — get_last_qty_new memfilter
     * `<= tanggal`, jadi movement dokumen ini ikut terhitung. Tanpa koreksi ini,
     * layar revisi menampilkan stock_before yang sudah termasuk adjustment-nya
     * sendiri (dobel).
     */
    public function stockBefore(Request $request)
    {
        $adjDateYmd = $this->toYmd($request->adjDate);

        if (!$adjDateYmd) {
            return response()->json(['stock' => 0]);
        }

        $stock = $this->qtyAt($request->article_code, $adjDateYmd, $request->location_code);

        if ($request->adjCode) {
            $stock -= (float) $this->postedBaseline($request->adjCode)->get($request->article_code, 0);
        }

        return response()->json(['stock' => $stock]);
    }

    /**
     * Versi bulk untuk import Excel.
     *
     * Import bisa ribuan artikel; kalau tiap baris memanggil stockBefore()
     * sendiri-sendiri, itu ribuan HTTP request. Endpoint ini mengembalikan
     * semuanya dalam SATU query (unnest).
     */
    public function stockBeforeBulk(Request $request)
    {
        $adjDateYmd   = $this->toYmd($request->adjDate);
        $locationCode = $request->location_code;
        $articleCodes = $request->article_codes;

        if (!$adjDateYmd || !$locationCode || !is_array($articleCodes)) {
            return response()->json(['stocks' => (object) []]);
        }

        $articleCodes = array_values(array_unique(array_filter(
            $articleCodes, fn($c) => trim((string) $c) !== ''
        )));

        if (empty($articleCodes)) {
            return response()->json(['stocks' => (object) []]);
        }

        $placeholders = implode(',', array_fill(0, count($articleCodes), '?'));

        $rows = DB::select("
            SELECT ac as article_code,
                   get_last_qty_new(ac, ?, ?, ?) as qty
            FROM (SELECT unnest(ARRAY[{$placeholders}]) as ac) t
        ", array_merge([$adjDateYmd, $this->siteCode, $locationCode], $articleCodes));

        $baseline = $request->adjCode ? $this->postedBaseline($request->adjCode) : collect();

        $stocks = [];
        foreach ($rows as $r) {
            $stocks[$r->article_code] = (float) $r->qty - (float) $baseline->get($r->article_code, 0);
        }

        return response()->json(['stocks' => $stocks]);
    }

    // =========================================================================
    //  AUTO-CODE
    // =========================================================================

    public function getLastCode(string $key): string
    {
        DB::table('master_code')->where('code_key', $key)->update([
            'code_number' => DB::raw('code_number + 1'),
            'updated_by'  => Auth::user()->username,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        $newCode = DB::table('master_code')->where('code_key', $key)->value('code_number');
        $months  = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];

        return sprintf(
            '%s-ASN-%s-%s-%s',
            $key, date('Y'), $months[date('n') - 1], str_pad($newCode, 4, '0', STR_PAD_LEFT)
        );
    }

    // =========================================================================
    //  IMPORT / EXPORT
    // =========================================================================

    public function import(Request $request) { return $this->importExcel($request); }
    public function exportExcel()            { return $this->export(); }

    public function importExcel(Request $request)
    {
        $this->validate($request, ['file' => 'required|mimes:xls,xlsx|max:5120']);

        $namaFile = Auth::user()->username . '_' . time();
        $title    = "Import {$this->title}";

        DB::table('import_adjustment_tmp')->where('file_name', $namaFile)->delete();

        try {
            try {
                Excel::import(new StockAdjustmentImport($namaFile), $request->file('file'));
            } catch (\Exception $e) {
                return $this->fail($title, ['Gagal membaca file: ' . $e->getMessage()]);
            }

            $rowCount = DB::table('import_adjustment_tmp')->where('file_name', $namaFile)->count();

            if ($rowCount === 0) {
                return $this->fail($title, ['File kosong atau format kolom tidak sesuai template.']);
            }
            if ($rowCount > self::IMPORT_MAX_ROWS) {
                return $this->fail($title, [
                    "File berisi {$rowCount} baris, melebihi batas maksimal " . self::IMPORT_MAX_ROWS
                    . ' baris per import. Silakan pecah file menjadi beberapa bagian.',
                ]);
            }

            // Satu query gabungan: dipakai untuk validasi SEKALIGUS data final.
            $rows = DB::table('import_adjustment_tmp as t')
                ->leftJoin('article as a', 'a.article_alternative_code', '=', 't.article_code')
                ->where('t.file_name', $namaFile)
                ->select('t.article_code as input_code', 't.qty as qty_adjustment', 't.notes', 'a.article_code', 'a.uom')
                ->get();

            $errors = [];
            foreach ($rows as $r) {
                if (is_null($r->article_code)) {
                    $errors[] = "Article Code {$r->input_code} tidak terdaftar.";
                }
                if (abs((float) $r->qty_adjustment) < self::EPSILON) {
                    $errors[] = "Article {$r->input_code} - Qty tidak boleh 0.";
                }
            }

            if ($errors) {
                return response()->json([
                    'status'     => 0,
                    'title'      => $title,
                    'message'    => array_map(fn($e) => [$e], $errors),  // bentuk lama dipertahankan
                    'alert'      => 'error',
                    'pesan'      => 'Ada error pada data yang diupload!',
                    'dataDetail' => [],
                ]);
            }

            // uom_member: satu query grouped, bukan subquery per-baris.
            $uomMemberMap = DB::table('uom_con_v2')
                ->select('article_code', DB::raw("string_agg(unit_to, ',' order by unit_from) as uom_member"))
                ->whereIn('article_code', $rows->pluck('article_code')->filter()->unique()->values()->all())
                ->groupBy('article_code')
                ->pluck('uom_member', 'article_code');

            // stock_before sengaja 0 — diisi belakangan oleh stockBeforeBulk()
            // dari frontend, supaya proses import tetap ringan.
            $data = $rows->map(fn($r) => [
                'article_code'   => $r->article_code,
                'qty_adjustment' => $r->qty_adjustment,
                'uom'            => $r->uom,
                'uom_member'     => $uomMemberMap[$r->article_code] ?? null,
                'notes'          => $r->notes,
                'stock_before'   => 0,
            ])->values();

            return response()->json([
                'status' => 1, 'title' => $title, 'message' => "{$title} berhasil diimport.",
                'alert'  => 'success', 'pesan' => '', 'dataDetail' => $data,
            ]);
        } finally {
            DB::table('import_adjustment_tmp')->where('file_name', $namaFile)->delete();
        }
    }

    public function export()
    {
        return Excel::download(new StockAdjustmentExport, 'stock_adjustment_template.xlsx');
    }

    // =========================================================================
    //  PRINT
    // =========================================================================

    public function print(Request $request)
    {
        $id = Crypt::decryptString($request->id);

        $hdr = DB::table('stock_adjustment_hdr as h')
            ->leftJoin('stock_location_master as loc', 'loc.location_code', '=', 'h.location_code')
            ->where('h.id', $id)
            ->select('h.*', 'loc.location_name')
            ->first();

        if (!$hdr) abort(404);

        return view('stockAdjustment.print', [
            'hdr'       => $hdr,
            'details'   => $this->detailRows($hdr->adj_code),
            'revisions' => $this->revisionRows($hdr->adj_code),
        ]);
    }

    // =========================================================================
    //  HELPERS — AUTH & SCOPE
    // =========================================================================

    private function isPrivileged(): bool
    {
        return Auth::user()->hasAnyRole(self::ROLE_PRIVILEGED);
    }

    private function canPost(): bool
    {
        return Auth::user()->hasAnyRole(self::ROLE_POSTING);
    }

    private function userDepts(): array
    {
        return DB::table('user_dept')
            ->where('username', Auth::user()->username)
            ->pluck('dept')->toArray();
    }

    private function allowedLocations()
    {
        return DB::table('stock_location_master')
            ->when(!$this->isPrivileged(), fn($q) => $q->whereIn('dept_code', $this->userDepts()))
            ->orderBy('location_name')
            ->get();
    }

    // =========================================================================
    //  HELPERS — QUERY
    // =========================================================================

    private function detailRows(string $adjCode, bool $withUomMember = false)
    {
        $q = DB::table('stock_adjustment_det as d')
            ->leftJoin('article as a', 'a.article_code', '=', 'd.article_code')
            ->where('d.adj_code', $adjCode)
            ->select('d.*', 'a.article_alternative_code', 'a.article_desc');

        if ($withUomMember) {
            $q->addSelect(DB::raw(
                "(select string_agg(unit_to, ',' order by unit_from)
                    from uom_con_v2 where article_code = d.article_code) as uom_member"
            ));
        }

        return $q->orderBy('d.id')->get();
    }

    private function getPostingDetails(string $adjCode, string $location)
    {
        return DB::table('stock_adjustment_det as d')
            ->leftJoin('article as a', 'a.article_code', '=', 'd.article_code')
            ->where('d.adj_code', $adjCode)
            ->select(
                'd.*', 'a.article_type', 'a.article_desc', 'a.article_alternative_code',
                'a.uom as article_uom',
                DB::raw('coalesce((select avg_price from warehouse_stock
                    where site_code       = ?
                      and article_code    = d.article_code
                      and location_number = ? limit 1), 0) as avg_cost')
            )
            ->addBinding([$this->siteCode, $location], 'select')
            ->get();
    }

    /**
     * Qty bertanda (signed) yang BENAR-BENAR sudah masuk stok untuk dokumen ini,
     * dibaca dari warehouse_movement.
     *
     * Ini satu-satunya sumber kebenaran "apa yang sudah terposting". Saat status
     * REVISED, stock_adjustment_det sudah berisi nilai pending yang belum masuk
     * stok, jadi tidak bisa dipakai sebagai acuan.
     *
     * @return Collection<string, float> article_code => signed qty
     */
    private function postedBaseline(string $adjCode): Collection
    {
        return DB::table('warehouse_movement')
            ->where('movement_transnno', $adjCode)
            ->where('movement_type', self::MV_ADJUSTMENT)
            ->groupBy('artikel_code')
            ->select('artikel_code', DB::raw(
                'sum(coalesce(movement_plus,0) - coalesce(movement_min,0)) as signed_qty'
            ))
            ->pluck('signed_qty', 'artikel_code')
            ->map(fn($v) => (float) $v);
    }

    private function qtyAt(string $articleCode, string $dateYmd, string $location): float
    {
        return (float) DB::selectOne(
            'SELECT get_last_qty_new(?, ?, ?, ?) as qty',
            [$articleCode, $dateYmd, $this->siteCode, $location]
        )->qty;
    }

    private function currentAvg(string $articleCode, string $location): float
    {
        return (float) (DB::table('warehouse_stock')
            ->where('site_code', $this->siteCode)
            ->where('article_code', $articleCode)
            ->where('location_number', $location)
            ->value('avg_price') ?? 0);
    }

    private function ensureStockRow(string $articleCode, string $location, ?object $val): void
    {
        DB::table('warehouse_stock')->updateOrInsert(
            ['site_code' => $this->siteCode, 'article_code' => $articleCode, 'location_number' => $location],
            array_filter([
                'dept_code' => $val->article_type ?? null,
                'uom'       => $val->article_uom ?? ($val->uom ?? null),
            ], fn($v) => $v !== null)
        );
    }

    /**
     * Kunci baris stok sebelum dimutasi — mencegah race dengan posting DN /
     * transfer lain yang menyentuh artikel+lokasi sama di tengah transaksi.
     */
    private function lockStockRows(array $articleCodes, string $location): void
    {
        if (empty($articleCodes)) return;

        DB::table('warehouse_stock')
            ->where('site_code', $this->siteCode)
            ->where('location_number', $location)
            ->whereIn('article_code', $articleCodes)
            ->lockForUpdate()
            ->get();
    }

    /**
     * Hitung ulang last_qty seluruh movement untuk artikel+lokasi terkait.
     *
     * Ekspresi & ordering DISAMAKAN PERSIS dengan get_last_qty_new():
     *   - SUM(-movement_min + movement_plus)  ← tanpa coalesce, lihat catatan
     *   - ORDER BY TO_DATE(movement_date,'dd-mm-yyyy'), movement_code
     *   - tanpa filter movement_type
     *
     * CATATAN NULL: get_last_qty_new tidak pakai coalesce. Kalau movement_min
     * ATAU movement_plus NULL, ekspresinya jadi NULL dan baris itu tidak
     * dihitung sama sekali oleh SUM. Kita replikasi perilaku itu supaya angka
     * konsisten dengan fungsi. Cek dulu:
     *   SELECT count(*) FROM warehouse_movement
     *   WHERE movement_min IS NULL OR movement_plus IS NULL;
     * Kalau 0, coalesce vs tidak sama saja.
     *
     * CATATAN GRANULARITAS: get_last_qty_new memfilter `<= TO_DATE(p_date)`,
     * jadi per-HARI bukan per-movement. Recalc ini per-POSISI (ROWS). Identik
     * selama hanya ada 1 movement per artikel/lokasi/hari — kasus umum.
     */
    private function recalcLastQty(array $articleCodes, string $location): void
    {
        if (empty($articleCodes)) return;

        $ph = implode(',', array_fill(0, count($articleCodes), '?'));

        // Partition key ikut dibawa keluar CTE dan dipakai di klausa join.
        // Tanpa ini, `WHERE m.movement_code = o.movement_code` bisa menimpa
        // baris artikel/lokasi LAIN yang kebetulan punya movement_code sama —
        // target UPDATE tidak ikut terfilter oleh WHERE di dalam CTE.
        DB::statement("
            WITH ordered AS (
                SELECT artikel_code,
                       site_code,
                       location_number,
                       movement_code,
                       SUM(-movement_min + movement_plus) OVER (
                           PARTITION BY artikel_code, site_code, location_number
                           ORDER BY TO_DATE(movement_date,'dd-mm-yyyy'), movement_code
                           ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
                       ) AS bal
                FROM warehouse_movement
                WHERE artikel_code    IN ({$ph})
                  AND site_code        = ?
                  AND location_number  = ?
            )
            UPDATE warehouse_movement m
            SET last_qty = COALESCE(o.bal, 0)
            FROM ordered o
            WHERE m.movement_code   = o.movement_code
              AND m.artikel_code    = o.artikel_code
              AND m.site_code       = o.site_code
              AND m.location_number = o.location_number
        ", array_merge($articleCodes, [$this->siteCode, $location]));
    }

    // =========================================================================
    //  HELPERS — DETAIL SYNC
    // =========================================================================

    /** Samakan isi stock_adjustment_det dengan payload frontend. */
    private function syncDetails(string $adjCode, Collection $newDets, string $username): void
    {
        DB::table('stock_adjustment_det')
            ->where('adj_code', $adjCode)
            ->whereNotIn('article_code', $newDets->keys()->all())
            ->delete();

        foreach ($newDets as $code => $val) {
            DB::table('stock_adjustment_det')->updateOrInsert(
                ['adj_code' => $adjCode, 'article_code' => $code],
                $this->detRow($adjCode, $val, $username, false)
            );
        }
    }

    private function detRow(string $adjCode, object $v, string $username, bool $isInsert): array
    {
        $row = [
            'uom'            => $v->uom,
            'direction'      => $v->direction,
            'stock_before'   => $v->stock_before,
            'qty_adjustment' => $v->qty_adjustment,
            'stock_after'    => $v->stock_after,
            'notes'          => $v->notes ?? null,
            'updated_by'     => $username,
            'updated_at'     => date('Y-m-d H:i:s'),
        ];

        if ($isInsert) {
            $row += [
                'adj_code'     => $adjCode,
                'article_code' => $v->article_code,
                'created_by'   => $username,
                'created_at'   => date('Y-m-d H:i:s'),
            ];
        }

        return $row;
    }

    private function signedQty(?object $d): float
    {
        if (!$d) return 0.0;
        $q = (float) $d->qty_adjustment;
        return $d->direction === '+' ? $q : -$q;
    }

    private function summarizeDirection(array $articles): string
    {
        $dirs = array_values(array_unique(array_map(fn($a) => $a->direction ?? '+', $articles)));
        return count($dirs) === 1 ? $dirs[0] : 'MIXED';
    }

    // =========================================================================
    //  HELPERS — VALIDASI
    // =========================================================================

    /** @return array<string> daftar pesan error; kosong = lolos. */
    private function validatePayload(Request $request, $articles, bool $checkLocation = true): array
    {
        $errors = [];

        if (!$request->adjDate)  $errors[] = 'Adjustment Date harus diisi.';
        if (!$request->adjType)  $errors[] = 'Adjustment Type harus dipilih.';
        if (!$request->periode)  $errors[] = 'Periode harus dipilih.';
        if ($checkLocation && !$request->location) $errors[] = 'Location harus dipilih.';
        if (empty($articles))    $errors[] = 'Artikel harus diisi.';

        if ($errors) return $errors;

        foreach ($articles as $val) {
            if (abs((float) $val->qty_adjustment) < self::EPSILON) {
                $errors[] = "Qty Adjustment untuk artikel {$val->article_code} tidak boleh 0.";
            }
            if ((float) $val->stock_after < 0) {
                $errors[] = "Stock after untuk artikel {$val->article_code} tidak boleh negatif.";
            }
        }

        return $errors;
    }

    // =========================================================================
    //  HELPERS — DATE & RESPONSE
    // =========================================================================

    private function toYmd(?string $dmy): ?string
    {
        if (!$dmy) return null;
        $dt = \DateTime::createFromFormat('d-m-Y', trim($dmy));
        return $dt ? $dt->format('Y-m-d') : null;
    }

    private function parseDateRange(?string $trDate): array
    {
        if (!$trDate) return ['', ''];

        $parts    = explode('to', $trDate);
        $fromDate = implode('/', array_reverse(explode('-', trim($parts[0]))));
        $toDate   = count($parts) > 1
            ? implode('/', array_reverse(explode('-', trim($parts[1]))))
            : $fromDate;

        return [$fromDate, $toDate];
    }

    private function fail(string $title, array $messages, ?string $username = null)
    {
        if ($username) {
            $this->logActivity($title, "username: {$username} | " . implode(' ', $messages));
        }

        return response()->json([
            'status' => 0, 'title' => $title, 'message' => $messages, 'alert' => 'error',
        ]);
    }

    private function backWarn(string $title, string $message)
    {
        return redirect()->back()->with(['title' => $title, 'alert' => 'warning', 'message' => $message]);
    }

    /**
     * Wrapper LogActivity::addToLog() dengan truncate otomatis.
     *
     * Kolom description di log_activities adalah VARCHAR(255). Pesan error
     * dari exception (terutama yang mengandung SQL) bisa jauh lebih panjang,
     * menyebabkan insert gagal dan mengubur error aslinya di balik error log.
     * Truncate di sini supaya log tetap masuk, sisanya dibuang dengan elipsis.
     */
    private function logActivity(string $subject, string $description, int $maxLen = 250): void
    {
        if (mb_strlen($description) > $maxLen) {
            $description = mb_substr($description, 0, $maxLen - 3) . '...';
        }

        \LogActivity::addToLog($subject, $description);
    }
}