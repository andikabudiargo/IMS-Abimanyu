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
            ['data'=> 'opening_balance', 'name'=>'opening_balance','title'=>'Opening Balance' ],
            ['data'=> 'type', 'name'=>'type','title'=>'Account Type' ],
            ['data'=> 'sub_account', 'name'=>'sub_account','title'=>'Sub Account' ],
            ['data'=> 'acc_header', 'name'=>'acc_header','title'=>'Category' ],
            ['data'=> 'header', 'name'=>'header','title'=>'Header' ],
            ['data'=> 'debit_credit', 'name'=>'debit_credit','title'=>'Normal Balance' ],
            ['data'=> 'final_statement', 'name'=>'final_statement','title'=>'Final Statement' ],
            ['data'=> 'note', 'name'=>'note','title'=>'Note' ]
        ];
        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $data['title'] = $this->title;
        $data['tree'] = $this->buildTree();
        $data['flat'] = $this->flattenTree($data['tree']);
        return view("accounts.index",$data);
    }

    /** Flat list for the plain-table view: every account with its Type/Sub/Header as columns. */
    private function flattenTree($tree)
    {
        $rows = collect();
        foreach ($tree as $type) {
            foreach ($type->subs as $sub) {
                $this->flattenAccounts($sub->accounts, $type, $sub, null, $rows);
            }
        }
        return $rows;
    }

    private function flattenAccounts($accounts, $type, $sub, $parentHeader, $rows)
    {
        foreach ($accounts as $acc) {
            $acc->type_label = $type->code . ' - ' . $type->name;
            $acc->sub_label = $sub->sub_code . ' - ' . $sub->description;
            $acc->header_label = $parentHeader ? ($parentHeader->account . ' - ' . $parentHeader->description) : '';
            $rows->push($acc);
            if (strtoupper($acc->acc_header) === 'HEADER') {
                $this->flattenAccounts($acc->children, $type, $sub, $acc, $rows);
            }
        }
    }

    /**
     * COA tree: Account Type -> Sub Account -> Header (recursive) -> Detail.
     *
     * Everything below Sub Account is nested by dotted CODE PREFIX (e.g. "1100.13.23"
     * under header "1100.13"), not by accounts.parent_id: in production data, detail
     * rows are very often parented straight to the Sub even when an intermediate header
     * with a matching code prefix exists, and parent_id chains aren't reliable past that
     * point anyway. The account's own code is the one relationship this data always gets
     * right, so a Sub's scope is every account whose code's first dot-segment is its
     * sub_code.
     */
    private function buildTree()
    {
        $subsByType = DB::table('acc_sub')->orderBy('sub_code')->get()->groupBy('type_code');
        $accountsBySubPrefix = DB::table('accounts')->get()->groupBy(function ($acc) {
            return explode('.', $acc->account)[0];
        });

        return DB::table('acc_types')->where('status', 1)->orderBy('code')->get()
            ->map(function ($type) use ($subsByType, $accountsBySubPrefix) {
                $type->subs = ($subsByType[$type->code] ?? collect())->map(function ($sub) use ($accountsBySubPrefix) {
                    $sub->accounts = $this->nestByCodePrefix($accountsBySubPrefix[$sub->sub_code] ?? collect());
                    return $sub;
                });
                return $type;
            });
    }

    /** Nest a flat list of accounts into a tree by dotted code prefix, sorted naturally by code. */
    private function nestByCodePrefix($accounts)
    {
        $byCode = [];
        foreach ($accounts as $acc) {
            $acc->children = collect();
            $byCode[$acc->account] = $acc;
        }

        $roots = collect();
        foreach ($accounts as $acc) {
            $parts = explode('.', $acc->account);
            $parentCode = null;
            for ($i = count($parts) - 1; $i >= 1; $i--) {
                $candidate = implode('.', array_slice($parts, 0, $i));
                if ($candidate !== $acc->account && isset($byCode[$candidate])) {
                    $parentCode = $candidate;
                    break;
                }
            }
            $parentCode !== null ? $byCode[$parentCode]->children->push($acc) : $roots->push($acc);
        }

        foreach ($byCode as $acc) {
            $acc->children = $this->sortByCode($acc->children);
        }

        return $this->sortByCode($roots);
    }

    private function sortByCode($accounts)
    {
        return $accounts->sortBy(function ($acc) {
            $pad = function ($seg) { return str_pad($seg, 10, '0', STR_PAD_LEFT); };
            return implode('.', array_map($pad, explode('.', $acc->account)));
        })->values();
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
        $status = '1';
        $other = '';
        $subAccount = $request->subAccount;
        // $cashBank = $request->cashBank;
        $cashBank = '';
        $accHeader = $request->accHeader;
        $accDebitCredit = $request->accDebitCredit;
        $accFinalStatement = $request->accFinalStatement;
        $note = $request->note;
        
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
                    'parent_id' => $subAccount,
                    'acc_header' => $accHeader ,
                    'debit_credit' => $accDebitCredit,
                    'final_statement' =>$accFinalStatement ,
                    'note' => $note

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
        ->where('type_code',$data['accounts']->type_code)
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
        $subAccount = $request->subAccount;
        // $cashBank = $request->cashBank;
        $cashBank = '';
        $accHeader = $request->accHeader;
        $accDebitCredit = $request->accDebitCredit;
        $accFinalStatement = $request->accFinalStatement;
        $note = $request->note;

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
                        'parent_id' => $subAccount,
                        'acc_header' => $accHeader ,
                        'debit_credit' => $accDebitCredit,
                        'final_statement' =>$accFinalStatement ,
                        'note' => $note
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
        ->leftJoin('accounts as header_acc', function($join){
            $join->on('header_acc.account', '=', 'accounts.parent_id')
                ->where('header_acc.acc_header', '=', 'HEADER');
        })
        ->where('accounts.account','ilike','%'.$code.'%')
        ->where('accounts.description','ilike','%'.$name.'%')
        ->select(
            'accounts.*',
            DB::raw("CASE WHEN acc_types.code IS NOT NULL THEN acc_types.code || ' - ' || acc_types.name ELSE '' END AS type"),
            DB::raw("CASE WHEN acc_sub.sub_code IS NOT NULL THEN acc_sub.sub_code || ' - ' || acc_sub.description ELSE '' END AS sub_account"),
            DB::raw("CASE WHEN header_acc.account IS NOT NULL THEN header_acc.account || ' - ' || header_acc.description ELSE '' END AS header")
        )
        ->orderByRaw("
            NULLIF(split_part(accounts.account, '.', 1), '')::numeric,
            NULLIF(split_part(accounts.account, '.', 2), '')::numeric,
            NULLIF(split_part(accounts.account, '.', 3), '')::numeric,
            accounts.account
        ");

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
        ->orderColumn('account', function ($query, $order) {
            $order = strtolower($order) === 'desc' ? 'desc' : 'asc';
            $query->orderByRaw("
                NULLIF(split_part(accounts.account, '.', 1), '')::numeric $order,
                NULLIF(split_part(accounts.account, '.', 2), '')::numeric $order,
                NULLIF(split_part(accounts.account, '.', 3), '')::numeric $order,
                accounts.account $order
            ");
        })
        ->rawColumns(['action','blacklist'])
        ->make(true);
    }
}
