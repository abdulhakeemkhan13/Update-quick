@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Estimates') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Estimates') }}</li>
@endsection

{{-- @section('action-btn')
    <div class="float-end">

        <a href="{{ route('proposal.export') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
            title="{{ __('Export') }}">
            <i class="ti ti-file-export"></i>
        </a>

        @can('create proposal')
            <a href="{{ route('proposal.create', 0) }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                title="{{ __('Create') }}">
                <i class="ti ti-plus"></i>
            </a>
        @endcan
    </div>

@endsection --}}
@push('css-page')
@endpush
@push('script-page')
@endpush
@section('content')
    {{-- Include Sales Tabs --}}
    @include('transaction.sales-tabs')

    <div class="row">
        <div class="col-sm-12">
            <div class="mt-2 mb-2" id="multiCollapseExample1">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="card-body">
                        {{ Form::open(['route' => ['proposal.index'], 'method' => 'GET', 'id' => 'frm_submit']) }}
                        <div class="row d-flex align-items-center justify-content-start">
                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 mr-2">
                                <div class="btn-box">
                                    {{ Form::label('issue_date', __('Date'), ['class' => 'form-label']) }}
                                    {{ Form::text('issue_date', isset($_GET['issue_date']) ? $_GET['issue_date'] : null, ['class' => 'form-control month-btn', 'id' => 'pc-daterangepicker-1']) }}
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 mr-2">
                                <div class="btn-box">
                                    {{ Form::label('status', __('Status'), ['class' => 'form-label']) }}
                                    {{ Form::select('status', ['' => 'Select Status'] + $status, isset($_GET['status']) ? $_GET['status'] : '', ['class' => 'form-control select auto-filter']) }}
                                </div>
                            </div>
                            {{-- <div class="col-auto float-end ms-2 mt-4">
                                <a href="#" class="btn btn-sm btn-primary"
                                    onclick="document.getElementById('frm_submit').submit(); return false;"
                                    data-bs-toggle="tooltip" data-original-title="{{ __('apply') }}">
                                    <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                </a>
                                <a href="{{ route('proposal.index') }}" class="btn btn-sm btn-danger"
                                    data-bs-toggle="tooltip" title="{{ __('Reset') }}">
                                    <span class="btn-inner--icon"><i class="ti ti-trash-off text-white "></i></span>
                                </a>
                            </div> --}}
                        </div>
                        {{ Form::close() }}
                    </div>
                    <div class="col-auto mt-4">
                        @can('create proposal')
                            <a href="#" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                data-bs-target="#createProposalModal" data-bs-toggle="tooltip" title="{{ __('Create') }}">
                                {{ __('Create Estimate') }}
                                <i class="ti ti-plus"></i>
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-submit form when select filters change (status)
            const selectFilterElements = document.querySelectorAll('select.auto-filter');

            selectFilterElements.forEach(function(element) {
                element.addEventListener('change', function() {
                    document.getElementById('frm_submit').submit();
                });
            });

            // Handle date field with delay to allow proper date selection
            const dateField = document.getElementById('pc-daterangepicker-1');
            let dateTimeout;

            if (dateField) {
                dateField.addEventListener('change', function() {
                    // Clear any existing timeout
                    clearTimeout(dateTimeout);

                    // Set a delay to allow user to finish selecting date
                    dateTimeout = setTimeout(function() {
                        document.getElementById('frm_submit').submit();
                    }, 1000); // 1 second delay
                });

                // Also submit when user clicks away from the date field (blur event)
                dateField.addEventListener('blur', function() {
                    clearTimeout(dateTimeout);
                    if (this.value) {
                        document.getElementById('frm_submit').submit();
                    }
                });
            }
        });
    </script>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th> {{ __('Estimate') }}</th>
                                    {{--                                @if (!\Auth::guard('customer')->check()) --}}
                                    {{--                                    <th> {{__('Customer')}}</th> --}}
                                    {{--                                @endif --}}
                                    <th> {{ __('Category') }}</th>
                                    <th> {{ __('Issue Date') }}</th>
                                    <th> {{ __('Status') }}</th>
                                    @if (Gate::check('edit proposal') || Gate::check('delete proposal') || Gate::check('show proposal'))
                                        <th width="10%"> {{ __('Action') }}</th>
                                    @endif
                                    {{-- <th>
                                    <td class="barcode">
                                        {!! DNS1D::getBarcodeHTML($invoice->sku, "C128",1.4,22) !!}
                                        <p class="pid">{{$invoice->sku}}</p>
                                    </td>
                                </th> --}}
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($proposals as $proposal)
                                    <tr class="font-style">
                                        <td class="Id">
                                            <a href="{{ route('proposal.show', \Crypt::encrypt($proposal->id)) }}"
                                                class="btn btn-outline-primary">{{ AUth::user()->proposalNumberFormat($proposal->proposal_id) }}
                                            </a>
                                        </td>

                                        <td>{{ !empty($proposal->category) ? $proposal->category->name : '' }}</td>
                                        <td>{{ Auth::user()->dateFormat($proposal->issue_date) }}</td>
                                        <td>
                                            @if ($proposal->status == 0)
                                                <span
                                                    class="status_badge badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                            @elseif($proposal->status == 1)
                                                <span
                                                    class="status_badge badge bg-info p-2 px-3 rounded">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                            @elseif($proposal->status == 2)
                                                <span
                                                    class="status_badge badge bg-success p-2 px-3 rounded">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                            @elseif($proposal->status == 3)
                                                <span
                                                    class="status_badge badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                            @elseif($proposal->status == 4)
                                                <span
                                                    class="status_badge badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                            @endif
                                        </td>
                                        @if (Gate::check('edit proposal') || Gate::check('delete proposal') || Gate::check('show proposal'))
                                            <td class="Action">
                                                @if ($proposal->is_convert == 0)
                                                    @can('convert invoice')
                                                        <div class="action-btn bg-warning ms-2">
                                                            {!! Form::open([
                                                                'method' => 'get',
                                                                'route' => ['proposal.convert', $proposal->id],
                                                                'id' => 'proposal-form-' . $proposal->id,
                                                            ]) !!}

                                                            <a href="#"
                                                                class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                                data-bs-toggle="tooltip" title="{{ __('Convert Invoice') }}"
                                                                data-original-title="{{ __('Convert to Invoice') }}"
                                                                data-original-title="{{ __('Delete') }}"
                                                                data-confirm="{{ __('You want to confirm convert to invoice. Press Yes to continue or Cancel to go back') }}"
                                                                data-confirm-yes="document.getElementById('proposal-form-{{ $proposal->id }}').submit();">
                                                                <i class="ti ti-exchange text-white"></i>
                                                                {!! Form::close() !!}
                                                            </a>
                                                        </div>
                                                    @endcan
                                                @else
                                                    @can('show invoice')
                                                        <div class="action-btn bg-warning ms-2">
                                                            <a href="{{ route('invoice.show', \Crypt::encrypt($proposal->converted_invoice_id)) }}"
                                                                class="mx-3 btn btn-sm  align-items-center"
                                                                data-bs-toggle="tooltip"
                                                                title="{{ __('Already convert to Invoice') }}"
                                                                data-original-title="{{ __('Already convert to Invoice') }}">
                                                                <i class="ti ti-file text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endcan
                                                @endif
                                                @can('duplicate proposal')
                                                    <div class="action-btn bg-success ms-2">
                                                        {!! Form::open([
                                                            'method' => 'get',
                                                            'route' => ['proposal.duplicate', $proposal->id],
                                                            'id' => 'duplicate-form-' . $proposal->id,
                                                        ]) !!}

                                                        <a href="#"
                                                            class="mx-3 btn btn-sm  align-items-center bs-pass-para"
                                                            data-bs-toggle="tooltip" title="{{ __('Duplicate') }}"
                                                            data-original-title="{{ __('Duplicate') }}"
                                                            data-original-title="{{ __('Delete') }}"
                                                            data-confirm="{{ __('You want to confirm duplicate this invoice. Press Yes to continue or Cancel to go back') }}"
                                                            data-confirm-yes="document.getElementById('duplicate-form-{{ $proposal->id }}').submit();">
                                                            <i class="ti ti-copy text-white text-white"></i>
                                                            {!! Form::close() !!}
                                                        </a>
                                                    </div>
                                                @endcan
                                                @can('show proposal')
                                                    <div class="action-btn bg-info ms-2">
                                                        <a href="{{ route('proposal.show', \Crypt::encrypt($proposal->id)) }}"
                                                            class="mx-3 btn btn-sm  align-items-center" data-bs-toggle="tooltip"
                                                            title="{{ __('Show') }}"
                                                            data-original-title="{{ __('Detail') }}">
                                                            <i class="ti ti-eye text-white text-white"></i>
                                                        </a>
                                                    </div>
                                                @endcan
                                                @can('edit proposal')
                                                    <div class="action-btn bg-primary ms-2">
                                                        <a href="#"
                                                            onclick="openProposalModal('{{ route('proposal.edit', \Crypt::encrypt($proposal->id)) }}', 'edit')"
                                                            class="mx-3 btn btn-sm align-items-center"
                                                            data-bs-toggle="tooltip" title="{{ __('Edit') }}"
                                                            data-original-title="{{ __('Edit') }}">
                                                            <i class="ti ti-pencil text-white"></i>
                                                        </a>
                                                    </div>
                                                @endcan

                                                @can('delete proposal')
                                                    <div class="action-btn bg-danger ms-2">
                                                        {!! Form::open([
                                                            'method' => 'DELETE',
                                                            'route' => ['proposal.destroy', $proposal->id],
                                                            'id' => 'delete-form-' . $proposal->id,
                                                        ]) !!}

                                                        <a href="#"
                                                            class="mx-3 btn btn-sm  align-items-center bs-pass-para"
                                                            data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                            data-original-title="{{ __('Delete') }}"
                                                            data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                            data-confirm-yes="document.getElementById('delete-form-{{ $proposal->id }}').submit();">
                                                            <i class="ti ti-trash text-white text-white"></i>
                                                        </a>
                                                        {!! Form::close() !!}
                                                    </div>
                                                @endcan
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Proposal Create Modal --}}
    <div class="modal fade qb-proposal-modal" id="createProposalModal" tabindex="-1"
        aria-labelledby="createProposalModalLabel" aria-hidden="true" style="z-index: 1200;">
        <div class="modal-dialog qb-modal-dialog" style="max-width: 100vw; margin: 0; height: 100vh; max-height: 100vh;">
            <div class="modal-content qb-modal-content"
                style="height: 100vh; max-height: 100vh; border: none; border-radius: 0; display: flex; flex-direction: column;">
                <div class="modal-body p-0" id="createProposalModalBody"
                    style="flex: 1; overflow-y: auto; max-height: calc(100vh - 60px);">
                    <!-- Content will be loaded here via AJAX -->
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">{{ __('Loading...') }}</span>
                        </div>
                        <p class="mt-2">{{ __('Loading ...') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function openProposalModal(url, mode) {
            // Load content into modal
            $.ajax({
                url: url,
                type: 'GET',
                success: function(data) {
                    $('#createProposalModalBody').html(data);
                    // Update browser URL
                    window.history.pushState({}, '', url);
                    // Mark content as loaded to prevent create content override
                    $('#createProposalModal').data('contentLoaded', true);
                    $('#createProposalModal').data('isEdit', true); // Flag to prevent URL override
                },
                error: function() {
                    $('#createProposalModalBody').html(
                        '<div class="text-center py-5"><p class="text-danger">{{ __('Error loading proposal form') }}</p></div>'
                    );
                }
            });
            // Show modal using Bootstrap 5 API
            const modalElement = document.getElementById('createProposalModal');
            if (modalElement) {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            }
        }
    </script>
    <script>
        $(document).ready(function() {
            // Check if URL has 'create' or 'edit' parameter
            const urlParams = new URLSearchParams(window.location.search);
            const showModal = urlParams.has('create');
            const isEditMode = urlParams.has('edit');

            // Function to load modal content
            function loadModalContent() {
                if ($('#createProposalModal').data('loading')) return;
                $('#createProposalModal').data('loading', true);

                $.ajax({
                    url: '{{ route('proposal.create', 0) }}',
                    type: 'GET',
                    success: function(data) {
                        $('#createProposalModalBody').html(data);
                        $('#createProposalModal').data('contentLoaded', true);
                    },
                    error: function() {
                        $('#createProposalModalBody').html(
                            '<div class="text-center py-5"><p class="text-danger">{{ __('Error loading proposal form') }}</p></div>'
                        );
                    },
                    complete: function() {
                        $('#createProposalModal').data('loading', false);
                    }
                });
            }

            // Show modal on page load if URL parameter is set (but not if in edit mode)
            if (showModal && !isEditMode) {
                loadModalContent();
                const modalElement = document.getElementById('createProposalModal');
                if (modalElement) {
                    const modal = new bootstrap.Modal(modalElement);
                    modal.show();
                }
            }

            // Load create proposal modal content when manually opened
            $('#createProposalModal').on('show.bs.modal', function(e) {
                // If the modal is being opened by the edit button (which loads its own content),
                // we should NOT load the create content.
                // The edit button calls openProposalModal which sets contentLoaded to true.
                if (!$(this).data('contentLoaded') && !showModal) {
                    loadModalContent();
                }

                // Update URL to maintain state ONLY if it's the create modal
                // We can check if we are in "create" mode by checking if we just loaded the create content
                // or if the URL param is already set.
                // However, for simplicity, let's just set it if we are not in edit mode.
                if (!$(this).data('isEdit')) {
                    const newUrl = new URL(window.location);
                    newUrl.searchParams.set('create', '1');
                    window.history.pushState({}, '', newUrl);
                }
            });

            // Remove URL parameter when modal is closed
            $('#createProposalModal').on('hidden.bs.modal', function() {
                const newUrl = new URL(window.location);
                newUrl.searchParams.delete('create');
                newUrl.searchParams.delete('edit');
                window.history.pushState({}, '', newUrl);
                // Reset content loaded flag and edit flag
                $(this).data('contentLoaded', false);
                $(this).data('isEdit', false);
                $('#createProposalModalBody').html(
                    '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">{{ __('Loading...') }}</span></div><p class="mt-2">{{ __('Loading ...') }}</p></div>'
                );
            });

            // Handle edit proposal button click
            $(document).on('click', '.btn-edit-proposal', function(e) {
                e.preventDefault();
                var url = $(this).attr('href');

                // Update URL to include edit parameter
                const newUrl = new URL(window.location);
                newUrl.searchParams.set('edit', 'true');
                window.history.pushState({}, '', newUrl);

                // Mark modal as edit mode
                $('#createProposalModal').data('isEdit', true);
                $('#createProposalModal').data('contentLoaded', true);

                // Show loading
                $('#createProposalModalBody').html(
                    '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Loading proposal...</p></div>'
                );

                // Create OR get existing modal instance
                window.createProposalModal = bootstrap.Modal.getOrCreateInstance(document.getElementById(
                    'createProposalModal'));
                window.createProposalModal.show();

                // Load edit form
                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(response) {
                        $('#createProposalModalBody').html(response);
                    },
                    error: function(xhr) {
                        $('#createProposalModalBody').html(
                            '<div class="alert alert-danger">Error loading proposal. Please try again.</div>'
                        );
                    }
                });
            });

        });
    </script>
@endsection
