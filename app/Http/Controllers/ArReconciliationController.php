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

    Filter "basis" menentukan tanggal Penambah utama (delivery_date / invoice_date).
    Ada DUA kolom nilai, sengaja dipisah:
      - "DPP"      : murni sebelum pajak (invoice_hdr.dpp / debit_note_hdr.dpp,
                     delivery basis pakai qty*price karena tidak ada pecahan pajak) --
                     cuma referensi, TIDAK dipakai untuk Balance.
      - "Penambah" (AR Invoice/Delivery Note + Debit Note) : TOTAL termasuk pajak
                     (invoice_hdr.grand_total / debit_note_hdr.grand_total) -- ini yang
                     dipakai untuk Balance, karena Opening Balance (lihat bawah) juga
                     grand_total-based.
    Debit Note hanya dihitung untuk basis 'invoice' -- basis 'delivery' murni melacak
    nilai barang lewat Surat Jalan, bukan siklus AR Invoice/Debit Note, jadi Debit Note
    disembunyikan di basis itu.
    Pengurang = kas_det.credit (voucher APPROVED, status='3') yang reference-nya
    cocok ke invoice_number atau dn_number -- Bank (BM/BK) & Kas (KM/KK) digabung
    jadi satu kolom, sama seperti pola balance invoice yang sudah established
    di ArAgingReportController (tanpa filter voucher_type).
    Saldo Awal dihitung lewat ArAgingReportController::totalOutstanding() --
    rumus yang SAMA dipakai di card "Opening Balance" halaman Invoice Customer
    (InvoiceController::analyticsAr) -- supaya kedua angka itu selalu cocok.
    Status dokumen: DRAFT (status '1') ikut dihitung (cuma status '5'/CANCEL yang
    disingkirkan), sama seperti InvoiceController::analyticsAr -- supaya Sales &
    Balance di sini selalu cocok dengan card Sales/Balance di AR Dashboard.
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

    /** Nilai DPP murni (kolom "DPP", referensi sebelum pajak) -- delivery basis tidak punya
     *  pecahan pajak sendiri jadi qty*(price material + price service) dipakai sebagai nilai
     *  DPP-nya, SAMA dengan invoice_hdr.dpp (lihat InvoiceController addArticle.blade.php
     *  totalAmount = qty*price + qty*priceJasa) -- kalau cuma qty*price, DPP delivery basis
     *  bakal lebih kecil dari DPP invoice basis sebesar nilai jasanya. */
    private function additionDppSql($basis)
    {
        if ($basis === 'delivery') {
            return "
                SELECT to_char(to_date(delivery_hdr.delivery_date,'DD-MM-YYYY'),'YYYY-MM') as ym,
                       SUM(invoice_det.qty * (invoice_det.price + invoice_det.price_service)) as amount
                FROM invoice_det
                JOIN invoice_hdr ON invoice_hdr.invoice_number = invoice_det.invoice_number
                JOIN delivery_hdr ON delivery_hdr.delivery_number = invoice_det.dn_number
                WHERE invoice_hdr.status <> '5'
                  AND to_date(delivery_hdr.delivery_date,'DD-MM-YYYY') BETWEEN to_date(:from,'DD-MM-YYYY') AND to_date(:to,'DD-MM-YYYY')
                GROUP BY 1
            ";
        }
        return "
            SELECT to_char(to_date(invoice_hdr.invoice_date,'DD-MM-YYYY'),'YYYY-MM') as ym,
                   SUM(invoice_hdr.dpp) as amount
            FROM invoice_hdr
            WHERE invoice_hdr.status <> '5'
              AND to_date(invoice_hdr.invoice_date,'DD-MM-YYYY') BETWEEN to_date(:from,'DD-MM-YYYY') AND to_date(:to,'DD-MM-YYYY')
            GROUP BY 1
        ";
    }

    /** Nilai Total (kolom "Penambah", termasuk pajak) -- yang dipakai untuk Balance,
     *  supaya konsisten dengan Opening Balance yang juga grand_total-based.
     *  Basis 'delivery': PPN cuma tersimpan di level invoice_hdr (bukan per SJ), jadi
     *  di-prorate ke tiap SJ sesuai porsi nilainya -- (qty*(price+price_service)) *
     *  (grand_total/dpp) -- sama seperti alokasi landed cost/discount per item di ERP,
     *  supaya SUM(Penambah) tetap persis sama dengan SUM(grand_total) invoice terkait
     *  (dan Balance basis Delivery konsisten dgn basis Invoice/AR Dashboard).
     *  Catatan: dpp pembagi dihitung ulang dari invoice_det (SUM per invoice_number),
     *  BUKAN dibaca dari invoice_hdr.dpp -- kolom itu snapshot sekali pas invoice
     *  dibuat dan bisa basi/0 kalau invoice-nya diedit belakangan, yang bikin rasio
     *  jatuh ke fallback 1 (PPN hilang dari Penambah). Dihitung dari sumber yang sama
     *  dengan pembilang supaya selalu konsisten. */
    private function additionTotalSql($basis)
    {
        if ($basis === 'delivery') {
            return "
                SELECT to_char(to_date(delivery_hdr.delivery_date,'DD-MM-YYYY'),'YYYY-MM') as ym,
                       SUM(
                            (invoice_det.qty * (invoice_det.price + invoice_det.price_service))
                            * CASE WHEN inv_dpp.dpp > 0 THEN invoice_hdr.grand_total / inv_dpp.dpp ELSE 1 END
                       ) as amount
                FROM invoice_det
                JOIN invoice_hdr ON invoice_hdr.invoice_number = invoice_det.invoice_number
                JOIN delivery_hdr ON delivery_hdr.delivery_number = invoice_det.dn_number
                JOIN (
                    SELECT invoice_number, SUM(qty * (price + price_service)) as dpp
                    FROM invoice_det
                    GROUP BY invoice_number
                ) inv_dpp ON inv_dpp.invoice_number = invoice_det.invoice_number
                WHERE invoice_hdr.status <> '5'
                  AND to_date(delivery_hdr.delivery_date,'DD-MM-YYYY') BETWEEN to_date(:from,'DD-MM-YYYY') AND to_date(:to,'DD-MM-YYYY')
                GROUP BY 1
            ";
        }
        return "
            SELECT to_char(to_date(invoice_hdr.invoice_date,'DD-MM-YYYY'),'YYYY-MM') as ym,
                   SUM(invoice_hdr.grand_total) as amount
            FROM invoice_hdr
            WHERE invoice_hdr.status <> '5'
              AND to_date(invoice_hdr.invoice_date,'DD-MM-YYYY') BETWEEN to_date(:from,'DD-MM-YYYY') AND to_date(:to,'DD-MM-YYYY')
            GROUP BY 1
        ";
    }

    private function debitNoteDppSql()
    {
        return "
            SELECT to_char(to_date(dn_date,'DD-MM-YYYY'),'YYYY-MM') as ym,
                   SUM(dpp) as amount
            FROM debit_note_hdr
            WHERE status <> '5'
              AND to_date(dn_date,'DD-MM-YYYY') BETWEEN to_date(:from,'DD-MM-YYYY') AND to_date(:to,'DD-MM-YYYY')
            GROUP BY 1
        ";
    }

    private function debitNoteTotalSql()
    {
        return "
            SELECT to_char(to_date(dn_date,'DD-MM-YYYY'),'YYYY-MM') as ym,
                   SUM(grand_total) as amount
            FROM debit_note_hdr
            WHERE status <> '5'
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

    private function buildReport($year, $basis)
    {
        $yearStart = "01-01-$year";
        $yearEnd   = "31-12-$year";
        // Delivery Note/SJ basis tracks item value through delivery, bukan siklus invoice
        // AR -> Debit Note yang dibukukan lewat account_piutang, jadi Debit Note di-skip.
        $showDebitNote = $basis !== 'delivery';

        $paySql = $this->paymentSql();

        $byYm = function ($rows) {
            $out = [];
            foreach ($rows as $r) {
                $out[$r->ym] = (float) $r->amount;
            }
            return $out;
        };

        $additionDpp   = $byYm(DB::select($this->additionDppSql($basis), ['from' => $yearStart, 'to' => $yearEnd]));
        $additionTotal = $byYm(DB::select($this->additionTotalSql($basis), ['from' => $yearStart, 'to' => $yearEnd]));
        $dnDpp         = $showDebitNote ? $byYm(DB::select($this->debitNoteDppSql(), ['from' => $yearStart, 'to' => $yearEnd])) : [];
        $dnTotal       = $showDebitNote ? $byYm(DB::select($this->debitNoteTotalSql(), ['from' => $yearStart, 'to' => $yearEnd])) : [];
        $payment       = $byYm(DB::select($paySql, ['from' => $yearStart, 'to' => $yearEnd]));

        // Pakai rumus outstanding yang SAMA dengan card "Opening Balance" di halaman Invoice
        // Customer (InvoiceController::analyticsAr -> ArAgingReportController::totalOutstanding),
        // supaya dua angka ini selalu cocok alih-alih dihitung ulang dengan query berbeda.
        $dayBeforeYearStart = date('d-m-Y', strtotime("$year-01-01 -1 day"));
        $openingBalance = (new ArAgingReportController())->totalOutstanding($dayBeforeYearStart, true);

        $rows = [];
        $balance = $openingBalance;
        $totals = ['dpp' => 0, 'addition' => 0, 'debitNote' => 0, 'payment' => 0];

        for ($m = 1; $m <= 12; $m++) {
            $ym = sprintf('%04d-%02d', $year, $m);
            $addDpp   = $additionDpp[$ym] ?? 0;
            $addTotal = $additionTotal[$ym] ?? 0;
            $dnvDpp   = $showDebitNote ? ($dnDpp[$ym] ?? 0) : 0;
            $dnvTotal = $showDebitNote ? ($dnTotal[$ym] ?? 0) : 0;
            $pay      = $payment[$ym] ?? 0;
            $balance += $addTotal + $dnvTotal - $pay;

            $rows[] = [
                'ym'        => $ym,
                'month'     => $m,
                'label'     => date('F Y', mktime(0, 0, 0, $m, 1, $year)),
                'dpp'       => $addDpp + $dnvDpp,
                'addition'  => $addTotal,
                'debitNote' => $dnvTotal,
                'payment'   => $pay,
                'balance'   => $balance,
            ];

            $totals['dpp']       += $addDpp + $dnvDpp;
            $totals['addition']  += $addTotal;
            $totals['debitNote'] += $dnvTotal;
            $totals['payment']   += $pay;
        }

        return [
            'year'           => $year,
            'basis'          => $basis,
            'showDebitNote'  => $showDebitNote,
            'openingBalance' => $openingBalance,
            'rows'           => $rows,
            'totals'         => $totals,
            'closingBalance' => $balance,
        ];
    }

    /** Tempel Balance basis satunya + Control (selisihnya) ke tiap baris & ke opening/closing --
     *  basisnya cuma 'invoice' vs 'delivery' jadi otherBasis tinggal ditukar, dan rows kedua
     *  basis selalu 12 baris berurutan per bulan, jadi aman di-zip per index. */
    private function attachControl($report, $year)
    {
        $otherBasis = $report['basis'] === 'delivery' ? 'invoice' : 'delivery';
        $other = $this->buildReport($year, $otherBasis);

        foreach ($report['rows'] as $i => &$row) {
            $row['otherBalance'] = $other['rows'][$i]['balance'];
            $row['control']      = $row['balance'] - $row['otherBalance'];
        }
        unset($row);

        $report['otherBasis']          = $otherBasis;
        $report['otherOpeningBalance'] = $other['openingBalance'];
        $report['otherClosingBalance'] = $other['closingBalance'];
        $report['controlClosing']      = $report['closingBalance'] - $other['closingBalance'];

        return $report;
    }

    public function data(Request $request)
    {
        $year  = (int) ($request->year ?: date('Y'));
        $basis = $request->basis === 'delivery' ? 'delivery' : 'invoice';

        return response()->json(['status' => 1] + $this->attachControl($this->buildReport($year, $basis), $year));
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
                SELECT id, dn_number as doc, dn_date as doc_date, grand_total as amount, 'Debit Note' as doc_type
                FROM debit_note_hdr
                WHERE status <> '5' AND to_date(dn_date,'DD-MM-YYYY') $dateCond
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
            // $column di sini cuma 'addition' (DPP tidak clickable, lihat arReconciliation/
            // index.blade.php cell() -- cuma addition/debitNote/payment yang dikasih class
            // recon-clickable), jadi amount-nya di-prorate sama kayak additionTotalSql('delivery').
            $sql = "
                SELECT delivery_hdr.id, delivery_hdr.delivery_number as doc, delivery_hdr.delivery_date as doc_date,
                       SUM(
                            (invoice_det.qty * (invoice_det.price + invoice_det.price_service))
                            * CASE WHEN inv_dpp.dpp > 0 THEN invoice_hdr.grand_total / inv_dpp.dpp ELSE 1 END
                       ) as amount, 'Delivery Note' as doc_type
                FROM invoice_det
                JOIN invoice_hdr ON invoice_hdr.invoice_number = invoice_det.invoice_number
                JOIN delivery_hdr ON delivery_hdr.delivery_number = invoice_det.dn_number
                JOIN (
                    SELECT invoice_number, SUM(qty * (price + price_service)) as dpp
                    FROM invoice_det
                    GROUP BY invoice_number
                ) inv_dpp ON inv_dpp.invoice_number = invoice_det.invoice_number
                WHERE invoice_hdr.status <> '5'
                  AND to_date(delivery_hdr.delivery_date,'DD-MM-YYYY') $dateCond
                GROUP BY delivery_hdr.id, delivery_hdr.delivery_number, delivery_hdr.delivery_date
                ORDER BY to_date(delivery_hdr.delivery_date,'DD-MM-YYYY')
            ";
        } else {
            $sql = "
                SELECT id, invoice_number as doc, invoice_date as doc_date, grand_total as amount, 'AR Invoice' as doc_type
                FROM invoice_hdr
                WHERE status <> '5' AND to_date(invoice_date,'DD-MM-YYYY') $dateCond
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
