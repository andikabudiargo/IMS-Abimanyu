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

class BankController extends Controller
{
    public function index(Request $request)
    {
        $data['title'] = "Banks";
        return view("bank.index",$data);
    }

    public function create(Request $request)
    {
        $data['title'] = "Create Bank";
        $data['subtitle'] = "Create New Bank";
                        
        return view("bank.create",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $type = $request->input('bankType');
        $name = strtoupper($request->input('bankName'));
        $accNumber = $request->input('accNumber');
        $branch = strtoupper($request->input('branch'));
        $isActive = '1';
        $pesan = '';
        
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken',
            'iunique' => "The code $accNumber has already been taken",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $rule = [
            'accNumber'=>'required|iunique:supplier_banks,account_number',
            'bankName'=>'required',
            'branch'=>'required'
        ];

        $this->validate($request,$rule,$messages);

        DB::beginTransaction();
        try {
                DB::table('supplier_banks')->insert([
                    'bank_type'=>$type,
                    'bank_name'=>$name,
                    'bank_branch'=>$branch,
                    'account_number'=>$accNumber,
                    'is_active'=>$isActive,
                    'created_by' => Auth::user()->username,
                    'updated_by' => Auth::user()->username,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                DB::commit();
                $alert  ="alert-success";
                $message  = "$name is successfully saved";
                \LogActivity::addToLog('Bank save ',"username: $username Status $message");
                return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  

        } catch (Exception $e) {
            DB::rollBack();
            $alert  ="alert-warning";
            $message  = "$name is failed to save";
            \LogActivity::addToLog('Bank save ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);   
        }
        
    }

    public function edit(Request $request)
    {

        $id=$request->id;
        $data['title'] = "Edit Bank";
        $data['subtitle'] = "Edit Bank";
        $data['supplier_banks'] = DB::table('supplier_banks')
        ->where('id',$id)
        ->get()->first();

        return view('bank.edit',$data);
        
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $id = $request->id;
        $type = $request->input('bankType');
        $name = strtoupper($request->input('bankName'));
        $accNumber = $request->input('accNumber');
        $branch = strtoupper($request->input('branch'));
        $isActive = '1';
        $pesan = '';
        
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken',
            'iunique' => "The code $accNumber has already been taken",
        ];
        
        $rule = [
            'bankName'=>'required',
            'accNumber'=>'required',
            'branch'=>'required'
        ];

        $this->validate($request,$rule,$messages);
        
        DB::beginTransaction();

        try {
                $row_affected=DB::table('supplier_banks')
                ->where('id',$id)
                ->update(
                    [
                        'bank_type'=>$type,
                        'bank_name'=>$name,
                        'bank_branch'=>$branch,
                        'account_number'=>$accNumber,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                DB::commit();

                if($row_affected>0){
                    $alert  ="alert-success";
                    $message  = "Successfully updated";
                    \LogActivity::addToLog('Bank update ',"username: $username Status $message");
                    return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  
                }else{
                    $alert  ="alert-warning";
                    $message  = "Failed to update";
                    \LogActivity::addToLog('Bank update ',"username: $username Status $message");
                    return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
                }

        } catch (Exception $e) {
            DB::rollBack();
            $alert  ="alert-warning";
            $message  = "Failed to update";
            \LogActivity::addToLog('Bank update ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
        }
    }

    public function destroy(Request $request)
    {

        $username =  Auth::user()->username;
        $id = $request->id;

        $row_affected = DB::table('supplier_banks')
        ->where('id',$id)
        ->delete();

        if($row_affected > 0){
            $alert  ="alert-success";
            $message  = "Successfully Deleted";
            \LogActivity::addToLog('Bank delete ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  
        }else{
            $alert  ="alert-warning";
            $message  = "Failed to Delete";
            \LogActivity::addToLog('Account type delete ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
        }

    }

    public function list(Request $request)
    {
        $type = strtolower($request->code);
        $name = strtolower($request->name);

        //ilike = string to lower
        $data=DB::table('supplier_banks')
        ->where(function ($query) use ($type,$name) {
            $type ? $query->where('bank_type','ilike','%'.$type.'%') :'';
            $name ? $query->where('bank_name','ilike','%'.$name.'%') :'';
        })->orderBy('bank_name')->get();;

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            if (Auth::user()->can('bank-edit')) {
            $buttons .=         '<a href="'. route('bank.edit', ['id'=>$data->id]) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
            }
            if (Auth::user()->can('bank-delete')) {
            $buttons .=         "<a href='javascript:;'
                                    id='deleteButton'
                                    class='dropdown-item'
                                    data-toggle='modal'
                                    data-target='#smallModal'
                                    data-href='". route("bank.destroy", ["id"=>$data->id]) ."'>
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
