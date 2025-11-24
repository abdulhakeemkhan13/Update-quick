@extends('layouts.admin')

@section('page-title')
    {{ __('Taxable Sales Detail') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Taxable Sales Detail') }}</li>
@endsection

@push('css-page')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/colreorder/1.7.0/css/colReOrder.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.4.0/css/fixedHeader.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        /* ===== IBCC look & feel ===== */
        .quickbooks-report {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            min-height: 100vh;
            color: #262626
        }

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
            flex-wrap: nowrap;
            overflow: hidden
        }

        .left-controls {
            display: flex;
            gap: 12px;
            align-items: flex-end;
            flex-wrap: nowrap;
            min-width: 0
        }

        .right-controls {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-shrink: 0;
            white-space: nowrap
        }

        .filter-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin: 0
        }

        .filter-label {
            font-size: 12px;
            color: #374151;
            margin: 0;
            line-height: 1;
            font-weight: 500;
            white-space: nowrap
        }

        .form-select,
        .form-control {
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 6px 8px;
            font-size: 13px;
            height: 32px;
            background: #fff;
            color: #374151
        }

        .form-select:focus,
        .form-control:focus {
            outline: none;
            border-color: #0066cc;
            box-shadow: 0 0 0 2px rgba(0, 102, 204, .2)
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

        /* Table */
        .table-container {
            background: #fff;
            max-height: 500px;
            overflow-y: auto
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
            z-index: 10;
            white-space: nowrap
        }

        .product-service-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f3f4f6;
            color: #262626;
            vertical-align: middle;
            white-space: nowrap
        }

        .product-service-table tbody tr:hover {
            background: #f9fafb
        }

        .text-right {
            text-align: right
        }

        /* Drawers / modals */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .5);
            z-index: 1050;
            overflow-y: auto
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
            box-shadow: 0 4px 20px rgba(0, 0, 0, .3)
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

        .filter-group {
            margin-bottom: 20px
        }

        .filter-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #2c3e50;
            font-size: 13px
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
            height: 36px
        }

        .modal-overlay.drawer-open {
            display: block
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
                opacity: 0
            }

            to {
                transform: translateX(0);
                opacity: 1
            }
        }

        .qb-columns-title {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #1f2937
        }

        .qb-columns-help {
            color: #6b7280;
            font-size: 13px;
            margin: 8px 0 16px
        }

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
            border-radius: 6px
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

            .report-header,
            .controls-bar {
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

        @media(max-width:768px) {
            .report-content {
                margin: 12px
            }
        }

        .dataTables_scrollHead,
        .dataTables_scrollBody {
            display: block;
            width: 100% !important;
        }

        .dataTables_scrollHeadInner {
            width: 100% !important;
        }

        .dataTables_scrollHeadInner table,
        .dataTables_scrollBody table {
            width: 100% !important;
            table-layout: auto;
        }
    </style>
@endpush

@push('script-page')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/colreorder/1.7.0/js/dataTables.colReorder.min.js"></script>
    <script src="https://cdn.datatables.net/fixedheader/3.4.0/js/dataTables.fixedHeader.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
@endpush

@section('content')
    <div class="quickbooks-report">
        <!-- Header -->
        <div class="report-header">
            <h4 class="mb-0">{{ __('Taxable Sales Detail') }}</h4>
            <div class="header-actions">
                <span class="last-updated">Last updated just now</span>
                <div class="actions">
                    <button class="btn btn-icon" id="btn-refresh" title="Refresh"><i class="fa fa-sync"></i></button>
                    <button class="btn btn-icon"
                        onclick="exportDataTable('ledger-table', '{{ __('Taxable Sales Detail') }}', 'print')"><i
                            class="fa fa-print"></i></button>
                    <button class="btn btn-icon" title="Export" id="btn-export"><i
                            class="fa fa-external-link-alt"></i></button>
                    <button class="btn btn-icon" id="btn-more" title="More"><i class="fa fa-ellipsis-v"></i></button>
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
                                onclick="exportDataTable('ledger-table', '{{ __('Taxable Sales Detail') }}')"
                                class="btn btn-success mx-auto w-75 justify-content-center text-center"
                                data-action="excel">Export to
                                Excel</button>
                        </div>
                        <div class="col-md-6">
                            <button
                                onclick="exportDataTable('ledger-table', '{{ __('Taxable Sales Detail') }}', 'pdf')"
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
                        <label class="filter-label">{{ __('Report period') }}</label>
                        <select class="form-select" id="report-period" style="width:160px">
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
                        <label class="filter-label">{{ __('Accounting method') }}</label>
                        <select class="form-select" id="accounting-method" style="width:120px">
                            <option value="accrual"
                                {{ ($filter['accountingMethod'] ?? 'accrual') == 'accrual' ? 'selected' : '' }}>Accrual</option>
                            <option value="cash" {{ ($filter['accountingMethod'] ?? 'accrual') == 'cash' ? 'selected' : '' }}>
                                Cash</option>
                        </select>
                    </div>

                    <button class="btn btn-icon" id="view-options-btn" title="View options"><i
                            class="fa fa-eye"></i></button>
                </div>

                <div class="right-controls">
                    <button class="btn btn-icon" id="columns-btn" title="Columns"><i
                            class="fa fa-table-columns"></i></button>
                    <button class="btn btn-icon" id="filter-btn" title="Filter"><i class="fa fa-filter"></i></button>
                    <button class="btn btn-icon" id="general-options-btn" title="General options"><i
                            class="fa fa-cog"></i></button>
                </div>
            </div>
        </div>

        <!-- Report -->
        <div class="report-content">
            <div class="report-title-section">
                <h2 class="report-title">{{ __('Taxable Sales Detail') }}</h2>
                <p class="company-name">{{ $user->name ?? 'Company' }}</p>
                <p class="date-range">
                    <span id="date-range-display">
                        @if (!empty($filter['startDateRange']) && !empty($filter['endDateRange']))
                            {{ \Carbon\Carbon::parse($filter['startDateRange'])->format('F j, Y') }} -
                            {{ \Carbon\Carbon::parse($filter['endDateRange'])->format('F j, Y') }}
                        @else
                            {{ __('All Dates') }}
                        @endif
                    </span>
                </p>
            </div>

            <div class="table-container">
                <table class="table product-service-table" id="ledger-table">
                    <thead>
                        <tr>
                            <th>{{ __('Transaction date') }}</th>
                            <th>{{ __('Transaction type') }}</th>
                            <th>{{ __('Num') }}</th>
                            <th>{{ __('Customer full name') }}</th>
                            <th>{{ __('Memo/Description') }}</th>
                            <th class="text-right">{{ __('Quantity') }}</th>
                            <th class="text-right">{{ __('Sales price') }}</th>
                            <th class="text-right">{{ __('Amount') }}</th>
                            <th class="text-right">{{ __('Balance') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Filter Drawer (From/To) --}}
    <div class="modal-overlay" id="filter-overlay">
        <div class="filter-modal">
            <div class="modal-header">
                <h5>Filter</h5>
                <button type="button" class="btn-close" id="close-filter">&times;</button>
            </div>
            <div class="modal-content">
                <p class="modal-subtitle">Updates apply immediately.</p>
                <div class="filter-group">
                    <label for="start-date">{{ __('From') }}</label>
                    <input type="date" id="start-date" class="form-control"
                        value="{{ $filter['startDateRange'] ?? '' }}">
                </div>
                <div class="filter-group">
                    <label for="end-date">{{ __('To') }}</label>
                    <input type="date" id="end-date" class="form-control"
                        value="{{ $filter['endDateRange'] ?? '' }}">
                </div>
            </div>
        </div>
    </div>

    {{-- General Options Drawer --}}
    <div class="modal-overlay" id="general-options-overlay">
        <div class="general-options-modal">
            <div class="modal-header">
                <h5>General options</h5>
                <button type="button" class="btn-close" id="close-general-options">&times;</button>
            </div>
            <div class="modal-content">
                <p class="modal-subtitle">Select general options for your report.</p>
                <div class="option-section" style="border:1px solid #e9ecef;border-radius:4px;margin-bottom:20px">
                    <h6 class="section-title"
                        style="background:#f8f9fa;padding:12px 15px;margin:0;font-size:14px;font-weight:600;color:#2c3e50;border-bottom:1px solid #e9ecef;cursor:pointer;display:flex;justify-content:space-between;align-items:center">
                        Number format <i class="fa fa-chevron-up"></i>
                    </h6>
                    <div class="option-group" style="padding:15px">
                        <label class="checkbox-label"><input type="checkbox" id="divide-by-1000"> Divide by 1000</label>
                        <label class="checkbox-label"><input type="checkbox" id="hide-zero-amounts"> Don't show zero
                            amounts</label>
                        <label class="checkbox-label"><input type="checkbox" id="round-whole-numbers"> Round to whole
                            numbers</label>
                    </div>
                </div>

                <div class="option-section" style="border:1px solid #e9ecef;border-radius:4px;margin-bottom:20px">
                    <h6 class="section-title"
                        style="background:#f8f9fa;padding:12px 15px;margin:0;font-size:14px;font-weight:600;color:#2c3e50;border-bottom:1px solid #e9ecef;cursor:pointer;display:flex;justify-content:space-between;align-items:center">
                        Negative numbers <i class="fa fa-chevron-up"></i>
                    </h6>
                    <div class="option-group" style="padding:15px">
                        <div style="display:flex;gap:12px;align-items:center">
                            <select id="negative-format" class="form-control" style="width:110px">
                                <option value="-100" selected>-100</option>
                                <option value="(100)">(100)</option>
                                <option value="100-">100-</option>
                            </select>
                            <label class="checkbox-label" style="margin:0"><input type="checkbox" id="show-in-red"> Show
                                in red</label>
                        </div>
                    </div>
                </div>

                <div class="option-section" style="border:1px solid #e9ecef;border-radius:4px;margin-bottom:20px">
                    <h6 class="section-title"
                        style="background:#f8f9fa;padding:12px 15px;margin:0;font-size:14px;font-weight:600;color:#2c3e50;border-bottom:1px solid #e9ecef;cursor:pointer;display:flex;justify-content:space-between;align-items:center">
                        Header <i class="fa fa-chevron-up"></i>
                    </h6>
                    <div class="option-group" style="padding:15px">
                        <label class="checkbox-label"><input type="checkbox" id="opt-report-title" checked> Report
                            title</label>
                        <label class="checkbox-label"><input type="checkbox" id="opt-company-name" checked> Company
                            name</label>
                        <label class="checkbox-label"><input type="checkbox" id="opt-report-period" checked> Report
                            period</label>
                        <div style="margin-top:8px">
                            <label class="alignment-label">Header alignment</label>
                            <select id="header-alignment" class="form-control" style="max-width:180px">
                                <option value="center" selected>Center</option>
                                <option value="left">Left</option>
                                <option value="right">Right</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="option-section" style="border:1px solid #e9ecef;border-radius:4px;margin-bottom:20px">
                    <h6 class="section-title"
                        style="background:#f8f9fa;padding:12px 15px;margin:0;font-size:14px;font-weight:600;color:#2c3e50;border-bottom:1px solid #e9ecef;cursor:pointer;display:flex;justify-content:space-between;align-items:center">
                        Footer <i class="fa fa-chevron-up"></i>
                    </h6>
                    <div class="option-group" style="padding:15px">
                        <label class="checkbox-label"><input type="checkbox" id="date-prepared" checked> Date
                            prepared</label>
                        <label class="checkbox-label"><input type="checkbox" id="time-prepared" checked> Time
                            prepared</label>
                        <label class="checkbox-label"><input type="checkbox" id="show-report-basis" checked> Report
                            basis</label>
                        <div style="display:flex;gap:12px;align-items:center;margin-top:6px">
                            <span style="min-width:110px">Basis</span>
                            <select id="report-basis" class="form-control" style="max-width:180px">
                                <option value="Accrual" selected>Accrual</option>
                                <option value="Cash">Cash</option>
                            </select>
                        </div>
                        <div style="margin-top:8px">
                            <label class="alignment-label">Footer alignment</label>
                            <select id="footer-alignment" class="form-control" style="max-width:180px">
                                <option value="center" selected>Center</option>
                                <option value="left">Left</option>
                                <option value="right">Right</option>
                            </select>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer"
                style="padding:15px 25px;border-top:1px solid #e9ecef;display:flex;justify-content:flex-end;gap:10px">
                <button class="btn" id="cancel-general-options"
                    style="background:#f8f9fa;color:#666;border:1px solid #ddd">Cancel</button>
                <button class="btn" id="apply-general-options"
                    style="background:#0066cc;color:#fff;border:1px solid #0066cc">Apply</button>
            </div>
        </div>
    </div>

    {{-- View Options Drawer --}}
    <div class="modal-overlay" id="view-options-overlay">
        <div class="view-options-modal">
            <div class="modal-header">
                <h5>View options</h5>
                <button type="button" class="btn-close" id="close-view-options">&times;</button>
            </div>
            <div class="modal-content">
                <p class="modal-subtitle">Choose display preferences. These do not affect data.</p>
                <div class="option-section" style="border:1px solid #e9ecef;border-radius:4px;margin-bottom:20px">
                    <h6 class="section-title"
                        style="background:#f8f9fa;padding:12px 15px;margin:0;font-size:14px;font-weight:600;color:#2c3e50">
                        Table density</h6>
                    <div class="option-group" style="padding:15px">
                        <label class="checkbox-label"><input type="checkbox" id="opt-compact"> Compact rows</label>
                        <label class="checkbox-label"><input type="checkbox" id="opt-hover" checked> Row hover
                            effects</label>
                    </div>
                </div>

                <div class="option-section" style="border:1px solid #e9ecef;border-radius:4px;margin-bottom:20px">
                    <h6 class="section-title"
                        style="background:#f8f9fa;padding:12px 15px;margin:0;font-size:14px;font-weight:600;color:#2c3e50">
                        Row style</h6>
                    <div class="option-group" style="padding:15px">
                        <label class="checkbox-label"><input type="checkbox" id="opt-striped" checked> Striped
                            rows</label>
                        <label class="checkbox-label"><input type="checkbox" id="opt-borders"> Show borders</label>
                        <label class="checkbox-label"><input type="checkbox" id="opt-wrap"> Wrap long text</label>
                        <label class="checkbox-label"><input type="checkbox" id="opt-sticky-head" checked> Sticky
                            header</label>
                    </div>
                </div>

                <div class="option-section" style="border:1px solid #e9ecef;border-radius:4px;margin-bottom:20px">
                    <h6 class="section-title"
                        style="background:#f8f9fa;padding:12px 15px;margin:0;font-size:14px;font-weight:600;color:#2c3e50">
                        Column width</h6>
                    <div class="option-group" style="padding:15px">
                        <label class="checkbox-label"><input type="checkbox" id="opt-auto-width" checked> Auto-fit
                            columns</label>
                        <label class="checkbox-label"><input type="checkbox" id="opt-equal-width"> Equal column
                            widths</label>
                    </div>
                </div>

                <div class="option-section" style="border:1px solid #e9ecef;border-radius:4px;margin-bottom:20px">
                    <h6 class="section-title"
                        style="background:#f8f9fa;padding:12px 15px;margin:0;font-size:14px;font-weight:600;color:#2c3e50">
                        Font size</h6>
                    <div class="option-group" style="padding:15px">
                        <label class="checkbox-label" style="gap:12px">
                            <span>Table font size</span>
                            <select id="font-size" class="form-control" style="width:160px">
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

    {{-- Columns Drawer --}}
    <div class="modal-overlay" id="columns-overlay">
        <div class="columns-modal">
            <div class="modal-header">
                <h5 class="qb-columns-title">Columns</h5>
                <button type="button" class="btn-close" id="close-columns">&times;</button>
            </div>
            <div class="modal-content">
                <div class="qb-columns-help">Add, remove and reorder the columns. Drag to reorder.</div>
                <ul id="qb-columns-list">
                    {{-- data-column = original DT column index --}}
                    <li class="qb-col-item" data-column="0"><span class="qb-handle"><i
                                class="fa fa-grip-vertical"></i></span>
                        <label class="qb-pill"><input type="checkbox" data-col="0" checked><span class="pill"><i
                                    class="fa fa-check"></i></span><span class="qb-col-name">Transaction
                                date</span></label>
                    </li>
                    <li class="qb-col-item" data-column="1"><span class="qb-handle"><i
                                class="fa fa-grip-vertical"></i></span>
                        <label class="qb-pill"><input type="checkbox" data-col="1" checked><span class="pill"><i
                                    class="fa fa-check"></i></span><span class="qb-col-name">Transaction
                                type</span></label>
                    </li>
                    <li class="qb-col-item" data-column="2"><span class="qb-handle"><i
                                class="fa fa-grip-vertical"></i></span>
                        <label class="qb-pill"><input type="checkbox" data-col="2" checked><span class="pill"><i
                                    class="fa fa-check"></i></span><span class="qb-col-name">Num</span></label>
                    </li>
                    <li class="qb-col-item" data-column="3"><span class="qb-handle"><i
                                class="fa fa-grip-vertical"></i></span>
                        <label class="qb-pill"><input type="checkbox" data-col="3" checked><span class="pill"><i
                                    class="fa fa-check"></i></span><span class="qb-col-name">Customer full
                                name</span></label>
                    </li>
                    <li class="qb-col-item" data-column="4"><span class="qb-handle"><i
                                class="fa fa-grip-vertical"></i></span>
                        <label class="qb-pill"><input type="checkbox" data-col="4" checked><span class="pill"><i
                                    class="fa fa-check"></i></span><span
                                class="qb-col-name">Memo/Description</span></label>
                    </li>
                    <li class="qb-col-item" data-column="5"><span class="qb-handle"><i
                                class="fa fa-grip-vertical"></i></span>
                        <label class="qb-pill"><input type="checkbox" data-col="5" checked><span class="pill"><i
                                    class="fa fa-check"></i></span><span class="qb-col-name">Quantity</span></label>
                    </li>
                    <li class="qb-col-item" data-column="6"><span class="qb-handle"><i
                                class="fa fa-grip-vertical"></i></span>
                        <label class="qb-pill"><input type="checkbox" data-col="6" checked><span class="pill"><i
                                    class="fa fa-check"></i></span><span class="qb-col-name">Sales price</span></label>
                    </li>
                    <li class="qb-col-item" data-column="7"><span class="qb-handle"><i
                                class="fa fa-grip-vertical"></i></span>
                        <label class="qb-pill"><input type="checkbox" data-col="7" checked><span class="pill"><i
                                    class="fa fa-check"></i></span><span class="qb-col-name">Amount</span></label>
                    </li>
                    <li class="qb-col-item" data-column="8"><span class="qb-handle"><i
                                class="fa fa-grip-vertical"></i></span>
                        <label class="qb-pill"><input type="checkbox" data-col="8" checked><span class="pill"><i
                                    class="fa fa-check"></i></span><span class="qb-col-name">Balance</span></label>
                    </li>
                </ul>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
    <script>
        $(function() {
            /* ===== Last updated ticker ===== */
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
            };
            const markNow = () => {
                lastUpdatedAt = Date.now();
                $last.text(`Last updated ${rel(lastUpdatedAt)}`);
                if (tickerId) clearInterval(tickerId);
                tickerId = setInterval(() => {
                    $last.text(`Last updated ${rel(lastUpdatedAt)}`)
                }, 30000);
            };
            markNow();

            /* ===== helpers (format) ===== */
            function money(val) {
                const n = Number(val || 0);
                const out = Math.abs(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                const sym = @json(Auth::user()->currencySymbol());
                return (n < 0 ? '-' : '') + sym + out;
            }

            function fmtQty(q) {
                const n = Number(q || 0);
                return (n % 1 === 0) ? n.toFixed(0) : n.toFixed(2);
            }

            function parseNum(v) {
                if (v === null || v === undefined) return 0;
                if (typeof v === 'number') return v;
                let s = String(v).trim();
                if (!s) return 0;
                let neg = false;
                if (s.startsWith('(') && s.endsWith(')')) {
                    neg = true;
                    s = s.slice(1, -1)
                }
                if (s.endsWith('-')) {
                    neg = true;
                    s = s.slice(0, -1)
                }
                s = s.replace(/[\$\u20AC\u00A3,\s]/g, '');
                const n = parseFloat(s) || 0;
                return neg ? -Math.abs(n) : n;
            }

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

            function formatAmount(raw, isMoney = true) {
                const o = window.reportOptions;
                let val = parseNum(raw);
                if (o.divideBy1000) val /= 1000;
                if (o.hideZeroAmounts && Math.abs(val) < 1e-12) return {
                    html: '',
                    classes: 'zero-amount'
                };
                const frac = o.roundWholeNumbers ? 0 : 2,
                    absTxt = Math.abs(val).toLocaleString('en-US', {
                        minimumFractionDigits: frac,
                        maximumFractionDigits: frac
                    });
                const neg = val < 0;
                let core = absTxt;
                if (neg) {
                    if (o.negativeFormat === '(100)') core = `(${absTxt})`;
                    else if (o.negativeFormat === '100-') core = `${absTxt}-`;
                    else core = `-${absTxt}`;
                }
                let html = isMoney ? `$ ${core}` :
                core; // simple symbol for UI; server currency also shown via money()
                return {
                    html,
                    classes: (neg && o.showInRed) ? 'negative-amount' : ''
                };
            }

            /* ===== DataTable ===== */
            const table = $('#ledger-table').DataTable({
                processing: true,
                serverSide: true,
                colReorder: true,
                scrollX: true,
                scrollY: '420px',
                scrollCollapse: true,
                fixedHeader: true,
                responsive: false,
                ajax: {
                    url: "{{ route('report.taxableSalesDetail') }}",
                    data: function(d) {
                        d.start_date = $('#start-date').val();
                        d.end_date = $('#end-date').val();
                        d.accounting_method = $('#accounting-method').val();
                        d.report_period = $('#report-period').val();
                    },
                    dataSrc: function(json) {
                        return json.data;
                    }
                },
                columns: [{
                        data: 'transaction_date',
                        name: 'transaction_date'
                    },
                    {
                        data: 'transaction_type',
                        name: 'transaction_type'
                    },
                    {
                        data: 'num',
                        name: 'num'
                    },
                    {
                        data: 'customer_name',
                        name: 'customer_name'
                    },
                    {
                        data: 'memo',
                        name: 'memo'
                    },
                    {
                        data: 'quantity',
                        name: 'quantity',
                        className: 'text-right',
                        render: (d, t) => t === 'display' ? formatAmount(fmtQty(d), false).html : d
                    },
                    {
                        data: 'sales_price',
                        name: 'sales_price',
                        className: 'text-right',
                        render: (d, t) => t === 'display' ? money(d) : d
                    },
                    {
                        data: 'amount',
                        name: 'amount',
                        className: 'text-right',
                        render: (d, t) => t === 'display' ? money(d) : d
                    },
                    {
                        data: 'balance',
                        name: 'balance',
                        className: 'text-right',
                        render: (d, t) => t === 'display' ? money(d) : d
                    },
                ],
                dom: 't',
                paging: false,
                searching: false,
                info: false,
                ordering: false
            });

            $('#ledger-table').on('xhr.dt', function() {
                markNow();
                $('#btn-refresh i').removeClass('fa-spin');
            });

            /* ===== Filters & period ===== */
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
            $('#report-period').on('change', function() {
                const p = $(this).val();
                const t = new Date();
                let s = '',
                    e = '';
                const d = (y, m, d) => new Date(y, m, d);
                switch (p) {
                    case 'today':
                        s = e = t.toISOString().split('T')[0];
                        break;
                    case 'this_week': {
                        const a = new Date(t);
                        a.setDate(t.getDate() - t.getDay());
                        const b = new Date(a);
                        b.setDate(a.getDate() + 6);
                        s = a.toISOString().split('T')[0];
                        e = b.toISOString().split('T')[0];
                    }
                    break;
                    case 'this_month':
                        s = d(t.getFullYear(), t.getMonth(), 1).toISOString().split('T')[0];
                        e = d(t.getFullYear(), t.getMonth() + 1, 0).toISOString().split('T')[0];
                        break;
                    case 'this_quarter': {
                        const q = Math.floor(t.getMonth() / 3);
                        s = d(t.getFullYear(), q * 3, 1).toISOString().split('T')[0];
                        e = d(t.getFullYear(), q * 3 + 3, 0).toISOString().split('T')[0];
                    }
                    break;
                    case 'this_year':
                        s = d(t.getFullYear(), 0, 1).toISOString().split('T')[0];
                        e = d(t.getFullYear(), 11, 31).toISOString().split('T')[0];
                        break;
                    case 'last_week': {
                        const a = new Date(t);
                        a.setDate(t.getDate() - t.getDay() - 7);
                        const b = new Date(a);
                        b.setDate(a.getDate() + 6);
                        s = a.toISOString().split('T')[0];
                        e = b.toISOString().split('T')[0];
                    }
                    break;
                    case 'last_month':
                        s = d(t.getFullYear(), t.getMonth() - 1, 1).toISOString().split('T')[0];
                        e = d(t.getFullYear(), t.getMonth(), 0).toISOString().split('T')[0];
                        break;
                    case 'last_quarter': {
                        let q = Math.floor(t.getMonth() / 3) - 1,
                            y = t.getFullYear();
                        if (q < 0) {
                            q = 3;
                            y--;
                        }
                        s = d(y, q * 3, 1).toISOString().split('T')[0];
                        e = d(y, q * 3 + 3, 0).toISOString().split('T')[0];
                    }
                    break;
                    case 'last_year':
                        s = d(t.getFullYear() - 1, 0, 1).toISOString().split('T')[0];
                        e = d(t.getFullYear() - 1, 11, 31).toISOString().split('T')[0];
                        break;
                    case 'last_7_days': {
                        const x = new Date(t);
                        x.setDate(t.getDate() - 6);
                        s = x.toISOString().split('T')[0];
                        e = t.toISOString().split('T')[0];
                    }
                    break;
                    case 'last_30_days': {
                        const x = new Date(t);
                        x.setDate(t.getDate() - 29);
                        s = x.toISOString().split('T')[0];
                        e = t.toISOString().split('T')[0];
                    }
                    break;
                    case 'last_90_days': {
                        const x = new Date(t);
                        x.setDate(t.getDate() - 89);
                        s = x.toISOString().split('T')[0];
                        e = t.toISOString().split('T')[0];
                    }
                    break;
                    case 'last_12_months': {
                        const x = d(t.getFullYear(), t.getMonth() - 11, 1);
                        s = x.toISOString().split('T')[0];
                        e = d(t.getFullYear(), t.getMonth() + 1, 0).toISOString().split('T')[0];
                    }
                    break;
                    case 'all_dates':
                        s = '2000-01-01';
                        e = t.toISOString().split('T')[0];
                        break;
                    default:
                        return;
                }
                $('#start-date').val(s);
                $('#end-date').val(e);
                updateHeaderDate();
                table.ajax.reload(null, false);
                markNow();
            });
            $('#start-date,#end-date,#accounting-method').on('change', function() {
                updateHeaderDate();
                table.ajax.reload(null, false);
                markNow();
            });

            /* ===== Drawers open/close ===== */
            $('#filter-btn').on('click', () => $('#filter-overlay').addClass('drawer-open'));
            $('#general-options-btn').on('click', () => $('#general-options-overlay').addClass('drawer-open'));
            $('#view-options-btn').on('click', () => $('#view-options-overlay').addClass('drawer-open'));
            $('#columns-btn').on('click', () => {
                syncListToCurrentOrder();
                $('#columns-overlay').addClass('drawer-open');
            });
            $('#close-filter').on('click', () => $('#filter-overlay').removeClass('drawer-open'));
            $('#close-general-options,#cancel-general-options').on('click', () => $('#general-options-overlay')
                .removeClass('drawer-open'));
            $('#close-view-options').on('click', () => $('#view-options-overlay').removeClass('drawer-open'));
            $('#close-columns').on('click', () => $('#columns-overlay').removeClass('drawer-open'));
            $('.modal-overlay').on('click', function(e) {
                if (e.target === this) $(this).removeClass('drawer-open');
            });
            $(document).on('keydown', e => {
                if (e.key === 'Escape') $('.modal-overlay').removeClass('drawer-open');
            });

            /* ===== Header actions ===== */
            $('#btn-refresh').on('click', function() {
                $(this).find('i').addClass('fa-spin');
                table.ajax.reload(null, false);
            });
            // $('#btn-print').on('click', () => window.print());
            // $('#btn-export').on('click', () => alert('Export action triggered'));
            $('#btn-more').on('click', () => alert('More options...'));
            $('#btn-save').on('click', function() {
                const name = prompt('Enter report name:', 'Taxable Sales Detail - ' + new Date()
                    .toISOString().slice(0, 10));
                if (name) alert('Report "' + name + '" would be saved with current settings.');
            });

            /* ===== General options wiring ===== */
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
                    '<div class="report-footer" style="padding:20px;border-top:1px solid #e6e6e6;text-align:center;font-size:12px;color:#6b7280"></div>'
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
                table.rows().invalidate().draw(false);
            }
            $('#apply-general-options').on('click', function() {
                applyGeneralOptions();
                $('#general-options-overlay').removeClass('drawer-open');
            });
            $('.general-options-modal input, .general-options-modal select').on('change', applyGeneralOptions);
            $(document).on('click', '.section-title', function() {
                $(this).next('.option-group').slideToggle(120);
                $(this).find('.fa-chevron-up, .fa-chevron-down').toggleClass(
                    'fa-chevron-up fa-chevron-down');
            });

            /* ===== View options (visual only) ===== */
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
                    '.product-service-table th,.product-service-table td{width:11.11%;}';
                const fs = $('#font-size').val();
                css +=
                    `.product-service-table, .product-service-table th, .product-service-table td{font-size:${fs};}`;
                css += '</style>';
                $('head').append(css);
            }
            $('#view-options-overlay input, #view-options-overlay select').on('change', applyViewOptions);

            /* ===== Columns drawer: show/hide & drag reorder ===== */
            function syncListToCurrentOrder() {
                if (!table.colReorder || typeof table.colReorder.order !== 'function') return;
                const order = table.colReorder.order();
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
            if (document.getElementById('qb-columns-list')) {
                new Sortable(document.getElementById('qb-columns-list'), {
                    animation: 150,
                    handle: '.qb-handle',
                    chosenClass: 'qb-chosen',
                    ghostClass: 'qb-ghost',
                    onEnd: function() {
                        const newOrder = $('#qb-columns-list .qb-col-item').map(function() {
                            return parseInt($(this).attr('data-column'), 10)
                        }).get();
                        if (table && table.colReorder && typeof table.colReorder.order === 'function') {
                            try {
                                table.colReorder.order(newOrder, true);
                                localStorage.setItem('taxable-sales-detail-column-order', JSON
                                    .stringify(newOrder));
                                table.columns.adjust().draw(false);
                            } catch (e) {}
                        }
                    }
                });
            }
            $('#qb-columns-list').on('change', 'input[type="checkbox"][data-col]', function() {
                const origIndex = parseInt($(this).data('col'), 10);
                let curIndex = origIndex;
                if (table.colReorder && typeof table.colReorder.transpose === 'function') {
                    curIndex = table.colReorder.transpose(origIndex, 'toCurrent');
                }
                const visible = $(this).is(':checked');
                try {
                    table.column(curIndex).visible(visible, false);
                    table.columns.adjust().draw(false);
                } catch (e) {}
            });

            /* ===== Kick initial visuals ===== */
            setTimeout(function() {
                applyGeneralOptions();
                applyViewOptions();
            }, 100);
        });
    </script>
@endpush
