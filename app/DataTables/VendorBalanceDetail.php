<?php

namespace App\DataTables;

use App\Models\Bill;
use Carbon\Carbon;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\DB;

class VendorBalanceDetail extends DataTable
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
        $runningBalance = 0; // 👈 keep track of running balance

        // Group invoices by vendor name
        $groupedData = $data->groupBy(function ($row) {
            return $row->name ?? 'Unknown Vendor';
        });

        $finalData = collect();

        foreach ($groupedData as $vendor => $rows) {
            $subtotalAmount = 0;
            $subtotalOpen = 0;
            $runningBalance = 0; // 👈 reset here for each vendor

            // Vendor header row
            $finalData->push((object) [
                'vendor' => $vendor,
                'id' => null,
                'bill_date' => '',
                'due_date' => '',
                // 'transaction' => '<strong>' . $vendor . '</strong>',
                'transaction' => '<span class="" data-bucket="' . \Str::slug($vendor) . '"> <span class="icon">▼</span> <strong>' . $vendor . '</strong></span>',
                'type' => '',
                'total_amount' => null,
                'open_balance' => null,
                'balance' => null,
                'isPlaceholder' => true,
                'isSubtotal' => false,
                'isParent' => true
            ]);

            foreach ($rows as $row) {
                $subtotalAmount += (float) ($row->subtotal ?? 0) + (float) ($row->total_tax ?? 0);
                $subtotalOpen += (float) $row->open_balance;
                // 👈 running balance logic
                $runningBalance += (float) $row->open_balance;
                $row->balance = $runningBalance;
                $row->vendor = $vendor;
                $finalData->push($row);
            }

            // Vendor subtotal row
            $finalData->push((object) [
                'vendor' => $vendor,
                'id' => null,
                'bill_date' => '',
                'due_date' => '',
                'transaction' => '<strong>Subtotal for ' . $vendor . '</strong>',
                'type' => '',
                'total_amount' => $subtotalAmount,
                'open_balance' => $subtotalOpen,
                'balance' => null,
                'isSubtotal' => true,
            ]);

            $finalData->push((object) [
                'vendor' => $vendor,
                'id' => null,
                'bill_date' => '',
                'due_date' => '',
                'transaction' => '',
                'type' => '',
                'total_amount' => '',
                'open_balance' => '',
                'balance' => '',
                'isPlaceholder' => true,
            ]);

            $grandTotalAmount += $subtotalAmount;
            $grandOpenBalance += $subtotalOpen;
        }

        // Grand total row
        $finalData->push((object) [
            'vendor' => '',
            'id' => null,
            'bill_date' => '',
            'due_date' => '',
            'transaction' => '<strong>Grand Total</strong>',
            'type' => '',
            'total_amount' => $grandTotalAmount,
            'open_balance' => $grandOpenBalance,
            'balance' => null,
            'isGrandTotal' => true,
        ]);

        return datatables()
            ->collection($finalData)
            ->addColumn('bill_date', fn($row) => isset($row->isSubtotal) || isset($row->isGrandTotal) ? '' : $row->bill_date)
            ->addColumn('due_date', fn($row) => isset($row->isSubtotal) || isset($row->isGrandTotal) ? '' : $row->due_date) // 👈 add due date
            ->addColumn('transaction', function ($row) {
                if (isset($row->isSubtotal) || isset($row->isGrandTotal) || (isset($row->isPlaceholder) && $row->isPlaceholder)) {
                    return $row->transaction;
                }
                return \Auth::user()->billNumberFormat($row->bill ?? $row->id);
            })
            ->addColumn('type', function ($row) {
                if (isset($row->isSubtotal) || isset($row->isGrandTotal) || (isset($row->isPlaceholder) && $row->isPlaceholder)) {
                    return '';
                }
                return 'Bill';
            })
            // 👇 FIX: Use number_format(value, 2) to prevent rounding and show decimals
            ->editColumn('total_amount', function ($row) {
                if (isset($row->isPlaceholder)) {
                    return '';
                }
                if (isset($row->isSubtotal) || isset($row->isGrandTotal)) {
                    return number_format($row->total_amount ?? 0, 2); // FIXED
                }
                return number_format(($row->subtotal ?? 0) + ($row->total_tax ?? 0), 2); // FIXED
            })
            // 👇 FIX: Use number_format(value, 2) to prevent rounding and show decimals
            ->editColumn('open_balance', function ($row) {
                if (isset($row->isPlaceholder)) {
                    return '';
                }
                if (isset($row->isSubtotal) || isset($row->isGrandTotal)) {
                    return number_format($row->open_balance ?? 0, 2); // FIXED
                }
                return number_format($row->open_balance ?? 0, 2); // FIXED
            })
            // 👇 FIX: Use number_format(value, 2) to prevent rounding and show decimals
            ->editColumn('balance', function ($row) { // 👈 show running balance
                if (isset($row->isPlaceholder) || isset($row->isSubtotal) || isset($row->isGrandTotal)) {
                    return '';
                }
                return number_format($row->balance ?? 0, 2); // FIXED
            })
            ->setRowClass(function ($row) {
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
     * Get query source of dataTable.
     *
     * @param \App\Models\Bill $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Bill $model)
    {
        $start = request()->get('start_date') ?? request()->get('startDate') ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $end = request()->get('end_date') ?? request()->get('endDate') ?? Carbon::now()->endOfDay()->format('Y-m-d');


    return $model->newQuery()
        ->select(
            'bills.id',
            'bills.bill_id as bill',
            'bills.bill_date',
            'bills.due_date',
            'bills.status',
            'venders.name',

            // --- 1. PRODUCT SUBTOTAL (Subquery) ---
            DB::raw('(
                SELECT COALESCE(SUM(
                    (price * quantity) - COALESCE(discount, 0)
                ), 0)
                FROM bill_products 
                WHERE bill_products.bill_id = bills.id
            ) as product_subtotal'),
            
            // --- 2. ACCOUNTS/EXPENSE TOTAL (Subquery - Already correct) ---
            DB::raw('(
                SELECT COALESCE(SUM(ba.price), 0)
                FROM bill_accounts ba
                WHERE ba.ref_id = bills.id
            ) as account_total'),

            // --- 3. TOTAL TAX CALCULATION (Subquery - Already correct) ---
            DB::raw('(
                SELECT COALESCE(SUM(
                    (price * quantity - COALESCE(discount, 0)) * (taxes.rate / 100)
                ), 0) 
                FROM bill_products 
                LEFT JOIN taxes ON FIND_IN_SET(taxes.id, bill_products.tax) > 0
                WHERE bill_products.bill_id = bills.id
            ) as total_tax'),

            // --- 4. TOTAL BILL AMOUNT (Combined Calculation) ---
            // Sum of (Product Subtotal) + (Account Total) + (Total Tax)
            DB::raw('(
                (
                    SELECT COALESCE(SUM(
                        (bp.price * bp.quantity) - COALESCE(bp.discount, 0)
                    ), 0)
                    FROM bill_products bp
                    WHERE bp.bill_id = bills.id
                )
                + (
                    SELECT COALESCE(SUM(ba.price), 0)
                    FROM bill_accounts ba
                    WHERE ba.ref_id = bills.id
                )
                + (
                    SELECT COALESCE(SUM(
                        (bp2.price * bp2.quantity - COALESCE(bp2.discount, 0)) * (t.rate / 100)
                    ), 0) 
                    FROM bill_products bp2
                    LEFT JOIN taxes t ON FIND_IN_SET(t.id, bp2.tax) > 0
                    WHERE bp2.bill_id = bills.id
                )
            ) as total_amount'),

            // --- 5. PAYMENTS TOTAL (Subquery - Changed from SUM/JOIN to Subquery) ---
            DB::raw('(
                SELECT COALESCE(SUM(amount), 0)
                FROM bill_payments
                WHERE bill_payments.bill_id = bills.id
            ) as pay_price'),
            
            // --- 6. DEBIT NOTES/CREDIT TOTAL (Subquery - Already correct) ---
            DB::raw('(
                SELECT COALESCE(SUM(debit_notes.amount), 0) 
                FROM debit_notes 
                WHERE debit_notes.bill = bills.id
            ) as debit_price'),

            // --- 7. OPEN BALANCE (Total Amount - Payments - Debit Notes) ---
            // This calculation now uses the 'total_amount', 'pay_price', and 'debit_price' fields from above
            // which are calculated correctly via subqueries.
            DB::raw('(
                (
                    (
                        SELECT COALESCE(SUM((bp.price * bp.quantity) - COALESCE(bp.discount, 0)), 0)
                        FROM bill_products bp WHERE bp.bill_id = bills.id
                    )
                    + (
                        SELECT COALESCE(SUM(ba.price), 0)
                        FROM bill_accounts ba WHERE ba.ref_id = bills.id
                    )
                    + (
                        SELECT COALESCE(SUM((bp2.price * bp2.quantity - COALESCE(bp2.discount, 0)) * (t.rate / 100)), 0) 
                        FROM bill_products bp2 LEFT JOIN taxes t ON FIND_IN_SET(t.id, bp2.tax) > 0 WHERE bp2.bill_id = bills.id
                    )
                ) - (
                    (
                        SELECT COALESCE(SUM(amount), 0)
                        FROM bill_payments WHERE bill_payments.bill_id = bills.id
                    )
                    + (
                        SELECT COALESCE(SUM(debit_notes.amount), 0) 
                        FROM debit_notes WHERE debit_notes.bill = bills.id
                    )
                )
            ) as open_balance')
        )
        // ❌ REMOVE unnecessary joins that caused aggregation issues: bill_products, bill_payments
        ->leftJoin('venders', 'venders.id', '=', 'bills.vender_id') 

        ->where('bills.created_by', \Auth::user()->creatorId())
        ->where('bills.type', 'bill')->where('bills.status', '!=','4')
        ->whereBetween('bills.bill_date', [$start, $end])
        // Since we removed the problematic joins, the GROUP BY only needs the main columns.
        ->groupBy('bills.id', 'bills.bill_id', 'bills.bill_date', 'bills.due_date', 'bills.status', 'venders.name') 
        ->orderBy('bills.bill_date', 'desc');
    }

    /**
     * Optional method if you want to use the html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
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
                // The JS logic already correctly uses toLocaleString for 2 decimals
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

    var totalAmount = api.column(4, { page: 'all' }).data()
        .reduce((a, b) => parseVal(a) + parseVal(b), 0);

    var totalOpen = api.column(5, { page: 'all' }).data()
        .reduce((a, b) => parseVal(a) + parseVal(b), 0);

    // ✅ Show as decimal (2 digits)
    $(api.column(4).footer()).html(
        totalAmount.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        })
    );

    $(api.column(5).footer()).html(
        totalOpen.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        })
    );
}
JS
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
            Column::make('type')->title('Type'),
            Column::make('due_date')->title('Due Date'), // 👈 add due date column
            Column::make('total_amount')->title('Amount'),
            Column::make('open_balance')->title('Open Balance'),
            Column::make('balance')->title('Balance'), // 👈 new running balance column
        ];
    }
}