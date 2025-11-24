<?php

namespace App\DataTables;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class SalesByCustomerSummaryDataTable extends DataTable
{
    public function dataTable($query)
    {

        // Get all rows first so we can manipulate and add a grand total row
        $rows = collect($query->get());

        // Calculate grand total
        $grandTotal = $rows->sum('total');

        // Push grand total row
        $rows->push((object) [
            'customer_name' => '<strong>Grand Total</strong>',
            'total' => $grandTotal,
            'isGrandTotal' => true,
        ]);

        /*return datatables()
            ->of($query)
            ->addColumn('customer_name', function ($row) {
                return $row->customer_name ?: '-';
            })
            ->addColumn('total', function ($row) {
                $amount = (float) ($row->total ?: 0);
                return number_format($amount);
            })
            ->rawColumns(['customer_name', 'total'])
            ->with([
                'totals' => $this->calculateTotals($query)
            ]);*/

        return datatables()
            ->collection($rows)
            ->addColumn('customer_name', fn($r) => $r->isGrandTotal ?? false ? $r->customer_name : ($r->customer_name ?: '-'))
            ->addColumn('total', fn($r) => $r->isGrandTotal ?? false ? '<strong>' . number_format($r->total ?? 0) . '</strong>' : number_format($r->total ?? 0))
            ->rawColumns(['customer_name', 'total']);
    }

    private function calculateTotals($query)
    {
        if (is_object($query) && method_exists($query, 'sum')) {
            // For DB query builder
            $totalSales = $query->sum('total');
            $customerCount = $query->count();
        } else {
            // For collection (sample data)
            $totalSales = $query->sum('total');
            $customerCount = $query->count();
        }

        return [
            'total_sales' => $totalSales,
            'customer_count' => $customerCount
        ];
    }

    public function query()
    {
        $user = Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();

        // Get start and end dates from request, fallback to defaults
        $start = request()->get('start_date')
            ?? request()->get('startDate')
            ?? date('Y-01-01');
        $end = request()->get('end_date')
            ?? request()->get('endDate')
            ?? date('Y-m-d');

        // Query based on memory knowledge: invoices don't have 'total' field
        // Must calculate from invoice_products: (price * quantity - discount) + tax
        $query = DB::table('customers')
            ->select([
                'customers.name as customer_name',
                // DB::raw('
                //     COALESCE(
                //         SUM(
                //             (invoice_products.price * invoice_products.quantity) - 
                //             COALESCE(invoice_products.discount, 0) + 
                //             COALESCE(
                //                 (
                //                     SELECT SUM(t.rate * ((invoice_products.price * invoice_products.quantity) - COALESCE(invoice_products.discount, 0)) / 100)
                //                     FROM taxes t
                //                     WHERE FIND_IN_SET(t.id, COALESCE(invoice_products.tax, "")) > 0
                //                 ), 0
                //             )
                //         ), 0
                //     ) as total'
                // )
                DB::raw('
    COALESCE(
        SUM(
            (invoice_products.price * invoice_products.quantity) - COALESCE(invoice_products.discount, 0)
        ), 0
    ) as total'
                )


            ])
            ->leftJoin('invoices', function ($join) use ($ownerId, $start, $end) {
                $join->on('customers.id', '=', 'invoices.customer_id')
                    ->where('invoices.created_by', $ownerId)
                    ->whereBetween('invoices.issue_date', [$start, $end])
                    ->where('invoices.status', '!=', 0); // Exclude draft (0)
            })
            ->leftJoin('invoice_products', 'invoice_products.invoice_id', '=', 'invoices.id')
            ->where('customers.created_by', $ownerId)
            ->groupBy('customers.id', 'customers.name')
            ->havingRaw('total > 0') // Only show customers with sales
            ->orderBy('total', 'desc');

        // Debug the query
        \Log::info('Sales by Customer Query:', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'owner_id' => $ownerId,
            'start_date' => $start,
            'end_date' => $end
        ]);

        // Get results and log count
        $results = $query->get();
        \Log::info('Query Results Count: ' . $results->count());

        if ($results->count() > 0) {
            \Log::info('Sample results:', $results->take(3)->toArray());
        } else {
            \Log::info('No sales data found in database');
        }

        return $query;
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('customer-balance-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('rt') // Only table, no pagination or search
            ->parameters([
                'responsive' => true,
                'autoWidth' => false,
                'paging' => false,
                'searching' => false,
                'info' => false,
                'ordering' => false,
                'order' => [[1, 'desc']], // Sort by Total descending
                'columnDefs' => [
                    [
                        'targets' => [1], // Total column
                        'className' => 'text-right'
                    ]
                ],
                'language' => [
                    'emptyTable' => 'No sales data found for the selected period.',
                    'zeroRecords' => 'No customer sales found.'
                ]
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('customer_name')
                ->title('Customer')
                ->width('70%'),
            Column::make('total')
                ->title('Total')
                ->width('30%')
                ->addClass('text-right')
        ];
    }

    protected function filename(): string
    {
        return 'SalesByCustomerSummary_' . date('YmdHis');
    }
}
