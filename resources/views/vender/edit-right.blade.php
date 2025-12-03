{{-- resources/views/vender/edit-right.blade.php --}}
{{ Form::model($vender, ['route' => ['vender.update', $vender->id], 'method' => 'PUT', 'id' => 'venderForm', 'class' => 'customer-drawer-wrapper h-100 d-flex flex-column']) }}

{{-- HEADER (single, QBO-style) --}}
<div class="customer-drawer-header d-flex align-items-center justify-content-between border-bottom px-4 py-3">
    <h5 class="mb-0">{{ __('Edit Vendor') }}</h5>

    <div class="d-flex align-items-center gap-2">

        {{-- header nav icons (scroll to sections) --}}
        <button type="button" class="customer-nav-btn active" data-target="#sec-basic"
            title="{{ __('Name and contact') }}">
            <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class="" width="24px" height="24px"
                fill="currentColor">
                <path
                    d="M17.862 2H7.138A3.142 3.142 0 004 5.138V6a1 1 0 000 2v3a1 1 0 000 2v3a1 1 0 000 2v.862A3.142 3.142 0 007.138 22h10.724A3.142 3.142 0 0021 18.862V5.138A3.142 3.142 0 0017.862 2zM19 18.862A1.14 1.14 0 0117.862 20H7.138A1.139 1.139 0 016 18.862V18a1 1 0 000-2v-3a1 1 0 000-2V8a1 1 0 000-2v-.862A1.14 1.14 0 017.138 4h10.724A1.14 1.14 0 0119 5.138v13.724z">
                </path>
                <path
                    d="M13.785 12.234c.05-.04.1-.086.151-.134.379-.38.591-.896.592-1.433v-.889a2.036 2.036 0 00-.6-1.436 2.078 2.078 0 00-2.869 0 2.03 2.03 0 00-.592 1.433v.89a2.03 2.03 0 00.743 1.566 2.03 2.03 0 00-1.632 1.988V15a1.252 1.252 0 001.25 1.25h3.334A1.254 1.254 0 0015.417 15v-.778a2.03 2.03 0 00-1.632-1.988z">
                </path>
            </svg>
        </button>

        <button type="button" class="customer-nav-btn" data-target="#sec-addresses" title="{{ __('Addresses') }}">
            <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class="" width="24px" height="24px"
                fill="currentColor">
                <path d="M12 14.5a4 4 0 110-8 4 4 0 010 8zm0-6a2 2 0 100 4 2 2 0 000-4z"></path>
                <path
                    d="M12 22a1 1 0 01-.858-.486L9.383 18.58a8.5 8.5 0 1110.97-9.68 8.454 8.454 0 01-5.737 9.681l-1.758 2.934A1 1 0 0112 22zm.018-18A6.493 6.493 0 0010.3 16.763c.251.068.466.23.6.453l1.1 1.838 1.1-1.838a1 1 0 01.6-.452 6.5 6.5 0 00-.4-12.638A6.801 6.801 0 0012.018 4z">
                </path>
            </svg>
        </button>

        <button type="button" class="customer-nav-btn" data-target="#sec-notes"
            title="{{ __('Notes & attachments') }}">
            <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class="" width="24px" height="24px"
                fill="currentColor">
                <path
                    d="M21.013 10a1.024 1.024 0 00-1 1l-.02 8.014a1 1 0 01-1 1l-14-.02a1 1 0 01-1-1l.02-14a1 1 0 011-1L13 4.01a1 1 0 100-2l-7.984-.02H5.01a3 3 0 00-3 3l-.02 14a3 3 0 003 3l14 .02h.006a3 3 0 003-2.994L22.01 11a1 1 0 00-.997-1z">
                </path>
                <path
                    d="M8.975 10.885L8 14.755a1 1 0 001.212 1.215l3.873-.962a1 1 0 00.465-.262l7.756-7.732a2.373 2.373 0 000-3.35l-.962-.964A2.359 2.359 0 0018.67 2a2.348 2.348 0 00-1.672.69l-7.759 7.731a1 1 0 00-.264.464zm1.872.757l7.559-7.536a.369.369 0 01.521.001l.966.969a.374.374 0 010 .522l-7.559 7.536-1.986.494.499-1.986z">
                </path>
            </svg>
        </button>

        <button type="button" class="customer-nav-btn" data-target="#sec-payments" title="{{ __('Payments') }}">
            <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class="" width="24" height="24"
                fill="currentColor">
                <path
                    d="M20.5 10A1.5 1.5 0 0022 8.5V6.325a1.5 1.5 0 00-.891-1.37l-8.5-3.78a1.5 1.5 0 00-1.216 0l-8.5 3.777A1.5 1.5 0 002 6.325V8.5A1.5 1.5 0 003.5 10H4v7h-.5A1.5 1.5 0 002 18.5v2A1.5 1.5 0 003.5 22h17a1.5 1.5 0 001.5-1.5v-2a1.5 1.5 0 00-1.5-1.5H20v-7h.5zM4 6.65l8-3.556 8 3.556V8H4V6.65zM9 17v-7h6v7H9zm-3-7h1v7H6v-7zm14 10H4v-1h16v1zm-2-3h-1v-7h1v7z">
                </path>
                <path d="M12 7a1 1 0 100-2 1 1 0 000 2z"></path>
            </svg>
        </button>

        <button type="button" class="customer-nav-btn" data-target="#sec-additional"
            title="{{ __('Additional info') }}">
            <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class="" width="24px" height="24px"
                fill="currentColor">
                <path
                    d="M20.988 8.939a1 1 0 00-.054-.265.973.973 0 00-.224-.374v-.005l-6-6a1 1 0 00-.283-.191c-.031-.014-.064-.022-.1-.034a.992.992 0 00-.259-.052C14.042 2.011 14.023 2 14 2H6a3 3 0 00-3 3v14a3 3 0 003 3h12a3 3 0 003-3V9c0-.022-.011-.04-.012-.061zM15 5.414L17.586 8H16a1 1 0 01-1-1V5.414zM18 20H6a1 1 0 01-1-1V5a1 1 0 011-1h7v3a3 3 0 003 3h3v9a1 1 0 01-1 1z">
                </path>
                <path
                    d="M7 10h3a1 1 0 100-2H7a1 1 0 000 2zm7 3H7a1 1 0 000 2h7a1 1 0 000-2zm0 3H7a1 1 0 000 2h7a1 1 0 000-2z">
                </path>
            </svg>
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
                        <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class="" width="24px"
                            height="24px" fill="currentColor">
                            <path
                                d="M17.862 2H7.138A3.142 3.142 0 004 5.138V6a1 1 0 000 2v3a1 1 0 000 2v3a1 1 0 000 2v.862A3.142 3.142 0 007.138 22h10.724A3.142 3.142 0 0021 18.862V5.138A3.142 3.142 0 0017.862 2zM19 18.862A1.14 1.14 0 0117.862 20H7.138A1.139 1.139 0 016 18.862V18a1 1 0 000-2v-3a1 1 0 000-2V8a1 1 0 000-2v-.862A1.14 1.14 0 017.138 4h10.724A1.14 1.14 0 0119 5.138v13.724z">
                            </path>
                            <path
                                d="M13.785 12.234c.05-.04.1-.086.151-.134.379-.38.591-.896.592-1.433v-.889a2.036 2.036 0 00-.6-1.436 2.078 2.078 0 00-2.869 0 2.03 2.03 0 00-.592 1.433v.89a2.03 2.03 0 00.743 1.566 2.03 2.03 0 00-1.632 1.988V15a1.252 1.252 0 001.25 1.25h3.334A1.254 1.254 0 0015.417 15v-.778a2.03 2.03 0 00-1.632-1.988z">
                            </path>
                        </svg><span style="padding-left:10px;"> {{ __('Name and contact') }} </span>
                    </button>
                </h2>
                <div id="collapse-basic" class="accordion-collapse collapse show" aria-labelledby="heading-basic">
                    <div class="accordion-body">

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                {{ Form::label('company_name', __('Company name'), ['class' => 'form-label']) }}
                                {{ Form::text('company_name', null, ['class' => 'form-control']) }}
                            </div>
                            <div class="col-md-6">
                                {{-- use existing "name" as Customer display name * --}}
                                {{ Form::label('name', __('Vendor display name') . ' *', ['class' => 'form-label']) }}
                                {{ Form::text('name', null, [
                                    'class' => 'form-control',
                                    'required' => 'required',
                                ]) }}
                            </div>
                        </div>

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
                        </div>

                    </div>
                </div>
            </div>

            {{-- SECTION: ADDRESSES --}}
            <div id="sec-addresses" class="accordion-item customer-section mb-3">
                <h2 class="accordion-header" id="heading-addresses">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapse-addresses" aria-expanded="true" aria-controls="collapse-addresses">
                        <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class="" width="24px"
                            height="24px" fill="currentColor">
                            <path d="M12 14.5a4 4 0 110-8 4 4 0 010 8zm0-6a2 2 0 100 4 2 2 0 000-4z"></path>
                            <path
                                d="M12 22a1 1 0 01-.858-.486L9.383 18.58a8.5 8.5 0 1110.97-9.68 8.454 8.454 0 01-5.737 9.681l-1.758 2.934A1 1 0 0112 22zm.018-18A6.493 6.493 0 0010.3 16.763c.251.068.466.23.6.453l1.1 1.838 1.1-1.838a1 1 0 01.6-.452 6.5 6.5 0 00-.4-12.638A6.801 6.801 0 0012.018 4z">
                            </path>
                        </svg><span style="padding-left:10px;"> {{ __('Addresses') }} </span>
                    </button>
                </h2>
                <div id="collapse-addresses" class="accordion-collapse collapse show"
                    aria-labelledby="heading-addresses">
                    <div class="accordion-body">

                        {{-- Billing --}}
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">{{ __('Address') }}</h6>
                        </div>

                        <div class="row g-3">

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

                    </div>
                </div>
            </div>

            {{-- SECTION: NOTES & ATTACHMENTS --}}
            <div id="sec-notes" class="accordion-item customer-section mb-3">
                <h2 class="accordion-header" id="heading-notes">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapse-notes" aria-expanded="true" aria-controls="collapse-notes">
                        <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class="" width="24px"
                            height="24px" fill="currentColor">
                            <path
                                d="M21.013 10a1.024 1.024 0 00-1 1l-.02 8.014a1 1 0 01-1 1l-14-.02a1 1 0 01-1-1l.02-14a1 1 0 011-1L13 4.01a1 1 0 100-2l-7.984-.02H5.01a3 3 0 00-3 3l-.02 14a3 3 0 003 3l14 .02h.006a3 3 0 003-2.994L22.01 11a1 1 0 00-.997-1z">
                            </path>
                            <path
                                d="M8.975 10.885L8 14.755a1 1 0 001.212 1.215l3.873-.962a1 1 0 00.465-.262l7.756-7.732a2.373 2.373 0 000-3.35l-.962-.964A2.359 2.359 0 0018.67 2a2.348 2.348 0 00-1.672.69l-7.759 7.731a1 1 0 00-.264.464zm1.872.757l7.559-7.536a.369.369 0 01.521.001l.966.969a.374.374 0 010 .522l-7.559 7.536-1.986.494.499-1.986z">
                            </path>
                        </svg><span style="padding-left:10px;"> {{ __('Notes and attachments') }} </span>
                    </button>
                </h2>
                <div id="collapse-notes" class="accordion-collapse collapse show" aria-labelledby="heading-notes">
                    <div class="accordion-body">

                        <div class="mb-3">
                            {{ Form::label('notes', __('Notes'), ['class' => 'form-label']) }}
                            {{ Form::textarea('notes', null, ['class' => 'form-control', 'rows' => 3]) }}
                        </div>

                        <div class="mb-2">
                            <style>
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

                                .attachments-header {
                                    display: flex;
                                    justify-content: flex-end;
                                    align-items: center;
                                    margin-bottom: 4px;
                                    font-size: 13px;
                                    color: #393a3d;
                                }

                                #attachments-list {
                                    margin-bottom: 8px;
                                }

                                .attachment-row {
                                    display: flex;
                                    align-items: center;
                                    gap: 8px;
                                    padding: 6px 8px;
                                    border: 1px solid #e4e4e7;
                                    border-radius: 4px;
                                    margin-bottom: 4px;
                                    font-size: 13px;
                                    background: #ffffff;
                                }

                                .attachment-row .form-check {
                                    margin-bottom: 0;
                                }

                                body.theme-6 .form-check-input:checked {
                                    background-color: #2ca01c;
                                    border-color: #2ca01c;
                                }

                                .form-check {
                                    padding-left: 2.75em !important;
                                }

                                .attachment-name {
                                    flex: 1;
                                    white-space: nowrap;
                                    overflow: hidden;
                                    text-overflow: ellipsis;
                                }

                                .attachment-size {
                                    font-size: 12px;
                                    color: #6b6c72;
                                }

                                .attachment-remove {
                                    border: none;
                                    background: none;
                                    cursor: pointer;
                                    padding: 0 4px;
                                    font-size: 16px;
                                    line-height: 1;
                                    color: #6b6c72;
                                }

                                .attachment-remove:hover {
                                    color: #e81500;
                                }
                            </style>

                            <script>
                                $(function() {
                                    var attachLabel = @json(__('Attach to email'));
                                    var maxFileSize = 20 * 1024 * 1024; // 20 MB

                                    var $zone = $('#attachment-zone');
                                    var $addLink = $('#attachment-add-link');
                                    var $header = $('#attachments-header');
                                    var $list = $('#attachments-list');
                                    var $inputsContainer = $('#attachment-file-inputs');
                                    var currentInput = null;

                                    function updateSelectAllState() {
                                        var $boxes = $list.find('.attachment-email');
                                        var $checked = $boxes.filter(':checked');
                                        $('#attachment_select_all').prop('checked',
                                            $boxes.length > 0 && $boxes.length === $checked.length
                                        );
                                    }

                                    function toggleHeader() {
                                        if ($list.find('.attachment-row').length) {
                                            $header.removeClass('d-none');
                                        } else {
                                            $header.addClass('d-none');
                                            $('#attachment_select_all').prop('checked', false);
                                        }
                                    }

                                    function createAttachmentInput() {
                                        var $input = $('<input type="file" class="single-attachment-input d-none">');
                                        $inputsContainer.append($input);
                                        currentInput = $input;

                                        $input.on('change', function() {
                                            if (!this.files || !this.files.length) return;

                                            var file = this.files[0];

                                            if (file.size > maxFileSize) {
                                                alert('Max file size is 20 MB');
                                                $input.val('');
                                                return;
                                            }

                                            var rowId = 'att_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
                                            // bind the name now so Laravel gets an associative array: attachments[rowId]
                                            $input.attr('name', 'attachments[' + rowId + ']');

                                            var sizeKB = Math.round(file.size / 1024);

                                            var $row = $(
                                                '<div class="attachment-row" data-row-id="' + rowId + '">' +
                                                '<div class="form-check">' +
                                                '<input class="form-check-input attachment-email" ' +
                                                'type="checkbox" ' +
                                                'name="attachments_email[' + rowId + ']" checked>' +
                                                '<label class="form-check-label">' + attachLabel + '</label>' +
                                                '</div>' +
                                                '<span class="attachment-name">' + file.name + '</span>' +
                                                '<span class="attachment-size">' + sizeKB + ' KB</span>' +
                                                '<button type="button" class="attachment-remove" ' +
                                                'data-row-id="' + rowId + '">&times;</button>' +
                                                '</div>'
                                            );

                                            // move the actual file input into this row (so the file is submitted)
                                            $row.append($input);
                                            $list.append($row);

                                            toggleHeader();
                                            updateSelectAllState();

                                            // prepare a fresh empty input for the next "Add attachment"
                                            createAttachmentInput();
                                        });
                                    }

                                    // first empty input
                                    createAttachmentInput();

                                    // clicking the link or the zone opens current file input
                                    $addLink.on('click', function(e) {
                                        e.preventDefault();
                                        if (currentInput) currentInput.trigger('click');
                                    });
                                    $zone.on('click', function(e) {
                                        if ($(e.target).is('#attachment-add-link') ||
                                            $(e.target).closest('.attachment-row').length) {
                                            return;
                                        }
                                        if (currentInput) currentInput.trigger('click');
                                    });

                                    // "Select All" checkbox
                                    $('#attachment_select_all').on('change', function() {
                                        var checked = $(this).is(':checked');
                                        $list.find('.attachment-email').prop('checked', checked);
                                    });

                                    // single checkbox change updates select-all state
                                    $(document).on('change', '.attachment-email', function() {
                                        updateSelectAllState();
                                    });

                                    // remove one attachment (also removes its file input)
                                    $(document).on('click', '.attachment-remove', function() {
                                        var rowId = $(this).data('row-id');
                                        var $row = $list.find('.attachment-row[data-row-id="' + rowId + '"]');
                                        $row.remove();
                                        toggleHeader();
                                        updateSelectAllState();
                                    });
                                });
                            </script>

                            <div class="info-field">
                                <label>{{ __('Attachments') }}</label>

                                {{-- header with "Select all" - hidden until first file is added --}}
                                {{-- <div class="attachments-header d-none" id="attachments-header">
                                                    <div class="form-check" style="padding-left: 2.75em !important;">
                                                        <input class="form-check-input" type="checkbox"
                                                            id="attachment_select_all">
                                                        <label class="form-check-label" for="attachment_select_all">
                                                            {{ __('Select All') }}
                                                        </label>
                                                    </div>
                                                </div> --}}

                                {{-- rows get injected here when files are added --}}
                                <div id="attachments-list"></div>

                                {{-- QBO-like drop zone --}}
                                <div class="attachment-zone" id="attachment-zone">
                                    <a href="#" class="attachment-link" id="attachment-add-link">
                                        {{ __('Add attachment') }}
                                    </a>
                                    <div class="attachment-limit">{{ __('Max file size: 20 MB') }}</div>
                                </div>

                                {{-- we keep our hidden file inputs here --}}
                                <div id="attachment-file-inputs" class="d-none"></div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- SECTION: PAYMENTS (Bill Pay ACH info – 2 fields like QBO) --}}
            <div id="sec-payments" class="accordion-item customer-section mb-3">
                <h2 class="accordion-header" id="heading-payments">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapse-payments" aria-expanded="true" aria-controls="collapse-payments">
                        <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class="" width="24"
                            height="24" fill="currentColor">
                            <path
                                d="M20.5 10A1.5 1.5 0 0022 8.5V6.325a1.5 1.5 0 00-.891-1.37l-8.5-3.78a1.5 1.5 0 00-1.216 0l-8.5 3.777A1.5 1.5 0 002 6.325V8.5A1.5 1.5 0 003.5 10H4v7h-.5A1.5 1.5 0 002 18.5v2A1.5 1.5 0 003.5 22h17a1.5 1.5 0 001.5-1.5v-2a1.5 1.5 0 00-1.5-1.5H20v-7h.5zM4 6.65l8-3.556 8 3.556V8H4V6.65zM9 17v-7h6v7H9zm-3-7h1v7H6v-7zm14 10H4v-1h16v1zm-2-3h-1v-7h1v7z">
                            </path>
                            <path d="M12 7a1 1 0 100-2 1 1 0 000 2z"></path>
                        </svg>
                        <span style="padding-left:10px;"> {{ __('Bill Pay ACH info') }} </span>
                    </button>
                </h2>
                <div id="collapse-payments" class="accordion-collapse collapse show"
                    aria-labelledby="heading-payments">
                    <div class="accordion-body">

                        <div class="row g-3">
                            <div class="col-md-6">
                                {{ Form::label('bank_account_number', __('Bank account number'), ['class' => 'form-label']) }}
                                {{ Form::text('bank_account_number', null, [
                                    'class' => 'form-control',
                                    'maxlength' => 17,
                                ]) }}
                                <small class="text-muted d-block mt-1">
                                    {{ __('Bank account number is 5-17 digits.') }}
                                </small>
                            </div>

                            <div class="col-md-6">
                                {{ Form::label('routing_number', __('Routing number'), ['class' => 'form-label']) }}
                                {{ Form::text('routing_number', null, [
                                    'class' => 'form-control',
                                    'maxlength' => 9,
                                ]) }}
                                <small class="text-muted d-block mt-1">
                                    {{ __('Routing number is 9 digits.') }}
                                </small>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- SECTION: ADDITIONAL INFO (QBO-style like screenshots) --}}
            <div id="sec-additional" class="accordion-item customer-section mb-3">
                <h2 class="accordion-header" id="heading-additional">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapse-additional" aria-expanded="true"
                        aria-controls="collapse-additional">
                        <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class="" width="24px"
                            height="24px" fill="currentColor">
                            <path
                                d="M20.988 8.939a1 1 0 00-.054-.265.973.973 0 00-.224-.374v-.005l-6-6a1 1 0 00-.283-.191c-.031-.014-.064-.022-.1-.034a.992.992 0 00-.259-.052C14.042 2.011 14.023 2 14 2H6a3 3 0 00-3 3v14a3 3 0 003 3h12a3 3 0 003-3V9c0-.022-.011-.04-.012-.061zM15 5.414L17.586 8H16a1 1 0 01-1-1V5.414zM18 20H6a1 1 0 01-1-1V5a1 1 0 011-1h7v3a3 3 0 003 3h3v9a1 1 0 01-1 1z">
                            </path>
                            <path
                                d="M7 10h3a1 1 0 100-2H7a1 1 0 000 2zm7 3H7a1 1 0 000 2h7a1 1 0 000-2zm0 3H7a1 1 0 000 2h7a1 1 0 000-2z">
                            </path>
                        </svg>
                        <span style="padding-left:10px;"> {{ __('Additional info') }} </span>
                    </button>
                </h2>
                <div id="collapse-additional" class="accordion-collapse collapse show"
                    aria-labelledby="heading-additional">
                    <div class="accordion-body">

                        {{-- TAXES --}}
                        <h6 class="mb-2">{{ __('Taxes') }}</h6>

                        <div class="row g-3 align-items-center">
                            <div class="col-md-6">
                                {{ Form::label('business_id_no', __('Business ID No. / Social Security No.'), ['class' => 'form-label']) }}
                                {{ Form::text('business_id_no', null, ['class' => 'form-control']) }}
                            </div>

                            <div class="col-md-6 mt-3 mt-md-4">
                                <div class="form-check">
                                    {{ Form::checkbox('track_payments_1099', 1, null, [
                                        'class' => 'form-check-input',
                                        'id' => 'track_payments_1099',
                                    ]) }}
                                    <label class="form-check-label" for="track_payments_1099">
                                        {{ __('Track payments for 1099') }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <hr class="my-3">

                        {{-- EXPENSE RATES --}}
                        <h6 class="mb-2">{{ __('Expense rates') }}</h6>

                        <div class="row g-3">
                            <div class="col-md-6">
                                {{ Form::label('billing_rate', __('Billing rate (hr)'), ['class' => 'form-label']) }}
                                {{ Form::number('billing_rate', null, [
                                    'class' => 'form-control',
                                    'step' => '0.01',
                                ]) }}
                            </div>
                        </div>

                        <hr class="my-3">

                        {{-- PAYMENTS --}}
                        <h6 class="mb-2">{{ __('Payments') }}</h6>

                        <div class="row g-3">
                            <div class="col-md-6">
                                {{ Form::label('terms', __('Terms'), ['class' => 'form-label']) }}
                                {{ Form::text('terms', null, ['class' => 'form-control']) }}
                            </div>
                            <div class="col-md-6">
                                {{ Form::label('account_no', __('Account no.'), ['class' => 'form-label']) }}
                                {{ Form::text('account_no', null, ['class' => 'form-control']) }}
                            </div>
                        </div>

                        <hr class="my-3">

                        {{-- ACCOUNTING --}}
                        <h6 class="mb-2">{{ __('Accounting') }}</h6>

                        <div class="row g-3">
                            <div class="col-md-12">
                                {{ Form::label('default_expense_category', __('Default expense category'), ['class' => 'form-label']) }}
                                {{ Form::text('default_expense_category', null, [
                                    'class' => 'form-control',
                                    'placeholder' => __('Choose account'),
                                ]) }}
                            </div>
                        </div>

                        <hr class="my-3">

                        {{-- OPENING BALANCE --}}
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

        </div> {{-- /.accordion --}}

    </div>
</div>

{{-- FOOTER (sticky inside drawer) --}}
<div class="customer-drawer-footer border-top d-flex justify-content-end gap-2 px-4 py-3">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    <button type="submit" class="btn btn-primary"
        style="
    background: #00892E !important;
    border: #00892E !important;
">Update</button>
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

    body.theme-6 .form-check-input:focus,
    body.theme-6 .form-select:focus,
    body.theme-6 .form-control:focus,
    body.theme-6 .custom-select:focus,
    body.theme-6 .dataTable-selector:focus,
    body.theme-6 .dataTable-input:focus {
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
        max-width: 700px;
        /* Increased width for better layout */
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
        border: none;
        /* Remove border */
    }

    /* modal-body should also be flex + full height, no padding */
    .modal.modal-right .modal-body {
        flex: 1 1 auto;
        padding: 0;
        display: flex;
        flex-direction: column;
        /* Ensure children stack vertically */
        min-height: 0;
        /* important for scroll */
        overflow: hidden;
        /* Prevent body itself from scrolling, let inner container scroll */
    }

    /* Ensure the form takes full height */
    #venderForm {
        display: flex;
        flex-direction: column;
        height: 100%;
        min-height: 0;
        /* Important for flex child to shrink */
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
        overflow: hidden;
        /* Prevent double scrollbars */
    }

    .customer-drawer-header {
        flex-shrink: 0;
        /* Don't shrink */
        background-color: #fff;
        z-index: 10;
    }

    .customer-drawer-header h5 {
        font-weight: 700;
        font-size: 20px;
        color: #6B6C72;
    }

    .customer-drawer-body {
        flex: 1 1 0;
        /* Flex-basis 0 to ensure it shrinks/grows properly */
        position: relative;
        /* Ensure position() works relative to this container */
        overflow-y: auto;
        /* this is the scroll area */
        min-height: 0;
        /* required for flexbox scroll */
        background-color: #f4f5f8;
        /* Light gray background like QBO */
        padding-bottom: 20px;
    }

    .customer-drawer-footer {
        flex-shrink: 0;
        /* Don't shrink */
        background-color: #fff;
        z-index: 10;
        box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.05);
        /* Subtle shadow */
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
        border-color: rgba(0, 0, 0, .125);
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
        border-color: #2ca01c;
        /* QBO Green focus */
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

    /* SweetAlert2 z-index fix */
    .swal2-container {
        z-index: 99999 !important;
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

        // ---------- SweetAlert2: "leave without saving" ----------
        var allowModalClose = false;

        $modal.on('hide.bs.modal', function (e) {
            // if we've already confirmed, let it close
            if (allowModalClose) {
                allowModalClose = false;
                return;
            }

            // block the default hide
            e.preventDefault();

            Swal.fire({
                title: 'Do you want to leave without saving?',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'No',
                reverseButtons: true,
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-success',
                    cancelButton: 'btn btn-outline-secondary',
                    actions: 'swal2-actions d-flex justify-content-center gap-2'
                }
            }).then(function (result) {
                if (result.isConfirmed) {
                    // allow the modal to actually close once
                    allowModalClose = true;
                    $modal.modal('hide');
                }
            });
        });
        // ---------------------------------------------------------

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
