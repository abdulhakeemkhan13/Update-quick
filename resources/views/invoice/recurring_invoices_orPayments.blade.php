@extends('layouts.admin')
@section('page-title')
    {{ __('Recurring Invoices') }}
@endsection
@push('css-page')
    <link rel="stylesheet" href="{{ asset('css/datatable/buttons.dataTables.min.css') }}">
    <style>
        .financial-accordion {
            max-width: 100%;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .accordion-header {
            padding: 20px 25px;
            /* border-bottom: 1px solid #e9ecef; */
            cursor: pointer;
            position: relative;
        }

        .accordion-header:hover {
            background-color: #f8f9fa;
        }

        .chevron {
            position: absolute;
            right: 25px;
            top: 50%;
            transform: translateY(-50%);
            transition: transform 0.3s ease;
            color: #6c757d;
        }

        .chevron.rotated {
            transform: translateY(-50%) rotate(180deg);
        }

        .accordion-content {
            padding: 25px;
            display: none;
        }

        .accordion-content.show {
            display: block;
        }

        .financial-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 60px;
        }

        .financial-section {
            flex: 1;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .amount-large {
            font-size: 16px;
            font-weight: 500;
            color: #666;
        }

        .period-text {
            font-size: 14px;
            color: #999;
        }

        .main-amount {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
            line-height: 1;
        }

        .status-text {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
        }

        .progress-container {
            display: flex;
            gap: 0;
            height: 20px;
            border-radius: 5px;
            overflow: hidden;
            background-color: #e9ecef;
            margin-top: 10px;
        }

        .progress-container2 {
            display: flex;
            gap: 0;
            height: 20px;
            border-radius: 5px;
            overflow: hidden;
            background-color: #65f394;
            margin-top: 10px;
        }

        .progress-bar-orange {
            background-color: #FF8000;
            flex: 0 0 30%;
        }

        .progress-bar-gray {
            background-color: #e0e0e0;
            flex: 1;
        }

        .progress-bar-light-green {
            background-color: #4CAF50;
            flex: 0 0 55%;
        }

        .progress-bar-dark-green {
            background-color: #2E7D32;
            flex: 0 0 45%;
        }

        .amounts-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .amount-item {
            flex: 1;
        }

        .amount-item:first-child {
            text-align: left;
        }

        .amount-item:last-child {
            text-align: right;
        }

        .amount-value {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
            line-height: 1;
        }

        .amount-label {
            font-size: 14px;
            color: #666;
        }

        .right-section .progress-container {
            margin-top: 0;
        }

        @media (max-width: 768px) {
            .financial-row {
                flex-direction: column;
                gap: 30px;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }

            .amounts-row {
                flex-direction: column;
                gap: 15px;
            }

            .amount-item:last-child {
                text-align: left;
            }
        }
    </style>
@endpush

@push('script-page')
    <script>
        function copyToClipboard(element) {

            var copyText = element.id;
            navigator.clipboard.writeText(copyText);
            // document.addEventListener('copy', function (e) {
            //     e.clipboardData.setData('text/plain', copyText);
            //     e.preventDefault();
            // }, true);
            //
            // document.execCommand('copy');
            show_toastr('success', 'Url copied to clipboard', 'success');
        }

        function toggleAccordion() {
            const content = document.getElementById('accordionContent');
            const chevron = document.getElementById('chevron');

            if (content.classList.contains('show')) {
                content.classList.remove('show');
                chevron.classList.add('rotated');
            } else {
                content.classList.add('show');
                chevron.classList.remove('rotated');
            }
        }
    </script>
@endpush


@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Invoice') }}</li>
@endsection

@section('content')
    {{-- Include Sales Tabs --}}
    @include('transaction.sales-tabs')

    <div class="row mt-3">
        <div class="col-sm-12">
            <div class="d-flex align-items-center justify-content-between">
                <div class="card-body py-0">

                    {{-- Filter Dropdown --}}
                    <div class="dropdown mb-2">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" id="invoiceFilterDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ti ti-filter"></i> {{ __('Filters') }}
                        </button>

                        <div class="dropdown-menu p-3" style="min-width: 380px;">
                            <div class="card shadow-none border-0 mb-0">
                                <div class="card-body p-0">
                                    {{ Form::open(['route' => ['invoice.recurring-invoices'], 'method' => 'GET', 'id' => 'invoiceFilterForm']) }}
                                    <div class="row">

                                        {{-- Issue Date --}}
                                        <div class="col-12 mb-3">
                                            {{ Form::label('issue_date', __('Issue Date'), ['class' => 'form-label']) }}
                                            {{ Form::date('issue_date', request('issue_date', ''), [
                                                'class' => 'form-control',
                                                'id' => 'pc-daterangepicker-1',
                                            ]) }}
                                        </div>

                                        {{-- Customer --}}
                                        <div class="col-12 mb-3">
                                            {{ Form::label('customer', __('Customer'), ['class' => 'form-label']) }}
                                            {{ Form::select('customer', $customer, request('customer', ''), [
                                                'class' => 'form-control select',
                                                'data-placeholder' => __('Select Customer'),
                                            ]) }}
                                        </div>

                                        {{-- Status --}}
                                        <div class="col-12 mb-3">
                                            {{ Form::label('status', __('Status'), ['class' => 'form-label']) }}
                                            {{ Form::select('status', ['' => __('Select Status')] + $status, request('status', ''), [
                                                'class' => 'form-control select',
                                            ]) }}
                                        </div>

                                        {{-- Buttons --}}
                                        <div class="col-12 d-flex justify-content-between">
                                            <a href="{{ route('invoice.recurring-invoices') }}"
                                                class="btn btn-outline-secondary btn-sm" data-bs-toggle="tooltip"
                                                title="{{ __('Reset') }}">
                                                <i class="ti ti-trash-off"></i> {{ __('Reset') }}
                                            </a>

                                            <button type="submit" class="btn btn-success btn-sm" data-bs-toggle="tooltip"
                                                title="{{ __('Apply') }}">
                                                <i class="ti ti-search"></i> {{ __('Apply') }}
                                            </button>
                                        </div>

                                    </div>
                                    {{ Form::close() }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Optional: show active chips inline (small summary) --}}
                    <div id="activeFilterChips" class="mt-1"></div>

                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-submit form when select filters change (customer, status)
            const selectFilterElements = document.querySelectorAll('select.auto-filter');

            selectFilterElements.forEach(function(element) {
                element.addEventListener('change', function() {
                    document.getElementById('customer_submit').submit();
                });
            });

            // Handle date field with delay to allow proper date selection
            const dateField = document.getElementById('pc-daterangepicker-1');
            let dateTimeout;

            if (dateField) {
                dateField.addEventListener('change', function() {
                    // Clear any existing timeout
                    clearTimeout(dateTimeout);

                    // Set a delay to allow user to finish selecting date
                    dateTimeout = setTimeout(function() {
                        document.getElementById('customer_submit').submit();
                    }, 1000); // 1 second delay
                });

                // Also submit when user clicks away from the date field (blur event)
                dateField.addEventListener('blur', function() {
                    clearTimeout(dateTimeout);
                    if (this.value) {
                        document.getElementById('customer_submit').submit();
                    }
                });
            }
        });
    </script>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <h5></h5>
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th> {{ __('Invoice') }}</th>
                                    {{--                                @if (!\Auth::guard('customer')->check()) --}}
                                    {{--                                    <th>{{ __('Customer') }}</th> --}}
                                    {{--                                @endif --}}
                                    <th>{{ __('Issue Date') }}</th>
                                    <th>{{ __('Due Date') }}</th>
                                    <th>{{ __('Due Amount') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    {{-- @if (Gate::check('edit invoice') || Gate::check('delete invoice') || Gate::check('show invoice'))
                                            <th>{{ __('Action') }}</th>
                                        @endif --}}
                                    {{-- <th>
                                <td class="barcode">
                                    {!! DNS1D::getBarcodeHTML($invoice->sku, "C128",1.4,22) !!}
                                    <p class="pid">{{$invoice->sku}}</p>
                                </td>
                            </th> --}}
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($invoices as $invoice)
                                    <tr>
                                        <td class="Id">
                                            <a href="{{ route('invoice.show', \Crypt::encrypt($invoice->id)) }}"
                                                style="padding-top: 11px;"
                                                class="btn btn-outline-primary">{{ AUth::user()->invoiceNumberFormat($invoice->invoice_id) }}
                                                @if ($invoice->updated_at < \Carbon\Carbon::now()->subHours(96) && $invoice->status != 4)
                                                    <small
                                                        style="position: absolute; position: absolute; margin-top: -15px; margin-left: -24px;"><span
                                                            class="badge bg-danger" data-bs-toggle="tooltip"
                                                            title="{{ __('No Activity for the Last 96 Hours ') }}">
                                                            {{ __('Follow Up') }}</span></small>
                                            </a>
                                @endif
                                </td>
                                <td>{{ Auth::user()->dateFormat($invoice->issue_date) }}</td>
                                <td>
                                    @if ($invoice->due_date < date('Y-m-d'))
                                        <p class="text-danger mt-3">
                                            {{ \Auth::user()->dateFormat($invoice->due_date) }}</p>
                                    @else
                                        {{ \Auth::user()->dateFormat($invoice->due_date) }}
                                    @endif
                                </td>
                                <td>{{ \Auth::user()->priceFormat($invoice->getDue()) }}</td>
                                <td>
                                    @if ($invoice->status == 0)
                                        <span
                                            class="status_badge badge bg-secondary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                    @elseif($invoice->status == 1)
                                        <span
                                            class="status_badge badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                    @elseif($invoice->status == 2)
                                        <span
                                            class="status_badge badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                    @elseif($invoice->status == 3)
                                        <span
                                            class="status_badge badge bg-info p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                    @elseif($invoice->status == 4)
                                        <span
                                            class="status_badge badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                    @endif
                                </td>
                                {{-- @if (Gate::check('edit invoice') || Gate::check('delete invoice') || Gate::check('show invoice'))
                                    <td class="Action">
                                        <span>
                                            @php $invoiceID= Crypt::encrypt($invoice->id); @endphp

                                            @can('copy invoice')
                                                <div class="action-btn bg-warning ms-2">
                                                    <a href="#" id="{{ route('invoice.link.copy', [$invoiceID]) }}"
                                                        class="mx-3 btn btn-sm align-items-center"
                                                        onclick="copyToClipboard(this)" data-bs-toggle="tooltip"
                                                        title="{{ __('Copy Invoice') }}"
                                                        data-original-title="{{ __('Copy Invoice') }}"><i
                                                            class="ti ti-link text-white"></i></a>
                                                </div>
                                            @endcan
                                            @can('duplicate invoice')
                                                <div class="action-btn bg-primary ms-2">
                                                    {!! Form::open([
                                                        'method' => 'get',
                                                        'route' => ['invoice.duplicate', $invoice->id],
                                                        'id' => 'duplicate-form-' . $invoice->id,
                                                    ]) !!}

                                                    <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                        data-toggle="tooltip" data-original-title="{{ __('Duplicate') }}"
                                                        data-bs-toggle="tooltip" title="Duplicate Invoice"
                                                        data-original-title="{{ __('Delete') }}"
                                                        data-confirm="You want to confirm this action. Press Yes to continue or Cancel to go back"
                                                        data-confirm-yes="document.getElementById('duplicate-form-{{ $invoice->id }}').submit();">
                                                        <i class="ti ti-copy text-white"></i>
                                                        {!! Form::open([
                                                            'method' => 'get',
                                                            'route' => ['invoice.duplicate', $invoice->id],
                                                            'id' => 'duplicate-form-' . $invoice->id,
                                                        ]) !!}
                                                        {!! Form::close() !!}
                                                    </a>
                                                </div>
                                            @endcan
                                            @can('show invoice')
                                                <div class="action-btn bg-info ms-2">
                                                    <a href="{{ route('invoice.show', \Crypt::encrypt($invoice->id)) }}"
                                                        class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip"
                                                        title="Show " data-original-title="{{ __('Detail') }}">
                                                        <i class="ti ti-eye text-white"></i>
                                                    </a>
                                                </div>
                                            @endcan
                                            @can('edit invoice')
                                                <div class="action-btn bg-primary ms-2">
                                                    <a href="{{ route('invoice.edit', \Crypt::encrypt($invoice->id)) }}"
                                                        class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip"
                                                        title="Edit " data-original-title="{{ __('Edit') }}">
                                                        <i class="ti ti-pencil text-white"></i>
                                                    </a>
                                                </div>
                                            @endcan
                                            @can('delete invoice')
                                                <div class="action-btn bg-danger ms-2">
                                                    {!! Form::open([
                                                        'method' => 'DELETE',
                                                        'route' => ['invoice.destroy', $invoice->id],
                                                        'id' => 'delete-form-' . $invoice->id,
                                                    ]) !!}
                                                    <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para "
                                                        data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                        data-original-title="{{ __('Delete') }}"
                                                        data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                        data-confirm-yes="document.getElementById('delete-form-{{ $invoice->id }}').submit();">
                                                        <i class="ti ti-trash text-white"></i>
                                                    </a>
                                                    {!! Form::close() !!}
                                                </div>
                                            @endcan
                                        </span>
                                    </td>
                                @endif --}}
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
