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
            case 'unitTo':
                $groupCode = explode("|",$code);
                $table = 'uom';
                $field = 'uom_group';
                $order = 'name';
                $code = $groupCode[1];
                $value = $groupCode[1];
                $default='';
                $defaulttxt='Choose uom';
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
            case 'provinsi': 
                $table='regions';
                $field ='parent_region_code';
                $order ='region_name';
                $value ='region_code';
                $name  ='region_name';
                $default='';
                $defaulttxt='Choose';
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
            case 'article_bom': 
                $table='article';
                $order ='article_desc';
                $name  ='article_desc';
                $default='';
                $defaulttxt='Choose article';
                break;
            case 'article_pr': 
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
            case 'pRequest': 
                $table='purchase_request_det';
                $field ='supp_code';
                $order ='pr_number';
                $value ='pr_number';
                $name  ='pr_number';
                $default='';
                $defaulttxt='Choose PR';
                break;
            case 'salesOrder': 
                $table='sales_order_hdr';
                $field ='supp_code';
                $order ='so_code';
                $value ='so_code';
                $name  ='so_code';
                $default='';
                $defaulttxt='Choose SO';
                break;
            case 'searchFromSO': 
                $table='sales_order_det';
                $field ='so_code';
                $order ='article_code';
                $value ='article_code';
                $name  ='article_code';
                $default='';
                $defaulttxt='Choose Article';
                break;
            case 'searchFromPr': 
                $table='purchase_request_det';
                $field ='supp_code';
                $order ='article_code';
                $value ='article_code';
                $name  ='article_code';
                $default='';
                $defaulttxt='Choose Article';
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
        }elseif($dependent =='article_bom'){
            $data= DB::table($table) 
            ->leftJoin('article_types','article_types.code','=',$table.'.article_type')
            ->whereNotIn('article_type',['FG','RM'])
            ->orderBy($order)
            ->select($table.'.*', 'article_types.name as type_name')
            ->get();
        }elseif($dependent =='searchFromPr'){
            $data= DB::table($table) 
            ->leftJoin('article','article.article_code','=',$table.'.article_code')
            ->leftJoin('article_stock','article_stock.article_code','=',$table.'.article_code')
            ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
            ->where($field,$code)
            ->where('po_number','=',null)
            ->orderBy('article.article_desc')
            ->distinct('article.article_desc')
            ->select($table.'.*','article.article_code as artikel_code','article.article_desc','article.costprice','article_stock.article_qty as qty','purchase_request_det.uom as uom1','group_materials.name as group')
            ->get();          
        }elseif($dependent =='searchFromSO'){
            $data= DB::table($table) 
            ->leftJoin('article','article.article_code','=',$table.'.article_code')
            ->leftJoin('article_stock','article_stock.article_code','=',$table.'.article_code')
            ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
            ->where($field,$code)
            ->orderBy('article.article_desc')
            ->distinct('article.article_desc')
            ->select($table.'.*','article.article_alternative_code','article.article_code as artikel_code','article.article_desc','article.costprice','article_stock.article_qty as qty_stock','article.uom as uom1','group_materials.name as group')
            ->get();          
        }elseif($dependent =='article_pr'){
            $data= DB::table($table) 
            ->whereNotIn('article_type',['FG','RM'])
            ->orderBy($order)
            ->get();           

        }elseif($dependent =='pRequest'){
            $data= DB::table($table) 
            ->where($field,$code)
            ->where('po_number','=',null)
            ->orderBy($order)
            ->distinct($order)
            ->get();
        }elseif($dependent =='salesOrder'){
            $data= DB::table($table) 
            ->where('order_type','NEW')
            ->orderBy($order)
            ->distinct($order)
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
            }elseif($dependent =='article_pr'){
                $output .='<option value="'.$row->article_code.'|'.$row->uom.'|'.$row->third_party.'|'.$row->dept.'">'.$row->article_alternative_code.' - '. $row->article_desc.'</option>';
            }elseif($dependent =='article_bom'){
                $output .='<option value="'.$row->article_code.'|'.$row->uom.'|'.$row->costprice.'|'.$row->article_type.'|'.$row->type_name.'">'.$row->article_alternative_code.' - '. $row->article_desc.'</option>';
            }elseif($dependent =='searchFromPr'){
                $output .='<option value="'.$row->article_code.'|'.$row->group.'|'.$row->qty_stock.'|'.$row->qty.'|'.$row->uom1.'|'.$row->costprice.'">'.$row->article_alternative_code.' - '. $row->article_desc.'</option>';
            }elseif($dependent =='searchFromSO'){
                $output .='<option value="'.$row->article_code.'|'.$row->group.'|'.$row->qty_stock.'|'.$row->qty.'|'.$row->uom1.'|'.$row->costprice.'">'.$row->article_alternative_code.' - '. $row->article_desc.'</option>';
            }elseif($dependent =='unitTo'){
                $output .='<option value="'.$row->code.'|'.$row->uom_group.'">'.$row->code.' - '.$row->name.'</option>';
            }else{
                $output .='<option value="'.$row->$value.'">'.$row->$name.'</option>';
            }
            
        }        
        
        return $output;
    }
}