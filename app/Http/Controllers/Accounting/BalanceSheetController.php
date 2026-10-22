<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use DataTables;
use DB;
use AppHelpers;
use App\Exports\BalanceSheetGridExport;
use Maatwebsite\Excel\Facades\Excel;

// Neraca, direfactor supaya sama persis strukturnya dengan TrialBalanceController:
// grid tahunan (saldo awal/pergerakan/saldo akhir per bulan) dihitung dari kas_det/kas_hdr,
// bisa disimpan (neraca_hdr/neraca_det). Satu-satunya beda: COA yang muncul cuma yang
// accounts.final_statement = 'NERACA' (dulu dari master_neraca/master_neraca_detail).
class BalanceSheetController extends Controller
{
    private $title;
    private $moduleCode;

    /** Sama seperti TrialBalanceController::GL_EPOCH. */
    const GL_EPOCH = '2024-01-01';

    const FINAL_STATEMENT = 'NERACA';

    public function __construct()
    {
        $this->title = "Neraca";
        $this->moduleCode = "NRC";
    }

    public function getSavedTableColoumn(){
        $kolom=
        [
            ['data'=> 'action', 'name'=>'action','title'=>'action', 'orderable'=> false, 'searchable'=> false ],
            ['data'=> 'nrc_number', 'name'=>'nrc_number','title'=>'Neraca Number' ],
            ['data'=> 'year', 'name'=>'year','title'=>'Year' ],
            ['data'=> 'month_name', 'name'=>'month_name','title'=>'Month' ],
            ['data'=> 'description', 'name'=>'description','title'=>'Description' ],
            ['data'=> 'note', 'name'=>'note','title'=>'Note' ],
            ['data'=> 'created_by', 'name'=>'created_by','title'=>'Created By' ],
            ['data'=> 'created_at', 'name'=>'created_at','title'=>'Created At' ],
            ['data'=> 'updated_by', 'name'=>'updated_by','title'=>'Updated By' ],
            ['data'=> 'updated_at', 'name'=>'updated_at','title'=>'Updated At' ],
        ];
        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $data['title'] = "$this->title";
        $data['kolom'] = $this->getSavedTableColoumn();
        return view('accounting.balanceSheet.index', $data);
    }

    public function list(Request $request)
    {
        $nrcNumber = $request->nrcNumber;
        $year = $request->year;
        $month = $request->month;

        $data = DB::table('neraca_hdr')
            ->when($nrcNumber, fn($q) => $q->where('nrc_number', 'ilike', "%$nrcNumber%"))
            ->when($year, fn($q) => $q->where('year', $year))
            ->when($month, fn($q) => $q->where('month', $month))
            ->orderByDesc('id')
            ->get();

        return Datatables::of($data)
            ->addColumn('action', function ($row) {
                $buttons = '<div class="d-inline-flex">
                                <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                    <i data-feather="menu"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">';

                $buttons .= '<a href="' . route('balanceSheet.saved.edit', ['id' => Crypt::encryptString($row->id)]) . '" class="dropdown-item">
                                <i data-feather="file-text"></i>
                                <span>' . __("Edit") . '</span>
                            </a>';

                $buttons .= "<a href='javascript:;'
                                id='deleteButton'
                                class='dropdown-item'
                                data-toggle='modal'
                                data-target='#smallModal'
                                data-href='" . route("balanceSheet.saved.destroy", ["id" => Crypt::encryptString($row->id)]) . "'>
                                <i data-feather='trash-2' class='feather-14-red'></i>
                                Delete
                            </a>";

                $buttons .= '</div></div>';

                return $buttons;
            })
            ->addColumn('month_name', function ($row) {
                return $row->month ? \DateTime::createFromFormat('n', $row->month)->format('F') : '-';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    private $months = [1,2,3,4,5,6,7,8,9,10,11,12];

    private function emptyMonthly()
    {
        $monthly = [];
        foreach ($this->months as $m) {
            $monthly[$m] = [
                'open_debit' => 0, 'open_credit' => 0,
                'mutasi_debit' => 0, 'mutasi_credit' => 0,
                'saldo_debit' => 0, 'saldo_credit' => 0,
            ];
        }
        return $monthly;
    }

    public function getLastCode($key)
    {
        DB::table('master_code')->where('code_key', $key)->update([
            'code_number' => DB::raw('code_number + 1'),
            'updated_by' => Auth::user()->username,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $newCode = DB::table('master_code')->where('code_key', $key)->value('code_number');
        $padded = str_pad($newCode, 4, '0', STR_PAD_LEFT);
        return "NRC-ASN-" . date('Y') . "-$padded";
    }

    // Peek only, belum commit -- dipakai buat nampilin preview nomor di field disabled sebelum Save ditekan.
    private function previewLastCode($key)
    {
        $current = (int) DB::table('master_code')->where('code_key', $key)->value('code_number');
        $padded = str_pad($current + 1, 4, '0', STR_PAD_LEFT);
        return "NRC-ASN-" . date('Y') . "-$padded";
    }

    // Sama seperti TrialBalanceController::groupedAccounts(), tapi cuma ambil account
    // yang accounts.final_statement = 'NERACA'.
    private function groupedAccounts()
    {
        $accounts = collect(DB::select("
            SELECT a.account, a.description, a.debit_credit, a.final_statement,
                   COALESCE(a.type_code, 'UNGROUPED') as group_data,
                   COALESCE(t.name, 'Ungrouped') as group_name,
                   COALESCE(NULLIF(a.type_code,'')::numeric, 999999) as urutan
            FROM accounts a
            LEFT JOIN acc_types t ON t.code = a.type_code
            WHERE a.final_statement = ?
            ORDER BY urutan, a.account
        ", [self::FINAL_STATEMENT]));

        $groups = $accounts->unique('group_data')->sortBy('urutan')->values();

        return [$accounts, $groups];
    }

    // Sama seperti TrialBalanceController::monthlyDataForYear(), tapi dibatasi ke
    // account yang final_statement = 'NERACA' saja.
    private function monthlyDataForYear(int $year): array
    {
        $neracaAccounts = DB::table('accounts')->where('final_statement', self::FINAL_STATEMENT)->pluck('account');

        $monthly = DB::select("
            SELECT d.account,
                   extract(month from to_date(h.voucher_date,'dd-mm-yyyy'))::int as month,
                   sum(d.debit) as debit, sum(d.credit) as credit
            FROM kas_det d
            JOIN kas_hdr h ON d.voucher_number = h.voucher_number
            WHERE h.status <> '5'
              AND extract(year from to_date(h.voucher_date,'dd-mm-yyyy')) = ?
              AND d.account in (select account from accounts where final_statement = ?)
            GROUP BY d.account, month
        ", [$year, self::FINAL_STATEMENT]);

        $runningNet = [];
        foreach (DB::table('accounts')->select('account', 'debit_credit', 'opening_balance')->where('final_statement', self::FINAL_STATEMENT)->get() as $a) {
            $sgn = strtoupper($a->debit_credit) === 'KREDIT' ? -1 : 1;
            $runningNet[$a->account] = $sgn * (float) $a->opening_balance;
        }

        $yearStart = sprintf('%04d-01-01', $year);
        if ($yearStart > self::GL_EPOCH) {
            $before = DB::select("
                SELECT d.account, sum(d.debit) as debit, sum(d.credit) as credit
                FROM kas_det d
                JOIN kas_hdr h ON d.voucher_number = h.voucher_number
                WHERE h.status <> '5'
                  AND to_date(h.voucher_date,'dd-mm-yyyy') >= ?
                  AND to_date(h.voucher_date,'dd-mm-yyyy') < ?
                  AND d.account in (select account from accounts where final_statement = ?)
                GROUP BY d.account
            ", [self::GL_EPOCH, $yearStart, self::FINAL_STATEMENT]);

            foreach ($before as $b) {
                $runningNet[$b->account] = ($runningNet[$b->account] ?? 0) + (float) $b->debit - (float) $b->credit;
            }
        }

        $byAccount = [];
        foreach ($monthly as $m) {
            $byAccount[$m->account][(int) $m->month] = ['debit' => (float) $m->debit, 'credit' => (float) $m->credit];
        }

        $result = [];
        foreach ($neracaAccounts as $account) {
            $net = $runningNet[$account] ?? 0;
            $monthsData = [];
            for ($m = 1; $m <= 12; $m++) {
                $openNet = $net;
                $debit = $byAccount[$account][$m]['debit'] ?? 0;
                $credit = $byAccount[$account][$m]['credit'] ?? 0;
                $net = $net + $debit - $credit;

                $monthsData[$m] = [
                    'open_debit' => $openNet >= 0 ? $openNet : 0,
                    'open_credit' => $openNet < 0 ? -$openNet : 0,
                    'mutasi_debit' => $debit,
                    'mutasi_credit' => $credit,
                    'saldo_debit' => $net >= 0 ? $net : 0,
                    'saldo_credit' => $net < 0 ? -$net : 0,
                ];
            }
            $result[$account] = $monthsData;
        }

        return $result;
    }

    public function savedGenerateData(Request $request)
    {
        $year = (int) $request->year;
        $month = (int) $request->month; // optional: 0 = semua bulan (dipakai ringkasan tahunan/export)
        if (!$year) {
            return response()->json(['status' => 0, 'message' => 'Year wajib diisi'], 422);
        }

        $data = $this->monthlyDataForYear($year);
        if ($month) {
            foreach ($data as $account => $months) {
                $data[$account] = [$month => $months[$month] ?? null];
            }
        }

        return response()->json(['status' => 1, 'data' => $data]);
    }

    // Gabungkan neraca_hdr+det per bulan (tiap bulan hdr terpisah) jadi satu grid 12 bulan,
    // dipakai toggle "Ringkasan Tahunan" di halaman list. Kalau ada lebih dari satu hdr untuk
    // bulan yang sama, dipakai yang paling baru (id terbesar).
    private function yearSummaryData(int $year): array
    {
        $hdrIdByMonth = DB::table('neraca_hdr')
            ->where('year', $year)
            ->whereNotNull('month')
            ->select('month', DB::raw('max(id) as id'))
            ->groupBy('month')
            ->pluck('id', 'month');

        $result = [];
        if ($hdrIdByMonth->isEmpty()) {
            return $result;
        }

        $details = DB::table('neraca_det')->whereIn('nrc_hdr_id', $hdrIdByMonth->values())->get();
        $monthByHdrId = $hdrIdByMonth->flip();

        foreach ($details as $d) {
            $month = (int) $monthByHdrId[$d->nrc_hdr_id];
            $monthly = json_decode($d->monthly, true) ?? [];
            $result[$d->account][$month] = $monthly[$month] ?? reset($monthly) ?: null;
        }

        return $result;
    }

    public function savedYearSummary(Request $request)
    {
        $year = (int) $request->year;
        $data['title'] = "Neraca - Ringkasan Tahunan";
        $data['year'] = $year;
        [$data['accounts'], $data['groups']] = $this->groupedAccounts();
        $data['months'] = $this->months;
        return view('accounting.balanceSheet.saved.yearSummary', $data);
    }

    public function savedYearSummaryData(Request $request)
    {
        $year = (int) $request->year;
        if (!$year) {
            return response()->json(['status' => 0, 'message' => 'Year wajib diisi'], 422);
        }

        return response()->json(['status' => 1, 'data' => $this->yearSummaryData($year)]);
    }

    // Export ringkasan tahunan dari data yang sudah disimpan per bulan (bukan hitung ulang
    // dari jurnal seperti savedExportExcel) -- bulan yang belum pernah disave keluar 0.
    public function savedYearSummaryExportExcel(Request $request)
    {
        $year = (int) $request->year;
        if (!$year) {
            return redirect()->back()->with(['status' => 1, 'alert' => 'warning', 'message' => 'Year wajib diisi']);
        }

        [$accounts, $groups] = $this->groupedAccounts();
        $monthlyData = $this->yearSummaryData($year);
        foreach ($accounts as $acc) {
            $monthlyData[$acc->account] = ($monthlyData[$acc->account] ?? []) + $this->emptyMonthly();
        }

        return Excel::download(
            new BalanceSheetGridExport($year, $accounts, $groups, $monthlyData, $this->months),
            "neraca_ringkasan_$year.xlsx"
        );
    }

    public function savedExportExcel(Request $request)
    {
        $year = (int) $request->year;
        if (!$year) {
            return redirect()->back()->with(['status' => 1, 'alert' => 'warning', 'message' => 'Year wajib diisi']);
        }

        [$accounts, $groups] = $this->groupedAccounts();
        $monthlyData = $this->monthlyDataForYear($year);

        return Excel::download(
            new BalanceSheetGridExport($year, $accounts, $groups, $monthlyData, $this->months),
            "neraca_$year.xlsx"
        );
    }

    // Sama seperti TrialBalanceController::savedVoucherList().
    public function savedVoucherList(Request $request)
    {
        $account = $request->account;
        $year = (int) $request->year;
        $month = (int) $request->month;
        $bucket = $request->bucket; // open | mutasi | saldo
        $side = $request->side === 'credit' ? 'credit' : 'debit';

        $monthStart = sprintf('%04d-%02d-01', $year, $month);
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        if ($bucket === 'open') {
            $dateCond = "to_date(h.voucher_date,'dd-mm-yyyy') < '$monthStart'";
        } elseif ($bucket === 'mutasi') {
            $dateCond = "to_date(h.voucher_date,'dd-mm-yyyy') between '$monthStart' and '$monthEnd'";
        } else {
            $dateCond = "to_date(h.voucher_date,'dd-mm-yyyy') <= '$monthEnd'";
        }

        $sideFilter = $bucket === 'mutasi' ? "AND d.$side > 0" : '';

        $rows = DB::select("
            SELECT h.id as hdr_id, h.voucher_number, h.voucher_date, h.voucher_type,
                   sum(d.debit) as debit, sum(d.credit) as credit, max(dp.name) as cost_center_name
            FROM kas_det d
            JOIN kas_hdr h ON d.voucher_number = h.voucher_number
            LEFT JOIN depts dp ON dp.code = d.cost_center
            WHERE h.status <> '5' AND d.account = ? AND $dateCond $sideFilter
            GROUP BY h.id, h.voucher_number, h.voucher_date, h.voucher_type
            ORDER BY to_date(h.voucher_date,'dd-mm-yyyy'), h.voucher_number
        ", [$account]);

        $directRoute = ['KK' => 'kasKeluar.edit', 'KM' => 'kasPenerimaan.edit', 'BK' => 'bankKeluar.edit', 'BM' => 'bankPenerimaan.edit', 'GJ' => 'jurnalUmum.edit'];
        $lookupDef = [
            'AP'     => ['accountPayable.edit', 'ap_invoice', 'ap_number'],
            'INV-DN' => ['debitNote.edit', 'debit_note_hdr', 'dn_number'],
            'INV'    => ['invoice.edit', 'invoice_hdr', 'invoice_number'],
        ];

        $idMaps = [];
        foreach ($lookupDef as $type => [$route, $table, $col]) {
            $numbers = collect($rows)->where('voucher_type', $type)->pluck('voucher_number')->unique()->all();
            if ($numbers) {
                $idMaps[$type] = DB::table($table)->whereIn($col, $numbers)->pluck('id', $col)->all();
            }
        }

        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($rows as $row) {
            $type = $row->voucher_type;
            if (isset($directRoute[$type])) {
                $row->edit_url = route($directRoute[$type], ['id' => Crypt::encryptString($row->hdr_id)]);
            } elseif ($type === 'SCO') {
                $row->edit_url = route('stockConsumption.edit', ['id' => Crypt::encryptString($row->voucher_number)]);
            } elseif (isset($lookupDef[$type]) && isset($idMaps[$type][$row->voucher_number])) {
                $row->edit_url = route($lookupDef[$type][0], ['id' => Crypt::encryptString($idMaps[$type][$row->voucher_number])]);
            } else {
                $row->edit_url = null;
            }
            $totalDebit += (float) $row->debit;
            $totalCredit += (float) $row->credit;
        }

        return response()->json([
            'status' => 1,
            'bucket' => $bucket,
            'rows' => $rows,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'net' => $totalDebit - $totalCredit,
        ]);
    }

    public function savedCreate(Request $request)
    {
        $data['title'] = "Create Neraca";
        $data['nextCode'] = $this->previewLastCode($this->moduleCode);
        [$data['accounts'], $data['groups']] = $this->groupedAccounts();
        return view('accounting.balanceSheet.saved.create', $data);
    }

    public function savedStore(Request $request)
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $year = $payload['year'] ?? null;
        $month = (int) ($payload['month'] ?? 0);
        $rows = $payload['rows'] ?? [];

        if (!$year || !$month || empty($rows)) {
            return response()->json(['status' => 0, 'alert' => 'warning', 'message' => 'Year, Month, dan data COA wajib diisi'], 422);
        }
        if (!trim($payload['description'] ?? '')) {
            return response()->json(['status' => 0, 'alert' => 'warning', 'message' => 'Description wajib diisi'], 422);
        }

        $username = Auth::user()->username;

        AppHelpers::resetCode($this->moduleCode);
        $nrcNumber = $this->getLastCode($this->moduleCode);

        $accounts = DB::table('accounts')->orderBy('account')->get()->keyBy('account');
        $emptyMonth = $this->emptyMonthly()[$month];

        DB::beginTransaction();
        try {
            $hdrId = DB::table('neraca_hdr')->insertGetId([
                'nrc_number' => $nrcNumber,
                'year' => $year,
                'month' => $month,
                'description' => $payload['description'] ?? null,
                'note' => $payload['note'] ?? null,
                'created_by' => $username,
                'updated_by' => $username,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $detRows = [];
            foreach ($rows as $row) {
                $accountCode = $row['account'] ?? null;
                if (!$accountCode) {
                    continue;
                }
                $acc = $accounts->get($accountCode);
                $detRows[] = [
                    'nrc_hdr_id' => $hdrId,
                    'account' => $accountCode,
                    'description' => $acc->description ?? null,
                    'debit_credit' => $acc->debit_credit ?? null,
                    'final_statement' => $acc->final_statement ?? null,
                    'monthly' => json_encode([$month => $row['monthly'][$month] ?? $emptyMonth]),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }

            foreach (array_chunk($detRows, 500) as $chunk) {
                DB::table('neraca_det')->insert($chunk);
            }

            DB::commit();
            \LogActivity::addToLog("Save Neraca", "username: $username Status $nrcNumber successfully saved");
            return response()->json(['status' => 1, 'alert' => 'success', 'message' => "$nrcNumber successfully saved", 'redirect' => route('balanceSheet.index')]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => 0, 'alert' => 'warning', 'message' => "Failed to save: " . $e->getMessage()]);
        }
    }

    public function savedEdit(Request $request)
    {
        $id = Crypt::decryptString($request->id);
        $data['title'] = "Edit Neraca";
        $data['hdr'] = DB::table('neraca_hdr')->where('id', $id)->first();
        $data['id'] = $request->id;

        [$allAccounts, $groups] = $this->groupedAccounts();
        $groupByAccount = $allAccounts->keyBy('account');

        $details = DB::table('neraca_det')->where('nrc_hdr_id', $id)->get()
            ->map(function ($d) use ($groupByAccount) {
                $g = $groupByAccount->get($d->account);
                $d->group_data = $g->group_data ?? 'UNGROUPED';
                $d->urutan = $g->urutan ?? 999999;
                return $d;
            })
            ->sortBy('account')
            ->sortBy('urutan')
            ->values();

        $data['details'] = $details;
        $data['groups'] = $groups;
        return view('accounting.balanceSheet.saved.edit', $data);
    }

    public function savedUpdate(Request $request)
    {
        $id = Crypt::decryptString($request->id);
        $username = Auth::user()->username;
        $payload = json_decode($request->getContent(), true) ?? [];
        $rows = $payload['rows'] ?? [];
        $month = (int) DB::table('neraca_hdr')->where('id', $id)->value('month');
        if (!$month) {
            return response()->json(['status' => 0, 'alert' => 'warning', 'message' => 'Neraca lama (format tahunan) tidak bisa diedit, buat ulang per bulan lewat menu Create'], 422);
        }
        if (!trim($payload['description'] ?? '')) {
            return response()->json(['status' => 0, 'alert' => 'warning', 'message' => 'Description wajib diisi'], 422);
        }
        $emptyMonth = $this->emptyMonthly()[$month];

        DB::beginTransaction();
        try {
            DB::table('neraca_hdr')->where('id', $id)->update([
                'description' => $request->input('description') ?? DB::raw('description'),
                'note' => $request->input('note') ?? DB::raw('note'),
                'updated_by' => $username,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            foreach ($rows as $row) {
                if (!isset($row['id'])) {
                    continue;
                }
                DB::table('neraca_det')
                    ->where('id', $row['id'])
                    ->where('nrc_hdr_id', $id)
                    ->update([
                        'monthly' => json_encode([$month => $row['monthly'][$month] ?? $emptyMonth]),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            }

            DB::commit();
            return response()->json(['status' => 1, 'alert' => 'success', 'message' => 'Neraca updated']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => 0, 'alert' => 'warning', 'message' => $e->getMessage()]);
        }
    }

    public function savedDestroy(Request $request)
    {
        $username = Auth::user()->username;
        $id = Crypt::decryptString($request->id);
        $hdr = DB::table('neraca_hdr')->where('id', $id)->first();

        if (!$hdr) {
            return redirect()->back()->with(['status' => 0, 'alert' => 'warning', 'message' => 'Data tidak ditemukan']);
        }

        DB::table('neraca_hdr')->where('id', $id)->delete(); // neraca_det ikut terhapus (ON DELETE CASCADE)

        \LogActivity::addToLog("Delete Neraca", "username: $username Status $hdr->nrc_number successfully deleted");
        return redirect()->back()->with(['status' => 1, 'alert' => 'success', 'message' => "$hdr->nrc_number successfully deleted"]);
    }
}
