@extends('layouts.admin')

@section('content')
    <style>
        /* ===== IBCS-ish skin (same as your working page) ===== */
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

        .btn-qb-action,
        .btn-qb-option {
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
        }

        .btn-qb-action:hover,
        .btn-qb-option:hover {
            background: #f3f4f6;
            color: #374151;
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
            max-height: 500px;
            overflow: auto;
        }

        .sales-by-product-service-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .sales-by-product-service-table th {
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

        .sales-by-product-service-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f3f4f6;
            color: #262626;
            vertical-align: middle;
        }

        .sales-by-product-service-table tbody tr:hover {
            background: #f9fafb;
        }

        .text-right {
            text-align: right;
        }

        .total-row {
            background: #f8f9fa !important;
            font-weight: 700;
        }

        .report-footer {
            padding: 20px;
            border-top: 1px solid #e6e6e6;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
        }

        /* ===== Drawers: same structure/classes as the working page ===== */
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

        /* Columns drawer styling */
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

        .checkbox-label input {
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

            .sales-by-product-service-table {
                font-size: 11px;
            }

            .sales-by-product-service-table th,
            .sales-by-product-service-table td {
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
            <h4 class="mb-0">{{ __('Sales by Product/Service Summary') }}</h4>
            <div class="header-actions">
                <span class="last-updated">Last updated just now</span>
                <div class="actions">
                    <button class="btn btn-icon" title="Refresh" id="btn-refresh"><i class="fa fa-sync"></i></button>
                    <button class="btn btn-icon"
                        onclick="exportDataTable('sales-by-product-service-table', '{{ __('Sales by Product/Service Summary') }}', 'print')"><i
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
                            <button
                                onclick="exportDataTable('sales-by-product-service-table', '{{ __('Sales by Product/Service Summary') }}')"
                                class="btn btn-success mx-auto w-75 justify-content-center text-center"
                                data-action="excel">Export to
                                Excel</button>
                        </div>
                        <div class="col-md-6">
                            <button
                                onclick="exportDataTable('sales-by-product-service-table', '{{ __('Sales by Product/Service Summary') }}', 'pdf')"
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
                        ReportPeriod: window.reportOptions.reportPeriod ? $(".report-title-section #date-range-display")
                            .text()
                            .replace(/\s+/g, ' ')
                            .trim() : "",
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
                <h2 class="report-title">{{ __('Sales by Product/Service Summary') }}</h2>
                <p class="company-name">{{ config('app.name', 'Your Company Name') }}</p>
                <p class="date-range">
                    <span id="date-range-display">
                        {{ \Carbon\Carbon::parse($filter['startDateRange'])->format('F j, Y') ?? '' }}
                        -
                        {{ \Carbon\Carbon::parse($filter['endDateRange'])->format('F j, Y') ?? '' }}
                    </span>
                </p>
            </div>

            <div class="table-container">
                {!! $dataTable->table([
                    'class' => 'table sales-by-product-service-table',
                    'id' => 'sales-by-product-service-table',
                ]) !!}
            </div>

            <!-- TOTAL row -->
            <div style="background:#fff;">
                <table class="table sales-by-product-service-table" style="margin-bottom:0;border-top:2px solid #dee2e6;">
                    <tbody>
                        <tr class="total-row">
                            <td style="padding:12px 16px;border-bottom:none;">TOTAL</td>
                            <td class="text-right" style="border-bottom:none;" id="total-quantity-display">0</td>
                            <td class="text-right" style="border-bottom:none;" id="total-amount-display">$ 0.00</td>
                            <td class="text-right" style="border-bottom:none;" id="total-percent-display">100.0%</td>
                            <td class="text-right" style="border-bottom:none;" id="total-avg-price-display">$ 0.00</td>
                            <td class="text-right" style="border-bottom:none;" id="total-cogs-display">$ 0.00</td>
                            <td class="text-right" style="border-bottom:none;" id="total-avg-cogs-display">$ 0.00</td>
                            <td class="text-right" style="border-bottom:none;" id="total-gm-display">$ 0.00</td>
                            <td class="text-right" style="border-bottom:none;" id="total-gm-percent-display">0.0%</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="report-footer"></div>
        </div>
    </div>

    <!-- ==== Drawers (same classes as working page) ==== -->

    <!-- Filter Drawer -->
    <div class="modal-overlay" id="filter-overlay">
        <div class="filter-modal">
            <div class="modal-header">
                <h5>Filter</h5>
                <button type="button" class="btn-close" id="close-filter">&times;</button>
            </div>
            <div class="modal-content">
                <p class="modal-subtitle">Updates apply immediately.</p>

                <div class="filter-group">
                    <label for="start-date">From</label>
                    <input type="date" id="start-date" class="form-control"
                        value="{{ $filter['startDateRange'] ?? '' }}">
                </div>
                <div class="filter-group">
                    <label for="end-date">To</label>
                    <input type="date" id="end-date" class="form-control"
                        value="{{ $filter['endDateRange'] ?? '' }}">
                </div>

                <div class="filter-group">
                    <label for="filter-product-name">Product/Service Name</label>
                    <input type="text" id="filter-product-name" class="form-control" placeholder="Search by name..."
                        value="{{ $filter['selectedProductName'] ?? '' }}">
                </div>

                <div class="filter-group">
                    <label for="filter-category">Category</label>
                    <select id="filter-category" class="form-control">
                        @foreach ($categories as $id => $name)
                            <option value="{{ $id }}"
                                {{ ($filter['selectedCategory'] ?? '') == $id ? 'selected' : '' }}>{{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label for="filter-type">Type</label>
                    <select id="filter-type" class="form-control">
                        @foreach ($types as $key => $value)
                            <option value="{{ $key }}"
                                {{ ($filter['selectedType'] ?? '') == $key ? 'selected' : '' }}>{{ $value }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- General Options Drawer -->
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
                <button type="button" class="btn btn-cancel" id="cancel-general-options"
                    style="background:#f8f9fa;color:#666;border:1px solid #ddd;">Cancel</button>
                <button type="button" class="btn btn-apply" id="apply-general-options"
                    style="background:#0066cc;color:#fff;border:1px solid #0066cc;">Apply</button>
            </div>
        </div>
    </div>

    <!-- View Options Drawer -->
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

    <!-- Columns Drawer -->
    <div class="modal-overlay" id="columns-overlay">
        <div class="columns-modal">
            <div class="modal-header">
                <h5 class="qb-columns-title">Columns</h5>
                <button type="button" class="btn-close" id="close-columns">&times;</button>
            </div>
            <div class="modal-content">
                <div class="qb-columns-help">Add, remove and reorder the columns. Drag to reorder.</div>
                <ul id="qb-columns-list">
                    <li class="qb-col-item" data-column="0"><span class="qb-handle"><i
                                class="fa fa-grip-vertical"></i></span><label class="qb-pill"><input type="checkbox"
                                data-col="0" checked><span class="pill"><i class="fa fa-check"></i></span><span
                                class="qb-col-name">Product/Service</span></label></li>
                    <li class="qb-col-item" data-column="1"><span class="qb-handle"><i
                                class="fa fa-grip-vertical"></i></span><label class="qb-pill"><input type="checkbox"
                                data-col="1" checked><span class="pill"><i class="fa fa-check"></i></span><span
                                class="qb-col-name">Quantity</span></label></li>
                    <li class="qb-col-item" data-column="2"><span class="qb-handle"><i
                                class="fa fa-grip-vertical"></i></span><label class="qb-pill"><input type="checkbox"
                                data-col="2" checked><span class="pill"><i class="fa fa-check"></i></span><span
                                class="qb-col-name">Amount</span></label></li>
                    <li class="qb-col-item" data-column="3"><span class="qb-handle"><i
                                class="fa fa-grip-vertical"></i></span><label class="qb-pill"><input type="checkbox"
                                data-col="3" checked><span class="pill"><i class="fa fa-check"></i></span><span
                                class="qb-col-name">% of Sales</span></label></li>
                    <li class="qb-col-item" data-column="4"><span class="qb-handle"><i
                                class="fa fa-grip-vertical"></i></span><label class="qb-pill"><input type="checkbox"
                                data-col="4" checked><span class="pill"><i class="fa fa-check"></i></span><span
                                class="qb-col-name">Avg Price</span></label></li>
                    <li class="qb-col-item" data-column="5"><span class="qb-handle"><i
                                class="fa fa-grip-vertical"></i></span><label class="qb-pill"><input type="checkbox"
                                data-col="5" checked><span class="pill"><i class="fa fa-check"></i></span><span
                                class="qb-col-name">COGS</span></label></li>
                    <li class="qb-col-item" data-column="6"><span class="qb-handle"><i
                                class="fa fa-grip-vertical"></i></span><label class="qb-pill"><input type="checkbox"
                                data-col="6" checked><span class="pill"><i class="fa fa-check"></i></span><span
                                class="qb-col-name">Avg COGS</span></label></li>
                    <li class="qb-col-item" data-column="7"><span class="qb-handle"><i
                                class="fa fa-grip-vertical"></i></span><label class="qb-pill"><input type="checkbox"
                                data-col="7" checked><span class="pill"><i class="fa fa-check"></i></span><span
                                class="qb-col-name">Gross Margin</span></label></li>
                    <li class="qb-col-item" data-column="8"><span class="qb-handle"><i
                                class="fa fa-grip-vertical"></i></span><label class="qb-pill"><input type="checkbox"
                                data-col="8" checked><span class="pill"><i class="fa fa-check"></i></span><span
                                class="qb-col-name">Gross Margin %</span></label></li>
                </ul>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    {{-- DataTables + extensions --}}
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
            /* ========= Last Updated ticker ========= */
            const $last = $('.last-updated');
            let lastUpdatedAt = Date.now(),
                tickerId = null;
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
            }

            function markNow() {
                lastUpdatedAt = Date.now();
                $last.text(`Last updated ${rel(lastUpdatedAt)}`);
                if (tickerId) clearInterval(tickerId);
                tickerId = setInterval(() => {
                    $last.text(`Last updated ${rel(lastUpdatedAt)}`)
                }, 30000)
            }
            markNow();

            /* ========= Helpers: parse/format numbers ========= */
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

            function nf2(x) {
                return '$ ' + (Number(x || 0)).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            /* ========= Drawer open/close ========= */
            const openOvr = (id) => $(id).addClass('drawer-open');
            const closeOvr = (id) => $(id).removeClass('drawer-open');

            $('#filter-btn').on('click', () => openOvr('#filter-overlay'));
            $('#general-options-btn').on('click', () => openOvr('#general-options-overlay'));
            $('#columns-btn').on('click', () => {
                syncListToCurrentOrder();
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

            /* ========= Header actions ========= */
            // $('#btn-print').on('click', () => window.print());
            // $('#btn-export').on('click', () => alert('Export action triggered'));
            $('#btn-save').on('click', function() {
                const name = prompt('Enter report name:', 'Sales by Product/Service Summary - ' + new Date()
                    .toISOString().slice(0, 10));
                if (name) alert('Report "' + name + '" would be saved with current settings.')
            });

            /* ========= Report Options / Footer ========= */
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

            function footerRender(o) {
                const now = new Date();
                const parts = [];
                if (o.datePrepared) parts.push(`Date Prepared: ${now.toLocaleDateString()}`);
                if (o.timePrepared) parts.push(`Time Prepared: ${now.toLocaleTimeString()}`);
                if (o.showReportBasis) parts.push(`Report Basis: ${o.reportBasis} Basis`);
                $('.report-footer').css('text-align', o.footerAlignment || 'center').html(parts.map(p =>
                    `<div>${p}</div>`).join(''));
            }

            /* ========= Per-column reformatter ========= */
            const NUM_QTY = [1],
                NUM_MONEY = [2, 4, 5, 6, 7],
                NUM_PERCENT = [3, 8];

            function dtApi() {
                return window.LaravelDataTables && window.LaravelDataTables["sales-by-product-service-table"];
            }

            function reformatVisibleNumericCells() {
                const dt = dtApi();
                if (!dt) return;
                dt.rows({
                    page: 'current'
                }).every(function() {
                    const $row = $(this.node());
                    // qty
                    NUM_QTY.forEach(idx => {
                        const cell = dt.cell($row, idx);
                        const out = formatAmount(cell.data(), false);
                        $(cell.node()).html(`<span class="${out.classes}">${out.html}</span>`);
                    });
                    // money
                    NUM_MONEY.forEach(idx => {
                        const cell = dt.cell($row, idx);
                        const out = formatAmount(cell.data(), true);
                        $(cell.node()).html(`<span class="${out.classes}">${out.html}</span>`);
                    });
                    // percent (leave as %, hide if zero when requested)
                    const o = window.reportOptions || {};
                    NUM_PERCENT.forEach(idx => {
                        const cell = dt.cell($row, idx);
                        const val = parseNum(cell.data());
                        if (o.hideZeroAmounts && Math.abs(val) < 1e-12) {
                            $(cell.node()).html('<span class="zero-amount"></span>');
                        } else {
                            $(cell.node()).html(((val) || 0).toFixed(1) + '%');
                        }
                    });
                });
            }

            /* ========= Apply General options ========= */
            function applyGeneralOptions() {
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
                footerRender(o);
                reformatVisibleNumericCells();
            }
            $('#apply-general-options').on('click', function() {
                applyGeneralOptions();
                closeOvr('#general-options-overlay');
            });
            // live preview when toggling inside the drawer
            $('#general-options-overlay input, #general-options-overlay select').on('change', applyGeneralOptions);

            // Initial footer/styles
            numberCSS(window.reportOptions);
            footerRender(window.reportOptions);

            /* ========= View Options ========= */
            function applyViewOptions() {
                $('#custom-view-styles').remove();
                let css = '<style id="custom-view-styles">';
                css += $('#opt-compact').prop('checked') ?
                    '.sales-by-product-service-table th,.sales-by-product-service-table td{padding:8px 12px;}' :
                    '.sales-by-product-service-table th,.sales-by-product-service-table td{padding:12px 16px;}';
                css += $('#opt-hover').prop('checked') ?
                    '.sales-by-product-service-table tbody tr:hover{background:#f9fafb;}' :
                    '.sales-by-product-service-table tbody tr:hover{background:inherit;}';
                if ($('#opt-striped').prop('checked')) css +=
                    '.sales-by-product-service-table tbody tr:nth-child(even){background-color:#f8f9fa;}';
                css += $('#opt-borders').prop('checked') ?
                    '.sales-by-product-service-table th,.sales-by-product-service-table td{border:1px solid #e5e7eb;}' :
                    '.sales-by-product-service-table th,.sales-by-product-service-table td{border:none;border-bottom:1px solid #f3f4f6;}';
                css += $('#opt-wrap').prop('checked') ?
                    '.sales-by-product-service-table th,.sales-by-product-service-table td{white-space:normal;word-wrap:break-word;}' :
                    '.sales-by-product-service-table th,.sales-by-product-service-table td{white-space:nowrap;}';
                css += $('#opt-auto-width').prop('checked') ?
                    '.sales-by-product-service-table{table-layout:auto;}' :
                    '.sales-by-product-service-table{table-layout:fixed;}';
                if ($('#opt-equal-width').prop('checked')) css +=
                    '.sales-by-product-service-table th,.sales-by-product-service-table td{width:10%;}';
                const fs = $('#font-size').val();
                css +=
                    `.sales-by-product-service-table, .sales-by-product-service-table th, .sales-by-product-service-table td{font-size:${fs};}`;
                css += '</style>';
                $('head').append(css);
            }
            $('#view-options-overlay input, #view-options-overlay select').on('change', applyViewOptions);
            applyViewOptions();

            /* ========= Period & Filter ========= */
            function updateHeaderDate() {
                const s = $('#start-date').val(),
                    e = $('#end-date').val();
                if (!s || !e) return;
                const so = new Date(s),
                    eo = new Date(e),
                    opt = {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    };
                $('#date-range-display').text(so.toLocaleDateString('en-US', opt) + ' - ' + eo.toLocaleDateString(
                    'en-US', opt));
            }

            function setPeriod(period) {
                const today = new Date();
                const dcopy = d => new Date(d.getTime());
                let s = '',
                    e = '';
                switch (period) {
                    case 'today':
                        s = e = today.toISOString().split('T')[0];
                        break;
                    case 'this_week': {
                        const t = dcopy(today);
                        const start = new Date(t.setDate(t.getDate() - t.getDay()));
                        const end = new Date(start.getFullYear(), start.getMonth(), start.getDate() + 6);
                        s = start.toISOString().split('T')[0];
                        e = end.toISOString().split('T')[0];
                    }
                    break;
                    case 'this_month':
                        s = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
                        e = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().split('T')[0];
                        break;
                    case 'this_quarter': {
                        const q = Math.floor(today.getMonth() / 3);
                        s = new Date(today.getFullYear(), q * 3, 1).toISOString().split('T')[0];
                        e = new Date(today.getFullYear(), q * 3 + 3, 0).toISOString().split('T')[0];
                    }
                    break;
                    case 'this_year':
                        s = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
                        e = new Date(today.getFullYear(), 11, 31).toISOString().split('T')[0];
                        break;
                    case 'last_week': {
                        const t = dcopy(today);
                        const start = new Date(t.setDate(t.getDate() - t.getDay() - 7));
                        const end = new Date(start.getFullYear(), start.getMonth(), start.getDate() + 6);
                        s = start.toISOString().split('T')[0];
                        e = end.toISOString().split('T')[0];
                    }
                    break;
                    case 'last_month':
                        s = new Date(today.getFullYear(), today.getMonth() - 1, 1).toISOString().split('T')[0];
                        e = new Date(today.getFullYear(), today.getMonth(), 0).toISOString().split('T')[0];
                        break;
                    case 'last_quarter': {
                        let q = Math.floor(today.getMonth() / 3) - 1;
                        const year = q < 0 ? today.getFullYear() - 1 : today.getFullYear();
                        const adjQ = (q + 4) % 4;
                        s = new Date(year, adjQ * 3, 1).toISOString().split('T')[0];
                        e = new Date(year, adjQ * 3 + 3, 0).toISOString().split('T')[0];
                    }
                    break;
                    case 'last_year':
                        s = new Date(today.getFullYear() - 1, 0, 1).toISOString().split('T')[0];
                        e = new Date(today.getFullYear() - 1, 11, 31).toISOString().split('T')[0];
                        break;
                    case 'last_7_days':
                        s = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 6).toISOString()
                            .split('T')[0];
                        e = new Date().toISOString().split('T')[0];
                        break;
                    case 'last_30_days':
                        s = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 29).toISOString()
                            .split('T')[0];
                        e = new Date().toISOString().split('T')[0];
                        break;
                    case 'last_90_days':
                        s = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 89).toISOString()
                            .split('T')[0];
                        e = new Date().toISOString().split('T')[0];
                        break;
                    case 'last_12_months': {
                        const S = new Date(today.getFullYear(), today.getMonth() - 11, 1);
                        const E = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                        s = S.toISOString().split('T')[0];
                        e = E.toISOString().split('T')[0];
                    }
                    break;
                    case 'all_dates':
                        s = '2000-01-01';
                        e = new Date().toISOString().split('T')[0];
                        break;
                    default:
                        return;
                }
                $('#start-date').val(s);
                $('#end-date').val(e);
                updateHeaderDate();
                reloadWithParams();
            }
            $('#report-period').on('change', function() {
                if ($(this).val() === 'custom') {
                    openOvr('#filter-overlay');
                } else {
                    setPeriod($(this).val());
                }
            });

            function reloadWithParams() {
                const rp = $('#report-period').val(),
                    s = $('#start-date').val(),
                    e = $('#end-date').val();
                const pn = $('#filter-product-name').val(),
                    cat = $('#filter-category').val(),
                    typ = $('#filter-type').val();
                const dt = dtApi();
                if (dt) {
                    const url = new URL(window.location.origin + window.location.pathname);
                    if (rp && rp !== 'all_dates') url.searchParams.set('report_period', rp);
                    if (s) url.searchParams.set('start_date', s);
                    if (e) url.searchParams.set('end_date', e);
                    if (pn) url.searchParams.set('product_name', pn);
                    if (cat) url.searchParams.set('category', cat);
                    if (typ) url.searchParams.set('type', typ);
                    dt.ajax.url(url.href).load();
                    markNow();
                }
            }
            $('#start-date,#end-date,#filter-product-name,#filter-category,#filter-type').on('change', function() {
                updateHeaderDate();
                reloadWithParams();
            });

            /* ========= Columns (drag + show/hide) ========= */
            if (document.getElementById('qb-columns-list')) {
                new Sortable(document.getElementById('qb-columns-list'), {
                    animation: 150,
                    handle: '.qb-handle',
                    onEnd: function() {
                        const newOrder = $('#qb-columns-list .qb-col-item').map(function() {
                            return parseInt($(this).attr('data-column'), 10)
                        }).get();
                        const dt = dtApi();
                        if (!dt) return;
                        try {
                            if (dt.colReorder && typeof dt.colReorder.order === 'function') {
                                dt.colReorder.order(newOrder, true);
                                localStorage.setItem('sbps-column-order', JSON.stringify(newOrder));
                                dt.columns.adjust().draw(false);
                                reformatVisibleNumericCells();
                            }
                        } catch (e) {}
                    }
                });
            }
            $('#qb-columns-list').on('change', 'input[type="checkbox"][data-col]', function() {
                const origIndex = parseInt($(this).data('col'), 10);
                const dt = dtApi();
                if (!dt) return;
                let curIndex = origIndex;
                if (dt.colReorder && typeof dt.colReorder.transpose === 'function') {
                    curIndex = dt.colReorder.transpose(origIndex, 'toCurrent');
                }
                const visible = $(this).is(':checked');
                try {
                    dt.column(curIndex).visible(visible, false);
                    dt.columns.adjust().draw(false);
                    reformatVisibleNumericCells();
                } catch (e) {}
            });

            function syncListToCurrentOrder() {
                const dt = dtApi();
                if (!dt || !dt.colReorder || typeof dt.colReorder.order !== 'function') return;
                const order = dt.colReorder.order();
                const $list = $('#qb-columns-list');
                const items = $list.children('li').get();
                items.sort(function(a, b) {
                    const aOrig = parseInt($(a).attr('data-column'), 10);
                    const bOrig = parseInt($(b).attr('data-column'), 10);
                    const aCur = order.indexOf(aOrig);
                    const bCur = order.indexOf(bOrig);
                    return aCur - bCur;
                });
                $list.empty().append(items);
            }

            /* ========= Hook DT lifecycle ========= */
            $('#btn-refresh').on('click', function() {
                $(this).find('i').addClass('fa-spin');
                const dt = dtApi();
                if (dt) dt.ajax.reload(null, false);
            });
            $('#sales-by-product-service-table').on('xhr.dt', function() {
                markNow();
                $('#btn-refresh i').removeClass('fa-spin');
                const dt = dtApi();
                if (dt && dt.ajax && dt.ajax.json && dt.ajax.json()) {
                    const json = dt.ajax.json();
                    if (json && json.totals) {
                        const t = json.totals;
                        $('#total-quantity-display').text(Math.round(t.quantity || 0));
                        $('#total-amount-display').text(nf2(t.amount || 0));
                        $('#total-cogs-display').text(nf2(t.cogs || 0));
                        $('#total-gm-display').text(nf2(t.gross_margin || 0));
                        $('#total-gm-percent-display').text(((t.gross_margin_percent || 0)).toFixed(1) +
                            '%');
                        $('#total-percent-display').text(((t.percent_of_sales || 100)).toFixed(1) + '%');
                        const qty = t.quantity || 0,
                            avgPrice = qty > 0 ? (t.amount || 0) / qty : 0,
                            avgCogs = qty > 0 ? (t.cogs || 0) / qty : 0;
                        $('#total-avg-price-display').text(nf2(avgPrice));
                        $('#total-avg-cogs-display').text(nf2(avgCogs));
                    }
                }
            });

            // After DT ready: fixed header, right-align numerics and reformat cells
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
                    dt.columns().every(function() {
                        const title = (this.header().textContent || '').toLowerCase();
                        const isNum = /(quantity|amount|price|cogs|margin|%)/.test(title);
                        if (isNum) $(this.nodes()).addClass('text-right');
                    });
                    reformatVisibleNumericCells();
                });
                dt.draw(false);
            });

            // Initial styling application
            applyViewOptions();
            numberCSS(window.reportOptions);
            footerRender(window.reportOptions);
        });
    </script>
@endpush
