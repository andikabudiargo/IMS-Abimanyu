<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Response;
use App\Permission;
use DataTables;
use DB;
use PDF;
use AppHelpers;

class DeliveryController extends Controller
{

    private $title;
    public function __construct()
    {
        $this->title = "Delivery";
    }

    public function index(Request $request)
    {
        $data['title'] = $this->title;

        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        // status
        // 1. Draft
        // 2. Update
        // 3. Posting
        // 4. Cancel

        $data['status'] = ['1'=>'Draft','2'=>'Update','3'=>'Posting','4'=>'Cancel'];
            
        return view("delivery.index",$data);
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
        // $months = ['I', 'II', 'III','IV','V', 'VI', 'VII', 'VIII','IX','X','XI','XII'];
        $months = ['01', '02', '03','04','05', '06', '07', '08','09','10','11','12'];
        $month = $months[date('n')-1];
        $year = date('y');
        $code="$key/ASN/$year/$month/$newCode";
        
        return $code;
    }

    public function create(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create $this->title";
        
        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        return view("delivery.create",$data);
    }

    public function soDetail(Request $request)
    {
        $so = $request->value;
        $data = DB::select("SELECT * ,
                round(qty) as qty_so,
                a.article_code,
                article_alternative_code,
                article_desc
                from sales_order_det a
                left join uom on uom.code=a.uom
                left join article on article.article_code = a.article_code
                where so_code = '$so'
                ");

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request -> articles);
        $dnDate = $request->dnDate;
        $customer = $request->customer;
        $soNumber = $request->soNumber;
        $note = $request->note;
        $status = '1';
        $gudang = 'false';
        $kurs = 1;

        // status
        // 1. Draft
        // 2. Update
        // 3. Posting
        // 4. Cancel

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
            // 'poNumber'=>'required|unique:sales_order_hdr,po_number',
            // 'dnNumber' => 'required',
            'dnDate'  => 'required',
            'customer'  => 'required',
        ]);
        
        $error_array = array();
        $success_output = '';
        // return $validation;
        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }
            $title="Save  $this->title";
            $alert ="error";
            return response()->json(array('status' => 0,'title' => $title, 'message' => $error_array,'alert' =>$alert));
        }else{
            $hasilUpdate = AppHelpers::resetCode('DN');
            $dnCode = $this->getLastCode('DN');
            DB::beginTransaction();
            try {
                    DB::table('delivery_hdr')->insert([
                        'delivery_number' => $dnCode,
                        'delivery_date' => $dnDate,
                        'customer_id' => $customer,
                        'so_number' => $soNumber,
                        'po_number' => '',
                        'status' => $status,
                        'note' =>  $note,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                    $dataSet = [];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            'delivery_number' => $dnCode,
                            'article_code' => $val->article_code,
                            'so_number' => $val->so_number,
                            'qty' => $val->qty,
                            'uom' => $val->uom,
                            'created_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                        ];
                    }

                    DB::table('delivery_det')->insert($dataSet);

                    DB::commit();
                    $title ="Save $this->title";
                    $alert  ="success";
                    $message  = "$title $dnCode is successfully saved";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'dnNumber'=>$dnCode));

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Save $this->title";
                $alert  ="warning";
                $message  = "$title $dnCode is failed to save";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 0,'title' => $title, 'message' => $message,'alert'=>$alert,'dnNumber'=>$dnCode));
            }
        }
    }

    public function show(Request $request)
    {
        $id=$request->id;
        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";

        $data['header'] = DB::table('delivery_hdr')
        ->where('id',$id)
        ->get()->first();

        $deliveryNumber = $data['header']->delivery_number;

        $data['detail'] = DB::table('delivery_det')
        ->leftJoin('article','article.article_code','=','delivery_det.article_code')
        ->leftJoin('uom','delivery_det.uom','uom.code')
        ->where('delivery_det.delivery_number',$deliveryNumber)
        ->orderBy('delivery_det.id')
        ->get();

        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();

        $statusDel = ['Draft','Update','Posting','Cancel'];
        $data['statusDel'] = $statusDel[$data['header']->status-1];

        return view("delivery.show",$data);
        
    }

    public function edit(Request $request)
    {
        $id=$request->id;
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['header'] = DB::table('delivery_hdr')
        ->where('id',$id)
        ->get()->first();

        $deliveryNumber = $data['header']->delivery_number;

        $data['detail'] = DB::table('delivery_det')
        ->leftJoin('article','article.article_code','=','delivery_det.article_code')
        ->leftJoin('uom','delivery_det.uom','uom.code')
        ->where('delivery_det.delivery_number',$deliveryNumber)
        ->orderBy('delivery_det.id')
        ->get();

        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();

        $statusDel = ['Draft','Update','Posting','Cancel'];
        $data['statusDel'] = $statusDel[$data['header']->status-1];

        return view("delivery.edit",$data);
        
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request -> articles);
        $dnDate=$request->dnDate;
        $customer=$request->customer;
        $soNumber=$request->soNumber;
        $dnNumber=$request->dnNumber;
        $note=$request->note;
        $status = '2';
        $poNumber =""; //ini nanti hapus
        
        // status
        // 1. Draft
        // 2. Update
        // 3. Posting
        // 4. Cancel

        $customMessages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
            'iunique' => "Invoice : $dnNumber has already been taken on PO : $poNumber",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) use ($poNumber) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            $column2 = $query->getGrammar()->wrap($parameters[2]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])
                          ->whereRaw("lower({$column2}) = lower(?)", [$poNumber])->count();
        });
        
        $validation = Validator::make($request->all(),$messages = [
            // 'dnNumber'=>'required|iunique:receiving_hdr,inv_number,po_number',
            'dnDate'  => 'required',
            'dnNumber'  => 'required',
            // 'supplier'  => 'required',
        ],$customMessages);
                
        $error_array = array();
        $success_output = '';
        // return $validation;
        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }
            $title="Update  $this->title";
            $alert ="error";
            return response()->json(array('status' => 0,'title' => $title, 'message' => $error_array,'alert' =>$alert));
        }else{
            DB::beginTransaction();
            try {
                    $row_affected=DB::table('delivery_hdr')
                    ->where('delivery_number',$dnNumber)
                    ->update(
                        [   
                        'delivery_date' => $dnDate,
                        'customer_id' => $customer,
                        'so_number' => $soNumber,
                        'po_number' => '',
                        'status' => $status,
                        'note' =>  $note,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );

                    $dataset=[];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            $dnNumber.$val->article_code
                        ];
                        
                    }

                    //Delete kalo article tidak ada di po $poNumber dan article nya $val->article_code
                    //berdasarkan 2 kondisi
                    DB::table('delivery_det')
                        ->whereNotIn(DB::raw("CONCAT(delivery_number,article_code)"),$dataSet)
                        ->where('delivery_number',$dnNumber)
                        ->delete();
                                  
                    foreach ($articles as $val) {
                        DB::table('delivery_det')
                        ->updateOrInsert(
                            ['delivery_number' => $dnNumber,'article_code' => $val->article_code],
                            [
                                'delivery_number' => $dnNumber,
                                'article_code' => $val->article_code,
                                'so_number' => $val->so_number,
                                'qty' => $val->qty,
                                'uom' => $val->uom,
                                'updated_by' => Auth::user()->username,
                                'updated_at' => date('Y-m-d H:i:s')
                            ]
                        );
                    }
                                                                
                    DB::commit();
                    $title ="Update $this->title";
                    $alert  ="success";
                    $message  = "$title $dnNumber is successfully updated";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'dnNumber'=>$dnNumber));
            } catch (Exception $e) {
                DB::rollBack();
                $title ="Update $this->title";
                $alert ="warning";
                $message  = "$title $dnNumber is failed to update";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 0,'title' => $title, 'message' => $message,'alert'=>$alert,'dnNumber'=>$dnNumber));
            }
        }
    }

    // public function posting(Request $request)
    // {
    //     // status
    //     // 1. Draft
    //     // 2. Update
    //     // 3. Posting
    //     // 4. Cancel

    //     $username =  Auth::user()->username;
    //     $recNumber = $request->recNumber;
    //     $recType = "NORMAL";
    //     $statusRec ="Posting";
    //     $status = '3';
    //     $authorizedBy = Auth::user()->username;

    //     // Update stock kalo article nya udah ada
    //     $sqlUpdate = "UPDATE article_stock a set article_qty = COALESCE(a.article_qty,0)  + COALESCE(b.qty,0)
    //     from (
    //     select art_code, (qty*factor_qty)+(qty_free*factor_free) as qty from 
    //     (
    //         select *,article.article_code as art_code,(select unit_factor from uom_con where unit_from = o.uom_rec and unit_to = article.uom) as factor_qty,(select unit_factor from uom_con where unit_from = o.uom_free and unit_to = article.uom) as factor_free  from (
    //         select * from receiving_det where rec_number in (
    //         select rec_number from receiving_hdr where rec_number = '$recNumber' and (status != '3' and status != '4'))) o
    //         left join article on article.article_code = o.article_code
    //     ) c
    //     ) b
    //     where a.article_code=b.art_code";

    //     //Insert ke stock kalo article nya belum ada
    //     $sqlInsert = "INSERT into article_stock (site_code,article_code,dept_code,location_number,article_qty,uom)
    //     select 'HO',art_code,article_type,'00',(qty*factor_qty)+(qty_free*factor_free) as qty,uom from 
    //     (
    //         select *,article.article_code as art_code,(select unit_factor from uom_con where unit_from = z.uom_rec and unit_to = article.uom) as factor_qty,(select unit_factor from uom_con where unit_from = z.uom_free and unit_to = article.uom) as factor_free  from (
    //         select * from receiving_det where rec_number in (
    //         select rec_number from receiving_hdr where rec_number = '$recNumber' and (status != '3' and status != '4'))) z
    //         left join article on article.article_code = z.article_code
    //         where article.article_code not in (select article_code from article_stock)
    //     ) y";

    //     //Insert into table movement
    //     $sqlMovement = "INSERT into movement
    //     (movement_date,artikel_code,artikel_desc,movement_min,movement_plus,movement_price,movement_transnno,movement_type,movement_desc)
    //     select 
    //     now()::timestamp::date,
    //     article_code,
    //     (select concat(article_alternative_code,'-',article_desc) from article where article_code = a.article_code) as article_desc,
    //     0,
    //     qty,
    //     price,
    //     rec_number,
    //     'REC',
    //     (select po_number from receiving_hdr where rec_number=a.rec_number) as po from receiving_det a where rec_number in (
    //     select rec_number from receiving_hdr where rec_number = '$recNumber' and status = '3' and qty <> 0)";
    
    //     DB::select($sqlUpdate);
    //     $rowAffected = DB::select($sqlInsert);
        
    //     if ($rowAffected > 0){
    //         DB::table('receiving_hdr')
    //         ->where('rec_number',$recNumber)
    //         ->update(
    //             [   
    //                 'status' => $status,
    //                 'authorized_by' => $authorizedBy,
    //                 'authorized_at' => date('Y-m-d H:i:s'),
    //                 'updated_by' => Auth::user()->username,
    //                 'updated_at' => date('Y-m-d H:i:s')
    //             ]
    //         );

    //         DB::select($sqlMovement);

    //         DB::commit();
    //         $alert  ="alert-success";
    //         $message  = "Posting Rec $recNumber Successfully Posting";
    //         \LogActivity::addToLog('Posting Rec ',"username: $username Status $message");
    //         return response()->json(array('statusRec' => $statusRec,'status' => 1, 'message' => $message,'alert'=>$alert,'recNumber'=>$recNumber));
    //     }else{
    //         $alert  ="alert-warning";
    //         $message  = "Posting Rec $recNumber Failed to Posting";
    //         \LogActivity::addToLog('Posting Rec ',"username: $username Status $message");
    //         return response()->json(array('statusRec' => $statusRec,'status' => 1, 'message' => $message,'alert'=>$alert,'recNumber'=>$recNumber));
    //     }
    // }

    public function destroy(Request $request)
    {
        // status
        // 1. Draft
        // 2. Update
        // 3. Posting
        // 4. Cancel

        $username =  Auth::user()->username;       
        $id = $request->id;
        $status = "4";

        $poHdr= DB::table('delivery_hdr')
        ->where('id',$id)
        ->get()->first();

        $dnNumber = $poHdr->delivery_number;
        $soNumber = $poHdr->so_number;
        $note = $poHdr->note;

        $rowAffected=DB::table('delivery_hdr')
        ->where('delivery_number',$dnNumber)
        ->update(
            [   
                'delivery_number' => $dnNumber."(C)",
                'so_number' => $soNumber."(C)",
                'status' => $status,
                'note' => $note." (Cancel)",
                'updated_by' => Auth::user()->username,
                'updated_at' => date('Y-m-d H:i:s')
            ]
        );

        if($rowAffected>0){
            DB::table('delivery_det')
            ->where('delivery_number',$dnNumber)
            ->update(
                [   
                    'delivery_number' => $dnNumber."(C)",
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );

            $title ="Cancel $this->title";
            $alert  ="success";
            $message  = "$title $dnNumber Successfully Cancel";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'title' => $title,'message'=> $message]);  
        }else{
            $title ="Cancel $this->title";
            $alert  ="warning";
            $message  = "$title $dnNumber Failed to Cancel";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'title' => $title,'message'=> $message]);
        }
    }

    public function list(Request $request)
    {
        // status:
        // 1. Draft
        // 2. Update
        // 3. Posting
        // 4. Cancel

        $searchInv = strtolower($request->searchInv);
        $searchSo = strtolower($request->searchSo);
        $searchCustomer = $request->searchCustomer; 
        $searchStatus = $request->searchStatus;
        $recDate = $request->recDate;       

        $filter='';
                
        $data = DB::select("SELECT *,
        (select concat(kode,'-',nama) from third_party where kode = customer_id limit 1) as customer_name 
        from delivery_hdr a $filter");

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            if (($data->status != '3') && ($data->status != '4')){
                if (Auth::user()->can('receiving-edit')) {
                $buttons .=         '<a href="'. route('delivery.edit', ['id'=>$data->id]) .'" class="dropdown-item">
                                        <i data-feather="file-text"></i>
                                        Edit
                                    </a>';
                }
            }
            // if ($data->status == '3'){
                $buttons .=         '<a href="'. route('delivery.print', ['id'=>$data->id]) .'" target="_blank" class="dropdown-item">
                                        <i data-feather="printer"></i>
                                        Print
                                    </a>';

            // }
            $buttons .=         '<a href="'. route('delivery.show', ['id'=>$data->id]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    Detail
                                </a>';
                
            if (($data->status != '3') && ($data->status != '4')){
                if (Auth::user()->can('receiving-delete')) {
                $buttons .=         "<a href='javascript:;'
                                        id='deleteButton'
                                        class='dropdown-item'
                                        data-toggle='modal'
                                        data-target='#smallModalCancel'
                                        data-href='". route("delivery.destroy", ["id"=>$data->id]) ."'>
                                        <i data-feather='trash-2'></i>
                                        Cancel
                                    </a>";
                }
            }
            $buttons .=     '</div>
                        </div>';

            return $buttons;
            })
        ->addColumn('status', function ($data) {
            $statusRec = ['Draft','Update','Posting','Cancel'];
            return $statusRec[$data->status - 1];
        })
        ->rawColumns(['action','status'])
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
        
        $dnHdr=DB::table('delivery_hdr')
        ->where('id',$id)
        ->first();

        $data['recHdr']=DB::table('delivery_hdr')
        ->where('id',$id)
        ->first();

        $dnNumber=$dnHdr -> delivery_number;
       
        $data['details']=DB::table('delivery_det')
        ->leftJoin('article','article.article_code','delivery_det.article_code')
        ->where('delivery_number',$dnNumber)
        ->get();


        $data['totals']=DB::select("SELECT * from (
            select 
            a.delivery_number,
            sum(qty) as qty 
            from delivery_det a
            left join delivery_hdr b
            on a.delivery_number = b.delivery_number 
            where a.delivery_number = '$dnNumber'
            group by a.delivery_number) as oki");

        $data['customers']=DB::table('third_party')
        ->where('kode',$dnHdr -> customer_id)
        ->first();
        
        $data['status'] ='1';
        $data['no'] = 0 ;

        view()->share($data);

        $pdf = PDF::loadView('delivery.print');
        return $pdf->stream("DN_$dnNumber.pdf");

    }

    public function listSo(Request $request)
    {
        $cust= $request->value;      
        $output="";

        $data= DB::table("sales_order_hdr") 
        ->where("customer_id",$cust)
        ->where("status","3")
        ->orderBy("so_code")
        ->select("so_code","po_number")
        ->get();          

        $output .='<option value=""></option>';            
        foreach ($data as $row){
            $output .='<option value="'.$row->so_code.'">'.$row->so_code. ' - ' .$row->po_number.'</option>';            
        }        
        
        return $output;
    }

    public function listUom(Request $request)
    {
        $uomGroup = $request->value;      
        $output="";

        $data= DB::table("uom") 
        ->where("uom_group",$uomGroup)
        ->orderBy("code")
        ->select("code","name")
        ->get();          

        // $output .='<option value=""></option>';            
        foreach ($data as $row){
            $output .='<option value="'.$row->code.'">'.$row->code.'</option>';            
        }        
        
        return $output;
    }
}
