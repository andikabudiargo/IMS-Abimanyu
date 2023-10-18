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

class AccountController extends Controller
{

    private $title;
    private $moduleCode;
    public function __construct()
    {
        $this->title = "Account";
        $this->moduleCode = "ACC";
    }

    public function getTableColoumn(){
        $kolom=
        [
            ['data'=> 'action', 'name'=>'action','title'=>'action', 'orderable'=> false, 'searchable'=> false ],
            ['data'=> 'account', 'name'=>'account','title'=>'Account' ],
            ['data'=> 'description', 'name'=>'description','title'=>'Description' ],
            ['data'=> 'sub_account', 'name'=>'sub_account','title'=>'Sub Account' ],
            ['data'=> 'type', 'name'=>'type','title'=>'Account Type' ],
            ['data'=> 'opening_balance', 'name'=>'opening_balance','title'=>'Opening Balance' ],
        ];
        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $data['title'] = $this->title;
        $data['kolom'] = $this->getTableColoumn();
        return view("accounts.index",$data);
    }

    public function create(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create New $this->title";
                
        $data['groups'] = DB::table('groups')
        ->where ('status','=',1)
        ->orderBy('name')
        ->get();

        $data['depts'] = DB::table('depts')
        ->where ('status','=',1)
        ->orderBy('name')
        ->get();

        $data['types'] = DB::table('acc_types')
        ->where ('status','=',1)
        ->orderBy('name')
        ->get();

        $data['subAcc'] = DB::table('acc_sub')
        ->orderBy('description')
        ->get();
        
        return view("accounts.create",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $account = strtoupper($request->account);
        $desc = $request->desc;
        $openingBalance = is_null($request->openingBalance) ? 0 : preg_replace('/[^0-9.]+/', '', $request->openingBalance);
        $group = $request->group;
        $type = $request->type;
        $dept = $request->dept;
        $cashBank = $request->cashBank;
        $status = '1';
        $other = '';
        $subAccount = $request->subAccount;
        
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The Account has already been taken',
            'iunique' => "The $account has already been taken",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $rule = [
            'account'=>'required|iunique:accounts,account',
            'desc'=>'required'
        ];

        $this->validate($request,$rule,$messages);

        DB::beginTransaction();
        try {
                DB::table('accounts')->insert([
                    'account' => $account,
                    'description' => $desc,
                    'opening_balance' => $openingBalance,
                    'group_code' => $group,
                    'type_code' => $type,
                    'dept_code' => $dept,
                    'created_date' => date('Y-m-d'),
                    'cash_bank' => $cashBank,
                    'status' => $status,
                    'other' => $other,
                    'created_by' => Auth::user()->username,
                    'updated_by' => Auth::user()->username,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'parent_id' => $subAccount
                ]);

                DB::commit();
                $title ="Save $this->title";
                $alert  ="success";
                $message  = "$account successfully saved";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with(['status' => 1,'alert'=>$alert,'message'=> $message]); 

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Save $this->title";
            $alert  ="warning";
            $message  = "$account is failed to save";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['status' => 1,'alert'=>$alert,'message'=> $message]); 
        }
    }

    public function edit(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Edit Account";
        $data['subtitle'] = "Edit Account";
        $data['groups'] = DB::table('groups')
        ->where ('status','=',1)
        ->orderBy('name')
        ->get();

        $data['depts'] = DB::table('depts')
        ->where ('status','=',1)
        ->orderBy('name')
        ->get();

        $data['types'] = DB::table('acc_types')
        ->where ('status','=',1)
        ->orderBy('name')
        ->get();

        $data['accounts'] = DB::table('accounts')
        ->where('id',$id)
        ->get()->first();

        $data['subAcc'] = DB::table('acc_sub')
        ->orderBy('description')
        ->get();

        return view('accounts.edit',$data);

    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $account = strtoupper($request->account);
        $desc = $request->desc;
        // $openingBalance = $request->openingBalance ? preg_replace('/[^0-9.]+/', '', $request->openingBalance):0;
        $openingBalance = is_null($request->openingBalance) ? 0 : preg_replace('/[^0-9.]+/', '', $request->openingBalance);
        $group = $request->group;
        $type = $request->type;
        $dept = $request->dept;
        $cashBank = $request->cashBank;
        $subAccount = $request->subAccount;

        $status = '1';
        $other = '';
        
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken',
            'iunique' => 'The code has already been taken',
        ];
        
        $rule = [
            'desc'=>'required'
        ];

        $this->validate($request,$rule,$messages);

        DB::beginTransaction();

        try {
                $row_affected=DB::table('accounts')
                ->where('id',$id)
                ->update(
                    [
                        'description' => $desc,
                        'opening_balance' => $openingBalance,
                        'group_code' => $group,
                        'type_code' => $type,
                        'dept_code' => $dept,
                        'cash_bank' => $cashBank,
                        'status' => $status,
                        'other' => $other,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'parent_id' => $subAccount
                    ]
                );

                DB::commit();

                if($row_affected>0){
                    $title ="Update $this->title";
                    $alert  ="success";
                    $message  = "Successfully updated";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return redirect()->back()->with(['status' => 1,'alert'=>$alert,'message'=> $message]);  
                }else{
                    $title ="Update $this->title";
                    $alert  ="warning";
                    $message  = "Failed to update";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return redirect()->back()->with(['status' => 1,'alert'=>$alert,'message'=> $message]);
                }

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Update $this->title";
            $alert  ="warning";
            $message  = "Failed to update";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['status' => 1,'alert'=>$alert,'message'=> $message]);
        }
        
    }

    public function destroy(Request $request)
    {
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);

        $row_affected = DB::table('accounts')
        ->where('id',$id)
        ->delete();

        if($row_affected>0){
            $title ="Delete account";
            $alert  ="success";
            $message  = "Successfully Deleted";
            \LogActivity::addToLog('Account delete ',"username: $username Status $message");
            return redirect()->back()->with(['title'=>$title,'alert'=>$alert,'message'=> $message]);  
        }else{
            $title ="Delete account";
            $alert  ="warning";
            $message  = "Failed to Delete";
            \LogActivity::addToLog('Account delete ',"username: $username Status $message");
            return redirect()->back()->with(['title'=>$title,'alert'=>$alert,'message'=> $message]);
        }
    }

    public function list(Request $request)
    {
        
        $code = strtolower($request->code);
        $name = strtolower($request->name);

        $data=DB::table('accounts')
        ->leftJoin('acc_types', 'acc_types.code', '=', 'accounts.type_code')
        ->leftJoin('acc_sub', 'acc_sub.sub_code', '=', 'accounts.parent_id')
        ->where('account','ilike','%'.$code.'%')
        ->where('accounts.description','ilike','%'.$name.'%')  
        ->select('accounts.*','acc_types.name as type','acc_sub.description as sub_account')
        ->orderBy('account')->get();

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                    <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                        <i data-feather="menu"></i>
                    </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';

            if (Auth::user()->can('account-edit')) {
                $buttons .=         '<a href="'. route('account.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="file-text"></i>
                                        Edit
                                    </a>';
            }

            if (Auth::user()->can('account-delete')) {
                $buttons .=         "<a href='javascript:;'
                                    id='deleteButton'
                                    class='dropdown-item'
                                    data-toggle='modal'
                                    data-target='#smallModal'
                                    data-href='". route("account.destroy", ['id'=>Crypt::encryptString($data->id)]) ."'>
                                    <i data-feather='trash-2' class='feather-14-red'></i>
                                    Delete
                                </a>";
            }

            $buttons .=     '</div>
                        </div>';

            return $buttons;
            })
        ->addColumn('blacklist', function ($data) {
            if ($data->status =='1') {
                $status = '<div class="custom-control custom-switch custom-control-inline">
                                <input type="checkbox" class="custom-control-input blackList" id="blackList_'.$data->id.'" data-nama="'.$data->id.'" checked/>
                                <label id="lblBlackList_'.$data->id.'" class="custom-control-label" for="blackList_'.$data->id.'">Active</label>
                            </div>';
            } else {
                $status = '<div class="custom-control custom-switch custom-control-inline">
                                <input type="checkbox" class="custom-control-input blackList" id="blackList_'.$data->id.'" data-nama="'.$data->id.'"/>
                                <label id="lblBlackList_'.$data->id.'" class="custom-control-label" for="blackList_'.$data->id.'">Locked</label>
                            </div>';
            }
            return $status;
        })
        ->rawColumns(['action','blacklist'])
        ->make(true);
    }
}
