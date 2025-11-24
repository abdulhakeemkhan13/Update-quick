<?php

namespace App\DataTables;

use App\Models\ChartOfAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ProfitLossByMonth extends DataTable
{
    protected $startDate;
    protected $endDate;
    protected $companyId;
    protected $owner;
    protected $monthColumns = [];

    public function __construct()
    {
        parent::__construct();

        // Default: 2 months ago to today
        $this->startDate = request('startDate')
            ? Carbon::parse(request('startDate'))->startOfDay()->format('Y-m-d')
            : Carbon::now()->startOfYear()->format('Y-m-d');

        $this->endDate = request('endDate')
            ? Carbon::parse(request('endDate'))->endOfDay()->format('Y-m-d')
            : Carbon::now()->endOfDay()->format('Y-m-d');

        $this->companyId = \Auth::user()->type === 'company' ? \Auth::user()->creatorId() : \Auth::user()->ownedId();
        $this->owner = \Auth::user()->type === 'company' ? 'created_by' : 'owned_by';
        
        // Generate month columns between start and end date
        $this->generateMonthColumns();
    }

    protected function generateMonthColumns()
    {
        $start = Carbon::parse($this->startDate)->startOfMonth();
        $end = Carbon::parse($this->endDate)->endOfMonth();
        
        $current = $start->copy();
        while ($current->lte($end)) {
            $this->monthColumns[] = [
                'key' => $current->format('Y-m'),
                'label' => $current->format('M Y'),
                'start' => $current->copy()->startOfMonth()->format('Y-m-d'),
                'end' => $current->copy()->endOfMonth()->format('Y-m-d')
            ];
            $current->addMonth();
        }
    }

    public function dataTable($query)
    {
        $datatable = datatables()
            ->collection($query)
            ->addColumn('account_name', function ($row) {
                $isSection = isset($row->is_section_header) && $row->is_section_header;
                $isTotal = isset($row->is_total) && $row->is_total;
                $isSubtotal = isset($row->is_subtotal) && $row->is_subtotal;
                $isChild = isset($row->is_child) && $row->is_child;
                
                if ($isSection) {
                    $chevronHtml = '';
                    $hasChildren = isset($row->has_children) && $row->has_children;
                    if ($hasChildren) {
                        $chevronHtml = '<i class="fas fa-caret-down toggle-chevron mr-2"></i>';
                    }
                    
                    return '<span class="toggle-section" data-group="' . ($row->group_key ?? '') . '" style="cursor: ' . ($hasChildren ? 'pointer' : 'default') . ';">
                        ' . $chevronHtml . '
                        <strong class="section-header">' . e($row->name) . '</strong>
                    </span>';
                }

                if ($isTotal) {
                    return '<strong class="total-label">' . e($row->name) . '</strong>';
                }
                if ($isSubtotal) {
                    return '<strong class="subtotal-label">' . e($row->name) . '</strong>';
                }

                return ($isChild ? '&nbsp;&nbsp;&nbsp;&nbsp;' : '')
                    . (isset($row->code) && $row->code ? '<span class="account-code">' . e($row->code) . ' - </span> ' : '')
                    . e($row->name);
            });

        // Add dynamic month columns
        foreach ($this->monthColumns as $month) {
            $datatable->addColumn($month['key'], function ($row) use ($month) {
                $isSection = isset($row->is_section_header) && $row->is_section_header;
                $isTotal = isset($row->is_total) && $row->is_total;
                $isSubtotal = isset($row->is_subtotal) && $row->is_subtotal;
                
                $monthlyAmounts = isset($row->monthly_amounts) && is_array($row->monthly_amounts) ? $row->monthly_amounts : [];
                $amount = $monthlyAmounts[$month['key']] ?? 0;
                
                if ($isSection) {
                    // Show section total when collapsed (initially hidden)
                    return '<span class="section-month-total" data-group="' . ($row->group_key ?? '') . '" style="display: none; font-weight: bold;">' 
                        . number_format($amount, 2) . '</span>';
                }
                
                if ($isTotal || $isSubtotal) {
                    return '<strong class="total-amount">' . number_format($amount, 2) . '</strong>';
                }

                if ($amount == 0) {
                    return '';
                }

                return '<span class="amount-cell">' . number_format($amount, 2) . '</span>';
            });
        }

        // Add total column
        $datatable->addColumn('total', function ($row) {
            $isSection = isset($row->is_section_header) && $row->is_section_header;
            $isTotal = isset($row->is_total) && $row->is_total;
            $isSubtotal = isset($row->is_subtotal) && $row->is_subtotal;
            
            $total = isset($row->total_amount) ? $row->total_amount : 0;
            
            if ($isSection) {
                // Show section total when collapsed (initially hidden)
                return '<span class="section-total-amount" data-group="' . ($row->group_key ?? '') . '" style="display: none; font-weight: bold;">' 
                    . number_format($total, 2) . '</span>';
            }
            
            if ($isTotal || $isSubtotal) {
                return '<strong class="total-amount">' . number_format($total, 2) . '</strong>';
            }

            if ($total == 0) {
                return '';
            }

            return '<span class="amount-cell">' . number_format($total, 2) . '</span>';
        });

        return $datatable
            ->setRowAttr([
                'class' => function ($row) {
                    $isSection = isset($row->is_section_header) && $row->is_section_header;
                    $isChild = isset($row->is_child) && $row->is_child;
                    $isTotal = isset($row->is_total) && $row->is_total;
                    $isSubtotal = isset($row->is_subtotal) && $row->is_subtotal;
                    
                    if ($isSection) {
                        return 'section-row';
                    }
                    if ($isChild) {
                        return 'child-row group-' . ($row->group_key ?? '');
                    }
                    if ($isTotal) {
                        return 'total-row group-' . ($row->group_key ?? '');
                    }
                    if ($isSubtotal) {
                        return 'subtotal-row group-' . ($row->group_key ?? '');
                    }
                    return '';
                }
            ])
            ->rawColumns(array_merge(['account_name'], array_column($this->monthColumns, 'key'), ['total']));
    }

    public function query()
    {
        // Get accounts with monthly breakdown
        $accountsData = [];
        
        foreach ($this->monthColumns as $month) {
            $monthAccounts = ChartOfAccount::where('chart_of_accounts.created_by', $this->companyId)
                ->leftJoin('chart_of_account_types', 'chart_of_accounts.type', '=', 'chart_of_account_types.id')
                ->leftJoin('chart_of_account_sub_types', 'chart_of_accounts.sub_type', '=', 'chart_of_account_sub_types.id')
                ->leftJoin('journal_items', 'chart_of_accounts.id', '=', 'journal_items.account')
                ->leftJoin('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
                ->where("journal_entries.{$this->owner}", $this->companyId)
                ->whereBetween('journal_entries.date', [$month['start'], $month['end']])
                ->select([
                    'chart_of_accounts.id',
                    'chart_of_accounts.name',
                    'chart_of_accounts.code',
                    'chart_of_account_types.name as account_type',
                    'chart_of_account_sub_types.name as sub_type',
                    DB::raw('COALESCE(SUM(journal_items.debit), 0) as total_debit'),
                    DB::raw('COALESCE(SUM(journal_items.credit), 0) as total_credit'),
                ])
                ->whereIn('chart_of_account_types.name', ['Income', 'Expenses', 'Costs of Goods Sold'])
                ->groupBy('chart_of_accounts.id', 'chart_of_accounts.name', 'chart_of_accounts.code', 'chart_of_account_types.name')
                ->get();

            foreach ($monthAccounts as $acc) {
                $key = $acc->id;
                if (!isset($accountsData[$key])) {
                    $accountsData[$key] = [
                        'id' => $acc->id,
                        'name' => $acc->name,
                        'code' => $acc->code,
                        'sub_type' => $acc->sub_type,
                        'account_type' => $acc->account_type,
                        'monthly_amounts' => []
                    ];
                }
                
                $amount = ($acc->account_type === 'Income') 
                    ? ($acc->total_credit - $acc->total_debit)
                    : ($acc->total_debit - $acc->total_credit);
                    
                $accountsData[$key]['monthly_amounts'][$month['key']] = $amount;
            }
        }

        // Initialize empty monthly amounts for all months
        $emptyMonthlyAmounts = [];
        foreach ($this->monthColumns as $month) {
            $emptyMonthlyAmounts[$month['key']] = 0;
        }

        $report = collect();

        // ---------------- INCOME ----------------
        $incomeAccounts = collect($accountsData)->filter(function($acc) {
            return $acc['account_type'] === 'Income';
        })->map(function ($acc) use ($emptyMonthlyAmounts) {
            $acc = (object) $acc;
            $acc->group_key = 'income';
            $acc->is_child = true;
            $acc->is_section_header = false;
            $acc->is_subtotal = false;
            $acc->is_total = false;
            $acc->monthly_amounts = array_merge($emptyMonthlyAmounts, $acc->monthly_amounts ?? []);
            $acc->total_amount = array_sum($acc->monthly_amounts);
            return $acc;
        });
        
        $incomeMonthlyTotals = [];
        foreach ($this->monthColumns as $month) {
            $incomeMonthlyTotals[$month['key']] = $incomeAccounts->sum(function($acc) use ($month) {
                return $acc->monthly_amounts[$month['key']] ?? 0;
            });
        }
        $incomeTotal = array_sum($incomeMonthlyTotals);

        $report->push((object) [
            'name' => 'Income',
            'is_section_header' => true,
            'is_subtotal' => false,
            'is_total' => false,
            'is_child' => false,
            'group_key' => 'income',
            'has_children' => $incomeAccounts->count() > 0,
            'monthly_amounts' => $incomeMonthlyTotals,
            'total_amount' => $incomeTotal
        ]);
        $report = $report->merge($incomeAccounts);
        $report->push((object) [
            'name' => 'Total Income',
            'is_subtotal' => true,
            'is_section_header' => false,
            'is_total' => false,
            'is_child' => false,
            'group_key' => 'income',
            'monthly_amounts' => $incomeMonthlyTotals,
            'total_amount' => $incomeTotal
        ]);

        // ---------------- COGS ----------------
        $cogsAccounts = collect($accountsData)->filter(function($acc) {
           return $acc['account_type'] == 'Costs of Goods Sold' || $acc['sub_type'] == 'Costs of Goods Sold';
        })->map(function ($acc) use ($emptyMonthlyAmounts) {
            $acc = (object) $acc;
            $acc->group_key = 'cogs';
            $acc->is_child = true;
            $acc->is_section_header = false;
            $acc->is_subtotal = false;
            $acc->is_total = false;
            $acc->monthly_amounts = array_merge($emptyMonthlyAmounts, $acc->monthly_amounts ?? []);
            $acc->total_amount = array_sum($acc->monthly_amounts);
            return $acc;
        });
        
        $cogsMonthlyTotals = [];
        foreach ($this->monthColumns as $month) {
            $cogsMonthlyTotals[$month['key']] = $cogsAccounts->sum(function($acc) use ($month) {
                return $acc->monthly_amounts[$month['key']] ?? 0;
            });
        }
        $cogsTotal = array_sum($cogsMonthlyTotals);

        $report->push((object) [
            'name' => 'Costs of Goods Sold',
            'is_section_header' => true,
            'is_subtotal' => false,
            'is_total' => false,
            'is_child' => false,
            'group_key' => 'cogs',
            'has_children' => $cogsAccounts->count() > 0,
            'monthly_amounts' => $cogsMonthlyTotals,
            'total_amount' => $cogsTotal
        ]);
        $report = $report->merge($cogsAccounts);
        $report->push((object) [
            'name' => 'Total Costs of Goods Sold',
            'is_subtotal' => true,
            'is_section_header' => false,
            'is_total' => false,
            'is_child' => false,
            'group_key' => 'cogs',
            'monthly_amounts' => $cogsMonthlyTotals,
            'total_amount' => $cogsTotal
        ]);

        // ---------------- GROSS PROFIT ----------------
        $grossProfitMonthly = [];
        foreach ($this->monthColumns as $month) {
            $grossProfitMonthly[$month['key']] = ($incomeMonthlyTotals[$month['key']] ?? 0) - ($cogsMonthlyTotals[$month['key']] ?? 0);
        }
        
        $report->push((object) [
            'name' => 'Gross Profit',
            'is_total' => true,
            'is_section_header' => false,
            'is_subtotal' => false,
            'is_child' => false,
            'monthly_amounts' => $grossProfitMonthly,
            'total_amount' => $incomeTotal - $cogsTotal
        ]);

        // ---------------- EXPENSES ----------------
    
        $expenseAccounts = collect($accountsData)
            ->filter(function ($acc) {
                $accountType = $acc['account_type'] ?? null;
                $subType = $acc['sub_type'] ?? null;

                return $accountType === 'Expenses'
                    && !in_array($subType, ['COGS', 'Costs of Goods Sold'])
                    && $accountType !== 'Costs of Goods Sold';
            })
            ->map(function ($acc) use ($emptyMonthlyAmounts) {
                $acc = (object) $acc;
                $acc->group_key = 'expenses';
                $acc->is_child = true;
                $acc->is_section_header = false;
                $acc->is_subtotal = false;
                $acc->is_total = false;
                $acc->monthly_amounts = array_merge($emptyMonthlyAmounts, $acc->monthly_amounts ?? []);
                $acc->total_amount = array_sum($acc->monthly_amounts);
                return $acc;
            });

        
        $expenseMonthlyTotals = [];
        foreach ($this->monthColumns as $month) {
            $expenseMonthlyTotals[$month['key']] = $expenseAccounts->sum(function($acc) use ($month) {
                return $acc->monthly_amounts[$month['key']] ?? 0;
            });
        }
        $expenseTotal = array_sum($expenseMonthlyTotals);

        $report->push((object) [
            'name' => 'Expenses',
            'is_section_header' => true,
            'is_subtotal' => false,
            'is_total' => false,
            'is_child' => false,
            'group_key' => 'expenses',
            'has_children' => $expenseAccounts->count() > 0,
            'monthly_amounts' => $expenseMonthlyTotals,
            'total_amount' => $expenseTotal
        ]);
        $report = $report->merge($expenseAccounts);
        $report->push((object) [
            'name' => 'Total Expenses',
            'is_subtotal' => true,
            'is_section_header' => false,
            'is_total' => false,
            'is_child' => false,
            'group_key' => 'expenses',
            'monthly_amounts' => $expenseMonthlyTotals,
            'total_amount' => $expenseTotal
        ]);

        // ---------------- NET OPERATING INCOME ----------------
        $netOperatingMonthly = [];
        foreach ($this->monthColumns as $month) {
            $netOperatingMonthly[$month['key']] = ($grossProfitMonthly[$month['key']] ?? 0) - ($expenseMonthlyTotals[$month['key']] ?? 0);
        }
        
        $report->push((object) [
            'name' => 'Net Operating Income',
            'is_total' => true,
            'is_section_header' => false,
            'is_subtotal' => false,
            'is_child' => false,
            'monthly_amounts' => $netOperatingMonthly,
            'total_amount' => ($incomeTotal - $cogsTotal) - $expenseTotal
        ]);

        // ---------------- NET INCOME ----------------
        $report->push((object) [
            'name' => 'NET INCOME',
            'is_total' => true,
            'is_section_header' => false,
            'is_subtotal' => false,
            'is_child' => false,
            'monthly_amounts' => $netOperatingMonthly,
            'total_amount' => ($incomeTotal - $cogsTotal) - $expenseTotal
        ]);

        return $report;
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('profit-loss-table')
            // ->setTableId('customer-balance-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'paging' => false,
                'searching' => false,
                'info' => false,
                'ordering' => false,
                'scrollX' => true,
                'scrollCollapse' => true,
                'fixedColumns' => [
                    'leftColumns' => 1
                ]
            ]);
    }

    protected function getColumns()
    {
        $columns = [
            Column::make('account_name')->title('Account')->width('250px')->addClass('sticky-column'),
        ];

        foreach ($this->monthColumns as $month) {
            $columns[] = Column::make($month['key'])
                ->title($month['label'])
                ->width('120px')
                ->addClass('text-right');
        }

        $columns[] = Column::make('total')
            ->title('Total')
            ->width('120px')
            ->addClass('text-right font-weight-bold');

        return $columns;
    }
}