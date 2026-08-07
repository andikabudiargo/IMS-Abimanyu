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
    use Excel;
    use App\Imports\SafetyStockImport;
    use App\Exports\SafetyStockExport;

    class ArticleController extends Controller
{
    private $title;
    private $decimalPlaces;
    private $moduleCode;
    private $lockDate;
    private $lockDateIndex;

    public function __construct()
{
    $this->title = "Article";
    $this->decimalPlaces = config('globalParam.decimal');
    $this->moduleCode = "ART";
}

    private function isModuleLocked()
{
    return false;
}

        public function getTableColoumn(){
            $kolom=    
            [
                ['data'=>'action','name'=>'action','title'=>'action','orderable'=>false, 'searchable'=>false],
                ['data'=>'article_alternative_code','name'=>'article_alternative_code','title'=>'Code'],
                ['data'=>'desc','name'=>'article_desc','title'=>'Name'],
                ['data'=>'third_party','name'=>'third_party','title'=>'Cust/Supp Code'],
                ['data'=>'cust','name'=>'third_party.nama','title'=>'Custs/Supp'],
                ['data'=>'uom','name'=>'uom','title'=>'UOM'],
                ['data'=>'article_type','name'=>'article_type','title'=>'Type'],
                ['data'=>'group_of_material','name'=>'group_of_material','title'=>'Group'],
                ['data'=>'color_code','name'=>'color_code','title'=>'Color'],
                ['data'=>'variant','name'=>'variant','title'=>'Variant'],
                ['data'=>'brand','name'=>'brand','title'=>'Brand'],
                ['data'=>'safety_stock','name'=>'safety_stock','title'=>'Safety Stock'],
                ['data'=>'min_package','name'=>'min_package','title'=>'Min Package'],
                ['data'=>'group','name'=>'group_materials.name','title'=>'Group','visible'=>false],
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
                ['data'=>'last_qty','name'=>'last_qty','title'=>'Last QTY'],
                ['data'=>'movement_desc','name'=> 'movement_desc','title'=>'Description'],
                ['data'=>'created_at','name'=> 'created_at','title'=>'Created At'],
                ['data'=>'urutan','name'=> 'urutan','title'=>'Runnng Number', 'searchable'=>false, 'visible'=>false]
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

        public function getStats(Request $request)
{
    $code  = strtolower($request->code);
    $name  = strtolower($request->name);
    $group = strtolower($request->group);
    $cust  = strtolower($request->cust);
    $supp  = strtolower($request->supp);
    $type  = strtolower($request->type);

    $base = DB::table('article')
    ->where(function ($query) use ($code,$name,$group,$cust,$supp,$type) {
        $code  ? $query->where('article_alternative_code','ilike','%'.$code.'%') : '';
        $name  ? $query->where('article_desc','ilike','%'.$name.'%') : '';
        $group ? $query->where('group_of_material','ilike','%'.$group.'%') : '';
        $cust  ? $query->where('third_party','ilike','%'.$cust.'%') : '';
        $supp  ? $query->where('third_party','ilike','%'.$supp.'%') : '';
        $type  ? $query->where('article_alternative_code','ilike',$type.'%') : '';
    });

    return response()->json([
        'total'  => (clone $base)->count(),
        'active' => (clone $base)->where('status','1')->count(),
        'freeze' => (clone $base)->where('status','0')->count(),
    ]);
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

            // $data['articles']= DB::table('article') 
            // ->orderBy('article_desc')
            // ->distinct('article_desc')
            // ->pluck('article_desc');
                            
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
                
                if($leadingCode=='GA'){
                    $lastCode = DB::table('article')
                    ->where('article_alternative_code','like',$leadingCode.'0%')
                    ->orderBy('article_alternative_code','DESC')->first();
                }else{
                    $lastCode = DB::table('article')
                    ->where('article_alternative_code','like',$leadingCode.'%')
                    ->orderBy('article_alternative_code','DESC')->first();
                }

                if (!$lastCode){
                    if($leadingCode=='GA'){
                        $newCode = '00000001';
                    }else{
                        $newCode = '0000001';
                    }
                }else{
                    $newCode = str_pad(substr($lastCode->article_alternative_code,-7)+1, 7, "0", STR_PAD_LEFT);
                    if($leadingCode=='GA'){
                        $newCode='0'.$newCode;
                    }
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
            $username =  Auth::user()->username;
            $type = $request->articleType;
            $cust = $request->cust;
            $nama = strtoupper($request->nama);
            $group = $request->group;
            $uom = $request->uom;
            $price = $request->price;
            $price = $price ? str_replace(",","",$price) : $price;
            $sapetiStok = $request->safetyStock;
            $safetyStock = $sapetiStok ? str_replace(",","",$sapetiStok) : $sapetiStok;
            // $minimumPackage = $request->minimumPackage;
            $minimumPackage = $request->minimumPackage ? str_replace(",","",$request->minimumPackage) : $request->minimumPackage;
            $note = $request->note;
            $files = $request->files;
            $status = '1';
            $pesan = '';
            $brand = $request->brand;

            $colorCode = $request->colorCode;
            $variant = $request->variant;

            $orderable = $request->orderableCheck == 'on' ? '1' : '0';

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
                        'brand' => $brand,
                        'orderable' =>$orderable
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
            ->get(['brand','article_code','costprice','article_alternative_code as code','article_desc as desc','uom','quality','note','id','group_of_material as group','third_party as cust','quality','status','article_type','imgfile','color_code','variant','safety_stock','min_package','orderable','marketing'])->first();
            

            $data['images'] = DB::table('images')
            ->where('key',$data['article']->article_code)
            ->get();

            $data['accounts'] = DB::table('accounts')
    // ->whereIn('type_code',['21','22','23','24'])
    ->where('acc_header','!=','HEADER')
    ->orderByRaw("CASE WHEN account ~ '^[0-9]+(\.[0-9]+)?$' THEN account::numeric ELSE NULL END")
    ->orderBy('account')
    ->get();


            $data['types'] = DB::table('article_types')
            ->where ('status','=',1)
            ->orderBy('name')
            ->get();

            $code = $data['article']->article_type;
            $data['custs'] = DB::table('third_party')->where(function ($query) use ($code) {
                // $code == 'FG' ? $query->where('third_party_type','cust') : '';
                // $code != 'FG' && $code != 'RM' ? $query->where('third_party_type','supp') : '';
            })->get();

            $data['groups'] = DB::table('group_materials')
            ->where ('status','=',1)
            ->orderBy('name')
            ->get();

            $data['uoms'] = DB::table('uom')
            ->orderBy('name')
            ->get();

            // $data['articles']= DB::table('article') 
            // ->orderBy('article_desc')
            // ->distinct('article_desc')
            // ->pluck('article_desc');

            $data['suppliers']= DB::table('article_supplier') 
            ->where('article_code',$data['article']->article_code)
            ->orderBy('id')
            ->pluck('supplier_code')->toArray();

            return view('articles.edit',$data);
            
        }

        public function show(Request $request)
        {
            $id=Crypt::decryptString($request->id);
            $data['title'] = "Detail $this->title";
            $data['subtitle'] = "Detail $this->title";
            
            $data['article'] = DB::table('article')
            ->where('id',$id)
            ->get(['article_code','costprice','article_alternative_code as code','article_desc as desc','uom','quality','note','id','group_of_material as group','brand', 'third_party as cust','quality','status','article_type','imgfile','color_code','variant','safety_stock','min_package','orderable','marketing'])->first();

            // $data['images'] = DB::table('images')
            // ->where('key',$data['article']->article_code)
            // ->get();

            $data['types'] = DB::table('article_types')
            ->where ('status','=',1)
            ->orderBy('name')
            ->get();

            // $data['article']->article_type  == 'FG' || $data['article']->article_type  == 'RM'  ? $typeTP = 'cust' : $typeTP = 'supp';

            $data['custs'] = DB::table('third_party')
            // ->where ('third_party_type','=',$typeTP)
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
            $sapetiStok = $request->safetyStock;
            $safetyStock = $sapetiStok ? str_replace(",","",$sapetiStok) : $sapetiStok;
            $minimumPackage = $request->minimumPackage ? str_replace(",","",$request->minimumPackage) : $request->minimumPackage;
            $note = $request->note;
            $files = $request->files;
            $fileDihapus = $request->fileDihapus;
            $status = $request->status == 'on' ? '1' : '0';
            $pesan = '';
            $colorCode = $request->colorCode;
            $variant = $request->variant;
            $brand = $request->brand;
            $orderable = $request->orderableCheck == 'on' ? '1' : '0';
            $marketing = $request->marketingCheck == 'on' ? '1' : '0'; // tambahkan ini

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
                    $rowAffected=DB::table('article')
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
                            'brand' => $brand,
                            'orderable' =>$orderable,
                            'marketing' =>$marketing
                        ]
                    );
                    
                    $dataset=[];
                    foreach ($cust as $val) {
                        $dataSet[] = [
                            $artCode.$val
                        ];
                    }

                    $getArticleCode = db::table('article')->where('id',$id)->value('article_code');

                    /*Update di BOM untuk main customer nya di update sesuai dengan di article*/
                    DB::table('bom_hdr')
                    ->where('article_code',$getArticleCode)
                    ->update(
                    [ 
                        'customer' => $cust[0]
                    ]); 
                        
                    /*
                    Delete kalo article tidak ada di po $poNumber dan article nya $val->article_code
                    berdasarkan 2 kondisi
                    */
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

                    if($rowAffected>0){
                        DB::commit();
                        $title ="Update $this->title";
                        $alert  ="success";
                        $message  = "$this->title $articleAltCode is successfully updated";
                        \LogActivity::addToLog($title,"username: $username Status $message");
                        // return redirect()->back()->with(['status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'articleCode'=>$articleAltCode]);
                        return redirect()->route('articles.index')->with(array('title' => $title, 'message' => $message,'alert'=>$alert,'articleCode'=>$articleAltCode));
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
                $rowAffected=DB::table('article')
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
                $rowAffected = DB::table('article')
                ->where('id',$id)
                ->delete();
            }

            if($rowAffected>0){
                $title ="$statusDelete $this->title";
                $alert  ="success";
                $message  = "$this->title $articleAltCode $artCode is successfully $statusDelete";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with(['status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'articleCode'=>$articleAltCode]);
            }else{
                $title ="$statusDelete $this->title";
                $alert  ="warning";
                $message  = "$this->title $articleAltCode $artCode is failed to $statusDelete";
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
    $statusFilter = $request->statusFilter;

    $data = DB::table('article')
    ->select(
        'article.*',
        'article.article_code as art_code',
        'article_alternative_code as code',
        'article_desc as desc',
        'brand',
        'article.uom',
        'quality',
        'note',
        'article.id',
        'group_materials.name as group',
        'third_party.nama as cust',
        'safety_stock',
        'min_package',
    )
    ->leftJoin('group_materials', 'group_materials.code', '=', 'article.group_of_material')
    ->leftJoin('third_party', 'third_party.kode', '=', 'article.third_party')
    ->leftJoin('uom', 'uom.code', '=', 'article.uom')
    ->where(function ($query) use ($code,$name,$group,$cust,$supp,$type) {
        $code  ? $query->where('article_alternative_code','ilike','%'.$code.'%') : '';
        $name  ? $query->where('article_desc','ilike','%'.$name.'%') : '';
        $group ? $query->where('group_of_material','ilike','%'.$group.'%') : '';
        $cust  ? $query->where('third_party','ilike','%'.$cust.'%') : '';
        $supp  ? $query->where('third_party','ilike','%'.$supp.'%') : '';
        $type  ? $query->where('article_alternative_code','ilike',$type.'%') : '';
    })
    ->when($statusFilter !== '' && $statusFilter !== null, function($query) use ($statusFilter) {
        $query->where('article.status', $statusFilter);
    })
    ->orderBy('article.updated_at', 'desc'); // <-- tambahan: terbaru diupdate di paling atas

    $bisaEdit = Auth::user()->can('article-edit');
    $bisaDelete = Auth::user()->can('article-delete');

    return Datatables::of($data)
    ->addColumn('action', function ($data) use ($bisaEdit,$bisaDelete) {
        $buttons = '<div class="d-inline-flex">
                        <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                            <i data-feather="menu"></i>
                        </a>';
        $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';

        if ($bisaEdit) {
            $buttons .=         '<a href="'. route('article.edit',  ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                <i data-feather="file-text"></i>
                                Edit
                            </a>';
        }
        $buttons .=         '<a href="'. route('article.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                <i data-feather="list"></i>
                                Detail
                            </a>';
        $buttons .= '<a href="javascript:;"
                class="dropdown-item btn-print-label"
                data-id="' . Crypt::encryptString($data->id) . '"
                data-code="' . e($data->article_alternative_code) . '"
                data-desc="' . e($data->article_desc) . '">
                <i data-feather="printer"></i>
                Print Label
            </a>';
        if ($bisaDelete) {
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

    ->addColumn('status', function ($data) {
        $badges=['badge-light-danger','badge-light-primary'];
        $statusCode = ['Freeze','Active'];
        return "<div class='badge badge-pill ".$badges[$data->status]."'>".$statusCode[$data->status]."</div>";
    })
    
    ->rawColumns(['action','status'])
    ->make(true);
}

public function printLabel(Request $request)
{
    $id  = Crypt::decryptString($request->id);
    $qty = (int) $request->qty;

    if ($qty < 1 || $qty > 100) {
        return response()->json(['status' => 0, 'message' => 'Jumlah label harus antara 1-100.']);
    }

    $article = DB::table('article')
        ->where('id', $id)
        ->select('article_code', 'article_alternative_code', 'article_desc', 'barcode_path', 'uom')
        ->first();

    if (!$article) {
        return response()->json(['status' => 0, 'message' => 'Artikel tidak ditemukan.']);
    }

    if (empty($article->barcode_path) || !\Storage::disk('public')->exists($article->barcode_path)) {
        return response()->json(['status' => 0, 'message' => 'QR Code belum digenerate untuk artikel ini. Silakan generate QR terlebih dahulu.']);
    }

    // Konversi QR PNG ke base64 untuk ZPL
    $qrAbsPath  = storage_path('app/public/' . $article->barcode_path);
    $qrBase64   = base64_encode(file_get_contents($qrAbsPath));
    $qrUrl      = asset('storage/' . $article->barcode_path);
    $printedBy  = Auth::user()->username;
    $printedAt  = now()->format('d/m/Y H:i');

    // Build ZPL untuk 30x20mm @ 203 DPI
    // 30mm = 240 dots, 20mm = 160 dots
    // QR native ZPL (lebih tajam dari PNG embed)
    $altCode = $article->article_alternative_code;
    $desc    = mb_substr($article->article_desc, 0, 40); // max 40 char
    $footer  = mb_substr("Dicetak: {$printedBy} {$printedAt}", 0, 50);

    // ZPL template (^BQR = native QR code Zebra, tajam di 203 DPI)
    $zpl = "^XA
^MMT
^PW240
^LL160
^LS0
^CI28
^FO8,4^BQN,2,3^FDQA,{$altCode}^FS
^FO76,6^A0N,14,13^FD{$altCode}^FS
^FO76,24^A0N,10,9^FB155,3,0,L^FD{$desc}^FS
^FO4,142^A0N,8,7^FD{$footer}^FS
^FO4,140^GB232,0,1^FS
^PQ{$qty},0,1,Y
^XZ";

    return response()->json([
        'status'      => 1,
        'article'     => $article,
        'qr_url'      => $qrUrl,
        'qr_base64'   => $qrBase64,
        'zpl'         => $zpl,
        'printed_by'  => $printedBy,
        'printed_at'  => $printedAt,
        'qty'         => $qty,
    ]);
}

public function printLabelNetwork(Request $request)
{
    $zpl  = $request->zpl;
    $ip   = $request->ip;
    $port = (int) ($request->port ?? 9100);

    if (empty($zpl) || empty($ip)) {
        return response()->json(['status' => 0, 'message' => 'ZPL atau IP kosong.']);
    }

    // Validasi IP format
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return response()->json(['status' => 0, 'message' => 'Format IP tidak valid.']);
    }

    try {
        $socket = @fsockopen($ip, $port, $errno, $errstr, 5); // timeout 5 detik
        if (!$socket) {
            return response()->json([
                'status'  => 0,
                'message' => "Tidak bisa terhubung ke {$ip}:{$port} — {$errstr} (#{$errno})"
            ]);
        }
        fwrite($socket, $zpl);
        fclose($socket);

        return response()->json(['status' => 1, 'message' => 'ZPL berhasil dikirim.']);
    } catch (\Exception $e) {
        return response()->json(['status' => 0, 'message' => $e->getMessage()]);
    }
}

        public function getSupplierOld(Request $request)
        {
            $code = $request->type;
            $dependent=$request->dependent;

            $data = DB::table('third_party')->where(function ($query) use ($code) {
                //kalo barang finish goods hanya punya nya customer, tapi kalo raw material yang punyanya bisa customer bisa supplier
                // $code == 'FG' ? $query->where('third_party_type','cust') : $query->where('third_party_type','supp');  //tadinya ini
                // $code == 'FG' ? $query->where('third_party_type','cust') : '';
                // $code != 'FG' ? $query->where('third_party_type','supp') : '';

            })->get();
            
            $output='';
            $output .= $code == 'FG'?'<option value=""></option>':'<option value=""></option>';

            foreach ($data as $row){
                $output .="<option value='$row->kode'>$row->kode - $row->nama</option>";
            }        

            return $output;
        }

        public function getSupplier(Request $request)
    {
        $code = $request->type;
        $dependent = $request->dependent;

        $data = DB::table('third_party')->where(function ($query) use ($code) {
            // FG hanya boleh pilih Customer, selain FG hanya boleh pilih Supplier
            if ($code == 'FG') {
                $query->where('third_party_type', 'cust');
            } else {
                $query->where('third_party_type', 'supp');
            }
        })->orderBy('nama')->get();

        $placeholder = $code == 'FG' ? 'Choose Customer' : 'Choose Supplier';

        $output = '<option value="">'.$placeholder.'</option>';

        foreach ($data as $row) {
            $output .= "<option value='$row->kode'>$row->kode - $row->nama</option>";
        }

        return $output;
    }

        public function movement(Request $request){
            
            $articleCode = $request->articleCode;
            $location = 'WH';
            $siteCode = 'HO';

            /* 
                update 15/12/2025
                query baru untuk movement  balance qty ambil dari perhitungan movement nya langsung

            */
            $sqlku = "SELECT 
                    m.movement_code,
                    m.artikel_code,
                    m.artikel_desc,
                    m.movement_plus - m.movement_min as qty,
                    m.movement_price,
                    m.movement_date,
                    m.movement_desc,
                    m.movement_type,
                    m.movement_min,
                    m.movement_plus,
                    m.movement_transnno,
                    SUM(-movement_min+movement_plus) OVER (ORDER BY TO_DATE(movement_date,'dd-mm-yyyy'), m.movement_code) as balanceqty,
                    ROW_NUMBER() OVER (ORDER BY TO_DATE(movement_date,'dd-mm-yyyy') DESC, m.movement_code DESC) as urutan,
                    SUM(-movement_min+movement_plus) OVER (ORDER BY TO_DATE(movement_date,'dd-mm-yyyy'), m.movement_code) as last_qty,
                    m.site_code,
                    m.location_number,
                    -- m.last_qty,
                    m.created_at
                FROM movement m
                WHERE m.artikel_code = '$articleCode'
                and m.site_code = '$siteCode'
                and m.location_number = '$location'
                ORDER BY TO_DATE(movement_date,'dd-mm-yyyy'), m.movement_code";

            /*
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
                        ,last_qty
                        ,created_at
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
                    ,last_qty
                    ,created_at
                    from movement
                    where artikel_code='$articleCode'
                    and site_code = '$siteCode'
                    and location_number = '$location'
                    ) t
                    order by movement_code");
            */


            $data = DB::select($sqlku);
            return Datatables::of($data)
            ->addColumn('qty', function ($data) {
                // $artilceQty = $data->uom_group =='PIECE' ? number_format($data->article_qty) : number_format($data->article_qty,3);
                if (fmod($data->qty,1) !== 0.00){
                    $decimal = $this->decimalPlaces;
                }else{
                    $decimal = 0;
                }
                $qty = number_format($data->qty,$decimal);
                return $data->qty < 0 ? "<div class='text-red'>$qty</div>" : "<div class='text-hijau'>$qty</div>";
            })
            ->addColumn('balanceqty', function ($data) {
                // $artilceQty = $data->uom_group =='PIECE' ? number_format($data->article_qty) : number_format($data->article_qty,3);
                if (fmod($data->balanceqty,1) !== 0.00){
                    $decimal = $this->decimalPlaces;
                }else{
                    $decimal = 0;
                }
                $balanceQty = number_format($data->balanceqty,$decimal);
                return $data->balanceqty < 0 ? "<div class='text-red'>$balanceQty</div>" : "<div class='text-hitam'>$balanceQty</div>";
            })
            ->rawColumns(['qty','balanceqty'])
            ->make(true);
        }

        private const RETURN_LOCS = ['049', '042', '009', '006', '005'];

       public function movement2(Request $request)
{
    $articleCode = $request->articleCode;
    $location    = $request->location;
    $siteCode    = 'HO';
    $fromDate    = $request->fromDate ?: date('01-m-Y');
    $toDate      = $request->toDate   ?: date('d-m-Y');
    $inout       = $request->inout;
    $isGlobal    = empty($location);

    // Saldo awal = carry-forward dari periode sebelumnya, Juni 2026 dikecualikan
    $opening   = $this->resolveOpeningBalance($articleCode, $location, $fromDate, $isGlobal);
    $saldoAwal = $opening['qty'];

    $bind = ['art' => $articleCode, 'site' => $siteCode, 'from' => $fromDate, 'to' => $toDate,
             'art_dir' => $articleCode, 'art_qty' => $articleCode];

    $whereLoc = '';
    if (!$isGlobal) { $whereLoc = "AND m.location_number = :loc"; $bind['loc'] = $location; }
    $locationCol = $isGlobal ? "'ALL'" : "b.location_number";

    $inoutFilter = '';
    if ($inout === 'in')  $inoutFilter = "AND (b.movement_plus > 0 OR b.adj_direction = '+')";
    if ($inout === 'out') $inoutFilter = "AND (b.movement_min  > 0 OR b.adj_direction = '-')";

    $sqlku = "
    WITH ledger AS (
        SELECT m.*,
            -- status header dokumen (dipakai untuk: exclude cancel=5, hide NEW=1, badge status)
            CASE m.movement_type
                WHEN 'RECEIVING'    THEN (SELECT status FROM receiving_hdr        WHERE rec_number      = m.movement_transnno LIMIT 1)
                WHEN 'TRANSFER'     THEN (SELECT status FROM transfer_stock_hdr   WHERE tr_number       = m.movement_transnno LIMIT 1)
                WHEN 'SUPPLY'       THEN (SELECT status FROM transfer_stock_hdr   WHERE tr_number       = m.movement_transnno LIMIT 1)
                WHEN 'DELIVERY'     THEN (SELECT status FROM delivery_hdr         WHERE delivery_number = m.movement_transnno LIMIT 1)
                WHEN 'RETURN'       THEN (SELECT status FROM dn_return_hdr        WHERE return_number   = m.movement_transnno LIMIT 1)
                WHEN 'REPLACEMENT'  THEN (SELECT status FROM dn_replace_hdr       WHERE replace_number  = m.movement_transnno LIMIT 1)
                WHEN 'ADJUSTMENT'   THEN (SELECT status FROM stock_adjustment_hdr WHERE adj_code        = m.movement_transnno LIMIT 1)
                WHEN 'DN SEMENTARA' THEN (SELECT status FROM temporary_dn_hdr     WHERE tdn_number      = m.movement_transnno LIMIT 1)
                WHEN 'DN UMUM'      THEN (SELECT status FROM dn_general_hdr        WHERE tdn_number      = m.movement_transnno LIMIT 1)
                ELSE NULL
            END AS hdr_status,

            (CASE WHEN m.movement_type IN ('ADJUSTMENT','CANCEL ADJUSTMENT') THEN
                (SELECT det.direction FROM stock_adjustment_det det
                 WHERE det.adj_code = m.movement_transnno AND det.article_code = :art_dir LIMIT 1)
            END) AS adj_direction,
            (CASE WHEN m.movement_type IN ('ADJUSTMENT','CANCEL ADJUSTMENT') THEN
                (SELECT det.qty_adjustment FROM stock_adjustment_det det
                 WHERE det.adj_code = m.movement_transnno AND det.article_code = :art_qty LIMIT 1)
            END) AS adj_qty,

            -- net adj-aware (Bug #1): plus=min=0 & adjustment -> ambil dari det
            CASE
                WHEN m.movement_type IN ('ADJUSTMENT','CANCEL ADJUSTMENT')
                     AND m.movement_plus = 0 AND m.movement_min = 0
                THEN (SELECT CASE WHEN det.direction = '-' THEN -det.qty_adjustment ELSE det.qty_adjustment END
                      FROM stock_adjustment_det det
                      WHERE det.adj_code = m.movement_transnno AND det.article_code = m.artikel_code LIMIT 1)
                     * CASE WHEN m.movement_type = 'CANCEL ADJUSTMENT' THEN -1 ELSE 1 END
                ELSE (m.movement_plus - m.movement_min)
            END AS net_value
        FROM warehouse_movement m
        WHERE m.artikel_code = :art
          AND m.site_code = :site
          $whereLoc
          AND TO_DATE(m.movement_date,'dd-mm-yyyy')
              BETWEEN TO_DATE(:from,'dd-mm-yyyy') AND TO_DATE(:to,'dd-mm-yyyy')
          AND m.movement_type NOT LIKE 'CANCEL %'
          AND m.movement_type NOT LIKE 'DELETE%'
          AND m.movement_type NOT LIKE 'REVISI %'
          AND m.movement_type NOT IN ('RETURN-CANCEL','RETURN-REVERSE')
          AND NOT (m.movement_type IN ('ADJUSTMENT','CANCEL ADJUSTMENT')
                   AND EXISTS (SELECT 1 FROM stock_adjustment_hdr h
                               WHERE h.adj_code = m.movement_transnno AND h.adj_type = 'OPENING BALANCE'))
    ),
    dedup AS (
        SELECT l.*,
            ROW_NUMBER() OVER (
                PARTITION BY l.artikel_code, l.movement_transnno, l.location_number
                ORDER BY l.created_at DESC, l.movement_code DESC
            ) AS rn
        FROM ledger l
        WHERE l.hdr_status IS DISTINCT FROM '5'      -- CANCEL: buang semua baris dokumen status 5
    ),
    kept AS (
        SELECT d.*,
            d.net_value AS counted_net
        FROM dedup d
        WHERE d.rn = 1                                -- REVISI: hanya baris terbaru
    ),
    base AS (
        SELECT k.*,
            $saldoAwal + SUM(k.counted_net) OVER (
                ORDER BY TO_DATE(k.movement_date,'dd-mm-yyyy'), k.movement_code
            ) AS balanceqty_calc,
            $saldoAwal + SUM(k.counted_net) OVER (
                ORDER BY TO_DATE(k.movement_date,'dd-mm-yyyy'), k.movement_code
            ) - k.counted_net AS last_qty_calc
        FROM kept k
    )
    SELECT
        b.movement_code, b.artikel_code, b.artikel_desc,
        b.movement_plus - b.movement_min AS qty,
        b.movement_price, b.movement_date, b.movement_desc, b.movement_type,
        b.movement_min, b.movement_plus, b.movement_transnno, b.partner_type,
        b.adj_direction, b.adj_qty, b.hdr_status,
        b.movement_to AS dest_code,
        $locationCol AS location_number,
        CASE
            WHEN b.partner_type = 'SUPP' THEN (SELECT nama FROM third_party WHERE kode = b.movement_from)
            ELSE (SELECT location_name FROM stock_location_master WHERE location_code = b.movement_from)
        END AS mv_from,
        CASE
            WHEN b.partner_type = 'CUST' THEN (SELECT nama FROM third_party WHERE kode = b.movement_to)
            ELSE (SELECT location_name FROM stock_location_master WHERE location_code = b.movement_to)
        END AS mv_to,
        b.balanceqty_calc AS balanceqty,
        b.last_qty_calc   AS last_qty,
        ROW_NUMBER() OVER (ORDER BY TO_DATE(b.movement_date,'dd-mm-yyyy') DESC, b.movement_code DESC) AS urutan,
        b.site_code, b.created_at,
        b.hdr_status AS trx_status
    FROM base b
    WHERE 1=1
    $inoutFilter
    ORDER BY TO_DATE(b.movement_date,'dd-mm-yyyy'), b.movement_code";

    $data = DB::select($sqlku, $bind);

    $artikelDesc = $data[0]->artikel_desc ?? null;
    if (!$artikelDesc) {
        $row = DB::table('article')->where('article_code', $articleCode)
            ->selectRaw("concat(article_alternative_code,'-',article_desc) as desc")->first();
        $artikelDesc = $row->desc ?? $articleCode;
    }

    $lastRow    = end($data); reset($data);
    $saldoAkhir = $lastRow ? (float) $lastRow->balanceqty : $saldoAwal;

    $totalIn = 0.0; $totalOut = 0.0;
    foreach ($data as $d) {
        [$in, $out] = $this->splitQty($d);   // NEW otomatis 0 (lihat splitQty)
        $totalIn  += $in;
        $totalOut += $out;
    }

    $rowAwal = $this->buildSummaryRow([
        'artikel_code'    => $articleCode,
        'artikel_desc'    => $artikelDesc,
        'movement_desc'   => $opening['note'] ?: 'Saldo Awal',
        'movement_type'   => 'OPENING',
        'location_number' => $isGlobal ? 'ALL' : $location,
        'balanceqty'      => $saldoAwal,
        'urutan'          => 999999999,
        'adj_code'        => $opening['adj_code'],
        'adj_id'          => $opening['adj_id'],
        'created_at'      => $opening['authorized_at'],
        'site_code'       => $siteCode,
    ]);

    $rowAkhir = $this->buildSummaryRow([
        'artikel_code'    => $articleCode,
        'artikel_desc'    => $artikelDesc,
        'movement_desc'   => 'Saldo Akhir ('.$toDate.')',
        'movement_type'   => 'CLOSING',
        'location_number' => $isGlobal ? 'ALL' : $location,
        'last_qty'        => $saldoAwal,
        'movement_plus'   => $totalIn,
        'movement_min'    => $totalOut,
        'balanceqty'      => $saldoAkhir,
        'urutan'          => -1,
        'site_code'       => $siteCode,
    ]);

    $dataFinal = array_merge([$rowAwal], $data, [$rowAkhir]);

    return Datatables::of($dataFinal)
        ->addColumn('qty_in', function ($d) {
            if (!empty($d->is_summary)) {
                if ($d->movement_type !== 'CLOSING') return '';
                return "<div class='mv-balance text-hijau'>".number_format((float) $d->movement_plus, 2)."</div>";
            }
            [$in, ] = $this->splitQty($d);
            $v = number_format($in, 2);
            return $in > 0 ? "<div class='text-hijau'>$v</div>" : "<div class='text-muted'>$v</div>";
        })
        ->addColumn('qty_out', function ($d) {
            if (!empty($d->is_summary)) {
                if ($d->movement_type !== 'CLOSING') return '';
                return "<div class='mv-balance text-red'>".number_format((float) $d->movement_min, 2)."</div>";
            }
            [, $out] = $this->splitQty($d);
            $v = number_format($out, 2);
            return $out > 0 ? "<div class='text-red'>$v</div>" : "<div class='text-muted'>$v</div>";
        })
        ->addColumn('last_qty', function ($d) {
            if (!empty($d->is_summary)) {
                if ($d->movement_type !== 'CLOSING') return '';
                return "<div class='mv-balance'>".number_format((float) $d->last_qty, 2)."</div>";
            }
            if ($d->last_qty === null) return '';
            return "<div class='text-hitam'>".number_format((float) $d->last_qty, 2)."</div>";
        })
        ->addColumn('balanceqty', function ($d) {
            $v = number_format((float) $d->balanceqty, 2);
            if (!empty($d->is_summary)) return "<div class='mv-balance'>$v</div>";
            return $d->balanceqty < 0 ? "<div class='text-red'>$v</div>" : "<div class='text-hitam'>$v</div>";
        })
        ->addColumn('movement_transnno', function ($d) {
            if (!empty($d->is_summary)) {
                if (empty($d->adj_code) || empty($d->adj_id)) return '-';
                $url = route('stockAdjustment.show', ['id' => Crypt::encryptString($d->adj_id)]);
                return "<a href='$url' target='_blank' class='badge badge-light-primary'>"
                     . "<i data-feather='external-link' class='font-small-1'></i> ".e($d->adj_code)."</a>";
            }
            return $this->renderRefLink($d->movement_type, $d->movement_transnno);
        })
        ->addColumn('inout', function ($d) {
    if (!empty($d->is_summary)) return '';
    [$in, $out] = $this->splitQty($d);
    if ($in  > 0) return "<span class='badge badge-pill badge-light-success'><i data-feather='arrow-down-circle' class='font-small-3'></i> IN</span>";
    if ($out > 0) return "<span class='badge badge-pill badge-light-danger'><i data-feather='arrow-up-circle' class='font-small-3'></i> OUT</span>";
    return "<span class='badge badge-pill badge-light-secondary'>-</span>";
})

->editColumn('movement_type', function ($d) {
    if (!empty($d->is_summary)) return '';
    if (in_array($d->movement_type, ['TRANSFER','SUPPLY'], true)) {
        if ((float) ($d->movement_min ?? 0) > 0) return 'SUPPLY';
        $dest = (string) ($d->dest_code ?? '');
        return in_array($dest, self::RETURN_LOCS, true) ? 'RETURN' : 'TRANSFER';
    }
    if ($d->movement_type === 'RETURN')      return 'DN RETURN';
    if ($d->movement_type === 'REPLACEMENT') return 'DN REPLACEMENT';
    return $d->movement_type;
})
        ->addColumn('trx_status', function ($d) {
            if (!empty($d->is_summary)) return '';
            $st = $d->trx_status;
            if ($st === null || $st === '') return "<span class='badge badge-pill badge-light-secondary'>-</span>";
            $map = [
                '1'=>['NEW','badge-light-primary'], '2'=>['VALIDATED','badge-light-info'],
                '3'=>['APPROVED','badge-light-warning'], '4'=>['POSTED','badge-light-success'],
                '5'=>['CANCELED','badge-light-danger'], '7'=>['REVISED','badge-light-warning'],
                '10'=>['REVISED','badge-light-warning'],
            ];
            [$label, $cls] = $map[$st] ?? [strtoupper($st), 'badge-light-secondary'];
            return "<span class='badge badge-pill $cls'>$label</span>";
        })
        ->addColumn('summary_label', function ($d) {
            if (empty($d->is_summary)) return '';
            $isOpen = $d->movement_type === 'OPENING';
            $icon   = $isOpen ? 'log-in' : 'flag';
            $text   = $isOpen ? 'SALDO AWAL' : 'SALDO AKHIR';
            return "<span class='mv-summary-badge'><i data-feather='$icon'></i>$text</span>";
        })
        ->rawColumns(['qty_in','qty_out','last_qty','balanceqty','movement_transnno','inout','trx_status','summary_label','movement_type'])
        ->make(true);
}

/** movement_type => [tabel, kolom_nomor, route_show] */
private array $refMap = [
    'RECEIVING'         => ['receiving_hdr',      'rec_number',      'receiving.show'],
    'TRANSFER'          => ['transfer_stock_hdr', 'tr_number',       'transferStock.show'],
    'SUPPLY'            => ['transfer_stock_hdr', 'tr_number',       'transferStock.show'],
    'DELIVERY'          => ['delivery_hdr',       'delivery_number', 'delivery.show'],
    'RETURN'            => ['dn_return_hdr',      'return_number',   'dnReturn.show'],
    'REPLACEMENT'       => ['dn_replace_hdr',     'replace_number',  'dnReplace.show'],
    'ADJUSTMENT'        => ['stock_adjustment_hdr','adj_code',       'stockAdjustment.show'],
    'DN SEMENTARA'      => ['temporary_dn_hdr',   'tdn_number',      'temporaryDn.show'],
    'DN UMUM'           => ['dn_general_hdr',     'tdn_number',      'dnGeneral.show'],
];

private function renderRefLink($type, $ref)
{
    if (!$ref || !isset($this->refMap[$type])) {
        return $ref ? '<span class="text-muted">'.e($ref).'</span>' : '-';
    }
    [$table, $col, $routeName] = $this->refMap[$type];
    $row = DB::table($table)->where($col, $ref)->select('id','status')->first();

    if (!$row || (string) $row->status === '5') { // 5 = CANCELED
        return '<span class="text-muted" title="Tidak bisa dibuka">'.e($ref).'</span>';
    }
    $url = route($routeName, ['id' => Crypt::encryptString($row->id)]);
    return '<a href="'.$url.'" target="_blank" class="text-primary">'.e($ref).'</a>';
}

private function resolveOpeningBalance($articleCode, $location, $fromDate, $isGlobal, $depth = 0)
{
    // Safety: batas rekursi maksimal / floor tanggal
    $floorDate = '30-06-2026'; // atau tanggal go-live sistem
    if ($depth > 36 || strtotime($fromDate) <= strtotime($floorDate)) {
        return ['qty' => 0.0, 'adj_code' => null, 'adj_id' => null,
                'note' => 'Saldo awal diasumsikan 0 (di luar rentang data)', 'authorized_at' => null];
    }

    $out = ['qty'=>0.0,'adj_code'=>null,'adj_id'=>null,'note'=>null,'authorized_at'=>null];

    // Saldo awal = saldo akhir SEHARI sebelum fromDate.
    // = OB periode (bulan fromDate - 1)  +  net movement (01-<bulan fromDate> .. fromDate-1)
    $parts = explode('-', $fromDate);
    $bulan = isset($parts[1]) ? (int) $parts[1] : (int) date('m');
    $tahun = isset($parts[2]) ? (int) $parts[2] : (int) date('Y');

    $periodeOB = $bulan - 1;
    $tahunOB   = $tahun;
    if ($periodeOB < 1) { $periodeOB = 12; $tahunOB = $tahun - 1; }

    // 1) Basis = OB periode (bulan-1). Kalau tidak ada, mundur rekursif ke saldo akhir bulan sebelumnya.
  $ob = $this->fetchOBByPeriode($articleCode, $location, $periodeOB, $tahunOB, $isGlobal);
if ($ob['found']) {
    $basis = $ob['qty'];
    $out['adj_code']      = $ob['adj_code'];
    $out['adj_id']        = $ob['adj_id'];
    $out['authorized_at'] = $ob['authorized_at'];
} elseif (strtotime(sprintf('01-%02d-%04d', $periodeOB, $tahunOB)) <= strtotime($floorDate)) {
    // Sebelum floor (Juni) dan OB Juni tidak ada -> dianggap 0, TIDAK rekursi, TIDAK tambah net bulan sebelumnya
    $basis = 0.0;
    $out['note'] = 'OB Juni tidak ditemukan, saldo sebelum periode floor diabaikan';
} else {
    // Masih di atas floor, boleh rekursi normal seperti biasa
    $awalPrev = $this->resolveOpeningBalance(
        $articleCode, $location,
        sprintf('01-%02d-%04d', $periodeOB, $tahunOB),
        $isGlobal,
        $depth + 1
    );
    $basis = $awalPrev['qty']
           + $this->netMovementRange($articleCode, $location,
               sprintf('01-%02d-%04d', $periodeOB, $tahunOB),
               date('t-m-Y', mktime(0,0,0,$periodeOB,1,$tahunOB)),
               $isGlobal);
}

    // 2) Tambah net movement dari AWAL bulan fromDate s/d SEHARI sebelum fromDate.
    //    Kalau fromDate = tanggal 1, rentang ini kosong → net 0.
    $awalBulan = sprintf('01-%02d-%04d', $bulan, $tahun);
    $prevDay   = date('d-m-Y', strtotime(
        \DateTime::createFromFormat('d-m-Y', $fromDate)->format('Y-m-d') . ' -1 day'
    ));

    $netParsial = 0.0;
    // hanya kalau fromDate bukan tanggal 1 (ada hari yang perlu diakumulasi)
    if ((int)$parts[0] > 1) {
        $netParsial = $this->netMovementRange($articleCode, $location, $awalBulan, $prevDay, $isGlobal);
    }

    $out['qty']  = $basis + $netParsial;
    $out['note'] = $ob['found']
        ? ($netParsial != 0.0 ? 'OB + mutasi awal bulan' : ($ob['note'] ?: 'Opening balance'))
        : 'Saldo akhir periode sebelumnya';

    if ($netParsial != 0.0 || !$ob['found']) {
    $out['adj_code'] = null;
    $out['adj_id']   = null;
}

    return $out;
}

private function netMovementRange($articleCode, $location, $from, $to, $isGlobal): float
{
    $whereLoc = $isGlobal ? '' : 'AND m.location_number = :loc2';
    $sql = "
      WITH acc AS (
        SELECT m.movement_code, m.created_at,
            CASE m.movement_type
                WHEN 'RECEIVING'    THEN (SELECT status FROM receiving_hdr        WHERE rec_number      = m.movement_transnno LIMIT 1)
                WHEN 'TRANSFER'     THEN (SELECT status FROM transfer_stock_hdr   WHERE tr_number       = m.movement_transnno LIMIT 1)
                WHEN 'SUPPLY'       THEN (SELECT status FROM transfer_stock_hdr   WHERE tr_number       = m.movement_transnno LIMIT 1)
                WHEN 'DELIVERY'     THEN (SELECT status FROM delivery_hdr         WHERE delivery_number = m.movement_transnno LIMIT 1)
                WHEN 'RETURN'       THEN (SELECT status FROM dn_return_hdr        WHERE return_number   = m.movement_transnno LIMIT 1)
                WHEN 'REPLACEMENT'  THEN (SELECT status FROM dn_replace_hdr       WHERE replace_number  = m.movement_transnno LIMIT 1)
                WHEN 'ADJUSTMENT'   THEN (SELECT status FROM stock_adjustment_hdr WHERE adj_code        = m.movement_transnno LIMIT 1)
                WHEN 'DN SEMENTARA' THEN (SELECT status FROM temporary_dn_hdr     WHERE tdn_number      = m.movement_transnno LIMIT 1)
                WHEN 'DN UMUM'      THEN (SELECT status FROM dn_general_hdr        WHERE tdn_number      = m.movement_transnno LIMIT 1)
                ELSE NULL
            END AS hdr_status,
            CASE
                WHEN m.movement_type IN ('ADJUSTMENT','CANCEL ADJUSTMENT')
                     AND m.movement_plus=0 AND m.movement_min=0
                THEN (SELECT CASE WHEN det.direction='-' THEN -det.qty_adjustment ELSE det.qty_adjustment END
                      FROM stock_adjustment_det det
                      WHERE det.adj_code=m.movement_transnno AND det.article_code=m.artikel_code LIMIT 1)
                     * CASE WHEN m.movement_type='CANCEL ADJUSTMENT' THEN -1 ELSE 1 END
                ELSE (m.movement_plus - m.movement_min)
            END AS net_value,
            ROW_NUMBER() OVER (
                PARTITION BY m.artikel_code, m.movement_transnno, m.location_number
                ORDER BY m.created_at DESC, m.movement_code DESC
            ) AS rn
        FROM warehouse_movement m
        WHERE m.artikel_code = :art AND m.site_code='HO'
          $whereLoc
          AND TO_DATE(m.movement_date,'dd-mm-yyyy') BETWEEN TO_DATE(:from,'dd-mm-yyyy') AND TO_DATE(:to,'dd-mm-yyyy')
          AND m.movement_type NOT LIKE 'CANCEL %'
          AND m.movement_type NOT LIKE 'DELETE%'
          AND m.movement_type NOT LIKE 'REVISI %'
          AND m.movement_type NOT IN ('RETURN-CANCEL','RETURN-REVERSE')
          AND NOT (m.movement_type IN ('ADJUSTMENT','CANCEL ADJUSTMENT')
                   AND EXISTS (SELECT 1 FROM stock_adjustment_hdr h
                               WHERE h.adj_code=m.movement_transnno AND h.adj_type='OPENING BALANCE'))
      )
      SELECT COALESCE(SUM(net_value),0) AS net
      FROM acc WHERE rn=1 AND hdr_status IS DISTINCT FROM '5'";
    $bind = ['art'=>$articleCode,'from'=>$from,'to'=>$to];
    if (!$isGlobal) $bind['loc2'] = $location;
    $r = DB::select($sql, $bind);
    return isset($r[0]) ? (float) $r[0]->net : 0.0;
}

private function netMovementBulan($articleCode, $location, $periode, $tahun, $isGlobal): float
{
    $from = sprintf('01-%02d-%04d', $periode, $tahun);
    $to   = date('t-m-Y', mktime(0,0,0,$periode,1,$tahun));  // hari terakhir bulan itu

    $whereLoc = $isGlobal ? '' : 'AND m.location_number = :loc2';
    $sql = "
      WITH acc AS (
        SELECT m.movement_code, m.created_at,
            CASE m.movement_type
                WHEN 'RECEIVING'    THEN (SELECT status FROM receiving_hdr        WHERE rec_number      = m.movement_transnno LIMIT 1)
                WHEN 'TRANSFER'     THEN (SELECT status FROM transfer_stock_hdr   WHERE tr_number       = m.movement_transnno LIMIT 1)
                WHEN 'SUPPLY'       THEN (SELECT status FROM transfer_stock_hdr   WHERE tr_number       = m.movement_transnno LIMIT 1)
                WHEN 'DELIVERY'     THEN (SELECT status FROM delivery_hdr         WHERE delivery_number = m.movement_transnno LIMIT 1)
                WHEN 'RETURN'       THEN (SELECT status FROM dn_return_hdr        WHERE return_number   = m.movement_transnno LIMIT 1)
                WHEN 'REPLACEMENT'  THEN (SELECT status FROM dn_replace_hdr       WHERE replace_number  = m.movement_transnno LIMIT 1)
                WHEN 'ADJUSTMENT'   THEN (SELECT status FROM stock_adjustment_hdr WHERE adj_code        = m.movement_transnno LIMIT 1)
                WHEN 'DN SEMENTARA' THEN (SELECT status FROM temporary_dn_hdr     WHERE tdn_number      = m.movement_transnno LIMIT 1)
                WHEN 'DN UMUM'      THEN (SELECT status FROM dn_general_hdr        WHERE tdn_number      = m.movement_transnno LIMIT 1)
                ELSE NULL
            END AS hdr_status,
            CASE
                WHEN m.movement_type IN ('ADJUSTMENT','CANCEL ADJUSTMENT')
                     AND m.movement_plus=0 AND m.movement_min=0
                THEN (SELECT CASE WHEN det.direction='-' THEN -det.qty_adjustment ELSE det.qty_adjustment END
                      FROM stock_adjustment_det det
                      WHERE det.adj_code=m.movement_transnno AND det.article_code=m.artikel_code LIMIT 1)
                     * CASE WHEN m.movement_type='CANCEL ADJUSTMENT' THEN -1 ELSE 1 END
                ELSE (m.movement_plus - m.movement_min)
            END AS net_value,
            ROW_NUMBER() OVER (
                PARTITION BY m.artikel_code, m.movement_transnno, m.location_number
                ORDER BY m.created_at DESC, m.movement_code DESC
            ) AS rn
        FROM warehouse_movement m
        WHERE m.artikel_code = :art AND m.site_code='HO'
          $whereLoc
          AND TO_DATE(m.movement_date,'dd-mm-yyyy') BETWEEN TO_DATE(:from,'dd-mm-yyyy') AND TO_DATE(:to,'dd-mm-yyyy')
          AND m.movement_type NOT LIKE 'CANCEL %'
          AND m.movement_type NOT LIKE 'DELETE%'
          AND m.movement_type NOT LIKE 'REVISI %'
          AND m.movement_type NOT IN ('RETURN-CANCEL','RETURN-REVERSE')
          AND NOT (m.movement_type IN ('ADJUSTMENT','CANCEL ADJUSTMENT')
                   AND EXISTS (SELECT 1 FROM stock_adjustment_hdr h
                               WHERE h.adj_code=m.movement_transnno AND h.adj_type='OPENING BALANCE'))
      )
      SELECT COALESCE(SUM(net_value),0) AS net
      FROM acc WHERE rn=1 AND hdr_status IS DISTINCT FROM '5' AND hdr_status IS DISTINCT FROM '1'";
    $bind = ['art'=>$articleCode,'from'=>$from,'to'=>$to];
    if (!$isGlobal) $bind['loc2'] = $location;
    $r = DB::select($sql, $bind);
    return isset($r[0]) ? (float) $r[0]->net : 0.0;
}

/** OB tepat pada periode+tahun tertentu. */
private function fetchOBByPeriode($articleCode, $location, $periode, $tahun, $isGlobal): array
{
    if ($isGlobal) {
        $sql = "SELECT COALESCE(SUM(det.stock_after),0) AS qty, MAX(hdr.authorized_at) AS authorized_at,
                       BOOL_OR(TRUE) AS found
                FROM stock_adjustment_hdr hdr
                JOIN stock_adjustment_det det ON det.adj_code = hdr.adj_code
                WHERE hdr.adj_type='OPENING BALANCE' AND hdr.status!='5'
                  AND hdr.periode = :periode
                  AND EXTRACT(YEAR FROM TO_DATE(hdr.adj_date,'dd-mm-yyyy')) = :tahun
                  AND det.article_code = :art";
        $r = DB::select($sql, ['periode'=>$periode,'tahun'=>$tahun,'art'=>$articleCode]);
        $found = isset($r[0]) && $r[0]->found;
        return ['found'=>(bool)$found,'qty'=>$found?(float)$r[0]->qty:0.0,
                'adj_code'=>null,'adj_id'=>null,'note'=>'Gabungan OB semua gudang',
                'authorized_at'=>$r[0]->authorized_at ?? null];
    }

    $sql = "SELECT hdr.id, hdr.adj_code, hdr.description, hdr.authorized_at, det.stock_after AS qty
            FROM stock_adjustment_hdr hdr
            JOIN stock_adjustment_det det ON det.adj_code = hdr.adj_code
            WHERE hdr.adj_type='OPENING BALANCE' AND hdr.status!='5'
              AND hdr.periode = :periode
              AND EXTRACT(YEAR FROM TO_DATE(hdr.adj_date,'dd-mm-yyyy')) = :tahun
              AND det.article_code = :art AND hdr.location_code = :loc
            LIMIT 1";
    $r = DB::select($sql, ['periode'=>$periode,'tahun'=>$tahun,'art'=>$articleCode,'loc'=>$location]);
    if (!isset($r[0])) return ['found'=>false];
    return ['found'=>true,'qty'=>(float)$r[0]->qty,'adj_code'=>$r[0]->adj_code,
            'adj_id'=>$r[0]->id,'note'=>$r[0]->description,'authorized_at'=>$r[0]->authorized_at];
}

/** OB terakhir yang efektif < fromDate dan >= floor (untuk fallback). */
private function fetchLatestOBBefore($articleCode, $location, $fromDate, $floor, $isGlobal): array
{
    if ($isGlobal) {
        $sql = "SELECT hdr.adj_code, hdr.adj_date, hdr.authorized_at,
                       SUM(det.stock_after) AS qty
                FROM stock_adjustment_hdr hdr
                JOIN stock_adjustment_det det ON det.adj_code = hdr.adj_code
                WHERE hdr.adj_type='OPENING BALANCE' AND hdr.status!='5'
                  AND det.article_code = :art
                  AND TO_DATE(hdr.adj_date,'dd-mm-yyyy') <  TO_DATE(:fromDate,'dd-mm-yyyy')
                  AND TO_DATE(hdr.adj_date,'dd-mm-yyyy') >= TO_DATE(:floor,'dd-mm-yyyy')
                GROUP BY hdr.adj_code, hdr.adj_date, hdr.authorized_at
                ORDER BY TO_DATE(hdr.adj_date,'dd-mm-yyyy') DESC
                LIMIT 1";
        $bind = ['art'=>$articleCode,'fromDate'=>$fromDate,'floor'=>$floor];
    } else {
        $sql = "SELECT hdr.id, hdr.adj_code, hdr.adj_date, hdr.authorized_at, det.stock_after AS qty
                FROM stock_adjustment_hdr hdr
                JOIN stock_adjustment_det det ON det.adj_code = hdr.adj_code
                WHERE hdr.adj_type='OPENING BALANCE' AND hdr.status!='5'
                  AND det.article_code = :art AND hdr.location_code = :loc
                  AND TO_DATE(hdr.adj_date,'dd-mm-yyyy') <  TO_DATE(:fromDate,'dd-mm-yyyy')
                  AND TO_DATE(hdr.adj_date,'dd-mm-yyyy') >= TO_DATE(:floor,'dd-mm-yyyy')
                ORDER BY TO_DATE(hdr.adj_date,'dd-mm-yyyy') DESC
                LIMIT 1";
        $bind = ['art'=>$articleCode,'loc'=>$location,'fromDate'=>$fromDate,'floor'=>$floor];
    }
    $r = DB::select($sql, $bind);
    if (!isset($r[0])) return ['found'=>false];
    return ['found'=>true,'qty'=>(float)$r[0]->qty,'adj_code'=>$r[0]->adj_code,
            'adj_id'=>$r[0]->id ?? null,'adj_date'=>$r[0]->adj_date,'authorized_at'=>$r[0]->authorized_at];
}

/** Akumulasi net movement (dedup revisi, skip cancel/status5/NEW/OB) antara anchorDate < d < fromDate. */
private function accumulateNet($articleCode, $location, $anchorDate, $fromDate, $isGlobal): float
{
    $whereLoc = $isGlobal ? '' : 'AND m.location_number = :loc2';
    $sql = "
      WITH acc AS (
        SELECT m.movement_code, m.created_at,
            CASE m.movement_type
                WHEN 'RECEIVING'    THEN (SELECT status FROM receiving_hdr        WHERE rec_number      = m.movement_transnno LIMIT 1)
                WHEN 'TRANSFER'     THEN (SELECT status FROM transfer_stock_hdr   WHERE tr_number       = m.movement_transnno LIMIT 1)
                WHEN 'SUPPLY'       THEN (SELECT status FROM transfer_stock_hdr   WHERE tr_number       = m.movement_transnno LIMIT 1)
                WHEN 'DELIVERY'     THEN (SELECT status FROM delivery_hdr         WHERE delivery_number = m.movement_transnno LIMIT 1)
                WHEN 'RETURN'       THEN (SELECT status FROM dn_return_hdr        WHERE return_number   = m.movement_transnno LIMIT 1)
                WHEN 'REPLACEMENT'  THEN (SELECT status FROM dn_replace_hdr       WHERE replace_number  = m.movement_transnno LIMIT 1)
                WHEN 'ADJUSTMENT'   THEN (SELECT status FROM stock_adjustment_hdr WHERE adj_code        = m.movement_transnno LIMIT 1)
                WHEN 'DN SEMENTARA' THEN (SELECT status FROM temporary_dn_hdr     WHERE tdn_number      = m.movement_transnno LIMIT 1)
                WHEN 'DN UMUM'      THEN (SELECT status FROM dn_general_hdr        WHERE tdn_number      = m.movement_transnno LIMIT 1)
                ELSE NULL
            END AS hdr_status,
            CASE
                WHEN m.movement_type IN ('ADJUSTMENT','CANCEL ADJUSTMENT')
                     AND m.movement_plus=0 AND m.movement_min=0
                THEN (SELECT CASE WHEN det.direction='-' THEN -det.qty_adjustment ELSE det.qty_adjustment END
                      FROM stock_adjustment_det det
                      WHERE det.adj_code=m.movement_transnno AND det.article_code=m.artikel_code LIMIT 1)
                     * CASE WHEN m.movement_type='CANCEL ADJUSTMENT' THEN -1 ELSE 1 END
                ELSE (m.movement_plus - m.movement_min)
            END AS net_value,
            ROW_NUMBER() OVER (
                PARTITION BY m.artikel_code, m.movement_transnno, m.location_number
                ORDER BY m.created_at DESC, m.movement_code DESC
            ) AS rn
        FROM warehouse_movement m
        WHERE m.artikel_code = :art AND m.site_code='HO'
          $whereLoc
          AND TO_DATE(m.movement_date,'dd-mm-yyyy') >  TO_DATE(:anchorDate,'dd-mm-yyyy')
          AND TO_DATE(m.movement_date,'dd-mm-yyyy') <  TO_DATE(:fromDate,'dd-mm-yyyy')
          AND m.movement_type NOT LIKE 'CANCEL %'
          AND m.movement_type NOT LIKE 'DELETE%'
          AND m.movement_type NOT LIKE 'REVISI %'
          AND m.movement_type NOT IN ('RETURN-CANCEL','RETURN-REVERSE')
          AND NOT (m.movement_type IN ('ADJUSTMENT','CANCEL ADJUSTMENT')
                   AND EXISTS (SELECT 1 FROM stock_adjustment_hdr h
                               WHERE h.adj_code=m.movement_transnno AND h.adj_type='OPENING BALANCE'))
      )
      SELECT COALESCE(SUM(net_value),0) AS acc
      FROM acc WHERE rn=1 AND hdr_status IS DISTINCT FROM '5' AND hdr_status IS DISTINCT FROM '1'";
    $bind = ['art'=>$articleCode,'anchorDate'=>$anchorDate,'fromDate'=>$fromDate];
    if (!$isGlobal) $bind['loc2'] = $location;
    $r = DB::select($sql, $bind);
    return isset($r[0]) ? (float) $r[0]->acc : 0.0;
}

/**
 * Pecah satu baris movement jadi [qty_in, qty_out].
 * movement_plus/min adalah sumber kebenaran (balanceqty dihitung dari situ).
 * Kalau keduanya 0 dan ini baris adjustment, arah dibaca dari stock_adjustment_det.direction.
 *
 * @return array{0:float,1:float}
 */
private function splitQty($d): array
{
    // NEW/DRAFT (status 1) tetap DITAMPILKAN qty aslinya.
    // Yang tidak dihitung ke saldo cukup lewat `counted_net = 0` di CTE `kept`,
    // jadi di sini tidak perlu lagi paksa [0,0].

    $plus = (float) ($d->movement_plus ?? 0);
    $min  = (float) ($d->movement_min  ?? 0);

    if ($plus > 0 || $min > 0) {
        return [$plus, $min];
    }

    if (!empty($d->adj_direction)) {
        $qty = abs((float) ($d->adj_qty ?? 0));
        $dir = trim($d->adj_direction);
        if (($d->movement_type ?? '') === 'CANCEL ADJUSTMENT') {
            $dir = ($dir === '-') ? '+' : '-';
        }
        return $dir === '-' ? [0.0, $qty] : [$qty, 0.0];
    }

    return [0.0, 0.0];
}

private function buildSummaryRow(array $p)
{
    return (object) array_merge([
        'movement_code'=>null, 'movement_price'=>null, 'movement_date'=>'',
        'movement_min'=>0, 'movement_plus'=>0, 'movement_transnno'=>null,
        'partner_type'=>null, 'mv_from'=>null, 'mv_to'=>null, 'last_qty'=>null,
        'location_number'=>null, 'site_code'=>null, 'created_at'=>null, 'trx_status'=>null,
        'adj_code'=>null, 'adj_id'=>null, 'adj_direction'=>null, 'adj_qty'=>0,
        'is_summary'=>true, 'qty'=>0, 'hdr_status'=>null,
    ], $p);
}

        /*request article*/

        public function getTableColoumnRequest(){
            $kolom=    
            [
                ['data'=>'action','name'=>'action','title'=>'action','orderable'=>false, 'searchable'=>false],
                ['data'=>'status_approve','name'=>'status_approve','title'=>'Status'],
                ['data'=>'statusKu','name'=>'statusKu','title'=>'Status','visible'=>false],
                ['data'=>'desc','name'=>'article_desc','title'=>'Name'],
                ['data'=>'third_party','name'=>'third_party','title'=>'Cust/supp'],
                ['data'=>'cust','name'=>'third_party.nama','title'=>'Custs/Supp'],
                ['data'=>'uom','name'=>'uom','title'=>'UOM'],
                ['data'=>'article_type','name'=>'article_type','title'=>'Type'],
                ['data'=>'group','name'=>'group_materials.name','title'=>'Group'],
                ['data'=>'color_code','name'=>'color_code','title'=>'Color'],
                ['data'=>'variant','name'=>'variant','title'=>'Variant'],
                ['data'=>'brand','name'=>'brand','title'=>'Brand'],
                ['data'=>'safety_stock','name'=>'safety_stock','title'=>'Safety Stock'],
                ['data'=>'min_package','name'=>'min_package','title'=>'Min Package'],
                ['data'=>'note','name'=>'note','title'=>'Note'],
                ['data'=>'created_by','name'=>'created_by','title'=>'Requested By'],
                ['data'=>'created_at','name'=>'created_at','title'=>'Requested At'],
                ['data'=>'approved_by','name'=>'approved_by','title'=>'Approved By'],
                ['data'=>'approved_at','name'=>'approved_at','title'=>'Approved At'],
                ['data'=>'submitted_by','name'=>'submitted_by','title'=>'Submitted By'],
                ['data'=>'submitted_at','name'=>'submitted_at','title'=>'Submitted At']
            ];
            return json_encode($kolom, true);
        }

        public function requestIndex(Request $request)
        {
            $data['title'] = "$this->title Request";

            $data['types'] = DB::table('article_types')
            ->where ('status','=',1)
            ->orderBy('name')
            ->get();
        
            $data['supps'] = DB::table('third_party')
            ->orderBy('nama')
            ->get();        

            $data['groups'] = DB::table('group_materials')
            ->where ('status','=',1)
            ->orderBy('name')
            ->get();

            $data['kolom'] = $this->getTableColoumnRequest();
            
            return view("articles.request",$data);
        }

        public function getStatsRequest(Request $request)
{
    $username      = Auth::user()->username;
    $userSubmitter = Auth::user()->can('article-request-submit') ? "yes" : "no";

    $name  = strtolower($request->name);
    $group = strtolower($request->group);
    $supp  = strtolower($request->supp);
    $type  = strtolower($request->type);

    // base query dengan visibility yang sama seperti requestList
    $base = DB::table('article_request')
    ->where(function ($query1) use ($userSubmitter,$username) {
        if($userSubmitter === "no"){
            $query1->where(DB::RAW("(SELECT count(*) from user_dept where username = article_request.created_by and dept in (select dept from user_dept where username = '$username'))"),">",0);
        }
    })
    ->where(function ($query) use ($name,$group,$supp,$type) {
        $name  ? $query->where('article_desc','ilike','%'.$name.'%') : '';
        $group ? $query->where('group_of_material','ilike','%'.$group.'%') : '';
        $supp  ? $query->where('third_party','ilike','%'.$supp.'%') : '';
        $type  ? $query->where('article_type','ilike',$type.'%') : '';
    });

    return response()->json([
        'total'     => (clone $base)->count(),
        'requested' => (clone $base)->where('status_approve','1')->count(),
        'approved'  => (clone $base)->where('status_approve','2')->count(),
        'submitted' => (clone $base)->where('status_approve','3')->count(),
    ]);
}

        public function requestCreate(Request $request)
        {
            $data['title'] = "Request $this->title";
            $data['subtitle'] = "Request New $this->title";
            
            $data['types'] = DB::table('article_types')
            ->where ('status','=',1)
            ->orderBy('name')
            ->get();

            $data['groups'] = DB::table('group_materials')
            ->where ('status','=',1)
            ->orderBy('name')
            ->get();

            $data['accounts'] = DB::table('accounts')
    // ->whereIn('type_code',['21','22','23','24'])
    ->where('acc_header','!=','HEADER')
    ->orderByRaw("CASE WHEN account ~ '^[0-9]+(\.[0-9]+)?$' THEN account::numeric ELSE NULL END")
    ->orderBy('account')
    ->get();


            $data['uoms'] = DB::table('uom')
            ->orderBy('name')
            ->get();
                            
            return view("articles.requestCreate",$data);
        }

        public function requestStore(Request $request)
        {
            // Dump, Die, Debug Fungsinya untuk nge-debug hasil dari submit
            $username =  Auth::user()->username;
            $type = $request->articleType;
            $cust = $request->cust;
            $nama = strtoupper($request->nama);
            $group = $request->group;
            $uom = $request->uom;
            $coa = $request->coa;
            // $price = $request->price;
            // $price = $price ? str_replace(",","",$price) : $price;
            // $sapetiStok = $request->safetyStock;
            // $safetyStock = $sapetiStok ? str_replace(",","",$sapetiStok) : $sapetiStok;
            // $minimumPackage = $request->minimumPackage ? str_replace(",","",$request->minimumPackage) : $request->minimumPackage;
            $price = is_null($request->price) ? 0 : preg_replace('/[^0-9.]/', '', $request->price);
            $safetyStock = is_null($request->safetyStock) ? 0 : preg_replace('/[^0-9.]/', '', $request->safetyStock);
            $minimumPackage = preg_replace('/[^0-9.]/', '', $request->minimumPackage);
            $note = $request->note;
            $files = $request->files;
            /*
            status 1 = requested
            status 2 = approved
            status 3 = submitted
            status 4 = Rejected
            */
            // $status = $request->status == 'on' ? '1' : '0';
            $status = '1';
            $statusApprove ='1';
            $pesan = '';
            $brand = $request->brand;
            $orderable = $request->orderableCheck == 'on' ? '1' : '0';
            $marketing = $request->marketingCheck == 'on' ? '1' : '0'; // tambahkan ini

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
                    
            DB::beginTransaction();
            try {
                    $artCode = uniqid();
                    DB::table('article_request')->insert([
                        'article_code' => $artCode,
                        'article_desc' => $nama,
                        'group_of_material' => $group,
                        'third_party' => $cust[0],
                        'note' => $note,
                        'uom' => $uom,
                        'coa' => $coa, 
                        'safety_stock' => $safetyStock,
                        'min_package' => $minimumPackage,
                        'costprice' => $price,
                        'status' => $status,
                        'status_approve' => $statusApprove,
                        'color_code' => $colorCode,
                        'variant' => $variant,
                        'article_type' => $type,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'brand' => $brand,
                        'orderable' =>$orderable,
                        'marketing' => $marketing,
                    ]); 

                    foreach($cust as $val){
                        DB::table('article_supplier_request')->insert([
                            'article_code' => $artCode,
                            'supplier_code' => $val,
                            'main_supplier' => $cust[0] == $val ? 'Y' : 'N',
                            'created_by' => Auth::user()->username,
                            'updated_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ]); 
                    }

                    // if($files){
                    //     foreach($files as $val){
                    //         DB::table('images')->insert([
                    //             'key' => $artCode,
                    //             'name' => $nama,
                    //             'path' => $val,
                    //             'created_by' => Auth::user()->username,
                    //             'updated_by' => Auth::user()->username,
                    //             'created_at' => date('Y-m-d H:i:s'),
                    //             'updated_at' => date('Y-m-d H:i:s')
                    //         ]); 
                    //     }
                    // }
                
                    DB::commit();
                    $title ="Save Request $this->title";
                    $alert  ="success";
                    $message  = "$this->title $artCode $nama is successfully saved";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return redirect()->back()->with(['status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'articleCode'=>$artCode]);  

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Save Request $this->title";
                $alert  ="warning";
                $message  = "$this->title $artCode $nama is failed to save";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with(['status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'articleCode'=>$artCode]);
            }        
        }

        public function requestDestroy(Request $request)
        {
            $username =  Auth::user()->username;
            $id=Crypt::decryptString($request->id);

            DB::beginTransaction();
            try {

                $articleDesc=db::table('article_request')->where('id',$id)->value('article_desc');

                $rowAffected=DB::table('article_request')
                ->where('id',$id)->delete();

                DB::commit();
                $title ="Delete $this->title";
                $alert  ="success";
                $message  = "$this->title $articleDesc is successfully deleted";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with(['status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'articleCode'=>$articleDesc]);
            } catch (Exception $e) {
                DB::rollBack();
                $title ="Delete $this->title";
                $alert  ="warning";
                $message  = "$this->title $articleDesc is failed to delete";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with(['status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'articleCode'=>$articleDesc]);
            }    
        }

        public function requestEdit(Request $request)
{
    $id=Crypt::decryptString($request->id);
    $data['title'] = "Edit Request $this->title";
    $data['subtitle'] = "Edit Request $this->title";

    $username =  Auth::user()->username;
    
    $data['article'] = DB::table('article_request')
    ->where('id',$id)
    ->get(['brand','article_code','costprice','article_alternative_code as code','article_desc as desc','uom','coa','quality','note','id','group_of_material as group','third_party as cust','quality','status','article_type','imgfile','color_code','variant','safety_stock','min_package','orderable','marketing','status_approve'])->first();

    $data['bisaApprove'] = DB::table('article_request')
    ->select('article_request.*'
    ,DB::RAW("(SELECT count(*) from user_dept where username = created_by and dept in (select dept from user_dept where username = '$username')) as bisa_approve"))
    ->where('id',$id)
    ->value('bisa_approve');

    $data['types'] = DB::table('article_types')
    ->where ('status','=',1)
    ->orderBy('name')
    ->get();

    $code = $data['article']->article_type;
    $data['custs'] = DB::table('third_party')->where(function ($query) use ($code) {
    })->get();

    $data['groups'] = DB::table('group_materials')
    ->where ('status','=',1)
    ->orderBy('name')
    ->get();

    $data['uoms'] = DB::table('uom')
    ->orderBy('name')
    ->get();

    $data['accounts'] = DB::table('accounts')
    // ->whereIn('type_code',['21','22','23','24'])
    ->where('acc_header','!=','HEADER')
    ->orderByRaw("CASE WHEN account ~ '^[0-9]+(\.[0-9]+)?$' THEN account::numeric ELSE NULL END")
    ->orderBy('account')
    ->get();

    $data['suppliers']= DB::table('article_supplier_request') 
    ->where('article_code',$data['article']->article_code)
    ->orderBy('id')
    ->pluck('supplier_code')->toArray();

    return view('articles.requestEdit',$data);
}

        public function requestUpdate(Request $request)
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
            $coa = $request->coa;
            $price = preg_replace('/[^0-9.]/', '', $request->price);
            $safetyStock = preg_replace('/[^0-9.]/', '', $request->safetyStock);
            $minimumPackage = preg_replace('/[^0-9.]/', '', $request->minimumPackage);
            $note = $request->note;
            // $files = $request->files;
            // $fileDihapus = $request->fileDihapus;
            $status = $request->status == 'on' ? '1' : '0';
            $pesan = '';
            $colorCode = $request->colorCode;
            $variant = $request->variant;
            $brand = $request->brand;

            $orderable = $request->orderableCheck == 'on' ? '1' : '0';
            $marketing = $request->marketingCheck == 'on' ? '1' : '0';
            $statusApprove = '1';

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
                    $rowAffected=DB::table('article_request')
                    ->where('id',$id)
                    ->update(
                        [
                            'article_desc' => $nama,
                            'group_of_material' => $group,
                            'third_party' => $cust[0],
                            'note' => $note,
                            'uom' => $uom,
                            'coa' => $coa,
                            'safety_stock' => $safetyStock,
                            'min_package' => $minimumPackage,
                            'costprice' => $price,
                            'status' => $status,
                            'status_approve' => $statusApprove,
                            'color_code' => $colorCode,
                            'variant' => $variant,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s'),
                            'brand' => $brand,
                            'orderable' =>$orderable,
                            'marketing' => $marketing
                        ]
                    );
                    
                    $dataset=[];
                    foreach ($cust as $val) {
                        $dataSet[] = [
                            $artCode.$val
                        ];
                    }

                    $getArticleCode = db::table('article_request')->where('id',$id)->value('article_code');

                    /*
                    Delete kalo article tidak ada di po $poNumber dan article nya $val->article_code
                    berdasarkan 2 kondisi
                    */
                    DB::table('article_supplier_request')
                    ->whereNotIn(DB::raw("CONCAT(article_code,supplier_code)"),$dataSet)
                    ->where('article_code',$artCode)
                    ->delete();
                        
                    foreach($cust as $val){
                        DB::table('article_supplier_request')
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

                    // if($fileDihapus){
                    //     DB::table('images')->whereIn('path',$fileDihapus)->delete();
                    // }
                    
                    // if($files){
                    //     foreach($files as $val){
                    //         DB::table('images')->insert([
                    //             'key' => $artCode,
                    //             'name' => $nama,
                    //             'path' => $val,
                    //             'created_by' => Auth::user()->username,
                    //             'updated_by' => Auth::user()->username,
                    //             'created_at' => date('Y-m-d H:i:s'),
                    //             'updated_at' => date('Y-m-d H:i:s')
                    //         ]); 
                    //     }
                    // }
                    
                    DB::commit();

                    if($rowAffected>0){
                        DB::commit();
                        $title ="Update $this->title";
                        $alert  ="success";
                        $message  = "$this->title $articleAltCode is successfully updated";
                        \LogActivity::addToLog($title,"username: $username Status $message");
                        return redirect()->route('article.request')->with(['status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'articleCode'=>$articleAltCode]);
                        // return redirect()->back()->with(['status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'articleCode'=>$articleAltCode]);
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

        public function requestApprove(Request $request)
        {
            $username =  Auth::user()->username;
            $id = $request->id;
            $artCode = $request->nama;
            $statusApprove = '2';
            $status = $request->status == 'on' ? '1' : '0';
                    
            DB::beginTransaction();

            try {
                    $rowAffected=DB::table('article_request')
                    ->where('id',$id)
                    ->update(
                        [
                            'status_approve' => $statusApprove,
                            'approved_by' => Auth::user()->username,
                            'approved_at' => date('Y-m-d H:i:s')
                        ]
                    );
                    
                    DB::commit();

                    if($rowAffected>0){
                        DB::commit();
                        $title ="Approve $this->title";
                        $alert  ="success";
                        $message  = "$this->title $artCode is successfully Approved";
                        \LogActivity::addToLog($title,"username: $username Status $message");
                        return redirect()->route('article.request')->with(['status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'articleCode'=>$artCode]);
                    }else{
                        $title ="Approve $this->title";
                        $alert  ="warning";
                        $message  = "$this->title $artCode is failed to Approve";
                        \LogActivity::addToLog($title,"username: $username Status $message");
                        return redirect()->route('article.request')->with(['status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'articleCode'=>$artCode]);
                    }

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Approve $this->title";
                $alert  ="warning";
                $message  = "$this->title $articleAltCode is failed to Approve";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->route('article.request')->with(['status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'articleCode'=>$articleAltCode]);
            }
        }

        public function requestShow(Request $request)
        {
            $id=Crypt::decryptString($request->id);
            $data['title'] = "Detail Request $this->title";
            $data['subtitle'] = "Detail Request $this->title";
            
            $data['article'] = DB::table('article_request')
            ->where('id',$id)
            ->get(['article_code','costprice','article_alternative_code as code','article_desc as desc','uom','quality','note','id','group_of_material as group','third_party as cust','quality','status','article_type','imgfile','color_code','variant','safety_stock','min_package','orderable','marketing'])->first();

            // $data['images'] = DB::table('images')
            // ->where('key',$data['article']->article_code)
            // ->get();

            $data['types'] = DB::table('article_types')
            ->where ('status','=',1)
            ->orderBy('name')
            ->get();

            $data['article']->article_type  == 'FG' || $data['article']->article_type  == 'RM'  ? $typeTP = 'cust' : $typeTP = 'supp';

            $data['custs'] = DB::table('third_party')
            // ->where ('third_party_type','=',$typeTP)
            ->orderBy('nama')
            ->get();

            $data['groups'] = DB::table('group_materials')
            ->where ('status','=',1)
            ->orderBy('name')
            ->get();

            $data['uoms'] = DB::table('uom')
            ->orderBy('name')
            ->get();

            $data['suppliers']= DB::table('article_supplier_request') 
            ->where('article_code',$data['article']->article_code)
            ->orderBy('id')
            ->pluck('supplier_code')->toArray();
            
            return view('articles.requestShow',$data);
            
        }

        public function requestList(Request $request)
        {
            $code = strtolower($request->code);
            $name = strtolower($request->name);
            $group = strtolower($request->group);
            $cust = strtolower($request->cust);
            $supp = strtolower($request->supp);
            $type = strtolower($request->type);
            $status = $request->status;
            $username =  Auth::user()->username;
            $userSubmitter = "no";


            if (Auth::user()->can('article-request-submit')){
                $userSubmitter = "yes";
            }

            // $berhakApprove = Approval::approveValidate($this->moduleCode,$bomNumber,$username);
            $data=DB::table('article_request')
            ->select('article_request.*'
            ,'costprice'
            ,'article_request.article_code as art_code'
            ,'article_alternative_code as code'
            ,'article_desc as desc'
            ,'brand'
            ,'article_request.uom'
            ,'quality'
            ,'note'
            ,'article_request.id as idku'
            ,'group_materials.name as group'
            ,'third_party.nama as cust'
            ,'safety_stock'
            ,'min_package'
            ,'uom.uom_group'
            ,DB::RAW("(SELECT count(*) from user_dept where username = article_request.created_by and dept in (select dept from user_dept where username = '$username')) as bisa_approve")
            )
            ->leftJoin('group_materials', 'group_materials.code', '=', 'article_request.group_of_material')
            ->leftJoin('third_party', 'third_party.kode', '=', 'article_request.third_party')
            ->leftJoin('uom','uom.code','article_request.uom')        
            // ->where(DB::RAW("(SELECT count(*) from user_dept where username = article_request.created_by and dept in (select dept from user_dept where username = '$username'))"),">",0)
            ->where(function ($query1) use ($userSubmitter,$username) {
                if($userSubmitter === "no"){
                    $query1->where(DB::RAW("(SELECT count(*) from user_dept where username = article_request.created_by and dept in (select dept from user_dept where username = '$username'))"),">",0);
                }
            })
            ->where(function ($query) use ($name,$group,$cust,$supp,$type,$status) {
                $name ? $query->where('article_desc','ilike','%'.$name.'%') :'';
                $group ? $query->where('group_of_material','ilike','%'.$group.'%') :'';
                $cust ? $query->where('third_party','ilike','%'.$cust.'%') :'';
                $supp ? $query->where('third_party','ilike','%'.$supp.'%') :'';
                $type ? $query->where('article_type','ilike',$type.'%') :'';      
                $status ? $query->where('article_request.status_approve',$status) :''; 
            })->orderBy('article_request.created_at','desc');
        
            return Datatables::of($data)
            ->addColumn('action', function ($data) {
                $buttons = '<div class="d-inline-flex">
                                <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                    <i data-feather="menu"></i>
                                </a>';
                $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            
                if (Auth::user()->can('article-request-edit') ) {
                    if (($data->bisa_approve > 0) && ($data->status_approve == '1' ||  $data->status_approve == '2') ) {
                    // if ($data->bisa_approve > 0 ) {
                        $buttons .= '<a href="'. route('article.request.edit',  ['id'=>Crypt::encryptString($data->idku)]) .'" class="dropdown-item">
                                        <i data-feather="file-text"></i>
                                        Edit
                                    </a>';
                    }
                }

                if (Auth::user()->can('article-request-approve')){
                    if ($data->bisa_approve > 0 && $data->status_approve == '1') {
                        $buttons .=         '<a href="'. route('article.request.edit',  ['id'=>Crypt::encryptString($data->idku)]) .'" class="dropdown-item">
                                                <i data-feather="check"></i>
                                                Approve
                                            </a>';
                    }

                }

                if (Auth::user()->can('article-request-submit')){
                    
                    if ( $data->status_approve == '2' ) {
                        $buttons .=         '<a href="'. route('article.request.edit',  ['id'=>Crypt::encryptString($data->idku)]) .'" class="dropdown-item">
                                                <i data-feather="check"></i>
                                                Submit
                                            </a>';
                    }

                }

                $buttons .=         '<a href="'. route('article.request.show', ['id'=>Crypt::encryptString($data->idku)]) .'" class="dropdown-item">
                                        <i data-feather="list"></i>
                                        Detail
                                    </a>';

                if (Auth::user()->can('article-request-delete')) {
                    if ($data->status_approve == '1') {
                        $buttons .=         '<a href="javascript:;"
                                                id="deleteButton"
                                                class="dropdown-item"
                                                data-toggle="modal"
                                                data-target="#smallModal"
                                                data-href="'. route("article.request.destroy", ['id'=>Crypt::encryptString($data->idku)]) .'">
                                                <i data-feather="trash-2" class="feather-14-red"></i>
                                                Delete
                                            </a>';
                    }
                }
                $buttons .=     '</div>
                            </div>';

                return $buttons;
            })
            ->addColumn('status_approve', function ($data) {
                /*
                status 1 = requested
                status 2 = approved
                status 3 = submitted
                status 4 = Rejected
                */
                if($data->status_approve > 0){
                    $badges=['badge-light-success','badge-light-primary','badge-light-danger'];
                    $statusCode = ['Requested','Approved','Submitted','Rejected'];
                    return "<div class='badge badge-pill ".$badges[$data->status_approve-1]."'>".$statusCode[$data->status_approve-1]."</div>";
                }else{
                    return $data->status_approve;
                }
            })
            ->addColumn('statusKu', function ($data) {
                return $data->status;
            })
            ->rawColumns(['action','status','status_approve'])
            ->make(true);
        }

        public function requestSubmit(Request $request)
        {

            $username =  Auth::user()->username;
            $articleCodeRequest = $request->artCode;
            $type = $request->articleType;
            $cust = $request->cust;
            $nama = strtoupper($request->nama);
            $group = $request->group;
            $uom = $request->uom;
            $coa = $request->coa;
            $price = is_null($request->price) ? 0 : preg_replace('/[^0-9.]/', '', $request->price);
            $safetyStock = is_null($request->safetyStock) ? 0 : preg_replace('/[^0-9.]/', '', $request->safetyStock);
            $minimumPackage = preg_replace('/[^0-9.]/', '', $request->minimumPackage);
            $note = $request->note;
            $files = $request->files;
            $statusApprove = '3';
            $pesan = '';
            $brand = $request->brand;
            $colorCode = $request->colorCode;
            $variant = $request->variant;
            $status = $request->status == 'on' ? '1' : '0';
            $orderable = $request->orderableCheck == 'on' ? '1' : '0';
            $marketing = $request->marketingCheck == 'on' ? '1' : '0';

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
                        'coa' => $coa,
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
                        'brand' => $brand,
                        'orderable' =>$orderable,
                        'marketing' =>$marketing
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

                    $rowAffected=DB::table('article_request')
                    ->where('article_code',$articleCodeRequest)
                    ->update(
                        [
                            'status_approve' => '3',
                            'submitted_by' => Auth::user()->username,
                            'submitted_at' => date('Y-m-d H:i:s')
                        ]
                    );

                    // if($files){
                    //     foreach($files as $val){
                    //         DB::table('images')->insert([
                    //             'key' => $artCode,
                    //             'name' => $nama,
                    //             'path' => $val,
                    //             'created_by' => Auth::user()->username,
                    //             'updated_by' => Auth::user()->username,
                    //             'created_at' => date('Y-m-d H:i:s'),
                    //             'updated_at' => date('Y-m-d H:i:s')
                    //         ]); 
                    //     }
                    // }
                
                    DB::commit();
                    $title ="Save $this->title";
                    $alert  ="success";
                    $message  = "$this->title $articleCode is successfully saved";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return redirect()->route('article.request')->with(['status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'articleCode'=>$articleCode]);

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Save $this->title";
                $alert  ="warning";
                $message  = "$this->title $articleCode is failed to save";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with(['status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'articleCode'=>$articleCode]);
            }   
        }

        public function safetyStockImportExcel(Request $request)
        {

            $JumlahData = 0;
            // validasi
            $this->validate($request, [
                'file' => 'required|mimes:xls,xlsx'
            ]);
    
            // menangkap file excel
            $file = $request->file('file');
    
            // // membuat nama file unik
            $namaFile = rand().$file->getClientOriginalName();
    
            // // upload ke folder file_siswa di dalam folder public
            // $file->move('file_siswa',$namaFile);
            // import data
            // Excel::import(new SiswaImport, public_path('/file_siswa/'.$namaFile));

            $data['filename']=$namaFile;
            db::table('import_stock_take_tmp')->delete();
            Excel::import(new SafetyStockImport($data), $file);

            $dataValidasi = DB::table('import_stock_take_tmp')
            ->leftJoin('article','article.article_alternative_code','import_stock_take_tmp.article_code')
            ->select('import_stock_take_tmp.article_code'
            ,'import_stock_take_tmp.qty'
            ,DB::RAW("concat(
                case when import_stock_take_tmp.qty::text ~ '^[0-9.]+$' = false then concat('Urutan ',row_number() over(),': Qty salah - ',qty) end,
                case when article.article_code is null then concat('Urutan ',row_number() over(),': Article Code:',import_stock_take_tmp.article_code, ' tidak terdaftar') end
                ) as notes")
            )
            ->where('file_name', $namaFile)
            ->get();

            $dataNotes=[];
            foreach ($dataValidasi as $val) {
                if($val->notes){
                    $dataNotes[]= [$val->notes];
                }
            } 

            $title ="Import $this->title";
            $pesan="";

            if (count($dataNotes) > 0 ){
                $pesan .='Ada error pada data yang diupload, silahkan cek notes error!';
                $status = 0;
                $alert = "error";
                $message = $dataNotes;
                $data = "";

            }else{

                // return redirect()->back()->with('success', 'Excel file imported successfully!');
                $data = db::table('import_stock_take_tmp')
                ->leftJoin('article','article.article_alternative_code','import_stock_take_tmp.article_code')
                ->select('article.article_code'
                ,'article.uom'
                ,'import_stock_take_tmp.qty')
                ->where('file_name', $namaFile)
                ->get();

                $JumlahData = db::table('import_stock_take_tmp')
                ->where('file_name', $namaFile)
                ->count();
                
                $status = 1;
                $alert = "success";
                $message  = "$title is successfully imported";

            }
                    
            // $alert  ="success";
            // $message  = "$title is successfully imported";

            return response()->json(array('status' => $status,'title' => $title, 'message' => $message,'alert' =>$alert,'dataDetail'=>$data,'pesan'=>$pesan,'namaFile'=>$namaFile,'JumlahData'=>$JumlahData));

            // return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message,'dataDetail'=>$data]);
        }

        public function safetyStockExport()
        {
            return Excel::download(new SafetyStockExport, 'safety_stock_template.xls');
        }

        public function updateSafetyStock(Request $request)
        {
            $username =  Auth::user()->username;
            $filename = $request->file;
            $type = $request->type;
            $rowAffected = 0;
            $title ="Update Safety Stock update";
                    
            DB::beginTransaction();
            try {

                if($type == 'update'){
                    $rowAffected = db::select("UPDATE article
                    SET safety_stock = (select 
                    (case when qty is not null then qty::numeric else 0 end) as qty from import_stock_take_tmp 
                    where article_code = article.article_alternative_code and file_name = '$filename')
                    where article_alternative_code  in (select article_code from import_stock_take_tmp where file_name = '$filename')");
                }

                if($type == 'cancel'){
                    $title ="Canceled Safety Stock update";
                }

                $rowAffected = DB::table('import_stock_take_tmp')->where('file_name', $filename)->delete();
                                                        
                if($rowAffected>0){
                    DB::commit();
                    $alert  ="success";
                    $message  = "Safety Stock update $filename is successfully updated";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert));
                }else{
                    $alert  ="warning";
                    $message  = "Safety Stock update $filename is failed to updated";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 0,'title' => $title, 'message' => $message,'alert'=>$alert));
                }

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Update $this->title";
                $alert  ="warning";
                $message  = "$this->title $filename is failed to updated";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 0,'title' => $title, 'message' => $message,'alert'=>$alert));
            }
        }

        public function getStatsByType(Request $request)
{
    $data = DB::table('article')
        ->leftJoin('article_types', 'article_types.code', '=', 'article.article_type')
        ->select(
            'article.article_type as code',
            DB::raw("COALESCE(article_types.name, 'Unknown') as type_name"),
            DB::raw('count(*) as total_qty')
        )
        ->groupBy('article.article_type', 'article_types.name')
        ->orderBy('article.article_type')
        ->get();

    return response()->json([
        'labels' => $data->pluck('code'),
        'names'  => $data->pluck('type_name'),   // ⬅️ tambahan baru
        'values' => $data->pluck('total_qty'),
    ]);
}
        
    }
