@extends('layouts.admin')
@section('page-title')
    {{ __('Bill Summary') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Bill Summary') }}</li>
@endsection
@push('theme-script')
    <script src="{{ asset('assets/libs/apexcharts/dist/apexcharts.min.js') }}"></script>
@endpush
@push('script-page')
    <script>
        (function() {
            var chartBarOptions = {
                series: [{
                    name: '{{ __('Bill') }}',
                    data: {!! json_encode($billTotal) !!},

                }, ],

                chart: {
                    height: 300,
                    type: 'bar',
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
                        text: '{{ __('Bill') }}'
                    },

                }

            };
            var arChart = new ApexCharts(document.querySelector("#chart-sales"), chartBarOptions);
            arChart.render();
        })();
    </script>
    <script type="text/javascript" src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
    <script>
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

        $(document).ready(function() {
            var filename = $('#filename').val();
            $('#report-dataTable').DataTable({
                dom: 'lBfrtip',
                buttons: [{
                        extend: 'excel',
                        title: filename
                    },
                    {
                        extend: 'pdf',
                        title: filename
                    }, {
                        extend: 'csv',
                        title: filename
                    }
                ]
            });
        });
    </script>
@endpush
@section('action-btn')
    <div class="float-end">

        <a href="{{ route('allReports') }}" class="btn btn-sm btn-primary" title="{{ __('Back to All Reports') }}"
            data-original-title="{{ __('Back to All Reports') }}">
            <i class="ti ti-arrow-left"></i>
            {{ __('Back to All Reports') }}
        </a>

    </div>
@endsection


@section('content')
    <div id="printableArea">
        <div class="row mt-3">
            <div class="col">
                <input type="hidden"
                    value="{{ $filter['status'] . ' ' . __('Bill') . ' ' . 'Report of' . ' ' . $filter['startDateRange'] . ' to ' . $filter['endDateRange'] . ' ' . __('of') . ' ' . $filter['vender'] }}"
                    id="filename">
                <div class="card p-4 mb-4">
                    <h7 class="report-text gray-text mb-0">{{ __('Report') }} :</h7>
                    <h6 class="report-text mb-0">{{ __('Bill Summary') }}</h6>
                </div>
            </div>
            @if ($filter['vender'] != __('All'))
                <div class="col">
                    <div class="card p-4 mb-4">
                        <h7 class="report-text gray-text mb-0">{{ __('Vendor') }} :</h7>
                        <h6 class="report-text mb-0">{{ $filter['vender'] }}</h6>
                    </div>
                </div>
            @endif
            @if ($filter['status'] != __('All'))
                <div class="col">
                    <div class="card p-4 mb-4">
                        <h7 class="report-text gray-text mb-0">{{ __('Status') }} :</h7>
                        <h6 class="report-text mb-0">{{ $filter['status'] }}</h6>
                    </div>
                </div>
            @endif
            <div class="col">
                <div class="card p-4 mb-4">
                    <h7 class="report-text gray-text mb-0">{{ __('Duration') }} :</h7>
                    <h6 class="report-text mb-0">{{ $filter['startDateRange'] . ' to ' . $filter['endDateRange'] }}</h6>
                </div>
            </div>
        </div>
        <div class="row">

            <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="card p-4 mb-4">
                    <h7 class="report-text gray-text mb-0">{{ __('Total Bill') }}</h7>
                    <h6 class="report-text mb-0">{{ Auth::user()->priceFormat($totalBill) }}</h6>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="card p-4 mb-4">
                    <h7 class="report-text gray-text mb-0">{{ __('Total Paid') }}</h7>
                    <h6 class="report-text mb-0">{{ Auth::user()->priceFormat($totalPaidBill) }}</h6>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="card p-4 mb-4">
                    <h7 class="report-text gray-text mb-0">{{ __('Total Due') }}</h7>
                    <h6 class="report-text mb-0">{{ Auth::user()->priceFormat($totalDueBill) }}</h6>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <div class="mt-2 mb-2 d-flex justify-content-between align-items-center">

                    {{-- Bill Summary Filters Dropdown --}}
                    <div class="dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" id="billFilterDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ti ti-filter"></i> {{ __('Filters') }}
                        </button>
                        <div class="dropdown-menu p-3" style="min-width: 400px;">
                            <div class="card shadow-none border-0">
                                <div class="card-body p-0">
                                    {{ Form::open(['route' => ['report.bill.summary'], 'method' => 'GET', 'id' => 'report_bill_summary']) }}
                                    <div class="row">

                                        {{-- Start Month --}}
                                        <div class="col-12 mb-3">
                                            {{ Form::label('start_month', __('Start Month'), ['class' => 'form-label']) }}
                                            {{ Form::month('start_month', isset($_GET['start_month']) ? $_GET['start_month'] : date('Y-m', strtotime('-5 month')), ['class' => 'form-control']) }}
                                        </div>

                                        {{-- End Month --}}
                                        <div class="col-12 mb-3">
                                            {{ Form::label('end_month', __('End Month'), ['class' => 'form-label']) }}
                                            {{ Form::month('end_month', isset($_GET['end_month']) ? $_GET['end_month'] : date('Y-m'), ['class' => 'form-control']) }}
                                        </div>

                                        {{-- Vendor --}}
                                        <div class="col-12 mb-3">
                                            {{ Form::label('vender', __('Vendor'), ['class' => 'form-label']) }}
                                            {{ Form::select('vender', $vender, isset($_GET['vender']) ? $_GET['vender'] : '', ['class' => 'form-control select']) }}
                                        </div>

                                        {{-- Status --}}
                                        <div class="col-12 mb-3">
                                            {{ Form::label('status', __('Status'), ['class' => 'form-label']) }}
                                            {{ Form::select('status', ['' => 'Select Status'] + $status, isset($_GET['status']) ? $_GET['status'] : '', ['class' => 'form-control select']) }}
                                        </div>

                                        {{-- Buttons --}}
                                        <div class="col-12 d-flex justify-content-between">
                                            <a href="{{ route('report.bill.summary') }}"
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

                    {{-- Buttons on the right (optional, e.g. Download/Export) --}}
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
            <div class="col-12" id="bill-container">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between w-100">


                            <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="profile-tab3" data-bs-toggle="pill" href="#summary"
                                        role="tab" aria-controls="pills-summary"
                                        aria-selected="true">{{ __('Summary') }}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="contact-tab4" data-bs-toggle="pill" href="#bills"
                                        role="tab" aria-controls="pills-invoice"
                                        aria-selected="false">{{ __('Bills') }}</a>
                                </li>

                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="tab-content" id="myTabContent2">
                                    <div class="tab-pane fade fade" id="bills" role="tabpanel"
                                        aria-labelledby="profile-tab3">
                                        <div class="table-responsive">

                                            <table class="table datatable" id="report-dataTable">
                                                <thead>
                                                    <tr>
                                                        <th> {{ __('Bill') }}</th>
                                                        <th> {{ __('Date') }}</th>
                                                        <th> {{ __('Customer') }}</th>
                                                        <th> {{ __('Category') }}</th>
                                                        <th> {{ __('Status') }}</th>
                                                        <th> {{ __('	Paid Amount') }}</th>
                                                        <th> {{ __('Due Amount') }}</th>
                                                        <th> {{ __('Payment Date') }}</th>
                                                        <th> {{ __('Amount') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($bills as $bill)
                                                        <tr>
                                                            <td class="Id">
                                                                <a href="{{ route('bill.show', \Crypt::encrypt($bill->id)) }}"
                                                                    class="btn btn-outline-primary">{{ Auth::user()->billNumberFormat($bill->bill_id) }}</a>
                                                            </td>
                                                            </td>
                                                            <td>{{ Auth::user()->dateFormat($bill->send_date) }}</td>
                                                            <td> {{ !empty($bill->vender) ? $bill->vender->name : '-' }}
                                                            </td>
                                                            <td>{{ !empty($bill->category) ? $bill->category->name : '-' }}
                                                            </td>
                                                            <td>
                                                                @if ($bill->status == 0)
                                                                    <span
                                                                        class="badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$bill->status]) }}</span>
                                                                @elseif($bill->status == 1)
                                                                    <span
                                                                        class="badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$bill->status]) }}</span>
                                                                @elseif($bill->status == 2)
                                                                    <span
                                                                        class="badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$bill->status]) }}</span>
                                                                @elseif($bill->status == 3)
                                                                    <span
                                                                        class="badge bg-info p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$bill->status]) }}</span>
                                                                @elseif($bill->status == 4)
                                                                    <span
                                                                        class="badge bg-success p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$bill->status]) }}</span>
                                                                @endif
                                                            </td>
                                                            <td> {{ \Auth::user()->priceFormat($bill->getTotal() - $bill->getDue()) }}
                                                            </td>
                                                            <td> {{ \Auth::user()->priceFormat($bill->getDue()) }}</td>
                                                            <td>{{ !empty($bill->lastPayments) ? \Auth::user()->dateFormat($bill->lastPayments->date) : '' }}
                                                            </td>
                                                            <td> {{ \Auth::user()->priceFormat($bill->getTotal()) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade fade show active" id="summary" role="tabpanel"
                                        aria-labelledby="profile-tab3">
                                        <div class="scrollbar-inner">
                                            <div id="chart-sales" data-color="primary" data-type="bar"
                                                data-height="300"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
