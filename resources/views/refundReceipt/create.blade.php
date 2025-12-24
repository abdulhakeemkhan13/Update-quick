@extends('layouts.admin')
@section('page-title')
    {{ __('Refund Receipt Create') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('refund-receipt.index') }}">{{ __('Refund Receipt') }}</a></li>
    <li class="breadcrumb-item">{{ __('Refund Receipt Create') }}</li>
@endsection

@push('css-page')
    <style>
        /* Custom Design from invoiceDesign.php */
        .invoice-container {
            background: #ffffff;
            max-width: 100%;
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .invoice-card {
            background: #fff;
            margin: 24px auto;
            max-width: 100%;
            width: 95%;
            padding: 0px;
        }

        /* Fixed Top Header (QuickBooks Style) */
        .fixed-top-header {
            position: sticky;
            top: 0;
            background: #fff;
            border-bottom: 1px solid #e4e4e7;
            box-shadow: 0 -2px 4px rgba(0, 0, 0, .1);
            z-index: 1000;
            padding: 0;
        }

        .header-top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            /* padding: 20px 40px 20px 40px; */
            padding: 12px 40px;
        }

        .header-bottom-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0px 32px 8px 32px;
            border-top: 1px solid #f0f0f0;
        }

        .invoice-label {
            font-size: 28px;
            font-weight: 500;
            color: #000000;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-tabs-custom {
            display: flex;
            gap: 35px;
            border: none;
            margin: 0;
        }

        .nav-tab {
            padding: 8px 0;
            font-size: 18px;
            font-weight: 500;
            color: #6B6C72;
            text-decoration: none;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
            cursor: pointer;
            background: none;
            border-top: none;
            border-left: none;
            border-right: none;
        }

        .nav-tab:hover {
            color: #0077c5;
        }

        .nav-tab.active {
            color: #0b7e3a;
            border-bottom-color: #0b7e3a;
            font-weight: 700;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header-action-btn {
            padding: 6px 12px;
            font-weight: 700;
            font-size: 18px;
            color: #6B6C72;
            background: #fff;
            border: 1px solid #ffffff;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .header-action-btn:hover {
            background: rgba(107, 108, 114, 0.1);
            border-color: rgba(107, 108, 114, 0.1);
        }

        .close-button {
            background: none;
            border: none;
            font-size: 24px;
            color: #6b6c72;
            cursor: pointer;
            padding: 4px 8px;
            line-height: 1;
            transition: color 0.2s;
        }

        .close-button:hover {
            color: #393a3d;
        }

        /* Header Section */
        .invoice-header {
            background: #fff;
            padding: 24px 32px 24px 32px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .header-left {
            display: flex;
            flex-direction: column;
            align-items: flex-start !important;
            gap: 12px;
            flex: 1;
        }

        .invoice-title {
            font-size: 35px;
            font-weight: 700;
            color: #0077c5;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.2;
        }

        .company-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .company-name {
            font-size: 16px;
            font-weight: 700;
            color: #393a3d;
        }

        .company-details-row {
            display: flex;
            gap: 16px;
            align-items: center;
            font-size: 14px;
            color: #393a3d;
        }

        .company-email {
            color: #393a3d;
        }

        .company-address {
            color: #393a3d;
        }

        .edit-company-link {
            color: #0077c5;
            font-size: 14px;
            text-decoration: none;
            margin-top: 4px;
            display: inline-block;
            cursor: pointer;
            font-weight: 400;
        }

        .edit-company-link:hover {
            text-decoration: underline;
        }

        .header-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 24px;
        }

        .balance-due {
            text-align: right;
            font-size: 14px;
            color: #393a3d;
        }

        .balance-label {
            color: #6b6c72;
            margin-right: 4px;
        }

        .balance-amount {
            font-weight: 700;
            color: #393a3d;
        }

        .logo-section {
            width: 350px;
            height: 100px;
            border: 1px dashed #c4c4c4;
            border-radius: 4px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #fff;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }

        .logo-section:hover {
            border-color: #2ca01c;
            background: #f4f5f8;
        }

        .add-logo-text {
            color: #0077c5;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 2px;
        }

        .logo-size-limit {
            color: #6b6c72;
            font-size: 12px;
        }

        /* Customer Section */
        .customer-section {
            background: #ebf4fa;
            padding: 24px 32px 24px 32px;
            /* border-bottom: 1px solid #e4e4e7; */
        }

        .customer-row {
            display: flex;
            gap: 24px;
            margin-bottom: 16px;
        }

        .customer-field {
            flex: 2;
        }

        .invoice-field {
            flex: 1;
        }

        .form-label {
            display: block;
            font-size: 13px;
            color: #393a3d;
            margin-bottom: 6px;
            font-weight: 500;
        }

        .form-control,
        .form-select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #c4c4c4;
            border-radius: 4px;
            font-size: 14px;
            background: #fff;
            color: #393a3d;
            transition: all 0.2s;
        }

        .form-control:focus,
        .form-select:focus {
            outline: none;
            border-color: #2ca01c !important;
            box-shadow: 0 0 0 3px rgba(0, 119, 197, 0.1);
        }

        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24'%3E%3Cpath fill='%23393a3d' d='M12.014 16.018a1 1 0 0 1-.708-.294L5.314 9.715A1.001 1.001 0 0 1 6.73 8.3l5.286 5.3 5.3-5.285a1 1 0 0 1 1.413 1.416l-6.009 5.995a1 1 0 0 1-.706.292'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 20px;
            padding-right: 36px;
        }

        textarea.form-control {
            resize: none;
            font-family: inherit;
            line-height: 1.5;
        }

        .link-button {
            color: #0077c5;
            font-size: 13px;
            text-decoration: none;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            margin-top: 8px;
            display: inline-block;
        }

        .link-button:hover {
            text-decoration: underline;
        }

        /* Transaction Details Grid */
        .transaction-details {
            padding: 0px 32px 24px 32px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            background: #ebf4fa;
            border-bottom: 1px solid #e4e4e7;
        }

        .detail-group {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .field-group {
            display: flex;
            flex-direction: column;
        }

        .terms-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .terms-label {
            font-size: 13px;
            color: #393a3d;
            font-weight: 500;
            min-width: 60px;
        }

        .terms-field {
            flex: 1;
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

        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .product-table thead th {
            padding: 12px 8px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #393a3d;
            border-bottom: 2px solid #e4e4e7;
            background: #fff;
        }

        .product-table thead th:first-child {
            width: 30px;
        }

        .product-table thead th:nth-child(2) {
            width: 30px;
        }

        .product-table thead th:nth-child(3) {
            width: 40px;
        }

        .product-table thead th:last-child {
            text-align: right;
            width: 120px;
        }

        .product-table tbody td {
            padding: 12px 8px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
        }

        .product-table tbody td:last-child {
            text-align: right;
        }

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

        .line-number {
            font-size: 13px;
            color: #6b6c72;
        }

        .delete-icon {
            color: #c4c4c4;
            cursor: pointer;
            display: none;
            transition: color 0.2s;
        }

        .product-table tbody tr:hover .delete-icon {
            display: block;
        }

        .delete-icon:hover {
            color: #e81500;
        }

        /* Table Actions */
        .table-actions {
            display: flex;
            gap: 12px;
            margin-bottom: 32px;
        }

        .btn-action {
            padding: 8px 16px;
            border: 1px solid #0077c5;
            background: #fff;
            color: #0077c5;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-action:hover {
            background: #ebf4fa;
        }

        .btn-action.split-button {
            padding-right: 36px;
            position: relative;
        }

        /* Bottom Section */
        .bottom-section {
            padding: 24px 0px;
            background: #ffffff;
        }

        .left-section {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .info-field {
            display: flex;
            flex-direction: column;
        }

        .info-field label {
            font-size: 13px;
            font-weight: 600;
            color: #393a3d;
            margin-bottom: 8px;
        }

        .info-text {
            font-size: 13px;
            color: #6b6c72;
            line-height: 1.6;
            padding: 12px;
            background: #f7f8fa;
            border-radius: 4px;
            border: 1px solid #e4e4e7;
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

        /* Totals Section */
        .totals-section {
            display: flex;
            flex-direction: column;
            gap: 16px;
            padding-top: 24px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            font-size: 14px;
        }

        .total-row.subtotal {
            color: #393a3d;
            padding-bottom: 12px;
        }

        .total-row.final {
            font-size: 16px;
            font-weight: 600;
            color: #393a3d;
            padding-top: 16px;
            border-top: 2px solid #e4e4e7;
        }

        .input-right {
            text-align: right;
        }

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

        /* Button Styles */
        .btn {
            padding: 10px 24px;
            border-radius: 20px;
            /* Rounded corners like QBO */
            font-size: 14px;
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
            background: #f4f5f8;
            border-color: #393a3d;
        }

        .btn-primary {
            background: #2ca01c;
            /* QBO Green */
            color: #fff;
            border-color: #2ca01c;
        }

        /* Keep Review & Send button green in all states */
        .invoice-footer .btn-primary,
        .invoice-footer .btn-primary:hover,
        .invoice-footer .btn-primary:focus,
        .invoice-footer .btn-primary:active,
        .invoice-footer .btn-primary.active,
        .invoice-footer .show>.btn-primary.dropdown-toggle {
            background-color: #00892E !important;
            /* your green */
            border-color: #00892E !important;
            color: #fff !important;
            box-shadow: none;
        }

        /* optional: slightly darker on hover if you want */
        .invoice-footer .btn-primary:hover {
            background-color: #108000 !important;
            border-color: #108000 !important;
        }


        .btn-primary:hover {
            background: #108000;
            border-color: #108000;
        }

        /* Split Button Specifics */
        .btn-group {
            position: relative;
            display: inline-flex;
            vertical-align: middle;
        }

        .btn-group .btn {
            border-radius: 20px 0 0 20px;
        }

        .btn-group .btn+.dropdown-toggle-split {
            border-radius: 0 20px 20px 0;
            padding-left: 10px;
            padding-right: 10px;
            border-left: 1px solid rgba(255, 255, 255, 0.3);
            margin-left: -1px;
        }

        .btn-group .btn-secondary+.dropdown-toggle-split {
            border-left: 1px solid #c4c4c4;
        }

        .dropdown-menu {
            position: absolute;
            bottom: 100%;
            left: 0;
            z-index: 1000;
            display: none;
            min-width: 10rem;
            padding: 0.5rem 0;
            margin: 0.125rem 0 0;
            font-size: 14px;
            color: #212529;
            text-align: left;
            list-style: none;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid rgba(0, 0, 0, 0.15);
            border-radius: 0.25rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropup .dropdown-menu {
            top: auto;
            bottom: 100%;
            margin-top: 0;
            margin-bottom: 0.125rem;
        }

        .dropdown-item {
            display: block;
            width: 100%;
            padding: 0.5rem 1.5rem;
            clear: both;
            font-weight: 400;
            color: #212529;
            text-align: inherit;
            white-space: nowrap;
            background-color: transparent;
            border: 0;
            cursor: pointer;
        }

        .dropdown-item:hover,
        .dropdown-item:focus {
            color: #16181b;
            text-decoration: none;
            background-color: #f8f9fa;
        }

        /* Footer Styles */
        .invoice-footer {
            background: #ffffff;
            /* pure white like QBO */
            padding: 12px 32px;
            border-top: 1px solid #e4e4e7;
            box-shadow: 0 -2px 4px rgba(0, 0, 0, .1);
            display: grid;
            grid-template-columns: auto 1fr auto;
            /* left / center / right */
            align-items: center;
            margin-top: 24px;
            /* small gap above footer */
            position: sticky;
            bottom: 0;
            z-index: 100;
        }

        .footer-left {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .footer-center {
            text-align: center;
        }

        .footer-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
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

        .header-right-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* QBO-like circular icon button */
        .help-button {
            background: #ffffff;
            border: 1px solid #d0d0d5;
            border-radius: 999px;
            padding: 4px;
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #6b6c72;
            transition: background 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        }

        .help-button:hover {
            background: #f4f5f8;
            border-color: #b0b1b5;
            box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.04);
        }

        .logo-wrapper {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        /* hidden input – we trigger it via JS */
        .logo-input {
            display: none;
        }

        /* image stays inside dashed box */
        .logo-preview {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        /* top-right little X to remove logo */
        .logo-remove-btn {
            position: absolute;
            top: -8px;
            right: -8px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: none;
            background: #ffffff;
            box-shadow: 0 0 4px rgba(0, 0, 0, 0.2);
            font-size: 14px;
            line-height: 1;
            padding: 0;
            cursor: pointer;
            color: #6b6c72;
        }

        .logo-remove-btn:hover {
            background: #f4f5f8;
        }

        .logo-section {
            width: 350px;
            height: 100px;
            border: 1px dashed #c4c4c4;
            border-radius: 4px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #fff;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }

        /* when a logo is present – no dashed box, shrink to logo */
        .logo-section.has-logo {
            border: none;
            background: transparent;
            width: auto;
            height: auto;
            padding: 0;
        }

        /* QBO-style: when you hover, you see the dotted box again */
        .logo-wrapper:hover .logo-section.has-logo {
            border: 1px dashed #c4c4c4;
            background: #fff;
        }

        /* logo itself */
        .logo-preview {
            max-width: 120px;
            max-height: 120px;
            object-fit: contain;
        }

        /* X button: only on hover */
        .logo-remove-btn {
            position: absolute;
            top: -8px;
            right: -8px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: none;
            background: #ffffff;
            box-shadow: 0 0 4px rgba(0, 0, 0, 0.2);
            font-size: 14px;
            line-height: 1;
            padding: 0;
            cursor: pointer;
            color: #6b6c72;
            opacity: 0;
            pointer-events: none;
            /* transition: opacity 0.15s ease; */
        }

        .logo-wrapper:hover .logo-remove-btn {
            opacity: 1;
            pointer-events: auto;
        }

        /* Totals Section */
        .totals-section {
            display: flex;
            flex-direction: column;
            gap: 16px;
            padding-top: 24px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            font-size: 14px;
        }

        .total-row.subtotal {
            color: #393a3d;
            padding-bottom: 12px;
        }

        .total-row.final {
            font-size: 16px;
            font-weight: 600;
            color: #393a3d;
            padding-top: 16px;
            border-top: 2px solid #e4e4e7;
        }

        .input-right {
            text-align: right;
        }

        /* label + small icon next to "Discount" (QBO-style) */
        .total-row-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        /* small circular icon button (move discount before/after tax) */
        .discount-toggle-btn {
            background: #ffffff;
            border: 1px solid #d0d0d5;
            border-radius: 999px;
            width: 28px;
            height: 28px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #6b6c72;
            transition: background 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .discount-toggle-btn:hover {
            background: #f4f5f8;
            border-color: #b0b1b5;
            box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.04);
        }

        /* "Select sales tax rate" dropdown width */
        .totals-tax-rate-select {
            min-width: 220px;
        }

        /* "See the math" link + tax amount alignment */
        .sales-tax-right {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .sales-tax-info-icon {
            font-size: 12px;
            color: #6b6c72;
            margin-left: 4px;
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

        /* Discount row: button on the LEFT of "Discount" */
        .total-row.discount-row {
            align-items: center;
        }

        .discount-label-wrapper {
            display: flex;
            align-items: center;
            gap: 0px;
            margin-left: -48px;
        }

        /* QBO-style rotate icon button */
        .discount-position-btn {
            border: none;
            background: #ffffff;
            padding: 2px;
            /* border-radius: 999px; */
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #6b6c72;
            transition: background 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
            /* border: 1px solid #d0d0d5; */
        }

        .discount-position-btn:hover {
            background: #f4f5f8;
            border-color: #b0b1b5;
            box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.04);
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

        /* “See the math” under sales tax, aligned right */
        .see-math-link {
            align-self: flex-end;
            margin-top: -4px;
        }

        /* “Edit totals” under invoice total, aligned right */
        .edit-totals-link {
            align-self: flex-end;
            margin-top: 4px;
        }

        /* Delete icon: always visible */
        .delete-icon {
            cursor: pointer;
            display: inline-block;
            color: #6b6c72;
            transition: color 0.2s;
        }

        /* Optional: only change color on row hover */
        .product-table tbody tr:hover .delete-icon {
            color: #e81500;
        }

        /* QBO-style grid */

        .product-table {
            width: 100%;
            border-collapse: collapse;
            border-top: 1px solid #e4e4e7;
            border-bottom: 1px solid #e4e4e7;
        }

        .product-table thead th,
        .product-table tbody td {
            padding: 12px 8px;
            font-size: 13px;
            vertical-align: middle;

            /* horizontal: solid */
            border-bottom: 1px solid #e4e4e7;

            /* vertical: dotted */
            border-right: 1px dotted #d8d8d8;
        }

        /* left edge */
        .product-table thead th:first-child,
        .product-table tbody td:first-child {
            border-left: 1px solid #e4e4e7;
        }

        /* right edge (delete col) */
        .product-table thead th:last-child,
        .product-table tbody td:last-child {
            border-right: 1px solid #e4e4e7;
        }

        /* OPTIONAL: remove only the very last row’s bottom border */
        .product-table>tbody:last-of-type tr:last-child td {
            border-bottom: 2px solid #BABEC5;
        }

        .product-table {
            width: 100%;
            border-collapse: separate;
            /* important for dotted verticals */
            border-spacing: 0;
        }

        /* headers */
        .product-table thead th {
            padding: 12px 8px;
            font-size: 13px;
            font-weight: 600;
            color: #393a3d;
            background: #fff;
            border-bottom: 1px solid #e4e4e7;
            /* solid horizontal line */
        }

        /* cells */
        .product-table tbody td {
            padding: 12px 8px;
            vertical-align: middle;
            border-bottom: 1px solid #e4e4e7;
            /* solid horizontal line between rows */
        }

        /* dotted vertical lines between columns */
        .product-table thead th+th,
        .product-table tbody td+td {
            border-left: 1px dotted #e4e4e7;
        }

        /* remove outer left border on the first column */
        .product-table thead th:first-child,
        .product-table tbody td:first-child {
            border-left: none;
        }

        /* Qty, Rate, Amount -> right aligned (6,7,8) */
        .product-table thead th:nth-child(6),
        .product-table thead th:nth-child(7),
        .product-table thead th:nth-child(8),
        .product-table tbody td:nth-child(6),
        .product-table tbody td:nth-child(7),
        .product-table tbody td:nth-child(8) {
            text-align: right;
        }

        /* Tax + Delete -> centered (9,10) */
        .product-table thead th:nth-child(9),
        .product-table thead th:nth-child(10),
        .product-table tbody td:nth-child(9),
        .product-table tbody td:nth-child(10) {
            text-align: center;
        }
    </style>
@endpush

@push('script-page')
    <script src="{{ asset('js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('js/jquery.repeater.min.js') }}"></script>
    @push('script-page')
        <script src="{{ asset('js/jquery-ui.min.js') }}"></script>
        <script src="{{ asset('js/jquery.repeater.min.js') }}"></script>

        <script>
            $(function() {
                // Handle close button - redirect to return_url if present, otherwise invoice index
                $('#invoiceCloseBtn').on('click', function(e) {
                    e.preventDefault();
                    var urlParams = new URLSearchParams(window.location.search);
                    var returnUrl = urlParams.get('return_url');
                    if (returnUrl) {
                        location.href = decodeURIComponent(returnUrl);
                    } else {
                        location.href = '{{ route("invoice.index") }}';
                    }
                });

                $('#invoice-form').on('submit', function(e) {
                    // Validate customer selection
                    var customerId = $('#customer_id').val();
                    if (!customerId || customerId === '' || customerId === '__add__') {
                        e.preventDefault();
                        alert('{{ __('Please select a customer before submitting the invoice.') }}');
                        $('#customer_id').focus();
                        return false;
                    }

                    // Validate that at least one product is selected
                    var hasProduct = false;
                    $('#sortable-table').children('tbody').each(function() {
                        var $body = $(this);
                        var $productRow = $body.find('tr.product-row');
                        if ($productRow.length) {
                            var itemId = $productRow.find('select.item').val();
                            if (itemId && itemId !== '' && itemId !== '--') {
                                hasProduct = true;
                                return false; // break the loop
                            }
                        }
                    });

                    if (!hasProduct) {
                        e.preventDefault();
                        alert('{{ __('Please add at least one product/service to the invoice.') }}');
                        return false;
                    }

                    var lines = [];

                    $('#sortable-table').children('tbody').each(function() {
                        var $body = $(this);

                        // 1) PRODUCT ROW
                        var $productRow = $body.find('tr.product-row');
                        if ($productRow.length) {
                            var $row = $productRow;

                            // Check if this item came from an estimate (or other source later)
                            var estimateId = $body.attr('data-estimate-id') || null;
                            var proposalProductId = $body.attr('data-proposal-product-id') || null;

                            var lineItem = {
                                type: 'product',
                                item_id: $row.find('select.item').val() || null,
                                description: $row.find('.pro_description').val() || '',
                                quantity: parseFloat($row.find('.quantity').val()) || 0,
                                price: parseFloat($row.find('.price').val()) || 0,
                                amount: parseFloat($row.find('.amount').val()) || 0,
                                is_taxable: $row.find('.form-check-input[type="checkbox"]')
                                    .prop('checked') ? 1 : 0,
                                tax_ids: ($row.find('.tax').val() || '').split(',').filter(
                                    Boolean),
                                item_tax_rate: parseFloat($row.find('.itemTaxRate').val()) || 0
                            };

                            // Add line_type, estimate_id and proposal_product_id if item came from an estimate
                            if (estimateId) {
                                lineItem.line_type = 'estimate';
                                lineItem.estimate_id = estimateId;
                                lineItem.proposal_product_id = proposalProductId;
                            }

                            lines.push(lineItem);

                            return; // continue to next tbody
                        }

                        // 2) SUBTOTAL ROW (tbody created by createSubtotalBody)
                        var $subtotalRow = $body.find('tr.subtotal-row');
                        if ($subtotalRow.length) {
                            var subtotalEstimateId = $body.attr('data-estimate-id') || null;
                            var subtotalProposalProductId = $body.attr('data-proposal-product-id') || null;
                            var subtotalLine = {
                                type: 'subtotal',
                                label: 'Subtotal',
                                amount: parseFloat($subtotalRow.find('.subtotal-amount')
                                    .text()) || 0
                            };
                            // Add line_type, estimate_id and proposal_product_id if from an estimate
                            if (subtotalEstimateId) {
                                subtotalLine.line_type = 'estimate';
                                subtotalLine.estimate_id = subtotalEstimateId;
                                subtotalLine.proposal_product_id = subtotalProposalProductId;
                            }
                            lines.push(subtotalLine);
                            return;
                        }

                        // 3) TEXT ROW (tbody created by createTextBody)
                        var $textRow = $body.find('tr.text-row');
                        if ($textRow.length) {
                            var textEstimateId = $body.attr('data-estimate-id') || null;
                            var textProposalProductId = $body.attr('data-proposal-product-id') || null;
                            var textLine = {
                                type: 'text',
                                text: $textRow.find('input[type="text"]').val() || ''
                            };
                            // Add line_type, estimate_id and proposal_product_id if from an estimate
                            if (textEstimateId) {
                                textLine.line_type = 'estimate';
                                textLine.estimate_id = textEstimateId;
                                textLine.proposal_product_id = textProposalProductId;
                            }
                            lines.push(textLine);
                            return;
                        }
                    });

                    // store JSON string in hidden input
                    $('#items_payload').val(JSON.stringify(lines));
                    // let form submit normally
                });
            });

            $(document).on('change', '#customer_id', function() {
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
                        $('#edit-cutomer-link').removeClass('d-none');
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
                                //shipping
                                var shippingAddress = '';
                                if (data.customer.shipping_name) {
                                    shippingAddress += data.customer.shipping_name + '\n';
                                }
                                if (data.customer.shipping_address) {
                                    shippingAddress += data.customer.shipping_address + '\n';
                                }
                                if (data.customer.shipping_city) {
                                    shippingAddress += data.customer.shipping_city;
                                    if (data.customer.shipping_state) {
                                        shippingAddress += ', ' + data.customer.shipping_state;
                                    }
                                    if (data.customer.shipping_zip) {
                                        shippingAddress += ' ' + data.customer.shipping_zip;
                                    }
                                    shippingAddress += '\n';
                                }
                                if (data.customer.shipping_country) {
                                    shippingAddress += data.customer.shipping_country;
                                }

                                $('textarea[name="ship_to"]').val(shippingAddress.trim());

                                // Show the ship-to section
                                $('#ship-to-section').show();
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
        </script>

        <script>
            // Open full-screen modal on load
            $(document).ready(function() {
                var invoiceModal = new bootstrap.Modal(document.getElementById('invoice-modal'), {
                    backdrop: 'static',
                    keyboard: false
                });
                invoiceModal.show();

                // initial totals
                recalcTotals();
            });

            // Renumber the "#" column for product rows only
            function renumberInvoiceLines() {
                var line = 1;
                $('#sortable-table').children('tbody').each(function() {
                    var $ln = $(this).find('.product-row .line-number');
                    if ($ln.length) {
                        $ln.text(line++);
                    }
                });
            }

            // ----- Helpers used everywhere -----

            // get numeric amount from a product row (from the Amount input)
            function getRowAmount($row) {
                var v = parseFloat($row.find('.amount').val());
                return isNaN(v) ? 0 : v;
            }

            // compute a single row's amount from qty * rate
            function recalcRowAmount($row) {
                var qty = parseFloat($row.find('.quantity').val()) || 0;
                var rate = parseFloat($row.find('.price').val()) || 0;
                // (add line-discount here in the future if needed)
                var amount = qty * rate;
                $row.find('.amount').val(amount.toFixed(2));
            }

            // recompute all in-table "Subtotal" lines
            // each subtotal = sum of product rows since the previous subtotal
            function recalcSubtotals() {
                var runningSegment = 0;

                $('#sortable-table').children('tbody').each(function() {
                    var $body = $(this);
                    var $productRow = $body.find('tr.product-row');
                    var $subtotalRow = $body.find('tr.subtotal-row');

                    if ($productRow.length) {
                        runningSegment += getRowAmount($productRow);
                    } else if ($subtotalRow.length) {
                        $subtotalRow.find('.subtotal-amount')
                            .text(runningSegment.toFixed(2));
                        runningSegment = 0;
                    }
                });
            }

            // main totals in bottom-right box
            function recalcTotals() {
                var grandSubtotal = 0; // all product rows
                var taxableSubtotal = 0; // only rows with tax checkbox checked
                var totalDiscount = 0; // (line discounts not used right now)

                $('#sortable-table').children('tbody').each(function() {
                    var $body = $(this);
                    var $productRow = $body.find('tr.product-row');

                    if (!$productRow.length) return;

                    var amount = getRowAmount($productRow);
                    grandSubtotal += amount;

                    var isTaxable = $productRow
                        .find('.form-check-input[type="checkbox"]')
                        .prop('checked');

                    if (isTaxable) {
                        taxableSubtotal += amount;
                    }
                });

                // sales tax rate select (value should be numeric percentage)
                var taxRate = parseFloat($('select[name="tax_id"] option:selected').data('rate')) || 0;
                var totalTax = taxableSubtotal * taxRate / 100;
                var totalAmount = grandSubtotal - totalDiscount + totalTax;

                // update bottom totals
                $('.subTotal').text(grandSubtotal.toFixed(2));
                $('.taxableSubtotal').text(taxableSubtotal.toFixed(2));
                $('.totalDiscount').text(totalDiscount.toFixed(2));
                $('.totalTax').text(totalTax.toFixed(2));
                $('.totalAmount').text(totalAmount.toFixed(2));

                // Update hidden inputs
                $('.subtotal_hidden').val(grandSubtotal.toFixed(2));
                $('.taxable_subtotal_hidden').val(taxableSubtotal.toFixed(2));
                $('.total_discount_hidden').val(totalDiscount.toFixed(2));
                $('.total_tax_hidden').val(totalTax.toFixed(2));
                $('.sales_tax_amount_hidden').val(totalTax.toFixed(2));
                $('.total_amount_hidden').val(totalAmount.toFixed(2));
                $('.tax_rate_hidden').val(taxRate);

                // update all subtotal rows inside table
                recalcSubtotals();
            }
        </script>

        <script>
            var selector = "body";

            if ($(selector + " .repeater").length) {

                // Sortable is applied to the repeater LIST (each <tbody> is one row group)
                var $dragAndDrop = $("body .repeater [data-repeater-list]").sortable({
                    handle: '.sort-handler',
                    items: 'tbody',
                    axis: 'y',
                    stop: function() {
                        // after drag finishes
                        renumberInvoiceLines();
                        recalcTotals();
                    }
                });

                var $repeater = $(selector + ' .repeater').repeater({
                    initEmpty: false,
                    defaultValues: {
                        'status': 1
                    },
                    show: function() {
                        var $newBody = $(this);
                        $newBody.slideDown();

                        // existing MultiFile / select2 logic
                        var file_uploads = $newBody.find('input.multi');
                        if (file_uploads.length) {
                            $newBody.find('input.multi').MultiFile({
                                max: 3,
                                accept: 'png|jpg|jpeg',
                                max_size: 2048
                            });
                        }
                        if ($('.select2').length) {
                            $('.select2').select2();
                        }

                        // position new row after a specific tbody (row-menu "Add product")
                        if (window.qbInsertAfterTbody && $(window.qbInsertAfterTbody).length) {
                            $newBody.insertAfter($(window.qbInsertAfterTbody));
                        }
                        // position new row BEFORE a specific tbody (e.g. before Subtotal/Text lines)
                        if (window.qbInsertBeforeTbody && $(window.qbInsertBeforeTbody).length) {
                            $newBody.insertBefore($(window.qbInsertBeforeTbody));
                        }

                        // duplicate content from source row if requested
                        if (window.qbDuplicateSource && $(window.qbDuplicateSource).length) {
                            var $srcBody = $(window.qbDuplicateSource);
                            var $srcRow = $srcBody.find('.product-row');
                            var $newRow = $newBody.find('.product-row');

                            $newRow.find('select.item').val($srcRow.find('select.item').val());
                            $newRow.find('textarea.pro_description')
                                .val($srcRow.find('textarea.pro_description').val());
                            $newRow.find('input.quantity')
                                .val($srcRow.find('input.quantity').val());
                            $newRow.find('input.price')
                                .val($srcRow.find('input.price').val());
                            $newRow.find('input.amount')
                                .val($srcRow.find('input.amount').val());

                            var taxChecked = $srcRow.find('.form-check-input[type="checkbox"]').prop('checked');
                            $newRow.find('.form-check-input[type="checkbox"]').prop('checked', taxChecked);
                        }

                        // 🔹 NEW: fill new product row from a suggestion (Estimate)
                        if (window.qbProposalToAdd) {
                            var p = window.qbProposalToAdd;
                            var $row = $newBody.find('.product-row');
                            var qty = p.quantity != null ? p.quantity : 1;
                            var priceN = Number(p.price || 0);

                            // Set product dropdown without triggering AJAX overwrite
                            if (p.product_id) {
                                $row.find('select.item')
                                    .data('ignore-ajax', true)
                                    .val(p.product_id)
                                    .trigger('change')
                                    .data('ignore-ajax', false);
                            }

                            $row.find('.pro_description').val(p.description || '');
                            $row.find('.quantity').val(qty);
                            $row.find('.price').val(priceN.toFixed(2));

                            // Store estimate_id and proposal_product_id on tbody if item came from an estimate
                            if (p.estimate_id) {
                                $newBody.attr('data-estimate-id', p.estimate_id);
                            }
                            if (p.proposal_product_id) {
                                $newBody.attr('data-proposal-product-id', p.proposal_product_id);
                            }

                            // compute amount from qty * rate
                            recalcRowAmount($row);
                        }

                        // reset helpers
                        window.qbInsertAfterTbody = null;
                        window.qbInsertBeforeTbody = null;
                        window.qbDuplicateSource = null;
                        window.qbProposalToAdd = null;

                        renumberInvoiceLines();
                        recalcTotals();
                    },

                    hide: function(deleteElement) {
                        if (confirm('Are you sure you want to delete this element?')) {
                            $(this).slideUp(deleteElement);
                            $(this).remove();
                            renumberInvoiceLines();
                            recalcTotals();
                        }
                    },

                    ready: function(setIndexes) {
                        $dragAndDrop.on('sortstop', function() {
                            setIndexes();
                            renumberInvoiceLines();
                            recalcTotals();
                        });
                    },
                    isFirstItemUndeletable: true
                });

                var value = $(selector + " .repeater").attr('data-value');
                if (typeof value != 'undefined' && value.length != 0) {
                    value = JSON.parse(value);
                    $repeater.setList(value);
                }
            }
        </script>


        <script>
            // when a product/service is selected
            $(document).on('change', '.item', function() {
                // If we are programmatically setting the value (e.g. from suggestion), skip AJAX
                if ($(this).data('ignore-ajax')) return;

                var iteams_id = $(this).val();
                var url = $(this).data('url');
                var el = $(this);

                // if user clears the select, wipe the row and recalc
                if (!iteams_id) {
                    var $row = el.closest('tr.product-row');
                    $row.find('.pro_description').val('');
                    $row.find('.quantity').val('');
                    $row.find('.price').val('');
                    $row.find('.amount').val('0.00');
                    el.closest('tbody').find('.form-check-input[type="checkbox"]').prop('checked', false);
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
                        // support both JSON string and already-parsed object
                        if (typeof data === 'string') {
                            try {
                                data = JSON.parse(data);
                            } catch (e) {
                                console.error('Could not parse product response', e);
                                return;
                            }
                        }

                        var item = data;

                        var $row = el.closest('tr.product-row');

                        $row.find('.quantity').val(1);
                        $row.find('.price').val(item.product.sale_price);
                        $row.find('.pro_description').val(item.product.description);

                        // optional tax badges / ids (kept so your backend still gets tax IDs)
                        var totalItemTaxRate = 0;
                        var taxesHtml = '';
                        var taxIds = [];

                        if (item.taxes && item.taxes.length) {
                            for (var i = 0; i < item.taxes.length; i++) {
                                taxesHtml += '<span class="badge bg-primary mt-1 mr-2">' +
                                    item.taxes[i].name + ' (' + item.taxes[i].rate + '%)</span>';
                                taxIds.push(item.taxes[i].id);
                                totalItemTaxRate += parseFloat(item.taxes[i].rate);
                            }
                        } else {
                            taxesHtml = '-';
                        }

                        $row.find('.taxes').html(taxesHtml);
                        $row.find('.tax').val(taxIds.join(','));
                        $row.find('.itemTaxRate').val(totalItemTaxRate.toFixed(2));

                        // compute the row amount from qty * rate and update totals
                        recalcRowAmount($row);
                        recalcTotals();
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching product data:', error);
                    }
                });
            });

            // qty or rate changed -> recompute that row's amount, then totals
            $(document).on('keyup change', '.quantity, .price', function() {
                var $row = $(this).closest('tr.product-row');
                recalcRowAmount($row);
                recalcTotals();
            });

            // user edits Amount directly -> just recompute totals
            $(document).on('keyup change', '.amount', function() {
                recalcTotals();
            });

            // tax checkbox toggled -> affects taxable subtotal
            $(document).on('change', '.product-row .form-check-input[type="checkbox"]', function() {
                recalcTotals();
            });

            // sales tax rate dropdown changed
            $(document).on('change', 'select[name="tax_id"]', function() {
                recalcTotals();
            });

            // when a repeater row is removed via data-repeater-delete
            $(document).on('click', '[data-repeater-delete]', function() {
                // actual DOM removal happens in repeater.hide()
                // just wait a tick then renumber + recalc
                setTimeout(function() {
                    renumberInvoiceLines();
                    recalcTotals();
                }, 200);
            });
        </script>

        <script>
            function toggleRecurringPanel() {
                const on = $('#recurring').val() === 'yes';
                $('#recurring-options').toggleClass('d-none', !on);

                // mark fields required when on
                $('#recurring_when, #recurring_start_date, #recurring_repeat, #recurring_every_n')
                    .prop('required', on);

                // default behavior for "when to charge"
                handleWhenToCharge();
                handleEndType();
            }

            function handleWhenToCharge() {
                const when = $('#recurring_when').val();
                if (when === 'now') {
                    const today = new Date().toISOString().slice(0, 10);
                    $('#recurring_start_date').val(today).prop('disabled', true);
                    $('#start-required').addClass('d-none');
                } else {
                    $('#recurring_start_date').prop('disabled', false);
                    // visual "Required" hint if empty
                    if (!$('#recurring_start_date').val()) {
                        $('#start-required').removeClass('d-none');
                    } else {
                        $('#start-required').addClass('d-none');
                    }
                }
            }

            function handleEndType() {
                const type = $('#recurring_end_type').val();
                if (type === 'by') {
                    $('#end-by-wrap').removeClass('d-none');
                    $('#recurring_end_date').prop('required', true);
                } else {
                    $('#end-by-wrap').addClass('d-none');
                    $('#recurring_end_date').prop('required', false).val('');
                }
            }

            // init + listeners
            $(document).on('change', '#recurring', toggleRecurringPanel);
            $(document).on('change', '#recurring_when', handleWhenToCharge);
            $(document).on('change keyup', '#recurring_start_date', handleWhenToCharge);
            $(document).on('change', '#recurring_end_type', handleEndType);

            // run once in case of validation errors returning to page
            $(function() {
                toggleRecurringPanel();
            });
        </script>
        <script>
            /** ===== Minimal Schedule Preview =====
             * Renders:
             *  - under Start date:  "Next Invoice Date YYYY-MM-DD."
             *  - under Repeat:      "Last invoice date YYYY-MM-DD"
             *
             * Rules:
             *  - "Next invoice date" is ALWAYS start date + 12 months (1 year)
             *    (independent of the selected repeat) — per the example:
             *      start = 2025-01-01 -> next = 2026-01-01 even if user switches to "monthly".
             *  - "Last invoice date" uses the selected repeat interval and count
             *    (e.g., monthly/quarterly/6months/yearly with every_n = count).
             */

            // --- Helpers ---
            function addMonthsNoOverflow(date, months) {
                const d = new Date(date.getTime());
                const day = d.getDate();
                d.setDate(1);
                d.setMonth(d.getMonth() + months);
                // snap to last day if original day doesn't exist in target month
                const lastDay = new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate();
                d.setDate(Math.min(day, lastDay));
                return d;
            }

            function toISO(d) {
                // Format as YYYY-MM-DD (avoid TZ drift)
                const tz = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
                return tz.toISOString().slice(0, 10);
            }

            function monthsForRepeat(repeat) {
                switch (repeat) {
                    case 'monthly':
                        return 1;
                    case 'quarterly':
                        return 3; // typical calendar quarterly = 3 months
                    case '6months':
                        return 6;
                    case 'yearly':
                        return 12;
                    default:
                        return 1;
                }
            }

            function ensurePreviewHolders() {
                // small under Start date
                if (!$('#schedule-summary').length) {
                    $('<small id="schedule-summary" class="text-muted d-block mt-1"></small>')
                        .insertAfter('#recurring_start_date');
                }
                // small under Repeat
                if (!$('#schedule-preview').length) {
                    $('<small id="schedule-preview" class="text-muted d-block mt-1"></small>')
                        .insertAfter('#recurring_repeat');
                }
            }

            function computeSchedulePreview() {
                ensurePreviewHolders();

                const startVal = $('#recurring_start_date').val(); // e.g. "2025-01-01"
                const repeat = $('#recurring_repeat').val(); // monthly|quarterly|6months|yearly
                const everyRaw = $('#recurring_every_n').val(); // count
                let count = parseInt(everyRaw, 10);

                if (!startVal) {
                    $('#schedule-summary').text('');
                    $('#schedule-preview').text('');
                    return;
                }
                if (isNaN(count) || count < 1) count = 1;

                const start = new Date(startVal + 'T00:00:00');


                // --- Last invoice date: based on repeat + count ---
                // If count = 1 -> last = start; otherwise add (count - 1) * interval
                const stepMonths = monthsForRepeat(repeat);
                // --- Next invoice date: ALWAYS start + 12 months (1 year) ---
                const nextDate = addMonthsNoOverflow(start, stepMonths);
                $('#schedule-summary').text('Next Invoice Date ' + toISO(nextDate) + '.');
                let lastDate = new Date(start.getTime());
                if (count > 1) {
                    lastDate = addMonthsNoOverflow(start, stepMonths * (count));
                }
                $('#schedule-preview').text('Last invoice date ' + toISO(lastDate));


                // --- Next invoice date: based on start + 12 months ---

            }

            // Hook into changes on only the relevant fields (simple version)
            $(document).on('change keyup',
                '#recurring_start_date, #recurring_repeat, #recurring_every_n',
                computeSchedulePreview
            );

            // Init on load
            $(function() {
                computeSchedulePreview();
            });
        </script>

        <script>
            $(document).ready(function() {
                var currentSelect = null;

                function openAddNewModal($select) {
                    if ($select.val() !== '__add__') return;
                    $select.val(''); // reset dropdown
                    currentSelect = $select; // save reference
                    var url = $select.data('create-url');
                    var title = $select.data('create-title') || 'Create New';

                    // prevent duplicate modal
                    if ($('#globalAddNewModal').length) {
                        $('#globalAddNewModal').modal('show');
                        return;
                    }

                    var $modal = $(`
            <div class="modal fade" id="globalAddNewModal" tabindex="-1">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">${title}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">Loading...</div>
                </div>
              </div>
            </div>
        `);

                    $('body').append($modal);

                    $.get(url, function(html) {
                        $modal.find('.modal-body').html(html);

                        // z-index stacking
                        var zIndex = 1070 + ($('.modal:visible').length * 10);
                        $modal.css('z-index', zIndex);
                        setTimeout(function() {
                            $('.modal-backdrop').last().css('z-index', zIndex - 1).addClass(
                                'modal-stack');
                        }, 0);

                        $modal.modal('show');
                    });

                    $modal.on('hidden.bs.modal', function() {
                        $modal.remove();
                    });
                }

                // Detect "Add New" selection
                $(document).on('change', 'select', function() {
                    var $select = $(this);
                    if ($select.val() === '__add__') {
                        openAddNewModal($select);
                    }
                });

                // AJAX submit for dynamic modal
                $(document).off('submit', '#globalAddNewModal form').on('submit', '#globalAddNewModal form', function(
                    e) {
                    e.preventDefault();
                    var $form = $(this);
                    var $modal = $form.closest('#globalAddNewModal');

                    // Find the select that triggered this modal
                    var $select = currentSelect;

                    $.ajax({
                        url: $form.attr('action'),
                        method: $form.attr('method') || 'POST',
                        data: $form.serialize(),
                        success: function(response) {
                            if (response.success) {
                                // 🔹 Insert new option before the "Add New" of the same select
                                var $addNewOption = $select.find('option[value="__add__"]').first();
                                var $newOption = $('<option>', {
                                    value: response.data.id,
                                    text: response.data.name
                                });

                                if ($addNewOption.length) {
                                    $select.append($newOption);
                                    // $newOption.insertBefore($addNewOption);
                                } else {
                                    $select.append($newOption);
                                }

                                $select.val(response.data.id).trigger('change');
                                $modal.modal('hide');
                            } else {
                                alert(response.message || 'Something went wrong!');
                            }
                        },
                        error: function(xhr) {
                            if (xhr.status === 422) {
                                var errors = xhr.responseJSON.errors;
                                $form.find('.invalid-feedback').remove();
                                $.each(errors, function(key, msgs) {
                                    $form.find('[name="' + key + '"]').after(
                                        `<small class="invalid-feedback text-danger">${msgs[0]}</small>`
                                    );
                                });
                            } else {
                                alert('Server error!');
                            }
                        }
                    });
                });

            });
        </script>
        <script>
            $(function() {
                var $logoInput = $('#company_logo_input');
                var $logoButton = $('#company_logo_button');
                var $logoPreview = $('.logo-preview');
                var $addText = $('.add-logo-text');
                var $sizeText = $('.logo-size-limit');
                var $removeBtn = $('#company_logo_remove');

                // open file dialog
                $logoButton.on('click', function() {
                    $logoInput.trigger('click');
                });

                // user chose a file
                $logoInput.on('change', function() {
                    var file = this.files[0];
                    if (!file) return;

                    // optional size check
                    if (file.size > 1024 * 1024) {
                        alert('Max size is 1 MB');
                        $logoInput.val('');
                        return;
                    }

                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $logoPreview.attr('src', e.target.result).removeClass('d-none');
                        $addText.addClass('d-none');
                        $sizeText.addClass('d-none');
                        $removeBtn.removeClass('d-none');

                        // 🔹 switch box into “has logo” mode (no dashed UI, QBO style)
                        $logoButton.addClass('has-logo');
                    };
                    reader.readAsDataURL(file);
                });

                // remove logo
                $removeBtn.on('click', function() {
                    $logoInput.val('');
                    $logoPreview.attr('src', '').addClass('d-none');
                    $addText.removeClass('d-none');
                    $sizeText.removeClass('d-none');
                    $removeBtn.addClass('d-none');

                    // back to empty state with dashed box and text
                    $logoButton.removeClass('has-logo');
                });
            });
        </script>
        <script>
            $(function() {
                var subtotalLabel = @json(__('Subtotal'));
                var textPlaceholder = @json(__('Enter your text or leave blank as a divider'));

                // 6-dot icon
                var dotsSvg =
                    '<svg width="20" height="20" viewBox="0 0 24 24" fill="#c4c4c4">' +
                    '<circle cx="8" cy="6" r="2"></circle>' +
                    '<circle cx="16" cy="6" r="2"></circle>' +
                    '<circle cx="8" cy="12" r="2"></circle>' +
                    '<circle cx="16" cy="12" r="2"></circle>' +
                    '<circle cx="8" cy="18" r="2"></circle>' +
                    '<circle cx="16" cy="18" r="2"></circle>' +
                    '</svg>';

                // delete cell for subtotal/text rows (no data-repeater-delete)
                var deleteCellHtml =
                    '<td class="text-center">' +
                    '<span class="delete-icon qb-special-delete" title="Delete line">' +
                    '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">' +
                    '<path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"></path>' +
                    '</svg>' +
                    '</span>' +
                    '</td>';

                // ---------- helpers to build special bodies ----------

                function createSubtotalBody(initialAmount) {
                    var amountText = (typeof initialAmount === 'string') ?
                        initialAmount :
                        '0.00';

                    var $tbody = $('<tbody class="special-body subtotal-body"></tbody>');
                    var $row = $('<tr class="subtotal-row"></tr>');

                    $row.append(
                        '<td><div style="opacity:0;">' + dotsSvg + '</div></td>' +
                        '<td><div class="drag-handle sort-handler">' + dotsSvg + '</div></td>' +
                        '<td></td>' +
                        '<td></td>' +
                        '<td></td>' +
                        '<td></td>' +
                        '<td style="font-size:13px;font-weight:600;color:#393a3d;">' +
                        subtotalLabel +
                        '</td>' +
                        '<td class="input-right subtotal-amount" ' +
                        'style="font-size:13px;color:#393a3d;">' + amountText + '</td>' +
                        '<td></td>' +
                        deleteCellHtml
                    );

                    $tbody.append($row);
                    return $tbody;
                }

                // allow optional initial text
                function createTextBody(initialText) {
                    var $tbody = $('<tbody class="special-body text-body"></tbody>');
                    var $row = $('<tr class="text-row"></tr>');

                    $row.append(
                        '<td><div style="opacity:0;">' + dotsSvg + '</div></td>' +
                        '<td><div class="drag-handle sort-handler">' + dotsSvg + '</div></td>' +
                        '<td></td>' +
                        '<td colspan="5">' +
                        '<input type="text" name="extra_lines_text[]" ' +
                        'class="form-control" ' +
                        'placeholder="' + textPlaceholder + '" ' +
                        'style="border:none;background:transparent;padding-left:0;box-shadow:none;" />' +
                        '</td>' +
                        '<td></td>' +
                        deleteCellHtml
                    );

                    $tbody.append($row);

                    // if text passed from proposal, set it
                    if (initialText) {
                        $tbody.find('input[type="text"]').val(initialText);
                    }

                    return $tbody;
                }

                // expose for other scripts (suggestion panel)
                window.qbCreateSubtotalBody = createSubtotalBody;
                window.qbCreateTextBody = createTextBody;

                // ----- bottom split button ("Add product or service" ▼) -----

                $('#new-line-menu-toggle').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $('#new-line-menu').toggleClass('show');
                });

                $(document).on('click', function(e) {
                    if (!$(e.target).closest('#new-line-menu, #new-line-menu-toggle, .qb-row-menu-wrapper')
                        .length) {
                        $('#new-line-menu').removeClass('show');
                        $('.qb-row-menu').removeClass('show');
                    }
                });

                $('#add-subtotal-line').on('click', function() {
                    $('#new-line-menu').removeClass('show');
                    var $tbody = createSubtotalBody('0.00');
                    $('#sortable-table').append($tbody);
                    recalcTotals();
                });

                $('#add-text-line').on('click', function() {
                    $('#new-line-menu').removeClass('show');
                    var $tbody = createTextBody();
                    $('#sortable-table').append($tbody);
                    renumberInvoiceLines();
                    recalcTotals();
                });

                // Clear all lines
                $('#clear-lines').on('click', function() {
                    var $tbodies = $('#sortable-table').find('tbody');
                    var $first = $tbodies.first();

                    if (!confirm('{{ __('Clear all lines?') }}')) return;

                    $tbodies.slice(1).remove();

                    $first.find('select.item').val('');
                    $first.find('textarea.pro_description').val('');
                    $first.find('input.quantity').val('');
                    $first.find('input.price').val('');
                    $first.find('input.amount').val('0.00');
                    $first.find('.form-check-input[type="checkbox"]').prop('checked', false);

                    renumberInvoiceLines();
                    recalcTotals();
                });

                // delete subtotal/text rows (those without data-repeater-delete)
                $(document).on('click', '.delete-icon', function(e) {
                    if (!$(this).is('[data-repeater-delete]')) {
                        e.preventDefault();
                        $(this).closest('tbody').remove();
                        renumberInvoiceLines();
                        recalcTotals();
                    }
                });

                // ----- per-row + menu (circle button on each row) -----

                $(document).on('click', '.qb-row-menu-btn', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var $menu = $(this).siblings('.qb-row-menu');
                    $('.qb-row-menu').not($menu).removeClass('show');
                    $menu.toggleClass('show');
                });

                // Add product or service under this row
                $(document).on('click', '.row-add-product', function(e) {
                    e.preventDefault();
                    var $tbody = $(this).closest('tbody');

                    window.qbInsertAfterTbody = $tbody;
                    window.qbDuplicateSource = null;

                    $('.qb-row-menu').removeClass('show');
                    $('[data-repeater-create]').trigger('click');
                });

                // Add subtotal under this row
                $(document).on('click', '.row-add-subtotal', function(e) {
                    e.preventDefault();
                    var $tbody = $(this).closest('tbody');
                    var $newBody = createSubtotalBody('0.00');
                    $newBody.insertAfter($tbody);
                    $('.qb-row-menu').removeClass('show');
                    recalcTotals();
                });

                // Duplicate this line
                $(document).on('click', '.row-duplicate-line', function(e) {
                    e.preventDefault();
                    var $tbody = $(this).closest('tbody');

                    window.qbInsertAfterTbody = $tbody;
                    window.qbDuplicateSource = $tbody;

                    $('.qb-row-menu').removeClass('show');
                    $('[data-repeater-create]').trigger('click');
                });

                // Add text line under this row
                $(document).on('click', '.row-add-text', function(e) {
                    e.preventDefault();
                    var $tbody = $(this).closest('tbody');
                    var $newBody = createTextBody();

                    $newBody.insertAfter($tbody);
                    $('.qb-row-menu').removeClass('show');
                    renumberInvoiceLines();
                    recalcTotals();
                });
            });
        </script>
    @endpush

@endpush

@section('content')
    <div class="modal fade" style="background-color: #ffffff;" id="invoice-modal" tabindex="-1"
        aria-labelledby="invoiceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="invoice-container">
                    {{ Form::open(['route' => 'refund-receipt.store', 'id' => 'invoice-form', 'files' => true]) }}
                    <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">

                    {{-- Fixed Top Header (QuickBooks Style) --}}
                    <div class="fixed-top-header">
                        {{-- First Row: Invoice Label, Help and Close Button --}}
                        <div class="header-top-row">
                            <div class="invoice-label">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    color="currentColor" width="24px" height="24px" focusable="false" aria-hidden="true">
                                    <path fill="currentColor"
                                        d="M13.007 7a1 1 0 0 0-1 1L12 12a1 1 0 0 0 1 1l3.556.006a1 1 0 0 0 0-2L14 11l.005-3a1 1 0 0 0-.998-1">
                                    </path>
                                    <path fill="currentColor"
                                        d="M19.374 5.647A8.94 8.94 0 0 0 13.014 3H13a8.98 8.98 0 0 0-8.98 8.593l-.312-.312a1 1 0 0 0-1.416 1.412l2 2a1 1 0 0 0 1.414 0l2-2a1 1 0 0 0-1.412-1.416l-.272.272A6.984 6.984 0 0 1 13 5h.012A7 7 0 0 1 13 19h-.012a7 7 0 0 1-4.643-1.775 1 1 0 1 0-1.33 1.494A9 9 0 0 0 12.986 21H13a9 9 0 0 0 6.374-15.353">
                                    </path>
                                </svg>
                                {{ __('Refund Receipt') }}
                            </div>

                            <div class="header-right-controls">
                                {{-- Help icon button (QBO style) --}}
                                <button aria-label="Help" class="help-button">
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

                                {{-- Close button (existing) --}}
                                <button type="button" class="close-button" id="invoiceCloseBtn" aria-label="Close">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        color="currentColor" width="24px" height="24px" focusable="false"
                                        aria-hidden="true">
                                        <path fill="currentColor"
                                            d="m13.432 11.984 5.3-5.285a1 1 0 1 0-1.412-1.416l-5.3 5.285-5.285-5.3A1 1 0 1 0 5.319 6.68l5.285 5.3L5.3 17.265a1 1 0 1 0 1.412 1.416l5.3-5.285L17.3 18.7a1 1 0 1 0 1.416-1.412z">
                                        </path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                    </div>

                    <div class="row">
                        {{-- MAIN INVOICE COLUMN (full width by default) --}}
                        <div class="col-md-12" id="invoice-main-col">
                            <div class="invoice-card">
                                {{-- Refund Receipt Header Section (QBO Style) --}}
                                <div class="refund-receipt-header" style="padding: 24px 32px; background: #ebf4fa;">
                                    {{-- Row 1: Customer, Email, Amount --}}
                                    <div class="row mb-3">
                                        <div class="col-md-3">
                                            <label for="customer_id" class="form-label" style="font-size: 13px; font-weight: 500;">{{ __('Customer') }}</label>
                                            {{ Form::select('customer_id', $customers, $customerId ?? '', [
                                                'class' => 'form-select',
                                                'id' => 'customer_id',
                                                'data-url' => route('invoice.customer'),
                                                'placeholder' => 'Choose a customer',
                                                'required' => 'required',
                                                'data-create-url' => route('customer.create'),
                                                'data-create-title' => __('Create New Customer'),
                                            ]) }}
                                        </div>
                                        <div class="col-md-4">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <label for="customer_email_field" class="form-label" style="font-size: 13px; font-weight: 500;">{{ __('Email') }}</label>
                                                <a href="javascript:void(0)" id="cc-bcc-toggle" style="font-size: 12px; color: #0077c5;">{{ __('Cc/Bcc') }}</a>
                                            </div>
                                            {{ Form::text('customer_email_display', '', [
                                                'class' => 'form-control',
                                                'id' => 'customer_email_field',
                                                'placeholder' => 'Email (Separate emails with a comma)',
                                            ]) }}
                                            {{ Form::hidden('customer_email', '', ['id' => 'customer_email']) }}
                                        </div>
                                        <div class="col-md-5 text-end">
                                            <div style="margin-top: 20px;">
                                                <label style="font-size: 12px; color: #6b6c72; text-transform: uppercase; letter-spacing: 0.5px;">{{ __('AMOUNT') }}</label>
                                                <div style="font-size: 28px; font-weight: 500; color: #393a3d;" id="header-amount-display">$0.00</div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Row 2: Billing Address, Refund Receipt Date, Location of Sale --}}
                                    <div class="row mb-3">
                                        <div class="col-md-3">
                                            <label for="billing_address" class="form-label" style="font-size: 13px; font-weight: 500;">{{ __('Billing Address') }}</label>
                                            {{ Form::textarea('billing_address', '', [
                                                'class' => 'form-control',
                                                'id' => 'billing_address',
                                                'rows' => 3,
                                                'style' => 'resize: none;',
                                            ]) }}
                                        </div>
                                        <div class="col-md-3">
                                            <label for="issue_date" class="form-label" style="font-size: 13px; font-weight: 500;">{{ __('Refund Receipt Date') }}</label>
                                            {{ Form::date('issue_date', date('Y-m-d'), [
                                                'class' => 'form-control',
                                                'id' => 'issue_date',
                                                'required' => 'required',
                                                'style' => 'width: 185px;',
                                            ]) }}
                                        </div>
                                        <div class="col-md-3 offset-md-3 text-end">
                                            <label for="location_of_sale" class="form-label" style="font-size: 13px; font-weight: 500;">{{ __('Location of Sale') }}</label>
                                            {{ Form::text('location_of_sale', Auth::user()->address ?? '123 Sierra Way, San Pablo CA 87999', [
                                                'class' => 'form-control',
                                                'id' => 'location_of_sale',
                                            ]) }}
                                        </div>
                                    </div>

                                    {{-- Row 3: Payment Method, Refund From, Link --}}
                                    <div class="row mb-2">
                                        <div class="col-md-3">
                                            <label for="payment_method" class="form-label" style="font-size: 13px; font-weight: 500;">{{ __('Payment method') }}</label>
                                            {{ Form::select('payment_method', $paymentMethods ?? ['' => 'Choose payment method', 'Cash' => 'Cash', 'Check' => 'Check', 'Credit Card' => 'Credit Card', 'Debit Card' => 'Debit Card', 'Bank Transfer' => 'Bank Transfer', 'Other' => 'Other'], null, [
                                                'class' => 'form-select',
                                                'id' => 'payment_method',
                                                'placeholder' => 'Choose payment method',
                                            ]) }}
                                        </div>
                                        <div class="col-md-3">
                                            <label for="refund_from" class="form-label" style="font-size: 13px; font-weight: 500;">{{ __('Refund From') }}</label>
                                            {{ Form::select('refund_from', $bankAccounts ?? ['' => 'Choose an account'], null, [
                                                'class' => 'form-select',
                                                'id' => 'refund_from',
                                                'placeholder' => 'Choose an account',
                                            ]) }}
                                        </div>
                                        <div class="col-md-6">
                                            <div style="margin-top: 28px;">
                                                <a href="javascript:void(0)" style="color: #0077c5; font-size: 14px; text-decoration: none;">{{ __('Refund payments in QuickBooks') }}</a>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Hidden fields for compatibility --}}
                                    {{ Form::hidden('category_id', 1) }}
                                    {{ Form::hidden('ref_number', '') }}
                                    {{ Form::hidden('recurring', 'no', ['id' => 'recurring']) }}
                                </div>

                                {{-- Recurring Options (Hidden by default) --}}
                                <div class="transaction-details d-none" id="recurring-options"
                                    style="background: #eef2f5; border-top: 0;">
                                    <div class="detail-group">
                                        <div class="field-group">
                                            <label class="form-label">{{ __('When to charge') }}</label>
                                            {{ Form::select('recurring_when', ['future' => 'Select future date', 'now' => 'Immediately'], null, ['class' => 'form-select', 'id' => 'recurring_when']) }}
                                        </div>
                                        <div class="field-group">
                                            <label class="form-label">{{ __('Start date') }}</label>
                                            {{ Form::date('recurring_start_date', null, ['class' => 'form-control', 'id' => 'recurring_start_date']) }}
                                            <small class="text-danger d-none"
                                                id="start-required">{{ __('Required') }}</small>
                                        </div>
                                    </div>
                                    <div class="detail-group">
                                        <div class="field-group">
                                            <label class="form-label">{{ __('Repeat') }}</label>
                                            {{ Form::select('recurring_repeat', ['monthly' => 'Monthly', 'quarterly' => 'Quarterly', '6months' => '6 Months', 'yearly' => 'Yearly'], 'monthly', ['class' => 'form-select', 'id' => 'recurring_repeat']) }}
                                            <small id="next-date-preview" class="text-muted d-block mt-1"></small>
                                        </div>
                                        <div class="field-group">
                                            <label class="form-label">{{ __('Invoice Count') }}</label>
                                            {{ Form::number('recurring_every_n', 1, ['class' => 'form-control', 'id' => 'recurring_every_n', 'min' => 1]) }}
                                        </div>
                                    </div>
                                </div>

                                {{-- Product Section --}}
                                <div class="product-section repeater">
                                    <h2 class="section-heading">{{ __('Product or service') }}</h2>

                                    <table class="product-table" id="sortable-table" data-repeater-list="items">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th></th>
                                                <th>#</th>
                                                <th>{{ __('Product/service') }}</th>
                                                <th>{{ __('Description') }}</th>
                                                <th>{{ __('Qty') }}</th>
                                                <th>{{ __('Rate') }}</th>
                                                {{-- Discount column removed --}}
                                                <th>{{ __('Amount') }}</th>
                                                <th>{{ __('Tax') }}</th>
                                                <th></th>
                                            </tr>
                                        </thead>

                                        <tbody data-repeater-item>
                                            <tr class="product-row">
                                                <td>
                                                    <div class="qb-row-menu-wrapper">
                                                        {{-- blue circular + button --}}
                                                        <button type="button" class="qb-row-menu-btn"
                                                            aria-haspopup="true" aria-expanded="false">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                                viewBox="0 0 24 24" color="currentColor" width="20px"
                                                                height="20px" focusable="false" aria-hidden="true"
                                                                tabindex="0" class="">
                                                                <path fill="currentColor"
                                                                    d="M15 11h-2V9a1 1 0 0 0-2 0v2H9a1 1 0 0 0 0 2h2v2a1 1 0 0 0 2 0v-2h2a1 1 0 0 0 0-2">
                                                                </path>
                                                                <path fill="currentColor"
                                                                    d="M12.015 2H12a10 10 0 1 0-.015 20H12a10 10 0 0 0 .015-20M12 20h-.012A8 8 0 0 1 12 4h.012A8 8 0 0 1 12 20">
                                                                </path>
                                                            </svg>
                                                        </button>

                                                        {{-- per-row dropdown menu --}}
                                                        <ul class="dropdown-menu qb-row-menu">
                                                            <li>
                                                                <button type="button"
                                                                    class="dropdown-item row-add-product">
                                                                    {{ __('Add product or service') }}
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <button type="button"
                                                                    class="dropdown-item row-add-subtotal">
                                                                    {{ __('Add subtotal') }}
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <button type="button"
                                                                    class="dropdown-item row-duplicate-line">
                                                                    {{ __('Duplicate') }}
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <button type="button" class="dropdown-item row-add-text">
                                                                    {{ __('Add text') }}
                                                                </button>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </td>
                                                <style>
                                                    /* Per-row + menu (QBO style) */
                                                    .qb-row-menu-wrapper {
                                                        position: relative;
                                                        display: inline-block;
                                                    }

                                                    .qb-row-menu-btn {
                                                        width: 24px;
                                                        height: 24px;
                                                        border-radius: 999px;
                                                        border: 1px solid #d0d0d5;
                                                        background: #ffffff;
                                                        padding: 0;
                                                        display: inline-flex;
                                                        align-items: center;
                                                        justify-content: center;
                                                        cursor: pointer;
                                                        color: #0077c5;
                                                        transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
                                                    }

                                                    .qb-row-menu-btn:hover {
                                                        background: #0077c5;
                                                        border-color: #0077c5;
                                                        color: #ffffff;
                                                    }

                                                    .qb-row-menu {
                                                        min-width: 190px;
                                                    }
                                                </style>
                                                <td>
                                                    <div class="drag-handle sort-handler">
                                                        <svg width="20" height="20" viewBox="0 0 24 24"
                                                            fill="currentColor">
                                                            <circle cx="8" cy="6" r="2"></circle>
                                                            <circle cx="16" cy="6" r="2"></circle>
                                                            <circle cx="8" cy="12" r="2"></circle>
                                                            <circle cx="16" cy="12" r="2"></circle>
                                                            <circle cx="8" cy="18" r="2"></circle>
                                                            <circle cx="16" cy="18" r="2"></circle>
                                                        </svg>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="line-number">1</span>
                                                </td>
                                                <td>
                                                    {{ Form::select('item', $product_services, '', [
                                                        'class' => 'form-select item',
                                                        'placeholder' => 'Select a product/service',
                                                        'data-url' => route('invoice.product'),
                                                        'required' => 'required',
                                                    ]) }}
                                                </td>
                                                <td>
                                                    {{ Form::textarea('description', null, [
                                                        'class' => 'form-control pro_description',
                                                        'rows' => '1',
                                                        'placeholder' => '',
                                                    ]) }}
                                                </td>
                                                <td>
                                                    {{ Form::text('quantity', '', [
                                                        'class' => 'form-control input-right quantity',
                                                        'placeholder' => '',
                                                        'required' => 'required',
                                                    ]) }}
                                                </td>
                                                <td>
                                                    {{ Form::text('price', '', [
                                                        'class' => 'form-control input-right price',
                                                        'placeholder' => '',
                                                        'required' => 'required',
                                                    ]) }}
                                                </td>
                                                <td>
                                                    <input type="text" name="amount"
                                                        class="form-control input-right amount" value="0.00">
                                                </td>

                                                <td>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" value="">
                                                    </div>
                                                    {{ Form::hidden('tax', '', ['class' => 'form-control tax']) }}
                                                    {{ Form::hidden('itemTaxPrice', '', ['class' => 'form-control itemTaxPrice']) }}
                                                    {{ Form::hidden('itemTaxRate', '', ['class' => 'form-control itemTaxRate']) }}
                                                </td>
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
                                                        text-align: left;
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
                                                <td class="delete-cell">
                                                    <span class="delete-icon" title="Delete line"
                                                        data-repeater-delete="">
                                                        <svg width="20" height="20" viewBox="0 0 24 24"
                                                            fill="currentColor">
                                                            <path
                                                                d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z">
                                                            </path>
                                                        </svg>
                                                    </span>
                                                </td>




                                            </tr>
                                        </tbody>
                                    </table>

                                    <div class="table-actions"
                                        style="display:flex;align-items:center;gap:8px;margin:12px 0 24px;">

                                        {{-- QBO-like split button --}}
                                        <div class="btn-group"
                                            style="position:relative;display:inline-flex;vertical-align:middle;">
                                            {{-- main: Add product or service --}}
                                            <button type="button" class="btn-action split-button" data-repeater-create
                                                style="
                                                        display:inline-flex;
                                                        align-items:center;
                                                        justify-content:center;
                                                        padding:6px 14px;
                                                        border-radius:3px 0 0 3px;
                                                        border:1px solid #c7c7c7;
                                                        background:#f0f0f0;
                                                        font-size:12px;
                                                        font-weight:400;
                                                        color:#000;
                                                        line-height:1.4;
                                                    ">
                                                {{ __('Add product or service') }}
                                            </button>
                                            {{-- small down arrow button --}}
                                            <button type="button" class="btn-action" id="new-line-menu-toggle"
                                                style="
                                                        display:inline-flex;
                                                        align-items:center;
                                                        justify-content:center;
                                                        padding:0 8px;
                                                        border-radius:0 3px 3px 0;
                                                        border:1px solid #c7c7c7;
                                                        border-left:0;
                                                        background:#f0f0f0;
                                                        height:30px;
                                                    ">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" color="currentColor" width="20px"
                                                    height="20px" focusable="false" aria-hidden="true">
                                                    <path fill="currentColor"
                                                        d="M12.014 16.018a1 1 0 0 1-.708-.294L5.314 9.715A1.001 1.001 0 0 1 6.73 8.3l5.286 5.3 5.3-5.285a1 1 0 0 1 1.413 1.416l-6.009 5.995a1 1 0 0 1-.706.292">
                                                    </path>
                                                </svg>
                                            </button>
                                            {{-- dropdown menu (Add subtotal / Add text) --}}
                                            <ul class="dropdown-menu" id="new-line-menu"
                                                style="
                                            margin-top:4px;
                                            min-width:170px;
                                            font-size:13px;
                                        ">
                                                <li>
                                                    <button type="button" class="dropdown-item" id="add-subtotal-line">
                                                        {{ __('Add subtotal') }}
                                                    </button>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item" id="add-text-line">
                                                        {{ __('Add text') }}
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                        {{-- Clear all lines button --}}
                                        <button type="button" class="btn-action" id="clear-lines"
                                            style="
                                            padding:6px 14px;
                                            border-radius:3px;
                                            border:1px solid #c7c7c7;
                                            background:#f0f0f0;
                                            font-size:12px;
                                            font-weight:400;
                                            color:#000;
                                            line-height:1.4;
                                        ">
                                            {{ __('Clear all lines') }}
                                        </button>
                                    </div>


                                    {{-- Bottom Section --}}
                                    <div class="bottom-section row">
                                        <div class="left-section col-md-4">

                                            <div class="info-field">
                                                <label>{{ __('Message displayed on refund receipt') }}</label>
                                                {{ Form::textarea('note', '', [
                                                    'class' => 'form-control',
                                                    'rows' => 3,
                                                    'placeholder' => 'Thank you for your business and have a great day!',
                                                ]) }}
                                            </div>

                                            <div class="info-field">
                                                <label>{{ __('Message displayed on statement') }}</label>
                                                {{ Form::textarea('memo', '', [
                                                    'class' => 'form-control',
                                                    'rows' => 3,
                                                ]) }}
                                            </div>

                                            <style>
                                                .attachments-header {
                                                    display: flex;
                                                    justify-content: flex-end;
                                                    align-items: center;
                                                    margin-bottom: 4px;
                                                    font-size: 13px;
                                                    color: #393a3d;
                                                }

                                                #attachments-list {
                                                    margin-bottom: 8px;
                                                }

                                                .attachment-row {
                                                    display: flex;
                                                    align-items: center;
                                                    gap: 8px;
                                                    padding: 6px 8px;
                                                    border: 1px solid #e4e4e7;
                                                    border-radius: 4px;
                                                    margin-bottom: 4px;
                                                    font-size: 13px;
                                                    background: #ffffff;
                                                }

                                                .attachment-row .form-check {
                                                    margin-bottom: 0;
                                                }

                                                body.theme-6 .form-check-input:checked {
                                                    background-color: #2ca01c;
                                                    border-color: #2ca01c;
                                                }

                                                .form-check {
                                                    padding-left: 2.75em !important;
                                                }

                                                .attachment-name {
                                                    flex: 1;
                                                    white-space: nowrap;
                                                    overflow: hidden;
                                                    text-overflow: ellipsis;
                                                }

                                                .attachment-size {
                                                    font-size: 12px;
                                                    color: #6b6c72;
                                                }

                                                .attachment-remove {
                                                    border: none;
                                                    background: none;
                                                    cursor: pointer;
                                                    padding: 0 4px;
                                                    font-size: 16px;
                                                    line-height: 1;
                                                    color: #6b6c72;
                                                }

                                                .attachment-remove:hover {
                                                    color: #e81500;
                                                }
                                            </style>

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

                                            <div class="info-field">
                                                <label>{{ __('Attachments') }}</label>

                                                {{-- header with "Select all" - hidden until first file is added --}}
                                                <div class="attachments-header d-none" id="attachments-header">
                                                    <div class="form-check" style="padding-left: 2.75em !important;">
                                                        <input class="form-check-input" type="checkbox"
                                                            id="attachment_select_all">
                                                        <label class="form-check-label" for="attachment_select_all">
                                                            {{ __('Select All') }}
                                                        </label>
                                                    </div>
                                                </div>

                                                {{-- rows get injected here when files are added --}}
                                                <div id="attachments-list"></div>

                                                {{-- QBO-like drop zone --}}
                                                <div class="attachment-zone" id="attachment-zone">
                                                    <a href="#" class="attachment-link" id="attachment-add-link">
                                                        {{ __('Add attachment') }}
                                                    </a>
                                                    <div class="attachment-limit">{{ __('Max file size: 20 MB') }}</div>
                                                </div>

                                                {{-- we keep our hidden file inputs here --}}
                                                <div id="attachment-file-inputs" class="d-none"></div>
                                            </div>

                                        </div>

                                        <div class="col-md-4">
                                        </div>

                                        <div class="totals-section col-md-4">
                                            {{-- Subtotal --}}
                                            <div class="total-row subtotal">
                                                <span>{{ __('Subtotal') }}</span>
                                                <span class="subTotal">0.00</span>
                                            </div>

                                            {{-- Discount with rotate button on the LEFT, QBO style --}}
                                            <div class="total-row discount-row">
                                                <div class="discount-label-wrapper">
                                                    {{-- move-discount-before/after-tax button --}}
                                                    <button type="button"
                                                        aria-label="To move discounts before or after sales tax, select the icon."
                                                        class="discount-position-btn" data-bs-toggle="tooltip"
                                                        data-bs-placement="left"
                                                        title="To move discounts before or after sales tax, select the icon.">
                                                        <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24"
                                                            width="20" height="20" fill="currentColor">
                                                            <path
                                                                d="M15.7 16.28a1 1 0 10-1.416 1.412l.292.294-5.585-.01a1 1 0 01-1-1l.014-10a1 1 0 011-1l5.586.01-.294.292a1 1 0 101.412 1.416l2-2a1 1 0 000-1.414l-2-2a1 1 0 10-1.416 1.412l.292.294-5.574-.01a3 3 0 00-3 3l-.014 10a3 3 0 002.995 3l5.586.01-.294.292a1 1 0 101.412 1.416l2-2a1 1 0 000-1.414l-1.996-2z">
                                                            </path>
                                                        </svg>
                                                    </button>

                                                    <span>{{ __('Discount') }}</span>
                                                </div>

                                                {{-- total discount value --}}
                                                <span class="totalDiscount">0.00</span>
                                            </div>

                                            {{-- Taxable subtotal --}}
                                            <div class="total-row">
                                                <span>{{ __('Taxable subtotal') }}</span>
                                                <span class="taxableSubtotal">0.00</span>
                                            </div>

                                            {{-- Select sales tax rate --}}
                                            <div class="total-row select-tax-row">
                                                <span>{{ __('Select sales tax rate') }}</span>
                                                <span>
                                                    <select name="tax_id" class="form-select totals-tax-rate-select">
                                                        <option value="" data-rate="0">{{ __('Select a tax rate') }}</option>
                                                        @foreach($taxes as $tax)
                                                            <option value="{{ $tax->id }}" data-rate="{{ $tax->rate }}">{{ $tax->name }} ({{ $tax->rate }}%)</option>
                                                        @endforeach
                                                    </select>
                                                </span>
                                            </div>

                                            {{-- Sales tax row --}}
                                            <div class="total-row sales-tax-row">
                                                <span>{{ __('Sales tax') }}</span>
                                                <span class="totalTax">0.00</span>
                                            </div>

                                            <script>
                                                $(function() {
                                                    // enable Bootstrap tooltip on the icon (optional, but matches QBO)
                                                    if (typeof bootstrap !== 'undefined') {
                                                        $('[data-bs-toggle="tooltip"]').each(function() {
                                                            new bootstrap.Tooltip(this);
                                                        });
                                                    }

                                                    var discountBeforeTax = true; // default = under Subtotal

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

                                                    // make sure initial order is "Subtotal -> Discount -> Taxable subtotal..."
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
                                            <a href="#"
                                                class="link-button see-math-link">{{ __('Need help with sales tax?') }} {{ __('Learn more') }}</a>

                                            {{-- Total --}}
                                            <div class="total-row final" style="border-top: 1px solid #e4e4e7; padding-top: 16px; margin-top: 16px;">
                                                <span style="font-weight: 600;">{{ __('Total') }}</span>
                                                <span class="totalAmount" style="font-weight: 600;">$0.00</span>
                                            </div>

                                            {{-- Total Amount Refunded --}}
                                            <div class="total-row" style="padding-top: 8px;">
                                                <span>{{ __('Total Amount Refunded') }}</span>
                                                <span class="totalAmountRefunded">$0.00</span>
                                            </div>
                                        </div>


                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- SIDE SUGGESTION PANEL (hidden by default) --}}
                        <div class="col-md-3 d-none" id="suggestion-col">
                            <aside class="customer-suggestions-col" style="box-shadow: -12px 0 10px -10px #d4d7dc;">
                                <div class="suggestions-header">
                                    <div>
                                        <div class="d-flex align-self-end">
                                            <button class="arrow"
                                                style="
    border: none;
    background: 0 0;
    padding: 0;
"><svg
                                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                    color="currentColor" width="24px" height="24px" focusable="false"
                                                    aria-hidden="true">
                                                    <path fill="currentColor"
                                                        d="M15.009 19.022a1 1 0 0 1-.708-.294L8.31 12.72a1 1 0 0 1 0-1.415l6.009-5.991a1 1 0 0 1 1.414 1.416l-5.3 5.285 5.285 5.3a1 1 0 0 1-.708 1.706z">
                                                    </path>
                                                </svg></button>
                                            <h3>Suggested transactions</h3>
                                        </div>
                                        <p class="suggestions-subtitle">
                                            We’ve found one or more transactions linked to this customer.
                                            Select the ones you’d like to add to the invoice.
                                        </p>
                                    </div>
                                    <button type="button" class="suggestions-close"><svg
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            color="currentColor" width="24px" height="24px" focusable="false"
                                            aria-hidden="true" class="">
                                            <path fill="currentColor"
                                                d="m13.432 11.984 5.3-5.285a1 1 0 1 0-1.412-1.416l-5.3 5.285-5.285-5.3A1 1 0 1 0 5.319 6.68l5.285 5.3L5.3 17.265a1 1 0 1 0 1.412 1.416l5.3-5.285L17.3 18.7a1 1 0 1 0 1.416-1.412z">
                                            </path>
                                        </svg></button>
                                </div>

                                <div class="suggestions-toolbar">
                                    {{-- QBO-like filter button --}}
                                    <button type="button" class="suggestions-filter-btn">
                                        <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="24px"
                                            height="24px" fill="currentColor" color="#6B6C72">
                                            <path
                                                d="M11 21.72a2 2 0 01-2-2v-4.429L3.134 6.074A2 2 0 014.822 3h14.356a2 2 0 011.687 3.074L15 15.291v3.928a2 2 0 01-1.515 1.94l-2 .5c-.159.04-.321.06-.485.061zM4.822 5l6.022 9.463A1 1 0 0111 15v4.719l2-.5V15a1 1 0 01.156-.537L19.178 5H4.822z">
                                            </path>
                                        </svg>
                                        <span>Filter</span>
                                    </button>

                                    <button type="button" class="link-button suggestions-addall">
                                        Add all
                                    </button>

                                    {{-- Filter popup --}}
                                    <div class="suggestions-filter-panel" id="suggestions-filter-panel">
                                        <div class="suggestions-filter-header">
                                            <div class="suggestions-filter-actions">
                                                <button type="button" class="suggestions-filter-reset"
                                                    id="suggestions-filter-reset">
                                                    Reset
                                                </button>
                                                <button type="button" class="suggestions-filter-apply"
                                                    id="suggestions-filter-apply">
                                                    Apply filter
                                                </button>
                                            </div>
                                            <button type="button" class="suggestions-filter-close">&times;</button>
                                        </div>

                                        <div class="suggestions-filter-title">Filter by</div>

                                        <div class="suggestions-filter-row">
                                            <label for="suggestions-filter-type">Transaction type</label>
                                            <select id="suggestions-filter-type" class="form-select">
                                                <option value="all">All transactions</option>
                                                <option value="estimate">Estimates only</option>
                                                <option value="time">Billable time only</option>
                                                <option value="expense">Billable expenses only</option>
                                            </select>
                                        </div>

                                        <div class="suggestions-filter-row">
                                            <label for="suggestions-filter-date">Date</label>
                                            <select id="suggestions-filter-date" class="form-select">
                                                <option value="all">All dates</option>
                                                <option value="last30">Last 30 days</option>
                                                <option value="last365">Last 12 months</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div id="suggestions-list" style="padding: 0; overflow-y: auto;">
                                    <p style="font-size:12px;color:#6b6f73;">
                                        Select a customer to see suggested transactions.
                                    </p>
                                </div>
                            </aside>

                            {{-- Right-side panel styles --}}
                            <style>
                                /* main content scrolls separately from suggestions */
                                #invoice-main-col {
                                    max-height: calc(100vh - 120px);
                                    overflow-y: auto;
                                    padding-left: 0px !important;
                                    padding-right: 0px !important;
                                }

                                .customer-suggestions-col {
                                    max-height: calc(100vh - 120px);
                                    overflow-y: auto;
                                    border-left: 1px solid #e3e5e8;
                                    background: #fafbfc;
                                    padding: 16px;
                                    display: flex;
                                    flex-direction: column;
                                }

                                /* hide scrollbar but still allow scrolling */
                                .customer-suggestions-col::-webkit-scrollbar {
                                    width: 0;
                                }

                                .customer-suggestions-col {
                                    scrollbar-width: none;
                                    /* Firefox */
                                }

                                .suggestions-header {
                                    display: flex;
                                    justify-content: space-between;
                                    align-items: flex-start;
                                    margin-bottom: 12px;
                                }

                                .suggestions-header h3 {
                                    font-size: 16px;
                                    margin: 0 0 4px 0;
                                }

                                .suggestions-subtitle {
                                    font-size: 12px;
                                    color: #6b6f73;
                                    margin: 0;
                                }

                                .suggestions-close {
                                    border: none;
                                    background: transparent;
                                    font-size: 20px;
                                    cursor: pointer;
                                    line-height: 1;
                                }

                                .suggestions-toolbar {
                                    position: relative;
                                    display: flex;
                                    justify-content: space-between;
                                    align-items: center;
                                    margin-bottom: 12px;
                                }

                                /* QBO-style Filter pill */
                                .suggestions-filter-btn {
                                    display: inline-flex;
                                    align-items: center;
                                    gap: 6px;
                                    padding: 4px 10px;
                                    border-radius: 4px;
                                    border: none;
                                    background: transparent;
                                    font-size: 14px;
                                    font-weight: 700;
                                    color: #6b6f73;
                                    cursor: pointer;
                                }

                                .suggestions-filter-btn:hover {
                                    background: #f4f5f8;
                                    border-color: #b0b1b5;
                                }

                                .customer-suggestions-col .link-button {
                                    border: none;
                                    background: none;
                                    padding: 0;
                                    font-size: 12px;
                                    color: #0077c5;
                                    cursor: pointer;
                                }

                                .suggestion-card {
                                    border-radius: 4px;
                                    background: #ffffff;
                                    border: 1px solid #D4D7DC;
                                    padding: 12px;
                                    margin-bottom: 8px;
                                    font-size: 12px;
                                    box-shadow: 0 2px 8px 0 rgba(0, 0, 0, 0.2);
                                }

                                .suggestion-card-header {
                                    display: flex;
                                    justify-content: space-between;
                                    align-items: center;
                                    margin-bottom: 8px;
                                    font-weight: 600;
                                }

                                .suggestion-card-body dl {
                                    margin: 0;
                                }

                                .suggestion-card-body dt {
                                    font-weight: 600;
                                    display: inline-block;
                                    width: 70px;
                                }

                                .suggestion-card-body dd {
                                    display: inline-block;
                                    margin: 0 0 4px 0;
                                }

                                .suggestion-add-button {
                                    border: none;
                                    background: none;
                                    color: #0077c5;
                                    font-size: 12px;
                                    cursor: pointer;
                                    padding: 0;
                                    margin-top: 4px;
                                    align-self: flex-end;
                                }

                                /* Filter popup */
                                .suggestions-filter-panel {
                                    position: absolute;
                                    top: 32px;
                                    left: 0;
                                    width: 260px;
                                    background: #ffffff;
                                    border: 1px solid rgba(0, 0, 0, 0.15);
                                    border-radius: 4px;
                                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                                    padding: 10px 12px 12px;
                                    font-size: 12px;
                                    z-index: 10;
                                    display: none;
                                }

                                .suggestions-filter-panel.show {
                                    display: block;
                                }

                                .suggestions-filter-header {
                                    display: flex;
                                    justify-content: space-between;
                                    align-items: center;
                                    margin-bottom: 6px;
                                }

                                .suggestions-filter-actions {
                                    display: flex;
                                    gap: 8px;
                                }

                                .suggestions-filter-reset,
                                .suggestions-filter-apply {
                                    border-radius: 20px;
                                    border: 1px solid #c4c4c4;
                                    padding: 4px 10px;
                                    font-size: 12px;
                                    cursor: pointer;
                                    background: #fff;
                                }

                                .suggestions-filter-apply {
                                    background: #2ca01c;
                                    color: #fff;
                                    border-color: #2ca01c;
                                }

                                .suggestions-filter-apply:hover {
                                    background: #108000;
                                    border-color: #108000;
                                }

                                .suggestions-filter-close {
                                    border: none;
                                    background: none;
                                    font-size: 16px;
                                    cursor: pointer;
                                }

                                .suggestions-filter-title {
                                    font-weight: 600;
                                    margin-bottom: 6px;
                                }

                                .suggestions-filter-row {
                                    margin-bottom: 8px;
                                }

                                .suggestions-filter-row label {
                                    display: block;
                                    margin-bottom: 3px;
                                    font-weight: 500;
                                }

                                .suggestions-filter-row select {
                                    width: 100%;
                                    font-size: 12px;
                                    padding: 4px 8px;
                                }
                            </style>
                        </div>


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

                    {{-- totals[...] hidden inputs you already added --}}
                    {{-- Hidden inputs for totals --}}
                    <input type="hidden" name="subtotal" class="subtotal_hidden">
                    <input type="hidden" name="taxable_subtotal" class="taxable_subtotal_hidden">
                    <input type="hidden" name="total_discount" class="total_discount_hidden">
                    <input type="hidden" name="total_tax" class="total_tax_hidden">
                    <input type="hidden" name="sales_tax_amount" class="sales_tax_amount_hidden">
                    <input type="hidden" name="total_amount" class="total_amount_hidden">
                    <input type="hidden" name="tax_rate" class="tax_rate_hidden">

                    {{-- unified array for all lines --}}
                    <input type="hidden" name="items_payload" id="items_payload">


                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>
    <script>
        $(function() {
            const $customerSelect = $('#customer_id');
            const suggestionsUrl = $customerSelect.data('suggestions-url');
            const proposalEditUrlPattern = $customerSelect.data('proposal-edit-url');
            const $list = $('#suggestions-list');
            const $suggestionCol = $('#suggestion-col');
            const $mainCol = $('#invoice-main-col');
            const $filterPanel = $('#suggestions-filter-panel');

            // Store all suggestions by type
            let allSuggestions = {
                estimates: [],
                billable_time: [],
                billable_expenses: []
            };
            let filteredItems = [];

            function formatDate(raw) {
                if (!raw) return '';
                const d = new Date(raw);
                if (isNaN(d)) return raw;
                return d.toLocaleDateString();
            }

            function formatDuration(duration) {
                if (!duration) return '0:00';
                if (typeof duration === 'string' && duration.includes(':')) {
                    return duration;
                }
                // If it's decimal hours, convert to HH:MM
                const hours = Math.floor(duration);
                const minutes = Math.round((duration - hours) * 60);
                return `${hours}:${minutes.toString().padStart(2, '0')}`;
            }

            function showSuggestionsPanel(show) {
                if (show) {
                    $mainCol.removeClass('col-md-12').addClass('col-md-9');
                    $suggestionCol.removeClass('d-none');
                } else {
                    $mainCol.removeClass('col-md-9').addClass('col-md-12');
                    $suggestionCol.addClass('d-none');
                }
            }

            // Render estimate card
            function renderEstimateCard(p) {
                return `
                <div class="suggestion-card" data-type="estimate" data-id="${p.id}">
                    <div class="suggestion-card-header">
                        <span>
                            ${(p.proposal_number || p.proposal_id) 
                                ? `Estimate ${p.proposal_number || p.proposal_id}` 
                                : 'Estimate'}
                        </span>
                        <a href="${proposalEditUrlPattern.replace('__id__', p.encrypted_id)}"
                           target="_blank"
                           style="font-size:11px;color:#6B6C72;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                 viewBox="0 0 24 24" color="currentColor"
                                 width="20px" height="20px" focusable="false"
                                 aria-hidden="true" class="icon">
                                <path fill="currentColor"
                                      d="M20 11.03a1 1 0 0 0-1 1l-.01 5.715a1.29 1.29 0 0 1-1.29 1.282l-11.425-.018a1.286 1.286 0 0 1-1.283-1.287l.017-11.43A1.284 1.284 0 0 1 6.294 5.01l5.714.009a1 1 0 1 0 0-2L6.3 3.008h-.006A3.284 3.284 0 0 0 3.009 6.29l-.017 11.428a3.29 3.29 0 0 0 3.28 3.29l11.429.019a3.29 3.29 0 0 0 3.287-3.281L21 12.032a1 1 0 0 0-1-1.002"></path>
                                <path fill="currentColor"
                                      d="m20.013 3.03-4-.006a1 1 0 0 0 0 2h1.586L13.3 9.314a1 1 0 1 0 1.412 1.415l4.3-4.287v1.586a1 1 0 0 0 2 0l.006-4a1 1 0 0 0-1.005-.998"></path>
                            </svg>
                        </a>
                    </div>
                    <div class="suggestion-card-body">
                        <dl>
                            <dt>Date:</dt><dd>${formatDate(p.issue_date)}</dd><br>
                            <dt>Total:</dt><dd>$${Number(p.total_amount || 0).toFixed(2)}</dd><br>
                            <dt>Notes:</dt><dd>${p.note ? p.note : ''}</dd>
                        </dl>
                    </div>
                    <button type="button"
                            class="suggestion-add-button"
                            data-type="estimate"
                            data-id="${p.id}">
                        Add
                    </button>
                </div>
            `;
            }

            // Render billable time card
            function renderTimeCard(t) {
                return `
                <div class="suggestion-card" data-type="time" data-id="${t.id}">
                    <div class="suggestion-card-header">
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            Billable time
                        </span>
                    </div>
                    <div class="suggestion-card-body">
                        <dl>
                            <dt>Date:</dt><dd>${formatDate(t.date)}</dd><br>
                            <dt>Duration:</dt><dd>${formatDuration(t.duration)}</dd><br>
                            <dt>Rate:</dt><dd>$${Number(t.rate || 0).toFixed(2)}/hr</dd><br>
                            <dt>Amount:</dt><dd>$${Number(t.amount || 0).toFixed(2)}</dd><br>
                            <dt>Service:</dt><dd>${t.service_name || ''}</dd>
                            ${t.worker_name ? `<br><dt>By:</dt><dd>${t.worker_name}</dd>` : ''}
                        </dl>
                    </div>
                    <button type="button"
                            class="suggestion-add-button"
                            data-type="time"
                            data-id="${t.id}">
                        Add
                    </button>
                </div>
            `;
            }

            // Render billable expense card
            function renderExpenseCard(e) {
                return `
                <div class="suggestion-card" data-type="expense" data-id="${e.id}">
                    <div class="suggestion-card-header">
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                            Billable expense
                        </span>
                    </div>
                    <div class="suggestion-card-body">
                        <dl>
                            <dt>Date:</dt><dd>${formatDate(e.date)}</dd><br>
                            <dt>Amount:</dt><dd>$${Number(e.amount || 0).toFixed(2)}</dd><br>
                            <dt>Account:</dt><dd>${e.account_name || ''}</dd>
                            ${e.vendor_name ? `<br><dt>Vendor:</dt><dd>${e.vendor_name}</dd>` : ''}
                            ${e.description ? `<br><dt>Desc:</dt><dd>${e.description}</dd>` : ''}
                        </dl>
                    </div>
                    <button type="button"
                            class="suggestion-add-button"
                            data-type="expense"
                            data-id="${e.id}">
                        Add
                    </button>
                </div>
            `;
            }

            function renderSuggestions(items) {
                $list.empty();

                if (!items || !items.length) {
                    $list.html(
                        '<p style="font-size:12px;color:#6b6f73;">' +
                        'No suggested transactions found for this customer.' +
                        '</p>'
                    );
                    return;
                }

                items.forEach(function(item) {
                    let cardHtml = '';
                    
                    if (item.type === 'estimate') {
                        cardHtml = renderEstimateCard(item);
                    } else if (item.type === 'time') {
                        cardHtml = renderTimeCard(item);
                    } else if (item.type === 'expense') {
                        cardHtml = renderExpenseCard(item);
                    }
                    
                    $list.append(cardHtml);
                });
            }

            // ---------- apply filter ----------

            function applyFilter() {
                const typeFilter = $('#suggestions-filter-type').val();
                const dateFilter = $('#suggestions-filter-date').val();
                const now = new Date();

                // Combine all types into a single list with type markers
                let allItems = [];
                allSuggestions.estimates.forEach(e => allItems.push({...e, type: 'estimate'}));
                allSuggestions.billable_time.forEach(t => allItems.push({...t, type: 'time'}));
                allSuggestions.billable_expenses.forEach(x => allItems.push({...x, type: 'expense'}));

                // Apply type filter
                if (typeFilter !== 'all') {
                    allItems = allItems.filter(item => item.type === typeFilter);
                }

                // Apply date filter
                if (dateFilter !== 'all') {
                    allItems = allItems.filter(function(item) {
                        const itemDate = item.issue_date || item.date;
                        if (!itemDate) return true;
                        const d = new Date(itemDate);
                        if (isNaN(d)) return true;
                        const diffDays = (now - d) / 86400000;
                        if (dateFilter === 'last30') return diffDays <= 30;
                        if (dateFilter === 'last365') return diffDays <= 365;
                        return true;
                    });
                }

                filteredItems = allItems;
                renderSuggestions(filteredItems);
            }

            // ---------- load suggestions for a customer ----------

            function loadCustomerSuggestions(customerId) {
                if (!customerId) {
                    $list.html(
                        '<p style="font-size:12px;color:#6b6f73;">' +
                        'Select a customer to see suggested transactions.' +
                        '</p>'
                    );
                    allSuggestions = { estimates: [], billable_time: [], billable_expenses: [] };
                    filteredItems = [];
                    showSuggestionsPanel(false);
                    return;
                }

                // Check if suggestionsUrl is available
                if (!suggestionsUrl) {
                    console.error('Suggestions URL not found. Check data-suggestions-url attribute on #customer_id.');
                    $list.html(
                        '<p style="font-size:12px;color:#d9534f;">' +
                        'Configuration error: Suggestions URL not defined.' +
                        '</p>'
                    );
                    showSuggestionsPanel(false);
                    return;
                }

                console.log('Loading suggestions from:', suggestionsUrl, 'for customer:', customerId);

                $.get(suggestionsUrl, {
                        customer_id: customerId
                    })
                    .done(function(data) {
                        console.log('Suggestions loaded:', data);
                        allSuggestions = {
                            estimates: data.estimates || [],
                            billable_time: data.billable_time || [],
                            billable_expenses: data.billable_expenses || []
                        };
                        
                        const totalCount = allSuggestions.estimates.length + 
                                          allSuggestions.billable_time.length + 
                                          allSuggestions.billable_expenses.length;

                        console.log('Total suggestions:', totalCount);
                        applyFilter();
                        showSuggestionsPanel(totalCount > 0);
                    })
                    .fail(function(xhr, status, error) {
                        console.error('Failed to load suggestions:', status, error, xhr.responseText);
                        $list.html(
                            '<p style="font-size:12px;color:#d9534f;">' +
                            'Failed to load suggested transactions.' +
                            '</p>'
                        );
                        allSuggestions = { estimates: [], billable_time: [], billable_expenses: [] };
                        filteredItems = [];
                        showSuggestionsPanel(false);
                    });
            }

            // ---------- helper: insert ONE item from proposal into table ----------

            function insertProposalItem(item, $insertBefore, estimateId) {
                var hasProduct = item.product_id !== null && item.product_id !== undefined && item.product_id !==
                    '';

                // Use passed estimateId or from item itself
                var estId = estimateId || item.estimate_id || null;
                // Get the proposal_product_id (the id from proposal_products table)
                var proposalProductId = item.id || null;

                if (hasProduct) {
                    // normal product/service line (use repeater like before)
                    window.qbInsertAfterTbody = null;
                    window.qbInsertBeforeTbody = $insertBefore || null;
                    window.qbDuplicateSource = null;
                    // Create a copy with estimate_id and proposal_product_id to avoid mutating original
                    window.qbProposalToAdd = Object.assign({}, item, {
                        estimate_id: estId,
                        proposal_product_id: proposalProductId
                    });
                    $('[data-repeater-create]').trigger('click');
                    return;
                }

                // product_id is NULL -> it's either Subtotal line or Text line
                var desc = (item.description || '').trim();

                // SUBTOTAL row
                if (desc.toLowerCase() === 'subtotal') {
                    if (typeof window.qbCreateSubtotalBody === 'function') {
                        // amount from DB is not critical; table will recalc
                        var $body = window.qbCreateSubtotalBody('0.00');

                        // Store estimate_id and proposal_product_id on subtotal tbody
                        if (estId) {
                            $body.attr('data-estimate-id', estId);
                        }
                        if (proposalProductId) {
                            $body.attr('data-proposal-product-id', proposalProductId);
                        }

                        if ($insertBefore && $insertBefore.length) {
                            $body.insertBefore($insertBefore);
                        } else {
                            $('#sortable-table').append($body);
                        }

                        recalcTotals();
                    }
                    return;
                }

                // TEXT row
                if (desc.length && typeof window.qbCreateTextBody === 'function') {
                    var $bodyText = window.qbCreateTextBody(desc);

                    // Store estimate_id and proposal_product_id on text tbody
                    if (estId) {
                        $bodyText.attr('data-estimate-id', estId);
                    }
                    if (proposalProductId) {
                        $bodyText.attr('data-proposal-product-id', proposalProductId);
                    }

                    if ($insertBefore && $insertBefore.length) {
                        $bodyText.insertBefore($insertBefore);
                    } else {
                        $('#sortable-table').append($bodyText);
                    }

                    renumberInvoiceLines();
                    recalcTotals();
                }
            }

            // ---------- events ----------

            // reload suggestions when customer changes
            $customerSelect.on('change', function() {
                loadCustomerSuggestions($(this).val());
            });

            // initial load if customer preselected
            if ($customerSelect.val()) {
                loadCustomerSuggestions($customerSelect.val());
            }

            // Helper: Check if a product row is empty (no product selected, no values)
            function isEmptyProductRow($body) {
                var $productRow = $body.find('tr.product-row');
                if (!$productRow.length) return false;

                var itemId = $productRow.find('select.item').val();
                var qty = $productRow.find('.quantity').val();
                var price = $productRow.find('.price').val();
                var desc = $productRow.find('.pro_description').val();

                // Row is empty if no product selected and no quantity/price/description
                return (!itemId || itemId === '' || itemId === '--') &&
                       (!qty || qty === '' || parseFloat(qty) === 0) &&
                       (!price || price === '' || parseFloat(price) === 0) &&
                       (!desc || desc.trim() === '');
            }

            // Helper: Remove empty default row if estimate has items
            function removeEmptyDefaultRowIfNeeded(estimateItemCount) {
                if (estimateItemCount <= 0) return;

                var $allBodies = $('#sortable-table').children('tbody');
                // Only remove if there's exactly one product row and it's empty
                var $productBodies = $allBodies.filter(function() {
                    return $(this).find('tr.product-row').length > 0;
                });

                if ($productBodies.length === 1 && isEmptyProductRow($productBodies.first())) {
                    $productBodies.first().remove();
                }
            }

            // --- Add SINGLE suggestion (estimate, time, or expense) ---
            $(document).on('click', '.suggestion-add-button', function() {
                const type = $(this).data('type');
                const id = String($(this).data('id'));
                
                // Determine insertion point: before trailing special rows, if any
                var $lastBody = $('#sortable-table').find('tbody').last();
                var $insertBefore = null;
                if ($lastBody.length && $lastBody.hasClass('special-body')) {
                    $insertBefore = $lastBody;
                }

                if (type === 'estimate') {
                    const p = allSuggestions.estimates.find(x => String(x.id) === id);
                    if (!p) return;

                    // Calculate how many items will be added
                    var itemCount = (p.items && p.items.length) ? p.items.length : 1;
                    removeEmptyDefaultRowIfNeeded(itemCount);

                    if (p.items && p.items.length) {
                        p.items.forEach(function(item) {
                            insertProposalItem(item, $insertBefore, p.id);
                        });
                    } else {
                        insertProposalItem({
                            product_id: p.product_id || null,
                            description: (p.note && p.note.length) ?
                                p.note : (p.proposal_number ? 'Estimate ' + p.proposal_number : 'Estimate'),
                            quantity: 1,
                            price: Number(p.total_amount || 0),
                            amount: Number(p.total_amount || 0)
                        }, $insertBefore, p.id);
                    }

                    // Remove from suggestions
                    allSuggestions.estimates = allSuggestions.estimates.filter(x => String(x.id) !== id);
                    
                } else if (type === 'time') {
                    const t = allSuggestions.billable_time.find(x => String(x.id) === id);
                    if (!t) return;

                    removeEmptyDefaultRowIfNeeded(1);

                    // Convert duration string to decimal hours for quantity
                    let quantity = 1;
                    if (t.duration) {
                        if (typeof t.duration === 'string' && t.duration.includes(':')) {
                            const parts = t.duration.split(':');
                            const hours = parseFloat(parts[0] || 0);
                            const minutes = parseFloat(parts[1] || 0);
                            quantity = hours + (minutes / 60);
                        } else {
                            quantity = parseFloat(t.duration) || 1;
                        }
                    }

                    // Create invoice line for time entry
                    window.qbInsertAfterTbody = null;
                    window.qbInsertBeforeTbody = $insertBefore || null;
                    window.qbDuplicateSource = null;
                    window.qbProposalToAdd = {
                        product_id: t.service_id || null,
                        description: `Time: ${t.service_name || 'Time Entry'}${t.notes ? ' - ' + t.notes : ''}`,
                        quantity: quantity.toFixed(2),
                        price: Number(t.rate || 0),
                        amount: Number(t.amount || 0),
                        taxable: t.taxable || false,
                        line_type: 'billable_time',
                        source_id: t.id
                    };
                    $('[data-repeater-create]').trigger('click');

                    // Remove from suggestions
                    allSuggestions.billable_time = allSuggestions.billable_time.filter(x => String(x.id) !== id);
                    
                } else if (type === 'expense') {
                    const e = allSuggestions.billable_expenses.find(x => String(x.id) === id);
                    if (!e) return;

                    removeEmptyDefaultRowIfNeeded(1);

                    // Create invoice line for expense
                    window.qbInsertAfterTbody = null;
                    window.qbInsertBeforeTbody = $insertBefore || null;
                    window.qbDuplicateSource = null;
                    window.qbProposalToAdd = {
                        product_id: null,
                        description: `Expense: ${e.description || e.account_name}${e.vendor_name ? ' (from ' + e.vendor_name + ')' : ''}`,
                        quantity: 1,
                        price: Number(e.amount || 0),
                        amount: Number(e.amount || 0),
                        line_type: 'billable_expense',
                        source_id: e.id,
                        source_type: e.source_type
                    };
                    $('[data-repeater-create]').trigger('click');

                    // Remove from suggestions
                    allSuggestions.billable_expenses = allSuggestions.billable_expenses.filter(x => String(x.id) !== id);
                }

                // Remove card from UI
                $(this).closest('.suggestion-card').remove();
                
                // Update filteredItems
                filteredItems = filteredItems.filter(x => !(x.type === type && String(x.id) === id));

                // Check if any suggestions remain
                const totalCount = allSuggestions.estimates.length + 
                                  allSuggestions.billable_time.length + 
                                  allSuggestions.billable_expenses.length;
                
                if (totalCount === 0) {
                    $list.html(
                        '<p style="font-size:12px;color:#6b6f73;">' +
                        'All suggested transactions have been added.' +
                        '</p>'
                    );
                    showSuggestionsPanel(false);
                }
            });

            // --- Add ALL visible suggestions ---
            $(document).on('click', '.suggestions-addall', function() {
                if (!filteredItems.length) return;

                // Calculate total items to be added
                var totalItemCount = 0;
                filteredItems.forEach(function(item) {
                    if (item.type === 'estimate' && item.items && item.items.length) {
                        totalItemCount += item.items.length;
                    } else {
                        totalItemCount += 1;
                    }
                });

                // Remove empty default row before adding items
                removeEmptyDefaultRowIfNeeded(totalItemCount);

                var $lastBody = $('#sortable-table').find('tbody').last();
                var $insertBefore = null;
                if ($lastBody.length && $lastBody.hasClass('special-body')) {
                    $insertBefore = $lastBody;
                }

                filteredItems.forEach(function(item) {
                    if (item.type === 'estimate') {
                        if (item.items && item.items.length) {
                            item.items.forEach(function(lineItem) {
                                insertProposalItem(lineItem, $insertBefore, item.id);
                            });
                        } else {
                            insertProposalItem({
                                product_id: item.product_id || null,
                                description: (item.note && item.note.length) ?
                                    item.note : (item.proposal_number ? 'Estimate ' + item.proposal_number : 'Estimate'),
                                quantity: 1,
                                price: Number(item.total_amount || 0),
                                amount: Number(item.total_amount || 0)
                            }, $insertBefore, item.id);
                        }
                    } else if (item.type === 'time') {
                        let quantity = 1;
                        if (item.duration) {
                            if (typeof item.duration === 'string' && item.duration.includes(':')) {
                                const parts = item.duration.split(':');
                                const hours = parseFloat(parts[0] || 0);
                                const minutes = parseFloat(parts[1] || 0);
                                quantity = hours + (minutes / 60);
                            } else {
                                quantity = parseFloat(item.duration) || 1;
                            }
                        }
                        window.qbInsertAfterTbody = null;
                        window.qbInsertBeforeTbody = $insertBefore || null;
                        window.qbDuplicateSource = null;
                        window.qbProposalToAdd = {
                            product_id: item.service_id || null,
                            description: `Time: ${item.service_name || 'Time Entry'}${item.notes ? ' - ' + item.notes : ''}`,
                            quantity: quantity.toFixed(2),
                            price: Number(item.rate || 0),
                            amount: Number(item.amount || 0),
                            taxable: item.taxable || false,
                            line_type: 'billable_time',
                            source_id: item.id
                        };
                        $('[data-repeater-create]').trigger('click');
                    } else if (item.type === 'expense') {
                        window.qbInsertAfterTbody = null;
                        window.qbInsertBeforeTbody = $insertBefore || null;
                        window.qbDuplicateSource = null;
                        window.qbProposalToAdd = {
                            product_id: null,
                            description: `Expense: ${item.description || item.account_name}${item.vendor_name ? ' (from ' + item.vendor_name + ')' : ''}`,
                            quantity: 1,
                            price: Number(item.amount || 0),
                            amount: Number(item.amount || 0),
                            line_type: 'billable_expense',
                            source_id: item.id,
                            source_type: item.source_type
                        };
                        $('[data-repeater-create]').trigger('click');
                    }
                });

                allSuggestions = { estimates: [], billable_time: [], billable_expenses: [] };
                filteredItems = [];
                $list.html(
                    '<p style="font-size:12px;color:#6b6f73;">' +
                    'All suggested transactions have been added.' +
                    '</p>'
                );
                showSuggestionsPanel(false);
            });

            // Close suggestions panel with the X
            $(document).on('click', '.suggestions-close', function() {
                showSuggestionsPanel(false);
            });

            // ---------- Filter UI ----------
            $(document).on('click', '.suggestions-filter-btn', function(e) {
                e.stopPropagation();
                $filterPanel.toggleClass('show');
            });

            $(document).on('click', '.suggestions-filter-close', function() {
                $filterPanel.removeClass('show');
            });

            // click outside closes filter popup
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#suggestions-filter-panel, .suggestions-filter-btn').length) {
                    $filterPanel.removeClass('show');
                }
            });

            $('#suggestions-filter-reset').on('click', function() {
                $('#suggestions-filter-type').val('all');
                $('#suggestions-filter-date').val('all');
                applyFilter();
            });

            $('#suggestions-filter-apply').on('click', function() {
                applyFilter();
                $filterPanel.removeClass('show');
            });

            // ========================================
            // Populate Email and Billing Address on Customer Selection
            // ========================================
            $('#customer_id').on('change', function() {
                var customerId = $(this).val();
                var customerDataUrl = $(this).data('url');
                
                if (!customerId) {
                    // Clear fields when no customer selected
                    $('#customer_email_field').val('');
                    $('#customer_email').val('');
                    $('#billing_address').val('');
                    return;
                }
                
                // Fetch customer data via AJAX (POST method required)
                $.ajax({
                    url: customerDataUrl,
                    type: 'POST',
                    data: { 
                        id: customerId,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response && response.customer) {
                            var customer = response.customer;
                            
                            // Populate email field
                            var email = customer.email || '';
                            $('#customer_email_field').val(email);
                            $('#customer_email').val(email);
                            
                            // Populate billing address
                            var billingAddress = '';
                            if (customer.billing_name) billingAddress += customer.billing_name + '\n';
                            if (customer.billing_address) billingAddress += customer.billing_address + '\n';
                            if (customer.billing_city || customer.billing_state || customer.billing_zip) {
                                billingAddress += (customer.billing_city || '') + 
                                    (customer.billing_state ? ', ' + customer.billing_state : '') + 
                                    (customer.billing_zip ? ' ' + customer.billing_zip : '');
                            }
                            $('#billing_address').val(billingAddress.trim());
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching customer data:', error);
                    }
                });
            });

            // ========================================
            // Update Header AMOUNT display when totals change
            // ========================================
            function updateHeaderAmount() {
                var total = 0;
                
                // Sum all line item amounts from product rows
                $('.product-table tbody').each(function() {
                    var $row = $(this).find('tr.product-row');
                    if ($row.length) {
                        var amount = parseFloat($row.find('.amount').val()) || 0;
                        total += amount;
                    }
                    // Also check for subtotal rows
                    var $subtotalRow = $(this).find('tr.subtotal-row');
                    if ($subtotalRow.length) {
                        // Subtotal rows don't add to total, they're just display
                    }
                });
                
                // Format as currency
                var formattedTotal = '$' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                
                // Update header AMOUNT display
                $('#header-amount-display').text(formattedTotal);
                
                // Update Total and Total Amount Refunded in totals section
                $('.totalAmount').text(formattedTotal);
                $('.totalAmountRefunded').text(formattedTotal);
                
                // Also update hidden inputs
                $('.total_amount_hidden').val(total.toFixed(2));
            }
            
            // Listen for changes on quantity, price, and amount fields
            $(document).on('change keyup', '.quantity, .price, .amount', function() {
                // Recalculate line amount first
                var $row = $(this).closest('tr.product-row');
                if ($row.length) {
                    var qty = parseFloat($row.find('.quantity').val()) || 0;
                    var price = parseFloat($row.find('.price').val()) || 0;
                    var amount = qty * price;
                    $row.find('.amount').val(amount.toFixed(2));
                }
                
                // Then update header
                setTimeout(updateHeaderAmount, 50);
            });
            
            // Listen for line item additions/deletions
            $(document).on('click', '[data-repeater-create], [data-repeater-delete], .delete-icon', function() {
                setTimeout(updateHeaderAmount, 100);
            });
            
            // Initial update on page load
            setTimeout(updateHeaderAmount, 500);
        });
    </script>
@endsection
<script src="{{ asset('js/invoice-items-payload-handler.js') }}"></script>
