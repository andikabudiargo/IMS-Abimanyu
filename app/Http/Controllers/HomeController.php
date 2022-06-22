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

        $data['greeting'] = self::greeting();            

        return view('home',$data);
    }

}
