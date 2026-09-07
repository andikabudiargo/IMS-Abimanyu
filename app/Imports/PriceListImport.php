<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class PriceListImport implements ToCollection, WithHeadingRow
{
    /** Rows dari sheet pertama ('template') saja — sheet 'master_article' diabaikan */
    public Collection $rows;

    public function collection(Collection $sheets)
    {
        // File punya 2 sheet → collection() menerima Collection-of-Collections (satu per sheet)
        $this->rows = $sheets->get(0, collect());
    }
}