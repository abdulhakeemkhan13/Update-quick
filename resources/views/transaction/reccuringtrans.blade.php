@extends('layouts.admin')

@section('page-title')
    {{ __('Recurring Transactions') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Accounting System') }}</a></li>
    <li class="breadcrumb-item">{{ __('Transactions') }}</li>
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
    <script>
        function saveAsPDF() {
            var element = document.getElementById('printableArea');
            var opt = {
                margin: 0.3,
                filename: 'receipts-summary',
                image: {
                    type: 'jpeg',
                    quality: 1
                },
                html2canvas: {
                    scale: 4,
                    dpi: 72,
                    letterRendering: true
                },
                jsPDF: {
                    unit: 'in',
                    format: 'A4'
                }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>
@endpush

{{-- @section('action-btn')
    <div class="float-end">
        <a href="#" class="btn btn-sm btn-primary" onclick="saveAsPDF()" data-bs-toggle="tooltip"
            title="{{ __('Download') }}">
            <span class="btn-inner--icon"><i class="ti ti-download"></i></span>
        </a>
    </div>
@endsection --}}

@section('content')
    {{-- tabs --}}
    @include('transaction.transactions-tabs')


    {{-- Receipts table --}}
    {{--<div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="row pb-3">
                        <div class="d-flex justify-content-between align-items-center">
                    {{-- Receipts Filter Dropdown --}}
                    <div class="dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" id="receiptsFilterDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ti ti-receipt"></i> {{ __('Receipts Filter') }}
                        </button>
                        <div class="dropdown-menu p-3" style="min-width: 350px;">
                            <div class="card shadow-none border-0">
                                <div class="card-body p-0">
                                    {{ Form::open(['route' => ['receipts.index'], 'method' => 'get', 'id' => 'receipts_filter']) }}
                                    <div class="row">

                                        {{-- Invoice --}}
                                        <div class="col-12 mb-3">
                                            {{ Form::label('invoice_id', __('Invoice'), ['class' => 'form-label']) }}
                                            {{ Form::select('invoice_id', $invoiceOptions, $selectedInvoice, ['class' => 'form-control select', 'placeholder' => __('All')]) }}
                                        </div>

                                        {{-- Start Month --}}
                                        <div class="col-12 mb-3">
                                            {{ Form::label('start_month', __('Start Month'), ['class' => 'form-label']) }}
                                            {{ Form::month('start_month', request('start_month') ?: date('Y-m', strtotime('-5 month')), ['class' => 'form-control']) }}
                                        </div>

                                        {{-- End Month --}}
                                        <div class="col-12 mb-3">
                                            {{ Form::label('end_month', __('End Month'), ['class' => 'form-label']) }}
                                            {{ Form::month('end_month', request('end_month') ?: date('Y-m'), ['class' => 'form-control']) }}
                                        </div>

                                        {{-- Buttons --}}
                                        <div class="col-12 d-flex justify-content-between">
                                            <a href="{{ route('receipts.index') }}"
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
                    {{-- Export Button --}}
                    <div class="float-end">
                        <a href="#" onclick="saveAsPDF()" class="btn btn-lg btn-primary" data-bs-toggle="tooltip"
                            title="{{ __('Export') }}">
                            <i class="ti ti-file"></i> {{ __('Export') }}
                        </a>
                    </div>
                    </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Invoice') }}</th>
                                    <th>{{ __('Account') }}</th>
                                    {{-- <th>{{ __('Payment Method') }}</th> --}}
                                    <th>{{ __('Payment Type') }}</th>
                                    {{-- <th>{{ __('Currency') }}</th> --}}
                                    {{-- <th>{{ __('TXN / Order') }}</th> --}}
                                    <th>{{ __('Reference') }}</th>
                                    {{-- <th>{{ __('Receipt File') }}</th> --}}
                                    <th class="text-end">{{ __('Amount') }}</th>
                                    {{-- <th>{{ __('Description') }}</th> --}}
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($receipts as $r)
                                    <tr>
                                        <td>{{ \Auth::user()->dateFormat($r->date) }}</td>
                                        <td>
                                            {{-- show invoice readable number if present; otherwise raw id --}}
                                            {{ optional($r->invoice)->invoice_id ?? $r->invoice_id }}
                                        </td>
                                        <td>
                                            @if ($r->bankAccount)
                                                {{ $r->bankAccount->holder_name === 'Cash'
                                                    ? 'Cash'
                                                    : trim($r->bankAccount->bank_name . ' ' . $r->bankAccount->holder_name) }}
                                            @else
                                                {{ $r->account_id }}
                                            @endif
                                        </td>
                                        {{-- <td>{{ $r->payment_method ?: '-' }}</td> --}}
                                        <td>{{ $r->payment_type ?: '-' }}</td>
                                        {{-- <td>{{ $r->currency ?: '-' }}</td> --}}
                                        {{-- <td>
                                            {{ $r->txn_id ?: '-' }}
                                            @if (!empty($r->order_id))
                                                / {{ $r->order_id }}
                                            @endif
                                        </td> --}}
                                        <td>{{ $r->reference ?: '-' }}</td>
                                        {{-- <td>
                                            @if ($r->receipt)
                                                <a href="{{ \Storage::url($r->receipt) }}"
                                                    target="_blank">{{ __('View') }}</a>
                                            @else
                                                -
                                            @endif
                                        </td> --}}
                                        <td class="text-end">{{ \Auth::user()->priceFormat($r->amount) }}</td>
                                        {{-- <td>{{ $r->description ?: '-' }}</td> --}}
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center">{{ __('No receipts found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>--}}
@endsection
