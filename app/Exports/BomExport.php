<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Events\AfterSheet;
use DB;

class BomExport implements FromArray, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $searchBom;
    protected $articleCode;
    protected $status;
    protected $maxRmCount;
    protected $maxSbCount;
    protected $maxDetCount;

    function __construct($searchBom, $articleCode, $status)
    {
        $this->searchBom   = $searchBom;
        $this->articleCode = $articleCode;
        $this->status      = $status;
    }

    private function getFilter(): string
    {
        $filter = "AND bom_hdr.status <> '7'";
        if ($this->searchBom)   $filter .= " AND bom_hdr.bom_code ILIKE '%{$this->searchBom}%'";
        if ($this->articleCode) $filter .= " AND bom_hdr.article_code = '{$this->articleCode}'";
        if ($this->status)      $filter .= " AND bom_hdr.status = '{$this->status}'";
        return $filter;
    }

    private function computeMaxCounts(): void
    {
        $filter = $this->getFilter();

        $this->maxRmCount = DB::selectOne("
            SELECT COALESCE(MAX(jumlah), 0) AS max_count
            FROM (
                SELECT COUNT(*) AS jumlah FROM bom_rm
                WHERE bom_code IN (SELECT bom_code FROM bom_hdr WHERE 1=1 $filter)
                GROUP BY bom_code
            ) x
        ")->max_count;

        $this->maxSbCount = DB::selectOne("
            SELECT COALESCE(MAX(jumlah), 0) AS max_count
            FROM (
                SELECT COUNT(*) AS jumlah FROM bom_spray_booth
                WHERE bom_code IN (SELECT bom_code FROM bom_hdr WHERE 1=1 $filter)
                GROUP BY bom_code
            ) x
        ")->max_count;

        $this->maxDetCount = DB::selectOne("
            SELECT COALESCE(MAX(jumlah), 0) AS max_count
            FROM (
                SELECT COUNT(*) AS jumlah FROM bom_det
                WHERE bom_code IN (SELECT bom_code FROM bom_hdr WHERE 1=1 $filter)
                GROUP BY bom_code
            ) x
        ")->max_count;
    }

    public function headings(): array
{
    $this->computeMaxCounts();

    $heads = [
        'BOM Code', 'Revision', 'Article Finish Good',
    ];

    // RM langsung setelah Article FG
    for ($i = 1; $i <= $this->maxRmCount; $i++) {
        $heads[] = "Article RM $i";
    }

    $heads[] = 'Customer';
    $heads[] = 'Group of Material';
    $heads[] = 'UOM';
    $heads[] = 'Part No';
    $heads[] = 'Model';
    $heads[] = 'Note';

    for ($i = 1; $i <= $this->maxSbCount; $i++) {
        $heads[] = "Spray Booth $i";
        $heads[] = "Tone SB $i";
        $heads[] = "Tack $i";
        $heads[] = "Pass Rate $i";
        $heads[] = "Pass Thru $i";
        $heads[] = "Cycle Time $i";
    }

    for ($i = 1; $i <= $this->maxDetCount; $i++) {
        $heads[] = "Article Det $i";
        $heads[] = "Tone Det $i";
        $heads[] = "Pos $i";
        $heads[] = "Qty $i";
        $heads[] = "UOM Det $i";
        $heads[] = "UOM Con $i";
    }

    return $heads;
}

    public function array(): array
    {
        $filter     = $this->getFilter();
        $sbColumns  = '';
        $detColumns = '';
        $rmColumns  = '';

        for ($i = 1; $i <= $this->maxSbCount; $i++) {
            $sbColumns .= ",MAX(CASE WHEN sb.urutan = $i THEN sb.spray_booth ELSE '' END) AS spray_booth_$i";
            $sbColumns .= ",MAX(CASE WHEN sb.urutan = $i THEN sb.tone ELSE '' END) AS tone_sb_$i";
            $sbColumns .= ",MAX(CASE WHEN sb.urutan = $i THEN CAST(sb.tack AS TEXT) ELSE '' END) AS tack_$i";
            $sbColumns .= ",MAX(CASE WHEN sb.urutan = $i THEN CAST(sb.pass_rate AS TEXT) ELSE '' END) AS pass_rate_$i";
            $sbColumns .= ",MAX(CASE WHEN sb.urutan = $i THEN CAST(sb.pass_thru AS TEXT) ELSE '' END) AS pass_thru_$i";
            $sbColumns .= ",MAX(CASE WHEN sb.urutan = $i THEN CAST(sb.cycle_time AS TEXT) ELSE '' END) AS cycle_time_$i";
        }

        for ($i = 1; $i <= $this->maxDetCount; $i++) {
            $detColumns .= ",MAX(CASE WHEN det.urutan = $i THEN det.article_label ELSE '' END) AS article_det_$i";
            $detColumns .= ",MAX(CASE WHEN det.urutan = $i THEN det.tone ELSE '' END) AS tone_det_$i";
            $detColumns .= ",MAX(CASE WHEN det.urutan = $i THEN det.pos_name ELSE '' END) AS pos_det_$i";
            $detColumns .= ",MAX(CASE WHEN det.urutan = $i THEN CAST(det.qty AS TEXT) ELSE '' END) AS qty_det_$i";
            $detColumns .= ",MAX(CASE WHEN det.urutan = $i THEN det.uom ELSE '' END) AS uom_det_$i";
            $detColumns .= ",MAX(CASE WHEN det.urutan = $i THEN det.uom_con ELSE '' END) AS uom_con_det_$i";
        }

        for ($i = 1; $i <= $this->maxRmCount; $i++) {
            $rmColumns .= ",MAX(CASE WHEN rm.urutan = $i THEN CONCAT(rm.article_alternative_code, ' - ', rm.article_desc) ELSE '' END) AS article_rm_$i";
        }

        $rows = DB::select("
            SELECT
                bom_hdr.bom_code,
                bom_hdr.num_revision,
                (SELECT CONCAT(article_alternative_code,' - ',article_desc) FROM article WHERE article_code = bom_hdr.article_code) AS article_finish_good,
                (SELECT CONCAT(kode,' - ',nama) FROM third_party WHERE kode = bom_hdr.customer) AS customer,
                bom_hdr.group_of_material,
                bom_hdr.uom,
                bom_hdr.part_no,
                bom_hdr.model,
                bom_hdr.note
                $sbColumns
                $detColumns
                $rmColumns

            FROM bom_hdr

            LEFT JOIN (
                SELECT bom_code, urutan, spray_booth, tone, tack, pass_rate, pass_thru, cycle_time
                FROM bom_spray_booth
            ) sb ON sb.bom_code = bom_hdr.bom_code

            LEFT JOIN (
                SELECT
                    bom_det.bom_code,
                    REPLACE(tone,'t','Tone ') AS tone,
                    (SELECT pos_name FROM bom_pos WHERE pos_code = bom_det.pos) AS pos_name,
                    (SELECT CONCAT(article_alternative_code,' - ',article_desc) FROM article WHERE article_code = bom_det.article_code) AS article_label,
                    bom_det.qty,
                    bom_det.uom,
                    bom_det.uom_con,
                    RANK() OVER (PARTITION BY bom_det.bom_code ORDER BY bom_det.urutan) AS urutan
                FROM bom_det
            ) det ON det.bom_code = bom_hdr.bom_code

            LEFT JOIN (
                SELECT bom_code, urutan, article_alternative_code, article_desc
                FROM bom_rm
            ) rm ON rm.bom_code = bom_hdr.bom_code

            WHERE 1=1 $filter

            GROUP BY
                bom_hdr.bom_code, bom_hdr.num_revision, bom_hdr.article_code,
                bom_hdr.customer, bom_hdr.group_of_material, bom_hdr.uom,
                bom_hdr.part_no, bom_hdr.model, bom_hdr.note

            ORDER BY bom_hdr.article_code
        ");

        // convert object ke array per baris
        $result = [];
        foreach ($rows as $row) {
    $r = [
        $row->bom_code,
        $row->num_revision,
        $row->article_finish_good,
    ];

    // RM langsung setelah Article FG
    for ($i = 1; $i <= $this->maxRmCount; $i++) {
        $r[] = $row->{"article_rm_$i"} ?? '';
    }

    $r[] = $row->customer;
    $r[] = $row->group_of_material;
    $r[] = $row->uom;
    $r[] = $row->part_no;
    $r[] = $row->model;
    $r[] = $row->note;

    for ($i = 1; $i <= $this->maxSbCount; $i++) {
        $r[] = $row->{"spray_booth_$i"} ?? '';
        $r[] = $row->{"tone_sb_$i"}     ?? '';
        $r[] = $row->{"tack_$i"}        ?? '';
        $r[] = $row->{"pass_rate_$i"}   ?? '';
        $r[] = $row->{"pass_thru_$i"}   ?? '';
        $r[] = $row->{"cycle_time_$i"}  ?? '';
    }

    for ($i = 1; $i <= $this->maxDetCount; $i++) {
        $r[] = $row->{"article_det_$i"} ?? '';
        $r[] = $row->{"tone_det_$i"}    ?? '';
        $r[] = $row->{"pos_det_$i"}     ?? '';
        $r[] = $row->{"qty_det_$i"}     ?? '';
        $r[] = $row->{"uom_det_$i"}     ?? '';
        $r[] = $row->{"uom_con_det_$i"} ?? '';
    }

    $result[] = $r;
    unset($row);
}

        return $result;
    }

    public function columnFormats(): array
    {
        return [];
    }

    public function registerEvents(): array
{
    return [];
}
}