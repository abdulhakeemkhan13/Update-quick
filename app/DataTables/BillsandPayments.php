<?php

namespace App\DataTables;

use App\Models\Bill;
use Carbon\Carbon;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\DB;

class BillsandPayments extends DataTable
{
    public function dataTable($query)
    {
        $data = collect($query->get());

        $grandTotalAmount = 0;
        $grandOpenBalance = 0;
        $finalData = collect();

        // Group transactions by vendor name
        $vendors = $data->groupBy('vendor_name');

        foreach ($vendors as $vendor => $rows) {
            $subtotalAmount = 0;
            $subtotalOpen = 0;
            $vendorDisplay = $vendor ?: 'Unknown Vendor';
            $transactionCount = $rows->count();

            // Vendor header row
            $finalData->push((object) [
                'transaction_date' => '<span class="toggle-bucket" data-bucket="' . \Str::slug($vendorDisplay) . '"><span class="icon">▼</span> <strong>' . e($vendorDisplay) . ' (' . $transactionCount . ')</strong></span>',
                'transaction_type' => '',
                'num' => '',
                'amount' => '',
                'open_balance' => '',
                'vendor_name' => $vendor,
                'isVendorHeader' => true,
            ]);

            // QuickBooks-style sorting: First sort by bill ID (ids), then by transaction type
            // This ensures: Bills appear first, then their related payments
            $sortedRows = $rows->sortBy([
                ['ids', 'desc'],  // Group by bill ID
                ['transaction_type', 'asc'],  // Bill comes before "Bill Payment" alphabetically
                ['transaction_date', 'asc'],  // Then by date
            ]);

            foreach ($sortedRows as $row) {
                $amount = (float) ($row->amount ?? 0);
                $openBalance = (float) ($row->open_balance ?? 0);

                $subtotalAmount += $amount;
                $subtotalOpen += $openBalance;

                $finalData->push((object) [
                    'transaction_date' => $row->transaction_date,
                    'transaction_type' => $row->transaction_type,
                    'num' => $row->num ?? '',
                    'amount' => $amount,
                    'open_balance' => $openBalance,
                    'vendor_name' => $vendor,
                    'isDetail' => true,
                ]);
            }

            // Vendor subtotal row
            // $finalData->push((object) [
            //     'transaction_date' => "<strong>Total for {$vendorDisplay}</strong>",
            //     'transaction_type' => '',
            //     'num' => '',
            //     'amount' => $subtotalAmount,
            //     'open_balance' => $subtotalOpen,
            //     'vendor_name' => $vendor,
            //     'isSubtotal' => true,
            // ]);

            // // Placeholder row for spacing
            // $finalData->push((object) [
            //     'transaction_date' => '',
            //     'transaction_type' => '',
            //     'num' => '',
            //     'amount' => '',
            //     'open_balance' => '',
            //     'vendor_name' => $vendor,
            //     'isPlaceholder' => true,
            // ]);

            // $grandTotalAmount += $subtotalAmount;
            // $grandOpenBalance += $subtotalOpen;
        }

        // Grand total row
        // $finalData->push((object) [
        //     'transaction_date' => '<strong>TOTAL</strong>',
        //     'transaction_type' => '',
        //     'num' => '',
        //     'amount' => $grandTotalAmount,
        //     'open_balance' => $grandOpenBalance,
        //     'isGrandTotal' => true,
        // ]);

        return datatables()
            ->collection($finalData)
            ->editColumn('transaction_date', function ($row) {
                if (isset($row->isDetail)) {
                    return $row->transaction_date ? Carbon::parse($row->transaction_date)->format('m/d/Y') : '';
                }
                return $row->transaction_date;
            })
            ->editColumn('amount', function ($row) {
                if (isset($row->isVendorHeader) || isset($row->isPlaceholder)) return '';
                return number_format((float) $row->amount, 2);
            })
            ->editColumn('open_balance', function ($row) {
                if (isset($row->isVendorHeader) || isset($row->isPlaceholder)) return '';
                return number_format((float) $row->open_balance, 2);
            })
            ->setRowClass(function ($row) {
                $vendorSlug = isset($row->vendor_name) ? \Str::slug($row->vendor_name) : 'no-vendor';
                if (isset($row->isVendorHeader)) return 'parent-row toggle-bucket bucket-' . $vendorSlug;
                if (isset($row->isSubtotal)) return 'subtotal-row bucket-' . $vendorSlug;
                if (isset($row->isGrandTotal)) return 'grandtotal-row';
                if (isset($row->isPlaceholder)) return 'placeholder-row bucket-' . $vendorSlug;
                return 'child-row bucket-' . $vendorSlug;
            })
            ->rawColumns(['transaction_date']);
    }

    public function query(Bill $model)
    {
        $userId = \Auth::user()->creatorId();
        $start = request()->get('start_date') ?? request()->get('startDate') ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $end = request()->get('end_date') ?? request()->get('endDate') ?? Carbon::now()->endOfDay()->format('Y-m-d');

        // 1. BILLS - Positive Amount
        // Show bills if: bill date is in range OR if any payment for this bill is in range
        $bills = DB::table('bills')
            ->select(
                'bills.bill_date as transaction_date',
                DB::raw('"Bill" as transaction_type'),
                'bills.bill_id as num',
                'bills.id as ids',
                'venders.name as vendor_name',
                // Total Bill Amount (products + accounts + tax)
                DB::raw('(
                    (SELECT IFNULL(SUM(bp.price * bp.quantity - IFNULL(bp.discount, 0)), 0) FROM bill_products bp WHERE bp.bill_id = bills.id)
                    + (SELECT IFNULL(SUM(ba.price), 0) FROM bill_accounts ba WHERE ba.ref_id = bills.id)
                    + (SELECT IFNULL(SUM((bp2.price * bp2.quantity - IFNULL(bp2.discount, 0)) * (t.rate / 100)), 0) 
                       FROM bill_products bp2 LEFT JOIN taxes t ON FIND_IN_SET(t.id, bp2.tax) > 0 WHERE bp2.bill_id = bills.id)
                ) as amount'),
                // Open Balance = Total - Payments - Credits
                DB::raw('(
                    (
                        (SELECT IFNULL(SUM(bp.price * bp.quantity - IFNULL(bp.discount, 0)), 0) FROM bill_products bp WHERE bp.bill_id = bills.id)
                        + (SELECT IFNULL(SUM(ba.price), 0) FROM bill_accounts ba WHERE ba.ref_id = bills.id)
                        + (SELECT IFNULL(SUM((bp2.price * bp2.quantity - IFNULL(bp2.discount, 0)) * (t.rate / 100)), 0) 
                           FROM bill_products bp2 LEFT JOIN taxes t ON FIND_IN_SET(t.id, bp2.tax) > 0 WHERE bp2.bill_id = bills.id)
                    ) - (
                        SELECT IFNULL(SUM(amount), 0) FROM bill_payments WHERE bill_payments.bill_id = bills.id
                    ) - (
                        SELECT IFNULL(SUM(debit_notes.amount), 0) FROM debit_notes WHERE debit_notes.bill = bills.id
                    )
                ) as open_balance')
            )
            ->join('venders', 'venders.id', '=', 'bills.vender_id')
            ->where('bills.created_by', $userId)
            ->where('bills.type', 'Bill')
            ->where('bills.status','4')
            ->whereRaw('LOWER(bills.user_type) = ?', ['vendor'])
            // Show bill if: bill date is in range OR has a payment in range
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('bills.bill_date', [$start, $end])
                    ->orWhereExists(function ($subquery) use ($start, $end) {
                        $subquery->select(DB::raw(1))
                            ->from('bill_payments')
                            ->whereRaw('bill_payments.bill_id = bills.id')
                            ->whereBetween('bill_payments.date', [$start, $end]);
                    });
            })
            // Skip bills with zero amount
            ->havingRaw('amount != 0');

        // 2. BILL PAYMENTS - Negative Amount (grouped by reference)
        // open_balance shows vendor credit amount applied to this payment
        $billPayments = DB::table('bill_payments')
            ->select(
                DB::raw('MIN(bill_payments.date) as transaction_date'),
                DB::raw('CASE 
                    WHEN bank_accounts.account_subtype = "credit_card" THEN "Bill Payment (Credit Card)"
                    ELSE "Bill Payment (Check)"
                END as transaction_type'),
                'bill_payments.reference as num',
                DB::raw('MIN(bill_payments.bill_id) as ids'),
                DB::raw('MAX(venders.name) as vendor_name'),
                // Sum of all payments with same reference + vendor credits
                DB::raw('-1 * (
                    SUM(bill_payments.amount) 
                    + IFNULL((
                        SELECT SUM(ABS(t.amount)) 
                        FROM transactions t 
                        JOIN bill_payments bp2 ON bp2.id = t.payment_id
                        WHERE bp2.reference = bill_payments.reference
                        AND LOWER(t.category) LIKE "%vendor credit%"
                    ), 0)
                ) as amount'),
                // Open balance = vendor credit amount from transactions table
                DB::raw('IFNULL((
                    SELECT SUM(ABS(t.amount)) 
                    FROM transactions t 
                    JOIN bill_payments bp2 ON bp2.id = t.payment_id
                    WHERE bp2.reference = bill_payments.reference
                    AND LOWER(t.category) LIKE "%vendor credit%"
                ), 0) as open_balance')
            )
            ->join('bills', 'bills.id', '=', 'bill_payments.bill_id')
            ->join('venders', 'venders.id', '=', 'bills.vender_id')
            ->leftJoin('bank_accounts', 'bank_accounts.id', '=', 'bill_payments.account_id')
            ->where('bills.created_by', $userId)
            ->where('bills.type', 'Bill')
            ->whereRaw('LOWER(bills.user_type) = ?', ['vendor'])
            ->whereBetween('bill_payments.date', [$start, $end])
            ->groupBy(
                'bill_payments.reference',
                'bank_accounts.account_subtype'
            );

        // 3. VENDOR CREDITS - Negative Amount
        $vendorCredits = DB::table('vendor_credits')
            ->select(
                'vendor_credits.date as transaction_date',
                DB::raw('"Vendor Credit" as transaction_type'),
                'vendor_credits.vendor_credit_id as num',
                'vendor_credits.id as ids',
                'venders.name as vendor_name',
                DB::raw('-1 * (
                    (SELECT IFNULL(SUM(vcp.price * vcp.quantity), 0) FROM vendor_credit_products vcp WHERE vcp.vendor_credit_id = vendor_credits.id)
                    + (SELECT IFNULL(SUM(vca.price), 0) FROM vendor_credit_accounts vca WHERE vca.vendor_credit_id = vendor_credits.id)
                ) as amount'),
                DB::raw('-1 * (
                    (SELECT IFNULL(SUM(vcp.price * vcp.quantity), 0) FROM vendor_credit_products vcp WHERE vcp.vendor_credit_id = vendor_credits.id)
                    + (SELECT IFNULL(SUM(vca.price), 0) FROM vendor_credit_accounts vca WHERE vca.vendor_credit_id = vendor_credits.id)
                ) as open_balance')
            )
            ->join('venders', 'venders.id', '=', 'vendor_credits.vender_id')
            ->where('vendor_credits.created_by', $userId)
            ->whereBetween('vendor_credits.date', [$start, $end]);

        // Combine all
        $combined = $bills->unionAll($billPayments)->unionAll($vendorCredits);

        return DB::query()->fromSub($combined, 'transactions')
            ->orderBy('num', 'asc')
            ->orderBy('transaction_date', 'asc');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('bills-and-payments-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0, 'asc')
            ->parameters([
                'paging' => false,
                'searching' => false,
                'info' => false,
                'ordering' => false,
                'dom' => 't',
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('transaction_date')->title('Date'),
            Column::make('transaction_type')->title('Transaction Type'),
            Column::make('num')->title('Num'),
            Column::make('amount')->title('Amount')->addClass('text-right'),
            Column::make('open_balance')->title('Open Balance')->addClass('text-right'),
        ];
    }
}