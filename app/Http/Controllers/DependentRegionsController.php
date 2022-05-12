<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Response;
use DB;

class DependentRegionsController extends Controller
{

    public function dependentFetch(Request $request)
    {
        $code= $request->value;
        $type= $request->type;
        $dependent=$request->dependent;
        $akhusus='';

        switch ($dependent) { 
            case 'provinsi': 
                $table='regions';
                $field ='parent_region_code';
                $order ='region_name';
                $value ='region_code';
                $name  ='region_name';
                $default='';
                $defaulttxt='Choose';
                break;
            case 'kota': 
                $table='regions';
                $field ='parent_region_code';
                $order ='region_name';
                $value ='region_code';
                $name  ='region_name';
                $default='';
                $defaulttxt='Choose';
                break;
            case 'kecamatan': 
                $table='regions';
                $field ='parent_region_code';
                $order ='region_name';
                $value ='region_code';
                $name  ='region_name';
                $default='';
                $defaulttxt='Choose';
                break;
            case 'kelurahan': 
                $table='regions';
                $field ='parent_region_code';
                $order ='region_name';
                $value ='region_code';
                $name  ='region_name';
                $default='';
                $defaulttxt='Choose';
                break;
            break;
                default:
                    $table='';
            }
                        
        $data= DB::table($table) 
        ->where($field,$code)
        ->orderBy($order)
        ->get();
     
        $output='';
        $output .='<option value="'.$default.'">'.$defaulttxt.'</option>';
        $output .='<option value="'.$row->$value.'">'.$row->$name.'</option>';
                     
        return $output;
    }
}