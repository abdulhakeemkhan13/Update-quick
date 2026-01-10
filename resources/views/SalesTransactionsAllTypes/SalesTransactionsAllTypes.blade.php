@extends('layouts.admin')

@section('page-title')
    {{ __('Sales transactions') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Sales transactions') }}</li>
@endsection

@push('css-page')
    <link rel="stylesheet" href="{{ asset('css/datatable/buttons.dataTables.min.css') }}">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <style>
        /* =========================================
               QBO Sales Transactions - Exact Design
               ========================================= */

        /* Page Header */
        .qbo-page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .qbo-page-title {
            font-size: 24px;
            font-weight: 700;
            color: #393A3D;
            margin: 0;
        }

        .qbo-feedback-link {
            color: #393A3D;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .qbo-feedback-link:hover {
            text-decoration: none;
        }

        /* Collapse Button */
        .qbo-collapse-btn {
            background: none;
            border: none !important;
            color: #6b6c72 !important;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .qbo-collapse-btn:hover {
            background: #f4f5f7 !important;
            color: #393a3d !important;
        }

        .qbo-collapse-btn i {
            transition: transform 0.2s ease;
        }

        .qbo-collapse-btn.collapsed i {
            transform: rotate(180deg);
        }

        /* =========================================
               Money Bar - QBO Exact Style
               ========================================= */
        .qbo-money-bar {
            background: #fff;
            margin-bottom: 16px;
            transition: all 0.3s ease;
        }

        .qbo-money-bar.collapsed {
            display: none;
        }

        /* Money Bar Group - contains all sections */
        .moneybar-group {
            display: flex;
            gap: 2px;
            padding: 16px 20px 8px 20px;
        }

        /* Each section: text + bar vertically stacked */
        .moneybar-section {
            flex: 1;
            width: 20%;
            display: flex;
            flex-direction: column;
        }

        .moneybar-section .text {
            margin-bottom: 8px;
        }

        .moneybar-section .text .amount {
            font-size: 20px;
            font-weight: 700;
            color: #393a3d;
            display: block;
        }

        .moneybar-section .text .label {
            font-size: 13px;
            color: #6b6c72;
        }

        /* The colored bar */
        .moneybar-section .bar {
            height: 25px;
            border-radius: 4px;
            cursor: pointer;
            transition: transform 0.1s linear;
            transform-origin: bottom;
        }

        .moneybar-section .bar:hover,
        .moneybar-section .bar:focus {
            transform: scaleY(1.3);
            box-shadow: inset 0 -5px 0 rgba(0, 0, 0, 0.15);
        }

        /* Bar colors */
        .moneybar-section.estimate .bar {
            background: #21ABF6;
        }

        .moneybar-section.unbilled .bar {
            background: #9457FA;
        }

        .moneybar-section.overdue .bar {
            background: #FF8000;
        }

        .moneybar-section.openInvoices .bar {
            background: #BABEC5;
        }

        .moneybar-section.recentlyPaid .bar {
            background: #2CA01C;
        }

        /* =========================================
               Info Alert Box
               ========================================= */
        .qbo-info-alert {
            background: linear-gradient(white, white) padding-box, conic-gradient(from 180deg at 50% 50%, #009eac 0deg, #00d0e0 54deg, #236cff 126deg, #00d0e0 180deg, #c5ef71 234deg, #00a63b 306deg, #009eac 360deg) border-box;
            border: 2px solid transparent;
            border-radius: 4px;
            padding: 16px 20px;
            margin-bottom: 16px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .qbo-info-alert-icon {
            color: #0077c5;
            font-size: 20px;
            flex-shrink: 0;
        }

        .qbo-info-alert-content {
            flex: 1;
        }

        .qbo-info-alert-title {
            font-weight: 600;
            color: #393a3d;
            margin-bottom: 4px;
        }

        .qbo-info-alert-text {
            font-size: 14px;
            color: #393a3d;
        }

        .qbo-info-alert-close {
            background: none;
            border: none;
            color: #6b6c72;
            cursor: pointer;
            padding: 0;
            font-size: 18px;
        }

        .qbo-info-alert-btn {
            background: #fff;
            border: 1px solid #d4d7dc;
            border-radius: 4px;
            padding: 8px 16px;
            font-size: 14px;
            color: #393a3d;
            cursor: pointer;
            white-space: nowrap;
        }

        .qbo-info-alert-btn:hover {
            background: #f4f5f7;
        }

        /* =========================================
               Filter Bar - QBO Exact Layout
               ========================================= */
        .qbo-filter-bar {
            background: #fff;
            padding: 16px 20px;
            margin-bottom: 0;
        }

        /* Primary filters row */
        .qbo-filters-primary {
            display: flex;
            align-items: flex-end;
            gap: 16px;
            margin-bottom: 12px;
        }

        .qbo-filter-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .qbo-filter-group.customer-group {
            flex: 1;
            max-width: 300px;
        }

        .qbo-filter-label {
            font-size: 11px;
            font-weight: 500;
            color: #6b6c72;
            text-transform: capitalize;
        }

        .qbo-filter-select {
            background: #fff;
            border: 1px solid #d4d7dc;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 14px;
            color: #393a3d;
            min-width: 140px;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b6c72' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            padding-right: 30px;
        }

        .qbo-filter-select:hover {
            border-color: #9ba0a8;
        }

        .qbo-filter-select:focus {
            border-color: #0077c5;
            outline: none;
        }

        /* Batch actions - teal colored */
        .qbo-batch-btn {
            background: #fff;
            border: 1.5px solid #00892E;
            color: #00892E;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .qbo-batch-btn:hover {
            background: #f0fffe;
        }

        /* New transaction button - QBO style */
        .qbo-new-btn {
            background: #2ca01c !important;
            border: none !important;
            color: #fff !important;
            border-radius: 4px;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-left: auto;
            transition: background-color 0.2s ease;
        }

        .qbo-new-btn:hover {
            background: #238c16 !important;
        }

        .qbo-new-btn::after {
            display: none;
        }

        .qbo-new-btn .dropdown-arrow {
            margin-left: 4px;
        }

        /* New transaction dropdown menu */
        .qbo-new-dropdown {
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
            border: 1px solid #e0e3e5;
            padding: 8px 0;
            min-width: 180px;
        }

        .qbo-new-dropdown .dropdown-item {
            padding: 10px 16px;
            font-size: 14px;
            color: #393a3d;
            transition: background-color 0.15s ease;
        }

        .qbo-new-dropdown .dropdown-item:hover {
            background-color: #f4f5f7;
        }

        .qbo-new-dropdown .dropdown-item.text-muted {
            color: #9ba0a8;
        }

        /* Secondary filters */
        .qbo-filters-secondary {
            display: flex;
            gap: 8px;
            padding-top: 8px;
            margin-left: 130px;
            /* Align with Type filter position */
        }

        .qbo-secondary-filter {
            background: none;
            border: 1px solid transparent;
            color: #393a3d;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border-radius: 4px;
        }

        .qbo-secondary-filter:hover {
            background: #f4f5f7;
        }

        .qbo-secondary-filter.active {
            border-color: #0077c5;
            color: #0077c5;
        }

        .qbo-secondary-dropdown {
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
            border: 1px solid #e0e3e5;
            padding: 8px 0;
            min-width: 180px;
        }

        .qbo-secondary-dropdown .dropdown-item {
            padding: 10px 16px;
            font-size: 14px;
            color: #393a3d;
        }

        .qbo-secondary-dropdown .dropdown-item:hover {
            background-color: #f4f5f7;
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

        /* Table action icons */
        .qbo-table-actions {
            display: flex;
            justify-content: flex-end;
            padding: 8px 16px;
            gap: 8px;
            border-bottom: 1px solid #e0e3e5;
        }

        .qbo-table-action-icon {
            background: none;
            border: none;
            color: #6b6c72;
            padding: 6px;
            cursor: pointer;
        }

        .qbo-table-action-icon:hover {
            color: #393a3d;
        }

        /* Table styling */
        #salesTransactionsTable {
            width: 100% !important;
            min-width: 1200px;
            /* Ensures horizontal scrolling on smaller screens */
            margin: 0 !important;
            border-collapse: collapse;
        }

        #salesTransactionsTable thead th {
            background: #fff;
            font-weight: 400;
            font-size: 12px;
            color: #6b6c72;
            border-top: none;
            border-bottom: 1px solid #e0e3e5;
            padding: 12px 16px;
            text-align: left;
            position: sticky;
            top: 60px;
            /* Sticks below main header */
            z-index: 100;
        }

        #salesTransactionsTable thead th.text-end {
            text-align: right;
        }

        #salesTransactionsTable tbody td {
            padding: 12px 16px;
            font-size: 14px;
            color: #393a3d;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
            white-space: nowrap;
        }

        #salesTransactionsTable tbody tr:hover {
            background: #f8f9fa;
        }

        /* Total row in tfoot */
        .qbo-total-row {
            background: #fff !important;
            border-top: 1px solid #e0e3e5;
        }

        .qbo-total-row td {
            padding: 16px !important;
            font-weight: 400;
        }

        /* Footer/Pagination styling */
        .dataTables_wrapper .dataTables_paginate {
            text-align: center;
            padding: 16px 0;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 8px 12px;
            margin: 0 4px;
            border: none;
            background: none;
            color: #6b6c72;
            cursor: pointer;
            font-size: 14px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            color: #0077c5;
            background: none !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            color: #0077c5;
            font-weight: 600;
            background: none !important;
        }

        .dataTables_wrapper .dataTables_info {
            text-align: center;
            padding: 8px 0;
            color: #6b6c72;
            font-size: 14px;
        }

        /* Table header action icons */
        .qbo-header-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .qbo-header-action-btn {
            background: none;
            border: none;
            color: #6b6c72;
            padding: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .qbo-header-action-btn:hover {
            color: #393a3d;
        }

        .qbo-total-row:hover {
            background: #f8f9fa !important;
        }

        /* Status styling */
        .qbo-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
        }

        .qbo-status.overdue {
            color: #d93025;
        }

        .qbo-status.open {
            color: #fd7e14;
        }

        .qbo-status.paid {
            color: #2ca01c;
        }

        .qbo-status.draft {
            color: #6c757d;
        }

        .qbo-status.closed {
            color: #2ca01c;
        }

        /* Action links */
        .qbo-action-link {
            color: #0077c5;
            text-decoration: none;
            font-size: 13px;
        }

        .qbo-action-link:hover {
            text-decoration: underline;
        }

        /* Hide DataTable default controls */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            display: none !important;
        }

        .dataTables_wrapper .dataTables_info {
            font-size: 13px;
            color: #6b6c72;
            padding: 12px 16px;
        }

        .dataTables_wrapper .dataTables_paginate {
            padding: 12px 16px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 4px 10px;
            margin: 0 2px;
            border: none !important;
            background: none !important;
            color: #0077c5 !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            color: #393a3d !important;
            font-weight: 600;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            color: #9ba0a8 !important;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .qbo-metrics-row {
                flex-wrap: wrap;
            }

            .qbo-metric {
                flex: 0 0 50%;
                margin-bottom: 12px;
            }

            .qbo-filters-primary {
                flex-wrap: wrap;
            }

            .qbo-filter-group.customer-group {
                max-width: 100%;
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    {{-- MY APPS Sidebar --}}
    @include('partials.admin.allApps-subMenu-Sidebar', [
        'activeSection' => 'sales',
        'activeItem' => 'sales_transactions',
    ])

    {{-- Fullscreen Modal for Delayed Credit --}}
    <div class="modal fade" id="delayedCreditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-fullscreen">
            <div class="modal-content"></div>
        </div>
    </div>

    {{-- Fullscreen Modal for Delayed Charge --}}
    <div class="modal fade" id="delayedChargeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-fullscreen">
            <div class="modal-content"></div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            {{-- Page Header --}}
            <div class="qbo-page-header">
                <h1 class="qbo-page-title">{{ __('Sales transactions') }}</h1>
                <div class="d-flex align-items-center gap-3">
                    <a href="#" class="qbo-feedback-link">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" color="currentColor"
                            width="24px" height="24px" focusable="false" aria-hidden="true">
                            <path fill="currentColor"
                                d="M14.35 2a1 1 0 0 1 0 2H6.49a2.54 2.54 0 0 0-2.57 2.5v7A2.54 2.54 0 0 0 6.49 16h1.43a1 1 0 0 1 1 1v1.74l2.727-2.48c.184-.167.424-.26.673-.26h5.03a2.54 2.54 0 0 0 2.57-2.5v-4a1 1 0 0 1 2 0v4a4.54 4.54 0 0 1-4.57 4.5h-4.643l-4.114 3.74A1.002 1.002 0 0 1 6.92 21v-3h-.43a4.54 4.54 0 0 1-4.57-4.5v-7A4.54 4.54 0 0 1 6.49 2zm6.414.6.725.726c.79.791.79 2.074 0 2.865l-5.812 5.794c-.128.128-.29.219-.465.263l-2.9.721q-.121.03-.247.031a.998.998 0 0 1-.969-1.244l.73-2.9a1 1 0 0 1 .263-.463L17.9 2.6a2.027 2.027 0 0 1 2.864 0m-1.412 1.413-.763.724L13.7 9.612l-.255 1.015 1.016-.252 5.616-5.6V4.74z">
                            </path>
                        </svg>
                        {{ __('Give feedback') }}
                    </a>
                    <button type="button" class="qbo-collapse-btn" id="moneyBarToggle" title="Toggle Money Bar">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" color="currentColor"
                            width="24px" height="24px" focusable="false" aria-hidden="true">
                            <path fill="currentColor"
                                d="M18.014 15.991a1 1 0 0 1-.708-.294l-5.285-5.3-5.3 5.285a.999.999 0 1 1-1.412-1.416l6.008-5.99a1 1 0 0 1 1.414 0l5.992 6.008a1 1 0 0 1-.708 1.706z">
                            </path>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Money Bar - QBO Structure --}}
            <div class="qbo-money-bar" id="moneyBar">
                <section class="moneybar-group">
                    {{-- Estimates --}}
                    <div class="moneybar-section estimate">
                        <div class="text">
                            <span
                                class="amount">{{ Auth::user()->priceFormat($salesData['estimates']['amount'] ?? 0) }}</span>
                            <div class="label"><span>{{ $salesData['estimates']['count'] ?? 0 }}
                                    {{ __('estimates') }}</span></div>
                        </div>
                        <div class="bar" data-filter="estimate" tabindex="0" role="link"
                            aria-label="MoneyBar filter: Estimates"></div>
                    </div>

                    {{-- Unbilled Income --}}
                    <div class="moneybar-section unbilled">
                        <div class="text">
                            <span
                                class="amount">{{ Auth::user()->priceFormat($salesData['unbilled']['amount'] ?? 0) }}</span>
                            <div class="label"><span>{{ __('Unbilled income') }}</span></div>
                        </div>
                        <div class="bar" data-filter="unbilledIncome" tabindex="0" role="link"
                            aria-label="MoneyBar filter: Unbilled income"></div>
                    </div>

                    {{-- Overdue Invoices --}}
                    <div class="moneybar-section overdue">
                        <div class="text">
                            <span
                                class="amount">{{ Auth::user()->priceFormat($salesData['overdue']['amount'] ?? 0) }}</span>
                            <div class="label"><span>{{ $salesData['overdue']['count'] ?? 0 }}
                                    {{ __('overdue invoices') }}</span></div>
                        </div>
                        <div class="bar" data-filter="overdue" tabindex="0" role="link"
                            aria-label="MoneyBar filter: Overdue invoices"></div>
                    </div>

                    {{-- Open Invoices and Credits --}}
                    <div class="moneybar-section openInvoices">
                        <div class="text">
                            <span class="amount">{{ Auth::user()->priceFormat($salesData['open']['amount'] ?? 0) }}</span>
                            <div class="label"><span>{{ $salesData['open']['count'] ?? 0 }}
                                    {{ __('open invoices and credits') }}</span></div>
                        </div>
                        <div class="bar" data-filter="openInvoices" tabindex="0" role="link"
                            aria-label="MoneyBar filter: Open invoices"></div>
                    </div>

                    {{-- Recently Paid --}}
                    <div class="moneybar-section recentlyPaid">
                        <div class="text">
                            <span class="amount">{{ Auth::user()->priceFormat($salesData['paid']['amount'] ?? 0) }}</span>
                            <div class="label"><span>{{ $salesData['paid']['count'] ?? 0 }}
                                    {{ __('recently paid') }}</span></div>
                        </div>
                        <div class="bar" data-filter="recentlyPaid" tabindex="0" role="link"
                            aria-label="MoneyBar filter: Recently paid"></div>
                    </div>
                </section>
            </div>

            {{-- Info Alert --}}
            <div class="qbo-info-alert" id="infoAlert">
                <svg width="26" height="24" viewBox="0 0 26 24" fill="none"
                    xmlns="http://www.w3.org/2000/svg" class="g5XsGF+8GhwgxGvOFOBVyg==">
                    <path
                        d="M0 12C0 5.37258 5.37258 0 12 0C18.6274 0 24 5.37258 24 12C24 18.6274 18.6274 24 12 24C5.37258 24 0 18.6274 0 12Z"
                        fill="#00254A"></path>
                    <path d="M16.7438 7.3916H13.6225V10.6146H10.6562V13.1336H7.47888V16.6565H16.7438V7.3916Z"
                        fill="#00D5B0"></path>
                    <path
                        d="M20.9999 0C20.9999 0 20.4477 2.37016 19.4089 3.40901C18.37 4.44786 15.9999 5 15.9999 5C15.9999 5 18.37 5.55214 19.4089 6.59099C20.4477 7.62984 20.9999 10 20.9999 10C20.9999 10 21.552 7.62984 22.5909 6.59099C23.6297 5.55214 25.9999 5 25.9999 5C25.9999 5 23.6297 4.44786 22.5909 3.40901C21.552 2.37016 20.9999 0 20.9999 0Z"
                        fill="#00D5B0"></path>
                </svg>
                <div class="qbo-info-alert-content">
                    <div class="qbo-info-alert-title">{{ __('Consider setting up late fees') }}</div>
                    <div class="qbo-info-alert-text">
                        {{ __('70% of invoices you sent in the last 12 months were paid late, or not at all. I drafted a late fee plan to encourage timely payment.') }}
                    </div>
                </div>
                <button class="qbo-info-alert-btn">{{ __('Set up late fees') }}</button>
                <button class="qbo-info-alert-close" onclick="document.getElementById('infoAlert').style.display='none'">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" color="currentColor"
                        width="20px" height="20px" focusable="false" aria-hidden="true" class="">
                        <path fill="currentColor"
                            d="m13.432 11.984 5.3-5.285a1 1 0 1 0-1.412-1.416l-5.3 5.285-5.285-5.3A1 1 0 1 0 5.319 6.68l5.285 5.3L5.3 17.265a1 1 0 1 0 1.412 1.416l5.3-5.285L17.3 18.7a1 1 0 1 0 1.416-1.412l-5.284-5.304Z">
                        </path>
                    </svg>
                </button>
            </div>

            {{-- Filter Bar --}}
            <div class="qbo-filter-bar">
                {{ Form::open(['route' => 'sales.transactions.index', 'method' => 'GET', 'id' => 'filterForm']) }}

                <div class="qbo-filters-primary">
                    {{-- Batch Actions --}}
                    <div class="dropdown">
                        <button type="button" class="qbo-batch-btn dropdown-toggle" data-bs-toggle="dropdown">
                            {{ __('Batch actions') }}
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">{{ __('Print selected') }}</a></li>
                            <li><a class="dropdown-item" href="#">{{ __('Email selected') }}</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="#">{{ __('Delete selected') }}</a></li>
                        </ul>
                    </div>

                    {{-- Type --}}
                    <div class="qbo-filter-group">
                        <label class="qbo-filter-label">{{ __('Type') }}</label>
                        {{ Form::select('type', $typeOptions, $type, ['class' => 'qbo-filter-select', 'onchange' => 'this.form.submit()']) }}
                    </div>

                    {{-- Date --}}
                    <div class="qbo-filter-group">
                        <label class="qbo-filter-label">{{ __('Date') }}</label>
                        {{ Form::select('date_range', $dateRangeOptions, $dateRange, ['class' => 'qbo-filter-select', 'onchange' => 'this.form.submit()']) }}
                    </div>

                    {{-- Customer --}}
                    <div class="qbo-filter-group customer-group">
                        <label class="qbo-filter-label">{{ __('Customer') }}</label>
                        {{ Form::select('customer', $customers, $customerId, ['class' => 'qbo-filter-select', 'onchange' => 'this.form.submit()', 'style' => 'width: 100%;']) }}
                    </div>

                    {{-- New Transaction --}}
                    <div class="dropdown" style="margin-left: auto;">
                        <button type="button" class="qbo-new-btn" data-bs-toggle="dropdown">
                            {{ __('New transaction') }}
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                color="currentColor" width="16px" height="16px" focusable="false" aria-hidden="true"
                                class="dropdown-arrow">
                                <path fill="currentColor"
                                    d="M12.014 16.018a1 1 0 0 1-.708-.294L5.314 9.715A1.001 1.001 0 0 1 6.73 8.3l5.286 5.3 5.3-5.285a1 1 0 0 1 1.413 1.416l-6.009 5.995a1 1 0 0 1-.706.292">
                                </path>
                            </svg>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end qbo-new-dropdown">
                            <li><a class="dropdown-item" href="{{ route('invoice.create', 0) }}">{{ __('Invoice') }}</a>
                            </li>
                            {{-- <li><a class="dropdown-item" href="{{ route('invoice.create', 0) }}">{{ __('Import invoices') }}</a></li> --}}
                            <li><a class="dropdown-item"
                                    href="{{ route('receive-payment.create') }}">{{ __('Payment') }}</a></li>
                            <li><a class="dropdown-item"
                                    href="{{ route('proposal.create', 0) }}">{{ __('Estimate') }}</a></li>
                            <li><a class="dropdown-item" href="#"
                                    onclick="showComingSoon(); return false;">{{ __('Payment Link') }}</a></li>
                            <li><a class="dropdown-item"
                                    href="{{ route('sales-receipt.create') }}">{{ __('Sales Receipt') }}</a></li>
                            <li><a class="dropdown-item"
                                    href="{{ route('creditmemo.create', 0) }}">{{ __('Credit Memo') }}</a></li>
                            <li><a class="dropdown-item"
                                    href="{{ route('refund-receipt.create') }}">{{ __('Refund Receipt') }}</a></li>
                            <li><a class="dropdown-item openDelayedCreditModal" href="#"
                                    data-url="{{ route('delayed-credit.create') }}">{{ __('Delayed Credit') }}</a></li>
                            <li><a class="dropdown-item openDelayedChargeModal" href="#"
                                    data-url="{{ route('delayed-charge.create') }}">{{ __('Delayed Charge') }}</a></li>
                            <li><a class="dropdown-item"
                                    href="{{ route('timeActivity.create') }}">{{ __('Time Activity') }}</a></li>
                        </ul>
                    </div>
                </div>

                {{-- Secondary Filters - aligned under Type filter --}}
                <div class="qbo-filters-secondary">
                    {{-- All Statuses --}}
                    <div class="dropdown">
                        <button type="button" class="qbo-secondary-filter" data-bs-toggle="dropdown">
                            {{ $status == 'all' ? __('All statuses') : ucfirst($status) }}
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="12"
                                height="12">
                                <path fill="currentColor"
                                    d="M12.014 16.018a1 1 0 0 1-.708-.294L5.314 9.715A1.001 1.001 0 0 1 6.73 8.3l5.286 5.3 5.3-5.285a1 1 0 0 1 1.413 1.416l-6.009 5.995a1 1 0 0 1-.706.292">
                                </path>
                            </svg>
                        </button>
                        <ul class="dropdown-menu qbo-secondary-dropdown">
                            <li><a class="dropdown-item" href="#"
                                    onclick="setFilter('status', 'all'); return false;">{{ __('All') }}</a></li>
                            <li><a class="dropdown-item" href="#"
                                    onclick="setFilter('status', 'open'); return false;">{{ __('Open') }}</a></li>
                            <li><a class="dropdown-item" href="#"
                                    onclick="setFilter('status', 'overdue'); return false;">{{ __('Overdue') }}</a></li>
                            <li><a class="dropdown-item" href="#"
                                    onclick="setFilter('status', 'paid'); return false;">{{ __('Paid') }}</a></li>
                            <li><a class="dropdown-item" href="#"
                                    onclick="setFilter('status', 'pending'); return false;">{{ __('Pending') }}</a></li>
                            <li><a class="dropdown-item" href="#"
                                    onclick="setFilter('status', 'accepted'); return false;">{{ __('Accepted') }}</a></li>
                            <li><a class="dropdown-item" href="#"
                                    onclick="setFilter('status', 'closed'); return false;">{{ __('Closed') }}</a></li>
                            <li><a class="dropdown-item" href="#"
                                    onclick="setFilter('status', 'converted'); return false;">{{ __('Converted') }}</a>
                            </li>
                            <li><a class="dropdown-item" href="#"
                                    onclick="setFilter('status', 'declined'); return false;">{{ __('Declined') }}</a></li>
                            <li><a class="dropdown-item" href="#"
                                    onclick="setFilter('status', 'expired'); return false;">{{ __('Expired') }}</a></li>
                            <li><a class="dropdown-item" href="#"
                                    onclick="setFilter('status', 'voided'); return false;">{{ __('Voided') }}</a></li>
                        </ul>
                    </div>

                    {{-- Delivery Method --}}
                    <div class="dropdown">
                        <button type="button" class="qbo-secondary-filter" data-bs-toggle="dropdown">
                            {{ __('Delivery method') }}
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="12"
                                height="12">
                                <path fill="currentColor"
                                    d="M12.014 16.018a1 1 0 0 1-.708-.294L5.314 9.715A1.001 1.001 0 0 1 6.73 8.3l5.286 5.3 5.3-5.285a1 1 0 0 1 1.413 1.416l-6.009 5.995a1 1 0 0 1-.706.292">
                                </path>
                            </svg>
                        </button>
                        <ul class="dropdown-menu qbo-secondary-dropdown">
                            <li><a class="dropdown-item" href="#"
                                    onclick="setFilter('delivery', 'any'); return false;">{{ __('Any') }}</a></li>
                            <li><a class="dropdown-item" href="#"
                                    onclick="setFilter('delivery', 'send_later'); return false;">{{ __('Send later') }}</a>
                            </li>
                            <li><a class="dropdown-item" href="#"
                                    onclick="setFilter('delivery', 'print_later'); return false;">{{ __('Print later') }}</a>
                            </li>
                        </ul>
                    </div>

                    {{-- Errors --}}
                    <div class="dropdown">
                        <button type="button" class="qbo-secondary-filter" data-bs-toggle="dropdown">
                            {{ __('Errors') }}
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="12"
                                height="12">
                                <path fill="currentColor"
                                    d="M12.014 16.018a1 1 0 0 1-.708-.294L5.314 9.715A1.001 1.001 0 0 1 6.73 8.3l5.286 5.3 5.3-5.285a1 1 0 0 1 1.413 1.416l-6.009 5.995a1 1 0 0 1-.706.292">
                                </path>
                            </svg>
                        </button>
                        <ul class="dropdown-menu qbo-secondary-dropdown">
                            <li><a class="dropdown-item" href="#"
                                    onclick="setFilter('errors', 'none'); return false;">{{ __('None') }}</a></li>
                            <li><a class="dropdown-item" href="#"
                                    onclick="setFilter('errors', 'delivery'); return false;">{{ __('Delivery errors') }}</a>
                            </li>
                            <li><a class="dropdown-item" href="#"
                                    onclick="setFilter('errors', 'payment'); return false;">{{ __('Payment processing errors') }}</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <input type="hidden" name="status" id="statusInput" value="{{ $status }}">
                <input type="hidden" name="delivery" id="deliveryInput" value="{{ request('delivery', 'any') }}">
                <input type="hidden" name="errors" id="errorsInput" value="{{ request('errors', 'none') }}">
                {{ Form::close() }}
            </div>

            {{-- Table --}}
            <div class="qbo-table-wrapper">
                {{-- Data Table --}}
                <table class="table" id="salesTransactionsTable">
                    <thead>
                        <tr>
                            <th width="40"><input type="checkbox" class="form-check-input" id="selectAll"></th>
                            <th>{{ __('DATE') }} ▼</th>
                            <th>{{ __('TYPE') }}</th>
                            <th>{{ __('NO.') }}</th>
                            <th>{{ __('CUSTOMER') }}</th>
                            <th>{{ __('MEMO') }}</th>
                            <th class="text-end">{{ __('AMOUNT') }}</th>
                            <th>{{ __('STATUS') }}</th>
                            <th>
                                <div class="qbo-header-actions">
                                    {{ __('ACTION') }}
                                    <button class="qbo-header-action-btn" title="{{ __('Print') }}"><i
                                            class="ti ti-printer"></i></button>
                                    <button class="qbo-header-action-btn" title="{{ __('Export') }}"><i
                                            class="ti ti-download"></i></button>
                                    <button class="qbo-header-action-btn" title="{{ __('More') }}"><i
                                            class="ti ti-dots-vertical"></i></button>
                                    <button class="qbo-header-action-btn" title="{{ __('Settings') }}"><i
                                            class="ti ti-settings"></i></button>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalAmount = collect($transactions ?? [])->sum('amount');
                        @endphp

                        @forelse($transactions ?? [] as $txn)
                            <tr>
                                <td><input type="checkbox" class="form-check-input row-checkbox"
                                        value="{{ $txn['id'] ?? '' }}"></td>
                                <td>{{ \Carbon\Carbon::parse($txn['date'] ?? '')->format('m/d/y') }}</td>
                                <td>{{ $txn['type'] ?? '' }}</td>
                                <td>{{ str_replace('#', '', $txn['no'] ?? '') }}</td>
                                <td>{{ $txn['customer'] ?? '' }}</td>
                                <td>{{ Str::limit($txn['memo'] ?? '', 30) }}</td>
                                <td class="text-end">{{ Auth::user()->priceFormat(abs($txn['amount'] ?? 0)) }}</td>
                                <td>
                                    @php
                                        $status = strtolower($txn['status'] ?? '');
                                        $statusClass = 'open';
                                        $icon = 'ti-clock';
                                        if (str_contains($status, 'overdue')) {
                                            $statusClass = 'overdue';
                                            $icon = 'ti-alert-circle';
                                        } elseif ($status == 'paid' || $status == 'closed' || $status == 'applied') {
                                            $statusClass = 'paid';
                                            $icon = 'ti-check';
                                        } elseif ($status == 'draft') {
                                            $statusClass = 'draft';
                                            $icon = 'ti-file';
                                        }
                                    @endphp
                                    <span class="qbo-status {{ $statusClass }}">
                                        <i class="ti {{ $icon }}"></i>
                                        {{ $txn['status'] ?? '' }}
                                    </span>
                                </td>
                                <td>
                                    @if (!empty($txn['view_url']))
                                        <a href="{{ $txn['view_url'] }}"
                                            class="qbo-action-link">{{ __('View/Edit') }}</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    {{ __('No transactions found') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="qbo-total-row">
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="text-end"><strong>{{ Auth::user()->priceFormat($totalAmount) }}</strong></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#salesTransactionsTable');
            // Count actual data rows (exclude the "No transactions found" row)
            var dataRows = table.find('tbody tr').filter(function() {
                return !$(this).find('td[colspan]').length;
            });
            var rowCount = dataRows.length;

            console.log('Sales Transactions DataTable - Row count:', rowCount);

            // Initialize DataTable if there are any data rows
            if (rowCount > 0) {
                table.DataTable({
                    order: [
                        [1, 'desc']
                    ],
                    pageLength: 25,
                    dom: 'rtip',
                    language: {
                        paginate: {
                            first: 'First',
                            last: 'Last',
                            next: 'Next',
                            previous: 'Previous'
                        },
                        info: '_START_-_END_ of _TOTAL_'
                    }
                });
            }

            // Money Bar Toggle
            $('#moneyBarToggle').on('click', function() {
                $('#moneyBar').toggleClass('collapsed');
                $(this).toggleClass('collapsed');
            });

            // Select All checkbox
            $('#selectAll').on('change', function() {
                $('.row-checkbox').prop('checked', this.checked);
            });
        });

        function showComingSoon() {
            if (typeof show_toastr !== 'undefined') {
                show_toastr('info', '{{ __('This feature is coming soon!') }}', 'info');
            } else {
                alert('{{ __('This feature is coming soon!') }}');
            }
        }

        function setFilter(name, value) {
            document.getElementById(name + 'Input').value = value;
            document.getElementById('filterForm').submit();
        }
        // Delayed Credit Modal
        $(document).on('click', '.openDelayedCreditModal', function(e) {
            e.preventDefault();
            var url = $(this).data('url');
            $('#delayedCreditModal').modal('show');
            $('#delayedCreditModal .modal-content').html(
                '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>'
                );
            $.ajax({
                url: url,
                type: 'GET',
                success: function(res) {
                    $('#delayedCreditModal .modal-content').html(res);
                },
                error: function() {
                    $('#delayedCreditModal .modal-content').html(
                        '<div class="text-center text-danger p-5">Something went wrong!</div>');
                }
            });
        });

        // Delayed Charge Modal
        $(document).on('click', '.openDelayedChargeModal', function(e) {
            e.preventDefault();
            var url = $(this).data('url');
            $('#delayedChargeModal').modal('show');
            $('#delayedChargeModal .modal-content').html(
                '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>'
                );
            $.ajax({
                url: url,
                type: 'GET',
                success: function(res) {
                    $('#delayedChargeModal .modal-content').html(res);
                },
                error: function() {
                    $('#delayedChargeModal .modal-content').html(
                        '<div class="text-center text-danger p-5">Something went wrong!</div>');
                }
            });
        });
    </script>
@endpush
