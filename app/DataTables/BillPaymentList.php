<?php

namespace App\DataTables;

use App\Models\Bill;
use Carbon\Carbon;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\DB;

class BillPaymentList extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        $data = collect($query->get());

        $grandTotalAmount = 0;

        // ✅ Group by bank account name
        $groupedData = $data->groupBy(function ($row) {
            return $row->bank_name ?? 'Unknown Bank';
        });

        $finalData = collect();

        foreach ($groupedData as $bank => $rows) {
            // Skip empty or zero groups entirely
            if ($rows->count() == 0) {
                continue;
            }

            // Compute subtotal using the already correctly-signed 'signed_amount' field
            // The logic for sign application is now inside the editColumn function, 
            // but we need to sum the raw 'total_amount' for an accurate final total.
            // *Correction*: We must calculate the signed sum for the subtotal/grand total *here*
            $subtotalAmount = $rows->sum(function($row) {
                 // Apply the sign logic for subtotal calculation
                 $isCreditCard = strtolower($row->account_subtype ?? '') === 'credit_card';
                 $amount = (float)($row->total_amount ?? 0);
                 return $isCreditCard ? $amount : -$amount;
            });

            // If subtotal is 0 (no actual payments), skip this bank group
            if ($subtotalAmount == 0) {
                // If you want to skip zero subtotals, keep this block. Otherwise, remove it.
                // For a Bank Register, zero totals should usually be shown.
                // For this scenario, we will keep showing it if payments exist, just to avoid empty rows.
            }

            // ✅ Header row for this bank
            $finalData->push((object) [
                'bank_name' => $bank,
                'vendor' => '',
                'id' => null,
                'bill_date' => '',
                'transaction' => '<span class="" data-bucket="' . \Str::slug($bank) . '"> <span class="icon">▼</span> <strong>' . $bank . ' (' . $rows->count() . ')</strong></span>',
                'total_amount' => null,
                'isPlaceholder' => true,
                'isSubtotal' => false,
                'isParent' => true
            ]);

            foreach ($rows as $row) {
                $row->bank_name = $bank;
                $finalData->push($row);
            }

            // ✅ Subtotal row
            $finalData->push((object) [
                'bank_name' => $bank,
                'vendor' => '',
                'id' => null,
                'bill_date' => '',
                'transaction' => '<strong>Subtotal for ' . $bank . '</strong>',
                // Use the calculated signed subtotal
                'total_amount' => $subtotalAmount,
                'isSubtotal' => true,
            ]);

            // Empty placeholder row for spacing
            $finalData->push((object) [
                'bank_name' => $bank,
                'vendor' => '',
                'id' => null,
                'bill_date' => '',
                'transaction' => '',
                'total_amount' => '',
                'isPlaceholder' => true,
            ]);

            $grandTotalAmount += $subtotalAmount;
        }


        // ✅ Grand total row
        $finalData->push((object) [
            'bank_name' => '',
            'vendor' => '',
            'id' => null,
            'bill_date' => '',
            'transaction' => '<strong>Grand Total</strong>',
            'total_amount' => $grandTotalAmount,
            'isGrandTotal' => true,
        ]);

        return datatables()
            ->collection($finalData)
            ->addColumn('bill_date', fn($row) => isset($row->isSubtotal) || isset($row->isGrandTotal) ? '' : $row->bill_date)
            ->addColumn('transaction', function ($row) {
                if (isset($row->isSubtotal) || isset($row->isGrandTotal) || (isset($row->isPlaceholder) && $row->isPlaceholder)) {
                    return $row->transaction;
                }

                // Show Payment ID instead of Bill Number
                return 'PAY-' . str_pad($row->payment_id, 5, '0', STR_PAD_LEFT);
            })
            ->addColumn('vendor', fn($row) => (isset($row->isSubtotal) || isset($row->isGrandTotal) || isset($row->isPlaceholder)) ? '' : ($row->vendor_name ?? ''))
            ->addColumn('type', function ($row) {
                if (isset($row->isSubtotal) || isset($row->isGrandTotal) || (isset($row->isPlaceholder) && $row->isPlaceholder)) {
                    return '';
                }
                return 'Payment';
            })
            // 👇 FIX: Apply decimal formatting AND sign logic
            ->editColumn('total_amount', function ($row) {
                if (isset($row->isPlaceholder))
                    return '';

                $amount = (float)($row->total_amount ?? 0);

                // Check for Subtotal or GrandTotal rows, which already hold the correct signed sum
                if (isset($row->isSubtotal) || isset($row->isGrandTotal)) {
                    // FIX: Apply 2 decimal places here
                    return number_format($amount, 2); 
                }

                // Logic for individual payment rows
                // Assuming 'credit_card' sub-type should be positive (a credit/liability increase)
                // and everything else (like 'bank') should be negative (a debit/asset decrease).
                $isCreditCard = strtolower($row->account_subtype ?? '') === 'credit_card';
                
                // Apply the sign logic: positive for credit card, negative for others
                $signedAmount = $isCreditCard ? $amount : -$amount;

                // FIX: Apply 2 decimal places here
                return number_format($signedAmount, 2);
            })

            ->setRowClass(function ($row) {
                if (property_exists($row, 'isParent') && $row->isParent) {
                    return 'parent-row toggle-bucket bucket-' . \Str::slug($row->bank_name ?? 'na');
                }
                if (property_exists($row, 'isSubtotal') && $row->isSubtotal && !property_exists($row, 'isGrandTotal')) {
                    return 'subtotal-row bucket-' . \Str::slug($row->bank_name ?? 'na');
                }
                if (!property_exists($row, 'isParent') && !property_exists($row, 'isSubtotal') && !property_exists($row, 'isGrandTotal') && !property_exists($row, 'isPlaceholder')) {
                    return 'child-row bucket-' . \Str::slug($row->bank_name ?? 'na');
                }
                if (property_exists($row, 'isGrandTotal') && $row->isGrandTotal) {
                    return 'grandtotal-row';
                }
                return '';
            })
            ->rawColumns(['transaction']);
    }

    /**
     * Get query source of dataTable.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Bill $model)
    {
        $start = request()->get('start_date')
            ?? request()->get('startDate')
            ?? Carbon::now()->startOfYear()->format('Y-m-d');

        $end = request()->get('end_date')
            ?? request()->get('endDate')
            ?? Carbon::now()->endOfDay()->format('Y-m-d');

        $userId = \Auth::user()->creatorId();

        // 1. Applied Bill Payments (grouped by reference)
        $appliedPayments = DB::table('bill_payments')
            ->select(
                'bill_payments.reference as payment_id',
                DB::raw('MIN(bill_payments.date) as bill_date'),
                
                // Combined amount: SUM of bill_payments + SUM of vendor credits for this reference
                DB::raw('(
                    SUM(bill_payments.amount) 
                    + COALESCE(
                        (SELECT SUM(ABS(t.amount)) 
                         FROM transactions t 
                         JOIN bill_payments bp2 ON bp2.id = t.payment_id
                         WHERE bp2.reference = bill_payments.reference
                         AND LOWER(t.category) LIKE "%vendor credit%"
                        ), 0
                    )
                ) as total_amount'),
                
                'bill_payments.reference',
                DB::raw('MAX(bill_payments.description) as description'),
                DB::raw('GROUP_CONCAT(DISTINCT bills.bill_id SEPARATOR ", ") as bill'),
                DB::raw('MAX(venders.name) as vendor_name'),
                'bank_accounts.bank_name',
                'bank_accounts.account_subtype as account_subtype',
                DB::raw('bill_payments.reference as memo')
            )
            ->leftJoin('bills', 'bills.id', '=', 'bill_payments.bill_id')
            ->leftJoin('venders', 'venders.id', '=', 'bills.vender_id')
            ->leftJoin('bank_accounts', 'bank_accounts.id', '=', 'bill_payments.account_id')
            ->where('bills.created_by', $userId)
            ->whereBetween('bill_payments.date', [$start, $end])
            ->where('bills.type', 'Bill')
            ->groupBy(
                'bill_payments.reference',
                'bank_accounts.bank_name',
                'bank_accounts.account_subtype'
            );

        // 2. Unapplied Payments (not yet linked to bills)
        // EXCLUDE payments that are already counted as vendor credits in the transactions table
        $unappliedPayments = DB::table('unapplied_payments')
            ->select(
                'unapplied_payments.reference as payment_id',
                'unapplied_payments.txn_date as bill_date',
                'unapplied_payments.unapplied_amount as total_amount',
                'unapplied_payments.reference',
                DB::raw('NULL as description'),
                DB::raw('"Unapplied" as bill'),
                'venders.name as vendor_name',
                'bank_accounts.bank_name',
                'bank_accounts.account_subtype as account_subtype',
                DB::raw('unapplied_payments.reference as memo')
            )
            ->leftJoin('venders', 'venders.id', '=', 'unapplied_payments.vendor_id')
            ->leftJoin('bank_accounts', 'bank_accounts.id', '=', 'unapplied_payments.account_id')
            ->where('unapplied_payments.created_by', $userId)
            ->whereBetween('unapplied_payments.txn_date', [$start, $end])
            ->where('unapplied_payments.unapplied_amount', '>', 0)
            // Exclude if already counted as vendor credit in transactions table (same reference/payment_no)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('transactions')
                    ->whereRaw('transactions.payment_no = unapplied_payments.reference')
                    ->whereRaw('LOWER(transactions.category) LIKE "%vendor credit%"');
            });

        // Combine both queries
        $combined = $appliedPayments->unionAll($unappliedPayments);

        return DB::query()->fromSub($combined, 'all_payments')
            ->orderBy('bill_date', 'asc');
    }


    /**
     * Optional method if you want to use the html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
            ->setTableId('customer-balance-table') // ✅ unchanged
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

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            Column::make('bill_date')->title('Date'),
            Column::make('transaction')->title('Transaction'),
            Column::make('vendor')->title('Vendor'), // ✅ added vendor column
            // Column::make('type')->title('Type'),
            Column::make('total_amount')->title('Amount'),
        ];
    }
}