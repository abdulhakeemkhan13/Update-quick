@extends('layouts.admin')
@section('page-title')
    {{ __('Deposits Summary') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Deposits Summary') }}</li>
@endsection

@push('theme-script')
    <script src="{{ asset('assets/js/plugins/apexcharts.min.js') }}"></script>
@endpush

@php
    if (isset($_GET['category']) && $_GET['period'] == 'yearly') {
        $chartArr = [];

        foreach ($chartIncomeArr as $innerArray) {
            foreach ($innerArray as $value) {
                $chartArr[] = $value;
            }
        }
    } else {
        $chartArr = $chartIncomeArr[0];
    }
@endphp
@push('script-page')
    <script>
        (function() {
            var chartBarOptions = {
                series: [{
                    name: '{{ __('Income') }}',
                    data: {!! json_encode($chartArr) !!},
                }, ],

                chart: {
                    height: 300,
                    type: 'area',
                    // type: 'line',
                    dropShadow: {
                        enabled: true,
                        color: '#000',
                        top: 18,
                        left: 7,
                        blur: 10,
                        opacity: 0.2
                    },
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
                    // categories: {!! json_encode($monthList) !!},
                    categories: {!! json_encode($monthList) !!},
                    title: {
                        text: '{{ __('Months') }}'
                    }
                },
                colors: ['#6fd944', '#6fd944'],

                grid: {
                    strokeDashArray: 4,
                },
                legend: {
                    show: false,
                },
                // markers: {
                //     size: 4,
                //     colors: ['#ffa21d', '#FF3A6E'],
                //     opacity: 0.9,
                //     strokeWidth: 2,
                //     hover: {
                //         size: 7,
                //     }
                // },
                yaxis: {
                    title: {
                        text: '{{ __('Income') }}'
                    },

                }

            };
            var arChart = new ApexCharts(document.querySelector("#chart-sales"), chartBarOptions);
            arChart.render();
        })();
    </script>
    <script type="text/javascript" src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
    <script>
        var year = '{{ $currentYear }}';
        var filename = $('#filename').val();

        function saveAsPDF() {
            var element = document.getElementById('printableArea');
            var opt = {
                margin: 0.3,
                filename: filename,
                image: {
                    type: 'jpeg',
                    quality: 1
                },
                html2canvas: {
                    scale: 4,
                    dpi: 72,
                    letterRendering: true
                },
                jsPDF: {
                    unit: 'in',
                    format: 'A2'
                }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>
@endpush

{{-- @section('action-btn')
    <div class="float-end">

        <a href="{{ route('allReports') }}" class="btn btn-sm btn-primary" title="{{ __('Back to All Reports') }}"
            data-original-title="{{ __('Back to All Reports') }}">
            <i class="ti ti-arrow-left"></i>
            {{ __('Back to All Reports') }}
        </a>

    </div>
@endsection --}}


@section('content')
@include('transaction.sales-tabs')
    <div id="printableArea">
        <div class="row mt-3">
            <div class="col">
                <input type="hidden"
                    value="{{ $filter['category'] . ' ' . __('Deposits Summary') . ' ' . 'Report of' . ' ' . $filter['startDateRange'] . ' to ' . $filter['endDateRange'] }}"
                    id="filename">
                <div class="card p-4 mb-4">
                    <h7 class="report-text gray-text mb-0">{{ __('Report') }} :</h7>
                    <h6 class="report-text mb-0">{{ __('Deposits Summary') }}</h6>
                </div>
            </div>
            @if ($filter['category'] != __('All'))
                <div class="col">
                    <div class="card p-4 mb-4">
                        <h7 class="report-text gray-text mb-0">{{ __('Category') }} :</h7>
                        <h6 class="report-text mb-0">{{ $filter['category'] }}</h6>
                    </div>
                </div>
            @endif
            @if ($filter['customer'] != __('All'))
                <div class="col">
                    <div class="card p-4 mb-4">
                        <h7 class="report-text gray-text mb-0">{{ __('Customer') }} :</h7>
                        <h6 class="report-text mb-0">{{ $filter['customer'] }}</h6>
                    </div>
                </div>
            @endif
            <div class="col">
                <div class="card p-4 mb-4">
                    <h7 class="report-text gray-text mb-0">{{ __('Duration') }} :</h7>
                    @if (isset($_GET['period']) && $_GET['period'] == 'yearly')
                        <h6 class="report-text mb-0">{{ array_key_last($yearList) . ' to ' . array_key_first($yearList) }}</h6>
                    @else
                        <h6 class="report-text mb-0">{{ $filter['startDateRange'] . ' to ' . $filter['endDateRange'] }}</h6>
                    @endif
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="mt-2 mb-2 d-flex justify-content-between align-items-center">

                    {{-- Filter Dropdown --}}
                    <div class="dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" id="incomeFilterDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ti ti-filter"></i> {{ __('Filters') }}
                        </button>
                        <div class="dropdown-menu p-3" style="min-width: 350px;">
                            <div class="card shadow-none border-0">
                                <div class="card-body p-0">
                                    {{ Form::open(['route' => ['report.income.summary'], 'method' => 'GET', 'id' => 'report_income_summary']) }}
                                    <div class="row">

                                        {{-- Income Period --}}
                                        <div class="col-12 mb-3">
                                            {{ Form::label('period', __('Income Period'), ['class' => 'form-label']) }}
                                            {{ Form::select('period', $periods, isset($_GET['period']) ? $_GET['period'] : '', ['class' => 'form-control select period', 'required' => 'required']) }}
                                        </div>

                                        {{-- Year (only if not yearly) --}}
                                        @if (!isset($_GET['period']) || $_GET['period'] != 'yearly')
                                            <div class="col-12 mb-3">
                                                {{ Form::label('year', __('Year'), ['class' => 'form-label']) }}
                                                {{ Form::select('year', $yearList, isset($_GET['year']) ? $_GET['year'] : '', ['class' => 'form-control select']) }}
                                            </div>
                                        @endif

                                        {{-- Category --}}
                                        <div class="col-12 mb-3">
                                            {{ Form::label('category', __('Category'), ['class' => 'form-label']) }}
                                            {{ Form::select('category', $category, isset($_GET['category']) ? $_GET['category'] : '', ['class' => 'form-control select']) }}
                                        </div>

                                        {{-- Customer --}}
                                        <div class="col-12 mb-3">
                                            {{ Form::label('customer', __('Customer'), ['class' => 'form-label']) }}
                                            {{ Form::select('customer', $customer, isset($_GET['customer']) ? $_GET['customer'] : '', ['class' => 'form-control select']) }}
                                        </div>

                                        {{-- Buttons --}}
                                        <div class="col-12 d-flex justify-content-between">
                                            <a href="{{ route('report.income.summary') }}"
                                                class="btn btn-outline-secondary btn-sm" data-bs-toggle="tooltip"
                                                title="{{ __('Reset') }}">
                                                <i class="ti ti-trash-off"></i> {{ __('Reset') }}
                                            </a>

                                            <button type="submit" class="btn btn-success btn-sm" data-bs-toggle="tooltip"
                                                title="{{ __('Apply') }}">
                                                <i class="ti ti-search"></i> {{ __('Apply') }}
                                            </button>
                                        </div>

                                    </div>
                                    {{ Form::close() }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Buttons on the right --}}
                    <div class="d-flex gap-2">
                        <a href="#" class="btn btn-sm btn-primary" onclick="saveAsPDF()" data-bs-toggle="tooltip"
                            title="{{ __('Download') }}">
                            <i class="ti ti-download"></i>
                        </a>
                    </div>

                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12" id="chart-container">
                <div class="card">
                    <div class="card-body">
                        <div class="scrollbar-inner">
                            <div id="chart-sales" data-color="primary" data-height="300"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-body table-border-style">
                        {{-- quarterly --}}
                        @if (isset($_GET['category']) && $_GET['period'] == 'quarterly')
                            <div class="table-responsive" id="quarterly">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Category') }}</th>
                                            @foreach ($monthList as $month)
                                                <th>{{ $month }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="5" class="text-dark"><span>{{ __('Revenue :') }}</span></td>
                                        </tr>
                                        @foreach ($incomeArr as $i => $income)
                                            <tr>
                                                <td>{{ $income['category'] }}</td>
                                                @foreach ($income['data'] as $j => $data)
                                                    <td>{{ \Auth::user()->priceFormat($data) }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                        <tr>
                                            <td colspan="5" class="text-dark"><span>{{ __('Invoice :') }}</span></td>
                                        </tr>
                                        @foreach ($invoiceArray as $i => $invoice)
                                            <tr>
                                                <td>{{ $invoice['category'] }}</td>
                                                @foreach ($invoice['data'] as $j => $data)
                                                    <td>{{ \Auth::user()->priceFormat($data) }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                        <tr>
                                            <td colspan="5" class="text-dark">
                                                <span>{{ __('Income = Revenue + Invoice :') }}</span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-dark">
                                                <h6>{{ __('Total') }}</h6>
                                            </td>
                                            @foreach ($chartIncomeArr as $i => $income)
                                                @foreach ($income as $key => $value)
                                                    <td>{{ \Auth::user()->priceFormat($value) }}</td>
                                                @endforeach
                                            @endforeach
                                        </tr>
                                    </tbody>
                                </table>
                            </div>


                            {{-- half yearly --}}
                        @elseif(isset($_GET['category']) && $_GET['period'] == 'half-yearly')
                            <div class="table-responsive" id="half-yearly">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Category half') }}</th>
                                            @foreach ($monthList as $month)
                                                <th>{{ $month }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="13" class="text-dark"><span>{{ __('Revenue :') }}</span></td>
                                        </tr>
                                        @foreach ($incomeArr as $i => $income)
                                            <tr>
                                                <td>{{ $income['category'] }}</td>
                                                @foreach ($income['data'] as $j => $data)
                                                    <td>{{ \Auth::user()->priceFormat($data) }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                        <tr>
                                            <td colspan="13" class="text-dark"><span>{{ __('Invoice :') }}</span></td>
                                        </tr>
                                        @foreach ($invoiceArray as $i => $invoice)
                                            <tr>
                                                <td>{{ $invoice['category'] }}</td>
                                                @foreach ($invoice['data'] as $j => $data)
                                                    <td>{{ \Auth::user()->priceFormat($data) }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                        <tr>
                                            <td colspan="13" class="text-dark">
                                                <span>{{ __('Income = Revenue + Invoice :') }}</span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-dark">
                                                <h6>{{ __('Total') }}</h6>
                                            </td>
                                            @foreach ($chartIncomeArr as $i => $income)
                                                @foreach ($income as $key => $value)
                                                    <td>{{ \Auth::user()->priceFormat($value) }}</td>
                                                @endforeach
                                            @endforeach

                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            {{-- yearly --}}
                        @elseif(isset($_GET['category']) && $_GET['period'] == 'yearly')
                            <div class="table-responsive" id="yearly">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Category year') }}</th>
                                            @foreach ($monthList as $month)
                                                <th>{{ $month }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="2" class="text-dark"><span>{{ __('Revenue :') }}</span></td>
                                        </tr>
                                        @foreach ($incomeArr as $i => $income)
                                            <tr>
                                                <td>{{ $income['category'] }}</td>
                                                @foreach ($income['data'] as $j => $data)
                                                    <td>{{ \Auth::user()->priceFormat($data) }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                        <tr>
                                            <td colspan="2" class="text-dark"><span>{{ __('Invoice :') }}</span></td>
                                        </tr>
                                        @foreach ($invoiceArray as $i => $invoice)
                                            <tr>
                                                <td>{{ $invoice['category'] }}</td>
                                                @foreach ($invoice['data'] as $j => $data)
                                                    <td>{{ \Auth::user()->priceFormat($data) }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                        <tr>
                                            <td colspan="2" class="text-dark">
                                                <span>{{ __('Income = Revenue + Invoice :') }}</span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-dark">
                                                <h6>{{ __('Total') }}</h6>
                                            </td>

                                            @foreach ($chartIncomeArr as $i => $income)
                                                @foreach ($income as $key => $value)
                                                    <td>{{ \Auth::user()->priceFormat($value) }}</td>
                                                @endforeach
                                            @endforeach

                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="table-responsive" id="monthly">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Category') }}</th>
                                            @foreach ($monthList as $month)
                                                <th>{{ $month }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="13" class="text-dark"><span>{{ __('Revenue :') }}</span></td>
                                        </tr>
                                        @foreach ($incomeArr as $i => $income)
                                            <tr>
                                                <td>{{ $income['category'] }}</td>
                                                @foreach ($income['data'] as $j => $data)
                                                    <td>{{ \Auth::user()->priceFormat($data) }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                        <tr>
                                            <td colspan="13" class="text-dark"><span>{{ __('Invoice :') }}</span></td>
                                        </tr>
                                        @foreach ($invoiceArray as $i => $invoice)
                                            <tr>
                                                <td>{{ $invoice['category'] }}</td>
                                                @foreach ($invoice['data'] as $j => $data)
                                                    <td>{{ \Auth::user()->priceFormat($data) }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                        <tr>
                                            <td colspan="13" class="text-dark">
                                                <span>{{ __('Income = Revenue + Invoice :') }}</span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-dark">
                                                <h6>{{ __('Total') }}</h6>
                                            </td>
                                            @foreach ($chartIncomeArr as $i => $income)
                                                @foreach ($income as $key => $value)
                                                    <td>{{ \Auth::user()->priceFormat($value) }}</td>
                                                @endforeach
                                            @endforeach
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
