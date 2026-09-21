<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
     * Endpoint AJAX utama. Dipanggil oleh tombol "Generate Report" di view.
     * Mengembalikan JSON: rows per customer + grand total + info balance DRAFT.
     */
    public function data(Request $request)
    {
        // ── Tanggal cut-off (wajib, single date, format DD-MM-YYYY) ──
        $cutoffDate = $request->cutoffDate ? trim($request->cutoffDate) : date('d-m-Y');

        // ── Filter tambahan opsional ──
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

        $bindings = ['cutoff' => $cutoffDate];

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
            FROM (
                SELECT
                    invoice_hdr.invoice_number,
                    invoice_hdr.customer_id,
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
                ) bayar ON true
                WHERE invoice_hdr.status NOT IN ('1','5')
                $whereExtra
            ) piutang
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