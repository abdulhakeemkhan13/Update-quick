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
                        onclick="exportDataTable('customer-balance-table', '{{ $pageTitle }}', 'print')"><i
                            class="fa fa-print"></i></button>
                    <button class="btn btn-icon" title="Export"><i class="fa fa-external-link-alt"></i></button>
                    {{-- <button class="btn btn-icon" title="More options"><i class="fa fa-ellipsis-v"></i></button> 
                    <button class="btn btn-success btn-save">Save As</button> --}}
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
                            <button onclick="exportDataTable('customer-balance-table', '{{ $pageTitle }}')"
                                class="btn btn-success mx-auto w-75 justify-content-center text-center"
                                data-action="excel">Export to
                                Excel</button>
                        </div>
                        <div class="col-md-6">
                            <button onclick="exportDataTable('customer-balance-table', '{{ $pageTitle }}', 'pdf')"
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

        <!-- Filter Controls -->
        <div class="filter-controls">
            <div class="filter-row">
                <div class="filter-group d-flex">

                    {{-- filter row --}}
                    <div class="col-md-7">
                        <div class="row">
                            <div class="filter-item col-md-2">
                                <label class="filter-label">Report period</label>
                                <select id="header-filter-period" class="form-control filter-period">
                                    <option value="all_dates">All Dates</option>
                                    <option value="custom_date">Custom dates</option>
                                    <option value="today">Today</option>
                                    <option value="this_week">This week</option>
                                    <option value="this_week_to_date">This week to date</option>
                                    <option value="this_month">This month</option>
                                    <option value="this_month_to_date" selected>This month to date</option>
                                    <option value="this_quarter">This quarter</option>
                                    <option value="this_quarter_to_date">This quarter to date</option>
                                    <option value="this_year">This year</option>
                                    <option value="this_year_to_date">This year to date</option>
                                    <option value="this_year_to_last_month">This year to last month</option>
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
                                    <option value="last_year">Last year</option>
                                    <option value="last_year_to_date">Last year to date</option>
                                    <option value="last_year_to_today">Last year to today</option>
                                    <option value="last_7_days">Last 7 days</option>
                                    <option value="last_30_days">Last 30 days</option>
                                    <option value="last_90_days">Last 90 days</option>
                                    <option value="last_12_months">Last 12 months</option>
                                    <option value="since_30_days_ago">Since 30 days ago</option>
                                    <option value="since_60_days_ago">Since 60 days ago</option>
                                    <option value="since_90_days_ago">Since 90 days ago</option>
                                    <option value="since_365_days_ago">Since 365 days ago</option>
                                    <option value="next_week">Next week</option>
                                    <option value="next_4_weeks">Next 4 weeks</option>
                                    <option value="next_month">Next month</option>
                                    <option value="next_quarter">Next quarter</option>
                                    <option value="next_year">Next year</option>
                                </select>

                            </div>
                            <div class="filter-item col-md-2">
                                <label class="filter-label">From</label>
                                {{-- <input type="text" id="daterange" class="form-control "
                                    value="{{ Carbon\Carbon::now()->format('m/d/Y') }}"> --}}
                                <input type="date" class="form-control" name="start_date" id="filter-start-date"
                                    value="{{ Carbon\Carbon::now()->startOfYear()->format('Y-m-d') }}">
                            </div>
                            <div class="filter-item col-md-2">
                                <label class="filter-label">To</label>
                                {{-- <input type="text" id="daterange" class="form-control " value="{{ Carbon\Carbon::now()->format('m/d/Y') }}"> --}}
                                <input type="date" class="form-control " name="end_date" id="filter-end-date"
                                    value="{{ Carbon\Carbon::now()->format('Y-m-d') }}">
                            </div>
                            @if (isset($accounting_method) && $accounting_method)
                                <div class="filter-item col-md-2">
                                    <label class="filter-label">Accounting method</label>
                                    <select id="accounting-method" class="form-control">
                                        <option value="accrual" selected>Accrual</option>
                                        <option value="cash">Cash</option>
                                    </select>
                                </div>
                            @endif
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

                                {{-- Filter Side Bar --}}
                                <div class="offcanvas offcanvas-end" data-bs-scroll="true" tabindex="-1"
                                    id="filterSidebar" aria-labelledby="filterSidebarLabel">
                                    <div class="offcanvas-header "
                                        style="background: #f9fafb; border-bottom: 1px solid #e6e6e6;">
                                        <h5 class="offcanvas-title" id="filterSidebarLabel">
                                            Filters
                                        </h5>
                                        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                                            aria-label="Close">
                                            <i class="fa fa-close"></i>
                                        </button>
                                    </div>
                                    <div class="offcanvas-body">
                                        <div class="filter-item mb-2">
                                            <label class="filter-label">Report period</label>
                                            <select id="sidebar-filter-period" class="form-control filter-period">
                                                <option value="all_dates">All Dates</option>
                                                <option value="custom_date">Custom dates</option>
                                                <option value="today">Today</option>
                                                <option value="this_week">This week</option>
                                                <option value="this_week_to_date">This week to date</option>
                                                <option value="this_month">This month</option>
                                                <option value="this_month_to_date" selected>This month to date</option>
                                                <option value="this_quarter">This quarter</option>
                                                <option value="this_quarter_to_date">This quarter to date</option>
                                                <option value="this_year">This year</option>
                                                <option value="this_year_to_date">This year to date</option>
                                                <option value="this_year_to_last_month">This year to last month</option>
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
                                                <option value="last_year">Last year</option>
                                                <option value="last_year_to_date">Last year to date</option>
                                                <option value="last_year_to_today">Last year to today</option>
                                                <option value="last_7_days">Last 7 days</option>
                                                <option value="last_30_days">Last 30 days</option>
                                                <option value="last_90_days">Last 90 days</option>
                                                <option value="last_12_months">Last 12 months</option>
                                                <option value="since_30_days_ago">Since 30 days ago</option>
                                                <option value="since_60_days_ago">Since 60 days ago</option>
                                                <option value="since_90_days_ago">Since 90 days ago</option>
                                                <option value="since_365_days_ago">Since 365 days ago</option>
                                                <option value="next_week">Next week</option>
                                                <option value="next_4_weeks">Next 4 weeks</option>
                                                <option value="next_month">Next month</option>
                                                <option value="next_quarter">Next quarter</option>
                                                <option value="next_year">Next year</option>
                                            </select>

                                        </div>

                                        <div class="filter-item mb-2">
                                             <label class="filter-label">from</label>
                                            {{-- <input type="text" id="daterange" class="form-control "
                                                value="{{ Carbon\Carbon::now()->format('m/d/Y') }}"> --}}
                                            <input type="date" class="form-control mb-2" name="start_date"
                                                id="sidebar-filter-start-date"
                                                value="{{ Carbon\Carbon::now()->startOfYear()->format('Y-m-d') }}">
                                            <label class="filter-label">To </label>
                                            {{-- <input type="text" id="daterange" class="form-control " value="{{ Carbon\Carbon::now()->format('m/d/Y') }}"> --}}
                                           
                                            <input type="date" class="form-control " name="end_date"
                                                id="sidebar-filter-end-date"
                                                value="{{ Carbon\Carbon::now()->format('Y-m-d') }}">
                                        </div>
                                    </div>
                                </div>


                                {{-- JS to sync filters --}}
                                <script>
                                    $(document).ready(function() {
                                        // Sync Report Period
                                        $('#sidebar-filter-period').on('change', function() {
                                            $('#header-filter-period').val($(this).val());
                                        });
                                        $('#header-filter-period').on('change', function() {
                                            $('#sidebar-filter-period').val($(this).val());
                                        });
                                    });
                                </script>
                                {{-- Filter Side Bar --}}

                                <button class="btn btn-outline" id="general-options-btn">
                                    <i class="fa fa-cog"></i> General options
                                </button>
                            </div>
                        </div>
                    </div>
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

            <button onclick="exportDataTable('customer-balance-table', '{{ $pageTitle }}')" id="ExprotExcel"
                class="d-none">
                <i class="fa fa-file-excel"></i> Excel
            </button>

            <button onclick="exportDataTable('customer-balance-table', '{{ $pageTitle }}', 'pdf')" id="ExprotPDF"
                class="d-none">
                <i class="fa fa-file-excel"></i> Excel
            </button>

            <div class="table-container p-2">
                {!! $dataTable->table(['class' => 'table customer-balance-table', 'id' => 'customer-balance-table']) !!}
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
                            <input type="checkbox" checked> Customer Name
                        </label>
                    </div>
                    <div class="column-item" data-column="1">
                        <i class="fa fa-grip-vertical handle"></i>
                        <label class="checkbox-label">
                            <input type="checkbox" checked> Current
                        </label>
                    </div>
                    <div class="column-item" data-column="2">
                        <i class="fa fa-grip-vertical handle"></i>
                        <label class="checkbox-label">
                            <input type="checkbox" checked> 1-15 DAYS
                        </label>
                    </div>
                    <div class="column-item" data-column="3">
                        <i class="fa fa-grip-vertical handle"></i>
                        <label class="checkbox-label">
                            <input type="checkbox" checked> 16-30 DAYS
                        </label>
                    </div>
                    <div class="column-item" data-column="4">
                        <i class="fa fa-grip-vertical handle"></i>
                        <label class="checkbox-label">
                            <input type="checkbox" checked> 31-45 DAYS
                        </label>
                    </div>
                    <div class="column-item" data-column="5">
                        <i class="fa fa-grip-vertical handle"></i>
                        <label class="checkbox-label">
                            <input type="checkbox" checked> > 45 DAYS
                        </label>
                    </div>
                    <div class="column-item" data-column="6">
                        <i class="fa fa-grip-vertical handle"></i>
                        <label class="checkbox-label">
                            <input type="checkbox" checked> Total
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

    <script>
        function buildColumnsFromTable() {
            const headers = document.querySelectorAll('#customer-balance-table thead th');
            const container = document.querySelector('#sortable-columns');
            const table = window.LaravelDataTables?.['customer-balance-table'];

            // Clear the old list
            container.innerHTML = '';

            headers.forEach((th) => {
                const columnName = th.innerText.trim().toUpperCase();
                const isHidden = th.classList.contains('default-hidden');

                // Skip BUCKET column
                if (columnName === 'BUCKET') {
                    return;
                }

                // Determine DataTables column index safely
                let colIndex = null;
                if (table) {
                    try {
                        colIndex = table.column(th).index();
                    } catch (e) {
                        colIndex = $(th).index(); // fallback
                    }
                } else {
                    colIndex = $(th).index();
                }

                // Build draggable/checkbox item
                const div = document.createElement('div');
                div.classList.add('column-item');
                div.setAttribute('data-column', colIndex);
                div.innerHTML = `
                <i class="fa fa-grip-vertical handle"></i>
                <label class="checkbox-label">
                    <input type="checkbox" ${isHidden ? '' : 'checked'}> ${columnName}
                </label>
            `;

                container.appendChild(div);
            });
        }

        // Build once after DataTable is initialized
        $(document).ready(function() {
            buildColumnsFromTable();

            const hideDefaultColumns = () => {
                if (window.LaravelDataTables && window.LaravelDataTables['customer-balance-table']) {
                    const table = window.LaravelDataTables['customer-balance-table'];

                    $('#customer-balance-table thead th.default-hidden').each(function() {
                        const colIndex = table.column(this).index();
                        table.column(colIndex).visible(false);

                        $(`.column-item[data-column="${colIndex}"] input[type="checkbox"]`).prop(
                            'checked', false);
                    });


                    {{-- buildColumnsFromTable(); --}}
                } else {
                    setTimeout(hideDefaultColumns, 500); // retry until ready
                    console.warn("DataTable instance not ready yet...");
                }
            };

            hideDefaultColumns();
        });

        // Rebuild on every table redraw
        $('#customer-balance-table').on('draw.dt', function() {
            buildColumnsFromTable();
        });

        // Delegated event binding for dynamically created checkboxes
        $(document).on('change', '#sortable-columns input[type="checkbox"]', function() {
            const columnIndex = $(this).closest('.column-item').data('column');
            const isVisible = $(this).prop('checked');

            if (columnIndex !== undefined && window.LaravelDataTables?.['customer-balance-table']) {
                try {
                    window.LaravelDataTables['customer-balance-table'].column(columnIndex).visible(isVisible);
                } catch (error) {
                    console.error('Column visibility change failed:', columnIndex, isVisible, error);
                }
            }

            // Update column count badge if function exists
            if (typeof updateColumnCountBadge === 'function') {
                updateColumnCountBadge();
            }
        });
    </script>


    <script>
        $('#customer-balance-table').on('click', '.toggle-bucket', function() {
            let $row = $(this);
            let bucket = $row.attr('class').match(/bucket-([^\s]+)/)[1]; // "current"
            let $icon = $row.find('.icon');

            // toggle
            $('.bucket-' + bucket).not($row).toggle(); // don’t hide the parent itself

            // swap icon
            $icon.text($icon.text() === '▶' ? '▼' : '▶');
        });
    </script>

    <script>
        $(document).on('click', '.toggle-section', handleSectionToggle);

        window.viewState = {};
        window.viewState.viewType = 'detailed'; // default view

        function handleSectionToggle(e) {
            e.preventDefault();

            if (window.viewState.viewType === 'compact') {
                return;
            }

            const $this = $(this);
            // fallback: use data-group from .toggle-section if row-id missing
            let group = $this.closest('tr').data('row-id');
            if (!group) {
                group = $this.data('group'); // <-- fix for this report
            }

            const $row = $this.closest('tr');
            const $chevron = $this.find('.toggle-chevron');
            // fallback: support both .section-total-amount and .section-total-display
            const $sectionTotal = $row.find(
                '.section-total-amount[data-group="' + group + '"], .section-total-display[data-group="' + group + '"]'
            );
            const $childRows = $('.group-' + group);

            if ($chevron.length === 0) return;

            if ($chevron.text() === "▼") {
                // Collapse
                $childRows.hide();
                $chevron.text("▶");
                $sectionTotal.show();
            } else {
                // Expand
                $childRows.show();
                $chevron.text("▼");
                $sectionTotal.hide();
            }
        }


        function toggleSection($headerRow) {
            if (window.viewState.viewType === 'compact') {
                return; // Don't allow toggle in compact view
            }

            const sectionId = $headerRow.attr('data-row-id');
            if (!sectionId) return;

            if ($headerRow.hasClass('section-expanded')) {
                // Collapse section
                collapseSection(sectionId);
            } else {
                // Expand section
                expandSection(sectionId);
            }
        }

        const expandCollapseInit = function() {
            if (!window.LaravelDataTables || !window.LaravelDataTables['customer-balance-table']) {
                setTimeout(expandCollapseInit, 500);
                return;
            }

            // Attach handler once DataTable is initialized
            window.LaravelDataTables['customer-balance-table']
                .on('draw.dt', function() {
                    console.log("Table redrawn, checking for toggle sections...");
                    console.log($('.toggle-section'));
                    $('.toggle-section').each(function() {
                        {{-- console.log($(this)); --}}
                        $(this).click();
                    })
                    // do your expand/collapse binding here
                    {{-- $('.toggle-section').off('click').on('click', function() {
                        console.log("Clicked section:", $(this).data('section'));
                    }); --}}
                });
        };

        $(document).ready(expandCollapseInit);


        const collapseFunction3 = function(e) {
            e.preventDefault();

            // Find the icon inside the clicked row
            const $row = $(this);
            const $icon = $row.find('.chevron-icon');

            const parentType = $icon.data('parent-type');
            const parentId = $icon.data('parent-id');

            if (!parentType || !parentId) return;

            // Build selector for children rows
            const childSelector = `.child-of-${parentType}-${parentId}`;
            const $children = $(childSelector);

            if ($children.length === 0) return;

            // Toggle collapse/expand
            if ($icon.text().trim() === "▼") {
                // Collapse
                $children.hide();
                $icon.text("▶");
            } else {
                // Expand
                $children.show();
                $icon.text("▼");
            }
        }

        // Attach once for chevron-icon style tables
        $(document).on('click', '.subtype-header-row', collapseFunction3);

        $(document).on('click', '.account-header-row', collapseFunction3);

        $(document).on('click', '.chevron-icon', collapseFunction3)

        $(document).on('click', '.section-header-row', collapseFunction3)
    </script>

        <style>
        :root {
            --qb-primary: #2ca01c;
            --qb-primary-hover: #248f17;
            --qb-muted: #f5f6f8;
            --qb-border: #e6e8eb;
            --qb-text: #333333;
            --qb-accent: #003366;
        }

        body {
            background: var(--qb-muted);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        /* Report Container */
        .report-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .report-header {
            text-align: center;
        }

        /* Filter Card */
        .filter-card {
            background: #fff;
            border-radius: 6px;
            border: 1px solid var(--qb-border);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        /* Form Controls */
        .form-select,
        .form-control {
            border: 1px solid #dde2e7;
            border-radius: 4px;
            padding: 8px 12px;
            height: 38px;
            font-size: 14px;
            transition: all 0.2s;
        }

        .form-select:focus,
        .form-control:focus {
            border-color: var(--qb-primary);
            box-shadow: 0 0 0 2px rgba(44, 160, 28, 0.25);
            outline: none;
        }

        .muted-label {
            display: block;
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 4px;
            font-weight: 500;
        }

        /* Radio buttons styling */
        .form-check-input {
            margin-right: 6px;
        }

        .form-check-label {
            font-size: 14px;
            color: var(--qb-text);
            margin-bottom: 0;
        }

        .form-check-inline {
            margin-right: 15px;
        }

        /* Buttons */
        .btn {
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 4px;
            transition: all 0.2s;
            border: 1px solid transparent;
            cursor: pointer;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-primary {
            background: var(--qb-primary);
            border-color: var(--qb-primary);
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            background: var(--qb-primary-hover);
            border-color: var(--qb-primary-hover);
        }

        .btn-outline-secondary {
            background: white;
            border-color: #dde2e7;
            color: #444;
        }

        .btn-outline-secondary:hover:not(:disabled) {
            background: #f8f9fa;
            border-color: #bbb;
        }

        .btn-success {
            background: var(--qb-primary);
            border-color: var(--qb-primary);
            color: white;
        }

        .btn-success:hover:not(:disabled) {
            background: var(--qb-primary-hover);
            border-color: var(--qb-primary-hover);
        }

        .btn-white {
            background: white;
            border: 1px solid #dde2e7;
            padding: 6px 10px;
            font-size: 14px;
        }

        .btn-white:hover:not(:disabled) {
            background: #f8f9fa;
            border-color: #bbb;
        }

        .btn-short {
            padding: 6px 12px;
            font-size: 14px;
        }

        /* Report Header */
        .report-card {
            background: #fff;
            border-radius: 6px;
            border: 1px solid var(--qb-border);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .report-toolbar {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f1f3;
            display: flex;
            flex-direction: column;
            gap: 8px;
            text-align: center !important;
        }

        .toolbar-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        .report-title {
            text-align: center;
            padding: 12px 8px;
        }

        .report-title h4 {
            margin-bottom: 4px;
            font-weight: 600;
            color: var(--qb-text);
        }

        .report-divider {
            height: 1px;
            background: var(--qb-border);
        }

        /* Table */
        .table-card {
            padding: 0;
            background: #fff;
            border-radius: 0 0 6px 6px;
        }

        .table {
            font-size: 13.5px;
            margin: 0;
            width: 100%;
        }

        .table thead th {
            padding: 10px 12px;
            font-weight: 700;
            position: sticky;
            top: 0;
            background: #fff;
            z-index: 3;
            border-bottom: 1px solid #eceff2;
            color: var(--qb-text);
        }

        .table tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid #f5f6f8;
            vertical-align: middle;
            color: var(--qb-text);
        }

        .table tbody tr:hover {
            background: #fbfcfe;
        }

        /* Toggle Button */
        .toggle-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 3px;
            background: #f1f3f5;
            border: 0;
            margin-right: 8px;
            color: #495057;
            cursor: pointer;
            transition: all 0.2s;
        }

        .toggle-btn:hover {
            background: #e9ecef;
        }

        .toggle-btn .toggle-icon {
            transition: transform 0.2s;
        }

        .toggle-btn.expanded .toggle-icon {
            transform: rotate(90deg);
        }

        /* Account Rows */
        .hidden-row {
            display: none !important;
        }

        .account-detail td:first-child {
            padding-left: 36px !important;
        }

        .col-amount {
            text-align: right;
            white-space: nowrap;
        }

        /* Indentation */
        .indent-spacer {
            display: inline-block;
            width: 20px;
        }

        /* Group Headers */
        .account-header {
            background: #f8f9fa;
            font-weight: 600;
        }

        .account-header:hover {
            background: #f1f3f5 !important;
        }

        .account-header .account-header-text {
            color: #333;
            font-weight: 700;
        }

        /* Subtotals */
        .account-subtotal {
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }

        /* Grand Total */
        .grand-total {
            font-weight: 800;
            background: #f8f9fa;
            border-top: 2px solid var(--qb-border);
        }

        /* Net Income */
        .net-income {
            background: #f0f8ff;
            font-weight: 600;
        }

        /* Amount Formatting */
        .text-success {
            color: #28a745 !important;
        }

        .text-danger {
            color: #dc3545 !important;
        }

        /* Loading State */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        /* Error States */
        .is-invalid {
            border-color: #dc3545;
        }

        .invalid-feedback {
            display: block;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875rem;
            color: #dc3545;
        }

        /* Time Period Columns */
        .month-debit,
        .month-credit,
        .quarter-debit,
        .quarter-credit,
        .year-debit,
        .year-credit {
            font-size: 13px;
            text-align: right;
            min-width: 100px;
        }

        /* Alternating column coloring */
        .month-debit,
        .quarter-debit,
        .year-debit {
            background-color: rgba(240, 248, 255, 0.3);
        }

        .month-credit,
        .quarter-credit,
        .year-credit {
            background-color: rgba(255, 240, 245, 0.3);
        }
         #switch-view-btn:hover , #customize-btn:hover {
        background-color: #206029 !important; /* Bootstrap primary */
        color: #fff !important;
        border-color: #206029 !important;
        }
        /* Responsive */
        @media (max-width: 992px) {
            .toolbar-row {
                flex-direction: column;
                align-items: stretch;
                gap: 8px;
            }

            .report-container {
                padding: 0 10px;
            }

            .table-responsive {
                font-size: 12px;
            }
        }

        @media (max-width: 768px) {
            .form-check-inline {
                display: block;
                margin-right: 0;
                margin-bottom: 5px;
            }
        }
    </style>

    <style>
        /* Base styling */
        * {
            box-sizing: border-box;
        }


        .account-header-row,
        subtype-header-row {
            cursor: pointer;
        }

        .toggle-section {
            cursor: pointer;
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

        .customer-balance-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .customer-balance-table th {
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

        .customer-balance-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f3f4f6;
            color: #262626;
        }

        .customer-balance-table tbody tr:hover {
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
            text-align: right !important;
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

        .parent-row {
            cursor: pointer;
        }

        i {
            font-style: normal;
        }
        .summary-total{
            font-weight: bold;
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
$(document).on('click', '.group-toggle', function() {
    const key = $(this).data('group');
    const icon = $(this).find('i.fas');
    const rows = $('.group-' + key);

    if (rows.is(':visible')) {
        rows.hide();
        icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
    } else {
        rows.show();
        icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
    }
});
</script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        let csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        function exportDataTable(tableId, pageTitle, format = "excel") {
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
                    let cellContent;

                    if ($(this).find('h4').length > 0 || $(this).find('strong').length > 0) {
                        // If <h4> exists, keep HTML (preserve h4)
                        cellContent = $(this).html()
                            .replace(/[\n\r]+/g, ' ')
                            .replace(/\s{2,}/g, ' ')
                            .trim();
                    } else {
                        // Otherwise, use plain text
                        cellContent = $(this).text()
                            .replace(/[\n\r]+/g, ' ')
                            .replace(/\s{2,}/g, ' ')
                            .trim();
                    }

                    rowArray.push(cellContent);
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
                    singleBold: pageTitle === "Balance Sheet - Standard" ? false : true,
                    format: format,
                    _token: '{{ csrf_token() }}'
                },
                xhrFields: {
                    responseType: 'blob'
                },
                success: function(blob, status, xhr) {
                    let filename = xhr.getResponseHeader('Content-Disposition')
                        .split('filename=')[1]
                        .replace(/"/g, '');

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
            $('.filter-period').on('change', function() {
                updateDateRange($(this).val());
            });

            // Update date range based on period selection
            function updateDateRange(period) {
                const today = moment();
                let startDate, endDate;

                switch (period) {
                    case 'all_dates':
                        startDate = null;
                        endDate = null;
                        break;

                    case 'custom_date':
                        // Do nothing, let user pick manually
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

                    case 'this_week_to_date':
                        startDate = today.clone().startOf('week');
                        endDate = today.clone();
                        break;

                    case 'last_week':
                        startDate = today.clone().subtract(1, 'week').startOf('week');
                        endDate = today.clone().subtract(1, 'week').endOf('week');
                        break;

                    case 'last_week_to_date':
                        startDate = today.clone().subtract(1, 'week').startOf('week');
                        endDate = today.clone();
                        break;

                    case 'last_week_to_today':
                        startDate = today.clone().subtract(1, 'week').startOf('week');
                        endDate = today.clone();
                        break;

                    case 'this_month':
                        startDate = today.clone().startOf('month');
                        endDate = today.clone().endOf('month');
                        break;

                    case 'this_month_to_date':
                        startDate = today.clone().startOf('month');
                        endDate = today.clone();
                        break;

                    case 'last_month':
                        startDate = today.clone().subtract(1, 'month').startOf('month');
                        endDate = today.clone().subtract(1, 'month').endOf('month');
                        break;

                    case 'last_month_to_date':
                        startDate = today.clone().subtract(1, 'month').startOf('month');
                        endDate = today.clone();
                        break;

                    case 'last_month_to_today':
                        startDate = today.clone().subtract(1, 'month').startOf('month');
                        endDate = today.clone();
                        break;

                    case 'this_quarter':
                        startDate = today.clone().startOf('quarter');
                        endDate = today.clone().endOf('quarter');
                        break;

                    case 'this_quarter_to_date':
                        startDate = today.clone().startOf('quarter');
                        endDate = today.clone();
                        break;

                    case 'last_quarter':
                        startDate = today.clone().subtract(1, 'quarter').startOf('quarter');
                        endDate = today.clone().subtract(1, 'quarter').endOf('quarter');
                        break;

                    case 'last_quarter_to_date':
                        startDate = today.clone().subtract(1, 'quarter').startOf('quarter');
                        endDate = today.clone();
                        break;

                    case 'last_quarter_to_today':
                        startDate = today.clone().subtract(1, 'quarter').startOf('quarter');
                        endDate = today.clone();
                        break;

                    case 'this_year':
                        startDate = today.clone().startOf('year');
                        endDate = today.clone().endOf('year');
                        break;

                    case 'this_year_to_date':
                        startDate = today.clone().startOf('year');
                        endDate = today.clone();
                        break;

                    case 'this_year_to_last_month':
                        startDate = today.clone().startOf('year');
                        endDate = today.clone().subtract(1, 'month').endOf('month');
                        break;

                    case 'last_year':
                        startDate = today.clone().subtract(1, 'year').startOf('year');
                        endDate = today.clone().subtract(1, 'year').endOf('year');
                        break;

                    case 'last_year_to_date':
                        startDate = today.clone().subtract(1, 'year').startOf('year');
                        endDate = today.clone();
                        break;

                    case 'last_year_to_today':
                        startDate = today.clone().subtract(1, 'year').startOf('year');
                        endDate = today.clone();
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

                    case 'last_12_months':
                        startDate = today.clone().subtract(12, 'months').startOf('month');
                        endDate = today.clone().endOf('month');
                        break;

                    case 'since_30_days_ago':
                        startDate = today.clone().subtract(30, 'days');
                        endDate = today.clone();
                        break;

                    case 'since_60_days_ago':
                        startDate = today.clone().subtract(60, 'days');
                        endDate = today.clone();
                        break;

                    case 'since_90_days_ago':
                        startDate = today.clone().subtract(90, 'days');
                        endDate = today.clone();
                        break;

                    case 'since_365_days_ago':
                        startDate = today.clone().subtract(365, 'days');
                        endDate = today.clone();
                        break;

                    case 'next_week':
                        startDate = today.clone().add(1, 'week').startOf('week');
                        endDate = today.clone().add(1, 'week').endOf('week');
                        break;

                    case 'next_4_weeks':
                        startDate = today.clone().add(1, 'week').startOf('week');
                        endDate = today.clone().add(4, 'week').endOf('week');
                        break;

                    case 'next_month':
                        startDate = today.clone().add(1, 'month').startOf('month');
                        endDate = today.clone().add(1, 'month').endOf('month');
                        break;

                    case 'next_quarter':
                        startDate = today.clone().add(1, 'quarter').startOf('quarter');
                        endDate = today.clone().add(1, 'quarter').endOf('quarter');
                        break;

                    case 'next_year':
                        startDate = today.clone().add(1, 'year').startOf('year');
                        endDate = today.clone().add(1, 'year').endOf('year');
                        break;

                    default:
                        startDate = today.clone().startOf('month');
                        endDate = today.clone();
                }

                // Update the inputs if not custom_date or all_dates
                // if (startDate && endDate) {
                //     $('#filter-start-date').val(startDate.format('YYYY-MM-DD'));
                //     $('#filter-end-date').val(endDate.format('YYYY-MM-DD'));
                // }
                // Update hidden date fields
                $('#filter-start-date').val(startDate.format('YYYY-MM-DD'));
                $('#filter-end-date').val(endDate.format('YYYY-MM-DD'));

                // Update DateRangePicker to reflect the new dates
                // $('#daterange').data('daterangepicker').setStartDate(startDate);
                // $('#daterange').data('daterangepicker').setEndDate(endDate);
                updateDateDisplay();
                refreshData();
            }

            const $last = $('.last-updated');
            let lastUpdatedAt = Date.now();
            let tickerId = null;

            function formatRelative(ts) {
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

            function updateLabel() {
                $last.text(`Last updated ${formatRelative(lastUpdatedAt)}`);
            }

            function markNow() {
                lastUpdatedAt = Date.now();
                updateLabel();
                if (tickerId) clearInterval(tickerId);
                tickerId = setInterval(updateLabel, 30 * 1000);
            }
            markNow();

            // Update date display
            function updateDateDisplay() {
                const startDate = moment($('#filter-start-date').val());
                const endDate = moment($('#filter-end-date').val());

                const formattedStart = startDate.format('MMMM D, YYYY');
                const formattedEnd = endDate.format('MMMM D, YYYY');

                $('#date-range-display').text(' As of  ' + formattedEnd);
            }

            // Refresh data function
            function refreshData() {
                if (window.LaravelDataTables && window.LaravelDataTables["customer-balance-table"]) {
                    window.LaravelDataTables["customer-balance-table"].draw();
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
            $('#filter-end-date').on('change', function() {
                $('#sidebar-filter-end-date').val($(this).val());
                updateDateDisplay();
                refreshData();
            });
            $('#filter-start-date').on('change', function() {
                $('#sidebar-filter-start-date').val($(this).val());
                updateDateDisplay();
                refreshData();
            });

            $('#sidebar-filter-end-date').on('change', function() {
                $('#filter-end-date').val($(this).val());
                updateDateDisplay();
                refreshData();
            });
            $('#sidebar-filter-start-date').on('change', function() {
                $('#filter-start-date').val($(this).val());
                updateDateDisplay();
                refreshData();
            });

            // Handle accounting method changes
            $('#accounting-method').on('change', function() {
                refreshData();
            });

            // Setup DataTable ajax parameters
            $('#customer-balance-table').on('preXhr.dt', function(e, settings, data) {
                data.startDate = moment($('#filter-start-date').val(), 'YYYY-MM-DD').format('YYYY-MM-DD');
                data.endDate = moment($('#filter-end-date').val(), 'YYYY-MM-DD').format('YYYY-MM-DD');
                data.account_id = $('#filter-account').val();
                data.accounting_method = $('#accounting-method').val();
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
                if (window.LaravelDataTables && window.LaravelDataTables["customer-balance-table"]) {
                    // Note: Column reordering requires ColReorder extension
                    console.log('Column order would be applied:', order);
                }
            }

            // Handle column visibility
            $('.columns-modal input[type="checkbox"]').on('change', function() {
                const columnIndex = $(this).closest('.column-item').data('column');
                const isVisible = $(this).prop('checked');

                if (columnIndex !== undefined && window.LaravelDataTables && window.LaravelDataTables[
                        "customer-balance-table"]) {
                    try {
                        window.LaravelDataTables["customer-balance-table"].column(columnIndex).visible(
                            isVisible);
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
                        <title>A/R Aging Summary Report - Print</title>
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
                        <div class="report-title">A/R Aging Summary Report</div>
                        <div class="company-name">${$('.company-name').text()}</div>
                        <div class="date-range">${$('.date-range').text()}</div>
                        <table>
                            ${$('.customer-balance-table').html()}
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
                const reportName = prompt('Enter report name:', 'A/R Aging Summary Report - ' + moment()
                    .format(
                        'YYYY-MM-DD'));
                if (reportName) {
                    // In a real application, this would save to the server
                    alert('Report "' + reportName + '" would be saved with current settings');

                    // Save current settings to localStorage for demo
                    const settings = {
                        name: reportName,
                        startDate: $('#filter-start-date').val(),
                        endDate: $('#filter-end-date').val(),
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


                // Get table ID dynamically (assumes closest table in DOM)
                const tableId = $(this).closest('div').find('table').attr('id');
                const pageTitle = document.title || 'Report';

                switch (option) {
                    case '1':
                        // alert('Excel export would be triggered');
                        $("#ExprotExcel").click();
                        break;
                    case '2':
                        $("#ExprotPDF").click();
                        // alert('PDF export would be triggered');
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
            $(document).on('draw.dt', '#customer-balance-table', function() {
                if (window.reportOptions) {
                    $('.customer-balance-table tbody tr').each(function() {
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
        <!-- Enhanced Trial Balance JavaScript -->
    <script>
        // Fixed Trial Balance JavaScript
        (function($) {
            "use strict";

            let dataTable = null;

            // Initialize toggle controls for hierarchical display
            function initializeToggleControls() {
                // Remove existing event listeners to prevent duplicates
                $(document).off('click.toggle', '.toggle-btn');

                // Add new event listener
                $(document).on('click.toggle', '.toggle-btn', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const $btn = $(this);
                    const targetGroup = $btn.data('target');
                    const $icon = $btn.find('.toggle-icon');
                    const isCollapsed = $btn.hasClass('collapsed');

                    if (isCollapsed) {
                        // Expand: show child rows
                        $btn.removeClass('collapsed').addClass('expanded');
                        $icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
                        $(`.child-of-${targetGroup}`).removeClass('hidden-row').show();

                        // Show header amounts when expanded
                        $btn.closest('tr').find('.header-amount').show();
                    } else {
                        // Collapse: hide child rows
                        $btn.removeClass('expanded').addClass('collapsed');
                        $icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
                        $(`.child-of-${targetGroup}`).addClass('hidden-row').hide();

                        // Hide header amounts when collapsed
                        $btn.closest('tr').find('.header-amount').hide();
                    }
                });

                // Initialize all toggle buttons as collapsed by default
                $('.toggle-btn').addClass('collapsed');
                $('.toggle-btn .toggle-icon').removeClass('fa-chevron-down').addClass('fa-chevron-right');

                // Hide all child rows initially
                $('.child-row').addClass('hidden-row').hide();

                // Hide header amounts initially
                $('.header-amount').hide();
            }

            // Make function globally available
            window.initializeToggleControls = initializeToggleControls;

            // Get unified filter value based on row and column settings
            function getShowFilterValue() {
                const showRows = $('input[name="showRows"]:checked').val();
                const showCols = $('input[name="showColumns"]:checked').val();

                // Map the radio button values to a unified parameter
                if (showRows === 'non-zero' || showCols === 'non-zero') {
                    return 'non-zero';
                } else if (showRows === 'all' && showCols === 'all') {
                    return 'all';
                } else {
                    return 'active';
                }
            }

            // Enhanced refresh table function
            function refreshTable() {
                // Show loading state
                const $runBtn = $('#run-report');
                const originalText = $runBtn.html();
                $runBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i>Loading...');

                try {
                    // Collect all filter values
                    const filters = {
                        reportPeriod: $('#report-period').val(),
                        dateFrom: $('#date-from').val(),
                        dateTo: $('#date-to').val(),
                        accountType: $('#account-filter').val(),
                        accountingMethod: $('input[name="accountingMethod"]:checked').val(),
                        displayColumns: $('#display-columns').val(),
                        showRows: $('input[name="showRows"]:checked').val(),
                        showColumns: $('input[name="showColumns"]:checked').val(),
                        showNonZero: getShowFilterValue(),
                        _token: $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val()
                    };

                    // Validate required filters
                    if (!filters.dateFrom || !filters.dateTo) {
                        alert('Please select valid date range');
                        return;
                    }

                    if (new Date(filters.dateFrom) > new Date(filters.dateTo)) {
                        alert('Start date cannot be after end date');
                        return;
                    }

                    // Update date display
                    const displayDate = moment(filters.dateTo).format('MMMM D, YYYY');
                    $('#report-asof').text(displayDate);

                    // Update last updated timestamp
                    const currentTime = moment().format('h:mm A, MMM D, YYYY');
                    if ($('#last-updated').length) {
                        $('#last-updated').text('Last updated — ' + currentTime);
                    } else {
                        $('.report-title').append('<div class="small text-muted" id="last-updated">Last updated — ' +
                            currentTime + '</div>');
                    }

                    // Get DataTable instance
                    const table = getDataTableInstance();

                    if (table && table.ajax) {
                        // Update DataTable with new parameters
                        table.off('preXhr.dt');
                        table.on('preXhr.dt', function(e, settings, data) {
                            // Add all filters to the ajax request
                            Object.assign(data, filters);
                        });

                        // Redraw table with new data
                        table.ajax.reload(function() {
                            // Reinitialize toggle controls after table refresh
                            setTimeout(() => {
                                initializeToggleControls();
                                applyRowVisibilityFilters();
                            }, 100);
                        }, false);
                    } else {
                        // Fallback: reload page with query parameters
                        const queryString = new URLSearchParams(filters).toString();
                        const newUrl = window.location.pathname + '?' + queryString;
                        window.location.href = newUrl;
                    }

                } catch (error) {
                    console.error('Error refreshing table:', error);
                    alert('Error refreshing report. Please try again.');
                } finally {
                    // Restore button state
                    setTimeout(() => {
                        $runBtn.prop('disabled', false).html(originalText);
                    }, 500);
                }
            }

            // Apply row visibility filters
            function applyRowVisibilityFilters() {
                const showRows = $('input[name="showRows"]:checked').val();

                if (showRows === 'non-zero') {
                    // Hide rows with zero debit and credit
                    $('#trial-balance-table tbody tr').each(function() {
                        const $row = $(this);
                        const debitText = $row.find('td:nth-child(4)').text().replace(/[^\d.-]/g, '');
                        const creditText = $row.find('td:nth-child(5)').text().replace(/[^\d.-]/g, '');
                        const debit = parseFloat(debitText) || 0;
                        const credit = parseFloat(creditText) || 0;

                        if (debit === 0 && credit === 0 && !$row.hasClass('account-header') && !$row.hasClass(
                                'grand-total')) {
                            $row.hide();
                        } else {
                            $row.show();
                        }
                    });
                } else {
                    // Show all rows (except those hidden by toggle)
                    $('#trial-balance-table tbody tr').each(function() {
                        const $row = $(this);
                        if (!$row.hasClass('hidden-row')) {
                            $row.show();
                        }
                    });
                }
            }

            // Get DataTable instance
            function getDataTableInstance() {
                // Try Laravel DataTables global first
                if (window.LaravelDataTables && window.LaravelDataTables["trial-balance-table"]) {
                    return window.LaravelDataTables["trial-balance-table"];
                }

                // Try jQuery DataTables
                if ($.fn.dataTable.isDataTable('#trial-balance-table')) {
                    return $('#trial-balance-table').DataTable();
                }

                return null;
            }

            // Update date inputs based on period selection
            function updateDateInputs() {
                const period = $('#report-period').val();
                let startDate, endDate;

                const now = moment();

                switch (period) {
                    case 'today':
                        startDate = endDate = now.format('YYYY-MM-DD');
                        break;
                    case 'yesterday':
                        startDate = endDate = now.subtract(1, 'day').format('YYYY-MM-DD');
                        break;
                    case 'this-week':
                        startDate = now.startOf('week').format('YYYY-MM-DD');
                        endDate = moment().format('YYYY-MM-DD');
                        break;
                    case 'last-week':
                        startDate = now.subtract(1, 'week').startOf('week').format('YYYY-MM-DD');
                        endDate = now.endOf('week').format('YYYY-MM-DD');
                        break;
                    case 'this-month':
                        startDate = now.startOf('month').format('YYYY-MM-DD');
                        endDate = moment().format('YYYY-MM-DD');
                        break;
                    case 'last-month':
                        startDate = now.subtract(1, 'month').startOf('month').format('YYYY-MM-DD');
                        endDate = now.endOf('month').format('YYYY-MM-DD');
                        break;
                    case 'this-quarter':
                        startDate = now.startOf('quarter').format('YYYY-MM-DD');
                        endDate = moment().format('YYYY-MM-DD');
                        break;
                    case 'last-quarter':
                        startDate = now.subtract(1, 'quarter').startOf('quarter').format('YYYY-MM-DD');
                        endDate = now.endOf('quarter').format('YYYY-MM-DD');
                        break;
                    case 'this-year':
                        startDate = now.startOf('year').format('YYYY-MM-DD');
                        endDate = moment().format('YYYY-MM-DD');
                        break;
                    case 'last-year':
                        startDate = now.subtract(1, 'year').startOf('year').format('YYYY-MM-DD');
                        endDate = now.endOf('year').format('YYYY-MM-DD');
                        break;
                    case 'custom':
                        // Don't change date inputs for custom selection
                        refreshTable();
                        return;
                    default:
                        return;
                }

                // Update the date input fields
                $('#date-from').val(startDate);
                $('#date-to').val(endDate);

                // Trigger table refresh after updating dates
                refreshTable();
            }

            // Export functionality
            function exportToExcel() {
                try {
                    const table = document.getElementById('trial-balance-table');
                    if (!table) {
                        alert('Table not found');
                        return;
                    }

                    // Clone table to avoid modifying original
                    const clonedTable = table.cloneNode(true);

                    // Remove any hidden rows or columns
                    $(clonedTable).find('.hidden-row').remove();
                    $(clonedTable).find('th, td').filter(':hidden').remove();

                    // Clean up HTML formatting for Excel
                    $(clonedTable).find('td, th').each(function() {
                        const $cell = $(this);
                        const text = $cell.text().trim();
                        $cell.html(text);
                    });

                    const workbook = XLSX.utils.table_to_book(clonedTable, {
                        sheet: "Trial Balance"
                    });

                    const filename = 'Trial_Balance_' + moment().format('YYYY-MM-DD') + '.xlsx';
                    XLSX.writeFile(workbook, filename);

                } catch (error) {
                    console.error('Export error:', error);
                    alert('Error exporting file. Please try again.');
                }
            }

            // Print functionality
            function printReport() {
                try {
                    const table = document.getElementById('trial-balance-table');
                    if (!table) {
                        alert('Table not found');
                        return;
                    }

                    const printWindow = window.open('', '_blank');
                    const companyName = $('#company-name').text() || "Company Name";
                    const reportDate = $('#report-asof').text() || moment().format('MMMM D, YYYY');

                    const printStyles = `
                body { font-family: Arial, sans-serif; margin: 20px; font-size: 12px; }
                .report-header { text-align: center; margin-bottom: 30px; }
                .report-header h2 { margin-bottom: 5px; font-size: 18px; }
                .report-header p { margin: 2px 0; color: #666; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { padding: 8px 12px; border-bottom: 1px solid #ddd; text-align: left; }
                th { background-color: #f8f9fa; font-weight: bold; border-bottom: 2px solid #333; }
                .text-end { text-align: right; }
                .account-header { background-color: #f0f0f0; font-weight: bold; }
                .account-subtotal { background-color: #f5f5f5; font-weight: bold; }
                .grand-total { font-weight: bold; border-top: 2px solid #333; background-color: #e9e9e9; }
                .hidden-row { display: none; }
                .text-success { color: #28a745; }
                .text-danger { color: #dc3545; }
                .toggle-btn { display: none; }
                @media print {
                    body { margin: 0; }
                    .no-print { display: none; }
                }
            `;

                    const printHTML = `
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="utf-8">
                    <title>Trial Balance Report</title>
                    <style>${printStyles}</style>
                </head>
                <body>
                    <div class="report-header">
                        <h2>${companyName}</h2>
                        <p>Trial Balance</p>
                        <p>As of ${reportDate}</p>
                    </div>
                    ${table.outerHTML}
                </body>
                </html>
            `;

                    printWindow.document.write(printHTML);
                    printWindow.document.close();

                    setTimeout(() => {
                        printWindow.print();
                        setTimeout(() => printWindow.close(), 1000);
                    }, 500);

                } catch (error) {
                    console.error('Print error:', error);
                    alert('Error printing report. Please try again.');
                }
            }

            // Email functionality
            function emailReport() {
                const subject = encodeURIComponent('Trial Balance Report');
                const reportDate = $('#report-asof').text();
                const body = encodeURIComponent('Please find the Trial Balance report as of ' + reportDate + '.');
                const mailto = 'mailto:?subject=' + subject + '&body=' + body;

                if (confirm('This will open your default email client. Do you want to continue?')) {
                    window.location.href = mailto;
                }
            }

            // Date validation
            function validateDateInputs() {
                const fromDate = $('#date-from').val();
                const toDate = $('#date-to').val();

                $('#date-from, #date-to').removeClass('is-invalid');
                $('.invalid-feedback').text('');

                if (!fromDate || !toDate) {
                    if (!fromDate) {
                        $('#date-from').addClass('is-invalid');
                        $('#date-from').siblings('.invalid-feedback').text('Please select a start date');
                    }
                    if (!toDate) {
                        $('#date-to').addClass('is-invalid');
                        $('#date-to').siblings('.invalid-feedback').text('Please select an end date');
                    }
                    return false;
                }

                if (new Date(fromDate) > new Date(toDate)) {
                    $('#date-from').addClass('is-invalid');
                    $('#date-from').siblings('.invalid-feedback').text('Start date cannot be after end date');
                    return false;
                }

                return true;
            }

            // Initialize everything when document is ready
            $(document).ready(function() {
                // Initialize DataTable reference
                setTimeout(() => {
                    dataTable = getDataTableInstance();
                }, 1000);

                // Run report button
                $('#run-report').on('click', function(e) {
                    e.preventDefault();
                    if (validateDateInputs()) {
                        refreshTable();
                    }
                });

                // Auto-refresh on filter changes (with debouncing)
                let filterTimeout;
                $(document).on('change', '.filter-control', function() {
                    clearTimeout(filterTimeout);

                    const $element = $(this);

                    // If period dropdown changes, update dates first
                    if ($element.attr('id') === 'report-period') {
                        updateDateInputs();
                        return;
                    }

                    // Debounce other filter changes
                    filterTimeout = setTimeout(() => {
                        if (validateDateInputs()) {
                            refreshTable();
                        }
                    }, 300);
                });

                // Period dropdown change handler
                $('#report-period').on('change', updateDateInputs);

                // Date input validation
                $('#date-from, #date-to').on('change', function() {
                    // Auto-update period to custom when dates are manually changed
                    $('#report-period').val('custom');

                    // Clear any existing validation errors
                    $(this).removeClass('is-invalid');
                    $(this).siblings('.invalid-feedback').text('');
                });

                // Export button
                $('#export-btn').on('click', function(e) {
                    e.preventDefault();
                    exportToExcel();
                });

                // Print button
                $('#print-btn').on('click', function(e) {
                    e.preventDefault();
                    printReport();
                });

                // Email button
                $('#email-btn').on('click', function(e) {
                    e.preventDefault();
                    emailReport();
                });

                // Settings button (placeholder)
                $('#settings-btn').on('click', function(e) {
                    e.preventDefault();
                    alert('Settings feature will be implemented here');
                });

                // Customize button (placeholder)
                $('#customize-btn').on('click', function(e) {
                    e.preventDefault();
                    alert('Customize report feature will be implemented here');
                });

                // Save customization button (placeholder)
                $('#save-btn').on('click', function(e) {
                    e.preventDefault();
                    alert('Save customization feature will be implemented here');
                });

                // Switch view button (placeholder)
                $('#switch-view-btn').on('click', function(e) {
                    e.preventDefault();
                    const $btn = $(this);
                    const currentText = $btn.html();
                    const isModern = currentText.includes('modern');
                    const newText = isModern ?
                        '<i class="fa fa-exchange-alt me-1"></i>Switch to classic view' :
                        '<i class="fa fa-exchange-alt me-1"></i>Switch to modern view';
                    $btn.html(newText);
                    alert('View switching feature will be implemented here');
                });

                // Handle DataTable draw events 
                $(document).on('draw.dt', '#trial-balance-table', function() {
                    setTimeout(() => {
                        initializeToggleControls();
                        applyRowVisibilityFilters();
                    }, 100);
                });

                // Initialize toggle controls on page load
                setTimeout(() => {
                    initializeToggleControls();
                    applyRowVisibilityFilters();
                }, 1500);

                // Handle window resize for responsive table
                $(window).on('resize', function() {
                    const table = getDataTableInstance();
                    if (table && table.columns) {
                        table.columns.adjust();
                    }
                });

                // Add keyboard shortcuts
                $(document).on('keydown', function(e) {
                    // Ctrl+R or F5 to refresh report
                    if ((e.ctrlKey && e.keyCode === 82) || e.keyCode === 116) {
                        e.preventDefault();
                        if (validateDateInputs()) {
                            refreshTable();
                        }
                    }

                    // Ctrl+P to print
                    if (e.ctrlKey && e.keyCode === 80) {
                        e.preventDefault();
                        printReport();
                    }

                    // Ctrl+E to export
                    if (e.ctrlKey && e.keyCode === 69) {
                        e.preventDefault();
                        exportToExcel();
                    }
                });

                // Form submission handling
                $('#filter-form').on('submit', function(e) {
                    e.preventDefault();
                    if (validateDateInputs()) {
                        refreshTable();
                    }
                });
            });

            // Make functions globally available
            window.refreshTrialBalance = refreshTable;
            window.exportTrialBalance = exportToExcel;
            window.printTrialBalance = printReport;

        })(jQuery);
    </script>
        <script>
        $(document).ready(function() {
            // Wait for DataTables to initialize, then setup toggle controls
            setTimeout(function() {
               /// expand all items               
               $('.toggle-btn').click();
               //toggle icon rotate 360 deg
               $('.toggle-icon').css('transform', 'rotate(360deg)');
            }, 5000);
        });
    </script>
     <script>
        let trialBalanceTable;
        let expandedGroups = new Set(); // Track which groups are expanded

        // Initialize DataTable
        $(document).ready(function() {
            trialBalanceTable = window.LaravelDataTables["trial-balance-table"];

            // Initially collapse all detail rows
            // initializeCollapsedState();
            initializeExpandedState();
        });

        // Initialize collapsed state after table draw
        $('#trial-balance-table').on('draw.dt', function() {
            initializeExpandedState();
            restoreExpandedState();
        });

        function initializeExpandedState() {
            // Hide all child rows initially
            $('#trial-balance-table tbody tr.child-row').removeClass('hidden-row');

            $('.toggle-btn')
                .addClass('expanded')
                .removeClass('collapsed')
                .css('transform', 'rotate(90deg)'); 
            
            //hide all debit and credit header
            $('.debit-cell, .credit-cell').hide();
        }


        function restoreExpandedState() {
            // Restore previously expanded groups
            expandedGroups.forEach(function(groupId) {
                expandGroup(groupId, false); // false = don't add to Set again
            });
        }

        function expandGroup(groupId, updateSet = true) {
            
            const $toggleBtn = $(`.toggle-btn[data-target="${groupId}"]`);
            const $childRows = $(`.parent-${groupId}`);            
            // Show child rows with animation
            $childRows.removeClass('hidden-row');
            //remove display none style css
            $childRows.css('display', 'table-row');
            
         
            // Update toggle button state
            $toggleBtn.removeClass('collapsed').addClass('expanded');
            $toggleBtn.css('transform', 'rotate(90deg)'); // ▼

            // Track expanded state
            if (updateSet) {
                expandedGroups.add(groupId);
            }
            $toggleBtn.closest('tr').find('.debit-cell, .credit-cell').hide();
        }

        function collapseGroup(groupId, updateSet = true) {
            const $toggleBtn = $(`.toggle-btn[data-target="${groupId}"]`);
            const $childRows = $(`.parent-${groupId}`);
            // Hide child rows
            $childRows.addClass('hidden-row');

            // Update toggle button state
            $toggleBtn.removeClass('expanded').addClass('collapsed');
            $toggleBtn.css('transform', 'rotate(90deg)'); // ▶

            // Remove from expanded state
            if (updateSet) {
                expandedGroups.delete(groupId);
            }
            $toggleBtn.closest('tr').find('.debit-cell, .credit-cell').show();
        }

        // Toggle group expansion on click
        $(document).on('click', '.toggle-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const groupId = $(this).data('target');
            const $row = $(this).closest('tr'); // current row
            const rowData = $('#trial-balance-table').DataTable().row($row).data(); // row data
            const isExpanded = $(this).hasClass('expanded');

            if (!isExpanded) {
                collapseGroup(groupId);
                $row.find('.debit-cell, .credit-cell').show(); 
            } else {
                expandGroup(groupId);
                $row.find('.debit-cell, .credit-cell').hide(); 
            }
        });



        // Expand all groups
        $('#expand-all').on('click', function() {
            $('.toggle-btn').each(function() {
                const groupId = $(this).data('target');
                expandGroup(groupId);
            });
        });

        // Collapse all groups
        $('#collapse-all').on('click', function() {
            $('.toggle-btn').each(function() {
                const groupId = $(this).data('target');
                collapseGroup(groupId);
            });
        });

        const showTable = () => {
            trialBalanceTable.draw(false);
        }

        // Trigger reload when dropdowns change
        $('#filter-subtype, #filter-type').on('change', showTable);

        
        // Initialize default date range display
        $('#date-range-display').text(
            moment().startOf('year').format('MMM DD, YYYY') + ' - ' + moment().format('MMM DD, YYYY')
        );

        // Enhanced row hover effects
        $(document).on('mouseenter', '#trial-balance-table tbody tr', function() {
            $(this).addClass('table-hover-effect');
        }).on('mouseleave', '#trial-balance-table tbody tr', function() {
            $(this).removeClass('table-hover-effect');
        });

        // Double-click to toggle (alternative interaction)
        $(document).on('dblclick', '.account-header', function() {
            const $toggleBtn = $(this).find('.toggle-btn');
            if ($toggleBtn.length) {
                $toggleBtn.trigger('click');
            }
        });

        // Keyboard navigation support
        $(document).on('keydown', function(e) {            
            if (e.ctrlKey || e.metaKey) {
                switch (e.key) {
                    case 'e':
                    case 'E':
                        e.preventDefault();
                        $('#expand-all').trigger('click');
                        break;
                    case 'c':
                    case 'C':
                        e.preventDefault();
                        $('#collapse-all').trigger('click');
                        break;
                }
            }
        });

        // Context menu for additional options (right-click)
        $(document).on('contextmenu', '.account-header', function(e) {
            e.preventDefault();
            const $row = $(this);
            const $toggleBtn = $row.find('.toggle-btn');
            const groupId = $toggleBtn.data('target');
            const isExpanded = $toggleBtn.hasClass('expanded');

            // Simple context action - toggle expansion
            if (isExpanded) {
                collapseGroup(groupId);
            } else {
                expandGroup(groupId);
            }
        });

        // Export functionality enhancement
        $(document).on('click', '.dt-button', function() {
            // Temporarily expand all groups for complete export
            const wasCollapsed = [];
            $('.toggle-btn.collapsed').each(function() {
                const groupId = $(this).data('target');
                wasCollapsed.push(groupId);
                expandGroup(groupId, false);
            });

            // After a short delay, restore the previous state
            setTimeout(function() {
                wasCollapsed.forEach(function(groupId) {
                    collapseGroup(groupId, false);
                });
            }, 1000);
        });

        // Responsive mobile handling
        function handleMobileView() {
            const isMobile = $(window).width() < 768;

            if (isMobile) {
                // On mobile, show more compact view
                $('#trial-balance-table').addClass('mobile-view');

                // Auto-collapse all on mobile for better performance
                if ($('.toggle-btn.expanded').length > 2) {
                    $('#collapse-all').trigger('click');
                }
            } else {
                $('#trial-balance-table').removeClass('mobile-view');
            }
        }

        // Handle window resize
        $(window).on('resize', function() {
            handleMobileView();
        });

        // Initial mobile check
        handleMobileView();
    </script>
@endpush
