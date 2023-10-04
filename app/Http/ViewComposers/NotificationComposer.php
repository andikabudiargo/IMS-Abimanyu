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
        $adaModule = db::table('approval_level')->where('username',$username)->distinct()->pluck('module_code')->toarray();
        
        // dd($adaModule);

        // dd(in_array("OKI", $adaModule));
        
        // $lists['listSo2'] = DB::select("SELECT * from (
        //     select id,so_code,so_date,'$username' as username,
        //     coalesce((select max(approval_order) from approval_history where module_code = 'SO' and module_number = so_code),0) as sudah_approve,
        //     coalesce((select approval_order from approval_level where username = '$username' and module_code = 'SO' limit 1),0) as berhak_approve,
        //     (select nama from third_party where kode = customer_id) as customer_name
        //     from sales_order_hdr 
        //     where status <> '3'
        //     ) as Oki
        // where berhak_approve-1 = sudah_approve");

        $lists['jumlahSo'] = 0;
        if (in_array("PO", $adaModule)){
            $bisaApproveSo = DB::select("SELECT count(*) as jumlah from (
                select 
                coalesce((select max(approval_order) from approval_history where module_code ='SO' and module_number =a.so_code),0) as current_level
                ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'SO'),0) as berhak_approve
                from sales_order_hdr a
                where status in ('1','2')
                ) as Oki
            where current_level+1 = berhak_approve");

            if($bisaApproveSo[0]->jumlah >0 ){
                $lists['listSo2'] = DB::select("SELECT * from (
                    select 
                    id
                    ,so_code
                    ,so_date
                    ,status
                    ,'$username' as username
                    ,coalesce((select max(approval_order) from approval_history where module_code ='SO' and module_number =a.so_code),0) as current_level
                    ,(select approval_number from approval_master where module_code = 'SO') as max_level
                    ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'SO'),0) as berhak_approve
                    ,(select nama from third_party where kode = customer_id) as customer_name
                    from sales_order_hdr a
                    where status in ('1','2')
                    ) as Oki
                where current_level+1 = berhak_approve");
                $lists['jumlahSo'] = count($lists['listSo2']);
            }
        }

        $lists['jumlahPo'] = 0;
        if (in_array("PO", $adaModule)){
            $bisaApprovePo = DB::select("SELECT count(*) as jumlah from (
                select 
                coalesce((select max(approval_order) from approval_history where module_code ='PO' and module_number =a.po_number),0) as current_level
                -- ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'PO' and approval_order not in(
                -- select approval_order from approval_history where username = '$username' and module_code = 'PO' and module_number = a.po_number)),0) as berhak_approve
                ,(select count(*) from approval_level 
                    where username = '$username' 
                    and module_code = 'PO' 
                    and approval_order = (coalesce((select max(approval_order) 
                                                     from approval_history 
                                                     where module_code ='PO' 
                                                     and module_number =a.po_number 
                                                     and status = '1' ),0)+1)) as berhak_approve
                from purchase_order_hdr a
                where status in ('1','2')
                ) as Oki
            where berhak_approve=1");

            if($bisaApprovePo[0]->jumlah >0 ){
                $lists['listPoNotif'] = DB::select("SELECT * from (
                select 
                    id
                    ,po_number
                    ,po_date
                    ,status
                    ,'$username' as username
                    ,coalesce((select max(approval_order) from approval_history where module_code ='PO' and module_number =a.po_number),0) as current_level
                    ,(select approval_number from approval_master where module_code = 'PO') as max_level
                    -- ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'PO' and approval_order not in(
                    -- select approval_order from approval_history where username = '$username' and module_code = 'PO' and module_number = a.po_number)),0) as berhak_approve
                    ,(select count(*) from approval_level 
                    where username = '$username' 
                    and module_code = 'PO' 
                    and approval_order = (coalesce((select max(approval_order) 
                                                     from approval_history 
                                                     where module_code ='PO' 
                                                     and module_number =a.po_number 
                                                     and status = '1' ),0)+1)) as berhak_approve
                    ,(SELECT sum(qty*price) from purchase_order_det where po_number = a.po_number) as po_amount
                    ,(select nama from third_party where kode = supplier_id) as supplier_name
                from purchase_order_hdr a
                where status in ('1','2')
                ) as Oki
                where berhak_approve=1
                order by po_number");

                $lists['jumlahPo'] = count($lists['listPoNotif']);    
            }
        }
        
        $lists['jumlahBom'] = 0;
        if (in_array("BOM", $adaModule)){

            $bisaApproveBom = DB::select("SELECT  count(*) as jumlah from (
                select 
                coalesce((select max(approval_order) from approval_history where module_code ='BOM' and module_number =a.bom_code),0) as current_level
                ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'BOM' and approval_order not in(
                select approval_order from approval_history where username = '$username' and module_code = 'BOM' and module_number = a.bom_code)),0) as berhak_approve
                ,(select nama from third_party where kode = customer) as customer_name
                from bom_hdr a
                where status in ('1','2')
                ) as Oki
            where current_level+1 = berhak_approve");

            if($bisaApproveBom[0]->jumlah >0 ){
                $lists['listBomNotif'] = DB::select("SELECT * from (
                select 
                    id
                    ,bom_code
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
                where status in ('1','2')
                ) as Oki
                where current_level+1 = berhak_approve");
                $lists['jumlahBom'] = count($lists['listBomNotif']);
            }
        }

        $lists['jumlahPr'] = 0;
        if (in_array("PR", $adaModule)){
            $bisaApprovePr =DB::select("SELECT  count(*) as jumlah  from (
                select 
                    coalesce((select max(approval_order) from approval_history where module_code ='PR' and module_number =a.pr_number),0) as current_level
                    ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'PR' and username in (select username from user_dept where dept = a.dept and username = '$username')
                    and approval_order not in( select approval_order from approval_history where username = '$username' and module_code = 'PR' and module_number = a.pr_number)),0) as berhak_approve
                from purchase_request_hdr a
                where status in ('1','2')
                ) as Oki
            where current_level+1 = berhak_approve");
        
            if($bisaApprovePr[0]->jumlah > 0 ){
                $lists['listPrNotif'] = DB::select("SELECT * from (
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
                    ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'PR' and approval_order not in(
                    select approval_order from approval_history where username = '$username' and module_code = 'PR' and module_number = a.pr_number)),0) as berhak_approve1
                from purchase_request_hdr a
                where status in ('1','2')
                ) as Oki
                where current_level+1 = berhak_approve");

                // dd($lists['listPrNotif']);

                $lists['jumlahPr'] = count($lists['listPrNotif']);
            }
        }
                
        $lists['jumlahTso'] = 0;
        if (in_array("TSO", $adaModule)){
            $bisaApproveTso =DB::select("SELECT  count(*) as jumlah from (
                select 
                    coalesce((select max(approval_order) from approval_history where module_code ='TSO' and module_number =a.tso_code),0) as current_level
                    ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'TSO' and approval_order not in(
                    select approval_order from approval_history where username = '$username' and module_code = 'TSO' and module_number = a.tso_code)),0) as berhak_approve
                from target_order_hdr a
                where status in ('1','2')
                ) as Oki
            where current_level+1 = berhak_approve");
    
            if($bisaApproveTso[0]->jumlah >0 ){
                $lists['listTsoNotif'] = DB::select("SELECT * from (
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
                where status in ('1','2')
                ) as Oki
                where current_level+1 = berhak_approve");
                $lists['jumlahTso'] = count($lists['listTsoNotif']);
            }
        }

        // dd($bisaApproveSo[0]->jumlah);
               
        $view->with($lists);
    }
}