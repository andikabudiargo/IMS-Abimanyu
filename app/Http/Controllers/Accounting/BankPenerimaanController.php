<?php

namespace App\Http\Controllers\Accounting;

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

class BankPenerimaanController extends Controller
{
    private $title;
    private $moduleCode;
    private $lockDate;
    private $lockDateIndex;

    public function __construct()
    {
        $this->title = "Bank Penerimaan";
        $this->moduleCode = "BM";

        $lockDate1 = DB::table('application_lock')
        ->where('code_key',$this->moduleCode)
        ->where('status','1')
        ->value('lock_date');

        $todayDate = date('Y-m-d');
        $lockDateHere = $lockDate1 ? $lockDate1 : '2023-01-01' ;
        $lockDateAt = date('Y-m-d', strtotime("+1 day", strtotime($lockDateHere)));

        if ($todayDate < $lockDateAt ){
            $firstDatePrevMonth = date('1-m-Y', strtotime("-1 months",strtotime($lockDateHere)));
            $lockDateAt = $firstDatePrevMonth;
        }else{
            $lockDateAt = date('1-m-Y', strtotime($lockDateAt));
        }

        $this->lockDate = $lockDateAt;

        $lockDateHereIndex = $lockDate1 ? $lockDate1 : '2023-01-01' ;
        $lockDateAtIndex = date('d-m-Y', strtotime($lockDateHere));
        $this->lockDateIndex = $lockDateAtIndex;
    }

    public function getTableColoumn()
    {
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=> false,'searchable'=>false],
            ['data'=>'voucher_number','name'=>'voucher_number','title'=>'Voucher Number'],
            ['data'=>'statusku','name'=>'statusku','title'=>'Status'],
            ['data'=>'voucher_date','name'=>'voucher_date','title'=>'Date'],
            ['data'=>'voucher_date_2','name'=>'voucher_date_2','title'=>'Date','visible'=>false],
            ['data'=>'receive_name','name'=>'receive_name','title'=>'Receive From'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'amount','name'=>'amount','title'=>'Amount'],
            ['data'=>'period','name'=>'period','title'=>'Period'],
            ['data'=> 'approval_by', 'name'=> 'approval_by','title'=>'Approved By' ],
            ['data'=> 'approval_at', 'name'=> 'approval_at','title'=>'Approved At' ],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created At']
        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnDetail()
    {
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=> false,'searchable'=>false],
            ['data'=>'voucher_number','name'=>'voucher_number','title'=>'Voucher Number'],
            ['data'=>'account_number','name'=>'account_number','title'=>'Account Number'],
            ['data'=>'amount','name'=>'amount','title'=>'Amount'],
            ['data'=>'voucher_date','name'=>'voucher_date','title'=>'Date'],
            ['data'=>'currency','name'=>'currency','title'=>'Currency'],
            ['data'=>'kurs','name'=>'kurs','title'=>'Kurs'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'order_no','name'=>'order_no','title'=>'Urutan'],
            ['data'=>'decription','name'=>'decription','title'=>'Desciption'],
            ['data'=>'debit','name'=>'debit','title'=>'Debit account'],
            ['data'=>'credit','name'=>'credit','title'=>'Credit account'],
            ['data'=>'amount','name'=>'amount','title'=>'Amount'],
            ['data'=>'memo','name'=>'memo','title'=>'Memo'],
            ['data'=>'auth_by','name'=>'auth_by','title'=>'Auth By'],
            ['data'=>'prep_by','name'=>'prep_by','title'=>'Prep By']
        ];
        return json_encode($kolom, true);
    }

    public function getLastCode($key,$period,$year)
    {
        /*
            old ways
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
            $month = $months[$period-1];
            // $month = str_pad(date('n'),2,"0",STR_PAD_LEFT);
            // $month = str_pad($period,2,"0",STR_PAD_LEFT);
            $year = date('y');
            // BM-ASN-23-X-0001
            $code="$key-ASN-$year-$month-$newCode";
            // $code="$key/$month/$year/$newCode";
        */

         /*
            CR - 2-8-2024
            Request: Diharapkan nomor invoice setiap tahunnya dapat mulai lagi dari awal. 
            Misal jika user menginputkan data tahun 2022, 
            maka sistem akan mengecek terlebih dahulu apakah ada invoice di tahun tersebut, 
            Jika di tahun tersebut nomor invoice terakhir yang diinputkan adalah: INV-ASN-22-VII-005, 
            maka data yang diinputkan akan otomatis menjadi INV-ASN-22-VII-006. 
            Begitupun ketika user menginputkan data di tahun 2025, 
            maka nomor invoicenya menjadi INV-ASN-25-I-001 dan seterusnya. 
            Serta, 
            sistem harus dapat mengecek nomor terkecil terlebih dahulu, 
            misal pada tahun 2022, nomor invoice terakhir adalah: INV-ASN-22-VII-002 
            namun ternyata tidak ada Invoice dengan nomor INV-ASN-22-VII-001, 
            maka invoice terbaru di tahun 2022 otomatis menjadi INV-ASN-22-VII-001
        */

        /*
            BM-ASN-23-X-0045
            BM/10/23/0006
        */

        $getCurrentYear = date('y');
        $inputYear = $year;
        $basicCode1 = "$key-ASN-$inputYear";
        $basicCode2 = "$key/__/$inputYear";
        $getLastCode = DB::table('kas_hdr')
        ->where(function($query) use ($basicCode1,$basicCode2){
            $query->where('voucher_number','like',$basicCode1.'%');
            $query->orWhere('voucher_number','like',$basicCode2.'%');
        })
        // ->where('invoice_number','like',$basicCode1.'%')
        // ->orWhere('invoice_number','like',$basicCode2.'%')
        ->where('status','<>','5')
        ->where('voucher_type',$key)
        ->orderBy(DB::raw("right(voucher_number,4)::numeric"),'desc')
        ->select(DB::raw("right(voucher_number,4) as last_code"))
        ->value('last_code');

        $getLastCode = $getLastCode ? $getLastCode : 1;

        $getMissingCode = DB::SELECT("SELECT generate_series(0001, $getLastCode) as missing_code
        except
        select voucher_number::integer from (select right(voucher_number,4) as voucher_number from kas_hdr 
        where (voucher_number like '$basicCode1%' or  voucher_number like '$basicCode2%') and (status <> '5') and (voucher_type = '$key') order by  id) as oki
        order by missing_code limit 1");

        if(count($getMissingCode) > 0){
            /*
                ini karena di tahun 2024 ada data yang kehapus ditengah dan nomornya kecil jadi di skip aja
            */
            if($year == '24'){
                $newCode = ($getLastCode*1)+1;
            }else{
                $newCode = $getMissingCode[0]->missing_code;
            }
            // $newCode = $getMissingCode[0]->missing_code;
        }else{
            $newCode = ($getLastCode*1)+1;
        }
        
        $newCode = str_pad($newCode,4,"0",STR_PAD_LEFT);
        $months = ['I', 'II', 'III','IV','V', 'VI', 'VII', 'VIII','IX','X','XI','XII'];
        $month = $months[$period-1];
        $year = $inputYear;
        $code="$key-ASN-$year-$month-$newCode";

        return $code;
    }

    public function getLastCode_old($key,$period,$year)
    {
        /*
            old ways
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
            $month = $months[$period-1];
            // $month = str_pad(date('n'),2,"0",STR_PAD_LEFT);
            // $month = str_pad($period,2,"0",STR_PAD_LEFT);
            $year = date('y');
            // BM-ASN-23-X-0001
            $code="$key-ASN-$year-$month-$newCode";
            // $code="$key/$month/$year/$newCode";
        */

        $getCurrentYear = date('y');
        $inputYear = $year;
        $basicCode = "______-$inputYear";

        $getResetRule = DB::table('master_code')
        ->where('code_key',$key)
        ->value('reset_by');

        if($getResetRule == 'YEAR'){
            $getLastNumber = DB::table('kas_hdr')
            ->where('voucher_number','like',$basicCode.'%')
            // ->where('status','<>','5')
            ->where('voucher_type',$key)
            ->orderBy('id','desc')
            ->first();
        }else{
            $getLastNumber = DB::table('kas_hdr')
            // ->where('status','<>','5')
            ->where('voucher_type',$key)
            ->orderBy('id','desc')
            ->first();
        }       

        if ($getLastNumber){
            $getYear = explode('-',$getLastNumber->voucher_number)[2];
            $getLastCode = explode('-',$getLastNumber->voucher_number)[4];
            $newCode = ($getLastCode*1)+1;
        }else{
            $getYear = $getCurrentYear;
            $newCode = 1;
        }

        $newCode = str_pad($newCode,4,"0",STR_PAD_LEFT);
        $months = ['I', 'II', 'III','IV','V', 'VI', 'VII', 'VIII','IX','X','XI','XII'];
        $month = $months[$period-1];
        $year = $inputYear;
        $code="$key-ASN-$year-$month-$newCode";

        return $code;
    }

    public function index(Request $request)
    {
        $data['title'] = $this->title;
        $data['type'] = 'penerimaan';

        $data['kolom'] = $this->getTableColoumn();
        $data['kolomDetail'] = $this->getTableColoumnDetail();

        $status = ['NEW','VALIDATED','APPROVED','','DELETED','CLOSED'];
        $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED'];

        $data['accounts'] =db::select("SELECT account,description
        ,(select account from third_party where kode =(select other_code from third_party where account = accounts.account)) as supp_coa
        ,(select other_code from third_party where account = accounts.account) as supplier_code
        from accounts 
        where acc_header <> 'HEADER'
        union
        select kode as account,nama description,'a' as a,'b' as b from third_party where third_party_type = 'supp' order by account");

        $data['lockDate'] = $this->lockDateIndex;

        return view("accounting.bank.index",$data);
    }

    public function create(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create $this->title";
        $data['type'] = 'penerimaan';

        $data['accounts'] =db::select("SELECT account,description
        ,(select account from third_party where kode =(select other_code from third_party where account = accounts.account)) as supp_coa
        ,(select other_code from third_party where account = accounts.account) as supplier_code
        from accounts 
        where acc_header <> 'HEADER'
        union
        select kode as account,nama description,'a' as a,'b' as b from third_party where third_party_type = 'supp' order by account");
        
        // $suppliers = DB::table('third_party')
        // ->select('kode as account','nama as description')
        // ->where('third_party_type','supp')
        // // ->orderBy('nama')
        // ->get();

        // $data['accounts'] = DB::table('accounts')
        // ->where('acc_header','!=','HEADER')
        // ->select('account','description')
        // // ->orderBy('account')
        // ->union($suppliers)
        // ->get();

        // dd($data['accounts']);

        $data['depts'] = DB::table('depts')
        ->orderBy('name')
        ->get();

        $data['edit'] = 'false';

        return view("accounting.bank.create",$data);

    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $details = json_decode($request->details);
        $vcDate = $request->vcDate;
        $period = $request->period;
        $note = $request->note;
        $totalAmount= $request->totalAmount;
        $recFrom = $request->recFrom;
        $recFromDesc = $request->recFromDesc;
        $status = '1';
        $leadCode =$this->moduleCode;

        /* batal pengkodean untuk angka romawi/bulan  jadi nya dari period
        $periodNomor=(int)explode('-', $vcDate)[1];
        */
        
        $periodYear=(int)explode('-', $vcDate)[2];
        $periodNomor=$period;

        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
            // 'iunique' => "KM Number has already been taken",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $validation = Validator::make($request->all(),$messages = [
            // 'poNumber'=>'required|unique:purchase_order_hdr,po_number',
            // 'pcNumber'  => 'required',
            'period'  => 'required'
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
            // $hasilUpdate = AppHelpers::resetCode($leadCode);
            $inputYear = substr($vcDate,-2);
            $vcNumber = $this->getLastCode($leadCode,$periodNomor,$inputYear);
            DB::beginTransaction();
            try {
                    DB::table('kas_hdr')->insert([
                        'voucher_number' => $vcNumber,
                        'voucher_type' => $leadCode,
                        'voucher_date' => $vcDate,
                        'receive_from' => $recFrom,
                        'description' => $recFromDesc,
                        // 'paid_to' =>,
                        'amount' => $totalAmount,
                        'period' => $period,
                        'year' => $periodYear,                        
                        'note' => $note,
                        'status' => $status,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                    $dataSet = [];
                    foreach ($details as $val) {
                        $dataSet[] = [
                            'voucher_number' => $vcNumber,
                            'account' => $val->account,
                            'description' => $val->description,
                            'cost_center' => $val->cc,
                            'debit' => $val->debit,
                            'credit' => $val->credit,
                            'reference' => $val->reference,
                            'created_by' => Auth::user()->username,
                            'updated_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                    }

                    DB::table('kas_det')->insert($dataSet);

                    DB::commit();
                    $title ="Save $this->title";
                    $alert  ="success";
                    $message  = "$title $vcNumber is successfully saved";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'vcNumber'=>$vcNumber));
            } catch (Exception $e) {
                DB::rollBack();
                $title ="Save $this->title";
                $alert  ="warning";
                $message  = "$vcNumber is failed to save";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'pcNumber'=>$vcNumber));
            }
        }
    }

    public function show(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;
        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";

        $data['header'] = DB::table('kas_hdr')
        // ->leftJoin('accounts','accounts.account','kas_hdr.receive_from')
        ->select('kas_hdr.*'
        ,'description as receive_name'
        // ,db::raw("concat(receive_from,'-',description) as receive_name")
        // ,db::raw("concat(accounts.account,'-',description) as receive_name")
        )
        ->where('kas_hdr.id',$id)
        ->get()->first();

        $vcNumber = $data['header']->voucher_number;
            
        $data['details'] = DB::table('kas_det')
        ->leftJoin('accounts','accounts.account','kas_det.account')
        ->leftJoin('depts','depts.code','kas_det.cost_center')
        ->select('kas_det'.'.*'
            ,db::raw("concat(accounts.account,'-',accounts.description) as account_name")
            ,'depts.name as cost_center_name'
        )
        ->where('voucher_number',$vcNumber)
        ->orderBy('id')
        ->get();

        $data['total']=DB::table('kas_det')
        ->select(DB::raw("sum(credit) as total_credit"),DB::raw("sum(debit) as total_debit"))
        ->where('voucher_number',$vcNumber)
        ->first();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$vcNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$vcNumber,$username);

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'','5'=>'DELETED','6'=>'CLOSED'];
        $status = ['NEW','VALIDATED','APPROVED','','DELETED','CLOSED'];
        $data['status'] = $status[$data['header']->status-1];

        return view("accounting.bank.show",$data);
        
    }

    public function edit(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;
        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";
        $data['type'] = 'penerimaan';

        $data['header'] = DB::table('kas_hdr')
        // ->leftJoin('accounts','accounts.account','kas_hdr.receive_from')
        ->select('kas_hdr.*'
        ,'description as receive_name'
        // ,db::raw("concat(accounts.account,'-',description) as receive_name")
        )
        ->where('kas_hdr.id',$id)
        ->get()->first();

        $vcNumber = $data['header']->voucher_number;
            
        $data['details'] = DB::table('kas_det')
        ->leftJoin('accounts','accounts.account','kas_det.account')
        ->leftJoin('depts','depts.code','kas_det.cost_center')
        ->select('kas_det'.'.*'
            ,db::raw("concat(accounts.account,'-',accounts.description) as account_name")
            ,'depts.name as cost_center_name'
        )
        ->where('voucher_number',$vcNumber)
        ->orderBy('id')
        ->get();

        // $data['accounts'] = DB::table('accounts')
        // ->where('acc_header','!=','HEADER')
        // ->orderBy('account')
        // ->get();

        $data['accounts'] =db::select("SELECT account,description
        ,(select account from third_party where kode =(select other_code from third_party where account = accounts.account)) as supp_coa
        ,(select other_code from third_party where account = accounts.account) as supplier_code
        from accounts 
        where acc_header <> 'HEADER'
        union
        select kode as account,nama description,'a' as a,'b' as b from third_party where third_party_type = 'supp' order by account");

        $data['depts'] = DB::table('depts')
        ->orderBy('name')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$vcNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$vcNumber,$username);

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'','5'=>'DELETED','6'=>'CLOSED'];
        $status = ['NEW','VALIDATED','APPROVED','','DELETED','CLOSED'];
        $data['status'] = $status[$data['header']->status-1];

        $data['edit'] = 'true';

        return view("accounting.bank.edit",$data);
        
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $details = json_decode($request->details);
        $vcNumber = $request->vcNumber;
        $vcDate = $request->vcDate;
        $period = $request->period;
        $note = $request->note;
        $totalAmount= $request->totalAmount;
        $recFrom = $request->recFrom;
        $recFromDesc = $request->recFromDesc;
        // $status = '1';
        $leadCode =$this->moduleCode;
        
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
            // 'iunique' => "KM Number has already been taken",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $validation = Validator::make($request->all(),$messages = [
            // 'poNumber'=>'required|unique:purchase_order_hdr,po_number',
            // 'pcNumber'  => 'required',
            'period'  => 'required'
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

                    $row_affected=DB::table('kas_hdr')
                    ->where('voucher_number',$vcNumber)
                    ->update(
                        [
                            'voucher_date' =>$vcDate,
                            'receive_from' =>$recFrom,
                            'description' =>$recFromDesc,
                            // 'paid_to' =>,
                            'amount' =>$totalAmount,
                            'period' =>$period,
                            'note' => $note,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );

                    DB::table('kas_det')
                        ->where('voucher_number',$vcNumber)
                        ->delete();

                    $dataSet = [];
                    foreach ($details as $val) {
                        $dataSet[] = [
                            'voucher_number' => $vcNumber,
                            'account' => $val->account,
                            'description' => $val->description,
                            'cost_center' => $val->cc,
                            'debit' => $val->debit,
                            'credit' => $val->credit,
                            'reference' => $val->reference,
                            'created_by' => Auth::user()->username,
                            'updated_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                    }

                    DB::table('kas_det')->insert($dataSet);

                    //update invoice jadi paid
                    // ['1'=>'DRAFT','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'CLOSED','6'=>'PAID'];

                    // $listInvoice=DB::table('kas_det')
                    // ->where('voucher_number',$vcNumber)
                    // ->pluck('reference')->toArray();

                    $status=DB::table('kas_hdr')
                    ->where('voucher_number',$vcNumber)
                    ->value('status');

                    // $customerAccount = DB::table('kas_hdr')->where('voucher_number',$vcNumber)->value('account');
             
                    if($status == '3'){

                        $this->paidTransaction($vcNumber);

                        // DB::table('invoice_hdr')
                        // ->whereIn('invoice_number',$listInvoice)
                        // ->update(
                        //     [   
                        //         'status' =>'6',
                        //         'updated_by' => Auth::user()->username,
                        //         'updated_at' => date('Y-m-d H:i:s'),
                        //     ]
                        // );
                    }

                    DB::commit();
                    $title ="Update $this->title";
                    $alert  ="success";
                    $message  = "$title $vcNumber is successfully updated";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'vcNumber'=>$vcNumber));
            } catch (Exception $e) {
                DB::rollBack();
                $title ="Update $this->title";
                $alert  ="warning";
                $message  = "$vcNumber is failed update";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'pcNumber'=>$vcNumber));
            }
        }

    }

    public function approve(Request $request)
    {
        $username =  Auth::user()->username;
        $vcNumber = $request->vcNumber;
        $statusLevelApproval = Approval::approvalLevelPosition($this->moduleCode,$vcNumber,$username);        
        $nextLevel = $statusLevelApproval[0]->next_level;
        $status = $statusLevelApproval[0]->next_level == $statusLevelApproval[0]->max_level ? '3' :'2';
        $maxLevel = $statusLevelApproval[0]->max_level;

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'','5'=>'DELETED','6'=>"CLOSED"];
                
        DB::beginTransaction();
        try {
                $row_affected=DB::table('kas_hdr')
                ->where('voucher_number',$vcNumber)
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
                        'module_number' => $vcNumber,
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

                // $listInvoice=DB::table('kas_det')
                // ->where('voucher_number',$vcNumber)
                // ->pluck('reference')->toArray();
                
                //update invoice jadi paid
                // ['1'=>'DRAFT','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'CLOSED','6'=>'PAID'];
                if($status == '3'){

                    $this->paidTransaction($vcNumber);

                    // DB::table('invoice_hdr')
                    // ->whereIn('invoice_number',$listInvoice)
                    // ->update(
                    //     [   
                    //         'status' =>'6',
                    //         'updated_by' => Auth::user()->username,
                    //         'updated_at' => date('Y-m-d H:i:s'),
                    //     ]
                    // );
                }
                
                /*
                    permintaan pak leo 9-11-2023
                    untuk akun pak Budi bisa auto apporoved

                */
                if( $nextLevel == ($maxLevel-1) ){
                    $this->autoApprove($vcNumber,'budi');
                }

                DB::commit();
                $title ="Approve $this->title";
                $alert  ="success";
                $message  = "$title $vcNumber is successfully Approve-".$nextLevel;
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => $status,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'$vcNumber'=>$vcNumber));

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Approve $this->title";
            $alert  ="warning";
            $message  = "$title $vcNumber is failed to Approve-".$nextLevel;
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('status' => $status,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'$vcNumber'=>$vcNumber));
        }
    }

    public function autoApprove($vcNumber,$username)
    {
       
        $statusLevelApproval = Approval::approvalLevelPosition($this->moduleCode,$vcNumber,$username);        
        $nextLevel = $statusLevelApproval[0]->next_level;
        $status = $statusLevelApproval[0]->next_level == $statusLevelApproval[0]->max_level ? '3' :'2';

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'','5'=>'DELETED','6'=>"CLOSED"];
                
        DB::beginTransaction();
        try {
                $row_affected=DB::table('kas_hdr')
                ->where('voucher_number',$vcNumber)
                ->update(
                    [
                        'status' => $status,
                        'updated_by' => $username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                if ($row_affected){
                    DB::table('approval_history')->insert([
                        'module_code' => $this->moduleCode,
                        'module_number' => $vcNumber,
                        'username' => $username,
                        'approval_order' => $nextLevel,
                        'approval_date' => date('Y-m-d'),
                        'status' => 1,
                        'created_by' => $username,
                        'updated_by' => $username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
                
                //update invoice jadi paid
                // ['1'=>'DRAFT','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'CLOSED','6'=>'PAID'];
                if($status == '3'){
                    $this->paidTransaction($vcNumber);
                }
                
                DB::commit();
                $title ="Auto Approve $this->title";
                $alert  ="success";
                $message  = "$title $vcNumber is successfully Approve-".$nextLevel;
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => $status,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'$vcNumber'=>$vcNumber));

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Auto Approve $this->title";
            $alert  ="warning";
            $message  = "$title $vcNumber is failed to Approve-".$nextLevel;
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('status' => $status,'status' => 0,'title' => $title, 'message' => $message,'alert'=>$alert,'$vcNumber'=>$vcNumber));
        }
    }

    public function destroy(Request $request)
    {
        $username =  Auth::user()->username;    
        $id=Crypt::decryptString($request->id);   
        $status = '5'; //DELETED
        
        /*
            status nya bukan 6 bisa di delete
            status 6 = closed
        */

        /*
            kalau status  1 atau  masih new bisa di delete permanen, tapi kalau statusnya selain itu ditandai delete cancel saja
        */

        $vcStatus = DB::table('kas_hdr')->where('id',$id)->first();
        $vcNumber = $vcStatus->voucher_number;
        $rowAffected = 0;

        if ($vcStatus->status === '1'){
            $rowAffected = DB::table('kas_hdr')->where('id',$id)->delete();
            if($rowAffected){
                DB::table('kas_det')->where('voucher_number',$vcNumber)->delete();
            }
        }else{

            $vcNumber = DB::table('kas_hdr')->where('id',$id)->where('status','<>','6')->value('voucher_number');
            $newVcNumber =  $vcNumber."(D)";
            for ($i=0;$i<10;$i++){
                $sudahPernahDelete  = DB::table('kas_hdr')->where('voucher_number',$newVcNumber)->count();
                $newVcNumber = $sudahPernahDelete == 0 ? $newVcNumber : $newVcNumber."(D)";
                if ($sudahPernahDelete == 0){
                    break;
                }
            }
        
            $rowAffected=DB::table('kas_hdr')
            ->where('voucher_number',$vcNumber)
            ->update(
                [
                    'voucher_number' => $newVcNumber,
                    'status' =>$status,
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );
        
            // $rowAffected = DB::table('kas_hdr')->where('id',$id)->delete();

            if($rowAffected>0){

                $rowAffected=DB::table('kas_det')
                ->where('voucher_number',$vcNumber)
                ->update(
                    [
                        'voucher_number' => $newVcNumber,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                DB::table('approval_history')
                ->where('module_number',$vcNumber)
                ->where('module_code',$this->moduleCode)
                ->update(
                    [   
                        'module_number' => $newVcNumber,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );
            }
        }

        if($rowAffected>0){
            $title ="Delete $this->title";
            $alert  ="success";
            $message  = "$vcNumber Successfully Deleted";
            \LogActivity::addToLog("$this->moduleCode delete ","username: $username Status $message");
            // return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }else{
            $title ="Delete $this->title";
            $alert  ="warning";
            $message  = "$vcNumber Failed to Delete";
            \LogActivity::addToLog("$this->moduleCode delete ","username: $username Status $message");
            // return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }
    }

    public function list(Request $request)
    {
        $seachVc = strtolower($request->seachVc);
        $vcDate = $request->vcDate;
        $vcType = $this->moduleCode;
        $period1 = $request->period1;
        $period2 = $request->period2;
        $year = $request->year;
        $fromDate = "";
        $toDate = "";
        $searchStatus=$request->searchStatus;
        $searchRecFrom = $request->searchRecFrom;

        if ($vcDate){
            $date = explode("to",$vcDate);
            // $fromDate = trim($date[0]);
            // $toDate = trim($date[1]);
            if(count($date)>1){
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate; 
            }

        }

        $data = DB::table('kas_hdr')
        // ->leftJoin('accounts','accounts.account','kas_hdr.receive_from')
        ->where(function ($query) use ($seachVc,$vcDate,$fromDate,$toDate,$year,$searchStatus,$searchRecFrom,$period1,$period2) {
            $seachVc ? $query->where('voucher_number','ilike','%'.$seachVc.'%') : '';
            $vcDate ? $query->whereBetween(DB::raw("to_date(voucher_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
            $period1 ? $query->whereBetween(db::raw("period::integer"),[$period1,$period2]) : '';
            $year ? $query->where('year', $year) : '';
            $searchStatus ? $query->where('kas_hdr.status', $searchStatus) : '';
            $searchRecFrom ? $query->where('kas_hdr.receive_from', $searchRecFrom) : '';
        })
        ->where('voucher_type',$vcType)
        ->where('kas_hdr.status','<>','5')
        ->select(
            'kas_hdr.*'
            ,DB::raw("to_char(to_date(voucher_date, 'DD-MM-YYYY'), 'DD/MM/YYYY') as voucher_date")
            ,DB::raw("to_date(voucher_date, 'DD-MM-YYYY') as voucher_date_2")
            ,'kas_hdr.status as statusku'
            // ,db::raw("concat(accounts.account,'-',description) as receive_name")
            ,'description as receive_name'
            ,db::raw("(select (select name from users where username = z.username) from approval_history z where module_number = kas_hdr.voucher_number order by approval_order desc limit 1) as approval_by")
            ,db::raw("(select to_char(approval_date::date, 'DD-MM-YYYY') from approval_history z where module_number = kas_hdr.voucher_number order by approval_order desc limit 1) as approval_at")
        )
        ->orderBy('id')
        ->get(); 

        $lockDateToDate = date('Y-m-d',strtotime($this->lockDate));
       
        return Datatables::of($data)
        ->addColumn('action', function ($data) use ($lockDateToDate) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';

            if ( $data->statusku == '2' or $data->statusku == '1') {
                // if (Auth::user()->can('kasPenerimaan-approve')) {
                $buttons .=     '<a href="'. route('bankPenerimaan.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="check"></i>
                                    <span>'. __("Approve") .'</span>
                                </a>';
                // }
            }
            
            // if (Auth::user()->can('kasPenerimaan-edit')) {
                //sibuka sementara dari pak leo 6-11-2023
                // if ( $data->statusku == '2' or $data->statusku == '1') {
                $bmDate = date('Y-m-d', strtotime($data->voucher_date_2));
                if($bmDate>=$lockDateToDate){
                    $buttons .=     '<a href="'. route('bankPenerimaan.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
                }
                // }
            // }

            $buttons .=         '<a href="'. route('bankPenerimaan.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    Detail
                                </a>';
                        
            $buttons .=         '<a href="'. route('bankPenerimaan.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
                                    <i data-feather="printer"></i>
                                    Print
                                </a>';
            
            
            // if (Auth::user()->can('kasPenerimaan-delete')) {
            if ($data->statusku != '5') {
                $bmDate = date('Y-m-d', strtotime($data->voucher_date_2));
                if($bmDate>=$lockDateToDate){
                    $buttons .=  "<a href='javascript:;'
                                    id='deleteButton'
                                    class='dropdown-item'
                                    data-toggle='modal'
                                    data-target='#smallModal'
                                    data-href='". route("bankPenerimaan.destroy", ['id'=>Crypt::encryptString($data->id)]) ."'>
                                    <i data-feather='trash-2' class='feather-14-red'></i>
                                    Delete
                                </a>";
                }
            }

            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        ->addColumn('statusku', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-secondary'];
            $status = ['NEW','VALIDATED','APPROVED','','DELETED','CLOSED'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$status[$data->status - 1]."</div>";
        })
        ->addColumn('voucher_number', function ($data) {
            return '<a href="'. route('bankPenerimaan.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" style="padding:0px">
                '.$data->voucher_number.'
            </a>';
        })
        ->rawColumns(['action','statusku','voucher_number'])
        ->make(true);
    }

    public function print(Request $request)
    {
        $id=Crypt::decryptString($request->id);

        $data['title'] ='Bank Masuk';
        
        $data['header']=DB::table('kas_hdr')
        // ->leftJoin('accounts','accounts.account','kas_hdr.receive_from')
        ->select('kas_hdr.*'
        ,'description as receive_name'
        // ,'accounts.description as receive_name'
        )
        ->where('kas_hdr.id',$id)
        ->first();

        $vcNumber=$data['header']->voucher_number;
       
        $data['details']=DB::table('kas_det')
        ->leftJoin('accounts','accounts.account','kas_det.account')
        ->select('kas_det.*','accounts.description as account_name')
        ->where('voucher_number',$vcNumber)
        ->orderBy('debit')
        ->orderBy('id')
        ->get();

        $limits = 24;

        $data['details']=DB::table('kas_det')
        ->leftJoin('accounts','accounts.account','kas_det.account')
        ->select('kas_det.*','accounts.description as account_name')
        ->where('voucher_number',$vcNumber)
        ->orderBy('debit')
        ->orderBy('id')
        ->limit($limits)
        ->get();

        $data['details2']=DB::table('kas_det')
        ->leftJoin('accounts','accounts.account','kas_det.account')
        ->select('kas_det.*','accounts.description as account_name')
        ->where('voucher_number',$vcNumber)
        ->orderBy('debit')
        ->orderBy('id')
        ->offset($limits)
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
        ->where('module_number',$vcNumber)
        ->where('approval_order',1)
        ->first();

        $data['approval2']=DB::table('approval_history')
        ->leftJoin('users','users.username','approval_history.username')
        ->where('module_code',$this->moduleCode)
        ->where('module_number',$vcNumber)
        ->where('approval_order',2)
        ->first();

        $data['approval3']=DB::table('approval_history')
        ->leftJoin('users','users.username','approval_history.username')
        ->where('module_code',$this->moduleCode)
        ->where('module_number',$vcNumber)
        ->where('approval_order',3)
        ->first();

        $data['approval4']=DB::table('approval_history')
        ->leftJoin('users','users.username','approval_history.username')
        ->where('module_code',$this->moduleCode)
        ->where('module_number',$vcNumber)
        ->where('approval_order',4)
        ->first();

        return view('accounting.bank.print',$data);

        // view()->share($data);

        // $pdf = PDF::loadView('accounting.bank.print');
        // return $pdf->stream("$vcNumber.pdf");

    }

    public function getInvoiceAmount(Request $request)
    {
        $refNumber = $request->vRef;
        $amount1 = db::table('invoice_hdr')
        ->where('invoice_number',$refNumber)
        // ->select(db::raw("dpp+vat as amount"))
        ->select(db::raw("grand_total as amount"))
        ->value('amount');

        $amount2 = db::table('debit_note_hdr')
        ->where('dn_number',$refNumber)
        ->select(db::raw("grand_total as amount"))
        ->value('amount');

        $amount = $amount1+$amount2; 

        return response()->json(array('amount' => $amount));
    }

    public function paidTransaction($vcNumber)
    {

        $supplierCode = DB::table('third_party')
        ->where('account', function($query) use ($vcNumber) {
            $query->select('receive_from')
            ->from('kas_hdr') 
            ->where('voucher_number',$vcNumber)
            ->value('receive_from');
        })->value('other_code');

        $listInvoice=DB::table('kas_det')
        ->where('voucher_number',$vcNumber)
        ->where('reference','<>',null)
        ->pluck('reference')->toArray();
        
        DB::table('ap_invoice')
        ->whereIn('inv_number',$listInvoice)
        ->where('supplier_id',$supplierCode)
        ->update(
            [   
                'status' =>'6',
                'updated_by' => Auth::user()->username,
                'updated_at' => date('Y-m-d H:i:s'),
            ]
        );

        DB::table('invoice_hdr')
        ->whereIn('invoice_number',$listInvoice)
        ->update(
            [   
                'status' =>'6',
                'updated_by' => Auth::user()->username,
                'updated_at' => date('Y-m-d H:i:s'),
            ]
        );

        DB::table('debit_note_hdr')
        ->whereIn('dn_number',$listInvoice)
        ->update(
            [   
                'status' =>'6',
                'updated_by' => Auth::user()->username,
                'updated_at' => date('Y-m-d H:i:s'),
            ]
        );
    }

}
