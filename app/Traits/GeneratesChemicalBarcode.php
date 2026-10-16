<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Generator barcode LOT-AAA-000000001 untuk receiving_chemical_unit.
 * Dipakai di ReceivingController (Add Expired Date) dan WarehouseControllerv2
 * (generate barcode dari stock lama / belum ber-lot) -- supaya sequence-nya
 * satu sumber, tidak ada 2 implementasi yang bisa berbeda/tabrakan.
 */
trait GeneratesChemicalBarcode
{
    private function lockChemicalBarcodeSequence(): void
    {
        // Advisory lock khusus barcode chemical unit, biar generate sequence aman dari race condition
        DB::select("SELECT pg_advisory_xact_lock(hashtext('receiving_chemical_unit_barcode'))");
    }

    private function nextChemicalBarcode(): string
    {
        $last = DB::table('receiving_chemical_unit')
            ->where('barcode_code', 'like', 'LOT-%')
            ->orderByRaw("substring(barcode_code from 5 for 3) desc, (substring(barcode_code from 9))::bigint desc")
            ->value('barcode_code');

        if (!$last || !preg_match('/^LOT-([A-Z]{3})-(\d{9})$/', $last, $m)) {
            return 'LOT-AAA-' . str_pad(1, 9, '0', STR_PAD_LEFT);
        }

        $letters = $m[1];
        $number  = (int) $m[2];

        if ($number >= 999999999) {
            $letters = $this->incrementLetterSeq($letters);
            $number  = 1;
        } else {
            $number++;
        }

        return 'LOT-' . $letters . '-' . str_pad($number, 9, '0', STR_PAD_LEFT);
    }

    /**
     * Increment murni dari string barcode terakhir, TANPA query ke DB.
     * Dipakai supaya generate banyak barcode sekaligus dalam satu request
     * tidak saling tabrakan (beda dengan nextChemicalBarcode() yang query DB).
     */
    private function incrementChemicalBarcode(string $last): string
    {
        if (!preg_match('/^LOT-([A-Z]{3})-(\d{9})$/', $last, $m)) {
            return 'LOT-AAA-' . str_pad(1, 9, '0', STR_PAD_LEFT);
        }

        $letters = $m[1];
        $number  = (int) $m[2];

        if ($number >= 999999999) {
            $letters = $this->incrementLetterSeq($letters);
            $number  = 1;
        } else {
            $number++;
        }

        return 'LOT-' . $letters . '-' . str_pad($number, 9, '0', STR_PAD_LEFT);
    }

    private function incrementLetterSeq(string $letters): string
    {
        $chars = str_split($letters);
        for ($i = 2; $i >= 0; $i--) {
            if ($chars[$i] === 'Z') {
                $chars[$i] = 'A';
                if ($i === 0) {
                    throw new \Exception('Barcode letter sequence overflow (ZZZ tercapai), perlu penanganan manual');
                }
            } else {
                $chars[$i] = chr(ord($chars[$i]) + 1);
                break;
            }
        }
        return implode('', $chars);
    }
}
