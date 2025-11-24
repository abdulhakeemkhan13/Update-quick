<?php

namespace App\DataTables;

use App\Models\PurchaseProduct;
use Carbon\Carbon;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\DB;

class OpenPurchaseOrderDetail extends DataTable
{
    public function dataTable($query)
    {
        $data = collect($query->get());

        $grandTotal = [
            'quantity' => 0,
            'received_quantity' => 0,
            'backordered_quantity' => 0,
            'total_amount' => 0,
            'received_amount' => 0,
            'open_balance' => 0,
        ];

        $finalData = collect();
        $categories = $data->groupBy('category_name');

        foreach ($categories as $category => $rowsByCategory) {
            $categoryTotals = [
                'quantity' => 0,
                'received_quantity' => 0,
                'backordered_quantity' => 0,
                'total_amount' => 0,
                'received_amount' => 0,
                'open_balance' => 0,
            ];

            // Category header (parent)
            $finalData->push((object) [
                'transaction_date' => '',
                'vendor_name' => '<span class="toggle-bucket" data-bucket="' . \Str::slug($category) . '">
                <span class="icon">▼</span> <strong>' . e($category) . '</strong></span>',
                'transaction' => '',
                'product_name' => '',
                'full_name' => '',
                'quantity' => '',
                'received_quantity' => '',
                'backordered_quantity' => '',
                'total_amount' => '',
                'received_amount' => '',
                'open_balance' => '',
                'isParent' => true,
                'category_name' => $category,
            ]);

            // Group inside category by purchase order
            $purchases = $rowsByCategory->groupBy('purchase_id');

            foreach ($purchases as $purchaseId => $rowsByPurchase) {
                $purchase = $rowsByPurchase->first();
                $purchaseNumber = \Auth::user()->purchaseNumberFormat($purchase->purchase ?? $purchaseId);

                // Compute PO-level totals
                $purchaseTotals = [
                    'quantity' => 0,
                    'received_quantity' => 0,
                    'backordered_quantity' => 0,
                    'total_amount' => 0,
                    'received_amount' => 0,
                    'open_balance' => 0,
                ];

                // Calculate totals for the purchase before rendering items
                foreach ($rowsByPurchase as $row) {
                    $row->backordered_quantity = $row->quantity - $row->received_quantity;
                    $row->total_amount = ($row->price * $row->quantity)
                        - ($row->discount ?? 0)
                        + ($row->tax_amount ?? 0);
                    $row->category_name = $category;

                    $purchaseTotals['quantity'] += $row->quantity;
                    $purchaseTotals['received_quantity'] += $row->received_quantity;
                    $purchaseTotals['backordered_quantity'] += $row->backordered_quantity;
                    $purchaseTotals['total_amount'] += $row->total_amount;
                }

                // Add purchase-level paid and open balance once
                $purchaseTotals['received_amount'] = $purchase->paid_amount ?? 0;
                $purchaseTotals['open_balance'] = $purchaseTotals['total_amount'] - $purchaseTotals['received_amount'];

                // Purchase Order header row (shows totals once)
                $finalData->push((object) [
                    'transaction_date' => '',
                    'transaction' => '<strong>' . e($purchaseNumber) . '</strong>',
                    'vendor_name' => '<strong>' . e($purchaseNumber) . '</strong>',
                    'product_name' => '<em>' . ($purchase->memo ?? '') . '</em>',
                    'full_name' => $purchase->ship_via ?? '',
                    'quantity' => '',
                    'received_quantity' => '',
                    'backordered_quantity' => '',
                    'total_amount' => '',
                    // 'received_amount' => $purchaseTotals['received_amount'],
                    // 'open_balance' => $purchaseTotals['open_balance'],
                    'received_amount' => $purchaseTotals['received_amount'],
                    'open_balance' => $purchaseTotals['open_balance'],
                    'isPurchase' => true,
                    'category_name' => $category,
                ]);

                // Item rows (no received/open balance)
                foreach ($rowsByPurchase as $row) {
                    $row->transaction = $purchaseNumber;
                    $row->received_amount = ''; // empty for item rows
                    $row->open_balance = ''; // empty for item rows
                    $finalData->push($row);
                }

                // Purchase subtotal
                $finalData->push((object) [
                    'transaction_date' => '',
                    'vendor_name' => "<strong>Subtotal for {$purchaseNumber}</strong>",
                    'transaction' => '',
                    'product_name' => '',
                    'full_name' => '',
                    'quantity' => $purchaseTotals['quantity'],
                    'received_quantity' => $purchaseTotals['received_quantity'],
                    'backordered_quantity' => $purchaseTotals['backordered_quantity'],
                    'total_amount' => $purchaseTotals['total_amount'],
                    'received_amount' => $purchaseTotals['received_amount'],
                    'open_balance' => $purchaseTotals['open_balance'],
                    'isSubtotal' => true,
                    'category_name' => $category,
                ]);

                foreach (array_keys($categoryTotals) as $key) {
                    $categoryTotals[$key] += $purchaseTotals[$key];
                }
            }

            // Category subtotal
            $finalData->push((object) [
                'transaction_date' => '',
                'vendor_name' => "<strong>Subtotal for {$category}</strong>",
                'transaction' => '',
                'product_name' => '',
                'full_name' => '',
                'quantity' => $categoryTotals['quantity'],
                'received_quantity' => $categoryTotals['received_quantity'],
                'backordered_quantity' => $categoryTotals['backordered_quantity'],
                'total_amount' => $categoryTotals['total_amount'],
                'received_amount' => $categoryTotals['received_amount'],
                'open_balance' => $categoryTotals['open_balance'],
                'isSubtotal' => true,
                'category_name' => $category,
            ]);

            // Blank spacer row
            $finalData->push((object) [
                'transaction_date' => '',
                'transaction' => '',
                'vendor_name' => '',
                'product_name' => '',
                'full_name' => '',
                'quantity' => '',
                'received_quantity' => '',
                'backordered_quantity' => '',
                'total_amount' => '',
                'received_amount' => '',
                'open_balance' => '',
                'isPlaceholder' => true,
                'category_name' => $category,
            ]);

            foreach ($categoryTotals as $key => $val) {
                $grandTotal[$key] += $val;
            }
        }

        // Grand Total row
        $finalData->push((object) [
            'transaction_date' => '',
            'vendor_name' => '<strong>Grand Total</strong>',
            'transaction' => '',
            'product_name' => '',
            'full_name' => '',
            'quantity' => $grandTotal['quantity'],
            'received_quantity' => $grandTotal['received_quantity'],
            'backordered_quantity' => $grandTotal['backordered_quantity'],
            'total_amount' => $grandTotal['total_amount'],
            'received_amount' => $grandTotal['received_amount'],
            'open_balance' => $grandTotal['open_balance'],
            'isGrandTotal' => true,
        ]);

        return datatables()
            ->collection($finalData)
            ->editColumn('transaction_date', function ($row) {
                return (isset($row->isParent) || isset($row->isSubtotal) || isset($row->isGrandTotal) || isset($row->isPlaceholder) || isset($row->isPurchase))
                    ? ''
                    : ($row->transaction_date ? Carbon::parse($row->transaction_date)->format('Y-m-d') : '');
            })
            ->setRowClass(function ($row) {
                $bucket = \Str::slug($row->category_name ?? 'na');

                if (property_exists($row, 'isParent') && $row->isParent)
                    return 'parent-row toggle-bucket bucket-' . $bucket;

                if (property_exists($row, 'isPurchase') && $row->isPurchase)
                    return 'purchase-row bucket-' . $bucket;

                if (property_exists($row, 'isSubtotal') && $row->isSubtotal)
                    return 'subtotal-row bucket-' . $bucket;

                if (property_exists($row, 'isGrandTotal') && $row->isGrandTotal)
                    return 'grandtotal-row';

                if (property_exists($row, 'isPlaceholder') && $row->isPlaceholder)
                    return 'placeholder-row bucket-' . $bucket;

                return 'child-row bucket-' . $bucket;
            })
            ->rawColumns(['transaction', 'product_name', "vendor_name"]);
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
                'purchases.id as purchase_id',
                'purchases.purchase_id as purchase',
                'purchases.purchase_date as transaction_date',
                'venders.name as vendor_name',
                'product_services.name as product_name',
                'product_service_categories.name as category_name',
                'product_services.name as full_name',

                // ✅ Total paid (same as before)
                DB::raw('(SELECT IFNULL(SUM(ppay.amount),0)
                      FROM purchase_payments ppay
                      WHERE ppay.purchase_id = purchases.id) as paid_amount'),

                // ✅ Updated tax_amount logic to fully match Query #1’s nested subquery
                DB::raw('(SELECT IFNULL(SUM((pp.price * pp.quantity - IFNULL(pp.discount,0)) * (taxes.rate / 100)),0)
                      FROM purchase_products pp
                      LEFT JOIN taxes ON FIND_IN_SET(taxes.id, pp.tax) > 0
                      WHERE pp.purchase_id = purchases.id) as tax_amount'),

                // ✅ Optional: add total amount per purchase (for consistency)
                DB::raw('(
                SELECT IFNULL(SUM(
                    (pp.price * pp.quantity)
                    - IFNULL(pp.discount, 0)
                    + IFNULL(
                        (SELECT IFNULL(SUM((pp2.price * pp2.quantity - pp2.discount) * (taxes.rate / 100)), 0)
                         FROM purchase_products pp2
                         LEFT JOIN taxes ON FIND_IN_SET(taxes.id, pp2.tax) > 0
                         WHERE pp2.purchase_id = purchases.id),
                    0)
                ), 0)
                FROM purchase_products pp
                WHERE pp.purchase_id = purchases.id
            ) as total_amount')
            )
            ->join('purchases', 'purchases.id', '=', 'purchase_products.purchase_id')
            ->join('venders', 'venders.id', '=', 'purchases.vender_id')
            ->join('product_services', 'product_services.id', '=', 'purchase_products.product_id')
            ->join('product_service_categories', 'product_service_categories.id', '=', 'product_services.category_id')
            ->where('purchases.created_by', \Auth::user()->creatorId())
            ->whereBetween('purchases.purchase_date', [$start, $end])
            ->groupBy(
                'purchase_products.id',
                'purchases.id',
                'purchases.purchase_id',
                'purchases.purchase_date',
                'venders.name',
                'product_services.name',
                'product_service_categories.name'
            );
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
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('transaction_date')->title('Date'),
            // Column::make('transaction')->title('Transaction'),
            Column::make('vendor_name')->title('Vendor Name'),
            Column::make('product_name')->title('Product/Service Name'),
            Column::make('full_name')->title('Full Name'),
            Column::make('quantity')->title('Quantity'),
            Column::make('backordered_quantity')->title('Backordered Quantity'),
            Column::make('total_amount')->title('Total Amount'),
            Column::make('received_amount')->title('Received (Paid) Amount'),
            Column::make('open_balance')->title('PO Open Balance'),
        ];
    }
}
