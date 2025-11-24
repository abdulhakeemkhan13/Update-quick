@extends('layouts.admin')

@section('page-title')
    {{ __('Deposit Detail') }}
@endsection

@push('script-page')
    <!-- jQuery (include if not already in your layout footer) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- DataTables + extensions -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.4.0/css/fixedHeader.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/colreorder/1.7.0/css/colReOrder.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/fixedheader/3.4.0/js/dataTables.fixedHeader.min.js"></script>
    <script src="https://cdn.datatables.net/colreorder/1.7.0/js/dataTables.colReorder.min.js"></script>

    <!-- Sortable for drag-reorder in Columns drawer -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
@endpush

@section('content')
    <style>
        /* ===== Base / Layout ===== */
        .quickbooks-report {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            min-height: 100vh;
            color: #262626;
        }

        /* Header with actions */
        .report-header {
            background: #fff;
            padding: 16px 24px;
            border-bottom: 1px solid #e6e6e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .report-header h4 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .last-updated {
            color: #6b7280;
            font-size: 13px;
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn {
            border: none;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 13px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: .2s;
        }

        .btn-icon {
            background: transparent;
            color: #6b7280;
            width: 32px;
            height: 32px;
            justify-content: center;
        }

        .btn-icon:hover {
            background: #f3f4f6;
            color: #262626;
        }

        .btn-success {
            background: #22c55e;
            color: #fff;
            font-weight: 500;
        }

        .btn-success:hover {
            background: #16a34a;
        }

        .btn-save {
            padding: 8px 16px;
        }

        /* Controls row */
        .controls-bar {
            background: #fff;
            padding: 10px 24px;
            border-bottom: 1px solid #e6e6e6;
        }

        .controls-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            flex-wrap: nowrap;
        }

        .left-controls {
            display: flex;
            align-items: center !important;
            gap: 12px;
            flex-wrap: nowrap;
            min-width: 0;
        }

        .right-controls {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-left: auto;
        }

        .filter-item {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .filter-label {
            font-size: 11px;
            color: #374151;
            margin-bottom: 2px;
            font-weight: 500;
            white-space: nowrap;
        }

        .form-select,
        .form-control {
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 6px 8px;
            font-size: 13px;
            height: 32px;
            background: #fff;
            color: #374151;
        }

        .form-select:focus,
        .form-control:focus {
            outline: none;
            border-color: #0066cc;
            box-shadow: 0 0 0 2px rgba(0, 102, 204, .2);
        }

        .btn-qb-option {
            background: transparent;
            border: none;
            color: #0066cc;
            padding: 6px 10px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            transition: .15s;
            white-space: nowrap;
        }

        .btn-qb-option:hover {
            background: #f0f7ff;
            color: #0052a3;
        }

        .btn-qb-option i {
            margin-right: 4px;
            font-size: 12px;
        }

        .btn-qb-action {
            background: transparent;
            border: none;
            color: #6b7280;
            padding: 6px 10px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            border-radius: 4px;
            white-space: nowrap;
            transition: .15s;
        }

        .btn-qb-action:hover {
            background: #f3f4f6;
            color: #374151;
        }

        .btn-qb-action i {
            margin-right: 4px;
            font-size: 12px;
        }

        /* Report content */
        .report-content {
            background: #fff;
            margin: 24px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .1);
            overflow: hidden;
        }

        .report-title-section {
            text-align: center;
            padding: 32px 24px 24px;
            border-bottom: 1px solid #e6e6e6;
        }

        .report-title {
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 8px;
        }

        .company-name {
            font-size: 16px;
            color: #6b7280;
            margin: 0 0 12px;
        }

        .date-range {
            font-size: 14px;
            color: #374151;
            margin: 0;
        }

        /* Table */
        .table-container {
            background: #fff;
            max-height: 520px;
            overflow: auto;
        }

        .deposit-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .deposit-table th {
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .025em;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .deposit-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f3f4f6;
            color: #262626;
            vertical-align: middle;
        }

        .deposit-table tbody tr:hover {
            background: #f9fafb;
        }

        .text-right {
            text-align: right;
        }

        /* Drawers (modals) */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .5);
            z-index: 1050;
            overflow-y: auto;
        }

        .filter-modal,
        .general-options-modal,
        .columns-modal,
        .view-options-modal {
            background: #fff;
            margin: 50px auto;
            width: 90%;
            max-width: 600px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .3);
        }

        .modal-header {
            padding: 20px 25px 15px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h5 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
        }

        .btn-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #999;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-close:hover {
            color: #666;
        }

        .modal-content {
            padding: 20px 25px 25px;
        }

        .modal-subtitle {
            color: #666;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .filter-group {
            margin-bottom: 20px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #2c3e50;
            font-size: 13px;
        }

        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
            background: #fff;
            color: #262626;
            height: 36px;
        }

        /* Option sections */
        .option-section {
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
            border-radius: 4px;
        }

        .section-title {
            background: #f8f9fa;
            padding: 12px 15px;
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: #2c3e50;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e9ecef;
        }

        .option-group {
            padding: 15px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            font-size: 13px;
            color: #2c3e50;
            cursor: pointer;
        }

        .checkbox-label input[type="checkbox"] {
            margin-right: 8px;
            width: auto;
        }

        .modal-overlay.drawer-open {
            display: block;
            background: rgba(0, 0, 0, .5);
        }

        .modal-overlay.drawer-open .filter-modal,
        .modal-overlay.drawer-open .general-options-modal,
        .modal-overlay.drawer-open .columns-modal,
        .modal-overlay.drawer-open .view-options-modal {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            height: 100%;
            width: 360px;
            max-width: 90vw;
            margin: 0;
            border-radius: 0;
            box-shadow: -2px 0 10px rgba(0, 0, 0, .1);
            overflow-y: auto;
            animation: slideInRight .18s ease-out;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(20px);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Columns drawer pills */
        .qb-columns-title {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
        }

        .qb-columns-help {
            color: #6b7280;
            font-size: 13px;
            margin: 8px 0 16px;
        }

        #qb-columns-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .qb-col-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 6px;
            border-radius: 6px;
        }

        .qb-col-item:hover {
            background: #f8fafc;
        }

        .qb-handle {
            color: #9ca3af;
            width: 18px;
            text-align: center;
            cursor: grab;
        }

        .qb-handle:active {
            cursor: grabbing;
        }

        .qb-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            user-select: none;
        }

        .qb-pill input {
            position: absolute;
            left: -9999px;
        }

        .qb-pill .pill {
            width: 22px;
            height: 22px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #d1d5db;
            background: #fff;
        }

        .qb-pill .pill i {
            font-size: 12px;
            color: #fff;
            opacity: 0;
            transition: opacity .12s ease;
        }

        .qb-pill input:checked+.pill {
            background: #22c55e;
            border-color: #16a34a;
        }

        .qb-pill input:checked+.pill i {
            opacity: 1;
        }

        .qb-col-name {
            font-size: 14px;
            color: #111827;
        }

        .qb-ghost {
            opacity: .6;
            background: #eef2ff;
        }

        .qb-chosen {
            background: #f1f5f9;
        }

        /* Print */
        @media print {

            .report-header,
            .controls-bar {
                display: none !important;
            }

            .quickbooks-report {
                background: #fff !important;
            }

            .report-content {
                box-shadow: none !important;
                margin: 0 !important;
            }

            .deposit-table {
                font-size: 11px;
            }

            .deposit-table th,
            .deposit-table td {
                padding: 6px 4px;
            }
        }
    </style>

    <div class="quickbooks-report">
        <!-- Header with actions -->
        <div class="report-header">
            <h4 class="mb-0">{{ __('Deposit Detail') }}</h4>
            <div class="header-actions">
                <span class="last-updated">Last updated just now</span>
                <div class="actions">
                    <button class="btn btn-icon" title="Refresh" id="btn-refresh"><i class="fa fa-sync"></i></button>
                    <button class="btn btn-icon"
                        onclick="exportDataTable('deposit-detail-table', '{{ __('Deposit Detail') }}', 'print')"><i
                            class="fa fa-print"></i></button>
                    <button class="btn btn-icon" title="Export" id="btn-export"><i
                            class="fa fa-external-link-alt"></i></button>
                    <button class="btn btn-icon" title="More options" id="btn-more"><i
                            class="fa fa-ellipsis-v"></i></button>
                    <button class="btn btn-success btn-save" id="btn-save">Save As</button>
                </div>
            </div>
        </div>

        <!-- Bootstrap Modal -->
        <div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content p-0">
                    <div class="modal-header">
                        <h5 class="modal-title">Choose Export Format</h5> <button type="button" class="btn-close"
                            data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center row">
                        <div class="col-md-6">
                            <button onclick="exportDataTable('deposit-detail-table', '{{ __('Deposit Detail') }}')"
                                class="btn btn-success mx-auto w-75 justify-content-center text-center"
                                data-action="excel">Export to
                                Excel</button>
                        </div>
                        <div class="col-md-6">
                            <button
                                onclick="exportDataTable('deposit-detail-table', '{{ __('Deposit Detail') }}', 'pdf')"
                                class="btn btn-success mx-auto w-75 justify-content-center text-center"
                                data-action="pdf">Export to
                                PDF</button>
                        </div>
                        {{-- <button class="btn btn-success mx-auto w-50 text-center" data-action="csv">Export to CSV</button> --}}
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Show modal on export button click
            $('.btn-icon[title="Export"]').on('click', function() {
                $('#exportModal').modal('show');
            });

            // Handle export actions
            $('#exportModal button[data-action]').on('click', function() {
                // Hide modal after action
                $('#exportModal').modal('hide');
            });
        </script>

        <script>
            let csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            {{-- console.log([window.Header, window.footerAlignment]) --}}

            function exportDataTable(tableId, pageTitle, format = 'excel') {
                let table = $('#' + tableId).DataTable();

                // Only get visible columns (skip auto-index)
                let columns = [];
                $('#' + tableId + ' thead th:visible').each(function() {
                    columns.push($(this).text().trim());
                });

                // Get visible data rows
                let data = [];

                const getRealtimeTableData = () => {

                    let data = [];


                    table.rows({
                        search: 'applied'
                    }).every(function() {
                        let rowData = this.data();

                        if (typeof rowData === 'object') {
                            // Only keep values for visible columns
                            let rowArray = [];
                            table.columns(':visible').every(function(colIdx) {
                                let val = rowData[this.dataSrc()] ?? '-';
                                rowArray.push(val);
                            });
                            rowData = rowArray;
                        }
                        data.push(rowData);
                    });

                    return data

                }

                // Get visible data rows (rendered DOM text, not raw data)
                $('#' + tableId + ' tbody tr:visible').each(function() {
                    let rowArray = [];
                    $(this).find('td:visible').each(function() {
                        rowArray.push($(this).text().trim());
                    });
                    data.push(rowArray);
                });



                // Send to universal export route
                $.ajax({
                    url: '{{ route('export.datatable') }}',
                    method: 'POST',
                    data: {
                        columns: columns,
                        data: data,
                        pageTitle: pageTitle,
                        ReportPeriod: window.reportOptions.reportPeriod ? $(".report-title-section .date-range")[0]
                            .textContent : "",
                        HeaderFooterAlignment: [window.reportOptions.headerAlignment, window.reportOptions
                            .footerAlignment
                        ],
                        format: format,
                        _token: '{{ csrf_token() }}'
                    },
                    xhrFields: {
                        responseType: 'blob'
                    },
                    success: function(blob, status, xhr) {
                        let filename = xhr.getResponseHeader('Content-Disposition')
                            .split('filename=')[1]
                            .replace(/"/g, ''); //"

                        if (format === "print") {
                            let fileURL = URL.createObjectURL(blob);
                            let printWindow = window.open(fileURL);
                            printWindow.onload = function() {
                                printWindow.focus();
                                printWindow.print();
                            };
                        } else {
                            let link = document.createElement('a');
                            link.href = window.URL.createObjectURL(blob);
                            link.download = filename;
                            link.click();
                        }
                    },
                    error: function(xhr) {
                        console.error('Export failed:', xhr.responseText);
                        alert('Export failed! Check console.');
                    }
                });
            }
        </script>

        <!-- Controls row -->
        <div class="controls-bar">
            <div class="controls-inner">
                <div class="left-controls">
                    <div class="filter-item">
                        <label class="filter-label">Report period</label>
                        <select class="form-select" id="report-period" style="width: 160px;">
                            <option value="all_dates">All Dates</option>
                            <option value="today">Today</option>
                            <option value="this_week">This week</option>
                            <option value="this_month">This month</option>
                            <option value="this_quarter">This quarter</option>
                            <option value="this_year">This year</option>
                            <option value="last_week">Last week</option>
                            <option value="last_month">Last month</option>
                            <option value="last_quarter">Last quarter</option>
                            <option value="last_year">Last year</option>
                            <option value="last_7_days">Last 7 days</option>
                            <option value="last_30_days">Last 30 days</option>
                            <option value="last_90_days">Last 90 days</option>
                            <option value="last_12_months">Last 12 months</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>

                    <div class="filter-item">
                        <label class="filter-label">Accounting method</label>
                        <select class="form-select" id="accounting-method" style="width: 120px;">
                            <option value="accrual" selected>Accrual</option>
                            <option value="cash">Cash</option>
                        </select>
                    </div>

                    <button class="btn btn-qb-option" id="view-options-btn">
                        <i class="fa fa-eye"></i> View options
                    </button>
                </div>

                <div class="right-controls">
                    <button class="btn btn-qb-action" id="columns-btn"><i class="fa fa-table-columns"></i> Columns</button>
                    <button class="btn btn-qb-action" id="filter-btn"><i class="fa fa-filter"></i> Filter</button>
                    <button class="btn btn-qb-action" id="general-options-btn"><i class="fa fa-cog"></i> General
                        options</button>
                </div>
            </div>
        </div>

        <!-- Report -->
        <div class="report-content">
            <div class="report-title-section">
                <h2 class="report-title">{{ __('Deposit Detail') }}</h2>
                <p class="company-name">{{ Auth::user()->name ?? config('app.name', 'Your Company') }}</p>
                <p class="date-range"><span id="date-range-display">All Dates</span></p>
            </div>

            <div class="table-container">
                {!! $dataTable->table(['class' => 'table deposit-table', 'id' => 'deposit-detail-table']) !!}
            </div>
        </div>
    </div>

    {{-- ===== Filter Drawer ===== --}}
    <div class="modal-overlay" id="filter-overlay">
        <div class="filter-modal">
            <div class="modal-header">
                <h5>Filter <i class="fa fa-info-circle" title="Filter by customer or vendor"></i></h5>
                <button type="button" class="btn-close" id="close-filter">&times;</button>
            </div>
            <div class="modal-content">
                <p class="modal-subtitle">Updates apply immediately.</p>

                <div class="filter-group">
                    <label for="start-date">From</label>
                    <input type="date" id="start-date" class="form-control"
                        value="{{ request('start_date') ?? '' }}">
                </div>

                <div class="filter-group">
                    <label for="end-date">To</label>
                    <input type="date" id="end-date" class="form-control" value="{{ request('end_date') ?? '' }}">
                </div>

                <div class="filter-group">
                    <label for="filter-customer-name">Customer Name</label>
                    <select id="filter-customer-name" class="form-control">
                        <option value="">All Customers</option>
                        @foreach ($customers as $customerName)
                            <option value="{{ $customerName }}"
                                {{ (request('customer_name') ?? '') == $customerName ? 'selected' : '' }}>
                                {{ $customerName }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label for="filter-vendor-name">Vendor Name</label>
                    <select id="filter-vendor-name" class="form-control">
                        <option value="">All Vendors</option>
                        @foreach ($vendors as $vendorName)
                            <option value="{{ $vendorName }}"
                                {{ (request('vendor_name') ?? '') == $vendorName ? 'selected' : '' }}>{{ $vendorName }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== General Options Drawer ===== --}}
    <div class="modal-overlay" id="general-options-overlay">
        <div class="general-options-modal">
            <div class="modal-header">
                <h5>General options <i class="fa fa-info-circle" title="Configure report settings"></i></h5>
                <button type="button" class="btn-close" id="close-general-options">&times;</button>
            </div>
            <div class="modal-content">
                <p class="modal-subtitle">Select general options for your report.</p>

                <!-- Number format -->
                <div class="option-section">
                    <h6 class="section-title">Number format <i class="fa fa-chevron-up"></i></h6>
                    <div class="option-group">
                        <label class="checkbox-label"><input type="checkbox" id="divide-by-1000"> Divide by 1000</label>
                        <label class="checkbox-label"><input type="checkbox" id="hide-zero-amounts"> Don't show zero
                            amounts</label>
                        <label class="checkbox-label"><input type="checkbox" id="round-whole-numbers"> Round to whole
                            numbers</label>
                    </div>
                </div>

                <!-- Negative numbers -->
                <div class="option-section">
                    <h6 class="section-title">Negative numbers <i class="fa fa-chevron-up"></i></h6>
                    <div class="option-group">
                        <div style="display:flex; gap:12px; align-items:center;">
                            <label class="checkbox-label" style="margin:0;">
                                <select id="negative-format" class="form-control" style="width:110px;">
                                    <option value="-100" selected>-100</option>
                                    <option value="(100)">(100)</option>
                                    <option value="100-">100-</option>
                                </select>
                            </label>
                            <label class="checkbox-label" style="margin:0;">
                                <input type="checkbox" id="show-in-red"> Show in red
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Header -->
                <div class="option-section">
                    <h6 class="section-title">Header <i class="fa fa-chevron-up"></i></h6>
                    <div class="option-group">
                        <label class="checkbox-label"><input type="checkbox" id="company-logo"> Company logo</label>
                        <label class="checkbox-label"><input type="checkbox" id="opt-report-title" checked> Report
                            title</label>
                        <label class="checkbox-label"><input type="checkbox" id="opt-company-name" checked> Company
                            name</label>
                        <label class="checkbox-label"><input type="checkbox" id="opt-report-period" checked> Report
                            period</label>
                        <div class="alignment-group" style="margin-top:8px;">
                            <label class="alignment-label">Header alignment</label>
                            <select id="header-alignment" class="form-control" style="max-width:180px;">
                                <option value="center" selected>Center</option>
                                <option value="left">Left</option>
                                <option value="right">Right</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="option-section">
                    <h6 class="section-title">Footer <i class="fa fa-chevron-up"></i></h6>
                    <div class="option-group">
                        <label class="checkbox-label"><input type="checkbox" id="date-prepared" checked> Date
                            prepared</label>
                        <label class="checkbox-label"><input type="checkbox" id="time-prepared" checked> Time
                            prepared</label>
                        <label class="checkbox-label"><input type="checkbox" id="show-report-basis" checked> Report
                            basis</label>

                        <div style="display:flex; gap:12px; align-items:center;">
                            <span style="min-width:110px;">Basis</span>
                            <select id="report-basis" class="form-control" style="max-width:180px;">
                                <option value="Accrual" selected>Accrual</option>
                                <option value="Cash">Cash</option>
                            </select>
                        </div>

                        <div class="alignment-group" style="margin-top:8px;">
                            <label class="alignment-label">Footer alignment</label>
                            <select id="footer-alignment" class="form-control" style="max-width:180px;">
                                <option value="center" selected>Center</option>
                                <option value="left">Left</option>
                                <option value="right">Right</option>
                            </select>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer"
                style="padding:15px 25px;border-top:1px solid #e9ecef;display:flex;justify-content:flex-end;gap:10px;">
                <button type="button" class="btn btn-cancel" id="cancel-general-options"
                    style="background:#f8f9fa;color:#666;border:1px solid #ddd;">Cancel</button>
                <button type="button" class="btn btn-apply" id="apply-general-options"
                    style="background:#0066cc;color:#fff;border:1px solid #0066cc;">Apply</button>
            </div>
        </div>
    </div>

    {{-- ===== View Options Drawer ===== --}}
    <div class="modal-overlay" id="view-options-overlay">
        <div class="view-options-modal">
            <div class="modal-header">
                <h5>View options <i class="fa fa-info-circle" title="Adjust how the report looks"></i></h5>
                <button type="button" class="btn-close" id="close-view-options">&times;</button>
            </div>
            <div class="modal-content">
                <p class="modal-subtitle">Choose display preferences. These do not affect data.</p>

                <div class="option-section">
                    <h6 class="section-title">Table density</h6>
                    <div class="option-group">
                        <label class="checkbox-label"><input type="checkbox" id="opt-compact"> Compact rows</label>
                        <label class="checkbox-label"><input type="checkbox" id="opt-hover" checked> Row hover
                            effects</label>
                    </div>
                </div>

                <div class="option-section">
                    <h6 class="section-title">Row style</h6>
                    <div class="option-group">
                        <label class="checkbox-label"><input type="checkbox" id="opt-striped" checked> Striped
                            rows</label>
                        <label class="checkbox-label"><input type="checkbox" id="opt-borders"> Show borders</label>
                        <label class="checkbox-label"><input type="checkbox" id="opt-wrap"> Wrap long text</label>
                        <label class="checkbox-label"><input type="checkbox" id="opt-sticky-head" checked> Sticky
                            header</label>
                    </div>
                </div>

                <div class="option-section">
                    <h6 class="section-title">Column width</h6>
                    <div class="option-group">
                        <label class="checkbox-label"><input type="checkbox" id="opt-auto-width" checked> Auto-fit
                            columns</label>
                        <label class="checkbox-label"><input type="checkbox" id="opt-equal-width"> Equal column
                            widths</label>
                    </div>
                </div>

                <div class="option-section">
                    <h6 class="section-title">Font size</h6>
                    <div class="option-group">
                        <label class="checkbox-label" style="gap:12px;">
                            <span>Table font size</span>
                            <select id="font-size" class="form-control" style="width:160px;">
                                <option value="11px">Small (11px)</option>
                                <option value="13px" selected>Normal (13px)</option>
                                <option value="15px">Large (15px)</option>
                                <option value="17px">Extra Large (17px)</option>
                            </select>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Columns Drawer ===== --}}
    <div class="modal-overlay" id="columns-overlay">
        <div class="columns-modal">
            <div class="modal-header">
                <h5 class="qb-columns-title">Columns</h5>
                <button type="button" class="btn-close" id="close-columns" aria-label="Close">&times;</button>
            </div>
            <div class="modal-content">
                <div class="qb-columns-help">
                    Add, remove and reorder the columns.<br>Drag columns to reorder.
                </div>
                <ul id="qb-columns-list"><!-- built dynamically --></ul>
            </div>
        </div>
    </div>

    <script>
        $(function() {
            const TABLE_ID = 'deposit-detail-table';

            function dt() {
                return window.LaravelDataTables && window.LaravelDataTables[TABLE_ID];
            }

            /* ===== Last updated ticker ===== */
            const $last = $('.last-updated');
            let lastUpdatedAt = Date.now(),
                tickerId = null;

            function rel(ts) {
                const s = Math.floor((Date.now() - ts) / 1000);
                if (s < 5) return 'just now';
                if (s < 60) return `${s} seconds ago`;
                const m = Math.floor(s / 60);
                if (m < 60) return m === 1 ? '1 minute ago' : `${m} minutes ago`;
                const h = Math.floor(m / 60);
                if (h < 24) return h === 1 ? '1 hour ago' : `${h} hours ago`;
                const d = Math.floor(h / 24);
                return d === 1 ? '1 day ago' : `${d} days ago`;
            }

            function renderLast() {
                $last.text(`Last updated ${rel(lastUpdatedAt)}`);
            }

            function markNow() {
                lastUpdatedAt = Date.now();
                renderLast();
                if (tickerId) clearInterval(tickerId);
                tickerId = setInterval(renderLast, 30_000);
            }
            markNow();

            /* ===== Numeric helpers ===== */
            function parseNum(v) {
                if (v === null || v === undefined) return 0;
                if (typeof v === 'number') return v;
                let s = String(v).trim();
                if (!s) return 0;
                let neg = false;
                if (s.startsWith('(') && s.endsWith(')')) {
                    neg = true;
                    s = s.slice(1, -1);
                }
                s = s.replace(/[\$€£,\s]/g, '');
                if (s.endsWith('-')) {
                    neg = true;
                    s = s.slice(0, -1);
                }
                const n = parseFloat(s.replace(/[^0-9.\-]/g, '')) || 0;
                return neg ? -Math.abs(n) : n;
            }
            window.reportOptions = {
                divideBy1000: false,
                hideZeroAmounts: false,
                roundWholeNumbers: false,
                negativeFormat: '-100',
                showInRed: false,
                companyLogo: false,
                reportTitle: true,
                companyName: true,
                reportPeriod: true,
                headerAlignment: 'center',
                datePrepared: true,
                timePrepared: true,
                showReportBasis: true,
                reportBasis: 'Accrual',
                footerAlignment: 'center'
            };

            function formatAmount(raw, isMoney = true) {
                const o = window.reportOptions || {};
                let val = parseNum(raw);
                if (o.divideBy1000) val /= 1000;
                if (o.hideZeroAmounts && Math.abs(val) < 1e-12) return {
                    html: '',
                    classes: 'zero-amount'
                };
                const frac = o.roundWholeNumbers ? 0 : 2;
                const absText = Math.abs(val).toLocaleString('en-US', {
                    minimumFractionDigits: frac,
                    maximumFractionDigits: frac
                });
                const isNeg = val < 0,
                    negFmt = o.negativeFormat || '-100';
                let core = absText;
                if (isNeg) {
                    if (negFmt === '(100)') core = `(${absText})`;
                    else if (negFmt === '100-') core = `${absText}-`;
                    else core = `-${absText}`;
                }
                let html = core;
                if (isMoney) {
                    if (isNeg && negFmt === '(100)') html = `($ ${absText})`;
                    else if (isNeg && negFmt === '100-') html = `$ ${absText}-`;
                    else if (isNeg && negFmt === '-100') html = `-$ ${absText}`;
                    else html = `$ ${absText}`;
                }
                return {
                    html,
                    classes: (isNeg && o.showInRed) ? 'negative-amount' : ''
                };
            }

            function numberCSS(opts) {
                $('#custom-number-format').remove();
                let css = '<style id="custom-number-format">';
                if (opts.showInRed) css += '.negative-amount{color:#dc2626!important;}';
                if (opts.hideZeroAmounts) css += '.zero-amount{display:none!important;}';
                css += '</style>';
                $('head').append(css);
            }

            /* ===== Header/Footer rendering ===== */
            function headerApply(opts) {
                $('.report-title')[opts.reportTitle ? 'show' : 'hide']();
                $('.company-name')[opts.companyName ? 'show' : 'hide']();
                $('.date-range')[opts.reportPeriod ? 'show' : 'hide']();
                $('.report-title-section').css('text-align', opts.headerAlignment || 'center');
            }

            function ensureFooter() {
                if ($('.report-footer').length) return;
                $('.report-content').append(
                    '<div class="report-footer" style="padding:20px;border-top:1px solid #e6e6e6;text-align:center;font-size:12px;color:#6b7280;"></div>'
                );
            }

            function footerRender(opts) {
                ensureFooter();
                const now = new Date();
                const parts = [];
                if (opts.datePrepared) parts.push(`Date Prepared: ${now.toLocaleDateString()}`);
                if (opts.timePrepared) parts.push(`Time Prepared: ${now.toLocaleTimeString()}`);
                if (opts.showReportBasis) parts.push(`Report Basis: ${opts.reportBasis} Basis`);
                $('.report-footer').css('text-align', opts.footerAlignment || 'center').html(parts.map(p =>
                    `<div>${p}</div>`).join(''));
            }

            /* ===== Apply General Options ===== */
            function applyNumericReformat() {
                const $table = $(`#${TABLE_ID}`);
                if (!$table.length) return;
                const moneyHeaders = ['amount', 'balance', 'deposit', 'payment', 'total', 'tax'];
                const qtyHeaders = ['qty', 'quantity'];
                const headerTexts = [];
                $table.find('thead th').each(function(i) {
                    headerTexts[i] = ($(this).text() || '').trim().toLowerCase();
                });
                $table.find('tbody tr').each(function() {
                    $(this).children('td').each(function(i) {
                        const h = headerTexts[i] || '';
                        const isMoney = moneyHeaders.some(k => h.includes(k));
                        const isQty = qtyHeaders.some(k => h.includes(k));
                        if (!isMoney && !isQty) return;
                        const raw = $(this).text();
                        const out = formatAmount(raw, isMoney);
                        $(this).html(`<span class="${out.classes}">${out.html}</span>`).addClass(
                            'text-right');
                    });
                });
            }

            function applyGeneralOptions(triggerRedraw = true) {
                const o = window.reportOptions;
                o.divideBy1000 = $('#divide-by-1000').prop('checked') || false;
                o.hideZeroAmounts = $('#hide-zero-amounts').prop('checked') || false;
                o.roundWholeNumbers = $('#round-whole-numbers').prop('checked') || false;
                o.negativeFormat = $('#negative-format').val() || '-100';
                o.showInRed = $('#show-in-red').prop('checked') || false;

                o.companyLogo = $('#company-logo').prop('checked') || false;
                o.reportTitle = $('#opt-report-title').prop('checked') !== false;
                o.companyName = $('#opt-company-name').prop('checked') !== false;
                o.reportPeriod = $('#opt-report-period').prop('checked') !== false;
                o.headerAlignment = $('#header-alignment').val() || 'center';

                o.datePrepared = $('#date-prepared').prop('checked') !== false;
                o.timePrepared = $('#time-prepared').prop('checked') !== false;
                o.showReportBasis = $('#show-report-basis').prop('checked') !== false;
                o.reportBasis = $('#report-basis').val() || 'Accrual';
                o.footerAlignment = $('#footer-alignment').val() || 'center';

                numberCSS(o);
                headerApply(o);
                footerRender(o);
                applyNumericReformat();
                if (triggerRedraw && dt()) dt().draw(false);
            }

            /* ===== View Options ===== */
            function applyViewOptions() {
                $('#custom-view-styles').remove();
                let css = '<style id="custom-view-styles">';
                css += $('#opt-compact').prop('checked') ?
                    '.deposit-table th,.deposit-table td{padding:8px 12px;}' :
                    '.deposit-table th,.deposit-table td{padding:12px 16px;}';
                css += $('#opt-hover').prop('checked') ? '.deposit-table tbody tr:hover{background:#f9fafb;}' :
                    '.deposit-table tbody tr:hover{background:inherit;}';
                if ($('#opt-striped').prop('checked')) css +=
                    '.deposit-table tbody tr:nth-child(even){background-color:#f8f9fa;}';
                css += $('#opt-borders').prop('checked') ?
                    '.deposit-table th,.deposit-table td{border:1px solid #e5e7eb;}' :
                    '.deposit-table th,.deposit-table td{border:none;border-bottom:1px solid #f3f4f6;}';
                css += $('#opt-wrap').prop('checked') ?
                    '.deposit-table th,.deposit-table td{white-space:normal;word-wrap:break-word;}' :
                    '.deposit-table th,.deposit-table td{white-space:nowrap;}';
                css += $('#opt-auto-width').prop('checked') ? '.deposit-table{table-layout:auto;}' :
                    '.deposit-table{table-layout:fixed;}';
                if ($('#opt-equal-width').prop('checked')) css += '.deposit-table th,.deposit-table td{width:10%;}';
                const fs = $('#font-size').val() || '13px';
                css += `.deposit-table, .deposit-table th, .deposit-table td{font-size:${fs};}`;
                css += '</style>';
                $('head').append(css);
            }

            /* ===== Drawers open/close ===== */
            $('#view-options-btn').on('click', () => $('#view-options-overlay').addClass('drawer-open'));
            $('#columns-btn').on('click', () => {
                buildColumnsList();
                $('#columns-overlay').addClass('drawer-open');
            });
            $('#filter-btn').on('click', () => $('#filter-overlay').addClass('drawer-open'));
            $('#general-options-btn').on('click', () => $('#general-options-overlay').addClass('drawer-open'));

            $('#close-filter').on('click', () => $('#filter-overlay').removeClass('drawer-open'));
            $('#close-general-options, #cancel-general-options').on('click', () => $('#general-options-overlay')
                .removeClass('drawer-open'));
            $('#close-view-options').on('click', () => $('#view-options-overlay').removeClass('drawer-open'));
            $('#close-columns').on('click', () => $('#columns-overlay').removeClass('drawer-open'));
            $('.modal-overlay').on('click', function(e) {
                if (e.target === this) $(this).removeClass('drawer-open');
            });
            $(document).on('keydown', e => {
                if (e.key === 'Escape') $('.modal-overlay').removeClass('drawer-open');
            });

            /* ===== Filters / Period ===== */
            function updateHeaderDate() {
                const s = $('#start-date').val(),
                    e = $('#end-date').val();
                if (!s || !e) return;
                const so = new Date(s),
                    eo = new Date(e);
                const opt = {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                };
                $('#date-range-display').text(so.toLocaleDateString('en-US', opt) + ' - ' + eo.toLocaleDateString(
                    'en-US', opt));
            }

            function pushUrlAndReload() {
                const url = new URL(window.location);
                const period = $('#report-period').val();
                const start = $('#start-date').val();
                const end = $('#end-date').val();
                const acct = $('#accounting-method').val();
                const cust = $('#filter-customer-name').val();
                const vend = $('#filter-vendor-name').val();
                if (period && period !== 'all_dates') url.searchParams.set('report_period', period);
                else url.searchParams.delete('report_period');
                if (start) url.searchParams.set('start_date', start);
                else url.searchParams.delete('start_date');
                if (end) url.searchParams.set('end_date', end);
                else url.searchParams.delete('end_date');
                if (acct && acct !== 'accrual') url.searchParams.set('accounting_method', acct);
                else url.searchParams.delete('accounting_method');
                if (cust) url.searchParams.set('customer_name', cust);
                else url.searchParams.delete('customer_name');
                if (vend) url.searchParams.set('vendor_name', vend);
                else url.searchParams.delete('vendor_name');
                window.history.replaceState({}, '', url);
                if (dt()) dt().draw();
                markNow();
            }

            function handlePeriodChange() {
                const period = $('#report-period').val();
                const today = new Date();
                let startDate = '',
                    endDate = '';
                const dcopy = d => new Date(d.getTime());
                switch (period) {
                    case 'today':
                        startDate = endDate = today.toISOString().split('T')[0];
                        break;
                    case 'this_week': {
                        const t = dcopy(today);
                        const start = new Date(t.setDate(t.getDate() - t.getDay()));
                        const end = new Date(start.getFullYear(), start.getMonth(), start.getDate() + 6);
                        startDate = start.toISOString().split('T')[0];
                        endDate = end.toISOString().split('T')[0];
                    }
                    break;
                    case 'this_month':
                        startDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
                        endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().split('T')[
                            0];
                        break;
                    case 'this_quarter': {
                        const q = Math.floor(today.getMonth() / 3);
                        startDate = new Date(today.getFullYear(), q * 3, 1).toISOString().split('T')[0];
                        endDate = new Date(today.getFullYear(), q * 3 + 3, 0).toISOString().split('T')[0];
                    }
                    break;
                    case 'this_year':
                        startDate = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
                        endDate = new Date(today.getFullYear(), 11, 31).toISOString().split('T')[0];
                        break;
                    case 'last_week': {
                        const t = dcopy(today);
                        const start = new Date(t.setDate(t.getDate() - t.getDay() - 7));
                        const end = new Date(start.getFullYear(), start.getMonth(), start.getDate() + 6);
                        startDate = start.toISOString().split('T')[0];
                        endDate = end.toISOString().split('T')[0];
                    }
                    break;
                    case 'last_month':
                        startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1).toISOString().split('T')[
                            0];
                        endDate = new Date(today.getFullYear(), today.getMonth(), 0).toISOString().split('T')[0];
                        break;
                    case 'last_quarter': {
                        let q = Math.floor(today.getMonth() / 3) - 1;
                        const year = q < 0 ? today.getFullYear() - 1 : today.getFullYear();
                        const adjQ = (q + 4) % 4;
                        startDate = new Date(year, adjQ * 3, 1).toISOString().split('T')[0];
                        endDate = new Date(year, adjQ * 3 + 3, 0).toISOString().split('T')[0];
                    }
                    break;
                    case 'last_year':
                        startDate = new Date(today.getFullYear() - 1, 0, 1).toISOString().split('T')[0];
                        endDate = new Date(today.getFullYear() - 1, 11, 31).toISOString().split('T')[0];
                        break;
                    case 'last_7_days':
                        startDate = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 6)
                            .toISOString().split('T')[0];
                        endDate = new Date().toISOString().split('T')[0];
                        break;
                    case 'last_30_days':
                        startDate = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 29)
                            .toISOString().split('T')[0];
                        endDate = new Date().toISOString().split('T')[0];
                        break;
                    case 'last_90_days':
                        startDate = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 89)
                            .toISOString().split('T')[0];
                        endDate = new Date().toISOString().split('T')[0];
                        break;
                    case 'last_12_months': {
                        const s = new Date(today.getFullYear(), today.getMonth() - 11, 1);
                        const e = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                        startDate = s.toISOString().split('T')[0];
                        endDate = e.toISOString().split('T')[0];
                    }
                    break;
                    case 'all_dates':
                        startDate = '';
                        endDate = '';
                        $('#date-range-display').text('All Dates');
                        pushUrlAndReload();
                        return;
                    case 'custom':
                    default:
                        return;
                }
                $('#start-date').val(startDate);
                $('#end-date').val(endDate);
                updateHeaderDate();
                pushUrlAndReload();
            }
            $('#report-period').on('change', handlePeriodChange);
            $('#start-date, #end-date, #accounting-method, #filter-customer-name, #filter-vendor-name').on('change',
                function() {
                    updateHeaderDate();
                    pushUrlAndReload();
                });

            /* ===== Columns Drawer ===== */
            function transposeIndex(orig) {
                const api = dt();
                return (api && api.colReorder && typeof api.colReorder.transpose === 'function') ? api.colReorder
                    .transpose(orig, 'toCurrent') : orig;
            }

            function buildColumnsList() {
                const api = dt();
                if (!api) return;
                const $list = $('#qb-columns-list');
                $list.empty();
                const headers = api.columns().header().toArray();
                headers.forEach((h, origIdx) => {
                    const curIdx = transposeIndex(origIdx),
                        visible = api.column(curIdx).visible();
                    const name = ($(h).text() || '').trim() || `Column ${origIdx+1}`;
                    $list.append(
                        `<li class="qb-col-item" data-column="${origIdx}">
                    <span class="qb-handle"><i class="fa fa-grip-vertical"></i></span>
                    <label class="qb-pill">
                        <input type="checkbox" data-col="${origIdx}" ${visible?'checked':''}>
                        <span class="pill"><i class="fa fa-check"></i></span>
                        <span class="qb-col-name">${name}</span>
                    </label>
                </li>`
                    );
                });
                if (window.Sortable && document.getElementById('qb-columns-list')) {
                    new Sortable(document.getElementById('qb-columns-list'), {
                        animation: 150,
                        handle: '.qb-handle',
                        chosenClass: 'qb-chosen',
                        ghostClass: 'qb-ghost',
                        onEnd: function() {
                            const newOrder = $('#qb-columns-list .qb-col-item').map(function() {
                                return parseInt($(this).attr('data-column'), 10);
                            }).get();
                            if (api && api.colReorder && typeof api.colReorder.order === 'function') {
                                try {
                                    api.colReorder.order(newOrder, true);
                                    localStorage.setItem('deposit-detail-col-order', JSON.stringify(
                                        newOrder));
                                    api.columns.adjust().draw(false);
                                } catch (e) {}
                            }
                        }
                    });
                }
                $list.off('change').on('change', 'input[type="checkbox"][data-col]', function() {
                    const origIndex = parseInt($(this).data('col'), 10);
                    const curIndex = transposeIndex(origIndex);
                    const visible = $(this).is(':checked');
                    try {
                        api.column(curIndex).visible(visible, false);
                        api.columns.adjust().draw(false);
                    } catch (e) {}
                });
            }

            /* ===== Header actions ===== */
            $('#btn-refresh').on('click', function() {
                $(this).find('i').addClass('fa-spin');
                if (dt()) dt().ajax.reload();
            });
            // $('#btn-print').on('click', () => window.print());
            // $('#btn-export').on('click', () => alert('Export action triggered'));
            $('#btn-more').on('click', () => alert('More options clicked'));
            $('#btn-save').on('click', function() {
                const name = prompt('Enter report name:', 'Deposit Detail - ' + new Date().toISOString()
                    .slice(0, 10));
                if (name) alert('Report "' + name + '" would be saved with current settings.');
            });

            /* ===== Bind drawers/live changes ===== */
            $('#apply-general-options').on('click', function() {
                applyGeneralOptions(true);
                $('#general-options-overlay').removeClass('drawer-open');
            });
            $('#cancel-general-options').on('click', function() {
                $('#general-options-overlay').removeClass('drawer-open');
            });
            $('.general-options-modal').on('change', 'input,select', function() {
                applyGeneralOptions(false);
            });
            $('#view-options-overlay').on('change', 'input,select', applyViewOptions);

            /* ===== Re-apply on DataTables lifecycle ===== */
            $(document).on('xhr.dt', `#${TABLE_ID}`, function() {
                markNow();
                $('#btn-refresh i').removeClass('fa-spin');
                applyGeneralOptions(false);
                applyViewOptions();
                footerRender(window.reportOptions);
            });
            $(document).on('draw.dt', `#${TABLE_ID}`, function() {
                applyNumericReformat();
            });

            /* ===== Initial paint ===== */
            setTimeout(function() {
                applyGeneralOptions(false);
                applyViewOptions();
                footerRender(window.reportOptions);
            }, 120);
        });
    </script>

    {!! $dataTable->scripts() !!}
@endsection
