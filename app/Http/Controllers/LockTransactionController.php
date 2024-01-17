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
        
        $data['menus'] = DB::table('application_lock')
        ->leftJoin('approval_master','module_code','code_key')
        ->where('status','1')
        ->select('application_lock.*'
        ,'module_name'
        ,db::raw("to_char(lock_date, 'dd-mm-yyyy') as lock_date")
        ,db::raw("to_char(application_lock.created_at, 'dd-mm-yyyy hh:ss:mm') as created_at")
        )
        ->orderBy('code_key')
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
