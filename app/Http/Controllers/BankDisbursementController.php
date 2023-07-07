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

class BankDisbursementController extends Controller
{
    public function index(Request $request)
    {
        $data['title'] = "List Bank Disbursement";
        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

        // status
        // 1. Draft
        // 2. Updated
        // 3. Posted
        // 4. Canceled

        $data['status'] = ['1'=>'Draft','2'=>'Updated','3'=>'Posted','4'=>'Canceled'];
            
        return view("bankDisbursement.index",$data);
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
        $newNumber="$key-DISB/$year/$month/$newCode";
        
        return $newNumber;
    }

    public function listInvoice(Request $request)
    {   
        $invDate = $request->invDate;
        $dueDate = $request->dueDate;
        $supplier = $request->supplier;
        $invFromDate = "";
        $invToDate = "";
        $dueFromDate = "";
        $dueToDate = "";

        // status
        // 1. Draft
        // 2. Update
        // 3. Posting
        // 4. Cancel
        // 5. Paid

        $output="";

        if ($invDate){
            $date = explode("to",$invDate);
            $invFromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
            $invToDate = implode("/", array_reverse(explode("-", trim($date[1]))));
        }
        
        if ($dueDate){
            $date = explode("to",$dueDate);
            $dueFromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
            $dueToDate = implode("/", array_reverse(explode("-", trim($date[1]))));
        }  

        $data1=DB::table('ap_invoice')
        ->leftJoin('third_party','third_party.kode','ap_invoice.supplier_id')
        ->select('ap_number',
                 'inv_number',
                 'inv_date',
                 'rec_date',
                 'due_date',
                 'basis_amount',
                 'vat',
                 'pph23',
                 'other_deduction',
                  DB::raw('(basis_amount + vat)- (pph23+other_deduction) as total'),
                  DB::raw("concat(third_party.kode,'-',third_party.nama) as supplier"))
        ->where(function ($query) use ($supplier,$invFromDate,$invToDate,$dueFromDate,$dueToDate) {
            $supplier ? $query->where('supplier_id',$supplier) : '';
            $invFromDate ? $query->whereBetween(DB::raw("to_date(inv_date,'DD-MM-YYYY')"), [$invFromDate, $invToDate]) : '';
            $dueFromDate ? $query->whereBetween(DB::raw("to_date(due_date,'DD-MM-YYYY')"), [$dueFromDate, $dueToDate]) : '';
        })
        ->where('status','4');

        $data2=DB::table('ap_pro_invoice')
        ->leftJoin('third_party','third_party.kode','ap_pro_invoice.supplier_id')
        ->select('pi_number',
                 'inv_number',
                 'inv_date',
                 'rec_date',
                 'due_date',
                 'basis_amount',
                 'vat',
                 'pph23',
                 'other_deduction',
                  DB::raw('(basis_amount + vat)- (pph23+other_deduction) as total'),
                  DB::raw("concat(third_party.kode,'-',third_party.nama) as supplier"))
        ->where(function ($query) use ($supplier,$invFromDate,$invToDate,$dueFromDate,$dueToDate) {
            $supplier ? $query->where('supplier_id',$supplier) : '';
            $invFromDate ? $query->whereBetween(DB::raw("to_date(inv_date,'DD-MM-YYYY')"), [$invFromDate, $invToDate]) : '';
            $dueFromDate ? $query->whereBetween(DB::raw("to_date(due_date,'DD-MM-YYYY')"), [$dueFromDate, $dueToDate]) : '';
        })
        ->where('status','3');
        
        
        $data = $data1->union($data2)->get();

        return Datatables::of($data)
        ->addColumn('select_orders', static function ($result) {
            return '<input type="checkbox" class="select-checkbox" name="apCheck[]" value="'.$result->ap_number.'"/>';
        })
        ->rawColumns(['select_orders'])
        ->make(true);
    }

    public function listSelected(Request $request)
    {   
        $apNumber = $request->apNumber;
        $apNumber = substr($apNumber,0,-1);     
        $apNumber = explode(",",$apNumber);

        $data1=DB::table('ap_invoice')
        ->leftJoin('third_party','third_party.kode','ap_invoice.supplier_id')
        ->select('ap_number','inv_number','inv_date','rec_date','due_date','supplier_id','third_party.nama',DB::raw('(basis_amount + vat)- (pph23+other_deduction) as total'),'third_party.bank_type',DB::raw("'ap' as type"))
        ->whereIn('ap_number',$apNumber);


        $data2=DB::table('ap_pro_invoice')
        ->leftJoin('third_party','third_party.kode','ap_pro_invoice.supplier_id')
        ->select('pi_number','inv_number','inv_date','rec_date','due_date','supplier_id','third_party.nama',DB::raw('(basis_amount + vat)- (pph23+other_deduction) as total'),'third_party.bank_type',DB::raw("'pi' as type"))
        ->whereIn('pi_number',$apNumber);
        
        $data = $data1->union($data2)
        ->orderBy('supplier_id','asc')
        ->orderBy('ap_number','asc')
        ->get();

        return response()->json($data);

        // return Datatables::of($data)
        // ->make(true);
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
        $data['title'] = "Bank disbursement";
        $data['subtitle'] = "Bank disbursement";
        
        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

        $data['status'] = 'New';
        $data['accounts'] = DB::table('accounts')->get();

        return view("bankDisbursement.create",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $details = json_decode($request->details);
        // $detailAp = json_decode($request->detailAp);
        // $detailPi = json_decode($request->detailPi);
        $paymentDate = $request->paymentDate;
        $admin = $request->admin;
        $discount = $request->discount;
        $others = $request->others;
        $subTotal = $request->subTotal;
        $othersNote = "";
        $bankName = ""; //Bank nya asn
        $accountNumber = ""; //Account bank nya asn
        $status = '1';
        $note="";

        // status
        // 1. Saved
        // 2. Update
        // 3. approve
        // 4. Cancel
        
        // $messages = [
        //     'required' => 'The field is required.',
        //     'unique' => 'The code has already been taken', 
        //     'iunique' => "Invoice Number : $invoiceNumber on PO: $poNumber has already exist",
        // ];
        
        // Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) use ($poNumber) {
        //     $query = DB::table($parameters[0]);
        //     $column = $query->getGrammar()->wrap($parameters[1]);
        //     $column2 = $query->getGrammar()->wrap($parameters[2]);
        //     return !$query->whereRaw("lower({$column}) = lower(?)", [$value])
        //                   ->whereRaw("lower({$column2}) = lower(?)", [$poNumber])->count();
        // });

        // $rule = [
        //     // 'poNumberDet'  => 'required',
        //     // 'invoiceNumber'=>'required|iunique:ap_pro_invoice,inv_number,po_number',
        //     // 'doDate'  => 'required',
        // ];

        // $this->validate($request,$rule,$messages);

        $hasilUpdate = AppHelpers::resetCode('BANK');
        $disbursementNumber = $this->getLastCode('BANK');
        DB::beginTransaction();
        try {
                DB::table('bank_disbursement_hdr')->insert([
                    'disbursement_number' => $disbursementNumber,
                    'disbursement_date' => $paymentDate,
                    'total' => $subTotal,
                    'admin' => $admin,
                    'discount' => $discount,
                    'other_admin' => $others ,
                    'other_note' => $othersNote,
                    'bank_name' => $bankName,
                    'account_number' => $accountNumber,
                    'status' => $status,
                    'note' => $note,
                    'created_by' => Auth::user()->username,
                    'updated_by' => Auth::user()->username,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                $dataSet = [];
                foreach ($details as $val) {
                    if ($val->type =="ap" ){
                        $data1=DB::table('ap_invoice')
                        ->leftJoin('third_party','third_party.kode','ap_invoice.supplier_id')
                        ->select(DB::raw("'$disbursementNumber' as disbursement_number"),
                                'ap_number',
                                DB::raw("'$val->type' as ref_type"),
                                'inv_number',
                                'inv_date',
                                'rec_date',
                                'due_date',
                                'third_party.bank_type',
                                DB::raw('(basis_amount + vat)- (pph23+other_deduction) as total'))
                        ->where('ap_number',$val->ap_number)
                        ->where('inv_number',$val->inv_number)
                        ->get();

                        foreach($data1 as $data_1){
                            $dataSet[] = [
                                'disbursement_number' => $data_1->disbursement_number,
                                'ref_number' => $data_1->ap_number,
                                'ref_type' => $data_1->ref_type,
                                'inv_number' => $data_1->inv_number,
                                'inv_date' => $data_1->inv_date,
                                'rec_date' => $data_1->rec_date,
                                'due_date' => $data_1->due_date,
                                'bank_type' => $data_1->bank_type,
                                'total' => $data_1->total,
                                'created_by' => Auth::user()->username,
                                'updated_by' => Auth::user()->username,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s')
                            ];
                        }
                    }

                    if ($val->type =="pi" ){
                        $data2=DB::table('ap_pro_invoice')
                        ->leftJoin('third_party','third_party.kode','ap_pro_invoice.supplier_id')
                        ->select(DB::raw("'$disbursementNumber' as disbursement_number"),
                                'pi_number',
                                DB::raw("'$val->type' as ref_type"),
                                'inv_number',
                                'inv_date',
                                'rec_date',
                                'due_date',
                                'third_party.bank_type',
                                DB::raw('(basis_amount + vat)- (pph23+other_deduction) as total'))
                        ->where('pi_number',$val->ap_number)
                        ->where('inv_number',$val->inv_number)
                        ->get();

                        foreach($data2 as $data_2){
                            $dataSet[] = [
                                'disbursement_number' => $data_2->disbursement_number,
                                'ref_number' => $data_2->pi_number,
                                'ref_type' => $data_2->ref_type,
                                'inv_number' => $data_2->inv_number,
                                'inv_date' => $data_2->inv_date,
                                'rec_date' => $data_2->rec_date,
                                'due_date' => $data_2->due_date,
                                'bank_type' => $data_2->bank_type,
                                'total' => $data_2->total,
                                'created_by' => Auth::user()->username,
                                'updated_by' => Auth::user()->username,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s')
                            ];
                        }
                    } 
                }
        
                DB::table('bank_disbursement_det')->insert($dataSet);

                DB::commit();

                $title ='Save Bank Disbursement Invoice';
                $alert  ="success";
                $message  = "$title $disbursementNumber is successfully saved";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'disNumber'=>$disbursementNumber));

        } catch (Exception $e) {
            DB::rollBack();
            $title ='Save Bank Disbursement Invoice';
            $alert  ="warning";
            $message  = "*Invoice $disbursementNumber is failed to save";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'disNumber'=>$disbursementNumber));
        }
        
    }

    public function show(Request $request)
    {
        $piNumber=Crypt::decryptString($request->piNumber);
        $data['title'] = "Detail Bank Disbursement";
        $data['subtitle'] = "Detail Bank Disbursement";

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
        $data['title'] = "Edit Bank Disbursement";
        $data['subtitle'] = "Edit Bank Disbursement";

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

    public function approve(Request $request)
    {
        // status
        // 1. Draft
        // 2. Updated
        // 3. Approved
        // 4. Canceled

        $username =  Auth::user()->username;
        $paymentCode = $request->paymentCode;
        $status = '3';
        $statusAp = "Approved";
        
        $rowAffected = DB::table('bank_disbursement_hdr')
            ->where('disbursement_number',$paymentCode)
            ->update(
                [   
                    'status' => $status,
                    'approved_by' => Auth::user()->username,
                    'approved_at' => date('Y-m-d H:i:s'),
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );

        if ($rowAffected){

            $disbs = DB::table('bank_disbursement_det')
                ->where('disbursement_number',$paymentCode)
                ->get();

            foreach($disbs as $disb){
                if ($disb->ref_type == 'ap'){
                    DB::table('ap_invoice')
                    ->where('ap_number',$disb->ref_number)
                    ->where('inv_number',$disb->inv_number)
                    ->update(
                        [   
                            'status' => '5',
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );
                }

                if ($disb->ref_type == 'pi'){
                    DB::table('ap_pro_invoice')
                    ->where('pi_number',$disb->ref_number)
                    ->where('inv_number',$disb->inv_number)
                    ->update(
                        [   
                            'status' => '5',
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );
                }
                
            }

            DB::commit();
            $title ='Posting bank disbursement';
            $alert  ="success";
            $message  = "$title $paymentCode is successfully updated";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('status' => 1, 'message' => $message,'alert'=>$alert,'disNumber'=>$paymentCode,'statusAp'=>$statusAp));
        }else{
            DB::rollBack();
            $title ='Posting bank disbursement';
            $alert  ="warning";
            $message  = "$title $paymentCode is failed to updated";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('status' => 0, 'message' => $message,'alert'=>$alert,'disNumber'=>$paymentCode));            
        }
    }

    public function revision(Request $request){
        $username =  Auth::user()->username;
        $id=$request->id;
        $piOrigin = $request->piNumber;
        $numRevision = $request->numRevision ? $request->numRevision +1 : 1 ;
        $piNew = $piOrigin.'-R'.$numRevision;

        $data['title'] = "Edit Bank Disbursement";
        $data['subtitle'] = "Edit Bank Disbursement";
        
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
        
        $searchCode=strtolower($request->searchCode);
        $searchDate=$request->searchDate;
        $searchStatus=$request->searchStatus;
       
        $filter='';
        
        // $filter.="status <> 6 ";
        
        if ($searchCode !='' ){
            $filter.="lower(disbursement_number) like '%$searchCode%' and ";
        }

        if ($searchStatus  != '' ){
            $filter.="status = '$searchStatus' and ";            
        }

        if ($searchDate  != '' ){
            $date = explode("to",$searchDate);
            $date1=trim($date[0]);
            $date2=trim($date[1]);
            $filter.= "to_date(disbursement_date, 'DD/MM/YYYY') BETWEEN to_date('$date1', 'DD/MM/YYYY') and to_date('$date2', 'DD/MM/YYYY') and ";
        }
        
        if ($filter !=''){
            $filter=" where ".substr($filter,0,-4);
        }

        $data = DB::select("SELECT *,total-(admin+discount+other_admin) as grand_total
        from bank_disbursement_hdr $filter");

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            if (($data->status != '3') && ($data->status != '4')){
                if (Auth::user()->can('ap-edit')) {
                $buttons .=         '<a href="'. route('disbursement.edit',['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="file-text"></i>
                                        Edit
                                    </a>';
                }
            }

            $buttons .=         '<a href="'. route('disbursement.show', ['piNumber'=>Crypt::encryptString($data->disbursement_number)]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    Detail
                                </a>';
                
            if (($data->status != '3' && $data->status != '4' )){
                if (Auth::user()->can('receiving-delete')) {
                $buttons .=         "<a href='javascript:;'
                                        id='deleteButton'
                                        class='dropdown-item'
                                        data-toggle='modal'
                                        data-target='#smallModalCancel'
                                        data-href='". route("disbursement.destroy", ["id"=>Crypt::encryptString($data->id)]) ."'>
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
            $statusRec = ['Draft','Updated','Approved','Canceled'];
            return $statusRec[$data->status - 1];
        })
        ->rawColumns(['action','status'])
        ->make(true);
    }
    
}
