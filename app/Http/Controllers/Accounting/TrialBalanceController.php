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

    public function index(Request $request)
    {

        $data['title'] = "$this->title";
        $datePeriode = $request->bsDate;

        $period1 = $request->period2 ? $request->period1 : $request->period2;
        $period2 = $request->period1 ? $request->period2 : $request->period1;

        if($datePeriode){

            if ($datePeriode){
                $date = explode("to",$datePeriode);
                if(count($date)>1){
                    $startDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                    $endDate = implode("/", array_reverse(explode("-", trim($date[1]))));
                }else{
                    $startDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                    $endDate = $startDate; 
                }
            }

            if($period1){
                $filter = " and period::integer between $period1 and $period2 ";
            }

            $queries="SELECT 
            mn.urutan
            ,mn.key
            ,mn.group_data
            ,mn.group_name
            ,mn.sub_group
            ,mn.sub_group_name
            ,concat(account_awal_asli,'-',account_akhir_asli) as account
            ,account_awal_asli
            ,account_akhir_asli
            , case when sumber_data ='debit' then opening_balance else 0 end as opening_balance_debit
            , case when sumber_data ='credit' then opening_balance else 0 end as opening_balance_credit
            ,pergerakan_debit
            ,pergerakan_credit
            ,case when sumber_data ='debit' then (opening_balance+pergerakan_debit)-pergerakan_credit end as saldo_akhir_debit
            ,case when sumber_data ='credit' then (opening_balance+pergerakan_credit)-pergerakan_credit end as saldo_akhir_credit
            from master_neraca_tb mn
            left join (
            select sub_group, sum(opening_balance) as opening_balance from master_neraca_detail_tb x
            left join accounts y on x.account = y.account
            group by sub_group
            ) h
            on mn.sub_group = h.sub_group
            left join (
            select group_data,sub_group, sum(debit) as pergerakan_debit,sum(credit) as pergerakan_credit from (
            select account, sum(debit) as debit,sum(credit) as credit
            from 
            kas_det 
            where 
            voucher_number in (select voucher_number from kas_hdr where to_date(voucher_date, 'dd-mm-yyyy') between '$startDate' and  '$endDate' and status = '3' $filter) 
            and account in (select account from master_neraca_detail_tb)
            group by account) c 
            left join master_neraca_detail_tb d on c.account = d.account
            group by group_data,sub_group
            order by d.group_data,d.sub_group) i
            on mn.group_data = i.group_data and mn.sub_group = i.sub_group
            order by urutan";

            /*
            ini pake cara langsung hitung opening balance
            $queries = "SELECT 
            mn.urutan
            ,mn.key
            ,mn.group_data
            ,mn.group_name
            ,mn.sub_group
            ,mn.sub_group_name
            ,concat(account_awal_asli,'-',account_akhir_asli) as account
            ,account_awal_asli
            ,account_akhir_asli
            , case when ((opening_balance_debit_1 > 0) and (opening_balance_credit_1) > 0) or (pergerakan_debit > 0 and pergerakan_debit > 0) then (opening_balance_debit_1-opening_balance_credit_1) else opening_balance_debit_1 end as opening_balance_debit
            , case when ((opening_balance_debit_1 > 0) and (opening_balance_credit_1) > 0) or (pergerakan_debit > 0 and pergerakan_debit > 0) then 0 else opening_balance_credit_1 end as opening_balance_credit
            ,pergerakan_debit
            ,pergerakan_credit
            ,case when ((opening_balance_debit_1 > 0) and (opening_balance_credit_1) > 0) or (pergerakan_debit > 0 and pergerakan_debit > 0) then 
            ((case when ((opening_balance_debit_1 > 0) and (opening_balance_credit_1) > 0) or (pergerakan_debit > 0 and pergerakan_debit > 0) then (opening_balance_debit_1-opening_balance_credit_1) else opening_balance_debit_1 end)+pergerakan_debit)-pergerakan_credit
            else (case when ((opening_balance_debit_1 > 0) and (opening_balance_credit_1) > 0) or (pergerakan_debit > 0 and pergerakan_debit > 0) then (opening_balance_debit_1-opening_balance_credit_1) else opening_balance_debit_1 end)+pergerakan_debit end as saldo_akhir_debit
            ,case when ((opening_balance_debit_1 > 0) and (opening_balance_credit_1) > 0) or (pergerakan_debit > 0 and pergerakan_debit > 0) then 
            00
            else (case when ((opening_balance_debit_1 > 0) and (opening_balance_credit_1) > 0) or (pergerakan_debit > 0 and pergerakan_debit > 0) then (opening_balance_debit_1-opening_balance_credit_1) else opening_balance_debit_1 end)+pergerakan_credit end as saldo_akhir_credit
            --,saldo_akhir_debit
            --,saldo_akhir_credit 
            from master_neraca_tb mn
            left join (
            select group_data,sub_group, sum(debit) as opening_balance_debit_1,sum(credit) as opening_balance_credit_1 from (
            select account, sum(debit) as debit,sum(credit) as credit
            from 
            kas_det 
            where 
            voucher_number in (select voucher_number from kas_hdr where to_date(voucher_date, 'dd-mm-yyyy') < '$startDate' and status = '3') 
            and account in (select account from master_neraca_detail_tb)
            group by account) a 
            left join master_neraca_detail_tb b on a.account = b.account
            group by group_data,sub_group
            order by b.group_data,b.sub_group) h
            on mn.group_data = h.group_data and mn.sub_group = h.sub_group
            left join (
            select group_data,sub_group, sum(debit) as pergerakan_debit,sum(credit) as pergerakan_credit from (
            select account, sum(debit) as debit,sum(credit) as credit
            from 
            kas_det 
            where 
            voucher_number in (select voucher_number from kas_hdr where to_date(voucher_date, 'dd-mm-yyyy') between '$startDate' and  '$endDate' and status = '3') 
            and account in (select account from master_neraca_detail_tb)
            group by account) c 
            left join master_neraca_detail_tb d on c.account = d.account
            group by group_data,sub_group
            order by d.group_data,d.sub_group) i
            on mn.group_data = i.group_data and mn.sub_group = i.sub_group
            --where key = '3'
            order by urutan";
            */

            /*
            $queries = "SELECT oki.*, main_name, group_name, sub_group_name, concat(account_awal_asli,'-',account_akhir_asli) as account from (
            SELECT (select urutan from master_neraca_tb mn where mn.group_data = n.group_data and mn.sub_group = n.sub_group) as urutan, n.main,n.group_data, n.sub_group, sum(t.debit) as opening_balance_debit, sum(t.credit) opening_balance_credit, sum(a.debit) pergerakan_debit, sum(a.credit) pergerakan_credit,sum(a.debit+t.debit) saldo_akhir_debit, sum(a.credit+t.credit) saldo_akhir_credit from 
            (
            select account, sum(debit) as debit,sum(credit) as credit
            from 
            kas_det 
            where 
            voucher_number in (select voucher_number from kas_hdr where to_date(voucher_date, 'dd-mm-yyyy') < '$startDate') 
            and account in (select account from master_neraca_detail_tb)
            group by account
            )
            a
            left join (
            select account, sum(debit) debit ,sum(credit) credit
            from 
            kas_det 
            where 
            voucher_number in (select voucher_number from kas_hdr where to_date(voucher_date, 'dd-mm-yyyy') between '$startDate' and  '$endDate')
            and account in (select account from master_neraca_detail_tb)
            group by account
            ) t
            on a.account = t.account
            left join master_neraca_detail_tb n on n.account = a.account 
            group by n.main,n.group_data,n.sub_group) oki
            left join master_neraca_tb m on (m.group_data = oki.group_data and m.sub_group = oki.sub_group)
            order by oki.urutan";
            */
           
            $data['details'] = db::select($queries);
            $data['groups'] = db::select("select * from (select distinct on (group_data) * from master_neraca_tb) as oki order by urutan asc");
            $data['tanggal'] = $datePeriode;
            $data['start'] = false;
  
            $data['total'] = db::select("SELECT 
            sum(opening_balance_debit) as opening_balance_debit
            ,sum(opening_balance_credit) as opening_balance_credit
            ,sum(pergerakan_debit) as pergerakan_debit
            ,sum(pergerakan_credit) as pergerakan_credit
            ,sum(saldo_akhir_debit) as saldo_akhir_debit
            ,sum(saldo_akhir_credit) as saldo_akhir_credit 
            from ($queries) oki");

            $qTotalSaldoAwal = $data['total'][0]->opening_balance_debit;
            $qTotalSaldoAkhir = $data['total'][0]->saldo_akhir_debit;
            $qTotalNet = $data['total'][0]->saldo_akhir_debit-$data['total'][0]->saldo_akhir_credit;
            $qTotalPergerakanKas = ($qTotalSaldoAwal+$data['total'][0]->pergerakan_debit+$qTotalSaldoAkhir)-($data['total'][0]->opening_balance_credit+$data['total'][0]->pergerakan_credit+$data['total'][0]->saldo_akhir_credit);

            $data['saldoAwal'] = number_format($qTotalSaldoAwal,2);
            $data['saldoAkhir'] = number_format($qTotalSaldoAkhir,2);
            $data['net'] = number_format($qTotalNet,2);
            $data['pergerakanKas'] = number_format($qTotalPergerakanKas,2);
            
            return view("accounting.trialBalance.index",$data);
        }else{
            $data['start'] = true;
            return view("accounting.trialBalance.index",$data);
        }
    }

    public function print(Request $request)
    {
        // $id=Crypt::decryptString($request->id);
        $username = Auth::user()->username;

        $ukuranKertas = "A42pageLs";
        $jumlahBaris=0;

        $data['ukuranKertas'] = $ukuranKertas;
        $data['jumlahBaris'] = $jumlahBaris;
        
        $datePeriode = $request->bsDate;
        $data['title'] = "$this->title";

        $data['title'] = "$this->title";
        $datePeriode = $request->bsDate;

        $period1 = $request->period2 ? $request->period1 : $request->period2;
        $period2 = $request->period1 ? $request->period2 : $request->period1;

        

        if ($datePeriode){
            $date = explode("to",$datePeriode);
            if(count($date)>1){
                $startDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $endDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            }else{
                $startDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $endDate = $startDate; 
            }
        }

        if($period1){
            $filter = " and period::integer between $period1 and $period2 ";
        }

        $queries="SELECT 
        mn.urutan
        ,mn.key
        ,mn.group_data
        ,mn.group_name
        ,mn.sub_group
        ,mn.sub_group_name
        ,concat(account_awal_asli,'-',account_akhir_asli) as account
        ,account_awal_asli
        ,account_akhir_asli
        , case when sumber_data ='debit' then opening_balance else 0 end as opening_balance_debit
        , case when sumber_data ='credit' then opening_balance else 0 end as opening_balance_credit
        ,pergerakan_debit
        ,pergerakan_credit
        ,case when sumber_data ='debit' then (opening_balance+pergerakan_debit)-pergerakan_credit end as saldo_akhir_debit
        ,case when sumber_data ='credit' then (opening_balance+pergerakan_credit)-pergerakan_credit end as saldo_akhir_credit
        from master_neraca_tb mn
        left join (
        select sub_group, sum(opening_balance) as opening_balance from master_neraca_detail_tb x
        left join accounts y on x.account = y.account
        group by sub_group
        ) h
        on mn.sub_group = h.sub_group
        left join (
        select group_data,sub_group, sum(debit) as pergerakan_debit,sum(credit) as pergerakan_credit from (
        select account, sum(debit) as debit,sum(credit) as credit
        from 
        kas_det 
        where 
        voucher_number in (select voucher_number from kas_hdr where to_date(voucher_date, 'dd-mm-yyyy') between '$startDate' and  '$endDate' and status = '3' $filter) 
        and account in (select account from master_neraca_detail_tb)
        group by account) c 
        left join master_neraca_detail_tb d on c.account = d.account
        group by group_data,sub_group
        order by d.group_data,d.sub_group) i
        on mn.group_data = i.group_data and mn.sub_group = i.sub_group
        order by urutan";
                    
        $data['details'] = db::select($queries);
        $data['groups'] = db::select("select * from (select distinct on (group_data) * from master_neraca_tb) as oki order by urutan asc");
        $data['tanggal'] = $datePeriode;
        $data['start'] = false;

        $data['total'] = db::select("SELECT 
        sum(opening_balance_debit) as opening_balance_debit
        ,sum(opening_balance_credit) as opening_balance_credit
        ,sum(pergerakan_debit) as pergerakan_debit
        ,sum(pergerakan_credit) as pergerakan_credit
        ,sum(saldo_akhir_debit) as saldo_akhir_debit
        ,sum(saldo_akhir_credit) as saldo_akhir_credit 
        from ($queries) oki");

        $qTotalSaldoAwal = $data['total'][0]->opening_balance_debit;
        $qTotalSaldoAkhir = $data['total'][0]->saldo_akhir_debit;
        $qTotalNet = $data['total'][0]->saldo_akhir_debit-$data['total'][0]->saldo_akhir_credit;
        $qTotalPergerakanKas = ($qTotalSaldoAwal+$data['total'][0]->pergerakan_debit+$qTotalSaldoAkhir)-($data['total'][0]->opening_balance_credit+$data['total'][0]->pergerakan_credit+$data['total'][0]->saldo_akhir_credit);

        $data['saldoAwal'] = number_format($qTotalSaldoAwal,2);
        $data['saldoAkhir'] = number_format($qTotalSaldoAkhir,2);
        $data['net'] = number_format($qTotalNet,2);
        $data['pergerakanKas'] = number_format($qTotalPergerakanKas,2);
        $data['tanggal'] = str_replace('to',' to ',$datePeriode);

        return view("accounting.trialBalance.print",$data);
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

    public function savedList(Request $request)
    {
        $data['title'] = "Saved Trial Balance";
        $data['list'] = DB::table('trial_balance_hdr')->orderByDesc('id')->get();
        return view('accounting.trialBalance.saved.list', $data);
    }

    // Accounts di-grouping sama seperti live Trial Balance report: lewat
    // master_neraca_detail_tb (account -> group_data/sub_group) + master_neraca_tb
    // (group_data/sub_group -> group_name, urutan). Account yang belum di-mapping
    // masuk bucket "UNGROUPED" di urutan paling akhir.
    private function groupedAccounts()
    {
        $accounts = collect(DB::select("
            SELECT a.account, a.description, a.debit_credit, a.final_statement,
                   COALESCE(mn.group_data, 'UNGROUPED') as group_data,
                   COALESCE(mn.group_name, 'Ungrouped') as group_name,
                   COALESCE(mn.urutan, 999999) as urutan
            FROM accounts a
            LEFT JOIN master_neraca_detail_tb d ON a.account = d.account
            LEFT JOIN master_neraca_tb mn ON mn.group_data = d.group_data AND mn.sub_group = d.sub_group
            ORDER BY COALESCE(mn.urutan, 999999), a.account
        "));

        $groups = $accounts->unique('group_data')->sortBy('urutan')->values();

        return [$accounts, $groups];
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
            return response()->json(['status' => 1, 'alert' => 'success', 'message' => "$tbNumber successfully saved", 'redirect' => route('trialBalance.saved.list')]);
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

        [, $groups] = $this->groupedAccounts();
        $groupByAccount = DB::select("
            SELECT a.account,
                   COALESCE(mn.group_data, 'UNGROUPED') as group_data,
                   COALESCE(mn.urutan, 999999) as urutan
            FROM accounts a
            LEFT JOIN master_neraca_detail_tb d ON a.account = d.account
            LEFT JOIN master_neraca_tb mn ON mn.group_data = d.group_data AND mn.sub_group = d.sub_group
        ");
        $groupByAccount = collect($groupByAccount)->keyBy('account');

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
