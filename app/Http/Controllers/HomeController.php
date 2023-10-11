<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use DB;
use DataTables;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */

    public function greeting()
    {
        if(date("H") < 12){
            return "Good morning";
        }elseif(date("H") > 11 && date("H") < 18){
            return "Good afternoon";
        }elseif(date("H") > 17){   
            return "Good evening";
        }
    }

    public function index()
    {
        $username =  Auth::user()->username;
        $data['tanggal'] = Carbon::now()->format('l').','.Carbon::now()->format('M Y');
        $data['listPoHome'] = DB::select("SELECT * from (
        select 
            id
            ,supplier_id
            ,po_number
            ,po_date
            ,created_by
            ,validate_by
            ,status
            ,'$username' as username
            ,coalesce((select max(approval_order) from approval_history where module_code ='PO' and module_number =a.po_number),0) as current_level
            ,(select approval_number from approval_master where module_code = 'PO') as max_level
            ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'PO' and approval_order not in(
	        select approval_order from approval_history where username = '$username' and module_code = 'PO' and module_number = a.po_number)),0) as berhak_approve
            ,(SELECT sum(qty*price) from purchase_order_det where po_number = a.po_number) as po_amount
            ,(select nama from third_party where kode = supplier_id) as supplier_name
        from purchase_order_hdr a
        where status not in ('3','4','5','6','7','8')
        ) as Oki
        where current_level+1 = berhak_approve");

        $data['listBomHome'] = DB::select("SELECT * from (
        select 
            id
            ,bom_code
            ,created_by
            ,(select article_desc from article where article_code = a.article_code) as article_fg
            ,(select article_desc from article where article_code = a.article_code_rm) as article_rm
            ,status
            ,'$username' as username
            ,coalesce((select max(approval_order) from approval_history where module_code ='BOM' and module_number =a.bom_code),0) as current_level
            ,(select approval_number from approval_master where module_code = 'BOM') as max_level
            ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'BOM' and approval_order not in(
	        select approval_order from approval_history where username = '$username' and module_code = 'BOM' and module_number = a.bom_code)),0) as berhak_approve
            ,(select nama from third_party where kode = customer) as customer_name
        from bom_hdr a
        where status not in ('3','4','5','6','7','8')
        ) as Oki
        where current_level+1 = berhak_approve");

        $data['listPrHome'] = DB::select("SELECT * from (
        select 
            id
            ,pr_number
            ,date
            ,dept
            ,order_type
            ,note
            ,created_by
            ,status
            ,'$username' as username
            ,coalesce((select max(approval_order) from approval_history where module_code ='PR' and module_number =a.pr_number),0) as current_level
            ,(select approval_number from approval_master where module_code = 'PR') as max_level
            ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'PR' and username in (select username from user_dept where dept = a.dept and username = '$username')
            and approval_order not in( select approval_order from approval_history where username = '$username' and module_code = 'PR' and module_number = a.pr_number)),0) as berhak_approve
            ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'PR'
            and username in (select username from user_dept where dept = a.dept and username = '$username')
            and approval_order not in(
            select approval_order from approval_history where username = '$username' and module_code = 'PR' and module_number = a.pr_number)),0) as berhak_approve1
        from purchase_request_hdr a
        where status not in ('3','4','5','6','7','8')
        ) as Oki
        where current_level+1 = berhak_approve");

        $data['listSoHome'] = DB::select("SELECT * from (
        select id
        ,so_code
        ,so_date
        ,po_number
        ,'$username' as username
        ,note
        ,status
        ,coalesce((select max(approval_order) from approval_history where module_code ='SO' and module_number =sales_order_hdr.so_code),0) as current_level
        ,(select approval_number from approval_master where module_code = 'SO') as max_level
        ,coalesce((select max(approval_order) from approval_history where module_code = 'SO' and module_number = so_code),0) as sudah_approve,
        coalesce((select approval_order from approval_level where username = '$username' and module_code = 'SO' limit 1),0) as berhak_approve,
        (select nama from third_party where kode = customer_id) as customer_name
        from sales_order_hdr 
        where status <> '3'
        ) as Oki
        where berhak_approve-1 = sudah_approve");
        
        $data['listTsoHome'] = DB::select("SELECT * from (
        select 
            id
            ,tso_code
            ,tso_date
            ,tso_name
            ,note
            ,created_by
            ,status
            ,'$username' as username
            ,coalesce((select max(approval_order) from approval_history where module_code ='TSO' and module_number =a.tso_code),0) as current_level
            ,(select approval_number from approval_master where module_code = 'TSO') as max_level
            ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'TSO' and approval_order not in(
            select approval_order from approval_history where username = '$username' and module_code = 'TSO' and module_number = a.tso_code)),0) as berhak_approve
        from target_order_hdr a
        where status not in ('3','4','5','6','7','8')
        ) as Oki
        where current_level+1 = berhak_approve");

        //bom yang status nya approved 2 minggu ke belakang
        $data['listBom']=DB::select("SELECT bom_code, customer
        ,(select nama from third_party where kode = customer) as customer_name
        ,(select article_alternative_code from article where article_code = bom_hdr.article_code) as article_code
        ,(select article_desc from article where article_code = bom_hdr.article_code) as article_name
        ,note,created_at,updated_at from bom_hdr where status ='3' and  updated_at >= now() - interval '2 week'");

        $data['listDnHome'] = DB::select("SELECT * from (
         select 
            id
            ,delivery_number
            ,delivery_date
            ,po_number
            ,note
            ,created_by
            ,status
            ,'$username' as username
            ,coalesce((select max(approval_order) from approval_history where module_code ='DN' and module_number =a.delivery_number),0) as current_level
            ,(select approval_number from approval_master where module_code = 'DN') as max_level
            ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'DN' and approval_order not in(
            select approval_order from approval_history where username = '$username' and module_code = 'DN' and module_number = a.delivery_number)),0) as berhak_approve
        from delivery_hdr a
        where status in ('10')
        ) as Oki
        where current_level+1 = berhak_approve");

        $data['bomCount'] = count($data['listBom']);
        $data['greeting'] = self::greeting(); 
        
        return view('home',$data);
    }

}
