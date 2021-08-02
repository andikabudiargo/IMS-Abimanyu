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

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $data['title'] = "Customer";
        return view("customers.index",$data);
    }

    public function create(Request $request)
    {
        $data['title'] = "Create Customer";
        $data['subtitle'] = "Create New Customer";
        $data['provinces'] = DB::table('regions')
        ->where ('index','=',0)
        ->get();
        
        $data['cities'] = DB::table('regions')
        ->where ('index','=',1)
        ->orderBy('region_name')
        ->get();
        
        $data['employees'] = DB::table('employees')
        ->where ('job_position','=','05')
        ->orderBy('name')
        ->get();

        $data['accounts'] = DB::table('accounts')
        ->where ('status','=','1')
        ->orderBy('description')
        ->get();

        return view("customers.create",$data);
    }

    public function store(Request $request)
    {
        $username = Auth::user()->username;
        $kode = $request->input('kode');
        $epte = $request->input('epte') ? true : false;
        $nama = $request->input('nama');
        $inisial = strtoupper($request->input('inisial'));
        $alamatTagih = $request->input('alamatTagih');
        $alamatKirim1 = $request->input('alamatKirim1');
        $alamatKirim2 = $request->input('alamatKirim2');
        $telepon = $request->input('telepon');
        $fax = $request->input('fax');
        $hp = $request->input('hp');
        $kontak = $request->input('kontak');
        $email = $request->input('email');
        $limitKredit = $request->input('limitKredit');
        $umurKredit = $request->input('umurKredit');
        $syaratBayar = $request->input('syaratBayar');
        $syaratKirim = $request->input('syaratKirim');
        $topBatas1 = $request->input('topBatas1');
        $topBatas2 = $request->input('topBatas2');
        $sales = $request->input('sales');
        $areaKirim = $request->input('areaKirim');
        $account = $request->input('account');
        $npwp = $request->input('npwp');
        $alamatNpwp = $request->input('alamatNpwp');
        $kotaNpwp = $request->input('kotaNpwp');
        $nppkp = $request->input('nppkp');
        $alamatEfaktur = $request->input('alamatEfaktur');
        $blok = $request->input('blok');
        $nomor = $request->input('nomor');
        $rt = $request->input('rt');
        $rw = $request->input('rw');
        $provinsi = $request->input('provinsi');
        $kota = $request->input('kota');
        $kelurahan = $request->input('kelurahan');
        $kecamatan = $request->input('kecamatan');
        $kodePos = $request->input('kodePos');
        $third_party_type='cust';
        $aktif = '1';
        $blacklist = '0';
        $pkp = 'N';
    
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken',
            'iunique' => "The code $kode has already been taken",
            'initunique' => "The intial $inisial has already been taken",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        Validator::extend('initunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $rule = [
            'kode'=>'required|iunique:third_party,kode',
            'nama'=>'required',
            'inisial'=>'required|initunique:third_party,inisial'
        ];

        $this->validate($request,$rule,$messages);

        DB::beginTransaction();
        try {
                DB::table('third_party')->insert([
                    'kode'=> $kode,
                    'nama'=> $nama,
                    'inisial'=> $inisial,
                    'alamat_tagih'=> $alamatTagih,
                    'alamat_kirim_1'=> $alamatKirim1,
                    'alamat_kirim_2'=> $alamatKirim2,
                    'npwp'=> $npwp,
                    'alamat_npwp'=> $alamatNpwp,
                    'nppkp'=> $nppkp,
                    'kota_npwp'=> $kotaNpwp,
                    'provinsi_npwp'=> $provinsi,
                    'sales'=> $sales,
                    'area_kirim'=> $areaKirim,
                    'efaktur_alamat'=> $alamatEfaktur,
                    'efaktur_blok'=> $blok,
                    'efaktur_no'=> $nomor,
                    'efaktur_rt'=> $rt,
                    'efaktur_rw'=> $rw,
                    'efaktur_kelurahan'=> $kelurahan,
                    'efaktur_kecamatan'=> $kecamatan,
                    'efaktur_kota'=>  $kota,
                    'efaktur_provinsi'=> $provinsi,
                    'efaktur_kode_pos'=> $kodePos,
                    'pkp'=> $pkp,
                    'telepon'=> $telepon,
                    'hp'=> $hp,
                    'fax'=> $fax,
                    'email'=> $email,
                    'nama_kontak'=> $kontak,
                    'limit_kredit'=> $limitKredit,
                    'umur_kredit' => $umurKredit,
                    'syarat_bayar'=> $syaratBayar,
                    'syarat_kirim'=> $syaratKirim,
                    'account'=> $account,
                    'top_batas_1'=> $topBatas1,
                    'top_batas_2'=> $topBatas2,
                    'aktif'=> $aktif,
                    'blacklist'=> $blacklist,
                    'epte'=>$epte,
                    'third_party_type'=> $third_party_type,
                    'created_by' => Auth::user()->username,
                    'updated_by' => Auth::user()->username,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                DB::commit();
                $alert  ="alert-success";
                $message  = "$kode is successfully saved";
                \LogActivity::addToLog('Customer save ',"username: $username Status $message");
                return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  

        } catch (Exception $e) {
            DB::rollBack();
            $alert  ="alert-warning";
            $message  = "$kode is failed to save";
            \LogActivity::addToLog('Customer save ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);   
        }

    }

    public function edit(Request $request)
    {
        
        $id=$request->id;
        $data['title'] = "Edit Customer";
        $data['subtitle'] = "Edit Customer";
        
        $data['provinces'] = DB::table('regions')
        ->where ('index','=',0)
        ->get();
        
        $data['cities'] = DB::table('regions')
        ->where ('index','=',1)
        ->orderBy('region_name')
        ->get();
        
        $data['employees'] = DB::table('employees')
        ->where ('job_position','=','05')
        ->orderBy('name')
        ->get();

        $data['accounts'] = DB::table('accounts')
        ->where ('status','=','1')
        ->orderBy('description')
        ->get();

        $data['customers'] = DB::table('third_party')
        ->where('id',$id)
        ->get()->first();

        $data['edit'] = 1;

        return view('customers.edit',$data);
        
    }

    public function update(Request $request)
    {
        $username = Auth::user()->username;
        $id = $request->id;
        $kode = $request->input('kode');
        $epte = $request->input('epte') ? true : false;
        $nama = $request->input('nama');
        $inisial = strtoupper($request->input('inisial'));
        $alamatTagih = $request->input('alamatTagih');
        $alamatKirim1 = $request->input('alamatKirim1');
        $alamatKirim2 = $request->input('alamatKirim2');
        $telepon = $request->input('telepon');
        $fax = $request->input('fax');
        $hp = $request->input('hp');
        $kontak = $request->input('kontak');
        $email = $request->input('email');
        $limitKredit = $request->input('limitKredit');
        $umurKredit = $request->input('umurKredit');
        $syaratBayar = $request->input('syaratBayar');
        $syaratKirim = $request->input('syaratKirim');
        $topBatas1 = $request->input('topBatas1');
        $topBatas2 = $request->input('topBatas2');
        $sales = $request->input('sales');
        $areaKirim = $request->input('areaKirim');
        $account = $request->input('account');
        $npwp = $request->input('npwp');
        $alamatNpwp = $request->input('alamatNpwp');
        $kotaNpwp = $request->input('kotaNpwp');
        $nppkp = $request->input('nppkp');
        $alamatEfaktur = $request->input('alamatEfaktur');
        $blok = $request->input('blok');
        $nomor = $request->input('nomor');
        $rt = $request->input('rt');
        $rw = $request->input('rw');
        $provinsi = $request->input('provinsi');
        $kota = $request->input('kota');
        $kelurahan = $request->input('kelurahan');
        $kecamatan = $request->input('kecamatan');
        $kodePos = $request->input('kodePos');
        $third_party_type='cust';
        $aktif = '1';
        $blacklist = '0';
        $pkp = 'N';
    
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken',
            'iunique' => "The code $kode has already been taken",
            'initunique' => "The intial $inisial has already been taken",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        Validator::extend('initunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $rule = [
            'nama'=>'required',
            'inisial'=>'required|initunique:third_party,inisial'
        ];

        $this->validate($request,$rule,$messages);

        DB::beginTransaction();

        try {
                $row_affected=DB::table('third_party')
                ->where('id',$id)
                ->update(
                    [
                    'nama'=> $nama,
                    'inisial'=> $inisial,
                    'alamat_tagih'=> $alamatTagih,
                    'alamat_kirim_1'=> $alamatKirim1,
                    'alamat_kirim_2'=> $alamatKirim2,
                    'npwp'=> $npwp,
                    'alamat_npwp'=> $alamatNpwp,
                    'nppkp'=> $nppkp,
                    'kota_npwp'=> $kotaNpwp,
                    'provinsi_npwp'=> $provinsi,
                    'sales'=> $sales,
                    'area_kirim'=> $areaKirim,
                    'efaktur_alamat'=> $alamatEfaktur,
                    'efaktur_blok'=> $blok,
                    'efaktur_no'=> $nomor,
                    'efaktur_rt'=> $rt,
                    'efaktur_rw'=> $rw,
                    'efaktur_kelurahan'=> $kelurahan,
                    'efaktur_kecamatan'=> $kecamatan,
                    'efaktur_kota'=>  $kota,
                    'efaktur_provinsi'=> $provinsi,
                    'efaktur_kode_pos'=> $kodePos,
                    'pkp'=> $pkp,
                    'telepon'=> $telepon,
                    'hp'=> $hp,
                    'fax'=> $fax,
                    'email'=> $email,
                    'nama_kontak'=> $kontak,
                    'limit_kredit'=> $limitKredit,
                    'umur_kredit' => $umurKredit,
                    'syarat_bayar'=> $syaratBayar,
                    'syarat_kirim'=> $syaratKirim,
                    'account'=> $account,
                    'top_batas_1'=> $topBatas1,
                    'top_batas_2'=> $topBatas2,
                    'aktif'=> $aktif,
                    'blacklist'=> $blacklist,
                    'epte'=>$epte,
                    'third_party_type'=> $third_party_type,
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                DB::commit();

                if($row_affected>0){
                    $alert  ="alert-success";
                    $message  = "Successfully updated";
                    \LogActivity::addToLog('Customer update ',"username: $username Status $message");
                    return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  
                }else{
                    $alert  ="alert-warning";
                    $message  = "Failed to update";
                    \LogActivity::addToLog('Customer update ',"username: $username Status $message");
                    return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
                }

        } catch (Exception $e) {
            DB::rollBack();
            $alert  ="alert-warning";
            $message  = "Failed to update";
            \LogActivity::addToLog('Customer update ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
        }

    }

    public function delete(Request $request)
    {
        $attr_name=$request['attr_name'];
        $attr_code=$request['attr_code'];
        DB::table('attributes')
        ->where('attr_name',$attr_name)
        ->where('attr_code',$attr_code)
        ->delete();
        return response()->json(['success'=>"Data sudah di hapus"]);
    }

    public function list(Request $request)
    {

        $code = strtolower($request->code);
        $name = strtolower($request->name);

        $data=DB::table('third_party')
        ->where('third_party_type','cust')
        ->where('kode','ilike','%'.$code.'%')
        ->where('nama','ilike','%'.$name.'%')  // string to lower
        ->orderBy('nama')->get();

        // $query = $request->get('q');
        // $sqlku="SELECT * from third_party where third_party_type = 'cust' and nama like '%$query%'";
        // $data = DB::table(DB::raw("($sqlku) as oki"));

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="more-vertical"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            if (Auth::user()->can('customer-edit')) {
            $buttons .=         '<a href="'. route('customer.edit', ['id'=>$data->id]) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
            }
            if (Auth::user()->can('customer-delete')) {
                $buttons .=         "<a href='javascript:;'
                                        id='deleteButton'
                                        class='dropdown-item'
                                        data-toggle='modal'
                                        data-target='#smallModal'
                                        data-href='". route("customer.destroy", ["id"=>$data->id]) ."'>
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
        ->addColumn('blacklist', function ($data) {
            if ($data->blacklist =='1') {
                $blacklist = '<div class="custom-control custom-switch custom-control-inline">
                                <input type="checkbox" class="custom-control-input blackList" id="blackList_'.$data->id.'" data-nama="'.$data->id.'" checked/>
                                <label id="lblBlackList_'.$data->id.'" class="custom-control-label" for="blackList_'.$data->id.'">Active</label>
                            </div>';
            } else {
                $blacklist = '<div class="custom-control custom-switch custom-control-inline">
                                <input type="checkbox" class="custom-control-input blackList" id="blackList_'.$data->id.'" data-nama="'.$data->id.'"/>
                                <label id="lblBlackList_'.$data->id.'" class="custom-control-label" for="blackList_'.$data->id.'">Locked</label>
                            </div>';
            }
            return $blacklist;
        })
        ->addColumn('epte', function ($data) {
            if ($data->epte == true){
                $weightStatus = '<span class="badge badge-pill badge-light-primary">Yes</span>';
            }else{
                $weightStatus = '<span class="badge badge-pill badge-light-danger">No</span>';
            }
            return $weightStatus;
        })
        ->rawColumns(['action','blacklist','epte'])
        ->make(true);
    }
}
