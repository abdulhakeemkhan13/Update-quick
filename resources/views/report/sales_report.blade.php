@extends('layouts.admin')
@section('page-title')
    {{ __('Sales Report') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Sales Report') }}</li>
@endsection
@push('script-page')
    <script type="text/javascript" src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
    <script>
        var filename = $('#filename').val();

        function saveAsPDF() {
            var printContents = document.getElementById('printableArea').innerHTML;
            var originalContents = document.body.innerHTML;
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
        }
    </script>

    <script>
        $(document).ready(function() {
            $("#filter").click(function() {
                $("#show_filter").toggle();
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            callback();

            function callback() {
                var start_date = $(".startDate").val();
                var end_date = $(".endDate").val();

                $('.start_date').val(start_date);
                $('.end_date').val(end_date);
            }
        });
    </script>

    <script>
        $(document).ready(function() {
            var id1 = $('.nav-item .active').attr('href');
            $('.report').val(id1);

            $("ul.nav-pills > li > a").click(function() {
                var report = $(this).attr('href');
                $('.report').val(report);
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
    <div class="row">
        <div class="col-sm-12">
            <div class="mt-2 mb-2 d-flex justify-content-between align-items-center" id="multiCollapseExample1">

                {{-- Sales Report Filters Dropdown --}}
                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle" type="button" id="salesFilterDropdown"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="ti ti-filter"></i> {{ __('Filters') }}
                    </button>
                    <div class="dropdown-menu p-3" style="min-width: 350px;">
                        <div class="card shadow-none border-0">
                            <div class="card-body p-0">
                                {{ Form::open(['route' => ['report.sales'], 'method' => 'GET', 'id' => 'report_sales']) }}
                                <div class="row">

                                    {{-- Start Date --}}
                                    <div class="col-12 mb-3">
                                        {{ Form::label('start_date', __('Start Date'), ['class' => 'form-label']) }}
                                        {{ Form::date('start_date', $filter['startDateRange'], ['class' => 'startDate form-control']) }}
                                    </div>

                                    {{-- End Date --}}
                                    <div class="col-12 mb-3">
                                        {{ Form::label('end_date', __('End Date'), ['class' => 'form-label']) }}
                                        {{ Form::date('end_date', $filter['endDateRange'], ['class' => 'endDate form-control']) }}
                                    </div>

                                    <input type="hidden" name="view" value="horizontal">

                                    {{-- Buttons --}}
                                    <div class="col-12 d-flex justify-content-between">
                                        <a href="{{ route('report.sales') }}" class="btn btn-outline-secondary btn-sm"
                                            data-bs-toggle="tooltip" title="{{ __('Reset') }}">
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

                    {{-- Export --}}
                    {{ Form::open(['route' => ['sales.export']]) }}
                    <input type="hidden" name="start_date" class="start_date">
                    <input type="hidden" name="end_date" class="end_date">
                    <input type="hidden" name="report" class="report">
                    <button type="submit" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                        title="{{ __('Export') }}">
                        <i class="ti ti-file-export"></i>
                    </button>
                    {{ Form::close() }}

                    {{-- Print --}}
                    <a href="#" onclick="saveAsPDF()" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                        title="{{ __('Print') }}">
                        <i class="ti ti-printer"></i>
                    </a>

                </div>

            </div>
        </div>
    </div>


    <div class="row">
        <div class="col-12" id="invoice-container">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between w-100">
                        <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="profile-tab3" data-bs-toggle="pill" href="#item"
                                    role="tab" aria-controls="pills-item"
                                    aria-selected="true">{{ __('Sales by Item') }}</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="contact-tab4" data-bs-toggle="pill" href="#customer" role="tab"
                                    aria-controls="pills-customer" aria-selected="false">{{ __('Sales by Customer') }}</a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card-body" id="printableArea">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="tab-content" id="myTabContent2">
                                <div class="tab-pane fade fade show active" id="item" role="tabpanel"
                                    aria-labelledby="profile-tab3">
                                    <div class="table-responsive">
                                        <table class="table pc-dt-simple" id="item-reort">
                                            <thead>
                                                <tr>
                                                    <th width="33%"> {{ __('Invoice Item') }}</th>
                                                    <th width="33%"> {{ __('Quantity Sold') }}</th>
                                                    <th width="33%"> {{ __('Amount') }}</th>
                                                    <th class="text-end"> {{ __('Average Price') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($invoiceItems as $invoiceItem)
                                                    <tr>
                                                        <td>{{ $invoiceItem['name'] }}</td>
                                                        <td>{{ $invoiceItem['quantity'] }}</td>
                                                        <td>{{ \Auth::user()->priceFormat($invoiceItem['price']) }}</td>
                                                        <td>{{ \Auth::user()->priceFormat($invoiceItem['avg_price']) }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="tab-pane fade fade" id="customer" role="tabpanel"
                                    aria-labelledby="profile-tab3">
                                    <div class="table-responsive">
                                        <table class="table pc-dt-simple" id="customer-report">
                                            <thead>
                                                <tr>
                                                    <th width="33%"> {{ __('Customer Name') }}</th>
                                                    <th width="33%"> {{ __('Invoice Count') }}</th>
                                                    <th width="33%"> {{ __('Sales') }}</th>
                                                    <th class="text-end"> {{ __('Sales With Tax') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($invoiceCustomers as $invoiceCustomer)
                                                    <tr>
                                                        <td>{{ $invoiceCustomer['name'] }}</td>
                                                        <td>{{ $invoiceCustomer['invoice_count'] }}</td>
                                                        <td>{{ \Auth::user()->priceFormat($invoiceCustomer['price']) }}
                                                        </td>
                                                        <td>{{ \Auth::user()->priceFormat($invoiceCustomer['price'] + $invoiceCustomer['total_tax']) }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
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
