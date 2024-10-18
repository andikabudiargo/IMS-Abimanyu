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

class BalanceSheetController extends Controller
{
    private $title;
    private $moduleCode;

    public function __construct()
    {
        $this->title = "Neraca";
        $this->moduleCode = "NRC";

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

        $datePeriode = $request->bsDate;
        $data['title'] = "$this->title";

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
    
            $masterNeraca = DB::table('master_neraca')->get();
            $sumberData="";
            $sumberData="";
            $queries="";
            $pakeUnion="";
            $hitung = "(sum(debit) - sum(credit))";
            foreach ($masterNeraca as $key => $value) {
                $pakeUnion =  $key > 0 ? " union " : "";
                $sumberData = $value->sumber_data;

                if($sumberData == 'debit'){
                    $hitung = "(sum(debit) - sum(credit))";
                }

                if($sumberData == 'credit'){
                    $hitung = "(sum(credit) - sum(debit))";
                }

                if($sumberData == 'coa'){
                    $queries .= " $pakeUnion select $value->urutan as urutan
                    ,'$value->main' as main
                    ,'$value->main_name' as main_name
                    ,'$value->group_data' as group_code
                    ,'$value->group_name' as group_name
                    ,'$value->sub_group' as sub_group
                    ,'$value->sub_group_name' as sub_group_name
                    , concat('$value->account_awal_asli','-','$value->account_akhir_asli') as account
                    , 0 as debit
                    , sum(opening_balance) as credit
                    , sum(opening_balance) as saldo 
                    from accounts 
                    where account in (select account from master_neraca_detail where sub_group = '$value->sub_group')";
                }else{
                    $queries .= " $pakeUnion select $value->urutan as urutan
                    ,'$value->main' as main
                    ,'$value->main_name' as main_name
                    ,'$value->group_data' as group_code
                    ,'$value->group_name' as group_name
                    ,'$value->sub_group' as sub_group
                    ,'$value->sub_group_name' as sub_group_name
                    , concat('$value->account_awal_asli','-','$value->account_akhir_asli') as account
                    ,sum(debit) as debit
                    ,sum(credit) as credit
                    ,$hitung as saldo 
                    from kas_det 
                    where account in (select account from master_neraca_detail where sub_group = '$value->sub_group')
                    and voucher_number in (select voucher_number from kas_hdr where to_date(voucher_date,'dd-mm-yyyy') between '$startDate' and '$endDate')";
                }

                // if($sumberData == 'coa'){
                //     $queries .= " $pakeUnion select $value->urutan as urutan
                //     ,'$value->main' as main
                //     ,'$value->main_name' as main_name
                //     ,'$value->group_data' as group_code
                //     ,'$value->group_name' as group_name
                //     ,'$value->sub_group' as sub_group
                //     ,'$value->sub_group_name' as sub_group_name
                //     , concat('$value->account_awal_asli','-','$value->account_akhir_asli') as account
                //     , 0 as debit
                //     , sum(opening_balance) as credit
                //     , sum(opening_balance) as saldo 
                //     from accounts 
                //     where account like '$value->key%' 
                //     and REPLACE(account, '.', '')::integer between '$value->account_awal'::integer and '$value->account_akhir'::integer";
                // }else{
                //     $queries .= " $pakeUnion select $value->urutan as urutan
                //     ,'$value->main' as main
                //     ,'$value->main_name' as main_name
                //     ,'$value->group_data' as group_code
                //     ,'$value->group_name' as group_name
                //     ,'$value->sub_group' as sub_group
                //     ,'$value->sub_group_name' as sub_group_name
                //     , concat('$value->account_awal_asli','-','$value->account_akhir_asli') as account
                //     ,sum(debit) as debit
                //     ,sum(credit) as credit
                //     ,$hitung as saldo 
                //     from kas_det 
                //     where account like '$value->key%' 
                //     and REPLACE(account, '.', '')::integer between '$value->account_awal'::integer and '$value->account_akhir'::integer 
                //     and voucher_number in (select voucher_number from kas_hdr where to_date(voucher_date,'dd-mm-yyyy') between '$startDate' and '$endDate')";
                // }

            }
    
            // $queries = substr_replace($queries, '', -1);
            $queries = "select * from ($queries) as oki order by urutan";
            $queriesTotalMain = "select main,main_name,sum(saldo) as jumlah from ($queries) as oki group by main,main_name";
            $queriesTotalGroup = "select group_code,group_name,sum(saldo) as jumlah from ($queries) as oki group by group_code,group_name";
            
            $data['totalMains'] = db::select($queriesTotalMain);
            $data['totalGroups'] = db::select($queriesTotalGroup);
            $data['details'] = db::select($queries);
            $data['mains'] = db::select("select * from (select distinct on (main) * from master_neraca) as oki order by urutan asc");
            $data['groups'] = db::select("select * from (select distinct on (group_data) * from master_neraca) as oki order by urutan asc");
            $data['tanggal'] = $datePeriode;
            $data['start'] = false;

             /*
                1. Quick Ratio = (Total Aktiva Lancar – Total Persediaan) / Total Kewajiban Lancar 
                2. Current Ratio = Total Aset Lancar / Total Kewajiban Lancar 
                3. Debt Equity Ratio = Total Kewajiban Lancar / Total Modal 
                4. Equity Ratio = Total Modal / Total Aset
            */

            $qTotalActivaLancar = db::select("select sum(saldo) as jumlah from ($queries) as oki where group_code = 'activalancar' and sub_group != 'persediaan' group by group_code");
            $qTotalPersediaan = db::select("select sum(saldo) as jumlah from ($queries) as oki where group_code = 'activalancar' and sub_group = 'persediaan' group by group_code");
            $qTotalKewajibanLancar = db::select("select sum(saldo) as jumlah from ($queries) as oki where group_code = 'kewajiban' group by group_code");
            $qTotalModal = db::select("select sum(saldo) as jumlah from ($queries) as oki where group_code = 'modal' group by group_code");
            $qTotalAsset = db::select("select sum(saldo) as jumlah from ($queries) as oki where main = 'asset' group by main");

            $totalActivaLancar = count($qTotalActivaLancar) > 0 ? $qTotalActivaLancar[0]->jumlah : 0;
            $totalPersediaan = count($qTotalPersediaan) > 0 ? $qTotalPersediaan[0]->jumlah : 0;
            $totalKewajibanLancar = count($qTotalKewajibanLancar) > 0 ? $qTotalKewajibanLancar[0]->jumlah : 1;
            $totalModal = count($qTotalModal) > 0 ? $qTotalModal[0]->jumlah : 1;
            $totalAsset = count($qTotalAsset) > 0 ? $qTotalAsset[0]->jumlah : 1;

            $data['quickRatio'] = number_format(($totalActivaLancar-$totalPersediaan)/$totalKewajibanLancar,2);
            $data['currentRatio'] = number_format($totalActivaLancar/$totalKewajibanLancar,2);
            $data['debtEquityRatio'] = number_format($totalKewajibanLancar/$totalModal,2);
            $data['equityRatio'] = number_format($totalModal/$totalAsset,2);
            
            return view("accounting.balanceSheet.index",$data);
        }else{
            $data['start'] = true;
            return view("accounting.balanceSheet.index",$data);
        }
    }

    public function print(Request $request)
    {
        // $id=Crypt::decryptString($request->id);
        $username = Auth::user()->username;

        $ukuranKertas = "A4";
        $jumlahBaris=0;

        $data['ukuranKertas'] = $ukuranKertas;
        $data['jumlahBaris'] = $jumlahBaris;
        
        $datePeriode = $request->bsDate;
        $data['title'] = "$this->title";

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

        $masterNeraca = DB::table('master_neraca')->get();
        $sumberData="";
        $sumberData="";
        $queries="";
        $pakeUnion="";
        $hitung = "(sum(debit) - sum(credit))";
        foreach ($masterNeraca as $key => $value) {
            $pakeUnion =  $key > 0 ? " union " : "";
            $sumberData = $value->sumber_data;

            if($sumberData == 'debit'){
                $hitung = "(sum(debit) - sum(credit))";
            }

            if($sumberData == 'credit'){
                $hitung = "(sum(credit) - sum(debit))";
            }

            if($sumberData == 'coa'){
                $queries .= " $pakeUnion select $value->urutan as urutan
                ,'$value->main' as main
                ,'$value->main_name' as main_name
                ,'$value->group_data' as group_code
                ,'$value->group_name' as group_name
                ,'$value->sub_group' as sub_group
                ,'$value->sub_group_name' as sub_group_name
                , concat('$value->account_awal_asli','-','$value->account_akhir_asli') as account
                , 0 as debit
                , sum(opening_balance) as credit
                , sum(opening_balance) as saldo 
                from accounts 
                where account in (select account from master_neraca_detail where sub_group = '$value->sub_group')";
            }else{
                $queries .= " $pakeUnion select $value->urutan as urutan
                ,'$value->main' as main
                ,'$value->main_name' as main_name
                ,'$value->group_data' as group_code
                ,'$value->group_name' as group_name
                ,'$value->sub_group' as sub_group
                ,'$value->sub_group_name' as sub_group_name
                , concat('$value->account_awal_asli','-','$value->account_akhir_asli') as account
                ,sum(debit) as debit
                ,sum(credit) as credit
                ,$hitung as saldo 
                from kas_det 
                where account in (select account from master_neraca_detail where sub_group = '$value->sub_group')
                and voucher_number in (select voucher_number from kas_hdr where to_date(voucher_date,'dd-mm-yyyy') between '$startDate' and '$endDate')";
            }

            // if($sumberData == 'coa'){
            //     $queries .= " $pakeUnion select $value->urutan as urutan
            //     ,'$value->main' as main
            //     ,'$value->main_name' as main_name
            //     ,'$value->group_data' as group_code
            //     ,'$value->group_name' as group_name
            //     ,'$value->sub_group' as sub_group
            //     ,'$value->sub_group_name' as sub_group_name
            //     , concat('$value->account_awal_asli','-','$value->account_akhir_asli') as account
            //     , 0 as debit
            //     , sum(opening_balance) as credit
            //     , sum(opening_balance) as saldo 
            //     from accounts 
            //     where account like '$value->key%' 
            //     and REPLACE(account, '.', '')::integer between '$value->account_awal'::integer and '$value->account_akhir'::integer";
            // }else{
            //     $queries .= " $pakeUnion select $value->urutan as urutan
            //     ,'$value->main' as main
            //     ,'$value->main_name' as main_name
            //     ,'$value->group_data' as group_code
            //     ,'$value->group_name' as group_name
            //     ,'$value->sub_group' as sub_group
            //     ,'$value->sub_group_name' as sub_group_name
            //     , concat('$value->account_awal_asli','-','$value->account_akhir_asli') as account
            //     ,sum(debit) as debit
            //     ,sum(credit) as credit
            //     ,$hitung as saldo 
            //     from kas_det 
            //     where account like '$value->key%' 
            //     and REPLACE(account, '.', '')::integer between '$value->account_awal'::integer and '$value->account_akhir'::integer 
            //     and voucher_number in (select voucher_number from kas_hdr where to_date(voucher_date,'dd-mm-yyyy') between '$startDate' and '$endDate')";
            // }

        }

        // $queries = substr_replace($queries, '', -1);
        $queries = "select * from ($queries) as oki order by urutan";
        $queriesTotalMain = "select main,main_name,sum(saldo) as jumlah from ($queries) as oki group by main,main_name";
        $queriesTotalGroup = "select group_code,group_name,sum(saldo) as jumlah from ($queries) as oki group by group_code,group_name";
        
        $data['totalMains'] = db::select($queriesTotalMain);
        $data['totalGroups'] = db::select($queriesTotalGroup);
        $data['details'] = db::select($queries);
        $data['mains'] = db::select("select * from (select distinct on (main) * from master_neraca) as oki order by urutan asc");
        $data['groups'] = db::select("select * from (select distinct on (group_data) * from master_neraca) as oki order by urutan asc");
        $data['tanggal'] = $datePeriode;
        $data['start'] = false;

            /*
            1. Quick Ratio = (Total Aktiva Lancar – Total Persediaan) / Total Kewajiban Lancar 
            2. Current Ratio = Total Aset Lancar / Total Kewajiban Lancar 
            3. Debt Equity Ratio = Total Kewajiban Lancar / Total Modal 
            4. Equity Ratio = Total Modal / Total Aset
        */

        $qTotalActivaLancar = db::select("select sum(saldo) as jumlah from ($queries) as oki where group_code = 'activalancar' and sub_group != 'persediaan' group by group_code");
        $qTotalPersediaan = db::select("select sum(saldo) as jumlah from ($queries) as oki where group_code = 'activalancar' and sub_group = 'persediaan' group by group_code");
        $qTotalKewajibanLancar = db::select("select sum(saldo) as jumlah from ($queries) as oki where group_code = 'kewajiban' group by group_code");
        $qTotalModal = db::select("select sum(saldo) as jumlah from ($queries) as oki where group_code = 'modal' group by group_code");
        $qTotalAsset = db::select("select sum(saldo) as jumlah from ($queries) as oki where main = 'asset' group by main");

        $totalActivaLancar = count($qTotalActivaLancar) > 0 ? $qTotalActivaLancar[0]->jumlah : 0;
        $totalPersediaan = count($qTotalPersediaan) > 0 ? $qTotalPersediaan[0]->jumlah : 0;
        $totalKewajibanLancar = count($qTotalKewajibanLancar) > 0 ? $qTotalKewajibanLancar[0]->jumlah : 1;
        $totalModal = count($qTotalModal) > 0 ? $qTotalModal[0]->jumlah : 1;
        $totalAsset = count($qTotalAsset) > 0 ? $qTotalAsset[0]->jumlah : 1;

        $data['quickRatio'] = number_format(($totalActivaLancar-$totalPersediaan)/$totalKewajibanLancar,2);
        $data['currentRatio'] = number_format($totalActivaLancar/$totalKewajibanLancar,2);
        $data['debtEquityRatio'] = number_format($totalKewajibanLancar/$totalModal,2);
        $data['equityRatio'] = number_format($totalModal/$totalAsset,2);
        
        return view("accounting.balanceSheet.print",$data);       

    }

}
