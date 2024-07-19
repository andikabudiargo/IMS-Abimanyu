<?php

namespace App\Imports;

use App\Models\ImportActualLoading;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
 
class ActualLoadingImport implements ToModel, WithStartRow,WithHeadingRow
{
    private $data;

    public function __construct(array $data = [])
    {
        $this->data = $data; 
    }

    public function model(array $row)
    {
        return new ImportActualLoading([
            'file_name' => $this->data['filename'],
            'urutan' => $row['no'],
            'wo_code' => $row['wos'],
            'article_code' => $row['article_code'],
            'qty_fresh' => $row['actual_qty_fresh'],
            'qty_repaint' => $row['actual_qty_repaint'],
            // 'qty_tag' => $row['actual_qty_tag']
        ]);
    }

    public function startRow(): int
    {
        return 2;
    }
}