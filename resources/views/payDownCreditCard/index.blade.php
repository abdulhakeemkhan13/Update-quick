@extends('layouts.admin')
@section('page-title')
    {{ __('Credit Card Payments') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('expense.index') }}">{{ __('Expenses') }}</a></li>
    <li class="breadcrumb-item">{{ __('Credit Card Payments') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="{{ route('paydowncreditcard.create') }}" class="btn btn-sm btn-primary">
            <i class="ti ti-plus"></i> {{ __('New Payment') }}
        </a>
    </div>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body table-border-style">
                <div class="table-responsive">
                    <table class="table datatable">
                        <thead>
                            <tr>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Credit Card') }}</th>
                                <th>{{ __('Payee') }}</th>
                                <th>{{ __('Bank Account') }}</th>
                                <th class="text-end">{{ __('Amount') }}</th>
                                <th>{{ __('Memo') }}</th>
                                <th width="120">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $payment)
                                <tr>
                                    <td>{{ $payment->payment_date->format('m/d/Y') }}</td>
                                    <td>{{ $payment->creditCardAccount ? $payment->creditCardAccount->name : '-' }}</td>
                                    <td>{{ $payment->payee_name }}</td>
                                    <td>{{ $payment->bankAccount ? $payment->bankAccount->bank_name . ' ' . $payment->bankAccount->holder_name : '-' }}</td>
                                    <td class="text-end">${{ number_format($payment->amount, 2) }}</td>
                                    <td>{{ Str::limit($payment->memo, 30) }}</td>
                                    <td class="Action">
                                        <span>
                                            <div class="action-btn bg-info ms-2">
                                                <a href="{{ route('paydowncreditcard.edit', Crypt::encrypt($payment->id)) }}" 
                                                   class="mx-3 btn btn-sm align-items-center" 
                                                   data-bs-toggle="tooltip" 
                                                   title="{{ __('Edit') }}">
                                                    <i class="ti ti-pencil text-white"></i>
                                                </a>
                                            </div>
                                            <div class="action-btn bg-danger ms-2">
                                                <form action="{{ route('paydowncreditcard.destroy', Crypt::encrypt($payment->id)) }}" 
                                                      method="POST" 
                                                      style="display: inline;"
                                                      onsubmit="return confirm('{{ __('Are you sure?') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="mx-3 btn btn-sm align-items-center" 
                                                            data-bs-toggle="tooltip" 
                                                            title="{{ __('Delete') }}">
                                                        <i class="ti ti-trash text-white"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">{{ __('No credit card payments found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
