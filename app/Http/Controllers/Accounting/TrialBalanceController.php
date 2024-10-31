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
}
