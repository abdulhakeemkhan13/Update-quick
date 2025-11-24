<?php

namespace App\DataTables;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;


class TaxableSalesDetailDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $dt = new EloquentDataTable($query);

        // Compute grand totals
        $totals = $query->clone()
            ->selectRaw('
            SUM(invoice_products.quantity) as total_quantity,
            SUM(invoice_products.price) as total_sales_price,
            SUM(invoice_products.quantity * invoice_products.price) as total_amount,
            SUM(COALESCE(tot.invoice_total,0) - COALESCE(pay.paid_amount,0)) as total_balance
        ')
            ->first();

        return $dt
            ->editColumn('transaction_date', fn($r) => $r->transaction_date)
            ->editColumn('transaction_type', fn() => 'Invoice')
            ->with([
                'grand_total' => [
                    'quantity' => number_format($totals->total_quantity ?? 0, 2),
                    'sales_price' => number_format($totals->total_sales_price ?? 0, 2),
                    'amount' => number_format($totals->total_amount ?? 0, 2),
                    'balance' => number_format($totals->total_balance ?? 0, 2),
                ]
            ])
            ->rawColumns([])
            ->escapeColumns([]);
    }


    public function query(): QueryBuilder
    {
        $user = Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $ownerColumn = $user->type === 'company' ? 'invoices.created_by' : 'invoices.owned_by';

        // $startDate        = $this->request()->get('start_date');
        // $endDate          = $this->request()->get('end_date');

        $startDate = request()->get('start_date')
            ?? request()->get('startDate')
            ?? Carbon::now()->startOfYear()->format('Y-m-d');

        $endDate = request()->get('end_date')
            ?? request()->get('endDate')
            ?? Carbon::now()->endOfDay()->format('Y-m-d');

        $reportPeriod = $this->request()->get('report_period', 'all_dates');
        $accountingMethod = $this->request()->get('accounting_method', 'accrual');
        $selectedCustomer = $this->request()->get('customer_name');
        $selectedCategory = $this->request()->get('category');
        $selectedType = $this->request()->get('type');
        $selectedProdName = $this->request()->get('product_name');

        // Sum of payments per invoice
        $payments = DB::table('invoice_payments')
            ->selectRaw('invoice_id, COALESCE(SUM(amount),0) AS paid_amount')
            ->groupBy('invoice_id');

        // Sum of ALL invoice lines per invoice (invoice total)
        $invoiceTotals = DB::table('invoice_products')
            ->selectRaw('invoice_id, COALESCE(SUM(price * quantity),0) AS invoice_total')
            ->groupBy('invoice_id');

        // Base: one row per TAXABLE invoice line
        $q = Invoice::query()
            ->where($ownerColumn, $ownerId)
            // include all statuses (draft/partial/unpaid/paid); remove filter if you had one
            ->join('invoice_products', 'invoices.id', '=', 'invoice_products.invoice_id')
            ->join('product_services', 'invoice_products.product_id', '=', 'product_services.id')
            ->join('customers', 'invoices.customer_id', '=', 'customers.id')
            ->leftJoinSub($payments, 'pay', function ($join) {
                $join->on('invoices.id', '=', 'pay.invoice_id');
            })
            ->leftJoinSub($invoiceTotals, 'tot', function ($join) {
                $join->on('invoices.id', '=', 'tot.invoice_id');
            })
            ->whereNotNull('invoice_products.tax')
            ->where('invoice_products.tax', '!=', '') // stored as CSV ids
            ->selectRaw("
                DATE(invoices.issue_date)                           AS transaction_date,
                'Invoice'                                          AS transaction_type,
                invoices.invoice_id                                 AS num,
                customers.name                                      AS customer_name,
                COALESCE(invoice_products.description, product_services.name) AS memo,
                invoice_products.quantity                            AS quantity,
                invoice_products.price                               AS sales_price,
                (invoice_products.quantity * invoice_products.price) AS amount,
                (COALESCE(tot.invoice_total,0) - COALESCE(pay.paid_amount,0)) AS balance
            ");

        // Date filters
        if ($accountingMethod === 'cash') {
            // filter by payment date for cash-basis
            $q->leftJoin('invoice_payments as ip2', 'invoices.id', '=', 'ip2.invoice_id');
            if ($startDate && $endDate) {
                $q->whereBetween('ip2.date', [$startDate, $endDate]);
            } else {
                [$s, $e] = $this->getDateRange($reportPeriod);
                $q->whereBetween('ip2.date', [$s, $e]);
            }
        } else {
            // accrual -> use issue_date
            if ($startDate && $endDate) {
                $q->whereBetween('invoices.issue_date', [$startDate, $endDate]);
            } else {
                [$s, $e] = $this->getDateRange($reportPeriod);
                $q->whereBetween('invoices.issue_date', [$s, $e]);
            }
        }

        // Additional filters
        if (!empty($selectedCustomer))
            $q->where('customers.name', 'like', '%' . $selectedCustomer . '%');
        if (!empty($selectedCategory))
            $q->where('product_services.category_id', $selectedCategory);
        if (!empty($selectedType))
            $q->where('product_services.type', $selectedType);
        if (!empty($selectedProdName))
            $q->where('product_services.name', 'like', '%' . $selectedProdName . '%');

        // Order like QuickBooks: date asc, then customer
        return $q->orderBy('invoices.issue_date')
            ->orderBy('customers.name');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('taxable-sales-detail-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('t')
            ->paging(false)
            ->ordering(false);
    }

    protected function getColumns(): array
    {
        return [
            Column::make('transaction_date')->title(__('Transaction date'))->addClass('text-start'),
            Column::make('transaction_type')->title(__('Transaction type'))->addClass('text-start'),
            Column::make('num')->title(__('Num'))->addClass('text-start'),
            Column::make('customer_name')->title(__('Customer full name'))->addClass('text-start'),
            Column::make('memo')->title(__('Memo/Description'))->addClass('text-start'),
            Column::make('quantity')->title(__('Quantity'))->addClass('text-end'),
            Column::make('sales_price')->title(__('Sales price'))->addClass('text-end'),
            Column::make('amount')->title(__('Amount'))->addClass('text-end'),
            Column::make('balance')->title(__('Balance'))->addClass('text-end'),
        ];
    }

    protected function filename(): string
    {
        return 'TaxableSalesDetail_' . date('YmdHis');
    }

    private function getDateRange($period)
    {
        $now = now();
        return match ($period) {
            'today' => [$now->toDateString(), $now->toDateString()],
            'this_week' => [$now->copy()->startOfWeek()->toDateString(), $now->copy()->endOfWeek()->toDateString()],
            'this_month' => [$now->copy()->startOfMonth()->toDateString(), $now->copy()->endOfMonth()->toDateString()],
            'this_quarter' => [$now->copy()->startOfQuarter()->toDateString(), $now->copy()->endOfQuarter()->toDateString()],
            'this_year' => [$now->copy()->startOfYear()->toDateString(), $now->copy()->endOfYear()->toDateString()],
            'last_week' => [$now->copy()->subWeek()->startOfWeek()->toDateString(), $now->copy()->subWeek()->endOfWeek()->toDateString()],
            'last_month' => [$now->copy()->subMonth()->startOfMonth()->toDateString(), $now->copy()->subMonth()->endOfMonth()->toDateString()],
            'last_quarter' => [$now->copy()->subQuarter()->startOfQuarter()->toDateString(), $now->copy()->subQuarter()->endOfQuarter()->toDateString()],
            'last_year' => [$now->copy()->subYear()->startOfYear()->toDateString(), $now->copy()->subYear()->endOfYear()->toDateString()],
            default => ['2000-01-01', $now->toDateString()],
        };
    }
}
