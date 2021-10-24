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
    public function index(Request $request)
    {
        $id = $request->id;
        $data['title'] = "Company";
        $data['subtitle'] = "Comnpany";

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
            // 'iunique' => "The $kode has already been taken",
        ];
        
        // Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
        //     $query = DB::table($parameters[0]);
        //     $column = $query->getGrammar()->wrap($parameters[1]);
        //     return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        // });

        $rule = [
            // 'kode'=>'required|iunique:third_party,kode',
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
                $alert  ="alert-success";
                $message  = "$code is successfully saved";
                \LogActivity::addToLog('Company save ',"username: $username Status $message");
                return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  

        } catch (Exception $e) {
            DB::rollBack();
            $alert  ="alert-warning";
            $message  = "$code is failed to save";
            \LogActivity::addToLog('Company save ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);   
        }        
        
    }

}
