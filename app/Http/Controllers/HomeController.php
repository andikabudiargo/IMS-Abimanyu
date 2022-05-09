<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

        $data['tanggal'] = Carbon::now()->format('l').','.Carbon::now()->format('M Y');
        $data['listPo'] = DB::table('purchase_order_hdr')
        ->where('status','2')
        // ->where('authorized_by',"")
        ->select('purchase_order_hdr'.'.*', DB::raw('(SELECT sum(qty*price) from purchase_order_det where po_number = purchase_order_hdr.po_number) as po_amount'))
        ->orderBy('id')
        ->get();

        $data['greeting'] = self::greeting();            

        return view('home',$data);
    }

}
