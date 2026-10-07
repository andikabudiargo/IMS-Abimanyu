<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;

ini_set('memory_limit', '1024M');
set_time_limit(300);

/**
 * Budgeting.
 *
 * Sumber data : kas_det + kas_hdr (sama seperti Buku Besar v2), status DELETED diabaikan.
 * COA         : hanya akun DETAIL yang segmen pertama kodenya ada di COA_RANGES
 *               (1000-1999, 5000-5999, 8000-8999).
 * Pengelompokan: per department (cost center kosong dianggap dept 007, seperti Buku Besar).
 *
 * Per department + COA:
 *   debit    = jumlah kolom debit di seluruh periode terpilih
 *   average  = debit / jumlah bulan yang debitnya TIDAK nol
 *              (bulan dengan debit 0 tidak ikut dibagi)
 *   budget   = average - cost reduction %  (cost reduction default 5%, diubah di layar)
 *   final    = default sama dengan budget, bisa diedit di layar
 */
class BudgetingController extends Controller
{
    /** Range segmen pertama kode COA yang ikut budgeting [dari, sampai]. */
    const COA_RANGES = [[1000, 1999], [5000, 5999], [8000, 8999]];

    /** Status voucher yang tidak pernah ikut hitung (5 = deleted). */
    const STATUS_EXCLUDED = ['5'];

    /** Cost center kosong dianggap dept ini (sama dengan Buku Besar). */
    const DEFAULT_DEPT = '007';

    const DEFAULT_COST_REDUCTION = 5;

    const BULAN = [
        1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL',
        5 => 'MEI', 6 => 'JUNI', 7 => 'JULI', 8 => 'AGUSTUS',
        9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER',
    ];

    private $title = "Budgeting";

    public function index(Request $request)
    {
        $data['title'] = $this->title;
        $data['depts'] = DB::table('depts')->orderBy('name')->get();

        // HEADER ikut tampil: memilihnya = menarik seluruh COA di bawahnya.
        $coa = DB::table('accounts')->select('account', 'description', 'acc_header');
        $this->applyCoaRange($coa, 'account');
        $data['accounts'] = $coa->orderBy(DB::raw("string_to_array(account,'.')::int[]"))->get();

        $data['crDefault'] = self::DEFAULT_COST_REDUCTION;
        // Default: Januari tahun ini s/d bulan ini.
        $data['periodeDefault'] = sprintf('01-%04d to %02d-%04d', date('Y'), date('n'), date('Y'));

        return view("accounting.budgeting.index", $data);
    }

    public function data(Request $request)
    {
        $periode = $this->resolvePeriode($request->periode);
        if (!$periode) {
            return response()->json(['error' => 'Format periode harus MM-YYYY to MM-YYYY.'], 422);
        }
        list($from, $to, $months) = $periode;

        $q = DB::table('kas_det as d')
            ->join('kas_hdr as h', 'h.voucher_number', 'd.voucher_number')
            ->leftJoin('accounts as a', 'a.account', 'd.account')
            ->whereNotIn('h.status', self::STATUS_EXCLUDED)
            ->whereRaw("to_date(h.voucher_date,'DD-MM-YYYY') between ? and ?", [$from, $to])
            ->whereRaw("coalesce(upper(a.acc_header),'') <> 'HEADER'");
        $this->applyCoaRange($q, 'd.account');

        // Filter department (multi). Cost center kosong = dept default.
        $depts = array_values(array_filter((array) $request->dept, 'strlen'));
        if ($depts) {
            $in = implode(',', array_fill(0, count($depts), '?'));
            $q->whereRaw("coalesce(nullif(d.cost_center,''),?) in ($in)", array_merge([self::DEFAULT_DEPT], $depts));
        }

        // Filter COA (multi). COA HEADER = dirinya + seluruh turunan (prefix kode).
        $coas = array_values(array_filter((array) $request->coa, 'strlen'));
        if ($coas) {
            $q->where(function ($w) use ($coas) {
                foreach ($coas as $c) {
                    $like = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $c) . '.%';
                    $w->orWhere('d.account', $c)->orWhere('d.account', 'like', $like);
                }
            });
        }

        // Debit per department + COA + bulan. Bulan tanpa debit tidak muncul sama sekali.
        $rows = $q->select(
                DB::raw("coalesce(nullif(d.cost_center,''),'" . self::DEFAULT_DEPT . "') as dept_code"),
                'd.account',
                'a.description as nama_akun',
                DB::raw("to_char(to_date(h.voucher_date,'DD-MM-YYYY'),'YYYY-MM') as ym"),
                DB::raw('sum(d.debit) as debit')
            )
            ->groupBy(DB::raw('1, 2, 3, 4'))
            ->havingRaw('sum(d.debit) <> 0')
            ->get();

        $deptNames = DB::table('depts')->pluck('name', 'code')->all();

        $agg = [];
        foreach ($rows as $r) {
            $dc = trim((string) $r->dept_code);
            $acc = (string) $r->account;
            if (!isset($agg[$dc][$acc])) {
                $agg[$dc][$acc] = ['nama' => $r->nama_akun, 'months' => []];
            }
            $agg[$dc][$acc]['months'][$r->ym] = ($agg[$dc][$acc]['months'][$r->ym] ?? 0) + (float) $r->debit;
        }

        $groups = [];
        foreach ($agg as $dc => $accounts) {
            uksort($accounts, 'strnatcmp');

            $list = [];
            foreach ($accounts as $acc => $info) {
                $total = array_sum($info['months']);
                // Hanya bulan yang debitnya tidak nol yang jadi pembagi.
                $active = count(array_filter($info['months'], function ($v) {
                    return abs($v) > 0.004;
                }));

                $list[] = [
                    'account'       => (string) $acc,
                    'nama_akun'     => $info['nama'],
                    'debit'         => round($total, 2),
                    'average'       => $active > 0 ? round($total / $active, 2) : 0,
                    'active_months' => $active,
                ];
            }

            $groups[] = [
                'dept_code' => (string) $dc,
                'dept_name' => $deptNames[$dc] ?? (string) $dc,
                'rows'      => $list,
            ];
        }
        usort($groups, function ($a, $b) {
            return strcasecmp($a['dept_name'], $b['dept_name']);
        });

        return response()->json([
            'header' => [
                'periode_text'   => $this->periodeText($from, $to),
                'jumlah_periode' => count($months),
                'cost_reduction' => self::DEFAULT_COST_REDUCTION,
            ],
            'groups' => $groups,
        ]);
    }

    /* ====================================================================
     |  Helper
     * ================================================================== */

    /** Batasi kolom kode akun ke segmen pertama dalam COA_RANGES (1000-1999, 5000-5999, 8000-8999). */
    private function applyCoaRange($query, $col)
    {
        $query->where(function ($w) use ($col) {
            foreach (self::COA_RANGES as $r) {
                $w->orWhereRaw(
                    "(case when split_part($col,'.',1) ~ '^[0-9]+\$' then split_part($col,'.',1)::int end) between ? and ?",
                    [$r[0], $r[1]]
                );
            }
        });
    }

    /**
     * "MM-YYYY to MM-YYYY" (atau satu bulan saja) -> [tanggal awal, tanggal akhir, daftar 'YYYY-MM'].
     * Kosong = Januari s/d bulan ini tahun berjalan. Null kalau formatnya salah.
     */
    private function resolvePeriode($raw)
    {
        $raw = trim((string) $raw);

        if ($raw === '') {
            $a = [1, (int) date('Y')];
            $b = [(int) date('n'), (int) date('Y')];
        } else {
            $parts = array_map('trim', explode('to', $raw));
            $a = $this->parseMonth($parts[0]);
            $b = (isset($parts[1]) && $parts[1] !== '') ? $this->parseMonth($parts[1]) : $a;
            if (!$a || !$b) {
                return null;
            }
        }

        if ($a[1] * 12 + $a[0] > $b[1] * 12 + $b[0]) {
            list($a, $b) = [$b, $a];
        }

        $from = sprintf('%04d-%02d-01', $a[1], $a[0]);
        $to = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $b[1], $b[0])));

        $months = [];
        $y = $a[1];
        $m = $a[0];
        while ($y * 12 + $m <= $b[1] * 12 + $b[0]) {
            $months[] = sprintf('%04d-%02d', $y, $m);
            if (++$m > 12) {
                $m = 1;
                $y++;
            }
        }

        return [$from, $to, $months];
    }

    /** "MM-YYYY" -> [bulan, tahun], null kalau bukan bulan yang valid. */
    private function parseMonth($raw)
    {
        if (!preg_match('/^(\d{1,2})-(\d{4})$/', trim($raw), $m)) {
            return null;
        }

        return ((int) $m[1] >= 1 && (int) $m[1] <= 12) ? [(int) $m[1], (int) $m[2]] : null;
    }

    private function periodeText($from, $to)
    {
        $f = strtotime($from);
        $t = strtotime($to);
        $fm = self::BULAN[(int) date('n', $f)] . ' ' . date('Y', $f);
        $tm = self::BULAN[(int) date('n', $t)] . ' ' . date('Y', $t);

        return $fm === $tm ? $fm : "$fm s/d $tm";
    }
}