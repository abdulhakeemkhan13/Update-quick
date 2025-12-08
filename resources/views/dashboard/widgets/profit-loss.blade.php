{{-- Profit & Loss Widget - QBO Style --}}
<div class="qbo-widget-card">
    <div class="qbo-widget-header">
        <div class="qbo-widget-title">
            <span>{{ __('PROFIT & LOSS') }}</span>
        </div>
        <div class="qbo-widget-period">
            <span class="text-muted">{{ __('Last month') }}</span>
            <i class="ti ti-chevron-down"></i>
        </div>
    </div>
    <div class="qbo-widget-body">
        @php
            $netProfit = \Auth::user()->incomeCurrentMonth() - \Auth::user()->expenseCurrentMonth();
            $lastMonthProfit = $netProfit; // Simplified
            $percentChange = $lastMonthProfit != 0 ? (($netProfit - $lastMonthProfit) / abs($lastMonthProfit)) * 100 : 0;
            $totalIncome = \Auth::user()->incomeCurrentMonth();
            $totalExpense = \Auth::user()->expenseCurrentMonth();
        @endphp
        
        <div class="qbo-profit-main">
            <div class="qbo-profit-label">{{ __('Net profit for') }} {{ now()->format('F') }}</div>
            <div class="qbo-profit-amount">
                {{ \Auth::user()->priceFormat($netProfit) }}
                <span class="qbo-badge qbo-badge-info">{{ number_format(min(($totalIncome > 0 ? ($netProfit / $totalIncome) * 100 : 0), 100), 0) }}%</span>
            </div>
            <div class="qbo-profit-change {{ $percentChange >= 0 ? 'text-success' : 'text-danger' }}">
                <i class="ti ti-arrow-{{ $percentChange >= 0 ? 'up' : 'down' }}"></i>
                {{ abs(number_format($percentChange, 0)) }}% {{ __('from prior month') }}
            </div>
        </div>

        <div class="qbo-profit-bars">
            <div class="qbo-profit-row">
                <div class="qbo-profit-row-label">
                    <span class="qbo-amount">{{ \Auth::user()->priceFormat($totalIncome) }}</span>
                </div>
                <div class="qbo-profit-row-bar">
                    <div class="qbo-bar qbo-bar-income" style="width: 100%;"></div>
                </div>
                <div class="qbo-profit-row-action">
                    <span class="qbo-review-link">{{ \Auth::user()->countInvoices() }} {{ __('to review') }}</span>
                </div>
            </div>
            <div class="qbo-profit-row-name">{{ __('Income') }}</div>
            
            <div class="qbo-profit-row">
                <div class="qbo-profit-row-label">
                    <span class="qbo-amount">{{ \Auth::user()->priceFormat($totalExpense) }}</span>
                </div>
                <div class="qbo-profit-row-bar">
                    <div class="qbo-bar qbo-bar-expense" style="width: {{ $totalIncome > 0 ? min(($totalExpense / $totalIncome) * 100, 100) : 0 }}%;"></div>
                </div>
                <div class="qbo-profit-row-action">
                    <span class="qbo-review-link">{{ \Auth::user()->countBills() }} {{ __('to review') }}</span>
                </div>
            </div>
            <div class="qbo-profit-row-name">{{ __('Expenses') }}</div>
        </div>

        <div class="qbo-widget-footer">
            <a href="{{ route('report.profit.loss') }}" class="qbo-link">{{ __('Categorize transactions') }}</a>
            <button class="qbo-menu-btn"><i class="ti ti-dots-vertical"></i></button>
        </div>
    </div>
</div>
