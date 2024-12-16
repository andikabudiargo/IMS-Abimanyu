<?php

namespace App\Http\Controllers\Conversion;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Conversion;
use Response;
use App\Permission;
use DataTables;
use DB;

class ConversionSettingController extends Controller
{

    private $title;
    public function __construct()
    {
        $this->title = "Conversion Setting";
    }

    public function index(Request $request)
    {
        $id = $request->id;
        $data['title'] = "$this->title";
        $data['subtitle'] = "$this->title";
        $data['conversion'] = Conversion::where('status','1')->get()->first();
        
        return view('conversion.conversionSetting.index',$data);

    }

    public function store(Request $request)
    {
        $username = Auth::user()->username;
        
        $cVal = $request->cVal;
                        
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken',
            'iunique' => "The has already been taken",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $rule = [
            'cVal'=>'required',
        ];

        $this->validate($request,$rule,$messages);

        DB::beginTransaction();
        try {

            
            if($cVal){
                DB::table('conversion_setting')
                ->where('status','1')
                ->update([
                    'status' => '0',
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                DB::table('conversion_setting')
                ->insert(
                    [
                        'conversion_value' => preg_replace('/[^0-9.]+/', '', $cVal),
                        'status' => '1',
                        'created_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]
                );
            }

            DB::commit();

            $title ="Save $this->title";
            $alert  ="success";
            $message  = "$this->title New value : $cVal ,is successfully saved";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert]); 

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Save $this->title";
            $alert  ="warning";
            $message  = "$this->title is failed to save";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert]);
        }        
        
    }

}
