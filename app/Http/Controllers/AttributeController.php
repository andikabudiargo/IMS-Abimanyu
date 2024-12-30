<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Attribute;
use Response;
use App\Permission;
use DataTables;
use DB;

class AttributeController extends Controller
{

    private $title;
    public function __construct()
    {
        $this->title = "System Setting";
    }

    public function index(Request $request)
    {
        $id = $request->id;
        $data['title'] = "$this->title";
        $data['subtitle'] = "$this->title";

        $data['attribute'] = Attribute::where('attr_name','main')->pluck('attr_value','attr_code');

        // dd($data['attribute']);
        return view('attribute.index',$data);

    }

    public function store(Request $request)
    {
        $username = Auth::user()->username;
        
        $ppn = $request->ppn;
        $pph23 = $request->pph23;
        $decimalPLaces = $request->decimalPLaces;
        $dataSet = [];        
        $dataSet[] = [
            'attr_id' => 'mainppn',
            'attr_value' => $ppn
        ];

        $dataSet[] = [
            'attr_id' => 'mainpph23',
            'attr_value' => $pph23
        ];

        $dataSet[] = [
            'attr_id' => 'maindecimalPlaces',
            'attr_value' => $decimalPLaces
        ];
                
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
            'ppn'=>'required',
            'pph23'=>'required',
            // 'decimalPlaces'=>'required'
        ];

        $this->validate($request,$rule,$messages);

        // dd($data);

        DB::beginTransaction();
        try {

            foreach($dataSet as $val){
                DB::table('attributes')
                ->updateOrInsert(
                    ['attr_id' => $val['attr_id']],
                    [
                        'attr_value' => $val['attr_value'],
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]
                );
            }

            DB::commit();

            $title ="Save $this->title";
            $alert  ="success";
            $message  = "$this->title is successfully saved";
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

    public static function getLastPpn($tanggal){
        /* Format tanggal harus dd-mm-yyyy*/

        $ppnDate = implode("-",array_reverse(explode("-", trim($tanggal))));
        $ppnValue = 11; //default kalau benar2 kosong

        $ppnValue = db::table('master_ppn')
        ->where('ppn_start_date',"<=",$ppnDate)
        ->where('ppn_end_date',">=",$ppnDate)
        ->orderBy('ppn_start_date','desc')
        ->value('ppn_value');

        if(!$ppnValue){
            $ppnValue = db::table('master_ppn')
            ->where('ppn_end_date',"<=",$ppnDate)
            ->orderBy('ppn_start_date','desc')
            ->value('ppn_value');
        }

        /*kalau database kosong ini yang dikeluarin*/
        if(!$ppnValue){
            $ppnValue = 11;
        }

        return $ppnValue;
    }

    public function getLastPpn1(Request $request){
        /* Format tanggal harus dd-mm-yyyy*/

        $tanggal = $request->ppnDate;
        $ppnDate = implode("-",array_reverse(explode("-", trim($tanggal))));
        $ppnValue = 11; //default kalau benar2 kosong

        $ppnValue = db::table('master_ppn')
        ->where('ppn_start_date',"<=",$ppnDate)
        ->where('ppn_end_date',">=",$ppnDate)
        ->orderBy('ppn_start_date','desc')
        ->value('ppn_value');

        if(!$ppnValue){
            $ppnValue = db::table('master_ppn')
            ->where('ppn_end_date',"<=",$ppnDate)
            ->orderBy('ppn_start_date','desc')
            ->value('ppn_value');
        }

        /*kalau database kosong ini yang dikeluarin*/
        if(!$ppnValue){
            $ppnValue = 11;
        }

        return $ppnValue;
    }

}
