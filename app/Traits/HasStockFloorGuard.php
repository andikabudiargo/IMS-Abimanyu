<?php

namespace App\Traits;

/**
 * Floor tanggal yang sama persis dengan function get_last_qty_new() /
 * get_last_qty_new_grouped() (GREATEST(ob_date, '2026-06-30')): movement
 * bertanggal <= floor ini TIDAK PERNAH dihitung sebagai net ledger. Trait
 * ini dipakai supaya warehouse_stock.article_qty ikut mengabaikannya juga
 * (tidak boleh ditambah/dikurangi), konsisten dengan ledger.
 */
trait HasStockFloorGuard
{
    private const STOCK_FLOOR_YMD = '2026-06-30';

    /**
     * @param string $dateDdMmYyyy format dd-mm-yyyy (format kolom movement_date/tanggal dokumen)
     */
    protected function isBeforeStockFloor(string $dateDdMmYyyy): bool
    {
        $d = \DateTime::createFromFormat('d-m-Y', trim($dateDdMmYyyy));
        if (!$d) {
            return false;
        }
        $floor = \DateTime::createFromFormat('Y-m-d', self::STOCK_FLOOR_YMD);
        return $d <= $floor;
    }
}
