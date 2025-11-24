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

        foreach ($grouped as $customerId => $transactions) {
            // stable group key
            $groupKey = 'customer-' . $customerId;

            // display name from first transaction (preserve actual customer label)
            $displayName = $transactions->first()->customer_name ?? 'Unknown Customer';

            // compute numeric group total and optionally running balance per group
            $groupTotalRaw = 0.0;
            foreach ($transactions as $t) {
                // Prefer using amount if the query delivered it; otherwise compute
                $amountRaw = isset($t->amount) ? (float) $t->amount : ((float)($t->sales_price ?? 0) * (float)($t->quantity ?? 0) - (float)($t->discount ?? 0));
                $groupTotalRaw += $amountRaw;
            }

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
            ]);

            // then push each transaction under this header (with running balance)
            $running = 0.0;
            foreach ($transactions as $t) {
                $amountRaw = isset($t->amount) ? (float) $t->amount : ((float)($t->sales_price ?? 0) * (float)($t->quantity ?? 0) - (float)($t->discount ?? 0));
                $running += $amountRaw;
                
                $final->push([
                    'group_key' => $groupKey,
                    'customer_name' => $displayName,
                    'transaction_date' => Carbon::parse($t->transaction_date ?? now())->format('m/d/Y'),
                    'transaction_type' => $t->transaction_type ?? '',
                    'num' => $t->num ?? ($t->invoice_id ?? '-'),
                    'product_service_name' => $t->product_service_name ?? '',
                    'memo_description' => $t->memo_description ?? '',
                    'quantity' => number_format((float)($t->quantity ?? 0), 2),
                    'sales_price' => number_format((float)($t->sales_price ?? 0), 2),
                    'amount' => number_format($amountRaw, 2),
                    'balance' => number_format($running, 2),
                    'is_group_header' => false,
                ]);
            }
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
                return e($r['transaction_date']);
            })
            ->addColumn('transaction_type', fn($r) => $r['is_group_header'] ? '' : e($r['transaction_type']))
            ->addColumn('num', fn($r) => $r['is_group_header'] ? '' : e($r['num']))
            ->addColumn('product_service_name', fn($r) => $r['is_group_header'] ? '' : e($r['product_service_name']))
            ->addColumn('memo_description', fn($r) => $r['is_group_header'] ? '' : e($r['memo_description']))
            ->addColumn('quantity', fn($r) => $r['is_group_header'] ? '' : $r['quantity'])
            ->addColumn('sales_price', fn($r) => $r['is_group_header'] ? '' : $r['sales_price'])
            ->addColumn('amount', fn($r) => $r['is_group_header'] ? '' : $r['amount'])
            ->addColumn('balance', fn($r) => $r['is_group_header'] ? '' : $r['balance'])
            ->setRowAttr([
                'class' => function ($r) {
                    return !empty($r['is_group_header']) ? 'group-header' : 'group-row group-' . $r['group_key'];
                },
                'data-group' => function ($r) {
                    return $r['group_key'];
                },
                // header visible, rows hidden by default
                'style' => function ($r) {
                    return !empty($r['is_group_header'])
                        ? 'background-color:#f8f9fa; font-weight:600; cursor:pointer;'
                        : 'display:none;';
                },
            ])
            ->rawColumns(['transaction_date'])
            ;
    }

    public function query()
    {
        $user = Auth::user();

        // Determine owner ID based on user type
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();

        // Use consistent column for created_by (same as Product/Service Detail)
        $ownerColumn = 'invoices.created_by';

        // Date range
        $start = request()->get('start_date') ?? request()->get('startDate') ?? date('Y-01-01');
        $end = request()->get('end_date') ?? request()->get('endDate') ?? date('Y-m-d');
        $selectedCustomer = request('customer_name', '');

        // Build the query (aligned with SalesByProductServiceDetail)
        $query = DB::table('invoice_products')
            ->join('invoices', 'invoices.id', '=', 'invoice_products.invoice_id')
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->leftJoin('product_services', 'product_services.id', '=', 'invoice_products.product_id')
            ->where($ownerColumn, $ownerId)
            ->where('invoices.status', '!=', 0) // exclude draft invoices
            ->whereBetween('invoices.issue_date', [$start, $end])
            ->select([
                'customers.id as customer_id',
                'customers.name as customer_name',
                'invoices.issue_date as transaction_date',
                DB::raw("'Invoice' as transaction_type"),
                'invoices.invoice_id as num',
                DB::raw('COALESCE(product_services.name, "") as product_service_name'),
                DB::raw('COALESCE(product_services.description, "") as memo_description'),
                DB::raw('COALESCE(invoice_products.quantity, 0) as quantity'),
                DB::raw('COALESCE(invoice_products.price, 0) as sales_price'),
                // amount: price * qty - discount (if discount exists)
                DB::raw('COALESCE((invoice_products.price * invoice_products.quantity - COALESCE(invoice_products.discount, 0)), 0) as amount'),
                // balance can be same as amount here; you may adjust if needed
                DB::raw('COALESCE((invoice_products.price * invoice_products.quantity - COALESCE(invoice_products.discount, 0)), 0) as balance'),
            ]);

        // Apply optional customer filter
        if (!empty($selectedCustomer)) {
            $query->where('customers.name', 'LIKE', '%' . $selectedCustomer . '%');
        }

        // Order consistently so grouping order is nice
        $query->orderBy('customers.name', 'asc')
            ->orderBy('invoices.issue_date', 'asc')
            ->orderBy('product_services.name', 'asc');

        \Log::info('SalesByCustomerDetail SQL', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings()
        ]);

        return $query;
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
