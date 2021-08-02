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
                            ->where ('index','=',2)
                            ->orderBy('region_name')
                            ->get();
        
        return view("suppliers.create",$data);
    }

    public function store(Request $request)
    {

        $kode =$request->input('kode');
        $nama =$request->input('nama');
        
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken',
            'iunique' => "The $kode has already been taken",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $rule = [
            'kode'=>'required|iunique:third_party,kode',
            'nama'=>'required'
        ];

        $this->validate($request,$rule,$messages);

        $alert  ="alert-warning";
        $pesan  = "Simpan data gagal.";

        $request->session()->flash($alert, $pesan);

        return view("customers.index");

        
        // return redirect()->back();  

        // $attr_name=$request['attr_name'];
        // $attr_code=$request['attr_code'];
        // $attr_desc=$request['attr_desc'];
        // $attr_value=str_replace( array( '.',','),'', $request['attr_value'])  ==''?0: round(str_replace( array( ','),'', $request['attr_value']));
        // $attr_status=$request['attr_status'] ==''?1:$request['attr_status'];

        // $messages = [
        //     'required' => 'The field is required.',
        //     'unique' => 'The code has already been taken'
        // ];

        // $rule = [
        //     'attr_code'=>['required',
        //                     Rule::unique('attributes')->where('attr_code',$attr_code)->where('attr_name',$attr_name)
        //                 ],
        //     'attr_desc'=>'required',
        //     'attr_name'=>'required'
        // ];

        // $this->validate($request, $rule,$messages);
        
        // DB::beginTransaction();
        // try {
        //         DB::table('attributes')->insert([
        //             'attr_name'=>$attr_name,
        //             'attr_code'=>$attr_code,
        //             'attr_desc'=>$attr_desc,
        //             'attr_value'=>$attr_value,
        //             'attr_status'=>$attr_status,
        //             'created_by' => Auth::user()->username,
        //             'updated_by' => Auth::user()->username,
        //             'created_at' => date('Y-m-d H:i:s'),
        //             'updated_at' => date('Y-m-d H:i:s')
        //         ]);

        //         DB::commit();
        //         $alert  ="alert-success";
        //         $pesan  = "Data sudah tersimpan, kode: $attr_code";
        //         $request->session()->flash($alert, $pesan);
        //         return redirect()->back();  

        // } catch (Exception $e) {
        //     DB::rollBack();
        //     $alert  ="alert-warning";
        //     $pesan  = "Simpan data gagal.";
        //     $request->session()->flash($alert, $pesan);
        //     return redirect()->back();  
        //     return redirect()->back()->with(['attr_code' =>$attr_code,'attr_desc' =>$attr_desc,'errornya' => $pesan]);
        // }

        // return redirect()->back();

        
    }

    public function edit(Request $request)
    {
        $attr_name=$request['attr_name'];
        $attr_code=$request['attr_code'];
        $attr_desc=$request['attr_desc'];

        $data = DB::table('attributes')
        ->where('attr_name',$attr_name)
        ->where('attr_code',$attr_code)
        ->get();

        return  Response()->json($data );
        
    }

    public function update(Request $request)
    {
        $attr_name=$request['attr_name'];    
        $attr_code=$request['attr_code'];
        $attr_desc=$request['attr_desc'];
        $attr_value=$request['attr_value'];
        $attr_status=$request['attr_status'];
        
        DB::beginTransaction();

        try {
                $row_affected=DB::table('attributes')
                ->where('attr_name',$attr_name)
                ->where('attr_code',$attr_code)
                ->update(
                    [
                        'attr_code'=>$attr_code,
                        'attr_desc'=>$attr_desc,
                        'attr_value'=>$attr_value,
                        'attr_status'=>$attr_status
                    ]
                );

                DB::commit();

                if($row_affected>0){
                    return response()->json(array('status' => 1, 'message' => 'Data sudah di update'));
                }else{
                    return response()->json(array('status' => 0, 'message' => 'Tidak ter-update :'.$attr_code));
                }

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(array('status' => 2, 'message' => 'Simpan data gagal'));
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
        $query = $request->get('q');
        // $user = User::where('name', 'LIKE', '%' . $query . '%');
            
        $sqlku="SELECT * from third_party where third_party_type = 'supp' and nama like '%$query%'";
        $data = DB::table(DB::raw("($sqlku) as oki"));
        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="more-vertical"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            if (Auth::user()->can('supplier-edit')) {
            $buttons .=         '<a href="'. route('users.edit', $data->id) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
            }
            if (Auth::user()->can('supplier-delete')) {
            $buttons .=         '<a href="javascript:;" onclick="validasidelete(\''.$data->id.'\')" class="dropdown-item">
                                    <i data-feather="trash-2"></i>
                                    Delete
                                </a>';
            }
            $buttons .=     '</div>
                        </div>';

            return $buttons;
            })
        ->addColumn('group_id', function ($user) {
            return '';
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
        ->rawColumns(['action','blacklist'])
        ->make(true);
    }
}
