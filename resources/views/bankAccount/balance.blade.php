@extends('layouts.admin')
@section('page-title')
    {{ __('Accounts Detail') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Accounts Detail') }}</li>
@endsection


@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body table-border-style">
                    @if (isset($accounts) && is_array($accounts))
                        <table class="table table-responsive">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Mask</th>
                                    <th>Type</th>
                                    <th>Subtype</th>
                                    <th>Available</th>
                                    <th>Current</th>
                                    <th>Currency</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $total = 0; @endphp
                                @foreach ($accounts as $acct)
                                    <tr>
                                        <td>{{ $acct->name ?? '' }}</td>
                                        <td>{{ $acct->mask ?? '' }}</td>
                                        <td>{{ $acct->type ?? '' }}</td>
                                        <td>{{ $acct->subtype ?? '' }}</td>
                                        <td>{{ $acct->balances->available ?? '' }}</td>
                                        <td>
                                            {{ $acct->balances->current ?? '' }}
                                            @php
                                                $total += $acct->balances->current ?? 0;
                                            @endphp
                                        </td>
                                        <td>{{ $acct->balances->iso_currency_code ?? '' }}</td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td colspan="5"><strong>Total</strong></td>
                                    <td><strong>{{ $total }}</strong></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    @else
                        <div>No account data found.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
