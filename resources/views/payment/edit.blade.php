{{ Form::model($payment, ['route' => ['payment.update', $payment->id], 'method' => 'PUT', 'enctype' => 'multipart/form-data']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-6">
            {{ Form::label('vender_id', __('Vendor'), ['class' => 'form-label']) }}
            @php
                $vendorOptions =
                    [
                        '__add__' => '➕ ' . __('Add New Vendor'),
                        '' => __('Select Vendor'),
                    ] + $venders->toArray();
            @endphp
            {{ Form::select('vender_id', $vendorOptions, null, [
                'class' => 'form-control select',
                'required' => 'required',
                'data-create-url' => route('vender.create'),
                'data-create-title' => __('Create New Vendor'),
            ]) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('date', __('Date'), ['class' => 'form-label']) }}
            {{ Form::date('date', null, ['class' => 'form-control', 'required' => 'required']) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('amount', __('Amount'), ['class' => 'form-label']) }}
            {{ Form::number('amount', null, ['class' => 'form-control', 'required' => 'required', 'step' => '0.01']) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('category_id', __('Category'), ['class' => 'form-label']) }}
            @php
                $categoryOptions =
                    [
                        '__add__' => '➕ ' . __('Add New Category'),
                        '' => __('Select Category'),
                    ] + $categories->toArray();
            @endphp
            {{ Form::select('category_id', $categoryOptions, null, [
                'class' => 'form-control select',
                'required' => 'required',
                'data-create-url' => route('product-category.create'),
                'data-create-title' => __('Create New Category'),
            ]) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('account_id', __('Account'), ['class' => 'form-label']) }}
            @php
                $accountOptions =
                    [
                        '__add__' => '➕ ' . __('Add New Account'),
                        '' => __('Select Account'),
                    ] + $accounts->toArray();
            @endphp
            {{ Form::select('account_id', $accountOptions, null, [
                'class' => 'form-control select',
                'required' => 'required',
                'data-create-url' => route('bank-account.create'),
                'data-create-title' => __('Create New Account'),
            ]) }}
        </div>

        {{--        <div class="form-group col-md-6"> --}}
        {{--            {{ Form::label('chart_account_id', __('Chart Of Account'),['class'=>'form-label']) }} --}}
        {{--            {{ Form::select('chart_account_id',$chartAccounts,null, array('class' => 'form-control select','required'=>'required')) }} --}}
        {{--        </div> --}}
        <div class="form-group col-md-6">
            {{ Form::label('reference', __('Reference'), ['class' => 'form-label']) }}
            {{ Form::text('reference', null, ['class' => 'form-control']) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('add_receipt', __('Payment Receipt'), ['class' => 'form-label']) }}
            {{ Form::file('add_receipt', ['class' => 'form-control', 'id' => 'files']) }}
            <img id="image" class="mt-2"
                src="{{ asset(Storage::url('uploads/payment')) . '/' . $payment->add_receipt }}" style="width:25%;" />
        </div>
        <div class="form-group  col-md-12">
            {{ Form::label('description', __('Description'), ['class' => 'form-label']) }}
            {{ Form::textarea('description', null, ['class' => 'form-control', 'rows' => 3]) }}
        </div>

    </div>
</div>

<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
</div>
{{ Form::close() }}


<script>
    document.getElementById('files').onchange = function() {
        var src = URL.createObjectURL(this.files[0])
        document.getElementById('image').src = src
    }
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
