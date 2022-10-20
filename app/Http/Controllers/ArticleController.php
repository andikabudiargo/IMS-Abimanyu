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

class ArticleController extends Controller
{
    private $title;
    private $decimalPlaces;
    public function __construct()
    {
        $this->title = "Article";
        $this->decimalPlaces = config('globalParam.decimal');
    }

    public function getTableColoumn(){
        $kolom=    
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=>false, 'searchable'=>false],
            ['data'=>'article_alternative_code','name'=>'article_alternative_code','title'=>'Code'],
            ['data'=>'desc','name'=>'article_desc','title'=>'Name'],
            ['data'=>'third_party','name'=>'third_party','title'=>'Cust/supp'],
            ['data'=>'cust','name'=>'third_party.nama','title'=>'Custs/Supp'],
            ['data'=>'costprice','name'=>'costprice','title'=>'Price'],
            ['data'=>'article_qty','name'=>'article_qty','title'=>'Qty'],
            ['data'=>'uom','name'=>'uom','title'=>'UOM'],
            ['data'=>'article_type','name'=>'article_type','title'=>'Type'],
            ['data'=>'safety_stock','name'=>'safety_stock','title'=>'Safety Stock'],
            ['data'=>'min_package','name'=>'min_package','title'=>'Min Package'],
            ['data'=>'group','name'=>'group_materials.name','title'=>'Group'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'note','name'=>'note','title'=>'Note']
        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnMovement(){
        $kolom=    
        [
            ['data'=>'movement_code','name'=>'movement_code','title'=>'Code'],
            ['data'=>'movement_date','name'=>'movement_date','title'=>'Date'],
            ['data'=>'movement_type','name'=>'movement_type','title'=>'Type'],
            ['data'=>'movement_transnno','name'=>'movement_transnno','title'=>'Ref'],
            ['data'=>'movement_price','name'=>'movement_price','title'=>'Price'],
            // ['data'=>'movement_min','name'=>'movement_min','title'=>'QTY Min'],
            // ['data'=>'movement_plus','name'=>'movement_plus','title'=>'QTY Plus'],
            ['data'=>'qty','name'=>'qty','title'=>'QTY'],
            ['data'=>'balanceqty','name'=>'balanceqty','title'=>'QTY Total'],
            ['data'=>'movement_desc','name'=> 'movement_desc','title'=>'Description']
        ];
        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $data['title'] = $this->title;

        $data['types'] = DB::table('article_types')
        ->where ('status','=',1)
        ->orderBy('name')
        ->get();
    
        $data['supps'] = DB::table('third_party')
        // ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();        

        $data['groups'] = DB::table('group_materials')
        ->where ('status','=',1)
        ->orderBy('name')
        ->get();

        $data['kolom'] = $this->getTableColoumn();
        $data['kolomMovement'] = $this->getTableColoumnMovement();
        
        return view("articles.index",$data);
    }

    public function create(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create New $this->title";
        
        $data['types'] = DB::table('article_types')
        ->where ('status','=',1)
        ->orderBy('name')
        ->get();

        $data['groups'] = DB::table('group_materials')
        ->where ('status','=',1)
        ->orderBy('name')
        ->get();

        $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();

        $data['articles']= DB::table('article') 
        ->orderBy('article_desc')
        ->distinct('article_desc')
        ->pluck('article_desc');
                        
        return view("articles.create",$data);
    }

    public function articleCodeCreate($custCode,$leadCode){
        //membuat article code diaawali dengan leadCode yang isinya kode awal dari article
        
        $customer = $custCode;
        // $customerInitial = substr($custCode,0,3);
        $customerInitial = DB::table('third_party')->where('kode',$customer)->value('inisial');
        $leadingCode = $leadCode; 

        if (($leadingCode == "FG") or  ($leadingCode == "RMP") or ($leadingCode == "RMNP")){
            /*
            pembuatan article_alternative_code sesuai dengan aturan, kalo FG harus ada kode cabang nya
            eg. FGXXX0001
            XXX= Initial dari customer
            */

            /*
            revisi 9-10-2022
            Suapaya alternative code tidak bentrok dikarenakan ada inisial yang lebih dari satu
            maka urutan hanya berdasarkan type+inisial
            */

            $lastCode = DB::table('article')
            // ->where('third_party','=',$customer)
            ->where('article_alternative_code','like',$leadingCode.$customerInitial.'%')
            ->orderBy('article_alternative_code','DESC')->first();

            if (!$lastCode){
                if (($leadingCode == "RMP") or ($leadingCode == "RMNP")){
                    $newCode = '01';
                }else{
                    $newCode = '00001';
                }
            }else{
                if (($leadingCode == "RMP") or ($leadingCode == "RMNP")){
                    $newCode = str_pad(substr($lastCode->article_alternative_code,-2)+1, 2, "0", STR_PAD_LEFT);
                }else{
                    $newCode = str_pad(substr($lastCode->article_alternative_code,-4)+1, 4, "0", STR_PAD_LEFT);
                }
                
            }

            $articleCode = $leadingCode.$customerInitial.$newCode."~".$leadingCode;

            /*
            revisi 9-10-2022
            tidak udah lihat database langsung bikin kode saja
            */
            // $articleCode = DB::table('third_party')
            // ->where('kode',$customer)
            // // ->where('inisial',$customerInitial)
            // ->select(DB::raw("CONCAT('$leadingCode',inisial,'$newCode','~','c') AS new_code"))->value('new_code');

        }else{
            $lastCode = DB::table('article')
            ->where('article_alternative_code','like',$leadingCode.'%')
            ->orderBy('article_alternative_code','DESC')->first();

            if (!$lastCode){
                $newCode = '0000001';
            }else{
                $newCode = str_pad(substr($lastCode->article_alternative_code,7)+1, 7, "0", STR_PAD_LEFT);
            }

            $articleCode = $leadingCode.$newCode."~".$leadingCode;
        }
        
        
        return  $articleCode;
    
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
        $type = $request->articleType;
        $cust = $request->cust;
        $nama = strtoupper($request->nama);
        $group = $request->group;
        $uom = $request->uom;
        $price = $request->price;
        $price = $price ? str_replace(",","",$price) : $price;
        $safetyStock = $request->safetyStock;
        $minimumPackage = $request->minimumPackage;
        $note = $request->note;
        $files = $request->files;
        $status = '1';
        $pesan = '';
        $brand = $request->brand;

        $colorCode = $request->colorCode;
        $variant = $request->variant;

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
            'nama'=>'required',
            'articleType'=>'required',
            'minimumPackage'=>'required'
        ];

        $this->validate($request,$rule,$messages);

        $articleCode = $this->articleCodeCreate($cust,$type);
                
        DB::beginTransaction();
        try {
                $artCode = $this->getArticleCode();
                $articleDet =  explode("~",$articleCode); 
                DB::table('article')->insert([
                    'article_code' => $artCode,
                    'article_alternative_code' => $articleDet[0],
                    'article_desc' => $nama,
                    'group_of_material' => $group,
                    'third_party' => $cust[0],
                    'note' => $note,
                    'uom' => $uom,
                    'safety_stock' => $safetyStock,
                    'min_package' => $minimumPackage,
                    'costprice' => $price,
                    'status' => $status,
                    'color_code' => $colorCode,
                    'variant' => $variant,
                    'article_type' => $articleDet[1],
                    'created_by' => Auth::user()->username,
                    'updated_by' => Auth::user()->username,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'brand' => $brand
                ]); 

                foreach($cust as $val){
                    DB::table('article_supplier')->insert([
                        'article_code' => $artCode,
                        'supplier_code' => $val,
                        'main_supplier' => $cust[0] == $val ? 'Y' : 'N',
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]); 
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
                $title ="Save $this->title";
                $alert  ="success";
                $message  = "$this->title $articleCode is successfully saved";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with(['status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'articleCode'=>$articleCode]);  

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Save $this->title";
            $alert  ="warning";
            $message  = "$this->title $articleCode is failed to save";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'articleCode'=>$articleCode]);
        }
        
    }

    public function edit(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";
        
        $data['article'] = DB::table('article')
        ->where('id',$id)
        ->get(['brand','article_code','costprice','article_alternative_code as code','article_desc as desc','uom','quality','note','id','group_of_material as group','third_party as cust','quality','status','article_type','imgfile','color_code','variant','safety_stock','min_package'])->first();

        $data['images'] = DB::table('images')
        ->where('key',$data['article']->article_code)
        ->get();

        $data['types'] = DB::table('article_types')
        ->where ('status','=',1)
        ->orderBy('name')
        ->get();

        $code = $data['article']->article_type;
        $data['custs'] = DB::table('third_party')->where(function ($query) use ($code) {
            $code == 'FG' ? $query->where('third_party_type','cust') : '';
            $code != 'FG' && $code != 'RM' ? $query->where('third_party_type','supp') : '';
        })->get();

        $data['groups'] = DB::table('group_materials')
        ->where ('status','=',1)
        ->orderBy('name')
        ->get();

        $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();

        $data['articles']= DB::table('article') 
        ->orderBy('article_desc')
        ->distinct('article_desc')
        ->pluck('article_desc');

        $data['suppliers']= DB::table('article_supplier') 
        ->where('article_code',$data['article']->article_code)
        ->orderBy('id')
        ->pluck('supplier_code')->toArray();

        return view('articles.edit',$data);
        
    }

    public function show(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Edit Article";
        $data['subtitle'] = "Edit Article";
        
        $data['article'] = DB::table('article')
        ->where('id',$id)
        ->get(['article_code','costprice','article_alternative_code as code','article_desc as desc','uom','quality','note','id','group_of_material as group','third_party as cust','quality','status','article_type','imgfile','color_code','variant','safety_stock','min_package'])->first();

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

        $data['suppliers']= DB::table('article_supplier') 
        ->where('article_code',$data['article']->article_code)
        ->orderBy('id')
        ->pluck('supplier_code')->toArray();
        
        return view('articles.show',$data);
        
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $id = $request->id;
        $artCode = $request->artCode;
        $articleAltCode = $request->kode;
        $type = $request->articleType;
        $cust = $request->cust;
        $nama = strtoupper($request->nama);
        $group = $request->group;
        $uom = $request->uom;
        $price = $request->price;
        $price = $price ? str_replace(",","",$price) : $price;
        $safetyStock = $request->safetyStock;
        $minimumPackage = $request->minimumPackage;
        $note = $request->note;
        $files = $request->files;
        $fileDihapus = $request->fileDihapus;
        $status = $request->statu == 'on' ? '0' : '1';
        $pesan = '';
        $colorCode = $request->colorCode;
        $variant = $request->variant;
        $brand = $request->brand;

        // status : 1= aktif, 0= freeze        
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
                        'third_party' => $cust[0],
                        'note' => $note,
                        'uom' => $uom,
                        'safety_stock' => $safetyStock,
                        'min_package' => $minimumPackage,
                        'costprice' => $price,
                        'status' => $status,
                        'color_code' => $colorCode,
                        'variant' => $variant,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'brand' => $brand
                    ]
                );
                
                $dataset=[];
                foreach ($cust as $val) {
                    $dataSet[] = [
                        $artCode.$val
                    ];
                }
                    
                //Delete kalo article tidak ada di po $poNumber dan article nya $val->article_code
                //berdasarkan 2 kondisi
                DB::table('article_supplier')
                ->whereNotIn(DB::raw("CONCAT(article_code,supplier_code)"),$dataSet)
                ->where('article_code',$artCode)
                ->delete();
                    
                foreach($cust as $val){
                    DB::table('article_supplier')
                    ->updateOrInsert(
                    ['article_code' => $artCode,'supplier_code' => $val],
                    [ 
                        'article_code' => $artCode,
                        'supplier_code' => $val,
                        'main_supplier' => $cust[0] == $val ? 'Y' : 'N',
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]); 
                }

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
                    DB::commit();
                    $title ="Update $this->title";
                    $alert  ="success";
                    $message  = "$this->title $articleAltCode is successfully updated";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return redirect()->back()->with(['status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'articleCode'=>$articleAltCode]);
                }else{
                    $title ="Update $this->title";
                    $alert  ="warning";
                    $message  = "$this->title $articleAltCode is failed to updated";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return redirect()->back()->with(['status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'articleCode'=>$articleAltCode]);
                }

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Update $this->title";
            $alert  ="warning";
            $message  = "$this->title $articleAltCode is failed to updated";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'articleCode'=>$articleAltCode]);
        }
    }

    public function destroy(Request $request)
    {
        $username =  Auth::user()->username;
        $id = $request->id;
        $artCode = $request->artCode;
        $articleAltCode = $request -> articleAltCode;

        $count = DB::table('movement')
        ->where('artikel_code',$artCode)
        ->count();

        $statusDelete ='Deleted';
        if ($count > 1){
            $row_affected=DB::table('article')
            ->where('id',$id)
            ->update(
                [
                    'status' => '0',
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );
            $statusDelete ='Freeze';
        }else{
            $row_affected = DB::table('article')
            ->where('id',$id)
            ->delete();
        }

        if($row_affected>0){
            $title ="$statusDelete $this->title";
            $alert  ="success";
            $message  = "$this->title $articleAltCode is successfully $statusDelete";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'articleCode'=>$articleAltCode]);
        }else{
            $title ="$statusDelete $this->title";
            $alert  ="warning";
            $message  = "$this->title $articleAltCode is failed to $statusDelete";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'articleCode'=>$articleAltCode]);
        }

    }

    public function list(Request $request)
    {
        $code = strtolower($request->code);
        $name = strtolower($request->name);
        $group = strtolower($request->group);
        $cust = strtolower($request->cust);
        $supp = strtolower($request->supp);
        $type = strtolower($request->type);

        $data=DB::table('article')
        ->select('article.*'
        ,'costprice'
        ,'article.article_code as art_code'
        ,'article_alternative_code as code'
        ,'article_desc as desc'
        ,'brand'
        ,'article.uom'
        ,'quality'
        ,'note'
        ,'article.id'
        ,'group_materials.name as group'
        ,'third_party.nama as cust'
        ,'article_stock.article_qty as article_qty'
        ,'safety_stock'
        ,'min_package'
        ,'uom.uom_group')
        // ,DB::raw("case when uom.uom_group = 'PIECE' then TO_CHAR(article_stock.article_qty,'999,999,999') else TO_CHAR(article_stock.article_qty,'999,999,999.99') end as article_qty"))
        ->leftJoin('group_materials', 'group_materials.code', '=', 'article.group_of_material')
        ->leftJoin('third_party', 'third_party.kode', '=', 'article.third_party')
        ->leftJoin('article_stock', 'article_stock.article_code', '=', 'article.article_code')
        ->leftJoin('uom','uom.code','article.uom')
        ->where(function ($query) use ($code,$name,$group,$cust,$supp,$type) {
            $code ? $query->where('article_alternative_code','ilike','%'.$code.'%') :'';
            $name ? $query->where('article_desc','ilike','%'.$name.'%') :'';
            $group ? $query->where('group_of_material','ilike','%'.$group.'%') :'';
            $cust ? $query->where('third_party','ilike','%'.$cust.'%') :'';
            $supp ? $query->where('third_party','ilike','%'.$supp.'%') :'';
            $type ? $query->where('article_alternative_code','ilike',$type.'%') :'';      
        })->orderBy('article_desc')->get();
       
        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
        
            $buttons .=         '<a href="javascript:;" onclick="movement(\''.$data->art_code.'\',\''.$data->code.'\',\''.$data->desc.'\')" class="dropdown-item">
                                    <i data-feather="activity"></i>
                                    Movement
                                </a>';
            if (Auth::user()->can('article-edit')) {
            $buttons .=         '<a href="'. route('article.edit',  ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
            }
            $buttons .=         '<a href="'. route('article.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    Detail
                                </a>';
            if (Auth::user()->can('article-delete')) {
            $buttons .=         '<a href="javascript:;"
                                    id="deleteButton"
                                    class="dropdown-item"
                                    data-toggle="modal"
                                    data-target="#smallModal"
                                    data-href="'. route("article.destroy", ['id'=>$data->id,'artCode'=>$data->art_code,'articleAltCode'=>$data->article_alternative_code]) .'">
                                    <i data-feather="trash-2" class="feather-14-red"></i>
                                    Delete
                                </a>';
            }
            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })

        ->addColumn('article_alternative_code', function ($data) {
            $badges=['badge-light-danger','badge-light-primary'];
            return '<span style="display: none;">'.$data->article_alternative_code.'</span><a class="badge d-block '.$badges[$data->status].'" href="'. route('article.show', ['id'=>Crypt::encryptString($data->id)]) .'" ><span>'.$data->article_alternative_code.'</span></a>';
        })
        
        ->addColumn('article_qty', function ($data) {
            // $artilceQty = $data->uom_group =='PIECE' ? number_format($data->article_qty) : number_format($data->article_qty,4);
            // $artilceQty = number_format($data->article_qty,$this->decimalPlaces);

            $artilceQty = floatval($data->article_qty) == intval($data->article_qty) ? number_format($data->article_qty) : number_format($data->article_qty,$this->decimalPlaces);
            return $data->article_qty < 0 ? "<div class='text-red'>$artilceQty</div>" : "<div class='text-hitam'>$artilceQty</div>";
        })
        ->addColumn('status', function ($data) {
            $badges=['badge-light-danger','badge-light-primary'];
            $statusCode = ['Freeze','Active'];
            return "<div class='badge badge-pill ".$badges[$data->status]."'>".$statusCode[$data->status]."</div>";
        })
        ->rawColumns(['action','article_alternative_code','status','article_qty'])
        ->make(true);
    }

    public function getSupplier(Request $request)
    {
        $code = $request->type;
        $dependent=$request->dependent;

        $data = DB::table('third_party')->where(function ($query) use ($code) {
            //kalo barang finish goods hanya punya nya customer, tapi kalo raw material yang punyanya bisa customer bisa supplier
            // $code == 'FG' ? $query->where('third_party_type','cust') : $query->where('third_party_type','supp');  //tadinya ini
            $code == 'FG' ? $query->where('third_party_type','cust') : '';
            $code != 'FG' && $code != 'RM' ? $query->where('third_party_type','supp') : '';

        })->get();
        
        $output='';
        $output .= $code == 'FG'?'<option value=""></option>':'<option value="All">All</option>';

        foreach ($data as $row){
            $output .="<option value='$row->kode'>$row->kode - $row->nama</option>";
        }        

        return $output;
    }

    public function movement(Request $request){
        
        $articleCode = $request->articleCode;
        $location = 'WH';
        $siteCode = 'HO';
        $sqlku=("SELECT movement_code
                    ,movement_date
                    ,artikel_code
                    ,artikel_desc
                    ,movement_price
                    ,movement_type
                    ,movement_transnno
                    ,movement_min
                    ,movement_plus
                    ,qty
                    ,balanceqty
                    ,movement_desc
                    ,site_code
                    ,location_number
                from (
                select movement_code
                ,artikel_code
                ,artikel_desc
                ,movement_price
                ,movement_date
                ,movement_desc
                ,movement_type
                ,movement_min
                ,movement_plus
                ,movement_transnno
                ,movement_plus - movement_min as qty
                ,sum(movement_plus) over (order by movement_code) - sum(movement_min) over (order by movement_code) as balanceqty
                ,row_Number() over (order by movement_code) as rn
                ,site_code
                ,location_number
                from movement
                where artikel_code='$articleCode'
                and site_code = '$siteCode'
                and location_number = '$location'
                ) t
                order by movement_code");
        $data = DB::select($sqlku);
        return Datatables::of($data)
        ->addColumn('qty', function ($data) {
            // $artilceQty = $data->uom_group =='PIECE' ? number_format($data->article_qty) : number_format($data->article_qty,3);
            $qty = number_format($data->qty,$this->decimalPlaces);
            return $data->qty < 0 ? "<div class='text-red'>$qty</div>" : "<div class='text-hitam'>$qty</div>";
        })
        ->addColumn('balanceqty', function ($data) {
            // $artilceQty = $data->uom_group =='PIECE' ? number_format($data->article_qty) : number_format($data->article_qty,3);
            $balanceQty = number_format($data->balanceqty,$this->decimalPlaces);
            return $data->balanceqty < 0 ? "<div class='text-red'>$balanceQty</div>" : "<div class='text-hitam'>$balanceQty</div>";
        })
        ->rawColumns(['qty','balanceqty'])
        ->make(true);
    }
    
}
