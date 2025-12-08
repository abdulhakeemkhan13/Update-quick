<?php

namespace App\DataTables;

use App\Models\BillProduct;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class PurchasesByProductServiceDetail extends DataTable
{
    public function dataTable($query)
    {
        // $query is already a collection from query() method
        $products = collect($query);
        $accounts = $this->getBillAccounts();
        
        // Merge products and accounts
        $data = $products->concat($accounts)->sortBy([
            ['product_service_name', 'asc'],
            ['transaction_date', 'asc'],
        ]);

        $finalData = collect();
        $grandTotal = 0;

        // Group by product/service instead of vendor
        $productServices = $data->groupBy(function($item) {
            return $item->product_service_name ?? $item->account_name ?? 'No Product/Service';
        });

        foreach ($productServices as $productService => $rows) {
            $productServiceSubtotal = 0;
            
            // Product/Service header row
            $finalData->push((object) [
                'transaction_date' => '<span class="toggle-bucket" data-bucket="' . \Str::slug($productService) . '"><span class="icon">▼</span> <strong>' . e($productService) . '</strong></span>',
                'transaction_type' => '',
                'transaction' => '',
                'vendor_name' => '',
                'memo' => '',
                'quantity' => '',
                'rate' => '',
                'amount' => '',
                'balance' => '',
                'product_service_name' => $productService,
                'isProductServiceHeader' => true,
                'isParent' => true,
            ]);

            foreach ($rows as $row) {
                // Calculate amount: price * quantity - discount + tax
                $amount = $row->price * $row->quantity - ($row->discount ?? 0) + ($row->tax_amount ?? 0);
                $productServiceSubtotal += $amount;

                $finalData->push((object) [
                    'transaction_date' => $row->transaction_date,
                    'transaction_type' => $row->bill_type ?? 'Bill',
                    'transaction' => \Auth::user()->billNumberFormat($row->bill_number ?? $row->bill_id),
                    'vendor_name' => $row->vendor_name ?? '',
                    'memo' => $row->description ?? '',
                    'quantity' => $row->quantity,
                    'rate' => $row->quantity > 0 ? ($row->price) : 0,
                    'amount' => $amount,
                    'balance' => $productServiceSubtotal, // Running balance
                    'product_service_name' => $productService,
                    'isDetail' => true,
                ]);
            }

            // Product/Service subtotal row
            $finalData->push((object) [
                'transaction_date' => "<strong>Subtotal for {$productService}</strong>",
                'transaction_type' => '',
                'transaction' => '',
                'vendor_name' => '',
                'memo' => '',
                'quantity' => '',
                'rate' => '',
                'amount' => $productServiceSubtotal,
                'balance' => $productServiceSubtotal,
                'product_service_name' => $productService,
                'isSubtotal' => true,
            ]);

            // Blank spacer row
            $finalData->push((object) [
                'transaction_date' => '',
                'transaction_type' => '',
                'transaction' => '',
                'vendor_name' => '',
                'memo' => '',
                'quantity' => '',
                'rate' => '',
                'amount' => '',
                'balance' => '',
                'product_service_name' => $productService,
                'isPlaceholder' => true,
            ]);

            $grandTotal += $productServiceSubtotal;
        }

        // Grand Total row
        $finalData->push((object) [
            'transaction_date' => '<strong>Grand Total</strong>',
            'transaction_type' => '',
            'transaction' => '',
            'vendor_name' => '',
            'memo' => '',
            'quantity' => '',
            'rate' => '',
            'amount' => $grandTotal,
            'balance' => $grandTotal,
            'product_service_name' => '',
            'isGrandTotal' => true,
        ]);

        return datatables()
            ->collection($finalData)
            ->editColumn('transaction_date', function ($row) {
                if (isset($row->isDetail)) {
                    return $row->transaction_date ? Carbon::parse($row->transaction_date)->format('M d, Y') : '';
                }
                return $row->transaction_date; // Keep HTML for headers/subtotals
            })
            ->editColumn('transaction', fn($row) => $row->transaction ?? '')
            ->editColumn('memo', fn($row) => isset($row->isDetail) ? $row->memo : '')
            ->editColumn('amount', function ($row) {
                if ((isset($row->isProductServiceHeader) && $row->isProductServiceHeader) || (isset($row->isPlaceholder) && $row->isPlaceholder)) {
                    return '';
                }
                return number_format((float) $row->amount, 2);
            })
            ->editColumn('quantity', function ($row) {
                if (isset($row->isProductServiceHeader) || isset($row->isSubtotal) || isset($row->isPlaceholder) || isset($row->isGrandTotal)) {
                    return '';
                }
                return $row->quantity;
            })
            ->editColumn('rate', function ($row) {
                if (isset($row->isProductServiceHeader) || isset($row->isSubtotal) || isset($row->isPlaceholder) || isset($row->isGrandTotal)) {
                    return '';
                }
                return number_format((float) $row->rate, 2);
            })
            ->editColumn('balance', function ($row) {
                if (isset($row->isProductServiceHeader) || isset($row->isPlaceholder)) {
                    return '';
                }
                return number_format((float) $row->balance, 2);
            })
            ->setRowClass(function ($row) {
                $productServiceSlug = $row->product_service_name ? \Str::slug($row->product_service_name) : 'no-product';
                if (isset($row->isProductServiceHeader) && $row->isProductServiceHeader)
                    return 'parent-row toggle-bucket bucket-' . $productServiceSlug;
                if (isset($row->isSubtotal) && !isset($row->isGrandTotal))
                    return 'subtotal-row bucket-' . $productServiceSlug;
                if (isset($row->isGrandTotal))
                    return 'grandtotal-row';
                if (isset($row->isPlaceholder))
                    return 'placeholder-row bucket-' . $productServiceSlug;
                return 'child-row bucket-' . $productServiceSlug;
            })
            ->rawColumns(['transaction', 'transaction_date', 'transaction_type']);
    }

    /**
     * Query bill_products with purchase->bill relationship
     */
    public function query(BillProduct $model)
    {
        $start = request()->get('start_date')
            ?? request()->get('startDate')
            ?? Carbon::now()->startOfYear()->format('Y-m-d');

        $end = request()->get('end_date')
            ?? request()->get('endDate')
            ?? Carbon::now()->endOfDay()->format('Y-m-d');

        // Query bill_products through purchases that have bill relationship
        return DB::table('bill_products')
            ->select(
                'bill_products.id',
                'bill_products.price',
                'bill_products.quantity',
                'bill_products.discount',
                'bill_products.description',
                'bills.bill_id',
                'bills.bill_date as transaction_date',
                'bills.type as bill_type',
                'venders.name as vendor_name',
                'product_services.name as product_service_name',
                DB::raw("NULL as account_name"), // Mark as product
                
                // FIXED TAX CALCULATION
                DB::raw('(
                    SELECT IFNULL(
                        SUM(
                            (
                                (bill_products.price * bill_products.quantity) 
                                - IFNULL(bill_products.discount, 0)
                            ) * (taxes.rate / 100)
                        ), 
                        0
                    )
                    FROM taxes
                    WHERE FIND_IN_SET(taxes.id, bill_products.tax) > 0
                ) AS tax_amount')
            )
            ->join('bills', 'bills.id', '=', 'bill_products.bill_id')
            ->join('purchases', function($join) {
                $join->on('purchases.txn_id', '=', 'bills.id')
                     ->whereNotNull('purchases.txn_type');
            })
            ->join('venders', 'venders.id', '=', 'purchases.vender_id')
            ->join('product_services', 'product_services.id', '=', 'bill_products.product_id')
            ->where('purchases.created_by', \Auth::user()->creatorId())
            ->whereBetween('bills.bill_date', [$start, $end])
            ->whereNotNull('purchases.txn_id')
            ->get();
    }

    /**
     * Get bill accounts through purchases that have bill relationship
     */
    protected function getBillAccounts()
    {
        $start = request()->get('start_date')
            ?? request()->get('startDate')
            ?? Carbon::now()->startOfYear()->format('Y-m-d');

        $end = request()->get('end_date')
            ?? request()->get('endDate')
            ?? Carbon::now()->endOfDay()->format('Y-m-d');

        return DB::table('bill_accounts')
            ->select(
                'bill_accounts.id',
                'bill_accounts.price',
                DB::raw('1 as quantity'), // Accounts don't have quantity
                DB::raw('0 as discount'), // Accounts don't have discount
                'bill_accounts.description',
                'bills.bill_id',
                'bills.bill_date as transaction_date',
                'bills.type as bill_type',
                'venders.name as vendor_name',
                DB::raw("NULL as product_service_name"), // Mark as account
                'chart_of_accounts.name as account_name',
                DB::raw('0 as tax_amount') // Price already includes tax for accounts
            )
            ->join('bills', 'bills.id', '=', 'bill_accounts.ref_id')
            ->join('purchases', function($join) {
                $join->on('purchases.txn_id', '=', 'bills.id');
            })
            ->join('venders', 'venders.id', '=', 'purchases.vender_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'bill_accounts.chart_account_id')
            ->where('purchases.created_by', \Auth::user()->creatorId())
            ->whereBetween('bills.bill_date', [$start, $end])
            ->whereNotNull('purchases.txn_id')
            ->get();
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
            Column::make('transaction_type')->title('Type'),
            Column::make('transaction')->title('Num'),
            Column::make('vendor_name')->title('Vendor'),
            Column::make('memo')->title('Memo/Description'),
            Column::make('quantity')->title('Qty'),
            Column::make('rate')->title('Rate'),
            Column::make('amount')->title('Amount'),
            Column::make('balance')->title('Balance'),
        ];
    }
}
