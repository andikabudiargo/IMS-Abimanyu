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
    // Semua DN bulan berjalan yang belum punya relasi ke invoice_det:
    //  DELIVERY = DN status 1-4, DN RECEIVED = DN status 8, TEMPORARY DN = surat jalan sementara OPEN.
    private function pendingRows($month)
    {
        return DB::select("
            SELECT * FROM (
                SELECT dh.id, dh.delivery_number AS dn_number, dh.customer_id, dh.delivery_date,
                    CASE WHEN dh.status = '8' THEN 'DN RECEIVED' ELSE 'DELIVERY' END AS source,
                    CASE dh.status WHEN '1' THEN 'NEW' WHEN '2' THEN 'VALIDATE' WHEN '3' THEN 'APPROVED'
                        WHEN '4' THEN 'POSTED' WHEN '10' THEN 'REVISED' WHEN '8' THEN
                        CASE dr.status WHEN '2' THEN 'SUBMITTED (BELUM DIBUATKAN INVOICE)' ELSE 'RECEIVED (BELUM SUBMIT AKUNTING)' END END AS status,
                    dh.created_by, dh.created_at
                FROM delivery_hdr dh
                LEFT JOIN dn_receipt dr ON dr.delivery_number = dh.delivery_number
                WHERE dh.status IN ('1','2','3','4','8','10')
                  AND (dh.origin_delivery_number IS NULL OR dh.origin_delivery_number = dh.delivery_number)
                  AND NOT EXISTS (SELECT 1 FROM invoice_det i WHERE i.dn_number = dh.delivery_number)
                UNION ALL
                SELECT t.id, t.tdn_number, t.customer_id, t.delivery_date, 'TEMPORARY DN', 'OPEN', t.created_by, t.created_at
                FROM temporary_dn_hdr t
                WHERE t.status = '1'
            ) x
            WHERE to_char(to_date(x.delivery_date,'DD-MM-YYYY'),'YYYY-MM') = ?
            ORDER BY to_date(x.delivery_date,'DD-MM-YYYY'), x.dn_number", [$month]);
    }

    private function week($deliveryDate)
    {
        return min(4, (int) ceil((int) substr($deliveryDate, 0, 2) / 7));
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
        foreach ($this->pendingRows($month) as $r) {
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

        $data['summary'] = $summary;
        $data['monthLabel'] = date('F Y', strtotime("$month-01"));
        $data['periode'] = $month;
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
        foreach ($this->pendingRows($month) as $r) {
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
        $rows = array_map(fn($r) => [$r->dn_number, $r->source, $r->delivery_date, $r->status, $r->created_by, $r->created_at], $this->detailRows($request));
        return $this->download($rows, ['Nomor DN', 'Tipe', 'Delivery Date', 'Status', 'Created By', 'Created At'], "dn_belum_invoice_{$request->customer}_W{$request->week}.xlsx");
    }

    public function exportSummary(Request $request)
    {
        $d = $this->summaryData($request);
        $rows = [];
        foreach ($d['summary'] as $r) {
            $w = $r['w'];
            $rows[] = [$r['name'], $r['cutt_off'], $w[1] ?? 0, $w[2] ?? 0, $w[3] ?? 0, $w[4] ?? 0, array_sum($w)];
        }
        if ($rows) {
            $rows[] = ['TOTAL', ''] + array_map(fn($i) => array_sum(array_column($rows, $i)), [2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6]);
        }
        return $this->download($rows, ['Customer', 'Cutt Off DN', 'W1 (1-7)', 'W2 (8-14)', 'W3 (15-21)', 'W4 (22-akhir)', 'Total'], "outstanding_surat_jalan_{$d['periode']}.xlsx");
    }
}
