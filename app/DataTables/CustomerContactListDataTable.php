<?php

namespace App\DataTables;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CustomerContactListDataTable extends DataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('customer_full_name', function($customer) {
                return $customer->name ?? '-';
            })
            ->addColumn('phone_numbers', function($customer) {
                $phones = [];
                if (!empty($customer->contact)) {
                    $phones[] = $customer->contact;
                }
                if (!empty($customer->billing_phone)) {
                    $phones[] = $customer->billing_phone;
                }
                if (!empty($customer->shipping_phone)) {
                    $phones[] = $customer->shipping_phone;
                }
                return !empty($phones) ? implode(', ', array_unique($phones)) : '-';
            })
            ->addColumn('email', function($customer) {
                return $customer->email ?? '-';
            })
            ->addColumn('full_name', function($customer) {
                return $customer->name ?? '-';
            })
            ->addColumn('bill_address', function($customer) {
                $address = [];
                if (!empty($customer->billing_address)) {
                    $address[] = $customer->billing_address;
                }
                if (!empty($customer->billing_city)) {
                    $address[] = $customer->billing_city;
                }
                if (!empty($customer->billing_state)) {
                    $address[] = $customer->billing_state;
                }
                if (!empty($customer->billing_zip)) {
                    $address[] = $customer->billing_zip;
                }
                if (!empty($customer->billing_country)) {
                    $address[] = $customer->billing_country;
                }
                return !empty($address) ? implode(', ', $address) : '-';
            })
            ->addColumn('ship_address', function($customer) {
                $address = [];
                if (!empty($customer->shipping_address)) {
                    $address[] = $customer->shipping_address;
                }
                if (!empty($customer->shipping_city)) {
                    $address[] = $customer->shipping_city;
                }
                if (!empty($customer->shipping_state)) {
                    $address[] = $customer->shipping_state;
                }
                if (!empty($customer->shipping_zip)) {
                    $address[] = $customer->shipping_zip;
                }
                if (!empty($customer->shipping_country)) {
                    $address[] = $customer->shipping_country;
                }
                return !empty($address) ? implode(', ', $address) : '-';
            })
            ->addColumn('company', function($customer) {
                return $customer->billing_name ?? $customer->name ?? '-';
            })
            ->addColumn('created_on', function($customer) {
                return $customer->created_at ? $customer->created_at->format('m/d/Y') : '-';
            })
            ->addColumn('customer', function($customer) {
                return $customer->name ?? '-';
            })
            ->addColumn('deleted', function($customer) {
                return $customer->is_active ? 'No' : 'Yes';
            })
            ->addColumn('ship_city', function($customer) {
                return $customer->shipping_city ?? '-';
            })
            ->addColumn('ship_state', function($customer) {
                return $customer->shipping_state ?? '-';
            });
    }

    public function query(Customer $model)
    {
        $user = Auth::user();

        $query = $model->newQuery()
            ->where('created_by', $user->creatorId());

        // Apply status filter (default: active only)
        $status = request()->get('status', '1'); // Default to active
        if ($status !== 'all') {
            $query->where('is_active', (int) $status);
        }

        // Apply customer name filter if provided
        if (request()->filled('customer_name') && request('customer_name') !== '') {
            $query->where('name', 'like', '%' . request('customer_name') . '%');
        }

        $query->orderByRaw("CASE WHEN name REGEXP '^[0-9]' THEN 0 ELSE 1 END ASC")->orderBy('name', 'asc');

        return $query;
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('customer-contact-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('rt')
            ->parameters([
                'responsive' => false,
                'autoWidth'  => true,
                'paging'     => false,
                'searching'  => false,
                'info'       => false,
                'ordering'   => true,
                'order'      => [[0, 'asc']],
            ]);
    }

    protected function getColumns()
    {
        return [
            // Default visible columns for Contact List (6 columns)
            Column::make('customer_full_name')->data('customer_full_name')->name('name')->title(__('Customer full name'))->orderable(true),
            Column::make('phone_numbers')->data('phone_numbers')->title(__('Phone numbers'))->orderable(true),
            Column::make('email')->data('email')->name('email')->title(__('Email'))->orderable(true),
            Column::make('full_name')->data('full_name')->title(__('Full name'))->orderable(true),
            Column::make('bill_address')->data('bill_address')->title(__('Bill address'))->orderable(true),
            Column::make('ship_address')->data('ship_address')->title(__('Ship address'))->orderable(true),
            
            // Hidden by default columns
            Column::make('company')->data('company')->title(__('Company'))->visible(false)->orderable(true),
            Column::make('created_on')->data('created_on')->title(__('Created on'))->visible(false)->orderable(true),
            Column::make('customer')->data('customer')->title(__('Customer'))->visible(false)->orderable(true),
            Column::make('deleted')->data('deleted')->title(__('Deleted'))->visible(false)->orderable(true),
            Column::make('ship_city')->data('ship_city')->title(__('Ship city'))->visible(false)->orderable(true),
            Column::make('ship_state')->data('ship_state')->title(__('Ship state'))->visible(false)->orderable(true),
        ];
    }

    protected function filename(): string
    {
        return 'CustomerContactList_'.date('YmdHis');
    }
}
