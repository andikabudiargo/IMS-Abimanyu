<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Exports\ApReconciliationExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use DB;
use PDF;

/*
    ================================================================
    AP RECONCILIATION (Rekonsiliasi Hutang)
    ================================================================
    Kembaran ArReconciliationController, sisi HUTANG. Rollforward per
    bulan: Saldo Awal -> + Penambah (Receiving/AP Invoice) -> - Pengurang
    (Bank/Kas digabung) -> Balance.

    Filter "basis":
      - 'receiving' : nilai = receiving_det.qty*price, tanggal = receiving_hdr.rec_date
      - 'ap'         : nilai = ap_invoice.basis_amount (DPP),
                        tanggal = COALESCE(ap_date, inv_date)
    Tidak ada kolom kedua seperti Debit Note di sisi AR -- AP cuma satu
    sumber Penambah.
    Pengurang pakai aturan match yang sama persis dengan
    ApAgingReportController::paymentMatchSql() (jangan diubah sendiri-
    sendiri, supaya kedua report selalu konsisten).
    ================================================================
*/

class ApReconciliationController extends Controller
{
    private $title = "AP Reconciliation";
    private $floorYear = 2023;

    public function index(Request $request)
    {
        $data['title'] = $this->title;
        $data['years'] = range($this->floorYear, (int) date('Y') + 1);
        return view('apReconciliation.index', $data);
    }

    private function additionSql($basis)
    {
        if ($basis === 'receiving') {
            return "
                SELECT to_char(to_date(receiving_hdr.rec_date,'DD-MM-YYYY'),'YYYY-MM') as ym,
                       SUM(receiving_det.qty * receiving_det.price) as amount
                FROM receiving_det
                JOIN receiving_hdr ON receiving_hdr.rec_number = receiving_det.rec_number
                WHERE receiving_hdr.status <> '5'
                  AND to_date(receiving_hdr.rec_date,'DD-MM-YYYY') BETWEEN to_date(:from,'DD-MM-YYYY') AND to_date(:to,'DD-MM-YYYY')
                GROUP BY 1
            ";
        }
        $anchor = "COALESCE(
                to_date(NULLIF(ap_invoice.ap_date,''),'DD-MM-YYYY'),
                to_date(NULLIF(ap_invoice.inv_date,''),'DD-MM-YYYY')
              )";
        return "
            SELECT to_char($anchor,'YYYY-MM') as ym,
                   SUM(ap_invoice.basis_amount) as amount
            FROM ap_invoice
            WHERE ap_invoice.status NOT IN ('1','5')
              AND $anchor BETWEEN to_date(:from,'DD-MM-YYYY') AND to_date(:to,'DD-MM-YYYY')
            GROUP BY 1
        ";
    }

    /** Sama persis dengan ApAgingReportController::paymentMatchSql(). */
    private function paymentMatchSql($hdr, $det)
    {
        return "(
            ($hdr.voucher_type IN ('KK','BK') AND $hdr.paid_to = ap_invoice.supplier_id)
            OR $hdr.voucher_type IN ('BM','KM')
            OR ($hdr.voucher_type = 'GJ'
                AND $det.account = (SELECT tp2.account FROM third_party tp2 WHERE tp2.kode = ap_invoice.supplier_id))
        )";
    }

    private function paymentSql()
    {
        $partyMatch = $this->paymentMatchSql('kas_hdr', 'kas_det');
        return "
            SELECT to_char(to_date(kas_hdr.voucher_date,'DD-MM-YYYY'),'YYYY-MM') as ym,
                   SUM(kas_det.debit) as amount
            FROM kas_det
            JOIN kas_hdr ON kas_hdr.voucher_number = kas_det.voucher_number
            JOIN ap_invoice ON ap_invoice.inv_number = kas_det.reference
            WHERE kas_hdr.status <> '5'
              AND ap_invoice.status NOT IN ('1','5')
              AND to_date(kas_hdr.voucher_date,'DD-MM-YYYY') BETWEEN to_date(:from,'DD-MM-YYYY') AND to_date(:to,'DD-MM-YYYY')
              AND $partyMatch
            GROUP BY 1
        ";
    }

    private function sumBefore($sql, $yearStart)
    {
        $rows = DB::select(str_replace(
            "BETWEEN to_date(:from,'DD-MM-YYYY') AND to_date(:to,'DD-MM-YYYY')",
            "< to_date(:to,'DD-MM-YYYY')",
            $sql
        ), [
            'to' => $yearStart,
        ]);
        $total = 0;
        foreach ($rows as $r) {
            $total += (float) $r->amount;
        }
        return $total;
    }

    private function buildReport($year, $basis)
    {
        $yearStart = "01-01-$year";
        $yearEnd   = "31-12-$year";

        $additionSql = $this->additionSql($basis);
        $paySql      = $this->paymentSql();

        $byYm = function ($rows) {
            $out = [];
            foreach ($rows as $r) {
                $out[$r->ym] = (float) $r->amount;
            }
            return $out;
        };

        $addition = $byYm(DB::select($additionSql, ['from' => $yearStart, 'to' => $yearEnd]));
        $payment  = $byYm(DB::select($paySql, ['from' => $yearStart, 'to' => $yearEnd]));

        $openingBalance = $this->sumBefore($additionSql, $yearStart)
            - $this->sumBefore($paySql, $yearStart);

        $rows = [];
        $balance = $openingBalance;
        $totals = ['dpp' => 0, 'addition' => 0, 'payment' => 0];

        for ($m = 1; $m <= 12; $m++) {
            $ym = sprintf('%04d-%02d', $year, $m);
            $add = $addition[$ym] ?? 0;
            $pay = $payment[$ym] ?? 0;
            $balance += $add - $pay;

            $rows[] = [
                'ym'       => $ym,
                'month'    => $m,
                'label'    => date('F Y', mktime(0, 0, 0, $m, 1, $year)),
                'dpp'      => $add,
                'addition' => $add,
                'payment'  => $pay,
                'balance'  => $balance,
            ];

            $totals['dpp']      += $add;
            $totals['addition'] += $add;
            $totals['payment']  += $pay;
        }

        return [
            'year'           => $year,
            'basis'          => $basis,
            'openingBalance' => $openingBalance,
            'rows'           => $rows,
            'totals'         => $totals,
            'closingBalance' => $balance,
        ];
    }

    public function data(Request $request)
    {
        $year  = (int) ($request->year ?: date('Y'));
        $basis = $request->basis === 'ap' ? 'ap' : 'receiving';

        return response()->json(['status' => 1] + $this->buildReport($year, $basis));
    }

    /**
     * Detail dokumen di balik satu angka (cell) hasil klik -- month null berarti
     * Saldo Awal (semua dokumen sebelum 1 Jan tahun terpilih).
     */
    public function detail(Request $request)
    {
        $year   = (int) ($request->year ?: date('Y'));
        $basis  = $request->basis === 'ap' ? 'ap' : 'receiving';
        $month  = $request->month !== null && $request->month !== '' ? (int) $request->month : null;
        $column = $request->column; // 'addition' | 'payment'

        if ($month) {
            $from = sprintf('01-%02d-%04d', $month, $year);
            $to   = date('t-m-Y', mktime(0, 0, 0, $month, 1, $year));
            $bindings = ['from' => $from, 'to' => $to];
            $dateCond = "BETWEEN to_date(:from,'DD-MM-YYYY') AND to_date(:to,'DD-MM-YYYY')";
        } else {
            $bindings = ['to' => "01-01-$year"];
            $dateCond = "< to_date(:to,'DD-MM-YYYY')";
        }

        if ($column === 'payment') {
            $partyMatch = $this->paymentMatchSql('kas_hdr', 'kas_det');
            $sql = "
                SELECT kas_hdr.voucher_number as doc, kas_hdr.voucher_date as doc_date, kas_det.debit as amount,
                       kas_hdr.voucher_type as doc_type, kas_det.reference as reference
                FROM kas_det
                JOIN kas_hdr ON kas_hdr.voucher_number = kas_det.voucher_number
                JOIN ap_invoice ON ap_invoice.inv_number = kas_det.reference
                WHERE kas_hdr.status <> '5'
                  AND ap_invoice.status NOT IN ('1','5')
                  AND to_date(kas_hdr.voucher_date,'DD-MM-YYYY') $dateCond
                  AND $partyMatch
                ORDER BY to_date(kas_hdr.voucher_date,'DD-MM-YYYY')
            ";
        } elseif ($basis === 'receiving') {
            $sql = "
                SELECT receiving_hdr.rec_number as doc, receiving_hdr.rec_date as doc_date,
                       SUM(receiving_det.qty * receiving_det.price) as amount, 'Receiving' as doc_type
                FROM receiving_det
                JOIN receiving_hdr ON receiving_hdr.rec_number = receiving_det.rec_number
                WHERE receiving_hdr.status <> '5'
                  AND to_date(receiving_hdr.rec_date,'DD-MM-YYYY') $dateCond
                GROUP BY receiving_hdr.rec_number, receiving_hdr.rec_date
                ORDER BY to_date(receiving_hdr.rec_date,'DD-MM-YYYY')
            ";
        } else {
            $anchor = "COALESCE(
                    to_date(NULLIF(ap_invoice.ap_date,''),'DD-MM-YYYY'),
                    to_date(NULLIF(ap_invoice.inv_date,''),'DD-MM-YYYY')
                  )";
            $sql = "
                SELECT ap_invoice.inv_number as doc, to_char($anchor,'DD-MM-YYYY') as doc_date,
                       ap_invoice.basis_amount as amount, 'AP Invoice' as doc_type
                FROM ap_invoice
                WHERE ap_invoice.status NOT IN ('1','5') AND $anchor $dateCond
                ORDER BY $anchor
            ";
        }

        $rows = DB::select($sql, $bindings);

        $result = [];
        $total = 0;
        foreach ($rows as $r) {
            $result[] = [
                'doc'       => $r->doc,
                'doc_date'  => $r->doc_date,
                'doc_type'  => $r->doc_type,
                'reference' => $r->reference ?? null,
                'amount'    => (float) $r->amount,
            ];
            $total += (float) $r->amount;
        }

        return response()->json(['status' => 1, 'rows' => $result, 'total' => $total]);
    }

    public function export(Request $request)
    {
        $year  = (int) ($request->year ?: date('Y'));
        $basis = $request->basis === 'ap' ? 'ap' : 'receiving';
        $s = $this->buildReport($year, $basis);

        return Excel::download(
            new ApReconciliationExport($s['rows'], $s['totals'], $s['openingBalance'], $s['closingBalance'], $year, $basis),
            'AP_Reconciliation_' . $year . '.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        $year  = (int) ($request->year ?: date('Y'));
        $basis = $request->basis === 'ap' ? 'ap' : 'receiving';
        $s = $this->buildReport($year, $basis);

        $pdf = PDF::loadView('apReconciliation.print', [
            'rows'           => $s['rows'],
            'totals'         => $s['totals'],
            'openingBalance' => $s['openingBalance'],
            'closingBalance' => $s['closingBalance'],
            'year'           => $year,
            'basis'          => $basis,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('AP_Reconciliation_' . $year . '.pdf');
    }
}
