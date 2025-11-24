@extends('layouts.admin')
@section('page-title')
    {{ __('Invoice Create') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoice.index') }}">{{ __('Invoice') }}</a></li>
    <li class="breadcrumb-item">{{ __('Invoice Create') }}</li>
@endsection
@push('script-page')
    <script src="{{ asset('js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('js/jquery.repeater.min.js') }}"></script>
    <script>
        var selector = "body";
        if ($(selector + " .repeater").length) {
            var $dragAndDrop = $("body .repeater tbody").sortable({
                handle: '.sort-handler'
            });
            var $repeater = $(selector + ' .repeater').repeater({
                initEmpty: false,
                defaultValues: {
                    'status': 1
                },
                show: function() {
                    $(this).slideDown();
                    var file_uploads = $(this).find('input.multi');
                    if (file_uploads.length) {
                        $(this).find('input.multi').MultiFile({
                            max: 3,
                            accept: 'png|jpg|jpeg',
                            max_size: 2048
                        });
                    }
                    if ($('.select2').length) {
                        $('.select2').select2();
                    }

                },
                hide: function(deleteElement) {
                    if (confirm('Are you sure you want to delete this element?')) {
                        $(this).slideUp(deleteElement);
                        $(this).remove();

                        var inputs = $(".amount");
                        var subTotal = 0;
                        for (var i = 0; i < inputs.length; i++) {
                            subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
                        }
                        $('.subTotal').html(subTotal.toFixed(2));
                        $('.totalAmount').html(subTotal.toFixed(2));
                    }
                },
                ready: function(setIndexes) {

                    $dragAndDrop.on('drop', setIndexes);
                },
                isFirstItemUndeletable: true
            });
            var value = $(selector + " .repeater").attr('data-value');
            if (typeof value != 'undefined' && value.length != 0) {
                value = JSON.parse(value);
                $repeater.setList(value);
            }

        }

        $(document).on('change', '#customer', function() {
            $('#customer_detail').removeClass('d-none');
            $('#customer_detail').addClass('d-block');
            $('#customer-box').removeClass('d-block');
            $('#customer-box').addClass('d-none');
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
                    if (data != '') {
                        $('#customer_detail').html(data);
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
        })

        $(document).on('change', '.item', function() {

            var iteams_id = $(this).val();
            var url = $(this).data('url');
            var el = $(this);

            $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': jQuery('#token').val()
                },
                data: {
                    'product_id': iteams_id
                },
                cache: false,
                success: function(data) {
                    var item = JSON.parse(data);
                    console.log(el.parent().parent().find('.quantity'))
                    $(el.parent().parent().find('.quantity')).val(1);
                    $(el.parent().parent().find('.price')).val(item.product.sale_price);
                    $(el.parent().parent().parent().find('.pro_description')).val(item.product
                        .description);
                    // $('.pro_description').text(item.product.description);

                    var taxes = '';
                    var tax = [];

                    var totalItemTaxRate = 0;

                    if (item.taxes == 0) {
                        taxes += '-';
                    } else {
                        for (var i = 0; i < item.taxes.length; i++) {
                            taxes += '<span class="badge bg-primary mt-1 mr-2">' + item.taxes[i].name +
                                ' ' + '(' + item.taxes[i].rate + '%)' + '</span>';
                            tax.push(item.taxes[i].id);
                            totalItemTaxRate += parseFloat(item.taxes[i].rate);
                        }
                    }
                    var itemTaxPrice = parseFloat((totalItemTaxRate / 100)) * parseFloat((item.product
                        .sale_price * 1));
                    $(el.parent().parent().find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));
                    $(el.parent().parent().find('.itemTaxRate')).val(totalItemTaxRate.toFixed(2));
                    $(el.parent().parent().find('.taxes')).html(taxes);
                    $(el.parent().parent().find('.tax')).val(tax);
                    $(el.parent().parent().find('.unit')).html(item.unit);
                    $(el.parent().parent().find('.discount')).val(0);

                    var inputs = $(".amount");
                    var subTotal = 0;
                    for (var i = 0; i < inputs.length; i++) {
                        subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
                    }

                    var totalItemPrice = 0;
                    var priceInput = $('.price');
                    for (var j = 0; j < priceInput.length; j++) {
                        totalItemPrice += parseFloat(priceInput[j].value);
                    }

                    var totalItemTaxPrice = 0;
                    var itemTaxPriceInput = $('.itemTaxPrice');
                    for (var j = 0; j < itemTaxPriceInput.length; j++) {
                        totalItemTaxPrice += parseFloat(itemTaxPriceInput[j].value);
                        $(el.parent().parent().find('.amount')).html(parseFloat(item.totalAmount) +
                            parseFloat(itemTaxPriceInput[j].value));
                    }

                    var totalItemDiscountPrice = 0;
                    var itemDiscountPriceInput = $('.discount');

                    for (var k = 0; k < itemDiscountPriceInput.length; k++) {

                        totalItemDiscountPrice += parseFloat(itemDiscountPriceInput[k].value);
                    }

                    $('.subTotal').html(totalItemPrice.toFixed(2));
                    $('.totalTax').html(totalItemTaxPrice.toFixed(2));
                    $('.totalAmount').html((parseFloat(totalItemPrice) - parseFloat(
                        totalItemDiscountPrice) + parseFloat(totalItemTaxPrice)).toFixed(2));


                },
            });
        });

        $(document).on('keyup', '.quantity', function() {
            var quntityTotalTaxPrice = 0;

            var el = $(this).parent().parent().parent().parent();

            var quantity = $(this).val();
            var price = $(el.find('.price')).val();
            var discount = $(el.find('.discount')).val();
            if (discount.length <= 0) {
                discount = 0;
            }

            var totalItemPrice = (quantity * price) - discount;

            var amount = (totalItemPrice);


            var totalItemTaxRate = $(el.find('.itemTaxRate')).val();
            var itemTaxPrice = parseFloat((totalItemTaxRate / 100) * (totalItemPrice));
            $(el.find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));

            $(el.find('.amount')).html(parseFloat(itemTaxPrice) + parseFloat(amount));

            var totalItemTaxPrice = 0;
            var itemTaxPriceInput = $('.itemTaxPrice');
            for (var j = 0; j < itemTaxPriceInput.length; j++) {
                totalItemTaxPrice += parseFloat(itemTaxPriceInput[j].value);
            }


            var totalItemPrice = 0;
            var inputs_quantity = $(".quantity");

            var priceInput = $('.price');
            for (var j = 0; j < priceInput.length; j++) {
                totalItemPrice += (parseFloat(priceInput[j].value) * parseFloat(inputs_quantity[j].value));
            }

            var inputs = $(".amount");

            var subTotal = 0;
            for (var i = 0; i < inputs.length; i++) {
                subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
            }

            $('.subTotal').html(totalItemPrice.toFixed(2));
            $('.totalTax').html(totalItemTaxPrice.toFixed(2));

            $('.totalAmount').html((parseFloat(subTotal)).toFixed(2));

        })

        $(document).on('keyup change', '.price', function() {
            var el = $(this).parent().parent().parent().parent();
            var price = $(this).val();
            var quantity = $(el.find('.quantity')).val();

            var discount = $(el.find('.discount')).val();
            if (discount.length <= 0) {
                discount = 0;
            }
            var totalItemPrice = (quantity * price) - discount;

            var amount = (totalItemPrice);


            var totalItemTaxRate = $(el.find('.itemTaxRate')).val();
            var itemTaxPrice = parseFloat((totalItemTaxRate / 100) * (totalItemPrice));
            $(el.find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));

            $(el.find('.amount')).html(parseFloat(itemTaxPrice) + parseFloat(amount));

            var totalItemTaxPrice = 0;
            var itemTaxPriceInput = $('.itemTaxPrice');
            for (var j = 0; j < itemTaxPriceInput.length; j++) {
                totalItemTaxPrice += parseFloat(itemTaxPriceInput[j].value);
            }


            var totalItemPrice = 0;
            var inputs_quantity = $(".quantity");

            var priceInput = $('.price');
            for (var j = 0; j < priceInput.length; j++) {
                totalItemPrice += (parseFloat(priceInput[j].value) * parseFloat(inputs_quantity[j].value));
            }

            var inputs = $(".amount");

            var subTotal = 0;
            for (var i = 0; i < inputs.length; i++) {
                subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
            }

            $('.subTotal').html(totalItemPrice.toFixed(2));
            $('.totalTax').html(totalItemTaxPrice.toFixed(2));

            $('.totalAmount').html((parseFloat(subTotal)).toFixed(2));


        })

        $(document).on('keyup change', '.discount', function() {
            var el = $(this).parent().parent().parent();
            var discount = $(this).val();
            if (discount.length <= 0) {
                discount = 0;
            }

            var price = $(el.find('.price')).val();
            var quantity = $(el.find('.quantity')).val();
            var totalItemPrice = (quantity * price) - discount;


            var amount = (totalItemPrice);


            var totalItemTaxRate = $(el.find('.itemTaxRate')).val();
            var itemTaxPrice = parseFloat((totalItemTaxRate / 100) * (totalItemPrice));
            $(el.find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));

            $(el.find('.amount')).html(parseFloat(itemTaxPrice) + parseFloat(amount));

            var totalItemTaxPrice = 0;
            var itemTaxPriceInput = $('.itemTaxPrice');
            for (var j = 0; j < itemTaxPriceInput.length; j++) {
                totalItemTaxPrice += parseFloat(itemTaxPriceInput[j].value);
            }


            var totalItemPrice = 0;
            var inputs_quantity = $(".quantity");

            var priceInput = $('.price');
            for (var j = 0; j < priceInput.length; j++) {
                totalItemPrice += (parseFloat(priceInput[j].value) * parseFloat(inputs_quantity[j].value));
            }

            var inputs = $(".amount");

            var subTotal = 0;
            for (var i = 0; i < inputs.length; i++) {
                subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
            }


            var totalItemDiscountPrice = 0;
            var itemDiscountPriceInput = $('.discount');

            for (var k = 0; k < itemDiscountPriceInput.length; k++) {

                totalItemDiscountPrice += parseFloat(itemDiscountPriceInput[k].value);
            }


            $('.subTotal').html(totalItemPrice.toFixed(2));
            $('.totalTax').html(totalItemTaxPrice.toFixed(2));

            $('.totalAmount').html((parseFloat(subTotal)).toFixed(2));
            $('.totalDiscount').html(totalItemDiscountPrice.toFixed(2));




        })

        var customerId = '{{ $customerId }}';
        if (customerId > 0) {
            $('#customer').val(customerId).change();
        }
    </script>
    <script>
        $(document).on('click', '[data-repeater-delete]', function() {
            $(".price").change();
            $(".discount").change();
        });
    </script>
    <script>
        function toggleRecurringPanel() {
            const on = $('#recurring').val() === 'yes';
            $('#recurring-options').toggleClass('d-none', !on);

            // mark fields required when on
            $('#recurring_when, #recurring_start_date, #recurring_repeat, #recurring_every_n')
                .prop('required', on);

            // default behavior for "when to charge"
            handleWhenToCharge();
            handleEndType();
        }

        function handleWhenToCharge() {
            const when = $('#recurring_when').val();
            if (when === 'now') {
                const today = new Date().toISOString().slice(0, 10);
                $('#recurring_start_date').val(today).prop('disabled', true);
                $('#start-required').addClass('d-none');
            } else {
                $('#recurring_start_date').prop('disabled', false);
                // visual "Required" hint if empty
                if (!$('#recurring_start_date').val()) {
                    $('#start-required').removeClass('d-none');
                } else {
                    $('#start-required').addClass('d-none');
                }
            }
        }

        function handleEndType() {
            const type = $('#recurring_end_type').val();
            if (type === 'by') {
                $('#end-by-wrap').removeClass('d-none');
                $('#recurring_end_date').prop('required', true);
            } else {
                $('#end-by-wrap').addClass('d-none');
                $('#recurring_end_date').prop('required', false).val('');
            }
        }

        // init + listeners
        $(document).on('change', '#recurring', toggleRecurringPanel);
        $(document).on('change', '#recurring_when', handleWhenToCharge);
        $(document).on('change keyup', '#recurring_start_date', handleWhenToCharge);
        $(document).on('change', '#recurring_end_type', handleEndType);

        // run once in case of validation errors returning to page
        $(function() {
            toggleRecurringPanel();
        });
    </script>
<script>
  /** ===== Minimal Schedule Preview =====
   * Renders:
   *  - under Start date:  "Next Invoice Date YYYY-MM-DD."
   *  - under Repeat:      "Last invoice date YYYY-MM-DD"
   *
   * Rules:
   *  - "Next invoice date" is ALWAYS start date + 12 months (1 year)
   *    (independent of the selected repeat) — per the example:
   *      start = 2025-01-01 -> next = 2026-01-01 even if user switches to "monthly".
   *  - "Last invoice date" uses the selected repeat interval and count
   *    (e.g., monthly/quarterly/6months/yearly with every_n = count).
   */

  // --- Helpers ---
  function addMonthsNoOverflow(date, months) {
    const d = new Date(date.getTime());
    const day = d.getDate();
    d.setDate(1);
    d.setMonth(d.getMonth() + months);
    // snap to last day if original day doesn't exist in target month
    const lastDay = new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate();
    d.setDate(Math.min(day, lastDay));
    return d;
  }

  function toISO(d) {
    // Format as YYYY-MM-DD (avoid TZ drift)
    const tz = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
    return tz.toISOString().slice(0, 10);
  }

  function monthsForRepeat(repeat) {
    switch (repeat) {
      case 'monthly':   return 1;
      case 'quarterly': return 3;  // typical calendar quarterly = 3 months
      case '6months':   return 6;
      case 'yearly':    return 12;
      default:          return 1;
    }
  }

  function ensurePreviewHolders() {
    // small under Start date
    if (!$('#schedule-summary').length) {
      $('<small id="schedule-summary" class="text-muted d-block mt-1"></small>')
        .insertAfter('#recurring_start_date');
    }
    // small under Repeat
    if (!$('#schedule-preview').length) {
      $('<small id="schedule-preview" class="text-muted d-block mt-1"></small>')
        .insertAfter('#recurring_repeat');
    }
  }

  function computeSchedulePreview() {
    ensurePreviewHolders();

    const startVal = $('#recurring_start_date').val();   // e.g. "2025-01-01"
    const repeat   = $('#recurring_repeat').val();       // monthly|quarterly|6months|yearly
    const everyRaw = $('#recurring_every_n').val();      // count
    let count = parseInt(everyRaw, 10);

    if (!startVal) {
      $('#schedule-summary').text('');
      $('#schedule-preview').text('');
      return;
    }
    if (isNaN(count) || count < 1) count = 1;

    const start = new Date(startVal + 'T00:00:00');


    // --- Last invoice date: based on repeat + count ---
    // If count = 1 -> last = start; otherwise add (count - 1) * interval
    const stepMonths = monthsForRepeat(repeat);
        // --- Next invoice date: ALWAYS start + 12 months (1 year) ---
    const nextDate = addMonthsNoOverflow(start, stepMonths);
    $('#schedule-summary').text('Next Invoice Date ' + toISO(nextDate) + '.');
    let lastDate = new Date(start.getTime());
    if (count > 1) {
      lastDate = addMonthsNoOverflow(start, stepMonths * (count));
    }
    $('#schedule-preview').text('Last invoice date ' + toISO(lastDate));


    // --- Next invoice date: based on start + 12 months ---
    
  }

  // Hook into changes on only the relevant fields (simple version)
  $(document).on('change keyup',
    '#recurring_start_date, #recurring_repeat, #recurring_every_n',
    computeSchedulePreview
  );

  // Init on load
  $(function () {
    computeSchedulePreview();
  });
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

                    // z-index stacking
                    var zIndex = 1070 + ($('.modal:visible').length * 10);
                    $modal.css('z-index', zIndex);
                    setTimeout(function() {
                        $('.modal-backdrop').last().css('z-index', zIndex - 1).addClass(
                            'modal-stack');
                    }, 0);

                    $modal.modal('show');
                });

                $modal.on('hidden.bs.modal', function() {
                    $modal.remove();
                });
            }

            // Detect "Add New" selection
            $(document).on('change', 'select', function() {
                var $select = $(this);
                if ($select.val() === '__add__') {
                    openAddNewModal($select);
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
                            // 🔹 Insert new option before the "Add New" of the same select
                            var $addNewOption = $select.find('option[value="__add__"]').first();
                            var $newOption = $('<option>', {
                                value: response.data.id,
                                text: response.data.name
                            });

                            if ($addNewOption.length) {
                                $select.append($newOption);
                                // $newOption.insertBefore($addNewOption);
                            } else {
                                $select.append($newOption);
                            }

                            $select.val(response.data.id).trigger('change');
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
@endpush
@section('content')
    <div class="row">
        {{ Form::open(['url' => 'invoice', 'class' => 'w-100']) }}
        <div class="col-12">
            <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <div class="form-group" id="customer-box">
                                {{ Form::label('customer_id', __('Customer'), ['class' => 'form-label']) }}
                                {{ Form::select('customer_id', $customers, $customerId ?? '', [
                                    'class' => 'form-control select',
                                    'id' => 'customer',
                                    'data-url' => route('invoice.customer'), // you already have this
                                    'required' => 'required',
                                    'data-create-url' => route('customer.create'),
                                    'data-create-title' => __('Create New Customer'),
                                ]) }}
                            </div>

                            <a href="#" data-size="lg" id="launchAddCustomer"
                                data-url="{{ route('customer.create') }}" data-ajax-popup="true" data-bs-toggle="tooltip"
                                title="{{ __('Create') }}" data-title="{{ __('Create New Customer') }}" class="d-none">
                            </a>

                            <div id="customer_detail" class="d-none">
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {{ Form::label('issue_date', __('Issue Date'), ['class' => 'form-label']) }}
                                        <div class="form-icon-user">
                                            {{ Form::date('issue_date', null, ['class' => 'form-control', 'required' => 'required']) }}

                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {{ Form::label('due_date', __('Due Date'), ['class' => 'form-label']) }}
                                        <div class="form-icon-user">
                                            {{ Form::date('due_date', null, ['class' => 'form-control', 'required' => 'required']) }}

                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {{ Form::label('invoice_number', __('Invoice Number'), ['class' => 'form-label']) }}
                                        <div class="form-icon-user">
                                            <input type="text" class="form-control" value="{{ $invoice_number }}"
                                                readonly>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {{ Form::label('category_id', __('Category'), ['class' => 'form-label']) }}
                                        {{ Form::select('category_id', $category, null, ['class' => 'form-control select', 'id' => 'category_id', 'data-create-url' => route('product-category.create'), 'data-create-title' => __('Create New Category')]) }}
                                    </div>

                                    {{-- Hidden anchor that triggers your existing AJAX modal --}}
                                    <a href="#" id="launchAddCategory"
                                        data-url="{{ route('product-category.create') }}" data-ajax-popup="true"
                                        data-bs-toggle="tooltip" title="{{ __('Create') }}"
                                        data-title="{{ __('Create New Category') }}" class="d-none">
                                    </a>

                                    {{--                                <div class="col-md-6"> --}}
                                    {{--                                    <div class="form-check custom-checkbox mt-4"> --}}
                                    {{--                                        <input class="form-check-input" type="checkbox" name="discount_apply" id="discount_apply"> --}}
                                    {{--                                        <label class="form-check-label " for="discount_apply">{{__('Discount Apply')}}</label> --}}
                                    {{--                                    </div> --}}
                                    {{--                                </div> --}}
                                    {{--                                <div class="col-md-6"> --}}
                                    {{--                                    <div class="form-group"> --}}
                                    {{--                                        {{Form::label('sku',__('SKU')) }} --}}
                                    {{--                                        {!!Form::text('sku', null,array('class' => 'form-control','required'=>'required')) !!} --}}
                                    {{--                                    </div> --}}
                                    {{--                                </div> --}}
                                    @if (!$customFields->isEmpty())
                                        <div class="col-md-6">
                                            <div class="tab-pane fade show" id="tab-2" role="tabpanel">
                                                @include('customFields.formBuilder')
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        {{ Form::label('ref_number', __('Ref Number'), ['class' => 'form-label']) }}
                                        <div class="form-icon-user">
                                            <span><i class="ti ti-joint"></i></span>
                                            {{ Form::text('ref_number', '', ['class' => 'form-control', 'placeholder' => __('Enter Ref NUmber')]) }}
                                        </div>
                                    </div>
                                </div>
                                {{-- Recurring toggle --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {{ Form::label('recurring', __('Recurring'), ['class' => 'form-label']) }}
                                        <div class="form-icon-user">
                                            {{ Form::select('recurring', ['no' => 'No', 'yes' => 'Yes'], null, ['class' => 'form-control select', 'id' => 'recurring']) }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 d-none" id="recurring-options">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        {{ Form::label('recurring_when', __('When to charge'), ['class' => 'form-label']) }}
                                        {{ Form::select('recurring_when', ['future' => 'Select future date', 'now' => 'Immediately'], null, ['class' => 'form-control', 'id' => 'recurring_when']) }}
                                    </div>
                                </div>

                                {{-- Start date --}}
                                <div class="col-md-3">
                                    <div class="form-group">
                                        {{ Form::label('recurring_start_date', __('Start date'), ['class' => 'form-label']) }}
                                        <div class="form-icon-user">
                                            {{ Form::date('recurring_start_date', null, ['class' => 'form-control', 'id' => 'recurring_start_date']) }}
                                        </div>
                                        <small class="text-danger d-none" id="start-required">{{ __('Required') }}</small>
                                    </div>
                                </div>

                                {{-- Repeat frequency --}}
                                <div class="col-md-3">
                                    <div class="form-group">
                                        {{ Form::label('recurring_repeat', __('Repeat'), ['class' => 'form-label']) }}
                                        {{ Form::select(
                                            'recurring_repeat',
                                            [
                                                'monthly' => 'Monthly',
                                                'quarterly' => 'Quarterly',
                                                '6months' => '6 Months',
                                                'yearly' => 'Yearly',
                                            ],
                                            'monthly',
                                            ['class' => 'form-control', 'id' => 'recurring_repeat'],
                                        ) }}
                                        <small id="next-date-preview" class="text-muted d-block mt-1"></small>
                                    </div>
                                </div>

                                {{-- Every N months --}}
                                <div class="col-md-3">
                                    <div class="form-group">
                                        {{ Form::label('recurring_every_n', __('Invoice Count'), ['class' => 'form-label']) }}
                                        {{ Form::number('recurring_every_n', 1, ['class' => 'form-control', 'id' => 'recurring_every_n', 'min' => 1]) }}
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="col-12">
                <h5 class=" d-inline-block mb-4">{{ __('Product & Services') }}</h5>
                <div class="card repeater">
                    <div class="item-section py-2">
                        <div class="row justify-content-between align-items-center">
                            <div class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end">
                                <div class="all-button-box me-2">
                                    <a href="#" data-repeater-create="" class="btn btn-primary" data-bs-toggle="modal"
                                        data-target="#add-bank">
                                        <i class="ti ti-plus"></i> {{ __('Add item') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body table-border-style mt-2">
                        <div class="table-responsive">
                            <table class="table mb-0 table-custom-style" data-repeater-list="items" id="sortable-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Items') }}</th>
                                        <th>{{ __('Quantity') }}</th>
                                        <th>{{ __('Price') }} </th>
                                        <th>{{ __('Discount') }}</th>
                                        <th>{{ __('Tax') }} (%)</th>
                                        <th class="text-end">{{ __('Amount') }} <br><small
                                                class="text-danger font-weight-bold">{{ __('after tax & discount') }}</small>
                                        </th>
                                        <th></th>
                                    </tr>
                                </thead>

                                <tbody class="ui-sortable" data-repeater-item>
                                    <tr>

                                        <td width="25%" class="form-group pt-0">
                                            {{ Form::select('item', $product_services, '', ['class' => 'form-control select2 item', 'data-url' => route('invoice.product'), 'required' => 'required']) }}
                                        </td>
                                        <td>
                                            <div class="form-group price-input input-group search-form">
                                                {{ Form::text('quantity', '', ['class' => 'form-control quantity', 'required' => 'required', 'placeholder' => __('Qty'), 'required' => 'required']) }}
                                                <span class="unit input-group-text bg-transparent"></span>
                                            </div>
                                        </td>


                                        <td>
                                            <div class="form-group price-input input-group search-form">
                                                {{ Form::text('price', '', ['class' => 'form-control price', 'required' => 'required', 'placeholder' => __('Price'), 'required' => 'required']) }}
                                                <span
                                                    class="input-group-text bg-transparent">{{ \Auth::user()->currencySymbol() }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-group price-input input-group search-form">
                                                {{ Form::text('discount', '', ['class' => 'form-control discount', 'required' => 'required', 'placeholder' => __('Discount')]) }}
                                                <span
                                                    class="input-group-text bg-transparent">{{ \Auth::user()->currencySymbol() }}</span>
                                            </div>
                                        </td>



                                        <td>
                                            <div class="form-group">
                                                <div class="input-group colorpickerinput">
                                                    <div class="taxes"></div>
                                                    {{ Form::hidden('tax', '', ['class' => 'form-control tax text-dark']) }}
                                                    {{ Form::hidden('itemTaxPrice', '', ['class' => 'form-control itemTaxPrice']) }}
                                                    {{ Form::hidden('itemTaxRate', '', ['class' => 'form-control itemTaxRate']) }}
                                                </div>
                                            </div>
                                        </td>

                                        <td class="text-end amount">0.00</td>
                                        <td>
                                            <a href="#"
                                                class="ti ti-trash text-white repeater-action-btn bg-danger ms-2 bs-pass-para"
                                                data-repeater-delete></a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <div class="form-group">
                                                {{ Form::textarea('description', null, ['class' => 'form-control pro_description', 'rows' => '2', 'placeholder' => __('Description')]) }}
                                            </div>
                                        </td>
                                        <td colspan="5"></td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td></td>
                                        <td><strong>{{ __('Sub Total') }} ({{ \Auth::user()->currencySymbol() }})</strong>
                                        </td>
                                        <td class="text-end subTotal">0.00</td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td></td>
                                        <td><strong>{{ __('Discount') }} ({{ \Auth::user()->currencySymbol() }})</strong>
                                        </td>
                                        <td class="text-end totalDiscount">0.00</td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td></td>
                                        <td><strong>{{ __('Tax') }} ({{ \Auth::user()->currencySymbol() }})</strong>
                                        </td>
                                        <td class="text-end totalTax">0.00</td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td class="blue-text"><strong>{{ __('Total Amount') }}
                                                ({{ \Auth::user()->currencySymbol() }})</strong></td>
                                        <td class="text-end totalAmount blue-text">0.00</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <input type="button" value="{{ __('Cancel') }}"
                    onclick="location.href = '{{ route('invoice.index') }}';" class="btn btn-light">
                <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
            </div>
            {{ Form::close() }}

        </div>
    @endsection
