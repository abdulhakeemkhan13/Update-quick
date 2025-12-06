@extends('layouts.admin')
@section('breadcrumb')
@endsection

@section('content')
<style>
    body.theme-6 .form-check-input:focus, body.theme-6 .form-select:focus, body.theme-6 .form-control:focus, body.theme-6 .custom-select:focus, body.theme-6 .dataTable-selector:focus, body.theme-6 .dataTable-input:focus {
    border-color: #00892E;
    box-shadow: 0 0 0 1.2px #00892E;
}
    .dash-container .dash-content{
        padding-left: 15px !important;
        padding-right: 15px !important;
    }
    /* Fixed Header Styles */
    .vendor-header {
        background: #fff;
        padding: 10px 20px;
        position: sticky;
        top: 0;
        z-index: 99;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
    }
    
    .vendor-header::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 80%;
        height: 4px;
        background: transparent;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
    }

    .vendor-header .header-left {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .vendor-header .vendor-name {
        font-size: 24px;
        font-weight: 600;
        color: #333;
        margin: 0;
    }

    .vendor-header .back-btn {
        color: #333;
        font-size: 18px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .vendor-header .header-right {
        display: flex;
        gap: 10px;
    }

    /* Layout Styles */
    .vendor-container {
        display: flex;
        height: calc(100vh - 130px);
        overflow: hidden;
        gap: 10px;
    }

    .vendor-sidebar {
        width: 250px;
        background: #fff;
        border: 1px solid #D4D7DC;
        border-radius: 4px;
        box-shadow: rgba(0, 0, 0, 0.2) 0 1px 4px 0;
        display: flex;
        flex-direction: column;
        transition: width 0.3s ease;
        flex-shrink: 0;
    }

    .vendor-sidebar.collapsed {
        width: 50px;
    }

    .sidebar-header {
        padding: 10px;
        border-bottom: 1px solid #e0e0e0;
        display: flex;
        justify-content: flex-end;
        align-items: center;
    }
    
    .sidebar-btn-group {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .sidebar-add-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        background-color: #00892E;
        border-radius: 4px;
        color: #fff;
        text-decoration: none;
    }
    
    .sidebar-add-btn:hover {
        background-color: #006f24;
        color: #fff;
    }

    .sidebar-toggle {
        cursor: pointer;
        padding: 5px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        color: #666;
    }

    .sidebar-toggle:hover {
        background: #e0e0e0;
    }

    .vendor-list {
        overflow-y: auto;
        flex: 1;
        padding: 5px;
        list-style: none;
        margin: 0;
    }

    .vendor-list-item {
        padding: 10px 15px;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
        transition: background 0.2s;
        border-left: 3px solid transparent;
    }

    .vendor-list-item:hover {
        background: #f5f5f5;
    }
    
    .vendor-list-item.active {
        background-color: #dcf5ff;
    }

    .vendor-list-item .v-name {
        font-weight: 500;
        color: #333;
        display: block;
        font-size: 14px;
    }
    
    .vendor-list-item .v-balance {
        font-size: 12px;
        color: #666;
    }

    .vendor-content {
        flex: 1;
        padding: 0;
        overflow-y: auto;
        overflow-x: hidden;
        background: #fff;
    }

    .sidebar-search {
        padding: 10px;
    }
    
    .sidebar-search input {
        font-size: 13px;
    }
    
    /* Collapsed state handling */
    .vendor-sidebar.collapsed .vendor-list-item,
    .vendor-sidebar.collapsed .sidebar-search,
    .vendor-sidebar.collapsed .sidebar-add-btn {
        display: none;
    }
    
    .vendor-sidebar.collapsed .sidebar-toggle {
        margin: 0 auto;
    }

    /* Vendor Name Section */
    .vendor-name-section {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 15px;
    }
    
    .vendor-title {
        font-size: 24px;
        font-weight: 700;
        color: #333;
        margin: 0;
    }
    
    .vendor-icon-link {
        color: #666;
        font-size: 16px;
        text-decoration: none;
    }
    
    .vendor-icon-link:hover {
        color: #0077c5;
    }

    /* Two Cards Row */
    .vendor-cards-row {
        display: flex;
        gap: 15px;
        padding: 20px;
    }

    /* Vendor Info Card - Left */
    .vendor-info-card {
        flex: 1;
        background: #fff;
        border: 1px solid #D4D7DC;
        border-radius: 4px;
        padding: 20px;
        box-shadow: 0 1px 4px 0 rgba(0, 0, 0, 0.2);
    }
    
    .vendor-details-section {
        display: flex;
        flex-wrap: wrap;
        gap: 30px;
        margin-bottom: 15px;
    }
    
    .detail-item {
        display: flex;
        flex-direction: column;
        min-width: 120px;
    }
    
    .detail-label {
        font-size: 11px;
        color: #666;
        text-transform: capitalize;
        margin-bottom: 2px;
    }
    
    .detail-value {
        font-size: 14px;
        color: #333;
    }
    
    .detail-link {
        font-size: 14px;
        text-decoration: none;
    }
    
    .vendor-notes-section {
        display: flex;
        flex-direction: column;
    }
    
    /* Summary Card - Right */
    .vendor-summary-card {
        width: 180px;
        background: #fff;
        border: 1px solid #D4D7DC;
        box-shadow: 0 1px 4px 0 rgba(0, 0, 0, 0.2);
        border-radius: 8px;
        padding: 15px;
        flex-shrink: 0;
    }
    
    .summary-title {
        font-size: 11px;
        font-weight: 600;
        color: #666;
        margin-bottom: 12px;
    }
    
    .summary-item {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }
    
    .summary-bar {
        width: 4px;
        height: 35px;
        margin-right: 10px;
        border-radius: 2px;
    }
    
    .summary-bar-orange {
        background-color: #f5a623;
    }
    
    .summary-bar-red {
        background-color: #d9534f;
    }
    
    .summary-content {
        display: flex;
        flex-direction: column;
    }
    
    .summary-value {
        font-size: 16px;
        font-weight: 600;
        color: #333;
    }
    
    .summary-label {
        font-size: 11px;
        color: #666;
    }

    /* Tabs - QBO Style */
    .vendor-tabs-qbo {
        border-bottom: 2px solid #D4D7DC;
        margin-bottom: 15px;
        padding: 0;
        list-style: none;
        display: flex;
    }
    
    .vendor-tabs-qbo .nav-item {
        margin-right: 0;
    }
    
    .vendor-tabs-qbo .nav-link {
        color: #666;
        font-weight: 500;
        font-size: 14px;
        border: none;
        border-bottom: 3px solid transparent;
        padding: 12px 20px;
        background: transparent;
        cursor: pointer;
    }
    
    .vendor-tabs-qbo .nav-link:hover {
        color: #333;
    }
    
    .vendor-tabs-qbo .nav-link.active {
        color: #333;
        border-bottom: 3px solid #2ca01c;
    }

    /* Transaction Filter Bar */
    .transaction-filter-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        margin-bottom: 15px;
    }
    
    .filter-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .filter-right {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .transaction-type-select {
        width: 180px;
        font-size: 13px;
        border: 1px solid #ccc;
        border-radius: 4px;
        padding: 6px 10px;
    }
    
    .filter-btn {
        color: #333;
        font-size: 13px;
        text-decoration: none;
    }
    
    .filter-btn:hover {
        color: #2ca01c;
    }
    
    .date-badge {
        background-color: #f0f0f0;
        padding: 6px 12px;
        border-radius: 15px;
        border: solid 2px #8d9096;
        font-size: 12px;
        color: #333;
    }
    
    .icon-btn {
        color: #666;
        font-size: 18px;
        padding: 5px 8px;
    }
    
    .icon-btn:hover {
        color: #333;
    }

    /* Filter Popup Styles */
    .filter-dropdown-wrapper {
        position: relative;
        display: inline-block;
    }
    
    .filter-popup {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        z-index: 1000;
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        min-width: 450px;
        margin-top: 5px;
    }
    
    .filter-popup.show {
        display: block;
    }
    
    .filter-popup-header {
        display: flex;
        justify-content: flex-end;
        padding: 10px 15px 0;
    }
    
    .filter-popup-body {
        padding: 10px 20px;
    }
    
    .filter-popup-body .form-label {
        font-size: 12px;
        color: #666;
        margin-bottom: 5px;
    }
    
    .filter-popup-body .form-select,
    .filter-popup-body .form-control {
        font-size: 13px;
    }
    
    .filter-popup-footer {
        display: flex;
        justify-content: space-between;
        padding: 15px 20px;
        border-top: 1px solid #e0e0e0;
    }
    
    .filter-popup-footer .btn {
        min-width: 80px;
    }

    /* QBO Table Style */
    .qbo-table {
        font-size: 13px;
    }
    
    .qbo-table thead th {
        font-weight: 600;
        font-size: 11px;
        text-transform: uppercase;
        color: #666;
        border-bottom: 1px solid #e0e0e0;
        padding: 12px 8px;
        background: #fff;
    }
    
    .qbo-table tbody td {
        padding: 12px 8px;
        border-bottom: 1px solid #f0f0f0;
        vertical-align: middle;
    }
    
    .qbo-table tbody tr:hover {
        background-color: #f9f9f9;
    }

    /* Details Tab */
    .details-section-title {
        font-size: 14px;
        font-weight: 600;
        color: #333;
        margin-bottom: 15px;
        border-bottom: 1px solid #e0e0e0;
        padding-bottom: 8px;
    }
    
    .details-table tr td {
        padding: 6px 0;
        font-size: 13px;
    }
    
    .details-label {
        color: #666;
        width: 140px;
    }
    
    .details-value {
        color: #333;
    }
</style>

<!-- Fixed Header -->
<div class="vendor-header">
    <div class="header-left">
        <a href="{{ route('vender.index') }}" class="back-btn">
            <svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="currentColor" focusable="false" aria-hidden="true" style="color: rgb(0, 119, 197);"><path d="M15.009 19.022a1 1 0 0 1-.708-.294L8.31 12.72a.999.999 0 0 1 0-1.415l6.009-5.991a1 1 0 0 1 1.414 1.416l-5.3 5.285 5.285 5.3a1 1 0 0 1-.708 1.706l-.001.001Z" fill="currentColor"></path></svg>
        </a>
        <h3 class="vendor-name">{{__('Vendors')}}</h3>
    </div>
    <div class="header-right">
        @can('edit vender')
            <div class="btn-group">
                <a href="#" data-size="xl" data-url="{{ route('vender.edit',$vendor->id) }}" data-ajax-popup="true" title="{{__('Edit')}}" class="btn btn-outline-secondary">
                    {{__('Edit')}}
                </a>
                <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="visually-hidden">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu">
                    @can('delete vender')
                        <li>
                             {!! Form::open(['method' => 'DELETE', 'route' => ['vender.destroy', $vendor->id],'id'=>'delete-form-'.$vendor->id]) !!}
                                <a href="#" class="dropdown-item text-danger" data-confirm="{{__('Are You Sure?').'|'.__('This action can not be undone. Do you want to continue?')}}" data-confirm-yes="document.getElementById('delete-form-{{ $vendor->id}}').submit();">
                                    {{__('Make inactive')}}
                                </a>
                            {!! Form::close() !!}
                        </li>
                    @endcan
                </ul>
            </div>
        @endcan

        <div class="btn-group">
            <button class="btn btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: #2ca01c; border-color: #2ca01c;">
                {{__('New transaction')}}
            </button>
            <ul class="dropdown-menu">
                @can('create bill')
                <li><a class="dropdown-item" href="{{ route('bill.create',$vendor->id) }}">{{__('Bill')}}</a></li>
                @endcan
                <li><a class="dropdown-item" href="#">{{__('Expense')}}</a></li>
                <li><a class="dropdown-item" href="#">{{__('Check')}}</a></li>
                <li><a class="dropdown-item" href="#">{{__('Purchase Order')}}</a></li>
            </ul>
        </div>
    </div>
</div>

<div class="vendor-container">
    <!-- Left Sidebar -->
    <div class="vendor-sidebar" id="vendorSidebar">
        <div class="sidebar-header">
            <div class="sidebar-btn-group">
                <!-- add vendor plus svg button -->
                <a href="#" data-size="lg" data-url="{{ route('vender.create') }}" data-ajax-popup="true" data-title="{{__('Create New Vendor')}}" class="sidebar-add-btn">
                    <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="currentColor" focusable="false" aria-hidden="true"><path d="m17.983 11.027-5-.007.007-5a1 1 0 0 0-2 0l-.007 5-5-.008a1 1 0 0 0 0 2l5 .008-.008 5a1 1 0 1 0 2 0l.008-5 5 .007a1 1 0 1 0 0-2Z" fill="currentColor"></path></svg>
                </a>
                <div class="sidebar-toggle" onclick="toggleSidebar()">
                    <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="currentColor" focusable="false" aria-hidden="true"><path d="m14.011 8.006-10-.012a1 1 0 0 1 0-2l10 .012a1 1 0 1 1 0 2ZM14 13.006l-10-.012a1 1 0 1 1 0-2l10 .012a1 1 0 0 1 0 2ZM14 18.006l-10-.012a1 1 0 1 1 0-2l10 .012a1 1 0 0 1 0 2ZM20.985 10a1 1 0 0 0-1.71-.7l-1.99 2.009a1 1 0 0 0 .006 1.414l2.009 1.99A1 1 0 0 0 21 14l-.015-4Z" fill="currentColor"></path></svg>
                </div>
            </div>
        </div>
        <div class="sidebar-search">
             <input type="text" class="form-control" placeholder="Search" id="sidebarSearch">
        </div>
        <ul class="vendor-list" id="vendorList">
            @foreach($vendors as $v)
                <li class="vendor-list-item {{ $v->id == $vendor->id ? 'active' : '' }}" onclick="window.location.href='{{ route('vender.show', \Crypt::encrypt($v->id)) }}'">
                    <span class="v-name">{{ $v->name }}</span>
                    <span class="v-balance">{{\Auth::user()->priceFormat($v->getDueAmount())}}</span>
                </li>
            @endforeach
        </ul>
    </div>

    <!-- Right Content -->
    <div class="vendor-content">
        <!-- Two Cards Row -->
        <div class="vendor-cards-row">
            <!-- Left Card - Vendor Info -->
            <div class="vendor-info-card">
                <!-- Row 1: Vendor Name with Icons -->
                <div class="vendor-name-section">
                    <h2 class="vendor-title">{{ $vendor->name }}</h2>
                    <a href="mailto:{{ $vendor->email ?? '' }}" class="vendor-icon-link"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="currentColor" focusable="false" aria-hidden="true" aria-describedby="ids-tooltip-f9e6xpm"><path d="M19 4H5a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3ZM5 6h14a1 1 0 0 1 1 1v1.279l-7.684 2.562a1.012 1.012 0 0 1-.632 0L4 8.279V7a1 1 0 0 1 1-1Zm14 12H5a1 1 0 0 1-1-1v-6.613l7.051 2.351a3.02 3.02 0 0 0 1.9 0L20 10.387V17a1 1 0 0 1-1 1Z" fill="currentColor"></path></svg></a>
                    <a href="tel:{{ $vendor->contact ?? '' }}" class="vendor-icon-link"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="currentColor" focusable="false" aria-hidden="true" aria-describedby="ids-tooltip-di8pzgn"><path d="M14.563 22.093a6.953 6.953 0 0 1-4.949-2.05l-5.657-5.657a7.01 7.01 0 0 1 0-9.9l1.994-1.993a2 2 0 0 1 3.041.25l2.358 3.3a1.994 1.994 0 0 1-.213 2.578l-.816.815a1 1 0 0 0 0 1.414l2.828 2.829a1.024 1.024 0 0 0 1.414 0l.816-.815a1.993 1.993 0 0 1 2.577-.213l3.3 2.356a2 2 0 0 1 .252 3.043l-1.993 1.993a6.956 6.956 0 0 1-4.952 2.05ZM7.365 3.908 5.371 5.9a5.006 5.006 0 0 0 0 7.07l5.657 5.658a4.998 4.998 0 0 0 7.072 0l1.993-1.994-3.3-2.357-.815.815a3.075 3.075 0 0 1-4.243 0l-2.828-2.83a3 3 0 0 1 0-4.242l.816-.815-2.358-3.297Z" fill="currentColor"></path></svg></a>
                </div>
                
                <!-- Row 2: Company, Address, ACH Info -->
                <div class="vendor-details-section">
                    <div class="detail-item">
                        <small class="detail-label">{{__('Company')}}</small>
                        <span class="detail-value text-primary">{{ $vendor->company_name ?? $vendor->name }}</span>
                    </div>
                    <div class="detail-item">
                        <small class="detail-label">{{__('Billing address')}}</small>
                        <span class="detail-value">
                            @if($vendor->billing_address)
                                {{ $vendor->billing_address }}
                                @if($vendor->billing_city), {{ $vendor->billing_city }}@endif
                                @if($vendor->billing_state), {{ $vendor->billing_state }}@endif
                                {{ $vendor->billing_zip }}
                            @else
                                -
                            @endif
                        </span>
                    </div>
                    <div class="detail-item">
                        <small class="detail-label">{{__('Bill Pay ACH info')}}</small>
                        <span class="detail-value">-</span>
                    </div>
                </div>
                
                <!-- Row 3: Notes -->
                <div class="vendor-notes-section">
                    <small class="detail-label">{{__('Notes')}}</small>
                    <a href="#" data-size="xl" data-url="{{ route('vender.edit',$vendor->id) }}" data-ajax-popup="true" class="text-primary detail-link">{{__('Add notes')}}</a>
                </div>
            </div>
            
            <!-- Right Card - Summary -->
            <div class="vendor-summary-card">
                <div class="summary-title">SUMMARY</div>
                <div class="summary-item">
                    <div class="summary-bar summary-bar-orange"></div>
                    <div class="summary-content">
                        <div class="summary-value">{{\Auth::user()->priceFormat($vendor->getDueAmount())}}</div>
                        <div class="summary-label">Open balance</div>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-bar summary-bar-red"></div>
                    <div class="summary-content">
                        <div class="summary-value">{{\Auth::user()->priceFormat($vendor->vendorOverdue($vendor->id))}}</div>
                        <div class="summary-label">Overdue payment</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs - QBO Style -->
        <ul class="nav vendor-tabs-qbo" id="vendorTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="transaction-tab" data-bs-toggle="tab" data-bs-target="#transaction" type="button" role="tab" aria-controls="transaction" aria-selected="true">{{__('Transaction List')}}</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab" aria-controls="details" aria-selected="false">{{__('Vendor Details')}}</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="notes-tab" data-bs-toggle="tab" data-bs-target="#notes" type="button" role="tab" aria-controls="notes" aria-selected="false">{{__('Notes')}}</button>
            </li>
        </ul>

        <div class="tab-content" id="vendorTabContent" style="padding: 0px 25px 0px 25px;">
            <!-- Transaction List Tab -->
            <div class="tab-pane fade show active" id="transaction" role="tabpanel" aria-labelledby="transaction-tab">
                <!-- Filter Bar -->
                <div class="transaction-filter-bar">
                    <div class="filter-left">
                        <select class="form-select transaction-type-select" id="transactionTypeFilter">
                            <option value="">All transactions</option>
                            <option value="expense">Expense</option>
                            <option value="bill">Bill</option>
                            <option value="bill_payment">Bill payment</option>
                            <option value="check">Check</option>
                            <option value="purchase_order">Purchase order</option>
                            <option value="recently_paid">Recently paid</option>
                            <option value="vendor_credit">Vendor credit</option>
                        </select>
                        <div class="filter-dropdown-wrapper">
                            <button class="btn btn-link filter-btn" type="button" id="filterToggleBtn">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" color="currentColor" width="24px" height="24px" focusable="false" aria-hidden="true" class="c87zJPBCVcBa9+C+gaJN9g=="><path fill="currentColor" d="m20.036 15.036-9.19-.01a2.98 2.98 0 0 0-5.62-.01h-1.19a1 1 0 1 0 0 2h1.18a2.981 2.981 0 0 0 5.64.01l9.18.01a1 1 0 0 0 0-2m-11.28 1.68-.04.04a.97.97 0 0 1-1.36 0l-.04-.04a.97.97 0 0 1 0-1.36l.04-.04a.97.97 0 0 1 1.36 0l.04.04a.97.97 0 0 1 0 1.36M20.046 7.056h-1.18a2.99 2.99 0 0 0-2.81-2.02h-.01a2.97 2.97 0 0 0-2.82 2.01l-9.18-.01a1 1 0 1 0 0 2l9.19.01c.142.418.378.798.69 1.11a2.94 2.94 0 0 0 2.12.88 3 3 0 0 0 2.81-1.98h1.19a1 1 0 1 0 0-2m-3.29 1.66-.04.04a.97.97 0 0 1-1.36 0l-.04-.04a.97.97 0 0 1 0-1.36l.04-.04a.97.97 0 0 1 1.36 0l.04.04a.97.97 0 0 1 0 1.36"></path></svg> {{__('Filter')}}
                            </button>
                            <!-- Filter Popup -->
                            <div class="filter-popup" id="filterPopup">
                                <div class="filter-popup-header">
                                    <button type="button" class="btn-close" id="filterPopupClose"></button>
                                </div>
                                <div class="filter-popup-body">
                                    <div class="mb-3">
                                        <label class="form-label">{{__('Status')}}</label>
                                        <select class="form-select" id="statusFilter">
                                            <option value="">All statuses</option>
                                            <option value="0">Draft</option>
                                            <option value="1">Sent</option>
                                            <option value="2">Unpaid</option>
                                            <option value="3">Partially Paid</option>
                                            <option value="4">Paid</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">{{__('Delivery method')}}</label>
                                        <select class="form-select" id="deliveryFilter">
                                            <option value="">Any</option>
                                            <option value="email">Email</option>
                                            <option value="print">Print</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">{{__('Date')}}</label>
                                        <div class="row g-2">
                                            <div class="col-4">
                                                <select class="form-select" id="datePresetFilter">
                                                    <option value="today">Today</option>
                                                    <option value="yesterday">Yesterday</option>
                                                    <option value="this_week">This week</option>
                                                    <option value="this_month">This month</option>
                                                    <option value="last_year">Last year</option>
                                                    <option value="this_year">This year</option>
                                                    <option value="last_week">Last week</option>
                                                    <option value="last_month">Last month</option>
                                                    <option value="last_3_months">Last 3 months</option>
                                                    <option value="last_6_months">Last 6 months</option>
                                                    <option value="last_12_months" selected>Last 12 months</option>
                                                    <option value="custom">Custom</option>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <div class="input-group">
                                                    <span class="input-group-text" style="font-size: 11px;">From</span>
                                                    <input type="date" class="form-control" id="dateFromFilter">
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="input-group">
                                                    <span class="input-group-text" style="font-size: 11px;">To</span>
                                                    <input type="date" class="form-control" id="dateToFilter">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">{{__('Category')}}</label>
                                        <select class="form-select" id="categoryFilter">
                                            <option value="">All</option>
                                            @if(isset($categories))
                                                @foreach($categories as $cat)
                                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                                <div class="filter-popup-footer">
                                    <button type="button" class="btn btn-outline-secondary" id="filterResetBtn">{{__('Reset')}}</button>
                                    <button type="button" class="btn btn-success" id="filterApplyBtn" style="background-color: #2ca01c; border-color: #2ca01c;">{{__('Apply')}}</button>
                                </div>
                            </div>
                        </div>
                        <span class="date-badge" id="activeDateBadge">Dates: Last 12 months</span>
                    </div>
                    <div class="filter-right">
                        <button class="btn btn-link icon-btn"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" color="currentColor" width="24px" height="24px" focusable="false" aria-hidden="true" class="icon"><path fill="currentColor" d="m18.016 2.01-12-.019a3 3 0 0 0-3 3l-.022 14a3 3 0 0 0 3 3l12 .018a3 3 0 0 0 3-3 1 1 0 1 0-2 0 1 1 0 0 1-1 1l-12-.018a1 1 0 0 1-1-1l.022-14a1 1 0 0 1 1-1l12 .018a1 1 0 0 1 1 1L19 8.961a1 1 0 0 0 2 0l.011-3.954a3 3 0 0 0-2.995-2.998"></path><path fill="currentColor" d="M16.3 17.7a1 1 0 0 0 1.414 0l2.995-3.005a1 1 0 0 0 0-1.414l-3-2.995a1.002 1.002 0 1 0-1.42 1.414l1.3 1.291h-2.647A4.946 4.946 0 0 0 10 17.971a1 1 0 0 0 1 .993h.006A1 1 0 0 0 12 17.958a2.946 2.946 0 0 1 2.941-2.965h2.646l-1.287 1.29a1 1 0 0 0 0 1.417"></path></svg></button>
                        <button class="btn btn-link icon-btn"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" color="currentColor" width="24px" height="24px" focusable="false" aria-hidden="true" class="icon"><path fill="currentColor" d="M5.292 12.283a1 1 0 1 0 1.415 1.413 1 1 0 0 0-1.415-1.413"></path><path fill="currentColor" d="M19 9.01h-1l.008-5a2 2 0 0 0-2-2l-8-.012a2 2 0 0 0-2 2L6 8.991H5a3 3 0 0 0-3 2.995l-.007 5a3.006 3.006 0 0 0 3 3h1.182a2.965 2.965 0 0 0 2.815 2l6 .008h.006a3 3 0 0 0 2.814-2H19a3.006 3.006 0 0 0 3-3l.007-5A3 3 0 0 0 19 9.01M8.012 3.994l8 .011-.008 5L8 8.994zM14.99 20l-6-.008a1 1 0 1 1 0-2l6 .008a1 1 0 1 1 0 2m5-2.993a1.006 1.006 0 0 1-1 1h-1.183a3 3 0 0 0-2.813-2l-6-.008h-.005a2.97 2.97 0 0 0-2.816 2H4.992a1.006 1.006 0 0 1-1-1l.007-5a1 1 0 0 1 1-1H5l14 .02a1 1 0 0 1 1 1z"></path></svg></button>
                        <button class="btn btn-link icon-btn"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" color="currentColor" width="24px" height="24px" focusable="false" aria-hidden="true" class="icon"><path fill="currentColor" d="M12.024 7.982h-.007a4 4 0 1 0 0 8 4 4 0 1 0 .007-8m-.006 6a2 2 0 0 1 .002-4 2 2 0 1 1 0 4z"></path><path fill="currentColor" d="m20.444 13.4-.51-.295a7.6 7.6 0 0 0 0-2.214l.512-.293a2.005 2.005 0 0 0 .735-2.733l-1-1.733a2.005 2.005 0 0 0-2.731-.737l-.512.295a8 8 0 0 0-1.915-1.113v-.59a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v.6a8 8 0 0 0-1.911 1.1l-.52-.3a2 2 0 0 0-2.725.713l-1 1.73a2 2 0 0 0 .728 2.733l.509.295a7.8 7.8 0 0 0-.004 2.22l-.51.293a2 2 0 0 0-.738 2.73l1 1.732a2 2 0 0 0 2.73.737l.513-.295A8 8 0 0 0 9.01 19.39v.586a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2V19.4a8 8 0 0 0 1.918-1.107l.51.3a2 2 0 0 0 2.734-.728l1-1.73a2 2 0 0 0-.728-2.735m-2.593-2.8a5.8 5.8 0 0 1 0 2.78 1 1 0 0 0 .472 1.1l1.122.651-1 1.73-1.123-.65a1 1 0 0 0-1.187.137 6 6 0 0 1-2.4 1.387 1 1 0 0 0-.716.957v1.294h-2v-1.293a1 1 0 0 0-.713-.96 6 6 0 0 1-2.4-1.395 1.01 1.01 0 0 0-1.188-.142l-1.125.648-1-1.733 1.125-.647a1 1 0 0 0 .475-1.1 6 6 0 0 1-.167-1.387c.003-.467.06-.933.17-1.388a1 1 0 0 0-.471-1.1l-1.123-.65 1-1.73 1.124.651c.019.011.04.01.06.02a1 1 0 0 0 .186.063 1 1 0 0 0 .2.04c.02 0 .039.011.059.011a1 1 0 0 0 .136-.025 1 1 0 0 0 .17-.032q.085-.036.163-.087a1 1 0 0 0 .157-.1c.015-.013.034-.017.048-.03a6 6 0 0 1 2.4-1.39l.049-.026a1 1 0 0 0 .183-.1 1 1 0 0 0 .15-.1 1 1 0 0 0 .122-.147q.057-.073.1-.156a1 1 0 0 0 .055-.173q.03-.098.04-.2c0-.018.012-.034.012-.053V3.981h2v1.294a1 1 0 0 0 .713.96c.897.273 1.72.75 2.4 1.395a1 1 0 0 0 1.186.141l1.126-.647 1 1.733-1.125.647a1 1 0 0 0-.465 1.096"></path></svg></button>
                    </div>
                </div>

                <div class="card border-0 shadow-none">
                     <div class="card-body p-0 table-border-style">
                        <div class="table-responsive">
                            {{ $dataTable->table(['class' => 'table qbo-table align-middle mb-0', 'width' => '100%']) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vendor Details Tab -->
            <div class="tab-pane fade" id="details" role="tabpanel" aria-labelledby="details-tab">
                <div class="text-end mb-3">
                    <a href="#" data-size="xl" data-url="{{ route('vender.edit',$vendor->id) }}" data-ajax-popup="true" class="btn btn-outline-secondary btn-sm">{{__('Edit')}}</a>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="details-section-title">{{__('Contact info')}}</h6>
                        <table class="table table-borderless details-table">
                             <tr>
                                 <td class="details-label">{{__('Vendor')}}</td>
                                 <td class="details-value text-primary">{{ $vendor->name }}</td>
                             </tr>
                             <tr>
                                 <td class="details-label">{{__('Email')}}</td>
                                 <td class="details-value">{{ $vendor->email ?? '-' }}</td>
                             </tr>
                             <tr>
                                 <td class="details-label">{{__('Phone')}}</td>
                                 <td class="details-value">{{ $vendor->contact ?? '-' }}</td>
                             </tr>
                             <tr>
                                 <td class="details-label">{{__('Mobile')}}</td>
                                 <td class="details-value">{{ $vendor->mobile ?? '-' }}</td>
                             </tr>
                             <tr>
                                 <td class="details-label">{{__('Fax')}}</td>
                                 <td class="details-value">{{ $vendor->fax ?? '-' }}</td>
                             </tr>
                             <tr>
                                 <td class="details-label">{{__('Other')}}</td>
                                 <td class="details-value">{{ $vendor->other ?? '-' }}</td>
                             </tr>
                             <tr>
                                 <td class="details-label">{{__('Website')}}</td>
                                 <td class="details-value">{{ $vendor->website ?? '-' }}</td>
                             </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                         <h6 class="details-section-title">{{__('Additional info')}}</h6>
                         <table class="table table-borderless details-table">
                             <tr>
                                 <td class="details-label">{{__('Bill Pay ACH info')}}</td>
                                 <td class="details-value">-</td>
                             </tr>
                             <tr>
                                 <td class="details-label">{{__('Billing address')}}</td>
                                 <td class="details-value">{{ $vendor->billing_address ?? '-' }}</td>
                             </tr>
                             <tr>
                                 <td class="details-label">{{__('Terms')}}</td>
                                 <td class="details-value">{{ $vendor->terms ?? '-' }}</td>
                             </tr>
                             <tr>
                                 <td class="details-label">{{__('Company')}}</td>
                                 <td class="details-value">{{ $vendor->company_name ?? '-' }}</td>
                             </tr>
                             <tr>
                                 <td class="details-label">{{__('Notes')}}</td>
                                 <td class="details-value">{{ $vendor->notes ?? '-' }}</td>
                             </tr>
                        </table>
                        <h6 class="details-section-title mt-4">{{__('Attachments')}}</h6>
                        <a href="#" class="text-primary" style="font-size: 14px;">Show existing attachments</a>
                    </div>
                </div>
            </div>

            <!-- Notes Tab -->
            <div class="tab-pane fade" id="notes" role="tabpanel" aria-labelledby="notes-tab">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <button class="btn btn-link icon-btn"><i class="ti ti-star"></i></button>
                        <button class="btn btn-link icon-btn"><i class="ti ti-refresh"></i></button>
                    </div>
                    <a href="#" data-size="xl" data-url="{{ route('vender.edit',$vendor->id) }}" data-ajax-popup="true" class="text-primary" style="font-size: 14px;">+ Add note</a>
                </div>
                @if($vendor->notes)
                    <p>{{ $vendor->notes }}</p>
                @else
                 <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="ti ti-folder text-muted" style="font-size: 64px;"></i>
                    </div>
                    <p class="text-muted">{{__('No notes yet')}}</p>
                 </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@push('css-page')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
@endpush

@push('script-page')
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    {{ $dataTable->scripts() }}
    
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('vendorSidebar');
            sidebar.classList.toggle('collapsed');
        }

        $(document).ready(function() {
            // Sidebar Search
            $("#sidebarSearch").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $("#vendorList li").filter(function() {
                    $(this).toggle($(this).find('.v-name').text().toLowerCase().indexOf(value) > -1)
                });
            });
            
            // Filter Popup Toggle
            $('#filterToggleBtn').on('click', function(e) {
                e.stopPropagation();
                $('#filterPopup').toggleClass('show');
            });
            
            // Close filter popup
            $('#filterPopupClose').on('click', function() {
                $('#filterPopup').removeClass('show');
            });
            
            // Close popup when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.filter-dropdown-wrapper').length) {
                    $('#filterPopup').removeClass('show');
                }
            });
            
            // Date preset change - calculate From/To dates
            $('#datePresetFilter').on('change', function() {
                var preset = $(this).val();
                var today = new Date();
                var fromDate, toDate;
                
                toDate = today.toISOString().split('T')[0];
                
                switch(preset) {
                    case 'today':
                        fromDate = toDate;
                        break;
                    case 'yesterday':
                        var yesterday = new Date(today);
                        yesterday.setDate(yesterday.getDate() - 1);
                        fromDate = yesterday.toISOString().split('T')[0];
                        toDate = fromDate;
                        break;
                    case 'this_week':
                        var firstDay = new Date(today.setDate(today.getDate() - today.getDay()));
                        fromDate = firstDay.toISOString().split('T')[0];
                        toDate = new Date().toISOString().split('T')[0];
                        break;
                    case 'this_month':
                        fromDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
                        toDate = new Date().toISOString().split('T')[0];
                        break;
                    case 'last_week':
                        var lastWeekEnd = new Date(today);
                        lastWeekEnd.setDate(lastWeekEnd.getDate() - lastWeekEnd.getDay() - 1);
                        var lastWeekStart = new Date(lastWeekEnd);
                        lastWeekStart.setDate(lastWeekStart.getDate() - 6);
                        fromDate = lastWeekStart.toISOString().split('T')[0];
                        toDate = lastWeekEnd.toISOString().split('T')[0];
                        break;
                    case 'last_month':
                        fromDate = new Date(today.getFullYear(), today.getMonth() - 1, 1).toISOString().split('T')[0];
                        toDate = new Date(today.getFullYear(), today.getMonth(), 0).toISOString().split('T')[0];
                        break;
                    case 'last_3_months':
                        fromDate = new Date(today.getFullYear(), today.getMonth() - 3, today.getDate()).toISOString().split('T')[0];
                        toDate = new Date().toISOString().split('T')[0];
                        break;
                    case 'last_6_months':
                        fromDate = new Date(today.getFullYear(), today.getMonth() - 6, today.getDate()).toISOString().split('T')[0];
                        toDate = new Date().toISOString().split('T')[0];
                        break;
                    case 'last_12_months':
                        fromDate = new Date(today.getFullYear() - 1, today.getMonth(), today.getDate()).toISOString().split('T')[0];
                        toDate = new Date().toISOString().split('T')[0];
                        break;
                    case 'this_year':
                        fromDate = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
                        toDate = new Date().toISOString().split('T')[0];
                        break;
                    case 'last_year':
                        fromDate = new Date(today.getFullYear() - 1, 0, 1).toISOString().split('T')[0];
                        toDate = new Date(today.getFullYear() - 1, 11, 31).toISOString().split('T')[0];
                        break;
                    case 'custom':
                        // Don't change dates for custom
                        return;
                }
                
                $('#dateFromFilter').val(fromDate);
                $('#dateToFilter').val(toDate);
            });
            
            // Initialize with Last 12 months
            $('#datePresetFilter').trigger('change');
            
            // Transaction type filter change
            $('#transactionTypeFilter').on('change', function() {
                var table = window.LaravelDataTables['vendor-transactions-table'];
                var transactionType = $(this).val();
                var dateFrom = $('#dateFromFilter').val();
                var dateTo = $('#dateToFilter').val();
                
                if (table) {
                    table.ajax.url('{{ route("vender.show", Crypt::encrypt($vendor->id)) }}?' + $.param({
                        transaction_type: transactionType,
                        date_from: dateFrom,
                        date_to: dateTo
                    })).load();
                }
            });
            
            // Apply Filter
            $('#filterApplyBtn').on('click', function() {
                var table = window.LaravelDataTables['vendor-transactions-table'];
                var status = $('#statusFilter').val();
                var dateFrom = $('#dateFromFilter').val();
                var dateTo = $('#dateToFilter').val();
                var category = $('#categoryFilter').val();
                var transactionType = $('#transactionTypeFilter').val();
                var datePreset = $('#datePresetFilter option:selected').text();
                
                // Update the date badge
                if (dateFrom && dateTo) {
                    $('#activeDateBadge').text('Dates: ' + datePreset);
                }
                
                // Reload DataTable with filter parameters
                if (table) {
                    table.ajax.url('{{ route("vender.show", Crypt::encrypt($vendor->id)) }}?' + $.param({
                        status: status,
                        date_from: dateFrom,
                        date_to: dateTo,
                        category: category,
                        transaction_type: transactionType
                    })).load();
                }
                
                $('#filterPopup').removeClass('show');
            });
            
            // Reset Filter
            $('#filterResetBtn').on('click', function() {
                $('#statusFilter').val('');
                $('#deliveryFilter').val('');
                $('#datePresetFilter').val('last_12_months').trigger('change');
                $('#categoryFilter').val('');
                $('#transactionTypeFilter').val('');
                $('#activeDateBadge').text('Dates: Last 12 months');
                
                // Reload DataTable without filters
                var table = window.LaravelDataTables['vendor-transactions-table'];
                if (table) {
                    table.ajax.url('{{ route("vender.show", Crypt::encrypt($vendor->id)) }}').load();
                }
            });
        });
    </script>
@endpush
