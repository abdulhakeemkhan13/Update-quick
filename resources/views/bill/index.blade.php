@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Bills') }}
@endsection

@push('script-page')
    <script>
        $(document).ready(function() {


            // copy link handler from your existing code
            $('.copy_link').click(function(e) {
                e.preventDefault();
                var copyText = $(this).attr('href');

                document.addEventListener('copy', function(e) {
                    e.clipboardData.setData('text/plain', copyText);
                    e.preventDefault();
                }, true);

                document.execCommand('copy');
                show_toastr('success', 'Url copied to clipboard', 'success');
            });

            // ----- MAIN TABLE CHECKBOX HANDLING -----
            // header select all checkbox toggles row checkboxes
            $(document).on('change', '#select-all-bills', function() {
                var checked = $(this).is(':checked');
                $('.bill-row-checkbox').prop('checked', checked);
            });

            // if any row checkbox changes: toggle header checkbox accordingly
            $(document).on('change', '.bill-row-checkbox', function() {
                var total = $('.bill-row-checkbox').length;
                var checked = $('.bill-row-checkbox:checked').length;
                $('#select-all-bills').prop('checked', total === checked);
            });

            // ----- OPEN PAYMENT MODAL -----
            $('#open-pay-modal').on('click', function(e) {
                e.preventDefault();

                // collect selected bill ids from main table
                var selectedIds = [];
                $('.bill-row-checkbox:checked').each(function() {
                    selectedIds.push($(this).data('bill-id').toString());
                });

                // clear modal table
                var $modalTableBody = $('#payModalTable tbody');
                $modalTableBody.empty();

                // iterate through original table rows and populate modal rows
                $('.bills-main-row').each(function() {
                    var $orig = $(this);
                    var billId = $orig.data('bill-id').toString();
                    var billNumber = $orig.find('.bill-number').text().trim();
                    var vendorName = $orig.find('td:nth-child(3)').text().trim();
                    var dueDate = $orig.find('.bill-due-date').text().trim();
                    var statusHtml = $orig.find('.bill-status').html();
                    var openBalance = parseFloat($orig.data('bill-due')) || 0;

                    // Check if bill is overdue
                    var isOverdue = statusHtml && (statusHtml.toLowerCase().includes('overdue') || 
                                    statusHtml.toLowerCase().includes('unpaid') ||
                                    statusHtml.toLowerCase().includes('partial'));
                    
                    // Status display QBO style
                    var statusDisplay = isOverdue ? 
                        '<span style="color: #d9534f; font-weight: 500;">Overdue</span><br><small style="color: #999; font-size: 11px;">days ago</small>' : 
                        statusHtml;

                    // default selected if was checked on main table
                    var selected = selectedIds.indexOf(billId) !== -1;
                    var checkedAttr = selected ? 'checked' : '';
                    var paymentValue = selected ? openBalance.toFixed(2) : '0.00';
                    var totalValue = selected ? '$' + openBalance.toFixed(2) : '$0.00';

                    // QBO Style row
                    var tr = '<tr data-bill-id="' + billId + '" data-bill-amount="' + openBalance +
                        '" data-bill-due="' + openBalance + '" style="border-bottom: 1px solid #e9ecef;">' +
                        '<td class="text-center align-middle" style="padding: 12px 8px;">' +
                        '<input name="bill_ids[]" value="' + billId + '" type="checkbox" class="modal-row-checkbox form-check-input" ' + checkedAttr + ' style="cursor: pointer;">' +
                        '</td>' +
                        '<td class="align-middle" style="padding: 12px 8px; color: #333;">' + vendorName + '</td>' +
                        '<td class="align-middle" style="padding: 12px 8px; color: #333;">' + billNumber + '</td>' +
                        '<td class="align-middle" style="padding: 12px 8px; color: #333;">' + dueDate + '</td>' +
                        '<td class="align-middle bill-status-display" style="padding: 12px 8px;">' + statusDisplay + '</td>' +
                        '<td class="align-middle text-end bill-open-col" style="padding: 12px 8px; color: #333;">$' + openBalance.toFixed(2) + '</td>' +
                        '<td class="align-middle text-center" style="padding: 12px 8px; color: #999;">Not available</td>' +
                        '<td class="align-middle text-center payment-col" style="padding: 12px 8px;">' +
                        '<input type="number" step="0.01" min="0" class="form-control form-control-sm payment-input text-center" ' +
                        'value="' + paymentValue + '" name="payment_amounts[' + billId + ']" style="width: 90px; margin: 0 auto; border-color: #c0c0c0;">' +
                        '</td>' +
                        '<td class="align-middle text-end total-col" style="padding: 12px 8px; color: #333;">' + totalValue + '</td>' +
                        '</tr>';

                    $modalTableBody.append(tr);
                });
                
                // Update bill count for pagination
                var billCount = $('#payModalTable tbody tr').length;
                $('.total-bills-count').text(billCount);

                // recalc totals and UI in modal
                recalcModalTotals();

                // show modal
                var payModal = new bootstrap.Modal(document.getElementById('payBillModal'), {
                    backdrop: 'static',
                    keyboard: false
                });
                payModal.show();
            });

            // ----- MODAL: checkbox and payment input logic -----
            // when modal checkbox toggled, if checked set payment input to openBalance, if unchecked set payment input to 0
            $(document).on('change', '.modal-row-checkbox', function() {
                var $tr = $(this).closest('tr');
                var openBal = parseFloat($tr.data('bill-due')) || 0;
                var $paymentInput = $tr.find('.payment-input');

                if ($(this).is(':checked')) {
                    // select row: set payment default to open balance
                    $paymentInput.val(openBal.toFixed(2));
                    $tr.find('.total-col').text('$' + openBal.toFixed(2));
                } else {
                    // unselect row: set payment to 0 and total to 0
                    $paymentInput.val('0.00');
                    $tr.find('.total-col').text('$0.00');
                }

                recalcModalTotals();
            });

            // when payment input changes
            $(document).on('input', '.payment-input', function() {
                var $tr = $(this).closest('tr');
                var val = parseFloat($(this).val()) || 0;
                var total = parseFloat($tr.data('bill-due')) || 0;

                // if entered amount greater than total: reduce to total
                if (val > total) {
                    val = total;
                    $(this).val(total.toFixed(2));
                }

                // update total-col: payment value is added in total column per spec
                // spec said: "After entering an amount that amount will also be added in total."
                // Interpreting: total-col should show open amount when row selected; but user requested "total column .... And when a row is selected then it displays it's amount i.e it's total amount."
                // We'll set total-col to show the row's total amount (open balance) when selected; but also ensure total row sums payment inputs.
                var isChecked = $tr.find('.modal-row-checkbox').is(':checked');
                if (isChecked) {
                    $tr.find('.total-col').text('$' + (parseFloat($tr.data('bill-due')) || 0).toFixed(2));
                } else {
                    $tr.find('.total-col').text('$0.00');
                }

                // show partially paid label if payment < total (and payment > 0)
                if (val > 0 && val < total) {
                    $tr.find('.partial-label-container').html(
                        '<span class="badge bg-warning small ms-2">Partially Paid</span>');
                } else {
                    $tr.find('.partial-label-container').empty();
                }


                // Check if not checked but payment added
                const value = parseFloat($(this).val()) || 0; // get numeric value
                const row = $(this).closest('tr');
                const checkbox = row.find('.modal-row-checkbox');

                if (value > 0) {
                    checkbox.prop('checked', true);
                } else {
                    checkbox.prop('checked', false);
                }

                updateModalSelectAllState();

                recalcModalTotals();
            });


            // recalc total display (modal top-right and footer total row)
            function recalcModalTotals() {
                var grandPayment = 0.00;
                var grandOpenBalance = 0.00;
                var grandTotal = 0.00;
                
                $('#payModalTable tbody tr').each(function() {
                    var $tr = $(this);
                    var isChecked = $tr.find('.modal-row-checkbox').is(':checked');
                    var pay = parseFloat($tr.find('.payment-input').val()) || 0;
                    var openBal = parseFloat($tr.data('bill-due')) || 0;
                    
                    grandPayment += pay;
                    if (isChecked) {
                        grandOpenBalance += openBal;
                        grandTotal += openBal;
                    }
                });

                // top right big total (with $ prefix)
                $('.grand-total-value').text(grandPayment.toFixed(2));

                // footer totals with $ prefix
                $('.modal-footer-open-balance').text('$' + grandOpenBalance.toFixed(2));
                $('.modal-footer-credit').text('$0.00');
                $('.modal-footer-payment').text('$0.00');
                $('.modal-footer-total').text('$' + grandPayment.toFixed(2));
            }

            // date filter behavior (in-modal) - this just filters rows client-side by bill date substring
            $(document).on('input', '#modal-date-filter', function() {
                var filter = $(this).val().toLowerCase().trim();
                $('#payModalTable tbody tr').each(function() {
                    var billDate = $(this).find('td:nth-child(4)').text().toLowerCase();
                    if (billDate.indexOf(filter) !== -1 || filter === '') {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });

            // Handle "Select All" inside the modal
            $(document).on('change', '#modalSelectAll', function() {
                const checked = $(this).is(':checked');
                $('.modal-row-checkbox').prop('checked', checked).trigger('change');
                console.log("AAL", $('.modal-row-checkbox'))
            });

            function updateModalSelectAllState() {
                const totalRows = $('.modal-row-checkbox').length;
                const selectedRows = $('.modal-row-checkbox:checked').length;
                $('#modalSelectAll').prop('checked', totalRows === selectedRows);
            }
            // When individual modal checkboxes change, update Select All state
            $(document).on('change', '.modal-row-checkbox', updateModalSelectAllState);

            $(document).on('change', '.item-product', function() {
                const productId = $(this).val();
                const currentRow = $(this).closest('tr');

                if (!productId) return;

                $.ajax({
                    url: '{{ route('bill.product') }}',
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
                            calculateBillTotal();
                        }
                    },
                    error: function(xhr) {
                        console.error('Error fetching product:', xhr);
                        alert('Failed to load product details. Please try again.');
                    }
                });
            });

            // Function to calculate bill totals
            function calculateBillTotal() {
                let subtotal = 0;

                // Sum all category amounts
                $('.category-amount').each(function() {
                    subtotal += parseFloat($(this).val()) || 0;
                });

                // Sum all item amounts
                $('.item-amount').each(function() {
                    subtotal += parseFloat($(this).val()) || 0;
                });

                // Update displays
                $('#subtotal').val(subtotal.toFixed(2));
                $('#total').val(subtotal.toFixed(2));
                $('#subtotal-display').text('$' + subtotal.toFixed(2));
                $('#total-display').text('$' + subtotal.toFixed(2));
                $('#grand-total-display').text('$' + subtotal.toFixed(2));
            }

            // Recalculate when qty or rate changes
            $(document).on('input', '.item-qty, .item-rate', function() {
                const row = $(this).closest('tr');
                const qty = parseFloat(row.find('.item-qty').val()) || 0;
                const rate = parseFloat(row.find('.item-rate').val()) || 0;
                row.find('.item-amount').val((qty * rate).toFixed(2));
                calculateBillTotal();
            });

            // Recalculate when category amount changes
            $(document).on('input', '.category-amount', function() {
                calculateBillTotal();
            });
            // modal proceed payment button - does nothing (explicit per requirements)
            $('#modal-proceed-payment').on('click', function(e) {
                // e.preventDefault();
                // intentionally no-op
                // You can read values now and send to server when you build backend endpoint
            });

            // ensure when modal closed, clear date filter
            $('#payBillModal').on('hidden.bs.modal', function() {
                $('#modal-date-filter').val('');
            });

            $('#bulkPaymentForm').on('submit', function(e) {
                if ($('.modal-row-checkbox:checked').length === 0) {
                    e.preventDefault();
                    alert('Please select at least one bill before proceeding.');
                }
            });


            //remove datatable sorter from select all a
            $('#select-all-bills').closest('a').removeClass('dataTable-sorter');

            // Receive Bill Payment button - navigate to bill payment create page
            $('#open-receive-modal').on('click', function(e) {
                e.preventDefault();
                window.location.href = '{{ route("receive-bill-payment.create") }}';
            });

        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const tableEl = document.querySelector(".datatable");

            if (tableEl) {
                // Destroy existing instance if your theme auto-inits
                if (tableEl.simpleDatatables) {
                    tableEl.simpleDatatables.destroy();
                }

                const dataTable = new simpleDatatables.DataTable(tableEl, {
                    searchable: true,
                    sortable: true,
                    perPageSelect: false,
                    paging: false, // Disable DataTables' pagination
                });

            }
        });
    </script>
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Bill') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="{{ route('bill.export') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
            title="{{ __('Export') }}">
            <i class="ti ti-file-export"></i>
        </a>

        @can('create bill')
            <a href="#" data-url="{{ route('bill.create', 0) }}" data-ajax-popup="true" data-size="fullscreen"
                data-title="{{ __('Create New Bill') }}" data-bs-toggle="tooltip" title="{{ __('Create') }}"
                class="btn btn-sm btn-primary">
                <i class="ti ti-plus"></i>
            </a>
        @endcan
    </div>
@endsection


@section('content')
{{-- MY APPS Sidebar (Fixed Position) --}}
@include('partials.admin.allApps-subMenu-Sidebar', [
    'activeSection' => 'expenses',
    'activeItem' => 'bills'
])

    {{-- tabs --}}
    @include('expense.expense-tabs')
    <div class="float-end">
        <a href="{{ route('bill.export') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
            title="{{ __('Export') }}">
            <i class="ti ti-file-export"></i>
        </a>

        @can('create bill')
            <a href="#" data-url="{{ route('bill.create', 0) }}" data-ajax-popup="true" data-size="fullscreen"
                data-title="{{ __('Create New Bill') }}" data-bs-toggle="tooltip" title="{{ __('Create') }}"
                class="btn btn-sm btn-primary">
                <i class="ti ti-plus"></i>
            </a>
        @endcan

        {{-- Pay Bill Button --}}
        @can('edit bill')
            <button id="open-pay-modal" class="btn btn-sm btn-success" data-bs-toggle="tooltip" title="{{ __('Pay Bill') }}">
                <i class="ti ti-cash"></i> {{ __('Pay Bill') }}
            </button>
        @endcan
        <!-- Recieve Bill Payments -->
         <button id="open-receive-modal" class="btn btn-sm btn-success" data-bs-toggle="tooltip" title="{{ __('Receive Bill Payment') }}">
            <i class="ti ti-cash"></i> {{ __('Receive Bill Payment') }}
        </button>
    </div>
    {{-- Filters Dropdown --}}
    <div class="dropdown mt-4 mb-2">
        <button class="btn btn-outline-primary dropdown-toggle d-flex align-items-center" type="button"
            id="filtersDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="ti ti-filter me-1"></i> {{ __('Filters') }}
        </button>

        <div class="dropdown-menu p-3" style="min-width: 350px;">
            <div class="card shadow-none border-0">
                <div class="card-body p-0">
                    {{ Form::open(['route' => ['bill.index'], 'method' => 'GET', 'id' => 'frm_submit']) }}
                    <div class="row">
                        {{-- Bill Date --}}
                        <div class="col-12 mb-3">
                            {{ Form::label('bill_date', __('Bill Date'), ['class' => 'form-label']) }}
                            {{ Form::text('bill_date', request('bill_date'), [
                                'class' => 'form-control month-btn',
                                'id' => 'pc-daterangepicker-1',
                                'readonly',
                            ]) }}
                        </div>
                        {{-- Vendor --}}
                        <div class="col-12 mb-3">
                            {{ Form::label('vender', __('Vendor'), ['class' => 'form-label']) }}
                            {{ Form::select('vender', $vender, request('vender'), [
                                'class' => 'form-control select',
                                'id' => 'vender',
                            ]) }}
                        </div>


                        {{-- Status --}}
                        <div class="col-12 mb-3">
                            {{ Form::label('status', __('Status'), ['class' => 'form-label']) }}
                            {{ Form::select('status', ['' => __('Select Status')] + $status, request('status'), [
                                'class' => 'form-control select',
                            ]) }}
                        </div>


                        {{-- Buttons --}}
                        <div class="col-12 d-flex justify-content-between">
                            <a href="{{ route('bill.index') }}" class="btn btn-outline-secondary btn-sm"
                                data-bs-toggle="tooltip" title="{{ __('Reset') }}">
                                <i class="ti ti-trash-off"></i> {{ __('Reset') }}
                            </a>

                            <button type="submit" class="btn btn-success btn-sm" data-bs-toggle="tooltip"
                                title="{{ __('Apply') }}">
                                <i class="ti ti-search"></i> {{ __('Apply') }}
                            </button>
                        </div>

                    </div>
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>


    {{-- MAIN TABLE --}}
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th class="text-center">
                                        <input type="checkbox" id="select-all-bills">
                                    </th>
                                    <th class="text-center"> {{ __('Bill') }}</th>
                                    <th class="text-center">{{ __('Vendor') }}</th>
                                    {{-- <th>{{ __('	Paid Amount') }}</th> --}}
                                    <th class="text-center">{{ __('Due Amount') }}</th>
                                    {{-- <th> {{ __('Category') }}</th> --}}
                                    <th class="text-center"> {{ __('Bill Date') }}</th>
                                    <th class="text-center"> {{ __('Due Date') }}</th>
                                    <th class="text-center">{{ __('Status') }}</th>
                                    <th class="text-center">{{ __('Bill Amount') }}</th>
                                    <th class="text-center">{{ __('Open Balance') }}</th>
                                    @if (Gate::check('edit bill') || Gate::check('delete bill') || Gate::check('show bill'))
                                        <th width="10%"> {{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($bills as $bill)
                                    @php
                                        // compute amounts using model methods
                                        $billTotal = (float) $bill->getTotal();
                                        $billDue = (float) $bill->getDue();
                                        // $billPaid = $billTotal - $billDue - $bill->billTotalDebitNote();
                                    @endphp
                                    <tr class="bills-main-row" data-bill-id="{{ $bill->id }}"
                                        data-bill-amount="{{ number_format($billTotal, 2, '.', '') }}"
                                        data-bill-due="{{ number_format($billDue, 2, '.', '') }}">
                                        <td class="text-center align-middle">
                                            <input type="checkbox" class="bill-row-checkbox"
                                                data-bill-id="{{ $bill->id }}">
                                        </td>

                                        <td class="Id align-middle">
                                            <a href="{{ route('bill.show', \Crypt::encrypt($bill->id)) }}"
                                                class="btn btn-outline-primary bill-number">{{ AUth::user()->billNumberFormat($bill->bill_id) }}</a>
                                        </td>
                                        <td class="text-center align-middle">
                                            {{ optional($bill->vender)->name ?? '-' }}
                                        </td>

                                        {{-- <td class="text-end align-middle">{{ \Auth::user()->priceFormat($billPaid) }}</td> --}}
                                        <td class="align-middle">{{ \Auth::user()->priceFormat($billDue) }}</td>
                                        {{-- <td class="bill-category align-middle">
                                            {{ !empty($bill->category) ? $bill->category->name : '-' }}</td> --}}
                                        <td class="bill-date align-middle">{{ Auth::user()->dateFormat($bill->bill_date) }}
                                        </td>
                                        <td class="bill-due-date align-middle">
                                            {{ Auth::user()->dateFormat($bill->due_date) }}</td>
                                        <td class="bill-status align-middle">
                                            @if ($bill->status == 0)
                                                <span
                                                    class="status_badge badge bg-secondary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$bill->status]) }}</span>
                                            @elseif($bill->status == 1)
                                                <span
                                                    class="status_badge badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$bill->status]) }}</span>
                                            @elseif($bill->status == 2)
                                                <span
                                                    class="status_badge badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$bill->status]) }}</span>
                                            @elseif($bill->status == 3)
                                                <span
                                                    class="status_badge badge bg-info p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$bill->status]) }}</span>
                                            @elseif($bill->status == 4)
                                                <span
                                                    class="status_badge badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$bill->status]) }}</span>
                                            @elseif($bill->status == 5)
                                                <span
                                                    class="status_badge badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$bill->status]) }}</span>
                                            @elseif($bill->status == 6)
                                                <span
                                                    class="status_badge badge bg-success p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$bill->status]) }}</span>
                                            @elseif($bill->status == 7)
                                                <span
                                                    class="status_badge badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$bill->status]) }}</span>
                                            @endif
                                        </td>

                                        <td class="align-middle">{{ number_format($billTotal, 2) }}</td>
                                        <td class="align-middle">{{ number_format($billDue, 2) }}</td>

                                        @if (Gate::check('edit bill') || Gate::check('delete bill') || Gate::check('show bill'))
                                            <td class="Action align-middle">
                                                <span>
                                                    @can('duplicate bill')
                                                        <div class="action-btn bg-primary ms-2">
                                                            {!! Form::open([
                                                                'method' => 'get',
                                                                'route' => ['bill.duplicate', $bill->id],
                                                                'id' => 'duplicate-form-' . $bill->id,
                                                            ]) !!}
                                                            <a href="#"
                                                                class="mx-3 btn btn-sm align-items-center bs-pass-para "
                                                                data-bs-toggle="tooltip"
                                                                data-original-title="{{ __('Duplicate') }}"
                                                                data-bs-toggle="tooltip" title="{{ __('Duplicate Bill') }}"
                                                                data-original-title="{{ __('Delete') }}"
                                                                data-confirm="You want to confirm this action. Press Yes to continue or Cancel to go back"
                                                                data-confirm-yes="document.getElementById('duplicate-form-{{ $bill->id }}').submit();">
                                                                <i class="ti ti-copy text-white"></i>
                                                                {!! Form::close() !!}
                                                            </a>
                                                        </div>
                                                    @endcan
                                                    @can('show bill')
                                                        <div class="action-btn bg-info ms-2">
                                                            <a href="{{ $bill->vender_id ? route('vender.show', \Crypt::encrypt($bill->vender_id)) : '#' }}"
                                                                class="mx-3 btn btn-sm align-items-center"
                                                                data-bs-toggle="tooltip" title="{{ __('Show Vendor') }}"
                                                                data-original-title="{{ __('Detail') }}">
                                                                <i class="ti ti-eye text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endcan
                                                    @can('edit bill')
                                                        <div class="action-btn bg-primary ms-2">
                                                            {{-- <a href="{{ route('bill.edit', \Crypt::encrypt($bill->id)) }}"
                                                                class="mx-3 btn btn-sm align-items-center"
                                                                data-bs-toggle="tooltip" title="Edit"
                                                                data-original-title="{{ __('Edit') }}">
                                                                <i class="ti ti-pencil text-white"></i>
                                                            </a> --}}
                                                            <a href="#" data-url="{{ route('bill.edit', \Crypt::encrypt($bill->id)) }}" 
                                                                data-ajax-popup="true" data-size="fullscreen"
                                                                data-bs-toggle="tooltip" title="Edit">
                                                                <i class="ti ti-pencil text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endcan
                                                    @can('delete bill')
                                                        <div class="action-btn bg-danger ms-2">
                                                            {!! Form::open([
                                                                'method' => 'DELETE',
                                                                'route' => ['bill.destroy', $bill->id],
                                                                'class' => 'delete-form-btn',
                                                                'id' => 'delete-form-' . $bill->id,
                                                            ]) !!}
                                                            <a href="#"
                                                                class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                                data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                                data-original-title="{{ __('Delete') }}"
                                                                data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                                data-confirm-yes="document.getElementById('delete-form-{{ $bill->id }}').submit();">
                                                                <i class="ti ti-trash text-white"></i>
                                                            </a>
                                                            {!! Form::close() !!}
                                                        </div>
                                                    @endcan
                                                </span>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach

                            </tbody>


                        </table>

                        <div>
                            {{ $bills->links() }}
                        </div>



                    </div>
                </div>
            </div>
        </div>
    </div>
    <style>
        .text-muted.small {
            font-size: 13px;
            color: #6c757d !important;
            padding-top: 8px;
        }
    </style>



    {{-- FULL SCREEN MODAL FOR PAYMENTS - QBO Style --}}
    <div class="modal fade" id="payBillModal" tabindex="-1" aria-labelledby="payBillModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content" style="background-color: #fff;">
                {{-- QBO Style Header --}}
                <div class="modal-header border-0 pb-0" style="background: #fff; padding: 16px 24px;">
                    <h4 class="modal-title fw-normal" id="payBillModalLabel" style="font-size: 24px; color: #333;">{{ __('Pay Bills') }}</h4>
                    <div class="ms-auto d-flex align-items-center gap-3">
                        <a href="#" class="text-success text-decoration-none d-flex align-items-center" style="font-size: 14px;">
                            <i class="ti ti-message-circle me-1"></i> {{ __('Give feedback') }}
                        </a>
                        <button type="button" class="btn btn-link p-0 text-muted" style="font-size: 20px;" data-bs-toggle="tooltip" title="{{ __('Help') }}">
                            <i class="ti ti-help-circle"></i>
                        </button>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="font-size: 12px;"></button>
                    </div>
                </div>

                {{-- FORM START --}}
                {{ Form::open(['route' => ['bill.bulk.payment'], 'method' => 'post', 'id' => 'bulkPaymentForm', 'enctype' => 'multipart/form-data', 'style' => 'display: contents;']) }}

                <div class="modal-body p-0" style="overflow-y: auto;">
                    {{-- QBO Style Transaction Controls Header --}}
                    <div class="px-4 py-3">
                        <div class="row align-items-end px-4 py-3" style="background: #ECEEF1;">
                            <div class="col-auto">
                                <label class="form-label text-muted mb-1" style="font-size: 12px;">{{ __('Payment account') }}</label>
                                <div class="position-relative">
                                    {{ Form::select('account_id', $accounts ?? [], null, [
                                        'class' => 'form-select',
                                        'placeholder' => __('Select an account'),
                                        'required' => true,
                                        'style' => 'min-width: 180px; font-size: 14px; border-color: #c0c0c0;'
                                    ]) }}
                                </div>
                            </div>

                            <div class="col-auto">
                                <label class="form-label text-muted mb-1" style="font-size: 12px;">{{ __('Payment date') }}</label>
                                {{ Form::date('date', now()->format('Y-m-d'), [
                                    'class' => 'form-control',
                                    'required' => true,
                                    'style' => 'min-width: 160px; font-size: 14px; border-color: #c0c0c0;'
                                ]) }}
                            </div>

                            <div class="col text-end">
                                <div class="text-muted text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">{{ __('TOTAL PAYMENT AMOUNT') }}</div>
                                <div id="modal-grand-total" class="fw-normal" style="font-size: 36px; color: #333;">$<span class="grand-total-value">0.00</span></div>
                            </div>
                        </div>
                    </div>

                    {{-- QBO Style Filters Section --}}
                    <div class="px-4 py-3 d-flex align-items-center justify-content-between" style="border-bottom: 1px solid #e9ecef;">
                        <div class="d-flex align-items-center gap-2">
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center" type="button" id="payBillFiltersDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 14px; border-color: #2ca01c; color: #2ca01c;">
                                    <i class="ti ti-filter me-1"></i> {{ __('Filters') }}
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="payBillFiltersDropdown">
                                    <li><a class="dropdown-item" href="#">{{ __('All Bills') }}</a></li>
                                    <li><a class="dropdown-item" href="#">{{ __('Overdue Only') }}</a></li>
                                    <li><a class="dropdown-item" href="#">{{ __('Due This Week') }}</a></li>
                                </ul>
                            </div>
                            <span class="badge rounded-pill" style="background: #e8e8e8; color: #333; font-weight: normal; font-size: 13px; padding: 6px 12px;">{{ __('Last 12 months') }}</span>
                        </div>
                        <button type="button" class="btn btn-link text-muted p-0" data-bs-toggle="tooltip" title="{{ __('Settings') }}">
                            <i class="ti ti-settings" style="font-size: 20px;"></i>
                        </button>
                    </div>

                    {{-- QBO Style Bills Table --}}
                    <div class="table-responsive px-4">
                        <table id="payModalTable" class="table table-hover mb-0" style="font-size: 14px;">
                            <thead>
                                <tr style="border-bottom: 2px solid #e0e0e0;">
                                    <th class="text-center" style="width: 40px; padding: 12px 8px;">
                                        <input type="checkbox" id="modalSelectAll" class="form-check-input" style="cursor: pointer;">
                                    </th>
                                    <th style="font-weight: 500; color: #6b6b6b; padding: 12px 8px;">{{ __('PAYEE') }}</th>
                                    <th style="font-weight: 500; color: #6b6b6b; padding: 12px 8px;">{{ __('REF NO.') }} <i class="ti ti-arrow-down" style="font-size: 12px;"></i></th>
                                    <th style="font-weight: 500; color: #6b6b6b; padding: 12px 8px;">{{ __('DUE DATE') }}</th>
                                    <th style="font-weight: 500; color: #6b6b6b; padding: 12px 8px;">{{ __('STATUS') }}</th>
                                    <th class="text-end" style="font-weight: 500; color: #6b6b6b; padding: 12px 8px;">{{ __('OPEN BALANCE') }}</th>
                                    <th class="text-center" style="font-weight: 500; color: #6b6b6b; padding: 12px 8px;">{{ __('CREDIT APPLIED') }}</th>
                                    <th class="text-center" style="font-weight: 500; color: #6b6b6b; padding: 12px 8px; width: 120px;">{{ __('PAYMENT') }}</th>
                                    <th class="text-end" style="font-weight: 500; color: #6b6b6b; padding: 12px 8px;">{{ __('TOTAL AMOUNT') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Populated dynamically by JS --}}
                            </tbody>
                            <tfoot>
                                <tr style="border-top: 2px solid #e0e0e0;">
                                    <td colspan="5" class="text-start" style="padding: 12px 8px;">
                                        <strong style="color: #333;">{{ __('Total payment') }}</strong>
                                    </td>
                                    <td class="text-end" style="padding: 12px 8px;"><strong class="modal-footer-open-balance" style="color: #333;">$0.00</strong></td>
                                    <td class="text-center" style="padding: 12px 8px;"><strong class="modal-footer-credit" style="color: #333;">$0.00</strong></td>
                                    <td class="text-center" style="padding: 12px 8px;"><strong class="modal-footer-payment" style="color: #333;">$0.00</strong></td>
                                    <td class="text-end" style="padding: 12px 8px;"><strong class="modal-footer-total" style="color: #333;">$0.00</strong></td>
                                </tr>
                                <tr>
                                    <td colspan="9" class="text-end" style="border: none; padding: 8px;">
                                        <small class="text-muted me-2">{{ __('First') }}</small>
                                        <small class="text-muted me-2">{{ __('Previous') }}</small>
                                        <small style="color: #333;">1 - <span class="total-bills-count">0</span> {{ __('of') }} <span class="total-bills-count">0</span></small>
                                        <small class="text-muted ms-2">{{ __('Next') }}</small>
                                        <small class="text-muted ms-2">{{ __('Last') }}</small>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- QBO Style Footer --}}
                <div class="modal-footer border-top d-flex justify-content-between align-items-center" style="background: #fff; padding: 16px 24px;">
                    <a href="#" class="text-success text-decoration-none" data-bs-dismiss="modal" style="font-size: 14px;">{{ __('Cancel') }}</a>
                    <div class="btn-group">
                        <button type="submit" id="modal-proceed-payment" class="btn btn-success px-4" style="background-color: #2ca01c; border-color: #2ca01c; font-size: 14px;">
                            {{ __('Schedule payment') }}
                        </button>
                        <button type="button" class="btn btn-success dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: #2ca01c; border-color: #2ca01c;">
                            <span class="visually-hidden">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#">{{ __('Pay now') }}</a></li>
                            <li><a class="dropdown-item" href="#">{{ __('Print checks') }}</a></li>
                        </ul>
                    </div>
                </div>
                {{ Form::close() }}
                {{-- FORM END --}}
            </div>
        </div>
    </div>
@endsection