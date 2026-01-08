<?php

namespace App\DataTables;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
class IncomeByCustomerSummaryTwoDataTable extends DataTable
{
    public function dataTable($query)
    {
        $user = Auth::user();

        $rows = collect($query->get());

        // Calculate per-row net income
        $rows = $rows->map(function ($r) {
            $r->net_income = ($r->income ?? 0) - ($r->expenses ?? 0);
            return $r;
        });

        // Calculate grand totals
        $grandIncome = $rows->sum('income');
        $grandExpenses = $rows->sum('expenses');
        $grandNet = $grandIncome - $grandExpenses;

        // Push grand total row
        $rows->push((object) [
            'customer' => '<strong>Grand Total</strong>',
            'income' => $grandIncome,
            'expenses' => $grandExpenses,
            'net_income' => $grandNet,
            'isGrandTotal' => true,
        ]);

        return datatables()
            ->collection($rows)
            ->addColumn('customer', fn($r) => $r->isGrandTotal ?? false ? $r->customer : ($r->name ?? '-'))
            ->addColumn('income', fn($r) => number_format($r->income ?? 0, 2))
            ->addColumn('expenses', fn($r) => number_format($r->expenses ?? 0, 2))
            ->addColumn('net_income', fn($r) => number_format($r->net_income ?? 0, 2))
            ->rawColumns(['customer', 'income', 'expenses', 'net_income']);
    }


    // public function query()
    // {
    //     $user = Auth::user();
    //     $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
    //     $column = ($user->type == 'company') ? 'created_by' : 'owned_by';

    //     // Get start and end dates from request, fallback to defaults
    //     $startDate = request()->get('start_date')
    //         ?? request()->get('startDate')
    //         ?? date('Y-01-01');
    //     $endDate = request()->get('end_date')
    //         ?? request()->get('endDate')
    //         ?? date('Y-m-d');
    //     $reportPeriod = request('report_period', 'all_dates');

    //     // Calculate date range based on report period (only if not 'all_dates')
    //     if ($reportPeriod && $reportPeriod !== 'all_dates' && $reportPeriod !== 'custom') {
    //         $dates = $this->calculateDateRange($reportPeriod);
    //         $startDate = $dates['start'];
    //         $endDate = $dates['end'];
    //     }

    //     // Debug the date values
    //     \Log::info('Date Filters Applied:', [
    //         'report_period' => $reportPeriod,
    //         'start_date' => $startDate,
    //         'end_date' => $endDate,
    //         'request_start' => request('start_date'),
    //         'request_end' => request('end_date')
    //     ]);

    //     /**
    //      * ===== FIXED: Using Eloquent Customer model with proper subqueries =====
    //      */

    //     // Create subquery for income calculation
    //     $incomeSubquery = DB::table('invoices as i')
    //         ->join('invoice_products as ip', 'ip.invoice_id', '=', 'i.id')
    //         ->select(
    //             'i.customer_id',
    //             // DB::raw('SUM(
    //             //     (ip.price * ip.quantity - COALESCE(ip.discount, 0)) + 
    //             //     COALESCE((
    //             //         SELECT SUM((ipp.price * ipp.quantity - COALESCE(ipp.discount, 0)) * (COALESCE(t.rate, 0) / 100))
    //             //         FROM invoice_products ipp
    //             //         LEFT JOIN taxes t ON FIND_IN_SET(t.id, ipp.tax) > 0
    //             //         WHERE ipp.id = ip.id
    //             //     ), 0)
    //             // ) as income')
    //             DB::raw('SUM(ip.price * ip.quantity - COALESCE(ip.discount, 0)) as income')

    //         )
    //         ->where('i.created_by', $ownerId)
    //         ->where('i.status', '!=', 0);

    //     // Apply date filters to income subquery (only if dates are provided)
    //     if ($startDate && $startDate !== '') {
    //         $incomeSubquery->whereDate('i.issue_date', '>=', $startDate);
    //         \Log::info('Applied income start date filter: ' . $startDate);
    //     }
    //     if ($endDate && $endDate !== '') {
    //         $incomeSubquery->whereDate('i.issue_date', '<=', $endDate);
    //         \Log::info('Applied income end date filter: ' . $endDate);
    //     }

    //     $incomeSubquery->groupBy('i.customer_id');

    //     // Create subquery for expenses calculation
    //     $expenseSubquery = DB::table('bills as b')
    //         ->leftJoin('bill_products as bp', 'bp.bill_id', '=', 'b.id')
    //         ->leftJoin('bill_accounts as ba', 'ba.ref_id', '=', 'b.id')
    //         ->select(
    //             'b.vender_id as customer_id',
    //             // DB::raw('SUM(
    //             //     COALESCE((bp.price * bp.quantity - COALESCE(bp.discount, 0)), 0) + 
    //             //     COALESCE((
    //             //         SELECT SUM((bpp.price * bpp.quantity - COALESCE(bpp.discount, 0)) * (COALESCE(t.rate, 0) / 100))
    //             //         FROM bill_products bpp
    //             //         LEFT JOIN taxes t ON FIND_IN_SET(t.id, bpp.tax) > 0
    //             //         WHERE bpp.id = bp.id
    //             //     ), 0) +
    //             //     COALESCE(ba.price, 0)
    //             // ) as expenses')
    //             DB::raw('SUM(COALESCE(bp.price * bp.quantity - COALESCE(bp.discount, 0), 0) + COALESCE(ba.price, 0)) as expenses')
    //         )
    //         ->where('b.created_by', $ownerId)
    //         ->where('b.user_type', 'customer')
    //         ->where('b.status', '!=', 0);

    //     // Apply date filters to expense subquery (only if dates are provided)
    //     if ($startDate && $startDate !== '') {
    //         $expenseSubquery->whereDate('b.bill_date', '>=', $startDate);
    //         \Log::info('Applied expense start date filter: ' . $startDate);
    //     }
    //     if ($endDate && $endDate !== '') {
    //         $expenseSubquery->whereDate('b.bill_date', '<=', $endDate);
    //         \Log::info('Applied expense end date filter: ' . $endDate);
    //     }

    //     $expenseSubquery->groupBy('b.vender_id');

    //     // Main query using Eloquent Customer model
    //     $model = new \App\Models\Customer();
    //     $q = $model->newQuery()
    //         ->where('customers.' . $column, $ownerId)
    //         ->leftJoinSub($incomeSubquery, 'inc', function ($join) {
    //             $join->on('customers.id', '=', 'inc.customer_id');
    //         })
    //         ->leftJoinSub($expenseSubquery, 'exp', function ($join) {
    //             $join->on('customers.id', '=', 'exp.customer_id');
    //         })
    //         ->select([
    //             'customers.*',
    //             DB::raw('COALESCE(inc.income, 0) as income'),
    //             DB::raw('COALESCE(exp.expenses, 0) as expenses'),
    //         ]);

    //     // Apply customer name filter if provided
    //     if (request()->filled('customer_name') && request('customer_name') !== '') {
    //         $q->where('customers.name', 'like', '%' . request('customer_name') . '%');
    //     }

    //     return $q;
    // }

    public function query()
    {
        $user = Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = ($user->type == 'company') ? 'created_by' : 'owned_by';

        // Handle date filters
        $startDate = request()->get('start_date') ?? request()->get('startDate') ?? date('Y-01-01');
        $endDate = request()->get('end_date') ?? request()->get('endDate') ?? date('Y-m-d');
        $reportPeriod = request('report_period', 'all_dates');

        if ($reportPeriod && $reportPeriod !== 'all_dates' && $reportPeriod !== 'custom') {
            $dates = $this->calculateDateRange($reportPeriod);
            $startDate = $dates['start'];
            $endDate = $dates['end'];
        }

        /**
         * ===== INCOME SUBQUERY (Invoices + Sales Receipts) =====
         */
        // Invoice income subquery
        $invoiceIncomeQuery = DB::table('invoices as i')
            ->leftJoin('invoice_products as ip', 'ip.invoice_id', '=', 'i.id')
            ->select(
                'i.customer_id',
                DB::raw('COALESCE(SUM(ip.price * ip.quantity - COALESCE(ip.discount, 0)), COALESCE(i.subtotal, 0)) as income')
            )
            ->where('i.created_by', $ownerId)
            ->where('i.status', '!=', 0);

        if ($startDate) {
            $invoiceIncomeQuery->whereDate('i.issue_date', '>=', $startDate);
        }
        if ($endDate) {
            $invoiceIncomeQuery->whereDate('i.issue_date', '<=', $endDate);
        }

        $invoiceIncomeQuery->groupBy('i.customer_id');

        // Sales Receipt income subquery
        $salesReceiptIncomeQuery = DB::table('sales_receipts as sr')
            ->leftJoin('sales_receipt_products as srp', 'srp.sales_receipt_id', '=', 'sr.id')
            ->select(
                'sr.customer_id',
                DB::raw('COALESCE(SUM(srp.price * srp.quantity - COALESCE(srp.discount, 0)), COALESCE(sr.total_amount, 0)) as income')
            )
            ->where('sr.created_by', $ownerId)
            ->where('sr.status', '!=', 0);

        if ($startDate) {
            $salesReceiptIncomeQuery->whereDate('sr.issue_date', '>=', $startDate);
        }
        if ($endDate) {
            $salesReceiptIncomeQuery->whereDate('sr.issue_date', '<=', $endDate);
        }

        $salesReceiptIncomeQuery->groupBy('sr.customer_id');

        // Combine invoices and sales receipts
        $incomeSubquery = DB::table(DB::raw("(
            SELECT customer_id, income FROM ({$invoiceIncomeQuery->toSql()}) as inv_income
            UNION ALL
            SELECT customer_id, income FROM ({$salesReceiptIncomeQuery->toSql()}) as sr_income
        ) as combined_income"))
            ->mergeBindings($invoiceIncomeQuery)
            ->mergeBindings($salesReceiptIncomeQuery)
            ->select('customer_id', DB::raw('SUM(income) as income'))
            ->groupBy('customer_id');

        /**
         * ===== EXPENSES SUBQUERY =====
         * Bills will now be counted as expenses.
         */
        $expenseSubquery = DB::table('bills as b')
            ->leftJoin('bill_products as bp', 'bp.bill_id', '=', 'b.id')
            ->leftJoin('bill_accounts as ba', 'ba.ref_id', '=', 'b.id')
            ->select(
                'b.vender_id as customer_id',
                // Use bill total as fallback if no products/accounts
                DB::raw('SUM(
                    CASE 
                        WHEN (bp.id IS NOT NULL OR ba.id IS NOT NULL) 
                        THEN COALESCE(bp.price * bp.quantity - COALESCE(bp.discount, 0), 0) + COALESCE(ba.price, 0)
                        ELSE COALESCE(b.total, 0)
                    END
                ) as expenses')
            )
            ->where('b.created_by', $ownerId)
            // Case-insensitive check for 'customer' user_type
            ->whereRaw('LOWER(b.user_type) = ?', ['customer'])
            ->where('b.status', '!=', 0);

        if ($startDate) {
            $expenseSubquery->whereDate('b.bill_date', '>=', $startDate);
        }
        if ($endDate) {
            $expenseSubquery->whereDate('b.bill_date', '<=', $endDate);
        }

        $expenseSubquery->groupBy('b.vender_id');

        /**
         * ===== MAIN QUERY =====
         */
        $model = new Customer();
        $q = $model->newQuery()
            ->where('customers.' . $column, $ownerId)
            ->leftJoinSub($incomeSubquery, 'inc', function ($join) {
                $join->on('customers.id', '=', 'inc.customer_id');
            })
            ->leftJoinSub($expenseSubquery, 'exp', function ($join) {
                $join->on('customers.id', '=', 'exp.customer_id');
            })
            ->select([
                'customers.*',
                DB::raw('COALESCE(inc.income, 0) as income'),
                DB::raw('COALESCE(exp.expenses, 0) as expenses'),
            ]);

        if (request()->filled('customer_name') && request('customer_name') !== '') {
            $q->where('customers.name', 'like', '%' . request('customer_name') . '%');
        }

        return $q;
    }

    private function calculateDateRange($period)
    {
        $today = \Carbon\Carbon::today();

        switch ($period) {
            case 'today':
                return ['start' => $today->format('Y-m-d'), 'end' => $today->format('Y-m-d')];
            case 'this_week':
                return ['start' => $today->startOfWeek()->format('Y-m-d'), 'end' => $today->endOfWeek()->format('Y-m-d')];
            case 'this_month':
                return ['start' => $today->startOfMonth()->format('Y-m-d'), 'end' => $today->endOfMonth()->format('Y-m-d')];
            case 'this_quarter':
                return ['start' => $today->startOfQuarter()->format('Y-m-d'), 'end' => $today->endOfQuarter()->format('Y-m-d')];
            case 'this_year':
                return ['start' => $today->startOfYear()->format('Y-m-d'), 'end' => $today->endOfYear()->format('Y-m-d')];
            case 'last_week':
                $lastWeek = $today->subWeek();
                return ['start' => $lastWeek->startOfWeek()->format('Y-m-d'), 'end' => $lastWeek->endOfWeek()->format('Y-m-d')];
            case 'last_month':
                $lastMonth = $today->subMonth();
                return ['start' => $lastMonth->startOfMonth()->format('Y-m-d'), 'end' => $lastMonth->endOfMonth()->format('Y-m-d')];
            case 'last_quarter':
                $lastQuarter = $today->subQuarter();
                return ['start' => $lastQuarter->startOfQuarter()->format('Y-m-d'), 'end' => $lastQuarter->endOfQuarter()->format('Y-m-d')];
            case 'last_year':
                $lastYear = $today->subYear();
                return ['start' => $lastYear->startOfYear()->format('Y-m-d'), 'end' => $lastYear->endOfYear()->format('Y-m-d')];
            case 'last_7_days':
                return ['start' => $today->subDays(7)->format('Y-m-d'), 'end' => \Carbon\Carbon::today()->format('Y-m-d')];
            case 'last_30_days':
                return ['start' => $today->subDays(30)->format('Y-m-d'), 'end' => \Carbon\Carbon::today()->format('Y-m-d')];
            case 'last_90_days':
                return ['start' => $today->subDays(90)->format('Y-m-d'), 'end' => \Carbon\Carbon::today()->format('Y-m-d')];
            case 'last_12_months':
                return ['start' => $today->subMonths(12)->format('Y-m-d'), 'end' => \Carbon\Carbon::today()->format('Y-m-d')];
            default:
                return ['start' => null, 'end' => null];
        }
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('customer-balance-table') // keep existing id so CSS/JS keep working
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('rt')
            ->parameters([
                'responsive' => true,
                'autoWidth' => false,
                'paging' => false,
                'searching' => false,
                'info' => false,
                'ordering' => false,
                'colReorder' => true,
                'fixedHeader' => true, // Enable fixed header
                'scrollY' => '400px', // Set scroll height for data area
                'scrollX' => false,
                'scrollCollapse' => true,
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('customer')->data('customer')->name('customer_name')->title(__('Customer')),
            Column::make('income')->data('income')->name('income')->title(__('Income'))->addClass('text-right'),
            Column::make('expenses')->data('expenses')->name('expenses')->title(__('Expenses'))->addClass('text-right'),
            Column::make('net_income')->data('net_income')->name('net_income')->title(__('Net Income'))->addClass('text-right'),
        ];
    }

    protected function filename(): string
    {
        return 'IncomeByCustomerSummary_' . date('YmdHis');
    }
}
