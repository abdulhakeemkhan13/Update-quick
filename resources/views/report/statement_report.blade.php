@extends('layouts.admin')
@section('page-title')
    {{ __('Account Statement Summary') }}
@endsection
@push('script-page')
    <!-- <script src="{{ asset('js/jspdf.min.js') }} "></script>
                    <script type="text/javascript" src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
                    <script type="text/javascript" src="{{ asset('assets/js/jszip.js') }}"></script>
                    <script type="text/javascript" src="{{ asset('assets/js/pdfmake.js') }}"></script>
                    <script type="text/javascript" src="{{ asset('assets/js/vfs_fonts.js') }}"></script>
                    <script type="text/javascript" src="{{ asset('assets/js/dataTables.buttons.js') }}"></script>
                    <script type="text/javascript" src="{{ asset('assets/js/buttons.html5.js') }}"></script>
                    <script type="text/javascript" src="{{ asset('assets/js/buttons.print.min.js') }}"></script> -->
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
                    format: 'A4'
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
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Account Statement Summary') }}</li>
@endsection


@section('action-btn')
    <div class="float-end">

        <a href="{{ route('allReports') }}" class="btn btn-sm btn-primary"
            title="{{ __('Back to All Reports') }}" data-original-title="{{ __('Back to All Reports') }}">
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
                    value="{{ __('Account Statement') . ' ' . $filter['type'] . ' ' . 'Report of' . ' ' . $filter['startDateRange'] . ' to ' . $filter['endDateRange'] }}"
                    id="filename">
                <div class="card p-4 mb-4">
                    <h7 class="report-text gray-text mb-0">{{ __('Report') }} :</h7>
                    <h6 class="report-text mb-0">{{ __('Account Statement Summary') }}</h6>
                </div>
            </div>
            @if ($filter['account'] != __('All'))
                <div class="col">
                    <div class="card p-4 mb-4">
                        <h7 class="report-text gray-text mb-0">{{ __('Account') }} :</h7>
                        <h6 class="report-text mb-0">{{ $filter['account'] }}</h6>
                    </div>
                </div>
            @endif
            @if ($filter['type'] != __('All'))
                <div class="col">
                    <div class="card p-4 mb-4">
                        <h7 class="report-text gray-text mb-0">{{ __('Type') }} :</h7>
                        <h6 class="report-text mb-0">{{ $filter['type'] }}</h6>
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
            <div class="col-sm-12">
                <div class="mt-2 mb-2 d-flex justify-content-between align-items-center" id="multiCollapseExample1">

                    {{-- Account Statement Filters Dropdown --}}
                    <div class="dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" id="accountFilterDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ti ti-filter"></i> {{ __('Filters') }}
                        </button>
                        <div class="dropdown-menu p-3" style="min-width: 350px;">
                            <div class="card shadow-none border-0">
                                <div class="card-body p-0">
                                    {{ Form::open(['route' => ['report.account.statement'], 'method' => 'get', 'id' => 'report_account']) }}
                                    <div class="row">

                                        {{-- Start Month --}}
                                        <div class="col-12 mb-3">
                                            {{ Form::label('start_month', __('Start Month'), ['class' => 'form-label']) }}
                                            {{ Form::month('start_month', request('start_month', date('Y-m', strtotime('-5 month'))), ['class' => 'form-control']) }}
                                        </div>

                                        {{-- End Month --}}
                                        <div class="col-12 mb-3">
                                            {{ Form::label('end_month', __('End Month'), ['class' => 'form-label']) }}
                                            {{ Form::month('end_month', request('end_month', date('Y-m')), ['class' => 'form-control']) }}
                                        </div>

                                        {{-- Account --}}
                                        <div class="col-12 mb-3">
                                            {{ Form::label('account', __('Account'), ['class' => 'form-label']) }}
                                            {{ Form::select('account', $account, request('account', ''), ['class' => 'form-control select']) }}
                                        </div>

                                        {{-- Category --}}
                                        <div class="col-12 mb-3">
                                            {{ Form::label('type', __('Category'), ['class' => 'form-label']) }}
                                            {{ Form::select('type', $types, request('type', ''), ['class' => 'form-control select', 'placeholder' => __('Select Category')]) }}
                                        </div>

                                        {{-- Buttons --}}
                                        <div class="col-12 d-flex justify-content-between">
                                            <a href="{{ route('report.account.statement') }}"
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
                        <a href="{{ route('accountstatement.export') }}" data-bs-toggle="tooltip"
                            title="{{ __('Export') }}" class="btn btn-sm btn-primary">
                            <i class="ti ti-file-export"></i>
                        </a>

                        <a href="#" class="btn btn-sm btn-primary" onclick="saveAsPDF()" data-bs-toggle="tooltip"
                            title="{{ __('Download') }}">
                            <i class="ti ti-download"></i>
                        </a>
                    </div>

                </div>
            </div>
        </div>


        @if (!empty($reportData['revenueAccounts']))
            <div class="row">
                @foreach ($reportData['revenueAccounts'] as $account)
                    <div class="col-xl-3 col-md-6 col-lg-3">
                        <div class="card p-4 mb-4">
                            @if ($account->holder_name == 'Cash')
                                <h7 class="report-text gray-text mb-0">{{ $account->holder_name }}</h7>
                            @elseif(empty($account->holder_name))
                                <h7 class="report-text gray-text mb-0">{{ __('Stripe / Paypal') }}</h7>
                            @else
                                <h7 class="report-text gray-text mb-0">
                                    {{ $account->holder_name . ' - ' . $account->bank_name }}</h7>
                            @endif
                            <h6 class="report-text mb-0">{{ \Auth::user()->priceFormat($account->total) }}</h6>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if (!empty($reportData['paymentAccounts']))
            <div class="row">
                @foreach ($reportData['paymentAccounts'] as $account)
                    <div class="col-xl-3 col-md-6 col-lg-3">
                        <div class="card p-4 mb-4">
                            @if ($account->holder_name == 'Cash')
                                <h5 class="report-text gray-text mb-0">{{ $account->holder_name }}</h5>
                            @elseif(empty($account->holder_name))
                                <h5 class="report-text gray-text mb-0">{{ __('Stripe / Paypal') }}</h5>
                            @else
                                <h5 class="report-text gray-text mb-0">
                                    {{ $account->holder_name . ' - ' . $account->bank_name }}</h5>
                            @endif
                            <h5 class="report-text mb-0">{{ \Auth::user()->priceFormat($account->total) }}</h5>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Description') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (!empty($reportData['revenues']))
                                    @foreach ($reportData['revenues'] as $revenue)
                                        <tr class="font-style">
                                            <td>{{ Auth::user()->dateFormat($revenue->date) }}</td>
                                            <td>{{ Auth::user()->priceFormat($revenue->amount) }}</td>
                                            <td>{{ $revenue->description }} </td>
                                        </tr>
                                    @endforeach
                                @endif
                                @if (!empty($reportData['payments']))
                                    @foreach ($reportData['payments'] as $payments)
                                        <tr class="font-style">
                                            <td>{{ Auth::user()->dateFormat($payments->date) }}</td>
                                            <td>{{ Auth::user()->priceFormat($payments->amount) }}</td>
                                            <td>{{ !empty($payments->description) ? $payments->description : '-' }} </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
