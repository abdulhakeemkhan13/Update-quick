<?php

namespace App\DataTables;

use App\Models\Voucher; // adjust model to match your receivables table
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;

class ReceivablesDataTable extends DataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('action', function ($row) {
                return '<a href="'.route('vouchers.show', $row->id).'" class="btn btn-sm btn-primary">View</a>';
            })
            ->editColumn('date', function ($row) {
                return Carbon::parse($row->date)->format('m/d/Y');
            });
    }

    public function query(Voucher $model)
    {
        // Example: only unpaid vouchers = receivables
        return $model->newQuery()
            ->where('type', 'receivable')
            ->where('status', '!=', 'paid');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('receivables-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(1);
    }

    protected function getColumns()
    {
        return [
            Column::make('id'),
            Column::make('date'),
            Column::make('reference'),
            Column::make('customer_name'),
            Column::make('amount'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(60)
                ->addClass('text-center'),
        ];
    }
}
