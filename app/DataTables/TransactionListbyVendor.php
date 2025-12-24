<?php

namespace App\DataTables;

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
                'transaction_date' => '<span class="toggle-bucket" data-bucket="' . \Str::slug($vendor) . '"><span class="icon">▼</span> <strong>' . e($vendor) . '</strong></span>',
                'transaction_type' => '',
                'transaction' => '',
                'posting_status' => '',
                'memo' => '',
                'account' => '',
                'open_balance' => '',
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
                    'transaction' => $this->formatTransaction($row),
                    'posting_status' => 'Y',
                    'memo' => $row->memo ?? '',
                    'account' => $row->account_name ?? '',
                    'open_balance' => number_format((float)($row->open_balance ?? 0), 2),
                    'amount' => $amount,
                    'vendor_name' => $vendor,
                    'isDetail' => true,
                ]);
            }

            // Subtotal
            $finalData->push((object) [
                'transaction_date' => "<strong>Subtotal for {$vendor}</strong>",
                'transaction_type' => '',
                'transaction' => '',
                'posting_status' => '',
                'memo' => '',
                'account' => '',
                'open_balance' => '',
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
                'account' => '',
                'open_balance' => '',
                'amount' => 0,
                'vendor_name' => $vendor,
                'isPlaceholder' => true,
            ]);

            $grandTotal += $vendorSubtotal;
        }

        // Grand total
        $finalData->push((object) [
            'transaction_date' => '<strong>Grand Total</strong>',
            'transaction_type' => '',
            'transaction' => '',
            'posting_status' => '',
            'memo' => '',
            'account' => '',
            'open_balance' => '',
            'amount' => $grandTotal,
            'vendor_name' => '',
            'isGrandTotal' => true,
        ]);

        return datatables()
            ->collection($finalData)
            ->editColumn('transaction_date', function ($row) {
                if (isset($row->isDetail)) {
                    return $row->transaction_date ? Carbon::parse($row->transaction_date)->format('M d, Y') : '';
                }
                return $row->transaction_date;
            })
            ->editColumn('transaction', fn($row) => $row->transaction ?? '')
            ->editColumn('memo', fn($row) => isset($row->isDetail) ? $row->memo : '')
            ->editColumn('account', fn($row) => isset($row->isDetail) ? $row->account : '')
            ->editColumn('amount', function ($row) {
                if (isset($row->isVendorHeader) || isset($row->isPlaceholder))
                    return '';
                return number_format((float) $row->amount, 2);
            })
            ->editColumn('open_balance', fn($row) => isset($row->isDetail) ? $row->open_balance : '')
            ->setRowClass(function ($row) {
                $vendorSlug = $row->vendor_name ? \Str::slug($row->vendor_name) : 'no-vendor';

                if (isset($row->isVendorHeader))
                    return 'parent-row toggle-bucket bucket-' . $vendorSlug;
                if (isset($row->isSubtotal) && !isset($row->isGrandTotal))
                    return 'subtotal-row bucket-' . $vendorSlug;
                if (isset($row->isGrandTotal))
                    return 'grandtotal-row';
                if (isset($row->isPlaceholder))
                    return 'placeholder-row bucket-' . $vendorSlug;
                return 'child-row bucket-' . $vendorSlug;
            })
            ->rawColumns(['transaction', 'transaction_date', 'transaction_type']);
    }

    protected function formatTransaction($row)
    {
        return match ($row->transaction_type) {
            'Bill' => \Auth::user()->billNumberFormat($row->transaction_number),
            'Bill Payment', 'Bill Payment (Check)' => \Auth::user()->paymentNumberFormat($row->transaction_number),
            'Purchase Order' => \Auth::user()->purchaseNumberFormat($row->transaction_number),
            default => \Auth::user()->billNumberFormat($row->transaction_number), // Use bill format for Expense, Check, etc.
        };
    }

    public function query()
    {
        $userId = \Auth::user()->creatorId();

        $start = request()->get('start_date') ?? request()->get('startDate') ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $end = request()->get('end_date') ?? request()->get('endDate') ?? Carbon::now()->endOfDay()->format('Y-m-d');

        // 1️⃣ Bills - showing actual bill type
        $bills = DB::table('bills')
            ->select(
                'bills.id',
                'bills.bill_date as transaction_date',
                'venders.name as vendor_name',
                'bills.bill_id as transaction_number',
                'bills.notes as memo',
                'bills.type as transaction_type', // Show actual type from bills table
                DB::raw('CASE 
                    WHEN bills.type = "Bill" 
                        THEN "Accounts Payable (A/P)"
                    ELSE (
                        SELECT coa.name
                        FROM bill_payments bp
                        JOIN bank_accounts ban ON ban.id = bp.account_id
                        JOIN chart_of_accounts coa ON coa.id = ban.chart_account_id
                        WHERE bp.bill_id = bills.id
                        LIMIT 1
                    )
                END as account_name'),
                // Bills type = "Bill" → POSITIVE (money owed)
                // Other types (Expense, Check, etc.) → NEGATIVE (money paid out)
                DB::raw('CASE 
                    WHEN bills.type = "Bill" THEN (
                        SELECT IFNULL(SUM(bp.price * bp.quantity - IFNULL(bp.discount, 0)), 0)
                        FROM bill_products bp
                        WHERE bp.bill_id = bills.id
                    ) + (
                        SELECT IFNULL(SUM(ba.price), 0)
                        FROM bill_accounts ba
                        WHERE ba.ref_id = bills.id
                    )
                    ELSE 
            (
                -- Subquery 1 & 2 repeated (calculating the total bill amount)
                (
                    SELECT IFNULL(SUM(bp.price * bp.quantity - IFNULL(bp.discount, 0)), 0)
                    FROM bill_products bp
                    WHERE bp.bill_id = bills.id
                ) + 
                (
                    SELECT IFNULL(SUM(ba.price), 0)
                    FROM bill_accounts ba
                    WHERE ba.ref_id = bills.id
                )
            ) 
            * CASE 
                -- Subquery 3: Check payment account type
                WHEN (
                    SELECT ba2.account_subtype
                    FROM bill_payments bp2
                    JOIN bank_accounts ba2 ON ba2.id = bp2.account_id
                    WHERE bp2.bill_id = bills.id
                    LIMIT 1 -- ⚠ This can be non-deterministic!
                ) = "credit_card"
                THEN 1   -- If Credit Card, it is an increase in Liability (Positive sign)
                ELSE -1  -- Otherwise (e.g., Bank/Cash), it is a decrease in Asset (Negative sign)
            END
                END AS amount'),
                    DB::raw('CASE 
                        WHEN bills.type = "Bill" THEN (
                            (
                                SELECT IFNULL(SUM(bp.price * bp.quantity - IFNULL(bp.discount, 0)), 0)
                                FROM bill_products bp
                                WHERE bp.bill_id = bills.id
                            ) + (
                                SELECT IFNULL(SUM(ba.price), 0)
                                FROM bill_accounts ba
                                WHERE ba.ref_id = bills.id
                            ) - (
                                SELECT IFNULL(SUM(bill_payments.amount), 0)
                                FROM bill_payments
                                WHERE bill_payments.bill_id = bills.id
                            )
                        )
                        ELSE 0
                    END as open_balance')
                )
            ->join('venders', 'venders.id', '=', 'bills.vender_id')
            ->where('bills.created_by', $userId)
            ->whereRaw('LOWER(bills.user_type) = ?', ['vendor'])
            ->whereBetween('bills.bill_date', [$start, $end]);

        // 2️⃣ Purchase Orders that have bills (txn_id) - POSITIVE
        $purchaseOrders = DB::table('purchases')
            ->select(
                'purchases.id',
                'purchases.purchase_date as transaction_date',
                'venders.name as vendor_name',
                'purchases.purchase_id as transaction_number',
                'purchases.notes as memo',
                DB::raw('"Purchase Order" as transaction_type'),
                DB::raw('"Accounts Payable (A/P)" as account_name'),
                // Purchase Orders are POSITIVE (amount owed)
                DB::raw('(
                    SELECT IFNULL(SUM(pp.price * pp.quantity - IFNULL(pp.discount, 0)), 0)
                    FROM purchase_products pp
                    WHERE pp.purchase_id = purchases.id
                ) + (
                    SELECT IFNULL(SUM(poa.price), 0)
                    FROM purchase_order_accounts poa
                    WHERE poa.ref_id = purchases.id
                ) as amount'),
                DB::raw('0 as open_balance')
            )
            ->join('venders', 'venders.id', '=', 'purchases.vender_id')
            ->where('purchases.created_by', $userId)
            // ->whereNotNull('purchases.txn_id') // Only POs that have been converted to bills
            ->whereBetween('purchases.purchase_date', [$start, $end]);

        // 3️⃣ Bill Payments - credit card = positive, bank/cash = negative
        // $billPayments = DB::table('bill_payments')
        // ->select(
        //     DB::raw('NULL as id'), // bills.id compatible
        //     DB::raw('MIN(bill_payments.date) as transaction_date'),
        //     'venders.name as vendor_name',
            
        //     // Important: transaction_number should match data type of others
        //     DB::raw('GROUP_CONCAT(bill_payments.id) as transaction_number'),
            
        //     DB::raw('bill_payments.reference as memo'),

        //     DB::raw('CASE 
        //         WHEN bank_accounts.account_subtype = "credit_card" 
        //             THEN "Bill Payment (Credit Card)"
        //         ELSE "Bill Payment"
        //     END as transaction_type'),

        //     'bank_accounts.bank_name as account_name',

        //     // Amount (matching column name of other queries)
        //     DB::raw('SUM(
        //         CASE 
        //             WHEN bank_accounts.account_subtype = "credit_card" 
        //                 THEN bill_payments.amount
        //             ELSE -1 * bill_payments.amount
        //         END
        //     ) as amount'),

        //     DB::raw('0 as open_balance')
        // )
        // ->join('bills', 'bills.id', '=', 'bill_payments.bill_id')
        // ->join('venders', 'venders.id', '=', 'bills.vender_id')
        // ->leftJoin('bank_accounts', 'bank_accounts.id', '=', 'bill_payments.account_id')
        // ->where('bills.created_by', $userId)
        // ->where('bills.type', 'Bill')
        // ->whereBetween('bill_payments.date', [$start, $end])
        // ->groupBy(
        //     'venders.id',
        //     'bill_payments.reference',
        //     'transaction_type',
        //     'bank_accounts.bank_name'
        // );
        $billPayments = DB::table('bill_payments')
            ->select(
                DB::raw('NULL as id'),
                DB::raw('MIN(bill_payments.date) as transaction_date'),
                'venders.name as vendor_name',
                DB::raw('GROUP_CONCAT(bill_payments.id) as transaction_number'),
                DB::raw('bill_payments.reference as memo'),

                DB::raw('CASE 
                    WHEN bank_accounts.account_subtype = "credit_card" 
                        THEN "Bill Payment (Credit Card)"
                    ELSE "Bill Payment"
                END as transaction_type'),

                'bank_accounts.bank_name as account_name',

                // ⭐ FINAL FIX: DO NOT subtract credit, add AND sign negative
                DB::raw('
                    -(
                        COALESCE(SUM(ABS(bill_payments.amount)), 0)
                        +
                        COALESCE(SUM(
                            CASE 
                                WHEN trans.category LIKE "%vendor credit%" 
                                    THEN ABS(trans.amount)
                                ELSE 0
                            END
                        ), 0)
                    ) as amount
                '),

                DB::raw('0 as open_balance')
            )
            ->join('bills', 'bills.id', '=', 'bill_payments.bill_id')
            ->join('venders', 'venders.id', '=', 'bills.vender_id')
            ->leftJoin('bank_accounts', 'bank_accounts.id', '=', 'bill_payments.account_id')
            ->leftJoin('transactions as trans', function ($join) {
                $join->on('trans.payment_id', '=', 'bill_payments.id')
                    ->where('trans.category', 'like', '%vendor credit%');
            })
            ->where('bills.created_by', $userId)
            ->where('bills.type', 'Bill')

            ->whereRaw('LOWER(bills.user_type) = ?', ['vendor'])
            ->whereBetween('bill_payments.date', [$start, $end])
            ->groupBy(
                'venders.id',
                'bill_payments.reference',
                'transaction_type',
                'bank_accounts.bank_name'
            );

         // 4️⃣ Vendor Credits - NEGATIVE (decreases amount owed)
        $vendorCredits = DB::table('vendor_credits')
            ->select(
                'vendor_credits.id',
                'vendor_credits.date as transaction_date',
                'venders.name as vendor_name',
                'vendor_credits.vendor_credit_id as transaction_number', // Using ID as txn number
                'vendor_credits.memo as memo',
                DB::raw('"Vendor Credit" as transaction_type'),
                DB::raw('"Accounts Payable (A/P)" as account_name'),
                
                // Calculate Total: (Products + Accounts) * -1
                DB::raw('-1 * (
                    (
                        SELECT IFNULL(SUM(vcp.price * vcp.quantity), 0)
                        FROM vendor_credit_products vcp
                        WHERE vcp.vendor_credit_id = vendor_credits.id
                    ) + (
                        SELECT IFNULL(SUM(vca.price), 0)
                        FROM vendor_credit_accounts vca
                        WHERE vca.vendor_credit_id = vendor_credits.id
                    )
                ) as amount'),

                // Open Balance for Credit (usually negative or 0)
                DB::raw('-1 * (
                    (
                        SELECT IFNULL(SUM(vcp.price * vcp.quantity), 0)
                        FROM vendor_credit_products vcp
                        WHERE vcp.vendor_credit_id = vendor_credits.id
                    ) + (
                        SELECT IFNULL(SUM(vca.price), 0)
                        FROM vendor_credit_accounts vca
                        WHERE vca.vendor_credit_id = vendor_credits.id
                    )
                ) as open_balance')
            )
            ->join('venders', 'venders.id', '=', 'vendor_credits.vender_id')
            ->where('vendor_credits.created_by', $userId)
            ->whereBetween('vendor_credits.date', [$start, $end]);


             // EXCLUDE payments that are already counted as vendor credits in the transactions table
            $unappliedPayments = DB::table('unapplied_payments')
            ->selectRaw("
                NULL as id,
                unapplied_payments.txn_date as transaction_date,
                venders.name as vendor_name,
                unapplied_payments.reference as transaction_number,
                unapplied_payments.reference as memo,
                'Unapplied Payment' as transaction_type,
                bank_accounts.bank_name as account_name,
                 -ABS(unapplied_payments.unapplied_amount) as amount,
                 -ABS(unapplied_payments.unapplied_amount) as open_balance
            ")
            ->leftJoin('venders', 'venders.id', '=', 'unapplied_payments.vendor_id')
            ->leftJoin('bank_accounts', 'bank_accounts.id', '=', 'unapplied_payments.account_id')
            ->where('unapplied_payments.created_by', $userId)
            ->whereBetween('unapplied_payments.txn_date', [$start, $end])
            ->where('unapplied_payments.unapplied_amount', '>', 0)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('transactions')
                      ->whereRaw('transactions.payment_no = unapplied_payments.reference')
                      ->whereRaw('LOWER(transactions.category) LIKE \'%vendor credit%\'');
            });
                    
                // ✅ Union all
                
                $combined = $bills->unionAll($purchaseOrders)->unionAll($billPayments)->unionAll($vendorCredits)->unionAll($unappliedPayments);

                return DB::query()->fromSub($combined, 'transactions')
                    ->orderBy('vendor_name', 'asc')
                    ->orderBy('transaction_date', 'asc');
        }


    public function html()
    {
        return $this->builder()
            ->setTableId('vendor-transaction-table')
            ->columns($this->getColumns())
              ->ajax([    
                'url' => route('expenses.transaction_list_by_vendor'),
                'type' => 'GET',
                'headers' => [
                    'X-CSRF-TOKEN' => csrf_token(),
                ],
            ])
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
            Column::make('posting_status')->title('Posting')->addClass('default-hidden'),
            Column::make('memo')->title('Memo/Description'),
            Column::make('account')->title('Account'),
            Column::make('open_balance')->title('Open Balance')->addClass('default-hidden'),
            Column::make('amount')->title('Amount'),
        ];
    }
}