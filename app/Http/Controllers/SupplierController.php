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

class SupplierController extends Controller
{
    private $title;
    public function __construct()
    {
        $this->title = "Supplier";
    }

    public function index(Request $request)
    {
        $data['title'] = "$this->title";
        return view("suppliers.index",$data);
    }

    public function create(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create New $this->title";

        $data['provinces'] = DB::table('regions')
        ->where ('index','=',0)
        ->get();

        $data['accounts'] = DB::table('accounts')
        ->get();

        $data['banks'] = DB::table('banks')
        ->orderBy('bank_name')
        ->get();

        $data['cities'] = DB::table('regions')
        ->where ('index','=',1)
        ->orderBy('region_name')
        ->get();

        $data['suppliers']= DB::table('third_party') 
        ->where('third_party_type','supp')
        ->orderBy('nama')
        ->distinct('nama')
        ->pluck('nama');
                
        return view("suppliers.create",$data);
    }

    public function supplierCodeCreate($initial){
        /*
            pembuatan article_alternative_code sesuai dengan aturan, kalo FG dan RM harus ada kode cabang nya
            apabila type nya FG atau RM makan akan terbentuk sekaligus 2 article
            kode customer
            INISIAL di bentuk oleh javascript
            MAJU PT = MAJXXXXXSUPP
            MAJU JAYA PT = MJAXXXXXSUPP
            MAJU JAYA ABADI PT = MJAXXXXXSUPP
            MAJU JAYA SENTOSA CV = MJSXXXXXSUPP
        */
         
        $lastCode = DB::table('third_party')
        ->where('kode','like',$initial.'%SUPP')
        ->value('kode');

        if (!$lastCode){
            $newCode = '00001';
        }else{
            $lastCode = substr($lastCode,3,5);
            $newCode = str_pad($lastCode+1, 5, "0", STR_PAD_LEFT);
        }

        $newCode = $initial.str_pad($newCode, 5, "0", STR_PAD_LEFT)."SUPP";

        return  $newCode;
    
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
        $nama = strtoupper($request->nama);
        $inisial = strtoupper($request->inisial);
        $kode = $this->supplierCodeCreate($inisial);
        $kodeCust = $this->customerCodeCreate($inisial);
        $alamat = $request->alamat;
        $provinsi = $request->provinsi;
        $kota = $request->kota;
        $kelurahan = $request->kelurahan;
        $kecamatan = $request->kecamatan;
        $kodePos = $request->kodePos;
        $telepon = $request->telepon;
        $fax = $request->fax;
        $hp = $request->hp;
        $kontak = $request->kontak;
        $email = $request->email;
        $termin = $request->termin;
        $npwp = $request->npwp;
        $alamatNpwp = $request->alamatNpwp;
        $kotaNpwp = $request->kotaNpwp;
        $bankType = $request->bankType;
        $bankName = $request->bankName;
        $accNumber = $request->accNumber;
        $branch = $request->branch;
        $asCustomer = $request->asCustomer;
        // $bankBca = $request->bankBca ? 'yes' : 'no';
        $third_party_type='supp';
        $aktif = '1';
        $blacklist = '0';
        $pkp = 'N';
        
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
            'nama'=>'required'
        ];

        $this->validate($request,$rule,$messages);

        DB::beginTransaction();
        try {
                DB::table('third_party')->insert([
                    'kode'=> $kode,
                    'nama'=> $nama,
                    'inisial'=> $inisial,
                    'alamat_tagih'=> $alamat,
                    'provinsi'=> $provinsi,
                    'kota'=>  $kota,
                    'kelurahan'=> $kelurahan,
                    'kecamatan'=> $kecamatan,                   
                    'kode_pos'=> $kodePos,
                    'pkp'=> $pkp,
                    'telepon'=> $telepon,
                    'hp'=> $hp,
                    'fax'=> $fax,
                    'email'=> $email,
                    'nama_kontak'=> $kontak,
                    'top_batas_1'=> $termin,
                    'aktif'=> $aktif,
                    'blacklist'=> $blacklist,
                    'third_party_type'=> $third_party_type,
                    'npwp'=> $npwp,
                    'alamat_npwp'=> $alamatNpwp,
                    'kota_npwp'=> $kotaNpwp,
                    // 'bank_bca' => $bankBca,
                    'bank_type' => $bankType,
                    'bank_name' => $bankName,
                    'account_number' => $accNumber,
                    'bank_branch' => $branch,
                    'created_by' => Auth::user()->username,
                    'updated_by' => Auth::user()->username,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                if ($asCustomer){
                    DB::table('third_party')->insert([
                        'kode'=> $kodeCust,
                        'nama'=> $nama,
                        'inisial'=> $inisial,
                        'alamat_tagih'=> $alamat,
                        'provinsi'=> $provinsi,
                        'kota'=>  $kota,
                        'kelurahan'=> $kelurahan,
                        'kecamatan'=> $kecamatan,                   
                        'kode_pos'=> $kodePos,
                        'pkp'=> $pkp,
                        'telepon'=> $telepon,
                        'hp'=> $hp,
                        'fax'=> $fax,
                        'email'=> $email,
                        'nama_kontak'=> $kontak,
                        'top_batas_1'=> $termin,
                        'aktif'=> $aktif,
                        'blacklist'=> $blacklist,
                        'third_party_type'=> 'cust',
                        'npwp'=> $npwp,
                        'alamat_npwp'=> $alamatNpwp,
                        'kota_npwp'=> $kotaNpwp,
                        // 'bank_bca' => $bankBca,
                        'bank_type' => $bankType,
                        'bank_name' => $bankName,
                        'account_number' => $accNumber,
                        'bank_branch' => $branch,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]); 
                }

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
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit New $this->title";

        $data['suppliers'] = DB::table('third_party')
        ->where('id',$id)
        ->get()->first();

        $data['provinces'] = DB::table('regions')
        ->where ('index','=',0)
        ->get();

        $data['accounts'] = DB::table('accounts')
        ->get();

        $data['banks'] = DB::table('banks')
        ->orderBy('bank_name')
        ->get();

        $data['cities'] = DB::table('regions')
        ->where ('index','=',1)
        ->orderBy('region_name')
        ->get();

        $data['edit'] = 1;

        return view('suppliers.edit',$data);
        
    }

    public function show(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";

        $data['suppliers'] = DB::table('third_party')
        ->where('id',$id)
        ->get()->first();

        $data['provinces'] = DB::table('regions')
        ->where ('index','=',0)
        ->get();

        $data['accounts'] = DB::table('accounts')
        ->get();

        $data['banks'] = DB::table('banks')
        ->orderBy('bank_name')
        ->get();

        $data['cities'] = DB::table('regions')
        ->where ('index','=',1)
        ->orderBy('region_name')
        ->get();

        return view('suppliers.show',$data);
        
    }

    public function update(Request $request)
    {
        $username = Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $kode = $request->input('kode');
        $inisial = strtoupper($request->input('inisial'));
        $nama = $request->input('nama');
        $alamat = $request->input('alamat');
        $provinsi = $request->input('provinsi');
        $kota = $request->input('kota');
        $kelurahan = $request->input('kelurahan');
        $kecamatan = $request->input('kecamatan');
        $kodePos = $request->input('kodePos');
        $telepon = $request->input('telepon');
        $fax = $request->input('fax');
        $hp = $request->input('hp');
        $kontak = $request->input('kontak');
        $email = $request->input('email');
        $termin = $request->input('termin');
        $npwp = $request->input('npwp');
        $alamatNpwp = $request->input('alamatNpwp');
        $kotaNpwp = $request->input('kotaNpwp');
        $third_party_type='supp';
        $bankType = $request->input('bankType');
        $bankName = $request->input('bankName');
        $accNumber = $request->input('accNumber');
        $branch = $request->input('branch');
        // $bankBca = $request->input('bankBca') ? 'yes' : 'no';
        $aktif = '1';
        $blacklist = '0';
        $pkp = 'N';
    
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken',
            'iunique' => "The code $kode has already been taken",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $rule = [
            'nama'=>'required',
            // 'kontak'=>'required',
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
                        'alamat_tagih'=> $alamat,
                        'provinsi'=> $provinsi,
                        'kota'=>  $kota,
                        'kelurahan'=> $kelurahan,
                        'kecamatan'=> $kecamatan,                   
                        'kode_pos'=> $kodePos,
                        'pkp'=> $pkp,
                        'telepon'=> $telepon,
                        'hp'=> $hp,
                        'fax'=> $fax,
                        'email'=> $email,
                        'nama_kontak'=> $kontak,
                        'top_batas_1'=> $termin,
                        'aktif'=> $aktif,
                        'blacklist'=> $blacklist,
                        'third_party_type'=> $third_party_type,
                        'npwp'=> $npwp,
                        'alamat_npwp'=> $alamatNpwp,
                        'kota_npwp'=> $kotaNpwp,
                        // 'bank_bca' => $bankBca,
                        'bank_type' => $bankType,
                        'bank_name' => $bankName,
                        'account_number' => $accNumber,
                        'bank_branch' => $branch,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
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

        //ilike = string to lower
        $data=DB::table('third_party')
        ->where(function ($query) use ($code,$name) {
            $code ? $query->where('kode','ilike','%'.$code.'%'):"";
            $name ? $query->where('nama','ilike','%'.$name.'%'):""; 
        })
        ->where('third_party_type','supp')
        ->orderBy('nama')
        ->get();

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            if (Auth::user()->can('supplier-edit')) {
            $buttons .=         '<a href="'. route('supplier.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
            }
            $buttons .=         '<a href="'. route('supplier.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    Detail
                                </a>';
            if (Auth::user()->can('supplier-delete')) {
                $buttons .=         "<a href='javascript:;'
                                        id='deleteButton'
                                        class='dropdown-item'
                                        data-toggle='modal'
                                        data-target='#smallModal'
                                        data-href='". route("supplier.destroy", ['id'=>Crypt::encryptString($data->id)]) ."'>
                                        <i data-feather='trash-2' class='feather-14-red'></i>
                                        Delete
                                    </a>";
            }
            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        ->addColumn('kode', function ($data) {
            return '<a href="'. route('supplier.show', ['id'=>Crypt::encryptString($data->id)]) .'" ><span>'.$data->kode.'</span></a>';
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
                $epte = '<span class="badge badge-pill badge-light-primary">Yes</span>';
            }else{
                $epte = '<span class="badge badge-pill badge-light-danger">No</span>';
            }
            return $epte;
        })
        ->rawColumns(['action','blacklist','epte','kode'])
        ->make(true);
    }
}
