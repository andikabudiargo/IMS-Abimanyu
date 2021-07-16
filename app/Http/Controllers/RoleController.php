<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use DB;
use DataTables;
   

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    function __construct()
    {
         $this->middleware('permission:role-list|role-create|role-edit|role-delete', ['only' => ['index','store']]);
         $this->middleware('permission:role-create', ['only' => ['create','store']]);
         $this->middleware('permission:role-edit', ['only' => ['edit','update']]);
         $this->middleware('permission:role-delete', ['only' => ['destroy']]);
    }
  

    /**

     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response

     */

    public function index(Request $request)

    {
        // $roles = Role::orderBy('id','DESC')->paginate(5);
        // return view('roles.index',compact('roles'))
        //     ->with('i', ($request->input('page', 1) - 1) * 5);

        return view('roles.index');
    }
   

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function create()
    {
        $permission = Permission::orderBy('group_name','ASC')->get();
        return view('roles.create',compact('permission'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|unique:roles,name',
            'permission' => 'required',
        ]);

        $name = $request->name;
        $permission = $request->permission;
        $role = Role::create(['name' => $name ]);
        $role->syncPermissions($permission);

        return response()->json(array('message' => 'Role created successfully'));


    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function show($id)
    {
        $role = Role::find($id);
        $rolePermissions = Permission::join("role_has_permissions","role_has_permissions.permission_id","=","permissions.id")
            ->where("role_has_permissions.role_id",$id)
            ->get();

        return view('roles.show',compact('role','rolePermissions'));

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function edit($id)
    {

        $role = Role::find($id);
        return view('roles.edit',compact('role'));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {   

        $id = $request->id;
        $name = $request->name;
        $permission = $request->permission;
        $roleOld = Role::find($id);
        $oldName = $roleOld->name;
        if ($oldName !=$name ){
            $this->validate($request, [
                'name' => 'required|unique:roles,name',
                'permission' => 'required',
            ]);
        }else{
            $this->validate($request, [
                'name' => 'required',
                'permission' => 'required',
            ]);
        }

        
        $role = Role::find($id);
        $role->name = $name;
        $role->save();
        $role->syncPermissions($permission);

        return response()->json(array('message' => 'Role updated successfully'));

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function listAllPermission(Request $request)
    {
        $id = $request->roleId;
        $rolePermissions = DB::table("role_has_permissions")->where("role_has_permissions.role_id",$id)
            ->pluck('role_has_permissions.permission_id')
            ->all();

        $permission = Permission::orderBy('group_name','ASC')->get();
        return response()->json(['permission'=>$permission,'rolePermissions'=>$rolePermissions]);   
    }

    public function destroy(Request $request)
    {

        $role_id=$request['id'];
        
        DB::beginTransaction();
            try {               
                DB::table('roles')->where('id',"=",$role_id)->delete();
                DB::commit();
                return response()->json(array('status' => 1, 'message' => 'Role deleted successfully'));

            } catch (Exception $e) {
                DB::rollBack();
                return response()->json(array('status' => 0, 'message' => 'Role deleted failed'));
            }
    }

    public function listRole(Request $request)
    {      
        $role = Role::orderBy('id','DESC');
        // $role = DB::table(DB::raw("($sqlku) as oki"));
        return Datatables::of($role)
        ->addColumn('action', function ($role) {
            $buttons = '<div class="d-inline-flex">';
            
            $buttons .= '<a class="ml-1 my-tooltip" href="'.route('roles.show', ['id'=>$role->id]).'" 
                                data-toggle="tooltip" data-placement="bottom" title="Show">
                                <i class="feather-24" data-feather="align-justify"></i>
                            </a>';
            
            if (Auth::user()->can('role-edit')) {
                $buttons .= '<a class="ml-1 my-tooltip" href="'.route('roles.edit', [$role->id]).'" 
                                data-toggle="tooltip" data-placement="bottom" title="Edit">
                                <i class="feather-24" data-feather="edit"></i>
                            </a>';
            }
            if (Auth::user()->can('role-delete')) {
                $buttons .= '<a class="ml-1 my-tooltip" href="javascript:;" onclick="validasidelete(\''.$role->id.'\',\''.$role->name.'\')"
                                data-toggle="tooltip" data-placement="bottom" title="Delete">
                                <i class="feather-24-red" data-feather="trash-2"></i>
                            </a>';
            }
            $buttons .='</div>';

            return $buttons;
            })
        ->rawColumns(['action'])
        ->make(true);
	}

}