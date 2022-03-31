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

class BomController extends Controller
{
    private $title;
    public function __construct()
    {
        $this->title = "Bill Of Material";
    }

    public function index(Request $request)
    {
        $data['title'] = "$this->title";

        $data['articles'] = DB::table('article')
        ->leftJoin('third_party','article.third_party','third_party.kode')
        ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
        ->where('article_type','FG')
        ->select('article.*', 'third_party.nama as cust_name','group_materials.name as group')
        ->get();
       
        // status:
        // 1 = New
        // 2 = Validated
        // 3 = Authorized
        // 4 = Received
        // 5 = Canceled
        // 6 = closed
        // 7 = po

        $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'AUTHORIZED','4'=>'RECEIVED','5'=>'CANCELED','6'=>"CLOSE",'7'=>'PO'];
            
        return view("bom.index",$data);
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

        $newCode = str_pad($newCode,8,"0", STR_PAD_LEFT);
        $month = str_pad(date('n'),2,"0", STR_PAD_LEFT);
        $year = date('y');
        $bomNumber="$key$month$year$newCode";
        
        return $bomNumber;
    }

    public function create(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create $this->title";

        $data['articles'] = DB::table('article')
        ->leftJoin('third_party','article.third_party','third_party.kode')
        ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
        ->where('article_type','FG')
        ->select('article.*', 'third_party.nama as cust_name','group_materials.name as group')
        ->get();
        
        return view("bom.create",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request -> articles);
        $articleCode = $request->articleCode;
        $customer = $request->customer;
        $group = $request->group;
        $uom = $request->uom;
        $tag = $request->tag;
        $passRate = $request->passRate;
        $passThru = $request->passThru;
        $cycleTime = $request->cycleTime;
        $note = $request->note;

        $status = '1';
        $print_seq = 0;

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
            'articleCode'=>'required|unique:bom_hdr,article_code',
            'customer'  => 'required',
        ]);
        
        $error_array = array();
        $success_output = '';
        // return $validation;
        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }
            $title="Save BOM";
            $alert ="error";
            return response()->json(array('status' => 0,'title' => $title, 'message' => $error_array,'alert' =>$alert));
        }else{
            $bomNumber = $this->getLastCode('BOM');
            DB::beginTransaction();
            try {
                    DB::table('bom_hdr')->insert([
                        'bom_code' => $bomNumber,
                        'customer' => $customer,
                        'article_code' => $articleCode,
                        'uom' => $uom,
                        'group_of_material' => $group,
                        'status' => $status,
                        'tag' => $tag,
                        'pass_rate' => $passRate,
                        'pass_thru' => $passThru,
                        'cycle_time' => $cycleTime,
                        'note' => $note,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                    $dataSet = [];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            'bom_code' => $bomNumber,
                            'article_code' => $val->article_code,
                            'qty' => $val->qty,
                            'uom' => $val->uom,
                            // 'cost_price' => $val->price,
                            'article_type' => $val->type,
                            'customer_code' => $val->customer_code,
                            // 'note' => $val->note,
                            'status' => '1',
                            'created_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                        ];
                    }

                    DB::table('bom_det')->insert($dataSet);

                    DB::commit();
                    $title ='Save BOM';
                    $alert  ="success";
                    $message  = "$title $bomNumber is successfully saved";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'bomNumber'=>$bomNumber));

            } catch (Exception $e) {
                DB::rollBack();
                $title ='Save BOM';
                $alert  ="warning";
                $message  = "$title $bomNumber is failed to save";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'bomNumber'=>$bomNumber));
            }
        }
    }

    public function show(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['header'] = DB::table('bom_hdr')
        ->where('id',$id)
        ->get()->first();

        $data['detail'] = DB::table('bom_det')
        ->where('bom_code',$data['header']->bom_code)
        ->leftJoin('uom','uom.code','bom_det.uom')
        ->leftJoin('article_types','article_types.code','=','bom_det.article_type')
        ->select('bom_det.*', 'uom.uom_group as uom_group','article_types.name as type_name')
        ->orderBy('bom_det.id')
        ->get();       

        $data['articleHeader']= DB::table('article')
        ->leftJoin('third_party','article.third_party','third_party.kode')
        ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
        ->where('article.third_party',$data['header']->customer)
        ->select('article.*', 'third_party.nama as cust_name','group_materials.name as group')
        ->get();   

        $data['articles'] = DB::table('article') 
        ->leftJoin('article_types','article_types.code','=','article.article_type')
        ->leftJoin('uom','uom.code','article.uom')
        // ->whereNotIn('article_type',['FG','RM'])
        ->orderBy('article_desc')
        ->select('article.*','uom.uom_group as uom_group','article_types.name as type_name')
        ->get();

        return view("bom.show",$data);
        
    }

    public function edit(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['header'] = DB::table('bom_hdr')
        ->where('id',$id)
        ->get()->first();

        $data['detail'] = DB::table('bom_det')
        ->where('bom_code',$data['header']->bom_code)
        ->leftJoin('uom','uom.code','bom_det.uom')
        ->leftJoin('article_types','article_types.code','=','bom_det.article_type')
        ->select('bom_det.*', 'uom.uom_group as uom_group','article_types.name as type_name')
        ->orderBy('bom_det.id')
        ->get();       

        $data['articleHeader']= DB::table('article')
        ->leftJoin('third_party','article.third_party','third_party.kode')
        ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
        ->where('article.third_party',$data['header']->customer)
        ->select('article.*', 'third_party.nama as cust_name','group_materials.name as group')
        ->get();   

        $data['articles'] = DB::table('article') 
        ->leftJoin('article_types','article_types.code','=','article.article_type')
        ->leftJoin('uom','uom.code','article.uom')
        // ->whereNotIn('article_type',['FG','RM'])
        ->orderBy('article_desc')
        ->select('article.*','uom.uom_group as uom_group','article_types.name as type_name')
        ->get();

        return view("bom.edit",$data);
        
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $bomNumber = $request -> bomNumber;
        $articles = json_decode($request -> articles);
        $articleCode = $request->articleCode;
        $customer = $request->customer;
        $group = $request->group;
        $uom = $request->uom;
        $tag = $request->tag;
        $passRate = $request->passRate;
        $passThru = $request->passThru;
        $cycleTime = $request->cycleTime;
        $note = $request->note;

        $status = '1';
        $print_seq = 0;
        
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
            // 'articleCode'=>'required|unique:bom_hdr,article_code',
            'customer'  => 'required',
        ]);
        
        $error_array = array();
        $success_output = '';
        // return $validation;
        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }
            $alert ="alert-danger";
            return response()->json(array('status' => 0, 'message' => $error_array,'alert' =>$alert));
        }else{
            DB::beginTransaction();
            try {
                    $row_affected=DB::table('bom_hdr')
                    ->where('bom_code',$bomNumber)
                    ->update(
                        [
                            'bom_code' => $bomNumber,
                            'customer' => $customer,
                            'article_code' => $articleCode,
                            'uom' => $uom,
                            'group_of_material' => $group,
                            'status' => $status,
                            'tag' => $tag,
                            'pass_rate' => $passRate,
                            'pass_thru' => $passThru,
                            'cycle_time' => $cycleTime,
                            'note' => $note,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );

                    $dataset=[];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            $bomNumber.$val->article_code
                        ];
                        
                    }

                    //Delete kalo article tidak ada di po $bomNumber dan article nya $val->article_code
                    //berdasarkan 2 kondisi
                    DB::table('bom_det')
                        ->whereNotIn(DB::raw("CONCAT(bom_code,article_code)"),$dataSet)
                        ->where('bom_code',$bomNumber)
                        ->delete();

                    foreach ($articles as $val) {
                        DB::table('bom_det')
                        ->updateOrInsert(
                            ['bom_code' => $bomNumber,'article_code' => $val->article_code],
                            [
                                'bom_code' => $bomNumber,
                                'article_code' => $val->article_code,
                                'qty' => $val->qty,
                                'uom' => $val->uom,
                                // 'cost_price' => $val->price,
                                'article_type' => $val->type,
                                'customer_code' => $val->customer_code,
                                // 'note' => $val->note,
                                'created_by' => Auth::user()->username,
                                'created_at' => date('Y-m-d H:i:s'),
                            ]
                        );
                    }
                    
                    DB::commit();
                    $title ="Update $this->title";
                    $alert  ="success";
                    $message  = "$title $bomNumber is successfully updated";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1, 'message' => $message,'alert'=>$alert,'bomNumber'=>$bomNumber));

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Update $this->title";
                $alert  ="warning";
                $message  = "$title $bomNumber is failed to updated";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 0, 'message' => $message,'alert'=>$alert,'bomNumber'=>$bomNumber));
            }
        }
    }

    public function destroy(Request $request)
    {
        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);
        $bom_code = DB::table('bom_hdr')->where('id',$id)->where('status','1')->value('bom_code');
        $rowAffected = DB::table('bom_hdr')->where('id',$id)->delete();
        if($rowAffected>0){
            $title ="Delete $this->title";
            $alert  ="success";
            $message  = "$this->title $bom_code is successfully deleted";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  
       }else{
            DB::rollBack();
            $title ="Delete $this->title";
            $alert  ="warning";
            $message  = "$this->title $bom_code is failed to delete";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);            
        }
    }

    public function list(Request $request)
    {
        // status:
        // 1 = New
        // 2 = Validated
        // 3 = Authorized
        // 4 = Received
        // 5 = Canceled
        // 6 = closed
        // 7 = po

        $searchBom = strtolower($request->searchBom);
        $articleCode = $request->articleCode;

        $data = DB::table('bom_hdr')
        ->leftJoin('article','article.article_code','bom_hdr.article_code')
        ->where(function ($query) use ($searchBom,$articleCode) {
            $searchBom ? $query->where('bom_code','ilike','%'.$searchBom.'%') : '';
            $articleCode ? $query->where('bom_hdr.article_code','ilike','%'.$articleCode.'%') : '';
        })
        ->orderBy('bom_code')
        ->get(['bom_hdr.*',DB::raw("CONCAT(article.article_alternative_code,'-',article.article_desc) as article_des")]); 
       
        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            if (Auth::user()->can('bom-edit')) {
            $buttons .=         '<a href="'. route('bom.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
            // $buttons .=         '<a href="'. route('bom.print', ['id'=>$data->id]) .'" target="_blank" class="dropdown-item">
            //                         <i data-feather="printer"></i>
            //                         Print
            //                     </a>';
            }
            $buttons .=         '<a href="'. route('bom.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    Detail
                                </a>';
                
            if (Auth::user()->can('bom-delete')) {
            $buttons .=         '<a href="javascript:;"
                                    id="deleteButton"
                                    class="dropdown-item"
                                    data-toggle="modal"
                                    data-target="#smallModal"
                                    data-href="'. route("bom.destroy", ['id'=>Crypt::encryptString($data->id)]) .'">
                                    <i data-feather="trash-2" class="feather-14-red"></i>
                                    Delete
                                </a>';
            }
            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        
        ->addColumn('bom_code', function ($data) {
            return '<a href="'. route('bom.show', ['id'=>Crypt::encryptString($data->id)]) .'" ><span>'.$data->bom_code.'</span></a>';
        })

        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary'];
            $status = ['Active','Not Active'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$status[$data->status - 1]."</div>";
        })
        ->rawColumns(['action','status','bom_code'])
        ->make(true);
    }

    public function print(Request $request)
    {
        $id = $request -> id;

        $data['companies']= array(
            "nama"=> "PT ABIMANYU SEKAR NUSANTARA",
            "alamat"=> "KP. KARANG MULYA RT 014 RW 005 DESA CIKOPO",
            "kota" => "KEC. BUNGURSARI KAB. PURWAKARTA JAWA BARAT",
            "tlp" =>  ""
        );
        
        $data['suppliers']=array(
            'nama'=>'PT ABIMANYU SEKAR NUSANTARA',
            'alamat'=>'KP. KARANG MULYA RT 014 RW 005 DESA CIKOPO',
            'kota' =>'KEC. BUNGURSARI KAB. PURWAKARTA JAWA BARAT',
            'tlp' => ''
        );
        
        $poHdr=DB::table('bom_hdr')
        ->where('id',$id)
        ->first();

        $bomNumber=$poHdr -> po_number;
       

        $data['details']=DB::table('bom_det')
        ->leftJoin('article','article.article_code','bom_det.article_code')
        ->where('po_number',$bomNumber)
        ->get();

        $data['totals']=DB::select("SELECT *,(gross-discount)+ppn as netto from (
            select a.po_number,authorized_by,prepared_by,sum(qty) as qty,sum(qty*price) as gross,sum(discount) as discount,sum(a.ppn) as ppn from bom_det a
            left join bom_hdr b
            on a.po_number = b.po_number 
            where a.po_number = '$bomNumber'
            group by a.po_number,authorized_by,prepared_by) as oki");

        $data['suppliers']=DB::table('third_party')
        ->where('kode',$poHdr -> supplier_id)
        ->get();

        $data['keterangan']=$poHdr -> note;
        $data['prNumber'] =$bomNumber;
        $data['poDate'] =$poHdr -> po_date;
        $data['poTerm'] =$poHdr -> termin;
        $data['poDelDate'] =$poHdr -> delivery_date;
        
        $data['status'] ='1';
        $data['no'] =1;

        view()->share($data);

        $pdf = PDF::loadView('bom.print');
        return $pdf->stream("PO_$bomNumber.pdf");

    }
}
