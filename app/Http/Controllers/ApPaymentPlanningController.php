<?php

namespace App\Http\Controllers;

use App\Exports\ApPaymentPlanningExport;
use App\Http\Controllers\Concerns\PaymentPlanActions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Maatwebsite\Excel\Facades\Excel;
use DB;
use PDF;

/*
    ================================================================
    AP PAYMENT PLANNING
    ================================================================
    Checklist per-invoice (bukan matrix per-supplier seperti AP Payment
    Schedule) buat nandain AP mana yang mau di-HOLD (+alasan) atau
    di-TO BE PAID (+estimasi biaya administrasi) sebelum voucher dibuat.

    Extends ApPaymentScheduleController supaya rumus jatuh tempo & balance
    (buildScheduleSubquery, dkk) tidak ditulis ulang -- dua laporan ini
    wajib selalu konsisten satu sama lain.

    Status 'Paid' TIDAK disimpan -- otomatis kalau balance_asof invoice
    sudah <= minOutstanding (sama persis kayak AP Payment Schedule).
    Asumsi: Total = Nominal (balance_asof, PPH23 sudah dipotong di
    grand_total invoice) dikurangi Biaya Administrasi. PPH23 cuma info.
    ================================================================
*/
class ApPaymentPlanningController extends ApPaymentScheduleController
{
    use PaymentPlanActions;

    private $monthNames = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];

    public function index(Request $request)
    {
        $data['title'] = 'AP Payment Planning';

        $data['suppliers'] = DB::table('third_party')
            ->where('third_party_type', '=', 'supp')
            ->orderBy('nama')
            ->get();

        return view('apPaymentPlanning.index', $data);
    }

    private function buildRows(Request $request)
    {
        $month  = $request->month ? (int) $request->month : (int) date('n');
        $year   = $request->year  ? (int) $request->year  : (int) date('Y');
        $status = $request->status ? trim($request->status) : '';

        list($periodStart, $periodEnd, $daysInMonth) = $this->periodBounds($month, $year);
        $asOf = $this->resolveAsOf($periodEnd);

        list($whereExtra, $bindings) = $this->buildFilters($request);
        $bindings['periodStart']        = $periodStart;
        $bindings['periodEnd']          = $periodEnd;
        $bindings['asOf']               = $asOf;
        $bindings['floorDate']          = $this->floorDate;
        $bindings['pairRequiredBefore'] = $this->pairRequiredBefore;

        $subquery = $this->buildScheduleSubquery($whereExtra);

        $aging    = new ApAgingReportController();
        $payWhere = "kas_det.reference = ap_invoice.inv_number
                   AND " . $aging->paymentMatchSql('kas_hdr', 'kas_det') . "
                   AND kas_hdr.status <> '5'";

        $sql = "
            SELECT
                hutang.ap_id as id,
                hutang.ap_number,
                hutang.inv_number,
                hutang.supplier_id,
                third_party.nama as supplier_name,
                third_party.bank_name,
                third_party.bank_type,
                third_party.account_number,
                hutang.inv_date,
                hutang.ap_date as receive_ap,
                ap_invoice.note,
                ap_invoice.pph23,
                hutang.jatuh_tempo_actual,
                hutang.balance_asof as nominal,
                ap_invoice.grand_total as outstanding,
                (select STRING_AGG(DISTINCT kas_hdr.voucher_type || '::' || kas_hdr.id::text || '::' || kas_hdr.voucher_number, ',')
                   from kas_det
                   join kas_hdr on kas_det.voucher_number = kas_hdr.voucher_number
                   where $payWhere) as voucher_raw,
                plan.status as plan_status,
                plan.hold_reason,
                plan.biaya_administrasi
            FROM ($subquery) hutang
            JOIN ap_invoice ON ap_invoice.id = hutang.ap_id
            LEFT JOIN third_party ON third_party.kode = hutang.supplier_id
            LEFT JOIN payment_plan plan ON plan.module = 'AP' AND plan.ref_number = hutang.ap_number
            WHERE hutang.jatuh_tempo_actual >= to_date(:periodStart,'DD-MM-YYYY')
              AND hutang.jatuh_tempo_actual <= to_date(:periodEnd,'DD-MM-YYYY')
            ORDER BY third_party.nama ASC, hutang.supplier_id ASC, hutang.jatuh_tempo_actual ASC, hutang.ap_number ASC
        ";

        $rows = DB::select($sql, $bindings);

        $result = [];
        $grand = ['outstanding' => 0.0, 'paid' => 0.0, 'nominal' => 0.0, 'biaya_administrasi' => 0.0, 'pph23' => 0.0, 'total' => 0.0];

        // Biaya admin otomatis: bank non-BCA kena 2.900 per transfer, satu transfer
        // maksimal 500 juta -> per supplier jumlah transfer = ceil(total balance
        // To Be Paid / 500jt). Dihitung sebelum filter status supaya tetap benar
        // walau tabel difilter. Isian manual (> 0) di salah satu invoice supplier
        // itu menggantikan perhitungan otomatis supplier tersebut.
        $autoFee   = [];
        $hasManual = [];
        $sumTbp    = [];
        $bankOf    = [];
        foreach ($rows as $r) {
            $bankOf[$r->supplier_id] = $r->bank_type;
            if ((float) $r->nominal <= $this->minOutstanding || $r->plan_status !== 'to_be_paid') {
                continue;
            }
            $sumTbp[$r->supplier_id] = ($sumTbp[$r->supplier_id] ?? 0) + (float) $r->nominal;
            if ((float) $r->biaya_administrasi > 0) {
                $hasManual[$r->supplier_id] = true;
            }
        }
        foreach ($sumTbp as $sid => $sum) {
            if (empty($hasManual[$sid]) && strtoupper(trim((string) $bankOf[$sid])) !== 'BCA') {
                $autoFee[$sid] = (int) ceil($sum / 500000000) * 2900;
            }
        }

        $prevSupplier = null;
        foreach ($rows as $r) {
            $nominal         = (float) $r->nominal;
            $isPaid          = $nominal <= $this->minOutstanding;
            $effectiveStatus = $isPaid ? 'paid' : ($r->plan_status ?: 'pending');

            if ($status !== '' && $status !== $effectiveStatus) {
                continue;
            }

            $biayaAdmin = (float) ($r->biaya_administrasi ?? 0);
            if ($effectiveStatus === 'to_be_paid' && $biayaAdmin <= 0 && isset($autoFee[$r->supplier_id])) {
                $biayaAdmin = (float) $autoFee[$r->supplier_id];
                unset($autoFee[$r->supplier_id]); // cukup di baris pertama supplier
            }
            $total       = $nominal - $biayaAdmin;
            $outstanding = (float) $r->outstanding;
            $paid        = $outstanding - $nominal;

            $result[] = [
                'ap_number'          => $r->ap_number,
                'supplier_name'      => $r->supplier_name,
                'supplier_label'     => $r->supplier_id === $prevSupplier ? '' : $r->supplier_name,
                'invoice_date'       => $r->inv_date ?: '-',
                'due_date'           => $r->jatuh_tempo_actual ? date('d-m-Y', strtotime($r->jatuh_tempo_actual)) : '-',
                'vouchers'           => $isPaid ? $this->buildVoucherLinks($r->voucher_raw) : [],
                'receive_ap'         => $r->receive_ap ?: '-',
                'note'               => $r->note,
                'bank_name'          => $r->bank_name ?: '-',
                'account_number'     => $r->account_number ?: '-',
                'outstanding'        => $outstanding,
                'paid'               => $paid,
                'nominal'            => $nominal,
                'biaya_administrasi' => $biayaAdmin,
                'pph23'              => (float) $r->pph23,
                'total'              => $total,
                'status'             => $effectiveStatus,
                'hold_reason'        => $r->hold_reason,
                'ap_link'            => route('accountPayable.show', ['id' => Crypt::encryptString($r->id)]),
            ];

            $prevSupplier = $r->supplier_id;

            $grand['outstanding']        += $outstanding;
            $grand['paid']               += $paid;
            $grand['nominal']            += $nominal;
            $grand['biaya_administrasi'] += $biayaAdmin;
            $grand['pph23']              += (float) $r->pph23;
            $grand['total']              += $total;
        }

        return [
            'rows'        => $result,
            'grand'       => $grand,
            'periodLabel' => ($this->monthNames[$month] ?? $month) . ' ' . $year,
        ];
    }

    public function data(Request $request)
    {
        $s = $this->buildRows($request);

        return response()->json([
            'status' => 1,
            'rows'   => $s['rows'],
            'grand'  => $s['grand'],
        ]);
    }

    public function mark(Request $request)
    {
        return $this->markPlanned('AP', $request);
    }

    public function updateFee(Request $request)
    {
        return $this->updateFeePlanned('AP', $request);
    }

    public function export(Request $request)
    {
        $s = $this->buildRows($request);

        return Excel::download(
            new ApPaymentPlanningExport($s['rows'], $s['grand'], $s['periodLabel']),
            'AP_Payment_Planning_' . str_replace(' ', '_', $s['periodLabel']) . '.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        $s = $this->buildRows($request);

        $pdf = PDF::loadView('apPaymentPlanning.print', [
            'rows'        => $s['rows'],
            'grand'       => $s['grand'],
            'periodLabel' => $s['periodLabel'],
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('AP_Payment_Planning_' . str_replace(' ', '_', $s['periodLabel']) . '.pdf');
    }
}
