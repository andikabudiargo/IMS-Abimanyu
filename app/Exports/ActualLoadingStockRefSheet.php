<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class ActualLoadingStockRefSheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    protected $rmStock;
    protected $fgWip;
    protected $boothName;

    protected $fgTitleRow;
    protected $fgHeaderRow;

    public function __construct($rmStock, $fgWip, $boothName = null)
    {
        $this->rmStock   = $rmStock;
        $this->fgWip     = $fgWip;
        $this->boothName = $boothName;
    }

    public function title(): string
    {
        return 'Stock Reference';
    }

    public function array(): array
    {
        $rows = [];

        // ── Seksi A: Stok RM di Spray Booth ──
        $rows[] = ['STOK RM DI SPRAY BOOTH' . ($this->boothName ? ' - ' . $this->boothName : '')];
        $rows[] = ['Article Code', 'Article Desc', 'UOM', 'Qty'];

        foreach ($this->rmStock as $r) {
            $rows[] = [$r->article_alternative_code, $r->article_desc, $r->uom, $r->qty];
        }
        if ($this->rmStock->isEmpty()) {
            $rows[] = ['(tidak ada stok RM di booth ini)'];
        }

        $rows[] = []; // baris kosong pemisah

        // ── Seksi B: Stok FG di gudang WIP ──
        $this->fgTitleRow  = count($rows) + 1;
        $rows[]            = ['STOK FG DI GUDANG WIP'];
        $this->fgHeaderRow = count($rows) + 1;
        $rows[]            = ['Article Code', 'Article Desc', 'Location', 'UOM', 'Qty'];

        foreach ($this->fgWip as $r) {
            $rows[] = [$r->article_alternative_code, $r->article_desc, $r->location_name, $r->uom, $r->qty];
        }
        if ($this->fgWip->isEmpty()) {
            $rows[] = ['(tidak ada stok FG di gudang WIP)'];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $highest = $event->sheet->getHighestDataRow();

                $sheet->getStyle("A1:E{$highest}")->getFont()->setSize(10);

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A2:D2')->getFont()->setBold(true);

                if ($this->fgTitleRow) {
                    $sheet->getStyle("A{$this->fgTitleRow}")->getFont()->setBold(true)->setSize(12);
                    $sheet->getStyle("A{$this->fgHeaderRow}:E{$this->fgHeaderRow}")->getFont()->setBold(true);
                }
            },
        ];
    }
}