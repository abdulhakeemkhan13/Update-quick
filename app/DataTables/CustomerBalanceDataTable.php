<?php

namespace App\DataTables;

use App\Models\Invoice;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Column;
use Carbon\Carbon;


class CustomerBalanceDataTable extends DataTable
{
    public function dataTable($query)
    {
        $entries = $query->get()->toArray();
        $mergedArray = [];
        $totalBalance = 0;

        foreach ($entries as $item) {
            $name = $item['name'];

            if (!isset($mergedArray[$name])) {
                $mergedArray[$name] = [
                    'name' => $name,
                    'price' => 0,
                    'pay_price' => 0,
                    'total_tax' => 0,
                    'credit_price' => 0,
                ];
            }

            $mergedArray[$name]['price'] += floatval($item['price']);
            $mergedArray[$name]['pay_price'] += floatval($item['pay_price']);
            $mergedArray[$name]['total_tax'] += floatval($item['total_tax']);
            $mergedArray[$name]['credit_price'] += floatval($item['credit_price']);
        }

        $data = collect();
        foreach ($mergedArray as $row) {
            $customerBalance = $row['price'] + $row['total_tax'] - $row['pay_price'];
            $balance = $customerBalance - $row['credit_price'];
            $totalBalance += $balance;

            $data->push([
                'name' => $row['name'],
                'price' => number_format($customerBalance, 2),
                'credit_price' => number_format($row['credit_price'], 2),
                'balance' => number_format($balance, 2),
            ]);
        }

        // Add total row
        $data->push([
            'name' => '<strong>Total</strong>',
            'price' => '',
            'credit_price' => '',
            'balance' => '<strong>' . number_format($totalBalance, 2) . '</strong>',
            'DT_RowClass' => 'summary-total'
        ]);

        return datatables()->collection($data)->rawColumns(['name', 'balance']);
    }


    public function query(Invoice $model)
    {
        $start = request()->get('startDate') ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $end = request()->get('endDate') ?? Carbon::now()->endOfDay()->format('Y-m-d');

        return $model->newQuery()
            ->select('customers.name')
            ->selectRaw('invoices.id as invoice_id')
            ->selectRaw('SUM((invoice_products.price * invoice_products.quantity) - invoice_products.discount) as price')
            ->selectRaw('SUM(invoice_payments.amount) as pay_price')
            ->selectRaw('(SELECT SUM((price * quantity - discount) * (taxes.rate / 100))
                      FROM invoice_products
                      LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoice_products.tax) > 0
                      WHERE invoice_products.invoice_id = invoices.id) as total_tax')
            ->selectRaw('(SELECT SUM(credit_notes.amount) 
                      FROM credit_notes
                      WHERE credit_notes.invoice = invoices.id) as credit_price')
            ->leftJoin('customers', 'customers.id', '=', 'invoices.customer_id')
            ->leftJoin('invoice_payments', 'invoice_payments.invoice_id', '=', 'invoices.id')
            ->leftJoin('invoice_products', 'invoice_products.invoice_id', '=', 'invoices.id')
            ->where('invoices.created_by', \Auth::user()->creatorId())
            ->whereBetween('invoices.issue_date', [$start, $end])
            ->groupBy('invoices.id'); // group per invoice, merge later
    }



    public function html()
    {
        return $this->builder()
            ->setTableId('customer-balance-table')
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
                    $('td:eq(1), td:eq(2), td:eq(3)', row).addClass('text-left');
                    if ($(row).hasClass('summary-total')) {
                        $(row).addClass('font-weight-bold bg-light');
                    }
                }"
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('name')->title('Customer Name'),
            Column::make('price')->title('Invoice Balance'),
            Column::make('credit_price')->title('Available Credits'),
            Column::make('balance')->title('Balance'),
        ];
    }
}
