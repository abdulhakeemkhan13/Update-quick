@extends('layouts.admin')
@section('page-title')
    {{ __('Receive Payment') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('receive-payment.index') }}">{{ __('Payments') }}</a></li>
    <li class="breadcrumb-item">{{ __('Receive Payment') }}</li>
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

        .top-customer-bar {
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

        #outstanding-invoices-table th {
            font-size: 12px;
            font-weight: 600;
            color: var(--qbo-gray-text);
            text-transform: uppercase;
            border-bottom: 2px solid var(--qbo-border-color);
        }

        #outstanding-invoices-table td {
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

        .amount-received-input {
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

        .amount-received-input:focus {
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

        /* Find by Invoice Dropdown */
        .find-invoice-wrapper {
            position: relative;
            display: inline-block;
        }

        .find-invoice-dropdown {
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

        .find-invoice-dropdown.show {
            display: block;
        }

        .find-invoice-results {
            max-height: 200px;
            overflow-y: auto;
            margin-top: 10px;
        }

        .find-invoice-item {
            padding: 8px 10px;
            cursor: pointer;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .find-invoice-item:hover {
            background: #f4f5f8;
        }

        .find-invoice-item.selected {
            background: #e8f5e3;
        }

        .text-success {
            color: var(--qbo-green) !important;
        }
    </style>
@endpush

@section('content')
    <!-- Modal -->
    <div class="modal fade show" id="receive-payment-modal" tabindex="-1" aria-labelledby="receivePaymentModalLabel"
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
                            {{ __('Receive Payment') }}
                        </div>
                        <button type="button" class="close-button" id="close-modal-btn">&times;</button>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="main-content">
                    <form action="{{ route('receive-payment.payment') }}" method="POST" id="receive-payment-form">
                        @csrf

                    <!-- Top Customer Bar -->
                    <div class="top-customer-bar">
                        <div class="row align-items-start">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('Customer') }}</label>
                                    <select name="customer_id" id="customer_id" class="form-select" required>
                                        @foreach ($customers as $id => $name)
                                            <option value="{{ $id }}" {{ $customerId == $id ? 'selected' : '' }}>
                                                {{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="find-invoice-wrapper">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="find-by-invoice-btn">
                                        <i class="ti ti-search"></i> {{ __('Find by invoice no.') }}
                                    </button>
                                    <div class="find-invoice-dropdown" id="find-invoice-dropdown">
                                        <input type="text" class="form-control" id="find-invoice-input" placeholder="{{ __('Search invoice no...') }}">
                                        <div class="find-invoice-results" id="find-invoice-results"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4"></div>
                            <div class="col-md-4">
                                <div class="amount-display-top">
                                    <div class="balance-row">
                                        <span class="balance-label">{{ __('Customer Balance') }}</span>
                                        <span class="balance-value" id="customer-balance">{{ Auth::user()->priceFormat($customerBalance ?? 0) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Amount Received and Record/Charge Row -->
                    <div class="row">
                        <div class="col-md-6">
                            <!-- Amount Received Card -->
                            <div class="qbo-card">
                                <div class="qbo-card-header">
                                    <span class="qbo-section-title">{{ __('Amount Received') }}</span>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ __('Amount') }}</label>
                                    <div class="amount-input-inline">
                                        <span class="currency-symbol">{{ Auth::user()->currencySymbol() }}</span>
                                        <input type="number" name="amount_received" id="amount_received"
                                               class="amount-received-input"
                                               step="0.01" min="0" value="0.00" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ __('Customer Balance') }}</label>
                                    <div class="balance-display">
                                        <span class="balance-value" id="customer-balance">{{ Auth::user()->priceFormat($customerBalance ?? 0) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <!-- Record or Charge Card -->
                            <div class="qbo-card">
                        <div class="qbo-card-header">
                            <span class="qbo-section-title">{{ __('Record or charge') }}</span>
                        </div>

                        <div class="payment-type-group d-flex gap-4">
                            <div>
                                <div class="payment-option">
                                    <input type="radio" name="payment_type" id="record_payment" value="record_payment" checked>
                                    <label for="record_payment">{{ __('Record payment') }}</label>
                                </div>
                                <div class="payment-option-desc">{{ __('Received via check, cash, other') }}</div>
                            </div>
                            <div>
                                <div class="payment-option">
                                    <input type="radio" name="payment_type" id="charge_payment" value="charge_payment">
                                    <label for="charge_payment">{{ __('Charge payment') }}</label>
                                </div>
                                <div class="payment-option-desc">{{ __('Charge to customer\'s card') }}</div>
                            </div>
                        </div>

                        <div id="record-payment-fields">
                            <div class="form-grid-4">
                                <div class="mb-3 payment-date-field">
                                    <label class="form-label">{{ __('Payment Date') }}</label>
                                    <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ __('Reference no.') }}</label>
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
                                <div class="mb-3 deposit-to-field">
                                    <label class="form-label">{{ __('Deposit To') }}</label>
                                    <select name="deposit_to" class="form-select">
                                        @foreach ($bankAccounts as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
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
                            <table class="table" id="outstanding-invoices-table">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;">
                                            <input type="checkbox" id="select-all-invoices" class="form-check-input">
                                        </th>
                                        <th>{{ __('Description') }}</th>
                                        <th>{{ __('Due Date') }}</th>
                                        <th class="text-end">{{ __('Original Amount') }}</th>
                                        <th class="text-end">{{ __('Open Balance') }}</th>
                                        <th class="text-end" style="width: 150px;">{{ __('Payment') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="invoices-tbody">
                                    @if(isset($outstandingInvoices) && $outstandingInvoices->count() > 0)
                                        @foreach($outstandingInvoices as $invoice)
                                            <tr data-invoice-id="{{ $invoice->id }}" data-due="{{ $invoice->getDue() }}">
                                                <td>
                                                    <input type="checkbox" class="form-check-input invoice-checkbox"
                                                           data-invoice-id="{{ $invoice->id }}">
                                                </td>
                                                <td>{{ __('Invoice') }} #{{ Auth::user()->invoiceNumberFormat($invoice->invoice_id) }}</td>
                                                <td>{{ Auth::user()->dateFormat($invoice->due_date) }}</td>
                                                <td class="text-end">{{ Auth::user()->priceFormat($invoice->getTotal()) }}</td>
                                                <td class="text-end open-balance">{{ Auth::user()->priceFormat($invoice->getDue()) }}</td>
                                                <td class="text-end">
                                                    <input type="number" name="payments[{{ $invoice->id }}]"
                                                           class="form-control form-control-sm payment-input text-end"
                                                           step="0.01" min="0" max="{{ $invoice->getDue() }}"
                                                           value="0.00" data-max="{{ $invoice->getDue() }}">
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr id="no-invoices-row">
                                            <td colspan="6" class="text-center text-muted py-4">
                                                {{ __('Select a customer to see outstanding invoices') }}
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
                        <button type="submit" form="receive-payment-form" class="btn btn-qbo-primary btn-main">
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
        window.location.href = '{{ route("receive-payment.index") }}';
    });

    // Load outstanding invoices function
    function loadOutstandingInvoices(customerId) {
        if (!customerId) {
            $('#invoices-tbody').html('<tr id="no-invoices-row"><td colspan="6" class="text-center text-muted py-4">{{ __("Select a customer to see outstanding invoices") }}</td></tr>');
            return;
        }

        $.ajax({
            url: '{{ route("receive-payment.outstanding-invoices") }}',
            method: 'POST',
            data: {
                customer_id: customerId,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                console.log('Response:', response);
                var html = '';
                if (response.invoices && response.invoices.length > 0) {
                    response.invoices.forEach(function(invoice) {
                        html += '<tr data-invoice-id="' + invoice.id + '" data-due="' + invoice.due + '">';
                        html += '<td><input type="checkbox" class="form-check-input invoice-checkbox" data-invoice-id="' + invoice.id + '"></td>';
                        html += '<td>{{ __("Invoice") }} #' + invoice.invoice_id + '</td>';
                        html += '<td>' + invoice.due_date + '</td>';
                        html += '<td class="text-end">' + invoice.total_formatted + '</td>';
                        html += '<td class="text-end open-balance">' + invoice.due_formatted + '</td>';
                        html += '<td class="text-end"><input type="number" name="payments[' + invoice.id + ']" class="form-control form-control-sm payment-input text-end" step="0.01" min="0" max="' + invoice.due + '" value="0.00" data-max="' + invoice.due + '"></td>';
                        html += '</tr>';
                    });
                } else {
                    html = '<tr id="no-invoices-row"><td colspan="6" class="text-center text-muted py-4">{{ __("No outstanding invoices for this customer") }}</td></tr>';
                }
                $('#invoices-tbody').html(html);
                $('#customer-balance').text(response.customer_balance_formatted);
                distributeAmountReceived();
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.error('Response:', xhr.responseText);
            }
        });
    }

    // Customer change handler - works with both native select and Choices.js
    var customerSelect = document.getElementById('customer_id');
    if (customerSelect) {
        // Listen for native change event
        customerSelect.addEventListener('change', function() {
            var customerId = this.value;
            console.log('Customer selected:', customerId);
            loadOutstandingInvoices(customerId);
        });
    }

    // Select all invoices checkbox
    $(document).on('change', '#select-all-invoices', function() {
        var isChecked = $(this).is(':checked');
        $('.invoice-checkbox').prop('checked', isChecked);
        var amountReceived = parseFloat($('#amount_received').val()) || 0;

        if (isChecked) {
            if (amountReceived > 0) {
                distributeAmountReceived();
            } else {
                // When amount received is 0, set all checked to due amounts
                $('.invoice-checkbox').each(function() {
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
                updateAmountReceivedFromPayments();
            }
        } else {
            // When unchecking all, subtract all payment amounts from amount received
            var totalPayments = 0;
            $('.payment-input').each(function() {
                totalPayments += parseFloat($(this).val()) || 0;
            });
            var amountReceived = parseFloat($('#amount_received').val()) || 0;
            var newAmountReceived = Math.max(0, amountReceived - totalPayments);
            $('#amount_received').val(newAmountReceived.toFixed(2));
            $('.payment-input').val('0.00');
            calculateTotals();
        }
    });

    // Individual invoice checkbox - redistribute amount received when checked/unchecked
    $(document).on('change', '.invoice-checkbox', function() {
        var $checkbox = $(this);
        var $row = $checkbox.closest('tr');
        var $input = $row.find('.payment-input');
        var amountReceived = parseFloat($('#amount_received').val()) || 0;

        if ($checkbox.is(':checked')) {
            if (amountReceived > 0) {
                // When amount received > 0, set to 0 initially, then redistribute
                $input.val('0.00');
                distributeAmountReceived();
            } else {
                // When amount received is 0, set to due amount (old behavior)
                var due = parseFloat($row.data('due'));
                $input.val(due.toFixed(2));
                updateAmountReceivedFromPayments();
            }
        } else {
            // When unchecking, subtract the payment amount from amount received
            var paymentAmount = parseFloat($input.val()) || 0;
            var newAmountReceived = Math.max(0, amountReceived - paymentAmount);
            $('#amount_received').val(newAmountReceived.toFixed(2));
            $input.val('0.00');

            // If there are still checked invoices and amount received > 0, redistribute
            var $checkedBoxes = $('.invoice-checkbox:checked');
            if ($checkedBoxes.length > 0 && newAmountReceived > 0) {
                distributeAmountReceived();
            } else {
                calculateTotals();
            }
        }
    });

    // Payment input change - ensure it doesn't exceed max and update amount received
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
        var $checkbox = $row.find('.invoice-checkbox');
        if (val > 0 && !$checkbox.is(':checked')) {
            $checkbox.prop('checked', true);
        } else if (val === 0 && $checkbox.is(':checked')) {
            $checkbox.prop('checked', false);
        }

        // Update amount received to sum of all payments
        updateAmountReceivedFromPayments();
    });

    // Amount received change - distribute among checked invoices
    $('#amount_received').on('input', function() {
        distributeAmountReceived();
    });

    // Clear payment button
    $('#clear-payment-btn, #clear-btn').on('click', function() {
        $('.payment-input').val('0.00');
        $('.invoice-checkbox').prop('checked', false);
        $('#select-all-invoices').prop('checked', false);
        $('#amount_received').val('0.00');
        calculateTotals();
    });

    // Update amount received based on sum of payment inputs (for zero amount case)
    function updateAmountReceivedFromPayments() {
        var total = 0;
        $('.payment-input').each(function() {
            total += parseFloat($(this).val()) || 0;
        });
        $('#amount_received').val(total.toFixed(2));
        calculateTotals();
    }

    // Distribute amount received among checked invoices
    function distributeAmountReceived() {
        var amountReceived = parseFloat($('#amount_received').val()) || 0;
        var $checkedBoxes = $('.invoice-checkbox:checked');

        if ($checkedBoxes.length === 0) {
            $('.payment-input').val('0.00');
            calculateTotals();
            return;
        }

        // Calculate total due for checked invoices
        var totalDue = 0;
        $checkedBoxes.each(function() {
            var invoiceId = $(this).data('invoice-id');
            var $row = $('tr[data-invoice-id="' + invoiceId + '"]');
            var due = parseFloat($row.data('due'));
            totalDue += due;
        });

        // If amount received >= total due, distribute proportionally
        if (amountReceived >= totalDue) {
            $checkedBoxes.each(function() {
                var invoiceId = $(this).data('invoice-id');
                var $row = $('tr[data-invoice-id="' + invoiceId + '"]');
                var $input = $row.find('.payment-input');
                var due = parseFloat($row.data('due'));
                $input.val(due.toFixed(2));
            });
        } else {
            // Distribute amount received sequentially (first-fit) among checked invoices
            var remainingAmount = amountReceived;
    
            $checkedBoxes.each(function() {
                var invoiceId = $(this).data('invoice-id');
                var $row = $('tr[data-invoice-id="' + invoiceId + '"]');
                var $input = $row.find('.payment-input');
                var due = parseFloat($row.data('due'));
    
                if (remainingAmount > 0) {
                    var allocatedAmount = Math.min(remainingAmount, due);
                    $input.val(allocatedAmount.toFixed(2));
                    remainingAmount -= allocatedAmount;
                } else {
                    $input.val('0.00');
                }
            });
        }

        calculateTotals();
    }


    function calculateTotals() {
        var totalApplied = 0;
        $('.payment-input').each(function() {
            totalApplied += parseFloat($(this).val()) || 0;
        });
        var amountReceived = parseFloat($('#amount_received').val()) || 0;
        var amountToCredit = amountReceived - totalApplied;

        // Update bottom summary
        $('#amount-to-apply').text('{{ Auth::user()->currencySymbol() }}' + totalApplied.toFixed(2));
        $('#amount-to-credit').text('{{ Auth::user()->currencySymbol() }}' + Math.max(0, amountToCredit).toFixed(2));
    }

    // Charge payment toggle - hide/show date and deposit fields
    $('input[name="payment_type"]').on('change', function() {
        if ($(this).val() === 'charge_payment') {
            $('.payment-date-field, .deposit-to-field').hide();
        } else {
            $('.payment-date-field, .deposit-to-field').show();
        }
    });

    // Find by invoice no - toggle dropdown
    $('#find-by-invoice-btn').on('click', function(e) {
        e.stopPropagation();
        $('#find-invoice-dropdown').toggleClass('show');
        if ($('#find-invoice-dropdown').hasClass('show')) {
            $('#find-invoice-input').focus();
        }
    });

    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.find-invoice-wrapper').length) {
            $('#find-invoice-dropdown').removeClass('show');
        }
    });

    // Search invoice on input
    var searchTimer;
    $('#find-invoice-input').on('input', function() {
        var invoiceNo = $(this).val().trim();
        clearTimeout(searchTimer);

        if (invoiceNo.length < 1) {
            $('#find-invoice-results').html('');
            return;
        }

        searchTimer = setTimeout(function() {
            $.ajax({
                url: '{{ route("receive-payment.outstanding-invoices") }}',
                method: 'POST',
                data: {
                    invoice_no: invoiceNo,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    var html = '';
                    if (response.invoices && response.invoices.length > 0) {
                        response.invoices.forEach(function(invoice) {
                            // Check if this invoice is already in the table and checked
                            var isSelected = $('tr[data-invoice-id="' + invoice.id + '"] .invoice-checkbox').is(':checked');
                            html += '<div class="find-invoice-item' + (isSelected ? ' selected' : '') + '" data-invoice-id="' + invoice.id + '" data-customer-id="' + response.customer_id + '">';
                            html += '<span>{{ __("Invoice") }} #' + invoice.invoice_id + '</span>';
                            html += '<span>' + invoice.due_formatted + '</span>';
                            html += '</div>';
                        });
                    } else {
                        html = '<div class="text-muted text-center py-2">{{ __("No invoices found") }}</div>';
                    }
                    $('#find-invoice-results').html(html);
                },
                error: function() {
                    $('#find-invoice-results').html('<div class="text-danger text-center py-2">{{ __("Error searching") }}</div>');
                }
            });
        }, 300);
    });

    // Select invoice from dropdown
    $(document).on('click', '.find-invoice-item', function() {
        var invoiceId = $(this).data('invoice-id');
        var customerId = $(this).data('customer-id');

        // Check if invoice is already in the table
        var $existingRow = $('tr[data-invoice-id="' + invoiceId + '"]');
        if ($existingRow.length) {
            // Mark it as checked
            var $checkbox = $existingRow.find('.invoice-checkbox');
            if (!$checkbox.is(':checked')) {
                $checkbox.prop('checked', true).trigger('change');
            }
            $('#find-invoice-dropdown').removeClass('show');
            return;
        }

        // If not in table, need to load customer's invoices first
        if (customerId) {
            $('#customer_id').val(customerId);
            var event = new Event('change');
            document.getElementById('customer_id').dispatchEvent(event);

            // After loading, check the specific invoice
            setTimeout(function() {
                var $row = $('tr[data-invoice-id="' + invoiceId + '"]');
                if ($row.length) {
                    var $checkbox = $row.find('.invoice-checkbox');
                    $checkbox.prop('checked', true).trigger('change');
                }
            }, 500);
        }

        $('#find-invoice-dropdown').removeClass('show');
        $('#find-invoice-input').val('');
        $('#find-invoice-results').html('');
    });

    // Load invoices on page load if customer is pre-selected
    @if($customerId)
        loadOutstandingInvoices('{{ $customerId }}');
    @endif

    // Pre-select invoice if specified in URL parameter
    @if(isset($preSelectedInvoice) && $preSelectedInvoice)
        setTimeout(function() {
            var preSelectedInvoiceId = '{{ $preSelectedInvoice->id }}';
            var $row = $('tr[data-invoice-id="' + preSelectedInvoiceId + '"]');
            if ($row.length) {
                var $checkbox = $row.find('.invoice-checkbox');
                var $input = $row.find('.payment-input');
                var dueAmount = parseFloat($row.data('due'));

                // Check the invoice
                $checkbox.prop('checked', true);

                // Set payment amount to due amount
                $input.val(dueAmount.toFixed(2));

                // Update amount received
                $('#amount_received').val(dueAmount.toFixed(2));

                // Calculate totals
                calculateTotals();
            }
        }, @if($customerId) 500 @else 100 @endif);
    @endif
});
</script>
@endpush

