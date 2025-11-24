<?php

namespace App\DataTables;

use App\Models\Bill;
use Carbon\Carbon;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\DB;

class VendorBalanceDetail extends DataTable
{
    public function dataTable($query)
    {
        $data = collect($query->get());

        $grandTotalAmount = 0;
        $grandOpenBalance = 0;
        $runningBalance = 0; // 👈 keep track of running balance

        // Group invoices by vendor name
        $groupedData = $data->groupBy(function ($row) {
            return $row->name ?? 'Unknown Vendor';
        });

        $finalData = collect();

        foreach ($groupedData as $vendor => $rows) {
            $subtotalAmount = 0;
            $subtotalOpen = 0;
            $runningBalance = 0; // 👈 reset here for each vendor


            // Vendor header row
            $finalData->push((object) [
                'vendor' => $vendor,
                'id' => null,
                'bill_date' => '',
                'due_date' => '',
                // 'transaction' => '<strong>' . $vendor . '</strong>',
                'transaction' => '<span class="" data-bucket="' . \Str::slug($vendor) . '"> <span class="icon">▼</span> <strong>' . $vendor . '</strong></span>',
                'type' => '',
                'total_amount' => null,
                'open_balance' => null,
                'balance' => null,
                'isPlaceholder' => true,
                'isSubtotal' => false,
                'isParent' => true
            ]);

            foreach ($rows as $row) {
                $subtotalAmount += ($row->subtotal ?? 0) + ($row->total_tax ?? 0);
                $subtotalOpen += $row->open_balance;
                // 👈 running balance logic
                $runningBalance += $row->open_balance;
                $row->balance = $runningBalance;
                $row->vendor = $vendor;
                $finalData->push($row);
            }

            // Vendor subtotal row
            $finalData->push((object) [
                'vendor' => $vendor,
                'id' => null,
                'bill_date' => '',
                'due_date' => '',
                'transaction' => '<strong>Subtotal for ' . $vendor . '</strong>',
                'type' => '',
                'total_amount' => $subtotalAmount,
                'open_balance' => $subtotalOpen,
                'balance' => null,
                'isSubtotal' => true,
            ]);

            $finalData->push((object) [
                'vendor' => $vendor,
                'id' => null,
                'bill_date' => '',
                'due_date' => '',
                'transaction' => '',
                'type' => '',
                'total_amount' => '',
                'open_balance' => '',
                'balance' => '',
                'isPlaceholder' => true,
            ]);

            $grandTotalAmount += $subtotalAmount;
            $grandOpenBalance += $subtotalOpen;
        }

        // Grand total row
        $finalData->push((object) [
            'vendor' => '',
            'id' => null,
            'bill_date' => '',
            'due_date' => '',
            'transaction' => '<strong>Grand Total</strong>',
            'type' => '',
            'total_amount' => $grandTotalAmount,
            'open_balance' => $grandOpenBalance,
            'balance' => null,
            'isGrandTotal' => true,
        ]);

        return datatables()
            ->collection($finalData)
            ->addColumn('bill_date', fn($row) => isset($row->isSubtotal) || isset($row->isGrandTotal) ? '' : $row->bill_date)
            ->addColumn('due_date', fn($row) => isset($row->isSubtotal) || isset($row->isGrandTotal) ? '' : $row->due_date) // 👈 add due date
            ->addColumn('transaction', function ($row) {
                if (isset($row->isSubtotal) || isset($row->isGrandTotal) || (isset($row->isPlaceholder) && $row->isPlaceholder)) {
                    return $row->transaction;
                }
                return \Auth::user()->billNumberFormat($row->bill ?? $row->id);
            })
            ->addColumn('type', function ($row) {
                if (isset($row->isSubtotal) || isset($row->isGrandTotal) || (isset($row->isPlaceholder) && $row->isPlaceholder)) {
                    return '';
                }
                return 'Bill';
            })
            ->editColumn('total_amount', function ($row) {
                if (isset($row->isPlaceholder)) {
                    return '';
                }
                if (isset($row->isSubtotal) || isset($row->isGrandTotal)) {
                    return number_format($row->total_amount ?? 0);
                }
                return number_format(($row->subtotal ?? 0) + ($row->total_tax ?? 0));
            })
            ->editColumn('open_balance', function ($row) {
                if (isset($row->isPlaceholder)) {
                    return '';
                }
                if (isset($row->isSubtotal) || isset($row->isGrandTotal)) {
                    return number_format($row->open_balance ?? 0);
                }
                return number_format($row->open_balance ?? 0);
            })
            ->editColumn('balance', function ($row) { // 👈 show running balance
                if (isset($row->isPlaceholder) || isset($row->isSubtotal) || isset($row->isGrandTotal)) {
                    return '';
                }
                return number_format($row->balance ?? 0);
            })
            ->setRowClass(function ($row) {
                if (property_exists($row, 'isParent') && $row->isParent) {
                    return 'parent-row toggle-bucket bucket-' . \Str::slug($row->vendor ?? 'na');
                }

                if (property_exists($row, 'isSubtotal') && $row->isSubtotal && !property_exists($row, 'isGrandTotal')) {
                    return 'subtotal-row bucket-' . \Str::slug($row->vendor ?? 'na');
                }

                if (
                    !property_exists($row, 'isParent') &&
                    !property_exists($row, 'isSubtotal') &&
                    !property_exists($row, 'isGrandTotal') &&
                    !property_exists($row, 'isPlaceholder')
                ) {
                    return 'child-row bucket-' . \Str::slug($row->vendor ?? 'na');
                }

                if (property_exists($row, 'isGrandTotal') && $row->isGrandTotal) {
                    return 'grandtotal-row';
                }

                return '';
            })
            ->rawColumns(['transaction']);
    }

    public function query(Bill $model)
    {
        $start = request()->get('start_date') ?? request()->get('startDate') ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $end = request()->get('end_date') ?? request()->get('endDate') ?? Carbon::now()->endOfDay()->format('Y-m-d');

        return $model->newQuery()
            ->select(
                'bills.id',
                'bills.bill_id as bill',
                'bills.bill_date',
                'bills.due_date', // 👈 add due date to query
                'bills.status',
                'venders.name',
                DB::raw('SUM((bill_products.price * bill_products.quantity) - bill_products.discount) as subtotal'),
                DB::raw('IFNULL(SUM(bill_payments.amount), 0) as pay_price'),
                DB::raw('(SELECT IFNULL(SUM((price * quantity - discount) * (taxes.rate / 100)),0) 
                    FROM bill_products 
                    LEFT JOIN taxes ON FIND_IN_SET(taxes.id, bill_products.tax) > 0
                    WHERE bill_products.bill_id = bills.id) as total_tax'),
                DB::raw('(SELECT IFNULL(SUM(debit_notes.amount),0) 
                    FROM debit_notes 
                    WHERE debit_notes.bill = bills.id) as debit_price'),
                DB::raw('(SUM((bill_products.price * bill_products.quantity) - bill_products.discount) 
                    + (SELECT IFNULL(SUM((price * quantity - discount) * (taxes.rate / 100)),0) 
                        FROM bill_products 
                        LEFT JOIN taxes ON FIND_IN_SET(taxes.id, bill_products.tax) > 0
                        WHERE bill_products.bill_id = bills.id)
                    - (IFNULL(SUM(bill_payments.amount),0) 
                    + (SELECT IFNULL(SUM(debit_notes.amount),0) FROM debit_notes WHERE debit_notes.bill = bills.id))
                ) as open_balance')
            )
            ->leftJoin('venders', 'venders.id', '=', 'bills.vender_id')
            ->leftJoin('bill_products', 'bill_products.bill_id', '=', 'bills.id')
            ->leftJoin('bill_payments', 'bill_payments.bill_id', '=', 'bills.id')
            ->where('bills.created_by', \Auth::user()->creatorId())
            ->whereBetween('bills.bill_date', [$start, $end])
            ->groupBy('bills.id');
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
                'footerCallback' => <<<JS
function (row, data, start, end, display) {
    var api = this.api();
    var parseVal = function (i) {
        return typeof i === 'string'
            ? parseFloat(i.replace(/[^0-9.-]+/g, '')) || 0
            : typeof i === 'number'
                ? i
                : 0;
    };

    var totalAmount = api.column(4, { page: 'all' }).data()
        .reduce((a, b) => parseVal(a) + parseVal(b), 0);

    var totalOpen = api.column(5, { page: 'all' }).data()
        .reduce((a, b) => parseVal(a) + parseVal(b), 0);

    $(api.column(4).footer()).html(totalAmount.toLocaleString());
    $(api.column(5).footer()).html(totalOpen.toLocaleString());
}
JS
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('bill_date')->title('Date'),
            Column::make('transaction')->title('Transaction'),
            Column::make('type')->title('Type'),
            Column::make('due_date')->title('Due Date'), // 👈 add due date column
            Column::make('total_amount')->title('Amount'),
            Column::make('open_balance')->title('Open Balance'),
            Column::make('balance')->title('Balance'), // 👈 new running balance column
        ];
    }
}
