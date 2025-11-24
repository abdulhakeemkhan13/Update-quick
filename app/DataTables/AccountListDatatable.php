<?php

namespace App\DataTables;

use App\Models\ChartOfAccount;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Button;

class AccountListDatatable extends DataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->editColumn('name', fn($row) => $row->name ?? '-')
            ->editColumn('code', fn($row) => $row->code ?? '-')
            ->editColumn('type', fn($row) => ucfirst($row->type ?? '-'))
            ->editColumn('sub_type', fn($row) => ucfirst($row->sub_type ?? '-'))
            ->editColumn('parent', fn($row) => $row->parent ? $row->parentAccount->name ?? '-' : '-')
            ->editColumn('is_enabled', fn($row) => $row->is_enabled ? '<span class="">Yes</span>' : '<span class="badge bg-danger">No</span>')
            ->editColumn('company_id', fn($row) => $row->company->name ?? '-')
            ->editColumn('created_at', fn($row) => $row->created_at ? $row->created_at->format('Y-m-d') : '-')
            ->editColumn('updated_at', fn($row) => $row->updated_at ? $row->updated_at->format('Y-m-d') : '-')
            ->rawColumns(['is_enabled']);
    }

    public function query(ChartOfAccount $model)
    {
        return $model->newQuery()
            ->select('id', 'name', 'code', 'type', 'sub_type', 'parent', 'is_enabled', 'company_id', 'created_at', 'updated_at')
            ->where('is_enabled', 1);
    }

    public function html()
    {

        return $this->builder()
            ->setTableId('customer-balance-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0, 'asc')
            ->parameters([
                'responsive' => true,
                'autoWidth' => false,
                'paging' => false,
                // 'pageLength' => 25,
                'searching' => false,
                'info' => false,
                'ordering' => false,
                'dom' => 'Bfrtip',
                'buttons' => [
                    // Button::make('excel')
                    //     ->text('<i class="fa fa-file-excel"></i> Excel')
                    //     ->action("exportDataTable('{$tableId}', '{$pageTitle}');"),
                    // Button::make('print')->text('<i class="fa fa-print"></i> Print'),
                ],
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('id')->title('ID')->addClass('text-nowrap'),
            Column::make('name')->title('Account Name'),
            Column::make('code')->title('Code')->addClass('text-nowrap'),
            Column::make('type')->title('Type'),
            Column::make('sub_type')->title('Sub Type'),
            Column::make('parent')->title('Parent Account'),
            Column::make('is_enabled')->title('Enabled')->addClass('text-center'),
            Column::make('company_id')->title('Company')->addClass('text-nowrap'),
            Column::make('created_at')->title('Created At')->addClass('text-nowrap'),
            Column::make('updated_at')->title('Updated At')->addClass('text-nowrap'),
        ];
    }

    protected function filename(): string
    {
        return str_replace(' ', '_', $this->pageTitle ?? 'Chart_Of_Accounts') . '_' . date('YmdHis');
    }
}
