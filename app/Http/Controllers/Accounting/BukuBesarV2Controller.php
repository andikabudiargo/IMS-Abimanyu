<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;

ini_set('memory_limit', '1024M');
set_time_limit(300);

/**
 * Buku Besar v2.
 *
 * Kolom tabel sama persis dengan Buku Besar v1, bedanya cuma ada baris
 * SALDO AWAL di atas dan SALDO AKHIR di bawah (pola yang sama dengan
 * ArticleController::movement2 -- lihat buildSummaryRow di sana).
 *
 * Saldo awal sebuah COA pada tanggal T dihitung dinamis:
 *   saldo_awal(T) = saldo_normal * accounts.opening_balance
 *                 + SUM(debit - credit) untuk voucher_date di [GL_EPOCH, T-1]
 *
 * accounts.opening_balance selalu disimpan positif sebagai saldo di sisi
 * normalnya, jadi akun KREDIT dibalik tandanya supaya semua perhitungan
 * memakai satu konvensi internal: positif = debit, negatif = kredit.
 * Nilainya ditampilkan di kolom Debet atau Kredit sesuai tandanya.
 *
 * Transaksi sebelum GL_EPOCH tidak ikut dihitung (opening_balance sudah
 * mewakili posisi per tanggal itu).
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

    const KELOMPOK = [
        '1' => '1-ASET',
        '2' => '2-KEWAJIBAN',
        '3' => '3-MODAL',
        '4' => '4-PENDAPATAN',
        '5' => '5-HARGA POKOK PENJUALAN',
        '6' => '6-BIAYA UMUM & ADMINISTRASI',
        '7' => '7-PENDAPATAN DI LUAR USAHA',
        '8' => '8-BEBAN DI LUAR USAHA',
    ];

    const BULAN = [
        1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL',
        5 => 'MEI', 6 => 'JUNI', 7 => 'JULI', 8 => 'AGUSTUS',
        9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER',
    ];

    private $title = "Buku Besar v2";

    /** Kolom tabel -- urutan & judul mengikuti Buku Besar v1. */
    public function getTableColoumn()
    {
        $kolom = [
            ['data' => 'nama_dept',      'name' => 'nama_dept',      'title' => 'Dept'],
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

        $data['depts'] = DB::table('depts')->orderBy('name')->get();
        $data['status'] = ['1' => 'NEW', '2' => 'VALIDATED', '3' => 'APPROVED', '6' => 'PAID'];
        $data['tahunAwal'] = (int) substr(self::GL_EPOCH, 0, 4);
        $data['tahunIni'] = (int) date('Y');

        return view("accounting.bukuBesarV2.index", $data);
    }

    /**
     * Rentang tanggal efektif laporan, dalam format Y-m-d.
     *
     * Dasarnya Tahun + Periode Awal/Akhir (periode = bulan pembukuan), lalu
     * rentang tanggal dari flatpickr -- kalau diisi -- menimpanya. Saldo awal
     * selalu dihitung per sehari sebelum tanggal mulai yang dipakai di sini,
     * jadi header dan detail tidak pernah beda basis.
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
     * COA yang ikut dihitung. Untuk akun DETAIL cuma dirinya sendiri; untuk
     * HEADER, dirinya sendiri + seluruh turunannya.
     *
     * Hubungan induk-anak diambil dari PREFIX kode ("2000.14" -> "2000.14.*"),
     * bukan dari accounts.parent_id -- kolom itu isinya tidak konsisten
     * (mis. 2000.14.1 ber-parent_id 2000.10, bukan 2000.14).
     */
    private function resolveAccounts($acc)
    {
        if (strtoupper($acc->acc_header) !== 'HEADER') {
            return [$acc->account];
        }

        return DB::table('accounts')
            ->where('account', $acc->account)
            ->orWhere('account', 'like', str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $acc->account) . '.%')
            ->orderBy(DB::raw("string_to_array(account,'.')::int[]"))
            ->pluck('account')
            ->toArray();
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

    public function data(Request $request)
    {
        $account = $request->account;
        if (!$account) {
            return response()->json(['error' => 'COA belum dipilih.'], 422);
        }

        $acc = DB::table('accounts')->where('account', $account)->first();
        if (!$acc) {
            return response()->json(['error' => "COA $account tidak ditemukan."], 404);
        }

        list($from, $to, $year, $p1, $p2) = $this->resolveRange($request);

        $accounts = $this->resolveAccounts($acc);
        $sign = strtoupper($acc->debit_credit) === 'KREDIT' ? -1 : 1;

        // Saldo awal = opening balance akun + seluruh mutasi sejak GL_EPOCH s/d sehari sebelum $from.
        // Untuk HEADER, opening balance tiap COA turunan dijumlahkan dengan tandanya masing-masing.
        $opening = (float) DB::table('accounts')
            ->whereIn('account', $accounts)
            ->sum(DB::raw("case when upper(debit_credit) = 'KREDIT' then -opening_balance else opening_balance end"));

        if ($from > self::GL_EPOCH) {
            $opening += (float) $this->movementQuery($accounts, $request)
                ->whereRaw("to_date(h.voucher_date,'DD-MM-YYYY') >= ?", [self::GL_EPOCH])
                ->whereRaw("to_date(h.voucher_date,'DD-MM-YYYY') < ?", [$from])
                ->sum(DB::raw('d.debit - d.credit'));
        }

        $rows = $this->movementQuery($accounts, $request)
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
            ->orderBy('d.account')
            ->orderBy('d.id')
            ->get();

        $totalDebit = 0;
        $totalCredit = 0;
        $out = [];

        foreach ($rows as $r) {
            $totalDebit += (float) $r->debit;
            $totalCredit += (float) $r->credit;

            $out[] = [
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
                'is_summary'     => false,
            ];
        }

        $closing = $opening + $totalDebit - $totalCredit;

        // Saldo awal/akhir masuk sebagai baris tabel (atas & bawah), nilainya
        // jatuh di kolom Debet kalau positif, Kredit kalau negatif.
        array_unshift($out, $this->buildSummaryRow(
            'SALDO AWAL',
            strtoupper($this->periodeText($from, $from)) . '  (s/d ' . date('d-m-Y', strtotime($from . ' -1 day')) . ')',
            $opening
        ));
        $out[] = $this->buildSummaryRow(
            'SALDO AKHIR',
            strtoupper($this->periodeText($to, $to)) . '  (s/d ' . date('d-m-Y', strtotime($to)) . ')',
            $closing
        );

        return response()->json([
            'header' => [
                'account'      => $acc->account,
                'description'  => $acc->description,
                'kelompok'     => self::KELOMPOK[substr($acc->account, 0, 1)] ?? '-',
                'saldo_normal' => $sign === -1 ? 'KREDIT' : 'DEBET',
                'is_header'    => strtoupper($acc->acc_header) === 'HEADER',
                'coa_count'    => count($accounts),
                'tahun'        => $year,
                'periode'      => $p1 === $p2 ? (string) $p1 : "$p1 s/d $p2",
                'periode_text' => $this->periodeText($from, $to),
                'date_from'    => date('d-m-Y', strtotime($from)),
                'date_to'      => date('d-m-Y', strtotime($to)),
                'opening'      => $opening,
                'total_debit'  => $totalDebit,
                'total_credit' => $totalCredit,
                'closing'      => $closing,
                'jumlah_trx'   => count($rows),
            ],
            'rows' => $out,
        ]);
    }

    /** Baris SALDO AWAL / SALDO AKHIR -- kolom lain dikosongkan lalu di-merge di sisi view. */
    private function buildSummaryRow($label, $note, $saldo)
    {
        return [
            'nama_dept'      => '',
            'account'        => '',
            'nama_akun'      => '',
            'reference'      => '',
            'voucher_number' => '',
            'description'    => '',
            'voucher_date'   => '',
            'period'         => '',
            'debit'          => $saldo >= 0 ? $saldo : null,
            'credit'         => $saldo < 0 ? -$saldo : null,
            'statusku'       => '',
            'created_by'     => '',
            'created_at'     => '',
            'approval_by'    => '',
            'approval_at'    => '',
            'is_summary'     => true,
            'summary_type'   => $label === 'SALDO AWAL' ? 'OPENING' : 'CLOSING',
            'summary_label'  => $label,
            'summary_note'   => $note,
        ];
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
