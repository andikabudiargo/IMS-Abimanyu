<?php

namespace App\Imports;

// use App\Models\ImportBomHdr;
// use App\Models\ImportBomSpBooth;
use Maatwebsite\Excel\Concerns\ToModel;
// use Maatwebsite\Excel\Concerns\WithHeadingRow;
// use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithStartRow;
// use Maatwebsite\Excel\Concerns\WithMultipleSheets;
// use Illuminate\Support\Collection;
// use Maatwebsite\Excel\Concerns\ToCollection;
// use Illuminate\Validation\ValidationException;


use App\Models\ImportBomHdr;
use App\Models\ImportBomSpBooth;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Validation\ValidationException;
use DB;
 
class BomUpload implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Header' => new HeaderSheetImport(),
            'SprayBooth' => new SprayboothSheetImport(),
        ];
    }
}

// class HeaderSheetImport implements ToModel, ToCollection, WithStartRow, WithHeadingRow WithValidation
// class HeaderSheetImport implements ToCollection, WithHeadingRow, WithValidation, WithStartRow
class HeaderSheetImport implements ToCollection, WithHeadingRow
{

    private $requiredHeaders = [
        'customer',
        'article_code_fg',
        'article_code_rm',
        'article_code',
        'note',
        'part_no',
        'qty',
        'uom',
        'uom_con',
        'article_type',
        'urutan',
        'pos',
        'tone'
    ];

    private $actualHeaders = [];

    public function collection(Collection $rows)
    {

        // Get actual headers from first row
        if ($rows->isNotEmpty()) {
            $this->actualHeaders = array_keys($rows->first()->toArray());
            $this->validateHeaders();
        }
        // Validasi header dilakukan otomatis sebelum method ini dipanggil
        foreach ($rows as $row) {
            if ($this->isEmptyRow($row)) {
                continue;
            }

            ImportBomHdr::create([
                'customer' => $row['customer'] ?? null,
                'article_code_fg' => $row['article_code_fg'] ?? null,
                'article_code_rm' => $row['article_code_rm'] ?? null,
                'article_code' => $row['article_code'] ?? null,
                'note' => $row['note'] ?? null,
                'part_no' => $row['part_no'] ?? null,
                'qty' => $row['qty'] ?? null,
                'uom' => $row['uom'] ?? null,
                'uom_con' => $row['uom_con'] ?? null,
                'article_type' => $row['article_type'] ?? null,
                'urutan' => $row['urutan'] ?? null,
                'pos' => $row['pos'] ?? null,
                'tone' => $row['tone'] ?? null, 
                'status' => '1' ?? null,
            ]);
        }

        db::statement("INSERT into bom_hdr_upload_tmp(customer, article_code_fg, uom,article_code_rm,pos,part_no,group_of_material,note,status)
                           SELECT DISTINCT ON (customer, article_code_fg,article_code_rm) 
                            customer, article_code_fg, uom, article_code_rm, pos, part_no, group_of_material, note, status
                        FROM bom_upload_tmp
                        ORDER BY article_code_fg");
    }

    private function validateHeaders()
    {
        $normalizedActual = $this->normalizeHeaders($this->actualHeaders);
        $normalizedRequired = $this->normalizeHeaders($this->requiredHeaders);
        
        $missingHeaders = array_diff($normalizedRequired, $normalizedActual);
        
        if (!empty($missingHeaders)) {
            $originalMissing = [];
            foreach ($missingHeaders as $missing) {
                $originalMissing[] = $this->getOriginalHeaderName($missing, $this->requiredHeaders);
            }
            
            throw ValidationException::withMessages([
                'headers' => ["Kolom di Sheet herader yang diperlukan tidak ditemukan: " . implode(', ', $originalMissing)]
            ]);
        }
    }

    private function normalizeHeaders(array $headers): array
    {
        return array_map(function ($header) {
            return strtolower(str_replace([' ', '_', '-'], '', $header));
        }, $headers);
    }

    private function getOriginalHeaderName(string $normalized, array $originalHeaders): string
    {
        foreach ($originalHeaders as $header) {
            if ($this->normalizeHeader($header) === $normalized) {
                return $header;
            }
        }
        return $normalized;
    }

    private function normalizeHeader(string $header): string
    {
        return strtolower(str_replace([' ', '_', '-'], '', $header));
    }

    private function isEmptyRow($row)
    {
        return $row->filter(function ($value) {
            return !is_null($value) && $value !== '';
        })->isEmpty();
    }

    // public function model(array $row)
    // {
    //     // Skip row jika kosong
    //     if (empty(array_filter($row))) {
    //         return null;
    //     }

    //     // 'nama' => $row['nama'] ?? $row['nama_header'] ?? $row['name'] ?? null,

    //     return new ImportBomHdr([
    //         'customer' => $row['customer'] ?? null,
    //         'article_code_fg' => $row['article_code_fg'] ?? null,
    //         'article_code_rm' => $row['article_code_rm'] ?? null,
    //         'article_code' => $row['article_code'] ?? null,
    //         'note' => $row['note'] ?? null,
    //         'part_no' => $row['part_no'] ?? null,
    //         'qty' => $row['qty'] ?? null,
    //         'uom' => $row['uom'] ?? null,
    //         'uom_con' => $row['uom_con'] ?? null,
    //         'article_type' => $row['article_type'] ?? null,
    //         'urutan' => $row['urutan'] ?? null,
    //         'pos' => $row['pos'] ?? null,
    //         'tone' => $row['tone'] ?? null, 
    //         'status' => '1' ?? null,
    //     ]);
    // }

    public function startRow(): int
    {
        return 2; // Mulai dari baris 2 jika baris 1 adalah header
    }
}

// class SprayboothSheetImport implements ToModel, WithStartRow, WithHeadingRow
class SprayboothSheetImport implements ToCollection, WithHeadingRow
{

    private $requiredHeaders = [
        'article_code_fg',
        'spray_booth',
        'tone',
        'tack',
        'pass_rate',
        'pass_thru',
        'cycle_time',
        'urutan'
    ];
    private $actualHeaders = [];

    public function collection(Collection $rows)
    {

        // Get actual headers from first row
        if ($rows->isNotEmpty()) {
            $this->actualHeaders = array_keys($rows->first()->toArray());
            $this->validateHeaders();
        }
        // Validasi header dilakukan otomatis sebelum method ini dipanggil
        foreach ($rows as $row) {
            if ($this->isEmptyRow($row)) {
                continue;
            }

            ImportBomSpBooth::create([
                'article_code_fg' => $row['article_code_fg'] ?? null,
                'article_code_rm' => $row['article_code_rm'] ?? null,
                'spray_booth' => $row['spray_booth'] ?? null,
                'tone' => $row['tone'] ?? null,
                'tack' => $row['tack'] ?? null,
                'pass_rate' => $row['pass_rate'] ?? null,
                'pass_thru' => $row['pass_thru'] ?? null,
                'cycle_time' => $row['cycle_time'] ?? null,
                'urutan' => $row['urutan'] ?? null,
            ]);
        }
    }

    private function validateHeaders()
    {
        $normalizedActual = $this->normalizeHeaders($this->actualHeaders);
        $normalizedRequired = $this->normalizeHeaders($this->requiredHeaders);
        
        $missingHeaders = array_diff($normalizedRequired, $normalizedActual);
        
        if (!empty($missingHeaders)) {
            $originalMissing = [];
            foreach ($missingHeaders as $missing) {
                $originalMissing[] = $this->getOriginalHeaderName($missing, $this->requiredHeaders);
            }
            
            throw ValidationException::withMessages([
                'headers' => ["Kolom di Sheet Spraybooth yang diperlukan tidak ditemukan: " . implode(', ', $originalMissing)]
            ]);
        }
    }

    private function normalizeHeaders(array $headers): array
    {
        return array_map(function ($header) {
            return strtolower(str_replace([' ', '_', '-'], '', $header));
        }, $headers);
    }

    private function getOriginalHeaderName(string $normalized, array $originalHeaders): string
    {
        foreach ($originalHeaders as $header) {
            if ($this->normalizeHeader($header) === $normalized) {
                return $header;
            }
        }
        return $normalized;
    }

    private function normalizeHeader(string $header): string
    {
        return strtolower(str_replace([' ', '_', '-'], '', $header));
    }

    private function isEmptyRow($row)
    {
        return $row->filter(function ($value) {
            return !is_null($value) && $value !== '';
        })->isEmpty();
    }

    // public function model(array $row)
    // {
    //     // Skip row jika kosong
    //     if (empty(array_filter($row))) {
    //         return null;
    //     }

    //     return new ImportBomSpBooth([

    //         'article_code_fg' => $row['article_code_fg'] ?? null,
    //         'spray_booth' => $row['spray_booth'] ?? null,
    //         'tone' => $row['tone'] ?? null,
    //         'tack' => $row['tack'] ?? null,
    //         'pass_rate' => $row['pass_rate'] ?? null,
    //         'pass_thru' => $row['pass_thru'] ?? null,
    //         'cycle_time' => $row['cycle_time'] ?? null,
    //         'urutan' => $row['urutan'] ?? null,
    //     ]);
    // }

    public function startRow(): int
    {
        return 2; // Mulai dari baris 2 jika baris 1 adalah header
    }
}