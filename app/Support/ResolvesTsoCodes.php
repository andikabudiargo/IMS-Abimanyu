<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

// Extracted from HomeController::buildSalesAchievement() so other controllers (e.g.
// DeliveryMonitoringController) can resolve "the" TSO(s) for a given month the same way,
// without duplicating the revision/snapshot handling.
trait ResolvesTsoCodes
{
    private const BULAN_NAMES = [
        1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL',
        5 => 'MEI', 6 => 'JUNI', 7 => 'JULI', 8 => 'AGUSTUS',
        9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER',
    ];

    // Bulan target dari tso_name (lihat HomeController::targetMonthFromName untuk alasannya
    // tso_date tidak dipakai). Return null kalau tso_name tidak mengandung nama bulan.
    private function targetMonthFromName(string $name): ?int
    {
        $upper = strtoupper($name);
        foreach (self::BULAN_NAMES as $num => $label) {
            if (strpos($upper, $label) !== false) {
                return $num;
            }
        }
        return null;
    }

    // TSO yang cocok untuk $periode/$tahun = TSO status=3 (APPROVED) yang nama bulan di
    // tso_name-nya == $periode, tahun dari tso_date. Kalau lagi direvisi (status draft/pending),
    // fallback ke snapshot approved terakhir (num_revision tertinggi) biar data lama tetap konsisten.
    // $firstMatchedId (by-ref, optional) = id dari header pertama yang cocok bulan/tahun,
    // dipakai HomeController buat link "lihat TSO" -- tidak dibutuhkan caller lain.
    private function resolveTsoCodes(int $periode, int $tahun, &$firstMatchedId = null): array
    {
        $candidateHeaders = DB::table('target_order_hdr')
            ->whereColumn('tso_code', 'origin_tso_code')
            ->whereIn('status', ['1', '2', '3'])
            ->whereRaw("EXTRACT(YEAR FROM to_date(tso_date,'DD-MM-YYYY')) BETWEEN ? AND ?", [$tahun - 1, $tahun + 1])
            ->orderBy('id')
            ->get(['id', 'tso_code', 'tso_name', 'tso_date', 'status']);

        $matchedTsoCodes = [];
        $firstMatchedId = null;
        foreach ($candidateHeaders as $h) {
            $month = $this->targetMonthFromName((string) $h->tso_name);
            if ($month === null) {
                continue;
            }
            $dt = \DateTime::createFromFormat('d-m-Y', trim((string) $h->tso_date));
            if (!$dt || $month !== $periode || (int) $dt->format('Y') !== $tahun) {
                continue;
            }
            if ($firstMatchedId === null) {
                $firstMatchedId = $h->id;
            }

            if ($h->status === '3') {
                $matchedTsoCodes[] = $h->tso_code;
            } else {
                $lastApprovedSnapshot = DB::table('target_order_hdr')
                    ->where('origin_tso_code', $h->tso_code)
                    ->where('tso_code', '<>', $h->tso_code)
                    ->orderByDesc('num_revision')
                    ->first(['tso_code']);

                if ($lastApprovedSnapshot) {
                    $matchedTsoCodes[] = $lastApprovedSnapshot->tso_code;
                }
            }
        }
        return $matchedTsoCodes;
    }
}
