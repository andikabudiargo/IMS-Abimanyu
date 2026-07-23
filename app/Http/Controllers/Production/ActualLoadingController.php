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
use App\Exports\ActualLoadingExport;
use App\Imports\ActualLoadingImport;
use Maatwebsite\Excel\Facades\Excel;

class ActualLoadingController extends Controller
{
    private $title;
    private $moduleCode;
    public function __construct()
    {
        $this->title = "Actual Loading";
        $this->moduleCode = "PRD";
    }

    public function getTableColoumn()
    {
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=> false,'searchable'=>false],
            ['data'=>'prod_code','name'=>'prod_code','title'=>'Prod. Code'],
            ['data'=>'prod_date','name'=>'prod_date','title'=>'Prod. Date'],
            ['data'=>'spraybooth','name'=>'spraybooth','title'=>'Spray Booth'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'num_revision','name'=>'num_revision','title'=>'Revision'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created At'],
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
            ['data'=>'article_code_fg','name'=>'article_code_fg','title'=>'Article Code'],
            ['data'=>'article_desc_fg','name'=>'article_desc_fg','title'=>'Article Desc'],
            ['data'=>'article_code_rm','name'=>'article_code_rm','title'=>'Article Code RM'],
            ['data'=>'article_desc_rm','name'=>'article_desc_rm','title'=>'Article Desc RM'],
            ['data'=>'qty_fresh','name'=>'qty_fresh','title'=>'Qty Fresh'],
            ['data'=>'qty_repaint','name'=>'qty_repaint','title'=>'Qty Repaint'],
            ['data'=>'note','name'=>'note','title'=>'Note']
        ];

        return json_encode($kolom, true);
    }

    public function getTableColoumnOld()
    {
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=> false,'searchable'=>false],
            ['data'=>'prod_code','name'=>'prod_code','title'=>'Prod. Code'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'prod_date','name'=>'prod_date','title'=>'Prod. Date'],
            ['data'=>'wo_code','name'=>'wo_code','title'=>'WOS. Code'],
            ['data'=>'wo_date','name'=>'wo_date','title'=>'WOS. Date'],
            ['data'=>'num_revision','name'=>'num_revision','title'=>'Revision'],
            ['data'=>'prod_shift','name'=>'wo_shift','title'=>'Shift'],
            ['data'=>'prod_group','name'=>'wo_group','title'=>'Group'],
            ['data'=>'start_time','name'=>'start_time','title'=>'Start Time'],
            ['data'=>'working_hour','name'=>'working_hour','title'=>'Working Hour'],
            ['data'=>'efficiency','name'=>'efficiency','title'=>'Efficiency']
        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnDetailOld()
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
            ['data'=>'act_qty_repaint','name'=>'act_qty_repaint','title'=>'Act Qty Repaint']            
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
            
        return view("production.actualLoading.index",$data);
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

    // Format: PRD-ASN-{bulan romawi}-{tahun}-{codeNumber}
    $prdNumber = "$key-ASN-$monthRoman-$year-$codeNumber";

    return $prdNumber;
}

/**
 * Konversi bulan (1-12) ke angka romawi (I-XII).
 */
private function toRomanMonth(int $month): string
{
    $romans = [
        1 => 'I',   2 => 'II',  3 => 'III', 4 => 'IV',
        5 => 'V',   6 => 'VI',  7 => 'VII', 8 => 'VIII',
        9 => 'IX',  10 => 'X',  11 => 'XI', 12 => 'XII',
    ];

    return $romans[$month] ?? '';
}

    public function getLastCodeOld($key)
    {
        DB::table('master_code')
        ->where('code_key',$key)
        ->update([
            'code_number' => DB::raw('code_number + 1'),
            'updated_by' => Auth::user()->username,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $newCode = DB::table('master_code')
        ->where('code_key',$key)
        ->value('code_number'); 
        $month = date('n');
        $year = date('Y');
        $prdNumber="$key/$year/$month/$newCode";
        
        return $prdNumber;
    }

    public function create(Request $request)
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

    // ── NEW: daftar Spray Booth ──
    $data['sprayBooths'] = DB::table('stock_location_master')
        ->where('location_type', 'booth')
        ->orderBy('location_name')
        ->get();

    $data['statusPrd'] = 'NEW';
    $data['oEdit'] = false;

    return view("production.actualLoading.create",$data);
}

/**
 * Ambil daftar FG yang bisa diproduksi di Spray Booth:
 * - FG punya BOM aktif dengan komponen RM bertipe RMP/RMNP
 * - FG tersebut ada stoknya (qty > 0) di lokasi ber-type 'wip'
 *
 * NOTE: asumsi tabel stok FG adalah 'stock' (location_code, article_code, qty)
 * — sesuaikan kalau ternyata pakai 'warehouse_stock' (location_number).
 */
public function articleBySprayBooth(Request $request)
{
    $locationCode = $request->location_code;
 
    $isBooth = DB::table('stock_location_master')
        ->where('location_code', $locationCode)
        ->where('location_type', 'booth')
        ->exists();
 
    if (!$isBooth) {
        return response()->json([]);
    }
 
    $fgList = DB::table('bom_hdr as bh')
        ->join('bom_rm as br', 'br.bom_code', '=', 'bh.bom_code')
        ->join('article as arm', 'arm.article_code', '=', 'br.article_code')
        ->join('article as afg', 'afg.article_code', '=', 'bh.article_code')
        ->where('bh.status', '3')
        ->whereIn('arm.article_type', ['RMP', 'RMNP'])
        // Munculkan FG hanya jika minimal ADA salah satu RM komponennya yang stock > 0 di booth ini
        ->whereExists(function ($q) use ($locationCode) {
            $q->select(DB::raw(1))
              ->from('bom_rm as br3')
              ->join('article as arm3', 'arm3.article_code', '=', 'br3.article_code')
              ->whereColumn('br3.bom_code', 'bh.bom_code')
              ->whereIn('arm3.article_type', ['RMP', 'RMNP'])
              ->whereRaw("
                  coalesce((
                      select sum(article_qty)
                      from warehouse_stock
                      where article_code = br3.article_code
                        and location_number = ?
                  ), 0) > 0
              ", [$locationCode]);
        })
        ->select(
            'afg.article_code',
            'afg.article_alternative_code',
            'afg.article_desc',
            'afg.uom',
            DB::raw("(select string_agg(unit_to,',' order by unit_from) from uom_con_v2 where article_code = afg.article_code) as uom_member"),
           DB::raw("(
    select greatest(coalesce(min(floor(greatest(coalesce(ws.total_qty,0),0) / nullif(br2.qty,0))),0),0)
    from bom_rm br2
    join article arm2
        on arm2.article_code = br2.article_code
       and arm2.article_type in ('RMP','RMNP')
    left join (
        select article_code, sum(article_qty) as total_qty
        from warehouse_stock
        where location_number = '$locationCode'
        group by article_code
    ) ws on ws.article_code = br2.article_code
    where br2.bom_code = bh.bom_code
) as stock_rm_fresh")
           DB::raw("(
    select coalesce(sum(greatest(t.qty,0)),0)
    from (
        select ws.location_number, sum(ws.article_qty) as qty
        from warehouse_stock ws
        join stock_location_master slm on slm.location_code = ws.location_number
        where ws.article_code = afg.article_code
          and slm.location_type = 'wip'
        group by ws.location_number
    ) t
) as stock_fg_repaint")
        )
        ->distinct()
        ->orderBy('afg.article_alternative_code')
        ->get();
 
    // ── Gabung jadi satu angka: Max FG ──
   $fgList = $fgList->map(function ($r) {
    $fresh   = max(0, (float) ($r->stock_rm_fresh   ?? 0));
    $repaint = max(0, (float) ($r->stock_fg_repaint ?? 0));
    $r->max_fg = $fresh + $repaint;
    return $r;
})->values();
 
    return response()->json($fgList);
}
 
/**
 * Detail 1 FG di spray booth terpilih:
 *  - breakdown RM (kebutuhan BOM vs stock di booth)
 *  - breakdown stok FG jadi di gudang ber-type WIP (sumber repaint)
 *
 * Catatan logika status:
 *  - is_limiting : RM ini yang jadi batas kapasitas fresh (max_fg == kapasitas keseluruhan).
 *                  Kalau RM cuma 1, dia OTOMATIS limiting — itu wajar, bukan masalah.
 *  - is_critical : baru dianggap masalah kalau kapasitas 0, ATAU dia limiting
 *                  SEKALIGUS ada RM lain yang kapasitasnya lebih tinggi.
 *                  Ini yang bikin baris jadi merah di popup.
 */
public function rmDetailBySprayBooth(Request $request)
{
    $locationCode = $request->location_code;
    $articleCode  = $request->article_code; // kode article FG
 
    // ── 1. Stok FG jadi di gudang WIP (buat repaint) ──
    $wipRows = DB::table('warehouse_stock as ws')
        ->join('stock_location_master as slm', 'slm.location_code', '=', 'ws.location_number')
        ->join('article as a', 'a.article_code', '=', 'ws.article_code')
        ->where('ws.article_code', $articleCode)
        ->where('slm.location_type', 'wip')
        ->select(
            'slm.location_code',
            'slm.location_name',
            'a.uom',
            DB::raw('sum(ws.article_qty) as qty')
        )
        ->groupBy('slm.location_code', 'slm.location_name', 'a.uom')
        ->havingRaw('sum(ws.article_qty) > 0')   // sebelumnya <> 0
        ->orderBy('slm.location_name')
        ->get();
 
    $wipTotal = (float) $wipRows->sum('qty');
 
    // ── 2. Breakdown RM dari BOM aktif ──
    $rows = DB::table('bom_hdr as bh')
        ->join('bom_rm as br', 'br.bom_code', '=', 'bh.bom_code')
        ->join('article as arm', 'arm.article_code', '=', 'br.article_code')
        ->where('bh.status', '3')
        ->where('bh.article_code', $articleCode)
        ->whereIn('arm.article_type', ['RMP', 'RMNP'])
        ->select(
            'arm.article_code',
            'arm.article_alternative_code',
            'arm.article_desc',
            'arm.uom',
            'br.qty as qty_per_fg',
            DB::raw("greatest(coalesce((
    select sum(ws.article_qty)
    from warehouse_stock ws
    where ws.article_code = arm.article_code
      and ws.location_number = ?
),0),0) as stock_qty")
        )
        ->addBinding($locationCode, 'select')
        ->get();
 
    // FG tanpa BOM RM: kapasitas fresh 0, tapi stok WIP tetap ditampilkan
    if ($rows->isEmpty()) {
        return response()->json([
            'rows'         => [],
            'max_fg_fresh' => 0,
            'wip_rows'     => $wipRows,
            'wip_total'    => $wipTotal,
            'max_fg_total' => $wipTotal,
        ]);
    }
 
   $rows = $rows->map(function ($r) {
    $perFg     = (float) $r->qty_per_fg;
    $r->max_fg = $perFg > 0 ? max(0, floor(((float) $r->stock_qty) / $perFg)) : 0;
    return $r;
});
 
    $overall = (float) $rows->min('max_fg'); // kapasitas fresh sebenarnya (bottleneck)
    $best    = (float) $rows->max('max_fg'); // kapasitas tertinggi andai RM lain tak terbatas
 
    // ada variasi kapasitas antar-RM? kalau tidak, tidak ada yg pantas disebut "penghambat"
    $adaVariasi = ($best > $overall);
 
    $result = $rows->map(function ($r) use ($overall, $best, $adaVariasi) {
        $perFg   = (float) $r->qty_per_fg;
        $stock   = (float) $r->stock_qty;
        $maxFg   = (float) $r->max_fg;
 
        $isLimiting = ($maxFg === $overall);
        $isCritical = ($overall <= 0) || ($isLimiting && $adaVariasi);
 
        // sisa stock setelah dipakai bikin $overall FG (selalu >= 0)
        $surplusQty = $stock - ($overall * $perFg);
 
        // kekurangan hanya relevan kalau memang ada RM lain yg bisa lebih banyak
        $deficitQty = null;
        $deficitFg  = null;
        if ($isLimiting && $adaVariasi) {
            $deficitFg  = $best - $maxFg;
            $deficitQty = ($best * $perFg) - $stock;
        }
 
        return [
            'article_code'             => $r->article_code,
            'article_alternative_code' => $r->article_alternative_code,
            'article_desc'             => $r->article_desc,
            'uom'                      => $r->uom,
            'qty_per_fg'               => $perFg,
            'stock_qty'                => $stock,
            'max_fg'                   => $maxFg,
            'is_limiting'              => $isLimiting,
            'is_critical'              => $isCritical,
            'surplus_qty'              => $surplusQty,
            'deficit_qty'              => $deficitQty,
            'deficit_fg'               => $deficitFg,
        ];
    });
 
    return response()->json([
        'rows'         => $result,
        'max_fg_fresh' => $overall,
        'wip_rows'     => $wipRows,
        'wip_total'    => $wipTotal,
        'max_fg_total' => $overall + $wipTotal,
    ]);
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
               
        return view("production.actualLoading.create",$data);

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
    $username    = Auth::user()->username;
    $articles    = json_decode($request->articles);
    $loadingDate = $request->loadingDate;
    $sprayBooth  = $request->sprayBooth;
    $reference   = $request->reference;   // ⬅ NEW
    $note        = $request->note;

    $loadingLocation = '047';       // gudang Actual Loading (tujuan)
    $movementType    = 'LOADING';

    $validation = Validator::make($request->all(), [
        'sprayBooth'  => 'required',
        'loadingDate' => 'required',
    ]);
    if ($validation->fails()) {
        $errs = [];
        foreach ($validation->messages()->getMessages() as $m) { $errs[] = $m; }
        return response()->json(['status'=>0,'title'=>"Save $this->title",'message'=>$errs,'alert'=>'error']);
    }
    if (empty($articles)) {
        return response()->json(['status'=>0,'title'=>"Save $this->title",'message'=>[['Tidak ada artikel yang diinput.']],'alert'=>'error']);
    }

    $loadingDateDb = $loadingDate ? implode('-', array_reverse(explode('-', $loadingDate))) : date('Y-m-d');
    $now = date('Y-m-d H:i:s');

    DB::beginTransaction();
    try {
        AppHelpers::resetCode($this->moduleCode);
        $prdNumber = $this->getLastCode($this->moduleCode);

        DB::table('actual_loading_hdr')->insert([
            'prod_code'          => $prdNumber,
            'original_prod_code' => $prdNumber,
            'loading_date'       => $loadingDateDb,
            'spray_booth'        => $sprayBooth,
            'wos_reference'       => $reference,   // ⬅ NEW
            'num_revision'       => 0,
            'status'             => 1,
            'note'               => $note,
            'created_by'         => $username,
            'updated_by'         => $username,
            'created_at'         => $now,
            'updated_at'         => $now,
        ]);

        // counter movement_code (pola TransferStock: max+1, increment per baris)
        $seq = (int) DB::table('warehouse_movement')->max('movement_code');

        $urutan = 0;
        foreach ($articles as $val) {
            $urutan++;
            $qtyTotal = (float)($val->qty ?? 0);
            if ($qtyTotal <= 0) continue;

            // pecah otomatis: fresh dulu, sisanya repaint
            $freshCapacity = $this->freshCapacity($val->article_code, $sprayBooth);
            $qtyFresh   = min($qtyTotal, $freshCapacity);
            $qtyRepaint = $qtyTotal - $qtyFresh;

            if ($qtyRepaint > 0) {
                $wipAvail = $this->wipAvailable($val->article_code);
                if ($wipAvail < $qtyRepaint) {
                    throw new \Exception(
                        "Qty {$qtyTotal} untuk {$val->article_code} tidak tercukupi. ".
                        "Fresh maks {$freshCapacity}, repaint (WIP) maks {$wipAvail}."
                    );
                }
            }

            DB::table('actual_loading_det')->insert([
                'prod_code'              => $prdNumber,
                'urutan'                 => $urutan,
                'article_code'           => $val->article_code,
                'uom'                    => $val->uom ?? null,
                'qty'                    => $qtyTotal,
                'qty_fresh'              => $qtyFresh,
                'qty_repaint'            => $qtyRepaint,
                'stock_fresh_snapshot'   => (float)($val->stock_fresh   ?? 0),
                'stock_repaint_snapshot' => (float)($val->stock_repaint ?? 0),
                'note'                   => $val->note ?? null,
                'created_by'             => $username,
                'updated_by'             => $username,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);

          if ($qtyFresh > 0) {
        foreach ($this->getBomRm($val->article_code) as $rm) {
            $this->postOut(
                $seq, $rm->article_code, $qtyFresh * (float)$rm->qty_per_fg,
                $sprayBooth, $loadingLocation, $movementType, $prdNumber,
                "Fresh RM", $username
            );
        }
        $this->postIn(
            $seq, $val->article_code, $val->uom, $qtyFresh,
            $loadingLocation, $sprayBooth, $movementType, $prdNumber,   // ⬅ from = $sprayBooth (bukan null)
            "Fresh RM", $username
        );
    }

            // REPAINT: FG dari WIP -> loading. OUT+IN dipasangkan per sumber
            //          di DALAM moveRepaintFromWip (from = WIP asal, akurat)
            if ($qtyRepaint > 0) {
                $this->moveRepaintFromWip(
                    $seq, $val->article_code, $val->uom, $qtyRepaint,
                    $loadingLocation, $movementType, $prdNumber, $username
                );
            }
        }   // ⬅ penutup foreach — pastikan HANYA satu, tepat di sini

        DB::commit();
        $title   = "Save $this->title";
        $message = "$title $prdNumber is successfully saved";
        \LogActivity::addToLog($title, "username: $username Status $message");
        return response()->json(['status'=>1,'title'=>$title,'message'=>$message,'alert'=>'success','prdNumber'=>$prdNumber,'oEdit'=>true]);

    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json(['status'=>0,'title'=>"Save $this->title",'message'=>[[$e->getMessage()]],'alert'=>'error']);
    }
}

/** Komponen RM (RMP/RMNP) dari BOM aktif suatu FG + qty per FG */
private function getBomRm($fgArticle)
{
    return DB::table('bom_hdr as bh')
        ->join('bom_rm as br', 'br.bom_code', '=', 'bh.bom_code')
        ->join('article as arm', 'arm.article_code', '=', 'br.article_code')
        ->where('bh.status', '3')
        ->where('bh.article_code', $fgArticle)
        ->whereIn('arm.article_type', ['RMP','RMNP'])
        ->select('arm.article_code','arm.uom','br.qty as qty_per_fg')
        ->get();
}

/** Kapasitas fresh: brp FG bisa dibuat dari RM di booth (bottleneck BOM). */
private function freshCapacity($fgArticle, $sprayBooth)
{
    $rows = $this->getBomRm($fgArticle);
    if ($rows->isEmpty()) return 0;

    $maxFg = null;
    foreach ($rows as $rm) {
        $perFg = (float)$rm->qty_per_fg;
        if ($perFg <= 0) continue;

        $have = (float) DB::table('warehouse_stock')
            ->where('article_code', $rm->article_code)
            ->where('location_number', $sprayBooth)
            ->sum('article_qty');

        $have    = max(0, $have);                  // ⬅ minus dianggap 0
        $canMake = max(0, floor($have / $perFg));  // ⬅ clamp
        $maxFg   = is_null($maxFg) ? $canMake : min($maxFg, $canMake);
    }
    return (float) max(0, $maxFg ?? 0);
}

/** Total FG di gudang WIP, lokasi bersaldo minus dianggap 0. */
private function wipAvailable($fgArticle)
{
    $perLoc = DB::table('warehouse_stock as ws')
        ->join('stock_location_master as slm','slm.location_code','=','ws.location_number')
        ->where('ws.article_code', $fgArticle)
        ->where('slm.location_type','wip')
        ->groupBy('ws.location_number')
        ->select('ws.location_number', DB::raw('sum(ws.article_qty) as qty'))
        ->get();

    $total = 0;
    foreach ($perLoc as $r) {
        $total += max(0, (float) $r->qty);   // ⬅ minus dianggap 0
    }
    return (float) $total;
}

/** Alokasi qty repaint KELUAR dari beberapa gudang WIP (greedy). */
/**
 * Alokasi qty repaint dari beberapa gudang WIP (greedy),
 * DAN langsung catat pasangan OUT+IN per sumber supaya
 * movement_from di sisi IN akurat (bukan hardcode 'WIP').
 */
private function moveRepaintFromWip(&$seq, $fgArticle, $uom, $qtyNeeded, $toLoc, $movementType, $transno, $username)
{
    $sources = DB::table('warehouse_stock as ws')
        ->join('stock_location_master as slm','slm.location_code','=','ws.location_number')
        ->where('ws.article_code', $fgArticle)
        ->where('slm.location_type','wip')
        ->where('ws.article_qty','>',0)
        ->groupBy('ws.location_number','slm.location_name')
        ->orderBy('slm.location_name')
        ->select('ws.location_number', DB::raw('sum(ws.article_qty) as qty'))
        ->get();

    $remaining = $qtyNeeded;
    foreach ($sources as $src) {
        if ($remaining <= 0) break;
        $take = min($remaining, (float)$src->qty);
        if ($take <= 0) continue;

        // OUT dari WIP asal
        $this->postOut(
            $seq, $fgArticle, $take,
            $src->location_number, $toLoc, $movementType, $transno,
            "Repaint dari WIP", $username
        );
        // IN ke loading, from = WIP asal yang SAMA (bukan hardcode)
        $this->postIn(
            $seq, $fgArticle, $uom, $take,
            $toLoc, $src->location_number, $movementType, $transno,
            "FG Repaint", $username
        );

        $remaining -= $take;
    }
    if ($remaining > 0) {
        throw new \Exception("Alokasi repaint {$fgArticle} tidak cukup (sisa {$remaining}).");
    }
}

/** Satu baris KELUAR: kurangi warehouse_stock sumber + movement (min) */
private function postOut(&$seq, $article, $qty, $fromLoc, $toLoc, $movementType, $transno, $desc, $username)
{
    if ($qty <= 0) return;
    $now = date('Y-m-d H:i:s');
    $adesc = DB::table('article')->where('article_code',$article)->value('article_desc');

    DB::table('warehouse_stock')
        ->where('article_code',$article)->where('location_number',$fromLoc)
        ->update([
            'article_qty' => DB::raw('coalesce(article_qty,0) - '.$qty),
            'updated_by'  => $username,
            'updated_at'  => $now,
        ]);

    // location_number = fromLoc (yg saldonya turun); from/to = jejak operasi
    $this->writeMovement($seq, $article, $adesc, $qty, $fromLoc, 'out',
                         $fromLoc, $toLoc, $movementType, $transno, $desc, $username);
}

/** Satu baris MASUK: tambah warehouse_stock tujuan + movement (plus) */
private function postIn(&$seq, $article, $uom, $qty, $toLoc, $fromLoc, $movementType, $transno, $desc, $username)
{
    if ($qty <= 0) return;
    $now = date('Y-m-d H:i:s');

    $art = DB::table('article')->where('article_code',$article)
        ->select('article_desc','article_type')->first();
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

    // location_number = toLoc (yg saldonya naik); from/to = jejak operasi
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
        'location_number'   => $location,      // lokasi yg saldonya kena
        'artikel_code'      => $article,
        'artikel_desc'      => $adesc,
        'movement_min'      => ($direction === 'out') ? $qty : 0,
        'movement_plus'     => ($direction === 'in')  ? $qty : 0,
        'movement_from'     => $fromLoc,       // ⬅ sumber operasi
        'movement_to'       => $toLoc,         // ⬅ tujuan operasi
        'movement_type'     => $movementType,
        'movement_transnno' => $transno,
        'movement_desc'     => $desc,
        'last_qty'          => DB::raw("get_last_qty_new('$article','$today','HO','$location') $sign $qty"),
        'created_by'        => $username,
        'created_at'        => date('Y-m-d H:i:s'),
    ]);
}

    public function storeOld(Request $request)
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
        $status = '1';
        $oEdit = true;
        $sprayBooth = $request->sprayBooth;

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
            // 'woNumber'=>'required|unique:purchase_order_hdr,wo_code',
            'wosNumber'  => 'required' 
        ]);
        
        $error_array = array();
        $success_output = '';
        // return $validation;
        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }
            $title="Save Production";
            $alert ="error";
            return response()->json(array('status' => 0,'title' => $title, 'message' => $error_array,'alert' =>$alert));
        }else{
            $hasilUpdate = AppHelpers::resetCode($this->moduleCode);
            $prdNumber = $this->getLastCode($this->moduleCode);

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
                efficiency,
                num_revision,
                status,
                note,
                created_by,
                updated_by,
                created_at,
                updated_at,
                spray_booth
            )
            select 
                '$prdNumber',
                wo_code,
                '$prdNumber',
                wo_date,
                wo_shift,
                wo_group,
                '$wosTime',
                $workHour,
                $efficiency,
                0,
                '1',
                note,
                '$username',
                '$username',
                '".date('Y-m-d H:i:s')."',
                '".date('Y-m-d H:i:s')."',
                spray_booth
            from wo_hdr where wo_code = '$wosNumber'";

            $sqlDet="INSERT into production_det
            (
                prod_code,
                so_code,
                so_qty,
                urutan,
                article_code,
                article_rm_code,
                plan_time_loading,
                plan_qty_fresh,
                plan_qty_repaint,
                plan_tag,
                origin_tag,
                qty_ok,
                qty_repair,
                qty_repaint,
                note,
                created_by,
                updated_by,
                created_at,
                updated_at,
                tone
            )
            select '$prdNumber',
                so_code,
                so_qty,
                urutan,
                article_code,
                article_rm_code,
                plan_time_loading,
                plan_qty_fresh,
                plan_qty_repaint,
                plan_tag,
                origin_tag,
                qty_ok,
                qty_repair,
                qty_repaint,
                note,
                '$username',
                '$username',
                '".date('Y-m-d H:i:s')."',
                '".date('Y-m-d H:i:s')."' ,
                tone
            from wo_det where wo_code = '$wosNumber'";

            DB::beginTransaction();
            try {

                $rowAffected =  DB::select($sqlHdr);
                if ($rowAffected){
                    DB::select($sqlDet);
                }

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
                            "act_time_loading" => $val->act_waktu,
                            "act_qty_fresh" => $val->act_qty_prod,
                            "act_qty_repaint" => $val->act_qty_repaint,
                            "act_tag" => $val->act_tag,
                        ]
                    );
                }

                DB::commit();
                $title ='Save Production';
                $alert  ="success";
                $message  = "$title $prdNumber is successfully saved";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'prdNumber'=>$prdNumber,'oEdit'=>$oEdit));

            } catch (Exception $e) {
                DB::rollBack();
                $title ='Save Production';
                $alert  ="warning";
                $message  = "$title $prdNumber is failed to save";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'prdNumber'=>$prdNumber,'oEdit'=>$oEdit));
            }
        }
    }

    public function posting(Request $request)
    {
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $prdNumber = DB::table('production_hdr')
                    ->where('id',$id)
                    ->where('status','=','3')
                    ->value('prod_code');
        $siteCode = 'HO';
        $location ='WH';
        $status = '4';
        $moduleCode = $this->moduleCode;
        $todayDate = date('Y-m-d');
        $movementDate = date("d-m-Y");
        $rowAffectedRm = "";

        // $status = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED','CLOSED','REVISED','INPUT FG','POSTED FG'];
        
        if ($prdNumber){
            $data = DB::table('production_det')
            ->leftJoin('production_hdr','production_hdr.prod_code','production_det.prod_code')
            ->leftJoin('article','article.article_code','production_det.article_code')
            ->where('production_det.prod_code',$prdNumber)
            ->where('production_hdr.status','3')
            ->where('production_det.so_code','<>','other')
            ->where('tone',db::raw("(select max(tone) from bom_det where bom_code in (select bom_code from bom_hdr where article_code = production_det.article_code))"))
            ->select('production_det.*'
            ,'article.article_type'
            ,'article.uom as uom_article'
            // ,'production_det.act_qty_fresh as total_qty'
            ,DB::raw("production_det.act_qty_fresh + production_det.act_qty_repaint as total_qty") //dari bu lupi qty yang potong RM adalah act_fresh + act_repaint
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
                // $rowAffectedFg = DB::table('article_stock')
                // ->where('site_code',$siteCode)
                // ->where('article_code',$val->article_code)
                // ->where('location_number',$location)
                // ->update([
                //     'article_qty' => DB::raw('coalesce(article_qty,0) + '.$val->total_qty)
                // ]);

                /*
                    update qty nya ditambahkan dengan qty baru RM
                    yang di potong stock nya hanya yang RM
                */
                $rowAffectedRm = DB::table('article_stock')
                ->where('site_code',$siteCode)
                ->where('article_code',$val->article_rm_code)
                ->where('location_number',$location)
                ->update([
                    'article_qty' => DB::raw('coalesce(article_qty,0) - '.$val->total_qty)
                ]);
            }
                    
            if ($rowAffectedRm > 0){
                DB::table('production_hdr')
                ->where('prod_code',$prdNumber)
                ->update(
                    [   
                        'status' => $status,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                // $movementFg = DB::table('production_det')
                // ->leftJoin('production_hdr','production_hdr.prod_code','production_det.prod_code')
                // ->leftJoin('article','article.article_code','production_det.article_code')
                // ->where('production_det.prod_code',$prdNumber)
                // ->where('production_hdr.status','4')
                // ->where('act_qty_fresh', '<>', 0)
                // ->where('production_det.so_code','<>','other')
                // ->select(
                //     DB::RAW("now()::timestamp::date as movement_date" )
                //     ,'production_det.article_code'
                //     ,'article.article_desc'
                //     ,DB::raw("0 as movement_min")
                //     ,DB::RAW("(production_det.act_qty_fresh) as movement_plus")
                //     ,DB::raw("0 as movement_price ")
                //     ,'production_hdr.prod_code as movement_transnno'
                //     ,DB::raw("'$moduleCode' as movement_type")
                //     ,'production_hdr.wo_code as movement_desc'
                // )
                // ->get();
                
                // $dataSetMovementFg = [];
                // foreach ($movementFg as $val) {
                //     $dataSetMovementFg[] = [
                //         'movement_date' => $val->movement_date,
                //         'artikel_code' => $val->article_code,
                //         'artikel_desc' => $val->article_desc,
                //         'movement_min' => $val->movement_min,
                //         'movement_plus' => $val->movement_plus,
                //         'movement_price' => $val->movement_price,
                //         'movement_transnno' => $val->movement_transnno,
                //         'movement_type' => $val->movement_type,
                //         'movement_desc' => $val->movement_desc,
                //         'created_by' => Auth::user()->username,
                //         'created_at' => date('Y-m-d H:i:s'),
                //         'site_code' => $siteCode,
                //         'location_number' => $location,
                //         'last_qty' => DB::raw("get_last_qty('$val->article_code','$todayDate','$siteCode','$location') + ($val->movement_min+$val->movement_plus)")
                //     ];
                // }

                // DB::table('movement')->insert($dataSetMovementFg);

                $movementRm = DB::table('production_det')
                ->leftJoin('production_hdr','production_hdr.prod_code','production_det.prod_code')
                ->leftJoin('article','article.article_code','production_det.article_rm_code')
                ->where('production_det.prod_code',$prdNumber)
                ->where('production_hdr.status','4')
                ->where('act_qty_fresh', '<>', 0)
                ->where('act_qty_repaint', '<>', 0)
                ->where('production_det.so_code','<>','other')
                ->select(
                    DB::RAW("'$movementDate' as movement_date" )
                    ,'production_det.article_rm_code'
                    ,'article.article_desc'
                    // ,DB::raw("(production_det.act_qty_fresh) as movement_min")
                    ,DB::RAW("(production_det.act_qty_fresh+act_qty_repaint) as movement_min")
                    ,DB::RAW("0 as movement_plus")
                    ,DB::RAW("0 as movement_price ")
                    ,'production_hdr.prod_code as movement_transnno'
                    ,DB::RAW("'$moduleCode' as movement_type")
                    ,'production_hdr.wo_code as movement_desc'
                )
                ->get();
                
                $dataSetMovementRm = [];
                foreach ($movementRm as $val) {
                    $dataSetMovementRm[] = [
                        'movement_date' => $val->movement_date,
                        'artikel_code' => $val->article_rm_code,
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
                        'last_qty' => DB::raw("get_last_qty('$val->article_rm_code','$todayDate','$siteCode','$location') - ($val->movement_min+$val->movement_plus)")
                    ];
                }

                DB::table('movement')->insert($dataSetMovementRm);

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
            $message  = "$title $prdNumber Failed to Posting";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }

    }

    public function show(Request $request)
{
    $id = Crypt::decryptString($request->id);
    $username = Auth::user()->username;
    $data['title'] = "Detail $this->title";
    $data['subtitle'] = "Detail $this->title";

    $data['header'] = DB::table('actual_loading_hdr as alh')
        ->leftJoin('stock_location_master as slm', 'slm.location_code', '=', 'alh.spray_booth')
        ->where('alh.id', $id)
        ->select(
            'alh.*',
            DB::raw("to_char(alh.loading_date, 'DD-MM-YYYY') as loading_date_fmt"),
            DB::raw("coalesce(slm.location_name, alh.spray_booth) as spray_booth_name")
        )
        ->first();

    if (!$data['header']) {
        abort(404);
    }

    $prdNumber = $data['header']->prod_code;

    $data['details'] = DB::table('actual_loading_det as ald')
        ->leftJoin('article as a', 'a.article_code', '=', 'ald.article_code')
        ->where('ald.prod_code', $prdNumber)
        ->select(
            'ald.*',
            'a.article_alternative_code',
            'a.article_desc'
        )
        ->orderBy('ald.urutan')
        ->get();

    $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode, $prdNumber, $username);
    $data['approveValidate'] = Approval::approveValidate($this->moduleCode, $prdNumber, $username);

    $data['oEdit'] = true;

    $statusPrd = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED'];
    $data['statusPrd'] = $statusPrd[$data['header']->status] ?? $data['header']->status;

    return view("production.actualLoading.show", $data);
}

    public function showOld(Request $request)
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
        ->select('production_det'.'.*'
        ,'article.article_alternative_code'
        ,'article.article_desc'
        ,DB::raw("case when so_code ='other' then production_det.article_code else concat(article.article_alternative_code,' - ',article.article_desc) end as article")
        )
        ->orderBy('urutan')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$prdNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$prdNumber,$username);

        $data['oEdit']=true;
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'CLOSED','7'=>'REVISED','8'=>'INPUT FG'];
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'CLOSED'];
        // $statusPrd = ['NEW','VALIDATED','APPROVED','POSTED','','CANCELED'];
        $statusPrd = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED','CLOSED','REVISED','INPUT FG','POSTED FG'];
        $data['statusPrd'] = $statusPrd[$data['headers'][0]->status-1];

        return view("production.actualLoading.show",$data);
        
    }

    public function wosDetail(Request $request)
    {
        $woCode = $request->wosNumber;
        $dariExcel = $request->dariExcel;

        if ($dariExcel == "true") {
            
        }else{
            $data = DB::table('wo_det')
            ->leftJoin('article','article.article_code','=','wo_det.article_code')
            ->where('wo_code',$woCode)
            // ->where('tone',db::raw("(select max(tone) from bom_det where article_code = wo_det.article_code)"))
            // ->where('so_code','<>','other')
            ->select('wo_det'.'.*'
            ,DB::raw("case when so_code ='other' then wo_det.article_code else concat(article.article_alternative_code,' - ',article.article_desc) end as article")
            , 'article.article_alternative_code'
            ,'article.article_desc')
            ->orderBy('urutan')
            ->get();
        }

        return response()->json($data);

    }

    public function edit(Request $request)
{
    $id = Crypt::decryptString($request->id);
    $username = Auth::user()->username;
    $data['title'] = "Edit $this->title";
    $data['subtitle'] = "Edit $this->title";

    $data['header'] = DB::table('actual_loading_hdr as alh')
        ->leftJoin('stock_location_master as slm', 'slm.location_code', '=', 'alh.spray_booth')
        ->where('alh.id', $id)
        ->select(
            'alh.*',
            DB::raw("to_char(alh.loading_date, 'DD-MM-YYYY') as loading_date_fmt"),
            DB::raw("coalesce(slm.location_name, alh.spray_booth) as spray_booth_name")
        )
        ->first();

    if (!$data['header']) {
        abort(404);
    }

    $prdNumber = $data['header']->prod_code;

    $data['details'] = DB::table('actual_loading_det as ald')
        ->leftJoin('article as a', 'a.article_code', '=', 'ald.article_code')
        ->where('ald.prod_code', $prdNumber)
        ->select(
            'ald.*',
            'a.article_alternative_code',
            'a.article_desc',
            'a.uom as uom_master'
        )
        ->orderBy('ald.urutan')
        ->get();

    $data['sprayBooths'] = DB::table('stock_location_master')
        ->where('location_type', 'booth')
        ->orderBy('location_name')
        ->get();

    // ── Log histori, dikelompokkan per revisi ──
    $logs = DB::table('actual_loading_log')
        ->where('prod_code', $prdNumber)
        ->orderBy('revision', 'desc')
        ->orderBy('created_at', 'desc')
        ->get();

    $data['history'] = $logs->groupBy('revision')->map(function ($group) {
        return [
            'revision'   => $group->first()->revision,
            'created_by' => $group->first()->created_by,
            'created_at' => $group->first()->created_at,
            'changes'    => $group,
        ];
    })->values();

    $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode, $prdNumber, $username);
    $data['approveValidate'] = Approval::approveValidate($this->moduleCode, $prdNumber, $username);

    $data['oEdit'] = true;

    $statusPrd = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED'];
    $data['statusPrd'] = $statusPrd[$data['header']->status] ?? $data['header']->status;

    return view("production.actualLoading.edit", $data);
}

    public function editOld(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['header'] = DB::table('production_hdr')
        ->where('id',$id)
        ->get()->first();

        $prdNumber = $data['header']->prod_code;

        $data['details'] = DB::table('production_det')
        ->leftJoin('article','article.article_code','=','production_det.article_code')
        ->where('prod_code',$prdNumber)
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

         // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'CLOSED'];
        $statusPrd = ['NEW','VALIDATED','APPROVED','POSTED','','CANCELED'];
        $data['statusPrd'] = $statusPrd[$data['header']->status-1];

        return view("production.actualLoading.edit",$data);
        
    }

    /**
 * Hitung net qty (plus-min) per artikel+lokasi untuk 1 dokumen,
 * lalu posting movement penyeimbang supaya net kembali ke 0.
 * Dipanggil SEBELUM posting ulang movement baru saat update.
 */
private function reverseDocumentStock(&$seq, $prdNumber, $username)
{
    $nets = DB::table('warehouse_movement')
        ->where('movement_transnno', $prdNumber)
        ->select(
            'artikel_code',
            'location_number',
            DB::raw('sum(movement_plus - movement_min) as net_qty')
        )
        ->groupBy('artikel_code', 'location_number')
        ->havingRaw('sum(movement_plus - movement_min) <> 0')
        ->get();

    foreach ($nets as $row) {
        $netQty = (float) $row->net_qty;
        $loc    = $row->location_number;
        $art    = $row->artikel_code;

        if ($netQty > 0) {
            // net-nya nambah stok -> reversal-nya KELUAR
            $this->postOut(
                $seq, $art, $netQty, $loc, $loc,
                $prdNumber, 'Reversal (edit)', $username
            );
        } else {
            // net-nya ngurangin stok -> reversal-nya MASUK
            $uom = DB::table('article')->where('article_code', $art)->value('uom');
            $this->postIn(
                $seq, $art, $uom, abs($netQty), $loc, $loc,
                $prdNumber, 'Reversal (edit)', $username
            );
        }
    }
}

public function update(Request $request)
{
    $username    = Auth::user()->username;
    $prdNumber   = $request->prdNumber;
    $loadingDate = $request->loadingDate;
    $sprayBooth  = $request->sprayBooth;
    $reference   = $request->reference;   // ⬅ NEW
    $note        = $request->note;
    $articles    = json_decode($request->articles);
    $now         = date('Y-m-d H:i:s');

    $validation = Validator::make($request->all(), [
        'sprayBooth'  => 'required',
        'loadingDate' => 'required',
    ]);
    if ($validation->fails()) {
        $errs = [];
        foreach ($validation->messages()->getMessages() as $m) { $errs[] = $m; }
        return response()->json(['status'=>0,'title'=>"Update $this->title",'message'=>$errs,'alert'=>'error']);
    }
    if (empty($articles)) {
        return response()->json(['status'=>0,'title'=>"Update $this->title",'message'=>[['Tidak ada artikel yang diinput.']],'alert'=>'error']);
    }

    $loadingDateDb = $loadingDate ? implode('-', array_reverse(explode('-', $loadingDate))) : null;

    DB::beginTransaction();
    try {
        $oldHeader = DB::table('actual_loading_hdr')->where('prod_code', $prdNumber)->first();
        if (!$oldHeader) {
            throw new \Exception("Data $prdNumber tidak ditemukan.");
        }
        if ($oldHeader->status != 1) {
            throw new \Exception("Hanya data berstatus NEW yang bisa diedit langsung. Gunakan Revision untuk dokumen yang sudah diproses.");
        }

        $newRevision  = $oldHeader->num_revision + 1;
        $changeCount  = 0;
        $seq          = (int) DB::table('warehouse_movement')->max('movement_code');

        // ── 1. Diff-log field header ──
       $headerFieldsMap = [
    'loading_date'  => ['old' => $oldHeader->loading_date, 'new' => $loadingDateDb, 'label' => 'Loading Date'],
    'spray_booth'   => ['old' => $oldHeader->spray_booth, 'new' => $sprayBooth, 'label' => 'Spray Booth'],
    'wos_reference' => ['old' => $oldHeader->wos_reference, 'new' => $reference, 'label' => 'Referensi WOS'], // ⬅ NEW
    'note'          => ['old' => $oldHeader->note, 'new' => $note, 'label' => 'Note'],
];
        foreach ($headerFieldsMap as $val) {
            if ((string)$val['old'] !== (string)$val['new']) {
                DB::table('actual_loading_log')->insert([
                    'prod_code' => $prdNumber, 'revision' => $newRevision, 'ref_type' => 'hdr',
                    'article_code' => null, 'field_name' => $val['label'],
                    'old_value' => $val['old'], 'new_value' => $val['new'],
                    'created_by' => $username, 'created_at' => $now,
                ]);
                $changeCount++;
            }
        }

        // ── 2. Reversal SEMUA movement lama dokumen ini ke net 0 ──
        $this->reverseDocumentStock($seq, $prdNumber, $username);

        // ── 3. Diff-log per artikel + hapus detail lama, siapkan insert baru ──
        $oldDetails = DB::table('actual_loading_det')->where('prod_code', $prdNumber)->get()->keyBy('article_code');
        $seenArticles = [];
        $urutan = 0;

        DB::table('actual_loading_det')->where('prod_code', $prdNumber)->delete();

        foreach ($articles as $val) {
            $urutan++;
            $articleCode = $val->article_code;
            $seenArticles[] = $articleCode;

            $qtyFresh   = (float)($val->qty_fresh ?? 0);
            $qtyRepaint = (float)($val->qty_repaint ?? 0);
            $qtyTotal   = $qtyFresh + $qtyRepaint;
            $newNote    = $val->note ?? null;

            $old = $oldDetails->get($articleCode);
            if ($old) {
                foreach ([
                    'Qty Fresh'   => [(float)$old->qty_fresh, $qtyFresh],
                    'Qty Repaint' => [(float)$old->qty_repaint, $qtyRepaint],
                    'Note'        => [$old->note, $newNote],
                ] as $label => [$oldVal, $newVal]) {
                    if ((string)$oldVal !== (string)$newVal) {
                        DB::table('actual_loading_log')->insert([
                            'prod_code' => $prdNumber, 'revision' => $newRevision, 'ref_type' => 'det',
                            'article_code' => $articleCode, 'field_name' => $label,
                            'old_value' => $oldVal, 'new_value' => $newVal,
                            'created_by' => $username, 'created_at' => $now,
                        ]);
                        $changeCount++;
                    }
                }
            } else {
                DB::table('actual_loading_log')->insert([
                    'prod_code' => $prdNumber, 'revision' => $newRevision, 'ref_type' => 'det',
                    'article_code' => $articleCode, 'field_name' => 'Article Added',
                    'old_value' => null, 'new_value' => "Fresh: $qtyFresh, Repaint: $qtyRepaint",
                    'created_by' => $username, 'created_at' => $now,
                ]);
                $changeCount++;
            }

            if ($qtyTotal <= 0) continue;

            // ── validasi ulang ketersediaan (sama seperti store) ──
            $freshCapacity = $this->freshCapacity($articleCode, $sprayBooth);
            if ($qtyFresh > $freshCapacity) {
                throw new \Exception("Qty Fresh {$qtyFresh} untuk {$articleCode} melebihi kapasitas RM ({$freshCapacity}).");
            }
            if ($qtyRepaint > 0) {
                $wipAvail = $this->wipAvailable($articleCode);
                if ($wipAvail < $qtyRepaint) {
                    throw new \Exception("Qty Repaint {$qtyRepaint} untuk {$articleCode} tidak tercukupi. WIP tersedia {$wipAvail}.");
                }
            }

            DB::table('actual_loading_det')->insert([
                'prod_code' => $prdNumber, 'urutan' => $urutan, 'article_code' => $articleCode,
                'uom' => $val->uom ?? ($old->uom ?? null),
                'qty' => $qtyTotal, 'qty_fresh' => $qtyFresh, 'qty_repaint' => $qtyRepaint,
                'note' => $newNote,
                'created_by' => $old->created_by ?? $username, 'updated_by' => $username,
                'created_at' => $old->created_at ?? $now, 'updated_at' => $now,
            ]);

            // ── 4. Posting ulang movement (sama logika seperti store) ──
            if ($qtyFresh > 0) {
                foreach ($this->getBomRm($articleCode) as $rm) {
                    $this->postOut($seq, $rm->article_code, $qtyFresh * (float)$rm->qty_per_fg,
                        $sprayBooth, '047', 'LOADING', $prdNumber, "Fresh RM (edit)", $username);
                }
                $this->postIn($seq, $articleCode, $val->uom ?? null, $qtyFresh,
                    '047', $sprayBooth, 'LOADING', $prdNumber, "Fresh RM (edit)", $username);
            }
            if ($qtyRepaint > 0) {
                $this->moveRepaintFromWip($seq, $articleCode, $val->uom ?? null, $qtyRepaint,
                    '047', 'LOADING', $prdNumber, $username);
            }
        }

        // ── artikel yang dihapus saat edit ──
        foreach ($oldDetails as $articleCode => $old) {
            if (!in_array($articleCode, $seenArticles)) {
                DB::table('actual_loading_log')->insert([
                    'prod_code' => $prdNumber, 'revision' => $newRevision, 'ref_type' => 'det',
                    'article_code' => $articleCode, 'field_name' => 'Article Removed',
                    'old_value' => "Fresh: {$old->qty_fresh}, Repaint: {$old->qty_repaint}", 'new_value' => null,
                    'created_by' => $username, 'created_at' => $now,
                ]);
                $changeCount++;
            }
        }

        DB::table('actual_loading_hdr')->where('prod_code', $prdNumber)->update([
            'loading_date' => $loadingDateDb,
            'spray_booth'  => $sprayBooth,
             'wos_reference' => $reference,   // ⬅ NEW
            'note'         => $note,
            'num_revision' => $changeCount > 0 ? $newRevision : $oldHeader->num_revision,
            'updated_by'   => $username,
            'updated_at'   => $now,
        ]);

        DB::commit();

        $title   = "Update $this->title";
        $message = $changeCount > 0
            ? "$title $prdNumber berhasil disimpan (Revisi $newRevision, $changeCount perubahan, stok disesuaikan)"
            : "$title $prdNumber tidak ada perubahan";
        \LogActivity::addToLog($title, "username: $username Status $message");

        return response()->json(['status'=>1,'title'=>$title,'message'=>$message,'alert'=>'success','prdNumber'=>$prdNumber,'oEdit'=>true]);

    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json(['status'=>0,'title'=>"Update $this->title",'message'=>[[$e->getMessage()]],'alert'=>'error']);
    }
}

    public function updateOld(Request $request)
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
                        'start_time' => $wosTime,
                        'working_hour'=> $workHour,
                        'efficiency' => $efficiency,
                        'note' => $note,
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
                            "act_time_loading" => $val->act_waktu,
                            "act_qty_fresh" => $val->act_qty_prod,
                            "act_qty_repaint" => $val->act_qty_repaint,
                            "act_tag" => $val->act_tag,
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
    $searchPrd    = strtolower($request->searchPrd);
    $prdDate      = $request->prdDate;
    $searchStatus = $request->searchStatus;

    $fromDate = "";
    $toDate   = "";

    if ($prdDate) {
        $date = explode("to", $prdDate);
        if (count($date) > 1) {
            $fromDate = implode("-", array_reverse(explode("-", trim($date[0]))));
            $toDate   = implode("-", array_reverse(explode("-", trim($date[1]))));
        } else {
            $fromDate = implode("-", array_reverse(explode("-", trim($date[0]))));
            $toDate   = $fromDate;
        }
    }

   $data = DB::table('actual_loading_hdr')
    ->leftJoin('stock_location_master', 'stock_location_master.location_code', '=', 'actual_loading_hdr.spray_booth')
    ->when($searchPrd, function ($query) use ($searchPrd) {
        $query->where('actual_loading_hdr.prod_code', 'ilike', '%'.$searchPrd.'%');
    })
    ->when($prdDate, function ($query) use ($fromDate, $toDate) {
        $query->whereBetween(DB::raw('actual_loading_hdr.loading_date'), [$fromDate, $toDate]);
    })
    ->when($searchStatus, function ($query) use ($searchStatus) {
        $query->where('actual_loading_hdr.status', $searchStatus);
    })
    ->select(
        'actual_loading_hdr.id',
        'actual_loading_hdr.prod_code',
        DB::raw("to_char(actual_loading_hdr.loading_date, 'DD-MM-YYYY') as prod_date"),
        DB::raw("coalesce(stock_location_master.location_name, actual_loading_hdr.spray_booth) as spraybooth"),
        'actual_loading_hdr.status',
        'actual_loading_hdr.num_revision',
        'actual_loading_hdr.note',
        'actual_loading_hdr.created_by',
        DB::raw("to_char(actual_loading_hdr.created_at, 'DD-MM-YYYY HH24:MI') as created_at")
    )
    ->orderBy('actual_loading_hdr.id', 'desc')
    ->get();

    return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .= '<div class="dropdown-menu dropdown-menu-right">';

            if (Auth::user()->can('actualLoading-edit') && $data->status == '1') {
                $buttons .= '<a href="'. route('production.actualLoading.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                <i data-feather="file-text"></i>
                                Edit
                            </a>';
            }

            $buttons .= '<a href="'. route('production.actualLoading.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
                            <i data-feather="printer"></i>
                            Print
                        </a>';

            if (Auth::user()->can('actualLoading-posting') && $data->status == '3') {
                $buttons .= "<a href='javascript:;'
                    class='dropdown-item'
                    data-size='sm'
                    data-ajax-delete='true'
                    data-confirm='Are You Sure want to post This number?'
                    data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                    data-modal-id='".$data->id."'
                    data-url='". route('production.actualLoading.posting', ['id'=>Crypt::encryptString($data->id)]) ."'>
                    <i data-feather='check' class='feather-14-red'></i>
                    <span>". __('Posting') ."</span>
                    </a>";
            }

            $buttons .= '<a href="'. route('production.actualLoading.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                            <i data-feather="list"></i>
                            Detail
                        </a>';

            if (Auth::user()->can('actualLoading-delete') && $data->status == '1') {
                $buttons .= "<a href='javascript:;'
                    class='dropdown-item'
                    data-size='sm'
                    data-ajax-delete='true'
                    data-confirm='Are You Sure want to Delete?|This action can not be undone. Do you want to continue?'
                    data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                    data-modal-id='".$data->id."'
                    data-url='". route('production.actualLoading.destroy', ['id'=>Crypt::encryptString($data->id)]) ."'>
                    <i data-feather='trash-2' class='feather-14-red'></i>
                    <span>". __('Delete') ."</span>
                    </a>";
            }

            $buttons .= '</div></div>';
            return $buttons;
        })
        ->addColumn('status', function ($data) {
            $badges = ['badge-primary', 'badge-info', 'badge-success', 'badge-warning'];
            $status = ['ON PROCESS', 'VALIDATE', 'APPROVED', 'POSTED'];
            $idx = $data->status - 1;
            return "<div class='badge ".($badges[$idx] ?? 'badge-secondary')."'>".($status[$idx] ?? $data->status)."</div>";
        })
        ->rawColumns(['action', 'status'])
        ->make(true);
}

    public function listOld(Request $request)
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
            if (Auth::user()->can('actualLoading-edit')) {
                if($data->status == '1'){
                    $buttons .=         '<a href="'. route('production.actualLoading.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="file-text"></i>
                                        Edit
                                    </a>';
                }
                
            }

            if ( $data->status == '2' or $data->status == '1' ){
                // if (Auth::user()->can('actualLoading-approve')) {
                    $buttons .=     '<a href="'. route('production.actualLoading.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="check"></i>
                                        <span>'. __("Approve") .'</span>
                                    </a>';
                // }
            }

            $buttons .=         '<a href="'. route('production.actualLoading.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
                                    <i data-feather="printer"></i>
                                    Print
                                </a>';

            if (Auth::user()->can('actualLoading-posting')) {
                if($data->status == '3'){
                    $buttons .="<a href='javascript:;'
                    class='dropdown-item' 
                    data-size='sm'
                    data-ajax-delete='true'
                    data-confirm='Are You Sure want to post This number?' 
                    data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                    data-modal-id='".$data->id."'
                    data-url='". route('production.actualLoading.posting', ['id'=>Crypt::encryptString($data->id)]) ."'>
                    <i data-feather='check' class='feather-14-red'></i>
                    <span>". __('Posting') ."</span>
                    </a>";
                }
            }

            // if (Auth::user()->can('actualLoading-revision')) {
            //     if (($data->status == '2') || ($data->status == '3') ){
            //         $buttons .=         '<a href="'. route('production.actualLoading.revision', ['id'=>Crypt::encryptString($data->id),'nR'=>$data->num_revision]) .'" class="dropdown-item">
            //                                 <i data-feather="copy"></i>
            //                                 <span>'. __("Revision") .'</span>
            //                             </a>';
            //     }
            // }
            
            $buttons .=         '<a href="'. route('production.actualLoading.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    Detail
                                </a>';
                
            // if (Auth::user()->can('actualLoading-delete')) {
            //     if ( $data->status == '1' ){
            //         if (Auth::user()->can('workingOrder-delete')) {
            //             $buttons .=         "<a href='javascript:;'
            //                                 class='dropdown-item' 
            //                                 data-size='sm'
            //                                 data-ajax-delete='true'
            //                                 data-confirm='Are You Sure want to Delete?|This action can not be undone. Do you want to continue?' 
            //                                 data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
            //                                 data-modal-id='".$data->id."'
            //                                 data-url='". route('production.actualLoading.destroy', ['id'=>Crypt::encryptString($data->id)]) ."'>
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

        $pdf = PDF::loadView('production.actualLoading.print');
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
            updated_at
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
            '".date('Y-m-d H:i:s')."' 
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
            return redirect()->route('production.actualLoading.edit', ['id'=>Crypt::encryptString($id)]);
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
        $filename = str_replace('/','_', $wosNumber);
        return Excel::download(new ActualLoadingExport($wosNumber), $filename.'.xlsx');
	}

    public function importExcel(Request $request)
    {

        $wosNumber = $request->aWosNumber;

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
        db::table('import_actual_loading_tmp')->delete();
        Excel::import(new ActualLoadingImport($data), $file);

        $dataValidasi = DB::table('import_actual_loading_tmp')
        ->leftJoin('article','article.article_alternative_code','import_actual_loading_tmp.article_code')
        ->select('import_actual_loading_tmp.article_code'
        ,'article_desc'
        ,'qty_fresh'
        ,'qty_repaint'
        ,'qty_tag'
        // ,DB::RAW("concat(
        //     case when import_actual_loading_tmp.qty_fresh::text ~ '^[0-9.]+$' = false then concat('Urutan ',row_number() over(),': Qty Actual Fresh salah : ',qty_fresh,'<br>') end,
        //     case when import_actual_loading_tmp.qty_repaint::text ~ '^[0-9.]+$' = false then concat('Urutan ',row_number() over(),': Qty Actual Repaint salah : ',qty_repaint,'<br>') end,
        //     case when import_actual_loading_tmp.qty_tag::text ~ '^[0-9.]+$' = false then concat('Urutan ',row_number() over(),': Qty Actual Tag salah : ',qty_tag,'<br>') end,
        //     case when article_desc is null and import_actual_loading_tmp.article_code <> 'gantiwarna' and import_actual_loading_tmp.article_code <> 'istirahat' then concat('Urutan ',row_number() over(),': Article Code:',import_actual_loading_tmp.article_code, ' tidak terdaftar <br>') end,
        //     case when import_actual_loading_tmp.wo_code != '$wosNumber' then concat('Urutan ',row_number() over(),': WOS Code:',import_actual_loading_tmp.wo_code, ' tidak sesuai <br>Seharusnya $wosNumber') end
        //     ) as notes")
        // )
        ,DB::RAW("concat(
            case when import_actual_loading_tmp.qty_fresh::text ~ '^[0-9.]+$' = false then concat('Urutan ',row_number() over(),': Qty Actual Fresh salah : ',qty_fresh,'<br>') end,
            case when import_actual_loading_tmp.qty_repaint::text ~ '^[0-9.]+$' = false then concat('Urutan ',row_number() over(),': Qty Actual Repaint salah : ',qty_repaint,'<br>') end,
            case when article_desc is null and import_actual_loading_tmp.article_code <> 'gantiwarna' and import_actual_loading_tmp.article_code <> 'istirahat' then concat('Urutan ',row_number() over(),': Article Code:',import_actual_loading_tmp.article_code, ' tidak terdaftar <br>') end,
            case when import_actual_loading_tmp.wo_code != '$wosNumber' then concat('Urutan ',row_number() over(),': WOS Code:',import_actual_loading_tmp.wo_code, ' tidak sesuai <br>Seharusnya $wosNumber') end
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

            $data = DB::table('wo_det')
            ->leftJoin('import_actual_loading_tmp', function ($join) {
                $join->on('import_actual_loading_tmp.wo_code', '=', 'wo_det.wo_code');
                $join->on('import_actual_loading_tmp.urutan', '=', 'wo_det.urutan');
            })
            ->leftJoin('article','article.article_code','=','wo_det.article_code')
            ->where('wo_det.wo_code',$wosNumber)
            ->select('wo_det'.'.*'
            ,DB::raw("case when so_code ='other' then wo_det.article_code else concat(article.article_alternative_code,' - ',article.article_desc) end as article")
            , 'article.article_alternative_code'
            ,'article.article_desc'
            ,'import_actual_loading_tmp.qty_fresh as qty_fresh_x'
            ,'import_actual_loading_tmp.qty_repaint as qty_repaint_x'
            ,'import_actual_loading_tmp.qty_tag as qty_tag_x'
            )
            ->orderBy('wo_det.urutan')
            ->get();

            $status = 1;
            $alert = "success";
            $message  = "$title is successfully imported";

            db::table('import_actual_loading_tmp')->where('file_name', $namaFile)->delete();

        }
                  
        return response()->json(array('status' => $status,'title' => $title, 'message' => $message,'alert' =>$alert,'dataDetail'=>$data,'pesan'=>$pesan));
    }
}
