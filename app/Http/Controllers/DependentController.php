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
        $code= $request->value;
        $type= $request->type;
        $dependent=$request->dependent;
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
            case 'article_pr_sub': 
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
            case 'article_pr_rm': 
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
            case 'article_wos': 
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
            case 'article_sub_rm': 
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
            case 'pRequest_sub': 
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
                $prNumber = $request->prNumber;
                $default='';
                $defaulttxt='Choose Article';
                break;
            case 'searchFromPr_sub': 
                $table='purchase_request_det';
                $field ='supp_code';
                $order ='article_code';
                $value ='article_code';
                $name  ='article_code';
                $prNumber = $request->prNumber;
                $default='';
                $defaulttxt='Choose Article';
                break;
            case 'account': 
                $table='accounts';
                $field ='';
                $order ='account';
                $value ='account';
                $name  ='description';
                $default='';
                $defaulttxt='Choose Account';
                break;
            case 'tso_list': 
                $table='target_order_hdr';
                $order ='tso_code';
                $name  ='tso_code';
                $value ='tso_code';
                $default='';
                $defaulttxt='Choose TSO';
                break;
            case 'tsoArticle': 
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
            case 'trArticle': 
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
            case 'wos_list': 
                $table='wo_hdr';
                $order ='wo_code';
                $name  ='wo_code';
                $value ='wo_code';
                $default='';
                $defaulttxt='Choose Wos';
                break;
            case 'reference': 
                $table='ap_invoice';
                $field ='supplier_id';
                $order ='ap_number';
                $value = $code;
                $name  ='ap_number';
                $default='';
                $defaulttxt='Choose invoice';
                break;
            case 'referenceAr': 
                $table='invoice_hdr';
                $field ='customer_id';
                $order ='invoice_number';
                $value = $code;
                $name  ='invoice_number';
                $default='';
                $defaulttxt='Choose invoice';
                break;
            break;
                default:
                    $table='';
        }

        if($dependent =='article_id'){
            //cari finish goods yang sudah memilki BOM baru bisa di bikin SO
            $data= DB::table($table) 
            ->leftJoin('article_stock','article_stock.article_code','=',$table.'.article_code')
            ->leftJoin('group_materials','group_materials.code','=',$table.'.group_of_material')
            ->leftJoin('uom','uom.code','=',$table.'.uom')
            ->whereIn($table.'.article_code', function($query) use ($code) {
                $query->select('article_code')
                ->from('bom_hdr') 
                ->where('status','3')
                ->where('customer',$code);
            })
            ->where($field,$code)
            ->where($field2,$type)
            ->orderBy($order)
            ->select($table.'.*', 'article_stock.article_qty as qty','article.uom as uom1','group_materials.name as group','uom.uom_group')
            ->get();          
        }elseif($dependent =='article_bom'){
            $data= DB::table($table) 
            ->leftJoin('article_types','article_types.code','=',$table.'.article_type')
            ->leftJoin('uom','uom.code','=',$table.'.uom')
            ->whereNotIn('article_type',['FG','RM'])
            ->orderBy($order)
            ->select($table.'.*'
            ,'article_types.name as type_name'
            ,'uom.uom_group'
            ,DB::RAW("(select 
                        string_agg(concat(unit_to,';',(uom_conversion(a.unit_to,article.uom))),',' order by unit_from) as uom_member 
                        from uom_con a where unit_from = $table.uom)")
            )
            ->get();
        }elseif($dependent =='searchFromPr'){
            $data= DB::table($table) 
            ->leftJoin('article','article.article_code','=',$table.'.article_code')
            ->leftJoin('article_stock','article_stock.article_code','=',$table.'.article_code')
            ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
            ->leftJoin('article_supplier','article_supplier.article_code','=','purchase_request_det.article_code')
            ->leftJoin('uom','uom.code','=',$table.'.uom')
            // Permintaan dari bu ifah tidak usah di filter by supplier
            // 11 04 2022 permintaan batal dari bu Yorin, jadi tetap di filter
            //->where($field,$code)
            // ->where('po_number','=',null)
            ->where('article_supplier.supplier_code',$code)
            ->where('pr_number','=',$prNumber)
            ->orderBy('article.article_desc')
            ->distinct('article.article_desc')
            ->select($table.'.*'
            ,'article.article_alternative_code'
            ,'article.article_code as artikel_code'
            ,'article.article_desc','article.costprice'
            ,'article_stock.article_qty as qty_stock'
            ,'purchase_request_det.uom as uom1'
            ,'group_materials.name as group'
            ,'uom.uom_group'
            ,DB::raw("(SELECT price as last_price from purchase_order_det where article_code = $table.article_code order by updated_at,created_at desc limit 1) as last_price")
            ,DB::RAW("(select coalesce(sum(qty),0) from purchase_order_det 
                where article_code = purchase_request_det.article_code 
                and  pr_number = purchase_request_det.pr_number
                and po_number in (select po_number from purchase_order_hdr where status = '3')
                ) as qty_po")
            )
            ->get();
        }elseif($dependent =='searchFromPr_sub'){
            $data= DB::table($table) 
            ->leftJoin('article','article.article_code','=',$table.'.article_code')
            ->leftJoin('article_stock','article_stock.article_code','=',$table.'.article_code')
            ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
            ->leftJoin('uom','uom.code','=',$table.'.uom')
            // ->where('po_number','=',null) //sementara PO boleh di isi sebagian, jadi kalo udah dibikin PO juga masih bisa dibikin PO lagi
            ->where('pr_number','=',$prNumber)
            ->orderBy('article.article_desc')
            ->distinct('article.article_desc')
            ->select($table.'.*'
            ,'article.article_alternative_code'
            ,'article.article_code as artikel_code'
            ,'article.article_desc','article.costprice'
            ,'article_stock.article_qty as qty_stock'
            ,'purchase_request_det.uom as uom1'
            ,'group_materials.name as group'
            ,'uom.uom_group'
            ,DB::raw("(SELECT price as last_price from purchase_order_det where article_code = $table.article_code order by updated_at,created_at desc limit 1) as last_price")
            )
            ->get();          
        }elseif($dependent =='searchFromSO'){
            $data= DB::table($table) 
            ->leftJoin('article','article.article_code','=',$table.'.article_code')
            // ->leftJoin('article_stock','article_stock.article_code','=',$table.'.article_code')
            ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
            ->leftJoin('bom_hdr','bom_hdr.article_code','=','article.article_code')
            ->where($field,$code)
            ->orderBy('article.article_desc')
            ->distinct('article.article_desc')
            ->select($table.'.*'
            ,'article.article_alternative_code'
            ,'article.article_code as artikel_code'
            ,'article.article_desc'
            ,'article.costprice'
            // ,'article_stock.article_qty as qty_stock'
            ,'article.uom as uom1'
            ,'group_materials.name as group'
            ,'bom_hdr.article_code_rm'
            ,'bom_hdr.tag')
            ->get();          

        }elseif($dependent =='article_pr'){
            /*  Pertmintaan bu lupi untuk yang FG juga bisa dibikinin PR */
            $data= DB::table($table)
            ->leftJoin('uom','uom.code','=',$table.'.uom')
            // ->whereNotIn('article_type',['FG'])
            ->orderBy($order)
            ->get();
        }elseif($dependent =='article_pr_sub'){
            $data= DB::table($table)
            ->leftJoin('uom','uom.code','=',$table.'.uom')
            ->whereIn('article_type',['FG'])
            ->orderBy($order)
            ->get();
        }elseif($dependent =='article_pr_rm'){
            $data= DB::table($table)
            ->leftJoin('uom','uom.code','=',$table.'.uom')
            ->whereIn('article_type',['RM','RMP','RMNP'])
            ->orderBy($order)
            ->get();
        }elseif($dependent =='article_wos'){

            $data=DB::select("SELECT *,(select article_qty from article_stock where article_code = z.article_code_rm) as qty_rm from (
                select *,
                (select (select article_code from article where article_code = c.article_code) as rm from bom_det c where bom_code = (select bom_code from bom_hdr where article_code = a.article_code) and article_type = 'RM') as article_code_rm,
                (select (select article_alternative_code from article where article_code = c.article_code) as rm from bom_det c where bom_code = (select bom_code from bom_hdr where article_code = a.article_code) and article_type = 'RM') as article_rm
                from article a where article_type = 'FG') z");
                
            // $data=DB::select("SELECT *, (select article_qty from article_stock where article_code = a.article_code) as qty_rm,
            // (select (select article_alternative_code from article where article_code = c.article_code) as rm from bom_det c where bom_code = (select bom_code from bom_hdr where article_code = a.article_code) and article_type = 'RM') as article_rm
            // from article a where article_type = 'FG'");
            // $data= DB::table($table) 
            // ->whereIn('article_type',['FG'])
            // ->orderBy($order)
            // ->get();
        }elseif($dependent =='article_sub_rm'){
            $data= DB::table($table) 
            ->whereNotIn('article_type',['RM'])
            ->orderBy($order)
            ->get();
        }elseif($dependent =='pRequest'){
            $data= DB::table($table) 
            ->whereIn('pr_number', function ($query) {
                $query->select('pr_number')->from('purchase_request_hdr')
                ->whereIn('order_type',['std','tso','rm'])
                ->whereIn('status',['3','7']);
            })
            // permintaan bu ifah tidak si filter by supplier
            // 11 04 2022 permintaan batal dari bu Yorin, jadi tetap di filter
            ->where($field,$code)
            // ->where('po_number','=',null)
            ->orderBy($order)
            ->distinct($order)
            ->select('purchase_request_det.*',
                DB::RAW("(select coalesce(sum(qty),0) from purchase_order_det 
                where article_code = purchase_request_det.article_code 
                and  pr_number = purchase_request_det.pr_number
                and po_number in (select po_number from purchase_order_hdr where status = '3')
                ) as qty_po")
            )
            ->get();
        }elseif($dependent =='pRequest_sub'){
            $data= DB::table($table) 
            ->whereIn('pr_number', function ($query) {
                $query->select('pr_number')->from('purchase_request_hdr')->where('order_type','sub');
            })
            ->where('po_number','=',null)
            ->orderBy($order)
            ->distinct($order)
            ->get();
        }elseif($dependent =='salesOrder'){
            $data= DB::table($table) 
            // ->where('order_type','NEW')
            ->where('status','3')
            ->orderBy($order)
            ->distinct($order)
            ->get();
        }elseif($dependent =='account'){
            $data= DB::table($table) 
            ->orderBy($order)
            ->get();
        }elseif($dependent =='tsoArticle'){
            $data= DB::table($table)
            ->leftJoin('uom','uom.code','=',$table.'.uom')
            ->whereIn(DB::raw("article_code"), function($query) {
                $query->select(DB::raw("article_code"))
                ->from('bom_hdr');
            })
            ->whereIn('article_type',['FG'])
            ->orderBy($order)
            ->get();
        }elseif($dependent =='trArticle'){
            $data= DB::table($table) 
            ->leftJoin('uom','uom.code','=',$table.'.uom')
            // ->whereNotIn('article_type',['FG'])
            ->orderBy($order)
            ->select($table.'.*'
            ,'uom.uom_group'
            ,DB::RAW("(select string_agg(unit_to,',' order by unit_from) as uom_member from uom_con where unit_from = $table.uom)")
            )
            ->get();
        }elseif($dependent =='tso_list'){
            $data= DB::table($table)
            ->where('pr_number','=',null)
            ->where('status','=','3')
            ->orderBy($order)
            ->get();
        }elseif($dependent =='wos_list'){
            //untuk wos minimal sudah di approved sekali statusnya udah validated
            // wos bisa dipanggil di wos mixing walaupun baru di level 1
            $data= DB::table($table)
            ->whereIn('status',['2','3'])
            ->whereNotIn(DB::raw("wo_code"), function($query) {
                $query->select(DB::raw("wos_number"))
                ->from('wos_mixing_hdr')
                ->where('wos_number','<>',null);
            })
            ->orderBy($order)
            ->get();
        }elseif($dependent =='wos_list_mix'){
            //untuk wos minimal sudah di approved sekali statusnya udah validated
            $data= DB::table($table)
            ->where('status','=','3')
            ->orderBy($order)
            ->get();
        }elseif($dependent =='reference'){
            $data= DB::table($table)
            ->where($field,$code)
            ->where('status','=','4') //POSTED
            ->whereNotIn(DB::raw("ap_number"), function($query) {
                $query->select(DB::raw("reference"))
                ->from('kas_det') 
                ->leftJoin('kas_hdr','kas_hdr.voucher_number','kas_det.voucher_number')
                ->where('kas_hdr.status','<>','5')
                ->where('kas_det.voucher_number','like','BK%')
                ->where('kas_det.voucher_number','like','KK%');
            })
            ->orderBy($order)
            ->get();
        }elseif($dependent =='referenceAr'){

            $customerId = DB::table('third_party')->where('account',$code)->value('kode');
            $data= DB::table($table)
            ->where($field,$customerId)
            ->where('status','=','3') //approved
            ->whereNotIn(DB::raw("invoice_number"), function($query) {
                $query->select(DB::raw("reference"))
                ->from('kas_det') 
                ->leftJoin('kas_hdr','kas_hdr.voucher_number','kas_det.voucher_number')
                ->where('kas_hdr.status','<>','5')
                ->where('kas_det.voucher_number','like','BM%')
                ->where('kas_det.voucher_number','like','KM%');
            })
            ->orderBy($order)
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
                $output .='<option value="'.$row->$value.'|'.$row->group.'|'.$row->qty.'|'.$row->uom1.'|'.$row->costprice.'" data-uom-group="'.$row->uom_group.'">'.$row->$value2.'-'. $row->$name.'</option>';
            }elseif($dependent =='article_pr'){
                $output .='<option value="'.$row->article_code.'" data-detail="'.$row->article_code.'|'.$row->uom.'|'.$row->third_party.'|'.$row->dept.'" data-uom-group="'.$row->uom_group.'">'.$row->article_alternative_code.' - '. $row->article_desc.'</option>';
            }elseif($dependent =='article_pr_sub'){
                $output .='<option value="'.$row->article_code.'" data-detail="'.$row->article_code.'|'.$row->uom.'|'.$row->third_party.'|'.$row->dept.'" data-uom-group="'.$row->uom_group.'">'.$row->article_alternative_code.' - '. $row->article_desc.'</option>';
            }elseif($dependent =='article_pr_rm'){
                $output .='<option value="'.$row->article_code.'" data-detail="'.$row->article_code.'|'.$row->uom.'|'.$row->third_party.'|'.$row->dept.'" data-uom-group="'.$row->uom_group.'">'.$row->article_alternative_code.' - '. $row->article_desc.'</option>';
            }elseif($dependent =='article_wos'){
                $output .='<option value="'.$row->article_code.'|'.$row->uom.'|'.$row->third_party.'|'.$row->dept.'|'.$row->article_rm.'|'.$row->qty_rm.'">'.$row->article_alternative_code.' - '. $row->article_desc.'</option>';
            }elseif($dependent =='article_sub_rm'){
                $output .='<option value="'.$row->article_code.'|'.$row->uom.'|'.$row->third_party.'|'.$row->dept.'">'.$row->article_alternative_code.' - '. $row->article_desc.'</option>';
            }elseif($dependent =='article_bom'){
                $output .='<option value="'.$row->article_code.'" 
                data-detail="'.$row->article_code.'|'.$row->uom.'|'.$row->costprice.'|'.$row->article_type.'|'.$row->type_name.'" 
                data-uom-group="'.$row->uom_group.'" 
                data-uom-member="'.$row->uom_member.'">
                '.$row->article_alternative_code.' - '. $row->article_desc.'
                </option>';
            }elseif($dependent =='searchFromPr'){
                if (($row->qty-$row->qty_po) > 0 ){
                    $output .='<option value="'.$row->article_code.'|'.$row->group.'|'.$row->qty_stock.'|'.($row->qty-$row->qty_po).'|'.$row->uom1.'|'.$row->costprice.'|'.$row->last_price.'" data-uom-group="'.$row->uom_group.'">'.$row->article_alternative_code.' - '. $row->article_desc.'</option>';
                }
            }elseif($dependent =='searchFromPr_sub'){
                $output .='<option value="'.$row->article_code.'|'.$row->group.'|'.$row->qty_stock.'|'.$row->qty.'|'.$row->uom1.'|'.$row->costprice.'|'.$row->last_price.'" data-uom-group="'.$row->uom_group.'">'.$row->article_alternative_code.' - '. $row->article_desc.'</option>';
            }elseif($dependent =='searchFromSO'){
                $output .='<option value="'.$row->article_code.'" data-article-rm="'.$row->article_code_rm.'" data-detail="'.$row->article_code.'|'.$row->group.'|'.$row->tag.'|'.$row->qty.'|'.$row->uom1.'|'.$row->costprice.'">'.$row->article_alternative_code.' - '. $row->article_desc.'</option>';
            }elseif($dependent =='unitTo'){
                $output .='<option value="'.$row->code.'|'.$row->uom_group.'">'.$row->code.' - '.$row->name.'</option>';
            }elseif($dependent =='account'){
                $output .='<option value="'.$row->account.'">'.$row->account.' - '.$row->description.'</option>';
            }elseif($dependent =='tsoArticle'){
                $output .="<option value='$row->article_code' data-uom-group ='$row->uom_group' data-uom ='$row->uom'>$row->article_alternative_code - $row->article_desc</option>";
            }elseif($dependent =='trArticle'){
                $output .="<option value='$row->article_code' data-uom-member='".$row->uom_member."' data-uom-group ='$row->uom_group' data-uom ='$row->uom'>$row->article_alternative_code - $row->article_desc</option>";
            }elseif($dependent =='salesOrder'){
                $output .='<option value="'.$row->$value.'">'.$row->$name.'</option>';
            }elseif($dependent =='pRequest'){
                if(($row->qty-$row->qty_po) > 0){
                    $output .="<option value='$row->pr_number'>$row->pr_number</option>";
                }
            }elseif($dependent =='reference'){
                $output .="<option value='$row->inv_number'>$row->inv_number</option>";
            }elseif($dependent =='referenceAr'){
                $output .="<option value='$row->invoice_number'>$row->invoice_number</option>";
            }else{
                $output .='<option value="'.$row->$value.'">'.$row->$name.'</option>';
            }
        }        
        
        return $output;
    }
}