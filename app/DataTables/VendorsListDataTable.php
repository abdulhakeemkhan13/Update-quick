<?php

namespace App\DataTables;

use App\Models\Vender;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class VendorsListDataTable extends DataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('checkbox', function ($vender) {
                return '<input type="checkbox" class="row-checkbox form-check-input" value="' . $vender->id . '">';
            })
            ->editColumn('name', function ($vender) {
                return '<a href="' . route('vender.show', \Crypt::encrypt($vender->id)) . '" class="text-body fw-bold">' . $vender->name . '</a>';
            })
            ->editColumn('company_name', function ($vender) {
                return $vender->company_name;
            })
            ->editColumn('billing_phone', function ($vender) {
                return $vender->billing_phone;
            })
            ->editColumn('email', function ($vender) {
                return $vender->email . ($vender->email ? ' <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="currentColor" focusable="false" aria-hidden="true" class=""><path d="M19 4H5a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3ZM5 6h14a1 1 0 0 1 1 1v1.279l-7.684 2.562a1.012 1.012 0 0 1-.632 0L4 8.279V7a1 1 0 0 1 1-1Zm14 12H5a1 1 0 0 1-1-1v-6.613l7.051 2.351a3.02 3.02 0 0 0 1.9 0L20 10.387V17a1 1 0 0 1-1 1Z" fill="currentColor"></path></svg>' : '');
            })
            ->editColumn('track_payments_1099', function ($vender) {
                return $vender->track_payments_1099 ? '<i class="ti ti-check text-dark"></i>' : '';
            })
            ->editColumn('balance', function ($vender) {
                return \Auth::user()->priceFormat($vender->getDueAmount());
            })
            ->addColumn('ach_info', function ($vender) {
                return '<div class="text-muted small">Missing</div><a href="#" class="text-primary small" style="text-decoration: none;">Add payment info</a>';
            })
            ->addColumn('action', function ($vender) {
                $actions = '<div class="d-flex justify-content-end align-items-center">
                                <a href="#" class="text-primary small me-2" style="text-decoration: none; font-weight: 500;">' . __('Create Expense') . '</a>
                                <div class="dropdown">
                                    <a class="text-muted dropdown-toggle no-arrow" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-chevron-down"></i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="' . route('vender.show', \Crypt::encrypt($vender->id)) . '">' . __('View') . '</a></li>';
                
                if(\Auth::user()->can('edit vender')){
                    $actions .= '<li><a class="dropdown-item" href="#" data-size="lg" data-url="' . route('vender.edit', $vender->id) . '" data-ajax-popup="true" data-title="' . __('Edit Vendor') . '">' . __('Edit') . '</a></li>';
                }
                
                if(\Auth::user()->can('delete vender')){
                    $actions .= '<li>
                                    <form method="POST" action="' . route('vender.destroy', $vender->id) . '" id="delete-form-' . $vender->id . '">
                                        ' . csrf_field() . '
                                        <input type="hidden" name="_method" value="DELETE">
                                        <a class="dropdown-item bs-pass-para" href="#" data-confirm="' . __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') . '" data-confirm-yes="document.getElementById(\'delete-form-' . $vender->id . '\').submit();">' . __('Delete') . '</a>
                                    </form>
                                </li>';
                }
                $actions .= '<li><a class="dropdown-item" href="#">' . __('Schedule payments') . '</a></li>';

                $actions .= '</ul></div></div>';
                return $actions;
            })
            ->rawColumns(['checkbox', 'name', 'email', 'track_payments_1099', 'ach_info', 'action', 'balance']);
    }

    public function query(Vender $model)
    {
        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = ($user->type == 'company') ? 'created_by' : 'owned_by';

        $query = $model->newQuery()->where($column, $ownerId);

        if (request()->has('filter')) {
            $filter = request()->get('filter');

            if ($filter == 'unbilled') {
                $query->whereHas('purchases', function ($q) {
                    $q->where('created_at', '>=', \Carbon\Carbon::now()->subDays(365));
                });
            } elseif ($filter == 'overdue') {
                $query->whereHas('bills', function ($q) {
                    $q->where('due_date', '<', date('Y-m-d'))
                      ->where('status', '!=', 4);
                });
            } elseif ($filter == 'open') {
                $query->whereHas('bills', function ($q) {
                    $q->where('status', '!=', 4);
                });
            } elseif ($filter == 'paid') {
                $query->whereHas('bills', function ($q) {
                    $q->where('status', 4)
                      ->where('updated_at', '>=', \Carbon\Carbon::now()->subDays(30));
                });
            }
        }

        return $query;
    }

    public function html()
    {
        return $this->builder()
                    ->setTableId('vendors-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    ->dom('t')
                    ->orderBy(1)
                    ->parameters([
                        "dom" =>  "<'row'<'col-sm-12'tr>>",
                        'language' => [
                            'paginate' => [
                                'next' => '<i class="ti ti-chevron-right"></i>',
                                'previous' => '<i class="ti ti-chevron-left"></i>'
                            ]
                        ],
                        'drawCallback' => "function() {
                            $('.dataTables_paginate > .pagination').addClass('pagination-rounded');
                        }"
                    ]);
    }

    protected function getColumns()
    {
        return [
            Column::computed('checkbox')
                  ->title('<input type="checkbox" class="form-check-input" id="select-all">')
                  ->exportable(false)
                  ->printable(false)
                  ->width(20)
                  ->addClass('text-center align-middle'),
            Column::make('name')->title('VENDOR')->addClass('align-middle'),
            Column::make('company_name')->title('COMPANY NAME')->addClass('align-middle'),
            Column::make('billing_phone')->title('PHONE')->addClass('align-middle'),
            Column::make('email')->title('EMAIL')->addClass('align-middle'),
            Column::make('track_payments_1099')->title('1099 TRACKING')->addClass('text-center align-middle'),
            Column::make('balance')->title('OPEN BALANCE')->addClass('text-end align-middle'),
            Column::computed('ach_info')->title('BILL PAY ACH INFO')->addClass('align-middle'),
            Column::computed('action')
                  ->exportable(false)
                  ->printable(false)
                  ->width(150)
                  ->addClass('text-end align-middle')
                  ->title('ACTION'),
        ];
    }

    protected function filename(): string
    {
        return 'Vendors_' . date('YmdHis');
    }
}