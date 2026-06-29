<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use DB;

class StockAdjustmentImport implements WithMultipleSheets
{
    protected string $filename;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    /**
     * Hanya baca sheet pertama ('template'), abaikan sheet lain
     */
    public function sheets(): array
    {
        return [
            0 => new StockAdjustmentTemplateSheet($this->filename),
        ];
    }
}

class StockAdjustmentTemplateSheet implements ToModel, WithHeadingRow, SkipsOnError
{
    use SkipsErrors;

    protected string $filename;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    /**
     * Kolom template: article_code | qty_adjustment | notes
     */
    public function model(array $row)
    {
        $artCode = trim($row['article_code']   ?? '');
        $qty     = trim($row['qty_adjustment'] ?? '');

        // Skip baris kosong
        if ($artCode === '' && $qty === '') {
            return null;
        }

        DB::table('import_adjustment_tmp')->insert([
            'file_name'    => $this->filename,
            'article_code' => strtoupper($artCode),
            'qty'          => is_numeric($qty) ? (float) $qty : 0,
            'notes'        => trim($row['notes'] ?? ''),
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        return null;
    }
}