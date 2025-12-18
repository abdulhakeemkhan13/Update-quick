@extends('layouts.admin')
@section('page-title')
    {{ __('Employee Profile') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('employee.index') }}">{{ __('Employees') }}</a></li>
    <li class="breadcrumb-item">{{ $employee->display_name ?? $employee->name }}</li>
@endsection

@push('css-page')
<style>
/* QBO Employee Profile Styles */
.qbo-profile-container { padding: 24px; background: #f5f5f5; min-height: calc(100vh - 120px); }

/* Back Link */
.back-link {
    display: inline-flex; align-items: center; gap: 8px; color: #0077c5;
    text-decoration: none; font-size: 14px; margin-bottom: 24px;
}
.back-link:hover { text-decoration: underline; color: #005999; }

/* Profile Header */
.profile-header {
    display: flex; align-items: center; gap: 24px; margin-bottom: 32px;
}
.profile-avatar {
    width: 80px; height: 80px; border-radius: 50%; background: #e0e3e5;
    display: flex; align-items: center; justify-content: center;
    position: relative;
}
.profile-avatar-initials {
    font-size: 32px; font-weight: 500; color: #6b6c72;
}
.profile-avatar-edit {
    position: absolute; bottom: 0; right: 0; width: 28px; height: 28px;
    background: #fff; border: 1px solid #babec5; border-radius: 50%;
    display: flex; align-items: center; justify-content: center; cursor: pointer;
}
.profile-info { flex: 1; }
.profile-name { font-size: 28px; font-weight: 400; color: #393a3d; margin: 0; }
.profile-status { font-size: 14px; color: #6b6c72; }
.profile-actions .btn-actions {
    background: #2ca01c; color: #fff; border: none; padding: 10px 20px;
    border-radius: 4px; font-size: 14px; font-weight: 500; cursor: pointer;
    display: inline-flex; align-items: center; gap: 8px;
}
.profile-actions .btn-actions:hover { background: #248a16; }

/* Tabs */
.profile-tabs {
    display: flex; gap: 8px; border-bottom: 2px solid #e0e3e5; margin-bottom: 24px;
}
.profile-tab {
    padding: 12px 20px; font-size: 14px; color: #6b6c72; cursor: pointer;
    border-bottom: 3px solid transparent; margin-bottom: -2px;
    text-decoration: none;
}
.profile-tab:hover { color: #393a3d; }
.profile-tab.active { color: #393a3d; border-bottom-color: #2ca01c; font-weight: 500; }

/* Info Cards */
.info-card {
    background: #fff; border: 1px solid #e0e3e5; border-radius: 8px;
    padding: 24px; margin-bottom: 20px;
}
.info-card-header {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 20px;
}
.info-card-title { font-size: 18px; font-weight: 600; color: #393a3d; margin: 0; }
.info-card-edit {
    color: #0077c5; font-size: 14px; font-weight: 500; text-decoration: none;
    cursor: pointer;
}
.info-card-edit:hover { text-decoration: underline; }
.info-grid {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px 32px;
}
.info-item {}
.info-label { font-size: 12px; color: #6b6c72; margin-bottom: 4px; }
.info-value { font-size: 14px; color: #393a3d; }
.info-description { font-size: 14px; color: #6b6c72; }

@media (max-width: 768px) {
    .info-grid { grid-template-columns: 1fr; }
    .profile-header { flex-direction: column; text-align: center; }
}
</style>
@endpush

@section('content')
{{-- MY APPS Sidebar --}}
@include('partials.admin.allApps-subMenu-Sidebar', [
    'activeSection' => 'team',
    'activeItem' => 'employees'
])

<div class="qbo-profile-container">
    {{-- Back Link --}}
    <a href="{{ route('employee.index') }}" class="back-link">
        <i class="ti ti-chevron-left"></i> {{ __('Employee List') }}
    </a>

    {{-- Profile Header --}}
    <div class="profile-header">
        <div class="profile-avatar">
            @php
                $initials = '';
                if (!empty($employee->first_name)) {
                    $initials .= strtoupper(substr($employee->first_name, 0, 1));
                }
                if (!empty($employee->last_name)) {
                    $initials .= strtoupper(substr($employee->last_name, 0, 1));
                }
                if (empty($initials) && !empty($employee->name)) {
                    $nameParts = explode(' ', $employee->name);
                    $initials = strtoupper(substr($nameParts[0] ?? '', 0, 1));
                    $initials .= strtoupper(substr($nameParts[1] ?? '', 0, 1));
                }
                $initials = $initials ?: 'E';
            @endphp
            <span class="profile-avatar-initials">{{ $initials }}</span>
            <div class="profile-avatar-edit">
                <i class="ti ti-pencil" style="font-size: 14px; color: #6b6c72;"></i>
            </div>
        </div>
        <div class="profile-info">
            <h1 class="profile-name">{{ $employee->display_name ?? $employee->name }}</h1>
            <span class="profile-status">{{ $employee->status ?? 'Active' }}</span>
        </div>
        <div class="profile-actions">
            <div class="dropdown">
                <button class="btn-actions dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    {{ __('Actions') }} <i class="ti ti-chevron-down"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#">{{ __('Make inactive') }}</a></li>
                    <li><a class="dropdown-item" href="#">{{ __('Run payroll') }}</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="#">{{ __('Delete employee') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="profile-tabs">
        <a href="#" class="profile-tab active">{{ __('Profile') }}</a>
        <a href="#" class="profile-tab">{{ __('Notes') }}</a>
    </div>

    {{-- Personal Info Card --}}
    <div class="info-card">
        <div class="info-card-header">
            <h3 class="info-card-title">{{ __('Personal info') }}</h3>
            <a href="{{ route('employee.edit.personal-info', Crypt::encrypt($employee->id)) }}" class="info-card-edit">{{ __('Edit') }}</a>
        </div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">{{ __('Legal name') }}</div>
                <div class="info-value">{{ $employee->display_name ?? $employee->name ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">{{ __('Preferred first name') }}</div>
                <div class="info-value">{{ $employee->preferred_first_name ?? $employee->first_name ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">{{ __('Email') }}</div>
                <div class="info-value">{{ $employee->email ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">{{ __('Phone number') }}</div>
                <div class="info-value">{{ $employee->phone ?? $employee->mobile_phone ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">{{ __('Home address') }}</div>
                <div class="info-value">{{ $employee->address ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">{{ __('Mailing address') }}</div>
                <div class="info-value">{{ $employee->mailing_address_same ? ($employee->address ?? '-') : '-' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">{{ __('Birth date') }}</div>
                <div class="info-value">{{ $employee->birth_date ? \Carbon\Carbon::parse($employee->birth_date)->format('m/d/Y') : ($employee->dob ? \Carbon\Carbon::parse($employee->dob)->format('m/d/Y') : '-') }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">{{ __('Gender') }}</div>
                <div class="info-value">{{ $employee->gender ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">{{ __('Social Security number') }}</div>
                <div class="info-value">{{ $employee->ssn ? '***-**-' . substr($employee->ssn, -4) : '-' }}</div>
            </div>
        </div>
    </div>

    {{-- Employment Details Card --}}
    <div class="info-card">
        <div class="info-card-header">
            <h3 class="info-card-title">{{ __('Employment details') }}</h3>
            <a href="{{ route('employee.edit.employment-details', Crypt::encrypt($employee->id)) }}" class="info-card-edit">{{ __('Edit') }}</a>
        </div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">{{ __('Status') }}</div>
                <div class="info-value">{{ $employee->status ?? 'Active' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">{{ __('Hire date') }}</div>
                <div class="info-value">{{ $employee->hire_date ? \Carbon\Carbon::parse($employee->hire_date)->format('m/d/Y') : ($employee->company_doj ? \Carbon\Carbon::parse($employee->company_doj)->format('m/d/Y') : '-') }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">{{ __('Manager') }}</div>
                <div class="info-value">{{ $employee->manager ? $employee->manager->display_name : '-' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">{{ __('Department') }}</div>
                <div class="info-value">{{ $employee->department_name ?? ($employee->department ? $employee->department->name : '-') }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">{{ __('Job title') }}</div>
                <div class="info-value">{{ $employee->job_title ?? ($employee->designation ? $employee->designation->name : '-') }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">{{ __('Employee ID') }}</div>
                <div class="info-value">{{ $employee->employee_id ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">{{ __('Name to print on checks') }}</div>
                <div class="info-value">{{ $employee->name_on_checks ?? $employee->display_name ?? $employee->name ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">{{ __('Billing rate') }}</div>
                <div class="info-value">{{ $employee->billing_rate ? '$' . number_format($employee->billing_rate, 2) : '-' }}</div>
            </div>
        </div>
    </div>

    {{-- Emergency Contact Card --}}
    <div class="info-card">
        <div class="info-card-header">
            <h3 class="info-card-title">{{ __('Emergency contact') }}</h3>
            <a href="{{ route('employee.edit.emergency-contact', Crypt::encrypt($employee->id)) }}" class="info-card-edit">{{ $employee->emergency_first_name ? __('Edit') : __('Start') }}</a>
        </div>
        @if($employee->emergency_first_name)
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">{{ __('Name') }}</div>
                    <div class="info-value">{{ $employee->emergency_first_name }} {{ $employee->emergency_last_name }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">{{ __('Relationship') }}</div>
                    <div class="info-value">{{ $employee->emergency_relationship ?? '-' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">{{ __('Phone number') }}</div>
                    <div class="info-value">{{ $employee->emergency_phone ?? '-' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">{{ __('Email') }}</div>
                    <div class="info-value">{{ $employee->emergency_email ?? '-' }}</div>
                </div>
            </div>
        @else
            <p class="info-description">{{ __("Employee's contact in case of emergency. This could be their spouse, partner, or friend.") }}</p>
        @endif
    </div>
</div>
@endsection
