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

/* QBO Fullscreen Modal Overlay Styles */
.qbo-fullscreen-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: #f5f5f5;
    z-index: 9999;
    display: none;
    flex-direction: column;
}
.qbo-fullscreen-overlay.show {
    display: flex;
}

/* Modal Header - Green Bar */
.qbo-modal-header-bar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 56px;
    background: #ECEEF1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    z-index: 10000;
}
.qbo-modal-title {
    color: #393A3D;
    font-size: 18px;
    font-weight: 500;
    margin: 0;
}
.qbo-modal-header-actions {
    display: flex;
    align-items: center;
    gap: 16px;
}
.qbo-modal-header-btn {
    background: none;
    border: none;
    color: #393A3D;
    font-size: 22px;
    cursor: pointer;
    padding: 8px;
    text-decoration: none;
    display: flex;
    align-items: center;
}
.qbo-modal-header-btn:hover {
    opacity: 0.8;
    color: #393A3D;
}

/* Modal Content - Scrollable */
.qbo-modal-content {
    position: absolute;
    top: 56px;
    left: 0;
    right: 0;
    bottom: 64px;
    overflow-y: auto;
    padding: 40px;
    background: #ffffffff;
}
.qbo-modal-content-inner {
    max-width: 800px;
    margin: 0 auto;
    width: 100%;
}

/* Modal Footer - Fixed */
.qbo-modal-footer-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    z-index: 10000;
    background-color: #ffffff;
    box-sizing: border-box;
    border-top: 1px solid #D4D7DC;
    box-shadow: 0 6px 24px 0 rgba(0, 0, 0, 0.2);
}
.qbo-modal-footer-left,
.qbo-modal-footer-right {
    display: flex;
    align-items: center;
    gap: 12px;
}
.qbo-btn-cancel {
    background: #fff;
    border: none !important;
    color: #2ca01c;
    font-weight: 600;
    padding: 10px 24px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
}
.qbo-btn-cancel:hover {
    background: #f0f9f0;
    color: #2ca01c;
}
.qbo-btn-save {
    background: #2ca01c;
    color: #fff;
    border: none;
    padding: 10px 32px;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
}
.qbo-btn-save:hover {
    background: #248a16;
}

/* Modal Form Styles */
.qbo-section-title {
    font-size: 24px;
    font-weight: 600;
    color: #393a3d;
    margin-bottom: 8px;
}
.qbo-section-desc {
    font-size: 14px;
    color: #6b6c72;
    margin-bottom: 32px;
}
.qbo-form-row {
    display: flex;
    gap: 16px;
    margin-bottom: 24px;
}
.qbo-form-group {
    flex: 1;
}
.qbo-form-group.small {
    flex: 0 0 80px;
}
.qbo-form-group.medium {
    flex: 0 0 220px;
}
.qbo-form-group.large {
    flex: 0 0 300px;
}
.qbo-form-label {
    display: block;
    font-size: 13px;
    color: #6b6c72;
    margin-bottom: 8px;
}
.qbo-form-label.required::after {
    content: ' *';
    color: #6b6c72;
}
.qbo-form-input,
.qbo-form-select {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #babec5;
    border-radius: 4px;
    font-size: 14px;
    color: #393a3d;
    background: #fff;
}
.qbo-form-input:focus,
.qbo-form-select:focus {
    outline: none;
    border-color: #0077c5;
    box-shadow: 0 0 0 2px rgba(0,119,197,0.15);
}
.qbo-checkbox-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 20px 0;
}
.qbo-checkbox-row input[type="checkbox"] {
    width: 20px;
    height: 20px;
    accent-color: #2ca01c;
}
.qbo-show-toggle {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 16px 0;
    cursor: pointer;
    font-size: 14px;
    color: #0077c5;
}

@media (max-width: 768px) {
    .qbo-modal-content { padding: 70px 20px 80px 20px; }
    .qbo-form-row { flex-direction: column; gap: 16px; }
    .qbo-form-group.small, .qbo-form-group.medium, .qbo-form-group.large { flex: 1; }
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
            <a href="javascript:void(0)" class="info-card-edit" onclick="openModal('personalInfoModal')">{{ __('Edit') }}</a>
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
            <a href="javascript:void(0)" class="info-card-edit" onclick="openModal('employmentDetailsModal')">{{ __('Edit') }}</a>
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
            <a href="javascript:void(0)" class="info-card-edit" onclick="openModal('emergencyContactModal')">{{ $employee->emergency_first_name ? __('Edit') : __('Start') }}</a>
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

{{-- ======================= EDIT PERSONAL INFO MODAL ======================= --}}
<div class="qbo-fullscreen-overlay" id="personalInfoModal">
    <div class="qbo-modal-header-bar">
        <h3 class="qbo-modal-title">{{ __('Edit personal info') }}</h3>
        <div class="qbo-modal-header-actions">
            <button class="qbo-modal-header-btn" title="Help"><i class="ti ti-help"></i></button>
            <button class="qbo-modal-header-btn" onclick="closeModal('personalInfoModal')">&times;</button>
        </div>
    </div>
    
    <form id="personalInfoForm" action="{{ route('employee.update', Crypt::encrypt($employee->id)) }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="form_type" value="personal_info">
        
        <div class="qbo-modal-content">
            <div>
            <h4 class="qbo-section-title">{{ __('Tell us more about') }} {{ $employee->first_name ?? $employee->name }}</h4>
            <p class="qbo-section-desc">{{ __('Legal first and last name are required, all other info is optional.') }}</p>
            
            <div class="qbo-form-row">
                <div class="qbo-form-group small">
                    <label class="qbo-form-label">{{ __('Title') }}</label>
                    <input type="text" name="title" class="qbo-form-input" value="{{ $employee->title }}">
                </div>
            </div>
            
            <div class="qbo-form-row">
                <div class="qbo-form-group medium">
                    <label class="qbo-form-label required">{{ __('First name') }}</label>
                    <input type="text" name="first_name" class="qbo-form-input" value="{{ $employee->first_name }}" required>
                </div>
                <div class="qbo-form-group small">
                    <label class="qbo-form-label">{{ __('M.I.') }}</label>
                    <input type="text" name="middle_initial" class="qbo-form-input" value="{{ $employee->middle_initial }}" maxlength="1">
                </div>
                <div class="qbo-form-group medium">
                    <label class="qbo-form-label required">{{ __('Last name') }}</label>
                    <input type="text" name="last_name" class="qbo-form-input" value="{{ $employee->last_name }}" required>
                </div>
            </div>
            
            <div class="qbo-form-row">
                <div class="qbo-form-group medium">
                    <label class="qbo-form-label">{{ __('Preferred first name') }}</label>
                    <input type="text" name="preferred_first_name" class="qbo-form-input" value="{{ $employee->preferred_first_name }}">
                </div>
            </div>
            
            <div class="qbo-show-toggle" onclick="document.getElementById('displayNameRow').style.display = document.getElementById('displayNameRow').style.display === 'none' ? 'flex' : 'none'">
                <i class="ti ti-eye"></i> {{ __('Show display name') }}
            </div>
            
            <div class="qbo-form-row" id="displayNameRow" style="display: none;">
                <div class="qbo-form-group large">
                    <label class="qbo-form-label">{{ __('Display name') }}</label>
                    <input type="text" name="display_name" class="qbo-form-input" value="{{ $employee->display_name }}">
                </div>
            </div>
            
            <div class="qbo-form-row">
                <div class="qbo-form-group large">
                    <label class="qbo-form-label">{{ __('Email') }}</label>
                    <input type="email" name="email" class="qbo-form-input" value="{{ $employee->email }}">
                </div>
            </div>
            
            <div class="qbo-form-row">
                <div class="qbo-form-group medium">
                    <label class="qbo-form-label">{{ __('Home phone number') }}</label>
                    <input type="text" name="home_phone" class="qbo-form-input" value="{{ $employee->home_phone }}">
                </div>
                <div class="qbo-form-group small">
                    <label class="qbo-form-label">{{ __('ext.') }}</label>
                    <input type="text" name="home_phone_ext" class="qbo-form-input" value="{{ $employee->home_phone_ext }}">
                </div>
            </div>
            
            <div class="qbo-form-row">
                <div class="qbo-form-group medium">
                    <label class="qbo-form-label">{{ __('Work phone number') }}</label>
                    <input type="text" name="work_phone" class="qbo-form-input" value="{{ $employee->work_phone }}">
                </div>
                <div class="qbo-form-group small">
                    <label class="qbo-form-label">{{ __('ext.') }}</label>
                    <input type="text" name="work_phone_ext" class="qbo-form-input" value="{{ $employee->work_phone_ext }}">
                </div>
            </div>
            
            <div class="qbo-form-row">
                <div class="qbo-form-group medium">
                    <label class="qbo-form-label">{{ __('Mobile phone number') }}</label>
                    <input type="text" name="mobile_phone" class="qbo-form-input" value="{{ $employee->mobile_phone }}">
                </div>
            </div>
            
            <div class="qbo-form-row" style="width: 50vw;">
                <div class="qbo-form-group">
                    <label class="qbo-form-label">{{ __('Address') }}</label>
                    <input type="text" name="address" class="qbo-form-input" value="{{ $employee->address }}">
                </div>
            </div>
            
            <div class="qbo-form-row" style="width: 50vw;">
                <div class="qbo-form-group">
                    <label class="qbo-form-label">{{ __('City') }}</label>
                    <input type="text" name="city" class="qbo-form-input" value="{{ $employee->city }}">
                </div>
                <div class="qbo-form-group">
                    <label class="qbo-form-label">{{ __('State') }}</label>
                    <input type="text" name="state" class="qbo-form-input" value="{{ $employee->state }}">
                </div>
                <div class="qbo-form-group">
                    <label class="qbo-form-label">{{ __('ZIP code') }}</label>
                    <input type="text" name="zip" class="qbo-form-input" value="{{ $employee->zip }}">
                </div>
            </div>
            
            <div class="qbo-checkbox-row">
                <input type="checkbox" name="mailing_address_same" id="modal_mailing_address_same" value="1" {{ $employee->mailing_address_same ? 'checked' : '' }}>
                <label for="modal_mailing_address_same">{{ __('Mailing address is the same') }}</label>
            </div>
            
            {{-- Mailing Address Section (hidden when checkbox is checked) --}}
            <div id="modal_mailing_address_section" style="{{ $employee->mailing_address_same ? 'display: none;' : '' }}">
                <div class="qbo-form-row" style="width: 50vw;">
                    <div class="qbo-form-group">
                        <label class="qbo-form-label required">{{ __('Mailing address') }}</label>
                        <input type="text" name="mailing_address" class="qbo-form-input" value="{{ $employee->mailing_address }}" placeholder="{{ __('Search by address') }}">
                    </div>
                </div>
                
                <div class="qbo-form-row" style="width: 50vw;">
                    <div class="qbo-form-group">
                        <label class="qbo-form-label required">{{ __('City') }}</label>
                        <input type="text" name="mailing_city" class="qbo-form-input" value="{{ $employee->mailing_city }}">
                    </div>
                    <div class="qbo-form-group">
                        <label class="qbo-form-label required">{{ __('State') }}</label>
                        <input type="text" name="mailing_state" class="qbo-form-input" value="{{ $employee->mailing_state }}">
                    </div>
                    <div class="qbo-form-group">
                        <label class="qbo-form-label required">{{ __('ZIP code') }}</label>
                        <input type="text" name="mailing_zip" class="qbo-form-input" value="{{ $employee->mailing_zip }}">
                    </div>
                </div>
            </div>
            
            <div class="qbo-form-row">
                <div class="qbo-form-group medium">
                    <label class="qbo-form-label">{{ __('Birth date') }}</label>
                    <input type="date" name="birth_date" class="qbo-form-input" value="{{ $employee->birth_date }}">
                </div>
            </div>
            
            <div class="qbo-form-row">
                <div class="qbo-form-group medium">
                    <label class="qbo-form-label">{{ __('Gender') }}</label>
                    <select name="gender" class="qbo-form-select">
                        <option value="">{{ __('Select') }}</option>
                        <option value="Male" {{ $employee->gender == 'Male' ? 'selected' : '' }}>{{ __('Male') }}</option>
                        <option value="Female" {{ $employee->gender == 'Female' ? 'selected' : '' }}>{{ __('Female') }}</option>
                        <option value="Other" {{ $employee->gender == 'Other' ? 'selected' : '' }}>{{ __('Other') }}</option>
                    </select>
                </div>
            </div>
            
            <div class="qbo-form-row">
                <div class="qbo-form-group" style="flex: 0 0 200px;">
                    <label class="qbo-form-label">{{ __('Social Security number') }}</label>
                    <input type="text" name="ssn" id="modal_ssn" class="qbo-form-input" placeholder="XXX-XX-XXXX" value="{{ $employee->ssn }}" maxlength="11">
                    <a href="#" style="color: #0077c5; text-decoration: none; font-size: 13px; display: inline-block; margin-top: 8px;">{{ __('What if they only have an ITIN?') }}</a>
                </div>
                <div class="qbo-form-group" style="flex: 0 0 200px;">
                    <label class="qbo-form-label">{{ __('Confirm Social Security number') }}</label>
                    <input type="text" name="ssn_confirm" id="modal_ssn_confirm" class="qbo-form-input" placeholder="XXX-XX-XXXX" maxlength="11">
                    <div id="modal_ssn_error" style="display: none; color: #d52b1e; font-size: 13px; margin-top: 8px; align-items: center; gap: 4px;">
                        <i class="ti ti-alert-triangle" style="color: #d52b1e;"></i> {{ __("Numbers don't match. Try again.") }}
                    </div>
                </div>
            </div>
            </div>
        </div>
        
        <div class="qbo-modal-footer-bar">
            <div class="qbo-modal-footer-left">
                <button type="button" class="qbo-btn-cancel" onclick="closeModal('personalInfoModal')">{{ __('Cancel') }}</button>
            </div>
            <div class="qbo-modal-footer-right">
                <button type="submit" class="qbo-btn-save">{{ __('Save') }}</button>
            </div>
        </div>
    </form>
</div>

{{-- ======================= EDIT EMPLOYMENT DETAILS MODAL ======================= --}}
<div class="qbo-fullscreen-overlay" id="employmentDetailsModal">
    <div class="qbo-modal-header-bar">
        <h3 class="qbo-modal-title">{{ __('Edit employment details') }}</h3>
        <div class="qbo-modal-header-actions">
            <button class="qbo-modal-header-btn" title="Help"><i class="ti ti-help"></i></button>
            <button class="qbo-modal-header-btn" onclick="closeModal('employmentDetailsModal')">&times;</button>
        </div>
    </div>
    
    <form id="employmentDetailsForm" action="{{ route('employee.update', Crypt::encrypt($employee->id)) }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="form_type" value="employment_details">
        
        <div class="qbo-modal-content">
            <div>
            <h4 class="qbo-section-title">{{ __('Employment details') }}</h4>
            <p class="qbo-section-desc">{{ __('Manage employment information for') }} {{ $employee->first_name ?? $employee->name }}</p>
            
            <div class="qbo-form-row" style="width: 30vw;">
                <div class="qbo-form-group">
                    <label class="qbo-form-label">{{ __('Status') }}</label>
                    <select name="status" class="qbo-form-select">
                        <option value="Active" {{ ($employee->status ?? 'Active') == 'Active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                        <option value="Inactive" {{ $employee->status == 'Inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                    </select>
                </div>
            </div>
            
            <div class="qbo-form-row" style="width: 30vw;">
                <div class="qbo-form-group">
                    <label class="qbo-form-label">{{ __('Hire date') }}</label>
                    <input type="date" name="hire_date" class="qbo-form-input" value="{{ $employee->hire_date ?? $employee->company_doj }}">
                </div>
            </div>
            
            <div class="qbo-form-row" style="width: 30vw;">
                <div class="qbo-form-group">
                    <label class="qbo-form-label">{{ __('Manager') }}</label>
                    <select name="manager_id" class="qbo-form-select">
                        <option value="">{{ __('Select Manager') }}</option>
                        @php
                            $user = \Auth::user();
                            $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
                            $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
                            $managers = \App\Models\Employee::where($column, $ownerId)->where('id', '!=', $employee->id)->get();
                        @endphp
                        @foreach($managers as $manager)
                            <option value="{{ $manager->id }}" {{ $employee->manager_id == $manager->id ? 'selected' : '' }}>
                                {{ $manager->display_name ?? $manager->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            
            <div class="qbo-form-row" style="width: 30vw;">
                <div class="qbo-form-group">
                    <label class="qbo-form-label">{{ __('Department') }}</label>
                    <input type="text" name="department_name" class="qbo-form-input" value="{{ $employee->department_name }}">
                </div>
            </div>
            
            <div class="qbo-form-row" style="width: 30vw;">
                <div class="qbo-form-group">
                    <label class="qbo-form-label">{{ __('Job title') }}</label>
                    <input type="text" name="job_title" class="qbo-form-input" value="{{ $employee->job_title }}">
                </div>
            </div>
            
            <div class="qbo-form-row" style="width: 30vw;">
                <div class="qbo-form-group">
                    <label class="qbo-form-label">{{ __('Employee ID') }}</label>
                    <input type="text" name="employee_id" class="qbo-form-input" value="{{ $employee->employee_id }}">
                </div>
            </div>
            
            <div class="qbo-form-row" style="width: 30vw;">
                <div class="qbo-form-group">
                    <label class="qbo-form-label">{{ __('Name to print on checks') }}</label>
                    <input type="text" name="name_on_checks" class="qbo-form-input" value="{{ $employee->name_on_checks ?? $employee->display_name ?? $employee->name }}">
                </div>
            </div>
            
            <div class="qbo-form-row" style="width: 30vw;">
                <div class="qbo-form-group">
                    <label class="qbo-form-label">{{ __('Billing rate (/hr)') }}</label>
                    <input type="number" step="0.01" name="billing_rate" class="qbo-form-input" value="{{ $employee->billing_rate }}">
                </div>
            </div>
            
            <div class="qbo-checkbox-row">
                <input type="checkbox" name="billable_by_default" id="billable_by_default" value="1" {{ $employee->billable_by_default ? 'checked' : '' }}>
                <label for="billable_by_default">{{ __("This employee's time is billable by default") }}</label>
            </div>
            </div>
        </div>
        
        <div class="qbo-modal-footer-bar">
            <div class="qbo-modal-footer-left">
                <button type="button" class="qbo-btn-cancel" onclick="closeModal('employmentDetailsModal')">{{ __('Cancel') }}</button>
            </div>
            <div class="qbo-modal-footer-right">
                <button type="submit" class="qbo-btn-save">{{ __('Save') }}</button>
            </div>
        </div>
    </form>
</div>

{{-- ======================= EDIT EMERGENCY CONTACT MODAL ======================= --}}
<div class="qbo-fullscreen-overlay" id="emergencyContactModal">
    <div class="qbo-modal-header-bar">
        <h3 class="qbo-modal-title">{{ __('Edit emergency contact') }}</h3>
        <div class="qbo-modal-header-actions">
            <button class="qbo-modal-header-btn" title="Help"><i class="ti ti-help"></i></button>
            <button class="qbo-modal-header-btn" onclick="closeModal('emergencyContactModal')">&times;</button>
        </div>
    </div>
    
    <form id="emergencyContactForm" action="{{ route('employee.update', Crypt::encrypt($employee->id)) }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="form_type" value="emergency_contact">
        
        <div class="qbo-modal-content">
            <div class="qbo-modal-content-inner">
            <h4 class="qbo-section-title">{{ __('Emergency contact') }}</h4>
            <p class="qbo-section-desc">{{ __("Employee's contact in case of emergency. This could be their spouse, partner, or friend.") }}</p>
            
            <div class="qbo-form-row">
                <div class="qbo-form-group">
                    <label class="qbo-form-label">{{ __('First name') }}</label>
                    <input type="text" name="emergency_first_name" class="qbo-form-input" value="{{ $employee->emergency_first_name }}">
                </div>
                <div class="qbo-form-group">
                    <label class="qbo-form-label">{{ __('Last name') }}</label>
                    <input type="text" name="emergency_last_name" class="qbo-form-input" value="{{ $employee->emergency_last_name }}">
                </div>
            </div>
            
            <div class="qbo-form-row">
                <div class="qbo-form-group medium">
                    <label class="qbo-form-label">{{ __('Relationship') }}</label>
                    <select name="emergency_relationship" class="qbo-form-select">
                        <option value="">{{ __('Select') }}</option>
                        <option value="Spouse" {{ $employee->emergency_relationship == 'Spouse' ? 'selected' : '' }}>{{ __('Spouse') }}</option>
                        <option value="Partner" {{ $employee->emergency_relationship == 'Partner' ? 'selected' : '' }}>{{ __('Partner') }}</option>
                        <option value="Parent" {{ $employee->emergency_relationship == 'Parent' ? 'selected' : '' }}>{{ __('Parent') }}</option>
                        <option value="Sibling" {{ $employee->emergency_relationship == 'Sibling' ? 'selected' : '' }}>{{ __('Sibling') }}</option>
                        <option value="Friend" {{ $employee->emergency_relationship == 'Friend' ? 'selected' : '' }}>{{ __('Friend') }}</option>
                        <option value="Other" {{ $employee->emergency_relationship == 'Other' ? 'selected' : '' }}>{{ __('Other') }}</option>
                    </select>
                </div>
            </div>
            
            <div class="qbo-form-row">
                <div class="qbo-form-group medium">
                    <label class="qbo-form-label">{{ __('Phone number') }}</label>
                    <input type="text" name="emergency_phone" class="qbo-form-input" value="{{ $employee->emergency_phone }}">
                </div>
            </div>
            
            <div class="qbo-form-row">
                <div class="qbo-form-group large">
                    <label class="qbo-form-label">{{ __('Email') }}</label>
                    <input type="email" name="emergency_email" class="qbo-form-input" value="{{ $employee->emergency_email }}">
                </div>
            </div>
            </div>
        </div>
        
        <div class="qbo-modal-footer-bar">
            <div class="qbo-modal-footer-left">
                <button type="button" class="qbo-btn-cancel" onclick="closeModal('emergencyContactModal')">{{ __('Cancel') }}</button>
            </div>
            <div class="qbo-modal-footer-right">
                <button type="submit" class="qbo-btn-save">{{ __('Save') }}</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('script-page')
<script>
// Modal open/close functions
function openModal(modalId) {
    document.getElementById(modalId).classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
    document.body.style.overflow = '';
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.qbo-fullscreen-overlay.show').forEach(function(modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        });
    }
});

// Mailing Address Toggle
var mailingCheckbox = document.getElementById('modal_mailing_address_same');
var mailingSection = document.getElementById('modal_mailing_address_section');

if (mailingCheckbox && mailingSection) {
    mailingCheckbox.addEventListener('change', function() {
        mailingSection.style.display = this.checked ? 'none' : 'block';
    });
}

// SSN Validation
var ssnInput = document.getElementById('modal_ssn');
var ssnConfirm = document.getElementById('modal_ssn_confirm');
var ssnError = document.getElementById('modal_ssn_error');

// SSN Input Masking - Format as XXX-XX-XXXX
function formatSSN(input) {
    var value = input.value.replace(/\D/g, ''); // Remove non-digits
    if (value.length > 9) value = value.substring(0, 9); // Max 9 digits
    
    var formatted = '';
    if (value.length > 0) {
        formatted = value.substring(0, 3);
    }
    if (value.length > 3) {
        formatted += '-' + value.substring(3, 5);
    }
    if (value.length > 5) {
        formatted += '-' + value.substring(5, 9);
    }
    input.value = formatted;
}

if (ssnInput) {
    ssnInput.addEventListener('input', function() {
        formatSSN(this);
        if (ssnConfirm && ssnError) validateSSN();
    });
}

if (ssnConfirm) {
    ssnConfirm.addEventListener('input', function() {
        formatSSN(this);
        if (ssnInput && ssnError) validateSSN();
    });
}

function validateSSN() {
    var ssn = ssnInput.value.trim();
    var confirm = ssnConfirm.value.trim();
    
    if (confirm.length > 0 && ssn !== confirm) {
        ssnConfirm.style.borderColor = '#d52b1e';
        ssnConfirm.style.backgroundColor = '#fff5f5';
        ssnError.style.display = 'flex';
    } else {
        ssnConfirm.style.borderColor = '#babec5';
        ssnConfirm.style.backgroundColor = '#fff';
        ssnError.style.display = 'none';
    }
}

// Handle form submissions via AJAX
document.querySelectorAll('#personalInfoForm, #employmentDetailsForm, #emergencyContactForm').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        var submitBtn = this.querySelector('.qbo-btn-save');
        var originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = '{{ __("Saving...") }}';
        
        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.success !== false) {
                // Reload page to show updated data
                window.location.reload();
            } else {
                alert(data.message || '{{ __("Error saving. Please try again.") }}');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        })
        .catch(function(error) {
            console.error('Error:', error);
            // If it's a redirect response, reload the page
            window.location.reload();
        });
    });
});
</script>
@endpush
