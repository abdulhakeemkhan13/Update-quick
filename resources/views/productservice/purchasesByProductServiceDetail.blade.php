@extends('layouts.admin')

@section('content')
    {{-- Base skin --}}
    <style>
        body{background:#f8f9fa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;font-size:14px}
        .main-container{background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.1)}
        .header-section{padding:16px 24px;border-bottom:1px solid #e9ecef;display:flex;justify-content:space-between;align-items:center}
        .header-left h4{margin:0;font-size:16px;font-weight:600;color:#262626}
        .header-right{display:flex;align-items:center;gap:8px}
        .btn-icon{width:32px;height:32px;border:none;background:transparent;color:#6b7280;border-radius:4px;display:flex;align-items:center;justify-content:center}
        .btn-icon:hover{background:#f3f4f6;color:#262626}
        .btn-save{background:#1a73e8;color:#fff;border:none;padding:6px 16px;border-radius:4px;font-size:13px;font-weight:600}
        .filter-section{display:flex;justify-content:space-between;align-items:center;padding:16px 24px;border-bottom:1px solid #e9ecef;background:#fafbfc}
        .filter-row{display:flex;align-items:end;gap:16px;margin-bottom:12px}
        .filter-group{display:flex;align-items:end;gap:12px}
        .filter-item{display:flex;flex-direction:column;min-width:140px}
        .filter-label{font-size:12px;color:#6b7280;margin-bottom:6px;font-weight:500}
        .form-control,.form-select{height:32px;font-size:13px;border:1px solid #dadce0;border-radius:4px;padding:0 8px;background:#fff}
        .options-row{display:flex;align-self:self-end;gap:16px}
        .columns-btn,.filter-btn,.general-options,.view-options{display:flex;align-items:center;gap:6px;border-radius:4px;font-size:13px;color:#3c4043;text-decoration:none;padding:8px 12px;border:1px solid #d1d5db;background:#fff}
        .columns-btn:hover,.filter-btn:hover,.general-options:hover,.view-options:hover{background:#f8f9fa}
        .report-content{padding:24px}
        .report-header{text-align:center;margin-bottom:24px}
        .report-title{font-size:24px;font-weight:700;color:#262626;margin:0 0 8px}
        .company-name{font-size:16px;color:#6b7280;margin:0 0 8px}
        .date-range{font-size:14px;color:#374151;margin:0}
        .table-container{margin-top:16px;overflow-x:auto;width:100%}
        .ledger-table{width:100%;font-size:13px;white-space:nowrap}
        .ledger-table thead th{background:#f9fafb;border-bottom:2px solid #e5e7eb;font-weight:600;color:#374151;padding:12px 8px;font-size:12px;text-transform:uppercase;letter-spacing:.025em}
        .ledger-table tbody td{padding:8px;border-bottom:1px solid #f3f4f6;vertical-align:middle}
        .text-right{text-align:right}
        .text-center{text-align:center}
        .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:10000;overflow-y:auto}
        .drawer{position:fixed;top:0;right:0;bottom:0;width:360px;background:#fff;box-shadow:-2px 0 10px rgba(0,0,0,.1);overflow-y:auto}
        .modal-header{display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid #e6e6e6;background:#f9fafb}
        .modal-header h5{margin:0;font-size:16px;font-weight:600;color:#262626;display:flex;align-items:center;gap:8px}
        .btn-close{background:none;border:none;font-size:20px;color:#6b7280;cursor:pointer;line-height:1}
        .btn-close:hover{color:#262626}
        .modal-content{padding:16px 20px 24px}
        .option-section{margin-bottom:20px}
        .section-title{font-size:14px;font-weight:600;color:#262626;margin:0 0 10px;display:flex;align-items:center;justify-content:space-between;cursor:pointer}
        .option-group{display:flex;flex-direction:row;gap:10px}
        .checkbox-label{display:flex;align-items:center;gap:8px;font-size:13px;color:#374151;cursor:pointer;margin:0}
        .checkbox-label input{width:16px;height:16px;margin:0}
        .negative-format-group{display:flex;align-items:center;gap:12px}
        .alignment-group{margin-top:10px}
        .alignment-label{display:block;font-size:12px;color:#6b7280;margin-bottom:6px;font-weight:500}
        .negative-amount{color:#dc2626}
        .zero-amount{display:none}
        @media (max-width:768px){
            .filter-row{flex-direction:column;align-items:stretch}
            .filter-group{flex-direction:column}
            .options-row{flex-wrap:wrap}
            .drawer{width:100%}
        }
    </style>

    {{-- Skin overrides to match the target look & keep DT in viewport --}}
    <style id="qb-skin-overrides">
      body{background:#f5f6fa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;font-size:14px;color:#262626}
      .main-container{background:#fff;margin:24px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.1);overflow:hidden}
      .header-section{background:#fff;padding:16px 24px;border-bottom:1px solid #e6e6e6}
      .header-left h4{font-size:18px}
      .btn-save{background:#22c55e;padding:8px 16px}
      .btn-save:hover{background:#16a34a}
      .filter-section{background:#fff;padding:16px 24px;border-bottom:1px solid #e6e6e6;flex-direction:row;align-items:stretch}
      .filter-row{display:flex;justify-content:space-between;align-items:flex-end;gap:20px;flex-wrap:nowrap;margin-bottom:0}
      .filter-group{gap:14px;flex-wrap:nowrap;flex:1;min-width:0;display:flex;align-items:flex-end}
      .filter-item{min-width:auto;flex-shrink:0}
      .filter-label{font-size:11px;color:#6b7280;margin-bottom:4px;font-weight:500;white-space:nowrap}
      .form-control,.form-select{height:32px;border:1px solid #d1d5db;border-radius:4px;padding:6px 10px;font-size:12px;line-height:1.2}
      .form-control:focus,.form-select:focus{outline:0;border-color:#0969da;box-shadow:0 0 0 2px rgba(9,105,218,.1)}
      .options-row{justify-content:flex-end;margin-top:8px;gap:10px;flex-shrink:0}
      .columns-btn,.filter-btn,.general-options,.view-options{background:#fff;border:1px solid #d1d5db;border-radius:4px;padding:6px 10px;color:#374151;font-size:12px;white-space:nowrap;display:flex;align-items:center;gap:4px;height:32px;transition:all .15s}
      .columns-btn:hover,.filter-btn:hover,.general-options:hover,.view-options:hover{background:#f9fafb;border-color:#9ca3af;text-decoration:none}
      .table-container{overflow-x:auto;max-width:100vw}
      #purchases-by-product-service-detail-table{width:100% !important;border-collapse:collapse;font-size:13px}
      .ledger-table thead th{padding:12px 16px}
      .ledger-table tbody td{padding:12px 16px}
      .ledger-table tbody tr:hover{background:#f9fafb}

      .filter-button-item{display:flex;flex-direction:column;align-items:flex-start}
      .filter-button-item .filter-label{font-size:11px;color:transparent;margin-bottom:4px;height:15px}
      .filter-button-item .view-options{padding:6px 12px}

      div.dataTables_wrapper div.dataTables_scroll{width:100%}
      div.dataTables_wrapper .dataTables_scrollHead table,
      div.dataTables_wrapper .dataTables_scrollBody table{width:100% !important}

      .table-compact .ledger-table thead th{padding:8px 10px}
      .table-compact .ledger-table tbody td{padding:6px 10px}
      .table-striped .ledger-table tbody tr:nth-child(odd){background:#fafafa}
      .table-wrap .ledger-table td,.table-wrap .ledger-table th{white-space:normal}
      .table-bordered .ledger-table td,.table-bordered .ledger-table th{border:1px solid #e5e7eb}
      .sticky-head .dataTables_scrollHead{position:sticky;top:0;z-index:5}

      @media (max-width:900px){
        .filter-row{flex-direction:column;align-items:stretch;gap:12px}
        .filter-group{flex-wrap:wrap}
      }
      @media (max-width:768px){
        .main-container{margin:0;border-radius:0}
        .filter-group{flex-direction:column;align-items:stretch;gap:10px}
        .filter-item input,.filter-item select{width:100% !important}
        .options-row{justify-content:center;flex-wrap:wrap}
      }
      @media (max-width:480px){
        .options-row{display:grid;grid-template-columns:1fr 1fr;gap:8px}
      }
    </style>

    <div class="main-container">
        {{-- Header --}}
        <div class="header-section">
            <div class="header-left">
                <h4>{{ __('Purchases by Product/Service Detail') }}</h4>
            </div>
            <div class="header-right">
                <button class="btn-icon" title="{{ __('Refresh') }}" onclick="refreshData()"><i class="fas fa-sync-alt"></i></button>
                <button class="btn-icon" title="{{ __('Print') }}" id="print-btn"><i class="fas fa-print"></i></button>
                <a class="btn-icon" title="{{ __('Export') }}" href="{{ route('productservice.export') }}"><i class="fas fa-external-link-alt"></i></a>
                <a class="btn-icon" title="{{ __('Import') }}" href="#" data-url="{{ route('productservice.file.import') }}" data-ajax-popup="true"><i class="fas fa-file-import"></i></a>
                <button class="btn-save">{{ __('Save As') }}</button>
            </div>
        </div>

        {{-- Filters row --}}
        <div class="filter-section">
            <div class="filter-row">
                <div class="filter-group">
                    <div class="filter-item">
                        <label class="filter-label">{{ __('Report period') }}</label>
                        <select class="form-select" id="report-period" style="width: 180px;">
                            <option value="all_dates">{{ __('All Dates') }}</option>
                            <option value="today">{{ __('Today') }}</option>
                            <option value="this_week">{{ __('This week') }}</option>
                            <option value="this_week_to_date">{{ __('This week to date') }}</option>
                            <option value="this_month" selected>{{ __('This month') }}</option>
                            <option value="this_month_to_date">{{ __('This month to date') }}</option>
                            <option value="this_quarter">{{ __('This quarter') }}</option>
                            <option value="this_quarter_to_date">{{ __('This quarter to date') }}</option>
                            <option value="this_year">{{ __('This year') }}</option>
                            <option value="this_year_to_date">{{ __('This year to date') }}</option>
                            <option value="last_month">{{ __('Last month') }}</option>
                            <option value="last_year">{{ __('Last year') }}</option>
                            <option value="custom">{{ __('Custom') }}</option>
                        </select>
                    </div>

                    <div class="filter-item">
                        <label class="filter-label">{{ __('From') }}</label>
                        <input type="date" class="form-control" id="start-date"
                               value="{{ $filter['startDateRange'] ?? \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d') }}"
                               style="width:150px;">
                    </div>

                    <div class="filter-item">
                        <label class="filter-label">{{ __('To') }}</label>
                        <input type="date" class="form-control" id="end-date"
                               value="{{ $filter['endDateRange'] ?? \Carbon\Carbon::now()->format('Y-m-d') }}"
                               style="width:150px;">
                    </div>

                    {{-- View options button aligned like a field --}}
                    <div class="filter-item filter-button-item">
                        <label class="filter-label">&nbsp;</label>
                        <a href="#" class="view-options" id="view-options-btn">
                            <i class="fa fa-eye"></i> {{ __('View options') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="options-row mt-2">
                <a href="#" class="columns-btn" id="columns-btn"><i class="fas fa-columns"></i> {{ __('Columns') }}</a>
                <a href="#" class="filter-btn" id="filter-btn"><i class="fas fa-filter"></i> {{ __('Filter') }}</a>
                <a href="#" class="general-options" id="general-options-btn"><i class="fa fa-cog"></i> {{ __('General options') }}</a>
            </div>
        </div>

        {{-- Report --}}
        <div class="report-content">
            <div class="report-header report-title-section">
                <h1 class="report-title">{{ __('Purchases by Product/Service Detail') }}</h1>
                <p class="company-name">{{ \Auth::user()->name ?? config('app.name') }}</p>
                <p class="date-range">
                    <span id="display-date-range">
                        {{ __('From') }}
                        {{ \Carbon\Carbon::parse($filter['startDateRange'] ?? now())->format('F j, Y') }}
                        {{ __('to') }}
                        {{ \Carbon\Carbon::parse($filter['endDateRange'] ?? now())->format('F j, Y') }}
                    </span>
                </p>
            </div>

            <div class="table-container" id="table-visual-wrapper">
                <table class="table ledger-table" id="purchases-by-product-service-detail-table">
                    <thead>
                    <tr>
                        <th class="text-center">{{ __('Transaction Date') }}</th>
                        <th>{{ __('Transaction Type') }}</th>
                        <th>{{ __('Num') }}</th>
                        <th>{{ __('Vendor') }}</th>
                        <th>{{ __('Memo/Description') }}</th>
                        <th class="text-right">{{ __('Quantity') }}</th>
                        <th class="text-right">{{ __('Rate') }}</th>
                        <th class="text-right">{{ __('Amount') }}</th>
                        <th class="text-right">{{ __('Balance') }}</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- FILTER drawer --}}
    <div class="modal-overlay" id="filter-overlay">
        <div class="drawer">
            <div class="modal-header">
                <h5>{{ __('Filter') }}</h5>
                <button type="button" class="btn-close" id="close-filter">&times;</button>
            </div>
            <div class="modal-content">
                <div class="filter-item mb-3">
                    <label class="filter-label">{{ __('Vendor') }}</label>
                    {{ Form::select('vendor', $vendors ?? [], $filter['selectedVendorName'] ?? '', ['class' => 'form-select', 'id' => 'filter-vendor']) }}
                </div>
                <div class="filter-item mb-3">
                    <label class="filter-label">{{ __('Product/Service') }}</label>
                    <input type="text" class="form-control" id="filter-product-name" placeholder="{{ __('Product/Service Name') }}" value="{{ $filter['selectedProductName'] ?? '' }}">
                </div>
            </div>
        </div>
    </div>

    {{-- COLUMNS drawer --}}
    <div class="modal-overlay" id="columns-overlay">
        <div class="drawer">
            <div class="modal-header">
                <h5>{{ __('Columns') }}</h5>
                <button type="button" class="btn-close" id="close-columns">&times;</button>
            </div>
            <div class="modal-content">
                <div class="columns-list">
                    <div class="column-item" data-column="0"><label class="checkbox-label"><input type="checkbox" checked> {{ __('Transaction Date') }}</label></div>
                    <div class="column-item" data-column="1"><label class="checkbox-label"><input type="checkbox" checked> {{ __('Transaction Type') }}</label></div>
                    <div class="column-item" data-column="2"><label class="checkbox-label"><input type="checkbox" checked> {{ __('Num') }}</label></div>
                    <div class="column-item" data-column="3"><label class="checkbox-label"><input type="checkbox" checked> {{ __('Vendor') }}</label></div>
                    <div class="column-item" data-column="4"><label class="checkbox-label"><input type="checkbox" checked> {{ __('Memo/Description') }}</label></div>
                    <div class="column-item" data-column="5"><label class="checkbox-label"><input type="checkbox" checked> {{ __('Quantity') }}</label></div>
                    <div class="column-item" data-column="6"><label class="checkbox-label"><input type="checkbox" checked> {{ __('Rate') }}</label></div>
                    <div class="column-item" data-column="7"><label class="checkbox-label"><input type="checkbox" checked> {{ __('Amount') }}</label></div>
                    <div class="column-item" data-column="8"><label class="checkbox-label"><input type="checkbox" checked> {{ __('Balance') }}</label></div>
                </div>
            </div>
        </div>
    </div>

    {{-- GENERAL OPTIONS drawer --}}
    <div class="modal-overlay" id="general-options-overlay">
        <div class="drawer general-options-modal">
            <div class="modal-header">
                <h5>{{ __('General options') }} <i class="fa fa-info-circle" title="{{ __('Configure report settings') }}"></i></h5>
                <button type="button" class="btn-close" id="close-general-options">&times;</button>
            </div>
            <div class="modal-content">
                <p class="modal-subtitle">{{ __('Select general options for your report.') }}</p>

                <div class="option-section">
                    <h6 class="section-title">{{ __('Number format') }} <i class="fa fa-chevron-up"></i></h6>
                    <div class="option-group">
                        <label class="checkbox-label"><input type="checkbox" id="divide-by-1000"> {{ __('Divide by 1000') }}</label>
                        <label class="checkbox-label"><input type="checkbox" id="hide-zero-amounts"> {{ __("Don't show zero amounts") }}</label>
                        <label class="checkbox-label"><input type="checkbox" id="round-whole-numbers"> {{ __('Round to the nearest whole number') }}</label>
                    </div>
                </div>

                <div class="option-section">
                    <h6 class="section-title">{{ __('Negative numbers') }}</h6>
                    <div class="option-group">
                        <div class="negative-format-group">
                            <select id="negative-format" class="form-control" style="width:100px;">
                                <option value="-100" selected>-100</option>
                                <option value="(100)">(100)</option>
                                <option value="100-">100-</option>
                            </select>
                            <label class="checkbox-label"><input type="checkbox" id="show-in-red"> {{ __('Show in red') }}</label>
                        </div>
                    </div>
                </div>

                <div class="option-section">
                    <h6 class="section-title">{{ __('Header') }} <i class="fa fa-chevron-up"></i></h6>
                    <div class="option-group">
                        <label class="checkbox-label"><input type="checkbox" id="company-logo"> {{ __('Company logo') }}</label>
                        <label class="checkbox-label"><input type="checkbox" id="report-period-checkbox" checked> {{ __('Report period') }}</label>
                        <label class="checkbox-label"><input type="checkbox" id="company-name-checkbox" checked> {{ __('Company name') }}</label>
                    </div>
                    <div class="alignment-group">
                        <label class="alignment-label">{{ __('Header alignment') }}</label>
                        <select id="header-alignment" class="form-control" style="max-width:150px;">
                            <option value="center" selected>{{ __('Center') }}</option>
                            <option value="left">{{ __('Left') }}</option>
                            <option value="right">{{ __('Right') }}</option>
                        </select>
                    </div>
                </div>

                <div class="option-section">
                    <h6 class="section-title">{{ __('Footer') }} <i class="fa fa-chevron-up"></i></h6>
                    <div class="option-group">
                        <label class="checkbox-label"><input type="checkbox" id="date-prepared" checked> {{ __('Date prepared') }}</label>
                        <label class="checkbox-label"><input type="checkbox" id="time-prepared" checked> {{ __('Time prepared') }}</label>
                        <label class="checkbox-label"><input type="checkbox" id="report-basis" checked> {{ __('Report basis') }}</label>
                    </div>
                    <div class="alignment-group">
                        <label class="alignment-label">{{ __('Footer alignment') }}</label>
                        <select id="footer-alignment" class="form-control" style="max-width:150px;">
                            <option value="center" selected>{{ __('Center') }}</option>
                            <option value="left">{{ __('Left') }}</option>
                            <option value="right">{{ __('Right') }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- VIEW OPTIONS drawer --}}
    <div class="modal-overlay" id="view-options-overlay">
        <div class="drawer">
            <div class="modal-header">
                <h5>{{ __('View options') }} <i class="fa fa-info-circle" title="{{ __('Adjust how the report looks') }}"></i></h5>
                <button type="button" class="btn-close" id="close-view-options">&times;</button>
            </div>
            <div class="modal-content">
                <p class="modal-subtitle">{{ __('Choose display preferences. These do not affect data.') }}</p>

                <div class="option-section">
                    <h6 class="section-title">{{ __('Table density') }}</h6>
                    <div class="option-group">
                        <label class="checkbox-label"><input type="checkbox" id="opt-compact"> {{ __('Compact rows') }}</label>
                    </div>
                </div>

                <div class="option-section">
                    <h6 class="section-title">{{ __('Row style') }}</h6>
                    <div class="option-group">
                        <label class="checkbox-label"><input type="checkbox" id="opt-striped" checked> {{ __('Striped rows') }}</label>
                        <label class="checkbox-label"><input type="checkbox" id="opt-borders"> {{ __('Show borders') }}</label>
                        <label class="checkbox-label"><input type="checkbox" id="opt-wrap"> {{ __('Wrap long text') }}</label>
                        <label class="checkbox-label"><input type="checkbox" id="opt-sticky-head" checked> {{ __('Sticky header') }}</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/colreorder/1.7.0/js/dataTables.colReorder.min.js"></script>
    <script src="https://cdn.datatables.net/fixedheader/3.4.0/js/dataTables.fixedHeader.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/colreorder/1.7.0/css/colReOrder.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.4.0/css/fixedHeader.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

    {!! $dataTable->scripts() !!}

    <script>
        $(function () {
            // Instead of waiting for DataTable initialization, we'll hook into the DataTable events
            // This prevents double initialization while still allowing us to customize the table
            
            // Set up a flag to ensure our initialization only runs once
            let isReportInitialized = false;
            
            // Hook into DataTable initialization
            $('#purchases-by-product-service-detail-table').on('init.dt', function (e, settings) {
                if (isReportInitialized) return;
                isReportInitialized = true;
                
                // Small delay to ensure DataTable is fully ready
                setTimeout(function() {
                    initializeReport();
                }, 100);
            });
            
            // Also try to initialize after a delay in case init.dt doesn't fire
            setTimeout(function() {
                if (!isReportInitialized && $.fn.dataTable.isDataTable('#purchases-by-product-service-detail-table')) {
                    isReportInitialized = true;
                    initializeReport();
                }
            }, 500);

            function initializeReport() {
                // Get the existing DataTable instance
                const table = $('#purchases-by-product-service-detail-table').DataTable();
                
                window.reportOptions = {
                    divideBy1000: false,
                    hideZeroAmounts: false,
                    roundWholeNumbers: false,
                    negativeFormat: '-100',
                    showInRed: false,
                    companyLogo: false,
                    reportPeriod: true,
                    companyName: true,
                    headerAlignment: 'center',
                    datePrepared: true,
                    timePrepared: true,
                    reportBasis: true,
                    footerAlignment: 'center'
                };

                const $visualWrap = $('#table-visual-wrapper');
                function applyViewOptions(){
                    $visualWrap.toggleClass('table-compact', $('#opt-compact').prop('checked'));
                    $visualWrap.toggleClass('table-striped', $('#opt-striped').prop('checked'));
                    $visualWrap.toggleClass('table-wrap', $('#opt-wrap').prop('checked'));
                    $visualWrap.toggleClass('table-bordered', $('#opt-borders').prop('checked'));
                    $visualWrap.toggleClass('sticky-head', $('#opt-sticky-head').prop('checked'));
                    // Only adjust columns if table is still initialized
                    if ($.fn.dataTable.isDataTable('#purchases-by-product-service-detail-table')) {
                        table.columns.adjust().draw(false);
                    }
                }

                $('#report-period').on('change', function(){
                    const now = moment();
                    let s, e;
                    switch ($(this).val()) {
                        case 'today':              s = now.clone(); e = now.clone(); break;
                        case 'this_week':          s = now.clone().startOf('week'); e = now.clone().endOf('week'); break;
                        case 'this_week_to_date':  s = now.clone().startOf('week'); e = now.clone(); break;
                        case 'this_month':         s = now.clone().startOf('month'); e = now.clone().endOf('month'); break;
                        case 'this_month_to_date': s = now.clone().startOf('month'); e = now.clone(); break;
                        case 'this_quarter':       s = now.clone().startOf('quarter'); e = now.clone().endOf('quarter'); break;
                        case 'this_quarter_to_date': s = now.clone().startOf('quarter'); e = now.clone(); break;
                        case 'this_year':          s = now.clone().startOf('year'); e = now.clone().endOf('year'); break;
                        case 'this_year_to_date':  s = now.clone().startOf('year'); e = now.clone(); break;
                        case 'last_month':         s = now.clone().subtract(1,'month').startOf('month'); e = now.clone().subtract(1,'month').endOf('month'); break;
                        case 'last_year':          s = now.clone().subtract(1,'year').startOf('year'); e = now.clone().subtract(1,'year').endOf('year'); break;
                        case 'all_dates':          s = moment('1900-01-01'); e = now.clone(); break;
                        default: return; // custom
                    }
                    $('#start-date').val(s.format('YYYY-MM-DD'));
                    $('#end-date').val(e.format('YYYY-MM-DD'));
                    updateHeaderDate();
                    table.draw();
                });

                $('#start-date, #end-date').on('change', function(){
                    updateHeaderDate();
                    table.draw();
                });

                function updateHeaderDate(){
                    const s = new Date($('#start-date').val());
                    const e = new Date($('#end-date').val());
                    if (isNaN(s) || isNaN(e)) return;
                    const opts = {year:'numeric', month:'long', day:'numeric'};
                    $('#display-date-range').text(
                        'From ' + s.toLocaleDateString('en-US', opts) + 
                        ' to ' + e.toLocaleDateString('en-US', opts)
                    );
                }

                // Filter drawer
                $('#filter-btn').on('click', e => { e.preventDefault(); $('#filter-overlay').show(); });
                $('#close-filter, #filter-overlay').on('click', function(e){
                    if (e.target.id === 'filter-overlay' || e.target.id === 'close-filter') $('#filter-overlay').hide();
                });
                $('#filter-vendor, #filter-product-name').on('change keyup', function(){ table.draw(); });

                // Columns drawer with colReorder awareness
                function getDT(cb){
                    const tryGet = function(n){
                        const dt = $.fn.dataTable.isDataTable('#purchases-by-product-service-detail-table') ? $('#purchases-by-product-service-detail-table').DataTable() : null;
                        if (dt) cb(dt); else if (n>0) setTimeout(()=>tryGet(n-1), 100);
                    };
                    tryGet(30);
                }
                $('#columns-btn').on('click', e => { e.preventDefault(); $('#columns-overlay').show(); });
                $('#close-columns, #columns-overlay').on('click', function(e){
                    if (e.target.id === 'columns-overlay' || e.target.id === 'close-columns') $('#columns-overlay').hide();
                });
                $('.columns-list input[type="checkbox"]').on('change', function(){
                    const originalIndex = $(this).closest('.column-item').data('column');
                    const isVisible = $(this).prop('checked');
                    if (originalIndex === undefined) return;
                    getDT(function(dt){
                        const currentIndex = dt.colReorder && typeof dt.colReorder.transpose === 'function'
                            ? dt.colReorder.transpose(originalIndex, 'toCurrent')
                            : originalIndex;
                        dt.column(currentIndex).visible(isVisible, false);
                        dt.columns.adjust().draw(false);
                    });
                });

                // General options
                $('#general-options-btn').on('click', function(e){ e.preventDefault(); $('#general-options-overlay').show(); });
                $('#close-general-options, #general-options-overlay').on('click', function(e){
                    if (e.target.id === 'general-options-overlay' || e.target.id === 'close-general-options') $('#general-options-overlay').hide();
                });
                function applyGeneralOptions() {
                    window.reportOptions.divideBy1000    = $('#divide-by-1000').prop('checked');
                    window.reportOptions.hideZeroAmounts = $('#hide-zero-amounts').prop('checked');
                    window.reportOptions.roundWholeNumbers = $('#round-whole-numbers').prop('checked');
                    window.reportOptions.negativeFormat  = $('#negative-format').val();
                    window.reportOptions.showInRed       = $('#show-in-red').prop('checked');
                    window.reportOptions.companyLogo     = $('#company-logo').prop('checked');
                    window.reportOptions.reportPeriod    = $('#report-period-checkbox').prop('checked');
                    window.reportOptions.companyName     = $('#company-name-checkbox').prop('checked');
                    window.reportOptions.headerAlignment = $('#header-alignment').val();
                    window.reportOptions.datePrepared    = $('#date-prepared').prop('checked');
                    window.reportOptions.timePrepared    = $('#time-prepared').prop('checked');
                    window.reportOptions.reportBasis     = $('#report-basis').prop('checked');
                    window.reportOptions.footerAlignment = $('#footer-alignment').val();
                    applyNumberFormatting(window.reportOptions);
                    applyHeaderFooterSettings(window.reportOptions);
                    table.draw(false);
                }
                function applyNumberFormatting(options) {
                    $('#custom-number-format').remove();
                    let css = '<style id="custom-number-format">';
                    if (options.showInRed) css += '.negative-amount{color:#dc2626!important}';
                    if (options.hideZeroAmounts) css += '.zero-amount{display:none!important}';
                    css += '</style>';
                    $('head').append(css);
                }
                function applyHeaderFooterSettings(options) {
                    $('.report-title-section').css('text-align', options.headerAlignment);
                    $('.company-name').toggle(!!options.companyName);
                    $('.date-range').toggle(!!options.reportPeriod);
                    if (!$('.report-footer').length) {
                        $('.report-content').append('<div class="report-footer" style="padding:12px 20px;border-top:1px solid #e6e6e6;font-size:12px;color:#6b7280;text-align:'+options.footerAlignment+';"></div>');
                    }
                    const now = new Date();
                    const parts = [];
                    if (options.reportBasis) parts.push('{{ __('Accrual basis') }}');
                    if (options.datePrepared || options.timePrepared) {
                        const dt = now.toLocaleString('en-US', {year:'numeric',month:'long',day:'numeric',hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:false,timeZoneName:'shortOffset'});
                        parts.push('| ' + dt);
                    }
                    $('.report-footer').css('text-align', options.footerAlignment).html(parts.join(' '));
                }
                $('.general-options-modal input, .general-options-modal select').on('change', applyGeneralOptions);

                // View options
                $('#view-options-btn').on('click', function(e){ e.preventDefault(); $('#view-options-overlay').show(); });
                $('#close-view-options, #view-options-overlay').on('click', function(e){
                    if (e.target.id === 'view-options-overlay' || e.target.id === 'close-view-options') $('#view-options-overlay').hide();
                });
                $('#view-options-overlay input[type="checkbox"]').on('change', function(){
                    applyViewOptions(); resizeInventoryDT();
                });

                // Cell styling helpers
                $('#purchases-by-product-service-detail-table').on('draw.dt', function(){
                    $('#purchases-by-product-service-detail-table tbody tr').each(function(){
                        $(this).find('td').each(function(){
                            const txt = ($(this).text() || '').trim();
                            if (!txt) return;
                            if (/^-/.test(txt) || /\((.*?)\)/.test(txt) || /-$/.test(txt)) $(this).addClass('negative-amount');
                            if (txt === '0' || txt === '0.0' || txt === '0.00' || txt === '{{ \Auth::user()->priceFormat(0) }}') $(this).addClass('zero-amount');
                        });
                    });
                });

                // Initial apply
                applyGeneralOptions();
                applyViewOptions();
                updateHeaderDate();

                // Print
                $('#print-btn').on('click', function(){ window.print(); });

                // Keep DT within viewport
                function resizeInventoryDT(){
                    // Check if table is still initialized
                    if (!$.fn.dataTable.isDataTable('#purchases-by-product-service-detail-table')) return;
                    
                    const hHeader   = $('.header-section').outerHeight(true)    || 0;
                    const hFilter   = $('.filter-section').outerHeight(true)    || 0;
                    const hReportHd = $('.report-title-section').outerHeight(true) || 0;
                    const padding   = 140;
                    const available = Math.max(280, window.innerHeight - (hHeader + hFilter + hReportHd + padding));
                    table.settings()[0].oScroll.sY = available + 'px';
                    $(table.table().container()).find('.dataTables_scrollBody').css({'max-height': available + 'px','height': available + 'px'});
                    table.columns.adjust().draw(false);
                }
                resizeInventoryDT();
                let _rsTimer; $(window).on('resize', function(){ clearTimeout(_rsTimer); _rsTimer = setTimeout(resizeInventoryDT, 120); });
            }
        });

        function refreshData(){ 
            if ($.fn.dataTable.isDataTable('#purchases-by-product-service-detail-table')) {
                $('#purchases-by-product-service-detail-table').DataTable().ajax.reload(null,false); 
            }
        }
    </script>
@endpush