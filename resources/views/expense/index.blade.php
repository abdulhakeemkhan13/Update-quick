@extends('layouts.admin')

@section('page-title')
    {{ __('Expenses') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Expenses') }}</li>
@endsection

@push('css-page')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <style>
        /* =========================================
           QBO Expense Transactions - Exact Design
           ========================================= */

        /* Page container */
        .qbo-expense-container {
            background: #fff;
            min-height: 100vh;
            padding: 20px 24px;
        }

        /* Page Header */
        .qbo-page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .qbo-page-title {
            font-size: 28px;
            font-weight: 700;
            color: #393A3D;
            margin: 0;
        }

        .qbo-header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Buttons */
        .qbo-btn-tertiary {
            background: none;
            border: none;
            color: #393a3d;
            font-size: 14px;
            padding: 8px 16px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 4px;
        }

        .qbo-btn-tertiary:hover {
            background: #f4f5f7;
        }

        .qbo-btn-secondary {
            background: #fff;
            border: 1px solid #8d9096;
            color: #393a3d;
            font-size: 14px;
            padding: 8px 16px;
            cursor: pointer;
            border-radius: 4px;
        }

        .qbo-btn-secondary:hover {
            background: #f4f5f7;
        }

        .qbo-btn-primary {
            background: #2ca01c;
            border: none;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            padding: 8px 20px;
            cursor: pointer;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .qbo-btn-primary:hover {
            background: #1e8012;
            color: #fff;
        }

        .qbo-btn-primary svg {
            width: 16px;
            height: 16px;
        }

        /* Split Button */
        .qbo-split-btn {
            display: inline-flex;
        }

        .qbo-split-btn .qbo-btn-secondary:first-child {
            border-radius: 4px 0 0 4px;
            border-right: none;
        }

        .qbo-split-btn .qbo-btn-secondary:last-child {
            border-radius: 0 4px 4px 0;
            padding: 8px 10px;
        }

        /* Filter Bar */
        .qbo-filter-bar {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .qbo-filter-dropdown {
            position: relative;
        }

        .qbo-filter-btn {
            background: #fff;
            border: 1px solid #c1c4c8;
            border-radius: 4px;
            padding: 8px 36px 8px 12px;
            font-size: 14px;
            color: #393a3d;
            cursor: pointer;
            min-width: 180px;
            text-align: left;
            position: relative;
        }

        .qbo-filter-btn::after {
            content: '';
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: solid #6b6c72;
            border-width: 0 2px 2px 0;
            padding: 3px;
            transform: translateY(-70%) rotate(45deg);
        }

        .qbo-filter-dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: #fff;
            border: 1px solid #e0e3e5;
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            min-width: 240px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }

        .qbo-filter-dropdown.show .qbo-filter-dropdown-menu {
            display: block;
        }

        .qbo-filter-dropdown-item {
            padding: 10px 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #393a3d;
        }

        .qbo-filter-dropdown-item:hover {
            background: #f4f5f7;
        }

        .qbo-filter-dropdown-item.active {
            font-weight: 600;
        }

        .qbo-filter-dropdown-item.active::before {
            content: '✓';
            color: #2ca01c;
            font-weight: bold;
        }

        /* Filter Icon Button */
        .qbo-filter-icon-btn {
            background: none;
            border: none;
            color: #393a3d;
            font-size: 14px;
            padding: 8px 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .qbo-filter-icon-btn:hover {
            background: #f4f5f7;
            border-radius: 4px;
        }

        /* Date Chip */
        .qbo-date-chip {
            background: #e4f7e1;
            border: 1px solid #2ca01c;
            border-radius: 16px;
            padding: 6px 14px;
            font-size: 13px;
            color: #1e7817;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .qbo-date-chip-label {
            font-weight: 600;
        }

        /* Table Actions (right side) */
        .qbo-table-header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: auto;
        }

        .qbo-icon-btn {
            background: none;
            border: none;
            color: #6b6c72;
            padding: 8px;
            cursor: pointer;
            border-radius: 4px;
        }

        .qbo-icon-btn:hover {
            background: #f4f5f7;
            color: #393a3d;
        }

        /* =========================================
           Table - QBO Style
           ========================================= */
        .qbo-table-wrapper {
            background: #fff;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* Custom scrollbar */
        .qbo-table-wrapper::-webkit-scrollbar {
            width: 12px;
            height: 12px;
        }

        .qbo-table-wrapper::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 6px;
        }

        .qbo-table-wrapper::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 6px;
        }

        .qbo-table-wrapper::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }

        /* Table styling */
        #expenseTable {
            width: 100% !important;
            min-width: 1000px;
            margin: 0 !important;
            border-collapse: collapse;
        }

        #expenseTable thead th {
            background: #fff;
            font-weight: 400;
            font-size: 12px;
            color: #6b6c72;
            text-transform: uppercase;
            border-top: none;
            border-bottom: 1px solid #e0e3e5;
            padding: 12px 16px;
            text-align: left;
            position: sticky;
            top: 60px;
            z-index: 100;
        }

        #expenseTable thead th.text-end {
            text-align: right;
        }

        #expenseTable tbody td {
            padding: 14px 16px;
            border-bottom: 1px solid #e0e3e5;
            font-size: 14px;
            color: #393a3d;
            vertical-align: middle;
            white-space: nowrap;
        }

        #expenseTable tbody tr:hover {
            background: #f8f9fa;
        }

        /* Category dropdown in table */
        .qbo-category-select {
            background: #fff;
            border: 1px solid #e0e3e5;
            border-radius: 4px;
            padding: 6px 28px 6px 10px;
            font-size: 13px;
            color: #393a3d;
            min-width: 140px;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24'%3E%3Cpath fill='%236b6c72' d='M12.014 16.018a1 1 0 0 1-.708-.294L5.314 9.715A1.001 1.001 0 0 1 6.73 8.3l5.286 5.3 5.3-5.285a1 1 0 0 1 1.413 1.416l-6.009 5.995a1 1 0 0 1-.706.292'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 16px;
        }

        /* Action button */
        .qbo-action-btn {
            background: none;
            border: none;
            color: #0077c5;
            font-size: 13px;
            padding: 6px 12px;
            cursor: pointer;
        }

        .qbo-action-btn:hover {
            text-decoration: underline;
        }

        /* Total row */
        .qbo-total-row td {
            font-weight: 600 !important;
            background: #fff;
            border-top: 1px solid #e0e3e5;
            padding: 16px !important;
        }

        /* =========================================
           Footer/Pagination
           ========================================= */
        .qbo-table-footer {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 16px 0;
            gap: 16px;
            font-size: 14px;
            color: #6b6c72;
        }

        .qbo-pagination {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .qbo-pagination-btn {
            background: none;
            border: none;
            color: #0077c5;
            padding: 6px 12px;
            cursor: pointer;
            font-size: 14px;
        }

        .qbo-pagination-btn:hover {
            text-decoration: underline;
        }

        .qbo-pagination-btn:disabled {
            color: #c1c4c8;
            cursor: not-allowed;
        }

        .qbo-pagination-btn:disabled:hover {
            text-decoration: none;
        }

        .qbo-pagination-info {
            color: #393a3d;
        }

        /* New Transaction Dropdown */
        .qbo-new-txn-dropdown {
            position: relative;
            display: inline-block;
        }

        .qbo-new-txn-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: #fff;
            border: 1px solid #e0e3e5;
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            min-width: 200px;
            z-index: 1000;
            display: none;
        }

        .qbo-new-txn-dropdown.show .qbo-new-txn-menu {
            display: block;
        }

        .qbo-new-txn-menu a {
            display: block;
            padding: 10px 16px;
            color: #393a3d;
            text-decoration: none;
            font-size: 14px;
        }

        .qbo-new-txn-menu a:hover {
            background: #f4f5f7;
        }

        /* Hide DataTables default styling */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            display: none;
        }

        /* Header action icons in column */
        .qbo-header-icons {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .qbo-header-icon-btn {
            background: none;
            border: none;
            color: #6b6c72;
            padding: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .qbo-header-icon-btn:hover {
            color: #393a3d;
        }

        /* Filter Modal */
        .qbo-filter-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            display: none;
            align-items: flex-start;
            justify-content: center;
            padding-top: 100px;
        }

        .qbo-filter-modal.show {
            display: flex;
        }

        .qbo-filter-modal-content {
            background: #fff;
            border-radius: 8px;
            padding: 24px;
            min-width: 400px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        }

        .qbo-filter-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .qbo-filter-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #6b6c72;
        }

        .qbo-filter-group {
            margin-bottom: 16px;
        }

        .qbo-filter-label {
            font-size: 13px;
            color: #6b6c72;
            margin-bottom: 6px;
            display: block;
        }

        .qbo-filter-select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #c1c4c8;
            border-radius: 4px;
            font-size: 14px;
        }

        .qbo-filter-row {
            display: flex;
            gap: 12px;
        }

        .qbo-filter-row .qbo-filter-group {
            flex: 1;
        }

        .qbo-filter-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .qbo-filter-reset {
            background: #fff;
            border: 1px solid #c1c4c8;
            padding: 8px 20px;
            border-radius: 4px;
            cursor: pointer;
        }

        .qbo-filter-apply {
            background: #2ca01c;
            border: none;
            color: #fff;
            padding: 8px 24px;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
@endpush

@section('content')
{{-- MY APPS Sidebar (Fixed Position) --}}
@include('partials.admin.allApps-subMenu-Sidebar', [
    'activeSection' => 'expenses',
    'activeItem' => 'expense_transactions'
])

<div class="qbo-expense-container">
    {{-- Page Header --}}
    <div class="qbo-page-header">
        <h1 class="qbo-page-title">{{ __('Expenses') }}</h1>
        <div class="qbo-header-actions">
            {{-- Give feedback --}}
            <button class="qbo-btn-tertiary">
                <i class="ti ti-message-circle"></i>
                {{ __('Give feedback') }}
            </button>

            {{-- Purchase notifications --}}
            <button class="qbo-btn-secondary">
                {{ __('Purchase notifications') }}
            </button>

            {{-- Print Checks Split Button --}}
            <div class="qbo-split-btn">
                <button class="qbo-btn-secondary">{{ __('Print Checks') }}</button>
                <button class="qbo-btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="16" height="16">
                        <path fill="currentColor" d="M12.014 16.018a1 1 0 0 1-.708-.294L5.314 9.715A1.001 1.001 0 0 1 6.73 8.3l5.286 5.3 5.3-5.285a1 1 0 0 1 1.413 1.416l-6.009 5.995a1 1 0 0 1-.706.292"></path>
                    </svg>
                </button>
            </div>

            {{-- New Transaction Dropdown --}}
            <div class="qbo-new-txn-dropdown">
                <button class="qbo-btn-primary" onclick="toggleNewTxnDropdown()">
                    {{ __('New transaction') }}
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="16" height="16">
                        <path fill="currentColor" d="M12.014 16.018a1 1 0 0 1-.708-.294L5.314 9.715A1.001 1.001 0 0 1 6.73 8.3l5.286 5.3 5.3-5.285a1 1 0 0 1 1.413 1.416l-6.009 5.995a1 1 0 0 1-.706.292"></path>
                    </svg>
                </button>
                <div class="qbo-new-txn-menu">
                    <a href="{{ route('timeActivity.create') }}">{{ __('Time activity') }}</a>
                    <a href="{{ route('bill.create', 0) }}">{{ __('Bill') }}</a>
                    <a href="#" class="openChecksModal" data-url="{{ route('expense.create', 0) }}">{{ __('Expense') }}</a>
                    <a href="#" class="openChecksModal" data-url="{{ route('checks.create') }}">{{ __('Check') }}</a>
                    <a href="{{ url('purchase/create/0') }}">{{ __('Purchase order') }}</a>
                    <a href="#" class="openChecksModal" data-url="{{ route('vendor-credit.create') }}">{{ __('Vendor credit') }}</a>
                    <a href="{{ route('creditcreditcard.create', 0) }}">{{ __('Credit card credit') }}</a>
                    <a href="#" class="openChecksModal" data-url="{{ route('paydowncreditcard.create') }}">{{ __('Pay down credit card') }}</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="qbo-filter-bar">
        {{-- All Transactions Dropdown --}}
        <div class="qbo-filter-dropdown" id="txnTypeDropdown">
            <button class="qbo-filter-btn" onclick="toggleFilterDropdown('txnTypeDropdown')">
                <span id="selectedTxnType">{{ __('All transactions') }}</span>
            </button>
            <div class="qbo-filter-dropdown-menu">
                <div class="qbo-filter-dropdown-item active" data-value="all">{{ __('All transactions') }}</div>
                <div class="qbo-filter-dropdown-item" data-value="expense">{{ __('Expense') }}</div>
                <div class="qbo-filter-dropdown-item" data-value="bill">{{ __('Bill') }}</div>
                <div class="qbo-filter-dropdown-item" data-value="bill_payment">{{ __('Bill payment') }}</div>
                <div class="qbo-filter-dropdown-item" data-value="check">{{ __('Check') }}</div>
                <div class="qbo-filter-dropdown-item" data-value="purchase_order">{{ __('Purchase order') }}</div>
                <div class="qbo-filter-dropdown-item" data-value="recently_paid">{{ __('Recently paid') }}</div>
                <div class="qbo-filter-dropdown-item" data-value="vendor_credit">{{ __('Vendor credit') }}</div>
                <div class="qbo-filter-dropdown-item" data-value="cc_payment">{{ __('Credit card payment') }}</div>
            </div>
        </div>

        {{-- Filter Button --}}
        <button class="qbo-filter-icon-btn" onclick="openFilterModal()">
            <i class="ti ti-adjustments-horizontal"></i>
            {{ __('Filter') }}
        </button>

        {{-- Date Chip --}}
        <div class="qbo-date-chip">
            <span class="qbo-date-chip-label">{{ __('Dates:') }}</span>
            {{ __('Last 12 months') }}
        </div>

        {{-- Right side icons --}}
        <div class="qbo-table-header-actions">
            <button class="qbo-icon-btn" title="{{ __('Export to Excel') }}">
                <i class="ti ti-download"></i>
            </button>
            <button class="qbo-icon-btn" title="{{ __('Print') }}">
                <i class="ti ti-printer"></i>
            </button>
            <button class="qbo-icon-btn" title="{{ __('Settings') }}">
                <i class="ti ti-settings"></i>
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="qbo-table-wrapper">
        <table id="expenseTable" class="display" style="width:100%">
            <thead>
                <tr>
                    <th width="40"><input type="checkbox" class="form-check-input" id="selectAllHeader"></th>
                    <th>{{ __('DATE') }}</th>
                    <th>{{ __('TYPE') }}</th>
                    <th>{{ __('NO.') }}</th>
                    <th>{{ __('PAYEE') }}</th>
                    <th>{{ __('CLASS') }}</th>
                    <th>{{ __('LOCATION') }}</th>
                    <th>{{ __('STATUS') }}</th>
                    <th>{{ __('METHOD') }}</th>
                    <th>{{ __('SOURCE') }}</th>
                    <th>{{ __('CATEGORY') }}</th>
                    <th>{{ __('MEMO') }}</th>
                    <th>{{ __('DUE DATE') }}</th>
                    <th class="text-end">{{ __('BALANCE') }}</th>
                    <th class="text-end">{{ __('TOTAL') }}</th>
                    <th width="50">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="20" height="20">
                            <path fill="currentColor" d="M10 22a4 4 0 0 1-4-4V7.5a5.5 5.5 0 1 1 11 0V19a1 1 0 0 1-2 0V7.5a3.5 3.5 0 1 0-7 0V18a2 2 0 0 0 4 0V8.5a.5.5 0 0 0-1 0V17a1 1 0 0 1-2 0V8.5a2.5 2.5 0 1 1 5 0V18a4 4 0 0 1-4 4"></path>
                        </svg>
                    </th>
                    <th>{{ __('ACTION') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions ?? [] as $txn)
                <tr>
                    <td><input type="checkbox" class="form-check-input row-checkbox" value="{{ $txn['id'] }}" data-type="{{ $txn['type_key'] }}"></td>
                    <td>{{ \Auth::user()->dateFormat($txn['date']) }}</td>
                    <td>{{ $txn['type'] }}</td>
                    <td>{{ $txn['no'] }}</td>
                    <td>{{ $txn['payee'] }}</td>
                    <td>{{ $txn['class'] }}</td>
                    <td>{{ $txn['location'] }}</td>
                    <td>
                        @php
                            $status = strtolower($txn['status']);
                            $statusClass = 'default';
                            if (str_contains($status, 'overdue')) $statusClass = 'danger';
                            elseif ($status === 'paid' || $status === 'applied') $statusClass = 'success';
                            elseif ($status === 'partial') $statusClass = 'warning';
                            elseif ($status === 'open' || $status === 'unapplied') $statusClass = 'info';
                        @endphp
                        <span class="qbo-status-{{ $statusClass }}">{{ $txn['status'] }}</span>
                    </td>
                    <td>{{ $txn['method'] }}</td>
                    <td>{{ $txn['source'] }}</td>
                    <td>{{ $txn['category'] }}</td>
                    <td>{{ $txn['memo'] }}</td>
                    <td>{{ $txn['due_date'] }}</td>
                    <td class="text-end">{{ \Auth::user()->priceFormat($txn['balance']) }}</td>
                    <td class="text-end" data-amount="{{ $txn['total'] }}">
                        @if($txn['total'] < 0)
                            -{{ \Auth::user()->priceFormat(abs($txn['total'])) }}
                        @else
                            {{ \Auth::user()->priceFormat($txn['total']) }}
                        @endif
                    </td>
                    <td>{{ $txn['attachments'] }}</td>
                    <td>
                        <a href="{{ $txn['view_url'] }}" class="qbo-action-btn">{{ __('View/Edit') }}</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="17" class="text-center">{{ __('No transactions found.') }}</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="qbo-total-row">
                    <td></td>
                    <td>{{ __('Total') }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="text-end" id="totalAmount">{{ \Auth::user()->priceFormat($totalAmount ?? 0) }}</td>
                    <td></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Footer/Pagination --}}
    <div class="qbo-table-footer">
        <div class="qbo-pagination">
            <button class="qbo-pagination-btn" id="paginateFirst">{{ __('First') }}</button>
            <button class="qbo-pagination-btn" id="paginatePrev">{{ __('Previous') }}</button>
            <span class="qbo-pagination-info" id="paginateInfo">1-{{ count($transactions ?? []) }} of {{ count($transactions ?? []) }}</span>
            <button class="qbo-pagination-btn" id="paginateNext">{{ __('Next') }}</button>
            <button class="qbo-pagination-btn" id="paginateLast">{{ __('Last') }}</button>
        </div>
    </div>
</div>

{{-- Filter Modal --}}
<div class="qbo-filter-modal" id="filterModal">
    <div class="qbo-filter-modal-content">
        <div class="qbo-filter-modal-header">
            <h3>{{ __('Filter') }}</h3>
            <button class="qbo-filter-modal-close" onclick="closeFilterModal()">&times;</button>
        </div>
        
        <form method="GET" action="{{ route('expense.index') }}">
            <div class="qbo-filter-group">
                <label class="qbo-filter-label">{{ __('Status') }}</label>
                <select class="qbo-filter-select" name="status">
                    <option value="">{{ __('All statuses') }}</option>
                    <option value="open">{{ __('Open') }}</option>
                    <option value="paid">{{ __('Paid') }}</option>
                </select>
            </div>

            <div class="qbo-filter-group">
                <label class="qbo-filter-label">{{ __('Delivery method') }}</label>
                <select class="qbo-filter-select" name="delivery">
                    <option value="">{{ __('Any') }}</option>
                </select>
            </div>

            <div class="qbo-filter-group">
                <label class="qbo-filter-label">{{ __('Date') }}</label>
                <select class="qbo-filter-select" name="date_range">
                    <option value="last_12_months">{{ __('Last 12 months') }}</option>
                    <option value="this_month">{{ __('This month') }}</option>
                    <option value="last_month">{{ __('Last month') }}</option>
                    <option value="custom">{{ __('Custom') }}</option>
                </select>
            </div>

            <div class="qbo-filter-row">
                <div class="qbo-filter-group">
                    <label class="qbo-filter-label">{{ __('From') }}</label>
                    <input type="date" class="qbo-filter-select" name="date_from">
                </div>
                <div class="qbo-filter-group">
                    <label class="qbo-filter-label">{{ __('To') }}</label>
                    <input type="date" class="qbo-filter-select" name="date_to">
                </div>
            </div>

            <div class="qbo-filter-group">
                <label class="qbo-filter-label">{{ __('Payee') }}</label>
                <select class="qbo-filter-select" name="payee">
                    <option value="">{{ __('All') }}</option>
                </select>
            </div>

            <div class="qbo-filter-group">
                <label class="qbo-filter-label">{{ __('Category') }}</label>
                <select class="qbo-filter-select" name="category">
                    <option value="">{{ __('All') }}</option>
                    @foreach($category ?? [] as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="qbo-filter-actions">
                <button type="button" class="qbo-filter-reset" onclick="closeFilterModal()">{{ __('Reset') }}</button>
                <button type="submit" class="qbo-filter-apply">{{ __('Apply') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal for Create Forms --}}
<div class="modal fade" id="ajaxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-fullscreen">
        <div class="modal-content">
        </div>
    </div>
</div>
@endsection

@push('script-page')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script>
    var expenseTable;
    var currentTxnType = '{{ $type ?? "all" }}';

    $(document).ready(function() {
        // Initialize DataTable - client-side (no AJAX)
        expenseTable = $('#expenseTable').DataTable({
            processing: false,
            serverSide: false,
            order: [[1, 'desc']],
            pageLength: 50,
            lengthMenu: [[50, 75, 100, 150, 300], [50, 75, 100, 150, 300]],
            dom: 'rt', // Hide default pagination, we use custom
            language: {
                emptyTable: "{{ __('No transactions found.') }}",
            },
            drawCallback: function(settings) {
                var api = this.api();
                updatePagination(api);
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        });

        // Update pagination info
        function updatePagination(api) {
            if (!api) return;
            var pageInfo = api.page.info();
            var start = pageInfo.recordsTotal > 0 ? pageInfo.start + 1 : 0;
            var end = pageInfo.end;
            var total = pageInfo.recordsTotal;

            $('#paginateInfo').text(start + '-' + end + ' of ' + total);

            // Enable/disable buttons
            $('#paginateFirst, #paginatePrev').prop('disabled', pageInfo.page === 0);
            $('#paginateNext, #paginateLast').prop('disabled', pageInfo.page >= pageInfo.pages - 1);
        }

        // Custom pagination buttons
        $('#paginateFirst').on('click', function() {
            expenseTable.page('first').draw('page');
        });
        $('#paginatePrev').on('click', function() {
            expenseTable.page('previous').draw('page');
        });
        $('#paginateNext').on('click', function() {
            expenseTable.page('next').draw('page');
        });
        $('#paginateLast').on('click', function() {
            expenseTable.page('last').draw('page');
        });

        // Select all checkbox
        $('#selectAllHeader').on('change', function() {
            var checked = this.checked;
            $('.row-checkbox').each(function() {
                this.checked = checked;
            });
        });
    });

    // Toggle New Transaction Dropdown
    function toggleNewTxnDropdown() {
        document.querySelector('.qbo-new-txn-dropdown').classList.toggle('show');
    }

    // Toggle Filter Dropdown
    function toggleFilterDropdown(id) {
        document.getElementById(id).classList.toggle('show');
    }

    // Filter dropdown item selection - reload page with new filter
    document.querySelectorAll('.qbo-filter-dropdown-item').forEach(item => {
        item.addEventListener('click', function() {
            const dropdown = this.closest('.qbo-filter-dropdown');
            dropdown.querySelectorAll('.qbo-filter-dropdown-item').forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            dropdown.querySelector('.qbo-filter-btn span').textContent = this.textContent;
            dropdown.classList.remove('show');

            // Reload page with new filter
            var txnType = this.dataset.value;
            var currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('txn_type', txnType);
            window.location.href = currentUrl.toString();
        });
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.qbo-new-txn-dropdown')) {
            document.querySelector('.qbo-new-txn-dropdown')?.classList.remove('show');
        }
        if (!e.target.closest('.qbo-filter-dropdown')) {
            document.querySelectorAll('.qbo-filter-dropdown').forEach(d => d.classList.remove('show'));
        }
    });

    // Filter Modal
    function openFilterModal() {
        document.getElementById('filterModal').classList.add('show');
    }

    function closeFilterModal() {
        document.getElementById('filterModal').classList.remove('show');
    }

    // Select All checkbox
    document.getElementById('selectAll')?.addEventListener('change', function() {
        document.querySelectorAll('.row-checkbox').forEach(cb => {
            cb.checked = this.checked;
        });
    });

    // AJAX Modal for create forms
    $(document).on('click', '.openChecksModal', function(e) {
        e.preventDefault();
        var url = $(this).data('url');
        $('#ajaxModal').modal('show');
        $.ajax({
            url: url,
            type: 'GET',
            success: function(res) {
                $('#ajaxModal .modal-content').html(res);
            },
            error: function() {
                alert('Something went wrong!');
            }
        });
    });

    // Coming Soon toast
    function showComingSoon() {
        show_toastr('info', 'Coming soon!', 'info');
    }
</script>
@endpush
