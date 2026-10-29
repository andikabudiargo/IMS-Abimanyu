<?php

namespace App\Imports;

use App\Models\ImportStake;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TransferStockImport implements WithMultipleSheets
{
    private $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        return [
            'article' => new TransferStockArticleSheetImport($this->data),
        ];
    }
}

// Transfer Stock only needs article_code and qty; location is already chosen via "Location From" on the form.
class TransferStockArticleSheetImport implements ToModel, WithStartRow, WithHeadingRow
{
    private $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function model(array $row)
    {
        if (empty($row['article_code']) && empty($row['qty'])) {
            return null;
        }

        return new ImportStake([
            'file_name'    => $this->data['filename'],
            'article_code' => $row['article_code'],
            'qty'          => $row['qty'],
        ]);
    }

    public function startRow(): int
    {
        return 2;
    }
}
