<?php

namespace App\Http\ViewComposers;

use Illuminate\View\View;
// use App\Repositories\UserRepository;
use DB;

class NotificationComposer
{
    public function compose(View $view)
    {
        $listPo = DB::table('purchase_order_hdr')
        // ->where('status','2')
        // ->where('authorized_by',"")
        ->select('purchase_order_hdr'.'.*', DB::raw('(SELECT sum(qty*price) from purchase_order_det where po_number = purchase_order_hdr.po_number) as po_amount'))
        ->orderBy('id')
        ->get();

        $view->with('listPo2', $listPo);
    }
}