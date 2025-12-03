@extends('layouts.admin')
@section('page-title')
    {{ __('Receive Payments') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Receive Payments') }}</li>
@endsection

@section('content')
    @include('transaction.sales-tabs')

    <div class="row">
        <div class="col-sm-12">
            <div class="text-end mb-3">
                <a href="{{ route('receive-payment.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus"></i> {{ __('Receive Payment') }}
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-border-style">
            <div class="table-responsive">
                <table class="table datatable">
                    <thead>
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Invoice') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('Deposit To') }}</th>
                            <th>{{ __('Reference') }}</th>
                            <th width="10%">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr>
                                <td>{{ Auth::user()->dateFormat($payment->date) }}</td>
                                <td>{{ $payment->invoice && $payment->invoice->customer ? $payment->invoice->customer->name : '-' }}</td>
                                <td>
                                    @if($payment->invoice)
                                        <a href="{{ route('invoice.show', \Crypt::encrypt($payment->invoice->id)) }}" class="btn btn-outline-primary btn-sm">
                                            {{ Auth::user()->invoiceNumberFormat($payment->invoice->invoice_id) }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ Auth::user()->priceFormat($payment->amount) }}</td>
                                <td>{{ $payment->bankAccount ? $payment->bankAccount->bank_name . ' ' . $payment->bankAccount->holder_name : 'Undeposited Funds' }}</td>
                                <td>{{ $payment->reference ?? '-' }}</td>
                                <td>
                                    <div class="action-btn bg-info ms-2">
                                        <a href="{{ route('receive-payment.show', $payment->id) }}" 
                                           class="mx-3 btn btn-sm align-items-center" 
                                           data-bs-toggle="tooltip" 
                                           title="{{ __('View') }}">
                                            <i class="ti ti-eye text-white"></i>
                                        </a>
                                    </div>
                                    <div class="action-btn bg-danger ms-2">
                                        {!! Form::open([
                                            'method' => 'DELETE',
                                            'route' => ['receive-payment.destroy', $payment->id],
                                            'id' => 'delete-form-' . $payment->id,
                                        ]) !!}
                                        <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                           data-confirm="{{ __('Are You Sure?') }}"
                                           data-text="{{ __('This action can not be undone. Do you want to continue?') }}"
                                           data-confirm-yes="document.getElementById('delete-form-{{ $payment->id }}').submit();"
                                           data-bs-toggle="tooltip" 
                                           title="{{ __('Delete') }}">
                                            <i class="ti ti-trash text-white"></i>
                                        </a>
                                        {!! Form::close() !!}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

