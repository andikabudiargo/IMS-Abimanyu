<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Response;
use DB;

class DependentController extends Controller
{

    public function dependentFetch(Request $request)
    {
        $code= $request->get('value');
        $type= $request->get('type');
        $dependent=$request->get('dependent');
        $akhusus='';

        switch ($dependent) { 
            case 'kota': 
                $table='regions';
                $field ='parent_region_code';
                $order ='region_name';
                $value ='region_code';
                $name  ='region_name';
                $default='';
                $defaulttxt='Pilih';
                break;
            case 'kecamatan': 
                $table='regions';
                $field ='parent_region_code';
                $order ='region_name';
                $value ='region_code';
                $name  ='region_name';
                $default='';
                $defaulttxt='Pilih';
                break;
            case 'kelurahan': 
                $table='regions';
                $field ='parent_region_code';
                $order ='region_name';
                $value ='region_code';
                $name  ='region_name';
                $default='';
                $defaulttxt='Pilih';
                break;
            case 'provinsi': 
                $table='regions';
                $field ='parent_region_code';
                $order ='region_name';
                $value ='region_code';
                $name  ='region_name';
                $default='';
                $defaulttxt='Pilih';
                break;
            case 'article_id': 
                $table='article';
                $field ='third_party';
                $field2 ='article_type';
                $type = $type;
                $order ='article_desc';
                $value ='article_code';
                $value2 ='article_alternative_code';
                $name  ='article_desc';
                $default='';
                $defaulttxt='Choose article';
                break;
            break;
                default:
                    $table='';
            }

        if($dependent =='article_id'){
            $data= DB::table($table) 
            ->leftJoin('article_stock','article_stock.article_code','=',$table.'.article_code')
            ->leftJoin('group_materials','group_materials.code','=',$table.'.group_of_material')
            ->where($field,$code)
            ->where($field2,$type)
            ->orderBy($order)
            ->select($table.'.*', 'article_stock.article_qty as qty','article.uom as uom1','group_materials.name as group')
            ->get();            
        }else{
            $data= DB::table($table) 
            ->where($field,$code)
            ->orderBy($order)
            ->get();
        }
        
        $output='';
        $output .='<option value="'.$default.'">'.$defaulttxt.'</option>';

        foreach ($data as $row){
            if($dependent =='article_id'){
                $output .='<option value="'.$row->$value.'|'.$row->group.'|'.$row->qty.'|'.$row->uom1.'|'.$row->costprice.'">'.$row->$value2.' - '. $row->$name.'</option>';
            }else{
                $output .='<option value="'.$row->$value.'">'.$row->$name.'</option>';
            }
        }        
        
        return $output;
    }
}