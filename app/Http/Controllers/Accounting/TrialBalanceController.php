<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Session;
use Response;
use App\Permission;
use DataTables;
use DB;
use PDF;
use AppHelpers;
use Approval;

class TrialBalanceController extends Controller
{
    private $title;
    private $moduleCode;

    public function __construct()
    {
        $this->title = "Trial Balance";
        $this->moduleCode = "TB";

        $this->nilaiPpn = DB::table('attributes')
        ->where('attr_id','mainppn')
        ->value('attr_value');

        $this->nilaiPph23 = DB::table('attributes')
        ->where('attr_id','mainpph23')
        ->value('attr_value');

        $this->nilaiPph21 = DB::table('attributes')
        ->where('attr_id','mainpph21')
        ->value('attr_value');

        $this->nilaiPph42 = DB::table('attributes')
        ->where('attr_id','mainpph42')
        ->value('attr_value');

    }

    public function getSavedTableColoumn(){
        $kolom=
        [
            ['data'=> 'action', 'name'=>'action','title'=>'action', 'orderable'=> false, 'searchable'=> false ],
            ['data'=> 'tb_number', 'name'=>'tb_number','title'=>'Nomor' ],
            ['data'=> 'year', 'name'=>'year','title'=>'Year' ],
            ['data'=> 'description', 'name'=>'description','title'=>'Description' ],
            ['data'=> 'note', 'name'=>'note','title'=>'Note' ],
            ['data'=> 'created_at', 'name'=>'created_at','title'=>'Created At' ],
        ];
        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $data['title'] = "$this->title";
        $data['kolom'] = $this->getSavedTableColoumn();
        return view('accounting.trialBalance.index', $data);
    }

    public function list(Request $request)
    {
        $tbNumber = $request->tbNumber;
        $year = $request->year;

        $data = DB::table('trial_balance_hdr')
            ->when($tbNumber, fn($q) => $q->where('tb_number', 'ilike', "%$tbNumber%"))
            ->when($year, fn($q) => $q->where('year', $year))
            ->orderByDesc('id')
            ->get();

        return Datatables::of($data)
            ->addColumn('action', function ($row) {
                return '<a href="'.route('trialBalance.saved.edit', ['id' => Crypt::encryptString($row->id)]).'" class="btn btn-sm btn-outline-primary">Edit</a>';
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
        return "TB-ASN-" . date('Y') . "-$padded";
    }

    // Peek only, belum commit -- dipakai buat nampilin preview nomor di field disabled sebelum Save ditekan.
    private function previewLastCode($key)
    {
        $current = (int) DB::table('master_code')->where('code_key', $key)->value('code_number');
        $padded = str_pad($current + 1, 4, '0', STR_PAD_LEFT);
        return "TB-ASN-" . date('Y') . "-$padded";
    }

    // Accounts di-grouping per Account Type (accounts.type_code -> acc_types),
    // bukan master_neraca_detail_tb -- itu cuma mapping manual sebagian akun jadi
    // banyak sub-akun (mis. BCA-FLASH 1100.13.xx) jatuh ke "Ungrouped". Account Type
    // kodenya numeric (1100, 1200, 2000, ...) dan dimiliki SEMUA akun.
    private function groupedAccounts()
    {
        $accounts = collect(DB::select("
            SELECT a.account, a.description, a.debit_credit, a.final_statement,
                   COALESCE(a.type_code, 'UNGROUPED') as group_data,
                   COALESCE(t.name, 'Ungrouped') as group_name,
                   COALESCE(NULLIF(a.type_code,'')::numeric, 999999) as urutan
            FROM accounts a
            LEFT JOIN acc_types t ON t.code = a.type_code
            ORDER BY urutan, a.account
        "));

        $groups = $accounts->unique('group_data')->sortBy('urutan')->values();

        return [$accounts, $groups];
    }

    // Hitung saldo awal/pergerakan/saldo akhir per bulan per account langsung dari
    // jurnal (kas_det/kas_hdr, semua status kecuali cancelled/'5' -- draft & pending
    // tetap ikut keitung), bukan input manual. Saldo awal bulan N
    // = net kumulatif seluruh transaksi sebelum bulan itu; saldo akhir = saldo awal +
    // pergerakan bulan itu. Net positif (debit>credit) ditaruh di kolom debit, net
    // negatif ditaruh di kolom credit (konvensi trial balance standar).
    public function savedGenerateData(Request $request)
    {
        $year = (int) $request->year;
        if (!$year) {
            return response()->json(['status' => 0, 'message' => 'Year wajib diisi'], 422);
        }

        $monthly = DB::select("
            SELECT d.account,
                   extract(month from to_date(h.voucher_date,'dd-mm-yyyy'))::int as month,
                   sum(d.debit) as debit, sum(d.credit) as credit
            FROM kas_det d
            JOIN kas_hdr h ON d.voucher_number = h.voucher_number
            WHERE h.status <> '5'
              AND extract(year from to_date(h.voucher_date,'dd-mm-yyyy')) = ?
            GROUP BY d.account, month
        ", [$year]);

        $before = DB::select("
            SELECT d.account, sum(d.debit) as debit, sum(d.credit) as credit
            FROM kas_det d
            JOIN kas_hdr h ON d.voucher_number = h.voucher_number
            WHERE h.status <> '5'
              AND to_date(h.voucher_date,'dd-mm-yyyy') < make_date(?,1,1)
            GROUP BY d.account
        ", [$year]);

        $runningNet = [];
        foreach ($before as $b) {
            $runningNet[$b->account] = (float) $b->debit - (float) $b->credit;
        }

        $byAccount = [];
        foreach ($monthly as $m) {
            $byAccount[$m->account][(int) $m->month] = ['debit' => (float) $m->debit, 'credit' => (float) $m->credit];
        }

        $allAccounts = DB::table('accounts')->pluck('account');

        $result = [];
        foreach ($allAccounts as $account) {
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

        return response()->json(['status' => 1, 'data' => $result]);
    }

    // Voucher yang jadi sumber angka 1 cell (Saldo Awal/Pergerakan/Saldo Akhir), dipanggil
    // dari modal drill-down -- angka di grid read-only, klik untuk lihat detailnya.
    public function savedVoucherList(Request $request)
    {
        $account = $request->account;
        $year = (int) $request->year;
        $month = (int) $request->month;
        $bucket = $request->bucket; // open | mutasi | saldo
        $side = $request->side === 'credit' ? 'credit' : 'debit'; // sisi yang diklik di grid

        $monthStart = sprintf('%04d-%02d-01', $year, $month);
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        if ($bucket === 'open') {
            $dateCond = "to_date(h.voucher_date,'dd-mm-yyyy') < '$monthStart'";
        } elseif ($bucket === 'mutasi') {
            $dateCond = "to_date(h.voucher_date,'dd-mm-yyyy') between '$monthStart' and '$monthEnd'";
        } else {
            $dateCond = "to_date(h.voucher_date,'dd-mm-yyyy') <= '$monthEnd'";
        }

        // Pergerakan (mutasi) itu angka mentah satu sisi -> filter cuma baris debit/kredit
        // yang diklik. Tapi Saldo Awal/Akhir itu angka NET (debit-credit), jadi harus
        // tampilin SEMUA transaksi (debit & kredit) biar totalnya nyambung ke saldo di grid --
        // kalau di-filter satu sisi doang, totalnya ga akan pernah sama kayak saldo net-nya.
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

        $editRoutes = ['KK' => 'kasKeluar.edit', 'KM' => 'kasPenerimaan.edit', 'BK' => 'bankKeluar.edit', 'BM' => 'bankPenerimaan.edit'];
        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($rows as $row) {
            $row->edit_url = isset($editRoutes[$row->voucher_type])
                ? route($editRoutes[$row->voucher_type], ['id' => Crypt::encryptString($row->hdr_id)])
                : null;
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
        $data['title'] = "Create Trial Balance";
        $data['nextCode'] = $this->previewLastCode($this->moduleCode);
        [$data['accounts'], $data['groups']] = $this->groupedAccounts();
        $data['months'] = $this->months;
        return view('accounting.trialBalance.saved.create', $data);
    }

    public function savedStore(Request $request)
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $year = $payload['year'] ?? null;
        $rows = $payload['rows'] ?? [];

        if (!$year || empty($rows)) {
            return response()->json(['status' => 0, 'alert' => 'warning', 'message' => 'Year dan data COA wajib diisi'], 422);
        }

        $username = Auth::user()->username;

        AppHelpers::resetCode($this->moduleCode);
        $tbNumber = $this->getLastCode($this->moduleCode);

        $accounts = DB::table('accounts')->orderBy('account')->get()->keyBy('account');

        DB::beginTransaction();
        try {
            $hdrId = DB::table('trial_balance_hdr')->insertGetId([
                'tb_number' => $tbNumber,
                'year' => $year,
                'description' => $payload['description'] ?? null,
                'note' => $payload['note'] ?? null,
                'created_by' => $username,
                'updated_by' => $username,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $detRows = [];
            foreach ($rows as $row) {
                $acc = $accounts->get($row['account']);
                $detRows[] = [
                    'tb_hdr_id' => $hdrId,
                    'account' => $row['account'],
                    'description' => $acc->description ?? null,
                    'debit_credit' => $acc->debit_credit ?? null,
                    'final_statement' => $acc->final_statement ?? null,
                    'monthly' => json_encode($row['monthly'] ?? $this->emptyMonthly()),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }

            foreach (array_chunk($detRows, 500) as $chunk) {
                DB::table('trial_balance_det')->insert($chunk);
            }

            DB::commit();
            \LogActivity::addToLog("Save Trial Balance", "username: $username Status $tbNumber successfully saved");
            return response()->json(['status' => 1, 'alert' => 'success', 'message' => "$tbNumber successfully saved", 'redirect' => route('trialBalance.index')]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 0, 'alert' => 'warning', 'message' => "Failed to save: " . $e->getMessage()]);
        }
    }

    public function savedEdit(Request $request)
    {
        $id = Crypt::decryptString($request->id);
        $data['title'] = "Edit Trial Balance";
        $data['hdr'] = DB::table('trial_balance_hdr')->where('id', $id)->first();
        $data['id'] = $request->id;

        [$allAccounts, $groups] = $this->groupedAccounts();
        $groupByAccount = $allAccounts->keyBy('account');

        $details = DB::table('trial_balance_det')->where('tb_hdr_id', $id)->get()
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
        $data['months'] = $this->months;
        return view('accounting.trialBalance.saved.edit', $data);
    }

    public function savedUpdate(Request $request)
    {
        $id = Crypt::decryptString($request->id);
        $username = Auth::user()->username;
        $rows = json_decode($request->getContent(), true)['rows'] ?? [];

        DB::beginTransaction();
        try {
            DB::table('trial_balance_hdr')->where('id', $id)->update([
                'description' => $request->input('description') ?? DB::raw('description'),
                'note' => $request->input('note') ?? DB::raw('note'),
                'updated_by' => $username,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            foreach ($rows as $row) {
                DB::table('trial_balance_det')
                    ->where('id', $row['id'])
                    ->where('tb_hdr_id', $id)
                    ->update([
                        'monthly' => json_encode($row['monthly']),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            }

            DB::commit();
            return response()->json(['status' => 1, 'alert' => 'success', 'message' => 'Trial Balance updated']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 0, 'alert' => 'warning', 'message' => $e->getMessage()]);
        }
    }
}
