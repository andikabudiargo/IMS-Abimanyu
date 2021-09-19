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

class UomConController extends Controller
{
    public function index(Request $request)
    {
        $data['title'] = "UOM Coversion";
        return view("uomCon.index",$data);
    }

    public function create(Request $request)
    {
        $data['title'] = "Create UOM Conversion";
        $data['subtitle'] = "Create UOM New Conversion";
        $data['uoms'] = DB::table('uom')->orderBy('name')->get();
        return view("uomCon.create",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $unitFrom = $request->input('unitFrom');
        $unitTo = $request->input('unitTo');
        $unitFactor = $request->input('unitFactor');
        $pesan = '';
        
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken',
        ];
    
        $rule = [
            'unitFrom'=>'required',
            'unitTo'=>'required',
            'unitFactor'=>'required'
        ];

        $this->validate($request,$rule,$messages);

        DB::beginTransaction();
        try {
                DB::table('uom_con')
                ->updateOrInsert(
                    [
                        'unit_from' => $unitFrom,
                        'unit_to' => $unitTo
                    ],
                    [
                        'unit_factor' => $unitFactor,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                DB::commit();
                $alert  ="alert-success";
                $message  = "Data is successfully saved";
                \LogActivity::addToLog('Uom save ',"username: $username Unit From : $unitFrom Unit To : $unitTo");
                return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  

        } catch (Exception $e) {
            DB::rollBack();
            $alert  ="alert-warning";
            $message  = "Data is failed to save";
            \LogActivity::addToLog('Dept save ',"username: $username Unit From : $unitFrom Unit To : $unitTo");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);   
        }
    }

    public function edit(Request $request)
    {

        $id=$request->id;
        $data['title'] = "Edit UOM";
        $data['subtitle'] = "Edit UOM";
        $data['uom'] = DB::table('uom')

        ->where('id',$id)
        ->get()->first();

        return view('uom.edit',$data);
        
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $id = $request->id;
        $kode = strtoupper($request->input('kode'));
        $nama = $request->input('nama');
        $weight = $request->input('weight') ? true : false;
        $status = '1';
        $pesan = '';
        
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken',
            'iunique' => "The code $kode has already been taken",
        ];
        

        $rule = [
            'nama'=>'required'
        ];

        $this->validate($request,$rule,$messages);
        
        DB::beginTransaction();

        try {
                $row_affected=DB::table('uom')
                ->where('id',$id)
                ->update(
                    [
                        'name'=>$nama,
                        'weight'=>$weight,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                DB::commit();

                if($row_affected>0){
                    $alert  ="alert-success";
                    $message  = "Successfully updated";
                    \LogActivity::addToLog('Uom update ',"username: $username Status $message");
                    return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  
                }else{
                    $alert  ="alert-warning";
                    $message  = "Failed to update";
                    \LogActivity::addToLog('Uom update ',"username: $username Status $message");
                    return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
                }

        } catch (Exception $e) {
            DB::rollBack();
            $alert  ="alert-warning";
            $message  = "Failed to update";
            \LogActivity::addToLog('Uom update ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
        }
    }

    public function destroy(Request $request)
    {

        $username =  Auth::user()->username;
        $id = $request->id;

        $row_affected = DB::table('uom')
        ->where('id',$id)
        ->delete();

        if($row_affected>0){
            $alert  ="alert-success";
            $message  = "Successfully Deleted";
            \LogActivity::addToLog('Uom delete ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  
        }else{
            $alert  ="alert-warning";
            $message  = "Failed to Delete";
            \LogActivity::addToLog('Uom delete ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
        }

    }

    public function list(Request $request)
    {
        $code = strtolower($request->code);
        $name = strtolower($request->name);

        $data=DB::table('uom')
        ->where('code','ilike','%'.$code.'%')
        ->where('name','ilike','%'.$name.'%')  // string to lower
        ->orderBy('name')->get();

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="more-vertical"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            if (Auth::user()->can('department-edit')) {
            $buttons .=         '<a href="'. route('uom.edit', ['id'=>$data->id]) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
            }
            if (Auth::user()->can('department-delete')) {
            $buttons .=         "<a href='javascript:;'
                                    id='deleteButton'
                                    class='dropdown-item'
                                    data-toggle='modal'
                                    data-target='#smallModal'
                                    data-href='". route("uom.destroy", ["id"=>$data->id]) ."'>
                                    <i data-feather='trash-2'></i>
                                    Delete
                                </a>";
            }
            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        ->addColumn('group_id', function () {
            return '';
        })

        ->addColumn('weight', function ($data) {
            if ($data->weight == true){
                $weightStatus = '<span class="badge badge-pill badge-light-primary">Yes</span>';
            }else{
                $weightStatus = '<span class="badge badge-pill badge-light-danger">No</span>';
            }
            return $weightStatus;
        })
        ->rawColumns(['action','weight'])
        ->make(true);
    }

    public function getFactor(Request $request)
    {

        $uomFrom = $request->unitFrom;
        $uomTo = $request->unitTo;

        $data=DB::table('uom_con')
        ->where('unit_from','=',$uomFrom)
        ->where('unit_to','=',$uomTo)
        ->value('unit_factor');

        return response()->json(['hasil'=>$data]);


    }
}
