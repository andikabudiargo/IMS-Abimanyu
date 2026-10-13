<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class DeliveryScheduleImport implements ToCollection, WithHeadingRow
{
    public $rows = [];

    /**
     * Kolom template: so_code | article_code | schedule_date (DD-MM-YYYY) | qty
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $soCode = strtoupper(trim($row['so_code'] ?? ''));
            $articleCode = strtoupper(trim($row['article_code'] ?? ''));
            $dateRaw = trim((string) ($row['schedule_date'] ?? ''));
            $qty = $row['qty'] ?? null;

            if ($soCode === '' && $articleCode === '' && $dateRaw === '') {
                continue;
            }

            try {
                $scheduleDate = Carbon::createFromFormat('d-m-Y', $dateRaw)->format('Y-m-d');
            } catch (\Exception $e) {
                $scheduleDate = null;
            }

            $this->rows[] = [
                'so_code' => $soCode,
                'article_code' => $articleCode,
                'schedule_date' => $scheduleDate,
                'qty' => is_numeric($qty) ? $qty : 0,
            ];
        }
    }
}
