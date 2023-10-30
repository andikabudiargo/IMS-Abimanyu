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
            ['data'=>'debit','name'=>'debit','title'=>'Debet'],
            ['data'=>'credit','name'=>'credit','title'=>'Kredit']
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
          
        return view("accounting.bukuBesar.index",$data);
    }
   
    public function list(Request $request)
    {
        $seachVc = strtolower($request->seachVc);
        $vcDate = $request->vcDate;
        $period1 = $request->period1;
        $period2 = $request->period2;
        $costCenter = $request->dept;
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

        $data = DB::table('kas_det')
        ->leftJoin('kas_hdr','kas_hdr.voucher_number','kas_det.voucher_number')
        ->leftJoin('accounts','accounts.account','kas_det.account')
        ->leftJoin('depts','depts.code','kas_det.cost_center')
        // ->leftJoin('third_party','third_party.kode','kas_hdr.paid_to')
        ->where(function ($query) use ($vcDate,$fromDate,$toDate,$period1,$period2,$costCenter) {
            $vcDate ? $query->whereBetween(DB::raw("to_date(voucher_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
            $period1 ? $query->whereBetween('period', [$period1, $period2]) : '';
            $costCenter ? $query->whereIn('cost_center', $costCenter) : '';
        })
        ->whereNOtIn('kas_hdr.status',['4','5'])
        ->select(
            'depts.name as nama_dept'
            ,'kas_det.account'
            ,'accounts.description as nama_akun'
            ,'reference'
            ,'kas_det.voucher_number'
            ,'kas_det.description'
            ,'kas_hdr.voucher_date'
            ,'debit'
            ,'credit'
        )
        // ->orderBy('kas_hdr.voucher_date')
        ->orderBy('kas_det.account')
        ->get(); 
       
        return Datatables::of($data)
        
        ->make(true);
    }


}
