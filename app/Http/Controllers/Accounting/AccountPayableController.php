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

class AccountPayableController extends Controller
{
    private $title;
    private $moduleCode;
    private $voucherCode;
    private $nilaiPpn;
    private $nilaiPph23;
    private $nilaiPph21;
    private $nilaiPph42;

    public function __construct()
    {
        $this->title = "Invoice Supplier";
        $this->moduleCode = "AP";
        $this->voucherCode = "APV";

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


    }

    public function getTableColoumn()
    {
        $kolom=
        [
            ['data'=> 'action', 'name'=> 'action','title'=>'action', 'orderable'=> false, 'searchable'=> false],
            ['data'=> 'ap_number', 'name'=> 'ap_number','title'=>'AP Number'],
            ['data'=> 'ap_date', 'name'=> 'ap_date','title'=>'AP Date'],
            ['data'=> 'ap_date_2', 'name'=> 'ap_date_2','title'=>'AP Date','visible'=>false],
            ['data'=> 'period', 'name'=> 'period','title'=>'Period'],
            ['data'=> 'status', 'name'=> 'status','title'=>'Status'],
            ['data'=> 'voucher_date', 'name'=> 'voucher_date','title'=>'Paid Date'],
            ['data'=> 'voucher_number', 'name'=> 'voucher_number','title'=>'Voucher Number'],
            ['data'=> 'voucher_amount', 'name'=> 'voucher_amount','title'=>'Amount Paid'],
            ['data'=> 'balance', 'name'=> 'balance','title'=>'Balance'],
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
            ['data'=> 'pph21', 'name'=> 'pph21','title'=>'PPH21'],
            ['data'=> 'pph23', 'name'=> 'pph23','title'=>'PPH23'],
            ['data'=> 'pph42', 'name'=> 'pph42','title'=>'PPH4(2)'],
            ['data'=> 'total_discount', 'name'=> 'total_discount','title'=>'Discount'],
            ['data'=> 'grand_total', 'name'=> 'grand_total','title'=>'Grand Total'],
            ['data'=> 'note', 'name'=> 'note','title'=>'Note'],
            ['data'=> 'approval_by','name'=> 'approval_by','title'=>'Approved By'],
            ['data'=> 'approval_at','name'=> 'approval_at','title'=>'Approved At'],
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

        $data['kolom'] = $this->getTableColoumn();        

        // $data['status'] = ['1'=>'DRAFT','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'CLOSED','7'=>'PAID'];
        $data['status'] = ['1'=>'DRAFT','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','6'=>'PAID'];
            
        return view("accounting.accountPayable.index",$data);
    }

    // public function getLastCode($key)
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
            // $month = $months[date('n')-1];
            $month = $months[$period-1];
            $year = date('Y');
            // AP-ASN-23-X-0001
            $code="$key-ASN-$year-$month-$newCode";
            // $code="$key-ASN/$year/$month/$newCode";
        */

        /*
            new ways
            Jadi dilihat nomor terakhir bukan dari tabel master_code lagi
            tapi dari nomor terakhir transaksi
                             1          2          3         4         5         6
            $statusCode = ['DRAFT','VALIDATED','APPROVED','POSTED','CANCELED','PAID'];
            "AP-ASN-2024-I-0001"
        */
        
        $getCurrentYear = date('Y');
        $inputYear = $year;
        // $basicCode = "$key-ASN-$inputYear";
        $basicCode = "______-$inputYear";

        $getResetRule = DB::table('master_code')
        ->where('code_key',$key)
        ->value('reset_by');

        if($getResetRule == 'YEAR'){
            $getLastNumber = DB::table('ap_invoice')
            ->where('ap_number','like',$basicCode.'%')
            ->where('status','<>','5')
            ->orderBy('id','desc')
            ->first();
        }else{
            $getLastNumber = DB::table('ap_invoice')
            ->where('status','<>','5')
            ->orderBy('id','desc')
            ->first();
        }       

        if ($getLastNumber){
            $getYear = explode('-',$getLastNumber->ap_number)[2];
            $getLastCode = explode('-',$getLastNumber->ap_number)[4];
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
            // ->whereIn('po_number', function($query) use ($supp) {
            //     $listRec = DB::table('ap_invoice_detail')
            //     ->whereIn('ap_number',DB::table('ap_invoice')
            //     // ->where('ap_invoice.po_number','purchase_order_hdr.po_number')
            //     ->where('status','4')
            //     ->pluck('ap_number')->toArray())
            //     ->pluck('rec_number')->toArray();

            //     $query->select('po_number')
            //     ->from('receiving_hdr') 
            //     ->whereNotIn('rec_number',$listRec);

            // })
            ->whereIn("status",['3','6'])
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
            ->whereIn("status",['3','6'])
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
            // ->leftJoin('purchase_order_hdr','purchase_order_hdr.po_number','receiving_hdr.po_number')
            ->where("receiving_hdr.po_number",$poNumber)
            ->where("receiving_hdr.status","4")
            ->whereNotIn(DB::raw("rec_number"), function($query) use ($poNumber,$apNumber) {
                $query->select("rec_number")
                ->from('ap_invoice_detail')
                /*walaupun udah posting masih bisa di edit*/
                // ->whereIn('ap_number',DB::table('ap_invoice')
                //                         ->whereIn('status',['4'])
                //                         ->pluck('ap_number')->toArray())
                ->orWhere('ap_number','<>',$apNumber);
            })
            ->orderBy("rec_number")
            ->select("rec_number","do_date","do_number"
            // ,db::raw("(select sum(qty*price) from purchase_order_det where po_number = purchase_order_hdr.po_number) as sub_total")
            // ,db::raw("(select sum((qty*price)*purchase_order_hdr.discount/100) from purchase_order_det where po_number = purchase_order_hdr.po_number) as discount")
            // ,db::raw("(select sum((qty*price)-((qty*price)*purchase_order_hdr.discount/100)) from purchase_order_det where po_number = purchase_order_hdr.po_number) as dpp")
            // ,db::raw("(select sum(((qty*price)-((qty*price)*purchase_order_hdr.discount/100))*purchase_order_hdr.ppn/100) from purchase_order_det where po_number = purchase_order_hdr.po_number) as ppn")
            // ,db::raw("(select sum((qty*price)-((qty*price)*purchase_order_hdr.discount/100)+(((qty*price)-((qty*price)*purchase_order_hdr.discount/100))*purchase_order_hdr.ppn/100)-(((qty*price)-((qty*price)*purchase_order_hdr.discount/100))*purchase_order_hdr.pph22/100)) from purchase_order_det where po_number = purchase_order_hdr.po_number) as total")
            ,db::raw("(select sum(qty) as sum_qty from receiving_det where rec_number=receiving_hdr.rec_number) as sum_qty"))
            ->get(); 

            // dd($data);

        }else{

            $data= DB::table("receiving_hdr") 
            // ->leftJoin('purchase_order_hdr','purchase_order_hdr.po_number','receiving_hdr.po_number')
            ->where("receiving_hdr.po_number",$poNumber)
            ->where("receiving_hdr.status","4")
            ->whereNotIn(DB::raw("rec_number"), function($query) use ($poNumber) {
                $query->select(DB::raw("rec_number"))
                ->from('ap_invoice_detail');
                // ->where('po_number',$poNumber);
            })
            ->orderBy("rec_number")
            ->select("rec_number","do_date","do_number"
            // ,db::raw("(sum(qty*price) from purchase_order_det where po_number = purchase_order_hdr.po_number) as sub_total")
            // ,db::raw("(select sum((qty*price)*purchase_order_hdr.discount/100) from purchase_order_det where po_number = purchase_order_hdr.po_number) as discount")
            // ,db::raw("(select sum((qty*price)-((qty*price)*purchase_order_hdr.discount/100)) from purchase_order_det where po_number = purchase_order_hdr.po_number) as dpp")
            // ,db::raw("(select sum(((qty*price)-((qty*price)*purchase_order_hdr.discount/100))*purchase_order_hdr.ppn/100) from purchase_order_det where po_number = purchase_order_hdr.po_number) as ppn")
            // ,db::raw("(select sum((qty*price)-((qty*price)*purchase_order_hdr.discount/100)+(((qty*price)-((qty*price)*purchase_order_hdr.discount/100))*purchase_order_hdr.ppn/100)-(((qty*price)-((qty*price)*purchase_order_hdr.discount/100))*purchase_order_hdr.pph22/100)) from purchase_order_det where po_number = purchase_order_hdr.po_number) as total")
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
        }else{
            $details=[];
        }

        $output="";
        foreach ($data as $key=>$row){
            $checked = in_array($row->rec_number, $details) ? 'checked' :'';
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
                            <td>$row->total</td>
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
        $apNumber = $request->apNumber;
        $recNumber = $request->recNumber;
        $arrayRecNumber = explode(",",$recNumber);

        $result = "'" . implode ( "', '", $arrayRecNumber ) . "'";

        $nilaiPPN = $this->nilaiPpn;

        $detailRec = DB::table('receiving_det')
        ->leftJoin('receiving_hdr','receiving_hdr.rec_number','receiving_det.rec_number')
        ->leftJoin('article','article.article_code','receiving_det.article_code')
        ->leftJoin(DB::RAW("(select * from ap_invoice_det where ap_number='$apNumber') as ap"),'ap.reference','receiving_det.article_code')
        // ->leftJoin(DB::RAW("(select * from purchase_order_det where po_number = '$poNumber') AS po"),function($join){
        //     $join->on('po.po_number','=','receiving_hdr.po_number')
        //         ->on('po.article_code','=','receiving_det.article_code');
        // })
        // ->leftJoin('purchase_order_hdr','purchase_order_hdr.po_number','purchase_order_det.po_number')
        // ->leftJoin(DB::RAW("(select * from purchase_order_det where po_number = '$poNumber') AS po"),'po.po_number','receiving_hdr.po_number')
        ->whereIn('receiving_det.rec_number',$arrayRecNumber)
        ->where('receiving_det.qty','>',0)
        ->select('article.article_alternative_code as article'
        ,'receiving_det.article_code'
        ,'article.article_desc as desc'
        ,'receiving_det.uom_rec as uom'
        ,db::raw("sum(receiving_det.qty) as qty")
        // ,'po.price'
        ,'receiving_det.price'
        // ,db::raw("(select dept from purchase_request_hdr where pr_number in (select pr_number from purchase_order_det where po_number = receiving_hdr.po_number ) limit 1) as dept")
        ,db::raw("(select dept from purchase_request_hdr where pr_number in (select pr_number from purchase_order_det where po_number = receiving_hdr.po_number and purchase_order_det.article_code = receiving_det.article_code ) limit 1) as dept")
        // ,'po.ppn'
        // ,'po.pph22'
        // ,'purchase_order_hdr.discount'
        // ,db::raw("sum((qty*po.price)-((qty*po.price)*purchase_order_hdr.discount/100)+(((qty*po.price)-((qty*po.price)*purchase_order_hdr.discount/100))*po.ppn/100)-(((qty*po.price)-((qty*po.price)*purchase_order_hdr.discount/100))*po.pph/100)) as total")
        // )
        // ,db::raw("(sum(receiving_det.qty*po.price)) as total")
        ,db::raw("(sum(receiving_det.qty*receiving_det.price)) as total")
        ,'ap.account as account'
        )
        ->groupBy('article.article_alternative_code')
        ->groupBy('article.article_desc')
        ->groupBy('receiving_det.uom_rec')
        // ->groupBy('po.price')
        ->groupBy('receiving_det.price')
        ->groupBy('receiving_det.article_code')
        // ->groupBy(db::raw("(select dept from purchase_request_hdr where pr_number in (select pr_number from purchase_order_det where po_number = receiving_hdr.po_number ) limit 1)"))
        ->groupBy(db::raw("(select dept from purchase_request_hdr where pr_number in (select pr_number from purchase_order_det where po_number = receiving_hdr.po_number and purchase_order_det.article_code = receiving_det.article_code) limit 1)"))
        ->groupBy('ap.account')
        ->get();

        // dd($detailRec);

        $summaryRec =  DB::select("SELECT z.*
        ,basis_amount1 - (basis_amount1*y.discount/100) as basis_amount
        ,ppn as vat
        ,y.discount as nilai_discount
        ,(((basis_amount1-(basis_amount1*y.discount/100))*y.ppn/100)) as nilai_pajak
        ,(((basis_amount1-(basis_amount1*y.discount/100))*y.pph22/100)) as nilai_pph
        ,pkp as pkp
        ,pph22 as pph22
        ,(basis_amount1*y.discount/100) as discount
        ,(select sum(qty) as qty  from purchase_order_det where po_number = z.po_number) as total_qty_po
        ,(basis_amount1 - (basis_amount1*y.discount/100)) + ((basis_amount1-(basis_amount1*y.discount/100))*y.ppn/100) - ((basis_amount1-(basis_amount1*y.discount/100))*y.pph22/100) total_netto
        ,((select sum(qty*price) as qty  from purchase_order_det where po_number = z.po_number)) as total_amount_po
        ,((select sum(qty*price) as qty  from purchase_order_det where po_number = z.po_number) -(select coalesce(sum(basis_amount),0) from ap_invoice where po_number = z.po_number)) as po_balance
        from 
        (select b.po_number
        ,sum(a.qty) as total_qty_rec
        ,(sum(a.qty*a.price)) as basis_amount1
        -- ,(sum(a.qty*c.price)) as basis_amount1
        from receiving_det a
        left join receiving_hdr b on a.rec_number = b.rec_number 
        -- left join purchase_order_det c on c.po_number = b.po_number and c.article_code = a.article_code 
        -- where a.rec_number in ('REC-ASN/2022/XI/4','REC-ASN/2023/I/1')
        where a.rec_number in ($result)
        --and a.qty > 0
        group by b.po_number) z
        left join purchase_order_hdr y on y.po_number = z.po_number");
        
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
        // ->whereIn('type_code',['po.ppn11','12','14','15','42','44','46','48'])
        ->where('acc_header','!=','HEADER')
        ->get();

        $data['accounts'] = DB::table('accounts')
        // ->whereIn('type_code',['21','22','23','24'])
        ->where('acc_header','!=','HEADER')
        ->get();

        $data['depts'] = $this->lisDept();

        $data['nilaiPPN'] = $this->nilaiPpn;
        $data['nilaiPPH23'] = $this->nilaiPph23;
        $data['nilaiPPH21'] = $this->nilaiPph21;
        $data['nilaiPPH42'] = $this->nilaiPph42;
        
        return view("accounting.accountPayable.create",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $suppCode = $request->supplier;
        $poNumber = $request->poNumber;
        $currency = $request->currency;
        $rate = is_null($request->rate) ? 0 : preg_replace('/[^0-9.]+/', '', $request->rate);
        $invoiceDate= $request->invoiceDate;
        $basisAmount = is_null($request->basisAmount) ? 0 : preg_replace('/[^0-9.]+/', '', $request->basisAmount);
        // $accountBasisA = $request->accountBasisA;
        $otherDeduct = 0;
        $account= $request->account;
        $note=$request->note;
        $apDate= $request->apDate;
        $invoiceNumber=$request->invoiceNumber;
        $taxInvoiceNumber=$request->taxInvoiceNumber;
        $recNumberSave = explode(",",$request->recNumberSave);
        $period=$request->period;
        $accountHutang = $request->accountHutang;
        $details = json_decode($request->details);
        $accountBasisA = ''; //untuk account basis amount akan diganti dengan account masing2 item

        // dd($recNumberSave);

        /* batal pengkodean untuk angka romawi/bulan  jadi nya dari period
        $tanggalReceive = (int)explode('-', $apDate)[0];
        $bulanReceive = (int)explode('-', $apDate)[1];
        
        $getTodayMonth = date('n'); 
        if(($tanggalReceive < 5) && ($getTodayMonth==$bulanReceive)){
            if($bulanReceive == 1){
                $periodNomor = 12;
            }else{
                $periodNomor = $bulanReceive-1;
            }
        }else{
            $periodNomor= $bulanReceive;
        }
        */

        $totalDiscount = is_null($request->totalDiscount) ? 0 : preg_replace('/[^0-9.]+/', '', $request->totalDiscount);
        $grandTotal = is_null($request->grandTotal) ? 0 :  preg_replace('/[^0-9.]+/', '', $request->grandTotal);

        $vat=is_null($request->totalPPN) ? 0 : preg_replace('/[^0-9.]+/', '', $request->totalPPN);
        $pph23 = is_null($request->totalPPH23) ? 0 : preg_replace('/[^0-9.]+/', '', $request->totalPPH23);
        $pph21 = is_null($request->totalPPH21) ? 0 : preg_replace('/[^0-9.]+/', '', $request->totalPPH21);
        $pph42 = is_null($request->totalPPH42) ? 0 : preg_replace('/[^0-9.]+/', '', $request->totalPPH42);

        $typePph ='';        
        if($pph23 > 0 && $typePph==''){
            $typePph='PPH23';
        }

        if($pph21 > 0 && $typePph==''){
            $typePph='PPH21';
        }

        if($pph42 > 0 && $typePph==''){
            $typePph='PPH42';
        }

        $nilaiPph=0;
        if($pph23 > 0 && $nilaiPph ==0){
            $nilaiPph=$pph23;
        }

        if($pph21 > 0 && $nilaiPph ==0){
            $nilaiPph=$pph21;
        }

        if($pph42 > 0 && $nilaiPph ==0){
            $nilaiPph=$pph42;
        }
                
        $accountVat   ='1100.73';
        // $acountTotal  ='2000.11';  
        $acountTotal  =$accountHutang; //ambil dari kode supplier
        $accountPph23 ='2000.14.3';
        $accountPph21 ='2000.14.2';
        $accountPph42 ='2000.14.6';

        $accountPph = '';
        if($pph23 > 0 && $accountPph ==''){
            $accountPph=$accountPph23;
        }

        if($pph21 > 0 && $accountPph ==''){
            $accountPph=$accountPph21;
        }

        if($pph42 > 0 && $accountPph ==''){
            $accountPph=$accountPph42;
        }
                
        $status = '1';
                
        // $status = ['1'=>'DRAFT','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'CLOSED','6'=>'PAID']
        
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

        $inputYear = substr($apDate,-4)*1;
        $inputMonth = explode("-",$apDate)[1]*1;
        $getCurrentYear = date('Y')*1;
        $getCurrentMonth = date('m')*1;
        $getTodayDate = date('d')*1; 
        
        $periodNomor=$period;

        /*
            Khusus untuk bulan Januai dibatasi oleh cut off per tanggal 5
        */

        if ($getCurrentMonth == 1){
            if($periodNomor == 1){
                $inputYear = $getCurrentYear;
            }
            
            if($periodNomor == 12){
                $inputYear=$getCurrentYear-1;
            }
        }

        // if ($getCurrentMonth == 1){
        //     if($getTodayDate < 6 ){
        //         if ($inputMonth == 1){
        //             $inputYear = $getCurrentYear;
        //         }else{
        //             $inputYear=$getCurrentYear-1;
        //         }
        //     }else{
        //         $periodNomor= 1;
        //         $inputYear = $getCurrentYear;
        //     } 
        // }else{
        //     $inputYear = substr($apDate,-4)*1;
        // }
        
        // $hasilUpdate = AppHelpers::resetCode($this->moduleCode);
        $apNumber = $this->getLastCode($this->moduleCode,$periodNomor,$inputYear);
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
                    'pph23' => $nilaiPph,
                    'pph23_type' =>$typePph,
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
                    'account_pph' => $accountPph,
                    'inv_number' => $invoiceNumber,
                    'tax_inv_number' =>$taxInvoiceNumber,
                    'ap_date' =>$apDate,
                    'period' =>$period
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

                    $dataReceivingDetail = [];
                    foreach ($details as $val) {
                        $dataReceivingDetail[] = [
                            'ap_number' => $apNumber,
                            'account' => $val->account,
                            'description' => $val->description,
                            'cost_center' => $val->cc,
                            'debit' => $val->debit,
                            'credit' => $val->credit,
                            'reference' => $val->reference,
                            'created_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                        ];
                    }

                    DB::table('ap_invoice_det')->insert($dataReceivingDetail);

                    $this->prosesPosting($apNumber);
                }
               
                DB::commit();

                $title ='Save Invoice';
                $alert  ="success";
                $message  = "$title $apNumber is successfully saved";

                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1, 'message' => $message,'alert'=>$alert,'apNumber'=>$apNumber));
                // return redirect()->back()->with(array('title' => $title, 'message' => $message,'alert'=>$alert,'apNumber'=>$apNumber));
                // return redirect()->route('ap.create')->with(array('title' => $title, 'message' => $message,'alert'=>$alert));
                // return redirect()->back()->with($data);

        } catch (Exception $e) {
            DB::rollBack();
            $title ='Save Invoice';
            $alert  ="warning";
            $message  = "*Invoice $apNumber is failed to save";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('status' => 0, 'message' => $message,'alert'=>$alert,'apNumber'=>$apNumber));
            // return redirect()->back()->with(array('title' => $title, 'message' => $message,'alert'=>$alert,'apNumber'=>$apNumber));
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
        ->leftJoin('third_party', 'third_party.kode', '=', 'ap_invoice.supplier_id')
        ->select('ap_invoice.*','third_party.top_batas_1')
        ->where('ap_invoice.id',$id)
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
        ->whereIn(DB::raw("rec_number"), function($query) use ($apNumber) {
            $query->select("rec_number")
            ->from('ap_invoice_detail')
            ->where('ap_number',$apNumber);
        })
        ->orderBy("id")
        ->select("rec_number","do_date","do_number")
        ->get();

        $listRec= DB::table("receiving_hdr") 
        ->whereIn(DB::raw("rec_number"), function($query) use ($apNumber) {
            $query->select("rec_number")
            ->from('ap_invoice_detail')
            ->where('ap_number',$apNumber);
        })
        ->pluck('rec_number')->toArray();
        
        
        $data['detailRec'] = DB::table('receiving_det')
        ->leftJoin('receiving_hdr','receiving_hdr.rec_number','receiving_det.rec_number')
        ->leftJoin('article','article.article_code','receiving_det.article_code')
        ->leftJoin(DB::RAW("(select * from ap_invoice_det where ap_number='$apNumber') as ap"),'ap.reference','receiving_det.article_code')
        // ->leftJoin(DB::RAW("(select * from purchase_order_det where po_number = '$poNumber') AS po"),function($join){
        //     $join->on('po.po_number','=','receiving_hdr.po_number')
        //         ->on('po.article_code','=','receiving_det.article_code');
        // })
        ->whereIn('receiving_det.rec_number',$listRec)
        ->where('receiving_det.qty','>',0)
        ->select('article.article_alternative_code as article'
        ,'article.article_desc as desc'
        ,'receiving_det.uom_rec as uom'
        ,db::raw("sum(receiving_det.qty) as qty")
        // ,'po.price'
        ,'receiving_det.price'
        // ,db::raw("(sum(receiving_det.qty*po.price)) as total")
        ,db::raw("(sum(receiving_det.qty*receiving_det.price)) as total")
        ,'ap.account as account'
        ,db::raw("(select dept from purchase_request_hdr where pr_number in (select pr_number from purchase_order_det where po_number = receiving_hdr.po_number and purchase_order_det.article_code = receiving_det.article_code) limit 1) as dept")
        // ,db::raw("(select dept from purchase_request_hdr where pr_number in (select pr_number from purchase_order_det where po_number = receiving_hdr.po_number and purchase_order_det.article_code = receiving_det.article_code ) limit 1) as dept")
        )
        ->groupBy('article.article_alternative_code')
        ->groupBy('article.article_desc')
        ->groupBy('receiving_det.uom_rec')
        // ->groupBy('po.price')
        ->groupBy('receiving_det.price')
        ->groupBy(db::raw("(select dept from purchase_request_hdr where pr_number in (select pr_number from purchase_order_det where po_number = receiving_hdr.po_number and purchase_order_det.article_code = receiving_det.article_code) limit 1)"))
        // ->groupBy(db::raw("(select dept from purchase_request_hdr where pr_number in (select pr_number from purchase_order_det where po_number = receiving_hdr.po_number ) limit 1)"))
        ->groupBy('ap.account')
        ->get();

        $data['apDetails'] = DB::table('ap_invoice_det')
        ->leftJoin('depts','ap_invoice_det.cost_center','depts.code')
        ->where('ap_number',$apNumber)
        ->where('reference','')
        ->get();

        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

        $status = ['DRAFT','VALIDATED','APPROVED','POSTED','CANCELED','CLOSED','PAID'];
                
        $data['status'] = $status[$data['header']->status -1];
        $data['currency'] = ['IDR','USD'];
        $data['accountBa'] = DB::table('accounts')
        // ->whereIn('type_code',['11','12','14','15','42','44','46','48'])
        ->where('acc_header','!=','HEADER')
        ->get();

        $data['accounts'] = DB::table('accounts')
        // ->whereIn('type_code',['21','22','23','24'])
        ->where('acc_header','!=','HEADER')
        ->get();

        $data['nilaiPPN'] = $this->nilaiPpn;
        $data['nilaiPPH23'] = $this->nilaiPph23;
        $data['nilaiPPH21'] = $this->nilaiPph21;
        $data['nilaiPPH42'] = $this->nilaiPph42;

        $data['statusRevision'] = '';
        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$apNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$apNumber,$username);

        // $data['status'] = ['1'=>'DRAFT','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'PAID'];
        $status = ['DRAFT','VALIDATED','APPROVED','POSTED','CANCELED','CLOSED','PAID'];
        $data['status'] = $status[$data['header']->status-1];

        return view("accounting.accountPayable.show",$data);
        
    }

    public function edit(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;

        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['id']=$id;
        
        $data['header'] = DB::table('ap_invoice')
        ->leftJoin('third_party', 'third_party.kode', '=', 'ap_invoice.supplier_id')
        ->select('ap_invoice.*','third_party.top_batas_1')
        ->where('ap_invoice.id',$id)
        ->get()->first();

        $apNumber = $data['header']->ap_number;

        $details = DB::table('ap_invoice_detail')->where('ap_number',$apNumber)->pluck('rec_number');

        $arrayData="";
        foreach($details as $val ){
            $arrayData.=$val.',';
        }
            
        $data['recNumbers'] = substr($arrayData, 0, -1);

        // $data['sub_details'] = DB::table('ap_invoice')
        // ->leftJoin('third_party', 'third_party.kode', '=', 'ap_invoice.supplier_id')
        // ->where('old_ap_number',$data['header']->ap_number)
        // ->where('status','6')
        // ->select('ap_invoice.*','nama')
        // ->orderBy('ap_invoice.id')
        // ->get();

        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$apNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$apNumber,$username);

        // $data['status'] = ['1'=>'DRAFT','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'PAID'];
        $status = ['DRAFT','VALIDATED','APPROVED','POSTED','CANCELED','PAID'];
        $data['status'] = $status[$data['header']->status-1];

        $data['currency'] = ['IDR','USD'];

        $data['accountBa'] = DB::table('accounts')
        // ->whereIn('type_code',['11','12','14','15','42','44','46','48'])
        ->where('acc_header','!=','HEADER')
        ->get();

        $data['accounts'] = DB::table('accounts')
        // ->whereIn('type_code',['21','22','23','24'])
        ->where('acc_header','!=','HEADER')
        ->get();

        $data['apDetails'] = DB::table('ap_invoice_det')
        ->where('ap_number',$apNumber)
        ->where('reference','')
        ->get();

        $data['depts'] = $this->lisDept();

        $data['nilaiPPN'] = $this->nilaiPpn;
        $data['nilaiPPH23'] = $this->nilaiPph23;
        $data['nilaiPPH21'] = $this->nilaiPph21;
        $data['nilaiPPH42'] = $this->nilaiPph42;

        $data['statusRevision'] = '';
        
        return view("accounting.accountPayable.edit",$data);
        
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $apNumber=$request->apNumber;
        $suppCode = $request->supplier;
        $poNumber = $request->poNumber;
        $currency = $request->currency;
        $rate = is_null($request->rate) ? 0 : preg_replace('/[^0-9.]+/', '', $request->rate);
        $invoiceNumber= $request->invoiceNumber;
        $invoiceDate= $request->invoiceDate;
        $taxInvoiceNumber = $request->taxInvoiceNumber;
        $basisAmount = is_null($request->basisAmount) ? 0 : preg_replace('/[^0-9.]+/', '', $request->basisAmount);
        // $accountBasisA = $request->accountBasisA;
        $otherDeduct = 0;
        $account= $request->account;
        $note=$request->note;
        $period=$request->period;
        $accountHutang = $request->accountHutang;

        $details = json_decode($request->details);
        $accountBasisA = ''; //untuk account basis amount akan diganti dengan account masing2 item

        $apDate= $request->apDate;
        $invoiceNumber=$request->invoiceNumber;
        $taxInvoiceNumber=$request->taxInvoiceNumber;
        $recNumberSave = explode(",",$request->recNumberSave);
        $totalDiscount = is_null($request->totalDiscount) ? 0 : preg_replace('/[^0-9.]+/', '', $request->totalDiscount);
        $grandTotal = is_null($request->grandTotal) ? 0 :  preg_replace('/[^0-9.]+/', '', $request->grandTotal);

        $vat=is_null($request->totalPPN) ? 0 : preg_replace('/[^0-9.]+/', '', $request->totalPPN);
        $pph23 = is_null($request->totalPPH23) ? 0 : preg_replace('/[^0-9.]+/', '', $request->totalPPH23);
        $pph21 = is_null($request->totalPPH21) ? 0 : preg_replace('/[^0-9.]+/', '', $request->totalPPH21);
        $pph42 = is_null($request->totalPPH42) ? 0 : preg_replace('/[^0-9.]+/', '', $request->totalPPH42);

        $typePph ='';        
        if($pph23 > 0 && $typePph==''){
            $typePph='PPH23';
        }

        if($pph21 > 0 && $typePph==''){
            $typePph='PPH21';
        }

        if($pph42 > 0 && $typePph==''){
            $typePph='PPH42';
        }

        $nilaiPph=0;
        if($pph23 > 0 && $nilaiPph ==0){
            $nilaiPph=$pph23;
        }

        if($pph21 > 0 && $nilaiPph ==0){
            $nilaiPph=$pph21;
        }

        if($pph42 > 0 && $nilaiPph ==0){
            $nilaiPph=$pph42;
        }
                
        $accountVat   ='1100.73';
        // $acountTotal  ='2000.11';
        $acountTotal  =  $accountHutang;
        $accountPph23 ='2000.14.3';
        $accountPph21 ='2000.14.2';
        $accountPph42 ='2000.14.6';

        $accountPph = '';
        if($pph23 > 0 && $accountPph ==''){
            $accountPph=$accountPph23;
        }

        if($pph21 > 0 && $accountPph ==''){
            $accountPph=$accountPph21;
        }

        if($pph42 > 0 && $accountPph ==''){
            $accountPph=$accountPph42;
        }

        $getLastStatus = DB::table('ap_invoice')->where('id',$id)->value('status');

        $status = $getLastStatus;
        
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
                        'supplier_id' => $suppCode,
                        'currency' => $currency,
                        'kurs' => $rate,
                        'basis_amount' => $basisAmount,
                        'total_discount' => $totalDiscount,
                        'vat' => $vat,
                        'other_deduction' => $otherDeduct,
                        'pph23' => $nilaiPph,
                        'pph23_type' => $typePph,
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
                        'account_pph' => $accountPph,
                        'period' => $period
                    ]
                );

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
                    
                    DB::table('ap_invoice_detail')->where('ap_number',$apNumber)->delete();
                    DB::table('ap_invoice_detail')->insert($dataReceiving);

                    $dataReceivingDetail = [];
                    foreach ($details as $val) {
                        $dataReceivingDetail[] = [
                            'ap_number' => $apNumber,
                            'account' => $val->account,
                            'description' => $val->description,
                            'cost_center' => $val->cc,
                            'debit' => $val->debit,
                            'credit' => $val->credit,
                            'reference' => $val->reference,
                            'created_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                        ];
                    }
                    DB::table('ap_invoice_det')->where('ap_number',$apNumber)->delete();
                    DB::table('ap_invoice_det')->insert($dataReceivingDetail);

                    $this->prosesUpdatePosting($apNumber);
                }

                // if($getLastStatus == '4'){

                //     DB::table('kas_hdr')
                //     ->where('voucher_number',$apNumber)
                //     ->where('voucher_type','AP')
                //     ->delete();
            
                //     DB::table('kas_det')
                //     ->where('voucher_number',$apNumber)
                //     ->delete();

                //     $this->prosesPosting($apNumber);
                // }
                                                                            
                DB::commit();

                $title ='Update AP Invoice';
                $alert  ="success";
                $message  = "$title $apNumber is successfully updated";

                $data['title'] = $title;
                $data['message'] = $message;
                $data['alert'] = $alert;

                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1, 'message' => $message,'alert'=>$alert,'apNumber'=>$apNumber));
                // return redirect()->back()->with(array('title' => $title, 'message' => $message,'alert'=>$alert,'apNumber'=>$apNumber));
                // return redirect()->route('ap.edit', ['id'=>Crypt::encryptString($id)])->with(array('title' => $title, 'message' => $message,'alert'=>$alert));
                // return redirect()->back()->with($data);

        } catch (Exception $e) {
            DB::rollBack();
            $title ='Update AP Invoice';
            $alert  ="warning";
            $message  = "Invoice $apNumber is failed to update";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('status' => 0, 'message' => $message,'alert'=>$alert,'apNumber'=>$apNumber));
            // return redirect()->back()->with(array('title' => $title, 'message' => $message,'alert'=>$alert,'apNumber'=>$apNumber));
        }
        
    }

    public function approve(Request $request)
    {
        $username =  Auth::user()->username;
        $apNumber = $request->apNumber;
        $statusLevelApproval = Approval::approvalLevelPosition($this->moduleCode,$apNumber,$username);        
        $nextLevel = $statusLevelApproval[0]->next_level;
        $status = $statusLevelApproval[0]->next_level == $statusLevelApproval[0]->max_level ? '3' :'2';
        $maxLevel = $statusLevelApproval[0]->max_level;

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

                $row_affected=DB::table('kas_hdr')
                ->where('voucher_number',$apNumber)
                ->update(
                    [
                        'status' => $status,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                if($status == '3'){
                    //posting AP ke kas
                    DB::table('ap_invoice')
                    ->where('ap_number',$apNumber)
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

                /*
                    permintaan pak leo 9-11-2023
                    untuk akun pak Budi bisa auto apporoved

                */
                if( $nextLevel == ($maxLevel-1) ){
                    $this->autoApprove($apNumber,'budi');
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

    public function autoApprove($apNumber,$username)
    {
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
                        'updated_by' => $username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                if ($row_affected){
                    DB::table('approval_history')->insert([
                        'module_code' => $this->moduleCode,
                        'module_number' => $apNumber,
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

                $row_affected=DB::table('kas_hdr')
                ->where('voucher_number',$apNumber)
                ->update(
                    [
                        'status' => $status,
                        'updated_by' => $username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                if($status == '3'){
                    //posting AP ke kas
                    DB::table('ap_invoice')
                    ->where('ap_number',$apNumber)
                    ->update(
                        [   
                            'status' => '4',
                            'authorized_by' => $username,
                            'authorized_at' => date('Y-m-d H:i:s'),
                            'updated_by' => $username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );
                }
                
                DB::commit();
                $title ="Approve $this->title";
                $alert  ="success";
                $message  = "$title $apNumber is successfully Approve-".$nextLevel;
                \LogActivity::addToLog($title,"username: $username Status $message");

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Approve $this->title";
            $alert  ="warning";
            $message  = "$title $apNumber is failed to Approve-".$nextLevel;
            \LogActivity::addToLog($title,"username: $username Status $message");
        }
    }

    public function prosesPosting($apNumber){
        /* Proses posting ke kas*/
        $apData = db::table('ap_invoice')
        ->leftJoin('third_party', 'third_party.kode', '=', 'ap_invoice.supplier_id')
        ->select('ap_invoice.*','third_party.nama as supplier_name')
        ->where('ap_number',$apNumber)->first();

        $period = $apData->period ? $apData->period : (int)explode('-',$apData->ap_date)[1];
        $periodYear=(int)explode('-', $apData->ap_date)[2];
        $apStatus = $apData->status;
     
        DB::table('kas_hdr')->insert([
            'voucher_number' => $apNumber,
            'voucher_type' => $this->moduleCode,
            // 'voucher_date' =>date('d-m-Y'), //tanggal posting
            'voucher_date' => $apData->ap_date, //receive date
            'paid_to' => $apData->supplier_id,
            'description' => $apNumber,
            'amount' => $apData->grand_total,
            // 'period' =>date('n'),
            'period' => $period,
            'year' => $periodYear,
            'note' => $apData->note,
            'status' => $apStatus,
            'created_by' => Auth::user()->username,
            'updated_by' => Auth::user()->username,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $listApDetail = DB::table('ap_invoice_det')
        ->where('ap_number',$apNumber)
        ->orderBy('id')
        ->get();

        $dataSet = [];
        $reference = '';

        foreach ($listApDetail as $val) {
            $dataSet[] = [
                'voucher_number' => $val->ap_number,
                'account' => $val->account,
                'description' => $val->description,
                'cost_center' => $val->cost_center,
                'debit' => $val->debit,
                'credit' => $val->credit,
                'reference' => $val->reference,
                'created_by' => Auth::user()->username,
                'updated_by' => Auth::user()->username,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }

        // $reference = $apNumber;

        /*
        account basic amount di ganti dengan data detail
        // $dataSet = [];
        // $dataSet[] = [
        //     'voucher_number' => $apNumber,
        //     'account' =>$apData->account_ba,
        //     'description' => $apNumber.' '.$apData->supplier_name,
        //     'debit' => $apData->basis_amount,
        //     'credit' => 0,
        //     'reference' => $reference,  //sementara tidak di masukan belum tau fungsinya apa
        //     'created_by' => Auth::user()->username,
        //     'updated_by' => Auth::user()->username,
        //     'created_at' => date('Y-m-d H:i:s'),
        //     'updated_at' => date('Y-m-d H:i:s')
        // ];
        */

        if($apData->vat > 0){
            $dataSet[] = [
                'voucher_number' => $apNumber,
                'account' =>$apData->account_vat,
                'description' => $apNumber.' '.$apData->supplier_name,
                'cost_center' => '',
                'debit' => $apData->vat,
                'credit' => 0,
                'reference' => $reference,
                'created_by' => Auth::user()->username,
                'updated_by' => Auth::user()->username,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }

        //belum ada no account nya
        // if($apData->total_discount > 0){
        //     $dataSet[] = [
        //         'voucher_number' => $apNumber,
        //         'account' =>$apData->account_total,
        //         'description' => $apNumber.' '.$apData->supplier_name,
        //         'debit' => 0,
        //         'credit' => $apData->total_discount,
        //         'reference' => $reference,
        //         'created_by' => Auth::user()->username,
        //         'updated_by' => Auth::user()->username,
        //         'created_at' => date('Y-m-d H:i:s'),
        //         'updated_at' => date('Y-m-d H:i:s')
        //     ];  
        // }

        if($apData->pph23 > 0){
            $dataSet[] = [
                'voucher_number' => $apNumber,
                'account' =>$apData->account_pph,
                'description' => $apNumber.' '.$apData->supplier_name,
                'cost_center' => '',
                'debit' => 0,
                'credit' => $apData->pph23,
                'reference' => $reference,
                'created_by' => Auth::user()->username,
                'updated_by' => Auth::user()->username,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];  
        }

        $dataSet[] = [
            'voucher_number' => $apNumber,
            'account' =>$apData->account_total,
            'description' => $apNumber.' '.$apData->supplier_name,
            'cost_center' => '',
            'debit' => 0,
            'credit' => $apData->grand_total,
            'reference' => $reference,
            'created_by' => Auth::user()->username,
            'updated_by' => Auth::user()->username,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];  

        DB::table('kas_det')->insert($dataSet);

    }

    public function prosesUpdatePosting($apNumber){
        /* Update proses posting ke kas*/

        $apData = db::table('ap_invoice')
        ->leftJoin('third_party', 'third_party.kode', '=', 'ap_invoice.supplier_id')
        ->select('ap_invoice.*','third_party.nama as supplier_name')
        ->where('ap_number',$apNumber)->first();

        $period = $apData->period ? $apData->period : (int)explode('-',$apData->ap_date)[1];
        $periodYear=(int)explode('-', $apData->ap_date)[2];
        $apStatus = $apData->status == '4'? '3' : $apData->status;
        $createdBy = $apData->created_by;
        $createdAt = $apData->created_at;
        
        $row_affected=DB::table('kas_hdr')
        ->where('voucher_number',$apNumber)
        ->update(
            [
                'voucher_type' =>$this->moduleCode,
                'voucher_date' => $apData->ap_date, //receive date
                'paid_to' => $apData->supplier_id,
                'description' => $apNumber,
                'amount' => $apData->grand_total,
                'period' => $period,
                'year' => $periodYear,
                'note' => $apData->note,
                'status' => $apStatus,
                'updated_by' => Auth::user()->username,
                'updated_at' => date('Y-m-d H:i:s')
            ]
        );

        DB::table('kas_det')
        ->where('voucher_number',$apNumber)
        ->delete();

        $listApDetail = DB::table('ap_invoice_det')
        ->where('ap_number',$apNumber)
        ->orderBy('id')
        ->get();

        $dataSet = [];
        $reference = '';

        foreach ($listApDetail as $val) {
            $dataSet[] = [
                'voucher_number' => $val->ap_number,
                'account' => $val->account,
                'description' => $val->description,
                'cost_center' => $val->cost_center,
                'debit' => $val->debit,
                'credit' => $val->credit,
                'reference' => $val->reference,
                'created_by' => Auth::user()->username,
                'updated_by' => Auth::user()->username,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }

       // $reference = $apNumber;
        
        /*
        diganti dengan data detail       
        $dataSet = [];
        $dataSet[] = [
            'voucher_number' => $apNumber,
            'account' =>$apData->account_ba,
            'description' => $apNumber.' '.$apData->supplier_name,
            'debit' => $apData->basis_amount,
            'credit' => 0,
            'reference' => $reference,  //sementara tidak di masukan belum tau fungsinya apa
            'created_by' => $createdBy,
            'updated_by' => Auth::user()->username,
            'created_at' => $createdAt,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        */

        if($apData->vat > 0){
            $dataSet[] = [
                'voucher_number' => $apNumber,
                'account' =>$apData->account_vat,
                'description' => $apNumber.' '.$apData->supplier_name,
                'cost_center' => '',
                'debit' => $apData->vat,
                'credit' => 0,
                'reference' => $reference,
                'created_by' => Auth::user()->username,
                'updated_by' => Auth::user()->username,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }

        if($apData->pph23 > 0){
            $dataSet[] = [
                'voucher_number' => $apNumber,
                'account' =>$apData->account_pph,
                'description' => $apNumber.' '.$apData->supplier_name,
                'cost_center' => '',
                'debit' => 0,
                'credit' => $apData->pph23,
                'reference' => $reference,
                'created_by' => Auth::user()->username,
                'updated_by' => Auth::user()->username,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];  
        }

        $dataSet[] = [
            'voucher_number' => $apNumber,
            'account' =>$apData->account_total,
            'description' => $apNumber.' '.$apData->supplier_name,
            'cost_center' => '',
            'debit' => 0,
            'credit' => $apData->grand_total,
            'reference' => $reference,
            'created_by' => Auth::user()->username,
            'updated_by' => Auth::user()->username,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];  

        DB::table('kas_det')->insert($dataSet);

        //kalau status ap terakhir 3 itu langsung posting
        // if ($apStatus=='3'){
        //     DB::table('ap_invoice')
        //     ->where('ap_number',$apNumber)
        //     ->update(
        //         [   
        //             'status' => '4',
        //             'authorized_by' => Auth::user()->username,
        //             'authorized_at' => date('Y-m-d H:i:s'),
        //             'updated_by' => Auth::user()->username,
        //             'updated_at' => date('Y-m-d H:i:s')
        //         ]
        //     );
        // }
    }

    public function prosesPosting_old($apNumber){
        /* Proses posting ke kas*/
        $apData = db::table('ap_invoice')
        ->leftJoin('third_party', 'third_party.kode', '=', 'ap_invoice.supplier_id')
        ->select('ap_invoice.*','third_party.nama as supplier_name')
        ->where('ap_number',$apNumber)->first();

        $period = $apData->period ? $apData->period : (int)explode('-',$apData->ap_date)[1];
        $periodYear=(int)explode('-', $apData->ap_date)[2];
        $apStatus = $apData->status;

        DB::table('kas_hdr')->insert([
            'voucher_number' => $apNumber,
            'voucher_type' => $this->moduleCode,
            // 'voucher_date' =>date('d-m-Y'), //tanggal posting
            'voucher_date' => $apData->ap_date, //receive date
            'paid_to' => $apData->supplier_id,
            'description' => $apNumber,
            'amount' => $apData->grand_total,
            // 'period' =>date('n'),
            'period' => $period,
            'year' => $periodYear,
            'note' => $apData->note,
            'status' => $apStatus,
            'created_by' => Auth::user()->username,
            'updated_by' => Auth::user()->username,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // $reference = $apNumber;
        $reference = '';

        $dataSet = [];
        $dataSet[] = [
            'voucher_number' => $apNumber,
            'account' =>$apData->account_ba,
            'description' => $apNumber.' '.$apData->supplier_name,
            'debit' => $apData->basis_amount,
            'credit' => 0,
            'reference' => $reference,  //sementara tidak di masukan belum tau fungsinya apa
            'created_by' => Auth::user()->username,
            'updated_by' => Auth::user()->username,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if($apData->vat > 0){
            $dataSet[] = [
                'voucher_number' => $apNumber,
                'account' =>$apData->account_vat,
                'description' => $apNumber.' '.$apData->supplier_name,
                'debit' => $apData->vat,
                'credit' => 0,
                'reference' => $reference,
                'created_by' => Auth::user()->username,
                'updated_by' => Auth::user()->username,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }

        //belum ada no account nya
        // if($apData->total_discount > 0){
        //     $dataSet[] = [
        //         'voucher_number' => $apNumber,
        //         'account' =>$apData->account_total,
        //         'description' => $apNumber.' '.$apData->supplier_name,
        //         'debit' => 0,
        //         'credit' => $apData->total_discount,
        //         'reference' => $reference,
        //         'created_by' => Auth::user()->username,
        //         'updated_by' => Auth::user()->username,
        //         'created_at' => date('Y-m-d H:i:s'),
        //         'updated_at' => date('Y-m-d H:i:s')
        //     ];  
        // }

        if($apData->pph23 > 0){
            $dataSet[] = [
                'voucher_number' => $apNumber,
                'account' =>$apData->account_pph,
                'description' => $apNumber.' '.$apData->supplier_name,
                'debit' => 0,
                'credit' => $apData->pph23,
                'reference' => $reference,
                'created_by' => Auth::user()->username,
                'updated_by' => Auth::user()->username,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];  
        }

        $dataSet[] = [
            'voucher_number' => $apNumber,
            'account' =>$apData->account_total,
            'description' => $apNumber.' '.$apData->supplier_name,
            'debit' => 0,
            'credit' => $apData->grand_total,
            'reference' => $reference,
            'created_by' => Auth::user()->username,
            'updated_by' => Auth::user()->username,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];  

        DB::table('kas_det')->insert($dataSet);
        DB::table('ap_invoice')
        ->where('ap_number',$apNumber)
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
            $this->prosesPosting($apNumber);           
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
        $statusCode = ['DRAFT','VALIDATED','APPROVED','POSTED','CANCELED','PAID'];

        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);
        
        $data= DB::table('ap_invoice')
        ->where('id',$id)
        ->get()->first();

        $apNumber = $data->ap_number;
        
        $rowAffected = DB::table('ap_invoice')
        ->where('ap_number',$apNumber)
        ->delete();

        if ($rowAffected){
            DB::table('ap_invoice_detail')
            ->where('ap_number',$apNumber)
            ->delete();

            // $kasDet = DB::table('kas_det')
            // ->where('reference',$apNumber)
            // // ->whereIn('voucher_type',['KK','BK'])
            // ->first();
            
            // $voucherNumber=$kasDet->voucher_number;

            DB::table('kas_hdr')
            ->where('voucher_number',$apNumber)
            ->where('voucher_type',$this->moduleCode)
            ->delete();

            DB::table('kas_det')
            ->where('voucher_number',$apNumber)
            ->delete();

            DB::table('approval_history')
            ->where('module_number',$apNumber)
            ->where('module_code',$this->moduleCode)
            ->delete();

            DB::commit();
            $title ='Delete input invoice';
            $alert  ="success";
            $message  = "$apNumber is successfully deleted";
            \LogActivity::addToLog('AP Invoice delete ',"username: $username Status $message");
            return redirect()->back()->with(array('status' => 1, 'message' => $message,'alert'=>$alert,'apNumber'=>$apNumber));

        }else{
            DB::rollBack();
            $title ='Delete input invoice';
            $alert  ="warning";
            $message  = "$apNumber is failed to delete";
            \LogActivity::addToLog('Delete AP ',"username: $username Status $message");
            return response()->back()->with(array('status' => 0, 'message' => $message,'alert'=>$alert,'apNumber'=>$apNumber));
        }

    }

    public function list(Request $request)
    {

        // $data['status'] = ['1'=>'DRAFT','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'PAID'];
        $searchPo = $request->searchPo;
        $searchAp = $request->searchAp;
        $searchSupplier = $request->searchSupplier;
        $searchStatus = $request->searchStatus;
        $apDate = $request->apDate;
        $fromDate = "";
        $toDate = "";
        $apPeriod = $request->apPeriod;

        // dd($searchStatus);
       
        if ($apDate){
            $date = explode("to",$apDate);
            if(count($date)>1){
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate; 
            }
        }

        $data = DB::table('ap_invoice')
        ->leftJoin('third_party','third_party.kode','ap_invoice.supplier_id')
        ->where(function ($query) use ($searchAp,$searchPo,$searchSupplier,$searchStatus,$apDate,$fromDate,$toDate,$apPeriod) {
            $searchPo ? $query->where('po_number','ilike','%'.$searchPo.'%') : '';
            $searchAp ? $query->where('ap_number','ilike','%'.$searchAp.'%') : '';
            $searchSupplier ? $query->where('supplier_id','ilike','%'.$searchSupplier.'%') : '';
            $searchStatus ? $query->where('ap_invoice.status','=',$searchStatus) : '';
            $apDate ? $query->whereBetween(DB::raw("to_date(ap_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
            $apPeriod ? $query->where('period',$apPeriod) : '';
        })
        ->whereNotIn('ap_invoice.status',['5'])
        ->select(
            'ap_invoice.*'
            // ,DB::raw("to_char(to_date(ap_invoice.ap_date, 'DD-MM-YYYY'), 'DD Month YYYY') as ap_date")
            ,DB::raw("to_char(to_date(ap_invoice.ap_date, 'DD-MM-YYYY'), 'DD/MM/YYYY') as ap_date")
            ,DB::raw("to_date(ap_invoice.ap_date, 'DD-MM-YYYY') as ap_date_2")
            ,DB::raw("(select STRING_AGG ( a.rec_number,',' ORDER BY a.id) as list_rec from ap_invoice_detail a where ap_number = ap_invoice.ap_number) as list_rec")
            ,'third_party.nama as supplier_name'
            ,db::raw("(select (select name from users where username = z.username) from approval_history z where module_number = ap_invoice.ap_number order by approval_order desc limit 1) as approval_by")
            ,db::raw("(select to_char(approval_date::date, 'DD-MM-YYYY') from approval_history z where module_number = ap_invoice.ap_number order by approval_order desc limit 1) as approval_at")
            ,db::raw("case when pph23_type = 'PPH21' then pph23 else 0 end as pph21")
            ,db::raw("case when pph23_type = 'PPH23' then pph23 else 0 end as pph23")
            ,db::raw("case when pph23_type = 'PPH42' then pph23 else 0 end as pph42")
            ,db::raw("case when ap_invoice.status = '6' then (select voucher_date from kas_hdr where voucher_number = (select voucher_number from kas_det where reference = ap_invoice.inv_number and account = third_party.account)) else '' end as voucher_date")
            ,db::raw("case when ap_invoice.status = '6' then (select voucher_number from kas_det where reference = ap_invoice.inv_number and account = third_party.account) else '' end as voucher_number")
            ,db::raw("case when ap_invoice.status = '6' then (select debit from kas_det where reference = ap_invoice.inv_number and account = third_party.account) else 0 end as voucher_amount")
            ,db::raw("grand_total-coalesce((select debit from kas_det where reference = ap_invoice.inv_number and account = third_party.account),0) as balance")
        )
        ->orderBy('ap_invoice.id')
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

            if ( $data->status == '2' or $data->status == '1') {
                // if (Auth::user()->can('kasPenerimaan-approve')) {
                $buttons .=     '<a href="'. route('accountPayable.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="check"></i>
                                    <span>'. __("Approve") .'</span>
                                </a>';
                // }
            }

            // if (($data->status != '3') && ($data->status != '4') && ($data->status != '5')){
                //sibuka sementara dari pak leo 6-11-2023
            if ($data->status != '5'){
            // if (($data->status != '4') && ($data->status != '5')){
                if ($bisaEdit) {
                $buttons .=         '<a href="'. route('accountPayable.edit',['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="file-text"></i>
                                        <span>'. __("Edit") .'</span>
                                    </a>';
                }
            }

            $buttons .=         '<a href="'. route('accountPayable.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
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

            // if (($data->status == '3')){
            //     // if (Auth::user()->can('ap-edit')) {
            //     $buttons .=         '<a href="'. route('ap.edit',['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
            //                             <i data-feather="check"></i>
            //                             <span>'. __("Posting") .'</span>
            //                         </a>';
            //     // }
            // }
                                
            // if ( $data->status == '3' ){
            //     if (Auth::user()->can('ap-revision')) {
            //         $buttons .= '<a href="'. route('ap.revision', ['id'=>$data->id,'apNumber'=>$data->ap_number,'numRevision'=>$data->num_revision,'statusRevision'=>'revision']) .'" class="dropdown-item">
            //                         <i data-feather="copy"></i>
            //                            Revision
            //                     </a>';
            //     }
            // }
                

            // if (($data->status == '4')){
                $buttons .=         '<a href="'. route('accountPayable.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
                                    <i data-feather="printer"></i>
                                    <span>'. __("Print") .'</span>
                                </a>';
            // }

            // if (($data->status != '4')){
            //     $buttons .=         '<a href="'. route('ap.print.draft', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
            //                         <i data-feather="printer"></i>
            //                         <span>'. __("Print") .'</span>
            //                     </a>';
            // }


            // if (($data->status == '4')){
                $buttons .=         '<a href="'. route('accountPayable.print.slip.pembayaran', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
                                    <i data-feather="printer"></i>
                                    <span>'. __("Print Slip") .'</span>
                                </a>';
            // }

            if (($data->status != '7')){
                if ($bisaDelete) {
                $buttons .=         "<a href='javascript:;'
                                        id='deleteButton'
                                        class='dropdown-item'
                                        data-toggle='modal'
                                        data-target='#smallModal'
                                        data-href='". route("accountPayable.destroy", ["id"=>Crypt::encryptString($data->id)]) ."'>
                                        <i data-feather='trash-2' class='feather-14-red'></i>
                                        Delete
                                    </a>";
                }
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
            $statusCode = ['DRAFT','VALIDATED','APPROVED','POSTED','CANCELED','PAID'];
            return "<div class='badge badge-pill ".$badges[$data->status - 1]."'>".$statusCode[$data->status - 1]."</div>";
        })
        ->rawColumns(['action','status','ap_number'])
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
        // if ($jumlahBaris < 18){
        //     $ukuranKertas = "A4A5";
        // }

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

    public function printDraft(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $data['title'] ='Invoice Supplier';

        $apNumber = DB::table('ap_invoice')->where('id',$id)->value('ap_number');

        $data['apInvoice'] = DB::table('ap_invoice')
        ->leftJoin('third_party','third_party.kode','ap_invoice.supplier_id')
        ->select(
            'ap_invoice.*'
            ,DB::raw("(select STRING_AGG ( a.rec_number,',' ORDER BY a.id) as list_rec from ap_invoice_detail a where ap_number = ap_invoice.ap_number) as list_rec")
            ,'third_party.nama as supplier_name'
            ,DB::raw("(select description from accounts where account=ap_invoice.account_ba) as account_ba_name")
            ,DB::raw("(select description from accounts where account=ap_invoice.account_total) as account_total_name")
            ,DB::raw("(select description from accounts where account=ap_invoice.account_vat) as account_vat_name")
            ,DB::raw("(select description from accounts where account=ap_invoice.account_pph) as account_pph_name")
        )
        ->where('ap_invoice.id',$id)->first();       

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

        $data['apNumber'] =  $data['apInvoice']->ap_number;
        $data['invoiceNumber'] = $data['apInvoice']->inv_number;
        $data['supplierName'] = $data['apInvoice']->supplier_name;
        $data['nomorLpb'] = $data['apInvoice']->list_rec;
        // $data['apDate'] = $data['apInvoice']->ap_date ? $data['apInvoice']->inv_date : '';
        // $data['apDate'] = $data['apInvoice']->ap_date;
        $data['invDate'] = $data['apInvoice']->inv_date;
        $data['noPo'] = $data['apInvoice']->po_number;

        return view('accounting.accountPayable.printDraft',$data);

    }

    public function printSlipPembayaran(Request $request)
    {
        $id=Crypt::decryptString($request->id);

        $apNumber = DB::table('ap_invoice')->where('id',$id)->value('ap_number');

        $apInvoice = DB::table('ap_invoice')
        ->leftJoin('third_party','third_party.kode','ap_invoice.supplier_id')
        ->leftJoin('accounts','accounts.account','ap_invoice.account_ba')
        ->select(
            'ap_invoice.*'
            ,DB::raw("(select STRING_AGG ( a.rec_number,',' ORDER BY a.id) as list_rec from ap_invoice_detail a where ap_number = ap_invoice.ap_number) as list_rec")
            ,'third_party.nama as supplier_name',
            'accounts.description as account_name'
        )
        ->where('ap_invoice.id',$id)->first();

        $data['title'] ='Invoice Supplier';

        // $data['header']=DB::table('kas_hdr')
        // ->select('kas_hdr.*'
        // ,'description as receive_name'
        // )
        // ->where('kas_hdr.description',$apNumber)
        // ->first();

        // $voucherNumber=$data['header']->voucher_number;
       
        // $data['details']=DB::table('kas_det')
        // ->leftJoin('accounts','accounts.account','kas_det.account')
        // ->select('kas_det.*','accounts.description as account_name')
        // ->where('voucher_number',$voucherNumber)
        // ->orderBy('id')
        // ->get();

        // $data['total']=DB::table('kas_det')
        // ->select(DB::raw("sum(credit) as total_credit"),DB::raw("sum(debit) as total_debit"))
        // ->where('voucher_number',$voucherNumber)
        // ->first();

        // $data['costCenter']=DB::table('kas_det')
        // ->leftJoin('depts','depts.code','kas_det.cost_center')
        // ->where('voucher_number',$voucherNumber)
        // ->distinct('depts.name')
        // ->pluck('depts.name')->implode(',');

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

        $data['top']=DB::table('third_party')->where('kode',$apInvoice->supplier_id)->value('top_batas_1');
        $data['apNumber'] =  $apInvoice->ap_number;
        $data['invoiceNumber'] = $apInvoice->inv_number;
        $data['supplierName'] = $apInvoice->supplier_name;
        $data['nomorLpb'] = $apInvoice->list_rec;
        // $data['apDate'] = $apInvoice->ap_date ? $apInvoice->inv_date : '';
        // $data['invDate'] = $apInvoice->inv_date;
        $data['apDate'] = $apInvoice->ap_date;
        $data['noPo'] = $apInvoice->po_number;
        $data['grandTotal'] = $apInvoice->grand_total;
        $data['accountName'] = $apInvoice->account_name;
        $data['accountBa'] = $apInvoice->account_ba;
        $data['vat'] = $apInvoice->vat;
        $data['basisAmount'] = $apInvoice->basis_amount;
        $data['invNumber'] = $apInvoice->inv_number;
        $data['notes'] = $apInvoice->note;
        
        return view('accounting.accountPayable.printSlipPembayaran',$data);

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

    public function lisDept()
    {
        
        $output="";
        $data= DB::table("depts") 
        ->orderBy("code")
        ->select("code","name")
        ->get();          

        $output .='<option value=""></option>';            
        foreach ($data as $row){
            $output .='<option value="'.$row->code.'">'.$row->name.'</option>';            
        }        
        
        return $output;
    }

    public function prosesAllPosting(){
        $listAp = db::table('ap_invoice')
        ->whereIn('status',['1','2','3'])
        ->whereNotIn(DB::raw("ap_number"), function($query) {
            $query->select('voucher_number')
            ->from('kas_hdr');
        })
        ->get();

        foreach($listAp as $val){
            $this->prosesPosting($val->ap_number);
        }

        return "beres proses";

    }

}
