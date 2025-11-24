<?php

namespace App\DataTables;

use App\Models\BillProduct;
use Carbon\Carbon;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\DB;

class PurchasesByProductServiceDetail extends DataTable
{
    public function dataTable($query)
    {
        $data = collect($query->get());

        $grandTotalAmount = 0;
        $finalData = collect();

        // Group by category
        $categories = $data->groupBy('category_name');

        foreach ($categories as $category => $rowsByCategory) {
            $categorySubtotal = 0;

            // Category Header
            $finalData->push((object) [
                'category' => $category,
                'transaction_date' => '',
                'transaction_type' => '',
                'transaction' => '<span class="toggle-bucket bucket-' . \Str::slug($category) . '"><span class="icon">▼</span> <strong>' . $category . '</strong></span>',
                'vendor' => '',
                'description' => '',
                'quantity' => '',
                'rate' => '',
                'amount' => '',
                'balance' => '',
                'isParent' => true,
                'isCategory' => true,
            ]);

            // Group by product
            $products = $rowsByCategory->groupBy('product_name');

            foreach ($products as $product => $rowsByProduct) {
                $productSubtotal = 0;
                $runningBalance = 0;

                // Product Header
                $finalData->push((object) [
                    'category' => $category,
                    'product' => $product,
                    'transaction_date' => '',
                    'transaction_type' => '',
                    'transaction' => '<span class="toggle-bucket bucket-' . \Str::slug($category . '-' . $product) . '"><span class="icon">▶</span> ' . $product . '</span>',
                    'vendor' => '',
                    'description' => '',
                    'quantity' => '',
                    'rate' => '',
                    'amount' => '',
                    'balance' => '',
                    'isParent' => true,
                    'isProduct' => true,
                ]);

                // Transactions
                foreach ($rowsByProduct as $row) {
                    $amount = ($row->price * $row->quantity) - ($row->discount ?? 0) + ($row->tax_amount ?? 0);
                    $runningBalance += $amount;
                    $productSubtotal += $amount;

                    $row->transaction = \Auth::user()->billNumberFormat($row->bill ?? $row->bill_id);
                    $row->transaction_type = 'Bill';
                    $row->amount = $amount;
                    $row->balance = $runningBalance;

                    $finalData->push($row);
                }

                // Product Subtotal
                $finalData->push((object) [
                    'category' => $category,
                    'product' => $product,
                    'transaction' => '<strong>Subtotal for ' . $product . '</strong>',
                    'transaction_date' => '',
                    'transaction_type' => '',
                    'vendor' => '',
                    'description' => '',
                    'quantity' => '',
                    'rate' => '',
                    'amount' => $productSubtotal,
                    'balance' => '',
                    'isSubtotal' => true,
                ]);

                $categorySubtotal += $productSubtotal;
            }

            // Category Subtotal
            $finalData->push((object) [
                'category' => $category,
                'transaction' => '<strong>Subtotal for ' . $category . '</strong>',
                'transaction_date' => '',
                'transaction_type' => '',
                'vendor' => '',
                'description' => '',
                'quantity' => '',
                'rate' => '',
                'amount' => $categorySubtotal,
                'balance' => '',
                'isSubtotal' => true,
            ]);

            $grandTotalAmount += $categorySubtotal;
        }

        // Grand Total
        $finalData->push((object) [
            'transaction' => '<strong>Grand Total</strong>',
            'transaction_date' => '',
            'transaction_type' => '',
            'vendor' => '',
            'description' => '',
            'quantity' => '',
            'rate' => '',
            'amount' => $grandTotalAmount,
            'balance' => '',
            'isGrandTotal' => true,
        ]);

        return datatables()
            ->collection($finalData)
            ->editColumn('transaction_date', fn($row) => isset($row->isSubtotal) || isset($row->isParent) || isset($row->isGrandTotal) ? '' : $row->bill_date)
            ->editColumn('vendor', fn($row) => isset($row->isSubtotal) || isset($row->isParent) || isset($row->isGrandTotal) ? '' : $row->vendor_name)
            ->editColumn('quantity', fn($row) => isset($row->isSubtotal) || isset($row->isParent) || isset($row->isGrandTotal) ? '' : $row->quantity)
            ->editColumn(
                'rate',
                fn($row) =>
                isset($row->isSubtotal) || isset($row->isParent) || isset($row->isGrandTotal)
                ? ''
                : number_format((float) $row->price, 2)
            )
            ->editColumn(
                'amount',
                fn($row) =>
                isset($row->isSubtotal) || isset($row->isGrandTotal)
                ? number_format((float) $row->amount, 2)
                : (isset($row->amount) ? number_format((float) $row->amount, 2) : '')
            )
            ->editColumn(
                'balance',
                fn($row) =>
                isset($row->balance)
                ? number_format((float) $row->balance, 2)
                : ''
            )
            ->rawColumns(['transaction']);
    }

    public function query(BillProduct $model)
    {
        $start = request()->get('start_date') ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $end = request()->get('end_date') ?? Carbon::now()->endOfDay()->format('Y-m-d');

        return $model->newQuery()
            ->select(
                'bill_products.*',
                'bills.bill_id as bill',
                'bills.bill_date',
                'venders.name as vendor_name',
                'product_services.name as product_name',
                'product_service_categories.name as category_name',
                DB::raw('(SELECT IFNULL(SUM((price * quantity - discount) * (taxes.rate / 100)),0) 
                    FROM bill_products 
                    LEFT JOIN taxes ON FIND_IN_SET(taxes.id, bill_products.tax) > 0
                    WHERE bill_products.id = bill_products.id) as tax_amount')
            )
            ->join('bills', 'bills.id', '=', 'bill_products.bill_id')
            ->join('venders', 'venders.id', '=', 'bills.vender_id')
            ->join('product_services', 'product_services.id', '=', 'bill_products.product_id')
            ->join('product_service_categories', 'product_service_categories.id', '=', 'product_services.category_id')
            ->where('bills.created_by', \Auth::user()->creatorId())
            ->whereBetween('bills.bill_date', [$start, $end]);
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('purchases-product-detail-table')
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
            Column::make('transaction')->title('Transaction'),
            Column::make('vendor')->title('Vendor'),
            Column::make('description')->title('Memo/Description'),
            Column::make('quantity')->title('Quantity'),
            Column::make('rate')->title('Rate'),
            Column::make('amount')->title('Amount'),
            Column::make('balance')->title('Balance'),
        ];
    }
}
