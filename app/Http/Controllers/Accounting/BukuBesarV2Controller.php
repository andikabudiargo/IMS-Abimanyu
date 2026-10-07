<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use Illuminate\Support\Facades\Schema;

ini_set('memory_limit', '1024M');
set_time_limit(300);

/**
 * Buku Besar v2.
 *
 * Kolom tabel sama dengan Buku Besar v1. Bedanya, laporan disusun per COA:
 *
 *   [banner]   hanya kalau COA yang dipilih adalah HEADER (nomor, nama, range rincian)
 *   [group]    sub header per COA (nomor + nama; kalau COA itu HEADER, plus range rincian)
 *   [opening]  Saldo Awal
 *   [trx...]   mutasi
 *   [total]    Total Mutasi (debet & kredit)
 *   [closing]  Saldo Akhir
 *
 * Saldo awal sebuah COA pada tanggal T:
 *   saldo_awal(T) = saldo_normal * accounts.opening_balance
 *                 + SUM(debit - credit) untuk voucher_date di [GL_EPOCH, T-1]
 *
 * Konvensi internal: positif = debit, negatif = kredit. Nilai ditampilkan di
 * kolom Debet atau Kredit sesuai tandanya.
 *
 * "Kelompok" = nama COA HEADER terdekat di atas akun tersebut (berdasarkan
 * prefix kode), bukan lagi mapping digit pertama.
 */
class BukuBesarV2Controller extends Controller
{
    /** Tanggal mulai pembukuan yang diakui. Mutasi sebelum ini diabaikan. */
    const GL_EPOCH = '2024-01-01';

    /** Status voucher yang tidak pernah ikut hitung (5 = deleted). */
    const STATUS_EXCLUDED = ['5'];

    const STATUS_LABEL = [
        '1' => 'NEW',
        '2' => 'VALIDATED',
        '3' => 'APPROVED',
        '5' => 'DELETED',
        '6' => 'PAID',
    ];

    const BULAN = [
        1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL',
        5 => 'MEI', 6 => 'JUNI', 7 => 'JULI', 8 => 'AGUSTUS',
        9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER',
    ];

    /** Tabel master tipe akun (accounts.type_code -> acc_types.code). */
    const ACC_TYPE_TABLE = 'acc_types';

    /** Tabel master sub akun (kolom: type_code, sub_code, description). accounts.parent_id -> sub_code. */
    const ACC_SUB_TABLE = 'acc_sub';

    private $title = "Buku Besar v2";

    /** Kolom tabel -- urutan & judul mengikuti Buku Besar v1. */
    public function getTableColoumn()
    {
        $kolom = [
            ['data' => 'nama_dept',      'name' => 'nama_dept',      'title' => 'Cost Center'],
            ['data' => 'account',        'name' => 'account',        'title' => 'Account'],
            ['data' => 'nama_akun',      'name' => 'nama_akun',      'title' => 'Account Name'],
            ['data' => 'reference',      'name' => 'reference',      'title' => 'Reference'],
            ['data' => 'voucher_number', 'name' => 'voucher_number', 'title' => 'Voucher Number'],
            ['data' => 'description',    'name' => 'description',    'title' => 'Description'],
            ['data' => 'voucher_date',   'name' => 'voucher_date',   'title' => 'Date'],
            ['data' => 'period',         'name' => 'period',         'title' => 'Period'],
            ['data' => 'debit',          'name' => 'debit',          'title' => 'Debet'],
            ['data' => 'credit',         'name' => 'credit',         'title' => 'Kredit'],
            ['data' => 'statusku',       'name' => 'statusku',       'title' => 'Status'],
            ['data' => 'created_by',     'name' => 'created_by',     'title' => 'Created By'],
            ['data' => 'created_at',     'name' => 'created_at',     'title' => 'Created At'],
            ['data' => 'approval_by',    'name' => 'approval_by',    'title' => 'Approve By'],
            ['data' => 'approval_at',    'name' => 'approval_at',    'title' => 'Approve At'],
        ];

        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $data['title'] = $this->title;
        $data['kolom'] = $this->getTableColoumn();

        // HEADER ikut tampil: memilihnya = menarik transaksi semua COA di bawahnya.
        $data['accounts'] = DB::table('accounts')
            ->orderBy(DB::raw("string_to_array(account,'.')::int[]"))
            ->get();

        $data['accTypes'] = DB::table(self::ACC_TYPE_TABLE)
            ->orderBy(DB::raw("string_to_array(code,'.')::int[]"))
            ->get();

        $data['depts'] = DB::table('depts')->orderBy('name')->get();
        $data['status'] = ['1' => 'NEW', '2' => 'VALIDATED', '3' => 'APPROVED', '6' => 'PAID'];
        $data['tahunAwal'] = (int) substr(self::GL_EPOCH, 0, 4);
        $data['tahunIni'] = (int) date('Y');

        return view("accounting.bukuBesarV2.index", $data);
    }

    /**
     * Rentang tanggal efektif laporan (Y-m-d). Dasarnya Tahun + Periode
     * Awal/Akhir; rentang flatpickr -- kalau diisi -- menimpanya.
     */
    private function resolveRange(Request $request)
    {
        $year = (int) ($request->tahun ?: date('Y'));
        $p1 = max(1, min(12, (int) ($request->period1 ?: 1)));
        $p2 = max(1, min(12, (int) ($request->period2 ?: 12)));
        if ($p2 < $p1) {
            $p2 = $p1;
        }

        $from = sprintf('%04d-%02d-01', $year, $p1);
        $to = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $p2)));

        // Flatpickr mengirim "dd-mm-yyyy to dd-mm-yyyy" (satu tanggal = sehari).
        if ($request->vcDate) {
            $parts = array_map('trim', explode('to', $request->vcDate));
            $f = $this->toIsoDate($parts[0]);
            $t = isset($parts[1]) ? $this->toIsoDate($parts[1]) : $f;
            if ($f && $t) {
                $from = $f;
                $to = $t;
            }
        }

        return [$from, $to, $year, $p1, $p2];
    }

    /** "dd-mm-yyyy" -> "yyyy-mm-dd", null kalau bukan tanggal. */
    private function toIsoDate($raw)
    {
        if (!preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $raw, $m)) {
            return null;
        }

        return checkdate((int) $m[2], (int) $m[1], (int) $m[3]) ? "$m[3]-$m[2]-$m[1]" : null;
    }

    /**
     * COA yang ikut dihitung (lengkap dengan data akunnya). Akun DETAIL cuma
     * dirinya sendiri; HEADER = dirinya + seluruh turunan.
     *
     * Hubungan induk-anak diambil dari PREFIX kode ("2000.14" -> "2000.14.*"),
     * bukan dari accounts.parent_id (isinya tidak konsisten).
     */
    private function loadAccounts($acc)
    {
        $q = DB::table('accounts')
            ->select('account', 'description', 'acc_header', 'debit_credit', 'opening_balance');

        if (strtoupper($acc->acc_header) !== 'HEADER') {
            $q->where('account', $acc->account);
        } else {
            $like = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $acc->account) . '.%';
            $q->where(function ($w) use ($acc, $like) {
                $w->where('account', $acc->account)->orWhere('account', 'like', $like);
            });
        }

        return $q->orderBy(DB::raw("string_to_array(account,'.')::int[]"))->get();
    }

    /** Query mutasi dengan filter yang identik untuk saldo awal maupun baris. */
    private function movementQuery(array $accounts, Request $request)
    {
        $q = DB::table('kas_det as d')
            ->join('kas_hdr as h', 'h.voucher_number', 'd.voucher_number')
            ->whereIn('d.account', $accounts)
            ->whereNotIn('h.status', self::STATUS_EXCLUDED);

        if ($request->searchStatus) {
            $q->where('h.status', $request->searchStatus);
        }
        if ($request->dept) {
            $q->whereIn('d.cost_center', (array) $request->dept);
        }

        return $q;
    }

    /**
     * "kode - nama" COA HEADER terdekat di atas $code. Urutan pencarian:
     * 1) leluhur berdasarkan prefix kode yang bertanda HEADER,
     * 2) leluhur berdasarkan prefix kode apa pun jenisnya,
     * 3) Sub Akun lewat accounts.parent_id, hanya kalau sub itu bertipe sama
     *    dengan akunnya (parent_id kadang salah menunjuk sub milik tipe lain),
     * 4) Tipe Akun (acc_types) dari accounts.type_code,
     * 5) akun itu sendiri kalau ia HEADER.
     */
    private function kelompokOf($code, array $map, array $types = [], array $subs = [])
    {
        $fmt = function ($c) use ($map) {
            return $c . ' - ' . $map[$c]->description;
        };
        $isHdr = function ($c) use ($map) {
            return strtoupper(trim((string) $map[$c]->acc_header)) === 'HEADER';
        };

        $code = trim($code);
        $parts = explode('.', $code);
        $any = null;
        for ($i = count($parts) - 1; $i >= 1; $i--) {
            $prefix = implode('.', array_slice($parts, 0, $i));
            if (isset($map[$prefix])) {
                if ($isHdr($prefix)) {
                    return $fmt($prefix);
                }
                if ($any === null) {
                    $any = $prefix;
                }
            }
        }
        if ($any !== null) {
            return $fmt($any);
        }

        // Tidak ada header di prefix: coba Sub Akun, lalu Tipe Akun.
        $tc = isset($map[$code]) ? trim((string) $map[$code]->type_code) : '';
        $pid = isset($map[$code]) ? trim((string) $map[$code]->parent_id) : '';
        if ($pid !== '' && isset($subs[$pid]) && trim((string) $subs[$pid]->type_code) === $tc) {
            return $pid . ' - ' . $subs[$pid]->description;
        }
        if ($tc !== '' && isset($types[$tc])) {
            return $tc . ' - ' . $types[$tc];
        }

        if (isset($map[$code]) && $isHdr($code)) {
            return $fmt($code);
        }

        return '-';
    }

    /** Range rincian sebuah HEADER: [pertama, terakhir, jumlah] akun DETAIL di bawahnya, atau null. */
    private function detailRange($list, $code)
    {
        $details = [];
        foreach ($list as $a) {
            if (strtoupper($a->acc_header) !== 'HEADER' && strpos($a->account, $code . '.') === 0) {
                $details[] = $a->account;
            }
        }

        return $details ? [$details[0], end($details), count($details)] : null;
    }

    private function rangeText($range)
    {
        if (!$range) {
            return '';
        }

        return $range[2] > 1
            ? $range[0] . ' s/d ' . $range[1] . ' (' . $range[2] . ' COA)'
            : $range[0];
    }

    /** Satu baris tabel dengan semua kolom terisi default. */
    private function blankRow(array $o = [])
    {
        return array_merge([
            'nama_dept'      => '',
            'account'        => '',
            'nama_akun'      => '',
            'reference'      => '',
            'voucher_number' => '',
            'description'    => '',
            'voucher_date'   => '',
            'period'         => '',
            'debit'          => null,
            'credit'         => null,
            'statusku'       => '',
            'created_by'     => '',
            'created_at'     => '',
            'approval_by'    => '',
            'approval_at'    => '',
            'row_type'       => 'trx',   // banner | group | opening | trx | total | closing
            'is_summary'     => false,
        ], $o);
    }

    public function data(Request $request)
    {
        $account = $request->account;
        $typeCode = $request->type_code;
        if (!$account && !$typeCode) {
            return response()->json(['error' => 'COA atau Tipe Akun belum dipilih.'], 422);
        }

        list($from, $to, $year, $p1, $p2) = $this->resolveRange($request);

        $typeMode = false;
        if ($account) {
            $acc = DB::table('accounts')->where('account', $account)->first();
            if (!$acc) {
                return response()->json(['error' => "COA $account tidak ditemukan."], 404);
            }
            $list = $this->loadAccounts($acc);
            $isHeader = strtoupper($acc->acc_header) === 'HEADER';
        } else {
            // Mode Tipe Akun: seluruh COA DETAIL bertipe itu digabung dalam satu blok.
            $type = DB::table(self::ACC_TYPE_TABLE)->where('code', $typeCode)->first();
            if (!$type) {
                return response()->json(['error' => "Tipe akun $typeCode tidak ditemukan."], 404);
            }
            $list = DB::table('accounts')
                ->select('account', 'description', 'acc_header', 'debit_credit', 'opening_balance')
                ->where('type_code', $typeCode)
                ->whereRaw("coalesce(upper(acc_header),'') <> 'HEADER'")
                ->orderBy(DB::raw("string_to_array(account,'.')::int[]"))
                ->get();
            if ($list->isEmpty()) {
                return response()->json(['error' => "Tidak ada COA pada tipe akun $typeCode."], 404);
            }
            $acc = (object) [
                'account'      => $type->code,
                'description'  => $type->name,
                'acc_header'   => 'HEADER',
                'debit_credit' => $list[0]->debit_credit,
            ];
            $isHeader = true;
            $typeMode = true;
        }

        $codes = $list->pluck('account')->all();
        // Seluruh akun (ringan) untuk mencari header induk.
        $headers = DB::table('accounts')
            ->select('account', 'description', 'acc_header', 'type_code', 'parent_id')
            ->get()
            ->keyBy('account')
            ->all();
        $types = DB::table(self::ACC_TYPE_TABLE)->pluck('name', 'code')->all();
        // Sub akun bersifat opsional: kalau nama tabelnya belum cocok, langkah ini dilewati.
        $subs = Schema::hasTable(self::ACC_SUB_TABLE)
            ? DB::table(self::ACC_SUB_TABLE)->select('sub_code', 'type_code', 'description')->get()->keyBy('sub_code')->all()
            : [];

        $kelompok = $typeMode
            ? $acc->account . ' - ' . $acc->description
            : $this->kelompokOf($acc->account, $headers, $types, $subs);
        $range = $typeMode
            ? $this->rangeText([$list[0]->account, $list[count($list) - 1]->account, count($list)])
            : ($isHeader ? $this->rangeText($this->detailRange($list, $acc->account)) : '');

        // Mutasi sejak GL_EPOCH s/d sehari sebelum $from, per COA.
        $prior = [];
        if ($from > self::GL_EPOCH) {
            $prior = $this->movementQuery($codes, $request)
                ->whereRaw("to_date(h.voucher_date,'DD-MM-YYYY') >= ?", [self::GL_EPOCH])
                ->whereRaw("to_date(h.voucher_date,'DD-MM-YYYY') < ?", [$from])
                ->groupBy('d.account')
                ->selectRaw('d.account as account, sum(d.debit - d.credit) as net')
                ->pluck('net', 'account')
                ->all();
        }

        $trxRows = $this->movementQuery($codes, $request)
            ->leftJoin('depts', 'depts.code', 'd.cost_center')
            ->leftJoin('accounts', 'accounts.account', 'd.account')
            ->whereRaw("to_date(h.voucher_date,'DD-MM-YYYY') between ? and ?", [$from, $to])
            ->select(
                // Sama seperti Buku Besar v1: cost center kosong dianggap dept 007.
                DB::raw("coalesce(nullif(depts.name,''),(select name from depts where code='007'),'-') as nama_dept"),
                'd.account',
                'accounts.description as nama_akun',
                'd.reference',
                'd.voucher_number',
                'd.description',
                DB::raw("to_char(to_date(h.voucher_date,'DD-MM-YYYY'),'DD-MM-YYYY') as voucher_date"),
                DB::raw("to_date(h.voucher_date,'DD-MM-YYYY') as voucher_date_2"),
                'h.period',
                'd.debit',
                'd.credit',
                'h.status',
                'h.created_by',
                DB::raw("to_char(h.created_at,'DD-MM-YYYY HH24:MI') as created_at"),
                DB::raw("(select username from approval_history where module_number = d.voucher_number order by approval_order desc limit 1) as approval_by"),
                DB::raw("(select to_char(approval_date::date,'DD-MM-YYYY') from approval_history where module_number = d.voucher_number order by approval_order desc limit 1) as approval_at")
            )
            ->orderBy('voucher_date_2')
            ->orderBy(DB::raw("string_to_array(d.account,'.')::int[]"))
            ->orderBy('d.id')
            ->get();

        $sign = strtoupper($acc->debit_credit) === 'KREDIT' ? -1 : 1;

        // Satu blok saja: untuk HEADER, saldo awal = jumlah seluruh COA di bawahnya
        // dan transaksinya digabung (kolom Account membedakan asal COA-nya).
        $grandOpening = 0;
        foreach ($list as $a) {
            $sgn = strtoupper($a->debit_credit) === 'KREDIT' ? -1 : 1;
            $grandOpening += $sgn * (float) $a->opening_balance + (float) ($prior[$a->account] ?? 0);
        }

        $out = [];
        $out[] = $this->blankRow([
            'row_type'   => 'group',
            'is_summary' => true,
            'account'    => $acc->account,
            'nama_akun'  => $acc->description,
            'g_kelompok' => $kelompok,
            'g_normal'   => $sign === -1 ? 'KREDIT' : 'DEBET',
            'g_range'    => $range,
            'g_tag'      => $typeMode ? 'TIPE AKUN' : 'HEADER',
        ]);
        $out[] = $this->blankRow([
            'row_type'    => 'opening',
            'is_summary'  => true,
            'description' => 'SALDO AWAL',
            's_label'     => 'Saldo Awal',
            's_note'      => 'per ' . date('d-m-Y', strtotime($from . ' -1 day')),
            'debit'       => $grandOpening >= 0 ? $grandOpening : null,
            'credit'      => $grandOpening < 0 ? -$grandOpening : null,
        ]);

        $grandDebit = 0;
        $grandCredit = 0;
        foreach ($trxRows as $r) {
            $grandDebit += (float) $r->debit;
            $grandCredit += (float) $r->credit;

            $out[] = $this->blankRow([
                'nama_dept'      => $r->nama_dept,
                'account'        => $r->account,
                'nama_akun'      => $r->nama_akun,
                'reference'      => $r->reference,
                'voucher_number' => $r->voucher_number,
                'description'    => $r->description,
                'voucher_date'   => $r->voucher_date,
                'period'         => $r->period,
                'debit'          => (float) $r->debit,
                'credit'         => (float) $r->credit,
                'statusku'       => self::STATUS_LABEL[$r->status] ?? $r->status,
                'created_by'     => $r->created_by,
                'created_at'     => $r->created_at,
                'approval_by'    => $r->approval_by,
                'approval_at'    => $r->approval_at,
            ]);
        }

        // Footer: satu baris Saldo Akhir. Debet/Kredit = jumlah kolomnya (termasuk
        // saldo awal di atas), hasilnya = Debet - Kredit.
        $sumDebit = ($grandOpening > 0 ? $grandOpening : 0) + $grandDebit;
        $sumCredit = ($grandOpening < 0 ? -$grandOpening : 0) + $grandCredit;
        $closing = $sumDebit - $sumCredit;

        $out[] = $this->blankRow([
            'row_type'    => 'closing',
            'is_summary'  => true,
            'description' => 'SALDO AKHIR',
            's_label'     => 'Saldo Akhir',
            's_note'      => 'per ' . date('d-m-Y', strtotime($to)),
            'debit'       => $sumDebit,
            'credit'      => $sumCredit,
            'statusku'    => number_format(abs($closing), 2, ',', '.') . ($closing >= 0 ? ' (DEBET)' : ' (KREDIT)'),
        ]);

        $grandClosing = $grandOpening + $grandDebit - $grandCredit;
        return response()->json([
            'header' => [
                'account'      => $acc->account,
                'description'  => $acc->description,
                'kelompok'     => $kelompok,
                'saldo_normal' => $sign === -1 ? 'KREDIT' : 'DEBET',
                'is_header'    => $isHeader,
                'tag'          => $typeMode ? 'TIPE AKUN' : ($isHeader ? 'HEADER' : ''),
                'coa_count'    => count($list),
                'tahun'        => $year,
                'periode'      => $p1 === $p2 ? (string) $p1 : "$p1 s/d $p2",
                'periode_text' => $this->periodeText($from, $to),
                'date_from'    => date('d-m-Y', strtotime($from)),
                'date_to'      => date('d-m-Y', strtotime($to)),
                'opening'      => $grandOpening,
                'total_debit'  => $grandDebit,
                'total_credit' => $grandCredit,
                'closing'      => $grandClosing,
                'jumlah_trx'   => count($trxRows),
            ],
            'rows' => $out,
        ]);
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