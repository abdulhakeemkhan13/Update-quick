@extends('layouts.admin')

@section('content')
    <style>
        .section-row {
            /* background-color: #f2f2f2 !important; */
            font-weight: bold;
        }

        .profit-loss-table {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .profit-loss-table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            /* color: #495057; */
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
            /* color: #495057; */
            /* text-transform: uppercase; */
        }

        .section-total-amount {
            font-size: 1em;
            font-style: italic;
        }

        .toggle-section {
            user-select: none;
        }

        .toggle-section[style*="pointer"]:hover {
            /* color: #007bff; */
        }

        .toggle-caret {
            transition: transform 0.2s ease;
            /* color: #007bff; */
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
            /* border-top: 1px solid #000; */
            font-weight: bold;
        }

        .total-row .total-amount {
            /* border-top: 2px solid #000; */
            /* border-bottom: 2px double #000; */
            font-weight: bold;
        }

        .section-row {
            /* background-color: #f8f9fa !important; */
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

        .compact-view .toggle-caret {
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

    <style>
        /* ===== Base / Layout ===== */
        .quickbooks-report {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            min-height: 100vh;
            color: #262626;
            width: 100%;
            /* stay inside viewport */
            overflow-x: hidden;
            box-sizing: border-box;
        }

        /* Header */
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
            padding: 8px 24px;
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
            transition: .15s;
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
            transition: .15s;
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
            box-shadow: 0 0 0 2px rgba(0, 102, 204, .2);
        }

        @media (max-width:1020px) {
            .controls-bar {
                overflow-x: auto;
            }

            .controls-inner {
                min-width: max-content;
            }
        }

        /* Report content */
        .report-content {
            background: #fff;
            margin: 0;
            border-radius: 0;
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

        /* Table (edge-to-edge, fixed layout) */
        .table-container {
            background: #fff;
            max-height: calc(100vh - 260px);
            overflow-y: auto;
            overflow-x: auto !important;
        }

        #sales-tax-liability-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            font-size: 13px;
        }

        #sales-tax-liability-table th {
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

        #sales-tax-liability-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f3f4f6;
            color: #262626;
            vertical-align: middle;
            white-space: nowrap;
        }

        #sales-tax-liability-table tbody tr:hover {
            background: #f9fafb;
        }

        #sales-tax-liability-table .text-right {
            text-align: right;
        }

        /* Lock column widths so children align perfectly */
        #sales-tax-liability-table th:first-child,
        #sales-tax-liability-table td:first-child {
            width: 44px !important;
            min-width: 44px !important;
            max-width: 44px !important;
        }

        #sales-tax-liability-table th:nth-child(3),
        #sales-tax-liability-table td:nth-child(3) {
            width: 180px !important;
        }

        #sales-tax-liability-table th:nth-child(4),
        #sales-tax-liability-table td:nth-child(4) {
            width: 160px !important;
        }

        /* Child rows (no header; only amounts aligned to last two columns) */
        .child-wrap {
            padding: 0;
        }

        .child-table {
            width: 100%;
            table-layout: fixed;
            font-size: 12px;
            border-collapse: collapse;
        }

        .child-table td {
            padding: 8px 16px;
            border-bottom: 1px dashed #e5e7eb;
            white-space: nowrap;
        }

        .child-table td:nth-child(1) {
            width: 44px;
        }

        /* toggle column space */
        .child-table td:nth-child(3) {
            width: 180px;
            text-align: right;
        }

        .child-table td:nth-child(4) {
            width: 160px;
            text-align: right;
        }

        .toggle-child {
            padding: 4px 8px;
            border: 1px solid #e5e7eb;
            background: #fff;
            border-radius: 4px;
        }

        .shown .toggle-child {
            background: #eef2ff;
            border-color: #c7d2fe;
        }

        /* Drawer-style Modals */
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

        /* Drawer override (slide-in right) */
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

        /* QB columns UI */
        .qb-columns-title {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
        }

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

        .qb-pill .pill {
            width: 22px;
            height: 22px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #d1d5db;
            background: #fff;
        }

        .qb-pill .pill i {
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

        .qb-ghost {
            opacity: .6;
            background: #eef2ff;
        }

        .qb-chosen {
            background: #f1f5f9;
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

            #sales-tax-liability-table {
                font-size: 11px;
            }

            #sales-tax-liability-table th,
            #sales-tax-liability-table td {
                padding: 6px 4px;
            }
        }
    </style>


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
            padding: 32px 24px 24px !important;
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

        .profit-loss-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .profit-loss-table th {
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

        .profit-loss-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f3f4f6;
            color: #262626;
        }

        .profit-loss-table tbody tr:hover {
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

        .parent-row {
            cursor: pointer;
        }
    </style>

    <div class="report-header">
        <h4 class="mb-0">{{ __('Profit & Loss Comparison') }}</h4>
        <div class="header-actions">
            <span class="last-updated">Last updated just now</span>
            <div class="actions">
                <button class="btn btn-icon" title="Refresh" id="btn-refresh"><i class="fa fa-sync"></i></button>
                <button class="btn btn-icon"
                    onclick="exportDataTable('profit-loss-table', '{{ __('Profit & Loss Comparison') }}', 'print')"><i
                        class="fa fa-print"></i></button>
                <button class="btn btn-icon" title="Export" id="btn-export"><i class="fa fa-external-link-alt"></i></button>
                <button class="btn btn-icon" title="More options" id="btn-more"><i class="fa fa-ellipsis-v"></i></button>
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
                        <button onclick="exportDataTable('profit-loss-table', '{{ __('Profit & Loss Comparison') }}')"
                            class="btn btn-success mx-auto w-75 justify-content-center text-center"
                            data-action="excel">Export to
                            Excel</button>
                    </div>
                    <div class="col-md-6">
                        <button
                            onclick="exportDataTable('profit-loss-table', '{{ __('Profit & Loss Comparison') }}', 'pdf')"
                            class="btn btn-success mx-auto w-75 justify-content-center text-center" data-action="pdf">Export
                            to
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
                    // Skip td's where all child elements are hidden
                    if ($(this).find(':visible').length === 0) {
                        return; // continue to next td
                    }

                    let cellContent;

                    if ($(this).find('h4').length > 0 || $(this).find('strong').length > 0) {
                        // If <h4> or <strong> exists, keep HTML (preserve h4/strong)
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
                    ReportPeriod: $("#date-range-display").text(),
                    HeaderFooterAlignment: ['center', 'center'],
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
                <!-- filter row -->
                <div class="col-md-7">
                    <div class="row">
                        <!-- Report period -->
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

                        <!-- Date Range -->
                        <div class="filter-item col-md-2">
                            <label class="filter-label">Date Range</label>
                            <input type="text" id="daterange" class="form-control date-input"
                                value="{{ Carbon\Carbon::now()->startOfYear()->format('m/d/Y') }} - {{ Carbon\Carbon::now()->format('m/d/Y') }}">
                            <input type="hidden" id="filter-start-date"
                                value="{{ Carbon\Carbon::now()->startOfYear()->format('Y-m-d') }}">
                            <input type="hidden" id="filter-end-date" value="{{ Carbon\Carbon::now()->format('Y-m-d') }}">
                        </div>

                        <!-- Accounting method 
                        <div class="filter-item col-md-2">
                            <label class="filter-label">Accounting method</label>
                            <select id="accounting-method" class="form-control">
                                <option value="accrual" selected>Accrual</option>
                                <option value="cash">Cash</option>
                            </select>
                        </div> -->

                        <!-- View options trigger & dropdown
                        <div class="filter-item col-md-2 mt-4" style="width: 140px !important;">
                            <div class="view-options" style="position: relative; width: 150px;">
                                <button class="btn btn-view-options text-nowrap d-inline-flex align-items-center"
                                    id="view-options-btn"
                                    style="border:none!important;border-left:1px solid #d1d5db!important;border-radius:0!important;width:130px;gap:6px;">
                                    <i class="fa fa-eye"></i>
                                    <span>View options</span>
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
                        </div>  -->
                        <!-- /View options -->
                    </div>
                </div>

                <!-- Action buttons area -->
                <div class="col-md-5">
                    <div class="row mt-4">
                        <div class="d-flex gap-2 justify-content-end align-items-center">
                            <button class="btn btn-outline" id="filter-btn">
                                <i class="fa fa-filter"></i> Filter
                            </button>

                            <button class="btn btn-outline" id="general-options-btn">
                                <i class="fa fa-cog"></i> General options
                            </button>
                        </div>
                    </div>
                </div>
                <!-- /Action buttons area -->
            </div>
        </div>
    </div>


    <!-- Main Content -->
    <div class="content-wrapper">
        <div class="d-flex flex-column w-tables rounded mt-3 bg-white">
            <div class="report-title-section p-2">
                <h2 class="report-title">Profit & Loss Comparison</h2>
                <p class="date-range">
                    <span id="date-range-display">
                        {{ Carbon\Carbon::now()->startOfYear()->format('F j, Y') }} -
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
                                value="{{ Carbon\Carbon::now()->startOfYear()->format('m/d/Y') }} - {{ Carbon\Carbon::now()->format('m/d/Y') }}">
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
                    <h6 class="section-title">Number format <i class="fa fa-caret-up"></i></h6>
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
                    <h6 class="section-title">Header <i class="fa fa-caret-up"></i></h6>
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
                    <h6 class="section-title">Footer <i class="fa fa-caret-up"></i></h6>
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

            if (typeof moment === 'undefined') {
                console.error('Moment.js is not loaded');
                return;
            }

            if (typeof $.fn.daterangepicker === 'undefined') {
                console.error('DateRangePicker plugin is not loaded');
                return;
            }

            setupEventListeners();
            initializeViewOptions();
            initializeFilterModal();
            updateDateDisplay();

            setTimeout(function() {
                initializeTableState();
                updateButtonVisibility();
            }, 1000);
        });

        function initializeViewOptions() {
            updateViewCheckmarks();
        }

        function initializeFilterModal() {
            $('#modal-filter-period').val($('#filter-period').val());
            $('#modal-accounting-method').val($('#accounting-method').val());

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

        $('#view-options-btn').on('click', function(e) {
            e.stopPropagation();
            $('#view-options-dropdown').toggle();
        });

        window.viewState = window.viewState || {
            viewType: 'normal',
            expandState: 'expand'
        };

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

        function updateViewCheckmarks() {
            $('.view-option-item').removeClass('active');
            $('.view-option-item[data-value="' + window.viewState.viewType + '"]').addClass('active');
            $('.view-option-item[data-value="' + window.viewState.expandState + '"]').addClass('active');
        }

        $(function() {
            applyViewState();
            updateViewCheckmarks();
        });

        $('#filter-btn').on('click', function() {
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

        $('#apply-filters').on('click', function() {
            $('#filter-period').val($('#modal-filter-period').val());
            $('#accounting-method').val($('#modal-accounting-method').val());

            const modalPicker = $('#modal-daterange').data('daterangepicker');
            if (modalPicker) {
                $('#filter-start-date').val(modalPicker.startDate.format('YYYY-MM-DD'));
                $('#filter-end-date').val(modalPicker.endDate.format('YYYY-MM-DD'));

                $('#daterange').data('daterangepicker').setStartDate(modalPicker.startDate);
                $('#daterange').data('daterangepicker').setEndDate(modalPicker.endDate);
            }

            updateDateDisplay();
            updateColumnHeaders(); // Add this
            refreshTable();
            $('#filter-overlay').hide();
        });

        $('#modal-filter-period').on('change', function() {
            const period = $(this).val();
            if (period !== 'custom_date') {
                updateModalDateRange(period);
            }
        });

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

            $('#modal-daterange').data('daterangepicker').setStartDate(startDate);
            $('#modal-daterange').data('daterangepicker').setEndDate(endDate);
        }

        $('#filter-period').on('change', function() {
            updateDateRange($(this).val());
        });

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

            $('#filter-start-date').val(startDate.format('YYYY-MM-DD'));
            $('#filter-end-date').val(endDate.format('YYYY-MM-DD'));

            $('#daterange').data('daterangepicker').setStartDate(startDate);
            $('#daterange').data('daterangepicker').setEndDate(endDate);

            updateDateDisplay();
            updateColumnHeaders(); // Add this
            refreshTable();
        }

        function updateDateDisplay() {
            const startDate = moment($('#filter-start-date').val());
            const endDate = moment($('#filter-end-date').val());

            const formattedStart = startDate.format('MMMM D, YYYY');
            const formattedEnd = endDate.format('MMMM D, YYYY');

            $('#date-range-display').text(formattedStart + ' - ' + formattedEnd);
        }

        // NEW FUNCTION: Update column headers dynamically
        function updateColumnHeaders() {
            const startDate = moment($('#filter-start-date').val());
            const endDate = moment($('#filter-end-date').val());
            const prevStartDate = startDate.clone().subtract(1, 'year');
            const prevEndDate = endDate.clone().subtract(1, 'year');

            // Update table headers
            const $table = $('#profit-loss-table');
            const $headers = $table.find('thead th');

            if ($headers.length >= 3) {
                // Current period header
                $($headers[1]).text(startDate.format('MMM D YYYY').toUpperCase() + ' - ' + endDate.format('MMM D YYYY')
                    .toUpperCase());

                // Previous period header
                $($headers[2]).text(prevStartDate.format('MMM D YYYY').toUpperCase() + ' - ' + prevEndDate.format(
                    'MMM D YYYY').toUpperCase() + ' (PY)');
            }
        }

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
            $('#filter-start-date').val(start.format('YYYY-MM-DD'));
            $('#filter-end-date').val(end.format('YYYY-MM-DD'));

            updateDateDisplay();
            updateColumnHeaders(); // Add this
            refreshTable();
        });

        function setupEventListeners() {
            $('#profit-loss-table').on('preXhr.dt', handleDataTablePreXhr);
            $('#profit-loss-table').on('draw.dt', handleDataTableDraw);
            $(document).on('click', '.toggle-section', handleSectionToggle);
            $('#accounting-method').on('change', function() {
                refreshTable();
            });
        }

        function handleDataTablePreXhr(e, settings, data) {
            const startDate = $('#filter-start-date').val();
            const endDate = $('#filter-end-date').val();
            const accountingMethod = $('#accounting-method').val();

            data.startDate = startDate;
            data.endDate = endDate;
            data.accountingMethod = accountingMethod;
        }

        function handleDataTableDraw() {
            setTimeout(function() {
                initializeTableState();
                updateButtonVisibility();
                applyViewState();
                updateColumnHeaders(); // Add this to ensure headers are updated after draw
            }, 100);
        }

        function handleExpandAll() {
            $('.child-row, .subtotal-row').show();
            $('.toggle-caret').removeClass('fa-caret-right').addClass('fa-caret-down');
            $('.section-total-amount').hide();
        }

        function handleCollapseAll() {
            $('.child-row, .subtotal-row').hide();
            $('.toggle-caret').removeClass('fa-caret-right').removeClass('fa-caret-down');
            $('.section-total-amount').show();
        }

        function handleSectionToggle(e) {
            e.preventDefault();

            if (window.viewState.viewType === 'compact') {
                return;
            }

            const $this = $(this);
            const group = $this.data('group');
            const $row = $this.closest('tr');
            const $caret = $this.find('.toggle-caret');
            const $sectionTotal = $row.find('.section-total-amount[data-group="' + group + '"]');
            const $childRows = $('.group-' + group);

            if ($caret.length === 0) return;
            if ($caret.hasClass('fa-caret-down')) {
                $childRows.hide();
                $caret.removeClass('fa-caret-down').addClass('fa-caret-right');
                $sectionTotal.show();
            } else {
                $childRows.show();
                $caret.removeClass('fa-caret-right').addClass('fa-caret-down');
                $sectionTotal.hide();
            }
        }

        function initializeTableState() {
            $('.child-row, .subtotal-row').show();
            $('.toggle-caret').removeClass('fa-caret-right').addClass('fa-caret-down');
            $('.section-total-display').hide();

            $('.toggle-section').each(function() {
                const group = $(this).data('group');
                const hasChildren = $('.group-' + group).length > 0;
                const $caret = $(this).find('.toggle-caret');

                if (hasChildren && $caret.length === 0) {
                    $(this).prepend('<i class="fas fa-caret-down toggle-caret mr-2"></i>');
                    $(this).css('cursor', 'pointer');
                } else if (!hasChildren) {
                    $caret.remove();
                    $(this).css('cursor', 'default');
                }
            });
        }

        function updateButtonVisibility() {
            const hasAnyChildren = $('.toggle-caret').length > 0;
        }

        $('#general-options-btn').on('click', function() {
            $('#general-options-overlay').show();
        });

        $('#close-general-options, #general-options-overlay').on('click', function(e) {
            if (e.target === this) {
                $('#general-options-overlay').hide();
            }
        });

        function applyGeneralOptions() {
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

            applyNumberFormatting(window.reportOptions);
            applyHeaderFooterSettings(window.reportOptions);
            refreshTable();
        }

        function applyNumberFormatting(options) {
            $('#custom-number-format').remove();

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
            $('.report-title-section').css('text-align', options.headerAlignment);

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

        $('.general-options-modal input, .general-options-modal select').on('change', function() {
            applyGeneralOptions();
        });

        $(document).on('draw.dt', '#profit-loss-table', function() {
            if (window.reportOptions) {
                $('.profit-loss-table tbody tr').each(function() {
                    const $row = $(this);

                    $row.find('td').each(function(index) {
                        const $cell = $(this);
                        const text = $cell.text().trim();

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

            // Add footer if it doesn't exist
            if ($('.report-footer').length === 0) {
                const currentDate = new Date();
                const dateStr = currentDate.toLocaleDateString();
                const timeStr = currentDate.toLocaleTimeString();
                const basisStr = $('#accounting-method').val() === 'accrual' ? 'Accrual Basis' : 'Cash Basis';

                let footerHTML =
                    '<div class="report-footer" style="padding: 20px; border-top: 1px solid #e6e6e6; text-align: center; font-size: 12px; color: #6b7280;">';

                // if (options.datePrepared) {
                footerHTML += '<div>Date Prepared: ' + dateStr + '</div>';
                // }

                // if (options.timePrepared) {
                footerHTML += '<div>Time Prepared: ' + timeStr + '</div>';
                // }

                // if (options.reportBasis) {
                footerHTML += '<div>Report Basis: ' + basisStr + '</div>';
                // }

                footerHTML += '</div>';

                $('.w-tables').append(footerHTML);
            }
        });

        function refreshTable() {
            if (window.LaravelDataTables && window.LaravelDataTables["profit-loss-table"]) {
                window.LaravelDataTables["profit-loss-table"].draw(false);
            }
        }

        // Close dropdown when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#view-options-btn, #view-options-dropdown').length) {
                $('#view-options-dropdown').hide();
            }
        });
    </script>
    {!! $dataTable->scripts() !!}
@endpush
