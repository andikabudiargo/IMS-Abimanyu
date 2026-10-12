<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

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
                JOIN delivery_hdr ON delivery_hdr.delivery_number = invoice_det.delivery_number
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

    public function data(Request $request)
    {
        $year  = (int) ($request->year ?: date('Y'));
        $basis = $request->basis === 'delivery' ? 'delivery' : 'invoice';

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

        return response()->json([
            'status'         => 1,
            'basis'          => $basis,
            'openingBalance' => $openingBalance,
            'rows'           => $rows,
            'totals'         => $totals,
            'closingBalance' => $balance,
        ]);
    }
}
