<?php

namespace App\Exports;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;

use DB;

class LabaRugiExport implements FromView,ShouldAutoSize,WithColumnFormatting,WithEvents
{
    protected $datePeriode;
    protected $period1;
    protected $period2;

    function __construct($datePeriode,$period1,$period2) {

        $this->datePeriode = $datePeriode;
        $this->period1 = $period1;
        $this->period2 = $period2;
    }
    public function view(): View
    {
        $datePeriode = $this->datePeriode;
        $period1 = $this->period2 ? $this->period1 : $this->period2;
        $period2 = $this->period1 ? $this->period2 : $this->period1;
        $filter = ""; 
        $ukuranKertas = "A4";
        $jumlahBaris=0;

        $data['ukuranKertas'] = $ukuranKertas;
        $data['jumlahBaris'] = $jumlahBaris;

        $data['title'] = "Print Laba Rugi";

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
        
        return view("accounting.labaRugi.excel",$data);
        
    }

    public function columnFormats(): Array
    {
        return [
            // 'E' => NumberFormat::FORMAT_NUMBER_00,
            // 'F' => NumberFormat::FORMAT_NUMBER_00,
            // 'G' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {
                $alphabet   = $event->sheet->getHighestDataColumn();
                $totalRow   = $event->sheet->getHighestDataRow();
                $cellRange  = 'A1:'.$alphabet.$totalRow;

                // $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(10);
                // $cellRange = 'A1:W1'; // All headers
                // $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(14);
            },
        ];
    }


}