@extends('layouts.admin')
@section('page-title')
    {{ __('Bill Payment') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('receive-bill-payment.index') }}">{{ __('Bill Payments') }}</a></li>
    <li class="breadcrumb-item">{{ __('Bill Payment') }}</li>
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

        .close-button {
            background: none;
            border: none;
            font-size: 24px;
            color: var(--qbo-gray-text);
            cursor: pointer;
            padding: 4px;
            line-height: 1;
        }

        .main-content {
            padding: 20px 30px;
            flex: 1;
            background-color: #f5f5f5;
        }

        .top-vendor-bar {
            margin-bottom: 20px;
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

        .form-label {
            font-size: 13px;
            color: var(--qbo-gray-text);
            margin-bottom: 4px;
            font-weight: 400;
        }

        .form-control, .form-select {
            border: 1px solid #8d9096;
            border-radius: 2px;
            padding: 8px 10px;
            font-size: 14px;
            color: #393a3d;
            height: 36px;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--qbo-green) !important;
            box-shadow: 0 0 0 2px rgba(44, 160, 28, 0.2) !important;
            outline: none !important;
        }

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
            background: none;
            border: none;
            cursor: pointer;
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
            color: #fff;
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
            border-color: var(--qbo-green);
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

        #outstanding-bills-table th {
            font-size: 12px;
            font-weight: 600;
            color: var(--qbo-gray-text);
            text-transform: uppercase;
            border-bottom: 2px solid var(--qbo-border-color);
        }

        #outstanding-bills-table td {
            font-size: 14px;
            vertical-align: middle;
        }

        .payment-input {
            width: 100px !important;
            display: inline-block;
        }

        /* Top Right Amount Display */
        .amount-display-top {
            text-align: right;
        }

        .amount-row {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 10px;
            margin-bottom: 5px;
        }

        .amount-label {
            font-size: 12px;
            color: var(--qbo-gray-text);
            text-transform: uppercase;
            font-weight: 600;
        }

        .amount-input-inline {
            display: flex;
            align-items: center;
            gap: 2px;
        }

        .currency-symbol {
            font-size: 24px;
            font-weight: 600;
            color: #393a3d;
        }

        .amount-paid-input {
            font-size: 28px;
            font-weight: 700;
            text-align: right;
            border: none;
            border-bottom: 2px solid var(--qbo-border-color);
            border-radius: 0;
            width: 150px;
            padding: 0 5px;
            background: transparent;
            outline: none;
        }

        .amount-paid-input:focus {
            border-bottom-color: var(--qbo-green);
        }

        .balance-row {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 10px;
        }

        .balance-label {
            font-size: 12px;
            color: var(--qbo-gray-text);
        }

        .balance-value {
            font-size: 14px;
            font-weight: 600;
            color: #393a3d;
        }

        /* Find by Bill Dropdown */
        .find-bill-wrapper {
            position: relative;
            display: inline-block;
        }

        .find-bill-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            width: 300px;
            background: #fff;
            border: 1px solid var(--qbo-border-color);
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            padding: 10px;
            margin-top: 5px;
        }

        .find-bill-dropdown.show {
            display: block;
        }

        .find-bill-results {
            max-height: 200px;
            overflow-y: auto;
            margin-top: 10px;
        }

        .find-bill-item {
            padding: 8px 10px;
            cursor: pointer;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .find-bill-item:hover {
            background: #f4f5f8;
        }

        .find-bill-item.selected {
            background: #e8f5e3;
        }

        .text-success {
            color: var(--qbo-green) !important;
        }
    </style>
@endpush

@section('content')
    <!-- Modal -->
    <div class="modal fade show" id="bill-payment-modal" tabindex="-1" aria-labelledby="billPaymentModalLabel"
        aria-modal="true" role="dialog" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content invoice-container">
                <!-- Fixed Top Header -->
                <div class="fixed-top-header">
                    <div class="header-top-row">
                        <div class="invoice-label">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <rect x="2" y="4" width="20" height="16" rx="2" />
                                <path d="M12 8v8M8 12h8" />
                            </svg>
                            {{ __('Bill Payment') }}
                        </div>
                        <button type="button" class="close-button" id="close-modal-btn">&times;</button>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="main-content">
                    <form action="{{ route('receive-bill-payment.payment') }}" method="POST" id="bill-payment-form">
                        @csrf
                        <input type="hidden" name="amount_paid" id="amount_paid_hidden" value="0">

                    <!-- Top Vendor Bar -->
                    <div class="top-vendor-bar">
                        <div class="row align-items-start">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('Payee') }} <span class="text-danger">*</span></label>
                                    <select name="vendor_id" id="vendor_id" class="form-select" required>
                                        @foreach ($vendors as $id => $name)
                                            <option value="{{ $id }}" {{ (isset($vendorId) && $vendorId == $id) ? 'selected' : '' }}>
                                                {{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="find-bill-wrapper">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="find-by-bill-btn">
                                        <i class="ti ti-search"></i> {{ __('Find by bill no.') }}
                                    </button>
                                    <div class="find-bill-dropdown" id="find-bill-dropdown">
                                        <input type="text" class="form-control" id="find-bill-input" placeholder="{{ __('Search bill no...') }}">
                                        <div class="find-bill-results" id="find-bill-results"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4"></div>
                            <div class="col-md-4">
                                <div class="amount-display-top">
                                    <div class="balance-row">
                                        <span class="balance-label">{{ __('Vendor Balance') }}</span>
                                        <span class="balance-value" id="vendor-balance">{{ Auth::user()->priceFormat($vendorBalance ?? 0) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Amount Paid and Payment Details Row -->
                    <div class="row">
                        <div class="col-md-6">
                            <!-- Amount Paid Card -->
                            <div class="qbo-card">
                                <div class="qbo-card-header">
                                    <span class="qbo-section-title">{{ __('Payment Amount') }}</span>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ __('Amount') }}</label>
                                    <div class="amount-input-inline">
                                        <span class="currency-symbol">{{ Auth::user()->currencySymbol() }}</span>
                                        <input type="number" id="amount_paid"
                                               class="amount-paid-input"
                                               step="0.01" min="0" value="0.00" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ __('Vendor Balance') }}</label>
                                    <div class="balance-display">
                                        <span class="balance-value" id="vendor-balance-display">{{ Auth::user()->priceFormat($vendorBalance ?? 0) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <!-- Payment Details Card -->
                            <div class="qbo-card">
                                <div class="qbo-card-header">
                                    <span class="qbo-section-title">{{ __('Payment Details') }}</span>
                                </div>

                                <div class="form-grid-4">
                                    <div class="mb-3 payment-account-field">
                                        <label class="form-label">{{ __('Bank/Credit account') }} <span class="text-danger">*</span></label>
                                        <select name="payment_account" class="form-select" required>
                                            @foreach ($bankAccounts as $id => $name)
                                                <option value="{{ $id }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3 payment-date-field">
                                        <label class="form-label">{{ __('Payment Date') }} <span class="text-danger">*</span></label>
                                        <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Ref no.') }}</label>
                                        <input type="text" name="reference_no" class="form-control" placeholder="{{ __('Optional') }}">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Payment method') }}</label>
                                        <select name="payment_method" class="form-select">
                                            <option value="">{{ __('Select method') }}</option>
                                            <option value="cash">{{ __('Cash') }}</option>
                                            <option value="check">{{ __('Check') }}</option>
                                            <option value="credit_card">{{ __('Credit Card') }}</option>
                                            <option value="bank_transfer">{{ __('Bank Transfer') }}</option>
                                        </select>
                                    </div>
                                </div>
                           </div>
                       </div>
                    </div>

                    <!-- Outstanding Transactions Section -->
                    <div class="qbo-card">
                        <div class="qbo-card-header">
                            <span class="qbo-section-title">{{ __('Outstanding Transactions') }}</span>
                            <button type="button" class="btn btn-outline-secondary btn-sm ms-auto" id="clear-payment-btn">
                                {{ __('Clear Payment') }}
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table" id="outstanding-bills-table">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;">
                                            <input type="checkbox" id="select-all-bills" class="form-check-input">
                                        </th>
                                        <th>{{ __('Description') }}</th>
                                        <th>{{ __('Due Date') }}</th>
                                        <th class="text-end">{{ __('Original Amount') }}</th>
                                        <th class="text-end">{{ __('Open Balance') }}</th>
                                        <th class="text-end" style="width: 150px;">{{ __('Payment') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="bills-tbody">
                                    @if(isset($outstandingBills) && $outstandingBills->count() > 0)
                                        @foreach($outstandingBills as $bill)
                                            <tr data-bill-id="{{ $bill->id }}" data-due="{{ $bill->getDue() }}">
                                                <td>
                                                    <input type="checkbox" class="form-check-input bill-checkbox"
                                                           data-bill-id="{{ $bill->id }}">
                                                </td>
                                                <td>{{ __('Bill') }} #{{ Auth::user()->billNumberFormat($bill->bill_id) }}</td>
                                                <td>{{ Auth::user()->dateFormat($bill->due_date) }}</td>
                                                <td class="text-end">{{ Auth::user()->priceFormat($bill->getTotal()) }}</td>
                                                <td class="text-end open-balance">{{ Auth::user()->priceFormat($bill->getDue()) }}</td>
                                                <td class="text-end">
                                                    <input type="number" name="payments[{{ $bill->id }}]"
                                                           class="form-control form-control-sm payment-input text-end"
                                                           step="0.01" min="0" max="{{ $bill->getDue() }}"
                                                           value="0.00" data-max="{{ $bill->getDue() }}">
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr id="no-bills-row">
                                            <td colspan="6" class="text-center text-muted py-4">
                                                {{ __('Select a vendor to see outstanding bills') }}
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        <!-- Amount Summary -->
                        <div class="row mt-3">
                            <div class="col-md-6"></div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>{{ __('Amount to Apply') }}</span>
                                    <span id="amount-to-apply">{{ Auth::user()->currencySymbol() }}0.00</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>{{ __('Amount to Credit') }}</span>
                                    <span id="amount-to-credit">{{ Auth::user()->currencySymbol() }}0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Memo Section -->
                    <div class="qbo-card">
                        <div class="mb-3">
                            <label class="form-label">{{ __('Memo') }}</label>
                            <textarea name="memo" class="form-control" rows="3" placeholder="{{ __('Add a note for your records') }}"></textarea>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">{{ __('Attachments') }}</label>
                            <div class="attachment-zone">
                                <input type="file" name="attachments[]" multiple class="d-none" id="attachment-input">
                                <label for="attachment-input" class="attachment-link" style="cursor: pointer;">
                                    {{ __('Drag and drop or select files to attach') }}
                                </label>
                                <div class="attachment-limit">{{ __('Maximum size: 20MB') }}</div>
                            </div>
                        </div>
                    </div>

                    </form>
                </div>

                <!-- Footer Section -->
                <div class="footer-section">
                    <div>
                        <button type="button" class="btn btn-qbo-secondary" id="cancel-btn">{{ __('Cancel') }}</button>
                        <button type="button" class="btn btn-qbo-secondary ms-2" id="clear-btn">{{ __('Clear') }}</button>
                    </div>
                    <div class="footer-center">
                        <button type="button" class="footer-link" id="print-btn">
                            <i class="ti ti-printer"></i> {{ __('Print') }}
                        </button>
                    </div>
                    <div class="btn-group-qbo">
                        <button type="submit" form="bill-payment-form" class="btn btn-qbo-primary btn-main">
                            {{ __('Save and close') }}
                        </button>
                        <button type="button" class="btn btn-qbo-primary btn-arrow dropdown-toggle dropdown-toggle-split"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="visually-hidden">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" id="save-new-btn">{{ __('Save and new') }}</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
<script>
$(document).ready(function() {
    // Close modal handler
    $('#close-modal-btn, #cancel-btn').on('click', function() {
        window.location.href = '{{ route("bill.index") }}';
    });

    // Load outstanding bills function
    function loadOutstandingBills(vendorId) {
        if (!vendorId) {
            $('#bills-tbody').html('<tr id="no-bills-row"><td colspan="6" class="text-center text-muted py-4">{{ __("Select a vendor to see outstanding bills") }}</td></tr>');
            return;
        }

        $.ajax({
            url: '{{ route("receive-bill-payment.outstanding-bills") }}',
            method: 'POST',
            data: {
                vendor_id: vendorId,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                console.log('Response:', response);
                var html = '';
                if (response.bills && response.bills.length > 0) {
                    response.bills.forEach(function(bill) {
                        html += '<tr data-bill-id="' + bill.id + '" data-due="' + bill.due + '">';
                        html += '<td><input type="checkbox" class="form-check-input bill-checkbox" data-bill-id="' + bill.id + '"></td>';
                        html += '<td>{{ __("Bill") }} #' + bill.bill_id + '</td>';
                        html += '<td>' + bill.due_date + '</td>';
                        html += '<td class="text-end">' + bill.total_formatted + '</td>';
                        html += '<td class="text-end open-balance">' + bill.due_formatted + '</td>';
                        html += '<td class="text-end"><input type="number" name="payments[' + bill.id + ']" class="form-control form-control-sm payment-input text-end" step="0.01" min="0" max="' + bill.due + '" value="0.00" data-max="' + bill.due + '"></td>';
                        html += '</tr>';
                    });
                } else {
                    html = '<tr id="no-bills-row"><td colspan="6" class="text-center text-muted py-4">{{ __("No outstanding bills for this vendor") }}</td></tr>';
                }
                $('#bills-tbody').html(html);
                $('#vendor-balance, #vendor-balance-display').text(response.vendor_balance_formatted);
                distributeAmountPaid();
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.error('Response:', xhr.responseText);
            }
        });
    }

    // Vendor change handler - works with both native select and Choices.js
    var vendorSelect = document.getElementById('vendor_id');
    if (vendorSelect) {
        // Listen for native change event
        vendorSelect.addEventListener('change', function() {
            var vendorId = this.value;
            console.log('Vendor selected:', vendorId);
            loadOutstandingBills(vendorId);
        });
    }

    // Select all bills checkbox
    $(document).on('change', '#select-all-bills', function() {
        var isChecked = $(this).is(':checked');
        $('.bill-checkbox').prop('checked', isChecked);
        var amountPaid = parseFloat($('#amount_paid').val()) || 0;

        if (isChecked) {
            if (amountPaid > 0) {
                distributeAmountPaid();
            } else {
                // When amount paid is 0, set all checked to due amounts
                $('.bill-checkbox').each(function() {
                    var $checkbox = $(this);
                    var $row = $checkbox.closest('tr');
                    var $input = $row.find('.payment-input');
                    if ($checkbox.is(':checked')) {
                        var due = parseFloat($row.data('due'));
                        $input.val(due.toFixed(2));
                    } else {
                        $input.val('0.00');
                    }
                });
                updateAmountPaidFromPayments();
            }
        } else {
            // When unchecking all, subtract all payment amounts from amount paid
            var totalPayments = 0;
            $('.payment-input').each(function() {
                totalPayments += parseFloat($(this).val()) || 0;
            });
            var amountPaid = parseFloat($('#amount_paid').val()) || 0;
            var newAmountPaid = Math.max(0, amountPaid - totalPayments);
            $('#amount_paid').val(newAmountPaid.toFixed(2));
            $('.payment-input').val('0.00');
            calculateTotals();
        }
    });

    // Individual bill checkbox - redistribute amount paid when checked/unchecked
    $(document).on('change', '.bill-checkbox', function() {
        var $checkbox = $(this);
        var $row = $checkbox.closest('tr');
        var $input = $row.find('.payment-input');
        var amountPaid = parseFloat($('#amount_paid').val()) || 0;

        if ($checkbox.is(':checked')) {
            if (amountPaid > 0) {
                // When amount paid > 0, set to 0 initially, then redistribute
                $input.val('0.00');
                distributeAmountPaid();
            } else {
                // When amount paid is 0, set to due amount (old behavior)
                var due = parseFloat($row.data('due'));
                $input.val(due.toFixed(2));
                updateAmountPaidFromPayments();
            }
        } else {
            // When unchecking, subtract the payment amount from amount paid
            var paymentAmount = parseFloat($input.val()) || 0;
            var newAmountPaid = Math.max(0, amountPaid - paymentAmount);
            $('#amount_paid').val(newAmountPaid.toFixed(2));
            $input.val('0.00');

            // If there are still checked bills and amount paid > 0, redistribute
            var $checkedBoxes = $('.bill-checkbox:checked');
            if ($checkedBoxes.length > 0 && newAmountPaid > 0) {
                distributeAmountPaid();
            } else {
                calculateTotals();
            }
        }
    });

    // Payment input change - ensure it doesn't exceed max and update amount paid
    $(document).on('input', '.payment-input', function() {
        var $input = $(this);
        var max = parseFloat($input.data('max'));
        var val = parseFloat($input.val()) || 0;
        if (val > max) {
            $input.val(max.toFixed(2));
            val = max;
        }

        // Auto-check the checkbox if payment > 0
        var $row = $input.closest('tr');
        var $checkbox = $row.find('.bill-checkbox');
        if (val > 0 && !$checkbox.is(':checked')) {
            $checkbox.prop('checked', true);
        } else if (val === 0 && $checkbox.is(':checked')) {
            $checkbox.prop('checked', false);
        }

        // Update amount paid to sum of all payments
        updateAmountPaidFromPayments();
    });

    // Amount paid change - distribute among checked bills
    $('#amount_paid').on('input', function() {
        distributeAmountPaid();
    });

    // Clear payment button
    $('#clear-payment-btn, #clear-btn').on('click', function() {
        $('.payment-input').val('0.00');
        $('.bill-checkbox').prop('checked', false);
        $('#select-all-bills').prop('checked', false);
        $('#amount_paid').val('0.00');
        calculateTotals();
    });

    // Update amount paid based on sum of payment inputs (for zero amount case)
    function updateAmountPaidFromPayments() {
        var total = 0;
        $('.payment-input').each(function() {
            total += parseFloat($(this).val()) || 0;
        });
        $('#amount_paid').val(total.toFixed(2));
        calculateTotals();
    }

    // Distribute amount paid among checked bills
    function distributeAmountPaid() {
        var amountPaid = parseFloat($('#amount_paid').val()) || 0;
        var $checkedBoxes = $('.bill-checkbox:checked');

        if ($checkedBoxes.length === 0) {
            $('.payment-input').val('0.00');
            calculateTotals();
            return;
        }

        // Calculate total due for checked bills
        var totalDue = 0;
        $checkedBoxes.each(function() {
            var billId = $(this).data('bill-id');
            var $row = $('tr[data-bill-id="' + billId + '"]');
            var due = parseFloat($row.data('due'));
            totalDue += due;
        });

        // Distribute amount among checked bills
        var remaining = amountPaid;
        $checkedBoxes.each(function() {
            var billId = $(this).data('bill-id');
            var $row = $('tr[data-bill-id="' + billId + '"]');
            var $input = $row.find('.payment-input');
            var due = parseFloat($row.data('due'));

            var allocate = Math.min(due, remaining);
            $input.val(allocate.toFixed(2));
            remaining -= allocate;
        });

        calculateTotals();
    }

    // Calculate totals
    function calculateTotals() {
        var amountPaid = parseFloat($('#amount_paid').val()) || 0;
        var totalApplied = 0;

        $('.payment-input').each(function() {
            totalApplied += parseFloat($(this).val()) || 0;
        });

        var amountToCredit = Math.max(0, amountPaid - totalApplied);

        $('#amount-to-apply').text('{{ Auth::user()->currencySymbol() }}' + totalApplied.toFixed(2));
        $('#amount-to-credit').text('{{ Auth::user()->currencySymbol() }}' + amountToCredit.toFixed(2));

        // Update hidden field for form submission
        $('#amount_paid_hidden').val(amountPaid);
    }

    // Find by bill number functionality
    $('#find-by-bill-btn').on('click', function(e) {
        e.stopPropagation();
        $('#find-bill-dropdown').toggleClass('show');
        $('#find-bill-input').focus();
    });

    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.find-bill-wrapper').length) {
            $('#find-bill-dropdown').removeClass('show');
        }
    });

    // Search bills by number
    var searchTimeout;
    $('#find-bill-input').on('input', function() {
        var searchVal = $(this).val();
        clearTimeout(searchTimeout);

        if (searchVal.length < 1) {
            $('#find-bill-results').html('');
            return;
        }

        searchTimeout = setTimeout(function() {
            $.ajax({
                url: '{{ route("receive-bill-payment.outstanding-bills") }}',
                method: 'POST',
                data: {
                    bill_no: searchVal,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    var html = '';
                    if (response.bills && response.bills.length > 0) {
                        response.bills.forEach(function(bill) {
                            html += '<div class="find-bill-item" data-bill-id="' + bill.id + '" data-vendor-id="' + response.vendor_id + '">';
                            html += '<span>Bill #' + bill.bill_id + '</span>';
                            html += '<span class="text-success">' + bill.due_formatted + '</span>';
                            html += '</div>';
                        });
                    } else {
                        html = '<div class="text-muted text-center py-2">{{ __("No bills found") }}</div>';
                    }
                    $('#find-bill-results').html(html);
                }
            });
        }, 300);
    });

    // Select bill from search results
    $(document).on('click', '.find-bill-item', function() {
        var vendorId = $(this).data('vendor-id');
        var billId = $(this).data('bill-id');

        // Check if bill is already in the table
        var $existingRow = $('tr[data-bill-id="' + billId + '"]');
        if ($existingRow.length) {
            // Mark it as checked
            var $checkbox = $existingRow.find('.bill-checkbox');
            if (!$checkbox.is(':checked')) {
                $checkbox.prop('checked', true).trigger('change');
            }
            $('#find-bill-dropdown').removeClass('show');
            $('#find-bill-input').val('');
            $('#find-bill-results').html('');
            return;
        }

        // If not in table, need to load vendor's bills first
        if (vendorId) {
            // Set the vendor dropdown value
            $('#vendor_id').val(vendorId);
            
            // Dispatch native change event to load outstanding bills
            var vendorSelect = document.getElementById('vendor_id');
            var event = new Event('change', { bubbles: true });
            vendorSelect.dispatchEvent(event);

            // After loading, check the specific bill
            setTimeout(function() {
                var $row = $('tr[data-bill-id="' + billId + '"]');
                if ($row.length) {
                    var $checkbox = $row.find('.bill-checkbox');
                    $checkbox.prop('checked', true).trigger('change');
                }
            }, 600);
        }

        $('#find-bill-dropdown').removeClass('show');
        $('#find-bill-input').val('');
        $('#find-bill-results').html('');
    });

    // Form validation before submit
    $('#bill-payment-form').on('submit', function(e) {
        var amountPaid = parseFloat($('#amount_paid').val()) || 0;
        var vendorId = $('#vendor_id').val();

        if (!vendorId) {
            e.preventDefault();
            alert('{{ __("Please select a vendor") }}');
            return false;
        }

        if (amountPaid <= 0) {
            e.preventDefault();
            alert('{{ __("Please enter a payment amount") }}');
            return false;
        }

        return true;
    });

    // Save and new button
    $('#save-new-btn').on('click', function(e) {
        e.preventDefault();
        var $form = $('#bill-payment-form');
        $form.append('<input type="hidden" name="save_and_new" value="1">');
        $form.submit();
    });

    // Initial load if vendor is pre-selected
    @if(isset($vendorId) && $vendorId)
        loadOutstandingBills('{{ $vendorId }}');
    @endif

    // Pre-select bill if specified
    @if(isset($preSelectedBill) && $preSelectedBill)
        setTimeout(function() {
            var $row = $('tr[data-bill-id="{{ $preSelectedBill->id }}"]');
            var $checkbox = $row.find('.bill-checkbox');
            $checkbox.prop('checked', true).trigger('change');
        }, 500);
    @endif
});
</script>
@endpush
