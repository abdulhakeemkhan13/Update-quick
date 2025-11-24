@extends('layouts.admin')

@section('page-title')
    {{ __('All Sales') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Accounting System') }}</a></li>
    <li class="breadcrumb-item">{{ __('Transactions') }}</li>
    <li class="breadcrumb-item">{{ __('All Sales') }}</li>
@endsection

@push('css-page')
    <link rel="stylesheet" href="{{ asset('css/datatable/buttons.dataTables.min.css') }}">
    <style>
        .sales-metric {
            text-align: center;
            padding: 0 15px;
        }

        .metric-amount {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 4px;
        }

        .metric-description {
            font-size: 0.875rem;
            line-height: 1.2;
        }

        .sales-progress-container {
            margin: 20px 0 0 0;
        }

        .progress-bar-custom {
            display: flex;
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            background-color: #e9ecef;
        }

        .progress-segment {
            min-width: 2px;
            transition: all 0.3s ease;
        }

        .progress-segment:not(:last-child) {
            margin-right: 1px;
        }

        .bg-purple {
            background-color: #6f42c1 !important;
        }

        @media (max-width: 768px) {
            .sales-metric {
                padding: 0 8px;
                margin-bottom: 15px;
            }

            .metric-amount {
                font-size: 1.25rem;
            }

            .metric-description {
                font-size: 0.8rem;
            }
        }
    </style>
@endpush

@push('script-page')
    <script type="text/javascript" src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
    <script src="{{ asset('js/datatable/jszip.min.js') }}"></script>
    <script src="{{ asset('js/datatable/pdfmake.min.js') }}"></script>
    <script src="{{ asset('js/datatable/vfs_fonts.js') }}"></script>
    <script>
        function saveAsPDF() {
            var element = document.getElementById('printableArea');
            var opt = {
                margin: 0.3,
                filename: 'allSales',
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
    </script>
@endpush

@section('action-btn')
    <div class="float-end">
        <a href="#" class="btn btn-sm btn-primary" onclick="saveAsPDF()" data-bs-toggle="tooltip"
            title="{{ __('Download') }}">
            <span class="btn-inner--icon"><i class="ti ti-download"></i></span>
        </a>
    </div>
@endsection

@section('content')
    {{-- Include Sales Tabs --}}
    @include('transaction.sales-tabs')

    {{-- Filters --}}
    <div class="row">
        <div class="col-sm-12">
            <div class="mt-2" id="filters">
                {{-- bars --}}
                <div class="accordion mb-3" id="salesAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="salesHeading">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                data-bs-target="#salesCollapse" aria-expanded="true" aria-controls="salesCollapse">
                                <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                    <span class="fw-bold">{{ __('Sales transactions') }}</span>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-primary small">{{ __('Give feedback') }}</span>
                                        <i class="bi bi-chat-square-text text-primary"></i>
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="salesCollapse" class="accordion-collapse collapse show" aria-labelledby="salesHeading"
                            data-bs-parent="#salesAccordion">
                            <div class="accordion-body p-4">

                                {{-- Sales Metrics Row --}}
                                <div class="row g-0 mb-3">
                                    {{-- Estimates --}}
                                    <div class="col">
                                        <div class="sales-metric">
                                            <div class="metric-amount">
                                                {{ Auth::user()->priceFormat($salesData['estimates']['amount'] ?? 0) }}
                                            </div>
                                            <div class="metric-description text-muted small">
                                                {{ $salesData['estimates']['count'] ?? 0 }} {{ __('estimates') }}
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Unbilled Income --}}
                                    <div class="col">
                                        <div class="sales-metric">
                                            <div class="metric-amount">
                                                {{ Auth::user()->priceFormat($salesData['unbilled']['amount'] ?? 0) }}
                                            </div>
                                            <div class="metric-description text-muted small">
                                                {{ __('Unbilled income') }}
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Overdue Invoices --}}
                                    <div class="col">
                                        <div class="sales-metric">
                                            <div class="metric-amount">
                                                {{ Auth::user()->priceFormat($salesData['overdue']['amount'] ?? 0) }}</div>
                                            <div class="metric-description text-muted small">
                                                {{ $salesData['overdue']['count'] ?? 0 }} {{ __('overdue invoices') }}
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Open Invoices --}}
                                    <div class="col">
                                        <div class="sales-metric">
                                            <div class="metric-amount">
                                                {{ Auth::user()->priceFormat($salesData['open']['amount'] ?? 0) }}</div>
                                            <div class="metric-description text-muted small">
                                                {{ $salesData['open']['count'] ?? 0 }}
                                                {{ __('open invoices and credits') }}
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Recently Paid --}}
                                    <div class="col">
                                        <div class="sales-metric">
                                            <div class="metric-amount">
                                                {{ Auth::user()->priceFormat($salesData['paid']['amount'] ?? 0) }}</div>
                                            <div class="metric-description text-muted small">
                                                {{ $salesData['paid']['count'] ?? 0 }} {{ __('recently paid') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Color Progress Bars --}}
                                <div class="sales-progress-container">
                                    <div class="progress-bar-custom">
                                        <div class="progress-segment bg-info"
                                            style="flex: {{ max(1, $salesData['estimates']['amount'] ?? 0) }};"></div>
                                        <div class="progress-segment bg-purple"
                                            style="flex: {{ max(1, $salesData['unbilled']['amount'] ?? 0) }};"></div>
                                        <div class="progress-segment bg-warning"
                                            style="flex: {{ max(1, $salesData['overdue']['amount'] ?? 0) }};"></div>
                                        <div class="progress-segment bg-primary"
                                            style="flex: {{ max(1, $salesData['open']['amount'] ?? 0) }};"></div>
                                        <div class="progress-segment bg-success"
                                            style="flex: {{ max(1, $salesData['paid']['amount'] ?? 0) }};"></div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                {{-- filters --}}
                <div class="pb-3">
                    <div class="card-body">
                        {{ Form::open(['route' => 'allSales', 'method' => 'get', 'id' => 'allSales_filter']) }}
                        <div class="row align-items-center justify-content-start">

                            <div class="col-xl-3 col-lg-3 col-md-6">
                                {{-- Filters Dropdown --}}
                                <div class="d-flex flex-column">
                                    <label for="filtersDropdown" class="form-label mb-1">{{ __('Filters') }}</label>
                                    <div class="dropdown">
                                        <button class="btn btn-outline-primary dropdown-toggle" type="button"
                                            id="filtersDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ti ti-filter"></i> {{ __('Filters') }}
                                        </button>

                                        <div class="dropdown-menu p-3" style="min-width: 350px;">
                                            <div class="card shadow-none border-0">
                                                <div class="card-body p-0">
                                                    {{ Form::open(['route' => 'allSales', 'method' => 'get', 'id' => 'filtersForm']) }}
                                                    <div class="row">

                                                        {{-- Transactions Type --}}
                                                        <div class="col-12 mb-3">
                                                            {{ Form::label('type', __('Transactions'), ['class' => 'form-label']) }}
                                                            {{ Form::select('type', $typeOptions, $selectedType, ['class' => 'form-control select']) }}
                                                        </div>

                                                        {{-- Customer --}}
                                                        <div class="col-12 mb-3">
                                                            {{ Form::label('customer', __('Customer'), ['class' => 'form-label']) }}
                                                            {{ Form::select('customer', $customers, $selectedCustomer, ['class' => 'form-control select']) }}
                                                        </div>

                                                        {{-- Buttons --}}
                                                        <div class="col-12 d-flex justify-content-between">
                                                            <a href="{{ route('allSales') }}"
                                                                class="btn btn-outline-secondary btn-sm"
                                                                data-bs-toggle="tooltip" title="{{ __('Reset') }}">
                                                                <i class="ti ti-trash-off"></i> {{ __('Reset') }}
                                                            </a>

                                                            <button type="submit" class="btn btn-success btn-sm"
                                                                data-bs-toggle="tooltip" title="{{ __('Apply') }}">
                                                                <i class="ti ti-search"></i> {{ __('Apply') }}
                                                            </button>
                                                        </div>

                                                    </div>
                                                    {{ Form::close() }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <div class="col-xl-3 col-lg-3 col-md-6">
                                {{-- Month Range Dropdown --}}
                                <div class="dropdown">
                                    {{-- Month Range Dropdown Label --}}
                                    <label class="form-label">{{ __('Month Range') }}</label>
                                    <button class="btn btn-outline-primary dropdown-toggle" type="button"
                                        id="monthFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-calendar"></i> {{ __('Select Month') }}
                                    </button>

                                    <div class="dropdown-menu p-3" style="min-width: 350px;">
                                        <div class="card shadow-none border-0">
                                            <div class="card-body p-0">
                                                {{ Form::open(['route' => 'allSales', 'method' => 'get', 'id' => 'monthFilterForm']) }}
                                                <div class="row">

                                                    {{-- Start Month --}}
                                                    <div class="col-12 mb-3">
                                                        {{ Form::label('start_month', __('Start Month'), ['class' => 'form-label']) }}
                                                        {{ Form::month('start_month', $start_month ?: date('Y-m'), ['class' => 'form-control']) }}
                                                    </div>

                                                    {{-- End Month --}}
                                                    <div class="col-12 mb-3">
                                                        {{ Form::label('end_month', __('End Month'), ['class' => 'form-label']) }}
                                                        {{ Form::month('end_month', $end_month ?: date('Y-m'), ['class' => 'form-control']) }}
                                                    </div>

                                                    {{-- Buttons --}}
                                                    <div class="col-12 d-flex justify-content-between">
                                                        <a href="{{ route('allSales') }}"
                                                            class="btn btn-outline-secondary btn-sm"
                                                            data-bs-toggle="tooltip" title="{{ __('Reset') }}">
                                                            <i class="ti ti-trash-off"></i> {{ __('Reset') }}
                                                        </a>

                                                        <button type="submit" class="btn btn-success btn-sm"
                                                            data-bs-toggle="tooltip" title="{{ __('Apply') }}">
                                                            <i class="ti ti-search"></i> {{ __('Apply') }}
                                                        </button>
                                                    </div>

                                                </div>
                                                {{ Form::close() }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                        {{ Form::close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Printable header --}}
    {{-- <div id="printableArea" class="mb-3">
        <div class="row">
            <div class="col">
                <div class="card p-4 mb-4">
                    <h6 class="mb-0">{{ __('Report') }} :</h6>
                    <h7 class="text-sm mb-0">{{ __('All Sales') }}</h7>
                </div>
            </div>
            <div class="col">
                <div class="card p-4 mb-4">
                    <h6 class="mb-0">{{ __('Duration') }} :</h6>
                    <h7 class="text-sm mb-0">{{ ($start_month ?: date('Y-m')) . ' to ' . ($end_month ?: date('Y-m')) }}</h7>
                </div>
            </div>
        </div>
    </div> --}}

    {{-- Table --}}
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Customer') }}</th>
                                    <th>{{ __('Memo') }}</th>
                                    <th class="text-end">{{ __('Amount') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th width="10%">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $r)
                                    <tr>
                                        <td>{{ Auth::user()->dateFormat($r['date']) }}</td>
                                        <td>{{ $r['type'] }}</td>
                                        <td>{{ $r['customer'] }}</td>
                                        <td>{{ $r['memo'] ?: '-' }}</td>
                                        <td class="text-end">{{ Auth::user()->priceFormat($r['amount']) }}</td>
                                        <td>{{ $r['status'] ?? '-' }}</td>
                                        <td class="Action">
                                            <div class="d-flex">
                                                @if (!empty($r['view_url']))
                                                    <div class="action-btn bg-info ms-2">
                                                        <a href="{{ $r['view_url'] }}"
                                                            class="mx-3 btn btn-sm align-items-center"
                                                            data-bs-toggle="tooltip" title="{{ __('View') }}">
                                                            <i class="ti ti-eye text-white"></i>
                                                        </a>
                                                    </div>
                                                @endif
                                                @if (!empty($r['edit_url']))
                                                    <div class="action-btn bg-primary ms-2">
                                                        <a href="{{ $r['edit_url'] }}"
                                                            class="mx-3 btn btn-sm align-items-center"
                                                            data-bs-toggle="tooltip" title="{{ __('Edit') }}">
                                                            <i class="ti ti-pencil text-white"></i>
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">{{ __('No transactions found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
