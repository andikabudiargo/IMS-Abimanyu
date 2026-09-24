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

class LockTransactionController extends Controller
{
    private $title;
    private $decimalPlaces;
    public function __construct()
    {
        $this->title = "Lock Transaction";
        $this->decimalPlaces = config('globalParam.decimal');
    }

    /**
     * Daftar modul yang bisa dikunci (single source of truth — code_key-nya
     * sama persis dengan $this->moduleCode di masing-masing controller).
     *   'period'    => modul punya field tanggal (period lock berlaku)
     *   'overstock' => modul mengurangi stok (overstock lock berlaku)
     *   'group'     => label pengelompokan di halaman Lock Transaction
     * Activity lock berlaku untuk semua modul di daftar ini.
     */
    public static function lockableModules(): array
    {
        return [
            // code_key    => [nama,                    period, overstock, group]
            'REC'          => ['Receiving',              true,  false, 'Inventory'],
            'DN'           => ['Delivery',                true,  true,  'Inventory'],
            'TRF'          => ['Transfer Stock',          true,  true,  'Inventory'],
            'DN-UMUM'      => ['Temporary DN',            true,  true,  'Inventory'],
            'DN-GENERAL'   => ['DN General',              true,  true,  'Inventory'],
            'DN-RETURN'    => ['DN Return',               true,  false, 'Inventory'],
            'DN-REPLACE'   => ['DN Replace',              true,  false, 'Inventory'],
            'REC-RETURN'   => ['Supplier Return',         true,  true,  'Inventory'],
            'REC-REPLACE'  => ['Supplier Replace',        true,  false, 'Inventory'],
            'ADJ'          => ['Stock Adjustment',        true,  false, 'Inventory'],
            'ALP'          => ['Actual Loading',          true,  true,  'Inventory'],
            'SCO'          => ['Stock Consumption',       true,  true,  'Inventory'],
            'STO'          => ['Stock Taking Order',      true,  false, 'Inventory'],
            'ART'          => ['Article',                 false, false, 'Inventory'],

            'PO'           => ['Purchase Order',          true,  false, 'Sales & Purchase'],
            'SO'           => ['Sales Order',             true,  false, 'Sales & Purchase'],

            'INV'          => ['Invoice Customer',        true,  false, 'Akunting'],
            'AP'           => ['Invoice Supplier',        true,  false, 'Akunting'],
            'INV-DN'       => ['Debit Note',              true,  false, 'Akunting'],
            'BK'           => ['Bank Keluar',             true,  false, 'Akunting'],
            'BM'           => ['Bank Penerimaan',         true,  false, 'Akunting'],
            'KK'           => ['Kas Keluar',              true,  false, 'Akunting'],
            'KM'           => ['Kas Penerimaan',          true,  false, 'Akunting'],
            'GJ'           => ['General Journal',         true,  false, 'Akunting'],
        ];
    }

    public function index(Request $request)
    {
        $data['title'] = "$this->title";

        $locks = DB::table('application_lock')
            ->where('status', '1')
            ->orderBy('id', 'desc')
            ->get()
            ->keyBy('code_key');   // baris aktif terbaru per code_key

        $groupOrder = ['Inventory', 'Sales & Purchase', 'Akunting'];

        $menus = collect();
        foreach (self::lockableModules() as $code => [$name, $period, $overstock, $group]) {
            $lock = $locks->get($code);
            $menus->push((object)[
                'code_key'       => $code,
                'module_name'    => $name,
                'group'          => $group,
                'has_period'     => $period,
                'has_overstock'  => $overstock,
                'lock_date'      => ($lock && $lock->lock_date) ? date('d-m-Y', strtotime($lock->lock_date)) : null,
                'activity_lock'  => $lock ? (bool) $lock->activity_lock : false,
                'overstock_lock' => $lock ? (bool) $lock->overstock_lock : false,
                'created_by'     => $lock->created_by ?? null,
                'created_at'     => ($lock && $lock->created_at) ? date('d-m-Y H:i:s', strtotime($lock->created_at)) : null,
            ]);
        }

        // Urut per group (sesuai $groupOrder), lalu nama modul di dalam group.
        $data['menus'] = $menus
            ->sortBy(fn($m) => sprintf('%02d-%s', array_search($m->group, $groupOrder), $m->module_name))
            ->values()
            ->groupBy('group');

        return view("lockTransaction.index", $data);
    }

    public function indexOld(Request $request)
    {
        $data['title'] = "$this->title";
        
        $data['menus'] = DB::table('application_lock')
        ->leftJoin('approval_master','module_code','code_key')
        ->where('status','1')
        ->select('application_lock.*'
        ,'module_name'
        ,db::raw("to_char(lock_date, 'dd-mm-yyyy') as lock_date")
        ,db::raw("to_char(application_lock.created_at, 'dd-mm-yyyy hh:ss:mm') as created_at")
        )
        ->orderBy('module_name')
        ->get();

        return view("lockTransaction.index",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $codeKey = $request->codeKey;
        $newDate = $request->newDate;
        $dateBefore = $request->dateBefore;
        $activity = $request->activityLock ?? [];
        $overstock = $request->overstockLock ?? [];

        DB::table('application_lock')
        ->where('status','1')
        ->update([
            'status'=> 0,
            'updated_by' => Auth::user()->username,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        foreach($codeKey as $index=>$val){
            $lockDate = $newDate[$index] ? date('Y/m/d', strtotime($newDate[$index])) : null ;
            if( ($lockDate == null) && $dateBefore[$index] ){
                $lockDate = date('Y/m/d', strtotime($dateBefore[$index]));
            }

            DB::table('application_lock')
            ->insert([
                'code_key' => $val,
                'lock_date' => $lockDate,
                'activity_lock' => !empty($activity[$index]) ? 1 : 0,
                'overstock_lock' => !empty($overstock[$index]) ? 1 : 0,
                'status' => '1',
                'created_by' => Auth::user()->username,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_by' => Auth::user()->username,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }

        $title ="Update $this->title";
        $alert  ="success";
        $message  = "$title Successfully updated";
        \LogActivity::addToLog($title,"username: $username Status $message");
        return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);               
    }

}
