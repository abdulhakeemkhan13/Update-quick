<?php

namespace App\DataTables;

use App\Models\Invoice;
use Carbon\Carbon;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\DB;


class AgingDetailsDataTable extends DataTable
{
    public function dataTable($query)
    {
        $end = request()->get('end_date')
            ? Carbon::parse(request()->get('end_date'))->endOfDay()
            : (request()->get('endDate')
                ? Carbon::parse(request()->get('endDate'))->endOfDay()
                : Carbon::today());

        $start = request()->get('start_date')
            ? Carbon::parse(request()->get('start_date'))->startOfDay()
            : (request()->get('startDate')
                ? Carbon::parse(request()->get('startDate'))->startOfDay()
                : Carbon::now()->startOfYear());

        $data = collect($query->get());

        $grandTotalAmount = 0;
        $grandBalanceDue = 0;
        $grandOpenBalance = 0;


        // Group invoices into buckets
        $groupedData = $data->groupBy(function ($row) use ($end) {
            $dueDate = $row->due_date ?? $row->issue_date;
            if (!$dueDate)
                return 'Current';

            try {
                $due = Carbon::parse($dueDate);
            } catch (\Exception $e) {
                return 'Current';
            }

            $age = $due->diffInDays($end, false); // 👈 use $end instead of today

            if ($age <= 0)
                return 'Current';
            if ($age <= 15)
                return '1–15 Days';
            if ($age <= 30)
                return '16–30 Days';
            if ($age <= 45)
                return '31–45 Days';
            return '> 45 Days';
        });


        $finalData = collect();

        foreach ($groupedData as $bucket => $rows) {
            $subtotalAmount = 0;
            $subtotalDue = 0;
            $subtotalOpen = 0;

            // Add subtotal row
            $finalData->push((object) [
                'bucket' => $bucket,
                'id' => null,
                'due_date' => '',
                // 'transaction' => '<strong>Subtotal for ' . $bucket . '</strong>',
                'transaction' => '<span class="" data-bucket="' . \Str::slug($bucket) . '"> <span class="icon">▼</span> <strong>' . $bucket . '</strong></span>',
                'type' => '',
                'status_label' => '',
                'customer' => '',
                'age' => '',
                'total_amount' => null,
                'balance_due' => null,
                'isPlaceholder' => true,
                'isSubtotal' => false,
                'isParent' => true
            ]);

            foreach ($rows as $row) {
                $subtotalAmount += ($row->subtotal ?? 0) + ($row->total_tax ?? 0);
                $subtotalDue += $row->balance_due;
                $row->bucket = $bucket; // keep bucket info in each row
                $subtotalOpen += $row->open_balance;
                $finalData->push($row);
            }

            // Add subtotal row
            $finalData->push((object) [
                'bucket' => $bucket,
                'id' => null,
                'due_date' => '',
                // 'transaction' => '<strong>Subtotal for ' . $bucket . '</strong>',
                'transaction' => '<strong>Subtotal </strong>',
                'type' => '',
                'status_label' => '',
                'customer' => '',
                'age' => '',
                'total_amount' => $subtotalAmount,
                'balance_due' => $subtotalDue,
                'open_balance' => $subtotalOpen,
                'isSubtotal' => true,
            ]);

            $finalData->push((object) [
                'bucket' => $bucket,
                'id' => null,
                'due_date' => '',
                'transaction' => '',
                'type' => '',
                'status_label' => '',
                'customer' => '',
                'age' => '',
                'total_amount' => 0,
                'balance_due' => 0,
                'open_balance' => 0,
                'isPlaceholder' => true,
                "isSubtotal" => true,
            ]);

            $grandTotalAmount += $subtotalAmount;
            $grandBalanceDue += $subtotalDue;
            $grandOpenBalance += $subtotalOpen;
        }

        // Add grand total row
        $finalData->push((object) [
            'bucket' => '',
            'id' => null,
            'due_date' => '',
            'transaction' => '<strong>Grand Total</strong>',
            'type' => '',
            'status_label' => '',
            'customer' => '',
            'age' => '',
            'total_amount' => $grandTotalAmount,
            'balance_due' => $grandBalanceDue,
            'open_balance' => $grandOpenBalance,
            'isGrandTotal' => true,
        ]);

        return datatables()
            ->collection($finalData)

            ->addColumn('bucket', fn($row) => $row->bucket ?? '')
            ->addColumn(
                'transaction',
                fn($row) =>
                isset($row->isSubtotal) || isset($row->isGrandTotal)
                ? $row->transaction

                : \Auth::user()->invoiceNumberFormat($row->invoice ?? $row->id)
            )
            ->addColumn('type', fn($row) => isset($row->isSubtotal) || isset($row->isGrandTotal) ? '' : 'Invoice')
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
                return '<span class="status_badger badger text-whit ' . ($classes[$status] ?? 'bg-secondary') . ' p-2 px-3 rounded">'
                    . __($labels[$status] ?? '-') . '</span>';
            })
            ->addColumn(
                'customer',
                fn($row) =>
                isset($row->isSubtotal) || isset($row->isGrandTotal) ? '' : ($row->name ?? '-')
            )
            ->addColumn(
                'age',
                fn($row) =>
                isset($row->isSubtotal) || isset($row->isGrandTotal)
                ? ''
                : ($row->age > 0 ? $row->age . ' Days' : '-')
            )
            ->addColumn('issue_date', fn($row) => $row->issue_date ?? '')
            ->editColumn('total_amount', function ($row) {
                if (isset($row->isHeader) || isset($row->isPlaceholder)) {
                    return '';
                }

                // Use pre-calculated value for subtotal & grand total rows
                if (isset($row->isSubtotal) || isset($row->isGrandTotal)) {
                    return number_format($row->total_amount ?? 0);
                }

                // Normal invoice row
                $total = ($row->subtotal ?? 0) + ($row->total_tax ?? 0);
                return number_format($total);
            })
            ->editColumn(
                'balance_due',
                fn($row) =>
                isset($row->isHeader) || isset($row->isPlaceholder)
                ? ''
                : number_format($row->balance_due ?? 0)
            )
            ->editColumn(
                'open_balance',
                fn($row) =>
                isset($row->isHeader) || isset($row->isPlaceholder)
                ? ''
                : number_format($row->open_balance ?? 0)
            )
            ->setRowClass(function ($row) {
                if (property_exists($row, 'isParent') && $row->isParent) {
                    return 'parent-row toggle-bucket bucket-' . \Str::slug($row->bucket ?? 'na');
                }

                if (property_exists($row, 'isSubtotal') && $row->isSubtotal && !property_exists($row, 'isGrandTotal')) {
                    return 'subtotal-row bucket-' . \Str::slug($row->bucket ?? 'na');
                }

                if (
                    !property_exists($row, 'isParent') &&
                    !property_exists($row, 'isSubtotal') &&
                    !property_exists($row, 'isGrandTotal') &&
                    !property_exists($row, 'isPlaceholder')
                ) {
                    return 'child-row bucket-' . \Str::slug($row->bucket ?? 'na');
                }

                if (property_exists($row, 'isGrandTotal') && $row->isGrandTotal) {
                    return 'grandtotal-row';
                }

                return '';
            })


            ->rawColumns(['transaction', 'status_label']);
    }


    public function query(Invoice $model)
    {
        // Accept both formats without breaking anything
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
                 ) as balance_due'),
                DB::raw('(
                    (SUM((invoice_products.price * invoice_products.quantity) - invoice_products.discount))
                    + (SELECT IFNULL(SUM((price * quantity - discount) * (taxes.rate / 100)),0) 
                       FROM invoice_products 
                       LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoice_products.tax) > 0
                       WHERE invoice_products.invoice_id = invoices.id)
                    - (IFNULL(SUM(invoice_payments.amount),0)
                       + (SELECT IFNULL(SUM(credit_notes.amount),0) 
                          FROM credit_notes 
                          WHERE credit_notes.invoice = invoices.id))
                ) as open_balance'),


                DB::raw('GREATEST(DATEDIFF(CURDATE(), invoices.due_date), 0) as age')
            )
            ->leftJoin('customers', 'customers.id', '=', 'invoices.customer_id')
            ->leftJoin('invoice_products', 'invoice_products.invoice_id', '=', 'invoices.id')
            ->leftJoin('invoice_payments', 'invoice_payments.invoice_id', '=', 'invoices.id')
            ->where('invoices.created_by', \Auth::user()->creatorId())
            ->whereBetween('invoices.issue_date', [$start, $end]) // ✅ keep existing behavior
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
                'rowGroup' => [
                    'dataSrc' => 'bucket',
                ],
                'footerCallback' => <<<JS
function (row, data, start, end, display) {
    var api = this.api();

    // Helper function to parse number
    var parseVal = function (i) {
        return typeof i === 'string'
            ? parseFloat(i.replace(/[^0-9.-]+/g, '')) || 0
            : typeof i === 'number'
                ? i
                : 0;
    };

    // Total over all pages
var totalAmount = api.column(6, { page: 'all'}).data()
    .reduce((a, b) => parseVal(a) + parseVal(b), 0);

var totalDue = api.column(7, { page: 'all'}).data()
    .reduce((a, b) => parseVal(a) + parseVal(b), 0);

// Update footer
$(api.column(6).footer()).html(totalAmount.toLocaleString());
$(api.column(7).footer()).html(totalDue.toLocaleString());

}
JS
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('bucket')->title('Bucket')->visible(false),
            Column::make('issue_date')->title('Date'),   // 👈 added
            Column::make('transaction')->title('Transaction'),
            Column::make('type')->title('Type'),
            Column::make('status_label')->title('Status'),
            Column::make('customer')->title('Customer Name'),
            // Column::make('age')->title('Age'),
            Column::make('due_date')->title('Due Date'),
            Column::make('total_amount')->title('Amount'),
            // Column::make('balance_due')->title('Balance Due'),
            Column::make('open_balance')->title('Open Balance'),

        ];
    }
}
