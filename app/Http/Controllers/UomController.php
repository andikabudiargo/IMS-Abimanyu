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

class UomController extends Controller
{
    private $title;
    public function __construct()
    {
        $this->title = "UOM";
    }

    public function index(Request $request)
    {
        $data['title'] = $this->title. " (Unit of measurement)";
        return view("uom.index",$data);
    }

    public function create(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create New $this->title";
                        
        return view("uom.create",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $kode = strtoupper($request->input('kode'));
        $nama = $request->input('nama');
        $uomType = $request -> input('uomType');
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
            'kode' => 'required|iunique:uom,code',
            'nama' => 'required',
            'uomType' => 'required'
        ];

        $this->validate($request,$rule,$messages);

        DB::beginTransaction();
        try {
                DB::table('uom')->insert([
                    'code'=>$kode,
                    'name'=>$nama,
                    'uom_group' => $uomType,
                    'created_by' => Auth::user()->username,
                    'updated_by' => Auth::user()->username,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                DB::commit();
                $title ="Save $this->title";
                $alert  ="success";
                $message  = "$this->title $kode is successfully saved";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);  

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Save $this->title";
            $alert  ="warning";
            $message  = "$this->title $kode is failed to saved";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }
    }

    public function edit(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";
        $data['uom'] = DB::table('uom')

        ->where('id',$id)
        ->get()->first();

        return view('uom.edit',$data);
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $kode = strtoupper($request->input('kode'));
        $nama = $request->input('nama');
        $uomType = $request -> input('uomType');
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
                $row_affected=DB::table('uom')
                ->where('id',$id)
                ->update(
                    [
                        'name'=>$nama,
                        'uom_group' => $uomType,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                DB::commit();

                if($row_affected>0){
                    DB::commit();
                    $title ="Update $this->title";
                    $alert  ="success";
                    $message  = "$this->title $kode is successfully updated";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return redirect()->back()->with(['title' => $title, 'alert'=>$alert,'message'=> $message]);
                }else{
                    $title ="Update $this->title";
                    $alert  ="warning";
                    $message  = "$this->title $kode is failed to updated";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return redirect()->back()->with(['title' => $title, 'alert'=>$alert,'message'=> $message]);
                }

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Update $this->title";
            $alert  ="warning";
            $message  = "$this->title $kode is failed to updated";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title, 'alert'=>$alert,'message'=> $message]);
        }
    }

    public function destroy(Request $request)
    {
        $username =  Auth::user()->username;
        $id = $request->id;
        $name = $request->name;

        $row_affected = DB::table('uom')
        ->where('id',$id)
        ->delete();

        if($row_affected>0){
            $title ="Delete $this->title";
            $alert  ="success";
            $message  = "$this->title $name is successfully deleted";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'name'=>$name]);
        }else{
            $title ="Delete $this->title";
            $alert  ="warning";
            $message  = "$this->title $name is failed to delete";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'name'=>$name]);
        }
    }

    public function list(Request $request)
    {
        $code = strtolower($request->code);
        $name = strtolower($request->name);

        $data = DB::table('uom')
        ->where(function ($query) use ($code,$name) {
            $code ? $query->where('code','ilike','%'.$code.'%') : '';
            $name ? $query->where('name','ilike','%'.$name.'%') : '';
        })->orderBy('name')->get(); 

        return DataTables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            if (Auth::user()->can('department-edit')) {
            $buttons .=         '<a href="'. route('uom.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
            }
            if (Auth::user()->can('department-delete')) {
            $buttons .=         "<a href='javascript:;'
                                    id='deleteButton'
                                    class='dropdown-item'
                                    data-toggle='modal'
                                    data-target='#smallModal'
                                    data-href='". route("uom.destroy", ['id'=>$data->id,'uomCode'=>$data->name]) ."'>
                                    <i data-feather='trash-2'></i>
                                    Delete
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
