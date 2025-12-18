{{-- resources/views/customer/create-right.blade.php --}}
{{ Form::open(['url' => 'customer', 'method' => 'post', 'id' => 'customerForm', 'class' => 'customer-drawer-wrapper h-100 d-flex flex-column']) }}

    {{-- HEADER (single, QBO-style) --}}
    <div class="customer-drawer-header d-flex align-items-center justify-content-between border-bottom px-4 py-3">
        <h5 class="mb-0">{{ __('Customer') }}</h5>

        <div class="d-flex align-items-center gap-2">

            {{-- header nav icons (scroll to sections) --}}
            <button type="button" class="customer-nav-btn active" data-target="#sec-basic"
                    title="{{ __('Name and contact') }}">
                <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class="" width="24px" height="24px" fill="currentColor"><path d="M17.862 2H7.138A3.142 3.142 0 004 5.138V6a1 1 0 000 2v3a1 1 0 000 2v3a1 1 0 000 2v.862A3.142 3.142 0 007.138 22h10.724A3.142 3.142 0 0021 18.862V5.138A3.142 3.142 0 0017.862 2zM19 18.862A1.14 1.14 0 0117.862 20H7.138A1.139 1.139 0 016 18.862V18a1 1 0 000-2v-3a1 1 0 000-2V8a1 1 0 000-2v-.862A1.14 1.14 0 017.138 4h10.724A1.14 1.14 0 0119 5.138v13.724z"></path><path d="M13.785 12.234c.05-.04.1-.086.151-.134.379-.38.591-.896.592-1.433v-.889a2.036 2.036 0 00-.6-1.436 2.078 2.078 0 00-2.869 0 2.03 2.03 0 00-.592 1.433v.89a2.03 2.03 0 00.743 1.566 2.03 2.03 0 00-1.632 1.988V15a1.252 1.252 0 001.25 1.25h3.334A1.254 1.254 0 0015.417 15v-.778a2.03 2.03 0 00-1.632-1.988z"></path></svg>
            </button>

            <button type="button" class="customer-nav-btn" data-target="#sec-addresses"
                    title="{{ __('Addresses') }}">
                <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class="" width="24px" height="24px" fill="currentColor"><path d="M12 14.5a4 4 0 110-8 4 4 0 010 8zm0-6a2 2 0 100 4 2 2 0 000-4z"></path><path d="M12 22a1 1 0 01-.858-.486L9.383 18.58a8.5 8.5 0 1110.97-9.68 8.454 8.454 0 01-5.737 9.681l-1.758 2.934A1 1 0 0112 22zm.018-18A6.493 6.493 0 0010.3 16.763c.251.068.466.23.6.453l1.1 1.838 1.1-1.838a1 1 0 01.6-.452 6.5 6.5 0 00-.4-12.638A6.801 6.801 0 0012.018 4z"></path></svg>
            </button>

            <button type="button" class="customer-nav-btn" data-target="#sec-notes"
                    title="{{ __('Notes & attachments') }}">
                <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class="" width="24px" height="24px" fill="currentColor"><path d="M21.013 10a1.024 1.024 0 00-1 1l-.02 8.014a1 1 0 01-1 1l-14-.02a1 1 0 01-1-1l.02-14a1 1 0 011-1L13 4.01a1 1 0 100-2l-7.984-.02H5.01a3 3 0 00-3 3l-.02 14a3 3 0 003 3l14 .02h.006a3 3 0 003-2.994L22.01 11a1 1 0 00-.997-1z"></path><path d="M8.975 10.885L8 14.755a1 1 0 001.212 1.215l3.873-.962a1 1 0 00.465-.262l7.756-7.732a2.373 2.373 0 000-3.35l-.962-.964A2.359 2.359 0 0018.67 2a2.348 2.348 0 00-1.672.69l-7.759 7.731a1 1 0 00-.264.464zm1.872.757l7.559-7.536a.369.369 0 01.521.001l.966.969a.374.374 0 010 .522l-7.559 7.536-1.986.494.499-1.986z"></path></svg>
            </button>

            <button type="button" class="customer-nav-btn" data-target="#sec-payments"
                    title="{{ __('Payments') }}">
                <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class="" width="24px" height="24px" fill="currentColor"><path d="M21.133 4.892a2.982 2.982 0 00-2.12-.882l-14-.02h-.005a3 3 0 00-3 3l-.015 10a3 3 0 003 3h2a1 1 0 100-2h-2a1 1 0 01-1-1l.009-6 16 .024-.009 6a1 1 0 01-1 1h-2a1 1 0 000 2H19a3 3 0 003-3l.015-10a2.981 2.981 0 00-.882-2.122zM4.006 8.988v-2a1 1 0 011-1l14 .02a1 1 0 011 1v2l-16-.02z"></path><path d="M15.707 15.293a1 1 0 00-1.414 0L12 17.586l-.793-.793a1 1 0 00-1.414 1.414l1.5 1.5a1 1 0 001.414 0l3-3a1 1 0 000-1.414z"></path></svg>
            </button>

            <button type="button" class="customer-nav-btn" data-target="#sec-additional"
                    title="{{ __('Additional info') }}">
                <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class="" width="24px" height="24px" fill="currentColor"><path d="M20.988 8.939a1 1 0 00-.054-.265.973.973 0 00-.224-.374v-.005l-6-6a1 1 0 00-.283-.191c-.031-.014-.064-.022-.1-.034a.992.992 0 00-.259-.052C14.042 2.011 14.023 2 14 2H6a3 3 0 00-3 3v14a3 3 0 003 3h12a3 3 0 003-3V9c0-.022-.011-.04-.012-.061zM15 5.414L17.586 8H16a1 1 0 01-1-1V5.414zM18 20H6a1 1 0 01-1-1V5a1 1 0 011-1h7v3a3 3 0 003 3h3v9a1 1 0 01-1 1z"></path><path d="M7 10h3a1 1 0 100-2H7a1 1 0 000 2zm7 3H7a1 1 0 000 2h7a1 1 0 000-2zm0 3H7a1 1 0 000 2h7a1 1 0 000-2z"></path></svg>
            </button>

            <button type="button" class="customer-nav-btn" data-target="#sec-custom-fields"
                    title="{{ __('Custom fields') }}">
                <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class="" width="24" height="24" fill="currentColor"><path d="M21.174 15.48l-3.5-3.5 1.768-1.767L20.857 8.8a3 3 0 000-4.243l-1.414-1.414a3 3 0 00-4.243 0l-1.414 1.414-1.767 1.768-3.5-3.5a2.18 2.18 0 00-1.31-.636 1.783 1.783 0 00-1.455.5l-3.029 3.03a1.964 1.964 0 00.138 2.764l3.5 3.5-2.476 2.474a.99.99 0 00-.226.374.694.694 0 00-.034.086l-1.418 5.66a1 1 0 001.213 1.214l5.657-1.414c.033-.01.062-.026.094-.037a1.002 1.002 0 00.367-.224l2.475-2.476 3.5 3.5c.398.405.94.636 1.509.643a1.76 1.76 0 001.255-.505l3.031-3.031a1.964 1.964 0 00-.136-2.767zm-4.56-10.923a1 1 0 011.415 0l1.414 1.414a1.001 1.001 0 010 1.415l-.707.707-2.829-2.83.707-.706zm-2.121 2.12l2.829 2.83-1.061 1.06-2.828-2.828 1.06-1.061zM7.776 13.4l2.824 2.824-1.764 1.768-2.828-2.828L7.776 13.4zm-3.222 6.05l.582-2.326 1.744 1.74-2.326.586zm12.418.31a.297.297 0 01-.041-.033l-1.378-1.377 1.415-1.415a1 1 0 00-1.414-1.414l-1.415 1.415-.707-.708.708-.707a1.002 1.002 0 00-1.414-1.421l-.708.707-.707-.707 1.415-1.414a1 1 0 10-1.414-1.414L9.9 12.689l-.707-.707.707-.707A1.001 1.001 0 008.483 9.86l-.707.707-.708-.708 1.415-1.414a1 1 0 00-1.414-1.414L5.654 8.446 4.277 7.069a.265.265 0 01-.033-.04l2.822-2.822a.2.2 0 01.04.034l4.2 4.2 4.238 4.238 4.2 4.2a.264.264 0 01.033.04l-2.805 2.842z"></path></svg>
            </button>

            <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
    </div>

    {{-- BODY (scrollable) --}}
    <div class="customer-drawer-body">
        <div class="customer-drawer-content p-4">

            <div class="accordion customer-accordion" id="customerAccordion">

                {{-- SECTION: NAME & CONTACT --}}
                <div id="sec-basic" class="accordion-item customer-section mb-3">
                    <h2 class="accordion-header" id="heading-basic">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapse-basic" aria-expanded="true" aria-controls="collapse-basic">
                            <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class="" width="24px" height="24px" fill="currentColor"><path d="M17.862 2H7.138A3.142 3.142 0 004 5.138V6a1 1 0 000 2v3a1 1 0 000 2v3a1 1 0 000 2v.862A3.142 3.142 0 007.138 22h10.724A3.142 3.142 0 0021 18.862V5.138A3.142 3.142 0 0017.862 2zM19 18.862A1.14 1.14 0 0117.862 20H7.138A1.139 1.139 0 016 18.862V18a1 1 0 000-2v-3a1 1 0 000-2V8a1 1 0 000-2v-.862A1.14 1.14 0 017.138 4h10.724A1.14 1.14 0 0119 5.138v13.724z"></path><path d="M13.785 12.234c.05-.04.1-.086.151-.134.379-.38.591-.896.592-1.433v-.889a2.036 2.036 0 00-.6-1.436 2.078 2.078 0 00-2.869 0 2.03 2.03 0 00-.592 1.433v.89a2.03 2.03 0 00.743 1.566 2.03 2.03 0 00-1.632 1.988V15a1.252 1.252 0 001.25 1.25h3.334A1.254 1.254 0 0015.417 15v-.778a2.03 2.03 0 00-1.632-1.988z"></path></svg><span style="padding-left:10px;"> {{ __('Name and contact') }} </span>
                        </button>
                    </h2>
                    <div id="collapse-basic" class="accordion-collapse collapse show"
                         aria-labelledby="heading-basic">
                        <div class="accordion-body">

                            <div class="row g-3">
                                {{-- Title / First / Middle / Last / Suffix --}}
                                <div class="col-md-2">
                                    {{ Form::label('title', __('Title'), ['class' => 'form-label']) }}
                                    {{ Form::text('title', null, ['class' => 'form-control', 'maxlength' => 16]) }}
                                </div>
                                <div class="col-md-3">
                                    {{ Form::label('first_name', __('First name'), ['class' => 'form-label']) }}
                                    {{ Form::text('first_name', null, ['class' => 'form-control', 'maxlength' => 100]) }}
                                </div>
                                <div class="col-md-3">
                                    {{ Form::label('middle_name', __('Middle name'), ['class' => 'form-label']) }}
                                    {{ Form::text('middle_name', null, ['class' => 'form-control', 'maxlength' => 100]) }}
                                </div>
                                <div class="col-md-3">
                                    {{ Form::label('last_name', __('Last name'), ['class' => 'form-label']) }}
                                    {{ Form::text('last_name', null, ['class' => 'form-control', 'maxlength' => 100]) }}
                                </div>
                                <div class="col-md-1">
                                    {{ Form::label('suffix', __('Suffix'), ['class' => 'form-label']) }}
                                    {{ Form::text('suffix', null, ['class' => 'form-control', 'maxlength' => 16]) }}
                                </div>
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    {{ Form::label('company_name', __('Company name'), ['class' => 'form-label']) }}
                                    {{ Form::text('company_name', null, ['class' => 'form-control']) }}
                                </div>
                                <div class="col-md-6">
                                    {{-- use existing "name" as Customer display name * --}}
                                    {{ Form::label('name', __('Customer display name').' *', ['class' => 'form-label']) }}
                                    {{ Form::text('name', null, [
                                        'class' => 'form-control',
                                        'required' => 'required',
                                    ]) }}
                                </div>
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    {{ Form::label('email', __('Email'), ['class' => 'form-label']) }}
                                    {{ Form::email('email', null, [
                                        'class' => 'form-control',
                                        'required' => 'required',
                                    ]) }}
                                    <small class="text-muted d-block mt-1">
                                        {{ __('In Bill Pay, this email is used to get payments.') }}
                                    </small>
                                </div>
                                <div class="col-md-6">
                                    {{-- map to existing "contact" column --}}
                                    {{ Form::label('contact', __('Phone number'), ['class' => 'form-label']) }}
                                    {{ Form::text('contact', null, [
                                        'class' => 'form-control',
                                        'maxlength' => 30,
                                    ]) }}
                                </div>
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    {{ Form::label('cc', __('Cc'), ['class' => 'form-label']) }}
                                    {{ Form::text('cc', null, ['class' => 'form-control', 'maxlength' => 200]) }}
                                </div>
                                <div class="col-md-6">
                                    {{ Form::label('bcc', __('Bcc'), ['class' => 'form-label']) }}
                                    {{ Form::text('bcc', null, ['class' => 'form-control', 'maxlength' => 200]) }}
                                </div>
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    {{ Form::label('mobile', __('Mobile number'), ['class' => 'form-label']) }}
                                    {{ Form::text('mobile', null, ['class' => 'form-control', 'maxlength' => 30]) }}
                                </div>
                                <div class="col-md-6">
                                    {{ Form::label('fax', __('Fax'), ['class' => 'form-label']) }}
                                    {{ Form::text('fax', null, ['class' => 'form-control', 'maxlength' => 30]) }}
                                </div>
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    {{ Form::label('other', __('Other'), ['class' => 'form-label']) }}
                                    {{ Form::text('other', null, ['class' => 'form-control', 'maxlength' => 30]) }}
                                </div>
                                <div class="col-md-6">
                                    {{ Form::label('website', __('Website'), ['class' => 'form-label']) }}
                                    {{ Form::url('website', null, ['class' => 'form-control', 'maxlength' => 1000]) }}
                                </div>
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    {{ Form::label('print_on_check_name', __('Name to print on checks'), ['class' => 'form-label']) }}
                                    {{ Form::text('print_on_check_name', null, ['class' => 'form-control', 'maxlength' => 110]) }}
                                </div>
                                {{-- <div class="col-md-6 d-flex align-items-end">
                                    <div class="form-check mt-3">
                                        {{ Form::checkbox('is_sub_customer', 1, false, [
                                            'class' => 'form-check-input',
                                            'id' => 'is_sub_customer',
                                        ]) }}
                                        <label class="form-check-label" for="is_sub_customer">
                                            {{ __('Is a sub-customer') }}
                                        </label>
                                    </div>
                                </div> --}}
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6 d-flex align-items-end">
                                    <div class="form-check mt-3">
                                        {{ Form::checkbox('is_sub_customer', 1, false, [
                                            'class' => 'form-check-input',
                                            'id' => 'is_sub_customer',
                                        ]) }}
                                        <label class="form-check-label" for="is_sub_customer">
                                            {{ __('Is a sub-customer') }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    {{ Form::label('tax_number', __('Tax Number'), ['class' => 'form-label']) }}
                                    {{ Form::text('tax_number', null, ['class' => 'form-control']) }}
                                </div>
                                <div class="col-md-6">
                                    {{ Form::label('ntn', __('NTN'), ['class' => 'form-label']) }}
                                    {{ Form::text('ntn', null, ['class' => 'form-control']) }}
                                </div>
                            </div> --}}

                        </div>
                    </div>
                </div>

                {{-- SECTION: ADDRESSES --}}
                <div id="sec-addresses" class="accordion-item customer-section mb-3">
                    <h2 class="accordion-header" id="heading-addresses">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapse-addresses" aria-expanded="true"
                                aria-controls="collapse-addresses">
                            <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class="" width="24px" height="24px" fill="currentColor"><path d="M12 14.5a4 4 0 110-8 4 4 0 010 8zm0-6a2 2 0 100 4 2 2 0 000-4z"></path><path d="M12 22a1 1 0 01-.858-.486L9.383 18.58a8.5 8.5 0 1110.97-9.68 8.454 8.454 0 01-5.737 9.681l-1.758 2.934A1 1 0 0112 22zm.018-18A6.493 6.493 0 0010.3 16.763c.251.068.466.23.6.453l1.1 1.838 1.1-1.838a1 1 0 01.6-.452 6.5 6.5 0 00-.4-12.638A6.801 6.801 0 0012.018 4z"></path></svg><span style="padding-left:10px;"> {{ __('Addresses') }} </span>
                        </button>
                    </h2>
                    <div id="collapse-addresses" class="accordion-collapse collapse show"
                         aria-labelledby="heading-addresses">
                        <div class="accordion-body">

                            {{-- Billing --}}
                            <div class="mb-3 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">{{ __('Billing address') }}</h6>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    {{ Form::label('billing_name', __('Name'), ['class' => 'form-label']) }}
                                    {{ Form::text('billing_name', null, ['class' => 'form-control']) }}
                                </div>
                                <div class="col-md-6">
                                    {{ Form::label('billing_phone', __('Phone'), ['class' => 'form-label']) }}
                                    {{ Form::text('billing_phone', null, ['class' => 'form-control']) }}
                                </div>

                                <div class="col-md-6">
                                    {{ Form::label('billing_address', __('Street address 1'), ['class' => 'form-label']) }}
                                    {{ Form::text('billing_address', null, ['class' => 'form-control', 'maxlength' => 255]) }}
                                </div>
                                <div class="col-md-6">
                                    {{ Form::label('billing_address_2', __('Street address 2'), ['class' => 'form-label']) }}
                                    {{ Form::text('billing_address_2', null, ['class' => 'form-control', 'maxlength' => 255]) }}
                                </div>

                                <div class="col-md-6">
                                    {{ Form::label('billing_city', __('City'), ['class' => 'form-label']) }}
                                    {{ Form::text('billing_city', null, ['class' => 'form-control']) }}
                                </div>
                                <div class="col-md-6">
                                    {{ Form::label('billing_state', __('State'), ['class' => 'form-label']) }}
                                    {{ Form::text('billing_state', null, ['class' => 'form-control']) }}
                                </div>
                                <div class="col-md-6">
                                    {{ Form::label('billing_zip', __('ZIP code'), ['class' => 'form-label']) }}
                                    {{ Form::text('billing_zip', null, ['class' => 'form-control']) }}
                                </div>
                                <div class="col-md-6">
                                    {{ Form::label('billing_country', __('Country'), ['class' => 'form-label']) }}
                                    {{ Form::text('billing_country', null, ['class' => 'form-control']) }}
                                </div>
                            </div>

                            @if(App\Models\Utility::getValByName('shipping_display')=='on')
                                <hr class="my-4">

                                {{-- Shipping --}}
                                <div class="d-flex flex-column justify-content-between gap-4 mb-3">
                                    <h6 class="mb-0">{{ __('Shipping address') }}</h6>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                               id="shipping_same_as_billing">
                                        <label class="form-check-label" for="shipping_same_as_billing">
                                            {{ __('Same as billing address') }}
                                        </label>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        {{ Form::label('shipping_name', __('Name'), ['class' => 'form-label']) }}
                                        {{ Form::text('shipping_name', null, ['class' => 'form-control']) }}
                                    </div>
                                    <div class="col-md-6">
                                        {{ Form::label('shipping_phone', __('Phone'), ['class' => 'form-label']) }}
                                        {{ Form::text('shipping_phone', null, ['class' => 'form-control']) }}
                                    </div>

                                    <div class="col-md-6">
                                        {{ Form::label('shipping_address', __('Street address 1'), ['class' => 'form-label']) }}
                                        {{ Form::text('shipping_address', null, ['class' => 'form-control', 'maxlength' => 255]) }}
                                    </div>
                                    <div class="col-md-6">
                                        {{ Form::label('shipping_address_2', __('Street address 2'), ['class' => 'form-label']) }}
                                        {{ Form::text('shipping_address_2', null, ['class' => 'form-control', 'maxlength' => 255]) }}
                                    </div>

                                    <div class="col-md-6">
                                        {{ Form::label('shipping_city', __('City'), ['class' => 'form-label']) }}
                                        {{ Form::text('shipping_city', null, ['class' => 'form-control']) }}
                                    </div>
                                    <div class="col-md-6">
                                        {{ Form::label('shipping_state', __('State'), ['class' => 'form-label']) }}
                                        {{ Form::text('shipping_state', null, ['class' => 'form-control']) }}
                                    </div>
                                    <div class="col-md-6">
                                        {{ Form::label('shipping_zip', __('ZIP code'), ['class' => 'form-label']) }}
                                        {{ Form::text('shipping_zip', null, ['class' => 'form-control']) }}
                                    </div>
                                    <div class="col-md-6">
                                        {{ Form::label('shipping_country', __('Country'), ['class' => 'form-label']) }}
                                        {{ Form::text('shipping_country', null, ['class' => 'form-control']) }}
                                    </div>
                                </div>
                            @endif

                        </div>
                    </div>
                </div>

                {{-- SECTION: NOTES & ATTACHMENTS --}}
                <div id="sec-notes" class="accordion-item customer-section mb-3">
                    <h2 class="accordion-header" id="heading-notes">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapse-notes" aria-expanded="true"
                                aria-controls="collapse-notes">
                            <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class="" width="24px" height="24px" fill="currentColor"><path d="M21.013 10a1.024 1.024 0 00-1 1l-.02 8.014a1 1 0 01-1 1l-14-.02a1 1 0 01-1-1l.02-14a1 1 0 011-1L13 4.01a1 1 0 100-2l-7.984-.02H5.01a3 3 0 00-3 3l-.02 14a3 3 0 003 3l14 .02h.006a3 3 0 003-2.994L22.01 11a1 1 0 00-.997-1z"></path><path d="M8.975 10.885L8 14.755a1 1 0 001.212 1.215l3.873-.962a1 1 0 00.465-.262l7.756-7.732a2.373 2.373 0 000-3.35l-.962-.964A2.359 2.359 0 0018.67 2a2.348 2.348 0 00-1.672.69l-7.759 7.731a1 1 0 00-.264.464zm1.872.757l7.559-7.536a.369.369 0 01.521.001l.966.969a.374.374 0 010 .522l-7.559 7.536-1.986.494.499-1.986z"></path></svg><span style="padding-left:10px;"> {{ __('Notes and attachments') }} </span>
                        </button>
                    </h2>
                    <div id="collapse-notes" class="accordion-collapse collapse show"
                         aria-labelledby="heading-notes">
                        <div class="accordion-body">

                            <div class="mb-3">
                                {{ Form::label('notes', __('Notes'), ['class' => 'form-label']) }}
                                {{ Form::textarea('notes', null, ['class' => 'form-control', 'rows' => 3]) }}
                            </div>

                            <div class="mb-2">
                                <label class="form-label d-block">{{ __('Attachments') }}</label>
                                <input type="file" class="form-control" multiple disabled>
                                <small class="text-muted">
                                    {{ __('(File upload handling can be wired later if needed.)') }}
                                </small>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- SECTION: PAYMENTS --}}
                <div id="sec-payments" class="accordion-item customer-section mb-3">
                    <h2 class="accordion-header" id="heading-payments">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapse-payments" aria-expanded="true"
                                aria-controls="collapse-payments">
                            <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class="" width="24px" height="24px" fill="currentColor"><path d="M21.133 4.892a2.982 2.982 0 00-2.12-.882l-14-.02h-.005a3 3 0 00-3 3l-.015 10a3 3 0 003 3h2a1 1 0 100-2h-2a1 1 0 01-1-1l.009-6 16 .024-.009 6a1 1 0 01-1 1h-2a1 1 0 000 2H19a3 3 0 003-3l.015-10a2.981 2.981 0 00-.882-2.122zM4.006 8.988v-2a1 1 0 011-1l14 .02a1 1 0 011 1v2l-16-.02z"></path><path d="M15.707 15.293a1 1 0 00-1.414 0L12 17.586l-.793-.793a1 1 0 00-1.414 1.414l1.5 1.5a1 1 0 001.414 0l3-3a1 1 0 000-1.414z"></path></svg><span style="padding-left:10px;"> {{ __('Payments') }} </span>
                        </button>
                    </h2>
                    <div id="collapse-payments" class="accordion-collapse collapse show"
                         aria-labelledby="heading-payments">
                        <div class="accordion-body">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    {{ Form::label('primary_payment_method', __('Primary payment method'), ['class' => 'form-label']) }}
                                    {{ Form::text('primary_payment_method', null, [
                                        'class' => 'form-control',
                                        'placeholder' => __('Select a primary payment method'),
                                    ]) }}
                                </div>

                                <div class="col-md-6">
                                    {{ Form::label('terms', __('Terms'), ['class' => 'form-label']) }}
                                    {{ Form::text('terms', null, ['class' => 'form-control']) }}
                                </div>
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    {{ Form::label('delivery_method', __('Sales form delivery options'), ['class' => 'form-label']) }}
                                    {{ Form::text('delivery_method', null, ['class' => 'form-control']) }}
                                </div>

                                <div class="col-md-6">
                                    {{ Form::label('lang', __('Language to use when you send invoices'), ['class' => 'form-label']) }}
                                    {{ Form::text('lang', null, [
                                        'class' => 'form-control',
                                        'placeholder' => __('English'),
                                    ]) }}
                                </div>
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    {{ Form::label('credit_limit', __('Credit Limit'), ['class' => 'form-label']) }}
                                    {{ Form::number('credit_limit', null, ['class' => 'form-control', 'step' => '0.01']) }}
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- SECTION: ADDITIONAL INFO --}}
                <div id="sec-additional" class="accordion-item customer-section mb-3">
                    <h2 class="accordion-header" id="heading-additional">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapse-additional" aria-expanded="true"
                                aria-controls="collapse-additional">
                            <i class="ti ti-file-description me-2"></i><span style="padding-left:10px;"> {{ __('Additional info') }} </span>
                        </button>
                    </h2>
                    <div id="collapse-additional" class="accordion-collapse collapse show"
                         aria-labelledby="heading-additional">
                        <div class="accordion-body">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    {{ Form::label('customer_type', __('Customer type'), ['class' => 'form-label']) }}
                                    {{ Form::text('customer_type', null, [
                                        'class' => 'form-control',
                                        'placeholder' => __('Select'),
                                    ]) }}
                                </div>
                            </div>

                            <hr class="my-3">

                            <h6 class="mb-2">{{ __('Taxes') }}</h6>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    {{ Form::label('tax_exemption_details', __('Exemption details'), ['class' => 'form-label']) }}
                                    {{ Form::text('tax_exemption_details', null, ['class' => 'form-control', 'maxlength' => 16]) }}
                                </div>
                            </div>

                            <div class="row g-3 mt-1 align-items-end">
                                <div class="col-md-12">
                                    <div class="form-check">
                                        {{ Form::checkbox('is_taxable', 1, true, [
                                            'class' => 'form-check-input',
                                            'id' => 'is_taxable',
                                        ]) }}
                                        <label class="form-check-label" for="is_taxable">
                                            {{ __('This customer is taxable') }}
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    {{ Form::label('default_tax_code', __('Default tax code'), ['class' => 'form-label']) }}
                                    {{ Form::text('default_tax_code', null, ['class' => 'form-control']) }}
                                </div>
                            </div>

                            <hr class="my-3">

                            <h6 class="mb-2">{{ __('Opening balance') }}</h6>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    {{ Form::label('opening_balance', __('Opening balance'), ['class' => 'form-label']) }}
                                    {{ Form::number('opening_balance', null, [
                                        'class' => 'form-control',
                                        'step' => '0.01',
                                    ]) }}
                                </div>
                                <div class="col-md-6">
                                    {{ Form::label('opening_balance_as_of', __('As of'), ['class' => 'form-label']) }}
                                    {{ Form::date('opening_balance_as_of', now(), [
                                        'class' => 'form-control',
                                    ]) }}
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- SECTION: CUSTOM FIELDS --}}
                {{-- @if(!$customFields->isEmpty()) --}}
                    <div id="sec-custom-fields" class="accordion-item customer-section mb-3">
                        <h2 class="accordion-header" id="heading-custom-fields">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapse-custom-fields" aria-expanded="true"
                                    aria-controls="collapse-custom-fields">
                                <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class="" width="24" height="24" fill="currentColor"><path d="M21.174 15.48l-3.5-3.5 1.768-1.767L20.857 8.8a3 3 0 000-4.243l-1.414-1.414a3 3 0 00-4.243 0l-1.414 1.414-1.767 1.768-3.5-3.5a2.18 2.18 0 00-1.31-.636 1.783 1.783 0 00-1.455.5l-3.029 3.03a1.964 1.964 0 00.138 2.764l3.5 3.5-2.476 2.474a.99.99 0 00-.226.374.694.694 0 00-.034.086l-1.418 5.66a1 1 0 001.213 1.214l5.657-1.414c.033-.01.062-.026.094-.037a1.002 1.002 0 00.367-.224l2.475-2.476 3.5 3.5c.398.405.94.636 1.509.643a1.76 1.76 0 001.255-.505l3.031-3.031a1.964 1.964 0 00-.136-2.767zm-4.56-10.923a1 1 0 011.415 0l1.414 1.414a1.001 1.001 0 010 1.415l-.707.707-2.829-2.83.707-.706zm-2.121 2.12l2.829 2.83-1.061 1.06-2.828-2.828 1.06-1.061zM7.776 13.4l2.824 2.824-1.764 1.768-2.828-2.828L7.776 13.4zm-3.222 6.05l.582-2.326 1.744 1.74-2.326.586zm12.418.31a.297.297 0 01-.041-.033l-1.378-1.377 1.415-1.415a1 1 0 00-1.414-1.414l-1.415 1.415-.707-.708.708-.707a1.002 1.002 0 00-1.414-1.421l-.708.707-.707-.707 1.415-1.414a1 1 0 10-1.414-1.414L9.9 12.689l-.707-.707.707-.707A1.001 1.001 0 008.483 9.86l-.707.707-.708-.708 1.415-1.414a1 1 0 00-1.414-1.414L5.654 8.446 4.277 7.069a.265.265 0 01-.033-.04l2.822-2.822a.2.2 0 01.04.034l4.2 4.2 4.238 4.238 4.2 4.2a.264.264 0 01.033.04l-2.805 2.842z"></path></svg><span style="padding-left:10px;"> {{ __('Custom fields') }} </span>
                            </button>
                        </h2>
                        <div id="collapse-custom-fields" class="accordion-collapse collapse show"
                             aria-labelledby="heading-custom-fields">
                            <div class="accordion-body">
                                @include('customFields.formBuilder')
                            </div>
                        </div>
                    </div>
                {{-- @endif --}}

            </div> {{-- /.accordion --}}

        </div>
    </div>

    {{-- FOOTER (sticky inside drawer) --}}
    <div class="customer-drawer-footer border-top d-flex justify-content-end gap-2 px-4 py-3">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="submit" class="btn btn-primary" style="
    background: #00892E !important;
    border: #00892E !important;
">Save</button>
    </div>

{{ Form::close() }}

{{-- ========== STYLES ========== --}}
<style>
    /* hide default commonModal header/footer so we only see our QBO-like header/footer */
    #commonModal .modal-header,
    #commonModal .modal-footer {
        display: none !important;
    }

    body.theme-6 .form-check-input:checked {
    background-color: #00892E;
    border-color: #00892E;
    }

    body.theme-6 .form-check-input:focus, body.theme-6 .form-select:focus, body.theme-6 .form-control:focus, body.theme-6 .custom-select:focus, body.theme-6 .dataTable-selector:focus, body.theme-6 .dataTable-input:focus {
    border-color: #00892E;
    box-shadow: 0 0 0 0.2rem rgb(0, 137, 46, 0.25);
    }

    /* RIGHT-SIDE DRAWER LAYOUT ------------------------------------------ */

    .modal.modal-right .modal-dialog {
        position: fixed;
        margin: 0;
        top: 0;
        right: 0;
        bottom: 0;
        height: 100%;
        max-width: 700px; /* Increased width for better layout */
        width: 100%;
        transform: translate3d(100%, 0, 0);
        transition: transform .3s ease-out;
    }

    .modal.modal-right.show .modal-dialog {
        transform: translate3d(0, 0, 0);
    }

    /* make the modal content a full-height flex column */
    .modal.modal-right .modal-content {
        height: 100%;
        border-radius: 0;
        display: flex;
        flex-direction: column;
        border: none; /* Remove border */
    }

    /* modal-body should also be flex + full height, no padding */
    .modal.modal-right .modal-body {
        flex: 1 1 auto;
        padding: 0;
        display: flex;
        flex-direction: column; /* Ensure children stack vertically */
        min-height: 0; /* important for scroll */
        overflow: hidden; /* Prevent body itself from scrolling, let inner container scroll */
    }
    
    /* Ensure the form takes full height */
    #customerForm {
        display: flex;
        flex-direction: column;
        height: 100%;
        min-height: 0; /* Important for flex child to shrink */
        overflow: hidden;
    }

    @media (max-width: 991.98px) {
        .modal.modal-right .modal-dialog {
            max-width: 100vw;
            width: 100vw;
        }
    }

    /* WRAPPER / HEADER / BODY / FOOTER ---------------------------------- */

    .customer-drawer-wrapper {
        display: flex;
        flex-direction: column;
        height: 100%;
        width: 100%;
        overflow: hidden; /* Prevent double scrollbars */
    }

    .customer-drawer-header {
        flex-shrink: 0; /* Don't shrink */
        background-color: #fff;
        z-index: 10;
    }

    .customer-drawer-header h5 {
        font-weight: 700;
        font-size: 20px;
        color: #6B6C72;
    }

    .customer-drawer-body {
        flex: 1 1 0; /* Flex-basis 0 to ensure it shrinks/grows properly */
        position: relative; /* Ensure position() works relative to this container */
        overflow-y: auto;   /* this is the scroll area */
        min-height: 0;      /* required for flexbox scroll */
        background-color: #f4f5f8; /* Light gray background like QBO */
        padding-bottom: 20px;
    }

    .customer-drawer-footer {
        flex-shrink: 0; /* Don't shrink */
        background-color: #fff;
        z-index: 10;
        box-shadow: 0 -4px 12px rgba(0,0,0,0.05); /* Subtle shadow */
    }

    /* HEADER NAV ICONS -------------------------------------------------- */

    .customer-nav-btn {
        border: none;
        background: #ffffff;
        box-shadow: 0 1px 4px 0 rgba(0, 0, 0, 0.2);
        width: 40px;
        height: 40px;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 20px;
        color: #6b6c72;
        transition: all 0.2s;
    }

    .customer-nav-btn:hover {
        background-color: #f4f5f8;
        color: #393a3d;
    }

    /* .customer-nav-btn.active {
        background-color: #e8f0fe;
        color: #0d6efd;
    } */

    /* ACCORDION LOOK ---------------------------------------------------- */

    .customer-accordion .accordion-item {
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #dcdcdc;
        margin-bottom: 1rem;
        background-color: #fff;
        box-shadow: 0 1px 4px 0 rgba(0, 0, 0, 0.2);
    }

    .customer-accordion .accordion-button {
        background-color: #fff;
        font-weight: 600;
        color: #393a3d;
        padding: 1rem 1.25rem;
    }

    .customer-accordion .accordion-button:not(.collapsed) {
        background-color: #fff;
        color: #393a3d;
        box-shadow: inset 0 -1px 0 #dcdcdc;
    }

    .customer-accordion .accordion-button:focus {
        box-shadow: none;
        border-color: rgba(0,0,0,.125);
    }

    .customer-accordion .accordion-body {
        background-color: #fff;
        padding: 1.25rem;
    }
    
    /* Form controls styling to match QBO */
    .form-label {
        font-weight: 500;
        color: #393a3d;
        font-size: 0.875rem;
    }
    
    .form-control {
        border-color: #8d9096;
        border-radius: 4px;
        padding: 0.5rem 0.75rem;
    }
    
    .form-control:focus {
        border-color: #2ca01c; /* QBO Green focus */
        box-shadow: 0 0 0 2px rgba(44, 160, 28, 0.2);
    }

    /* --- FIXED HEADER + FOOTER, MIDDLE SCROLL --- */

/* make the drawer content a positioned container */
.modal.modal-right .modal-content {
    position: relative;
}

/* middle area is the scroll container; leave space so footer doesn't cover inputs */
.customer-drawer-body {
    overflow-y: auto;
    min-height: 0;
    padding-bottom: 100vh;
}

/* pin footer to bottom of the drawer viewport */
.customer-drawer-footer {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
}

</style>


{{-- ========== SCRIPTS ========== --}}
<script>
    (function ($) {
        "use strict";

        var $modal = $('#commonModal');
        $modal.addClass('modal-right');
        $modal.one('hidden.bs.modal', function () {
            $modal.removeClass('modal-right');
        });

        // header icon click -> smooth scroll
        $('.customer-nav-btn').on('click', function (e) {
            e.preventDefault();
            var target = $(this).data('target');
            if (!target) return;

            // Manually set active class immediately
            $('.customer-nav-btn').removeClass('active');
            $(this).addClass('active');

            var $container = $('.customer-drawer-body');
            var $el = $(target);
            
            if ($el.length) {
                // Calculate position relative to the container's current scroll
                // position().top is relative to the viewport of the container
                var currentScroll = $container.scrollTop();
                var elementTop = $el.position().top;
                var targetScroll = currentScroll + elementTop - 20; // 20px padding from top

                $container.animate({
                    scrollTop: targetScroll
                }, 300);
            }
        });

        // highlight active icon on scroll
        var scrollTimeout;
        $('.customer-drawer-body').on('scroll', function () {
            var $container = $(this);
            
            // Debounce to improve performance
            if (scrollTimeout) clearTimeout(scrollTimeout);
            
            scrollTimeout = setTimeout(function() {
                var containerTop = $container.offset().top;
                var containerScroll = $container.scrollTop();
                
                // Find the section that is currently at the top
                var currentId = '';
                
                $('.customer-section').each(function () {
                    var $sec = $(this);
                    var secTop = $sec.offset().top - containerTop; // Distance from top of container viewport
                    
                    // If the section is near the top (within 100px) or we've scrolled past it
                    // We want the last section that satisfies this to be the current one
                    if (secTop <= 100) {
                        currentId = $sec.attr('id');
                    }
                });

                if (currentId) {
                    $('.customer-nav-btn').removeClass('active');
                    $('.customer-nav-btn[data-target="#' + currentId + '"]').addClass('active');
                }
            }, 50);
        });

        // shipping same as billing
        $('#shipping_same_as_billing').on('change', function () {
            var checked = $(this).is(':checked');

            if (checked) {
                $('[name="shipping_name"]').val($('[name="billing_name"]').val());
                $('[name="shipping_phone"]').val($('[name="billing_phone"]').val());
                $('[name="shipping_address"]').val($('[name="billing_address"]').val());
                $('[name="shipping_address_2"]').val($('[name="billing_address_2"]').val());
                $('[name="shipping_city"]').val($('[name="billing_city"]').val());
                $('[name="shipping_state"]').val($('[name="billing_state"]').val());
                $('[name="shipping_country"]').val($('[name="billing_country"]').val());
                $('[name="shipping_zip"]').val($('[name="billing_zip"]').val());
            }
        });

    })(jQuery);
</script>
