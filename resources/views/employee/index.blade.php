@extends('layouts.admin')
@section('page-title')
    {{ __('Employees') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Team') }}</li>
    <li class="breadcrumb-item">{{ __('Employees') }}</li>
@endsection

@push('css-page')
<link rel="stylesheet" href="{{ asset('css/datatable/buttons.dataTables.min.css') }}">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<style>
/* QBO Employee Index Styles */
.qbo-employees-container { padding: 24px; }
.qbo-page-header {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 24px;
}
.qbo-page-title { font-size: 24px; font-weight: 500; color: #393a3d; margin: 0; }

/* Empty State */
.empty-state-container {
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; padding: 60px 20px; text-align: center;
}
.empty-state-image { max-width: 320px; margin-bottom: 24px; }
.empty-state-title { font-size: 20px; font-weight: 600; color: #393a3d; margin-bottom: 16px; }
.add-employee-btn {
    background-color: #2ca01c; color: #fff; border: none; padding: 10px 24px;
    border-radius: 4px; font-size: 14px; font-weight: 500; cursor: pointer;
    text-decoration: none; display: inline-block;
}
.add-employee-btn:hover { background-color: #248a16; color: #fff; }

/* Filters Row */
.filters-row {
    display: flex; align-items: center; gap: 16px; margin-bottom: 20px; flex-wrap: wrap;
}
.search-box {
    display: flex; align-items: center; border: 1px solid #babec5; border-radius: 4px;
    padding: 8px 12px; width: 240px; background: #fff;
}
.search-box input {
    border: none; outline: none; width: 100%; font-size: 14px;
}
.search-box i { color: #6b6c72; margin-right: 8px; }
.status-dropdown {
    display: flex; align-items: center; border: 1px solid #2ca01c; border-radius: 4px;
    padding: 8px 16px; background: #fff; cursor: pointer; gap: 8px;
}
.status-dropdown span { color: #2ca01c; font-size: 14px; font-weight: 500; }
.status-dropdown i { color: #2ca01c; }
.add-btn-container { margin-left: auto; display: flex; align-items: center; gap: 8px; }
.settings-btn {
    background: none; border: none; color: #6b6c72; cursor: pointer; padding: 8px;
}

/* Employee Table */
.employee-avatar {
    width: 32px; height: 32px; border-radius: 50%; display: flex;
    align-items: center; justify-content: center; color: #fff;
    font-size: 12px; font-weight: 600; text-transform: uppercase;
}
.status-active { color: #393a3d; }
.status-inactive { color: #6b6c72; }

#qbo-employees-table { border-collapse: collapse; }
#qbo-employees-table thead { background: #fff; }
#qbo-employees-table th {
    font-size: 11px; font-weight: 700; color: #6b6c72; text-transform: uppercase;
    letter-spacing: 0.5px; border-bottom: 2px solid #e0e3e5; padding: 12px 16px;
    background: #fff;
}
#qbo-employees-table th:first-child {
    width: 50px; text-align: center;
}
#qbo-employees-table td {
    font-size: 14px; color: #393a3d; padding: 16px;
    border-bottom: 1px solid #e0e3e5; vertical-align: middle;
}
#qbo-employees-table td:first-child { text-align: center; }
#qbo-employees-table tbody tr { background: #fff; }
#qbo-employees-table tbody tr:hover { background: #f5f5f5; cursor: pointer; }
.dataTables_wrapper { background: #fff; }

/* Payroll Banner */
.payroll-banner {
    background: #fff; border: 1px solid #e0e3e5; border-radius: 8px;
    padding: 24px; margin-bottom: 24px; display: flex; align-items: center; gap: 24px;
}
.payroll-steps { display: flex; gap: 32px; align-items: center; flex: 1; }
.payroll-step { display: flex; flex-direction: column; align-items: center; text-align: center; }
.payroll-step img { width: 60px; height: 60px; margin-bottom: 8px; }
.payroll-step span { font-size: 12px; color: #6b6c72; }
.payroll-info { max-width: 280px; }
.payroll-info h4 { font-size: 16px; font-weight: 600; color: #393a3d; margin-bottom: 8px; }
.payroll-info p { font-size: 13px; color: #6b6c72; margin-bottom: 12px; }
.get-started-btn {
    background: #2ca01c; color: #fff; border: none; padding: 10px 20px;
    border-radius: 4px; font-size: 14px; font-weight: 500; cursor: pointer;
}
.payroll-phone { font-size: 12px; color: #6b6c72; margin-top: 8px; }

/* QBO Add Employee Modal */
.qbo-modal-overlay {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5); display: none; justify-content: center;
    align-items: center; z-index: 1050;
}
.qbo-modal-overlay.show { display: flex; }
.qbo-modal {
    background: #fff; border-radius: 8px; width: 100%; max-width: 560px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.15); position: relative;
    max-height: 90vh; overflow-y: auto;
}
.qbo-modal-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    padding: 32px 32px 16px;
}
.qbo-modal-title {
    font-size: 24px; font-weight: 400; color: #393a3d; margin: 0;
    line-height: 1.3;
}
.qbo-modal-close {
    background: none; border: none; font-size: 28px; color: #6b6c72;
    cursor: pointer; padding: 0; line-height: 1; position: absolute;
    top: 16px; right: 16px;
}
.qbo-modal-close:hover { color: #393a3d; }
.qbo-modal-body { padding: 8px 32px 16px; }
.qbo-form-row {
    display: flex; gap: 24px; margin-bottom: 24px;
}
.qbo-form-group { flex: 1; }
.qbo-form-group.small { flex: 0 0 80px; }
.qbo-form-label {
    display: block; font-size: 12px; color: #6b6c72; margin-bottom: 4px;
    font-weight: 400;
}
.qbo-form-label .required { color: #393a3d; }
.qbo-form-input {
    width: 100%; padding: 8px 0; 
    border: none; border-bottom: 1px solid #babec5;
    border-radius: 0; font-size: 14px; color: #393a3d;
    background: transparent;
}
.qbo-form-input:focus {
    outline: none; border-bottom-color: #0077c5;
    border-bottom-width: 2px;
}
.qbo-form-input::placeholder { color: #babec5; }
.qbo-modal-footer {
    padding: 24px 32px 32px; display: flex; justify-content: center;
}
.qbo-submit-btn {
    background: #2ca01c; color: #fff; border: none; padding: 14px 60px;
    border-radius: 4px; font-size: 14px; font-weight: 600; cursor: pointer;
    min-width: 200px;
}
.qbo-submit-btn:hover { background: #248a16; }
.qbo-submit-btn:disabled { background: #babec5; cursor: not-allowed; }

/* Table header with sort icon */
#qbo-employees-table thead th:first-child { padding-left: 24px; }
#qbo-employees-table th.sorting_asc::after,
#qbo-employees-table th.sorting_desc::after { margin-left: 8px; }

@media (max-width: 768px) {
    .filters-row { flex-direction: column; align-items: stretch; }
    .search-box { width: 100%; }
    .add-btn-container { margin-left: 0; justify-content: flex-end; }
    .qbo-form-row { flex-direction: column; gap: 20px; }
    .qbo-form-group.small { flex: 1; }
    .qbo-modal-header, .qbo-modal-body, .qbo-modal-footer { padding-left: 20px; padding-right: 20px; }
}
</style>
@endpush

@section('content')
{{-- MY APPS Sidebar --}}
@include('partials.admin.allApps-subMenu-Sidebar', [
    'activeSection' => 'team',
    'activeItem' => 'employees'
])

<div class="qbo-employees-container">
    @php
        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = $user->type == 'company' ? 'created_by' : 'owned_by';
        $employeeCount = \App\Models\Employee::where($column, $ownerId)->count();
    @endphp

    @if($employeeCount == 0)
        {{-- Empty State --}}
        <div class="empty-state-container">
            <svg class="empty-state-image" width="320" height="200" viewBox="0 0 320 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Simple team illustration -->
                <ellipse cx="160" cy="180" rx="120" ry="15" fill="#f0f0f0"/>
                <!-- Person 1 -->
                <circle cx="80" cy="100" r="25" fill="#FFC107"/>
                <rect x="60" y="130" width="40" height="50" rx="5" fill="#FFC107"/>
                <!-- Person 2 -->
                <circle cx="160" cy="90" r="30" fill="#2196F3"/>
                <rect x="135" y="125" width="50" height="55" rx="5" fill="#2196F3"/>
                <!-- Person 3 -->
                <circle cx="240" cy="100" r="25" fill="#4CAF50"/>
                <rect x="220" y="130" width="40" height="50" rx="5" fill="#4CAF50"/>
                <!-- Laptop -->
                <rect x="130" y="150" width="60" height="35" rx="3" fill="#666"/>
                <rect x="120" y="182" width="80" height="5" rx="2" fill="#888"/>
            </svg>
            <div class="empty-state-title">{{ __('Tell us about your team') }}</div>
            <button type="button" class="add-employee-btn" id="openAddEmployeeModal">
                {{ __('Add an employee') }}
            </button>
        </div>
    @else
        {{-- Payroll Banner --}}
        <div class="payroll-banner">
            <div class="payroll-steps">
                <div class="payroll-step">
                    <svg width="60" height="60" viewBox="0 0 60 60" fill="none">
                        <circle cx="30" cy="30" r="28" stroke="#2ca01c" stroke-width="2" stroke-dasharray="4 4"/>
                        <path d="M20 30L27 37L40 24" stroke="#2ca01c" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <span>{{ __('Set up payroll') }}</span>
                </div>
                <svg width="40" height="20" viewBox="0 0 40 20"><path d="M5 10h30" stroke="#babec5" stroke-dasharray="4 2"/><path d="M30 5l5 5-5 5" stroke="#babec5" fill="none"/></svg>
                <div class="payroll-step">
                    <svg width="60" height="60" viewBox="0 0 60 60" fill="none">
                        <rect x="15" y="10" width="30" height="40" rx="2" stroke="#6b6c72" stroke-width="1.5"/>
                        <path d="M20 20h20M20 28h20M20 36h12" stroke="#6b6c72" stroke-width="1.5"/>
                    </svg>
                    <span>{{ __('Create paychecks in minutes') }}</span>
                </div>
                <svg width="40" height="20" viewBox="0 0 40 20"><path d="M5 10h30" stroke="#babec5" stroke-dasharray="4 2"/><path d="M30 5l5 5-5 5" stroke="#babec5" fill="none"/></svg>
                <div class="payroll-step">
                    <svg width="60" height="60" viewBox="0 0 60 60" fill="none">
                        <rect x="12" y="15" width="36" height="30" rx="2" stroke="#6b6c72" stroke-width="1.5"/>
                        <path d="M18 35h24M18 40h16" stroke="#6b6c72" stroke-width="1.5"/>
                    </svg>
                    <span>{{ __('Get payroll taxes done') }}</span>
                </div>
            </div>
            <div class="payroll-info">
                <h4>{{ __('Need to pay your employees?') }}</h4>
                <p>{{ __('Run payroll quickly and accurately. File and pay taxes or let our experts do it for you.') }}</p>
                <button class="get-started-btn">{{ __('Get started') }}</button>
                <div class="payroll-phone">{{ __('Questions?') }} Call 844-832-2909</div>
            </div>
        </div>

        {{-- Page Header --}}
        <div class="qbo-page-header">
            <h1 class="qbo-page-title">{{ __('Employees') }}</h1>
        </div>

        {{-- Filters Row --}}
        <div class="filters-row">
            <div class="search-box">
                <i class="ti ti-search"></i>
                <input type="text" id="employee-search" placeholder="{{ __('Find an employee') }}">
            </div>
            <div class="status-dropdown" id="status-filter-btn">
                <span id="status-filter-text">{{ __('Active Employees') }}</span>
                <i class="ti ti-chevron-down"></i>
            </div>
            <div class="add-btn-container">
                <button type="button" class="add-employee-btn" id="openAddEmployeeModalList">
                    {{ __('Add an employee') }}
                </button>
                <button class="settings-btn">
                    <i class="ti ti-settings"></i>
                </button>
            </div>
        </div>

        {{-- Employee Table --}}
        <div class="card">
            <div class="card-body p-0">
                {{ $dataTable->table(['class' => 'table', 'style' => 'width:100%']) }}
            </div>
        </div>
    @endif
</div>

{{-- Status Filter Dropdown --}}
<div id="status-dropdown-menu" class="dropdown-menu" style="display: none; position: absolute;">
    <a class="dropdown-item status-option" data-status="Active" href="#">{{ __('Active Employees') }}</a>
    <a class="dropdown-item status-option" data-status="Inactive" href="#">{{ __('Inactive Employees') }}</a>
    <a class="dropdown-item status-option" data-status="All" href="#">{{ __('All Employees') }}</a>
</div>

{{-- QBO Add Employee Modal --}}
<div class="qbo-modal-overlay" id="addEmployeeModal">
    <div class="qbo-modal">
        <div class="qbo-modal-header">
            <h2 class="qbo-modal-title">{{ __("Who's your new team member?") }}</h2>
            <button type="button" class="qbo-modal-close" id="closeAddEmployeeModal">&times;</button>
        </div>
        <form id="addEmployeeForm" action="{{ route('employee.store') }}" method="POST">
            @csrf
            <div class="qbo-modal-body">
                <div class="qbo-form-row">
                    <div class="qbo-form-group">
                        <label class="qbo-form-label">{{ __('First name') }} <span class="required">*</span></label>
                        <input type="text" name="first_name" class="qbo-form-input" required>
                    </div>
                    <div class="qbo-form-group small">
                        <label class="qbo-form-label">{{ __('M.I.') }}</label>
                        <input type="text" name="middle_initial" class="qbo-form-input" maxlength="1">
                    </div>
                    <div class="qbo-form-group">
                        <label class="qbo-form-label">{{ __('Last name') }} <span class="required">*</span></label>
                        <input type="text" name="last_name" class="qbo-form-input" required>
                    </div>
                </div>
                <div class="qbo-form-row">
                    <div class="qbo-form-group">
                        <label class="qbo-form-label">{{ __('Email') }}</label>
                        <input type="email" name="email" class="qbo-form-input">
                    </div>
                </div>
                <div class="qbo-form-row">
                    <div class="qbo-form-group" style="max-width: 200px;">
                        <label class="qbo-form-label">{{ __('Hire date') }}</label>
                        <input type="date" name="hire_date" class="qbo-form-input">
                    </div>
                </div>
            </div>
            <div class="qbo-modal-footer">
                <button type="submit" class="qbo-submit-btn" id="addEmployeeSubmit">{{ __('Add employee') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('script-page')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
@if(isset($dataTable))
{!! $dataTable->scripts() !!}
@endif
<script>
$(document).ready(function() {
    // Modal open/close functionality
    $('#openAddEmployeeModal, #openAddEmployeeModalList').on('click', function() {
        $('#addEmployeeModal').addClass('show');
    });
    
    $('#closeAddEmployeeModal').on('click', function() {
        $('#addEmployeeModal').removeClass('show');
    });
    
    // Close modal on overlay click
    $('#addEmployeeModal').on('click', function(e) {
        if (e.target === this) {
            $(this).removeClass('show');
        }
    });
    
    // Close modal on Escape key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#addEmployeeModal').hasClass('show')) {
            $('#addEmployeeModal').removeClass('show');
        }
    });
    
    // Handle form submission via AJAX
    $('#addEmployeeForm').on('submit', function(e) {
        e.preventDefault();
        
        var $btn = $('#addEmployeeSubmit');
        var originalText = $btn.text();
        $btn.prop('disabled', true).text('{{ __("Adding...") }}');
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                // Reload page to show new employee
                window.location.reload();
            },
            error: function(xhr) {
                $btn.prop('disabled', false).text(originalText);
                var errorMsg = '{{ __("Error adding employee. Please try again.") }}';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                alert(errorMsg);
            }
        });
    });

@if($employeeCount > 0)
    // Search functionality
    $('#employee-search').on('keyup', function() {
        var table = $('#qbo-employees-table').DataTable();
        table.search(this.value).draw();
    });

    // Status filter dropdown
    $('#status-filter-btn').on('click', function(e) {
        e.stopPropagation();
        var menu = $('#status-dropdown-menu');
        var btn = $(this);
        menu.css({
            top: btn.offset().top + btn.outerHeight(),
            left: btn.offset().left
        }).toggle();
    });

    $(document).on('click', function() {
        $('#status-dropdown-menu').hide();
    });

    $('.status-option').on('click', function(e) {
        e.preventDefault();
        var status = $(this).data('status');
        var text = $(this).text();
        $('#status-filter-text').text(text);
        $('#status-dropdown-menu').hide();
        
        // Reload table with status filter
        var table = $('#qbo-employees-table').DataTable();
        table.ajax.url('{{ route("employee.index") }}?status=' + status).load();
    });

    // Row click to view profile
    $(document).on('click', '#qbo-employees-table tbody tr', function() {
        var table = $('#qbo-employees-table').DataTable();
        var data = table.row(this).data();
        if (data && data.encrypted_id) {
            window.location.href = '{{ url("employee") }}/' + data.encrypted_id;
        }
    });
@endif
});
</script>
@endpush
