{{-- Cash Flow Widget - QBO Style --}}
<div class="qbo-widget-card">
    <div class="qbo-widget-header">
        <div class="qbo-widget-title">
            <span>{{ __('CASH FLOW') }}</span>
        </div>
        <div class="qbo-widget-period">
            <span class="text-muted">{{ __('This year to date') }}</span>
            <i class="ti ti-chevron-down"></i>
        </div>
    </div>
    <div class="qbo-widget-body">
        <div class="qbo-cashflow-chart">
            <div id="cashflowWidgetChart"></div>
        </div>

        <div class="qbo-cashflow-legend">
            <div class="qbo-legend-item">
                <span class="qbo-legend-bar qbo-legend-income"></span>
                <span>{{ __('Money in') }}</span>
            </div>
            <div class="qbo-legend-item">
                <span class="qbo-legend-bar qbo-legend-expense"></span>
                <span>{{ __('Money out') }}</span>
            </div>
        </div>

        <div class="qbo-widget-footer">
            <a href="{{ route('report.profit.loss') }}" class="qbo-link">{{ __('View full report') }}</a>
            <button class="qbo-menu-btn"><i class="ti ti-dots-vertical"></i></button>
        </div>
    </div>
</div>

@push('script-page')
<script>
(function() {
    if(document.getElementById('cashflowWidgetChart')) {
        var cashflowData = {
            months: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            income: [4500, 3200, 5100, 4200, 4800, 5500, 4900, 3800, 5200, 4100, 4600, 5300],
            expense: [3800, 2800, 4200, 3500, 3900, 4500, 4100, 3200, 4300, 3600, 3900, 4400]
        };
        
        var options = {
            series: [{
                name: "{{ __('Money in') }}",
                data: cashflowData.income
            }, {
                name: "{{ __('Money out') }}",
                data: cashflowData.expense
            }],
            chart: {
                type: 'bar',
                height: 200,
                toolbar: { show: false },
                fontFamily: 'inherit'
            },
            colors: ['#2ca01c', '#e21b1b'],
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '60%',
                    borderRadius: 2
                }
            },
            dataLabels: { enabled: false },
            xaxis: {
                categories: cashflowData.months,
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: { style: { fontSize: '11px', colors: '#6c757d' } }
            },
            yaxis: {
                labels: {
                    formatter: function(val) {
                        return '$' + (val / 1000).toFixed(0) + 'K';
                    },
                    style: { fontSize: '11px', colors: '#6c757d' }
                }
            },
            grid: {
                borderColor: '#f1f1f1',
                strokeDashArray: 4
            },
            legend: { show: false },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return '$' + val.toLocaleString();
                    }
                }
            }
        };
        
        var chart = new ApexCharts(document.getElementById('cashflowWidgetChart'), options);
        chart.render();
    }
})();
</script>
@endpush
