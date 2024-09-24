<?php

namespace App\Http\Controllers\Accounting;

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

/*
catatan:
- kalau sudah di posting (POSTED) itu sudah masuk ke tabel kas_det dan kas_hdr
kalau
Delete : dihapus di tabel ap_invoice, ap_invoice_detail,kas_det,kas_hdr,approval_history tidak bisa di kembalikan lagi
Edit: akan edit tabel ap_invoice, ap_invoice_detail,kas_det,kas_hdr
Edit: kalau sudah di approved maka semua approved akan dihapus di tabel approval_history jadi harus approved lagi

*/

class AssetController extends Controller
{
    private $title;
    private $moduleCode;
 

    public function __construct()
    {
        $this->title = "Assets";
        $this->moduleCode = "ASET";

        $this->nilaiPpn = DB::table('attributes')
        ->where('attr_id','mainppn')
        ->value('attr_value');

        $this->nilaiPph23 = DB::table('attributes')
        ->where('attr_id','mainpph23')
        ->value('attr_value');

        $this->nilaiPph21 = DB::table('attributes')
        ->where('attr_id','mainpph21')
        ->value('attr_value');

        $this->nilaiPph42 = DB::table('attributes')
        ->where('attr_id','mainpph42')
        ->value('attr_value');

        $lockDate1 = DB::table('application_lock')
        ->where('code_key',$this->moduleCode)
        ->where('status','1')
        ->value('lock_date');

        // $todayDate = date('d-m-Y');
        // $lockDateHere = $lockDate1 ? $lockDate1 : '2023-01-01' ;
        // $lockDateAt = date('d-m-Y', strtotime("+1 day", strtotime($lockDateHere)));

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
            ['data'=> 'action', 'name'=> 'action','title'=>'action', 'orderable'=> false, 'searchable'=> false],
            ['data'=> 'asset_number', 'name'=> 'asset_number','title'=>'No Asset'],
            ['data'=> 'asset_desc', 'name'=> 'asset_desc','title'=>'Nama Asset'],
            ['data'=> 'qty', 'name'=> 'qty','title'=>'QTY'],
            ['data'=> 'buying_price', 'name'=> 'buying_price','title'=>'Harga Beli'],
            ['data'=> 'nilai_penyusutan', 'name'=> 'nilai_penyusutan','title'=>'Penyusutan'],
            ['data'=> 'masa_manfaat', 'name'=> 'masa_manfaat','title'=>'Masa Manfaat'],
            ['data'=> 'umur', 'name'=> 'umur','title'=>'Umur'],
            ['data'=> 'nilai_buku', 'name'=> 'nilai_buku','title'=>'Nilai Buku']
        ];
        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $data['title'] = "List $this->title";
        
        $data['coas'] = DB::table('accounts')
        ->where ('status','=','1')
        ->orderBy('account')
        ->get();

        $data['kolom'] = $this->getTableColoumn();        

        // $data['status'] = ['1'=>'DRAFT','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'CLOSED','7'=>'PAID'];
        $data['status'] = ['1'=>'DRAFT','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','6'=>'PAID'];

        $data['lockDate'] = $this->lockDateIndex;
            
        return view("accounting.asset.index",$data);
    }

    // public function getLastCode($key)
    public function getLastCode($key)
    {
        $key = 'ASET';
        $getCurrentYear = date('Y');
        $getCurrentMonth = date('m');
        // ASET-ASN-24-IX-00001
        $basicCode = "$key-ASN-$getCurrentYear";
        $getResetRule = 'YEAR';

        $getResetRule = DB::table('master_code')
        ->where('code_key',$key)
        ->value('reset_by');

        if($getResetRule == 'YEAR'){
            $getLastNumber = DB::table('assets')
            ->where('asset_number','like',$basicCode.'%')
            ->where('status','<>','5')
            ->orderBy('id','desc')
            ->first();
        }else{
            $getLastNumber = DB::table('assets')
            ->where('status','<>','5')
            ->orderBy('id','desc')
            ->first();
        }       

        if ($getLastNumber){
            $getYear = explode('-',$getLastNumber->asset_number)[2];
            $getLastCode = explode('-',$getLastNumber->asset_number)[4];
            $newCode = ($getLastCode*1)+1;
        }else{
            $getYear = $getCurrentYear;
            $newCode = 1;
        }

        $newCode = str_pad($newCode,4,"0",STR_PAD_LEFT);
        $months = ['I', 'II', 'III','IV','V', 'VI', 'VII', 'VIII','IX','X','XI','XII'];
        $month = $months[$getCurrentMonth-1];
        $year = $getCurrentYear;
        $code="$key-ASN-$year-$month-$newCode";
        
        return $code;
    }

    public function getListAp(Request $request)
    {
        $coa= $request->value;      
        $output="";

        // $data= DB::table("ap_invoice_det") 
        // ->leftJoin('ap_invoice','ap_invoice_det.ap_number','ap_invoice.ap_number')
        // ->where("ap_invoice_det.account",$coa)
        // ->whereIn("ap_invoice.status",["3","6"])
        // ->orderBy("ap_invoice_det.id")
        // ->select("ap_invoice_det.id","ap_invoice_det.ap_number","description")
        // ->get();   

        $assetEdit = $request->assetName;
        $assetList = db::table('assets')
        ->where('asset_name','!=',$assetEdit)
        ->select('asset_name')
        ->pluck('asset_name');
        
        $data= DB::table("ap_invoice_det") 
        ->leftJoin('ap_invoice','ap_invoice_det.ap_number','ap_invoice.ap_number')
        ->where("ap_invoice_det.account",$coa)
        ->whereIn("ap_invoice.status",["3","6"])
        ->whereNotIn(DB::raw("CONCAT(ap_invoice_det.id,'_',ap_invoice_det.ap_number)"),$assetList)
        ->distinct("ap_invoice_det.ap_number")
        ->get();          

        $output .='<option value=""></option>';            
        foreach ($data as $row){
            // $output .='<option data-id="'.$row->id.'" value="'.$row->ap_number.'">'.$row->ap_number.'</option>';
            $output .='<option value="'.$row->ap_number.'">'.$row->ap_number.'</option>';            
        }        
        
        return $output;
    }

    public function getListAsset(Request $request)
    {
        $apNumber= $request->value;
        $account= $request->account;
        $output="";

        $assetEdit = $request->assetName;
        $assetList = db::table('assets')
        ->where('asset_name','!=',$assetEdit)
        ->select('asset_name')
        ->pluck('asset_name');

        $data= DB::table("ap_invoice_det") 
        ->leftJoin('ap_invoice','ap_invoice_det.ap_number','ap_invoice.ap_number')
        ->leftJoin(db::raw("(select article_code, sum(qty) as qty from receiving_det 
        where rec_number in (select rec_number from ap_invoice_detail where ap_number = '$apNumber')
        and article_code in (select reference from ap_invoice_det where ap_number = '$apNumber' and account='$account')
        group by article_code) as receiving"),'receiving.article_code','=','ap_invoice_det.reference')
        ->where("ap_invoice_det.ap_number",$apNumber)
        ->where("ap_invoice_det.account",$account)
        ->whereNotIn(DB::raw("CONCAT(ap_invoice_det.id,'_',ap_invoice_det.ap_number)"),$assetList)
        ->select('ap_invoice_det.*','ap_invoice.ap_date','ap_invoice.supplier_id','ap_invoice.inv_number','receiving.qty')
        ->orderBy("id")
        ->get();          

        // dd($data);

        $output .='<option value=""></option>';            
        foreach ($data as $row){
            $output .='<option 
            data-id="'.$row->id.'" 
            data-ap-number="'.$row->ap_number.'" 
            data-account="'.$row->account.'" 
            data-value="'.$row->debit.'" 
            data-ap-date="'.$row->ap_date.'" 
            data-supplier="'.$row->supplier_id.'" 
            data-dept="'.$row->cost_center.'" 
            data-inv-number="'.$row->inv_number.'" 
            data-asset-description="'.$row->description.'" 
            data-qty="'.number_format($row->qty).'" 
            value="'.$row->id.'_'.$row->ap_number.'">'.$row->description.' | '.number_format($row->debit).'</option>';
            // value="'.$row->id.$row->ap_number.'">'.$row->description.' | '.number_format($row->debit).'</option>';
        }        

        // dd($output);
        
        return $output;
    }

    public function getAkunMapping(Request $request)
    {
        $akunTetap = $request->value;        
        $data= DB::table("mapping_akun") 
        ->where("akun_tetap",$akunTetap)
        ->get();          

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

        $data['depts'] = DB::table('depts')
        ->orderBy('name')
        ->get();

        $data['accounts'] = DB::table('accounts')
        ->where('account','>=','1200.11')
        ->where('account','<=','1200.18')
        ->where('acc_header','!=','HEADER')
        ->orderBy('account')
        ->get();

        $data['accountPenyusutan'] = DB::table('accounts')
        // ->where('account','>=','1200.11')
        // ->where('account','<=','1200.18')
        ->where('acc_header','!=','HEADER')
        ->orderBy('account')
        ->get();

        $data['kelompoks'] = DB::table('kelompok')
        ->orderBy('kode')
        ->get();
        
        return view("accounting.asset.create",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $assetCoa  = $request->assetCoa;
        $voucherId = $request->voucherId;
        $voucherNumber = $request->voucherNumber;
        $assetName = $request->assetName;
        $assetDescription = $request->assetDescription;
        $tanggalPembelian = $request->tanggalPembelian ? implode("-",array_reverse(explode("-",$request->tanggalPembelian))): null;
        $hargaBeli = is_null($request->hargaBeli) ? 0 : preg_replace('/[^0-9.]+/', '', $request->hargaBeli);
        $qtyBeli = is_null($request->qtyBeli) ? 0 : preg_replace('/[^0-9.]+/', '', $request->qtyBeli);
        $statusBeli = $request->statusBeli;
        $supplier = $request->supplier;
        $departement = $request->departement;
        $invoiceNumber = $request->invoiceNumber;
        $akunAssetTetap = $request->akunAssetTetap;
        $penyusutan = $request->penyusutan == 'on' ? '1' : '0';
        $akunAkumulasiPenyusutan = $request->akunAkumulasiPenyusutan;
        $akunPenyusutan = $request->akunPenyusutan;
        $kelompokPenyusutan = $request->kelompokPenyusutan;
        $nilaiPenyusutanPerTahun = $request->nilaiPenyusutanPerTahun;
        $masaManfaat = $request->masaManfaat;
        $invoiceDate = $request->invoiceDate ? implode("-",array_reverse(explode("-",$request->invoiceDate))): null;
        $lastDate = $request->lastDate ? implode("-",array_reverse(explode("-",$request->lastDate))):null;
        $metodePenyusutan = $request->metodePenyusutan;
        $akumulasiPenyusutan = is_null($request->akumulasiPenyusutan) ? 0 : preg_replace('/[^0-9.]+/', '', $request->akumulasiPenyusutan);
        $note ="";
        $assetNumber="";
        
        $status = '1';
                
        // $status = ['1'=>'DRAFT','2'=>'','3'=>'','4'=>'','5'=>'','6'=>'','6'=>'']
        
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
            // 'iunique' => "Invoice Number : $invoiceNumber on PO: $poNumber has already exist",
        ];
        
        // Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) use ($poNumber) {
        //     $query = DB::table($parameters[0]);
        //     $column = $query->getGrammar()->wrap($parameters[1]);
        //     $column2 = $query->getGrammar()->wrap($parameters[2]);
        //     return !$query->whereRaw("lower({$column}) = lower(?)", [$value])
        //                   ->whereRaw("lower({$column2}) = lower(?)", [$poNumber])->count();
        // });

        $rule = [
            // 'poNumberDet'  => 'required',
            // 'invoiceNumber'=>'required|iunique:ap_invoice,inv_number,po_number',
            // 'doDate'  => 'required',
        ];

        $this->validate($request,$rule,$messages);

        $assetNumber = $this->getLastCode($this->moduleCode);
        DB::beginTransaction();
        try {
                $rowAffected = DB::table('assets')->insert([
                    'asset_number' => $assetNumber,
                    'asset_name' => $assetName,
                    'asset_desc' => $assetDescription,
                    'coa_number' => $assetCoa,
                    'voucher_id' => $voucherId,
                    'voucher_number' => $voucherNumber,
                    'buying_date' => $tanggalPembelian,
                    'buying_price' => $hargaBeli,
                    'qty' => $qtyBeli,
                    'status_beli' => $statusBeli,
                    'supplier' => $supplier,
                    'departement' => $departement,
                    'invoice_number' => $invoiceNumber,
                    'akun_aset_tetap' => $akunAssetTetap,
                    'penyusutan' => $penyusutan,
                    'akun_akumulasi_penyusutan' => $akunAkumulasiPenyusutan,
                    'akun_penyusutan' => $akunPenyusutan,
                    'kelompok_penyusutan' => $kelompokPenyusutan,
                    'nilai_penyusutan' => $nilaiPenyusutanPerTahun,
                    'masa_manfaat' => $masaManfaat,
                    'tanggal_awal_penyusutan' => $invoiceDate,
                    'tanggal_akhir_penyusutan' => $lastDate,
                    'metode_penyusutan' => $metodePenyusutan,
                    'akumulai_penyusutan' => $akumulasiPenyusutan,
                    'status' => $status,
                    'note' => $note,
                    'created_by' => Auth::user()->username,
                    'updated_by' => Auth::user()->username,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                    
        
                if($rowAffected && $penyusutan == '1'){
                    $simulasiManfaat = [];
                    $tanggalAsset = $invoiceDate;
                    $aNilaiAsset = $hargaBeli;
                    $aPenyusutan = $akumulasiPenyusutan;
                    $nilaiBuku = $hargaBeli;

                    for($i=0;$i<$masaManfaat+1;$i++){
                        $futureDate = $i != 0 ? date('Y-m-d', strtotime('+1 year', strtotime($futureDate))) : $tanggalAsset;
                        $aPenyusutan = ($i != 0) ? $akumulasiPenyusutan : 0;
                        $aNilaiAsset =  ($i > 1)  ? ($aNilaiAsset - $akumulasiPenyusutan) : $aNilaiAsset;
                        $nilaiBuku =  $nilaiBuku - $aPenyusutan;
                        $simulasiManfaat[] = [
                            'asset_number' => $assetNumber,
                            'tanggal_asset' => $futureDate,
                            'nilai_asset' => $aNilaiAsset,
                            'penyusutan' => $aPenyusutan,
                            'nilai_buku' =>  $nilaiBuku,
                            'created_by' => Auth::user()->username,
                            'updated_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                    }
                    DB::table('asset_detail')->insert($simulasiManfaat);
                }
               
                DB::commit();

                $title ='Save Invoice';
                $alert  ="success";
                $message  = "$title $assetNumber is successfully saved";

                \LogActivity::addToLog($title,"username: $username Status $message");
                // return response()->json(array('status' => 1, 'message' => $message,'alert'=>$alert,'assetNumber'=>$assetNumber));
                return redirect()->back()->with(array('title' => $title, 'message' => $message,'alert'=>$alert,'assetNumber'=>$assetNumber));
                // return redirect()->route('asset.create')->with(array('title' => $title, 'message' => $message,'alert'=>$alert));
                // return redirect()->back()->with($data);

        } catch (Exception $e) {
            DB::rollBack();
            $title ='Save Invoice';
            $alert  ="warning";
            $message  = "*Invoice $assetNumber is failed to save";
            \LogActivity::addToLog($title,"username: $username Status $message");
            // return response()->json(array('status' => 0, 'message' => $message,'alert'=>$alert,'assetNumber'=>$assetNumber));
            return redirect()->back()->with(array('title' => $title, 'message' => $message,'alert'=>$alert,'assetNumber'=>$assetNumber));
        }
        
    }

    public function show(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;

        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";

        $data['id']=$id;

        $data['header'] = DB::table('assets')
        ->leftJoin('depts','depts.code','assets.departement')
        ->select('assets.*'
        ,'depts.name as dept_name'
        ,db::raw("(select nama from third_party where kode = supplier) as supplier_name")
        ,db::raw("(select concat(account,' - ',description) from accounts where account = akun_aset_tetap) as akun_aset_tetap_name")
        ,db::raw("(select concat(account,' - ',description) from accounts where account = akun_akumulasi_penyusutan) as akun_akumulasi_penyusutan_name")
        )
        ->where('assets.id',$id)
        ->get()->first(); 

        $data['details'] = DB::table('asset_detail')
        ->where('asset_number',$data['header']->asset_number)
        ->get(); 
                
        // $data['status'] = ['1'=>'DRAFT','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'PAID'];
        $status = ['DRAFT','','','','','',''];
        $data['status'] = $status[$data['header']->status-1];

        return view("accounting.asset.show",$data);
        
    }

    public function edit(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;

        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['id']=$id;

        $data['header'] = DB::table('assets')
        ->leftJoin('depts','depts.code','assets.departement')
        ->select('assets.*'
        ,'depts.name as dept_name'
        ,db::raw("(select nama from third_party where kode = supplier) as supplier_name")
        ,db::raw("(select concat(account,' - ',description) from accounts where account = akun_aset_tetap) as akun_aset_tetap_name")
        ,db::raw("(select concat(account,' - ',description) from accounts where account = akun_akumulasi_penyusutan) as akun_akumulasi_penyusutan_name")
        )
        ->where('assets.id',$id)
        ->get()->first(); 

        $data['details'] = DB::table('asset_detail')
        ->where('asset_number',$data['header']->asset_number)
        ->get(); 
                
        // $data['status'] = ['1'=>'DRAFT','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'PAID'];
        $status = ['DRAFT','','','','','',''];
        $data['status'] = $status[$data['header']->status-1];


        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

        $data['depts'] = DB::table('depts')
        ->orderBy('name')
        ->get();

        $data['accounts'] = DB::table('accounts')
        ->where('account','>=','1200.11')
        ->where('account','<=','1200.18')
        ->where('acc_header','!=','HEADER')
        ->orderBy('account')
        ->get();

        $data['accountPenyusutan'] = DB::table('accounts')
        // ->where('account','>=','1200.11')
        // ->where('account','<=','1200.18')
        ->where('acc_header','!=','HEADER')
        ->orderBy('account')
        ->get();

        $data['kelompoks'] = DB::table('kelompok')
        ->orderBy('kode')
        ->get();
        
        return view("accounting.asset.edit",$data);
        
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);

        $assetCoa  = $request->assetCoa;
        $voucherId = $request->voucherId;
        $voucherNumber = $request->voucherNumber;
        $assetName = $request->assetName;
        $assetDescription = $request->assetDescription;
        $tanggalPembelian = $request->tanggalPembelian ? implode("-",array_reverse(explode("-",$request->tanggalPembelian))): null;
        $hargaBeli = is_null($request->hargaBeli) ? 0 : preg_replace('/[^0-9.]+/', '', $request->hargaBeli);
        $qtyBeli = is_null($request->qtyBeli) ? 0 : preg_replace('/[^0-9.]+/', '', $request->qtyBeli);
        $statusBeli = $request->statusBeli;
        $supplier = $request->supplier;
        $departement = $request->departement;
        $invoiceNumber = $request->invoiceNumber;
        $akunAssetTetap = $request->akunAssetTetap;
        $penyusutan = $request->penyusutan == 'on' ? '1' : '0';
        $akunAkumulasiPenyusutan = $request->akunAkumulasiPenyusutan;
        $akunPenyusutan = $request->akunPenyusutan;
        $kelompokPenyusutan = $request->kelompokPenyusutan;
        $nilaiPenyusutanPerTahun = $request->nilaiPenyusutanPerTahun;
        $masaManfaat = $request->masaManfaat;
        $invoiceDate = $request->invoiceDate ? implode("-",array_reverse(explode("-",$request->invoiceDate))): null;
        $lastDate = $request->lastDate ? implode("-",array_reverse(explode("-",$request->lastDate))):null;
        $metodePenyusutan = $request->metodePenyusutan;
        $akumulasiPenyusutan = is_null($request->akumulasiPenyusutan) ? 0 : preg_replace('/[^0-9.]+/', '', $request->akumulasiPenyusutan);
        $note ="";
        $assetNumber="";
        
        // $data['status'] = ['1'=>'DRAFT','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'CLOSED','6'=>'PAID'];
        
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
            // 'iunique' => "Invoice Number : $invoiceNumber on PO: $poNumber has already exist",
        ];
        
        // Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) use ($poNumber) {
        //     $query = DB::table($parameters[0]);
        //     $column = $query->getGrammar()->wrap($parameters[1]);
        //     $column2 = $query->getGrammar()->wrap($parameters[2]);
        //     return !$query->whereRaw("lower({$column}) = lower(?)", [$value])
        //                   ->whereRaw("lower({$column2}) = lower(?)", [$poNumber])->count();
        // });

        $rule = [
            // 'poNumberDet'  => 'required',
            // 'invoiceNumber'=>'required|iunique:ap_invoice,inv_number,po_number',
            // 'doDate'  => 'required',
        ];

        $this->validate($request,$rule,$messages);
        
        DB::beginTransaction();
        try {
                $rowAffected=DB::table('assets')
                ->where('id',$id)
                ->update(
                    [
                        'asset_name' => $assetName,
                        'asset_desc' => $assetDescription,
                        'coa_number' => $assetCoa,
                        'voucher_id' => $voucherId,
                        'voucher_number' => $voucherNumber,
                        'buying_date' => $tanggalPembelian,
                        'buying_price' => $hargaBeli,
                        'qty' => $qtyBeli,
                        'status_beli' => $statusBeli,
                        'supplier' => $supplier,
                        'departement' => $departement,
                        'invoice_number' => $invoiceNumber,
                        'akun_aset_tetap' => $akunAssetTetap,
                        'penyusutan' => $penyusutan,
                        'akun_akumulasi_penyusutan' => $akunAkumulasiPenyusutan,
                        'akun_penyusutan' => $akunPenyusutan,
                        'kelompok_penyusutan' => $kelompokPenyusutan,
                        'nilai_penyusutan' => $nilaiPenyusutanPerTahun,
                        'masa_manfaat' => $masaManfaat,
                        'tanggal_awal_penyusutan' => $invoiceDate,
                        'tanggal_akhir_penyusutan' => $lastDate,
                        'metode_penyusutan' => $metodePenyusutan,
                        'akumulai_penyusutan' => $akumulasiPenyusutan,
                    ]
                );

                $assetNumber=db::table('assets')->where('id',$id)->value('asset_number');

                if($rowAffected && $penyusutan == '1'){
                    $simulasiManfaat = [];
                    $tanggalAsset = $invoiceDate;
                    $aNilaiAsset = $hargaBeli;
                    $aPenyusutan = $akumulasiPenyusutan;
                    $nilaiBuku = $hargaBeli;

                    DB::table('asset_detail')->where('asset_number',$assetNumber)->delete();

                    for($i=0;$i<$masaManfaat+1;$i++){
                        $futureDate = $i != 0 ? date('Y-m-d', strtotime('+1 year', strtotime($futureDate))) : $tanggalAsset;
                        $aPenyusutan = ($i != 0) ? $akumulasiPenyusutan : 0;
                        $aNilaiAsset =  ($i > 1)  ? ($aNilaiAsset - $akumulasiPenyusutan) : $aNilaiAsset;
                        $nilaiBuku =  $nilaiBuku - $aPenyusutan;
                        $simulasiManfaat[] = [
                            'asset_number' => $assetNumber,
                            'tanggal_asset' => $futureDate,
                            'nilai_asset' => $aNilaiAsset,
                            'penyusutan' => $aPenyusutan,
                            'nilai_buku' =>  $nilaiBuku,
                            'created_by' => Auth::user()->username,
                            'updated_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                    }
                    DB::table('asset_detail')->insert($simulasiManfaat);
                }
                                       
                DB::commit();

                $title ='Update Asset';
                $alert  ="success";
                $message  = "$title $assetNumber is successfully updated";

                $data['title'] = $title;
                $data['message'] = $message;
                $data['alert'] = $alert;

                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with(array('title' => $title, 'message' => $message,'alert'=>$alert,'assetNumber'=>$assetNumber));

        } catch (Exception $e) {
            DB::rollBack();
            $title ='Update Asset';
            $alert  ="warning";
            $message  = "Invoice $assetNumber is failed to update";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(array('title' => $title, 'message' => $message,'alert'=>$alert,'assetNumber'=>$assetNumber));
        }
        
    }

    public function destroy(Request $request)
    {
        $statusCode = ['DRAFT','','','','',''];

        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);
        
        $data= DB::table('assets')
        ->where('id',$id)
        ->get()->first();

        $assetNumber = $data->asset_number;
        
        $rowAffected = DB::table('assets')
        ->where('asset_number',$assetNumber)
        ->delete();

        if ($rowAffected){

            DB::table('asset_detail')
            ->where('asset_number',$assetNumber)
            ->delete();

            DB::commit();
            $title ='Delete asset';
            $alert  ="success";
            $message  = "$assetNumber is successfully deleted";
            \LogActivity::addToLog('Asset delete ',"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);  

        }else{
            DB::rollBack();
            $title ='Delete asset';
            $alert  ="warning";
            $message  = "$assetNumber is failed to delete";
            \LogActivity::addToLog('Asset delete ',"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);  
        }

    }

    public function list(Request $request)
    {

        // $data['status'] = ['1'=>'DRAFT','2'=>'','3'=>'','4'=>'','5'=>'','6'=>''];
        $searchNoAsset = $request->searchNoAsset;
        $searchName = $request->searchName;
               
        // if ($apDate){
        //     $date = explode("to",$apDate);
        //     if(count($date)>1){
        //         $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
        //         $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
        //     }else{
        //         $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
        //         $toDate = $fromDate; 
        //     }
        // }

        $data = DB::table('assets')
        ->where(function ($query) use ($searchNoAsset,$searchName) {
            $searchNoAsset ? $query->where('asset_number','ilike','%'.$searchNoAsset.'%') : '';
            $searchName ? $query->where('asset_desc','ilike','%'.$searchName.'%') : '';
            // $apDate ? $query->whereBetween(DB::raw("to_date(ap_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
            
        })
        ->where('assets.status','1')
        ->select(
            'assets.*'
            ,DB::raw("EXTRACT(YEAR FROM age(buying_date)) as umur")
            ,DB::raw("(SELECT nilai_buku FROM asset_detail z where z.asset_number = assets.asset_number and  date_part('year', tanggal_asset) = date_part('year', CURRENT_DATE)) as nilai_buku")
        )
        ->orderBy('assets.id')
        ->get(); 

        $bisaEdit = Auth::user()->can('ap-edit');
        $bisaDelete = Auth::user()->can('ap-delete');
        
        return Datatables::of($data)
        ->addColumn('action', function ($data)  use ($bisaEdit,$bisaDelete){
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';

            if ($data->status != '5'){
                if ($bisaEdit) {
                    $buttons .= '<a href="'. route('asset.edit',['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    <span>'.__("Edit") .'</span>
                                </a>';
                }
            }

            $buttons .= '<a href="'. route('asset.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                            <i data-feather="list"></i>
                            <span>'. __("Detail") .'</span>
                        </a>';               

            // $buttons .=         '<a href="'. route('accountPayable.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
            //                         <i data-feather="printer"></i>
            //                         <span>'. __("Print") .'</span>
            //                     </a>';
            
            // if (($data->status != '7')){
                    if ($bisaDelete) {
                    $buttons .= "<a href='javascript:;'
                                    id='deleteButton'
                                    class='dropdown-item'
                                    data-toggle='modal'
                                    data-target='#smallModal'
                                    data-href='". route("asset.destroy", ["id"=>Crypt::encryptString($data->id)]) ."'>
                                    <i data-feather='trash-2' class='feather-14-red'></i>
                                    Delete
                                </a>";
                    }
            // }

            $buttons .=     '</div>
                        </div>';

            return $buttons;
            })
        
        ->addColumn('nilai_penyusutan', function ($data) {
            return $data->nilai_penyusutan ? $data->nilai_penyusutan.'%' : '';
        })
        ->addColumn('masa_manfaat', function ($data) {
            return $data->masa_manfaat ? $data->masa_manfaat.' Tahun' : '';
        })

        ->addColumn('nilai_buku', function ($data) {
            return $data->nilai_buku ? $data->nilai_buku : $data->buying_price;
        })

        ->rawColumns(['action','status','nilai_penyusutan','masa_manfaat','nilai_buku'])
        ->make(true);
    }

    public function print(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $data['title'] ='Invoice Supplier';

        $apNumber = DB::table('ap_invoice')->where('id',$id)->value('ap_number');

        $apInvoice = DB::table('ap_invoice')
        ->leftJoin('third_party','third_party.kode','ap_invoice.supplier_id')
        ->select(
            'ap_invoice.*'
            ,DB::raw("(select STRING_AGG ( a.rec_number,',' ORDER BY a.id) as list_rec from ap_invoice_detail a where ap_number = ap_invoice.ap_number) as list_rec")
            ,'third_party.nama as supplier_name'
        )
        ->where('ap_invoice.id',$id)->first();       

        $data['header']=DB::table('kas_hdr')
        ->select('kas_hdr.*'
        ,'description as receive_name'
        )
        ->where('kas_hdr.description',$apNumber)
        ->first();

        $voucherNumber=$data['header']->voucher_number;
       
        $data['details']=DB::table('kas_det')
        ->leftJoin('kas_hdr','kas_hdr.voucher_number','kas_det.voucher_number')
        ->leftJoin('ap_invoice','ap_invoice.ap_number','kas_hdr.description')
        ->leftJoin('accounts','accounts.account','kas_det.account')
        ->select('kas_det.*'
        ,'ap_invoice.ap_number'
        ,'ap_invoice.inv_number'
        ,'accounts.description as account_name'
        ,DB::raw("(select STRING_AGG ( a.rec_number,', ' ORDER BY a.id) as list_rec from ap_invoice_detail a where ap_number = ap_invoice.ap_number) as list_rec")
        )
        ->where('kas_det.voucher_number',$voucherNumber)
        ->orderBy('id')
        ->get();

        $jumlahBaris= 0;
        foreach($data['details'] as $val){
            $jumlahBaris += count(explode(",",$val->list_rec));
        }

        // dd($jumlahBaris);
        $ukuranKertas = "A4";
        if ($jumlahBaris < 56){
            $ukuranKertas = "A4";
        }else if(($jumlahBaris > 56) && ($jumlahBaris < 140)){
            $ukuranKertas = "A42page";
        }else if (($jumlahBaris > 140) && ($jumlahBaris < 200) ){
            $ukuranKertas = "A43page";
        }else if ($jumlahBaris > 200 ){
            $ukuranKertas = "A44page";
        }

        /*20/8/2024 - semua standarnya jadi besar (A4) aja*/
        if (($jumlahBaris < 18) && count($data['details']) < 7){
            $ukuranKertas = "A4A5";
        }

        // if (count($data['details']) < 7){
        //     if ($jumlahBaris < 18){
        //         $ukuranKertas = "A4A5";
        //     }else{
        //         $ukuranKertas = "A4";
        //     }
        // }else{
        //     if ($jumlahBaris > 56){
        //         $ukuranKertas = "A42page";
        //     }

        //     if ($jumlahBaris > 70){
        //         $ukuranKertas = "A43page";
        //     }
        //     // else{
        //     //     $ukuranKertas = "A4";
        //     // }
        // }

        // dd($ukuranKertas);

        // dd($jumlahBaris);

        $data['ukuranKertas'] = $ukuranKertas;
        $data['jumlahBaris'] = $jumlahBaris;
        $data['total']=DB::table('kas_det')
        ->select(DB::raw("sum(credit) as total_credit"),DB::raw("sum(debit) as total_debit"))
        ->where('voucher_number',$voucherNumber)
        ->first();

        $data['costCenter']=DB::table('kas_det')
        ->leftJoin('depts','depts.code','kas_det.cost_center')
        ->where('voucher_number',$voucherNumber)
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

        $data['approval4']=DB::table('approval_history')
        ->leftJoin('users','users.username','approval_history.username')
        ->where('module_code',$this->moduleCode)
        ->where('module_number',$apNumber)
        ->where('approval_order',4)
        ->first();

        $data['apNumber'] =  $apInvoice->ap_number;
        $data['invoiceNumber'] = $apInvoice->inv_number;
        $data['supplierName'] = $apInvoice->supplier_name;
        $data['nomorLpb'] = $apInvoice->list_rec;
        // $data['apDate'] = $apInvoice->ap_date ? $apInvoice->inv_date : '';
        // $data['apDate'] = $apInvoice->ap_date;
        $data['invDate'] = $apInvoice->inv_date;
        $data['noPo'] = $apInvoice->po_number;

        return view('accounting.accountPayable.print',$data);

        // view()->share($data);

        // $pdf = PDF::loadView('accounting.accountPayable.print')->setPaper([0, 0, 595.28, 841.89], 'portrait');
        // return $pdf->stream("ap_$apNumber.pdf");

    }

}
