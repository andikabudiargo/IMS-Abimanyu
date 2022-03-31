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

class UomConController extends Controller
{
    private $title;
    public function __construct()
    {
        $this->title = "UOM Coversion";
    }
    
    public function index(Request $request)
    {
        $data['title'] = "$this->title";
        $data['uoms'] = DB::table('uom')->orderBy('name')->get();
        return view("uomCon.index",$data);
    }

    public function create(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create New $this->title";
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
                $title ="Save $this->title";
                $alert  ="success";
                $message  = "$this->title is successfully saved Unit From : $unitFrom[0] Unit To : $unitTo[0]";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);  

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Save $this->title";
            $alert  ="warning";
            $message  = "$this->title is failed to saved Unit From : $unitFrom[0] Unit To : $unitTo[0]";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }
    }

    public function edit(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";
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
        $id=Crypt::decryptString($request->id);

        $row_affected = DB::table('uom_con')
        ->where('id',$id)
        ->delete();

        if($row_affected>0){
            $title ="Delete $this->title";
            $alert  ="success";
            $message  = "$this->title $name is successfully deleted";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
        }else{
            $title ="Delete $this->title";
            $alert  ="warning";
            $message  = "$this->title $name is failed to delete";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
        }
    }

    public function list(Request $request)
    {
        $unitFrom = strtolower($request->unitFrom);
        $unitTo = strtolower($request->unitTo);
        // ilike = string to lower
        $data = DB::table('uom_con')
        ->where(function ($query) use ($unitFrom,$unitTo) {
            $unitFrom ? $query->where('unit_from','ilike','%'.$unitFrom.'%') :"";
            $unitTo ? $query->where('unit_to','ilike','%'.$unitTo.'%') :"";  
        })->get(); 

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            if (Auth::user()->can('department-edit')) {
            $buttons .=         '<a href="'. route('uomCon.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
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
                                    data-href='". route("uomCon.destroy", ["id"=>Crypt::encryptString($data->id)]) ."'>
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
