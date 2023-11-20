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

class InvoiceController extends Controller
{

    private $title;
    private $moduleCode;
    private $nilaiPpn;
    private $nilaiPph23;

    public function __construct()
    {
        $this->title = "Invoice";
        $this->moduleCode = "INV";

        $this->nilaiPpn = DB::table('attributes')
        ->where('attr_id','mainppn')
        ->value('attr_value');

        $this->nilaiPph23 = DB::table('attributes')
        ->where('attr_id','mainpph23')
        ->value('attr_value');
    }

    public function getTableColoumn()
    {
        $kolom=
        [
            ['data'=> 'action', 'name'=> 'action','title'=>'action', 'orderable'=> false, 'searchable'=> false ],
            ['data'=> 'invoice_number', 'name'=> 'invoice_number','title'=>'Inv. Number' ],
            ['data'=> 'status', 'name'=> 'status','title'=>'Status' ],
            ['data'=> 'invoice_date', 'name'=> 'invoice_date','title'=>'Date' ],
            ['data'=> 'so_number', 'name'=> 'so_number','title'=>'SO Number' ],
            ['data'=> 'po_number', 'name'=> 'po_number','title'=>'PO Number' ],
            ['data'=> 'customer_name', 'name'=> 'customer_name','title'=>'Customer' ],
            ['data'=> 'dpp', 'name'=> 'dpp','title'=>'DPP' ],
            ['data'=> 'total_ppn', 'name'=> 'total_ppn','title'=>'PPN' ],
            ['data'=> 'total_pph', 'name'=> 'total_pph','title'=>'PPH' ],
            ['data'=> 'grand_total', 'name'=> 'grand_total','title'=>'Total' ],
            ['data'=> 'approval_by', 'name'=> 'approval_by','title'=>'Approved By' ],
            ['data'=> 'approval_at', 'name'=> 'approval_at','title'=>'Approved At' ],
            ['data'=> 'created_by', 'name'=> 'created_by','title'=>'Created By' ],
            ['data'=> 'created_at', 'name'=> 'created_at','title'=>'Created At' ],
            ['data'=> 'dn_number', 'name'=> 'dn_number','title'=>'DN Number' ],
            // ['data'=> 'updated_by', 'name'=> 'updated_by','title'=>'Updated By'],
            // ['data'=> 'updated_at', 'name'=> 'updated_at','title'=>'Updated At']
        ];
        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $data['title'] = $this->title;

        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        $data['kolom'] = $this->getTableColoumn();

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','6'=>'PAID','7'=>'REVISED'];
        $data['status'] = ['1'=>'DRAFT','2'=>'VALIDATE','3'=>'APPROVED','6'=>'PAID'];
                    
        return view("invoice.index",$data);
    }

    public function getLastCode($key,$period)
    {
        /*
            31 Oktober 2023
            Untuk angka romawi berdasarkan period
        */
        
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
        $months = ['I', 'II', 'III','IV','V', 'VI', 'VII', 'VIII','IX','X','XI','XII'];
        // $month = $months[date('n')-1];
        $month = $months[$period-1];
        $year = date('y');
        // INV-ASN-23-X-0001
        $code="$key-ASN-$year-$month-$newCode";
        // $code="$key/ASN/$year/$month/$newCode";
        
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

        $data['nilaiPPN'] = $this->nilaiPpn;
        $data['nilaiPPH'] = $this->nilaiPph23;

        $data['status']='NEW';

        return view("invoice.create",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request -> articles);
        $invDate = $request->invDate;
        $customer = $request->customer;
        $ppn = $request->ppn;
        $pph23 = $request->pph23;
        $totalPpn = $request->totalPpn;
        $totalPph = $request->totalPph;
        $soNumber = $request->soNumber;
        $dnNumber  = $request->dnNumber;
        // $poNumber  = $request->poNumber;
        $note = $request->note;
        $status = '1';
        $gudang = 'false';
        $kurs = 1;
        $fakturPajak  = $request->fakturPajak;
        $dpp = $request->totalAmount;
        $grandTotal = $request->grandTotal;
        $period = (int)explode('-', $invDate)[1];
        $periodNomor = (int)explode('-', $invDate)[1];

        $accountPenjualan = DB::table('third_party')->where('kode',$customer)->value('coa_penjualan');
        $accountPiutang = DB::table('third_party')->where('kode',$customer)->value('account');

       // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','6'=>'PAID','7'=>'REVISED'];

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
            // // 'orderNumber' => 'required',
            // 'orderDate'  => 'required',
            // 'currency'  => 'required',
            // 'type'  => 'required',
            'customer'  => 'required',
        ]);
        
        $error_array = array();
        $success_output = '';
        // return $validation;
        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }
            $title="Save Invoice";
            $alert ="error";
            return response()->json(array('status' => 0,'title' => $title, 'message' => $error_array,'alert' =>$alert));
        }else{
            $hasilUpdate = AppHelpers::resetCode($this->moduleCode);
            $invCode = $this->getLastCode($this->moduleCode,$periodNomor);
            DB::beginTransaction();
            try {
                DB::table('invoice_hdr')->insert([
                    'invoice_number' => $invCode,
                    'invoice_date' => $invDate,
                    'customer_id' => $customer,
                    'so_number' => $soNumber,
                    // 'po_number' => $poNumber,
                    'dn_number' => $dnNumber,
                    'dpp' => $dpp,
                    'other_admin' => 0 ,
                    'discount' => 0,
                    'ppn' => $ppn,
                    'pph23' => $pph23,
                    'npwp' => "",
                    'payment_term' => 0 ,
                    'payment_terms' => '',
                    'account_number' => '',
                    'status' => $status,
                    'note' =>  $note,
                    'created_by' => Auth::user()->username,
                    'updated_by' => Auth::user()->username,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'faktur_pajak' =>$fakturPajak,
                    'grand_total' =>$grandTotal,
                    'total_ppn' =>$totalPpn,
                    'total_pph' =>$totalPph,
                    'period'=> $period,
                    'account_piutang' =>$accountPiutang,
                    'account_penjualan' =>$accountPenjualan
                ]);

                $dataSet = [];
                foreach ($articles as $val) {
                    $dataSet[] = [
                        'invoice_number' => $invCode,
                        'article_code' => $val->article_code,
                        'so_number' => $val->so_number,
                        'po_number' => $val->po_number,
                        'dn_number' => $val->dn_number,
                        'qty' => $val->qty,
                        'uom' => $val->uom,
                        'price' => $val->price,
                        'price_service' => $val->price_service,
                        'ppn' => ($val->price*$val->qty) * $ppn/100,
                        'pph23' => ($val->price_service*$val->qty) * $pph23/100,
                        'created_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                }

                DB::table('invoice_det')->insert($dataSet);
                $this->prosesPosting($invCode);

                DB::commit();
                $title ='Save Invoice';
                $alert  ="success";
                $message  = "$title $invCode is successfully saved";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'invNumber'=>$invCode));

            } catch (Exception $e) {
                DB::rollBack();
                $title ='Save Invoice';
                $alert  ="warning";
                $message  = "$title $invCode is failed to save";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 0,'title' => $title, 'message' => $message,'alert'=>$alert,'invNumber'=>$invCode));
            }
        }
    }

    public function show(Request $request)
    {
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['header'] = DB::table('invoice_hdr')
        ->where('id',$id)
        ->get()->first();

        $invoiceNumber = $data['header']->invoice_number;

        /*
        detail 
        $data['detail'] = DB::table('invoice_det')
        ->leftJoin('article','article.article_code','=','invoice_det.article_code')
        ->leftJoin('uom','uom.code','invoice_det.uom')
        ->select('invoice_det.*','article_alternative_code as article','article_desc as desc')
        ->where('invoice_det.invoice_number',$invoiceNumber)
        ->orderBy('invoice_det.id')
        ->get();
        */

        /*summary*/

        $data['detail']=DB::table('invoice_det')
        ->leftJoin('article','article.article_code','invoice_det.article_code')
        ->select('article.article_alternative_code as article'
        ,'article.article_desc as desc'
        ,'invoice_det.uom as uom'
        ,db::raw('sum(qty) as qty')
        ,'price'
        ,'price_service')
        ->where('invoice_number',$invoiceNumber)
        ->groupBy(['article.article_code'
        ,'article.article_desc'
        ,'article.article_alternative_code'
        ,'invoice_det.uom'
        // ,'qty'
        ,'price'
        ,'price_service'])
        ->orderBy('article.article_code')
        ->get();

        

        $data['delivery'] = DB::table('delivery_hdr')
        ->whereIn('delivery_hdr.delivery_number', function($query) use ($invoiceNumber) {
            $query->select('dn_number')
            ->from('invoice_det') 
            ->where('invoice_number',$invoiceNumber);
        })
        ->select('delivery_hdr.delivery_number'
        ,'delivery_hdr.delivery_date'
        ,DB::raw("(select po_number from sales_order_hdr where so_code = delivery_hdr.so_number) as po_number")
        )
        ->get();

        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$invoiceNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$invoiceNumber,$username);

        // $data['status'] = ['1'=>'DRAFT','2'=>'VALIDATE','3'=>'APPROVED','6'=>'PAID','7'=>'REVISED'];
        $statusInv = ['DRAFT','VALIDATE','APPROVED','','','PAID','REVISED'];
        $data['statusInv'] = $statusInv[$data['header']->status-1];

        $data['nilaiPPN'] = $this->nilaiPpn;
        $data['nilaiPPH'] = $this->nilaiPph23;

        return view("invoice.show",$data);
        
    }

    public function edit(Request $request)
    {
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['id']=$id;

        $data['header'] = DB::table('invoice_hdr')
        ->where('id',$id)
        ->get()->first();

        $invoiceNumber = $data['header']->invoice_number;
        $data['soNumber'] = $data['header']->so_number;

        $data['detail'] = DB::table('invoice_det')
        ->leftJoin('article','article.article_code','=','invoice_det.article_code')
        ->leftJoin('uom','uom.code','invoice_det.uom')
        ->where('invoice_det.invoice_number',$invoiceNumber)
        ->orderBy('invoice_det.id')
        ->get();

        $data['summary']=DB::table('invoice_det')
        ->leftJoin('article','article.article_code','invoice_det.article_code')
        ->select('article.article_alternative_code'
        ,'article.article_desc'
        ,'invoice_det.uom as uom'
        ,db::raw('sum(qty) as qty')
        ,'price'
        ,'price_service')
        ->where('invoice_number',$invoiceNumber)
        ->groupBy(['article.article_desc'
        ,'article.article_code'
        ,'article.article_alternative_code'
        ,'invoice_det.uom'
        // ,'qty'
        ,'price'
        ,'price_service'])
        ->orderBy('article.article_code')
        ->get();

        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$invoiceNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$invoiceNumber,$username);

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','6'=>'PAID','7'=>'REVISED'];
        $status = ['DRAFT','VALIDATE','APPROVED','','','PAID','REVISED'];
        $data['status'] = $status[$data['header']->status-1];

        $data['nilaiPPN'] = $this->nilaiPpn;
        $data['nilaiPPH'] = $this->nilaiPph23;

        return view("invoice.edit",$data);
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request -> articles);
        $invNumber = $request->invNumber;
        $invDate = $request->invDate;
        $customer = $request->customer;
        $ppn = $request->ppn;
        $pph23 = $request->pph23;
        $totalPpn = $request->totalPpn;
        $totalPph = $request->totalPph;
        $soNumber = $request->soNumber;
        $dnNumber  = $request->dnNumber;
        // $poNumber  = $request->poNumber;
        $note = $request->note;
        $status = '1';
        $gudang = 'false';
        $kurs = 1;
        $fakturPajak  = $request->fakturPajak;
        $dpp = $request->totalAmount;
        $grandTotal = $request->grandTotal;

        $period = (int)explode('-', $invDate)[1];

        $accountPenjualan = DB::table('third_party')->where('kode',$customer)->value('coa_penjualan');
        $accountPiutang = DB::table('third_party')->where('kode',$customer)->value('account');

        // $data['status'] = ['1'=>'DRAFT','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'CLOSED','6'=>'PAID'];

        $customMessages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
            // 'iunique' => "Invoice : $invNumber has already been taken on PO : $poNumber",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            $column2 = $query->getGrammar()->wrap($parameters[2]);
            // return !$query->whereRaw("lower({$column}) = lower(?)", [$value])
            //               ->whereRaw("lower({$column2}) = lower(?)", [$poNumber])->count();
        });
        
        $validation = Validator::make($request->all(),$messages = [
            // 'invNumber'=>'required|iunique:receiving_hdr,inv_number,po_number',
            // 'recDate'  => 'required',
            // 'poNumber'  => 'required',
            'customer'  => 'required'
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
                   
                $row_affected=DB::table('invoice_hdr')
                    ->where('invoice_number',$invNumber)
                    ->update(
                        [   
                            'invoice_date' => $invDate,
                            'customer_id' => $customer,
                            'so_number' => $soNumber,
                            // 'po_number' => $poNumber,
                            'dn_number' => $dnNumber,
                            'dpp' => $dpp,
                            'other_admin' => 0 ,
                            'discount' => 0,
                            'ppn' => $ppn,
                            'pph23' => $pph23,
                            'npwp' => "",
                            'payment_term' => 0 ,
                            'payment_terms' => '',
                            'account_number' => '',
                            'note' =>  $note,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s'),
                            'faktur_pajak' =>$fakturPajak,
                            'grand_total' =>$grandTotal,
                            'total_ppn' =>$totalPpn,
                            'total_pph' =>$totalPph,
                            'period' =>$period,
                            'account_piutang' =>$accountPiutang,
                            'account_penjualan' =>$accountPenjualan
                        ]
                    );

                    $dataset=[];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            $invNumber.$val->po_number.$val->dn_number.$val->article_code
                        ];
                    }

                    //berdasarkan 3 kondisi
                    DB::table('invoice_det')
                        ->whereNotIn(DB::raw("CONCAT(invoice_number,po_number,dn_number,article_code)"),$dataSet)
                        ->where('invoice_number',$invNumber)
                        ->delete();
                                  
                    foreach ($articles as $val) {
                        DB::table('invoice_det')
                        ->updateOrInsert(
                            ['invoice_number' => $invNumber
                                ,'article_code' => $val->article_code
                                ,'po_number' => $val->po_number
                                ,'dn_number' => $val->dn_number
                            ],
                            [
                                'invoice_number' => $invNumber,
                                'article_code' => $val->article_code,
                                'so_number' => $val->so_number,
                                'po_number' => $val->po_number,
                                'dn_number' => $val->dn_number,
                                'qty' => $val->qty,
                                'uom' => $val->uom,
                                'price' => $val->price,
                                'price_service' => $val->price_service,
                                'ppn' => ($val->price*$val->qty) * $ppn/100,
                                'pph23' => ($val->price_service*$val->qty) * $pph23/100,
                                'updated_by' => Auth::user()->username,
                                'updated_at' => date('Y-m-d H:i:s')
                            ]
                        );
                    }

                    $this->prosesUpdatePosting($invNumber);
                                                                
                    DB::commit();
                    $title ="Update $this->title";
                    $alert  ="success";
                    $message  = "$title $invNumber is successfully updated";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'invNumber'=>$invNumber));

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Update $this->title";
                $alert ="warning";
                $message  = "$title $invNumber is failed to update";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 0,'title' => $title, 'message' => $message,'alert'=>$alert,'invNumber'=>$invNumber));
            }
        }

    }

    public function posting(Request $request)
    {
        // status
        // 1. Draft
        // 2. Update
        // 3. Posting
        // 4. Cancel

        // $username =  Auth::user()->username;
        // $recNumber = $request->recNumber;
        // $recType = "NORMAL";
        // $statusRec ="Posting";
        // $status = '3';
        // $authorizedBy = Auth::user()->username;
        // $todayDate = date("d-m-Y");

        // // Update stock kalo article nya udah ada
        // $sqlUpdate = "UPDATE article_stock a set article_qty = COALESCE(a.article_qty,0)  + COALESCE(b.qty,0)
        // from (
        // select art_code, (qty*factor_qty)+(qty_free*factor_free) as qty from 
        // (
        //     select *,article.article_code as art_code,(select unit_factor from uom_con where unit_from = o.uom_rec and unit_to = article.uom) as factor_qty,(select unit_factor from uom_con where unit_from = o.uom_free and unit_to = article.uom) as factor_free  from (
        //     select * from receiving_det where rec_number in (
        //     select rec_number from receiving_hdr where rec_number = '$recNumber' and (status != '3' and status != '4'))) o
        //     left join article on article.article_code = o.article_code
        // ) c
        // ) b
        // where a.article_code=b.art_code";

        // //Insert ke stock kalo article nya belum ada
        // $sqlInsert = "INSERT into article_stock (site_code,article_code,dept_code,location_number,article_qty,uom)
        // select 'HO',art_code,article_type,'00',(qty*factor_qty)+(qty_free*factor_free) as qty,uom from 
        // (
        //     select *,article.article_code as art_code,(select unit_factor from uom_con where unit_from = z.uom_rec and unit_to = article.uom) as factor_qty,(select unit_factor from uom_con where unit_from = z.uom_free and unit_to = article.uom) as factor_free  from (
        //     select * from receiving_det where rec_number in (
        //     select rec_number from receiving_hdr where rec_number = '$recNumber' and (status != '3' and status != '4'))) z
        //     left join article on article.article_code = z.article_code
        //     where article.article_code not in (select article_code from article_stock)
        // ) y";

        // //Insert into table movement
        // $sqlMovement = "INSERT into movement
        // (movement_date,artikel_code,artikel_desc,movement_min,movement_plus,movement_price,movement_transnno,movement_type,movement_desc)
        // select 
        // '$todayDate',
        // article_code,
        // (select concat(article_alternative_code,'-',article_desc) from article where article_code = a.article_code) as article_desc,
        // 0,
        // qty,
        // price,
        // rec_number,
        // 'REC',
        // (select po_number from receiving_hdr where rec_number=a.rec_number) as po from receiving_det a where rec_number in (
        // select rec_number from receiving_hdr where rec_number = '$recNumber' and status = '3' and qty <> 0)";
    
        // DB::select($sqlUpdate);
        // $rowAffected = DB::select($sqlInsert);
        
        // if ($rowAffected > 0){
        //     DB::table('receiving_hdr')
        //     ->where('rec_number',$recNumber)
        //     ->update(
        //         [   
        //             'status' => $status,
        //             'authorized_by' => $authorizedBy,
        //             'authorized_at' => date('Y-m-d H:i:s'),
        //             'updated_by' => Auth::user()->username,
        //             'updated_at' => date('Y-m-d H:i:s')
        //         ]
        //     );

        //     DB::select($sqlMovement);

        //     DB::commit();
        //     $alert  ="alert-success";
        //     $message  = "Posting Rec $recNumber Successfully Posting";
        //     \LogActivity::addToLog('Posting Rec ',"username: $username Status $message");
        //     return response()->json(array('statusRec' => $statusRec,'status' => 1, 'message' => $message,'alert'=>$alert,'recNumber'=>$recNumber));
        // }else{
        //     $alert  ="alert-warning";
        //     $message  = "Posting Rec $recNumber Failed to Posting";
        //     \LogActivity::addToLog('Posting Rec ',"username: $username Status $message");
        //     return response()->json(array('statusRec' => $statusRec,'status' => 1, 'message' => $message,'alert'=>$alert,'recNumber'=>$recNumber));
        // }
    }

    public function destroy(Request $request)
    {
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','6'=>'PAID','7'=>'REVISED'];
        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);
        
        $data= DB::table('invoice_hdr')
        ->where('id',$id)
        ->first();

        $invNumber = $data->invoice_number;
        
        $rowAffected = DB::table('invoice_hdr')
        ->where('invoice_number',$invNumber)
        ->delete();
                     
        // $status = "5";

        // $invHdr= DB::table('invoice_hdr')
        // ->where('id',$id)
        // ->get()->first();

        // $invNumber = $invHdr->invoice_number;
        // $note = $invHdr->note;

        // $rowAffected=DB::table('invoice_hdr')
        // ->where('invoice_number',$invNumber)
        // ->update(
        //     [   
        //         'invoice_number' => $invNumber."(C)",
        //         'status' => $status,
        //         'note' => $note." (Cancel)",
        //         'updated_by' => Auth::user()->username,
        //         'updated_at' => date('Y-m-d H:i:s')
        //     ]
        // );

        if($rowAffected){

            DB::table('invoice_det')
            ->where('invoice_number',$invNumber)
            ->delete();

            $kasDet = DB::table('kas_det')
            ->where('reference',$invNumber)
            // ->whereIn('voucher_type',['KM','BM'])
            ->first();

            $voucherNumber=$kasDet->voucher_number;

            if($voucherNumber){
                DB::table('kas_hdr')
                ->where('voucher_number',$voucherNumber)
                ->delete();
    
                DB::table('kas_det')
                ->where('voucher_number',$voucherNumber)
                ->delete();
            }

            DB::table('approval_history')
            ->where('module_number',$invNumber)
            ->where('module_code',$this->moduleCode)
            ->delete();
            
            // DB::table('invoice_det')
            // ->where('invoice_number',$invNumber)
            // ->update(
            //     [   
            //         'invoice_number' => $invNumber."(C)",
            //         'updated_by' => Auth::user()->username,
            //         'updated_at' => date('Y-m-d H:i:s')
            //     ]
            // );

            $title ="Delete $this->title";
            $alert  ="success";
            $message  = "$title $invNumber Successfully Deleted";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'title' => $title,'message'=> $message]);   
        }else{
            $title ="Cancel $this->title";
            $alert  ="warning";
            $message  = "$title $invNumber Failed to Delete";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'title' => $title,'message'=> $message]);
        }
    }

    public function dnDetail(Request $request)
    {
        $so = $request->soNumber;
        $dn = $request->dnNumber;

        $arrayDnNumber = explode(",",$dn);
        $result = "'" . implode ( "', '", $arrayDnNumber ) . "'";

        $data['detail'] = DB::table('delivery_det')
        ->join('sales_order_det', function ($join) {
            $join->on('sales_order_det.so_code', '=', 'delivery_det.so_number')
                 ->on('sales_order_det.article_code', '=', 'delivery_det.article_code');
        })
        ->leftJoin('article','article.article_code','=','delivery_det.article_code')
        ->leftJoin('uom','delivery_det.uom','uom.code')
        // ->select('delivery_det.*','article.*','uom.uom_group','sales_order_det.*','delivery_det.qty as qty_dn')
        ->select('article.article_code'
        ,'article.article_alternative_code'
        ,'article.article_desc'
        ,'delivery_det.qty as qty_dn'
        ,'uom.uom_group'
        ,'delivery_det.uom'
        ,'sales_order_det.price'
        ,'sales_order_det.price_service'
        ,'delivery_det.so_number'
        ,'delivery_det.delivery_number'
        ,'delivery_det.po_number'
        )
        ->whereIn('delivery_det.delivery_number',$arrayDnNumber)
        ->where('delivery_det.so_number',$so)
        ->orderBy('article.article_code')
        ->get();


        

        /* summary */

        $data['summary'] = DB::table('delivery_det')
        ->join('sales_order_det', function ($join) {
            $join->on('sales_order_det.so_code', '=', 'delivery_det.so_number')
                 ->on('sales_order_det.article_code', '=', 'delivery_det.article_code');
        })
        ->leftJoin('article','article.article_code','=','delivery_det.article_code')
        ->leftJoin('uom','delivery_det.uom','uom.code')
        // ->select('delivery_det.*','article.*','uom.uom_group','sales_order_det.*','delivery_det.qty as qty_dn')
        ->select('article.article_code'
        ,'article.article_alternative_code'
        ,'article.article_desc'
        ,db::raw('sum(delivery_det.qty) as qty_dn')
        ,'uom.uom_group'
        ,'delivery_det.uom'
        ,'sales_order_det.price'
        ,'sales_order_det.price_service'
        ,'delivery_det.so_number'
        // ,'delivery_det.delivery_number'
        // ,'delivery_det.po_number'
        )
        ->whereIn('delivery_det.delivery_number',$arrayDnNumber)
        ->where('delivery_det.so_number',$so)
        ->groupBy(['article.article_code'
        ,'article.article_alternative_code'
        ,'article.article_desc'
        ,'uom.uom_group'
        ,'delivery_det.uom'
        ,'sales_order_det.price'
        ,'sales_order_det.price_service'
        ,'delivery_det.so_number'
        // ,'delivery_det.delivery_number'
        // ,'delivery_det.po_number'
        ]
        )
        ->orderBy('article.article_code')
        ->get();

        // $data = DB::select("SELECT 
        //     a.article_code,
        //     article_alternative_code,
        //     article_desc,
        //     a.qty,
        //     uom_group,
        //     a.uom,
        //     price,
        //     price_service,
        //     so_number,
        //     delivery_number
        //     from delivery_det a 
        //     left join sales_order_det b on b.so_code = a.so_number  and a.article_code = b.article_code
        //     left join uom on uom.code=a.uom
        //     left join article on article.article_code = a.article_code
        //     where 
        //     delivery_number = '$dn' 
        //     and so_number = '$so'");

        return response()->json($data);
    }

    public function list(Request $request)
    {
       
        $searchInv = strtolower($request->searchInv);
        $searchSo = strtolower($request->searchSo);
        $searchCustomer = $request->searchCustomer; 
        $searchStatus = $request->searchStatus;
        $invDate = $request->recDate;
        $fromDate = "";
        $toDate = "";

        if ($invDate){
            $date = explode("to",$invDate);
            if(count($date)>1){
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate; 
            }
        }

        $data = DB::table('invoice_hdr')
        ->leftJoin('third_party','third_party.kode','invoice_hdr.customer_id')
        ->where(function ($query) use ($searchInv,$searchSo,$searchCustomer,$searchStatus,$invDate,$fromDate,$toDate) {
            $searchInv ? $query->where('invoice_number','ilike','%'.$searchInv.'%') : '';
            $searchSo ? $query->where('so_number','ilike','%'.$searchSo.'%') : '';
            $searchCustomer ? $query->where('customer_id','ilike','%'.$searchCustomer.'%') : '';
            $searchStatus ? $query->where('invoice_hdr.status','=',$searchStatus) : '';
            $invDate ? $query->whereBetween(DB::raw("to_date(invoice_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
        })
        // ->where('invoice_hdr.status','<>','6')
        ->select(
            'invoice_hdr.*'
            // ,DB::raw("(select STRING_AGG ( a.rec_number,',' ORDER BY a.id) as list_rec from invoice_hdr_detail a where ap_number = invoice_hdr.ap_number) as list_rec")
            //,db::raw("concat(third_party.kode,'-',third_party.nama) as customer_name")
            // ,db::raw("(select STRING_AGG((select name from users where username = z.username), ' -> ' ORDER BY approval_order) AS main from approval_history z where module_number = invoice_hdr.invoice_number) as approval_by")
            ,'third_party.nama as customer_name'
            ,db::raw("(select (select name from users where username = z.username) from approval_history z where module_number = invoice_hdr.invoice_number order by approval_order desc limit 1) as approval_by")
            ,db::raw("(select to_char(approval_date::date, 'DD-MM-YYYY') from approval_history z where module_number = invoice_hdr.invoice_number order by approval_order desc limit 1) as approval_at")
            // ,DB::raw("(select STRING_AGG ( distinct a.po_number,', ' ORDER BY a.po_number) as po_number from invoice_det a where invoice_number = invoice_hdr.invoice_number) as po_number")
            ,DB::raw("(select STRING_AGG ( distinct (select po_number from sales_order_hdr where so_code = so_number),',') as po_number from invoice_det a where invoice_number = invoice_hdr.invoice_number) as po_number")
            ,DB::raw("(select STRING_AGG ( distinct a.dn_number,', ' ORDER BY a.dn_number) as dn_number from invoice_det a where invoice_number = invoice_hdr.invoice_number) as dn_number")
        )
        ->orderBy('invoice_hdr.id')
        ->get(); 
                
        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            

            if (($data->status != '3') && ($data->status != '4')){
                if (Auth::user()->can('receiving-edit')) {
                $buttons .=         '<a href="'. route('invoice.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="check"></i>
                                        Approve
                                    </a>';
                }
            }

            //sibuka sementara dari pak leo 6-11-2023
            // if (($data->status != '3') && ($data->status != '4')){
                if (Auth::user()->can('receiving-edit')) {
                    $buttons .=         '<a href="'. route('invoice.edit',  ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                            <i data-feather="file-text"></i>
                                            Edit
                                        </a>';
                    }
                // }

                $buttons .=      '<a href="'. route('invoice.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    Detail
                                </a>';

            // if ($data->status == '3'){
                $buttons .=         '<a href="'. route('invoice.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
                                        <i data-feather="printer"></i>
                                        Print
                                    </a>';

            // }
            
                
            if (($data->status != '3') && ($data->status != '4')){
                if (Auth::user()->can('receiving-delete')) {
                $buttons .=         "<a href='javascript:;'
                                        id='deleteButton'
                                        class='dropdown-item'
                                        data-toggle='modal'
                                        data-target='#smallModal'
                                        data-href='". route("invoice.destroy", ['id'=>Crypt::encryptString($data->id)]) ."'>
                                        <i data-feather='trash-2' class='feather-14-red'></i>
                                        Delete
                                    </a>";
                }
            }
            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })

        // ->addColumn('invoice_number', function ($data) {
        //     $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger'];            
            
        //     return '<span style="display: none;">'.$data->invoice_number.'</span><a class="text-left badge d-block '.$badges[$data->status - 1].'" name="'.$data->invoice_number.'" href="'. route('invoice.show', ['id'=>Crypt::encryptString($data->id)]) .'" ><span>'.$data->invoice_number.'</span></a>';
        // })
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger'];            
            // $data['status'] = ['1'=>'DRAFT','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'PAID'];
            $statusInv = ['DRAFT','VALIDATE','APPROVED','POSTED','DELETED','PAID'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusInv[$data->status - 1]."</div>";
        })
        ->rawColumns(['action','status','invoice_number'])
        ->make(true);
    }

    public function print(Request $request)
    {
        $id=Crypt::decryptString($request->id);

        $data['companies']= array(
            "nama"=> "PT ABIMANYU SEKAR NUSANTARA",
            "alamat"=> "KP. KARANG MULYA RT 014 RW 005 DESA CIKOPO",
            "kota" => "KEC. BUNGURSARI KAB. PURWAKARTA JAWA BARAT",
            "tlp" =>  ""
        );
        
        // $data['suppliers']=array(
        //     'nama'=>'PT ABIMANYU SEKAR NUSANTARA',
        //     'alamat'=>'KP. KARANG MULYA RT 014 RW 005 DESA CIKOPO',
        //     'kota' =>'KEC. BUNGURSARI KAB. PURWAKARTA JAWA BARAT',
        //     'tlp' => ''
        // );
        
        $invHdr=DB::table('invoice_hdr')
        ->where('id',$id)
        ->first();

        $data['recHdr']=DB::table('invoice_hdr')
        ->where('id',$id)
        ->first();

        $invNumber=$invHdr -> invoice_number;

        $data['title']=$invNumber;
       
        $data['details']=DB::table('invoice_det')
        ->leftJoin('article','article.article_code','invoice_det.article_code')
        ->select('article.article_desc'
        ,db::raw('sum(qty) as qty')
        ,'price'
        ,'price_service')
        ->where('invoice_number',$invNumber)
        ->groupBy([
            'article.article_code'
            ,'article.article_desc'
            // ,'qty'
            ,'price'
            ,'price_service'
        ])
        ->orderBy('article.article_code')
        ->limit(25)
        ->get();

        $data['details2']=DB::table('invoice_det')
        ->leftJoin('article','article.article_code','invoice_det.article_code')
        ->select('article.article_desc'
        ,db::raw('sum(qty) as qty')
        ,'price'
        ,'price_service')
        ->where('invoice_number',$invNumber)
        ->groupBy([
            'article.article_code'
            ,'article.article_desc'
            // ,'qty'
            ,'price'
            ,'price_service'
        ])
        ->orderBy('article.article_code')
        ->offset(25)
        ->get();

        
        
        $header=DB::table('invoice_hdr')
        ->where('invoice_number',$invNumber)
        ->first();


        // $listpo=DB::select("SELECT string_agg(distinct po_number,',') as po_list from invoice_det where invoice_number = '$invNumber'");
        /*revisi PO diambil langsung dari data SO */

        $listpo=DB::select("SELECT string_agg(distinct (select po_number from sales_order_hdr where so_code = so_number),',') as po_list from invoice_det where invoice_number = '$invNumber'");

        $data['listpo'] = $listpo[0]->po_list;

        $data['totals']=DB::select("SELECT total_ppn as ppn,
        total_material,
        total_service,
        total_pph as pph23 
        ,(total_material+total_service) as sub_total
        ,((total_material+total_service+total_ppn)-total_pph) as grand_total 
        FROM 
        (SELECT
        invoice_number,
        sum(qty) as qty,
        sum(qty*price) as total_material,
        sum(qty*price_service) as total_service
        from invoice_det
        where invoice_number = '$invNumber'
        group by invoice_number) a
        left join invoice_hdr b
        on a.invoice_number = b.invoice_number
        ");

        $data['terbilang'] =  $this->terbilang($data['totals'][0]->grand_total);

        $data['customers']=DB::table('third_party')
        ->where('kode',$invHdr -> customer_id)
        ->first();
        
        $data['status'] ='1';
        $data['no'] = 0 ;

        $data['nilaiPPN'] = $this->nilaiPpn;
        $data['nilaiPPH'] = $this->nilaiPph23;
        // $data['totalPpn'] = $header->total_ppn;
        // $data['totalPph'] = $header->total_pph;

        $bulan = array(
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember',
        );

        /* Invoice date tadinya tanggal hari ini , sekarang pakai tanggal invoice date */
        $invoiceDate = $header->invoice_date;
        
        if ($invoiceDate){
            $invoiceDate= explode("-",$invoiceDate);
            $data['tanggalHariIni']= $invoiceDate[0].' '.($bulan[$invoiceDate[1]]).' '.$invoiceDate[2];
        }else{
            $data['tanggalHariIni']= date('d').' '.($bulan[date('m')]).' '.date('Y');    
        }

        // $data['tanggalHariIni']= date('d').' '.($bulan[date('m')]).' '.date('Y');

        // $data['tanggalHariIni']=date("d F Y");

        if ($data['totals'][0]->total_material > 0 and $data['totals'][0]->total_service > 0){
            $printType = '12';  
        }

        if ($data['totals'][0]->total_material > 0 and $data['totals'][0]->total_service == 0){
            $printType = '1';
        }

        if ($data['totals'][0]->total_material == 0 and $data['totals'][0]->total_service > 0){
            $printType = '2';
        }
        
        $data['printType'] = $printType;

        if ($printType == '12'){
            return view('invoice.print',$data);    
        }else{
            return view('invoice.printV2',$data);    
        }


        // return view('invoice.print',$data);
        // return view('invoice.printV2',$data);

        // view()->share($data);

        // $pdf = PDF::loadView('invoice.print');
        // return $pdf->stream("PO_$invNumber.pdf");

    }

    public function listDn(Request $request)
    {
        $soNumber= $request->soNumber;
        $invNumber= $request->invNumber;
        $output="";
        $edit = $request->edit;

        if($edit == 'true'){
            $data= DB::table("delivery_hdr") 
            ->leftJoin('dn_receipt','dn_receipt.delivery_number','delivery_hdr.delivery_number')
            ->where("so_number",$soNumber)
            ->where("delivery_hdr.status","<>","7")
            ->where('dn_receipt.status','2') //sudah di submitt di dn receipt
            // ->where("status","8") //sudah di received
            ->whereNotIn(DB::raw("delivery_hdr.delivery_number"), function($query) use ($invNumber) {
                $query->select('dn_number')
                ->from('invoice_det')
                ->where('invoice_number','<>',$invNumber);
            })
            ->orderBy("delivery_date")
            ->select("delivery_hdr.delivery_number","so_number","po_number","delivery_hdr.delivery_date")
            ->get();
        }else{
            $data= DB::table("delivery_hdr") 
            ->leftJoin('dn_receipt','dn_receipt.delivery_number','delivery_hdr.delivery_number')
            ->where("so_number",$soNumber)
            // ->where("delivery_hdr.status","<>","7")
            // ->where("status","4")
            ->where('dn_receipt.status','2') //sudah di submitt di dn receipt
            // ->where("status","8") //sudah di received
            ->whereNotIn(DB::raw("delivery_hdr.delivery_number"), function($query) {
                $query->select('dn_number')
                ->from('invoice_det');
            })
            ->orderBy("delivery_date")
            ->select("delivery_hdr.delivery_number","so_number","po_number","delivery_hdr.delivery_date")
            ->get();
        }

        if ($invNumber){
            $details = DB::table('invoice_det')->where('invoice_number',$invNumber)->pluck('dn_number');
            $arrayData=[];
            foreach($details as $val ){
                array_push($arrayData,$val);
            }
            $details = $arrayData;
            // dd($details);
        }else{
            $details=[];
        }
        
        $showDetail ='false';
        foreach ($data as $key=>$row){
            $checked = in_array($row->delivery_number, $details) ? 'checked' :'';           
            $output .="<tr>
                        <td>
                            <div class='custom-control custom-checkbox'>
                                <input type='checkbox' class='custom-control-input' id='customCheck$key' name='customCheck'
                                data-dn-date='$row->delivery_date' 
                                data-dn-number = '$row->delivery_number'
                                data-sum-qty = '$row->po_number' $checked>
                                <label class='custom-control-label' for='customCheck$key'></label>
                            </div>
                        </td>
                        <td>$row->delivery_number</td>
                        <td>$row->delivery_date</td>
                        <td>$row->po_number</td>
                    </tr>";
        }        
        
        return $output;
    }
    
    public function listSo(Request $request)
    {
        $cust= $request->value;      
        $output="";

        $data= DB::table("sales_order_hdr") 
        ->where("customer_id",$cust)
        ->where("status","3")
        ->whereIn('so_code', function($query) use ($cust) {
            $query->select('so_number')
            ->from('delivery_hdr') 
            ->where('customer_id',$cust)
            // ->where('status','4');
            ->where('status','8'); // sudah di invoice receive
        })
        ->orderBy("so_code")
        ->select("so_code","po_number")
        ->get();          

        $output .='<option value=""></option>';            
        foreach ($data as $row){
            $output .='<option value="'.$row->so_code.'" data-po-number="'.$row->po_number.'">'.$row->so_code. ' - ' .$row->po_number.'</option>';            
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

    public function approve(Request $request)
    {
        $username =  Auth::user()->username;
        $invNumber = $request->invNumber;
        $statusLevelApproval = Approval::approvalLevelPosition($this->moduleCode,$invNumber,$username);        
        $nextLevel = $statusLevelApproval[0]->next_level;
        $statusInv = $statusLevelApproval[0]->next_level == $statusLevelApproval[0]->max_level ? '3' :'2';
                
        DB::beginTransaction();
        try {
                $row_affected=DB::table('invoice_hdr')
                ->where('invoice_number',$invNumber)
                ->update(
                    [
                        'status' => $statusInv,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                if ($row_affected){
                    DB::table('approval_history')->insert([
                        'module_code' => $this->moduleCode,
                        'module_number' => $invNumber,
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

                $row_affected=DB::table('kas_hdr')
                ->where('voucher_number',$invNumber)
                ->update(
                    [
                        'status' => $statusInv,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                if($statusInv == '3'){
                    // $this->prosesPosting($invNumber);
                    //posting AP ke kas
                    DB::table('ap_invoice')
                    ->where('ap_number',$invNumber)
                    ->update(
                        [   
                            'status' => '4',
                            'authorized_by' => Auth::user()->username,
                            'authorized_at' => date('Y-m-d H:i:s'),
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );
                }
                
                DB::commit();
                $title ="Approve $this->title";
                $alert  ="success";
                $message  = "$title $invNumber is successfully Approve-".$nextLevel;
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('statusPo' => $statusInv,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'invNumber'=>$invNumber));

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Approve $this->title";
            $alert  ="warning";
            $message  = "$title $invNumber is failed to Approve-".$nextLevel;
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('statusPo' => $statusInv,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'invNumber'=>$invNumber));
        }
    }

    public function penyebut($nilai) 
    {
		$nilai = abs($nilai);
		$huruf = array("", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas");
		$temp = "";
		if ($nilai < 12) {
			$temp = " ". $huruf[$nilai];
		} else if ($nilai <20) {
			$temp = $this->penyebut($nilai - 10). " belas";
		} else if ($nilai < 100) {
			$temp = $this->penyebut($nilai/10)." puluh". $this->penyebut($nilai % 10);
		} else if ($nilai < 200) {
			$temp = " seratus" . $this->penyebut($nilai - 100);
		} else if ($nilai < 1000) {
			$temp = $this->penyebut($nilai/100) . " ratus" . $this->penyebut($nilai % 100);
		} else if ($nilai < 2000) {
			$temp = " seribu" . $this->penyebut($nilai - 1000);
		} else if ($nilai < 1000000) {
			$temp = $this->penyebut($nilai/1000) . " ribu" . $this->penyebut($nilai % 1000);
		} else if ($nilai < 1000000000) {
			$temp = $this->penyebut($nilai/1000000) . " juta" . $this->penyebut($nilai % 1000000);
		} else if ($nilai < 1000000000000) {
			$temp = $this->penyebut($nilai/1000000000) . " milyar" . $this->penyebut(fmod($nilai,1000000000));
		} else if ($nilai < 1000000000000000) {
			$temp = $this->penyebut($nilai/1000000000000) . " trilyun" . $this->penyebut(fmod($nilai,1000000000000));
		}     
		return $temp;
	}
 
	public function terbilang($nilai) 
    {
		if($nilai<0) {
			$hasil = "minus ". trim($this->penyebut($nilai)).' rupiah';
		} else {
			$hasil = trim($this->penyebut($nilai)).' rupiah';
		}     		
		return ucfirst($hasil);
	}

    public function prosesPosting($invNumber){
        /* Proses posting ke kas*/

        $pphDibayarDimuka = '1100.75';
        $ppnKeluaranCustomer = '2000.14.1';
        $costCenter = '007';

        $invData = db::table('invoice_hdr')
        ->leftJoin('third_party', 'third_party.kode', '=', 'invoice_hdr.customer_id')
        ->select('invoice_hdr.*','third_party.nama as customer_name')
        ->where('invoice_number',$invNumber)->first();

        $periodYear = (int)explode('-', $invData->invoice_date)[2];
        $invStatus = $invData->status;

        DB::table('kas_hdr')->insert([
            'voucher_number' =>$invNumber,
            'voucher_type' =>$this->moduleCode,
            // 'voucher_date' =>date('d-m-Y'), //tanggal posting
            'voucher_date' =>$invData->invoice_date, //invoice date
            'paid_to' => $invData->customer_id,
            'description' => $invNumber,
            'amount' => $invData->grand_total,
            'period' => $invData->period,
            'year' => $periodYear,                        
            'note' => $invData->note,
            'status' => $invStatus,
            'created_by' => Auth::user()->username,
            'updated_by' => Auth::user()->username,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // $reference = $invNumber;
        $reference = '';

        /*
            1.piutang usaha dulu
        */

        $dataSet = [];
        $dataSet[] = [
            'voucher_number' => $invNumber,
            'account' =>$invData->account_piutang,
            'description' => $invNumber.' '.$invData->customer_name,
            'debit' => $invData->grand_total,
            'credit' => 0,
            'reference' => $reference,  //sementara tidak di masukan belum tau fungsinya apa
            'created_by' => Auth::user()->username,
            'updated_by' => Auth::user()->username,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'cost_center' => $costCenter
        ];

        /*
            2.pph dibayar dimuka 1100.75

        */
        
        if($invData->total_pph > 0){
            $dataSet[] = [
                'voucher_number' => $invNumber,
                'account' =>$pphDibayarDimuka,
                'description' => $invNumber.' '.$invData->customer_name,
                'debit' => $invData->total_pph,
                'credit' => 0,
                'reference' => $reference,
                'created_by' => Auth::user()->username,
                'updated_by' => Auth::user()->username,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'cost_center' => $costCenter
            ];
        }

        /*
            3.penjualan
        */

        $dataSet[] = [
            'voucher_number' => $invNumber,
            'account' =>$invData->account_penjualan,
            'description' => $invNumber.' '.$invData->customer_name,
            'debit' => 0,
            'credit' => $invData->dpp,
            'reference' => $reference,  //sementara tidak di masukan belum tau fungsinya apa
            'created_by' => Auth::user()->username,
            'updated_by' => Auth::user()->username,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'cost_center' => $costCenter
        ];

        /*
            3.ppn keluran customer
        */

        if($invData->total_ppn > 0){
            $dataSet[] = [
                'voucher_number' => $invNumber,
                'account' =>$ppnKeluaranCustomer,
                'description' => $invNumber.' '.$invData->customer_name,
                'debit' => 0,
                'credit' => $invData->total_ppn,
                'reference' => $reference,
                'created_by' => Auth::user()->username,
                'updated_by' => Auth::user()->username,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'cost_center' => $costCenter
            ];
        }       

        DB::table('kas_det')->insert($dataSet);
    }

    public function prosesUpdatePosting($invNumber){
        /* Proses posting ke kas*/

        $pphDibayarDimuka = '1100.75';
        $ppnKeluaranCustomer = '2000.14.1';
        $costCenter = '007';

        $invData = db::table('invoice_hdr')
        ->leftJoin('third_party', 'third_party.kode', '=', 'invoice_hdr.customer_id')
        ->select('invoice_hdr.*','third_party.nama as customer_name')
        ->where('invoice_number',$invNumber)->first();

        $periodYear=(int)explode('-', $invData->invoice_date)[2];
        $invStatus = $invData->status == '4'? '3' : $invData->status;
        $createdBy = $invData->created_by;
        $createdAt = $invData->created_at;

        $row_affected=DB::table('kas_hdr')
        ->where('voucher_number',$invNumber)
        ->update(
            [
                'voucher_type' =>$this->moduleCode,
                'voucher_date' =>$invData->invoice_date, //invoice date
                'paid_to' => $invData->customer_id,
                'description' => $invNumber,
                'amount' => $invData->grand_total,
                'period' => $invData->period,
                'year' => $periodYear,                        
                'note' => $invData->note,
                'status' => $invStatus,
                'updated_by' => Auth::user()->username,
                'updated_at' => date('Y-m-d H:i:s')
            ]
        );

        DB::table('kas_det')
        ->where('voucher_number',$invNumber)
        ->delete();

        // $reference = $invNumber;
        $reference = '';

        /*
            1.piutang usaha dulu
        */

        $dataSet = [];
        $dataSet[] = [
            'voucher_number' => $invNumber,
            'account' =>$invData->account_piutang,
            'description' => $invNumber.' '.$invData->customer_name,
            'debit' => $invData->grand_total,
            'credit' => 0,
            'reference' => $reference,  //sementara tidak di masukan belum tau fungsinya apa
            'created_by' => $createdBy,
            'updated_by' => Auth::user()->username,
            'created_at' => $createdAt,
            'updated_at' => date('Y-m-d H:i:s'),
            'cost_center' => $costCenter
        ];

        /*
            2.pph dibayar dimuka 1100.75

        */
        
        if($invData->total_pph > 0){
            $dataSet[] = [
                'voucher_number' => $invNumber,
                'account' =>$pphDibayarDimuka,
                'description' => $invNumber.' '.$invData->customer_name,
                'debit' => $invData->total_pph,
                'credit' => 0,
                'reference' => $reference,
                'created_by' => $createdBy,
                'updated_by' => Auth::user()->username,
                'created_at' => $createdAt,
                'updated_at' => date('Y-m-d H:i:s'),
                'cost_center' => $costCenter
            ];
        }

        /*
            3.penjualan
        */

        $dataSet[] = [
            'voucher_number' => $invNumber,
            'account' =>$invData->account_penjualan,
            'description' => $invNumber.' '.$invData->customer_name,
            'debit' => 0,
            'credit' => $invData->dpp,
            'reference' => $reference,  //sementara tidak di masukan belum tau fungsinya apa
            'created_by' => $createdBy,
            'updated_by' => Auth::user()->username,
            'created_at' => $createdAt,
            'updated_at' => date('Y-m-d H:i:s'),
            'cost_center' => $costCenter
        ];

        /*
            3.ppn keluran customer
        */

        if($invData->total_ppn > 0){
            $dataSet[] = [
                'voucher_number' => $invNumber,
                'account' =>$ppnKeluaranCustomer,
                'description' => $invNumber.' '.$invData->customer_name,
                'debit' => 0,
                'credit' => $invData->total_ppn,
                'reference' => $reference,
                'created_by' => $createdBy,
                'updated_by' => Auth::user()->username,
                'created_at' => $createdAt,
                'updated_at' => date('Y-m-d H:i:s'),
                'cost_center' => $costCenter
            ];
        }       

        DB::table('kas_det')->insert($dataSet);
    }

    public function prosesAllPosting(){
        $listInvoice = db::table('invoice_hdr')
        ->whereIn('status',['1','2','3'])
        ->whereNotIn(DB::raw("invoice_number"), function($query) {
            $query->select('voucher_number')
            ->from('kas_hdr');
        })
        ->get();

        foreach($listInvoice as $val){
            $this->prosesPosting($val->invoice_number);
        }

        return "beres";

    }
}
