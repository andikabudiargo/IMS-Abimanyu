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
use App\Exports\LabaRugiExport;
use Maatwebsite\Excel\Facades\Excel;

class LabaRugiController extends Controller
{
    private $title;
    private $moduleCode;

    public function __construct()
    {
        $this->title = "Laba Rugi";
        $this->moduleCode = "LR";

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
        $period1 = $request->period2 ? $request->period1 : $request->period2;
        $period2 = $request->period1 ? $request->period2 : $request->period1;
        $filter = ""; 

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

            if($period1){
                $filter = " and voucher_number in (select voucher_number from kas_hdr where period::integer between $period1 and $period2) ";
            }
   
            $masterNeraca = DB::table('master_neraca_lr')->get();
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
                where account in (select account from master_neraca_detail_lr where sub_group = '$value->sub_group')
                and voucher_number in (select voucher_number from kas_hdr where to_date(voucher_date,'dd-mm-yyyy') between '$startDate' and '$endDate' $filter)";
            }
    
            // $queries = substr_replace($queries, '', -1);
            $queries = "select * from ($queries) as oki order by urutan";
            $queriesTotalMain = "select main,main_name,sum(saldo) as jumlah from ($queries) as oki group by main,main_name";
            $queriesTotalGroup = "select group_code,group_name,sum(saldo) as jumlah from ($queries) as oki group by group_code,group_name";
            
            $data['totalMains'] = db::select($queriesTotalMain);
            $data['totalGroups'] = db::select($queriesTotalGroup);
            $data['details'] = db::select($queries);
            $data['mains'] = db::select("select * from (select distinct on (main) * from master_neraca_lr) as oki order by urutan asc");
            $data['groups'] = db::select("select * from (select distinct on (group_data) * from master_neraca_lr) as oki order by urutan asc");
            $data['tanggal'] = str_replace('to',' to ',$datePeriode);
            $data['start'] = false;

            $qTotalPendapatan = db::select("select sum(saldo) as jumlah from ($queries) as oki where group_code = 'pendapatan' group by group_code");
            $qTotalPendapatanDiluarUsaha = db::select("select sum(saldo) as jumlah from ($queries) as oki where group_code = 'pendapatandiluarusaha' group by group_code");
            $qTotalHargaPokoPenjualan = db::select("select sum(saldo) as jumlah from ($queries) as oki where group_code = 'hargapokokpenjualan' group by group_code");
            $qTotalBiayaUmumAdministrasi = db::select("select sum(saldo) as jumlah from ($queries) as oki where group_code = 'biayaumumadministrasi' group by group_code");
            $qTotalBebanDiluarUsaha = db::select("select sum(saldo) as jumlah from ($queries) as oki where group_code = 'bebandiluarusaha' group by group_code");

            $totalPendapatan = count($qTotalPendapatan) > 0 ? $qTotalPendapatan[0]->jumlah : 0;
            $totalPendapatanDiluarUsaha = count($qTotalPendapatanDiluarUsaha) > 0 ? $qTotalPendapatanDiluarUsaha[0]->jumlah : 0;
            $totalHargaPokoPenjualan = count($qTotalHargaPokoPenjualan) > 0 ? $qTotalHargaPokoPenjualan[0]->jumlah : 1;
            $totalBiayaUmumAdministrasi = count($qTotalBiayaUmumAdministrasi) > 0 ? $qTotalBiayaUmumAdministrasi[0]->jumlah : 1;
            $totalBebanDiluarUsaha = count($qTotalBebanDiluarUsaha) > 0 ? $qTotalBebanDiluarUsaha[0]->jumlah : 1;

            $hitungLabaKotor = ($totalPendapatan+$totalPendapatanDiluarUsaha)-$totalHargaPokoPenjualan;
            $data['labaKotor'] = number_format($hitungLabaKotor,2);
            $data['marginLabaKotor'] = number_format($hitungLabaKotor/($totalPendapatan+$totalPendapatanDiluarUsaha)*100,2);
            $hitungLabaBersih = $hitungLabaKotor-($totalBiayaUmumAdministrasi+$totalBebanDiluarUsaha);
            $data['labaBersih'] = number_format($hitungLabaKotor-($totalBiayaUmumAdministrasi+$totalBebanDiluarUsaha),2);
            $data['marginLabaBersih'] = number_format($hitungLabaBersih/($totalPendapatan+$totalPendapatanDiluarUsaha)*100,2);
            
            return view("accounting.labaRugi.index",$data);
        }else{
            $data['start'] = true;
            return view("accounting.labaRugi.index",$data);
        }
    }

    public function print(Request $request)
    {
        // $id=Crypt::decryptString($request->id);
        $username = Auth::user()->username;

        // $ukuranKertas = "A4";
        $ukuranKertas = "A42page";
        
        $jumlahBaris=0;

        $data['ukuranKertas'] = $ukuranKertas;
        $data['jumlahBaris'] = $jumlahBaris;
        
        $datePeriode = $request->bsDate;
        $period1 = $request->period2 ? $request->period1 : $request->period2;
        $period2 = $request->period1 ? $request->period2 : $request->period1;

        // dd("Periode : $datePeriode,  periode 1:$period1, periode2:$period2");

        $data['title'] = "$this->title";
        $filter = ""; 

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

        $masterNeraca = DB::table('master_neraca_lr')->get();
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
            where account in (select account from master_neraca_detail_lr where sub_group = '$value->sub_group')
            and voucher_number in (select voucher_number from kas_hdr where to_date(voucher_date,'dd-mm-yyyy') between '$startDate' and '$endDate' and status = '3' $filter)";
        }

        // dd($queries);
      
        // $queries = substr_replace($queries, '', -1);
        $queries = "select * from ($queries) as oki order by urutan";
        $queriesTotalMain = "select main,main_name,sum(saldo) as jumlah from ($queries) as oki group by main,main_name";
        $queriesTotalGroup = "select group_code,group_name,sum(saldo) as jumlah from ($queries) as oki group by group_code,group_name";
        
        $data['totalMains'] = db::select($queriesTotalMain);
        $data['totalGroups'] = db::select($queriesTotalGroup);
        $data['details'] = db::select($queries);
        $data['mains'] = db::select("select * from (select distinct on (main) * from master_neraca_lr) as oki order by urutan asc");
        $data['groups'] = db::select("select * from (select distinct on (group_data) * from master_neraca_lr) as oki order by urutan asc");
        $data['tanggal'] = str_replace('to',' to ',$datePeriode);
        $data['start'] = false;

        $qTotalPendapatan = db::select("select sum(saldo) as jumlah from ($queries) as oki where group_code = 'pendapatan' group by group_code");
        $qTotalPendapatanDiluarUsaha = db::select("select sum(saldo) as jumlah from ($queries) as oki where group_code = 'pendapatandiluarusaha' group by group_code");
        $qTotalHargaPokoPenjualan = db::select("select sum(saldo) as jumlah from ($queries) as oki where group_code = 'hargapokokpenjualan' group by group_code");
        $qTotalBiayaUmumAdministrasi = db::select("select sum(saldo) as jumlah from ($queries) as oki where group_code = 'biayaumumadministrasi' group by group_code");
        $qTotalBebanDiluarUsaha = db::select("select sum(saldo) as jumlah from ($queries) as oki where group_code = 'bebandiluarusaha' group by group_code");

        $totalPendapatan = count($qTotalPendapatan) > 0 ? $qTotalPendapatan[0]->jumlah : 0;
        $totalPendapatanDiluarUsaha = count($qTotalPendapatanDiluarUsaha) > 0 ? $qTotalPendapatanDiluarUsaha[0]->jumlah : 0;
        $totalHargaPokoPenjualan = count($qTotalHargaPokoPenjualan) > 0 ? $qTotalHargaPokoPenjualan[0]->jumlah : 1;
        $totalBiayaUmumAdministrasi = count($qTotalBiayaUmumAdministrasi) > 0 ? $qTotalBiayaUmumAdministrasi[0]->jumlah : 1;
        $totalBebanDiluarUsaha = count($qTotalBebanDiluarUsaha) > 0 ? $qTotalBebanDiluarUsaha[0]->jumlah : 1;

        $hitungLabaKotor = ($totalPendapatan+$totalPendapatanDiluarUsaha)-$totalHargaPokoPenjualan;
        $data['labaKotor'] = number_format($hitungLabaKotor,2);
        $data['marginLabaKotor'] = number_format($hitungLabaKotor/($totalPendapatan+$totalPendapatanDiluarUsaha)*100,2);
        $hitungLabaBersih = $hitungLabaKotor-($totalBiayaUmumAdministrasi+$totalBebanDiluarUsaha);
        $data['labaBersih'] = number_format($hitungLabaKotor-($totalBiayaUmumAdministrasi+$totalBebanDiluarUsaha),2);
        $data['marginLabaBersih'] = number_format($hitungLabaBersih/($totalPendapatan+$totalPendapatanDiluarUsaha)*100,2);
        
        return view("accounting.labaRugi.print",$data);       

    }


    public function export(Request $request)
    {
		$datePeriode = $request->bsDate;
        $period1 = $request->period2 ? $request->period1 : $request->period2;
        $period2 = $request->period1 ? $request->period2 : $request->period1;

        return Excel::download(new LabaRugiExport($datePeriode,$period1,$period2), 'laba_rugi.xlsx');
    
	}

}
