<?php

namespace App\DataTables;

use App\Models\ChartOfAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ProfitLossComparisonDataTable extends DataTable
{
    protected $startDate;
    protected $endDate;
    protected $prevStartDate;
    protected $prevEndDate;

    protected $companyId;
    protected $owner;

    public function __construct()
    {
        parent::__construct();

        $this->startDate = request('startDate')
            ? Carbon::parse(request('startDate'))->startOfDay()->format('Y-m-d')
            : Carbon::now()->startOfYear()->format('Y-m-d');

        $this->endDate = request('endDate')
            ? Carbon::parse(request('endDate'))->endOfDay()->format('Y-m-d')
            : Carbon::now()->endOfDay()->format('Y-m-d');

        // Previous year same period
        $this->prevStartDate = Carbon::parse($this->startDate)->subYear()->format('Y-m-d');
        $this->prevEndDate = Carbon::parse($this->endDate)->subYear()->format('Y-m-d');

        $this->companyId = \Auth::user()->type === 'company'
            ? \Auth::user()->creatorId()
            : \Auth::user()->ownedId();

        $this->owner = \Auth::user()->type === 'company'
            ? 'created_by'
            : 'owned_by';
    }

    public function dataTable($query)
    {
        return datatables()
            ->collection($query)
            ->addColumn('account_name', function ($row) {
                // Section header
                if ($row->is_section_header ?? false) {
                    $chevronHtml = $row->has_children
                        ? '<i class="fas fa-caret-down toggle-caret mr-2"></i>'
                        : '';

                    return '<span class="toggle-section" data-group="' . $row->group_key . '" style="cursor: ' . ($row->has_children ? 'pointer' : 'default') . ';">
                        ' . $chevronHtml . '
                        <strong class="section-header">' . e($row->name) . '</strong>
                        <span class="section-total-display" data-group="' . $row->group_key . '" style="display: none; font-weight: normal; color: #6c757d; margin-left: 10px;">
                            (' . number_format($row->section_total ?? 0, 2) . ')
                        </span>
                    </span>';
                }

                // Totals
                if ($row->is_total ?? false) {
                    return '<strong class="total-label">' . e($row->name) . '</strong>';
                }
                if ($row->is_subtotal ?? false) {
                    return '<strong class="subtotal-label">' . e($row->name) . '</strong>';
                }

                // Child row
                return ($row->is_child ? '&nbsp;&nbsp;&nbsp;&nbsp;' : '')
                    . ($row->code ? '<span class="account-code">' . e($row->code) . ' - </span> ' : '')
                    . e($row->name);
            })
            ->addColumn('current_amount', function ($row) {
                if ($row->is_section_header ?? false) {
                    return '';
                }
                if ($row->is_total ?? false || $row->is_subtotal ?? false) {
                    return '<strong class="total-amount">' . number_format($row->net ?? 0, 2) . '</strong>';
                }
                return '<span class="amount-cell">' . number_format($row->current_amount ?? 0, 2) . '</span>';
            })
            ->addColumn('previous_amount', function ($row) {
                if ($row->is_section_header ?? false) {
                    return '';
                }
                if ($row->is_total ?? false || $row->is_subtotal ?? false) {
                    return '<strong class="total-amount">' . number_format($row->prev_net ?? 0, 2) . '</strong>';
                }
                return '<span class="amount-cell">' . number_format($row->previous_amount ?? 0, 2) . '</span>';
            })
            ->setRowAttr([
                'class' => function ($row) {
                    if ($row->is_section_header ?? false) return 'section-row';
                    if ($row->is_child ?? false) return 'child-row group-' . $row->group_key;
                    if ($row->is_total ?? false) return 'total-row group-' . ($row->group_key ?? '');
                    if ($row->is_subtotal ?? false) return 'subtotal-row group-' . ($row->group_key ?? '');
                    return '';
                }
            ])
            ->rawColumns(['account_name', 'current_amount', 'previous_amount']);
    }

    public function query()
    {
        $accounts = ChartOfAccount::where('chart_of_accounts.created_by', $this->companyId)
            ->leftJoin('chart_of_account_types', 'chart_of_accounts.type', '=', 'chart_of_account_types.id')
            ->leftJoin('chart_of_account_sub_types', 'chart_of_accounts.sub_type', '=', 'chart_of_account_sub_types.id')
            ->leftJoin('journal_items', 'chart_of_accounts.id', '=', 'journal_items.account')
            ->leftJoin('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
            ->where("journal_entries.{$this->owner}", $this->companyId)
            ->select([
                'chart_of_accounts.id',
                'chart_of_accounts.name',
                'chart_of_accounts.code',
                'chart_of_account_types.name as account_type',
                'chart_of_account_sub_types.name as sub_type',
                DB::raw("COALESCE(SUM(CASE WHEN journal_entries.date BETWEEN '{$this->startDate}' AND '{$this->endDate}' THEN journal_items.debit ELSE 0 END),0) as total_debit"),
                DB::raw("COALESCE(SUM(CASE WHEN journal_entries.date BETWEEN '{$this->startDate}' AND '{$this->endDate}' THEN journal_items.credit ELSE 0 END),0) as total_credit"),
                DB::raw("COALESCE(SUM(CASE WHEN journal_entries.date BETWEEN '{$this->prevStartDate}' AND '{$this->prevEndDate}' THEN journal_items.debit ELSE 0 END),0) as prev_total_debit"),
                DB::raw("COALESCE(SUM(CASE WHEN journal_entries.date BETWEEN '{$this->prevStartDate}' AND '{$this->prevEndDate}' THEN journal_items.credit ELSE 0 END),0) as prev_total_credit"),
            ])
            ->whereIn('chart_of_account_types.name', ['Income', 'Expenses', 'Costs of Goods Sold'])
            ->groupBy(
                'chart_of_accounts.id',
                'chart_of_accounts.name',
                'chart_of_accounts.code',
                'chart_of_account_types.name'
            )
            ->orderBy('chart_of_account_types.name')
            ->get();

        $report = collect();

        // ---------------- INCOME ----------------
        $incomeAccounts = $accounts->where('account_type', 'Income')->map(function ($acc) {
            $acc->group_key = 'income';
            $acc->is_child = true;
            $acc->current_amount = $acc->total_credit - $acc->total_debit;
            $acc->previous_amount = $acc->prev_total_credit - $acc->prev_total_debit;
            return $acc;
        });
        $incomeTotal = $incomeAccounts->sum('current_amount');
        $incomePrevTotal = $incomeAccounts->sum('previous_amount');

        $report->push((object)[
            'name' => 'Income',
            'is_section_header' => true,
            'group_key' => 'income',
            'has_children' => $incomeAccounts->count() > 0,
            'section_total' => $incomeTotal
        ]);
        $report = $report->merge($incomeAccounts);
        $report->push((object)[
            'name' => 'Total Income',
            'is_subtotal' => true,
            'group_key' => 'income',
            'net' => $incomeTotal,
            'prev_net' => $incomePrevTotal
        ]);

        // ---------------- COGS ----------------
        // $cogsAccounts = $accounts->filter(fn($acc) => $acc->account_type === 'Costs of Goods Sold')
        $cogsAccounts = $accounts->filter(function ($acc) {
            return $acc->account_type == 'Costs of Goods Sold' || ($acc->sub_type == 'Costs of Goods Sold');
            })
            ->map(function ($acc) {
                $acc->group_key = 'cogs';
                $acc->is_child = true;
                $acc->current_amount = $acc->total_debit - $acc->total_credit;
                $acc->previous_amount = $acc->prev_total_debit - $acc->prev_total_credit;
                return $acc;
            });
        $cogsTotal = $cogsAccounts->sum('current_amount');
        $cogsPrevTotal = $cogsAccounts->sum('previous_amount');

        $report->push((object)[
            'name' => 'Costs of Goods Sold',
            'is_section_header' => true,
            'group_key' => 'cogs',
            'has_children' => $cogsAccounts->count() > 0,
            'section_total' => $cogsTotal
        ]);
        $report = $report->merge($cogsAccounts);
        $report->push((object)[
            'name' => 'Total Costs of Goods Sold',
            'is_subtotal' => true,
            'group_key' => 'cogs',
            'net' => $cogsTotal,
            'prev_net' => $cogsPrevTotal
        ]);

        // ---------------- GROSS PROFIT ----------------
        $report->push((object)[
            'name' => 'Gross Profit',
            'is_total' => true,
            'group_key' => 'gross_profit',
            'net' => $incomeTotal - $cogsTotal,
            'prev_net' => $incomePrevTotal - $cogsPrevTotal
        ]);

        // ---------------- EXPENSES ----------------
        $expenseAccounts  = $accounts->filter(function ($acc) {
                return $acc->account_type === 'Expenses'
                    && !in_array($acc->sub_type, ['COGS', 'Costs of Goods Sold'])
                    && $acc->account_type !== 'Costs of Goods Sold';
             })
            ->map(function ($acc) {
                $acc->group_key = 'expenses';
                $acc->is_child = true;
                $acc->current_amount = $acc->total_debit - $acc->total_credit;
                $acc->previous_amount = $acc->prev_total_debit - $acc->prev_total_credit;
                return $acc;
            });
        $expenseTotal = $expenseAccounts->sum('current_amount');
        $expensePrevTotal = $expenseAccounts->sum('previous_amount');

        $report->push((object)[
            'name' => 'Expenses',
            'is_section_header' => true,
            'group_key' => 'expenses',
            'has_children' => $expenseAccounts->count() > 0,
            'section_total' => $expenseTotal
        ]);
        $report = $report->merge($expenseAccounts);
        $report->push((object)[
            'name' => 'Total Expenses',
            'is_subtotal' => true,
            'group_key' => 'expenses',
            'net' => $expenseTotal,
            'prev_net' => $expensePrevTotal
        ]);

        // ---------------- NET OPERATING INCOME ----------------
        $report->push((object)[
            'name' => 'Net Operating Income',
            'is_total' => true,
            'group_key' => 'net_operating_income',
            'net' => ($incomeTotal - $cogsTotal) - $expenseTotal,
            'prev_net' => ($incomePrevTotal - $cogsPrevTotal) - $expensePrevTotal
        ]);

        // ---------------- NET INCOME ----------------
        $report->push((object)[
            'name' => 'NET INCOME',
            'is_total' => true,
            'group_key' => 'net_income',
            'net' => ($incomeTotal - $cogsTotal) - $expenseTotal,
            'prev_net' => ($incomePrevTotal - $cogsPrevTotal) - $expensePrevTotal
        ]);

        return $report;
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('profit-loss-table')
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
            Column::make('account_name')->title('Account')->width('50%'),
            Column::make('current_amount')->title('Current (' . date('d-m-Y', strtotime($this->startDate)) . ' to ' . date('d-m-Y', strtotime($this->endDate)) . ')')->width('25%')->addClass('text-right'),
            Column::make('previous_amount')->title('Previous (' . date('d-m-Y', strtotime($this->prevStartDate)) . ' to ' . date('d-m-Y', strtotime($this->prevEndDate)) . ')')->width('25%')->addClass('text-right'),
        ];
    }
}
