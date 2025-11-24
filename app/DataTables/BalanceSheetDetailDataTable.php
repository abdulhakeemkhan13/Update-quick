<?php

namespace App\DataTables;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class BalanceSheetDetailDataTable extends DataTable
{
    protected $startDate;
    protected $asOfDate;
    protected $companyId;
    protected $owner;
    protected $accountingMethod;

    public function __construct()
    {
        parent::__construct();
        $this->startDate = request('startDate')
            ? Carbon::parse(request('startDate'))->startOfDay()
            : Carbon::now()->startOfMonth();
        $this->asOfDate = request('endDate')
            ? Carbon::parse(request('endDate'))->endOfDay()
            : Carbon::now()->endOfDay();

        $this->companyId = \Auth::user()->type === 'company'
            ? \Auth::user()->creatorId()
            : \Auth::user()->ownedId();

        $this->owner = \Auth::user()->type === 'company' ? 'created_by' : 'owned_by';

        $this->accountingMethod = request('accounting_method') ?? 'accrual'; // default accrual
    }

    public function dataTable($query)
    {
        return datatables()
            ->collection($query)
            ->addColumn('account', function ($row) {
                $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $row->level ?? 0);

                if ($row->is_type_header ?? false) {
                    return '<h4><strong>' . e($row->account_name) . '</strong></h4>';
                }
                if ($row->is_subtype_header ?? false) {
                    $hasChildren = $row->has_children ?? false;
                    $subtypeId = $row->subtype_id ?? 'subtype_' . str_replace(' ', '_', strtolower($row->account_name));
                    $chevron = $hasChildren
                        ? '<i class=" chevron-icon" data-parent-type="subtype" data-parent-id="' . $subtypeId . '" style="margin-right: 8px; cursor: pointer;">▼</i>'
                        : '';
                    return $indent . $chevron . '<strong>' . e($row->account_name) . '</strong>';
                }
                if ($row->is_account_header ?? false) {
                    $hasChildren = $row->has_children ?? false;
                    $accountId = $row->account_id ?? 0;
                    $chevron = $hasChildren
                        ? '<i class=" chevron-icon" data-parent-type="account" data-parent-id="' . $accountId . '" style="margin-right: 8px; cursor: pointer;">▼</i>'
                        : '<span style="margin-right: 20px;"></span>';
                    return $indent . $chevron . e($row->account_code . ' - ' . $row->account_name);
                }
                if ($row->is_account_total ?? false) {
                    return '<strong>' . $indent . 'Total ' . e($row->account_name) . '</strong>';
                }
                if ($row->is_subtype_total ?? false) {
                    return '<strong>' . $indent . 'Total ' . e($row->account_name) . '</strong>';
                }
                if ($row->is_type_total ?? false) {
                    return '<strong>Total ' . e($row->account_name) . '</strong>';
                }
                return $indent . e($row->date);
            })
            ->addColumn('transaction_type', fn($row) => $row->transaction_type ?? '')
            ->addColumn('num', fn($row) => $row->num ?? '')
            ->addColumn('name', fn($row) => $row->transaction_type_code ?? '')
            ->addColumn('memo', fn($row) => $row->memo ?? '')
            ->addColumn('split', fn($row) => $row->split_account ?? '')
            ->addColumn('debit', fn($row) => ($row->debit ?? null) ? number_format($row->debit, 2) : '&nbsp;')
            ->addColumn('credit', fn($row) => ($row->credit ?? null) ? number_format($row->credit, 2) : '&nbsp;')
            ->addColumn('amount', fn($row) => isset($row->amount) ? number_format($row->amount, 2) : '&nbsp;')
            ->addColumn('balance', fn($row) => isset($row->balance) ? number_format($row->balance, 2) : '&nbsp;')
            ->addColumn('DT_RowClass', function ($row) {
                $classes = [];
                if ($row->is_type_header ?? false) $classes[] = 'type-header-row';
                if ($row->is_subtype_header ?? false) {
                    $classes[] = 'subtype-header-row';
                    $subtypeId = $row->subtype_id ?? 'subtype_' . str_replace(' ', '_', strtolower($row->account_name));
                    $classes[] = 'parent-subtype-' . $subtypeId;
                }
                if ($row->is_account_header ?? false) {
                    $classes[] = 'account-header-row';
                    $classes[] = 'parent-account-' . ($row->account_id ?? 0);
                    if ($row->parent_subtype_id ?? false) {
                        $classes[] = 'child-of-subtype-' . $row->parent_subtype_id;
                    }
                }
                if ($row->is_account_total ?? false) {
                    $classes[] = 'account-total-row';
                    if ($row->parent_account_id ?? false) {
                        $classes[] = 'child-of-account-' . $row->parent_account_id;
                    }
                    if ($row->parent_subtype_id ?? false) {
                        $classes[] = 'child-of-subtype-' . $row->parent_subtype_id;
                    }
                }
                if ($row->is_subtype_total ?? false) {
                    $classes[] = 'subtype-total-row';
                    if ($row->parent_subtype_id ?? false) {
                        $classes[] = 'child-of-subtype-' . $row->parent_subtype_id;
                    }
                }
                if ($row->is_type_total ?? false) $classes[] = 'type-total-row';
                if ($row->is_transaction ?? false) {
                    $classes[] = 'transaction-row';
                    if ($row->parent_account_id ?? false) {
                        $classes[] = 'child-of-account-' . $row->parent_account_id;
                    }
                    if ($row->parent_subtype_id ?? false) {
                        $classes[] = 'child-of-subtype-' . $row->parent_subtype_id;
                    }
                }
                return implode(' ', $classes);
            })
            ->addColumn('DT_RowData', function ($row) {
                $data = [];
                if ($row->is_transaction ?? false) {
                    $data['transaction-id'] = $row->transaction_id ?? 0;
                }
                if ($row->account_id ?? false) {
                    $data['account-id'] = $row->account_id;
                }
                if ($row->subtype_id ?? false) {
                    $data['subtype-id'] = $row->subtype_id;
                }
                return $data;
            })
            ->rawColumns(['account', 'debit', 'credit', 'amount', 'balance']);
    }

    public function query()
    {
        // time out 0 
        set_time_limit(0);

        // ---- CASH BASIS filter logic ----
        $voucherIds = [];
        $excludeAccounts = [];
        
        if ($this->accountingMethod === 'cash') {
            $invoicePaymentVouchers = DB::table('invoice_payments')
                ->where('date', '<=', $this->asOfDate)
                ->whereNotNull('voucher_id')
                ->pluck('voucher_id')
                ->toArray();

            $billPaymentVouchers = DB::table('bill_payments')
                ->where('date', '<=', $this->asOfDate)
                ->whereNotNull('voucher_id')
                ->pluck('voucher_id')
                ->toArray();
            
            $voucherIds = array_merge($invoicePaymentVouchers, $billPaymentVouchers);

            if (!empty($voucherIds)) {
                $excludeAccounts = DB::table('journal_items as ji')
                    ->join('journal_entries as je', 'ji.journal', '=', 'je.id')
                    ->whereIn('je.id', $voucherIds)
                    ->whereIn('je.voucher_type', ['BPV', 'CPV']) // payment vouchers only
                    ->pluck('ji.account')
                    ->toArray();
            }
        }

        $entries = DB::table('chart_of_accounts')
            ->leftJoin('chart_of_account_sub_types', 'chart_of_accounts.sub_type', '=', 'chart_of_account_sub_types.id')
            ->leftJoin('chart_of_account_types', 'chart_of_account_sub_types.type', '=', 'chart_of_account_types.id')
            ->leftJoin('journal_items', function ($join) {
                $join->on('chart_of_accounts.id', '=', 'journal_items.account')
                    ->leftJoin('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
                    ->whereBetween('journal_items.created_at', [
                        date('Y-m-d 00:00:00', strtotime($this->startDate)),
                        date('Y-m-d 23:59:59', strtotime($this->asOfDate)),
                    ]);
                    //  ->orWhereNull('journal_items.id');
            })
            ->where("chart_of_accounts.{$this->owner}", $this->companyId)
            ->whereIn('chart_of_account_types.name', ['Assets', 'Equity', 'Liabilities'])
            ->select([
                'journal_entries.id as journal_id',
                'journal_entries.date',
                'journal_items.name as transaction_type',
                'journal_items.type as transaction_type_code',
                'journal_entries.reference as num',
                'journal_items.description as memo',
                DB::raw('COALESCE(journal_items.debit, 0) as debit'),
                DB::raw('COALESCE(journal_items.credit, 0) as credit'),
                DB::raw('COALESCE(journal_items.debit, 0) - COALESCE(journal_items.credit, 0) as amount'),
                'chart_of_accounts.id as account_id',
                'chart_of_accounts.name as account_name',
                'chart_of_accounts.code as account_code',
                'chart_of_accounts.parent as parent_id',
                'chart_of_account_sub_types.id as subtype_db_id',
                'chart_of_account_sub_types.name as subtype_name',
                'chart_of_account_types.name as type_name',
            ])
            ->orderBy('chart_of_account_types.name')
            ->orderBy('chart_of_account_sub_types.name')
            ->orderBy('chart_of_accounts.parent')
            ->orderBy('chart_of_accounts.name')
            ->orderBy('journal_entries.date')
            ->get();

        // Grouping + formatting
        // $groupedByType = $entries->groupBy('type_name');
        $order = ['Assets', 'Liabilities', 'Equity'];

        $groupedByType = $entries->groupBy('type_name')
            ->sortBy(function ($group, $key) use ($order) {
                return array_search($key, $order);
            });
        $report = collect();
        $totalAssets = 0;
        $totalLiabilities = 0;
        $totalEquity = 0;
        
        foreach ($groupedByType as $typeName => $typeEntries) {

            $report->push((object)[
                'is_type_header' => true,
                'account_name' => $typeName,
                'level' => 0,
            ]);

            $groupedBySubType = $typeEntries->groupBy('subtype_name');

            $typeTotal = 0; // Reset type total before looping subtypes
            
            foreach ($groupedBySubType as $subTypeName => $subEntries) {
                $subtypeId = 'subtype_' . str_replace(' ', '_', strtolower($subTypeName ?: 'uncategorized'));
                $hasAccounts = $subEntries->groupBy('account_id')->count() > 0;

                $report->push((object)[
                    'is_subtype_header' => true,
                    'account_name' => $subTypeName ?: 'Uncategorized',
                    'subtype_id' => $subtypeId,
                    'has_children' => $hasAccounts,
                    'level' => 1,
                ]);

                // Process individual accounts and collect their balances
                $accountBalances = [];
                $report = $this->processAccounts($subEntries, $report, null, 2, $subtypeId, $accountBalances);

                // --- Calculate subtotal for this subtype from account balances ---
                $subTypeTotal = array_sum($accountBalances);

                // Push subtype total
                $report->push((object)[
                    'is_subtype_total' => true,
                    'account_name' => $subTypeName ?: 'Uncategorized',
                    'parent_subtype_id' => $subtypeId,
                    'balance' => $subTypeTotal,
                    'level' => 1,
                ]);

                $typeTotal += $subTypeTotal; // Add to type total
            }

            // --- Add Profit & Loss for Equity ---
            if ($typeName == 'Equity') {
                $netProfit = $this->calculateNetProfit();
                $netPreviousProfit = $this->calculatePerviousNetProfit();
                
                 // Net Income
                $report->push((object)[
                    'is_subtype_total' => true,
                    'account_name' => "Net Income",
                    'balance' => $netProfit,
                    'level' => 1,
                ]);
                // Retained Earnings
                $report->push((object)[
                    'is_subtype_total' => true,
                    'account_name' => "Retained Earnings",
                    'balance' => $netPreviousProfit,
                    'level' => 1,
                ]);

               

                $typeTotal += $netProfit + $netPreviousProfit; // Add to Equity total
            }

            // --- Push type total ---
            $report->push((object)[
                'is_type_total' => true,
                'account_name' => $typeName,
                'balance' => $typeTotal,
                'level' => 0,
            ]);

            // Save totals for final summary
            if ($typeName === 'Assets') $totalAssets = $typeTotal;
            if ($typeName === 'Liabilities') $totalLiabilities = $typeTotal;
            if ($typeName === 'Equity') $totalEquity = $typeTotal;
        }

        // --- Push final Liabilities & Equity total ---
        $report->push((object)[
            'is_type_total' => true,
            'account_name' => "Total Liabilities & Equity",
            'balance' => $totalLiabilities + $totalEquity,
            'level' => 0,
        ]);

        return $report;
    }

    private function calculateNetProfit()
    {
        $income = DB::table('journal_items')
            ->join('chart_of_accounts', 'journal_items.account', '=', 'chart_of_accounts.id')
            ->join('chart_of_account_types', 'chart_of_accounts.type', '=', 'chart_of_account_types.id')
            ->join('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
            ->where("journal_entries.{$this->owner}", $this->companyId)
            ->whereBetween('journal_entries.date', [$this->startDate, $this->asOfDate])
            ->where('chart_of_account_types.name', 'Income')
            ->selectRaw('COALESCE(SUM(journal_items.credit - journal_items.debit), 0) as total')
            ->value('total');
            
        $expensesAndCogs = DB::table('journal_items')
            ->join('chart_of_accounts', 'journal_items.account', '=', 'chart_of_accounts.id')
            ->join('chart_of_account_types', 'chart_of_accounts.type', '=', 'chart_of_account_types.id')
            ->join('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
            ->where("journal_entries.{$this->owner}", $this->companyId)
            ->whereBetween('journal_entries.date', [$this->startDate, $this->asOfDate])
            ->whereIn('chart_of_account_types.name', ['Expenses', 'Costs of Goods Sold'])
            ->selectRaw('COALESCE(SUM(journal_items.debit - journal_items.credit), 0) as total')
            ->value('total');
            
        return $income - $expensesAndCogs;
    }
    
    private function calculatePerviousNetProfit()
    {
        $income = DB::table('journal_items')
            ->join('chart_of_accounts', 'journal_items.account', '=', 'chart_of_accounts.id')
            ->join('chart_of_account_types', 'chart_of_accounts.type', '=', 'chart_of_account_types.id')
            ->join('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
            ->where("journal_entries.{$this->owner}", $this->companyId)
            ->where('journal_entries.date', '<', $this->startDate)
            ->where('chart_of_account_types.name', 'Income')
            ->selectRaw('COALESCE(SUM(journal_items.credit - journal_items.debit), 0) as total')
            ->value('total');
            
        $expensesAndCogs = DB::table('journal_items')
            ->join('chart_of_accounts', 'journal_items.account', '=', 'chart_of_accounts.id')
            ->join('chart_of_account_types', 'chart_of_accounts.type', '=', 'chart_of_account_types.id')
            ->join('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
            ->where("journal_entries.{$this->owner}", $this->companyId)
            ->where('journal_entries.date', '<', $this->startDate)
            ->whereIn('chart_of_account_types.name', ['Expenses', 'Costs of Goods Sold'])
            ->selectRaw('COALESCE(SUM(journal_items.debit - journal_items.credit), 0) as total')
            ->value('total');
            
        return $income - $expensesAndCogs;
    }

    protected function processAccounts($accounts, $report, $parentId = null, $level = 0, $subtypeId = null, &$accountBalances = [])
{
    // Get all direct child accounts under this parent
    // $childAccounts = $accounts->where('parent_id', $parentId)->unique('account_id');
    $childAccounts = $accounts->where('parent', $parentId)->unique('account_id');

    foreach ($childAccounts as $account) {
        $accountId   = $account->account_id;
        $accountName = $account->account_name;
        $accountCode = $account->account_code;
        $accountType = $account->type_name;

        // Transactions for this account
        $lines = $accounts->where('account_id', $accountId);

        // Check if it has child accounts
        $hasChildAccounts = $accounts->where('parent', $accountId)->count() > 0;
        $hasTransactions  = $lines->count() > 0;
        $hasChildren      = $hasChildAccounts || $hasTransactions;

        // -----------------------------
        // 🔹 ACCOUNT HEADER
        // -----------------------------
        $report->push((object)[
            'is_account_header'  => true,
            'account_name'       => $accountName,
            'account_code'       => $accountCode,
            'account_id'         => $accountId,
            'parent_subtype_id'  => $subtypeId,
            'has_children'       => $hasChildren,
            'level'              => $level,
        ]);

        // -----------------------------
        // 🔹 OPENING BALANCE
        // -----------------------------
        $opening = DB::table('journal_items')
            ->join('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
            ->where('journal_items.account', $accountId)
            ->where("journal_entries.{$this->owner}", $this->companyId)
            ->where('journal_entries.date', '<', date('Y-m-d', strtotime($this->startDate)))
            ->selectRaw('
                SUM(journal_items.debit) as total_debit,
                SUM(journal_items.credit) as total_credit
            ')
            ->first();

        $openingBalance = in_array($accountType, ['Assets', 'Expenses', 'Costs of Goods Sold'])
            ? (($opening->total_debit ?? 0) - ($opening->total_credit ?? 0))
            : (($opening->total_credit ?? 0) - ($opening->total_debit ?? 0));

        $balance = $openingBalance;

        // Always show opening balance row
        $report->push((object)[
            'is_transaction'       => true,
            'parent_account_id'    => $accountId,
            'parent_subtype_id'    => $subtypeId,
            'transaction_id'       => null,
            'date'                 => null,
            'transaction_type'     => 'Opening Balance',
            'num'                  => '',
            'memo'                 => '',
            'split_account'        => '',
            'debit'                => null,
            'credit'               => null,
            'amount'               => null,
            'balance'              => $openingBalance,
            'level'                => $level + 1,
        ]);

        // -----------------------------
        // 🔹 TRANSACTIONS
        // -----------------------------
        foreach ($lines as $line) {
            $amount = in_array($accountType, ['Assets', 'Expenses', 'Costs of Goods Sold'])
                ? (($line->debit ?? 0) - ($line->credit ?? 0))
                : (($line->credit ?? 0) - ($line->debit ?? 0));

            $balance += $amount;

            $splitAccounts = DB::table('journal_items')
                ->join('chart_of_accounts', 'journal_items.account', '=', 'chart_of_accounts.id')
                ->where('journal_items.journal', $line->journal_id)
                ->where('journal_items.account', '!=', $line->account_id)
                ->pluck('chart_of_accounts.name')
                ->implode(', ');

            $report->push((object)[
                'is_transaction'        => true,
                'parent_account_id'     => $accountId,
                'parent_subtype_id'     => $subtypeId,
                'transaction_id'        => $line->journal_id,
                'date'                  => $line->date,
                'transaction_type'      => $line->transaction_type,
                'transaction_type_code' => $line->transaction_type_code,
                'num'                   => $line->num,
                'memo'                  => $line->memo,
                'split_account'         => $splitAccounts,
                'debit'                 => (float) ($line->debit ?? 0),
                'credit'                => (float) ($line->credit ?? 0),
                'amount'                => (float) $amount,
                'balance'               => $balance,
                'level'                 => $level + 1,
            ]);
        }

        // -----------------------------
        // 🔹 RECURSIVE CHILDREN
        // -----------------------------
        $report = $this->processAccounts($accounts, $report, $accountId, $level + 1, $subtypeId, $accountBalances);

        // -----------------------------
        // 🔹 ADD CHILD BALANCES TO TOTAL
        // -----------------------------
        $childIds      = $accounts->where('parent_id', $accountId)->pluck('account_id')->toArray();
        $childBalances = collect($accountBalances)->only($childIds)->sum();
        $balance      += $childBalances;

        $accountBalances[$accountId] = $balance;

        // -----------------------------
        // 🔹 ACCOUNT TOTAL
        // -----------------------------
        $report->push((object)[
            'is_account_total'     => true,
            'account_name'         => $accountName,
            'parent_account_id'    => $accountId,
            'parent_subtype_id'    => $subtypeId,
            'balance'              => $balance,
            'level'                => $level,
        ]);
    }

    return $report;
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
                'scrollY' => '600px',
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
            Column::make('account')->title('Transaction Date'),
            Column::make('transaction_type')->title('Type'),
            Column::make('num')->title('Num'),
            Column::make('name')->title('Name'),
            Column::make('memo')->title('Memo/Description'),
            Column::make('split')->title('Split Account'),
            Column::make('debit')->title('Debit')->addClass('text-right'),
            Column::make('credit')->title('Credit')->addClass('text-right'),
            Column::make('amount')->title('Amount')->addClass('text-right'),
            Column::make('balance')->title('Balance')->addClass('text-right'),
        ];
    }
}