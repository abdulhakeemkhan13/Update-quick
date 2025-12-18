@extends('layouts.admin')
@section('page-title')
    {{ __('Estimate Edit') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('proposal.index') }}">{{ __('Estimate') }}</a></li>
    <li class="breadcrumb-item">{{ __('Estimate Edit') }}</li>
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
            border: 1px solid #dcdcdc;
            /* border-radius: 4px; */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin: 24px auto;
            max-width: 100%;
            width: 95%;
            padding: 24px;
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
            /* display: grid;
                        grid-template-columns: 1fr 400px; */
            /* gap: 350px; */
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
    <script>
        $(function() {
            $('#invoice-form').on('submit', function() {
                var lines = [];

                $('#sortable-table').children('tbody').each(function() {
                    var $body = $(this);

                    // 1) PRODUCT ROW
                    var $productRow = $body.find('tr.product-row');
                    if ($productRow.length) {
                        var $row = $productRow;

                        lines.push({
                            type: 'product',
                            item_id: $row.find('select.item').val() || null,
                            description: $row.find('.pro_description').val() || '',
                            quantity: parseFloat($row.find('.quantity').val()) || 0,
                            price: parseFloat($row.find('.price').val()) || 0,
                            discount: parseFloat($row.find('.discount').val()) || 0,
                            tax: $row.find('.tax').val() || '',
                            amount: parseFloat($row.find('.amount').val()) || 0,
                            is_taxable: $row.find('.form-check-input[type="checkbox"]')
                                .prop('checked') ? 1 : 0,
                            tax_ids: ($row.find('.tax').val() || '').split(',').filter(
                                Boolean),
                            itemTaxPrice: parseFloat($row.find('.itemTaxPrice').val()) || 0,
                            item_tax_rate: parseFloat($row.find('.itemTaxRate').val()) || 0
                        });

                        return; // continue to next tbody
                    }

                    // 2) SUBTOTAL ROW (tbody created by createSubtotalBody)
                    var $subtotalRow = $body.find('tr.subtotal-row');
                    if ($subtotalRow.length) {
                        lines.push({
                            type: 'subtotal',
                            label: 'Subtotal',
                            amount: parseFloat($subtotalRow.find('.subtotal-amount')
                                .text()) || 0
                        });
                        return;
                    }

                    // 3) TEXT ROW (tbody created by createTextBody)
                    var $textRow = $body.find('tr.text-row');
                    if ($textRow.length) {
                        lines.push({
                            type: 'text',
                            text: $textRow.find('input[type="text"]').val() || ''
                        });
                        return;
                    }
                });

                // store JSON string in hidden input
                $('#items_payload').val(JSON.stringify(lines));

                // Populate hidden inputs with calculated totals
                var subtotal = parseFloat($('.subTotal').text().replace(/[^0-9.-]+/g, '')) || 0;
                var taxableSubtotal = parseFloat($('.taxableSubtotal').text().replace(/[^0-9.-]+/g, '')) ||
                    0;
                var totalDiscount = parseFloat($('.totalDiscount').text().replace(/[^0-9.-]+/g, '')) || 0;
                var totalTax = parseFloat($('.totalTax').text().replace(/[^0-9.-]+/g, '')) || 0;
                var salesTaxAmount = parseFloat($('#sales_tax_amount').text().replace(/[^0-9.-]+/g, '')) ||
                    0;
                var totalAmount = parseFloat($('.totalAmount').text().replace(/[^0-9.-]+/g, '')) || 0;

                $('#hidden_subtotal').val(subtotal);
                $('#hidden_taxable_subtotal').val(taxableSubtotal);
                $('#hidden_total_discount').val(totalDiscount);
                $('#hidden_total_tax').val(totalTax);
                $('#hidden_sales_tax_amount').val(salesTaxAmount);
                $('#hidden_total_amount').val(totalAmount);

                // let form submit normally
            });
        });

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
    </script>
    <script>
        // Open full-screen modal on load
        $(document).ready(function() {
            var invoiceModal = new bootstrap.Modal(
                document.getElementById('invoice-modal'), {
                    backdrop: 'static',
                    keyboard: false,
                    focus: false // ← ADD THIS LINE
                }
            );
            invoiceModal.show();

            // Disable focus trap to prevent stack overflow
            document.getElementById('invoice-modal')._focustrap = null;

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

                var $checkbox = $productRow.find('.form-check-input[type="checkbox"]');
                var isTaxable = $checkbox.prop('checked');


                if (isTaxable) {
                    taxableSubtotal += amount;
                }
            });
            // sales tax rate select (value should be numeric percentage)
            var taxRate = parseFloat($('select[name="sales_tax_rate"]').val()) || 0;
            var totalTax = taxableSubtotal * taxRate / 100;

            // update bottom totals
            $('.subTotal').text(grandSubtotal.toFixed(2));
            $('.taxableSubtotal').text(taxableSubtotal.toFixed(2));
            $('.totalDiscount').text(totalDiscount.toFixed(2));
            $('.totalTax').text(totalTax.toFixed(2));
            $('.totalAmount').text((grandSubtotal - totalDiscount + totalTax).toFixed(2));
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

                    // reset helpers
                    window.qbInsertAfterTbody = null;
                    window.qbDuplicateSource = null;

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

                    // Create modal WITHOUT focus trap
                    var childModal = new bootstrap.Modal($modal[0], {
                        backdrop: true,
                        keyboard: true,
                        focus: false // ← DISABLE FOCUS TRAP
                    });

                    // Prevent double initialization
                    if ($modal.data('bs.modal')) {
                        $modal.data('bs.modal').dispose();
                    }

                    childModal.show();
                });

                $modal.on('hidden.bs.modal', function() {
                    // Properly dispose Bootstrap modal
                    var modalInstance = bootstrap.Modal.getInstance($modal[0]);
                    if (modalInstance) {
                        modalInstance.dispose();
                    }

                    // Remove from DOM
                    $modal.remove();

                    // Reset current select reference
                    currentSelect = null;
                });
            }

            // Ensure Add New exists for Branch & Department on load (in case server array missed it)
            function ensureAddNewOption($select) {
                if ($select.find('option[value="__add__"]').length === 0) {
                    $select.prepend('<option value="__add__">➕  Add New</option>');
                }
            }
            ensureAddNewOption($('#branch_id'));
            ensureAddNewOption($('#department_id'));

            // Detect "Add New" selection for any select with data-create-url
            $(document).on('change', 'select[data-create-url]', function() {
                var $select = $(this);

                // SAFETY: Prevent multiple rapid clicks
                if ($select.data('modal-opening')) {
                    return;
                }

                if ($select.val() === '__add__') {
                    $select.data('modal-opening', true);

                    openAddNewModal($select);

                    setTimeout(function() {
                        $select.data('modal-opening', false);
                    }, 500);
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
                            var $newOption = $('<option>', {
                                value: response.data.id,
                                text: response.data.name
                            });

                            // append and select
                            $select.append($newOption);

                            // Handle both Choices.js and regular selects
                            if ($select.hasClass('select2')) {
                                var selectId = $select.attr('id');
                                if (selectId) {
                                    // Destroy existing Choices instance if it exists
                                    if (window.Choices) {
                                        var choicesInstance = document.querySelector('#' +
                                            selectId);
                                        if (choicesInstance && choicesInstance.choices) {
                                            choicesInstance.choices.destroy();
                                        }
                                    }

                                    // Set the value
                                    $select.val(response.data.id);

                                    // Reinitialize Choices
                                    if (window.Choices) {
                                        new Choices('#' + selectId, {
                                            removeItemButton: true,
                                        });
                                    }
                                } else {
                                    $select.val(response.data.id).trigger('change');
                                }
                            } else {
                                $select.val(response.data.id).trigger('change');
                            }

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
@endpush

@section('content')
    <!-- Full Screen Modal -->
    <div class="modal fade" id="invoice-modal" tabindex="-1" aria-hidden="true" style="padding: 0 !important;">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content" style="background: #f4f5f8;">
                {{ Form::model($proposal, ['route' => ['proposal.update', $proposal->id], 'method' => 'PUT', 'class' => 'w-100', 'id' => 'invoice-form', 'files' => true]) }}
                <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
                <input type="hidden" name="items_payload" id="items_payload" value="">
                <input type="hidden" name="subtotal" id="hidden_subtotal" value="">
                <input type="hidden" name="taxable_subtotal" id="hidden_taxable_subtotal" value="">
                <input type="hidden" name="total_discount" id="hidden_total_discount" value="">
                <input type="hidden" name="total_tax" id="hidden_total_tax" value="">
                <input type="hidden" name="sales_tax_amount" id="hidden_sales_tax_amount" value="">
                <input type="hidden" name="total_amount" id="hidden_total_amount" value="">
                <input type="hidden" name="proposal_id" value="{{ $proposal->id }}">

                <div class="invoice-container">
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
                                {{ __('Estimate') }}
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
                                <button type="button" class="close-button"
                                    onclick="location.href = '{{ route('invoice.index') }}';" aria-label="Close">
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

                        {{-- Second Row: Navigation Tabs and Action Buttons --}}
                        <div class="header-bottom-row">
                            <div class="nav-tabs-custom">
                                <button type="button" class="nav-tab active">{{ __('Edit') }}</button>
                                <button type="button" class="nav-tab">{{ __('Email view') }}</button>
                                <button type="button" class="nav-tab">{{ __('PDF view') }}</button>
                            </div>
                            <div class="header-actions">
                                <button type="button" class="header-action-btn">
                                    <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class=""
                                        width="24px" height="24px" fill="currentColor">
                                        <path
                                            d="M12.024 7.982h-.007a4 4 0 100 8 4 4 0 10.007-8zm-.006 6a2 2 0 01.002-4 2 2 0 110 4h-.002z">
                                        </path>
                                        <path
                                            d="M20.444 13.4l-.51-.295a7.557 7.557 0 000-2.214l.512-.293a2.005 2.005 0 00.735-2.733l-1-1.733a2.005 2.005 0 00-2.731-.737l-.512.295a8.071 8.071 0 00-1.915-1.113v-.59a2 2 0 00-2-2h-2a2 2 0 00-2 2v.6a8.016 8.016 0 00-1.911 1.1l-.52-.3a2 2 0 00-2.725.713l-1 1.73a2 2 0 00.728 2.733l.509.295a7.75 7.75 0 00-.004 2.22l-.51.293a2 2 0 00-.738 2.73l1 1.732a2 2 0 002.73.737l.513-.295A8.07 8.07 0 009.01 19.39v.586a2 2 0 002 2h2a2 2 0 002-2V19.4a8.014 8.014 0 001.918-1.107l.51.3a2 2 0 002.734-.728l1-1.73a2 2 0 00-.728-2.735zm-2.593-2.8a5.8 5.8 0 010 2.78 1 1 0 00.472 1.1l1.122.651-1 1.73-1.123-.65a1 1 0 00-1.187.137 6.02 6.02 0 01-2.4 1.387 1 1 0 00-.716.957v1.294h-2v-1.293a1 1 0 00-.713-.96 5.991 5.991 0 01-2.4-1.395 1.006 1.006 0 00-1.188-.142l-1.125.648-1-1.733 1.125-.647a1 1 0 00.475-1.1 5.945 5.945 0 01-.167-1.387c.003-.467.06-.933.17-1.388a1 1 0 00-.471-1.1l-1.123-.65 1-1.73 1.124.651c.019.011.04.01.06.02a.97.97 0 00.186.063.9.9 0 00.2.04c.02 0 .039.011.059.011a1.08 1.08 0 00.136-.025.98.98 0 00.17-.032A1.02 1.02 0 007.7 7.75a.986.986 0 00.157-.1c.015-.013.034-.017.048-.03a6.011 6.011 0 012.4-1.39.453.453 0 00.049-.026.938.938 0 00.183-.1.87.87 0 00.15-.1.953.953 0 00.122-.147c.038-.049.071-.1.1-.156a1.01 1.01 0 00.055-.173.971.971 0 00.04-.2c0-.018.012-.034.012-.053V3.981h2v1.294a1 1 0 00.713.96c.897.273 1.72.75 2.4 1.395a1 1 0 001.186.141l1.126-.647 1 1.733-1.125.647a1 1 0 00-.465 1.096z">
                                        </path>
                                    </svg>
                                    {{ __('Manage') }}
                                </button>
                                <button type="button" class="header-action-btn">
                                    <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class=""
                                        width="24px" height="24px" fill="currentColor">
                                        <path
                                            d="M20.832 14.445l-1.7-2.555a2 2 0 00-1.667-.89H13v-1h4a2 2 0 002-2V4a2 2 0 00-2-2H6.535a2 2 0 00-1.664.89l-1.7 2.555a1 1 0 000 1.11l1.7 2.554A2 2 0 006.535 10H11v1H7a2 2 0 00-2 2v4a2 2 0 002 2h4v2a1 1 0 002 0v-2h4.465a2 2 0 001.664-.891l1.7-2.554a1 1 0 00.003-1.11zM5.2 6l1.335-2H17v4H6.535L5.2 6zm12.265 11H7v-4h10.465l1.335 2-1.335 2z">
                                        </path>
                                    </svg>
                                    {{ __('Take tour') }}
                                </button>
                                <button type="button" class="header-action-btn">
                                    <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class=""
                                        width="24px" height="24px" fill="currentColor">
                                        <path
                                            d="M12 22a.999.999 0 01-.857-.485L9.033 18H6.57A4.541 4.541 0 012 13.5v-7A4.54 4.54 0 016.57 2h10.86A4.54 4.54 0 0122 6.5v7a4.541 4.541 0 01-4.57 4.5h-2.463l-2.11 3.515A1 1 0 0112 22zM6.57 4A2.538 2.538 0 004 6.5v7A2.538 2.538 0 006.57 16H9.6a1 1 0 01.857.485L12 19.057l1.543-2.572A1 1 0 0114.4 16h3.03A2.538 2.538 0 0020 13.5v-7A2.538 2.538 0 0017.43 4H6.57z">
                                        </path>
                                        <path
                                            d="M8 11a1 1 0 100-2 1 1 0 000 2zm4 0a1 1 0 100-2 1 1 0 000 2zm4 0a1 1 0 100-2 1 1 0 000 2z">
                                        </path>
                                    </svg>
                                    {{ __('Feedback') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Scrollable Content --}}
                    <div style="flex: 1; overflow-y: auto;">
                        <div class="invoice-card">
                            {{-- Header Section --}}
                            <div class="invoice-header">
                                <div class="header-left">
                                    <h1 class="invoice-title">{{ __('ESTIMATE') }}</h1>

                                    <div class="company-info">
                                        <!-- <div class="company-name">{{ Auth::user()->name ?? 'CREATIVE IT PARK' }}</div> -->
                                        <div class="company-details-row">
                                            <!-- Placeholder address as it's not in Auth::user() usually, or use dynamic if available -->
                                            <div class="company-name">{{ Auth::user()->name ?? 'CREATIVE IT PARK' }}</div>
                                            <div class="company-email">{{ Auth::user()->email }}</div>
                                        </div>
                                        <div class="company-details-row">
                                            <!-- Placeholder address as it's not in Auth::user() usually, or use dynamic if available -->
                                            <div class="company-address">
                                                {{ Auth::user()->address ?? '123 Sierra Way, San Pablo CA 87999' }}</div>

                                        </div>
                                        <a href="#" class="edit-company-link">{{ __('Edit company') }}</a>
                                    </div>
                                </div>

                                <div class="header-right">
                                    <div class="balance-due">
                                        <span class="balance-label">{{ __('Amount (hidden):') }}</span>
                                        <span class="balance-amount">$0.00</span>
                                    </div>

                                    <div class="logo-wrapper">
                                        {{-- hidden file input --}}
                                        <input type="file" id="company_logo_input" name="company_logo"
                                            accept="image/*" class="logo-input">

                                        {{-- clickable box (QBO-style dashed border) --}}
                                        <button type="button" class="logo-section" id="company_logo_button">
                                            {{-- logo preview (hidden until a file is chosen) --}}
                                            <img src="" alt="Logo" class="logo-preview d-none">

                                            {{-- default text --}}
                                            <span class="add-logo-text">{{ __('Add logo') }}</span>
                                            <span class="logo-size-limit">{{ __('Max size: 1 MB') }}</span>
                                        </button>

                                        {{-- small delete icon like QBO --}}
                                        <button type="button" class="logo-remove-btn d-none" id="company_logo_remove"
                                            aria-label="Delete logo">
                                            &times;
                                        </button>
                                    </div>

                                </div>
                            </div>

                            {{-- Customer & Transaction Details Section --}}
                            <div class="customer-section row">
                                {{-- Column 1: Customer & Bill To --}}
                                <div class="col-md-3">
                                    {{-- Customer Dropdown --}}
                                    <div class="customer-row">
                                        <div class="customer-field">
                                            {{ Form::select('customer_id', $customers, $customerId ?? '', [
                                                'class' => 'form-select',
                                                'id' => 'customer',
                                                'data-url' => route('invoice.customer'),
                                                'required' => 'required',
                                                'data-create-url' => route('customer.create'),
                                                'data-create-title' => __('Create New Customer'),
                                                'style' => 'width: 60%;',
                                            ]) }}
                                        </div>
                                    </div>

                                    {{-- Bill To (Hidden by default, shown on customer selection) --}}
                                    <div class="field-group" id="bill-to-section"
                                        style="margin-top: 16px; display: none;">
                                        <label class="form-label"
                                            style="font-size: 13px; margin-bottom: 4px;">{{ __('Bill to') }}</label>
                                        {{ Form::textarea('bill_to', '', [
                                            'class' => 'form-control',
                                            'rows' => 4,
                                            'style' => 'font-size: 13px;',
                                        ]) }}
                                    </div>
                                </div>

                                {{-- Column 2: Terms & Dates --}}
                                <div class="col-md-3 mt-4">
                                    {{-- Estimate Date (inline label) --}}
                                    <div class="field-group" style="margin-bottom: 12px;">
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <label class="form-label"
                                                style="font-size: 13px; margin: 0; width: 120px; flex-shrink: 0;">
                                                {{ __('Estimate date') }}
                                            </label>
                                            <div style="flex: 1;">
                                                {{ Form::date('issue_date', date('Y-m-d'), [
                                                    'class' => 'form-control',
                                                    'required' => 'required',
                                                    'style' => 'font-size: 13px; width: 100%;',
                                                ]) }}
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Expiration Date (inline label) --}}
                                    <div class="field-group" style="margin-bottom: 12px;">
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <label class="form-label"
                                                style="font-size: 13px; margin: 0; width: 120px; flex-shrink: 0;">
                                                {{ __('Expiration date') }}
                                            </label>
                                            <div style="flex: 1;">
                                                {{ Form::date('send_date', null, [
                                                    'class' => 'form-control',
                                                    'style' => 'font-size: 13px; width: 100%;',
                                                ]) }}
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                {{-- Column 3: Terms & Dates --}}
                                <div class="col-md-3 mt-4">

                                    {{-- Accepted By (UI Only) --}}
                                    <div class="field-group" style="margin-bottom: 12px;">
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <label class="form-label"
                                                style="font-size: 13px; margin: 0; width: 120px; flex-shrink: 0;">
                                                {{ __('Accepted by') }}
                                            </label>
                                            <div style="flex: 1;">
                                                {{ Form::text('accepted_by', '', [
                                                    'class' => 'form-control',
                                                    'style' => 'font-size: 13px; width: 100%;',
                                                ]) }}
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Accepted Date (UI Only) --}}
                                    <div class="field-group" style="margin-bottom: 12px;">
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <label class="form-label"
                                                style="font-size: 13px; margin: 0; width: 120px; flex-shrink: 0;">
                                                {{ __('Accepted date') }}
                                            </label>
                                            <div style="flex: 1;">
                                                {{ Form::date('accepted_date', null, [
                                                    'class' => 'form-control',
                                                    'style' => 'font-size: 13px; width: 100%;',
                                                ]) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>


                                {{-- Column 4: Empty --}}
                                <div class="col-md-3">
                                    {{-- Intentionally left empty to match QuickBooks layout --}}
                                </div>

                                {{-- Hidden fields for compatibility --}}
                                {{ Form::hidden('customer_email', '', ['id' => 'customer_email']) }}
                                {{ Form::hidden('category_id', 1) }}
                                {{ Form::hidden('proposal_number', $proposal_number) }}
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
                                                    <button type="button" class="qb-row-menu-btn" aria-haspopup="true"
                                                        aria-expanded="false">
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
                                                            <button type="button" class="dropdown-item row-add-product">
                                                                {{ __('Add product or service') }}
                                                            </button>
                                                        </li>
                                                        <li>
                                                            <button type="button" class="dropdown-item row-add-subtotal">
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
                                                    'data-url' => route('proposal.product'),
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
                                            <!-- <td>
                                                                                                                    {{ Form::text('discount', '', [
                                                                                                                        'class' => 'form-control input-right discount',
                                                                                                                        'placeholder' => '0.00',
                                                                                                                    ]) }}
                                                                                                                </td> -->
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
                                            <td class="delete-cell">
                                                <span class="delete-icon" title="Delete line" data-repeater-delete="">
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
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                color="currentColor" width="20px" height="20px" focusable="false"
                                                aria-hidden="true">
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
                                            <label>{{ __('Customer payment options') }}</label>
                                            <div style="margin: 8px 0;">
                                                <img src="{{ asset('assets/images/credit_cards_qbocredits.png') }}"
                                                    alt="Credit Cards" style="height: 24px;">
                                            </div>
                                            <style>
                                                .activate-payments-text {
                                                    font-size: 13px;
                                                    color: #393a3d;
                                                    margin-top: 2px;
                                                    padding-bottom: 10px;
                                                }

                                                .activate-payments-text a {
                                                    color: #0077c5;
                                                    text-decoration: none;
                                                    font-weight: 500;
                                                }

                                                .activate-payments-text a:hover {
                                                    text-decoration: underline;
                                                }
                                            </style>
                                            <div class="activate-payments-text">
                                                Activate online card or bank transfer payments for your customers.
                                                <a href="#">{{ __('Activate payments') }}</a>
                                            </div>
                                            <div class="info-text">
                                                {{ __('Tell your customer how you want to get paid. To keep instructions same for all future invoices, you can specify your payment preferences by clicking on "Edit default".') }}
                                            </div>
                                        </div>

                                        <div class="info-field">
                                            <label>{{ __('Note to customer') }}</label>
                                            {{ Form::textarea('note', '', [
                                                'class' => 'form-control',
                                                'rows' => 3,
                                                'placeholder' => 'Thank you for your business.',
                                            ]) }}
                                        </div>

                                        <div class="info-field">
                                            <label>{{ __('Memo on statement (hidden)') }}</label>
                                            {{ Form::textarea('memo', '', [
                                                'class' => 'form-control',
                                                'rows' => 3,
                                                'placeholder' => 'This memo will not show up on your invoice, but will appear on the statement.',
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

                                    <div class="left-section col-md-4">
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
                                                <select name="sales_tax_rate" class="form-select totals-tax-rate-select">
                                                    <option value="">{{ __('Select a tax rate') }}</option>
                                                </select>
                                            </span>
                                        </div>

                                        {{-- Sales tax row  ✅ add class sales-tax-row --}}
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
                                        <a href="#" class="link-button see-math-link">{{ __('See the math') }}</a>

                                        {{-- Invoice total --}}
                                        <div class="total-row final">
                                            <span>{{ __('Invoice total') }}</span>
                                            <span class="totalAmount">0.00</span>
                                        </div>

                                        {{-- Edit totals (right-aligned) --}}
                                        <a href="#"
                                            class="link-button edit-totals-link">{{ __('Edit totals') }}</a>
                                    </div>

                                </div>
                            </div>
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
                </div>
                {{ Form::close() }}
            </div>
        </div>
    </div>

    {{-- Auto-populate script for edit mode --}}
    <script>
        $(document).ready(function() {
            // Check if we have proposal data (edit mode)
            @if (isset($proposalData) && $proposalData)
                var proposalData = @json($proposalData);
                var taxableSubtotal = parseFloat(proposalData.taxable_subtotal) || 0;
                // Populate header fields immediately
                if (proposalData.customer_id) {
                    $('#customer').val(proposalData.customer_id).trigger('change');
                }

                if (proposalData.issue_date) {
                    $('input[name="issue_date"]').val(proposalData.issue_date);
                }

                if (proposalData.send_date) {
                    $('input[name="send_date"]').val(proposalData.send_date);
                }

                if (proposalData.category_id) {
                    $('select[name="category_id"]').val(proposalData.category_id);
                }

                // Show and populate Bill To if exists
                if (proposalData.bill_to) {
                    $('#bill-to-section').show();
                    $('[name="bill_to"]').val(proposalData.bill_to);
                }

                // Populate terms, memo, note
                if (proposalData.terms) {
                    $('select[name="terms"]').val(proposalData.terms);
                }

                if (proposalData.memo) {
                    $('textarea[name="memo"]').val(proposalData.memo);
                }

                if (proposalData.note) {
                    $('textarea[name="note"]').val(proposalData.note);
                }

                // Display existing logo if exists
                if (proposalData.logo) {
                    $('.logo-preview').attr('src', proposalData.logo).removeClass('d-none').show();
                    $('.add-logo-text').hide();
                    $('.logo-size-limit').hide();
                    $('#company_logo_remove').removeClass('d-none').show();
                    $('#company_logo_button').addClass('has-logo');
                }

                // Display existing attachments if exist
                if (proposalData.attachments && proposalData.attachments.length > 0) {
                    proposalData.attachments.forEach(function(attachment) {
                        addExistingAttachment(attachment);
                    });
                }

                // Populate items sequentially to avoid timing conflicts
                if (proposalData.items && proposalData.items.length > 0) {
                    var $table = $('#sortable-table');

                    // Remove default empty row
                    $table.find('tbody').remove();

                    var currentIndex = 0;

                    // Function to add one item at a time
                    function addNextItem() {
                        if (currentIndex >= proposalData.items.length) {
                            // All items loaded - now renumber and recalculate
                            setTimeout(function() {
                                renumberInvoiceLines();
                                recalcTotals();
                                $('#customer').trigger('change');
                            }, 300);
                            return;
                        }

                        var item = proposalData.items[currentIndex];

                        if (item.type === 'product') {
                            // Add product row
                            $('[data-repeater-create]').trigger('click');

                            setTimeout(function() {
                                var $tbody = $table.children('tbody').last();
                                var $row = $tbody.find('tr.product-row');


                                // Store the taxable value before triggering change
                                var isTaxable = (item.taxable == 1 || item.taxable === true || item
                                    .taxable === '1');

                                // Populate product fields
                                if (item.item) {
                                    // Trigger change will cause AJAX call that overwrites checkbox
                                    // So we'll set checkbox AFTER the AJAX completes
                                    $row.find('select.item').val(item.item).trigger('change');
                                }

                                // Wait for AJAX to complete, then set all fields
                                setTimeout(function() {
                                    if (item.description) {
                                        $row.find('.pro_description').val(item.description);
                                    }

                                    if (item.quantity) {
                                        $row.find('input.quantity').val(item.quantity);
                                    }

                                    if (item.price) {
                                        $row.find('input.price').val(item.price);
                                    }

                                    if (item.discount) {
                                        $row.find('input.discount').val(item.discount);
                                    }

                                    if (item.amount) {
                                        $row.find('input.amount').val(item.amount);
                                    }

                                    // Set taxable checkbox AFTER AJAX completes
                                    var $checkbox = $row.find('.form-check-input[type="checkbox"]');
                                    $checkbox.prop('checked', isTaxable);

                                    if (item.itemTaxPrice) {
                                        $row.find('input.itemTaxPrice').val(item.itemTaxPrice);
                                    }

                                    if (item.itemTaxRate) {
                                        $row.find('input.itemTaxRate').val(item.itemTaxRate);
                                    }

                                    // Add hidden ID for update
                                    if (item.id) {
                                        $tbody.append(
                                            '<input type="hidden" name="item_ids[]" value="' +
                                            item.id + '">');
                                    }

                                    currentIndex++;
                                    addNextItem(); // Add next item
                                }, 200); // Wait for AJAX to complete
                            }, 100);

                        } else if (item.type === 'subtotal') {
                            // Add subtotal row
                            var $subtotalBody = window.createSubtotalBody(item.amount || '0.00');
                            $table.append($subtotalBody);

                            currentIndex++;
                            setTimeout(addNextItem, 50); // Small delay before next item

                        } else if (item.type === 'text') {
                            // Add text row
                            var $textBody = window.createTextBody(item.description || '');
                            $table.append($textBody);

                            currentIndex++;
                            setTimeout(addNextItem, 50); // Small delay before next item
                        }
                    }
                    $('.taxableSubtotal').text(taxableSubtotal.toFixed(2));
                    // Start adding items
                    addNextItem();
                }
            @endif
        });

        // Helper function to add existing attachment to the list
        function addExistingAttachment(attachment) {
            var attachmentHtml = `
                <div class="attachment-row" data-attachment-id="${attachment.id}">
                    <div class="form-check">
                        <input class="form-check-input attachment-checkbox" type="checkbox"
                            id="attachment_${attachment.id}" checked>
                        <label class="form-check-label" for="attachment_${attachment.id}">
                            <a href="${attachment.url}" target="_blank">${attachment.name}</a>
                            <span class="attachment-size">(${formatFileSize(attachment.size)})</span>
                        </label>
                    </div>
                    <button type="button" class="btn-remove-attachment" data-attachment-id="${attachment.id}">
                        <i class="ti ti-x"></i>
                    </button>
                    <input type="hidden" name="existing_attachments[]" value="${attachment.name}">
                </div>
            `;
            $('#attachments-list').append(attachmentHtml);
        }

        // Helper function to format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }
    </script>
    @include('proposal._form-scripts')
@endsection
