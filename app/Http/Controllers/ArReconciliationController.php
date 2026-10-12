<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Exports\ArReconciliationExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use DB;
use PDF;

/*
    ================================================================
    AR RECONCILIATION (Rekonsiliasi Piutang)
    ================================================================
    Rollforward piutang per bulan dalam satu tahun:
        Saldo Awal -> + Penambah (Delivery/Invoice, Debit Note)
                    -> - Pengurang (Bank/Kas, digabung)
                    -> Balance (saldo akhir bulan)

    Filter "basis" menentukan dasar nilai & tanggal Penambah utama:
      - 'delivery' : nilai = invoice_det.qty*price, tanggal = delivery_hdr.delivery_date
      - 'invoice'  : nilai = invoice_hdr.dpp,        tanggal = invoice_hdr.invoice_date
    Debit Note selalu dihitung dari dn_date (tidak ada basis Delivery untuknya).
    Pengurang = kas_det.credit (voucher APPROVED, status='3') yang reference-nya
    cocok ke invoice_number atau dn_number -- Bank (BM/BK) & Kas (KM/KK) digabung
    jadi satu kolom, sama seperti pola balance invoice yang sudah established
    di ArAgingReportController (tanpa filter voucher_type).
    ================================================================
*/

class ArReconciliationController extends Controller
{
    private $title = "AR Reconciliation";
    private $floorYear = 2023;

    public function index(Request $request)
    {
        $data['title'] = $this->title;
        $data['years'] = range($this->floorYear, (int) date('Y') + 1);
        return view('arReconciliation.index', $data);
    }

    private function additionSql($basis)
    {
        if ($basis === 'delivery') {
            return "
                SELECT to_char(to_date(delivery_hdr.delivery_date,'DD-MM-YYYY'),'YYYY-MM') as ym,
                       SUM(invoice_det.qty * invoice_det.price) as amount
                FROM invoice_det
                JOIN invoice_hdr ON invoice_hdr.invoice_number = invoice_det.invoice_number
                JOIN delivery_hdr ON delivery_hdr.delivery_number = invoice_det.dn_number
                WHERE invoice_hdr.status NOT IN ('1','5')
                  AND to_date(delivery_hdr.delivery_date,'DD-MM-YYYY') BETWEEN to_date(:from,'DD-MM-YYYY') AND to_date(:to,'DD-MM-YYYY')
                GROUP BY 1
            ";
        }
        return "
            SELECT to_char(to_date(invoice_hdr.invoice_date,'DD-MM-YYYY'),'YYYY-MM') as ym,
                   SUM(invoice_hdr.dpp) as amount
            FROM invoice_hdr
            WHERE invoice_hdr.status NOT IN ('1','5')
              AND to_date(invoice_hdr.invoice_date,'DD-MM-YYYY') BETWEEN to_date(:from,'DD-MM-YYYY') AND to_date(:to,'DD-MM-YYYY')
            GROUP BY 1
        ";
    }

    private function debitNoteSql()
    {
        return "
            SELECT to_char(to_date(dn_date,'DD-MM-YYYY'),'YYYY-MM') as ym,
                   SUM(dpp) as amount
            FROM debit_note_hdr
            WHERE status NOT IN ('1','5')
              AND to_date(dn_date,'DD-MM-YYYY') BETWEEN to_date(:from,'DD-MM-YYYY') AND to_date(:to,'DD-MM-YYYY')
            GROUP BY 1
        ";
    }

    private function paymentSql()
    {
        return "
            SELECT to_char(to_date(kas_hdr.voucher_date,'DD-MM-YYYY'),'YYYY-MM') as ym,
                   SUM(kas_det.credit) as amount
            FROM kas_det
            JOIN kas_hdr ON kas_hdr.voucher_number = kas_det.voucher_number
            WHERE kas_hdr.status = '3'
              AND to_date(kas_hdr.voucher_date,'DD-MM-YYYY') BETWEEN to_date(:from,'DD-MM-YYYY') AND to_date(:to,'DD-MM-YYYY')
              AND (
                    EXISTS (SELECT 1 FROM invoice_hdr WHERE invoice_hdr.invoice_number = kas_det.reference)
                 OR EXISTS (SELECT 1 FROM debit_note_hdr WHERE debit_note_hdr.dn_number = kas_det.reference)
              )
            GROUP BY 1
        ";
    }

    /** Total satu sisi (addition/debitnote/payment) sebelum tanggal :to (exclusive), untuk Saldo Awal. */
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
        $dnSql       = $this->debitNoteSql();
        $paySql      = $this->paymentSql();

        $byYm = function ($rows) {
            $out = [];
            foreach ($rows as $r) {
                $out[$r->ym] = (float) $r->amount;
            }
            return $out;
        };

        $addition = $byYm(DB::select($additionSql, ['from' => $yearStart, 'to' => $yearEnd]));
        $dn       = $byYm(DB::select($dnSql, ['from' => $yearStart, 'to' => $yearEnd]));
        $payment  = $byYm(DB::select($paySql, ['from' => $yearStart, 'to' => $yearEnd]));

        $openingBalance = $this->sumBefore($additionSql, $yearStart)
            + $this->sumBefore($dnSql, $yearStart)
            - $this->sumBefore($paySql, $yearStart);

        $rows = [];
        $balance = $openingBalance;
        $totals = ['dpp' => 0, 'addition' => 0, 'debitNote' => 0, 'payment' => 0];

        for ($m = 1; $m <= 12; $m++) {
            $ym = sprintf('%04d-%02d', $year, $m);
            $add = $addition[$ym] ?? 0;
            $dnv = $dn[$ym] ?? 0;
            $pay = $payment[$ym] ?? 0;
            $balance += $add + $dnv - $pay;

            $rows[] = [
                'ym'        => $ym,
                'month'     => $m,
                'label'     => date('F Y', mktime(0, 0, 0, $m, 1, $year)),
                'dpp'       => $add + $dnv,
                'addition'  => $add,
                'debitNote' => $dnv,
                'payment'   => $pay,
                'balance'   => $balance,
            ];

            $totals['dpp']       += $add + $dnv;
            $totals['addition']  += $add;
            $totals['debitNote'] += $dnv;
            $totals['payment']   += $pay;
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
        $basis = $request->basis === 'delivery' ? 'delivery' : 'invoice';

        return response()->json(['status' => 1] + $this->buildReport($year, $basis));
    }

    /**
     * Detail dokumen di balik satu angka (cell) hasil klik -- month null berarti
     * Saldo Awal (semua dokumen sebelum 1 Jan tahun terpilih).
     */
    public function detail(Request $request)
    {
        $year   = (int) ($request->year ?: date('Y'));
        $basis  = $request->basis === 'delivery' ? 'delivery' : 'invoice';
        $month  = $request->month !== null && $request->month !== '' ? (int) $request->month : null;
        $column = $request->column; // 'addition' | 'debitNote' | 'payment'

        if ($month) {
            $from = sprintf('01-%02d-%04d', $month, $year);
            $to   = date('t-m-Y', mktime(0, 0, 0, $month, 1, $year));
            $bindings = ['from' => $from, 'to' => $to];
            $dateCond = "BETWEEN to_date(:from,'DD-MM-YYYY') AND to_date(:to,'DD-MM-YYYY')";
        } else {
            $bindings = ['to' => "01-01-$year"];
            $dateCond = "< to_date(:to,'DD-MM-YYYY')";
        }

        if ($column === 'debitNote') {
            $sql = "
                SELECT id, dn_number as doc, dn_date as doc_date, dpp as amount, 'Debit Note' as doc_type
                FROM debit_note_hdr
                WHERE status NOT IN ('1','5') AND to_date(dn_date,'DD-MM-YYYY') $dateCond
                ORDER BY to_date(dn_date,'DD-MM-YYYY')
            ";
        } elseif ($column === 'payment') {
            $sql = "
                SELECT kas_hdr.id, kas_hdr.voucher_number as doc, kas_hdr.voucher_date as doc_date, kas_det.credit as amount,
                       kas_hdr.voucher_type as doc_type, kas_det.reference as reference
                FROM kas_det
                JOIN kas_hdr ON kas_hdr.voucher_number = kas_det.voucher_number
                WHERE kas_hdr.status = '3'
                  AND to_date(kas_hdr.voucher_date,'DD-MM-YYYY') $dateCond
                  AND (
                        EXISTS (SELECT 1 FROM invoice_hdr WHERE invoice_hdr.invoice_number = kas_det.reference)
                     OR EXISTS (SELECT 1 FROM debit_note_hdr WHERE debit_note_hdr.dn_number = kas_det.reference)
                  )
                ORDER BY to_date(kas_hdr.voucher_date,'DD-MM-YYYY')
            ";
        } elseif ($basis === 'delivery') {
            $sql = "
                SELECT delivery_hdr.id, delivery_hdr.delivery_number as doc, delivery_hdr.delivery_date as doc_date,
                       SUM(invoice_det.qty * invoice_det.price) as amount, 'Delivery Note' as doc_type
                FROM invoice_det
                JOIN invoice_hdr ON invoice_hdr.invoice_number = invoice_det.invoice_number
                JOIN delivery_hdr ON delivery_hdr.delivery_number = invoice_det.dn_number
                WHERE invoice_hdr.status NOT IN ('1','5')
                  AND to_date(delivery_hdr.delivery_date,'DD-MM-YYYY') $dateCond
                GROUP BY delivery_hdr.id, delivery_hdr.delivery_number, delivery_hdr.delivery_date
                ORDER BY to_date(delivery_hdr.delivery_date,'DD-MM-YYYY')
            ";
        } else {
            $sql = "
                SELECT id, invoice_number as doc, invoice_date as doc_date, dpp as amount, 'AR Invoice' as doc_type
                FROM invoice_hdr
                WHERE status NOT IN ('1','5') AND to_date(invoice_date,'DD-MM-YYYY') $dateCond
                ORDER BY to_date(invoice_date,'DD-MM-YYYY')
            ";
        }

        $rows = DB::select($sql, $bindings);

        $kasEditRoute = [
            'BM' => 'bankPenerimaan.edit',
            'BK' => 'bankKeluar.edit',
            'KM' => 'kasPenerimaan.edit',
            'KK' => 'kasKeluar.edit',
        ];

        $result = [];
        $total = 0;
        foreach ($rows as $r) {
            $encId = \Crypt::encryptString($r->id);
            if ($r->doc_type === 'Delivery Note') {
                $link = route('delivery.show', ['id' => $encId]);
            } elseif ($r->doc_type === 'Debit Note') {
                $link = route('debitNote.edit', ['id' => $encId]);
            } elseif ($r->doc_type === 'AR Invoice') {
                $link = route('invoice.edit', ['id' => $encId]);
            } elseif (isset($kasEditRoute[$r->doc_type])) {
                $link = route($kasEditRoute[$r->doc_type], ['id' => $encId]);
            } else {
                $link = null;
            }

            $result[] = [
                'doc'      => $r->doc,
                'doc_date' => $r->doc_date,
                'doc_type' => $r->doc_type,
                'reference'=> $r->reference ?? null,
                'amount'   => (float) $r->amount,
                'link'     => $link,
            ];
            $total += (float) $r->amount;
        }

        return response()->json(['status' => 1, 'rows' => $result, 'total' => $total]);
    }

    public function export(Request $request)
    {
        $year  = (int) ($request->year ?: date('Y'));
        $basis = $request->basis === 'delivery' ? 'delivery' : 'invoice';
        $s = $this->buildReport($year, $basis);

        return Excel::download(
            new ArReconciliationExport($s['rows'], $s['totals'], $s['openingBalance'], $s['closingBalance'], $year, $basis),
            'AR_Reconciliation_' . $year . '.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        $year  = (int) ($request->year ?: date('Y'));
        $basis = $request->basis === 'delivery' ? 'delivery' : 'invoice';
        $s = $this->buildReport($year, $basis);

        $pdf = PDF::loadView('arReconciliation.print', [
            'rows'           => $s['rows'],
            'totals'         => $s['totals'],
            'openingBalance' => $s['openingBalance'],
            'closingBalance' => $s['closingBalance'],
            'year'           => $year,
            'basis'          => $basis,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('AR_Reconciliation_' . $year . '.pdf');
    }
}
