<?php

namespace App\DataTables;

use App\Models\Vender;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class VendorsContactList extends DataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->editColumn('name', fn($row) => e($row->name))
            ->editColumn('contact', function ($row) {
                if (empty($row->contact)) {
                    return '-';
                }

                // Prefix with non-breaking space to force string treatment
                return '&nbsp;' . e($row->contact);
            })
            ->rawColumns(['contact']) // allow &nbsp; to render as HTML
            ->editColumn('email', fn($row) => e($row->email ?? '-'))
            ->editColumn('billing_name', fn($row) => e($row->billing_name ?? '-'))
            ->editColumn('billing_address', fn($row) => e($row->billing_address ?? '-'))
            ->editColumn('vender_id', fn($row) => e($row->vender_id ?: '-'));
    }

    public function query(Vender $model)
    {
        return $model->newQuery()
            ->select('id', 'vender_id', 'name', 'contact', 'email', 'billing_name', 'billing_address')
            ->whereNotNull('name')
            ->where('name', '!=', '');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('customer-balance-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0, 'asc')
            ->parameters([
                'paging' => false,
                'searching' => false,
                'info' => false,
                'ordering' => false,
                'responsive' => true,

                // 🚫 disable DataTables numeric formatting
                'columnDefs' => [
                    [
                        'targets' => [1], // column index of Phone Number (0-based)
                        'type' => 'string', // force text
                        'render' => 'function(data, type, row, meta) { return data; }',
                    ],
                ],
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('name')->title('Vendor'),
            Column::make('contact')
                ->title('Phone Number')
                ->addClass('text-start'),
            Column::make('email')->title('Email'),
            Column::make('billing_name')->title('Full Name'),
            Column::make('billing_address')->title('Billing Address'),
            Column::make('vender_id')->title('Account #'),
        ];
    }
}
