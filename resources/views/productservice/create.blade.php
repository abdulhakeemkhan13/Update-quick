{{ Form::open(['url' => 'productservice', 'enctype' => 'multipart/form-data']) }}
<div class="modal-body">
    {{-- start for ai module --}}
    @php
        $plan = \App\Models\Utility::getChatGPTSettings();
    @endphp

    <style>
        .modal-backdrop.show:nth-of-type(2) {
            z-index: 1060;
        }

        #unitCreateModal {
            z-index: 1080;
            /* second modal upar rahe */
        }
    </style>

    @if ($plan->chatgpt == 1)
        <div class="text-end">
            <a href="#" data-size="md" class="btn  btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                data-url="{{ route('generate', ['productservice']) }}" data-bs-placement="top"
                data-title="{{ __('Generate content with AI') }}">
                <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
            </a>
        </div>
    @endif
    {{-- end for ai module --}}
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('name', __('Name'), ['class' => 'form-label']) }}<span class="text-danger">*</span>
                {{ Form::text('name', '', ['class' => 'form-control', 'required' => 'required']) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('sku', __('SKU'), ['class' => 'form-label']) }}<span class="text-danger">*</span>
                {{ Form::text('sku', '', ['class' => 'form-control', 'required' => 'required']) }}
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('sale_price', __('Sale Price'), ['class' => 'form-label']) }}<span
                    class="text-danger">*</span>
                {{ Form::number('sale_price', '', ['class' => 'form-control', 'required' => 'required', 'step' => '0.01']) }}
            </div>
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('sale_chartaccount_id', __('Income Account'), ['class' => 'form-label']) }}
            <select name="sale_chartaccount_id" class="form-control" required="required" data-create-url="{{ route('chart-of-account.create') }}" data-create-title="{{ __('Create New Account') }}">
                <option value="add_new">➕  Add New</option>
                <option value="0" selected>Select Account</option>                
                @foreach ($incomeChartAccounts as $key => $chartAccount)
                    <option value="{{ $key }}" class="subAccount">{{ $chartAccount }}</option>
                    @foreach ($incomeSubAccounts as $subAccount)
                        @if ($key == $subAccount['account'])
                            <option value="{{ $subAccount['id'] }}" class="ms-5"> &nbsp; &nbsp;&nbsp;
                                {{ $subAccount['code_name'] }}</option>
                        @endif
                    @endforeach
                @endforeach
          
            </select>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('purchase_price', __('Purchase Price'), ['class' => 'form-label']) }}<span
                    class="text-danger">*</span>
                {{ Form::number('purchase_price', '', ['class' => 'form-control', 'required' => 'required', 'step' => '0.01']) }}
            </div>
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('expense_chartaccount_id', __('Expense Account'), ['class' => 'form-label']) }}
            <select name="expense_chartaccount_id" class="form-control" required="required" data-create-url="{{ route('chart-of-account.create') }}" data-create-title="{{ __('Create New Account') }}">
                <option value="add_new">➕  Add New</option>
                <option value="0" selected>Select Account</option>                
                @foreach ($expenseChartAccounts as $key => $chartAccount)
                    <option value="{{ $key }}" class="subAccount">{{ $chartAccount }}</option>
                    @foreach ($expenseSubAccounts as $subAccount)
                        @if ($key == $subAccount['account'])
                            <option value="{{ $subAccount['id'] }}" class="ms-5"> &nbsp; &nbsp;&nbsp;
                                {{ $subAccount['code_name'] }}</option>
                        @endif
                    @endforeach
                @endforeach
              
            </select>
        </div>

        <div class="form-group col-md-6">
            {{ Form::label('tax_id', __('Tax'), ['class' => 'form-label']) }}
            {{ Form::select('tax_id[]',   $tax->toArray(), null, ['class' => 'form-control select2', 'data-create-url' => route('taxes.create'), 'data-create-title' => __('Create New Tax')]) }}
            <!-- {{ Form::select('tax_id[]', $tax, null, ['class' => 'form-control select2', 'id' => 'choices-multiple1', 'multiple']) }} -->
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('category_id', __('Category'), ['class' => 'form-label']) }}<span class="text-danger">*</span>
            {{ Form::select('category_id', $category->toArray() , null, ['class' => 'form-control select', 'required' => 'required',  'data-create-url' => route('product-category.create'), 'data-create-title' => __('Create New Category')]) }}

        </div>
        <div class="form-group col-md-6">
            {{ Form::label('unit_id', __('Unit'), ['class' => 'form-label']) }}<span class="text-danger">*</span>
            {{ Form::select('unit_id',  $unit->toArray(), null, ['class' => 'form-control select', 'required' => 'required',  'data-create-url' => route('product-unit.create'), 'data-create-title' => __('Create New Unit')]) }}
        </div>
        <div class="col-md-6 form-group">
            {{ Form::label('pro_image', __('Product Image'), ['class' => 'form-label']) }}
            <div class="choose-file ">
                <label for="pro_image" class="form-label">
                    <input type="file" class="form-control file-validate" name="pro_image" id="pro_image"
                        data-filename="pro_image_create">
                    <p id="" class="file-error text-danger"></p>
                    <img id="image" class="mt-3" style="width:25%;" />
                </label>
            </div>
        </div>



        <div class="col-md-6">
            <div class="form-group">
                <div class="btn-box">
                    <label class="d-block form-label">{{ __('Type') }}</label>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check form-check-inline">
                                <input type="radio" class="form-check-input type" id="customRadio5" name="type"
                                    value="product" checked="checked">
                                <label class="custom-control-label form-label"
                                    for="customRadio5">{{ __('Product') }}</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-check-inline">
                                <input type="radio" class="form-check-input type" id="customRadio6" name="type"
                                    value="service">
                                <label class="custom-control-label form-label"
                                    for="customRadio6">{{ __('Service') }}</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group col-md-6 quantity">
            {{ Form::label('quantity', __('Quantity'), ['class' => 'form-label']) }}<span class="text-danger">*</span>
            {{ Form::text('quantity', null, ['class' => 'form-control']) }}
        </div>

        <div class="form-group col-md-12">
            {{ Form::label('description', __('Description'), ['class' => 'form-label']) }}
            {!! Form::textarea('description', null, ['class' => 'form-control', 'rows' => '2']) !!}
        </div>

        @if (!$customFields->isEmpty())
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="tab-pane fade show" id="tab-2" role="tabpanel">
                    @include('customFields.formBuilder')
                </div>
            </div>
        @endif
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
</div>
{{ Form::close() }}


<script>
    document.getElementById('pro_image').onchange = function() {
        var src = URL.createObjectURL(this.files[0])
        document.getElementById('image').src = src
    }

    //hide & show quantity

    $(document).on('click', '.type', function() {
        var type = $(this).val();
        if (type == 'product') {
            $('.quantity').removeClass('d-none')
            $('.quantity').addClass('d-block');
        } else {
            $('.quantity').addClass('d-none')
            $('.quantity').removeClass('d-block');
        }
    });



$(document).ready(function() {
    var currentSelect = null;

    function openAddNewModal($select) {
        if ($select.val() !== 'add_new') return;
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
                $('.modal-backdrop').last().css('z-index', zIndex - 1).addClass('modal-stack');
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
        if ($select.val() === 'add_new') {
            openAddNewModal($select);
        }
    });

    // AJAX submit for dynamic modal
    $(document).off('submit', '#globalAddNewModal form').on('submit', '#globalAddNewModal form', function(e) {
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
                    var $addNewOption = $select.find('option[value="add_new"]').first();
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
                        $form.find('[name="'+key+'"]').after(`<small class="invalid-feedback text-danger">${msgs[0]}</small>`);
                    });
                } else {
                    alert('Server error!');
                }
            }
        });
    });

});


</script>
