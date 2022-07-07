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

class TransferInController extends Controller
{
    private $title;
    private $moduleCode;
    public function __construct()
    {
        $this->title = "Transfer In";
        $this->moduleCode = "TRIN";
    }

    public function getTableColoumn()
    {
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=> false,'searchable'=>false],
            ['data'=>'tr_number','name'=>'tr_number','title'=>'Tr Number'],
            ['data'=>'tr_date','name'=>'tr_date','title'=>'Date'],
            ['data'=>'tr_type','name'=>'tr_type','title'=>'Type'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'note','name'=>'note','title'=>'Note']
        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnDetail()
    {
        $kolom=
        [
            ['data'=>'tr_number','name'=>'tr_number','title'=>'TSO Code'],
            ['data'=>'tr_date','name'=>'tr_date','title'=>'Date'],
            ['data'=>'article_alternatif_code','name'=>'article_alternatif_code','title'=>'Article Code'],
            ['data'=>'article_desc','name'=>'article_desc','title'=>'Article desc'],
            ['data'=>'qty','name'=>'qty','title'=>'Qty'],
            ['data'=>'uom','name'=>'uom','title'=>'UOM'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created Date'],
            ['data'=>'updated_by','name'=>'updated_by','title'=>'Updated By'],
            ['data'=>'updated_at','name'=>'updated_at','title'=>'Updated Date']
            
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
        $months = ['I', 'II', 'III','IV','V', 'VI', 'VII', 'VIII','IX','X','XI','XII'];
        $month = $months[date('n')-1];
        $year = date('Y');
        $codeNumber="$key/$year/$month/$newCode";
        
        return $codeNumber;
    }

    public function index(Request $request)
    {
        $data['title'] = "$this->title";
        $data['subtitle'] = "$this->title";

        $data['kolom'] = $this->getTableColoumn();
        $data['kolomDetail'] = $this->getTableColoumnDetail();
        
        $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];
    
        return view("transferIn.index",$data);
    }

    public function create(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create $this->title";
        $data['oEdit']=false;

        return view("transferIn.create",$data);

    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request->articles);
        $trDate = $request->trDate;
        $trType = $this->moduleCode;
        $note = $request->note;
        $status = '1';
        $poLeadCode = $trType; 

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','5'=>'CANCELED'];

        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
            // 'iunique' => "Number has already been taken",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $validation = Validator::make($request->all(),$messages = [
            'trDate'  => 'required'
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
            $hasilUpdate = AppHelpers::resetCode($poLeadCode);
            $trNumber = $this->getLastCode($poLeadCode);
            DB::beginTransaction();
            try {
                    $rowAffected = DB::table('transfer_hdr')->insert([
                        'tr_number' => $trNumber,
                        'ref_number' => '' ,
                        'tr_date' => $trDate,
                        'tr_type' => $trType,
                        'status' => $status,
                        'note' => $note,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                    $dataSet = [];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            'tr_number' => $trNumber,
                            'article_code' => $val->article_code,
                            'qty' => $val->qty,
                            'uom' => $val->uom,
                            'created_by' => Auth::user()->username,
                            'updated_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                    }

                    if ($rowAffected){
                        DB::table('transfer_det')->insert($dataSet);
                    }

                    DB::commit();
                    $title ="Save $this->title";
                    $alert  ="success";
                    $message  = "$title $trNumber is successfully saved";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'trNumber'=>$trNumber,'oEdit'=>true));

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Save $this->title";
                $alert  ="warning";
                $message  = "$title $trNumber is failed to save";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'trNumber'=>$trNumber));

            }
        }
    }

    public function posting(Request $request)
    {
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $tr = DB::table('transfer_hdr')->where('id',$id)->where('status','3')->first();
        $trNumber = $tr->tr_number;
        $trType = $this->moduleCode;
        $siteCode = 'HO';
        $status = '4';
        $authorizedBy = Auth::user()->username;

        if ($trNumber){
            // Update stock kalo article nya udah ada
            $sqlUpdate = "UPDATE article_stock a set article_qty = COALESCE(a.article_qty,0) + COALESCE(b.qty,0)
            from (
            select art_code, (qty*factor_qty) as qty from 
            (
                select *,article.article_code as art_code,(select unit_factor from uom_con where unit_from = o.uom_tr and unit_to = article.uom) as factor_qty  from (
                select *,uom as uom_tr from transfer_det where tr_number in (
                select tr_number from transfer_hdr where tr_number = '$trNumber' and status = '3')) o
                left join article on article.article_code = o.article_code
            ) c
            ) b
            where a.article_code=b.art_code and site_code ='$siteCode'";

            //Insert ke article stock kalo article nya belum ada
            $sqlInsert = "INSERT into article_stock (site_code,article_code,dept_code,location_number,article_qty,uom)
            select '$siteCode',art_code,article_type,'00',(qty*factor_qty) as qty,uom_tr from 
            (
                select *,article.article_code as art_code,(select unit_factor from uom_con where unit_from = z.uom_tr and unit_to = article.uom) as factor_qty from (
                select *,uom as uom_tr from transfer_det where tr_number in (
                select tr_number from transfer_hdr where tr_number = '$trNumber' and status = '3')) z
                left join article on article.article_code = z.article_code
                where article.article_code not in (select article_code from article_stock)
            ) y";

            //Insert into table movement
            $sqlMovement = "INSERT into movement
            (movement_date,artikel_code,artikel_desc,movement_min,movement_plus,movement_price,movement_transnno,movement_type,movement_desc)
            select 
            now()::timestamp::date,
            article_code,
            (select concat(article_alternative_code,'-',article_desc) from article where article_code = a.article_code) as article_desc,
            0,
            qty,
            price,
            tr_number,
            '$trType',
            (select note from transfer_hdr where tr_number=a.tr_number) as note 
            from transfer_det a where tr_number in (
            select tr_number from transfer_hdr where tr_number = '$trNumber' and status = '4' and qty <> 0)";
        
            DB::select($sqlUpdate);
            $rowAffected = DB::select($sqlInsert);
            
            if ($rowAffected > 0){
                DB::table('transfer_hdr')
                ->where('tr_number',$trNumber)
                ->update(
                    [   
                        'status' => $status,
                        // 'authorized_by' => $authorizedBy,
                        // 'authorized_at' => date('Y-m-d H:i:s'),
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                DB::select($sqlMovement);

                DB::commit();
                $title ="Posting $this->title";
                $alert  ="success";
                $message  = "$title $trNumber Successfully Posted";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
                // return response()->json(array('statusRec' => $statusRec,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'trNumber'=>$trNumber));
            }else{
                $title ="Posting $this->title";
                $alert  ="warning";
                $message  = "$title $trNumber Failed to Posting";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
                // return response()->json(array('statusRec' => $statusRec,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'trNumber'=>$trNumber));
            }
        }else{
            $title ="Posting $this->title";
            $alert  ="warning";
            $message  = "$title $trNumber Failed to Posting";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }
    }

    public function cancel(Request $request)
    {
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $tr = DB::table('transfer_hdr')->where('id',$id)->where('status','4')->first();
        $trNumber = $tr->tr_number;
        $trType = $this->moduleCode;
        $siteCode = 'HO';
        $status = '5';
        $authorizedBy = Auth::user()->username;

        // Update stock kalo article nya udah ada
        $sqlUpdate = "UPDATE article_stock a set article_qty = COALESCE(a.article_qty,0) - COALESCE(b.qty,0)
        from (
        select art_code, (qty*factor_qty) as qty from 
        (
            select *
            ,article.article_code as art_code
            ,(select unit_factor from uom_con where unit_from = o.uom_tr and unit_to = article.uom) as factor_qty  
            from (
            select *,uom as uom_tr from transfer_det where tr_number in (
            select tr_number from transfer_hdr where tr_number = '$trNumber' and status = '4' )
            ) o
            left join article on article.article_code = o.article_code
        ) c
        ) b
        where a.article_code=b.art_code and site_code ='$siteCode'";

        //Insert ke article stock kalo article nya belum ada
        $sqlInsert = "INSERT into article_stock (site_code,article_code,dept_code,location_number,article_qty,uom)
        select '$siteCode',art_code,article_type,'00',(qty*factor_qty)*-1 as qty,uom_tr from 
        (
            select *
            ,article.article_code as art_code
            ,(select unit_factor from uom_con where unit_from = z.uom_tr and unit_to = article.uom) as factor_qty 
            from (
            select *,uom as uom_tr from transfer_det where tr_number in (
            select tr_number from transfer_hdr where tr_number = '$trNumber' and status = '4')
            ) z
            left join article on article.article_code = z.article_code
            where article.article_code not in (select article_code from article_stock)
        ) y";

        //Insert into table movement
        $sqlMovement = "INSERT into movement
        (movement_date,artikel_code,artikel_desc,movement_min,movement_plus,movement_price,movement_transnno,movement_type,movement_desc)
        select 
        now()::timestamp::date,
        article_code,
        (select concat(article_alternative_code,'-',article_desc) from article where article_code = a.article_code) as article_desc,
        qty,
        0,
        price,
        tr_number,
        '$trType',
        'CANCEL'
        from transfer_det a where tr_number in (
        select tr_number from transfer_hdr where tr_number = '$trNumber' and status = '5' and qty <> 0)";
    
        DB::select($sqlUpdate);
        $rowAffected = DB::select($sqlInsert);
        
        if ($rowAffected > 0){
            DB::table('transfer_hdr')
            ->where('tr_number',$trNumber)
            ->update(
                [   
                    'status' => $status,
                    // 'authorized_by' => $authorizedBy,
                    // 'authorized_at' => date('Y-m-d H:i:s'),
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );

            DB::select($sqlMovement);

            DB::commit();
            $title ="Cancel $this->title";
            $alert  ="success";
            $message  = "$title $trNumber Successfully Canceled";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }else{
            $title ="Cancel $this->title";
            $alert  ="warning";
            $message  = "$title $trNumber Failed to Cancel";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }
    }

    public function show(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;
        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";

        $data['header'] = DB::table('transfer_hdr')
        ->where('id',$id)
        ->where('tr_type',$this->moduleCode)
        ->select('transfer_hdr.*'
        ,DB::raw('(select count(*) from transfer_det where tr_number = transfer_hdr.tr_number) as sum_row')
        ,DB::raw('(select sum(qty) from transfer_det where tr_number = transfer_hdr.tr_number) as sum_qty'))

        ->get()->first();
        
        $trNumber = $data['header']->tr_number;
        
        $data['details'] = DB::table('transfer_det')
        ->leftJoin('article','article.article_code','=','transfer_det.article_code')
        ->leftJoin('uom','uom.code','transfer_det.uom')
        ->where('tr_number',$trNumber)
        ->select('transfer_det.*'
        ,'uom.uom_group as uom_group'
        ,DB::raw("concat(article.article_alternative_code,'-',article.article_desc) as article")
        )
        ->orderBy('id')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$trNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$trNumber,$username);
        
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'','5'=>'CANCELED'];
        $statusTr = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED'];
        $data['statusTr'] = $statusTr[$data['header']->status-1];
       
        return view("transferIn.show",$data);        
    }

    public function showEdit($key)
    {
        $id=Crypt::decryptString($key);
        $username =  Auth::user()->username;
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['header'] = DB::table('transfer_hdr')
        ->where('id',$id)
        ->where('tr_type',$this->moduleCode)
        ->get()->first();
        
        $trNumber = $data['header']->tr_number;
        
        $data['details'] = DB::table('transfer_det')
        ->where('tr_number',$trNumber)
        ->select('transfer_det.*'
        ,DB::RAW("(select string_agg(unit_to,',' order by unit_from) as uom_member from uom_con where unit_from = transfer_det.uom)")
        )
        ->orderBy('id')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$trNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$trNumber,$username);
        
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'','5'=>'CANCELED'];
        $statusTr = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED'];
        $data['statusTr'] = $statusTr[$data['header']->status-1];

        $data['oEdit']=true;

        return view("transferIn.edit",$data);
    }

    public function edit(Request $request)
    {
        return $this->showEdit($request->id);
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request->articles);
        $trNumber = $request->trNumber;
        $trDate = $request->trDate;
        $trType = $this->moduleCode;
        $note = $request->note;
        $status = '1';
        $poLeadCode = $trType; 
              
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','5'=>'CANCELED'];

        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
            // 'iunique' => "Number has already been taken",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $validation = Validator::make($request->all(),$messages = [
            'trDate'  => 'required'
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
                $rowAffected=DB::table('transfer_hdr')
                ->where('tr_number',$trNumber)
                ->update(
                    [
                        'ref_number' => '' ,
                        'tr_date' => $trDate,
                        'tr_type' => $trType,
                        'status' => $status,
                        'note' => $note,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]
                );

                $dataset=[];
                foreach ($articles as $val) {
                    $dataSet[] = [
                        $trNumber.$val->article_code
                    ];
                    
                }

                //Delete kalo article tidak ada di tr $trNumber dan article nya $val->article_code
                //berdasarkan 2 kondisi
                DB::table('transfer_det')
                    ->whereNotIn(DB::raw("CONCAT(tr_number,article_code)"),$dataSet)
                    ->where('tr_number',$trNumber)
                    ->delete();

                foreach ($articles as $val) {
                    DB::table('transfer_det')
                    ->updateOrInsert(
                        ['tr_number' => $trNumber,'article_code' => $val->article_code],
                        [
                            'tr_number' => $trNumber,
                            'article_code' => $val->article_code,
                            'qty' => $val->qty,
                            'uom' => $val->uom,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );
                }
                                        
                DB::commit();

                $title ="Save $this->title";
                $alert  ="success";
                $message  = "$title $trNumber is successfully updated";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'trNumber'=>$trNumber,'oEdit'=>true));

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Save $this->title";
                $alert ="warning";
                $message  = "$title $trNumber is failed to updated";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'trNumber'=>$trNumber));
            }
        }

    }

    public function approve(Request $request)
    {
        $username =  Auth::user()->username;
        $trNumber = $request->trNumber;
        $statusLevelApproval = Approval::approvalLevelPosition($this->moduleCode,$trNumber,$username);        
        $nextLevel = $statusLevelApproval[0]->next_level;
        $statusTso = $statusLevelApproval[0]->next_level == $statusLevelApproval[0]->max_level ? '3' :'2';
                
        DB::beginTransaction();
        try {
                $row_affected=DB::table('transfer_hdr')
                ->where('tr_number',$trNumber)
                ->update(
                    [
                        'status' => $statusTso,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                if ($row_affected){
                    DB::table('approval_history')->insert([
                        'module_code' => $this->moduleCode,
                        'module_number' => $trNumber,
                        'username' => Auth::user()->username,
                        'approval_order' => $nextLevel,
                        'approval_date' => date('Y-m-d'),
                        'status' => 1,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
                
                DB::commit();
                $title ="Approve $this->title";
                $alert  ="success";
                $message  = "$title $trNumber is successfully Approve-".$nextLevel;
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('statusPo' => $statusTso,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'trNumber'=>$trNumber));

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Approve $this->title";
            $alert  ="warning";
            $message  = "$title $trNumber is failed to Approve-".$nextLevel;
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('statusPo' => $statusTso,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'trNumber'=>$trNumber));
        }
    }

    public function destroy(Request $request)
    {
        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);
        $trNumber = DB::table('transfer_hdr')->where('id',$id)->where('status','1')->first();
        $trNumber = $trNumber->tr_number;
        $rowAffected = DB::table('transfer_hdr')->where('id',$id)->where('status','1')->delete();
        
        if($rowAffected>0){
            DB::table('transfer_det')->where('tr_number',$trNumber)->delete();
            $title ="Delete $this->title";
            $alert  ="success";
            $message  = "$title $trNumber Successfully Deleted";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);  
        }else{
            $title ="Delete $this->title";
            $alert  ="warning";
            $message  = "$title $trNumber Failed to Delete";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }
    }
   
    public function list(Request $request)
    {
        $username = Auth::user()->username;
        $searchTr = strtolower($request->searchTr);
        $searchType = $request->searchType;
        $searchStatus = $request->searchStatus;
        $trDate = $request->trDate;
        $fromDate ="";
        $toDate = "";
        if ($trDate){
            $date = explode("to",$trDate);
            $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
            $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
        }

        $data = DB::table('transfer_hdr')
        ->where(function ($query) use ($searchTr,$searchStatus,$trDate,$fromDate,$toDate,$searchType) {
            $searchType ? $query->where('tr_type',$searchType) : '';
            $searchTr ? $query->where('tr_number','ilike','%'.$searchTr.'%') : '';
            $searchStatus ? $query->where('status',$searchStatus) : '';
            $trDate ? $query->whereBetween(DB::raw("to_date(tr_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
        })
        ->where('tr_type','TRIN')
        ->orderBy('id')
        ->get(); 
       
        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            
            if ( $data->status == '1' or $data->status == '2') {
                if (Auth::user()->can('transferIn-approve')) {
                $buttons .=         '<a href="'. route('transferIn.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="file-text"></i>
                                        <span>'. __("Approve") .'</span>
                                    </a>';
                }
            }

            if ( $data->status == '3' ) {
                // $buttons .=         '<a href="'. route('transferIn.posting', ['trNumber'=>$data->tr_number]) .'" class="dropdown-item">
                //                         <i data-feather="check"></i>
                //                         <span>'. __("Posting") .'</span>
                //                     </a>';
                // }
                
                if (Auth::user()->can('transferIn-posting')) {
                    $buttons .="<a href='javascript:;'
                    class='dropdown-item' 
                    data-size='sm'
                    data-ajax-delete='true'
                    data-confirm='Are You Sure want to post This number?' 
                    data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                    data-modal-id='".$data->id."'
                    data-url='". route('transferIn.posting', ['id'=>Crypt::encryptString($data->id)]) ."'>
                    <i data-feather='check' class='feather-14-red'></i>
                    <span>". __('Posting') ."</span>
                    </a>";
                }
                
            }
            
            if ( $data->status == '1' or $data->status == '2' ){
                if (Auth::user()->can('transferIn-edit')) {
                $buttons .=         '<a href="'. route('transferIn.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="file-text"></i>
                                        <span>'. __("Edit") .'</span>
                                    </a>';
                }
            }
  
            if ( $data->status == '4' ){
                if (Auth::user()->can('transferIn-delete')) {
                    $buttons .="<a href='javascript:;'
                    class='dropdown-item' 
                    data-size='sm'
                    data-ajax-delete='true'
                    data-confirm='Are You Sure want to Cancel?|This action can not be undone. Do you want to continue?' 
                    data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                    data-modal-id='".$data->id."'
                    data-url='". route('transferIn.cancel', ['id'=>Crypt::encryptString($data->id)]) ."'>
                    <i data-feather='corner-down-left' class='feather-14-red'></i>
                    <span>". __('Cancel') ."</span>
                    </a>";
                }
            }

            $buttons .= '<a href="'. route('transferIn.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                            <i data-feather="list"></i>
                            <span>'. __("Detail") .'</span>
                        </a>';

            if ( $data->status != '4' or $data->status != '5' ){
                if (Auth::user()->can('transferIn-delete')) {
                    $buttons .=         "<a href='javascript:;'
                                        class='dropdown-item' 
                                        data-size='sm'
                                        data-ajax-delete='true'
                                        data-confirm='Are You Sure want to Delete?|This action can not be undone. Do you want to continue?' 
                                        data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                                        data-modal-id='".$data->id."'
                                        data-url='". route('transferIn.destroy', ['id'=>Crypt::encryptString($data->id)]) ."'>
                                        <i data-feather='trash-2' class='feather-14-red'></i>
                                        <span>". __('Delete') ."</span>
                                    </a>";
                }
            }
            
            $buttons .=     '<a href="'. route('transferIn.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
                                <i data-feather="printer"></i>
                                <span>'. __("Print") .'</span>
                            </a>';

            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        ->addColumn('tr_number', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger'];            
            // $statusTr = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED'];
            // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];
            return '<span style="display: none;">'.$data->tr_number.'</span><a class="text-left badge d-block '.$badges[$data->status - 1].'" name="'.$data->tr_number.'" href="'. route('transferIn.show', ['id'=>Crypt::encryptString($data->id)]) .'" ><span>'.$data->tr_number.'</span></a>';
        })
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger'];            
            $statusTr = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusTr[$data->status - 1]."</div>";
        })
        ->rawColumns(['action','status','tr_number'])
        ->make(true);
    }

    public function listDetail(Request $request)
    {
        $searchTr = strtolower($request->searchTr);
        $username = Auth::user()->username;
        $searchType = $request->searchType;
        $searchStatus = $request->searchStatus;
        $trDate = $request->trDate;
        $fromDate ="";
        $toDate = "";
        
        if ($trDate){
            $date = explode("to",$trDate);
            $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
            $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
        }

        $data = DB::table('transfer_det')
        ->leftJoin('transfer_hdr','transfer_hdr.tr_number','transfer_det.tr_number')
        ->leftJoin('article','article.article_code','transfer_det.article_code')
        ->leftJoin('uom','uom.code','transfer_det.uom')
        ->where(function ($query) use ($searchTr,$searchStatus,$trDate,$fromDate,$toDate,$searchType) {
            $searchType ? $query->where('tr_type',$searchType) : '';
            $searchTr ? $query->where('tr_number','ilike','%'.$searchTr.'%') : '';
            $searchStatus ? $query->where('transfer_hdr.status',$searchStatus) : '';
            $trDate ? $query->whereBetween(DB::raw("to_date(tr_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
        })
        ->where('tr_type','TRIN')
        ->select('transfer_det.*'
        ,'transfer_hdr.*'
        ,'article_alternative_code'
        ,'article.article_desc'
        ,'uom_group'
        // ,DB::raw("case when uom_group = 'PIECE' then TO_CHAR(qty,'999,999,999') when uom_group <> 'PIECE' then TO_CHAR(qty,'999,999,999.999') end as qty")
        )
        ->orderBy('transfer_det.id')
        ->get(); 
       
        return Datatables::of($data)
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger'];            
            $statusTso = ['NEW','VALIDATED','POSTED','APPROVED'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusTso[$data->status - 1]."</div>";
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
            
        $trHdr=DB::table('transfer_hdr')
        ->where('id',$id)
        ->first();

        $trNumber=$trHdr->tr_number;
    
        $data['details']=DB::table('transfer_det')
        ->leftJoin('article','article.article_code','transfer_det.article_code')
        ->where('tr_number',$trNumber)
        ->get();

        // $data['totals']=DB::select("SELECT *,(gross-discount)+ppn as netto from (
        //     select a.tr_number,authorized_by,validate_by,sum(qty) as qty,sum(qty*price) as gross,sum(discount) as discount,sum(qty*price*b.ppn/100) as ppn from purchase_order_det a
        //     left join purchase_order_hdr b
        //     on a.tr_number = b.tr_number 
        //     where a.tr_number = '$trNumber'
        //     group by a.po_number,authorized_by,validate_by) as oki");

        $data['keterangan']=$trHdr->note;
        $data['trNumber'] =$trNumber;
        $data['trDate'] =$trHdr->tr_date;
        $data['no'] = 0;
        $statusTr = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED'];
        $data['status'] = $statusTr[$trHdr->status-1];
        $data['createdBy'] = $trHdr->created_by;

        $data['approved'] = DB::table('approval_history')
        ->leftJoin('users','users.username','approval_history.username')
        ->where('module_number',$trNumber)
        ->orderBy('approval_order','desc')
        ->value('users.name');
        
        view()->share($data);

        $pdf = PDF::loadView('transferIn.print');
        return $pdf->stream("$trNumber.pdf");

    }

}
