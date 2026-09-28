<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use DB;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DnMonitoringController extends Controller
{
    // Semua DN bulan berjalan yang belum kelar salah satu dari dua hal:
    //  - belum di-invoice (belum ada relasi ke invoice_det)
    //  - belum kembali (surat jalan belum di-Receive, DELIVERY yang belum berstatus DN RECEIVED)
    // DELIVERY = DN status 1-4/10, DN RECEIVED = DN status 8, TEMPORARY DN = surat jalan sementara OPEN.
    private function pendingRows($month, $filter = 'invoice')
    {
        $rows = DB::select("
            SELECT * FROM (
                SELECT dh.id, dh.delivery_number AS dn_number, dh.customer_id, dh.delivery_date,
                    CASE WHEN dh.status = '8' THEN 'DN RECEIVED' ELSE 'DELIVERY' END AS source,
                    CASE dh.status WHEN '1' THEN 'NEW' WHEN '2' THEN 'VALIDATE' WHEN '3' THEN 'APPROVED'
                        WHEN '4' THEN 'POSTED' WHEN '10' THEN 'REVISED' WHEN '8' THEN
                        CASE dr.status WHEN '2' THEN 'SUBMITTED (BELUM DIBUATKAN INVOICE)' ELSE 'RECEIVED (BELUM SUBMIT AKUNTING)' END END AS status,
                    dh.created_by, dh.created_at,
                    to_char(to_date(dr.dr_date,'YYYY-MM-DD'), 'DD-MM-YYYY') AS received_date,
                    NOT EXISTS (SELECT 1 FROM invoice_det i WHERE i.dn_number = dh.delivery_number) AS belum_invoice,
                    dh.status <> '8' AS belum_kembali
                FROM delivery_hdr dh
                LEFT JOIN dn_receipt dr ON dr.delivery_number = dh.delivery_number
                WHERE dh.status IN ('1','2','3','4','8','10')
                  AND (dh.origin_delivery_number IS NULL OR dh.origin_delivery_number = dh.delivery_number)
                UNION ALL
                SELECT t.id, t.tdn_number, t.customer_id, t.delivery_date, 'TEMPORARY DN', 'OPEN', t.created_by, t.created_at,
                    NULL, true, true
                FROM temporary_dn_hdr t
                WHERE t.status = '1'
            ) x
            WHERE to_char(to_date(x.delivery_date,'DD-MM-YYYY'),'YYYY-MM') = ?
              AND (x.belum_invoice OR x.belum_kembali)
            ORDER BY to_date(x.delivery_date,'DD-MM-YYYY'), x.dn_number", [$month]);

        if ($filter == 'invoice') {
            return array_values(array_filter($rows, fn($r) => $r->belum_invoice));
        }
        if ($filter == 'kembali') {
            return array_values(array_filter($rows, fn($r) => $r->belum_kembali));
        }
        return $rows; // all
    }

    private function filter(Request $request)
    {
        return in_array($request->filter, ['kembali', 'all']) ? $request->filter : 'invoice';
    }

    private function week($deliveryDate)
    {
        return (int) ceil((int) substr($deliveryDate, 0, 2) / 7);
    }

    // Kebanyakan bulan punya 4 minggu (28-31 hari); bulan yang tanggal 29-31-nya
    // jatuh ke minggu ke-5 (ceil > 4) dapat kolom W5 tambahan di tabel.
    private function weekCount($month)
    {
        return (int) ceil(date('t', strtotime("$month-01")) / 7);
    }

    private function month(Request $request)
    {
        return preg_match('/^\d{4}-\d{2}$/', $request->periode) ? $request->periode : date('Y-m');
    }

    private function summaryData(Request $request)
    {
        $month = $this->month($request);

        $hasCutOff = Schema::hasColumn('third_party', 'cutt_off_dn');
        $customers = DB::table('third_party')->where('third_party_type', 'cust')
            ->select('kode', 'nama', DB::raw($hasCutOff ? 'cutt_off_dn as cutt_off' : "'' as cutt_off"))
            ->get()->keyBy('kode');

        $summary = [];
        $selected = array_filter((array) $request->customer);
        foreach ($this->pendingRows($month, $this->filter($request)) as $r) {
            if ($selected && !in_array($r->customer_id, $selected)) {
                continue;
            }
            $c = $r->customer_id;
            $summary[$c]['name'] = $customers[$c]->nama ?? $c;
            $summary[$c]['cutt_off'] = $customers[$c]->cutt_off ?? '';
            $w = $this->week($r->delivery_date);
            $summary[$c]['w'][$w] = ($summary[$c]['w'][$w] ?? 0) + 1;
        }
        uasort($summary, fn($a, $b) => strcmp($a['name'], $b['name']));

        $weeks = range(1, $this->weekCount($month));
        $daysInMonth = date('t', strtotime("$month-01"));
        $data['summary'] = $summary;
        $data['weeks'] = $weeks;
        $data['weekLabels'] = [];
        foreach ($weeks as $w) {
            $end = min($w * 7, $daysInMonth);
            $data['weekLabels'][$w] = "W$w (" . ($w * 7 - 6) . "-" . ($w == count($weeks) ? 'akhir' : $end) . ')';
        }
        $data['monthLabel'] = date('F Y', strtotime("$month-01"));
        $data['periode'] = $month;
        $data['filter'] = $this->filter($request);
        $data['customers'] = $customers;
        $data['selected'] = $selected;
        return $data;
    }

    public function index(Request $request)
    {
        $data = $this->summaryData($request);
        $data['title'] = 'DN Monitoring';
        return view('monitoring.dnMonitoring', $data);
    }

    private function detailRows(Request $request)
    {
        $month = $this->month($request);
        $rows = [];
        foreach ($this->pendingRows($month, $this->filter($request)) as $r) {
            if ($r->customer_id != $request->customer || $this->week($r->delivery_date) != (int) $request->week) {
                continue;
            }
            $id = Crypt::encryptString($r->id);
            $r->url = $r->source == 'TEMPORARY DN'
                ? route('suratJalanSementara.show', ['id' => $id])
                : route('delivery.show', ['id' => $id]);
            unset($r->id);
            $rows[] = $r;
        }
        return $rows;
    }

    public function detail(Request $request)
    {
        return response()->json($this->detailRows($request));
    }

    private function download(array $rows, array $headings, $name)
    {
        $export = new class($rows, $headings) implements FromArray, WithHeadings, ShouldAutoSize {
            private $rows;
            private $headings;
            public function __construct(array $rows, array $headings) { $this->rows = $rows; $this->headings = $headings; }
            public function array(): array { return $this->rows; }
            public function headings(): array { return $this->headings; }
        };
        return Excel::download($export, $name);
    }

    public function export(Request $request)
    {
        $rows = array_map(fn($r) => [$r->dn_number, $r->source, $r->delivery_date, $r->received_date ?? '', $r->status, $r->created_by, $r->created_at], $this->detailRows($request));
        return $this->download($rows, ['Nomor DN', 'Tipe', 'Delivery Date', 'Received Date', 'Status', 'Created By', 'Created At'], "dn_belum_invoice_{$request->customer}_W{$request->week}.xlsx");
    }

    public function exportSummary(Request $request)
    {
        $d = $this->summaryData($request);
        $weeks = $d['weeks'];
        $rows = [];
        foreach ($d['summary'] as $r) {
            $w = $r['w'];
            $rows[] = array_merge([$r['name'], $r['cutt_off']], array_map(fn($i) => $w[$i] ?? 0, $weeks), [array_sum($w)]);
        }
        if ($rows) {
            $totalCol = count($weeks) + 2;
            $rows[] = array_merge(['TOTAL', ''], array_map(fn($i) => array_sum(array_column($rows, $i)), range(2, $totalCol)));
        }
        $headings = array_merge(['Customer', 'Cutt Off DN'], array_map(fn($i) => $d['weekLabels'][$i], $weeks), ['Total']);
        return $this->download($rows, $headings, "outstanding_surat_jalan_{$d['periode']}.xlsx");
    }
}
