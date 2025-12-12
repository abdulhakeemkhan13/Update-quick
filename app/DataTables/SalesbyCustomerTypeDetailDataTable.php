<?php

namespace App\DataTables;

use App\Models\InvoiceProduct;
use App\Models\Tax;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class SalesbyCustomerTypeDetailDataTable extends DataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->of($query)
            // Transaction Date column
            ->addColumn(
                'transaction_date',
                fn($row) =>
                $row->transaction_date
                ? Carbon::parse($row->transaction_date)->format('m/d/Y')
                : '-'
            )
            
            // Transaction Type column
            ->addColumn('transaction_type', fn($row) => $row->transaction_type ?? 'Invoice')
            
            // Num (Invoice Number) column  
            ->addColumn('num', fn($row) => $row->num ?? $row->ref_number ?? '-')
            
            // Product/Service Full Name column
            ->addColumn('product_service', fn($row) => $row->product_service ?? '-')
            
            // Memo/Description column
            ->addColumn(
                'memo_description',
                fn($row) => $row->memo_description ?: ($row->ref_number ? "Invoice Ref #" . $row->ref_number : '-')
            )
            
            // Quantity column
            ->addColumn('quantity', fn($row) => number_format(($row->quantity ?? 0), 2))
            
            // Sales Price column
            ->addColumn('sales_price', fn($row) => number_format(($row->sales_price ?? 0), 2))
            
            // Amount column
            ->addColumn('amount', fn($row) => number_format(($row->amount ?? 0), 2))
            
            // Balance column
            ->addColumn('balance', fn($row) => number_format(($row->balance ?? 0), 2))
            
            // Customer Type for grouping
            ->addColumn('customer_type', fn($row) => $row->customer_type ?? '-')
            
            // Customer name for reference
            ->addColumn('customer_name', fn($row) => $row->customer_name ?? '-')
            
            ->rawColumns(['transaction_date', 'transaction_type', 'num', 'product_service', 'memo_description', 'quantity', 'sales_price', 'amount', 'balance', 'customer_type', 'customer_name']);
    }

    public function query(InvoiceProduct $model)
    {
        $start = request()->get('start_date') ?? date('Y-01-01');
        $end = request()->get('end_date') ?? date('Y-m-d');
        
        // First query: Invoices with products (positive amounts)
        $invoices = \DB::table('invoices as i')
            ->leftJoin('invoice_products as ip', 'i.id', '=', 'ip.invoice_id')
            ->leftJoin('customers as c', 'i.customer_id', '=', 'c.id')
            ->leftJoin('customer_types as ct', 'c.type_id', '=', 'ct.id')
            ->leftJoin('product_services as ps', 'ip.product_id', '=', 'ps.id')
            ->select(
                \DB::raw('"invoice" as doc_type'),
                'i.id as doc_db_id',
                'ip.id as product_line_id',  // Add unique product line ID
                'i.invoice_id as num',
                'i.issue_date as transaction_date',
                'i.ref_number',
                'i.customer_id',
                'c.name as customer_name',
                'ct.name as customer_type',
                'ps.name as product_service',
                'ip.description as memo_description',
                'ip.quantity',
                'ip.price as sales_price',
                \DB::raw('COALESCE(ip.quantity, 0) * COALESCE(ip.price, 0) as amount'),
                \DB::raw('"Invoice" as transaction_type')
            )
            ->where(function($dateQuery) use ($start, $end) {
                $dateQuery->whereBetween(\DB::raw('DATE(i.issue_date)'), [$start, $end])
                          ->orWhereBetween(\DB::raw('DATE(i.send_date)'), [$start, $end]);
            })
            ->where('i.created_by', \Auth::user()->creatorId());

        // Second query: Credit Memos with products (negative amounts)
        $creditMemos = \DB::table('credit_notes as cn')
            ->leftJoin('credit_note_products as cnp', 'cn.id', '=', 'cnp.credit_note_id')
            ->leftJoin('customers as c', 'cn.customer', '=', 'c.id')
            ->leftJoin('customer_types as ct', 'c.type_id', '=', 'ct.id')
            ->leftJoin('product_services as ps', 'cnp.product_id', '=', 'ps.id')
            ->select(
                \DB::raw('"credit_memo" as doc_type'),
                'cn.id as doc_db_id',
                'cnp.id as product_line_id',  // Add unique product line ID
                'cn.credit_note_id as num',
                'cn.date as transaction_date',
                \DB::raw('NULL as ref_number'),
                'cn.customer as customer_id',
                'c.name as customer_name',
                'ct.name as customer_type',
                'ps.name as product_service',
                'cnp.description as memo_description',
                \DB::raw('COALESCE(cnp.quantity, 0) * -1 as quantity'),  // Negative quantity
                'cnp.price as sales_price',
                \DB::raw('COALESCE(cnp.quantity, 0) * COALESCE(cnp.price, 0) * -1 as amount'), // Negative amount
                \DB::raw('"Credit Memo" as transaction_type')
            )
            ->whereBetween(\DB::raw('DATE(cn.date)'), [$start, $end])
            ->where('c.created_by', \Auth::user()->creatorId()); // Filter by customer's creator

        // UNION both queries and sort
        return $invoices
            ->unionAll($creditMemos)
            ->orderByRaw('transaction_date ASC, num ASC, doc_db_id ASC, product_line_id ASC');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('ledger-table')
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
                'scrollX' => true,
                'scrollY' => '420px',
                'scrollCollapse' => true,

                // === FRONTEND GROUP BY CUSTOMER TYPE WITH COUNTS ===
                'drawCallback' => <<<JS
    function(settings) {
        var api = this.api();
        var data = api.rows({page:'current'}).data().toArray();

        // 1️⃣ Group data by customer type and count entries
        var typeGroups = {};
        data.forEach(function(row) {
            var customerType = row.customer_type || '-';
            var amount = parseFloat(row.amount.replace(/,/g, '')) || 0;
            
            if (!typeGroups[customerType]) {
                typeGroups[customerType] = {
                    total: 0,
                    count: 0,
                    rows: []
                };
            }
            typeGroups[customerType].total += amount;
            typeGroups[customerType].count += 1;
            typeGroups[customerType].rows.push(row);
        });

        // 2️⃣ Clear table body
        var tbody = $(api.table().body());
        tbody.empty();

        // 3️⃣ Calculate grand total
        var grandTotal = 0;
        Object.keys(typeGroups).forEach(function(type) {
            grandTotal += typeGroups[type].total;
        });

        // 4️⃣ Render each customer type group
        Object.keys(typeGroups).forEach(function(customerType) {
            var group = typeGroups[customerType];
            var totalFormatted = group.total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            
            // Group header row with count
            var header = $('<tr class="group-header" style="cursor:pointer; background:#f9fafb; font-weight:600;">' +
                '<td colspan="9" style="padding:10px 16px;">' +
                    '<span class="toggle-arrow" style="display:inline-block; width:20px;">▶</span> ' +
                    customerType + ' (' + group.count + ')' +
                '</td>' +
            '</tr>');
            tbody.append(header);

            // Type detail rows (hidden by default)
            group.rows.forEach(function(rowData) {
                var rowNode = $('<tr class="type-detail-row" style="display:none;">' +
                    '<td style="padding:8px 16px; padding-left:40px;">' + rowData.transaction_date + '</td>' +
                    '<td style="padding:8px 16px;">' + rowData.transaction_type + '</td>' +
                    '<td style="padding:8px 16px;">' + rowData.num + '</td>' +
                    '<td style="padding:8px 16px;">' + rowData.product_service + '</td>' +
                    '<td style="padding:8px 16px;">' + (rowData.memo_description || '-') + '</td>' +
                    '<td class="text-right" style="padding:8px 16px;">' + rowData.quantity + '</td>' +
                    '<td class="text-right" style="padding:8px 16px;">' + rowData.sales_price + '</td>' +
                    '<td class="text-right" style="padding:8px 16px;">' + rowData.amount + '</td>' +
                    '<td class="text-right" style="padding:8px 16px;">' + rowData.balance + '</td>' +
                '</tr>');
                tbody.append(rowNode);
            });
            
            // Subtotal row for this type (hidden by default)
            var subtotalRow = $('<tr class="type-subtotal" style="display:none; font-weight:600; background:#f3f4f6;">' +
                '<td colspan="7" class="text-right" style="padding:8px 16px;"></td>' +
                '<td class="text-right" style="padding:8px 16px;">' + totalFormatted + '</td>' +
                '<td class="text-right" style="padding:8px 16px;"></td>' +
            '</tr>');
            tbody.append(subtotalRow);
        });

        // 5️⃣ Add TOTAL row at the bottom
        var grandTotalFormatted = grandTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        var totalRow = $('<tr style="font-weight:700; background:#e5e7eb; border-top:2px solid #9ca3af;">' +
            '<td colspan="7" style="padding:12px 16px;">TOTAL</td>' +
            '<td class="text-right" style="padding:12px 16px;">\$' + grandTotalFormatted + '</td>' +
            '<td></td>' +
        '</tr>');
        tbody.append(totalRow);

        // 6️⃣ Toggle expand/collapse on group header click
        $('.group-header').off('click').on('click', function() {
            var arrow = $(this).find('.toggle-arrow');
            var detailsR = $(this).nextUntil('.group-header, tr:not(.type-detail-row,.type-subtotal)').filter('.type-detail-row');
            var subtotal = $(this).nextUntil('.group-header').filter('.type-subtotal');
            
            if (detailsR.first().is(':visible')) {
                // Collapse
                detailsR.hide();
                subtotal.hide();
                arrow.text('▶');
            } else {
                // Expand
                detailsR.show();
                subtotal.show();
                arrow.text('▼');
            }
        });

        // 7️⃣ Start with all groups collapsed
        $('.group-header').each(function() {
            $(this).find('.toggle-arrow').text('▶');
        });
    }
JS

            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('transaction_date')->title('Transaction date')->width('120px'),
            Column::make('transaction_type')->title('Transaction type')->width('120px'),
            Column::make('num')->title('Num')->width('100px'),
            Column::make('product_service')->title('Product/Service full name')->width('200px'),
            Column::make('memo_description')->title('Memo/Description')->width('200px'),
            Column::make('quantity')->title('Quantity')->width('80px')->addClass('text-right'),
            Column::make('sales_price')->title('Sales price')->width('100px')->addClass('text-right'),
            Column::make('amount')->title('Amount')->width('120px')->addClass('text-right'),
            Column::make('balance')->title('Balance')->width('120px')->addClass('text-right'),
        ];
    }

    protected function filename(): string
    {
        return 'SalesByCustomerTypeDetail_' . date('YmdHis');
    }
}
