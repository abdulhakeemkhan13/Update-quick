<?php

namespace App\Exports;

use Dompdf\Dompdf;
use Dompdf\Options;

class UniversalDataTableExportPdf
{
    protected $collection;
    protected $columns;
    protected $pageTitle;
    protected $reportPeriod;
    protected $headerFooterAlignment;

    public function __construct($collection, array $columns = [], string $pageTitle = 'Report', string $reportPeriod = '', $headerFooterAlignment = [])
    {
        $this->collection = $collection;
        $this->columns = $columns;
        $this->pageTitle = $pageTitle;
        $this->reportPeriod = $reportPeriod;

        if (is_array($headerFooterAlignment) && count($headerFooterAlignment) === 2) {
            $this->headerFooterAlignment = $headerFooterAlignment;
        } else {
            $this->headerFooterAlignment = ['center', 'center'];
        }
    }

    /**
     * Generate PDF HTML with fixed width for A4
     */
    protected function generateHtml(): string
    {
        $html = '<html><head><style>
            body { font-family: DejaVu Sans, sans-serif; font-size:12px; }
            h1 { text-align: ' . $this->headerFooterAlignment[0] . '; font-size:24px; margin-bottom:5px; }
            .report-period { text-align: ' . $this->headerFooterAlignment[0] . '; font-size:11px; margin-top:0; color:#555; }

            /* Ensure table always fits page width */
            .table-container {
                width: 100%;
                overflow: hidden;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 15px;
                table-layout: fixed; /* lock column widths */
                word-wrap: break-word;
            }
            th, td {
                border: 0px solid #ccc;
                border-bottom: 1px solid #f3f4f6;
                padding: 6px 8px;
                font-size: 10px;
                overflow: hidden;
                text-overflow: ellipsis;
                word-break: break-word;
                text-align: center;   /* default for all cells */
            }

            /* override first column (index 1) */
            th:first-child,
            td:first-child {
                text-align: left;
            }

            h4 {
                font-size: 1.25rem;
                font-weight: 600;
            }

            th {
                background: #f1f1f1;
                font-weight: bold;
            }
            tr.bold td {
                font-weight: bold;
            }

            tr.bold td:first-child {
                text-align: left !important;
            }

            .footer {
                margin-top: 30px;
                text-align: ' . $this->headerFooterAlignment[1] . ';
                font-size: 11px;
                color: #6B7280;
            }

            h4 {
                font-size: 16px;
                font-weight: bold;
                margin: 0;
                padding: 0;
}

            td h4 {
                font-size: 16px !important;
                font-weight: bold !important;
                margin: 0;
                padding: 0;
            }
        </style></head><body>';

        // Title + period
        $html .= "<h1>{$this->pageTitle}</h1>";
        if (!empty($this->reportPeriod)) {
            $html .= "<p class='report-period'>{$this->reportPeriod}</p>";
        }

        // Table
        $html .= '<div class="table-container"><table><thead><tr>';
        foreach ($this->columns as $col) {
            $html .= "<th>{$col}</th>";
        }
        $html .= '</tr></thead><tbody>';

        foreach ($this->collection as $row) {
            $isBold = false;
            $isLeft = false;
            $rowHtml = '';

            foreach ($row as $cell) {
                $val = trim($cell);
                if (
                    stripos($val, 'subtotal') !== false ||
                    stripos($val, 'total') !== false ||
                    strpos($val, '▶') !== false ||
                    strpos($val, '▼') !== false
                ) {
                    $isBold = true;
                    $isLeft = true;
                }
                $val = strip_tags($val, '<h4><strong>');
                $val = html_entity_decode($val, ENT_QUOTES, 'UTF-8');
                $val = str_replace("\xC2\xA0", ' ', $val);
                $val = preg_replace('/\s+/', ' ', $val);
                $val = trim($val);
                $rowHtml .= '<td>' . $val . '</td>';
            }

            $html .= $isBold ? "<tr class='bold'>{$rowHtml}</tr>" : "<tr>{$rowHtml}</tr>";
        }

        $html .= '</tbody></table></div>';

        // Footer
        $html .= '<div class="footer">'
            . 'Date Prepared: ' . now()->format('d/m/Y') . '<br>'
            . 'Time Prepared: ' . now()->format('H:i:s') . '<br>'
            . 'Report Basis: Cash Basis'
            . '</div>';

        $html .= '</body></html>';

        return $html;
    }

    /**
     * Download PDF (forces file download)
     */
    public function download(string $filename)
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->generateHtml());
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->stream($filename);
    }

    /**
     * Stream PDF inline (for browser preview/print)
     */
    public function stream(string $filename)
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->generateHtml());
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }
}
