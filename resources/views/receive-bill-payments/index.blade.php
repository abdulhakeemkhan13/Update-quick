@extends('layouts.admin')

@section('page-title')
    {{ __('Bill Payments') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Bill Payments') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="{{ route('receive-bill-payment.create') }}" class="btn btn-sm btn-primary">
            <i class="ti ti-plus"></i> {{ __('Create Bill Payment') }}
        </a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Payment #') }}</th>
                                    <th>{{ __('Vendor') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Payment Method') }}</th>
                                    <th>{{ __('Reference') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @isset($payments)
                                    @foreach($payments as $payment)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $payment->bill && $payment->bill->vender ? $payment->bill->vender->name : '-' }}</td>
                                            <td>{{ Auth::user()->dateFormat($payment->date) }}</td>
                                            <td>{{ Auth::user()->priceFormat($payment->amount) }}</td>
                                            <td>{{ ucfirst($payment->payment_method ?? '-') }}</td>
                                            <td>{{ $payment->reference ?? '-' }}</td>
                                            <td>
                                                <form method="POST" action="{{ route('receive-bill-payment.destroy', $payment->id) }}" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this payment?') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endisset
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
