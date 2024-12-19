<?php

namespace App\Http\Controllers\Conversion;

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

class ConversionController extends Controller
{
    private $title;
    private $moduleCode;
    private $convertionValue;
    public function __construct()
    {
        $this->title = "Conversion";
        $this->moduleCode = "CON";
        $this->convertionValue = db::table('conversion_setting')->orderBy('created_at', 'desc')->value('conversion_value');
    }

    public function getTableColoumn()
    {
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'Action','orderable'=> false,'searchable'=>false],
            ['data'=>'conversion_code','name'=>'conversion_code','title'=>'Conversion Number'],
            ['data'=>'conversion_name','name'=>'conversion_name','title'=>'Conversion Name'],
            ['data'=>'convertion_date','name'=>'convertion_date','title'=>'Date'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created At']
        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnDetail()
    {
        $kolom=
        [
            ['data'=>'conversion_code','name'=>'conversion_code','title'=>'Conversion Number'],
            ['data'=>'conversion_name','name'=>'conversion_name','title'=>'Conversion Name'],
            ['data'=>'dn_number','name'=>'dn_number','title'=>'DN Number'],
            ['data'=>'customer_name','name'=>'customer_name','title'=>'Customer'],
            ['data'=>'article_alternative_code','name'=>'article_alternative_code','title'=>'Article Code'],
            ['data'=>'article_desc','name'=>'article_desc','title'=>'Article Desc'],
            ['data'=>'conversion_total','name'=>'conversion_total','title'=>'Conversion price'],
            ['data'=>'delivery_date','name'=>'delivery_date','title'=>'Delivery Date'],
            // ['data'=>'note','name'=>'note','title'=>'Note'],
            // ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created At','visible'=>false]
        ];
        return json_encode($kolom, true);
    }

    public function getLastCode()
    {
         /* 
            Kode conversi yang diinginkan
            CON/ASN/24/10/0001
        */

        $getCurrentYear = date('Y');
        $getCurrentMonth = date('m');
        $key = "CON";
        $inputYear = $getCurrentYear;
        $basicCode = "$key/ASN/$inputYear";

        $getResetRule = 'YEAR';

        if($getResetRule == 'YEAR'){
            $getLastNumber = DB::table('conversion_hdr')
            ->where('conversion_code','like',$basicCode.'%')
            // ->where('status','<>','5')
            ->orderBy('id','desc')
            ->first();
        }else{
            $getLastNumber = DB::table('conversion_hdr')
            // ->where('status','<>','5')
            ->orderBy('id','desc')
            ->first();
        }       

        if ($getLastNumber){
            $getYear = explode('/',$getLastNumber->conversion_code)[2];
            $getLastCode = explode('/',$getLastNumber->conversion_code)[4];
            $newCode = ((int)$getLastCode*1)+1;
        }else{
            $getYear = $getCurrentYear;
            $newCode = 1;
        }

        $newCode = str_pad($newCode,4,"0",STR_PAD_LEFT);
        // $months = ['I', 'II', 'III','IV','V', 'VI', 'VII', 'VIII','IX','X','XI','XII'];
        $months = ['01', '02', '03','04','05', '06', '07', '08','09','10','11','12'];
        $month = $months[(int)$getCurrentMonth-1];
        $year = $inputYear;
        $code="$key/ASN/$year/$month/$newCode";
        
        return $code;
    }
       
    public function index(Request $request)
    {
        $data['title'] = $this->title;
        $data['kolom'] = $this->getTableColoumn();
        $data['kolomDetail'] = $this->getTableColoumnDetail();

        $data['customers'] = DB::table('third_party')
        ->where('third_party_type','cust')
        ->orderBy('nama')
        ->get();

        $data['conversionVal'] = $this->convertionValue;

        return view("conversion.conversion.index",$data);
    }

    public function create(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create $this->title";
                
        $data['customers'] = DB::table('third_party')
        ->where('third_party_type','cust')
        ->orderBy('nama')
        ->get();       

        $data['conversionVal'] = $this->convertionValue;

        return view("conversion.conversion.create",$data);

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

        $data['header'] = DB::table('conversion_hdr')
        ->where('id',$id)
        ->first();

        // dd($data['header']);

        $data['details'] = DB::table('conversion_det')
        ->leftJoin('delivery_hdr','delivery_hdr.delivery_number','=','conversion_det.dn_number')
        ->leftJoin('article','article.article_code','=','conversion_det.article_code')
        ->leftJoin('third_party','third_party.kode','=','conversion_det.customer_id')
        ->where('conversion_code',$data['header']->conversion_code)
        ->select(DB::raw("concat(article.article_alternative_code,' - ',article.article_desc) as article_description")
        ,'article.article_code as artikel_code'
        ,'article.article_desc'
        ,'delivery_hdr.delivery_number'
        ,'delivery_hdr.customer_id'
        ,'third_party.nama as customer_name'
        ,'conversion_det.purchase_price'
        ,'conversion_det.selling_price'
        ,'conversion_det.conversion'
        ,'conversion_det.conversion_total'
        )
        ->get();

        $data['conversionVal'] = $data['details'][0]->conversion;

        return view("conversion.conversion.edit",$data);

    }
    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $conversionNumber = $request->cNumber;
        $conversionName = $request->cName;
        // $customerCode = $request->customerCode;
        $note = $request->cNote;
        $cValue = is_null($request->cValue) ? 0 : preg_replace('/[^0-9.]+/', '', $request->cValue);
        $details = json_decode($request->details);
        $status = '1';

        $todayDate = date('d-m-Y');
        // $todayDate = $todayDate->format("d-m-Y");
                    
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

                if (!$conversionNumber){
                    $conversionNumber = $this->getLastCode();
                    DB::table('conversion_hdr')->insert([
                        'conversion_code' => $conversionNumber,
                        'origin_conversion_code' => $conversionNumber,
                        'conversion_name' => $conversionName,
                        'convertion_date' => $todayDate,
                        'status' => $status,
                        'note' => $note,
                        // 'num_revision' => ,
                        // 'revised_by' => ,
                        // 'revised_at' => ,
                        // 'reason text' => ,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                    $dataSet = [];
                    foreach ($details as $val) {
                        $dataSet[] = [
                            'conversion_code' => $conversionNumber,
                            'dn_number' => $val->dn_number,
                            'customer_id' => $val->customer_id,
                            'article_code' => $val->article_code,
                            'purchase_price' => $val->purchase_price,
                            'selling_price' => $val->selling_price,
                            'conversion' => $val->conversion,
                            'conversion_total' => $val->conversion_total,
                            'created_by' => Auth::user()->username,
                            'updated_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ];
                    }
                    DB::table('conversion_det')->insert($dataSet);
                }else{
                    DB::table('conversion_hdr')
                    ->where('conversion_code',$conversionNumber)
                    ->update([
                        'conversion_name' => $conversionName,
                        'note' => $note,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                
                    $dataSet = [];
                    foreach ($details as $val) {
                        $dataSet[] = [
                            'conversion_code' => $conversionNumber,
                            'dn_number' => $val->dn_number,
                            'customer_id' => $val->customer_id,
                            'article_code' => $val->article_code,
                            'purchase_price' => $val->purchase_price,
                            'selling_price' => $val->selling_price,
                            'conversion' => $val->conversion,
                            'conversion_total' => $val->conversion_total,
                            'created_by' => Auth::user()->username,
                            'updated_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ];
                    }

                    if($dataSet){
                        DB::table('conversion_det')->where('conversion_code',$conversionNumber)->delete();
                        DB::table('conversion_det')->insert($dataSet);
                    }
                }
                    
                DB::commit();
                $title ="Save $this->title";
                $alert  ="success";
                $message  = "$title $conversionNumber is successfully saved";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'conversionNumber'=>$conversionNumber));
            } catch (Exception $e) {
                DB::rollBack();
                $title ="Save $this->title";
                $alert  ="warning";
                $message  = "$conversionNumber is failed to save";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 0,'title' => $title, 'message' => $message,'alert'=>$alert,'conversionNumber'=>$conversionNumber));
            }
        }
    }
    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $forcastNumber = $request->conversionNumber;
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
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;    

        $conversionNumber = DB::table('conversion_hdr')
        ->where('id',$id)
        ->value('conversion_code');
        
        $rowAffected=DB::table('conversion_hdr')
        ->where('id',$id)
        ->delete();

        if($rowAffected>0){
            DB::table('conversion_det')
            ->where('conversion_code',$conversionNumber)
            ->delete();

            $title ="Delete $this->title";
            $alert  ="success";
            $message  = "$conversionNumber Successfully Deleted";
            \LogActivity::addToLog("Conversion Sales","username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
            // return response()->json(array('status'=>"1",'message'=>$message,'alert'=>$alert,'conversionNumber'=>$conversionNumber));
        }else{
            $title ="Delete $this->title";
            $alert  ="warning";
            $message  = "$conversionNumber Failed to Delete";
            \LogActivity::addToLog("Conversion Sales","username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
            // return response()->json(array('status'=>"0",'message'=>$message,'alert'=>$alert,'conversionNumber'=>$conversionNumber));
        }
    }
    public function show(Request $request)
    {

        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;

        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";
                
        $data['customers'] = DB::table('third_party')
        ->where('third_party_type','cust')
        ->orderBy('nama')
        ->get();

        $data['header'] = DB::table('conversion_hdr')
        ->where('id',$id)
        ->first();

        // dd($data['header']);

        $data['details'] = DB::table('conversion_det')
        ->leftJoin('delivery_hdr','delivery_hdr.delivery_number','=','conversion_det.dn_number')
        ->leftJoin('article','article.article_code','=','conversion_det.article_code')
        ->leftJoin('third_party','third_party.kode','=','conversion_det.customer_id')
        ->where('conversion_code',$data['header']->conversion_code)
        ->select(DB::raw("concat(article.article_alternative_code,' - ',article.article_desc) as article_description")
        ,'article.article_code as artikel_code'
        ,'article.article_desc'
        ,'delivery_hdr.delivery_number'
        ,'delivery_hdr.customer_id'
        ,'third_party.nama as customer_name'
        ,'conversion_det.purchase_price'
        ,'conversion_det.selling_price'
        ,'conversion_det.conversion'
        ,'conversion_det.conversion_total'
        )
        ->get();

        $data['conversionVal'] = $data['details'][0]->conversion;

        return view("conversion.conversion.show",$data);

    }
    public function list(Request $request)
    {
        $customer = $request->customer;
        $conversionName = $request->conversionName;
        $deliveryDate = $request->deliveryDate;
        $conversionNumber = $request->conversionNumber;
        $fromDate = "";
        $toDate = "";

        if ($deliveryDate){
            $date = explode("to",$deliveryDate);
            // $fromDate = trim($date[0]);
            // $toDate = trim($date[1]);

            if(count($date)>1){
                
                $fromDate = trim($date[0]);
                $toDate = trim($date[1]);

                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate; 
            }
        }

        $data = DB::table('conversion_hdr')
        ->where(function ($query) use ($customer, $conversionName, $deliveryDate, $conversionNumber,$fromDate,$toDate) {
            $customer ? $query->whereIn("conversion_code", function($query) use ($customer) {
                $query->select("conversion_code")
                ->from('conversion_det')
                ->Where('customer_id','=',$customer);
            }) : '';
            $deliveryDate ? $query->whereIn("conversion_code", function($query) use ($deliveryDate,$fromDate,$toDate) {
                $query->select("conversion_code")
                ->from('conversion_det')
                    ->wherein('dn_number', function($query1) use ($deliveryDate,$fromDate,$toDate) {
                    $query1->select('delivery_number')
                    ->from('delivery_hdr')
                    ->whereBetween(DB::raw("to_date(delivery_date,'DD-MM-YYYY')"), [$fromDate, $toDate]);
                    // ->Where(DB::raw("to_date(delivery_date,'DD-MM-YYYY')"),'=',$deliveryDate);
                });
            }) : '';
            $conversionName ? $query->where('conversion_name','ilike','%'.$conversionName.'%') : '';
            $conversionNumber ? $query->where('conversion_code','ilike','%'.$conversionNumber.'%') : '';
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
            
            // if (Auth::user()->can('conversion-edit')) {
                // if ( $data->statusku == '2' or $data->statusku == '1') {
                $buttons .=     '<a href="'. route('conversion.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
                $buttons .=     '<a href="'. route('conversion.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                <i data-feather="list"></i>
                                Detail
                            </a>';
                // }
            // }            
            
            // if (Auth::user()->can('conversion-delete')) {
                // if ($data->statusku != '5') {
                    $buttons .=  "<a href='javascript:;'
                                        id='deleteButton'
                                        class='dropdown-item'
                                        data-toggle='modal'
                                        data-target='#smallModal'
                                        data-href='". route("conversion.destroy", ['id'=>Crypt::encryptString($data->id)]) ."'>
                                        <i data-feather='trash-2' class='feather-14-red'></i>
                                        Delete
                                    </a>";
                // }
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

    public function listDetail(Request $request)
    {
        $customer = $request->customer;
        $conversionName = $request->conversionName;
        $deliveryDate = $request->deliveryDate;
        $conversionNumber = $request->conversionNumber;

        $fromDate = "";
        $toDate = "";

        if ($deliveryDate){
            $date = explode("to",$deliveryDate);
            $fromDate = trim($date[0]);
            $toDate = trim($date[1]);

            if(count($date)>1){
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate; 
            }
        }

        $data = DB::table('conversion_det')
        ->leftJoin('conversion_hdr','conversion_hdr.conversion_code','=','conversion_det.conversion_code')
        ->leftJoin('third_party','kode','=','conversion_det.customer_id')
        ->leftJoin('delivery_hdr','delivery_hdr.delivery_number','=','conversion_det.dn_number')
        ->leftJoin('article','article.article_code','=','conversion_det.article_code')
        ->select('conversion_det.*'
        ,'conversion_hdr.conversion_name'
        ,'third_party.nama as customer_name'
        ,'article.article_alternative_code'
        ,'article.article_desc'
        ,'delivery_hdr.delivery_date'
        ,'delivery_hdr.note'
        )
        ->where(function ($query) use ($customer, $conversionName, $deliveryDate, $conversionNumber,$fromDate,$toDate) {
            $customer ? $query->where("conversion_code",'=',$customer) : '';
            $deliveryDate ? $query->wherein('dn_number', function($query) use ($deliveryDate,$fromDate,$toDate) {
                    $query->select('delivery_number')
                    ->from('delivery_hdr')
                    ->whereBetween(DB::raw("to_date(delivery_date,'DD-MM-YYYY')"), [$fromDate, $toDate]);
                    // ->Where(DB::raw("to_date(delivery_date,'DD-MM-YYYY')"),'=',$deliveryDate);
                }) : '';
            $conversionName ? $query->where('conversion_hdr.conversion_name','ilike','%'.$conversionName.'%') : '';
            $conversionNumber ? $query->where('conversion_det.conversion_code','ilike','%'.$conversionNumber.'%') : '';
        })
        ->orderBy('conversion_det.id')
        ->get();
       
        return Datatables::of($data)
        ->make(true);
    }

    public function getDn(Request $request)
    {
        $customerCode = $request->customerCode;
        $deliveryDate = $request->deliveryDate;
        // $date = strtotime($deliveryDate);
        // $deliveryDate = date('Y-m-d', $date);
        $deliveryDate = $request->deliveryDate;

        $fromDate = "";
        $toDate = "";

        if ($deliveryDate){
            $date = explode("to",$deliveryDate);
        
            if(count($date)>1){
                $fromDate = trim($date[0]);
                $toDate = trim($date[1]);

                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate; 
            }
        }

        $result = DB::select("select delivery_number from delivery_det where
        delivery_number in (select dn_number from conversion_det)
        and concat(delivery_number,article_code) not in (select concat(dn_number,article_code) from conversion_det)");
        $masihBisaDipanggil = [];
        foreach($result as $row){
            $masihBisaDipanggil[] = $row->delivery_number;
        }
        // dd($masihBisaDipanggil);
        $data= DB::table('delivery_hdr') 
        ->where(function ($query) use ($customerCode) {
            $customerCode ? $query->where('customer_id',$customerCode) : '';
        })
        ->whereNotIn("delivery_number", function($query)  use ($masihBisaDipanggil) {
            $query->select("dn_number")
            ->from('conversion_det')
            ->whereNotIn('dn_number',$masihBisaDipanggil);
            // ->orWhere('ap_number','<>',$apNumber);
        })
        // ->where(db::raw("to_date(delivery_date,'dd-mm-yyyy')"),$deliveryDate)
        ->whereBetween(DB::raw("to_date(delivery_date,'DD-MM-YYYY')"), [$fromDate, $toDate])
        ->whereIn('status',['4','8'])
        ->orderBy('delivery_number','asc')
        ->select('delivery_number','customer_id')
        ->get();

        $output='';
        if($data->count() == 0){
            $output .='<option value="">DN Not Found</option>';
        }else{
            $output .='<option value="">Choose DN</option>';
        }

        foreach ($data as $row){
            $output .='<option value="'.$row->delivery_number.'" >'.$row->customer_id.' - '.$row->delivery_number.'</option>';
        }

        return $output;

    }

    public function getListArticle(Request $request)
    {
        $dnNumber = $request->dnNumber;
        $data= DB::table('delivery_det') 
            ->leftJoin('delivery_hdr','delivery_hdr.delivery_number','=','delivery_det.delivery_number')
            ->leftJoin('article','article.article_code','=','delivery_det.article_code')
            ->leftJoin('third_party','third_party.kode','=','delivery_hdr.customer_id')
            ->where('delivery_det.delivery_number','=',$dnNumber)
            ->whereNotIn("delivery_det.article_code", function($query) use ($dnNumber) {
                $query->select("article_code")
                ->from('conversion_det')
                ->where('dn_number',$dnNumber);
            })
            ->orderBy('article.article_desc')
            ->select(DB::raw("concat(article.article_alternative_code,' - ',article.article_desc) as article_description")
            ,'article.article_code as artikel_code'
            ,'article.article_desc'
            ,'delivery_det.delivery_number'
            ,'delivery_hdr.customer_id'
            ,'third_party.nama as customer_name')
            ->get();

        return response()->json(array('data' => $data));

    }

    // public function getArticle(Request $request)
    // {
    //     $customerCode = $request->customerCode;

    //     $data= DB::table('article') 
    //     ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
    //     ->where('third_party',$customerCode)
    //     ->orderBy('article.article_desc')
    //     ->distinct('article.article_desc')
    //     ->select('article.*'
    //     ,'article.article_alternative_code'
    //     ,'article.article_code as artikel_code'
    //     ,'article.article_desc'
    //     ,'article.costprice'
    //     ,'article.uom as uom1'
    //     ,'group_materials.name as group')
    //     ->get();

    //     $output='';
    //     $output .='<option value="">Choose Article</option>';

    //     foreach ($data as $row){
    //         $output .='<option value="'.$row->article_code.'"  data-detail="'.$row->article_code.'">'.$row->article_alternative_code.' - '. $row->article_desc.'</option>';
    //     }

    //     return $output;

    // }

    // public function getQtyArticle(Request $request)
    // {
    //     $customerCode = $request->customerCode;
    //     $article = $request->article;
    //     $year = $request->year;
    //     $articleId = $request->articleId;
    //     $conversionNumber = $request->conversionNumber;

    //     $data= DB::table('forecasting_sales') 
    //     ->where('customer_id',$customerCode)
    //     ->where('year',$year)
    //     ->where('forcast_number',$conversionNumber)
    //     ->where('article_code',$articleId)
    //     ->get();
        
    //     return response()->json(array('data'=>$data));

    // }

    // public function getListArticle(Request $request)
    // {
    //     $customerCode = $request->customerCode;
    //     $forcastingName = $request->forcastName;
    //     $year = $request->year;
    //     $bulanAwal = $request->bulanAwal;
    //     $bulanAkhir = $request->bulanAkhir;
    //     $conversionNumber = $request->conversionnumber;

    //     $data= DB::table('forecasting_sales') 
    //     // ->leftJoin('third_party','third_party.code','=','forecasting_sales.customer_id')
    //     // ->where('customer_id',$customerCode)
    //     ->where('forcast_number',$conversionNumber)
    //     ->where('year',$year)
    //     ->get();
        
    //     $namaBulan="";
    //     $conversi = ['satu','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan','sepuluh','sebelas','duabelas'];

    //     if($bulanAwal&&$bulanAkhir&&$year){
    //         for ($i=$bulanAwal;$i<=$bulanAkhir;$i++){
    //             $namaBulan.="sum(case when month = '$i' then qty end) as $conversi[$i],";
    //         }

    //         $namaBulan=substr($namaBulan ,0,-1);

    //         // $filter = $customerCode ? "and a.customer_id = '$customerCode'" :'';
    //         // $filter = $forcastingName ? "and a.forcasting_name = '$forcastingName'" :'';
    //         $filter = $forcastingName ? "and a.forcast_number = '$conversionNumber'" :'';
            

    //         $data = db::select("SELECT a.forcasting_name,a.customer_id,c.nama,c.nama,a.article_code,b.article_alternative_code,b.article_desc,a.year,
    //         $namaBulan
    //         -- sum(case when month = '1' then qty end) as satu,
    //         -- sum(case when month = '2' then qty end) as dua,
    //         -- sum(case when month = '3' then qty end) as tiga,
    //         -- sum(case when month = '4' then qty end) as empat,
    //         -- sum(case when month = '5' then qty end) as lima,
    //         -- sum(case when month = '6' then qty end) as enam,
    //         -- sum(case when month = '7' then qty end) as tujuh,
    //         -- sum(case when month = '8' then qty end) as delapan,
    //         -- sum(case when month = '9' then qty end) as sembilan,
    //         -- sum(case when month = '10' then qty end) as sepuluh,
    //         -- sum(case when month = '11' then qty end) as sebelas,
    //         -- sum(case when month = '12' then qty end) as duabelas
    //         from forecasting_sales a 
    //         left join article b on b.article_code = a.article_code
    //         left join third_party c on a.customer_id = c.kode
    //         where a.year = '$year'
    //         $filter
    //         -- and a.customer_id = '$customerCode'
    //         group by a.forcasting_name,a.customer_id,c.nama, a.article_code,b.article_desc,b.article_alternative_code,a.year,c.nama
    //         order by article_alternative_code");
    //     }
    //     return response()->json(array('data'=>$data));
    // }

    // public function getSelectArticle(Request $request)
    // {
    //     $searchTerm = $request->q;
    //     if (empty($searchTerm)) {
    //         return response()->json([]);
    //     }

    //     $data= DB::table('article') 
    //     ->where('article_desc','ilike','%'.$searchTerm.'%')
    //     ->where('article_type','FG')
    //     ->get();

    //     $formattedArts = [];
    //     foreach ($data as $art) {
    //         $formattedArts[] = ['id' => $art->article_code,'alt' => $art->article_alternative_code,'articleDesc' => $art->article_desc,'customer'=>$art->third_party,'articleCode'=>$art->article_code];
    //     }
    //     return response()->json($formattedArts);
    // }

    

}
