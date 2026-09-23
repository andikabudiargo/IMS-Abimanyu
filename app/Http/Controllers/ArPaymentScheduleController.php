<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ArPaymentScheduleExport;
use DB;

/*
    ================================================================
    AR PAYMENT SCHEDULE
    ================================================================
    Filter utama: PERIODE (bulan & tahun). Untuk periode itu, tiap
    customer di-pecah jadi:
      - Opening    : saldo invoice yang jatuh tempo SEBELUM awal
                     periode (masih outstanding di awal bulan).
      - Kolom 1..N : invoice yang jatuh tempo PADA tanggal itu, di
                     dalam periode terpilih (jadwal pembayaran).
      - Total      : Opening + seluruh kolom tanggal.
      - Paid       : uang yang benar-benar masuk (kas_det/kas_hdr)
                     selama periode tsb.
      - Balance    : Total - Paid.
      - Outstanding: bagian dari Total yang jatuh tempo-nya sudah
                     lewat (dibandingkan hari ini / akhir periode)
                     dan masih belum lunas -- ini angka yang
                     "harusnya udah dibayar tapi belum".

    Jatuh tempo & balance pakai rumus yang PERSIS SAMA dengan
    ArAgingReportController (lihat buildScheduleSubquery) supaya
    kedua laporan selalu konsisten satu sama lain:
      - jatuh_tempo = invoice_hdr.jatuh_tempo, fallback ke
        sending_date + third_party.top_batas_1 hari.
      - balance = grand_total - SUM(kas_det.credit) yang sudah
        APPROVED (kas_hdr.status='3'), tanpa filter voucher_type.
      - invoice DRAFT('1') & CANCELED('5') di-exclude.
    ================================================================
*/

class ArPaymentScheduleController extends Controller
{
    private $title = "AR Payment Schedule";

    private $floorDate = '01-01-2023';
    private $pairRequiredBefore = '01-01-2024';

    public function index(Request $request)
    {
        $data['title'] = $this->title;

        $data['customers'] = DB::table('third_party')
            ->where('third_party_type', '=', 'cust')
            ->orderBy('nama')
            ->get();

        return view('arPaymentSchedule.index', $data);
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

    // Snapshot cut-off untuk kolom Paid/Outstanding: akhir periode, atau
    // hari ini kalau periode yang dipilih belum selesai (sedang berjalan
    // atau di masa depan) -- supaya tidak "membaca" pembayaran yang belum
    // terjadi.
    private function resolveAsOf($periodEnd)
    {
        $periodEndDt = \DateTime::createFromFormat('d-m-Y', $periodEnd);
        $today = new \DateTime();
        return $periodEndDt < $today ? $periodEnd : $today->format('d-m-Y');
    }

    private function buildFilters(Request $request)
    {
        $customerCodes = $request->customer;

        $whereExtra = "";
        if ($customerCodes && is_array($customerCodes) && count($customerCodes) > 0) {
            $escaped = array_map(function ($c) {
                return "'" . str_replace("'", "''", $c) . "'";
            }, $customerCodes);
            $whereExtra .= " AND invoice_hdr.customer_id IN (" . implode(',', $escaped) . ") ";
        }

        return [$whereExtra, []];
    }

    /**
     * Subquery per-invoice: due date sama persis dengan
     * ArAgingReportController::buildPiutangSubquery, tapi balance
     * dipecah jadi 2 titik waktu supaya bisa dihitung Opening/Paid/
     * Outstanding untuk satu periode (bukan cuma satu cut-off):
     *   - balance_open  : saldo di AWAL periode (sebelum :periodStart).
     *   - paid_in_period: yang dibayar DI DALAM periode (:periodStart..:asOf).
     *   - balance_asof  : saldo saat ini (= balance_open - paid_in_period).
     */
    private function buildScheduleSubquery($whereExtra)
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
                (invoice_hdr.grand_total - COALESCE(pb.paid,0)) as balance_open,
                COALESCE(pp.paid,0) as paid_in_period,
                (invoice_hdr.grand_total - COALESCE(pb.paid,0) - COALESCE(pp.paid,0)) as balance_asof
            FROM invoice_hdr
            LEFT JOIN LATERAL (
                SELECT SUM(kas_det.credit) as paid
                FROM kas_det
                LEFT JOIN kas_hdr ON kas_det.voucher_number = kas_hdr.voucher_number
                WHERE kas_det.reference = invoice_hdr.invoice_number
                  AND kas_hdr.status = '3'
                  AND to_date(kas_hdr.voucher_date,'DD-MM-YYYY') < to_date(:periodStart,'DD-MM-YYYY')
            ) pb ON true
            LEFT JOIN LATERAL (
                SELECT SUM(kas_det.credit) as paid
                FROM kas_det
                LEFT JOIN kas_hdr ON kas_det.voucher_number = kas_hdr.voucher_number
                WHERE kas_det.reference = invoice_hdr.invoice_number
                  AND kas_hdr.status = '3'
                  AND to_date(kas_hdr.voucher_date,'DD-MM-YYYY') >= to_date(:periodStart,'DD-MM-YYYY')
                  AND to_date(kas_hdr.voucher_date,'DD-MM-YYYY') <= to_date(:asOf,'DD-MM-YYYY')
            ) pp ON true
            WHERE invoice_hdr.status NOT IN ('1','5')
              AND to_date(invoice_hdr.invoice_date,'DD-MM-YYYY') >= to_date(:floorDate,'DD-MM-YYYY')
              AND to_date(invoice_hdr.invoice_date,'DD-MM-YYYY') <= to_date(:asOf,'DD-MM-YYYY')
              AND (
                    to_date(invoice_hdr.invoice_date,'DD-MM-YYYY') >= to_date(:pairRequiredBefore,'DD-MM-YYYY')
                    OR EXISTS (SELECT 1 FROM kas_det WHERE kas_det.reference = invoice_hdr.invoice_number)
                  )
              $whereExtra
        ";
    }

    private function bucketWhere($bucket, $daysInMonth)
    {
        if ($bucket === 'opening') {
            return " AND piutang.jatuh_tempo_actual < to_date(:periodStart,'DD-MM-YYYY') ";
        }
        if ($bucket === 'outstanding') {
            return " AND piutang.jatuh_tempo_actual <= to_date(:asOf,'DD-MM-YYYY') AND piutang.balance_asof > 0.01 ";
        }
        if (preg_match('/^d(\d+)$/', (string) $bucket, $m)) {
            $day = (int) $m[1];
            if ($day >= 1 && $day <= $daysInMonth) {
                return " AND piutang.jatuh_tempo_actual = (to_date(:periodStart,'DD-MM-YYYY') + " . ($day - 1) . ")::date ";
            }
        }
        return ""; // 'total' (atau bucket tak dikenal) -> tanpa filter tambahan
    }

    private function bucketLabel($bucket)
    {
        if ($bucket === 'opening') return 'Opening Balance';
        if ($bucket === 'total') return 'Total';
        if ($bucket === 'outstanding') return 'Outstanding (Sudah Lewat Jatuh Tempo)';
        if (preg_match('/^d(\d+)$/', (string) $bucket, $m)) return 'Jatuh Tempo Tanggal ' . $m[1];
        return $bucket;
    }

    /**
     * Bangun grid Opening/1..N/Total/Paid/Balance/Outstanding per customer
     * untuk satu periode. Dipakai bersama oleh data() & export() supaya
     * angka di layar & di file Excel selalu sama.
     */
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
            $cond = "piutang.jatuh_tempo_actual = (to_date(:periodStart,'DD-MM-YYYY') + " . ($d - 1) . ")::date";
            $dayCases[] = "SUM(CASE WHEN $cond THEN piutang.balance_open ELSE 0 END) as d$d";
            // sisa (balance_asof) per tanggal -> dipakai frontend buat tandai sel hijau kalau lunas
            $dayCases[] = "SUM(CASE WHEN $cond THEN piutang.balance_asof ELSE 0 END) as r$d";
        }

        $sql = "
            SELECT
                piutang.customer_id as customer_code,
                third_party.nama    as customer_name,
                SUM(CASE WHEN piutang.jatuh_tempo_actual < to_date(:periodStart,'DD-MM-YYYY') THEN piutang.balance_open ELSE 0 END) as opening,
                SUM(CASE WHEN piutang.jatuh_tempo_actual < to_date(:periodStart,'DD-MM-YYYY') THEN piutang.balance_asof ELSE 0 END) as opening_r,
                " . implode(",\n                ", $dayCases) . ",
                SUM(piutang.balance_open) as total,
                SUM(piutang.paid_in_period) as paid,
                SUM(CASE WHEN piutang.jatuh_tempo_actual <= to_date(:asOf,'DD-MM-YYYY') AND piutang.balance_asof > 0.01 THEN piutang.balance_asof ELSE 0 END) as outstanding
            FROM ($subquery) piutang
            LEFT JOIN third_party ON third_party.kode = piutang.customer_id
            WHERE piutang.jatuh_tempo_actual <= to_date(:periodEnd,'DD-MM-YYYY')
            GROUP BY piutang.customer_id, third_party.nama
            HAVING SUM(piutang.balance_open) > 0.01
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
                'customer_code' => $r->customer_code,
                'customer_name' => $r->customer_name,
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
        $customerCode = $request->customerCode ? trim($request->customerCode) : null;
        $bucket       = $request->bucket ? trim($request->bucket) : 'total';

        list($periodStart, $periodEnd, $daysInMonth) = $this->periodBounds($month, $year);
        $asOf = $this->resolveAsOf($periodEnd);

        list($whereExtra, $bindings) = $this->buildFilters($request);
        $bindings['periodStart']        = $periodStart;
        $bindings['periodEnd']          = $periodEnd;
        $bindings['asOf']               = $asOf;
        $bindings['floorDate']          = $this->floorDate;
        $bindings['pairRequiredBefore'] = $this->pairRequiredBefore;

        if ($customerCode) {
            $whereExtra .= " AND invoice_hdr.customer_id = :detailCustomer ";
            $bindings['detailCustomer'] = $customerCode;
        }

        $subquery    = $this->buildScheduleSubquery($whereExtra);
        $bucketWhere = $this->bucketWhere($bucket, $daysInMonth);

        $sql = "
            SELECT
                piutang.invoice_id,
                piutang.invoice_number,
                third_party.nama as customer_name,
                piutang.invoice_date,
                piutang.sending_date,
                piutang.term,
                piutang.jatuh_tempo_actual,
                piutang.balance_open,
                piutang.balance_asof
            FROM ($subquery) piutang
            LEFT JOIN third_party ON third_party.kode = piutang.customer_id
            WHERE piutang.jatuh_tempo_actual <= to_date(:periodEnd,'DD-MM-YYYY')
            $bucketWhere
            ORDER BY piutang.jatuh_tempo_actual ASC, piutang.invoice_number ASC
        ";

        $rows = DB::select($sql, $bindings);

        $result = [];
        foreach ($rows as $r) {
            $balance = $bucket === 'outstanding' ? (float) $r->balance_asof : (float) $r->balance_open;
            if ($balance <= 0.01) {
                continue;
            }
            $result[] = [
                'invoice_number' => $r->invoice_number,
                'customer_name'  => $r->customer_name,
                'invoice_date'   => $r->invoice_date ?: '-',
                'sending_date'   => $r->sending_date ?: '-',
                'term'           => $r->term !== null ? ((int) $r->term . ' hari') : '-',
                'jatuh_tempo'    => $r->jatuh_tempo_actual ? date('d-m-Y', strtotime($r->jatuh_tempo_actual)) : '-',
                'balance'        => $balance,
                'invoice_link'   => route('invoice.show', ['id' => Crypt::encryptString($r->invoice_id)]),
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
            new ArPaymentScheduleExport($s['rows'], $s['grand'], $s['daysInMonth'], $s['periodStart']),
            'AR_Payment_Schedule_' . sprintf('%02d%04d', $s['month'], $s['year']) . '.xlsx'
        );
    }
}
