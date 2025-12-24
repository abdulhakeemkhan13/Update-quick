<style>
    /* QuickBooks Style for Delayed Charge Modal */
    .dc-modal-container {
        background: #ffffff;
        max-width: 100%;
        margin: 0;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    .dc-card {
        background: #fff;
        margin: 0 auto;
        max-width: 100%;
        width: 100%;
        padding: 0px;
        flex: 1;
        overflow-y: auto;
    }

    /* Fixed Top Header */
    .fixed-top-header {
        position: sticky;
        top: 0;
        background: #fff;
        border-bottom: 1px solid #e4e4e7;
        box-shadow: 0 2px 4px rgba(0, 0, 0, .1);
        z-index: 1000;
        padding: 0;
    }

    .header-top-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 40px;
    }

    .dc-label {
        font-size: 28px;
        font-weight: 500;
        color: #000000;
        display: flex;
        align-items: center;
        gap: 8px;
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
    .dc-header {
        background: #ebf4fa;
        padding: 24px 32px;
    }

    .dc-form-label {
        display: block;
        font-size: 13px;
        color: #393a3d;
        margin-bottom: 6px;
        font-weight: 500;
    }

    .dc-form-control,
    .dc-form-select {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #c4c4c4;
        border-radius: 4px;
        font-size: 14px;
        background: #fff;
        color: #393a3d;
        transition: all 0.2s;
    }

    .dc-form-control:focus,
    .dc-form-select:focus {
        outline: none;
        border-color: #2ca01c !important;
        box-shadow: 0 0 0 3px rgba(0, 119, 197, 0.1);
    }

    /* Product Table */
    .product-section {
        padding: 24px 32px;
        background: #fff;
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

    .product-table tbody td {
        padding: 12px 8px;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
    }

    .drag-handle {
        cursor: grab;
        color: #c4c4c4;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .line-number {
        font-size: 13px;
        color: #6b6c72;
    }

    .delete-icon {
        color: #c4c4c4;
        cursor: pointer;
        transition: color 0.2s;
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

    /* Bottom Section */
    .bottom-section {
        padding: 24px 32px;
        background: #ffffff;
    }

    .info-field label {
        font-size: 13px;
        font-weight: 600;
        color: #393a3d;
        margin-bottom: 8px;
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

    /* Footer */
    .dc-footer {
        background: #ffffff;
        padding: 12px 32px;
        border-top: 1px solid #e4e4e7;
        box-shadow: 0 -2px 4px rgba(0, 0, 0, .1);
        display: grid;
        grid-template-columns: auto 1fr auto;
        align-items: center;
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

    /* Buttons */
    .dc-btn {
        padding: 10px 24px;
        border-radius: 4px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        border: 1px solid transparent;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .dc-btn-secondary {
        background: #fff;
        color: #393a3d;
        border-color: #c4c4c4;
    }

    .dc-btn-secondary:hover {
        background: #f4f5f8;
        border-color: #393a3d;
    }

    .dc-btn-primary {
        background: #2ca01c;
        color: #fff;
        border-color: #2ca01c;
    }

    .dc-btn-primary:hover {
        background: #108000;
        border-color: #108000;
    }

    /* Split Button */
    .dc-btn-group {
        position: relative;
        display: inline-flex;
    }

    .dc-btn-group .dc-btn {
        border-radius: 4px 0 0 4px;
    }

    .dc-btn-group .dc-btn+.dropdown-toggle-split {
        border-radius: 0 4px 4px 0;
        padding-left: 10px;
        padding-right: 10px;
        border-left: 1px solid rgba(255, 255, 255, 0.3);
        margin-left: -1px;
    }

    .dc-dropdown-menu {
        position: absolute;
        bottom: 100%;
        right: 0;
        z-index: 1000;
        display: none;
        min-width: 10rem;
        padding: 0.5rem 0;
        font-size: 14px;
        background-color: #fff;
        border: 1px solid rgba(0, 0, 0, 0.15);
        border-radius: 0.25rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .dc-dropdown-menu.show {
        display: block;
    }

    .dc-dropdown-item {
        display: block;
        width: 100%;
        padding: 0.5rem 1.5rem;
        font-weight: 400;
        color: #212529;
        background-color: transparent;
        border: 0;
        cursor: pointer;
    }

    .dc-dropdown-item:hover {
        background-color: #f8f9fa;
    }

    .existing-attachment {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 4px 8px;
        background: #e9ecef;
        border-radius: 4px;
        margin: 4px;
    }
</style>

<div class="dc-modal-container">
    {{ Form::model($delayedCharge, ['route' => ['delayed-charge.update', $delayedCharge->id], 'method' => 'PUT', 'id' => 'delayed-charge-form', 'files' => true]) }}

    {{-- Fixed Top Header --}}
    <div class="fixed-top-header">
        <div class="header-top-row">
            <div class="dc-label">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" color="currentColor" width="24px" height="24px">
                    <path fill="currentColor" d="M13.007 7a1 1 0 0 0-1 1L12 12a1 1 0 0 0 1 1l3.556.006a1 1 0 0 0 0-2L14 11l.005-3a1 1 0 0 0-.998-1"></path>
                    <path fill="currentColor" d="M19.374 5.647A8.94 8.94 0 0 0 13.014 3H13a8.98 8.98 0 0 0-8.98 8.593l-.312-.312a1 1 0 0 0-1.416 1.412l2 2a1 1 0 0 0 1.414 0l2-2a1 1 0 0 0-1.412-1.416l-.272.272A6.984 6.984 0 0 1 13 5h.012A7 7 0 0 1 13 19h-.012a7 7 0 0 1-4.643-1.775 1 1 0 1 0-1.33 1.494A9 9 0 0 0 12.986 21H13a9 9 0 0 0 6.374-15.353"></path>
                </svg>
                {{ __('Delayed Charge') }} #{{ $delayedCharge->charge_id }}
            </div>

            <div class="header-right-controls">
                <button type="button" class="close-button" onclick="$('#delayedChargeModal').modal('hide');" aria-label="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" color="currentColor" width="24px" height="24px">
                        <path fill="currentColor" d="m13.432 11.984 5.3-5.285a1 1 0 1 0-1.412-1.416l-5.3 5.285-5.285-5.3A1 1 0 1 0 5.319 6.68l5.285 5.3L5.3 17.265a1 1 0 1 0 1.412 1.416l5.3-5.285L17.3 18.7a1 1 0 1 0 1.416-1.412z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div class="dc-card">
        {{-- Header Section --}}
        <div class="dc-header">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="customer_id" class="dc-form-label">{{ __('Customer') }}</label>
                    {{ Form::select('customer_id', $customers, $delayedCharge->customer_id, [
                        'class' => 'dc-form-select',
                        'id' => 'customer_id',
                        'placeholder' => 'Choose a customer',
                        'required' => 'required',
                    ]) }}
                </div>
                <div class="col-md-5 text-end">
                    <div style="margin-top: 20px;">
                        <label style="font-size: 12px; color: #6b6c72; text-transform: uppercase; letter-spacing: 0.5px;">{{ __('AMOUNT') }}</label>
                        <div style="font-size: 28px; font-weight: 500; color: #393a3d;" id="header-amount-display">${{ number_format($delayedCharge->total_amount, 2) }}</div>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="date" class="dc-form-label">{{ __('Delayed Charge Date') }}</label>
                    {{ Form::date('date', $delayedCharge->date ? $delayedCharge->date->format('Y-m-d') : date('Y-m-d'), [
                        'class' => 'dc-form-control',
                        'id' => 'date',
                        'required' => 'required',
                        'style' => 'width: 185px;',
                    ]) }}
                </div>
            </div>
        </div>

        {{-- Product Section --}}
        <div class="product-section">
            <table class="product-table" id="items-table">
                <thead>
                    <tr>
                        <th style="width: 30px;"></th>
                        <th style="width: 30px;">#</th>
                        <th style="width: 250px;">{{ __('PRODUCT/SERVICE') }}</th>
                        <th>{{ __('DESCRIPTION') }}</th>
                        <th style="width: 80px;">{{ __('QTY') }}</th>
                        <th style="width: 100px;">{{ __('RATE') }}</th>
                        <th style="width: 100px;">{{ __('AMOUNT') }}</th>
                        <th style="width: 50px;">{{ __('TAX') }}</th>
                        <th style="width: 40px;"></th>
                    </tr>
                </thead>
                <tbody id="items-body">
                    @php $rowIndex = 0; @endphp
                    @forelse($delayedCharge->lines as $line)
                        <tr class="item-row">
                            <td>
                                <div class="drag-handle">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                        <circle cx="8" cy="6" r="2"></circle>
                                        <circle cx="16" cy="6" r="2"></circle>
                                        <circle cx="8" cy="12" r="2"></circle>
                                        <circle cx="16" cy="12" r="2"></circle>
                                        <circle cx="8" cy="18" r="2"></circle>
                                        <circle cx="16" cy="18" r="2"></circle>
                                    </svg>
                                </div>
                            </td>
                            <td><span class="line-number">{{ $rowIndex + 1 }}</span></td>
                            <td>
                                {{ Form::select('items['.$rowIndex.'][item]', $product_services, $line->product_id, [
                                    'class' => 'dc-form-select item-select',
                                    'placeholder' => 'Select a product/service',
                                ]) }}
                            </td>
                            <td>
                                {{ Form::textarea('items['.$rowIndex.'][description]', $line->description, [
                                    'class' => 'dc-form-control item-description',
                                    'rows' => '1',
                                ]) }}
                            </td>
                            <td>
                                {{ Form::text('items['.$rowIndex.'][quantity]', $line->quantity, [
                                    'class' => 'dc-form-control input-right item-quantity',
                                ]) }}
                            </td>
                            <td>
                                {{ Form::text('items['.$rowIndex.'][price]', $line->rate, [
                                    'class' => 'dc-form-control input-right item-price',
                                ]) }}
                            </td>
                            <td>
                                <input type="text" name="items[{{ $rowIndex }}][amount]" class="dc-form-control input-right item-amount" value="{{ number_format($line->amount, 2) }}" readonly>
                            </td>
                            <td>
                                <div class="form-check">
                                    <input class="form-check-input item-tax" type="checkbox" name="items[{{ $rowIndex }}][tax]" value="1" {{ $line->tax ? 'checked' : '' }}>
                                </div>
                            </td>
                            <td>
                                <span class="delete-icon delete-row" title="Delete line">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"></path>
                                    </svg>
                                </span>
                            </td>
                        </tr>
                        @php $rowIndex++; @endphp
                    @empty
                        <tr class="item-row">
                            <td>
                                <div class="drag-handle">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                        <circle cx="8" cy="6" r="2"></circle>
                                        <circle cx="16" cy="6" r="2"></circle>
                                        <circle cx="8" cy="12" r="2"></circle>
                                        <circle cx="16" cy="12" r="2"></circle>
                                        <circle cx="8" cy="18" r="2"></circle>
                                        <circle cx="16" cy="18" r="2"></circle>
                                    </svg>
                                </div>
                            </td>
                            <td><span class="line-number">1</span></td>
                            <td>
                                {{ Form::select('items[0][item]', $product_services, '', [
                                    'class' => 'dc-form-select item-select',
                                    'placeholder' => 'Select a product/service',
                                ]) }}
                            </td>
                            <td>
                                {{ Form::textarea('items[0][description]', null, [
                                    'class' => 'dc-form-control item-description',
                                    'rows' => '1',
                                ]) }}
                            </td>
                            <td>
                                {{ Form::text('items[0][quantity]', '', [
                                    'class' => 'dc-form-control input-right item-quantity',
                                ]) }}
                            </td>
                            <td>
                                {{ Form::text('items[0][price]', '', [
                                    'class' => 'dc-form-control input-right item-price',
                                ]) }}
                            </td>
                            <td>
                                <input type="text" name="items[0][amount]" class="dc-form-control input-right item-amount" value="0.00" readonly>
                            </td>
                            <td>
                                <div class="form-check">
                                    <input class="form-check-input item-tax" type="checkbox" name="items[0][tax]" value="1">
                                </div>
                            </td>
                            <td>
                                <span class="delete-icon delete-row" title="Delete line">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"></path>
                                    </svg>
                                </span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="table-actions">
                <button type="button" class="btn-action" id="add-line">{{ __('Add lines') }}</button>
                <button type="button" class="btn-action" id="clear-lines">{{ __('Clear all lines') }}</button>
            </div>
        </div>

        {{-- Bottom Section --}}
        <div class="bottom-section">
            <div class="row">
                <div class="col-md-6">
                    <div class="info-field mb-4">
                        <label for="memo" class="dc-form-label">{{ __('Memo') }}</label>
                        {{ Form::textarea('memo', $delayedCharge->memo, [
                            'class' => 'dc-form-control',
                            'id' => 'memo',
                            'rows' => '3',
                        ]) }}
                    </div>

                    <div class="info-field">
                        <label class="dc-form-label">{{ __('Attachments') }}</label>
                        @if($delayedCharge->attachments && count($delayedCharge->attachments) > 0)
                            <div class="mb-2">
                                <strong>{{ __('Existing:') }}</strong>
                                @foreach($delayedCharge->attachments as $attachment)
                                    <span class="existing-attachment">
                                        <a href="{{ asset('storage/uploads/delayed_charge_attachments/' . $attachment) }}" target="_blank">{{ $attachment }}</a>
                                    </span>
                                @endforeach
                            </div>
                        @endif
                        <div class="attachment-zone" onclick="document.getElementById('attachments').click()">
                            <span class="attachment-link">{{ __('Add attachment') }}</span>
                            <div class="attachment-limit">{{ __('Max file size: 20 MB') }}</div>
                        </div>
                        <input type="file" name="attachments[]" id="attachments" multiple style="display: none;">
                        <div id="attachment-list" class="mt-2"></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="totals-section">
                        <div class="total-row final">
                            <span>{{ __('Total') }}</span>
                            <span id="total-amount">${{ number_format($delayedCharge->total_amount, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="dc-footer">
        <div class="footer-left">
            <button type="button" class="dc-btn dc-btn-secondary" onclick="$('#delayedChargeModal').modal('hide');">{{ __('Cancel') }}</button>
        </div>
        <div class="footer-center">
            <a href="#" class="text-primary">{{ __('Make recurring') }}</a>
        </div>
        <div class="footer-actions">
            <div class="dc-btn-group dropup">
                <button type="submit" class="dc-btn dc-btn-primary">{{ __('Save and close') }}</button>
                <button type="button" class="dc-btn dc-btn-primary dropdown-toggle-split" onclick="document.getElementById('saveMenu').classList.toggle('show')">
                    <span>▼</span>
                </button>
                <ul class="dc-dropdown-menu" id="saveMenu">
                    <li><button type="submit" class="dc-dropdown-item" name="save_action" value="save_close">{{ __('Save and close') }}</button></li>
                    <li><button type="submit" class="dc-dropdown-item" name="save_action" value="save_new">{{ __('Save and new') }}</button></li>
                </ul>
            </div>
        </div>
    </div>

    {{ Form::close() }}
</div>

<script>
    $(document).ready(function() {
        var rowIndex = {{ $delayedCharge->lines->count() > 0 ? $delayedCharge->lines->count() : 1 }};

        // Recalculate row amount
        function recalcRow($row) {
            var qty = parseFloat($row.find('.item-quantity').val()) || 0;
            var price = parseFloat($row.find('.item-price').val()) || 0;
            var amount = qty * price;
            $row.find('.item-amount').val(amount.toFixed(2));
            recalcTotal();
        }

        // Recalculate total
        function recalcTotal() {
            var total = 0;
            $('.item-amount').each(function() {
                total += parseFloat($(this).val()) || 0;
            });
            $('#total-amount').text('$' + total.toFixed(2));
            $('#header-amount-display').text('$' + total.toFixed(2));
        }

        // Renumber lines
        function renumberLines() {
            $('#items-body .item-row').each(function(idx) {
                $(this).find('.line-number').text(idx + 1);
            });
        }

        // Add new line
        $('#add-line').on('click', function() {
            var newRow = `
                <tr class="item-row">
                    <td>
                        <div class="drag-handle">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <circle cx="8" cy="6" r="2"></circle>
                                <circle cx="16" cy="6" r="2"></circle>
                                <circle cx="8" cy="12" r="2"></circle>
                                <circle cx="16" cy="12" r="2"></circle>
                                <circle cx="8" cy="18" r="2"></circle>
                                <circle cx="16" cy="18" r="2"></circle>
                            </svg>
                        </div>
                    </td>
                    <td><span class="line-number">${rowIndex + 1}</span></td>
                    <td>
                        <select name="items[${rowIndex}][item]" class="dc-form-select item-select">
                            <option value="">Select a product/service</option>
                            @foreach($product_services as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <textarea name="items[${rowIndex}][description]" class="dc-form-control item-description" rows="1"></textarea>
                    </td>
                    <td>
                        <input type="text" name="items[${rowIndex}][quantity]" class="dc-form-control input-right item-quantity">
                    </td>
                    <td>
                        <input type="text" name="items[${rowIndex}][price]" class="dc-form-control input-right item-price">
                    </td>
                    <td>
                        <input type="text" name="items[${rowIndex}][amount]" class="dc-form-control input-right item-amount" value="0.00" readonly>
                    </td>
                    <td>
                        <div class="form-check">
                            <input class="form-check-input item-tax" type="checkbox" name="items[${rowIndex}][tax]" value="1">
                        </div>
                    </td>
                    <td>
                        <span class="delete-icon delete-row" title="Delete line">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"></path>
                            </svg>
                        </span>
                    </td>
                </tr>
            `;
            $('#items-body').append(newRow);
            rowIndex++;
            renumberLines();
        });

        // Clear all lines
        $('#clear-lines').on('click', function() {
            if (confirm('{{ __("Clear all lines?") }}')) {
                $('#items-body .item-row').slice(1).remove();
                var $first = $('#items-body .item-row:first');
                $first.find('.item-select').val('');
                $first.find('.item-description').val('');
                $first.find('.item-quantity').val('');
                $first.find('.item-price').val('');
                $first.find('.item-amount').val('0.00');
                $first.find('.item-tax').prop('checked', false);
                renumberLines();
                recalcTotal();
            }
        });

        // Delete row
        $(document).on('click', '.delete-row', function() {
            var $row = $(this).closest('.item-row');
            if ($('#items-body .item-row').length > 1) {
                $row.remove();
                renumberLines();
                recalcTotal();
            }
        });

        // Recalc on quantity/price change
        $(document).on('input', '.item-quantity, .item-price', function() {
            recalcRow($(this).closest('.item-row'));
        });

        // Product selection - fetch price
        $(document).on('change', '.item-select', function() {
            var $row = $(this).closest('.item-row');
            var productId = $(this).val();
            if (productId) {
                $.ajax({
                    url: '{{ route("invoice.product") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        product_id: productId
                    },
                    success: function(response) {
                        if (response.productService) {
                            $row.find('.item-description').val(response.productService.description || '');
                            $row.find('.item-quantity').val(1);
                            $row.find('.item-price').val(response.productService.sale_price || 0);
                            recalcRow($row);
                        }
                    }
                });
            }
        });

        // Show attached file names
        $('#attachments').on('change', function() {
            var files = this.files;
            var list = '';
            for (var i = 0; i < files.length; i++) {
                list += '<div class="badge bg-primary me-1 mb-1">' + files[i].name + '</div>';
            }
            $('#attachment-list').html(list);
        });

        // Close dropdown on outside click
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.dc-btn-group').length) {
                $('#saveMenu').removeClass('show');
            }
        });
    });
</script>
