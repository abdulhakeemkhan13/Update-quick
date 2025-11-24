<?php

namespace App\DataTables;

use App\Models\ChartOfAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ProfitLossDetailDataTable extends DataTable
{
    protected $startDate;
    protected $endDate;
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

        $this->companyId = \Auth::user()->type === 'company' ? \Auth::user()->creatorId() : \Auth::user()->ownedId();
        $this->owner = \Auth::user()->type === 'company' ? 'created_by' : 'owned_by';
    }

    public function dataTable($query)
    {
        return datatables()
            ->collection($query)
            ->addColumn('account_name', function ($row) {
                $rowType = $row->row_type ?? 'unknown';
                
                switch ($rowType) {
                    case 'section_header':
                        $chevronHtml = '';
                        if ($row->has_children ?? false) {
                            $chevronHtml = '<i class="fas toggle-chevron mr-2">▼</i>';
                        }
                        return '<span class="toggle-section" data-group="' . ($row->group_key ?? '') . '" style="cursor: ' . ($row->has_children ? 'pointer' : 'default') . ';">
                            ' . $chevronHtml . '
                            <strong class="section-header">' . e($row->name ?? '') . '</strong>
                        </span>';
                    
                    case 'total':
                    case 'subtotal':
                        return '<strong class="total-label">' . e($row->name ?? '') . '</strong>';
                    
                    case 'account_header':
                        return '&nbsp;&nbsp;&nbsp;&nbsp;<strong>' 
                            . (isset($row->code) && $row->code ? '<span class="account-code">' . e($row->code) . '</span> ' : '')
                            . e($row->name ?? '') . '</strong>';
                    
                    case 'transaction':
                        return '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                    
                    case 'account_total':
                        return '&nbsp;&nbsp;&nbsp;&nbsp;<strong>Total ' . e($row->account_name ?? '') . '</strong>';
                    
                    default:
                        return e($row->name ?? '');
                }
            })
            ->addColumn('transaction_date', function ($row) {
                if (($row->row_type ?? '') === 'transaction' && isset($row->date)) {
                    return Carbon::parse($row->date)->format('m/d/Y');
                }
                return '';
            })
            ->addColumn('transaction_type', function ($row) {
                if (($row->row_type ?? '') === 'transaction') {
                    return e($row->voucher_type ?? '');
                }
                return '';
            })
            ->addColumn('amount', function ($row) {
                $rowType = $row->row_type ?? 'unknown';
                
                if ($rowType === 'section_header') {
                    return '';
                }
                
                if (in_array($rowType, ['total', 'subtotal', 'account_total'])) {
                    $amount = $row->net ?? $row->amount ?? 0;
                    return '<strong class="total-amount">' . number_format(abs($amount), 2) . '</strong>';
                }
                
                if ($rowType === 'account_header') {
                    return '<strong>' . number_format(abs($row->account_total ?? 0), 2) . '</strong>';
                }
                
                if ($rowType === 'transaction') {
                    $amount = $row->amount ?? 0;
                    if ($amount == 0) {
                        return '';
                    }
                    return '<span class="amount-cell">' . number_format(abs($amount), 2) . '</span>';
                }
                
                return '';
            })
            ->addColumn('memo', function ($row) {
                if (($row->row_type ?? '') === 'transaction') {
                    return e($row->description ?? '');
                }
                return '';
            })
            ->setRowAttr([
                'class' => function ($row) {
                    $rowType = $row->row_type ?? 'unknown';
                    $groupKey = $row->group_key ?? '';
                    
                    switch ($rowType) {
                        case 'section_header':
                            return 'section-row';
                        case 'account_header':
                            return 'account-header-row group-' . $groupKey;
                        case 'transaction':
                            return 'transaction-row group-' . $groupKey;
                        case 'account_total':
                            return 'account-total-row group-' . $groupKey;
                        case 'total':
                            return 'total-row';
                        case 'subtotal':
                            return 'subtotal-row group-' . $groupKey;
                        default:
                            return '';
                    }
                }
            ])
            ->rawColumns(['account_name', 'amount']);
    }

    private function createRow($data)
    {
        $defaults = [
            'row_type' => 'unknown',
            'name' => '',
            'code' => null,
            'group_key' => '',
            'has_children' => false,
            'account_name' => '',
            'account_total' => 0,
            'amount' => 0,
            'net' => 0,
            'date' => null,
            'voucher_type' => null,
            'description' => null,
        ];
        
        return (object) array_merge($defaults, $data);
    }

    public function query()
    {
        // Get all accounts with their transactions individually
        $transactions = DB::table('chart_of_accounts')
            ->where('chart_of_accounts.created_by', $this->companyId)
            ->leftJoin('chart_of_account_types', 'chart_of_accounts.type', '=', 'chart_of_account_types.id')
            ->leftJoin('chart_of_account_sub_types', 'chart_of_accounts.sub_type', '=', 'chart_of_account_sub_types.id')
            ->join('journal_items', 'chart_of_accounts.id', '=', 'journal_items.account')
            ->join('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
            ->where("journal_entries.{$this->owner}", $this->companyId)
            ->whereBetween('journal_entries.date', [$this->startDate, $this->endDate])
            ->whereIn('chart_of_account_types.name', ['Income', 'Expenses', 'Costs of Goods Sold', 'Other Income', 'Other Expense'])
            ->select([
                'chart_of_accounts.id as account_id',
                'chart_of_accounts.name as account_name',
                'chart_of_accounts.code as account_code',
                'chart_of_account_types.name as account_type',
                'chart_of_account_sub_types.name as sub_type',
                'journal_entries.date',
                'journal_items.type as voucher_type' ,
                'journal_items.name as user_name' ,
                'journal_entries.description',
                'journal_items.debit',
                'journal_items.credit',
            ])
            ->orderBy('chart_of_account_types.name')
            ->orderBy('chart_of_accounts.name')
            ->orderBy('journal_entries.date')
            ->get();

        $report = collect();

        // Group transactions by account type
        $groupedByType = $transactions->groupBy('account_type');

        // ---------------- INCOME ----------------
        $incomeTransactions = $groupedByType->get('Income', collect());
        $incomeTotal = 0;

        if ($incomeTransactions->isNotEmpty()) {
            $report->push($this->createRow([
                'row_type' => 'section_header',
                'name' => 'Income',
                'group_key' => 'income',
                'has_children' => true,
            ]));

            $incomeByAccount = $incomeTransactions->groupBy('account_id');
            
            foreach ($incomeByAccount as $accountId => $accountTransactions) {
                $firstTrans = $accountTransactions->first();
                $accountTotal = $accountTransactions->sum(function ($t) {
                    return $t->credit - $t->debit;
                });
                $incomeTotal += $accountTotal;

                // Account header
                $report->push($this->createRow([
                    'row_type' => 'account_header',
                    'name' => $firstTrans->account_name,
                    'code' => $firstTrans->account_code,
                    'group_key' => 'income',
                    'account_total' => $accountTotal,
                ]));

                // Individual transactions
                foreach ($accountTransactions as $trans) {
                    $report->push($this->createRow([
                        'row_type' => 'transaction',
                        'group_key' => 'income',
                        'date' => $trans->date,
                        'name' => $trans->user_name,
                        'voucher_type' => $trans->voucher_type,
                        'description' => $trans->description,
                        'amount' => $trans->credit - $trans->debit,
                    ]));
                }

                // Account total
                $report->push($this->createRow([
                    'row_type' => 'account_total',
                    'group_key' => 'income',
                    'account_name' => $firstTrans->account_name,
                    'amount' => $accountTotal,
                ]));
            }

            $report->push($this->createRow([
                'row_type' => 'subtotal',
                'name' => 'Total Income',
                'net' => $incomeTotal,
                'group_key' => 'income'
            ]));
        }

        // ---------------- COGS ----------------
        $cogsTransactions = $groupedByType->get('Costs of Goods Sold', collect())
        ->merge(
            $groupedByType->flatten(1)->filter(function ($acc) {
                return $acc->account_type === 'Expenses' 
                    && in_array($acc->sub_type, ['COGS', 'Costs of Goods Sold']);
            })
        );
        $cogsTotal = 0;

        if ($cogsTransactions->isNotEmpty()) {
            $report->push($this->createRow([
                'row_type' => 'section_header',
                'name' => 'Costs of Goods Sold',
                'group_key' => 'cogs',
                'has_children' => true,
            ]));

            $cogsByAccount = $cogsTransactions->groupBy('account_id');
            
            foreach ($cogsByAccount as $accountId => $accountTransactions) {
                $firstTrans = $accountTransactions->first();
                $accountTotal = $accountTransactions->sum(function ($t) {
                    return $t->debit - $t->credit;
                });
                $cogsTotal += $accountTotal;

                $report->push($this->createRow([
                    'row_type' => 'account_header',
                    'name' => $firstTrans->account_name,
                    'code' => $firstTrans->account_code,
                    'group_key' => 'cogs',
                    'account_total' => $accountTotal,
                ]));

                foreach ($accountTransactions as $trans) {
                    $report->push($this->createRow([
                        'row_type' => 'transaction',
                        'group_key' => 'cogs',
                        'date' => $trans->date,
                        'name' => $trans->user_name,
                        'voucher_type' => $trans->voucher_type,
                        'description' => $trans->description,
                        'amount' => $trans->debit - $trans->credit,
                    ]));
                }

                $report->push($this->createRow([
                    'row_type' => 'account_total',
                    'group_key' => 'cogs',
                    'account_name' => $firstTrans->account_name,
                    'amount' => $accountTotal,
                ]));
            }

            $report->push($this->createRow([
                'row_type' => 'subtotal',
                'name' => 'Total Costs of Goods Sold',
                'net' => $cogsTotal,
                'group_key' => 'cogs'
            ]));
        }

        // ---------------- GROSS PROFIT ----------------
        $grossProfit = $incomeTotal - $cogsTotal;
        $report->push($this->createRow([
            'row_type' => 'total',
            'name' => 'Gross Profit',
            'net' => $grossProfit,
        ]));

        // ---------------- EXPENSES ----------------
        $expenseTransactions = $groupedByType->flatten(1)->filter(function ($acc) { 
            return $acc->account_type === 'Expenses' 
                && !in_array($acc->sub_type, ['COGS', 'Costs of Goods Sold']);
        });

        $expenseTotal = 0;

        if ($expenseTransactions->isNotEmpty()) {
            $report->push($this->createRow([
                'row_type' => 'section_header',
                'name' => 'Expenses',
                'group_key' => 'expenses',
                'has_children' => true,
            ]));

            $expensesByAccount = $expenseTransactions->groupBy('account_id');
            
            foreach ($expensesByAccount as $accountId => $accountTransactions) {
                $firstTrans = $accountTransactions->first();
                $accountTotal = $accountTransactions->sum(function ($t) {
                    return $t->debit - $t->credit;
                });
                $expenseTotal += $accountTotal;

                $report->push($this->createRow([
                    'row_type' => 'account_header',
                    'name' => $firstTrans->account_name,
                    'code' => $firstTrans->account_code,
                    'group_key' => 'expenses',
                    'account_total' => $accountTotal,
                ]));

                foreach ($accountTransactions as $trans) {
                    $report->push($this->createRow([
                        'row_type' => 'transaction',
                        'group_key' => 'expenses',
                        'date' => $trans->date,
                        'name' => $trans->user_name,
                        'voucher_type' => $trans->voucher_type,
                        'description' => $trans->description,
                        'amount' => $trans->debit - $trans->credit,
                    ]));
                }

                $report->push($this->createRow([
                    'row_type' => 'account_total',
                    'group_key' => 'expenses',
                    'account_name' => $firstTrans->account_name,
                    'amount' => $accountTotal,
                ]));
            }

            $report->push($this->createRow([
                'row_type' => 'subtotal',
                'name' => 'Total Expenses',
                'net' => $expenseTotal,
                'group_key' => 'expenses'
            ]));
        }

        // ---------------- NET ORDINARY INCOME ----------------
        $netOrdinary = $grossProfit - $expenseTotal;
        $report->push($this->createRow([
            'row_type' => 'total',
            'name' => 'NET ORDINARY INCOME',
            'net' => $netOrdinary,
        ]));

        // ---------------- FINAL NET INCOME ----------------
        $report->push($this->createRow([
            'row_type' => 'total',
            'name' => 'NET INCOME',
            'net' => $netOrdinary,
        ]));

        return $report;
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('profit-loss-detail-table')
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
            Column::make('account_name')->title('Account')->width('35%'),
            Column::make('transaction_date')->title('Date')->width('10%'),
            Column::make('name')->title('name')->width('12%'),
            Column::make('transaction_type')->title('Type')->width('10%'),
            Column::make('memo')->title('Memo/Description')->width('22%'),
            Column::make('amount')->title('Amount')->width('10%')->addClass('text-right'),
        ];
    }
}