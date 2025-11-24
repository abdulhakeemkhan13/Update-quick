@extends('layouts.admin')
@section('page-title')
    {{ __('Dashboard') }}
@endsection
@php
    $seriesWeak = [$weeklyBill['billTotal'], $weeklyBill['billPaid'], $weeklyBill['billDue']];
    $seriesMonth = [$monthlyBill['billTotal'], $monthlyBill['billPaid'], $monthlyBill['billDue']];
@endphp
@push('script-page')
    <script>
        @if (\Auth::user()->can('show account dashboard'))
            (function() {
                var chartBarOptions = {
                    series: [{
                            name: "{{ __('Income') }}",
                            data: {!! json_encode($incExpLineChartData['income']) !!}
                        },
                        {
                            name: "{{ __('Expense') }}",
                            data: {!! json_encode($incExpLineChartData['expense']) !!}
                        }
                    ],

                    chart: {
                        height: 250,
                        type: 'area',
                        // type: 'line',
                        // dropShadow: {
                        //     enabled: true,
                        //     color: '#000',
                        //     top: 18,
                        //     left: 7,
                        //     blur: 10,
                        //     opacity: 0.2
                        // },
                        toolbar: {
                            show: false
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        width: 2,
                        curve: 'smooth'
                    },
                    title: {
                        text: '',
                        align: 'left'
                    },
                    xaxis: {
                        categories: {!! json_encode($incExpLineChartData['day']) !!},
                        title: {
                            text: '{{ __('Date') }}'
                        }
                    },
                    colors: [usedColor, usedColorDarker],


                    grid: {
                        strokeDashArray: 4,
                    },
                    legend: {
                        show: false,
                    },
                    // markers: {
                    //     size: 4,
                    //     colors: ['#6fd944', '#FF3A6E'],
                    //     opacity: 0.9,
                    //     strokeWidth: 2,
                    //     hover: {
                    //         size: 7,
                    //     }
                    // },
                    yaxis: {
                        title: {
                            text: '{{ __('Amount') }}'
                        },

                    }

                };
                var arChart = new ApexCharts(document.querySelector("#cash-flow"), chartBarOptions);
                arChart.render();
            })();

            (function() {
                var options = {
                    chart: {
                        height: 300,
                        type: 'bar',
                        toolbar: {
                            show: false,
                        },
                    },
                    dataLabels: {
                        enabled: false
                    },
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '45%',
                            borderRadius: 7,
                            endingShape: 'rounded',
                            borderRadiusApplication: 'around',
                        },
                    },
                    stroke: {
                        show: true,
                        width: 5,
                        curve: 'smooth',
                        colors: ['transparent']
                    },
                    series: [{
                        name: "{{ __('Income') }}",
                        data: {!! json_encode($incExpBarChartData['income']) !!}
                    }, {
                        name: "{{ __('Expense') }}",
                        data: {!! json_encode($incExpBarChartData['expense']) !!}
                    }],
                    xaxis: {
                        categories: {!! json_encode($incExpBarChartData['month']) !!},
                        labels: {
                            style: {
                                colors: '#6c757d',
                                fontSize: '12px',
                            },
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: '#6c757d',
                                fontSize: '12px',
                            },
                        },
                        min: 0,
                    },
                    colors: [usedColor, usedColorDarker],
                    fill: {
                        type: 'solid',
                    },
                    grid: {
                        strokeDashArray: 4,
                        // borderColor: '#e9ecef'
                    },
                    legend: {
                        show: true,
                        position: 'top',
                        horizontalAlign: 'right',
                        labels: {
                            colors: '#495057',
                            useSeriesColors: false
                        }
                    },
                    // markers: {
                    //     size: 4,
                    //     colors:  ['#3ec9d6', '#FF3A6E',],
                    //     opacity: 0.9,
                    //     strokeWidth: 2,
                    //     hover: {
                    //         size: 7,
                    //     }
                    // }
                };
                var chart = new ApexCharts(document.querySelector("#incExpBarChart"), options);
                chart.render();
            })();

            function initExpenseChart() {
                var options = {
                    chart: {
                        height: 250,
                        type: 'donut',
                    },
                    dataLabels: {
                        enabled: false,
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '70%',
                            }
                        }
                    },
                    fill: {
                        type: 'gradient',
                    },
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            chart: {
                                width: 200
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }],
                    series: {!! json_encode($expenseCatAmount) !!},
                    colors: {!! json_encode($expenseCategoryColor) !!},
                    labels: {!! json_encode($expenseCategory) !!},
                    legend: {
                        show: true,
                        position: 'left',
                    }
                };
                var chart = new ApexCharts(document.querySelector("#chartByCategory"), options);
                chart.render();
            };

            function initIncomeChart() {
                var options = {
                    chart: {
                        height: 250,
                        type: 'donut',
                    },
                    dataLabels: {
                        enabled: false,
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '70%',
                            }
                        }
                    },
                    fill: {
                        type: 'gradient',
                    },
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            chart: {
                                width: 200
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }],
                    series: {!! json_encode($incomeCatAmount) !!},
                    colors: {!! json_encode($incomeCategoryColor) !!},
                    labels: {!! json_encode($incomeCategory) !!},
                    legend: {
                        show: true,
                        position: 'left',
                    }
                };
                var chart = new ApexCharts(document.querySelector("#chartByCategory"), options);
                chart.render();
            };

        
            // Event listener for "Show Income" button
            document.getElementById('btn-income').addEventListener('click', function() {
                document.getElementById('chartByCategory').textContent = '';
                initIncomeChart();
                document.getElementById('chart-title').textContent = '{{ __('Income By Category') }}';
                document.getElementById('btn-income').classList.add('btn-primary');
                document.getElementById('btn-expense').classList.remove('btn-primary');
            });

            // Event listener for "Show Expense" button
            document.getElementById('btn-expense').addEventListener('click', function() {
                document.getElementById('chartByCategory').textContent = '';

                initExpenseChart();
                document.getElementById('chart-title').textContent = '{{ __('Expense By Category') }}';
                document.getElementById('btn-expense').classList.add('btn-primary');
                document.getElementById('btn-income').classList.remove('btn-primary');
            });
            initIncomeChart();
         

            // DON 
            (function() {
                // var options = {
                //     series: [{{ round($storage_limit, 2) }}],
                //     chart: {
                //         height: 350,
                //         type: 'radialBar',
                //         // offsetY: -20,
                //         // sparkline: {
                //         //     enabled: true
                //         // }
                //         toolbar: {
                //             show: true
                //         }
                //     },
                //     plotOptions: {
                //         radialBar: {
                //             startAngle: -90,
                //             endAngle: 90,
                //             track: {
                //                 background: "#e7e7e7",
                //                 strokeWidth: '97%',
                //                 margin: 5, // margin is in pixels
                //             },
                //             dataLabels: {
                //                 name: {
                //                     show: true
                //                 },
                //                 value: {
                //                     offsetY: -50,
                //                     fontSize: '20px'
                //                 }
                //             }
                //         }
                //     },
                //     grid: {
                //         padding: {
                //             top: -10
                //         }
                //     },
                //     colors: ["#6FD943"],
                //     labels: ['Used'],
                // };

                var options = {
                    series: [{{ round($storage_limit, 2) }}],
                    chart: {
                        height: 350,
                        type: 'radialBar',
                        toolbar: {
                            show: false
                        }
                    },
                    plotOptions: {
                        radialBar: {
                            startAngle: -135,
                            endAngle: 225,
                            hollow: {
                                margin: 0,
                                size: '70%',
                                background: '#fff',
                                image: undefined,
                                imageOffsetX: 0,
                                imageOffsetY: 0,
                                position: 'front',
                                dropShadow: {
                                    enabled: true,
                                    top: 0,
                                    left: 0,
                                    blur: 10,
                                    opacity: 0.1
                                }
                            },
                            track: {
                                background: '#fff',
                                strokeWidth: '67%',
                                margin: 0, // margin is in pixels
                                dropShadow: {
                                    enabled: true,
                                    top: 0,
                                    left: 0,
                                    blur: 10,
                                    opacity: 0.1
                                }
                            },

                            dataLabels: {
                                show: true,
                                name: {
                                    offsetY: -10,
                                    show: true,
                                    color: '#888',
                                    fontSize: '17px'
                                },
                                value: {
                                    formatter: function(val) {
                                        return parseFloat(val.toFixed(1));
                                    },
                                    color: '#111',
                                    fontSize: '36px',
                                    show: true,
                                }
                            }
                        }
                    },
                    fill: {
                        type: 'gradient',
                        colors: [usedColorDarker],
                        gradient: {
                            shade: 'dark',
                            type: 'horizontal',
                            shadeIntensity: 0.5,
                            gradientToColors: [usedColor],
                            inverseColors: true,
                            opacityFrom: 1,
                            opacityTo: 1,
                            stops: [0, 300]
                        }
                    },
                    stroke: {
                        lineCap: 'round'
                    },
                    labels: ['Percent'],
                };

                var chart = new ApexCharts(document.querySelector("#limit-chart"), options);
                chart.render();
            })();

            // new charts 
            function createWeeklyChart() {
                var options = {
                    chart: {
                        height: 300,
                        type: 'bar',
                        toolbar: {
                            show: false,
                        },
                    },
                    dataLabels: {
                        enabled: false
                    },
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '45%',
                            borderRadius: 7,
                            endingShape: 'rounded',
                            borderRadiusApplication: 'around',
                        },
                    },
                    stroke: {
                        show: true,
                        width: 5,
                        curve: 'smooth',
                        colors: ['transparent']
                    },
                    series: [{
                        name: "{{ __('Income') }}",
                        data: {!! json_encode($incweekBarChartData['income']) !!}
                    }, {
                        name: "{{ __('Recived') }}",
                        data: {!! json_encode($incweekBarChartData['recive']) !!}
                    }, {
                        name: "{{ __('Due') }}",
                        data: {!! json_encode($incweekBarChartData['due']) !!}
                    }],
                    xaxis: {
                        categories: {!! json_encode($incweekBarChartData['labels']) !!},
                        labels: {
                            style: {
                                colors: '#6c757d',
                                fontSize: '12px',
                            },
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: '#6c757d',
                                fontSize: '12px',
                            },
                        },
                        offsetX: -10,
                        min: 0,
                    },
                    colors: [usedColor, usedColorLight, usedColorMedium, usedColorDark, usedColorContrast,
                        usedColorDarker
                    ],
                    fill: {
                        type: 'solid',
                    },
                    grid: {
                        strokeDashArray: 4,
                        // borderColor: '#e9ecef'
                    },
                    legend: {
                        show: true,
                        position: 'top',
                        horizontalAlign: 'center',
                        labels: {
                            colors: '#495057',
                            useSeriesColors: false
                        }
                    },
                    // markers: {
                    //     size: 4,
                    //     colors:  ['#3ec9d6', '#FF3A6E',],
                    //     opacity: 0.9,
                    //     strokeWidth: 2,
                    //     hover: {
                    //         size: 7,
                    //     }
                    // }
                };
                var chart = new ApexCharts(document.querySelector("#weeklyChart"), options);
                chart.render();
            };
            // new charts 
            function createMonthlyChart() {
                var options = {
                    chart: {
                        height: 300,
                        type: 'bar',
                        toolbar: {
                            show: false,
                        },
                    },
                    dataLabels: {
                        enabled: false
                    },
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '45%',
                            borderRadius: 7,
                            endingShape: 'rounded',
                            borderRadiusApplication: 'around',
                        },
                    },
                    stroke: {
                        show: true,
                        width: 5,
                        curve: 'smooth',
                        colors: ['transparent']
                    },
                    series: [{
                        name: "{{ __('Income') }}",
                        data: {!! json_encode($incmonthBarChartData['income']) !!}
                    }, {
                        name: "{{ __('Recived') }}",
                        data: {!! json_encode($incmonthBarChartData['recive']) !!}
                    }, {
                        name: "{{ __('Due') }}",
                        data: {!! json_encode($incmonthBarChartData['due']) !!}
                    },],
                    xaxis: {
                        categories: {!! json_encode($incmonthBarChartData['labels']) !!},
                        labels: {
                            style: {
                                colors: '#6c757d',
                                fontSize: '12px',
                            },
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: '#6c757d',
                                fontSize: '12px',
                            },
                        },
                        offsetX: -10,
                        min: 0,
                    },
                    colors: [usedColor, usedColorLight, usedColorMedium, usedColorDark, usedColorContrast,
                        usedColorDarker
                    ],
                    fill: {
                        type: 'solid',
                    },
                    grid: {
                        strokeDashArray: 4,
                        // borderColor: '#e9ecef'
                    },
                    legend: {
                        show: true,
                        position: 'top',
                        horizontalAlign: 'center',
                        labels: {
                            colors: '#495057',
                            useSeriesColors: false
                        }
                    },
                    // markers: {
                    //     size: 4,
                    //     colors:  ['#3ec9d6', '#FF3A6E',],
                    //     opacity: 0.9,
                    //     strokeWidth: 2,
                    //     hover: {
                    //         size: 7,
                    //     }
                    // }
                };
                var chart = new ApexCharts(document.querySelector("#monthlyChart"), options);
                chart.render();
            };
            // new charts 
            function createYearlyChart() {
                var options = {
                    chart: {
                        height: 300,
                        type: 'bar',
                        toolbar: {
                            show: false,
                        },
                    },
                    dataLabels: {
                        enabled: false
                    },
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '45%',
                            borderRadius: 7,
                            endingShape: 'rounded',
                            borderRadiusApplication: 'around',
                        },
                    },
                    stroke: {
                        show: true,
                        width: 5,
                        curve: 'smooth',
                        colors: ['transparent']
                    },
                    series: [{
                        name: "{{ __('Income') }}",
                        data: {!! json_encode($incyearBarChartData['income']) !!}
                    }, {
                        name: "{{ __('Recived') }}",
                        data: {!! json_encode($incyearBarChartData['recive']) !!}
                    }, {
                        name: "{{ __('Due') }}",
                        data: {!! json_encode($incyearBarChartData['due']) !!}
                    }],
                    xaxis: {
                        categories: {!! json_encode($incyearBarChartData['labels']) !!},
                        labels: {
                            style: {
                                colors: '#6c757d',
                                fontSize: '12px',
                            },
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: '#6c757d',
                                fontSize: '12px',
                            },
                        },
                        offsetX: -10,
                        min: 0,
                    },
                    colors: [usedColor, usedColorLight, usedColorMedium, usedColorDark, usedColorContrast,
                        usedColorDarker
                    ],
                    fill: {
                        type: 'solid',
                    },
                    grid: {
                        strokeDashArray: 4,
                        // borderColor: '#e9ecef'
                    },
                    legend: {
                        show: true,
                        position: 'top',
                        horizontalAlign: 'center',
                        labels: {
                            colors: '#495057',
                            useSeriesColors: false
                        }
                    },
                    // markers: {
                    //     size: 4,
                    //     colors:  ['#3ec9d6', '#FF3A6E',],
                    //     opacity: 0.9,
                    //     strokeWidth: 2,
                    //     hover: {
                    //         size: 7,
                    //     }
                    // }
                };
                var chart = new ApexCharts(document.querySelector("#yearlyChart"), options);
                chart.render();
            };

            // Common function to show the selected chart
            function showChart(chartId, btnId) {
                var charts = document.querySelectorAll('.chart');
                var btn = document.getElementById(btnId);

                // Remove 'active' class from all buttons
                var buttons = document.querySelectorAll('.btn-graph-toggler');
                buttons.forEach(function(button) {
                    button.classList.remove('active');
                });

                // Add 'active' class to the clicked button
                btn.classList.add('active');

                // Hide all charts
                charts.forEach(function(chart) {
                    chart.style.visibility = 'hidden';
                    chart.style.position = 'absolute';
                });

                // Show the selected chart by its ID
                var selectedChart = document.getElementById(chartId);
                if (selectedChart) {
                    selectedChart.style.visibility = 'visible';
                    selectedChart.style.position = 'relative';

                    // Recreate the chart depending on which one is shown
                    if (chartId === 'weeklyChart') {
                        createWeeklyChart();
                    } else if (chartId === 'monthlyChart') {
                        createMonthlyChart();
                    } else if (chartId === 'yearlyChart') {
                        createYearlyChart();
                    }
                } else {
                    console.error(`Chart with ID "${chartId}" not found.`);
                }
            }
            // Create the initial chart
            createYearlyChart(); // You can choose which chart to show initially

            function initWeeklyChart() {
                var options = {
                    chart: {
                        height: 250,
                        type: 'donut',
                    },
                    dataLabels: {
                        enabled: false,
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '70%',
                            }
                        }
                    },
                    fill: {
                        type: 'gradient',
                    },
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            chart: {
                                width: 200
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }],
                    series:  @json($seriesWeak),
                    colors: [usedColor, usedColorMedium,usedColorLight,],
                    labels: ['Total', 'Paid', 'Due'],
                    legend: {
                        show: true,
                        position: 'left',
                    }
                };
                var chart = new ApexCharts(document.querySelector("#billChartContainer"), options);
                chart.render();
            };

            function initMonthlyChart() {
                var options = {
                    chart: {
                        height: 250,
                        type: 'donut',
                    },
                    dataLabels: {
                        enabled: false,
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '70%',
                            }
                        }
                    },
                    fill: {
                        type: 'gradient',
                    },
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            chart: {
                                width: 200
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }],
                    series: @json($seriesMonth),
                    colors:  [usedColor, usedColorMedium,usedColorLight,],
                    labels:  ['Total', 'Paid', 'Due'],
                    legend: {
                        show: true,
                        position: 'left',
                    }
                };
                var chart = new ApexCharts(document.querySelector("#billChartContainer"), options);
                chart.render();
            };

               // new data 
            // Event listener for "Weekly Statistics" button
            document.getElementById('btn-weekly').addEventListener('click', function() {
                document.getElementById('billChartContainer').textContent = '';
                initWeeklyChart();
                document.getElementById('chart-title_exp').textContent = '{{ __('Bills Weekly Statistics') }}';
                document.getElementById('btn-weekly').classList.add('btn-primary');
                document.getElementById('btn-monthly').classList.remove('btn-primary');
            });

            // Event listener for "Monthly Statistics" button
            document.getElementById('btn-monthly').addEventListener('click', function() {
                document.getElementById('billChartContainer').textContent = '';
                initMonthlyChart();
                document.getElementById('chart-title_exp').textContent = '{{ __('Bills Monthly Statistics') }}';
                document.getElementById('btn-monthly').classList.add('btn-primary');
                document.getElementById('btn-weekly').classList.remove('btn-primary');
            });

            // Initialize the Weekly chart by default (since it’s active by default)
            initWeeklyChart();
        @endif
    </script>
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Account') }}</li>
@endsection
@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="row">
                <div class="col-xxl-12">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-lg-3 col-12 col-sm-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="card-flex-row">
                                                <div class="theme-avtar bg-primary">
                                                    <img class="image-icon" src="{{ asset('/assets/images/1.png') }}"
                                                        alt="">
                                                </div>
                                                {{-- <p class="text-muted text-sm mt-4 mb-2">{{__('Total')}}</p> --}}
                                                <div class="col-12">
                                                    <h6 class="mb-0">{{ __('Total Customers') }}</h6>
                                                    <h3 class="mb-0">{{ \Auth::user()->countCustomers() }}

                                                    </h3>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-12 col-sm-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="card-flex-row">
                                                <div class="theme-avtar bg-primary">
                                                    <img class="image-icon" src="{{ asset('/assets/images/2.png') }}"
                                                        alt="">
                                                    {{-- <i class="ti ti-users"></i> --}}
                                                </div>
                                                <div class="col-12">
                                                    <h6 class="mb-0">{{ __('Total Vendors') }}</h6>
                                                    <h3 class="mb-0">{{ \Auth::user()->countVenders() }}</h3>
                                                </div>
                                            </div>
                                            {{-- <p class="text-muted text-sm mt-4 mb-2">{{__('Total')}}</p> --}}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-12 col-sm-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="card-flex-row">
                                                <div class="theme-avtar bg-primary">
                                                    <img class="image-icon" src="{{ asset('/assets/images/3.png') }}"
                                                        alt="">
                                                    {{-- <i class="ti ti-report-money"></i> --}}
                                                </div>
                                                {{-- <p class="text-muted text-sm mt-4 mb-2">{{__('Total')}}</p> --}}
                                                <div class="col-12">
                                                    <h6 class="mb-0">{{ __('Total Invoices') }}</h6>
                                                    <h3 class="mb-0">{{ \Auth::user()->countInvoices() }} </h3>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-12 col-sm-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="card-flex-row">
                                                <div class="theme-avtar bg-primary">
                                                    <img class="image-icon" src="{{ asset('/assets/images/4.png') }}"
                                                        alt="">
                                                    {{-- <i class="ti ti-report-money"></i> --}}
                                                </div>
                                                {{-- <p class="text-muted text-sm mt-4 mb-2">{{__('Total')}}</p> --}}
                                                <div class="col-12">
                                                    <h6 class="mb-0">{{ __('Total Bills') }}</h6>
                                                    <h3 class="mb-0">{{ \Auth::user()->countBills() }} </h3>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>{{ __('Income & Expense') }}
                                        <span
                                            class="float-end text-muted">{{ __('Current Year') . ' - ' . $currentYear }}</span>
                                    </h5>

                                </div>
                                <div class="card-body">
                                    <div id="incExpBarChart"></div>
                                </div>
                            </div>
                        </div>

                        {{-- hide for new design --}}
                        {{-- <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mt-1 mb-0">{{__('Account Balance')}}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                            <tr>
                                                <th>{{__('Bank')}}</th>
                                                <th>{{__('Holder Name')}}</th>
                                                <th>{{__('Balance')}}</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @forelse($bankAccountDetail as $bankAccount)

                                                <tr class="font-style">
                                                    <td>{{$bankAccount->bank_name}}</td>
                                                    <td>{{$bankAccount->holder_name}}</td>
                                                    <td>{{\Auth::user()->priceFormat($bankAccount->opening_balance)}}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4">
                                                        <div class="text-center">
                                                            <h6>{{__('there is no account balance')}}</h6>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            </div>
                            <div class="col-xxl-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mt-1 mb-0">{{__('Latest Income')}}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                            <tr>
                                                <th>{{__('Date')}}</th>
                                                <th>{{__('Customer')}}</th>
                                                <th>{{__('Amount Due')}}</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @forelse($latestIncome as $income)
                                                <tr>
                                                    <td>{{\Auth::user()->dateFormat($income->date)}}</td>
                                                    <td>{{!empty($income->customer)?$income->customer->name:'-'}}</td>
                                                    <td>{{\Auth::user()->priceFormat($income->amount)}}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4">
                                                        <div class="text-center">
                                                            <h6>{{__('There is no latest income')}}</h6>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            </div>
                            <div class="col-xxl-12">
                                <div class="card">
                                <div class="card-header">
                                    <h5 class="mt-1 mb-0">{{__('Latest Expense')}}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                            <tr>
                                                <th>{{__('Date')}}</th>
                                                <th>{{__('Vendor')}}</th>
                                                <th>{{__('Amount Due')}}</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @forelse($latestExpense as $expense)

                                                <tr>
                                                    <td>{{\Auth::user()->dateFormat($expense->date)}}</td>
                                                    <td>{{!empty($expense->vender)?$expense->vender->name:'-'}}</td>
                                                    <td>{{\Auth::user()->priceFormat($expense->amount)}}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4">
                                                        <div class="text-center">
                                                            <h6>{{__('There is no latest expense')}}</h6>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="col-xxl-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mt-1 mb-0">{{__('Recent Invoices')}}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>{{__('Customer')}}</th>
                                                <th>{{__('Issue Date')}}</th>
                                                <th>{{__('Due Date')}}</th>
                                                <th>{{__('Amount')}}</th>
                                                <th>{{__('Status')}}</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @forelse($recentInvoice as $invoice)
                                                <tr>
                                                    <td>{{\Auth::user()->invoiceNumberFormat($invoice->invoice_id)}}</td>
                                                    <td>{{!empty($invoice->customer_name)? $invoice->customer_name:'' }} </td>
                                                    <td>{{ Auth::user()->dateFormat($invoice->issue_date) }}</td>
                                                    <td>{{ Auth::user()->dateFormat($invoice->due_date) }}</td>
                                                    <td>{{\Auth::user()->priceFormat($invoice->getTotal())}}</td>
                                                    <td>
                                                        @if ($invoice->status == 0)
                                                            <span class="p-2 px-3 rounded badge status_badge bg-secondary">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                                        @elseif($invoice->status == 1)
                                                            <span class="p-2 px-3 rounded badge status_badge bg-warning">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                                        @elseif($invoice->status == 2)
                                                            <span class="p-2 px-3 rounded badge status_badge bg-danger">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                                        @elseif($invoice->status == 3)
                                                            <span class="p-2 px-3 rounded badge status_badge bg-info">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                                        @elseif($invoice->status == 4)
                                                            <span class="p-2 px-3 rounded badge status_badge bg-primary">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6">
                                                        <div class="text-center">
                                                            <h6>{{__('There is no recent invoice')}}</h6>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mt-1 mb-0">{{__('Recent Bills')}}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>{{__('Vendor')}}</th>
                                                <th>{{__('Bill Date')}}</th>
                                                <th>{{__('Due Date')}}</th>
                                                <th>{{__('Amount')}}</th>
                                                <th>{{__('Status')}}</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @forelse($recentBill as $bill)
                                                <tr>
                                                    <td>{{\Auth::user()->billNumberFormat($bill->bill_id)}}</td>
                                                    <td>{{!empty($bill->vender_name)? $bill->vender_name : '-' }} </td>
                                                    <td>{{ Auth::user()->dateFormat($bill->bill_date) }}</td>
                                                    <td>{{ Auth::user()->dateFormat($bill->due_date) }}</td>
                                                    <td>{{\Auth::user()->priceFormat($bill->getTotal())}}</td>
                                                    <td>
                                                        @if ($bill->status == 0)
                                                            <span class="p-2 px-3 rounded badge bg-secondary">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                                        @elseif($bill->status == 1)
                                                            <span class="p-2 px-3 rounded badge bg-warning">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                                        @elseif($bill->status == 2)
                                                            <span class="p-2 px-3 rounded badge bg-danger">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                                        @elseif($bill->status == 3)
                                                            <span class="p-2 px-3 rounded badge bg-info">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                                        @elseif($bill->status == 4)
                                                            <span class="p-2 px-3 rounded badge bg-primary">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6">
                                                        <div class="text-center">
                                                            <h6>{{__('There is no recent bill')}}</h6>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div> --}}

                    </div>
                </div>
                <div class="col-xxl-12">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mt-1 mb-0">{{ __('Cashflow') }}</h5>
                                </div>
                                <div class="card-body">
                                    <div id="cash-flow"></div>
                                </div>
                            </div>


                        </div>
                        <div class="row " style="display: contents;">
                            <div class="col-xl-6 col-xxl-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>{{ __('Storage Limit') }}
                                            {{--                                        <span class="float-end text-muted">{{__('Year').' - '.$currentYear}}</span> --}}
                                            <small class="float-end text-muted">{{ $users->storage_limit . 'MB' }} /
                                                {{ $plan->storage_limit . 'MB' }}</small>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="limit-chart"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-6 col-xxl-6 ">
                                <div class="card " style ="height: 95%;">
                                    <div class="card-header">
                                        <h5 class="mt-1 mb-0">{{ __('Income Vs Expense') }}</h5>
                                    </div>
                                    <div class="card-body height-responsive-40">
                                        <div class="row">
                                            <div class="col-sm-12 my-2 border-b">
                                                <div
                                                    class="d-flex align-items-start justify-content-between mb-2 margin_inline">
                                                    <div class="d-flex">
                                                        <div class="theme-avtar bg-primary">
                                                            <img class="image-icon"
                                                                src="{{ asset('/assets/images/2.1.png') }}" alt="">
                                                            {{-- <i class="ti ti-report-money"></i> --}}
                                                        </div>
                                                        <div class="ms-2">
                                                            <p class="text-primary text-sm mb-0">{{ __('Income Today') }}
                                                            </p>
                                                            <p class="text-primary text-sm mb-0">{{ __('Total Income') }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <h4 class="mb-0 ">
                                                        {{ \Auth::user()->priceFormat(\Auth::user()->todayIncome()) }}</h4>

                                                </div>
                                            </div>

                                            <div class="col-sm-12 my-2 border-b">
                                                <div
                                                    class="d-flex align-items-start justify-content-between mb-2 margin_inline">
                                                    <div class="d-flex">
                                                        <div class="theme-avtar bg-primary">
                                                            <img class="image-icon"
                                                                src="{{ asset('/assets/images/2.2.png') }}" alt="">
                                                            {{-- <i class="ti ti-file-invoice"></i> --}}
                                                        </div>
                                                        <div class="ms-2">
                                                            <p class="text-primary text-sm mb-0">{{ __('Expense Today') }}
                                                            </p>
                                                            <p class="text-primary text-sm mb-0">{{ __('Total Expense') }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <h4 class="mb-0 ">
                                                        {{ \Auth::user()->priceFormat(\Auth::user()->todayExpense()) }}
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="col-sm-12 my-2 border-b">

                                                <div
                                                    class="d-flex align-items-start justify-content-between mb-2 margin_inline">
                                                    <div class="d-flex">
                                                        <div class="theme-avtar bg-primary">
                                                            <img class="image-icon"
                                                                src="{{ asset('/assets/images/2.3.png') }}" alt="">
                                                            {{-- <i class="ti ti-report-money"></i> --}}
                                                        </div>
                                                        <div class="ms-2">
                                                            <p class="text-primary text-sm mb-0">
                                                                {{ __('Income This Month') }}
                                                            </p>
                                                            <p class="text-primary text-sm mb-0">{{ __('Total Income') }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <h4 class="mb-0 ">
                                                        {{ \Auth::user()->priceFormat(\Auth::user()->incomeCurrentMonth()) }}
                                                    </h4>
                                                </div>

                                            </div>
                                            <div class=" col-sm-12 my-2 border-b">
                                                <div
                                                    class="d-flex align-items-start justify-content-between mb-2 margin_inline">
                                                    <div class="d-flex">
                                                        <div class="theme-avtar bg-primary">
                                                            <img class="image-icon"
                                                                src="{{ asset('/assets/images/2.4.png') }}"
                                                                alt="">
                                                            {{-- <i class="ti ti-file-invoice"></i> --}}
                                                        </div>
                                                        <div class="ms-2">
                                                            <p class="text-primary text-sm mb-0">
                                                                {{ __('Expense This Month') }}
                                                            </p>
                                                            <p class="text-primary text-sm mb-0">{{ __('Total Expense') }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <h4 class="mb-0 ">
                                                        {{ \Auth::user()->priceFormat(\Auth::user()->expenseCurrentMonth()) }}
                                                    </h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xxl-12">
                            <div class="card">
                                <div class="card-header">
                                    <span class="float-end text-muted">{{ __('Current Year') . ' - ' . $currentYear }}</span>
                                    <div class="btn-graph-bar">
                                        <div class="rounded text-white p-2  btn-graph-toggler" id="showWeekly"
                                            onclick="showChart('weeklyChart','showWeekly')">Week</div>
                                        <div class="rounded text-white p-2  btn-graph-toggler" id="showMonthly"
                                            onclick="showChart('monthlyChart','showMonthly')">Month</div>
                                        <div class="rounded text-white p-2  btn-graph-toggler active" id="showYearly"
                                            onclick="showChart('yearlyChart','showYearly')">Year</div>
                                    </div>
                                </div>
                                <div class="card-body">


                                    <style>
                                        .btn-graph-bar {
                                            background: #f8f7fc;
                                            display: flex;
                                            justify-content: center;
                                            align-items: center;
                                            gap: 1rem;
                                            padding-block: 0.3rem;
                                            padding-inline: 2rem;
                                            width: max-content;
                                            margin-inline: auto;
                                            border: 1px solid #f8f7fc;
                                            border-radius: 20px;
                                        }

                                        .btn-graph-toggler {
                                            background: transparent;
                                            color: black !important;
                                            transition: all 0.03s ease-out;
                                            cursor: pointer;
                                        }

                                        .btn-graph-toggler.active {
                                            background: #ffff;
                                            box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
                                            padding: 0.6rem !important;
                                            background-color: var(--used-color);
                                            color: #fff !important;
                                        }

                                        .charts-area {
                                            height: auto !important;

                                        }

                                        #incomeByCategory,
                                        #expenseByCategory {
                                            height: 30vh !important;
                                            width: 100%;
                                        }


                                        /* Default styling for the charts */
                                        .chart {
                                            visibility: hidden;
                                            position: absolute;
                                            width: 100% !important;
                                            height: 40vh !important;
                                        }

                                        /* Show weekly chart by default */
                                        #yearlyChart {
                                            visibility: visible;
                                            position: relative;
                                        }
                                        .image-icon {
                                            height: auto !important;
                                            width: 25px !important;
                                        }
                                    </style>
                                    <div class="charts-area">
                                        {{-- <div class="table-responsive"> --}}
                                        <div id="weeklyChart" class="chart"></div>
                                        <div id="monthlyChart" class="chart"></div>
                                        <div id="yearlyChart" class="chart"></div>
                                        {{-- <div id="pie_chart_111" style="height: 30vh; width: 50vh;"></div> --}}
                                        {{-- </div> --}}
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xl-12 col-xxl-6">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between">
                                    <h5 id="chart-title">{{ __('Income By Category') }}
                                        <span class="float-end text-muted">{{ __('Year') . ' - ' . $currentYear }}</span>
                                    </h5>
                                    <div>
                                        <button id="btn-income" class="btn btn-primary">{{ __('Income') }}</button>
                                        <button id="btn-expense" class="btn ">{{ __('Expense') }}</button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div id="chartByCategory"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-12 col-xxl-6">
                            <div class="card h-100" style="height:95% !important;">
                                <div class="card-header d-flex justify-content-between">
                                    <h5 id="chart-title_exp">{{ __('Bills Weekly Statistics') }}</h5>
                                    <div>
                                        <button id="btn-weekly"
                                            class="btn btn-primary">{{ __('Weekly Statistics') }}</button>
                                        <button id="btn-monthly" class="btn ">{{ __('Monthly Statistics') }}</button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    {{-- <div class="table-responsive"> --}}
                                    <div id="billChartContainer"></div>
                                    {{-- </div> --}}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- <div class="col-xxl-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>{{__('Expense By Category')}}
                                        <span class="float-end text-muted">{{__('Year').' - '.$currentYear}}</span>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div id="expenseByCategory"></div>
                                </div>
                            </div>
                        </div> --}}

                    {{-- <div class="col-xxl-12">
                        <div class="card">
                            <div class="card-body">

                                <ul class="nav nav-pills mb-5" id="pills-tab" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" id="pills-home-tab" data-bs-toggle="pill"
                                            href="#invoice_weekly_statistics" role="tab" aria-controls="pills-home"
                                            aria-selected="true">{{ __('Invoices Weekly Statistics') }}</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="pills-profile-tab" data-bs-toggle="pill"
                                            href="#invoice_monthly_statistics" role="tab"
                                            aria-controls="pills-profile"
                                            aria-selected="false">{{ __('Invoices Monthly Statistics') }}</a>
                                    </li>
                                </ul>
                                <div class="tab-content" id="pills-tabContent">
                                    <div class="tab-pane fade show active" id="invoice_weekly_statistics" role="tabpanel"
                                        aria-labelledby="pills-home-tab">
                                        <div class="table-responsive">
                                            <table class="table align-items-center mb-0 ">
                                                <tbody class="list">
                                                    <tr>
                                                        <td>
                                                            <h5 class="mb-0">{{ __('Total') }}</h5>
                                                            <p class="text-muted text-sm mb-0">
                                                                {{ __('Invoice Generated') }}</p>

                                                        </td>
                                                        <td>
                                                            <h4 class="text-muted">
                                                                {{ \Auth::user()->priceFormat($weeklyInvoice['invoiceTotal']) }}
                                                            </h4>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <h5 class="mb-0">{{ __('Total') }}</h5>
                                                            <p class="text-muted text-sm mb-0">{{ __('Paid') }}</p>
                                                        </td>
                                                        <td>
                                                            <h4 class="text-muted">
                                                                {{ \Auth::user()->priceFormat($weeklyInvoice['invoicePaid']) }}
                                                            </h4>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <h5 class="mb-0">{{ __('Total') }}</h5>
                                                            <p class="text-muted text-sm mb-0">{{ __('Due') }}</p>
                                                        </td>
                                                        <td>
                                                            <h4 class="text-muted">
                                                                {{ \Auth::user()->priceFormat($weeklyInvoice['invoiceDue']) }}
                                                            </h4>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="invoice_monthly_statistics" role="tabpanel"
                                        aria-labelledby="pills-profile-tab">
                                        <div class="table-responsive">
                                            <table class="table align-items-center mb-0 ">
                                                <tbody class="list">
                                                    <tr>
                                                        <td>
                                                            <h5 class="mb-0">{{ __('Total') }}</h5>
                                                            <p class="text-muted text-sm mb-0">
                                                                {{ __('Invoice Generated') }}</p>

                                                        </td>
                                                        <td>
                                                            <h4 class="text-muted">
                                                                {{ \Auth::user()->priceFormat($monthlyInvoice['invoiceTotal']) }}
                                                            </h4>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <h5 class="mb-0">{{ __('Total') }}</h5>
                                                            <p class="text-muted text-sm mb-0">{{ __('Paid') }}</p>
                                                        </td>
                                                        <td>
                                                            <h4 class="text-muted">
                                                                {{ \Auth::user()->priceFormat($monthlyInvoice['invoicePaid']) }}
                                                            </h4>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <h5 class="mb-0">{{ __('Total') }}</h5>
                                                            <p class="text-muted text-sm mb-0">{{ __('Due') }}</p>
                                                        </td>
                                                        <td>
                                                            <h4 class="text-muted">
                                                                {{ \Auth::user()->priceFormat($monthlyInvoice['invoiceDue']) }}
                                                            </h4>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-12">
                        <div class="card">
                            <div class="card-body">

                                <ul class="nav nav-pills mb-5" id="pills-tab" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" id="pills-home-tab" data-bs-toggle="pill"
                                            href="#bills_weekly_statistics" role="tab" aria-controls="pills-home"
                                            aria-selected="true">{{ __('Bills Weekly Statistics') }}</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="pills-profile-tab" data-bs-toggle="pill"
                                            href="#bills_monthly_statistics" role="tab" aria-controls="pills-profile"
                                            aria-selected="false">{{ __('Bills Monthly Statistics') }}</a>
                                    </li>
                                </ul>
                                <div class="tab-content" id="pills-tabContent">
                                    <div class="tab-pane fade show active" id="bills_weekly_statistics" role="tabpanel"
                                        aria-labelledby="pills-home-tab">
                                        <div class="table-responsive">
                                            <table class="table align-items-center mb-0 ">
                                                <tbody class="list">
                                                    <tr>
                                                        <td>
                                                            <h5 class="mb-0">{{ __('Total') }}</h5>
                                                            <p class="text-muted text-sm mb-0">{{ __('Bill Generated') }}
                                                            </p>

                                                        </td>
                                                        <td>
                                                            <h4 class="text-muted">
                                                                {{ \Auth::user()->priceFormat($weeklyBill['billTotal']) }}
                                                            </h4>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <h5 class="mb-0">{{ __('Total') }}</h5>
                                                            <p class="text-muted text-sm mb-0">{{ __('Paid') }}</p>
                                                        </td>
                                                        <td>
                                                            <h4 class="text-muted">
                                                                {{ \Auth::user()->priceFormat($weeklyBill['billPaid']) }}
                                                            </h4>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <h5 class="mb-0">{{ __('Total') }}</h5>
                                                            <p class="text-muted text-sm mb-0">{{ __('Due') }}</p>
                                                        </td>
                                                        <td>
                                                            <h4 class="text-muted">
                                                                {{ \Auth::user()->priceFormat($weeklyBill['billDue']) }}
                                                            </h4>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="bills_monthly_statistics" role="tabpanel"
                                        aria-labelledby="pills-profile-tab">
                                        <div class="table-responsive">
                                            <table class="table align-items-center mb-0 ">
                                                <tbody class="list">
                                                    <tr>
                                                        <td>
                                                            <h5 class="mb-0">{{ __('Total') }}</h5>
                                                            <p class="text-muted text-sm mb-0">{{ __('Bill Generated') }}
                                                            </p>

                                                        </td>
                                                        <td>
                                                            <h4 class="text-muted">
                                                                {{ \Auth::user()->priceFormat($monthlyBill['billTotal']) }}
                                                            </h4>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <h5 class="mb-0">{{ __('Total') }}</h5>
                                                            <p class="text-muted text-sm mb-0">{{ __('Paid') }}</p>
                                                        </td>
                                                        <td>
                                                            <h4 class="text-muted">
                                                                {{ \Auth::user()->priceFormat($monthlyBill['billPaid']) }}
                                                            </h4>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <h5 class="mb-0">{{ __('Total') }}</h5>
                                                            <p class="text-muted text-sm mb-0">{{ __('Due') }}</p>
                                                        </td>
                                                        <td>
                                                            <h4 class="text-muted">
                                                                {{ \Auth::user()->priceFormat($monthlyBill['billDue']) }}
                                                            </h4>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div> --}}
                </div>
            </div>
            {{-- <div class="col-xxl-12">
                <div class="card">
                    <div class="card-header">
                        <h5>{{ __('Goal') }}</h5>
                    </div>
                    <div class="card-body">
                        @forelse($goals as $goal)
                            @php
                                $total = $goal->target($goal->type, $goal->from, $goal->to, $goal->amount)['total'];
                                $percentage = $goal->target($goal->type, $goal->from, $goal->to, $goal->amount)[
                                    'percentage'
                                ];
                                $per = number_format(
                                    $goal->target($goal->type, $goal->from, $goal->to, $goal->amount)['percentage'],
                                    Utility::getValByName('decimal_number'),
                                    '.',
                                    '',
                                );
                            @endphp
                            <div class="card border-success border-2 border-bottom-0 border-start-0 border-end-0">
                                <div class="card-body">
                                    <div class="form-check">
                                        <label class="form-check-label d-block" for="customCheckdef1">
                                            <span>
                                                <span class="row align-items-center">
                                                    <span class="col">
                                                        <span class="text-muted text-sm">{{ __('Name') }}</span>
                                                        <h6 class="text-nowrap mb-3 mb-sm-0">{{ $goal->name }}</h6>
                                                    </span>
                                                    <span class="col">
                                                        <span class="text-muted text-sm">{{ __('Type') }}</span>
                                                        <h6 class="mb-3 mb-sm-0">
                                                            {{ __(\App\Models\Goal::$goalType[$goal->type]) }}</h6>
                                                    </span>
                                                    <span class="col">
                                                        <span class="text-muted text-sm">{{ __('Duration') }}</span>
                                                        <h6 class="mb-3 mb-sm-0">{{ $goal->from . ' To ' . $goal->to }}</h6>
                                                    </span>
                                                    <span class="col">
                                                        <span class="text-muted text-sm">{{ __('Target') }}</span>
                                                        <h6 class="mb-3 mb-sm-0">
                                                            {{ \Auth::user()->priceFormat($total) . ' of ' . \Auth::user()->priceFormat($goal->amount) }}
                                                        </h6>
                                                    </span>
                                                    <span class="col">
                                                        <span class="text-muted text-sm">{{ __('Progress') }}</span>
                                                        <h6 class="mb-2 d-block">
                                                            {{ number_format($goal->target($goal->type, $goal->from, $goal->to, $goal->amount)['percentage'], Utility::getValByName('decimal_number'), '.', '') }}%
                                                        </h6>
                                                        <div class="progress mb-0">
                                                            @if ($per <= 33)
                                                                <div class="progress-bar bg-danger"
                                                                    style="width: {{ $per }}%"></div>
                                                            @elseif($per >= 33 && $per <= 66)
                                                                <div class="progress-bar bg-warning"
                                                                    style="width: {{ $per }}%"></div>
                                                            @else
                                                                <div class="progress-bar bg-primary"
                                                                    style="width: {{ $per }}%"></div>
                                                            @endif
                                                        </div>
                                                    </span>
                                                </span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="card pb-0">
                                <div class="card-body text-center">
                                    <h6>{{ __('There is no goal.') }}</h6>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div> --}}
        </div>
    </div>
    {{-- </div> --}}
@endsection

@push('script-page')
    <script>
        if (window.innerWidth <= 500) {
            $('p').removeClass('text-sm');
        }
    </script>
@endpush
