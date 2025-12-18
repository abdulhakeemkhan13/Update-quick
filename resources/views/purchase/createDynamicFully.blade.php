<style>
    .qbo-po-container {
        background: #f8f9fa;
        min-height: 100vh;
        padding: 20px;
    }

    .qbo-po-header {
        background: white;
        padding: 20px;
        border-bottom: 1px solid #e0e0e0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .qbo-po-title {
        font-size: 24px;
        font-weight: 600;
        color: #333;
        display: flex;
        align-items: center;
    }

    .qbo-total-badge {
        font-size: 28px;
        font-weight: 700;
        color: #2ca01c;
    }

    .qbo-form-section {
        background: white;
        padding: 30px;
        margin-top: 20px;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .qbo-grid-section {
        margin-top: 20px;
    }

    .qbo-section-header {
        display: flex;
        align-items: center;
        cursor: pointer;
        margin-bottom: 15px;
    }

    .qbo-section-title {
        font-weight: 600;
        color: #0077c5;
        font-size: 14px;
        margin-left: 8px;
    }

    .qbo-grid-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }

    .qbo-grid-table thead {
        background: #f3f4f6;
        border-top: 1px solid #e0e0e0;
        border-bottom: 1px solid #e0e0e0;
    }

    .qbo-grid-table th {
        padding: 8px;
        text-align: left;
        font-size: 11px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
    }

    .qbo-grid-table td {
        padding: 8px;
        border-bottom: 1px solid #f0f0f0;
    }

    .qbo-grid-table tbody tr:hover {
        background: #f9fafb;
    }

    .qbo-line-number {
        color: #9ca3af;
        font-weight: 500;
    }

    .qbo-add-line-btn {
        background: none;
        border: none;
        color: #0077c5;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        padding: 8px 0;
    }

    .qbo-add-line-btn:hover {
        text-decoration: underline;
    }

    .qbo-clear-btn {
        background: none;
        border: none;
        color: #6b7280;
        font-size: 13px;
        margin-left: 20px;
        cursor: pointer;
    }

    .qbo-clear-btn:hover {
        text-decoration: underline;
    }

    .row-actions i {
        color: #9ca3af;
        cursor: pointer;
        font-size: 14px;
    }

    .row-actions i:hover {
        color: #ef4444;
    }

    .qbo-total-section {
        text-align: right;
        padding: 20px 0;
    }

    .qbo-total-row {
        display: flex;
        justify-content: flex-end;
        padding: 8px 0;
        font-size: 14px;
    }

    .qbo-total-row span:first-child {
        margin-right: 40px;
        color: #6b7280;
    }

    .qbo-total-row span:last-child {
        font-weight: 600;
        min-width: 100px;
    }

    .qbo-total-row.grand-total {
        border-top: 2px solid #e0e0e0;
        padding-top: 12px;
        margin-top: 8px;
        font-size: 16px;
        font-weight: 700;
    }

    .qbo-label {
        font-size: 12px;
        color: #6b6c72;
        margin-bottom: 5px;
        font-weight: 500;
    }

    .qbo-input {
        border: 1px solid #babec5;
        border-radius: 2px;
        padding: 8px;
        font-size: 13px;
        background-color: #fff;
    }

    .qbo-input:focus {
        border-color: #2ca01c;
        outline: none;
        box-shadow: 0 0 0 1px #2ca01c;
    }

    .qbo-action-buttons {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #e0e0e0;
    }

    .btn-qbo {
        padding: 10px 20px;
        font-size: 13px;
        font-weight: 500;
        border-radius: 4px;
        border: 1px solid #d1d5db;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }

    .btn-qbo-cancel {
        background: white;
        color: #6b7280;
    }

    .btn-qbo-save {
        background: #2ca01c;
        color: white;
        border-color: #2ca01c;
    }

    .btn-qbo-save:hover {
        background: #248817;
    }

    .btn-qbo-save-close {
        background: #1a7310;
        color: white;
        border-color: #1a7310;
    }
</style>

<div class="qbo-po-container">
    {{ Form::open(['route' => 'purchase.store', 'method' => 'POST', 'enctype' => 'multipart/form-data', 'id' => 'po-form']) }}

    {{-- Header Section --}}
    <div class="qbo-po-header">
        <div class="qbo-po-title">
            <i class="ti ti-file-invoice me-2"></i> {{ __('Purchase Order') }}
        </div>
        <div class="qbo-balance-section">
            <div class="qbo-balance-label" style="font-size: 11px; color: #6b6c72;">{{ __('AMOUNT') }}</div>
            <div class="qbo-total-badge" id="grand-total-display">$0.00</div>
        </div>
    </div>

    {{-- Form Section --}}
    <div class="qbo-form-section">
        <div class="row">
            {{-- Left Column --}}
            <div class="col-md-6">
                {{-- Vendor --}}
                <div class="mb-3">
                    <label class="qbo-label">{{ __('Vendor') }}</label>
                    <select name="vendor_id" class="form-control qbo-input" id="vendor_selector" required>
                        <option value="">{{ __('Choose a vendor') }}</option>
                        @foreach ($vendors as $id => $vendor)
                            <option value="{{ $id }}">{{ $vendor }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- PO Status --}}
                <div class="mb-3">
                    <label class="qbo-label">{{ __('Purchase Order status') }}</label>
                    <input type="text" class="form-control qbo-input" value="OPEN" readonly
                        style="background: #f3f4f6;">
                </div>

                {{-- Mailing Address --}}
                <div class="mb-3">
                    <label class="qbo-label">{{ __('Mailing address') }}</label>
                    <textarea class="form-control qbo-input" name="mailing_address" id="vendor_address" rows="4" readonly
                        style="background: #f9fafb;"></textarea>
                </div>
            </div>

            {{-- Right Column --}}
            <div class="col-md-6">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="qbo-label">{{ __('Email') }}</label>
                        <input type="email" name="vendor_email" class="form-control qbo-input"
                            placeholder="{{ __('Separate emails with a comma') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="qbo-label">{{ __('Ref No.') }}</label>
                        <input type="text" name="ref_number" class="form-control qbo-input">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="qbo-label">{{ __('Ship to') }}</label>
                    <select name="ship_to" class="form-control qbo-input">
                        <option value="">{{ __('Select customer for address') }}</option>
                        @foreach ($customers ?? [] as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="qbo-label">{{ __('Shipping address') }}</label>
                    <textarea class="form-control qbo-input" name="ship_to_address" rows="3"></textarea>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="qbo-label">{{ __('Purchase Order date') }}</label>
                        <input type="date" name="po_date" class="form-control qbo-input" required
                            value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="qbo-label">{{ __('Ship Via') }}</label>
                        <input type="text" name="ship_via" class="form-control qbo-input"
                            placeholder="FedEx, UPS, etc.">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="qbo-label">{{ __('Due date') }}</label>
                        <input type="date" name="expected_date" class="form-control qbo-input">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Category Details Section --}}
    <div class="qbo-form-section">
        <div class="qbo-grid-section">
            <div class="qbo-section-header">
                <i class="fas fa-chevron-down"></i>
                <span class="qbo-section-title">{{ __('Category details') }}</span>
            </div>
            <div class="qbo-grid-content">
                <table class="qbo-grid-table">
                    <thead>
                        <tr>
                            <th style="width: 30px;">#</th>
                            <th style="width: 25%;">{{ __('CATEGORY') }}</th>
                            <th style="width: 35%;">{{ __('DESCRIPTION') }}</th>
                            <th style="width: 15%;">{{ __('AMOUNT') }}</th>
                            <th style="width: 20%;">{{ __('CUSTOMER') }}</th>
                            <th style="width: 30px;"></th>
                        </tr>
                    </thead>
                    <tbody id="category-tbody">
                        <tr class="category-row">
                            <td class="qbo-line-number">1</td>
                            <td>
                                <select name="category[0][account_id]" class="form-control form-control-sm">
                                    <option value="">{{ __('Select') }}</option>
                                    @foreach ($chartAccounts as $id => $account)
                                        <option value="{{ $id }}">{{ $account }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="text" name="category[0][description]"
                                    class="form-control form-control-sm">
                            </td>
                            <td>
                                <input type="number" name="category[0][amount]"
                                    class="form-control form-control-sm category-amount" step="0.01"
                                    value="0.00">
                            </td>
                            <td>
                                <select name="category[0][customer_id]" class="form-control form-control-sm">
                                    <option value=""></option>
                                    @foreach ($customers ?? [] as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="row-actions">
                                <i class="fas fa-copy me-2 copy-row" title="Duplicate"></i>
                                <i class="fas fa-trash delete-row" title="Delete"></i>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <button type="button" class="qbo-add-line-btn"
                    id="add-category-line">{{ __('Add lines') }}</button>
                <button type="button" class="qbo-clear-btn"
                    id="clear-category-lines">{{ __('Clear all lines') }}</button>
            </div>
        </div>

        {{-- Item Details Section --}}
        <div class="qbo-grid-section mt-4">
            <div class="qbo-section-header">
                <i class="fas fa-chevron-down"></i>
                <span class="qbo-section-title">{{ __('Item details') }}</span>
            </div>
            <div class="qbo-grid-content">
                <table class="qbo-grid-table">
                    <thead>
                        <tr>
                            <th style="width: 30px;">#</th>
                            <th style="width: 20%;">{{ __('PRODUCT/SERVICE') }}</th>
                            <th style="width: 28%;">{{ __('DESCRIPTION') }}</th>
                            <th style="width: 10%;">{{ __('QTY') }}</th>
                            <th style="width: 12%;">{{ __('RATE') }}</th>
                            <th style="width: 12%;">{{ __('AMOUNT') }}</th>
                            <th style="width: 15%;">{{ __('CUSTOMER') }}</th>
                            <th style="width: 30px;"></th>
                        </tr>
                    </thead>
                    <tbody id="item-tbody">
                        <tr class="item-row">
                            <td class="qbo-line-number">1</td>
                            <td>
                                <select name="items[0][product_id]" class="form-control form-control-sm item-product">
                                    <option value="">{{ __('Select') }}</option>
                                    @foreach ($product_services as $id => $product)
                                        <option value="{{ $id }}">{{ $product }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="text" name="items[0][description]"
                                    class="form-control form-control-sm item-description">
                            </td>
                            <td>
                                <input type="number" name="items[0][quantity]"
                                    class="form-control form-control-sm item-qty" step="1" value="1">
                            </td>
                            <td>
                                <input type="number" name="items[0][price]"
                                    class="form-control form-control-sm item-rate" step="0.01" value="0.00">
                            </td>
                            <td>
                                <input type="number" name="items[0][amount]"
                                    class="form-control form-control-sm item-amount" step="0.01" value="0.00"
                                    readonly>
                            </td>
                            <td>
                                <select name="items[0][customer_id]" class="form-control form-control-sm">
                                    <option value=""></option>
                                    @foreach ($customers ?? [] as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="row-actions">
                                <i class="fas fa-copy me-2 copy-row" title="Duplicate"></i>
                                <i class="fas fa-trash delete-row" title="Delete"></i>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <button type="button" class="qbo-add-line-btn" id="add-item-line">{{ __('Add lines') }}</button>
                <button type="button" class="qbo-clear-btn"
                    id="clear-item-lines">{{ __('Clear all lines') }}</button>
            </div>
        </div>

        {{-- Bottom Section --}}
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="mb-3">
                    <label class="qbo-label">{{ __('Your message to vendor') }}</label>
                    <textarea name="vendor_message" class="form-control qbo-input" rows="3"></textarea>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <label class="qbo-label">{{ __('Memo') }}</label>
                    <textarea name="notes" class="form-control qbo-input" rows="3"></textarea>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <label class="qbo-label">{{ __('Attachments') }}</label>
                    <div class="border rounded p-3 text-center"
                        style="border-style: dashed !important; background: #f9fafb;">
                        <input type="file" name="attachments[]" id="attachments" multiple class="d-none">
                        <label for="attachments" style="cursor: pointer; margin: 0;">
                            <i class="fas fa-paperclip" style="font-size: 20px; color: #0077c5;"></i>
                            <p class="mb-0 mt-2 small text-muted">{{ __('Add attachment') }}</p>
                        </label>
                    </div>
                </div>

                {{-- Total Section --}}
                <div class="qbo-total-section mt-3">
                    <div class="qbo-total-row grand-total">
                        <span>{{ __('Total') }}</span>
                        <span id="total-display">$0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="subtotal" id="subtotal" value="0">
        <input type="hidden" name="total" id="total" value="0">

        {{-- Action Buttons --}}
        <div class="qbo-action-buttons">
            <div>
                <a href="{{ route('purchase.index') }}" class="btn-qbo btn-qbo-cancel">{{ __('Cancel') }}</a>
                <button type="button" class="btn-qbo btn-qbo-cancel" id="clear-form">{{ __('Clear') }}</button>
            </div>
            <div>
                <button type="submit" class="btn-qbo btn-qbo-save">{{ __('Save') }}</button>
                <button type="submit" class="btn-qbo btn-qbo-save-close" name="save_and_close"
                    value="1">{{ __('Save and close') }}</button>
            </div>
        </div>
    </div>

    {{ Form::close() }}
</div>

<script>
    $(document).ready(function() {
        let categoryLineCount = 1;
        let itemLineCount = 1;

        // Add Category Line
        $('#add-category-line').on('click', function() {
            const newRow = `
            <tr class="category-row">
                <td class="qbo-line-number">${++categoryLineCount}</td>
                <td>
                    <select name="category[${categoryLineCount}][account_id]" class="form-control form-control-sm">
                        <option value="">{{ __('Select') }}</option>
                        @foreach ($chartAccounts as $id => $account)
                            <option value="{{ $id }}">{{ $account }}</option>
                        @endforeach
                    </select>
                </td>
                <td><input type="text" name="category[${categoryLineCount}][description]" class="form-control form-control-sm"></td>
                <td><input type="number" name="category[${categoryLineCount}][amount]" class="form-control form-control-sm category-amount" step="0.01" value="0.00"></td>
                <td>
                    <select name="category[${categoryLineCount}][customer_id]" class="form-control form-control-sm">
                        <option value=""></option>
                        @foreach ($customers ?? [] as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td class="row-actions">
                    <i class="fas fa-copy me-2 copy-row"></i>
                    <i class="fas fa-trash delete-row"></i>
                </td>
            </tr>
        `;
            $('#category-tbody').append(newRow);
            updateLineNumbers();
        });

        // Add Item Line
        $('#add-item-line').on('click', function() {
            const newRow = `
            <tr class="item-row">
                <td class="qbo-line-number">${++itemLineCount}</td>
                <td>
                    <select name="items[${itemLineCount}][product_id]" class="form-control form-control-sm item-product">
                        <option value="">{{ __('Select') }}</option>
                        @foreach ($product_services as $id => $product)
                            <option value="{{ $id }}">{{ $product }}</option>
                        @endforeach
                    </select>
                </td>
                <td><input type="text" name="items[${itemLineCount}][description]" class="form-control form-control-sm item-description"></td>
                <td><input type="number" name="items[${itemLineCount}][quantity]" class="form-control form-control-sm item-qty" step="1" value="1"></td>
                <td><input type="number" name="items[${itemLineCount}][price]" class="form-control form-control-sm item-rate" step="0.01" value="0.00"></td>
                <td><input type="number" name="items[${itemLineCount}][amount]" class="form-control form-control-sm item-amount" step="0.01" value="0.00" readonly></td>
                <td>
                    <select name="items[${itemLineCount}][customer_id]" class="form-control form-control-sm">
                        <option value=""></option>
                        @foreach ($customers ?? [] as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td class="row-actions">
                    <i class="fas fa-copy me-2 copy-row"></i>
                    <i class="fas fa-trash delete-row"></i>
                </td>
            </tr>
        `;
            $('#item-tbody').append(newRow);
            updateLineNumbers();
        });

        // Delete Row
        $(document).on('click', '.delete-row', function() {
            $(this).closest('tr').remove();
            updateLineNumbers();
            calculateTotal();
        });

        // Copy Row
        $(document).on('click', '.copy-row', function() {
            const row = $(this).closest('tr').clone();
            const isCategory = row.hasClass('category-row');
            const tbody = isCategory ? '#category-tbody' : '#item-tbody';
            const count = isCategory ? ++categoryLineCount : ++itemLineCount;

            // Update name attributes
            row.find('[name]').each(function() {
                const name = $(this).attr('name');
                $(this).attr('name', name.replace(/\[\d+\]/, `[${count}]`));
            });

            $(tbody).append(row);
            updateLineNumbers();
        });

        // Clear Lines
        $('#clear-category-lines').on('click', function() {
            $('#category-tbody').empty();
            categoryLineCount = 0;
            calculateTotal();
        });

        $('#clear-item-lines').on('click', function() {
            $('#item-tbody').empty();
            itemLineCount = 0;
            calculateTotal();
        });

        // Update Line Numbers
        function updateLineNumbers() {
            $('.category-row').each(function(index) {
                $(this).find('.qbo-line-number').text(index + 1);
            });
            $('.item-row').each(function(index) {
                $(this).find('.qbo-line-number').text(index + 1);
            });
        }

        // Calculate Item Amount
        $(document).on('input', '.item-qty, .item-rate', function() {
            const row = $(this).closest('tr');
            const qty = parseFloat(row.find('.item-qty').val()) || 0;
            const rate = parseFloat(row.find('.item-rate').val()) || 0;
            row.find('.item-amount').val((qty * rate).toFixed(2));
            calculateTotal();
        });

        // Category Amount Change
        $(document).on('input', '.category-amount', function() {
            calculateTotal();
        });

        // Calculate Total
        function calculateTotal() {
            let total = 0;
            $('.category-amount, .item-amount').each(function() {
                total += parseFloat($(this).val()) || 0;
            });

            $('#subtotal').val(total.toFixed(2));
            $('#total').val(total.toFixed(2));
            $('#total-display').text('$' + total.toFixed(2));
            $('#grand-total-display').text('$' + total.toFixed(2));
        }

        // Vendor Selection
        $(document).on('change', '#vendor_selector', function() {
            const vendorId = $(this).val();
            if (vendorId) {
                $.ajax({
                    url: '{{ route('bill.vender') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: vendorId
                    },
                    success: function(data) {
                        if (typeof data === 'object' && data.address) {
                            $('#vendor_address').val(data.address);
                        } else {
                            const tempDiv = document.createElement("div");
                            tempDiv.innerHTML = data;
                            $('#vendor_address').val(tempDiv.innerText || tempDiv
                                .textContent);
                        }
                    }
                });
            }
        });

        // Product Auto-fill
        $(document).on('change', '.item-product', function() {
            const productId = $(this).val();
            const currentRow = $(this).closest('tr');

            if (!productId) return;

            $.ajax({
                url: '{{ route('purchase.product') }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    product_id: productId
                },
                success: function(response) {
                    const data = typeof response === 'string' ? JSON.parse(response) :
                        response;

                    if (data.product) {
                        currentRow.find('.item-description').val(data.product.description ||
                            data.product.name || '');
                        const rate = parseFloat(data.product.purchase_price) || 0;
                        currentRow.find('.item-rate').val(rate.toFixed(2));

                        const qty = parseFloat(currentRow.find('.item-qty').val()) || 1;
                        currentRow.find('.item-amount').val((qty * rate).toFixed(2));
                        calculateTotal();
                    }
                }
            });
        });

        // Form Submit
        $('#po-form').on('submit', function(e) {
            e.preventDefault();
            $('.btn-qbo-save, .btn-qbo-save-close').prop('disabled', true).text(
                '{{ __('Saving...') }}');

            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: new FormData(this),
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.status === 'success' || response.po_id) {
                        $('#commonModalOver').modal('hide');
                        show_toastr('success', response.message ||
                            '{{ __('Purchase Order created successfully') }}',
                            'success');
                        setTimeout(() => window.location.reload(), 500);
                    }
                },
                error: function(xhr) {
                    let message = '{{ __('Error creating purchase order') }}';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    show_toastr('Error', message, 'error');
                    $('.btn-qbo-save, .btn-qbo-save-close').prop('disabled', false).text(
                        '{{ __('Save') }}');
                }
            });
        });

        // Clear Form
        $('#clear-form').on('click', function() {
            $('#po-form')[0].reset();
            $('#category-tbody, #item-tbody').empty();
            categoryLineCount = 0;
            itemLineCount = 0;
            calculateTotal();
        });

        // Initialize
        calculateTotal();
    });
</script>
