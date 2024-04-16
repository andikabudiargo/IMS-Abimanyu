<?php

namespace App\Http\Controllers;

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

class TemporaryDnController extends Controller
{
    private $title;
    private $moduleCode;
    private $decimalPlaces;
    public function __construct()
    {
        $this->title = "Temporary DN";
        $this->moduleCode = "DN-UMUM";
        $this->decimalPlaces = config('globalParam.decimal');
    }

    public function getTableColoumn(){
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=>false,'searchable'=>false],
            ['data'=>'tdn_number','name'=>'tdn_number','title'=>'Number'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'so_number','name'=>'so_number','title'=>'SO Number'],
            ['data'=>'delivery_number','name'=>'delivery_number','title'=>'DN Number'],
            ['data'=>'delivery_date','name'=>'delivery_date','title'=>'Delivery Date'],
            ['data'=>'customer_name','name'=>'customer_name','title'=>'Customer'],
            ['data'=>'perihal','name'=>'perihal','title'=>'Perihal'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'updated_so_by','name'=>'updated_so_by','title'=>'Update SO By'],
            ['data'=>'updated_so_at','name'=>'updated_so_at','title'=>'Update SO Date'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created Date'],
            ['data'=>'updated_by','name'=>'updated_by','title'=>'Updated By'],
            ['data'=>'updated_at','name'=>'updated_at','title'=>'Updated Date'],
        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnDetail(){
        $kolom=
        [
            ['data'=>'tdn_number','name'=>'tdn_number','title'=>'Delivery Number'],
            ['data'=>'so_number','name'=>'so_number','title'=>'SO Number'],
            ['data'=>'delivery_number','name'=>'delivery_number','title'=>'DN Number'],
            ['data'=>'delivery_date','name'=>'delivery_date','title'=>'Delivery Date'],
            // ['data'=>'customer_id','name'=>'customer_id','title'=>'Customer'],
            ['data'=>'customer_name','name'=>'customer_name','title'=>'Customer'],
            ['data'=>'article_alternative_code','name'=>'article_alternative_code','title'=>'Article Code'],
            ['data'=>'article_desc','name'=>'article_desc','title'=>'Description'],
            ['data'=>'qty','name'=>'qty','title'=>'Qty'],
            ['data'=>'uom','name'=>'uom','title'=>'UOM'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'perihal','name'=>'perihal','title'=>'Perihal'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'updated_so_by','name'=>'updated_so_by','title'=>'Update SO By'],
            ['data'=>'updated_so_at','name'=>'updated_so_at','title'=>'Update SO Date'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created Date'],
            ['data'=>'updated_by','name'=>'updated_by','title'=>'Updated By'],
            ['data'=>'updated_at','name'=>'updated_at','title'=>'Updated Date'],
        ];
        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $username =  Auth::user()->username;
        $data['title'] = "$this->title";
        $data['kolom'] = $this->getTableColoumn();
        $data['kolomDetail'] = $this->getTableColoumnDetail();
        $data['status'] = ['1'=>'OPEN','2'=>'SO','3'=>'CLOSED'];

        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        return view("temporaryDn.index",$data);
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

        $newCode = str_pad($newCode,5,"0", STR_PAD_LEFT);
        $month = str_pad(date('n'),2,"0", STR_PAD_LEFT);
        $year = date('y');
        $prNumber="$key-$year-$month-$newCode";
        
        return $prNumber;
    }

    public function create(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create $this->title";
        $username =  Auth::user()->username;
        
        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        // $data['articleList']= DB::table('article')
        // ->whereIn('article_type',['FG'])
        // ->orderBy('article_desc')
        // ->get();  
        
        $data['currentDate'] = date('d-m-Y');
        
        return view("temporaryDn.create",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request -> articles);
        $customerId = $request->customerId;
        $deliveryDate = $request->deliveryDate;
        $perihal = $request->perihal;
        $note = $request->note;
        $status = '1';
        $tDnNumber ='';
        $leadCode = $this->moduleCode;
                
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
            // 'iunique' => "PO Number has already been taken",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $validation = Validator::make($request->all(),$messages = [
            'deliveryDate'  => 'required',
            'customerId'  => 'required',
        ]);
        
        $error_array = array();
        $success_output = '';

        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }

            $alert ="warning";
            return response()->json(array('status' => 0, 'message' => $error_array,'alert' =>$alert));

        }else{
            $hasilUpdate = AppHelpers::resetCode($leadCode);
            $tDnNumber = $this->getLastCode($leadCode);
            DB::beginTransaction();
            try {
                DB::table('temporary_dn_hdr')->insert([
                    'tdn_number' => $tDnNumber,
                    'customer_id' => $customerId,
                    'delivery_date' => $deliveryDate,
                    'perihal' => $perihal,
                    'note' => $note,
                    'origin_tdn_number' => $tDnNumber,
                    'status' => $status,
                    'created_by' => Auth::user()->username,
                    'updated_by' => Auth::user()->username,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                $dataSet = [];
                foreach ($articles as $val) {
                    $dataSet[] = [
                        'tdn_number' => $tDnNumber,
                        'article_code' => $val->article_code,
                        'qty' => $val->qty,
                        'uom' => $val->uom,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                }

                DB::table('temporary_dn_det')->insert($dataSet);

                DB::commit();
                $title ="Save $this->title";
                $alert  ="success";
                $message  = "$title $tDnNumber is successfully saved";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'tDnNumber'=>$tDnNumber));

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Save $this->title";
                $alert  ="warning";
                $message  = "$title $tDnNumber is failed to saved";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'tDnNumber'=>$tDnNumber));
            }
        }
    }

    public function show(Request $request)
    {
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";

        $data['headers'] = DB::table('temporary_dn_hdr')
        ->leftJoin('third_party', 'third_party.kode', '=', 'temporary_dn_hdr.customer_id')
        ->where('origin_tdn_number', function($query) use ($id){
            $query->select('tdn_number')->from('temporary_dn_hdr')->where('id',$id);
        })
        ->where('temporary_dn_hdr.status','!=','4')
        ->select('temporary_dn_hdr.*'
        ,DB::raw('(select sum(qty) from temporary_dn_det where tdn_number = temporary_dn_hdr.tdn_number) as sum_qty') 
        ,DB::raw('(select count(*) from temporary_dn_det where tdn_number = temporary_dn_hdr.tdn_number) as sum_row')
        ,DB::raw("concat(kode,'-',nama) as customer_name")
        )
        ->orderBy('id')
        ->get(); 

        $tDnNumber = $data['headers'][0]->tdn_number;
        
        $data['details'] = DB::table('temporary_dn_det')
        ->whereIn('temporary_dn_det.tdn_number', function($query) use ($tDnNumber){
            $query->select('tdn_number')->from('temporary_dn_hdr')->where('origin_tdn_number',$tDnNumber);
        })
        ->leftJoin('article','article.article_code','=','temporary_dn_det.article_code')
        ->select('temporary_dn_det'.'.*'
            ,DB::raw("concat(article_alternative_code,'-',article_desc) as article")
            ,DB::raw("(select STRING_AGG( (qty::real)::text,' -> ' ORDER BY tdn_number) AS main from temporary_dn_det p where article_code = temporary_dn_det.article_code and tdn_number like '$tDnNumber%' ) as notes")
        )
        ->orderBy('temporary_dn_det.tdn_number')
        ->orderBy('temporary_dn_det.id')
        ->get();       

        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        $status = ['OPEN','SALES ORDER','CLOSED','CANCELED'];
        $data['status'] = $status[$data['headers'][0]->status-1];

        return view("temporaryDn.show",$data);
        
    }

    public function edit(Request $request)
    {   
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['header'] = DB::table('temporary_dn_hdr')
        ->where('id',$id)
        ->first();
    
        $tDnNumber = $data['header']->tdn_number;
        $custCode = $data['header']->customer_id;

        $data['details'] = DB::table('temporary_dn_det')
        ->where('tdn_number',$tDnNumber)
        ->orderBy('id')
        ->get(); 

        $dataQuery= DB::table('article') 
        ->whereIn('article.article_code', function($query) use ($custCode) {
            $query->select('article_code')
            ->from('bom_hdr') 
            ->where('status','3')
            ->where('customer',$custCode);
        })
        ->where('third_party',$custCode)
        ->where('article_type','FG')
        ->orderBy('article_desc')
        ->get();

        $output='';
        $output .='<option value="">Choose article</option>';

        foreach ($dataQuery as $row){
            $output .='<option value="'.$row->article_code.'" data-uom="'.$row->uom.'">'.$row->article_alternative_code.'-'. $row->article_desc.'</option>';
        }


        $data['articles'] = $output;

        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        // dd($data['customers']);
        // dd($data['title']);

        $status = ['OPEN','SALES ORDER','CLOSED','CANCELED'];
        $data['status'] = $status[$data['header']->status-1];

        
        return view("temporaryDn.edit",$data);
        
    }
    
    public function update(Request $request)
    {

        $username =  Auth::user()->username;
        $articles = json_decode($request -> articles);
        $tDnNumber = $request->tDnNumber;
        $customerId = $request->customerId;
        $deliveryDate = $request->deliveryDate;
        $perihal = $request->perihal;
        $note = $request->note;
               
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
            // 'iunique' => "PO Number has already been taken",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $validation = Validator::make($request->all(),$messages = [
            'deliveryDate'  => 'required',
            'customerId'  => 'required',
        ]);
        
        $error_array = array();
        $success_output = '';
        // return $validation;
        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }
            
            $title="Save Purchase Request";
            $alert ="error";
            return response()->json(array('status' => 0,'title' => $title, 'message' => $error_array,'alert' =>$alert));
        }else{
            DB::beginTransaction();
            try {               
                $row_affected=DB::table('temporary_dn_hdr')
                ->where('tdn_number',$tDnNumber)
                ->update(
                    [
                        'customer_id' => $customerId,
                        'delivery_date' => $deliveryDate,
                        'perihal' => $perihal,
                        'note' => $note,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                $dataset=[];
                foreach ($articles as $val) {
                    $dataSet[] = [
                        $tDnNumber.$val->article_code
                    ];
                    
                }

                /*
                    Delete kalo article tidak ada di $tDnNumber dan article nya $val->article_code
                    berdasarkan 2 kondisi
                */

                DB::table('temporary_dn_det')
                    ->whereNotIn(DB::raw("CONCAT(tdn_number,article_code)"),$dataSet)
                    ->where('tdn_number',$tDnNumber)
                    ->delete();

                foreach ($articles as $val) {
                    DB::table('temporary_dn_det')
                    ->updateOrInsert(
                        ['tdn_number' => $tDnNumber,'article_code' => $val->article_code],
                        [
                            'tdn_number' => $tDnNumber,
                            'article_code' => $val->article_code,
                            'qty' => $val->qty,
                            'uom' => $val->uom,
                            'created_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );
                }
                
                DB::commit();

                $title ="Save $this->title";
                $alert ="success";
                $message = "$title $tDnNumber is successfully updated";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'tDnNumber'=>$tDnNumber));

            } catch (Exception $e) {
                
                DB::rollBack();
                $title ="Save $this->title";
                $alert  ="warning";
                $message  = "$title $tDnNumber is failed to updated";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'tDnNumber'=>$tDnNumber));
            }
        }
    }

    public function updateSo(Request $request)
    {   
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Update SO $this->title";
        $data['subtitle'] = "Update SO $this->title";

        $data['header'] = DB::table('temporary_dn_hdr')
        ->select('temporary_dn_hdr.*'
        ,DB::raw('(select sum(qty) from temporary_dn_det where tdn_number = temporary_dn_hdr.tdn_number) as sum_qty') 
        ,DB::raw('(select count(*) from temporary_dn_det where tdn_number = temporary_dn_hdr.tdn_number) as sum_row')
        )
        ->where('id',$id)
        ->first();
    
        $tDnNumber = $data['header']->tdn_number;
        $custCode = $data['header']->customer_id;
        $soNumber = $data['header']->so_number;

        $data['details'] = DB::table('temporary_dn_det')
        ->leftJoin('article','article.article_code','=','temporary_dn_det.article_code')
        ->select('temporary_dn_det'.'.*'
            ,DB::raw("concat(article_alternative_code,'-',article_desc) as article")
        )
        ->where('tdn_number',$tDnNumber)
        ->orderBy('id')
        ->get(); 

        $dataQuery= DB::table('article') 
        ->whereIn('article.article_code', function($query) use ($custCode) {
            $query->select('article_code')
            ->from('bom_hdr') 
            ->where('status','3')
            ->where('customer',$custCode);
        })
        ->where('third_party',$custCode)
        ->where('article_type','FG')
        ->orderBy('article_desc')
        ->get();

        $output='';
        $output .='<option value="">Choose article</option>';

        foreach ($dataQuery as $row){
            $output .='<option value="'.$row->article_code.'" data-uom="'.$row->uom.'">'.$row->article_alternative_code.'-'. $row->article_desc.'</option>';
        }

        $data['articles'] = $output;

        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        /*
            16/4/2024
            kalo begini tidak bisa pak, karena SO yng sudah dibuat DN tapi belum close juga kan masih bisa di pakai pak jadi harus muncul.
            yang jangan muncul itu yang status SO nya sudah close semua

            1 SO Boleh ada di beberapa Temporary DN

        */

        $data['soNumbers'] = DB::table('sales_order_hdr')
        ->where ('customer_id','=',$custCode)
        // ->whereNotIn('so_code', function($query) use ($custCode,$soNumber) {
        //     $query->select(db::raw("coalesce(so_number,'')"))
        //     ->from('temporary_dn_hdr') 
        //     ->where('customer_id',$custCode)
        //     ->where('so_number','<>',$soNumber);
        // })
        ->whereIn("status",['2','3'])
        ->where(db::raw("(SELECT count(*) from sales_order_det where so_code = sales_order_hdr.so_code and status = '1')"),">",0)
        ->orderBy('id')
        ->get();

        $status = ['OPEN','SALES ORDER','CLOSED','CANCELED'];
        $data['status'] = $status[$data['header']->status-1];

        return view("temporaryDn.updateSo",$data);
        
    }

    public function updateSoUpdate(Request $request)
    {
        $username =  Auth::user()->username;
        $tDnNumber = $request->tDnNumber;
        $customerId = $request->customerId;
        $soNumber = $request->soNumber;
                       
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
            // 'iunique' => "PO Number has already been taken",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $validation = Validator::make($request->all(),$messages = [
            'soNumber'  => 'required',
            'tDnNumber'  => 'required',
        ]);
        
        $error_array = array();
        $success_output = '';
        // return $validation;
        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }
            $title="Save Purchase Request";
            $alert ="error";
            return response()->json(array('status' => 0,'title' => $title, 'message' => $error_array,'alert' =>$alert));
        }else{
            DB::beginTransaction();
            try {               
                $row_affected=DB::table('temporary_dn_hdr')
                ->where('tdn_number',$tDnNumber)
                ->update(
                    [
                        'so_number' => $soNumber,
                        'status' => '2',
                        'updated_so_by' => Auth::user()->username,
                        'updated_so_at' => date('Y-m-d H:i:s')
                    ]
                );
                
                DB::commit();

                $title ="Update SO $this->title";
                $alert ="success";
                $message = "$title $tDnNumber, $soNumber is successfully updated";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'tDnNumber'=>$tDnNumber));

            } catch (Exception $e) {
                
                DB::rollBack();
                $title ="Update SO $this->title";
                $alert  ="warning";
                $message  = "$title $tDnNumber, $soNumber is failed to updated";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'tDnNumber'=>$tDnNumber));
            }
        }
    }

    public function destroy(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;       
        $tDnHdr = DB::table('temporary_dn_hdr')->where('id',$id)->first();

        $tDnNumber = $tDnHdr->tdn_number;
        $soNumber = $tDnHdr->so_number;

        DB::beginTransaction();
        try {
                $rowAffected=DB::table('temporary_dn_hdr')
                ->where('id',$id)
                ->update(
                    [
                        'status' => '4',
                        'tdn_number' => $tDnNumber."(C)",
                        'so_number' => $soNumber."(C)",
                        'origin_tdn_number' => $tDnNumber."(C)",
                        'reason' => "Cancel",
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                if($rowAffected>0){
                    DB::table('temporary_dn_det')
                    ->where('tdn_number',$tDnNumber)
                    ->update(
                    [
                        'tdn_number' => $tDnNumber."(C)",
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                    );
                    DB::commit();
                    $title ="Delete $this->title";
                    $alert  ="success";
                    $message  = "$title $tDnNumber Successfully Delete";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
                }else{
                    DB::rollBack();
                    $title ="Delete $this->title";
                    $alert  ="warning";
                    $message  = "$title $tDnNumber Failed to Delete";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);

                }

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Delete $this->title";
            $alert  ="warning";
            $message  = "$title $tDnNumber Failed to Delete";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }

    }

    public function closed(Request $request)
    {
        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);
        $tDnNumber = DB::table('temporary_dn_hdr')->where('id',$id)->value('tdn_number');
        $status = '3';
        DB::beginTransaction();
        try {
                $row_affected=DB::table('temporary_dn_hdr')
                ->where('id',$id)
                ->update(
                    [
                        'status' => $status,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );
                
                DB::commit();
                $title ="Close $this->title";
                $alert  ="success";
                $message  = "$title $tDnNumber Successfully Closed";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Close $this->title";
            $alert  ="warning";
            $message  = "$title $tDnNumber Failed to Close";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }
    }

    public function createDn(Request $request)
    {

        /*
            pada saat pembuatan DN dari temporary cek dulu QTY SO nya apakah sudah sesuai atau belum
            dengan kedaan pasa saat DN dibuat
            Cek apakah article yang ada di temporary semua ada di SO ?
            Cek apakah qty temporary melebihi sisa SO

        */

        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);
        $tDnHeader = DB::table('temporary_dn_hdr')->where('id',$id)->first();
        $tDnNumber = $tDnHeader->tdn_number; 
        $dnDate = $tDnHeader->delivery_date;
        $soNumber = $tDnHeader->so_number;
        $poNumber = db::table('sales_order_hdr')->where('so_code',$soNumber)->value('po_number');
        $dnNew ="";
        $pesan ="";
        $hasilPosting = "";
        $idDelivery = "";
        $idKu = "";

        /*
            Cek apakah article yang ada di temporary semua ada di SO ?
        */

        $cekArticle = DB::table('temporary_dn_det as a')
        ->leftJoin('temporary_dn_hdr as b','a.tdn_number','b.tdn_number')
        ->leftJoin('sales_order_det as c', function ($join) {
            $join->on('c.so_code', '=', 'b.so_number');
            $join->on('c.article_code', '=', 'a.article_code');
        })
        ->where('a.tdn_number',$tDnNumber)
        ->where('c.article_code','=',null)
        ->count();

        /*
            Cek apakah qty SO masih ada atau sudah kosong
        */

        $cekSelisihQuery = DB::select("SELECT count(*) as jumlah from
        (select *, 
        (select coalesce((select qty from sales_order_det where so_code = b.so_number and article_code = a.article_code),0)) as qty_so,
        coalesce((select sum(qty) as qty_delivery from delivery_det where delivery_number in (select delivery_number from delivery_hdr where so_number = b.so_number) and article_code = a.article_code group by article_code),0) as qty_delivery,
        (select coalesce((select qty from sales_order_det where so_code = b.so_number and article_code = a.article_code),0)-
        coalesce((select sum(qty) as qty_delivery from delivery_det where delivery_number in (select delivery_number from delivery_hdr where so_number = b.so_number) and article_code = a.article_code group by article_code),0)) as qty_selisih
        from temporary_dn_det a
        left join temporary_dn_hdr b on a.tdn_number = b.tdn_number
        where a.tdn_number = '$tDnNumber'
        ) as oki
        where qty_selisih <= 0
        ");

        $adaSelisih = $cekSelisihQuery[0]->jumlah;

        if ($cekArticle > 0 ){
            $pesan .= ", Article di DN tidak ada di SO";
        }

        if ($adaSelisih > 0 ){
            $pesan .= ", QTY SO sudah habis";
        }

        // dd($pesan);

        if($pesan == ''){

            $periodNomor=(int)explode('-', $dnDate)[1];
            $dnNew = app('App\Http\Controllers\DeliveryController')->getLastCode('DN',$periodNomor);
            
            DB::beginTransaction();
            try {
                $sqlHdr = "INSERT into delivery_hdr 
                (
                    delivery_number,
                    origin_delivery_number,
                    delivery_date,
                    customer_id,
                    so_number,
                    po_number,
                    status,
                    note,
                    created_by,
                    updated_by,
                    created_at,
                    updated_at
                )
                select 
                '$dnNew',
                '$dnNew',
                delivery_date,
                customer_id,
                so_number,
                '$poNumber',
                '1',
                note,
                '$username',
                '$username',
                '".date('Y-m-d H:i:s')."',
                '".date('Y-m-d H:i:s')."'
                from temporary_dn_hdr where tdn_number = '$tDnNumber'";
            
                $sqlDet="INSERT into delivery_det
                (
                    delivery_number,
                    article_code,
                    so_number,
                    po_number,
                    qty,
                    uom,
                    created_by,
                    created_at,
                    qty_so
                )
                select 
                    '$dnNew',
                    article_code,
                    '$soNumber',
                    '$poNumber',
                    qty,
                    uom,
                    '$username',
                    '".date('Y-m-d H:i:s')."',
                    (select coalesce((select qty from sales_order_det where so_code = b.so_number and article_code = a.article_code),0)-
                    coalesce((select sum(qty) as qty_delivery from delivery_det where delivery_number in (select delivery_number from delivery_hdr where so_number = b.so_number) and article_code = a.article_code group by article_code),0)) as qty_so
                from temporary_dn_det a
                left join temporary_dn_hdr b on b.tdn_number = a.tdn_number
                where a.tdn_number = '$tDnNumber'";

                $rowAffected =  DB::select($sqlHdr);
                if ($rowAffected){
                    DB::select($sqlDet);
                    $row_affected=DB::table('temporary_dn_hdr')
                    ->where('tdn_number',$tDnNumber)
                    ->update(
                        [
                            'status' => '3',
                            'delivery_number' => $dnNew,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );

                    $idDelivery = db::table('delivery_hdr')->where('delivery_number',$dnNew)->value('id');

                    $idKu = Crypt::encryptString($idDelivery);

                    $hasilPosting = app('App\Http\Controllers\DeliveryController')->postingFromOther($idDelivery);
                    
                    DB::commit();
                    $title ="Create DN $this->title";
                    $alert  ="success";
                    $message  = "$title $dnNew from $tDnNumber Successfully Create DN";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message,'hasilPosting'=>$hasilPosting,'idDelivery' => $idKu]);
                }else{
                    DB::rollBack();
                    // 'DN/ASN/24/04/2379
                    $noAkhirDn = explode('/', $dnNew)[4];
                    $row_affected=DB::table('master_code')
                    ->where('code_key','DN')
                    ->where('code_number',$noAkhirDn)
                    ->update(
                        [
                            'code_number' => $noAkhirDn-1,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );

                    $title ="Create DN $this->title";
                    $alert  ="warning";
                    $message  = "$title $dnNew from $tDnNumber Failed Create DN Insert to HDR";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message,'hasil'=>$hasilPosting,'idDelivery' => $idKu]);
                }
            } catch (Exception $e) {
                DB::rollBack();
                $title ="Create DN $this->title";
                $alert  ="warning";
                $message  = "$title $dnNew from $tDnNumber Failed2 Create DN Error Query";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message,'hasil'=>$hasilPosting,'idDelivery' => $idKu]);
            }
        }else{
            $title ="Create DN $this->title";
            $alert  ="warning";
            $message  = "$title $dnNew from $tDnNumber Failed3 Create DN Warning message";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message.$pesan,'hasil'=>$hasilPosting,'idDelivery' => $idKu]);
        }
    }

    public function list(Request $request)
    {
        // status:
        // 1 = Open
        // 2 = Sales Order
        // 3 = Closed
        // 4 = Canceled
        
        $username =  Auth::user()->username;
        $searchDn = strtolower($request->searchDn);
        $searchStatus = $request->searchStatus;
        $deliveryDate = $request->deliveryDate;
        $searchCustomer = $request->searchCustomer;
        $fromDate ="";
        $toDate = "";       
 
        if ($deliveryDate){
            $date = explode("to",$deliveryDate);
            if(count($date)>1){
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate; 
            }
            // $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
            // $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
        }

        $data = DB::table('temporary_dn_hdr')
        ->leftJoin('third_party', 'third_party.kode', '=', 'temporary_dn_hdr.customer_id')
        ->where(function ($query) use ($searchDn,$searchStatus,$deliveryDate,$fromDate,$toDate,$searchCustomer) {
            $searchDn ? $query->where('temporary_dn_hdr.tdn_number','ilike','%'.$searchDn.'%') : '';
            $searchStatus ? $query->where('temporary_dn_hdr.status',$searchStatus) : '';
            $deliveryDate ? $query->whereBetween(DB::raw("to_date(delivery_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
            $searchCustomer ? $query->where('temporary_dn_hdr.customer_id',$searchCustomer) : '';
        })
        ->where('temporary_dn_hdr.status','!=','4')
        ->select('temporary_dn_hdr.*',DB::raw("concat(kode,'-',nama) as customer_name"))
        ->orderBy('id')
        ->get(); 
             
        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';

            if ($data->status == '1') {
            $buttons .= '<a href="'. route('suratJalanSementara.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                            <i data-feather="file-text"></i>
                               Edit
                        </a>';
            }

            if ($data->status == '1' || $data->status == '2') {
                $buttons .= '<a href="'. route('suratJalanSementara.updateSo', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                <i data-feather="file-text"></i>
                                   Update SO
                            </a>';
            }

            $buttons .= '<a href="'. route('suratJalanSementara.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
                            <i data-feather="printer"></i>
                            Print
                        </a>';

            $buttons .= '<a href="'. route('suratJalanSementara.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                            <i data-feather="list"></i>
                            Detail
                         </a>';

            if ( $data->status == '2' ){
            // if (Auth::user()->can('purchaseOrder-delete')) {
                $buttons .="<a href='javascript:;'
                class='dropdown-item' 
                data-size='sm'
                data-ajax-delete='true'
                data-confirm='Are You Sure want to Create Delivery Note?' 
                data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                data-modal-id='".$data->id."'
                id='deleteButton'
                data-url='". route('suratJalanSementara.createDn', ['id'=>Crypt::encryptString($data->id)]) ."'>
                <i data-feather='file-text'></i>
                <span>". __('Create DN') ."</span>
                </a>";
            // }
            }
                
            if ($data->status != '3') {
                $buttons .= "<a href='javascript:;'
                                class='dropdown-item' 
                                data-size='sm'
                                data-ajax-delete='true'
                                data-confirm='Are You Sure want to Delete?|This action can not be undone. Do you want to continue?' 
                                data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                                data-modal-id='".$data->id."'
                                id='deleteButton'
                                data-url='". route('suratJalanSementara.destroy', ['id'=>Crypt::encryptString($data->id)]) ."'>
                                <i data-feather='trash-2' class='feather-14-red'></i>
                                <span>". __('Cancel') ."</span>
                            </a>";
            }
            
            // if ( $data->status == '2' ){
            //     // if (Auth::user()->can('purchaseOrder-delete')) {
            //         $buttons .="<a href='javascript:;'
            //         class='dropdown-item' 
            //         data-size='sm'
            //         data-ajax-delete='true'
            //         data-confirm='Are You Sure want to Close?|This action can not be undone. Do you want to continue?' 
            //         data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
            //         data-modal-id='".$data->id."'
            //         id='deleteButton'
            //         data-url='". route('suratJalanSementara.close', ['id'=>Crypt::encryptString($data->id)]) ."'>
            //         <i data-feather='x' class='feather-14-red'></i>
            //         <span>". __('Close') ."</span>
            //         </a>";
            //     // }
            // }

            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-secondary'];
            $statusPr = ['OPEN','SO','CLOSED','CANCELED'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusPr[$data->status - 1]."</div>";
        })
        ->rawColumns(['action','status','tdn_number'])
        ->make(true);
    }

    public function listDetail(Request $request)
    {
        $username =  Auth::user()->username;
        $searchDn = strtolower($request->searchDn);
        $searchStatus = $request->searchStatus;
        $deliveryDate = $request->deliveryDate;
        $searchCustomer = $request->searchCustomer;
        $fromDate ="";
        $toDate = "";

        if ($deliveryDate){
            $date = explode("to",$deliveryDate);
            if(count($date)>1){
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate; 
            }
        }      
    
        $data = DB::table('temporary_dn_det')
        ->leftJoin('temporary_dn_hdr','temporary_dn_hdr.tdn_number','temporary_dn_det.tdn_number')
        ->leftJoin('article','article.article_code','temporary_dn_det.article_code')
        ->leftJoin('third_party','third_party.kode','temporary_dn_hdr.customer_id')
        ->where(function ($query) use ($searchDn,$searchStatus,$deliveryDate,$fromDate,$toDate,$searchCustomer) {
            $searchDn ? $query->where('temporary_dn_hdr.tdn_number','ilike','%'.$searchDn.'%') : '';
            $searchStatus ? $query->where('temporary_dn_hdr.status',$searchStatus) : '';
            $deliveryDate ? $query->whereBetween(DB::raw("to_date(delivery_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
            $searchCustomer ? $query->where('temporary_dn_hdr.customer_id',$searchCustomer) : '';
        })
        ->where('temporary_dn_hdr.status','!=','4')
        ->select('temporary_dn_det.*'
            ,'article_alternative_code'
            ,'article.article_desc'
            ,'temporary_dn_hdr.status'
            ,'temporary_dn_hdr.delivery_date'
            ,'temporary_dn_hdr.perihal'
            ,'temporary_dn_hdr.note'
            ,'temporary_dn_hdr.updated_so_by'
            ,'temporary_dn_hdr.updated_so_at'
            ,'temporary_dn_hdr.so_number'
            ,'temporary_dn_hdr.delivery_number'
            ,'temporary_dn_hdr.delivery_date'
            ,'third_party.nama as customer_name'    
        )
        ->orderBy('id')
        ->orderBy('tdn_number')
        ->get(); 
             
        return Datatables::of($data)
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-secondary'];
            $statusPr = ['OPEN','SO','CLOSED','CANCELED'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusPr[$data->status - 1]."</div>";
        })
        ->rawColumns(['status'])
        ->make(true);
    }

    public function print(Request $request)
    {
        $data['title'] = "Print $this->title";
        $data['subtitle'] = "Print $this->title";
        $id=Crypt::decryptString($request->id);
                
        $tDnHdr=DB::table('temporary_dn_hdr')
        ->where('temporary_dn_hdr.id',$id)
        ->first();

        $data['tDnHdr']=DB::table('temporary_dn_hdr')
        ->where('temporary_dn_hdr.id',$id)
        ->first();

        $tDnNumber=$tDnHdr->tdn_number;

        $data['details']=DB::table('temporary_dn_det')
        ->leftJoin('article','article.article_code','temporary_dn_det.article_code')
        ->select('article_alternative_code'
        ,'article_desc'
        ,'temporary_dn_det.qty'
        ,'temporary_dn_det.uom'
        ,DB::raw("(select STRING_AGG( (qty::real)::text,' -> ' ORDER BY tdn_number) AS main from (select * from temporary_dn_det p where article_code = temporary_dn_det.article_code and tdn_number like '$tDnNumber%' limit 2) sub) as notes")
        )
        ->where('tdn_number',$tDnNumber)
        ->orderBy('temporary_dn_det.id')
        ->get();

        $data['tDnNumber'] =$tDnNumber;
        $data['tDnDate'] =$tDnHdr->delivery_date;
        $data['tDnNote'] =$tDnHdr->note;
        
        $statusPr = ['NEW','VALIDATED','APPROVED','RECEIVED','CANCELED','CLOSED','PO'];
        $data['prStatus'] = $statusPr[$tDnHdr->status-1];

        $data['no'] =0;

        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->where('kode',$tDnHdr->customer_id)
        ->orderBy('nama')
        ->first();

        return view('temporaryDn.print',$data);
       
        // view()->share($data);

        // $pdf = PDF::loadView('temporaryDn.print');
        // return $pdf->stream("TDN_$tDnNumber.pdf");

    }

    public function getArticle(Request $request){
        
        $custCode = $request->custCode;
        $data= DB::table('article') 
        ->whereIn('article.article_code', function($query) use ($custCode) {
            $query->select('article_code')
            ->from('bom_hdr') 
            ->where('status','3')
            ->where('customer',$custCode);
        })
        ->where('third_party',$custCode)
        ->where('article_type','FG')
        ->orderBy('article_desc')
        ->get();

        $output='';
        $output .='<option value="">Choose article</option>';

        foreach ($data as $row){
            $output .='<option value="'.$row->article_code.'" data-uom="'.$row->uom.'">'.$row->article_alternative_code.'-'. $row->article_desc.'</option>';
        }

        return $output;

    }


}
