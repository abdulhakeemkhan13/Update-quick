<?php

namespace App\DataTables;

use App\Models\ChartOfAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class TrialBalanceDataTable extends DataTable
{
    protected $startDate;
    protected $endDate;
    protected $companyId;
    protected $owner;
    protected $accountingMethod;

    public function __construct()
    {
        parent::__construct();

        $this->startDate = request('startDate')
            ? Carbon::parse(request('startDate'))->startOfDay()
            : Carbon::now()->startOfYear();

        $this->endDate = request('endDate')
            ? Carbon::parse(request('endDate'))->endOfDay()
            : Carbon::now()->endOfDay();

        $this->companyId = \Auth::user()->type === 'company' ? \Auth::user()->creatorId() : \Auth::user()->ownedId();
        $this->owner = \Auth::user()->type === 'company' ? 'created_by' : 'owned_by';
        $this->accountingMethod = request('accountingMethod', 'accrual'); // default accrual
    }

    public function dataTable($query)
    {
        return datatables()
            ->collection($query)
            ->addColumn('DT_RowClass', function ($row) {
                $classes = [];

                if (!empty($row->is_header)) {
                    $classes[] = 'account-header';
                    $classes[] = 'parent-row';
                    $classes[] = 'level-0';
                } elseif (!empty($row->is_subtotal)) {
                    $classes[] = 'account-subtotal';
                    $classes[] = 'child-row';
                    $classes[] = 'level-2';
                } elseif (!empty($row->is_total)) {
                    $classes[] = 'grand-total';
                    $classes[] = 'level-0';
                } elseif (!empty($row->is_net_income)) {
                    $classes[] = 'net-income';
                    $classes[] = 'level-0';
                } else {
                    $classes[] = 'account-detail';
                    $classes[] = 'child-row';
                    $classes[] = 'level-1';
                }

                if (!empty($row->parent_id)) {
                    $classes[] = 'parent-' . $row->parent_id;
                }

                return implode(' ', $classes);
            })
            ->addColumn('DT_RowData', function ($row) {
                $data = [];

                if (!empty($row->parent_id)) {
                    $data['parent'] = $row->parent_id;
                }

                if (!empty($row->has_children)) {
                    $data['has-children'] = 'true';
                }
                if(!empty($row->is_subtotal)) {
                    $data['is-subtotal'] = 'true';
                }
                $data['level'] = $this->getRowLevel($row);
                $data['row-id'] = $row->id;

                return $data;
            })
            ->addColumn('code', function ($row) {
                return isset($row->is_header) || isset($row->is_total) || isset($row->is_subtotal) || isset($row->is_net_income)
                    ? ''
                    : ($row->code ?? '');
            })
            ->addColumn('account_name', function ($row) {
                $indent = $this->getIndentation($row);

                // Header row with toggle
                if (!empty($row->is_header)) {
                    return $indent . '<span class="toggle-btn collapsed" data-target="' . $row->id . '">'
                        . '<i class="fa fa-chevron-right toggle-icon"></i>'
                        . '</span>'
                        . '<strong class="text-uppercase account-header-text">' . e($row->name) . '</strong>';
                }

                // Subtotal row
                if (!empty($row->is_subtotal)) {
                    return $indent . '<strong class="text-primary account-subtotal-text">Total ' . e($row->name) . '</strong>';
                }

                // Grand total row
                if (!empty($row->is_total)) {
                    return '<strong class="text-danger grand-total-text">GRAND TOTAL</strong>';
                }

                // Net Income row
                if (!empty($row->is_net_income)) {
                    return '<strong class="text-info net-income-text">' . e($row->name) . '</strong>';
                }

                // Normal account row with link
                $accountName = $indent . e($row->name);

                if (!empty($row->id) && is_numeric(str_replace('acc-', '', $row->id))) {
                    $accountId = str_replace('acc-', '', $row->id);
                    $url = route('ledger.index', ['account_id' => $accountId]);
                    return '<a href="' . $url . '" class="text-primary ledger-link" target="_blank">' . $accountName . '</a>';
                }

                return $accountName;
            })
            ->addColumn('account_type', function ($row) {
                return (isset($row->is_header) || isset($row->is_subtotal) || isset($row->is_total) || isset($row->is_net_income))
                    ? ''
                    : ucfirst($row->account_type ?? '');
            })
            ->addColumn('debit', function ($row) {
                $debit = $row->debit ?? 0;

                if (!empty($row->is_header)) {
                    return '<strong class="text-success debit-cell">' . number_format(abs($debit), 2) . '</strong>';
                }

                if (!empty($row->is_subtotal) || !empty($row->is_total)) {
                    return '<strong class="text-success">' . number_format(abs($debit), 2) . '</strong>';
                }

                if (!empty($row->is_net_income)) {
                    return $debit > 0 ? '<strong class="text-success">' . number_format($debit, 2) . '</strong>' : '';
                }

                return $debit > 0 ? '<span class="text-success">' . number_format($debit, 2) . '</span>' : '';
            })
            ->addColumn('credit', function ($row) {
                $credit = $row->credit ?? 0;

                if (!empty($row->is_header)) {
                    return '<strong class="text-danger credit-cell">' . number_format(abs($credit), 2) . '</strong>';
                }

                if (!empty($row->is_subtotal) || !empty($row->is_total)) {
                    return '<strong class="text-danger">' . number_format(abs($credit), 2) . '</strong>';
                }

                if (!empty($row->is_net_income)) {
                    return $credit > 0 ? '<strong class="text-danger">' . number_format($credit, 2) . '</strong>' : '';
                }

                return $credit > 0 ? '<span class="text-danger">' . number_format($credit, 2) . '</span>' : '';
            })
            ->rawColumns(['account_name', 'debit', 'credit']);
    }

    private function getRowLevel($row)
    {
        if (!empty($row->is_header) || !empty($row->is_total) || !empty($row->is_net_income)) {
            return 0;
        } elseif (!empty($row->is_subtotal)) {
            return 2;
        } else {
            return 1; // Normal accounts
        }
    }

    private function getIndentation($row)
    {
        $level = $this->getRowLevel($row);
        return str_repeat('<span class="indent-spacer"></span>', $level);
    }

    public function query()
    {
        // dd($this->accountingMethod,request()->all());
        $startDate = request('startDate')
            ? Carbon::parse(request('startDate'))->startOfDay()
            : Carbon::now()->startOfYear();

        $endDate = request('endDate')
            ? Carbon::parse(request('endDate'))->endOfDay()
            : Carbon::now()->endOfDay();

        // Apply filters
        $subtypeFilter = request('subtype');
        $typeFilter = request('type');

        // --- Prepare cash account ids when cash accounting is requested ---
        $cashAccountIds = [];
        if ($this->accountingMethod === 'cash') {
            $cashAccountIds = ChartOfAccount::query()
                ->select('chart_of_accounts.id')
                ->join('chart_of_account_types', 'chart_of_accounts.type', '=', 'chart_of_account_types.id')
                ->where('chart_of_accounts.created_by', $this->companyId)
                ->where('chart_of_account_types.name', 'Assets')
                ->where(function ($q) {
                    // Try common cash/bank indicators: subtype or name containing 'bank'/'cash'
                    $q->whereIn('chart_of_accounts.sub_type', ['Bank', 'Cash', 'Bank Account', 'Cash Account'])
                      ->orWhereRaw("LOWER(chart_of_accounts.name) LIKE '%bank%'")
                      ->orWhereRaw("LOWER(chart_of_accounts.name) LIKE '%cash%'");
                })
                ->pluck('chart_of_accounts.id')
                ->toArray();
        }

        $cashJournalCondition = '';
        if ($this->accountingMethod === 'cash') {
            // If no cash accounts found, the condition will look for IN (NULL) - which returns no rows.
            $ids = count($cashAccountIds) ? implode(',', array_map('intval', $cashAccountIds)) : 'NULL';
            $cashJournalCondition = " AND je.id IN (SELECT journal FROM journal_items WHERE account IN ($ids)) ";
        }

        // Build opening and period subqueries, applying cash filter when needed
        $openingSub = "(
            SELECT 
                jel.account,
                SUM(jel.debit) as opening_debit,
                SUM(jel.credit) as opening_credit
            FROM journal_items jel
            INNER JOIN journal_entries je ON je.id = jel.journal
            WHERE je.{$this->owner} = " . $this->companyId . "
              AND je.date < '" . $startDate->format('Y-m-d') . "' 
              $cashJournalCondition
            GROUP BY jel.account
        ) as opening";

        $periodSub = "(
            SELECT 
                jel.account,
                SUM(jel.debit) as period_debit,
                SUM(jel.credit) as period_credit
            FROM journal_items jel
            INNER JOIN journal_entries je ON je.id = jel.journal
            WHERE je.{$this->owner} = " . $this->companyId . "
              AND je.date BETWEEN '" . $startDate->format('Y-m-d') . "' AND '" . $endDate->format('Y-m-d') . "' 
              $cashJournalCondition 
            GROUP BY jel.account
        ) as period";

        $accounts = ChartOfAccount::query()
            ->where('chart_of_accounts.created_by', $this->companyId)
            ->leftJoin('chart_of_account_types', 'chart_of_accounts.type', '=', 'chart_of_account_types.id')
            ->leftJoin(DB::raw($openingSub), 'chart_of_accounts.id', '=', 'opening.account')
            ->leftJoin(DB::raw($periodSub), 'chart_of_accounts.id', '=', 'period.account');

        // Apply type filter
        if ($typeFilter) {
            $accounts->where('chart_of_account_types.name', $typeFilter);
        }

        // Apply subtype filter (assuming you have a subtype field)
        if ($subtypeFilter) {
            $accounts->where('chart_of_accounts.sub_type', $subtypeFilter);
        }

        $accounts = $accounts->select([
            'chart_of_accounts.id',
            'chart_of_accounts.name',
            'chart_of_accounts.code',
            'chart_of_accounts.sub_type as subtype',
            'chart_of_account_types.name as account_type',
            DB::raw(" 
                    CASE 
                        WHEN chart_of_account_types.name IN ('Assets','Liabilities','Equity') 
                            THEN GREATEST(
                                (COALESCE(opening.opening_debit,0) + COALESCE(period.period_debit,0)) 
                                - (COALESCE(opening.opening_credit,0) + COALESCE(period.period_credit,0)),
                                0
                            )
                        ELSE GREATEST(
                                COALESCE(period.period_debit,0) - COALESCE(period.period_credit,0),
                                0
                            )
                    END as debit
                "),
            DB::raw(" 
                    CASE 
                        WHEN chart_of_account_types.name IN ('Assets','Liabilities','Equity') 
                            THEN GREATEST(
                                (COALESCE(opening.opening_credit,0) + COALESCE(period.period_credit,0)) 
                                - (COALESCE(opening.opening_debit,0) + COALESCE(period.period_debit,0)),
                                0
                            )
                        ELSE GREATEST(
                                COALESCE(period.period_credit,0) - COALESCE(period.period_debit,0),
                                0
                            )
                    END as credit
                ")
        ])
            ->groupBy(
                'chart_of_accounts.id',
                'chart_of_accounts.name',
                'chart_of_accounts.code',
                'chart_of_accounts.sub_type',
                'chart_of_account_types.name',
                'opening.opening_debit',
                'opening.opening_credit',
                'period.period_debit',
                'period.period_credit'
            )
            ->orderBy('chart_of_account_types.name')
            ->orderBy('chart_of_accounts.code')
            ->get();

        return $this->buildHierarchicalData($accounts, $startDate, $cashAccountIds);
    }

    private function buildHierarchicalData($accounts, $startDate, $cashAccountIds = [])
    {
        $report = collect();
        $accountTypes = ['Assets', 'Liabilities', 'Equity', 'Income', 'Expenses'];

        $netResult = $this->calculateNetProfit($startDate);
        $netIncome = $netResult['net_profit'];
        $debit = $netResult['debit'];
        $credit = $netResult['credit'];
        $netDebit = $netResult['total_debit'];
        $netCredit = $netResult['total_credit'];
        // dd($netResult,$debit,$credit,$netIncome,$totalDebit,$totalCredit);
        foreach ($accountTypes as $type) {
            $group = $accounts->where('account_type', $type);

            if ($group->isEmpty()) {
                continue;
            }

            // Add header row
            $headerRow = (object) [
                'id' => 'type-' . strtolower($type),
                'parent_id' => null,
                'name' => strtoupper($type),
                'is_header' => true,
                'debit' => $group->sum('debit'),
                'credit' => $group->sum('credit'),
                'is_subtotal' => true,
                'has_children' => true,
                'account_type' => $type
            ];
            $report->push($headerRow);

            // Add individual accounts
            foreach ($group as $acc) {
                $accountRow = (object) [
                    'id' => 'acc-' . $acc->id,
                    'parent_id' => 'type-' . strtolower($type),
                    'name' => $acc->name,
                    'code' => $acc->code,
                    'account_type' => $acc->account_type,
                    'subtype' => $acc->subtype,
                    'debit' => $acc->debit,
                    'credit' => $acc->credit,
                    'has_children' => false
                ];
                $report->push($accountRow);
            }

            // 🧾 Step 3: Add Profit/Loss under Owner’s Equity
            if ($type === 'Equity') {
                // Find if Owner’s Equity account exists
                $ownerEquity = $group->first(function ($acc) {
                    return stripos($acc->name, 'owner') !== false && stripos($acc->name, 'equity') !== false;
                });

                if ($ownerEquity) {
                // Add current Owner’s Equity balances
                $totalDebit  = $ownerEquity->debit + $netDebit;
                $totalCredit = $ownerEquity->credit + $netCredit;

                // ⚖️ Calculate net balance
                if ($totalCredit > $totalDebit) {
                    $ownerEquity->debit = 0;
                    $ownerEquity->credit = $totalCredit - $totalDebit;
                } elseif ($totalDebit > $totalCredit) {
                    $ownerEquity->debit = $totalDebit - $totalCredit;
                    $ownerEquity->credit = 0;
                } else {
                    $ownerEquity->debit = $ownerEquity->credit = 0;
                }

                // Update Owner’s Equity row in report
                $report = $report->map(function ($row) use ($ownerEquity) {
                    if (isset($row->id) && $row->id === 'acc-' . $ownerEquity->id) {
                        $row->debit = $ownerEquity->debit;
                        $row->credit = $ownerEquity->credit;
                    }
                    return $row;
                });

            } else {
                // If no Owner’s Equity, create one with net balance
                $newEquityId = 'acc-owner-equity-temp';

                if ($netCredit > $netDebit) {
                    $ownerDebit = 0;
                    $ownerCredit = $netCredit - $netDebit;
                } elseif ($netDebit > $netCredit) {
                    $ownerDebit = $netDebit - $netCredit;
                    $ownerCredit = 0;
                } else {
                    $ownerDebit = $ownerCredit = 0;
                }

                // Add Owner’s Equity
                $report->push((object) [
                    'id' => 'acc-' . $acc->id,
                    'parent_id' => 'type-' . strtolower($type),
                    'name' => 'Retained Earnings',
                    'account_type' => $acc->account_type,
                    'subtype' => $acc->subtype,
                    'debit' => $ownerDebit,
                    'credit' => $ownerCredit,
                    'has_children' => false
                ]);
                 $newEquity = (object) [
                    'id' => 'acc-owner-equity-temp',
                    'parent_id' => 'type-' . strtolower($type),
                    'name' => 'Owner’s Equity',
                    'account_type' => 'Equity',
                    'subtype' => null,
                    'debit' => $ownerDebit,
                    'credit' => $ownerCredit,
                    'has_children' => false
                ];

                $group->push($newEquity);
                $accounts->push($newEquity);

               
            }
            }

            // Add subtotal row
            $subtotalRow = (object) [
                'id' => 'sub-' . strtolower($type),
                'parent_id' => 'type-' . strtolower($type),
                'name' => $type,
                'debit' => $group->sum('debit'),
                'credit' => $group->sum('credit'),
                'is_subtotal' => true,
                'has_children' => false,
                'account_type' => $type
            ];
            $report->push($subtotalRow);
        }

        

        // Add accumulated profit/loss row
        // $netProfit = $this->calculateNetProfit($startDate, $cashAccountIds);

        // $accumulatedRow = (object) [
        //     'id' => 'net-income',
        //     'parent_id' => null,
        //     'name' => 'Accumulated Profit / (Loss)',
        //     'account_type' => 'Equity',
        //     'debit' => $netProfit < 0 ? abs($netProfit) : 0,
        //     'credit' => $netProfit > 0 ? $netProfit : 0,
        //     'is_net_income' => true,
        //     'has_children' => false
        // ];
        // $report->push($accumulatedRow);

        // // Add grand total row
        // $totalDebit = $accounts->sum('debit') + $accumulatedRow->debit;
        // $totalCredit = $accounts->sum('credit') + $accumulatedRow->credit;
        $totalDebit = $accounts->sum('debit');
        $totalCredit = $accounts->sum('credit');

        $grandTotalRow = (object) [
            'id' => 'grand-total',
            'parent_id' => null,
            'name' => 'GRAND TOTAL',
            'debit' => $totalDebit,
            'credit' => $totalCredit,
            'is_total' => true,
            'has_children' => false
        ];
        $report->push($grandTotalRow);

        return $report;
    }

    private function calculateNetProfit($startDate, $cashAccountIds = [])
    {
        // $qb = DB::table('journal_items')
        //     ->join('chart_of_accounts', 'journal_items.account', '=', 'chart_of_accounts.id')
        //     ->join('chart_of_account_types', 'chart_of_accounts.type', '=', 'chart_of_account_types.id')
        //     ->join('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
        //     ->where("journal_entries.{$this->owner}", $this->companyId)
        //     ->where('journal_entries.date', '<=', $startDate)
        //     ->whereIn('chart_of_account_types.name', ['Income', 'Expenses']);

        // // When cash method is selected, include only journal entries that contain a cash account line
        // if ($this->accountingMethod === 'cash') {
        //     if (count($cashAccountIds)) {
        //         $qb->whereIn('journal_entries.id', function ($sub) use ($cashAccountIds) {
        //             $sub->select('journal')->from('journal_items')->whereIn('account', $cashAccountIds);
        //         });
        //     } else {
        //         // No cash accounts found: force zero result
        //         $qb->whereRaw('1 = 0');
        //     }
        // }

        // $net = $qb->selectRaw('SUM(journal_items.credit - journal_items.debit) as net_profit')
        //     ->value('net_profit');

        // return $net ?? 0;

        // 🧾 Get totals for INCOME
        $income = DB::table('journal_items')
            ->join('chart_of_accounts', 'journal_items.account', '=', 'chart_of_accounts.id')
            ->join('chart_of_account_types', 'chart_of_accounts.type', '=', 'chart_of_account_types.id')
            ->join('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
            ->where("journal_entries.{$this->owner}", $this->companyId)
            ->where('journal_entries.date', '<', $startDate)
            ->where('chart_of_account_types.name', 'Income')
            ->selectRaw('
                COALESCE(SUM(journal_items.debit), 0) as total_debit,
                COALESCE(SUM(journal_items.credit), 0) as total_credit
            ')
            ->first();

        // 🧾 Get totals for EXPENSES + COGS
        $expensesAndCogs = DB::table('journal_items')
            ->join('chart_of_accounts', 'journal_items.account', '=', 'chart_of_accounts.id')
            ->join('chart_of_account_types', 'chart_of_accounts.type', '=', 'chart_of_account_types.id')
            ->join('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
            ->where("journal_entries.{$this->owner}", $this->companyId)
            ->where('journal_entries.date', '<', $startDate)
            ->whereIn('chart_of_account_types.name', ['Expenses', 'Costs of Goods Sold'])
            ->selectRaw('
                COALESCE(SUM(journal_items.debit), 0) as total_debit,
                COALESCE(SUM(journal_items.credit), 0) as total_credit
            ')
            ->first();

        // 🧮 Combine both totals
        $totalDebit  = $income->total_debit  + $expensesAndCogs->total_debit;
        $totalCredit = $income->total_credit + $expensesAndCogs->total_credit;

        // ⚖️ Determine profit or loss (difference on the higher side)
        if ($totalCredit > $totalDebit) {
            // Profit → show difference on credit side
            $netProfit = $totalCredit - $totalDebit;
            $debit = 0;
            $credit = $netProfit;
        } elseif ($totalDebit > $totalCredit) {
            // Loss → show difference on debit side
            $netProfit = $totalDebit - $totalCredit;
            $debit = $netProfit;
            $credit = 0;
        } else {
            $netProfit = 0;
            $debit = $credit = 0;
        }

        // 📦 Return structured result
        return [
            'net_profit' => $netProfit,
            'debit'      => $debit,
            'credit'     => $credit,
            'total_debit'  => $totalDebit,
            'total_credit' => $totalCredit,
            'income'     => $income,
            'expenses'   => $expensesAndCogs,
        ];
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('trial-balance-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'paging' => false,
                'searching' => false,
                'info' => false,
                'ordering' => false,
                'scrollY' => '500px',
                'scrollCollapse' => true,
                'createdRow' => "function(row, data, dataIndex) {
                    if (data.DT_RowClass) {
                        $(row).addClass(data.DT_RowClass);
                    }
                    if (data.DT_RowData) {
                        for (let key in data.DT_RowData) {
                            $(row).attr('data-' + key, data.DT_RowData[key]);
                        }
                    }
                }"
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('code')->title('Account #')->width('12%')->addClass('text-center'),
            Column::make('account_name')->title('Account Name')->width('45%'),
            Column::make('debit')->title('Debit')->width('15%')->addClass('text-right'),
            Column::make('credit')->title('Credit')->width('15%')->addClass('text-right'),
        ];
    }
}
