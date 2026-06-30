<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Response;
use App\Permission;
use DataTables;
use DB;

class LockTransactionController extends Controller
{
    private $title;
    private $decimalPlaces;
    public function __construct()
    {
        $this->title = "Lock Transaction";
        $this->decimalPlaces = config('globalParam.decimal');
    }

    public function index(Request $request)
{
    $data['title'] = "$this->title";

      $excludedCodes = ['APINV', 'BDIS', 'PRD']; // sesuaikan dengan code_key aslinya
    
    $menus = DB::table('approval_master')
        ->leftJoin('application_lock', function($join) {
            $join->on('approval_master.module_code', '=', 'application_lock.code_key')
                 ->where('application_lock.status', '1');
        })
         ->whereNotIn('approval_master.module_code', $excludedCodes)
        ->select(
            'approval_master.module_code as code_key',
            'approval_master.module_name',
            'application_lock.created_by',
            DB::raw("to_char(application_lock.lock_date, 'dd-mm-yyyy') as lock_date"),
            DB::raw("to_char(application_lock.created_at, 'dd-mm-yyyy hh:ss:mm') as created_at")
        )
        ->orderBy('module_name')
        ->get();

    // tambahkan module yang tidak ada di approval_master
    $extraModules = [
        ['code_key' => 'ART', 'module_name' => 'Article'],
    ];

    foreach ($extraModules as $extra) {
        $lock = DB::table('application_lock')
            ->where('code_key', $extra['code_key'])
            ->where('status', '1')
            ->first();

        $menus->push((object)[
            'code_key'   => $extra['code_key'],
            'module_name'=> $extra['module_name'],
            'created_by' => $lock->created_by ?? null,
            'lock_date'  => $lock ? date('d-m-Y', strtotime($lock->lock_date)) : null,
            'created_at' => $lock ? date('d-m-Y H:i:s', strtotime($lock->created_at)) : null,
        ]);
    }

    $data['menus'] = $menus->sortBy('module_name')->values();

    return view("lockTransaction.index", $data);
}

    public function indexOld(Request $request)
    {
        $data['title'] = "$this->title";
        
        $data['menus'] = DB::table('application_lock')
        ->leftJoin('approval_master','module_code','code_key')
        ->where('status','1')
        ->select('application_lock.*'
        ,'module_name'
        ,db::raw("to_char(lock_date, 'dd-mm-yyyy') as lock_date")
        ,db::raw("to_char(application_lock.created_at, 'dd-mm-yyyy hh:ss:mm') as created_at")
        )
        ->orderBy('module_name')
        ->get();

        return view("lockTransaction.index",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $codeKey = $request->codeKey;
        $newDate = $request->newDate;
        $dateBefore = $request->dateBefore;

        DB::table('application_lock')
        ->where('status','1')
        ->update([
            'status'=> 0,
            'updated_by' => Auth::user()->username,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        foreach($codeKey as $index=>$val){
            $lockDate = $newDate[$index] ? date('Y/m/d', strtotime($newDate[$index])) : null ;
            if( ($lockDate == null) && $dateBefore[$index] ){
                $lockDate = date('Y/m/d', strtotime($dateBefore[$index]));
            }

            DB::table('application_lock')
            ->insert([
                'code_key' => $val,
                'lock_date' => $lockDate,
                'status' => '1',
                'created_by' => Auth::user()->username,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_by' => Auth::user()->username,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }

        $title ="Update $this->title";
        $alert  ="success";
        $message  = "$title Successfully updated";
        \LogActivity::addToLog($title,"username: $username Status $message");
        return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);               
    }

}
