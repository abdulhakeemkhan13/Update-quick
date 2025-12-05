{{-- resources/views/terms/create-right.blade.php --}}
{{ Form::open(['route' => 'payment-terms.store', 'method' => 'post', 'id' => 'termForm', 'class' => 'term-drawer-wrapper h-100']) }}

    {{-- HEADER --}}
    <div class="term-drawer-header d-flex align-items-center justify-content-between border-bottom px-4 py-3">
        <h5 class="mb-0">{{ __('New Term') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    {{-- BODY --}}
    <div class="term-drawer-body">
        <div class="term-drawer-content p-4">

            {{-- Name Field --}}
            <div class="mb-4">
                {{ Form::label('name', __('Name (required)'), ['class' => 'form-label']) }}
                {{ Form::text('name', null, ['class' => 'form-control', 'required' => 'required', 'placeholder' => __('e.g. Net 30')]) }}
            </div>

            {{-- Type Selection --}}
            <div class="mb-4">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="type" id="type_fixed_days" value="fixed_days" checked>
                    <label class="form-check-label" for="type_fixed_days">
                        {{ __('Due in fixed number of days') }}
                    </label>
                </div>
                <div class="term-field-container ms-4 mb-3" id="fixed_days_field">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            {{ Form::number('due_in_days', 30, ['class' => 'form-control', 'style' => 'width: 80px;', 'min' => 0]) }}
                        </div>
                        <div class="col-auto">
                            <span class="text-muted">{{ __('days') }}</span>
                        </div>
                    </div>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="type" id="type_day_of_month" value="day_of_month">
                    <label class="form-check-label" for="type_day_of_month">
                        {{ __('Due by certain day of the month') }}
                    </label>
                </div>
                <div class="term-field-container ms-4 mb-3" id="day_of_month_field" style="display: none;">
                    <div class="row align-items-center mb-3">
                        <div class="col-auto">
                            {{ Form::number('day_of_month', 15, ['class' => 'form-control', 'style' => 'width: 80px;', 'min' => 1, 'max' => 31]) }}
                        </div>
                        <div class="col-auto">
                            <span class="text-muted">{{ __('day of month') }}</span>
                        </div>
                    </div>
                    
                    <div class="row align-items-center">
                        <div class="col-12 mb-2">
                            <span class="text-muted">{{ __('Due the next month if issued within') }}</span>
                        </div>
                        <div class="col-auto">
                            {{ Form::number('cutoff_days', null, ['class' => 'form-control', 'style' => 'width: 80px;', 'min' => 0]) }}
                        </div>
                        <div class="col-auto">
                            <span class="text-muted">{{ __('days of due date') }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- FOOTER --}}
    <div class="term-drawer-footer border-top d-flex justify-content-end gap-2 px-4 py-3">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="submit" class="btn btn-primary" style="background: #00892E !important; border: #00892E !important;">{{ __('Save') }}</button>
    </div>

{{ Form::close() }}

{{-- STYLES --}}
<style>
    /* Force modal to be right-aligned and full height */
    #commonModal .modal-dialog {
        position: fixed;
        margin: 0;
        top: 0;
        right: 0;
        bottom: 0;
        height: 100%;
        max-width: 450px;
        width: 100%;
        transform: translate3d(100%, 0, 0);
        transition: transform .3s ease-out;
    }

    #commonModal.show .modal-dialog {
        transform: translate3d(0, 0, 0);
    }

    #commonModal .modal-content {
        height: 100%;
        border-radius: 0;
        border: none;
        display: flex;
        flex-direction: column;
    }

    #commonModal .modal-body {
        padding: 0 !important;
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    /* Hide default header/footer if they exist */
    #commonModal .modal-header,
    #commonModal .modal-footer {
        display: none !important;
    }

    /* Wrapper for our form content */
    .term-drawer-wrapper {
        display: flex;
        flex-direction: column;
        height: 100%;
        width: 100%;
        position: relative;
    }

    .term-drawer-header {
        flex-shrink: 0;
        background-color: #fff;
        z-index: 10;
    }

    .term-drawer-header h5 {
        font-weight: 700;
        font-size: 20px;
        color: #6B6C72;
    }

    .term-drawer-body {
        flex: 1;
        overflow-y: auto;
        background-color: #fff;
        padding-bottom: 80px; /* Space for footer */
    }

    .term-drawer-footer {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background-color: #fff;
        z-index: 20;
        box-shadow: 0 -4px 12px rgba(0,0,0,0.05);
    }

    .term-field-container {
        padding: 10px 15px;
        background-color: #f9f9f9;
        border-radius: 4px;
    }

    body.theme-6 .form-check-input:checked {
        background-color: #00892E;
        border-color: #00892E;
    }

    body.theme-6 .form-check-input:focus,
    body.theme-6 .form-control:focus {
        border-color: #00892E;
        box-shadow: 0 0 0 0.2rem rgb(0, 137, 46, 0.25);
    }
</style>

{{-- SCRIPTS --}}
<script>
    (function ($) {
        "use strict";

        // Handle radio button changes to show/hide fields
        $('input[name="type"]').on('change', function() {
            var selectedType = $(this).val();
            
            // Hide all field containers
            $('#fixed_days_field, #day_of_month_field').hide();
            
            // Show the relevant field container
            if (selectedType === 'fixed_days') {
                $('#fixed_days_field').show();
            } else if (selectedType === 'day_of_month') {
                $('#day_of_month_field').show();
            }
        });

        // Trigger initial state
        $('input[name="type"]:checked').trigger('change');

    })(jQuery);
</script>
