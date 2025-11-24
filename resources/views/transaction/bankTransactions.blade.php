@extends('layouts.admin')
@section('page-title')
    {{ __('Bank Transactions') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Report') }}</li>
    <li class="breadcrumb-item">{{ __('Bank Transactions') }}</li>
@endsection
@push('css-page')
    <link rel="stylesheet" href="{{ asset('css/datatable/buttons.dataTables.min.css') }}">
    <style>
        .subacct-card {
            cursor: pointer;
            border: 1px solid #e9ecef;
            border-radius: .5rem;
            transition: box-shadow .2s, border-color .2s, background-color .2s;
        }

        .subacct-card:hover {
            box-shadow: 0 .25rem .75rem rgba(0, 0, 0, .05);
        }

        .subacct-card.is-active {
            border-color: #0d6efd;
            background: #eef5ff;
            box-shadow: 0 0 0 .2rem rgba(13, 110, 253, .15);
        }
    </style>
@endpush


@push('script-page')
    <script type="text/javascript" src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
    <script src="{{ asset('js/datatable/jszip.min.js') }}"></script>
    <script src="{{ asset('js/datatable/pdfmake.min.js') }}"></script>
    <script src="{{ asset('js/datatable/vfs_fonts.js') }}"></script>
    <script>
        // keep your saveAsPDF() …

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('bankTransaction');
            const bankSelect = document.getElementById('bank-select');

            // Auto-submit when bank changes, and clear any previously chosen account
            if (bankSelect) {
                bankSelect.addEventListener('change', function() {
                    // remove existing hidden account value (if any)
                    const oldHidden = document.getElementById('account-hidden');
                    if (oldHidden) oldHidden.remove();
                    form.submit();
                });
            }

            // Make each sub-account card clickable
            document.querySelectorAll('.js-subacct-card').forEach(function(card) {
                card.addEventListener('click', function() {
                    const id = this.getAttribute('data-account-id');

                    // create/update hidden input "account"
                    let hidden = document.getElementById('account-hidden');
                    if (!hidden) {
                        hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = 'account';
                        hidden.id = 'account-hidden';
                        form.appendChild(hidden);
                    }
                    hidden.value = id;

                    form.submit();
                });
            });
        });
    </script>
@endpush


@section('action-btn')
    <div class="float-end">
        @can('create bank account')
            <a href="#" data-url="{{ route('bank-account.create') }}" data-ajax-popup="true" data-size="lg"
                data-bs-toggle="tooltip" title="{{ __('Create') }}" data-title="{{ __('Create New Bank Account') }}"
                class="btn btn-sm btn-primary">
                {{ __('Create New Bank Account') }}
                <i class="ti ti-plus"></i>
            </a>
        @endcan

    </div>
@endsection


@section('content')
    {{-- tabs --}}
    @include('transaction.transactions-tabs')
    <div class="row mt-3">
        <div id="printableArea">
            <div class="row">
                @if ($filter['bank'])
                    @foreach ($subAccountGroups as $bankName => $items)
                        <div class="row align-items-center">
                            <div class="col-6 mb-2">
                                {{-- Bank Transactions Dropdown --}}
                                <div class="dropdown">
                                    <button class="btn dropdown-toggle d-flex align-items-center gap-2" type="button"
                                        id="bankFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        {{-- <button class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2" type="button"
                                        id="bankFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false"> --}}
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center"
                                                style="width: 32px; height: 32px;">
                                                <i class="bi bi-credit-card-2-front text-white"></i>
                                            </div>
                                            <span>{{ $filter['bank'] ?: 'Select Bank Account' }}</span>
                                        </div>
                                    </button>
                                    <div class="dropdown-menu p-0"
                                        style="min-width: 400px; max-height: 500px; overflow-y: auto;">
                                        <div class="card shadow-none border-0">
                                            <div class="card-header bg-light border-bottom">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-primary fw-bold">Hide account cards</small>
                                                    <small class="text-primary fw-bold">Reorder accounts</small>
                                                </div>
                                            </div>
                                            <div class="card-body p-0">
                                                {{ Form::open(['route' => ['transaction.bankTransactions'], 'method' => 'get', 'id' => 'bankTransaction']) }}

                                                {{-- Bank Account Cards --}}
                                                <div class="account-list">
                                                    @foreach ($allBanks->groupBy('bank_name') as $bankName => $accounts)
                                                        @php
                                                            $totalBalance = $accounts->sum('opening_balance');
                                                            $totalTransactions = $accounts->sum(function ($acc) {
                                                                return $acc->transactions
                                                                    ? $acc->transactions->count()
                                                                    : 0;
                                                            });
                                                        @endphp
                                                        <div class="account-item p-3 border-bottom {{ $filter['bank'] == $bankName ? 'bg-light' : '' }}"
                                                            style="cursor: pointer;"
                                                            onclick="selectBank('{{ $bankName }}')">
                                                            <div class="d-flex align-items-center gap-3">
                                                                <div class="account-icon bg-primary rounded-circle d-flex align-items-center justify-content-center"
                                                                    style="width: 48px; height: 48px;">
                                                                    @if (strtolower($bankName) == 'chase bank')
                                                                        <i
                                                                            class="bi bi-credit-card-2-front text-white fs-5"></i>
                                                                    @elseif(strtolower($bankName) == 'bank of america')
                                                                        <i class="bi bi-bank text-white fs-5"></i>
                                                                    @elseif(strtolower($bankName) == 'wells fargo')
                                                                        <i class="bi bi-piggy-bank text-white fs-5"></i>
                                                                    @elseif(strtolower($bankName) == 'cash')
                                                                        <i class="bi bi-cash-stack text-white fs-5"></i>
                                                                    @else
                                                                        <i class="bi bi-credit-card text-white fs-5"></i>
                                                                    @endif
                                                                </div>
                                                                <div class="account-details flex-grow-1">
                                                                    <div class="account-name fw-bold text-dark mb-1">
                                                                        {{ $bankName }}</div>
                                                                    <div class="account-balance text-muted small mb-1">
                                                                        Bank balance:
                                                                        {{ \Auth::user()->priceFormat($totalBalance) }}
                                                                    </div>
                                                                    <div class="account-transactions text-muted small">
                                                                        {{ $accounts->count() }}
                                                                        Account{{ $accounts->count() !== 1 ? 's' : '' }}
                                                                    </div>
                                                                </div>
                                                                <div class="account-actions text-end">
                                                                    <div class="updated-time text-muted small mb-2">Updated
                                                                        moments ago</div>
                                                                    @if ($filter['bank'] == $bankName)
                                                                        <i class="bi bi-check-circle-fill text-success"></i>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>

                                                {{-- Hidden input for selected bank --}}
                                                <input type="hidden" name="bank" id="selectedBank"
                                                    value="{{ $filter['bank'] }}">

                                                {{-- Footer --}}
                                                <div class="card-footer bg-light text-center border-top">
                                                    <div class="d-flex justify-content-between">
                                                        <a href="{{ route('transaction.bankTransactions') }}"
                                                            class="btn btn-outline-secondary btn-sm">
                                                            <i class="ti ti-refresh"></i> Reset
                                                        </a>
                                                        <a type="button" class="btn btn-outline-success btn-sm"
                                                            href="{{ route('bank-account.index') }}">
                                                            <i class="bi bi-bank"></i> Try new banking
                                                        </a>
                                                        </button>
                                                    </div>
                                                </div>

                                                {{ Form::close() }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-6 mb-2">
                                <div class="float-end"> <a href="{{ route('transaction.export') }}"
                                        data-bs-toggle="tooltip" title="{{ __('Export') }}"
                                        class="btn btn-sm btn-primary">
                                        <i class="ti ti-file-export"></i>
                                    </a>

                                    <a href="#" class="btn btn-sm btn-primary"
                                        onclick="saveAsPDF()"data-bs-toggle="tooltip" title="{{ __('Download') }}"
                                        data-original-title="{{ __('Download') }}">
                                        <span class="btn-inner--icon"><i class="ti ti-download"></i></span>
                                    </a>
                                </div>
                            </div>

                            @forelse($items as $acc)
                                @php $isActive = (string)request('account') === (string)$acc->holder_name; @endphp
                                <div class="col-xl-3 col-lg-4 col-md-6">
                                    <div class="card p-4 mb-4 subacct-card js-subacct-card {{ $isActive ? 'is-active' : '' }}"
                                        data-account-id="{{ $acc->holder_name }}">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1">{{ $acc->holder_name ?: __('(Unnamed Account)') }}</h6>
                                                <div class="text-xs text-muted">{{ $bankName }}
                                                    ({{ $acc->account_count }} accounts)
                                                </div>
                                            </div>
                                            <span class="menu-icon"><i class="bi bi-bank"></i></span>
                                        </div>

                                        <div class="mt-3">
                                            <div class="text-uppercase text-xxs text-muted">{{ __('Total in Period') }}
                                            </div>
                                            <div class="h5 mb-2">{{ \Auth::user()->priceFormat($acc->total) }}</div>

                                            <div class="text-uppercase text-xxs text-muted">{{ __('Transactions') }}</div>
                                            <div class="h6 mb-0">{{ $acc->txn_count }}</div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="alert alert-info mb-0">{{ __('No accounts found under this bank.') }}
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    @endforeach
                @else
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-secondary mb-4">{{ __('Select a bank to view its accounts.') }}</div>
                        </div>
                    </div>
                @endif


            </div>

            <div class="d-flex gap-2 mb-2">
                {{-- All Dates Dropdown --}}
                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle" type="button" id="dateFilterDropdown"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="ti ti-calendar"></i> All Dates
                    </button>
                    <div class="dropdown-menu p-3" style="min-width: 350px;">
                        <div class="card shadow-none border-0">
                            <div class="card-body p-0">
                                {{ Form::open(['route' => ['transaction.bankTransactions'], 'method' => 'get', 'id' => 'dateFilterForm']) }}
                                <div class="row">

                                    {{-- Start Month --}}
                                    <div class="col-12 mb-3">
                                        {{ Form::label('start_month', __('Start Month'), ['class' => 'form-label']) }}
                                        {{ Form::month('start_month', request('start_month', date('Y-m', strtotime('-5 month'))), ['class' => 'form-control']) }}
                                    </div>

                                    {{-- End Month --}}
                                    <div class="col-12 mb-3">
                                        {{ Form::label('end_month', __('End Month'), ['class' => 'form-label']) }}
                                        {{ Form::month('end_month', request('end_month', date('Y-m')), ['class' => 'form-control']) }}
                                    </div>

                                    {{-- Bank --}}
                                    <div class="col-12 mb-3">
                                        {{ Form::label('bank', __('Bank Accounts'), ['class' => 'form-label']) }}
                                        {{ Form::select('bank', $banks, request('bank', ''), ['class' => 'form-control select']) }}
                                    </div>

                                    {{-- Buttons --}}
                                    <div class="col-12 d-flex justify-content-between">
                                        <a href="{{ route('transaction.bankTransactions') }}"
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

                {{-- Category Dropdown --}}
                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle" type="button" id="categoryFilterDropdown"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="ti ti-filter"></i> All Categories
                    </button>
                    <div class="dropdown-menu p-3" style="min-width: 350px;">
                        <div class="card shadow-none border-0">
                            <div class="card-body p-0">
                                {{ Form::open(['route' => ['transaction.bankTransactions'], 'method' => 'get', 'id' => 'categoryFilterForm']) }}
                                <div class="row">

                                    {{-- Category --}}
                                    <div class="col-12 mb-3">
                                        {{ Form::label('category', __('Category'), ['class' => 'form-label']) }}
                                        {{ Form::select('category', $category, request('category', ''), ['class' => 'form-control select']) }}
                                    </div>

                                    {{-- Buttons --}}
                                    <div class="col-12 d-flex justify-content-between">
                                        <a href="{{ route('transaction.bankTransactions') }}"
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
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body table-border-style">
                            <div class="d-flex newOtherFilters pb-2 gap-2">



                                {{-- Search Bar --}}
                                {{-- <div class="search-bar ms-3">
                                    <form action="{{ route('transaction.bankTransactions') }}" method="get">
                                        <div class="input-group">
                                            <input type="text" name="search" class="form-control" 
                                                placeholder="{{ __('Search by description, check number, or amount') }}"
                                                value="{{ request('search') }}">
                                            <button type="submit" class="btn btn-outline-secondary">
                                                <i class="ti ti-search"></i>
                                            </button>
                                        </div>
                                    </form>
                                  </div> --}}



                            </div>
                            <div class="table-responsive">
                                <table class="table datatable">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Date') }}</th>
                                            <th>{{ __('Account') }}</th>
                                            <th>{{ __('Type') }}</th>
                                            <th>{{ __('Category') }}</th>
                                            <th>{{ __('Description') }}</th>
                                            <th>{{ __('Amount') }}</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($transactions as $transaction)
                                            <tr>
                                                <td>{{ \Auth::user()->dateFormat($transaction->date) }}</td>
                                                <td>
                                                    @if (!empty($transaction->bankAccount) && $transaction->bankAccount->holder_name == 'Cash')
                                                        {{ $transaction->bankAccount->holder_name }}
                                                    @else
                                                        {{ !empty($transaction->bankAccount) ? $transaction->bankAccount->bank_name . ' ' . $transaction->bankAccount->holder_name : '-' }}
                                                    @endif
                                                </td>
                                                <td>{{ $transaction->type }}</td>
                                                <td>{{ $transaction->category }}</td>
                                                <td>{{ !empty($transaction->description) ? $transaction->description : '-' }}
                                                </td>
                                                <td>{{ \Auth::user()->priceFormat($transaction->amount) }}</td>
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
    </div>
    <script>
        function selectBank(bankName) {
            document.getElementById('selectedBank').value = bankName;
            document.getElementById('bankTransaction').submit();
        }

        function closeDropdown() {
            const dropdown = bootstrap.Dropdown.getInstance(document.getElementById('bankFilterDropdown'));
            if (dropdown) {
                dropdown.hide();
            }
        }

        // Update button text when bank is selected
        document.addEventListener('DOMContentLoaded', function() {
            const selectedBank = '{{ $filter['bank'] }}';
            if (selectedBank) {
                const buttonText = document.querySelector('#bankFilterDropdown span');
                buttonText.textContent = selectedBank;
            }
        });
    </script>

    <style>
        .account-item:hover {
            background-color: #f8f9fa !important;
        }

        .account-item.selected {
            background-color: #e3f2fd !important;
            border-left: 4px solid #2196f3;
        }

        .dropdown-menu {
            border-radius: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .account-icon {
            flex-shrink: 0;
        }
    </style>
