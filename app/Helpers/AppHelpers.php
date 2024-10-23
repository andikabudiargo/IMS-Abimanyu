<?php
namespace App\Helpers;
use DB;

/*global function supaya bisa di panggil dari controller yang lain 
*/

class AppHelpers
{
    public static function resetCode($condKey){
        $afterEffect = DB::select("UPDATE 
            master_code set code_number = 0,
            last_reset = now(),
            updated_at = now(),
            updated_by = 'system'
            WHERE 
            (date_part(reset_by,last_reset) <> date_part(reset_by,current_date::timestamp) or last_reset is null) and 
            code_key = '$condKey'");
        return $afterEffect;
    }

    public static function lockDate($moduleCode){
        /*
        $lastDatePrevMonth = date('t-m-Y', strtotime('-1 months'));
        $lastDatePrevMonth = date('t-m-Y', strtotime('-1 months',strtotime('05-11-2023')));
        $firstDayCurrentMonth = date('1-m-Y');
        $firstDayCurrentMonth = date('1-m-Y', strtotime('05-11-2023'));
        $prevmonth = date('M Y 1', strtotime('-1 months'));
        
        Pagi pak Leo, saya maumastikan untuk proses lock data, apakah benar sepertin ini, tolong konfirmasi nya
        Apabila tidak sesuai minta contoh untuk penerapannya

        Tanggal lock itu adalah H-1 dari tanggal yang disetting
        misalkan:
        tanggal lock yang di setting : 15-10-2024
        maka tanggal lock yang berlaku itu :14-10-2024

        Ketentuan tanggal lock nya:

        1. Jika tanggal hari ini lebih kecil dari tanggal lock (Lock Date) maka tanggal Lock nya adalah 
        tanggal 1 di bulan sebelumnya.
        
        Misalkan 
        Tanggal Hari ini : 16-10-2024 
        Tanggal Lock date yang disetting: 17-10-2024
        Maka Tanggal lock yang berlaku adalah : 1-9-2024
        Jadi semua transaksi yang lebih kecil dari tanggal 1-9-2024 tidak akan bisa di edit, delete dan revisi

        2. Jika tanggal hari ini lebih besar dari tanggal lock (Locak Date) maka tanggal Lock nya adalah 
        tanggal 1 di bulan ini.

        Misalkan 
        Tanggal Hari ini : 16-10-2024
        Tanggal Lock date yang disetting: 15-10-2024
        Maka Tanggal lock yang berlaku adalah : 1-10-2024
        Jadi semua transaksi yang lebih kecil dari tanggal 1-10-2024 tidak akan bisa di edit, delete dan revisi

        */

        $lockDate1 = DB::table('application_lock')
        ->where('code_key',$moduleCode)
        ->where('status','1')
        ->value('lock_date');

        $todayDate = date('d-m-Y');
        $lockDateHere = $lockDate1 ? $lockDate1 : '2023-01-01' ;
        $lockDateAt = date('d-m-Y', strtotime("+1 day", strtotime($lockDateHere)));

        if ($todayDate < $lockDateAt ){
            $firstDatePrevMonth = date('1-m-Y', strtotime("-1 months",strtotime($lockDateHere)));
            $lockDateAt = $firstDatePrevMonth;
        }else{
            $lockDateAt = date('1-m-Y', strtotime($lockDateAt));
        }

        // tanggal lock yang sebenarnya
        // $this->lockDate = $lockDateAt;

        $lockDateHereIndex = $lockDate1 ? $lockDate1 : '2023-01-01' ;

        //data yang ditampilkan di menu index
        $lockDateAtIndex = date('d-m-Y', strtotime($lockDateHere));
        // $this->lockDateIndex = $lockDateAtIndex;

        $hasilLockDate = [$lockDateAt,$lockDateAtIndex];

        return $hasilLockDate;
    }
}