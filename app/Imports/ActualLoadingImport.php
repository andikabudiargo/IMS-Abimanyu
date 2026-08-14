<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ActualLoadingImport implements ToCollection, WithHeadingRow
{
    public $rows;

    public function collection(\Illuminate\Support\Collection $rows)
    {
        $this->rows = $rows;
    }
}