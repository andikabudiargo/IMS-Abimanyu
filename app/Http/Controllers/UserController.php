<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Dept;
use App\Models\UserDept;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use DB;
use Hash;
use DataTables;

use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class UserController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');
 
        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'invalid_credentials'], 400);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }
 
        return response()->json(compact('token'));
    }
 
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            // 'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);
 
        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }
 
        $user = User::create([
            'name' => $request->get('name'),
            'username' => $request->get('name'),
            // 'email' => $request->get('email'),
            'status' => '1',
            'password' => Hash::make($request->get('password')),
        ]);
 
        $token = JWTAuth::fromUser($user);
 
        return response()->json(compact('user','token'),201);
    }
 
    public function getAuthenticatedUser()
    {
        try {
 
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }
 
        } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
 
            return response()->json(['token_expired'], $e->getStatusCode());
 
        } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
 
            return response()->json(['token_invalid'], $e->getStatusCode());
 
        } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {
 
            return response()->json(['token_absent'], $e->getStatusCode());
 
        }
 
        return response()->json(compact('user'));
    }

    public function getTableColoumn(){
        $kolom=[
            ['data'=>'action', 'name'=>'action','title'=>'action','orderable'=>false,'searchable'=>false],
            // ['data'=>'group_id','name'=>'group_id','title'=>'','orderable'=> false,'searchable'=> false],
            ['data'=>'name', 'name'=>'name','title'=>'Name'],
            ['data'=>'username', 'name'=>'username','title'=>'Username'],
            ['data'=>'dept', 'name'=>'dept','title'=>'Department'],
            ['data'=>'email', 'name'=>'email','title'=>'Email'],
            ['data'=>'status', 'name'=>'status','title'=>'Status'],
            ['data'=>'roles', 'name'=>'roles','title'=>'Roles'],
            ['data'=>'last_login_at', 'name'=>'last_login_at','title'=>'Last login'],
            ['data'=>'last_login_ip', 'name'=>'last_login_ip','title'=>'Last IP'],
            
        ];

        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        \LogActivity::addToLog('User index','masuk ke menu users');
        $data['kolom']=$this->getTableColoumn();
        return view('users.index',$data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function create()
    {
        \LogActivity::addToLog('User create','');
        $roles = Role::pluck('name','name')->all();
        $depts = Dept::pluck('name','code')->all();
        return view('users.create',compact('roles','depts'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {
        \LogActivity::addToLog('User save data');
        $this->validate($request, [
            'name' => 'required',
            'username' => 'required|unique:users,username',
            'password' => 'required|same:confirm-password',
            'roles' => 'required',
            'depts' => 'required'
        ]);
    
        $input = $request->all();
        $input['password'] = Hash::make($input['password']);
        $input['status'] = '1';
        $user = User::create($input);
        $user->assignRole($request->input('roles'));

        $username=$request->input('username'); 
        $depts = $request->input('depts');
        
        DB::table('user_dept')->where('username',$username)->delete();

        foreach ($depts as $val) {
            $data[] = [
                'username' => $username,
                'dept' => $val,
                'created_by' => Auth::user()->username,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }
        DB::table('user_dept')->insert($data);
            
        return redirect()->route('users.index')
                        ->with('success','User created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function show($id)
    {
        $user = User::find($id);
        return view('users.show',compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function edit($id)
    {
        $user = User::find($id);
        $roles = Role::pluck('name','name')->all();
        $userRole = $user->roles->pluck('name','name')->all();
        $depts = Dept::pluck('name','code')->all();
        $userDept = UserDept::where('username',$user->username)->pluck('dept')->all();
                
        return view('users.edit',compact('user','roles','userRole','depts','userDept'));
    }

    public function userUpdateStatus(Request $request)
    {
        $username=$request->username;
        $oldStatus=$request->oldStatus;
        $newStatus=$request->newStatus;

        \LogActivity::addToLog('User update status',"username: $username Status from $oldStatus to $newStatus");

        DB::beginTransaction();
        try {
                $row_affected=DB::table('users')->where('username',$username)->update(
                    [
                    'status'=>$newStatus,
                    'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                DB::commit();

                if($row_affected>0){
                    return response()->json(array('status' => 1, 'message' => 'Update status berhasil'));
                }else{
                    return response()->json(array('status' => 0, 'message' => 'Update status gagal'));
                }

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(array('status' => 0, 'message' => 'Update data gagal'));
        }



    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request, $id)
    {   
        
        \LogActivity::addToLog('User update data');

        $this->validate($request, [
            'name' => 'required',
            'password' => 'same:confirm-password',
            'roles' => 'required',
            'depts' => 'required'
        ]);

        $input = $request->all();
        if(!empty($input['password'])){ 
            $input['password'] = Hash::make($input['password']);
        }else{
            $input = Arr::except($input,array('password'));    
        }

        $user = User::find($id);
        $user->update($input);
        DB::table('model_has_roles')->where('model_id',$id)->delete();

        $user->assignRole($request->input('roles'));

        $username=$user->username;
        $depts = $request->input('depts');
        
        DB::table('user_dept')->where('username',$username)->delete();

        foreach ($depts as $val) {
            $data[] = [
                'username' => $username,
                'dept' => $val,
                'created_by' => Auth::user()->username,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }
        DB::table('user_dept')->insert($data);

        return redirect()->route('users.index')
                        ->with('success','User updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function destroy($id)
    {
        \LogActivity::addToLog('User Delete data');
        
        $user= User::find($id);
        $usernameDelete=$user->username;       
        DB::table('user_dept')->where('username',$usernameDelete)->delete();

        User::find($id)->delete();

        return redirect()->route('users.index')
                        ->with('success','User deleted successfully');
    }


    public function delete(Request $request)
    {
        $id = $request->userid;
        $username =  Auth::user()->username;
        $message = "User $id Deleted";
        $title = "User";
        \LogActivity::addToLog($title,"username: $username Status $message");

        $user= User::find($id);
        $usernameDelete=$user->username;       
        DB::table('user_dept')->where('username',$usernameDelete)->delete();

        User::find($id)->delete();
        return response()->json(array('message' => $message));
    }

    public function userProfile(Request $request)
    {
        $id = Auth::user()->id;
        $user = User::find($id);
        $depts = UserDept::where('username',$user->username)
        ->leftJoin('depts','depts.code','user_dept.dept')
        ->pluck('name','name')->toArray();
        // $depts = implode(",", $depts);
        return view('users.profile',compact('user','depts'));
    }

    public function changePassword(Request $request)
    {

        \LogActivity::addToLog('Change password','');

        $this->validate($request, [
            'oldPassword' => ['required'],
            'newPassword' => ['required'],
            'retypeNewPassword' => ['same:newPassword'],
        ]);
     
        $hashedPassword = Auth::user()->password;
     
        if (\Hash::check($request->oldPassword , $hashedPassword )) {
     
             if (!\Hash::check($request->newPassword , $hashedPassword)) {
                $users =user::find(Auth::user()->id);
                $users->password = bcrypt($request->newPassword);
                user::where( 'id' , Auth::user()->id)->update( array( 'password' =>  $users->password));
                $message = 'Password updated successfully';
            }else{
                $message ='New password can not be the old password!';
            }
        }else{           
            $message ="Old password doesn't matched";
        }

        return response()->json([
            'message' => $message
        ]);

    }

    public function changeProfile(Request $request)
    {

        \LogActivity::addToLog('Change profile','');

        $this->validate($request, [
            'username' => ['required'],
            'name' => ['required'],
        ]);

        $username = $request->username;

        DB::beginTransaction();
        try {
                $row_affected=DB::table('users')->where('username',$username)->update(
                    [
                        'name' => $request->name,
                        'email' => $request->email,
                    ]
                );

                DB::commit();

                if($row_affected>0){
                    return response()->json([
                        'message' => "Profile updated successfully"
                    ]);
                }else{
                    return response()->json([
                        'message' => "Profile updated failed"
                    ]);
                }

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => "Profile updated failed"
            ]);
        }

    }


    public function detlistuser()
    {
        $sql = ("SELECT * from users");
        $users = DB::select($sql);
        return  Response()->json($users);
    }

    public function userLists(Request $request)
    {
        $query = $request->get('q');
        $user = User::where('name', 'ilike', '%' . $query . '%')
        ->select('users.*'
        ,db::raw("(select STRING_AGG((select name from depts where code = user_dept.dept), ',' ORDER BY dept) AS main from user_dept where username = users.username) as dept")
        );
            
        return Datatables::of($user)
        ->addColumn('action', function ($user) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            $buttons .=         '<a href="'. route('users.edit', $user->id) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
            $buttons .=         '<a href="javascript:;" onclick="validasidelete(\''.$user->id.'\',\''.$user->username.'\')" class="dropdown-item">
                                    <i data-feather="trash-2" class="feather-14-red"></i>
                                    Delete
                                </a>';
            $buttons .=     '</div>
                        </div>';

            return $buttons;
            })
        ->addColumn('roles', function ($user) {
            $isinya=''; 
            foreach($user->getRoleNames() as $v) {
                // $isinya.= $v;
                $isinya.= "<div class='badge badge-info'>".$v."</div>". "  ";
            }
            return $isinya;
        })
        ->addColumn('group_id', function ($user) {
            return '';
        })
        ->addColumn('dept', function ($user) {
            $depts = explode(",",$user->dept);
            $deptList='';
            foreach($depts as $val) {
                $deptList.= "<div class='badge badge-success'>".$val."</div>". "  ";
            }
            return $deptList;
        })
        ->addColumn('status', function ($user) {
            if ($user->status =='1') {
                $status = '<div class="custom-control custom-switch custom-control-inline">
                                <input type="checkbox" class="custom-control-input userLock" id="userLock_'.$user->username.'" data-nama="'.$user->username.'" checked/>
                                <label id="lblUserLock_'.$user->username.'" class="custom-control-label" for="userLock_'.$user->username.'">Active</label>
                            </div>';
            } else {
                $status = '<div class="custom-control custom-switch custom-control-inline">
                                <input type="checkbox" class="custom-control-input userLock" id="userLock_'.$user->username.'" data-nama="'.$user->username.'"/>
                                <label id="lblUserLock_'.$user->username.'" class="custom-control-label" for="userLock_'.$user->username.'">Locked</label>
                            </div>';
            }
            return $status;
        })
        ->rawColumns(['action','status','dept','roles'])
        ->make(true);
    }
}
