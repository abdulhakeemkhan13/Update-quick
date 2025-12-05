
<style>
    .qbo-bill-container {
        background: #f8f9fa;
        min-height: 100vh;
        padding: 20px;
    }

    .qbo-bill-header {
        background: white;
        padding: 20px;
        border-bottom: 1px solid #e0e0e0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .qbo-bill-title {
        font-size: 24px;
        font-weight: 600;
        color: #333;
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
        margin-top: 30px;
    }

    .qbo-section-header {
        display: flex;
        align-items: center;
        cursor: pointer;
        margin-bottom: 15px;
    }

    .qbo-section-title {
        font-weight: 600;
        color: #393a3d;
        font-size: 16px;
        margin-left: 10px;
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
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
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
        font-size: 14px;
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
        font-size: 14px;
        margin-left: 20px;
        cursor: pointer;
    }

    .qbo-clear-btn:hover {
        text-decoration: underline;
    }

    .row-actions i {
        color: #9ca3af;
        cursor: pointer;
        font-size: 16px;
    }

    .row-actions i:hover {
        color: #ef4444;
    }

    .qbo-checkbox {
        width: 18px;
        height: 18px;
        cursor: pointer;
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
    }

    .qbo-total-row.grand-total span {
        font-size: 16px;
        font-weight: 700;
    }

    .qbo-footer {
        margin-top: 30px;
    }

    .qbo-action-buttons {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #e0e0e0;
    }

    .btn-qbo-cancel,
    .btn-qbo-clear,
    .btn-qbo-save {
        padding: 10px 24px;
        font-size: 14px;
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

    .btn-qbo-clear {
        background: white;
        color: #6b7280;
    }

    .btn-qbo-save {
        background: #2ca01c;
        color: white;
        border-color: #2ca01c;
    }

    .btn-qbo-save:hover {
        background: #24881 7;
    }
    /* QBO Style Container */
    .qbo-bill-container {
        background: #f4f5f8; /* Light gray background like QBO */
        padding: 20px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    /* Header Section */
    .qbo-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .qbo-bill-title {
        font-size: 24px;
        font-weight: 700;
        color: #393a3d;
        display: flex;
        align-items: center;
    }
    
    .qbo-balance-section {
        text-align: right;
    }
    
    .qbo-balance-label {
        font-size: 11px;
        text-transform: uppercase;
        color: #6b6c72;
        font-weight: 600;
        margin-bottom: 4px;
    }
    
    .qbo-balance-amount {
        font-size: 28px;
        font-weight: 700;
        color: #393a3d;
    }

    /* Form Elements */
    .qbo-label {
        font-size: 13px;
        color: #6b6c72;
        margin-bottom: 5px;
        font-weight: 400;
    }
    
    .qbo-input {
        border: 1px solid #babec5;
        border-radius: 2px;
        padding: 8px;
        font-size: 14px;
        height: 36px;
        width: 100%;
        background-color: #fff;
    }
    
    .qbo-input:focus {
        border-color: #2ca01c; /* QuickBooks Green focus */
        outline: none;
        box-shadow: 0 0 0 1px #2ca01c;
    }

    .qbo-textarea {
        height: 80px;
        resize: none;
    }

    /* Grid Layout for Inputs */
    .qbo-input-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        align-items: flex-start;
    }

    /* Vendor Dropdown specific width */
    .vendor-wrapper {
        width: 25%;
        min-width: 250px;
        margin-bottom: 20px;
    }

    /* The row containing Address, Terms, Dates */
    .details-row {
        display: flex;
        gap: 15px;
        width: 100%;
    }

    .col-address { flex: 0 0 25%; } /* Matches vendor width */
    .col-term { flex: 1; }
    .col-date { flex: 1; }
    .col-number { flex: 1; }

    /* Mobile Responsiveness */
    @media (max-width: 768px) {
        .details-row { flex-direction: column; }
        .vendor-wrapper, .col-address { width: 100%; }
    }
</style>

<div class="qbo-bill-container">
    {{ Form::open(['route' => 'bill.store', 'method' => 'POST', 'enctype' => 'multipart/form-data', 'id' => 'bill-form']) }}
<div class="qbo-bill-container">
    
    {{-- Header Section --}}
    <div class="qbo-header">
        <div class="qbo-bill-title">
            <i class="ti ti-history me-2"></i> {{ __('Bill') }}
        </div>
        <div class="qbo-balance-section">
            <div class="qbo-balance-label">{{ __('BALANCE DUE') }}</div>
            <div class="qbo-balance-amount" id="grand-total-display">$0.00</div>
        </div>
    </div>

    {{-- Form Section --}}
    <div class="qbo-form-section">

        {{-- Row 1: Vendor Selection --}}
        <div class="vendor-wrapper">
            <label class="qbo-label required">{{ __('Vendor') }}</label>
            <select name="vender_id" class="form-control qbo-input" data-url="{{ route('bill.vender') }}" id="vender_selector" required>
                <option value="">{{ __('Choose a vendor') }}</option>
                @foreach ($venders as $id => $vendor)
                    <option value="{{ $id }}">{{ $vendor }}</option>
                @endforeach
            </select>
        </div>


        {{-- Row 2: Horizontal layout for Address, Terms, Dates, Bill No --}}
        <div class="details-row">
            
            {{-- Column 1: Mailing Address (Matches width of Vendor above) --}}
            <div class="col-address">
                <label class="qbo-label">{{ __('Mailing address') }}</label>
                {{-- Using a textarea to mimic the box look in the screenshot --}}
                <textarea class="form-control qbo-input qbo-textarea" name="mailing_address" id="vendor_address" ></textarea>
            </div>


            {{-- Column 2: Terms --}}
            <div class="col-term">
                <label class="qbo-label">{{ __('Terms') }}</label>
                <select name="terms" class="form-control qbo-input">
                    <option value="">Due on receipt</option>
                    <option value="Net 15">Net 15</option>
                    <option value="Net 30" selected>Net 30</option>
                    <option value="Net 60">Net 60</option>
                </select>
            </div>


            {{-- Column 3: Bill Date --}}
            <div class="col-date">
                <label class="qbo-label required">{{ __('Bill date') }}</label>
                <input type="date" name="bill_date" class="form-control qbo-input" required value="{{ date('Y-m-d') }}">
            </div>

            {{-- Column 4: Due Date --}}
            <div class="col-date">
                <label class="qbo-label required">{{ __('Due date') }}</label>
                <input type="date" name="due_date" class="form-control qbo-input" required>
            </div>

            {{-- Column 5: Bill No --}}
            <div class="col-number">
                <label class="qbo-label">{{ __('Bill no.') }}</label>
                <input type="text" name="bill_number" class="form-control qbo-input" value="{{ $bill_number }}">
            </div>
            <div class="col-number">
                
            </div>
        </div>

    </div>
{{-- </div> --}}


        {{-- Category Details Section --}}
        <div class="qbo-grid-section">
            <div class="qbo-section-header">
                <i class="fas fa-chevron-down"></i>
                <span class="qbo-section-title">{{ __('Category details') }}</span>
            </div>
            <div class="qbo-grid-content">
                <table class="qbo-grid-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th style="width: 20%;">{{ __('Account') }}</th>
                            <th style="width: 30%;">{{ __('Description') }}</th>
                            <th style="width: 12%;">{{ __('Amount') }}</th>
                            <th style="width: 8%;" class="text-center">{{ __('Billable') }}</th>
                            <th style="width: 8%;">{{ __('Tax') }}</th>
                            <th style="width: 15%;">{{ __('Customer') }}</th>
                            <th style="width: 40px;"></th>
                        </tr>
                    </thead>
                    <tbody id="category-tbody">
                        <tr class="category-row">
                            <td class="qbo-line-number">1</td>
                            <td>
                                <select name="category[0][account_id]" class="form-control category-account">
                                    <option value="">{{ __('Select account') }}</option>
                                    @foreach ($chartAccounts as $id => $account)
                                        <option value="{{ $id }}">{{ $account }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <textarea name="category[0][description]" class="form-control" rows="1"></textarea>
                            </td>
                            <td><input type="number" name="category[0][amount]" class="form-control category-amount"
                                    step="0.01" value="0.00"></td>
                            <td class="text-center"><input type="checkbox" name="category[0][billable]"
                                    class="qbo-checkbox" value="1"></td>
                            <td>
                                <input type="checkbox" name="category[0][tax]" class="qbo-checkbox category-tax">
                            </td>
                            <td>
                                <select name="category[0][customer_id]" class="form-control customer-select">
                                    <option value="">-</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="row-actions">
                                <i class="fas fa-trash delete-row"></i>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <button type="button" class="qbo-add-line-btn" id="add-category-line">{{ __('Add line') }}</button>
                <button type="button" class="qbo-clear-btn"
                    id="clear-category-lines">{{ __('Clear all lines') }}</button>
            </div>
        </div>


        {{-- Item Details Section --}}
        <div class="qbo-grid-section">
            <div class="qbo-section-header">
                <i class="fas fa-chevron-down"></i>
                <span class="qbo-section-title">{{ __('Item details') }}</span>
            </div>
            <div class="qbo-grid-content">
                <table class="qbo-grid-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th style="width: 18%;">{{ __('Product/Service') }}</th>
                            <th style="width: 25%;">{{ __('Description') }}</th>
                            <th style="width: 10%;">{{ __('Qty') }}</th>
                            <th style="width: 12%;">{{ __('Rate') }}</th>
                            <th style="width: 12%;">{{ __('Amount') }}</th>
                            <th style="width: 7%;" class="text-center">{{ __('Billable') }}</th>
                            <th style="width: 7%;">{{ __('Tax') }}</th>
                            <th style="width: 12%;">{{ __('Customer') }}</th>
                            <th style="width: 40px;"></th>
                        </tr>
                    </thead>
                    <tbody id="item-tbody">
                        <tr class="item-row">
                            <td class="qbo-line-number">1</td>
                            <td>
                                <select name="items[0][product_id]" class="form-control item-product">
                                    <option value="">{{ __('Select product/service') }}</option>
                                    @foreach ($product_services as $id => $product)
                                        <option value="{{ $id }}">{{ $product }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <textarea name="items[0][description]" class="form-control item-description" rows="1"></textarea>
                            </td>
                            <td><input type="number" name="items[0][quantity]" class="form-control item-qty"
                                    step="1" value="1"></td>
                            <td><input type="number" name="items[0][price]" class="form-control item-rate"
                                    step="0.01" value="0.00"></td>
                            <td><input type="number" name="items[0][amount]" class="form-control item-amount"
                                    step="0.01" value="0.00" readonly></td>
                            <td class="text-center"><input type="checkbox" name="items[0][billable]"
                                    class="qbo-checkbox"></td>
                            <td>
                                <input type="checkbox" name="items[0][tax]" class="qbo-checkbox item-tax">
                            </td>
                            <td>
                                <select name="items[0][customer_id]" class="form-control customer-select">
                                    <option value="">-</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="row-actions">
                                <i class="fas fa-trash delete-row"></i>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <button type="button" class="qbo-add-line-btn" id="add-item-line">{{ __('Add line') }}</button>
                <button type="button" class="qbo-clear-btn"
                    id="clear-item-lines">{{ __('Clear all lines') }}</button>
            </div>
        </div>

    </div>

    {{-- Footer Section --}}
    <div class="qbo-footer">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">{{ __('Memo') }}</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="{{ __('Optional memo or notes') }}"></textarea>
                </div>

                <div class="form-group mt-3">
                    <label class="form-label">{{ __('Attachments') }}</label>
                    <div class="border rounded p-3 text-center" style="border-style: dashed !important;">
                        <input type="file" name="attachments[]" id="attachments" multiple class="d-none">
                        <label for="attachments" style="cursor: pointer; margin: 0;">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 24px; color: #0077c5;"></i>
                            <p class="mb-0 mt-2">{{ __('Add attachments') }}</p>
                            <small class="text-muted">{{ __('Max file size: 20 MB') }}</small>
                        </label>
                    </div>
                    <div id="attachment-list" class="mt-2"></div>
                </div>
            </div>


            <div class="col-md-6">
                <div class="qbo-total-section">
                    <div class="qbo-total-row">
                        <span>{{ __('Subtotal') }}</span>
                        <span id="subtotal-display">$0.00</span>
                    </div>
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
            <a href="{{ route('bill.index') }}" class="btn-qbo-cancel">{{ __('Cancel') }}</a>
            <button type="button" class="btn-qbo-clear" id="clear-form">{{ __('Clear') }}</button>
            <button type="submit" class="btn-qbo-save">{{ __('Save') }}</button>
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
                    <select name="category[${categoryLineCount}][account_id]" class="form-control category-account">
                        <option value="">{{ __('Select account') }}</option>
                        @foreach ($chartAccounts as $id => $account)
                            <option value="{{ $id }}">{{ $account }}</option>
                        @endforeach
                    </select>
                </td>
                <td><textarea name="category[${categoryLineCount}][description]" class="form-control" rows="1"></textarea></td>
                <td><input type="number" name="category[${categoryLineCount}][amount]" class="form-control category-amount" step="0.01" value="0.00"></td>
                <td class="text-center"><input type="checkbox" name="category[${categoryLineCount}][billable]" class="qbo-checkbox" value="1"></td>
                <td><input type="checkbox" name="category[${categoryLineCount}][tax]" class="qbo-checkbox category-tax"></td>
                <td>
                    <select name="category[${categoryLineCount}][customer_id]" class="form-control customer-select">
                        <option value="">-</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td class="row-actions"><i class="fas fa-trash delete-row"></i></td>
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
                    <select name="items[${itemLineCount}][product_id]" class="form-control item-product">
                        <option value="">{{ __('Select product/service') }}</option>
                        @foreach ($product_services as $id => $product)
                            <option value="{{ $id }}">{{ $product }}</option>
                        @endforeach
                    </select>
                </td>
                <td><textarea name="items[${itemLineCount}][description]" class="form-control item-description" rows="1"></textarea></td>
                <td><input type="number" name="items[${itemLineCount}][quantity]" class="form-control item-qty" step="1" value="1"></td>
                <td><input type="number" name="items[${itemLineCount}][price]" class="form-control item-rate" step="0.01" value="0.00"></td>
                <td><input type="number" name="items[${itemLineCount}][amount]" class="form-control item-amount" step="0.01" value="0.00" readonly></td>
                <td class="text-center"><input type="checkbox" name="items[${itemLineCount}][billable]" class="qbo-checkbox"></td>
                <td><input type="checkbox" name="items[${itemLineCount}][tax]" class="qbo-checkbox item-tax"></td>
                <td>
                    <select name="items[${itemLineCount}][customer_id]" class="form-control customer-select">
                        <option value="">-</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td class="row-actions"><i class="fas fa-trash delete-row"></i></td>
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

        // Calculate Item Amount (Qty × Rate)
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
            let subtotal = 0;
            $('.category-amount').each(function() {
                subtotal += parseFloat($(this).val()) || 0;
            });
            $('.item-amount').each(function() {
                subtotal += parseFloat($(this).val()) || 0;
            });


            $('#subtotal').val(subtotal.toFixed(2));
            $('#total').val(subtotal.toFixed(2));
            $('#subtotal-display').text('$' + subtotal.toFixed(2));
            $('#total-display').text('$' + subtotal.toFixed(2));
            $('#grand-total-display').text('$' + subtotal.toFixed(2));
        }


        // File Upload Display
        $('#attachments').on('change', function() {
            const files = this.files;
            $('#attachment-list').empty();
            for (let i = 0; i < files.length; i++) {
                $('#attachment-list').append(
                    `<div class="small text-muted"><i class="fas fa-paperclip"></i> ${files[i].name}</div>`
                );
            }
        });

        // Product Auto-fill - loads product details when selected
        $(document).on('change', '.item-product', function() {
            const productId = $(this).val();
            const currentRow = $(this).closest('tr');

            if (!productId) return;

            $.ajax({
                url: '{{ route('invoice.product') }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    product_id: productId
                },
                success: function(response) {
                    // Parse JSON if it's a string
                    const data = typeof response === 'string' ? JSON.parse(response) :
                        response;

                    if (data.product) {
                        // Get description (or name if description doesn't exist)
                        const description = data.product.description || data.product.name ||
                            '';
                        currentRow.find('.item-description').val(description);

                        // Get purchase price
                        const rate = parseFloat(data.product.purchase_price) || 0;
                        currentRow.find('.item-rate').val(rate.toFixed(2));

                        // Set quantity to 1
                        const qty = 1;
                        currentRow.find('.item-qty').val(qty);

                        // Calculate amount (qty × rate)
                        const amount = qty * rate;
                        currentRow.find('.item-amount').val(amount.toFixed(2));

                        // Recalculate subtotal and grand total
                        calculateTotal();
                    }
                },
                error: function(xhr) {
                    console.error('Error fetching product:', xhr);
                    show_toastr('Error', 'Failed to load product details. Please try again.', 'error');
                }

            });
        });


        // Form Submit
        $('#bill-form').on('submit', function(e) {
            e.preventDefault();
            $('.btn-qbo-save').prop('disabled', true).text('{{ __('Saving...') }}');

            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: new FormData(this),
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.status === 'success') {
                        $('#commonModalOver').modal('hide');
                        if (typeof show_toastr === 'function') {
                            show_toastr('{{ __('Success') }}',
                                '{{ __('Bill created successfully') }}', 'success');
                        }
                        setTimeout(() => window.location.reload(), 500);
                    } else {
                      show_toastr('success', response.message || '{{ __("Bill created successfully") }}', 'success');
                        $('.btn-qbo-save').prop('disabled', false).text(
                            '{{ __('Save') }}');
                    }

                },
                error: function(xhr) {
                    let message = '{{ __('Error creating bill') }}';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    show_toastr('error', message, 'error');


                    $('.btn-qbo-save').prop('disabled', false).text('{{ __('Save') }}');
                }

            });
        });

        // Initialize
        calculateTotal();
    });
    $(document).on('change', '#vender_selector', function () {
    var id = $(this).val();
    var url = $(this).data('url');

    // Clear the box or show loading state
    $('#vendor_address').val(''); 

    if (id) {
        $.ajax({
            url: url,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': jQuery('meta[name="csrf-token"]').attr('content') // OR jQuery('#token').val()
            },
            data: {
                'id': id
            },
            cache: false,
            success: function (data) {
                // CASE 1: If your controller returns JSON (Best Practice)
                // Example: return response()->json(['address' => '123 Main St...']);
                if (typeof data === 'object' && data.address) {
                    $('#vendor_address').val(data.address);
                } 
                
                // CASE 2: If your controller returns an HTML View (Old way)
                // Since we are using a textarea, we need to strip the HTML tags (<br>, <p>)
                // so they don't show up as code in the box.
                else {
                    // This creates a temporary element to strip tags and extract text
                    var tempDiv = document.createElement("div");
                    tempDiv.innerHTML = data;
                    
                    // If the data had <br> tags, replace them with newlines first
                    var addressText = tempDiv.innerText || tempDiv.textContent;
                    
                    $('#vendor_address').val(addressText.trim());
                }
            },

    }
});
</script>

