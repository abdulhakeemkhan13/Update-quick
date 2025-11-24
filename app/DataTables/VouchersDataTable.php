<?php

namespace App\DataTables;

use App\Models\JournalEntry;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class VouchersDataTable extends DataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('action', function ($row) {
                return '<div class="task_view">
                            <div class="dropdown">
                                <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link"
                                    id="dropdownMenuLink-' . $row->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="icon-options-vertical icons"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a class="dropdown-item" href="' . route('vouchers.show', $row->id) . '"><i class="fa fa-eye m-r-5"></i> View</a>
                                    <a class="dropdown-item" href="' . route('vouchers.edit', $row->id) . '"><i class="fa fa-edit m-r-5"></i> Edit</a>
                                    <a class="dropdown-item text-danger delete-voucher" data-id="' . $row->id . '">
                                        <i class="fa fa-trash m-r-5"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    ';
            })
            ->editColumn('status', function ($row) {
                return ucfirst($row->status);
            })
            ->editColumn('date', function ($row) {
                // format date safely
                return $row->date ? \Carbon\Carbon::parse($row->date)->format(company()->date_format) : '';
            })
            ->rawColumns(['action']);
    }

    public function query()
    {
        $query = JournalEntry::query()
            ->where('company_id', company()->id)
            ->select(['id', 'number', 'voucher_type', 'date', 'status']);

        // 🔎 Apply filters from request
        if (request()->filled('status') && request('status') !== 'all') {
            $query->where('status', request('status'));
        }

        if (request()->filled('startDate') && request()->filled('endDate')) {
            try {
                $start = \Carbon\Carbon::parse(request('startDate'))->startOfDay();
                $end = \Carbon\Carbon::parse(request('endDate'))->endOfDay();
                $query->whereBetween('date', [$start, $end]);
            } catch (\Exception $e) {
                // ignore invalid date format
            }
        }

        if (request()->filled('searchText')) {
            $search = request('searchText');
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhere('voucher_type', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('vouchers-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(1, 'desc')
            ->dom('<"row mb-3"<"col-md-6"f><"col-md-6 text-right d-flex justify-content-end p-2"B>>rtip')
            ->buttons([
                [
                    'extend' => 'excel',
                    'text' => '<i class="fa fa-file-excel"></i> Excel',
                    'className' => 'btn btn-sm btn-success'
                ],
                [
                    'extend' => 'csv',
                    'text' => '<i class="fa fa-file-csv"></i> CSV',
                    'className' => 'btn btn-sm btn-info'
                ],
                [
                    'extend' => 'pdf',
                    'text' => '<i class="fa fa-file-pdf"></i> PDF',
                    'className' => 'btn btn-sm btn-info'
                ],
                [
                    'extend' => 'print',
                    'text' => '<i class="fa fa-print"></i> Print',
                    'className' => 'btn btn-sm btn-info'
                ],
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('id')->title('ID'),
            Column::make('number')->title('Voucher No'),
            Column::make('voucher_type')->title('Type'),
            Column::make('date')->title('Date'),
            Column::make('status')->title('Status'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(120)
                ->addClass('text-center'),
        ];
    }
}
