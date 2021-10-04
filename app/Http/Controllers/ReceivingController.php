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

class ReceivingController extends Controller
{
    public function index(Request $request)
    {
        $data['title'] = "Receiving";

        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

        // status
        // 1. Draft
        // 2. Update
        // 3. Posting
        // 4. Cancel

        $data['status'] = ['1'=>'Draft','2'=>'Update','3'=>'Posting','4'=>'Cancel'];
            
        return view("receiving.index",$data);
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
        $poNumber="$key-ASN/$year/$month/$newCode";
        
        return $poNumber;
    }

    public function create(Request $request)
    {
        $data['title'] = "Create Receiving";
        $data['subtitle'] = "Create Receiving";
        
        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

         $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();

        return view("receiving.create",$data);
    }

    public function poDetail(Request $request)
    {
        $po = $request->value;


        $data = DB::select("SELECT a.*,a.article_code,article_alternative_code,article_desc,uom_group, (a.qty-b.qty) as qty_order from purchase_order_det a
                left join uom on uom.code=a.uom
                left join article on article.article_code = a.article_code
                left join 
                (select po, article_code,sum(qty) as qty from(
                select * , (select po_number from receiving_hdr where rec_number = a.rec_number) as po from receiving_det a) z
                group by po, article_code) b
                on a.po_number = b.po and a.article_code = b.article_code
                where po_number = '$po'");

        // $data = DB::table("purchase_order_det")
        // ->select("purchase_order_det.*","purchase_order_det.article_code","article_alternative_code","article_desc","uom_group")
        // ->leftJoin("uom","uom.code","purchase_order_det.uom") 
        // ->leftJoin("article","article.article_code","purchase_order_det.article_code") 
        // ->where("po_number",$po)
        // ->orderBy("purchase_order_det.id")
        // ->get();                
        
        return response()->json($data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $invNumber = $request->invNumber;
        $poNumber = $request->poNumber;
        $supplier = $request->supp;
        $recDate = $request->recDate;
        $note = $request->note;
        $articles = json_decode($request->articles);
        $recType = "NORMAL";
        $statusRec ="Draft";
        $status = '1';
        $authorizedBy = "";

        // status
        // 1. Draft
        // 2. Update
        // 3. Posting
        // 4. Cancel
        
        $customMessages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
            'iunique' => "Invoice : $invNumber has already been taken on PO : $poNumber",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) use ($poNumber) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            $column2 = $query->getGrammar()->wrap($parameters[2]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])
                          ->whereRaw("lower({$column2}) = lower(?)", [$poNumber])->count();
        });
        
        $validation = Validator::make($request->all(),$messages = [
            'invNumber'=>'required|iunique:receiving_hdr,inv_number,po_number',
            'recDate'  => 'required',
            'poNumber'  => 'required',
            // 'supplier'  => 'required',
        ],$customMessages);
        
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
            $hasilUpdate = AppHelpers::resetCode('REC');
            $recNumber = $this->getLastCode('REC');
            DB::beginTransaction();
            try {
                    DB::table('receiving_hdr')->insert([
                        'rec_number' => $recNumber,
                        'inv_number' => $invNumber,
                        'po_number' => $poNumber,
                        'supplier_id' => $supplier,
                        'rec_date' => $recDate,
                        'authorized_by' => $authorizedBy,
                        'prepared_by' => Auth::user()->username,
                        'rec_type' => $recType,
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
                            'rec_number' => $recNumber,
                            'article_code' => $val->article_code,
                            'qty' => $val->qty,
                            'uom_rec' => $val->uom,
                            'qty_free' => $val->qty_free,
                            'uom_free' => $val->uom_free,
                            'created_by' => Auth::user()->username,
                            'updated_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                    }

                    DB::table('receiving_det')->insert($dataSet);

                    DB::commit();
                    $alert  ="alert-success";
                    $message  = "Rec $recNumber is successfully saved";
                    $statusRec  = $statusRec;
                    \LogActivity::addToLog('Rec save ',"username: $username Status $message");
                    return response()->json(array('statusRec' => $statusRec, 'status' => 1, 'message' => $message,'alert'=>$alert,'recNumber'=>$recNumber));

            } catch (Exception $e) {
                DB::rollBack();
                $alert  ="alert-warning";
                $message  = "Rec $recNumber is failed to save";
                $statusRec = 'FAILED';
                \LogActivity::addToLog('Rec save ',"username: $username Status $message");
                return response()->json(array('statusRec' => $statusRec,'status' => 1, 'message' => $message,'alert'=>$alert,'recNumber'=>$recNumber));
            }
        }
    }

    public function show(Request $request)
    {
        $id=$request->id;
        $data['title'] = "Detail Receiving";
        $data['subtitle'] = "Detail Receiving";

        $data['header'] = DB::table('purchase_order_hdr')
        ->leftJoin('purchase_request_det','purchase_order_hdr.po_number','purchase_request_det.po_number')
        ->where('purchase_order_hdr.id',$id)
        ->get()->first();

        $data['prHeader'] = DB::table('purchase_request_det') 
        ->where('supp_code',$data['header']->supplier_id)
        ->where('po_number','=',$data['header']->po_number)
        ->orderBy('pr_number')
        ->distinct('pr_number')
        ->get();

        $data['articles'] = DB::table('purchase_request_det') 
            ->leftJoin('article','article.article_code','=','purchase_request_det'.'.article_code')
            ->leftJoin('article_stock','article_stock.article_code','=','purchase_request_det'.'.article_code')
            ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
            ->where('supp_code',$data['header']->supplier_id)
            ->where('po_number','=',$data['header']->po_number)
            // ->where('pr_number','=',$data['header']->pr_number)
            ->orderBy('article.article_desc')
            ->distinct('article.article_desc')
            ->select('purchase_request_det'.'.*','article.article_alternative_code','article.article_code as artikel_code','article.article_desc','article.costprice','article_stock.article_qty as qty_stock','purchase_request_det.uom as uom1','group_materials.name as group')
            ->get();

        $data['detail'] = DB::table('purchase_order_det')
        ->leftJoin('article','article.article_code','=','purchase_order_det.article_code')
        ->leftJoin('article_stock','article_stock.article_code','=','purchase_order_det.article_code')
        ->leftJoin('purchase_request_det', function($join) {
            $join->on('purchase_request_det.po_number','purchase_order_det.po_number')
            ->on('purchase_request_det.article_code','purchase_order_det.article_code');
        })
        ->where('purchase_order_det.po_number',$data['header']->po_number)
        ->select('purchase_order_det'.'.*','purchase_request_det.pr_number','article_stock.article_qty as qty_stock', DB::raw('(SELECT name from group_materials where code = group_of_material) as group'))
        ->orderBy('id')
        ->get();       

        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

        $data['currency'] = ['IDR','USD'];

        $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();


        return view("receiving.show",$data);
        
    }

    public function edit(Request $request)
    {
        $id=$request->id;
        $data['title'] = "Edit Receiving";
        $data['subtitle'] = "Edit Receiving";

        $data['header'] = DB::table('purchase_order_hdr')
        ->leftJoin('purchase_request_det','purchase_order_hdr.po_number','purchase_request_det.po_number')
        ->where('purchase_order_hdr.id',$id)
        ->get()->first();

        $data['prHeader'] = DB::table('purchase_request_det') 
        ->where('supp_code',$data['header']->supplier_id)
        ->where('po_number','=',$data['header']->po_number)
        ->orderBy('pr_number')
        ->distinct('pr_number')
        ->get();

        $data['articles'] = DB::table('purchase_request_det') 
            ->leftJoin('article','article.article_code','=','purchase_request_det'.'.article_code')
            ->leftJoin('article_stock','article_stock.article_code','=','purchase_request_det'.'.article_code')
            ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
            ->where('supp_code',$data['header']->supplier_id)
            ->where('po_number','=',$data['header']->po_number)
            // ->where('pr_number','=',$data['header']->pr_number)
            ->orderBy('article.article_desc')
            ->distinct('article.article_desc')
            ->select('purchase_request_det'.'.*','article.article_alternative_code','article.article_code as artikel_code','article.article_desc','article.costprice','article_stock.article_qty as qty_stock','purchase_request_det.uom as uom1','group_materials.name as group')
            ->get();

        $data['detail'] = DB::table('purchase_order_det')
        ->leftJoin('article','article.article_code','=','purchase_order_det.article_code')
        ->leftJoin('article_stock','article_stock.article_code','=','purchase_order_det.article_code')
        ->leftJoin('purchase_request_det', function($join) {
            $join->on('purchase_request_det.po_number','purchase_order_det.po_number')
            ->on('purchase_request_det.article_code','purchase_order_det.article_code');
        })
        ->where('purchase_order_det.po_number',$data['header']->po_number)
        ->select('purchase_order_det'.'.*','purchase_request_det.pr_number','article_stock.article_qty as qty_stock', DB::raw('(SELECT name from group_materials where code = group_of_material) as group'))
        ->orderBy('id')
        ->get();       

        // $data['articles']= DB::table('article') 
        // ->leftJoin('article_stock','article_stock.article_code','=','article.article_code')
        // ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
        // // ->where('third_party',$data['header']->customer_id)
        // ->where('article_type','CM')
        // ->orderBy('article_desc')
        // ->select('article'.'.*', 'article_stock.article_qty as qty','article.uom as uom1','group_materials.name as group')
        // ->get();   

        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

        $data['currency'] = ['IDR','USD'];

        $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();

        return view("receiving.edit",$data);
        
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $poNumber = $request -> poNumber;
        $articles = json_decode($request -> articles);
        $recDate = $request->recDate;
        $deliveryDate = $request->deliveryDate;
        $currency = $request->currency;
        $supplier = $request->supplier;
        $tax = $request->tax;
        $ppn = $request->ppn;
        $termin = $request -> term;
        $pph = 0;
        $kurs = $request -> kurs;
        $totalPpn = $request->totalPpn;
        $totalPph = $request->totalPph;
        $discount = $request->discount;
        $note = $request->note;
        $status = '1';

        // status:
        // 1 = New
        // 2 = Validated
        // 3 = Authorized
        // 4 = Received
        // 5 = Canceled
        // 6 = closed
        
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
            // 'poNumber'=>'required|unique:purchase_order_hdr,po_number',
            // 'orderNumber' => 'required',
            'recDate'  => 'required',
            'currency'  => 'required',
            'supplier'  => 'required',
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
                    $row_affected=DB::table('purchase_order_hdr')
                    ->where('po_number',$poNumber)
                    ->update(
                        [
                            'po_number' => $poNumber,
                            'supplier_id' => $supplier,
                            'po_date' => $recDate,
                            'delivery_date' =>$deliveryDate,
                            'currency' => $currency,
                            'kurs' => $kurs,
                            'ppn' => $ppn,
                            'pph22' => $pph,
                            'status' => $status,
                            'note' =>  $note,
                            'authorized_by' => '',
                            'prepared_by' =>  '',
                            'discount' => $discount,
                            'pkp' => $tax,
                            'termin' =>$termin,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );

                    $dataset=[];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            $poNumber.$val->article_code
                        ];
                        
                    }

                    //Delete kalo article tidak ada di po $poNumber dan article nya $val->article_code
                    //berdasarkan 2 kondisi
                    DB::table('purchase_order_det')
                        ->whereNotIn(DB::raw("CONCAT(po_number,article_code)"),$dataSet)
                        ->where('po_number',$poNumber)
                        ->delete();
                                  
                    foreach ($articles as $val) {
                        DB::table('purchase_order_det')
                        ->updateOrInsert(
                            ['po_number' => $poNumber,'article_code' => $val->article_code],
                            [
                            'po_number' => $poNumber,
                            'pr_number' => $val->pRequest,
                            'article_code' => $val->article_code,
                            'qty' => $val->qty,
                            'uom' => $val->uom,
                            'old_price' => $val->price,
                            'price' => $val->newPrice,
                            'ppn' => $totalPpn,
                            'pph22' => $totalPph,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                            ]
                        );

                        DB::table('purchase_request_det')
                        ->where('pr_number',$val->pRequest)
                        ->where('article_code',$val->article_code)
                        ->where('supp_code',$supplier)
                        ->update(
                            [
                            'po_number' => $poNumber,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                            ]
                        );

                        DB::table('purchase_request_hdr')
                        ->where('number',$val->pRequest)
                        ->update(
                            [
                            'status' => 7,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                            ]
                        );
                    }
                    
                    //update purchase_request_det kalo ada article yang di hapus di PO, jadi kolom po_number di null kan
                    DB::table('purchase_request_det')
                    ->whereNotIn(DB::raw("CONCAT(pr_number,po_number,article_code)"), function($query) use ($poNumber) {
                        $query->select(DB::raw("CONCAT(pr_number,po_number,article_code)"))
                        ->from('purchase_order_det') 
                        ->where('po_number',$poNumber);
                    })
                    ->where('po_number',$poNumber)
                    ->update(
                        [
                            'po_number' => null,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );
                                            
                    DB::commit();
                    $alert  ="alert-success";
                    $message  = "PO $poNumber is successfully updated";
                    \LogActivity::addToLog('PO update ',"username: $username Status $message");
                    return response()->json(array('status' => 1, 'message' => $message,'alert'=>$alert,'poNumber'=>$poNumber));

            } catch (Exception $e) {
                DB::rollBack();
                $alert  ="alert-warning";
                $message  = "PO $poNumber is failed to updated";
                \LogActivity::addToLog('PO update ',"username: $username Status $message");
                return response()->json(array('status' => 1, 'message' => $message,'alert'=>$alert,'poNumber'=>$poNumber));
            }
        }

    }

    public function destroy(Request $request)
    {
        $username =  Auth::user()->username;       
        $id = $request->id;
        $po_number = DB::table('purchase_order_hdr')->where('id',$id)->where('status','1')->value('po_number');
        $rowAffected = DB::table('purchase_order_hdr')->where('id',$id)->delete();
        if($rowAffected>0){
            DB::table('purchase_order_det')->where('po_number',$po_number)->delete();
            $alert  ="alert-success";
            $message  = "PO $po_number Successfully Deleted";
            \LogActivity::addToLog('PO delete ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  
        }else{
            $alert  ="alert-warning";
            $message  = "PO $po_number Failed to Delete";
            \LogActivity::addToLog('PO delete ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
        }

    }

    public function list(Request $request)
    {
        // status:
        // 1. Draft
        // 2. Update
        // 3. Posting
        // 4. Cancel

        $searchRec = strtolower($request->searchRec);
        $searchPo = strtolower($request->searchPo);
        $searchInv = strtolower($request->searchInv);
        $searchSupplier = $request->searchSupplier;
        $searchStatus = $request->searchStatus;
        $recDate = $request->recDate;
       

        $filter='';
        
        if ($searchRec !='' ){
            $filter.="lower(a.rec_number) like '%$searchRec%' and ";
        }

        if ($searchPo !='' ){
            $filter.="lower(a.po_number) like '%$searchPo%' and ";
        }

        if ($searchInv !='' ){
            $filter.="lower(a.inv_number) like '%$searchInv%' and ";
        }

        if ($searchSupplier  != '' ){
            $filter.="supplier_id = '$searchSupplier' and ";            
        }

        if ($searchStatus  != '' ){
            $filter.="status = '$searchStatus' and ";            
        }

        if ($recDate  != '' ){
            $date = explode("to",$recDate);
            $date1=trim($date[0]);
            $date2=trim($date[1]);
            $filter.= "to_date(rec_date, 'DD/MM/YYYY')  BETWEEN to_date('$date1', 'DD/MM/YYYY') and to_date('$date2', 'DD/MM/YYYY') and ";
        }

        
        if ($filter !=''){
            $filter=" where ".substr($filter,0,-4);
        }

        $data = DB::select("SELECT id,inv_number,rec_number,rec_date,po_number,
        (select concat(kode,'-',nama) from third_party where kode = supplier_id limit 1) as supp_name ,prepared_by,authorized_by,status
        from receiving_hdr $filter");

        // $data=DB::select("SELECT *,delivery_date,(select concat(kode,'-',nama) from third_party where kode = supplier_id limit 1) as supp_name,(gross-discount)+ppn as netto from (
        //     select b.status,b.id,a.po_number,supplier_id,po_date,delivery_date,pkp,termin,authorized_by,prepared_by,uom,sum(qty) as qty,sum(qty*price) as gross,sum(discount) as discount,sum(a.ppn) as ppn from purchase_order_det a
        //     left join purchase_order_hdr b
        //     on a.po_number = b.po_number 
        //     $filter
        //     group by b.id,a.po_number,supplier_id,po_date,delivery_date,pkp,termin,authorized_by,prepared_by,uom,b.status) as oki");
        
        // $data=DB::table('purchase_order_hdr')->get();

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            if (Auth::user()->can('receiving-edit')) {
            $buttons .=         '<a href="'. route('receiving.edit', ['id'=>$data->id]) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
            $buttons .=         '<a href="'. route('receiving.print', ['id'=>$data->id]) .'" target="_blank" class="dropdown-item">
                                    <i data-feather="printer"></i>
                                    Print
                                </a>';
            }
            $buttons .=         '<a href="'. route('receiving.show', ['id'=>$data->id]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    Detail
                                </a>';
                
            if (Auth::user()->can('receiving-delete')) {
            $buttons .=         "<a href='javascript:;'
                                    id='deleteButton'
                                    class='dropdown-item'
                                    data-toggle='modal'
                                    data-target='#smallModal'
                                    data-href='". route("receiving.destroy", ["id"=>$data->id]) ."'>
                                    <i data-feather='trash-2'></i>
                                    Delete
                                </a>";
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
        
        $poHdr=DB::table('purchase_order_hdr')
        ->where('id',$id)
        ->first();

        $poNumber=$poHdr -> po_number;
       

        $data['details']=DB::table('purchase_order_det')
        ->leftJoin('article','article.article_code','purchase_order_det.article_code')
        ->where('po_number',$poNumber)
        ->get();

        $data['totals']=DB::select("SELECT *,(gross-discount)+ppn as netto from (
            select a.po_number,authorized_by,prepared_by,sum(qty) as qty,sum(qty*price) as gross,sum(discount) as discount,sum(a.ppn) as ppn from purchase_order_det a
            left join purchase_order_hdr b
            on a.po_number = b.po_number 
            where a.po_number = '$poNumber'
            group by a.po_number,authorized_by,prepared_by) as oki");

        $data['suppliers']=DB::table('third_party')
        ->where('kode',$poHdr -> supplier_id)
        ->get();

        $data['keterangan']=$poHdr -> note;
        $data['poNumber'] =$poNumber;
        $data['poDate'] =$poHdr -> po_date;
        $data['poTerm'] =$poHdr -> termin;
        $data['poDelDate'] =$poHdr -> delivery_date;
        
        $data['status'] ='1';
        $data['no'] =1;

        view()->share($data);

        $pdf = PDF::loadView('receiving.print');
        return $pdf->stream("PO_$poNumber.pdf");

    }

    public function listPo(Request $request)
    {
        $supp= $request->value;      
        $output="";

        $data= DB::table("purchase_order_hdr") 
        ->where("supplier_id",$supp)
        ->where("status","3")
        ->orderBy("po_number")
        ->select("po_number")
        ->get();          

        $output .='<option value=""></option>';            
        foreach ($data as $row){
            $output .='<option value="'.$row->po_number.'">'.$row->po_number.'</option>';            
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

        $output .='<option value=""></option>';            
        foreach ($data as $row){
            $output .='<option value="'.$row->code.'">'.$row->code.'</option>';            
        }        
        
        return $output;
    }
}
