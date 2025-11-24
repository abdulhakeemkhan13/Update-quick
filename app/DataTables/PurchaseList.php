<?php

namespace App\DataTables;

use App\Models\BillProduct;
use Carbon\Carbon;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\DB;
use App\Models\PurchaseProduct;


class PurchaseList extends DataTable
{
    public function dataTable($query)
    {
        $data = collect($query->get());

        $grandTotal = [
            'amount' => 0,
            'tax_amount' => 0,
        ];

        foreach ($data as $row) {
            $row->transaction = \Auth::user()->billNumberFormat($row->bill ?? $row->bill_id);
            $row->transaction_date = $row->transaction_date
                ? Carbon::parse($row->transaction_date)->format('Y-m-d')
                : '';
            $row->memo = $row->description ?? '';

            $row->amount = ($row->price * $row->quantity) - ($row->discount ?? 0) + ($row->tax_amount ?? 0);
            $row->tax_amount = $row->tax_amount ?? 0;

            $grandTotal['amount'] += $row->amount;
            $grandTotal['tax_amount'] += $row->tax_amount;
        }

        // Add Grand Total row
        $data->push((object) [
            'transaction_id' => '',
            'vendor_name' => '<strong>Grand Total</strong>',
            'transaction_date' => '',
            'transaction' => '',
            'memo' => '',
            'amount' => $grandTotal['amount'],
            'tax_amount' => $grandTotal['tax_amount'],
            'isGrandTotal' => true,
        ]);

        return datatables()
            ->collection($data)
            ->setRowClass(function ($row) {
                if (isset($row->isGrandTotal)) {
                    return 'grandtotal-row';
                }
                return 'detail-row';
            })
            ->rawColumns(['transaction','vendor_name']);
    }

    public function query(PurchaseProduct $model)
    {
        $start = request()->get('start_date')
            ?? request()->get('startDate')
            ?? Carbon::now()->startOfYear()->format('Y-m-d');

        $end = request()->get('end_date')
            ?? request()->get('endDate')
            ?? Carbon::now()->endOfDay()->format('Y-m-d');

        return $model->newQuery()
            ->select(
                'purchase_products.*',
                'purchases.id as transaction_id',
                'purchases.purchase_id as purchase',
                'purchases.purchase_date as transaction_date',
                'venders.name as vendor_name',
                DB::raw('(SELECT IFNULL(SUM((pp.price * pp.quantity - pp.discount) * (taxes.rate / 100)),0)
                FROM purchase_products pp
                LEFT JOIN taxes ON FIND_IN_SET(taxes.id, pp.tax) > 0
                WHERE pp.id = purchase_products.id) as tax_amount')
            )
            ->join('purchases', 'purchases.id', '=', 'purchase_products.purchase_id')
            ->join('venders', 'venders.id', '=', 'purchases.vender_id')
            ->where('purchases.created_by', \Auth::user()->creatorId())
            ->whereBetween('purchases.purchase_date', [$start, $end]);
    }


    public function html()
    {
        return $this->builder()
            ->setTableId('customer-balance-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(2, 'asc')
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
            Column::make('transaction_id')->title('Transaction ID'),
            Column::make('vendor_name')->title('Name'),
            Column::make('transaction_date')->title('Transaction Date'),
            Column::make('transaction')->title('Transaction'),
            Column::make('memo')->title('Memo / Description'),
            Column::make('amount')->title('Amount'),
            Column::make('tax_amount')->title('Tax Amount'),
        ];
    }
}