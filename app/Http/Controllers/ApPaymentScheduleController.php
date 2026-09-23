<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ApPaymentScheduleExport;
use DB;

/*
    ================================================================
    AP PAYMENT SCHEDULE  (jadwal bayar hutang ke SUPPLIER)
    ================================================================
    Kembaran AR Payment Schedule, sisi HUTANG. Filter utama PERIODE
    (bulan & tahun). Tiap supplier di-pecah:
      - Opening    : saldo AP yang jatuh tempo SEBELUM awal periode.
      - Kolom 1..N : AP yang jatuh tempo pada tanggal itu dalam periode.
      - Total      : Opening + seluruh kolom tanggal.
      - Paid       : uang keluar ke supplier selama periode.
      - Balance    : Total - Paid.
      - Outstanding: bagian Total yang jatuh tempo-nya lewat & belum dibayar.

    Rumus jatuh tempo & pembayaran PERSIS SAMA dengan ApAgingReportController:
      - anchor date  = ap_date (fallback inv_date).
      - jatuh_tempo  = ap_invoice.due_date, fallback anchor + top_batas_1 hari.
      - pembayaran   = kas_det.DEBIT (bayar supplier) lewat voucher KK/BK,
                       kas_hdr.paid_to = supplier, status <> '5'.
      - ap_invoice DRAFT('1') & CANCELED('5') di-exclude.
    ================================================================
*/

class ApPaymentScheduleController extends Controller
{
    private $title = "AP Payment Schedule";

    private $floorDate = '01-01-2023';
    private $pairRequiredBefore = '01-01-2024';

    // balance <= ini dianggap lunas (sisa pembulatan PPN/diskon).
    private $minOutstanding = 1;

    public function index(Request $request)
    {
        $data['title'] = $this->title;

        $data['suppliers'] = DB::table('third_party')
            ->where('third_party_type', '=', 'supp')
            ->orderBy('nama')
            ->get();

        return view('apPaymentSchedule.index', $data);
    }

    private function periodBounds($month, $year)
    {
        $month = max(1, min(12, (int) $month));
        $year  = (int) $year;

        $start = \DateTime::createFromFormat('!Y-n-j', "$year-$month-1");
        $daysInMonth = (int) $start->format('t');

        $end = clone $start;
        $end->modify('+' . ($daysInMonth - 1) . ' days');

        return [$start->format('d-m-Y'), $end->format('d-m-Y'), $daysInMonth];
    }

    private function resolveAsOf($periodEnd)
    {
        $periodEndDt = \DateTime::createFromFormat('d-m-Y', $periodEnd);
        $today = new \DateTime();
        return $periodEndDt < $today ? $periodEnd : $today->format('d-m-Y');
    }

    private function buildFilters(Request $request)
    {
        $supplierCodes = $request->supplier;

        $whereExtra = "";
        if ($supplierCodes && is_array($supplierCodes) && count($supplierCodes) > 0) {
            $escaped = array_map(function ($c) {
                return "'" . str_replace("'", "''", $c) . "'";
            }, $supplierCodes);
            $whereExtra .= " AND ap_invoice.supplier_id IN (" . implode(',', $escaped) . ") ";
        }

        return [$whereExtra, []];
    }

    /**
     * Subquery per-AP: due date sama dengan ApAgingReport, balance dipecah
     * jadi balance_open (sebelum periode) & balance_asof (open - paid periode).
     * Pembayaran = kas_det.DEBIT (bayar supplier) lewat voucher KK/BK.
     */
    private function buildScheduleSubquery($whereExtra)
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

        // baris pembayaran AP: debit, voucher KK/BK, paid_to = supplier, status<>5
        $payWhere = "kas_det.reference = ap_invoice.inv_number
                     AND kas_hdr.paid_to = ap_invoice.supplier_id
                     AND kas_hdr.voucher_type IN ('KK','BK')
                     AND kas_hdr.status <> '5'";

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
                (ap_invoice.grand_total - COALESCE(pb.paid,0)) as balance_open,
                COALESCE(pp.paid,0) as paid_in_period,
                (ap_invoice.grand_total - COALESCE(pb.paid,0) - COALESCE(pp.paid,0)) as balance_asof
            FROM ap_invoice
            LEFT JOIN LATERAL (
                SELECT SUM(kas_det.debit) as paid
                FROM kas_det
                JOIN kas_hdr ON kas_det.voucher_number = kas_hdr.voucher_number
                WHERE $payWhere
                  AND to_date(kas_hdr.voucher_date,'DD-MM-YYYY') < to_date(:periodStart,'DD-MM-YYYY')
            ) pb ON true
            LEFT JOIN LATERAL (
                SELECT SUM(kas_det.debit) as paid
                FROM kas_det
                JOIN kas_hdr ON kas_det.voucher_number = kas_hdr.voucher_number
                WHERE $payWhere
                  AND to_date(kas_hdr.voucher_date,'DD-MM-YYYY') >= to_date(:periodStart,'DD-MM-YYYY')
                  AND to_date(kas_hdr.voucher_date,'DD-MM-YYYY') <= to_date(:asOf,'DD-MM-YYYY')
            ) pp ON true
            WHERE ap_invoice.status NOT IN ('1','5')
              AND $anchor >= to_date(:floorDate,'DD-MM-YYYY')
              AND $anchor <= to_date(:asOf,'DD-MM-YYYY')
              AND (
                    $anchor >= to_date(:pairRequiredBefore,'DD-MM-YYYY')
                    OR EXISTS (
                        SELECT 1 FROM kas_det d
                        JOIN kas_hdr h ON h.voucher_number = d.voucher_number
                        WHERE d.reference = ap_invoice.inv_number
                          AND h.paid_to = ap_invoice.supplier_id
                          AND h.voucher_type IN ('KK','BK')
                          AND h.status <> '5'
                    )
                  )
              $whereExtra
        ";
    }

    private function bucketWhere($bucket, $daysInMonth)
    {
        if ($bucket === 'opening') {
            return " AND hutang.jatuh_tempo_actual < to_date(:periodStart,'DD-MM-YYYY') ";
        }
        if ($bucket === 'outstanding') {
            return " AND hutang.jatuh_tempo_actual <= to_date(:asOf,'DD-MM-YYYY') AND hutang.balance_asof > {$this->minOutstanding} ";
        }
        if (preg_match('/^d(\d+)$/', (string) $bucket, $m)) {
            $day = (int) $m[1];
            if ($day >= 1 && $day <= $daysInMonth) {
                return " AND hutang.jatuh_tempo_actual = (to_date(:periodStart,'DD-MM-YYYY') + " . ($day - 1) . ")::date ";
            }
        }
        return "";
    }

    private function bucketLabel($bucket)
    {
        if ($bucket === 'opening') return 'Opening Balance';
        if ($bucket === 'total') return 'Total';
        if ($bucket === 'outstanding') return 'Outstanding (Sudah Lewat Jatuh Tempo)';
        if (preg_match('/^d(\d+)$/', (string) $bucket, $m)) return 'Jatuh Tempo Tanggal ' . $m[1];
        return $bucket;
    }

    private function computeSchedule(Request $request)
    {
        $month = $request->month ? (int) $request->month : (int) date('n');
        $year  = $request->year  ? (int) $request->year  : (int) date('Y');

        list($periodStart, $periodEnd, $daysInMonth) = $this->periodBounds($month, $year);
        $asOf = $this->resolveAsOf($periodEnd);

        list($whereExtra, $bindings) = $this->buildFilters($request);
        $bindings['periodStart']         = $periodStart;
        $bindings['periodEnd']           = $periodEnd;
        $bindings['asOf']                = $asOf;
        $bindings['floorDate']           = $this->floorDate;
        $bindings['pairRequiredBefore']  = $this->pairRequiredBefore;

        $subquery = $this->buildScheduleSubquery($whereExtra);

        $dayCases = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $cond = "hutang.jatuh_tempo_actual = (to_date(:periodStart,'DD-MM-YYYY') + " . ($d - 1) . ")::date";
            $dayCases[] = "SUM(CASE WHEN $cond THEN hutang.balance_open ELSE 0 END) as d$d";
            $dayCases[] = "SUM(CASE WHEN $cond THEN hutang.balance_asof ELSE 0 END) as r$d";
        }

        $sql = "
            SELECT
                hutang.supplier_id as supplier_code,
                third_party.nama   as supplier_name,
                SUM(CASE WHEN hutang.jatuh_tempo_actual < to_date(:periodStart,'DD-MM-YYYY') THEN hutang.balance_open ELSE 0 END) as opening,
                SUM(CASE WHEN hutang.jatuh_tempo_actual < to_date(:periodStart,'DD-MM-YYYY') THEN hutang.balance_asof ELSE 0 END) as opening_r,
                " . implode(",\n                ", $dayCases) . ",
                SUM(hutang.balance_open) as total,
                SUM(hutang.paid_in_period) as paid,
                SUM(CASE WHEN hutang.jatuh_tempo_actual <= to_date(:asOf,'DD-MM-YYYY') AND hutang.balance_asof > {$this->minOutstanding} THEN hutang.balance_asof ELSE 0 END) as outstanding
            FROM ($subquery) hutang
            LEFT JOIN third_party ON third_party.kode = hutang.supplier_id
            WHERE hutang.jatuh_tempo_actual <= to_date(:periodEnd,'DD-MM-YYYY')
            GROUP BY hutang.supplier_id, third_party.nama
            HAVING SUM(hutang.balance_open) > {$this->minOutstanding}
            ORDER BY third_party.nama ASC
        ";

        $rows = DB::select($sql, $bindings);

        $grand = ['opening' => 0.0, 'total' => 0.0, 'paid' => 0.0, 'balance' => 0.0, 'outstanding' => 0.0];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $grand['d' . $d] = 0.0;
        }

        $result = [];
        foreach ($rows as $r) {
            $row  = (array) $r;
            $days = [];
            $daysRemain = [];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $key = 'd' . $d;
                $days[$key] = (float) $row[$key];
                $daysRemain[$key] = (float) $row['r' . $d];
                $grand[$key] += $days[$key];
            }
            $balance = (float) $row['total'] - (float) $row['paid'];

            $result[] = [
                'supplier_code' => $r->supplier_code,
                'supplier_name' => $r->supplier_name,
                'opening'       => (float) $r->opening,
                'opening_remain'=> (float) $row['opening_r'],
                'days'          => $days,
                'days_remain'   => $daysRemain,
                'total'         => (float) $row['total'],
                'paid'          => (float) $row['paid'],
                'balance'       => $balance,
                'outstanding'   => (float) $row['outstanding'],
            ];

            $grand['opening']     += (float) $r->opening;
            $grand['total']       += (float) $row['total'];
            $grand['paid']        += (float) $row['paid'];
            $grand['balance']     += $balance;
            $grand['outstanding'] += (float) $row['outstanding'];
        }

        return [
            'month'       => $month,
            'year'        => $year,
            'periodStart' => $periodStart,
            'periodEnd'   => $periodEnd,
            'daysInMonth' => $daysInMonth,
            'asOf'        => $asOf,
            'rows'        => $result,
            'grand'       => $grand,
        ];
    }

    public function data(Request $request)
    {
        $s = $this->computeSchedule($request);
        return response()->json(array_merge(['status' => 1], $s));
    }

    public function detail(Request $request)
    {
        $month        = $request->month ? (int) $request->month : (int) date('n');
        $year         = $request->year  ? (int) $request->year  : (int) date('Y');
        $supplierCode = $request->supplierCode ? trim($request->supplierCode) : null;
        $bucket       = $request->bucket ? trim($request->bucket) : 'total';

        list($periodStart, $periodEnd, $daysInMonth) = $this->periodBounds($month, $year);
        $asOf = $this->resolveAsOf($periodEnd);

        list($whereExtra, $bindings) = $this->buildFilters($request);
        $bindings['periodStart']        = $periodStart;
        $bindings['periodEnd']          = $periodEnd;
        $bindings['asOf']               = $asOf;
        $bindings['floorDate']          = $this->floorDate;
        $bindings['pairRequiredBefore'] = $this->pairRequiredBefore;

        if ($supplierCode) {
            $whereExtra .= " AND ap_invoice.supplier_id = :detailSupplier ";
            $bindings['detailSupplier'] = $supplierCode;
        }

        $subquery    = $this->buildScheduleSubquery($whereExtra);
        $bucketWhere = $this->bucketWhere($bucket, $daysInMonth);

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
                hutang.balance_open,
                hutang.balance_asof
            FROM ($subquery) hutang
            LEFT JOIN third_party ON third_party.kode = hutang.supplier_id
            WHERE hutang.jatuh_tempo_actual <= to_date(:periodEnd,'DD-MM-YYYY')
            $bucketWhere
            ORDER BY hutang.jatuh_tempo_actual ASC, hutang.ap_number ASC
        ";

        $rows = DB::select($sql, $bindings);

        $result = [];
        foreach ($rows as $r) {
            $balance = $bucket === 'outstanding' ? (float) $r->balance_asof : (float) $r->balance_open;
            if ($balance <= $this->minOutstanding) {
                continue;
            }
            $result[] = [
                'ap_number'     => $r->ap_number,
                'inv_number'    => $r->inv_number,
                'supplier_name' => $r->supplier_name,
                'ap_date'       => $r->ap_date ?: '-',
                'inv_date'      => $r->inv_date ?: '-',
                'term'          => $r->term !== null ? ((int) $r->term . ' hari') : '-',
                'jatuh_tempo'   => $r->jatuh_tempo_actual ? date('d-m-Y', strtotime($r->jatuh_tempo_actual)) : '-',
                'balance'       => $balance,
                'ap_link'       => route('accountPayable.show', ['id' => Crypt::encryptString($r->ap_id)]),
            ];
        }

        return response()->json([
            'status'      => 1,
            'bucketLabel' => $this->bucketLabel($bucket),
            'rows'        => $result,
            'total'       => array_sum(array_column($result, 'balance')),
        ]);
    }

    public function export(Request $request)
    {
        $s = $this->computeSchedule($request);

        return Excel::download(
            new ApPaymentScheduleExport($s['rows'], $s['grand'], $s['daysInMonth'], $s['periodStart']),
            'AP_Payment_Schedule_' . sprintf('%02d%04d', $s['month'], $s['year']) . '.xlsx'
        );
    }
}
