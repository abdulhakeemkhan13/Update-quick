@extends('layouts.admin')

@section('content')
    <div class="content-wrapper">
        <!-- Header with actions -->
        <div class="report-header">
            <h4 class="mb-0">{{ $pageTitle }}</h4>
            <div class="header-actions">
                <span class="last-updated">Last updated 8 minutes ago</span>
                <div class="actions">
                    <button class="btn btn-icon" title="Refresh"><i class="fa fa-sync"></i></button>
                    <button class="btn btn-icon"
                        onclick="exportDataTable('ledger-table', '{{ $pageTitle }}', 'print')"><i
                            class="fa fa-print"></i></button>
                    <button class="btn btn-icon" title="Export"><i class="fa fa-external-link-alt"></i></button>
                    {{-- <button class="btn btn-icon" title="More options"><i class="fa fa-ellipsis-v"></i></button> --}}
                    {{--<button class="btn btn-success btn-save">Save As</button>--}}
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
                            <button onclick="exportDataTable('ledger-table', '{{ $pageTitle }}')"
                                class="btn btn-success mx-auto w-75 justify-content-center text-center"
                                data-action="excel">Export to
                                Excel</button>
                        </div>
                        <div class="col-md-6">
                            <button onclick="exportDataTable('ledger-table', '{{ $pageTitle }}', 'pdf')"
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

        <!-- Filter Controls -->
        <div class="filter-controls">
            <div class="filter-row">
                <div class="filter-group d-flex">
                    {{-- filter row --}}
                    <div class="col-md-7">
                        <div class="row">
                            <div class="filter-item col-md-2">
                                <label class="filter-label">Report period</label>
                                <select id="filter-period" class="form-control">
                                    <option value="this_month_to_date" selected>This month to date</option>
                                    <option value="today">Today</option>
                                    <option value="this_week">This week</option>
                                    <option value="this_month">This month</option>
                                    <option value="this_quarter">This quarter</option>
                                    <option value="this_year">This year</option>
                                    <option value="last_month">Last month</option>
                                    <option value="last_quarter">Last quarter</option>
                                    <option value="last_year">Last year</option>
                                    <option value="custom_date">Custom dates</option>
                                </select>
                            </div>

                            <div class="filter-item col-md-1">
                                <label class="filter-label">Date Range</label>
                                <input type="text" id="daterange" class="form-control date-input"
                                    value="{{ Carbon\Carbon::now()->startOfMonth()->format('m/d/Y') }} - {{ Carbon\Carbon::now()->format('m/d/Y') }}">
                                <input type="hidden" id="filter-start-date"
                                    value="{{ Carbon\Carbon::now()->startOfMonth()->format('Y-m-d') }}">
                                <input type="hidden" id="filter-end-date"
                                    value="{{ Carbon\Carbon::now()->format('Y-m-d') }}">
                            </div>
                            {{--
                            <div class="filter-item col-md-1">
                                <label class="filter-label">Accounting method</label>
                                <select id="accounting-method" class="form-control">
                                    <option value="accrual" selected>Accrual</option>
                                    <option value="cash">Cash</option>
                                </select>
                            </div>
                            --}}
                            {{-- <div class="filter-item col-md-2 mt-4">
                                <button class="btn btn-view-options" id="view-options-btn"
                                    style="border: none !important; border-left: 1px solid #d1d5db !important; border-radius: 0px !important; width: 130px;">
                                    <i class="fa fa-eye"></i>
                                    <span>View options</span>
                                </button>
                            </div> --}}
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="row mt-4">
                            <!-- Action buttons row -->
                            <div class="d-flex gap-2 justify-content-end align-items-center">
                                <button class="btn btn-outline" id="columns-btn">
                                    <i class="fa fa-columns"></i> Columns <span class="badge">9</span>
                                </button>
                                <button class="btn btn-outline" type="button" data-bs-toggle="offcanvas"
                                    data-bs-target="#filterSidebar" aria-controls="filterSidebar">
                                    <i class="fa fa-filter"></i> Filter
                                </button>

                                <button class="btn btn-outline" id="general-options-btn">
                                    <i class="fa fa-cog"></i> General options
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Side Bar -->
        <div class="offcanvas offcanvas-end" data-bs-scroll="true" tabindex="-1" id="filterSidebar"
            aria-labelledby="filterSidebarLabel">
            <div class="offcanvas-header" style="background:#f9fafb; border-bottom:1px solid #e6e6e6;">
                <h5 class="offcanvas-title" id="filterSidebarLabel">Filters</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close">
                    <i class="fa fa-close"></i>
                </button>
            </div>

            <div class="offcanvas-body">
                <!-- Account filter MOVED here -->
                <div class="filter-item mb-3">
                    <label class="filter-label">Account</label>
                    <select id="filter-account" class="form-control">
                        <option value="all">All Accounts</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}" {{ $accountId == $account->id ? 'selected' : '' }}>
                                {{ $account->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-item mb-3">
                    <label class="filter-label">Report period</label>
                    <select id="sidebar-filter-period" class="form-control">
                        <option value="this_month_to_date" selected>This month to date</option>
                        <option value="today">Today</option>
                        <option value="this_week">This week</option>
                        <option value="this_month">This month</option>
                        <option value="this_quarter">This quarter</option>
                        <option value="this_year">This year</option>
                        <option value="last_month">Last month</option>
                        <option value="last_quarter">Last quarter</option>
                        <option value="last_year">Last year</option>
                        <option value="custom_date">Custom dates</option>
                    </select>
                </div>

            </div>
        </div>

        <!-- Report Content -->
        <div class="report-content">
            <div class="report-title-section">
                <h2 class="report-title">{{ $pageTitle }}</h2>
                {{-- <p class="company-name">{{ config('app.name', 'Craig\'s Design and Landscaping Services') }}</p> --}}
                <p class="date-range">
                    <span id="date-range-display">
                        {{ Carbon\Carbon::now()->startOfMonth()->format('F j, Y') }} -
                        {{ Carbon\Carbon::now()->format('F j, Y') }}
                    </span>
                </p>
            </div>

            <div class="table-container p-2">
                {!! $dataTable->table(['class' => 'table ledger-table', 'id' => 'ledger-table']) !!}
            </div>
        </div>
    </div>

    <!-- General Options Modal -->
    <div class="modal-overlay" id="general-options-overlay">
        <div class="general-options-modal">
            <div class="modal-header">
                <h5>General options <i class="fa fa-info-circle" title="Configure report settings"></i></h5>
                <button type="button" class="btn-close" id="close-general-options">&times;</button>
            </div>
            <div class="modal-content">
                <p class="modal-subtitle">Select general options for your report.</p>

                <!-- Number format section -->
                <div class="option-section">
                    <h6 class="section-title">Number format <i class="fa fa-chevron-up"></i></h6>
                    <div class="option-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="divide-by-1000"> Divide by 1000
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" id="hide-zero-amounts"> Don't show zero amounts
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" id="round-whole-numbers"> Round to the nearest whole number
                        </label>
                    </div>
                </div>

                <!-- Negative numbers section -->
                <div class="option-section">
                    <h6 class="section-title">Negative numbers</h6>
                    <div class="option-group">
                        <div class="negative-format-group">
                            <select id="negative-format" class="form-control">
                                <option value="-100" selected>-100</option>
                                <option value="(100)">(100)</option>
                                <option value="100-">100-</option>
                            </select>
                            <label class="checkbox-label">
                                <input type="checkbox" id="show-in-red"> Show in red
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Header section -->
                <div class="option-section">
                    <h6 class="section-title">Header <i class="fa fa-chevron-up"></i></h6>
                    <div class="option-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="company-logo"> Company logo
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" id="report-period" checked> Report period
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" id="company-name" checked> Company name
                        </label>
                    </div>
                    <div class="alignment-group">
                        <label class="alignment-label">Header alignment</label>
                        <select id="header-alignment" class="form-control">
                            <option value="center" selected>Center</option>
                            <option value="left">Left</option>
                            <option value="right">Right</option>
                        </select>
                    </div>
                </div>

                <!-- Footer section -->
                <div class="option-section">
                    <h6 class="section-title">Footer <i class="fa fa-chevron-up"></i></h6>
                    <div class="option-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="date-prepared" checked> Date prepared
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" id="time-prepared" checked> Time prepared
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" id="report-basis" checked> Report basis (cash vs. accrual)
                        </label>
                    </div>
                    <div class="alignment-group">
                        <label class="alignment-label">Footer alignment</label>
                        <select id="footer-alignment" class="form-control">
                            <option value="center" selected>Center</option>
                            <option value="left">Left</option>
                            <option value="right">Right</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Columns Modal -->
    <div class="modal-overlay" id="columns-overlay">
        <div class="columns-modal">
            <div class="modal-header">
                <h5>Columns <i class="fa fa-info-circle"></i></h5>
                <button type="button" class="btn-close" id="close-columns">&times;</button>
            </div>
            <div class="modal-content">
                <p class="modal-subtitle">Drag columns to reorder the columns</p>

                <div class="columns-list" id="sortable-columns">
                    <div class="column-item" data-column="0">
                        <i class="fa fa-grip-vertical handle"></i>
                        <label class="checkbox-label">
                            <input type="checkbox" checked> Distribution account
                        </label>
                    </div>
                    <div class="column-item" data-column="1">
                        <i class="fa fa-grip-vertical handle"></i>
                        <label class="checkbox-label">
                            <input type="checkbox" checked> Transaction date
                        </label>
                    </div>
                    <div class="column-item" data-column="2">
                        <i class="fa fa-grip-vertical handle"></i>
                        <label class="checkbox-label">
                            <input type="checkbox" checked> Transaction type
                        </label>
                    </div>
                    <div class="column-item" data-column="3">
                        <i class="fa fa-grip-vertical handle"></i>
                        <label class="checkbox-label">
                            <input type="checkbox" checked> Num
                        </label>
                    </div>
                    <div class="column-item" data-column="4">
                        <i class="fa fa-grip-vertical handle"></i>
                        <label class="checkbox-label">
                            <input type="checkbox" checked> Name
                        </label>
                    </div>
                    <div class="column-item" data-column="5">
                        <i class="fa fa-grip-vertical handle"></i>
                        <label class="checkbox-label">
                            <input type="checkbox" checked> Memo/Description
                        </label>
                    </div>
                    <div class="column-item" data-column="6">
                        <i class="fa fa-grip-vertical handle"></i>
                        <label class="checkbox-label">
                            <input type="checkbox" checked> Split account
                        </label>
                    </div>
                    <div class="column-item" data-column="7">
                        <i class="fa fa-grip-vertical handle"></i>
                        <label class="checkbox-label">
                            <input type="checkbox" checked> Balance
                        </label>
                    </div>
                </div>

                <hr>

                <div class="additional-columns">
                    {{-- // additional columns will be added here --}}
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Base styling */
        * {
            box-sizing: border-box;
        }

        .content-wrapper {
            background-color: #f5f6fa;
            min-height: 100vh;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 14px;
            color: #262626;
        }

        /* Header */
        .report-header {
            background: white;
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
            color: #262626;
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
            transition: all 0.2s;
        }

        .btn-icon {
            background: transparent;
            color: #6b7280;
            padding: 8px;
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
            color: white;
            font-weight: 500;
        }

        .btn-success:hover {
            background: #16a34a;
        }

        .btn-save {
            padding: 8px 16px;
        }

        /* Filter Controls */
        .filter-controls {
            background: white;
            padding: 20px 24px;
            border-bottom: 1px solid #e6e6e6;
        }

        .filter-item {
            display: flex;
            flex-direction: column;
            min-width: 140px;
        }

        .filter-label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 6px;
            font-weight: 500;
        }

        .form-control {
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 13px;
            background: white;
            color: #262626;
            height: 36px;
        }

        .form-control:focus {
            outline: none;
            border-color: #0969da;
            box-shadow: 0 0 0 2px rgba(9, 105, 218, 0.1);
        }

        .date-input {
            position: relative;
        }

        .view-options {
            display: flex;
            align-items: center;
        }

        .btn-view-options {
            background: transparent;
            color: #6b7280;
            border: 1px solid #d1d5db;
            padding: 8px 12px;
            font-size: 13px;
        }

        .btn-view-options:hover {
            background: #f9fafb;
            border-color: #9ca3af;
        }

        /* Action buttons row */
        .action-buttons-row {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .btn-outline {
            background: white;
            border: 1px solid #d1d5db;
            color: #374151;
            padding: 8px 12px;
            font-size: 13px;
        }

        .btn-outline:hover {
            background: #f9fafb;
            border-color: #9ca3af;
        }

        .badge {
            background: #e5e7eb;
            color: #374151;
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 4px;
        }

        /* Report Content */
        .report-content {
            background: white;
            margin: 24px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
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
            color: #262626;
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

        /* Table Container */
        .table-container {
            overflow-x: auto;
        }

        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .ledger-table th {
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .ledger-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f3f4f6;
            color: #262626;
        }

        .ledger-table tbody tr:hover {
            background: #f9fafb;
        }

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            overflow-y: auto;
        }

        .general-options-modal,
        .columns-modal {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            width: 360px;
            background: white;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid #e6e6e6;
            background: #f9fafb;
        }

        .modal-header h5 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #262626;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-close {
            background: none;
            border: none;
            font-size: 20px;
            color: #6b7280;
            cursor: pointer;
            padding: 4px;
            line-height: 1;
        }

        .btn-close:hover {
            color: #262626;
        }

        .modal-content {
            padding: 24px;
        }

        .modal-subtitle {
            color: #6b7280;
            font-size: 13px;
            margin: 0 0 24px;
        }

        /* Option Sections */
        .option-section {
            margin-bottom: 24px;
        }

        .section-title {
            font-size: 14px;
            font-weight: 600;
            color: #262626;
            margin: 0 0 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
        }

        .option-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #374151;
            cursor: pointer;
            margin: 0;
        }

        .checkbox-label input[type="checkbox"] {
            margin: 0;
            width: 16px;
            height: 16px;
        }

        .negative-format-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .negative-format-group select {
            width: 80px;
            flex-shrink: 0;
        }

        .alignment-group {
            margin-top: 12px;
        }

        .alignment-label {
            display: block;
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 6px;
            font-weight: 500;
        }

        /* Columns Modal Specific */
        .columns-list {
            margin-bottom: 20px;
        }

        .column-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
            cursor: move;
        }

        .handle {
            color: #9ca3af;
            margin-right: 12px;
            cursor: grab;
        }

        .handle:active {
            cursor: grabbing;
        }

        .additional-columns {
            max-height: 300px;
            overflow-y: auto;
        }

        .additional-columns .column-item {
            padding-left: 28px;
            cursor: default;
        }

        #ledger-table {
            table-layout: fixed !important;
            width: 100% !important;
        }
        #ledger-table th, #ledger-table td {
            word-wrap: break-word;
            white-space: normal;
        }

        /* Enhanced form controls */
        select.form-control {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 12px;
            padding-right: 32px;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        /* Table enhancements */
        .text-right {
            text-align: right;
        }

        .negative-amount {
            color: #dc2626;
        }

        .account-group {
            background-color: #f8fafc;
            font-weight: 600;
            cursor: pointer;
        }

        .account-row {
            font-weight: normal;
        }

        .opening-balance {
            font-style: italic;
            color: #6b7280;
        }

        .expand-icon {
            margin-right: 6px;
            font-size: 11px;
        }

        /* QuickBooks specific styling */
        .fa-info-circle {
            color: #0969da;
            font-size: 12px;
        }

        .fa-chevron-up {
            font-size: 10px;
            color: #6b7280;
        }

        .option-section hr {
            border: none;
            border-top: 1px solid #e6e6e6;
            margin: 20px 0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            /* .filter-group {
                                                        flex-direction: column;
                                                        width: 100%;
                                                        gap: 16px;
                                                    } */

            .filter-item {
                width: 100%;
                min-width: auto;
            }

            .general-options-modal,
            .columns-modal {
                width: 100%;
                left: 0;
            }

            .header-actions {
                flex-direction: column;
                gap: 8px;
                align-items: flex-end;
            }

            .actions {
                flex-wrap: wrap;
            }
        }
    </style>

    {!! $dataTable->scripts() !!}
@endsection

@push('script-page')
    <!-- Include jQuery and required libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <script>
        $(document).ready(function() {
            // Global variables
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

            // Initialize date range picker
            $('#daterange').daterangepicker({
                startDate: moment($('#filter-start-date').val()),
                endDate: moment($('#filter-end-date').val()),
                opens: 'left',
                autoApply: true,
                locale: {
                    format: 'MM/DD/YYYY'
                },
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1,
                        'month').endOf('month')],
                    'This Quarter': [moment().startOf('quarter'), moment().endOf('quarter')],
                    'This Year': [moment().startOf('year'), moment().endOf('year')]
                }
            }, function(start, end) {
                // Update hidden inputs with formatted dates for the server
                $('#filter-start-date').val(start.format('YYYY-MM-DD'));
                $('#filter-end-date').val(end.format('YYYY-MM-DD'));

                // Update display
                updateDateDisplay();

                // Auto refresh data
                refreshData();
            });

            // General Options Modal
            $('#general-options-btn').on('click', function() {
                $('#general-options-overlay').show();
            });

            $('#close-general-options').on('click', function() {
                $('#general-options-overlay').hide();
            });

            $('#general-options-overlay').on('click', function(e) {
                if (e.target === this) {
                    $('#general-options-overlay').hide();
                }
            });

            // Columns Modal
            $('#columns-btn').on('click', function() {
                $('#columns-overlay').show();
            });

            $('#close-columns').on('click', function() {
                $('#columns-overlay').hide();
            });

            $('#columns-overlay').on('click', function(e) {
                if (e.target === this) {
                    $('#columns-overlay').hide();
                }
            });

            // Initialize Sortable for column reordering
            if (document.getElementById('sortable-columns')) {
                new Sortable(document.getElementById('sortable-columns'), {
                    animation: 150,
                    handle: '.handle',
                    onEnd: function() {
                        updateColumnOrder();
                    }
                });
            }

            // Handle period filter changes
            $('#filter-period').on('change', function() {
                updateDateRange($(this).val());
            });

            // Update date range based on period selection
            function updateDateRange(period) {
                const today = moment();
                let startDate, endDate;

                switch (period) {
                    case 'today':
                        startDate = today.clone();
                        endDate = today.clone();
                        break;
                    case 'this_week':
                        startDate = today.clone().startOf('week');
                        endDate = today.clone().endOf('week');
                        break;
                    case 'this_month':
                        startDate = today.clone().startOf('month');
                        endDate = today.clone().endOf('month');
                        break;
                    case 'this_month_to_date':
                        startDate = today.clone().startOf('month');
                        endDate = today.clone();
                        break;
                    case 'this_quarter':
                        startDate = today.clone().startOf('quarter');
                        endDate = today.clone().endOf('quarter');
                        break;
                    case 'this_year':
                        startDate = today.clone().startOf('year');
                        endDate = today.clone().endOf('year');
                        break;
                    case 'last_month':
                        startDate = today.clone().subtract(1, 'month').startOf('month');
                        endDate = today.clone().subtract(1, 'month').endOf('month');
                        break;
                    case 'last_quarter':
                        startDate = today.clone().subtract(1, 'quarter').startOf('quarter');
                        endDate = today.clone().subtract(1, 'quarter').endOf('quarter');
                        break;
                    case 'last_year':
                        startDate = today.clone().subtract(1, 'year').startOf('year');
                        endDate = today.clone().subtract(1, 'year').endOf('year');
                        break;
                    default:
                        startDate = today.clone().startOf('month');
                        endDate = today.clone();
                }

                // Update hidden date fields
                $('#filter-start-date').val(startDate.format('YYYY-MM-DD'));
                $('#filter-end-date').val(endDate.format('YYYY-MM-DD'));

                // Update DateRangePicker to reflect the new dates
                $('#daterange').data('daterangepicker').setStartDate(startDate);
                $('#daterange').data('daterangepicker').setEndDate(endDate);

                // Update display
                updateDateDisplay();
                refreshData();
            }

            // Update date display
            function updateDateDisplay() {
                const startDate = moment($('#filter-start-date').val());
                const endDate = moment($('#filter-end-date').val());

                const formattedStart = startDate.format('MMMM D, YYYY');
                const formattedEnd = endDate.format('MMMM D, YYYY');

                $('#date-range-display').text(formattedStart + ' - ' + formattedEnd);
            }

            // Refresh data function
            function refreshData() {
                if (window.LaravelDataTables && window.LaravelDataTables["ledger-table"]) {
                    window.LaravelDataTables["ledger-table"].draw();
                } else {
                    console.log('DataTable not yet initialized');
                    setTimeout(refreshData, 100);
                }
            }

            // Handle date changes
            $('#filter-start-date, #filter-end-date').on('apply.daterangepicker', function() {
                updateDateDisplay();
                refreshData();
            });

            // Handle account filter changes
            $('#filter-account').on('change', function() {
                refreshData();
            });

            // Handle accounting method changes
            $('#accounting-method').on('change', function() {
                refreshData();
            });

            // Setup DataTable ajax parameters
            $('#ledger-table').on('preXhr.dt', function(e, settings, data) {
                data.startDate = moment($('#filter-start-date').val(), 'YYYY-MM-DD').format('YYYY-MM-DD');
                data.endDate = moment($('#filter-end-date').val(), 'YYYY-MM-DD').format('YYYY-MM-DD');

                data.account_id = $('#filter-account').val();
                data.accounting_method = $('#accounting-method').val();

                // Add general options to the request
                data.reportOptions = window.reportOptions;
            });

            // General Options functionality
            function applyGeneralOptions() {
                // Update global options object
                window.reportOptions.divideBy1000 = $('#divide-by-1000').prop('checked');
                window.reportOptions.hideZeroAmounts = $('#hide-zero-amounts').prop('checked');
                window.reportOptions.roundWholeNumbers = $('#round-whole-numbers').prop('checked');
                window.reportOptions.negativeFormat = $('#negative-format').val();
                window.reportOptions.showInRed = $('#show-in-red').prop('checked');
                window.reportOptions.companyLogo = $('#company-logo').prop('checked');
                window.reportOptions.reportPeriod = $('#report-period').prop('checked');
                window.reportOptions.companyName = $('#company-name').prop('checked');
                window.reportOptions.headerAlignment = $('#header-alignment').val();
                window.reportOptions.datePrepared = $('#date-prepared').prop('checked');
                window.reportOptions.timePrepared = $('#time-prepared').prop('checked');
                window.reportOptions.reportBasis = $('#report-basis').prop('checked');
                window.reportOptions.footerAlignment = $('#footer-alignment').val();

                // Apply number formatting
                applyNumberFormatting(window.reportOptions);

                // Apply header/footer settings
                applyHeaderFooterSettings(window.reportOptions);

                // Refresh the table with new settings
                refreshData();
            }

            function applyNumberFormatting(options) {
                // Remove any existing custom styles
                $('#custom-number-format').remove();

                // Create custom style tag
                let customCSS = '<style id="custom-number-format">';

                if (options.showInRed) {
                    customCSS += '.negative-amount { color: #dc2626 !important; }';
                }

                if (options.hideZeroAmounts) {
                    customCSS += '.zero-amount { display: none !important; }';
                }

                customCSS += '</style>';
                $('head').append(customCSS);
            }

            function applyHeaderFooterSettings(options) {
                // Update header alignment
                $('.report-title-section').css('text-align', options.headerAlignment);

                // Show/hide header elements
                if (!options.companyName) {
                    $('.company-name').hide();
                } else {
                    $('.company-name').show();
                }

                if (!options.reportPeriod) {
                    $('.date-range').hide();
                } else {
                    $('.date-range').show();
                }

                // Add footer if it doesn't exist
                if ($('.report-footer').length === 0) {
                    const currentDate = new Date();
                    const dateStr = currentDate.toLocaleDateString();
                    const timeStr = currentDate.toLocaleTimeString();
                    const basisStr = $('#accounting-method').val() === 'accrual' ? 'Accrual Basis' : 'Cash Basis';

                    let footerHTML =
                        '<div class="report-footer" style="padding: 20px; border-top: 1px solid #e6e6e6; text-align: ' +
                        options.footerAlignment + '; font-size: 12px; color: #6b7280;">';

                    if (options.datePrepared) {
                        footerHTML += '<div>Date Prepared: ' + dateStr + '</div>';
                    }

                    if (options.timePrepared) {
                        footerHTML += '<div>Time Prepared: ' + timeStr + '</div>';
                    }

                    if (options.reportBasis) {
                        footerHTML += '<div>Report Basis: ' + basisStr + '</div>';
                    }

                    footerHTML += '</div>';

                    $('.report-content').append(footerHTML);
                } else {
                    // Update existing footer
                    $('.report-footer').css('text-align', options.footerAlignment);

                    if (!options.datePrepared) {
                        $('.report-footer div:contains("Date Prepared")').hide();
                    } else {
                        $('.report-footer div:contains("Date Prepared")').show();
                    }

                    if (!options.timePrepared) {
                        $('.report-footer div:contains("Time Prepared")').hide();
                    } else {
                        $('.report-footer div:contains("Time Prepared")').show();
                    }

                    if (!options.reportBasis) {
                        $('.report-footer div:contains("Report Basis")').hide();
                    } else {
                        $('.report-footer div:contains("Report Basis")').show();
                    }
                }
            }

            // Apply general options when checkboxes change
            $('.general-options-modal input, .general-options-modal select').on('change', function() {
                applyGeneralOptions();
            });

            // Column management
            function updateColumnOrder() {
                const order = [];
                $('#sortable-columns .column-item').each(function() {
                    const columnIndex = $(this).data('column');
                    if (columnIndex !== undefined) {
                        order.push(columnIndex);
                    }
                });

                // Store column order preference
                localStorage.setItem('ledger-column-order', JSON.stringify(order));
                console.log('Column order updated:', order);

                // Apply column order if DataTable supports it
                if (window.LaravelDataTables && window.LaravelDataTables["ledger-table"]) {
                    // Note: Column reordering requires ColReorder extension
                    console.log('Column order would be applied:', order);
                }
            }

            // Handle column visibility
            $('.columns-modal input[type="checkbox"]').on('change', function() {
                const columnIndex = $(this).closest('.column-item').data('column');
                const isVisible = $(this).prop('checked');

                if (columnIndex !== undefined && window.LaravelDataTables && window.LaravelDataTables[
                        "ledger-table"]) {
                    try {
                        window.LaravelDataTables["ledger-table"].column(columnIndex).visible(isVisible);
                    } catch (error) {
                        console.log('Column visibility change:', columnIndex, isVisible);
                    }
                }

                // Update column count badge
                updateColumnCountBadge();
            });

            function updateColumnCountBadge() {
                const visibleCount = $('.columns-modal input[type="checkbox"]:checked').length;
                $('.badge').text(visibleCount);
            }

            // Collapsible sections in General Options
            $('.section-title').on('click', function() {
                const section = $(this).next('.option-group');
                const icon = $(this).find('.fa-chevron-up, .fa-chevron-down');

                section.slideToggle();
                if (icon.hasClass('fa-chevron-up')) {
                    icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                } else {
                    icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                }
            });

            // Add expand/collapse functionality for account groups
            $(document).on('click', '.account-group', function() {
                const accountId = $(this).data('account-id');
                $('.account-row[data-parent="' + accountId + '"]').toggle();

                // Toggle icon
                const icon = $(this).find('.expand-icon');
                if (icon.text() === '▼') {
                    icon.text('►');
                } else {
                    icon.text('▼');
                }
            });

            // Initialize with current selection
            updateDateDisplay();

            // Print functionality
            $('.btn-icon[title="Print"]').on('click', function() {
                // Create print-friendly version
                const printWindow = window.open('', '_blank');
                const printContent = `
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>General Ledger - Print</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 20px; }
                            .report-title { text-align: center; font-size: 24px; font-weight: bold; margin-bottom: 10px; }
                            .company-name { text-align: center; font-size: 16px; margin-bottom: 10px; }
                            .date-range { text-align: center; font-size: 14px; margin-bottom: 20px; }
                            table { width: 100%; border-collapse: collapse; font-size: 12px; }
                            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                            th { background-color: #f5f5f5; font-weight: bold; }
                            .text-right { text-align: right; }
                            .negative-amount { color: red; }
                            @media print { body { margin: 0; } }
                        </style>
                    </head>
                    <body>
                        <div class="report-title">General Ledger</div>
                        <div class="company-name">${$('.company-name').text()}</div>
                        <div class="date-range">${$('.date-range').text()}</div>
                        <table>
                            ${$('.ledger-table').html()}
                        </table>
                    </body>
                    </html>
                `;
                printWindow.document.write(printContent);
                printWindow.document.close();
                printWindow.print();
            });

            // Save As functionality
            $('.btn-save').on('click', function() {
                const reportName = prompt('Enter report name:', 'General Ledger - ' + moment().format(
                    'YYYY-MM-DD'));
                if (reportName) {
                    // In a real application, this would save to the server
                    alert('Report "' + reportName + '" would be saved with current settings');

                    // Save current settings to localStorage for demo
                    const settings = {
                        name: reportName,
                        startDate: $('#start-date').val(),
                        endDate: $('#end-date').val(),
                        account: $('#filter-account').val(),
                        accountingMethod: $('#accounting-method').val(),
                        options: window.reportOptions,
                        savedAt: new Date().toISOString()
                    };

                    localStorage.setItem('saved-report-' + Date.now(), JSON.stringify(settings));
                }
            });

            // Export functionality
            /*$('.btn-icon[title="Export"]').on('click', function() {
                // Create export menu
                const exportOptions = [{
                        text: 'Export to Excel',
                        action: 'excel'
                    },
                    {
                        text: 'Export to PDF',
                        action: 'pdf'
                    },
                    {
                        text: 'Export to CSV',
                        action: 'csv'
                    }
                ];

                const option = prompt(
                    'Choose export format:\n1. Excel\n2. PDF\n3. CSV\n\nEnter number (1-3):');

                switch (option) {
                    case '1':
                        alert('Excel export would be triggered');
                        break;
                    case '2':
                        alert('PDF export would be triggered');
                        break;
                    case '3':
                        alert('CSV export would be triggered');
                        break;
                    default:
                        alert('Invalid option');
                }
            });*/

            // View options functionality
            $('#view-options-btn').on('click', function() {
                alert('View options panel would open here with additional display settings');
            });

            // Filter button functionality
            $('#filter-btn').on('click', function() {
                alert('Advanced filter panel would open here');
            });

            // Refresh button functionality
            $('.btn-icon[title="Refresh"]').on('click', function() {
                $(this).find('i').addClass('fa-spin');
                refreshData();
                setTimeout(() => {
                    $(this).find('i').removeClass('fa-spin');
                }, 1000);
            });

            // Initialize general options with default values
            setTimeout(function() {
                applyGeneralOptions();
                updateColumnCountBadge();
            }, 100);

            // Format numbers in table based on options
            $(document).on('draw.dt', '#ledger-table', function() {
                if (window.reportOptions) {
                    $('.ledger-table tbody tr').each(function() {
                        const $row = $(this);

                        // Apply number formatting to amount columns
                        $row.find('td').each(function(index) {
                            const $cell = $(this);
                            const text = $cell.text().trim();

                            // Check if cell contains a number
                            if (text && !isNaN(text.replace(/[,$()]/g, ''))) {
                                let value = parseFloat(text.replace(/[,$()]/g, ''));

                                if (window.reportOptions.hideZeroAmounts && value === 0) {
                                    $cell.addClass('zero-amount');
                                }

                                if (window.reportOptions.divideBy1000) {
                                    value = value / 1000;
                                }

                                if (window.reportOptions.roundWholeNumbers) {
                                    value = Math.round(value);
                                }

                                // Format negative numbers
                                if (value < 0) {
                                    $cell.addClass('negative-amount');

                                    switch (window.reportOptions.negativeFormat) {
                                        case '(100)':
                                            $cell.text('(' + Math.abs(value)
                                                .toLocaleString() + ')');
                                            break;
                                        case '100-':
                                            $cell.text(Math.abs(value).toLocaleString() +
                                                '-');
                                            break;
                                        default:
                                            $cell.text('-' + Math.abs(value)
                                                .toLocaleString());
                                    }
                                } else if (value > 0) {
                                    $cell.text(value.toLocaleString());
                                }
                            }
                        });
                    });
                }
            });

            // Keyboard shortcuts
            $(document).on('keydown', function(e) {
                // Ctrl/Cmd + P for print
                if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                    e.preventDefault();
                    $('.btn-icon[title="Print"]').click();
                }

                // Escape to close modals
                if (e.key === 'Escape') {
                    $('.modal-overlay').hide();
                }
            });

            console.log('QuickBooks-style General Ledger initialized successfully');
        });
    </script>
@endpush
