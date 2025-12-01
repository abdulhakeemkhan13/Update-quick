@extends('layouts.admin')
@php
    // $profile=asset(Storage::url('uploads/avatar/'));
    $profile = \App\Models\Utility::get_file('uploads/avatar/');
@endphp
@push('css-page')
    <link rel="stylesheet" href="{{ asset('css/datatable/buttons.dataTables.min.css') }}">
    <style>
        .sales-metric {
            text-align: center;
            padding: 0 15px;
        }

        .metric-amount {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 4px;
        }

        .metric-description {
            font-size: 0.875rem;
            line-height: 1.2;
        }

        .sales-progress-container {
            margin: 20px 0 0 0;
        }

        .progress-bar-custom {
            display: flex;
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            background-color: #e9ecef;
        }

        .progress-segment {
            min-width: 2px;
            transition: all 0.3s ease;
        }

        .progress-segment:not(:last-child) {
            margin-right: 1px;
        }

        .bg-purple {
            background-color: #6f42c1 !important;
        }

        @media (max-width: 768px) {
            .sales-metric {
                padding: 0 8px;
                margin-bottom: 15px;
            }

            .metric-amount {
                font-size: 1.25rem;
            }

            .metric-description {
                font-size: 0.8rem;
            }
        }
    </style>
@endpush
@push('script-page')
    <script>
        $(document).on('click', '#billing_data', function() {
            $("[name='shipping_name']").val($("[name='billing_name']").val());
            $("[name='shipping_country']").val($("[name='billing_country']").val());
            $("[name='shipping_state']").val($("[name='billing_state']").val());
            $("[name='shipping_city']").val($("[name='billing_city']").val());
            $("[name='shipping_phone']").val($("[name='billing_phone']").val());
            $("[name='shipping_zip']").val($("[name='billing_zip']").val());
            $("[name='shipping_address']").val($("[name='billing_address']").val());
        })
    </script>
@endpush
@section('page-title')
    {{ __('Manage Customers') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Customer') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="#" data-size="md" data-bs-toggle="tooltip" title="{{ __('Import') }}"
            data-url="{{ route('customer.file.import') }}" data-ajax-popup="true"
            data-title="{{ __('Import customer CSV file') }}" class="btn btn-sm btn-primary">
            <i class="ti ti-file-import"></i>
        </a>
        <a href="{{ route('customer.export') }}" data-bs-toggle="tooltip" title="{{ __('Export') }}"
            class="btn btn-sm btn-primary">
            <i class="ti ti-file-export"></i>
        </a>

        <a href="#" data-size="lg" data-url="{{ route('customer.create') }}" data-ajax-popup="true"
            data-bs-toggle="tooltip" title="{{ __('Create') }}" data-title="{{ __('Create Customer') }}"
            class="btn btn-sm btn-primary">
            <span>{{ __('New Customer') }}</span>
            <i class="ti ti-plus"></i>
        </a>
        {{-- right modal Global --}}
        <a href="#" data-size="lg" data-url="{{ route('customer.create') }}" data-ajax-popup="true"
            data-bs-toggle="tooltip" title="{{ __('Create') }}" data-title="{{ __('Create Customer') }}"
            class="btn btn-sm btn-primary">
            <span>{{ __('New Customer Modal Global') }}</span>
            <i class="ti ti-plus"></i>
        </a>
    </div>
@endsection

@section('content')
    {{-- Include Sales Tabs --}}
    @include('transaction.sales-tabs')

    {{-- Bars / Sales Data --}}
    <div class="row">
        <div class="col-sm-12">
            <div class="mt-2" id="filters">
                {{-- bars --}}
                <div class="accordion mt-3" id="salesAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="salesHeading">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                data-bs-target="#salesCollapse" aria-expanded="true" aria-controls="salesCollapse">
                                <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                    <span class="fw-bold">{{ __('Sales transactions') }}</span>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-primary small">{{ __('Give feedback') }}</span>
                                        <i class="bi bi-chat-square-text text-primary"></i>
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="salesCollapse" class="accordion-collapse collapse show" aria-labelledby="salesHeading"
                            data-bs-parent="#salesAccordion">
                            <div class="accordion-body p-4">

                                {{-- Sales Metrics Row --}}
                                <div class="row g-0 mb-3">
                                    {{-- Estimates --}}
                                    <div class="col">
                                        <div class="sales-metric">
                                            <div class="metric-amount">
                                                {{ Auth::user()->priceFormat($salesData['estimates']['amount'] ?? 0) }}
                                            </div>
                                            <div class="metric-description text-muted small">
                                                {{ $salesData['estimates']['count'] ?? 0 }} {{ __('estimates') }}
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Unbilled Income --}}
                                    <div class="col">
                                        <div class="sales-metric">
                                            <div class="metric-amount">
                                                {{ Auth::user()->priceFormat($salesData['unbilled']['amount'] ?? 0) }}
                                            </div>
                                            <div class="metric-description text-muted small">
                                                {{ __('Unbilled income') }}
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Overdue Invoices --}}
                                    <div class="col">
                                        <div class="sales-metric">
                                            <div class="metric-amount">
                                                {{ Auth::user()->priceFormat($salesData['overdue']['amount'] ?? 0) }}</div>
                                            <div class="metric-description text-muted small">
                                                {{ $salesData['overdue']['count'] ?? 0 }} {{ __('overdue invoices') }}
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Open Invoices --}}
                                    <div class="col">
                                        <div class="sales-metric">
                                            <div class="metric-amount">
                                                {{ Auth::user()->priceFormat($salesData['open']['amount'] ?? 0) }}</div>
                                            <div class="metric-description text-muted small">
                                                {{ $salesData['open']['count'] ?? 0 }}
                                                {{ __('open invoices and credits') }}
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Recently Paid --}}
                                    <div class="col">
                                        <div class="sales-metric">
                                            <div class="metric-amount">
                                                {{ Auth::user()->priceFormat($salesData['paid']['amount'] ?? 0) }}</div>
                                            <div class="metric-description text-muted small">
                                                {{ $salesData['paid']['count'] ?? 0 }} {{ __('recently paid') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Color Progress Bars --}}
                                <div class="sales-progress-container">
                                    <div class="progress-bar-custom">
                                        <div class="progress-segment bg-info"
                                            style="flex: {{ max(1, $salesData['estimates']['amount'] ?? 0) }};"></div>
                                        <div class="progress-segment bg-purple"
                                            style="flex: {{ max(1, $salesData['unbilled']['amount'] ?? 0) }};"></div>
                                        <div class="progress-segment bg-warning"
                                            style="flex: {{ max(1, $salesData['overdue']['amount'] ?? 0) }};"></div>
                                        <div class="progress-segment bg-primary"
                                            style="flex: {{ max(1, $salesData['open']['amount'] ?? 0) }};"></div>
                                        <div class="progress-segment bg-success"
                                            style="flex: {{ max(1, $salesData['paid']['amount'] ?? 0) }};"></div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th> {{ __('Name') }}</th>
                                    <th> {{ __('Contact') }}</th>
                                    <th> {{ __('Email') }}</th>
                                    <th> {{ __('Balance') }}</th>
                                    <th> {{ __('Qb Balance') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($customers as $k => $customer)
                                    <tr class="cust_tr" id="cust_detail"
                                        data-url="{{ route('customer.show', $customer['id']) }}"
                                        data-id="{{ $customer['id'] }}">
                                        <td class="Id">
                                            @can('show customer')
                                                <a href="{{ route('customer.show', \Crypt::encrypt($customer['id'])) }}"
                                                    class="btn btn-outline-primary">
                                                    {{ AUth::user()->customerNumberFormat($customer['customer_id']) }}
                                                </a>
                                            @else
                                                <a href="#" class="btn btn-outline-primary">
                                                    {{ AUth::user()->customerNumberFormat($customer['customer_id']) }}
                                                </a>
                                            @endcan
                                        </td>
                                        <td class="font-style">{{ $customer['name'] }}</td>
                                        <td>{{ $customer['contact'] }}</td>
                                        <td>{{ $customer['email'] }}</td>
                                        <td>{{ \Auth::user()->priceFormat($customer['balance']) }}</td>
                                        <td>{{ \Auth::user()->priceFormat($customer['qb_balance']) }}</td>
                                        <td class="Action">
                                            <span>
                                                @if ($customer['is_active'] == 0)
                                                    <i class="ti ti-lock" title="Inactive"></i>
                                                @else
                                                    @can('show customer')
                                                        <div class="action-btn bg-info ms-2">
                                                            <a href="{{ route('customer.show', \Crypt::encrypt($customer['id'])) }}"
                                                                class="mx-3 btn btn-sm align-items-center"
                                                                data-bs-toggle="tooltip" title="{{ __('View') }}">
                                                                <i class="ti ti-eye text-white text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endcan
                                                    @can('edit customer')
                                                        <div class="action-btn bg-primary ms-2">
                                                            <a href="#" class="mx-3 btn btn-sm  align-items-center"
                                                                data-url="{{ route('customer.edit', $customer['id']) }}"
                                                                data-ajax-popup="true" data-size="lg"
                                                                data-bs-toggle="tooltip" title="{{ __('Edit') }}"
                                                                data-title="{{ __('Edit Customer') }}">
                                                                <i class="ti ti-pencil text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endcan
                                                    @can('delete customer')
                                                        <div class="action-btn bg-danger ms-2">
                                                            {!! Form::open([
                                                                'method' => 'DELETE',
                                                                'route' => ['customer.destroy', $customer['id']],
                                                                'id' => 'delete-form-' . $customer['id'],
                                                            ]) !!}
                                                            <a href="#"
                                                                class="mx-3 btn btn-sm  align-items-center bs-pass-para"
                                                                data-bs-toggle="tooltip" title="{{ __('Delete') }}"><i
                                                                    class="ti ti-trash text-white text-white"></i></a>
                                                            {!! Form::close() !!}
                                                        </div>
                                                    @endcan
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
