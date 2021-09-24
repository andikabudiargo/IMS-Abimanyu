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

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $data['title'] = "Supplier";
        return view("suppliers.index",$data);
    }

    public function create(Request $request)
    {
        $data['title'] = "Create Supplier";
        $data['subtitle'] = "Create New Supplier";

        $data['cities'] = DB::table('regions')
        ->where ('index','=',1)
        ->orderBy('region_name')
        ->get();
                
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

    public function store(Request $request)
    {
        $username = Auth::user()->username;
        $nama = strtoupper($request->input('nama'));
        $inisial = strtoupper($request->input('inisial'));
        $kode = $this->supplierCodeCreate($inisial);
        $alamat = $request->input('alamat');
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
                    'alamat_tagih'=> $alamat,
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
                    'created_by' => Auth::user()->username,
                    'updated_by' => Auth::user()->username,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                DB::commit();
                $alert  ="alert-success";
                $message  = "$kode is successfully saved";
                \LogActivity::addToLog('Supplier save ',"username: $username Status $message");
                return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  

        } catch (Exception $e) {
            DB::rollBack();
            $alert  ="alert-warning";
            $message  = "$kode is failed to save";
            \LogActivity::addToLog('Supplier save ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);   
        }        
        

        
    }

    public function edit(Request $request)
    {
        $id = $request->id;
        $data['title'] = "Edit Supplier";
        $data['subtitle'] = "Edit New Supplier";

        $data['suppliers'] = DB::table('third_party')
        ->where('id',$id)
        ->get()->first();

        $data['cities'] = DB::table('regions')
        ->where ('index','=',1)
        ->orderBy('region_name')
        ->get();

        $data['edit'] = 1;

        return view('suppliers.edit',$data);
        
    }

    public function update(Request $request)
    {
        $username = Auth::user()->username;
        $id = $request->id;
        $kode = $request->input('kode');
        $nama = $request->input('nama');
        $alamat = $request->input('alamat');
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
            'kontak'=>'required',
        ];

        $this->validate($request,$rule,$messages);

        DB::beginTransaction();

        try {
                $row_affected=DB::table('third_party')
                    ->where('id',$id)
                    ->update(
                    [
                        'nama'=> $nama,
                        'alamat_tagih'=> $alamat,
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
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                DB::commit();

                if($row_affected>0){
                    $alert  ="alert-success";
                    $message  = "Successfully updated";
                    \LogActivity::addToLog('Supplier update ',"username: $username Status $message");
                    return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  
                }else{
                    $alert  ="alert-warning";
                    $message  = "Failed to update";
                    \LogActivity::addToLog('Supplier update ',"username: $username Status $message");
                    return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
                }

        } catch (Exception $e) {
            DB::rollBack();
            $alert  ="alert-warning";
            $message  = "Failed to update";
            \LogActivity::addToLog('Supplier update ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
        }
    }

    public function destroy(Request $request)
    {
        $username =  Auth::user()->username;
        $id = $request->id;

        $row_affected = DB::table('third_party')
        ->where('id',$id)
        ->delete();

        if($row_affected>0){
            $alert  ="alert-success";
            $message  = "Successfully Deleted";
            \LogActivity::addToLog('Supplier delete ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  
        }else{
            $alert  ="alert-warning";
            $message  = "Failed to Delete";
            \LogActivity::addToLog('Supplier delete ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
        }
    }

    public function list(Request $request)
    {
        $code = strtolower($request->code);
        $name = strtolower($request->name);

        //ilike = string to lower
        $data=DB::table('third_party');
        $data->where('third_party_type','supp');
        $code ? $data->where('kode','ilike','%'.$code.'%'):"";
        $name ? $data->where('nama','ilike','%'.$name.'%'):""; 
        $data->orderBy('nama')->get();

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            if (Auth::user()->can('supplier-edit')) {
            $buttons .=         '<a href="'. route('supplier.edit', ['id'=>$data->id]) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
            }
            if (Auth::user()->can('supplier-delete')) {
                $buttons .=         "<a href='javascript:;'
                                        id='deleteButton'
                                        class='dropdown-item'
                                        data-toggle='modal'
                                        data-target='#smallModal'
                                        data-href='". route("supplier.destroy", ["id"=>$data->id]) ."'>
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
