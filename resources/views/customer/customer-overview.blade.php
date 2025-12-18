@extends('layouts.admin')

@section('page-title')
    {{ __('Customer Hub Overview') }}
@endsection

@push('css-page')
<style>
.customer-overview-container { padding: 30px; }
.customer-overview-header {
    position: sticky; top: 0; z-index: 100; background: #fff;
    display: flex; justify-content: space-between; align-items: center;
    padding: 16px 0; margin: 0 -24px; padding-left: 24px; padding-right: 24px;
    border-bottom: 1px solid #e9ecef; margin-bottom: 24px;
}
.customer-overview-title { font-size: 24px; font-weight: 500; color: #393a3d; margin: 0; }
.header-actions { display: flex; align-items: center; gap: 12px; }
.fullscreen-btn { background: none; border: none; color: #6b6c72; cursor: pointer; padding: 8px; border-radius: 4px; }
.fullscreen-btn:hover { background: #f4f5f7; color: #393a3d; }

.glance-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.glance-title { font-size: 14px; font-weight: 600; color: #393a3d; }
.feedback-btn { display: flex; align-items: center; gap: 6px; color: #0077c5; background: none; border: none; font-size: 14px; cursor: pointer; }
.feedback-btn:hover { text-decoration: underline; }

.widgets-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
.widget-card { background: #fff; border: 1px solid #e0e3e5; border-radius: 8px; padding: 20px; }
.widget-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.widget-title { font-size: 11px; font-weight: 600; color: #6b6c72; text-transform: uppercase; letter-spacing: 0.5px; }
.widget-period { font-size: 13px; color: #6b6c72; }

.total-label { font-size: 13px; color: #6b6c72; margin-bottom: 4px; }
.total-amount { font-size: 28px; font-weight: 700; color: #393a3d; margin-bottom: 16px; }

.invoice-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
.invoice-table th { font-size: 11px; color: #6b6c72; text-transform: uppercase; padding: 8px 12px; text-align: left; border-bottom: 1px solid #e0e3e5; }
.invoice-table td { padding: 10px 12px; font-size: 13px; border-bottom: 1px solid #f4f5f7; }
.invoice-table td:last-child { text-align: right; }
.client-link { color: #0077c5; text-decoration: none; }
.client-link:hover { text-decoration: underline; }
.view-link { color: #0077c5; text-decoration: none; font-size: 13px; font-weight: 500; }

.empty-state { padding: 20px 0 30px 0; }
.empty-title { font-size: 18px; font-weight: 600; color: #393a3d; margin-bottom: 8px; }
.empty-text { font-size: 13px; color: #6b6c72; margin-bottom: 0; }
.create-estimate-btn { display: block; width: 100%; padding: 10px 20px; border: 1px solid #393a3d; border-radius: 4px; color: #393a3d; text-decoration: none; font-size: 13px; background: #fff; text-align: center; margin-top: auto; }
.create-estimate-btn:hover { background: #f4f5f7; color: #393a3d; }
.widget-card.estimates-widget { display: flex; flex-direction: column; min-height: 280px; }
.estimates-content { flex: 1; }

.tasks-widget { grid-column: 1; }
.shortcuts-widget { grid-column: 2; }

.tasks-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.tasks-dropdown { display: flex; align-items: center; gap: 4px; font-size: 13px; color: #393a3d; cursor: pointer; }
.task-table { width: 100%; border-collapse: collapse; }
.task-table th { font-size: 12px; font-weight: 600; color: #393a3d; padding: 8px 12px; text-align: left; border-bottom: 1px solid #e0e3e5; }
.task-empty { text-align: center; padding: 40px 20px; }
.task-empty-icon { color: #d52b1e; font-size: 24px; margin-bottom: 12px; }
.task-empty-text { font-size: 14px; color: #393a3d; margin-bottom: 8px; }
.refresh-btn { color: #0077c5; background: none; border: none; font-size: 13px; cursor: pointer; }
.show-all-link { color: #0077c5; text-decoration: none; font-size: 13px; }

.shortcuts-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 16px; }
.shortcut-item { display: flex; flex-direction: column; align-items: center; text-decoration: none; cursor: pointer; }
.shortcut-icon { width: 68px; height: 68px; margin-bottom: 8px; }
.shortcut-label { font-size: 12px; color: #393a3d; text-align: center; }

@media (max-width: 992px) {
    .widgets-grid { grid-template-columns: 1fr; }
    .tasks-widget, .shortcuts-widget { grid-column: 1; }
}
</style>
@endpush

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
<li class="breadcrumb-item">{{ __('Customer Hub') }}</li>
<li class="breadcrumb-item">{{ __('Overview') }}</li>
@endsection

@section('content')
@include('partials.admin.allApps-subMenu-Sidebar', ['activeSection' => 'customers', 'activeItem' => 'overview'])

<div class="customer-overview-container">
    <div class="customer-overview-header">
        <h1 class="customer-overview-title">{{ __('Customer Hub overview') }}</h1>
        <div class="header-actions">
            <button class="fullscreen-btn" title="{{ __('Fullscreen') }}">
                <i class="ti ti-arrows-maximize"></i>
            </button>
        </div>
    </div>

    <div class="glance-header">
        <span class="glance-title">{{ __('Customers at a glance') }}</span>
        <button class="feedback-btn">
            <i class="ti ti-message-check"></i>
            {{ __('Give us feedback') }}
        </button>
    </div>

    <div class="widgets-grid">
        {{-- Overdue Invoices Widget --}}
        <div class="widget-card">
            <div class="widget-header">
                <span class="widget-title">{{ __('OVERDUE INVOICES') }}</span>
                <span class="widget-period">{{ __('As of today') }}</span>
            </div>
            @php
                $overdueInvoices = \App\Models\Invoice::where('created_by', \Auth::user()->creatorId())
                    ->where('due_date', '<', now())
                    ->whereIn('status', [1, 2, 3])
                    ->orderBy('due_date', 'asc')
                    ->take(5)
                    ->get();
                $overdueTotal = $overdueInvoices->sum('total_amount');
            @endphp
            <div class="total-label">{{ __('Total of overdue invoices') }}</div>
            <div class="total-amount">{{ \Auth::user()->priceFormat($overdueTotal) }}</div>
            
            @if($overdueInvoices->count() > 0)
            <table class="invoice-table">
                <thead>
                    <tr><th>{{ __('CLIENT') }}</th><th>{{ __('DATE') }}</th><th>{{ __('AMOUNT') }}</th></tr>
                </thead>
                <tbody>
                    @foreach($overdueInvoices as $invoice)
                    <tr>
                        <td><a href="{{ route('customer.show', $invoice->customer_id) }}" class="client-link">{{ $invoice->customer->name ?? 'N/A' }}</a></td>
                        <td>{{ \Carbon\Carbon::parse($invoice->due_date)->format('m/d/y') }}</td>
                        <td>{{ \Auth::user()->priceFormat($invoice->total_amount) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
            <a href="{{ route('invoice.index', ['status' => 2]) }}" class="view-link">{{ __('View invoices') }}</a>
        </div>

        {{-- Open Estimates Widget --}}
        <div class="widget-card estimates-widget">
            <div class="widget-header">
                <span class="widget-title">{{ __('OPEN ESTIMATES') }}</span>
            </div>
            @php
                $openEstimates = \App\Models\Proposal::where('created_by', \Auth::user()->creatorId())
                    ->where('status', 0)
                    ->count();
            @endphp
            <div class="estimates-content">
                @if($openEstimates > 0)
                    <div class="total-label">{{ __('You have') }} {{ $openEstimates }} {{ __('open estimates') }}</div>
                @else
                    <div class="empty-state">
                        <div class="empty-title">{{ __('You have no open estimates.') }}</div>
                        <div class="empty-text">{{ __('Create an estimate to win more jobs!') }}</div>
                    </div>
                @endif
            </div>
            <a href="{{ route('proposal.create', 0) }}" class="create-estimate-btn">{{ __('Create an estimate') }}</a>
        </div>

        {{-- Tasks Widget --}}
        <div class="widget-card tasks-widget">
            <div class="tasks-header">
                <span class="widget-title">{{ __('TASKS') }}</span>
                <div class="tasks-dropdown">
                    <span>{{ __('All open tasks') }}</span>
                    <i class="ti ti-chevron-down"></i>
                </div>
            </div>
            <table class="task-table">
                <thead>
                    <tr>
                        <th>{{ __('Task') }} <i class="ti ti-arrows-sort"></i></th>
                        <th>{{ __('Assigned To') }}</th>
                        <th>{{ __('Due Date') }} <i class="ti ti-arrow-up"></i></th>
                    </tr>
                </thead>
            </table>
            <div class="task-empty">
                <div class="task-empty-icon"><i class="ti ti-alert-triangle"></i></div>
                <div class="task-empty-text">{{ __("We couldn't load your tasks") }}</div>
                <button class="refresh-btn">{{ __('Refresh to retry') }}</button>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px;">
                <a href="#" class="show-all-link">{{ __('Show all') }}</a>
                <i class="ti ti-dots-vertical" style="color: #6b6c72; cursor: pointer;"></i>
            </div>
        </div>

        {{-- Shortcuts Widget --}}
        <div class="widget-card shortcuts-widget">
            <span class="widget-title">{{ __('SHORTCUTS') }}</span>
            <div class="shortcuts-grid">
                <a href="{{ route('customer.create') }}" class="shortcut-item">
                    <svg class="shortcut-icon" viewBox="0 0 84 83" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="42" cy="41" r="33.25" fill="#ECEEF1" stroke="#D4D7DC" stroke-width="1.5"/>
                        <circle cx="60" cy="67" r="8" fill="#53B700"/>
                        <path d="M60 63.17V70.83" stroke="white" stroke-width="1.54" stroke-linecap="round"/>
                        <path d="M63.83 67H56.17" stroke="white" stroke-width="1.54" stroke-linecap="round"/>
                        <path d="M42 35C45.87 35 49 31.87 49 28C49 24.13 45.87 21 42 21C38.13 21 35 24.13 35 28C35 31.87 38.13 35 42 35Z" fill="white" stroke="#008481" stroke-width="1.5"/>
                        <path d="M52 52C52 45.37 47.52 40 42 40C36.48 40 32 45.37 32 52" fill="white"/>
                        <path d="M52 52C52 45.37 47.52 40 42 40C36.48 40 32 45.37 32 52" stroke="#008481" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    <span class="shortcut-label">{{ __('New Customer') }}</span>
                </a>
                <a href="#" class="shortcut-item">
                    <svg class="shortcut-icon" viewBox="0 0 84 83" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="42" cy="41" r="33.25" fill="#ECEEF1" stroke="#D4D7DC" stroke-width="1.5"/>
                        <circle cx="60" cy="67" r="8" fill="#53B700"/>
                        <path d="M60 63.17V70.83" stroke="white" stroke-width="1.54" stroke-linecap="round"/>
                        <path d="M63.83 67H56.17" stroke="white" stroke-width="1.54" stroke-linecap="round"/>
                        <path d="M35 30C38 30 41 27.5 41 24C35 24 32 27 32 30C32 30.5 32 31 35 30Z" fill="white" stroke="#008481" stroke-width="1.5"/>
                        <path d="M49 30C46 30 43 27.5 43 24C49 24 52 27 52 30C52 30.5 52 31 49 30Z" fill="white" stroke="#008481" stroke-width="1.5"/>
                        <path d="M32 50C32 43 36 38 42 38C48 38 52 43 52 50" fill="white"/>
                        <path d="M32 50C32 43 36 38 42 38C48 38 52 43 52 50" stroke="#008481" stroke-width="1.5"/>
                    </svg>
                    <span class="shortcut-label">{{ __('Import Customers') }}</span>
                </a>
                <a href="{{ route('invoice.create', 0) }}" class="shortcut-item">
                    <svg class="shortcut-icon" viewBox="0 0 84 83" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="42" cy="41" r="33.25" fill="#ECEEF1" stroke="#D4D7DC" stroke-width="1.5"/>
                        <circle cx="60" cy="67" r="8" fill="#53B700"/>
                        <path d="M60 63.17V70.83" stroke="white" stroke-width="1.54" stroke-linecap="round"/>
                        <path d="M63.83 67H56.17" stroke="white" stroke-width="1.54" stroke-linecap="round"/>
                        <rect x="30" y="26" width="24" height="32" rx="2" fill="white" stroke="#008481" stroke-width="1.5"/>
                        <path d="M35 34H49" stroke="#00C1BF" stroke-width="1"/>
                        <path d="M35 38H49" stroke="#00C1BF" stroke-width="1"/>
                        <path d="M35 42H45" stroke="#00C1BF" stroke-width="1"/>
                        <path d="M35 50H42" stroke="#57B520" stroke-width="1"/>
                    </svg>
                    <span class="shortcut-label">{{ __('Create Invoice') }}</span>
                </a>
                <a href="{{ route('customer.index') }}" class="shortcut-item">
                    <svg class="shortcut-icon" viewBox="0 0 84 83" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="42" cy="41" r="33.25" fill="#ECEEF1" stroke="#D4D7DC" stroke-width="1.5"/>
                        <rect x="30" y="26" width="24" height="32" rx="2" fill="white" stroke="#008481" stroke-width="1.5"/>
                        <rect x="34" y="32" width="16" height="2" rx="1" fill="#00C1BF"/>
                        <rect x="34" y="38" width="16" height="2" rx="1" fill="#00C1BF"/>
                        <rect x="34" y="44" width="10" height="2" rx="1" fill="#00C1BF"/>
                        <rect x="34" y="50" width="8" height="2" rx="1" fill="#57B520"/>
                    </svg>
                    <span class="shortcut-label">{{ __('View Customers') }}</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
