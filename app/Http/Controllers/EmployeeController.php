<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Response;
use App\Permission;
use DataTables;
use DB;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $data['title'] = "Employee";
        $data['positions'] = DB::table('job_position')
        ->where ('status','=',1)
        ->orderBy('name')
        ->get();

        $data['depts'] = DB::table('depts')
        ->where ('status','=',1)
        ->orderBy('name')
        ->get();
        
        return view("employees.index",$data);
    }

    public function create(Request $request)
    {
        $data['title'] = "Create Employee";
        $data['subtitle'] = "Create New Employee";
        
        $data['positions'] = DB::table('job_position')
        ->where ('status','=',1)
        ->orderBy('name')
        ->get();

        $data['depts'] = DB::table('depts')
        ->where ('status','=',1)
        ->orderBy('name')
        ->get();
                        
        return view("employees.create",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $kode = $request->input('kode');
        $nama = strtoupper($request->input('nama'));
        $dept = $request->input('dept');
        $position = $request->input('position');
        
        $status = '1';
        $pesan = '';
        
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken',
            'iunique' => "The code $kode has already been taken",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $rule = [
            'kode'=>'required|iunique:employees,employee_id',
            'nama'=>'required'
        ];

        $this->validate($request,$rule,$messages);

        DB::beginTransaction();
        try {
                DB::table('employees')->insert([
                    'employee_id'=> $kode,
                    'name'=> $nama,
                    'department'=> $dept,
                    'job_position'=> $position,
                    'status'=>$status,
                    'created_by' => Auth::user()->username,
                    'updated_by' => Auth::user()->username,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                DB::commit();
                $alert  ="alert-success";
                $message  = "$kode is successfully saved";
                \LogActivity::addToLog('Employee save ',"username: $username Status $message");
                return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  

        } catch (Exception $e) {
            DB::rollBack();
            $alert  ="alert-warning";
            $message  = "$kode is failed to save";
            \LogActivity::addToLog('Employee save ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);   
        }
        
    }

    public function edit(Request $request)
    {

        $id=$request->id;
        $data['title'] = "Edit Employee";
        $data['subtitle'] = "Edit Employee";
        
        $data['employee'] = DB::table('employees')
        ->where('id',$id)
        ->get()->first();

        $data['positions'] = DB::table('job_position')
        ->where ('status','=',1)
        ->orderBy('name')
        ->get();

        $data['depts'] = DB::table('depts')
        ->where ('status','=',1)
        ->orderBy('name')
        ->get();

        return view('employees.edit',$data);
        
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $id = $request->id;
        $nama = strtoupper($request->input('nama'));
        $dept = $request->input('dept');
        $position = $request->input('position');
        $status = '1';
        $pesan = '';
        
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken',
            'iunique' => "The code $nama has already been taken",
        ];
        
        $rule = [
            'nama'=>'required'
        ];

        $this->validate($request,$rule,$messages);
        
        DB::beginTransaction();

        try {
                $row_affected=DB::table('employees')
                ->where('id',$id)
                ->update(
                    [
                        'name'=> $nama,
                        'department'=> $dept,
                        'job_position'=> $position,
                        'status'=>$status,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                DB::commit();

                if($row_affected>0){
                    $alert  ="alert-success";
                    $message  = "Successfully updated";
                    \LogActivity::addToLog('Employee update ',"username: $username Status $message");
                    return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  
                }else{
                    $alert  ="alert-warning";
                    $message  = "Failed to update";
                    \LogActivity::addToLog('Employee update ',"username: $username Status $message");
                    return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
                }

        } catch (Exception $e) {
            DB::rollBack();
            $alert  ="alert-warning";
            $message  = "Failed to update";
            \LogActivity::addToLog('Employee update ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
        }
    }

    public function destroy(Request $request)
    {

        $username =  Auth::user()->username;
        $id = $request->id;

        $row_affected = DB::table('employees')
        ->where('id',$id)
        ->delete();

        if($row_affected>0){
            $alert  ="alert-success";
            $message  = "Successfully Deleted";
            \LogActivity::addToLog('Employee delete ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  
        }else{
            $alert  ="alert-warning";
            $message  = "Failed to Delete";
            \LogActivity::addToLog('Employee delete ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
        }

    }

    public function list(Request $request)
    {
        $code = strtolower($request->code);
        $name = strtolower($request->name);
        $dept = strtolower($request->dept);
        $pos = strtolower($request->pos);
        
        $data=DB::table('employees')
        ->where('employee_id','ilike','%'.$code.'%')
        ->where('name','ilike','%'.$name.'%')  // string to lower
        ->orderBy('name')->get();

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="dropdown-toggle" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            if (Auth::user()->can('employee-edit')) {
            $buttons .=         '<a href="'. route('employee.edit', ['id'=>$data->id]) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
            }
            if (Auth::user()->can('employee-delete')) {
            $buttons .=         "<a href='javascript:;'
                                    id='deleteButton'
                                    class='dropdown-item'
                                    data-toggle='modal'
                                    data-target='#smallModal'
                                    data-href='". route("employee.destroy", ["id"=>$data->id]) ."'>
                                    <i data-feather='trash-2'></i>
                                    Delete
                                </a>";
            }
            $buttons .=     '</div>
                        </div>';

            return $buttons;
            })
        ->addColumn('group_id', function ($user) {
            return '';
        })
        ->rawColumns(['action'])
        ->make(true);
    }
}
