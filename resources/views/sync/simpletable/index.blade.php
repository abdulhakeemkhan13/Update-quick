@extends('layouts.admin')

@section('content')
    <div class="content-wrapper">
        <!-- Header with actions -->
        <div class="report-header">
            <h4 class="mb-0">{{ $pageTitle }}</h4>
            <div class="header-actions">
                <span class="last-updated">Last updated just now</span>
                <div class="actions">
                    <button class="btn btn-icon" title="Refresh" onclick="refreshTableData()"><i class="fa fa-sync"></i></button>
                    <button class="btn btn-icon" onclick="exportDataTable('proposals-by-customer-table', '{{ $pageTitle }}', 'print')" title="Print"><i class="fa fa-print"></i></button>
                    <button class="btn btn-icon" type="button" data-bs-toggle="modal" data-bs-target="#exportModal" title="Export">
                        <i class="fa fa-external-link-alt"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Export Modal -->
        <div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content p-0">
                    <div class="modal-header">
                        <h5 class="modal-title">Choose Export Format</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center row">
                        <div class="col-md-6">
                            <button onclick="exportDataTable('proposals-by-customer-table', '{{ $pageTitle }}')" class="btn btn-success mx-auto w-75" data-bs-dismiss="modal">
                                Export to Excel
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button onclick="exportDataTable('proposals-by-customer-table', '{{ $pageTitle }}', 'pdf')" class="btn btn-success mx-auto w-75" data-bs-dismiss="modal">
                                Export to PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="filter-controls">
            <div class="filter-row">
                <div class="filter-group d-flex">
                    <div class="col-md-7">
                        <div class="row">
                            <div class="filter-item col-md-4">
                                <label class="filter-label">Report Period</label>
                                <select id="header-filter-period" class="form-control filter-period">
                                    <option value="all_dates">All Dates</option>
                                    <option value="today">Today</option>
                                    <option value="yesterday">Yesterday</option>
                                    <option value="this_week">This Week</option>
                                    <option value="last_week">Last Week</option>
                                    <option value="this_month" selected>This Month</option>
                                    <option value="last_month">Last Month</option>
                                    <option value="this_quarter">This Quarter</option>
                                    <option value="last_quarter">Last Quarter</option>
                                    <option value="this_year">This Year</option>
                                    <option value="last_year">Last Year</option>
                                    <option value="last_7_days">Last 7 Days</option>
                                    <option value="last_30_days">Last 30 Days</option>
                                    <option value="last_90_days">Last 90 Days</option>
                                    <option value="custom_date">Custom Date Range</option>
                                </select>
                            </div>
                            <div class="filter-item col-md-2">
                                <label class="filter-label">From</label>
                                <input type="date" class="form-control" id="filter-start-date" value="{{ Carbon\Carbon::now()->startOfMonth()->format('Y-m-d') }}">
                            </div>
                            <div class="filter-item col-md-2">
                                <label class="filter-label">To</label>
                                <input type="date" class="form-control" id="filter-end-date" value="{{ Carbon\Carbon::now()->format('Y-m-d') }}">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="row mt-4">
                            <div class="d-flex gap-2 justify-content-end align-items-center">
                                <button class="btn btn-outline" id="columns-btn">
                                    <i class="fa fa-columns"></i> Columns <span class="badge">8</span>
                                </button>
                                <button class="btn btn-outline" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterSidebar">
                                    <i class="fa fa-filter"></i> Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Sidebar -->
        <div class="offcanvas offcanvas-end" data-bs-scroll="true" tabindex="-1" id="filterSidebar" aria-labelledby="filterSidebarLabel">
            <div class="offcanvas-header" style="background: #f9fafb; border-bottom: 1px solid #e6e6e6;">
                <h5 class="offcanvas-title" id="filterSidebarLabel">Filters</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <div class="filter-item mb-3">
                    <label class="filter-label">Report Period</label>
                    <select id="sidebar-filter-period" class="form-control filter-period">
                        <option value="all_dates">All Dates</option>
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="this_week">This Week</option>
                        <option value="last_week">Last Week</option>
                        <option value="this_month" selected>This Month</option>
                        <option value="last_month">Last Month</option>
                        <option value="this_quarter">This Quarter</option>
                        <option value="last_quarter">Last Quarter</option>
                        <option value="this_year">This Year</option>
                        <option value="last_year">Last Year</option>
                        <option value="last_7_days">Last 7 Days</option>
                        <option value="last_30_days">Last 30 Days</option>
                        <option value="last_90_days">Last 90 Days</option>
                        <option value="custom_date">Custom Date Range</option>
                    </select>
                </div>
                <div class="filter-item mb-3">
                    <label class="filter-label">From</label>
                    <input type="date" class="form-control mb-2" id="sidebar-filter-start-date" value="{{ Carbon\Carbon::now()->startOfMonth()->format('Y-m-d') }}">
                    <label class="filter-label">To</label>
                    <input type="date" class="form-control mb-3" id="sidebar-filter-end-date" value="{{ Carbon\Carbon::now()->format('Y-m-d') }}">
                </div>
                <div class="filter-item mb-3">
                    <label class="filter-label">Status</label>
                    <select id="sidebar-filter-status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="0">Draft</option>
                        <option value="1">Sent</option>
                        <option value="2">Accepted</option>
                        <option value="3">Rejected</option>
                    </select>
                </div>
                <div class="filter-item">
                    <label class="filter-label">Customer</label>
                    <input type="text" class="form-control" id="sidebar-filter-customer" placeholder="Search customer...">
                </div>
            </div>
        </div>

        <!-- Report Content -->
        <div class="report-content">
            <div class="report-title-section">
                <h2 class="report-title">{{ $pageTitle }}</h2>
                <p class="date-range">
                    <span id="date-range-display">From {{ \Carbon\Carbon::parse($startDate ?? date('Y-01-01'))->format('F j, Y') }} to {{ \Carbon\Carbon::parse($endDate ?? date('Y-m-d'))->format('F j, Y') }}</span>
                </p>
            </div>
            <div class="table-container">
                {!! $dataTable->table(['class' => 'table proposals-by-customer-table', 'id' => 'proposals-by-customer-table']) !!}
            </div>
        </div>
    </div>

    <!-- Columns Modal -->
    <div class="modal-overlay" id="columns-overlay">
        <div class="columns-modal">
            <div class="modal-header">
                <h5><i class="fa fa-columns"></i> Columns</h5>
                <button type="button" class="btn-close" id="close-columns">&times;</button>
            </div>
            <div class="modal-content">
                <p class="modal-subtitle">Drag to reorder columns</p>
                <div class="columns-list" id="sortable-columns">
                    <!-- Populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <style>
        * {
            box-sizing: border-box;
        }

        .content-wrapper {
            background-color: #f5f6fa;
            min-height: 100vh;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 14px;
            color: #262626;
        }

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
        .customer-header-row strong{
            text-align: left;
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

        .filter-controls {
            background: white;
            padding: 20px 24px;
            border-bottom: 1px solid #e6e6e6;
        }

        .filter-item {
            display: flex;
            flex-direction: column;
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

        .date-range {
            font-size: 14px;
            color: #374151;
            margin: 0;
        }

        .table-container {
            overflow-x: auto;
            overflow-y: hidden;
        }

        .proposals-by-customer-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            table-layout: fixed;
        }

        .proposals-by-customer-table thead {
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .proposals-by-customer-table th {
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
            padding: 12px 12px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 12px;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .proposals-by-customer-table td {
            padding: 12px 12px;
            border-bottom: 1px solid #f3f4f6;
            color: #262626;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .proposals-by-customer-table tbody tr:hover {
            background: #f9fafb;
        }

        /* Customer Header Row */
        .customer-header-row {
            background-color: #f8f9fa !important;
            border-top: 2px solid #dee2e6;
            border-bottom: 1px solid #dee2e6;
            font-weight: 600;
        }

        .customer-header-row td {
            padding: 12px 12px !important;
            vertical-align: middle;
        }

        .customer-header-row strong {
            display: block;
            white-space: normal;
        }

        .customer-header-row strong:hover {
            cursor: help;
        }

        /* Child Rows */
        .child-row {
            background-color: #ffffff;
        }

        .child-row td {
            padding: 8px 12px !important;
            border-bottom: 1px solid #f0f0f0;
        }

        .child-row td:first-child {
            padding-left: 50px !important;
        }

        .child-row:hover {
            background-color: #f9f9f9;
        }

        /* Chevron Icon */
        .chevron-icon {
            display: inline-block;
            width: 20px;
            height: 20px;
            line-height: 20px;
            text-align: center;
            color: #666;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s ease;
            user-select: none;
            margin-right: 10px;
            flex-shrink: 0;
        }

        .chevron-icon:hover {
            color: #333;
        }

        .customer-total-amount {
            color: #2c5282;
            font-weight: 600;
        }

        /* Grand Total Row */
        .grand-total-row {
            background-color: #e8f4f8 !important;
            border-top: 2px solid #2c5282;
            border-bottom: 2px solid #2c5282;
            font-weight: 700;
        }

        .grand-total-row td {
            padding: 14px 12px !important;
            vertical-align: middle;
            border-bottom: 2px solid #2c5282 !important;
        }

        .grand-total-row:hover {
            background-color: #e8f4f8 !important;
        }

        .grand-total-amount {
            color: #1a365d;
            font-weight: 700;
            font-size: 14px;
        }

        .text-right {
            text-align: right !important;
        }

        /* Column Classes */
        .col-date { width: 22%; }
        .col-num { width: 10%; }
        .col-status { width: 12%; }
        .col-accepted-on { width: 10%; }
        .col-accepted-by { width: 13%; }
        .col-expiration { width: 12%; }
        .col-invoice { width: 10%; }
        .col-amount { width: 11%; }

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

        @media (max-width: 768px) {
            .columns-modal {
                width: 100%;
                left: 0;
            }

            .header-actions {
                flex-direction: column;
                gap: 8px;
            }
        }
    </style>

@endsection

@push('script-page')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <script>
        $(document).ready(function() {
            let lastUpdatedTime = Date.now();

            // Sync period filters
            $('#sidebar-filter-period, #header-filter-period').on('change', function() {
                const value = $(this).val();
                $('#header-filter-period, #sidebar-filter-period').val(value);
                if (value !== 'custom_date') {
                    updateDateRangeFromPeriod(value);
                }
            });

            // Sync date inputs
            $('#filter-start-date, #sidebar-filter-start-date').on('change', function() {
                const value = $(this).val();
                $('#filter-start-date, #sidebar-filter-start-date').val(value);
                updateDateDisplay();
                refreshTableData();
            });

            $('#filter-end-date, #sidebar-filter-end-date').on('change', function() {
                const value = $(this).val();
                $('#filter-end-date, #sidebar-filter-end-date').val(value);
                updateDateDisplay();
                refreshTableData();
            });

            // Status filter
            $('#sidebar-filter-status').on('change', function() {
                refreshTableData();
            });

            // Customer filter
            $('#sidebar-filter-customer').on('keyup', function() {
                refreshTableData();
            });

            // Build columns list
            buildColumnsFromTable();
            $('#proposals-by-customer-table').on('draw.dt', function() {
                buildColumnsFromTable();
            });

            // Columns modal
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

            // Initialize sortable
            if (document.getElementById('sortable-columns')) {
                new Sortable(document.getElementById('sortable-columns'), {
                    animation: 150,
                    handle: '.handle'
                });
            }

            // Column visibility
            $(document).on('change', '.columns-list input[type="checkbox"]', function() {
                updateColumnCountBadge();
            });

            // Update last updated time
            setInterval(function() {
                updateLastUpdated(lastUpdatedTime);
            }, 30000);

            // Setup DataTable parameters
            $('#proposals-by-customer-table').on('preXhr.dt', function(e, settings, data) {
                data.startDate = $('#filter-start-date').val();
                data.endDate = $('#filter-end-date').val();
                data.status = $('#sidebar-filter-status').val();
                data.customer_name = $('#sidebar-filter-customer').val();
            });
        });

        function updateDateRangeFromPeriod(period) {
            const today = moment();
            let startDate, endDate;

            switch (period) {
                case 'all_dates':
                    return;
                case 'today':
                    startDate = today.clone();
                    endDate = today.clone();
                    break;
                case 'yesterday':
                    startDate = today.clone().subtract(1, 'day');
                    endDate = today.clone().subtract(1, 'day');
                    break;
                case 'this_week':
                    startDate = today.clone().startOf('week');
                    endDate = today.clone().endOf('week');
                    break;
                case 'last_week':
                    startDate = today.clone().subtract(1, 'week').startOf('week');
                    endDate = today.clone().subtract(1, 'week').endOf('week');
                    break;
                case 'this_month':
                    startDate = today.clone().startOf('month');
                    endDate = today.clone().endOf('month');
                    break;
                case 'last_month':
                    startDate = today.clone().subtract(1, 'month').startOf('month');
                    endDate = today.clone().subtract(1, 'month').endOf('month');
                    break;
                case 'this_quarter':
                    startDate = today.clone().startOf('quarter');
                    endDate = today.clone().endOf('quarter');
                    break;
                case 'last_quarter':
                    startDate = today.clone().subtract(1, 'quarter').startOf('quarter');
                    endDate = today.clone().subtract(1, 'quarter').endOf('quarter');
                    break;
                case 'this_year':
                    startDate = today.clone().startOf('year');
                    endDate = today.clone().endOf('year');
                    break;
                case 'last_year':
                    startDate = today.clone().subtract(1, 'year').startOf('year');
                    endDate = today.clone().subtract(1, 'year').endOf('year');
                    break;
                case 'last_7_days':
                    startDate = today.clone().subtract(6, 'days');
                    endDate = today.clone();
                    break;
                case 'last_30_days':
                    startDate = today.clone().subtract(29, 'days');
                    endDate = today.clone();
                    break;
                case 'last_90_days':
                    startDate = today.clone().subtract(89, 'days');
                    endDate = today.clone();
                    break;
                default:
                    return;
            }

            $('#filter-start-date, #sidebar-filter-start-date').val(startDate.format('YYYY-MM-DD'));
            $('#filter-end-date, #sidebar-filter-end-date').val(endDate.format('YYYY-MM-DD'));
            updateDateDisplay();
            refreshTableData();
        }

        function updateDateDisplay() {
            const startDate = moment($('#filter-start-date').val());
            const endDate = moment($('#filter-end-date').val());
            $('#date-range-display').text('From ' + startDate.format('MMMM D, YYYY') + ' to ' + endDate.format('MMMM D, YYYY'));
        }

        function refreshTableData() {
            if (window.LaravelDataTables && window.LaravelDataTables["proposals-by-customer-table"]) {
                window.LaravelDataTables["proposals-by-customer-table"].draw();
            }
        }

        function buildColumnsFromTable() {
            const headers = document.querySelectorAll('#proposals-by-customer-table thead th');
            const container = document.querySelector('#sortable-columns');

            if (!container) return;
            
            container.innerHTML = '';
            headers.forEach((th, index) => {
                const columnName = th.innerText.trim().toUpperCase();
                const div = document.createElement('div');
                div.classList.add('column-item');
                div.setAttribute('data-column', index);
                div.innerHTML = `
                    <i class="fa fa-grip-vertical handle"></i>
                    <label class="checkbox-label">
                        <input type="checkbox" checked> ${columnName}
                    </label>
                `;
                container.appendChild(div);
            });
        }

        function updateColumnCountBadge() {
            const count = document.querySelectorAll('.columns-list input[type="checkbox"]:checked').length;
            const badge = document.querySelector('#columns-btn .badge');
            if (badge) badge.textContent = count;
        }

        function updateLastUpdated(time) {
            const $last = $('.last-updated');
            const seconds = Math.floor((Date.now() - time) / 1000);

            if (seconds < 60) {
                $last.text('Last updated just now');
            } else if (seconds < 3600) {
                const minutes = Math.floor(seconds / 60);
                $last.text('Last updated ' + minutes + ' min' + (minutes > 1 ? 's' : '') + ' ago');
            } else {
                const hours = Math.floor(seconds / 3600);
                $last.text('Last updated ' + hours + ' hour' + (hours > 1 ? 's' : '') + ' ago');
            }
        }

        function exportDataTable(tableId, pageTitle, format = 'excel') {
            let table = $('#' + tableId).DataTable();

            let columns = [];
            $('#' + tableId + ' thead th:visible').each(function() {
                columns.push($(this).text().trim());
            });

            let data = [];
            table.rows({search: 'applied'}).every(function() {
                let rowData = this.data();
                if (typeof rowData === 'object') {
                    let rowArray = [];
                    table.columns(':visible').every(function(colIdx) {
                        let val = rowData[this.dataSrc()] ?? '-';
                        rowArray.push(val);
                    });
                    rowData = rowArray;
                }
                data.push(rowData);
            });

            $.ajax({
                url: '{{ route('export.datatable') }}',
                method: 'POST',
                data: {
                    columns: columns,
                    data: data,
                    pageTitle: pageTitle,
                    format: format,
                    _token: '{{ csrf_token() }}'
                },
                xhrFields: {responseType: 'blob'},
                success: function(blob, status, xhr) {
                    let filename = xhr.getResponseHeader('Content-Disposition')?.split('filename=')[1]?.replace(/"/g, '') || pageTitle + '.' + (format === 'pdf' ? 'pdf' : 'xlsx');

                    if (format === 'print') {
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
                error: function() {
                    alert('Export failed!');
                }
            });
        }

        // Chevron functionality
        document.addEventListener('DOMContentLoaded', function() {
            const table = document.getElementById('proposals-by-customer-table');
            if (!table) return;

            const expandedGroups = new Map();
            table.querySelectorAll('.customer-header-row').forEach(row => {
                const customerId = row.getAttribute('data-customer-id');
                expandedGroups.set(customerId, true);
            });

            table.addEventListener('click', function(e) {
                const chevron = e.target.closest('.chevron-icon');
                if (!chevron) return;

                e.preventDefault();
                e.stopPropagation();

                const customerId = chevron.getAttribute('data-parent-id');
                const isExpanded = expandedGroups.get(customerId);
                const childRows = table.querySelectorAll('.child-of-customer-' + customerId);
                const headerRow = chevron.closest('tr');
                const customerName = headerRow.getAttribute('data-customer-name');
                const formattedTotal = headerRow.getAttribute('data-formatted-total');
                const strong = chevron.nextElementSibling;

                if (isExpanded) {
                    // collapsing
                    childRows.forEach(row => row.style.display = 'none');
                    expandedGroups.set(customerId, false);
                    chevron.style.transform = 'rotate(-90deg)';
                    strong.innerHTML = customerName + ' - Total: ' + formattedTotal;
                } else {
                    // expanding
                    childRows.forEach(row => row.style.display = '');
                    expandedGroups.set(customerId, true);
                    chevron.style.transform = 'rotate(0deg)';
                    strong.innerHTML = customerName;
                }
            });
        });

        // Refresh button animation
        document.querySelector('.btn-icon[title="Refresh"]').addEventListener('click', function() {
            this.querySelector('i').style.animation = 'spin 1s linear';
            setTimeout(() => {
                this.querySelector('i').style.animation = '';
            }, 1000);
        });
    </script>

    <style>
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>

    {!! $dataTable->scripts() !!}
@endpush