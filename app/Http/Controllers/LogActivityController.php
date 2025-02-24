<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use DataTables;

class LogActivityController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function getTableColoumn(){
        $kolom=
        [
            // ['data'>'id',title:'No',render: function (data, type, row, meta) {
            //         return meta.row + meta.settings._iDisplayStart + 1;
            //     }, orderable: false, searchable: false
            // },
            ['data'=>'subject','name'=>'subject','title'=>'Subject'],
            ['data'=>'description','name'=>'description','title'=>'Description'],
            // ['data'=>'url','name'=>'url','title'=>'URL'],
            // ['data'=>'method','name'=>'method','title'=>'Method'],
            // ['data'=>'agent','name'=>'agen','title'=>'User Agent'],
            ['data'=>'ip','name'=>'ip','title'=>'IP Address'],
            ['data'=>'name','name'=>'name','title'=>'Username'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Date'],
        ];

        return json_encode($kolom, true);
    }

    public function index()
    {
        $data['kolom'] = $this->getTableColoumn();
        $data['users'] = db::table('users')
        ->where('status','1')
        ->whereNotIn('name',['Direktur','admin','oki','Administrator','Supervisor'])
        ->get();
        return view('log.logActivity',$data);
    }

    public function myTestAddToLog()
    {
        \LogActivity::addToLog('My Testing Add To Log.');
        dd('log insert successfully.');
    }


    public function showLogLists(Request $request)
    {
        $searchDesc = $request->searchDesc;
        $searchSubject = $request->searchSubject ? $request->searchSubject : '';
        $searchUserId = $request->searchUserId;
        $searchDate = $request->searchDate;
        $fromDate = "";
        $toDate = "";

        if ($searchDate){
            $date = explode("to",$searchDate);
            if(count($date)>1){
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate; 
            }
        }

        // $jumlahArray = $searchSubject ? count($searchSubject) : '';
        
        // if($jumlahArray>0){
        //     $dataIsi = $searchDesc.$searchUserId.$searchDate;
        // }else{
        //     $dataIsi = $searchDesc.$searchSubject.$searchUserId.$searchDate;
        // }

        $dataIsi = $searchDesc.$searchSubject.$searchUserId.$searchDate;
        
        if ($dataIsi != ''){
            $data = DB::table('log_activities')
            ->leftJoin('users','log_activities.user_id','users.username')
            ->where(function ($query) use ($searchDesc,$searchSubject,$searchUserId,$searchDate,$fromDate,$toDate) {
                $searchDesc ? $query->where('description','ilike','%'.$searchDesc.'%') : '';
                $searchSubject ? $query->where('subject','ilike','%'.$searchSubject.'%') : '';
                // $searchSubject ? $query->whereIn('subject',$searchSubject) : '';
                $searchUserId ? $query->where('user_id','=',$searchUserId) : '';
                $searchDate ? $query->whereBetween('log_activities.created_at', [$fromDate, $toDate]) : '';
            })
            // ->limit(1000)
            ->get();
        }else{
            $data = DB::table('log_activities')
            ->leftJoin('users','log_activities.user_id','users.username')
            ->orderBy('log_activities.created_at','desc')
            ->limit(1000)
            ->get();
            // $logs = \LogActivity::logActivityLists();
        }

        return Datatables::of($data)
        ->make(true);
    }

}
