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

class AccountPayableProformaController extends Controller
{
    public function index(Request $request)
    {
        $data['title'] = "List Proforma Invoice";
        
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
            
        return view("accountPayableProforma.index",$data);
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

    public function poDetail(Request $request)
    {
        $po = $request->poNumber;
        $data = DB::select("SELECT *,
                        (select currency from purchase_order_hdr where po_number = a.po_number) as currency ,
                        (select kurs from purchase_order_hdr where po_number = a.po_number) as kurs
                        from 
                        (SELECT po_number,
                        round(sum(qty*price)) as total_po,
                        sum(ppn) as ppn
                        from purchase_order_det 
                        where po_number = '$po'
                        group by po_number) a");
        return response()->json($data);
    }

    public function create(Request $request)
    {
        $data['title'] = "Create Proforma Invoice";
        $data['subtitle'] = "Create Proforma Invoice";
        
        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

        $data['currency'] = ['IDR','USD'];
        $data['status'] = 'New';
        $data['accounts'] = DB::table('accounts')->get();

        return view("accountPayableProforma.create",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $suppCode = $request->input('supplier');
        $poNumber = $request->input('poNumberDet');
        $currency = $request->input('currency');
        $rate = is_null($request->input('rate')) ? 0 : preg_replace('/[^0-9.]+/', '', $request->input('rate'));
        $invoiceNumber= $request->input('invoiceNumber');
        $invoiceDate= $request->input('invoiceDate');
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
            // 'invoiceNumber'=>'required|iunique:ap_pro_invoice,inv_number,po_number',
            // 'doDate'  => 'required',
        ];

        $this->validate($request,$rule,$messages);

        $hasilUpdate = AppHelpers::resetCode('PRO');
        $piNumber = $this->getLastCode('PRO');
        DB::beginTransaction();
        try {
                DB::table('ap_pro_invoice')->insert([
                    'pi_number' => $piNumber,
                    'inv_number' => $invoiceNumber,
                    'old_pi_number' => $piNumber,
                    'inv_date' => $invoiceDate,
                    'po_number' => $poNumber,
                    'supplier_id' => $suppCode,
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

                $title ='Save Proforma Invoice';
                $alert  ="success";
                $message  = "$title $piNumber is successfully saved";

                $data['details'] = DB::table('ap_pro_invoice')
                ->where('pi_number',$piNumber)
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
            $message  = "*Invoice $piNumber is failed to save";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(array('title' => $title, 'message' => $message,'alert'=>$alert,'piNumber'=>$piNumber));
        }
        
    }

    public function show(Request $request)
    {
        $piNumber=Crypt::decryptString($request->piNumber);
        $data['title'] = "Detail Proforma Invoice";
        $data['subtitle'] = "Detail Proforma Invoice";

        $data['details'] = DB::table('ap_pro_invoice')
        ->where('pi_number',$piNumber)
        ->select('ap_pro_invoice.*',DB::raw('(basis_amount + vat + pph23)-other_deduction as total'))
        ->get()->first();

        $data['sub_details'] = DB::table('ap_pro_invoice')
        ->leftJoin('third_party', 'third_party.kode', '=', 'ap_pro_invoice.supplier_id')
        ->where('old_pi_number',$piNumber)
        ->select('ap_pro_invoice.*','nama',DB::raw('(basis_amount + vat + pph23)-other_deduction as total'))
        ->orderBy('id')
        ->get();

        $statusEdit = ['Draft','Updated','Posted','Cancel','Paid'];
                
        $data['statusEdit'] = $statusEdit[$data['details']->status -1];

        $data['accounts'] = DB::table('accounts')->get();

        return view("accountPayableProforma.show",$data);
        
    }

    public function edit(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Edit Proforma Invoice";
        $data['subtitle'] = "Edit Proforma Invoice";

        $data['details'] = DB::table('ap_pro_invoice')
        ->where('id',$id)
        ->select('ap_pro_invoice.*',DB::raw('(basis_amount + vat + pph23)-other_deduction as total'))
        ->get()->first();

        $data['sub_details'] = DB::table('ap_pro_invoice')
        ->leftJoin('third_party', 'third_party.kode', '=', 'ap_pro_invoice.supplier_id')
        ->where('old_pi_number',$data['details']->pi_number)
        ->where('status','6')
        ->select('ap_pro_invoice.*','nama',DB::raw('(basis_amount + vat + pph23)-other_deduction as total'))
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

        return view("accountPayableProforma.edit",$data);
        
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $piNumber = $request->input('piNumber');
        $suppCode = $request->input('supplier');
        $poNumber = $request->input('poNumberDet');
        $currency = $request->input('currency');
        $rate = is_null($request->input('rate')) ? 0 : preg_replace('/[^0-9.]+/', '', $request->input('rate'));
        // $invoiceNumber= $request->input('invoiceNumber');
        $invoiceDate= $request->input('invoiceDate');
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
            // 'invoiceNumber' => 'required'
        ];
        
        $this->validate($request,$rule,$messages);
        
        DB::beginTransaction();
        try {
                $row_affected=DB::table('ap_pro_invoice')
                ->where('pi_number',$piNumber)
                ->update(
                    [   
                        // 'inv_number' => $invoiceNumber,
                        'inv_date' => $invoiceDate,
                        'po_number' => $poNumber,
                        'supplier_id' => $suppCode,
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

                $title ='Edit Proforma Invoice';
                $alert  ="success";
                $message  = "$title $piNumber is successfully update";

                $data['details'] = DB::table('ap_pro_invoice')
                ->where('pi_number',$piNumber)
                ->select('ap_pro_invoice.*',DB::raw('(basis_amount + vat + pph23)-other_deduction as total'))
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
            $title ='Update Proforma Invoice';
            $alert  ="warning";
            $message  = "$title $piNumber is failed to update";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(array('title' => $title, 'message' => $message,'alert'=>$alert,'piNumber'=>$piNumber));
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
        $piNumber = $request->piNumber;
        $recType = "NORMAL";
        $statusAp ="Posted";
        $status = '3';
        $authorizedBy = Auth::user()->username;
        
        
        $rowAffected = DB::table('ap_pro_invoice')
            ->where('pi_number',$piNumber)
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
            $title ='Posting Proforma invoice';
            $alert  ="success";
            $message  = "$title $piNumber is successfully updated";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('status' => 1, 'message' => $message,'alert'=>$alert,'piNumber'=>$piNumber,'statusAp'=>$statusAp));
        }else{
            DB::rollBack();
            $title ='Posting Proforma invoice';
            $alert  ="warning";
            $message  = "$title $piNumber is failed to updated";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('status' => 0, 'message' => $message,'alert'=>$alert,'piNumber'=>$piNumber));            
        }
    }

    public function revision(Request $request){
        $username =  Auth::user()->username;
        $id=$request->id;
        $piOrigin = $request->piNumber;
        $numRevision = $request->numRevision ? $request->numRevision +1 : 1 ;
        $piNew = $piOrigin.'-R'.$numRevision;

        $data['title'] = "Edit Invoice";
        $data['subtitle'] = "Edit Invoice";
        
        $sqlAp = "INSERT into ap_pro_invoice
        (
            pi_number,
            old_pi_number,
            inv_number,
            inv_date,
            po_number,
            supplier_id,
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
            '$piNew',
            '$piOrigin',
            inv_number,
            inv_date,
            po_number,
            supplier_id,
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
        from ap_pro_invoice where pi_number = '$piOrigin'";

        $rowAffected =  DB::select($sqlAp);

        // status:
        // status
        // 1. Draft
        // 2. Updated
        // 3. Posted
        // 4. Canceled
        // 5. Paid
        // 6. Revised

        DB::table('ap_pro_invoice')
        ->where('pi_number',$piOrigin)
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
        
        return redirect()->route('apProforma.edit', ['id' =>Crypt::encryptString($id)]);
        
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

        $data= DB::table('ap_pro_invoice')
        ->where('id',$id)
        ->get()->first();

        $piNumber = $data->pi_number;
        $invNumber = $data->inv_number;
        $note = $data->note;

        $rowAffected=DB::table('ap_pro_invoice')
        ->where('pi_number',$piNumber)
        ->update(
            [   
                'pi_number' => $piNumber."(C)",
                'status' => $status,
                'note' => $note." (Cancel)",
                'updated_by' => Auth::user()->username,
                'updated_at' => date('Y-m-d H:i:s')
            ]
        );

        if ($rowAffected){
            DB::commit();
            $title ='Cancel Proforma invoice';
            $alert  ="success";
            $message  = "$piNumber is successfully cancel";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(array('status' => 1, 'message' => $message,'alert'=>$alert,'piNumber'=>$piNumber));

        }else{
            DB::rollBack();
            $title ='Cancel Proforma invoice';
            $alert  ="warning";
            $message  = "$piNumber is failed to cancel";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->back()->with(array('status' => 0, 'message' => $message,'alert'=>$alert,'piNumber'=>$piNumber));

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

        $searchPo = strtolower($request->searchPo);
        $searchInv = strtolower($request->searchInv);
        $searchSupplier = $request->searchSupplier;
        $searchStatus = $request->searchStatus;
        $invDate = $request->invDate;
       

        $filter='';
        
        // $filter.="status <> 6 ";
        
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

        if ($invDate  != '' ){
            $date = explode("to",$invDate);
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
        from ap_pro_invoice a where $filter status != '6' ");

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            if (($data->status != '3') && ($data->status != '4')  && ($data->status != '5') ){
                if (Auth::user()->can('ap-edit')) {
                $buttons .=         '<a href="'. route('apProforma.edit',['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="file-text"></i>
                                        Edit
                                    </a>';
                }
            }

            $buttons .=         '<a href="'. route('apProforma.show', ['piNumber'=>Crypt::encryptString($data->pi_number)]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    Detail
                                </a>';
                                
            if ( $data->status == '3' ){
                if (Auth::user()->can('ap-revision')) {
                    $buttons .= '<a href="'. route('apProforma.revision', ['id'=>$data->id,'piNumber'=>$data->pi_number,'numRevision'=>$data->num_revision]) .'" class="dropdown-item">
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
                                        data-href='". route("apProforma.destroy", ["id"=>Crypt::encryptString($data->id)]) ."'>
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
    
}
