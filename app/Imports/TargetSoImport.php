<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Illuminate\Support\Collection;
use DB;

class TargetSoImport implements WithMultipleSheets
{
    protected string $batchId;

    public function __construct(string $batchId)
    {
        $this->batchId = $batchId;
    }

    /**
     * Hanya baca sheet pertama ('template'), abaikan sheet referensi master_article.
     */
    public function sheets(): array
    {
        return [
            0 => new TargetSoTemplateImportSheet($this->batchId),
        ];
    }
}

class TargetSoTemplateImportSheet implements ToCollection, WithHeadingRow, WithChunkReading
{
    protected string $batchId;

    public function __construct(string $batchId)
    {
        $this->batchId = $batchId;
    }

    /**
     * Kolom template: article_code | qty_target | qty_forecast_1 | qty_forecast_2 | qty_forecast_3 (qty_forecast = template lama)
     */
    public function collection(Collection $rows)
    {
        $now     = date('Y-m-d H:i:s');
        $dataSet = [];

        foreach ($rows as $row) {
            $artCode = trim($row['article_code'] ?? '');
            $qtyTarget = trim((string) ($row['qty_target'] ?? ''));
            $qtyForcast = trim((string) ($row['qty_forecast_1'] ?? $row['qty_forecast'] ?? ''));
            $qtyForcast2 = trim((string) ($row['qty_forecast_2'] ?? ''));
            $qtyForcast3 = trim((string) ($row['qty_forecast_3'] ?? ''));

            if ($artCode === '' && $qtyTarget === '' && $qtyForcast === '') {
                continue;
            }

            $dataSet[] = [
                'batch_id'     => $this->batchId,
                'article_code' => strtoupper($artCode),
                'qty_target'   => is_numeric($qtyTarget) ? $qtyTarget : '0',
                'qty_forcast'  => is_numeric($qtyForcast) ? $qtyForcast : '0',
                'qty_forcast2' => is_numeric($qtyForcast2) ? $qtyForcast2 : null,
                'qty_forcast3' => is_numeric($qtyForcast3) ? $qtyForcast3 : null,
                'created_at'   => $now,
            ];
        }

        if (!empty($dataSet)) {
            DB::table('import_target_so_tmp')->insert($dataSet);
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
