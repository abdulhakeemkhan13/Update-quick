@extends('layouts.admin')
@section('page-title')
    {{ __('Create Time Activity') }}
@endsection

@section('content')
    <div class="modal fade" id="time-activity-modal" tabindex="-1" aria-labelledby="timeActivityModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
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
            <h5 class="mb-0" style="font-size: 22px; font-weight: 500; color: #393A3D;">Single Day Entry</h5>
        </div>
        <div class="TrowserHeader d-flex align-items-center">
            <button type="button" class="header-action-btn">
<svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="currentColor" focusable="false" aria-hidden="true" class=""><path d="m20.832 14.445-1.7-2.555a2 2 0 0 0-1.667-.89H13v-1h4a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H6.535a2 2 0 0 0-1.664.89l-1.7 2.555a1 1 0 0 0 0 1.11l1.7 2.554A2 2 0 0 0 6.535 10H11v1H7a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h4v2a1 1 0 0 0 2 0v-2h4.465a2 2 0 0 0 1.664-.891l1.7-2.554a1 1 0 0 0 .003-1.11ZM5.2 6l1.335-2H17v4H6.535L5.2 6Zm12.265 11H7v-4h10.465l1.335 2-1.335 2Z" fill="currentColor"></path></svg>
               See what's new
            </button>
            <button type="button" class="header-action-btn">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" color="currentColor"
                    width="24px" height="24px" focusable="false" aria-hidden="true" class="">
                    <path fill="currentColor"
                        d="M14.35 2a1 1 0 0 1 0 2H6.49a2.54 2.54 0 0 0-2.57 2.5v7A2.54 2.54 0 0 0 6.49 16h1.43a1 1 0 0 1 1 1v1.74l2.727-2.48c.184-.167.424-.26.673-.26h5.03a2.54 2.54 0 0 0 2.57-2.5v-4a1 1 0 0 1 2 0v4a4.54 4.54 0 0 1-4.57 4.5h-4.643l-4.114 3.74A1.002 1.002 0 0 1 6.92 21v-3h-.43a4.54 4.54 0 0 1-4.57-4.5v-7A4.54 4.54 0 0 1 6.49 2zm6.414.6.725.726c.79.791.79 2.074 0 2.865l-5.812 5.794c-.128.128-.29.219-.465.263l-2.9.721q-.121.03-.247.031a.998.998 0 0 1-.969-1.244l.73-2.9a1 1 0 0 1 .263-.463L17.9 2.6a2.027 2.027 0 0 1 2.864 0m-1.412 1.413-.763.724L13.7 9.612l-.255 1.015 1.016-.252 5.616-5.6V4.74z">
                    </path>
                </svg>
               Give feedback
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
                <a href="#" class="text-dark me-2"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" color="currentColor" width="24px" height="24px" focusable="false"
                        aria-hidden="true" class="">
                        <path fill="currentColor"
                            d="m13.432 11.984 5.3-5.285a1 1 0 1 0-1.412-1.416l-5.3 5.285-5.285-5.3A1 1 0 1 0 5.319 6.68l5.285 5.3L5.3 17.265a1 1 0 1 0 1.412 1.416l5.3-5.285L17.3 18.7a1 1 0 1 0 1.416-1.412z">
                        </path>
                    </svg></a>
            </div>

        </div>
    </div>
                <div class="modal-body qbo-modal-body" style="padding-top: 70px; padding-bottom: 80px; background-color: #ffffff;">
    <div class="row qbo-form-container">
        @php
            $times = [];
            for($i = 0; $i < 24; $i++) {
                foreach(['00', '15', '30', '45'] as $min) {
                    $time = sprintf('%02d:%s', $i, $min);
                    $times[$time] = date('h:i A', strtotime("2020-01-01 $time"));
                }
            }
        @endphp
        {{ Form::open(['route' => 'timeActivity.store', 'class' => 'w-100']) }}
                        <div class="col-12">
                            <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
                            <div class="card shadow-none border-0 qbo-card">
                                <div class="card-body qbo-card-body">
                                    <div class="row qbo-row">
                                        <div class="col-md-6 qbo-left-column">
                                            <div class="form-group qbo-form-group" id="customer-box">
                                                {{ Form::label('user_id', __('Name'), ['class' => 'form-label qbo-label']) }}
                                                {{ Form::select('user_id', $employees, null, ['class' => 'form-control select2 qbo-select', 'id' => 'user_id', 'placeholder' => 'Select name']) }}
                                            </div>
                                            <div class="form-group qbo-form-group">
                                                {{ Form::label('customer_id', __('Customers'), ['class' => 'form-label qbo-label']) }}
                                                {{ Form::select('customer_id', $customers, null, ['class' => 'form-control select2 qbo-select', 'id' => 'customer_id', 'placeholder' => 'Select Customers']) }}
                                            </div>
                                            <div class="form-group qbo-form-group">
                                                {{ Form::label('service_id', __('Service'), ['class' => 'form-label qbo-label']) }}
                                                {{ Form::select('service_id', $services, null, ['class' => 'form-control select2 qbo-select', 'id' => 'service_id', 'placeholder' => 'Select service']) }}
                                            </div>
                                            <div class="form-group qbo-form-group qbo-checkbox-group">
                                                <div class="form-check form-check-inline qbo-checkbox-item">
                                                    <input class="form-check-input qbo-checkbox" type="checkbox" id="billable" name="billable" value="1" checked>
                                                    <label class="form-check-label qbo-checkbox-label" for="billable">{{ __('Billable (per hour)') }}</label>
                                                </div>
                                                <div class="form-check form-check-inline qbo-checkbox-item" id="rate_div">
                                                    <input class="form-control qbo-input-inline" type="number" id="rate" name="rate" placeholder="0.00" style="width: 100px; display: inline-block;">
                                                </div>
                                                <div class="form-check form-check-inline qbo-checkbox-item">
                                                    <input class="form-check-input qbo-checkbox" type="checkbox" id="taxable" name="taxable" value="1">
                                                    <label class="form-check-label qbo-checkbox-label" for="taxable">{{ __('Taxable') }}</label>
                                                </div>
                                            </div>
                                        </div>
                                        <style>/* Right column time section max 70% like QBO */
.time-section-70 {
    width: 70%;
    max-width: 70%;
}

/* On small screens, let it be full width */
@media (max-width: 768px) {
    .time-section-70 {
        width: 100%;
        max-width: 100%;
    }
}
</style>
                                        <div class="col-md-6 qbo-right-column">

    {{-- 70% WIDTH BLOCK (date / time / break / duration) --}}
    <div class="time-section-70 qbo-time-section">
        <div class="form-group qbo-form-group qbo-toggle-group">
            <div class="form-check form-switch qbo-switch">
                <input type="checkbox" class="form-check-input qbo-switch-input" id="time_toggle" name="time_toggle">
                <label class="form-check-label qbo-switch-label" for="time_toggle">
                    {{ __('Set start and end time') }}
                </label>
            </div>
        </div>

        <div class="form-group qbo-form-group">
            {{ Form::label('date', __('Start date'), ['class' => 'form-label qbo-label']) }}
            {{ Form::date('date', date('Y-m-d'), ['class' => 'form-control qbo-input', 'required' => 'required']) }}
        </div>

        <div class="row qbo-time-inputs-row" id="time_inputs" style="display: none;">
            <div class="col-md-6">
                <div class="form-group qbo-form-group">
                    {{ Form::label('start_time', __('Start time'), ['class' => 'form-label qbo-label']) }}
                    {{ Form::select('start_time', $times, null, ['class' => 'form-control select2 qbo-select', 'id' => 'start_time', 'placeholder' => 'Select Start Time']) }}
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group qbo-form-group">
                    {{ Form::label('end_time', __('End time'), ['class' => 'form-label qbo-label']) }}
                    {{ Form::select('end_time', $times, null, ['class' => 'form-control select2 qbo-select', 'id' => 'end_time', 'placeholder' => 'Select End Time']) }}
                </div>
            </div>
            <div class="col-md-12">
                <button type="button" class="btn btn-sm btn-outline-primary qbo-add-break-btn" id="add_break">
                    {{ __('Add break') }}
                </button>
                <div id="break_div" class="qbo-break-div" style="display: none; margin-top: 10px;">
                    {{ Form::label('break_duration', __('Break (hh:mm)'), ['class' => 'form-label qbo-label']) }}
                    {{ Form::text('break_duration', null, ['class' => 'form-control qbo-input', 'id' => 'break_duration', 'placeholder' => '00:00']) }}
                </div>
            </div>
        </div>

        <div class="form-group qbo-form-group" id="duration_div">
            {{ Form::label('duration', __('Duration (hh:mm)'), ['class' => 'form-label qbo-label']) }}
            {{ Form::text('duration', null, ['class' => 'form-control qbo-input', 'id' => 'duration', 'placeholder' => 'hh:mm']) }}
        </div>
    </div>

    {{-- NOTES – FULL WIDTH OF THE COLUMN --}}
    <div class="form-group qbo-form-group qbo-notes-group">
        {{ Form::label('notes', __('Notes'), ['class' => 'form-label qbo-label']) }}
        {{ Form::textarea('notes', null, ['class' => 'form-control qbo-textarea', 'rows' => 3]) }}
    </div>

</div>

                                    </div>
                                </div>
    <div class="modal-footer-custom fixed-footer">
        <!-- Left section: secondary actions -->
        <div class="footer-left d-flex align-items-center" style="gap:0px;">
                                <button type="button" class="btn btn-link text-success p-2 btn-cancel-custom" data-bs-dismiss="modal"
                                    style="
                    background: #fff;
                        border: none;
                        color: #00892E !important;
                        font-weight: 700;
                        padding: 6px 12px !important;
                        border-radius: 4px;
                        margin-left: 10px;
                        cursor: pointer;
                        font-size: 14px;
                        white-space: nowrap;
                                    ">Cancel</button>
                            </div>

                            <!-- Right section: primary actions -->
                            <div class="footer-right d-flex align-items-center gap-2">
                                <button type="submit" class="btn btn-light btn-sm-qbo" style="
                        color: #00892E;
                        border: 2px solid #00892E;
                        border-color: #00892E;
                        padding: 6px 28px !important;
                        font-size: 14px;
                        font-weight: 700;
                    ">Save</button>
            <div class="btn-group">
                <button type="submit"
                    class="btn btn-success btn-sm-qbo" style="padding: 6px 22px !important;">{{ __('Save and close') }}</button>
                <button type="button" style="padding: 7px 10px !important;" class="btn btn-success btn-sm-qbo dropdown-toggle dropdown-toggle-split"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="visually-hidden">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">{{ __('Save and new') }}</a></li>
                    {{-- <li><a class="dropdown-item" href="#">{{ __('Save and print') }}</a></li> --}}
                </ul>
            </div>
        </div>
    </div>

    <style>

                            .header-action-btn {
                                padding: 6px 12px;
                                font-weight: 700;
                                font-size: 16px;
                                color: #6B6C72;
                                background: transparent;
                                border: none;
                                border-radius: 4px;
                                cursor: pointer;
                                transition: all 0.2s;
                                display: flex;
                                align-items: center;
                                gap: 6px;
                            }

                            .header-action-btn:hover {
                                background: rgba(107, 108, 114, 0.1);
                                border-color: rgba(107, 108, 114, 0.1);
                            }


                            .toolbar-buttons {
                                display: flex;
                                gap: 5px;
                            }

                            .toolbar-btn {
                                border: none;
                                padding: 6px;
                                border-radius: 2px;
                                cursor: pointer;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                width: 32px;
                                height: 32px;
                                transition: 0.2s;
                                background: transparent;
                            }

                            .toolbar-btn:hover {
                                background: #f5f5f5;
                                border-color: #bcbcbc;
                            }

                            .toolbar-btn svg {
                                width: 20px;
                                height: 20px;
                                fill: currentColor;
                            }


                            #category-table thead tr {
                                border-bottom: 1px solid #cfd1d7 !important;
                            }

                            #category-table th {
                                border-right: 1px solid #e3e5eb !important;
                                border-bottom: 1px solid #e3e5eb !important;
                                border-top: 0px !important;
                                /* Header top open */
                                border-left: 0px !important;
                                /* Header left open */
                                padding: 12px 8px;
                                font-size: 13px;
                                font-weight: 600;
                                color: #5a5b5f;
                            }

                            #category-table td {
                                border-right: 1px solid #e3e5eb !important;
                                border-bottom: 1px solid #e3e5eb !important;
                                border-top: 0px !important;
                                /* Cells top open */
                                border-left: 0px !important;
                                /* Cells left open */
                                padding: 10px 8px;
                                font-size: 13px;
                                color: #2b2c30;
                                vertical-align: middle;
                            }

                            #category-table tbody tr {
                                background: white;
                            }

                            #category-table tbody tr:hover {
                                background: #fafafa;
                            }



                            .size {
                                width: 270px;
                            }

                            .field {
                                width: 185px;
                            }

                            .field1 {
                                width: 165px;
                            }

                            .TrowserHeader {
                                padding: 10px;
                            }

                            .txp-capability-expenseLayout-N1jWN header[class*='TrowserHeader-header'] button svg {
                                width: 33px;
                                height: 33px;
                            }

                            /* Container targeting */
                            .txp-capability-gridWrapper-dVsAS .txp-capability-gridButtons-Uh9M\+ [class*='Button-priority-primary'] {
                                background-color: var(--color-container-background-primary);
                                color: var(--color-text-primary);
                                border-color: var(--color-container-border-primary);
                                border-radius: 4px;
                                /* optional for rounded corners */
                                font-weight: 500;
                                cursor: pointer;
                                transition: all 0.2s ease-in-out;
                            }

                            /* Padding for label */
                            .Button-size-medium-6a785d2 .Button-label-6a785d2 {
                                padding-left: var(--padding-inline, 8px);
                                padding-right: var(--padding-inline, 8px);
                                display: inline-block;
                            }

                            /* Optional: Hover effect */
                            .Button-priority-primary-6a785d2:hover {
                                background-color: #6c6464c0;
                                /* QuickBooks blue hover */
                                color: #000000ff;

                            }
    </style>

    <style>
        /* Sticky footer at the bottom of the viewport */
        .fixed-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: #ffffff;
            padding: 0.75rem 1.5rem;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1050;
            /* above other content */
        }

        /* Left section links/buttons */
        .fixed-footer .footer-left {
            display: flex;
            align-items: center;
            /* gap: 1rem; */
        }

        /* Center section */
        .fixed-footer .footer-center {
            display: flex;
            align-items: center;
        }

        /* Right section action buttons */
        .fixed-footer .footer-right {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Cancel button custom style */
        .btn-cancel-custom {
            border: 1px solid #00892e;
            color: #00892e;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            background-color: transparent;
            font-weight: 500;
            transition: background-color 0.2s, color 0.2s;
        }

        .btn-cancel-custom:hover {
            background-color: #00892e;
            color: #fff;
        }

        /* Primary success button style */
        .btn-success {
            background-color: #2ca01c;
            border-color: #2ca01c;
            color: #fff;
        }

        .btn-success:hover {
            background-color: #25861b;
            border-color: #25861b;
        }

        /* Optional: adjust dropdown menu for split button */
        .fixed-footer .btn-group .dropdown-menu {
            min-width: auto;
        }
    </style>


    <style>
        .modal-footer-custom {
            background: #ffffff;
            padding: 0.75rem 1.5rem;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            /* Left / center / right */
            align-items: center;
        }

        /* Left section links/buttons */
        .modal-footer-custom .footer-left {
            display: flex;
            align-items: center;
            /* gap: 1rem; */
        }

        /* Center section (optional) */
        .modal-footer-custom .footer-center {
            display: flex;
            align-items: center;
        }

        /* Right section action buttons */
        .modal-footer-custom .footer-right {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Primary success button style */
        .modal-footer-custom .btn-success {
            background-color: #00892e;
            border-color: #00892e;
            color: #fff;
        }

        .modal-footer-custom .btn-success:hover {
            background-color: #25861b;
            border-color: #25861b;
        }

        /* Split button dropdown adjustments */
        .modal-footer-custom .btn-group .dropdown-menu {
            min-width: auto;
        }

        .btn-cancel-custom {
            border: 1px solid #00892e;
            /* green border */
            color: #00892e;
            /* text color green */
            padding: 0.25rem 0.75rem;
            /* some padding to look like a button */
            border-radius: 4px;
            /* slightly rounded corners */
            background-color: transparent;
            /* keep background white/transparent */
            font-weight: 500;
            transition: background-color 0.2s, color 0.2s;
        }
    </style>
                            </div>
                        </div>
                        {{ Form::close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
<style>
/* QBO Modal Body Styling */
.qbo-modal-body {
    background-color: #F4F5F8;
}

.qbo-form-container {
    max-width: 1200px;
    margin: 0 auto;
}

/* QBO Card Styling */
.qbo-card {
    background-color: #FFFFFF;
}

.qbo-card-body {
    padding: 32px 40px;
}

/* QBO Row and Column Spacing */
.qbo-row {
    margin-left: -16px;
    margin-right: -16px;
}

.qbo-left-column,
.qbo-right-column {
    padding-left: 16px;
    padding-right: 16px;
}

/* QBO Form Group */
.qbo-form-group {
    margin-bottom: 20px;
}

/* QBO Label Styling */
.qbo-label {
    display: block;
    margin-bottom: 6px;
    font-size: 13px;
    font-weight: 600;
    color: #393A3D;
    line-height: 1.4;
    letter-spacing: 0.01em;
}

/* QBO Input and Select Styling */
.qbo-input,
.qbo-select,
.qbo-textarea,
.select2-container--default .select2-selection--single {
    height: 36px !important;
    padding: 6px 12px !important;
    font-size: 14px !important;
    line-height: 1.5 !important;
    color: #393A3D !important;
    background-color: #FFFFFF !important;
    border: 1px solid #8D9096 !important;
    border-radius: 4px !important;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
    box-shadow: none !important;
}

.qbo-input:focus,
.qbo-select:focus,
.qbo-textarea:focus,
.select2-container--default.select2-container--focus .select2-selection--single,
.select2-container--default.select2-container--open .select2-selection--single {
    border-color: #0077C5 !important;
    outline: 0 !important;
    box-shadow: 0 0 0 3px rgba(0, 119, 197, 0.15) !important;
}

/* QBO Textarea Specific */
.qbo-textarea {
    min-height: 100px !important;
    height: auto !important;
    resize: vertical;
    padding: 10px 12px !important;
}

/* QBO Select2 Styling */
.select2-container--default .select2-selection--single {
    background-color: #FFFFFF !important;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #393A3D !important;
    line-height: 34px !important;
    padding-left: 12px !important;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 34px !important;
    right: 8px !important;
}

.select2-container--default .select2-selection--single .select2-selection__placeholder {
    color: #6B6C72 !important;
}

/* QBO Checkbox Styling */
.qbo-checkbox {
    width: 16px !important;
    height: 16px !important;
    margin-top: 0.2em;
    border: 2px solid #8D9096 !important;
    border-radius: 3px !important;
    cursor: pointer;
    background-color: #FFFFFF !important;
}

.qbo-checkbox:checked {
    background-color: #00892E !important;
    border-color: #00892E !important;
}

.qbo-checkbox:focus {
    border-color: #0077C5 !important;
    box-shadow: 0 0 0 3px rgba(0, 119, 197, 0.15) !important;
}

.qbo-checkbox-label {
    font-size: 13px;
    color: #393A3D;
    font-weight: 500;
    margin-left: 8px;
    cursor: pointer;
    user-select: none;
}

.qbo-checkbox-group {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px;
}

.qbo-checkbox-item {
    display: inline-flex;
    align-items: center;
    margin-right: 0;
}

/* QBO Switch Styling */
.qbo-switch {
    display: flex;
    align-items: center;
}

.qbo-switch-input {
    width: 44px !important;
    height: 24px !important;
    border-radius: 24px !important;
    background-color: #CBD2D9 !important;
    border: none !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%23fff'/%3e%3c/svg%3e") !important;
    background-position: left 2px center !important;
    background-size: 20px 20px !important;
    cursor: pointer;
    margin-top: 0 !important;
}

.qbo-switch-input:checked {
    background-color: #00892E !important;
    border-color: #00892E !important;
    background-position: right 2px center !important;
}

.qbo-switch-input:focus {
    box-shadow: 0 0 0 3px rgba(0, 137, 46, 0.15) !important;
}

.qbo-switch-label {
    font-size: 13px;
    font-weight: 500;
    color: #393A3D;
    margin-left: 12px;
    cursor: pointer;
    user-select: none;
}

/* QBO Inline Input */
.qbo-input-inline {
    width: 100px;
    display: inline-block;
    vertical-align: middle;
    height: 32px !important;
    padding: 4px 8px !important;
    font-size: 13px !important;
    border: 1px solid #8D9096 !important;
    border-radius: 4px !important;
}

/* QBO Add Break Button */
.qbo-add-break-btn {
    color: #393A3D !important;
    border: 2px solid #6B6C72 !important;
    background-color: #FFFFFF !important;
    font-weight: 600 !important;
    padding: 4px 12px !important;
    border-radius: 4px !important;
    font-size: 13px !important;
    transition: all 0.2s;
}

.qbo-add-break-btn:hover {
    background-color: #F4F5F8 !important;
    border-color: #393A3D !important;
    color: #393A3D !important;
}

/* QBO Time Section */
.qbo-time-section {
    width: 70%;
    max-width: 70%;
}

@media (max-width: 768px) {
    .qbo-time-section {
        width: 100%;
        max-width: 100%;
    }
}

/* QBO Time Inputs Row */
.qbo-time-inputs-row {
    margin-left: -8px;
    margin-right: -8px;
}

.qbo-time-inputs-row > [class*="col-"] {
    padding-left: 8px;
    padding-right: 8px;
}

/* QBO Placeholder Text */
.qbo-input::placeholder,
.qbo-select::placeholder,
.qbo-textarea::placeholder {
    color: #6B6C72;
    opacity: 1;
}

/* QBO Input Number Spinner Remove */
input[type="number"].qbo-input::-webkit-inner-spin-button,
input[type="number"].qbo-input::-webkit-outer-spin-button,
input[type="number"].qbo-input-inline::-webkit-inner-spin-button,
input[type="number"].qbo-input-inline::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

input[type="number"].qbo-input,
input[type="number"].qbo-input-inline {
    -moz-appearance: textfield;
}

/* QBO Date Input */
input[type="date"].qbo-input {
    padding-right: 12px;
}

/* QBO Disabled State */
.qbo-input:disabled,
.qbo-select:disabled,
.qbo-textarea:disabled {
    background-color: #F4F5F8 !important;
    color: #9EA0A5 !important;
    cursor: not-allowed;
    border-color: #CBD2D9 !important;
}

/* Legacy Compatibility - Keep existing classes working */
.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 6px;
    font-size: 13px;
    font-weight: 600;
    color: #393A3D;
    line-height: 1.4;
}

.form-control,
.form-select {
    height: 36px !important;
    padding: 6px 12px !important;
    font-size: 14px !important;
    line-height: 1.5 !important;
    color: #393A3D !important;
    background-color: #FFFFFF !important;
    border: 1px solid #8D9096 !important;
    border-radius: 4px !important;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
    box-shadow: none !important;
}

.form-control:focus,
.form-select:focus {
    border-color: #00892E !important;
    outline: 0 !important;
    box-shadow: 0 0 0 1.1px #00892E !important;
}

textarea.form-control {
    min-height: 100px !important;
    height: auto !important;
    resize: vertical;
    padding: 10px 12px !important;
}

.form-check-input {
    width: 16px;
    height: 16px;
    margin-top: 0.2em;
    border: 2px solid #8D9096;
    border-radius: 3px;
    cursor: pointer;
}

.form-check-input:checked {
    background-color: #00892E;
    border-color: #00892E;
}

.form-check-input:focus {
    border-color: #0077C5;
    box-shadow: 0 0 0 3px rgba(0, 119, 197, 0.15);
}

.form-check-label {
    font-size: 13px;
    color: #393A3D;
    font-weight: 500;
    margin-left: 8px;
    cursor: pointer;
    user-select: none;
}

.form-switch .form-check-input {
    width: 44px;
    height: 24px;
    border-radius: 24px;
    background-color: #CBD2D9;
    border: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%23fff'/%3e%3c/svg%3e");
    background-position: left 2px center;
    background-size: 20px 20px;
    cursor: pointer;
}

.form-switch .form-check-input:checked {
    background-color: #00892E;
    border-color: #00892E;
    background-position: right 2px center;
}

.form-switch .form-check-input:focus {
    box-shadow: 0 0 0 3px rgba(0, 137, 46, 0.15);
}

.form-check-inline {
    display: inline-flex;
    align-items: center;
    margin-right: 16px;
}

.card {
    border: none !important;
    box-shadow: none !important;
}

.card-body {
    padding: 24px;
}

.form-control::placeholder,
.form-select::placeholder {
    color: #6B6C72;
    opacity: 1;
}

input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

input[type="number"] {
    -moz-appearance: textfield;
}

input[type="date"].form-control {
    padding-right: 12px;
}

.form-control:disabled,
.form-select:disabled {
    background-color: #F4F5F8 !important;
    color: #9EA0A5 !important;
    cursor: not-allowed;
}

.row {
    margin-left: -12px;
    margin-right: -12px;
}

.row > [class*="col-"] {
    padding-left: 12px;
    padding-right: 12px;
}

.modal-body {
    padding-left: 24px;
    padding-right: 24px;
}

#rate {
    width: 100px;
    display: inline-block;
    vertical-align: middle;
}

#add_break {
    color: #393A3D;
    border: 2px solid #6B6C72;
    background-color: #FFFFFF;
    font-weight: 600;
    padding: 4px 12px !important;
    border-radius: 4px;
    font-size: 13px;
}

#add_break:hover {
    background-color: #F4F5F8;
}

.select2-container .select2-selection--single {
    height: 36px !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 34px !important;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 34px !important;
}

/* Layout Spacing Adjustments */
.card-body > .row {
    margin-left: -40px;
    margin-right: -40px;
}

.card-body > .row > .col-md-6 {
    padding-left: 40px;
    padding-right: 30px;
}

/* Reset spacing for nested time inputs row */
#time_inputs {
    margin-left: -12px;
    margin-right: -12px;
}

#time_inputs .col-md-6 {
    padding-left: 12px;
    padding-right: 12px;
}
</style>
@endsection

@push('script-page')
    <script>
        $(document).ready(function() {
            var timeActivityModal = new bootstrap.Modal(document.getElementById('time-activity-modal'), {
                backdrop: 'static',
                keyboard: false
            });
            timeActivityModal.show();

            $('#time_toggle').change(function() {
                if ($(this).is(':checked')) {
                    $('#time_inputs').show();
                    $('#duration_div').hide();
                } else {
                    $('#time_inputs').hide();
                    $('#duration_div').show();
                }
            });

            $('#billable').change(function() {
                if ($(this).is(':checked')) {
                    $('#rate_div').show();
                } else {
                    $('#rate_div').hide();
                }
            });

            $('#add_break').click(function() {
                $('#break_div').toggle();
            });

            function calculateDuration() {
                var startTime = $('#start_time').val();
                var endTime = $('#end_time').val();
                var breakDuration = $('#break_duration').val();

                if (startTime && endTime) {
                    var start = new Date("01/01/2007 " + startTime);
                    var end = new Date("01/01/2007 " + endTime);

                    var diff = (end - start) / 60000; // difference in minutes

                    if (breakDuration) {
                        var breakParts = breakDuration.split(':');
                        var breakMinutes = parseInt(breakParts[0]) * 60 + parseInt(breakParts[1]);
                        diff -= breakMinutes;
                    }

                    if (diff > 0) {
                        var hours = Math.floor(diff / 60);
                        var minutes = diff % 60;
                        $('#duration').val(hours + ':' + (minutes < 10 ? '0' : '') + minutes);
                    } else {
                        $('#duration').val('');
                    }
                }
            }

            $('#start_time, #end_time, #break_duration').change(calculateDuration);
        });
    </script>
@endpush
