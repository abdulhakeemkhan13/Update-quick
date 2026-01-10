@extends('layouts.admin')
@section('page-title')
    {{ __('Sales Receipt') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('sales.reciepts.index') }}">{{ __('Sales Receipts') }}</a></li>

    <li class="breadcrumb-item">{{ __('Create Sales Receipt') }}</li>
@endsection

@push('css-page')
    <style>
        :root {
            --qbo-green: #2ca01c;
            --qbo-green-hover: #108000;
            --qbo-gray-text: #6b6c72;
            --qbo-border-color: #dcdcdc;
            --qbo-bg-color: #f4f5f8;
        }

        body {
            background-color: var(--qbo-bg-color);
            color: #393a3d;
            font-family: 'Avenir Next forINTUIT', 'Avenir Next', Futura, sans-serif;
        }

        .invoice-container {
            background: var(--qbo-bg-color);
            max-width: 100%;
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Fixed Top Header */
        .fixed-top-header {
            position: sticky;
            top: 0;
            background: #fff;
            border-bottom: 1px solid #f4f5f8;
            z-index: 1000;
            padding: 0;
        }

        .header-top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f4f5f8 !important;
            padding: 15px 30px;
        }

        .invoice-label {
            font-size: 24px;
            font-weight: 600;
            color: #393a3d;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .invoice-label svg {
            color: #393a3d;
        }

        .close-button {
            background: none;
            border: none;
            font-size: 24px;
            color: var(--qbo-gray-text);
            cursor: pointer;
            padding: 4px;
            line-height: 1;
        }

        /* Main Content Area */
        .main-content {
            padding: 20px 30px;
            flex: 1;
            background-color: #f5f5f5;
        }

        /* Top Customer Bar */
        .top-customer-bar {
            /* display: flex; */
            /* justify-content: space-between; */
            /* align-items: flex-start; */
            margin-bottom: 20px;
            /* gap: 20px; */
        }

        .customer-select-group {
            /* flex: 1;
                                                                max-width: 400px; */
        }

        .email-group {
            /* flex: 1;
                                                                max-width: 400px;
                                                                display: flex;
                                                                flex-direction: column; */
        }

        .email-input-row {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .amount-display {
            text-align: right;
        }

        .amount-label {
            font-size: 12px;
            color: var(--qbo-gray-text);
            text-transform: uppercase;
            font-weight: 600;
        }

        .amount-value {
            font-size: 36px;
            font-weight: 700;
            color: #393a3d;
        }

        /* QBO Cards */
        .qbo-card {
            background: #fff;
            border: 1px solid var(--qbo-border-color);
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .qbo-card-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            gap: 10px;
        }

        .qbo-section-title {
            font-size: 16px;
            font-weight: 600;
            color: #393a3d;
        }

        .new-badge {
            background: #d52b84;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 2px;
            text-transform: uppercase;
        }

        /* Grid Layout for Cards */
        .cards-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        /* Record Payment Section */
        .payment-type-group {
            margin-bottom: 20px;
        }

        .payment-option {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            cursor: pointer;
        }

        .payment-option input[type="radio"] {
            accent-color: var(--qbo-green);
            width: 18px;
            height: 18px;
        }

        .payment-option label {
            font-size: 14px;
            font-weight: 500;
            color: #393a3d;
            cursor: pointer;
        }

        .payment-option-desc {
            font-size: 13px;
            color: var(--qbo-gray-text);
            margin-left: 26px;
            margin-bottom: 12px;
        }

        .payment-icons {
            display: flex;
            gap: 5px;
            margin-left: 26px;
        }

        .payment-icon {
            height: 20px;
            border: 1px solid #ddd;
            border-radius: 2px;
        }

        .form-grid-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }

        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        /* Form Controls */
        .form-label {
            font-size: 13px;
            color: var(--qbo-gray-text);
            margin-bottom: 4px;
            font-weight: 400;
        }

        .form-control,
        .form-select {
            border: 1px solid #8d9096;
            border-radius: 2px;
            padding: 8px 10px;
            font-size: 14px;
            color: #393a3d;
            height: 36px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--qbo-green) !important;
            box-shadow: 0 0 0 2px rgba(44, 160, 28, 0.2) !important;
            outline: none !important;
        }

        /* Addresses Section */
        .address-box {
            height: 100px;
        }

        /* Product Table */
        .product-section {
            background: #fff;
            border-top: 1px solid var(--qbo-border-color);
            border-bottom: 1px solid var(--qbo-border-color);
            padding: 0;
            margin-bottom: 20px;
        }

        .product-table {
            width: 100%;
            border-collapse: collapse;
        }

        .product-table th {
            text-transform: uppercase;
            font-size: 11px;
            font-weight: 700;
            color: var(--qbo-gray-text);
            padding: 10px 15px;
            border-bottom: 1px solid var(--qbo-border-color);
            text-align: left;
        }

        .product-table td {
            padding: 10px 15px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: top;
        }

        .product-table tr:hover td {
            background-color: #f9f9fa;
        }

        .product-table tr:hover .delete-icon {
            opacity: 1;
        }

        .delete-icon {
            opacity: 1;
            cursor: pointer;
            color: var(--qbo-gray-text);
            transition: opacity 0.2s;
        }

        .delete-icon:hover {
            color: #393a3d;
        }

        .table-actions {
            padding: 10px 15px;
        }

        .btn-outline {
            background: #fff;
            border: 1px solid #8d9096;
            color: #393a3d;
            padding: 6px 12px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
        }

        .btn-outline:hover {
            background-color: #f4f5f8;
        }

        /* Footer Section */
        .footer-section {
            background: #fff;
            border-top: 1px solid var(--qbo-border-color);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            bottom: 0;
            z-index: 100;
        }

        .footer-center {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .footer-link {
            color: #393a3d;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .footer-link:hover {
            color: var(--qbo-green);
        }

        .btn-qbo-primary {
            background-color: var(--qbo-green);
            color: #fff;
            border: 1px solid var(--qbo-green);
            padding: 8px 24px;
            border-radius: 18px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
        }

        .btn-qbo-primary:hover {
            background-color: var(--qbo-green-hover);
        }

        .btn-qbo-secondary {
            background-color: #fff;
            color: #393a3d;
            border: 1px solid #8d9096;
            padding: 8px 24px;
            border-radius: 18px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
        }

        .btn-qbo-secondary:hover {
            background-color: #f4f5f8;
            border-color: #393a3d;
        }

        .btn-group-qbo {
            display: flex;
        }

        .btn-group-qbo .btn-main {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
            border-right: 1px solid rgba(255, 255, 255, 0.3);
        }

        .btn-group-qbo .btn-arrow {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            padding-left: 10px;
            padding-right: 10px;
        }

        /* Totals Section */
        .totals-section {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding-top: 24px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 0;
            font-size: 14px;
        }

        .total-row span:last-child {
            min-width: 80px;
            text-align: right;
        }

        .total-row.subtotal {
            color: #393a3d;
            padding-bottom: 8px;
        }

        .total-row.final {
            font-size: 16px;
            font-weight: 600;
            color: #393a3d;
            padding-top: 12px;
            border-top: 2px solid #e4e4e7;
        }

        .total-row.total-big {
            font-size: 16px;
            font-weight: 700;
            border-top: 2px solid #e4e4e7;
            padding-top: 12px;
            margin-top: 10px;
        }

        /* Discount row: button on the LEFT of "Discount" */
        .total-row.discount-row {
            align-items: center;
        }

        .discount-label-wrapper {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-left: -32px;
        }

        /* QBO-style rotate icon button */
        .discount-position-btn {
            border: none;
            background: #ffffff;
            padding: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #6b6c72;
            transition: background 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        }

        .discount-position-btn:hover {
            background: #f4f5f8;
            border-radius: 4px;
        }

        /* Tax rate select row */
        .select-tax-row span:first-child {
            font-size: 13px;
            color: #393a3d;
            margin-right: 8px;
        }

        .select-tax-row select {
            width: 190px;
            font-size: 13px;
        }

        /* "See the math" under sales tax, aligned right */
        .see-math-link {
            align-self: flex-end;
            margin-top: -4px;
        }

        /* "Edit totals" under invoice total, aligned right */
        .edit-totals-link {
            align-self: flex-end;
            margin-top: 4px;
        }

        .link-button {
            color: #0077c5;
            text-decoration: none;
            font-size: 13px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px 0;
        }

        .link-button:hover {
            text-decoration: underline;
        }

        .helper-link {
            color: #0077c5;
            text-decoration: none;
            font-size: 13px;
        }

        /* Attachment */
        .attachment-box {
            border: 1px solid #dcdcdc;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            background: #fff;
        }

        .attachment-box a {
            color: #0077c5;
            text-decoration: none;
            font-weight: 500;
        }
    </style>
    <style>
        /* font color of this whole page as #d4d7dc */
        body {
            color: #d4d7dc !important;
        }

        /* Product Table */
        .product-section {
            padding: 24px 32px;
            background: #fff;
        }

        .section-heading {
            font-size: 15px;
            font-weight: 600;
            color: #393a3d;
            margin-bottom: 16px;
        }

        /* === SINGLE SOURCE OF TRUTH FOR TABLE LAYOUT === */
        .invoice-card .product-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-top: 1px solid #e4e4e7;
            border-bottom: 1px solid #e4e4e7;
        }

        /* headers */
        .invoice-card .product-table thead th {
            padding: 12px 8px;
            font-size: 13px;
            font-weight: 600;
            color: #393a3d;
            background: #fff;
            border-bottom: 1px solid #e4e4e7;
            /* solid horizontal */
        }

        /* body cells */
        .invoice-card .product-table tbody td {
            padding: 12px 8px;
            font-size: 13px;
            vertical-align: middle;
            border-bottom: 1px solid #e4e4e7;
            /* solid horizontal */
        }

        /* dotted vertical lines between columns */
        .invoice-card .product-table thead th+th,
        .invoice-card .product-table tbody td+td {
            border-left: 1px dotted #e4e4e7;
        }

        /* no outer left border on first column */
        .invoice-card .product-table thead th:first-child,
        .invoice-card .product-table tbody td:first-child {
            border-left: none;
        }

        /* === COLUMN WIDTHS & ALIGNMENT === */

        /* col 1: + menu */
        .invoice-card .product-table thead th:nth-child(1),
        .invoice-card .product-table tbody td:nth-child(1) {
            width: 26px;
            text-align: center;
        }

        /* col 2: drag handle */
        .invoice-card .product-table thead th:nth-child(2),
        .invoice-card .product-table tbody td:nth-child(2) {
            width: 26px;
            text-align: center;
        }

        /* col 3: line # */
        .invoice-card .product-table thead th:nth-child(3),
        .invoice-card .product-table tbody td:nth-child(3) {
            width: 30px;
            text-align: center;
        }

        /* col 5: Description (wide) */
        .invoice-card .product-table thead th:nth-child(5),
        .invoice-card .product-table tbody td:nth-child(5) {
            width: 30%;
        }

        /* cols 6–8: Qty, Rate, Amount – same width, right aligned */
        .invoice-card .product-table thead th:nth-child(6),
        .invoice-card .product-table thead th:nth-child(7),
        .invoice-card .product-table thead th:nth-child(8),
        .invoice-card .product-table tbody td:nth-child(6),
        .invoice-card .product-table tbody td:nth-child(7),
        .invoice-card .product-table tbody td:nth-child(8) {
            width: 7%;
            max-width: 80px;
            text-align: right;
        }

        .invoice-card .product-table tbody td:nth-child(6) .form-control,
        .invoice-card .product-table tbody td:nth-child(7) .form-control,
        .invoice-card .product-table tbody td:nth-child(8) .form-control {
            width: 100%;
            text-align: right;
        }

        /* col 9: Tax – small, centered */
        .invoice-card .product-table thead th:nth-child(9),
        .invoice-card .product-table tbody td:nth-child(9) {
            width: 40px;
            text-align: center;
        }

        /* col 10: Delete – small, centered */
        .invoice-card .product-table thead th:nth-child(10),
        .invoice-card .product-table tbody td:nth-child(10) {
            width: 40px;
            text-align: center;
            padding-left: 0;
            padding-right: 0;
        }

        /* center the trash icon itself */
        .invoice-card .product-table tbody td:nth-child(10) .delete-icon {
            display: inline-block;
        }

        /* drag-handle look */
        .drag-handle {
            cursor: grab;
            color: #c4c4c4;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .drag-handle:active {
            cursor: grabbing;
        }
    </style>
    <style>
        /* Product Table */
        .product-section {
            padding: 24px 32px;
            background: #fff;
        }

        .section-heading {
            font-size: 15px;
            font-weight: 600;
            color: #393a3d;
            margin-bottom: 16px;
        }

        /* === SINGLE SOURCE OF TRUTH FOR TABLE LAYOUT === */
        .invoice-card .product-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-top: 1px solid #e4e4e7;
            border-bottom: 1px solid #e4e4e7;
        }

        /* headers */
        .invoice-card .product-table thead th {
            padding: 12px 8px;
            font-size: 13px;
            font-weight: 600;
            color: #393a3d;
            background: #fff;
            border-bottom: 1px solid #e4e4e7;
            /* solid horizontal */
        }

        /* body cells */
        .invoice-card .product-table tbody td {
            padding: 12px 8px;
            font-size: 13px;
            vertical-align: middle;
            border-bottom: 1px solid #e4e4e7;
            /* solid horizontal */
        }

        /* dotted vertical lines between columns */
        .invoice-card .product-table thead th+th,
        .invoice-card .product-table tbody td+td {
            border-left: 1px dotted #e4e4e7;
        }

        /* no outer left border on first column */
        .invoice-card .product-table thead th:first-child,
        .invoice-card .product-table tbody td:first-child {
            border-left: none;
        }

        /* === COLUMN WIDTHS & ALIGNMENT === */

        /* col 1: + menu */
        .invoice-card .product-table thead th:nth-child(1),
        .invoice-card .product-table tbody td:nth-child(1) {
            width: 26px;
            text-align: center;
        }

        /* col 2: drag handle */
        .invoice-card .product-table thead th:nth-child(2),
        .invoice-card .product-table tbody td:nth-child(2) {
            width: 26px;
            text-align: center;
        }

        /* col 3: line # */
        .invoice-card .product-table thead th:nth-child(3),
        .invoice-card .product-table tbody td:nth-child(3) {
            width: 30px;
            text-align: center;
        }

        /* col 5: Description (wide) */
        .invoice-card .product-table thead th:nth-child(5),
        .invoice-card .product-table tbody td:nth-child(5) {
            width: 30%;
        }

        /* cols 6–8: Qty, Rate, Amount – same width, right aligned */
        .invoice-card .product-table thead th:nth-child(6),
        .invoice-card .product-table thead th:nth-child(7),
        .invoice-card .product-table thead th:nth-child(8),
        .invoice-card .product-table tbody td:nth-child(6),
        .invoice-card .product-table tbody td:nth-child(7),
        .invoice-card .product-table tbody td:nth-child(8) {
            width: 7%;
            max-width: 80px;
            text-align: right;
        }

        .invoice-card .product-table tbody td:nth-child(6) .form-control,
        .invoice-card .product-table tbody td:nth-child(7) .form-control,
        .invoice-card .product-table tbody td:nth-child(8) .form-control {
            width: 100%;
            text-align: right;
        }

        /* col 9: Tax – small, centered */
        .invoice-card .product-table thead th:nth-child(9),
        .invoice-card .product-table tbody td:nth-child(9) {
            width: 40px;
            text-align: center;
        }

        /* col 10: Delete – small, centered */
        .invoice-card .product-table thead th:nth-child(10),
        .invoice-card .product-table tbody td:nth-child(10) {
            width: 40px;
            text-align: center;
            padding-left: 0;
            padding-right: 0;
        }

        /* center the trash icon itself */
        .invoice-card .product-table tbody td:nth-child(10) .delete-icon {
            display: inline-block;
        }

        /* drag-handle look */
        .drag-handle {
            cursor: grab;
            color: #c4c4c4;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .drag-handle:active {
            cursor: grabbing;
        }
    </style>
    <style>
        /* Product Table */
        .product-section {
            padding: 24px 32px;
            background: #fff;
        }

        .section-heading {
            font-size: 15px;
            font-weight: 600;
            color: #393a3d;
            margin-bottom: 16px;
        }

        /* === SINGLE SOURCE OF TRUTH FOR TABLE LAYOUT === */
        .invoice-card .product-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-top: 1px solid #e4e4e7;
            border-bottom: 1px solid #e4e4e7;
        }

        /* headers */
        .invoice-card .product-table thead th {
            padding: 12px 8px;
            font-size: 13px;
            font-weight: 600;
            color: #393a3d;
            background: #fff;
            border-bottom: 1px solid #e4e4e7;
            /* solid horizontal */
        }

        /* body cells */
        .invoice-card .product-table tbody td {
            padding: 12px 8px;
            font-size: 13px;
            vertical-align: middle;
            border-bottom: 1px solid #e4e4e7;
            /* solid horizontal */
        }

        /* dotted vertical lines between columns */
        .invoice-card .product-table thead th+th,
        .invoice-card .product-table tbody td+td {
            border-left: 1px dotted #e4e4e7;
        }

        /* no outer left border on first column */
        .invoice-card .product-table thead th:first-child,
        .invoice-card .product-table tbody td:first-child {
            border-left: none;
        }

        /* === COLUMN WIDTHS & ALIGNMENT === */

        /* col 1: + menu */
        .invoice-card .product-table thead th:nth-child(1),
        .invoice-card .product-table tbody td:nth-child(1) {
            width: 26px;
            text-align: center;
        }

        /* col 2: drag handle */
        .invoice-card .product-table thead th:nth-child(2),
        .invoice-card .product-table tbody td:nth-child(2) {
            width: 26px;
            text-align: center;
        }

        /* col 3: line # */
        .invoice-card .product-table thead th:nth-child(3),
        .invoice-card .product-table tbody td:nth-child(3) {
            width: 30px;
            text-align: center;
        }

        /* col 5: Description (wide) */
        .invoice-card .product-table thead th:nth-child(5),
        .invoice-card .product-table tbody td:nth-child(5) {
            width: 30%;
        }

        /* cols 6–8: Qty, Rate, Amount – same width, right aligned */
        .invoice-card .product-table thead th:nth-child(6),
        .invoice-card .product-table thead th:nth-child(7),
        .invoice-card .product-table thead th:nth-child(8),
        .invoice-card .product-table tbody td:nth-child(6),
        .invoice-card .product-table tbody td:nth-child(7),
        .invoice-card .product-table tbody td:nth-child(8) {
            width: 7%;
            max-width: 80px;
            text-align: right;
        }

        .invoice-card .product-table tbody td:nth-child(6) .form-control,
        .invoice-card .product-table tbody td:nth-child(7) .form-control,
        .invoice-card .product-table tbody td:nth-child(8) .form-control {
            width: 100%;
            text-align: right;
        }

        /* col 9: Tax – small, centered */
        .invoice-card .product-table thead th:nth-child(9),
        .invoice-card .product-table tbody td:nth-child(9) {
            width: 40px;
            text-align: center;
        }

        /* col 10: Delete – small, centered */
        .invoice-card .product-table thead th:nth-child(10),
        .invoice-card .product-table tbody td:nth-child(10) {
            width: 40px;
            text-align: center;
            padding-left: 0;
            padding-right: 0;
        }

        /* center the trash icon itself */
        .invoice-card .product-table tbody td:nth-child(10) .delete-icon {
            display: inline-block;
        }

        /* drag-handle look */
        .drag-handle {
            cursor: grab;
            color: #c4c4c4;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .drag-handle:active {
            cursor: grabbing;
        }

        /* “Print or download” link in center, like QBO */
        .footer-link {
            background: none;
            border: none;
            color: #2ca01c;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            padding: 0;
        }

        /* Buttons (keep your pill style for real buttons) */
        .btn {
            padding: 10px 24px;
            border-radius: 4px;
            /* Changed from 20px to 4px for QBO style */
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-secondary {
            background: #fff;
            color: #393a3d;
            border-color: #c4c4c4;
        }

        .btn-secondary:hover {
            background: rgba(0, 137, 46, 0.2);
            border-color: rgba(0, 137, 46, 0.2);
        }

        .btn-primary {
            background: #00892E;
            color: #fff;
            border-color: #00892E;
        }

        .btn-primary:hover {
            background: #108000;
            border-color: #108000;
        }

        /* Split Button Specifics (unchanged, keeps your shape) */
        .btn-group {
            position: relative;
            display: inline-flex;
            vertical-align: middle;
        }

        .btn-group .btn {
            border-radius: 4px 0 0 4px;
            /* Changed from 20px */
        }

        .btn-group .btn+.dropdown-toggle-split {
            border-radius: 0 4px 4px 0;
            /* Changed from 20px */
            padding-left: 10px;
            padding-right: 10px;
            border-left: 1px solid rgba(255, 255, 255, 0.3);
            margin-left: -1px;
        }

        .btn-group .btn-secondary+.dropdown-toggle-split {
            border-left: 1px solid #c4c4c4;
        }

        .attachment-zone {
            border: 2px dashed #c4c4c4;
            border-radius: 4px;
            padding: 32px;
            text-align: center;
            background: #fafafa;
            cursor: pointer;
            transition: all 0.2s;
        }

        .attachment-zone:hover {
            border-color: #2ca01c;
            background: #f7f8fa;
        }

        .attachment-link {
            color: #0077c5;
            font-size: 14px;
            text-decoration: none;
            font-weight: 500;
        }

        .attachment-limit {
            color: #6b6c72;
            font-size: 12px;
            margin-top: 8px;
        }

        /* Attachments wrapper + header */
        .attachments-wrapper {
            margin-top: 8px;
        }

        #attachments-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 13px;
            color: #393a3d;
        }

        /* Left side of header: "Attachments" + "Select All" */
        .attachments-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .attachments-header-right {
            font-size: 12px;
            color: #6b6c72;
        }

        /* Individual attachment rows */
        .attachment-row {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            border: 1px solid #dcdcdc;
            border-radius: 4px;
            background: #fff;
            margin-bottom: 6px;
        }

        .attachment-row .form-check {
            margin: 0;
        }

        .attachment-name {
            flex: 1;
            font-size: 13px;
            color: #393a3d;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .attachment-size {
            font-size: 12px;
            color: #6b6c72;
        }

        .attachment-remove {
            border: none;
            background: none;
            color: #6b6c72;
            font-size: 18px;
            cursor: pointer;
            padding: 0 4px;
        }

        .attachment-remove:hover {
            color: #393a3d;
        }

        /* You already have these, keep them – they define the drop zone */
        .attachment-zone {
            border: 2px dashed #c4c4c4;
            border-radius: 4px;
            padding: 32px;
            text-align: center;
            background: #fafafa;
            cursor: pointer;
            transition: all 0.2s;
        }

        .attachment-zone:hover {
            border-color: #2ca01c;
            background: #f7f8fa;
        }

        .attachment-link {
            color: #0077c5;
            font-size: 14px;
            text-decoration: none;
            font-weight: 500;
        }

        .attachment-limit {
            color: #6b6c72;
            font-size: 12px;
            margin-top: 8px;
        }
    </style>
@endpush

@push('script-page')
    <script src="{{ asset('js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('js/jquery.repeater.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            var invoiceModal = new bootstrap.Modal(document.getElementById('invoice-modal'), {
                backdrop: 'static',
                keyboard: false
            });
            invoiceModal.show();


            // Check if we're in edit mode and populate form data
            @if (isset($salesReceiptData) && $salesReceiptData)
                console.log('Edit mode detected, calling populateEditForm');
                populateEditForm(@json($salesReceiptData));
            @else
                console.log('Create mode - no salesReceiptData found');
            @endif
        });

        // Function to populate form with existing sales receipt data
        function populateEditForm(salesReceiptData) {
            console.log('populateEditForm called with data:', salesReceiptData);

            // Populate basic form fields
            $('#customer').val(salesReceiptData.customer_id).trigger('change');
            $('#customer_email').val(salesReceiptData.customer_email || '');
            $('#issue_date').val(salesReceiptData.issue_date);
            $('#ref_number').val(salesReceiptData.ref_number || '');
            $('#location_of_sale').val(salesReceiptData.location_of_sale || '');
            $('textarea[name="bill_to"]').val(salesReceiptData.bill_to || '');
            $('textarea[name="memo"]').val(salesReceiptData.memo || '');
            $('textarea[name="note"]').val(salesReceiptData.note || '');

            // Populate payment fields
            if (salesReceiptData.payment_type) {
                $('input[name="payment_type"][value="' + salesReceiptData.payment_type + '"]').prop('checked', true);
                if (salesReceiptData.payment_type === 'record_payment') {
                    $('#record-payment-fields').show();
                    $('#charge-payment-card').hide();
                } else {
                    $('#record-payment-fields').hide();
                    $('#charge-payment-card').show();
                }
            }
            $('#payment_method').val(salesReceiptData.payment_method || '');
            $('#deposit_to').val(salesReceiptData.deposit_to || '');

            // Populate discount fields
            $('.discount-type-select').val(salesReceiptData.discount_type || 'percent');
            $('.discount-input').val(salesReceiptData.discount_value || 0);

            // Populate tax rate
            $('select[name="sales_tax_rate"]').val(salesReceiptData.sales_tax_rate || '');

            // Update form action for edit
            $('#invoice-form').attr('action', '{{ route('sales-receipt.update', ':id') }}'.replace(':id', salesReceiptData
                .id));
            // Add method spoofing for PUT request
            if (!$('#invoice-form input[name="_method"]').length) {
                $('#invoice-form').append('<input type="hidden" name="_method" value="PUT">');
            }

            // Update breadcrumb
            $('.breadcrumb-item.active').text('{{ __('Edit Sales Receipt') }}');

            // Populate product lines
            if (salesReceiptData.items && salesReceiptData.items.length > 0) {
                console.log('Setting repeater data with items:', salesReceiptData.items);
                // Set the repeater data
                $('#sortable-table').data('repeater-list', salesReceiptData.items);

                // Trigger recalculation after a short delay to ensure DOM is ready
                setTimeout(function() {
                    recalcTotals();
                    renumberInvoiceLines();
                }, 500);
            } else {
                console.log('No items found in salesReceiptData');
            }
        }

        // NEW: helper to renumber the "#" column after add / delete / drag
        function renumberInvoiceLines() {
            $('#sortable-table').find('tbody').each(function(index) {
                $(this).find('.line-number').text(index + 1);
            });
        }
    </script>
    <script>
        var selector = "body";
        if ($(selector + " .repeater").length) {

            // UPDATED: sortable is applied to the repeater LIST (which contains all tbodys),
            // not to each tbody individually
            var $dragAndDrop = $("body .repeater [data-repeater-list]").sortable({
                handle: '.sort-handler',
                items: 'tbody',
                axis: 'y',
                stop: function() {
                    // after drag finishes, just renumber the visible line numbers
                    renumberInvoiceLines();
                }
            });

            var $repeater = $(selector + ' .repeater').repeater({
                initEmpty: false,
                defaultValues: {
                    'status': 1
                },
                show: function() {
                    $(this).slideDown();
                    var file_uploads = $(this).find('input.multi');
                    if (file_uploads.length) {
                        $(this).find('input.multi').MultiFile({
                            max: 3,
                            accept: 'png|jpg|jpeg',
                            max_size: 2048
                        });
                    }
                    if ($('.select2').length) {
                        $('.select2').select2();
                    }


                    // Initialize new row with default values
                    var $newRow = $(this).find('tr.product-row');
                    $newRow.find('.quantity').val('1');
                    $newRow.find('.price').val('0.00');
                    $newRow.find('.discount').val('0.00');
                    $newRow.find('.amount').html('0.00');
                    $newRow.find('.itemTaxPrice').val('0.00');
                    $newRow.find('.itemTaxRate').val('0.00');
                    $newRow.find('.form-check-input[type="checkbox"]').prop('checked', false);

                    // NEW: renumber lines whenever a new row is added
                    renumberInvoiceLines();

                    // Recalculate totals after adding new row
                    recalcTotals();
                },
                hide: function(deleteElement) {
                    if (confirm('Are you sure you want to delete this element?')) {
                        $(this).slideUp(deleteElement);
                        $(this).remove();


                        // Recalculate totals after deletion
                        recalcTotals();
                        renumberInvoiceLines();
                    }
                },
                ready: function(setIndexes) {
                    // UPDATED: jQuery UI sortable uses "sortstop" instead of "drop"
                    $dragAndDrop.on('sortstop', function() {
                        setIndexes();
                        renumberInvoiceLines();
                    });

                    // Handle populating existing data for edit mode
                    @if (isset($salesReceiptData) && $salesReceiptData && isset($salesReceiptData['items']))
                        // Use sequential approach like invoice edit modal
                        setTimeout(function() {
                            var existingItems = @json($salesReceiptData['items']);
                            console.log('Processing existing items sequentially:', existingItems);

                            if (existingItems && existingItems.length > 0) {
                                console.log('Found', existingItems.length,
                                    'existing items to populate');

                                // Remove default empty row first
                                $('#sortable-table tbody').remove();
                                console.log('Removed default empty row');

                                var currentIndex = 0;

                                // Function to add one item at a time
                                function addNextItem() {
                                    if (currentIndex >= existingItems.length) {
                                        // All items loaded - now renumber and recalculate
                                        console.log(
                                            'All items loaded. Renumbering and calculating totals...'
                                        );
                                        setTimeout(function() {
                                            renumberInvoiceLines();
                                            recalcTotals();
                                            $('#customer_id').trigger('change');
                                            console.log('Auto-population complete!');
                                        }, 300);
                                        return;
                                    }

                                    var item = existingItems[currentIndex];
                                    console.log('Loading item', currentIndex + 1, ':', item.type,
                                        'taxable:', item.taxable);

                                    if (item.type === 'product') {
                                        // Create product row manually to avoid repeater conflicts
                                        var $tbody = $('<tbody data-repeater-item></tbody>');
                                        var rowHtml = `
                                            <tr class="product-row">
                                                <td>
                                                    <div class="drag-handle sort-handler"><i class="ti ti-grid-dots"></i></div>
                                                </td>
                                                <td><span class="line-number">1</span></td>
                                                <td>
                                                    <select class="form-select item" data-url="{{ route('invoice.product') }}" required="required">
                                                        <option value="">--</option>
                                                        @foreach ($product_services as $key => $product)
                                                            <option value="{{ $key }}" {{ isset($item) && $item == $key ? 'selected' : '' }}>{{ $product }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <textarea class="form-control pro_description" rows="1" placeholder=""></textarea>
                                                </td>
                                                <td>
                                                    <input type="text" name="quantity" class="form-control input-right quantity" required="required">
                                                </td>
                                                <td>
                                                    <input type="text" name="price" class="form-control input-right price" required="required">
                                                </td>
                                                <td class="text-end">
                                                    <input type="text" name="amount" class="form-control input-right amount" value="0.00" readonly>
                                                </td>
                                                <td>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" value="">
                                                        <input type="hidden" name="tax" class="form-control tax">
                                                        <input type="hidden" name="itemTaxPrice" class="form-control itemTaxPrice">
                                                        <input type="hidden" name="itemTaxRate" class="form-control itemTaxRate">
                                                        <input type="hidden" name="discount" class="form-control discount">
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="delete-icon" data-repeater-delete>
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                                            <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                                        </svg>
                                                    </span>
                                                </td>
                                            </tr>
                                        `;
                                        $tbody.html(rowHtml);
                                        $('#sortable-table').append($tbody);

                                        // Now populate the fields
                                        var $row = $tbody.find('tr.product-row');

                                        // Populate product fields
                                        if (item.item) {
                                            $row.find('select.item').val(item.item);
                                        }
                                        if (item.description) {
                                            $row.find('textarea.pro_description').val(item.description);
                                        }
                                        if (item.quantity) {
                                            $row.find('input.quantity').val(item.quantity);
                                        }
                                        if (item.price) {
                                            $row.find('input.price').val(item.price);
                                        }
                                        if (item.amount) {
                                            $row.find('input.amount').val(item.amount);
                                        }
                                        // Check taxable checkbox - handle both boolean and string values
                                        if (item.taxable === 1 || item.taxable === '1' || item
                                            .taxable === true) {
                                            $row.find('.form-check-input[type="checkbox"]').prop(
                                                'checked', true);
                                            console.log('Setting taxable checkbox to checked for item:',
                                                item.description);
                                        } else {
                                            $row.find('.form-check-input[type="checkbox"]').prop(
                                                'checked', false);
                                            console.log(
                                                'Setting taxable checkbox to unchecked for item:',
                                                item.description);
                                        }
                                        if (item.tax) {
                                            $row.find('input.tax').val(item.tax);
                                        }
                                        if (item.itemTaxRate) {
                                            $row.find('input.itemTaxRate').val(item.itemTaxRate);
                                        }
                                        if (item.itemTaxPrice) {
                                            $row.find('input.itemTaxPrice').val(item.itemTaxPrice);
                                        }
                                        if (item.discount) {
                                            $row.find('input.discount').val(item.discount);
                                        }

                                        // Add hidden ID for update
                                        if (item.id) {
                                            $tbody.append(
                                                '<input type="hidden" name="item_ids[]" value="' +
                                                item.id + '">');
                                        }

                                        console.log('Product row populated:', item.description);
                                        currentIndex++;
                                        addNextItem(); // Add next item

                                    } else if (item.type === 'subtotal') {
                                        // Add subtotal row
                                        var $subtotalBody = window.createSubtotalBody(item.amount ||
                                            '0.00');
                                        $('#sortable-table').append($subtotalBody);
                                        console.log('Subtotal row added:', item.amount);

                                        currentIndex++;
                                        setTimeout(addNextItem, 50); // Small delay before next item

                                    } else if (item.type === 'text') {
                                        // Add text row
                                        var $textBody = window.createTextBody(item.description || '');
                                        $('#sortable-table').append($textBody);
                                        console.log('Text row added:', item.description);

                                        currentIndex++;
                                        setTimeout(addNextItem, 50); // Small delay before next item
                                    } else {
                                        // Unknown type, skip
                                        currentIndex++;
                                        addNextItem();
                                    }
                                }

                                // Start adding items
                                addNextItem();

                            } else {
                                console.log('No existing items to process');
                            }
                        }, 200);
                    @endif
                },
                isFirstItemUndeletable: true
            });

            // Check for existing data (either from data-value attribute or salesReceiptData)
            var value = $(selector + " .repeater").attr('data-value');
            console.log('Initial repeater data-value:', value);
            @if (isset($salesReceiptData) && $salesReceiptData && isset($salesReceiptData['items']))
                // For edit mode, use the salesReceiptData items
                value = @json($salesReceiptData['items']);
                console.log('Overriding with salesReceiptData items:', value);
            @endif

            if (typeof value != 'undefined' && value.length != 0) {
                if (typeof value === 'string') {
                    value = JSON.parse(value);
                }
                console.log('Setting repeater list with:', value);
                console.log('Data has', value.length, 'items');
                $repeater.setList(value);
                console.log('After setList, tbody elements:', $('#sortable-table tbody').length);
            } else {
                console.log('No data to set in repeater');
            }

        }

        $(document).on('change', '#customer', function() {
            var id = $(this).val();
            var url = $(this).data('url');

            // Clear fields and hide bill-to section if no customer selected
            if (!id || id === '' || id === '__add__') {
                $('#customer_email').val('');
                $('textarea[name="bill_to"]').val('');
                $('#bill-to-section').hide();
                return;
            }

            $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': jQuery('#token').val()
                },
                data: {
                    'id': id
                },
                cache: false,
                success: function(data) {
                    // Check if data is JSON object or HTML string
                    if (typeof data === 'object') {
                        // If it's a JSON object with customer data
                        if (data.customer) {
                            // Populate customer email
                            if (data.customer.email) {
                                $('#customer_email').val(data.customer.email);
                            }

                            // Populate billing address
                            var billingAddress = '';
                            if (data.customer.billing_name) {
                                billingAddress += data.customer.billing_name + '\n';
                            }
                            if (data.customer.billing_address) {
                                billingAddress += data.customer.billing_address + '\n';
                            }
                            if (data.customer.billing_city) {
                                billingAddress += data.customer.billing_city;
                                if (data.customer.billing_state) {
                                    billingAddress += ', ' + data.customer.billing_state;
                                }
                                if (data.customer.billing_zip) {
                                    billingAddress += ' ' + data.customer.billing_zip;
                                }
                                billingAddress += '\n';
                            }
                            if (data.customer.billing_country) {
                                billingAddress += data.customer.billing_country;
                            }

                            $('textarea[name="bill_to"]').val(billingAddress.trim());

                            // Show the bill-to section
                            $('#bill-to-section').show();
                        }
                    } else if (data != '') {
                        // Legacy: If it returns HTML, display it in customer_detail
                        $('#customer_detail').removeClass('d-none');
                        $('#customer_detail').addClass('d-block');
                        $('#customer_detail').html(data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching customer data:', error);
                }
            });
        });

        $(document).on('click', '#remove', function() {
            $('#customer-box').removeClass('d-none');
            $('#customer-box').addClass('d-block');
            $('#customer_detail').removeClass('d-block');
            $('#customer_detail').addClass('d-none');
        })

        $(document).on('change', '.item', function() {

            var iteams_id = $(this).val();
            var url = $(this).data('url');
            var el = $(this);

            $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': jQuery('#token').val()
                },
                data: {
                    'product_id': iteams_id
                },
                cache: false,
                success: function(data) {
                    var item = JSON.parse(data);
                    console.log(el.parent().parent().find('.quantity'))
                    $(el.parent().parent().find('.quantity')).val(1);
                    $(el.parent().parent().find('.price')).val(item.product.sale_price);
                    $(el.parent().parent().parent().find('.pro_description')).val(item.product
                        .description);
                    // $('.pro_description').text(item.product.description);

                    var taxes = '';
                    var tax = [];

                    var totalItemTaxRate = 0;

                    if (item.taxes == 0) {
                        taxes += '-';
                    } else {
                        for (var i = 0; i < item.taxes.length; i++) {
                            taxes += '<span class="badge bg-primary mt-1 mr-2">' + item.taxes[i].name +
                                ' ' + '(' + item.taxes[i].rate + '%)' + '</span>';
                            tax.push(item.taxes[i].id);
                            totalItemTaxRate += parseFloat(item.taxes[i].rate);
                        }
                    }
                    var itemTaxPrice = parseFloat((totalItemTaxRate / 100)) * parseFloat((item.product
                        .sale_price * 1));
                    $(el.parent().parent().find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));
                    $(el.parent().parent().find('.itemTaxRate')).val(totalItemTaxRate.toFixed(2));
                    $(el.parent().parent().find('.taxes')).html(taxes);
                    $(el.parent().parent().find('.tax')).val(tax);
                    $(el.parent().parent().find('.unit')).html(item.unit);
                    $(el.parent().parent().find('.discount')).val(0);

                    var inputs = $(".amount");
                    var subTotal = 0;
                    for (var i = 0; i < inputs.length; i++) {
                        subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
                    }

                    var totalItemPrice = 0;
                    var priceInput = $('.price');
                    for (var j = 0; j < priceInput.length; j++) {
                        totalItemPrice += parseFloat(priceInput[j].value);
                    }

                    var totalItemTaxPrice = 0;
                    var itemTaxPriceInput = $('.itemTaxPrice');
                    for (var j = 0; j < itemTaxPriceInput.length; j++) {
                        totalItemTaxPrice += parseFloat(itemTaxPriceInput[j].value);
                        $(el.parent().parent().find('.amount')).html(parseFloat(item.totalAmount) +
                            parseFloat(itemTaxPriceInput[j].value));
                    }

                    var totalItemDiscountPrice = 0;
                    var itemDiscountPriceInput = $('.discount');

                    for (var k = 0; k < itemDiscountPriceInput.length; k++) {

                        totalItemDiscountPrice += parseFloat(itemDiscountPriceInput[k].value);
                    }

                    $('.subTotal').html(totalItemPrice.toFixed(2));
                    $('.totalTax').html(totalItemTaxPrice.toFixed(2));
                    $('.totalAmount').html((parseFloat(totalItemPrice) - parseFloat(
                        totalItemDiscountPrice) + parseFloat(totalItemTaxPrice)).toFixed(2));


                },
            });
        });

        $(document).on('keyup', '.quantity', function() {
            var quntityTotalTaxPrice = 0;

            var el = $(this).parent().parent().parent().parent();

            var quantity = $(this).val();
            var price = $(el.find('.price')).val();
            var discount = $(el.find('.discount')).val();
            if (discount.length <= 0) {
                discount = 0;
            }

            var totalItemPrice = (quantity * price) - discount;

            var amount = (totalItemPrice);


            var totalItemTaxRate = $(el.find('.itemTaxRate')).val();
            var itemTaxPrice = parseFloat((totalItemTaxRate / 100) * (totalItemPrice));
            $(el.find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));

            $(el.find('.amount')).html(parseFloat(itemTaxPrice) + parseFloat(amount));

            var totalItemTaxPrice = 0;
            var itemTaxPriceInput = $('.itemTaxPrice');
            for (var j = 0; j < itemTaxPriceInput.length; j++) {
                totalItemTaxPrice += parseFloat(itemTaxPriceInput[j].value);
            }


            var totalItemPrice = 0;
            var inputs_quantity = $(".quantity");

            var priceInput = $('.price');
            for (var j = 0; j < priceInput.length; j++) {
                totalItemPrice += (parseFloat(priceInput[j].value) * parseFloat(inputs_quantity[j].value));
            }

            var inputs = $(".amount");

            var subTotal = 0;
            for (var i = 0; i < inputs.length; i++) {
                subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
            }

            $('.subTotal').html(totalItemPrice.toFixed(2));
            $('.totalTax').html(totalItemTaxPrice.toFixed(2));

            $('.totalAmount').html((parseFloat(subTotal)).toFixed(2));

        })

        $(document).on('keyup change', '.price', function() {
            var el = $(this).parent().parent().parent().parent();
            var price = $(this).val();
            var quantity = $(el.find('.quantity')).val();

            var discount = $(el.find('.discount')).val();
            if (discount.length <= 0) {
                discount = 0;
            }
            var totalItemPrice = (quantity * price) - discount;

            var amount = (totalItemPrice);


            var totalItemTaxRate = $(el.find('.itemTaxRate')).val();
            var itemTaxPrice = parseFloat((totalItemTaxRate / 100) * (totalItemPrice));
            $(el.find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));

            $(el.find('.amount')).html(parseFloat(itemTaxPrice) + parseFloat(amount));

            var totalItemTaxPrice = 0;
            var itemTaxPriceInput = $('.itemTaxPrice');
            for (var j = 0; j < itemTaxPriceInput.length; j++) {
                totalItemTaxPrice += parseFloat(itemTaxPriceInput[j].value);
            }


            var totalItemPrice = 0;
            var inputs_quantity = $(".quantity");

            var priceInput = $('.price');
            for (var j = 0; j < priceInput.length; j++) {
                totalItemPrice += (parseFloat(priceInput[j].value) * parseFloat(inputs_quantity[j].value));
            }

            var inputs = $(".amount");

            var subTotal = 0;
            for (var i = 0; i < inputs.length; i++) {
                subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
            }

            $('.subTotal').html(totalItemPrice.toFixed(2));
            $('.totalTax').html(totalItemTaxPrice.toFixed(2));

            $('.totalAmount').html((parseFloat(subTotal)).toFixed(2));


        })


        $(document).on('keyup change', '.discount', function() {
            var el = $(this).parent().parent().parent();
            var discount = $(this).val();
            if (discount.length <= 0) {
                discount = 0;
            }

            var price = $(el.find('.price')).val();
            var quantity = $(el.find('.quantity')).val();
            var totalItemPrice = (quantity * price) - discount;


            var amount = (totalItemPrice);


            var totalItemTaxRate = $(el.find('.itemTaxRate')).val();
            var itemTaxPrice = parseFloat((totalItemTaxRate / 100) * (totalItemPrice));
            $(el.find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));

            $(el.find('.amount')).html(parseFloat(itemTaxPrice) + parseFloat(amount));

            var totalItemTaxPrice = 0;
            var itemTaxPriceInput = $('.itemTaxPrice');
            for (var j = 0; j < itemTaxPriceInput.length; j++) {
                totalItemTaxPrice += parseFloat(itemTaxPriceInput[j].value);
            }


            var totalItemPrice = 0;
            var inputs_quantity = $(".quantity");

            var priceInput = $('.price');
            for (var j = 0; j < priceInput.length; j++) {
                totalItemPrice += (parseFloat(priceInput[j].value) * parseFloat(inputs_quantity[j].value));
            }

            var inputs = $(".amount");

            var subTotal = 0;
            for (var i = 0; i < inputs.length; i++) {
                subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
            }


            var totalItemDiscountPrice = 0;
            var itemDiscountPriceInput = $('.discount');

            for (var k = 0; k < itemDiscountPriceInput.length; k++) {

                totalItemDiscountPrice += parseFloat(itemDiscountPriceInput[k].value);
            }


            $('.subTotal').html(totalItemPrice.toFixed(2));
            $('.totalTax').html(totalItemTaxPrice.toFixed(2));

            $('.totalAmount').html((parseFloat(subTotal)).toFixed(2));
            $('.totalDiscount').html(totalItemDiscountPrice.toFixed(2));




        })

        var customerId = '{{ $customerId }}';
        if (customerId > 0) {
            $('#customer').val(customerId).change();
        }
    </script>
    <script>
        $(document).on('click', '[data-repeater-delete]', function() {
            $(".price").change();
            $(".discount").change();
        });
    </script>
    <script>
        $(function() {
            var attachLabel = @json(__('Attach to email'));
            var maxFileSize = 20 * 1024 * 1024; // 20 MB

            var $zone = $('#attachment-zone');
            var $addLink = $('#attachment-add-link');
            var $header = $('#attachments-header');
            var $list = $('#attachments-list');
            var $inputsContainer = $('#attachment-file-inputs');
            var currentInput = null;

            function updateSelectAllState() {
                var $boxes = $list.find('.attachment-email');
                var $checked = $boxes.filter(':checked');
                $('#attachment_select_all').prop('checked',
                    $boxes.length > 0 && $boxes.length === $checked.length
                );
            }

            function toggleHeader() {
                if ($list.find('.attachment-row').length) {
                    $header.removeClass('d-none');
                } else {
                    $header.addClass('d-none');
                    $('#attachment_select_all').prop('checked', false);
                }
            }

            function createAttachmentInput() {
                var $input = $('<input type="file" class="single-attachment-input d-none">');
                $inputsContainer.append($input);
                currentInput = $input;

                $input.on('change', function() {
                    if (!this.files || !this.files.length) return;

                    var file = this.files[0];

                    if (file.size > maxFileSize) {
                        alert('Max file size is 20 MB');
                        $input.val('');
                        return;
                    }

                    var rowId = 'att_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
                    // bind the name now so Laravel gets an associative array: attachments[rowId]
                    $input.attr('name', 'attachments[' + rowId + ']');

                    var sizeKB = Math.round(file.size / 1024);

                    var $row = $(
                        '<div class="attachment-row" data-row-id="' + rowId + '">' +
                        '<div class="form-check">' +
                        '<input class="form-check-input attachment-email" ' +
                        'type="checkbox" ' +
                        'name="attachments_email[' + rowId + ']" checked>' +
                        '<label class="form-check-label">' + attachLabel + '</label>' +
                        '</div>' +
                        '<span class="attachment-name">' + file.name + '</span>' +
                        '<span class="attachment-size">' + sizeKB + ' KB</span>' +
                        '<button type="button" class="attachment-remove" ' +
                        'data-row-id="' + rowId + '">&times;</button>' +
                        '</div>'
                    );

                    // move the actual file input into this row (so the file is submitted)
                    $row.append($input);
                    $list.append($row);

                    toggleHeader();
                    updateSelectAllState();

                    // prepare a fresh empty input for the next "Add attachment"
                    createAttachmentInput();
                });
            }

            // first empty input
            createAttachmentInput();

            // clicking the link or the zone opens current file input
            $addLink.on('click', function(e) {
                e.preventDefault();
                if (currentInput) currentInput.trigger('click');
            });
            $zone.on('click', function(e) {
                if ($(e.target).is('#attachment-add-link') ||
                    $(e.target).closest('.attachment-row').length) {
                    return;
                }
                if (currentInput) currentInput.trigger('click');
            });

            // "Select All" checkbox
            $('#attachment_select_all').on('change', function() {
                var checked = $(this).is(':checked');
                $list.find('.attachment-email').prop('checked', checked);
            });

            // single checkbox change updates select-all state
            $(document).on('change', '.attachment-email', function() {
                updateSelectAllState();
            });

            // remove one attachment (also removes its file input)
            $(document).on('click', '.attachment-remove', function() {
                var rowId = $(this).data('row-id');
                var $row = $list.find('.attachment-row[data-row-id="' + rowId + '"]');
                $row.remove();
                toggleHeader();
                updateSelectAllState();
            });
        });
    </script>
@endpush

@section('content')
    <div class="modal fade" id="invoice-modal" tabindex="-1" aria-labelledby="invoiceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="invoice-container">

                    {{ Form::open(['url' => 'sales-receipt', 'id' => 'invoice-form']) }}
                    <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
                    <style>
                        .header-actions {
                            display: flex;
                            align-items: center;
                            gap: 12px;
                        }

                        /* Feedback pill button */
                        .qbo-feedback-btn {
                            display: inline-flex;
                            align-items: center;
                            gap: 8px;
                            padding: 6px 14px;
                            border: transparent;
                            background-color: #f4f5f8;
                            color: #393a3d;
                            font-size: 14px;
                            font-weight: 600;
                            cursor: pointer;
                        }

                        .qbo-feedback-btn svg {
                            display: block;
                        }

                        .qbo-feedback-btn:hover {
                            background-color: #6b6c721a;
                        }

                        /* Small round icon buttons (settings / help) */
                        .qbo-icon-btn {
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            width: 32px;
                            height: 32px;
                            border: none;
                            background-color: transparent;
                            color: #393a3d;
                            cursor: pointer;
                        }

                        .qbo-icon-btn:hover {
                            background-color: #f4f5f8;
                        }

                        /* keep your existing close-button style, optionally tweak size */
                        .close-button {
                            background: none;
                            border: none;
                            font-size: 24px;
                            color: var(--qbo-gray-text);
                            cursor: pointer;
                            padding: 4px;
                            line-height: 1;
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                        }
                    </style>
                    {{-- Fixed Top Header --}}
                    {{-- Fixed Top Header --}}
                    <div class="fixed-top-header">
                        <div class="header-top-row">
                            <div class="invoice-label">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    color="currentColor" width="24px" height="24px" focusable="false" aria-hidden="true"
                                    class="">
                                    <path fill="currentColor"
                                        d="M13.007 7a1 1 0 0 0-1 1L12 12a1 1 0 0 0 1 1l3.556.006a1 1 0 0 0 0-2L14 11l.005-3a1 1 0 0 0-.998-1">
                                    </path>
                                    <path fill="currentColor"
                                        d="M19.374 5.647A8.94 8.94 0 0 0 13.014 3H13a8.98 8.98 0 0 0-8.98 8.593l-.312-.312a1 1 0 0 0-1.416 1.412l2 2a1 1 0 0 0 1.414 0l2-2a1 1 0 0 0-1.412-1.416l-.272.272A6.984 6.984 0 0 1 13 5h.012A7 7 0 0 1 13 19h-.012a7 7 0 0 1-4.643-1.775 1 1 0 1 0-1.33 1.494A9 9 0 0 0 12.986 21H13a9 9 0 0 0 6.374-15.353">
                                    </path>
                                </svg>
                                {{ __('Sales Receipt') }}
                            </div>

                            <div class="header-actions">
                                {{-- Feedback button (QBO style) --}}
                                <button type="button" class="qbo-feedback-btn" aria-label="Feedback">
                                    <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class=""
                                        width="24px" height="24px" fill="currentColor">
                                        <path
                                            d="M12 22a.999.999 0 01-.857-.485L9.033 18H6.57A4.541 4.541 0 012 13.5v-7A4.54 4.54 0 016.57 2h10.86A4.54 4.54 0 0122 6.5v7a4.541 4.541 0 01-4.57 4.5h-2.463l-2.11 3.515A1 1 0 0112 22zM6.57 4A2.538 2.538 0 004 6.5v7A2.538 2.538 0 006.57 16H9.6a1 1 0 01.857.485L12 19.057l1.543-2.572A1 1 0 0114.4 16h3.03A2.538 2.538 0 0020 13.5v-7A2.538 2.538 0 0017.43 4H6.57z">
                                        </path>
                                        <path
                                            d="M8 11a1 1 0 100-2 1 1 0 000 2zm4 0a1 1 0 100-2 1 1 0 000 2zm4 0a1 1 0 100-2 1 1 0 000 2z">
                                        </path>
                                    </svg>
                                    <span>{{ __('Feedback') }}</span>
                                </button>

                                {{-- Settings icon button --}}
                                <button type="button" class="qbo-icon-btn" aria-label="Settings">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        color="currentColor" width="24px" height="24px" focusable="false"
                                        aria-hidden="true" class="">
                                        <path fill="currentColor"
                                            d="M12.024 7.982h-.007a4 4 0 1 0 0 8 4 4 0 1 0 .007-8m-.006 6a2 2 0 0 1 .002-4 2 2 0 1 1 0 4z">
                                        </path>
                                        <path fill="currentColor"
                                            d="m20.444 13.4-.51-.295a7.6 7.6 0 0 0 0-2.214l.512-.293a2.005 2.005 0 0 0 .735-2.733l-1-1.733a2.005 2.005 0 0 0-2.731-.737l-.512.295a8 8 0 0 0-1.915-1.113v-.59a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v.6a8 8 0 0 0-1.911 1.1l-.52-.3a2 2 0 0 0-2.725.713l-1 1.73a2 2 0 0 0 .728 2.733l.509.295a7.8 7.8 0 0 0-.004 2.22l-.51.293a2 2 0 0 0-.738 2.73l1 1.732a2 2 0 0 0 2.73.737l.513-.295A8 8 0 0 0 9.01 19.39v.586a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2V19.4a8 8 0 0 0 1.918-1.107l.51.3a2 2 0 0 0 2.734-.728l1-1.73a2 2 0 0 0-.728-2.735m-2.593-2.8a5.8 5.8 0 0 1 0 2.78 1 1 0 0 0 .472 1.1l1.122.651-1 1.73-1.123-.65a1 1 0 0 0-1.187.137 6 6 0 0 1-2.4 1.387 1 1 0 0 0-.716.957v1.294h-2v-1.293a1 1 0 0 0-.713-.96 6 6 0 0 1-2.4-1.395 1.01 1.01 0 0 0-1.188-.142l-1.125.648-1-1.733 1.125-.647a1 1 0 0 0 .475-1.1 6 6 0 0 1-.167-1.387c.003-.467.06-.933.17-1.388a1 1 0 0 0-.471-1.1l-1.123-.65 1-1.73 1.124.651c.019.011.04.01.06.02a1 1 0 0 0 .186.063 1 1 0 0 0 .2.04c.02 0 .039.011.059.011a1 1 0 0 0 .136-.025 1 1 0 0 0 .17-.032q.085-.036.163-.087a1 1 0 0 0 .157-.1c.015-.013.034-.017.048-.03a6 6 0 0 1 2.4-1.39l.049-.026a1 1 0 0 0 .183-.1 1 1 0 0 0 .15-.1 1 1 0 0 0 .122-.147q.057-.073.1-.156a1 1 0 0 0 .055-.173q.03-.098.04-.2c0-.018.012-.034.012-.053V3.981h2v1.294a1 1 0 0 0 .713.96c.897.273 1.72.75 2.4 1.395a1 1 0 0 0 1.186.141l1.126-.647 1 1.733-1.125.647a1 1 0 0 0-.465 1.096">
                                        </path>
                                    </svg>
                                </button>

                                {{-- Help icon button --}}
                                <button type="button" class="qbo-icon-btn" aria-label="Help">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        color="currentColor" width="24px" height="24px" focusable="false"
                                        aria-hidden="true" class="">
                                        <path fill="currentColor"
                                            d="M12 15a1 1 0 1 0 0 2 1 1 0 0 0 0-2M15 10a3.006 3.006 0 0 0-3-3 3 3 0 0 0-2.9 2.27 1 1 0 1 0 1.937.494A1.02 1.02 0 0 1 12 9a1.006 1.006 0 0 1 1 1c0 .013.007.024.007.037s-.007.023-.007.036a.5.5 0 0 1-.276.447l-1.172.584A1 1 0 0 0 11 12v1a1 1 0 1 0 2 0v-.383l.619-.308a2.52 2.52 0 0 0 1.381-2.3z">
                                        </path>
                                        <path fill="currentColor"
                                            d="M19.082 4.94A9.93 9.93 0 0 0 12.016 2H12a10 10 0 0 0-.016 20H12a10 10 0 0 0 7.082-17.06m-1.434 12.725A7.94 7.94 0 0 1 12 20h-.013A8 8 0 1 1 12 4h.012a8 8 0 0 1 5.636 13.665">
                                        </path>
                                    </svg>
                                </button>

                                {{-- Close X --}}
                                <button type="button" class="close-button"
                                    onclick="location.href = '{{ route('sales-receipt.index') }}';" aria-label="Close">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>


                    <div class="main-content">
                        {{-- Top Customer Bar --}}
                        <div class="top-customer-bar row">

                            <div class="customer-select-group col-3">
                                <label class="form-label">{{ __('Customer') }}</label>
                                {{ Form::select('customer_id', $customers, $customerId ?? '', [
                                    'class' => 'form-select',
                                    'id' => 'customer',
                                    'data-url' => route('invoice.customer'),
                                    'required' => 'required',
                                    'placeholder' => 'Choose a customer',
                                ]) }}
                            </div>

                            <div class="email-group col-3">
                                <div style="display: flex; justify-content: space-between;">
                                    <label class="form-label">{{ __('Email') }}</label>
                                    <a href="#" class="helper-link">{{ __('Cc/Bcc') }}</a>
                                </div>
                                {{ Form::text('customer_email', '', [
                                    'class' => 'form-control',
                                    'id' => 'customer_email',
                                    'placeholder' => 'Email (Separate emails with a comma)',
                                ]) }}
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" value="" id="send_later">
                                    <label class="form-check-label" for="send_later" style="font-size: 13px;">
                                        {{ __('Send later') }}
                                    </label>
                                </div>
                            </div>

                            <div class="email-group col-4">
                            </div>

                            <div class="amount-display col-2">
                                <div class="amount-label">{{ __('AMOUNT') }}</div>
                                <div class="amount-value totalAmount">$0.00</div>
                            </div>

                        </div>

                        {{-- Cards Grid --}}
                        <div class="cards-grid">
                            {{-- Left Column --}}
                            <div class="left-column">
                                {{-- Record or Charge Card --}}
                                <div class="qbo-card">
                                    <div class="qbo-card-header">
                                        <div class="qbo-section-title">{{ __('Record or charge') }}</div>
                                        <span class="new-badge">NEW</span>
                                    </div>
                                    <div class="row d-flex">
                                        <div class="col-4">
                                            <div class="payment-type-group">
                                                <div class="payment-option">
                                                    <input type="radio" name="payment_type" id="record_payment"
                                                        checked>
                                                    <label for="record_payment">{{ __('Record payment') }}</label>
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                        viewBox="0 0 24 24" fill="none" stroke="#6b6c72"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                        style="margin-left: 5px;">
                                                        <circle cx="12" cy="12" r="10"></circle>
                                                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                                                        <line x1="12" y1="17" x2="12.01"
                                                            y2="17"></line>
                                                    </svg>
                                                </div>
                                                <div class="payment-option-desc">
                                                    {{ __('Received via check, cash, other') }}</div>

                                                <div class="payment-option">
                                                    <input type="radio" name="payment_type" id="charge_payment">
                                                    <label for="charge_payment">{{ __('Charge new payment') }}</label>
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                        viewBox="0 0 24 24" fill="none" stroke="#6b6c72"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                        style="margin-left: 5px;">
                                                        <circle cx="12" cy="12" r="10"></circle>
                                                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                                                        <line x1="12" y1="17" x2="12.01"
                                                            y2="17"></line>
                                                    </svg>
                                                </div>
                                                <div class="payment-option-desc">
                                                    {{ __('Process a card or ACH bank transfer') }}</div>
                                                <div class="payment-icons">
                                                    <div style="margin: 8px 0;">
                                                        <img src="{{ asset('assets/images/credit_cards_qbocredits.png') }}"
                                                            alt="Credit Cards" style="height: 24px;">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <style>
                                            .qbo-card-header {
                                                display: flex;
                                                align-items: center;
                                                gap: 10px;

                                                /* NEW: to match QBO */
                                                padding-bottom: 10px;
                                                margin-bottom: 15px;
                                                border-bottom: 1px solid #e4e4e7;
                                            }

                                            .charge-payment-card {
                                                background: #f4f5f8;
                                                border: 1px solid #e4e4e7;
                                                border-radius: 4px;
                                                padding: 20px;
                                            }

                                            .charge-card-title {
                                                font-size: 15px;
                                                font-weight: 600;
                                                color: #393a3d;
                                                margin-bottom: 8px;
                                            }

                                            .charge-card-text {
                                                font-size: 13px;
                                                color: #6b6c72;
                                                margin-bottom: 8px;
                                            }

                                            .charge-card-link {
                                                display: inline-block;
                                                font-size: 13px;
                                                color: #0077c5;
                                                text-decoration: none;
                                                margin-bottom: 16px;
                                            }

                                            .charge-card-link:hover {
                                                text-decoration: underline;
                                            }

                                            .btn-activate-payments {
                                                display: inline-flex;
                                                align-items: center;
                                                justify-content: center;
                                                padding: 8px 18px;
                                                border-radius: 4px;
                                                border: 1px solid var(--qbo-green);
                                                background: var(--qbo-green);
                                                color: #fff;
                                                font-size: 14px;
                                                font-weight: 600;
                                                cursor: pointer;
                                            }

                                            .btn-activate-payments:hover {
                                                background: var(--qbo-green-hover);
                                            }
                                        </style>
                                        <script>
                                            // Toggle between "Record payment" form and "Charge new payment" info card
                                            $(document).on('change', 'input[name="payment_type"]', function() {
                                                if ($('#charge_payment').is(':checked')) {
                                                    $('#record-payment-fields').hide();
                                                    $('#charge-payment-card').show();
                                                } else {
                                                    $('#charge-payment-card').hide();
                                                    $('#record-payment-fields').show();
                                                }
                                            });

                                            // ensure correct state on load (in case default changes later)
                                            $(function() {
                                                if ($('#charge_payment').is(':checked')) {
                                                    $('#record-payment-fields').hide();
                                                    $('#charge-payment-card').show();
                                                } else {
                                                    $('#charge-payment-card').hide();
                                                    $('#record-payment-fields').show();
                                                }
                                            });
                                        </script>
                                        <div class="col-8">
                                            {{-- Record payment fields (default view) --}}
                                            <div id="record-payment-fields" class="form-grid-2">
                                                <div class="field-group">
                                                    <label class="form-label">{{ __('Sales Receipt Date') }}</label>
                                                    {{ Form::date('issue_date', date('Y-m-d'), ['class' => 'form-control', 'required' => 'required']) }}
                                                </div>
                                                <div class="field-group">
                                                    <label class="form-label">{{ __('Reference no.') }}</label>
                                                    {{ Form::text('ref_number', '', ['class' => 'form-control']) }}
                                                </div>
                                                <div class="field-group">
                                                    <label class="form-label">{{ __('Payment method') }}</label>
                                                    {{ Form::select('payment_method', ['' => 'Select method', 'Cash' => 'Cash', 'Check' => 'Check', 'Credit Card' => 'Credit Card'], null, ['class' => 'form-select']) }}
                                                </div>
                                                <div class="field-group">
                                                    <label class="form-label">{{ __('Deposit To') }}</label>
                                                    {{-- add account --}}
                                                    {{ Form::select('deposit_to', $accounts, null, ['class' => 'form-select']) }}
                                                </div>
                                            </div>

                                            {{-- Charge new payment info card (QBO style) --}}
                                            <div id="charge-payment-card" class="charge-payment-card"
                                                style="display:none;">
                                                <div class="charge-card-title">
                                                    {{ __('Process payments on open invoices') }}
                                                </div>
                                                <div class="charge-card-text">
                                                    {{ __('Charge debit and credit cards or process ACH bank payments. Your books update automatically when you get paid through QuickBooks.') }}
                                                </div>
                                                <a href="#"
                                                    class="charge-card-link">{{ __('Find out more') }}</a><br>

                                                <button type="button" class="btn-activate-payments">
                                                    {{ __('Activate payments') }}
                                                </button>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                {{-- Additional Fields Card --}}
                                <div class="qbo-card">
                                    <div class="qbo-section-title" style="margin-bottom: 15px;">
                                        {{ __('Additional fields') }}</div>
                                    <div class="field-group">
                                        <label class="form-label">{{ __('Location of Sale') }}</label>
                                        {{ Form::text('location_of_sale', '123 Sierra Way San Pablo, CA 87999', ['class' => 'form-control']) }}
                                    </div>
                                </div>
                            </div>

                            {{-- Right Column --}}
                            <div class="right-column">
                                <div class="qbo-card">
                                    <div class="qbo-section-title" style="margin-bottom: 15px;">{{ __('Addresses') }}
                                    </div>
                                    <div class="field-group">
                                        <label class="form-label">{{ __('Billing Address') }}</label>
                                        {{ Form::textarea('bill_to', '', ['class' => 'form-control address-box', 'rows' => 3]) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Product Section --}}
                        <div class="invoice-card">
                            <div class="product-section repeater">
                                <h2 class="section-heading">{{ __('Product or service') }}</h2>

                                <table class="product-table" id="sortable-table" data-repeater-list="items">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>#</th>
                                            <th>{{ __('Product/service') }}</th>
                                            <th>{{ __('Description') }}</th>
                                            <th>{{ __('Qty') }}</th>
                                            <th>{{ __('Rate') }}</th>
                                            <th>{{ __('Amount') }}</th>
                                            <th>{{ __('Tax') }}</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody data-repeater-item>
                                        <tr class="product-row">
                                            <td>
                                                <div class="drag-handle sort-handler"><i class="ti ti-grid-dots"></i>
                                                </div>
                                            </td>
                                            <td><span class="line-number">1</span></td>
                                            <td>
                                                {{ Form::select('item', $product_services, '', ['class' => 'form-select item', 'data-url' => route('invoice.product'), 'required' => 'required']) }}
                                            </td>
                                            <td>
                                                {{ Form::textarea('description', null, ['class' => 'form-control pro_description', 'rows' => '1', 'placeholder' => '']) }}
                                            </td>
                                            <td>
                                                {{ Form::text('quantity', '', ['class' => 'form-control input-right quantity', 'required' => 'required']) }}
                                            </td>
                                            <td>
                                                {{ Form::text('price', '', ['class' => 'form-control input-right price', 'required' => 'required']) }}
                                            </td>
                                            <td class="text-end">
                                                <input type="text" name="amount"
                                                    class="form-control input-right amount" value="0.00" readonly>
                                            </td>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="">
                                                </div>
                                                {{ Form::hidden('tax', '', ['class' => 'form-control tax']) }}
                                                {{ Form::hidden('itemTaxPrice', '', ['class' => 'form-control itemTaxPrice']) }}
                                                {{ Form::hidden('itemTaxRate', '', ['class' => 'form-control itemTaxRate']) }}
                                                {{ Form::hidden('discount', '', ['class' => 'form-control discount']) }}
                                            </td>
                                            <td>
                                                <span class="delete-icon" data-repeater-delete>
                                                    <svg width="16" height="16" viewBox="0 0 24 24"
                                                        fill="currentColor">
                                                        <path
                                                            d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z" />
                                                    </svg>
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div class="table-actions">
                                    <button type="button" class="btn-outline"
                                        style="border: 2px solid #8d9096 !important; color: #393a3d !important; padding: 1px 12px !important; border-radius:3px !important;"
                                        data-repeater-create>{{ __('Add lines') }}</button>
                                    <button type="button" class="btn-outline"
                                        style="border: 2px solid #8d9096 !important; color: #393a3d !important; padding: 1px 12px !important; border-radius:3px !important;"
                                        id="clear-lines" style="margin-left: 10px;">{{ __('Clear all lines') }}</button>
                                </div>
                            </div>
                        </div>
                        <style>
                            /* Product Table */
                            .product-section {
                                padding: 24px 32px;
                                background: #fff;
                            }

                            .section-heading {
                                font-size: 15px;
                                font-weight: 600;
                                color: #393a3d;
                                margin-bottom: 16px;
                            }

                            /* === SINGLE SOURCE OF TRUTH FOR TABLE LAYOUT === */
                            .invoice-card .product-table {
                                width: 100%;
                                border-collapse: separate;
                                border-spacing: 0;
                                border-top: 1px solid #e4e4e7;
                                border-bottom: 1px solid #e4e4e7;
                            }

                            /* headers */
                            .invoice-card .product-table thead th {
                                padding: 12px 8px;
                                font-size: 13px;
                                font-weight: 600;
                                color: #393a3d;
                                background: #fff;
                                border-bottom: 1px solid #e4e4e7;
                            }

                            /* body cells */
                            .invoice-card .product-table tbody td {
                                padding: 12px 8px;
                                font-size: 13px;
                                vertical-align: middle;
                                border-bottom: 1px solid #e4e4e7;
                            }

                            /* dotted vertical lines between columns */
                            .invoice-card .product-table thead th+th,
                            .invoice-card .product-table tbody td+td {
                                border-left: 1px dotted #e4e4e7;
                            }

                            /* no outer left border on first column */
                            .invoice-card .product-table thead th:first-child,
                            .invoice-card .product-table tbody td:first-child {
                                border-left: none;
                            }

                            /* === COLUMN WIDTHS & ALIGNMENT === */

                            /* col 1: drag handle */
                            .invoice-card .product-table thead th:nth-child(1),
                            .invoice-card .product-table tbody td:nth-child(1) {
                                width: 26px;
                                text-align: center;
                            }

                            /* col 2: line # */
                            .invoice-card .product-table thead th:nth-child(2),
                            .invoice-card .product-table tbody td:nth-child(2) {
                                width: 30px;
                                text-align: center;
                            }

                            /* col 4: Description (wide) */
                            .invoice-card .product-table thead th:nth-child(4),
                            .invoice-card .product-table tbody td:nth-child(4) {
                                width: 30%;
                            }

                            /* cols 5–7: Qty, Rate, Amount – same width, right aligned */
                            .invoice-card .product-table thead th:nth-child(5),
                            .invoice-card .product-table thead th:nth-child(6),
                            .invoice-card .product-table thead th:nth-child(7),
                            .invoice-card .product-table tbody td:nth-child(5),
                            .invoice-card .product-table tbody td:nth-child(6),
                            .invoice-card .product-table tbody td:nth-child(7) {
                                width: 7%;
                                max-width: 80px;
                                text-align: right;
                            }

                            .invoice-card .product-table tbody td:nth-child(5) .form-control,
                            .invoice-card .product-table tbody td:nth-child(6) .form-control,
                            .invoice-card .product-table tbody td:nth-child(7) .form-control {
                                width: 100%;
                                text-align: right;
                            }

                            /* col 8: Tax – small, centered */
                            .invoice-card .product-table thead th:nth-child(8),
                            .invoice-card .product-table tbody td:nth-child(8) {
                                width: 40px;
                                text-align: center;
                            }

                            /* col 9: Delete – small, centered */
                            .invoice-card .product-table thead th:nth-child(9),
                            .invoice-card .product-table tbody td:nth-child(9) {
                                width: 40px;
                                text-align: center;
                                padding-left: 0;
                                padding-right: 0;
                            }

                            /* center the trash icon itself */
                            .invoice-card .product-table tbody td:nth-child(9) .delete-icon {
                                display: inline-block;
                            }

                            /* drag-handle look */
                            .drag-handle {
                                cursor: grab;
                                color: #c4c4c4;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                            }

                            .drag-handle:active {
                                cursor: grabbing;
                            }

                            /* === QBO-style totals section === */

                            /* Keep total rows aligned */
                            .totals-section .total-row {
                                align-items: center;
                            }

                            /* Discount row: dropdown + input inline on the left, amount on the right */
                            .totals-section .discount-row {
                                align-items: center;
                            }

                            .discount-controls {
                                display: flex;
                                flex: 1;
                                gap: 8px;
                            }

                            .discount-type-select {
                                flex: 1;
                            }

                            .discount-input {
                                width: 90px;
                                text-align: right;
                            }

                            /* Tax selector row: rotate icon + dropdown */
                            .tax-selector-row {
                                align-items: center;
                            }

                            .tax-selector-inner {
                                display: flex;
                                flex: 1;
                                align-items: center;
                                gap: 6px;
                            }

                            .tax-selector-inner .form-select {
                                flex: 1;
                            }

                            /* Helper text under tax selector */
                            .sales-tax-help {
                                font-size: 12px;
                                color: #6b6c72;
                                margin: 4px 0 0;
                                text-align: left;
                            }

                            .sales-tax-help .helper-link {
                                font-size: 12px;
                            }

                            /* Keep rotate icon looking like QBO */
                            .discount-position-btn {
                                border: none;
                                background: #ffffff;
                                padding: 4px;
                                display: inline-flex;
                                align-items: center;
                                justify-content: center;
                                cursor: pointer;
                                color: #6b6c72;
                                transition: background 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
                            }

                            .discount-position-btn:hover {
                                background: #f4f5f8;
                                border-radius: 4px;
                            }
                        </style>

                        {{-- Bottom Grid --}}
                        <div class="bottom-grid row">
                            <div class="left-section col-4">
                                <div class="field-group">
                                    <label class="form-label">{{ __('Message displayed on sales receipt') }}</label>
                                    {{ Form::textarea('note', '', ['class' => 'form-control', 'rows' => 3, 'style' => 'height: 85px;', 'placeholder' => 'Thank you for your business and have a great day!']) }}
                                </div>
                                <div class="field-group" style="margin-top: 20px;">
                                    <label class="form-label">{{ __('Message displayed on statement') }}</label>
                                    {{ Form::textarea('memo', '', ['class' => 'form-control', 'style' => 'height: 85px;', 'rows' => 3]) }}
                                </div>
                                <div class="field-group" style="margin-top: 20px;">
                                    <label class="form-label">{{ __('Attachments') }}</label>

                                    <div class="attachments-wrapper">
                                        {{-- Header with "Select All" --}}
                                        <div id="attachments-header" class="d-none">
                                            <div class="attachments-header-left">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox"
                                                        id="attachment_select_all">
                                                    <label class="form-check-label" for="attachment_select_all">
                                                        {{ __('Select All') }}
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="attachments-header-right">
                                                {{ __('Attach files to this email') }}
                                            </div>
                                        </div>

                                        {{-- Dynamic list of attachment rows (filled by JS) --}}
                                        <div id="attachments-list"></div>

                                        {{-- Drop zone / Add attachment button --}}
                                        <div id="attachment-zone" class="attachment-zone">
                                            <a href="#" id="attachment-add-link" class="attachment-link">
                                                {{ __('Add attachment') }}
                                            </a>
                                            <div class="attachment-limit">
                                                {{ __('Max file size: 20 MB') }}
                                            </div>
                                        </div>

                                        {{-- Hidden container where file inputs live (so they submit with the form) --}}
                                        <div id="attachment-file-inputs" class="d-none"></div>
                                    </div>

                                    <a href="#" class="helper-link"
                                        style="margin-top: 5px; display: inline-block;">{{ __('Show existing') }}</a>
                                </div>

                            </div>
                            <div class="left-section col-4">
                            </div>
                            <div class="right-section col-4">
                                <div class="totals-section">
                                    {{-- Hidden fields to submit calculated totals --}}
                                    <input type="hidden" name="subtotal" id="hidden_subtotal"
                                        value="{{ isset($salesReceiptData) ? $salesReceiptData['subtotal'] : 0 }}">
                                    <input type="hidden" name="taxable_subtotal" id="hidden_taxable_subtotal"
                                        value="{{ isset($salesReceiptData) ? $salesReceiptData['taxable_subtotal'] : 0 }}">
                                    <input type="hidden" name="total_discount" id="hidden_total_discount"
                                        value="{{ isset($salesReceiptData) ? $salesReceiptData['total_discount'] : 0 }}">
                                    <input type="hidden" name="total_tax" id="hidden_total_tax"
                                        value="{{ isset($salesReceiptData) ? $salesReceiptData['total_tax'] : 0 }}">
                                    <input type="hidden" name="sales_tax_amount" id="hidden_sales_tax_amount"
                                        value="{{ isset($salesReceiptData) ? $salesReceiptData['sales_tax_amount'] : 0 }}">
                                    <input type="hidden" name="total_amount" id="hidden_total_amount"
                                        value="{{ isset($salesReceiptData) ? $salesReceiptData['total_amount'] : 0 }}">

                                    {{-- Subtotal --}}
                                    <div class="total-row subtotal">
                                        <span>{{ __('Subtotal') }}</span>
                                        <span
                                            class="subTotal">{{ isset($salesReceiptData) ? number_format($salesReceiptData['subtotal'], 2) : '0.00' }}</span>
                                    </div>

                                    {{-- Discount row – QBO style: dropdown + input on the left, discount amount on the right --}}
                                    <div class="total-row discount-row">
                                        <div class="discount-controls">
                                            <select name="discount_type" class="form-select discount-type-select">
                                                <option value="percent"
                                                    {{ isset($salesReceiptData) && $salesReceiptData['discount_type'] == 'percent' ? 'selected' : '' }}>
                                                    {{ __('Discount Percent') }}</option>
                                                <option value="value"
                                                    {{ isset($salesReceiptData) && $salesReceiptData['discount_type'] == 'value' ? 'selected' : '' }}>
                                                    {{ __('Discount Value') }}</option>
                                            </select>

                                            <input type="number" step="0.01" name="discount_value"
                                                class="form-control discount-input"
                                                value="{{ isset($salesReceiptData) ? $salesReceiptData['discount_value'] : '0.00' }}">
                                        </div>

                                        {{-- total discount value --}}
                                        <span
                                            class="totalDiscount">{{ isset($salesReceiptData) ? number_format($salesReceiptData['total_discount'], 2) : '0.00' }}</span>
                                    </div>

                                    {{-- Taxable subtotal --}}
                                    <div class="total-row">
                                        <span>{{ __('Taxable subtotal') }}</span>
                                        <span
                                            class="taxableSubtotal">{{ isset($salesReceiptData) ? number_format($salesReceiptData['taxable_subtotal'], 2) : '0.00' }}</span>
                                    </div>

                                    {{-- Sales tax selector: rotate icon + dropdown (left side) --}}
                                    <div class="total-row tax-selector-row">
                                        <div class="tax-selector-inner">
                                            {{-- move-discount-before/after-tax button (QBO icon) --}}
                                            <button type="button"
                                                aria-label="To move discounts before or after sales tax, select the icon."
                                                class="discount-position-btn" data-bs-toggle="tooltip"
                                                data-bs-placement="left"
                                                title="{{ __('To move discounts before or after sales tax, select the icon.') }}">
                                                <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24"
                                                    width="20" height="20" fill="currentColor">
                                                    <path
                                                        d="M15.7 16.28a1 1 0 10-1.416 1.412l.292.294-5.585-.01a1 1 0 01-1-1l.014-10a1 1 0 011-1l5.586.01-.294.292a1 1 0 101.412 1.416l2-2a1 1 0 000-1.414l-2-2a1 1 0 10-1.416 1.412l.292.294-5.574-.01a3 3 0 00-3 3l-.014 10a3 3 0 002.995 3l5.586.01-.294.292a1 1 0 101.412 1.416l2-2a1 1 0 000-1.414l-1.996-2z">
                                                    </path>
                                                </svg>
                                            </button>

                                            <select name="sales_tax_rate" class="form-select totals-tax-rate-select">
                                                <option value="" data-rate="0">{{ __('Select a tax rate') }}
                                                </option>
                                                @foreach ($taxes as $tax)
                                                    <option value="{{ $tax->id }}" data-rate="{{ $tax->rate }}"
                                                        {{ isset($salesReceiptData) && $salesReceiptData['sales_tax_rate'] == $tax->id ? 'selected' : '' }}>
                                                        {{ $tax->name }} ({{ $tax->rate }}%)</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- keep empty span on the right so alignment stays like QBO --}}
                                        <span></span>
                                    </div>

                                    {{-- Helper text under the tax selector --}}
                                    <div class="sales-tax-help">
                                        {{ __('Need help with sales tax?') }}
                                        <a href="#" class="helper-link">{{ __('Learn more') }}</a>
                                    </div>

                                    {{-- Sales tax row --}}
                                    <div class="total-row sales-tax-row">
                                        <span>{{ __('Sales tax') }}</span>
                                        <span
                                            class="totalTax">{{ isset($salesReceiptData) ? number_format($salesReceiptData['total_tax'], 2) : '0.00' }}</span>
                                    </div>

                                    <script>
                                        $(function() {
                                            // enable Bootstrap tooltip on the icon
                                            if (typeof bootstrap !== 'undefined') {
                                                $('[data-bs-toggle="tooltip"]').each(function() {
                                                    new bootstrap.Tooltip(this);
                                                });
                                            }

                                            var discountBeforeTax = true; // visual order only

                                            function placeDiscountBeforeTax() {
                                                var $discountRow = $('.totals-section .discount-row');
                                                var $subtotalRow = $('.totals-section .subtotal').first();

                                                if ($discountRow.length && $subtotalRow.length) {
                                                    // Discount just after Subtotal
                                                    $discountRow.insertAfter($subtotalRow);
                                                }
                                                discountBeforeTax = true;
                                            }

                                            function placeDiscountAfterTax() {
                                                var $discountRow = $('.totals-section .discount-row');
                                                var $salesTaxRow = $('.totals-section .sales-tax-row').first();

                                                if ($discountRow.length && $salesTaxRow.length) {
                                                    // Discount just after Sales tax
                                                    $discountRow.insertAfter($salesTaxRow);
                                                }
                                                discountBeforeTax = false;
                                            }

                                            // initial order: Subtotal -> Discount -> Taxable subtotal...
                                            placeDiscountBeforeTax();

                                            // click on rotate icon toggles position
                                            $('.discount-position-btn').on('click', function() {
                                                if (discountBeforeTax) {
                                                    placeDiscountAfterTax();
                                                } else {
                                                    placeDiscountBeforeTax();
                                                }
                                            });
                                        });
                                    </script>

                                    {{-- See the math (right-aligned) --}}
                                    <a href="#" class="link-button see-math-link">{{ __('See the math') }}</a>

                                    {{-- Total --}}
                                    <div class="total-row final">
                                        <span>{{ __('Total') }}</span>
                                        <span
                                            class="totalAmount">{{ isset($salesReceiptData) ? number_format($salesReceiptData['total_amount'], 2) : '0.00' }}</span>
                                    </div>

                                    {{-- Amount received (for sales receipt, same as total initially) --}}
                                    <div class="total-row final">
                                        <span>{{ __('Amount received') }}</span>
                                        <span
                                            class="amountReceived">{{ isset($salesReceiptData) ? number_format($salesReceiptData['amount_received'], 2) : '0.00' }}</span>
                                    </div>

                                    {{-- Balance due (for sales receipt, should be 0 if fully paid) --}}
                                    <div class="total-row">
                                        <span>{{ __('Balance due') }}</span>
                                        <span
                                            class="balanceDue">{{ isset($salesReceiptData) ? number_format($salesReceiptData['balance_due'], 2) : '0.00' }}</span>
                                    </div>

                                    {{-- Edit totals (right-aligned) --}}
                                    <a href="#" class="link-button edit-totals-link">{{ __('Edit totals') }}</a>
                                </div>
                            </div>

                        </div>
                        <style>
                            /* Footer Styles */
                            .invoice-footer {
                                background: #f7f8fa;
                                padding: 16px 32px;
                                border-top: 1px solid #e4e4e7;
                                box-shadow: var(--qbds-7b236e, 0 6px 24px 0) var(--qbds-e87a1b, rgba(0, 0, 0, .2));
                                display: flex;
                                justify-content: space-between;
                                align-items: center;
                                margin-top: auto;
                                position: sticky;
                                bottom: 0;
                                z-index: 100;
                            }

                            .footer-left {
                                display: flex;
                                gap: 16px;
                                align-items: center;
                            }

                            .footer-actions {
                                display: flex;
                                gap: 12px;
                            }
                        </style>

                    </div>
                    {{-- Footer --}}
                    <div class="invoice-footer">
                        <div class="footer-left">
                            <!-- <button type="button" class="btn btn-secondary"
                                                                                                                                                                            onclick="location.href = '{{ route('invoice.index') }}';">
                                                                                                                                                                        {{ __('Cancel') }}
                                                                                                                                                                    </button> -->
                        </div>

                        <div class="footer-center">
                            <button type="button" class="footer-link" onclick="window.print()">
                                {{ __('Print or download') }}
                            </button>
                        </div>

                        <div class="footer-actions">
                            {{-- Save Split Button --}}
                            <div class="btn-group dropup">
                                <button type="submit" class="btn btn-secondary" name="submit_action" value="save">
                                    {{ __('Save') }}
                                </button>
                                <button type="button" class="btn btn-secondary dropdown-toggle dropdown-toggle-split"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="visually-hidden">Toggle Dropdown</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><button type="submit" class="dropdown-item" name="submit_action"
                                            value="save_new">
                                            {{ __('Save and new') }}
                                        </button></li>
                                    <li><button type="submit" class="dropdown-item" name="submit_action"
                                            value="save_close">
                                            {{ __('Save and close') }}
                                        </button></li>
                                </ul>
                            </div>

                            {{-- Review and Send Split Button --}}
                            <div class="btn-group dropup">
                                <button type="submit" class="btn btn-primary" name="submit_action" value="review_send">
                                    {{ __('Review and send') }}
                                </button>
                                <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="visually-hidden">Toggle Dropdown</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><button type="submit" class="dropdown-item" name="submit_action"
                                            value="share_link">
                                            {{ __('Share link') }}
                                        </button></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
    <script src="{{ asset('js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('js/jquery.repeater.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            var invoiceModal = new bootstrap.Modal(document.getElementById('invoice-modal'), {
                backdrop: 'static',
                keyboard: false
            });
            invoiceModal.show();
        });

        function renumberInvoiceLines() {
            $('#sortable-table').find('tbody').each(function(index) {
                $(this).find('.line-number').text(index + 1);
            });
        }
    </script>
    <script>
        var selector = "body";
        if ($(selector + " .repeater").length) {
            var $dragAndDrop = $("body .repeater [data-repeater-list]").sortable({
                handle: '.sort-handler',
                items: 'tbody',
                axis: 'y',
                stop: function() {
                    renumberInvoiceLines();
                }
            });

            var $repeater = $(selector + ' .repeater').repeater({
                initEmpty: false,
                defaultValues: {
                    'status': 1
                },
                show: function() {
                    $(this).slideDown();
                    if ($('.select2').length) {
                        $('.select2').select2();
                    }

                    // Initialize new row with default values and trigger calculations
                    var $newRow = $(this).find('tr.product-row');
                    $newRow.find('.quantity').val('1');
                    $newRow.find('.price').val('0.00');
                    $newRow.find('.discount').val('0.00');
                    $newRow.find('.amount').html('0.00');
                    $newRow.find('.itemTaxPrice').val('0.00');
                    $newRow.find('.itemTaxRate').val('0.00');
                    $newRow.find('.form-check-input[type="checkbox"]').prop('checked', false);

                    // NEW: renumber lines whenever a new row is added
                    renumberInvoiceLines();

                    // Recalculate totals after adding new row
                    recalcTotals();
                },
                hide: function(deleteElement) {
                    if (confirm('Are you sure you want to delete this element?')) {
                        $(this).slideUp(deleteElement);
                        $(this).remove();


                        // Recalculate totals after deletion
                        recalcTotals();
                        renumberInvoiceLines();
                    }
                },
                ready: function(setIndexes) {
                    $dragAndDrop.on('sortstop', function() {
                        setIndexes();
                        renumberInvoiceLines();
                    });
                },
                isFirstItemUndeletable: true
            });
        }

        $(document).on('change', '#customer', function() {
            var id = $(this).val();
            var url = $(this).data('url');

            if (!id) {
                $('#customer_email').val('');
                $('textarea[name="bill_to"]').val('');
                return;
            }

            $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': jQuery('#token').val()
                },
                data: {
                    'id': id
                },
                cache: false,
                success: function(data) {
                    if (typeof data === 'object' && data.customer) {
                        if (data.customer.email) {
                            $('#customer_email').val(data.customer.email);
                        }

                        var billingAddress = '';
                        if (data.customer.billing_name) billingAddress += data.customer.billing_name +
                            '\n';
                        if (data.customer.billing_address) billingAddress += data.customer
                            .billing_address + '\n';
                        if (data.customer.billing_city) {
                            billingAddress += data.customer.billing_city;
                            if (data.customer.billing_state) billingAddress += ', ' + data.customer
                                .billing_state;
                            if (data.customer.billing_zip) billingAddress += ' ' + data.customer
                                .billing_zip;
                            billingAddress += '\n';
                        }
                        if (data.customer.billing_country) billingAddress += data.customer
                            .billing_country;

                        $('textarea[name="bill_to"]').val(billingAddress.trim());
                    }
                }
            });
        });

        $(document).on('change', '.item', function() {
            var iteams_id = $(this).val();
            var url = $(this).data('url');
            var $row = $(this).closest('tr.product-row');

            if (!iteams_id) {
                // Clear the row if no product selected
                $row.find('.quantity').val('');
                $row.find('.price').val('');
                $row.find('.pro_description').val('');
                $row.find('.itemTaxPrice').val('0.00');
                $row.find('.itemTaxRate').val('0.00');
                $row.find('.tax').val('');
                $row.find('.discount').val('0');
                $row.find('.amount').val('0.00');
                recalcTotals();
                return;
            }

            $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': jQuery('#token').val()
                },
                data: {
                    'product_id': iteams_id
                },
                cache: false,
                success: function(data) {
                    var item = JSON.parse(data);

                    // Set default quantity to 1
                    $row.find('.quantity').val(1);

                    // Set price from product
                    $row.find('.price').val(parseFloat(item.product.sale_price).toFixed(2));

                    // Set description
                    $row.find('.pro_description').val(item.product.description || '');

                    // Handle taxes
                    var taxIds = [];
                    var totalItemTaxRate = 0;

                    if (item.taxes && item.taxes.length > 0) {
                        for (var i = 0; i < item.taxes.length; i++) {
                            taxIds.push(item.taxes[i].id);
                            totalItemTaxRate += parseFloat(item.taxes[i].rate);
                        }
                    }

                    // Calculate tax amount: (price * tax_rate / 100)
                    var itemTaxPrice = parseFloat(item.product.sale_price) * totalItemTaxRate / 100;

                    // Update hidden fields
                    $row.find('.itemTaxPrice').val(itemTaxPrice.toFixed(2));
                    $row.find('.itemTaxRate').val(totalItemTaxRate.toFixed(2));
                    $row.find('.tax').val(taxIds.join(','));
                    $row.find('.discount').val('0');

                    // Calculate and display amount: (quantity * price) - discount + tax
                    var quantity = parseFloat($row.find('.quantity').val()) || 0;
                    var price = parseFloat($row.find('.price').val()) || 0;
                    var discount = parseFloat($row.find('.discount').val()) || 0;
                    var amount = (quantity * price) - discount + itemTaxPrice;

                    $row.find('.amount').val(amount.toFixed(2));

                    // Recalculate totals
                    recalcTotals();
                },
                error: function() {
                    console.error('Error loading product data');
                }
            });
        });

        $(document).on('keyup change', '.quantity, .price, .discount', function() {
            var el = $(this).closest('tr');
            var quantity = parseFloat($(el.find('.quantity')).val()) || 0;
            var price = parseFloat($(el.find('.price')).val()) || 0;
            var discount = parseFloat($(el.find('.discount')).val()) || 0;

            // Calculate base amount: quantity * price - discount
            var baseAmount = (quantity * price) - discount;

            // Calculate tax on the base amount
            var totalItemTaxRate = parseFloat($(el.find('.itemTaxRate')).val()) || 0;
            var itemTaxPrice = (totalItemTaxRate / 100) * baseAmount;

            // Update tax price field
            $(el.find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));

            // Calculate final amount: base amount + tax
            var finalAmount = baseAmount + itemTaxPrice;
            $(el.find('.amount')).val(finalAmount.toFixed(2));

            // Recalculate totals
            recalcTotals();
        });

        // Tax checkbox change handler - set tax ID from invoice-level tax and recalculate
        $(document).on('change', '.product-row .form-check-input[type="checkbox"]', function() {
            var $row = $(this).closest('tr.product-row');
            var isChecked = $(this).prop('checked');

            // Get the invoice-level tax rate from the dropdown
            var $selectedTax = $('select[name="sales_tax_rate"]').find(':selected');
            var taxId = $selectedTax.val() || '';
            var taxRate = parseFloat($selectedTax.data('rate')) || 0;

            if (isChecked && taxId) {
                // Set the tax ID for this item
                $row.find('.tax').val(taxId);
                $row.find('.itemTaxRate').val(taxRate.toFixed(2));

                // Calculate tax amount based on line amount
                var quantity = parseFloat($row.find('.quantity').val()) || 0;
                var price = parseFloat($row.find('.price').val()) || 0;
                var discount = parseFloat($row.find('.discount').val()) || 0;
                var baseAmount = (quantity * price) - discount;
                var itemTaxPrice = (taxRate / 100) * baseAmount;

                $row.find('.itemTaxPrice').val(itemTaxPrice.toFixed(2));

                // Update amount display
                var finalAmount = baseAmount + itemTaxPrice;
                $row.find('.amount').val(finalAmount.toFixed(2));
            } else {
                // Clear tax when unchecked
                $row.find('.tax').val('');
                $row.find('.itemTaxRate').val('0.00');
                $row.find('.itemTaxPrice').val('0.00');

                // Recalculate amount without tax
                var quantity = parseFloat($row.find('.quantity').val()) || 0;
                var price = parseFloat($row.find('.price').val()) || 0;
                var discount = parseFloat($row.find('.discount').val()) || 0;
                var baseAmount = (quantity * price) - discount;
                $row.find('.amount').val(baseAmount.toFixed(2));
            }

            recalcTotals();
        });

        // Tax rate selector change handler
        $(document).on('change', 'select[name="sales_tax_rate"]', function() {
            recalcTotals();
        });


        // Function to create subtotal body
        window.createSubtotalBody = function(amount) {
            var $tbody = $('<tbody data-repeater-item>');
            $tbody.append('<input type="hidden" name="item_ids[]" value="">');
            $tbody.append('<input type="hidden" name="items[][type]" value="subtotal">');
            $tbody.append('<input type="hidden" name="items[][amount]" value="' + (amount || '0.00') + '">');
            $tbody.append(
                '<tr class="subtotal-row"><td colspan="7" style="text-align: right; font-weight: bold;">Subtotal</td><td style="text-align: right; font-weight: bold;">' +
                (amount || '0.00') + '</td><td></td><td></td></tr>');
            return $tbody;
        };

        // Function to create text body
        window.createTextBody = function(description) {
            var $tbody = $('<tbody data-repeater-item>');
            $tbody.append('<input type="hidden" name="item_ids[]" value="">');
            $tbody.append('<input type="hidden" name="items[][type]" value="text">');
            $tbody.append('<input type="hidden" name="items[][description]" value="' + (description || '') + '">');
            $tbody.append('<tr class="text-row"><td colspan="10" style="text-align: left;">' + (description || '') +
                '</td></tr>');
            return $tbody;
        };

        // Main totals calculation function
        function recalcTotals() {
            var grandSubtotal = 0; // sum of all line amounts (qty * price - discount + tax)
            var taxableSubtotal = 0; // sum of taxable line amounts
            var totalDiscount = 0; // total discount from discount controls

            // Calculate per-row amounts and sum them up
            $('#sortable-table').children('tbody').each(function() {
                var $body = $(this);
                var $productRow = $body.find('tr.product-row');
                if (!$productRow.length) return;


                // Get values from this row
                var quantity = parseFloat($productRow.find('.quantity').val()) || 0;
                var price = parseFloat($productRow.find('.price').val()) || 0;
                var discount = parseFloat($productRow.find('.discount').val()) || 0;
                var itemTaxPrice = parseFloat($productRow.find('.itemTaxPrice').val()) || 0;

                // Calculate line amount: (quantity * price) - discount + tax
                var lineAmount = (quantity * price) - discount + itemTaxPrice;

                // Update the amount display for this row
                $productRow.find('.amount').html(lineAmount.toFixed(2));

                // Add to grand subtotal
                grandSubtotal += lineAmount;

                // Check if taxable
                var isTaxable = $productRow.find('.form-check-input[type="checkbox"]').prop('checked');
                if (isTaxable) {
                    taxableSubtotal += lineAmount;
                }
            });

            // Tax from dropdown (applied to taxable subtotal)
            // Get the data-rate attribute from the selected option, not the value (which is the tax ID)
            var $selectedTax = $('select[name="sales_tax_rate"]').find(':selected');
            var taxRate = parseFloat($selectedTax.data('rate')) || 0;
            var totalTax = taxableSubtotal * taxRate / 100;

            // Discount from controls (applied to grand subtotal)
            var discountType = $('.discount-type-select').val();
            var discountValue = parseFloat($('.discount-input').val()) || 0;
            if (discountType === 'percent') {
                totalDiscount = grandSubtotal * (discountValue / 100);
            } else if (discountType === 'value') {
                totalDiscount = discountValue;
            }


            // Cap discount so it can't exceed subtotal
            if (totalDiscount > grandSubtotal) {
                totalDiscount = grandSubtotal;
            }

            // Calculate final totals
            var finalSubtotal = grandSubtotal - totalDiscount;
            var grandTotal = finalSubtotal + totalTax;

            // Update display
            $('.subTotal').text(grandSubtotal.toFixed(2));
            $('.taxableSubtotal').text(taxableSubtotal.toFixed(2));
            $('.totalDiscount').text(totalDiscount.toFixed(2));
            $('.totalTax').text(totalTax.toFixed(2));

            $('.totalAmount').text(grandTotal.toFixed(2));

            // For sales receipts, amount received = total, balance due = 0
            $('.amountReceived').text(grandTotal.toFixed(2));

            // Update hidden fields for form submission
            $('#hidden_subtotal').val(grandSubtotal.toFixed(2));
            $('#hidden_taxable_subtotal').val(taxableSubtotal.toFixed(2));
            $('#hidden_total_discount').val(totalDiscount.toFixed(2));
            $('#hidden_total_tax').val(totalTax.toFixed(2));
            $('#hidden_sales_tax_amount').val(totalTax.toFixed(2));
            $('#hidden_total_amount').val(grandTotal.toFixed(2));
            $('.balanceDue').text('0.00');
        }
        // Recalculate when the discount UI changes
        $(document).on('change', '.discount-type-select', recalcTotals);
        $(document).on('keyup change', '.discount-input', recalcTotals);

        // Clear all lines button handler
        $(document).on('click', '#clear-lines', function() {
            if (confirm('Are you sure you want to clear all lines?')) {
                // Remove all tbody elements except the first one (template)
                $('#sortable-table tbody:not(:first)').remove();

                // Reset the first row to default values
                var $firstRow = $('#sortable-table tbody:first tr.product-row');
                $firstRow.find('.item').val('');
                $firstRow.find('.pro_description').val('');
                $firstRow.find('.quantity').val('');
                $firstRow.find('.price').val('');
                $firstRow.find('.discount').val('0');
                $firstRow.find('.amount').html('0.00');
                $firstRow.find('.itemTaxPrice').val('0.00');
                $firstRow.find('.itemTaxRate').val('0.00');
                $firstRow.find('.tax').val('');
                $firstRow.find('.form-check-input[type="checkbox"]').prop('checked', false);

                // Recalculate totals
                recalcTotals();
                renumberInvoiceLines();
            }
        });

        // Frontend validation to prevent saving without customer selection
        $(document).on('submit', '#invoice-form', function(e) {
            var customerId = $('#customer').val();

            if (!customerId || customerId === '' || customerId === '__add__') {
                e.preventDefault();
                alert('{{ __('Please select a customer before saving.') }}');
                $('#customer').focus();
                return false;
            }

            return true;
        });
    </script>
@endpush
