<?php

namespace App\Http\Controllers\Production;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Response;
use App\Permission;
use DataTables;
use DB;
use PDF;
use AppHelpers;
use Approval;
use App\Exports\ActualFinishGoodsExport;
use App\Imports\ActualFinishGoodsImport;
use Maatwebsite\Excel\Facades\Excel;

class ActualFinishGoodsController extends Controller
{
   private $title;
private $moduleCode;
private $whLoading, $whFg, $whFgOt, $whWip;
private $loadingStatusDone;

public function __construct()
{
    $this->title      = "Actual Finish Goods";
    $this->moduleCode = "PRDFG";

    $this->whLoading = '047'; // sumber stok fisik (hasil actual loading)
    $this->whFg      = '007'; // gudang FG
    $this->whFgOt    = '008'; // gudang FG OT
    $this->whWip     = '012'; // gudang WIP

    $this->loadingStatusDone = 4; // status actual loading setelah FG diinput (4 = POSTED)
}

    public function getTableColoumn()
    {
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=> false,'searchable'=>false],
            ['data'=>'prod_code','name'=>'prod_code','title'=>'Prod. Number'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'prod_date','name'=>'prod_date','title'=>'Prod. Date'],
            ['data'=>'wo_code','name'=>'wo_code','title'=>'WOS. Code'],
            ['data'=>'wo_date','name'=>'wo_date','title'=>'WOS. Date'],
            ['data'=>'num_revision','name'=>'num_revision','title'=>'Revision'],
            ['data'=>'prod_shift','name'=>'wo_shift','title'=>'Shift'],
            ['data'=>'prod_group','name'=>'wo_group','title'=>'Group'],
            ['data'=>'start_time','name'=>'start_time','title'=>'Start Time'],
            ['data'=>'working_hour','name'=>'working_hour','title'=>'Working Hour'],
            ['data'=>'efficiency','name'=>'efficiency','title'=>'Efficiency'],

        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnDetail()
    {
        $kolom=
        [
            ['data'=>'prod_code','name'=>'prod_code','title'=>'Prod. Number'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'prod_date','name'=>'prod_date','title'=>'Prod. Date'],
            ['data'=>'wo_code','name'=>'wo_code','title'=>'WOS. Code'],
            ['data'=>'wo_date','name'=>'wo_date','title'=>'WOS. Date'],
            ['data'=>'num_revision','name'=>'num_revision','title'=>'Revision'],
            ['data'=>'prod_shift','name'=>'wo_shift','title'=>'Shift'],
            ['data'=>'prod_group','name'=>'wo_group','title'=>'Group'],
            ['data'=>'start_time','name'=>'start_time','title'=>'Start Time'],
            ['data'=>'working_hour','name'=>'working_hour','title'=>'Working Hour'],
            ['data'=>'efficiency','name'=>'efficiency','title'=>'Efficiency'],
            ['data'=>'article_code_fg','name'=>'article_code_fg','title'=>'Article Code'],
            ['data'=>'article_desc_fg','name'=>'article_desc_fg','title'=>'Article Desc'],
            ['data'=>'article_code_rm','name'=>'article_code_rm','title'=>'Article Code RM'],
            ['data'=>'article_desc_rm','name'=>'article_desc_rm','title'=>'Article Desc RM'],
            ['data'=>'plan_qty_fresh','name'=>'plan_qty_fresh','title'=>'Plan Qty Fresh'],
            ['data'=>'act_qty_fresh','name'=>'act_qty_fresh','title'=>'Act Qty Fresh'],
            ['data'=>'act_finish_goods','name'=>'act_finish_goods','title'=>'Act Finish goods']
        ];

        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $data['title'] = $this->title;
        $data['subtitle'] = "$this->title";

        $data['kolom'] = $this->getTableColoumn();
        $data['kolomDetail'] = $this->getTableColoumnDetail();

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'CLOSED','7'='REVISED'];
        $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED'];
            
        return view("production.actualFinishGoods.index",$data);
    }

    public function getLastCode($key)
{
    DB::table('master_code')
        ->where('code_key', $key)
        ->update([
            'code_number' => DB::raw('code_number + 1'),
            'updated_by'  => Auth::user()->username,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

    $newCode = DB::table('master_code')
        ->where('code_key', $key)
        ->value('code_number');

    $monthRoman = $this->toRomanMonth((int) date('n'));
    $year       = date('Y');
    $codeNumber = str_pad($newCode, 4, '0', STR_PAD_LEFT);

    // Format: AFG-ASN-{bulan romawi}-{tahun}-{nomor}
    return "$key-ASN-$monthRoman-$year-$codeNumber";
}

private function toRomanMonth(int $month): string
{
    $romans = [
        1 => 'I',   2 => 'II',  3 => 'III', 4 => 'IV',
        5 => 'V',   6 => 'VI',  7 => 'VII', 8 => 'VIII',
        9 => 'IX',  10 => 'X',  11 => 'XI', 12 => 'XII',
    ];
    return $romans[$month] ?? '';
}


    public function create(Request $request)
{
    $data['title']    = "Input Actual Finish Goods";
    $data['subtitle'] = "Input Actual Finish Goods";

    // Actual Loading yg sudah POSTED (4) & belum punya FG (belum di-input)
    $data['listLoading'] = DB::table('actual_loading_hdr as alh')
        ->where('alh.status', 1)
        ->whereNotExists(function ($q) {
            $q->select(DB::raw(1))
              ->from('actual_finish_goods_hdr as afg')
              ->whereColumn('afg.loading_code', 'alh.prod_code')
              ->where('afg.status', '<>', 5); // abaikan yg canceled
        })
        ->orderBy('alh.prod_code', 'desc')
        ->select(
            'alh.prod_code',
            'alh.wos_reference',
            DB::raw("to_char(alh.loading_date, 'DD-MM-YYYY') as loading_date_fmt")
        )
        ->get();

    $data['statusPrd'] = 'NEW';
    $data['oEdit']     = false;

    return view("production.actualFinishGoods.create", $data);
}

/** Ambil artikel dari actual_loading_det utk prod_code terpilih */
public function articleByLoading(Request $request)
{
    $prodCode = $request->prod_code;

    $rows = DB::table('actual_loading_det as ald')
        ->leftJoin('article as a', 'a.article_code', '=', 'ald.article_code')
        ->where('ald.prod_code', $prodCode)
        ->select(
            'ald.article_code',
            'a.article_alternative_code',
            'a.article_desc',
            'ald.uom',
            'ald.qty as qty_loading'
        )
        ->orderBy('ald.urutan')
        ->get();

    return response()->json($rows);
}

public function store(Request $request)
{
    $username    = Auth::user()->username;
    $articles    = json_decode($request->articles);
    $fgDate      = $request->fgDate;
    $loadingCode = $request->loadingCode;
    $reference   = $request->reference;
    $note        = $request->note;

    $validation = Validator::make($request->all(), [
        'loadingCode' => 'required',
        'fgDate'      => 'required',
    ]);
    if ($validation->fails()) {
        $errs = [];
        foreach ($validation->messages()->getMessages() as $m) { $errs[] = $m; }
        return response()->json(['status'=>0,'title'=>"Save Actual Finish Goods",'message'=>$errs,'alert'=>'error']);
    }
    if (empty($articles)) {
        return response()->json(['status'=>0,'title'=>"Save Actual Finish Goods",'message'=>[['Tidak ada artikel yang diinput.']],'alert'=>'error']);
    }

    $fgDateDb = $fgDate ? implode('-', array_reverse(explode('-', $fgDate))) : date('Y-m-d');
    $now      = date('Y-m-d H:i:s');

    DB::beginTransaction();
    try {
        // ── header loading: pastikan ada & belum diproses ──
        $loading = DB::table('actual_loading_hdr as alh')
            ->leftJoin('stock_location_master as slm', 'slm.location_code', '=', 'alh.spray_booth')
            ->where('alh.prod_code', $loadingCode)
            ->select(
                'alh.status',
                'alh.spray_booth',
                DB::raw("coalesce(slm.location_name, alh.spray_booth) as booth_name")
            )
            ->first();

        if (!$loading) {
            throw new \Exception("Actual Loading {$loadingCode} tidak ditemukan.");
        }
        if ((int)$loading->status === $this->loadingStatusDone) {
            throw new \Exception("Actual Loading {$loadingCode} sudah pernah diinput Finish Goods-nya.");
        }

        $sprayBooth = $loading->spray_booth;
        $boothName  = $loading->booth_name ?? '-';
        $refText    = $reference ? " ({$reference})" : '';
        $descBase   = "HASIL LOADING {$boothName}{$refText}"; // ex: HASIL LOADING SPRAYBOOTH 5A (WOS MALAM A)

        AppHelpers::resetCode('AFG');
        $fgNumber = $this->getLastCode('AFG');

        DB::table('actual_finish_goods_hdr')->insert([
            'fg_code'       => $fgNumber,
            'loading_code'  => $loadingCode,
            'wos_reference' => $reference,
            'spray_booth'   => $sprayBooth,
            'fg_date'       => $fgDateDb,
            'num_revision'  => 0,
            'status'        => 1, // NEW
            'note'          => $note,
            'created_by'    => $username,
            'updated_by'    => $username,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        $seq          = (int) DB::table('warehouse_movement')->max('movement_code');
        $movementType = 'FINISH GOODS';
        $urutan       = 0;
        $savedRows    = 0;

        foreach ($articles as $val) {
            $qtyFg    = (float)($val->qty_fg  ?? 0);
            $qtyOt    = (float)($val->qty_ot  ?? 0);
            $qtyWip   = (float)($val->qty_wip ?? 0);
            $qtyTotal = $qtyFg + $qtyOt + $qtyWip;
            if ($qtyTotal <= 0) continue;

            $urutan++;
            $savedRows++;

            // stok fisik di gudang loading (038) harus cukup
            $avail = (float) DB::table('warehouse_stock')
                ->where('article_code', $val->article_code)
                ->where('location_number', $this->whLoading)
                ->sum('article_qty');

            if ($avail < $qtyTotal) {
                throw new \Exception(
                    "Stok loading (038) untuk {$val->article_code} tidak cukup ".
                    "(tersedia {$avail}, butuh {$qtyTotal})."
                );
            }

            DB::table('actual_finish_goods_det')->insert([
                'fg_code'      => $fgNumber,
                'loading_code' => $loadingCode,
                'urutan'       => $urutan,
                'article_code' => $val->article_code,
                'uom'          => $val->uom ?? null,
                'qty_loading'  => (float)($val->qty_loading ?? 0),
                'qty_wip'      => $qtyWip,
                'qty_fg'       => $qtyFg,
                'qty_ot'       => $qtyOt,
                'note'         => $val->note ?? null,
                'created_by'   => $username,
                'updated_by'   => $username,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);

            // FG->007, OT->008, WIP->012 (potong dari 038, booth cuma di desc)
            $this->postFgBucket($seq, $val->article_code, $val->uom, $qtyFg,  $this->whFg,   $movementType, $fgNumber, "{$descBase} - FG",  $username);
            $this->postFgBucket($seq, $val->article_code, $val->uom, $qtyOt,  $this->whFgOt, $movementType, $fgNumber, "{$descBase} - OT",  $username);
            $this->postFgBucket($seq, $val->article_code, $val->uom, $qtyWip, $this->whWip,  $movementType, $fgNumber, "{$descBase} - WIP", $username);
        }

        if ($savedRows === 0) {
            throw new \Exception("Tidak ada qty (FG/OT/WIP) yang diinput.");
        }

        // ── tutup Actual Loading: status jadi POSTED ──
        DB::table('actual_loading_hdr')
            ->where('prod_code', $loadingCode)
            ->update([
                'status'     => $this->loadingStatusDone,
                'updated_by' => $username,
                'updated_at' => $now,
            ]);

        DB::commit();
        $title   = "Save Actual Finish Goods";
        $message = "$title $fgNumber is successfully saved";
        \LogActivity::addToLog($title, "username: $username Status $message");
        return response()->json(['status'=>1,'title'=>$title,'message'=>$message,'alert'=>'success','fgNumber'=>$fgNumber,'oEdit'=>true]);

    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json(['status'=>0,'title'=>"Save Actual Finish Goods",'message'=>[[$e->getMessage()]],'alert'=>'error']);
    }
}

/** 1 bucket (FG/OT/WIP): potong dari gudang loading (038), masuk ke $toLoc. */
private function postFgBucket(&$seq, $article, $uom, $qty, $toLoc, $movementType, $transno, $desc, $username)
{
    if ($qty <= 0) return;
    $this->postOut($seq, $article, $qty, $this->whLoading, $toLoc, $movementType, $transno, $desc, $username);
    $this->postIn ($seq, $article, $uom, $qty, $toLoc, $this->whLoading, $movementType, $transno, $desc, $username);
}

/** Satu baris KELUAR: kurangi warehouse_stock sumber + movement (min) */
private function postOut(&$seq, $article, $qty, $fromLoc, $toLoc, $movementType, $transno, $desc, $username)
{
    if ($qty <= 0) return;
    $now   = date('Y-m-d H:i:s');
    $adesc = DB::table('article')->where('article_code',$article)->value('article_desc');

    DB::table('warehouse_stock')
        ->where('article_code',$article)->where('location_number',$fromLoc)
        ->update([
            'article_qty' => DB::raw('coalesce(article_qty,0) - '.$qty),
            'updated_by'  => $username,
            'updated_at'  => $now,
        ]);

    $this->writeMovement($seq, $article, $adesc, $qty, $fromLoc, 'out',
                         $fromLoc, $toLoc, $movementType, $transno, $desc, $username);
}

/** Satu baris MASUK: tambah warehouse_stock tujuan + movement (plus) */
private function postIn(&$seq, $article, $uom, $qty, $toLoc, $fromLoc, $movementType, $transno, $desc, $username)
{
    if ($qty <= 0) return;
    $now = date('Y-m-d H:i:s');

    $art   = DB::table('article')->where('article_code',$article)->select('article_desc','article_type')->first();
    $adesc = $art->article_desc ?? null;
    $dept  = $art->article_type ?? null;

    $exists = DB::table('warehouse_stock')
        ->where('article_code',$article)->where('location_number',$toLoc)->exists();

    if ($exists) {
        DB::table('warehouse_stock')
            ->where('article_code',$article)->where('location_number',$toLoc)
            ->update([
                'article_qty' => DB::raw('coalesce(article_qty,0) + '.$qty),
                'updated_by'  => $username,
                'updated_at'  => $now,
            ]);
    } else {
        DB::table('warehouse_stock')->insert([
            'site_code'       => 'HO',
            'article_code'    => $article,
            'location_number' => $toLoc,
            'article_qty'     => $qty,
            'uom'             => $uom,
            'dept_code'       => $dept,
            'created_by'      => $username,
            'updated_by'      => $username,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);
    }

    $this->writeMovement($seq, $article, $adesc, $qty, $toLoc, 'in',
                         $fromLoc, $toLoc, $movementType, $transno, $desc, $username);
}

/** Tulis 1 baris warehouse_movement. $direction: 'in'|'out'. Naikkan $seq. */
private function writeMovement(&$seq, $article, $adesc, $qty, $location, $direction,
                               $fromLoc, $toLoc, $movementType, $transno, $desc, $username)
{
    $sign  = ($direction === 'in') ? '+' : '-';
    $today = date('Y-m-d');

    DB::table('warehouse_movement')->insert([
        'movement_code'     => ++$seq,
        'movement_date'     => date('d-m-Y'),
        'site_code'         => 'HO',
        'location_number'   => $location,   // lokasi yg saldonya kena (OUT=038, IN=tujuan)
        'artikel_code'      => $article,
        'artikel_desc'      => $adesc,
        'movement_min'      => ($direction === 'out') ? $qty : 0,
        'movement_plus'     => ($direction === 'in')  ? $qty : 0,
        'movement_from'     => $fromLoc,    // 038 (sumber fisik)
        'movement_to'       => $toLoc,      // 007 / 008 / 012
        'movement_type'     => $movementType,
        'movement_transnno' => $transno,
        'movement_desc'     => $desc,       // "FG | Booth: SB1" dst
        'last_qty'          => DB::raw("get_last_qty_new('$article','$today','HO','$location') $sign $qty"),
        'created_by'        => $username,
        'created_at'        => date('Y-m-d H:i:s'),
    ]);
}

public function storeOld(Request $request)
{
    $username    = Auth::user()->username;
    $articles    = json_decode($request->articles);
    $fgDate      = $request->fgDate;
    $loadingCode = $request->loadingCode;
    $reference   = $request->reference;
    $note        = $request->note;

    $validation = Validator::make($request->all(), [
        'loadingCode' => 'required',
        'fgDate'      => 'required',
    ]);
    if ($validation->fails()) {
        $errs = [];
        foreach ($validation->messages()->getMessages() as $m) { $errs[] = $m; }
        return response()->json(['status'=>0,'title'=>"Save Actual Finish Goods",'message'=>$errs,'alert'=>'error']);
    }
    if (empty($articles)) {
        return response()->json(['status'=>0,'title'=>"Save Actual Finish Goods",'message'=>[['Tidak ada artikel yang diinput.']],'alert'=>'error']);
    }

    $fgDateDb = $fgDate ? implode('-', array_reverse(explode('-', $fgDate))) : date('Y-m-d');
    $now      = date('Y-m-d H:i:s');

    DB::beginTransaction();
    try {
        AppHelpers::resetCode('AFG');
        $fgNumber = $this->getLastCode('AFG'); // reuse pola getLastCode (romawi bulan)

        DB::table('actual_finish_goods_hdr')->insert([
            'fg_code'       => $fgNumber,
            'loading_code'  => $loadingCode,
            'wos_reference' => $reference,
            'fg_date'       => $fgDateDb,
            'num_revision'  => 0,
            'status'        => 1, // NEW
            'note'          => $note,
            'created_by'    => $username,
            'updated_by'    => $username,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        $urutan = 0;
        foreach ($articles as $val) {
            $urutan++;
            $qtyWip = (float)($val->qty_wip ?? 0);
            $qtyFg  = (float)($val->qty_fg  ?? 0);
            $qtyOt  = (float)($val->qty_ot  ?? 0);

            // lewati baris yg semua qty-nya 0
            if ($qtyWip <= 0 && $qtyFg <= 0 && $qtyOt <= 0) continue;

            DB::table('actual_finish_goods_det')->insert([
                'fg_code'      => $fgNumber,
                'loading_code' => $loadingCode,
                'urutan'       => $urutan,
                'article_code' => $val->article_code,
                'uom'          => $val->uom ?? null,
                'qty_loading'  => (float)($val->qty_loading ?? 0),
                'qty_wip'      => $qtyWip,
                'qty_fg'       => $qtyFg,
                'qty_ot'       => $qtyOt,
                'note'         => $val->note ?? null,
                'created_by'   => $username,
                'updated_by'   => $username,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }

        DB::commit();
        $title   = "Save Actual Finish Goods";
        $message = "$title $fgNumber is successfully saved";
        \LogActivity::addToLog($title, "username: $username Status $message");
        return response()->json(['status'=>1,'title'=>$title,'message'=>$message,'alert'=>'success','fgNumber'=>$fgNumber,'oEdit'=>true]);

    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json(['status'=>0,'title'=>"Save Actual Finish Goods",'message'=>[[$e->getMessage()]],'alert'=>'error']);
    }
}

    public function createOld(Request $request)
    {
        $data['title'] = "Input $this->title";
        $data['subtitle'] = "Input $this->title";

        $data['listWo'] = DB::table('wo_hdr')
        ->where('status','=','3')
        ->whereNotIn('wo_hdr.wo_code', function($query) {
            $query->select('wo_code')->from('production_hdr')->where('status','<>','5');
        })
        ->select('wo_hdr.*',DB::raw("to_char(wo_date, 'DD-MM-YYYY') as tanggal"))
        ->get();

        $data['statusPrd'] = 'NEW';
        $data['oEdit'] = false;
               
        return view("production.actualFinishGoods.create",$data);

    }

    public function posting(Request $request)
    {

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'CLOSED','7'=>'REVISED','8'=>'INPUT FG','9'=>'POSTED FG'];

        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $prdNumber = DB::table('production_hdr')
                    ->where('id',$id)
                    ->where('status','=','8')
                    ->value('prod_code');
        $siteCode = 'HO';
        $location ='WH';
        $status = '9';
        $moduleCode = $this->moduleCode;
        $todayDate = date('Y-m-d');
        $movementDate = date("d-m-Y");
        
        if ($prdNumber){
            $data = DB::table('production_det')
            ->leftJoin('production_hdr','production_hdr.prod_code','production_det.prod_code')
            ->leftJoin('article','article.article_code','production_det.article_code')
            ->where('production_det.prod_code',$prdNumber)
            ->where('production_hdr.status','8')
            ->where('production_det.so_code','<>','other')
            ->where('tone',db::raw("(select max(tone) from bom_det where bom_code in (select bom_code from bom_hdr where article_code = production_det.article_code))"))
            ->select('production_det.*'
            ,'article.article_type'
            ,'article.uom as uom_article'
            ,'production_det.act_finish_goods as total_qty'
            )
            ->get();

            foreach($data as $val){
                //insert article code kalo belum ada di tabel item_stock
                DB::table('article_stock')
                ->updateOrInsert(
                    [ 'site_code' =>$siteCode,
                      'article_code' => $val->article_code,
                      'location_number'=> $location
                    ],
                    [
                      'dept_code'=>$val->article_type,
                      'uom'=>$val->uom_article,
                    ]
                );

                DB::table('article_stock')
                ->updateOrInsert(
                    [ 'site_code' =>$siteCode,
                      'article_code' => $val->article_rm_code,
                      'location_number'=> $location
                    ],
                    [
                      'dept_code'=>$val->article_type,
                      'uom'=>$val->uom_article,
                    ]
                );

                //update qty nya ditambahkan dengan qty baru FG
                $rowAffectedFg = DB::table('article_stock')
                ->where('site_code',$siteCode)
                ->where('article_code',$val->article_code)
                ->where('location_number',$location)
                ->update([
                    'article_qty' => DB::raw('coalesce(article_qty,0) + '.$val->total_qty)
                ]);

            }
                    
            if ($rowAffectedFg > 0){
                DB::table('production_hdr')
                ->where('prod_code',$prdNumber)
                ->update(
                    [   
                        'status' => $status,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                $movementFg = DB::table('production_det')
                ->leftJoin('production_hdr','production_hdr.prod_code','production_det.prod_code')
                ->leftJoin('article','article.article_code','production_det.article_code')
                ->where('production_det.prod_code',$prdNumber)
                ->where('production_hdr.status','9')
                ->where('act_finish_goods', '<>', 0)
                ->where('production_det.so_code','<>','other')
                ->select(
                    DB::RAW("'$movementDate' as movement_date" )
                    ,'production_det.article_code'
                    ,'article.article_desc'
                    ,DB::raw("0 as movement_min")
                    ,DB::RAW("(production_det.act_finish_goods) as movement_plus")
                    ,DB::raw("0 as movement_price ")
                    ,'production_hdr.prod_code as movement_transnno'
                    ,DB::raw("'$moduleCode' as movement_type")
                    ,'production_hdr.wo_code as movement_desc'
                )
                ->get();
                
                $dataSetMovementFg = [];
                foreach ($movementFg as $val) {
                    $dataSetMovementFg[] = [
                        'movement_date' => $val->movement_date,
                        'artikel_code' => $val->article_code,
                        'artikel_desc' => $val->article_desc,
                        'movement_min' => $val->movement_min,
                        'movement_plus' => $val->movement_plus,
                        'movement_price' => $val->movement_price,
                        'movement_transnno' => $val->movement_transnno,
                        'movement_type' => $val->movement_type,
                        'movement_desc' => $val->movement_desc,
                        'created_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'site_code' => $siteCode,
                        'location_number' => $location,
                        'last_qty' => DB::raw("get_last_qty('$val->article_code','$todayDate','$siteCode','$location') + ($val->movement_min+$val->movement_plus)")
                    ];
                }

                DB::table('movement')->insert($dataSetMovementFg);

                DB::commit();
                $title ="Posting $this->title";
                $alert  ="success";
                $message  = "$title $prdNumber Successfully Posted";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
            }else{
                $title ="Posting $this->title";
                $alert  ="warning";
                $message  = "$title $prdNumber Failed to Posting";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
            }
        }else{
            $title ="Posting $this->title";
            $alert  ="warning";
            $message  = "$title $prdNumber Failed to Posting1";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }

    }

    public function show(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;
        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";

        $data['headers'] = DB::table('production_hdr')
        ->where('original_prod_code', function($query) use ($id){
            $query->select('prod_code')->from('production_hdr')->where('id',$id);
        })
        ->select('production_hdr.*'
        ,DB::raw("(working_hour*3600*(efficiency/100))/30 as sum_time_required")
        ,DB::raw("(select sum(plan_tag) from production_det where prod_code=production_hdr.prod_code) as sum_available_time")
        )
        ->orderBy('id')
        ->get();

        $prdNumber = $data['headers'][0]->prod_code;

        $data['details'] = DB::table('production_det')
        ->leftJoin('article','article.article_code','=','production_det.article_code')
        ->whereIn('production_det.prod_code', function($query) use ($prdNumber){
            $query->select('prod_code')->from('production_hdr')->where('original_prod_code',$prdNumber);
        })
        ->where('so_code','<>','other')
        ->select('production_det'.'.*'
        ,'article.article_alternative_code'
        ,'article.article_desc'
        ,DB::raw("case when so_code ='other' then production_det.article_code else concat(article.article_alternative_code,' - ',article.article_desc) end as article")
        )
        ->orderBy('urutan')
        ->get();

        $data['arrTone'] = ['t1'=>'Tone 1','t2'=>'Tone 2','t3'=>'Tone 3','t4'=>'Tone 4'];
        $data['arrSprayBooth'] = ['sb1'=>'Spray Booth 1','sb2'=>'Spray Booth 2','sb3'=>'Spray Booth 3','sb4'=>'Spray Booth 4'];

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$prdNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$prdNumber,$username);

        $data['oEdit']=true;

         // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'CLOSED','7'=>'REVISED','8'=>'INPUT FG','9'=>'POSTED FG'];
        $statusPrd = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED','CLOSED','REVISED','INPUT FG','POSTED FG'];
        $data['statusPrd'] = $statusPrd[$data['headers'][0]->status-1];

        return view("production.actualFinishGoods.show",$data);
        
    }

    public function edit(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;
        $data['title'] = "Input $this->title";
        $data['subtitle'] = "Input $this->title";

        $data['header'] = DB::table('production_hdr')
        ->where('id',$id)
        ->get()->first();

        $prdNumber = $data['header']->prod_code;

        $data['details'] = DB::table('production_det')
        ->leftJoin('article','article.article_code','=','production_det.article_code')
        ->where('prod_code',$prdNumber)
        ->where('so_code','!=','other')
        ->select('production_det.*'
        ,DB::RAW("concat(article.article_alternative_code,article.article_desc) as article")
        ,'article.article_alternative_code'
        ,'article.article_desc')
        ->orderBy('id')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$prdNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$prdNumber,$username);

        $data['oEdit']=true;

        $data['arrSprayBooth'] = ['sb1'=>'Spray Booth 1','sb2'=>'Spray Booth 2','sb3'=>'Spray Booth 3','sb4'=>'Spray Booth 4'];

        
        // $status = ['NEW','VALIDATED','APPROVED ACT LOADING','POSTED WO','CANCELED','CLOSED','REVISED','INPUT FG','POSTED FG'];
        $statusPrd = ['NEW','VALIDATED','APPROVED ACT LOADING','POSTED WO','CANCELED','CLOSED','REVISED','INPUT FG','POSTED FG'];
        $data['statusPrd'] = $statusPrd[$data['header']->status-1];

        return view("production.actualFinishGoods.edit",$data);
        
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request -> articles);
        $prdNumber = $request->prdNumber;
        $wosNumber = $request->wosNumber;
        $wosTime = $request->wosTime;
        $workHour = $request->workHour;
        $efficiency = $request->efficiency;
        $note = $request->note;
        $prdDate = date("Y-m-d");
        $oEdit = true;

        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
            // 'iunique' => "PO Number has already been taken",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $validation = Validator::make($request->all(),$messages = [
            'wosNumber'  => 'required' 
        ]);
        
        $error_array = array();
        $success_output = '';
        // return $validation;
        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }
            $alert ="alert-danger";
            return response()->json(array('status' => 0, 'message' => $error_array,'alert' =>$alert));
        }else{
            DB::beginTransaction();
            try {
                $row_affected=DB::table('production_hdr')
                ->where('prod_code',$prdNumber)
                ->update(
                    [
                        'status' => '8',
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                $dataSet = [];
                foreach ($articles as $val) {
                    DB::table('production_det')
                    ->where("prod_code", $prdNumber)
                    ->where("urutan", $val->urutan)
                    ->where("so_code",$val->so_code)
                    ->where("article_code",$val->article_code)
                    ->where("article_rm_code",$val->article_rm)
                    ->update(
                        [
                            "act_finish_goods" => $val->act_qty_fg,
                        ]
                    );
                }
                                    
                DB::commit();
                $title ="Update $this->title";
                $alert  ="success";
                $message  = "$title $prdNumber is successfully updated";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'prdNumber'=>$prdNumber,'oEdit'=>$oEdit));

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Update $this->title";
                $alert  ="warning";
                $message  = "$title $prdNumber is failed to update";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'prdNumber'=>$prdNumber,'oEdit'=>$oEdit));
            }
        }

    }

    public function destroy(Request $request)
    {
        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);
        $prdNumber = DB::table('production_hdr')->where('id',$id)->where('status','1')->value('prod_code');
        $rowAffected = DB::table('production_hdr')->where('id',$id)->delete();
        if($rowAffected>0){
            DB::table('production_det')->where('prod_code',$prdNumber)->delete();
            $title ="Delete $this->title";
            $alert  ="success";
            $message  = "$title $prdNumber Successfully Deleted";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);  
        }else{
            $title ="Delete $this->title";
            $alert  ="warning";
            $message  = "$title $prdNumber Failed to Delete";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }
    }

    public function list(Request $request)
    {
        $searchPrd = strtolower($request->searchPrd);
        $searchWos = strtolower($request->searchWos);
        $prdDate = $request->prdDate;
        $wosDate = $request->wosDate;
        $searchStatus = $request->searchStatus;

        $fromDate ="";
        $toDate = "";

        $fromDate1 ="";
        $toDate1 = "";

        if ($wosDate){
            $date = explode("to",$wosDate);
            if(count($date)>1){
                $fromDate = implode("-", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("-", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("-", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate; 
            }
        }

        if ($prdDate){
            $date1 = explode("to",$prdDate);
            if(count($date1)>1){
                $fromDate1 = implode("-", array_reverse(explode("-", trim($date1[0]))));
                $toDate1 = implode("-", array_reverse(explode("-", trim($date1[1]))));
            }else{
                $fromDate1 = implode("-", array_reverse(explode("-", trim($date1[0]))));
                $toDate1 = $fromDate1; 
            }
        }

        $data = DB::table('production_hdr')
        ->leftJoin('wo_hdr','wo_hdr.wo_code','production_hdr.wo_code')
        ->where(function ($query) use ($searchPrd,$searchWos,$wosDate,$prdDate,$fromDate,$fromDate1,$toDate,$toDate1) {
            $searchPrd ? $query->where('prod_code','ilike','%'.$searchPrd.'%') : '';
            $searchWos ? $query->where('wo_code','ilike','%'.$searchWos.'%') : '';
            $wosDate ? $query->whereBetween(DB::raw("wo_date"), [$fromDate, $toDate]) : '';
            $prdDate ? $query->whereBetween(DB::raw("prod_date"), [$fromDate1, $toDate1]) : '';
        })
        ->where('production_hdr.status','<>', '7')
        ->select('production_hdr.*','wo_hdr.wo_date')
        ->orderBy('prod_code')
        ->get(); 

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';

            if (Auth::user()->can('actualFinishGoods-edit')) {
                if($data->status == '4' || $data->status == '8'){
                    $buttons .=         '<a href="'. route('production.actualFinishGoods.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="file-text"></i>
                                        Input Finish Goods
                                    </a>';
                }
            }

            // $buttons .=         '<a href="'. route('production.actualFinishGoods.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
            //                         <i data-feather="printer"></i>
            //                         Print
            //                     </a>';

            if (Auth::user()->can('actualFinishGoods-posting')) {
                if($data->status == '8'){
                    $buttons .="<a href='javascript:;'
                    class='dropdown-item' 
                    data-size='sm'
                    data-ajax-delete='true'
                    data-confirm='Are You Sure want to post This number?' 
                    data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                    data-modal-id='".$data->id."'
                    data-url='". route('production.actualFinishGoods.posting', ['id'=>Crypt::encryptString($data->id)]) ."'>
                    <i data-feather='check' class='feather-14-red'></i>
                    <span>". __('Posting') ."</span>
                    </a>";
                }
            }

            // if (Auth::user()->can('actualFinishGoods-revision')) {
            //     if (($data->status == '2') || ($data->status == '3') ){
            //         $buttons .=         '<a href="'. route('production.actualFinishGoods.revision', ['id'=>Crypt::encryptString($data->id),'nR'=>$data->num_revision]) .'" class="dropdown-item">
            //                                 <i data-feather="copy"></i>
            //                                 <span>'. __("Revision") .'</span>
            //                             </a>';
            //     }
            // }
            
            $buttons .=         '<a href="'. route('production.actualFinishGoods.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    Detail
                                </a>';
                
            // if (Auth::user()->can('actualFinishGoods-delete')) {
            //     if ( $data->status == '1' ){
            //         if (Auth::user()->can('workingOrder-delete')) {
            //             $buttons .=         "<a href='javascript:;'
            //                                 class='dropdown-item' 
            //                                 data-size='sm'
            //                                 data-ajax-delete='true'
            //                                 data-confirm='Are You Sure want to Delete?|This action can not be undone. Do you want to continue?' 
            //                                 data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
            //                                 data-modal-id='".$data->id."'
            //                                 data-url='". route('production.actualFinishGoods.destroy', ['id'=>Crypt::encryptString($data->id)]) ."'>
            //                                 <i data-feather='trash-2' class='feather-14-red'></i>
            //                                 <span>". __('Delete') ."</span>
            //                             </a>";
            //         }
            //     }
            // }
            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })

        ->addColumn('status', function ($data) {
            // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'CLOSED','7'=>'REVISED','8'=>'INPUT FG','9'=>'POSTED FG'];
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-primary','badge-warning'];
            $status = ['NEW','VALIDATED','APPROVED ACT LOADING','POSTED WO','CANCELED','CLOSED','REVISED','INPUT FG','POSTED FG'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$status[$data->status - 1]."</div>";
        })
        ->rawColumns(['action','status'])
        ->make(true);
    }

    public function listDetail(Request $request)
    {
       
        $searchPrd = strtolower($request->searchPrd);
        $searchWos = strtolower($request->searchWos);
        $prdDate = $request->prdDate;
        $wosDate = $request->wosDate;
        $searchStatus = $request->searchStatus;

        $fromDate ="";
        $toDate = "";

        $fromDate1 ="";
        $toDate1 = "";

        if ($wosDate){
            $date = explode("to",$wosDate);
            if(count($date)>1){
                $fromDate = implode("-", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("-", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("-", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate; 
            }
        }

        if ($prdDate){
            $date1 = explode("to",$prdDate);
            if(count($date1)>1){
                $fromDate1 = implode("-", array_reverse(explode("-", trim($date1[0]))));
                $toDate1 = implode("-", array_reverse(explode("-", trim($date1[1]))));
            }else{
                $fromDate1 = implode("-", array_reverse(explode("-", trim($date1[0]))));
                $toDate1 = $fromDate1; 
            }
        }

        $data = DB::table('production_det')
        ->leftJoin('production_hdr','production_hdr.prod_code','production_det.prod_code')
        ->leftJoin('wo_hdr','wo_hdr.wo_code','production_hdr.wo_code')
        ->leftJoin('article as a','a.article_code','production_det.article_code')
        ->leftJoin('article as b','b.article_code','production_det.article_rm_code')
        ->where(function ($query) use ($searchPrd,$searchWos,$wosDate,$prdDate,$fromDate,$fromDate1,$toDate,$toDate1) {
            $searchPrd ? $query->where('production_det.prod_code','ilike','%'.$searchPrd.'%') : '';
            $searchWos ? $query->where('production_hdr.wo_code','ilike','%'.$searchWos.'%') : '';
            $wosDate ? $query->whereBetween(DB::raw("wo_hdr.wo_date"), [$fromDate, $toDate]) : '';
            $prdDate ? $query->whereBetween(DB::raw("production_hdr.prod_date"), [$fromDate1, $toDate1]) : '';
        })
        ->where('production_hdr.status','<>', '7')
        ->where('production_det.so_code','<>', 'other')
        ->select('production_det.*'
        ,'production_hdr.*'
        ,'wo_hdr.wo_date'
        ,'a.article_alternative_code as article_code_fg'
        ,'a.article_desc as article_desc_fg'
        ,'b.article_alternative_code as article_code_rm'
        ,'b.article_desc as article_desc_rm'
        )
        ->orderBy('production_det.prod_code')
        ->orderBy('urutan')
        ->get(); 
                        
        return Datatables::of($data)
        ->make(true);
    }

    public function print(Request $request)
    {
        $id=Crypt::decryptString($request->id);

        $data['header']=DB::table('production_hdr')
        ->where('id',$id)
        ->select('production_hdr.*'
        ,DB::raw("(SELECT sum(act_tag) as total_tag from production_det where wo_code = production_hdr.wo_code) as total_tag")
        )
        ->first();

        $prdNumber=$data['header'] -> prod_code;
       
        $data['details']=DB::table('production_det')
        ->leftJoin('article','article.article_code','production_det.article_code')
        ->select('production_det.*'
        ,'article.article_alternative_code'
        ,'article.article_desc'
        ,DB::raw("(SELECT article_qty from article_stock where article_code = production_det.article_rm_code and site_code ='HO' and location_number='WH') as qty_rm")
        )
        ->where('prod_code',$prdNumber)
        ->orderBy('urutan','asc')
        ->get();

        // $data['totals']=DB::select("SELECT sum(plan_tag) as total_tag from wo_det where wo_code = '$prdNumber'");

        $data['prdNumber'] = $prdNumber;
        $data['no'] = 0;

        $data['title'] = $prdNumber;


        view()->share($data);

        $pdf = PDF::loadView('production.actualFinishGoods.print');
        return $pdf->stream("$prdNumber.pdf");

    }

    public function approve(Request $request)
    {
        $username =  Auth::user()->username;
        $prdNumber = $request->prdNumber;
        $statusLevelApproval = Approval::approvalLevelPosition($this->moduleCode,$prdNumber,$username);        
        $nextLevel = $statusLevelApproval[0]->next_level;
        $status = $statusLevelApproval[0]->next_level == $statusLevelApproval[0]->max_level ? '3' :'2';
                
        DB::beginTransaction();
        try {
                $row_affected=DB::table('production_hdr')
                ->where('prod_code',$prdNumber)
                ->update(
                    [
                        'status' => $status,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                if ($row_affected){
                    DB::table('approval_history')->insert([
                        'module_code' => $this->moduleCode,
                        'module_number' => $prdNumber,
                        'username' => Auth::user()->username,
                        'approval_order' => $nextLevel,
                        'approval_date' => date('Y-m-d'),
                        'status' => 1,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
                
                DB::commit();
                $title ="Approve $this->title";
                $alert  ="success";
                $message  = "$title $prdNumber is successfully Approve-".$nextLevel;
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('statusWo' => $status,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'prdNumber'=>$prdNumber));

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Approve $this->title";
            $alert  ="warning";
            $message  = "$title $prdNumber is failed to Approve-".$nextLevel;
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('statusWo' => $status,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'prdNumber'=>$prdNumber));
        }
    }

    public function revision(Request $request){
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $prdOrigin=DB::table('production_hdr')->where('id',$id)->value('prod_code');
        $numRevision = $request->nR ? $request->nR +1 : 1 ;
        $prdNew = $prdOrigin.'-R'.$numRevision;
        $checkNewPrd=DB::table('production_hdr')->where('prod_code',$prdNew)->count();

        if ($checkNewPrd > 0){
            $prdNew = $prdOrigin.'-R'.$numRevision;
        } 
                
        $sqlHdr = "INSERT into production_hdr 
        (
            prod_code,
            wo_code,
            original_prod_code,
            prod_date,
            prod_shift,
            prod_group,
            start_time,
            working_hour,
            num_revision,
            status,
            note,
            created_by,
            updated_by,
            created_at,
            updated_at
        )
        select 
            '$prdNew',
            wo_code,
            '$prdOrigin',
            prod_date,
            prod_shift,
            prod_group,
            start_time,
            working_hour,
            $numRevision,
            '7',
            note,
            '$username',
            '$username',
            '".date('Y-m-d H:i:s')."',
            '".date('Y-m-d H:i:s')."'
        from production_hdr where prod_code = '$prdOrigin'";

        $sqlDet="INSERT into production_det
        (
            prod_code,
            so_code,
            so_qty,
            urutan,
            article_code,
            article_rm_code,
            plan_time_loading,
            act_time_loading,
            plan_qty_fresh,
            plan_qty_repaint,
            plan_tag,
            act_qty_fresh,
            act_qty_repaint,
            act_tag,
            origin_tag,
            qty_ok,
            qty_repair,
            qty_repaint,
            note,
            status,
            created_by,
            updated_by,
            created_at,
            updated_at,
            tone
        )
        select '$prdNew',
            so_code,
            so_qty,
            urutan,
            article_code,
            article_rm_code,
            plan_time_loading,
            act_time_loading,
            plan_qty_fresh,
            plan_qty_repaint,
            plan_tag,
            act_qty_fresh,
            act_qty_repaint,
            act_tag,
            origin_tag,
            qty_ok,
            qty_repair,
            qty_repaint,
            note,
            status,
            '$username',
            '$username',
            '".date('Y-m-d H:i:s')."',
            '".date('Y-m-d H:i:s')."',
            tone
        from production_det where prod_code = '$prdOrigin'";

        $rowAffected =  DB::select($sqlHdr);
        if ($rowAffected){
            DB::select($sqlDet);

            // status:
            // 1 = New
            // 2 = Validated
            // 3 = Authorized
            // 4 = Received
            // 5 = Canceled
            // 6 = closed
            // 7 = Revised

            DB::table('production_hdr')
            ->where('prod_code',$prdOrigin)
            ->update(
                [
                    'num_revision' => $numRevision,
                    'status' => '1',
                    'revised_by'=>Auth::user()->username,
                    'revised_at'=> date('Y-m-d H:i:s'),
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );

            DB::table('approval_history')
            ->where('module_number',$prdOrigin)
            ->update(
                [
                    'module_number' => $prdNew,
                    'status' => '0',
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );
            
            $title ="Save $this->title";
            $alert  ="success";
            $message  = "$title Revision PRD: $prdOrigin to $prdNew is successfully saved";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->route('production.actualFinishGoods.edit', ['id'=>Crypt::encryptString($id)]);
        }else{
            $title ="Save $this->title";
            $alert  ="warning";
            $message  = "$title Revision PRD: $prdOrigin to $prdNew is failed to save";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
        }
    }

    public function export(Request $request)
    {
		$wosNumber = $request->wos_number;
        $prdNumber = $request->prd_number;
        $filename = str_replace('/','_', $prdNumber);
        return Excel::download(new ActualFinishGoodsExport($wosNumber,$prdNumber), $filename.'.xlsx');
	}

    public function importExcel(Request $request)
    {

        $prdNumber = $request->aprdNumber;

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
        db::table('import_actual_finish_goods_tmp')->delete();
        Excel::import(new ActualFinishGoodsImport($data), $file);

        $dataValidasi = DB::table('import_actual_finish_goods_tmp')
        ->leftJoin('article','article.article_alternative_code','import_actual_finish_goods_tmp.article_code')
        ->select('import_actual_finish_goods_tmp.article_code'
        ,'article_desc'
        ,'qty_finish_goods'
        ,DB::RAW("concat(
            case when import_actual_finish_goods_tmp.qty_finish_goods::text ~ '^[0-9.]+$' = false then concat('Urutan ',row_number() over(),': Qty Actual Finish Goods salah : ',qty_finish_goods,'<br>') end,
            case when article_desc is null and import_actual_finish_goods_tmp.article_code <> 'gantiwarna' and import_actual_finish_goods_tmp.article_code <> 'istirahat' then concat('Urutan ',row_number() over(),': Article Code:',import_actual_finish_goods_tmp.article_code, ' tidak terdaftar <br>') end,
            case when import_actual_finish_goods_tmp.prod_code != '$prdNumber' then concat('Urutan ',row_number() over(),': Production Number:',import_actual_finish_goods_tmp.prod_code, ' tidak sesuai <br>Seharusnya $prdNumber') end
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

            $data = DB::table('production_det')
            ->leftJoin('import_actual_finish_goods_tmp', function ($join) {
                $join->on('import_actual_finish_goods_tmp.prod_code', '=', 'production_det.prod_code');
                $join->on('import_actual_finish_goods_tmp.urutan', '=', 'production_det.urutan');
                $join->on('import_actual_finish_goods_tmp.article_code_1', '=', 'production_det.article_code');
            })
            ->leftJoin('article','article.article_code','=','production_det.article_code')
            ->where('production_det.prod_code',$prdNumber)
            ->where('production_det.so_code','!=','other')
            ->select('production_det.*'
            ,DB::RAW("concat(article.article_alternative_code,article.article_desc) as article")
            ,'article.article_alternative_code'
            ,'article.article_desc'
            ,'import_actual_finish_goods_tmp.qty_finish_goods as qty_finish_goods')
            ->orderBy('id')
            ->get();

            $status = 1;
            $alert = "success";
            $message  = "$title is successfully imported";

            db::table('import_actual_finish_goods_tmp')->where('file_name', $namaFile)->delete();

        }
                  
        return response()->json(array('status' => $status,'title' => $title, 'message' => $message,'alert' =>$alert,'dataDetail'=>$data,'pesan'=>$pesan));
    }
}
