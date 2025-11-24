@extends('layouts.admin')

@section('content')
    <style>
        .sticky-column {
            position: sticky !important;
            left: 0;
            z-index: 10;
            background-color: white !important;
        }

        .section-row .sticky-column {
            /* background-color: #f8f9fa !important; */
        }

        .subtotal-row .sticky-column,
        .total-row .sticky-column {
            /* background-color: #f8f9fa !important; */
        }

        thead th.sticky-column {
            z-index: 11 !important;
            background-color: #f8f9fa !important;
        }

        .section-row {
            /* background-color: #f8f9fa !important; */
            font-weight: bold;
        }

        .section-row:hover {
            background-color: #e9ecef !important;
        }

        .section-row:hover .sticky-column {
            background-color: #e9ecef !important;
        }

        .section-month-total,
        .section-total-amount {
            font-weight: bold;
            /* color: #495057; */
            font-style: italic;
        }

        .profit-loss-table {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            border-collapse: separate !important;
            border-spacing: 0;
        }

        .profit-loss-table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            /* color: #495057; */
            white-space: nowrap;
            padding: 12px 8px;
        }

        .profit-loss-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .profit-loss-table tbody tr:hover .sticky-column {
            background-color: #f8f9fa;
        }

        .profit-loss-table tbody td {
            padding: 8px;
            border-bottom: 1px solid #f0f0f0;
        }

        .text-right {
            text-align: right !important;
        }

        .section-header {
            font-weight: 700;
            /* font-size: 1.05em;
                    color: #495057; */
            /* text-transform: uppercase; */
        }

        .toggle-section {
            user-select: none;
        }

        .toggle-section[style*="pointer"]:hover {
            /* color: #007bff; */
        }

        .toggle-chevron {
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

        .subtotal-row {
            background-color: #f8f9fa;
            font-weight: bold;
            /* border-top: 1px solid #000 !important; */
        }

        .subtotal-row td {
            /* border-top: 1px solid #000 !important; */
        }

        .total-row {
            background-color: #e9ecef;
            font-weight: bold;
            /* border-top: 2px solid #000 !important; */
        }

        .total-row td {
            /* border-top: 1px solid #000 !important; */
        }

        .subtotal-label,
        .total-label {
            font-weight: bold;
        }

        .total-amount {
            font-weight: bold;
        }

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
            width: 100%;
        }

        .btn-view-options:hover {
            background: #f9fafb;
            border-color: #9ca3af;
        }

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
            visibility: hidden;
        }

        .view-option-item.active .checkmark {
            visibility: visible;
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

        /* Compact view styles */
        .compact-view .child-row {
            display: none !important;
        }

        .compact-view .subtotal-row {
            display: none !important;
        }

        .compact-view .toggle-chevron {
            transform: rotate(-90deg);
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
        <h4 class="mb-0">{{ __('Profit & Loss by Month') }}</h4>
        <div class="header-actions">
            <span class="last-updated">Last updated just now</span>
            <div class="actions">
                <button class="btn btn-icon" title="Refresh" id="btn-refresh"><i class="fa fa-sync"></i></button>
                <button class="btn btn-icon"
                    onclick="exportDataTable('profit-loss-table', '{{ __('Profit & Loss by Month') }}', 'print')"><i
                        class="fa fa-print"></i></button>
                <button class="btn btn-icon" title="Export" id="btn-export"><i class="fa fa-external-link-alt"></i></button>
                <button class="btn btn-icon" title="More options" id="btn-more"><i class="fa fa-ellipsis-v"></i></button>
              <!--  <button class="btn btn-success btn-save" id="btn-save">Save As</button> -->
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
                        <button onclick="exportDataTable('profit-loss-table', '{{ __('Profit & Loss by Month') }}')"
                            class="btn btn-success mx-auto w-75 justify-content-center text-center"
                            data-action="excel">Export to
                            Excel</button>
                    </div>
                    <div class="col-md-6">
                        <button onclick="exportDataTable('profit-loss-table', '{{ __('Profit & Loss by Month') }}', 'pdf')"
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
    <div class="filter-controls">
        <div class="filter-row">
            <div class="filter-group row mb-2">
                <div class="filter-item col-md-3">
                    <label class="filter-label">From Date</label>
                    <input type="date" id="filter-start-date" class="form-control"
                        value="{{ request('startDate', Carbon\Carbon::now()->startOfYear()->format('Y-m-d')) }}">
                </div>

                <div class="filter-item col-md-3">
                    <label class="filter-label">To Date</label>
                    <input type="date" id="filter-end-date" class="form-control"
                        value="{{ request('endDate', Carbon\Carbon::now()->format('Y-m-d')) }}">
                </div>

              <!--  <div class="filter-item col-md-3">
                    <label class="filter-label">Accounting method</label>
                    <select id="accounting-method" class="form-control">
                        <option value="accrual" {{ request('accountingMethod', 'accrual') == 'accrual' ? 'selected' : '' }}>
                            Accrual</option>
                        <option value="cash" {{ request('accountingMethod') == 'cash' ? 'selected' : '' }}>Cash</option>
                    </select>
                </div>  

                <div class="filter-item col-md-3 pt-4">
                    <div class="view-options" style="position: relative;">
                        <div class="filter-item col-md-2">
                            <button class="btn btn-view-options" id="view-options-btn"
                                style="border: none !important; border-left: 1px solid #d1d5db !important; border-radius: 0px !important; ">
                                <i class="fa fa-eye"></i> View options
                            </button>
                        </div> 
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
                </div> -->
            </div>
        </div>
    </div>

    <div class="content-wrapper">
        <div class="d-flex flex-column w-tables rounded mt-3 bg-white">
            <div class="report-title-section p-2">
                <h2 class="report-title">Profit & Loss by Month</h2>
                <p class="date-range">
                    <span id="date-range-display">
                        @php
                            $displayStart = request(
                                'startDate',
                                Carbon\Carbon::now()->startOfYear()->format('Y-m-d'),
                            );
                            $displayEnd = request('endDate', Carbon\Carbon::now()->format('Y-m-d'));
                            echo Carbon\Carbon::parse($displayStart)->format('F j, Y') .
                                ' - ' .
                                Carbon\Carbon::parse($displayEnd)->format('F j, Y');
                        @endphp
                    </span>
                </p>
            </div>
            <div class="table-responsive p-3" id="report-content">
                {!! $dataTable->table(['class' => 'table table-hover border-0 w-100 profit-loss-table']) !!}
            </div>
        </div>
    </div>
@endsection

@push('script-page')
    @include('sections.datatable_js')
    <script>
        $(document).ready(function() {
            // Initialize view state
            window.viewState = {
                viewType: 'normal',
                expandState: 'expand'
            };

            initializeViewOptions();
            setupEventListeners();

            // Initialize table state after DataTable loads
            setTimeout(function() {
                initializeTableState();
                applyViewState();
            }, 500);
        });

        function initializeViewOptions() {
            updateViewCheckmarks();
        }

        function setupEventListeners() {
            // Date filter changes
            $('#filter-start-date, #filter-end-date, #accounting-method').on('change', function() {
                const startDate = $('#filter-start-date').val();
                const endDate = $('#filter-end-date').val();
                const accountingMethod = $('#accounting-method').val();

                if (startDate && endDate) {
                    if (new Date(startDate) > new Date(endDate)) {
                        alert('Start date must be before end date');
                        return;
                    }

                    const url = new URL(window.location.href);
                    url.searchParams.set('startDate', startDate);
                    url.searchParams.set('endDate', endDate);
                    url.searchParams.set('accountingMethod', accountingMethod);
                    window.location.href = url.toString();
                }
            });

            // View options dropdown
            $('#view-options-btn').on('click', function(e) {
                e.stopPropagation();
                $('#view-options-dropdown').toggle();
            });

            // Close dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.view-options').length) {
                    $('#view-options-dropdown').hide();
                }
            });

            // View option selection
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

            // Section toggle click
            $(document).on('click', '.toggle-section', handleSectionToggle);

            // DataTable events
            $('#profit-loss-table').on('draw.dt', function() {
                setTimeout(function() {
                    initializeTableState();
                    applyViewState();
                }, 100);
            });
        }

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

        function applyViewState() {
            const $reportContent = $('#report-content');

            // Remove compact view class
            $reportContent.removeClass('compact-view');

            // Apply view type
            if (window.viewState.viewType === 'compact') {
                $reportContent.addClass('compact-view');
            }

            // Apply expand/collapse state
            if (window.viewState.expandState === 'expand') {
                handleExpandAll();
            } else {
                handleCollapseAll();
            }
        }

        function updateViewCheckmarks() {
            $('.view-option-item').removeClass('active');

            // Mark view type as active
            $('.view-option-item[data-value="' + window.viewState.viewType + '"]').addClass('active');

            // Mark expand state as active
            $('.view-option-item[data-value="' + window.viewState.expandState + '"]').addClass('active');
        }

        function handleExpandAll() {
            $('.child-row, .subtotal-row').show();
            $('.toggle-chevron').removeClass('fa-caret-right').addClass('fa-caret-down');

            // Hide section totals when expanded
            $('.section-month-total, .section-total-amount').hide();
        }

        function handleCollapseAll() {
            $('.child-row, .subtotal-row').hide();
            $('.toggle-chevron').removeClass('fa-caret-down').addClass('fa-caret-right');

            // Show section totals when collapsed
            $('.section-month-total, .section-total-amount').show();
        }

        function handleSectionToggle(e) {
            e.preventDefault();

            // Don't allow manual toggle in compact view
            if (window.viewState.viewType === 'compact') {
                return;
            }

            const $this = $(this);
            const group = $this.data('group');
            const $chevron = $this.find('.toggle-chevron');
            const $childRows = $('.group-' + group);

            // Get section totals for this group
            const $sectionMonthTotals = $('.section-month-total[data-group="' + group + '"]');
            const $sectionTotalAmount = $('.section-total-amount[data-group="' + group + '"]');

            if ($chevron.length === 0) return;

            if ($chevron.hasClass('fa-caret-down')) {
                // Collapse this section
                $childRows.hide();
                $chevron.removeClass('fa-caret-down').addClass('fa-caret-right');

                // Show section totals when collapsed
                $sectionMonthTotals.show();
                $sectionTotalAmount.show();
            } else {
                // Expand this section
                $childRows.show();
                $chevron.removeClass('fa-caret-right').addClass('fa-caret-down');

                // Hide section totals when expanded
                $sectionMonthTotals.hide();
                $sectionTotalAmount.hide();
            }
        }

        function initializeTableState() {
            // Show all rows by default
            $('.child-row, .subtotal-row').show();

            // Set all chevrons to expanded state
            $('.toggle-chevron').removeClass('fa-caret-right').addClass('fa-caret-down');

            // Add chevrons to sections that have children
            $('.toggle-section').each(function() {
                const group = $(this).data('group');
                const hasChildren = $('.group-' + group).length > 0;
                const $chevron = $(this).find('.toggle-chevron');

                if (hasChildren && $chevron.length === 0) {
                    $(this).prepend('<i class="fas fa-caret-down toggle-chevron mr-2"></i>');
                    $(this).css('cursor', 'pointer');
                } else if (!hasChildren) {
                    $chevron.remove();
                    $(this).css('cursor', 'default');
                }
            });

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
            // else {
            //     // Update existing footer
            //     $('.report-footer').css('text-align', options.footerAlignment);

            //     if (!options.datePrepared) {
            //         $('.report-footer div:contains("Date Prepared")').hide();
            //     } else {
            //         $('.report-footer div:contains("Date Prepared")').show();
            //     }

            //     if (!options.timePrepared) {
            //         $('.report-footer div:contains("Time Prepared")').hide();
            //     } else {
            //         $('.report-footer div:contains("Time Prepared")').show();
            //     }

            //     if (!options.reportBasis) {
            //         $('.report-footer div:contains("Report Basis")').hide();
            //     } else {
            //         $('.report-footer div:contains("Report Basis")').show();
            //     }
            // }
        }
    </script>
    {!! $dataTable->scripts() !!}
@endpush
