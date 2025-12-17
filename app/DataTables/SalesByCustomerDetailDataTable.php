<?php

namespace App\DataTables;

use App\Models\InvoiceProduct;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class SalesByCustomerDetailDataTable extends DataTable
{
    public function dataTable($query)
    {
        // Fetch rows from the builder
        $rows = $query->get();

        $final = collect();

        // Group by stable customer_id (ensures names/spaces/casing don't split groups)
        $grouped = $rows->groupBy('customer_id');

        $grandTotal = 0.0;
        $grandTotalQuantity = 0.0;

        $currencySymbol = \Auth::user()->currencySymbol();

        $formatWithCurrency = function ($val) use ($currencySymbol) {
            $formatted = \Auth::user()->priceFormat($val);
            if (strpos($formatted, $currencySymbol) === false) {
                return $currencySymbol . ' ' . $formatted;
            }
            return $formatted;
        };

        foreach ($grouped as $customerId => $transactions) {
            // stable group key
            $groupKey = 'customer-' . $customerId;

            // display name from first transaction (preserve actual customer label)
            $displayName = $transactions->first()->customer_name ?? 'Unknown Customer';

            // compute numeric group total and optionally running balance per group
            $groupTotalRaw = 0.0;
            $groupTotalQuantity = 0.0;
            foreach ($transactions as $t) {
                // Use amount from query
                $amountRaw = (float) ($t->amount ?? 0);
                $quantityRaw = (float) ($t->quantity ?? 0);
                $priceRaw = (float) ($t->sales_price ?? 0);
                $groupTotalRaw += $amountRaw;
                // Don't add quantity if price and amount are both 0
                if (!($priceRaw == 0 && $amountRaw == 0)) {
                    $groupTotalQuantity += $quantityRaw;
                }
            }

            $grandTotal += $groupTotalRaw;
            $grandTotalQuantity += $groupTotalQuantity;

            // push group header first (so header appears above items)
            $final->push([
                'group_key' => $groupKey,
                'customer_name' => $displayName,
                'transaction_date' => '', // header uses this column to show name + chevron
                'transaction_type' => '',
                'num' => '',
                'product_service_name' => '',
                'memo_description' => '',
                'quantity' => '',
                'sales_price' => '',
                'amount' => '',
                'balance' => number_format($groupTotalRaw, 2),
                'is_group_header' => true,
                'is_total_row' => false,
                'is_grand_total' => false,
            ]);

            // then push each transaction under this header (with running balance)
            $running = 0.0;
            foreach ($transactions as $t) {
                $amountRaw = (float) ($t->amount ?? 0);
                $running += $amountRaw;

                $final->push([
                    'group_key' => $groupKey,
                    'customer_name' => $displayName,
                    'transaction_date' => Carbon::parse($t->transaction_date ?? now())->format('m/d/Y'),
                    'transaction_type' => $t->transaction_type ?? '',
                    'num' => $t->num ?? '-',
                    'product_service_name' => $t->product_service_name ?? '',
                    'memo_description' => $t->memo_description ?? '',
                    'quantity' => number_format((float)($t->quantity ?? 0), 2),
                    'sales_price' => number_format((float)($t->sales_price ?? 0), 2),
                    'amount' => number_format($amountRaw, 2),
                    'balance' => number_format($running, 2),
                    'is_group_header' => false,
                    'is_total_row' => false,
                    'is_grand_total' => false,
                ]);
            }

            // Add total row for the customer
            $final->push([
                'group_key' => $groupKey,
                'customer_name' => $displayName,
                'transaction_date' => '<strong>Total for ' . e($displayName) . '</strong>',
                'transaction_type' => '',
                'num' => '',
                'product_service_name' => '',
                'memo_description' => '',
                'quantity' => '<strong>' . number_format($groupTotalQuantity, 2) . '</strong>',
                'sales_price' => '',
                'amount' => '<strong>' . $formatWithCurrency($groupTotalRaw) . '</strong>',
                'balance' => '<strong>' . $formatWithCurrency($groupTotalRaw) . '</strong>',
                'is_group_header' => false,
                'is_total_row' => true,
                'is_grand_total' => false,
            ]);
        }

        // Add grand total row
        if ($final->isNotEmpty()) {
            $final->push([
                'group_key' => 'grand-total',
                'customer_name' => 'Grand Total',
                'transaction_date' => '<strong>Grand Total</strong>',
                'transaction_type' => '',
                'num' => '',
                'product_service_name' => '',
                'memo_description' => '',
                'quantity' => '<strong>' . number_format($grandTotalQuantity, 2) . '</strong>',
                'sales_price' => '',
                'amount' => '<strong>' . $formatWithCurrency($grandTotal) . '</strong>',
                'balance' => '<strong>' . $formatWithCurrency($grandTotal) . '</strong>',
                'is_group_header' => false,
                'is_total_row' => false,
                'is_grand_total' => true,
            ]);
        }

        // Return a datatables collection where headers have a chevron toggle element
        return datatables()
            ->collection($final)
            ->addColumn('transaction_date', function ($r) {
                if (!empty($r['is_group_header'])) {
                    // header: chevron + customer name + total
                    return '<span class="group-toggle" data-group="' . e($r['group_key']) . '" style="cursor:pointer;">'
                        . '<i class="fas fa-chevron-right me-2"></i>'
                        . '<strong>' . e($r['customer_name']) . '</strong>'
                        . ' <span class="text-muted"></span>'
                        . '</span>';
                }
                if (!empty($r['is_total_row']) || !empty($r['is_grand_total'])) {
                    // For total rows, return the label directly (it might have HTML)
                    return $r['transaction_date'];
                }
                return e($r['transaction_date']);
            })
            ->addColumn('transaction_type', fn($r) => (!empty($r['is_group_header']) || !empty($r['is_total_row']) || !empty($r['is_grand_total'])) ? '' : e($r['transaction_type']))
            ->addColumn('num', fn($r) => (!empty($r['is_group_header']) || !empty($r['is_total_row']) || !empty($r['is_grand_total'])) ? '' : e($r['num']))
            ->addColumn('product_service_name', fn($r) => (!empty($r['is_group_header']) || !empty($r['is_total_row']) || !empty($r['is_grand_total'])) ? '' : e($r['product_service_name']))
            ->addColumn('memo_description', fn($r) => (!empty($r['is_group_header']) || !empty($r['is_total_row']) || !empty($r['is_grand_total'])) ? '' : e($r['memo_description']))
            ->addColumn('quantity', fn($r) => (!empty($r['is_group_header'])) ? '' : $r['quantity'])
            ->addColumn('sales_price', fn($r) => (!empty($r['is_group_header']) || !empty($r['is_total_row']) || !empty($r['is_grand_total'])) ? '' : $r['sales_price'])
            ->addColumn('amount', fn($r) => $r['amount']) // Already formatted or styled string
            ->addColumn('balance', fn($r) => $r['balance']) // Already formatted or styled string
            ->setRowAttr([
                'class' => function ($r) {
                    if (!empty($r['is_grand_total'])) {
                        return 'summary-total';
                    }
                    if (!empty($r['is_total_row'])) {
                        return 'group-row group-' . $r['group_key'] . ' font-weight-bold';
                    }
                    return !empty($r['is_group_header']) ? 'group-header' : 'group-row group-' . $r['group_key'];
                },
                'data-group' => function ($r) {
                    return $r['group_key'];
                },
                // header visible, rows hidden by default
                'style' => function ($r) {
                    if (!empty($r['is_grand_total'])) {
                        return 'background-color:#e2e8f0; font-weight:700;';
                    }
                    if (!empty($r['is_total_row'])) {
                        // Total rows visible, styled bold
                        return 'background-color:#fefeff; font-weight:700; display:table-row;';
                    }
                    return !empty($r['is_group_header'])
                        ? 'background-color:#f8f9fa; font-weight:600; cursor:pointer;'
                        : 'display:none;';
                },
            ])
            ->rawColumns(['transaction_date', 'quantity', 'amount', 'balance'])
            ;
    }

    public function query()
    {
        $user = Auth::user();

        // Determine owner ID based on user type
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();

        // Date range
        $start = request()->get('start_date') ?? request()->get('startDate') ?? date('Y-01-01');
        $end = request()->get('end_date') ?? request()->get('endDate') ?? date('Y-m-d');
        $selectedCustomer = request('customer_name', '');

        // Query 1: Invoice Products
        $invoiceQuery = DB::table('invoice_products')
            ->join('invoices', 'invoices.id', '=', 'invoice_products.invoice_id')
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->leftJoin('product_services', 'product_services.id', '=', 'invoice_products.product_id')
            ->where('invoices.created_by', $ownerId)
            ->where('invoices.status', '!=', 0) // exclude draft invoices
            ->whereBetween('invoices.issue_date', [$start, $end])
            ->select([
                'customers.id as customer_id',
                'customers.name as customer_name',
                'invoices.issue_date as transaction_date',
                DB::raw("'Invoice' as transaction_type"),
                'invoices.invoice_id as num',
                DB::raw('COALESCE(product_services.name, "") as product_service_name'),
                DB::raw('COALESCE(invoice_products.description, "") as memo_description'),
                DB::raw('COALESCE(invoice_products.quantity, 0) as quantity'),
                DB::raw('COALESCE(invoice_products.price, 0) as sales_price'),
                // amount: price * qty - discount
                DB::raw('COALESCE((invoice_products.price * invoice_products.quantity - COALESCE(invoice_products.discount, 0)), 0) as amount'),
            ]);

        // Query 2: Credit Note Products
        $creditQuery = DB::table('credit_note_products')
            ->join('credit_notes', 'credit_notes.id', '=', 'credit_note_products.credit_note_id')
            ->join('customers', 'customers.id', '=', 'credit_notes.customer')
            ->leftJoin('product_services', 'product_services.id', '=', 'credit_note_products.product_id')
            ->where('credit_notes.created_by', $ownerId)
            ->whereBetween('credit_notes.date', [$start, $end])
            ->select([
                'customers.id as customer_id',
                'customers.name as customer_name',
                'credit_notes.date as transaction_date',
                DB::raw("'Credit Memo' as transaction_type"),
                'credit_notes.credit_note_id as num',
                DB::raw('COALESCE(product_services.name, "") as product_service_name'),
                DB::raw('COALESCE(credit_note_products.description, "") as memo_description'),
                DB::raw('(COALESCE(credit_note_products.quantity, 0) * -1) as quantity'),
                DB::raw('COALESCE(credit_note_products.price, 0) as sales_price'),
                // amount: (price * qty - discount) * -1 to make negative
                DB::raw('(COALESCE((credit_note_products.price * credit_note_products.quantity - COALESCE(credit_note_products.discount, 0)), 0) * -1) as amount'),
            ]);

        // Apply optional customer filter to both
        if (!empty($selectedCustomer)) {
            $invoiceQuery->where('customers.name', 'LIKE', '%' . $selectedCustomer . '%');
            $creditQuery->where('customers.name', 'LIKE', '%' . $selectedCustomer . '%');
        }

        // Union the queries
        $invoiceQuery->unionAll($creditQuery);

        // Order by customer, then by num (invoice/credit note id), then date
        $invoiceQuery->orderBy('customer_name', 'asc')
            ->orderBy('num', 'asc')
            ->orderBy('transaction_date', 'asc');

        \Log::info('SalesByCustomerDetail SQL', [
            'sql' => $invoiceQuery->toSql(),
            'bindings' => $invoiceQuery->getBindings()
        ]);

        return $invoiceQuery;
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
                'order' => [[0, 'asc']],
                'colReorder' => true,
                'fixedHeader' => true,
                'scrollY' => '420px',
                'scrollX' => true,
                'scrollCollapse' => true,
                'columnDefs' => [
                    [
                        'targets' => [5, 6, 7, 8],
                        'className' => 'text-right'
                    ]
                ],
                'language' => [
                    'emptyTable' => 'No Data Found for the selected period.',
                    'zeroRecords' => 'No Data Found'
                ]
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('transaction_date')->title(__('Transaction Date'))->visible(true)->width('12%'),
            Column::make('transaction_type')->title(__('Transaction Type'))->visible(true)->width('12%'),
            Column::make('num')->title(__('Num'))->visible(true)->width('10%'),
            Column::make('product_service_name')->title(__('Product/Service Full Name'))->visible(true)->width('20%'),
            Column::make('memo_description')->title(__('Memo/Description'))->visible(true)->width('18%'),
            Column::make('quantity')->title(__('Quantity'))->visible(true)->addClass('text-right')->width('8%'),
            Column::make('sales_price')->title(__('Sales Price'))->visible(true)->addClass('text-right')->width('10%'),
            Column::make('amount')->title(__('Amount'))->visible(true)->addClass('text-right')->width('10%'),
            Column::make('balance')->title(__('Balance'))->visible(true)->addClass('text-right')->width('10%'),
        ];
    }

    protected function filename(): string
    {
        return 'SalesByCustomerDetail_' . date('YmdHis');
    }
}
