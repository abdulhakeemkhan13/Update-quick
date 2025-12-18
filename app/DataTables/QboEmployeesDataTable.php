<?php

namespace App\DataTables;

use App\Models\Employee;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class QboEmployeesDataTable extends DataTable
{
    protected $status = 'Active';

    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('encrypted_id', function ($row) {
                return \Crypt::encrypt($row->id);
            })
            ->addColumn('avatar', function ($row) {
                $initials = $this->getInitials($row);
                $colors = ['#0077c5', '#2ca01c', '#d52b1e', '#6b6c72', '#008481'];
                $color = $colors[ord(substr($initials, 0, 1)) % count($colors)];
                return '<div class="employee-avatar" style="background-color: ' . $color . ';">' . $initials . '</div>';
            })
            ->addColumn('display_name', function ($row) {
                return $row->display_name;
            })
            ->editColumn('email', fn($row) => $row->email ?? '-')
            ->addColumn('phone_number', fn($row) => $row->primary_phone ?? '-')
            ->editColumn('status', function ($row) {
                $statusClass = $row->status === 'Active' ? 'status-active' : 'status-inactive';
                return '<span class="' . $statusClass . '">' . ($row->status ?? 'Active') . '</span>';
            })
            ->rawColumns(['avatar', 'status']);
    }

    protected function getInitials($employee)
    {
        $first = !empty($employee->first_name) ? strtoupper(substr($employee->first_name, 0, 1)) : '';
        $last = !empty($employee->last_name) ? strtoupper(substr($employee->last_name, 0, 1)) : '';
        
        if (empty($first) && empty($last)) {
            $nameParts = explode(' ', $employee->name ?? '');
            $first = !empty($nameParts[0]) ? strtoupper(substr($nameParts[0], 0, 1)) : '';
            $last = !empty($nameParts[1]) ? strtoupper(substr($nameParts[1], 0, 1)) : '';
        }
        
        return $first . $last ?: 'E';
    }

    public function query(Employee $model)
    {
        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = $user->type == 'company' ? 'created_by' : 'owned_by';

        $query = $model->newQuery()
            ->select('id', 'name', 'first_name', 'middle_initial', 'last_name', 'display_name', 'email', 'phone', 'mobile_phone', 'work_phone', 'home_phone', 'status', 'is_active')
            ->where($column, '=', $ownerId);

        // Apply status filter
        if ($this->status === 'Active') {
            $query->where(function($q) {
                $q->where('status', 'Active')
                  ->orWhereNull('status');
            });
        } elseif ($this->status === 'Inactive') {
            $query->where('status', 'Inactive');
        }
        // 'All' shows all employees

        return $query;
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('qbo-employees-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(1, 'asc')
            ->parameters([
                'dom' => 'rt',
                'paging' => true,
                'pageLength' => 25,
                'searching' => true,
                'info' => false,
                'responsive' => true,
                'language' => [
                    'emptyTable' => 'No employees found',
                ],
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::computed('avatar')
                ->title('<i class="ti ti-user" style="font-size:16px;color:#6b6c72;"></i>')
                ->width(50)
                ->orderable(false)
                ->searchable(false),
            Column::computed('display_name')
                ->title('DISPLAY NAME')
                ->orderable(true)
                ->searchable(true),
            Column::make('email')
                ->title('EMAIL ADDRESS'),
            Column::computed('phone_number')
                ->title('PHONE NUMBER')
                ->orderable(false),
            Column::make('status')
                ->title('STATUS'),
        ];
    }

    protected function filename(): string
    {
        return 'QboEmployees_' . date('YmdHis');
    }
}
