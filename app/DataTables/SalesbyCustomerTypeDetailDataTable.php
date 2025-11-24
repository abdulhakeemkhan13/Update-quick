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
        $dataTable = datatables()
            ->eloquent($query)
            ->addColumn('transaction_type', fn($row) => 'Invoice')
            ->addColumn(
                'transaction_date',
                fn($row) =>
                optional($row->invoice)->issue_date
                ? Carbon::parse($row->invoice->issue_date)->format('m/d/Y')
                : 'No date available'
            )
            ->addColumn(
                'invoice_number',
                fn($row) =>
                optional($row->invoice)->ref_number ?? $row->invoice_id ?? '-'
            )
            ->addColumn(
                'memo_description',
                fn($row) =>
                $row->description ?: (
                    optional($row->invoice)->ref_number
                    ? "Invoice Ref #" . optional($row->invoice)->ref_number
                    : '-'
                )
            )
            ->addColumn(
                'customer_name',
                fn($row) =>
                optional(optional($row->invoice)->customer)->name ?? '-'
            )
            ->addColumn('quantity', fn($row) => number_format(($row->quantity ?? 0), 2))
            ->addColumn('sales_price', fn($row) => number_format(($row->price ?? 0), 2))
            ->addColumn('amount', fn($row) => number_format(($row->price ?? 0) * ($row->quantity ?? 0), 2))
            ->addColumn('balance', fn($row) => number_format(optional($row->invoice)->getDue() ?? 0, 2));

        $dataTable->filter(function ($query) {
            $start = request()->get('start_date') ?? date('Y-01-01');
            $end = request()->get('end_date') ?? date('Y-m-d');
            $query->whereHas('invoice', function ($q) use ($start, $end) {
                $q->whereBetween(\DB::raw('DATE(issue_date)'), [$start, $end])
                    ->where('created_by', \Auth::user()->creatorId());
            });
        });

        return $dataTable;
    }

    public function query(InvoiceProduct $model)
    {
        return $model->with(['invoice.customer'])
            ->whereHas('invoice', function ($q) {
                $start = request()->get('start_date') ?? date('Y-01-01');
                $end = request()->get('end_date') ?? date('Y-m-d');
                $q->whereBetween(\DB::raw('DATE(issue_date)'), [$start, $end])
                    ->where('created_by', \Auth::user()->creatorId());
            });
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
                'scrollX' => true,
                'scrollY' => '420px',
                'scrollCollapse' => true,

                // === FRONTEND GROUP BY CUSTOMER ===
                'drawCallback' => <<<JS
    function(settings) {
        var api = this.api();
        var rows = api.rows({page:'current'}).nodes();
        var data = api.rows({page:'current'}).data().toArray();

        // 1️⃣ Compute totals grouped by customer
        var customerGroups = {};
        data.forEach(function(row) {
            var customer = row.customer_name || '-';
            var amount = parseFloat(row.amount.replace(/,/g, '')) || 0;
            if (!customerGroups[customer]) {
                customerGroups[customer] = {
                    total: 0,
                    rows: []
                };
            }
            customerGroups[customer].total += amount;
            customerGroups[customer].rows.push(row);
        });

        // 2️⃣ Clear table body completely (we’ll re-render it grouped)
        var tbody = $(api.table().body());
        tbody.empty();

        // 3️⃣ Insert one header per unique customer, then all their rows
        Object.keys(customerGroups).forEach(function(customer) {
            var group = customerGroups[customer];
            var totalFormatted = group.total.toLocaleString(undefined, { minimumFractionDigits: 2 });
            
            // Group header row
            var header = $('<tr class="group bg-light" style="font-weight:bold;cursor:pointer;">' +
                '<td colspan="9"><span class="chevron">▶</span> ' + customer + ' (Total: ' + totalFormatted + ')</td>' +
            '</tr>');
            tbody.append(header);

            // Customer rows
            group.rows.forEach(function(rowData) {
                var rowNode = $('<tr>' +
                    '<td>' + rowData.transaction_type + '</td>' +
                    '<td>' + rowData.transaction_date + '</td>' +
                    '<td>' + rowData.invoice_number + '</td>' +
                    '<td>' + rowData.memo_description + '</td>' +
                    '<td>' + rowData.customer_name + '</td>' +
                    '<td class="text-right">' + rowData.quantity + '</td>' +
                    '<td class="text-right">' + rowData.sales_price + '</td>' +
                    '<td class="text-right">' + rowData.amount + '</td>' +
                    '<td class="text-right">' + rowData.balance + '</td>' +
                '</tr>');
                tbody.append(rowNode);
            });
        });

        // 4️⃣ Add expand/collapse toggle
        $('.group').off('click').on('click', function() {
            var chevron = $(this).find('.chevron');
            var next = $(this).nextUntil('.group');
            if (next.is(':visible')) {
                next.hide();
                chevron.text('▶');
            } else {
                next.show();
                chevron.text('▼');
            }
        });

        // 5️⃣ Start collapsed
        $('.group').each(function() {
            $(this).nextUntil('.group').hide();
        });
    }
JS

            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('transaction_type')->title('Transaction Type')->width('150px'),
            Column::make('transaction_date')->title('Transaction Date')->width('120px'),
            Column::make('invoice_number')->title('Invoice Number / Num')->width('120px'),
            Column::make('memo_description')->title('Memo/Description')->width('200px'),
            Column::make('customer_name')->title('Customer Name')->width('150px'),
            Column::make('quantity')->title('Quantity')->width('100px')->addClass('text-right'),
            Column::make('sales_price')->title('Sales Price')->width('100px')->addClass('text-right'),
            Column::make('amount')->title('Amount')->width('120px')->addClass('text-right'),
            Column::make('balance')->title('Balance')->width('120px')->addClass('text-right'),
        ];
    }

    protected function filename(): string
    {
        return 'SalesByCustomerTypeDetail_' . date('YmdHis');
    }
}
