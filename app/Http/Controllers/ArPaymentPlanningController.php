<?php

namespace App\Http\Controllers;

use App\Exports\ArPaymentPlanningExport;
use App\Http\Controllers\Concerns\PaymentPlanActions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Maatwebsite\Excel\Facades\Excel;
use DB;
use PDF;

/*
    ================================================================
    AR PAYMENT PLANNING
    ================================================================
    Kembaran ApPaymentPlanningController, sisi PIUTANG. Lihat catatan
    di controller itu untuk asumsi Total & kenapa status 'Paid' tidak
    disimpan.
    ================================================================
*/
class ArPaymentPlanningController extends ArPaymentScheduleController
{
    use PaymentPlanActions;

    private $minOutstanding = 0.01;
    private $monthNames = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];

    public function index(Request $request)
    {
        $data['title'] = 'AR Payment Planning';

        $data['customers'] = DB::table('third_party')
            ->where('third_party_type', '=', 'cust')
            ->orderBy('nama')
            ->get();

        return view('arPaymentPlanning.index', $data);
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

        $sql = "
            SELECT
                piutang.invoice_id as id,
                piutang.invoice_number,
                piutang.customer_id,
                third_party.nama as customer_name,
                piutang.invoice_date,
                invoice_hdr.note,
                invoice_hdr.pph23,
                piutang.jatuh_tempo_actual,
                piutang.balance_asof as nominal,
                (select STRING_AGG(DISTINCT kas_hdr.voucher_type || '::' || kas_hdr.id::text || '::' || kas_hdr.voucher_number, ',')
                   from kas_det
                   left join kas_hdr on kas_det.voucher_number = kas_hdr.voucher_number
                   where kas_det.reference = piutang.invoice_number and kas_hdr.status = '3') as voucher_raw,
                plan.status as plan_status,
                plan.hold_reason,
                plan.biaya_administrasi
            FROM ($subquery) piutang
            JOIN invoice_hdr ON invoice_hdr.id = piutang.invoice_id
            LEFT JOIN third_party ON third_party.kode = piutang.customer_id
            LEFT JOIN payment_plan plan ON plan.module = 'AR' AND plan.ref_number = piutang.invoice_number
            WHERE piutang.jatuh_tempo_actual >= to_date(:periodStart,'DD-MM-YYYY')
              AND piutang.jatuh_tempo_actual <= to_date(:periodEnd,'DD-MM-YYYY')
            ORDER BY piutang.jatuh_tempo_actual ASC, piutang.invoice_number ASC
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
                'invoice_number'     => $r->invoice_number,
                'customer_name'      => $r->customer_name,
                'invoice_date'       => $r->invoice_date ?: '-',
                'due_date'           => $r->jatuh_tempo_actual ? date('d-m-Y', strtotime($r->jatuh_tempo_actual)) : '-',
                'vouchers'           => $isPaid ? $this->buildVoucherLinks($r->voucher_raw) : [],
                'note'               => $r->note,
                'nominal'            => $nominal,
                'biaya_administrasi' => $biayaAdmin,
                'pph23'              => (float) $r->pph23,
                'total'              => $total,
                'status'             => $effectiveStatus,
                'hold_reason'        => $r->hold_reason,
                'invoice_link'       => route('invoice.show', ['id' => Crypt::encryptString($r->id)]),
            ];

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
        return $this->markPlanned('AR', $request);
    }

    public function updateFee(Request $request)
    {
        return $this->updateFeePlanned('AR', $request);
    }

    public function export(Request $request)
    {
        $s = $this->buildRows($request);

        return Excel::download(
            new ArPaymentPlanningExport($s['rows'], $s['grand'], $s['periodLabel']),
            'AR_Payment_Planning_' . str_replace(' ', '_', $s['periodLabel']) . '.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        $s = $this->buildRows($request);

        $pdf = PDF::loadView('arPaymentPlanning.print', [
            'rows'        => $s['rows'],
            'grand'       => $s['grand'],
            'periodLabel' => $s['periodLabel'],
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('AR_Payment_Planning_' . str_replace(' ', '_', $s['periodLabel']) . '.pdf');
    }
}
