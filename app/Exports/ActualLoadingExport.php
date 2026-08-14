<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ActualLoadingExport implements WithMultipleSheets
{
    protected $articles;
    protected $rmStock;
    protected $fgWip;
    protected $sprayBooth;
    protected $boothName;

    public function __construct($articles, $rmStock, $fgWip, $sprayBooth = null, $boothName = null)
    {
        $this->articles   = $articles;
        $this->rmStock    = $rmStock;
        $this->fgWip      = $fgWip;
        $this->sprayBooth = $sprayBooth;
        $this->boothName  = $boothName;
    }

    public function sheets(): array
    {
        return [
            new ActualLoadingTemplateSheet($this->articles, $this->sprayBooth, $this->boothName),
            new ActualLoadingStockRefSheet($this->rmStock, $this->fgWip, $this->boothName),
        ];
    }
}