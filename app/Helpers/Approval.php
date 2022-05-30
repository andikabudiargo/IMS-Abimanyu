<?php
namespace App\Helpers;
use Request;
use App\Models\ApprovalLevel as ApprovalLevelModel;
use App\Models\ApprovalMaster as ApprovalMasterModel;
use DB;

class Approval
{
    public static function approvalHistory($moduleCode,$moduleNumber,$username)
    {
        /*
        untuk melihat apakah po tersebut sudah di approve belum sesuai dengan kode module
        dan  username nya
        */
    	$approvalHistory = DB::select("SELECT DISTINCT ON (a.approval_order) a.approval_order 
        ,(select name from users where username = a.username) as name
        ,(select STRING_AGG((select name from users where username = p.username),',' ORDER BY module_code) AS main from approval_level p where module_code = a.module_code and approval_order = a.approval_order ) as petugas
        ,(select approval_number from approval_master where module_code = a.module_code) as max_approval,
        case when module_number is not null then true else false end status
        ,b.status as statusApprove
        from approval_level a
        left join (select * from approval_history where module_number = '$moduleNumber') b
        on b.module_code = a.module_code and b.approval_order = a.approval_order and b.username = a.username
        where a.approval_order <= (select approval_number from approval_master where module_code ='$moduleCode')
        and a.module_code = '$moduleCode'
        order by a.approval_order,module_number");

        return $approvalHistory;
    }

    public static function approveValidate($moduleCode,$moduleNumber,$username)
    {
        /*
        untuk mengetahui apakaha user berhak untuk approve atau ngk nyari di history
        dan hak untuk approve nya
        kalo validate true berarti dia berhak untuk approve
        */
    	$approveValidate = DB::select("SELECT username= '$username' as validate,current_level + 1 as next_level,* from (
        select username,approval_order,
        (select max(approval_number) from approval_master where module_code = a.module_code ) as max_level,
        COALESCE((select max(approval_order) from approval_history
        where module_code = a.module_code
        and module_number = '$moduleNumber'),'0') as current_level
        from approval_level a 
        where module_code = '$moduleCode' and username = '$username') b
        where approval_order = current_level+1");

        return $approveValidate;
    }

    public static function approvalLevelPosition($moduleCode,$moduleNumber,$username)
    {
        /*
        untuk mengetahui posisi approval, maksimum, saat ini, selanjutnya
        */
    	$approvalLevelPosition = DB::select("SELECT max_level,current_level,next_level from
        (select 
        (select approval_number from approval_master where module_code = '$moduleCode') as max_level 
        ,coalesce(max(approval_order),0) as current_level
        ,coalesce(max(approval_order),0)+1 as next_level
        from approval_history a
        where module_code ='$moduleCode'
        and module_number = '$moduleNumber'
        ) as oki
        where next_level in 
        (select approval_order from approval_level where module_code = '$moduleCode' and username = '$username')");

        return $approvalLevelPosition;
    }
}