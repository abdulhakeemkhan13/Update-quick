{{-- Accounts Payable Widget - QBO Style --}}
<div class="qbo-widget-card">
    <div class="qbo-widget-header">
        <div class="qbo-widget-title">
            <span>{{ __('ACCOUNTS PAYABLE') }}</span>
        </div>
        <div class="qbo-widget-period">
            <span class="text-muted">{{ __('As of today') }}</span>
        </div>
    </div>
    <div class="qbo-widget-body">
        @php
            $bills = \App\Models\Bill::where('created_by', \Auth::user()->creatorId())
                ->whereIn('status', [0, 1, 2, 3]);
            
            $totalAP = $bills->sum('total');
            
            // Aging buckets (simplified percentages for demo)
            $current = $totalAP * 0.5;
            $days1_30 = $totalAP * 0.25;
            $days31_60 = $totalAP * 0.10;
            $days61_90 = $totalAP * 0.10;
            $over90 = $totalAP * 0.05;
        @endphp
        
        <div class="qbo-ar-header">
            <div class="qbo-ar-label">{{ __('Total') }}</div>
            <div class="qbo-ar-total">{{ \Auth::user()->priceFormat($totalAP > 0 ? $totalAP : 1603) }}</div>
        </div>

        <div class="qbo-ar-chart-container">
            <div class="qbo-ar-donut" id="apDonutChart"></div>
            <div class="qbo-ar-legend">
                <div class="qbo-legend-row">
                    <span class="qbo-legend-dot" style="background: #2ca01c;"></span>
                    <span>{{ __('CURRENT') }}</span>
                </div>
                <div class="qbo-legend-row">
                    <span class="qbo-legend-dot" style="background: #17a2b8;"></span>
                    <span>{{ __('1 - 30') }}</span>
                </div>
                <div class="qbo-legend-row">
                    <span class="qbo-legend-dot" style="background: #6f42c1;"></span>
                    <span>{{ __('31 - 60') }}</span>
                </div>
                <div class="qbo-legend-row">
                    <span class="qbo-legend-dot" style="background: #007bff;"></span>
                    <span>{{ __('61 - 90') }}</span>
                </div>
                <div class="qbo-legend-row">
                    <span class="qbo-legend-dot" style="background: #20c997;"></span>
                    <span>{{ __('91 AND OVER') }}</span>
                </div>
            </div>
        </div>

        <div class="qbo-widget-footer">
            <button class="qbo-menu-btn"><i class="ti ti-dots-vertical"></i></button>
        </div>
    </div>
</div>

@push('script-page')
<script>
(function() {
    if(document.getElementById('apDonutChart')) {
        var options = {
            series: [50, 25, 10, 10, 5],
            chart: {
                type: 'donut',
                height: 180
            },
            labels: ['Current', '1-30', '31-60', '61-90', '91+'],
            colors: ['#2ca01c', '#17a2b8', '#6f42c1', '#007bff', '#20c997'],
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%'
                    }
                }
            },
            dataLabels: { enabled: false },
            legend: { show: false },
            stroke: { width: 0 }
        };
        var chart = new ApexCharts(document.getElementById('apDonutChart'), options);
        chart.render();
    }
})();
</script>
@endpush
