@extends('layouts.admin')

@section('page-title')
    {{ __('Sales & Get Paid Overview') }}
@endsection

@push('css-page')
<style>
    .sales-overview-container {
        padding: 30px;
    }
    
    .sales-overview-header {
        position: sticky;
        top: 0;
        z-index: 100;
        background: #fff;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 0;
        margin: 0 -24px;
        padding-left: 24px;
        padding-right: 24px;
        border-bottom: 1px solid #e9ecef;
        margin-bottom: 24px;
    }
    
    .sales-overview-title {
        font-size: 24px;
        font-weight: 500;
        color: #393a3d;
        margin: 0;
    }
    
    .sales-overview-header-actions {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .fullscreen-btn {
        background: none;
        border: none;
        color: #6b6c72;
        cursor: pointer;
        padding: 8px;
        border-radius: 4px;
        transition: all 0.2s ease;
    }
    
    .fullscreen-btn:hover {
        background: #f4f5f7;
        color: #393a3d;
    }
    
    .business-feed-section {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 24px;
    }
    
    .business-feed-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }
    
    .business-feed-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: #6b6c72;
    }
    
    .business-feed-card {
        background: #e8f4fc;
        border-radius: 8px;
        padding: 16px;
        border-left: 4px solid #0077c5;
    }
    
    .business-feed-card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 8px;
    }
    
    .business-feed-card-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        color: #393a3d;
    }
    
    .business-feed-card-text {
        color: #6b6c72;
        font-size: 14px;
        margin-bottom: 12px;
    }
    
    .business-feed-card-link {
        color: #0077c5;
        text-decoration: none;
        font-weight: 500;
    }
    
    .business-feed-card-link:hover {
        text-decoration: underline;
    }
    
    .create-actions-section {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }
    
    .create-actions-title {
        font-weight: 600;
        color: #393a3d;
        margin-right: 8px;
    }
    
    .create-action-btn {
        display: inline-block;
        padding: 8px 16px;
        border: 1px solid #e0e3e5;
        border-radius: 20px;
        color: #393a3d;
        text-decoration: none;
        font-size: 14px;
        background: #fff;
        transition: all 0.2s ease;
    }
    
    .create-action-btn:hover {
        background: #f4f5f7;
        border-color: #0077c5;
        color: #0077c5;
    }
    
    .show-all-link {
        color: #0077c5;
        text-decoration: none;
        font-weight: 500;
    }
    
    .show-all-link:hover {
        text-decoration: underline;
    }
    
    .glance-section {
        margin-bottom: 24px;
    }
    
    .glance-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }
    
    .glance-title {
        font-size: 18px;
        font-weight: 600;
        color: #393a3d;
    }
    
    .glance-actions {
        display: flex;
        gap: 8px;
    }
    
    .glance-action-btn {
        background: none;
        border: none;
        color: #6b6c72;
        cursor: pointer;
        padding: 4px;
    }
    
    .glance-action-btn:hover {
        color: #393a3d;
    }
    
    .widgets-grid {
        display: grid;
        gap: 24px;
    }
    
    .widget-card {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        overflow: hidden;
    }
    
    /* Sales Funnel Widget Overrides for Overview Page */
    .sales-funnel-widget .qbo-widget-card {
        border: none;
        box-shadow: none;
    }
    
    .sales-funnel-widget .qbo-funnel-container {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
    }
    
    .sales-funnel-widget .qbo-funnel-stage {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 16px;
        min-height: 140px;
    }
    
    .sales-funnel-widget .qbo-funnel-bar {
        height: 4px;
        border-radius: 2px;
        margin-bottom: 12px;
    }
    
    .sales-funnel-widget .qbo-funnel-bar-warning {
        background: linear-gradient(90deg, #ff8000 0%, #ff8000 100%);
    }
    
    .sales-funnel-widget .qbo-funnel-bar-success {
        background: linear-gradient(90deg, #2ca01c 0%, #2ca01c 100%);
    }
    
    .sales-funnel-widget .qbo-funnel-bar-primary {
        background: linear-gradient(90deg, #0d6534 0%, #0d6534 100%);
    }
    
    .sales-funnel-widget .qbo-funnel-label {
        font-size: 14px;
        color: #6b6c72;
        margin-bottom: 8px;
    }
    
    .sales-funnel-widget .qbo-funnel-amount {
        font-size: 24px;
        font-weight: 700;
        color: #393a3d;
        margin-bottom: 16px;
    }
    
    .sales-funnel-widget .qbo-funnel-count {
        font-size: 13px;
        color: #2ca01c;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .sales-funnel-widget .qbo-funnel-create {
        background: #fff;
        border: 1px solid #e9ecef;
    }
    
    .sales-funnel-widget .qbo-funnel-box {
        height: 100%;
    }
    
    .sales-funnel-widget .qbo-funnel-box-content {
        padding: 0;
    }
    
    .sales-funnel-widget .qbo-btn-dropdown {
        background: #fff;
        border: 1px solid #393a3d;
        border-radius: 4px;
        padding: 8px 12px;
        font-size: 13px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    
    .sales-funnel-widget .qbo-btn-dropdown:hover {
        background: #f4f5f7;
    }
    
    .view-details-link {
        color: #0077c5;
        text-decoration: none;
        font-size: 13px;
    }
    
    .view-details-link:hover {
        text-decoration: underline;
    }
</style>
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Sales & Get Paid') }}</li>
    <li class="breadcrumb-item">{{ __('Overview') }}</li>
@endsection

@section('content')
{{-- MY APPS Sidebar (Fixed Position) --}}
@include('partials.admin.allApps-subMenu-Sidebar', [
    'activeSection' => 'sales',
    'activeItem' => 'overview'
])

<div class="sales-overview-container">
    {{-- Fixed Page Header --}}
    <div class="sales-overview-header">
        <h1 class="sales-overview-title">{{ __('Sales & Get Paid overview') }}</h1>
        <div class="sales-overview-header-actions">
            <button class="fullscreen-btn" title="{{ __('Fullscreen') }}">
                <i class="ti ti-arrows-maximize"></i>
            </button>
        </div>
    </div>

    {{-- Business Feed Section --}}
    <div class="business-feed-section">
        <div class="business-feed-header">
            <div class="business-feed-title">
                <i class="ti ti-sparkles"></i>
                <span>{{ __('Business Feed') }}</span>
            </div>
            <a href="#" class="show-all-link">{{ __('View all') }}</a>
        </div>
        
        @php
            $overdueInvoices = \App\Models\Invoice::where('created_by', \Auth::user()->creatorId())
                ->where('status', 2)
                ->where('due_date', '<', now())
                ->sum('total_amount');
        @endphp
        
        <div class="business-feed-card">
            <div class="business-feed-card-header">
                <div class="business-feed-card-title">
                    <i class="ti ti-file-invoice"></i>
                    <span>{{ __('Overdue invoices') }}</span>
                </div>
                <button class="btn btn-link p-0 text-muted"><i class="ti ti-dots-vertical"></i></button>
            </div>
            <p class="business-feed-card-text">
                {{ __('Over') }} {{ \Auth::user()->priceFormat($overdueInvoices) }} {{ __('worth of invoice reminders are ready for you to review and send') }}
            </p>
            <a href="{{ route('invoice.index', ['status' => 2]) }}" class="business-feed-card-link">{{ __('Review all') }}</a>
        </div>
    </div>

    {{-- Create Actions Section --}}
    <div class="create-actions-section">
        <span class="create-actions-title">{{ __('Create actions') }}</span>
        <a href="#" class="create-action-btn">{{ __('Get paid online') }}</a>
        <a href="{{ route('invoice.create', 0) }}" class="create-action-btn">{{ __('Create invoice') }}</a>
        <a href="#" class="create-action-btn">{{ __('Create payment link') }}</a>
        <a href="{{ route('sales-receipt.create') }}" class="create-action-btn">{{ __('Create sales receipt') }}</a>
        <a href="#" class="create-action-btn">{{ __('Record payment') }}</a>
        <a href="#" class="show-all-link">{{ __('Show all') }}</a>
    </div>

    {{-- Sales & Get Paid at a Glance Section --}}
    <div class="glance-section">
        <div class="glance-header">
            <h2 class="glance-title">{{ __('Sales & Get Paid at a glance') }}</h2>
            <div class="glance-actions">
                <button class="glance-action-btn" title="{{ __('Settings') }}">
                    <i class="ti ti-adjustments-horizontal"></i>
                </button>
                <button class="glance-action-btn" title="{{ __('Hide') }}">
                    <i class="ti ti-eye-off"></i>
                </button>
            </div>
        </div>

        {{-- Widgets Grid --}}
        <div class="widgets-grid">
            {{-- Sales Funnel Widget --}}
            <div class="widget-card sales-funnel-widget">
                @include('dashboard.widgets.sales-funnel')
            </div>

            {{-- Income Over Time Widget --}}
            <div class="widget-card">
                @include('dashboard.widgets.income-over-time')
            </div>
        </div>
    </div>
</div>
@endsection
