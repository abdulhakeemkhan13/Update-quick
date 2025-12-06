@extends('layouts.admin')

@section('content')
    <div class="content-wrapper">
        <!-- Header with actions -->
        <div class="report-header">
            <h4 class="mb-0">{{ $pageTitle }}</h4>
            <div class="header-actions">
                <span class="last-updated">Last updated just now</span>
                <div class="actions">
                    <button class="btn btn-icon" title="Refresh" onclick="refreshTableData()"><i
                            class="fa fa-sync"></i></button>
                    <button class="btn btn-icon"
                        onclick="exportDataTable('proposals-by-customer-table', '{{ $pageTitle }}', 'print')"
                        title="Print"><i class="fa fa-print"></i></button>
                    <button class="btn btn-icon" type="button" data-bs-toggle="modal" data-bs-target="#exportModal"
                        title="Export">
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
                            <button onclick="exportDataTable('proposals-by-customer-table', '{{ $pageTitle }}')"
                                class="btn btn-success mx-auto w-75" data-bs-dismiss="modal">
                                Export to Excel
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button onclick="exportDataTable('proposals-by-customer-table', '{{ $pageTitle }}', 'pdf')"
                                class="btn btn-success mx-auto w-75" data-bs-dismiss="modal">
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
                            <div class="filter-item col-md-2 mt-4">
                                <div class="view-options-wrapper">
                                    <button class="btn btn-view-options" id="view-options-btn"
                                        style="border: none !important; border-radius: 0px !important; width: 130px;">
                                        <i class="fa fa-eye"></i>
                                        <span>View options</span>
                                    </button>

                                    <!-- Dropdown Menu -->
                                    <div class="view-dropdown" id="view-options-dropdown">
                                        <div class="view-item" data-view="compact">Compact view</div>
                                        <div class="view-item selected" data-view="normal">
                                            <i class="fa fa-check text-success"></i> Normal view
                                        </div>
                                        <div class="view-item" data-view="cozy">Cozy view</div>
                                        <div class="view-item" data-view="comfort">Comfort view</div>

                                        <hr class="dropdown-divider ">

                                        <div class="view-item disabled db_disabled">Expand all</div>
                                        <div class="view-item disabled db_disabled">Collapse all</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="col-md-5">
                        <div class="row mt-4">
                            <div class="d-flex gap-2 justify-content-end align-items-center">
                                <button class="btn btn-outline" id="columns-btn">
                                    <i class="fa fa-columns"></i> Columns <span class="badge">8</span>
                                </button>
                                <button class="btn btn-outline" type="button" data-bs-toggle="offcanvas"
                                    data-bs-target="#filterSidebar">
                                    <i class="fa fa-filter"></i> Filter
                                </button>
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

        <!-- Filter Sidebar -->
        <div class="offcanvas offcanvas-end" data-bs-scroll="true" tabindex="-1" id="filterSidebar"
            aria-labelledby="filterSidebarLabel">
            <div class="offcanvas-header" style="background: #f9fafb; border-bottom: 1px solid #e6e6e6;">
                <h5 class="offcanvas-title" id="filterSidebarLabel">Filters</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
           
                <div class="filter-item mb-3">
                    <label class="filter-label">Vendor</label>
                    <select id="sidebar-filter-status" class="form-control">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
               
            </div>
        </div>

        <!-- Report Content -->
        <div class="report-content">
            <div class="report-title-section">
                <h2 class="report-title">{{ $pageTitle }}</h2>
                <p class="date-range">
                    <span id="date-range-display">As of {{ Carbon\Carbon::now()->format('F j, Y') }}</span>
                </p>
            </div>
            <div class="table-wrapper qb-scroll">
                <div class="table-container">
                    {!! $dataTable->table(['class' => 'table proposals-by-customer-table', 'id' => 'proposals-by-customer-table']) !!}
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
                            <input type="checkbox" id="report-period"> Report period
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
                            <input type="checkbox" id="report-basis"> Report basis (cash vs. accrual)
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

        .summary-total {
            font-weight: bold;
        }

        /* NORMAL VIEW (default) */
        .table-normal tbody tr td {
            padding: 12px 10px;
            font-size: 14px;
        }

        /* COMPACT VIEW */
        .table-compact tbody tr td {
            padding: 4px 6px !important;
            font-size: 12px !important;
        }

        /* COZY VIEW */
        .table-cozy tbody tr td {
            padding: 16px 12px !important;
            font-size: 15px !important;
        }

        /* COMFORT VIEW */
        .table-comfort tbody tr td {
            padding: 20px 14px !important;
            font-size: 16px !important;
        }


        .view-options-wrapper {
            position: relative;
        }

        .view-dropdown {
            position: absolute;
            top: 40px;
            left: 0;
            width: 180px;
            background: white;
            border: 1px solid #dcdcdc;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            border-radius: 4px;
            padding: 6px 0;
            display: none;
            z-index: 999;
        }

        /* QuickBooks-style fixed height table */
        .qb-scroll {
            max-height: calc(100vh - 220px);
            /* Adjust height like QB */
            overflow-y: auto;
            overflow-x: hidden;
            border: 1px solid #e1e1e1;
            border-radius: 4px;
        }

        /* Smooth QB scroll feel */
        .qb-scroll::-webkit-scrollbar {
            width: 8px;
        }

        .qb-scroll::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 5px;
        }

        .qb-scroll::-webkit-scrollbar-thumb:hover {
            background: #9d9d9d;
        }

        /* Optional: Freeze header like QuickBooks */
        .qb-scroll table thead tr th {
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
        }

        table {
            width: 80% !important;
            border: 1px solid darkgray !important;
        }



        .view-item {
            padding: 8px 14px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            gap: 6px;
            align-items: center;
        }

        .view-item:hover {
            background: #f2f2f2;
        }

        .view-item.selected {
            color: #007b00;
            font-weight: 500;
        }

        .dropdown-divider {
            margin: 5px 0;
        }

        .db_disabled {
            cursor: not-allowed;
            opacity: 0.6;
            color: darkgray;
            pointer-events: none;
        }

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
            margin: 10px;
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
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
        .col-date {
            width: 22%;
        }

        .col-num {
            width: 10%;
        }

        .col-status {
            width: 12%;
        }

        .col-accepted-on {
            width: 10%;
        }

        .col-accepted-by {
            width: 13%;
        }

        .col-expiration {
            width: 12%;
        }

        .col-invoice {
            width: 10%;
        }

        .col-amount {
            width: 11%;
        }

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
        document.getElementById("view-options-btn").addEventListener("click", function() {
            document.getElementById("view-options-dropdown").style.display =
                document.getElementById("view-options-dropdown").style.display === "block" ?
                "none" :
                "block";
        });


        // Close dropdown on click outside
        document.addEventListener("click", function(e) {
            const dropdown = document.getElementById("view-options-dropdown");
            const button = document.getElementById("view-options-btn");

            if (!button.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.style.display = "none";
            }
        });

        // Select view and show checkmark
        document.querySelectorAll(".view-item").forEach(item => {
            item.addEventListener("click", function() {

                if (this.classList.contains("db_disabled")) return;

                // REMOVE old selected
                document.querySelectorAll(".view-item").forEach(i => {
                    i.classList.remove("selected");
                    if (i.querySelector(".fa-check")) i.querySelector(".fa-check").remove();
                });

                // ADD selected + checkmark
                this.classList.add("selected");
                this.insertAdjacentHTML("afterbegin", '<i class="fa fa-check text-success"></i>');

                let viewType = this.getAttribute("data-view");

                let table = document.getElementById("proposals-by-customer-table");

                // Remove previous view class
                table.classList.remove("table-normal", "table-compact", "table-cozy", "table-comfort");

                // Add new class
                table.classList.add("table-" + viewType);
            });
        });

        $(document).ready(function() {

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

            let lastUpdatedTime = Date.now();

            // Sync period filters
            $('#sidebar-filter-period, #header-filter-period').on('change', function() {
                const value = $(this).val();
                $('#header-filter-period, #sidebar-filter-period').val(value);
                if (value !== 'custom_date') {
                    // updateDateRangeFromPeriod(value);
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
                // data.customer_name = $('#sidebar-filter-customer').val();
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
            const endDate = moment($('#filter-end-date').val());
            $('#date-range-display').text('As of ' + endDate.format('MMMM D, YYYY'));
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
            updateColumnCountBadge();
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
            table.rows({
                search: 'applied'
            }).every(function() {
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
                xhrFields: {
                    responseType: 'blob'
                },
                success: function(blob, status, xhr) {
                    let filename = xhr.getResponseHeader('Content-Disposition')?.split('filename=')[1]?.replace(
                        /"/g, '') || pageTitle + '.' + (format === 'pdf' ? 'pdf' : 'xlsx');

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

                if (isExpanded) {
                    childRows.forEach(row => row.style.display = 'none');
                    expandedGroups.set(customerId, false);
                    chevron.style.transform = 'rotate(-90deg)';
                } else {
                    childRows.forEach(row => row.style.display = '');
                    expandedGroups.set(customerId, true);
                    chevron.style.transform = 'rotate(0deg)';
                }
            });
        });

        // Refresh button animation
        const refreshBtn = document.querySelector('.btn-icon[title="Refresh"]');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                this.querySelector('i').style.animation = 'spin 1s linear';
                setTimeout(() => {
                    this.querySelector('i').style.animation = '';
                }, 1000);
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
            let refreshDataRetryCount = 0;
            const MAX_RETRY_COUNT = 50; // Max 5 seconds of retries (50 * 100ms)

            function refreshData() {
                if (window.LaravelDataTables && window.LaravelDataTables["proposals-by-customer-table"]) {
                    window.LaravelDataTables["proposals-by-customer-table"].draw();
                    refreshDataRetryCount = 0; // Reset counter on success
                } else if (refreshDataRetryCount < MAX_RETRY_COUNT) {
                    refreshDataRetryCount++;
                    setTimeout(refreshData, 100);
                } else {
                    // Only log if we've exhausted all retries
                    if (refreshDataRetryCount === MAX_RETRY_COUNT) {
                        console.warn('DataTable initialization timeout after ' + (MAX_RETRY_COUNT * 100) + 'ms');
                        refreshDataRetryCount++; // Increment to prevent repeated warnings
                    }
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
            $('#proposals-by-customer-table').on('preXhr.dt', function(e, settings, data) {
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
                // Redraw table to apply number formatting immediately
                if (window.LaravelDataTables && window.LaravelDataTables["proposals-by-customer-table"]) {
                    window.LaravelDataTables["proposals-by-customer-table"].draw(false);
                }
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
                if (window.LaravelDataTables && window.LaravelDataTables["proposals-by-customer-table"]) {
                    // Note: Column reordering requires ColReorder extension
                    console.log('Column order would be applied:', order);
                }
            }

            // Handle column visibility
            $('.columns-modal input[type="checkbox"]').on('change', function() {
                const columnIndex = $(this).closest('.column-item').data('column');
                const isVisible = $(this).prop('checked');

                if (columnIndex !== undefined && window.LaravelDataTables && window.LaravelDataTables[
                        "proposals-by-customer-table"]) {
                    try {
                        window.LaravelDataTables["proposals-by-customer-table"].column(columnIndex).visible(
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
                        <title> Print</title>
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
                            ${$('.proposals-by-customer-table').html()}
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
            $(document).on('draw.dt', '#proposals-by-customer-table', function() {
                if (window.reportOptions) {
                    $('#proposals-by-customer-table tbody tr').each(function() {
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
                                                .toString() + ')');
                                            break;
                                        case '100-':
                                            $cell.text(Math.abs(value).toString() +
                                                '-');
                                            break;
                                        default:
                                            $cell.text('-' + Math.abs(value)
                                                .toString());
                                    }
                                } else if (value > 0) {
                                    $cell.text(value.toString());
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

    <style>
        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }
    </style>

    {!! $dataTable->scripts() !!}
@endpush
