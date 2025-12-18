<?php

namespace App\DataTables;

use App\Models\Bill;
use Carbon\Carbon;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\DB;

class UnpaidBillsReportDataTable extends DataTable
{
    public function dataTable($query)
    {
        $data = collect($query->get());

        $grandTotalAmount = 0;
        $grandOpenBalance = 0;

        // Group by vendor name
        $groupedData = $data->groupBy(function ($row) {
            return $row->name ?? 'Unknown Vendor';
        });

        $finalData = collect();

        foreach ($groupedData as $vendor => $rows) {
            $subtotalAmount = 0;
            $subtotalOpen = 0;
            $rows = $rows->sortBy('type');
            
            // First pass: calculate subtotals to determine if we should skip this vendor
            foreach ($rows as $row) {
                $amount = ($row->subtotal ?? 0) + ($row->total_tax ?? 0);
                $openBalance = $row->open_balance ?? 0;
                $subtotalAmount += $amount;
                $subtotalOpen += $openBalance;
            }
            
            // Skip vendors with 0 total open balance
            if (abs($subtotalOpen) < 0.01) {
                continue;
            }
            
            $rowCount = $rows->count();

            // Vendor header row with count (QuickBooks style)
            $finalData->push((object) [
                'vendor' => $vendor,
                'date' => '<span class="toggle-bucket" data-bucket="' . \Str::slug($vendor) . '"><span class="icon">▼</span> <strong>' . $vendor . ' (' . $rowCount . ')</strong></span>',
                'transaction_type' =>'',
                'num' => '',
                'due_date' => '',
                'past_due' => '',
                'amount' => '',
                'open_balance' => '',
                'isParent' => true,
            ]);

            // Second pass: add individual rows
            foreach ($rows as $row) {
                $amount = ($row->subtotal ?? 0) + ($row->total_tax ?? 0);
                $openBalance = $row->open_balance ?? 0;

                // Calculate past due days (only for bills, not for credits/payments)
                $pastDueDays = '';
                $typeValue = isset($row->type) ? strtolower($row->type) : 'bill';
                $isCredit = in_array($typeValue, ['vendor credit']);
                
                if (!$isCredit && $row->due_date) {
                    $dueDate = Carbon::parse($row->due_date);
                    $today = Carbon::today();
                    if ($dueDate->lt($today)) {
                        $pastDueDays = $dueDate->diffInDays($today);
                    }
                }

                // Determine transaction type - use database value if it's a credit type
                $transactionType = 'Bill';
                if (isset($row->type)) {
                    $type = strtolower($row->type);
                    if ($type === 'vendor credit') {
                        $transactionType = 'Vendor Credit';
                    } elseif ($type === 'unapplied payment') {
                        $transactionType = 'Bill Payment (Unapplied)';
                    } elseif ($type === 'check') {
                        $transactionType = 'Check';
                    } elseif ($type === 'expense') {
                        $transactionType = 'Expense';
                    } elseif ($type === 'bill') {
                        $transactionType = 'Bill';
                    } elseif ($type === 'bill payment') {
                        $transactionType = 'Bill Payment';
                    }
                }

                $finalData->push((object) [
                    'vendor' => $vendor,
                    'date' => $row->bill_date,
                    'transaction_type' => $transactionType,
                    // For Vendor Credit and Unapplied Payment, show "–" for Num, Due date, Past due
                    'num' => $isCredit ? '–' : ($row->bill ?? $row->id),
                    'due_date' => $isCredit ? '–' : $row->due_date,
                    'past_due' => $isCredit ? '–' : $pastDueDays,
                    'amount' => $amount,
                    'open_balance' => $openBalance,
                    'isDetail' => true,
                    'isCredit' => $isCredit,
                ]);
            }

            // Vendor subtotal row (QuickBooks style: "Total for [Vendor]")
            $finalData->push((object) [
                'vendor' => $vendor,
                'date' => '<strong>Total for ' . $vendor . '</strong>',
                'transaction_type' => '',
                'num' => '',
                'due_date' => '',
                'past_due' => '',
                'amount' => $subtotalAmount,
                'open_balance' => $subtotalOpen,
                'isSubtotal' => true,
            ]);

            // Empty spacer row
            $finalData->push((object) [
                'vendor' => $vendor,
                'date' => '',
                'transaction_type' => '',
                'num' => '',
                'due_date' => '',
                'past_due' => '',
                'amount' => '',
                'open_balance' => '',
                'isPlaceholder' => true,
            ]);

            $grandTotalAmount += $subtotalAmount;
            $grandOpenBalance += $subtotalOpen;
        }

        // Grand total row
        $finalData->push((object) [
            'vendor' => '',
            'date' => '<strong>TOTAL</strong>',
            'transaction_type' => '',
            'num' => '',
            'due_date' => '',
            'past_due' => '',
            'amount' => $grandTotalAmount,
            'open_balance' => $grandOpenBalance,
            'isGrandTotal' => true,
        ]);

        return datatables()
            ->collection($finalData)
            ->editColumn('date', function ($row) {
                if (isset($row->isParent) || isset($row->isSubtotal) || isset($row->isGrandTotal) || isset($row->isPlaceholder)) {
                    return $row->date ?? '';
                }
                return $row->date ? Carbon::parse($row->date)->format('m/d/Y') : '';
            })
            ->editColumn('due_date', function ($row) {
                if (isset($row->isParent) || isset($row->isSubtotal) || isset($row->isGrandTotal) || isset($row->isPlaceholder)) {
                    return '';
                }
                // Return "–" directly for credit types
                if ($row->due_date === '–') {
                    return '–';
                }
                return $row->due_date ? Carbon::parse($row->due_date)->format('m/d/Y') : '';
            })
            ->editColumn('num', function ($row) {
                if (isset($row->isParent) || isset($row->isSubtotal) || isset($row->isGrandTotal) || isset($row->isPlaceholder)) {
                    return '';
                }
                // Return "–" directly for credit types
                if ($row->num === '–') {
                    return '–';
                }
                return \Auth::user()->billNumberFormat($row->num);
            })
            ->editColumn('past_due', function ($row) {
                if (isset($row->isParent) || isset($row->isSubtotal) || isset($row->isGrandTotal) || isset($row->isPlaceholder)) {
                    return '';
                }
                // Return "–" directly for credit types
                if ($row->past_due === '–') {
                    return '–';
                }
                return $row->past_due !== '' ? $row->past_due : '';
            })
            ->editColumn('amount', function ($row) {
                if (isset($row->isPlaceholder)) return '';
                if (isset($row->isParent)) return '';
                $val = (float) ($row->amount ?? 0);
                if (abs($val) < 0.01) return '–';
                return number_format($val, 2);
            })
            ->editColumn('open_balance', function ($row) {
                if (isset($row->isPlaceholder)) return '';
                if (isset($row->isParent)) return '';
                $val = (float) ($row->open_balance ?? 0);
                if (abs($val) < 0.01) return '–';
                return number_format($val, 2);
            })
            ->setRowClass(function ($row) {
                $bucketSlug = isset($row->vendor) ? \Str::slug($row->vendor) : 'na';
                
                if (property_exists($row, 'isParent') && $row->isParent) {
                    return 'parent-row toggle-bucket bucket-' . $bucketSlug;
                }
                if (property_exists($row, 'isSubtotal') && $row->isSubtotal) {
                    return 'subtotal-row bucket-' . $bucketSlug;
                }
                if (property_exists($row, 'isGrandTotal') && $row->isGrandTotal) {
                    return 'grandtotal-row font-weight-bold bg-light';
                }
                if (property_exists($row, 'isPlaceholder') && $row->isPlaceholder) {
                    return 'placeholder-row bucket-' . $bucketSlug;
                }
                if (property_exists($row, 'isDetail') && $row->isDetail) {
                    return 'child-row bucket-' . $bucketSlug;
                }
                return '';
            })
            ->rawColumns(['date', 'transaction_type']);
    }

    public function query(Bill $model)
    {
        $end = request()->get('end_date') ?? request()->get('endDate') ?? Carbon::now()->endOfDay()->format('Y-m-d');
        $userId = \Auth::user()->creatorId();

        // Open balance calculation - handles both bill_products and bill_accounts
        $openBalanceCalc = '
            (
                COALESCE(
                    (SELECT SUM(bp.price * bp.quantity - IFNULL(bp.discount, 0)) FROM bill_products bp WHERE bp.bill_id = bills.id),
                    0
                )
                +
                COALESCE(
                    (SELECT SUM(ba.price) FROM bill_accounts ba WHERE ba.ref_id = bills.id),
                    0
                )
                +
                COALESCE(
                    (SELECT SUM((bp2.price * bp2.quantity - IFNULL(bp2.discount, 0)) * (t.rate / 100))
                     FROM bill_products bp2
                     LEFT JOIN taxes t ON FIND_IN_SET(t.id, bp2.tax) > 0
                     WHERE bp2.bill_id = bills.id),
                    0
                )
                -
                COALESCE(
                    (SELECT SUM(bpay.amount) FROM bill_payments bpay WHERE bpay.bill_id = bills.id AND bpay.date <= "' . $end . '"),
                    0
                )
                -
                COALESCE(
                    (SELECT SUM(dn.amount) FROM debit_notes dn WHERE dn.bill = bills.id AND dn.date <= "' . $end . '"),
                    0
                )
            )
        ';

        /* ---------------------------------------------------------
         | 1. BILLS with open balance
         --------------------------------------------------------- */
        $bills = DB::table('bills')
            ->select(
                'bills.id',
                'bills.bill_id as bill',
                'bills.bill_date',
                'bills.due_date',
                'bills.type',
                'venders.name',
                // Total amount (products + accounts + tax)
                DB::raw('(
                    COALESCE((SELECT SUM(bp.price * bp.quantity - IFNULL(bp.discount, 0)) FROM bill_products bp WHERE bp.bill_id = bills.id), 0)
                    +
                    COALESCE((SELECT SUM(ba.price) FROM bill_accounts ba WHERE ba.ref_id = bills.id), 0)
                ) as subtotal'),
                DB::raw('COALESCE(
                    (SELECT SUM((bp2.price * bp2.quantity - IFNULL(bp2.discount, 0)) * (t.rate / 100))
                     FROM bill_products bp2
                     LEFT JOIN taxes t ON FIND_IN_SET(t.id, bp2.tax) > 0
                     WHERE bp2.bill_id = bills.id),
                    0
                ) as total_tax'),
                DB::raw("($openBalanceCalc) as open_balance")
            )
            ->leftJoin('venders', 'venders.id', '=', 'bills.vender_id')
            ->where('bills.created_by', $userId)
            ->where('bills.status', '!=', 4) // Not paid
            ->where('bills.bill_date', '<=', $end)
            ->havingRaw("ABS($openBalanceCalc) > 0.01");

        /* ---------------------------------------------------------
         | 2. UNAPPLIED PAYMENTS (negative - reduces A/P)
         --------------------------------------------------------- */
        $unappliedPayments = DB::table('unapplied_payments')
            ->select(
                'unapplied_payments.id',
                'unapplied_payments.reference as bill',
                'unapplied_payments.txn_date as bill_date',
                DB::raw('unapplied_payments.txn_date as due_date'),
                DB::raw('"Unapplied Payment" as type'),
                'venders.name',
                DB::raw('-1 * unapplied_payments.unapplied_amount as subtotal'),
                DB::raw('0 as total_tax'),
                DB::raw('-1 * unapplied_payments.unapplied_amount as open_balance')
            )
            ->join('venders', 'venders.id', '=', 'unapplied_payments.vendor_id')
            ->where('unapplied_payments.created_by', $userId)
            ->where('unapplied_payments.unapplied_amount', '>', 0)
            ->where('unapplied_payments.txn_date', '<=', $end);

        /* ---------------------------------------------------------
         | 3. VENDOR CREDITS (negative - reduces A/P)
         --------------------------------------------------------- */
        $vendorCredits = DB::table('vendor_credits')
            ->select(
                'vendor_credits.id',
                'vendor_credits.vendor_credit_id as bill',
                'vendor_credits.date as bill_date',
                DB::raw('vendor_credits.date as due_date'),
                DB::raw('"Vendor Credit" as type'),
                'venders.name',
                DB::raw('
                    -1 * (
                        COALESCE((SELECT SUM(vcp.price * vcp.quantity) FROM vendor_credit_products vcp WHERE vcp.vendor_credit_id = vendor_credits.id), 0)
                        +
                        COALESCE((SELECT SUM(vca.price) FROM vendor_credit_accounts vca WHERE vca.vendor_credit_id = vendor_credits.id), 0)
                    ) as subtotal
                '),
                DB::raw('0 as total_tax'),
                DB::raw('
                    -1 * (
                        COALESCE((SELECT SUM(vcp.price * vcp.quantity) FROM vendor_credit_products vcp WHERE vcp.vendor_credit_id = vendor_credits.id), 0)
                        +
                        COALESCE((SELECT SUM(vca.price) FROM vendor_credit_accounts vca WHERE vca.vendor_credit_id = vendor_credits.id), 0)
                    ) as open_balance
                ')
            )
            ->join('venders', 'venders.id', '=', 'vendor_credits.vender_id')
            ->where('vendor_credits.created_by', $userId)
            ->where('vendor_credits.date', '<=', $end);

        /* ---------------------------------------------------------
         | 4. BILL PAYMENTS WITH VENDOR CREDITS
         --------------------------------------------------------- */
        // First, get vendor credit amounts linked to bill payments via transactions
         $vendorCreditAmounts = DB::table('transactions as tr_credit')
            ->join('transactions as tr_inv', 'tr_inv.payment_no', '=', 'tr_credit.payment_no')
            ->join('bill_payments as bpmt', 'tr_inv.payment_id', '=', 'bpmt.id')
            ->where('tr_inv.category', 'Bill')
            ->where('tr_credit.category', 'Vendor Credit')
            ->select('bpmt.reference', 'tr_credit.amount as vendor_credit_amount')
            ->groupBy('bpmt.reference');

        // Bill payments grouped by reference with vendor credit - ONE ROW PER REFERENCE
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
                DB::raw('MIN(bp.id) as id'),
                'bp.reference as bill',
                DB::raw('MIN(bp.date) as bill_date'),
                DB::raw('MIN(bp.date) as due_date'),
                DB::raw('"Bill Payment" as type'),
                DB::raw('MIN(v.name) as name'),
                // Negative amount (reduces A/P)
                DB::raw('-1 * (SUM(bp.amount) + MAX(IFNULL(vc.vendor_credit_amount, 0))) as subtotal'),
                DB::raw('0 as total_tax'),
                DB::raw('-1 * MAX(IFNULL(vc.vendor_credit_amount, 0)) as open_balance')
            )
            ->groupBy('bp.reference');

        /* ---------------------------------------------------------
         | 5. EXPENSES WITH ACCOUNT PAYABLE (other than bills)
         | bill_accounts where chart_account_id = account_payable
         | Shown as NEGATIVE (reduces A/P)
         --------------------------------------------------------- */
        $accountPayable = \App\Models\Utility::getAccountPayableAccount($userId);
        $expensesWithAP = DB::table('bill_accounts')
            ->select(
                'bill_accounts.id',
                'bills.bill_id as bill',
                'bills.bill_date',
                'bills.due_date',
                'bills.type',
                'venders.name',
                DB::raw('-1 * bill_accounts.price as subtotal'),
                DB::raw('0 as total_tax'),
                DB::raw('-1 * bill_accounts.price as open_balance')
            )
            ->join('bills', 'bills.id', '=', 'bill_accounts.ref_id')
            ->join('venders', 'venders.id', '=', 'bills.vender_id')
            ->where('bills.created_by', $userId)
            ->whereRaw('LOWER(bills.type) != ?', ['bill']) // Expenses/Checks only, not bills
            ->where('bills.bill_date', '<=', $end)
            ->where('bill_accounts.chart_account_id', $accountPayable ? $accountPayable->id : 0);

        /* ---------------------------------------------------------
         | COMBINE ALL
         --------------------------------------------------------- */
        $combined = $bills
            ->unionAll($unappliedPayments)
            ->unionAll($vendorCredits)
            ->unionAll($billPaymentsWithCredits)
            ->unionAll($expensesWithAP);

        return DB::query()
            ->fromSub($combined, 'unpaid')
            ->orderBy('name', 'asc')
            ->orderBy('bill_date', 'asc');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('unpaid-bills-table')
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
            Column::make('date')->title('Date'),
            Column::make('transaction_type')->title('Transaction type'),
            Column::make('num')->title('Num'),
            Column::make('due_date')->title('Due date'),
            Column::make('past_due')->title('Past due')->addClass('text-right'),
            Column::make('amount')->title('Amount')->addClass('text-right'),
            Column::make('open_balance')->title('Open balance')->addClass('text-right'),
        ];
    }
}
