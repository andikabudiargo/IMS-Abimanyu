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
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=>false,'searchable'=>false],
            ['data'=>'bom_code','name'=>'bom_code','title'=>'BOM Code'],
            ['data'=>'customer','name'=>'customer','title'=>'Customer'],
            ['data'=>'num_revision','name'=>'num_revision','title'=>'Revision'],
            ['data'=>'article_des','name'=>'article_des','title'=>'Article'],
            ['data'=>'part_no','name'=>'part_no','title'=>'Part No'],
            ['data'=>'model','name'=>'model','title'=>'Model'],
            ['data'=>'group_of_material','name'=>'group_of_material','title'=>'Group'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'tag','name'=>'tag','title'=>'Tag'],
            ['data'=>'pass_rate','name'=>'pass_rate','title'=>'Pass Rate'],
            ['data'=>'pass_thru','name'=>'pass_thru','title'=>'Pass Thru'],
            ['data'=>'cycle_time','name'=>'cycle_time','title'=>'Cycle Time'],
            ['data'=>'approval_by','name'=>'approval_by','title'=>'Approval By'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created At'],
            ['data'=>'updated_by','name'=>'updated_by','title'=>'Updated By'],
            ['data'=>'updated_at','name'=>'updated_at','title'=>'Updated At'],
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
        ->where('article_type','<>','FG')
        ->where('article_type','<>','RM')
        ->select('article.*', 'third_party.nama as cust_name','group_materials.name as group')
        ->get();
       
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'RECEIVED','5'=>'DELETED','6'=>'CLOSED','7'=>'REVISED'];
        $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','5'=>'DELETED'];
                        
        return view("bomReport.index",$data);
    }

    public function list(Request $request)
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

    public function print(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        
        $data['bomHdr']=DB::table('bom_hdr')
        ->leftJoin('third_party','third_party.kode','bom_hdr.customer')
        ->leftJoin('article','article.article_code','bom_hdr.article_code')
        ->select('bom_hdr.*','third_party.*','article.*','bom_hdr.note as note_hdr')
        ->where('bom_hdr.id',$id)
        ->first();

        $bomNumber=$data['bomHdr']->bom_code;

        $data['title'] = "$bomNumber";
       
        $data['details']=DB::table('bom_det')
        ->leftJoin('article','article.article_code','bom_det.article_code')
        ->leftJoin('third_party','third_party.kode','article.third_party')
        ->select('bom_det.*'
        ,'article.article_alternative_code'
        ,'article.article_desc'
        ,'third_party.nama')
        ->where('bom_code',$bomNumber)
        ->orderBy('bom_det.id')
        ->get();

        $username="";
        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$bomNumber,$username);
        
        $data['keterangan']=$data['bomHdr'] -> note;
        $data['bomNumber'] =$bomNumber;
        
        $data['status'] ='1';
        $data['no'] =0;

        view()->share($data);

        $pdf = PDF::loadView('bom.print')->setPaper([0, 0, 595.28, 841.89], 'portrait');
        return $pdf->stream("PO_$bomNumber.pdf");

    }

    public function revision(Request $request){
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $bomOrigin=DB::table('bom_hdr')->where('id',$id)->value('bom_code');
        $numRevision = $request->nR ? $request->nR + 1 : 1 ;
        $bomNew = $bomOrigin.'-R'.$numRevision;
        $checkNewBom=DB::table('bom_hdr')->where('bom_code',$bomNew)->count();

        if ($checkNewBom > 0){
            $bomNew = $bomOrigin.'-R'.($numRevision+1);
        }
                
        $sqlHdr = "INSERT into bom_hdr 
        (
            bom_code,
            customer,
            article_code,
            uom,
            group_of_material,
            status,
            note,
            tag,
            pass_rate,
            pass_thru,
            cycle_time,
            created_by,
            updated_by,
            created_at,
            updated_at,
            origin_bom_code,
            num_revision,
            authorized_by,
            revised_by,
            revised_at,
            part_no,
            model,
            article_code_rm
        )
        select 
            '$bomNew',
            customer,
            article_code,
            uom,
            group_of_material,
            '7',
            note,
            tag,
            pass_rate,
            pass_thru,
            cycle_time,
            '$username',
            '$username',
            '".date('Y-m-d H:i:s')."',
            '".date('Y-m-d H:i:s')."',
            '$bomOrigin',
            $numRevision,
            authorized_by,
            '$username',
            '".date('Y-m-d H:i:s')."',
            part_no,
            model,
            article_code_rm
        from bom_hdr where bom_code = '$bomOrigin'";

        $sqlDet="INSERT into bom_det
        (
            bom_code,
            article_code,
            qty,
            uom,
            cost_price,
            article_type,
            customer_code,
            status,
            note,
            created_by,
            updated_by,
            created_at,
            updated_at,
            uom_con,
            urutan
        )
        select 
            '$bomNew',
            article_code,
            qty,
            uom,
            cost_price,
            article_type,
            customer_code,
            status,
            note,
            '$username',
            '$username',
            '".date('Y-m-d H:i:s')."',
            '".date('Y-m-d H:i:s')."',
            uom_con,
            urutan
        from bom_det where bom_code = '$bomOrigin' order by id";

        $rowAffected =  DB::select($sqlHdr);
        if ($rowAffected){
            DB::select($sqlDet);

            DB::table('bom_hdr')
            ->where('bom_code',$bomOrigin)
            ->update(
                [
                    'num_revision' => $numRevision,
                    'status' => '1',
                    'revised_by'=>Auth::user()->username,
                    'revised_at'=> date('Y-m-d H:i:s'),
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );

            DB::table('approval_history')
            ->where('module_number',$bomOrigin)
            ->update(
                [
                    'module_number' => $bomNew,
                    'status' => '0',
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );
            
            $title ="Save $this->title";
            $alert  ="success";
            $message  = "$title Revision PO: $bomOrigin to $bomNew is successfully saved";
            \LogActivity::addToLog($title,"username: $username Status $message");
            // return $this->showEdit(Crypt::encryptString($id));
            return redirect()->route('bom.edit', ['id'=>Crypt::encryptString($id)]);
        }else{
            $title ="Save $this->title";
            $alert  ="warning";
            $message  = "$title Revision PO: $bomOrigin to $bomNew is failed to save";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
        }
        
    }
}
