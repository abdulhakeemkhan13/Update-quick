<?php

namespace App\DataTables;

use App\Models\StockReport;
use App\Models\ProductService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;

class InventoryValuationDetailDataTable extends DataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('product_service', function ($stock) {
                return $stock->product ? $stock->product->name : '-';
            })
            ->addColumn('transaction_date', function ($stock) {
                return $stock->created_at ? Carbon::parse($stock->created_at)->format('m/d/Y') : '-';
            })
            ->addColumn('transaction_type', function ($stock) {
                $types = [
                    'bill' => 'Bill',
                    'purchase' => 'Purchase',
                    'vendor_bill' => 'Vendor Bill',
                    'stock_in' => 'Stock In',
                    'opening' => 'Opening',
                    'adjustment_in' => 'Adjustment In',
                    'transfer_in' => 'Transfer In',
                    'credit_note_in' => 'Credit Note In',
                    'manually' => 'Manually',
                    'invoice' => 'Invoice',
                    'sale' => 'Sale',
                    'proposal' => 'Proposal',
                    'stock_out' => 'Stock Out',
                    'adjustment_out' => 'Adjustment Out',
                    'transfer_out' => 'Transfer Out',
                    'debit_note_out' => 'Debit Note Out',
                ];
                return isset($types[$stock->type]) ? $types[$stock->type] : ucfirst($stock->type);
            })
            ->addColumn('num', function ($stock) {
                // Format based on transaction type
                switch ($stock->type) {
                    case 'bill':
                    case 'purchase':
                        return 'PUR-' . str_pad($stock->type_id, 4, '0', STR_PAD_LEFT);
                    case 'invoice':
                    case 'sale':
                        return 'INV-' . str_pad($stock->type_id, 4, '0', STR_PAD_LEFT);
                    default:
                        return $stock->type_id ? $stock->type_id : '-';
                }
            })
            ->addColumn('name', function ($stock) {
                return $stock->description ?? '-';
            })
            ->addColumn('qty', function ($stock) {
                return $stock->quantity;
            })
            ->addColumn('rate', function ($stock) {
                // For products, we can get the purchase price
                if ($stock->product && $stock->product->purchase_price) {
                    return $stock->product->purchase_price;
                }
                return '-';
            })
            ->addColumn('inventory_cost', function ($stock) {
                // Calculate inventory cost = quantity * rate
                $rate = 0;
                if ($stock->product && $stock->product->purchase_price) {
                    $rate = $stock->product->purchase_price;
                }
                $cost = $stock->quantity * $rate;
                return $cost;
            })
            ->addColumn('qty_on_hand', function ($stock) {
                // This would need to be calculated based on all previous transactions
                // For now, we'll return the current quantity
                return $stock->quantity;
            })
            ->addColumn('asset_value', function ($stock) {
                // Asset value = qty_on_hand * rate
                $rate = 0;
                if ($stock->product && $stock->product->purchase_price) {
                    $rate = $stock->product->purchase_price;
                }
                $value = $stock->quantity * $rate;
                return $value;
            })
            ->rawColumns(['product_service', 'transaction_date', 'transaction_type', 'num', 'name', 'qty', 'rate', 'inventory_cost', 'qty_on_hand', 'asset_value']);
    }

    public function query(StockReport $model)
    {
        $user = Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();

        // Build query to get stock reports with product information
        $query = $model->newQuery()
            ->select([
                'stock_reports.id',
                'stock_reports.product_id',
                'stock_reports.quantity',
                'stock_reports.type',
                'stock_reports.type_id',
                'stock_reports.description',
                'stock_reports.created_at',
                'stock_reports.created_by',
                'product_services.name as product_name',
                'product_services.purchase_price'
            ])
            // Join with products
            ->leftJoin('product_services', 'stock_reports.product_id', '=', 'product_services.id')
            // Filter by ownership
            ->where('stock_reports.created_by', $ownerId)
            // Only show stock reports that have a product
            ->whereNotNull('product_services.name');

        // Apply filters from request
        if (request()->filled('product_name') && request('product_name') !== '') {
            $productName = request('product_name');
            $query->where('product_services.name', 'LIKE', "%{$productName}%");
        }

        // dd(request('startDate'), request('endDate'));
        if (request()->filled('startDate') && request()->filled('endDate')) {
            $startDate = request('startDate');
            $endDate = request('endDate');
            $query->whereBetween('stock_reports.created_at', [$startDate, $endDate]);
        }

        return $query->orderBy('stock_reports.created_at', 'desc')
                    ->orderBy('product_services.name', 'asc');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('inventory-valuation-detail-table')
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
            Column::make('product_service')->title(__('Product/Service')),
            Column::make('transaction_date')->title(__('Transaction Date'))->addClass('text-center'),
            Column::make('transaction_type')->title(__('Transaction Type')),
            Column::make('num')->title(__('Num')),
            Column::make('name')->title(__('Name')),
            Column::make('qty')->title(__('Qty'))->addClass('text-right'),
            Column::make('rate')->title(__('Rate'))->addClass('text-right'),
            Column::make('inventory_cost')->title(__('Inventory Cost'))->addClass('text-right'),
            Column::make('qty_on_hand')->title(__('Qty on Hand'))->addClass('text-right'),
            Column::make('asset_value')->title(__('Asset Value'))->addClass('text-right'),
        ];
    }

    protected function filename(): string
    {
        return 'InventoryValuationDetail_' . date('YmdHis');
    }
}