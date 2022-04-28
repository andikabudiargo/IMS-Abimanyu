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

class CompanyController extends Controller
{

    private $title;
    public function __construct()
    {
        $this->title = "Company";
    }

    public function index(Request $request)
    {
        $id = $request->id;
        $data['title'] = "$this->title";
        $data['subtitle'] = "$this->title";

        $data['provinces'] = DB::table('regions')
        ->where ('index','=',0)
        ->get();
        
        $data['companies'] = DB::table('company')
        ->get()->first();

        $data['cities'] = DB::table('regions')
        ->where ('index','=',1)
        ->orderBy('region_name')
        ->get();

        return view('company.index',$data);

    }

    public function store(Request $request)
    {
        $username = Auth::user()->username;
        $code = strtoupper($request->input('code'));
        $name = strtoupper($request->input('name'));
        $address = $request->input('address');
        $provinsi = $request->input('provinsi');
        $kota = $request->input('kota');
        $kelurahan = $request->input('kelurahan');
        $kecamatan = $request->input('kecamatan');
        $telepon = $request->input('telepon');
        $fax = $request->input('fax');
        $hp = $request->input('hp');
        $email = $request->input('email');
        $npwp = $request->input('npwp');
        $alamatNpwp = $request->input('alamatNpwp');
        $kotaNpwp = $request->input('kotaNpwp');

        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken',
            'iunique' => "The $code has already been taken",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $rule = [
            'code'=>'required',
            'name'=>'required',
            'address'=>'required'
        ];

        $this->validate($request,$rule,$messages);

        DB::beginTransaction();
        try {
            $row_affected= DB::table('company')
            ->updateOrInsert(
                ['code' => $code],
                [
                    'code' => $code,
                    'name' => $name,
                    'address' => $address ,
                    'province' => $provinsi,
                    'city' => $kota,
                    'village' => $kelurahan,
                    'district' => $kecamatan ,
                    'tlp' => $telepon,
                    'fax' => $fax,
                    'hp' => $hp,
                    'email' => $email ,
                    'npwp' => $npwp,
                    'tax_address' => $alamatNpwp,
                    // 'tax_province' => ,
                    'tax_city' => $kotaNpwp,
                    // 'tax_village' => ,
                    // 'tax_district' => ,
                    'updated_by' => Auth::user()->username,
                    'created_at' => date('Y-m-d H:i:s'),
                ]
            );

            DB::commit();

            $title ="Save $this->title";
            $alert  ="success";
            $message  = "$this->title $code is successfully saved";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert]); 

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Save $this->title";
            $alert  ="warning";
            $message  = "$this->title $code is failed to save";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert]);
        }        
        
    }

}
