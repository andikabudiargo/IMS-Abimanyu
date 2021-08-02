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

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $data['title'] = "Article";

        $data['types'] = DB::table('article_types')
        ->where ('status','=',1)
        ->orderBy('name')
        ->get();

        $data['custs'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        $data['groups'] = DB::table('group_materials')
        ->where ('status','=',1)
        ->orderBy('name')
        ->get();
        
        return view("articles.index",$data);
    }

    public function create(Request $request)
    {
        $data['title'] = "Create Article";
        $data['subtitle'] = "Create New Article";
        
        $data['types'] = DB::table('article_types')
        ->where ('status','=',1)
        ->orderBy('name')
        ->get();

        $data['custs'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        $data['groups'] = DB::table('group_materials')
        ->where ('status','=',1)
        ->orderBy('name')
        ->get();

        $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();
                        
        return view("articles.create",$data);
    }

    public function articleCodeCreate(Request $request){
        $customer = $request->customer;
        $leadingCode = 'FG';

        $lastCode = DB::table('article')
        ->where('third_party','=',$customer)
        ->orderBy('article_alternative_code','DESC')->first();

        if (!$lastCode){
            $newCode = '00001';
        }else{
            $newCode = str_pad(substr($lastCode->article_alternative_code,5)+1, 5, "0", STR_PAD_LEFT);
        }

        $artilceCode = DB::table('third_party')
        ->where('kode',$customer)
        ->select(DB::raw("CONCAT('$leadingCode',inisial,'$newCode') AS new_code"))->value('new_code');

        return  Response()->json($artilceCode);
    
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $kode = $request->input('kode');
        $cust = $request->input('cust');
        $nama = $request->input('nama');
        $quality = $request->input('quality');
        $group = $request->input('group');
        $uom = $request->input('uom');
        $note = $request->input('note');
        
        $status = '1';
        $type='FG';

        $pesan = '';
        
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
            'kode'=>'required|iunique:article,article_alternative_code',
            'nama'=>'required'
        ];

        $this->validate($request,$rule,$messages);

        $lastCode = DB::table('article')
        ->orderBy('article_code','DESC')->first();
        
        if (!$lastCode){
            $newCode = '1000001';
        }else{
            $newCode = $lastCode->article_code+1;
        }
        

        DB::beginTransaction();
        try {
                DB::table('article')->insert([
                    'article_code' => $newCode,
                    'article_alternative_code' => $kode,
                    'article_desc' => $nama,
                    'group_of_material' => $group,
                    'third_party' => $cust,
                    'quality' => $quality,
                    'note' => $note,
                    'uom' => $uom,
                    'status' => $status,
                    'article_type' => $type,
                    'created_by' => Auth::user()->username,
                    'updated_by' => Auth::user()->username,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                DB::commit();
                $alert  ="alert-success";
                $message  = "$kode is successfully saved";
                \LogActivity::addToLog('Article save ',"username: $username Status $message");
                return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  

        } catch (Exception $e) {
            DB::rollBack();
            $alert  ="alert-warning";
            $message  = "$kode is failed to save";
            \LogActivity::addToLog('Article save ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);   
        }
        
    }

    public function edit(Request $request)
    {

        $id=$request->id;
        $data['title'] = "Edit Article";
        $data['subtitle'] = "Edit Article";
        
        $data['article'] = DB::table('article')
        ->where('id',$id)
        ->get(['article_alternative_code as code','article_desc as desc','uom','quality','note','id','group_of_material as group','third_party as cust','quality','status'])->first();

        $data['types'] = DB::table('article_types')
        ->where ('status','=',1)
        ->orderBy('name')
        ->get();

        $data['custs'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        $data['groups'] = DB::table('group_materials')
        ->where ('status','=',1)
        ->orderBy('name')
        ->get();

        $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();

        return view('articles.edit',$data);
        
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $id = $request->id;
        $kode = $request->input('kode');
        $cust = $request->input('cust');
        $nama = $request->input('nama');
        $quality = $request->input('quality');
        $group = $request->input('group');
        $uom = $request->input('uom');
        $note = $request->input('note');
        $status = $request->input('status') ? '0' : '1';

        // status : 1= aktif, 0= closing

        $pesan = '';
        
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken',
            'iunique' => "The code $nama has already been taken",
        ];
        
        $rule = [
            'nama'=>'required'
        ];

        $this->validate($request,$rule,$messages);
        
        DB::beginTransaction();

        try {
                $row_affected=DB::table('article')
                ->where('id',$id)
                ->update(
                    [
                        'article_desc' => $nama,
                        'group_of_material' => $group,
                        'third_party' => $cust,
                        'quality' => $quality,
                        'note' => $note,
                        'uom' => $uom,
                        'status' => $status,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                DB::commit();

                if($row_affected>0){
                    $alert  ="alert-success";
                    $message  = "Successfully updated";
                    \LogActivity::addToLog('Article update ',"username: $username Status $message");
                    return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  
                }else{
                    $alert  ="alert-warning";
                    $message  = "Failed to update";
                    \LogActivity::addToLog('Article update ',"username: $username Status $message");
                    return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
                }

        } catch (Exception $e) {
            DB::rollBack();
            $alert  ="alert-warning";
            $message  = "Failed to update";
            \LogActivity::addToLog('Article update ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
        }
    }

    public function destroy(Request $request)
    {

        $username =  Auth::user()->username;
        $id = $request->id;

        $row_affected = DB::table('article')
        ->where('id',$id)
        ->delete();

        if($row_affected>0){
            $alert  ="alert-success";
            $message  = "Successfully Deleted";
            \LogActivity::addToLog('Article delete ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  
        }else{
            $alert  ="alert-warning";
            $message  = "Failed to Delete";
            \LogActivity::addToLog('Article delete ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
        }

    }

    public function list(Request $request)
    {
        $code = strtolower($request->code);
        $name = strtolower($request->name);
        $group = strtolower($request->group);
        $cust = strtolower($request->cust);
        
        $data=DB::table('article')
        ->join('group_materials', 'group_materials.code', '=', 'article.group_of_material')
        ->join('third_party', 'third_party.kode', '=', 'article.third_party')
        ->where('article_alternative_code','ilike','%'.$code.'%')
        ->where('article_desc','ilike','%'.$name.'%')  // string to lower
        ->where('group_of_material','ilike','%'.$group.'%')
        ->where('third_party','ilike','%'.$cust.'%')
        ->orderBy('article_desc')->get(['article_alternative_code as code','article_desc as desc','uom','quality','note','article.id','group_materials.name as group','third_party.nama as cust']);

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="more-vertical"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            if (Auth::user()->can('article-edit')) {
            $buttons .=         '<a href="'. route('article.edit', ['id'=>$data->id]) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
            }
            if (Auth::user()->can('article-delete')) {
            $buttons .=         "<a href='javascript:;'
                                    id='deleteButton'
                                    class='dropdown-item'
                                    data-toggle='modal'
                                    data-target='#smallModal'
                                    data-href='". route("article.destroy", ["id"=>$data->id]) ."'>
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
        ->rawColumns(['action'])
        ->make(true);
    }
}
