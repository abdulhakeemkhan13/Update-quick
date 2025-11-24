<?php

namespace App\DataTables;

use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use App\Models\JournalItem;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LedgerDataTable extends DataTable
{
    public $accountId1;

    protected $companyId;

    protected $owner;


    public function __construct()
    {
        $this->companyId = \Auth::user()->type === 'company' ? \Auth::user()->creatorId() : \Auth::user()->ownedId();
        $this->owner = \Auth::user()->type === 'company' ? 'created_by' : 'owned_by';
    }

    /** Call this from the controller */
    public function setAccountId($accountId1): self
    {
        $this->accountId1 = $accountId1 ? $accountId1 : 'all';
        return $this;
    }

    public function dataTable($query)
    {
        $runningBalance = 0;
        $openingBalance = $this->getOpeningBalance();
        $accountType = $this->getAccountType();

        // Safely handle the query
        $entries = $query instanceof Collection ? $query : $query->get();

        // Prepare the data collection
        $data = collect();

        // If specific account is selected, or we have entries
        if ((!empty($entries) && $entries->count() > 0) || $this->accountId1 !== 'all') {
            // If specific account is selected
            if ($this->accountId1 !== 'all') {
                $account = ChartOfAccount::find($this->accountId1);

                if ($account) {
                    // Get entries for this account
                    $accountEntries = $entries->where('account', $this->accountId1);
                $accountTotal = $openingBalance + $accountEntries->sum('debit') - $accountEntries->sum('credit');

                    // Add account header
                    $data->push([
                        'id' => 'group-' . $account->id,
                        'date' => '',
                        'voucher_no' => '',
                        'account_name' => $account->name . ' (' . $accountEntries->count() . ')',
                        'debit' => '',
                        'credit' => '',
                        'memo' => '',
                        'running_balance' => number_format($accountTotal, 2),
                        'DT_RowClass' => 'account-group',
                        'DT_RowData' => ['account-id' => $account->id]
                    ]);

                    // Add opening balance row if applicable
                    if (in_array($accountType, ['Assets', 'Liabilities', 'Equity'])) {
                        $data->push([
                            'id' => 'opening-' . $account->id,
                            'date' => request('startDate') ?? Carbon::now()->startOfMonth()->format('Y-m-d'),
                            'voucher_no' => '',
                            'account_name' => 'Beginning Balance',
                            'debit' => '',
                            'credit' => '',
                            'memo' => '',
                            'running_balance' => number_format($openingBalance, 2),
                            'DT_RowClass' => 'account-row opening-balance',
                            'DT_RowData' => ['parent' => $account->id]
                        ]);

                        $runningBalance = $openingBalance;
                    } else {
                        $runningBalance = 0;
                    }

                    // Add transaction rows
                    foreach ($accountEntries->sortBy(function ($item) {
                        return optional($item->journalEntry)->date ?? '';
                    }) as $entry) {
                        $runningBalance += ($entry->debit - $entry->credit);
                        $journalEntry = $entry->journalEntry;

                        $data->push([
                            'id' => $entry->id,
                            'date' => $journalEntry ? Carbon::parse($journalEntry->date)->format('m/d/Y') : '',
                            'voucher_no' => $journalEntry ? $journalEntry->reference : '',
                            'account_name' => $account->name,
                            'debit' => $entry->debit > 0 ? number_format($entry->debit, 2) : '',
                            'credit' => $entry->credit > 0 ? number_format($entry->credit, 2) : '',
                            'memo' => $entry->description,
                            'running_balance' => number_format($runningBalance, 2),
                            'DT_RowClass' => 'account-row',
                            'DT_RowData' => ['parent' => $account->id]
                        ]);
                    }
                }
            } else {
                // Group entries by account
                $accountIds = $entries->pluck('account')->unique();
                $accounts = ChartOfAccount::whereIn('id', $accountIds->filter())->get()->keyBy('id');

                foreach ($accountIds as $accountId) {
                    if (!$accountId)
                        continue;

                    $account = $accounts->get($accountId);
                    if (!$account)
                        continue;

                    $accountEntries = $entries->where('account', $accountId);
                    $accountTotal = $accountEntries->sum('debit') - $accountEntries->sum('credit');

                    // Add account header
                    $data->push([
                        'id' => 'group-' . $accountId,
                        'date' => '',
                        'voucher_no' => '',
                        'account_name' => $account->name . ' (' . $accountEntries->count() . ')',
                        'debit' => '',
                        'credit' => '',
                        'memo' => '',
                        'running_balance' => number_format($accountTotal, 2),
                        'DT_RowClass' => 'account-group',
                        'DT_RowData' => ['account-id' => $accountId]
                    ]);

                    // Reset running balance for this account
                    $runningBalance = 0;

                    // Get opening balance for this account
                    if (in_array($accountType, ['Assets', 'Liabilities', 'Equity'])) {
                        $runningBalance = $this->getOpeningBalanceForAccount($accountId);

                        $data->push([
                            'id' => 'opening-' . $accountId,
                            'date' => request('startDate') ?? Carbon::now()->startOfMonth()->format('Y-m-d'),
                            'voucher_no' => '',
                            'account_name' => 'Beginning Balance',
                            'debit' => '',
                            'credit' => '',
                            'memo' => '',
                            'running_balance' => number_format($runningBalance, 2),
                            'DT_RowClass' => 'account-row opening-balance',
                            'DT_RowData' => ['parent' => $accountId]
                        ]);
                    }

                    // Add detail rows
                    foreach ($accountEntries->sortBy(function ($item) {
                        return optional($item->journalEntry)->date ?? '';
                    }) as $entry) {
                        $runningBalance += ($entry->debit - $entry->credit);
                        $journalEntry = $entry->journalEntry;

                        $data->push([
                            'id' => $entry->id,
                            'date' => $journalEntry ? Carbon::parse($journalEntry->date)->format('m/d/Y') : '',
                            'voucher_no' => $journalEntry ? $journalEntry->reference : '',
                            'account_name' => $account->name,
                            'debit' => $entry->debit > 0 ? number_format($entry->debit, 2) : '',
                            'credit' => $entry->credit > 0 ? number_format($entry->credit, 2) : '',
                            'memo' => $entry->description,
                            'running_balance' => number_format($runningBalance, 2),
                            'DT_RowClass' => 'account-row',
                            'DT_RowData' => ['parent' => $accountId]
                        ]);
                    }
                }
            }
        } else {
            // No data case - add a blank row
            $data->push([
                'id' => 'no-data',
                'date' => '',
                'voucher_no' => '',
                'account_name' => 'No transactions found for the selected period.',
                'debit' => '',
                'credit' => '',
                'memo' => '',
                'running_balance' => '',
                'DT_RowClass' => 'no-data-row'
            ]);
        }

        return datatables()
            ->collection($data)
            ->rawColumns(['account_name']);
    }

    public function query()
    {
        $query = JournalItem::query()
            ->with([
                'accounts:id,name,type,sub_type',
                'accounts.types:id,name',
                'accounts.subType:id,name',
                'journalEntry:id,date,reference,journal_id,owned_by'
            ])
            ->whereHas('journalEntry', function ($q) {
                $q->where("journal_entries.{$this->owner}", $this->companyId); // Specify the table name, 2 is for company
            });
        if (request()->filled('account_id') && request('account_id') !== 'all') {
            $query->where('account', request('account_id'));
        } elseif ($this->accountId1 !== 'all') {
            $query->where('account', $this->accountId1);
        }

        // 🔎 Filter by date range
        if (request()->filled('startDate') && request()->filled('endDate')) {
            try {
                $start = Carbon::parse(request('startDate'))->startOfDay();
                $end = Carbon::parse(request('endDate'))->endOfDay();

                $query->whereHas('journalEntry', fn($q) => $q->whereBetween('date', [$start, $end]));
            } catch (\Exception $e) {
                // Ignore invalid dates
            }
        }

        return $query->select([
            'journal_items.id as line_id',
            'journal_items.journal',
            'journal_items.account',
            'journal_items.debit',
            'journal_items.credit',
            'journal_items.description as memo',
        ])
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_items.account')
            ->join('chart_of_account_sub_types', 'chart_of_account_sub_types.id', '=', 'chart_of_accounts.sub_type')
            ->join('chart_of_account_types', 'chart_of_account_types.id', '=', 'chart_of_account_sub_types.type')
            ->where("journal_entries.{$this->owner}", $this->companyId) // Explicitly reference the table
            ->orderBy('chart_of_account_types.name', 'asc')   // First by type
            ->orderBy('chart_of_account_sub_types.name', 'asc') // Then by subtype
            ->orderBy('chart_of_accounts.name', 'asc')        // Then by account
            ->orderBy('journal_entries.date', 'asc');         // Finally by entry date
    }

    /**
     * Calculate Opening Balance before startDate (only for Assets, Liabilities, Equity)
     */
    protected function getOpeningBalance(): float
    {
        if (!request()->filled('startDate')) {
            return 0;
        }

        try {
            $start = Carbon::parse(request('startDate'))->startOfDay();
        } catch (\Exception $e) {
            return 0;
        }

        $accountType = $this->getAccountType();
        if (!in_array($accountType, ['Assets', 'Liabilities', 'Equity'])) {
            return 0; // Income & Expense reset each year
        }

        $query = \DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal')
            ->where("journal_entries.{$this->owner}", $this->companyId) // Specify the table name
            ->where('journal_entries.date', '<', $start);

        if (request()->filled('account_id') && request('account_id') !== 'all') {
            $query->where('journal_items.account', request('account_id'));
        } elseif ($this->accountId1 !== 'all') {
            $query->where('journal_items.account', $this->accountId1);
        }

        $totals = $query->selectRaw("SUM(debit) as total_debit, SUM(credit) as total_credit")->first();
        
        return ($totals->total_debit ?? 0) - ($totals->total_credit ?? 0);
    }

    /**
     * Calculate Opening Balance for a specific account
     */
    protected function getOpeningBalanceForAccount($accountId): float
    {
        if (!request()->filled('startDate')) {
            return 0;
        }

        try {
            $start = Carbon::parse(request('startDate'))->startOfDay();
        } catch (\Exception $e) {
            return 0;
        }

        $account = ChartOfAccount::find($accountId);
        if (!$account) {
            return 0;
        }

        // Get account type
        $accountType = optional($account->types)->name;
        if (!in_array($accountType, ['Assets', 'Liabilities', 'Equity'])) {
            return 0; // Income & Expense reset each year
        }

        $query = \DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal')
            ->where("journal_entries.{$this->owner}", $this->companyId)
            ->where('journal_entries.created_at', '<', date('Y-m-d 00:00:01', strtotime($start)))
            // ->where('journal_entries.date', '<', $start)
            ->where('journal_items.account', $accountId);

        $totals = $query->selectRaw("SUM(debit) as total_debit, SUM(credit) as total_credit")->first();

        return ($totals->total_debit ?? 0) - ($totals->total_credit ?? 0);
    }

    /**
     * Get the selected account type (via chart_of_account_types.name)
     */
    protected function getAccountType(): ?string
    {
        $accountId = request('account_id') !== 'all' ? request('account_id') : $this->accountId1;

        if ($accountId && $accountId !== 'all') {
            $account = ChartOfAccount::with([
                'types' => function ($query) {
                    $query->select('id', 'name');
                }
            ])->find($accountId);

            return optional(optional($account)->types)->name;
        }

        return null;
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('ledger-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(1, 'asc') // order by Date
            // ->dom('Bfrtip')
            ->parameters([
                'paging' => false,
                'searching' => false,
                'info' => false,
                'ordering' => false,
                'scrollY' => '500px',
                'colReorder' => true,
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
                    if (data.DT_RowAttr) {
                        for (let key in data.DT_RowAttr) {
                            $(row).attr(key, data.DT_RowAttr[key]);
                        }
                    }
                    
                    // Add expand/collapse icons
                    if ($(row).hasClass('account-group')) {
                        const accountName = $('td:eq(2)', row);
                        accountName.html('<span class=\"expand-icon\">▼</span>' + accountName.text());
                        $(row).addClass('clickable');
                    }
                    
                    // Right-align amount columns
                    $('td:eq(3), td:eq(4), td:eq(6)', row).addClass('text-right');
                    
                    // Format negative numbers
                    if (data.running_balance && data.running_balance.toString().includes('-')) {
                        $('td:eq(6)', row).addClass('negative-amount');
                    }
                }"
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('id')->title('ID')->visible(false),
            Column::make('date')->title('Date'),
            Column::make('voucher_no')->title('Reference'),
            Column::make('account_name')->title('Account'),
            Column::make('debit')->title('Debit'),
            Column::make('credit')->title('Credit'),
            Column::make('memo')->title('Memo/Description'),
            Column::make('running_balance')->title('Balance'),
        ];
    }
}