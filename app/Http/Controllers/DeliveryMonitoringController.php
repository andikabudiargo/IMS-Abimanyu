<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use DB;
use PDF;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DeliveryMonitoringExport;

class DeliveryMonitoringController extends Controller
{
    private function period(Request $request)
    {
        return [
            $request->from ?: date('Y-m-01'),
            $request->to ?: date('Y-m-t'),
        ];
    }

    private function dnType(Request $request)
    {
        return in_array($request->dn_type, ['dn', 'temp', 'replace']) ? $request->dn_type : 'all';
    }

    private function dnTypeLabel($dnType)
    {
        return ['dn' => 'DN', 'temp' => 'SJ SEMENTARA', 'replace' => 'DN REPLACE'][$dnType] ?? null;
    }

    // Satu sumber transaksi untuk pivot & modal detail: gabungan DN (delivery_hdr/det),
    // SJ Sementara (temporary_dn_hdr/det), dan DN Replace (dn_replace_hdr/det).
    // status NOT IN('5','7')/<>'4'/<>'3' = exclude cancelled & DN lama yang sudah direvisi,
    // supaya konsisten dengan qty_delivery yang dipakai modul lain.
    private function rows(Request $request)
    {
        [$from, $to] = $this->period($request);
        $selected = array_filter((array) $request->customer);
        $dnType = $this->dnType($request);

        $where = ["to_date(x.delivery_date,'DD-MM-YYYY') BETWEEN ? AND ?"];
        $bindings = [$from, $to];

        if ($selected) {
            $where[] = 'x.customer_id IN (' . implode(',', array_fill(0, count($selected), '?')) . ')';
            $bindings = array_merge($bindings, $selected);
        }
        if ($label = $this->dnTypeLabel($dnType)) {
            $where[] = 'x.type = ?';
            $bindings[] = $label;
        }
        if ($request->article) {
            $where[] = 'x.article_code = ?';
            $bindings[] = $request->article;
        }
        if ($request->date) {
            $where[] = 'x.delivery_date = ?';
            $bindings[] = $request->date;
        }

        $sql = "SELECT x.customer_id, tp.nama AS customer_name, x.article_code,
                a.article_alternative_code, a.article_desc, x.delivery_date, x.type, x.number, x.hdr_id, x.so_number, x.qty
            FROM (
                SELECT dh.customer_id, dd.article_code, dh.delivery_date, 'DN' AS type, dh.delivery_number AS number, dh.id AS hdr_id, dd.so_number, dd.qty
                FROM delivery_det dd JOIN delivery_hdr dh ON dh.delivery_number = dd.delivery_number
                WHERE dh.status NOT IN ('5','7')
                UNION ALL
                SELECT th.customer_id, td.article_code, th.delivery_date, 'SJ SEMENTARA' AS type, th.tdn_number AS number, th.id AS hdr_id, th.so_number, td.qty
                FROM temporary_dn_det td JOIN temporary_dn_hdr th ON th.tdn_number = td.tdn_number
                WHERE th.status <> '4'
                UNION ALL
                SELECT rh.customer_id, rd.article_code, rh.replace_date AS delivery_date, 'DN REPLACE' AS type, rh.replace_number AS number, rh.id AS hdr_id, NULL::varchar AS so_number, rd.qty
                FROM dn_replace_det rd JOIN dn_replace_hdr rh ON rh.replace_number = rd.replace_number
                WHERE rh.status <> '3'
            ) x
            LEFT JOIN article a ON a.article_code = x.article_code
            LEFT JOIN third_party tp ON tp.kode = x.customer_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY to_date(x.delivery_date,'DD-MM-YYYY'), x.number";

        return DB::select($sql, $bindings);
    }

    private function sortDates(array $dates)
    {
        usort($dates, fn($a, $b) => strtotime(implode('-', array_reverse(explode('-', $a)))) <=> strtotime(implode('-', array_reverse(explode('-', $b)))));
        return $dates;
    }

    // $summary true = satu tabel gabungan, baris = customer+article (bukan dipecah per card customer).
    private function groups(array $rows, $summary = false)
    {
        $data = [];
        foreach ($rows as $r) {
            $key = $summary ? 'ALL' : $r->customer_id;
            $data[$key]['name'] = $summary ? 'Summary - Semua Customer' : ($r->customer_name ?? $r->customer_id);
            $rowKey = $summary ? $r->customer_id . '|' . $r->article_code : $r->article_code;
            $data[$key]['articles'][$rowKey] = trim(($r->article_alternative_code ?? $r->article_code) . ' - ' . $r->article_desc, ' -');
            $data[$key]['rowCustomer'][$rowKey] = $r->customer_name ?? $r->customer_id;
            $data[$key]['dates'][$r->delivery_date] = true;
            $data[$key]['matrix'][$rowKey][$r->delivery_date] = ($data[$key]['matrix'][$rowKey][$r->delivery_date] ?? 0) + (float) $r->qty;
        }
        foreach ($data as &$g) {
            $customers = $g['rowCustomer'] ?? [];
            $articles = $g['articles'];
            uksort($articles, fn($a, $b) => strcmp(($customers[$a] ?? '') . $articles[$a], ($customers[$b] ?? '') . $articles[$b]));
            $g['articles'] = $articles;
            $g['dates'] = $this->sortDates(array_keys($g['dates']));
        }
        uasort($data, fn($a, $b) => strcmp($a['name'], $b['name']));
        return $data;
    }

    // Heatmap hari-dalam-minggu x minggu-ke-N (relatif ke tanggal awal periode),
    // persis pola kalender kontribusi: baris Minggu di atas, Senin di bawah.
    private function heatmapSeries(array $rows, $from)
    {
        $dayNames = ['Senin', 'Selasa', 'Rabu', 'Kamis', "Jum'at", 'Sabtu', 'Minggu'];
        $daily = [];
        foreach ($rows as $r) {
            $iso = implode('-', array_reverse(explode('-', $r->delivery_date)));
            $daily[$iso] = ($daily[$iso] ?? 0) + (float) $r->qty;
        }

        $fromTs = strtotime($from);
        $grid = [];
        $maxWeek = 1;
        foreach ($daily as $iso => $qty) {
            $ts = strtotime($iso);
            $week = (int) floor(($ts - $fromTs) / 86400 / 7) + 1;
            $dayIndex = ((int) date('N', $ts)) - 1; // 0=Senin..6=Minggu
            $grid[$dayIndex][$week] = ($grid[$dayIndex][$week] ?? 0) + $qty;
            $maxWeek = max($maxWeek, $week);
        }

        $series = [];
        foreach ([6, 5, 4, 3, 2, 1, 0] as $dayIndex) {
            $points = [];
            for ($w = 1; $w <= $maxWeek; $w++) {
                $points[] = ['x' => "Minggu ke-$w", 'y' => round($grid[$dayIndex][$w] ?? 0, 2)];
            }
            $series[] = ['name' => $dayNames[$dayIndex], 'data' => $points];
        }
        return $series;
    }

    private function viewData(Request $request)
    {
        $data['from'] = $request->from ?: date('Y-m-01');
        $data['to'] = $request->to ?: date('Y-m-t');
        $data['selected'] = array_filter((array) $request->customer);
        $data['dnType'] = $this->dnType($request);
        $data['summary'] = $request->view == 'summary';
        $rows = $this->rows($request);
        $data['groups'] = $this->groups($rows, $data['summary']);
        $data['heatmap'] = $this->heatmapSeries($rows, $data['from']);
        return $data;
    }

    public function index(Request $request)
    {
        $data = $this->viewData($request);
        $data['customers'] = DB::table('third_party')->where('third_party_type', 'cust')->orderBy('nama')->get(['kode', 'nama']);
        $data['title'] = 'Delivery Monitoring';
        return view('monitoring.deliveryMonitoring', $data);
    }

    public function detail(Request $request)
    {
        $routes = ['DN' => 'delivery.show', 'SJ SEMENTARA' => 'suratJalanSementara.show', 'DN REPLACE' => 'dnReplace.show'];
        $rows = array_map(function ($r) use ($routes) {
            $r->url = route($routes[$r->type], ['id' => Crypt::encryptString($r->hdr_id)]);
            return $r;
        }, $this->rows($request));
        return response()->json($rows);
    }

    public function exportExcel(Request $request)
    {
        $data = $this->viewData($request);
        return Excel::download(new DeliveryMonitoringExport($data['groups']), 'Delivery_Monitoring_' . date('Ymd_His') . '.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $data = $this->viewData($request);
        $pdf = PDF::loadView('monitoring.deliveryMonitoringPrint', $data)->setPaper('a4', 'landscape');
        return $pdf->stream('Delivery_Monitoring_' . date('Ymd_His') . '.pdf');
    }
}
