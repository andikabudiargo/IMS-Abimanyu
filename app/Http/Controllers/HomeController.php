<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;
use DB;
use DataTables;

/*
    4-2-2026 : ada perbaikan untuk antisipasi revisi ke approved 
*/

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */

    public function greeting()
    {
        if(date("H") < 12){
            return "Good morning";
        }elseif(date("H") > 11 && date("H") < 18){
            return "Good afternoon";
        }elseif(date("H") > 17){   
            return "Good evening";
        }
    }

    private static function formatAgingHome(float $seconds): array
{
    $seconds = (int) abs($seconds);

    if ($seconds < 60) {
        return ['label' => $seconds . ' detik', 'level' => 'success'];
    } elseif ($seconds < 3600) {
        return ['label' => floor($seconds / 60) . ' menit', 'level' => 'success'];
    } elseif ($seconds < 86400) {
        return ['label' => floor($seconds / 3600) . ' jam', 'level' => 'warning'];
    } elseif ($seconds < 259200) {
        return ['label' => floor($seconds / 86400) . ' hari', 'level' => 'warning'];
    }
    return ['label' => floor($seconds / 86400) . ' hari', 'level' => 'danger'];
}

    /**
     * conversion_value aktif dari conversion_setting -- sama seperti
     * ConversionReportController::activeConversionValue(). Disengaja disalin
     * (bukan di-share antar controller) supaya modul Home tetap independen,
     * pola yang sama dipakai di ConversionReportController/PriceListController.
     */
    private function activeConversionValueHome(): float
    {
        $conv = DB::table('conversion_setting')->where('status', '1')->orderByDesc('id')->first();
        return $conv ? (float) $conv->conversion_value : 0;
    }

    /**
     * Avg harga terima artikel dari receiving_det (weighted by qty), anchor
     * ke bulan $periode/$tahun widget Sales Achievement kalau diisi (BUKAN
     * selalu bulan berjalan -- widget ini bisa difilter ke periode lain lewat
     * salesAchievementFilter()); kalau kosong mundur bulan demi bulan. Salinan
     * persis ConversionReportController::avgReceivingPrice().
     */
    private function avgReceivingPriceHome(string $articleCode, ?int $periode = null, ?int $tahun = null, int $maxMonthsBack = 24): float
    {
        $anchor = ($periode && $tahun)
            ? \DateTime::createFromFormat('Y-n-j', "{$tahun}-{$periode}-1")
            : new \DateTime('today');
        for ($i = 0; $i <= $maxMonthsBack; $i++) {
            $monthStart = (clone $anchor)->modify("-{$i} month")->modify('first day of this month');
            $monthEnd   = (clone $monthStart)->modify('last day of this month');

            $row = DB::selectOne("
                SELECT COALESCE(SUM(price*qty)/NULLIF(SUM(qty),0),0) AS avg_price, COUNT(*) AS n
                FROM receiving_det
                WHERE article_code = ?
                  AND created_at::date BETWEEN ?::date AND ?::date
            ", [$articleCode, $monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')]);

            if ($row && $row->n > 0) {
                return (float) $row->avg_price;
            }
        }
        return 0.0;
    }

    /**
     * Avg harga jual artikel dari sales_order_det (weighted by qty), anchor
     * ke bulan $periode/$tahun widget Sales Achievement kalau diisi (BUKAN
     * selalu bulan berjalan), mundur bulan demi bulan kalau bulan itu kosong
     * -- pasangan avgReceivingPriceHome() di sisi jual. Dipakai buat estimasi
     * konversi TARGET, karena target belum punya transaksi delivery aktual
     * (masih proyeksi), jadi butuh harga jual acuan dari histori SO artikel
     * itu sendiri, bukan dari Price List (Price List belum terisi lengkap utk
     * semua artikel).
     */
    private function avgSellingPriceHome(string $articleCode, ?int $periode = null, ?int $tahun = null, int $maxMonthsBack = 24): float
    {
        $anchor = ($periode && $tahun)
            ? \DateTime::createFromFormat('Y-n-j', "{$tahun}-{$periode}-1")
            : new \DateTime('today');
        for ($i = 0; $i <= $maxMonthsBack; $i++) {
            $monthStart = (clone $anchor)->modify("-{$i} month")->modify('first day of this month');
            $monthEnd   = (clone $monthStart)->modify('last day of this month');

            $row = DB::selectOne("
                SELECT COALESCE(SUM((price+price_service)*qty)/NULLIF(SUM(qty),0),0) AS avg_price, COUNT(*) AS n
                FROM sales_order_det
                WHERE article_code = ?
                  AND created_at::date BETWEEN ?::date AND ?::date
            ", [$articleCode, $monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')]);

            if ($row && $row->n > 0) {
                return (float) $row->avg_price;
            }
        }
        return 0.0;
    }

    /**
     * Purchase price satu artikel -- salinan persis
     * ConversionReportController::purchasePrice(): BOM aktif -> total biaya
     * material (RM+DET), tidak ada BOM -> avg receiving artikel itu sendiri.
     */
    private function purchasePriceHome(string $articleCode, ?int $periode = null, ?int $tahun = null): float
    {
        // FIX: sama seperti ConversionReportController::purchasePrice() --
        // 'status != 5' ikut meloloskan BOM REVISED (7, versi lama yang sudah
        // digantikan), disamakan ke status = '3' (APPROVED) seperti semua
        // modul lain yang konsumsi BOM.
        $bom = DB::table('bom_hdr')
            ->where('article_code', $articleCode)
            ->where('status', '3')
            ->orderByDesc('id')
            ->first();

        if (!$bom) {
            return $this->avgReceivingPriceHome($articleCode, $periode, $tahun);
        }

        $rm = DB::table('bom_rm as b')
            ->leftJoin('article as a', 'a.article_code', '=', 'b.article_code')
            ->where('b.bom_code', $bom->bom_code)
            ->select('b.article_code', 'a.article_type', 'b.qty')
            ->get();

        $det = DB::table('bom_det as b')
            ->leftJoin('article as a', 'a.article_code', '=', 'b.article_code')
            ->where('b.bom_code', $bom->bom_code)
            ->whereIn('a.article_type', ['RMP', 'RMNP'])
            ->select('b.article_code', 'a.article_type', 'b.qty')
            ->get();

        $total = 0;
        foreach ($rm->concat($det) as $m) {
            $type  = strtoupper($m->article_type ?? '');
            $qty   = (float) $m->qty;
            $price = $type === 'RMNP' ? 0 : $this->avgReceivingPriceHome($m->article_code, $periode, $tahun);
            $total += $price * $qty;
        }

        return $total;
    }

    /** 12 nama bulan Indonesia, dipakai buat cari nama bulan target di dalam tso_name. */
    private const BULAN_NAMES = [
        1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL',
        5 => 'MEI', 6 => 'JUNI', 7 => 'JULI', 8 => 'AGUSTUS',
        9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER',
    ];

    /**
     * Bulan target dari tso_name, mis. "TARGET SO SEPTEMBER" / "TSO SEMIFIX
     * SEPTEMBER" -> 9. tso_date TIDAK dipakai buat ini -- terbukti dari data
     * riil, tso_date cuma tanggal dibuatnya dokumen, bisa kapan saja relatif
     * ke bulan target (pernah ketemu 3 TSO dibuat di tanggal yang SAMA tapi
     * menyasar 3 bulan target yang berbeda -- Feb/Mar/Apr, dibedakan cuma
     * lewat tso_name). Return null kalau nggak ketemu nama bulan di tso_name.
     */
    private function targetMonthFromName(string $name): ?int
    {
        $upper = strtoupper($name);
        foreach (self::BULAN_NAMES as $num => $label) {
            if (strpos($upper, $label) !== false) {
                return $num;
            }
        }
        return null;
    }

    /**
     * Widget "Sales Achievement" di Home: qty & konversi delivery bulan
     * $periode/$tahun dibandingkan Target SO -- default bulan berjalan
     * (month-to-date, dari tgl 1 s.d. hari ini), bisa difilter ke periode lain.
     *
     * Target SO yang cocok untuk $periode/$tahun = TSO status=3 (APPROVED)
     * yang nama bulan di tso_name-nya == $periode (lihat targetMonthFromName()),
     * dan TAHUN-nya diambil dari tso_date (tso_name jarang mencantumkan tahun,
     * mis. "TSO NOVEMBER" tanpa tahun, jadi tahun tetap dari tso_date). Kalau
     * tso_name tidak mengandung nama bulan sama sekali (data legacy/tidak
     * rapi), TSO itu di-skip dari perhitungan (lebih aman drop daripada nebak
     * salah bulan).
     *
     * Konversi dihitung PERSIS seperti ConversionReportController::buildSummary()
     * (avg selling dari transaksi SO/DN aktual, avg purchase dari BOM+receiving),
     * BUKAN dari Price List -- Price List belum terisi lengkap utk semua artikel
     * jadi tidak dipakai di sini. Untuk baris Target (belum ada delivery aktual)
     * avg selling didekati dari histori SO artikel itu (avgSellingPriceHome()).
     */
    private function buildSalesAchievement(?int $periode = null, ?int $tahun = null): array
    {
        $now     = Carbon::now();
        $periode = $periode ?: (int) $now->format('n');
        $tahun   = $tahun ?: (int) $now->format('Y');

        $monthStart = sprintf('%04d-%02d-01', $tahun, $periode);
        $monthEnd   = date('Y-m-t', strtotime($monthStart));

        $isCurrentMonth = ($periode == (int) $now->format('n') && $tahun == (int) $now->format('Y'));
        $achievedEnd    = $isCurrentMonth ? $now->format('Y-m-d') : $monthEnd;

        $convVal = $this->activeConversionValueHome();

       // ---- TARGET (TSO status APPROVED, bulan target dari tso_name, tahun dari tso_date) ----
$candidateHeaders = DB::table('target_order_hdr')
    ->whereColumn('tso_code', 'origin_tso_code')   // cuma row utama, bukan snapshot -R
    ->whereIn('status', ['1', '2', '3'])           // ikut tangkap yang lagi direvisi
    ->whereRaw("EXTRACT(YEAR FROM to_date(tso_date,'DD-MM-YYYY')) BETWEEN ? AND ?", [$tahun - 1, $tahun + 1])
    ->orderBy('id')
    ->get(['id', 'tso_code', 'tso_name', 'tso_date', 'status']);

$matchedTsoCodes = [];
$firstMatchedId  = null;
foreach ($candidateHeaders as $h) {
    $month = $this->targetMonthFromName((string) $h->tso_name);
    if ($month === null) {
        continue;
    }
    $dt = \DateTime::createFromFormat('d-m-Y', trim((string) $h->tso_date));
    if (!$dt) {
        continue;
    }
    if ($month === $periode && (int) $dt->format('Y') === $tahun) {
        if ($firstMatchedId === null) {
            $firstMatchedId = $h->id;
        }

        if ($h->status === '3') {
            // sudah approved, pakai data row utama seperti biasa
            $matchedTsoCodes[] = $h->tso_code;
        } else {
            // lagi direvisi (status draft/pending) -- fallback ke snapshot
            // approved terakhir (num_revision tertinggi) biar data lama tetap tampil
            $lastApprovedSnapshot = DB::table('target_order_hdr')
                ->where('origin_tso_code', $h->tso_code)
                ->where('tso_code', '<>', $h->tso_code)
                ->orderByDesc('num_revision')
                ->first(['tso_code']);

            if ($lastApprovedSnapshot) {
                $matchedTsoCodes[] = $lastApprovedSnapshot->tso_code;
            }
            // kalau belum pernah ada snapshot sama sekali (TSO baru, belum
            // pernah approved sekalipun), memang belum ada data buat ditampilkan
        }
    }
}

        // Kalau ada beberapa TSO yang cocok (beda customer, bulan target sama),
        // tombol "Target SO" cuma nunjuk ke yang pertama -- widget ini agregat,
        // bukan per-customer, jadi nggak ada satu halaman detail yang mewakili
        // semuanya. Kalau nggak ada yang cocok sama sekali, arahkan ke index
        // (biar user bisa cari/browse sendiri).
        $targetSoUrl = $firstMatchedId
            ? route('targetSo.show', ['id' => Crypt::encryptString($firstMatchedId)])
            : route('targetSo.index');

        $targetLines = empty($matchedTsoCodes) ? collect() : DB::table('target_order_det')
            ->whereIn('tso_code', $matchedTsoCodes)
            ->select('article_code', DB::raw('SUM(qty_target) as qty_target'))
            ->groupBy('article_code')
            ->get();

        $targetQty = 0;
        $targetConversion = 0;
        foreach ($targetLines as $line) {
            $qty = (float) $line->qty_target;
            $targetQty += $qty;

            $avgSelling  = $this->avgSellingPriceHome($line->article_code, $periode, $tahun);
            $avgPurchase = $this->purchasePriceHome($line->article_code, $periode, $tahun);
            $targetConversion += $convVal > 0 ? (($avgSelling - $avgPurchase) * $qty) / $convVal : 0;
        }

        // ---- ACHIEVED (delivery aktual dalam rentang periode, s.d. hari ini kalau periode berjalan) ----
        $achievedQty = 0;
        $achievedConversion = 0;
        if ($achievedEnd >= $monthStart) {
            $dnRows = DB::select("
                SELECT
                    dd.article_code,
                    dd.qty,
                    (COALESCE(sod.price,0)+COALESCE(sod.price_service,0)) AS price_unit
                FROM delivery_det dd
                JOIN delivery_hdr dh ON dh.delivery_number = dd.delivery_number
                LEFT JOIN sales_order_det sod ON sod.so_code = dd.so_number AND sod.article_code = dd.article_code
                WHERE to_date(dh.delivery_date,'DD-MM-YYYY') BETWEEN ?::date AND ?::date
                  AND dh.status NOT IN ('5','7')
            ", [$monthStart, $achievedEnd]);

            $grouped = [];
            foreach ($dnRows as $r) {
                $grouped[$r->article_code][] = $r;
            }

            foreach ($grouped as $articleCode => $lines) {
                $qty = 0;
                $value = 0;
                foreach ($lines as $l) {
                    $qty   += (float) $l->qty;
                    $value += (float) $l->qty * (float) $l->price_unit;
                }
                $avgSelling  = $qty > 0 ? $value / $qty : 0;
                $avgPurchase = $this->purchasePriceHome($articleCode, $periode, $tahun);

                $achievedQty += $qty;
                $achievedConversion += $convVal > 0 ? (($avgSelling - $avgPurchase) * $qty) / $convVal : 0;
            }
        }

        $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        return [
            'hasTarget'          => $targetQty > 0,
            'periode'            => $periode,
            'tahun'              => $tahun,
            'monthLabel'         => $months[$periode] . ' ' . $tahun,
            'targetQty'          => round($targetQty, 2),
            'achievedQty'        => round($achievedQty, 2),
            'qtyPct'             => $targetQty > 0 ? round($achievedQty / $targetQty * 100, 1) : 0,
            'targetConversion'   => round($targetConversion, 2),
            'achievedConversion' => round($achievedConversion, 2),
            'conversionPct'      => $targetConversion > 0 ? round($achievedConversion / $targetConversion * 100, 1) : 0,
            'targetSoUrl'        => $targetSoUrl,
        ];
    }

    /** AJAX -- dipanggil saat filter periode/tahun widget Sales Achievement diganti. */
    public function salesAchievementFilter(Request $request)
    {
        $periode = (int) $request->periode;
        $tahun   = (int) $request->tahun;
        if ($periode < 1 || $periode > 12) {
            $periode = (int) Carbon::now()->format('n');
        }
        if (!$tahun) {
            $tahun = (int) Carbon::now()->format('Y');
        }

        return response()->json(['status' => 1, 'data' => $this->buildSalesAchievement($periode, $tahun)]);
    }

    public function index()
    {

        $username =  Auth::user() ? Auth::user()->username : '';
        $adaModule = db::table('approval_level')
        ->where('username',$username)
        ->where('approval_order','>',1)
        ->distinct()
        ->pluck('module_code')->toarray();

        $lists['jumlahSo'] = 0;
        $lists['jumlahPo'] = 0;
        $lists['jumlahBom'] = 0;
        $lists['jumlahPr'] = 0;
        $lists['jumlahTso'] = 0;
        $lists['jumlahDn'] = 0;
        $lists['jumlahAp'] = 0;
        $lists['jumlahAr'] = 0;
        $lists['jumlahRec'] = 0;
        $lists['jumlahBm'] = 0;
        $lists['jumlahBk'] = 0;
        $lists['jumlahKm'] = 0;
        $lists['jumlahKk'] = 0;
        $lists['jumlahGj'] = 0;
        $lists['jumlahDebitNote'] = 0;

        $username =  Auth::user()->username;
        $data['tanggal'] = Carbon::now()->format('l').','.Carbon::now()->format('d M Y');

        // if (in_array("PO", $adaModule)){
            $data['listPoHome'] = DB::select("SELECT * from (
                select 
                    id
                    ,supplier_id
                    ,po_number
                    ,po_date
                    ,created_by
                    ,validate_by
                    ,status
                    ,'$username' as username
                    ,coalesce((select max(approval_order) from approval_history where module_code ='PO' and module_number =a.po_number),0) as current_level
                    ,(select approval_number from approval_master where module_code = 'PO') as max_level
                    ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'PO' and approval_order not in(
                    select approval_order from approval_history where username = '$username' and module_code = 'PO' and module_number = a.po_number)),0) as berhak_approve
                    ,(SELECT sum(qty*price) from purchase_order_det where po_number = a.po_number) as po_amount
                    ,(select nama from third_party where kode = supplier_id) as supplier_name
                from purchase_order_hdr a
                -- where status not in ('3','4','5','6','7','8')
                where status in ('2')
            ) as Oki
            where current_level+1 = berhak_approve");
        // }

        $data['listBomHome'] = DB::select("SELECT * from (
            select 
                id
                ,bom_code
                ,created_by
                ,(select article_desc from article where article_code = a.article_code) as article_fg
                ,(select article_desc from article where article_code = a.article_code_rm) as article_rm
                ,status
                ,'$username' as username
                ,coalesce((select max(approval_order) from approval_history where module_code ='BOM' and module_number =a.bom_code),0) as current_level
                ,(select approval_number from approval_master where module_code = 'BOM') as max_level
                ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'BOM' and approval_order not in(
                select approval_order from approval_history where username = '$username' and module_code = 'BOM' and module_number = a.bom_code)),0) as berhak_approve
                ,(select nama from third_party where kode = customer) as customer_name
            from bom_hdr a
            -- where status not in ('3','4','5','6','7','8')
            where status in ('1','2')
            ) as Oki
        where current_level+1 = berhak_approve");

        $data['listPrHome'] = DB::select("SELECT * from (
            select 
                id
                ,pr_number
                ,date
                ,dept
                ,order_type
                ,note
                ,created_by
                ,status
                ,'$username' as username
                ,coalesce((select max(approval_order) from approval_history where module_code ='PR' and module_number =a.pr_number),0) as current_level
                ,(select approval_number from approval_master where module_code = 'PR') as max_level
                ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'PR' and username in (select username from user_dept where dept = a.dept and username = '$username')
                and approval_order not in( select approval_order from approval_history where username = '$username' and module_code = 'PR' and module_number = a.pr_number)),0) as berhak_approve
                ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'PR'
                and username in (select username from user_dept where dept = a.dept and username = '$username')
                and approval_order not in(
                select approval_order from approval_history where username = '$username' and module_code = 'PR' and module_number = a.pr_number)),0) as berhak_approve1
            from purchase_request_hdr a
            -- where status not in ('3','4','5','6','7','8','9')
            where status in ('1','2')
            ) as Oki
        where current_level+1 = berhak_approve");

        $data['listSoHome'] = DB::select("SELECT * from (
            select id
            ,so_code
            ,so_date
            ,po_number
            ,'$username' as username
            ,note
            ,status
            ,coalesce((select max(approval_order) from approval_history where module_code ='SO' and module_number =sales_order_hdr.so_code),0) as current_level
            ,(select approval_number from approval_master where module_code = 'SO') as max_level
            ,coalesce((select max(approval_order) from approval_history where module_code = 'SO' and module_number = so_code),0) as sudah_approve,
            coalesce((select approval_order from approval_level where username = '$username' and module_code = 'SO' limit 1),0) as berhak_approve,
            (select nama from third_party where kode = customer_id) as customer_name
            from sales_order_hdr 
            -- where status <> '3'
            where status in ('1','2')
            ) as Oki
        where berhak_approve-1 = sudah_approve");

        // $data['listSoHome'] = DB::select("SELECT * from (
        //     select id
        //     ,so_code
        //     ,so_date
        //     ,po_number
        //     ,'$username' as username
        //     ,note
        //     ,status
        //     ,coalesce((select max(approval_order) from approval_history where module_code ='SO' and module_number =sales_order_hdr.so_code),0) as current_level
        //     ,(select approval_number from approval_master where module_code = 'SO') as max_level
        //     ,coalesce((select max(approval_order) from approval_history where module_code = 'SO' and module_number = so_code),0) as sudah_approve,
        //     coalesce((select approval_order from approval_level where username = '$username' and module_code = 'SO' limit 1),0) as berhak_approve,
        //     (select nama from third_party where kode = customer_id) as customer_name
        //     from sales_order_hdr 
        //     where status <> '3'
        //     ) as Oki
        // where berhak_approve-1 = sudah_approve");
            
        $data['listTsoHome'] = DB::select("SELECT * from (
            select 
                id
                ,tso_code
                ,tso_date
                ,tso_name
                ,note
                ,created_by
                ,status
                ,'$username' as username
                ,coalesce((select max(approval_order) from approval_history where module_code ='TSO' and module_number =a.tso_code),0) as current_level
                ,(select approval_number from approval_master where module_code = 'TSO') as max_level
                ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'TSO' and approval_order not in(
                select approval_order from approval_history where username = '$username' and module_code = 'TSO' and module_number = a.tso_code)),0) as berhak_approve
            from target_order_hdr a
            -- where status not in ('3','4','5','6','7','8')
            where status in ('1','2')
            ) as Oki
        where current_level+1 = berhak_approve");

        // $data['listTsoHome'] = DB::select("SELECT * from (
        //     select 
        //         id
        //         ,tso_code
        //         ,tso_date
        //         ,tso_name
        //         ,note
        //         ,created_by
        //         ,status
        //         ,'$username' as username
        //         ,coalesce((select max(approval_order) from approval_history where module_code ='TSO' and module_number =a.tso_code),0) as current_level
        //         ,(select approval_number from approval_master where module_code = 'TSO') as max_level
        //         ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'TSO' and approval_order not in(
        //         select approval_order from approval_history where username = '$username' and module_code = 'TSO' and module_number = a.tso_code)),0) as berhak_approve
        //     from target_order_hdr a
        //     -- where status not in ('3','4','5','6','7','8')
        //     where status in ('1','2')
        //     ) as Oki
        // where current_level+1 = berhak_approve");

        //bom yang status nya approved 2 minggu ke belakang
        $data['listBom']=DB::select("SELECT bom_code, customer
        ,(select nama from third_party where kode = customer) as customer_name
        ,(select article_alternative_code from article where article_code = bom_hdr.article_code) as article_code
        ,(select article_desc from article where article_code = bom_hdr.article_code) as article_name
        ,note,created_at,updated_at from bom_hdr where status ='3' and  updated_at >= now() - interval '2 week'");

        $data['listDnHome'] = DB::select("SELECT * from (
            select 
                id
                ,delivery_number
                ,delivery_date
                ,po_number
                ,note
                ,created_by
                ,status
                ,'$username' as username
                ,coalesce((select max(approval_order) from approval_history where module_code ='DN' and module_number =a.delivery_number),0) as current_level
                ,(select approval_number from approval_master where module_code = 'DN') as max_level
                ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'DN' and approval_order not in(
                select approval_order from approval_history where username = '$username' and module_code = 'DN' and module_number = a.delivery_number)),0) as berhak_approve
            from delivery_hdr a
            where status in ('10')
            ) as Oki
        where current_level+1 = berhak_approve");

        $data['listBkHome'] = DB::select("SELECT * from (
            select 
                id
                ,voucher_number
                ,voucher_date
                ,description
                ,note
                ,created_by
                ,status
                ,'$username' as username
                ,coalesce((select max(approval_order) from approval_history where module_code ='BK' and module_number =a.voucher_number),0) as current_level
                ,(select approval_number from approval_master where module_code = 'BK') as max_level
                ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'BK' and approval_order not in(
                select approval_order from approval_history where username = '$username' and module_code = 'BK' and module_number = a.voucher_number)),0) as berhak_approve
            from kas_hdr a
            where status in ('2')
            and voucher_type = 'BK'
            ) as Oki
        where current_level+1 = berhak_approve");

        $data['listBmHome'] = DB::select("SELECT * from (
            select 
                id
                ,voucher_number
                ,voucher_date
                ,description
                ,note
                ,created_by
                ,status
                ,'$username' as username
                ,coalesce((select max(approval_order) from approval_history where module_code ='BM' and module_number =a.voucher_number),0) as current_level
                ,(select approval_number from approval_master where module_code = 'BM') as max_level
                ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'BM' and approval_order not in(
                select approval_order from approval_history where username = '$username' and module_code = 'BM' and module_number = a.voucher_number)),0) as berhak_approve
            from kas_hdr a
            where status in ('2')
            and voucher_type = 'BM'
            ) as Oki
        where current_level+1 = berhak_approve");

        $data['listKmHome'] = DB::select("SELECT * from (
            select 
                id
                ,voucher_number
                ,voucher_date
                ,description
                ,note
                ,created_by
                ,status
                ,'$username' as username
                ,coalesce((select max(approval_order) from approval_history where module_code ='KM' and module_number =a.voucher_number),0) as current_level
                ,(select approval_number from approval_master where module_code = 'KM') as max_level
                ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'KM' and approval_order not in(
                select approval_order from approval_history where username = '$username' and module_code = 'KM' and module_number = a.voucher_number)),0) as berhak_approve
            from kas_hdr a
            where status in ('2')
            and voucher_type = 'KM'
            ) as Oki
        where current_level+1 = berhak_approve");

        $data['listKkHome'] = DB::select("SELECT * from (
            select 
                id
                ,voucher_number
                ,voucher_date
                ,description
                ,note
                ,created_by
                ,status
                ,'$username' as username
                ,amount
                ,coalesce((select max(approval_order) from approval_history where module_code ='KK' and module_number =a.voucher_number),0) as current_level
                ,(select approval_number from approval_master where module_code = 'KK') as max_level
                ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'KK' and approval_order not in(
                select approval_order from approval_history where username = '$username' and module_code = 'KK' and module_number = a.voucher_number)),0) as berhak_approve
            from kas_hdr a
            where status in ('2')
            and voucher_type = 'KK'
            ) as Oki
        where current_level+1 = berhak_approve");

        $data['listGjHome'] = DB::select("SELECT * from (
            select 
                id
                ,voucher_number
                ,voucher_date
                ,description
                ,note
                ,created_by
                ,status
                ,'$username' as username
                ,coalesce((select max(approval_order) from approval_history where module_code ='GJ' and module_number =a.voucher_number),0) as current_level
                ,(select approval_number from approval_master where module_code = 'GJ') as max_level
                ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'GJ' and approval_order not in(
                select approval_order from approval_history where username = '$username' and module_code = 'GJ' and module_number = a.voucher_number)),0) as berhak_approve
            from kas_hdr a
            where status in ('2')
            and voucher_type = 'GJ'
            ) as Oki
        where current_level+1 = berhak_approve");

        $data['listApHome'] = DB::select("SELECT * from (
            select 
                id
                ,ap_number
                ,inv_date
                ,po_number
                ,note
                ,ap_date
                ,created_by
                ,status
                ,'$username' as username
                ,coalesce((select max(approval_order) from approval_history where module_code ='AP' and module_number =a.ap_number),0) as current_level
                ,(select approval_number from approval_master where module_code = 'AP') as max_level
                ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'AP' and approval_order not in(
                select approval_order from approval_history where username = '$username' and module_code = 'AP' and module_number = a.ap_number)),0) as berhak_approve
            from ap_invoice a
            where status in ('2')
            ) as Oki
        where current_level+1 = berhak_approve");

        $data['listArHome'] = DB::select("SELECT * from (
            select 
                id
                ,invoice_number
                ,invoice_date
                ,po_number
                ,so_number
                ,note
                ,created_by
                ,status
                ,'$username' as username
                ,coalesce((select max(approval_order) from approval_history where module_code ='INV' and module_number =a.invoice_number),0) as current_level
                ,(select approval_number from approval_master where module_code = 'INV') as max_level
                ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'INV' and approval_order not in(
                select approval_order from approval_history where username = '$username' and module_code = 'INV' and module_number = a.invoice_number)),0) as berhak_approve
            from invoice_hdr a
            where status in ('2')
            ) as Oki
        where current_level+1 = berhak_approve");

        $data['listRecHome'] =DB::select("SELECT * from (
            select 
                id
                ,rec_number
                ,rec_date
                ,do_number
                ,po_number
                ,note
                ,created_by
                ,status
                ,'$username' as username
                ,coalesce((select max(approval_order) from approval_history where module_code ='REC' and module_number =a.rec_number),0) as current_level
                ,(select approval_number from approval_master where module_code = 'REC') as max_level
                ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'REC' and approval_order not in(
                select approval_order from approval_history where username = '$username' and module_code = 'REC' and module_number = a.rec_number)),0) as berhak_approve
            from receiving_hdr a
            where status in ('10')
            ) as Oki
        where current_level+1 = berhak_approve");     

        $data['listDebNoteHome'] = DB::select("SELECT * from (
            select 
                id
                ,dn_number
                ,dn_date
                ,po_number
                ,note
                ,created_by
                ,status
                ,'$username' as username
                ,coalesce((select max(approval_order) from approval_history where module_code ='INV-DN' and module_number =a.dn_number),0) as current_level
                ,(select approval_number from approval_master where module_code = 'INV-DN') as max_level
                ,coalesce((select min(approval_order) from approval_level where username = '$username' and module_code = 'INV-DN' and approval_order not in(
                select approval_order from approval_history where username = '$username' and module_code = 'INV-DN' and module_number = a.dn_number)),0) as berhak_approve
            from debit_note_hdr a
            where status in ('2')
            ) as Oki
        where current_level+1 = berhak_approve");

        // Cek dept user (pakai tabel user_dept yang sudah dipakai di query PR)
$userDepts = DB::table('user_dept')
    ->where('username', $username)
    ->pluck('dept')
    ->toArray();

// ===== Cek akses Critical Stock Alert =====
$userDepts = DB::table('user_dept')
    ->where('username', $username)
    ->pluck('dept') // sesuaikan nama kolom kalau bukan 'dept'
    ->toArray();

$allowedDeptCriticalStock = ['005', '008']; // 005 = Logistik, 008 = Purchasing
$hasAllowedDept = count(array_intersect($userDepts, $allowedDeptCriticalStock)) > 0;
$hasPrivilegedRole = Auth::user()->hasAnyRole(['Superuser', 'accounting']);

$data['showCriticalStock'] = $hasAllowedDept || $hasPrivilegedRole;

if ($data['showCriticalStock']) {
    $allowedLocations = ['009', '005', '006', '007']; // RM, Chemical, Consumable, FG
    $excludedThirdPartyAtFG = ['STI00001CUST', 'STI00002CUST'];

    $data['listCriticalStock'] = DB::table('warehouse_stock as ws')
        ->join('article as a', 'a.article_code', '=', 'ws.article_code')
        ->leftJoin('third_party as tp', 'tp.kode', '=', 'a.third_party')
        ->leftJoin('stock_location_master as loc', 'loc.location_code', '=', 'ws.location_number')
        ->whereIn('ws.location_number', $allowedLocations)
        ->where(function($q) use ($excludedThirdPartyAtFG) {
            $q->where('ws.location_number', '!=', '007')
              ->orWhereNotIn('a.third_party', $excludedThirdPartyAtFG)
              ->orWhereNull('a.third_party');
        })
        ->select(
            'a.article_code',
            'a.article_alternative_code as code',
            'a.article_desc as name',
            'a.uom',
            'a.min_package',
            DB::raw('coalesce(a.safety_stock,0) as safety_stock'),
            'loc.location_name',
            DB::raw('coalesce(ws.article_qty,0) as stock_qty'),
            'tp.nama as supplier_name'
        )
        ->where(function($q){
            $q->whereRaw('coalesce(ws.article_qty,0) < coalesce(a.safety_stock,0)')
              ->orWhere(function($q2){
                  $q2->whereNull('a.safety_stock')->where('ws.article_qty', '<=', 0);
              });
        })
        ->orderBy('ws.location_number', 'asc')
        ->orderBy('a.article_alternative_code', 'asc')
        ->get();
} else {
    $data['listCriticalStock'] = collect();
}
$data['criticalStockCount'] = $data['listCriticalStock']->count();

        // ===== Transfer Stock yang perlu diposting (masuk ke gudang dept saya) =====
$userDepts = DB::table('user_dept')
    ->where('username', $username)
    ->pluck('dept')
    ->toArray();

$data['outstandingTransferIn'] = DB::table('transfer_stock_hdr')
    ->leftJoin('stock_location_master as locFrom', 'locFrom.location_code', '=', 'transfer_stock_hdr.location_from')
    ->leftJoin('stock_location_master as locTo',   'locTo.location_code',   '=', 'transfer_stock_hdr.location_to')
    ->whereIn('transfer_stock_hdr.status', ['1', '2'])
    ->whereIn('transfer_stock_hdr.approve_dept', $userDepts)
    ->select(
        'transfer_stock_hdr.*',
        'locFrom.location_name as location_name',
        'locTo.location_name as location_name_to'
    )
    ->orderBy('transfer_stock_hdr.created_at', 'asc')
    ->get()
    ->map(function ($row) {
        $created = Carbon::parse($row->created_at);
        $seconds = max(0, $created->diffInSeconds(now(), false));
        $row->age_seconds = $seconds;
        $aging = self::formatAgingHome($seconds);
        $row->aging_label = $aging['label'];
        $row->aging_level = $aging['level'];
        return $row;
    });

$data['outstandingTransferInCount'] = $data['outstandingTransferIn']->count();
        $data['bomCount'] = count($data['listBom']);
        $data['greeting'] = self::greeting();
        $data['salesAchievement'] = $this->buildSalesAchievement();

        return view('home',$data);
    }

}
