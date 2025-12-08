<?php

namespace App\DataTables;

use App\Models\Bill;
use Carbon\Carbon;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\DB;

class BillsandPayments extends DataTable
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
        $grandOpenBalance = 0;

        // Group transactions by vendor name
        $groupedData = $data->groupBy(function ($row) {
            return $row->name ?? 'Unknown Vendor';
        });

        $finalData = collect();

        foreach ($groupedData as $vendor => $rows) {
            $subtotalAmount = 0;
            $subtotalOpen = 0;

            // Vendor header row
            $finalData->push((object) [
                'vendor' => $vendor,
                'id' => null,
                'bill_date' => '',
                'transaction' => '<span class="" data-bucket="' . \Str::slug($vendor) . '"> <span class="icon">▼</span> <strong>' . $vendor . '</strong></span>',
                'type' => '',
                'total_amount' => null,
                'open_balance' => null,
                'isPlaceholder' => true,
                'isSubtotal' => false,
                'isParent' => true
            ]);

            // Sort rows by date within the vendor group
            $sortedRows = $rows->sortBy('sort_date');
            
            foreach ($sortedRows as $row) {
                // Aggregate subtotals only for Bill transactions (type=Bill), 
                // as Payments/Accounts affect the Open Balance, not the Bill's gross amount
                if ($row->type == 'Bill') {
                    $subtotalAmount += (float) ($row->total_amount ?? 0);
                    $subtotalOpen += (float) $row->open_balance;
                }
                
                $row->vendor = $vendor;
                $finalData->push($row);
            }

            // Vendor subtotal row 
            $finalData->push((object) [
                'vendor' => $vendor,
                'id' => null,
                'bill_date' => '',
                'transaction' => '<strong>Subtotal for ' . $vendor . '</strong>',
                'type' => '',
                'total_amount' => $subtotalAmount,
                'open_balance' => $subtotalOpen,
                'isSubtotal' => true,
                'sort_date' => $rows->last()->sort_date ?? date('Y-m-d') 
            ]);

            // Add an empty space row for visual separation
            $finalData->push((object) [
                'vendor' => $vendor,
                'id' => null,
                'bill_date' => '',
                'transaction' => '',
                'type' => '',
                'total_amount' => '',
                'open_balance' => '',
                'isPlaceholder' => true,
                'sort_date' => $rows->last()->sort_date ?? date('Y-m-d')
            ]);


            $grandTotalAmount += $subtotalAmount;
            $grandOpenBalance += $subtotalOpen;
        }

        // Grand total row
        $finalData->push((object) [
            'vendor' => '',
            'id' => null,
            'bill_date' => '',
            'transaction' => '<strong>Grand Total</strong>',
            'type' => '',
            'total_amount' => $grandTotalAmount,
            'open_balance' => $grandOpenBalance,
            'isGrandTotal' => true,
        ]);

        return datatables()
            ->collection($finalData)
            // FIX: Replaced isset with property_exists or simple null check
            ->addColumn('bill_date', fn($row) => property_exists($row, 'isSubtotal') || property_exists($row, 'isGrandTotal') || property_exists($row, 'isPlaceholder') ? '' : $row->bill_date)
            
            ->addColumn('transaction', function ($row) {
                // FIX: Replaced isset with property_exists
                if (property_exists($row, 'isSubtotal') || property_exists($row, 'isGrandTotal') || (property_exists($row, 'isPlaceholder') && $row->isPlaceholder)) {
                    return $row->transaction;
                }
                // Determine transaction text based on type
                if ($row->type == 'Bill') {
                    return \Auth::user()->billNumberFormat($row->bill ?? $row->id);
                } elseif ($row->type == 'Payment') {
                    return 'Payment for Bill #' . \Auth::user()->billNumberFormat($row->bill_id);
                } elseif ($row->type == 'Account') {
                    return 'Account Entry for Bill #' . \Auth::user()->billNumberFormat($row->bill_id);
                }
                return 'N/A'; // fallback
            })
            
            ->addColumn('type', function ($row) {
                // FIX: Replaced isset with property_exists
                if (property_exists($row, 'isSubtotal') || property_exists($row, 'isGrandTotal') || (property_exists($row, 'isPlaceholder') && $row->isPlaceholder)) {
                    return ''; // leave blank for headers, subtotal, grand total
                }
                return $row->type; // This will show 'Bill', 'Payment', or 'Account'
            })

            // FIX: Apply number_format with 2 decimal places (2) and replaced isset
            ->editColumn('total_amount', function ($row) {
                if (property_exists($row, 'isPlaceholder')) {
                    return ''; // show blank for vendor header rows
                }
                if (property_exists($row, 'isSubtotal') || property_exists($row, 'isGrandTotal')) {
                    return number_format($row->total_amount ?? 0, 2);
                }
                // Only Bills have a gross amount. Payments/Accounts have amount/price.
                return number_format($row->total_amount ?? $row->price ?? 0, 2);
            })
            
            // FIX: Apply number_format with 2 decimal places (2) and replaced isset
            ->editColumn('open_balance', function ($row) {
                if (property_exists($row, 'isPlaceholder')) {
                    return ''; // show blank for vendor header rows
                }
                if ($row->type === 'Payment') {
                    // Show negative payment amount in open balance column
                    return number_format(($row->price ?? 0) * -1, 2);
                }
                if (property_exists($row, 'isSubtotal') || property_exists($row, 'isGrandTotal')) {
                    return number_format($row->open_balance ?? 0, 2);
                }
                return number_format($row->open_balance ?? 0, 2);
            })
            
            ->setRowClass(function ($row) {
                // All row class checks already use property_exists, so they are fine.
                if (property_exists($row, 'isParent') && $row->isParent) {
                    return 'parent-row toggle-bucket bucket-' . \Str::slug($row->vendor ?? 'na');
                }

                if (property_exists($row, 'isSubtotal') && $row->isSubtotal && !property_exists($row, 'isGrandTotal')) {
                    return 'subtotal-row bucket-' . \Str::slug($row->vendor ?? 'na');
                }

                if (
                    !property_exists($row, 'isParent') &&
                    !property_exists($row, 'isSubtotal') &&
                    !property_exists($row, 'isGrandTotal') &&
                    !property_exists($row, 'isPlaceholder')
                ) {
                    return 'child-row bucket-' . \Str::slug($row->vendor ?? 'na');
                }

                if (property_exists($row, 'isGrandTotal') && $row->isGrandTotal) {
                    return 'grandtotal-row';
                }

                return '';
            })
            ->rawColumns(['transaction']);
    }

    /**
     * Get query source of dataTable using UNION.
     *
     * @param \App\Models\Bill $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Bill $model)
    {
        $start = request()->get('start_date') ?? request()->get('startDate') ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $end = request()->get('end_date') ?? request()->get('endDate') ?? Carbon::now()->endOfDay()->format('Y-m-d');

        // --- 1. BILLS (Transaction Type: Bill) ---
        $billsQuery = DB::table('bills')
            ->select(
                'bills.id',
                'bills.bill_id as bill',
                'bills.bill_date',
                'venders.name',
                DB::raw("'Bill' as type"),
                DB::raw('(
                    SELECT COALESCE(SUM(
                        (bp.price * bp.quantity) - COALESCE(bp.discount, 0)
                    ), 0)
                    FROM bill_products bp
                    WHERE bp.bill_id = bills.id
                ) + (
                    SELECT COALESCE(SUM(ba.price), 0)
                    FROM bill_accounts ba
                    WHERE ba.ref_id = bills.id
                ) as total_amount'), // Bill gross amount (Products + Accounts)
                DB::raw('0.00 as price'), // Placeholder for Bill Payments/Accounts
                DB::raw('bills.bill_date as sort_date'), // For internal sorting
                DB::raw('bills.id as bill_id'), // Needed for reference later in transaction column

                // Open balance calculation: Total Bill Amount - Payments - Debit Notes
                DB::raw('(
                    (
                        (
                            SELECT COALESCE(SUM((bp.price * bp.quantity) - COALESCE(bp.discount, 0)), 0) FROM bill_products bp WHERE bp.bill_id = bills.id
                        )
                        + (
                            SELECT COALESCE(SUM(ba.price), 0) FROM bill_accounts ba WHERE ba.ref_id = bills.id
                        )
                    )
                    + (
                        SELECT COALESCE(SUM((bp2.price * bp2.quantity - COALESCE(bp2.discount, 0)) * (t.rate / 100)), 0) 
                        FROM bill_products bp2 LEFT JOIN taxes t ON FIND_IN_SET(t.id, bp2.tax) > 0 WHERE bp2.bill_id = bills.id
                    )
                    - (
                        (
                            SELECT COALESCE(SUM(amount), 0) FROM bill_payments WHERE bill_payments.bill_id = bills.id
                        )
                        + (
                            SELECT COALESCE(SUM(debit_notes.amount), 0) FROM debit_notes WHERE debit_notes.bill = bills.id
                        )
                    )
                ) as open_balance')
            )
            ->leftJoin('venders', 'venders.id', '=', 'bills.vender_id')
            ->where('bills.created_by', \Auth::user()->creatorId())
            ->where('bills.type', 'bill')
            ->where('bills.status', '!=', '4')
            ->whereBetween('bills.bill_date', [$start, $end]);

        // --- 2. BILL PAYMENTS (Transaction Type: Payment) ---
        $paymentsQuery = DB::table('bill_payments')
            ->select(
                'bill_payments.id',
                'bills.bill_id as bill', // bill number
                'bill_payments.date as bill_date',
                'venders.name',
                DB::raw("'Payment' as type"),
                DB::raw('0.00 as total_amount'), // No gross amount for payment
                'bill_payments.amount as price', // Payment amount
                DB::raw('bill_payments.date as sort_date'), // For internal sorting
                'bills.bill_id as bill_id', // reference bill ID (bill number)
                DB::raw('0.00 as open_balance') // Not applicable for payments in this context
            )
            ->leftJoin('bills', 'bills.id', '=', 'bill_payments.bill_id')
            ->leftJoin('venders', 'venders.id', '=', 'bills.vender_id')
            ->where('bills.created_by', \Auth::user()->creatorId())
            ->where('bills.type', 'bill')
            ->whereBetween('bill_payments.date', [$start, $end]);

        // --- 3. BILL ACCOUNTS (Transaction Type: Account) ---
        $accountsQuery = DB::table('bill_accounts')
            ->select(
                'bill_accounts.id',
                'bills.bill_id as bill', // bill number
                'bills.bill_date', // Use bill date as the transaction date
                'venders.name',
                DB::raw("'Account' as type"),
                DB::raw('0.00 as total_amount'), // No gross amount
                'bill_accounts.price as price', // Account price
                DB::raw('bills.bill_date as sort_date'), // For internal sorting
                'bills.bill_id as bill_id', // reference bill ID (bill number)
                DB::raw('0.00 as open_balance') // Not applicable
            )
            ->leftJoin('bills', 'bills.id', '=', 'bill_accounts.ref_id')
            ->leftJoin('venders', 'venders.id', '=', 'bills.vender_id')
            ->where('bills.created_by', \Auth::user()->creatorId())
            ->where('bills.type', 'bill')
            ->whereBetween('bills.bill_date', [$start, $end]);
            
        // Final UNION query
        return $billsQuery
            ->union($paymentsQuery)
            ->union($accountsQuery);
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
                'footerCallback' => <<<JS
function (row, data, start, end, display) {
    var api = this.api();
    var parseVal = function (i) {
        return typeof i === 'string'
            ? parseFloat(i.replace(/[^0-9.-]+/g, '')) || 0
            : typeof i === 'number'
                ? i
                : 0;
    };

    // Index 3 is 'total_amount', Index 4 is 'open_balance'
    var totalAmount = api.column(3, { page: 'all' }).data()
        .reduce((a, b) => parseVal(a) + parseVal(b), 0);

    var totalOpen = api.column(4, { page: 'all' }).data()
        .reduce((a, b) => parseVal(a) + parseVal(b), 0);
        
    // FIX: Use toLocaleString for consistent decimal display in footer
    $(api.column(3).footer()).html(totalAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    $(api.column(4).footer()).html(totalOpen.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
}
JS
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('bill_date')->title('Date'),
            Column::make('transaction')->title('Transaction'),
            Column::make('type')->title('Type'),
            Column::make('total_amount')->title('Amount'),
            Column::make('open_balance')->title('Open Balance'),
        ];
    }
}