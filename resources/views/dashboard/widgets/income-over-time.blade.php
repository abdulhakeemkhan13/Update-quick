{{-- Income Over Time Widget - QBO Style --}}
<div class="qbo-widget-card income-over-time-widget">
    <div class="qbo-widget-header">
        <div class="qbo-widget-title d-flex align-items-center gap-2">
            <span>{{ __('INCOME OVER TIME') }}</span>
            <i class="ti ti-info-circle text-muted" data-bs-toggle="tooltip" title="{{ __('Shows your income trends over the selected period') }}"></i>
        </div>
        <div class="qbo-widget-controls d-flex align-items-center gap-3">
            <span class="text-muted">{{ __('Duration:') }}</span>
            <div class="qbo-widget-period">
                <span>{{ __('This month') }}</span>
                <i class="ti ti-chevron-down"></i>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small">{{ __('Compare to previous year:') }}</span>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="compareToggle">
                </div>
            </div>
        </div>
    </div>
    <div class="qbo-widget-body">
        @php
            // Calculate income data
            $startOfMonth = now()->startOfMonth();
            $endOfMonth = now()->endOfMonth();
            $lastMonth = now()->subMonth();
            
            $invoices = \App\Models\Invoice::where('created_by', \Auth::user()->creatorId());
            
            // This month's income (paid invoices)
            $thisMonthIncome = $invoices->clone()
                ->whereIn('status', [3, 4]) // Paid statuses
                ->whereBetween('issue_date', [$startOfMonth, $endOfMonth])
                ->sum('total_amount');
            
            // Last month's income for comparison
            $lastMonthIncome = $invoices->clone()
                ->whereIn('status', [3, 4])
                ->whereBetween('issue_date', [$lastMonth->startOfMonth(), $lastMonth->endOfMonth()])
                ->sum('total_amount');
            
            // Previous year same month
            $prevYearIncome = $invoices->clone()
                ->whereIn('status', [3, 4])
                ->whereBetween('issue_date', [now()->subYear()->startOfMonth(), now()->subYear()->endOfMonth()])
                ->sum('total_amount');
            
            $difference = $thisMonthIncome - $prevYearIncome;
            $isPositive = $difference >= 0;
        @endphp
        
        <div class="income-summary mb-3">
            <div class="text-muted small">{{ __('Data updated') }} {{ now()->diffForHumans() }}</div>
            <div class="income-amount mt-2">
                <span class="h2 fw-bold mb-0">{{ \Auth::user()->priceFormat($thisMonthIncome) }}</span>
                <span class="text-muted ms-2">{{ __('This month') }}</span>
            </div>
            <div class="income-comparison mt-1">
                <span class="{{ $isPositive ? 'text-success' : 'text-danger' }}">
                    <i class="ti ti-arrow-{{ $isPositive ? 'up' : 'down' }}"></i>
                    {{ \Auth::user()->priceFormat(abs($difference)) }}
                </span>
                <span class="text-{{ $isPositive ? 'success' : 'danger' }}">
                    {{ $isPositive ? __('more') : __('less') }} {{ __('than') }} {{ now()->subYear()->format('M, Y') }}
                </span>
            </div>
        </div>
        
        {{-- Chart Area --}}
        <div class="income-chart-container">
            <div class="income-chart" id="incomeOverTimeChart"></div>
            <div class="chart-y-axis">
                <div class="y-label">$1.00</div>
                <div class="y-label">$0.80</div>
                <div class="y-label">$0.60</div>
                <div class="y-label">$0.40</div>
                <div class="y-label">$0.20</div>
                <div class="y-label">$0.00</div>
            </div>
            <div class="chart-grid">
                <div class="grid-line"></div>
                <div class="grid-line"></div>
                <div class="grid-line"></div>
                <div class="grid-line"></div>
                <div class="grid-line"></div>
                <div class="grid-line"></div>
            </div>
        </div>
    </div>
</div>

<style>
.income-over-time-widget {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
}

.income-over-time-widget .qbo-widget-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    flex-wrap: wrap;
    gap: 12px;
}

.income-over-time-widget .qbo-widget-title {
    font-size: 12px;
    font-weight: 600;
    color: #393a3d;
    letter-spacing: 0.5px;
}

.income-over-time-widget .qbo-widget-period {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    cursor: pointer;
    color: #393a3d;
}

.income-over-time-widget .qbo-widget-period:hover {
    color: #0077c5;
}

.income-amount .h2 {
    font-size: 28px;
    color: #393a3d;
}

.income-comparison {
    font-size: 14px;
}

.income-chart-container {
    position: relative;
    height: 180px;
    margin-top: 20px;
}

.chart-y-axis {
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 50px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 0 8px 0 0;
}

.y-label {
    font-size: 11px;
    color: #6b6c72;
    text-align: right;
}

.chart-grid {
    position: absolute;
    left: 55px;
    right: 0;
    top: 0;
    bottom: 0;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.grid-line {
    height: 1px;
    background: #e9ecef;
    width: 100%;
}

.income-chart {
    position: absolute;
    left: 55px;
    right: 0;
    top: 0;
    bottom: 0;
}

.form-check-input:checked {
    background-color: #0077c5;
    border-color: #0077c5;
}
</style>
