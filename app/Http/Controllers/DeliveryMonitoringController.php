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
    // ponytail: hardcoded cutover, rolling-plan belum ada datanya sebelum tanggal ini.
    // Naikkan ke constant config kalau nanti butuh anchor berbeda per customer/periode.
    private const ROLLING_PLAN_ANCHOR = '2026-10-01';

    private function period(Request $request, $anchored = false)
    {
        return [
            $anchored ? self::ROLLING_PLAN_ANCHOR : ($request->from ?: date('Y-m-01')),
            $request->to ?: date('Y-m-t'),
        ];
    }

    // dn_type[] boleh pilih beberapa sekaligus (mis. DN + SJ Sementara tanpa DN Replace).
    // Kosong atau semua tercentang = tidak difilter (semua tipe).
    private function dnType(Request $request)
    {
        $selected = array_intersect((array) $request->dn_type, ['dn', 'temp', 'replace']);
        return count($selected) == 3 ? [] : array_values($selected);
    }

    private function dnTypeLabels(array $dnType)
    {
        $map = ['dn' => 'DN', 'temp' => 'SJ SEMENTARA', 'replace' => 'DN REPLACE'];
        return array_values(array_intersect_key($map, array_flip($dnType)));
    }

    // Satu sumber transaksi untuk pivot & modal detail: gabungan DN (delivery_hdr/det),
    // SJ Sementara (temporary_dn_hdr/det), dan DN Replace (dn_replace_hdr/det).
    // status NOT IN('5','7')/<>'4'/<>'3' = exclude cancelled & DN lama yang sudah direvisi,
    // supaya konsisten dengan qty_delivery yang dipakai modul lain.
    // $ignoreArticle = true saat memanggil hanya untuk mengisi pilihan dropdown article
    // (supaya pilihan yang sudah dicentang tidak ikut menyempitkan daftarnya sendiri).
    private function rows(Request $request, $ignoreArticle = false, $anchored = false)
    {
        [$from, $to] = $this->period($request, $anchored);
        $selected = array_filter((array) $request->customer);
        $dnType = $this->dnType($request);
        $articles = $ignoreArticle ? [] : array_filter((array) $request->article);

        $where = ["to_date(x.delivery_date,'DD-MM-YYYY') BETWEEN ? AND ?"];
        $bindings = [$from, $to];

        if ($selected) {
            $where[] = 'x.customer_id IN (' . implode(',', array_fill(0, count($selected), '?')) . ')';
            $bindings = array_merge($bindings, $selected);
        }
        if ($labels = $this->dnTypeLabels($dnType)) {
            $where[] = 'x.type IN (' . implode(',', array_fill(0, count($labels), '?')) . ')';
            $bindings = array_merge($bindings, $labels);
        }
        if ($articles) {
            $where[] = 'x.article_code IN (' . implode(',', array_fill(0, count($articles), '?')) . ')';
            $bindings = array_merge($bindings, $articles);
        }
        if ($request->date) {
            $where[] = 'x.delivery_date = ?';
            $bindings[] = $request->date;
        }

        $sql = "SELECT x.customer_id, tp.nama AS customer_name, x.article_code,
                a.article_alternative_code, a.article_desc, x.delivery_date, x.type, x.number, x.hdr_id, x.so_number, x.qty, x.status
            FROM (
                SELECT dh.customer_id, dd.article_code, dh.delivery_date, 'DN' AS type, dh.delivery_number AS number, dh.id AS hdr_id, dd.so_number, dd.qty,
                    CASE dh.status WHEN '1' THEN 'NEW' WHEN '2' THEN 'VALIDATE' WHEN '3' THEN 'APPROVED' WHEN '4' THEN 'POSTED'
                        WHEN '8' THEN 'RECEIVED' WHEN '10' THEN 'REVISED' ELSE dh.status END AS status
                FROM delivery_det dd JOIN delivery_hdr dh ON dh.delivery_number = dd.delivery_number
                WHERE dh.status NOT IN ('5','7')
                UNION ALL
                SELECT th.customer_id, td.article_code, th.delivery_date, 'SJ SEMENTARA' AS type, th.tdn_number AS number, th.id AS hdr_id, th.so_number, td.qty,
                    CASE th.status WHEN '1' THEN 'OPEN' WHEN '2' THEN 'SO' WHEN '3' THEN 'CLOSED' ELSE th.status END AS status
                FROM temporary_dn_det td JOIN temporary_dn_hdr th ON th.tdn_number = td.tdn_number
                WHERE th.status <> '4'
                UNION ALL
                SELECT rh.customer_id, rd.article_code, rh.replace_date AS delivery_date, 'DN REPLACE' AS type, rh.replace_number AS number, rh.id AS hdr_id, NULL::varchar AS so_number, rd.qty,
                    CASE rh.status WHEN '1' THEN 'OPEN' WHEN '2' THEN 'CLOSED' ELSE rh.status END AS status
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

    // Plan source: delivery_schedule_det for delivery_type='PO' SOs (per-date planned qty,
    // see DeliveryScheduleController), else the SO's single header-level delivery_date holds
    // the whole article qty (non-PO types are shipped in one go, not scheduled across dates).
    private function planRows(Request $request, $anchored = false)
    {
        [$from, $to] = $this->period($request, $anchored);
        $selected = array_filter((array) $request->customer);
        $articles = array_filter((array) $request->article);

        $where = ["x.plan_date BETWEEN ? AND ?"];
        $bindings = [$from, $to];

        if ($selected) {
            $where[] = 'x.customer_id IN (' . implode(',', array_fill(0, count($selected), '?')) . ')';
            $bindings = array_merge($bindings, $selected);
        }
        if ($articles) {
            $where[] = 'x.article_code IN (' . implode(',', array_fill(0, count($articles), '?')) . ')';
            $bindings = array_merge($bindings, $articles);
        }

        $sql = "SELECT x.customer_id, tp.nama AS customer_name, x.article_code,
                a.article_alternative_code, a.article_desc, to_char(x.plan_date,'DD-MM-YYYY') AS delivery_date, x.qty
            FROM (
                SELECT h.customer_id, dsd.article_code, dsd.schedule_date AS plan_date, dsd.qty
                FROM delivery_schedule_det dsd
                JOIN sales_order_hdr h ON h.so_code = dsd.so_code
                WHERE h.delivery_type = 'PO'
                UNION ALL
                SELECT h.customer_id, d.article_code, h.delivery_date AS plan_date, d.qty
                FROM sales_order_det d
                JOIN sales_order_hdr h ON h.so_code = d.so_code
                WHERE h.delivery_type IS NOT NULL AND h.delivery_type <> 'PO' AND h.delivery_date IS NOT NULL
            ) x
            LEFT JOIN article a ON a.article_code = x.article_code
            LEFT JOIN third_party tp ON tp.kode = x.customer_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY x.plan_date";

        return DB::select($sql, $bindings);
    }

    // $summary true = satu tabel gabungan, baris = customer+article (bukan dipecah per card customer).
    // $actualRows feeds 'matrix' (Actual - DN Replace excluded, see Plan/Actual/Balance remarks
    // on the monitoring table), $planRows feeds 'plan'; Balance (Actual - Plan) is computed in the view.
    private function groups(array $actualRows, array $planRows, $summary = false)
    {
        $data = [];
        $add = function ($r, $field) use (&$data, $summary) {
            $key = $summary ? 'ALL' : $r->customer_id;
            $data[$key]['name'] = $summary ? 'Summary - Semua Customer' : ($r->customer_name ?? $r->customer_id);
            $rowKey = $summary ? $r->customer_id . '|' . $r->article_code : $r->article_code;
            $data[$key]['articles'][$rowKey] = trim(($r->article_alternative_code ?? $r->article_code) . ' - ' . $r->article_desc, ' -');
            $data[$key]['rowCustomer'][$rowKey] = $r->customer_name ?? $r->customer_id;
            $data[$key]['dates'][$r->delivery_date] = true;
            $data[$key][$field][$rowKey][$r->delivery_date] = ($data[$key][$field][$rowKey][$r->delivery_date] ?? 0) + (float) $r->qty;
        };

        foreach ($actualRows as $r) {
            if ($r->type === 'DN REPLACE') {
                continue;
            }
            $add($r, 'matrix');
        }
        foreach ($planRows as $r) {
            $add($r, 'plan');
        }

        foreach ($data as &$g) {
            $customers = $g['rowCustomer'] ?? [];
            $articles = $g['articles'];
            uksort($articles, fn($a, $b) => strcmp(($customers[$a] ?? '') . $articles[$a], ($customers[$b] ?? '') . $articles[$b]));
            $g['articles'] = $articles;
            $g['dates'] = $this->sortDates(array_keys($g['dates']));
            $g['matrix'] = $g['matrix'] ?? [];
            $g['plan'] = $g['plan'] ?? [];
        }
        uasort($data, fn($a, $b) => strcmp($a['name'], $b['name']));
        return $data;
    }

    // Heatmap hari-dalam-minggu x minggu-ke-N (relatif ke tanggal awal periode), persis pola
    // kalender kontribusi: baris Minggu di atas, Senin di bawah. Nilai tiap sel = Balance
    // (Actual - Plan, Actual exclude DN Replace) supaya warnanya menunjukkan performa kirim
    // terhadap rencana (kurang/sesuai/lebih), bukan volume mentah. Sel tanpa plan maupun
    // actual sama sekali dibiarkan null (kosong, tidak ikut dihitung sebagai "sesuai plan").
    private function heatmapSeries(array $actualRows, array $planRows, $from)
    {
        $dayNames = ['Senin', 'Selasa', 'Rabu', 'Kamis', "Jum'at", 'Sabtu', 'Minggu'];

        $dailyActual = [];
        foreach ($actualRows as $r) {
            if ($r->type === 'DN REPLACE') {
                continue;
            }
            $iso = implode('-', array_reverse(explode('-', $r->delivery_date)));
            $dailyActual[$iso] = ($dailyActual[$iso] ?? 0) + (float) $r->qty;
        }

        $dailyPlan = [];
        foreach ($planRows as $r) {
            $iso = implode('-', array_reverse(explode('-', $r->delivery_date)));
            $dailyPlan[$iso] = ($dailyPlan[$iso] ?? 0) + (float) $r->qty;
        }

        $allIso = array_unique(array_merge(array_keys($dailyActual), array_keys($dailyPlan)));

        $fromTs = strtotime($from);
        $grid = [];
        $maxWeek = 1;
        foreach ($allIso as $iso) {
            $balance = ($dailyActual[$iso] ?? 0) - ($dailyPlan[$iso] ?? 0);
            $ts = strtotime($iso);
            $week = (int) floor(($ts - $fromTs) / 86400 / 7) + 1;
            $dayIndex = ((int) date('N', $ts)) - 1; // 0=Senin..6=Minggu
            $grid[$dayIndex][$week] = ($grid[$dayIndex][$week] ?? 0) + $balance;
            $maxWeek = max($maxWeek, $week);
        }

        $series = [];
        foreach ([6, 5, 4, 3, 2, 1, 0] as $dayIndex) {
            $points = [];
            for ($w = 1; $w <= $maxWeek; $w++) {
                $points[] = ['x' => "Minggu ke-$w", 'y' => array_key_exists($w, $grid[$dayIndex] ?? []) ? round($grid[$dayIndex][$w], 2) : null];
            }
            $series[] = ['name' => $dayNames[$dayIndex], 'data' => $points];
        }
        return $series;
    }

    // Plan berjalan: Balance (Actual - Plan) tanggal sebelumnya menambah/mengurangi Plan
    // tanggal berikutnya, dihitung berurutan sejak ROLLING_PLAN_ANCHOR (bukan cuma dalam
    // rentang $from-$to) supaya carry-nya tidak hilang saat filter tanggal dipersempit.
    // Plan asli di delivery_schedule_det/SO tidak diubah, ini cuma nilai tampilan.
    private function rollingPlans(array $anchoredActualRows, array $anchoredPlanRows, $summary)
    {
        $key = function ($r) use ($summary) {
            $g = $summary ? 'ALL' : $r->customer_id;
            $row = $summary ? $r->customer_id . '|' . $r->article_code : $r->article_code;
            return [$g, $row];
        };
        $toIso = fn($d) => implode('-', array_reverse(explode('-', $d)));

        $actual = [];
        foreach ($anchoredActualRows as $r) {
            if ($r->type === 'DN REPLACE') {
                continue;
            }
            [$g, $row] = $key($r);
            $iso = $toIso($r->delivery_date);
            $actual[$g][$row][$iso] = ($actual[$g][$row][$iso] ?? 0) + (float) $r->qty;
        }
        $plan = [];
        foreach ($anchoredPlanRows as $r) {
            [$g, $row] = $key($r);
            $iso = $toIso($r->delivery_date);
            $plan[$g][$row][$iso] = ($plan[$g][$row][$iso] ?? 0) + (float) $r->qty;
        }

        $adjusted = [];
        foreach (array_unique(array_merge(array_keys($actual), array_keys($plan))) as $g) {
            $rows = array_unique(array_merge(array_keys($actual[$g] ?? []), array_keys($plan[$g] ?? [])));
            foreach ($rows as $row) {
                $isoDates = array_unique(array_merge(
                    array_keys($actual[$g][$row] ?? []),
                    array_keys($plan[$g][$row] ?? [])
                ));
                sort($isoDates);
                $carry = 0;
                foreach ($isoDates as $iso) {
                    $adjPlan = ($plan[$g][$row][$iso] ?? 0) + $carry;
                    $carry = $adjPlan - ($actual[$g][$row][$iso] ?? 0);
                    $adjusted[$g][$row][$toIso($iso)] = $adjPlan;
                }
            }
        }
        return $adjusted;
    }

    private function viewData(Request $request)
    {
        $data['from'] = $request->from ?: date('Y-m-01');
        $data['to'] = $request->to ?: date('Y-m-t');
        $data['selected'] = array_filter((array) $request->customer);
        $data['selectedArticles'] = array_filter((array) $request->article);
        $data['dnType'] = $this->dnType($request);
        $data['summary'] = $request->view == 'summary';

        $allRows = $this->rows($request, true);
        $articleOptions = [];
        foreach ($allRows as $r) {
            $articleOptions[$r->article_code] = trim(($r->article_alternative_code ?? $r->article_code) . ' - ' . $r->article_desc, ' -');
        }
        ksort($articleOptions);
        $data['articleOptions'] = $articleOptions;

        $rows = $data['selectedArticles']
            ? array_values(array_filter($allRows, fn($r) => in_array($r->article_code, $data['selectedArticles'])))
            : $allRows;
        $planRows = $this->planRows($request);
        $data['groups'] = $this->groups($rows, $planRows, $data['summary']);
        $data['heatmap'] = $this->heatmapSeries($rows, $planRows, $data['from']);

        $anchoredActualRows = $this->rows($request, false, true);
        $anchoredPlanRows = $this->planRows($request, true);
        $adjustedPlan = $this->rollingPlans($anchoredActualRows, $anchoredPlanRows, $data['summary']);
        foreach ($data['groups'] as $gKey => &$g) {
            foreach ($g['articles'] as $rowKey => $name) {
                foreach ($adjustedPlan[$gKey][$rowKey] ?? [] as $d => $v) {
                    if (in_array($d, $g['dates'], true)) {
                        $g['plan'][$rowKey][$d] = $v;
                    }
                }
            }
        }
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
