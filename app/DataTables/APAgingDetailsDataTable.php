<?php

namespace App\DataTables;

use Carbon\Carbon;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\DB;

class APAgingDetailsDataTable extends DataTable
{
    public function dataTable($query)
    {
        $end = request()->get('end_date')
            ? Carbon::parse(request()->get('end_date'))->endOfDay()
            : (request()->get('endDate')
                ? Carbon::parse(request()->get('endDate'))->endOfDay()
                : Carbon::today());

        $data = collect($query->get());

        $grandTotalAmount = 0;
        $grandTotalOpenBalance = 0;

        // Define bucket order
        $bucketOrder = ['Current', '1 - 30', '31 - 60', '61 - 90', '91 and over'];

        // Group transactions into aging buckets
        $groupedData = $data->groupBy(function ($row) use ($end) {
            $dueDate = $row->due_date ?? $row->transaction_date;
            if (!$dueDate) return 'Current';

            try {
                $due = Carbon::parse($dueDate);
            } catch (\Exception $e) {
                return 'Current';
            }

            $age = $due->diffInDays($end, false);

            if ($age <= 0) return 'Current';
            if ($age <= 30) return '1 - 30';
            if ($age <= 60) return '31 - 60';
            if ($age <= 90) return '61 - 90';
            return '91 and over';
        });

        $finalData = collect();

        // Process in defined order
        foreach ($bucketOrder as $bucket) {
            if (!isset($groupedData[$bucket])) continue;
            
            $rows = $groupedData[$bucket];
            $subtotalAmount = 0;
            $subtotalOpenBalance = 0;

            // Bucket header row (collapsible)
            $finalData->push((object) [
                'bucket' => $bucket,
                'transaction_date' => '<span class="toggle-bucket" data-bucket="' . \Str::slug($bucket) . '"><span class="icon">▼</span> <strong>' . $bucket . '</strong></span>',
                'transaction' => '',
                'type' => '',
                'vendor_name' => '',
                'due_date' => '',
                'past_due' => '',
                'amount' => '',
                'open_balance' => '',
                'isParent' => true,
            ]);

            // Sort rows by vendor name, then by date
            $sortedRows = $rows->sortBy([
                ['vendor_name', 'asc'],
                ['transaction_date', 'asc']
            ]);

            foreach ($sortedRows as $row) {
                $amount = (float) ($row->amount ?? 0);
                $openBalance = (float) ($row->open_balance ?? 0);

                $subtotalAmount += $amount;
                $subtotalOpenBalance += $openBalance;

                // Calculate past due days
                $pastDue = '';
                if ($row->due_date) {
                    try {
                        $due = Carbon::parse($row->due_date);
                        $age = $due->diffInDays($end, false);
                        if ($age > 0) {
                            $pastDue = $age;
                        }
                    } catch (\Exception $e) {
                        // ignore
                    }
                }

                $finalData->push((object) [
                    'bucket' => $bucket,
                    'transaction_date' => $row->transaction_date,
                    'transaction' => $row->transaction_number ?? '',
                    'type' => $row->transaction_type,
                    'vendor_name' => $row->vendor_name ?? '',
                    'due_date' => $row->due_date ?? '',
                    'past_due' => $pastDue,
                    'amount' => $amount,
                    'open_balance' => $openBalance,
                    'isDetail' => true,
                ]);
            }

            // Bucket subtotal row
            $finalData->push((object) [
                'bucket' => $bucket,
                'transaction_date' => '<strong>Total for ' . $bucket . '</strong>',
                'transaction' => '',
                'type' => '',
                'vendor_name' => '',
                'due_date' => '',
                'past_due' => '',
                'amount' => $subtotalAmount,
                'open_balance' => $subtotalOpenBalance,
                'isSubtotal' => true,
            ]);

            // Empty spacer row
            $finalData->push((object) [
                'bucket' => $bucket,
                'transaction_date' => '',
                'transaction' => '',
                'type' => '',
                'vendor_name' => '',
                'due_date' => '',
                'past_due' => '',
                'amount' => '',
                'open_balance' => '',
                'isPlaceholder' => true,
            ]);

            $grandTotalAmount += $subtotalAmount;
            $grandTotalOpenBalance += $subtotalOpenBalance;
        }

        // Grand total row
        $finalData->push((object) [
            'bucket' => '',
            'transaction_date' => '<strong>TOTAL</strong>',
            'transaction' => '',
            'type' => '',
            'vendor_name' => '',
            'due_date' => '',
            'past_due' => '',
            'amount' => $grandTotalAmount,
            'open_balance' => $grandTotalOpenBalance,
            'isGrandTotal' => true,
        ]);

        return datatables()
            ->collection($finalData)
            ->addColumn('bucket', fn($row) => $row->bucket ?? '')
            ->editColumn('transaction_date', function ($row) {
                if (isset($row->isDetail)) {
                    return $row->transaction_date ? Carbon::parse($row->transaction_date)->format('m/d/Y') : '';
                }
                return $row->transaction_date ?? '';
            })
            ->editColumn('due_date', function ($row) {
                if (isset($row->isDetail) && $row->due_date) {
                    return Carbon::parse($row->due_date)->format('m/d/Y');
                }
                return '';
            })
            ->editColumn('amount', function ($row) {
                if (isset($row->isParent) || isset($row->isPlaceholder)) return '';
                $val = (float) ($row->amount ?? 0);
                if (abs($val) < 0.01) return '–';
                return number_format($val, 2);
            })
            ->editColumn('open_balance', function ($row) {
                if (isset($row->isParent) || isset($row->isPlaceholder)) return '';
                $val = (float) ($row->open_balance ?? 0);
                if (abs($val) < 0.01) return '–';
                return number_format($val, 2);
            })
            ->setRowClass(function ($row) {
                $bucketSlug = isset($row->bucket) ? \Str::slug($row->bucket) : 'na';
                
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
            ->rawColumns(['transaction_date', 'transaction']);
    }

    public function query()
    {
        ini_set('memory_limit', '512M');
        set_time_limit(600);
        $userId = \Auth::user()->creatorId();
        $end = request()->get('end_date')
            ?? request()->get('endDate')
            ?? Carbon::now()->endOfDay()->format('Y-m-d');

       // Pre-aggregate bill products + accounts
$billAmounts = DB::table('bill_products as bp')
    ->select('bp.bill_id', DB::raw('SUM(bp.price * bp.quantity - IFNULL(bp.discount,0)) as total_products'))
    ->groupBy('bp.bill_id');

$billAccounts = DB::table('bill_accounts as ba')
    ->select('ba.ref_id', DB::raw('SUM(ba.price) as total_accounts'))
    ->groupBy('ba.ref_id');

$billPayments = DB::table('bill_payments as bpmt')
    ->select('bpmt.bill_id', DB::raw('SUM(bpmt.amount) as total_paid'))
    ->where('bpmt.date', '<=', $end)
    ->groupBy('bpmt.bill_id');

$debitNotes = DB::table('debit_notes as dn')
    ->select('dn.bill', DB::raw('SUM(dn.amount) as total_debit'))
    ->where('dn.date', '<=', $end)
    ->groupBy('dn.bill');

/* ---------------------------------------------------------
 | 1. BILLS / CHECKS / EXPENSES (Optimized)
 --------------------------------------------------------- */
$bills = DB::table('bills as b')
    ->join('venders as v', 'v.id', '=', 'b.vender_id')
    ->leftJoinSub($billAmounts, 'bp', 'bp.bill_id', '=', 'b.id')
    ->leftJoinSub($billAccounts, 'ba', 'ba.ref_id', '=', 'b.id')
    ->leftJoinSub($billPayments, 'pmt', 'pmt.bill_id', '=', 'b.id')
    ->leftJoinSub($debitNotes, 'dn', 'dn.bill', '=', 'b.id')
    ->select(
        'b.id as transaction_id',
        'b.bill_date as transaction_date',
        'b.due_date',
        'v.name as vendor_name',
        'b.bill_id as transaction_number',
        DB::raw('CASE 
                    WHEN LOWER(b.type) = "bill" THEN "Bill"
                    WHEN LOWER(b.type) = "expense" THEN "Expense"
                    WHEN LOWER(b.type) = "check" THEN "Check"
                    ELSE b.type
                END as transaction_type'),
        // Total amount
        DB::raw('COALESCE(NULLIF(bp.total_products + IFNULL(ba.total_accounts,0), 0), b.total) as amount'),
        // Open balance
        DB::raw('COALESCE(NULLIF(bp.total_products + IFNULL(ba.total_accounts,0),0), b.total)
                 - IFNULL(pmt.total_paid,0)
                 - IFNULL(dn.total_debit,0) as open_balance')
    )
    ->where('b.created_by', $userId)
    ->whereRaw('LOWER(b.type) IN (?)', ['bill'])
    ->where('b.bill_date', '<=', $end)
    ->havingRaw('ABS(open_balance) > 0');


        /* ---------------------------------------------------------
         | 2. VENDOR CREDITS
         --------------------------------------------------------- */
      $vendorCredits = DB::table('vendor_credits as vc')
    ->join('venders as v', 'v.id', '=', 'vc.vender_id')
    ->leftJoin('vendor_credit_products as vcp', 'vcp.vendor_credit_id', '=', 'vc.id')
    ->leftJoin('vendor_credit_accounts as vca', 'vca.vendor_credit_id', '=', 'vc.id')
    ->select(
        'vc.id as transaction_id',
        'vc.date as transaction_date',
        'vc.date as due_date', // aging base
        'v.name as vendor_name',
        'vc.id as transaction_number', // or your numbering logic
        DB::raw('"Vendor Credit" as transaction_type'),
        DB::raw('
            -1 * (SUM(IFNULL(vcp.price * vcp.quantity,0)) + SUM(IFNULL(vca.price,0))) as amount
        '),
        DB::raw('
            -1 * (SUM(IFNULL(vcp.price * vcp.quantity,0)) + SUM(IFNULL(vca.price,0))) as open_balance
        ')
    )
    ->where('vc.created_by', $userId)
    ->where('vc.date', '<=', $end)
    ->groupBy('vc.id', 'vc.date', 'v.name');


        /* ---------------------------------------------------------
         | 3. BILL PAYMENTS WITH VENDOR CREDITS
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
                DB::raw('MIN(bp.id) as transaction_id'),
                DB::raw('MIN(bp.date) as transaction_date'),
                DB::raw('MIN(bp.date) as due_date'),
                'v.name as vendor_name',
                'bp.reference as transaction_number',
                DB::raw('"Bill Payment" as transaction_type'),
                // Negative amount (reduces A/P)
                DB::raw('-1 * (SUM(bp.amount) + IFNULL(vc.vendor_credit_amount, 0)) as amount'),
                DB::raw('-1 * (IFNULL(vc.vendor_credit_amount, 0)) as open_balance')
            )
            ->groupBy('bp.reference', 'v.name', 'vc.vendor_credit_amount');

            $unappliedPayments = DB::table('unapplied_payments')
            ->select(
                DB::raw('unapplied_payments.id as transaction_id'),
                'unapplied_payments.txn_date as transaction_date',
                DB::raw('unapplied_payments.txn_date as due_date'),
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
            ->unionAll($unappliedPayments)
            ->unionAll($billPaymentsWithCredits);

        return DB::query()
            ->fromSub($combined, 'transactions')
            ->orderBy('due_date', 'asc')
            ->orderBy('vendor_name', 'asc');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('aging-details-table')
            ->columns($this->getColumns())
            ->ajax(
                [
                    'url' => route('payables.aging_details'),
                    'type' => 'GET',
                    'headers' => [
                        'X-CSRF-TOKEN' => csrf_token(),
                    ],
                ]
            )
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
            Column::make('bucket')->title('Bucket')->visible(false),
            Column::make('transaction_date')->title('Date'),
            Column::make('transaction')->title('Transaction'),
            Column::make('type')->title('Type'),
            Column::make('vendor_name')->title('Vendor Display Name'),
            Column::make('due_date')->title('Due Date'),
            Column::make('past_due')->title('Past Due'),
            Column::make('amount')->title('Amount')->addClass('text-right'),
            Column::make('open_balance')->title('Open Balance')->addClass('text-right'),
        ];
    }
}
