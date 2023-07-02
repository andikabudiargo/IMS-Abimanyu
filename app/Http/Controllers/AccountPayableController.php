<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Session;
use Response;
use App\Permission;
use DataTables;
use DB;
use PDF;
use AppHelpers;
use Approval;

class AccountPayableController extends Controller
{
    private $title;
    private $moduleCode;
    private $voucherCode;

    public function __construct()
    {
        $this->title = "Invoice Supplier";
        $this->moduleCode = "AP";
        $this->voucherCode = "APV";
    }

    public function getTableColoumn()
    {
        $kolom=
        [
            ['data'=> 'action', 'name'=> 'action','title'=>'action', 'orderable'=> false, 'searchable'=> false],
            ['data'=> 'ap_number', 'name'=> 'ap_number','title'=>'AP Number'],
            ['data'=> 'ap_date', 'name'=> 'ap_date','title'=>'AP Date'],
            ['data'=> 'status', 'name'=> 'status','title'=>'Status'],
            ['data'=> 'num_revision', 'name'=> 'num_revision','title'=>'Rev.','visible'=>false],
            ['data'=> 'inv_number', 'name'=> 'inv_number','title'=>'Invoice Number'],
            ['data'=> 'proforma_inv_number', 'name'=> 'proforma_inv_number','title'=>'Proforma','visible'=>false],
            ['data'=> 'tax_inv_number', 'name'=> 'tax_inv_number','title'=>'Tax Inv Number'],
            ['data'=> 'inv_date', 'name'=> 'inv_date','title'=>'Inv Date'],
            ['data'=> 'supplier_name', 'name'=> 'supplier_name','title'=>'Supplier'],
            ['data'=> 'po_number', 'name'=> 'po_number','title'=>'PO Number'],
            ['data'=> 'list_rec', 'name'=> 'list_rec','title'=>'Rec Number'],
            ['data'=> 'rec_date', 'name'=> 'rec_date','title'=>'Rec Date','visible'=>false],
            ['data'=> 'basis_amount', 'name'=> 'basis_amount','title'=>'DPP'],
            ['data'=> 'vat', 'name'=> 'vat','title'=>'VAT'],
            ['data'=> 'pph23', 'name'=> 'pph23','title'=>'PPH23'],
            ['data'=> 'total_discount', 'name'=> 'total_discount','title'=>'Discount'],
            ['data'=> 'grand_total', 'name'=> 'grand_total','title'=>'Grand Total'],
            ['data'=> 'note', 'name'=> 'note','title'=>'Note'],
            ['data'=> 'created_by', 'name'=> 'created_by','title'=>'Created By'],
            ['data'=> 'created_at', 'name'=> 'created_at','title'=>'Created At']
        ];
        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $data['title'] = "List $this->title";
        
        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

        // $data['nilaiPPN'] = DB::table('attributes')
        // ->where('attr_id','mainppn')
        // ->value('attr_value');

        // $data['nilaiPPH'] = DB::table('attributes')
        // ->where('attr_id','mainpph23')
        // ->value('attr_value');

        $data['kolom'] = $this->getTableColoumn();

        // status
        // 1. Draft
        // 2. Updated
        // 3. Submitted / Posted
        // 4. Canceled
        // 5. Paid

        $data['status'] = ['1'=>'DRAFT','2'=>'UPDATED','3'=>'POSTED','4'=>'CANCELED','5'=>'PAID'];
            
        return view("accountPayable.index",$data);
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

    public function getLastCodeVoucher($key)
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

    public function listSj(Request $request)
    {
        $supp= $request->value;      
        $output="";

        $data= DB::table("receiving_hdr") 
        ->where("supplier_id",$supp)
        ->where("status","3")
        ->orderBy("do_number")
        ->select("do_number","do_date")
        ->get();          

        $output .='<option value=""></option>';            
        foreach ($data as $row){
            $output .='<option data-do-date="'.$row->do_date.'" value="'.$row->do_number.'">'.$row->do_number.'</option>';            
        }        
        
        return $output;
    }

    public function listPo(Request $request)
    {
        $supp= $request->value;      
        $output="";
        $edit = $request->edit;        

        if($edit == 'true'){
            $data= DB::table("purchase_order_hdr") 
            ->where("supplier_id",$supp)
            ->whereIn('po_number', function($query) use ($supp) {

                $listRec = DB::table('ap_invoice_detail')
                ->whereIn('ap_number',DB::table('ap_invoice')
                // ->where('ap_invoice.po_number','purchase_order_hdr.po_number')
                ->where('status','4')
                ->pluck('ap_number')->toArray())
                ->pluck('rec_number')->toArray();

                $query->select('po_number')
                ->from('receiving_hdr') 
                ->whereNotIn('rec_number',$listRec);

            })
            ->where("status","3")
            ->orderBy("po_number")
            ->select("po_number","po_date","currency","kurs")
            ->get();

        }else{
            $data= DB::table("purchase_order_hdr") 
            ->where("supplier_id",$supp)
            ->whereIn('po_number', function($query) use ($supp) {
                $listRec = DB::table('ap_invoice_detail')
                ->whereIn('ap_number',DB::table('ap_invoice')
                // ->where('ap_invoice.po_number','purchase_order_hdr.po_number')
                ->pluck('ap_number')->toArray())
                ->pluck('rec_number')->toArray();

                $query->select('po_number')
                ->from('receiving_hdr') 
                ->where('supplier_id',$supp)
                ->whereNotIn('rec_number',$listRec);
            })
            ->where("status","3")
            ->orderBy("po_number")
            ->select("po_number","po_date","currency","kurs")
            ->get();

        }
        
        $output .='<option value=""></option>';            
        foreach ($data as $row){
            $output .='<option 
                            data-po-date="'.$row->po_date.'" 
                            data-po-currency="'.$row->currency.'" 
                            data-po-kurs="'.$row->kurs.'" 
                            value="'.$row->po_number.'">'.$row->po_number.'</option>';            
        }        
        
        return $output;
    }

    public function listRec(Request $request)
    {
        $poNumber= $request->value;
        $apNumber= $request->apNumber;
        $showDetail= $request->showDetail;
        $output="";

        $edit = $request->edit;        

        if($edit == 'true'){

            $data= DB::table("receiving_hdr") 
            ->where("po_number",$poNumber)
            ->where("status","4")
            ->whereNotIn(DB::raw("rec_number"), function($query) use ($poNumber,$apNumber) {
                $query->select("rec_number")
                ->from('ap_invoice_detail')
                ->whereIn('ap_number',DB::table('ap_invoice')
                                        ->whereIn('status',['4'])
                                        ->pluck('ap_number')->toArray())
                ->orWhere('ap_number','<>',$apNumber);
            })
            ->orderBy("rec_number")
            ->select("rec_number","do_date","do_number"
            ,db::raw("(select sum(qty) as sum_qty from receiving_det where rec_number=receiving_hdr.rec_number) as sum_qty"))
            ->get(); 

            // dd($data);

        }else{

            $data= DB::table("receiving_hdr") 
            ->where("po_number",$poNumber)
            ->where("status","4")
            ->whereNotIn(DB::raw("rec_number"), function($query) use ($poNumber) {
                $query->select(DB::raw("rec_number"))
                ->from('ap_invoice_detail');
                // ->where('po_number',$poNumber);
            })
            ->orderBy("rec_number")
            ->select("rec_number","do_date","do_number"
            ,db::raw("(select sum(qty) as sum_qty from receiving_det where rec_number=receiving_hdr.rec_number) as sum_qty"))
            ->get(); 
        }
        
        if ($apNumber){
            $details = DB::table('ap_invoice_detail')->where('ap_number',$apNumber)->pluck('rec_number');
            $arrayData=[];
            foreach($details as $val ){
                array_push($arrayData,$val);
            }
            $details = $arrayData;
            // dd($details);
        }else{
            $details=[];
        }

        $output="";
        foreach ($data as $key=>$row){
            $checked = in_array($row->rec_number, $details) ? 'checked' :'';

            // dd($showDetail);

            if($showDetail =='true' && $checked ){
                $output .="<tr>
                            <td>
                                <div class='custom-control custom-checkbox'>
                                    <input type='checkbox' class='custom-control-input' id='customCheck$key' name='customCheck'
                                    data-do-date='$row->do_date' 
                                    data-rec-number = '$row->rec_number'
                                    data-sum-qty = '$row->sum_qty' $checked disabled>
                                    <label class='custom-control-label' for='customCheck$key'></label>
                                </div>
                            </td>
                            <td>$row->rec_number</td>
                            <td>$row->do_date</td>
                            <td>$row->do_number</td>
                        </tr>";
            }

            if($showDetail=='false' ){
                $output .="<tr>
                            <td>
                                <div class='custom-control custom-checkbox'>
                                    <input type='checkbox' class='custom-control-input' id='customCheck$key' name='customCheck'
                                    data-do-date='$row->do_date' 
                                    data-rec-number = '$row->rec_number'
                                    data-sum-qty = '$row->sum_qty' $checked>
                                    <label class='custom-control-label' for='customCheck$key'></label>
                                </div>
                            </td>
                            <td>$row->rec_number</td>
                            <td>$row->do_date</td>
                            <td>$row->do_number</td>
                        </tr>";
            }
        }

        // $output .='<option value=""></option>';            
        // foreach ($data as $row){
        //     $output .='<option data-do-date="'.$row->do_date.'" value="'.$row->rec_number.'">'.$row->rec_number.'</option>';            
        // }        
        
        return $output;
    }

    public function detailRec(Request $request){
        $poNumber = $request->poNumber;
        $recNumber = $request->recNumber;
        $arrayRecNumber = explode(",",$recNumber);

        $result = "'" . implode ( "', '", $arrayRecNumber ) . "'";

        $nilaiPPN = DB::table('attributes')
        ->where('attr_id','mainppn')
        ->value('attr_value');

        $detailRec = DB::table('receiving_det')
        ->leftJoin('receiving_hdr','receiving_hdr.rec_number','receiving_det.rec_number')
        ->leftJoin('article','article.article_code','receiving_det.article_code')
        ->leftJoin(DB::RAW("(select * from purchase_order_det where po_number = '$poNumber') AS po"),function($join){
            $join->on('po.po_number','=','receiving_hdr.po_number')
                ->on('po.article_code','=','receiving_det.article_code');
        })

        // ->leftJoin(DB::RAW("(select * from purchase_order_det where po_number = '$poNumber') AS po"),'po.po_number','receiving_hdr.po_number')
        ->whereIn('receiving_det.rec_number',$arrayRecNumber)
        ->where('receiving_det.qty','>',0)
        ->select('article.article_alternative_code as article'
        ,'article.article_desc as desc'
        ,'receiving_det.uom_rec as uom'
        ,db::raw("sum(receiving_det.qty) as qty")
        ,'po.price'
        ,db::raw("round(sum(receiving_det.qty*po.price)) as total"))
        ->groupBy('article.article_alternative_code')
        ->groupBy('article.article_desc')
        ->groupBy('receiving_det.uom_rec')
        ->groupBy('po.price')
        ->get();

        $summaryRec =  DB::select("SELECT z.*
        ,ppn as vat
        ,round(((basis_amount-discount)*$nilaiPPN/100)) as nilai_pajak
        ,pkp as pkp
        ,pph22 as pph22
        ,discount
        ,(select sum(qty) as qty  from purchase_order_det where po_number = z.po_number) as total_qty_po
        ,round((select sum(qty*price) as qty  from purchase_order_det where po_number = z.po_number)) as total_amount_po
        ,round((select sum(qty*price) as qty  from purchase_order_det where po_number = z.po_number) -(select sum(basis_amount) from ap_invoice where po_number = z.po_number)) as po_balance
        ,round((basis_amount-discount)+(basis_amount*ppn/100)+pph22) as total_netto
        from 
        (select b.po_number
        ,sum(a.qty) as total_qty_rec
        ,round(sum(a.qty*c.price)) as basis_amount
        from receiving_det a
        left join receiving_hdr b on a.rec_number = b.rec_number 
        left join purchase_order_det c on c.po_number = b.po_number and c.article_code = a.article_code 
        -- where a.rec_number in ('REC-ASN/2022/XI/4','REC-ASN/2023/I/1')
        where a.rec_number in ($result)
        --and a.qty > 0
        group by b.po_number) z
        left join purchase_order_hdr y on y.po_number = z.po_number");

        // dd($summaryRec);
        return response()->json(array('detailRec'=>$detailRec,'summaryRec'=>$summaryRec));    
    }

    public function poDetail(Request $request)
    {
        $po = $request->value;
        $data = DB::select("SELECT 
                a.*,
                a.article_code,
                article_alternative_code,
                article_desc,uom_group, 
                (COALESCE(a.qty,0)-COALESCE(b.qty,0)) as qty_order
                from purchase_order_det a
                left join uom on uom.code=a.uom
                left join article on article.article_code = a.article_code
                left join 
                    (select po, article_code,sum(qty) as qty,price from (
                        select *,(select po_number from receiving_hdr where rec_number = a.rec_number) as po from receiving_det a where rec_number in (
                        select rec_number from receiving_hdr where status = '3')
                    ) z
                group by po, article_code,price) b
                on a.po_number = b.po and a.article_code = b.article_code
                where po_number = '$po'");

        return response()->json($data);
    }

    public function create(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create $this->title";
        
        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

        $data['currency'] = ['IDR','USD'];
        $data['status'] = 'New';

        $data['accountBa'] = DB::table('accounts')
        // ->whereIn('type_code',['11','12','14','15','42','44','46','48'])
        ->get();

        $data['accounts'] = DB::table('accounts')
        // ->whereIn('type_code',['21','22','23','24'])
        ->get();

        $data['nilaiPPN'] = DB::table('attributes')
        ->where('attr_id','mainppn')
        ->value('attr_value');

        $data['nilaiPPH'] = DB::table('attributes')
        ->where('attr_id','mainpph23')
        ->value('attr_value');

        return view("accountPayable.create",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $suppCode = $request->supplier;
        $poNumber = $request->poNumber;
        // $profInvoice = $request->profInvoice;
        // $recNumber = $request->recNumber;
        // $dueDate = $request->dueDate;
        $currency = $request->currency;
        $rate = is_null($request->rate) ? 0 : preg_replace('/[^0-9.]+/', '', $request->rate);
        $invoiceDate= $request->invoiceDate;
        $basisAmount = is_null($request->basisAmount) ? 0 : preg_replace('/[^0-9.]+/', '', $request->basisAmount);
        $accountBasisA = $request->accountBasisA;
        $otherDeduct = 0;
        $account= $request->account;
        $note=$request->note;
        $apDate= $request->apDate;
        $invoiceNumber=$request->invoiceNumber;
        $taxInvoiceNumber=$request->taxInvoiceNumber;

        $recNumberSave = explode(",",$request->recNumberSave);
        $vat=is_null($request->totalPPN) ? 0 : preg_replace('/[^0-9.]+/', '', $request->totalPPN);
        $pph23 = is_null($request->totalPPH) ? 0 : preg_replace('/[^0-9.]+/', '', $request->totalPPH);
        $totalDiscount = is_null($request->totalDiscount) ? 0 : preg_replace('/[^0-9.]+/', '', $request->totalDiscount);
        $grandTotal = is_null($request->grandTotal) ? 0 :  preg_replace('/[^0-9.]+/', '', $request->grandTotal);

        $accountVat ='1100.73';
        $acountTotal = '2000.11';
        $accountPph23 ='2000.14.3';
                
        $status = '1';
        $authorizedBy = "";
        
        // status
        // 1. Draft
        // 2. Update
        // 3. Posting
        // 4. Cancel
        // 5. Paid
        
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
            'iunique' => "Invoice Number : $invoiceNumber on PO: $poNumber has already exist",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) use ($poNumber) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            $column2 = $query->getGrammar()->wrap($parameters[2]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])
                          ->whereRaw("lower({$column2}) = lower(?)", [$poNumber])->count();
        });

        $rule = [
            // 'poNumberDet'  => 'required',
            // 'invoiceNumber'=>'required|iunique:ap_invoice,inv_number,po_number',
            // 'doDate'  => 'required',
        ];

        $this->validate($request,$rule,$messages);

        $hasilUpdate = AppHelpers::resetCode($this->moduleCode);
        $apNumber = $this->getLastCode($this->moduleCode);
        DB::beginTransaction();
        try {
                $rowAffected = DB::table('ap_invoice')->insert([
                    'ap_number' => $apNumber,
                    'inv_date' => $invoiceDate,
                    'old_ap_number' => $apNumber,
                    'po_number' => $poNumber,
                    'supplier_id' => $suppCode,
                    'currency' => $currency,
                    'kurs' => $rate,
                    'basis_amount' => $basisAmount,
                    'total_discount' => $totalDiscount,
                    'vat' => $vat,
                    'other_deduction' => $otherDeduct,
                    'pph23' => $pph23,
                    'grand_total' => $grandTotal,
                    'account_ba'=> $accountBasisA,
                    'account_total' => $acountTotal,
                    'account_vat' => $accountVat,
                    'prepared_by' => Auth::user()->username,
                    'status' => $status,
                    'note' => $note,
                    'created_by' => Auth::user()->username,
                    'updated_by' => Auth::user()->username,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'account_pph' => $accountPph23,
                    'inv_number' => $invoiceNumber,
                    'tax_inv_number' =>$taxInvoiceNumber,
                    'ap_date' =>$apDate
                ]);

                if($rowAffected){
                    $dataReceiving = [];
                    foreach ($recNumberSave as $val) {
                        $dataReceiving[] = [
                            'ap_number' => $apNumber,
                            'rec_number' => $val,
                            'created_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                        ];
                    }
                    DB::table('ap_invoice_detail')->insert($dataReceiving);
                }
               

                DB::commit();

                $title ='Save Invoice';
                $alert  ="success";
                $message  = "$title $apNumber is successfully saved";

                \LogActivity::addToLog($title,"username: $username Status $message");

                return redirect()->route('ap.create')->with(array('title' => $title, 'message' => $message,'alert'=>$alert));

                // return redirect()->back()->with($data);

        } catch (Exception $e) {
            DB::rollBack();
            $title ='Save Invoice';
            $alert  ="warning";
            $message  = "*Invoice $apNumber is failed to save";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(array('title' => $title, 'message' => $message,'alert'=>$alert,'apNumber'=>$apNumber));
        }
        
    }

    public function show(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;

        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";

        $data['id']=$id;
        
        $data['header'] = DB::table('ap_invoice')
        ->where('id',$id)
        ->get()->first();

        $apNumber = $data['header']->ap_number;
        $poNumber = $data['header']->po_number;

        $details = DB::table('ap_invoice_detail')->where('ap_number',$apNumber)->pluck('rec_number');

        $arrayData="";
        foreach($details as $val ){
            $arrayData.=$val.',';
        }
            
        $data['recNumbers'] = substr($arrayData, 0, -1);

        $data['sub_details'] = DB::table('ap_invoice')
        ->leftJoin('third_party', 'third_party.kode', '=', 'ap_invoice.supplier_id')
        ->where('old_ap_number',$apNumber)
        ->where('status','6')
        ->select('ap_invoice.*','nama')
        ->orderBy('id')
        ->get();

        $data['listRec']= DB::table("receiving_hdr") 
        ->whereNotIn(DB::raw("rec_number"), function($query) use ($apNumber) {
            $query->select("rec_number")
            ->from('ap_invoice_detail')
            ->where('ap_number','<>',$apNumber);
        })
        ->orderBy("id")
        ->select("rec_number","do_date","do_number")
        ->get();

        $listRec= DB::table("receiving_hdr") 
        ->whereNotIn(DB::raw("rec_number"), function($query) use ($apNumber) {
            $query->select("rec_number")
            ->from('ap_invoice_detail')
            ->where('ap_number','<>',$apNumber);
        })
        ->pluck('rec_number')->toArray();
        
        
        $data['detailRec'] = DB::table('receiving_det')
        ->leftJoin('receiving_hdr','receiving_hdr.rec_number','receiving_det.rec_number')
        ->leftJoin('article','article.article_code','receiving_det.article_code')
        ->leftJoin(DB::RAW("(select * from purchase_order_det where po_number = '$poNumber') AS po"),function($join){
            $join->on('po.po_number','=','receiving_hdr.po_number')
                ->on('po.article_code','=','receiving_det.article_code');
        })
        ->whereIn('receiving_det.rec_number',$listRec)
        ->where('receiving_det.qty','>',0)
        ->select('article.article_alternative_code as article'
        ,'article.article_desc as desc'
        ,'receiving_det.uom_rec as uom'
        ,db::raw("sum(receiving_det.qty) as qty")
        ,'po.price'
        ,db::raw("round(sum(receiving_det.qty*po.price)) as total"))
        ->groupBy('article.article_alternative_code')
        ->groupBy('article.article_desc')
        ->groupBy('receiving_det.uom_rec')
        ->groupBy('po.price')
        ->get();

        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

        $status = ['DRAFT','UPDATED','POSTED','CANCEL','PAID'];
                
        $data['status'] = $status[$data['header']->status -1];

        $data['currency'] = ['IDR','USD'];
        
        $data['accountBa'] = DB::table('accounts')
        // ->whereIn('type_code',['11','12','14','15','42','44','46','48'])
        ->get();

        $data['accounts'] = DB::table('accounts')
        // ->whereIn('type_code',['21','22','23','24'])
        ->get();

        $data['nilaiPPN'] = DB::table('attributes')
        ->where('attr_id','mainppn')
        ->value('attr_value');

        $data['nilaiPPH'] = DB::table('attributes')
        ->where('attr_id','mainpph23')
        ->value('attr_value');

        $data['statusRevision'] = '';

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$apNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$apNumber,$username);

        // $data['status'] = ['1'=>'DRAFT','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'CLOSED','6'=>'PAID'];
        $status = ['DRAFT','VALIDATED','APPROVED','POSTED','CANCELED','CLOSED','PAID'];
        $data['status'] = $status[$data['header']->status-1];

        return view("accountPayable.show",$data);
        
    }

    public function edit(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;

        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['id']=$id;
        
        $data['header'] = DB::table('ap_invoice')
        ->where('id',$id)
        ->get()->first();

        $apNumber = $data['header']->ap_number;

        $details = DB::table('ap_invoice_detail')->where('ap_number',$apNumber)->pluck('rec_number');

        $arrayData="";
        foreach($details as $val ){
            $arrayData.=$val.',';
        }
            
        $data['recNumbers'] = substr($arrayData, 0, -1);

        $data['sub_details'] = DB::table('ap_invoice')
        ->leftJoin('third_party', 'third_party.kode', '=', 'ap_invoice.supplier_id')
        ->where('old_ap_number',$data['header']->ap_number)
        ->where('status','6')
        ->select('ap_invoice.*','nama')
        ->orderBy('id')
        ->get();

        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$apNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$apNumber,$username);

        // $data['status'] = ['1'=>'DRAFT','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'CLOSED','6'=>'PAID'];
        $status = ['DRAFT','VALIDATED','APPROVED','POSTED','CANCELED','CLOSED','PAID'];
        $data['status'] = $status[$data['header']->status-1];

        $data['currency'] = ['IDR','USD'];

        $data['accountBa'] = DB::table('accounts')
        // ->whereIn('type_code',['11','12','14','15','42','44','46','48'])
        ->get();

        $data['accounts'] = DB::table('accounts')
        // ->whereIn('type_code',['21','22','23','24'])
        ->get();

        $data['nilaiPPN'] = DB::table('attributes')
        ->where('attr_id','mainppn')
        ->value('attr_value');

        $data['nilaiPPH'] = DB::table('attributes')
        ->where('attr_id','mainpph23')
        ->value('attr_value');

        $data['statusRevision'] = '';
        
        return view("accountPayable.edit",$data);
        
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $apNumber=$request->apNumber;
        $suppCode = $request->supplier;
        $poNumber = $request->poNumber;
        // $profInvoice = $request->profInvoice;
        // $recNumber = $request->recNumber;
        // $dueDate = $request->dueDate;
        $currency = $request->currency;
        $rate = is_null($request->rate) ? 0 : preg_replace('/[^0-9.]+/', '', $request->rate);
        $invoiceNumber= $request->invoiceNumber;
        $invoiceDate= $request->invoiceDate;
        $taxInvoiceNumber = $request->taxInvoiceNumber;
        $basisAmount = is_null($request->basisAmount) ? 0 : preg_replace('/[^0-9.]+/', '', $request->basisAmount);
        $accountBasisA = $request->accountBasisA;
        $otherDeduct = 0;
        $account= $request->account;
        $note=$request->note;

        $apDate= $request->apDate;
        $invoiceNumber=$request->invoiceNumber;
        $taxInvoiceNumber=$request->taxInvoiceNumber;

        $recNumberSave = explode(",",$request->recNumberSave);
        $vat=is_null($request->totalPPN) ? 0 : preg_replace('/[^0-9.]+/', '', $request->totalPPN);
        $pph23 = is_null($request->totalPPH) ? 0 : preg_replace('/[^0-9.]+/', '', $request->totalPPH);
        $totalDiscount = is_null($request->totalDiscount) ? 0 : preg_replace('/[^0-9.]+/', '', $request->totalDiscount);
        $grandTotal = is_null($request->grandTotal) ? 0 :  preg_replace('/[^0-9.]+/', '', $request->grandTotal);

        $accountVat ='1100.73';
        $acountTotal = '2000.11';
        $accountPph23 ='2000.14.3';
                
        $status = '1';
        $authorizedBy = "";
        
        // $data['status'] = ['1'=>'DRAFT','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'CLOSED','6'=>'PAID'];
        
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
            'iunique' => "Invoice Number : $invoiceNumber on PO: $poNumber has already exist",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) use ($poNumber) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            $column2 = $query->getGrammar()->wrap($parameters[2]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])
                          ->whereRaw("lower({$column2}) = lower(?)", [$poNumber])->count();
        });

        $rule = [
            // 'poNumberDet'  => 'required',
            // 'invoiceNumber'=>'required|iunique:ap_invoice,inv_number,po_number',
            // 'doDate'  => 'required',
        ];

        $this->validate($request,$rule,$messages);
        
        DB::beginTransaction();
        try {
                $rowAffected=DB::table('ap_invoice')
                ->where('id',$id)
                ->update(
                    [   
                        'inv_date' => $invoiceDate,
                        'po_number' => $poNumber,
                        'currency' => $currency,
                        'kurs' => $rate,
                        'basis_amount' => $basisAmount,
                        'total_discount' => $totalDiscount,
                        'vat' => $vat,
                        'other_deduction' => $otherDeduct,
                        'pph23' => $pph23,
                        'grand_total' => $grandTotal,
                        'account_ba'=> $accountBasisA,
                        'status' => $status,
                        'note' => $note,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'inv_number' => $invoiceNumber,
                        'tax_inv_number' =>$taxInvoiceNumber,
                        'ap_date' =>$apDate,
                        'account_total' => $acountTotal,
                        'account_vat' => $accountVat,
                        'account_pph' => $accountPph23,
                    ]
                );

                DB::table('ap_invoice_detail')->where('ap_number',$apNumber)->delete();

                if($rowAffected){
                    $dataReceiving = [];
                    foreach ($recNumberSave as $val) {
                        $dataReceiving[] = [
                            'ap_number' => $apNumber,
                            'rec_number' => $val,
                            'created_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                        ];
                    }
                     
                    DB::table('ap_invoice_detail')->insert($dataReceiving);
                }
                                                                            
                DB::commit();

                $title ='Update AP Invoice';
                $alert  ="success";
                $message  = "$title $apNumber is successfully updated";

                $data['title'] = $title;
                $data['message'] = $message;
                $data['alert'] = $alert;

                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->route('ap.edit', ['id'=>Crypt::encryptString($id)])->with(array('title' => $title, 'message' => $message,'alert'=>$alert));
                // return redirect()->back()->with($data);

        } catch (Exception $e) {
            DB::rollBack();
            $title ='Update AP Invoice';
            $alert  ="warning";
            $message  = "Invoice $apNumber is failed to update";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(array('title' => $title, 'message' => $message,'alert'=>$alert,'apNumber'=>$apNumber));
        }
        
    }

    public function approve(Request $request)
    {
        $username =  Auth::user()->username;
        $apNumber = $request->apNumber;
        $statusLevelApproval = Approval::approvalLevelPosition($this->moduleCode,$apNumber,$username);        
        $nextLevel = $statusLevelApproval[0]->next_level;
        $status = $statusLevelApproval[0]->next_level == $statusLevelApproval[0]->max_level ? '3' :'2';

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'','5'=>'DELETED','6'=>"CLOSED"];
                
        DB::beginTransaction();
        try {
                $row_affected=DB::table('ap_invoice')
                ->where('ap_number',$apNumber)
                ->update(
                    [
                        'status' => $status,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                if ($row_affected){
                    DB::table('approval_history')->insert([
                        'module_code' => $this->moduleCode,
                        'module_number' => $apNumber,
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
                $message  = "$title $apNumber is successfully Approve-".$nextLevel;
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => $status,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'$apNumber'=>$apNumber));

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Approve $this->title";
            $alert  ="warning";
            $message  = "$title $apNumber is failed to Approve-".$nextLevel;
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('status' => $status,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'$apNumber'=>$apNumber));
        }
    }

    public function posting(Request $request)
    {
      
        // $data['status'] = ['1'=>'DRAFT','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'CLOSED','6'=>'PAID'];

        $username =  Auth::user()->username;
        $apNumber = $request->apNumber;
        $statusAp ="Posted";
        $status = '4';
        $authorizedBy = Auth::user()->username;
        
        
        $rowAffected = DB::table('ap_invoice')
            ->where('ap_number',$apNumber)
            ->update(
                [   
                    'status' => $status,
                    'authorized_by' => $authorizedBy,
                    'authorized_at' => date('Y-m-d H:i:s'),
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );

        if($rowAffected){

            $hasilUpdate = AppHelpers::resetCode($this->voucherCode);
            $vcNumber = $this->getLastCodeVoucher($this->voucherCode);

            $apData = db::table('ap_invoice')
            ->leftJoin('third_party', 'third_party.kode', '=', 'ap_invoice.supplier_id')
            ->select('ap_invoice.*','third_party.nama as supplier_name')
            ->where('ap_number',$apNumber)->first();

            DB::table('kas_hdr')->insert([
                'voucher_number' =>$vcNumber,
                'voucher_type' =>$this->moduleCode,
                'voucher_date' =>date('Y-m-d'), //tanggal posting
                'paid_to' => $apData->supplier_id,
                'description' => $apNumber,
                'amount' => $apData->grand_total,
                'period' =>date('n'),
                'year' =>date('Y'),                        
                'note' => $apData->note,
                'status' => '1',
                'created_by' => Auth::user()->username,
                'updated_by' => Auth::user()->username,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
    
            $dataSet = [];
            $dataSet[] = [
                'voucher_number' => $vcNumber,
                'account' =>$apData->account_ba,
                'description' => $vcNumber.' '.$apData->supplier_name,
                'debit' => $apData->basis_amount,
                'credit' => 0,
                'reference' => $apNumber,
                'created_by' => Auth::user()->username,
                'updated_by' => Auth::user()->username,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $dataSet[] = [
                'voucher_number' => $vcNumber,
                'account' =>$apData->account_vat,
                'description' => $vcNumber.' '.$apData->supplier_name,
                'debit' => $apData->vat,
                'credit' => 0,
                'reference' => $apNumber,
                'created_by' => Auth::user()->username,
                'updated_by' => Auth::user()->username,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            //belum ada no account nya
            // if($apData->total_discount > 0){
            //     $dataSet[] = [
            //         'voucher_number' => $vcNumber,
            //         'account' =>$apData->account_total,
            //         'description' => $vcNumber.' '.$apData->supplier_name,
            //         'debit' => 0,
            //         'credit' => $apData->total_discount,
            //         'reference' => $apNumber,
            //         'created_by' => Auth::user()->username,
            //         'updated_by' => Auth::user()->username,
            //         'created_at' => date('Y-m-d H:i:s'),
            //         'updated_at' => date('Y-m-d H:i:s')
            //     ];  
            // }

            if($apData->pph23 > 0){
                $dataSet[] = [
                    'voucher_number' => $vcNumber,
                    'account' =>$apData->account_pph,
                    'description' => $vcNumber.' '.$apData->supplier_name,
                    'debit' => 0,
                    'credit' => $apData->pph23,
                    'reference' => $apNumber,
                    'created_by' => Auth::user()->username,
                    'updated_by' => Auth::user()->username,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];  
            }

            $dataSet[] = [
                'voucher_number' => $vcNumber,
                'account' =>$apData->account_total,
                'description' => $vcNumber.' '.$apData->supplier_name,
                'debit' => 0,
                'credit' => $apData->grand_total,
                'reference' => $apNumber,
                'created_by' => Auth::user()->username,
                'updated_by' => Auth::user()->username,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];  
    
            DB::table('kas_det')->insert($dataSet);
        }

        if ($rowAffected){
            DB::commit();
            $title ='Posting input invoice';
            $alert  ="success";
            $message  = "Posting $apNumber is successfully posted";
            \LogActivity::addToLog('AP Invoice update ',"username: $username Status $message");
            return response()->json(array('status' => 1, 'message' => $message,'alert'=>$alert,'apNumber'=>$apNumber,'statusAp'=>$statusAp));
        }else{
            DB::rollBack();
            $title ='Posting input invoice';
            $alert  ="warning";
            $message  = "Posting $apNumber is failed to posted";
            \LogActivity::addToLog('Posting AP ',"username: $username Status $message");
            return response()->json(array('status' => 0, 'message' => $message,'alert'=>$alert,'apNumber'=>$apNumber));            
        }
    }

    public function revision(Request $request){
        $username =  Auth::user()->username;
        $id=$request->id;
        $apOrigin = $request->apNumber;
        $numRevision = $request->numRevision ? $request->numRevision +1 : 1 ;
        $apNew = $apOrigin.'-R'.$numRevision;

        $data['title'] = "Revision Invoice";
        $data['subtitle'] = "Revision Invoice";
        
        $sqlAp = "INSERT into ap_invoice
        (
            ap_number,
            old_ap_number,
            inv_number,
            proforma_inv_number,
            tax_inv_number,
            inv_date,
            rec_number,
            po_number,
            supplier_id,
            rec_date,
            due_date,
            currency,
            kurs,
            basis_amount,
            vat,
            pph23,
            pph23_type,
            other_deduction,
            account,
            authorized_by,
            authorized_at,
            prepared_by,
            rec_type,
            status,
            note,
            num_revision,
            revised_by,
            revised_at,
            updated_by,
            updated_at
        )
        select 
            '$apNew',
            '$apOrigin',
            inv_number,
            proforma_inv_number,
            tax_inv_number,
            inv_date,
            rec_number,
            po_number,
            supplier_id,
            rec_date,
            due_date,
            currency,
            kurs,
            basis_amount,
            vat,
            pph23,
            pph23_type,
            other_deduction,
            account,
            authorized_by,
            authorized_at,
            prepared_by,
            rec_type,
            '6',
            note,
            $numRevision,
            '$username',
            '".date('Y-m-d H:i:s')."',
            '$username',
            '".date('Y-m-d H:i:s')."'
        from ap_invoice where ap_number = '$apOrigin'";

        $rowAffected =  DB::select($sqlAp);

        // status:
        // status
        // 1. Draft
        // 2. Updated
        // 3. Posted
        // 4. Canceled
        // 5. Paid
        // 6. Revised

        DB::table('ap_invoice')
        ->where('ap_number',$apOrigin)
        ->update(
            [
                'num_revision' => $numRevision,
                'status' => '1',
                'revised_by'=>Auth::user()->username,
                'revised_at'=> date('Y-m-d H:i:s'),
                'updated_by' => Auth::user()->username,
                'updated_at' => date('Y-m-d H:i:s')
            ]
        );
        
        return redirect()->route('ap.edit', ['id' =>Crypt::encryptString($id)]);
        
    }

    public function destroy(Request $request)
    {
        // status
        // 1. Draft
        // 2. Updated
        // 3. Posted
        // 4. Canceled
        // 5. Paid
        // 6. Revised

        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);
        $status = "4";

        $data= DB::table('ap_invoice')
        ->where('id',$id)
        ->get()->first();

        $apNumber = $data->ap_number;
        $invNumber = $data->inv_number;
        $note = $data->note;

        $rowAffected=DB::table('ap_invoice')
        ->where('ap_number',$apNumber)
        ->update(
            [   
                'inv_number' => $invNumber."(C)",
                'status' => $status,
                'note' => $note,
                'updated_by' => Auth::user()->username,
                'updated_at' => date('Y-m-d H:i:s')
            ]
        );

        if ($rowAffected){
            DB::commit();
            $title ='Cancel input invoice';
            $alert  ="success";
            $message  = "$apNumber is successfully cancel";
            \LogActivity::addToLog('AP Invoice update ',"username: $username Status $message");
            return redirect()->back()->with(array('status' => 1, 'message' => $message,'alert'=>$alert,'apNumber'=>$apNumber));

        }else{
            DB::rollBack();
            $title ='Cancel input invoice';
            $alert  ="warning";
            $message  = "$apNumber is failed to cancel";
            \LogActivity::addToLog('Posting AP ',"username: $username Status $message");
            return response()->back()->with(array('status' => 0, 'message' => $message,'alert'=>$alert,'apNumber'=>$apNumber));
        }

    }

    public function list(Request $request)
    {
     
        // $data['status'] = ['1'=>'DRAFT','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'CLOSED','6'=>'PAID'];

        $searchPo = $request->searchPo;
        $searchAp = $request->searchAp;
        $searchSupplier = $request->searchSupplier;
        $searchStatus = $request->searchStatus;
        $apDate = $request->apDate;
        $fromDate = "";
        $toDate = "";
       
        if ($apDate){
            $date = explode("to",$apDate);
            $fromDate = trim($date[0]);
            $toDate = trim($date[1]);

            // if(count($date)>1){
            //     $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
            //     $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            // }else{
            //     $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
            //     $toDate = $fromDate; 
            // }
        }

        $data = DB::table('ap_invoice')
        ->leftJoin('third_party','third_party.kode','ap_invoice.supplier_id')
        ->where(function ($query) use ($searchAp,$searchPo,$searchSupplier,$searchStatus,$apDate,$fromDate,$toDate) {
            $searchPo ? $query->where('po_number','ilike','%'.$searchPo.'%') : '';
            $searchAp ? $query->where('ap_number','ilike','%'.$searchAp.'%') : '';
            $searchSupplier ? $query->where('supplier_id','ilike','%'.$searchSupplier.'%') : '';
            $searchStatus ? $query->where('status','=','.$searchStatus.') : '';
            $apDate ? $query->whereBetween('inv_date', [$fromDate, $toDate]) : '';
        })
        ->where('ap_invoice.status','<>','6')
        ->select(
            'ap_invoice.*'
            ,DB::raw("(select STRING_AGG ( a.rec_number,',' ORDER BY a.id) as list_rec from ap_invoice_detail a where ap_number = ap_invoice.ap_number) as list_rec")
            ,db::raw("concat(third_party.kode,'-',third_party.nama) as supplier_name")
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

            if ( $data->status == '2' or $data->status == '1') {
                // if (Auth::user()->can('kasPenerimaan-approve')) {
                $buttons .=     '<a href="'. route('ap.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="check"></i>
                                    <span>'. __("Approve") .'</span>
                                </a>';
                // }
            }

            if (($data->status != '3') && ($data->status != '4') && ($data->status != '5')){
                if (Auth::user()->can('ap-edit')) {
                $buttons .=         '<a href="'. route('ap.edit',['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="file-text"></i>
                                        <span>'. __("Edit") .'</span>
                                    </a>';
                }
            }

            $buttons .=         '<a href="'. route('ap.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                            <i data-feather="list"></i>
                            <span>'. __("Detail") .'</span>
                        </a>';

            // if (($data->status != '2') && ($data->status != '3') && ($data->status != '4') && ($data->status != '5')){
            //     if (Auth::user()->can('ap-edit')) {
            //     $buttons .=         '<a href="'. route('ap.edit',['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
            //                             <i data-feather="check"></i>
            //                             Update
            //                         </a>';
            //     }
            // }

            if (($data->status == '3')){
                // if (Auth::user()->can('ap-edit')) {
                $buttons .=         '<a href="'. route('ap.edit',['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="check"></i>
                                        <span>'. __("Posting") .'</span>
                                    </a>';
                // }
            }
                                
            // if ( $data->status == '3' ){
            //     if (Auth::user()->can('ap-revision')) {
            //         $buttons .= '<a href="'. route('ap.revision', ['id'=>$data->id,'apNumber'=>$data->ap_number,'numRevision'=>$data->num_revision,'statusRevision'=>'revision']) .'" class="dropdown-item">
            //                         <i data-feather="copy"></i>
            //                            Revision
            //                     </a>';
            //     }
            // }
                
            // if (($data->status != '5' && $data->status != '4' )){
            //     if (Auth::user()->can('receiving-delete')) {
            //     $buttons .=         "<a href='javascript:;'
            //                             id='deleteButton'
            //                             class='dropdown-item'
            //                             data-toggle='modal'
            //                             data-target='#smallModalCancel'
            //                             data-href='". route("ap.destroy", ["id"=>Crypt::encryptString($data->id)]) ."'>
            //                             <i data-feather='trash-2'></i>
            //                             Cancel
            //                         </a>";
            //     }
            // }
            if (($data->status == '4')){
                $buttons .=         '<a href="'. route('ap.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
                                    <i data-feather="printer"></i>
                                    <span>'. __("Print") .'</span>
                                </a>';
            }

            $buttons .=     '</div>
                        </div>';

            return $buttons;
            })
        
        // ->addColumn('ap_number', function ($data) {
        //     return '<a href="'. route('ap.show',['apNumber'=>Crypt::encryptString($data->id)]) .'" 
        //                 type="button" 
        //                 style="text-align: left;">
        //                 <span>'.$data->ap_number.'</span>
        //             </a>';            
        // })
        
        ->addColumn('status', function ($data) {
            $badges=['badge-light-primary','badge-light-info','badge-light-success','badge-light-warning','badge-light-danger','badge-light-dark','badge-light-secondary','badge-light-danger'];
            $statusCode = ['DRAFT','VALIDATED','APPROVED','POSTED','CANCELED','CLOSED','PAID'];
            return "<div class='badge badge-pill ".$badges[$data->status - 1]."'>".$statusCode[$data->status - 1]."</div>";
        })
        ->rawColumns(['action','status','ap_number'])
        ->make(true);
    }

    public function print(Request $request)
    {
        $id=Crypt::decryptString($request->id);

        $apNumber = DB::table('ap_invoice')->where('id',$id)->value('ap_number');

        $data['title'] ='Invoice Supplier';

        $data['header']=DB::table('kas_hdr')
        ->select('kas_hdr.*'
        ,'description as receive_name'
        )
        ->where('kas_hdr.description',$apNumber)
        ->first();

        $vcNumber=$data['header']->voucher_number;
       
        $data['details']=DB::table('kas_det')
        ->leftJoin('accounts','accounts.account','kas_det.account')
        ->select('kas_det.*','accounts.description as account_name')
        ->where('voucher_number',$vcNumber)
        ->orderBy('id')
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
        ->where('module_number',$apNumber)
        ->where('approval_order',1)
        ->first();

        $data['approval2']=DB::table('approval_history')
        ->leftJoin('users','users.username','approval_history.username')
        ->where('module_code',$this->moduleCode)
        ->where('module_number',$apNumber)
        ->where('approval_order',2)
        ->first();

        $data['approval3']=DB::table('approval_history')
        ->leftJoin('users','users.username','approval_history.username')
        ->where('module_code',$this->moduleCode)
        ->where('module_number',$apNumber)
        ->where('approval_order',3)
        ->first();

        $data['apNumber']=$apNumber;

        return view('accountPayable.print',$data);

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
