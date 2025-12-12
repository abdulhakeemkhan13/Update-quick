<?php

namespace App\DataTables;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class VendorBalanceDetail extends DataTable
{
    public function dataTable($query)
    {
        $data = collect($query->get());

        $grandTotalAmount = 0;
        $grandTotalOpenBalance = 0;
        $finalData = collect();

        // Group by vendor
        $vendors = $data->groupBy('vendor_name');

        foreach ($vendors as $vendor => $rows) {
            $vendorSubtotalAmount = 0;
            $vendorSubtotalOpenBalance = 0;
            $runningBalance = 0;
            $transactionCount = $rows->count();
            $vendorDisplay = $vendor ?: 'Unknown Vendor';

            // Calculate vendor total first to check if should be displayed
            foreach ($rows as $row) {
                $vendorSubtotalOpenBalance += (float) ($row->open_balance ?? 0);
            }

            // Skip vendors with zero total open balance (like QuickBooks)
            if (abs($vendorSubtotalOpenBalance) < 0.01) {
                continue;
            }

            // Reset for actual processing
            $vendorSubtotalOpenBalance = 0;

            // Vendor header row
            $finalData->push((object) [
                'transaction_date' => '<span class="toggle-bucket" data-bucket="' . \Str::slug($vendorDisplay) . '"><span class="icon">▼</span> <strong>' . e($vendorDisplay) . ' (' . $transactionCount . ')</strong></span>',
                'transaction_type' => '',
                'num' => '',
                'due_date' => '',
                'amount' => '',
                'open_balance' => '',
                'balance' => '',
                'vendor_name' => $vendor,
                'isVendorHeader' => true,
            ]);

            foreach ($rows as $row) {
                $amount = (float) ($row->amount ?? 0);
                $openBalance = (float) ($row->open_balance ?? 0);
                
                // Running balance uses Amount (like QuickBooks Balance column)
                $runningBalance += $amount;
                
                $vendorSubtotalAmount += $amount;
                $vendorSubtotalOpenBalance += $openBalance;

                $finalData->push((object) [
                    'transaction_date' => $row->transaction_date,
                    'transaction_type' => $row->transaction_type,
                    'num' => $row->transaction_number ?? '',
                    'due_date' => $row->due_date ?? '',
                    'amount' => $amount,
                    'open_balance' => $openBalance,
                    'balance' => $runningBalance,
                    'vendor_name' => $vendor,
                    'isDetail' => true,
                ]);
            }

            // Vendor subtotal
            $finalData->push((object) [
                'transaction_date' => "<strong>Total for {$vendorDisplay}</strong>",
                'transaction_type' => '',
                'num' => '',
                'due_date' => '',
                'amount' => $vendorSubtotalAmount,
                'open_balance' => $vendorSubtotalOpenBalance,
                'balance' => '',
                'vendor_name' => $vendor,
                'isSubtotal' => true,
            ]);

            // Placeholder row
            $finalData->push((object) [
                'transaction_date' => '',
                'transaction_type' => '',
                'num' => '',
                'due_date' => '',
                'amount' => '',
                'open_balance' => '',
                'balance' => '',
                'vendor_name' => $vendor,
                'isPlaceholder' => true,
            ]);

            $grandTotalAmount += $vendorSubtotalAmount;
            $grandTotalOpenBalance += $vendorSubtotalOpenBalance;
        }

        // Grand total row
        $finalData->push((object) [
            'transaction_date' => '<strong>TOTAL</strong>',
            'transaction_type' => '',
            'num' => '',
            'due_date' => '',
            'amount' => $grandTotalAmount,
            'open_balance' => $grandTotalOpenBalance,
            'balance' => '',
            'isGrandTotal' => true,
        ]);

        return datatables()
            ->collection($finalData)
            ->editColumn('transaction_date', function ($row) {
                if (isset($row->isDetail)) {
                    return $row->transaction_date ? Carbon::parse($row->transaction_date)->format('m/d/Y') : '';
                }
                return $row->transaction_date;
            })
            ->editColumn('due_date', function ($row) {
                if (isset($row->isDetail) && $row->due_date) {
                    return Carbon::parse($row->due_date)->format('m/d/Y');
                }
                return '';
            })
            ->editColumn('amount', function ($row) {
                if (isset($row->isVendorHeader) || isset($row->isPlaceholder)) return '';
                return number_format((float) $row->amount, 2);
            })
            ->editColumn('open_balance', function ($row) {
                if (isset($row->isVendorHeader) || isset($row->isPlaceholder)) return '';
                return number_format((float) $row->open_balance, 2);
            })
            ->editColumn('balance', function ($row) {
                if (!isset($row->isDetail)) return '';
                return number_format((float) $row->balance, 2);
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

public function query()
    {
        $userId = \Auth::user()->creatorId();
        $start = request()->get('start_date') ?? request()->get('startDate') ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $end   = request()->get('end_date') ?? request()->get('endDate') ?? Carbon::now()->endOfDay()->format('Y-m-d');

        // Reuse this SQL logic to determine if a bill is "Open" (Not fully paid)
        // (Bill Total) - (Payments) - (Debit Notes)
        // FIX: Use COALESCE with bills.total for Check/Expense types
        $billOpenBalanceLogic = '
            (
                COALESCE(
                    NULLIF(
                        (SELECT IFNULL(SUM(bp.price * bp.quantity - IFNULL(bp.discount,0)),0) FROM bill_products bp WHERE bp.bill_id = bills.id)
                        +
                        (SELECT IFNULL(SUM(ba.price),0) FROM bill_accounts ba WHERE ba.ref_id = bills.id),
                        0
                    ),
                    bills.total
                )
                -
                (SELECT IFNULL(SUM(amount),0) FROM bill_payments WHERE bill_payments.bill_id = bills.id)
                -
                (SELECT IFNULL(SUM(debit_notes.amount),0) FROM debit_notes WHERE debit_notes.bill = bills.id)
            )
        ';

        /* ---------------------------------------------------------
         | 1. BILLS  (Only fetch if Open Balance is NOT 0)
         --------------------------------------------------------- */
        $bills = DB::table('bills')
            ->select(
                DB::raw('bills.id as transaction_id'),
                'bills.bill_date as transaction_date',
                'bills.due_date',
                'venders.name as vendor_name',
                'bills.bill_id as transaction_number',
                DB::raw('"Bill" as transaction_type'),

                // Total amount - FIX: Use COALESCE with bills.total for Check/Expense types
                DB::raw('COALESCE(
                    NULLIF(
                        (SELECT IFNULL(SUM(bp.price * bp.quantity - IFNULL(bp.discount,0)),0) FROM bill_products bp WHERE bp.bill_id = bills.id)
                        +
                        (SELECT IFNULL(SUM(ba.price),0) FROM bill_accounts ba WHERE ba.ref_id = bills.id),
                        0
                    ),
                    bills.total
                ) as amount'),

                // Open balance logic
                DB::raw("($billOpenBalanceLogic) as open_balance")
            )
            ->join('venders', 'venders.id', '=', 'bills.vender_id')
            ->whereRaw('LOWER(bills.type) IN (?, ?, ?)', ['bill', 'check', 'expense'])
            ->where('bills.created_by', $userId)
            ->where('bills.bill_date','<=', $end)
            // ->where('bills.status', '!=','4')
            // FILTER: Only show bills that have an open balance (positive or negative, but not 0)
            ->havingRaw("ABS(open_balance) > 0");


        /* ---------------------------------------------------------
         | 2. VENDOR CREDITS (Included as requested)
         --------------------------------------------------------- */
        $vendorCredits = DB::table('vendor_credits')
            ->select(
                DB::raw('vendor_credits.id as transaction_id'),
                'vendor_credits.date as transaction_date',
                DB::raw('NULL as due_date'),
                'venders.name as vendor_name',
                DB::raw('vendor_credits.vendor_credit_id as transaction_number'),
                DB::raw('"Vendor Credit" as transaction_type'),

                // Always NEGATIVE
                DB::raw('
                -1 * (
                    IFNULL((SELECT SUM(vcp.price * vcp.quantity)
                        FROM vendor_credit_products vcp
                        WHERE vcp.vendor_credit_id = vendor_credits.id),0)
                    +
                    IFNULL((SELECT SUM(vca.price)
                        FROM vendor_credit_accounts vca
                        WHERE vca.vendor_credit_id = vendor_credits.id),0)
                ) as amount
            '),

                // Open balance same as amount
                DB::raw('
                -1 * (
                    IFNULL((SELECT SUM(vcp.price * vcp.quantity)
                        FROM vendor_credit_products vcp
                        WHERE vcp.vendor_credit_id = vendor_credits.id),0)
                    +
                    IFNULL((SELECT SUM(vca.price)
                        FROM vendor_credit_accounts vca
                        WHERE vca.vendor_credit_id = vendor_credits.id),0)
                ) as open_balance
            ')
            )
            ->join('venders', 'venders.id', '=', 'vendor_credits.vender_id')
            ->where('vendor_credits.created_by', $userId)
            ->where('vendor_credits.date', '<=', $end);


        /* ---------------------------------------------------------
         | 3. BILL PAYMENTS (Only fetch if the linked Bill is still Open)
         --------------------------------------------------------- */
        $billPayments = DB::table('bill_payments')
            ->select(
                DB::raw('bill_payments.id as transaction_id'),
                'bill_payments.date as transaction_date',
                'bill_payments.date as due_date',
                'venders.name as vendor_name',
                'bill_payments.reference as transaction_number',
                DB::raw('CASE 
                WHEN bank_accounts.account_subtype = "credit_card" THEN "Bill Payment (Credit Card)"
                ELSE "Bill Payment (Check)"
            END as transaction_type'),
                DB::raw('-1 * bill_payments.amount as amount'),
                DB::raw('-1 * bill_payments.amount as open_balance')
            )
            ->join('bills', 'bills.id', '=', 'bill_payments.bill_id')
            ->join('venders', 'venders.id', '=', 'bills.vender_id')
            ->leftJoin('bank_accounts', 'bank_accounts.id', '=', 'bill_payments.account_id')
            ->where('bills.created_by', $userId)
            ->where('bills.type', 'Bill')
            // ->where('bills.status', '!=','4')
            ->where('bill_payments.date', '<=', $end)
            // FILTER: Only show payments if the PARENT BILL still has an open balance.
            // If the bill is fully paid, we hide the bill AND its payments.
            ->whereRaw("ABS($billOpenBalanceLogic) > 0");


        /* ---------------------------------------------------------
         | 4. UNAPPLIED PAYMENTS (not linked to any bill yet)
         --------------------------------------------------------- */
        $unappliedPayments = DB::table('unapplied_payments')
            ->select(
                DB::raw('unapplied_payments.id as transaction_id'),
                'unapplied_payments.txn_date as transaction_date',
                DB::raw('NULL as due_date'),
                'venders.name as vendor_name',
                'unapplied_payments.reference as transaction_number',
                DB::raw('"Unapplied Payment" as transaction_type'),
                DB::raw('-1 * unapplied_payments.unapplied_amount as amount'),
                DB::raw('-1 * unapplied_payments.unapplied_amount as open_balance')
            )
            ->join('venders', 'venders.id', '=', 'unapplied_payments.vendor_id')
            ->where('unapplied_payments.created_by', $userId)
            ->where('unapplied_payments.unapplied_amount', '>', 0);


        /* ---------------------------------------------------------
         | COMBINE ALL
         --------------------------------------------------------- */
        $combined = $bills
            ->unionAll($vendorCredits)
            ->unionAll($billPayments)
            ->unionAll($unappliedPayments);

        return DB::query()->fromSub($combined, 'transactions')
            ->orderBy('vendor_name', 'asc')
            ->orderBy('transaction_date', 'asc');
    }


    public function html()
    {
        return $this->builder()
            ->setTableId('vendor-balance-detail-table')
            ->columns($this->getColumns())
            ->ajax([
                'url' => route('payables.vendor_balance_detail'),
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
                'dom' => 't',
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('transaction_date')->title('Date'),
            Column::make('transaction_type')->title('Transaction type'),
            Column::make('num')->title('Num'),
            Column::make('due_date')->title('Due date'),
            Column::make('amount')->title('Amount')->addClass('text-right'),
            Column::make('open_balance')->title('Open balance')->addClass('text-right'),
            Column::make('balance')->title('Balance')->addClass('text-right'),
        ];
    }
}