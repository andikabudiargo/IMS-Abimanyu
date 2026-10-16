<?php

namespace App\Traits;

/**
 * Lokasi-lokasi chemical yang kalengnya di-track per-barcode (remaining_qty).
 * 005 = Gudang Chemical, 049 = Chemical Expired, 042 = Chemical Dead Stock.
 * Satu sumber supaya backend (TransferStockController, WarehouseControllerv2)
 * dan mobile (kChemicalLotLocations di ims_mobile) tidak punya daftar sendiri-
 * sendiri yang bisa out-of-sync kalau nanti nambah lokasi chemical baru.
 */
trait HasChemicalLotLocations
{
    private static function chemicalLotLocations(): array
    {
        return ['005', '049', '042'];
    }

    private static function isChemicalLotLocation(?string $locationCode): bool
    {
        return in_array($locationCode, self::chemicalLotLocations(), true);
    }
}
