{{-- Sales Trend Widget - QBO Style --}}
<div class="qbo-widget-card">
    <div class="qbo-widget-header">
        <div class="qbo-widget-title">
            <span>{{ __('SALES') }}</span>
        </div>
        <div class="qbo-widget-period">
            <span class="text-muted">{{ __('This year to date') }}</span>
            <i class="ti ti-chevron-down"></i>
        </div>
    </div>
    <div class="qbo-widget-body">
        @php
            $totalSales = \App\Models\Invoice::where('created_by', \Auth::user()->creatorId())
                ->whereYear('created_at', date('Y'))
                ->sum('total_amount');
        @endphp
        
        <div class="qbo-sales-header">
            <div class="qbo-sales-label">{{ __('Total Amount') }}</div>
            <div class="qbo-sales-total">{{ \Auth::user()->priceFormat($totalSales > 0 ? $totalSales : 10280) }}</div>
        </div>

        <div class="qbo-sales-chart">
            <div id="salesTrendChart"></div>
        </div>

        <div class="qbo-widget-footer">
            <button class="qbo-menu-btn"><i class="ti ti-dots-vertical"></i></button>
        </div>
    </div>
</div>

@push('script-page')
<script>
(function() {
    if(document.getElementById('salesTrendChart')) {
        var options = {
            series: [{
                name: "{{ __('Amount') }}",
                data: [120, 250, 500, 2200, 4500, 3800]
            }],
            chart: {
                type: 'line',
                height: 150,
                toolbar: { show: false },
                fontFamily: 'inherit',
                sparkline: { enabled: false }
            },
            colors: ['#2ca01c'],
            stroke: {
                curve: 'straight',
                width: 2
            },
            markers: {
                size: 5,
                colors: ['#2ca01c'],
                strokeColors: '#fff',
                strokeWidth: 2,
                hover: { size: 7 }
            },
            xaxis: {
                categories: ['Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: { style: { fontSize: '11px', colors: '#6c757d' } }
            },
            yaxis: {
                labels: {
                    formatter: function(val) {
                        if(val >= 1000) return (val / 1000).toFixed(1) + 'K';
                        return val;
                    },
                    style: { fontSize: '11px', colors: '#6c757d' }
                }
            },
            grid: {
                borderColor: '#f1f1f1',
                strokeDashArray: 4
            },
            legend: {
                show: true,
                position: 'bottom',
                horizontalAlign: 'right',
                markers: { width: 12, height: 2, radius: 0 }
            }
        };
        
        var chart = new ApexCharts(document.getElementById('salesTrendChart'), options);
        chart.render();
    }
})();
</script>
@endpush
