<?php

namespace App\DataTables;

use App\Models\JournalItem;
use Carbon\Carbon;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Collection;

class JournalLedgerDataTable extends DataTable
{
    protected $companyId;
    protected $owner;

    public function __construct()
    {
        ini_set('memory_limit', '512M');
        $this->companyId = \Auth::user()->type === 'company'
            ? \Auth::user()->creatorId()
            : \Auth::user()->ownedId();

        $this->owner = \Auth::user()->type === 'company'
            ? 'created_by'
            : 'owned_by';
    }

    public function dataTable($query)
    {
        $entries = $query instanceof Collection ? $query : $query->get();
        $data = collect();

        if ($entries->isEmpty()) {
            $data->push([
                'id' => 'no-data',
                'transaction_date' => '',
                'transaction_type' => '',
                'num' => '',
                'name' => '',
                'memo' => 'No transactions found for the selected period.',
                'full_name' => '',
                'debit' => '',
                'credit' => '',
                'DT_RowClass' => 'no-data-row fw-bold'
            ]);

            return datatables()->collection($data)->rawColumns(['memo']);
        }

        // Group by journal
        $journalGroups = $entries->groupBy('journal');

        foreach ($journalGroups as $journalId => $journalEntries) {
            $journalEntry = $journalEntries->first()->journalEntry ?? null;
            if (!$journalEntry) continue;

            $journalDate = Carbon::parse($journalEntry->date)->format('m/d/Y');
            $totalDebit = $journalEntries->sum('debit');
            $totalCredit = $journalEntries->sum('credit');

            // Header row for journal group
            $data->push([
                'id' => 'journal-group-' . $journalId,
                'transaction_date' => "{$journalId} (" . $journalEntries->count() . " entries)",
                'transaction_type' => '',
                'num' => '',
                'name' => '',
                'memo' => "",
                'full_name' => '',
                'debit' => '',
                'credit' => '',
                'DT_RowClass' => 'journal-group fw-bold cursor-pointer',
                'DT_RowData' => ['journal-id' => $journalId]
            ]);

            // Detail rows
            foreach ($journalEntries as $entry) {
                $data->push([
                    'id' => $entry->id,
                    'transaction_date' => $journalDate,
                    'transaction_type' => ucfirst($entry->type ?? '-'),
                    'num' => '-',
                    'name' => $entry->name ?? '-',
                    'memo' => $entry->description ?? '-',
                    'full_name' => optional($entry->accounts)->name ?? '-',
                    'debit' => $entry->debit > 0 ? number_format($entry->debit, 2) : '',
                    'credit' => $entry->credit > 0 ? number_format($entry->credit, 2) : '',
                    'DT_RowClass' => 'journal-row',
                    'DT_RowData' => ['parent' => $journalId]
                ]);
            }

            // Footer total row per journal
            $data->push([
                'id' => 'journal-total-' . $journalId,
                'transaction_date' => "Total for {$journalId}",
                'transaction_type' => '',
                'num' => '',
                'name' => '',
                'memo' => "",
                'full_name' => '',
                'debit' => number_format($totalDebit, 2),
                'credit' => number_format($totalCredit, 2),
                'DT_RowClass' => 'journal-total fw-bold',
                'DT_RowData' => ['parent' => $journalId]
            ]);
        }

        return datatables()->collection($data)->rawColumns(['memo']);
    }

    public function query()
    {
        $query = JournalItem::query()
            ->with([
                'accounts:id,name',
                'journalEntry:id,date,reference,owned_by'
            ])
            ->whereHas('journalEntry', function ($q) {
                $q->where("journal_entries.{$this->owner}", $this->companyId);
            });

        // Filter by date
        if (request()->filled('startDate') && request()->filled('endDate')) {
            try {
                $start = Carbon::parse(request('startDate'))->startOfDay();
                $end = Carbon::parse(request('endDate'))->endOfDay();
                $query->whereHas('journalEntry', fn($q) => $q->whereBetween('date', [$start, $end]));
            } catch (\Exception $e) {
                // ignore invalid dates
            }
        }

        return $query->select([
            'journal_items.id',
            'journal_items.journal',
            'journal_items.type',
            'journal_items.name',
            'journal_items.debit',
            'journal_items.credit',
            'journal_items.description',
            'journal_items.account',
        ])
        ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal')
        ->where("journal_entries.{$this->owner}", $this->companyId)
        ->orderBy('journal_entries.date', 'asc')
        ->orderBy('journal_items.id', 'asc');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('journal-type-ledger-table')
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
                'createdRow' => "function(row, data, dataIndex) {
                    if (data.DT_RowClass) {
                        $(row).addClass(data.DT_RowClass);
                    }
                    if (data.DT_RowData) {
                        for (let key in data.DT_RowData) {
                            $(row).attr('data-' + key, data.DT_RowData[key]);
                        }
                    }
                    if ($(row).hasClass('journal-group')) {
                        const memoCell = $('td:eq(0)', row);
                        memoCell.html('<span class=\"expand-icon\">▼</span>' + memoCell.text());
                        $(row).addClass('clickable');
                    }
                    $('td:eq(6), td:eq(7)', row).addClass('text-right');
                }"
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('id')->title('ID')->visible(false),
            Column::make('transaction_date')->title('Transaction Date'),
            Column::make('transaction_type')->title('Transaction Type'),
            Column::make('num')->title('Num'),
            Column::make('name')->title('Name'),
            Column::make('memo')->title('Memo/Description'),
            Column::make('full_name')->title('Full Name'),
            Column::make('debit')->title('Debit'),
            Column::make('credit')->title('Credit'),
        ];
    }
}
