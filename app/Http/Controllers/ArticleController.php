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
        public function __construct()
        {
            $this->title = "Article";
            $this->decimalPlaces = config('globalParam.decimal');
            $this->moduleCode = "ART";
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

        public function getStats()
{
    $total  = DB::table('article')->count();
    $active = DB::table('article')->where('status','1')->count();
    $freeze = DB::table('article')->where('status','0')->count();

    return response()->json([
        'total'  => $total,
        'active' => $active,
        'freeze' => $freeze,
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
            ->get(['brand','article_code','costprice','article_alternative_code as code','article_desc as desc','uom','quality','note','id','group_of_material as group','third_party as cust','quality','status','article_type','imgfile','color_code','variant','safety_stock','min_package','orderable'])->first();
            

            $data['images'] = DB::table('images')
            ->where('key',$data['article']->article_code)
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
            ->get(['article_code','costprice','article_alternative_code as code','article_desc as desc','uom','quality','note','id','group_of_material as group','brand', 'third_party as cust','quality','status','article_type','imgfile','color_code','variant','safety_stock','min_package','orderable'])->first();

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
                            'orderable' =>$orderable
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
    $statusFilter = $request->statusFilter; // '' | '0' | '1'

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
    });

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

        public function movement2(Request $request){
        $articleCode = $request->articleCode;
        $location    = $request->location;     // kosong = global
        $siteCode    = 'HO';
        $fromDate    = $request->fromDate;     // 'dd-mm-yyyy'
        $toDate      = $request->toDate;       // 'dd-mm-yyyy'
        $inout       = $request->inout;        // '', 'in', 'out'

        if ($location) {
            $whereLoc    = "and m.location_number = '".$location."'";
            $locationCol = "b.location_number";
        } else {
            $whereLoc    = ""; // global: tampilkan semua type, termasuk TRF
            $locationCol = "'ALL'";
        }

        $dateFilter = ($fromDate && $toDate)
            ? "and TO_DATE(b.movement_date,'dd-mm-yyyy')
                between TO_DATE('$fromDate','dd-mm-yyyy') and TO_DATE('$toDate','dd-mm-yyyy')"
            : "";

        $inoutFilter = '';
        if ($inout === 'in')  $inoutFilter = "and b.movement_plus > 0";
        if ($inout === 'out') $inoutFilter = "and b.movement_min  > 0";

        $sqlku = "
            WITH base AS (
        SELECT m.*,
            SUM(m.movement_plus - m.movement_min) OVER (
                ORDER BY TO_DATE(m.movement_date,'dd-mm-yyyy'), m.movement_code
            ) as balanceqty_calc,
            SUM(m.movement_plus - m.movement_min) OVER (
                ORDER BY TO_DATE(m.movement_date,'dd-mm-yyyy'), m.movement_code
            ) - (m.movement_plus - m.movement_min) as last_qty_calc
        FROM warehouse_movement m
        WHERE m.artikel_code = '$articleCode'
        and m.site_code = '$siteCode'
        $whereLoc
    )
            SELECT
                b.movement_code,
                b.artikel_code,
                b.artikel_desc,
                b.movement_plus - b.movement_min as qty,
                b.movement_price,
                b.movement_date,
                b.movement_desc,
                b.movement_type,
                b.movement_min,
                b.movement_plus,
                b.movement_transnno,
                $locationCol as location_number,
                case when b.movement_type = 'TRF'
                    then 'Internal'
                    when b.partner_type = 'SUPP'
                    then (select nama from third_party where kode = b.movement_from)
                    else (select location_name from stock_location_master where location_code = b.movement_from)
                end as mv_from,
                case when b.movement_type = 'TRF'
                    then 'Internal'
                    when b.partner_type = 'CUST'
                    then (select nama from third_party where kode = b.movement_to)
                    else (select location_name from stock_location_master where location_code = b.movement_to)
                end as mv_to,
                b.balanceqty_calc as balanceqty,
                b.last_qty_calc   as last_qty,
                ROW_NUMBER() OVER (
                    ORDER BY TO_DATE(b.movement_date,'dd-mm-yyyy') DESC, b.movement_code DESC
                ) as urutan,
                b.site_code,
                b.created_at
            FROM base b
            WHERE 1=1
            $dateFilter
            $inoutFilter
            ORDER BY TO_DATE(b.movement_date,'dd-mm-yyyy'), b.movement_code";

        $data = DB::select($sqlku);

        return Datatables::of($data)
        ->addColumn('qty', function ($data) {
            $decimal = (fmod($data->qty,1) !== 0.00) ? $this->decimalPlaces : 0;
            $qty = number_format($data->qty,$decimal);
            return $data->qty < 0 ? "<div class='text-red'>$qty</div>" : "<div class='text-hijau'>$qty</div>";
        })
        ->addColumn('movement_transnno', function ($data) {
            $ref = $data->movement_transnno;
            if (!$ref) return '-';

            $url = null;
            switch ($data->movement_type) {
                case 'REC':   // receiving
                    $id = DB::table('receiving_hdr')->where('rec_number', $ref)->value('id');
                    if ($id) $url = route('receiving.show', ['id' => Crypt::encryptString($id)]);
                    break;

                case 'TRF': // transfer antar gudang
                    $id = DB::table('transfer_hdr')->where('tr_number', $ref)->value('id');
                    if ($id) $url = route('warehouse.show', ['id' => Crypt::encryptString($id)]);
                    break;

                case 'DEL':   // delivery
                    $id = DB::table('delivery_hdr')->where('del_number', $ref)->value('id');
                    if ($id) $url = route('delivery.show', ['id' => Crypt::encryptString($id)]);
                    break;
            }

            return $url
                ? '<a href="'.$url.'" target="_blank" class="text-primary">'.$ref.'</a>'
                : $ref;
        })
        ->addColumn('balanceqty', function ($data) {
            $decimal = (fmod($data->balanceqty,1) !== 0.00) ? $this->decimalPlaces : 0;
            $balanceQty = number_format($data->balanceqty,$decimal);
            return $data->balanceqty < 0 ? "<div class='text-red'>$balanceQty</div>" : "<div class='text-hitam'>$balanceQty</div>";
        })
        ->addColumn('inout', function ($data) {
            if ($data->movement_plus > 0) {
                return "<span class='badge badge-pill badge-light-success'>
                            <i data-feather='arrow-down-circle' class='font-small-3'></i> IN
                        </span>";
            } elseif ($data->movement_min > 0) {
                return "<span class='badge badge-pill badge-light-danger'>
                            <i data-feather='arrow-up-circle' class='font-small-3'></i> OUT
                        </span>";
            }
            return "<span class='badge badge-pill badge-light-secondary'>-</span>";
        })
        ->rawColumns(['qty','balanceqty','movement_transnno','inout'])
        ->make(true);
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

        public function getStatsRequest()
{
    $username      = Auth::user()->username;
    $userSubmitter = Auth::user()->can('article-request-submit') ? "yes" : "no";

    // base query dengan visibility yang sama seperti requestList
    $base = DB::table('article_request')->where(function ($query1) use ($userSubmitter,$username) {
        if($userSubmitter === "no"){
            $query1->where(DB::RAW("(SELECT count(*) from user_dept where username = article_request.created_by and dept in (select dept from user_dept where username = '$username'))"),">",0);
        }
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
                        'orderable' =>$orderable
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
            ->get(['brand','article_code','costprice','article_alternative_code as code','article_desc as desc','uom','quality','note','id','group_of_material as group','third_party as cust','quality','status','article_type','imgfile','color_code','variant','safety_stock','min_package','orderable','status_approve'])->first();

            $data['bisaApprove'] = DB::table('article_request')
            ->select('article_request.*'
            ,DB::RAW("(SELECT count(*) from user_dept where username = created_by and dept in (select dept from user_dept where username = '$username')) as bisa_approve"))
            ->where('id',$id)
            ->value('bisa_approve');

            // $data['images'] = DB::table('images')
            // ->where('key',$data['article']->article_code)
            // ->get();

            $data['types'] = DB::table('article_types')
            ->where ('status','=',1)
            ->orderBy('name')
            ->get();

            $code = $data['article']->article_type;
            $data['custs'] = DB::table('third_party')->where(function ($query) use ($code) {
                // $code == 'FG' ? $query->where('third_party_type','cust') : '';
                // $code != 'FG' ? $query->where('third_party_type','supp') : '';
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
                            'orderable' =>$orderable
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
            ->get(['article_code','costprice','article_alternative_code as code','article_desc as desc','uom','quality','note','id','group_of_material as group','third_party as cust','quality','status','article_type','imgfile','color_code','variant','safety_stock','min_package','orderable'])->first();

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
            })->orderBy('created_at')->get();
        
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
        
    }
