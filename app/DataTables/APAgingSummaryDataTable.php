<?php

// app/DataTables/APAgingSummaryDataTable.php
namespace App\DataTables;

use Carbon\Carbon;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Column;
use Illuminate\Support\Facades\DB;

class APAgingSummaryDataTable extends DataTable
{
    public function dataTable($query)
    {
        $data = collect($query->get());
        $finalData = collect();

        // Track totals
        $currentTotal = $days30Total = $days60Total = $days90Total = $days90PlusTotal = $grandTotal = 0;

        // Group by vendor and sum balances in each bucket
        $vendors = $data->groupBy('vendor_name');

        foreach ($vendors as $vendor => $rows) {
            $buckets = [
                'current' => 0,
                'days_1_30' => 0,
                'days_31_60' => 0,
                'days_61_90' => 0,
                'days_90_plus' => 0,
            ];

            foreach ($rows as $row) {
                $buckets['current'] += (float) ($row->current ?? 0);
                $buckets['days_1_30'] += (float) ($row->days_1_30 ?? 0);
                $buckets['days_31_60'] += (float) ($row->days_31_60 ?? 0);
                $buckets['days_61_90'] += (float) ($row->days_61_90 ?? 0);
                $buckets['days_90_plus'] += (float) ($row->days_90_plus ?? 0);
            }

            $totalDue = array_sum($buckets);

            // Skip vendors with zero total (like QuickBooks)
            // if (abs($totalDue) < 0.01) {
            //     continue;
            // }

            $vendorDisplay = $vendor ?: 'Unknown Vendor';

            $finalData->push([
                'vendor_name' => $vendorDisplay,
                'current' => $this->formatAmount($buckets['current']),
                'days_1_30' => $this->formatAmount($buckets['days_1_30']),
                'days_31_60' => $this->formatAmount($buckets['days_31_60']),
                'days_61_90' => $this->formatAmount($buckets['days_61_90']),
                'days_90_plus' => $this->formatAmount($buckets['days_90_plus']),
                'total_due' => '<strong>' . $this->formatAmount($totalDue) . '</strong>',
            ]);

            // Update totals
            $currentTotal += $buckets['current'];
            $days30Total += $buckets['days_1_30'];
            $days60Total += $buckets['days_31_60'];
            $days90Total += $buckets['days_61_90'];
            $days90PlusTotal += $buckets['days_90_plus'];
            $grandTotal += $totalDue;
        }

        // Sort by vendor name
        $finalData = $finalData->sortBy('vendor_name')->values();

        if ($finalData->count() > 0) {
            // Totals row
            $finalData->push([
                'vendor_name' => '<strong>TOTAL</strong>',
                'current' => '<strong>' . $this->formatAmount($currentTotal) . '</strong>',
                'days_1_30' => '<strong>' . $this->formatAmount($days30Total) . '</strong>',
                'days_31_60' => '<strong>' . $this->formatAmount($days60Total) . '</strong>',
                'days_61_90' => '<strong>' . $this->formatAmount($days90Total) . '</strong>',
                'days_90_plus' => '<strong>' . $this->formatAmount($days90PlusTotal) . '</strong>',
                'total_due' => '<strong>' . $this->formatAmount($grandTotal) . '</strong>',
                'DT_RowClass' => 'summary-total'
            ]);
        } else {
            $finalData->push([
                'vendor_name' => 'No data found for the selected period.',
                'current' => '',
                'days_1_30' => '',
                'days_31_60' => '',
                'days_61_90' => '',
                'days_90_plus' => '',
                'total_due' => '',
                'DT_RowClass' => 'no-data-row'
            ]);
        }

        return datatables()
            ->collection($finalData)
            ->rawColumns([
                'vendor_name',
                'current',
                'days_1_30',
                'days_31_60',
                'days_61_90',
                'days_90_plus',
                'total_due'
            ]);
    }

    /**
     * Format amount with proper handling of zero and negative values
     */
    private function formatAmount($amount)
    {
        if (abs($amount) < 0.01) {
            return '–'; // Em dash for zero amounts (QuickBooks style)
        }
        return number_format($amount, 2);
    }

    public function query()
    {
        ini_set('memory_limit', '512M');
        set_time_limit(600);
        $userId = \Auth::user()->creatorId();
        $end = request()->get('end_date')
            ?? request()->get('endDate')
            ?? Carbon::now()->endOfDay()->format('Y-m-d');

        // Calculate open balance logic for bills
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
                (SELECT IFNULL(SUM(amount),0) FROM bill_payments WHERE bill_payments.bill_id = bills.id AND bill_payments.date <= "' . $end . '")
                -
                (SELECT IFNULL(SUM(debit_notes.amount),0) FROM debit_notes WHERE debit_notes.bill = bills.id AND debit_notes.date <= "' . $end . '")
            )
        ';

        /* ---------------------------------------------------------
         | 1. BILLS / CHECKS / EXPENSES - with aging buckets
         --------------------------------------------------------- */
        $bills = DB::table('bills')
            ->join('venders', 'venders.id', '=', 'bills.vender_id')
            ->select(
                'venders.name as vendor_name',
                // Current (due date is today or in the future)
                DB::raw("
                    CASE 
                        WHEN DATEDIFF('$end', bills.due_date) <= 0 
                        THEN ($billOpenBalanceLogic)
                        ELSE 0 
                    END as current
                "),
                // 1-30 days past due
                DB::raw("
                    CASE 
                        WHEN DATEDIFF('$end', bills.due_date) BETWEEN 1 AND 30 
                        THEN ($billOpenBalanceLogic)
                        ELSE 0 
                    END as days_1_30
                "),
                // 31-60 days past due
                DB::raw("
                    CASE 
                        WHEN DATEDIFF('$end', bills.due_date) BETWEEN 31 AND 60 
                        THEN ($billOpenBalanceLogic)
                        ELSE 0 
                    END as days_31_60
                "),
                // 61-90 days past due
                DB::raw("
                    CASE 
                        WHEN DATEDIFF('$end', bills.due_date) BETWEEN 61 AND 90 
                        THEN ($billOpenBalanceLogic)
                        ELSE 0 
                    END as days_61_90
                "),
                // 91+ days past due
                DB::raw("
                    CASE 
                        WHEN DATEDIFF('$end', bills.due_date) > 90 
                        THEN ($billOpenBalanceLogic)
                        ELSE 0 
                    END as days_90_plus
                ")
            )
            ->whereRaw('LOWER(bills.type) IN (?)', ['bill'])
            ->where('bills.created_by', $userId)
            // ->where('bills.bill_date', '>=', '2025-01-01')
            ->where('bills.bill_date', '<=', $end)
            ->where('bills.status', '!=', 4)
            // Only include bills with non-zero open balance
            ->havingRaw("ABS(current) > 0 OR ABS(days_1_30) > 0 OR ABS(days_31_60) > 0 OR ABS(days_61_90) > 0 OR ABS(days_90_plus) > 0");

        /* ---------------------------------------------------------
         | 2. VENDOR CREDITS - Always show as negative in "91 and over" or "current"
         |    (Since credits reduce what you owe, they appear as negative)
         --------------------------------------------------------- */
        $vendorCredits = DB::table('vendor_credits')
            ->join('venders', 'venders.id', '=', 'vendor_credits.vender_id')
            ->select(
                'venders.name as vendor_name',
                DB::raw('0 as current'),
                DB::raw('0 as days_1_30'),
                DB::raw('0 as days_31_60'),
                DB::raw('0 as days_61_90'),
                // Vendor credits go to 91+ bucket as negative (reduces A/P)
                DB::raw('
                    -1 * (
                        IFNULL((SELECT SUM(vcp.price * vcp.quantity) FROM vendor_credit_products vcp WHERE vcp.vendor_credit_id = vendor_credits.id), 0)
                        +
                        IFNULL((SELECT SUM(vca.price) FROM vendor_credit_accounts vca WHERE vca.vendor_credit_id = vendor_credits.id), 0)
                    ) as days_90_plus
                ')
            )
            ->where('vendor_credits.created_by', $userId)
            // ->where('vendor_credits.date', '>=', '2025-01-01')
            ->where('vendor_credits.date', '<=', $end);

        /* ---------------------------------------------------------
         | 3. UNAPPLIED PAYMENTS - Show as negative (reduces A/P)
         --------------------------------------------------------- */

        $unappliedPayments = DB::table('unapplied_payments')
            ->join('venders', 'venders.id', '=', 'unapplied_payments.vendor_id')
            ->select(
                'venders.name as vendor_name',
                DB::raw('0 as current'),
                DB::raw('0 as days_1_30'),
                DB::raw('0 as days_31_60'),
                DB::raw('0 as days_61_90'),
                DB::raw('-1 * unapplied_payments.unapplied_amount as days_90_plus')
            )
            ->where('unapplied_payments.created_by', $userId)
            ->where('unapplied_payments.unapplied_amount', '>', 0)
            // ->where('unapplied_payments.date', '>=', '2025-01-01')
            ->where('unapplied_payments.txn_date', '<=', $end);

        /* ---------------------------------------------------------
         | 4. BILL PAYMENTS WITH VENDOR CREDITS
         | Get bill payments grouped by reference, plus vendor credit amounts
         --------------------------------------------------------- */
        // First, get vendor credit amounts linked to bill payments via transactions
        $vendorCreditAmounts = DB::table('transactions as tr_credit')
            ->join('transactions as tr_inv', 'tr_inv.payment_no', '=', 'tr_credit.payment_no')
            ->join('bill_payments as bpmt', 'tr_inv.payment_id', '=', 'bpmt.id')
            ->where('tr_inv.category', 'Bill')
            ->where('tr_credit.category', 'Vendor Credit')
            ->select('bpmt.reference', DB::raw('SUM(tr_credit.amount) as vendor_credit_amount'))
            ->groupBy('bpmt.reference');

        // Bill payments grouped by reference with vendor credit
        $billPaymentsWithCredits = DB::table('bill_payments as bp')
            ->join('bills as b', 'b.id', '=', 'bp.bill_id')
            ->join('venders as v', 'v.id', '=', 'b.vender_id')
            ->leftJoinSub($vendorCreditAmounts, 'vc', 'vc.reference', '=', 'bp.reference')
            ->where('b.type', 'Bill')
            ->where('b.created_by', $userId)
            ->where('bp.date', '<=', $end)
            ->whereNotNull('vc.vendor_credit_amount')
            ->where('vc.vendor_credit_amount', '>', 0)
            ->select(
                'v.name as vendor_name',
                DB::raw('0 as current'),
                DB::raw('0 as days_1_30'),
                DB::raw('0 as days_31_60'),
                DB::raw('0 as days_61_90'),
                // Negative amount (reduces A/P) - only vendor credit portion
                DB::raw('-1 * IFNULL(vc.vendor_credit_amount, 0) as days_90_plus')
            )
            ->groupBy('bp.reference', 'v.name', 'vc.vendor_credit_amount');

        /* ---------------------------------------------------------
         | COMBINE ALL
         --------------------------------------------------------- */
        $combined = $bills
            ->unionAll($vendorCredits)
            ->unionAll($unappliedPayments)
            ->unionAll($billPaymentsWithCredits);

        return DB::query()
            ->fromSub($combined, 'aging')
            ->orderBy('vendor_name', 'asc');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('aging-summary-table')
            ->columns($this->getColumns())
            ->ajax([
                'url' => route('payables.aging_summary'),
                'type' => 'GET',
                //    token
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
                'scrollY' => '500px',
                'colReorder' => true,
                'scrollCollapse' => true,
                'createdRow' => "function(row, data) {
                    $('td:eq(1), td:eq(2), td:eq(3), td:eq(4), td:eq(5), td:eq(6)', row).addClass('text-right');
                    if ($(row).hasClass('summary-total')) {
                        $(row).addClass('font-weight-bold bg-light');
                    }
                }"
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('vendor_name')->title('Vendor'),
            Column::make('current')->title('CURRENT')->addClass('text-right'),
            Column::make('days_1_30')->title('1 - 30')->addClass('text-right'),
            Column::make('days_31_60')->title('31 - 60')->addClass('text-right'),
            Column::make('days_61_90')->title('61 - 90')->addClass('text-right'),
            Column::make('days_90_plus')->title('91 AND OVER')->addClass('text-right'),
            Column::make('total_due')->title('Total')->addClass('text-right'),
        ];
    }
}
