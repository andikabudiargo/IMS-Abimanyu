<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Response;
use App\Permission;
use DataTables;
use DB;

class MonitoringController extends Controller
{
    private $title;
    private $decimalPlaces;
    private $moduleCode;
    public function __construct()
    {
        $this->title = "Monitoring";
    }

    public function getTableColoumnQtyNotBalance(){
        $kolom=    
        [
            ['data'=>'movement_code','name'=>'movement_code','title'=>'Movement Code'],
            ['data'=>'artikel_code','name'=>'artikel_code','title'=>'Article Code'],
            ['data'=>'alternative','name'=>'alternative','title'=>'Article Alternative'],
            ['data'=>'description','name'=>'description','title'=>'Article Description'],
            ['data'=>'last_qty','name'=>'last_qty','title'=>'Last Qty Movement'],
            ['data'=>'article_qty','name'=>'article_qty','title'=>'Qty On Article'],
        ];
        return json_encode($kolom, true);
    }

    public function qtyNotBalance(Request $request)
    {
        $data['title'] = "$this->title Qty Not Balance";
        $data['kolom'] = $this->getTableColoumnQtyNotBalance();
        
        return view("monitoring.qtyNotBalance",$data);
    }
    
    public function qtyNotBalanceList(Request $request)
    {
        $data=DB::select("SELECT *,(select article_alternative_code from article where article_code = oki.artikel_code) alternative ,(select article_desc from article where article_code = oki.artikel_code) description
        from 
        (select movement_code,artikel_code,last_qty,(select article_qty from article_stock where article_code = movement.artikel_code)
        from movement 
        where (movement_code) in (select max(movement_code) from movement group by artikel_code)) as oki
        where last_qty <> article_qty");

        return Datatables::of($data)
        ->make(true);
    }
       
}
