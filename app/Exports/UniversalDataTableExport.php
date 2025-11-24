<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;

class UniversalDataTableExport implements FromCollection, WithHeadings, WithStyles, WithEvents
{
    protected $collection;
    protected $columns;
    protected $pageTitle;
    protected $reportPeriod;
    protected $headerFooterAlignment;
    protected $allowSingleColumnBold;


    public function __construct($collection, array $columns = [], string $pageTitle = 'Report', string $reportPeriod = '', $headerFooterAlignment = [], $allowSingleColumnBold = false)
    {
        $this->collection = $collection->map(function ($row) {
            if (isset($row['id']))
                unset($row['id']);

            // foreach ($row as $key => $value) {
            // $row[$key] = trim(str_replace('▼', '', strip_tags($value)));
            // }

            foreach ($row as $key => $value) {
                if (is_string($value)) {
                    // Decode HTML entities, including &nbsp;
                    $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                    // Strip unwanted tags but keep <b>, <strong>, <h4>
                    $decoded = strip_tags($decoded, '<h4><strong><b>');

                    // Replace non-breaking spaces with normal spaces
                    $decoded = str_replace("\xC2\xA0", ' ', $decoded);
                    $decoded = str_replace('&nbsp;', ' ', $decoded);

                    $row[$key] = trim($decoded);
                }
            }


            return $row;
        });

        $this->columns = $columns;
        $this->pageTitle = $pageTitle;
        $this->reportPeriod = $reportPeriod;

        // If empty string was passed, fallback to defaults
        if (is_array($headerFooterAlignment) && count($headerFooterAlignment) === 2) {
            $this->headerFooterAlignment = $headerFooterAlignment;
        } else {
            $this->headerFooterAlignment = ['center', 'center']; // default
        }

        $this->allowSingleColumnBold = $allowSingleColumnBold;
    }

    public function collection()
    {
        return $this->collection;
    }

    public function headings(): array
    {
        $headings = [
            [$this->pageTitle],
        ];

        if (!empty($this->reportPeriod)) {
            $headings[] = [$this->reportPeriod];
        }

        $headings[] = [];
        $headings[] = $this->columns;

        return $headings;
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [
            1 => ['font' => ['bold' => true, 'size' => 24]],
        ];

        $headerRow = empty($this->reportPeriod) ? 3 : 4;
        $styles[$headerRow] = ['font' => ['bold' => true]];

        return $styles;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $columnCount = count($this->columns);
                $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnCount);

                // HEADER ALIGNMENT
                $headerAlign = strtolower($this->headerFooterAlignment[0] ?? 'center');

                // Merge + align title
                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->getStyle("A1:{$lastColumn}1")->getAlignment()
                    ->setHorizontal($headerAlign)
                    ->setVertical('center');
                $sheet->getRowDimension(1)->setRowHeight(34);

                // Report period row (if exists)
                if (!empty($this->reportPeriod)) {
                    $sheet->mergeCells("A2:{$lastColumn}2");
                    $sheet->getStyle("A2:{$lastColumn}2")->getAlignment()
                        ->setHorizontal($headerAlign)
                        ->setVertical('center');
                    $sheet->getRowDimension(2)->setRowHeight(20);
                    $sheet->getStyle("A2:{$lastColumn}2")->getFont();
                }

                // Auto-size columns
                foreach (range(1, $columnCount) as $colIndex) {
                    $sheet->getColumnDimensionByColumn($colIndex)->setAutoSize(true);
                }

                $highestRow = $sheet->getHighestRow();
                $headerRow = empty($this->reportPeriod) ? 3 : 4;

                // Style column headers
                $sheet->getRowDimension($headerRow)->setRowHeight(22);
                $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")
                    ->getFont()->setBold(true);
                $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")
                    ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F1F1F1');
                $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")
                    ->getAlignment()
                    ->setHorizontal('center')
                    ->setVertical('center')
                    ->setIndent(1);

                // Apply alignment + styles to data rows (same as before)
    
                if ($this->collection->isEmpty()) {
                    $noDataRow = $headerRow + 1;

                    // Force the merge across all columns (at least one)
                    $lastColumn = $columnCount > 0
                        ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnCount)
                        : 'A';

                    $sheet->mergeCells("A4:D4");
                    $sheet->getStyle("A4:D4")->getAlignment()
                        ->setHorizontal($headerAlign)
                        ->setVertical('center');
                    $sheet->getRowDimension(1)->setRowHeight(34);
                } else {
                    for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
                        $isBoldRow = false;
                        foreach (range(1, $columnCount) as $colIndex) {
                            $cellValue = trim($sheet->getCellByColumnAndRow($colIndex, $row)->getValue());
                            if (
                                stripos($cellValue, 'subtotal') !== false ||
                                stripos($cellValue, 'total') !== false ||
                                strpos($cellValue, '▶') !== false ||
                                strpos($cellValue, '▼') !== false
                            ) {
                                $isBoldRow = true;
                                $plainValue = html_entity_decode(strip_tags($cellValue));

                                // Replace non-breaking spaces (&nbsp;) with normal spaces
                                $plainValue = str_replace("\xC2\xA0", ' ', $plainValue);

                                // Trim leading/trailing spaces
                                $plainValue = trim($plainValue);

                                $sheet->setCellValueByColumnAndRow($colIndex, $row, $plainValue);


                            }
                            // ✅ detect if <h4>, <strong>, <b> existed
                            else if (
                                stripos($cellValue, '<h4') !== false ||
                                stripos($cellValue, '<strong') !== false || // note: allow attributes like <strong class="">
                                stripos($cellValue, '<b') !== false         // same for <b ...>
                            ) {
                                // strip only after detection
                                $plainValue = trim(html_entity_decode(strip_tags($cellValue)));
                                $sheet->setCellValueByColumnAndRow($colIndex, $row, $plainValue);

                                // Bold this particular cell
                                $sheet->getStyleByColumnAndRow($colIndex, $row)->getFont()->setBold(true);
                            }


                            if ($colIndex === 1 && !empty($cellValue)) {
                                $emptyOtherColumns = true;
                                foreach (range(2, $columnCount) as $checkCol) {
                                    if (trim($sheet->getCellByColumnAndRow($checkCol, $row)->getValue()) !== '') {
                                        $emptyOtherColumns = false;
                                        break;
                                    }
                                }
                                if ($emptyOtherColumns && $this->allowSingleColumnBold) {
                                    $isBoldRow = true;
                                }
                            }
                            $sheet->getStyleByColumnAndRow($colIndex, $row)
                                ->getAlignment()
                                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT)
                                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
                                ->setIndent(1);
                        }
                        if ($sheet->getRowDimension($row)->getRowHeight() === -1) {
                            $sheet->getRowDimension($row)->setRowHeight(18);
                        }
                        if ($isBoldRow) {
                            $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->getFont()->setBold(true);
                        }
                    }
                }

                // FOOTER ALIGNMENT
                $footerAlign = strtolower($this->headerFooterAlignment[1] ?? 'center');

                $footerRowStart = $highestRow + 2;
                $footerTexts = [
                    'Date Prepared: ' . now()->format('d/m/Y'),
                    'Time Prepared: ' . now()->format('H:i:s'),
                    'Report Basis: Cash Basis'
                ];

                foreach ($footerTexts as $i => $text) {
                    $row = $footerRowStart + $i;
                    $sheet->setCellValueByColumnAndRow(1, $row, $text);
                    $sheet->mergeCellsByColumnAndRow(1, $row, $columnCount, $row);
                    $sheet->getStyleByColumnAndRow(1, $row)->getAlignment()
                        ->setHorizontal($footerAlign)
                        ->setVertical('center');
                    $sheet->getStyleByColumnAndRow(1, $row)->getFont()
                        ->setSize(11)
                        ->getColor()->setRGB('6B7280');

                    if ($sheet->getRowDimension($row)->getRowHeight() === -1) {
                        $sheet->getRowDimension($row)->setRowHeight(18);
                    }
                }
            },
        ];
    }
}
