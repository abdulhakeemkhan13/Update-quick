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
        $grandOpenBalance = 0; // was $grandBalanceDue


        // ✅ Group by Customer Name
        $groupedData = $data->groupBy('name');

        $finalData = collect();

        foreach ($groupedData as $customer => $rows) {
            $subtotalAmount = 0;
            $subtotalOpen = 0; // was $subtotalDue

            foreach ($rows as $row) {
                $subtotalAmount += ($row->subtotal ?? 0) + ($row->total_tax ?? 0);
                $subtotalOpen += ($row->open_balance ?? 0); // accumulate open_balance
                $row->customer = $customer;
                $row->past_due = $row->age > 0 ? $row->age . ' Days' : '-';
                $finalData->push($row);
            }

            $grandTotalAmount += $subtotalAmount;
            $grandOpenBalance += $subtotalOpen;
        }

        // ✅ Add grand total row
        $finalData->push((object) [
            // 'customer' => '<strong>Grand Total</strong>',
            'transaction' => '<strong>Grand Total</strong>',
            'due_date' => '',
            'customer' => '',
            'past_due' => '',
            'type' => '',
            'status_label' => '',
            'age' => '',
            'total_amount' => $grandTotalAmount,
            'open_balance' => $grandOpenBalance,
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
            ->addColumn('past_due', fn($row) => $row->past_due ?? '')
            ->addColumn(
                'type',
                fn($row) =>
                isset($row->isSubtotal) || isset($row->isGrandTotal) ? '' : 'Invoice'
            )
            ->addColumn('status_label', function ($row) {
                if (isset($row->isSubtotal) || isset($row->isGrandTotal)) {
                    return '';
                }
                $status = $row->status ?? 0;
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
            ->addColumn('issue_date', fn($row) => $row->issue_date ?? '')
            ->editColumn('total_amount', function ($row) {
                if (isset($row->isPlaceholder)) {
                    return '';
                }
                if (isset($row->isSubtotal) || isset($row->isGrandTotal)) {
                    return number_format($row->total_amount ?? 0);
                }
                $total = ($row->subtotal ?? 0) + ($row->total_tax ?? 0);
                return number_format($total);
            })
            ->editColumn(
                'open_balance',
                fn($row) =>
                isset($row->isPlaceholder) ? '' : number_format($row->open_balance ?? 0)
            )

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
                DB::raw('SUM((invoice_products.price * invoice_products.quantity) - invoice_products.discount) as subtotal'),
                DB::raw('IFNULL(SUM(invoice_payments.amount), 0) as pay_price'),
                DB::raw('(SELECT IFNULL(SUM((price * quantity - discount) * (taxes.rate / 100)),0) 
                  FROM invoice_products 
                  LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoice_products.tax) > 0
                  WHERE invoice_products.invoice_id = invoices.id) as total_tax'),
                DB::raw('(SELECT IFNULL(SUM(credit_notes.amount),0) 
                  FROM credit_notes 
                  WHERE credit_notes.invoice = invoices.id) as credit_price'),
                DB::raw('(
    (SUM((invoice_products.price * invoice_products.quantity) - invoice_products.discount))
    + (SELECT IFNULL(SUM((price * quantity - discount) * (taxes.rate / 100)),0) 
       FROM invoice_products 
       LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoice_products.tax) > 0
       WHERE invoice_products.invoice_id = invoices.id)
    - (IFNULL(SUM(invoice_payments.amount),0)
    + (SELECT IFNULL(SUM(credit_notes.amount),0) FROM credit_notes WHERE credit_notes.invoice = invoices.id))
 ) as open_balance'),

                DB::raw('GREATEST(DATEDIFF(CURDATE(), invoices.due_date), 0) as age')
            )
            ->leftJoin('customers', 'customers.id', '=', 'invoices.customer_id')
            ->leftJoin('invoice_products', 'invoice_products.invoice_id', '=', 'invoices.id')
            ->leftJoin('invoice_payments', 'invoice_payments.invoice_id', '=', 'invoices.id')
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
                // 'rowGroup' => [
                //     'dataSrc' => 'customer',
                // ],
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('issue_date')->title('Date'),   // 👈 added
            Column::make('transaction')->title('Transaction'),
            Column::make('type')->title('Type'),
            Column::make('customer')->title('Customer Name'),
            Column::make('status_label')->title('Status'),
            Column::make('due_date')->title('Due Date'), // 👈 moved here
            // Column::make('past_due')->title('Past Due'),
            Column::make('total_amount')->title('Amount'),
            Column::make('open_balance')->title('Open Balance'),
            // Column::make('balance_due')->title('Balance Due'),

        ];
    }
}
