@extends('layouts.admin')
@section('content')
    <style>
        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 14px;
        }

        .dash-content {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        .main-container {
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .header-section {
            padding: 16px 24px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left h4 {
            margin: 0;
            font-size: 16px;
            font-weight: 500;
            color: #333;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .last-updated {
            color: #666;
            font-size: 13px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            border: none;
            background: none;
            color: #666;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-icon:hover {
            background-color: #f1f3f4;
            color: #333;
        }

        .btn-save {
            background-color: #1a73e8;
            color: white;
            border: none;
            padding: 6px 16px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
        }

        .btn-save:hover {
            background-color: #1557b0;
        }

        .filter-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 24px;
            border-bottom: 1px solid #e9ecef;
            background-color: #fafbfc;
        }

        .filter-row {
            display: flex;
            align-items: end;
            gap: 16px;
            margin-bottom: 12px;
        }

        .filter-group {
            display: flex;
            align-items: end;
            gap: 12px;
        }

        .filter-item {
            display: flex;
            flex-direction: column;
        }

        .filter-label {
            font-size: 12px;
            color: #5f6368;
            margin-bottom: 4px;
            font-weight: 500;
        }

        .form-control,
        .form-select {
            height: 32px;
            font-size: 13px;
            border: 1px solid #dadce0;
            border-radius: 4px;
            padding: 0 8px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #1a73e8;
            box-shadow: 0 0 0 1px #1a73e8;
        }

        .options-row {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .view-options,
        .columns-btn,
        .filter-btn,
        .general-options {
            display: flex;
            align-items: center;
            gap: 6px;
            /* padding: 6px 12px; */
            /* border: 1px solid #dadce0; */
            /* background: white; */
            border-radius: 4px;
            font-size: 13px;
            color: #3c4043;
            text-decoration: none;
        }

        .view-options:hover,
        .columns-btn:hover,
        .filter-btn:hover,
        .general-options:hover {
            background-color: #f8f9fa;
            color: #3c4043;
            text-decoration: none;
        }

        .filter-count {
            background-color: #1a73e8;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 4px;
        }

        .report-content {
            padding: 24px;
        }

        .report-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .report-title {
            font-size: 24px;
            font-weight: 600;
            color: #202124;
            margin-bottom: 8px;
        }

        .company-name {
            font-size: 16px;
            color: #5f6368;
            margin-bottom: 4px;
        }

        .date-range {
            font-size: 14px;
            color: #5f6368;
        }

        .table-container {
            margin-top: 24px;
            overflow-x: auto;
            width: 100%;
        }

        .ledger-table {
            width: 100%;
            font-size: 13px;
            white-space: nowrap;
            /* keep cells in a single line for horizontal scroll */
        }

        .dataTables_wrapper .dataTables_scroll {
            width: 100% !important;
        }

        .dataTables_wrapper .dataTables_scrollHead {
            overflow: hidden !important;
        }

        .dataTables_wrapper .dataTables_scrollBody {
            overflow-y: auto !important;
            overflow-x: auto !important;
        }

        .ledger-table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #5f6368;
            padding: 12px 8px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .ledger-table tbody td {
            padding: 8px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }

        .ledger-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .account-parent {
            font-weight: 600;
            color: #202124;
        }

        .account-child {
            padding-left: 20px;
            color: #5f6368;
        }

        .balance-positive {
            color: #137333;
        }

        .balance-negative {
            color: #d93025;
        }

        .text-right {
            text-align: right;
        }

        .expandable-row {
            cursor: pointer;
        }

        .expand-icon {
            margin-right: 8px;
            transition: transform 0.2s;
        }

        .expand-icon.expanded {
            transform: rotate(90deg);
        }

        /* Side drawer modals (fixed to the right like QuickBooks) */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.35);
            z-index: 1060;
        }

        .general-options-modal,
        .columns-modal {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            width: 360px;
            max-width: 90vw;
            background: #fff;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.15);
            overflow-y: auto;
            z-index: 1070;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid #e6e6e6;
            background: #f9fafb;
        }

        .modal-content {
            padding: 16px 20px 24px;
        }

        @media (max-width: 768px) {
            .filter-row {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group {
                flex-direction: column;
            }

            .options-row {
                flex-wrap: wrap;
            }
        }
    </style>
    <div class="main-container">
        <!-- Header Section -->
        <div class="header-section">
            <div class="header-left">
                <h4>General Ledger List</h4>
            </div>
            <div class="header-right">
                <span class="last-updated">Last updated 7 minutes ago</span>
                <div class="header-actions">
                    <button class="btn-icon" title="Refresh" onclick="refreshData()">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button class="btn-icon" title="Print" onclick="printReport()">
                        <i class="fas fa-print"></i>
                    </button>
                    <button class="btn-icon" title="Export" onclick="exportReport()">
                        <i class="fas fa-external-link-alt"></i>
                    </button>
                    <button class="btn-icon" title="More options">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <button class="btn-save">Save As</button>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-row">
                <div class="filter-group">
                    <div class="filter-item">
                        <label class="filter-label">Report period</label>
                        <select class="form-select" id="report-period" style="width: 160px;">
                            <option value="all_dates">All Dates</option>
                            <option value="today">Today</option>
                            <option value="this_week">This week</option>
                            <option value="this_week_to_date">This week to date</option>
                            <option value="this_fiscal_week">This fiscal week</option>
                            <option value="this_month">This month</option>
                            <option value="this_month_to_date">This month to date</option>
                            <option value="this_quarter">This quarter</option>
                            <option value="this_quarter_to_date">This quarter to date</option>
                            <option value="this_fiscal_quarter">This fiscal quarter</option>
                            <option value="this_fiscal_quarter_to_date">This fiscal quarter to date</option>
                            <option value="this_year">This year</option>
                            <option value="this_year_to_date">This year to date</option>
                            <option value="this_year_to_last_month">This year to last month</option>
                            <option value="this_fiscal_year">This fiscal year</option>
                            <option value="this_fiscal_year_to_date">This fiscal year to date</option>
                            <option value="this_fiscal_year_to_last_month">This fiscal year to last month</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="recent">Recent</option>
                            <option value="last_week">Last week</option>
                            <option value="last_week_to_date">Last week to date</option>
                            <option value="last_week_to_today">Last week to today</option>
                            <option value="last_month">Last month</option>
                            <option value="last_month_to_date">Last month to date</option>
                            <option value="last_month_to_today">Last month to today</option>
                            <option value="last_quarter">Last quarter</option>
                            <option value="last_quarter_to_date">Last quarter to date</option>
                            <option value="last_quarter_to_today">Last quarter to today</option>
                            <option value="last_fiscal_quarter">Last fiscal quarter</option>
                            <option value="last_fiscal_quarter_to_date">Last fiscal quarter to date</option>
                            <option value="last_year">Last year</option>
                            <option value="last_year_to_date">Last year to date</option>
                            <option value="last_year_to_today">Last year to today</option>
                            <option value="last_fiscal_year">Last fiscal year</option>
                            <option value="last_fiscal_year_to_date">Last fiscal year to date</option>
                            <option value="last_7_days">Last 7 days</option>
                            <option value="last_30_days">Last 30 days</option>
                            <option value="last_90_days">Last 90 days</option>
                            <option value="last_12_months">Last 12 months</option>
                            <option value="since_30_days">Since 30 days ago</option>
                            <option value="since_60_days">Since 60 days ago</option>
                            <option value="since_90_days">Since 90 days ago</option>
                            <option value="since_365_days">Since 365 days ago</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                    

                    <div class="filter-item">
                        <label class="filter-label">From</label>
                        <input type="date" class="form-control" id="start-date" value="{{ $filter['startDateRange'] }}"
                            style="width: 140px;">
                    </div>

                    <div class="filter-item">
                        <label class="filter-label">To</label>
                        <input type="date" class="form-control" id="end-date" value="{{ $filter['endDateRange'] }}"
                            style="width: 140px;">
                    </div>

                    <div class="filter-item">
                        <label class="filter-label">Accounting method</label>
                        <select class="form-select" id="accounting-method" style="width: 120px;">
                            <option value="accrual" selected {{ $filter['accountingMethod'] == 'accrual' ? 'selected' : '' }}>Accrual
                            </option>
                            <option value="cash" {{ $filter['accountingMethod'] == 'cash' ? 'selected' : '' }}>Cash
                            </option>
                        </select>
                    </div>
                    <div class="filter-item">
                    <a href="#" class="view-options" id="view-options-btn" style="width: 120px;">
                        <label class="filter-label"></label>
                        <i class="fas fa-eye"></i>
                        View options
                    </a>
                    </div>
                </div>
            </div>

            <div class="options-row mt-1">
                <a href="#" class="columns-btn" id="columns-btn">
                    <i class="fas fa-columns"></i>
                    Columns
                </a>
                <a href="#" class="filter-btn" id="filter-btn">
                    <i class="fas fa-filter"></i>
                    Filter
                    {{-- <span class="filter-count">0</span> --}}
                </a>
                <a href="#" class="general-options" id="general-options-btn">
                    <i class="bi bi-sliders2-vertical"></i>
                    General options
                </a>
            </div>
        </div>

        <!-- Report Content -->
        <div class="report-content">
            <div class="report-header">
                <h1 class="report-title">General Ledger List</h1>
                <p class="company-name">{{ $user->name ?? "Craig's Design and Landscaping Services" }}</p>
                <p class="date-range">
                    <span id="display-date-range">
                        {{ \Carbon\Carbon::parse($filter['startDateRange'])->format('F j, Y') }} -
                        {{ \Carbon\Carbon::parse($filter['endDateRange'])->format('F j, Y') }}
                    </span>
                </p>
            </div>

            <div class="table-container">
                <table class="table ledger-table" id="ledger-table">
                    <thead>
                        <tr>
                            <th>Distribution Account</th>
                            <th>Transaction Date</th>
                            <th>Memo/Description</th>
                            <th>Name</th>
                            <th>Transaction ID</th>
                            <th>Num</th>
                            <th class="text-right">Balance</th>
                            <th class="text-right">Debit</th>
                            <th class="text-right">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Dynamic content will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- General Options Modal (minimal structure) -->
    <div class="modal-overlay" id="general-options-overlay" style="display:none;">
        <div class="general-options-modal">
            <div class="modal-header">
                <h5>General options</h5>
                <button type="button" class="btn-close" id="close-general-options">&times;</button>
            </div>
            <div class="modal-content">
                <div class="option-section">
                    <h6 class="section-title">Number format</h6>
                    <div class="option-group">
                        <label class="checkbox-label"><input type="checkbox" id="divide-by-1000"> Divide by 1000</label>
                        <label class="checkbox-label"><input type="checkbox" id="hide-zero-amounts"> Don't show zero
                            amounts</label>
                        <label class="checkbox-label"><input type="checkbox" id="round-whole-numbers"> Round to the
                            nearest whole number</label>
                        <div class="negative-format-group">
                            <select id="negative-format" class="form-control" style="width: 100px;">
                                <option value="-100" selected>-100</option>
                                <option value="(100)">(100)</option>
                                <option value="100-">100-</option>
                            </select>
                            <label class="checkbox-label"><input type="checkbox" id="show-in-red"> Show in red</label>
                        </div>
                    </div>
                </div>
                <div class="option-section">
                    <h6 class="section-title">Header</h6>
                    <div class="option-group">
                        <label class="checkbox-label"><input type="checkbox" id="company-logo"> Company logo</label>
                        <label class="checkbox-label"><input type="checkbox" id="report-period-checkbox" checked> Report
                            period</label>
                        <label class="checkbox-label"><input type="checkbox" id="company-name-checkbox" checked> Company
                            name</label>
                        <label class="alignment-label">Header alignment</label>
                        <select id="header-alignment" class="form-control" style="max-width:140px;">
                            <option value="center" selected>Center</option>
                            <option value="left">Left</option>
                            <option value="right">Right</option>
                        </select>
                    </div>
                </div>
                <div class="option-section">
                    <h6 class="section-title">Footer</h6>
                    <div class="option-group">
                        <label class="checkbox-label"><input type="checkbox" id="date-prepared" checked> Date
                            prepared</label>
                        <label class="checkbox-label"><input type="checkbox" id="time-prepared" checked> Time
                            prepared</label>
                        <label class="checkbox-label"><input type="checkbox" id="report-basis" checked> Report
                            basis</label>
                        <label class="alignment-label">Footer alignment</label>
                        <select id="footer-alignment" class="form-control" style="max-width:140px;">
                            <option value="center" selected>Center</option>
                            <option value="left">Left</option>
                            <option value="right">Right</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Columns Modal (minimal structure) -->
    <div class="modal-overlay" id="columns-overlay" style="display:none;">
        <div class="columns-modal">
            <div class="modal-header">
                <h5>Columns</h5>
                <button type="button" class="btn-close" id="close-columns">&times;</button>
            </div>
            <div class="modal-content">
                <div class="columns-list">
                    <div class="column-item" data-column="0"><label class="checkbox-label"><input type="checkbox"
                                checked> Distribution account</label></div>
                    <div class="column-item" data-column="1"><label class="checkbox-label"><input type="checkbox"
                                checked> Transaction date</label></div>
                    <div class="column-item" data-column="2"><label class="checkbox-label"><input type="checkbox"
                                checked> Memo/Description</label></div>
                    <div class="column-item" data-column="3"><label class="checkbox-label"><input type="checkbox"
                                checked> Name</label></div>
                    <div class="column-item" data-column="4"><label class="checkbox-label"><input type="checkbox"
                                checked> Transaction ID</label></div>
                    <div class="column-item" data-column="5"><label class="checkbox-label"><input type="checkbox"
                                checked> Num</label></div>
                    <div class="column-item" data-column="6"><label class="checkbox-label"><input type="checkbox"
                                checked> Balance</label></div>
                    <div class="column-item" data-column="7"><label class="checkbox-label"><input type="checkbox"
                                checked> Debit</label></div>
                    <div class="column-item" data-column="8"><label class="checkbox-label"><input type="checkbox"
                                checked> Credit</label></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/colreorder/1.7.0/js/dataTables.colReorder.min.js"></script>
    <script src="https://cdn.datatables.net/fixedheader/3.4.0/js/dataTables.fixedHeader.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/colreorder/1.7.0/css/colReorder.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.4.0/css/fixedHeader.dataTables.min.css">

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            let table = $('#ledger-table').DataTable({
                processing: true,
                serverSide: true,
                colReorder: true,
                scrollX: true,
                responsive: false,
                scrollY: '420px',
                scrollCollapse: true,
                fixedHeader: true,
                ajax: {
                    url: "{{ route('report.general.ledger') }}", // server-side source
                    data: function(d) {
                        d.start_date = $('#start-date').val();
                        d.end_date = $('#end-date').val();
                        d.account = $('#filter-account').length ? $('#filter-account').val() : '';
                        d.accounting_method = $('#accounting-method').val();
                        d.report_period = $('#report-period').val();
                        d.reportOptions = window.reportOptions || {};
                    }
                },
                columns: [{
                        data: 'distribution_account',
                        name: 'distribution_account'
                    },
                    {
                        data: 'transaction_date',
                        name: 'transaction_date'
                    },
                    {
                        data: 'memo_description',
                        name: 'memo_description'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'transaction_id',
                        name: 'transaction_id'
                    },
                    {
                        data: 'num',
                        name: 'num'
                    },
                    {
                        data: 'balance',
                        name: 'balance',
                        className: 'text-right'
                    },
                    {
                        data: 'debit',
                        name: 'debit',
                        className: 'text-right'
                    },
                    {
                        data: 'credit',
                        name: 'credit',
                        className: 'text-right'
                    }
                ],
                dom: 't',
                paging: false,
                searching: false,
                info: false,
                ordering: false
            });

            // Handle report period changes and date updates
            $('#start-date').on('change', function() {
                updateHeaderDate();
                table.draw();
            });

            $('#end-date').on('change', function() {
                updateHeaderDate();
                table.draw();
            });

            $('#accounting-method').on('change', function() {
                table.draw();
            });

            $('#report-period').on('change', function() {
                let period = $(this).val();
                let startDate, endDate;
                const today = moment();

                switch (period) {
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
                    case 'this_year':
                        startDate = today.clone().startOf('year');
                        endDate = today.clone().endOf('year');
                        break;
                    case 'last_year':
                        startDate = today.clone().subtract(1, 'year').startOf('year');
                        endDate = today.clone().subtract(1, 'year').endOf('year');
                        break;
                    default:
                        return; // Custom or unsupported -> do nothing
                }

                $('#start-date').val(startDate.format('YYYY-MM-DD'));
                $('#end-date').val(endDate.format('YYYY-MM-DD'));
                updateHeaderDate();
                table.draw();
            });

            function updateHeaderDate() {
                const s = new Date($('#start-date').val());
                const e = new Date($('#end-date').val());
                const options = {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                };
                $('#display-date-range').text(
                    s.toLocaleDateString('en-US', options) + ' - ' + e.toLocaleDateString('en-US', options)
                );
            }

            // Optional general options support (applies when modal exists)
            window.reportOptions = window.reportOptions || {
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

            function applyGeneralOptions() {
                if (!$('.general-options-modal').length) return; // only if present
                $('#custom-number-format').remove();
                let css = '<style id="custom-number-format">';
                if (window.reportOptions.showInRed) css += '.negative-amount{color:#dc2626!important}';
                if (window.reportOptions.hideZeroAmounts) css += '.zero-amount{display:none!important}';
                css += '</style>';
                $('head').append(css);

                $('.report-title-section').css('text-align', window.reportOptions.headerAlignment);
                $('.company-name').toggle(!!window.reportOptions.companyName);
                $('.date-range').toggle(!!window.reportOptions.reportPeriod);

                // --- Footer design ---
                if (!$('.report-footer').length) {
                    $('.report-content').append(`
                <div class="report-footer"
                style="padding:12px 20px;
                       border-top:1px solid #e6e6e6;
                       font-size:12px;
                       color:#6b7280;
                       text-align:${window.reportOptions.footerAlignment};">
                </div>
                 `);
                }

                const now = new Date();
                const footerParts = [];

                // Report basis (first line)
                if (window.reportOptions.reportBasis) {
                    footerParts.push($('#accounting-method').val() === 'accrual' ? 'Accrual basis' : 'Cash basis');
                }

                // Date + Time + Timezone (second line)
                if (window.reportOptions.datePrepared || window.reportOptions.timePrepared) {
                    const fullDateTime = now.toLocaleString('en-US', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: false,
                        timeZoneName: 'shortOffset' // includes GMT+05:00
                    });
                    footerParts.push(`|\n${fullDateTime}`);
                }

                $('.report-footer')
                    .css('text-align', window.reportOptions.footerAlignment)
                    .html(footerParts.join(' '));

                table.draw();
            }



            if ($('.general-options-modal').length) {
                $('.general-options-modal input, .general-options-modal select').on('change', function() {
                    window.reportOptions.divideBy1000 = $('#divide-by-1000').prop('checked');
                    window.reportOptions.hideZeroAmounts = $('#hide-zero-amounts').prop('checked');
                    window.reportOptions.roundWholeNumbers = $('#round-whole-numbers').prop('checked');
                    window.reportOptions.negativeFormat = $('#negative-format').val();
                    window.reportOptions.showInRed = $('#show-in-red').prop('checked');
                    window.reportOptions.companyLogo = $('#company-logo').prop('checked');
                    window.reportOptions.reportPeriod = $('#report-period-checkbox').prop('checked');
                    window.reportOptions.companyName = $('#company-name-checkbox').prop('checked');
                    window.reportOptions.headerAlignment = $('#header-alignment').val();
                    window.reportOptions.datePrepared = $('#date-prepared').prop('checked');
                    window.reportOptions.timePrepared = $('#time-prepared').prop('checked');
                    window.reportOptions.reportBasis = $('#report-basis').prop('checked');
                    window.reportOptions.footerAlignment = $('#footer-alignment').val();
                    applyGeneralOptions();
                });

                $('#general-options-btn').on('click', function() {
                    $('#general-options-overlay').show();
                });
                $('#close-general-options, #general-options-overlay').on('click', function(e) {
                    if (e.target.id === 'general-options-overlay' || e.target.id ===
                        'close-general-options') $('#general-options-overlay').hide();
                });
                applyGeneralOptions();
            }

            // Columns modal show/hide
            $('#columns-btn').on('click', function(e) {
                e.preventDefault();
                $('#columns-overlay').show();
            });
            $('#close-columns, #columns-overlay').on('click', function(e) {
                if (e.target.id === 'columns-overlay' || e.target.id === 'close-columns') $(
                    '#columns-overlay').hide();
            });

            // General options modal show/hide
            $('#general-options-btn').on('click', function(e) {
                e.preventDefault();
                $('#general-options-overlay').show();
            });
            $('#close-general-options, #general-options-overlay').on('click', function(e) {
                if (e.target.id === 'general-options-overlay' || e.target.id === 'close-general-options') $(
                    '#general-options-overlay').hide();
            });

            function getLedgerDT(callback) {
                const tryGet = function(attempts) {
                    const dt = $.fn.dataTable.isDataTable('#ledger-table') ? $('#ledger-table').DataTable() :
                        null;
                    if (dt) {
                        callback(dt);
                    } else if (attempts > 0) {
                        setTimeout(function() {
                            tryGet(attempts - 1);
                        }, 100);
                    }
                };
                tryGet(30);
            }

            $('.columns-modal input[type="checkbox"]').on('change', function() {
                const originalIndex = $(this).closest('.column-item').data('column');
                const isVisible = $(this).prop('checked');
                if (originalIndex === undefined) return;
                getLedgerDT(function(dt) {
                    const currentIndex = dt.colReorder && typeof dt.colReorder.transpose === 'function'
                        ? dt.colReorder.transpose(originalIndex, 'toCurrent')
                        : originalIndex;
                    dt.column(currentIndex).visible(isVisible, false);
                    dt.columns.adjust().draw(false);
                });
            });

            // Remove demo seeding of rows to avoid header duplication and mixed content
        });

        function loadSampleData() {
            // Sample data matching the image structure
            const sampleData = [{
                    account: 'Checking',
                    type: 'parent',
                    count: 2,
                    children: [{
                            account: 'Beginning Balance',
                            date: '',
                            memo: '-',
                            name: '-',
                            transactionId: '-',
                            num: '-',
                            balance: '2,101.00'
                        },
                        {
                            account: 'Checking',
                            date: '09/08/2025',
                            memo: '-',
                            name: '-',
                            transactionId: '139',
                            num: '-',
                            balance: '1,201.00'
                        }
                    ]
                },
                {
                    account: 'Mastercard',
                    type: 'parent',
                    count: 4,
                    children: [{
                        account: 'Beginning Balance',
                        date: '',
                        memo: '-',
                        name: '-',
                        transactionId: '-',
                        num: '-',
                        balance: '1,003.73'
                    }]
                }
            ];

            let tbody = $('#ledger-tbody');
            tbody.empty();

            sampleData.forEach(function(item) {
                // Add parent row
                let parentRow = `
                    <tr class="expandable-row account-parent" data-account="${item.account}">
                        <td>
                            <i class="fas fa-caret-right expand-icon"></i>
                            ${item.account} (${item.count})
                        </td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                `;
                tbody.append(parentRow);

                // Add child rows (initially hidden)
                if (item.children) {
                    item.children.forEach(function(child) {
                        let childRow = `
                            <tr class="account-child" data-parent="${item.account}" style="display: none;">
                                <td style="padding-left: 40px;">${child.account}</td>
                                <td>${child.date}</td>
                                <td>${child.memo}</td>
                                <td>${child.name}</td>
                                <td>${child.transactionId}</td>
                                <td>${child.num}</td>
                                <td class="text-right">${child.balance}</td>
                                <td class="text-right">${child.debit}</td>
                                <td class="text-right">${child.credit}</td>
                            </tr>
                        `;
                        tbody.append(childRow);
                    });
                }
            });

            // Handle expand/collapse
            $('.expandable-row').on('click', function() {
                let account = $(this).data('account');
                let icon = $(this).find('.expand-icon');
                let childRows = $(`tr[data-parent="${account}"]`);

                if (icon.hasClass('expanded')) {
                    icon.removeClass('expanded');
                    childRows.hide();
                } else {
                    icon.addClass('expanded');
                    childRows.show();
                }
            });
        }

        function refreshData() {
            $('#ledger-table').DataTable().ajax.reload();
        }

        function printReport() {
            window.print();
        }

        function exportReport() {
            // Implement export functionality
            alert('Export functionality would be implemented here');
        }
    </script>
@endpush
