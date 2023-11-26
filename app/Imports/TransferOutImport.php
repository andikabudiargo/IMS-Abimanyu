<?php

namespace App\Imports;

use App\Models\ImportStake;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
 
class TransferOutImport implements ToModel, WithStartRow,WithHeadingRow
{
    private $data;

    public function __construct(array $data = [])
    {
        $this->data = $data; 
    }

    public function model(array $row)
    {
        return new ImportStake([
            'file_name'      => $this->data['filename'],
            'article_code'   => $row['article_code'],
            'qty'            => $row['qty'], 
        ]);
    }

    public function startRow(): int
    {
        return 2;
    }
}