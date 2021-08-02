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

class JobPositionController extends Controller
{
    public function index(Request $request)
    {
        $data['title'] = "Jabatan";
        return view("jobPositions.index",$data);
    }

    public function create(Request $request)
    {
        $data['title'] = "Create Jabatan";
        $data['subtitle'] = "Create New Jabatan";
                        
        return view("jobPositions.create",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $kode = $request->input('kode');
        $nama = strtoupper($request->input('nama'));
        $desc = $request->input('desc');
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
            'kode'=>'required|iunique:job_position,code',
            'nama'=>'required'
        ];

        $this->validate($request,$rule,$messages);

        DB::beginTransaction();
        try {
                DB::table('job_position')->insert([
                    'code'=>$kode,
                    'name'=>$nama,
                    'status'=>$status,
                    'description'=>$desc,
                    'created_by' => Auth::user()->username,
                    'updated_by' => Auth::user()->username,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                DB::commit();
                $alert  ="alert-success";
                $message  = "$kode is successfully saved";
                \LogActivity::addToLog('Jabatan save ',"username: $username Status $message");
                return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  

        } catch (Exception $e) {
            DB::rollBack();
            $alert  ="alert-warning";
            $message  = "$kode is failed to save";
            \LogActivity::addToLog('Jabatan save ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);   
        }
        
    }

    public function edit(Request $request)
    {

        $id=$request->id;
        $data['title'] = "Edit Jabatan";
        $data['subtitle'] = "Edit Jabatan";
        $data['position'] = DB::table('job_position')
        ->where('id',$id)
        ->get()->first();

        return view('jobPositions.edit',$data);
        
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $id = $request->id;
        $kode = $request->input('kode');
        $nama = strtoupper($request->input('nama'));
        $desc = $request->input('desc');
        $status = '1';
        $pesan = '';
        
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken',
            'iunique' => "The code $kode has already been taken",
        ];
        

        $rule = [
            'nama'=>'required'
        ];

        $this->validate($request,$rule,$messages);
        
        DB::beginTransaction();

        try {
                $row_affected=DB::table('job_position')
                ->where('id',$id)
                ->update(
                    [
                        'name'=>$nama,
                        'status'=>$status,
                        'description'=>$desc,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                DB::commit();

                if($row_affected>0){
                    $alert  ="alert-success";
                    $message  = "Successfully updated";
                    \LogActivity::addToLog('Jabatan update ',"username: $username Status $message");
                    return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  
                }else{
                    $alert  ="alert-warning";
                    $message  = "Failed to update";
                    \LogActivity::addToLog('Jabatan update ',"username: $username Status $message");
                    return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
                }

        } catch (Exception $e) {
            DB::rollBack();
            $alert  ="alert-warning";
            $message  = "Failed to update";
            \LogActivity::addToLog('Jabatan update ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
        }
    }

    public function destroy(Request $request)
    {

        $username =  Auth::user()->username;
        $id = $request->id;

        $row_affected = DB::table('job_position')
        ->where('id',$id)
        ->delete();

        if($row_affected>0){
            $alert  ="alert-success";
            $message  = "Successfully Deleted";
            \LogActivity::addToLog('Jabatan delete ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  
        }else{
            $alert  ="alert-warning";
            $message  = "Failed to Delete";
            \LogActivity::addToLog('Jabatan delete ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
        }

    }

    public function list(Request $request)
    {
        $code = strtolower($request->code);
        $name = strtolower($request->name);

        $data=DB::table('job_position')
        ->where('code','ilike','%'.$code.'%')
        ->where('name','ilike','%'.$name.'%')  // string to lower
        ->orderBy('name')->get();

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="more-vertical"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            if (Auth::user()->can('jobPosition-edit')) {
            $buttons .=         '<a href="'. route('jobPosition.edit', ['id'=>$data->id]) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
            }
            if (Auth::user()->can('jobPosition-delete')) {
            $buttons .=         "<a href='javascript:;'
                                    id='deleteButton'
                                    class='dropdown-item'
                                    data-toggle='modal'
                                    data-target='#smallModal'
                                    data-href='". route("jobPosition.destroy", ["id"=>$data->id]) ."'>
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
