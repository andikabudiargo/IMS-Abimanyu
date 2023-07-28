<?php

namespace App\Http\Controllers\Forecasting;

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

class ForcastingPurchaseController extends Controller
{
    private $title;
    private $moduleCode;
    public function __construct()
    {
        $this->title = "Forcasting Purchase";
        $this->moduleCode = "FCP";
    }

    public function getTableColoumn()
    {
        $kolom=
        [
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created At']
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

        $data['suppliers'] = DB::table('third_party')
        ->where('third_party_type','supp')
        ->orderBy('nama')
        ->get();

        $data['bulan'] = ['1'=>"Januari",'2'=>"Februari",'3'=>"Maret",'4'=>"April",'5'=>"Mei",'6'=>"Juni",'7'=>"Juli",'8'=>"Agustus",'9'=>"September",'10'=>"Oktober",'11'=>"November",'12'=>"Desember"];
    
        return view("forecasting.purchasing.index",$data);
    }

    public function create(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create $this->title";
                
        $data['suppliers'] = DB::table('third_party')
        ->where('third_party_type','supp')
        ->orderBy('nama')
        ->get();

        $data['bulan'] = ['1'=>"Januari",'2'=>"Februari",'3'=>"Maret",'4'=>"April",'5'=>"Mei",'6'=>"Juni",'7'=>"Juli",'8'=>"Agustus",'9'=>"September",'10'=>"Oktober",'11'=>"November",'12'=>"Desember"];

        return view("forecasting.purchasing.create",$data);

    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $details = json_decode($request->details);
        $vcDate = $request->vcDate;
        $period = $request->period;
        $note = $request->note;
        $totalAmount= $request->totalAmount;
        $paidTo = $request->paidTo;
        $status = '1';
        $leadCode =$this->moduleCode;
        $paidToDesc = $request->paidToDesc;

        // dd($details);
        
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
            // 'iunique' => "KM Number has already been taken",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $validation = Validator::make($request->all(),$messages = [
            // 'poNumber'=>'required|unique:purchase_order_hdr,po_number',
            // 'pcNumber'  => 'required',
            // 'period'  => 'required'
        ]);
        
        $error_array = array();
        $success_output = '';
        // return $validation;
        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }
            $title="Save $this->title";
            $alert ="error";
            return response()->json(array('status' => 0,'title' => $title, 'message' => $error_array,'alert' =>$alert));
        }else{
            $hasilUpdate = AppHelpers::resetCode($leadCode);
            $vcNumber = $this->getLastCode($leadCode);
            DB::beginTransaction();
            try {
                    // DB::table('forecasting_purchase_hdr')->insert([
                    //     'created_by' => Auth::user()->username,
                    //     'updated_by' => Auth::user()->username,
                    //     'created_at' => date('Y-m-d H:i:s'),
                    //     'updated_at' => date('Y-m-d H:i:s')
                    // ]);
                    
                    $listCode =[];
                    $dataSet = [];
                    foreach ($details as $val) {
                        $dataSet[] = [
                            'fc_code' => $val->fc_code.$val->article_code,
                            'supplier_id' =>$val->supplier_id,
                            'article_code' =>$val->article_code,
                            'qty' =>is_null($val->qty) ? 0 : preg_replace('/[^0-9.]+/', '', $val->qty),
                            'year' =>$val->year,
                            'month' =>$val->month,
                            'created_by' => Auth::user()->username,
                            'updated_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];

                        $listCode[]=[$val->fc_code.$val->article_code]; 
                        $fcNumber=$val->fc_code;
                    }

                    // $rowAffected = 
                    DB::table('forecasting_purchase')->whereIn('fc_code',$listCode)->delete();

                    // if ($rowAffected){
                        DB::table('forecasting_purchase')->insert($dataSet);
                    // }

                    DB::commit();
                    $title ="Save $this->title";
                    $alert  ="success";
                    $message  = "$title $fcNumber is successfully saved";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'fcNumber'=>$fcNumber));
            } catch (Exception $e) {
                DB::rollBack();
                $title ="Save $this->title";
                $alert  ="warning";
                $message  = "$fcNumber is failed to save";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 0,'title' => $title, 'message' => $message,'alert'=>$alert,'fcNumber'=>$fcNumber));
            }
        }
    }

    public function destroy(Request $request)
    {
        $username =  Auth::user()->username;    
        $supplierId=$request->supplierId;
        $articleCode=$request->articleCode;
        $year=$request->year;
        $articleDesc=$request->articleDesc;

        $rowAffected=DB::table('forecasting_purchase')
        ->where('supplier_id',$supplierId)
        ->where('article_code',$articleCode)
        ->where('year',$year)
        ->delete();

        if($rowAffected>0){
            $title ="Delete $this->title";
            $alert  ="success";
            $message  = "$articleDesc Successfully Deleted";
            \LogActivity::addToLog('FC Sales ',"username: $username Status $message");
            return response()->json(array('status'=>"1",'message'=>$message,'alert'=>$alert));
        }else{
            $title ="Delete $this->title";
            $alert  ="warning";
            $message  = "$articleDesc Failed to Delete";
            \LogActivity::addToLog('FC Sales delete ',"username: $username Status $message");
            return response()->json(array('status'=>"0",'message'=>$message,'alert'=>$alert));
        }
    }

    public function print(Request $request)
    {
        $id=Crypt::decryptString($request->id);

        $data['title'] ='Kas Keluar';
        
        $data['header'] = DB::table('kas_hdr')
        // ->leftJoin('third_party','third_party.kode','kas_hdr.paid_to')
        ->select('kas_hdr.*'
        ,'description as supplier_name'
        // ,db::raw("concat(third_party.kode,'-',third_party.nama) as supplier_name")
        )
        ->where('kas_hdr.id',$id)
        ->get()->first();


        $vcNumber=$data['header']->voucher_number;
       
        $data['details']=DB::table('kas_det')
        ->leftJoin('accounts','accounts.account','kas_det.account')
        ->select('kas_det.*','accounts.description as account_name')
        ->where('voucher_number',$vcNumber)
        ->get();

        $data['total']=DB::table('kas_det')
        ->select(DB::raw("sum(credit) as total_credit"),DB::raw("sum(debit) as total_debit"))
        ->where('voucher_number',$vcNumber)
        ->first();

        $data['costCenter']=DB::table('kas_det')
        ->leftJoin('depts','depts.code','kas_det.cost_center')
        ->where('voucher_number',$vcNumber)
        ->distinct('depts.name')
        ->pluck('depts.name')->implode(',');

        $data['approval1']=DB::table('approval_history')
        ->leftJoin('users','users.username','approval_history.username')
        ->where('module_code',$this->moduleCode)
        ->where('module_number',$vcNumber)
        ->where('approval_order',1)
        ->first();

        $data['approval2']=DB::table('approval_history')
        ->leftJoin('users','users.username','approval_history.username')
        ->where('module_code',$this->moduleCode)
        ->where('module_number',$vcNumber)
        ->where('approval_order',2)
        ->first();

        $data['approval3']=DB::table('approval_history')
        ->leftJoin('users','users.username','approval_history.username')
        ->where('module_code',$this->moduleCode)
        ->where('module_number',$vcNumber)
        ->where('approval_order',3)
        ->first();

        return view('forecasting.purchasing.print',$data);

        // view()->share($data);

        // $pdf = PDF::loadView('forecasting.purchasing.print');
        // return $pdf->stream("$vcNumber.pdf");

    }

    public function getArticle(Request $request)
    {
        $supplierCode = $request->supplierCode;

        $data= DB::table('article') 
        ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
        ->where('third_party',$supplierCode)
        ->orderBy('article.article_desc')
        ->distinct('article.article_desc')
        ->select('article.*'
        ,'article.article_alternative_code'
        ,'article.article_code as artikel_code'
        ,'article.article_desc'
        ,'article.costprice'
        ,'article.uom as uom1'
        ,'group_materials.name as group')
        ->get();

        $output='';
        $output .='<option value="">Choose Article</option>';

        foreach ($data as $row){
            $output .='<option value="'.$row->article_code.'"  data-detail="'.$row->article_code.'">'.$row->article_alternative_code.' - '. $row->article_desc.'</option>';
        }

        return $output;

    }

    public function getQtyArticle(Request $request)
    {
        $supplierCode = $request->supplierCode;
        $article = $request->article;
        $year = $request->year;
        $articleId = $request->articleId;

        $data= DB::table('forecasting_purchase') 
        ->where('supplier_id',$supplierCode)
        ->where('year',$year)
        ->where('article_code',$articleId)
        ->get();
        
        return response()->json(array('data'=>$data));

    }

    public function getListArticle(Request $request)
    {
        $supplierCode = $request->supplierCode;
        $year = $request->year;
        $bulanAwal = $request->bulanAwal;
        $bulanAkhir = $request->bulanAkhir;

        $data= DB::table('forecasting_purchase') 
        ->where('supplier_id',$supplierCode)
        ->where('year',$year)
        ->get();

        $namaBulan="";
        $conversi = ['satu','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan','sepuluh','sebelas','duabelas'];
        if($bulanAwal&&$bulanAkhir&&$year){
            for ($i=$bulanAwal;$i<=$bulanAkhir;$i++){
                $namaBulan.="sum(case when month = '$i' then qty end) as $conversi[$i],";
            }
        

            $namaBulan=substr($namaBulan ,0,-1);

            $filter = $supplierCode ? "and a.supplier_id = '$supplierCode'" :'';

            $data = db::select("SELECT a.supplier_id,c.nama,a.article_code,b.article_alternative_code,b.article_desc,a.year,
            $namaBulan
            -- sum(case when month = '1' then qty end) as satu,
            -- sum(case when month = '2' then qty end) as dua,
            -- sum(case when month = '3' then qty end) as tiga,
            -- sum(case when month = '4' then qty end) as empat,
            -- sum(case when month = '5' then qty end) as lima,
            -- sum(case when month = '6' then qty end) as enam,
            -- sum(case when month = '7' then qty end) as tujuh,
            -- sum(case when month = '8' then qty end) as delapan,
            -- sum(case when month = '9' then qty end) as sembilan,
            -- sum(case when month = '10' then qty end) as sepuluh,
            -- sum(case when month = '11' then qty end) as sebelas,
            -- sum(case when month = '12' then qty end) as duabelas
            from forecasting_purchase a 
            left join article b on b.article_code = a.article_code
            left join third_party c on a.supplier_id = c.kode
            where a.year = '$year'
            $filter
            -- and a.supplier_id = '$supplierCode'
            group by a.supplier_id, a.article_code,b.article_desc,b.article_alternative_code,a.year,c.nama
            order by article_alternative_code");
        }

        return response()->json(array('data'=>$data));

    }

}
