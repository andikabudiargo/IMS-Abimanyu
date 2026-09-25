<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use DB;

class DnMonitoringController extends Controller
{
    // Semua DN bulan berjalan yang belum punya relasi ke invoice_det:
    //  DELIVERY = DN status 1-4, DN RECEIVED = DN status 8, TEMPORARY DN = surat jalan sementara OPEN.
    private function pendingRows()
    {
        $month = date('Y-m');
        return DB::select("
            SELECT * FROM (
                SELECT dh.id, dh.delivery_number AS dn_number, dh.customer_id, dh.delivery_date,
                    CASE WHEN dh.status = '8' THEN 'DN RECEIVED' ELSE 'DELIVERY' END AS source,
                    CASE dh.status WHEN '1' THEN 'NEW' WHEN '2' THEN 'VALIDATE' WHEN '3' THEN 'APPROVED'
                        WHEN '4' THEN 'POSTED' WHEN '8' THEN
                        CASE dr.status WHEN '2' THEN 'RECEIVED - SUBMITTED' ELSE 'RECEIVED' END END AS status,
                    dh.created_by, dh.created_at
                FROM delivery_hdr dh
                LEFT JOIN dn_receipt dr ON dr.delivery_number = dh.delivery_number
                WHERE dh.status IN ('1','2','3','4','8')
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

    public function index()
    {
        $data['title'] = 'DN Monitoring';

        $hasCutOff = Schema::hasColumn('third_party', 'cutt_off_dn');
        $customers = DB::table('third_party')->where('third_party_type', 'cust')
            ->select('kode', 'nama', DB::raw($hasCutOff ? 'cutt_off_dn as cutt_off' : "'' as cutt_off"))
            ->get()->keyBy('kode');

        $summary = [];
        foreach ($this->pendingRows() as $r) {
            $c = $r->customer_id;
            $summary[$c]['name'] = $customers[$c]->nama ?? $c;
            $summary[$c]['cutt_off'] = $customers[$c]->cutt_off ?? '';
            $w = $this->week($r->delivery_date);
            $summary[$c]['w'][$w] = ($summary[$c]['w'][$w] ?? 0) + 1;
        }
        uasort($summary, fn($a, $b) => strcmp($a['name'], $b['name']));

        $data['summary'] = $summary;
        $data['monthLabel'] = date('F Y');
        return view('monitoring.dnMonitoring', $data);
    }

    public function detail(Request $request)
    {
        $rows = [];
        foreach ($this->pendingRows() as $r) {
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
        return response()->json($rows);
    }
}
