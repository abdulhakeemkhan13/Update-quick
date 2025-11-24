<?php

namespace App\DataTables;

use App\Models\ChartOfAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CashFlowDataTable extends DataTable
{
    protected $startDate;
    protected $endDate;
    protected $companyId;
    protected $owner;

    public function __construct()
    {
        $this->startDate = request('startDate')
            ? Carbon::parse(request('startDate'))->startOfDay()->format('Y-m-d')
            : Carbon::now()->startOfMonth()->format('Y-m-d');

        $this->endDate = request('endDate')
            ? Carbon::parse(request('endDate'))->endOfDay()->format('Y-m-d')
            : Carbon::now()->endOfDay()->format('Y-m-d');

        $this->companyId = \Auth::user()->type === 'company' ? \Auth::user()->creatorId() : \Auth::user()->ownedId();
        $this->owner = \Auth::user()->type === 'company' ? 'created_by' : 'owned_by';
    }

    public function dataTable($query)
    {
        return datatables()
            ->collection($query)
            ->addColumn('description', function ($row) {
                if ($row->is_section_header ?? false) {
                    return '<strong class="section-header">' . e($row->name) . '</strong>';
                }
                if ($row->is_subtotal ?? false) {
                    return '<strong class="subtotal-label">' . e($row->name) . '</strong>';
                }
                if ($row->is_total ?? false) {
                    return '<strong class="total-label">' . e($row->name) . '</strong>';
                }
                return ($row->is_child ?? false ? '&nbsp;&nbsp;&nbsp;&nbsp;' : '') . e($row->name);
            })
            ->addColumn('amount', function ($row) {
                $amount = $row->amount ?? 0;
                if ($row->is_section_header ?? false) return '';
                if ($row->is_subtotal ?? false) {
                    return '<strong class="subtotal-amount">' . number_format($amount, 2) . '</strong>';
                }
                if ($row->is_total ?? false) {
                    return '<strong class="total-amount">' . number_format($amount, 2) . '</strong>';
                }
                return $amount == 0 ? '' : '<span class="amount-cell">' . number_format($amount, 2) . '</span>';
            })
            ->addColumn('row_class', function ($row) {
                if ($row->is_section_header ?? false) return 'section-header-row';
                if ($row->is_subtotal ?? false) return 'subtotal-row';
                if ($row->is_total ?? false) return 'total-row';
                if ($row->is_child ?? false) return 'child-row';
                return '';
            })
            ->rawColumns(['description', 'amount']);
    }

    public function query()
    {
        // Get Net Income from P&L
        $netIncome = (float) $this->getNetIncome();

        $report = collect();

        $sections = [
            'operating' => 'CASH FLOWS FROM OPERATING ACTIVITIES',
            'investing' => 'CASH FLOWS FROM INVESTING ACTIVITIES',
            'financing' => 'CASH FLOWS FROM FINANCING ACTIVITIES',
        ];

        $netChange = 0;

        foreach ($sections as $key => $title) {
            $report->push((object)[
                'name' => $title,
                'amount' => 0,
                'is_section_header' => true
            ]);

            $items = $this->getCashFlowByCategory($key);

            if ($key === 'operating') {
                $report->push((object)[
                    'name' => 'Net Income',
                    'amount' => $netIncome,
                    'is_child' => true
                ]);
            }

            foreach ($items as $item) {
                $report->push($item);
            }

            // Special case: Add Owner's Contribution inside Financing
            $ownerContribution = 0;
            if ($key === 'financing') {
                $ownerContribution = DB::table('journal_items')
                    ->join('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
                    ->join('chart_of_accounts', 'journal_items.account', '=', 'chart_of_accounts.id')
                    ->where("journal_entries.{$this->owner}", $this->companyId)
                    ->whereBetween('journal_entries.date', [$this->startDate, $this->endDate])
                    ->where('chart_of_accounts.name', "Owner's Equity")
                    ->select(DB::raw('SUM(journal_items.credit - journal_items.debit) as contribution'))
                    ->value('contribution');

                if ($ownerContribution != 0) {
                    $report->push((object)[
                        'name' => "Owner's Contribution",
                        'amount' => (float) ($ownerContribution ?? 0),
                        'is_child' => true
                    ]);
                }
            }

            // Calculate subtotal
            $subtotal = $items->sum('amount');

            if ($key === 'operating') {
                $subtotal += $netIncome;
            }

            if ($key === 'financing') {
                $subtotal += (float) ($ownerContribution ?? 0);
            }

            $report->push((object)[
                'name' => 'Net Cash ' . ($key === 'operating'
                        ? 'Provided by Operating Activities'
                        : ($key === 'investing'
                            ? 'Used in Investing Activities'
                            : 'Used in Financing Activities')),
                'amount' => $subtotal,
                'is_subtotal' => true
            ]);

            $report->push((object)['name' => '', 'amount' => 0]); // spacing

            $netChange += $subtotal;
        }

        // Final Totals
        $report->push((object)[
            'name' => 'NET INCREASE (DECREASE) IN CASH',
            'amount' => $netChange,
            'is_total' => true
        ]);

        $beginningCash = $this->getBeginningCashBalance();
        $endingCash = $beginningCash + $netChange;

        $report->push((object)[
            'name' => 'Cash at Beginning of Period',
            'amount' => $beginningCash,
            'is_total' => true
        ]);

        $report->push((object)[
            'name' => 'Cash at End of Period',
            'amount' => $endingCash,
            'is_total' => true
        ]);

        return $report;
    }

    private function getNetIncome()
    {
        $income = DB::table('chart_of_accounts')
            ->leftJoin('chart_of_account_sub_types', 'chart_of_accounts.sub_type', '=', 'chart_of_account_sub_types.id')
            ->leftJoin('chart_of_account_types', 'chart_of_account_sub_types.type', '=', 'chart_of_account_types.id')
            ->leftJoin('journal_items', 'chart_of_accounts.id', '=', 'journal_items.account')
            ->leftJoin('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
            ->where("journal_entries.{$this->owner}", $this->companyId)
            ->whereBetween('journal_entries.date', [$this->startDate, $this->endDate])
            ->whereIn('chart_of_account_types.name', ['income', 'expense'])
            ->select([DB::raw('SUM(CASE 
                    WHEN chart_of_account_types.name = "income" 
                        THEN journal_items.credit - journal_items.debit
                    ELSE journal_items.debit - journal_items.credit 
                END) as net_income')])
            ->first();

        return (float) ($income->net_income ?? 0);
    }

    private function getCashFlowByCategory($category)
    {
        // First, get accounts that belong to this cash flow category
        $accounts = DB::table('chart_of_accounts')
            ->join('chart_of_account_sub_types', 'chart_of_accounts.sub_type', '=', 'chart_of_account_sub_types.id')
            ->where("chart_of_accounts.{$this->owner}", $this->companyId)
            ->where('chart_of_account_sub_types.name', 'LIKE', "%{$category}%") // Assuming category is in sub_type name
            ->select('chart_of_accounts.id', 'chart_of_accounts.name', 'chart_of_accounts.parent')
            ->get();

        $items = collect();

        foreach ($accounts as $account) {
            $change = DB::table('journal_items')
                ->join('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
                ->where("journal_entries.{$this->owner}", $this->companyId)
                ->whereBetween('journal_entries.date', [$this->startDate, $this->endDate])
                ->where('journal_items.account', $account->id)
                ->select(DB::raw('SUM(journal_items.debit - journal_items.credit) as change_amount'))
                ->value('change_amount');

            $change = (float) ($change ?? 0);

            // Skip zero or empty entries
            if ($change == 0) {
                continue;
            }

            $items->push((object)[
                'name' => $account->name,
                'amount' => $change,
                'is_child' => !is_null($account->parent)
            ]);
        }

        return $items;
    }

    private function getBeginningCashBalance()
    {
        // Get cash accounts (assuming they have 'cash' in their name or specific type)
        $cashBalance = DB::table('chart_of_accounts')
            ->leftJoin('chart_of_account_sub_types', 'chart_of_accounts.sub_type', '=', 'chart_of_account_sub_types.id')
            ->leftJoin('chart_of_account_types', 'chart_of_account_sub_types.type', '=', 'chart_of_account_types.id')
            ->leftJoin('journal_items', 'chart_of_accounts.id', '=', 'journal_items.account')
            ->leftJoin('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
            ->where("journal_entries.{$this->owner}", $this->companyId)
            ->where('journal_entries.date', '<', $this->startDate)
            ->where(function($query) {
                $query->where('chart_of_accounts.name', 'LIKE', '%cash%')
                      ->orWhere('chart_of_accounts.name', 'LIKE', '%bank%');
            })
            ->select([DB::raw('SUM(journal_items.debit - journal_items.credit) as cash_balance')])
            ->first();

        return (float) ($cashBalance->cash_balance ?? 0);
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('customer-balance-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
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
            Column::make('description')->title('Full Name')->width('70%'),
            Column::make('amount')->title('TOTAL')->width('30%')->addClass('text-right'),
        ];
    }
}