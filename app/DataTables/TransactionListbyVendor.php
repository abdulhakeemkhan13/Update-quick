<?php

namespace App\DataTables;

use App\Models\BillPayment;
use App\Models\Purchase;
use App\Models\PurchaseProduct;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class TransactionListByVendor extends DataTable
{
    public function dataTable($query)
    {
        $data = collect($query->get());

        $grandTotal = 0;
        $finalData = collect();

        // Group by vendor
        $vendors = $data->groupBy('vendor_name');

        foreach ($vendors as $vendor => $rows) {
            $vendorSubtotal = 0;

            // Vendor header
            $finalData->push((object) [
                'transaction_date' => '',
                'transaction_type' => '<span class="" data-bucket="' . \Str::slug($vendor) . '"> <span class="icon">▼</span> <strong>' . e($vendor) . '</strong></span>',
                'transaction' => '',
                'posting_status' => '',
                'memo' => '',
                'account_full_name' => '',
                'amount' => 0,
                'vendor_name' => $vendor,
                'isVendorHeader' => true,
            ]);

            foreach ($rows as $row) {
                $amount = (float) ($row->amount ?? 0);
                $vendorSubtotal += $amount;

                $finalData->push((object) [
                    'transaction_date' => $row->transaction_date,
                    'transaction_type' => $row->transaction_type,
                    'transaction' => match ($row->transaction_type) {
                        'Bill' => \Auth::user()->billNumberFormat($row->bill),
                        'Bill Payment' => \Auth::user()->paymentNumberFormat($row->transaction_id),
                        'Purchase' => \Auth::user()->purchaseNumberFormat($row->transaction_id),
                        default => $row->transaction_id,
                    },
                    'posting_status' => 'Y',
                    'memo' => $row->description,
                    'account_full_name' => $row->account_full_name,
                    'amount' => $amount,
                    'vendor_name' => $vendor,
                    'isDetail' => true,
                ]);
            }

            // Subtotal
            $finalData->push((object) [
                'transaction_date' => '',
                'transaction_type' => "<strong>Subtotal for {$vendor}</strong>",
                'transaction' => '',
                'posting_status' => '',
                'memo' => '',
                'account_full_name' => '',
                'amount' => $vendorSubtotal,
                'vendor_name' => $vendor,
                'isSubtotal' => true,
            ]);

            // Placeholder row
            $finalData->push((object) [
                'transaction_date' => '',
                'transaction_type' => '',
                'transaction' => '',
                'posting_status' => '',
                'memo' => '',
                'account_full_name' => '',
                'amount' => 0,
                'vendor_name' => $vendor,
                'isPlaceholder' => true,
            ]);

            $grandTotal += $vendorSubtotal;
        }

        // Grand total
        $finalData->push((object) [
            'transaction_date' => '',
            'transaction_type' => 'Grand Total',
            'transaction' => '',
            'posting_status' => '',
            'memo' => '',
            'account_full_name' => '',
            'amount' => $grandTotal,
            'vendor_name' => '',
            'isGrandTotal' => true,
        ]);

        return datatables()
            ->collection($finalData)
            ->editColumn('transaction_date', fn($row) => isset($row->isDetail) ? $row->transaction_date : '')
            ->editColumn('transaction', fn($row) => $row->transaction ?? '')
            ->editColumn('memo', fn($row) => isset($row->isDetail) ? $row->memo : '')
            ->editColumn('account_full_name', fn($row) => isset($row->isDetail) ? $row->account_full_name : '')
            ->editColumn('amount', function ($row) {
                if (isset($row->isVendorHeader) || isset($row->isPlaceholder))
                    return '';
                return number_format((float) $row->amount, 2);
            })
            ->setRowClass(function ($row) {
                $vendorSlug = $row->vendor_name ? \Str::slug($row->vendor_name) : 'no-vendor';

                if (isset($row->isVendorHeader))
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

    public function query()
    {
        $userId = \Auth::user()->creatorId();

        $start = request()->get('start_date') ?? request()->get('startDate') ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $end = request()->get('end_date') ?? request()->get('endDate') ?? Carbon::now()->endOfDay()->format('Y-m-d');

        // 1️⃣ Bills (Purchase Products)
        $bills = PurchaseProduct::query()
            ->select(
                'purchase_products.id',
                'purchases.purchase_id as bill',
                'purchases.purchase_id as transaction_id',
                'purchases.purchase_date as transaction_date',
                'venders.name as vendor_name',
                'purchase_products.description',
                'bank_accounts.bank_name as account_full_name',
                'purchase_products.price',
                'purchase_products.quantity',
                'purchase_products.discount',
                DB::raw('0 as tax_amount'),
                DB::raw('((purchase_products.price * purchase_products.quantity) - IFNULL(purchase_products.discount,0)) as amount'),
                DB::raw('"Bill" as transaction_type')
            )
            ->join('purchases', 'purchases.id', '=', 'purchase_products.purchase_id')
            ->join('venders', 'venders.id', '=', 'purchases.vender_id')
            ->leftJoin('purchase_payments', 'purchase_payments.purchase_id', '=', 'purchases.id')
            ->leftJoin('bank_accounts', 'bank_accounts.id', '=', 'purchase_payments.account_id')
            ->where('purchases.created_by', $userId)
            ->whereBetween('purchases.purchase_date', [$start, $end]);

        // 2️⃣ Purchases (header-level)
        $purchases = Purchase::query()
            ->select(
                'purchases.id',
                DB::raw('NULL as bill'),
                'purchases.id as transaction_id',
                'purchases.purchase_date as transaction_date',
                'venders.name as vendor_name',
                DB::raw('purchases.status as description'),
                DB::raw('NULL as account_full_name'),
                DB::raw('0 as price'),
                DB::raw('0 as quantity'),
                DB::raw('0 as discount'),
                DB::raw('0 as tax_amount'),
                DB::raw('0 as amount'),
                DB::raw('"Purchase" as transaction_type')
            )
            ->join('venders', 'venders.id', '=', 'purchases.vender_id')
            ->where('purchases.created_by', $userId)
            ->whereBetween('purchases.purchase_date', [$start, $end]);

        // 3️⃣ Bill Payments
        $billPayments = BillPayment::query()
            ->select(
                'bill_payments.id',
                DB::raw('bills.bill_id as bill'),
                'bill_payments.id as transaction_id',
                'bill_payments.date as transaction_date',
                'venders.name as vendor_name',
                'bill_payments.description',
                'bank_accounts.bank_name as account_full_name',
                DB::raw('0 as price'),
                DB::raw('0 as quantity'),
                DB::raw('0 as discount'),
                DB::raw('0 as tax_amount'),
                'bill_payments.amount',
                DB::raw('"Bill Payment" as transaction_type')
            )
            ->join('bills', 'bills.id', '=', 'bill_payments.bill_id')
            ->join('venders', 'venders.id', '=', 'bills.vender_id')
            ->leftJoin('bank_accounts', 'bank_accounts.id', '=', 'bill_payments.account_id')
            ->where('bills.created_by', $userId)
            ->whereBetween('bill_payments.date', [$start, $end]);

        // ✅ Union all (all 13 columns identical)
        $combined = $bills->unionAll($purchases)->unionAll($billPayments);

        return DB::query()->fromSub($combined, 'transactions');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('vendor-transaction-table')
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
            Column::make('transaction_type')->title('Transaction Type'),
            Column::make('transaction')->title('Transaction'),
            Column::make('posting_status')->title('Posting Y/N')->addClass('default-hidden'),
            Column::make('memo')->title('Memo / Description'),
            Column::make('account_full_name')->title('Account Full Name')->addClass('default-hidden'),
            Column::make('amount')->title('Amount'),
        ];
    }
}
