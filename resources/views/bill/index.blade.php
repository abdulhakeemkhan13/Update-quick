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
                    var category = $orig.find('.bill-category').text().trim();
                    var billDate = $orig.find('.bill-date').text().trim();
                    var dueDate = $orig.find('.bill-due-date').text().trim();
                    var statusHtml = $orig.find('.bill-status').html();
                    var billAmount = parseFloat($orig.data('bill-amount')) || 0;
                    var openBalance = parseFloat($orig.data('bill-due')) || 0;

                    // default selected if was checked on main table
                    var selected = selectedIds.indexOf(billId) !== -1;

                    var checkedAttr = selected ? 'checked' : '';
                    var paymentValue = selected ? (openBalance).toFixed(2) : '0.00';
                    var totalValue = selected ? (openBalance).toFixed(2) : '0.00';
                    var partialLabel = (selected && parseFloat(paymentValue) < parseFloat(
                            totalValue)) ?
                        '<span class="badge bg-warning small ms-2">Partially Paid</span>' : '';

                    // var tr = '<tr data-bill-id="' + billId + '" data-bill-amount="' + billAmount +
                    //     '" data-bill-due="' + openBalance + '">' +
                    //     '<td class="text-center align-middle"><input name="bill_ids[]" value="'+billId+'" type="checkbox" class="modal-row-checkbox" ' +
                    //     checkedAttr + '></td>' +
                    //     '<td class="align-middle">' + billNumber + '</td>' +
                    //     '<td class="align-middle">' + category + '</td>' +
                    //     '<td class="align-middle">' + billDate + '</td>' +
                    //     '<td class="align-middle">' + dueDate + '</td>' +
                    //     '<td class="align-middle">' + statusHtml + '</td>' +
                    //     '<td class="align-middle text-end bill-amount-col">' + billAmount.toFixed(
                    //         2) + '</td>' +
                    //     '<td class="align-middle text-end bill-open-col">' + openBalance.toFixed(
                    //         2) + '</td>' +
                    //     '<td class="align-middle text-end total-col">' + totalValue + '</td>' +
                    //     '<td class="align-middle text-end payment-col"><input type="number" step="0.01" min="0" class="form-control form-control-sm payment-input" value="' +
                    //     paymentValue + '" name="payment_amounts[' + billId + ']"><small class="partial-label-container">' + partialLabel +
                    //     '</small></td>' +
                    //     '</tr>';
                    var tr = '<tr data-bill-id="' + billId + '" data-bill-amount="' + billAmount +
                        '" data-bill-due="' + openBalance + '">' +
                        '<td class="text-center align-middle"><input name="bill_ids[]" value="' +
                        billId + '" type="checkbox" class="modal-row-checkbox" ' +
                        checkedAttr + '></td>' +
                        '<td class="align-middle">' + billNumber + '</td>' +
                        '<td class="align-middle">' + category + '</td>' +
                        '<td class="align-middle">' + billDate + '</td>' +
                        '<td class="align-middle">' + dueDate + '</td>' +
                        '<td class="align-middle">' + statusHtml + '</td>' +
                        '<td class="align-middle text-end bill-amount-col">' + billAmount.toFixed(
                            2) + '</td>' +
                        '<td class="align-middle text-end bill-open-col">' + openBalance.toFixed(
                            2) + '</td>' +
                        '<td class="align-middle text-end payment-col">' +
                        '<input type="number" step="0.01" min="0" class="form-control form-control-sm payment-input" ' +
                        'value="' + paymentValue + '" name="payment_amounts[' + billId + ']">' +
                        '<small class="partial-label-container">' + partialLabel + '</small>' +
                        '</td>' +
                        '<td class="align-middle text-end total-col">' + totalValue + '</td>' +
                        '</tr>';


                    $modalTableBody.append(tr);
                });

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
                    $tr.find('.total-col').text(openBal.toFixed(2));
                } else {
                    // unselect row: set payment to 0 and total to 0
                    $paymentInput.val('0.00');
                    $tr.find('.total-col').text('0.00');
                    $tr.find('.partial-label-container').empty();
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
                    $tr.find('.total-col').text((parseFloat($tr.data('bill-due')) || 0).toFixed(2));
                } else {
                    $tr.find('.total-col').text('0.00');
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
                var grand = 0.00;
                $('#payModalTable tbody tr').each(function() {
                    var $tr = $(this);
                    var pay = parseFloat($tr.find('.payment-input').val()) || 0;
                    grand += pay;
                });

                // top right big total
                $('#modal-grand-total').text(parseFloat(grand).toFixed(2));

                // footer total row (sum of payment inputs)
                $('.modal-footer-total').text(parseFloat(grand).toFixed(2));
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

        {{-- Pay Bill Button --}}
        @can('edit bill')
            <button id="open-pay-modal" class="btn btn-sm btn-success" data-bs-toggle="tooltip" title="{{ __('Pay Bill') }}">
                <i class="ti ti-cash"></i> {{ __('Pay Bill') }}
            </button>
        @endcan
    </div>
@endsection


@section('content')
    {{-- tabs --}}
    @include('expense.expense-tabs')

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
                                                            <a href="{{ route('bill.show', \Crypt::encrypt($bill->id)) }}"
                                                                class="mx-3 btn btn-sm align-items-center"
                                                                data-bs-toggle="tooltip" title="{{ __('Show') }}"
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



    {{-- FULL SCREEN MODAL FOR PAYMENTS --}}
    <div class="modal fade" id="payBillModal" tabindex="-1" aria-labelledby="payBillModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header d-flex align-items-center" style="background:#f0f4f3;">
                    <h5 class="modal-title" id="payBillModalLabel">{{ __('Pay Selected Bills') }}</h5>
                    <div class="ms-auto d-flex align-items-center">
                        <button type="button" class="btn-close ms-3" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                </div>

                {{-- FORM START --}}
                {{ Form::open(['route' => ['bill.bulk.payment'], 'method' => 'post', 'id' => 'bulkPaymentForm', 'enctype' => 'multipart/form-data', 'style' => 'display: contents;']) }}

                <div class="modal-body p-3">
                    {{-- Transaction Controls --}}
                    <div class="row align-items-end m-1 mb-4 py-4 px-3" style="background:#f0f4f3; border-radius:8px;">
                        <div class="col-md-2">
                            {{ Form::label('date', __('Transaction Date'), ['class' => 'form-label']) }}
                            {{ Form::date('date', now()->format('Y-m-d'), ['class' => 'form-control', 'required' => true]) }}
                        </div>

                        <div class="col-md-2">
                            {{ Form::label('account_id', __('Account'), ['class' => 'form-label']) }}
                            {{ Form::select('account_id', $accounts ?? [], null, ['class' => 'form-control', 'placeholder' => __('Select Account'), 'required' => true]) }}
                        </div>

                        <div class="col-md-8 text-end">
                            <div class="text-muted">{{ __('Total Payment Amount') }}</div>
                            <div id="modal-grand-total" class="h1 mb-0" style="font-size: 3rem;">{{ __('0.00') }}
                            </div>
                        </div>
                    </div>

                    {{-- Bills Table --}}
                    <div class="table-responsive pt-3">
                        <table id="payModalTable" class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th class="text-center"><input type="checkbox" id="modalSelectAll"></th>
                                    <th>{{ __('Bill') }}</th>
                                    <th>{{ __('Category') }}</th>
                                    <th>{{ __('Bill Date') }}</th>
                                    <th>{{ __('Due Date') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th class="text-end">{{ __('Bill Amount') }}</th>
                                    <th class="text-end">{{ __('Open Balance') }}</th>
                                    <th class="text-end">{{ __('Payment') }}</th>
                                    <th class="text-end">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Populated dynamically by JS --}}
                                {{-- Example Row for JS reference:
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" name="bill_ids[]" value="123" class="modal-bill-checkbox">
                                </td>
                                <td>#BILL-123</td>
                                <td>Supplies</td>
                                <td>2025-11-02</td>
                                <td>2025-11-15</td>
                                <td><span class="badge bg-warning">Partial</span></td>
                                <td class="text-end">1,000.00</td>
                                <td class="text-end">300.00</td>
                                <td class="text-end">1,000.00</td>
                                <td class="text-end">
                                    <input type="number" name="payment_amounts[]" value="300.00" step="0.01" class="form-control form-control-sm text-end payment-input" required>
                                </td>
                            </tr>
                            --}}
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="6" class="text-end"><strong>{{ __('Total Payments') }}</strong></td>
                                    <td class="text-end"><strong class="modal-footer-total">0.00</strong></td>
                                    <td class="text-end"><strong class="modal-footer-total">0.00</strong></td>
                                    <td class="text-end"><strong class="modal-footer-total">0.00</strong></td>
                                    <td class="text-end"><strong class="modal-footer-total">0.00</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="modal-footer d-flex justify-content-between">
                    <div>
                        <small class="text-muted">
                            {{ __('Select bills and enter payment amounts before proceeding.') }}
                        </small>
                    </div>
                    <div class="d-flex align-items-center">
                        <button type="submit" id="modal-proceed-payment" class="btn btn-primary">
                            {{ __('Proceed Payment') }}
                        </button>
                    </div>
                </div>
                {{ Form::close() }}
                {{-- FORM END --}}
            </div>
        </div>
    </div>
@endsection