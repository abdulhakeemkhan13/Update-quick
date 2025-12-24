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

        return datatables()
            ->collection($rows)
            ->addColumn('customer_name', fn($r) => $r->isGrandTotal ?? false ? $r->customer_name : ($r->customer_name ?: '-'))
            ->addColumn('total', fn($r) => $r->isGrandTotal ?? false ? '<strong>' . number_format($r->total ?? 0, 2) . '</strong>' : number_format($r->total ?? 0, 2))
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

        // Invoice Query - includes invoice-level total_discount apportioned across products
        // First, get the count of products per invoice to apportion the discount
        $invoices = DB::table('invoice_products as ip')
            ->join('invoices as i', 'i.id', '=', 'ip.invoice_id')
            ->join('customers as c', 'c.id', '=', 'i.customer_id')
            ->leftJoin(DB::raw('(SELECT invoice_id, COUNT(*) as product_count FROM invoice_products GROUP BY invoice_id) as ipc'), 'ipc.invoice_id', '=', 'i.id')
            ->select(
                'c.id as customer_id',
                'c.name as customer_name',
                DB::raw('(ip.price * ip.quantity - COALESCE(ip.discount, 0) - (COALESCE(i.total_discount, 0) / COALESCE(ipc.product_count, 1))) as total')
            )
            ->where('i.created_by', $ownerId)
            ->whereBetween('i.issue_date', [$start, $end])
            ->where('i.status', '!=', 0);

        // Credit Note Query (Negative values)
        $creditNotes = DB::table('credit_note_products as cp')
            ->join('credit_notes as cn', 'cn.id', '=', 'cp.credit_note_id')
            ->join('customers as c', 'c.id', '=', 'cn.customer')
            ->select(
                'c.id as customer_id',
                'c.name as customer_name',
                DB::raw('(-1 * (cp.price * cp.quantity - COALESCE(cp.discount, 0))) as total')
            )
            ->where('cn.created_by', $ownerId)
            ->whereBetween('cn.date', [$start, $end]);

        // Sales Receipt Query
        $salesReceipts = DB::table('sales_receipt_products as srp')
            ->join('sales_receipts as sr', 'sr.id', '=', 'srp.sales_receipt_id')
            ->join('customers as c', 'c.id', '=', 'sr.customer_id')
            ->leftJoin(DB::raw('(SELECT sales_receipt_id, COUNT(*) as product_count FROM sales_receipt_products GROUP BY sales_receipt_id) as srpc'), 'srpc.sales_receipt_id', '=', 'sr.id')
            ->select(
                'c.id as customer_id',
                'c.name as customer_name',
                DB::raw('(srp.price * srp.quantity - COALESCE(srp.discount, 0) - (COALESCE(sr.total_discount, 0) / COALESCE(srpc.product_count, 1))) as total')
            )
            ->where('sr.created_by', $ownerId)
            ->whereBetween('sr.issue_date', [$start, $end])
            ->where('sr.status', '!=', 0);

        // Unite Scripts
        $unionQuery = $invoices->unionAll($creditNotes)->unionAll($salesReceipts);

        // Final Query to aggregate by customer
        $query = DB::query()->fromSub($unionQuery, 'combined_sales')
            ->select('customer_name', DB::raw('SUM(total) as total'))
            ->groupBy('customer_id', 'customer_name')
            ->havingRaw('SUM(total) > 0') // Only show customers with positive sales
            ->orderBy('customer_name', 'asc');

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
