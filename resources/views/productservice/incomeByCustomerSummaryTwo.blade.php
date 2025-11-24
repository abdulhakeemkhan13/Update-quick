@extends('layouts.admin')

@section('page-title')
    {{ __('Income By Customer Summary') }}
@endsection

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
            padding: 8px 16px;
            border-bottom: 1px solid #e6e6e6;
            overflow: hidden;
        }

        .controls-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: nowrap;
            max-width: 100%;
        }

        .left-controls {
            display: flex;
            gap: 12px;
            align-items: flex-end;
            flex-wrap: nowrap;
            flex-shrink: 1;
            min-width: 0;
        }

        .right-controls {
            display: flex;
            gap: 6px;
            align-items: center;
            flex-shrink: 0;
            margin-left: auto;
        }

        .btn-outline {
            background: #fff;
            color: #374151;
            padding: 8px 12px;
            font-size: 13px;
        }

        .btn-outline:hover {
            background: #f9fafb;
            border-color: #9ca3af;
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
            text-decoration: none;
            transition: all 0.15s ease;
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
            text-decoration: none;
            transition: all 0.15s ease;
            border-radius: 4px;
            white-space: nowrap;
        }

        .btn-qb-action:hover {
            background: #f3f4f6;
            color: #374151;
        }

        .btn-qb-action i {
            margin-right: 4px;
            font-size: 12px;
        }

        .btn-quickbooks {
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            color: #374151;
            padding: 6px 12px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .btn-quickbooks:hover {
            background: #f9fafb;
            border-color: #9ca3af;
            color: #1f2937;
        }

        .btn-quickbooks:active {
            background: #f3f4f6;
            border-color: #6b7280;
        }

        .btn-quickbooks i {
            margin-right: 4px;
            font-size: 12px;
        }

        .filter-item {
            display: flex;
            flex-direction: column;
            gap: 3px;
            flex-shrink: 0;
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
            max-height: 500px;
            overflow-y: auto;
        }

        /* Add scrolling container */
        .product-service-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
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
            z-index: 10;
        }

        .product-service-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f3f4f6;
            color: #262626;
        }

        .product-service-table tbody tr:hover {
            background: #f9fafb;
        }

        .product-service-table .text-right {
            text-align: right;
        }

        .product-service-table tfoot th {
            background: #f8f9fa;
            border-top: 2px solid #dee2e6;
            font-weight: bold;
            position: sticky;
            bottom: 0;
            z-index: 9;
        }

        .product-service-table .total-row {
            background: #f8f9fa !important;
        }

        /* ===== Drawer-style Modals ===== */
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

        .filter-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
            background: #fff;
            color: #262626;
            height: 36px;
        }

        /* Options blocks */
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

        /* Drawer override (slide from right) */
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

        .modal-overlay.drawer-open {
            cursor: pointer;
        }

        .modal-overlay.drawer-open .filter-modal,
        .modal-overlay.drawer-open .general-options-modal,
        .modal-overlay.drawer-open .columns-modal,
        .modal-overlay.drawer-open .view-options-modal {
            cursor: default;
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

            .product-service-table {
                font-size: 11px;
            }

            .product-service-table th,
            .product-service-table td {
                padding: 6px 4px;
            }
        }

        @media(max-width:768px) {
            .report-content {
                margin: 12px;
            }
        }
    </style>

    <style id="controls-row-fix">
        /* Keep the entire controls row on a single line and center vertically */
        .controls-bar {
            padding: 10px 24px;
        }

        .controls-inner {
            display: flex;
            align-items: center;
            /* vertical centering */
            gap: 14px;
            flex-wrap: nowrap;
            /* single row */
            overflow: hidden;
        }

        /* Left controls: label+input stacks stay aligned as a group */
        .left-controls {
            display: flex;
            align-items: center !important;
            /* override earlier flex-end */
            gap: 12px;
            flex-wrap: nowrap;
            /* single row */
            min-width: 0;
        }

        /* Tighter label spacing; consistent control heights */
        .filter-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin: 0;
        }

        .filter-label {
            margin: 0;
            line-height: 1;
            font-size: 12px;
            white-space: nowrap;
        }

        /* Normalize heights for selects/inputs/buttons in this row */
        .controls-bar .form-control,
        .controls-bar .form-select {
            height: 32px;
            padding: 6px 8px;
            font-size: 13px;
        }

        .controls-bar .btn-qb-option,
        .controls-bar .btn-qb-action,
        .controls-bar .btn-quickbooks {
            height: 32px;
            display: inline-flex;
            align-items: center;
            line-height: 30px;
        }

        /* Keep the ‘View options’ link visually aligned with fields */
        #view-options-btn {
            padding: 6px 10px;
            align-self: center;
        }

        /* Right controls always stay on the same line */
        .right-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: auto;
            flex-shrink: 0;
            white-space: nowrap;
        }

        /* Prevent accidental wrapping of the tiny captions in tight widths */
        .controls-bar select,
        .controls-bar input[type="date"] {
            white-space: nowrap;
        }

        /* Optional: if viewport gets too narrow, allow horizontal scroll instead of wrapping */
        @media (max-width: 1020px) {
            .controls-bar {
                overflow-x: auto;
            }

            .controls-inner {
                min-width: max-content;
            }
        }
    </style>


    <div class="quickbooks-report">
        <!-- Header with actions -->
        <div class="report-header">
            <h4 class="mb-0">{{ $pageTitle }}</h4>
            <div class="header-actions">
                <span class="last-updated">Last updated just now</span>
                <div class="actions">
                    <button class="btn btn-icon" title="Refresh" id="btn-refresh"><i class="fa fa-sync"></i></button>
                    <button class="btn btn-icon"
                        onclick="exportDataTable('product-service-table', '{{ __('Income By Customer Summary') }}', 'print')"><i
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
                            <button onclick="exportDataTable('product-service-table', '{{ __('Income By Customer Summary') }}')"
                                class="btn btn-success mx-auto w-75 justify-content-center text-center"
                                data-action="excel">Export to
                                Excel</button>
                        </div>
                        <div class="col-md-6">
                            <button
                                onclick="exportDataTable('product-service-table', '{{ __('Income By Customer Summary') }}', 'pdf')"
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
                <div class="left-controls"
                    style="display:flex; gap:12px; align-items:flex-end; flex-wrap:nowrap; flex-shrink:0;">
                    <div class="filter-item">
                        <label class="filter-label">Report period</label>
                        <select class="form-select" id="report-period" style="width: 130px; font-size: 13px;">
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
                        <label class="filter-label">From</label>
                        <input type="date" class="form-control" id="start-date"
                            value="{{ $filter['startDateRange'] ?? '' }}" style="width: 110px; font-size: 13px;">
                    </div>

                    <div class="filter-item">
                        <label class="filter-label">To</label>
                        <input type="date" class="form-control" id="end-date"
                            value="{{ $filter['endDateRange'] ?? '' }}" style="width: 110px; font-size: 13px;">
                    </div>

                    <div class="filter-item">
                        <label class="filter-label">Accounting method</label>
                        <select class="form-select" id="accounting-method" style="width: 100px; font-size: 13px;">
                            <option value="accrual"
                                {{ ($filter['accountingMethod'] ?? 'accrual') == 'accrual' ? 'selected' : '' }}>Accrual
                            </option>
                            <option value="cash"
                                {{ ($filter['accountingMethod'] ?? 'accrual') == 'cash' ? 'selected' : '' }}>Cash</option>
                        </select>
                    </div>

                    <button class="btn btn-qb-option pt-4" id="view-options-btn">
                        <i class="fa fa-eye"></i> View options
                    </button>
                </div>
                <div class="right-controls d-flex pt-3" style="gap: 6px; align-items: center; flex-shrink: 0;">
                    <button class="btn btn-qb-action" id="columns-btn">
                        <i class="fa fa-table-columns"></i> Compare
                    </button>
                    <button class="btn btn-qb-action" id="filter-btn">
                        <i class="fa fa-filter"></i> Filter
                    </button>
                    <button class="btn btn-qb-action" id="general-options-btn">
                        <i class="fa fa-cog"></i> General options
                    </button>
                </div>
            </div>
        </div>

        <!-- Report -->
        <div class="report-content">
            <div class="report-title-section">
                <h2 class="report-title">{{ $pageTitle }}</h2>
                <p class="company-name">{{ config('app.name', 'Your Company Name') }}</p>
                <p class="date-range">
                    <span id="date-range-display">
                        {{ $filter['selectedCustomerName'] ?? '' ? 'Customer: ' . $filter['selectedCustomerName'] : 'All Customers' }}
                    </span>
                </p>
            </div>

            <div class="table-container">
                {!! $dataTable->table(['class' => 'table product-service-table', 'id' => 'product-service-table']) !!}
            </div>

            <!-- Hard-coded TOTAL row with proper table alignment -->
            <div class="total-summary-container" style="background:#fff;">
                <table class="table product-service-table" style="margin-bottom:0;border-top:2px solid #dee2e6;">
                    <tbody>
                        <tr class="total-row" style="background-color: #f8f9fa; font-weight: bold;">
                            <td style="font-weight: bold; padding: 12px 16px; border-bottom: none;">TOTAL</td>
                            <td class="text-right" style="font-weight: bold; padding: 12px 16px; border-bottom: none;"
                                id="total-income-display">$ 0.00</td>
                            <td class="text-right" style="font-weight: bold; padding: 12px 16px; border-bottom: none;"
                                id="total-expenses-display">$ 0.00</td>
                            <td class="text-right" style="font-weight: bold; padding: 12px 16px; border-bottom: none;"
                                id="total-net-display">$ 0.00</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Footer will be injected by JS -->
        </div>
    </div>

    <!-- Filter Modal (live dropdown) -->
    <div class="modal-overlay" id="filter-overlay">
        <div class="filter-modal">
            <div class="modal-header">
                <h5>Filter <i class="fa fa-info-circle" title="Filter by customer name"></i></h5>
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

    <!-- General Options Modal -->
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
                                <span style="min-width:110px;display:inline-block;">Format</span>
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
                        <label class="checkbox-label"><input type="checkbox" id="report-title" checked> Report
                            title</label>
                        <label class="checkbox-label"><input type="checkbox" id="company-name" checked> Company
                            name</label>
                        <label class="checkbox-label"><input type="checkbox" id="report-period" checked> Report
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

    <!-- View Options Modal -->
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

    <!-- Columns Modal -->
    <div class="modal-overlay" id="columns-overlay">
        <div class="columns-modal">
            <div class="modal-header">
                <h5>Columns <i class="fa fa-info-circle"></i></h5>
                <button type="button" class="btn-close" id="close-columns">&times;</button>
            </div>
            <div class="modal-content">
                <p class="modal-subtitle">Drag to reorder, uncheck to hide columns.</p>
                <div class="columns-list" id="sortable-columns">
                    <div class="column-item" data-column="0">
                        <i class="fa fa-grip-vertical handle"></i>
                        <label class="checkbox-label"><input type="checkbox" data-col="0" checked> Customer</label>
                    </div>
                    <div class="column-item" data-column="1">
                        <i class="fa fa-grip-vertical handle"></i>
                        <label class="checkbox-label"><input type="checkbox" data-col="1" checked> Income</label>
                    </div>
                    <div class="column-item" data-column="2">
                        <i class="fa fa-grip-vertical handle"></i>
                        <label class="checkbox-label"><input type="checkbox" data-col="2" checked> Expenses</label>
                    </div>
                    <div class="column-item" data-column="3">
                        <i class="fa fa-grip-vertical handle"></i>
                        <label class="checkbox-label"><input type="checkbox" data-col="3" checked> Net Income</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/colreorder/1.7.0/js/dataTables.colReorder.min.js"></script>
    <script src="https://cdn.datatables.net/fixedheader/3.4.0/js/dataTables.fixedHeader.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/colreorder/1.7.0/css/colReOrder.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.4.0/css/fixedHeader.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    {!! $dataTable->scripts() !!}

    <script>
        $(function() {
            /* ================= Last Updated ticker ================= */
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

            /* ===== Drawer open/close ===== */
            $('#view-options-btn').on('click', () => $('#view-options-overlay').addClass('drawer-open'));
            $('#columns-btn').on('click', () => $('#columns-overlay').addClass('drawer-open'));
            $('#filter-btn').on('click', () => $('#filter-overlay').addClass('drawer-open'));
            $('#general-options-btn').on('click', () => $('#general-options-overlay').addClass('drawer-open'));

            $('#close-filter').on('click', () => $('#filter-overlay').removeClass('drawer-open'));
            $('#close-general-options, #cancel-general-options').on('click', () => $('#general-options-overlay')
                .removeClass('drawer-open'));
            $('#close-view-options').on('click', () => $('#view-options-overlay').removeClass('drawer-open'));
            $('#close-columns').on('click', () => $('#columns-overlay').removeClass('drawer-open'));

            $('#filter-overlay').on('click', e => {
                if (e.target.id === 'filter-overlay') $(e.currentTarget).removeClass('drawer-open');
            });
            $('#general-options-overlay').on('click', e => {
                if (e.target.id === 'general-options-overlay') $(e.currentTarget).removeClass(
                'drawer-open');
            });
            $('#view-options-overlay').on('click', e => {
                if (e.target.id === 'view-options-overlay') $(e.currentTarget).removeClass('drawer-open');
            });
            $('#columns-overlay').on('click', e => {
                if (e.target.id === 'columns-overlay') $(e.currentTarget).removeClass('drawer-open');
            });

            /* ===== Header actions ===== */
            $('#btn-refresh').on('click', function() {
                const $i = $(this).find('i').addClass('fa-spin');
                const dt = window.LaravelDataTables && window.LaravelDataTables["product-service-table"];
                if (dt) dt.ajax.reload(null, false);
            });
            $('#product-service-table').on('xhr.dt', function() {
                console.log('DataTable XHR fired - data loading');
                markNow();
                $('#btn-refresh i').removeClass('fa-spin');

                // Update totals display after data loads
                const dt = window.LaravelDataTables && window.LaravelDataTables["product-service-table"];
                if (dt && dt.ajax && dt.ajax.json && dt.ajax.json()) {
                    const json = dt.ajax.json();
                    console.log('DataTable response received:', json);
                    if (json && json.totals) {
                        console.log('Updating totals with:', json.totals);

                        function nf(x) {
                            try {
                                return '$ ' + new Intl.NumberFormat('en-US', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                }).format(x || 0);
                            } catch (e) {
                                return '$ ' + (x || 0);
                            }
                        }

                        $('#total-income-display').text(nf(json.totals.income || 0));
                        $('#total-expenses-display').text(nf(json.totals.expenses || 0));
                        $('#total-net-display').text(nf(json.totals.net || 0));
                    } else {
                        console.log('No totals found in response');
                    }
                } else {
                    console.log('DataTable AJAX response not available');
                }
            });

            // $('#btn-print').on('click', () => window.print());
            // $('#btn-export').on('click', () => alert('Export action triggered'));
            $('#btn-more').on('click', () => alert('More options clicked'));
            $('#btn-save').on('click', function() {
                const name = prompt('Enter report name:', 'Income By Customer Summary - ' + new Date()
                    .toISOString().slice(0, 10));
                if (name) alert('Report "' + name + '" would be saved with current settings.');
            });

            /* ===== Date Range and Filter Controls ===== */
            $('#report-period, #start-date, #end-date, #accounting-method').on('change', function() {
                const reportPeriod = $('#report-period').val();
                const startDate = $('#start-date').val();
                const endDate = $('#end-date').val();
                const accountingMethod = $('#accounting-method').val();

                console.log('Filter changed:', {
                    reportPeriod: reportPeriod,
                    startDate: startDate,
                    endDate: endDate,
                    accountingMethod: accountingMethod
                });

                // Get the DataTable instance
                const dt = window.LaravelDataTables && window.LaravelDataTables["product-service-table"];
                if (dt) {
                    console.log('Applying filters to DataTable');

                    // Set up the new AJAX URL with parameters
                    const url = new URL(window.location.origin + window.location.pathname);

                    if (reportPeriod && reportPeriod !== 'all_dates') {
                        url.searchParams.set('report_period', reportPeriod);
                    }

                    if (startDate && startDate !== '') {
                        url.searchParams.set('start_date', startDate);
                    }

                    if (endDate && endDate !== '') {
                        url.searchParams.set('end_date', endDate);
                    }

                    if (accountingMethod && accountingMethod !== 'accrual') {
                        url.searchParams.set('accounting_method', accountingMethod);
                    }

                    console.log('New DataTable URL:', url.href);

                    // Update the DataTable AJAX URL and reload
                    dt.ajax.url(url.href).load(function() {
                        console.log('DataTable successfully reloaded with new filters');
                    }, false);

                } else {
                    console.error('DataTable not found:', window.LaravelDataTables);
                }

                markNow();
            });

            // Auto-populate date fields when report period changes
            $('#report-period').on('change', function() {
                const period = $(this).val();
                const today = new Date();
                let startDate = '',
                    endDate = '';

                switch (period) {
                    case 'today':
                        startDate = endDate = today.toISOString().split('T')[0];
                        break;
                    case 'this_week':
                        const startOfWeek = new Date(today.setDate(today.getDate() - today.getDay()));
                        const endOfWeek = new Date(today.setDate(today.getDate() - today.getDay() + 6));
                        startDate = startOfWeek.toISOString().split('T')[0];
                        endDate = endOfWeek.toISOString().split('T')[0];
                        break;
                    case 'this_month':
                        startDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split(
                            'T')[0];
                        endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString()
                            .split('T')[0];
                        break;
                    case 'this_year':
                        startDate = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
                        endDate = new Date(today.getFullYear(), 11, 31).toISOString().split('T')[0];
                        break;
                    case 'last_month':
                        startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1).toISOString()
                            .split('T')[0];
                        endDate = new Date(today.getFullYear(), today.getMonth(), 0).toISOString().split(
                            'T')[0];
                        break;
                    case 'last_7_days':
                        startDate = new Date(today.setDate(today.getDate() - 7)).toISOString().split('T')[
                        0];
                        endDate = new Date().toISOString().split('T')[0];
                        break;
                    case 'last_30_days':
                        startDate = new Date(today.setDate(today.getDate() - 30)).toISOString().split('T')[
                            0];
                        endDate = new Date().toISOString().split('T')[0];
                        break;
                    case 'custom':
                        // Leave dates as they are for custom selection
                        return;
                    default:
                        startDate = endDate = '';
                }

                if (period !== 'custom') {
                    $('#start-date').val(startDate);
                    $('#end-date').val(endDate);
                }
            });

            /* ===== LIVE Filter (Customer Name dropdown) ===== */
            $('#filter-customer-name').on('change', function() {
                const customerName = $('#filter-customer-name').val();
                const url = new URL(window.location);

                if (customerName) url.searchParams.set('customer_name', customerName);
                else url.searchParams.delete('customer_name');

                const dt = window.LaravelDataTables && window.LaravelDataTables["product-service-table"];
                if (dt) dt.ajax.url(url.href).load();

                // Update display text
                let displayText = 'All Customers';
                if (customerName) {
                    displayText = `Customer: ${customerName}`;
                }

                $('#date-range-display').text(displayText);
                $('#filter-overlay').removeClass('drawer-open');
            });

            /* ===== General Options (state + apply) ===== */
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

            function numberCSS(opts) {
                $('#custom-number-format').remove();
                let css = '<style id="custom-number-format">';
                if (opts.showInRed) css += '.negative-amount{color:#dc2626!important;}';
                if (opts.hideZeroAmounts) css += '.zero-amount{display:none!important;}';
                css += '</style>';
                $('head').append(css);
            }

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

            function applyGeneralOptions() {
                const o = window.reportOptions;
                o.divideBy1000 = $('#divide-by-1000').prop('checked');
                o.hideZeroAmounts = $('#hide-zero-amounts').prop('checked');
                o.roundWholeNumbers = $('#round-whole-numbers').prop('checked');
                o.negativeFormat = $('#negative-format').val();
                o.showInRed = $('#show-in-red').prop('checked');
                o.companyLogo = $('#company-logo').prop('checked');
                o.reportTitle = $('#report-title').prop('checked');
                o.companyName = $('#company-name').prop('checked');
                o.reportPeriod = $('#report-period').prop('checked');
                o.headerAlignment = $('#header-alignment').val();
                o.datePrepared = $('#date-prepared').prop('checked');
                o.timePrepared = $('#time-prepared').prop('checked');
                o.showReportBasis = $('#show-report-basis').prop('checked');
                o.reportBasis = $('#report-basis').val();
                o.footerAlignment = $('#footer-alignment').val();

                numberCSS(o);
                headerApply(o);
                footerRender(o);

                const dt = window.LaravelDataTables && window.LaravelDataTables["product-service-table"];
                if (dt) dt.draw(false);
            }
            $('#apply-general-options').on('click', function() {
                applyGeneralOptions();
                $('#general-options-overlay').removeClass('drawer-open');
            });
            $('#cancel-general-options').on('click', function() {
                $('#general-options-overlay').removeClass('drawer-open');
            });
            $('.general-options-modal input, .general-options-modal select').on('change', applyGeneralOptions);

            // Toggle sections
            $('.section-title').on('click', function() {
                const $g = $(this).next('.option-group');
                $g.slideToggle(120);
                $(this).find('.fa-chevron-up, .fa-chevron-down').toggleClass(
                    'fa-chevron-up fa-chevron-down');
            });

            /* ===== View Options (appearance only) ===== */
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
                    '.product-service-table th,.product-service-table td{width:25%;}';
                const fs = $('#font-size').val();
                css +=
                    `.product-service-table, .product-service-table th, .product-service-table td{font-size:${fs};}`;
                css += '</style>';
                $('head').append(css);

                if ($('#opt-sticky-head').prop('checked')) {
                    // Enable sticky header and footer
                    $('.product-service-table thead th').css({
                        'position': 'sticky',
                        'top': '0',
                        'z-index': '10',
                        'background': '#f9fafb'
                    });
                    $('.product-service-table tfoot th').css({
                        'position': 'sticky',
                        'bottom': '0',
                        'z-index': '9',
                        'background': '#f8f9fa'
                    });
                } else {
                    $('.product-service-table thead th').css({
                        'position': 'static'
                    });
                    $('.product-service-table tfoot th').css({
                        'position': 'static'
                    });
                }
            }
            $('#view-options-overlay input, #view-options-overlay select').on('change', applyViewOptions);

            /* ===== Columns (visibility + order) ===== */
            if (document.getElementById('sortable-columns')) {
                new Sortable(document.getElementById('sortable-columns'), {
                    animation: 150,
                    handle: '.handle',
                    onEnd: function() {
                        const order = [];
                        $('#sortable-columns .column-item').each(function() {
                            order.push(parseInt($(this).data('column'), 10));
                        });
                        localStorage.setItem('income-by-customer-column-order', JSON.stringify(order));
                        const dt = window.LaravelDataTables && window.LaravelDataTables[
                            "product-service-table"];
                        if (dt && typeof dt.colReorder !== 'undefined') {
                            try {
                                dt.colReorder.order(order, true);
                            } catch (e) {}
                        }
                    }
                });
            }
            $('#columns-overlay input[type="checkbox"][data-col]').on('change', function() {
                const colIdx = parseInt($(this).data('col'), 10);
                const visible = $(this).is(':checked');
                const dt = window.LaravelDataTables && window.LaravelDataTables["product-service-table"];
                if (dt) {
                    try {
                        dt.column(colIdx).visible(visible, false);
                        dt.columns.adjust().draw(false);
                    } catch (e) {}
                }
            });

            /* ===== Keyboard: ESC closes drawers ===== */
            $(document).on('keydown', e => {
                if (e.key === 'Escape') $('.modal-overlay').removeClass('drawer-open');
            });

            /* ===== Init ===== */
            setTimeout(function() {
                applyGeneralOptions();
                applyViewOptions();
                footerRender(window.reportOptions);
            }, 100);
        });
    </script>
@endpush
