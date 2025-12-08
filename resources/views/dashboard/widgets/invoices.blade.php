{{-- Invoices Widget - QBO Style --}}
<div class="qbo-widget-card">
    <div class="qbo-widget-header">
        <div class="qbo-widget-title">
            <span>{{ __('INVOICES') }}</span>
        </div>
        <div class="qbo-widget-period">
            <span class="text-muted">{{ __('Last 365 days') }}</span>
        </div>
    </div>
    <div class="qbo-widget-body">
        @php
            $invoices = \App\Models\Invoice::where('created_by', \Auth::user()->creatorId());
            $totalUnpaid = $invoices->clone()->whereIn('status', [0, 1, 2])->sum('total_amount');
            $overdueCount = $invoices->clone()->where('status', 2)->count();
            $overdueAmount = $invoices->clone()->where('status', 2)->sum('total_amount');
            $notDueAmount = $invoices->clone()->whereIn('status', [0, 1])->sum('total_amount');
            $paidLast30 = $invoices->clone()->where('status', 4)->where('updated_at', '>=', now()->subDays(30))->sum('total_amount');
            $notDeposited = $invoices->clone()->where('status', 3)->sum('total_amount');
            $deposited = $paidLast30;
        @endphp
        
        <div class="qbo-invoice-header">
            <span class="qbo-invoice-unpaid">{{ \Auth::user()->priceFormat($totalUnpaid) }} {{ __('Unpaid') }}</span>
        </div>

        <div class="qbo-invoice-stats">
            <div class="qbo-invoice-stat-row">
                <div class="qbo-invoice-stat">
                    <div class="qbo-invoice-stat-amount text-danger">{{ \Auth::user()->priceFormat($overdueAmount) }}</div>
                    <div class="qbo-invoice-stat-label">{{ __('Overdue') }}</div>
                </div>
                <div class="qbo-invoice-stat">
                    <div class="qbo-invoice-stat-amount">{{ \Auth::user()->priceFormat($notDueAmount) }}</div>
                    <div class="qbo-invoice-stat-label">{{ __('Not due yet') }}</div>
                </div>
            </div>
            <div class="qbo-invoice-progress">
                <div class="qbo-progress-bar">
                    <div class="qbo-progress-segment qbo-progress-danger" style="width: {{ $totalUnpaid > 0 ? ($overdueAmount / $totalUnpaid) * 100 : 0 }}%;"></div>
                    <div class="qbo-progress-segment qbo-progress-success" style="width: {{ $totalUnpaid > 0 ? ($notDueAmount / $totalUnpaid) * 100 : 0 }}%;"></div>
                </div>
            </div>
        </div>

        <div class="qbo-invoice-paid-section">
            <div class="qbo-invoice-paid-header">
                <span>{{ \Auth::user()->priceFormat($paidLast30) }} {{ __('Paid') }}</span>
                <span class="text-muted">{{ __('Last 30 days') }}</span>
            </div>
            <div class="qbo-invoice-stat-row">
                <div class="qbo-invoice-stat">
                    <div class="qbo-invoice-stat-amount">{{ \Auth::user()->priceFormat($notDeposited) }}</div>
                    <div class="qbo-invoice-stat-label">{{ __('Not deposited') }}</div>
                </div>
                <div class="qbo-invoice-stat">
                    <div class="qbo-invoice-stat-amount text-success">{{ \Auth::user()->priceFormat($deposited) }}</div>
                    <div class="qbo-invoice-stat-label">{{ __('Deposited') }}</div>
                </div>
            </div>
            <div class="qbo-invoice-progress">
                <div class="qbo-progress-bar">
                    <div class="qbo-progress-segment qbo-progress-warning" style="width: {{ ($paidLast30 + $notDeposited) > 0 ? ($notDeposited / ($paidLast30 + $notDeposited)) * 100 : 0 }}%;"></div>
                    <div class="qbo-progress-segment qbo-progress-success" style="width: {{ ($paidLast30 + $notDeposited) > 0 ? ($deposited / ($paidLast30 + $notDeposited)) * 100 : 0 }}%;"></div>
                </div>
            </div>
        </div>

        <div class="qbo-widget-footer">
            <button class="qbo-menu-btn"><i class="ti ti-dots-vertical"></i></button>
        </div>
    </div>
</div>
