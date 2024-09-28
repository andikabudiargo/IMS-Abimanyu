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

class ForcastingSalesController extends Controller
{
    private $title;
    private $moduleCode;
    public function __construct()
    {
        $this->title = "Forecasting Sales";
        $this->moduleCode = "FCS";
    }

    public function getTableColoumn()
    {
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'Action','orderable'=> false,'searchable'=>false],
            ['data'=>'forcast_number','name'=>'forcast_number','title'=>'Nomor Forecasting'],
            ['data'=>'forcast_name','name'=>'forcast_name','title'=>'Forecasting Name'],
            ['data'=>'year','name'=>'year','title'=>'Year'],
            ['data'=>'month_start','name'=>'month_start','title'=>'Month Start'],
            ['data'=>'month_end','name'=>'month_end','title'=>'Month End'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created At']
        ];
        return json_encode($kolom, true);
    }
    public function getLastCode()
    {
         /* 
            Kode forcast yang diinginkan
            FRCST/ASN/24/VIII/0001
        */

        $getCurrentYear = date('Y');
        $getCurrentMonth = date('m');
        $key = "FRCST";
        $inputYear = $getCurrentYear;
        $basicCode = "FRCST/ASN/$inputYear";

        $getResetRule = 'YEAR';

        if($getResetRule == 'YEAR'){
            $getLastNumber = DB::table('forecasting_sales_hdr')
            ->where('forcast_number','like',$basicCode.'%')
            // ->where('status','<>','5')
            ->orderBy('id','desc')
            ->first();
        }else{
            $getLastNumber = DB::table('forecasting_sales_hdr')
            // ->where('status','<>','5')
            ->orderBy('id','desc')
            ->first();
        }       

        if ($getLastNumber){
            $getYear = explode('/',$getLastNumber->forcast_number)[2];
            $getLastCode = explode('/',$getLastNumber->forcast_number)[4];
            $newCode = ((int)$getLastCode*1)+1;
        }else{
            $getYear = $getCurrentYear;
            $newCode = 1;
        }

        $newCode = str_pad($newCode,4,"0",STR_PAD_LEFT);
        $months = ['I', 'II', 'III','IV','V', 'VI', 'VII', 'VIII','IX','X','XI','XII'];
        $month = $months[(int)$getCurrentMonth-1];
        $year = $inputYear;
        $code="$key/ASN/$year/$month/$newCode";
        
        return $code;
    }
   
    public function index(Request $request)
    {
        $data['title'] = $this->title;
        $data['kolom'] = $this->getTableColoumn();

        $data['customers'] = DB::table('third_party')
        ->where('third_party_type','cust')
        ->orderBy('nama')
        ->get();

        $data['bulan'] = ['1'=>"Januari",'2'=>"Februari",'3'=>"Maret",'4'=>"April",'5'=>"Mei",'6'=>"Juni",'7'=>"Juli",'8'=>"Agustus",'9'=>"September",'10'=>"Oktober",'11'=>"November",'12'=>"Desember"];
    
        return view("forecasting.sales.index",$data);
    }

    public function create(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create $this->title";
                
        $data['customers'] = DB::table('third_party')
        ->where('third_party_type','cust')
        ->orderBy('nama')
        ->get();

        $data['articles'] = DB::table('article')
        ->where('status','1')
        ->where('article_type','FG')
        ->orderBy('article_alternative_code','asc')
        ->get();

        $data['forcastNumber'] = "";
        $data['forcastName'] = "";
        $data['note'] = "";
        $data['year'] = "";
        $data['bulanAwal'] = "";
        $data['bulanAkhir'] = "";
        

        $data['bulan'] = ['1'=>"Januari",'2'=>"Februari",'3'=>"Maret",'4'=>"April",'5'=>"Mei",'6'=>"Juni",'7'=>"Juli",'8'=>"Agustus",'9'=>"September",'10'=>"Oktober",'11'=>"November",'12'=>"Desember"];

        return view("forecasting.sales.create",$data);

    }

    public function edit(Request $request)
    {

        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;

        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";
                
        $data['customers'] = DB::table('third_party')
        ->where('third_party_type','cust')
        ->orderBy('nama')
        ->get();

        $header = DB::table('forecasting_sales_hdr')
        ->where('id',$id)
        ->first();

        $data['articles'] = DB::table('article')
        ->where('status','1')
        ->where('article_type','FG')
        ->orderBy('article_alternative_code','asc')
        ->get();

        $data['forcastNumber'] = $header->forcast_number;
        $data['forcastName'] = $header->forcast_name;
        $data['note'] = $header->note;
        $data['year'] = $header->year;
        $data['bulanAwal'] = $header->month_start;
        $data['bulanAkhir'] = $header->month_end;

        $data['bulan'] = ['1'=>"Januari",'2'=>"Februari",'3'=>"Maret",'4'=>"April",'5'=>"Mei",'6'=>"Juni",'7'=>"Juli",'8'=>"Agustus",'9'=>"September",'10'=>"Oktober",'11'=>"November",'12'=>"Desember"];

        return view("forecasting.sales.create",$data);

    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $details = json_decode($request->details);
        $bulanAwal = $request->bulanAwal;
        $bulanAkhir = $request->bulanAkhir;
        $year = $request->year;
        $note = $request->note;
        $totalAmount= $request->totalAmount;
        $status = '1';
        $fcNumber = $request->fcNumber;
        $forcastName = $request->forcastName;
                
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
            
            DB::beginTransaction();
            try {

                if (!$fcNumber){
                    $fcNumber = $this->getLastCode();
                    DB::table('forecasting_sales_hdr')->insert([
                        'forcast_number' => $fcNumber,
                        'year' => $year,
                        'month_start' => $bulanAwal,
                        'month_end' => $bulanAkhir,
                        'forcast_name' => $forcastName,
                        'note' => $note,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
                    
                $listCode =[];
                $dataSet = [];
                foreach ($details as $val) {
                    $dataSet[] = [
                        'fc_code' => $val->fc_code.$val->article_code,
                        'customer_id' =>$val->customer_id,
                        'article_code' =>$val->article_code,
                        'qty' =>is_null($val->qty) ? 0 : preg_replace('/[^0-9.]+/', '', $val->qty),
                        'year' =>$val->year,
                        'month' =>$val->month,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'forcasting_name' => $val->forcasting_name,
                        'forcast_number' => $fcNumber
                    ];

                    $listCode[]=[$val->fc_code.$val->article_code]; 
                    // $fcNumber=$val->fc_code;
                }

                // $rowAffected = 
                DB::table('forecasting_sales')
                ->whereIn('fc_code',$listCode)
                ->where('forcast_number',$fcNumber)
                ->delete();
                // if ($rowAffected){
                DB::table('forecasting_sales')->insert($dataSet);
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

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $forcastNumber = $request->fcNumber;
        $forcastName=$request->forcastName;      
        $note = $request->note;
        
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
            'iunique' => "KM Number has already been taken",
        ];

        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });
        
        $validation = Validator::make($request->all(),$messages = [
            // 'poNumber'=>'required|unique:purchase_order_hdr,po_number',
            // 'orderDate'  => 'required',
            'forcastName'=>'required|unique:forecasting_sales_hdr,forcast_name',
            // 'supplier'  => 'required',
        ]);
        
        $error_array = array();
        $success_output = '';
        
        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }
           
            $title="Save $this->title";
            $alert ="error";
            return response()->json(array('status' => 0,'title' => $title, 'message' => $error_array,'alert' =>$alert));
        }
        
        DB::beginTransaction();
        try {
                $rowAffected=DB::table('forecasting_sales_hdr')
                ->where('forcast_number',$forcastNumber)
                ->update(
                    [   
                        'forcast_name' => $forcastName,
                        'note' => $note,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]
                );

                if($rowAffected){
                                        
                    DB::table('forecasting_sales')
                    ->where('forcast_number',$forcastNumber)
                    ->update(
                        [   
                            'forcasting_name' => $forcastName
                        ]
                    );

                    DB::commit();
                    $title ="Update $this->title";
                    $alert  ="success";
                    $message  = "$title $forcastName is successfully updated";

                    $data['title'] = $title;
                    $data['message'] = $message;
                    $data['alert'] = $alert;

                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1, 'message' => $message,'alert'=>$alert,'forcastName'=>$forcastName));
                    // return redirect()->back()->with(array('title' => $title, 'message' => $message,'alert'=>$alert,'forcastName'=>$forcastName));
                }

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Update $this->title";
            $alert  ="warning";
            $message  = "Invoice $forcastName is failed to update";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('status' => 1, 'message' => $message,'alert'=>$alert,'forcastName'=>$forcastName));
            // return redirect()->back()->with(array('title' => $title, 'message' => $message,'alert'=>$alert,'forcastName'=>$forcastName));
        }
        
    }
    
    public function destroy(Request $request)
    {
        $username =  Auth::user()->username;    
        $customerId=$request->customerId;
        $articleCode=$request->articleCode;
        $year=$request->year;
        $articleDesc=$request->articleDesc;
        $fcNumber=$request->uFcNumber;

        $rowAffected=DB::table('forecasting_sales')
        ->where('forcast_number',$fcNumber)
        ->where('customer_id',$customerId)
        ->where('article_code',$articleCode)
        ->where('year',$year)
        ->delete();

        if($rowAffected>0){
            $title ="Delete $this->title";
            $alert  ="success";
            $message  = "$fcNumber $articleDesc Successfully Deleted";
            \LogActivity::addToLog('FC Sales ',"username: $username Status $message");
            return response()->json(array('status'=>"1",'message'=>$message,'alert'=>$alert,'fcNumber'=>$fcNumber));
        }else{
            $title ="Delete $this->title";
            $alert  ="warning";
            $message  = "$fcNumber $articleDesc Failed to Delete";
            \LogActivity::addToLog('FC Sales delete ',"username: $username Status $message");
            return response()->json(array('status'=>"0",'message'=>$message,'alert'=>$alert,'fcNumber'=>$fcNumber));
        }
    }
    public function show(Request $request)
    {

        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;

        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";
                
        $data['customers'] = DB::table('third_party')
        ->where('third_party_type','cust')
        ->orderBy('nama')
        ->get();

        $header = DB::table('forecasting_sales_hdr')
        ->where('id',$id)
        ->first();

        $data['articles'] = DB::table('article')
        ->where('status','1')
        ->where('article_type','FG')
        ->orderBy('article_alternative_code','asc')
        ->get();

        $data['forcastNumber'] = $header->forcast_number;
        $data['forcastName'] = $header->forcast_name;
        $data['note'] = $header->note;
        $data['year'] = $header->year;
        $data['bulanAwal'] = $header->month_start;
        $data['bulanAkhir'] = $header->month_end;

        $data['bulan'] = ['1'=>"Januari",'2'=>"Februari",'3'=>"Maret",'4'=>"April",'5'=>"Mei",'6'=>"Juni",'7'=>"Juli",'8'=>"Agustus",'9'=>"September",'10'=>"Oktober",'11'=>"November",'12'=>"Desember"];

        return view("forecasting.sales.show",$data);

    }

    public function list(Request $request)
    {
        $bulanAwal = $request->bulanAwal;
        $bulanAkhir = $request->bulanAkhir;
        $year = $request->year;
        $customer = $request->customer;
        $forcastingName = $request->forcastName;

        $data = DB::table('forecasting_sales_hdr')
        ->where(function ($query) use ($bulanAwal,$bulanAkhir,$year,$customer,$forcastingName) {
            $year ? $query->where('year',$year) : '';
            $forcastingName ? $query->where('forcast_name','ilike','%'.$forcastingName.'%') : '';
            // $customer ?  ? $query->where('forcast_name','ilike','%'.$forcastingName.'%') : '';
            // $vcDate ? $query->whereBetween('voucher_date', [$fromDate, $toDate]) : '';
            // $period ? $query->where('period', $period) : '';
            // $year ? $query->where('year', $year) : '';
        })
        ->orderBy('id')
        ->get(); 
       
        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            
            // if (Auth::user()->can('bankKeluar-edit')) {
                // if ( $data->statusku == '2' or $data->statusku == '1') {
                $buttons .=     '<a href="'. route('forecastSales.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
                $buttons .=     '<a href="'. route('forecastSales.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                <i data-feather="list"></i>
                                Detail
                            </a>';
                // }
            // }            
            
            // if (Auth::user()->can('bankKeluar-delete')) {
            // if ($data->statusku != '5') {
            //     $buttons .=         "<a href='javascript:;'
            //                         id='deleteButton'
            //                         class='dropdown-item'
            //                         data-toggle='modal'
            //                         data-target='#smallModal'
            //                         data-href='". route("bankKeluar.destroy", ['id'=>Crypt::encryptString($data->id)]) ."'>
            //                         <i data-feather='trash-2' class='feather-14-red'></i>
            //                         Delete
            //                     </a>";
            // }

            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        // ->addColumn('statusku', function ($data) {
        //     $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-secondary'];
        //     $status = ['NEW','VALIDATED','APPROVED','','DELETED','CLOSED'];
        //     return "<div class='badge ".$badges[$data->status - 1]."'>".$status[$data->status - 1]."</div>";
        // })
        ->rawColumns(['action'])
        ->make(true);
    }

    public function getArticle(Request $request)
    {
        $customerCode = $request->customerCode;

        $data= DB::table('article') 
        ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
        ->where('third_party',$customerCode)
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
        $customerCode = $request->customerCode;
        $article = $request->article;
        $year = $request->year;
        $articleId = $request->articleId;
        $fcNumber = $request->fcNumber;

        $data= DB::table('forecasting_sales') 
        ->where('customer_id',$customerCode)
        ->where('year',$year)
        ->where('forcast_number',$fcNumber)
        ->where('article_code',$articleId)
        ->get();
        
        return response()->json(array('data'=>$data));

    }

    public function getListArticle(Request $request)
    {
        $customerCode = $request->customerCode;
        $forcastingName = $request->forcastName;
        $year = $request->year;
        $bulanAwal = $request->bulanAwal;
        $bulanAkhir = $request->bulanAkhir;
        $fcNumber = $request->fcnumber;

        $data= DB::table('forecasting_sales') 
        // ->leftJoin('third_party','third_party.code','=','forecasting_sales.customer_id')
        // ->where('customer_id',$customerCode)
        ->where('forcast_number',$fcNumber)
        ->where('year',$year)
        ->get();
        
        $namaBulan="";
        $conversi = ['satu','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan','sepuluh','sebelas','duabelas'];

        if($bulanAwal&&$bulanAkhir&&$year){
            for ($i=$bulanAwal;$i<=$bulanAkhir;$i++){
                $namaBulan.="sum(case when month = '$i' then qty end) as $conversi[$i],";
            }

            $namaBulan=substr($namaBulan ,0,-1);

            // $filter = $customerCode ? "and a.customer_id = '$customerCode'" :'';
            // $filter = $forcastingName ? "and a.forcasting_name = '$forcastingName'" :'';
            $filter = $forcastingName ? "and a.forcast_number = '$fcNumber'" :'';
            

            $data = db::select("SELECT a.forcasting_name,a.customer_id,c.nama,c.nama,a.article_code,b.article_alternative_code,b.article_desc,a.year,
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
            from forecasting_sales a 
            left join article b on b.article_code = a.article_code
            left join third_party c on a.customer_id = c.kode
            where a.year = '$year'
            $filter
            -- and a.customer_id = '$customerCode'
            group by a.forcasting_name,a.customer_id,c.nama, a.article_code,b.article_desc,b.article_alternative_code,a.year,c.nama
            order by article_alternative_code");
        }
        return response()->json(array('data'=>$data));
    }

    public function getSelectArticle(Request $request)
    {
        $searchTerm = $request->q;
        if (empty($searchTerm)) {
            return response()->json([]);
        }

        $data= DB::table('article') 
        ->where('article_desc','ilike','%'.$searchTerm.'%')
        ->where('article_type','FG')
        ->get();

        $formattedArts = [];
        foreach ($data as $art) {
            $formattedArts[] = ['id' => $art->article_code,'alt' => $art->article_alternative_code,'articleDesc' => $art->article_desc,'customer'=>$art->third_party,'articleCode'=>$art->article_code];
        }
        return response()->json($formattedArts);
    }

    

}
