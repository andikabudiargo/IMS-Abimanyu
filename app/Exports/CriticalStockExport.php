<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CriticalStockExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $rows;

    public function __construct($rows)
    {
        $this->rows = $rows;
    }

    public function collection()
    {
        return collect($this->rows)->map(function ($val) {
            return [
                'location_name' => $val->location_name,
                'code' => $val->code,
                'name' => $val->name,
                'supplier_name' => $val->supplier_name,
                'stock_qty' => $val->stock_qty,
                'safety_stock' => $val->safety_stock,
                'uom' => $val->uom,
                'min_package' => $val->min_package,
            ];
        });
    }

    public function headings(): array
    {
        return ['Location', 'Code', 'Article', 'Supplier/Customer', 'Stock', 'Safety Stock', 'UOM', 'Min Package'];
    }
}
