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

class CustomerController extends Controller
{   
    private $title;
    public function __construct()
    {
        $this->title = "Customer";
    }

    public function getTableColoumn(){
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=>false, 'searchable'=>false],
            ['data'=>'kode','name'=>'kode','title'=>'Kode'],
            ['data'=>'nama','name'=>'nama','title'=>'Nama'],
            ['data'=>'inisial','name'=>'inisial','title'=>'Inisial'],
            ['data'=>'nama_kontak','name'=>'nama_kontak','title'=>'Nama Kontak'],
            ['data'=>'telepon','name'=>'telepon','title'=>'Telepon'],
            ['data'=>'hp','name'=>'hp','title'=>'HP'],
            ['data'=>'fax','name'=>'fax','title'=>'Fax'],
            ['data'=>'alamat_tagih','name'=>'alamat_tagih','title'=>'Alamat tagih'],
            ['data'=>'alamat_kirim_1','name'=>'alamat_kirim_1','title'=>'Alamat Kirim 1'],
            ['data'=>'alamat_kirim_2','name'=>'alamat_kirim_2','title'=>'Alamat Kirim 2'],
            ['data'=>'npwp','name'=>'npwp','title'=>'NPWP'],
            ['data'=>'nppkp','name'=>'nppkp','title'=>'NPPKP'],
            ['data'=>'alamat_npwp','name'=>'alamat_npwp','title'=>'Alamat NPWP'],
            ['data'=>'blacklist','name'=>'blacklist','title'=>'Blacklist'],
            ['data'=>'epte','name'=>'epte','title'=>'EPTE'],
            ['data'=>'account','name'=>'account','title'=>'COA Piutang'],
            ['data'=>'coa_penjualan','name'=>'coa_penjualan','title'=>'COA Penjualan'],
            ['data'=>'top_batas_1','name'=>'top_batas_1','title'=>'TOP'],
        ];
        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $data['title'] = "$this->title";
        $data['kolom'] = $this->getTableColoumn();
        return view("customers.index",$data);
    }

    public function create(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create New $this->title";
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
        ->where('parent_id','1100.40')
        ->orderBy('description')
        ->get();

        $data['coaPenjualans'] = DB::table('accounts')
        ->where('parent_id','4000.10')
        ->orderBy('description')
        ->get();

        return view("customers.create",$data);
    }

    public function customerCodeCreate($initial){
        /*
            pembuatan article_alternative_code sesuai dengan aturan, kalo FG dan RM harus ada kode cabang nya
            apabila type nya FG atau RM makan akan terbentuk sekaligus 2 article
            kode customer
            INISIAL di bentuk oleh javascript
            MAJU PT = MAJXXXXXCUST
            MAJU JAYA PT = MJAXXXXXCUST
            MAJU JAYA ABADI PT = MJAXXXXXCUST
            MAJU JAYA SENTOSA CV = MJSXXXXXCUST
        */
         
        $lastCode = DB::table('third_party')
        ->where('kode','like',$initial.'%CUST')
        ->orderBy('kode','desc')
        ->value('kode');

        if (!$lastCode){
            $newCode = '00001';
        }else{
            $lastCode = substr($lastCode,3,5);
            $newCode = str_pad($lastCode+1, 5, "0", STR_PAD_LEFT);
        }

        $newCode = $initial.str_pad($newCode, 5, "0", STR_PAD_LEFT)."CUST";

        return  $newCode;
    
    }

    public function store(Request $request)
    {
        $username = Auth::user()->username;
        $epte = $request->epte ? true : false;
        $nama = strtoupper($request->nama);
        $inisial = strtoupper($request->inisial);
        $kode = $this->customerCodeCreate($inisial);
        $alamatTagih = $request->alamatTagih;
        $alamatKirim1 = $request->alamatKirim1;
        $alamatKirim2 = $request->alamatKirim2;
        $telepon = $request->telepon;
        $fax = $request->fax;
        $hp = $request->hp;
        $kontak = $request->kontak;
        $email = $request->email;
        $limitKredit = $request->limitKredit;
        $umurKredit = $request->umurKredit;
        $syaratBayar = $request->syaratBayar;
        $syaratKirim = $request->syaratKirim;
        $topBatas1 = $request->topBatas1;
        $topBatas2 = $request->topBatas2;
        $sales = $request->sales;
        $areaKirim = $request->areaKirim;
        $account = $request->account;
        $npwp = $request->npwp;
        $alamatNpwp = $request->alamatNpwp;
        $kotaNpwp = $request->kotaNpwp;
        $nppkp = $request->nppkp;
        $alamatEfaktur = $request->alamatEfaktur;
        $blok = $request->blok;
        $nomor = $request->nomor;
        $rt = $request->rt;
        $rw = $request->rw;
        $provinsi = $request->provinsi;
        $kota = $request->kota;
        $kelurahan = $request->kelurahan;
        $kecamatan = $request->kecamatan;
        $kodePos = $request->kodePos;
        $third_party_type='cust';
        $aktif = '1';
        $blacklist = '0';
        $pkp = 'N';
        $coaPenjualan = $request->coaPenjualan;
    
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken',
            // 'iunique' => "The code $kode has already been taken",
            // 'initunique' => "The intial $inisial has already been taken",
        ];
        
        // Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
        //     $query = DB::table($parameters[0]);
        //     $column = $query->getGrammar()->wrap($parameters[1]);
        //     return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        // });

        // Validator::extend('initunique', function ($attribute, $value, $parameters, $validator) {
        //     $query = DB::table($parameters[0]);
        //     $column = $query->getGrammar()->wrap($parameters[1]);
        //     return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        // });

        $rule = [
            // 'kode'=>'required|iunique:third_party,kode',
            'nama'=>'required',
            // 'inisial'=>'required|initunique:third_party,inisial'
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
                    'updated_at' => date('Y-m-d H:i:s'),
                    'coa_penjualan' => $coaPenjualan
                ]);

                DB::commit();
                $title = $this->title;
                $alert  ="success";
                $message  = "$title $kode is successfully saved";
                \LogActivity::addToLog('Supplier save ',"username: $username Status $message");
                return redirect()->back()->with(['alert'=>$alert,'title'=>$title,'message'=> $message]);  

        } catch (Exception $e) {
            DB::rollBack();
            $title = $this->title;
            $alert  ="warning";
            $message  = "$title $kode is failed to save";
            \LogActivity::addToLog('Supplier save ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'title'=>$title,'message'=> $message]);
        }
    }

    public function edit(Request $request)
    {
        $id=Crypt::decryptString($request->id);
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
        ->where('parent_id','1100.40')
        ->orderBy('description')
        ->get();

        $data['coaPenjualans'] = DB::table('accounts')
        ->where('parent_id','4000.10')
        ->orderBy('description')
        ->get();

        $data['customers'] = DB::table('third_party')
        ->where('id',$id)
        ->get()->first();

        $data['suppliers'] = DB::table('third_party')
        ->where('third_party_type','supp')
        ->get();

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
        $coaPenjualan = $request->coaPenjualan;
        $otherCode = $request->otherCode;
    
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken',
            // 'iunique' => "The code $kode has already been taken",
            // 'initunique' => "The intial $inisial has already been taken",
        ];
        
        // Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
        //     $query = DB::table($parameters[0]);
        //     $column = $query->getGrammar()->wrap($parameters[1]);
        //     return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        // });

        // Validator::extend('initunique', function ($attribute, $value, $parameters, $validator) {
        //     $query = DB::table($parameters[0]);
        //     $column = $query->getGrammar()->wrap($parameters[1]);
        //     return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        // });

        $rule = [
            'nama'=>'required',
            // 'inisial'=>'required|initunique:third_party,inisial'
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
                    'updated_at' => date('Y-m-d H:i:s'),
                    'coa_penjualan' => $coaPenjualan,
                    'other_code' => $otherCode
                    ]
                );

                DB::commit();

                if($row_affected>0){
                    $title = $this->title;
                    $alert  ="success";
                    $message  = "$title Successfully updated";
                    \LogActivity::addToLog('Supplier update ',"username: $username Status $message");
                    return redirect()->back()->with(['alert'=>$alert,'title'=>$title,'message'=> $message]);  
                }else{
                    $title = $this->title;
                    $alert  ="warning";
                    $message  = "$title Failed to update";
                    \LogActivity::addToLog('Supplier update ',"username: $username Status $message");
                    return redirect()->back()->with(['alert'=>$alert,'title'=>$title,'message'=> $message]);
                }

        } catch (Exception $e) {
            DB::rollBack();
            $title = $this->title;
            $alert  ="warning";
            $message  = "$title Failed to update";
            \LogActivity::addToLog('Supplier update ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'title'=>$title,'message'=> $message]);
        }

    }

    public function destroy(Request $request)
    {
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $row_affected = DB::table('third_party')
        ->where('id',$id)
        ->delete();

        if($row_affected>0){
            $title = $this->title;
            $alert  ="success";
            $message  = "$title successfully Deleted";
            \LogActivity::addToLog('Supplier delete ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'title'=>$title,'message'=> $message]);  
        }else{
            $title = $this->title;
            $alert  ="warning";
            $message  = "$title failed to Delete";
            \LogActivity::addToLog('Supplier delete ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'title'=>$title,'message'=> $message]);
        }
    }

    public function list(Request $request)
    {

        $code = strtolower($request->code);
        $name = strtolower($request->name);

        // ilike = string to lower
        $data=DB::table('third_party')
        ->where(function ($query) use ($code,$name) {
            $code ? $query->where('kode','ilike','%'.$code.'%'):"";
            $name ? $query->where('nama','ilike','%'.$name.'%'):""; 
        })
        ->where('third_party_type','cust')
        ->orderBy('nama')
        ->get();

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow " data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            if (Auth::user()->can('customer-edit')) {
            $buttons .=         '<a href="'. route('customer.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
            }
            if (Auth::user()->can('customer-delete')) {
                $buttons .=         "<a href='javascript:;'
                                        class='dropdown-item' 
                                        data-size='sm'
                                        data-ajax-delete='true'
                                        data-confirm='Are You Sure want to Delete?|This action can not be undone. Do you want to continue?' 
                                        data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                                        data-modal-id='".$data->id."'
                                        id='deleteButton'
                                        data-url='". route("customer.destroy", ['id'=>Crypt::encryptString($data->id)]) ."'>
                                        <i data-feather='trash-2' class='feather-14-red'></i>
                                        <span>". __('Delete') ."</span>
                                    </a>";
            }
            $buttons .=     '</div>
                        </div>';

            return $buttons;
            })
        ->addColumn('kode', function ($data) {
            return '<a href="'. route('customer.edit', ['id'=>Crypt::encryptString($data->id)]) .'" ><span>'.$data->kode.'</span></a>';
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
        ->rawColumns(['action','blacklist','epte','kode'])
        ->make(true);
    }
}
