<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ApAgingReportExport;
use DB;

/*
    ================================================================
    AP AGING REPORT  (hutang kita ke SUPPLIER)
    ================================================================
    Kembaran AR Aging Report, tapi sisi HUTANG:
      - Sumber : ap_invoice (bukan invoice_hdr)
      - Pihak  : supplier (third_party_type = 'supp')
      - Tanggal anchor : ap_date (fallback inv_date) -- tanggal AP dibukukan
      - Jatuh tempo    : ap_invoice.due_date (kalau diisi), fallback
                         ap_date + term supplier (third_party.top_batas_1),
                         PERSIS pola due_date di AccountPayableController::list().
      - Pembayaran     : BEDA dgn AR. Pelunasan AP tercatat di kas_det.DEBIT
                         (bukan credit). Voucher KK/BK/BM/KM (BM/KM dipakai kalau
                         AP dilunasi via offset -- mis. AR & AP pihak sama saling
                         impas), pihak dicocokkan via paid_to ATAU receive_from
                         (voucher masuk BM/KM pakai receive_from), status <> '5',
                         voucher_date <= cutoff.
      - Balance = grand_total - total pembayaran approved (<= cutoff).
      - Status ap_invoice: 1 DRAFT, 2 VALIDATED, 3 APPROVED, 4 POSTED,
        5 CANCELED, 6 PAID, 7 PARTIALLY PAID. DRAFT & CANCELED di-exclude;
        yang lunas otomatis hilang karena balance <= 0.
    ================================================================
*/

class ApAgingReportController extends Controller
{
    private $title = "AP Aging Report";

    // AP sebelum tanggal ini tidak pernah dibaca oleh report (batas bawah data).
    private $floorDate = '01-01-2023';

    // AP sebelum tanggal ini (2023) hanya ikut kalau sudah punya pasangan
    // voucher di kas_det -- banyak AP lama yang voucher pembayarannya belum
    // sempat diinput, kalau dipaksa ikut akan muncul sebagai hutang palsu.
    private $pairRequiredBefore = '01-01-2024';

    // Ambang outstanding: balance <= ini dianggap lunas (sisa pembulatan PPN/
    // diskon bisa menyisakan pecahan < 1 rupiah). Skala IDR, jadi 1 rupiah aman.
    private $minOutstanding = 1;

    public function index(Request $request)
    {
        $data['title'] = $this->title;

        $data['suppliers'] = DB::table('third_party')
            ->where('third_party_type', '=', 'supp')
            ->orderBy('nama')
            ->get();

        return view('apAgingReport.index', $data);
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

    private function buildFilters(Request $request)
    {
        $supplierCodes = $request->supplier; // array kode supplier (multi-select)

        $bindings   = [];
        $whereExtra = "";

        if ($supplierCodes && is_array($supplierCodes) && count($supplierCodes) > 0) {
            $escaped = array_map(function ($c) {
                return "'" . str_replace("'", "''", $c) . "'";
            }, $supplierCodes);
            $whereExtra .= " AND ap_invoice.supplier_id IN (" . implode(',', $escaped) . ") ";
        }

        return [$whereExtra, $bindings];
    }

    /**
     * Subquery per-AP: balance & umur hutang terhadap :cutoff.
     * anchor date = coalesce(ap_date, inv_date) supaya AP lama yang ap_date-nya
     * kosong tetap ikut. Jatuh tempo & pembayaran lihat komentar header.
     */
    /**
     * Syarat sebuah baris voucher (kas_det) dianggap PEMBAYARAN/pengurang hutang AP.
     * Sumber: KK/BK (paid_to = supplier), BM/KM (offset, cukup via reference),
     * dan GJ (General Journal) -- GJ dihitung hanya kalau baris debitnya di akun
     * hutang supplier (third_party.account), supaya baris beban/lawan tidak ikut.
     * Semua dicocokkan lewat kas_det.reference = ap_invoice.inv_number.
     */
    private function paymentMatchSql($hdr, $det)
    {
        return "(
            ($hdr.voucher_type IN ('KK','BK') AND $hdr.paid_to = ap_invoice.supplier_id)
            OR $hdr.voucher_type IN ('BM','KM')
            OR ($hdr.voucher_type = 'GJ'
                AND $det.account = (SELECT tp2.account FROM third_party tp2 WHERE tp2.kode = ap_invoice.supplier_id))
        )";
    }

    /**
     * Total pembayaran dalam periode [$startDate, $cutoffDate] atas AP yang masuk
     * populasi aging (aturan floor/pair sama persis dgn buildHutangSubquery).
     * Dipakai AP Dashboard supaya Opening + Pembelian - Pembayaran = Balance.
     */
    public function totalPaidBetween($startDate, $cutoffDate)
    {
        $anchor = "COALESCE(
                to_date(NULLIF(ap_invoice.ap_date,''),'DD-MM-YYYY'),
                to_date(NULLIF(ap_invoice.inv_date,''),'DD-MM-YYYY')
              )";
        $partyMatch = $this->paymentMatchSql('kas_hdr', 'kas_det');
        $pairMatch  = $this->paymentMatchSql('h', 'd');

        $row = DB::selectOne("
            SELECT COALESCE(SUM(kas_det.debit),0) as total
            FROM ap_invoice
            JOIN kas_det ON kas_det.reference = ap_invoice.inv_number
            JOIN kas_hdr ON kas_hdr.voucher_number = kas_det.voucher_number
            WHERE ap_invoice.status NOT IN ('1','5')
              AND $partyMatch
              AND kas_hdr.status <> '5'
              AND to_date(kas_hdr.voucher_date,'DD-MM-YYYY') BETWEEN to_date(:startDate,'DD-MM-YYYY') AND to_date(:cutoff,'DD-MM-YYYY')
              AND $anchor >= to_date(:floorDate,'DD-MM-YYYY')
              AND $anchor <= to_date(:cutoff,'DD-MM-YYYY')
              AND (
                    $anchor >= to_date(:pairRequiredBefore,'DD-MM-YYYY')
                    OR EXISTS (
                        SELECT 1 FROM kas_det d
                        JOIN kas_hdr h ON h.voucher_number = d.voucher_number
                        WHERE d.reference = ap_invoice.inv_number
                          AND $pairMatch
                          AND h.status <> '5'
                    )
                  )
        ", [
            'startDate'          => $startDate,
            'cutoff'             => $cutoffDate,
            'floorDate'          => $this->floorDate,
            'pairRequiredBefore' => $this->pairRequiredBefore,
        ]);
        return (float) $row->total;
    }

    private function buildHutangSubquery($whereExtra)
{
    $anchor = "COALESCE(
                to_date(NULLIF(ap_invoice.ap_date,''),'DD-MM-YYYY'),
                to_date(NULLIF(ap_invoice.inv_date,''),'DD-MM-YYYY')
              )";

    $jatuhTempo = "COALESCE(
                to_date(NULLIF(ap_invoice.due_date,''),'DD-MM-YYYY'),
                ($anchor + INTERVAL '1 day' * COALESCE(
                    (SELECT top_batas_1 FROM third_party tp WHERE tp.kode = ap_invoice.supplier_id),
                    0
                ))::date
              )";

    // Syarat pihak HANYA berlaku untuk KK/BK (voucher keluar langsung ke
    // supplier via paid_to). Untuk BM/KM (offset AR<->AP), paid_to selalu
    // NULL dan receive_from berisi kode akun kas/bank (mis. 1100.40.32),
    // BUKAN kode supplier -- jadi tidak bisa dipakai sebagai syarat pihak.
    // kas_det.reference = ap_invoice.inv_number sudah cukup presisi untuk
    // BM/KM karena reference selalu diisi nomor invoice AP yang dituju.
    $partyMatch = $this->paymentMatchSql('kas_hdr', 'kas_det');
    $pairMatch  = $this->paymentMatchSql('h', 'd');

    return "
        SELECT
            ap_invoice.id as ap_id,
            ap_invoice.ap_number,
            ap_invoice.inv_number,
            ap_invoice.supplier_id,
            to_char($anchor,'DD-MM-YYYY') as ap_date,
            ap_invoice.inv_date,
            COALESCE(
                (SELECT top_batas_1 FROM third_party tp WHERE tp.kode = ap_invoice.supplier_id),
                0
            ) as term,
            $jatuhTempo as jatuh_tempo_actual,
            -- Status PAID (6): dipaksa 0 HANYA kalau voucher pelunasannya belum lengkap
            -- (total_semua < grand_total). Kalau sudah lunas di voucher, dihitung normal
            -- per cutoff, supaya saldo historis (mis. opening tahun lalu) tidak hilang.
            (CASE WHEN ap_invoice.status = '6'
                       AND COALESCE(bayar.total_semua,0) < ap_invoice.grand_total - 1 THEN 0
                  ELSE ap_invoice.grand_total - COALESCE(bayar.total_dibayar,0) END) as balance,
            (to_date(:cutoff,'DD-MM-YYYY') - $jatuhTempo) as diff_hari
        FROM ap_invoice
        LEFT JOIN LATERAL (
            -- Pelunasan AP tercatat di kas_det.DEBIT lewat voucher KK/BK
            -- (paid_to = supplier) atau BM/KM (offset, dicocokkan via
            -- reference saja -- lihat catatan partyMatch di atas).
            SELECT SUM(kas_det.debit) FILTER (WHERE to_date(kas_hdr.voucher_date,'DD-MM-YYYY') <= to_date(:cutoff,'DD-MM-YYYY')) as total_dibayar,
                   SUM(kas_det.debit) as total_semua
            FROM kas_det
            JOIN kas_hdr ON kas_det.voucher_number = kas_hdr.voucher_number
            WHERE kas_det.reference = ap_invoice.inv_number
              AND $partyMatch
              AND kas_hdr.status <> '5'
        ) bayar ON true
        WHERE ap_invoice.status NOT IN ('1','5')
          AND $anchor >= to_date(:floorDate,'DD-MM-YYYY')
          AND $anchor <= to_date(:cutoff,'DD-MM-YYYY')
          AND (
                $anchor >= to_date(:pairRequiredBefore,'DD-MM-YYYY')
                OR EXISTS (
                    SELECT 1 FROM kas_det d
                    JOIN kas_hdr h ON h.voucher_number = d.voucher_number
                    WHERE d.reference = ap_invoice.inv_number
                      AND $pairMatch
                      AND h.status <> '5'
                )
              )
          $whereExtra
    ";
}

    private function bucketWhere($bucket)
    {
        switch ($bucket) {
            case 'belum_jatuh_tempo': return " AND hutang.diff_hari <= 0 ";
            case 'd1_30':             return " AND hutang.diff_hari BETWEEN 1 AND 30 ";
            case 'd31_60':            return " AND hutang.diff_hari BETWEEN 31 AND 60 ";
            case 'd61_90':            return " AND hutang.diff_hari BETWEEN 61 AND 90 ";
            case 'd90plus':           return " AND hutang.diff_hari > 90 ";
            case 'total_overdue':     return " AND hutang.diff_hari > 0 ";
            case 'total_hutang':      return "";
            default:                  return "";
        }
    }

    /**
     * Total hutang outstanding per cutoff (tanpa filter supplier).
     */
    public function totalOutstanding($cutoffDate)
    {
        $bindings = [
            'cutoff'             => $cutoffDate,
            'floorDate'          => $this->floorDate,
            'pairRequiredBefore' => $this->pairRequiredBefore,
        ];
        $subquery = $this->buildHutangSubquery('');
        $row = DB::selectOne("SELECT COALESCE(SUM(balance),0) as total FROM ($subquery) hutang WHERE balance > {$this->minOutstanding}", $bindings);
        return (float) $row->total;
    }

    /**
     * Jumlah saldo yang DIBUANG dari total hutang (balance <= minOutstanding,
     * termasuk saldo negatif / lebih bayar). Untuk rekonsiliasi AP Dashboard.
     */
    public function excludedBalance($cutoffDate)
    {
        $bindings = [
            'cutoff'             => $cutoffDate,
            'floorDate'          => $this->floorDate,
            'pairRequiredBefore' => $this->pairRequiredBefore,
        ];
        $subquery = $this->buildHutangSubquery('');
        $row = DB::selectOne("SELECT COALESCE(SUM(balance),0) as total FROM ($subquery) hutang WHERE balance <= {$this->minOutstanding}", $bindings);
        return (float) $row->total;
    }

    public function data(Request $request)
    {
        $cutoffDate = $request->cutoffDate ? trim($request->cutoffDate) : date('d-m-Y');

        list($whereExtra, $bindings) = $this->buildFilters($request);
        $bindings['cutoff']    = $cutoffDate;
        $bindings['floorDate'] = $this->floorDate;
        $bindings['pairRequiredBefore'] = $this->pairRequiredBefore;

        $subquery = $this->buildHutangSubquery($whereExtra);

        $sql = "
            SELECT
                hutang.supplier_id as supplier_code,
                third_party.nama   as supplier_name,
                SUM(hutang.balance) as total_hutang,
                SUM(CASE WHEN hutang.diff_hari <= 0               THEN hutang.balance ELSE 0 END) as belum_jatuh_tempo,
                SUM(CASE WHEN hutang.diff_hari BETWEEN 1  AND 30  THEN hutang.balance ELSE 0 END) as d1_30,
                SUM(CASE WHEN hutang.diff_hari BETWEEN 31 AND 60  THEN hutang.balance ELSE 0 END) as d31_60,
                SUM(CASE WHEN hutang.diff_hari BETWEEN 61 AND 90  THEN hutang.balance ELSE 0 END) as d61_90,
                SUM(CASE WHEN hutang.diff_hari > 90               THEN hutang.balance ELSE 0 END) as d90plus
            FROM ($subquery) hutang
            LEFT JOIN third_party ON third_party.kode = hutang.supplier_id
            WHERE hutang.balance > {$this->minOutstanding}
            GROUP BY hutang.supplier_id, third_party.nama
            ORDER BY third_party.nama ASC
        ";

        $rows = DB::select($sql, $bindings);

        $grand = [
            'belum_jatuh_tempo' => 0, 'd1_30' => 0, 'd31_60' => 0,
            'd61_90' => 0, 'd90plus' => 0, 'total_overdue' => 0, 'total_hutang' => 0,
        ];

        $result = [];
        foreach ($rows as $r) {
            $overdue    = $r->d1_30 + $r->d31_60 + $r->d61_90 + $r->d90plus;
            $pctOverdue = $r->total_hutang > 0 ? ($overdue / $r->total_hutang) * 100 : 0;

            $result[] = [
                'supplier_code'     => $r->supplier_code,
                'supplier_name'     => $r->supplier_name,
                'belum_jatuh_tempo' => (float) $r->belum_jatuh_tempo,
                'd1_30'             => (float) $r->d1_30,
                'd31_60'            => (float) $r->d31_60,
                'd61_90'            => (float) $r->d61_90,
                'd90plus'           => (float) $r->d90plus,
                'total_overdue'     => (float) $overdue,
                'total_hutang'      => (float) $r->total_hutang,
                'pct_overdue'       => round($pctOverdue, 1),
            ];

            $grand['belum_jatuh_tempo'] += $r->belum_jatuh_tempo;
            $grand['d1_30']             += $r->d1_30;
            $grand['d31_60']            += $r->d31_60;
            $grand['d61_90']            += $r->d61_90;
            $grand['d90plus']           += $r->d90plus;
            $grand['total_overdue']     += $overdue;
            $grand['total_hutang']      += $r->total_hutang;
        }

        $grand['pct_overdue'] = $grand['total_hutang'] > 0
            ? round(($grand['total_overdue'] / $grand['total_hutang']) * 100, 1)
            : 0;

        // Info rekonsiliasi: total grand_total AP DRAFT (tidak masuk aging)
        $draftBalance = DB::selectOne("
            SELECT COALESCE(SUM(grand_total),0) as total
            FROM ap_invoice
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

    public function detail(Request $request)
    {
        $cutoffDate   = $request->cutoffDate ? trim($request->cutoffDate) : date('d-m-Y');
        $supplierCode = $request->supplierCode ? trim($request->supplierCode) : null;
        $bucket       = $request->bucket ? trim($request->bucket) : 'total_hutang';

        list($whereExtra, $bindings) = $this->buildFilters($request);
        $bindings['cutoff']    = $cutoffDate;
        $bindings['floorDate'] = $this->floorDate;
        $bindings['pairRequiredBefore'] = $this->pairRequiredBefore;

        if ($supplierCode) {
            $whereExtra .= " AND ap_invoice.supplier_id = :detailSupplier ";
            $bindings['detailSupplier'] = $supplierCode;
        }

        $subquery    = $this->buildHutangSubquery($whereExtra);
        $bucketWhere = $this->bucketWhere($bucket);

        $sql = "
            SELECT
                hutang.ap_id,
                hutang.ap_number,
                hutang.inv_number,
                third_party.nama as supplier_name,
                hutang.ap_date,
                hutang.inv_date,
                hutang.term,
                hutang.jatuh_tempo_actual,
                hutang.balance
            FROM ($subquery) hutang
            LEFT JOIN third_party ON third_party.kode = hutang.supplier_id
            WHERE hutang.balance > {$this->minOutstanding}
            $bucketWhere
            ORDER BY hutang.jatuh_tempo_actual ASC, hutang.ap_number ASC
        ";

        $rows = DB::select($sql, $bindings);

        $result = [];
        foreach ($rows as $r) {
            $result[] = [
                'ap_number'     => $r->ap_number,
                'inv_number'    => $r->inv_number,
                'supplier_name' => $r->supplier_name,
                'ap_date'       => $r->ap_date ?: '-',
                'inv_date'      => $r->inv_date ?: '-',
                'term'          => $r->term !== null ? ((int) $r->term . ' hari') : '-',
                'jatuh_tempo'   => $r->jatuh_tempo_actual ? date('d-m-Y', strtotime($r->jatuh_tempo_actual)) : '-',
                'balance'       => (float) $r->balance,
                'ap_link'       => route('accountPayable.show', ['id' => Crypt::encryptString($r->ap_id)]),
            ];
        }

        return response()->json([
            'status'      => 1,
            'bucketLabel' => $this->bucketLabels()[$bucket] ?? ($bucket === 'total_overdue' ? 'Total Overdue' : 'Total Hutang'),
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
        $bindings['pairRequiredBefore'] = $this->pairRequiredBefore;

        $subquery = $this->buildHutangSubquery($whereExtra);

        $sql = "
            SELECT
                hutang.supplier_id as supplier_code,
                third_party.nama   as supplier_name,
                SUM(hutang.balance) as total_hutang,
                SUM(CASE WHEN hutang.diff_hari <= 0               THEN hutang.balance ELSE 0 END) as belum_jatuh_tempo,
                SUM(CASE WHEN hutang.diff_hari BETWEEN 1  AND 30  THEN hutang.balance ELSE 0 END) as d1_30,
                SUM(CASE WHEN hutang.diff_hari BETWEEN 31 AND 60  THEN hutang.balance ELSE 0 END) as d31_60,
                SUM(CASE WHEN hutang.diff_hari BETWEEN 61 AND 90  THEN hutang.balance ELSE 0 END) as d61_90,
                SUM(CASE WHEN hutang.diff_hari > 90               THEN hutang.balance ELSE 0 END) as d90plus
            FROM ($subquery) hutang
            LEFT JOIN third_party ON third_party.kode = hutang.supplier_id
            WHERE hutang.balance > {$this->minOutstanding}
            GROUP BY hutang.supplier_id, third_party.nama
            ORDER BY third_party.nama ASC
        ";

        $rows = DB::select($sql, $bindings);

        $result = [];
        $grand  = [
            'belum_jatuh_tempo' => 0, 'd1_30' => 0, 'd31_60' => 0,
            'd61_90' => 0, 'd90plus' => 0, 'total_overdue' => 0, 'total_hutang' => 0,
        ];

        foreach ($rows as $r) {
            $overdue = $r->d1_30 + $r->d31_60 + $r->d61_90 + $r->d90plus;

            $result[] = [
                'supplier_name'     => $r->supplier_name,
                'belum_jatuh_tempo' => (float) $r->belum_jatuh_tempo,
                'd1_30'             => (float) $r->d1_30,
                'd31_60'            => (float) $r->d31_60,
                'd61_90'            => (float) $r->d61_90,
                'd90plus'           => (float) $r->d90plus,
                'total_overdue'     => (float) $overdue,
                'total_hutang'      => (float) $r->total_hutang,
            ];

            $grand['belum_jatuh_tempo'] += $r->belum_jatuh_tempo;
            $grand['d1_30']             += $r->d1_30;
            $grand['d31_60']            += $r->d31_60;
            $grand['d61_90']            += $r->d61_90;
            $grand['d90plus']           += $r->d90plus;
            $grand['total_overdue']     += $overdue;
            $grand['total_hutang']      += $r->total_hutang;
        }

        return Excel::download(
            new ApAgingReportExport($result, $grand, $cutoffDate),
            'AP_Aging_Report_' . str_replace('-', '', $cutoffDate) . '.xlsx'
        );
    }
}
