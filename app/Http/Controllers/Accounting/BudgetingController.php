<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use DB;
use PDF;
use DataTables;

ini_set('memory_limit', '1024M');
set_time_limit(300);

/**
 * Budgeting.
 *
 * Sumber data : kas_det + kas_hdr (sama seperti Buku Besar v2), status DELETED diabaikan.
 * COA         : hanya akun DETAIL yang segmen pertama kodenya ada di COA_RANGES
 *               (5000-5999, 6000-6999, 7000-7999, 8000-8999).
 * Pengelompokan: per department (cost center kosong dianggap dept 007, seperti Buku Besar).
 *
 * Satu budgeting (budgeting_hdr) = satu department + satu previous period + satu budget period.
 * Per COA (budgeting_det):
 *   debit    = jumlah kolom debit di previous period
 *   average  = debit / jumlah bulan yang debitnya TIDAK nol
 *   budget   = average - cost reduction %  (diedit di layar)
 *   final    = default sama dengan budget, bisa diedit manual
 *   realisasi_json = realisasi debit per bulan di budget period (untuk kolom bulanan + modal transaksi)
 */
class BudgetingController extends Controller
{
    /** Range segmen pertama kode COA yang ikut budgeting [dari, sampai]. */
    const COA_RANGES = [[5000, 5999], [6000, 6999], [7000, 7999], [8000, 8999]];

    /** Status voucher yang tidak pernah ikut hitung (5 = deleted). */
    const STATUS_EXCLUDED = ['5'];

    /** Cost center kosong dianggap dept ini (sama dengan Buku Besar). */
    const DEFAULT_DEPT = '007';

    const DEFAULT_COST_REDUCTION = 5;

    const BULAN = [
        1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL',
        5 => 'MEI', 6 => 'JUNI', 7 => 'JULI', 8 => 'AGUSTUS',
        9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER',
    ];

    private $title = "Budgeting";

    /* ====================================================================
     |  LIST
     * ================================================================== */

    public function index(Request $request)
    {
        return view('accounting.budgeting.index', [
            'title'       => $this->title,
            'depts'       => DB::table('depts')->orderBy('name')->get(),
            'fiscalYears' => $this->fiscalYearOptions(),
            'kolom'       => $this->getTableColumn(),
        ]);
    }

    /** AJAX: ringkasan untuk dashboard chart di index (budget vs actual per dept, sebaran status). */
    public function chart(Request $request)
    {
        $list = $this->budgetList();

        $byDept = $list->groupBy(fn($r) => $r->dept_name ?: $r->dept_code)
            ->map(fn($rows, $dept) => [
                'dept'   => $dept,
                'budget' => round($rows->sum('total_budget'), 2),
                'actual' => round($rows->sum('actual'), 2),
            ])
            ->sortByDesc('budget')
            ->values();

        $statusLabels = ['Overbudget', 'Underbudget', 'Sesuai Budget'];
        $statusCounts = collect($statusLabels)->map(fn($s) => $list->where('status', $s)->count());

        return response()->json([
            'depts'        => $byDept->pluck('dept'),
            'budget'       => $byDept->pluck('budget'),
            'actual'       => $byDept->pluck('actual'),
            'statusLabels' => $statusLabels,
            'statusCounts' => $statusCounts,
            'summary'      => [
                'total'       => $list->count(),
                'totalBudget' => round($list->sum('total_budget'), 2),
                'totalActual' => round($list->sum('actual'), 2),
                'totalMargin' => round($list->sum('margin'), 2),
                'overCount'   => $list->where('status', 'Overbudget')->count(),
            ],
        ]);
    }

    public function list(Request $request)
    {
        $list = $this->budgetList();

        if ($request->filled('number')) {
            $kw = mb_strtoupper($request->number);
            $list = $list->filter(fn($r) => str_contains(mb_strtoupper($r->budgeting_number), $kw));
        }
        if ($request->filled('dept')) {
            $list = $list->filter(fn($r) => $r->dept_code === $request->dept);
        }
        if ($request->filled('status')) {
            $list = $list->filter(fn($r) => $r->status === $request->status);
        }
        if ($request->filled('fiscalYear')) {
            $list = $list->filter(fn($r) => (string) $r->fiscal_year === (string) $request->fiscalYear);
        }

        return Datatables::of($list->values())
            ->addColumn('action', function ($r) {
                $id = $r->id;

                return '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown"><i data-feather="menu"></i></a>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a href="' . route('budgeting.edit', $id) . '" class="dropdown-item"><i data-feather="edit-2"></i> Edit</a>
                                <a href="javascript:;" onclick="deleteBudgeting(\'' . $id . '\',\'' . $r->budgeting_number . '\')" class="dropdown-item"><i data-feather="trash-2" class="feather-14-red"></i> Delete</a>
                            </div>
                        </div>';
            })
            ->addColumn('status_label', function ($r) {
                $cls = $r->status === 'Overbudget' ? 'danger' : ($r->status === 'Underbudget' ? 'success' : 'secondary');

                return '<span class="badge badge-pill badge-light-' . $cls . '">' . $r->status . '</span>';
            })
            ->addColumn('budget_used', function ($r) {
                $cls = $r->status === 'Overbudget' ? 'danger' : ($r->status === 'Underbudget' ? 'success' : 'secondary');
                $w = min($r->used_pct, 100);

                return '<div class="d-flex align-items-center"><div class="progress flex-grow-1" style="height:8px"><div class="progress-bar bg-' . $cls . '" style="width:' . $w . '%"></div></div><small class="text-muted ml-1">' . $r->used_pct . '%</small></div>';
            })
            ->editColumn('total_budget', fn($r) => number_format($r->total_budget, 2))
            ->editColumn('actual', fn($r) => number_format($r->actual, 2))
            ->addColumn('margin_label', function ($r) {
                $cls = $r->margin < 0 ? 'text-danger' : 'text-success';

                return '<span class="' . $cls . '">' . number_format($r->margin, 2) . '</span>';
            })
            ->editColumn('created_at', fn($r) => $r->created_at ? date('d-m-Y H:i', strtotime($r->created_at)) : '-')
            ->editColumn('updated_at', fn($r) => $r->updated_at ? date('d-m-Y H:i', strtotime($r->updated_at)) : '-')
            ->rawColumns(['action', 'status_label', 'budget_used', 'margin_label'])
            ->make(true);
    }

    /** Daftar kolom DataTables untuk index (konsisten dgn pola getTableColoumn() modul lain). */
    private function getTableColumn(): string
    {
        return json_encode([
            ['data' => 'action',           'name' => 'action',           'title' => 'Action', 'orderable' => false, 'searchable' => false],
            ['data' => 'budgeting_number', 'name' => 'budgeting_number', 'title' => 'Budgeting Number'],
            ['data' => 'fiscal_year',      'name' => 'fiscal_year',      'title' => 'Fiscal Year'],
            ['data' => 'dept_name',        'name' => 'dept_name',        'title' => 'Department'],
            ['data' => 'status_label',     'name' => 'status_label',     'title' => 'Status', 'orderable' => false, 'searchable' => false],
            ['data' => 'previous_period',  'name' => 'previous_period',  'title' => 'Previous Period', 'orderable' => false, 'searchable' => false],
            ['data' => 'budget_period',    'name' => 'budget_period',    'title' => 'Budget Period', 'orderable' => false, 'searchable' => false],
            ['data' => 'budget_used',      'name' => 'budget_used',      'title' => 'Budget Used', 'orderable' => false, 'searchable' => false],
            ['data' => 'total_budget',     'name' => 'total_budget',     'title' => 'Total Budget', 'orderable' => false, 'searchable' => false],
            ['data' => 'actual',           'name' => 'actual',           'title' => 'Actual', 'orderable' => false, 'searchable' => false],
            ['data' => 'margin_label',     'name' => 'margin_label',     'title' => 'Margin', 'orderable' => false, 'searchable' => false],
            ['data' => 'created_by',       'name' => 'created_by',       'title' => 'Created By'],
            ['data' => 'created_at',       'name' => 'created_at',       'title' => 'Created At'],
            ['data' => 'updated_by',       'name' => 'updated_by',       'title' => 'Updated By'],
            ['data' => 'updated_at',       'name' => 'updated_at',       'title' => 'Updated At'],
        ], true);
    }

    /** Daftar budgeting + agregat final_budget/realisasi, status & % terpakai dihitung di PHP. */
    private function budgetList()
    {
        $list = DB::table('budgeting_hdr as h')
            ->leftJoin('depts as dp', 'dp.code', 'h.dept_code')
            ->leftJoin(DB::raw('(select budgeting_hdr_id, sum(final_budget) as sum_final, sum(realisasi_total) as sum_real from budgeting_det group by budgeting_hdr_id) as agg'), 'agg.budgeting_hdr_id', 'h.id')
            ->select('h.*', 'dp.name as dept_name', 'agg.sum_final', 'agg.sum_real')
            ->orderByDesc('h.id')
            ->get();

        foreach ($list as $r) {
            // final_budget per det = total untuk seluruh budget period (bukan nilai bulanan).
            $r->total_budget = round((float) $r->sum_final, 2);
            $r->actual = round((float) $r->sum_real, 2);
            $r->margin = round($r->total_budget - $r->actual, 2);
            $r->used_pct = $r->total_budget > 0 ? round($r->actual / $r->total_budget * 100, 1) : 0;
            $r->status = $r->margin < 0 ? 'Overbudget' : ($r->margin > 0 ? 'Underbudget' : 'Sesuai Budget');
            $r->previous_period = date('d M Y', strtotime($r->previous_from)) . ' - ' . date('d M Y', strtotime($r->previous_to));
            $r->budget_period = date('d M Y', strtotime($r->budget_from)) . ' - ' . date('d M Y', strtotime($r->budget_to));
        }

        return $list;
    }

    /* ====================================================================
     |  CREATE / STORE
     * ================================================================== */

    public function create(Request $request)
    {
        $now = date('Y-m-d');

        return view('accounting.budgeting.create', [
            'title'           => "Create {$this->title}",
            'depts'           => DB::table('depts')->orderBy('name')->get(),
            'accounts'        => $this->coaOptions(),
            'crDefault'       => self::DEFAULT_COST_REDUCTION,
            'fiscalYears'     => $this->fiscalYearOptions(),
            'fiscalYearDefault' => (int) date('Y'),
            'previousDefault' => sprintf('01-%04d to %02d-%04d', date('Y', strtotime($now)), date('n', strtotime($now)), date('Y', strtotime($now))),
            'budgetDefault'   => sprintf('%02d-%04d to %02d-%04d', date('n'), date('Y'), date('n'), date('Y') + 1),
        ]);
    }

    /** AJAX: preview previous + realisasi sebelum disimpan (dipakai juga oleh create.blade). */
    public function data(Request $request)
    {
        $prevP = $this->resolvePeriode($request->previous);
        $budgP = $this->resolvePeriode($request->budget);
        if (!$prevP || !$budgP) {
            return response()->json(['error' => 'Format periode harus MM-YYYY to MM-YYYY.'], 422);
        }
        list($prevFrom, $prevTo) = $prevP;
        list($budgFrom, $budgTo, $budgMonths) = $budgP;

        $dept = trim((string) $request->dept);
        if ($dept === '') {
            return response()->json(['error' => 'Department wajib dipilih.'], 422);
        }
        $coas = array_values(array_filter((array) $request->coa, 'strlen'));

        return response()->json([
            'dept_name'      => DB::table('depts')->where('code', $dept)->value('name') ?: $dept,
            'previous_text'  => $this->periodeText($prevFrom, $prevTo),
            'budget_text'    => $this->periodeText($budgFrom, $budgTo),
            'months'         => $budgMonths,
            'cost_reduction' => self::DEFAULT_COST_REDUCTION,
            'rows'           => $this->computeDet($dept, $coas, $prevFrom, $prevTo, $budgMonths, $budgFrom, $budgTo),
        ]);
    }

    public function store(Request $request)
    {
        $username = optional(Auth::user())->username;

        $prevP = $this->resolvePeriode($request->previous);
        $budgP = $this->resolvePeriode($request->budget);
        if (!$prevP || !$budgP) {
            return response()->json(['status' => 0, 'message' => 'Format periode harus MM-YYYY to MM-YYYY.'], 422);
        }
        list($prevFrom, $prevTo) = $prevP;
        list($budgFrom, $budgTo, $budgMonths) = $budgP;

        $dept = trim((string) $request->dept);
        if ($dept === '') {
            return response()->json(['status' => 0, 'message' => 'Department wajib dipilih.'], 422);
        }
        $fiscalYear = (int) $request->fiscal_year;
        if (!in_array($fiscalYear, $this->fiscalYearOptions())) {
            return response()->json(['status' => 0, 'message' => 'Fiscal Year tidak valid.'], 422);
        }
        if (trim((string) $request->description) === '') {
            return response()->json(['status' => 0, 'message' => 'Description wajib diisi.'], 422);
        }
        $coas = array_values(array_filter((array) $request->coa, 'strlen'));
        $rows = $this->computeDet($dept, $coas, $prevFrom, $prevTo, $budgMonths, $budgFrom, $budgTo);
        $edits = collect(json_decode($request->rows, true) ?: [])->keyBy('account');

        DB::beginTransaction();
        try {
            $number = $this->generateNumber($fiscalYear, $dept);

            $hdrId = DB::table('budgeting_hdr')->insertGetId([
                'budgeting_number' => $number,
                'fiscal_year'      => $fiscalYear,
                'dept_code'        => $dept,
                'description'      => $request->description,
                'note'             => $request->note,
                'coa_filter'       => $coas ? implode(',', $coas) : null,
                'previous_from'    => $prevFrom,
                'previous_to'      => $prevTo,
                'budget_from'      => $budgFrom,
                'budget_to'        => $budgTo,
                'created_by'       => $username,
                'updated_by'       => $username,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            foreach ($rows as $r) {
                $edit = $edits->get($r['account']);
                $cr = $edit ? min(100, max(0, (float) $edit['cost_reduction'])) : self::DEFAULT_COST_REDUCTION;
                $budget = round($r['average'] * (1 - $cr / 100), 2);
                // final_budget = TOTAL untuk seluruh budget period (bukan bulanan).
                // Default diskalakan ke active_months (bukan seluruh bulan budget period), supaya pengeluaran
                // yang historisnya jarang muncul (mis. sekali dalam setahun) tidak diproyeksikan jadi bulanan.
                $final = ($edit && isset($edit['final_budget'])) ? (float) $edit['final_budget'] : round($budget * $r['active_months'], 2);

                DB::table('budgeting_det')->insert([
                    'budgeting_hdr_id' => $hdrId,
                    'account'          => $r['account'],
                    'nama_akun'        => $r['nama_akun'],
                    'debit'            => $r['debit'],
                    'average'          => $r['average'],
                    'active_months'    => $r['active_months'],
                    'cost_reduction'   => $cr,
                    'budget'           => $budget,
                    'final_budget'     => $final,
                    'realisasi_json'   => json_encode($r['realisasi']),
                    'realisasi_total'  => $r['realisasi_total'],
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'status'   => 1,
                'message'  => "Budgeting {$number} berhasil disimpan.",
                'redirect' => route('budgeting.edit', $hdrId),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 0, 'message' => 'Gagal disimpan: ' . $e->getMessage()], 500);
        }
    }

    /* ====================================================================
     |  EDIT / UPDATE / SHOW / DESTROY
     * ================================================================== */

    public function edit($id)
    {
        $d = $this->loadDetail($id);
        if (!$d) {
            return redirect()->route('budgeting.index')->with(['alert' => 'warning', 'message' => 'Data tidak ditemukan.']);
        }

        return view('accounting.budgeting.edit', $d + ['title' => "Edit {$this->title}", 'fiscalYears' => $this->fiscalYearOptions()]);
    }

    public function show($id)
    {
        $d = $this->loadDetail($id);
        if (!$d) {
            return redirect()->route('budgeting.index')->with(['alert' => 'warning', 'message' => 'Data tidak ditemukan.']);
        }

        return view('accounting.budgeting.show', $d + ['title' => "Detail {$this->title}"]);
    }

    public function update(Request $request, $id)
    {
        $hdr = DB::table('budgeting_hdr')->where('id', $id)->first();
        if (!$hdr) {
            return response()->json(['status' => 0, 'message' => 'Data tidak ditemukan.'], 404);
        }

        $fiscalYear = (int) $request->fiscal_year;
        if (!in_array($fiscalYear, $this->fiscalYearOptions())) {
            return response()->json(['status' => 0, 'message' => 'Fiscal Year tidak valid.'], 422);
        }

        $edits = collect(json_decode($request->rows, true) ?: []);
        $monthsCount = count($this->monthsBetween($hdr->budget_from, $hdr->budget_to));

        DB::beginTransaction();
        try {
            foreach ($edits as $e) {
                $det = DB::table('budgeting_det')->where('budgeting_hdr_id', $id)->where('account', $e['account'])->first();
                if (!$det) {
                    continue;
                }
                $cr = min(100, max(0, (float) ($e['cost_reduction'] ?? 0)));
                $budget = round(((float) $det->average) * (1 - $cr / 100), 2);
                // final_budget = TOTAL untuk seluruh budget period (bukan bulanan).
                $final = isset($e['final_budget']) ? (float) $e['final_budget'] : round($budget * $monthsCount, 2);

                DB::table('budgeting_det')->where('id', $det->id)->update([
                    'cost_reduction' => $cr,
                    'budget'         => $budget,
                    'final_budget'   => $final,
                    'updated_at'     => now(),
                ]);
            }

            DB::table('budgeting_hdr')->where('id', $id)->update([
                'fiscal_year' => $fiscalYear,
                'description' => $request->description,
                'note'        => $request->note,
                'updated_by'  => optional(Auth::user())->username,
                'updated_at'  => now(),
            ]);

            DB::commit();

            return response()->json(['status' => 1, 'message' => 'Budgeting berhasil diperbarui.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 0, 'message' => 'Gagal menyimpan: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $deleted = DB::table('budgeting_hdr')->where('id', $id)->delete();
        if (!$deleted) {
            return response()->json(['status' => 0, 'message' => 'Data tidak ditemukan.'], 404);
        }

        return response()->json(['status' => 1, 'message' => 'Budgeting berhasil dihapus.']);
    }

    /** AJAX: tarik ulang debit/average (previous) & realisasi (budget period) dari kas_det, lalu simpan. */
    public function recalculate($id)
    {
        $hdr = DB::table('budgeting_hdr')->where('id', $id)->first();
        if (!$hdr) {
            return response()->json(['status' => 0, 'message' => 'Data tidak ditemukan.'], 404);
        }

        $coas = $hdr->coa_filter ? explode(',', $hdr->coa_filter) : [];
        $months = $this->monthsBetween($hdr->budget_from, $hdr->budget_to);
        $fresh = $this->computeDet($hdr->dept_code, $coas, $hdr->previous_from, $hdr->previous_to, $months, $hdr->budget_from, $hdr->budget_to);
        $existing = DB::table('budgeting_det')->where('budgeting_hdr_id', $id)->get()->keyBy('account');

        DB::beginTransaction();
        try {
            foreach ($fresh as $r) {
                $ex = $existing->get($r['account']);
                $cr = $ex ? (float) $ex->cost_reduction : self::DEFAULT_COST_REDUCTION;
                $budget = round($r['average'] * (1 - $cr / 100), 2);

                $payload = [
                    'nama_akun'       => $r['nama_akun'],
                    'debit'           => $r['debit'],
                    'average'         => $r['average'],
                    'active_months'   => $r['active_months'],
                    'budget'          => $budget,
                    'realisasi_json'  => json_encode($r['realisasi']),
                    'realisasi_total' => $r['realisasi_total'],
                    'updated_at'      => now(),
                ];

                if ($ex) {
                    DB::table('budgeting_det')->where('id', $ex->id)->update($payload);
                } else {
                    DB::table('budgeting_det')->insert($payload + [
                        'budgeting_hdr_id' => $id,
                        'account'          => $r['account'],
                        'cost_reduction'   => $cr,
                        'final_budget'     => round($budget * $r['active_months'], 2),
                        'created_at'       => now(),
                    ]);
                }
            }

            DB::table('budgeting_hdr')->where('id', $id)->update(['updated_at' => now()]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 0, 'message' => 'Gagal menarik ulang data: ' . $e->getMessage()], 500);
        }

        return response()->json(['status' => 1, 'message' => 'Data berhasil ditarik ulang.'] + $this->loadDetail($id));
    }

    /** AJAX: list transaksi untuk modal hyperlink Debit / Realisasi. */
    public function transactions(Request $request)
    {
        $dept = trim((string) $request->dept);
        $account = trim((string) $request->account);
        $from = $request->from;
        $to = $request->to;
        if ($dept === '' || $account === '' || !$from || !$to) {
            return response()->json(['error' => 'Parameter tidak lengkap.'], 422);
        }

        $rows = DB::table('kas_det as d')
            ->join('kas_hdr as h', 'h.voucher_number', 'd.voucher_number')
            ->whereNotIn('h.status', self::STATUS_EXCLUDED)
            ->whereRaw("to_date(h.voucher_date,'DD-MM-YYYY') between ? and ?", [$from, $to])
            ->whereRaw("coalesce(nullif(d.cost_center,''),?) = ?", [self::DEFAULT_DEPT, $dept])
            ->where('d.account', $account)
            ->select(
                DB::raw("to_char(to_date(h.voucher_date,'DD-MM-YYYY'),'DD-MM-YYYY') as voucher_date"),
                DB::raw("to_date(h.voucher_date,'DD-MM-YYYY') as voucher_date_2"),
                'd.voucher_number', 'd.description', 'd.debit', 'h.id as hdr_id', 'h.voucher_type'
            )
            ->orderBy('voucher_date_2')
            ->orderBy('d.id')
            ->get();

        $rows->each(function ($r) {
            $r->detail_url = $this->voucherShowUrl($r->voucher_type, $r->hdr_id, $r->voucher_number);
            unset($r->hdr_id, $r->voucher_type);
        });

        return response()->json([
            'rows'  => $rows,
            'total' => round($rows->sum('debit'), 2),
        ]);
    }

    /** URL detail voucher sesuai controller masing-masing, null kalau tipe tidak dikenal. */
    private function voucherShowUrl($voucherType, $hdrId, $voucherNumber)
    {
        // AP (accrual invoice) disimpan di ap_invoice, bukan kas_hdr -> cari id via ap_number.
        if ($voucherType === 'AP') {
            $apId = DB::table('ap_invoice')->where('ap_number', $voucherNumber)->value('id');

            return $apId ? route('accountPayable.show', ['id' => \Crypt::encryptString($apId)]) : null;
        }

        $routes = [
            'KK' => 'kasKeluar.show',
            'BK' => 'bankKeluar.show',
            'KM' => 'kasPenerimaan.show',
            'BM' => 'bankPenerimaan.show',
            'GJ' => 'jurnalUmum.show',
        ];
        $routeName = $routes[$voucherType] ?? null;

        return $routeName ? route($routeName, ['id' => \Crypt::encryptString($hdrId)]) : null;
    }

    /* ====================================================================
     |  EXPORT
     * ================================================================== */

    public function exportExcel($id)
    {
        $d = $this->loadDetail($id);
        if (!$d) {
            abort(404);
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Budgeting');

        $sheet->fromArray([$d['hdr']->budgeting_number . ' - ' . $d['hdr']->dept_name], null, 'A1');
        $sheet->fromArray(['Previous: ' . $this->periodeText($d['hdr']->previous_from, $d['hdr']->previous_to) . '  |  Budget Period: ' . $this->periodeText($d['hdr']->budget_from, $d['hdr']->budget_to)], null, 'A2');

        $headers = array_merge(
            ['Account', 'Name', 'Debit', 'Average', 'CR %', 'Monthly Budget', 'Final Budget (Total)'],
            $d['months'],
            ['Total Realisasi', 'Selisih', 'Realisasi %']
        );
        $sheet->fromArray($headers, null, 'A4');

        $rowNum = 5;
        foreach ($d['rows'] as $r) {
            $line = array_merge(
                [$r['account'], $r['nama_akun'], $r['debit'], $r['average'], $r['cost_reduction'], $r['final_budget_monthly'], $r['final_budget']],
                array_map(function ($m) use ($r) {
                    return $r['realisasi'][$m] ?? 0;
                }, $d['months']),
                [$r['realisasi_total'], $r['selisih'], $r['realisasi_pct']]
            );
            $sheet->fromArray($line, null, 'A' . $rowNum);
            $rowNum++;
        }

        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = $d['hdr']->budgeting_number . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function exportPdf($id)
    {
        $d = $this->loadDetail($id);
        if (!$d) {
            abort(404);
        }

        $pdf = PDF::loadView('accounting.budgeting.print', $d)->setPaper('a4', 'landscape');

        return $pdf->download($d['hdr']->budgeting_number . '.pdf');
    }

    /* ====================================================================
     |  Helper
     * ================================================================== */

    private function coaOptions()
    {
        $coa = DB::table('accounts')->select('account', 'description', 'acc_header');
        $this->applyCoaRange($coa, 'account');

        return $coa->orderBy(DB::raw("string_to_array(account,'.')::int[]"))->get();
    }

    /** Load hdr + det + months + summary cards untuk halaman edit/show/export. */
    private function loadDetail($id)
    {
        $hdr = DB::table('budgeting_hdr as h')
            ->leftJoin('depts as dp', 'dp.code', 'h.dept_code')
            ->where('h.id', $id)
            ->select('h.*', 'dp.name as dept_name')
            ->first();
        if (!$hdr) {
            return null;
        }

        if ($hdr->coa_filter) {
            $codes = array_map('trim', explode(',', $hdr->coa_filter));
            $names = DB::table('accounts')->whereIn('account', $codes)->pluck('description', 'account');
            $hdr->coa_label = collect($codes)
                ->map(fn($c) => $names->has($c) ? "$c - {$names[$c]}" : $c)
                ->implode(', ');
        } else {
            $hdr->coa_label = 'Semua COA (' . implode(', ', array_map(fn($r) => "{$r[0]}-{$r[1]}", self::COA_RANGES)) . ')';
        }

        $months = $this->monthsBetween($hdr->budget_from, $hdr->budget_to);

        $det = DB::table('budgeting_det')
            ->where('budgeting_hdr_id', $id)
            ->orderBy(DB::raw("string_to_array(account,'.')::int[]"))
            ->get();

        $rows = [];
        foreach ($det as $r) {
            $realisasi = json_decode($r->realisasi_json, true) ?: [];
            $realTotal = (float) $r->realisasi_total;
            // final_budget = TOTAL untuk seluruh budget period (bukan nilai bulanan).
            $budgetTotal = (float) $r->final_budget;

            $rows[] = [
                'id'             => $r->id,
                'account'        => $r->account,
                'nama_akun'      => $r->nama_akun,
                'debit'          => (float) $r->debit,
                'average'        => (float) $r->average,
                'active_months'  => (int) $r->active_months,
                'cost_reduction' => (float) $r->cost_reduction,
                'budget'         => (float) $r->budget,
                'final_budget'   => $budgetTotal,
                'final_budget_monthly' => count($months) > 0 ? round($budgetTotal / count($months), 2) : 0,
                'realisasi'      => $realisasi,
                'realisasi_total' => $realTotal,
                'selisih'        => round($budgetTotal - $realTotal, 2),
                'realisasi_pct'  => $budgetTotal > 0 ? round($realTotal / $budgetTotal * 100, 2) : ($realTotal > 0 ? -100 : 0),
            ];
        }

        $previous = round(array_sum(array_column($rows, 'debit')), 2);
        $totalBudget = round(array_sum(array_column($rows, 'final_budget')), 2);
        $actual = round(array_sum(array_column($rows, 'realisasi_total')), 2);
        $margin = round($totalBudget - $actual, 2);

        $cards = [
            'previous_expenses' => $previous,
            'previous_pct'      => $totalBudget > 0 ? round($previous / $totalBudget * 100, 2) : 0,
            'total_budget'      => $totalBudget,
            'budget_growth_pct' => $previous > 0 ? round(($totalBudget - $previous) / $previous * 100, 2) : 0,
            'actual_expenses'   => $actual,
            'actual_pct'        => $totalBudget > 0 ? round($actual / $totalBudget * 100, 2) : ($actual > 0 ? -100 : 0),
            'margin'            => $margin,
            'margin_pct'        => $totalBudget > 0 ? round($margin / $totalBudget * 100, 2) : ($margin < 0 ? -100 : 0),
        ];

        return ['hdr' => $hdr, 'months' => $months, 'rows' => $rows, 'cards' => $cards];
    }

    /** Pull debit kas_det per account + per bulan untuk 1 department dalam rentang tanggal. */
    private function pullMonthly($dept, array $coas, $from, $to)
    {
        $q = DB::table('kas_det as d')
            ->join('kas_hdr as h', 'h.voucher_number', 'd.voucher_number')
            ->leftJoin('accounts as a', 'a.account', 'd.account')
            ->whereNotIn('h.status', self::STATUS_EXCLUDED)
            ->whereRaw("to_date(h.voucher_date,'DD-MM-YYYY') between ? and ?", [$from, $to])
            ->whereRaw("coalesce(upper(a.acc_header),'') <> 'HEADER'")
            ->whereRaw("coalesce(nullif(d.cost_center,''),?) = ?", [self::DEFAULT_DEPT, $dept]);
        $this->applyCoaRange($q, 'd.account');

        if ($coas) {
            $q->where(function ($w) use ($coas) {
                foreach ($coas as $c) {
                    $like = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $c) . '.%';
                    $w->orWhere('d.account', $c)->orWhere('d.account', 'like', $like);
                }
            });
        }

        $rows = $q->select(
                'd.account',
                'a.description as nama_akun',
                DB::raw("to_char(to_date(h.voucher_date,'DD-MM-YYYY'),'YYYY-MM') as ym"),
                DB::raw('sum(d.debit) as debit')
            )
            ->groupBy(DB::raw('1, 2, 3'))
            ->havingRaw('sum(d.debit) <> 0')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $acc = (string) $r->account;
            if (!isset($out[$acc])) {
                $out[$acc] = ['nama' => $r->nama_akun, 'months' => []];
            }
            $out[$acc]['months'][$r->ym] = ($out[$acc]['months'][$r->ym] ?? 0) + (float) $r->debit;
        }

        return $out;
    }

    /** Gabungkan previous period (debit/average) + budget period (realisasi per bulan) per COA. */
    private function computeDet($dept, array $coas, $prevFrom, $prevTo, array $budgetMonths, $budgFrom, $budgTo)
    {
        $prev = $this->pullMonthly($dept, $coas, $prevFrom, $prevTo);
        $real = $this->pullMonthly($dept, $coas, $budgFrom, $budgTo);

        $rows = [];
        foreach (array_unique(array_merge(array_keys($prev), array_keys($real))) as $acc) {
            $p = $prev[$acc] ?? ['nama' => null, 'months' => []];
            $r = $real[$acc] ?? ['nama' => null, 'months' => []];

            $total = array_sum($p['months']);
            $active = count(array_filter($p['months'], function ($v) {
                return abs($v) > 0.004;
            }));

            $realMonths = [];
            foreach ($budgetMonths as $ym) {
                $realMonths[$ym] = round((float) ($r['months'][$ym] ?? 0), 2);
            }

            $rows[$acc] = [
                'account'         => $acc,
                'nama_akun'       => $p['nama'] ?: ($r['nama'] ?: $acc),
                'debit'           => round($total, 2),
                'average'         => $active > 0 ? round($total / $active, 2) : 0,
                'active_months'   => $active,
                'realisasi'       => $realMonths,
                'realisasi_total' => round(array_sum($realMonths), 2),
            ];
        }
        ksort($rows, SORT_NATURAL);

        return array_values($rows);
    }

    /** Nomor budgeting: BGT-ASN-{fiscalYear}-{deptCode}, tambah suffix -2/-3/... kalau dept + fiscal year sudah dipakai. */
    private function generateNumber($fiscalYear, $deptCode)
    {
        $base = "BGT-ASN-{$fiscalYear}-{$deptCode}";
        if (!DB::table('budgeting_hdr')->where('budgeting_number', $base)->exists()) {
            return $base;
        }
        for ($i = 2; $i < 100; $i++) {
            $number = "{$base}-{$i}";
            if (!DB::table('budgeting_hdr')->where('budgeting_number', $number)->exists()) {
                return $number;
            }
        }
        throw new \Exception('Gagal generate nomor budgeting.');
    }

    /** Daftar fiscal year pilihan: 2024 s/d tahun depan, bertambah otomatis tiap tahun. */
    private function fiscalYearOptions()
    {
        return range(2024, (int) date('Y') + 1);
    }

    /** Daftar 'YYYY-MM' dari tanggal $from s/d $to (inklusif, per bulan). */
    private function monthsBetween($from, $to)
    {
        $months = [];
        $d = strtotime(date('Y-m-01', strtotime($from)));
        $end = strtotime(date('Y-m-01', strtotime($to)));
        while ($d <= $end) {
            $months[] = date('Y-m', $d);
            $d = strtotime('+1 month', $d);
        }

        return $months;
    }

    /** Batasi kolom kode akun ke segmen pertama dalam COA_RANGES. */
    private function applyCoaRange($query, $col)
    {
        $query->where(function ($w) use ($col) {
            foreach (self::COA_RANGES as $r) {
                $w->orWhereRaw(
                    "(case when split_part($col,'.',1) ~ '^[0-9]+\$' then split_part($col,'.',1)::int end) between ? and ?",
                    [$r[0], $r[1]]
                );
            }
        });
    }

    /**
     * "MM-YYYY to MM-YYYY" (atau satu bulan saja) -> [tanggal awal, tanggal akhir, daftar 'YYYY-MM'].
     * Null kalau formatnya salah.
     */
    private function resolvePeriode($raw)
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }

        $parts = array_map('trim', explode('to', $raw));
        $a = $this->parseMonth($parts[0]);
        $b = (isset($parts[1]) && $parts[1] !== '') ? $this->parseMonth($parts[1]) : $a;
        if (!$a || !$b) {
            return null;
        }

        if ($a[1] * 12 + $a[0] > $b[1] * 12 + $b[0]) {
            list($a, $b) = [$b, $a];
        }

        $from = sprintf('%04d-%02d-01', $a[1], $a[0]);
        $to = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $b[1], $b[0])));

        $months = [];
        $y = $a[1];
        $m = $a[0];
        while ($y * 12 + $m <= $b[1] * 12 + $b[0]) {
            $months[] = sprintf('%04d-%02d', $y, $m);
            if (++$m > 12) {
                $m = 1;
                $y++;
            }
        }

        return [$from, $to, $months];
    }

    /** "MM-YYYY" -> [bulan, tahun], null kalau bukan bulan yang valid. */
    private function parseMonth($raw)
    {
        if (!preg_match('/^(\d{1,2})-(\d{4})$/', trim($raw), $m)) {
            return null;
        }

        return ((int) $m[1] >= 1 && (int) $m[1] <= 12) ? [(int) $m[1], (int) $m[2]] : null;
    }

    private function periodeText($from, $to)
    {
        $f = strtotime($from);
        $t = strtotime($to);
        $fm = self::BULAN[(int) date('n', $f)] . ' ' . date('Y', $f);
        $tm = self::BULAN[(int) date('n', $t)] . ' ' . date('Y', $t);

        return $fm === $tm ? $fm : "$fm s/d $tm";
    }
}
