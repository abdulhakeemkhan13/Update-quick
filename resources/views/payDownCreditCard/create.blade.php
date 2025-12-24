<script src="{{ asset('js/jquery-ui.min.js') }}"></script>
<script>
    $(document).ready(function() {
        // Update total paid display when amount changes
        $('#pay-down-amount').on('input', function() {
            var amount = parseFloat($(this).val()) || 0;
            var formatted = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(amount);
            $('#totalPaidDisplay').text(formatted);
        });
        
        // Toggle accordion
        $('.qbo-accordion-header').on('click', function() {
            $(this).toggleClass('expanded');
            var content = $(this).next('.qbo-accordion-content');
            content.toggleClass('show');
            // Update chevron direction
            var svg = $(this).find('svg');
            if ($(this).hasClass('expanded')) {
                svg.css('transform', 'rotate(90deg)');
            } else {
                svg.css('transform', 'rotate(0deg)');
            }
        });
        
        // Toggle save dropdown
        $('.dropdown-toggle-split').on('click', function(e) {
            e.stopPropagation();
        });
        
        // Form Submit via AJAX
        $('#pay-down-credit-card-form').on('submit', function(e) {
            e.preventDefault();
            $('.btn-qbo-save').prop('disabled', true).text('{{ __("Saving...") }}');
            
            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: new FormData(this),
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $('#ajaxModal').modal('hide');
                        if (typeof show_toastr === 'function') {
                            show_toastr('success', response.message || '{{ __("Credit card payment recorded successfully") }}', 'success');
                        }
                        setTimeout(() => window.location.reload(), 500);
                    } else {
                        if (typeof show_toastr === 'function') {
                            show_toastr('error', response.message || '{{ __("Error recording payment") }}', 'error');
                        }
                        $('.btn-qbo-save').prop('disabled', false).text('{{ __("Save") }}');
                    }
                },
                error: function(xhr) {
                    let message = '{{ __("Error recording payment") }}';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    if (typeof show_toastr === 'function') {
                        show_toastr('error', message, 'error');
                    }
                    $('.btn-qbo-save').prop('disabled', false).text('{{ __("Save") }}');
                }
            });
        });
        
        // Initialize Select2 if available
        if ($.fn.select2) {
            $('.select2').select2({
                dropdownParent: $('#ajaxModal')
            });
        }
    });
</script>

<!-- Attachments Script from Bill Create -->
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
                    '<span class="attachment-name">' + file.name + '</span>' +
                    '<span class="attachment-size">' + sizeKB + ' KB</span>' +
                    '<button type="button" class="attachment-remove" data-row-id="' + rowId +
                    '">&times;</button>' +
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

<!-- Attachments CSS from Bill Create -->
<style>
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

    .attachment-zone {
        border: 2px dashed #c4c4c4;
        border-radius: 4px;
        padding: 32px;
        text-align: center;
        background: #fafafa;
        cursor: pointer;
        transition: all 0.2s;
        max-width: 280px;
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
    
    /* Header action buttons */
    .header-action-btn {
        background: none;
        border: none;
        color: #393a3d;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 8px;
        cursor: pointer;
        font-size: 14px;
    }
    
    .header-action-btn:hover {
        background: rgba(0,0,0,0.05);
        border-radius: 4px;
    }
    
    /* Accordion styling */
    .qbo-accordion-content.show {
        display: block !important;
    }
    
    /* Form control focus states */
    .form-control:focus {
        border-color: #0077c5;
        box-shadow: 0 0 0 1px #0077c5;
    }
    
    /* Button styles */
    .btn-sm-qbo {
        padding: 6px 14px;
        font-size: 14px;
        font-weight: 600;
    }
    
    .btn-success {
        background-color: #2ca01c !important;
        border-color: #2ca01c !important;
    }
    
    .btn-success:hover {
        background-color: #25861b !important;
        border-color: #25861b !important;
    }
</style>

<div class="row">
    <!-- Header - Same as expense create -->
    <div class="d-flex justify-content-between align-items-center border-bottom"
        style="
            font-size: 15px;
            font-weight: 600;
            height: 55px;
            background: #f4f5f8;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 999;
            padding: 0 10px;
        ">
        <div class="TrowserHeader d-flex align-items-center">
            <a href="#" class="text-dark me-2"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24" color="currentColor" width="24px" height="24px" focusable="false"
                    aria-hidden="true" class="">
                    <path fill="currentColor"
                        d="M13.007 7a1 1 0 0 0-1 1L12 12a1 1 0 0 0 1 1l3.556.006a1 1 0 0 0 0-2L14 11l.005-3a1 1 0 0 0-.998-1">
                    </path>
                    <path fill="currentColor"
                        d="M19.374 5.647A8.94 8.94 0 0 0 13.014 3H13a8.98 8.98 0 0 0-8.98 8.593l-.312-.312a1 1 0 0 0-1.416 1.412l2 2a1 1 0 0 0 1.414 0l2-2a1 1 0 0 0-1.412-1.416l-.272.272A6.984 6.984 0 0 1 13 5h.012A7 7 0 0 1 13 19h-.012a7 7 0 0 1-4.643-1.775 1 1 0 1 0-1.33 1.494A9 9 0 0 0 12.986 21H13a9 9 0 0 0 6.374-15.353">
                    </path>
                </svg></a>
            <h5 class="mb-0" style="font-size: 1.2rem;">{{ __('Pay down credit card') }}</h5>
        </div>
        <div class="TrowserHeader d-flex align-items-center">
            <button type="button" class="header-action-btn">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" color="currentColor"
                    width="24px" height="24px" focusable="false" aria-hidden="true" class="">
                    <path fill="currentColor"
                        d="M14.35 2a1 1 0 0 1 0 2H6.49a2.54 2.54 0 0 0-2.57 2.5v7A2.54 2.54 0 0 0 6.49 16h1.43a1 1 0 0 1 1 1v1.74l2.727-2.48c.184-.167.424-.26.673-.26h5.03a2.54 2.54 0 0 0 2.57-2.5v-4a1 1 0 0 1 2 0v4a4.54 4.54 0 0 1-4.57 4.5h-4.643l-4.114 3.74A1.002 1.002 0 0 1 6.92 21v-3h-.43a4.54 4.54 0 0 1-4.57-4.5v-7A4.54 4.54 0 0 1 6.49 2zm6.414.6.725.726c.79.791.79 2.074 0 2.865l-5.812 5.794c-.128.128-.29.219-.465.263l-2.9.721q-.121.03-.247.031a.998.998 0 0 1-.969-1.244l.73-2.9a1 1 0 0 1 .263-.463L17.9 2.6a2.027 2.027 0 0 1 2.864 0m-1.412 1.413-.763.724L13.7 9.612l-.255 1.015 1.016-.252 5.616-5.6V4.74z">
                    </path>
                </svg>
                Feedback
            </button>
            <button type="button" class="header-action-btn">
                <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class="" width="24px"
                    height="24px" fill="currentColor">
                    <path d="M12.024 7.982h-.007a4 4 0 100 8 4 4 0 10.007-8zm-.006 6a2 2 0 01.002-4 2 2 0 110 4h-.002z">
                    </path>
                    <path
                        d="M20.444 13.4l-.51-.295a7.557 7.557 0 000-2.214l.512-.293a2.005 2.005 0 00.735-2.733l-1-1.733a2.005 2.005 0 00-2.731-.737l-.512.295a8.071 8.071 0 00-1.915-1.113v-.59a2 2 0 00-2-2h-2a2 2 0 00-2 2v.6a8.016 8.016 0 00-1.911 1.1l-.52-.3a2 2 0 00-2.725.713l-1 1.73a2 2 0 00.728 2.733l.509.295a7.75 7.75 0 00-.004 2.22l-.51.293a2 2 0 00-.738 2.73l1 1.732a2 2 0 002.73.737l.513-.295A8.07 8.07 0 009.01 19.39v.586a2 2 0 002 2h2a2 2 0 002-2V19.4a8.014 8.014 0 001.918-1.107l.51.3a2 2 0 002.734-.728l1-1.73a2 2 0 00-.728-2.735zm-2.593-2.8a5.8 5.8 0 010 2.78 1 1 0 00.472 1.1l1.122.651-1 1.73-1.123-.65a1 1 0 00-1.187.137 6.02 6.02 0 01-2.4 1.387 1 1 0 00-.716.957v1.294h-2v-1.293a1 1 0 00-.713-.96 5.991 5.991 0 01-2.4-1.395 1.006 1.006 0 00-1.188-.142l-1.125.648-1-1.733 1.125-.647a1 1 0 00.475-1.1 5.945 5.945 0 01-.167-1.387c.003-.467.06-.933.17-1.388a1 1 0 00-.471-1.1l-1.123-.65 1-1.73 1.124.651c.019.011.04.01.06.02a.97.97 0 00.186.063.9.9 0 00.2.04c.02 0 .039.011.059.011a1.08 1.08 0 00.136-.025.98.98 0 00.17-.032A1.02 1.02 0 007.7 7.75a.986.986 0 00.157-.1c.015-.013.034-.017.048-.03a6.011 6.011 0 012.4-1.39.453.453 0 00.049-.026.938.938 0 00.183-.1.87.87 0 00.15-.1.953.953 0 00.122-.147c.038-.049.071-.1.1-.156a1.01 1.01 0 00.055-.173.971.971 0 00.04-.2c0-.018.012-.034.012-.053V3.981h2v1.294a1 1 0 00.713.96c.897.273 1.72.75 2.4 1.395a1 1 0 001.186.141l1.126-.647 1 1.733-1.125.647a1 1 0 00-.465 1.096z">
                    </path>
                </svg>
            </button>
            <div class="TrowserHeader">
                <a href="#" class="text-dark me-2"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" color="currentColor" width="24px" height="24px" focusable="false"
                        aria-hidden="true" class="">
                        <path fill="currentColor"
                            d="M12 15a1 1 0 1 0 0 2 1 1 0 0 0 0-2M15 10a3.006 3.006 0 0 0-3-3 3 3 0 0 0-2.9 2.27 1 1 0 1 0 1.937.494A1.02 1.02 0 0 1 12 9a1.006 1.006 0 0 1 1 1c0 .013.007.024.007.037s-.007.023-.007.036a.5.5 0 0 1-.276.447l-1.172.584A1 1 0 0 0 11 12v1a1 1 0 1 0 2 0v-.383l.619-.308a2.52 2.52 0 0 0 1.381-2.3z">
                        </path>
                        <path fill="currentColor"
                            d="M19.082 4.94A9.93 9.93 0 0 0 12.016 2H12a10 10 0 0 0-.016 20H12a10 10 0 0 0 7.082-17.06m-1.434 12.725A7.94 7.94 0 0 1 12 20h-.013A8 8 0 1 1 12 4h.012a8 8 0 0 1 5.636 13.665">
                        </path>
                    </svg></a>
            </div>
            <div class="TrowserHeader">
                <a href="#" class="text-dark me-2 close-modal-btn" onclick="$('#ajaxModal').modal('hide'); return false;"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" color="currentColor" width="24px" height="24px" focusable="false"
                        aria-hidden="true" class="">
                        <path fill="currentColor"
                            d="m13.432 11.984 5.3-5.285a1 1 0 1 0-1.412-1.416l-5.3 5.285-5.285-5.3A1 1 0 1 0 5.319 6.68l5.285 5.3L5.3 17.265a1 1 0 1 0 1.412 1.416l5.3-5.285L17.3 18.7a1 1 0 1 0 1.416-1.412z">
                        </path>
                    </svg></a>
            </div>
        </div>
    </div>

    <!-- Form Content - Same structure as expense create -->
    <form action="{{ route('paydowncreditcard.store') }}" method="POST" id="pay-down-credit-card-form" enctype="multipart/form-data" class="w-100" style="padding: 30px 30px; background: #ffffff;">
        @csrf
        <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
        
        <div class="col-12">
            <div class="card">
                <div class="card-body" style="background:#f4f5f8;">
                    
                    <!-- TOP SECTION -->
                    <div class="row align-items-start">
                        <!-- LEFT: Form Fields -->
                        <div class="col-10">
                            <p class="mb-3" style="font-size: 14px; color: #393a3d;">{{ __('Record payments made to your balance') }}</p>
                            
                            <!-- Credit Card Account -->
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label" style="font-size: 12px; color: #6b7280;">{{ __('Which credit card did you pay?') }}</label>
                                    <select name="credit_card_account_id" id="credit_card_account_id" class="form-control select" required>
                                        <option value="">{{ __('Select credit card') }}</option>
                                        @foreach($creditCardAccounts as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Payee (Optional) -->
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label" style="font-size: 12px; color: #6b7280;">{{ __('Payee (optional)') }}</label>
                                    <select name="payee_id" id="payee_id" class="form-control select">
                                        <option value="">{{ __('Choose a payee') }}</option>
                                        @foreach($vendors as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Amount and Date Row -->
                            <div class="row mb-3">
                                <div class="col-3">
                                    <label class="form-label" style="font-size: 12px; color: #6b7280;">{{ __('How much did you pay?') }}</label>
                                    <input type="text" name="amount" id="pay-down-amount" class="form-control" 
                                           placeholder="{{ __('Enter the amount') }}" required>
                                </div>
                                <div class="col-3">
                                    <label class="form-label" style="font-size: 12px; color: #6b7280;">{{ __('Date of payment') }}</label>
                                    <input type="date" name="payment_date" id="payment_date" class="form-control" 
                                           value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                            
                            <!-- Bank Account -->
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label" style="font-size: 12px; color: #6b7280;">{{ __('What did you use to make this payment?') }}</label>
                                    <select name="bank_account_id" id="bank_account_id" class="form-control select" required>
                                        <option value="">{{ __('Select bank account') }}</option>
                                        @foreach($bankAccounts as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Memo and Attachments Accordion -->
                            <div class="mt-4">
                                <button type="button" class="qbo-accordion-header d-flex align-items-center gap-2 bg-transparent border-0 p-0" style="font-size: 14px; font-weight: 600; color: #393a3d; cursor: pointer;">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="16" height="16" style="transition: transform 0.2s;">
                                        <path fill="currentColor" d="M9.009 19.013a1 1 0 0 1-.709-1.708l5.3-5.285-5.281-5.3a1 1 0 1 1 1.416-1.413l5.991 6.01a1 1 0 0 1 0 1.413l-6.011 5.991a1 1 0 0 1-.706.292"></path>
                                    </svg>
                                    <span>{{ __('Memo and attachments') }}</span>
                                </button>
                                <div class="qbo-accordion-content" style="display: none; padding: 16px 0 16px 24px;">
                                    <div style="font-size: 12px; color: #6b7280; margin-bottom: 8px;">{{ __('Memo') }}</div>
                                    <textarea name="memo" id="memo" class="form-control" style="max-width: 280px; min-height: 120px;"></textarea>
                                    
                                    <div class="mt-4">
                                        <label style="font-size: 12px; color: #6b7280; margin-bottom: 8px; display: block;">{{ __('Attachments') }}</label>
                                        
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
                                        
                                        <div class="mt-2">
                                            <a href="#" style="color: #0077c5; font-size: 14px; text-decoration: none;">{{ __('Show existing') }}</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Privacy Link -->
                            <div class="text-center mt-4">
                                <a href="https://www.intuit.com/privacy/" target="_blank" style="color: #0077c5; font-size: 14px; text-decoration: none;">{{ __('Privacy') }}</a>
                            </div>
                        </div>
                        
                        <!-- RIGHT: Total Paid -->
                        <div class="col-2 text-end">
                            <div style="font-size: 12px; color: #6b7280; margin-bottom: 2px;">{{ __('Total paid') }}</div>
                            <div id="totalPaidDisplay" style="font-size: 28px; font-weight: 700; color: #393a3d;">$0.00</div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </form>

    <!-- Footer - Same as expense create -->
    <div class="fixed-footer d-flex justify-content-between align-items-center"
        style="
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: #ffffff;
            padding: 0.75rem 1.5rem;
            border-top: 1px solid #dee2e6;
            z-index: 1050;
        ">
        <!-- Left section -->
        <div class="footer-left d-flex align-items-center">
            <button type="button" class="btn btn-link text-success p-2 btn-cancel-custom" onclick="$('#ajaxModal').modal('hide');"
                style="
                    background: #fff;
                    border: 2px solid #00892E;
                    color: #00892E;
                    font-weight: 600;
                    padding: 6px 12px !important;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 14px;
                    white-space: nowrap;
                ">Cancel</button>
        </div>

        <!-- Center section -->
        <div class="footer-center d-flex align-items-center">
        </div>

        <!-- Right section: primary actions -->
        <div class="footer-right d-flex align-items-center gap-2">
            <button type="submit" form="pay-down-credit-card-form" class="btn btn-light btn-sm-qbo btn-qbo-save">{{ __('Save') }}</button>
            <div class="btn-group">
                <button type="submit" form="pay-down-credit-card-form"
                    class="btn btn-success btn-sm-qbo" style="background-color: #2ca01c; border-color: #2ca01c;">{{ __('Save and close') }}</button>
                <button type="button" class="btn btn-success btn-sm-qbo dropdown-toggle dropdown-toggle-split"
                    data-bs-toggle="dropdown" aria-expanded="false" style="background-color: #2ca01c; border-color: #2ca01c;">
                    <span class="visually-hidden">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">{{ __('Save and new') }}</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>
