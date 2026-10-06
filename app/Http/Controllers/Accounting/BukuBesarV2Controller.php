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
 * Saldo awal sebuah COA pada tanggal T dihitung dinamis:
 *   saldo_awal(T) = saldo_normal * accounts.opening_balance
 *                 + SUM(debit - credit) untuk voucher_date di [GL_EPOCH, T-1]
 *
 * accounts.opening_balance selalu disimpan positif sebagai saldo di sisi
 * normalnya, jadi akun KREDIT dibalik tandanya supaya semua perhitungan
 * memakai satu konvensi: positif = debit, negatif = kredit.
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

    public function index(Request $request)
    {
        $data['title'] = $this->title;

        $data['accounts'] = DB::table('accounts')
            ->where('acc_header', '!=', 'HEADER')
            ->where('status', '1')
            ->orderBy(DB::raw("replace(account,'.','')"))
            ->get();

        $data['depts'] = DB::table('depts')->orderBy('name')->get();
        $data['bulan'] = self::BULAN;
        $data['status'] = ['1' => 'NEW', '2' => 'VALIDATED', '3' => 'APPROVED', '6' => 'PAID'];
        $data['tahunAwal'] = (int) substr(self::GL_EPOCH, 0, 4);
        $data['tahunIni'] = (int) date('Y');

        return view("accounting.bukuBesarV2.index", $data);
    }

    /**
     * Rentang tanggal efektif laporan.
     * Mode "ytd" = 1 Januari s/d akhir bulan terpilih, "month" = bulan itu saja.
     * dateFrom/dateTo dari form mempersempit hasil, tapi saldo awal tetap
     * dihitung dari tanggal mulai yang dipakai.
     */
    private function resolveRange(Request $request)
    {
        $year  = (int) ($request->tahun ?: date('Y'));
        $month = (int) ($request->bulan ?: date('n'));
        $month = max(1, min(12, $month));

        $from = $request->mode === 'month'
            ? sprintf('%04d-%02d-01', $year, $month)
            : sprintf('%04d-01-01', $year);
        $to = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $month)));

        // Override manual kalau user isi rentang tanggal sendiri.
        if ($request->dateFrom) {
            $from = $request->dateFrom;
        }
        if ($request->dateTo) {
            $to = $request->dateTo;
        }

        return [$from, $to, $year, $month];
    }

    /** Query mutasi dengan filter yang identik untuk saldo awal maupun baris. */
    private function movementQuery($account, Request $request)
    {
        $q = DB::table('kas_det as d')
            ->join('kas_hdr as h', 'h.voucher_number', 'd.voucher_number')
            ->where('d.account', $account)
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

        list($from, $to, $year, $month) = $this->resolveRange($request);

        $sign = strtoupper($acc->debit_credit) === 'KREDIT' ? -1 : 1;

        // Saldo awal = opening balance akun + seluruh mutasi sejak GL_EPOCH s/d sehari sebelum $from.
        $opening = $sign * (float) $acc->opening_balance;
        if ($from > self::GL_EPOCH) {
            $opening += (float) $this->movementQuery($account, $request)
                ->whereRaw("to_date(h.voucher_date,'DD-MM-YYYY') >= ?", [self::GL_EPOCH])
                ->whereRaw("to_date(h.voucher_date,'DD-MM-YYYY') < ?", [$from])
                ->sum(DB::raw('d.debit - d.credit'));
        }

        $rows = $this->movementQuery($account, $request)
            ->leftJoin('depts', 'depts.code', 'd.cost_center')
            ->whereRaw("to_date(h.voucher_date,'DD-MM-YYYY') between ? and ?", [$from, $to])
            ->select(
                DB::raw("to_char(to_date(h.voucher_date,'DD-MM-YYYY'),'DD-MM-YYYY') as tanggal"),
                DB::raw("to_date(h.voucher_date,'DD-MM-YYYY') as tanggal_sort"),
                'h.period',
                'd.voucher_number',
                'd.reference',
                'd.description',
                // Sama seperti Buku Besar v1: cost center kosong dianggap dept 007.
                DB::raw("coalesce(nullif(depts.name,''),(select name from depts where code='007'),'-') as dept"),
                'd.debit',
                'd.credit',
                'h.status'
            )
            ->orderBy('tanggal_sort')
            ->orderBy('d.id')
            ->get();

        $saldo = $opening;
        $totalDebit = 0;
        $totalCredit = 0;
        $out = [];

        foreach ($rows as $i => $r) {
            $debit = (float) $r->debit;
            $credit = (float) $r->credit;
            $saldo += $debit - $credit;
            $totalDebit += $debit;
            $totalCredit += $credit;

            $out[] = [
                'no'             => $i + 1,
                'tanggal'        => $r->tanggal,
                'period'         => $r->period,
                'voucher_number' => $r->voucher_number,
                'reference'      => $r->reference,
                'description'    => $r->description,
                'dept'           => $r->dept,
                'debit'          => $debit,
                'credit'         => $credit,
                'saldo_debit'    => $saldo >= 0 ? $saldo : null,
                'saldo_credit'   => $saldo < 0 ? -$saldo : null,
                'status'         => self::STATUS_LABEL[$r->status] ?? $r->status,
            ];
        }

        return response()->json([
            'header' => [
                'account'      => $acc->account,
                'description'  => $acc->description,
                'kelompok'     => self::KELOMPOK[substr($acc->account, 0, 1)] ?? '-',
                'saldo_normal' => strtoupper($acc->debit_credit) === 'KREDIT' ? 'KREDIT' : 'DEBET',
                'bulan'        => self::BULAN[$month],
                'mode'         => $request->mode === 'month' ? self::BULAN[$month] : 'JANUARI S/D BULAN',
                'tahun'        => $year,
                'periode_text' => $this->periodeText($from, $to),
                'date_from'    => $from,
                'date_to'      => $to,
                'opening'      => abs($opening),
                'opening_side' => $opening < 0 ? 'K' : 'D',
                'total_debit'  => $totalDebit,
                'total_credit' => $totalCredit,
                'closing'      => abs($saldo),
                'closing_side' => $saldo < 0 ? 'K' : 'D',
                'jumlah_trx'   => count($out),
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
