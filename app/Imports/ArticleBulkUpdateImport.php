<?php

namespace App\Imports;

use App\Models\ArticleBulkUpdateStake;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;

class ArticleBulkUpdateImport implements ToModel, WithStartRow, WithHeadingRow
{
    private $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function model(array $row)
    {
        if (empty($row['article_code'])) {
            // ponytail: template workbook has reference sheets (Referensi Article/COA/...)
            // that get fed through this same mapper since we don't implement
            // WithMultipleSheets; their rows never have article_code, so skip them here.
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
