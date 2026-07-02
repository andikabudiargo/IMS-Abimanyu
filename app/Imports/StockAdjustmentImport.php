<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Illuminate\Support\Collection;
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

class StockAdjustmentTemplateSheet implements ToCollection, WithHeadingRow, WithChunkReading
{
    protected string $filename;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    /**
     * Baca file per chunk (lihat chunkSize()), lalu setiap chunk
     * di-insert SEKALIGUS dalam satu query — bukan satu-satu.
     * Kolom template: article_code | qty_adjustment | notes
     */
    public function collection(Collection $rows)
    {
        $now     = date('Y-m-d H:i:s');
        $dataSet = [];

        foreach ($rows as $row) {
            $artCode = trim($row['article_code']   ?? '');
            $qty     = trim($row['qty_adjustment'] ?? '');

            // Skip baris kosong
            if ($artCode === '' && $qty === '') {
                continue;
            }

            $dataSet[] = [
                'file_name'    => $this->filename,
                'article_code' => strtoupper($artCode),
                'qty'          => is_numeric($qty) ? (float) $qty : 0,
                'notes'        => trim($row['notes'] ?? ''),
                'created_at'   => $now,
            ];
        }

        if (!empty($dataSet)) {
            // insert satu query untuk seluruh chunk (bukan per baris)
            DB::table('import_adjustment_tmp')->insert($dataSet);
        }
    }

    /**
     * Baca file 500 baris per chunk — cukup kecil untuk hemat memori,
     * cukup besar supaya jumlah query insert tetap sedikit.
     */
    public function chunkSize(): int
    {
        return 500;
    }
}