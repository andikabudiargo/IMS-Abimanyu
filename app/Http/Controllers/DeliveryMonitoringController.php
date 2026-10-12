<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

class DeliveryMonitoringController extends Controller
{
    private function period(Request $request)
    {
        return [
            $request->from ?: date('Y-m-01'),
            $request->to ?: date('Y-m-t'),
        ];
    }

    // Grup per customer -> per article (kode+nama) -> per tanggal delivery -> qty.
    // status NOT IN ('5','7') = exclude CANCELED & REVISED lama, sama seperti
    // qty_delivery di DeliveryController supaya angkanya konsisten dengan modul lain.
    private function pivotData(Request $request)
    {
        [$from, $to] = $this->period($request);
        $selected = array_filter((array) $request->customer);

        $rows = DB::select("
            SELECT dh.customer_id, tp.nama AS customer_name,
                dd.article_code, a.article_alternative_code, a.article_desc,
                dh.delivery_date, SUM(dd.qty) AS qty
            FROM delivery_det dd
            JOIN delivery_hdr dh ON dh.delivery_number = dd.delivery_number
            LEFT JOIN article a ON a.article_code = dd.article_code
            LEFT JOIN third_party tp ON tp.kode = dh.customer_id
            WHERE dh.status NOT IN ('5','7')
              AND to_date(dh.delivery_date,'DD-MM-YYYY') BETWEEN ? AND ?
              " . ($selected ? 'AND dh.customer_id IN (' . implode(',', array_fill(0, count($selected), '?')) . ')' : '') . "
            GROUP BY dh.customer_id, tp.nama, dd.article_code, a.article_alternative_code, a.article_desc, dh.delivery_date
        ", array_merge([$from, $to], $selected));

        $data = [];
        foreach ($rows as $r) {
            $c = $r->customer_id;
            $data[$c]['name'] = $r->customer_name ?? $c;
            $data[$c]['articles'][$r->article_code] = trim(($r->article_alternative_code ?? $r->article_code) . ' - ' . $r->article_desc, ' -');
            $data[$c]['dates'][$r->delivery_date] = true;
            $data[$c]['matrix'][$r->article_code][$r->delivery_date] = ($data[$c]['matrix'][$r->article_code][$r->delivery_date] ?? 0) + (float) $r->qty;
        }

        $sortDate = fn($a, $b) => strtotime(implode('-', array_reverse(explode('-', $a)))) <=> strtotime(implode('-', array_reverse(explode('-', $b))));
        foreach ($data as &$c) {
            ksort($c['articles']);
            $c['dates'] = array_keys($c['dates']);
            usort($c['dates'], $sortDate);
        }
        uasort($data, fn($a, $b) => strcmp($a['name'], $b['name']));

        return $data;
    }

    public function index(Request $request)
    {
        [$from, $to] = $this->period($request);
        $data['from'] = $from;
        $data['to'] = $to;
        $data['selected'] = array_filter((array) $request->customer);
        $data['customers'] = DB::table('third_party')->where('third_party_type', 'cust')->orderBy('nama')->get(['kode', 'nama']);
        $data['groups'] = $this->pivotData($request);
        $data['title'] = 'Delivery Monitoring';
        return view('monitoring.deliveryMonitoring', $data);
    }
}
