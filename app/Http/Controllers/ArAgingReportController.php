<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ArAgingReportExport;
use DB;

/*
    ================================================================
    AR AGING REPORT
    ================================================================
    Filter utama : TANGGAL CUT-OFF (single date, wajib) -> dasar
                   perhitungan umur piutang (lihat pembahasan chat
                   sebelumnya: aging = snapshot per satu tanggal,
                   bukan laporan rentang transaksi).
    Filter tambahan (opsional, tidak menggantikan cut-off):
        - Rentang tanggal invoice  -> mempersempit invoice mana yang
          ikut dihitung (mis. "aging hari ini tapi cuma invoice bulan
          lalu yang masih outstanding")
        - Customer (multi-select)

    ASUMSI mengikuti pola yang SUDAH ADA di InvoiceController::list()
    dan wajib Anda cek/sesuaikan kalau struktur kas_det/kas_hdr di
    project Anda berbeda:
      1. Invoice yang dihitung: status NOT IN ('1' DRAFT, '5' CANCELED)
         -> DRAFT sengaja di-exclude sesuai catatan di format Excel
            Anda ("Invoice berstatus DRAFT tidak dihitung").
      2. Balance = grand_total - total pembayaran yang sudah di-approve.
         Pembayaran customer dicocokkan lewat kas_det.reference =
         invoice_number DAN kas_hdr.status = '3' (APPROVED) -- TANPA
         filter voucher_type. Awalnya di-filter ke voucher_type = 'BM'
         saja, tapi ternyata pelunasan invoice bisa tercatat lewat
         voucher_type lain juga (mis. BK), jadi filter itu bikin invoice
         yang sudah lunas kebaca tetap outstanding. Pola tanpa filter
         voucher_type ini mengikuti kolom 'balance' yang sudah established
         & terbukti benar di InvoiceController::list() / show().
         Di sini saya pakai SUM (bukan scalar subquery tanpa agregasi)
         supaya kalau satu invoice dibayar bertahap (partial payment /
         lebih dari satu voucher), semua kredit tetap terhitung.
         Tambahan: pembayaran hanya dihitung kalau voucher_date-nya
         <= tanggal cut-off (lihat ArAgingReportController::buildPiutangSubquery),
         supaya cut-off tetap jadi snapshot yang benar terhadap waktu.
      3. Jatuh tempo = invoice_hdr.jatuh_tempo (kalau diisi manual),
         fallback ke sending_date + top_batas_1 (termin, dalam hari)
         dari third_party -- sama seperti kolom jatuh_tempo_2 di
         InvoiceController::list().
      4. Umur piutang = tanggal CUT-OFF dikurangi tanggal jatuh tempo.
         diff_hari <= 0  -> Belum Jatuh Tempo
         diff_hari 1-30  -> bucket 1-30 hari
         dst.
    ================================================================
*/

class ArAgingReportController extends Controller
{
    private $title = "AR Aging Report";

    // Invoice sebelum tanggal ini tidak pernah dibaca oleh report (batas bawah data).
    private $floorDate = '01-01-2024';

    public function index(Request $request)
    {
        $data['title'] = $this->title;

        $data['customers'] = DB::table('third_party')
            ->where('third_party_type', '=', 'cust')
            ->orderBy('nama')
            ->get();

        return view('arAgingReport.index', $data);
    }

    private function bucketLabels()
    {
        return [
            'belum_jatuh_tempo' => 'Belum Jatuh Tempo',
            'd1_30'   => '1 - 30 Hari',
            'd31_60'  => '31 - 60 Hari',
            'd61_90'  => '61 - 90 Hari',
            'd90plus' => '> 90 Hari',
        ];
    }

    /**
     * Bangun bagian filter opsional (rentang tanggal invoice + customer) yang
     * dipakai bersama oleh data() dan detail(), supaya keduanya selalu
     * menghitung dengan logika balance & bucket yang identik.
     */
    private function buildFilters(Request $request)
{
    $customerCodes = $request->customer; // array kode customer (multi-select), boleh kosong

    $bindings   = [];
    $whereExtra = "";

    if ($customerCodes && is_array($customerCodes) && count($customerCodes) > 0) {
        $escaped = array_map(function ($c) {
            return "'" . str_replace("'", "''", $c) . "'";
        }, $customerCodes);
        $whereExtra .= " AND invoice_hdr.customer_id IN (" . implode(',', $escaped) . ") ";
    }

    return [$whereExtra, $bindings];
}

    /**
     * Subquery per-invoice: balance & umur piutang terhadap :cutoff.
     * - Invoice sebelum $floorDate tidak pernah ikut (batas bawah data).
     * - Pembayaran dicocokkan lewat kas_det.reference = invoice_number DAN
     *   kas_hdr.status = '3' (APPROVED) -- TANPA filter voucher_type, sama
     *   seperti pola balance yang sudah established & terbukti benar di
     *   InvoiceController::list() (lihat kolom 'balance' di situ). Pelunasan
     *   invoice ternyata bisa tercatat lewat berbagai jenis voucher kas/bank
     *   (mis. BM maupun BK), jadi tidak boleh dibatasi ke satu voucher_type
     *   saja -- itu sebabnya invoice yang sudah lunas via voucher BK dulu
     *   sempat kebaca tetap outstanding di aging.
     * - Pembayaran hanya dihitung sebagai pengurang balance kalau
     *   voucher_date-nya <= :cutoff. Ini penting supaya laporan tetap jadi
     *   snapshot yang benar: kalau cut-off di-set mundur (mis. 22 Sept)
     *   tapi pelunasannya baru terjadi setelah itu (mis. dibayar 25 Sept),
     *   invoice tsb TIDAK boleh kebaca lunas pada cut-off 22 Sept.
     */
    private function buildPiutangSubquery($whereExtra)
{
    return "
        SELECT
            invoice_hdr.id as invoice_id,
            invoice_hdr.invoice_number,
            invoice_hdr.customer_id,
            invoice_hdr.invoice_date,
            invoice_hdr.sending_date,
            COALESCE(
                (SELECT top_batas_1 FROM third_party tp WHERE tp.kode = invoice_hdr.customer_id),
                0
            ) as term,
            COALESCE(
                invoice_hdr.jatuh_tempo::date,
                (
                    to_date(invoice_hdr.sending_date,'DD-MM-YYYY')
                    + INTERVAL '1 day' * COALESCE(
                        (SELECT top_batas_1 FROM third_party tp WHERE tp.kode = invoice_hdr.customer_id),
                        0
                    )
                )::date
            ) as jatuh_tempo_actual,
            (invoice_hdr.grand_total - COALESCE(bayar.total_dibayar,0)) as balance,
            (
                to_date(:cutoff,'DD-MM-YYYY') -
                COALESCE(
                    invoice_hdr.jatuh_tempo::date,
                    (
                        to_date(invoice_hdr.sending_date,'DD-MM-YYYY')
                        + INTERVAL '1 day' * COALESCE(
                            (SELECT top_batas_1 FROM third_party tp WHERE tp.kode = invoice_hdr.customer_id),
                            0
                        )
                    )::date
                )
            ) as diff_hari
        FROM invoice_hdr
        LEFT JOIN LATERAL (
            SELECT SUM(kas_det.credit) as total_dibayar
            FROM kas_det
            LEFT JOIN kas_hdr ON kas_det.voucher_number = kas_hdr.voucher_number
            WHERE kas_det.reference = invoice_hdr.invoice_number
              AND kas_hdr.status = '3'
              AND to_date(kas_hdr.voucher_date,'DD-MM-YYYY') <= to_date(:cutoff,'DD-MM-YYYY')
        ) bayar ON true
        WHERE invoice_hdr.status NOT IN ('1','5')
          AND to_date(invoice_hdr.invoice_date,'DD-MM-YYYY') >= to_date(:floorDate,'DD-MM-YYYY')
          AND to_date(invoice_hdr.invoice_date,'DD-MM-YYYY') <= to_date(:cutoff,'DD-MM-YYYY')
          $whereExtra
    ";
}

    private function bucketWhere($bucket)
    {
        switch ($bucket) {
            case 'belum_jatuh_tempo': return " AND piutang.diff_hari <= 0 ";
            case 'd1_30':             return " AND piutang.diff_hari BETWEEN 1 AND 30 ";
            case 'd31_60':            return " AND piutang.diff_hari BETWEEN 31 AND 60 ";
            case 'd61_90':            return " AND piutang.diff_hari BETWEEN 61 AND 90 ";
            case 'd90plus':           return " AND piutang.diff_hari > 90 ";
            case 'total_overdue':     return " AND piutang.diff_hari > 0 ";
            case 'total_piutang':     return "";
            default:                  return "";
        }
    }

    /**
     * Endpoint AJAX utama. Dipanggil oleh tombol "Generate Report" di view.
     * Mengembalikan JSON: rows per customer + grand total + info balance DRAFT.
     */
    public function data(Request $request)
    {
        // ── Tanggal cut-off (wajib, single date, format DD-MM-YYYY) ──
        $cutoffDate = $request->cutoffDate ? trim($request->cutoffDate) : date('d-m-Y');

        list($whereExtra, $bindings) = $this->buildFilters($request);
        $bindings['cutoff']    = $cutoffDate;
        $bindings['floorDate'] = $this->floorDate;

        $subquery = $this->buildPiutangSubquery($whereExtra);

        // ── Query utama ──
        $sql = "
            SELECT
                piutang.customer_id as customer_code,
                third_party.nama    as customer_name,
                SUM(piutang.balance) as total_piutang,
                SUM(CASE WHEN piutang.diff_hari <= 0               THEN piutang.balance ELSE 0 END) as belum_jatuh_tempo,
                SUM(CASE WHEN piutang.diff_hari BETWEEN 1  AND 30  THEN piutang.balance ELSE 0 END) as d1_30,
                SUM(CASE WHEN piutang.diff_hari BETWEEN 31 AND 60  THEN piutang.balance ELSE 0 END) as d31_60,
                SUM(CASE WHEN piutang.diff_hari BETWEEN 61 AND 90  THEN piutang.balance ELSE 0 END) as d61_90,
                SUM(CASE WHEN piutang.diff_hari > 90               THEN piutang.balance ELSE 0 END) as d90plus
            FROM ($subquery) piutang
            LEFT JOIN third_party ON third_party.kode = piutang.customer_id
            WHERE piutang.balance > 0.01
            GROUP BY piutang.customer_id, third_party.nama
            ORDER BY third_party.nama ASC
        ";

        $rows = DB::select($sql, $bindings);

        $grand = [
            'belum_jatuh_tempo' => 0, 'd1_30' => 0, 'd31_60' => 0,
            'd61_90' => 0, 'd90plus' => 0, 'total_overdue' => 0, 'total_piutang' => 0,
        ];

        $result = [];
        foreach ($rows as $r) {
            $overdue    = $r->d1_30 + $r->d31_60 + $r->d61_90 + $r->d90plus;
            $pctOverdue = $r->total_piutang > 0 ? ($overdue / $r->total_piutang) * 100 : 0;

            $result[] = [
                'customer_code'     => $r->customer_code,
                'customer_name'     => $r->customer_name,
                'belum_jatuh_tempo' => (float) $r->belum_jatuh_tempo,
                'd1_30'             => (float) $r->d1_30,
                'd31_60'            => (float) $r->d31_60,
                'd61_90'            => (float) $r->d61_90,
                'd90plus'           => (float) $r->d90plus,
                'total_overdue'     => (float) $overdue,
                'total_piutang'     => (float) $r->total_piutang,
                'pct_overdue'       => round($pctOverdue, 1),
            ];

            $grand['belum_jatuh_tempo'] += $r->belum_jatuh_tempo;
            $grand['d1_30']             += $r->d1_30;
            $grand['d31_60']            += $r->d31_60;
            $grand['d61_90']            += $r->d61_90;
            $grand['d90plus']           += $r->d90plus;
            $grand['total_overdue']     += $overdue;
            $grand['total_piutang']     += $r->total_piutang;
        }

        $grand['pct_overdue'] = $grand['total_piutang'] > 0
            ? round(($grand['total_overdue'] / $grand['total_piutang']) * 100, 1)
            : 0;

        // Info rekonsiliasi: total balance invoice DRAFT (tidak masuk aging)
        $draftBalance = DB::selectOne("
            SELECT COALESCE(SUM(grand_total),0) as total
            FROM invoice_hdr
            WHERE status = '1'
        ")->total;

        return response()->json([
            'status'       => 1,
            'cutoff'       => $cutoffDate,
            'rows'         => $result,
            'grand'        => $grand,
            'draftBalance' => (float) $draftBalance,
            'bucketLabels' => $this->bucketLabels(),
        ]);
    }

    /**
     * Endpoint AJAX untuk modal detail: dipanggil saat angka di tabel/grand
     * total diklik. Mengembalikan daftar invoice (nomor, jatuh tempo, nilai,
     * dan id ter-enkripsi untuk link ke invoice.show) yang membentuk angka
     * tsb, dengan logika balance & bucket yang identik dengan data().
     */
    public function detail(Request $request)
{
    $cutoffDate   = $request->cutoffDate ? trim($request->cutoffDate) : date('d-m-Y');
    $customerCode = $request->customerCode ? trim($request->customerCode) : null;
    $bucket       = $request->bucket ? trim($request->bucket) : 'total_piutang';

    list($whereExtra, $bindings) = $this->buildFilters($request);
    $bindings['cutoff']    = $cutoffDate;
    $bindings['floorDate'] = $this->floorDate;

    if ($customerCode) {
        $whereExtra .= " AND invoice_hdr.customer_id = :detailCustomer ";
        $bindings['detailCustomer'] = $customerCode;
    }

    $subquery    = $this->buildPiutangSubquery($whereExtra);
    $bucketWhere = $this->bucketWhere($bucket);

    $sql = "
        SELECT
            piutang.invoice_id,
            piutang.invoice_number,
            third_party.nama as customer_name,
            piutang.invoice_date,
            piutang.sending_date,
            piutang.term,
            piutang.jatuh_tempo_actual,
            piutang.balance
        FROM ($subquery) piutang
        LEFT JOIN third_party ON third_party.kode = piutang.customer_id
        WHERE piutang.balance > 0.01
        $bucketWhere
        ORDER BY piutang.jatuh_tempo_actual ASC, piutang.invoice_number ASC
    ";

    $rows = DB::select($sql, $bindings);

    $result = [];
    foreach ($rows as $r) {
        $result[] = [
            'invoice_number' => $r->invoice_number,
            'customer_name'  => $r->customer_name,
            'invoice_date'   => $r->invoice_date ?: '-',
            'sending_date'   => $r->sending_date ?: '-',
            'term'           => $r->term !== null ? ((int) $r->term . ' hari') : '-',
            'jatuh_tempo'    => $r->jatuh_tempo_actual ? date('d-m-Y', strtotime($r->jatuh_tempo_actual)) : '-',
            'balance'        => (float) $r->balance,
            'invoice_link'   => route('invoice.show', ['id' => Crypt::encryptString($r->invoice_id)]),
        ];
    }

    return response()->json([
        'status'      => 1,
        'bucketLabel' => $this->bucketLabels()[$bucket] ?? ($bucket === 'total_overdue' ? 'Total Overdue' : 'Total Piutang'),
        'rows'        => $result,
        'total'       => array_sum(array_column($result, 'balance')),
    ]);
}

    public function export(Request $request)
{
    $cutoffDate = $request->cutoffDate ? trim($request->cutoffDate) : date('d-m-Y');

    list($whereExtra, $bindings) = $this->buildFilters($request);
    $bindings['cutoff']    = $cutoffDate;
    $bindings['floorDate'] = $this->floorDate;

    $subquery = $this->buildPiutangSubquery($whereExtra);

    $sql = "
        SELECT
            piutang.customer_id as customer_code,
            third_party.nama    as customer_name,
            SUM(piutang.balance) as total_piutang,
            SUM(CASE WHEN piutang.diff_hari <= 0               THEN piutang.balance ELSE 0 END) as belum_jatuh_tempo,
            SUM(CASE WHEN piutang.diff_hari BETWEEN 1  AND 30  THEN piutang.balance ELSE 0 END) as d1_30,
            SUM(CASE WHEN piutang.diff_hari BETWEEN 31 AND 60  THEN piutang.balance ELSE 0 END) as d31_60,
            SUM(CASE WHEN piutang.diff_hari BETWEEN 61 AND 90  THEN piutang.balance ELSE 0 END) as d61_90,
            SUM(CASE WHEN piutang.diff_hari > 90               THEN piutang.balance ELSE 0 END) as d90plus
        FROM ($subquery) piutang
        LEFT JOIN third_party ON third_party.kode = piutang.customer_id
        WHERE piutang.balance > 0.01
        GROUP BY piutang.customer_id, third_party.nama
        ORDER BY third_party.nama ASC
    ";

    $rows = DB::select($sql, $bindings);

    $result = [];
    $grand  = [
        'belum_jatuh_tempo' => 0, 'd1_30' => 0, 'd31_60' => 0,
        'd61_90' => 0, 'd90plus' => 0, 'total_overdue' => 0, 'total_piutang' => 0,
    ];

    foreach ($rows as $r) {
        $overdue = $r->d1_30 + $r->d31_60 + $r->d61_90 + $r->d90plus;

        $result[] = [
            'customer_name'     => $r->customer_name,
            'belum_jatuh_tempo' => (float) $r->belum_jatuh_tempo,
            'd1_30'             => (float) $r->d1_30,
            'd31_60'            => (float) $r->d31_60,
            'd61_90'            => (float) $r->d61_90,
            'd90plus'           => (float) $r->d90plus,
            'total_overdue'     => (float) $overdue,
            'total_piutang'     => (float) $r->total_piutang,
        ];

        $grand['belum_jatuh_tempo'] += $r->belum_jatuh_tempo;
        $grand['d1_30']             += $r->d1_30;
        $grand['d31_60']            += $r->d31_60;
        $grand['d61_90']            += $r->d61_90;
        $grand['d90plus']           += $r->d90plus;
        $grand['total_overdue']     += $overdue;
        $grand['total_piutang']     += $r->total_piutang;
    }

    return Excel::download(
        new ArAgingReportExport($result, $grand, $cutoffDate),
        'AR_Aging_Report_' . str_replace('-', '', $cutoffDate) . '.xlsx'
    );
}
}