<?php

namespace App\Imports;
  
use App\Models\ImportStake;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StockTakeImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */

    public function model(array $row)
    {
        return new ImportStake([
            'article_code'     => $row['article_code'],
            'article_qty'    => $row['qty'], 
        ]);
    }
}