<?php

namespace App\DataTables;

use App\Models\Invoice;
use Carbon\Carbon;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\DB;

class OpenInvoiceList extends DataTable
{
    public function dataTable($query)
    {
        $data = collect($query->get());

        $grandTotalAmount = 0;
        $grandBalanceDue = 0;

        // ✅ Group by Customer Name
        $groupedData = $data->groupBy('name');

        $finalData = collect();

        foreach ($groupedData as $customer => $rows) {
            $subtotalAmount = 0;
            $subtotalDue = 0;

            // Header row for this customer
            $finalData->push((object) [
                // 'customer' => '<strong>' . $customer . '</strong>',
                // 'transaction' => '<strong>' . $customer . '</strong>',
                'customer' => $customer,
                'transaction' => '<span class="" data-bucket="' . \Str::slug($customer) . '"> <span class="icon">▼</span> <strong>' . $customer . '</strong></span>',
                'due_date' => '',
                'past_due' => null,
                'type' => null,
                'status_label' => '',
                // 'age' => '',
                'total_amount' => null,
                'balance_due' => null,
                'isPlaceholder' => true,
                'open_balance' => null,
                'isSubtotal' => true,
                'isParent' => true
            ]);

            foreach ($rows as $row) {
                $subtotalAmount += ($row->subtotal ?? 0) + ($row->total_tax ?? 0);
                $subtotalDue += $row->balance_due;
                $row->customer = $customer;
                $row->past_due = $row->age > 0 ? $row->age . ' Days' : '-'; // 👈 Past Due column
                // $row->customer = $customer;
                $finalData->push($row);
            }

            // Subtotal row for this customer
            $finalData->push((object) [
                // 'customer' => '<strong>Subtotal</strong>',
                'customer' => $customer,
                'transaction' => '<strong>Subtotal for ' . $customer . '</strong>',
                'due_date' => '',
                'past_due' => '',
                'type' => '',
                'status_label' => '',
                // 'age' => '',
                'total_amount' => $subtotalAmount,
                'balance_due' => $subtotalDue,
                'isSubtotal' => true,
            ]);

            // $finalData->push((object) [
            //     'customer' => '',
            //     'transaction' => '',
            //     'due_date' => '',
            //     'past_due' => '',
            //     'type' => '',
            //     'status_label' => '',
            //     // 'age' => '',
            //     'total_amount' => 0,
            //     'balance_due' => 0,
            //     'isPlaceholder' => true,
            //     'isSubtotal' => true
            // ]);

            $grandTotalAmount += $subtotalAmount;
            $grandBalanceDue += $subtotalDue;
        }

        // ✅ Add grand total row
        $finalData->push((object) [
            // 'customer' => '<strong>Grand Total</strong>',
            'transaction' => '<strong>Grand Total</strong>',
            'due_date' => '',
            'past_due' => '',
            'type' => '',
            'status_label' => '',
            'age' => '',
            'total_amount' => $grandTotalAmount,
            'balance_due' => $grandBalanceDue,
            'isGrandTotal' => true,
        ]);

        return datatables()
            ->collection($finalData)
            ->addColumn('transaction', function ($row) {
                if (isset($row->isSubtotal) || isset($row->isGrandTotal) || isset($row->isPlaceholder)) {
                    return $row->transaction ?? '';
                }

                return \Auth::user()->invoiceNumberFormat($row->invoice ?? ($row->id ?? ''));
            })

            ->addColumn('due_date', fn($row) => $row->due_date ?? '')
            // ->addColumn('past_due', fn($row) => $row->past_due ?? '')
            ->addColumn(
                'type',
                fn($row) =>
                isset($row->isSubtotal) || isset($row->isGrandTotal) ? '' : 'Invoice'
            )
            // ->addColumn('status_label', function ($row) {
            //     if (isset($row->isSubtotal) || isset($row->isGrandTotal)) {
            //         return '';
            //     }
            //     $status = $row->status ?? 0;
            //     $labels = \App\Models\Invoice::$statues;
            //     $classes = [
            //         0 => 'bg-secondary',
            //         1 => 'bg-warning',
            //         2 => 'bg-danger',
            //         3 => 'bg-info',
            //         4 => 'bg-primary',
            //     ];
            //     return '<span class="status_badge badge text-white ' . ($classes[$status] ?? 'bg-secondary') . ' p-2 px-3 rounded">'
            //         . __($labels[$status] ?? '-') . '</span>';
            // })
            ->addColumn('issue_date', fn($row) => $row->issue_date ?? '')
            // ->editColumn('total_amount', function ($row) {
            //     if (isset($row->isPlaceholder)) {
            //         return '';
            //     }
            //     if (isset($row->isSubtotal) || isset($row->isGrandTotal)) {
            //         return number_format($row->total_amount ?? 0);
            //     }
            //     $total = ($row->subtotal ?? 0) + ($row->total_tax ?? 0);
            //     return number_format($total);
            // })
            ->addColumn('open_balance', function ($row) {
                if (isset($row->isPlaceholder)) {
                    return '';
                }
                if (isset($row->isSubtotal) || isset($row->isGrandTotal)) {
                    return number_format($row->balance_due ?? 0);
                }
                return number_format($row->balance_due ?? 0);
            })
            ->setRowClass(function ($row) {
                if (property_exists($row, 'isParent') && $row->isParent) {
                    return 'parent-row toggle-bucket bucket-' . \Str::slug($row->customer ?? 'na');
                }

                if (property_exists($row, 'isSubtotal') && $row->isSubtotal && !property_exists($row, 'isGrandTotal')) {
                    return 'subtotal-row bucket-' . \Str::slug($row->customer ?? 'na');
                }

                if (
                    !property_exists($row, 'isParent') &&
                    !property_exists($row, 'isSubtotal') &&
                    !property_exists($row, 'isGrandTotal') &&
                    !property_exists($row, 'isPlaceholder')
                ) {
                    return 'child-row bucket-' . \Str::slug($row->customer ?? 'na');
                }

                if (property_exists($row, 'isGrandTotal') && $row->isGrandTotal) {
                    return 'grandtotal-row';
                }

                return '';
            })
            // ->editColumn(
            //     'balance_due',
            //     fn($row) =>
            //     isset($row->isPlaceholder) ? '' : number_format($row->balance_due ?? 0)
            // )
            ->rawColumns(['customer', 'transaction', 'status_label']);
    }

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

            // ✅ total (sum of line items)
            DB::raw('(
                SELECT IFNULL(SUM((price * quantity) - discount), 0)
                FROM invoice_products
                WHERE invoice_products.invoice_id = invoices.id
            ) AS subtotal'),

            // ✅ tax
            DB::raw('(
                SELECT IFNULL(SUM((price * quantity - discount) * (taxes.rate / 100)), 0)
                FROM invoice_products
                LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoice_products.tax) > 0
                WHERE invoice_products.invoice_id = invoices.id
            ) AS total_tax'),

            // ✅ paid
            DB::raw('(
                SELECT IFNULL(SUM(amount), 0)
                FROM invoice_payments
                WHERE invoice_payments.invoice_id = invoices.id
            ) AS pay_price'),

            // ✅ credit notes
            DB::raw('(
                SELECT IFNULL(SUM(amount), 0)
                FROM credit_notes
                WHERE credit_notes.invoice = invoices.id
            ) AS credit_price'),

            // ✅ open balance = subtotal + tax − paid − credit
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
                    -
                    (
                        (SELECT IFNULL(SUM(amount), 0)
                         FROM invoice_payments
                         WHERE invoice_payments.invoice_id = invoices.id)
                        +
                        (SELECT IFNULL(SUM(amount), 0)
                         FROM credit_notes
                         WHERE credit_notes.invoice = invoices.id)
                    )
                )
            ) AS balance_due'),

            DB::raw('GREATEST(DATEDIFF(CURDATE(), invoices.due_date), 0) AS age')
        )
        ->leftJoin('customers', 'customers.id', '=', 'invoices.customer_id')
        ->where('invoices.created_by', \Auth::user()->creatorId())
        ->where('invoices.issue_date', '<', $end)
        ->where('invoices.status', '!=', 4)
        ->orderBy('customers.name', 'asc')
        ->orderBy('invoices.issue_date', 'asc');
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
                'rowGroup' => [
                    'dataSrc' => 'customer',
                ],
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('issue_date')->title('Date'),
            Column::make('transaction')->title('Transaction'),
            Column::make('type')->title('Type'),
            // Column::make('status_label')->title('Status'), // Removed
            Column::make('due_date')->title('Due Date'),
            // Column::make('past_due')->title('Past Due'), // Will remove
            // Column::make('total_amount')->title('Amount'), // Will remove
            // Column::make('balance_due')->title('Balance Due'), // Will remove
            Column::make('open_balance')->title('Open Balance'), // Added
        ];
    }
}
