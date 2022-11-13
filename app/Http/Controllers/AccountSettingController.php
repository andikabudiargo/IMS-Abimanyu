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

class AccountSettingController extends Controller
{
    private $title;
    private $decimalPlaces;
    public function __construct()
    {
        $this->title = "Akun default";
        $this->decimalPlaces = config('globalParam.decimal');
    }

    public function barang(Request $request)
    {
        $data['title'] = "$this->title barang";
        $data['accounts'] = DB::table('accounts')
        ->orderBy('account')
        ->orderBy('description')
        ->get();

        $data['accDefaults'] = DB::table('acc_default')
        ->where('type','barang')
        ->orderBy('type')
        ->orderBy('code')
        ->get();

        return view("accountSetting.barang",$data);
    }

    public function mataUang(Request $request)
    {
        $data['title'] = "$this->title mata uang";
        
        $data['accounts'] = DB::table('accounts')
        ->orderBy('account')
        ->orderBy('description')
        ->get();

        $data['accDefaults'] = DB::table('acc_default')
        ->where('type','mataUang')
        ->orderBy('type')
        ->orderBy('code')
        ->get();

        return view("accountSetting.mataUang",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $type = $request->type;

        $list = DB::table('acc_default')
        ->where('type',$type)
        ->get();

        foreach($list as $val){
            $account = $request->input($val->code);
            $rowAffected = DB::table('acc_default')
                ->where('type',$type)
                ->where('code',$val->code)
                ->update([
                    'account' => $account,
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
        }

        $title ="Update $this->title";
        $alert  ="success";
        $message  = "$title Successfully updated";
        \LogActivity::addToLog($title,"username: $username Status $message");
        return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);

        // $pesan = '';
        
        // $messages = [
        //     'required' => 'The field is required.',
        //     'unique' => 'The code has already been taken',
        //     'iunique' => "The code $accNumber has already been taken",
        // ];
        
        // Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
        //     $query = DB::table($parameters[0]);
        //     $column = $query->getGrammar()->wrap($parameters[1]);
        //     return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        // });

        // $rule = [
        //     'accNumber'=>'required|iunique:supplier_banks,account_number',
        //     'bankName'=>'required',
        //     'branch'=>'required'
        // ];

        // $this->validate($request,$rule,$messages);

        // DB::beginTransaction();
        // try {
        //         DB::table('supplier_banks')->insert([
        //             'bank_type'=>$type,
        //             'bank_name'=>$name,
        //             'bank_branch'=>$branch,
        //             'account_number'=>$accNumber,
        //             'is_active'=>$isActive,
        //             'created_by' => Auth::user()->username,
        //             'updated_by' => Auth::user()->username,
        //             'created_at' => date('Y-m-d H:i:s'),
        //             'updated_at' => date('Y-m-d H:i:s')
        //         ]);

        //         DB::commit();
        //         $alert  ="alert-success";
        //         $message  = "$name is successfully saved";
        //         \LogActivity::addToLog('Bank save ',"username: $username Status $message");
        //         return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  

        // } catch (Exception $e) {
        //     DB::rollBack();
        //     $alert  ="alert-warning";
        //     $message  = "$name is failed to save";
        //     \LogActivity::addToLog('Bank save ',"username: $username Status $message");
        //     return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);   
        // }
        
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

}
