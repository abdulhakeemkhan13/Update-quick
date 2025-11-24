<?php

namespace App\DataTables;

use App\Models\ProductService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;

class SalesByProductServiceSummaryDataTable extends DataTable
{
    public function dataTable($query)
    {
        $rows = $query->get();

        // === Compute Totals (use numeric types) ===
        $totalQuantity = (float) $rows->sum('total_quantity');
        $totalAmount   = (float) $rows->sum('total_amount');
        $totalCogs     = (float) $rows->sum(fn($r) => (float)($r->purchase_price ?? 0) * (float)($r->total_quantity ?? 0));
        $totalGrossMargin = $totalAmount - $totalCogs;
        $totalGrossMarginPercent = $totalAmount > 0 ? ($totalGrossMargin / $totalAmount) * 100 : 0;
        $avgCogs = $totalQuantity > 0 ? $totalCogs / $totalQuantity : 0;
        $avgPrice = $totalQuantity > 0 ? $totalAmount / $totalQuantity : 0;
        $data = collect();

        foreach ($rows as $r) {
            $qty = (float) ($r->total_quantity ?? 0);
            $amt = (float) ($r->total_amount ?? 0);
            $cogs = (float) ($r->purchase_price ?? 0) * $qty;
            $grossMargin = $amt - $cogs;
            $grossMarginPercent = $amt > 0 ? ($grossMargin / $amt) * 100 : 0;

            $data->push([
                // product_service shown as plain text (we'll allow HTML in totals row via rawColumns)
                'product_service' => $r->name ?? '-',
                // numeric columns are formatted numbers (no currency symbol)
                'quantity' => number_format($qty, 2),
                'amount' => number_format($amt, 2),
                'percent_of_sales' => $totalAmount > 0
                    ? number_format(($amt / $totalAmount) * 100, 1) . '%'
                    : '0.0%',
                'average_price' => number_format($qty > 0 ? ($amt / $qty) : 0, 2),
                'cogs' => number_format($cogs, 2),
                'avg_cogs' => number_format((float)($r->purchase_price ?? 0), 2),
                'gross_margin' => number_format($grossMargin, 2),
                'gross_margin_percent' => number_format($grossMarginPercent, 1) . '%',
            ]);
        }

        // === Add Total Row ===
        if ($rows->count() > 0) {
            $data->push([
                'product_service' => '<strong>Total</strong>',
                'quantity' => '<strong>' . number_format($totalQuantity, 2) . '</strong>',
                'amount' => '<strong>' . number_format($totalAmount, 2) . '</strong>',
                'percent_of_sales' => '<strong>100%</strong>',
                'average_price' => '<strong>' . number_format($avgPrice, 2) . '</strong>',
                'cogs' => '<strong>' . number_format($totalCogs, 2) . '</strong>',
                'avg_cogs' => '<strong>' . number_format($avgCogs, 2) . '</strong>',
                'gross_margin' => '<strong>' . number_format($totalGrossMargin, 2) . '</strong>',
                'gross_margin_percent' => '<strong>' . number_format($totalGrossMarginPercent, 1) . '%</strong>',
                'DT_RowClass' => 'summary-total'
            ]);
        } else {
            $data->push([
                'product_service' => 'No data found for the selected period.',
                'quantity' => '',
                'amount' => '',
                'percent_of_sales' => '',
                'average_price' => '',
                'cogs' => '',
                'avg_cogs' => '',
                'gross_margin' => '',
                'gross_margin_percent' => '',
                'DT_RowClass' => 'no-data-row'
            ]);
        }

        return datatables()
            ->collection($data)
            // allow HTML only for product_service (Total row) and for numeric totals we already wrapped with <strong>
            ->rawColumns([
                'product_service',
                'quantity',
                'amount',
                'percent_of_sales',
                'average_price',
                'cogs',
                'avg_cogs',
                'gross_margin',
                'gross_margin_percent'
            ]);
    }

    public function query()
    {
        $user = Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = $user->type === 'company' ? 'created_by' : 'owned_by';

        // === Determine Date Range ===
        $reportPeriod = request('report_period', 'all_dates');
        $startDate = request('start_date') ?? request('startDate') ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $endDate   = request('end_date') ?? request('endDate') ?? Carbon::now()->endOfDay()->format('Y-m-d');

        if ($reportPeriod && !in_array($reportPeriod, ['all_dates', 'custom'])) {
            $dates = $this->calculateDateRange($reportPeriod);
            $startDate = $dates['start'];
            $endDate   = $dates['end'];
        }

        // === Subquery: Invoices joined with products ===
        $invoiceProductsSubquery = DB::table('invoice_products as ip')
            ->join('invoices as i', 'i.id', '=', 'ip.invoice_id')
            ->leftJoin('taxes as t', DB::raw('FIND_IN_SET(t.id, ip.tax)'), '>', DB::raw('0'))
            ->select(
                'ip.product_id',
                DB::raw('SUM(ip.quantity) as total_quantity'),
                DB::raw('SUM(ip.price * ip.quantity - COALESCE(ip.discount, 0)) as total_amount')
            )
            ->where('i.' . $column, $ownerId)
            ->where('i.status', '!=', 0);

        if ($startDate) {
            $invoiceProductsSubquery->whereDate('i.issue_date', '>=', $startDate);
        }
        if ($endDate) {
            $invoiceProductsSubquery->whereDate('i.issue_date', '<=', $endDate);
        }

        $invoiceProductsSubquery->groupBy('ip.product_id');

        // === Main Query ===
        $model = new ProductService();
        $q = $model->newQuery()
            ->where('product_services.' . $column, $ownerId)
            ->leftJoinSub($invoiceProductsSubquery, 'sales', function ($join) {
                $join->on('product_services.id', '=', 'sales.product_id');
            })
            ->select([
                'product_services.*',
                DB::raw('COALESCE(sales.total_quantity, 0) as total_quantity'),
                DB::raw('COALESCE(sales.total_amount, 0) as total_amount'),
            ])
            ->having('total_quantity', '>', 0);

        // === Optional Filters ===
        if (request()->filled('product_name')) {
            $q->where('product_services.name', 'like', '%' . request('product_name') . '%');
        }
        if (request()->filled('category')) {
            $q->where('product_services.category_id', request('category'));
        }
        if (request()->filled('type')) {
            $q->where('product_services.type', request('type'));
        }

        return $q->orderBy('total_amount', 'DESC');
    }

    private function calculateDateRange($period)
    {
        $today = Carbon::today();

        return match ($period) {
            'today' => ['start' => $today->format('Y-m-d'), 'end' => $today->format('Y-m-d')],
            'this_week' => ['start' => $today->startOfWeek()->format('Y-m-d'), 'end' => $today->endOfWeek()->format('Y-m-d')],
            'this_month' => ['start' => $today->startOfMonth()->format('Y-m-d'), 'end' => $today->endOfMonth()->format('Y-m-d')],
            'this_quarter' => ['start' => $today->startOfQuarter()->format('Y-m-d'), 'end' => $today->endOfQuarter()->format('Y-m-d')],
            'this_year' => ['start' => $today->startOfYear()->format('Y-m-d'), 'end' => $today->endOfYear()->format('Y-m-d')],
            'last_week' => ['start' => $today->subWeek()->startOfWeek()->format('Y-m-d'), 'end' => $today->endOfWeek()->format('Y-m-d')],
            'last_month' => ['start' => $today->subMonth()->startOfMonth()->format('Y-m-d'), 'end' => $today->endOfMonth()->format('Y-m-d')],
            'last_quarter' => ['start' => $today->subQuarter()->startOfQuarter()->format('Y-m-d'), 'end' => $today->endOfQuarter()->format('Y-m-d')],
            'last_year' => ['start' => $today->subYear()->startOfYear()->format('Y-m-d'), 'end' => $today->endOfYear()->format('Y-m-d')],
            'last_7_days' => ['start' => Carbon::today()->subDays(7)->format('Y-m-d'), 'end' => Carbon::today()->format('Y-m-d')],
            'last_30_days' => ['start' => Carbon::today()->subDays(30)->format('Y-m-d'), 'end' => Carbon::today()->format('Y-m-d')],
            'last_90_days' => ['start' => Carbon::today()->subDays(90)->format('Y-m-d'), 'end' => Carbon::today()->format('Y-m-d')],
            'last_12_months' => ['start' => Carbon::today()->subMonths(12)->format('Y-m-d'), 'end' => Carbon::today()->format('Y-m-d')],
            default => ['start' => null, 'end' => null],
        };
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('customer-balance-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('rt')
            ->parameters([
                'responsive' => true,
                'autoWidth' => false,
                'paging' => false,
                'searching' => false,
                'info' => false,
                'ordering' => false,
                'colReorder' => true,
                'fixedHeader' => true,
                'scrollY' => '400px',
                'scrollX' => true, // allow horizontal scroll so equal widths are respected
                'scrollCollapse' => true,
            ]);
    }

    protected function getColumns()
    {
        // 9 columns -> use equal widths (11% each; leaves a little room)
        $colWidth = '11%';

        return [
            Column::make('product_service')->title(__('Product/Service'))->addClass('text-left')->width($colWidth),
            Column::make('quantity')->title(__('Quantity'))->addClass('text-right')->width($colWidth),
            Column::make('amount')->title(__('Amount'))->addClass('text-right')->width($colWidth),
            Column::make('percent_of_sales')->title(__('% Of Sales'))->addClass('text-right')->width($colWidth),
            Column::make('average_price')->title(__('Avg. Price'))->addClass('text-right')->width($colWidth),
            Column::make('cogs')->title(__('COGS'))->addClass('text-right')->width($colWidth),
            Column::make('avg_cogs')->title(__('Avg. COGS'))->addClass('text-right')->width($colWidth),
            Column::make('gross_margin')->title(__('Gross Margin'))->addClass('text-right')->width($colWidth),
            Column::make('gross_margin_percent')->title(__('Gross Margin %'))->addClass('text-right')->width($colWidth),
        ];
    }

    protected function filename(): string
    {
        return 'SalesByProductServiceSummary_' . date('YmdHis');
    }
}
