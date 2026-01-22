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

class DnReplaceController extends Controller
{
    private $title;
    private $moduleCode;
    private $decimalPlaces;
    private $lockDate;
    private $lockDateIndex;
    public function __construct()
    {

        $this->title = "DN Replace";
        $this->moduleCode = "DN-REPLACE";
        $this->decimalPlaces = config('globalParam.decimal');
        // $lockDate1 = DB::table('application_lock')
        // ->where('code_key',$this->moduleCode)
        // ->where('status','1')
        // ->value('lock_date');

        /*
            $lastDatePrevMonth = date('t-m-Y', strtotime('-1 months'));
            $lastDatePrevMonth = date('t-m-Y', strtotime('-1 months',strtotime('05-11-2023')));
            $firstDayCurrentMonth = date('1-m-Y');
            $firstDayCurrentMonth = date('1-m-Y', strtotime('05-11-2023'));
            $prevmonth = date('M Y 1', strtotime('-1 months'));
            
            
            jika tanggal hari ini lebih kecil dari lockdate maka
            min date nya adalah tanggal akhir dari bulan sebelumnya
            kalau tanggal hari ini lebi besar dari lockdate maka 
            tanggal minimum nya adalah tanngal awal di bulan ini
        */

        // $todayDate = date('d-m-Y');
        // $lockDateHere = $lockDate1 ? $lockDate1 : '2023-01-01' ;
        // $lockDateAt = date('d-m-Y', strtotime("+1 day", strtotime($lockDateHere)));

        // dd(date('t-m-Y', strtotime($lockDateAt)));
        // dd($todayDate." < ".$lockDateAt);

        // if ($todayDate < $lockDateAt ){
        //     $firstDatePrevMonth = date('1-m-Y', strtotime("-1 months",strtotime($lockDateHere)));
        //     $lockDateAt = $firstDatePrevMonth;
        // }else{
        //     $lockDateAt = date('1-m-Y', strtotime($lockDateAt));
        // }

        // // $lockDateHere = $lockDate1 ? $lockDate1 : '2023-01-01' ;
        // // $lockDateAt = date('d-m-Y', strtotime("+1 day", strtotime($lockDateHere)));
        // $this->lockDate = $lockDateAt;

        // $lockDateHereIndex = $lockDate1 ? $lockDate1 : '2023-01-01' ;
        // $lockDateAtIndex = date('d-m-Y', strtotime($lockDateHere));
        // $this->lockDateIndex = $lockDateAtIndex;

    }

    public function getTableColoumn(){
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action', 'orderable'=> false,'searchable'=>false],
            ['data'=>'replace_number','name'=>'replace_number','title'=>'Rec Number'],
            ['data'=>'return_number','name'=>'return_number','title'=>'Return Number'],
            ['data'=>'tanggal_replace','name'=>'tanggal_replace','title'=>'Replace Date'],
            ['data'=>'customer_id','name'=>'customer_id','title'=>'Customer Code'],
            ['data'=>'customer_name','name'=>'customer_name','title'=>'Customer'],
            // ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created At']
        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnDetail(){
        $kolom=
        [
            ['data'=>'replace_number','name'=>'replace_number','title'=>'Rec Number'],
            ['data'=>'return_number','name'=>'return_number','title'=>'Return Number'],
            ['data'=>'tanggal_replace','name'=>'tanggal_replace','title'=>'Replace Date'],
            ['data'=>'customer_id','name'=>'customer_id','title'=>'Customer Code'],
            ['data'=>'customer_name','name'=>'customer_name','title'=>'Customer'],
            // ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'article_alternative_code','name'=>'article_alternative_code','title'=>'Article Code'],
            ['data'=>'article_desc','name'=>'article_desc','title'=>'Article Desc'],
            ['data'=>'qty','name'=>'qty','title'=>'qty'],
            ['data'=>'uom','name'=>'uom','title'=>'uom'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'created_by_1','name'=>'created_by_1','title'=>'Created By'],
            ['data'=>'created_at_1','name'=>'created_at_1','title'=>'Created By'],
        ];
        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $data['title'] = $this->title;
        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','7'=>'REVISED','10'=>'REVISI'];
        //  ['NEW','VALIDATE','APPROVED','POSTED','CANCELED','','','','','REVISI']; 

        $data['status'] = ['1'=>'OPEN','2'=>'CLOSED','3'=>'CANCELED'];
        $data['kolom'] = $this->getTableColoumn();
        $data['kolomDetail'] = $this->getTableColoumnDetail();

        // dd($this->lockDate);
        // $data['lockDate'] = $this->lockDateIndex;
            
        return view("dnReplace.index",$data);
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
        $data['cust'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();
        $data['oEdit']=false;
        // $data['lockDate'] = $this->lockDate;

        return view("dnReplace.create",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request->articles);
        $replaceDate = $request->replaceDate;
        $returnNumber = $request->returnNumber;
        $customer = $request->customer;
        $note = $request->note;
        $status = '1';
        $leadCode = $this->moduleCode;

        // $data['status'] = ['1'=>'OPEN','2'=>'CLOSED','3'=>'CANCELED'];
        
        $customMessages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) use ($returnNumber) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            $column2 = $query->getGrammar()->wrap($parameters[2]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])
                          ->whereRaw("lower({$column2}) = lower(?)", [$returnNumber])->count();
        });
        
        $validation = Validator::make($request->all(),$messages = [
            'returnNumber'=>'required',
            'replaceDate'  => 'required',
        ],$customMessages);
        
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
            $hasilUpdate = AppHelpers::resetCode($leadCode);
            $replaceNumber = $this->getLastCode($leadCode);
            DB::beginTransaction();
            try {
                    $idKu = DB::table('dn_replace_hdr')->insertGetId([
                        'replace_number' => $replaceNumber,
                        'return_number' => $returnNumber,
                        'replace_date' => $replaceDate,
                        'customer_id' => $customer,
                        'status' => $status,
                        'note' => $note,
                        'origin_replace_number' => $replaceNumber,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                    $idKu = Crypt::encryptString($idKu);

                    $dataSet = [];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            'replace_number' => $replaceNumber,
                            'return_number' => $returnNumber,
                            'article_code' => $val->article_code,
                            'qty_return' => $val->qty_return,
                            'qty' => $val->qty,
                            'uom' => $val->uom,
                            'created_by' => Auth::user()->username,
                            'updated_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                    }

                    DB::table('dn_replace_det')->insert($dataSet);

                    $sisaReturn = DB::select("SELECT sum(qty) - sum(qty_replace) as sisa_return from 
                    (select * ,
                    (select sum(qty) from dn_replace_det where return_number = dn_return_det.return_number and article_code= dn_return_det.article_code) as qty_replace
                    from dn_return_det where return_number = '$returnNumber') as oki
                    ");

                    if ($sisaReturn[0]->sisa_return == 0){
                        DB::table('dn_return_hdr')
                        ->where('return_number',$returnNumber)
                        ->update([
                            'status' => '3',
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    }else{
                        DB::table('dn_return_hdr')
                        ->where('return_number',$returnNumber)
                        ->update([
                            'status' => '1',
                        ]);
                    }

                    DB::commit();
                    $title = "Save $this->title";
                    $alert  ="success";
                    $message  = "$title $replaceNumber is successfully saved";
                    $statusReplace  = 'NEW';
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('statusReplace' => $statusReplace, 'title' => $title, 'status' => 1, 'message' => $message,'alert'=>$alert,'replaceNumber'=>$replaceNumber,'idKu'=>$idKu));

            } catch (Exception $e) {
                DB::rollBack();
                $title = "Save $this->title";
                $alert  ="warning";
                $message  = "$title $replaceNumber is failed to save";
                $statusReplace = 'FAILED';
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('statusReplace' => $statusReplace, 'title' => $title, 'status' => 1, 'message' => $message,'alert'=>$alert,'replaceNumber'=>$replaceNumber,'idKu'=>''));
            }
        }
    }

    public function show(Request $request)
    {
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['header'] = DB::table('dn_replace_hdr')
        ->leftJoin('dn_return_hdr','dn_return_hdr.return_number','dn_replace_hdr.return_number')
        ->leftJoin('third_party', 'third_party.kode', '=', 'dn_replace_hdr.customer_id')
        ->select('dn_replace_hdr.*'
        ,'dn_return_hdr.dn_number'
        ,DB::raw('(select sum(qty) from dn_replace_det where replace_number = dn_replace_hdr.replace_number) as sum_qty') 
        ,DB::raw('(select count(*) from dn_replace_det where replace_number = dn_replace_hdr.replace_number) as sum_row')
        ,DB::raw("concat(kode,'-',nama) as customer_name"))
        ->where('dn_replace_hdr.id',$id)
        ->get()->first();

        $replaceNumber = $data['header']->replace_number;
        $custId = $data['header']->customer_id;
        $returnNumber = $data['header']->return_number;
                
        $data['details'] = DB::table('dn_replace_det')
        ->leftJoin('dn_replace_hdr','dn_replace_hdr.replace_number','dn_replace_det.replace_number')
        ->leftJoin('article','article.article_code','=','dn_replace_det.article_code')
        ->where('dn_replace_det.replace_number',$replaceNumber)
        ->orderBy('dn_replace_det.id')
        ->select('dn_replace_det.*','dn_replace_det.uom',
            db::raw("concat(article.article_alternative_code,'-',article_desc) as article")
            ,DB::raw("(select qty from dn_return_det where return_number = dn_replace_det.return_number and article_code = dn_replace_det.article_code) as tot_qty_return"),
            DB::RAW("coalesce(
                (select ((select sum(qty) from dn_return_det where return_number = dn_replace_hdr.return_number and article_code = dn_replace_det.article_code) + dn_replace_det.qty) - sum(qty) as qty_return from dn_replace_det a where replace_number in (
            select replace_number from dn_replace_hdr z where z.status not in ('3') and z.return_number = dn_replace_hdr.return_number) 
            and article_code = dn_replace_det.article_code),0) as qty_return")
        )
        ->get();       

        $data['custs'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$replaceNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$replaceNumber,$username);

        // $data['status'] = ['1'=>'OPEN','2'=>'CLOSED','3'=>'CANCELED'];
        $statusReplace = ['OPEN','CLOSED','CANCELED'];
        $data['statusReplace'] = $statusReplace[$data['header']->status-1];

        $data['oEdit']=true;

        $dataCust= DB::table("dn_return_hdr") 
        ->where("customer_id",$custId)
        ->where("status","1")
        ->orderBy("return_number")
        ->select('return_number','dn_number')
        ->get();          

        $output = "";
        if (count($dataCust)>0){
            $output .='<option value="Choose PO">Choose DN</option>';            
            foreach ($dataCust as $row){
                $selected = $row->return_number=== $returnNumber ? 'selected' :'';
                $output .='<option value="'.$row->return_number.'" selected data-dn= "'.$row->dn_number.'">'.$row->return_number.'</option>';            
            }
        }

        $data['listReturn'] = $output;

        $status = ['OPEN','CLOSED','CANCELED'];
        $data['status'] = $status[$data['header']->status-1];

        // $data['lockDate'] = $this->lockDate;

        // dd($data);

        return view("dnReplace.show",$data);
        
    }

    public function edit(Request $request)
    {
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['header'] = DB::table('dn_replace_hdr')
        ->leftJoin('dn_return_hdr','dn_return_hdr.return_number','dn_replace_hdr.return_number')
        ->where('dn_replace_hdr.id',$id)
        ->get()->first();

        $replaceNumber = $data['header']->replace_number;
        $custId = $data['header']->customer_id;
        $returnNumber = $data['header']->return_number;
                
        $data['detail'] = DB::table('dn_replace_det')
        ->leftJoin('dn_replace_hdr','dn_replace_hdr.replace_number','dn_replace_det.replace_number')
        ->leftJoin('article','article.article_code','=','dn_replace_det.article_code')
        ->where('dn_replace_det.replace_number',$replaceNumber)
        ->orderBy('dn_replace_det.id')
        ->select('dn_replace_det.*','dn_replace_det.uom','article.*',
            DB::raw("(select qty from dn_return_det where return_number = dn_replace_det.return_number and article_code = dn_replace_det.article_code) as tot_qty_return"),
            DB::RAW("coalesce(
                (select ((select sum(qty) from dn_return_det where return_number = dn_replace_hdr.return_number and article_code = dn_replace_det.article_code) + dn_replace_det.qty) - sum(qty) as qty_return from dn_replace_det a where replace_number in (
            select replace_number from dn_replace_hdr z where z.status not in ('3') and z.return_number = dn_replace_hdr.return_number) 
            and article_code = dn_replace_det.article_code),0) as qty_return")
        )
        ->get();       

        $data['custs'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$replaceNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$replaceNumber,$username);

        // $data['status'] = ['1'=>'OPEN','2'=>'CLOSED','3'=>'CANCELED'];
        $statusReplace = ['OPEN','CLOSED','CANCELED'];
        $data['statusReplace'] = $statusReplace[$data['header']->status-1];

        $data['oEdit']=true;

        $dataCust= DB::table("dn_return_hdr") 
        ->where("customer_id",$custId)
        // ->where("status","1")
        ->orderBy("return_number")
        ->select('return_number','dn_number')
        ->get();          

        $output = "";
        if (count($dataCust)>0){
            $output .='<option value="Choose PO">Choose DN</option>';            
            foreach ($dataCust as $row){
                $selected = $row->return_number=== $returnNumber ? 'selected' :'';
                $output .='<option value="'.$row->return_number.'" selected data-dn= "'.$row->dn_number.'">'.$row->return_number.'</option>';            
            }
        }

        $data['listReturn'] = $output;

        $status = ['OPEN','CLOSED','CANCELED'];
        $data['status'] = $status[$data['header']->status-1];

        // $data['lockDate'] = $this->lockDate;

        // dd($data);

        return view("dnReplace.edit",$data);
        
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $replaceNumber = $request->replaceNumber;
        $replaceDate = $request->replaceDate;
        $returnNumber = $request->returnNumber;
        $customer = $request->customer;
        $note = $request->note;
        $articles = json_decode($request->articles);
        $status = '1';
        
        // $data['status'] = ['1'=>'OPEN','2'=>'CLOSED','3'=>'CANCELED'];

        $customMessages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            $column2 = $query->getGrammar()->wrap($parameters[2]);
        });
        
        $validation = Validator::make($request->all(),$messages = [
            // 'invNumber'=>'required|iunique:dn_replace_hdr,inv_number,po_number',
            'replaceDate'  => 'required',
            'replaceNumber'  => 'required',
            // 'supplier'  => 'required',
        ],$customMessages);
                
        $error_array = array();
        $success_output = '';
        // return $validation;
        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }
            $title="Update $this->title";
            $alert ="error";
            return response()->json(array('status' => 0,'title' => $title, 'message' => $error_array,'alert' =>$alert));
        }else{
            DB::beginTransaction();
            try {
                    $row_affected=DB::table('dn_replace_hdr')
                    ->where('replace_number',$replaceNumber)
                    ->update(
                        [   
                            'return_number' => $returnNumber,
                            'replace_date' => $replaceDate,
                            'customer_id' => $customer,
                            'status' => $status,
                            'note' => $note,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );

                    $dataset=[];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            $replaceNumber.$val->article_code
                        ];
                        
                    }

                    //Delete kalo article tidak ada di po $poNumber dan article nya $val->article_code
                    //berdasarkan 2 kondisi
                    DB::table('dn_replace_det')
                        ->whereNotIn(DB::raw("CONCAT(replace_number,article_code)"),$dataSet)
                        ->where('replace_number',$replaceNumber)
                        ->delete();

                    foreach ($articles as $val) {
                        DB::table('dn_replace_det')
                        ->updateOrInsert(
                            ['replace_number' => $replaceNumber,'article_code' => $val->article_code],
                            [
                                'replace_number' => $replaceNumber,
                                'return_number' => $returnNumber,
                                'article_code' => $val->article_code,
                                'qty' => $val->qty,
                                'uom' => $val->uom,
                                'updated_by' => Auth::user()->username,
                                'updated_at' => date('Y-m-d H:i:s')
                            ]
                        );
                    }

                    $sisaReturn = DB::select("SELECT sum(qty) - sum(qty_replace) as sisa_return from 
                    (select * ,
                    (select sum(qty) from dn_replace_det where return_number = dn_return_det.return_number and article_code= dn_return_det.article_code) as qty_replace
                    from dn_return_det where return_number = '$returnNumber') as oki
                    ");

                    if ($sisaReturn[0]->sisa_return == 0){
                        DB::table('dn_return_hdr')
                        ->where('return_number',$returnNumber)
                        ->update([
                            'status' => '3',
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    }else{
                        DB::table('dn_return_hdr')
                        ->where('return_number',$returnNumber)
                        ->update([
                            'status' => '1',
                        ]);
                    }
                                                                
                    DB::commit();
                    $title ="Update $this->title";
                    $alert  ="success";
                    $message  = "$title $replaceNumber is successfully updated";
                    $statusReplace = 'UPDATED';
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('statusReplace' => $statusReplace,'status' => 1, 'title' => $title,'message' => $message,'alert'=>$alert,'replaceNumber'=>$replaceNumber));

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Update $this->title";
                $alert  ="warning";
                $message  = "$title $replaceNumber is failed to updated";
                $statusReplace = 'FAILED';
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('statusReplace' => $statusReplace,'status' => 1, 'title' => $title,'message' => $message,'alert'=>$alert,'replaceNumber'=>$replaceNumber));
            }
        }
    }

    public function posting(Request $request)
    {
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $replaceNumber = $request->replaceNumber;
        $replaceNumber = DB::table('dn_replace_hdr')->where('id',$id)->value('replace_number');
        $recType = "NORMAL";
        $siteCode = 'HO';
        $location ='WH';
        $status = '4';
        $moduleCode = $this->moduleCode;
        $todayDate = date('Y-m-d');
        $movementDate = date("d-m-Y");
        $dariNew = $request->dariNew;

        // $rowAffected = 0;

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','7'=>'REVISED','10'=>'REVISI'];
        //  ['NEW','VALIDATE','APPROVED','POSTED','CANCELED','','','','','REVISI']; 
            
        if ($replaceNumber){
            $data = DB::table('dn_replace_det')
            ->leftJoin('dn_replace_hdr','dn_replace_hdr.replace_number','dn_replace_det.replace_number')
            ->leftJoin('article','article.article_code','dn_replace_det.article_code')
            ->where('dn_replace_det.replace_number',$replaceNumber)
            // ->where('dn_replace_hdr.status','3')
            ->select('dn_replace_det.*'
            ,'article.article_type'
            ,'article.uom as uom_article'
            ,DB::RAW("average_cost(dn_replace_det.article_code,'$siteCode','$location','$moduleCode') as average_cost")
            ,DB::RAW("(dn_replace_det.qty*uom_conversion(dn_replace_det.uom_rec,article.uom))+(dn_replace_det.qty_free*uom_conversion(dn_replace_det.uom_rec,article.uom)) as total_qty")
            )
            ->get();


            foreach($data as $val){
                //insert article code kalo belum ada di tabel item_stock
                // if( $val->total_qty > 0 ){
                    DB::table('article_stock')
                    ->updateOrInsert(
                        [ 'site_code' =>$siteCode,
                          'article_code' => $val->article_code,
                          'location_number'=> $location
                        ],
                        [
                          'dept_code'=>$val->article_type,
                          'uom'=>$val->uom_article,
                        ]
                    );
    
                    //update qty nya ditambahkan dengan qty baru
                    $rowAffected = DB::table('article_stock')
                    ->where('site_code',$siteCode)
                    ->where('article_code',$val->article_code)
                    ->where('location_number',$location)
                    ->update([
                        'article_qty' => DB::raw('coalesce(article_qty,0) + '.$val->total_qty)
                    ]);

                    if ($rowAffected > 0){
                        DB::table('article')
                        ->where('article_code',$val->article_code)
                        ->update(
                        [   
                            'lastcost' => $val->price,
                            'avgcost' =>  $val->average_cost,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                        );
                    }
                // }

                // $rowAffected = DB::table('article_stock')
                // ->where('site_code',$siteCode)
                // ->where('article_code',$val->article_code)
                // ->increment('article_qty', $val->total_qty);

            }
                    
            $rowAffected = DB::table('dn_replace_hdr')
            ->where('replace_number',$replaceNumber)
            ->update(
                [   
                    'status' => $status,
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );
            
            if ($rowAffected > 0){
                $movements = DB::table('dn_replace_det')
                ->leftJoin('dn_replace_hdr','dn_replace_hdr.replace_number','dn_replace_det.replace_number')
                ->leftJoin('article','article.article_code','dn_replace_det.article_code')
                ->where('dn_replace_det.replace_number',$replaceNumber)
                ->where('dn_replace_hdr.status','4')
                ->where('qty', '<>', 0)
                ->select(
                    DB::RAW("'$movementDate' as movement_date")
                    // 'dn_replace_hdr.rec_date as movement_date'
                    ,'dn_replace_det.article_code'
                    ,'article.article_desc'
                    ,DB::raw("0 as movement_min")
                    ,DB::RAW("(uom_conversion(dn_replace_det.uom_rec,article.uom)*dn_replace_det.qty) as movement_plus")
                    ,DB::raw("dn_replace_det.price as movement_price ")
                    ,'dn_replace_hdr.replace_number as movement_transnno'
                    ,DB::raw("'$moduleCode' as movement_type")
                    ,'dn_replace_hdr.po_number as movement_desc'
                )
                ->get();
                
                $dataSetMovement = [];
                foreach ($movements as $val) {
                    $dataSetMovement[] = [
                        'movement_date' => $val->movement_date,
                        'artikel_code' => $val->article_code,
                        'artikel_desc' => $val->article_desc,
                        'movement_min' => $val->movement_min,
                        'movement_plus' => $val->movement_plus,
                        'movement_price' => $val->movement_price,
                        'movement_transnno' => $val->movement_transnno,
                        'movement_type' => $val->movement_type,
                        'movement_desc' => $val->movement_desc,
                        'created_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'site_code' => $siteCode,
                        'location_number' => $location,
                        'last_qty' => DB::raw("get_last_qty('$val->article_code','$todayDate','$siteCode','$location') + ($val->movement_min+$val->movement_plus)")
                    ];
                }

                DB::table('movement')->insert($dataSetMovement);

                DB::statement("INSERT into kas_hdr (voucher_number,voucher_type,voucher_date,receive_from,amount,period,year,note,status,created_by,updated_by,created_at,updated_at,description)
                select replace_number as voucher_number
                ,'REC' as voucher_type
                ,do_date as voucher_date
                ,customer_id as receive_from
                ,(select sum((qty+qty_free)*price) from dn_replace_det where replace_number = dn_replace_hdr.replace_number) as amount
                ,substring(do_date,4,2)::integer as period
                ,substring(do_date,7) as year,note
                ,'3' as status
                ,created_by
                ,updated_by
                ,now()
                ,now()
                ,replace_number as description 
                from dn_replace_hdr
                where status = '4'
                and replace_number in (select replace_number
                from dn_replace_det
                left join article on article.article_code = dn_replace_det.article_code
                where article_type in ('RMP','CM1','CM2','RM'))
                and replace_number = '$replaceNumber'
                order by created_at");

                DB::statement("INSERT into kas_det (voucher_number,account,description,debit,created_by,updated_by,created_at,updated_at,cost_center) 
                select replace_number as voucher_number
                ,case when article_type='RMP' then '1100.31' when article_type='RM' then '1100.31' when article_type='CM1' then '1100.32.1' when article_type='CM2' then '1100.32.2' else '' end as account
                ,concat(replace_number,' ',article_desc) 
                ,(qty+qty_free)*price as debit
                ,dn_replace_det.created_by
                ,dn_replace_det.updated_by
                ,now()
                ,now()
                ,'003' as cost_center
                from dn_replace_det
                left join article on article.article_code = dn_replace_det.article_code
                where article_type in ('RMP','CM1','CM2','RM')
                and (qty+qty_free) > 0
                and replace_number in (select replace_number from dn_replace_hdr where status = '4' and replace_number = '$replaceNumber')
                order by dn_replace_det.created_at");

                $idKu = Crypt::encryptString($id);

                DB::commit();
                $title ="Posting $this->title";
                $alert  ="success";
                $message  = "$title $replaceNumber Successfully Posted";
                \LogActivity::addToLog($title,"username: $username Status $message");

                if($dariNew=='true'){
                    return response()->json(array('statusReplace' => $status, 'title' => $title, 'status' => 1, 'message' => $message,'alert'=>$alert,'replaceNumber'=>$replaceNumber,'idKu'=>$idKu));
                }else{
                    return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
                }      
                // return response()->json(array('statusReplace' => $statusReplace,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'replaceNumber'=>$replaceNumber));
            }else{

                $title ="Posting $this->title";
                $alert  ="warning";
                $message  = "$title $replaceNumber Failed to Posting";
                \LogActivity::addToLog($title,"username: $username Status $message");

                if($dariNew=='true'){
                    return response()->json(array('statusReplace' => $status, 'title' => $title, 'status' => 0, 'message' => $message,'alert'=>$alert,'replaceNumber'=>$replaceNumber,'idKu'=>$idKu));
                }else{
                    return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
                }     
                // return response()->json(array('statusReplace' => $statusReplace,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'replaceNumber'=>$replaceNumber));
            }
        }else{
            $title ="Posting $this->title";
            $alert  ="warning";
            $message  = "$title $replaceNumber Failed to Posting";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }
    }

    public function cancel(Request $request)
    {
        /*
            $data['status'] = ['1'=>'OPEN','2'=>'CLOSED','3'=>'CANCEL'];
            ['OPEN','CLOSED','CANCELED']; 
        */

        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $replaceNumber = DB::table('dn_replace_hdr')->where('id',$id)->value('replace_number');
        $status = '3';
        $moduleCode = $this->moduleCode;
        $reason = "(Cancel by $username, Reason: $request->reason)";
        $todayDate = date('Y-m-d');
        $movementDate = date("d-m-Y");
        
        DB::table('dn_replace_hdr')
        ->where('replace_number',$replaceNumber)
        ->update(
            [   
                'status' => $status,
                'return_number'=>DB::raw("CONCAT(po_number,';','(C)')") ,
                'note' => DB::raw("CONCAT(note,';','$reason')") ,
                'updated_by' => Auth::user()->username,
                'updated_at' => date('Y-m-d H:i:s')
            ]
        );

        DB::commit();
        $title ="Cancel $this->title";
        $alert  ="success";
        $message  = "$title $replaceNumber Successfully Canceled";
        \LogActivity::addToLog($title,"username: $username Status $message");
        return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
    }
  
    public function destroy(Request $request)
    {
       /*
            $data['status'] = ['1'=>'OPEN','2'=>'CLOSED','3'=>'CANCEL'];
            ['OPEN','CLOSED','CANCELED']; 
        */

        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);
        $leadCode = $this->moduleCode;
        
        $replaceNumber = DB::table('dn_replace_hdr')->where('id',$id)
        ->whereNotIn('status',['3'])
        ->value('replace_number');

        $urutan = (int)explode('-',$replaceNumber)[3];
        $urutanSebelum = (int)explode('-',$replaceNumber)[3] -1;

        $rowAffected = DB::table('dn_replace_hdr')->where('replace_number',$replaceNumber)->delete();

        if($rowAffected>0){
            DB::table('dn_replace_det')->where('replace_number',$replaceNumber)->delete();

            db::table('master_code')
            ->where('code_key',$leadCode)
            ->where('code_number',$urutan)
            ->update([
                'code_number' => $urutanSebelum
            ]);

            $title ="Delete $this->title";
            $alert  ="success";
            $message  = "$title $replaceNumber Successfully Deleted";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'title' => $title,'message'=> $message]);  
        }else{
            $title ="Delete $this->title";
            $alert  ="warning";
            $message  = "$title $replaceNumber Failed to Delete";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'title' => $title,'message'=> $message]);
        }
    }

    public function revision(Request $request)
    {
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $receiving=DB::table('dn_replace_hdr')->where('id',$id)->first();
        $recOrigin=$receiving->replace_number;
        $recStatus=$receiving->status;
        
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','7'=>'REVISED','10'=>'REVISI'];
        //  ['NEW','VALIDATE','APPROVED','POSTED','CANCELED','','','','','REVISI']; 
              
        $numRevision = $request->nR ? $request->nR +1 : 1 ;
        $numRevisionName = '-R'.$numRevision;
        $recNew = $recOrigin.$numRevisionName;
        $checkNewRec=DB::table('dn_replace_hdr')->where('replace_number',$recNew)->count();
        $reason = $request->reason;

        if ($checkNewRec > 0){
            $recNew = $recOrigin.'-R'.($numRevision+1);
        } 
                
        $sqlHdr = "INSERT into dn_replace_hdr 
        (
            replace_number,
            inv_number,
            inv_date,
            do_number,
            do_date,
            po_number,
            customer_id,
            rec_date,
            authorized_by,
            authorized_at,
            prepared_by,
            rec_type,
            status,
            note,
            created_by,
            updated_by,
            created_at,
            updated_at,
            origin_replace_number,
            num_revision,
            revised_by,
            revised_at,
            reason
        )
        select 
            '$recNew',
            inv_number,
            inv_date,
            do_number,
            do_date,
            po_number,
            customer_id,
            rec_date,
            authorized_by,
            authorized_at,
            prepared_by,
            rec_type,
            '7',
            note,
            created_by,
            '$username',
            created_at,
            '".date('Y-m-d H:i:s')."',
            '$recOrigin',
            $numRevision,
            '$username',
            '".date('Y-m-d H:i:s')."',
            '$reason'
        from dn_replace_hdr where replace_number = '$recOrigin'";

        $sqlDet="INSERT into dn_replace_det
        (
            replace_number,
            article_code,
            qty,
            uom_rec,
            qty_free,
            uom_free,
            price,
            created_by,
            updated_by,
            created_at,
            updated_at,
            pr_number
        )
        select 
            '$recNew',
            article_code,
            qty,
            uom_rec,
            qty_free,
            uom_free,
            price,
            created_by,
            '$username',
            created_at,
            '".date('Y-m-d H:i:s')."',
            pr_number
        from dn_replace_det where replace_number = '$recOrigin'";

        $rowAffected =  DB::select($sqlHdr);
        if ($rowAffected){
            DB::select($sqlDet);

            // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','7'=>'REVISED','10'=>'REVISI'];
            // ['NEW','VALIDATE','APPROVED','POSTED','CANCELED','','','','','REVISI']; 
        
            $rowAffected = DB::table('dn_replace_hdr')
            ->where('replace_number',$recOrigin)
            ->update(
                [
                    'num_revision' => $numRevision,
                    'status' => '10', //Revisi
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );

            if($rowAffected){
                if($recStatus == '4'){
                    $this->unPosting($recOrigin);
                }
            }

            DB::table('approval_history')
            ->where('module_number',$recOrigin)
            ->update(
                [
                    'module_number' => $recNew,
                    'status' => '0',
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );
            
            DB::table('kas_det')->where('voucher_number',$recOrigin)->delete();
            DB::table('kas_hdr')->where('voucher_number',$recOrigin)->delete();

            $title ="Save $this->title";
            $alert  ="success";
            $message  = "$title Revision Rec: $recOrigin to $recNew is successfully saved";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->route('dnReplace.edit', ['id'=>Crypt::encryptString($id)]);
        }else{
            $title ="Save $this->title";
            $alert  ="warning";
            $message  = "$title Revision Rec: $recOrigin to $recNew is failed to save";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
        }
        
    }

    public function unPosting($replaceNumber)
    {
        $username =  Auth::user()->username;
        $recType = "NORMAL";
        $siteCode = 'HO';
        $location ='WH';
        $moduleCode = $this->moduleCode;
        $todayDate = date('Y-m-d');
        $movementDate = date("d-m-Y");
        
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','7'=>'REVISED','10'=>'REVISI'];
        //  ['NEW','VALIDATE','APPROVED','POSTED','CANCELED','','','','','REVISI']; 
            
        if ($replaceNumber){
            $data = DB::table('dn_replace_det')
            ->leftJoin('dn_replace_hdr','dn_replace_hdr.replace_number','dn_replace_det.replace_number')
            ->leftJoin('article','article.article_code','dn_replace_det.article_code')
            ->where('dn_replace_det.replace_number',$replaceNumber)
            // ->where('dn_replace_hdr.status','3')
            ->select('dn_replace_det.*'
            ,'article.article_type'
            ,'article.uom as uom_article'
            ,DB::RAW("average_cost(dn_replace_det.article_code,'$siteCode','$location','$moduleCode') as average_cost")
            ,DB::RAW("(dn_replace_det.qty*uom_conversion(dn_replace_det.uom_rec,article.uom))+(dn_replace_det.qty_free*uom_conversion(dn_replace_det.uom_rec,article.uom)) as total_qty")
            )
            ->get();

            foreach($data as $val){
                //insert article code kalo belum ada di tabel item_stock
                DB::table('article_stock')
                ->updateOrInsert(
                    [   'site_code' =>$siteCode,
                        'article_code' => $val->article_code,
                        'location_number'=> $location
                    ],
                    [
                        'dept_code'=>$val->article_type,
                        'uom'=>$val->uom_article,
                    ]
                );

                //update qty nya dikurang qty lama karena di unpost
                $rowAffected = DB::table('article_stock')
                ->where('site_code',$siteCode)
                ->where('article_code',$val->article_code)
                ->where('location_number',$location)
                ->update([
                    'article_qty' => DB::raw('coalesce(article_qty,0) - '.$val->total_qty)
                ]);

            }
                                            
            $movements = DB::table('dn_replace_det')
            ->leftJoin('dn_replace_hdr','dn_replace_hdr.replace_number','dn_replace_det.replace_number')
            ->leftJoin('article','article.article_code','dn_replace_det.article_code')
            ->where('dn_replace_det.replace_number',$replaceNumber)
            // ->where('dn_replace_hdr.status','4')
            ->where('qty', '<>', 0)
            ->select(
                DB::RAW("'$movementDate' as movement_date")
                // 'dn_replace_hdr.rec_date as movement_date'
                ,'dn_replace_det.article_code'
                ,'article.article_desc'
                ,DB::RAW("(uom_conversion(dn_replace_det.uom_rec,article.uom)*dn_replace_det.qty) as movement_min")
                ,DB::raw("0 as movement_plus")
                ,DB::raw("dn_replace_det.price as movement_price ")
                ,'dn_replace_hdr.replace_number as movement_transnno'
                ,DB::raw("'$moduleCode' as movement_type")
                ,'dn_replace_hdr.po_number as movement_desc'
            )
            ->get();
            
            $dataSetMovement = [];
            foreach ($movements as $val) {
                $dataSetMovement[] = [
                    'movement_date' => $val->movement_date,
                    'artikel_code' => $val->article_code,
                    'artikel_desc' => $val->article_desc,
                    'movement_min' => $val->movement_min,
                    'movement_plus' => $val->movement_plus,
                    'movement_price' => $val->movement_price,
                    'movement_transnno' => $val->movement_transnno,
                    'movement_type' => $val->movement_type,
                    'movement_desc' => $val->movement_desc."(Revision)",
                    'created_by' => Auth::user()->username,
                    'created_at' => date('Y-m-d H:i:s'),
                    'site_code' => $siteCode,
                    'location_number' => $location,
                    'last_qty' => DB::raw("get_last_qty('$val->article_code','$todayDate','$siteCode','$location') - ($val->movement_min+$val->movement_plus)")
                ];
            }

            DB::table('movement')->insert($dataSetMovement);

            DB::table('kas_det')->where('voucher_number',$replaceNumber)->delete();
            DB::table('kas_hdr')->where('voucher_number',$replaceNumber)->delete();

            return 'true';
        }else{
            return 'false';
        }
    }

    public function list(Request $request)
    {
        // $data['status'] = ['1'=>'OPEN','2'=>'CLOSED','3'=>'CANCELED'];
        // ['OPEN','CLOSED','CANCELED']; 

        $searchReplace = strtolower($request->searchReplace);
        $searchReturn = strtolower($request->searchReturn);
        $searchCustomer = $request->searchCustomer;
        $searchStatus = $request->searchStatus;
        $replaceDate = $request->replaceDate;
        $doDate = $request->doDate;
        $fromDate ="";
        $toDate = "";

        if ($replaceDate){
            $date = explode("to",$replaceDate);
            if(count($date)>1){
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate; 
            }
        }

        $data = DB::table('dn_replace_hdr')
        ->where(function ($query) use ($searchReplace,$searchReturn,$searchCustomer,$searchStatus,$replaceDate,$fromDate,$toDate) {
            $searchReturn ? $query->where('return_number','ilike','%'.$searchReturn.'%') : '';
            $searchCustomer ? $query->where('customer_id','ilike','%'.$searchCustomer.'%') : '';
            $searchReplace ? $query->where('replace_number','ilike','%'.$searchReplace.'%') : '';
            $searchStatus ? $query->where('status',$searchStatus) : '';
            $replaceDate ? $query->whereBetween(DB::raw("to_date(replace_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
        })
        ->whereNotIn('status',['3'])
        ->select('dn_replace_hdr.*'
        ,DB::raw("(select nama from third_party where kode = dn_replace_hdr.customer_id limit 1) as customer_name")
        ,DB::raw("to_char(to_date(replace_date,'DD-MM-YYYY'),'DD-MM-YYYY') as tanggal_replace")
        )
        ->orderBy('id')
        ->get(); 
       
        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';

            // if (($data->status == '1') OR ($data->status == '2')){
            if ($data->status == '1'){
                if (Auth::user()->can('receiving-edit')) {
                    $buttons .=     '<a href="'. route('dnReplace.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="file-text"></i>
                                        <span>'. __("Edit") .'</span>
                                    </a>';
                }
            }

            // if ( in_array($data->status,['1','2','3','4']) ) {
            //     // if (Auth::user()->can('receiving-revision')) {
            //         $replaceDate = date('Y-m-d', strtotime($data->rec_date));
            //         if($replaceDate>=$lockDateToDate){
            //             $buttons .= "<a href='javascript:;'
            //                             id='revisionReasonButton'
            //                             class='dropdown-item'
            //                             data-toggle='modal'
            //                             data-target='#reasonModalRevision'
            //                             data-href='". route('dnReplace.revision', ['id'=>Crypt::encryptString($data->id),'nR'=>$data->num_revision]) ."'>
            //                             <i data-feather='corner-down-left' class='feather-14-red'></i>
            //                             <span>". __('Revision') ."</span>
            //                         </a>";
            //         }
            //     // }            
            // }

            // if ($data->status == '4'){
                $buttons .=         "<a href='". route('dnReplace.print', ['id'=>Crypt::encryptString($data->id)]) ."' target='_blank' class='dropdown-item'>
                                        <i data-feather='printer'></i>
                                        <span>". __('Print') ."</span>
                                    </a>";

            // }

            $buttons .=         '<a href="'. route('dnReplace.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    Detail
                                </a>';

            if ( $data->status == '2' ){
                if (Auth::user()->can('receiving-delete')) {
                    $buttons .=         "<a href='javascript:;'
                                            id='cancelReasonButton'
                                            class='dropdown-item'
                                            data-toggle='modal'
                                            data-target='#reasonModalCancel'
                                            data-href='". route("dnReplace.cancel", ["id"=>Crypt::encryptString($data->id)]) ."'>
                                            <i data-feather='corner-down-left' class='feather-14-red'></i>
                                            <span>". __('Cancel') ."</span>
                                        </a>";
                }
            }
            
            if ($data->status == '1'){
                if (Auth::user()->can('receiving-delete')) {
                    $buttons .=         "<a href='javascript:;'
                                        class='dropdown-item' 
                                        data-size='sm'
                                        data-ajax-delete='true'
                                        data-confirm='Are You Sure want to Delete?|This action can not be undone. Do you want to continue?' 
                                        data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                                        data-modal-id='".$data->id."'
                                        data-url='". route('dnReplace.destroy', ['id'=>Crypt::encryptString($data->id)]) ."'>
                                        <i data-feather='trash-2' class='feather-14-red'></i>
                                        <span>". __('Delete') ."</span>
                                    </a>";
                }
            }

            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-warning','badge-danger','badge-dark','badge-secondary','badge-success','badge-success','badge-success'];            
            $statusReplace = ['OPEN','CLOSED','CANCELED'];
            // $data['status'] = ['1'=>'OPEN','2'=>'CLOSED','3'=>'CANCELED'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusReplace[$data->status - 1]."</div>";
        })
        ->rawColumns(['action','status'])
        ->make(true);
    }

    public function listDetail(Request $request)
    {
        $searchReplace = strtolower($request->searchReplace);
        $searchReturn = strtolower($request->searchReturn);
        $searchCustomer = $request->searchCustomer;
        $searchStatus = $request->searchStatus;
        $replaceDate = $request->replaceDate;
        $doDate = $request->doDate;
        $fromDate ="";
        $toDate = "";

        if ($replaceDate){
            $date = explode("to",$replaceDate);
            if(count($date)>1){
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate; 
            }
        }

        $data = DB::table('dn_replace_det')
        ->leftJoin('dn_replace_hdr','dn_replace_hdr.replace_number','dn_replace_det.replace_number')      
        ->leftJoin('article','article.article_code','dn_replace_det.article_code')
        ->where(function ($query) use ($searchReplace,$searchReturn,$searchCustomer,$searchStatus,$replaceDate,$fromDate,$toDate) {
            $searchReturn ? $query->where('dn_replace_det.return_number','ilike','%'.$searchReturn.'%') : '';
            $searchCustomer ? $query->where('customer_id','ilike','%'.$searchCustomer.'%') : '';
            $searchReplace ? $query->where('dn_replace_det.replace_number','ilike','%'.$searchReplace.'%') : '';
            $searchStatus ? $query->where('dn_replace_hdr.status',$searchStatus) : '';
            $replaceDate ? $query->whereBetween(DB::raw("to_date(replace_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
        })
        ->where('dn_replace_det.qty','>',0)
        ->whereNotIn('dn_replace_hdr.status',['3'])
        ->select('dn_replace_det.*'
        ,'dn_replace_hdr.*'
        ,'dn_replace_hdr.created_by as created_by_1'
        ,'dn_replace_hdr.created_at as created_at_1'
        ,'article_alternative_code'
        ,'article_desc'
        ,DB::raw("(select nama from third_party where kode = dn_replace_hdr.customer_id limit 1) as customer_name")
        ,DB::raw("to_char(to_date(replace_date,'DD-MM-YYYY'),'DD-MM-YYYY') as tanggal_replace")
        )
        ->orderBy('dn_replace_det.id')
        ->get(); 
        
        return Datatables::of($data)
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-warning','badge-danger','badge-dark','badge-secondary','badge-success','badge-success','badge-success'];            
            $statusReplace = ['OPEN','CLOSED','CANCELED'];
            // $data['status'] = ['1'=>'OPEN','2'=>'CLOSED','3'=>'CANCELED'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusReplace[$data->status - 1]."</div>";
        })
        ->rawColumns(['status'])
        ->make(true);
    }

    public function print(Request $request)
    {
        $id=Crypt::decryptString($request->id);

        $data['companies']=DB::table('company')
        ->where('code','ASN')
        ->select('name as nama', 'address as alamat', DB::RAW('(select region_name from regions where region_code = city::integer)  as kota'),'tlp')
        ->get()->first();
                
        $recHdr=DB::table('dn_replace_hdr')
        ->leftJoin('dn_return_hdr','dn_return_hdr.return_number','dn_replace_hdr.return_number')
        ->select('dn_replace_hdr.*','dn_return_hdr.dn_number')
        ->where('dn_replace_hdr.id',$id)
        ->first();

        $data['replaceHdr']=DB::table('dn_replace_hdr')
        ->leftJoin('dn_return_hdr','dn_return_hdr.return_number','dn_replace_hdr.return_number')
        ->select('dn_replace_hdr.*','dn_return_hdr.dn_number')
        ->where('dn_replace_hdr.id',$id)
        ->first();

        $replaceNumber=$recHdr->replace_number;
       
        $data['details']=DB::table('dn_replace_det')
        ->leftJoin('article','article.article_code','dn_replace_det.article_code')
        ->where('replace_number',$replaceNumber)
        ->where('qty','>',0)
        ->get();

        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->where('kode',$recHdr->customer_id)
        ->orderBy('nama')
        ->first();
        
        $status = ['OPEN','CLOSED','CANCELED'];
        $data['status'] = $status[$recHdr->status-1];

        $data['no'] =0;

        $data['title'] =$replaceNumber;

        return view('dnReplace.print',$data);

        // view()->share($data);

        // $pdf = PDF::loadView('dnReplace.print');
        // return $pdf->stream("PO_$replaceNumber.pdf");

    }

    public function listReturn(Request $request)
    {
        $cust= $request->value;      
        $output="";

        $data= DB::table("dn_return_hdr") 
        ->where("customer_id",$cust)
        ->where("status","1")
        ->orderBy("return_number")
        ->select('return_number','dn_number')
        ->get();          

        if (count($data)>0){
            $output .='<option value="Choose PO">Choose DN</option>';            
            foreach ($data as $row){
                $output .='<option value="'.$row->return_number.'" data-dn= "'.$row->dn_number.'">'.$row->return_number.'</option>';            
            }
        }
        return $output;
    }

    public function returnDetail(Request $request)
    {
        $returnNumber = $request->value;
        $data = DB::select("SELECT 
        a.*,
        a.article_code,
        article_alternative_code,
        article_desc,
        (COALESCE(a.qty,0)) as tot_qty_return,
        (COALESCE(a.qty,0)-COALESCE(b.qty,0)) as qty_return
        ,a.uom
        from dn_return_det a
        left join article on article.article_code = a.article_code
        left join (select sum(qty) as qty,return_number, article_code from dn_replace_det where return_number = '$returnNumber' group by return_number, article_code) as b
        on a.article_code = b.article_code
        where a.return_number = '$returnNumber'
        order by a.id");


        return response()->json($data);
    }

}
