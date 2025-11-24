@extends('layouts.admin')

@section('content')
    <style>
        .section-row {
            background-color: #f2f2f2 !important;
            font-weight: bold;
        }

        .profit-loss-table {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .profit-loss-table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
        }

        .profit-loss-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .profit-loss-table tbody tr.income-section {
            background-color: #e8f5e8;
        }

        .profit-loss-table tbody tr.expense-section {
            background-color: #ffeaea;
        }

        .text-right {
            text-align: right !important;
        }

        .section-header {
            font-weight: 700;
            font-size: 1.1em;
            color: #495057;
            text-transform: uppercase;
        }

        .section-total-amount {
            font-size: 1em;
            font-style: italic;
        }

        .toggle-section {
            user-select: none;
        }

        .toggle-section[style*="pointer"]:hover {
            color: #007bff;
        }

        .toggle-chevron {
            transition: transform 0.2s ease;
            color: #007bff;
            font-size: 12px;
        }

        .child-row td:first-child {
            padding-left: 30px !important;
        }

        .amount-cell {
            text-align: right;
            display: block;
        }

        .subtotal-row .total-amount {
            border-top: 1px solid #000;
            font-weight: bold;
        }

        .total-row .total-amount {
            border-top: 2px solid #000;
            border-bottom: 2px double #000;
            font-weight: bold;
        }

        .section-row {
            background-color: #f8f9fa !important;
            font-weight: bold;
        }

        .section-row:hover {
            background-color: #e9ecef !important;
        }

        .subtotal-row {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .total-row {
            background-color: #e9ecef;
            font-weight: bold;
            border-top: 2px solid #dee2e6;
        }

        .subtotal-label,
        .total-label {
            font-weight: bold;
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
            position: relative;
        }

        .btn-view-options:hover {
            background: #f9fafb;
            border-color: #9ca3af;
        }

        /* View Options Dropdown */
        .view-options-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: none;
            min-width: 200px;
        }

        .view-option-item {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
            font-size: 13px;
        }

        .view-option-item:last-child {
            border-bottom: none;
        }

        .view-option-item:hover {
            background: #f9fafb;
        }

        .view-option-item.divider {
            border-top: 1px solid #e5e7eb;
            margin-top: 4px;
            padding-top: 8px;
        }

        .view-option-item .checkmark {
            margin-right: 8px;
            color: #10b981;
            width: 16px;
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
        .filter-modal {
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

        .view-option-item .checkmark {
            visibility: hidden;
            margin-right: 6px;
        }

        .view-option-item.active .checkmark {
            visibility: visible;
        }

        /* Your existing compact styles */
        .compact-view .child-row {
            display: none !important;
        }

        .compact-view .subtotal-row {
            display: none !important;
        }

        .compact-view .section-total-amount {
            display: inline-block !important;
        }

        .compact-view .toggle-chevron {
            transform: rotate(-90deg);
        }

        /* Filter modal specific styles */
        .filter-section {
            margin-bottom: 24px;
        }

        .filter-section-title {
            font-size: 14px;
            font-weight: 600;
            color: #262626;
            margin: 0 0 16px;
        }

        .date-filter-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .date-filter-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .date-filter-label {
            font-size: 12px;
            color: #6b7280;
            font-weight: 500;
        }
    </style>

    <!-- Filter Controls -->
    <div class="filter-controls">
        <div class="filter-row">
            <div class="filter-group row mb-2">
                <div class="filter-item col-md-3">
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

                <div class="filter-item col-md-3">
                    <label class="filter-label">Date Range</label>
                    <input type="text" id="daterange" class="form-control date-input"
                        value="{{ Carbon\Carbon::now()->startOfMonth()->format('m/d/Y') }} - {{ Carbon\Carbon::now()->format('m/d/Y') }}">
                    <input type="hidden" id="filter-start-date"
                        value="{{ Carbon\Carbon::now()->startOfMonth()->format('Y-m-d') }}">
                    <input type="hidden" id="filter-end-date" value="{{ Carbon\Carbon::now()->format('Y-m-d') }}">
                </div>

                <div class="filter-item col-md-3">
                    <label class="filter-label">Accounting method</label>
                    <select id="accounting-method" class="form-control">
                        <option value="accrual" selected>Accrual</option>
                        <option value="cash">Cash</option>
                    </select>
                </div>

                <div class="filter-item col-md-3 pt-4">
                    <div class="view-options" style="position: relative;">
                        <button class="btn btn-view-options" id="view-options-btn">
                            <i class="fa fa-eye"></i> View options
                        </button>
                        <div class="view-options-dropdown" id="view-options-dropdown">
                            <div class="view-option-item" data-value="normal">
                                <span class="checkmark"><i class="fa fa-check"></i></span>
                                Normal view
                            </div>
                            <div class="view-option-item" data-value="compact">
                                <span class="checkmark"><i class="fa fa-check"></i></span>
                                Compact view
                            </div>
                            <div class="view-option-item divider" data-value="expand">
                                <span class="checkmark"><i class="fa fa-check"></i></span>
                                Expand
                            </div>
                            <div class="view-option-item" data-value="collapse">
                                <span class="checkmark"><i class="fa fa-check"></i></span>
                                Collapse
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Action buttons row -->
        <div class="action-buttons-row">
            <button class="btn btn-outline" id="filter-btn">
                <i class="fa fa-filter"></i> Filter
            </button>
            <button class="btn btn-outline" id="general-options-btn">
                <i class="fa fa-cog"></i> General options
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content-wrapper">
        <div class="d-flex flex-column w-tables rounded mt-3 bg-white">
            <div class="report-title-section p-2">
                <h2 class="report-title">Profit & Loss Statement</h2>
                <p class="date-range">
                    <span id="date-range-display">
                        {{ Carbon\Carbon::now()->startOfMonth()->format('F j, Y') }} -
                        {{ Carbon\Carbon::now()->format('F j, Y') }}
                    </span>
                </p>
            </div>
            <div class="table-responsive p-3" id="report-content">
                {!! $dataTable->table(['class' => 'table table-hover border-0 w-100 profit-loss-table']) !!}
            </div>
        </div>
    </div>

    <!-- Filter Modal -->
    <div class="modal-overlay" id="filter-overlay">
        <div class="filter-modal">
            <div class="modal-header">
                <h5>Filter Options <i class="fa fa-filter" title="Configure filters"></i></h5>
                <button type="button" class="btn-close" id="close-filter">&times;</button>
            </div>
            <div class="modal-content">
                <p class="modal-subtitle">Configure date range and other filters for your report.</p>

                <!-- Date Range section -->
                <div class="filter-section">
                    <h6 class="filter-section-title">Date Range</h6>
                    <div class="date-filter-group">
                        <div class="date-filter-item">
                            <label class="date-filter-label">Report Period</label>
                            <select id="modal-filter-period" class="form-control">
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

                        <div class="date-filter-item">
                            <label class="date-filter-label">Custom Date Range</label>
                            <input type="text" id="modal-daterange" class="form-control date-input"
                                value="{{ Carbon\Carbon::now()->startOfMonth()->format('m/d/Y') }} - {{ Carbon\Carbon::now()->format('m/d/Y') }}">
                        </div>

                        <div class="date-filter-item">
                            <label class="date-filter-label">Accounting Method</label>
                            <select id="modal-accounting-method" class="form-control">
                                <option value="accrual" selected>Accrual</option>
                                <option value="cash">Cash</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Apply button -->
                <div class="filter-section">
                    <button class="btn btn-success w-100" id="apply-filters">
                        Apply Filters
                    </button>
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
@endsection

@push('script-page')
    @include('sections.datatable_js')
    <script>
        $(document).ready(function() {
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
            // Wait for all dependencies to load
            if (typeof moment === 'undefined') {
                console.error('Moment.js is not loaded');
                return;
            }

            if (typeof $.fn.daterangepicker === 'undefined') {
                console.error('DateRangePicker plugin is not loaded');
                return;
            }

            // Initialize components
            setupEventListeners();
            initializeViewOptions();
            initializeFilterModal();

            // Set initial date display
            updateDateDisplay();

            // Initialize table state after DataTable loads
            setTimeout(function() {
                initializeTableState();
                updateButtonVisibility();
            }, 1000);
        });

        // Initialize view options dropdown
        function initializeViewOptions() {
            // Set initial checkmarks
            updateViewCheckmarks();
        }

        // Initialize filter modal
        function initializeFilterModal() {
            // Sync modal values with main form
            $('#modal-filter-period').val($('#filter-period').val());
            $('#modal-accounting-method').val($('#accounting-method').val());

            // Initialize modal date range picker
            $('#modal-daterange').daterangepicker({
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
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month')
                        .endOf('month')
                    ],
                    'This Quarter': [moment().startOf('quarter'), moment().endOf('quarter')],
                    'This Year': [moment().startOf('year'), moment().endOf('year')]
                }
            });
        }

        // View Options Dropdown Handlers
        $('#view-options-btn').on('click', function(e) {
            e.stopPropagation();
            $('#view-options-dropdown').toggle();
        });

        // initialize state if not set
        window.viewState = window.viewState || {
            viewType: 'normal', // 'normal' or 'compact'
            expandState: 'expand' // 'expand' or 'collapse'
        };

        // strict linking
        function setViewType(type) {
            if (type === 'compact') {
                window.viewState.viewType = 'compact';
                window.viewState.expandState = 'collapse';
            } else if (type === 'normal') {
                window.viewState.viewType = 'normal';
                window.viewState.expandState = 'expand';
            }
        }

        function setExpandState(state) {
            if (state === 'collapse') {
                window.viewState.expandState = 'collapse';
                window.viewState.viewType = 'compact';
            } else if (state === 'expand') {
                window.viewState.expandState = 'expand';
                window.viewState.viewType = 'normal';
            }
        }

        // click handler
        $('.view-option-item').off('click.viewOptions').on('click.viewOptions', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const value = $(this).data('value');

            if (value === 'compact' || value === 'normal') {
                setViewType(value);
            } else if (value === 'expand' || value === 'collapse') {
                setExpandState(value);
            }

            applyViewState();
            updateViewCheckmarks();
            $('#view-options-dropdown').hide();
        });

        // apply state to table
        function applyViewState() {
            const $reportContent = $('#report-content');
            $reportContent.removeClass('compact-view');

            if (window.viewState.viewType === 'compact') {
                $reportContent.addClass('compact-view');
            }

            if (window.viewState.expandState === 'expand') {
                handleExpandAll();
            } else {
                handleCollapseAll();
            }
        }

        // update checkmarks (only 2 active at a time)
        function updateViewCheckmarks() {
            $('.view-option-item').removeClass('active');

            $('.view-option-item[data-value="' + window.viewState.viewType + '"]').addClass('active');
            $('.view-option-item[data-value="' + window.viewState.expandState + '"]').addClass('active');
        }

        // init on load
        $(function() {
            applyViewState();
            updateViewCheckmarks();
        });

        // Filter Modal Handlers
        $('#filter-btn').on('click', function() {
            // Sync modal values before showing
            $('#modal-filter-period').val($('#filter-period').val());
            $('#modal-accounting-method').val($('#accounting-method').val());
            $('#modal-daterange').val($('#daterange').val());

            $('#filter-overlay').show();
        });

        $('#close-filter, #filter-overlay').on('click', function(e) {
            if (e.target === this) {
                $('#filter-overlay').hide();
            }
        });

        // Apply filters from modal
        $('#apply-filters').on('click', function() {
            // Update main form values
            $('#filter-period').val($('#modal-filter-period').val());
            $('#accounting-method').val($('#modal-accounting-method').val());

            // Get dates from modal picker
            const modalPicker = $('#modal-daterange').data('daterangepicker');
            if (modalPicker) {
                $('#filter-start-date').val(modalPicker.startDate.format('YYYY-MM-DD'));
                $('#filter-end-date').val(modalPicker.endDate.format('YYYY-MM-DD'));

                // Update main picker
                $('#daterange').data('daterangepicker').setStartDate(modalPicker.startDate);
                $('#daterange').data('daterangepicker').setEndDate(modalPicker.endDate);
            }

            updateDateDisplay();
            refreshTable();
            $('#filter-overlay').hide();
        });

        // Modal period change handler
        $('#modal-filter-period').on('change', function() {
            const period = $(this).val();
            if (period !== 'custom_date') {
                updateModalDateRange(period);
            }
        });

        // Update modal date range based on period
        function updateModalDateRange(period) {
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

            // Update modal daterangepicker
            $('#modal-daterange').data('daterangepicker').setStartDate(startDate);
            $('#modal-daterange').data('daterangepicker').setEndDate(endDate);
        }

        // Period dropdown change handler
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
            refreshTable();
        }

        // Update date display
        function updateDateDisplay() {
            const startDate = moment($('#filter-start-date').val());
            const endDate = moment($('#filter-end-date').val());

            const formattedStart = startDate.format('MMMM D, YYYY');
            const formattedEnd = endDate.format('MMMM D, YYYY');

            $('#date-range-display').text(formattedStart + ' - ' + formattedEnd);
        }

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
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf(
                    'month')],
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
            refreshTable();
        });

        /**
         * Setup all event listeners
         */
        function setupEventListeners() {
            // DataTable pre-request event
            $('#profit-loss-table').on('preXhr.dt', handleDataTablePreXhr);

            // DataTable draw event
            $('#profit-loss-table').on('draw.dt', handleDataTableDraw);

            // Dynamic section toggle events
            $(document).on('click', '.toggle-section', handleSectionToggle);

            // Accounting method change
            $('#accounting-method').on('change', function() {
                refreshTable();
            });
        }

        /**
         * Handle DataTable pre-request
         */
        function handleDataTablePreXhr(e, settings, data) {
            // Get dates from hidden fields
            const startDate = $('#filter-start-date').val();
            const endDate = $('#filter-end-date').val();
            const accountingMethod = $('#accounting-method').val();

            // Send the data to server
            data.startDate = startDate;
            data.endDate = endDate;
            data.accountingMethod = accountingMethod;
        }

        /**
         * Handle DataTable draw event
         */
        function handleDataTableDraw() {
            setTimeout(function() {
                initializeTableState();
                updateButtonVisibility();
                applyViewState(); // Reapply view state after table redraw
            }, 100);
        }

        /**
         * Handle expand all
         */
        function handleExpandAll() {
            $('.child-row, .subtotal-row').show();
            $('.toggle-chevron').removeClass('fa-chevron-right').addClass('fa-chevron-down');
            $('.section-total-amount').hide();
        }

        /**
         * Handle collapse all
         */
        function handleCollapseAll() {
            $('.child-row, .subtotal-row').hide();
            $('.toggle-chevron').removeClass('fa-chevron-right').removeClass('fa-chevron-down');
            $('.section-total-amount').show();
        }

        /**
         * Handle section toggle
         */
        function handleSectionToggle(e) {
            e.preventDefault();

            // Don't allow toggle in compact view
            if (window.viewState.viewType === 'compact') {
                return;
            }

            const $this = $(this);
            const group = $this.data('group');
            const $row = $this.closest('tr');
            const $chevron = $this.find('.toggle-chevron');
            const $sectionTotal = $row.find('.section-total-amount[data-group="' + group + '"]');
            const $childRows = $('.group-' + group);

            if ($chevron.length === 0) return;
            if ($chevron.hasClass('fa-chevron-down')) {
                // Collapse section
                $childRows.hide();
                $chevron.removeClass('fa-chevron-down').addClass('fa-chevron-right');
                $sectionTotal.show();
            } else {
                // Expand section
                $childRows.show();
                $chevron.removeClass('fa-chevron-right').addClass('fa-chevron-down');
                $sectionTotal.hide();
            }
        }

        /**
         * Initialize table state
         */
        function initializeTableState() {
            // Show all child and subtotal rows by default
            $('.child-row, .subtotal-row').show();

            // Set all chevrons to expanded state
            $('.toggle-chevron').removeClass('fa-chevron-right').addClass('fa-chevron-down');

            // Hide all section totals since we're showing details
            $('.section-total-display').hide();

            // Update sections based on children
            $('.toggle-section').each(function() {
                const group = $(this).data('group');
                const hasChildren = $('.group-' + group).length > 0;
                const $chevron = $(this).find('.toggle-chevron');

                if (hasChildren && $chevron.length === 0) {
                    // Add chevron if children exist but chevron is missing
                    $(this).prepend('<i class="fas fa-chevron-down toggle-chevron mr-2"></i>');
                    $(this).css('cursor', 'pointer');
                } else if (!hasChildren) {
                    // Remove chevron and change cursor if no children
                    $chevron.remove();
                    $(this).css('cursor', 'default');
                }
            });
        }

        /**
         * Update button visibility based on available toggles
         */
        function updateButtonVisibility() {
            const hasAnyChildren = $('.toggle-chevron').length > 0;
        }

        // General Options Modal handlers
        $('#general-options-btn').on('click', function() {
            $('#general-options-overlay').show();
        });

        $('#close-general-options, #general-options-overlay').on('click', function(e) {
            if (e.target === this) {
                $('#general-options-overlay').hide();
            }
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
            refreshTable();
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

                $('#report-content').append(footerHTML);
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

        // Format numbers in table based on options
        $(document).on('draw.dt', '#profit-loss-table', function() {
            if (window.reportOptions) {
                $('.profit-loss-table tbody tr').each(function() {
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
                                        $cell.text('(' + Math.abs(value).toLocaleString() + ')');
                                        break;
                                    case '100-':
                                        $cell.text(Math.abs(value).toLocaleString() + '-');
                                        break;
                                    default:
                                        $cell.text('-' + Math.abs(value).toLocaleString());
                                }
                            } else if (value > 0) {
                                $cell.text(value.toLocaleString());
                            }
                        }
                    });
                });
            }
        });

        /**
         * Refresh DataTable
         */
        function refreshTable() {
            if (window.LaravelDataTables && window.LaravelDataTables["profit-loss-table"]) {
                window.LaravelDataTables["profit-loss-table"].draw(false);
            }
        }
    </script>
    {!! $dataTable->scripts() !!}
@endpush
