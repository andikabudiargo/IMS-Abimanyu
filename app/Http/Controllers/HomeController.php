<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use DB;
use DataTables;

/*
    4-2-2026 : ada perbaikan untuk antisipasi revisi ke approved 
*/

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

    private static function formatAgingHome(float $seconds): array
{
    $seconds = (int) abs($seconds);

    if ($seconds < 60) {
        return ['label' => $seconds . ' detik', 'level' => 'success'];
    } elseif ($seconds < 3600) {
        return ['label' => floor($seconds / 60) . ' menit', 'level' => 'success'];
    } elseif ($seconds < 86400) {
        return ['label' => floor($seconds / 3600) . ' jam', 'level' => 'warning'];
    } elseif ($seconds < 259200) {
        return ['label' => floor($seconds / 86400) . ' hari', 'level' => 'warning'];
    }
    return ['label' => floor($seconds / 86400) . ' hari', 'level' => 'danger'];
}

    public function index()
    {

        $username =  Auth::user() ? Auth::user()->username : '';
        $adaModule = db::table('approval_level')
        ->where('username',$username)
        ->where('approval_order','>',1)
        ->distinct()
        ->pluck('module_code')->toarray();

        $lists['jumlahSo'] = 0;
        $lists['jumlahPo'] = 0;
        $lists['jumlahBom'] = 0;
        $lists['jumlahPr'] = 0;
        $lists['jumlahTso'] = 0;
        $lists['jumlahDn'] = 0;
        $lists['jumlahAp'] = 0;
        $lists['jumlahAr'] = 0;
        $lists['jumlahRec'] = 0;
        $lists['jumlahBm'] = 0;
        $lists['jumlahBk'] = 0;
        $lists['jumlahKm'] = 0;
        $lists['jumlahKk'] = 0;
        $lists['jumlahGj'] = 0;
        $lists['jumlahDebitNote'] = 0;

        $username =  Auth::user()->username;
        $data['tanggal'] = Carbon::now()->format('l').','.Carbon::now()->format('d M Y');

        // if (in_array("PO", $adaModule)){
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
                -- where status not in ('3','4','5','6','7','8')
                where status in ('2')
            ) as Oki
            where current_level+1 = berhak_approve");
        // }

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
            -- where status not in ('3','4','5','6','7','8')
            where status in ('1','2')
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
            -- where status not in ('3','4','5','6','7','8','9')
            where status in ('1','2')
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
            -- where status <> '3'
            where status in ('1','2')
            ) as Oki
        where berhak_approve-1 = sudah_approve");

        // $data['listSoHome'] = DB::select("SELECT * from (
        //     select id
        //     ,so_code
        //     ,so_date
        //     ,po_number
        //     ,'$username' as username
        //     ,note
        //     ,status
        //     ,coalesce((select max(approval_order) from approval_history where module_code ='SO' and module_number =sales_order_hdr.so_code),0) as current_level
        //     ,(select approval_number from approval_master where module_code = 'SO') as max_level
        //     ,coalesce((select max(approval_order) from approval_history where module_code = 'SO' and module_number = so_code),0) as sudah_approve,
        //     coalesce((select approval_order from approval_level where username = '$username' and module_code = 'SO' limit 1),0) as berhak_approve,
        //     (select nama from third_party where kode = customer_id) as customer_name
        //     from sales_order_hdr 
        //     where status <> '3'
        //     ) as Oki
        // where berhak_approve-1 = sudah_approve");
            
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
            -- where status not in ('3','4','5','6','7','8')
            where status in ('1','2')
            ) as Oki
        where current_level+1 = berhak_approve");

        // $data['listTsoHome'] = DB::select("SELECT * from (
        //     select 
        //         id
        //         ,tso_code
        //         ,tso_date
        //         ,tso_name
        //         ,note
        //         ,created_by
        //         ,status
        //         ,'$username' as username
        //         ,coalesce((select max(approval_order) from approval_history where module_code ='TSO' and module_number =a.tso_code),0) as current_level
        //         ,(select approval_number from approval_master where module_code = 'TSO') as max_level
        //         ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'TSO' and approval_order not in(
        //         select approval_order from approval_history where username = '$username' and module_code = 'TSO' and module_number = a.tso_code)),0) as berhak_approve
        //     from target_order_hdr a
        //     -- where status not in ('3','4','5','6','7','8')
        //     where status in ('1','2')
        //     ) as Oki
        // where current_level+1 = berhak_approve");

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

        $data['listBkHome'] = DB::select("SELECT * from (
            select 
                id
                ,voucher_number
                ,voucher_date
                ,description
                ,note
                ,created_by
                ,status
                ,'$username' as username
                ,coalesce((select max(approval_order) from approval_history where module_code ='BK' and module_number =a.voucher_number),0) as current_level
                ,(select approval_number from approval_master where module_code = 'BK') as max_level
                ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'BK' and approval_order not in(
                select approval_order from approval_history where username = '$username' and module_code = 'BK' and module_number = a.voucher_number)),0) as berhak_approve
            from kas_hdr a
            where status in ('2')
            and voucher_type = 'BK'
            ) as Oki
        where current_level+1 = berhak_approve");

        $data['listBmHome'] = DB::select("SELECT * from (
            select 
                id
                ,voucher_number
                ,voucher_date
                ,description
                ,note
                ,created_by
                ,status
                ,'$username' as username
                ,coalesce((select max(approval_order) from approval_history where module_code ='BM' and module_number =a.voucher_number),0) as current_level
                ,(select approval_number from approval_master where module_code = 'BM') as max_level
                ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'BM' and approval_order not in(
                select approval_order from approval_history where username = '$username' and module_code = 'BM' and module_number = a.voucher_number)),0) as berhak_approve
            from kas_hdr a
            where status in ('2')
            and voucher_type = 'BM'
            ) as Oki
        where current_level+1 = berhak_approve");

        $data['listKmHome'] = DB::select("SELECT * from (
            select 
                id
                ,voucher_number
                ,voucher_date
                ,description
                ,note
                ,created_by
                ,status
                ,'$username' as username
                ,coalesce((select max(approval_order) from approval_history where module_code ='KM' and module_number =a.voucher_number),0) as current_level
                ,(select approval_number from approval_master where module_code = 'KM') as max_level
                ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'KM' and approval_order not in(
                select approval_order from approval_history where username = '$username' and module_code = 'KM' and module_number = a.voucher_number)),0) as berhak_approve
            from kas_hdr a
            where status in ('2')
            and voucher_type = 'KM'
            ) as Oki
        where current_level+1 = berhak_approve");

        $data['listKkHome'] = DB::select("SELECT * from (
            select 
                id
                ,voucher_number
                ,voucher_date
                ,description
                ,note
                ,created_by
                ,status
                ,'$username' as username
                ,amount
                ,coalesce((select max(approval_order) from approval_history where module_code ='KK' and module_number =a.voucher_number),0) as current_level
                ,(select approval_number from approval_master where module_code = 'KK') as max_level
                ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'KK' and approval_order not in(
                select approval_order from approval_history where username = '$username' and module_code = 'KK' and module_number = a.voucher_number)),0) as berhak_approve
            from kas_hdr a
            where status in ('2')
            and voucher_type = 'KK'
            ) as Oki
        where current_level+1 = berhak_approve");

        $data['listGjHome'] = DB::select("SELECT * from (
            select 
                id
                ,voucher_number
                ,voucher_date
                ,description
                ,note
                ,created_by
                ,status
                ,'$username' as username
                ,coalesce((select max(approval_order) from approval_history where module_code ='GJ' and module_number =a.voucher_number),0) as current_level
                ,(select approval_number from approval_master where module_code = 'GJ') as max_level
                ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'GJ' and approval_order not in(
                select approval_order from approval_history where username = '$username' and module_code = 'GJ' and module_number = a.voucher_number)),0) as berhak_approve
            from kas_hdr a
            where status in ('2')
            and voucher_type = 'GJ'
            ) as Oki
        where current_level+1 = berhak_approve");

        $data['listApHome'] = DB::select("SELECT * from (
            select 
                id
                ,ap_number
                ,inv_date
                ,po_number
                ,note
                ,ap_date
                ,created_by
                ,status
                ,'$username' as username
                ,coalesce((select max(approval_order) from approval_history where module_code ='AP' and module_number =a.ap_number),0) as current_level
                ,(select approval_number from approval_master where module_code = 'AP') as max_level
                ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'AP' and approval_order not in(
                select approval_order from approval_history where username = '$username' and module_code = 'AP' and module_number = a.ap_number)),0) as berhak_approve
            from ap_invoice a
            where status in ('2')
            ) as Oki
        where current_level+1 = berhak_approve");

        $data['listArHome'] = DB::select("SELECT * from (
            select 
                id
                ,invoice_number
                ,invoice_date
                ,po_number
                ,so_number
                ,note
                ,created_by
                ,status
                ,'$username' as username
                ,coalesce((select max(approval_order) from approval_history where module_code ='INV' and module_number =a.invoice_number),0) as current_level
                ,(select approval_number from approval_master where module_code = 'INV') as max_level
                ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'INV' and approval_order not in(
                select approval_order from approval_history where username = '$username' and module_code = 'INV' and module_number = a.invoice_number)),0) as berhak_approve
            from invoice_hdr a
            where status in ('2')
            ) as Oki
        where current_level+1 = berhak_approve");

        $data['listRecHome'] =DB::select("SELECT * from (
            select 
                id
                ,rec_number
                ,rec_date
                ,do_number
                ,po_number
                ,note
                ,created_by
                ,status
                ,'$username' as username
                ,coalesce((select max(approval_order) from approval_history where module_code ='REC' and module_number =a.rec_number),0) as current_level
                ,(select approval_number from approval_master where module_code = 'REC') as max_level
                ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'REC' and approval_order not in(
                select approval_order from approval_history where username = '$username' and module_code = 'REC' and module_number = a.rec_number)),0) as berhak_approve
            from receiving_hdr a
            where status in ('10')
            ) as Oki
        where current_level+1 = berhak_approve");     

        $data['listDebNoteHome'] = DB::select("SELECT * from (
            select 
                id
                ,dn_number
                ,dn_date
                ,po_number
                ,note
                ,created_by
                ,status
                ,'$username' as username
                ,coalesce((select max(approval_order) from approval_history where module_code ='INV-DN' and module_number =a.dn_number),0) as current_level
                ,(select approval_number from approval_master where module_code = 'INV-DN') as max_level
                ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'INV-DN' and approval_order not in(
                select approval_order from approval_history where username = '$username' and module_code = 'INV-DN' and module_number = a.dn_number)),0) as berhak_approve
            from debit_note_hdr a
            where status in ('2')
            ) as Oki
        where current_level+1 = berhak_approve");

        // Cek dept user (pakai tabel user_dept yang sudah dipakai di query PR)
$userDepts = DB::table('user_dept')
    ->where('username', $username)
    ->pluck('dept')
    ->toArray();

// ===== Cek akses Critical Stock Alert =====
$userDepts = DB::table('user_dept')
    ->where('username', $username)
    ->pluck('dept') // sesuaikan nama kolom kalau bukan 'dept'
    ->toArray();

$allowedDeptCriticalStock = ['005', '008']; // 005 = Logistik, 008 = Purchasing
$hasAllowedDept = count(array_intersect($userDepts, $allowedDeptCriticalStock)) > 0;
$hasPrivilegedRole = Auth::user()->hasAnyRole(['Superuser', 'accounting']);

$data['showCriticalStock'] = $hasAllowedDept || $hasPrivilegedRole;

if ($data['showCriticalStock']) {
    $allowedLocations = ['009', '005', '006']; // RM, Chemical, Consumable, FG

    $data['listCriticalStock'] = DB::table('warehouse_stock as ws')
        ->join('article as a', 'a.article_code', '=', 'ws.article_code')
        ->leftJoin('third_party as tp', 'tp.kode', '=', 'a.third_party')
        ->leftJoin('stock_location_master as loc', 'loc.location_code', '=', 'ws.location_number')
        ->whereIn('ws.location_number', $allowedLocations)
        ->select(
            'a.article_code',
            'a.article_alternative_code as code',
            'a.article_desc as name',
            'a.uom',
            DB::raw('coalesce(a.safety_stock,0) as safety_stock'),
            'loc.location_name',
            DB::raw('coalesce(ws.article_qty,0) as stock_qty'),
            'tp.nama as supplier_name'
        )
       ->where(function($q){
    $q->whereRaw('coalesce(ws.article_qty,0) < coalesce(a.safety_stock,0)')
      ->orWhere(function($q2){
          $q2->whereNull('a.safety_stock')->where('ws.article_qty', '<=', 0);
      });
})
        ->orderBy('ws.location_number', 'asc')
        ->orderBy('a.article_alternative_code', 'asc')
        ->get();
} else {
    $data['listCriticalStock'] = collect();
}
$data['criticalStockCount'] = $data['listCriticalStock']->count();

        // ===== Transfer Stock yang perlu diposting (masuk ke gudang dept saya) =====
$userDepts = DB::table('user_dept')
    ->where('username', $username)
    ->pluck('dept')
    ->toArray();

$data['outstandingTransferIn'] = DB::table('transfer_stock_hdr')
    ->leftJoin('stock_location_master as locFrom', 'locFrom.location_code', '=', 'transfer_stock_hdr.location_from')
    ->leftJoin('stock_location_master as locTo',   'locTo.location_code',   '=', 'transfer_stock_hdr.location_to')
    ->whereIn('transfer_stock_hdr.status', ['1', '2'])
    ->whereIn('transfer_stock_hdr.approve_dept', $userDepts)
    ->select(
        'transfer_stock_hdr.*',
        'locFrom.location_name as location_name',
        'locTo.location_name as location_name_to'
    )
    ->orderBy('transfer_stock_hdr.created_at', 'asc')
    ->get()
    ->map(function ($row) {
        $created = Carbon::parse($row->created_at);
        $seconds = max(0, $created->diffInSeconds(now(), false));
        $row->age_seconds = $seconds;
        $aging = self::formatAgingHome($seconds);
        $row->aging_label = $aging['label'];
        $row->aging_level = $aging['level'];
        return $row;
    });

$data['outstandingTransferInCount'] = $data['outstandingTransferIn']->count();
        $data['bomCount'] = count($data['listBom']);
        $data['greeting'] = self::greeting(); 
        
        return view('home',$data);
    }

}
