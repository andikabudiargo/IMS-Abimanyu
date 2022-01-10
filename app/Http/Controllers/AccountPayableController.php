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

class AccountPayableController extends Controller
{
    public function index(Request $request)
    {
        $data['title'] = "List invoice";
        
        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

        // status
        // 1. Draft
        // 2. Updated
        // 3. Posted
        // 4. Canceled
        // 5. Paid

        $data['status'] = ['1'=>'Draft','2'=>'Updated','3'=>'Posted','4'=>'Canceled','5'=>'Paid'];
            
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

        $data= DB::table("purchase_order_hdr") 
        ->where("supplier_id",$supp)
        ->whereIn('po_number', function($query) use ($supp) {
            $query->select('po_number')
            ->from('receiving_hdr') 
            ->where('supplier_id',$supp);
        })
        ->where("status","3")
        ->orderBy("po_number")
        ->select("po_number","po_date")
        ->get();          

        $output .='<option value=""></option>';            
        foreach ($data as $row){
            $output .='<option data-po-date="'.$row->po_date.'" value="'.$row->po_number.'">'.$row->po_number.'</option>';            
        }        
        
        return $output;
    }

    public function listRec(Request $request)
    {
        $poNumber= $request->value;      
        $output="";

        $data= DB::table("receiving_hdr") 
        ->where("po_number",$poNumber)
        ->where("status","3")
        // ->whereNotIn(DB::raw("rec_number"), function($query) use ($poNumber) {
        //     $query->select(DB::raw("rec_number"))
        //     ->from('ap_invoice') 
        //     ->where('po_number',$poNumber);
        // })
        ->orderBy("rec_number")
        ->select("rec_number","do_date")
        ->get();          

        $output .='<option value=""></option>';            
        foreach ($data as $row){
            $output .='<option data-do-date="'.$row->do_date.'" value="'.$row->rec_number.'">'.$row->rec_number.'</option>';            
        }        
        
        return $output;
    }

    public function detailRec(Request $request){
        $poNumber = $request->poNumber;
        $data = DB::select("SELECT 
                            a.*
                            ,b.nama
                            ,(select round(sum(qty*price)) as total_po from purchase_order_det where po_number= a.po_number) as total_po 
                            ,round((select sum(qty*price) from receiving_det where rec_number = a.rec_number)) as basis_amount
                            ,(select ppn from purchase_order_hdr where po_number =a.po_number) as vat
                            ,(select pkp from purchase_order_hdr where po_number =a.po_number) as pkp
                            ,(select currency from purchase_order_hdr where po_number =a.po_number) as currency
                            ,(select kurs from purchase_order_hdr where po_number =a.po_number) as kurs
                            ,to_char(to_date(rec_date,'dd-mm-yyyy')+(select termin from purchase_order_hdr where po_number = a.po_number),'dd-mm-yyyy') as due_date
                            ,((select sum(qty*price) from receiving_det where po_number = a.po_number) - (select sum(qty*price) from purchase_order_det where po_number = a.po_number)) as po_balance
                            from receiving_hdr a
                            left join third_party b on b.kode = a.supplier_id
                            where po_number = '$poNumber'");
         return response()->json($data);
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
        $data['title'] = "Create Invoice";
        $data['subtitle'] = "Create Invoice";
        
        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

        $data['currency'] = ['IDR','USD'];
        $data['status'] = 'New';
        $data['accounts'] = DB::table('accounts')->get();

        return view("accountPayable.create",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $suppCode = $request->input('supplier');
        $poNumber = $request->input('poNumberDet');
        $recNumber = $request->input('recNumber');
        $recDate = $request->input('recDate');
        $dueDate = $request->input('dueDate');
        $currency = $request->input('currency');
        $rate = is_null($request->input('rate')) ? 0 : preg_replace('/[^0-9.]+/', '', $request->input('rate'));
        $invoiceNumber= $request->input('invoiceNumber');
        $invoiceDate= $request->input('invoiceDate');
        $taxInvoiceNumber= $request->input('taxInvoiceNumber');
        $basisAmount = is_null($request->input('basisAmount')) ? 0 : preg_replace('/[^0-9.]+/', '', $request->input('basisAmount'));
        $vat = is_null($request->input('vat')) ? 0 : preg_replace('/[^0-9.]+/', '', $request->input('vat'));
        $otherDeduct = is_null($request->input('otherDeduct')) ? 0 : preg_replace('/[^0-9.]+/', '', $request->input('otherDeduct'));
        $account= $request->input('account');
        $pph23 = $request->input('pph23Check') == 'on'? is_null($request->input('pph23')) ? 0 : preg_replace('/[^0-9.]+/', '', $request->input('pph23')) : 0;
        $pph23Type= $request->input('pph23Check') == 'on'? is_null($request->input('pph23'))? "":$request->input('pph23Type') : '';
        
        $status = '1';
        $authorizedBy = "";
        $note="";

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
            'poNumberDet'  => 'required',
            'invoiceNumber'=>'required|iunique:ap_invoice,inv_number,po_number',
            // 'doDate'  => 'required',
        ];

        $this->validate($request,$rule,$messages);

        $hasilUpdate = AppHelpers::resetCode('AP');
        $apNumber = $this->getLastCode('AP');
        DB::beginTransaction();
        try {
                DB::table('ap_invoice')->insert([
                    'ap_number' => $apNumber,
                    'inv_number' => $invoiceNumber,
                    'tax_inv_number' => $taxInvoiceNumber,
                    'old_ap_number' => $apNumber,
                    'inv_date' => $invoiceDate,
                    'rec_number' => $recNumber,
                    'po_number' => $poNumber,
                    'supplier_id' => $suppCode,
                    'rec_date' => $recDate,
                    'due_date' => $dueDate,
                    'currency' => $currency,
                    'kurs' => $rate,
                    'basis_amount' => $basisAmount,
                    'vat' => $vat,
                    'pph23' => $pph23,
                    'pph23_type' => $pph23Type,
                    'other_deduction' => $otherDeduct,
                    'account' => $account,
                    'prepared_by' => Auth::user()->username,
                    'status' => $status,
                    'note' => $note,
                    'created_by' => Auth::user()->username,
                    'updated_by' => Auth::user()->username,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                DB::commit();

                $title ='Save Invoice';
                $alert  ="success";
                $message  = "$title $apNumber is successfully saved";

                $data['details'] = DB::table('ap_invoice')
                ->where('ap_number',$apNumber)
                ->get()->first();
                
                $data['supps'] = DB::table('third_party')
                ->where ('third_party_type','=','supp')
                ->orderBy('nama')
                ->get();

                $data['currency'] = ['IDR','USD'];

                $data['accounts'] = DB::table('accounts')
                ->get();

                $data['status'] = 'Saved';

                $data['title'] = $title;
                $data['message'] = $message;
                $data['alert'] = $alert;

                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with($data);

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
        $apNumber=Crypt::decryptString($request->apNumber);
        $data['title'] = "Detail Invoice";
        $data['subtitle'] = "Detail Invoice";

        $data['details'] = DB::table('ap_invoice')
        ->where('ap_number',$apNumber)
        ->get()->first();

        $data['sub_details'] = DB::table('ap_invoice')
        ->leftJoin('third_party', 'third_party.kode', '=', 'ap_invoice.supplier_id')
        ->where('old_ap_number',$apNumber)
        ->select('ap_invoice.*','nama')
        ->orderBy('id')
        ->get();

        $statusEdit = ['Draft','Updated','Posted','Cancel','Paid'];
                
        $data['statusEdit'] = $statusEdit[$data['details']->status -1];

        $data['accounts'] = DB::table('accounts')->get();

        return view("accountPayable.show",$data);
        
    }

    public function edit(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Edit Invoice";
        $data['subtitle'] = "Edit Invoice";

        $data['details'] = DB::table('ap_invoice')
        ->where('id',$id)
        ->get()->first();

        $data['sub_details'] = DB::table('ap_invoice')
        ->leftJoin('third_party', 'third_party.kode', '=', 'ap_invoice.supplier_id')
        ->where('old_ap_number',$data['details']->ap_number)
        ->where('status','6')
        ->select('ap_invoice.*','nama')
        ->orderBy('id')
        ->get();

        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

        $statusRec = ['Draft','Updated','Posted','Cancel','Paid'];
                
        $data['statusEdit'] = $statusRec[$data['details']->status -1];

        $data['currency'] = ['IDR','USD'];
        $data['status'] = 'New';
        $data['accounts'] = DB::table('accounts')->get();

        return view("accountPayable.edit",$data);
        
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $apNumber = $request->input('apNumber');
        $suppCode = $request->input('supplier');
        $poNumber = $request->input('poNumberDet');
        $recNumber = $request->input('recNumber');
        $recDate = $request->input('recDate');
        $dueDate = $request->input('dueDate');
        $currency = $request->input('currency');
        $rate = is_null($request->input('rate')) ? 0 : preg_replace('/[^0-9.]+/', '', $request->input('rate'));
        $invoiceNumber= $request->input('invoiceNumber');
        $invoiceDate= $request->input('invoiceDate');
        $taxInvoiceNumber= $request->input('taxInvoiceNumber');
        $basisAmount = is_null($request->input('basisAmount')) ? 0 : preg_replace('/[^0-9.]+/', '', $request->input('basisAmount'));
        $vat = is_null($request->input('vat')) ? 0 : preg_replace('/[^0-9.]+/', '', $request->input('vat'));
        $pph23 = $request->input('pph23Check') == 'on'? is_null($request->input('pph23')) ? 0 : preg_replace('/[^0-9.]+/', '', $request->input('pph23')) : 0;
        $pph23Type= $request->input('pph23Check') == 'on'? is_null($request->input('pph23'))? "":$request->input('pph23Type') : '';
        $otherDeduct = is_null($request->input('otherDeduct')) ? 0 : preg_replace('/[^0-9.]+/', '', $request->input('otherDeduct'));
        $account= $request->input('account');
        $status = '2';
        $authorizedBy = "";
        $note="";
    
        // status
        // 1. Draft
        // 2. Update
        // 3. Posting
        // 4. Cancel
        // 5. Paid

        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
        ];
        
        $rule = [
            'poNumberDet'  => 'required',
            'invoiceNumber' => 'required'
        ];
        
        $this->validate($request,$rule,$messages);
        
        DB::beginTransaction();
        try {
                $row_affected=DB::table('ap_invoice')
                ->where('ap_number',$apNumber)
                ->update(
                    [   
                        'inv_number' => $invoiceNumber,
                        'tax_inv_number' => $taxInvoiceNumber,
                        'inv_date' => $invoiceDate,
                        'rec_number' => $recNumber,
                        'po_number' => $poNumber,
                        'supplier_id' => $suppCode,
                        'rec_date' => $recDate,
                        'due_date' => $dueDate,
                        'currency' => $currency,
                        'kurs' => $rate,
                        'basis_amount' => $basisAmount,
                        'vat' => $vat,
                        'pph23' => $pph23,
                        'pph23_type' => $pph23Type,
                        'other_deduction' => $otherDeduct,
                        'account' => $account,
                        'prepared_by' => Auth::user()->username,
                        'status' => $status,
                        'note' => $note,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );
                                                                            
                DB::commit();

                $title ='Edit Invoice';
                $alert  ="success";
                $message  = "$title $apNumber is successfully update";

                $data['details'] = DB::table('ap_invoice')
                ->where('ap_number',$apNumber)
                ->get()->first();
                
                $data['supps'] = DB::table('third_party')
                ->where ('third_party_type','=','supp')
                ->orderBy('nama')
                ->get();

                $data['currency'] = ['IDR','USD'];

                $data['accounts'] = DB::table('accounts')
                ->get();

                $statusRec = ['Draft','Update','Posted','Cancel','Paid'];
                $data['statusEdit'] = $statusRec[$data['details']->status -1];

                $data['title'] = $title;
                $data['message'] = $message;
                $data['alert'] = $alert;

                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with($data);

        } catch (Exception $e) {
            DB::rollBack();
            $title ='Update Invoice';
            $alert  ="warning";
            $message  = "*Invoice $apNumber is failed to update";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(array('title' => $title, 'message' => $message,'alert'=>$alert,'apNumber'=>$apNumber));
        }
        

    }

    public function posting(Request $request)
    {
        // status
        // 1. Draft
        // 2. Updated
        // 3. Posted
        // 4. Canceled
        // 5. Paid
        // 6. Revised

        $username =  Auth::user()->username;
        $apNumber = $request->apNumber;
        $recType = "NORMAL";
        $statusAp ="Posted";
        $status = '3';
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

        if ($rowAffected){
            DB::commit();
            $title ='Posting input invoice';
            $alert  ="success";
            $message  = "Posting $apNumber is successfully updated";
            \LogActivity::addToLog('AP Invoice update ',"username: $username Status $message");
            return response()->json(array('status' => 1, 'message' => $message,'alert'=>$alert,'apNumber'=>$apNumber,'statusAp'=>$statusAp));
        }else{
            DB::rollBack();
            $title ='Posting input invoice';
            $alert  ="warning";
            $message  = "Posting $apNumber is failed to updated";
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

        $data['title'] = "Edit Invoice";
        $data['subtitle'] = "Edit Invoice";
        
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
                'note' => $note." (Cancel)",
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
            $title ='Cancelinput invoice';
            $alert  ="warning";
            $message  = "$apNumber is failed to cancel";
            \LogActivity::addToLog('Posting AP ',"username: $username Status $message");
            return response()->back()->with(array('status' => 0, 'message' => $message,'alert'=>$alert,'apNumber'=>$apNumber));

        }

    }

    public function list(Request $request)
    {
     
        // status
        // 1. Draft
        // 2. Updated
        // 3. Posted
        // 4. Canceled
        // 5. Paid
        // 6. Revised

        $searchRec = strtolower($request->searchRec);
        $searchPo = strtolower($request->searchPo);
        $searchInv = strtolower($request->searchInv);
        $searchSupplier = $request->searchSupplier;
        $searchStatus = $request->searchStatus;
        $recDate = $request->recDate;
       

        $filter='';
        
        // $filter.="status <> 6 ";
        
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
            $filter.= "to_date(inv_date, 'DD/MM/YYYY')  BETWEEN to_date('$date1', 'DD/MM/YYYY') and to_date('$date2', 'DD/MM/YYYY') and ";
        }
        
        // if ($filter !=''){
        //     $filter=" where ".substr($filter,0,-4);
        // }

        $data = DB::select("SELECT *,
        (select concat(kode,'-',nama) from third_party where kode = supplier_id limit 1) as supp_name,
        (basis_amount+vat+pph23)-other_deduction as total
        from ap_invoice a where $filter status != '6' ");

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            if (($data->status != '3') && ($data->status != '4')){
                if (Auth::user()->can('ap-edit')) {
                $buttons .=         '<a href="'. route('ap.edit',['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="file-text"></i>
                                        Edit
                                    </a>';
                }
            }

            $buttons .=         '<a href="'. route('ap.show', ['apNumber'=>Crypt::encryptString($data->ap_number)]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    Detail
                                </a>';
                                
            if ( $data->status == '3' ){
                if (Auth::user()->can('ap-revision')) {
                    $buttons .= '<a href="'. route('ap.revision', ['id'=>$data->id,'apNumber'=>$data->ap_number,'numRevision'=>$data->num_revision]) .'" class="dropdown-item">
                                    <i data-feather="copy"></i>
                                       Revision
                                </a>';
                }
            }
                
            if (($data->status != '5' && $data->status != '4' )){
                if (Auth::user()->can('receiving-delete')) {
                $buttons .=         "<a href='javascript:;'
                                        id='deleteButton'
                                        class='dropdown-item'
                                        data-toggle='modal'
                                        data-target='#smallModalCancel'
                                        data-href='". route("ap.destroy", ["id"=>Crypt::encryptString($data->id)]) ."'>
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
            $statusRec = ['Draft','Updated','Posted','Canceled','Paid','Revised'];
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
