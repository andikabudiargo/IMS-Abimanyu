<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
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
         invoice_number, DAN kas_hdr.voucher_type = 'BM' (Bukti Masuk
         -- dikonfirmasi dari query voucher_type di kas_hdr; voucher_type
         'INV' adalah posting invoice itu sendiri, BUKAN pembayaran,
         jadi tidak boleh ikut dihitung sebagai pengurang balance).
         Status di-filter = '3' (APPROVED) -- dikonfirmasi dari query
         distribusi status BM: mayoritas (2202) berstatus 3, sisanya
         1 (DRAFT, 1 baris) dan 5 (CANCELED, 17 baris) sengaja di-exclude.
         Di sini saya pakai SUM (bukan scalar subquery tanpa agregasi)
         supaya kalau satu invoice dibayar bertahap (partial payment /
         lebih dari satu voucher BM), semua kredit tetap terhitung.
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
        $invoiceDateRange = $request->invoiceDateRange; // "DD-MM-YYYY to DD-MM-YYYY"
        $customerCodes    = $request->customer;          // array kode customer (multi-select), boleh kosong

        $invFrom = null;
        $invTo   = null;
        if ($invoiceDateRange) {
            $parts = explode('to', $invoiceDateRange);
            if (count($parts) > 1) {
                $invFrom = trim($parts[0]);
                $invTo   = trim($parts[1]);
            } else {
                $invFrom = trim($parts[0]);
                $invTo   = $invFrom;
            }
        }

        $bindings = [];

        $whereExtra = "";
        if ($invFrom && $invTo) {
            $whereExtra .= " AND to_date(invoice_hdr.invoice_date,'DD-MM-YYYY')
                              BETWEEN to_date(:invFrom,'DD-MM-YYYY') AND to_date(:invTo,'DD-MM-YYYY') ";
            $bindings['invFrom'] = $invFrom;
            $bindings['invTo']   = $invTo;
        }

        if ($customerCodes && is_array($customerCodes) && count($customerCodes) > 0) {
            // whereIn manual (named binding PDO tidak mendukung array secara langsung untuk IN)
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
     * - Pembayaran (kas_hdr voucher_type BM, status APPROVED) hanya dihitung
     *   sebagai pengurang balance kalau voucher_date-nya <= :cutoff. Ini
     *   penting supaya laporan tetap jadi snapshot yang benar: kalau cut-off
     *   di-set mundur (mis. 22 Sept) tapi pelunasannya baru terjadi setelah
     *   itu (mis. dibayar 25 Sept), invoice tsb TIDAK boleh kebaca lunas
     *   pada cut-off 22 Sept.
     */
    private function buildPiutangSubquery($whereExtra)
    {
        return "
            SELECT
                invoice_hdr.id as invoice_id,
                invoice_hdr.invoice_number,
                invoice_hdr.customer_id,
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
                  AND kas_hdr.voucher_type = 'BM'
                  AND kas_hdr.status = '3'
                  AND to_date(kas_hdr.voucher_date,'DD-MM-YYYY') <= to_date(:cutoff,'DD-MM-YYYY')
            ) bayar ON true
            WHERE invoice_hdr.status NOT IN ('1','5')
              AND to_date(invoice_hdr.invoice_date,'DD-MM-YYYY') >= to_date(:floorDate,'DD-MM-YYYY')
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
        $cutoffDate  = $request->cutoffDate ? trim($request->cutoffDate) : date('d-m-Y');
        $customerCode = $request->customerCode ? trim($request->customerCode) : null;
        $bucket       = $request->bucket ? trim($request->bucket) : 'total_piutang';

        list($whereExtra, $bindings) = $this->buildFilters($request);
        $bindings['cutoff']    = $cutoffDate;
        $bindings['floorDate'] = $this->floorDate;

        if ($customerCode) {
            $whereExtra .= " AND invoice_hdr.customer_id = :detailCustomer ";
            $bindings['detailCustomer'] = $customerCode;
        }

        $subquery   = $this->buildPiutangSubquery($whereExtra);
        $bucketWhere = $this->bucketWhere($bucket);

        $sql = "
            SELECT
                piutang.invoice_id,
                piutang.invoice_number,
                third_party.nama as customer_name,
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
                'jatuh_tempo'    => $r->jatuh_tempo_actual ? date('d-m-Y', strtotime($r->jatuh_tempo_actual)) : '-',
                'balance'        => (float) $r->balance,
                'invoice_link'   => route('invoice.show', ['id' => Crypt::encryptString($r->invoice_id)]),
            ];
        }

        return response()->json([
            'status'       => 1,
            'bucketLabel'  => $this->bucketLabels()[$bucket] ?? ($bucket === 'total_overdue' ? 'Total Overdue' : 'Total Piutang'),
            'rows'         => $result,
            'total'        => array_sum(array_column($result, 'balance')),
        ]);
    }

    public function export(Request $request)
    {
        // Rekomendasi: buat class Export terpisah (mis. ArAgingReportExport)
        // yang menerima payload sama dengan data() lalu generate via
        // Maatwebsite\Excel (FromArray / FromView), sama seperti pola
        // StoReportExport yang sudah Anda pakai di modul STO Report.
        //
        // return Excel::download(new ArAgingReportExport($request->all()), 'AR_Aging_Report.xlsx');
        //
        // Endpoint ini di-stub dulu supaya route & tombol export di frontend
        // sudah siap dipasang; tinggal isi logic export-nya menyusul.
        return response()->json(['status' => 0, 'message' => 'Export belum diimplementasikan.']);
    }
}