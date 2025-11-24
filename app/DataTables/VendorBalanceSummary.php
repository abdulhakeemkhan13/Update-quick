<?php

namespace App\DataTables;

use App\Models\Bill;
use Carbon\Carbon;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Column;

class VendorBalanceSummary extends DataTable
{
    public function dataTable($query)
    {
        $entries = $query->get()->toArray();
        $mergedArray = [];
        $totalBalance = 0;
        $grandTotal = 0;

        foreach ($entries as $item) {
            $name = $item['name'];

            if (!isset($mergedArray[$name])) {
                $mergedArray[$name] = [
                    'name' => $name,
                    'price' => 0,
                    'pay_price' => 0,
                    'total_tax' => 0,
                    'debit_price' => 0,
                ];
            }

            $mergedArray[$name]['price'] += floatval($item['price']);
            $mergedArray[$name]['pay_price'] += floatval($item['pay_price']);
            $mergedArray[$name]['total_tax'] += floatval($item['total_tax']);
            $mergedArray[$name]['debit_price'] += floatval($item['debit_price']);
        }

        $data = collect();
        foreach ($mergedArray as $row) {
            $vendorTotal = $row['price'] + $row['total_tax']; // total gross
            $vendorBalance = $vendorTotal - $row['pay_price']; // bill balance
            $balance = $vendorBalance - $row['debit_price'];

            $totalBalance += $balance;
            $grandTotal += $balance;

            $data->push([
                'name' => $row['name'],
                'price' => number_format($vendorBalance, 2),
                'debit_price' => number_format($row['debit_price'], 2),
                'balance' => number_format($balance, 2),
                'total' => number_format($balance, 2),
            ]);
        }

        // Add total row
        $data->push([
            'name' => '<strong>Total</strong>',
            'price' => '',
            'debit_price' => '',
            'balance' => '<strong>' . number_format($totalBalance, 2) . '</strong>',
            'total' => '<strong>' . number_format($grandTotal, 2) . '</strong>',
            'DT_RowClass' => 'summary-total'
        ]);

        return datatables()->collection($data)->rawColumns(['name', 'balance', 'total']);
    }

    public function query(Bill $model)
    {
        $start = request()->get('startDate') ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $end = request()->get('endDate') ?? Carbon::now()->endOfDay()->format('Y-m-d');

        return $model->newQuery()
            ->select('venders.name')
            ->selectRaw('bills.id as bill_id')
            ->selectRaw('SUM((bill_products.price * bill_products.quantity) - bill_products.discount) as price')
            ->selectRaw('SUM(bill_payments.amount) as pay_price')
            ->selectRaw('(SELECT SUM((price * quantity - discount) * (taxes.rate / 100))
                          FROM bill_products
                          LEFT JOIN taxes ON FIND_IN_SET(taxes.id, bill_products.tax) > 0
                          WHERE bill_products.bill_id = bills.id) as total_tax')
            ->selectRaw('(SELECT SUM(debit_notes.amount) 
                          FROM debit_notes
                          WHERE debit_notes.bill = bills.id) as debit_price')
            ->leftJoin('venders', 'venders.id', '=', 'bills.vender_id')
            ->leftJoin('bill_payments', 'bill_payments.bill_id', '=', 'bills.id')
            ->leftJoin('bill_products', 'bill_products.bill_id', '=', 'bills.id')
            ->where('bills.created_by', \Auth::user()->creatorId())
            ->whereNotIn('bills.user_type', ['employee', 'customer'])
            ->whereBetween('bills.bill_date', [$start, $end])
            ->groupBy('bills.id');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('customer-balance-table') // ✅ same table id
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0, 'desc')
            ->parameters([
                'paging' => false,
                'searching' => false,
                'info' => false,
                'ordering' => false,
                'scrollY' => '500px',
                'scrollCollapse' => true,
                'colReorder' => true,
                'createdRow' => "function(row, data) {
                    $('td:eq(1), td:eq(2), td:eq(3), td:eq(4)', row).addClass('text-left');
                    if ($(row).hasClass('summary-total')) {
                        $(row).addClass('font-weight-bold bg-light');
                    }
                }"
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('name')->title('Vendor Name'),
            Column::make('price')->title('Billed Amount'),
            Column::make('debit_price')->title('Available Debit'),
            Column::make('balance')->title('Closing Balance'),
            Column::make('total')->title('Total'),
        ];
    }
}
