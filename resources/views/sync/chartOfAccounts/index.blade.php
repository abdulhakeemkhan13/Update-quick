@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- SweetAlert2 CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        .content-wrapper {
            background: #fff !important;
            padding: 30px 30px !important;
        }

        .dt-buttons {
            position: relative !important;
            left: 70% !important;
        }
        
        .offcanvas {
            width: 400px !important;
        }
        .offcanvas-backdrop {
            background-color: rgba(0, 0, 0, 0.5);
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        .invalid-feedback {
            display: block;
        }
        .edit-account {
            cursor: pointer;
        }
    </style>
@endpush

@section('filter-section')
<x-filters.filter-box>
</x-filters.filter-box>
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="d-flex flex-column w-tables rounded mt-3 bg-white">
            <div class="d-lg-flex d-md-flex d-block justify-content-between p-4 bg-white border-bottom-grey text-capitalize">
                <h4 class="heading-h4 mb-0">
                    Chart of Accounts
                    <span class="text-lightest f-12 ml-2" id="date-range-display"></span>
                </h4>
            </div>

            <div class="table-responsive p-2">
                {!! $dataTable->table(['class' => 'table table-hover border-0 w-100 chartofaccounts-table']) !!}
            </div>
        </div>
    </div>

    <!-- Edit Chart of Account Modal -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="editAccountModal" aria-labelledby="editAccountModalLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title" id="editAccountModalLabel">
                <i class="fa fa-edit me-2"></i>Edit Chart of Account
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form id="editAccountForm" method="POST">
                @csrf                
                <div class="mb-3">
                    <label for="edit_name" class="form-label">Account Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="edit_name" name="name" required>
                    <div class="invalid-feedback" id="name-error"></div>
                </div>

                <div class="mb-3">
                    <label for="edit_code" class="form-label">Account Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="edit_code" name="code" required>
                    <div class="invalid-feedback" id="code-error"></div>
                </div>

                <div class="d-flex gap-2 mt-4 justify-content-end">
                    <button type="submit" class="btn btn-sm btn-success">
                        <i class="fa fa-save me-1"></i> Save
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="offcanvas">
                        <i class="fa fa-times me-1"></i>Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    @include('sections.datatable_js')
    
    <!-- Bootstrap 5 JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        $(document).ready(function() {
            // Ensure Bootstrap is loaded            
            // Handle edit button click
            $(document).on('click', '.edit-account', function() {                
                const accountId = $(this).data('id');
                const accountName = $(this).data('name');
                const accountCode = $(this).data('code');
                                
                // Populate form fields
                $('#edit_name').val(accountName);
                $('#edit_code').val(accountCode);
                
                // Set form action URL
                $('#editAccountForm').attr('action', `{{ route('chart-of-accounts.update', '') }}/${accountId}`);
                
                // Clear previous errors
                $('.form-control').removeClass('is-invalid');
                $('.invalid-feedback').text('');
                
                // Show modal
                const modalElement = document.getElementById('editAccountModal');
                const modal = new bootstrap.Offcanvas(modalElement);
                modal.show();
                            });

            // Handle form submission
            $('#editAccountForm').on('submit', function(e) {
                e.preventDefault();                
                const form = $(this);
                const formData = form.serialize();
                const actionUrl = form.attr('action');
               // Show loading state
                const submitBtn = form.find('button[type="submit"]');
                const originalText = submitBtn.html();
                submitBtn.html('<i class="fa fa-spinner fa-spin me-1"></i>Updating...').prop('disabled', true);
                
                // Clear previous errors
                $('.form-control').removeClass('is-invalid');
                $('.invalid-feedback').text('');
                
                $.ajax({
                    url: actionUrl,
                    type: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {                        
                        if (response.success) {
                            // Show success message
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message || 'Account updated successfully',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            
                            // Hide modal
                            const modalElement = document.getElementById('editAccountModal');
                            const modal = bootstrap.Offcanvas.getInstance(modalElement);
                            if (modal) {
                                modal.hide();
                            }
                            
                            // Refresh datatable
                            $('#chartofaccounts-table').DataTable().ajax.reload(null, false);
                        }
                    },
                    error: function(xhr) {                        
                        if (xhr.status === 422) {
                            // Validation errors
                            const errors = xhr.responseJSON.errors;
                            console.log('Validation errors:', errors);
                            
                            $.each(errors, function(field, messages) {
                                const fieldElement = $(`#edit_${field}`);
                                fieldElement.addClass('is-invalid');
                                $(`#${field}-error`).text(messages[0]);
                            });
                        } else {
                            // Other errors
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: xhr.responseJSON?.message || 'Something went wrong. Please try again.',
                            });
                        }
                    },
                    complete: function() {
                        // Reset button state
                        submitBtn.html(originalText).prop('disabled', false);
                    }
                });
            });

            // Handle modal close events
            $(document).on('click', '[data-bs-dismiss="offcanvas"]', function() {
                const modalElement = document.getElementById('editAccountModal');
                const modal = bootstrap.Offcanvas.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                }
            });
        });
    </script>
@endpush