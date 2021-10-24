<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
    public function index()
    {

        $data['listPo'] = DB::table('purchase_order_hdr')
        ->where('status','2')
        // ->where('authorized_by',"")
        ->select('purchase_order_hdr'.'.*', DB::raw('(SELECT sum(qty*price) from purchase_order_det where po_number = purchase_order_hdr.po_number) as po_amount'))
        ->orderBy('id')
        ->get();

        return view('home',$data);
    }

}
