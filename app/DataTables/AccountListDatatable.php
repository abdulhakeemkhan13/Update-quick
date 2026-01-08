<?php

namespace App\DataTables;

use App\Models\ChartOfAccount;
use App\Models\JournalItem;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Button;

class AccountListDatatable extends DataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('full_name', fn($row) => $row->name ?? '-')
            ->addColumn('type_name', fn($row) => $row->types->name ?? '-')
            ->addColumn('detail_type', fn($row) => $row->subType->name ?? '-')
            ->editColumn('description', fn($row) => $row->description ?? '-')
            ->addColumn('total_balance', function ($row) {
                // Calculate balance from journal items
                $journalItem = JournalItem::selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) as balance')
                    ->where('account', $row->id)
                    ->first();
                
                $balance = $journalItem->balance ?? 0;
                
                // Use qb_balance if no journal entries exist
                if ($balance == 0 && $row->qb_balance) {
                    $balance = $row->qb_balance;
                }
                
                // Format with proper alignment (negative values in parentheses)
                if ($balance < 0) {
                    return '<span class="text-end d-block">' . number_format(abs($balance), 2) . '</span>';
                }
                return '<span class="text-end d-block">' . number_format($balance, 2) . '</span>';
            })
            ->rawColumns(['total_balance']);
    }

    public function query(ChartOfAccount $model)
    {
        return $model->newQuery()
            ->with(['types', 'subType'])
            ->select('id', 'name', 'code', 'type', 'sub_type', 'parent', 'is_enabled', 'description', 'qb_balance', 'created_by')
            ->where('is_enabled', 1);
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('account-list-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0, 'asc')
            ->parameters([
                'responsive' => true,
                'autoWidth' => false,
                'paging' => false,
                'searching' => false,
                'info' => false,
                'ordering' => true,
                'dom' => 'Bfrtip',
                'buttons' => [],
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('full_name')->title('Full name')->addClass('text-nowrap'),
            Column::make('type_name')->title('Type')->orderable(true),
            Column::make('detail_type')->title('Detail type'),
            Column::make('description')->title('Description'),
            Column::make('total_balance')->title('Total balance')->addClass('text-end'),
        ];
    }

    protected function filename(): string
    {
        return str_replace(' ', '_', $this->pageTitle ?? 'Account_List') . '_' . date('YmdHis');
    }
}
