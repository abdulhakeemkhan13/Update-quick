<?php

namespace App\DataTables;

use App\Models\Invoice;
use Carbon\Carbon;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\DB;

class InvoicesandReceivedPaymentsDataTable extends DataTable
{
    public function dataTable($query)
    {
        $data = collect($query);

        $finalData = collect();


        $grandTotal = 0;

        $grouped = $data->groupBy('name');

        // 🔹 Merge per customer
        // $customers = $invoices->pluck('name')
        //     ->merge($payments->pluck('name'))
        //     ->unique();


        foreach ($grouped as $customer => $rows) {
            $subtotal = 0;
            $finalData->push((object) [
                // 'transaction' => '<strong>' . $customer . '</strong>',
                'customer' => $customer,
                'transaction' => '<span class="" data-bucket="' . \Str::slug($customer) . '"> <span class="icon">▼</span> <strong>' . $customer . '</strong></span>',
                'issue_date' => '',
                'type' => '',
                'total_amount' => '',
                'memo' => '',
                'isPlaceholder' => true,
                'isParent' => true
            ]);

            foreach ($rows as $row) {
                // 👇 Push payments first (if any)
                if (!empty($row->payments)) {
                    foreach ($row->payments as $pay) {
                        $subtotal += $pay->total_amount;
                        $grandTotal += $pay->total_amount;
                        $row->customer = $customer;
                        $finalData->push($pay);
                    }
                }

                // 👇 Then push the invoice row itself
                $subtotal += $row->total_amount;
                $grandTotal += $row->total_amount;
                $row->customer = $customer;
                $finalData->push($row);
            }

            // Subtotal row
            // $finalData->push((object) [
            //     'customer' => $customer,
            //     'transaction' => '<strong>Subtotal for ' . $customer . '</strong>',
            //     'issue_date' => '',
            //     'type' => '',
            //     'total_amount' => $subtotal,
            //     'memo' => '',
            //     'isSubtotal' => true,
            // ]);

            // Spacer row
            $finalData->push((object) [
                'transaction' => '',
                'issue_date' => '',
                'type' => '',
                'total_amount' => '',
                'memo' => '',
                'isPlaceholder' => true,
            ]);
        }


        // Grand total
        // $finalData->push((object) [
        //     'transaction' => '<strong>Grand Total</strong>',
        //     'issue_date' => '',
        //     'type' => '',
        //     'total_amount' => $grandTotal,
        //     'memo' => '',
        //     'isGrandTotal' => true,
        // ]);

        return datatables()
            ->collection($finalData)
            ->addColumn('transaction', function ($row) {
                if (isset($row->isSubtotal) || isset($row->isGrandTotal) || isset($row->isPlaceholder)) {
                    return $row->transaction ?? '';
                }

                return \Auth::user()->invoiceNumberFormat($row->invoice ?? ($row->id ?? ''));
            })
            ->editColumn('transaction', function ($row) {
                return $row->transaction ?? '';
            })


            ->addColumn('due_date', fn($row) => $row->due_date ?? '')
            // ->addColumn('past_due', fn($row) => $row->past_due ?? '')
            ->editColumn(
                'type',
                fn($row) =>
                (isset($row->isPlaceholder) || isset($row->isSubtotal) || isset($row->isGrandTotal))
                ? ''
                : $row->type
            )
            ->addColumn('issue_date', fn($row) => $row->issue_date ?? '')
            ->editColumn('total_amount', function ($row) {
                if (isset($row->isPlaceholder)) {
                    return '';
                }
                return number_format($row->total_amount ?? 0, 2);
            })

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

        // 🔹 Get Invoices with attached payments
        $invoices = $model->newQuery()
            ->select(
                'invoices.id',
                'invoices.invoice_id as invoice',
                'invoices.issue_date',
                'invoices.due_date',
                'invoices.status',
                'customers.name',
                'invoices.ref_number',
                DB::raw('SUM((invoice_products.price * invoice_products.quantity) - invoice_products.discount) as subtotal'),
                DB::raw('(SELECT IFNULL(SUM((price * quantity - discount) * (taxes.rate / 100)),0) 
              FROM invoice_products 
              LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoice_products.tax) > 0
              WHERE invoice_products.invoice_id = invoices.id) as total_tax')
            )
            ->leftJoin('customers', 'customers.id', '=', 'invoices.customer_id')
            ->leftJoin('invoice_products', 'invoice_products.invoice_id', '=', 'invoices.id')
            ->where('invoices.created_by', \Auth::user()->creatorId())
            ->whereBetween('invoices.issue_date', [$start, $end])
            ->where('invoices.status', '!=', 4)
            ->groupBy('invoices.id')
            ->get()
            ->map(function ($inv) use ($start, $end) {
                $payments = DB::table('invoice_payments')
                    ->select(
                        'invoice_payments.id',
                        'invoice_payments.date',
                        'invoice_payments.amount',
                        'invoice_payments.description'
                    )
                    ->where('invoice_payments.invoice_id', $inv->id)
                    ->whereBetween('invoice_payments.date', [$start, $end])
                    ->get()
                    ->map(function ($pay) use ($inv) {
                        return (object) [
                            'id' => $pay->id,
                            'issue_date' => $pay->date,
                            'transaction' => "Payment #{$pay->id}",
                            'type' => 'Payment',
                            'total_amount' => $pay->amount,
                            'memo' => $pay->description,
                            'customer' => $inv->name
                        ];
                    });

                return (object) [
                    'id' => $inv->id,
                    'name' => $inv->name,
                    'issue_date' => $inv->issue_date,
                    'transaction' => \Auth::user()->invoiceNumberFormat($inv->invoice ?? $inv->id),
                    'type' => 'Invoice',
                    'total_amount' => ($inv->subtotal ?? 0) + ($inv->total_tax ?? 0),
                    'memo' => $inv->ref_number,
                    'payments' => $payments, // 👈 keep nested payments
                ];
            });

        // ✅ Must return a collection
        return $invoices;
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
            Column::make('memo')->title('Memo/Description'),
            Column::make('type')->title('Type'),
            Column::make('total_amount')->title('Amount'),
        ];
    }

}
