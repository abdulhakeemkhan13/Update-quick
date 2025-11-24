<?php

namespace App\DataTables;

use App\Models\BillProduct;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use App\Models\PurchaseProduct;

class PurchasesByVendorDetail extends DataTable
{
    public function dataTable($query)
    {
        $data = collect($query->get());
        $finalData = collect();
        $grandTotal = 0;

        $vendors = $data->groupBy('vendor_name');

        foreach ($vendors as $vendor => $rows) {
            $vendorSubtotal = 0;

            $finalData->push((object) [
                'transaction_date' => '',
                'transaction_type' => '<span class="" data-bucket="' . \Str::slug($vendor) . '"><span class="icon">▼</span> <strong>' . $vendor . '</strong></span>',
                'transaction' => '',
                'product_service' => '',
                'memo' => '',
                'quantity' => 0,
                'rate' => 0,
                'amount' => 0,
                'balance' => 0,
                'vendor_name' => $vendor,
                'isVendorHeader' => true,
                'isParent' => true,
            ]);

            foreach ($rows as $row) {
                $amount = ($row->price * $row->quantity) - ($row->discount ?? 0) + ($row->tax_amount ?? 0);
                $vendorSubtotal += $amount;

                $finalData->push((object) [
                    'transaction_date' => $row->transaction_date,
                    'transaction_type' => 'Bill',
                    'transaction' => \Auth::user()->billNumberFormat($row->bill),
                    'product_service' => $row->product_service_name ?? '',
                    'memo' => $row->description ?? '',
                    'quantity' => $row->quantity,
                    'rate' => $row->price,
                    'amount' => $amount,
                    'balance' => $amount,
                    'vendor_name' => $vendor,
                    'isDetail' => true,
                ]);
            }

            $finalData->push((object) [
                'transaction_date' => '',
                'transaction_type' => "<strong>Subtotal for {$vendor}</strong>",
                'transaction' => '',
                'product_service' => '',
                'memo' => '',
                'quantity' => 0,
                'rate' => 0,
                'amount' => $vendorSubtotal,
                'balance' => $vendorSubtotal,
                'vendor_name' => $vendor,
                'isSubtotal' => true,
            ]);

            $finalData->push((object) [
                'transaction_date' => '',
                'transaction_type' => '',
                'transaction' => '',
                'product_service' => '',
                'memo' => '',
                'quantity' => 0,
                'rate' => 0,
                'amount' => 0,
                'balance' => 0,
                'vendor_name' => $vendor,
                'isPlaceholder' => true,
            ]);

            $grandTotal += $vendorSubtotal;
        }

        $finalData->push((object) [
            'transaction_date' => '',
            'transaction_type' => '<strong>Grand Total</strong>',
            'transaction' => '',
            'product_service' => '',
            'memo' => '',
            'quantity' => 0,
            'rate' => 0,
            'amount' => $grandTotal,
            'balance' => $grandTotal,
            'vendor_name' => '',
            'isGrandTotal' => true,
        ]);

        return datatables()
            ->collection($finalData)
            ->editColumn('transaction_date', fn($row) => isset($row->isDetail) ? $row->transaction_date : '')
            ->editColumn('transaction', fn($row) => $row->transaction ?? '')
            ->editColumn('memo', fn($row) => isset($row->isDetail) ? $row->memo : '')
            ->editColumn('amount', function ($row) {
                if ((isset($row->isVendorHeader) && $row->isVendorHeader) || (isset($row->isPlaceholder) && $row->isPlaceholder)) {
                    return '';
                }
                return number_format((float) $row->amount, 2);
            })
            ->editColumn('quantity', function ($row) {
                if (isset($row->isVendorHeader) || isset($row->isSubtotal) || isset($row->isPlaceholder) || isset($row->isGrandTotal)) {
                    return '';
                }
                return $row->quantity;
            })
            ->editColumn('rate', function ($row) {
                if (isset($row->isVendorHeader) || isset($row->isSubtotal) || isset($row->isPlaceholder) || isset($row->isGrandTotal)) {
                    return '';
                }
                return number_format((float) $row->rate, 2);
            })
            ->editColumn('balance', function ($row) {
                if (isset($row->isVendorHeader) || isset($row->isPlaceholder)) {
                    return '';
                }
                return number_format((float) $row->balance, 2);
            })

            ->setRowClass(function ($row) {
                $vendorSlug = $row->vendor_name ? \Str::slug($row->vendor_name) : 'no-vendor';
                if (isset($row->isVendorHeader) && $row->isVendorHeader)
                    return 'parent-row toggle-bucket bucket-' . $vendorSlug;
                if (isset($row->isSubtotal) && !isset($row->isGrandTotal))
                    return 'subtotal-row bucket-' . $vendorSlug;
                if (!isset($row->isVendorHeader) && !isset($row->isSubtotal) && !isset($row->isGrandTotal) && !isset($row->isPlaceholder))
                    return 'child-row bucket-' . $vendorSlug;
                if (isset($row->isGrandTotal))
                    return 'grandtotal-row';
                return '';
            })
            ->rawColumns(['transaction', 'transaction_type']);
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
                'purchases.purchase_id as purchase',
                'purchases.purchase_date as transaction_date',
                'venders.name as vendor_name',
                'product_services.name as product_service_name',
                DB::raw('(SELECT IFNULL(SUM((pp.price * pp.quantity - pp.discount) * (taxes.rate / 100)),0)
                FROM purchase_products pp
                LEFT JOIN taxes ON FIND_IN_SET(taxes.id, pp.tax) > 0
                WHERE pp.id = purchase_products.id) as tax_amount')
            )
            ->join('purchases', 'purchases.id', '=', 'purchase_products.purchase_id')
            ->join('venders', 'venders.id', '=', 'purchases.vender_id')
            ->join('product_services', 'product_services.id', '=', 'purchase_products.product_id')
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
            Column::make('transaction_date')->title('Date'),
            Column::make('transaction_type')->title('Type'),
            Column::make('transaction')->title('Num'),
            Column::make('product_service')->title('Product/Service Full Name'),
            Column::make('memo')->title('Memo/Description'),
            Column::make('quantity')->title('Quantity'),
            Column::make('rate')->title('Rate'),
            Column::make('amount')->title('Amount'),
            Column::make('balance')->title('Balance'),
        ];
    }
}
