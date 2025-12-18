<?php

namespace App\DataTables;

use App\Models\Invoice;
use Carbon\Carbon;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\DB;

class InvoiceListbyDate extends DataTable
{
    public function dataTable($query)
    {
        $data = collect($query->get());

        $grandTotalAmount = 0;
        $grandOpenBalance = 0;

        // Group by Customer Name
        $groupedData = $data->groupBy('name');

        $finalData = collect();

        foreach ($groupedData as $customer => $rows) {
            $subtotalAmount = 0;
            $subtotalOpen = 0;

            foreach ($rows as $row) {
                // normalize row to object so property access is safe
                if (is_array($row)) {
                    $row = (object) $row;
                }

                // ensure these properties exist to avoid undefined property notices
                $row->subtotal = data_get($row, 'subtotal', 0);
                $row->total_tax = data_get($row, 'total_tax', 0);
                $row->open_balance = data_get($row, 'open_balance', 0);
                $row->age = data_get($row, 'age', 0);
                $row->issue_date = data_get($row, 'issue_date', '');
                $row->due_date = data_get($row, 'due_date', '');
                $row->invoice = data_get($row, 'invoice', data_get($row, 'id', ''));

                $subtotalAmount += ($row->subtotal ?? 0) + ($row->total_tax ?? 0);
                $subtotalOpen += ($row->open_balance ?? 0);

                $row->customer = $customer;
                $row->past_due = ($row->age > 0) ? $row->age . ' Days' : '-';

                $finalData->push($row);
            }

            $grandTotalAmount += $subtotalAmount;
            $grandOpenBalance += $subtotalOpen;
        }

        // Add grand total row (normalize as object and include same properties)
        $finalData->push((object) [
            'transaction'   => '<strong>Grand Total</strong>',
            'issue_date'    => '',
            'due_date'      => '',
            'customer'      => '',
            'past_due'      => '',
            'type'          => '',
            'status_label'  => '',
            'age'           => '',
            'total_amount'  => $grandTotalAmount,
            'open_balance'  => $grandOpenBalance,
            'isGrandTotal'  => true,
        ]);

        return datatables()
            ->collection($finalData)
            ->addColumn('transaction', function ($row) {
                // safe access
                $isGrand = data_get($row, 'isGrandTotal', false);
                if ($isGrand) {
                    return data_get($row, 'transaction', '');
                }
                $invoice = data_get($row, 'invoice', data_get($row, 'id', ''));
                return \Auth::user()->invoiceNumberFormat($invoice);
            })
            ->addColumn('issue_date', function ($row) {
                return data_get($row, 'issue_date', '');
            })
            ->addColumn('past_due', function ($row) {
                return data_get($row, 'past_due', '');
            })
            ->addColumn('type', function ($row) {
                return data_get($row, 'isGrandTotal') ? '' : 'Invoice';
            })
            ->addColumn('status_label', function ($row) {
                if (data_get($row, 'isGrandTotal')) return '';
                $status = data_get($row, 'status', 0);
                $labels = \App\Models\Invoice::$statues;
                $classes = [
                    0 => 'nbg-secondary',
                    1 => 'nbg-warning',
                    2 => 'nbg-danger',
                    3 => 'nbg-info',
                    4 => 'nbg-primary',
                    5 => 'nbg-primary',
                    6 => 'nbg-primary',
                    7 => 'nbg-primary',
                ];
                return '<span class="status_badger badger text-whiter ' . ($classes[$status] ?? 'bg-secondary') . ' p-2 px-3 rounded">'
                    . __($labels[$status] ?? '-') . '</span>';
            })
            ->addColumn('due_date', function ($row) {
                return data_get($row, 'due_date', '');
            })
            ->editColumn('total_amount', function ($row) {
                if (data_get($row, 'isGrandTotal')) {
                    return number_format(data_get($row, 'total_amount', 0), 2);
                }
                $total = (float) data_get($row, 'subtotal', 0) + (float) data_get($row, 'total_tax', 0);
                return number_format($total, 2);
            })
            ->editColumn('open_balance', function ($row) {
                return number_format((float) data_get($row, 'open_balance', 0), 2);
            })
            ->rawColumns(['customer', 'transaction', 'status_label']);
    }

    // -------------------------------
    // FIXED QUERY
    // -------------------------------
    public function query(Invoice $model)
    {
        $start = request()->get('start_date')
            ?? request()->get('startDate')
            ?? Carbon::now()->startOfYear()->format('Y-m-d');

        $end = request()->get('end_date')
            ?? request()->get('endDate')
            ?? Carbon::now()->endOfDay()->format('Y-m-d');

        return $model->newQuery()
            ->select(
                'invoices.id',
                'invoices.invoice_id as invoice',
                'invoices.issue_date',
                'invoices.due_date',
                'invoices.status',
                'customers.name',

                // Subtotal
                DB::raw('(SELECT IFNULL(SUM((price * quantity) - discount), 0)
                          FROM invoice_products
                          WHERE invoice_products.invoice_id = invoices.id) AS subtotal'),

                // Tax
                DB::raw('(SELECT IFNULL(SUM((price * quantity - discount) * (taxes.rate / 100)), 0)
                          FROM invoice_products
                          LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoice_products.tax) > 0
                          WHERE invoice_products.invoice_id = invoices.id) AS total_tax'),

                // Payments (total) - NOTE: This already includes credit memo applications from QBO
                DB::raw('(SELECT IFNULL(SUM(amount), 0)
                          FROM invoice_payments
                          WHERE invoice_payments.invoice_id = invoices.id) AS payment_total'),

                // Open balance = invoice_total - payments
                // NOTE: Credit memos are already included in invoice_payments.amount when applied via QBO
                DB::raw('(
                    (
                        (SELECT IFNULL(SUM((price * quantity) - discount), 0)
                         FROM invoice_products
                         WHERE invoice_products.invoice_id = invoices.id)
                        +
                        (SELECT IFNULL(SUM((price * quantity - discount) * (taxes.rate / 100)), 0)
                         FROM invoice_products
                         LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoice_products.tax) > 0
                         WHERE invoice_products.invoice_id = invoices.id)
                    )
                    -
                    (SELECT IFNULL(SUM(amount), 0)
                     FROM invoice_payments
                     WHERE invoice_payments.invoice_id = invoices.id)
                ) AS open_balance'),

                DB::raw('GREATEST(DATEDIFF(CURDATE(), invoices.due_date), 0) AS age')
            )
            ->leftJoin('customers', 'customers.id', '=', 'invoices.customer_id')
            ->where('invoices.created_by', \Auth::user()->creatorId())
            ->whereBetween('invoices.issue_date', [$start, $end])
            ->groupBy('invoices.id');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('customer-balance-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0, 'asc')
            ->parameters([
                'paging' => false,
                'searching' => false,
                'info' => false,
                'ordering' => false,
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('issue_date')->title('Date'),
            Column::make('transaction')->title('Transaction'),
            Column::make('type')->title('Type'),
            Column::make('customer')->title('Customer Name'),
            Column::make('status_label')->title('Status'),
            Column::make('due_date')->title('Due Date'),
            Column::make('total_amount')->title('Amount'),
            Column::make('open_balance')->title('Open Balance'),
        ];
    }
}
