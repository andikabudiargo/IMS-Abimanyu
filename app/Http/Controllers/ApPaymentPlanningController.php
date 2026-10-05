<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaymentPlanActions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use DB;

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

    public function index(Request $request)
    {
        $data['title'] = 'AP Payment Planning';

        $data['suppliers'] = DB::table('third_party')
            ->where('third_party_type', '=', 'supp')
            ->orderBy('nama')
            ->get();

        return view('apPaymentPlanning.index', $data);
    }

    public function data(Request $request)
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
                hutang.ap_date,
                ap_invoice.note,
                ap_invoice.pph23,
                hutang.jatuh_tempo_actual,
                hutang.balance_asof as nominal,
                (select STRING_AGG(a.rec_number, ',' ORDER BY a.id) from ap_invoice_detail a where a.ap_number = hutang.ap_number) as receive_ap,
                (select STRING_AGG(DISTINCT kas_hdr.voucher_number, ',')
                   from kas_det
                   join kas_hdr on kas_det.voucher_number = kas_hdr.voucher_number
                   where $payWhere) as voucher_number,
                plan.status as plan_status,
                plan.hold_reason,
                plan.biaya_administrasi
            FROM ($subquery) hutang
            JOIN ap_invoice ON ap_invoice.id = hutang.ap_id
            LEFT JOIN third_party ON third_party.kode = hutang.supplier_id
            LEFT JOIN payment_plan plan ON plan.module = 'AP' AND plan.ref_number = hutang.ap_number
            WHERE hutang.jatuh_tempo_actual >= to_date(:periodStart,'DD-MM-YYYY')
              AND hutang.jatuh_tempo_actual <= to_date(:periodEnd,'DD-MM-YYYY')
            ORDER BY hutang.jatuh_tempo_actual ASC, hutang.ap_number ASC
        ";

        $rows = DB::select($sql, $bindings);

        $result = [];
        $grand = ['nominal' => 0.0, 'biaya_administrasi' => 0.0, 'pph23' => 0.0, 'total' => 0.0];

        foreach ($rows as $r) {
            $nominal         = (float) $r->nominal;
            $isPaid          = $nominal <= $this->minOutstanding;
            $effectiveStatus = $isPaid ? 'paid' : ($r->plan_status ?: 'pending');

            if ($status !== '' && $status !== $effectiveStatus) {
                continue;
            }

            $biayaAdmin = (float) ($r->biaya_administrasi ?? 0);
            $total      = $nominal - $biayaAdmin;

            $result[] = [
                'ap_number'          => $r->ap_number,
                'inv_number'         => $r->inv_number,
                'supplier_name'      => $r->supplier_name,
                'ap_date'            => $r->ap_date ?: '-',
                'due_date'           => $r->jatuh_tempo_actual ? date('d-m-Y', strtotime($r->jatuh_tempo_actual)) : '-',
                'voucher_number'     => $isPaid ? $r->voucher_number : '',
                'receive_ap'         => $r->receive_ap,
                'note'               => $r->note,
                'nominal'            => $nominal,
                'biaya_administrasi' => $biayaAdmin,
                'pph23'              => (float) $r->pph23,
                'total'              => $total,
                'status'             => $effectiveStatus,
                'hold_reason'        => $r->hold_reason,
                'ap_link'            => route('accountPayable.show', ['id' => Crypt::encryptString($r->id)]),
            ];

            $grand['nominal']            += $nominal;
            $grand['biaya_administrasi'] += $biayaAdmin;
            $grand['pph23']              += (float) $r->pph23;
            $grand['total']              += $total;
        }

        return response()->json([
            'status' => 1,
            'rows'   => $result,
            'grand'  => $grand,
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
}
