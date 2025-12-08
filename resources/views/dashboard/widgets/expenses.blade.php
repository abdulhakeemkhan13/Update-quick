{{-- Expenses Widget - QBO Style with Donut Chart --}}
<div class="qbo-widget-card">
    <div class="qbo-widget-header">
        <div class="qbo-widget-title">
            <span>{{ __('EXPENSES') }}</span>
        </div>
        <div class="qbo-widget-period">
            <span class="text-muted">{{ __('Last 30 days') }}</span>
            <i class="ti ti-chevron-down"></i>
        </div>
    </div>
    <div class="qbo-widget-body">
        @php
            $totalExpense = \Auth::user()->expenseCurrentMonth();
            $lastMonthExpense = $totalExpense; // Simplified
            $percentChange = $lastMonthExpense != 0 ? (($totalExpense - $lastMonthExpense) / abs($lastMonthExpense)) * 100 : 0;
        @endphp
        
        <div class="qbo-expense-header">
            <div class="qbo-expense-label">{{ __('Spending for last 30 days') }}</div>
            <div class="qbo-expense-amount">
                {{ \Auth::user()->priceFormat($totalExpense) }}
                <span class="qbo-badge qbo-badge-info">61%</span>
            </div>
            <div class="qbo-expense-change {{ $percentChange >= 0 ? 'text-danger' : 'text-success' }}">
                <i class="ti ti-arrow-{{ $percentChange >= 0 ? 'down' : 'up' }}"></i>
                {{ __('Down') }} {{ abs(number_format($percentChange, 0)) }}% {{ __('from prior 30 days') }}
            </div>
        </div>

        <div class="qbo-expense-chart-container">
            <div id="expenseDonutChart-{{ uniqid() }}" class="qbo-donut-chart"></div>
            <div class="qbo-expense-legend">
                @foreach($expenseCategory ?? [] as $index => $category)
                    <div class="qbo-legend-item">
                        <span class="qbo-legend-dot" style="background-color: {{ $expenseCategoryColor[$index] ?? '#6c757d' }};"></span>
                        <span class="qbo-legend-text">{{ Str::limit($category, 18) }}</span>
                    </div>
                @endforeach
                @if(empty($expenseCategory))
                    <div class="qbo-legend-item">
                        <span class="qbo-legend-dot" style="background-color: #28a745;"></span>
                        <span class="qbo-legend-text">{{ __('Cost of Goods Sold') }}</span>
                    </div>
                    <div class="qbo-legend-item">
                        <span class="qbo-legend-dot" style="background-color: #17a2b8;"></span>
                        <span class="qbo-legend-text">{{ __('Legal & Professional...') }}</span>
                    </div>
                    <div class="qbo-legend-item">
                        <span class="qbo-legend-dot" style="background-color: #ffc107;"></span>
                        <span class="qbo-legend-text">{{ __('Other') }}</span>
                    </div>
                @endif
                <div class="qbo-legend-item">
                    <span class="qbo-legend-text text-muted">{{ \Auth::user()->countBills() }} {{ __('to review') }}</span>
                </div>
            </div>
        </div>

        <div class="qbo-widget-footer">
            <a href="{{ route('payment.index') }}" class="qbo-link">{{ __('Categorize transactions') }}</a>
            <button class="qbo-menu-btn"><i class="ti ti-dots-vertical"></i></button>
        </div>
    </div>
</div>
