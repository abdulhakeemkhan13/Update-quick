<?php

namespace App\DataTables;

use App\Models\ChartOfAccount;
use App\Models\JournalItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class GeneralLedgerListDataTable extends DataTable
{
    protected $accountId1;
    protected $companyId;
    protected $owner;

    public function __construct()
    {
        $this->companyId = \Auth::user()->type === 'company'
            ? \Auth::user()->creatorId()
            : \Auth::user()->ownedId();

        $this->owner = \Auth::user()->type === 'company'
            ? 'created_by'
            : 'owned_by';

    }

    public function setAccountId($accountId1): self
    {
        $this->accountId1 = $accountId1 ?: 'all';
        return $this;
    }

    public function dataTable($query)
    {
        $entries = $query instanceof Collection ? $query : $query->get();
        $data = collect();

        // ✅ Load all chart of accounts — even if no journal entries exist
        $allAccounts = ChartOfAccount::with('types:id,name')
            ->where($this->owner, $this->companyId)
            ->orderBy('type')
            ->get();

        // Group all journal items by account
        $entriesByAccount = $entries->groupBy('account');

        foreach ($allAccounts as $account) {
            $accountEntries = $entriesByAccount->get($account->id, collect());
            // $accountType = optional($account->types)->name ?? '';
            $accountType =  '';

            $openingBalance = $this->getOpeningBalanceForAccount($account->id);
            $runningBalance = $openingBalance;

            $totalDebit = $accountEntries->sum('debit');
            $totalCredit = $accountEntries->sum('credit');
            $endingBalance = $openingBalance + $totalDebit - $totalCredit;

            // Account header row (similar to QuickBooks)
            $data->push([
                'id' => 'group-' . $account->id,
                'date' => '',
                'voucher_no' => '',
                'account_name' => $account->name,
                'type' => '',
                'debit' => '',
                'credit' => '',
                'memo' => '',
                'running_balance' => number_format($endingBalance, 2),
                'DT_RowClass' => 'account-group fw-bold bg-light',
                'DT_RowData' => ['account-id' => $account->id]
            ]);

            // Opening balance row
            $data->push([
                'id' => 'opening-' . $account->id,
                'date' => request('startDate') ?? Carbon::now()->startOfMonth()->format('Y-m-d'),
                'voucher_no' => '',
                'account_name' => 'Beginning Balance',
                'type' => '',
                'debit' => '',
                'credit' => '',
                'memo' => '',
                'running_balance' => number_format($openingBalance, 2),
                'DT_RowClass' => 'account-row opening-balance text-muted',
                'DT_RowData' => ['parent' => $account->id]
            ]);
            // dd($accountEntries);
            // Transaction rows
            foreach ($accountEntries->sortBy(fn($item) => optional($item->journalEntry)->date ?? '') as $entry) {
                $runningBalance += ($entry->debit - $entry->credit);
                $journalEntry = $entry->journalEntry;
                $data->push([
                    'id' => $entry->id,
                    'date' => $journalEntry ? Carbon::parse($journalEntry->date)->format('m/d/Y') : '',
                    'voucher_no' => $journalEntry?->reference ?? '',
                    'account_name' => $entry->name ?? '',
                    'account_name' => $entry->name ?? '',
                    'type' => $entry->type ?? '',
                    'debit' => $entry->debit > 0 ? number_format($entry->debit, 2) : '',
                    'credit' => $entry->credit > 0 ? number_format($entry->credit, 2) : '',
                    'memo' => $entry->description ?? '',
                    'running_balance' => number_format($runningBalance, 2),
                    'DT_RowClass' => 'account-row',
                    'DT_RowData' => ['parent' => $account->id]
                ]);
            }

                // Ending balance row (like QuickBooks “Ending Balance”)
                $data->push([
                    'id' => 'ending-' . $account->id,
                    'date' => '',
                    'voucher_no' => '',
                    'account_name' => 'Ending Balance',
                    'type' => '',
                    'debit' => '',
                    'credit' => '',
                    'memo' => '',
                    'running_balance' => number_format($endingBalance, 2),
                    'DT_RowClass' => 'account-row ending-balance fw-semibold border-top',
                    'DT_RowData' => ['parent' => $account->id]
                ]);

        }

        return datatables()
            ->collection($data)
            ->rawColumns(['account_name']);
    }

    public function query()
    {
        if (request()->filled('account_id')) {
            $this->accountId1 = request('account_id');
        }
        $query = JournalItem::query()
            ->with(['accounts:id,name', 'journalEntry:id,date,reference'])
            ->whereHas('journalEntry', fn($q) => $q->where("journal_entries.{$this->owner}", $this->companyId));
        // ✅ Date filter
        if (request()->filled('startDate') && request()->filled('endDate')) {
            $start = Carbon::parse(request('startDate'))->startOfDay();
            $end = Carbon::parse(request('endDate'))->endOfDay();
            $query->whereBetween('journal_items.created_at', [$start, $end]);
            // $query->whereHas('JournalItem', fn($q) => $q->whereBetween('created_by', [$start, $end]));
        }

        // ✅ Optional account filter
        if ($this->accountId1 !== 'all') {
            // dd($this->accountId1);
            $query->where('journal_items.account', $this->accountId1);
        }

        return $query->select([
            'journal_items.id',
            'journal_items.account',
            'journal_items.debit',
            'journal_items.credit',
            'journal_items.description',
            'journal_items.journal',
            'journal_items.type',
            'journal_items.name',
        ])
        ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal')
        ->orderBy('journal_entries.date', 'asc');
    }

    protected function getOpeningBalanceForAccount($accountId): float
    {
        if (!request()->filled('startDate')) return 0;

        $start = Carbon::parse(request('startDate'))->startOfDay();

        $totals = \DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal')
            ->where("journal_entries.{$this->owner}", $this->companyId)
            ->where('journal_items.account', $accountId)
            ->where('journal_entries.date', '<', $start)
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        return ($totals->total_debit ?? 0) - ($totals->total_credit ?? 0);
    }

    public function html()
    {
       return $this->builder()
        ->setTableId('ledger-table')
        ->columns($this->getColumns())
        ->minifiedAjax()
        ->orderBy(1, 'asc')
        ->parameters([
            'paging' => false,
            'searching' => false,
            'info' => false,
            'ordering' => false,
            'scrollY' => '500px',
            'scrollCollapse' => true,
            'autoWidth' => false, // 🔥 important
            'columnDefs' => [
                ['width' => '8%', 'targets' => 1],  // Date
                ['width' => '10%', 'targets' => 2], // Reference
                ['width' => '20%', 'targets' => 3], // Account
                ['width' => '8%', 'targets' => 4],  // Type
                ['width' => '10%', 'targets' => 5], // Debit
                ['width' => '10%', 'targets' => 6], // Credit
                ['width' => '20%', 'targets' => 7], // Memo
                ['width' => '14%', 'targets' => 8], // Balance
            ],
            'createdRow' => "function(row, data) {
                if (data.DT_RowClass) $(row).addClass(data.DT_RowClass);
                if (data.DT_RowData) for (let k in data.DT_RowData) $(row).attr('data-' + k, data.DT_RowData[k]);
                if ($(row).hasClass('account-group')) {
                    const accountName = $('td:eq(2)', row);
                    accountName.html('<span class=\"expand-icon\">▼</span> ' + accountName.text());
                    $(row).addClass('clickable');
                }
                $('td:eq(4), td:eq(5), td:eq(7)', row).addClass('text-right');
            }"
        ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('id')->visible(false),
            Column::make('date')->title('Date'),
            Column::make('voucher_no')->title('Reference'),
            Column::make('account_name')->title('Account'),
            Column::make('type')->title('Type'),
            Column::make('debit')->title('Debit'),
            Column::make('credit')->title('Credit'),
            Column::make('memo')->title('Memo / Description'),
            Column::make('running_balance')->title('Balance'),
        ];
    }
}
