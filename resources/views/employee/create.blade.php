@extends('layouts.admin')

@section('page-title')
    {{ __('Create Employee') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ url('employee') }}">{{ __('Employee') }}</a></li>
    <li class="breadcrumb-item">{{ __('Create Employee') }}</li>
@endsection


@section('content')
    <div class="row">
        <div class="">
            <div class="">
                <div class="row">
                </div>
                {{ Form::open(['route' => ['employee.store'], 'method' => 'post', 'enctype' => 'multipart/form-data']) }}
                <div class="row">
                    <div class="col-md-6">
                        <div class="card em-card">
                            <div class="card-header">
                                <h5>{{ __('Personal Detail') }}</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        {!! Form::label('name', __('Name'), ['class' => 'form-label']) !!}<span class="text-danger pl-1">*</span>
                                        {!! Form::text('name', old('name'), [
                                            'class' => 'form-control',
                                            'required' => 'required',
                                            'placeholder' => 'Enter employee name',
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('phone', __('Phone'), ['class' => 'form-label']) !!}<span class="text-danger pl-1">*</span>
                                        {!! Form::text('phone', old('phone'), [
                                            'class' => 'form-control',
                                            'required' => 'required',
                                            'placeholder' => 'Enter employee phone',
                                        ]) !!}
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('dob', __('Date of Birth'), ['class' => 'form-label']) !!}<span class="text-danger pl-1">*</span>
                                            {{ Form::date('dob', null, ['class' => 'form-control ', 'required' => 'required', 'autocomplete' => 'off', 'placeholder' => 'Select Date of Birth']) }}
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('gender', __('Gender'), ['class' => 'form-label', 'required' => 'required']) !!}<span class="text-danger pl-1">*</span>
                                            <div class="d-flex radio-check">
                                                <div class="custom-control custom-radio custom-control-inline">
                                                    <input type="radio" id="g_male" value="Male" name="gender"
                                                        class="form-check-input" checked>
                                                    <label class="form-check-label"
                                                        for="g_male">{{ __('Male') }}</label>
                                                </div>
                                                <div class="custom-control custom-radio ms-1 custom-control-inline">
                                                    <input type="radio" id="g_female" value="Female" name="gender"
                                                        class="form-check-input">
                                                    <label class="form-check-label"
                                                        for="g_female">{{ __('Female') }}</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('email', __('Email'), ['class' => 'form-label']) !!}<span class="text-danger pl-1">*</span>
                                        {!! Form::email('email', old('email'), [
                                            'class' => 'form-control',
                                            'required' => 'required',
                                            'placeholder' => 'Enter employee email',
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('password', __('Password'), ['class' => 'form-label']) !!}<span class="text-danger pl-1">*</span>
                                        {!! Form::password('password', [
                                            'class' => 'form-control',
                                            'required' => 'required',
                                            'placeholder' => 'Enter employee new password',
                                        ]) !!}
                                    </div>
                                </div>
                                <div class="form-group">
                                    {!! Form::label('address', __('Address'), ['class' => 'form-label']) !!}<span class="text-danger pl-1">*</span>
                                    {!! Form::textarea('address', old('address'), [
                                        'class' => 'form-control',
                                        'rows' => 2,
                                        'placeholder' => 'Enter employee address',
                                        'required' => 'required',
                                    ]) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card em-card">
                            <div class="card-header">
                                <h5>{{ __('Company Detail') }}</h5>
                            </div>
                            <div class="card-body employee-detail-create-body">
                                <div class="row">
                                    @csrf
                                    <div class="form-group ">
                                        {!! Form::label('employee_id', __('Employee ID'), ['class' => 'form-label']) !!}
                                        {!! Form::text('employee_id', $employeesId, ['class' => 'form-control', 'disabled' => 'disabled']) !!}
                                    </div>

                                    <div class="form-group col-md-6">
                                        {{ Form::label('branch_id', __('Select Branch*'), ['class' => 'form-label']) }}
                                        <div class="form-icon-user">
                                            {{-- Branch --}}
                                            @php
                                                $branchesOptions =
                                                    ['__add__' => '➕  Add New'] +
                                                    ($branches instanceof \Illuminate\Support\Collection
                                                        ? $branches->all()
                                                        : (array) $branches);
                                            @endphp
                                            {{ Form::select('branch_id', $branchesOptions, null, [
                                                'class' => 'form-control select2',
                                                'placeholder' => 'Select Branch',
                                                'required' => 'required',
                                                'data-create-url' => route('branch.create'),
                                                'id' => 'branch_id',
                                                'data-create-title' => __('Create Branch'),
                                            ]) }}

                                        </div>
                                    </div>

                                    <div class="form-group col-md-6">
                                        {{ Form::label('department_id', __('Select Department*'), ['class' => 'form-label']) }}
                                        <div class="form-icon-user">
                                            {{-- Department --}}
                                            @php
                                                $departmentsOptions =
                                                    ['__add__' => '➕  Add New'] +
                                                    ($departments instanceof \Illuminate\Support\Collection
                                                        ? $departments->all()
                                                        : (array) $departments);
                                            @endphp
                                            {{ Form::select('department_id', $departmentsOptions, null, [
                                                'class' => 'form-control select2',
                                                'id' => 'department_id',
                                                'placeholder' => 'Select Department',
                                                'required' => 'required',
                                                'data-create-url' => route('department.create'),
                                                'data-create-title' => __('Create Department'),
                                            ]) }}

                                        </div>
                                    </div>

                                    <div class="form-group col-md-6">
                                        {{ Form::label('designation_id', __('Select Designation'), ['class' => 'form-label']) }}
                                        <div class="form-icon-user designation_div">
                                            {{-- Designation (initial render) --}}
                                            @php
                                                $designationsOptions =
                                                    ['__add__' => '➕  Add New'] +
                                                    ($designations instanceof \Illuminate\Support\Collection
                                                        ? $designations->all()
                                                        : (array) $designations);
                                            @endphp
                                            {{ Form::select('designation_id', $designationsOptions, null, [
                                                'class' => 'form-control select2 designation_id',
                                                'id' => 'designation_id',
                                                'placeholder' => 'Select Designation',
                                                'required' => 'required',
                                                'data-create-url' => route('designation.create'),
                                                'data-create-title' => __('Create Designation'),
                                            ]) }}

                                        </div>
                                    </div>

                                    <div class="form-group col-md-6">
                                        {!! Form::label('biometric_emp_id', __('Employee Code'), ['class' => 'form-label']) !!}
                                        {!! Form::text('biometric_emp_id', old('biometric_emp_id'), [
                                            'class' => 'form-control',
                                            'placeholder' => 'Enter Employee Code',
                                            'required' => 'required',
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('company_doj', __('Company Date Of Joining'), ['class' => '  form-label']) !!}
                                        {{ Form::date('company_doj', null, ['class' => 'form-control ', 'required' => 'required', 'autocomplete' => 'off', 'placeholder' => 'Select company date of joining']) }}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('report_to', 'Report To', ['class' => 'form-label']) !!}
                                        {{ Form::select('report_to', $employee_report, null, ['class' => 'form-control select']) }}
                                    </div>

                                    @if (!$customFields->isEmpty())
                                        @include('customFields.formBuilder')
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Documents --}}
                <div class="row">
                    <div class="col-md-6 ">
                        <div class="card em-card">
                            <div class="card-header">
                                <h5>{{ __('Document') }}</h5>
                            </div>
                            <div class="card-body employee-detail-create-body">
                                @foreach ($documents as $key => $document)
                                    <div class="row">
                                        <div class="form-group col-12 d-flex">
                                            <div class="float-left col-4">
                                                <label for="document" class="float-left pt-1 form-label">
                                                    {{ $document->name }}
                                                    @if ($document->is_required == 1)
                                                        <span class="text-danger">*</span>
                                                    @endif
                                                </label>
                                            </div>
                                            <div class="float-right col-8">
                                                <input type="hidden" name="emp_doc_id[{{ $document->id }}]"
                                                    value="{{ $document->id }}">
                                                <div class="choose-files">
                                                    <label for="document[{{ $document->id }}]">
                                                        <div class=" bg-primary document ">
                                                            <i class="ti ti-upload "></i>{{ __('Choose file here') }}
                                                        </div>
                                                        <input type="file"
                                                            class="form-control file file-validate d-none @error('document') is-invalid @enderror"
                                                            @if ($document->is_required == 1) required @endif
                                                            name="document[{{ $document->id }}]"
                                                            id="document[{{ $document->id }}]"
                                                            data-filename="{{ $document->id . '_filename' }}"
                                                            onchange="document.getElementById('{{ 'blah' . $key }}').src = window.URL.createObjectURL(this.files[0])">
                                                        <p class="file-error text-danger"></p>
                                                    </label>
                                                    <img id="{{ 'blah' . $key }}" src="" width="50%" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Bank --}}
                    <div class="col-md-6 ">
                        <div class="card em-card">
                            <div class="card-header">
                                <h5>{{ __('Bank Account Detail') }}</h5>
                            </div>
                            <div class="card-body employee-detail-create-body">
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        {!! Form::label('account_holder_name', __('Account Holder Name'), ['class' => 'form-label']) !!}
                                        {!! Form::text('account_holder_name', old('account_holder_name'), [
                                            'class' => 'form-control',
                                            'placeholder' => 'Enter account holder name',
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('account_number', __('Account Number'), ['class' => 'form-label']) !!}
                                        {!! Form::number('account_number', old('account_number'), [
                                            'class' => 'form-control',
                                            'placeholder' => 'Enter account number',
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('bank_name', __('Bank Name'), ['class' => 'form-label']) !!}
                                        {!! Form::text('bank_name', old('bank_name'), ['class' => 'form-control', 'placeholder' => 'Enter bank name']) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('bank_identifier_code', __('Bank Identifier Code'), ['class' => 'form-label']) !!}
                                        {!! Form::text('bank_identifier_code', old('bank_identifier_code'), [
                                            'class' => 'form-control',
                                            'placeholder' => 'Enter bank identifier code',
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('branch_location', __('Branch Location'), ['class' => 'form-label']) !!}
                                        {!! Form::text('branch_location', old('branch_location'), [
                                            'class' => 'form-control',
                                            'placeholder' => 'Enter branch location',
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('tax_payer_id', __('Tax Payer Id'), ['class' => 'form-label']) !!}
                                        {!! Form::text('tax_payer_id', old('tax_payer_id'), [
                                            'class' => 'form-control',
                                            'placeholder' => 'Enter tax payer id',
                                        ]) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="float-end">
                <button type="submit" class="btn  btn-primary">{{ 'Create' }}</button>
            </div>
            </form>
        </div>
    </div>
@endsection

@push('script-page')
    <script>
        // file label
        $('input[type="file"]').change(function(e) {
            var file = e.target.files[0].name;
            var file_name = $(this).attr('data-filename');
            $('.' + file_name).append(file);
        });
    </script>

    <script>
        // initial load: get department id correctly
        $(document).ready(function() {
            var d_id = $('#department_id').val();
            if (d_id) {
                getDesignation(d_id);
            }
        });

        // when department changes, reload designations
        $(document).on('change', 'select[name=department_id]', function() {
            var department_id = $(this).val();
            // if user clicked Add New, modal will handle; skip ajax
            if (department_id === '__add__') return;
            getDesignation(department_id);
        });

        function getDesignation(did) {
            $.ajax({
                url: '{{ route('employee.json') }}',
                type: 'POST',
                data: {
                    "department_id": did,
                    "_token": "{{ csrf_token() }}",
                },
                success: function(data) {
                    // rebuild designation select with Add New + data-create-*
                    var emp_selct = `
                        <select class="form-control designation_id"
                                name="designation_id"
                                id="choices-multiple"
                                placeholder="Select Designation"
                                data-create-url="{{ route('designation.create') }}"
                                data-create-title="{{ __('Create Designation') }}">
                            <option value="">Select any Designation</option>
                            <option value="__add__">➕  Add New</option>
                        </select>`;
                    $('.designation_div').html(emp_selct);

                    // Optional "All"
                    $('.designation_id').append('<option value="0">{{ __('All') }}</option>');

                    // Populate options
                    $.each(data, function(key, value) {
                        $('.designation_id').append('<option value="' + key + '">' + value +
                            '</option>');
                    });

                    // Re-init Choices
                    new Choices('#choices-multiple', {
                        removeItemButton: true,
                    });
                }
            });
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

            // Ensure Add New exists for Branch & Department on load (in case server array missed it)
            function ensureAddNewOption($select) {
                if ($select.find('option[value="__add__"]').length === 0) {
                    $select.prepend('<option value="__add__">➕  Add New</option>');
                }
            }
            ensureAddNewOption($('#branch_id'));
            ensureAddNewOption($('#department_id'));

            // Detect "Add New" selection for any select with data-create-url
            $(document).on('change', 'select[data-create-url]', function() {
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
                            var $newOption = $('<option>', {
                                value: response.data.id,
                                text: response.data.name
                            });

                            // append and select
                            $select.append($newOption);

                            // Handle both Choices.js and regular selects
                            if ($select.hasClass('select2')) {
                                var selectId = $select.attr('id');
                                if (selectId) {
                                    // Destroy existing Choices instance if it exists
                                    if (window.Choices) {
                                        var choicesInstance = document.querySelector('#' +
                                            selectId);
                                        if (choicesInstance && choicesInstance.choices) {
                                            choicesInstance.choices.destroy();
                                        }
                                    }

                                    // Set the value
                                    $select.val(response.data.id);

                                    // Reinitialize Choices
                                    if (window.Choices) {
                                        new Choices('#' + selectId, {
                                            removeItemButton: true,
                                        });
                                    }
                                } else {
                                    $select.val(response.data.id).trigger('change');
                                }
                            } else {
                                $select.val(response.data.id).trigger('change');
                            }

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
