<?php

namespace App\Exports;

use App\Exports\Concerns\ApprovalBlock;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ArPaymentPlanningExport implements FromArray, WithTitle, WithStyles, WithColumnWidths, WithEvents
{
    use ApprovalBlock;

    protected $rows;
    protected $grand;
    protected $periodLabel;

    const TOTAL_COLS  = 12;
    const IDX_NOMINAL = 8;
    const IDX_BIAYA   = 9;
    const IDX_PPH23   = 10;
    const IDX_TOTAL   = 11;

    public function __construct(array $rows, array $grand, string $periodLabel)
    {
        $this->rows         = $rows;
        $this->grand        = $grand;
        $this->periodLabel  = $periodLabel;
    }

    public function title(): string
    {
        return 'AR Payment Planning';
    }

    private function statusLabel($row)
    {
        $map = ['pending' => 'Pending', 'hold' => 'Hold', 'to_be_paid' => 'To Be Paid', 'paid' => 'Paid'];
        $label = $map[$row['status']] ?? $row['status'];
        if ($row['status'] === 'hold' && !empty($row['hold_reason'])) {
            $label .= ' (' . $row['hold_reason'] . ')';
        }
        return $label;
    }

    private function voucherText($row)
    {
        if (empty($row['vouchers'])) {
            return '';
        }
        return implode(', ', array_column($row['vouchers'], 'number'));
    }

    public function array(): array
    {
        $out = [];
        $out[] = ['AR PAYMENT PLANNING'];
        $out[] = ['Periode: ' . $this->periodLabel];
        $out[] = [];
        $out[] = ['No', 'Customer', 'Invoice Date', 'Invoice Number', 'Due Date', 'Voucher Number', 'Note', 'Nominal', 'Biaya Administrasi', 'PPH23', 'Total', 'Status'];

        foreach ($this->rows as $i => $r) {
            $out[] = [
                $i + 1,
                $r['customer_name'],
                $r['invoice_date'],
                $r['invoice_number'],
                $r['due_date'],
                $this->voucherText($r),
                $r['note'],
                $r['nominal'],
                $r['biaya_administrasi'],
                $r['pph23'],
                $r['total'],
                $this->statusLabel($r),
            ];
        }

        $out[] = ['', '', '', '', '', '', 'GRAND TOTAL', $this->grand['nominal'], $this->grand['biaya_administrasi'], $this->grand['pph23'], $this->grand['total'], ''];

        return $out;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5, 'B' => 22, 'C' => 12, 'D' => 16, 'E' => 12,
            'F' => 18, 'G' => 26, 'H' => 14, 'I' => 16, 'J' => 12, 'K' => 14, 'L' => 16,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastCol = Coordinate::stringFromColumnIndex(self::TOTAL_COLS);

                $headerRow = 4;
                $dataStart = 5;
                $grandRow  = $dataStart + count($this->rows);
                $approvalLabelRow = $grandRow + 2;

                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->getStyle('A2')->getFont()->setBold(true);

                $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
                    'font'      => ['bold' => true],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEF2F7']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle("A{$headerRow}:{$lastCol}{$grandRow}")
                    ->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('C9D2DF');

                $sheet->mergeCells("A{$grandRow}:G{$grandRow}");
                $sheet->getStyle("A{$grandRow}:G{$grandRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("A{$grandRow}:{$lastCol}{$grandRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E9EDF3']],
                ]);

                foreach ([self::IDX_NOMINAL, self::IDX_BIAYA, self::IDX_PPH23, self::IDX_TOTAL] as $idx) {
                    $c = Coordinate::stringFromColumnIndex($idx);
                    $sheet->getStyle("{$c}{$dataStart}:{$c}{$grandRow}")
                        ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                    $sheet->getStyle("{$c}{$dataStart}:{$c}{$grandRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                $this->drawApprovalBlock($sheet, $approvalLabelRow, self::IDX_NOMINAL, self::IDX_BIAYA, self::IDX_PPH23, self::IDX_TOTAL);
            },
        ];
    }
}
