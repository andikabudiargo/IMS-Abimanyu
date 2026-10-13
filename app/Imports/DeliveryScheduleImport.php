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
     * Kolom template: article_code | date (DD-MM-YYYY) | qty
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $articleCode = strtoupper(trim($row['article_code'] ?? ''));
            $dateRaw = trim((string) ($row['schedule_date'] ?? ''));
            $qty = $row['qty'] ?? null;

            if ($articleCode === '' && $dateRaw === '') {
                continue;
            }

            try {
                $scheduleDate = Carbon::createFromFormat('d-m-Y', $dateRaw)->format('Y-m-d');
            } catch (\Exception $e) {
                $scheduleDate = null;
            }

            $this->rows[] = [
                'article_code' => $articleCode,
                'schedule_date' => $scheduleDate,
                'qty' => is_numeric($qty) ? $qty : 0,
            ];
        }
    }
}
