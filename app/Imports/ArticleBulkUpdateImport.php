<?php

namespace App\Imports;

use App\Models\ArticleBulkUpdateStake;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStartRow;

class ArticleBulkUpdateImport implements WithMultipleSheets
{
    private $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        // Workbook also has "Referensi Article"/"Referensi COA"/etc sheets for the
        // user to look codes up in; only sheet 0 ("Template") holds actual data to import.
        return [0 => new ArticleBulkUpdateSheetImport($this->data)];
    }
}

class ArticleBulkUpdateSheetImport implements ToModel, WithStartRow, WithHeadingRow
{
    private $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function model(array $row)
    {
        if (empty($row['article_code'])) {
            // skip blank trailing rows in the Template sheet
            return null;
        }

        return new ArticleBulkUpdateStake([
            'batch_id'     => $this->data['batch_id'],
            'article_code' => $row['article_code'] ?? null,
            'safety_stock' => $row['safety_stock'] ?? null,
            'coa'          => $row['coa'] ?? null,
            'min_package'  => $row['min_package'] ?? null,
            'cashflow_category' => $row['cashflow_category'] ?? null,
        ]);
    }

    public function startRow(): int
    {
        return 2;
    }
}
