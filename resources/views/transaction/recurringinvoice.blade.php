@extends('layouts.admin')

@section('page-title')
    {{ __('Recurring Transactions') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Recurring Transactions') }}</li>
@endsection

@push('css-page')
    <link rel="stylesheet" href="{{ asset('css/datatable/buttons.dataTables.min.css') }}">
@endpush

@push('script-page')
    <script type="text/javascript" src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
    <script src="{{ asset('js/datatable/jszip.min.js') }}"></script>
    <script src="{{ asset('js/datatable/pdfmake.min.js') }}"></script>
    <script src="{{ asset('js/datatable/vfs_fonts.js') }}"></script>
@endpush

@section('content')
    {{-- tabs --}}
    @include('transaction.transactions-tabs')

    {{-- Filters (From/To Date + Customer) --}}
    <div class="row mt-4">
        <div class="col-sm-12">
            <div class="d-flex align-items-center justify-content-between">
                <div class="card-body py-0">

                    <div class="dropdown mb-2">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" id="invoiceFilterDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ti ti-filter"></i> {{ __('Filters') }}
                        </button>

                        <div class="dropdown-menu p-3" style="min-width: 420px;">
                            <div class="card shadow-none border-0 mb-0">
                                <div class="card-body p-0">
                                    {{ Form::open(['route' => ['report.recurring'], 'method' => 'GET', 'id' => 'invoiceFilterForm']) }}
                                    <div class="row">
                                        {{-- From Date --}}
                                        <div class="col-12 mb-3">
                                            {{ Form::label('from', __('From Date'), ['class' => 'form-label']) }}
                                            {{ Form::date('from', request('from', ''), ['class' => 'form-control', 'id' => 'date-from']) }}
                                        </div>

                                        {{-- To Date --}}
                                        <div class="col-12 mb-3">
                                            {{ Form::label('to', __('To Date'), ['class' => 'form-label']) }}
                                            {{ Form::date('to', request('to', ''), ['class' => 'form-control', 'id' => 'date-to']) }}
                                        </div>

                                        {{-- Customer (plain select, not searchable) --}}
                                        <div class="col-12 mb-3">
                                            {{ Form::label('customer', __('Customer'), ['class' => 'form-label']) }}
                                            {{ Form::select('customer', $customer, request('customer', ''), ['class' => 'form-control']) }}
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
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-1">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style mt-2">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Customer') }}</th>
                                    <th>{{ __('Invoice #') }}</th>
                                    <th>{{ __('Payments Count') }}</th>
                                    <th>{{ __('Total Amount') }}</th>
                                    <th>{{ __('Due Amount') }}</th>
                                    <th>{{ __('Total Paid') }}</th>
                                    <th>{{ __('Last Payment Date') }}</th>
                                    <th>{{ __('Category') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recurring as $key => $payments)
                                    @php
                                        $first = $payments->first();
                                        $customer = \App\Models\Customer::find($first->customer_id);
                                        $category = \App\Models\ProductServiceCategory::find($first->category_id);

                                        // We passed inv_pk from the controller for each grouped row.
                                        $invoiceModel = \App\Models\Invoice::find($first->inv_pk);
                                        $dueAmount = $invoiceModel ? $invoiceModel->getDue() : 0;

                                        // Compute totals
                                        $totalPaid = $payments->sum('amount'); // from joined payment rows
                                        $totalAmount = $dueAmount + $totalPaid; // FIX: total amount = due + paid
                                    @endphp
                                    <tr>
                                        <td>{{ $customer?->name ?? '-' }}</td>
                                        <td>#{{ $first->invoice_id }}</td>
                                        <td>{{ $payments->count() }}</td>
                                        <td>{{ Auth::user()->priceFormat($totalAmount) }}</td> {{-- FIXED --}}
                                        <td>{{ Auth::user()->priceFormat($dueAmount) }}</td>
                                        <td>{{ Auth::user()->priceFormat($totalPaid) }}</td> {{-- uses defined $totalPaid --}}
                                        <td>{{ Auth::user()->dateFormat($payments->max('date')) }}</td>
                                        <td>{{ $category?->name ?? '-' }}</td>
                                    </tr>
                                @endforeach

                                @if ($recurring->isEmpty())
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            {{ __('No recurring payments found') }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
