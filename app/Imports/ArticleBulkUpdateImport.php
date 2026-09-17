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
        return new ArticleBulkUpdateStake([
            'batch_id'     => $this->data['batch_id'],
            'article_code' => $row['article_code'] ?? null,
            'safety_stock' => $row['safety_stock'] ?? null,
            'coa'          => $row['coa'] ?? null,
            'min_package'  => $row['min_package'] ?? null,
        ]);
    }

    public function startRow(): int
    {
        return 2;
    }
}
