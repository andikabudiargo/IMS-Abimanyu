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
use PDF;
use AppHelpers;
use Approval;

class DnReplaceController extends Controller
{
    private $title;
    private $moduleCode;
    private $decimalPlaces;
    private $lockDate;
    private $lockDateIndex;

    /*
        Status header (dn_replace_hdr.status / dn_return_hdr.status):
            '1' => OPEN
            '2' => CLOSED
            '3' => CANCELED

        CATATAN REFACTOR (v6):
        =======================
        (v5 tetap berlaku, lihat riwayat sebelumnya. Perubahan baru di v6:)

        14. FIX: decrementStock() sekarang mengisi kolom `dept_code` (dari
            article.article_type) & `uom` (dari article.uom) saat insert baris
            baru ke warehouse_stock. Sebelumnya insert tidak mengisi dept_code
            sama sekali padahal kolom itu NOT NULL di database -> query gagal
            dengan "null value in column dept_code violates not-null constraint"
            setiap kali stock untuk artikel tsb belum pernah ada baris-nya di
            lokasi 007. Baris yang SUDAH ADA tidak disentuh dept_code/uom-nya
            (hanya article_qty yang di-decrement), supaya data existing tidak
            tertimpa.

        15. REFACTOR BESAR (Opsi A - full audit trail, menyamakan pola dengan
            DeliveryController): unPosting() tidak lagi MENGHAPUS baris
            warehouse_movement lama. Sebagai gantinya, method ini meng-INSERT
            baris movement baru bertipe 'CANCEL REPLACEMENT' atau
            'REVISI REPLACEMENT' (movement_plus = qty yang dikembalikan),
            sehingga movement asli ('REPLACEMENT', barang keluar) tetap
            tersimpan sebagai history dan bisa ditelusuri kapan & kenapa
            barang itu dikembalikan. $movementType dan $reason ditentukan oleh
            pemanggil (update/cancel/destroy/revision) sama seperti pola
            DeliveryController::unPosting($dnNumber, $reason, $movementType).

        16. KONSEKUENSI dari #15: wasPosted() diredefinisi. Karena movement
            lama tidak pernah dihapus lagi, exists() sederhana tidak valid
            (akan selalu true walau sudah di-reverse). Sekarang dihitung dari
            NET qty: total movement_min bertipe 'REPLACEMENT' dikurangi total
            movement_plus dari movement reversal ('CANCEL REPLACEMENT' /
            'REVISI REPLACEMENT'). Kalau net > 0, dokumen dianggap masih
            "secara stock" ter-posting. Ini juga otomatis benar untuk siklus
            posting berulang (mis. update() yang unPosting lalu postingInline
            lagi berkali-kali pada replace_number yang sama).

        17. unPosting() sekarang juga memastikan baris warehouse_stock ada
            (updateOrInsert dept_code/uom) SEBELUM increment, konsisten dengan
            decrementStock(), supaya tidak silent no-op kalau barisnya
            (secara tidak wajar) belum pernah ada.

        18. Pemanggil unPosting() (update/cancel/destroy/revision) sekarang
            mengirim $reason & $movementType yang berbeda-beda supaya history
            movement bisa dibedakan:
                - update()   -> reason "Update by <user>",   type 'REVISI REPLACEMENT'
                - cancel()   -> reason dari form cancel,      type 'CANCEL REPLACEMENT'
                - destroy()  -> reason "Delete by <user>",    type 'CANCEL REPLACEMENT'
                - revision() -> reason dari form revisi,      type 'REVISI REPLACEMENT'

        CATATAN v5 (tetap berlaku):
        1. FATAL FIX: menghapus tag "<?php" kedua yang nyelip di tengah body class.
        2. FIX KRITIS: movement_type disamakan jadi 'REPLACEMENT' di SEMUA tempat
           insert warehouse_movement untuk posting (store/update/posting/revision).
        3. FIX: status setelah posting benar-benar '2' (CLOSED) via applyReplaceStatus().
        4. REFACTOR: logika posting diekstrak ke postingInline().
        5. FIX: unPosting() dulu dipanggil dengan argumen salah & tidak return apa pun.
        6. FIX: double $seq++ di store().
        7. FIX: dropdown <option> $selected sekarang benar-benar dipakai.
        8. store()/update()/posting() punya guard kuota via assertNotExceedReturn(),
           lock row sebelum decrement.
        9. update() cek wasPosted() sebelum reverse stock lama.
        10. cancel() dibungkus transaction, tidak lagi menimpa return_number dengan
            po_number, reason lewat binding (bukan raw concat).
        11. destroy() dibungkus transaction, fix bug explode() nomor urut.
        12. average_cost() dihapus dari query posting() karena tidak terpakai.
        13. getTableColoumnDetail(): label created_at_1 diperbaiki jadi 'Created At'.
    */

    public function __construct()
    {
        $this->title = "DN Replace";
        $this->moduleCode = "DN-REPLACE";
        $this->decimalPlaces = config('globalParam.decimal');
    }

    public function getTableColoumn(){
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action', 'orderable'=> false,'searchable'=>false],
            ['data'=>'replace_number','name'=>'replace_number','title'=>'Rec Number'],
            ['data'=>'return_number','name'=>'return_number','title'=>'Return Number'],
            ['data'=>'tanggal_replace','name'=>'tanggal_replace','title'=>'Replace Date'],
            ['data'=>'customer_id','name'=>'customer_id','title'=>'Customer Code'],
            ['data'=>'customer_name','name'=>'customer_name','title'=>'Customer'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created At']
        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnDetail(){
        $kolom=
        [
            ['data'=>'replace_number','name'=>'replace_number','title'=>'Rec Number'],
            ['data'=>'return_number','name'=>'return_number','title'=>'Return Number'],
            ['data'=>'tanggal_replace','name'=>'tanggal_replace','title'=>'Replace Date'],
            ['data'=>'customer_id','name'=>'customer_id','title'=>'Customer Code'],
            ['data'=>'customer_name','name'=>'customer_name','title'=>'Customer'],
            ['data'=>'article_alternative_code','name'=>'article_alternative_code','title'=>'Article Code'],
            ['data'=>'article_desc','name'=>'article_desc','title'=>'Article Desc'],
            ['data'=>'qty','name'=>'qty','title'=>'qty'],
            ['data'=>'uom','name'=>'uom','title'=>'uom'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'created_by_1','name'=>'created_by_1','title'=>'Created By'],
            ['data'=>'created_at_1','name'=>'created_at_1','title'=>'Created At'],
        ];
        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $data['title'] = $this->title;
        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        $data['status'] = ['1'=>'OPEN','2'=>'CLOSED','3'=>'CANCELED'];
        $data['kolom'] = $this->getTableColoumn();
        $data['kolomDetail'] = $this->getTableColoumnDetail();

        return view("dnReplace.index",$data);
    }

    public function getLastCode($key)
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

        $newCode = str_pad($newCode,5,"0", STR_PAD_LEFT);
        $month = str_pad(date('n'),2,"0", STR_PAD_LEFT);
        $year = date('y');
        $prNumber="$key-$year-$month-$newCode";

        return $prNumber;
    }

    public function create(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create $this->title";
        $data['cust'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();
        $data['oEdit']=false;

        return view("dnReplace.create",$data);
    }

    /**
     * Kurangi stock (dengan lock row untuk mencegah race condition saat ada
     * 2 proses posting bersamaan). Pengecekan "stock cukup atau tidak" SENGAJA
     * DIHILANGKAN -- stock boleh minus. Kuota divalidasi ke qty return lewat
     * assertNotExceedReturn(), bukan ke ketersediaan stock FG.
     *
     * dept_code & uom diambil dari tabel article (article_type & uom) HANYA
     * dipakai saat INSERT baris baru -- baris yang sudah ada tidak disentuh
     * dept_code/uom-nya (hanya article_qty yang di-decrement), supaya data
     * existing tidak tertimpa oleh nilai master artikel yang mungkin berbeda.
     *
     * Return: avg_price baris stock (untuk dicatat di movement_price), atau 0
     * kalau baris stock belum ada sebelumnya.
     */
    private function decrementStock($articleCode, $locationCode, $qtyKeluar)
    {
        if ($qtyKeluar <= 0) {
            return null;
        }

        $siteCode = 'HO';

        $stockRow = DB::table('warehouse_stock')
            ->where('site_code', $siteCode)
            ->where('article_code', $articleCode)
            ->where('location_number', $locationCode)
            ->lockForUpdate()
            ->first();

        if ($stockRow) {
            DB::table('warehouse_stock')
                ->where('site_code', $siteCode)
                ->where('article_code', $articleCode)
                ->where('location_number', $locationCode)
                ->decrement('article_qty', $qtyKeluar);

            return $stockRow->avg_price ?? 0;
        }

        // Baris belum ada -- lengkapi dept_code & uom dari master artikel
        // supaya tidak melanggar NOT NULL constraint pada dept_code.
        $article = DB::table('article')
            ->where('article_code', $articleCode)
            ->select('article_type', 'uom')
            ->first();

        DB::table('warehouse_stock')->insert([
            'site_code'       => $siteCode,
            'article_code'    => $articleCode,
            'location_number' => $locationCode,
            'article_qty'     => -$qtyKeluar,
            'dept_code'       => $article->article_type ?? '',
            'uom'             => $article->uom ?? '',
        ]);

        return 0;
    }

    /**
     * Validasi kuota terhadap RETURN (pengganti cek stock):
     * total qty replace utk 1 artikel tidak boleh melebihi qty di dn_return_det,
     * dihitung akumulasi dari semua dokumen replace non-CANCELED utk return tsb.
     */
    private function assertNotExceedReturn($returnNumber, $articleCode, $qtyNow, $excludeReplaceNumber = null)
    {
        $qtyReturn = (float) DB::table('dn_return_det')
            ->where('return_number', $returnNumber)
            ->where('article_code', $articleCode)
            ->sum('qty');

        $qtyReplacedLain = (float) DB::table('dn_replace_det')
            ->join('dn_replace_hdr', 'dn_replace_hdr.replace_number', '=', 'dn_replace_det.replace_number')
            ->where('dn_replace_det.return_number', $returnNumber)
            ->where('dn_replace_det.article_code', $articleCode)
            ->whereNotIn('dn_replace_hdr.status', ['3'])
            ->when($excludeReplaceNumber, function ($q) use ($excludeReplaceNumber) {
                $q->where('dn_replace_det.replace_number', '<>', $excludeReplaceNumber);
            })
            ->sum('dn_replace_det.qty');

        if (($qtyReplacedLain + $qtyNow) > $qtyReturn) {
            $sisa = $qtyReturn - $qtyReplacedLain;
            throw new \Exception("Qty replace artikel {$articleCode} melebihi qty return. Sisa yang boleh di-replace: {$sisa}");
        }
    }

    /**
     * Sisa qty return yang belum dipakai oleh dokumen replace manapun (agregat
     * total per return_number, bukan per-artikel -- kalau butuh validasi
     * per-artikel yang lebih ketat, ini perlu dipecah per article_code).
     */
    private function sisaReturn($returnNumber)
    {
        $result = DB::select("
            SELECT sum(qty) - sum(qty_replace) as sisa_return
            FROM (
                SELECT *,
                (SELECT sum(qty) FROM dn_replace_det
                 WHERE return_number = dn_return_det.return_number
                 AND article_code = dn_return_det.article_code) as qty_replace
                FROM dn_return_det
                WHERE return_number = ?
            ) as oki
        ", [$returnNumber]);

        return $result[0]->sisa_return ?? 0;
    }

    /** Update status dn_return_hdr: CANCELED('3')/habis kalau sisa == 0, selain itu OPEN('1'). */
    private function applyReturnStatus($returnNumber, $username)
    {
        DB::table('dn_return_hdr')
            ->where('return_number', $returnNumber)
            ->update([
                'status'     => ($this->sisaReturn($returnNumber) == 0) ? '3' : '1',
                'updated_by' => $username,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    /** Update status dn_replace_hdr: CLOSED('2') kalau qty return sudah habis, OPEN('1') kalau masih sisa. */
    private function applyReplaceStatus($replaceNumber, $returnNumber, $username)
    {
        $status = ($this->sisaReturn($returnNumber) == 0) ? '2' : '1';

        DB::table('dn_replace_hdr')
            ->where('replace_number', $replaceNumber)
            ->update([
                'status'     => $status,
                'updated_by' => $username,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return $status;
    }

    /**
     * Posting inline: kurangi stock FG (007) untuk setiap baris dn_replace_det
     * milik $replaceNumber, lalu insert warehouse_movement-nya. Dipakai bareng
     * oleh store(), update(), posting(), dan revision() supaya logikanya tidak
     * diduplikasi 4x.
     */
    private function postingInline($replaceNumber, $returnNumber, $customer, $note, $username, $excludeReplaceNumber = null)
    {
        $siteCode     = 'HO';
        $locationFG   = '007';
        $todayDate    = date('Y-m-d');
        $movementDate = date('d-m-Y');

        $detail = DB::table('dn_replace_det')
            ->leftJoin('article', 'article.article_code', '=', 'dn_replace_det.article_code')
            ->where('dn_replace_det.replace_number', $replaceNumber)
            ->where('dn_replace_det.qty', '<>', 0)
            ->select(
                'dn_replace_det.*',
                'article.article_type',
                'article.article_desc',
                'article.uom as uom_article'
            )
            ->get();

        $seq = (int) DB::table('warehouse_movement')->max('movement_code');
        $dataSetMovement = [];

        foreach ($detail as $val) {
            $qtyKeluar = (float) $val->qty;
            if ($qtyKeluar <= 0) {
                continue;
            }

            // Validasi kuota ke RETURN (bukan cek stock cukup)
            $this->assertNotExceedReturn($returnNumber, $val->article_code, $qtyKeluar, $excludeReplaceNumber);

            // Kurangi stock (boleh minus, sudah tervalidasi ke return di atas)
            $stockFG = $this->decrementStock($val->article_code, $locationFG, $qtyKeluar);

            $seq++;
            $dataSetMovement[] = [
                'movement_code'     => $seq,
                'movement_date'     => $movementDate,
                'artikel_code'      => $val->article_code,
                'artikel_desc'      => $val->article_desc ?? '',
                'movement_min'      => $qtyKeluar,   // keluar dari FG
                'movement_plus'     => 0,
                'movement_price'    => $stockFG ?? 0,
                'movement_transnno' => $replaceNumber,
                'movement_type'     => 'REPLACEMENT',
                'movement_desc'     => $note ?: $replaceNumber,
                'created_by'        => $username,
                'created_at'        => date('Y-m-d H:i:s'),
                'site_code'         => $siteCode,
                'location_number'   => $locationFG,
                'last_qty'          => DB::raw("get_last_qty_new('{$val->article_code}','$todayDate','$siteCode','$locationFG') - $qtyKeluar"),
                'movement_from'     => $locationFG,  // dari gudang FG 007
                'movement_to'       => $customer,    // ke customer
                'partner_type'      => 'CUST',
            ];
        }

        if (!empty($dataSetMovement)) {
            DB::table('warehouse_movement')->insert($dataSetMovement);
        }

        return count($dataSetMovement);
    }

    /**
     * Reverse posting (Opsi A - full audit trail): kembalikan stock FG yang
     * sudah dikurangi lewat warehouse_movement milik $replaceNumber, TAPI
     * TIDAK menghapus movement lama -- sebagai gantinya INSERT movement baru
     * bertipe $movementType (mis. 'CANCEL REPLACEMENT' / 'REVISI REPLACEMENT')
     * dengan movement_plus = qty yang dikembalikan. Movement asli
     * ('REPLACEMENT') tetap tersimpan sebagai history barang keluar.
     *
     * Pola ini menyamakan dengan DeliveryController::unPosting($dnNumber,
     * $reason, $movementType).
     *
     * PENTING: method ini TIDAK membuka/menutup transaction sendiri -- dipanggil
     * dari dalam transaction milik caller (update()/cancel()/destroy()/revision()).
     */
    private function unPosting($replaceNumber, $username, $reason = '', $movementType = 'REVERSE REPLACEMENT')
    {
        $siteCode   = 'HO';
        $locationFG = '007';
        $todayDate  = date('Y-m-d');

        $detail = DB::table('dn_replace_det')
            ->leftJoin('article', 'article.article_code', '=', 'dn_replace_det.article_code')
            ->where('dn_replace_det.replace_number', $replaceNumber)
            ->where('dn_replace_det.qty', '<>', 0)
            ->select(
                'dn_replace_det.*',
                'article.article_type',
                'article.article_desc',
                'article.uom as uom_article'
            )
            ->get();

        $seq = (int) DB::table('warehouse_movement')->max('movement_code');
        $dataSetMovement = [];

        foreach ($detail as $val) {
            $qtyKembali = (float) $val->qty;
            if ($qtyKembali <= 0) {
                continue;
            }

            // Pastikan baris stock ada (lengkap dept_code/uom) sebelum increment,
            // konsisten dengan decrementStock() -- supaya tidak silent no-op.
            DB::table('warehouse_stock')
                ->updateOrInsert(
                    [
                        'site_code'       => $siteCode,
                        'article_code'    => $val->article_code,
                        'location_number' => $locationFG,
                    ],
                    [
                        'dept_code' => $val->article_type ?? '',
                        'uom'       => $val->uom_article ?? '',
                    ]
                );

            DB::table('warehouse_stock')
                ->where('site_code', $siteCode)
                ->where('article_code', $val->article_code)
                ->where('location_number', $locationFG)
                ->increment('article_qty', $qtyKembali);

            // INSERT movement pengembalian (bukan hapus movement lama) supaya
            // ada jejak audit yang membedakan CANCEL vs REVISI vs reverse biasa.
            $seq++;
            $dataSetMovement[] = [
                'movement_code'     => $seq,
                'movement_date'     => date('d-m-Y'),
                'artikel_code'      => $val->article_code,
                'artikel_desc'      => $val->article_desc ?? '',
                'movement_min'      => 0,
                'movement_plus'     => $qtyKembali,   // masuk kembali ke FG
                'movement_price'    => 0,
                'movement_transnno' => $replaceNumber,
                'movement_type'     => $movementType, // 'CANCEL REPLACEMENT' / 'REVISI REPLACEMENT'
                'movement_desc'     => trim($replaceNumber . ($reason ? " ($reason)" : '')),
                'created_by'        => $username,
                'created_at'        => date('Y-m-d H:i:s'),
                'site_code'         => $siteCode,
                'location_number'   => $locationFG,
                'last_qty'          => DB::raw("get_last_qty_new('{$val->article_code}','$todayDate','$siteCode','$locationFG') + $qtyKembali"),
                'movement_from'     => null,          // kembali dari customer
                'movement_to'       => $locationFG,   // masuk ke gudang FG 007
                'partner_type'      => 'CUST',
            ];
        }

        if (!empty($dataSetMovement)) {
            DB::table('warehouse_movement')->insert($dataSetMovement);
        }

        \LogActivity::addToLog("Unposting $this->title", "username: $username Status $replaceNumber stock reversed ($movementType)" . ($reason ? " Reason: $reason" : ''));

        return true;
    }

    /**
     * Apakah dokumen ini SAAT INI masih "secara stock" ter-posting?
     *
     * Karena unPosting() (Opsi A) tidak lagi menghapus movement lama, exists()
     * sederhana tidak valid lagi. Dihitung dari NET qty: total movement_min
     * bertipe 'REPLACEMENT' dikurangi total movement_plus dari movement
     * reversal ('CANCEL REPLACEMENT' / 'REVISI REPLACEMENT'). Kalau net > 0,
     * berarti masih ada qty yang "keluar" dan belum dikembalikan.
     *
     * Ini otomatis benar untuk siklus posting berulang (mis. update() yang
     * unPosting lalu postingInline lagi beberapa kali pada replace_number
     * yang sama -- movement_min akan terus bertambah tiap kali posting ulang,
     * begitu juga movement_plus tiap kali di-reverse, dan selisihnya tetap
     * mencerminkan kondisi stock terkini).
     */
    private function wasPosted($replaceNumber)
    {
        $qtyKeluar = (float) DB::table('warehouse_movement')
            ->where('movement_transnno', $replaceNumber)
            ->where('movement_type', 'REPLACEMENT')
            ->sum('movement_min');

        $qtyKembali = (float) DB::table('warehouse_movement')
            ->where('movement_transnno', $replaceNumber)
            ->whereIn('movement_type', ['CANCEL REPLACEMENT', 'REVISI REPLACEMENT'])
            ->sum('movement_plus');

        return $qtyKeluar > $qtyKembali;
    }

    public function store(Request $request)
    {
        $username     = Auth::user()->username;
        $articles     = json_decode($request->articles);
        $replaceDate  = $request->replaceDate;
        $returnNumber = $request->returnNumber;
        $customer     = $request->customer;
        $note         = $request->note;
        $leadCode     = $this->moduleCode;

        $customMessages = [
            'required' => 'The field is required.',
            'unique'   => 'The code has already been taken',
        ];

        $validation = Validator::make($request->all(), [
            'returnNumber' => 'required',
            'replaceDate'  => 'required',
        ], $customMessages);

        if ($validation->fails()) {
            $error_array = [];
            foreach ($validation->messages()->getMessages() as $field_name => $messages) {
                $error_array[] = $messages;
            }
            return response()->json(['status' => 0, 'title' => "Save $this->title", 'message' => $error_array, 'alert' => 'error']);
        }

        // GUARD: harus ada minimal 1 artikel
        if (empty($articles)) {
            return response()->json(['status' => 0, 'title' => "Save $this->title", 'message' => 'Article list is empty', 'alert' => 'error']);
        }

        $hasilUpdate   = AppHelpers::resetCode($leadCode);
        $replaceNumber = $this->getLastCode($leadCode);

        DB::beginTransaction();
        try {
            // ===== 1. INSERT HEADER (status sementara OPEN, difinalkan di langkah 4) =====
            $idKu = DB::table('dn_replace_hdr')->insertGetId([
                'replace_number'        => $replaceNumber,
                'return_number'         => $returnNumber,
                'replace_date'          => $replaceDate,
                'customer_id'           => $customer,
                'status'                => '1',
                'note'                  => $note,
                'origin_replace_number' => $replaceNumber,
                'created_by'            => $username,
                'updated_by'            => $username,
                'created_at'            => date('Y-m-d H:i:s'),
                'updated_at'            => date('Y-m-d H:i:s'),
            ]);

            $idKuEnc = Crypt::encryptString($idKu);

            // ===== 2. INSERT DETAIL =====
            $dataSet = [];
            foreach ($articles as $val) {
                $dataSet[] = [
                    'replace_number' => $replaceNumber,
                    'return_number'  => $returnNumber,
                    'article_code'   => $val->article_code,
                    'qty_return'     => $val->qty_return,
                    'qty'            => $val->qty,
                    'uom'            => $val->uom,
                    'created_by'     => $username,
                    'updated_by'     => $username,
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_at'     => date('Y-m-d H:i:s'),
                ];
            }
            DB::table('dn_replace_det')->insert($dataSet);

            // ===== 3. POSTING: kurangi stock FG (007) + movement =====
            $this->postingInline($replaceNumber, $returnNumber, $customer, $note, $username, $replaceNumber);

            // ===== 4. FINALISASI STATUS (replace & return) berdasarkan sisa qty return =====
            $status = $this->applyReplaceStatus($replaceNumber, $returnNumber, $username);
            $this->applyReturnStatus($returnNumber, $username);

            DB::commit();

            $title   = "Save $this->title";
            $alert   = "success";
            $message = "$title $replaceNumber successfully saved & posted";
            \LogActivity::addToLog($title, "username: $username Status $message");

            return response()->json([
                'statusReplace' => ($status == '2') ? 'CLOSED' : 'OPEN',
                'title'         => $title,
                'status'        => 1,
                'message'       => $message,
                'alert'         => $alert,
                'replaceNumber' => $replaceNumber,
                'idKu'          => $idKuEnc,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $title   = "Save $this->title";
            $alert   = "warning";
            $message = "$title $replaceNumber failed: " . $e->getMessage();
            \LogActivity::addToLog($title, "username: $username Status $message");

            return response()->json([
                'statusReplace' => 'FAILED',
                'title'         => $title,
                'status'        => 0,
                'message'       => $message,
                'alert'         => $alert,
                'replaceNumber' => $replaceNumber,
                'idKu'          => '',
            ]);
        }
    }

    public function storeTidakPosting(Request $request)
    {
        // Method ini sengaja TIDAK memotong stock / membuat movement.
        // update()/cancel()/destroy() mendeteksi ini otomatis lewat wasPosted(),
        // jadi dokumen yang dibuat lewat sini aman diedit tanpa memicu bug "phantom stock".
        $username     = Auth::user()->username;
        $articles     = json_decode($request->articles);
        $replaceDate  = $request->replaceDate;
        $returnNumber = $request->returnNumber;
        $customer     = $request->customer;
        $note         = $request->note;
        $status       = '1';
        $leadCode     = $this->moduleCode;

        $customMessages = [
            'required' => 'The field is required.',
            'unique'   => 'The code has already been taken',
        ];

        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) use ($returnNumber) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            $column2 = $query->getGrammar()->wrap($parameters[2]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])
                          ->whereRaw("lower({$column2}) = lower(?)", [$returnNumber])->count();
        });

        $validation = Validator::make($request->all(), [
            'returnNumber' => 'required',
            'replaceDate'  => 'required',
        ], $customMessages);

        if ($validation->fails()) {
            $error_array = [];
            foreach ($validation->messages()->getMessages() as $field_name => $messages) {
                $error_array[] = $messages;
            }
            $title = "Save $this->title";
            $alert = "error";
            return response()->json(['status' => 0, 'title' => $title, 'message' => $error_array, 'alert' => $alert]);
        }

        if (empty($articles)) {
            return response()->json(['status' => 0, 'title' => "Save $this->title", 'message' => 'Article list is empty', 'alert' => 'error']);
        }

        $hasilUpdate   = AppHelpers::resetCode($leadCode);
        $replaceNumber = $this->getLastCode($leadCode);

        DB::beginTransaction();
        try {
            $idKu = DB::table('dn_replace_hdr')->insertGetId([
                'replace_number'        => $replaceNumber,
                'return_number'         => $returnNumber,
                'replace_date'          => $replaceDate,
                'customer_id'           => $customer,
                'status'                => $status,
                'note'                  => $note,
                'origin_replace_number' => $replaceNumber,
                'created_by'            => $username,
                'updated_by'            => $username,
                'created_at'            => date('Y-m-d H:i:s'),
                'updated_at'            => date('Y-m-d H:i:s'),
            ]);

            $idKuEnc = Crypt::encryptString($idKu);

            $dataSet = [];
            foreach ($articles as $val) {
                $dataSet[] = [
                    'replace_number' => $replaceNumber,
                    'return_number'  => $returnNumber,
                    'article_code'   => $val->article_code,
                    'qty_return'     => $val->qty_return,
                    'qty'            => $val->qty,
                    'uom'            => $val->uom,
                    'created_by'     => $username,
                    'updated_by'     => $username,
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_at'     => date('Y-m-d H:i:s'),
                ];
            }
            DB::table('dn_replace_det')->insert($dataSet);

            $this->applyReturnStatus($returnNumber, $username);

            DB::commit();
            $title         = "Save $this->title";
            $alert         = "success";
            $message       = "$title $replaceNumber is successfully saved";
            $statusReplace = 'NEW';
            \LogActivity::addToLog($title, "username: $username Status $message");

            return response()->json([
                'statusReplace' => $statusReplace,
                'title'         => $title,
                'status'        => 1,
                'message'       => $message,
                'alert'         => $alert,
                'replaceNumber' => $replaceNumber,
                'idKu'          => $idKuEnc,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $title         = "Save $this->title";
            $alert         = "warning";
            $message       = "$title $replaceNumber is failed to save: " . $e->getMessage();
            $statusReplace = 'FAILED';
            \LogActivity::addToLog($title, "username: $username Status $message");

            return response()->json([
                'statusReplace' => $statusReplace,
                'title'         => $title,
                'status'        => 0,
                'message'       => $message,
                'alert'         => $alert,
                'replaceNumber' => $replaceNumber,
                'idKu'          => '',
            ]);
        }
    }

    public function show(Request $request)
    {
        $username = Auth::user()->username;
        $id = Crypt::decryptString($request->id);
        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";

        $data['header'] = DB::table('dn_replace_hdr')
        ->leftJoin('dn_return_hdr','dn_return_hdr.return_number','dn_replace_hdr.return_number')
        ->leftJoin('third_party', 'third_party.kode', '=', 'dn_replace_hdr.customer_id')
        ->select('dn_replace_hdr.*'
        ,'dn_return_hdr.dn_number'
        ,DB::raw('(select sum(qty) from dn_replace_det where replace_number = dn_replace_hdr.replace_number) as sum_qty')
        ,DB::raw('(select count(*) from dn_replace_det where replace_number = dn_replace_hdr.replace_number) as sum_row')
        ,DB::raw("concat(kode,'-',nama) as customer_name"))
        ->where('dn_replace_hdr.id',$id)
        ->get()->first();

        $replaceNumber = $data['header']->replace_number;
        $custId = $data['header']->customer_id;
        $returnNumber = $data['header']->return_number;

        $data['details'] = DB::table('dn_replace_det')
        ->leftJoin('dn_replace_hdr','dn_replace_hdr.replace_number','dn_replace_det.replace_number')
        ->leftJoin('article','article.article_code','=','dn_replace_det.article_code')
        ->where('dn_replace_det.replace_number',$replaceNumber)
        ->orderBy('dn_replace_det.id')
        ->select('dn_replace_det.*','dn_replace_det.uom',
            db::raw("concat(article.article_alternative_code,'-',article_desc) as article")
            ,DB::raw("(select qty from dn_return_det where return_number = dn_replace_det.return_number and article_code = dn_replace_det.article_code) as tot_qty_return"),
            DB::RAW("coalesce(
                (select ((select sum(qty) from dn_return_det where return_number = dn_replace_hdr.return_number and article_code = dn_replace_det.article_code) + dn_replace_det.qty) - sum(qty) as qty_return from dn_replace_det a where replace_number in (
            select replace_number from dn_replace_hdr z where z.status not in ('3') and z.return_number = dn_replace_hdr.return_number)
            and article_code = dn_replace_det.article_code),0) as qty_return")
        )
        ->get();

        $data['custs'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$replaceNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$replaceNumber,$username);

        $statusReplace = ['OPEN','CLOSED','CANCELED'];
        $data['statusReplace'] = $statusReplace[$data['header']->status-1];

        $data['oEdit']=true;

        $dataCust= DB::table("dn_return_hdr")
        ->where("customer_id",$custId)
        ->where("status","1")
        ->orderBy("return_number")
        ->select('return_number','dn_number')
        ->get();

        $output = "";
        if (count($dataCust)>0){
            $output .='<option value="Choose PO">Choose DN</option>';
            foreach ($dataCust as $row){
                // FIX: $selected sebelumnya dihitung tapi tidak pernah dipakai --
                // atribut 'selected' dulu hardcoded di semua <option>.
                $selected = $row->return_number === $returnNumber ? 'selected' : '';
                $output .='<option value="'.$row->return_number.'" '.$selected.' data-dn="'.$row->dn_number.'">'.$row->return_number.'</option>';
            }
        }

        $data['listReturn'] = $output;

        $status = ['OPEN','CLOSED','CANCELED'];
        $data['status'] = $status[$data['header']->status-1];

        return view("dnReplace.show",$data);

    }

    public function edit(Request $request)
    {
        $username = Auth::user()->username;
        $id = Crypt::decryptString($request->id);
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['header'] = DB::table('dn_replace_hdr')
        ->leftJoin('dn_return_hdr','dn_return_hdr.return_number','dn_replace_hdr.return_number')
        ->where('dn_replace_hdr.id',$id)
        ->get()->first();

        $replaceNumber = $data['header']->replace_number;
        $custId = $data['header']->customer_id;
        $returnNumber = $data['header']->return_number;

        $data['detail'] = DB::table('dn_replace_det')
        ->leftJoin('dn_replace_hdr','dn_replace_hdr.replace_number','dn_replace_det.replace_number')
        ->leftJoin('article','article.article_code','=','dn_replace_det.article_code')
        ->where('dn_replace_det.replace_number',$replaceNumber)
        ->orderBy('dn_replace_det.id')
        ->select('dn_replace_det.*','dn_replace_det.uom','article.*',
            DB::raw("(select qty from dn_return_det where return_number = dn_replace_det.return_number and article_code = dn_replace_det.article_code) as tot_qty_return"),
            DB::RAW("coalesce(
                (select ((select sum(qty) from dn_return_det where return_number = dn_replace_hdr.return_number and article_code = dn_replace_det.article_code) + dn_replace_det.qty) - sum(qty) as qty_return from dn_replace_det a where replace_number in (
            select replace_number from dn_replace_hdr z where z.status not in ('3') and z.return_number = dn_replace_hdr.return_number)
            and article_code = dn_replace_det.article_code),0) as qty_return")
        )
        ->get();

        $data['custs'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$replaceNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$replaceNumber,$username);

        $statusReplace = ['OPEN','CLOSED','CANCELED'];
        $data['statusReplace'] = $statusReplace[$data['header']->status-1];

        $data['oEdit']=true;

        $dataCust= DB::table("dn_return_hdr")
        ->where("customer_id",$custId)
        ->where("status","1")
        ->orderBy("return_number")
        ->select('return_number','dn_number')
        ->get();

        $output = "";
        if (count($dataCust)>0){
            $output .='<option value="Choose PO">Choose DN</option>';
            foreach ($dataCust as $row){
                $selected = $row->return_number === $returnNumber ? 'selected' : '';
                $output .='<option value="'.$row->return_number.'" '.$selected.' data-dn="'.$row->dn_number.'">'.$row->return_number.'</option>';
            }
        }

        $data['listReturn'] = $output;

        $status = ['OPEN','CLOSED','CANCELED'];
        $data['status'] = $status[$data['header']->status-1];

        return view("dnReplace.edit",$data);

    }

    public function update(Request $request)
    {
        $username      = Auth::user()->username;
        $replaceNumber = $request->replaceNumber;
        $replaceDate   = $request->replaceDate;
        $returnNumber  = $request->returnNumber;
        $customer      = $request->customer;
        $note          = $request->note;
        $articles      = json_decode($request->articles);

        $locationFG = '007';

        $customMessages = [
            'required' => 'The field is required.',
            'unique'   => 'The code has already been taken',
        ];

        $validation = Validator::make($request->all(), [
            'replaceDate'   => 'required',
            'replaceNumber' => 'required',
        ], $customMessages);

        if ($validation->fails()) {
            $error_array = [];
            foreach ($validation->messages()->getMessages() as $field_name => $messages) {
                $error_array[] = $messages;
            }
            return response()->json(['status' => 0, 'title' => "Update $this->title", 'message' => $error_array, 'alert' => 'error']);
        }

        if (empty($articles)) {
            return response()->json(['status' => 0, 'title' => "Update $this->title", 'message' => 'Article list is empty', 'alert' => 'error']);
        }

        DB::beginTransaction();
        try {
            // GUARD: cek dulu apakah dokumen ini SEBELUMNYA benar-benar sudah diposting
            // (net qty movement REPLACEMENT masih > 0). Kalau dokumen dibuat lewat
            // storeTidakPosting(), stock belum pernah dikurangi, jadi jangan di-reverse
            // (mencegah phantom stock). Movement lama TIDAK dihapus (Opsi A) -- di-insert
            // movement baru bertipe 'REVISI REPLACEMENT' sebagai jejak pengembalian.
            if ($this->wasPosted($replaceNumber)) {
                $this->unPosting($replaceNumber, $username, "Update by {$username}", 'REVISI REPLACEMENT');
            }

            // ===== UPDATE HEADER (status sementara OPEN, difinalkan setelah posting ulang) =====
            DB::table('dn_replace_hdr')
                ->where('replace_number', $replaceNumber)
                ->update([
                    'return_number' => $returnNumber,
                    'replace_date'  => $replaceDate,
                    'customer_id'   => $customer,
                    'status'        => '1',
                    'note'          => $note,
                    'updated_by'    => $username,
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);

            // ===== UPDATE DETAIL (delete not exist + upsert) =====
            $keepKeys = [];
            foreach ($articles as $val) {
                $keepKeys[] = $replaceNumber . $val->article_code;
            }

            DB::table('dn_replace_det')
                ->whereNotIn(DB::raw("CONCAT(replace_number,article_code)"), $keepKeys)
                ->where('replace_number', $replaceNumber)
                ->delete();

            foreach ($articles as $val) {
                DB::table('dn_replace_det')
                    ->updateOrInsert(
                        ['replace_number' => $replaceNumber, 'article_code' => $val->article_code],
                        [
                            'replace_number' => $replaceNumber,
                            'return_number'  => $returnNumber,
                            'article_code'   => $val->article_code,
                            'qty_return'     => $val->qty_return,
                            'qty'            => $val->qty,
                            'uom'            => $val->uom,
                            'updated_by'     => $username,
                            'updated_at'     => date('Y-m-d H:i:s'),
                        ]
                    );
            }

            // ===== POSTING ULANG: kurangi stock FG (007) + movement baru =====
            $this->postingInline($replaceNumber, $returnNumber, $customer, $note, $username, $replaceNumber);

            // ===== FINALISASI STATUS =====
            $status = $this->applyReplaceStatus($replaceNumber, $returnNumber, $username);
            $this->applyReturnStatus($returnNumber, $username);

            DB::commit();

            $title   = "Update $this->title";
            $alert   = "success";
            $message = "$title $replaceNumber is successfully updated";
            \LogActivity::addToLog($title, "username: $username Status $message");

            return response()->json([
                'statusReplace' => ($status == '2') ? 'CLOSED' : 'OPEN',
                'status'        => 1,
                'title'         => $title,
                'message'       => $message,
                'alert'         => $alert,
                'replaceNumber' => $replaceNumber,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            $title   = "Update $this->title";
            $alert   = "warning";
            $message = "$title $replaceNumber is failed to update: " . $e->getMessage();
            \LogActivity::addToLog($title, "username: $username Status $message");

            return response()->json([
                'statusReplace' => 'FAILED',
                'status'        => 0,
                'title'         => $title,
                'message'       => $message,
                'alert'         => $alert,
                'replaceNumber' => $replaceNumber,
            ]);
        }
    }

    public function posting(Request $request)
    {
        $username      = Auth::user()->username;
        $id            = Crypt::decryptString($request->id);
        $header        = DB::table('dn_replace_hdr')->where('id', $id)->first();
        $replaceNumber = $header->replace_number ?? null;
        $dariNew       = $request->dariNew;

        if (!$replaceNumber) {
            $title   = "Posting $this->title";
            $alert   = "warning";
            $message = "$title Failed to Posting — Replace Number not found";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);
        }

        $returnNumber = $header->return_number;
        $customer     = $header->customer_id;
        $note         = $header->note;

        // GUARD: cegah double posting -- store()/update() sudah posting inline,
        // jadi endpoint ini seharusnya cuma dipakai untuk dokumen hasil storeTidakPosting()
        // yang belum pernah ada movement-nya sama sekali.
        if ($this->wasPosted($replaceNumber)) {
            $title   = "Posting $this->title";
            $alert   = "warning";
            $message = "$title $replaceNumber already posted, cannot post again";
            \LogActivity::addToLog($title, "username: $username Status $message");

            if ($dariNew == 'true') {
                return response()->json(['statusReplace' => 'OPEN', 'title' => $title, 'status' => 0, 'message' => $message, 'alert' => $alert, 'replaceNumber' => $replaceNumber, 'idKu' => '']);
            } else {
                return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);
            }
        }

        DB::beginTransaction();
        try {
            $this->postingInline($replaceNumber, $returnNumber, $customer, $note, $username, $replaceNumber);

            $status = $this->applyReplaceStatus($replaceNumber, $returnNumber, $username);
            $this->applyReturnStatus($returnNumber, $username);

            $idKu = Crypt::encryptString($id);
            DB::commit();

            $title   = "Posting $this->title";
            $alert   = "success";
            $message = "$title $replaceNumber Successfully Posted";
            \LogActivity::addToLog($title, "username: $username Status $message");

            if ($dariNew == 'true') {
                return response()->json(['statusReplace' => ($status == '2') ? 'CLOSED' : 'OPEN', 'title' => $title, 'status' => 1, 'message' => $message, 'alert' => $alert, 'replaceNumber' => $replaceNumber, 'idKu' => $idKu]);
            } else {
                return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $title   = "Posting $this->title";
            $alert   = "warning";
            $message = "$title $replaceNumber Failed: " . $e->getMessage();
            \LogActivity::addToLog($title, "username: $username Status $message");

            if ($dariNew == 'true') {
                return response()->json(['statusReplace' => 'OPEN', 'title' => $title, 'status' => 0, 'message' => $message, 'alert' => $alert, 'replaceNumber' => $replaceNumber, 'idKu' => '']);
            } else {
                return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);
            }
        }
    }

    public function cancel(Request $request)
    {
        /*
            $data['status'] = ['1'=>'OPEN','2'=>'CLOSED','3'=>'CANCELED'];
        */

        $username = Auth::user()->username;
        $id = Crypt::decryptString($request->id);
        $status = '3';
        $reason = $request->reason;

        $header = DB::table('dn_replace_hdr')->where('id', $id)->first();

        if (!$header) {
            return redirect()->back()->with(['title' => "Cancel $this->title", 'alert' => 'warning', 'message' => 'Document not found']);
        }

        $replaceNumber = $header->replace_number;
        $returnNumber  = $header->return_number;

        // FIX: dulu tidak ada DB::beginTransaction() padahal DB::commit() dipanggil -> exception.
        DB::beginTransaction();
        try {
            // GUARD: reverse stock yang sudah diposting sebelum cancel, supaya stock FG
            // tidak nyangkut minus setelah dokumen dibatalkan. Movement asli TIDAK dihapus
            // (Opsi A) -- di-insert movement baru bertipe 'CANCEL REPLACEMENT' sebagai jejak.
            if ($this->wasPosted($replaceNumber)) {
                $this->unPosting($replaceNumber, $username, $reason, 'CANCEL REPLACEMENT');
            }

            // FIX: dulu pakai DB::raw("CONCAT(po_number,...)") ke kolom return_number,
            // padahal kolom po_number tidak ada di tabel ini dan itu merusak return_number.
            // Sekarang cukup dicatat di note lewat parameter binding (bukan raw string
            // concat) untuk menghindari SQL injection dari $reason.
            $reasonNote = "(Cancel by {$username}, Reason: {$reason})";
            $newNote = trim(($header->note ?? '') . ';' . $reasonNote, ';');

            DB::table('dn_replace_hdr')
                ->where('replace_number', $replaceNumber)
                ->update([
                    'status'     => $status,
                    'note'       => $newNote,
                    'updated_by' => $username,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            // Return-nya dibebaskan lagi setelah dokumen replace ini dibatalkan.
            $this->applyReturnStatus($returnNumber, $username);

            DB::commit();
            $title   = "Cancel $this->title";
            $alert   = "success";
            $message = "$title $replaceNumber Successfully Canceled";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);

        } catch (\Exception $e) {
            DB::rollBack();
            $title   = "Cancel $this->title";
            $alert   = "warning";
            $message = "$title $replaceNumber Failed to Cancel: " . $e->getMessage();
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);
        }
    }

    public function destroy(Request $request)
    {
        /*
            $data['status'] = ['1'=>'OPEN','2'=>'CLOSED','3'=>'CANCELED'];
        */

        $username = Auth::user()->username;
        $id = Crypt::decryptString($request->id);
        $leadCode = $this->moduleCode;

        $header = DB::table('dn_replace_hdr')
            ->where('id', $id)
            ->whereNotIn('status', ['3'])
            ->first();

        if (!$header) {
            $title   = "Delete $this->title";
            $alert   = "warning";
            $message = "$title Failed to Delete — document not found or already canceled";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['alert' => $alert, 'title' => $title, 'message' => $message]);
        }

        $replaceNumber = $header->replace_number;
        $returnNumber  = $header->return_number;

        // FIX: format replace_number = "DN-REPLACE-YY-MM-00001". Karena moduleCode
        // "DN-REPLACE" sendiri mengandung "-", explode('-')[3] dulu salah ambil "MM",
        // bukan angka urut "00001". Sekarang pakai elemen TERAKHIR dari hasil explode.
        $parts = explode('-', $replaceNumber);
        $urutan = (int) end($parts);
        $urutanSebelum = $urutan - 1;

        DB::beginTransaction();
        try {
            // GUARD: reverse stock sebelum hapus dokumen, supaya stock FG tidak nyangkut
            // minus. Movement asli TIDAK dihapus (Opsi A) -- di-insert movement baru
            // bertipe 'CANCEL REPLACEMENT' sebagai jejak pengembalian karena delete.
            if ($this->wasPosted($replaceNumber)) {
                $this->unPosting($replaceNumber, $username, "Delete by {$username}", 'CANCEL REPLACEMENT');
            }

            $rowAffected = DB::table('dn_replace_hdr')->where('replace_number', $replaceNumber)->delete();

            if ($rowAffected > 0) {
                DB::table('dn_replace_det')->where('replace_number', $replaceNumber)->delete();

                DB::table('master_code')
                    ->where('code_key', $leadCode)
                    ->where('code_number', $urutan)
                    ->update([
                        'code_number' => $urutanSebelum
                    ]);

                $this->applyReturnStatus($returnNumber, $username);

                DB::commit();
                $title   = "Delete $this->title";
                $alert   = "success";
                $message = "$title $replaceNumber Successfully Deleted";
                \LogActivity::addToLog($title, "username: $username Status $message");
                return redirect()->back()->with(['alert' => $alert, 'title' => $title, 'message' => $message]);
            } else {
                DB::rollBack();
                $title   = "Delete $this->title";
                $alert   = "warning";
                $message = "$title $replaceNumber Failed to Delete";
                \LogActivity::addToLog($title, "username: $username Status $message");
                return redirect()->back()->with(['alert' => $alert, 'title' => $title, 'message' => $message]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $title   = "Delete $this->title";
            $alert   = "warning";
            $message = "$title $replaceNumber Failed to Delete: " . $e->getMessage();
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['alert' => $alert, 'title' => $title, 'message' => $message]);
        }
    }

    /**
     * Buat dokumen revisi dari dokumen replace yang sudah ada: dokumen asal
     * di-unposting & di-CANCEL, lalu dokumen baru (nomor "-R<n>") dibuat,
     * detail-nya dicopy, langsung diposting ulang, dan statusnya difinalkan
     * lewat applyReplaceStatus()/applyReturnStatus() (CLOSED kalau return
     * habis, OPEN kalau masih sisa).
     */
    public function revision(Request $request)
    {
        $username = Auth::user()->username;
        $id       = Crypt::decryptString($request->id);
        $reason   = $request->reason;

        $original = DB::table('dn_replace_hdr')->where('id', $id)->first();

        if (!$original) {
            $title = "Revision $this->title";
            return redirect()->back()->with(['title' => $title, 'alert' => 'warning', 'message' => 'Document not found']);
        }

        if ($original->status == '3') {
            $title = "Revision $this->title";
            return redirect()->back()->with(['title' => $title, 'alert' => 'warning', 'message' => 'Canceled document cannot be revised']);
        }

        $recOrigin    = $original->replace_number;
        $trueOrigin   = $original->origin_replace_number ?: $recOrigin;
        $returnNumber = $original->return_number;
        $customer     = $original->customer_id;
        $note         = $original->note;

        // Nomor revisi berikutnya (server-side, dari rantai origin).
        $numRevision = DB::table('dn_replace_hdr')
            ->where('origin_replace_number', $trueOrigin)
            ->count();

        $recNew = $trueOrigin . '-R' . $numRevision;
        while (DB::table('dn_replace_hdr')->where('replace_number', $recNew)->exists()) {
            $numRevision++;
            $recNew = $trueOrigin . '-R' . $numRevision;
        }

        $detailOriginal = DB::table('dn_replace_det')
            ->where('replace_number', $recOrigin)
            ->get();

        DB::beginTransaction();
        try {
            // 1. Reverse stok dokumen asal jika sudah pernah ada movement. Movement asli
            //    TIDAK dihapus (Opsi A) -- di-insert movement baru bertipe
            //    'REVISI REPLACEMENT' sebagai jejak audit revisi.
            if ($this->wasPosted($recOrigin)) {
                $this->unPosting($recOrigin, $username, $reason, 'REVISI REPLACEMENT');
            }

            // 2. HEADER BARU (status sementara OPEN, difinalkan di langkah 6).
            $noteBaru = trim(
                ($note ?? '') . "; Revision of {$recOrigin}" . ($reason ? ", reason: {$reason}" : ''),
                '; '
            );

            $newId = DB::table('dn_replace_hdr')->insertGetId([
                'replace_number'        => $recNew,
                'return_number'         => $returnNumber,
                'replace_date'          => $original->replace_date,
                'customer_id'           => $customer,
                'status'                => '1',
                'note'                  => $noteBaru,
                'origin_replace_number' => $trueOrigin,
                'created_by'            => $username,
                'updated_by'            => $username,
                'created_at'            => date('Y-m-d H:i:s'),
                'updated_at'            => date('Y-m-d H:i:s'),
            ]);

            // 3. COPY DETAIL (return_number & qty_return ikut).
            $dataSet = [];
            foreach ($detailOriginal as $val) {
                $dataSet[] = [
                    'replace_number' => $recNew,
                    'return_number'  => $val->return_number,
                    'article_code'   => $val->article_code,
                    'qty_return'     => $val->qty_return,
                    'qty'            => $val->qty,
                    'uom'            => $val->uom,
                    'created_by'     => $username,
                    'updated_by'     => $username,
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_at'     => date('Y-m-d H:i:s'),
                ];
            }
            if (!empty($dataSet)) {
                DB::table('dn_replace_det')->insert($dataSet);
            }

            // 4. Dokumen asal -> CANCELED (digantikan) -- dilakukan SEBELUM posting
            //    dokumen baru supaya assertNotExceedReturn() tidak menghitung dokumen
            //    asal (yang qty-nya identik) sebagai pemakai kuota return.
            DB::table('dn_replace_hdr')
                ->where('replace_number', $recOrigin)
                ->update([
                    'status'     => '3',
                    'note'       => trim(($note ?? '') . "; Superseded by revision {$recNew}" . ($reason ? ", reason: {$reason}" : ''), '; '),
                    'updated_by' => $username,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            // 5. POSTING INLINE dokumen baru: potong stok FG (007) + movement.
            $this->postingInline($recNew, $returnNumber, $customer, $noteBaru, $username, $recNew);

            // 6. Status akhir: CLOSED kalau return habis, OPEN kalau masih ada sisa.
            $this->applyReplaceStatus($recNew, $returnNumber, $username);
            $this->applyReturnStatus($returnNumber, $username);

            // 7. Pindahkan approval history ke nomor baru.
            DB::table('approval_history')
                ->where('module_number', $recOrigin)
                ->update([
                    'module_number' => $recNew,
                    'status'        => '0',
                    'updated_by'    => $username,
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);

            DB::commit();

            $title   = "Revision $this->title";
            $message = "$title: $recOrigin successfully revised to $recNew";
            \LogActivity::addToLog($title, "username: $username Status $message");

            return redirect()->route('dnReplace.edit', ['id' => Crypt::encryptString($newId)])
                ->with(['title' => $title, 'alert' => 'success', 'message' => $message]);

        } catch (\Exception $e) {
            DB::rollBack();
            $title   = "Revision $this->title";
            $message = "$title: $recOrigin failed to revise: " . $e->getMessage();
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['title' => $title, 'alert' => 'warning', 'message' => $message]);
        }
    }

    public function list(Request $request)
    {
        $searchReplace = strtolower($request->searchReplace);
        $searchReturn = strtolower($request->searchReturn);
        $searchCustomer = $request->searchCustomer;
        $searchStatus = $request->searchStatus;
        $replaceDate = $request->replaceDate;
        $doDate = $request->doDate;
        $fromDate ="";
        $toDate = "";

        if ($replaceDate){
            $date = explode("to",$replaceDate);
            if(count($date)>1){
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate;
            }
        }

        $data = DB::table('dn_replace_hdr')
        ->where(function ($query) use ($searchReplace,$searchReturn,$searchCustomer,$searchStatus,$replaceDate,$fromDate,$toDate) {
            $searchReturn ? $query->where('return_number','ilike','%'.$searchReturn.'%') : '';
            $searchCustomer ? $query->where('customer_id','ilike','%'.$searchCustomer.'%') : '';
            $searchReplace ? $query->where('replace_number','ilike','%'.$searchReplace.'%') : '';
            $searchStatus ? $query->where('status',$searchStatus) : '';
            $replaceDate ? $query->whereBetween(DB::raw("to_date(replace_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
        })
        ->whereNotIn('status',['3'])
        ->select('dn_replace_hdr.*'
        ,DB::raw("(select nama from third_party where kode = dn_replace_hdr.customer_id limit 1) as customer_name")
        ,DB::raw("to_char(to_date(replace_date,'DD-MM-YYYY'),'DD-MM-YYYY') as tanggal_replace")
        )
        ->orderBy('id')
        ->get();

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';

            if ($data->status == '1'){
                if (Auth::user()->can('receiving-edit')) {
                    $buttons .=     '<a href="'. route('dnReplace.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="file-text"></i>
                                        <span>'. __("Edit") .'</span>
                                    </a>';
                }
            }

            $buttons .=         "<a href='". route('dnReplace.print', ['id'=>Crypt::encryptString($data->id)]) ."' target='_blank' class='dropdown-item'>
                                    <i data-feather='printer'></i>
                                    <span>". __('Print') ."</span>
                                </a>";

            $buttons .=         '<a href="'. route('dnReplace.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    Detail
                                </a>';

            if ( $data->status == '2' ){
                    $buttons .=         "<a href='javascript:;'
                                            id='cancelReasonButton'
                                            class='dropdown-item'
                                            data-toggle='modal'
                                            data-target='#reasonModalCancel'
                                            data-href='". route("dnReplace.cancel", ["id"=>Crypt::encryptString($data->id)]) ."'>
                                            <i data-feather='corner-down-left' class='feather-14-red'></i>
                                            <span>". __('Cancel') ."</span>
                                        </a>";
            }

            if ($data->status == '1'){
                    $buttons .=         "<a href='javascript:;'
                                        class='dropdown-item'
                                        data-size='sm'
                                        data-ajax-delete='true'
                                        data-confirm='Are You Sure want to Delete?|This action can not be undone. Do you want to continue?'
                                        data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                                        data-modal-id='".$data->id."'
                                        data-url='". route('dnReplace.destroy', ['id'=>Crypt::encryptString($data->id)]) ."'>
                                        <i data-feather='trash-2' class='feather-14-red'></i>
                                        <span>". __('Delete') ."</span>
                                    </a>";
            }

            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-warning','badge-danger','badge-dark','badge-secondary','badge-success','badge-success','badge-success'];
            $statusReplace = ['OPEN','CLOSED','CANCELED'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusReplace[$data->status - 1]."</div>";
        })
        ->rawColumns(['action','status'])
        ->make(true);
    }

    public function listDetail(Request $request)
    {
        $searchReplace = strtolower($request->searchReplace);
        $searchReturn = strtolower($request->searchReturn);
        $searchCustomer = $request->searchCustomer;
        $searchStatus = $request->searchStatus;
        $replaceDate = $request->replaceDate;
        $doDate = $request->doDate;
        $fromDate ="";
        $toDate = "";

        if ($replaceDate){
            $date = explode("to",$replaceDate);
            if(count($date)>1){
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate;
            }
        }

        $data = DB::table('dn_replace_det')
        ->leftJoin('dn_replace_hdr','dn_replace_hdr.replace_number','dn_replace_det.replace_number')
        ->leftJoin('article','article.article_code','dn_replace_det.article_code')
        ->where(function ($query) use ($searchReplace,$searchReturn,$searchCustomer,$searchStatus,$replaceDate,$fromDate,$toDate) {
            $searchReturn ? $query->where('dn_replace_det.return_number','ilike','%'.$searchReturn.'%') : '';
            $searchCustomer ? $query->where('customer_id','ilike','%'.$searchCustomer.'%') : '';
            $searchReplace ? $query->where('dn_replace_det.replace_number','ilike','%'.$searchReplace.'%') : '';
            $searchStatus ? $query->where('dn_replace_hdr.status',$searchStatus) : '';
            $replaceDate ? $query->whereBetween(DB::raw("to_date(replace_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
        })
        ->where('dn_replace_det.qty','>',0)
        ->whereNotIn('dn_replace_hdr.status',['3'])
        ->select('dn_replace_det.*'
        ,'dn_replace_hdr.*'
        ,'dn_replace_hdr.created_by as created_by_1'
        ,'dn_replace_hdr.created_at as created_at_1'
        ,'article_alternative_code'
        ,'article_desc'
        ,DB::raw("(select nama from third_party where kode = dn_replace_hdr.customer_id limit 1) as customer_name")
        ,DB::raw("to_char(to_date(replace_date,'DD-MM-YYYY'),'DD-MM-YYYY') as tanggal_replace")
        )
        ->orderBy('dn_replace_det.id')
        ->get();

        return Datatables::of($data)
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-warning','badge-danger','badge-dark','badge-secondary','badge-success','badge-success','badge-success'];
            $statusReplace = ['OPEN','CLOSED','CANCELED'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusReplace[$data->status - 1]."</div>";
        })
        ->rawColumns(['status'])
        ->make(true);
    }

    public function print(Request $request)
    {
        $id=Crypt::decryptString($request->id);

        $data['companies']=DB::table('company')
        ->where('code','ASN')
        ->select('name as nama', 'address as alamat', DB::RAW('(select region_name from regions where region_code = city::integer)  as kota'),'tlp')
        ->get()->first();

        $recHdr=DB::table('dn_replace_hdr')
        ->leftJoin('dn_return_hdr','dn_return_hdr.return_number','dn_replace_hdr.return_number')
        ->select('dn_replace_hdr.*','dn_return_hdr.dn_number')
        ->where('dn_replace_hdr.id',$id)
        ->first();

        $data['replaceHdr']=DB::table('dn_replace_hdr')
        ->leftJoin('dn_return_hdr','dn_return_hdr.return_number','dn_replace_hdr.return_number')
        ->select('dn_replace_hdr.*','dn_return_hdr.dn_number')
        ->where('dn_replace_hdr.id',$id)
        ->first();

        $replaceNumber=$recHdr->replace_number;

        $data['details']=DB::table('dn_replace_det')
        ->leftJoin('article','article.article_code','dn_replace_det.article_code')
        ->where('replace_number',$replaceNumber)
        ->where('qty','>',0)
        ->get();

        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->where('kode',$recHdr->customer_id)
        ->orderBy('nama')
        ->first();

        $status = ['OPEN','CLOSED','CANCELED'];
        $data['status'] = $status[$recHdr->status-1];

        $data['no'] =0;

        $data['title'] =$replaceNumber;

        return view('dnReplace.print',$data);
    }

    public function listReturn(Request $request)
    {
        $cust = $request->value;
        $output = "";

        $data = DB::table("dn_return_hdr")
            ->where("customer_id", $cust)
            ->where("status", "1")
            ->orderBy("return_number")
            ->select('return_number', 'dn_number')
            ->get();

        if (count($data) > 0) {
            $output .= '<option value="">Choose DN Return</option>';
            foreach ($data as $row) {
                $dnLabel = $row->dn_number ? ' (' . $row->dn_number . ')' : '';
                $output .= '<option value="' . $row->return_number . '" data-dn="' . $row->dn_number . '">'
                            . $row->return_number . $dnLabel .
                           '</option>';
            }
        }

        return $output;
    }

    public function returnDetail(Request $request)
    {
        $returnNumber = $request->value;
        $data = DB::select("SELECT
        a.*,
        a.article_code,
        article_alternative_code,
        article_desc,
        (COALESCE(a.qty,0)) as tot_qty_return,
        (COALESCE(a.qty,0)-COALESCE(b.qty,0)) as qty_return,
        a.uom,
        COALESCE(ws.qty_stock, 0) as qty_stock
        from dn_return_det a
        left join article on article.article_code = a.article_code
        left join (
            select sum(qty) as qty, return_number, article_code
            from dn_replace_det
            where return_number = ?
            group by return_number, article_code
        ) as b on a.article_code = b.article_code
        left join (
            select article_code, sum(article_qty) as qty_stock
            from warehouse_stock
            where location_number = '007'
            group by article_code
        ) as ws on ws.article_code = a.article_code
        where a.return_number = ?
        order by a.id", [$returnNumber, $returnNumber]);

        return response()->json($data);
    }

}