<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use App\Models\ApprovalMaster;
use App\Models\ApprovalLevel;
use App\Models\User;
use DB;
use DataTables;
use Response;
use App\Permission;

class ApprovalController extends Controller
{
    private $title;
    public function __construct()
    {
        $this->title = "Approval";
    }

    public function getTableColoumnMaster(){
        $kolom=[
            [ 'data'=>'module_name','name'=>'module_name','title'=>'Name'],
            [ 'data'=>'module_code','name'=>'module_code','title'=>'Code'],
            [ 'data'=>'approval_number','name'=>'approvel_number','title'=>'Number of Approval'],
            [ 'data'=>'note','name'=>'note','title'=>'Note']
        ];

        return json_encode($kolom, true);
    }

    public function getTableColoumnLevel(){
        $kolom=[
            [ 'data'=>'action','name'=>'action','title'=>'action','orderable'=> false,'searchable'=>false],
            [ 'data'=>'module_name','name'=>'module_name','title'=>'Name'],
            [ 'data'=>'username','name'=>'username','title'=>'Username'],
            [ 'data'=>'name','name'=>'name','title'=>'Name'],
            [ 'data'=>'approval_order','name'=>'approval_order','title'=>'Order of Approval'],
        ];

        return json_encode($kolom, true);
    }

    public function index()
    {
        $data['title'] = "$this->title";
        $data['kolomMaster']=$this->getTableColoumnMaster();
        $data['kolomLevel']=$this->getTableColoumnLevel();
        return view("approval.index",$data);
    }

    public function createLevel()
    {
        $data['users']=User::pluck('name','username');
        $data['approvals']=ApprovalMaster::pluck('module_name','module_code');
        $data['orders']=['1'=>'1','2'=>'2','3'=>'3','4'=>'4','5'=>'5'];
        return view('approval.create',$data);
    }

    public function storeLevel(Request $request)
    {
        $username = $request->username;
        $approvalOrder = $request->approvalOrder;
        $validator = \Validator::make($request->all(), [
                'module' => [
                    'required',
                    Rule::unique('approval_level','module_code')->where(function ($query) use($username,$approvalOrder) {
                        return $query->where('username', $username)->where('approval_order', $approvalOrder);
                    })
                ],
                'username' => 'required',
                'approvalOrder' => 'required'
            ]
        );
        
        if($validator->fails()){
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }

        $approval = new ApprovalLevel();
        $approval->module_code = $request->module;
        $approval->username = $username;
        $approval->approval_order = $approvalOrder;
        $approval->created_by = Auth::user()->username;
        $approval->updated_by = Auth::user()->username;
        $approval->save();

        return redirect()->route('approval.index')->with('success', __("Item successfully created"));
    }

    public function editLevel(Request $request)
    {        
        if(\Auth::user()->can('approval-edit')){ 
            $data['users']=User::pluck('name','username');
            $data['approvals']=ApprovalMaster::pluck('module_name','module_code');
            $data['orders']=['1'=>'1','2'=>'2','3'=>'3','4'=>'4','5'=>'5'];
            $data['approvalLevel'] = ApprovalLevel::where('id',$request->id)->first();
            return view('approval.edit', $data);
        }
        else{
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function updateLevel( Request $request )
    {
        if(\Auth::user()->can('approval-edit')){

            $validator = \Validator::make(
                $request->all(), [
                    'module' => 'required',
                    'username' => 'required',
                    'approvalOrder' => 'required'
                ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            ApprovalLevel::where('id',$request->id)
            ->update([
                'module_code' => $request->module,
                'username' => $request->username,
                'approval_order' => $request->approvalOrder,
                'updated_by' => Auth::user()->username,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            return redirect()->route('approval.index')->with('success', __('Approval successfully updated.'));

        }else{
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroyLevel( Request $request )
    {
        if(\Auth::user()->can('approval-delete')){
            ApprovalLevel::where('id',$request->id)->delete();
            return redirect()->route('approval.index')->with('success', __('Successfully deleted.'));
        }else{
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function listMaster(Request $request)
    {
        $data=DB::table('approval_master')
        ->orderBy('module_name')->get();

        return Datatables::of($data)
        ->make(true);
    }

    public function listLevel(Request $request)
    {
        $code = strtolower($request->code);
        $name = strtolower($request->name);

        $data=DB::table('approval_level')
        ->leftJoin('users','users.username','approval_level.username')
        ->leftJoin('approval_master','approval_master.module_code','approval_level.module_code')        
        ->select(['approval_level.*','users.name as name','approval_master.module_name'])
        ->where(function ($query) use ($code,$name) {
            $code ? $query->where('module_name','ilike','%'.$code.'%') : "";
            $name ? $query->where('users.name','ilike','%'.$name.'%') : "";
        })
        ->orderBy('module_name')
        ->orderBy('approval_order')
        ->orderBy('username')
        ->get();
        
        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="btn-group">
                            <a class="btn btn-icon btn-flat-primary dropdown-toggle hide-arrow" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu">';
            if (Auth::user()->can('approval-edit')) {
            $buttons .=         "<a href='javascript:void(0);'
                                data-url='". route('approval.edit.level', ['id'=>$data->id]) ."'
                                data-size='sm'
                                data-ajax-popup='true'
                                data-title='Edit'
                                class='dropdown-item'>
                                <i data-feather='edit'></i>
                                <span>". __('Edit') ."</span>
                                </a>";

            }
            if (Auth::user()->can('approval-delete')) {
                $buttons .=         "<a href='javascript:;'
                                        class='dropdown-item' 
                                        data-size='sm'
                                        data-title='Edit'
                                        data-ajax-delete='true'
                                        data-confirm='Are You Sure want to Delete?|This action can not be undone. Do you want to continue?' 
                                        data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                                        data-modal-id='".$data->id."'
                                        id='deleteButton'
                                        data-url='". route('approval.destroy.level', ['id'=>$data->id]) ."'>
                                        <i data-feather='trash-2' class='feather-14-red'></i>
                                        <span>". __('Delete') ."</span>
                                    </a>";
            }
            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        ->rawColumns(['action'])
        ->make(true);
    }
}
