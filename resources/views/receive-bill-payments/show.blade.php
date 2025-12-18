@extends('layouts.admin')
@section('page-title')
    {{ __('Payment Details') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('receive-payment.index') }}">{{ __('Payments') }}</a></li>
    <li class="breadcrumb-item">{{ __('Payment Details') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Payment Information') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>{{ __('Date') }}:</strong>
                            <p>{{ Auth::user()->dateFormat($payment->date) }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>{{ __('Amount') }}:</strong>
                            <p class="h4 text-success">{{ Auth::user()->priceFormat($payment->amount) }}</p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>{{ __('Customer') }}:</strong>
                            <p>{{ $payment->invoice && $payment->invoice->customer ? $payment->invoice->customer->name : '-' }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>{{ __('Invoice') }}:</strong>
                            <p>
                                @if($payment->invoice)
                                    <a href="{{ route('invoice.show', \Crypt::encrypt($payment->invoice->id)) }}" class="btn btn-outline-primary btn-sm">
                                        {{ Auth::user()->invoiceNumberFormat($payment->invoice->invoice_id) }}
                                    </a>
                                @else
                                    -
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>{{ __('Deposit To') }}:</strong>
                            <p>{{ $payment->bankAccount ? $payment->bankAccount->bank_name . ' ' . $payment->bankAccount->holder_name : 'Undeposited Funds' }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>{{ __('Reference') }}:</strong>
                            <p>{{ $payment->reference ?? '-' }}</p>
                        </div>
                    </div>

                    @if($payment->description)
                    <div class="row mb-3">
                        <div class="col-12">
                            <strong>{{ __('Memo') }}:</strong>
                            <p>{{ $payment->description }}</p>
                        </div>
                    </div>
                    @endif
                </div>
                <div class="card-footer">
                    <a href="{{ route('receive-payment.index') }}" class="btn btn-secondary">
                        <i class="ti ti-arrow-left"></i> {{ __('Back to Payments') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

