<?php

namespace App\DataTables;

use App\Models\Employee;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class EmployeesContactList extends DataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->editColumn('name', fn($row) => $row->name ?? '-')
            ->editColumn('phone', fn($row) => $row->phone ?? '-')
            ->editColumn('address', fn($row) => $row->address ?? '-')
            ->editColumn('email', fn($row) => $row->email ?? '-');
    }

    public function query(Employee $model)
    {
        return $model->newQuery()
            ->select('id', 'name', 'phone', 'address', 'email')
            ->where('created_by', \Auth::user()->creatorId());
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
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('name')->title('Employee'),
            Column::make('phone')->title('Phone Number'),
            Column::make('address')->title('Address'),
            Column::make('email')->title('Email'),
        ];
    }
}
