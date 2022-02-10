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

class DeliveryPlanController extends Controller
{
    // public function index(Request $request)
    // {
    //     $data['title'] = "Working Order Sheet";

    //     $data['supps'] = DB::table('third_party')
    //     ->where ('third_party_type','=','supp')
    //     ->orderBy('nama')
    //     ->get();

    //     // status:
    //     // 1 = New
    //     // 2 = Validated
    //     // 3 = Authorized
    //     // 4 = Received
    //     // 5 = Canceled
    //     // 6 = closed

    //     $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'AUTHORIZED','4'=>'RECEIVED','5'=>'CANCELED','6'=>"CLOSE"];
            
    //     return view("workingOrderSheet.index",$data);
    // }

    // public function getLastCode($key)
    // {
    //     DB::table('master_code')
    //     ->where('code_key',$key)
    //     ->update([
    //         'code_number' => DB::raw('code_number + 1'),
    //         'updated_by' => Auth::user()->username,
    //         'updated_at' => date('Y-m-d H:i:s')
    //     ]);

    //     $newCode = DB::table('master_code')
    //     ->where('code_key',$key)
    //     ->value('code_number'); 
    //     $month = date('n');
    //     $year = date('Y');
    //     $woNumber="$key/ASN/$year/$month/$newCode";
        
    //     return $woNumber;
    // }

    public function create(Request $request)
    {
        $data['title'] = "Delivery Plan";
        $data['subtitle'] = "Delivery Plan";
       
        return view("deliveryPlan.create",$data);

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

        $month = date('m');
        $year = date('y');
        $soNumber="$key/ASN/$month/$year/$newCode";
        
        return $soNumber;
    }

    public function generatePlan(Request $request)
    {   
        $articles = json_decode($request->articles);
        $soDate = $request->soDate;
        $articleNumber = $request->articleNumber;
        $articleNumber = substr($articleNumber,0,-1);
        $username = Auth::user()->username;
        
        if ($soDate){
            $date = explode("to",$soDate);
            $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
            $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            $fromDate1 = implode("-", array_reverse(explode("-", trim($date[0]))));
            $toDate1 = implode("-", array_reverse(explode("-", trim($date[1]))));
        }     

        // $fromDate = '2021-06-01';
        // $toDate = '2021-07-01';

        $randomNumber = rand();
        $dataSet = [];
        foreach ($articles as $val) {
            $dataSet[] = [
                'random_code' => $randomNumber,
                'article_code' => $val->article_code,
                'so_code' => $val->so_code,
                'so_qty' => $val->so_qyt
            ];
        }

        DB::table('del_plan_tmp')->insert($dataSet);

        $planCode = $this->getLastCode('DP');

        DB::select("INSERT into del_plan_det
        (del_plan_code,so_code,so_qty,article_code,article_alternative_code,article_desc,code,group_of_material,color_code,variant,day,tanggal,plan,act,balance,cust_code,cust_name,created_by,updated_by,created_at,updated_at)
        SELECT 	'$planCode' as del_plan_code
            ,so_code
            ,so_qty
            ,a.article_code
            ,article_alternative_code
            ,a.article_desc
            ,group_of_material as group_code
            ,(select name from group_materials where code = group_of_material) as group_of_material 
            ,color_code
            ,variant
            ,day
            ,to_char(day, 'dd') as tanggal
            ,plan
            ,act
            ,balance
            ,third_party as cust_code 
            ,(select nama from third_party where kode = third_party) as cust_name 
            ,'$username'
            ,'$username'
            ,now()
            ,now()
        from 
        (SELECT p.article_code,so_code,so_qty,day::date,0 as plan,0 as act,0 as balance
        FROM (select * from del_plan_tmp ) p cross join
        generate_series(
            timestamp '$fromDate'
            , timestamp '$toDate'
            , INTERVAL '1 day') 
        day left join
        article dm
        ON dm.article_code = p.article_code
        ) as oki
        left join article a
        on a.article_code = oki.article_code
        where concat(a.article_code,to_char(day, 'dd')) not in (select concat(article_code,tanggal) from del_plan_det)
        ORDER BY group_code,article_alternative_code,day ASC");

        DB::table('del_plan_tmp')->where('random_code', $randomNumber)->delete();

        $kolomHeader=DB::select("SELECT day
                                        ,to_char(day, 'dy') as dy
                                        ,to_char(day, 'dd-Mon') as datemon
                                        ,to_char(day, 'YYYY') as dateyear 
                                        ,('$toDate'::date -'$fromDate'::date) as countday
                                from (SELECT t.day::date
                                FROM  generate_series(
                                    timestamp '$fromDate'
                                    , timestamp '$toDate'
                                    , interval  '1 day') AS t(day)
                                ) as oki");

        $data=DB::table('del_plan_det')
         ->whereBetween('day', [$fromDate1, $toDate1])
        //  ->where('del_plan_code',$planCode)
         ->orderBy('code')
         ->orderBy('article_alternative_code')
         ->orderBy('day')
         ->get();

        $sumOfSupp=DB::select("SELECT cust_name,day,sum(plan) as plan,sum(act) as act,sum(balance) as balance 
            from del_plan_det
            where day between '$fromDate1' and '$toDate1'
            group by cust_name,day
            order by cust_name,day");

        // $data=DB::select("SELECT a.article_code
        //                         ,article_alternative_code
        //                         ,article_desc,group_of_material as group_code
        //                         ,(select name from group_materials where code = group_of_material) as group_of_material 
        //                         ,color_code
        //                         ,variant
        //                         ,day
        //                         ,to_char(day, 'dd') as tanggal
        //                         ,plan
        //                         ,act
        //                         ,balance
        //                         ,(select nama from third_party where kode = third_party) as supp_name 
        //                 from 
        //                 (SELECT p.article_code,day::date,1 as plan,2 as act,0 as balance
        //                 FROM (select article_code from article where article_code in($articleNumber) ) p cross join
        //                 -- FROM (select article_code from article where article_type ='FG' ) p cross join
        //                 generate_series(
        //                     timestamp '$fromDate'
        //                     , timestamp '$toDate'
        //                     , INTERVAL '1 day') 
        //                 day left join
        //                 article dm
        //                 ON dm.article_code = p.article_code
        //                 ) as oki
        //                 left join article a
        //                 on a.article_code = oki.article_code
        //                 ORDER BY group_code,article_alternative_code,day ASC");

        
        // $sumOfSupp=DB::select("SELECT supp_name,day,sum(plan) as plan,sum(act) as act,sum(balance) as balance from (
        //     SELECT a.article_code,article_alternative_code,article_desc,group_of_material as group_code,(select name from group_materials where code = group_of_material) as group_of_material ,color_code,variant,day,to_char(day, 'dd') as tanggal,plan,act,balance 
        //     ,(select nama from third_party where kode = third_party) as supp_name
        //     from 
        //     (SELECT p.article_code,day::date,1 as plan,2 as act,0 as balance
        //     FROM (select article_code from article where article_code in($articleNumber) ) p cross join
        //     -- FROM (select article_code from article where article_type ='FG') p cross join
        //     generate_series(
        //         timestamp '$fromDate'
        //         , timestamp '$toDate'
        //         , INTERVAL '1 day') 
        //     day left join
        //     article dm
        //     ON dm.article_code = p.article_code
        //     ORDER BY p.article_code,day ASC) as oki
        //     left join article a
        //     on a.article_code = oki.article_code) as oki
        //     group by supp_name,day
        //     order by supp_name,day");

        return Response()->json(['data'=> $data,'kolom'=>$kolomHeader,'supp' => $sumOfSupp,'delPlanCode' => $planCode]);

    }

    public function reGeneratePlan(Request $request)
    {   
        $soDate = $request->soDate;

        if ($soDate){
            $date = explode("to",$soDate);
            $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
            $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            $fromDate1 = implode("-", array_reverse(explode("-", trim($date[0]))));
            $toDate1 = implode("-", array_reverse(explode("-", trim($date[1]))));
        }     

        $kolomHeader=DB::select("SELECT day
                                        ,to_char(day, 'dy') as dy
                                        ,to_char(day, 'dd-Mon') as datemon
                                        ,to_char(day, 'YYYY') as dateyear 
                                        ,('$toDate'::date -'$fromDate'::date) as countday
                                from (SELECT t.day::date
                                FROM  generate_series(
                                    timestamp '$fromDate'
                                    , timestamp '$toDate'
                                    , interval  '1 day') AS t(day)
                                ) as oki");

         $data=DB::table('del_plan_det')
         ->whereBetween('day', [$fromDate1, $toDate1])
         ->orderBy('code')
         ->orderBy('article_alternative_code')
         ->orderBy('day')
         ->get();

         $sumOfSupp=DB::select("SELECT cust_name,day,sum(plan) as plan,sum(act) as act,sum(balance) as balance 
            from del_plan_det
            where day between '$fromDate1' and '$toDate1'
            group by cust_name,day
            order by cust_name,day");

        return Response()->json(['data'=> $data,'kolom'=>$kolomHeader,'supp' => $sumOfSupp]);

    }

    public function listSo(Request $request)
    {
        $soDate = $request->soDate;
        $fromDate = "";
        $toDate = "";

        if ($soDate){
            $date = explode("to",$soDate);
            // $fromDate = date(trim($date[0]));
            // $toDate = date(trim($date[1]));
            $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
            $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
        }      

        $data=DB::table('sales_order_hdr')
        ->select('sales_order_hdr.*','third_party.nama as cust_name')
        ->leftJoin('third_party', 'third_party.kode', '=', 'sales_order_hdr.customer_id')
        ->whereBetween('sales_order_hdr.created_at', [$fromDate, $toDate])
        ->get();

        return Datatables::of($data)
        ->addColumn('select_orders', static function ($result) {
            return '<input type="checkbox" class="select-checkbox" name="soCheck[]" value="'.$result->so_code.'"/>';
        })
        ->rawColumns(['select_orders'])
        ->make(true);
    }

    public function listArticle(Request $request)
    {
        $soNumber = $request->soNumber;
        $soNumber = substr($soNumber,0,-1);     
        $soNumber = explode(",",$soNumber);

        $data=DB::table('sales_order_det')
        ->leftJoin('article','article.article_code','=','sales_order_det.article_code')
        ->select('sales_order_det'.'.*','article.article_alternative_code','article.article_desc',DB::raw("round(qty) as so_qty"))
        ->whereIn('so_code',$soNumber)->get();

        return Datatables::of($data)
        ->addColumn('select_orders', static function ($result) {
            return '<input type="checkbox" class="select-checkbox-article" 
                    name="articleCheck[]" 
                    data-so-number= "'.$result->so_code.'" 
                    data-so-qty= "'.$result->so_qty.'" 
                    value="'.$result->article_code.'"/>';
        })
        ->rawColumns(['select_orders'])
        ->make(true);
    }

    public function update(Request $request)
    {   
        $articles = json_decode($request->articles);
        $username = Auth::user()->username;
        DB::beginTransaction();
        try {

            foreach ($articles as $val) {
                DB::table('del_plan_det')
                    ->where('article_code', $val->article_code)
                    ->where('day', $val->date)
                    ->update([
                        'plan' => $val->plan,
                        'updated_by' => $username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
            }

            DB::commit();
                    $alert  ="alert-success";
                    $message  = "Plan Delivery is successfully updated";
                    \LogActivity::addToLog('Plan Delivery update ',"username: $username Status $message");
                    return response()->json(array('status' => 1, 'message' => $message,'alert'=>$alert));

        } catch (Exception $e) {
            DB::rollBack();
            $alert  ="alert-warning";
            $message  = "Plan Delivery is failed to updated";
            \LogActivity::addToLog('Plan Delivery update ',"username: $username Status $message");
            return response()->json(array('status' => 0, 'message' => $message,'alert'=>$alert));
        }

    }

    public function listDetail(Request $request)
    {
        $tanggal = $request -> tanggal;
        $tanggal = implode("-", array_reverse(explode("-", $tanggal)));
        $randomCode = rand();

        if (!$tanggal){
            DB::select("INSERT into wo_detail_temp
            select $randomCode,article_code, sum(plan) as qty  from del_plan_det group by article_code");
        }else{
            DB::select("INSERT into wo_detail_temp
            select $randomCode,article_code, sum(plan) as qty  from del_plan_det where day='$tanggal' group by article_code");
        }
        

        $data=DB::select("SELECT article_alternative_code
                                ,article_desc
                                ,article.uom
                                ,qty
                                ,qty_proses
                                ,qty_total 
                                ,article.article_type
                                ,(select name from article_types where code = article.article_type) as kelompok 
                            from (
                                select article_code,sum(oki.qty) as qty,sum(mari.qty) as qty_proses,sum(oki.qty*mari.qty) as qty_total 
                                    from (
                                            select * from bom_det where bom_code in (
                                                select bom_code from bom_hdr 
                                                left join wo_detail_temp on bom_hdr.article_code = wo_detail_temp.article_code
                                                where bom_hdr.article_code in (select article_code from wo_detail_temp))) oki
                                            left join(
                                                select bom_code,qty from bom_hdr 
                                                left join wo_detail_temp on bom_hdr.article_code = wo_detail_temp.article_code
                                                where bom_hdr.article_code in (select article_code from wo_detail_temp)) mari
                                            on oki.bom_code= mari.bom_code
                                            group by article_code
                                    ) so
                            left join article on article.article_code = so.article_code");

        DB::table('wo_detail_temp')
        ->where('code',$randomCode)
        ->delete();
                        
        return Datatables::of($data)
        ->make(true);
    }

    // public function articleCodeCreate(Request $request){
    //     $customer = $request->customer;
    //     $leadingCode = 'FG';

    //     $lastCode = DB::table('article')
    //     ->where('third_party','=',$customer)
    //     ->orderBy('article_alternative_code','DESC')->first();

    //     if (!$lastCode){
    //         $newCode = '00001';
    //     }else{
    //         $newCode = str_pad(substr($lastCode->article_alternative_code,5)+1, 5, "0", STR_PAD_LEFT);
    //     }

    //     $artilceCode = DB::table('third_party')
    //     ->where('kode',$customer)
    //     ->select(DB::raw("CONCAT('$leadingCode',inisial,'$newCode') AS new_code"))->value('new_code');

    //     return  Response()->json($artilceCode);
    
    // }

    // public function store(Request $request)
    // {
        
    //     $username =  Auth::user()->username;
    //     $articles = json_decode($request -> articles);
    //     $orderDate = $request->orderDate;
    //     $deliveryDate = $request->deliveryDate;
    //     $currency = $request->currency;
    //     $supplier = $request->supplier;
    //     $tax = $request->tax;
    //     $ppn = $request->ppn;
    //     $termin = $request -> term;
    //     $pph = 0;
    //     $kurs = $request -> kurs;
    //     $totalPpn = $request->totalPpn;
    //     $totalPph = $request->totalPph;
    //     $discount = $request->discount;
    //     $note = $request->note;
    //     $status = '1';

    //     $messages = [
    //         'required' => 'The field is required.',
    //         'unique' => 'The code has already been taken', 
    //         // 'iunique' => "PO Number has already been taken",
    //     ];
        
    //     Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
    //         $query = DB::table($parameters[0]);
    //         $column = $query->getGrammar()->wrap($parameters[1]);
    //         return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
    //     });

    //     $validation = Validator::make($request->all(),$messages = [
    //         // 'woNumber'=>'required|unique:purchase_order_hdr,po_number',
    //         'orderDate'  => 'required',
    //         'currency'  => 'required',
    //         'supplier'  => 'required',
    //     ]);
        
    //     $error_array = array();
    //     $success_output = '';
    //     // return $validation;
    //     if ($validation->fails()){
    //         foreach ($validation->messages()->getMessages() as $field_name => $messages){
    //             $error_array[] = $messages;
    //         }
    //         $alert ="alert-danger";
    //         return response()->json(array('status' => 0, 'message' => $error_array,'alert' =>$alert));
    //     }else{
    //         $woNumber = $this->getLastCode('PO');
    //         DB::beginTransaction();
    //         try {
    //                 DB::table('purchase_order_hdr')->insert([
    //                     'po_number' => $woNumber,
    //                     'supplier_id' => $supplier,
    //                     'po_date' => $orderDate,
    //                     'delivery_date' =>$deliveryDate,
    //                     'currency' => $currency,
    //                     'kurs' => $kurs,
    //                     'ppn' => $ppn,
    //                     'pph22' => $pph,
    //                     'status' => $status,
    //                     'note' =>  $note,
    //                     'authorized_by' => '',
    //                     'prepared_by' =>  '',
    //                     'discount' => $discount,
    //                     'pkp' => $tax,
    //                     'termin' =>$termin,
    //                     'created_by' => Auth::user()->username,
    //                     'updated_by' => Auth::user()->username,
    //                     'created_at' => date('Y-m-d H:i:s'),
    //                     'updated_at' => date('Y-m-d H:i:s')
    //                 ]);

    //                 $dataSet = [];
    //                 foreach ($articles as $val) {
    //                     $dataSet[] = [
    //                         'po_number' => $woNumber,
    //                         'article_code' => $val->article_code,
    //                         'qty' => $val->qty,
    //                         'uom' => $val->uom,
    //                         'old_price' => $val->price,
    //                         'price' => $val->newPrice,
    //                         'ppn' => $totalPpn,
    //                         'pph22' => $totalPph,
    //                         'created_by' => Auth::user()->username,
    //                         'created_at' => date('Y-m-d H:i:s'),
    //                     ];
    //                 }

    //                 DB::table('purchase_order_det')->insert($dataSet);

    //                 DB::commit();
    //                 $alert  ="alert-success";
    //                 $message  = "SO $woNumber is successfully saved";
    //                 \LogActivity::addToLog('SO save ',"username: $username Status $message");
    //                 return response()->json(array('status' => 1, 'message' => $message,'alert'=>$alert,'woNumber'=>$woNumber));

    //         } catch (Exception $e) {
    //             DB::rollBack();
    //             $alert  ="alert-warning";
    //             $message  = "SO $woNumber is failed to save";
    //             \LogActivity::addToLog('SO save ',"username: $username Status $message");
    //             return response()->json(array('status' => 1, 'message' => $message,'alert'=>$alert,'woNumber'=>$woNumber));
    //         }
    //     }
    // }

    // public function show(Request $request)
    // {
    //     $id=$request->id;
    //     $data['title'] = "Edit Working Order";
    //     $data['subtitle'] = "Edit Working Order";

    //     $data['header'] = DB::table('purchase_order_hdr')
    //     ->where('id',$id)
    //     ->get()->first();

    //     $data['detail'] = DB::table('purchase_order_det')
    //     ->leftJoin('article','article.article_code','=','purchase_order_det.article_code')
    //     ->leftJoin('article_stock','article_stock.article_code','=','purchase_order_det.article_code')
    //     ->where('po_number',$data['header']->po_number)
    //     ->select('purchase_order_det'.'.*','article_stock.article_qty as qty_stock', DB::raw('(SELECT name from group_materials where code = group_of_material) as group'))
    //     ->orderBy('id')
    //     ->get();       

    //     $data['articles']= DB::table('article') 
    //     ->leftJoin('article_stock','article_stock.article_code','=','article.article_code')
    //     ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
    //     // ->where('third_party',$data['header']->customer_id)
    //     ->where('article_type','CM')
    //     ->orderBy('article_desc')
    //     ->select('article'.'.*', 'article_stock.article_qty as qty','article.uom as uom1','group_materials.name as group')
    //     ->get();   

    //     $data['supps'] = DB::table('third_party')
    //     ->where ('third_party_type','=','supp')
    //     ->orderBy('nama')
    //     ->get();

    //     $data['currency'] = ['IDR','USD'];

    //     $data['uoms'] = DB::table('uom')
    //     ->orderBy('name')
    //     ->get();

    //     return view("purchaseOrder.show",$data);
        
    // }

    // public function edit(Request $request)
    // {
    //     $id=$request->id;
    //     $data['title'] = "Edit Working Order";
    //     $data['subtitle'] = "Edit Working Order";

    //     $data['header'] = DB::table('purchase_order_hdr')
    //     ->where('id',$id)
    //     ->get()->first();

    //     $data['detail'] = DB::table('purchase_order_det')
    //     ->leftJoin('article','article.article_code','=','purchase_order_det.article_code')
    //     ->leftJoin('article_stock','article_stock.article_code','=','purchase_order_det.article_code')
    //     ->where('po_number',$data['header']->po_number)
    //     ->select('purchase_order_det'.'.*','article_stock.article_qty as qty_stock', DB::raw('(SELECT name from group_materials where code = group_of_material) as group'))
    //     ->orderBy('id')
    //     ->get();       

    //     $data['articles']= DB::table('article') 
    //     ->leftJoin('article_stock','article_stock.article_code','=','article.article_code')
    //     ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
    //     // ->where('third_party',$data['header']->customer_id)
    //     ->where('article_type','CM')
    //     ->orderBy('article_desc')
    //     ->select('article'.'.*', 'article_stock.article_qty as qty','article.uom as uom1','group_materials.name as group')
    //     ->get();   

    //     $data['supps'] = DB::table('third_party')
    //     ->where ('third_party_type','=','supp')
    //     ->orderBy('nama')
    //     ->get();

    //     $data['currency'] = ['IDR','USD'];

    //     $data['uoms'] = DB::table('uom')
    //     ->orderBy('name')
    //     ->get();

    //     return view("purchaseOrder.edit",$data);
        
    // }

    // public function update(Request $request)
    // {
    //     $username =  Auth::user()->username;
    //     $woNumber = $request -> woNumber;
    //     $articles = json_decode($request -> articles);
    //     $orderDate = $request->orderDate;
    //     $deliveryDate = $request->deliveryDate;
    //     $currency = $request->currency;
    //     $supplier = $request->supplier;
    //     $tax = $request->tax;
    //     $ppn = $request->ppn;
    //     $termin = $request -> term;
    //     $pph = 0;
    //     $kurs = $request -> kurs;
    //     $totalPpn = $request->totalPpn;
    //     $totalPph = $request->totalPph;
    //     $discount = $request->discount;
    //     $note = $request->note;
    //     $status = '1';

    //     // status:
    //     // 1 = New
    //     // 2 = Validated
    //     // 3 = Authorized
    //     // 4 = Received
    //     // 5 = Canceled
    //     // 6 = closed
        
    //     $messages = [
    //         'required' => 'The field is required.',
    //         'unique' => 'The code has already been taken', 
    //         // 'iunique' => "PO Number has already been taken",
    //     ];
        
    //     Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
    //         $query = DB::table($parameters[0]);
    //         $column = $query->getGrammar()->wrap($parameters[1]);
    //         return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
    //     });

    //     $validation = Validator::make($request->all(),$messages = [
    //         // 'woNumber'=>'required|unique:purchase_order_hdr,po_number',
    //         // 'orderNumber' => 'required',
    //         'orderDate'  => 'required',
    //         'currency'  => 'required',
    //         'supplier'  => 'required',
    //     ]);
        
    //     $error_array = array();
    //     $success_output = '';
    //     // return $validation;
    //     if ($validation->fails()){
    //         foreach ($validation->messages()->getMessages() as $field_name => $messages){
    //             $error_array[] = $messages;
    //         }
    //         $alert ="alert-danger";
    //         return response()->json(array('status' => 0, 'message' => $error_array,'alert' =>$alert));
    //     }else{
    //         DB::beginTransaction();
    //         try {
    //                 $row_affected=DB::table('purchase_order_hdr')
    //                 ->where('po_number',$woNumber)
    //                 ->update(
    //                     [
    //                         'po_number' => $woNumber,
    //                         'supplier_id' => $supplier,
    //                         'po_date' => $orderDate,
    //                         'delivery_date' =>$deliveryDate,
    //                         'currency' => $currency,
    //                         'kurs' => $kurs,
    //                         'ppn' => $ppn,
    //                         'pph22' => $pph,
    //                         'status' => $status,
    //                         'note' =>  $note,
    //                         'authorized_by' => '',
    //                         'prepared_by' =>  '',
    //                         'discount' => $discount,
    //                         'pkp' => $tax,
    //                         'termin' =>$termin,
    //                         'updated_by' => Auth::user()->username,
    //                         'updated_at' => date('Y-m-d H:i:s')
    //                     ]
    //                 );

    //                 $dataset=[];
    //                 foreach ($articles as $val) {
    //                     $dataSet[] = [
    //                         $woNumber.$val->article_code
    //                     ];
                        
    //                 }

    //                 //Delete kalo article tidak ada di po $woNumber dan article nya $val->article_code
    //                 //berdasarkan 2 kondisi
    //                 DB::table('purchase_order_det')
    //                     ->whereNotIn(DB::raw("CONCAT(po_number,article_code)"),$dataSet)
    //                     ->where('po_number',$woNumber)
    //                     ->delete();

    //                 foreach ($articles as $val) {
    //                     DB::table('purchase_order_det')
    //                     ->updateOrInsert(
    //                         ['po_number' => $woNumber,'article_code' => $val->article_code],
    //                         [
    //                         'po_number' => $woNumber,
    //                         'article_code' => $val->article_code,
    //                         'qty' => $val->qty,
    //                         'uom' => $val->uom,
    //                         'old_price' => $val->price,
    //                         'price' => $val->newPrice,
    //                         'ppn' => $totalPpn,
    //                         'pph22' => $totalPph,
    //                         'updated_by' => Auth::user()->username,
    //                         'updated_at' => date('Y-m-d H:i:s')
    //                         ]
    //                     );
    //                 }
                    
    //                 DB::commit();
    //                 $alert  ="alert-success";
    //                 $message  = "PO $woNumber is successfully updated";
    //                 \LogActivity::addToLog('PO update ',"username: $username Status $message");
    //                 return response()->json(array('status' => 1, 'message' => $message,'alert'=>$alert,'woNumber'=>$woNumber));

    //         } catch (Exception $e) {
    //             DB::rollBack();
    //             $alert  ="alert-warning";
    //             $message  = "PO $woNumber is failed to updated";
    //             \LogActivity::addToLog('PO update ',"username: $username Status $message");
    //             return response()->json(array('status' => 1, 'message' => $message,'alert'=>$alert,'woNumber'=>$woNumber));
    //         }
    //     }

    // }

    // public function destroy(Request $request)
    // {
    //     $username =  Auth::user()->username;       
    //     $id = $request->id;
    //     $po_number = DB::table('purchase_order_hdr')->where('id',$id)->where('status','1')->value('po_number');
    //     $rowAffected = DB::table('purchase_order_hdr')->where('id',$id)->delete();
    //     if($rowAffected>0){
    //         DB::table('purchase_order_det')->where('po_number',$po_number)->delete();
    //         $alert  ="alert-success";
    //         $message  = "SO $po_number Successfully Deleted";
    //         \LogActivity::addToLog('SO delete ',"username: $username Status $message");
    //         return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  
    //     }else{
    //         $alert  ="alert-warning";
    //         $message  = "SO $po_number Failed to Delete";
    //         \LogActivity::addToLog('SO delete ',"username: $username Status $message");
    //         return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
    //     }

    // }

    // public function list(Request $request)
    // {
    //     // status:
    //     // 1 = New
    //     // 2 = Validated
    //     // 3 = Authorized
    //     // 4 = Received
    //     // 5 = Canceled
    //     // 6 = closed

    //     $seachPo = strtolower($request->seachPo);
    //     $searchSupplier = $request->searchSupplier;
    //     $searchStatus = $request->searchStatus;
    //     $orderDate = $request->orderDate;
       

    //     $filter='';
        
    //     if ($seachPo !='' ){
    //         $filter.="lower(a.po_number) like '%$seachPo%' and ";
    //     }

    //     if ($searchSupplier  != '' ){
    //         $filter.="supplier_id = '$searchSupplier' and ";            
    //     }

    //     if ($searchStatus  != '' ){
    //         $filter.="status = '$searchStatus' and ";            
    //     }

    //     if ($orderDate  != '' ){
    //         $date = explode("to",$orderDate);
    //         $date1=trim($date[0]);
    //         $date2=trim($date[1]);
    //         $filter.= "to_date(po_date, 'DD/MM/YYYY')  BETWEEN to_date('$date1', 'DD/MM/YYYY') and to_date('$date2', 'DD/MM/YYYY') and ";
    //     }

        
    //     if ($filter !=''){
    //         $filter=" where ".substr($filter,0,-4);
    //     }

    //     $data=DB::select("SELECT *,delivery_date,(select concat(kode,'-',nama) from third_party where kode = supplier_id limit 1) as supp_name,(gross-discount)+ppn as netto from (
    //         select b.status,b.id,a.po_number,supplier_id,po_date,delivery_date,pkp,termin,authorized_by,prepared_by,uom,sum(qty) as qty,sum(qty*price) as gross,sum(discount) as discount,sum(a.ppn) as ppn from purchase_order_det a
    //         left join purchase_order_hdr b
    //         on a.po_number = b.po_number 
    //         $filter
    //         group by b.id,a.po_number,supplier_id,po_date,delivery_date,pkp,termin,authorized_by,prepared_by,uom,b.status) as oki");
        
    //     // $data=DB::table('purchase_order_hdr')->get();

    //     return Datatables::of($data)
    //     ->addColumn('action', function ($data) {
    //         $buttons = '<div class="d-inline-flex">
    //                         <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
    //                             <i data-feather="menu"></i>
    //                         </a>';
    //         $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
    //         if (Auth::user()->can('purchaseOrder-edit')) {
    //         $buttons .=         '<a href="'. route('purchaseOrder.edit', ['id'=>$data->id]) .'" class="dropdown-item">
    //                                 <i data-feather="file-text"></i>
    //                                 Edit
    //                             </a>';
    //         $buttons .=         '<a href="'. route('purchaseOrder.print', ['id'=>$data->id]) .'" target="_blank" class="dropdown-item">
    //                                 <i data-feather="printer"></i>
    //                                 Print
    //                             </a>';
    //         }
    //         $buttons .=         '<a href="'. route('purchaseOrder.show', ['id'=>$data->id]) .'" class="dropdown-item">
    //                                 <i data-feather="list"></i>
    //                                 Detail
    //                             </a>';
                
    //         if (Auth::user()->can('purchaseOrder-delete')) {
    //         $buttons .=         "<a href='javascript:;'
    //                                 id='deleteButton'
    //                                 class='dropdown-item'
    //                                 data-toggle='modal'
    //                                 data-target='#smallModal'
    //                                 data-href='". route("purchaseOrder.destroy", ["id"=>$data->id]) ."'>
    //                                 <i data-feather='trash-2'></i>
    //                                 Delete
    //                             </a>";
    //         }
    //         $buttons .=     '</div>
    //                     </div>';

    //         return $buttons;
    //         })
    //     ->addColumn('group_id', function ($user) {
    //         return '';
    //     })
    //     ->rawColumns(['action'])
    //     ->make(true);
    // }

    // public function listDetail(Request $request)
    // {
    //     $articles = json_decode($request -> articles);

    //     $dataSet = [];
    //     $randomCode = rand();
    //     foreach ($articles as $val) {
    //         $dataSet[] = [
    //             'code' => $randomCode,
    //             'article_code' => $val->article_code,
    //             'qty' => $val->qty,
    //         ];
    //     }

    //     DB::table('wo_detail_temp')->insert($dataSet);

    //     $data=DB::select("SELECT article_alternative_code,article_desc,article.uom,qty,qty_proses,qty_total ,article.article_type,(select name from article_types where code = article.article_type) as kelompok from (
    //         select article_code,sum(oki.qty) as qty,sum(mari.qty) as qty_proses,sum(oki.qty*mari.qty) as qty_total from (
    //         select * from bom_det where bom_code in (
    //         select bom_code from bom_hdr 
    //         left join wo_detail_temp on bom_hdr.article_code = wo_detail_temp.article_code
    //         where bom_hdr.article_code in (select article_code from wo_detail_temp))) oki
    //         left join(
    //         select bom_code,qty from bom_hdr 
    //         left join wo_detail_temp on bom_hdr.article_code = wo_detail_temp.article_code
    //         where bom_hdr.article_code in (select article_code from wo_detail_temp)) mari
    //         on oki.bom_code= mari.bom_code
    //         group by article_code) so
    //         left join article on article.article_code = so.article_code");

    //     DB::table('wo_detail_temp')
    //     ->where('code',$randomCode)
    //     ->delete();
                        
    //     return Datatables::of($data)
    //     ->make(true);
    // }

    // public function print(Request $request)
    // {
    //     $id = $request -> id;

    //     $data['companies']= array(
    //         "nama"=> "PT ABIMANYU SEKAR NUSANTARA",
    //         "alamat"=> "KP. KARANG MULYA RT 014 RW 005 DESA CIKOPO",
    //         "kota" => "KEC. BUNGURSARI KAB. PURWAKARTA JAWA BARAT",
    //         "tlp" =>  ""
    //     );
        
    //     $data['suppliers']=array(
    //         'nama'=>'PT ABIMANYU SEKAR NUSANTARA',
    //         'alamat'=>'KP. KARANG MULYA RT 014 RW 005 DESA CIKOPO',
    //         'kota' =>'KEC. BUNGURSARI KAB. PURWAKARTA JAWA BARAT',
    //         'tlp' => ''
    //     );
        
    //     $poHdr=DB::table('purchase_order_hdr')
    //     ->where('id',$id)
    //     ->first();

    //     $woNumber=$poHdr -> po_number;
       

    //     $data['details']=DB::table('purchase_order_det')
    //     ->leftJoin('article','article.article_code','purchase_order_det.article_code')
    //     ->where('po_number',$woNumber)
    //     ->get();

    //     $data['totals']=DB::select("SELECT *,(gross-discount)+ppn as netto from (
    //         select a.po_number,authorized_by,prepared_by,sum(qty) as qty,sum(qty*price) as gross,sum(discount) as discount,sum(a.ppn) as ppn from purchase_order_det a
    //         left join purchase_order_hdr b
    //         on a.po_number = b.po_number 
    //         where a.po_number = '$woNumber'
    //         group by a.po_number,authorized_by,prepared_by) as oki");

    //     $data['suppliers']=DB::table('third_party')
    //     ->where('kode',$poHdr -> supplier_id)
    //     ->get();

    //     $data['keterangan']=$poHdr -> note;
    //     $data['woNumber'] =$woNumber;
    //     $data['poDate'] =$poHdr -> po_date;
    //     $data['poTerm'] =$poHdr -> termin;
    //     $data['poDelDate'] =$poHdr -> delivery_date;
        
    //     $data['status'] ='1';
    //     $data['no'] =1;

    //     view()->share($data);

    //     $pdf = PDF::loadView('purchaseOrder.print');
    //     return $pdf->stream("PO_$woNumber.pdf");

    // }
}
