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

        // $data['custs'] = DB::table('third_party')
        // ->where ('third_party_type','=','cust')
        // ->orderBy('nama')
        // ->get();


        $data['custs'] = DB::table('third_party')
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

        // $data['custs'] = DB::table('third_party')
        // ->where ('third_party_type','=','cust')
        // ->orderBy('nama')
        // ->get();

        $data['groups'] = DB::table('group_materials')
        ->where ('status','=',1)
        ->orderBy('name')
        ->get();

        $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();
                        
        return view("articles.create",$data);
    }

    public function articleCodeCreate($custCode,$leadCode){
        /*
        pembuatan article_alternative_code sesuai dengan aturan, kalo FG dan RM harus ada kode cabang nya
        apabila type nya FG atau RM makan akan terbentuk sekaligus 2 article
        eg. FGXXX0001
        XXX= Initial dari customer
        */

        $customer = $custCode;
        $leadingCode = $leadCode;
    
        if ($leadingCode == "FG" || $leadingCode == "RM"){
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
            ->select(DB::raw("CONCAT('FG',inisial,'$newCode','|RM',inisial,'$newCode') AS new_code"))->value('new_code');

        }else{
            $lastCode = DB::table('article')
            ->where('article_alternative_code','not like','FG%')
            ->orWhere('article_alternative_code','not like','RM%')
            ->orderBy('article_alternative_code','DESC')->first();

            if (!$lastCode){
                $newCode = '00000001';
            }else{
                $newCode = str_pad(substr($lastCode->article_alternative_code,8)+1, 8, "0", STR_PAD_LEFT);
            }

            $artilceCode = $leadingCode.$newCode;
        }
        
        return  $artilceCode;
    
    }

    public function getArticleCode(){
        $lastCode = DB::table('article')
        ->orderBy('article_code','DESC')->first();
        
        if (!$lastCode){
            $newCode = '1000001';
        }else{
            $newCode = $lastCode->article_code+1;
        }

        return $newCode;
    }

    public function storeImage(Request $request){
        $image = $request->file('file');    
        $files = [];
        foreach($image as $val){
            // Simpan file si folder storage/app/public/article-image dengan nama file yang sudah di generater= otomatis
            // jangan lupa untuk membuat symbolic link php artisan storage:link
            $image = $val->store('article-image');
            $files[]=$image;
        }

        return response()->json(array('files' => $files));
    }

    public function store(Request $request)
    {
        // Dump, Die, Debug Fungsinya untuk nge-debug hasil dari submit
        // ddd($request);
        
        $username =  Auth::user()->username;
        $type = $request->input('articleType');
        $cust = $request->input('cust');
        $nama = $request->input('nama');
        $group = $request->input('group');
        $uom = $request->input('uom');
        $price = $request->input('price');
        $price = str_replace(",","",$price);
        $note = $request->input('note');
        $files = $request->input('files');
        $status = '1';
        $pesan = '';
        
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken',
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $rule = [
            'nama'=>'required'
        ];

        $this->validate($request,$rule,$messages);

        $articleCode = $this->articleCodeCreate($cust,$type);
        $articles = explode("|",$articleCode);      
        
        DB::beginTransaction();
        try {
                foreach($articles as $val){
                    $artCode = $this->getArticleCode();
                    DB::table('article')->insert([
                        'article_code' => $artCode,
                        'article_alternative_code' => $val,
                        'article_desc' => $nama,
                        'group_of_material' => $group,
                        'third_party' => $cust,
                        'note' => $note,
                        'uom' => $uom,
                        'costprice' => $price,
                        'status' => $status,
                        'article_type' => $type,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]); 

                    if($files){
                        foreach($files as $val){
                            DB::table('images')->insert([
                                'key' => $artCode,
                                'name' => $nama,
                                'path' => $val,
                                'created_by' => Auth::user()->username,
                                'updated_by' => Auth::user()->username,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s')
                            ]); 
                        }
                    }
                }

                DB::commit();
                $alert  ="alert-success";
                $message  = "$articleCode is successfully saved";
                \LogActivity::addToLog('Article save ',"username: $username Status $message");
                return redirect()->back()->with(['alert'=>$alert,'message'=> $message,'articleCode'=>$articleCode]);  

        } catch (Exception $e) {
            DB::rollBack();
            $alert  ="alert-warning";
            $message  = "$articleCode is failed to save";
            \LogActivity::addToLog('Article save ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message,'articleCode'=>$articleCode]);   
        }
        
    }

    public function edit(Request $request)
    {

        $id=$request->id;
        $data['title'] = "Edit Article";
        $data['subtitle'] = "Edit Article";
        
        $data['article'] = DB::table('article')
        ->where('id',$id)
        ->get(['article_code','costprice','article_alternative_code as code','article_desc as desc','uom','quality','note','id','group_of_material as group','third_party as cust','quality','status','article_type','imgfile'])->first();

        $data['images'] = DB::table('images')
        ->where('key',$data['article']->article_code)
        ->get();

        $data['types'] = DB::table('article_types')
        ->where ('status','=',1)
        ->orderBy('name')
        ->get();

        $data['article']->article_type  == 'FG' || $data['article']->article_type  == 'RM'  ? $typeTP = 'cust' : $typeTP = 'supp';

        $data['custs'] = DB::table('third_party')
        ->where ('third_party_type','=',$typeTP)
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

    public function show(Request $request)
    {

        $id=$request->id;
        $data['title'] = "Edit Article";
        $data['subtitle'] = "Edit Article";
        
        $data['article'] = DB::table('article')
        ->where('id',$id)
        ->get(['article_code','costprice','article_alternative_code as code','article_desc as desc','uom','quality','note','id','group_of_material as group','third_party as cust','quality','status','article_type','imgfile'])->first();

        $data['images'] = DB::table('images')
        ->where('key',$data['article']->article_code)
        ->get();

        $data['types'] = DB::table('article_types')
        ->where ('status','=',1)
        ->orderBy('name')
        ->get();

        $data['article']->article_type  == 'FG' || $data['article']->article_type  == 'RM'  ? $typeTP = 'cust' : $typeTP = 'supp';

        $data['custs'] = DB::table('third_party')
        ->where ('third_party_type','=',$typeTP)
        ->orderBy('nama')
        ->get();

        $data['groups'] = DB::table('group_materials')
        ->where ('status','=',1)
        ->orderBy('name')
        ->get();

        $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();

        return view('articles.show',$data);
        
    }

    public function update(Request $request)
    {

        $username =  Auth::user()->username;
        $id = $request->id;
        $artCode = $request->artCode;
        $type = $request->input('articleType');
        $cust = $request->input('cust');
        $nama = $request->input('nama');
        $group = $request->input('group');
        $uom = $request->input('uom');
        $price = $request->input('price');
        $price = str_replace(",","",$price);
        $note = $request->input('note');
        $files = $request->input('files');
        $fileDihapus = $request->input('fileDihapus');
        $status = $request->input('status') ? '0' : '1';
        $pesan = '';
        

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
                        'note' => $note,
                        'uom' => $uom,
                        'costprice' => $price,
                        'status' => $status,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                if($fileDihapus){
                    DB::table('images')->whereIn('path',$fileDihapus)->delete();
                }

                if($files){
                    foreach($files as $val){
                        DB::table('images')->insert([
                            'key' => $artCode,
                            'name' => $nama,
                            'path' => $val,
                            'created_by' => Auth::user()->username,
                            'updated_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ]); 
                    }
                }
                
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
        $type = strtolower($request->type);

        // $type == 'CM'? $type='supp' :  $type='cust';
        $data=DB::table('article');
        $data->select('article.*','costprice','article_alternative_code as code','article_desc as desc','uom','quality','note','article.id','group_materials.name as group','third_party.nama as cust');
        $data->leftJoin('group_materials', 'group_materials.code', '=', 'article.group_of_material');
        $data->leftJoin('third_party', 'third_party.kode', '=', 'article.third_party');
        $code ? $data->where('article_alternative_code','ilike','%'.$code.'%') :'';
        $name ? $data->where('article_desc','ilike','%'.$name.'%') :'';
        $group ? $data->where('group_of_material','ilike','%'.$group.'%') :'';
        $cust ? $data->where('third_party','ilike','%'.$cust.'%') :'';
        $type ? $data->where('article_alternative_code','ilike',$type.'%') :'';      
        $data->orderBy('article_desc');
        $data->get(['costprice','article_alternative_code as code','article_desc as desc','uom','quality','note','article.id','group_materials.name as group','third_party.nama as cust']);

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            
            if (Auth::user()->can('article-edit')) {
            $buttons .=         '<a href="'. route('article.edit', ['id'=>$data->id]) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
            }
            $buttons .=         '<a href="'. route('article.show', ['id'=>$data->id]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    Detail
                                </a>';
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

    public function getSupplier(Request $request)
    {
        $code = $request->type;
        $dependent=$request->dependent;
        $code == 'FG' || $code == 'RM' ? $type = 'cust' : $type= 'supp';
        
        $data= DB::table('third_party') 
        ->where('third_party_type',$type)
        ->orderBy('nama')
        ->get();            
        
        $output='';
        $output .='<option value=""></option>';

        foreach ($data as $row){
            $output .="<option value='$row->kode'>$row->kode - $row->nama</option>";
        }        

        return $output;
    }
}
