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
        $data['uoms'] = DB::table('uom')->orderBy('name')->get();
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
        $unitFrom = explode("|",$unitFrom);
        $unitTo = $request->input('unitTo');
        $unitTo = explode("|",$unitTo);
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
                        'unit_from' => $unitFrom[0],
                        'unit_to' => $unitTo[0]
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
                \LogActivity::addToLog('Uom save ',"username: $username Unit From : $unitFrom[0] Unit To : $unitTo[0]");
                return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  

        } catch (Exception $e) {
            DB::rollBack();
            $alert  ="alert-warning";
            $message  = "Data is failed to save";
            \LogActivity::addToLog('Dept save ',"username: $username Unit From : $unitFrom[0] Unit To : $unitTo[0]");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);   
        }
    }

    public function edit(Request $request)
    {
        $id=$request->id;
        $data['title'] = "Edit UOM Conversion";
        $data['subtitle'] = "Edit UOM Conversion";
        $data['uomCon'] = DB::table('uom_con')
        ->leftJoin('uom','code','unit_from')
        ->where('uom_con.id',$id)
        ->get()->first();

        $data['uoms'] = DB::table('uom')
        ->where('uom_group',$data['uomCon']->uom_group)
        ->orderBy('name')->get();

        return view('uomCon.edit',$data);
        
    }

    public function destroy(Request $request)
    {
        $username =  Auth::user()->username;
        $id = $request->id;

        $row_affected = DB::table('uom_con')
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
        $unitFrom = strtolower($request->unitFrom);
        $unitTo = strtolower($request->unitTo);
        // ilike = string to lower
        $data=DB::table('uom_con');
        $unitFrom ? $data->where('unit_from','ilike','%'.$unitFrom.'%') :"";
        $unitTo ? $data->where('unit_to','ilike','%'.$unitTo.'%') :"";  
        $data->get();

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            if (Auth::user()->can('department-edit')) {
            $buttons .=         '<a href="'. route('uomCon.edit', ['id'=>$data->id]) .'" class="dropdown-item">
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
                                    data-href='". route("uomCon.destroy", ["id"=>$data->id]) ."'>
                                    <i data-feather='trash-2'></i>
                                    Delete
                                </a>";
            }
            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        ->rawColumns(['action'])
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
