<?php

namespace App\DataTables;

use App\Models\Purchase;
use App\Models\ProductService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;

class PurchasesByProductServiceDetailDataTable extends DataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('transaction_date', function ($purchase) {
                return $purchase->purchase_date ? Carbon::parse($purchase->purchase_date)->format('m/d/Y') : '-';
            })
            ->addColumn('transaction_type', function ($purchase) {
                return 'Bill';
            })
            ->addColumn('num', function ($purchase) {
                return \Auth::user()->billNumberFormat($purchase->purchase_id);
            })
            ->addColumn('vendor', function ($purchase) {
                return $purchase->vender ? $purchase->vender->name : '-';
            })
            ->addColumn('memo_description', function ($purchase) {
                return $purchase->description ?? ($purchase->product ? $purchase->product->name : '-');
            })
            ->addColumn('quantity', function ($purchase) {
                return $purchase->quantity ?? 0;
            })
            ->addColumn('rate', function ($purchase) {
                return \Auth::user()->priceFormat($purchase->price ?? 0);
            })
            ->addColumn('amount', function ($purchase) {
                $quantity = $purchase->quantity ?? 0;
                $price = $purchase->price ?? 0;
                $amount = $quantity * $price;
                return \Auth::user()->priceFormat($amount);
            })
            ->addColumn('balance', function ($purchase) {
                // For purchases, balance would typically be the same as amount
                $quantity = $purchase->quantity ?? 0;
                $price = $purchase->price ?? 0;
                $amount = $quantity * $price;
                return \Auth::user()->priceFormat($amount);
            })
            ->rawColumns(['transaction_date', 'transaction_type', 'num', 'vendor', 'memo_description', 'quantity', 'rate', 'amount', 'balance']);
    }

    public function query(Purchase $model)
    {
        $user = Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();

        // Build query to get purchases with product and vendor information
        $query = $model->newQuery()
            ->select([
                'purchases.id as id',
                'purchases.purchase_id as purchase_id',
                'purchases.vender_id as vender_id',
                'purchases.purchase_date as purchase_date',
                'purchases.description as description',
                'purchase_products.quantity as quantity',
                'purchase_products.price as price',
                'purchase_products.product_id as product_id',
                'purchase_products.description as product_description',
                'product_services.name as product_name'
            ])
            // Join with purchase products
            ->leftJoin('purchase_products', 'purchases.id', '=', 'purchase_products.purchase_id')
            // Join with product services
            ->leftJoin('product_services', 'purchase_products.product_id', '=', 'product_services.id')
            // Join with vendors
            ->leftJoin('venders', 'purchases.vender_id', '=', 'venders.id')
            // Filter by ownership
            ->where('purchases.created_by', $ownerId)
            // Only show purchases that have products
            ->whereNotNull('purchase_products.product_id');

        // Apply filters from request
        if (request()->filled('vendor_name') && request('vendor_name') !== '') {
            $vendorName = request('vendor_name');
            $query->where('venders.name', 'LIKE', "%{$vendorName}%");
        }

        if (request()->filled('product_name') && request('product_name') !== '') {
            $productName = request('product_name');
            $query->where('product_services.name', 'LIKE', "%{$productName}%");
        }

        if (request()->filled('start_date') && request()->filled('end_date')) {
            $startDate = request('start_date');
            $endDate = request('end_date');
            $query->whereBetween('purchases.purchase_date', [$startDate, $endDate]);
        }

        return $query->orderBy('purchases.purchase_date', 'desc')
                    ->orderBy('purchases.purchase_id', 'desc');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('purchases-by-product-service-detail-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('rt')
            ->parameters([
                'responsive' => true,
                'autoWidth' => false,
                'paging' => false,
                'searching' => false,
                'info' => false,
                'ordering' => false,
                'colReorder' => true,
                'fixedHeader' => true,
                'scrollY' => '420px',
                'scrollX' => true,
                'scrollCollapse' => true,
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('transaction_date')->title(__('Transaction Date'))->addClass('text-center'),
            Column::make('transaction_type')->title(__('Transaction Type')),
            Column::make('num')->title(__('Num')),
            Column::make('vendor')->title(__('Vendor')),
            Column::make('memo_description')->title(__('Memo/Description')),
            Column::make('quantity')->title(__('Quantity'))->addClass('text-right'),
            Column::make('rate')->title(__('Rate'))->addClass('text-right'),
            Column::make('amount')->title(__('Amount'))->addClass('text-right'),
            Column::make('balance')->title(__('Balance'))->addClass('text-right'),
        ];
    }

    protected function filename(): string
    {
        return 'PurchasesByProductServiceDetail_' . date('YmdHis');
    }
}