@extends('layouts.admin')

@section('content')
    {{-- ===== IBCS styles (trimmed to what we need) ===== --}}
    <style>
        /* ===== Base / Layout ===== */
        .quickbooks-report {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            min-height: 100vh;
            color: #262626
        }

        /* Header with actions */
        .report-header {
            background: #fff;
            padding: 16px 24px;
            border-bottom: 1px solid #e6e6e6;
            display: flex;
            justify-content: space-between;
            align-items: center
        }

        .report-header h4 {
            margin: 0;
            font-size: 18px;
            font-weight: 600
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 16px
        }

        .last-updated {
            color: #6b7280;
            font-size: 13px
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 8px
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
            transition: .2s
        }

        .btn-icon {
            background: transparent;
            color: #6b7280;
            width: 32px;
            height: 32px;
            justify-content: center
        }

        .btn-icon:hover {
            background: #f3f4f6;
            color: #262626
        }

        .btn-success {
            background: #22c55e;
            color: #fff;
            font-weight: 500
        }

        .btn-success:hover {
            background: #16a34a
        }

        .btn-save {
            padding: 8px 16px
        }

        /* Controls row */
        .controls-bar {
            background: #fff;
            padding: 10px 24px;
            border-bottom: 1px solid #e6e6e6;
            overflow: hidden
        }

        .controls-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            flex-wrap: nowrap
        }

        .left-controls {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: nowrap;
            min-width: 0
        }

        .right-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: auto;
            flex-shrink: 0;
            white-space: nowrap
        }

        .btn-qb-option,
        .btn-qb-action {
            background: transparent;
            border: none;
            padding: 6px 10px;
            font-size: 13px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            border-radius: 4px;
            white-space: nowrap;
            cursor: pointer;
            transition: .15s
        }

        .btn-qb-option {
            color: #0066cc
        }

        .btn-qb-option:hover {
            background: #f0f7ff;
            color: #0052a3
        }

        .btn-qb-action {
            color: #6b7280
        }

        .btn-qb-action:hover {
            background: #f3f4f6;
            color: #374151
        }

        .btn-qb-option i,
        .btn-qb-action i {
            margin-right: 4px;
            font-size: 12px
        }

        /* Report content */
        .report-content {
            background: #fff;
            margin: 24px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .1);
            overflow: hidden
        }

        .report-title-section {
            text-align: center;
            padding: 32px 24px 24px;
            border-bottom: 1px solid #e6e6e6
        }

        .report-title {
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 8px
        }

        .company-name {
            font-size: 16px;
            color: #6b7280;
            margin: 0 0 12px
        }

        .date-range {
            font-size: 14px;
            color: #374151;
            margin: 0
        }

        /* Table (use same skin) */
        .table-container {
            background: #fff;
            max-height: 500px;
            overflow: auto
        }

        .product-service-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px
        }

        .product-service-table th {
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
            z-index: 10
        }

        .product-service-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f3f4f6;
            color: #262626;
            vertical-align: middle
        }

        .product-service-table tbody tr:hover {
            background: #f9fafb
        }

        .text-right {
            text-align: right
        }

        /* Drawer-style Modals */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .5);
            z-index: 1050;
            overflow-y: auto
        }

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
            animation: slideInRight .18s ease-out
        }

        .modal-header {
            padding: 20px 25px 15px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center
        }

        .modal-header h5 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50
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
            justify-content: center
        }

        .btn-close:hover {
            color: #666
        }

        .modal-content {
            padding: 20px 25px 25px
        }

        .modal-subtitle {
            color: #666;
            margin-bottom: 20px;
            font-size: 14px
        }

        .option-section {
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
            border-radius: 4px
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
            border-bottom: 1px solid #e9ecef
        }

        .option-group {
            padding: 15px
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            font-size: 13px;
            color: #2c3e50;
            cursor: pointer
        }

        .checkbox-label input[type="checkbox"] {
            margin-right: 8px;
            width: auto
        }

        /* Columns UI */
        #qb-columns-list {
            list-style: none;
            margin: 0;
            padding: 0
        }

        .qb-col-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 6px;
            border-radius: 6px;
            border-bottom: 1px solid #f3f4f6
        }

        .qb-col-item:hover {
            background: #f8fafc
        }

        .qb-handle {
            color: #9ca3af;
            width: 18px;
            text-align: center;
            cursor: grab
        }

        .qb-handle:active {
            cursor: grabbing
        }

        .qb-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            user-select: none
        }

        .qb-pill input {
            position: absolute;
            left: -9999px
        }

        .qb-pill .pill {
            width: 22px;
            height: 22px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #d1d5db;
            background: #fff
        }

        .qb-pill .pill i {
            font-size: 12px;
            color: #fff;
            opacity: 0;
            transition: opacity .12s ease
        }

        .qb-pill input:checked+.pill {
            background: #22c55e;
            border-color: #16a34a
        }

        .qb-pill input:checked+.pill i {
            opacity: 1
        }

        .qb-col-name {
            font-size: 14px;
            color: #111827
        }

        .qb-ghost {
            opacity: .6;
            background: #eef2ff
        }

        .qb-chosen {
            background: #f1f5f9
        }

        /* Print */
        @media print {

            .controls-bar,
            .report-header {
                display: none !important
            }

            .quickbooks-report {
                background: #fff !important
            }

            .report-content {
                box-shadow: none !important;
                margin: 0 !important
            }

            .product-service-table {
                font-size: 11px
            }

            .product-service-table th,
            .product-service-table td {
                padding: 6px 4px
            }
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

        @media(max-width:768px) {
            .report-content {
                margin: 12px
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
                        onclick="exportDataTable('proposals-by-customer-table', '{{ __('Estimates by Customer') }}', 'print')"><i
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
                            <button onclick="exportDataTable('proposals-by-customer-table', '{{ __('Estimates by Customer') }}')"
                                class="btn btn-success mx-auto w-75 justify-content-center text-center"
                                data-action="excel">Export to
                                Excel</button>
                        </div>
                        <div class="col-md-6">
                            <button
                                onclick="exportDataTable('proposals-by-customer-table', '{{ __('Estimates by Customer') }}', 'pdf')"
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
                    <button class="btn btn-qb-option" id="view-options-btn"><i class="fa fa-eye"></i> View options</button>
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
                <h2 class="report-title">{{ $pageTitle }}</h2>
                <p class="company-name">{{ config('app.name', 'Your Company Name') }}</p>
                <p class="date-range"><span id="date-range-display">All Estimates</span></p>
            </div>

            <div class="table-container">
                {{-- IMPORTANT: add "product-service-table" for the IBCS table skin --}}
                {!! $dataTable->table(['class' => 'product-service-table', 'id' => 'proposals-by-customer-table']) !!}
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
                    <div class="option-group" style="display:flex;gap:12px;align-items:center;">
                        <select id="negative-format" class="form-control" style="width:110px;">
                            <option value="-100" selected>-100</option>
                            <option value="(100)">(100)</option>
                            <option value="100-">100-</option>
                        </select>
                        <label class="checkbox-label" style="margin:0;"><input type="checkbox" id="show-in-red"> Show in
                            red</label>
                    </div>
                </div>

                <!-- Header -->
                <div class="option-section">
                    <h6 class="section-title">Header <i class="fa fa-chevron-up"></i></h6>
                    <div class="option-group">
                        <label class="checkbox-label"><input type="checkbox" id="company-logo"> Company logo</label>
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
                        <div style="display:flex;gap:12px;align-items:center;">
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
                <button type="button" class="btn-close" id="close-columns">&times;</button>
            </div>
            <div class="modal-content">
                <div class="qb-columns-help">Add, remove and reorder the columns.<br>Drag columns to reorder.</div>
                <ul id="qb-columns-list"><!-- Built dynamically from the table header --></ul>
            </div>
        </div>
    </div>

    {{-- ===== Filter Drawer ===== --}}
    <div class="modal-overlay" id="filter-overlay">
        <div class="columns-modal">
            <div class="modal-header">
                <h5>Filter <i class="fa fa-info-circle"></i></h5>
                <button type="button" class="btn-close" id="close-filter">&times;</button>
            </div>
            <div class="modal-content">
                <p class="modal-subtitle">Select filter criteria for your report.</p>

                <div class="filter-item mb-3">
                    <label class="filter-label">Customer</label>
                    <select id="filter-customer-modal" class="form-control">
                        <option value="">All Customers</option>
                        @if (isset($customers))
                            @foreach ($customers as $customer)
                                <option value="{{ $customer }}">{{ $customer }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div class="filter-item mb-3">
                    <label class="filter-label">Status</label>
                    <select id="filter-status-modal" class="form-control">
                        <option value="">All Statuses</option>
                        @if (isset($statuses))
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div class="filter-item mb-3">
                    <label class="filter-label">From Date</label>
                    <input type="date" id="filter-start-date-modal" class="form-control"
                        value="{{ $filter['startDateRange'] ?? '' }}">
                </div>

                <div class="filter-item">
                    <label class="filter-label">To Date</label>
                    <input type="date" id="filter-end-date-modal" class="form-control"
                        value="{{ $filter['endDateRange'] ?? '' }}">
                </div>
            </div>
        </div>
    </div>

@endsection

@push('script-page')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    {{-- DataTables + extensions for IBCS parity --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/colreorder/1.7.0/js/dataTables.colReorder.min.js"></script>
    <script src="https://cdn.datatables.net/fixedheader/3.4.0/js/dataTables.fixedHeader.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/colreorder/1.7.0/css/colReOrder.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.4.0/css/fixedHeader.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    {!! $dataTable->scripts() !!}

    <script>
        $(function() {
            /* ============ Last Updated ticker ============ */
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
                tickerId = setInterval(renderLast, 30000);
            }
            markNow();

            /* ============ Global report options ============ */
            window.reportOptions = {
                divideBy1000: false,
                hideZeroAmounts: false,
                roundWholeNumbers: false,
                negativeFormat: '-100',
                showInRed: false,
                companyLogo: false,
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
                $('.company-name')[o.companyName ? 'show' : 'hide']();
                $('.date-range')[o.reportPeriod ? 'show' : 'hide']();
                $('.report-title-section').css('text-align', o.headerAlignment || 'center');
            }

            function ensureFooter() {
                if ($('.report-footer').length) return;
                $('.report-content').append(
                    '<div class="report-footer" style="padding:20px;border-top:1px solid #e6e6e6;text-align:center;font-size:12px;color:#6b7280;"></div>'
                    );
            }

            function footerRender(o) {
                ensureFooter();
                const now = new Date();
                const parts = [];
                if (o.datePrepared) parts.push(`Date Prepared: ${now.toLocaleDateString()}`);
                if (o.timePrepared) parts.push(`Time Prepared: ${now.toLocaleTimeString()}`);
                if (o.showReportBasis) parts.push(`Report Basis: ${o.reportBasis} Basis`);
                $('.report-footer').css('text-align', o.footerAlignment || 'center').html(parts.map(p =>
                    `<div>${p}</div>`).join(''));
            }

            function applyViewOptions() {
                $('#custom-view-styles').remove();
                let css = '<style id="custom-view-styles">';
                css += $('#opt-compact').prop('checked') ?
                    '.product-service-table th,.product-service-table td{padding:8px 12px;}' :
                    '.product-service-table th,.product-service-table td{padding:12px 16px;}';
                css += $('#opt-hover').prop('checked') ?
                    '.product-service-table tbody tr:hover{background:#f9fafb;}' :
                    '.product-service-table tbody tr:hover{background:inherit;}';
                if ($('#opt-striped').prop('checked')) css +=
                    '.product-service-table tbody tr:nth-child(even){background-color:#f8f9fa;}';
                css += $('#opt-borders').prop('checked') ?
                    '.product-service-table th,.product-service-table td{border:1px solid #e5e7eb;}' :
                    '.product-service-table th,.product-service-table td{border:none;border-bottom:1px solid #f3f4f6;}';
                css += $('#opt-wrap').prop('checked') ?
                    '.product-service-table th,.product-service-table td{white-space:normal;word-wrap:break-word;}' :
                    '.product-service-table th,.product-service-table td{white-space:nowrap;}';
                css += $('#opt-auto-width').prop('checked') ? '.product-service-table{table-layout:auto;}' :
                    '.product-service-table{table-layout:fixed;}';
                if ($('#opt-equal-width').prop('checked')) css +=
                    '.product-service-table th,.product-service-table td{width:10%;}';
                const fs = $('#font-size').val();
                css +=
                    `.product-service-table, .product-service-table th, .product-service-table td{font-size:${fs};}`;
                css += '</style>';
                $('head').append(css);
            }

            function applyGeneralOptionsFromUI() {
                const o = window.reportOptions;
                o.divideBy1000 = $('#divide-by-1000').prop('checked');
                o.hideZeroAmounts = $('#hide-zero-amounts').prop('checked');
                o.roundWholeNumbers = $('#round-whole-numbers').prop('checked');
                o.negativeFormat = $('#negative-format').val();
                o.showInRed = $('#show-in-red').prop('checked');
                o.companyLogo = $('#company-logo').prop('checked');
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
                footerRender(o);
            }

            /* ============ DataTable (Laravel DataTables instance) ============ */
            // Wait for LD to boot, then enhance with ColReorder & FixedHeader + bind UI
            function withDT(cb) {
                const dt = window.LaravelDataTables ? window.LaravelDataTables['proposals-by-customer-table'] :
                null;
                if (dt) return cb(dt);
                setTimeout(() => withDT(cb), 60);
            }

            // Add our params on every request
            $(document).on('preXhr.dt', '#proposals-by-customer-table', function(e, settings, data) {
                data.customer_name = $('#filter-customer-modal').val();
                data.status = $('#filter-status-modal').val();
                data.start_date = $('#filter-start-date-modal').val();
                data.end_date = $('#filter-end-date-modal').val();
                data.reportOptions = window.reportOptions || {};
            });

            withDT(function(dt) {
                try {
                    new $.fn.dataTable.FixedHeader(dt);
                } catch (e) {}
                try {
                    new $.fn.dataTable.ColReorder(dt);
                } catch (e) {}

                // Rebuild Columns drawer list from current headers
                function buildColumnsList() {
                    const $list = $('#qb-columns-list').empty();
                    dt.columns().every(function(i) {
                        const header = $(this.header()).text().trim() || `Column ${i+1}`;
                        const li = $(`
                          <li class="qb-col-item" data-column="${i}">
                            <span class="qb-handle"><i class="fa fa-grip-vertical"></i></span>
                            <label class="qb-pill">
                              <input type="checkbox" data-col="${i}" ${dt.column(i).visible() ? 'checked':''}>
                              <span class="pill"><i class="fa fa-check"></i></span>
                              <span class="qb-col-name">${header}</span>
                            </label>
                          </li>`);
                        $list.append(li);
                    });

                    // Sortable -> reorder columns via ColReorder
                    new Sortable(document.getElementById('qb-columns-list'), {
                        animation: 150,
                        handle: '.qb-handle',
                        chosenClass: 'qb-chosen',
                        ghostClass: 'qb-ghost',
                        onEnd: function() {
                            const newOrder = $('#qb-columns-list .qb-col-item').map(function() {
                                return parseInt($(this).attr('data-column'), 10);
                            }).get();
                            if (dt.colReorder && typeof dt.colReorder.order === 'function') {
                                try {
                                    dt.colReorder.order(newOrder, true);
                                    dt.columns.adjust().draw(false);
                                } catch (e) {}
                            }
                        }
                    });
                }

                buildColumnsList();

                // Toggle visibility
                $('#qb-columns-list').on('change', 'input[type="checkbox"][data-col]', function() {
                    const idx = parseInt($(this).data('col'), 10);
                    const visible = $(this).is(':checked');
                    dt.column(idx).visible(visible, false);
                    dt.columns.adjust().draw(false);
                });

                // Refresh stamp
                $('#proposals-by-customer-table').on('xhr.dt', function() {
                    markNow();
                    $('#btn-refresh i').removeClass('fa-spin');
                });
            });

            function refreshData() {
                withDT(dt => dt.draw(false));
            }

            // Filter UI -> refresh
            $('#filter-customer-modal,#filter-status-modal,#filter-start-date-modal,#filter-end-date-modal').on(
                'change',
                function() {
                    updateDateRangeDisplay();
                    refreshData();
                });

            function updateDateRangeDisplay() {
                const s = $('#filter-start-date-modal').val(),
                    e = $('#filter-end-date-modal').val();
                if (s && e) $('#date-range-display').text(new Date(s).toLocaleDateString() + ' - ' + new Date(e)
                    .toLocaleDateString());
                else if (s) $('#date-range-display').text('From ' + new Date(s).toLocaleDateString());
                else if (e) $('#date-range-display').text('To ' + new Date(e).toLocaleDateString());
                else $('#date-range-display').text('All Estimates');
            }

            /* ============ Drawers open/close ============ */
            $('#view-options-btn').on('click', () => $('#view-options-overlay').show());
            $('#columns-btn').on('click', () => {
                $('#columns-overlay').show();
            });
            $('#filter-btn').on('click', () => $('#filter-overlay').show());
            $('#general-options-btn').on('click', () => $('#general-options-overlay').show());

            $('#close-view-options').on('click', () => {
                $('#view-options-overlay').hide();
                applyViewOptions();
            });
            $('#close-columns').on('click', () => $('#columns-overlay').hide());
            $('#close-filter').on('click', () => {
                $('#filter-overlay').hide();
                refreshData();
            });
            $('#close-general-options').on('click', () => {
                $('#general-options-overlay').hide();
                applyGeneralOptionsFromUI();
                refreshData();
            });

            $('.modal-overlay').on('click', function(e) {
                if (e.target === this) {
                    $(this).hide();
                }
            });
            $(document).on('keydown', e => {
                if (e.key === 'Escape') $('.modal-overlay').hide();
            });

            // Section collapse toggles
            $(document).on('click', '.section-title', function() {
                $(this).next('.option-group').slideToggle(120);
                $(this).find('.fa-chevron-up, .fa-chevron-down').toggleClass(
                    'fa-chevron-up fa-chevron-down');
            });

            /* ============ Header actions ============ */
            $('#btn-refresh').on('click', function() {
                $(this).find('i').addClass('fa-spin');
                refreshData();
            });
            // $('#btn-print').on('click', () => window.print());
            // $('#btn-export').on('click', () => alert('Export action triggered'));
            $('#btn-more').on('click', () => alert('More options clicked'));
            $('#btn-save').on('click', function() {
                const name = prompt('Enter report name:', '{{ $pageTitle }} - ' + new Date()
                    .toISOString().slice(0, 10));
                if (name) alert('Report "' + name + '" would be saved with current settings.');
            });

            // Initial apply
            setTimeout(function() {
                applyGeneralOptionsFromUI();
                applyViewOptions();
                footerRender(window.reportOptions);
            }, 150);
        });
    </script>
@endpush
