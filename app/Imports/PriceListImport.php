<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PriceListImport implements WithHeadingRow
{
    // Sengaja tidak implement ToCollection — dipakai sebagai "concern holder"
    // untuk Excel::toCollection(), yang secara konsisten mengembalikan
    // Collection-of-sheets tanpa ambiguitas seperti Excel::import().
}