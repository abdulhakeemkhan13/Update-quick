@section('page-title')
    @if (isset($mode) && $mode == 'show')
        {{ __('Invoice View') }}
    @elseif(isset($mode) && $mode == 'edit')
        {{ __('Invoice Edit') }}
    @else
        {{ __('Invoice Create') }}
    @endif
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoice.index') }}">{{ __('Invoice') }}</a></li>
    @if (isset($mode) && $mode == 'show')
        <li class="breadcrumb-item">{{ __('Invoice View') }}</li>
    @elseif(isset($mode) && $mode == 'edit')
        <li class="breadcrumb-item">{{ __('Invoice Edit') }}</li>
    @else
        <li class="breadcrumb-item">{{ __('Invoice Create') }}</li>
    @endif
@endsection

<style>
    .invoice-container {
        background: #fff;
        max-width: 100%;
        margin: 0;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    .invoice-header {
        background: #fff;
        padding: 20px 32px;
        border-bottom: 1px solid #e4e4e7;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .header-left {
        display: flex;
        flex-direction: column;
        gap: 16px;
        flex: 1;
    }

    .invoice-title {
        font-size: 28px;
        font-weight: 400;
        color: #0077c5;
        margin: 0;
        letter-spacing: 0.5px;
    }

    .company-info {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .company-name {
        font-size: 15px;
        font-weight: 600;
        color: #393a3d;
    }

    .company-email {
        font-size: 14px;
        color: #6b6c72;
    }

    .edit-company-link {
        color: #0077c5;
        font-size: 14px;
        text-decoration: none;
        margin-top: 8px;
        display: inline-block;
        cursor: pointer;
    }

    .edit-company-link:hover {
        text-decoration: underline;
    }

    .header-right {
        display: flex;
        align-items: flex-start;
        gap: 20px;
    }

    .balance-due {
        font-size: 14px;
        color: #6b6c72;
        text-align: right;
    }

    .balance-amount {
        font-weight: 600;
        color: #393a3d;
    }

    .logo-section {
        width: 120px;
        height: 120px;
        border: 2px dashed #d4d4d8;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fafafa;
        cursor: pointer;
    }

    .customer-section {
        background: #f7f8fa;
        padding: 24px 32px;
        border-bottom: 1px solid #e4e4e7;
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
        border-color: #0077c5;
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

    .transaction-details {
        padding: 24px 32px;
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
        background: #f7f8fa;
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

    .btn-action.split-button::after {
        content: '';
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        width: 0;
        height: 0;
        border-left: 4px solid transparent;
        border-right: 4px solid transparent;
        border-top: 5px solid #0077c5;
    }

    .bottom-section {
        padding: 24px 32px;
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 40px;
        background: #fff;
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
        border-color: #0077c5;
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

    .invoice-footer {
        background: #f7f8fa;
        padding: 16px 32px;
        border-top: 1px solid #e4e4e7;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: auto;
        position: sticky;
        bottom: 0;
    }

    .footer-link {
        color: #0077c5;
        font-size: 14px;
        text-decoration: none;
        cursor: pointer;
    }

    .footer-link:hover {
        text-decoration: underline;
    }

    .footer-actions {
        display: flex;
        gap: 12px;
    }

    .btn {
        padding: 10px 24px;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
    }

    .btn-secondary {
        background: #fff;
        color: #393a3d;
        border: 1px solid #c4c4c4;
    }

    .btn-secondary:hover {
        background: #f7f8fa;
    }

    .btn-primary {
        background: #0b7e3a;
        color: #fff;
        padding-right: 40px;
        position: relative;
    }

    .btn-primary::after {
        content: '';
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        width: 0;
        height: 0;
        border-left: 4px solid transparent;
        border-right: 4px solid transparent;
        border-top: 5px solid #fff;
    }

    .btn-primary:hover {
        background: #096830;
    }

    .input-right {
        text-align: right;
    }
</style>

<div id="invoice-modal" class="invoice-container">
                    @if (isset($mode) && $mode == 'edit')
                        {{ Form::model($invoice, ['route' => ['invoice.update', Crypt::encrypt($invoice->id)], 'method' => 'PUT', 'id' => 'invoice-form', 'enctype' => 'multipart/form-data']) }}
                    @else
                        {{ Form::open(['route' => 'invoice.store', 'id' => 'invoice-form', 'enctype' => 'multipart/form-data']) }}
                    @endif
                    <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">

                    {{-- Header --}}
                    <div class="invoice-header">
                        <div class="header-left">
                            <h1 class="invoice-title">
                                @if (isset($mode) && $mode == 'show')
                                    {{ __('Invoice') }}
                                    {{ isset($invoice) ? \Auth::user()->invoiceNumberFormat($invoice->invoice_id) : $invoice_number }}
                                @elseif(isset($mode) && $mode == 'edit')
                                    {{ __('Invoice') }}
                                    {{ isset($invoice) ? \Auth::user()->invoiceNumberFormat($invoice->invoice_id) : $invoice_number }}
                                @else
                                    {{ __('Invoice') }} {{ $invoice_number }}
                                @endif
                            </h1>

                            <div class="company-info">
                                <div class="company-name">{{ Auth::user()->name }}</div>
                                <div class="company-email">{{ Auth::user()->email }}</div>
                                <a href="#" class="edit-company-link">{{ __('Edit company') }}</a>
                            </div>
                        </div>

                        <div class="header-right">
                            <div class="balance-due">
                                {{ __('Balance due (hidden):') }}<br>
                                <span class="balance-amount">{{ Auth::user()->priceFormat(0) }}</span>
                            </div>
                            <div class="logo-section" id="logo-upload-area">
                                <input type="file" id="logo-upload" name="logo" accept="image/*"
                                    style="display: none;">
                                <div id="logo-preview" style="display: none;">
                                    <img id="logo-image" src="" alt="Logo"
                                        style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                    <button type="button" id="remove-logo"
                                        style="position: absolute; top: 5px; right: 5px; background: red; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer;">&times;</button>
                                </div>
                                <div id="logo-placeholder">
                                    <span
                                        style="color: #c4c4c4; font-size: 12px; cursor: pointer;">{{ __('Add logo') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="">
                        {{-- Customer Section --}}
                        <div class="row customer-section">
                            <div class="customer-row col-12 col-md-8 col-lg-8">
                                <div class="customer-field">
                                    {{ Form::select('customer_id', $customers, isset($invoice) ? $invoice->customer_id : $customerId ?? '', [
                                        'class' => 'form-select',
                                        'id' => 'customer',
                                        'data-url' => route('invoice.customer'),
                                        'required' => 'required',
                                        'data-create-url' => route('customer.create'),
                                        'data-create-title' => __('Create New Customer'),
                                        isset($mode) && $mode == 'show' ? 'disabled' : '' => isset($mode) && $mode == 'show' ? 'disabled' : '',
                                    ]) }}
                                </div>
                            </div>

                            <div class="customer-row col-12 col-md-8 col-lg-8">
                                <div class="customer-field">
                                    {{ Form::email('customer_email', isset($invoice) && $invoice->customer ? $invoice->customer->email : '', [
                                        'class' => 'form-control',
                                        'id' => 'customer_email',
                                        'placeholder' => 'Customer email',
                                        isset($mode) && $mode == 'show' ? 'readonly' : '' => isset($mode) && $mode == 'show' ? 'readonly' : '',
                                    ]) }}
                                </div>
                                <a href="#" class="link-button">{{ __('Cc/Bcc') }}</a>
                            </div>
                            <div id="customer_detail" class="d-none small text-muted"></div>
                        </div>
                    </div>
                    {{-- Transaction Details --}}
                    <div class="row transaction-details">
                        <div class="d-flex gap-5">
                            <div class="detail-group">
                                <div class="field-group">
                                    <label class="form-label">{{ __('Bill to') }}</label>
                                    {{ Form::textarea('bill_to', isset($billTo) ? $billTo : (isset($invoice) ? $invoice->bill_to : ''), [
                                        'class' => 'form-control',
                                        'id' => 'bill_to',
                                        'rows' => 3,
                                        isset($mode) && $mode == 'show' ? 'readonly' : '' => isset($mode) && $mode == 'show' ? 'readonly' : '',
                                    ]) }}
                                    <a href="#" class="link-button">{{ __('Edit Customer') }}</a>
                                </div>
                                <div class="detail-group">
                                    <div class="field-group">
                                        <label class="form-label">{{ __('Ship to') }}</label>
                                        {{ Form::textarea('ship_to', isset($shipTo) ? $shipTo : (isset($invoice) ? $invoice->ship_to : ''), [
                                            'class' => 'form-control',
                                            'id' => 'ship_to',
                                            'rows' => 3,
                                            isset($mode) && $mode == 'show' ? 'readonly' : '' => isset($mode) && $mode == 'show' ? 'readonly' : '',
                                        ]) }}
                                    </div>
                                </div>
                            </div>
                            <div class="detail-group">
                                <div class="field-group">
                                    <label class="form-label">{{ __('Invoice no.') }}</label>
                                    {{ Form::text(
                                        'invoice_number',
                                        isset($invoice) ? \Auth::user()->invoiceNumberFormat($invoice->invoice_id) : $invoice_number,
                                        [
                                            'class' => 'form-control',
                                            'required' => 'required',
                                            'readonly' => 'readonly',
                                        ],
                                    ) }}
                                </div>

                                <div class="field-group">
                                    <label class="form-label">{{ __('Terms') }}</label>
                                    {{-- <span class="terms-label">{{ __('Terms') }}</span> --}}
                                    <div class="terms-field">
                                        {{ Form::select(
                                            'terms',
                                            [
                                                'net_10' => 'Net 10',
                                                'net_15' => 'Net 15',
                                                'net_30' => 'Net 30',
                                                'net_60' => 'Net 60',
                                                'Due on receipt' => 'Due on receipt',
                                            ],
                                            isset($invoice) ? $invoice->terms : 'Net 30',
                                            [
                                                'class' => 'form-select',
                                                isset($mode) && $mode == 'show' ? 'disabled' : '' => isset($mode) && $mode == 'show' ? 'disabled' : '',
                                            ],
                                        ) }}
                                    </div>
                                </div>

                                <div class="field-group">
                                    <label class="form-label">{{ __('Invoice date') }}</label>
                                    {{ Form::date('issue_date', isset($invoice) ? $invoice->issue_date : date('Y-m-d'), [
                                        'class' => 'form-control',
                                        'required' => 'required',
                                        isset($mode) && $mode == 'show' ? 'readonly' : '' => isset($mode) && $mode == 'show' ? 'readonly' : '',
                                    ]) }}
                                </div>

                                <div class="field-group">
                                    <label class="form-label">{{ __('Due date') }}</label>
                                    {{ Form::date('due_date', isset($invoice) ? $invoice->due_date : date('Y-m-d', strtotime('+30 days')), [
                                        'class' => 'form-control',
                                        'required' => 'required',
                                        isset($mode) && $mode == 'show' ? 'readonly' : '' => isset($mode) && $mode == 'show' ? 'readonly' : '',
                                    ]) }}
                                </div>

                                {{-- <div class="field-group">
                                <label class="form-label">{{ __('Category') }}</label>
                                {{ Form::select('category_id', $category, null, ['class' => 'form-select', 'id' => 'category_id', 'data-create-url' => route('product-category.create'), 'data-create-title' => __('Create New Category')]) }}
                            </div>

                            <div class="field-group">
                                <label class="form-label">{{ __('Ref Number') }}</label>
                                {{ Form::text('ref_number', '', ['class' => 'form-control']) }}
                            </div>

                            {{-- Recurring Toggle
                            <div class="field-group">
                                <label class="form-label">{{ __('Recurring') }}</label>
                                {{ Form::select('recurring', ['no' => 'No', 'yes' => 'Yes'], null, ['class' => 'form-select', 'id' => 'recurring']) }}
                            </div> --}}
                            </div>
                        </div>
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
                                <small class="text-danger d-none" id="start-required">{{ __('Required') }}</small>
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
                    <div class="product-section">
                        <h2 class="section-heading">{{ __('Product or service') }}</h2>

                        <table class="product-table" id="sortable-table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th></th>
                                    <th>#</th>
                                    <th>{{ __('Product/service') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('Qty') }}</th>
                                    <th>{{ __('Rate') }}</th>
                                    <th>{{ __('Discount') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Tax') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody data-repeater-list="items">
                                <tr class="product-row">
                                    <td>
                                        <div style="opacity: 0;">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="#c4c4c4">
                                                <circle cx="8" cy="6" r="2" />
                                                <circle cx="16" cy="6" r="2" />
                                                <circle cx="8" cy="12" r="2" />
                                                <circle cx="16" cy="12" r="2" />
                                                <circle cx="8" cy="18" r="2" />
                                                <circle cx="16" cy="18" r="2" />
                                            </svg>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="drag-handle sort-handler">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                                <circle cx="8" cy="6" r="2" />
                                                <circle cx="16" cy="6" r="2" />
                                                <circle cx="8" cy="12" r="2" />
                                                <circle cx="16" cy="12" r="2" />
                                                <circle cx="8" cy="18" r="2" />
                                                <circle cx="16" cy="18" r="2" />
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
                                        {{ Form::text('discount', '', [
                                            'class' => 'form-control input-right discount',
                                            'placeholder' => '0.00',
                                        ]) }}
                                    </td>
                                    <td>
                                        <span class="amount">0.00</span>
                                    </td>
                                    <td>
                                        <input type="checkbox" class="taxable-checkbox" name="taxable[]" value="1"
                                            style="margin-bottom: 5px;">
                                        <div class="d-none taxes small"></div>
                                        {{ Form::hidden('tax', '', ['class' => 'form-control tax']) }}
                                        {{ Form::hidden('itemTaxPrice', '', ['class' => 'form-control itemTaxPrice']) }}
                                        {{ Form::hidden('itemTaxRate', '', ['class' => 'form-control itemTaxRate']) }}
                                    </td>
                                    <td>
                                        <span class="delete-icon" title="Delete line" data-repeater-delete>
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                                <path
                                                    d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z" />
                                            </svg>
                                        </span>
                                        <input type="text" name="items[0][id]" class="product-id" value="" style="width: 50px; background: #f0f0f0;">
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="table-actions">
                            <button type="button" class="btn-action split-button" id="add-row">
                                {{ __('Add product or service') }}
                            </button>
                            <button type="button" class="btn-action" id="clear-lines">
                                {{ __('Clear all lines') }}
                            </button>
                        </div>

                        {{-- Bottom Section --}}
                        <div class="bottom-section">
                            <div class="left-section">
                                <div class="info-field">
                                    <label>{{ __('Customer payment options') }}</label>
                                    <div class="info-text">
                                        {{ __('Tell your customer how you want to get paid. To keep instructions same for all future invoices, you can specify your payment preferences by clicking on "Edit default".') }}
                                    </div>
                                </div>

                                <div class="info-field">
                                    <label>{{ __('Note to customer') }}</label>
                                    {{ Form::textarea('note', isset($invoice) ? $invoice->note : '', [
                                        'class' => 'form-control',
                                        'rows' => 3,
                                        'placeholder' => 'Thank you for your business.',
                                        isset($mode) && $mode == 'show' ? 'readonly' : '' => isset($mode) && $mode == 'show' ? 'readonly' : '',
                                    ]) }}
                                </div>

                                <div class="info-field">
                                    <label>{{ __('Memo on statement (hidden)') }}</label>
                                    {{ Form::textarea('memo', isset($invoice) ? $invoice->memo : '', [
                                        'class' => 'form-control',
                                        'rows' => 3,
                                        'placeholder' => 'This memo will not show up on your invoice, but will appear on the statement.',
                                        isset($mode) && $mode == 'show' ? 'readonly' : '' => isset($mode) && $mode == 'show' ? 'readonly' : '',
                                    ]) }}
                                </div>

                                <div class="info-field">
                                    <label>{{ __('Attachments') }}</label>
                                    <div class="attachment-zone" id="attachment-upload-area">
                                        <input type="file" id="attachment-upload" name="attachments[]" multiple
                                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif" style="display: none;">
                                        <a href="#" class="attachment-link"
                                            id="attachment-trigger">{{ __('Add attachment') }}</a>
                                        <div class="attachment-limit">{{ __('Max file size: 20 MB') }}</div>
                                        <div id="attachment-list" style="margin-top: 10px;"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="totals-section">
                                <div class="total-row subtotal">
                                    <span>{{ __('Subtotal') }}</span>
                                    <span class="subTotal">{{ \Auth::user()->priceFormat(0) }}</span>
                                </div>
                                <div class="total-row">
                                    <span>{{ __('Taxable Subtotal') }}</span>
                                    <span class="taxableSubtotal">{{ \Auth::user()->priceFormat(0) }}</span>
                                </div>
                                <div class="total-row">
                                    <span>{{ __('Discount') }}</span>
                                    <span class="totalDiscount">{{ \Auth::user()->priceFormat(0) }}</span>
                                </div>

                                <div class="total-row">
                                    <span>{{ __('Tax') }}</span>
                                    <span class="totalTax">{{ \Auth::user()->priceFormat(0) }}</span>
                                </div>
                                <div class="info-field">
                                    <label>{{ __('Sales Tax') }}</label>
                                    <select class="form-select" id="sales_tax_id" name="sales_tax_id"
                                        {{ isset($mode) && $mode == 'show' ? 'disabled' : '' }}>
                                        <option value="">Select Tax</option>
                                        @foreach ($taxes ?? [] as $tax)
                                            <option value="{{ $tax->id }}" data-rate="{{ $tax->rate }}"
                                                {{ isset($invoice) && $invoice->sales_tax_id == $tax->id ? 'selected' : '' }}>
                                                {{ $tax->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="total-row">
                                    <span>{{ __('Sales Tax') }}</span>
                                    <span class="sales_tax_amount"
                                        id="sales_tax_amount">{{ \Auth::user()->priceFormat(0) }}</span>
                                </div>
                                <div class="total-row final">
                                    <span>{{ __('Invoice total') }}</span>
                                    <span class="totalAmount">{{ \Auth::user()->priceFormat(0) }}</span>
                                </div>

                                <a href="#" class="link-button">{{ __('Edit totals') }}</a>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="invoice-footer">
                        <a href="#" class="footer-link">{{ __('Print or download') }}</a>

                        <div class="footer-actions">
                            <button type="button" class="btn btn-secondary"
                                data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-secondary" name="action"
                                value="save">{{ __('Save') }}</button>
                            <button type="submit" class="btn btn-primary" name="action"
                                value="review">{{ __('Review and send') }}</button>
                        </div>
                    </div>

                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

    <script>
        var pricePattern = '{{ Auth::user()->priceFormat() }}';

        // Format price according to user pattern
        function formatPrice(amount) {
            amount = parseFloat(amount || 0).toFixed(2);
            return pricePattern.replace("0.00", amount);
        }


        $(document).ready(function() {

            // Make invoice data available to JavaScript
            @if (isset($invoiceData))
                window.invoiceData = @json($invoiceData);
            @endif

            // Invoice modal content is now shown in the outer modal

            // Populate existing invoice data if editing or showing
            // Removed in favor of separate edit_modal.blade.php

            // Function to update line numbers
            function updateLineNumbers() {
                $('#invoice-modal tbody tr').each(function(index) {
                    $(this).find('.line-number').text(index + 1);
                });
            }

            // Function to update names
            function updateNames() {
                $('#invoice-modal tbody tr').each(function(index) {
                    $(this).find('input, select, textarea').each(function() {
                        var name = $(this).attr('name');
                        if (name) {
                            var newName = name.replace(/items\[\d+\]/, 'items[' + index + ']');
                            $(this).attr('name', newName);
                        }
                    });
                });
                updateLineNumbers();
            }

            // Initialize sortable
            if ($.ui && $.ui.sortable) {
                $('#invoice-modal tbody').sortable({
                    items: '> tr',
                    handle: ".sort-handler",
                    update: function() {
                        updateNames();
                    }
                });
            }
            updateNames();
            recalcAll(); // Calculate on load


            // Product selection AJAX
            $('#invoice-modal').on("change", ".item", function() {
                var $row = $(this).closest("tr");
                var url = $(this).data("url");
                var pid = $(this).val();

                if (!pid) {
                    $row.find(".quantity").val(1);
                    $row.find(".price, .discount, .itemTaxRate, .itemTaxPrice").val(0);
                    $row.find(".amount").text("0.00");
                    $row.find(".taxes").html('');
                    recalcRow($row);
                    recalcAll();
                    return;
                }

                $.ajax({
                    url: url,
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $("#invoice-modal #token").val()
                    },
                    data: {
                        product_id: pid
                    },
                    success: function(res) {
                        var item = JSON.parse(res);
                        $row.find(".quantity").val(1);
                        $row.find(".price").val(item.product.sale_price);
                        $row.find(".pro_description").val(item.product.description);
                        $row.find(".discount").val(0);

                        // Tax
                        var totalTaxRate = 0;
                        var taxIDs = [];
                        var html = "";

                        if (item.taxes.length) {
                            item.taxes.forEach(t => {
                                html +=
                                    `<span class="badge bg-primary mt-1 mr-2">${t.name} (${t.rate}%)</span>`;
                                totalTaxRate += parseFloat(t.rate);
                                taxIDs.push(t.id);
                            });
                        } else {
                            html = "-";
                        }

                        $row.find(".taxes").html(html);
                        $row.find(".tax").val(taxIDs);
                        $row.find(".itemTaxRate").val(totalTaxRate);

                        recalcRow($row);
                        recalcAll();
                    }
                });
            });

            // Recalculate a single row
            function recalcRow($row) {
                var qty = parseFloat($row.find(".quantity").val()) || 0;
                var rate = parseFloat($row.find(".price").val()) || 0;
                var disc = parseFloat($row.find(".discount").val()) || 0;
                var taxRt = parseFloat($row.find(".itemTaxRate").val()) || 0;

                var base = (qty * rate) - disc;
                var tax = base * (taxRt / 100);
                var total = base + tax;

                $row.find(".itemTaxPrice").val(tax.toFixed(2));
                $row.find(".amount").text(total.toFixed(2));
                $row.find(".amount_value").val(total.toFixed(2));
            }

            // Recalculate all rows
            function recalcAll() {
                var subtotal = 0,
                    discount = 0,
                    taxTotal = 0,
                    taxable = 0;

                $('#invoice-modal tbody tr').each(function() {
                    var $r = $(this);
                    var qty = parseFloat($r.find(".quantity").val()) || 0;
                    var rate = parseFloat($r.find(".price").val()) || 0;
                    var disc = parseFloat($r.find(".discount").val()) || 0;
                    var tax = parseFloat($r.find(".itemTaxPrice").val()) || 0;
                    var line = (qty * rate) - disc;

                    subtotal += line;
                    discount += disc;
                    taxTotal += tax;

                    if ($r.find(".taxable-checkbox").is(":checked")) {
                        taxable += line + tax;
                    }
                });

                $("#invoice-modal .subTotal").text(formatPrice(subtotal));
                $("#invoice-modal .totalDiscount").text(formatPrice(discount));
                $("#invoice-modal .totalTax").text(formatPrice(taxTotal));
                $("#invoice-modal .taxableSubtotal").text(formatPrice(taxable));

                calcSalesTax();
                updateGrandTotal();
            }

            // Add row
            $('#invoice-modal #add-row').on('click', function() {
                var $tbody = $('#invoice-modal tbody');
                var $lastRow = $tbody.find('tr:last');
                var $newRow = $lastRow.clone();
                $newRow.find('input').val('');
                $newRow.find('select').val('');
                $newRow.find('textarea').val('');
                $newRow.find('.amount').text('0.00');
                $newRow.find('.taxes').html('');
                $newRow.addClass('product-row');
                $tbody.append($newRow);
                updateNames();
                recalcAll();
            });

            // Delete row
            $('#invoice-modal').on('click', '[data-repeater-delete]', function() {
                if ($('#invoice-modal tbody tr').length > 1) {
                    $(this).closest('tr').remove();
                    updateNames();
                    recalcAll();
                }
            });

            // Clear lines
            $('#invoice-modal #clear-lines').on("click", function() {
                $('#invoice-modal tbody tr:gt(0)').remove();
                updateNames();
                recalcAll();
            });

            // Sales tax calculation
            function calcSalesTax() {
                var taxable = parseFloat($("#invoice-modal .taxableSubtotal").text().replace(/[^0-9.-]+/g, '')) ||
                    0;
                var rate = parseFloat($("#invoice-modal #sales_tax_id option:selected").data("rate")) || 0;
                var amount = taxable * rate / 100;
                $("#invoice-modal #sales_tax_amount").text(formatPrice(amount));
                return amount;
            }

            // Update grand total
            function updateGrandTotal() {
                var subtotal = parseFloat($("#invoice-modal .subTotal").text().replace(/[^0-9.-]+/g, '')) || 0;
                var discount = parseFloat($("#invoice-modal .totalDiscount").text().replace(/[^0-9.-]+/g, '')) || 0;
                var tax = parseFloat($("#invoice-modal .totalTax").text().replace(/[^0-9.-]+/g, '')) || 0;
                var salesTax = parseFloat($("#invoice-modal #sales_tax_amount").text().replace(/[^0-9.-]+/g, '')) ||
                    0;

                var total = subtotal - discount + tax + salesTax;
                $("#invoice-modal .totalAmount").text(formatPrice(total));
            }

            // Bind input events
            $('#invoice-modal').on("input keyup change", ".quantity, .price, .discount, .taxable-checkbox",
                function() {
                    var $row = $(this).closest("tr");
                    recalcRow($row);
                    recalcAll();
                });

            $('#invoice-modal').on("change", "#sales_tax_id", function() {
                calcSalesTax();
                updateGrandTotal();
            });
        });
        var customerId = '{{ $customerId }}';
        if (customerId > 0) {
            $('#customer').val(customerId).change();
        }
        $(document).on('change', '#customer', function() {
            // $('#customer_detail').removeClass('d-none');
            // $('#customer_detail').addClass('d-block');
            // $('#customer-box').removeClass('d-block');
            // $('#customer-box').addClass('d-none');
            var id = $(this).val();
            var url = $(this).data('url');
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
                    if (data.html != '') {

                        // $('#customer_detail').html(data.html);

                        setTimeout(function() {
                            $('#bill_to').val(data.bill_to);
                            $('#ship_to').val(data.ship_to);
                        }, 50);

                    } else {
                        $('#customer-box').removeClass('d-none');
                        $('#customer-box').addClass('d-block');
                        $('#customer_detail').removeClass('d-block');
                        $('#customer_detail').addClass('d-none');
                    }

                },

            });
        });

        $(document).on('click', '#remove', function() {
            $('#customer-box').removeClass('d-none');
            $('#customer-box').addClass('d-block');
            $('#customer_detail').removeClass('d-block');
            $('#customer_detail').addClass('d-none');
        });

        // Logo upload functionality
        $(document).on('click', '#logo-upload-area', function() {
            $('#logo-upload').click();
        });

        $(document).on('change', '#logo-upload', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#logo-image').attr('src', e.target.result);
                    $('#logo-preview').show();
                    $('#logo-placeholder').hide();
                };
                reader.readAsDataURL(file);
            }
        });

        $(document).on('click', '#remove-logo', function(e) {
            e.stopPropagation();
            $('#logo-upload').val('');
            $('#logo-preview').hide();
            $('#logo-placeholder').show();
        });

        // Attachment upload functionality
        $(document).on('click', '#attachment-trigger', function(e) {
            e.preventDefault();
            $('#attachment-upload').click();
        });

        $(document).on('change', '#attachment-upload', function(e) {
            const files = e.target.files;
            const attachmentList = $('#attachment-list');
            attachmentList.empty();

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const fileSize = (file.size / 1024 / 1024).toFixed(2); // Convert to MB

                if (file.size > 20 * 1024 * 1024) { // 20MB limit
                    alert('File "' + file.name + '" is too large. Maximum size is 20MB.');
                    continue;
                }

                const fileItem = $(`
                    <div class="attachment-item" style="display: flex; justify-content: space-between; align-items: center; padding: 5px; border: 1px solid #ddd; margin: 2px 0; border-radius: 3px;">
                        <span style="font-size: 12px;">${file.name} (${fileSize} MB)</span>
                        <button type="button" class="remove-attachment" data-index="${i}" style="background: red; color: white; border: none; border-radius: 3px; padding: 2px 6px; font-size: 10px; cursor: pointer;">Remove</button>
                    </div>
                `);
                attachmentList.append(fileItem);
            }
        });

        $(document).on('click', '.remove-attachment', function() {
            const index = $(this).data('index');
            const input = $('#attachment-upload')[0];
            const dt = new DataTransfer();

            for (let i = 0; i < input.files.length; i++) {
                if (i !== index) {
                    dt.items.add(input.files[i]);
                }
            }

            input.files = dt.files;
            $(this).closest('.attachment-item').remove();
        });

        // Form submission
        $(document).on('submit', '#invoice-form', function(e) {
            e.preventDefault();

            // Prevent double submission
            if ($(this).data('submitting')) {
                return false;
            }
            $(this).data('submitting', true);

            const formData = new FormData();

            // Add basic form fields
            const formFields = $(this).serializeArray();
            formFields.forEach(field => {
                formData.append(field.name, field.value);
            });

            // Explicitly add memo and note fields to ensure they're included
            formData.append('memo', $('#invoice-modal textarea[name="memo"]').val());
            formData.append('note', $('#invoice-modal textarea[name="note"]').val());

            // Add logo file if selected
            const logoFile = $('#logo-upload')[0].files[0];
            if (logoFile) {
                formData.append('logo', logoFile);
            }

            // Add attachment files
            const attachmentFiles = $('#attachment-upload')[0].files;
            for (let i = 0; i < attachmentFiles.length; i++) {
                formData.append('attachments[]', attachmentFiles[i]);
            }

            // Collect product lines data
            const products = [];
            $('#invoice-modal tbody tr').each(function(index) {
                const $row = $(this);
                console.log($row);
                
                const productData = {
                    id: $row.find('.product-id').val() || '', // Include product ID for updates
                    item: $row.find('.item').val(),
                    description: $row.find('.pro_description').val(),
                    quantity: $row.find('.quantity').val(),
                    price: $row.find('.price').val(),
                    discount: $row.find('.discount').val(),
                    tax: $row.find('.tax').val(),
                    taxable: $row.find('.taxable-checkbox').is(':checked') ? 1 : 0,
                    itemTaxPrice: $row.find('.itemTaxPrice').val(),
                    itemTaxRate: $row.find('.itemTaxRate').val(),
                    amount: $row.find('.amount').text()
                };
                products.push(productData);
            });

            // Add products array to form data
            formData.append('items', JSON.stringify(products));

            // Add calculated totals
            formData.append('subtotal', $('#invoice-modal .subTotal').text().replace(/[^0-9.-]+/g, ''));
            formData.append('taxable_subtotal', $('#invoice-modal .taxableSubtotal').text().replace(/[^0-9.-]+/g,
                ''));
            formData.append('total_discount', $('#invoice-modal .totalDiscount').text().replace(/[^0-9.-]+/g, ''));
            formData.append('total_tax', $('#invoice-modal .totalTax').text().replace(/[^0-9.-]+/g, ''));
            formData.append('sales_tax_amount', $('#invoice-modal #sales_tax_amount').text().replace(/[^0-9.-]+/g,
                ''));
            formData.append('total_amount', $('#invoice-modal .totalAmount').text().replace(/[^0-9.-]+/g, ''));

            // Determine URL based on mode
            let submitUrl = '{{ route('invoice.store') }}';
            let submitMethod = 'POST';

            // Check if we're in edit mode by looking for invoice data
            if (typeof window.invoiceData !== 'undefined' && window.invoiceData.id) {
                // For edit mode, generate the update URL
                submitUrl = '{{ url('invoice') }}/' + window.invoiceData.encrypted_id;
                submitMethod = 'POST';
                formData.append('_method', 'PUT');
            }

            $.ajax({
                url: submitUrl,
                type: submitMethod,
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    // Reset submitting flag
                    $('#invoice-form').data('submitting', false);

                    // Close modal and redirect to invoice index
                    const modal = bootstrap.Modal.getInstance(document.getElementById('invoice-modal'));
                    if (modal) {
                        modal.hide();
                    }
                    window.location.href = '{{ route('invoice.index') }}';
                },
                error: function(xhr) {
                    // Reset submitting flag
                    $('#invoice-form').data('submitting', false);

                    if (xhr.status === 422) {
                        // Validation errors
                        const errors = xhr.responseJSON.errors;
                        let errorMessage = 'Please fix the following errors:\n';
                        for (const field in errors) {
                            errorMessage += '- ' + errors[field][0] + '\n';
                        }
                        alert(errorMessage);
                    } else {
                        const action =
                            '{{ isset($mode) && $mode == 'edit' ? 'updating' : 'creating' }}';
                        alert('An error occurred while ' + action + ' the invoice. Please try again.');
                    }
                }
            });

            // Function to get row template
            function getRowTemplate(index, data = {}) {
                // Default values
                const id = data.id || '';
                const item = data.item || '';
                const description = data.description || '';
                const quantity = data.quantity || '';
                const price = data.price || '';
                const discount = data.discount || '';
                const amount = data.amount || '0.00';
                const taxable = data.taxable == 1 ? 'checked' : '';
                const tax = data.tax || '';
                const itemTaxPrice = data.itemTaxPrice || '';
                const itemTaxRate = data.itemTaxRate || '';

                return `
                    <tr class="product-row" data-product-id="${id}">
                        <td>
                            <div style="opacity: 0;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="#c4c4c4">
                                    <circle cx="8" cy="6" r="2" />
                                    <circle cx="16" cy="6" r="2" />
                                    <circle cx="8" cy="12" r="2" />
                                    <circle cx="16" cy="12" r="2" />
                                    <circle cx="8" cy="18" r="2" />
                                    <circle cx="16" cy="18" r="2" />
                                </svg>
                            </div>
                        </td>
                        <td>
                            <div class="drag-handle sort-handler">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <circle cx="8" cy="6" r="2" />
                                    <circle cx="16" cy="6" r="2" />
                                    <circle cx="8" cy="12" r="2" />
                                    <circle cx="16" cy="12" r="2" />
                                    <circle cx="8" cy="18" r="2" />
                                    <circle cx="16" cy="18" r="2" />
                                </svg>
                            </div>
                        </td>
                        <td><span class="line-number">${index + 1}</span></td>
                        <td><select class="form-select item" name="items[${index}][item]" data-url="{{ route('invoice.product') }}" required></select></td>
                        <td><textarea class="form-control pro_description" name="items[${index}][description]" rows="1">${description}</textarea></td>
                        <td><input type="text" class="form-control input-right quantity" name="items[${index}][quantity]" value="${quantity}" required></td>
                        <td><input type="text" class="form-control input-right price" name="items[${index}][price]" value="${price}" required></td>
                        <td><input type="text" class="form-control input-right discount" name="items[${index}][discount]" value="${discount}"></td>
                        <td><span class="amount">${amount}</span></td>
                        <td>
                            <input type="checkbox" class="taxable-checkbox" name="items[${index}][taxable]" value="1" ${taxable} style="margin-bottom: 5px;">
                            <div class="d-none taxes small"></div>
                            <input type="hidden" class="form-control tax" name="items[${index}][tax]" value="${tax}">
                            <input type="hidden" class="form-control itemTaxPrice" name="items[${index}][itemTaxPrice]" value="${itemTaxPrice}">
                            <input type="hidden" class="form-control itemTaxRate" name="items[${index}][itemTaxRate]" value="${itemTaxRate}">
                        </td>
                        <td>
                            <span class="delete-icon" title="Delete line" data-repeater-delete>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z" />
                                </svg>
                            </span>
                            <input type="text" name="items[${index}][id]" class="product-id" value="${id}" style="width: 50px; background: #f0f0f0;">
                        </td>
                    </tr>
                `;
            }
            // Update Add Row to use template
            $('#invoice-modal #add-row').off('click').on('click', function() {
                var $tbody = $('#invoice-modal tbody');
                var index = $tbody.find('tr').length;
                $tbody.append(getRowTemplate(index));
                updateNames();
                recalcAll();
            });
        });
    </script>
