<?php

namespace App\Imports;

use App\Models\ImportStake;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
 
class TransferOutImport implements WithMultipleSheets
{
    private $data;

    public function __construct(array $data = [])
    {
        $this->data = $data; 
    }

    public function sheets(): array
    {
        return [
            'article' => new ArticleSheetImport($this->data),
        ];
    }
}

// Create a separate class for the article sheet
class ArticleSheetImport implements ToModel, WithStartRow, WithHeadingRow
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
            'location_code'  => $row['location_code'],
            'qty'            => $row['qty'], 
        ]);
    }

    public function startRow(): int
    {
        return 2;
    }
}