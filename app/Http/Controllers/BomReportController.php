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
use PDF;
use AppHelpers;
use Approval;

class BomReportController extends Controller
{
    private $title;
    private $moduleCode;
    private $decimalPlaces;
    public function __construct()
    {
        $this->title = "BOM Report";
        $this->moduleCode = "BOM";
        $this->decimalPlaces = config('globalParam.decimal');
    }

    public function getTableColoumn(){
        $kolom=
        [
            ['data'=>'bom_code','name'=>'bom_code','title'=>'BOM Code'],
            ['data'=>'customer_code','name'=>'customer_code','title'=>'Customer'],
            ['data'=>'article_fg','name'=>'article_fg','title'=>'Article FG'],
            ['data'=>'article_des','name'=>'article_des','title'=>'Article FG Desc'],
            ['data'=>'article_ch','name'=>'article_ch','title'=>'Article Chemical'],
            ['data'=>'article_des_det','name'=>'article_des_det','title'=>'Article Chemical Desc'],
            ['data'=>'qty','name'=>'qty','title'=>'QTY Bom'],
            ['data'=>'uom_bom','name'=>'uom_bom','title'=>'UOM BOM'],
            ['data'=>'uom_con','name'=>'uom_con','title'=>'UOM Con'],
            ['data'=>'conversi','name'=>'conversi','title'=>'Conversi'],
            ['data'=>'part_no','name'=>'part_no','title'=>'Part No'],
            ['data'=>'model','name'=>'model','title'=>'Model'],
            ['data'=>'group_of_material','name'=>'group_of_material','title'=>'Group'],
            ['data'=>'statusku','name'=>'statusku','title'=>'Status'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            // ['data'=>'tag','name'=>'tag','title'=>'Tag','visible'=>false],
            // ['data'=>'pass_rate','name'=>'pass_rate','title'=>'Pass Rate','visible'=>false],
            // ['data'=>'pass_thru','name'=>'pass_thru','title'=>'Pass Thru','visible'=>false],
            // ['data'=>'cycle_time','name'=>'cycle_time','title'=>'Cycle Time','visible'=>false],
        ];
        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $data['title'] = "$this->title";
        $data['kolom'] = $this->getTableColoumn();
        $data['articles'] = DB::table('article')
        ->leftJoin('third_party','article.third_party','third_party.kode')
        ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
        ->where('article_type','FG')
        ->select('article.*', 'third_party.nama as cust_name','group_materials.name as group')
        ->get();

        $data['materials'] = DB::table('article')
        ->leftJoin('third_party','article.third_party','third_party.kode')
        ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
        ->whereIn('article_type',['CM1','CM2','PT']) 
        // ->where('article_type','<>','FG')
        // ->where('article_type','<>','RM')
        ->select('article.*', 'third_party.nama as cust_name','group_materials.name as group')
        ->get();
       
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'RECEIVED','5'=>'DELETED','6'=>'CLOSED','7'=>'REVISED'];
        $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','5'=>'DELETED'];
                        
        return view("bomReport.index",$data);
    }

    public function list(Request $request)
    {
        $username =  Auth::user()->username;
        $searchBom = strtolower($request->searchBom);
        $articleMaterial = $request->articleMaterial;
        $articleCode = $request->articleCode;

        $data = DB::table('bom_det')
        ->leftJoin('bom_hdr','bom_hdr.bom_code','bom_det.bom_code')
        ->leftJoin('article','article.article_code','bom_hdr.article_code')
        ->leftJoin('article as b','b.article_code','bom_det.article_code')
        ->where(function ($query) use ($searchBom,$articleCode,$articleMaterial) {
            $searchBom ? $query->where('bom_det.bom_code','ilike','%'.$searchBom.'%') : '';
            $articleCode ? $query->where('bom_hdr.article_code','ilike','%'.$articleCode.'%') : '';
            $articleMaterial ? $query->where('bom_det.article_code','ilike','%'.$articleMaterial.'%') : '';
        })
        ->where('bom_hdr.status','<>','7')
        ->select('bom_det.*','bom_hdr.*'
        ,'bom_hdr.status as statusku'
        ,'bom_det.uom as uom_bom'
        ,'article.article_alternative_code as article_fg'
        ,'b.article_alternative_code as article_ch'
        ,'article.article_desc as article_des'
        ,'b.article_desc as article_des_det'
        ,db::raw("(coalesce((select unit_factor from uom_con where unit_from = bom_det.uom_con and unit_to = b.uom),1)) as conversi")
        )       
        ->orderBy('bom_det.bom_code')
        ->get(); 
       
        return Datatables::of($data)
        ->addColumn('statusku', function ($data) {
            $status = ['NEW','VALIDATE','APPROVED','RECEIVED','DELETED','CLOSED','REVISED','DECLINE'];
            if ($data->statusku > 0 ){
                return $status[$data->statusku - 1];
            }else{
                return '';
            }
            
        })
        ->rawColumns(['statusku'])
        ->make(true);
    }

    public function listMaterial(Request $request)
    {
       // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'RECEIVED','5'=>'DELETED','6'=>'CLOSED','7'=>'REVISED','8'=>'DECLINE'];
        $username =  Auth::user()->username;
        $searchBom = strtolower($request->searchBom);
        $articleCode = $request->articleCode;
        $status = $request->status;

        $data = DB::table('bom_hdr')
        ->leftJoin('article','article.article_code','bom_hdr.article_code')
        ->where(function ($query) use ($searchBom,$articleCode,$status) {
            $searchBom ? $query->where('bom_code','ilike','%'.$searchBom.'%') : '';
            $articleCode ? $query->where('bom_hdr.article_code','ilike','%'.$articleCode.'%') : '';
            $status ? $query->where('bom_hdr.status','=',$status) : '';
        })
        ->where('bom_hdr.status','<>','7')
        ->select('bom_hdr.*'
        ,DB::raw("CONCAT(article.article_alternative_code,'-',article.article_desc) as article_des")
        ,DB::raw("(select STRING_AGG((select name from users where username = a.username), ' -> ' ORDER BY approval_order) AS main from approval_history a where module_number = bom_hdr.bom_code) as approval_by")
        ,DB::raw("(SELECT username = '$username' as validate from (
            select username,approval_order,
            (select max(approval_number) from approval_master where module_code = a.module_code ) as max_level,
            COALESCE((select max(approval_order) from approval_history
            where module_code = a.module_code
            and module_number = bom_hdr.bom_code),'0') as current_level
            from approval_level a 
            where module_code = '".$this->moduleCode."' and username = '$username') b
            where approval_order = current_level+1
            ) as statusku")
        )
        ->orderBy('bom_code')
        ->get(); 
       
        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            if (Auth::user()->can('bom-edit') && $data->status != '3' && $data->status != '5') {
            $buttons .=         '<a href="'. route('bom.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
            }

            if ( $data->statusku and ($data->status == '2' or $data->status == '1') ){
                
                $buttons .= '<a href="'. route('bom.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                <i data-feather="check"></i>
                                <span>'. __("Approve") .'</span>
                            </a>';
            }

            $buttons .=         '<a href="'. route('bom.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
                                    <i data-feather="printer"></i>
                                    Print
                                </a>';
            $buttons .=         '<a href="'. route('bom.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    Detail
                                </a>';
            if (($data->status == '2') || ($data->status == '3') ){
                if (Auth::user()->can('bom-revision')) {
                    $buttons .= '<a href="'. route('bom.revision', ['id'=>Crypt::encryptString($data->id),'nR'=>$data->num_revision]) .'" class="dropdown-item">
                                    <i data-feather="copy"></i>
                                    <span>'. __("Revision") .'</span>
                                </a>';
                }
            }
                
            if (Auth::user()->can('bom-delete')) {
                if ( $data->status != '5' ){
                    $buttons .= "<a href='javascript:;'
                                    class='dropdown-item' 
                                    data-size='sm'
                                    data-ajax-delete='true'
                                    data-confirm='Are You Sure want to Delete?|This action can not be undone. Do you want to continue?' 
                                    data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                                    data-modal-id='".$data->id."'
                                    id='deleteButton'
                                    data-url='". route('bom.destroy', ['id'=>Crypt::encryptString($data->id)]) ."'>
                                    <i data-feather='trash-2' class='feather-14-red'></i>
                                    <span>". __('Delete') ."</span>
                                </a>";
                }
            }
            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        
        ->addColumn('bom_code', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger'];
            // $statusBo = ['NEW','VALIDATED','APPROVED','RECEIVED','DELETED','CLOSED','REVISED','DECLINE'];
            return '<span style="display: none;">'.$data->bom_code.'</span>
                    <a class="badge d-block '.$badges[$data->status - 1].'" href="'. route('bom.show', ['id'=>Crypt::encryptString($data->id)]) .'" >
                    <span>'.$data->bom_code.'</span>
                    </a>';
        })

        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary'];
            $status = ['NEW','VALIDATE','APPROVED','RECEIVED','DELETED','CLOSED','REVISED','DECLINE'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$status[$data->status - 1]."</div>";
        })
        ->rawColumns(['action','status','bom_code'])
        ->make(true);
    }

    
}
