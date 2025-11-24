@extends('layouts.admin')

@push('script-page')
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.4.0/css/fixedHeader.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/colreorder/1.7.0/css/colReOrder.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.css">

    <!-- JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/fixedheader/3.4.0/js/dataTables.fixedHeader.min.js"></script>
    <script src="https://cdn.datatables.net/colreorder/1.7.0/js/dataTables.colReorder.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.min.js"></script>
@endpush

@section('content')
    <style>
        /* ===== Skin (same as your working pages) ===== */
        .quickbooks-report {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            min-height: 100vh;
            color: #262626;
        }

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

        .controls-bar {
            background: #fff;
            padding: 10px 24px;
            border-bottom: 1px solid #e6e6e6;
            overflow: hidden;
        }

        .controls-inner {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: nowrap;
            max-width: 100%;
        }

        .left-controls {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: nowrap;
            min-width: 0;
        }

        .right-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: auto;
            white-space: nowrap;
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
            font-size: 12px;
            height: 32px;
            background: #fff;
            color: #374151;
        }

        .form-select:focus,
        .form-control:focus {
            outline: none;
            border-color: #0066cc;
            box-shadow: 0 0 0 2px rgba(0, 102, 204, 0.2);
        }

        .btn-qb-action,
        .btn-qb-option {
            background: transparent;
            border: none;
            color: #3c4043;
            padding: 6px 10px;
            font-size: 13px;
            font-weight: 500;
            border-radius: 4px;
        }

        /* no borders */
        .btn-qb-action:hover,
        .btn-qb-option:hover {
            background: #f3f4f6;
            color: #374151;
        }

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

        .table-container {
            background: #fff;
            overflow: auto;
        }

        .sales-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            table-layout: fixed;
        }

        /* fixed widths -> header/body/tfoot align */
        .sales-table th {
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

        .sales-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f3f4f6;
            color: #262626;
            vertical-align: middle;
        }

        .sales-table tfoot td {
            background: #f8f9fa;
            border-top: 2px solid #e5e7eb;
            font-weight: 700;
        }

        .sales-table tbody tr:hover {
            background: #f9fafb;
        }

        .text-right {
            text-align: right;
        }

        .report-footer {
            padding: 20px;
            border-top: 1px solid #e6e6e6;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
        }

        /* Drawers (Filter / General / View / Columns) */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .5);
            z-index: 1050;
            overflow-y: auto;
        }

        .modal-overlay.drawer-open {
            display: block;
        }

        .filter-modal,
        .general-options-modal,
        .columns-modal,
        .view-options-modal {
            background: #fff;
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
                opacity: 0
            }

            to {
                transform: translateX(0);
                opacity: 1
            }
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
            margin-bottom: 16px;
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

        /* Option sections (no borders request → remove outline, keep header band) */
        .option-section {
            margin-bottom: 16px;
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
            border: 1px solid #e9ecef;
            border-radius: 6px;
        }

        .option-group {
            padding: 12px 4px 6px 4px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            font-size: 13px;
            color: #2c3e50;
            cursor: pointer;
        }

        /* Columns drawer */
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
            padding: 8px 6px;
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

        .pill {
            width: 22px;
            height: 22px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #d1d5db;
            background: #fff;
        }

        .pill i {
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

        /* Add to your existing CSS */
        .qb-ghost {
            opacity: 0.6;
            background: #eef2ff;
        }

        .qb-chosen {
            background: #f1f5f9;
        }

        .checkbox-label input[type="checkbox"] {
            margin-right: 8px;
            width: auto;
        }

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

            .sales-table {
                font-size: 11px;
            }

            .sales-table th,
            .sales-table td {
                padding: 6px 4px;
            }
        }

        @media (max-width: 1020px) {
            .controls-bar {
                overflow-x: auto
            }

            .controls-inner {
                min-width: max-content
            }
        }
    </style>

    <div class="quickbooks-report">
        <!-- Header -->
        <div class="report-header">
            <h4 class="mb-0">{{ $pageTitle }}</h4>
            <div class="header-actions">
                <span class="last-updated">Last updated just now</span>
                <div class="actions">
                    <button class="btn btn-icon" title="Refresh" id="btn-refresh"><i class="fa fa-sync"></i></button>
                    <button class="btn btn-icon"
                        onclick="exportDataTable('sales-by-customer-table', '{{ __('Sales by Customer Summary') }}', 'print')"><i
                            class="fa fa-print"></i></button>
                    <button class="btn btn-icon" title="Export" id="btn-export"><i
                            class="fa fa-external-link-alt"></i></button>
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
                            <button onclick="exportDataTable('sales-by-customer-table', '{{ __('Sales by Customer Summary') }}')"
                                class="btn btn-success mx-auto w-75 justify-content-center text-center"
                                data-action="excel">Export to
                                Excel</button>
                        </div>
                        <div class="col-md-6">
                            <button
                                onclick="exportDataTable('sales-by-customer-table', '{{ __('Sales by Customer Summary') }}', 'pdf')"
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

        <!-- Controls -->
        <div class="controls-bar">
            <div class="controls-inner">
                <div class="left-controls">
                    <div class="filter-item">
                        <label class="filter-label">Report period</label>
                        <select class="form-select" id="report-period" style="width:160px;">
                            <option value="all_dates" selected>All Dates</option>
                            <option value="today">Today</option>
                            <option value="this_week">This week</option>
                            <option value="this_month">This month</option>
                            <option value="this_quarter">This quarter</option>
                            <option value="this_year">This year</option>
                            <option value="last_week">Last week</option>
                            <option value="last_month">Last month</option>
                            <option value="last_quarter">Last quarter</option>
                            <option value="last_year">Last year</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>

                    <div class="filter-item" id="custom-date-range" style="display:none;">
                        <label class="filter-label">Date range</label>
                        <input type="text" class="form-control" id="date-range-picker" style="width:155px;"
                            placeholder="Select date range">
                    </div>

                    <div class="filter-item">
                        <label class="filter-label">Accounting method</label>
                        <select class="form-select" id="accounting-method" style="width:150px;">
                            <option value="Accrual" selected>Accrual</option>
                            <option value="Cash">Cash</option>
                        </select>
                    </div>

                    <!-- View options button sits here -->
                    <button class="btn btn-qb-option mt-4" style="border-left: 2px solid #d1d5db" id="view-options-btn"><i
                            class="fa fa-eye"></i> View options</button>
                </div>

                <div class="right-controls mt-4">
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
                <h2 class="report-title">{{ $pageTitle }}</h2>
                <p class="company-name">{{ config('app.name', 'Your Company Name') }}</p>
                <p class="date-range"><span id="date-range-display">All Dates</span></p>
            </div>

            <div class="table-container">
                {!! $dataTable->table(['class' => 'table sales-table', 'id' => 'sales-by-customer-table']) !!}
                {{-- We will inject a <tfoot> inside this same table via JS to keep perfect alignment --}}
            </div>

            <div class="report-footer"></div>
        </div>
    </div>

    <!-- ==== Drawers ==== -->
    <!-- Filter -->
    <div class="modal-overlay" id="filter-overlay">
        <div class="filter-modal">
            <div class="modal-header">
                <h5>Filter</h5>
                <button type="button" class="btn-close" id="close-filter">&times;</button>
            </div>
            <div class="modal-content">
                <p class="modal-subtitle">Choose filters to narrow down the report. Updates immediately.</p>
                <div class="filter-group">
                    <label for="filter-customer-name">Customer Name</label>
                    <select id="filter-customer-name" class="form-control">
                        <option value="">All Customers</option>
                        @foreach ($customers as $customerName)
                            <option value="{{ $customerName }}"
                                {{ ($filter['selectedCustomerName'] ?? '') == $customerName ? 'selected' : '' }}>
                                {{ $customerName }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- General -->
    <div class="modal-overlay" id="general-options-overlay">
        <div class="general-options-modal">
            <div class="modal-header">
                <h5>General options</h5>
                <button type="button" class="btn-close" id="close-general-options">&times;</button>
            </div>
            <div class="modal-content">
                <p class="modal-subtitle">Select general options for your report.</p>

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

                <div class="option-section">
                    <h6 class="section-title">Negative numbers <i class="fa fa-chevron-up"></i></h6>
                    <div class="option-group" style="display:flex;gap:12px;align-items:center;">
                        <select id="negative-format" class="form-control" style="max-width:130px;">
                            <option value="-100" selected>-100</option>
                            <option value="(100)">(100)</option>
                            <option value="100-">100-</option>
                        </select>
                        <label class="checkbox-label" style="margin:0;"><input type="checkbox" id="show-in-red"> Show in
                            red</label>
                    </div>
                </div>

                <div class="option-section">
                    <h6 class="section-title">Header <i class="fa fa-chevron-up"></i></h6>
                    <div class="option-group">
                        <label class="checkbox-label"><input type="checkbox" id="opt-report-title" checked> Report
                            title</label>
                        <label class="checkbox-label"><input type="checkbox" id="opt-company-name" checked> Company
                            name</label>
                        <label class="checkbox-label"><input type="checkbox" id="opt-report-period" checked> Report
                            period</label>
                        <div style="margin-top:8px;">
                            <label class="filter-label">Header alignment</label>
                            <select id="header-alignment" class="form-control" style="max-width:180px;">
                                <option value="center" selected>Center</option>
                                <option value="left">Left</option>
                                <option value="right">Right</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="option-section">
                    <h6 class="section-title">Footer <i class="fa fa-chevron-up"></i></h6>
                    <div class="option-group">
                        <label class="checkbox-label"><input type="checkbox" id="date-prepared" checked> Date
                            prepared</label>
                        <label class="checkbox-label"><input type="checkbox" id="time-prepared" checked> Time
                            prepared</label>
                        <label class="checkbox-label"><input type="checkbox" id="show-report-basis" checked> Report
                            basis</label>
                        <div style="display:flex;gap:12px;align-items:center;margin-top:6px;">
                            <span style="min-width:70px;">Basis</span>
                            <select id="report-basis" class="form-control" style="max-width:180px;">
                                <option value="Accrual" selected>Accrual</option>
                                <option value="Cash">Cash</option>
                            </select>
                        </div>
                        <div style="margin-top:8px;">
                            <label class="filter-label">Footer alignment</label>
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
                <button type="button" class="btn" id="cancel-general-options"
                    style="background:#f8f9fa;color:#666;">Cancel</button>
                <button type="button" class="btn" id="apply-general-options"
                    style="background:#0066cc;color:#fff;">Apply</button>
            </div>
        </div>
    </div>

    <!-- View options (same look) -->
    <div class="modal-overlay" id="view-options-overlay">
        <div class="view-options-modal">
            <div class="modal-header">
                <h5>View options</h5>
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
                    <div class="option-group" style="display:flex;align-items:center;gap:12px;">
                        <span>Table font size</span>
                        <select id="font-size" class="form-control" style="width:160px;">
                            <option value="11px">Small (11px)</option>
                            <option value="13px" selected>Normal (13px)</option>
                            <option value="15px">Large (15px)</option>
                            <option value="17px">Extra Large (17px)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Columns -->
    <div class="modal-overlay" id="columns-overlay">
        <div class="columns-modal">
            <div class="modal-header">
                <h5>Columns</h5>
                <button type="button" class="btn-close" id="close-columns">&times;</button>
            </div>
            <div class="modal-content">
                <div class="qb-columns-help">Add, remove and reorder the columns. Drag to reorder.</div>
                <ul id="qb-columns-list"><!-- built from thead dynamically --></ul>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
    {!! $dataTable->scripts() !!}

    <script>
        $(function() {
            /* ===== Last updated ===== */
            const $last = $('.last-updated');
            let lastAt = Date.now(),
                tick = null;
            const rel = ts => {
                const s = Math.floor((Date.now() - ts) / 1000);
                if (s < 5) return 'just now';
                if (s < 60) return `${s} seconds ago`;
                const m = Math.floor(s / 60);
                if (m < 60) return m === 1 ? '1 minute ago' : `${m} minutes ago`;
                const h = Math.floor(m / 60);
                if (h < 24) return h === 1 ? '1 hour ago' : `${h} hours ago`;
                const d = Math.floor(h / 24);
                return d === 1 ? '1 day ago' : `${d} days ago`;
            };

            function mark() {
                lastAt = Date.now();
                $last.text(`Last updated ${rel(lastAt)}`);
                if (tick) clearInterval(tick);
                tick = setInterval(() => {
                    $last.text(`Last updated ${rel(lastAt)}`)
                }, 30000);
            }
            mark();

            /* ===== Drawer helpers ===== */
            const openOvr = id => $(id).addClass('drawer-open');
            const closeOvr = id => $(id).removeClass('drawer-open');
            $('#filter-btn').on('click', () => openOvr('#filter-overlay'));
            $('#general-options-btn').on('click', () => openOvr('#general-options-overlay'));
            $('#columns-btn').on('click', () => {
                buildColumnsList();
                openOvr('#columns-overlay');
            });
            $('#view-options-btn').on('click', () => openOvr('#view-options-overlay'));
            $('#close-filter').on('click', () => closeOvr('#filter-overlay'));
            $('#close-general-options,#cancel-general-options').on('click', () => closeOvr(
                '#general-options-overlay'));
            $('#close-view-options').on('click', () => closeOvr('#view-options-overlay'));
            $('#close-columns').on('click', () => closeOvr('#columns-overlay'));
            $('.modal-overlay').on('click', function(e) {
                if (e.target === this) closeOvr('#' + this.id);
            });
            $(document).on('keydown', e => {
                if (e.key === 'Escape') $('.modal-overlay').removeClass('drawer-open');
            });

            /* ===== Header actions ===== */
            // $('#btn-print').on('click', () => window.print());
            // $('#btn-export').on('click', () => alert('Export action triggered'));
            $('#btn-save').on('click', function() {
                const name = prompt('Enter report name:', '{{ $pageTitle }} - ' + new Date()
                    .toISOString().slice(0, 10));
                if (name) alert('Report "' + name + '" would be saved with current settings.');
            });
            $('#btn-refresh').on('click', function() {
                $(this).find('i').addClass('fa-spin');
                dtApi() && dtApi().ajax.reload(null, false);
            });

            /* ===== Global options + helpers ===== */
            window.reportOptions = {
                divideBy1000: false,
                hideZeroAmounts: false,
                roundWholeNumbers: false,
                negativeFormat: '-100',
                showInRed: false,
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

            function numberCSS(o) {
                $('#custom-number-format').remove();
                let css = '<style id="custom-number-format">';
                if (o.showInRed) css += '.negative-amount{color:#dc2626!important;}';
                if (o.hideZeroAmounts) css += '.zero-amount{display:none!important;}';
                css += '</style>';
                $('head').append(css);
            }

            function headerApply(o) {
                $('.report-title')[o.reportTitle ? 'show' : 'hide']();
                $('.company-name')[o.companyName ? 'show' : 'hide']();
                $('.date-range')[o.reportPeriod ? 'show' : 'hide']();
                $('.report-title-section').css('text-align', o.headerAlignment || 'center');
            }

            function footerApply(o) {
                const now = new Date();
                const parts = [];
                if (o.datePrepared) parts.push(`Date Prepared: ${now.toLocaleDateString()}`);
                if (o.timePrepared) parts.push(`Time Prepared: ${now.toLocaleTimeString()}`);
                if (o.showReportBasis) parts.push(`Report Basis: ${o.reportBasis} Basis`);
                $('.report-footer').css('text-align', o.footerAlignment || 'center').html(parts.map(p =>
                    `<div>${p}</div>`).join(''));
            }
            numberCSS(window.reportOptions);
            headerApply(window.reportOptions);
            footerApply(window.reportOptions);

            function applyGeneral() {
                const o = window.reportOptions;
                o.divideBy1000 = $('#divide-by-1000').prop('checked');
                o.hideZeroAmounts = $('#hide-zero-amounts').prop('checked');
                o.roundWholeNumbers = $('#round-whole-numbers').prop('checked');
                o.negativeFormat = $('#negative-format').val();
                o.showInRed = $('#show-in-red').prop('checked');
                o.reportTitle = $('#opt-report-title').prop('checked');
                o.companyName = $('#opt-company-name').prop('checked');
                o.reportPeriod = $('#opt-report-period').prop('checked');
                o.headerAlignment = $('#header-alignment').val();
                o.datePrepared = $('#date-prepared').prop('checked');
                o.timePrepared = $('#time-prepared').prop('checked');
                o.showReportBasis = $('#show-report-basis').prop('checked');
                o.reportBasis = $('#report-basis').val();
                o.footerAlignment = $('#footer-alignment').val();
                numberCSS(o);
                headerApply(o);
                footerApply(o);
                reformatVisibleNumericCells();
            }
            $('#apply-general-options').on('click', function() {
                applyGeneral();
                closeOvr('#general-options-overlay');
            });
            $('#general-options-overlay input,#general-options-overlay select').on('change', applyGeneral);

            /* ===== View options (same design, live CSS) ===== */
            function applyView() {
                $('#custom-view-styles').remove();
                let css = '<style id="custom-view-styles">';
                css += $('#opt-compact').prop('checked') ? '.sales-table th,.sales-table td{padding:8px 12px;}' :
                    '.sales-table th,.sales-table td{padding:12px 16px;}';
                css += $('#opt-hover').prop('checked') ? '.sales-table tbody tr:hover{background:#f9fafb;}' :
                    '.sales-table tbody tr:hover{background:inherit;}';
                if ($('#opt-striped').prop('checked')) css +=
                    '.sales-table tbody tr:nth-child(even){background-color:#f8f9fa;}';
                css += $('#opt-borders').prop('checked') ?
                    '.sales-table th,.sales-table td{border:1px solid #e5e7eb;}' :
                    '.sales-table th,.sales-table td{border:none;border-bottom:1px solid #f3f4f6;}';
                css += $('#opt-wrap').prop('checked') ?
                    '.sales-table th,.sales-table td{white-space:normal;word-wrap:break-word;}' :
                    '.sales-table th,.sales-table td{white-space:nowrap;}';
                css += $('#opt-auto-width').prop('checked') ? '.sales-table{table-layout:auto;}' :
                    '.sales-table{table-layout:fixed;}';
                if ($('#opt-equal-width').prop('checked')) css += '.sales-table th,.sales-table td{width:10%;}';
                const fs = $('#font-size').val();
                css += `.sales-table, .sales-table th, .sales-table td{font-size:${fs};}`;
                css += '</style>';
                $('head').append(css);
            }
            $('#view-options-overlay input,#view-options-overlay select').on('change', applyView);
            applyView();

            /* ===== Period & date range ===== */
            function dtApi() {
                return window.LaravelDataTables && window.LaravelDataTables["sales-by-customer-table"];
            }

            function updateHeaderDate(start, end) {
                if (!start || !end) return;
                const so = new Date(start),
                    eo = new Date(end),
                    opt = {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    };
                $('#date-range-display').text(so.toLocaleDateString('en-US', opt) + ' - ' + eo.toLocaleDateString(
                    'en-US', opt));
            }

            function setPeriod(period) {
                const t = new Date();
                let s = '',
                    e = '';
                const iso = d => d.toISOString().split('T')[0];
                switch (period) {
                    case 'today':
                        s = e = iso(t);
                        break;
                    case 'this_week': {
                        const d = new Date(t);
                        const start = new Date(d.setDate(d.getDate() - d.getDay()));
                        const end = new Date(start.getFullYear(), start.getMonth(), start.getDate() + 6);
                        s = iso(start);
                        e = iso(end);
                    }
                    break;
                    case 'this_month':
                        s = iso(new Date(t.getFullYear(), t.getMonth(), 1));
                        e = iso(new Date(t.getFullYear(), t.getMonth() + 1, 0));
                        break;
                    case 'this_quarter': {
                        const q = Math.floor(t.getMonth() / 3);
                        s = iso(new Date(t.getFullYear(), q * 3, 1));
                        e = iso(new Date(t.getFullYear(), q * 3 + 3, 0));
                    }
                    break;
                    case 'this_year':
                        s = iso(new Date(t.getFullYear(), 0, 1));
                        e = iso(new Date(t.getFullYear(), 11, 31));
                        break;
                    case 'last_week': {
                        const d = new Date(t);
                        const start = new Date(d.setDate(d.getDate() - d.getDay() - 7));
                        const end = new Date(start.getFullYear(), start.getMonth(), start.getDate() + 6);
                        s = iso(start);
                        e = iso(end);
                    }
                    break;
                    case 'last_month':
                        s = iso(new Date(t.getFullYear(), t.getMonth() - 1, 1));
                        e = iso(new Date(t.getFullYear(), t.getMonth(), 0));
                        break;
                    case 'last_quarter': {
                        let q = Math.floor(t.getMonth() / 3) - 1;
                        const Y = q < 0 ? t.getFullYear() - 1 : t.getFullYear();
                        q = (q + 4) % 4;
                        s = iso(new Date(Y, q * 3, 1));
                        e = iso(new Date(Y, q * 3 + 3, 0));
                    }
                    break;
                    case 'last_year':
                        s = iso(new Date(t.getFullYear() - 1, 0, 1));
                        e = iso(new Date(t.getFullYear() - 1, 11, 31));
                        break;
                    case 'all_dates':
                    default:
                        s = '2000-01-01';
                        e = iso(new Date());
                        break;
                }
                updateHeaderDate(s, e);
                reloadWithParams({
                    start: s,
                    end: e
                });
            }
            $('#report-period').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('#custom-date-range').show();
                } else {
                    $('#custom-date-range').hide();
                    setPeriod($(this).val());
                }
            });
            if (typeof $.fn.daterangepicker !== 'undefined') {
                $('#date-range-picker').daterangepicker({
                    opens: 'left',
                    locale: {
                        format: 'YYYY-MM-DD'
                    }
                }, function(start, end) {
                    const s = start.format('YYYY-MM-DD'),
                        e = end.format('YYYY-MM-DD');
                    updateHeaderDate(s, e);
                    reloadWithParams({
                        start: s,
                        end: e
                    });
                });
            }
            $('#accounting-method').on('change', function() {
                window.reportOptions.reportBasis = $(this).val();
                footerApply(window.reportOptions);
                reloadWithParams();
            });

            /* ===== Numbers formatting ===== */
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
                s = s.replace(/[\$\u20AC\u00A3,\s]/g, '');
                if (s.endsWith('-')) {
                    neg = true;
                    s = s.slice(0, -1);
                }
                const n = parseFloat(s.replace(/[^0-9.\-]/g, '')) || 0;
                return neg ? -Math.abs(n) : n;
            }

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
                    fmt = o.negativeFormat || '-100';
                let core = absText;
                if (isNeg) {
                    if (fmt === '(100)') core = `(${absText})`;
                    else if (fmt === '100-') core = `${absText}-`;
                    else core = `-${absText}`;
                }
                let html = core;
                if (isMoney) {
                    if (isNeg && fmt === '(100)') html = `($ ${absText})`;
                    else if (isNeg && fmt === '100-') html = `$ ${absText}-`;
                    else if (isNeg && fmt === '-100') html = `-$ ${absText}`;
                    else html = `$ ${absText}`;
                }
                return {
                    html,
                    classes: (isNeg && o.showInRed) ? 'negative-amount' : ''
                };
            }

            function reformatVisibleNumericCells() {
                const dt = dtApi();
                if (!dt) return;
                // Right-align numeric looking columns
                dt.columns().every(function() {
                    const title = (this.header().textContent || '').toLowerCase();
                    const isNum = /(qty|quantity|amount|total|sales|revenue|balance|price|cogs|margin|%)/
                        .test(title);
                    if (isNum) $(this.nodes()).addClass('text-right');
                    // format values if money-like
                    const moneyLike = /(amount|total|sales|revenue|balance|price|cogs|margin)/.test(title);
                    if (moneyLike) {
                        this.nodes().to$().each((_, td) => {
                            const out = formatAmount($(td).text(), true);
                            $(td).html(`<span class="${out.classes}">${out.html}</span>`);
                        });
                    }
                });
                // format tfoot last cell as money if exists
                const $tfoot = $('#sales-by-customer-table tfoot td');
                if ($tfoot.length > 0) {
                    const last = $tfoot.last();
                    const out = formatAmount(last.text(), true);
                    last.html(`<span class="${out.classes}">${out.html}</span>`).addClass('text-right');
                }
            }

            /* ===== Build/Update totals in SAME table -> alignment fixed ===== */
            function updateTotalsRow(totalSales) {
                const table = $('#sales-by-customer-table');
                // ensure tfoot exists with correct colspans
                const cols = table.find('thead th:visible').length || table.find('thead th').length;
                let $tfoot = table.find('tfoot');
                if (!$tfoot.length) {
                    $tfoot = $('<tfoot/>').appendTo(table);
                }
                const leftColspan = Math.max(1, cols - 1);
                const user = @json(Auth::user());
                const cur = (user && user.currency) ? user.currency : 'USD';
                const formatted = new Intl.NumberFormat('en-US', {
                    style: 'currency',
                    currency: cur
                }).format(totalSales || 0);

                $tfoot.html(`
            <tr class="total-row">
                <td colspan="${leftColspan}" style="padding:12px 16px;">TOTAL</td>
                <td class="text-right" style="padding:12px 16px;">${formatted}</td>
            </tr>
        `);
            }

            /* ===== DataTables hooks ===== */
            $('#sales-by-customer-table').on('xhr.dt', function() {
                mark();
                $('#btn-refresh i').removeClass('fa-spin');
                const dt = dtApi();
                if (dt && dt.ajax && dt.ajax.json && dt.ajax.json()) {
                    const json = dt.ajax.json();
                    if (json && json.totals && json.totals.total_sales != null) {
                        updateTotalsRow(json.totals.total_sales);
                    } else {
                        // fallback: sum visible "Total" column if present
                        const totalIdx = findColumnIndexByTitle(/total/i);
                        if (totalIdx > -1) {
                            let sum = 0;
                            dt.column(totalIdx, {
                                filter: 'applied'
                            }).data().each(v => {
                                sum += parseNum(v);
                            });
                            updateTotalsRow(sum);
                        }
                    }
                    reformatVisibleNumericCells();
                }
            });

            const waitFor = (cond, cb, tries = 40) => {
                const id = setInterval(() => {
                    if (cond()) {
                        clearInterval(id);
                        cb();
                    } else if (--tries <= 0) {
                        clearInterval(id)
                    }
                }, 100);
            };
            waitFor(() => !!dtApi(), () => {
                const dt = dtApi();
                try {
                    new $.fn.dataTable.FixedHeader(dt, {
                        header: true,
                        footer: false
                    });
                } catch (e) {}
                dt.on('draw', function() {
                    reformatVisibleNumericCells();
                    syncColumnsChecks();
                    fixTfootColspan();
                });
                dt.draw(false);
            });

            function findColumnIndexByTitle(regex) {
                const headers = $('#sales-by-customer-table thead th');
                for (let i = 0; i < headers.length; i++) {
                    if (regex.test(headers.eq(i).text().trim())) return i;
                }
                return -1;
            }

            function fixTfootColspan() {
                const table = $('#sales-by-customer-table');
                const cols = table.find('thead th:visible').length || table.find('thead th').length;
                const $tf = table.find('tfoot tr td');
                if ($tf.length === 2) {
                    const needLeft = Math.max(1, cols - 1);
                    $tf.first().attr('colspan', needLeft);
                }
            }

            /* ===== FIXED: Columns drawer (dynamic) - Proper Yajra handling ===== */
            function buildColumnsList() {
                const dt = dtApi();
                if (!dt) {
                    console.error('DataTable not initialized');
                    return;
                }

                const $list = $('#qb-columns-list');
                $list.empty();

                // Get current column order (visible indexes)
                const columnCount = dt.columns().count();
                const currentOrder = [];

                // Build list based on current visible order
                for (let i = 0; i < columnCount; i++) {
                    const header = $(dt.column(i).header());
                    const title = header.text().trim() || ('Column ' + (i + 1));
                    const visible = dt.column(i).visible();

                    $list.append(`
                <li class="qb-col-item" data-column="${i}">
                    <span class="qb-handle"><i class="fa fa-grip-vertical"></i></span>
                    <label class="qb-pill">
                        <input type="checkbox" data-col="${i}" ${visible?'checked':''}>
                        <span class="pill"><i class="fa fa-check"></i></span>
                        <span class="qb-col-name">${title}</span>
                    </label>
                </li>
            `);

                    currentOrder.push(i);
                }

                // Initialize Sortable if not already done
                if ($list[0] && !$list.data('sortable')) {
                    new Sortable($list[0], {
                        animation: 150,
                        handle: '.qb-handle',
                        ghostClass: 'qb-ghost',
                        chosenClass: 'qb-chosen',
                        onEnd: function(evt) {
                            const newOrder = [];
                            $('#qb-columns-list .qb-col-item').each(function() {
                                newOrder.push(parseInt($(this).attr('data-column'), 10));
                            });

                            const dt = dtApi();
                            if (!dt) return;

                            try {
                                // Reorder columns using DataTables API
                                dt.colReorder.order(newOrder, false);
                                dt.columns.adjust().draw(false);
                                reformatVisibleNumericCells();
                                fixTfootColspan();
                            } catch (e) {
                                console.error('Error reordering columns:', e);
                            }
                        }
                    });
                    $list.data('sortable', true);
                }

                syncColumnsChecks();
            }

            function syncColumnsChecks() {
                const dt = dtApi();
                if (!dt) return;

                $('#qb-columns-list input[data-col]').each(function() {
                    const index = parseInt($(this).data('col'), 10);
                    $(this).prop('checked', dt.column(index).visible());
                });
            }

            // Fixed column visibility toggle
            $('#qb-columns-list').on('change', 'input[type="checkbox"][data-col]', function() {
                const columnIndex = parseInt($(this).data('col'), 10);
                const dt = dtApi();
                if (!dt) return;

                const visible = $(this).is(':checked');

                try {
                    dt.column(columnIndex).visible(visible);
                    dt.columns.adjust().draw(false);
                    reformatVisibleNumericCells();
                    fixTfootColspan();
                } catch (e) {
                    console.error('Error toggling column visibility:', e);
                }
            });

            /* ===== Filtering ===== */
            $('#filter-customer-name').on('change', function() {
                const url = new URL(window.location.origin + window.location.pathname);
                const name = $(this).val();
                if (name) url.searchParams.set('customer_name', name);
                const dt = dtApi();
                if (dt) {
                    dt.ajax.url(url.href).load();
                    mark();
                }
                const label = name ? `Customer: ${name}` : 'All Customers';
                $('#date-range-display').text(label);
            });

            function reloadWithParams(extra = {}) {
                const url = new URL(window.location.origin + window.location.pathname);
                const rp = $('#report-period').val();
                if (rp && rp !== 'all_dates') url.searchParams.set('report_period', rp);
                if (extra.start && extra.end) {
                    url.searchParams.set('start_date', extra.start);
                    url.searchParams.set('end_date', extra.end);
                }
                const cust = $('#filter-customer-name').val();
                if (cust) url.searchParams.set('customer_name', cust);
                const basis = $('#accounting-method').val();
                if (basis) url.searchParams.set('basis', basis);
                const dt = dtApi();
                if (dt) {
                    dt.ajax.url(url.href).load();
                    mark();
                }
            }

            // Initialize ColReorder for Yajra DataTable
            waitFor(() => !!dtApi(), () => {
                const dt = dtApi();
                if (dt && !dt.colReorder) {
                    // Initialize ColReorder if not already initialized
                    try {
                        dt.colReorder = new $.fn.dataTable.ColReorder(dt, {
                            fixedColumnsRight: 0
                        });
                    } catch (e) {
                        console.warn('ColReorder initialization failed:', e);
                    }
                }
            });
        });
    </script>
@endpush
