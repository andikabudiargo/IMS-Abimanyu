<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Permission;
use Illuminate\Http\Request;
use DataTables;
use DB;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('permissions.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
		$permission_id=$request['permission_id'];
		$name=$request['name'];
		$name_inner=ucfirst($request['name']);
		$display_name=ucfirst($request['display_name']);
		$description=ucfirst($request['description']);
		$other=$request['other'];
		$menu=$request['menu'];
		$create=$request['create'];
		$edit=$request['edit'];
		$list=$request['list'];
		$delete=$request['delete'];
        $guard_name='web';

		if ($permission_id == ''){

			DB::beginTransaction();
				try {
				
						if ($menu==1){
							DB::table('permissions')->insertGetId([
                                'group_name'=>$name,
                                'guard_name'=>$guard_name,
								'name'=>$name.'-index',
								'display_name'=>$name_inner.' Menu',
								'description'=>'Menu of '.$name_inner,
								'created_at' => date('Y-m-d H:i:s'),
								'updated_at' => date('Y-m-d H:i:s')
							]);
						}

						if ($list==1){
							DB::table('permissions')->insertGetId([
                                'group_name'=>$name,
                                'guard_name'=>$guard_name,
								'name'=>$name.'-list',
								'display_name'=>'Display '.$name_inner.' Listing',
								'description'=>'See only Listing Of '.$name_inner,
								'created_at' => date('Y-m-d H:i:s'),
								'updated_at' => date('Y-m-d H:i:s')
							]);
						}

						if ($create==1){
							DB::table('permissions')->insertGetId([
                                'group_name'=>$name,
                                'guard_name'=>$guard_name,
								'name'=>$name.'-create',
								'display_name'=>'Create '.$name_inner,
								'description'=>'Create new '.$name_inner,
								'created_at' => date('Y-m-d H:i:s'),
								'updated_at' => date('Y-m-d H:i:s')
							]);
						}

						if ($edit==1){
							DB::table('permissions')->insertGetId([
                                'group_name'=>$name,
                                'guard_name'=>$guard_name,
								'name'=>$name.'-edit',
								'display_name'=>'Edit '.$name_inner,
								'description'=>'Edit '.$name_inner,
								'created_at' => date('Y-m-d H:i:s'),
								'updated_at' => date('Y-m-d H:i:s')
							]);
						}

						if ($delete==1){
							DB::table('permissions')->insertGetId([
                                'group_name'=>$name,
                                'guard_name'=>$guard_name,
								'name'=>$name.'-delete',
								'display_name'=>'Delete '.$name_inner,
								'description'=>'Delete '.$name_inner,
								'created_at' => date('Y-m-d H:i:s'),
								'updated_at' => date('Y-m-d H:i:s')
							]);
						}

						if ($other==1){
							DB::table('permissions')->insertGetId([
                                'group_name'=>$name,
                                'guard_name'=>$guard_name,
								'name'=>$name,
								'display_name'=>$display_name,
								'description'=>$description,
								'created_at' => date('Y-m-d H:i:s'),
								'updated_at' => date('Y-m-d H:i:s')
							]);
						}
					

					DB::commit();
					return response()->json(array('status' => 1, 'message' => 'Data successfully saved'));

				} catch (Exception $e) {
					DB::rollBack();
					return response()->json(array('status' => 0, 'message' => 'Data failed to save'));
				}
		}else{

			$row_affected=DB::table('permissions')
					->where('id',$permission_id)
					->update([
						'display_name'=>$display_name,
						'description'=>$description,
						'updated_at' => date('Y-m-d H:i:s')
					]);
				DB::commit();

			if($row_affected>0){
				return response()->json(array('status' => 1, 'message' => 'Data sudah di update'));
			}else{
				return response()->json(array('status' => 0, 'message' => 'Tidak ter update :'.$display_name));
			}
		}

	}

    /**
     * Display the specified resource.
     *
     * @param  \App\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function show(Permission $permission)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function edit(Permission $permission)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Permission $permission)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $permission_id=$request['permission_id'];
        
        DB::beginTransaction();
            try {               
                DB::table('permissions')->where('id',"=",$permission_id)->delete();
                DB::commit();
                return response()->json(array('status' => 1, 'message' => 'Data sudah di delete'));

            } catch (Exception $e) {
                DB::rollBack();
                return response()->json(array('status' => 0, 'message' => 'Delete data gagal'));
            }
    }

    public function listPermission(Request $request)
    {      
        $sqlku=("SELECT * FROM permissions order by ID");
        $permission = DB::table(DB::raw("($sqlku) as oki"));
        return Datatables::of($permission)
        ->addColumn('responsive_id', function ($permission){
            $kosong="";
            return $kosong;
        })
        ->addColumn('action', function ($permission) {
            $buttons = '<div class="d-inline-flex">';
            // $buttons .= '    <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
            //                     <i data-feather="more-vertical"></i>
            //                 </a>';
            // $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            // if (Auth::user()->can('permission-edit')) {
            // $buttons .=         '<a href="javascript:;" onclick="validasiedit(\''.$permission->id.'\',\''.$permission->name.'\',\''.$permission->display_name.'\',\''.$permission->description.'\')" class="dropdown-item">
            //                         <i data-feather="edit"></i>
            //                         Edit
            //                     </a>';
            // }
            // if (Auth::user()->can('permission-delete')) {
            // $buttons .=         '<a href="javascript:;" onclick="validasidelete(\''.$permission->id.'\')" class="dropdown-item">
            //                         <i data-feather="trash-2"></i>
            //                         Delete
            //                     </a>';
            // }
            // $buttons .=     '</div>';
            if (Auth::user()->can('permission-edit')) {
                $buttons .= '<a class="ml-1 my-tooltip" href="javascript:;" 
                                onclick="validasiedit(\''.$permission->id.'\',\''.$permission->name.'\',\''.$permission->group_name.'\',\''.$permission->display_name.'\',\''.$permission->description.'\')"
                                data-toggle="tooltip" data-placement="bottom" title="Edit"
                                >
                                <i class="feather-24" data-feather="edit"></i>
                            </a>';
            }
            if (Auth::user()->can('permission-delete')) {
                $buttons .= '<a class="ml-1 my-tooltip" href="javascript:;" onclick="validasidelete(\''.$permission->id.'\')"
                                data-toggle="tooltip" data-placement="bottom" title="Delete"
                                >
                                <i class="feather-24" data-feather="trash-2"></i>
                            </a>';
            }
            $buttons .='</div>';

            return $buttons;
            })
        ->rawColumns(['action'])
        ->make(true);
	}

    public function ddpermission(Request $request)
    {
		$filter=$request['filter'];
		$filter =='menu' ? $filter=" where display_name like '%menu%'": $filter=" where display_name not like '%menu%'";
		$sql = ("SELECT * FROM permissions $filter");
		$data = DB::select($sql);
        return  Response()->json($data);
	}
}
