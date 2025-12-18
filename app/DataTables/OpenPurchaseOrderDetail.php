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
        // Fetch products and accounts, then merge
        $products = collect($query->get());
        $accounts = $this->getPurchaseOrderAccounts();
        
        // Merge products and accounts
        $data = $products->concat($accounts)->sortBy(function($item) {
            return $item->purchase_id . '-' . ($item->order ?? 999);
        });

        $grandTotal = [
            'quantity' => 0,
            'received_quantity' => 0,
            'backordered_quantity' => 0,
            'total_amount' => 0,
            'received_amount' => 0,
            'open_balance' => 0,
        ];

        $finalData = collect();
        $vendors = $data->groupBy('vendor_name');

        foreach ($vendors as $vendor => $rowsByVendor) {
            $vendorTotals = [
                'quantity' => 0,
                'received_quantity' => 0,
                'backordered_quantity' => 0,
                'total_amount' => 0,
                'received_amount' => 0,
                'open_balance' => 0,
            ];

            // Vendor header (parent)
            $finalData->push((object) [
                'transaction_date' => '<span class="toggle-bucket" data-bucket="' . \Str::slug($vendor) . '">
                <span class="icon">▼</span> <strong>' . e($vendor) . '</strong></span>',
                'vendor_name' => $vendor,
                'transaction' => '',
                'product_name' => '',
                'full_name' => '',
                'memo' => '',
                'ship_via' => '',
                'quantity' => '',
                'received_quantity' => '',
                'backordered_quantity' => '',
                'total_amount' => '',
                'received_amount' => '',
                'open_balance' => '',
                'isParent' => true,
                'isVendor' => true,
            ]);

            // Group inside vendor by purchase order
            $purchases = $rowsByVendor->groupBy('purchase_id');

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
                    $row->vendor_name = $vendor;
                    $row->memo = $purchase->memo ?? '';
                    $row->ship_via = $purchase->ship_via ?? '';

                    $purchaseTotals['quantity'] += $row->quantity;
                    $purchaseTotals['received_quantity'] += $row->received_quantity;
                    $purchaseTotals['backordered_quantity'] += $row->backordered_quantity;
                    $purchaseTotals['total_amount'] += $row->total_amount;
                }

                // Add purchase-level paid and open balance once
                $purchaseTotals['received_amount'] = $purchase->paid_amount ?? 0;
                $purchaseTotals['open_balance'] = $purchaseTotals['total_amount'] - $purchaseTotals['received_amount'];

                // Purchase Order header row - REMOVED per user request
                // $finalData->push((object) [
                //     'transaction_date' => '',
                //     'transaction' => '<strong>' . e($purchaseNumber) . '</strong>',
                //     'vendor_name' => $vendor,
                //     'product_name' => '',
                //     'full_name' => '',
                //     'memo' => '<em>' . ($purchase->memo ?? '') . '</em>',
                //     'ship_via' => $purchase->ship_via ?? '',
                //     'quantity' => '',
                //     'received_quantity' => '',
                //     'backordered_quantity' => '',
                //     'total_amount' => '',
                //     'received_amount' => $purchaseTotals['received_amount'],
                //     'open_balance' => $purchaseTotals['open_balance'],
                //     'isPurchase' => true,
                //     'vendor_name' => $vendor,
                // ]);

                // Item rows (show open balance per line)
                foreach ($rowsByPurchase as $row) {
                    $row->transaction = $purchaseNumber;
                    $row->received_amount = 0; // Line items not individually paid
                    $row->open_balance = $row->total_amount; // Full amount is open for each line
                    $finalData->push($row);
                }

                // Purchase subtotal - REMOVED per user request
                // $finalData->push((object) [
                //     'transaction_date' => '<strong>Subtotal for ' . $purchaseNumber . '</strong>',
                //     'vendor_name' => $vendor,
                //     'transaction' => '',
                //     'product_name' => '',
                //     'full_name' => '',
                //     'memo' => '',
                //     'ship_via' => '',
                //     'quantity' => $purchaseTotals['quantity'],
                //     'received_quantity' => $purchaseTotals['received_quantity'],
                //     'backordered_quantity' => $purchaseTotals['backordered_quantity'],
                //     'total_amount' => $purchaseTotals['total_amount'],
                //     'received_amount' => $purchaseTotals['received_amount'],
                //     'open_balance' => $purchaseTotals['open_balance'],
                //     'isSubtotal' => true,
                //     'vendor_name' => $vendor,
                // ]);

                foreach (array_keys($vendorTotals) as $key) {
                    $vendorTotals[$key] += $purchaseTotals[$key];
                }
            }

            // Vendor subtotal
            $finalData->push((object) [
                'transaction_date' => '<strong>Subtotal for ' . $vendor . '</strong>',
                'vendor_name' => $vendor,
                'transaction' => '',
                'product_name' => '',
                'full_name' => '',
                'memo' => '',
                'ship_via' => '',
                'quantity' => $vendorTotals['quantity'],
                'received_quantity' => $vendorTotals['received_quantity'],
                'backordered_quantity' => $vendorTotals['backordered_quantity'],
                'total_amount' => $vendorTotals['total_amount'],
                'received_amount' => $vendorTotals['received_amount'],
                'open_balance' => $vendorTotals['open_balance'],
                'isSubtotal' => true,
                'vendor_name' => $vendor,
            ]);

            // Blank spacer row
            $finalData->push((object) [
                'transaction_date' => '',
                'transaction' => '',
                'vendor_name' => '',
                'product_name' => '',
                'full_name' => '',
                'memo' => '',
                'ship_via' => '',
                'quantity' => '',
                'received_quantity' => '',
                'backordered_quantity' => '',
                'total_amount' => '',
                'received_amount' => '',
                'open_balance' => '',
                'isPlaceholder' => true,
            ]);

            foreach ($vendorTotals as $key => $val) {
                $grandTotal[$key] += $val;
            }
        }

        // Grand Total row
        $finalData->push((object) [
            'transaction_date' => '<strong>Grand Total</strong>',
            'vendor_name' => '',
            'transaction' => '',
            'product_name' => '',
            'full_name' => '',
            'memo' => '',
            'ship_via' => '',
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
                    ? $row->transaction_date // keep HTML for special rows
                    : ($row->transaction_date ? Carbon::parse($row->transaction_date)->format('Y-m-d') : '');
            })
            ->setRowClass(function ($row) {
                $bucket = \Str::slug($row->vendor_name ?? 'na');

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
            ->rawColumns(['transaction_date', 'transaction', 'product_name', 'vendor_name', 'memo']);
    }

    /**
     * Get purchase order accounts to merge with products
     */
    protected function getPurchaseOrderAccounts()
    {
        $start = request()->get('start_date')
            ?? request()->get('startDate')
            ?? Carbon::now()->startOfYear()->format('Y-m-d');

        $end = request()->get('end_date')
            ?? request()->get('endDate')
            ?? Carbon::now()->endOfDay()->format('Y-m-d');

        return DB::table('purchase_order_accounts')
            ->select(
                'purchase_order_accounts.id',
                'purchase_order_accounts.ref_id as purchase_id',
                'purchase_order_accounts.description',
                'purchase_order_accounts.price',
                DB::raw('1 as quantity'), // Accounts don't have quantity
                DB::raw('0 as received_quantity'), // Not tracked at line level
                'purchase_order_accounts.order',
                'purchases.purchase_id as purchase',
                'purchases.purchase_date as transaction_date',
                'purchases.notes as memo',
                'purchases.ship_via',
                'venders.name as vendor_name',
                DB::raw("'' as product_name"), // Empty for accounts
                'chart_of_accounts.name as full_name', // Account name in full_name
                DB::raw("'Account' as item_type"), // Mark as account
                DB::raw('0 as discount'),
                DB::raw('0 as tax_amount'), // Tax already in price
                DB::raw('0 as paid_amount'), // Payments at PO level, not line level
                DB::raw('purchase_order_accounts.price as total_amount'), // Price = total
                DB::raw('0 as received_amount'), // Not tracked at line level
                DB::raw('purchase_order_accounts.price as open_balance') // Full amount is open
            )
            ->join('purchases', 'purchases.id', '=', 'purchase_order_accounts.ref_id')
            ->join('venders', 'venders.id', '=', 'purchases.vender_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'purchase_order_accounts.chart_account_id')
            ->where('purchases.created_by', \Auth::user()->creatorId())
            ->whereBetween('purchases.purchase_date', [$start, $end])
            ->where('purchases.status', '!=', '2')
            ->get();
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
                'purchases.notes as memo',
                'purchases.ship_via',
                'venders.name as vendor_name',
                'product_services.name as product_name',
                'product_services.name as full_name', // Product full name
                'product_service_categories.name as category_name',
                DB::raw("'Product' as item_type"), // Mark as product
                DB::raw('0 as received_quantity'), // Not tracked at product line level

                // ✅ Total paid (same as before)
                DB::raw('(SELECT IFNULL(SUM(ppay.amount),0)
                      FROM purchase_payments ppay
                      WHERE ppay.purchase_id = purchases.id) as paid_amount'),

                // ✅ Updated tax_amount logic to fully match Query #1's nested subquery
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
            ->where('purchases.status', '!=', '2')
            ->groupBy(
                'purchase_products.id',
                'purchases.id',
                'purchases.purchase_id',
                'purchases.purchase_date',
                'purchases.notes',
                'purchases.ship_via',
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
            Column::make('vendor_name')->title('Vendor'),
            Column::make('product_name')->title('Product/Service'),
            Column::make('full_name')->title('Full Name'),
            Column::make('memo')->title('Memo / Description'),
            Column::make('ship_via')->title('Ship Via'),
            Column::make('quantity')->title('Qty'),
            Column::make('backordered_quantity')->title('Backordered')->addClass('default-hidden'),
            Column::make('total_amount')->title('Amount'),
            Column::make('received_amount')->title('Received'),
            Column::make('open_balance')->title('PO Open Balance'),
        ];
    }
}
