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
                // This could be the same as customer_full_name or different based on requirements
                return $customer->name ?? '-';
            })
            ->addColumn('bill_address', function($customer) {
                $address = [];
                if (!empty($customer->billing_name)) {
                    $address[] = $customer->billing_name;
                }
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
                if (!empty($customer->shipping_name)) {
                    $address[] = $customer->shipping_name;
                }
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
            });
    }

    public function query(Customer $model)
    {
        $user = Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = ($user->type == 'company') ? 'created_by' : 'owned_by';

        $query = $model->newQuery()
            ->where($column, $ownerId)
            ->where('is_active', 1);

        // Apply customer name filter if provided
        if (request()->filled('customer_name') && request('customer_name') !== '') {
            $query->where('name', 'like', '%' . request('customer_name') . '%');
        }

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
                'responsive' => true,
                'autoWidth'  => false,
                'paging'     => false,
                'searching'  => false,
                'info'       => false,
                'ordering'   => false,
                'colReorder' => true,
                'fixedHeader'=> true,
                // 'scrollY'    => '420px',
                'scrollX'    => true,
                'scrollCollapse' => true,
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('customer_full_name')->data('customer_full_name')->name('name')->title(__('Customer Full Name')),
            Column::make('phone_numbers')->data('phone_numbers')->title(__('Phone Numbers')),
            Column::make('email')->data('email')->name('email')->title(__('Email')),
            Column::make('full_name')->data('full_name')->name('name')->title(__('Full Name')),
            Column::make('bill_address')->data('bill_address')->title(__('Bill Address')),
            Column::make('ship_address')->data('ship_address')->title(__('Ship Address')),
        ];
    }

    protected function filename(): string
    {
        return 'CustomerContactList_'.date('YmdHis');
    }
}