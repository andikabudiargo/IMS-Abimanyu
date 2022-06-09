<?php

namespace App\Http\ViewComposers;
use Illuminate\Support\Facades\Auth;

use Illuminate\View\View;
// use App\Repositories\UserRepository;
use DB;

class NotificationComposer
{
    public function compose(View $view)
    {
        $username =  Auth::user() ? Auth::user()->username : '';
        // $listPo = DB::table('purchase_order_hdr')
        // // ->where('status','2')
        // // ->where('authorized_by',"")
        // ->select('purchase_order_hdr'.'.*', DB::raw('(SELECT sum(qty*price) from purchase_order_det where po_number = purchase_order_hdr.po_number) as po_amount'))
        // ->orderBy('id')
        // ->get();

        $lists['listSo2'] = DB::select("SELECT * from (
        select id,so_code,so_date,'$username' as username,
        coalesce((select max(approval_order) from approval_history where module_code = 'SO' and module_number = so_code),0) as sudah_approve,
        coalesce((select approval_order from approval_level where username = '$username' and module_code = 'SO'),0) as berhak_approve,
        (select nama from third_party where kode = customer_id) as customer_name
        from sales_order_hdr 
        --where status <> '3'
        ) as Oki
        where berhak_approve-1 = sudah_approve");

        // $lists['listSo2'] = DB::select("SELECT * from (
        // select 
        // id
        // ,so_code
        // ,so_date
        // ,status
        // ,'$username' as username
        // ,coalesce((select max(approval_order) from approval_history where module_code ='SO' and module_number =a.po_number),0) as current_level
        // ,(select approval_number from approval_master where module_code = 'SO') as max_level
        // ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'PO'),0) as berhak_approve
        // ,(SELECT sum(qty*price) from purchase_order_det where po_number = a.po_number) as po_amount
        // ,(select nama from third_party where kode = supplier_id) as supplier_name
        // from sales_order_hdr a
        // where status not in ('3','4','5','6','7','8')
        // ) as Oki
        // where current_level+1 = berhak_approve");

        $lists['listPo2'] = DB::select("SELECT * from (
        select 
            id
            ,po_number
            ,po_date
            ,status
            ,'$username' as username
            ,coalesce((select max(approval_order) from approval_history where module_code ='PO' and module_number =a.po_number),0) as current_level
            ,(select approval_number from approval_master where module_code = 'PO') as max_level
            ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'PO'),0) as berhak_approve
            ,(SELECT sum(qty*price) from purchase_order_det where po_number = a.po_number) as po_amount
            ,(select nama from third_party where kode = supplier_id) as supplier_name
        from purchase_order_hdr a
        where status not in ('3','4','5','6','7','8')
        ) as Oki
        where current_level+1 = berhak_approve");

        $view->with($lists);
    }
}