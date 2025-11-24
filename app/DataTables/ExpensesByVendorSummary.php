<?php

namespace App\DataTables;

use App\Models\BillProduct;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use App\Models\PurchaseProduct;

class ExpensesByVendorSummary extends DataTable
{
    public function dataTable($query)
    {
        $data = collect($query->get());

        $finalData = collect();
        $grandTotal = 0;

        // Group by vendor
        $vendors = $data->groupBy('vendor_name');

        foreach ($vendors as $vendor => $rows) {
            $vendorTotal = 0;

            foreach ($rows as $row) {
                $amount = ($row->price * $row->quantity) - ($row->discount ?? 0) + ($row->tax_amount ?? 0);
                $vendorTotal += $amount;
            }

            $finalData->push((object) [
                'vendor_name' => $vendor,
                'total' => $vendorTotal,
                'isDetail' => true,
            ]);

            $grandTotal += $vendorTotal;
        }

        // Add grand total row
        $finalData->push((object) [
            'vendor_name' => "<strong>Grand Total</strong>",
            'total' => $grandTotal,
            'isGrandTotal' => true,
        ]);

        return datatables()
            ->collection($finalData)
            ->editColumn('total', fn($row) => number_format((float) $row->total, 2))
            ->setRowClass(function ($row) {
                if (isset($row->isGrandTotal)) {
                    return 'grandtotal-row';
                }
                return 'detail-row';
            })
            ->rawColumns(['vendor_name']);
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
            ->orderBy(0, 'asc')
            ->parameters([
                'paging' => false,
                'searching' => false,
                'info' => false,
                'ordering' => false,
                'responsive' => true,
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('vendor_name')->title('Vendor'),
            Column::make('total')->title('Total'),
        ];
    }
}
