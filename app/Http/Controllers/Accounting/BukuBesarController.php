<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Response;
use App\Permission;
use DataTables;
use DB;
use PDF;
use AppHelpers;
use Approval;

class BukuBesarController extends Controller
{
    private $title;
    private $moduleCode;
    public function __construct()
    {
        $this->title = "Buku Besar";
        $this->moduleCode = "BB";
    }

    public function getTableColoumn()
    {
        $kolom=
        [
            ['data'=>'nama_dept','name'=>'nama_dept','title'=>'Dept'],
            ['data'=>'account','name'=>'account','title'=>'Account'],
            ['data'=>'nama_akun','name'=>'nama_akun','title'=>'Account Name'],
            ['data'=>'reference','name'=>'reference','title'=>'Reference'],
            ['data'=>'voucher_number','name'=>'voucher_number','title'=>'Voucher Number'],
            ['data'=>'description','name'=>'description','title'=>'Description'],
            ['data'=>'voucher_date','name'=>'voucher_date','title'=>'Date'],
            ['data'=>'voucher_date_2','name'=>'voucher_date_2','title'=>'Date', 'visible'=>false],
            ['data'=>'period','name'=>'period','title'=>'Period'],
            ['data'=>'debit','name'=>'debit','title'=>'Debet'],
            ['data'=>'credit','name'=>'credit','title'=>'Kredit'],
            ['data'=>'statusku','name'=>'statusku','title'=>'Status'],
            ['data'=>'approval_by','name'=>'approval_by','title'=>'Approve By'],
            ['data'=>'approval_at','name'=>'approval_at','title'=>'Approve At']
            
        ];
        return json_encode($kolom, true);
    }

    public function getLastCode($key)
    {
        DB::table('master_code')
        ->where('code_key',$key)
        ->update([
            'code_number' => DB::raw('code_number + 1'),
            'updated_by' => Auth::user()->username,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $newCode = DB::table('master_code')
        ->where('code_key',$key)
        ->value('code_number'); 

        $newCode = str_pad($newCode,4,"0",STR_PAD_LEFT);
        $month = str_pad(date('n'),2,"0",STR_PAD_LEFT);
        $year = date('y');
        $code="$key/$month/$year/$newCode";
        
        return $code;
    }

    public function index(Request $request)
    {
        $data['title'] = $this->title;
        
        $data['kolom'] = $this->getTableColoumn();

        $data['depts'] = DB::table('depts')
        ->orderBy('name')
        ->get();

        $data['accounts'] = DB::table('accounts')
        ->where('acc_header','!=','HEADER')
        ->orderBy('account')
        ->get();

        $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED'];
          
        return view("accounting.bukuBesar.index",$data);
    }
   
    public function list(Request $request)
    {
        $seachVc = strtolower($request->seachVc);
        $vcDate = $request->vcDate;
        $period1 = $request->period1;
        $period2 = $request->period2;
        $costCenter = $request->dept;
        $perkiraan1 = $request->perkiraan1;
        $perkiraan2 = $request->perkiraan2;
        $status =  $request->searchStatus;

        $adaPerkiraan = '';

        if ($perkiraan1 || $perkiraan2){
            $adaPerkiraan = "ada";
            if($perkiraan1 && !$perkiraan2){
                $perkiraan2=$perkiraan1;
            }
            if(!$perkiraan1 && $perkiraan2){
                $perkiraan1=$perkiraan2;
            }
        }        

        $fromDate = "";
        $toDate = "";
        
        if ($vcDate){
            $date = explode("to",$vcDate);
            // $fromDate = trim($date[0]);
            // $toDate = trim($date[1]);
            if(count($date)>1){
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate; 
            }
        }

        // $data = DB::table('kas_det')
        // ->leftJoin('kas_hdr','kas_hdr.voucher_number','kas_det.voucher_number')
        // ->leftJoin('accounts','accounts.account','kas_det.account')
        // ->leftJoin('depts','depts.code','kas_det.cost_center')
        // ->leftJoin(db::raw("(SELECT module_number,(select name from users where username = subq.username) as username,approval_date
        //                     FROM (SELECT module_number
        //                             ,username
        //                             ,approval_date
        //                                 ,approval_order
        //                                 ,MAX(approval_order) OVER (partition by module_number) as max_value
        //                         FROM approval_history) as subq
        //                     WHERE subq.approval_order = subq.max_value) as u"),'u.module_number','kas_hdr.voucher_number')
        // // ->leftJoin('third_party','third_party.kode','kas_hdr.paid_to')
        // ->where(function ($query) use ($vcDate,$fromDate,$toDate,$period1,$period2,$costCenter,$perkiraan1,$perkiraan2,$adaPerkiraan,$status) {
        //     $vcDate ? $query->whereBetween(DB::raw("to_date(voucher_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
        //     $period1 ? $query->whereBetween(db::raw("period::integer"), [$period1, $period2]) : '';
        //     $costCenter ? $query->whereIn('cost_center', $costCenter) : '';
        //     $adaPerkiraan ? $query->whereBetween('kas_det.account', [$perkiraan1, $perkiraan2]) : '';
        //     $status ? $query->where('kas_hdr.status',$status) : '';
        // })
        // ->whereNotIn('kas_hdr.status',['5'])
        // // ->whereIn('kas_hdr.status',['3'])
        // // ->where('kas_hdr.voucher_number','AP-ASN-2023-II-0111')
        // ->select(
        //     'depts.name as nama_dept'
        //     ,'kas_det.account'
        //     ,'accounts.description as nama_akun'
        //     ,'reference'
        //     ,'kas_det.voucher_number'
        //     ,'kas_det.description'
        //     ,'kas_hdr.voucher_date'
        //     , DB::raw("to_date(voucher_date,'DD-MM-YYYY') as voucher_date_2 ")
        //     ,'debit'
        //     ,'credit'
        //     ,'kas_hdr.status as statusku'
        //     ,'u.username as approval_by'
        //     ,db::raw("to_char(u.approval_date::date, 'DD-MM-YYYY') as approval_at")
        //     // ,db::raw("(select (select name from users where username = z.username) from approval_history z where module_number = kas_hdr.voucher_number order by approval_order desc limit 1) as approval_by")
        //     // ,db::raw("(select to_char(approval_date::date, 'DD-MM-YYYY') from approval_history z where module_number = kas_hdr.voucher_number order by approval_order desc limit 1) as approval_at")
        // )
        // // ->orderBy('kas_hdr.voucher_date')
        // ->orderBy('kas_det.account')
        // ->get(); 

        $statusBaru = '';

        $arrayPerkiraan1 = explode(".",$perkiraan1);
        $arrayPerkiraan2 = explode(".",$perkiraan2);

        $like1 = '';
        for ($x = 0; $x < count($arrayPerkiraan1)-1; $x++) {
            $like1.=$arrayPerkiraan1[$x].".";
        }

        $like2 = '';
        for ($x = 0; $x < count($arrayPerkiraan2)-1; $x++) {
            $like2.=$arrayPerkiraan2[$x].".";
        }

        if($like1 == $like2){
            $perkiraan1= str_replace('.','',$perkiraan1);
            $perkiraan2= str_replace('.','',$perkiraan2);
            $like1 = substr($like1, 0, -1);
            $adaPerkiraan = '';
            if ($perkiraan1!='' || $perkiraan2!=''){
                $statusBaru = "kas_det.account like '$like1%' and replace(kas_det.account,'.','')::numeric between '$perkiraan1' and '$perkiraan2'";
            }
        }

        // dd($adaPerkiraan);
        
        $data = DB::table('kas_det')
        ->leftJoin('kas_hdr','kas_hdr.voucher_number','kas_det.voucher_number')
        ->leftJoin('accounts','accounts.account','kas_det.account')
        ->leftJoin('depts','depts.code','kas_det.cost_center')
        ->where(function ($query) use ($vcDate,$fromDate,$toDate,$period1,$period2,$costCenter,$perkiraan1,$perkiraan2,$adaPerkiraan,$status,$statusBaru) {
            $vcDate ? $query->whereBetween(DB::raw("to_date(voucher_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
            $period1 ? $query->whereBetween(db::raw("period::integer"), [$period1, $period2]) : '';
            $costCenter ? $query->whereIn('cost_center', $costCenter) : '';
            $adaPerkiraan ? $query->whereBetween('kas_det.account', [$perkiraan1, $perkiraan2]) : '';
            $status ? $query->where('kas_hdr.status',$status) : '';
            $statusBaru ? $query->whereRaw($statusBaru) : '';
        })
        ->whereNotIn('kas_hdr.status',['5'])
        ->select(
            'depts.name as nama_dept'
            ,'kas_det.account'
            ,'kas_hdr.period'
            ,'accounts.description as nama_akun'
            ,'reference'
            ,'kas_det.voucher_number'
            ,'kas_det.description'
            ,'kas_hdr.voucher_date'
            ,DB::raw("to_date(voucher_date,'DD-MM-YYYY') as voucher_date_2")
            ,DB::raw("to_char(to_date(voucher_date, 'DD-MM-YYYY'), 'DD/MM/YYYY') as voucher_date")
            ,'debit'
            ,'credit'
            ,'kas_hdr.status as statusku'
            ,db::raw("(SELECT username FROM approval_history where module_number = kas_det.voucher_number order by approval_order desc limit 1) as approval_by")
            ,db::raw("(SELECT to_char(approval_date::date, 'DD-MM-YYYY') FROM approval_history where module_number = kas_det.voucher_number order by approval_order desc limit 1) as approval_at")
        )
        ->orderBy(db::raw("replace(kas_det.account,'.','')::numeric"))
        ->get(); 
       
        return Datatables::of($data)
        ->addColumn('statusku', function ($data) {
            $statusBb = ['NEW','VALIDATE','APPROVED','','','PAID'];
            return $statusBb[$data->statusku - 1];
        })
        ->rawColumns(['statusku'])
        ->make(true);
    }


}
