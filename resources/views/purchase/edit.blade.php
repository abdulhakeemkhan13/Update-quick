@extends('layouts.admin')
@section('page-title')
    {{ __('Purchase Edit') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('expense.index') }}">{{ __('Expenses') }}</a></li>
    <li class="breadcrumb-item">{{ __('Purchase Edit') }}</li>
@endsection

@push('script-page')
    <script src="{{ asset('js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('js/jquery.repeater.min.js') }}"></script>
    <script src="{{ asset('js/jquery-searchbox.js') }}"></script>
    <script>
        $(document).ready(function() {
            let categoryLineCount = {{ count($purchase->accounts ?? []) }};
            let itemLineCount = {{ count($purchase->items ?? []) }};

            // Add Category Line
            $('#add-category-line').on('click', function() {
                const newRow = `
                <tr class="category-row">
                    <td>
                        <span class="text-muted me-2 drag-handle" style="cursor: move; font-size: 18px;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" color="#babec5" width="16px" height="16px" focusable="false" aria-hidden="true">
                                <path fill="currentColor" d="m10.636 4.565-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0"></path>
                                <path fill="currentColor" d="m10.636 4.565-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0M4.636 4.565l-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.071-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.729 0M16.636 4.565l-.071.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.071-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.729 0M10.636 10.565l-.071.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.729 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.729 0"></path>
                                <path fill="currentColor" d="m10.636 10.565-.071.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.729 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.729 0M4.636 10.565l-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0M16.636 10.565l-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0M10.636 16.565l-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0"></path>
                                <path fill="currentColor" d="m10.636 16.565-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0M4.636 16.565l-.071.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.729 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0M16.636 16.565l-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0"></path>
                            </svg>
                        </span>
                    </td>
                    <td><span class="row-number qbo-line-number">${categoryLineCount + 1}</span></td>
                    <td>
                        <select name="category[${categoryLineCount}][account_id]" class="form-control select2 category-select category-account">
                            <option value="">{{ __('Select account') }}</option>
                            @foreach ($chartAccounts as $id => $account)
                                <option value="{{ $id }}">{{ $account }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td><textarea name="category[${categoryLineCount}][description]" class="form-control" rows="1"></textarea></td>
                    <td><input type="number" name="category[${categoryLineCount}][amount]" class="form-control text-end category-amount" step="0.01" value="0.00"></td>
                    <td>
                        <select name="category[${categoryLineCount}][customer_id]" class="form-control select2 customer-select">
                            <option value="">-</option>
                            @foreach ($customers ?? [] as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="row-actions"><i class="fas fa-trash delete-row"></i></td>
                </tr>
            `;
                $('#category-tbody').append(newRow);
                categoryLineCount++;
                updateLineNumbers();
            });

            // Add Item Line
            $('#add-item-line').on('click', function() {
                const newRow = `
                <tr class="item-row">
                    <td>
                        <span class="text-muted me-2 drag-handle" style="cursor: move; font-size: 18px;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" color="#babec5" width="16px" height="16px" focusable="false" aria-hidden="true">
                                <path fill="currentColor" d="m10.636 4.565-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0"></path>
                                <path fill="currentColor" d="m10.636 4.565-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0M4.636 4.565l-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.071-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.729 0M16.636 4.565l-.071.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.071-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.729 0M10.636 10.565l-.071.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.729 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.729 0"></path>
                                <path fill="currentColor" d="m10.636 10.565-.071.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.729 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.729 0M4.636 10.565l-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0M16.636 10.565l-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0M10.636 16.565l-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0"></path>
                                <path fill="currentColor" d="m10.636 16.565-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0M4.636 16.565l-.071.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.729 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0M16.636 16.565l-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0"></path>
                            </svg>
                        </span>
                    </td>
                    <td><span class="row-number qbo-line-number">${itemLineCount + 1}</span></td>
                    <td>
                        {{ Form::select("items[\${itemLineCount}][product_id]", $product_services ?? [], null, [
                            'class' => 'form-control select2 item-select item-product',
                            'placeholder' => 'Select Product/Service',
                        ]) }}
                    </td>
                    <td><textarea name="items[${itemLineCount}][description]" class="form-control item-description" rows="1"></textarea></td>
                    <td><input type="number" name="items[${itemLineCount}][quantity]" class="form-control text-center item-qty" min="1" value="1"></td>
                    <td><input type="number" name="items[${itemLineCount}][price]" class="form-control text-end item-rate" step="0.01" placeholder="0.00"></td>
                    <td><input type="number" name="items[${itemLineCount}][amount]" class="form-control text-end item-amount" step="0.01" readonly></td>
                    <td>
                        <select name="items[${itemLineCount}][customer_id]" class="form-control select2 customer-select">
                            <option value="">-</option>
                            @foreach ($customers ?? [] as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="row-actions"><i class="fas fa-trash delete-row"></i></td>
                </tr>
            `;
                $('#item-tbody').append(newRow);
                itemLineCount++;
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

            // Calculate Total
            function calculateTotal() {
                var subtotal = 0;

                // Sum category amounts
                $('.category-amount').each(function() {
                    subtotal += parseFloat($(this).val()) || 0;
                });

                // Sum item amounts
                $('.item-amount').each(function() {
                    subtotal += parseFloat($(this).val()) || 0;
                });

                // Update displays
                $('#subtotal').val(subtotal.toFixed(2));
                $('#total').val(subtotal.toFixed(2));
                $('#subtotal-display').text('$' + subtotal.toFixed(2));
                $('#total-display').text('$' + subtotal.toFixed(2));
                $('.grand-total-display').text('$' + subtotal.toFixed(2));
            }

            // Recalculate on amount changes
            $(document).on('keyup change', '.category-amount, .item-amount', function() {
                calculateTotal();
            });

            // Recalculate on quantity/rate changes
            $(document).on('keyup change', '.item-qty, .item-rate', function() {
                var row = $(this).closest('tr');
                var qty = parseFloat(row.find('.item-qty').val()) || 0;
                var rate = parseFloat(row.find('.item-rate').val()) || 0;
                var amount = qty * rate;
                row.find('.item-amount').val(amount.toFixed(2));
                calculateTotal();
            });

            // Vendor Selection
            $(document).on('change', '#vendor_selector', function() {
                var vendorId = $(this).val();
                if (vendorId) {
                    $.ajax({
                        url: '{{ route('bill.vender') }}',
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        data: {
                            id: vendorId
                        },
                        success: function(data) {
                            if (typeof data === 'object' && data.address) {
                                $('#vendor_address').val(data.address);
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
            $('#bill-form').on('submit', function(e) {
                e.preventDefault();
                $('.btn-qbo-save').prop('disabled', true).text('{{ __('Updating...') }}');

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
                                show_toastr('success',
                                    '{{ __('Purchase Order updated successfully') }}',
                                    'success');
                            }
                            const PO_URL = "{{ route('expense.index') }}";
                            setTimeout(() => {
                                window.location.href = PO_URL;
                            }, 500);
                        } else {
                            show_toastr('success', response.message ||
                                '{{ __('Purchase Order updated successfully') }}',
                                'success');
                            $('.btn-qbo-save').prop('disabled', false).text(
                                '{{ __('Save') }}');
                        }
                    },
                    error: function(xhr) {
                        let message = '{{ __('Error updating purchase order') }}';
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

        // Auto-show modal
        $(document).ready(function() {
            var expenseModal = new bootstrap.Modal(document.getElementById('expense-modal'), {
                backdrop: 'static',
                keyboard: false
            });
            expenseModal.show();
        });
    </script>
@endpush

@section('content')
    <div class="modal fade" id="expense-modal" tabindex="-1" aria-labelledby="expenseModalLabel" aria-hidden="true"
        style="background: #ffffff;">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="row">
                    <div class="d-flex justify-content-between align-items-center border-bottom"
                        style="font-size: 15px; font-weight: 600; height: 55px; background: #f4f5f8; position: fixed; top: 0; left: 0; right: 0; z-index: 999; padding: 0 10px;">
                        <div class="TrowserHeader d-flex align-items-center">
                            <a href="#" class="text-dark me-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    color="currentColor" width="24px" height="24px" focusable="false" aria-hidden="true">
                                    <path fill="currentColor"
                                        d="M13.007 7a1 1 0 0 0-1 1L12 12a1 1 0 0 0 1 1l3.556.006a1 1 0 0 0 0-2L14 11l.005-3a1 1 0 0 0-.998-1">
                                    </path>
                                    <path fill="currentColor"
                                        d="M19.374 5.647A8.94 8.94 0 0 0 13.014 3H13a8.98 8.98 0 0 0-8.98 8.593l-.312-.312a1 1 0 0 0-1.416 1.412l2 2a1 1 0 0 0 1.414 0l2-2a1 1 0 0 0-1.412-1.416l-.272.272A6.984 6.984 0 0 1 13 5h.012A7 7 0 0 1 13 19h-.012a7 7 0 0 1-4.643-1.775 1 1 0 1 0-1.33 1.494A9 9 0 0 0 12.986 21H13a9 9 0 0 0 6.374-15.353">
                                    </path>
                                </svg>
                            </a>
                            <h5 class="mb-0" style="font-size: 1.2rem;">Purchase Order
                                #{{ Auth::user()->purchaseNumberFormat($purchase->purchase_id) }}</h5>
                        </div>
                        <div class="TrowserHeader d-flex align-items-center">
                            <div class="TrowserHeader">
                                <a href="{{ route('expense.index') }}" class="text-dark me-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        color="currentColor" width="24px" height="24px" focusable="false"
                                        aria-hidden="true">
                                        <path fill="currentColor"
                                            d="m13.432 11.984 5.3-5.285a1 1 0 1 0-1.412-1.416l-5.3 5.285-5.285-5.3A1 1 0 1 0 5.319 6.68l5.285 5.3L5.3 17.265a1 1 0 1 0 1.412 1.416l5.3-5.285L17.3 18.7a1 1 0 1 0 1.416-1.412z">
                                        </path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>

                    {{ Form::model($purchase, ['route' => ['purchase.update', $purchase->id], 'method' => 'PUT', 'enctype' => 'multipart/form-data', 'class' => 'w-100', 'style' => 'padding: 30px 30px; background: #ffffff;', 'id' => 'bill-form']) }}
                    <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
                    <input type="hidden" name="purchase_id" value="{{ $purchase->id }}">

                    <div class="col-12">
                        <div class="card">
                            <div class="card-body" style="background:#f4f5f8;">

                                <!-- ========== TOP SECTION ========== -->
                                <div class="row align-items-start">
                                    <!-- LEFT & RIGHT Columns for Form Fields -->
                                    <div class="col-10">
                                        <div class="row">
                                            <!-- Left Column: Vendor & Mailing -->
                                            <div class="col-md-4">
                                                <div class="form-group mb-3">
                                                    <label class="form-label">{{ __('Vendor') }}</label>
                                                    <select name="vendor_id" class="form-control select2"
                                                        id="vendor_selector" required>
                                                        <option value="">{{ __('Choose a vendor') }}</option>
                                                        @foreach ($vendors as $id => $vendor)
                                                            <option value="{{ $id }}"
                                                                {{ $purchase->vender_id == $id ? 'selected' : '' }}>
                                                                {{ $vendor }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="form-group mb-3">
                                                    <label class="form-label">{{ __('Mailing address') }}</label>
                                                    <textarea class="form-control" name="mailing_address" id="vendor_address" rows="5" style="background: #ffffff;">{{ $purchase->mailing_address }}</textarea>
                                                </div>
                                            </div>

                                            <!-- Middle Column: Email, Shipping, Dates -->
                                            <div class="col-md-8">
                                                <!-- Row 1: Email & Status -->
                                                <div class="row">
                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label">{{ __('Email') }}</label>
                                                        <input type="email" name="vendor_email" class="form-control"
                                                            value="{{ $purchase->vendor_email }}"
                                                            placeholder="{{ __('Separate emails with a comma') }}">
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label">{{ __('PO Status') }}</label>
                                                        <select name="status" class="form-control select2">
                                                            @foreach ($statuses as $key => $status)
                                                                <option value="{{ $key }}"
                                                                    {{ $purchase->status == $key ? 'selected' : '' }}>
                                                                    {{ __($status) }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label">{{ __('Ref No.') }}</label>
                                                        <input type="text" name="ref_number" class="form-control"
                                                            value="{{ $purchase->ref_number }}">
                                                    </div>
                                                </div>

                                                <!-- Row 2: Ship to & Address -->
                                                <div class="row">
                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label">{{ __('Ship to') }}</label>
                                                        <select name="ship_to" class="form-control select2">
                                                            <option value="">{{ __('Select customer for address') }}
                                                            </option>
                                                            @foreach ($customers ?? [] as $customer)
                                                                <option value="{{ $customer->id }}"
                                                                    {{ $purchase->ship_to == $customer->id ? 'selected' : '' }}>
                                                                    {{ $customer->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-8 mb-3">
                                                        <label class="form-label">{{ __('Shipping address') }}</label>
                                                        <textarea class="form-control" name="ship_to_address" rows="2">{{ $purchase->ship_to_address }}</textarea>
                                                    </div>
                                                </div>

                                                <!-- Row 3: Dates -->
                                                <div class="row">
                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label">{{ __('PO Date') }}</label>
                                                        <input type="date" name="po_date" class="form-control"
                                                            required
                                                            value="{{ $purchase->po_date ? \Carbon\Carbon::parse($purchase->po_date)->format('Y-m-d') : '' }}">
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label">{{ __('Ship Via') }}</label>
                                                        <input type="text" name="ship_via" class="form-control"
                                                            value="{{ $purchase->ship_via }}"
                                                            placeholder="FedEx, UPS, etc.">
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label">{{ __('Due Date') }}</label>
                                                        <input type="date" name="expected_date" class="form-control"
                                                            value="{{ $purchase->expected_date ? \Carbon\Carbon::parse($purchase->expected_date)->format('Y-m-d') : '' }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- RIGHT: AMOUNT Display -->
                                    <div class="col-2 text-end" style="margin-top: -34px;">
                                        <div class="d-flex flex-column align-items-end">
                                            <label class="form-label mb-0" style="color:#6b6c72;">AMOUNT</label>
                                            <p class="h3 mb-0 grand-total-display"
                                                style="font-size:36px;font-weight:900;">
                                                ${{ number_format($purchase->getTotal(), 2) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="containerbox mb-3" style="background-color: white;">
                        <!-- ======================== CATEGORY TABLE ======================== -->
                        <div class="col-12">
                            <div class="custom-accordion">
                                <div class="accordion-header d-flex" onclick="toggleAccordion(this)">
                                    <div class="accordion-arrow">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            color="currentColor" width="24px" height="24px" focusable="false"
                                            aria-hidden="true">
                                            <path fill="currentColor"
                                                d="M9.009 19.013a1 1 0 0 1-.709-1.708l5.3-5.285-5.281-5.3a1 1 0 1 1 1.416-1.413l5.991 6.01a1 1 0 0 1 0 1.413l-6.011 5.991a1 1 0 0 1-.706.292">
                                            </path>
                                        </svg>
                                    </div>
                                    <h5 class="mb-0">{{ __('Category details') }}</h5>
                                </div>
                                <div class="accordion-content" style="display: block;">
                                    <div class="card repeater" id="category-repeater">
                                        <div class="card-body table-border-style">
                                            <div class="table-responsive">
                                                <table class="table align-middle" id="category-table">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th width="1%"></th>
                                                            <th width="1%">#</th>
                                                            <th width="20%">{{ __('CATEGORY') }}</th>
                                                            <th width="30%">{{ __('DESCRIPTION') }}</th>
                                                            <th width="12%" class="text-end">{{ __('AMOUNT') }}</th>
                                                            <th width="15%">{{ __('CUSTOMER') }}</th>
                                                            <th width="5%"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody data-repeater-list="categories" id="category-tbody">
                                                        @foreach ($purchase->accounts ?? [] as $i => $category)
                                                            <tr data-repeater-item class="category-row">
                                                                <td>
                                                                    <input type="hidden"
                                                                        name="category[{{ $i }}][id]"
                                                                        value="{{ $category->id }}">
                                                                    <span class="text-muted me-2 drag-handle"
                                                                        style="cursor: move; font-size: 18px;">
                                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                                            fill="none" viewBox="0 0 24 24"
                                                                            color="#babec5" width="16px" height="16px"
                                                                            focusable="false" aria-hidden="true">
                                                                            <path fill="currentColor"
                                                                                d="m10.636 4.565-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0">
                                                                            </path>
                                                                            <path fill="currentColor"
                                                                                d="m10.636 4.565-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0M4.636 4.565l-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.071-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.729 0M16.636 4.565l-.071.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.071-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.729 0M10.636 10.565l-.071.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.729 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.729 0">
                                                                            </path>
                                                                        </svg>
                                                                    </span>
                                                                </td>
                                                                <td><span
                                                                        class="row-number qbo-line-number">{{ $i + 1 }}</span>
                                                                </td>
                                                                <td>
                                                                    <select
                                                                        name="category[{{ $i }}][account_id]"
                                                                        class="form-control select2 category-select category-account">
                                                                        <option value="">{{ __('Select account') }}
                                                                        </option>
                                                                        @foreach ($chartAccounts as $id => $account)
                                                                            <option value="{{ $id }}"
                                                                                {{ ($category->chart_account_id ?? '') == $id ? 'selected' : '' }}>
                                                                                {{ $account }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </td>
                                                                <td>{{ Form::textarea("category[{$i}][description]", $category->description ?? '', ['class' => 'form-control', 'rows' => 1]) }}
                                                                </td>
                                                                <td>{{ Form::number("category[{$i}][amount]", $category->price ?? 0, ['class' => 'form-control text-end category-amount', 'step' => '0.01']) }}
                                                                </td>
                                                                <td>
                                                                    <select
                                                                        name="category[{{ $i }}][customer_id]"
                                                                        class="form-control select2 customer-select">
                                                                        <option value="">-</option>
                                                                        @foreach ($customers ?? [] as $customer)
                                                                            <option value="{{ $customer->id }}"
                                                                                {{ ($category->customer_id ?? '') == $customer->id ? 'selected' : '' }}>
                                                                                {{ $customer->name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </td>
                                                                <td class="row-actions"><i
                                                                        class="fas fa-trash delete-row"></i></td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                                <div class="d-flex"
                                                    style="margin-left: 8px;margin-top: 15px;gap: 7px;background-color: white;">
                                                    <button type="button" id="add-category-line"
                                                        style="background-color: white;border-radius: 5px;border: 2px solid #8D9096;color: #393A3D;font-weight: 600;padding: 0px 14px;border-radius: 4px;margin-left: 10px;cursor: pointer;font-size: 14px;"
                                                        class="qbo-add-line-btn">
                                                        <span>Add line</span>
                                                    </button>
                                                    <button type="button" id="clear-category-lines"
                                                        style="background-color: white;border-radius: 5px;border: 2px solid #8D9096;color: #393A3D;font-weight: 600;padding: 0px 12px;border-radius: 4px;margin-left: 10px;cursor: pointer;font-size: 14px;"
                                                        class="qbo-clear-btn">
                                                        <span>Clear all lines</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ======================== ITEM TABLE ======================== -->
                        <div class="col-12">
                            <div class="custom-accordion">
                                <div class="accordion-header d-flex" onclick="toggleAccordion(this)">
                                    <div class="accordion-arrow">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            color="currentColor" width="24px" height="24px" focusable="false"
                                            aria-hidden="true">
                                            <path fill="currentColor"
                                                d="M9.009 19.013a1 1 0 0 1-.709-1.708l5.3-5.285-5.281-5.3a1 1 0 1 1 1.416-1.413l5.991 6.01a1 1 0 0 1 0 1.413l-6.011 5.991a1 1 0 0 1-.706.292">
                                            </path>
                                        </svg>
                                    </div>
                                    <h5 class="mb-0">{{ __('Item details') }}</h5>
                                </div>
                                <div class="accordion-content" style="display: block;">
                                    <div class="card repeater" id="item-repeater">
                                        <div class="card-body table-border-style">
                                            <div class="table-responsive">
                                                <table class="table align-middle" id="item-table">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th width="1%"></th>
                                                            <th width="1%">#</th>
                                                            <th width="20%">{{ __('PRODUCT/SERVICE') }}</th>
                                                            <th width="22%">{{ __('DESCRIPTION') }}</th>
                                                            <th width="8%" class="text-end">{{ __('QTY') }}</th>
                                                            <th width="10%" class="text-end">{{ __('RATE') }}</th>
                                                            <th width="10%" class="text-end">{{ __('AMOUNT') }}</th>
                                                            <th width="12%">{{ __('CUSTOMER') }}</th>
                                                            <th width="5%"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody data-repeater-list="items" id="item-tbody">
                                                        @foreach ($purchase->items ?? [] as $i => $item)
                                                            <tr data-repeater-item class="item-row">
                                                                <td>
                                                                    <input type="hidden"
                                                                        name="items[{{ $i }}][id]"
                                                                        value="{{ $item->id }}">
                                                                    <span class="text-muted me-2 drag-handle"
                                                                        style="cursor: move; font-size: 18px;">
                                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                                            fill="none" viewBox="0 0 24 24"
                                                                            color="#babec5" width="16px" height="16px"
                                                                            focusable="false" aria-hidden="true">
                                                                            <path fill="currentColor"
                                                                                d="m10.636 4.565-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0">
                                                                            </path>
                                                                        </svg>
                                                                    </span>
                                                                </td>
                                                                <td><span
                                                                        class="row-number qbo-line-number">{{ $i + 1 }}</span>
                                                                </td>
                                                                <td>
                                                                    {{ Form::select("items[{$i}][product_id]", $product_services ?? [], $item->product_id ?? null, [
                                                                        'class' => 'form-control select2 item-select item-product',
                                                                        'placeholder' => 'Select Product/Service',
                                                                    ]) }}
                                                                </td>
                                                                <td>
                                                                    {{ Form::textarea("items[{$i}][description]", $item->description ?? '', [
                                                                        'class' => 'form-control item-description',
                                                                        'rows' => 1,
                                                                    ]) }}
                                                                </td>
                                                                <td>
                                                                    {{ Form::number("items[{$i}][quantity]", $item->quantity ?? 1, [
                                                                        'class' => 'form-control text-center item-qty',
                                                                        'min' => 1,
                                                                    ]) }}
                                                                </td>
                                                                <td>
                                                                    {{ Form::number("items[{$i}][price]", $item->price ?? 0, [
                                                                        'class' => 'form-control text-end item-rate',
                                                                        'step' => '0.01',
                                                                        'placeholder' => '0.00',
                                                                    ]) }}
                                                                </td>
                                                                <td>
                                                                    {{ Form::number("items[{$i}][amount]", ($item->quantity ?? 1) * ($item->price ?? 0), ['class' => 'form-control text-end item-amount', 'step' => '0.01', 'readonly' => true]) }}
                                                                </td>
                                                                <td>
                                                                    <select name="items[{{ $i }}][customer_id]"
                                                                        class="form-control select2 customer-select">
                                                                        <option value="">-</option>
                                                                        @foreach ($customers ?? [] as $customer)
                                                                            <option value="{{ $customer->id }}"
                                                                                {{ ($item->customer_id ?? '') == $customer->id ? 'selected' : '' }}>
                                                                                {{ $customer->name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </td>
                                                                <td class="row-actions"><i
                                                                        class="fas fa-trash delete-row"></i></td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                                <div class="d-flex"
                                                    style="margin-left: 8px;margin-top: 15px;gap: 7px;background-color: white;">
                                                    <button type="button" id="add-item-line"
                                                        style="background-color: white;border-radius: 5px;border: 2px solid #8D9096;color: #393A3D;font-weight: 600;padding: 0px 14px;border-radius: 4px;margin-left: 10px;cursor: pointer;font-size: 14px;"
                                                        class="qbo-add-line-btn">
                                                        <span>Add line</span>
                                                    </button>
                                                    <button type="button" id="clear-item-lines"
                                                        style="background-color: white;border-radius: 5px;border: 2px solid #8D9096;color: #393A3D;font-weight: 600;padding: 0px 12px;border-radius: 4px;margin-left: 10px;cursor: pointer;font-size: 14px;"
                                                        class="qbo-clear-btn">
                                                        <span>Clear all lines</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-transparent">
                                            <div class="text-end d-flex justify-content-end"
                                                style="margin-top: -5px;margin-right: 135px;font-size: 19px;gap: 23px;position: relative;top: -46px;left: 18px;">
                                                <div style="margin-right: 16px;">
                                                    <strong>{{ __('Total') }}:</strong>
                                                </div>
                                                <div>
                                                    <span
                                                        class="h5 text-primary grand-total-display">${{ number_format($purchase->getTotal(), 2) }}</span>
                                                    <input type="hidden" name="total" id="total"
                                                        value="{{ $purchase->getTotal() }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ======================== MEMO AND ATTACHMENT ======================== -->
                        <div class="row" style="padding:20px;">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="vendor_message"
                                        class="form-label">{{ __('Your message to vendor') }}</label>
                                    {{ Form::textarea('vendor_message', $purchase->vendor_message ?? '', [
                                        'class' => 'form-control',
                                        'rows' => '5',
                                        'id' => 'vendor_message',
                                        'maxlength' => '4000',
                                    ]) }}
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="notes" class="form-label">{{ __('Memo') }}</label>
                                    {{ Form::textarea('notes', $purchase->notes ?? '', [
                                        'class' => 'form-control',
                                        'rows' => '5',
                                        'id' => 'notes',
                                        'maxlength' => '4000',
                                    ]) }}
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="attachments" class="form-label">{{ __('Attachments') }}</label>
                                    <div class="border rounded p-3 text-center"
                                        style="border-style: dashed !important; background: #f9fafb;">
                                        <input type="file" name="attachments[]" id="attachments" multiple
                                            class="d-none">
                                        <label for="attachments" style="cursor: pointer; margin: 0;">
                                            <i class="fas fa-paperclip" style="font-size: 20px; color: #0077c5;"></i>
                                            <p class="mb-0 mt-2 small text-muted">{{ __('Add attachment') }}</p>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ======================== FOOTER BUTTONS ======================== -->
                    <div class="row" style="padding:20px;">
                        <div class="col-12 d-flex justify-content-between">
                            <div>
                                <a href="{{ route('expense.index') }}"
                                    class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-success btn-qbo-save">{{ __('Update') }}</button>
                            </div>
                        </div>
                    </div>

                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>
@endsection
