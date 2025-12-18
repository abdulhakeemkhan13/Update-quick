@extends('layouts.admin')
@section('page-title')
    {{ __('Edit Personal Info') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('employee.index') }}">{{ __('Employees') }}</a></li>
    <li class="breadcrumb-item">{{ __('Edit Personal Info') }}</li>
@endsection

@push('css-page')
<style>
/* QBO Full Screen Modal Styles */
.qbo-fullscreen-container {
    min-height: 100vh;
    background: #f5f5f5;
    margin: -24px;
    position: relative;
}

/* Green Header Bar */
.qbo-header-bar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 56px;
    background: #2ca01c;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    z-index: 1050;
}
.qbo-header-title {
    color: #fff;
    font-size: 18px;
    font-weight: 500;
    margin: 0;
}
.qbo-header-actions {
    display: flex;
    align-items: center;
    gap: 16px;
}
.qbo-header-btn {
    background: none;
    border: none;
    color: #fff;
    font-size: 24px;
    cursor: pointer;
    padding: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
}
.qbo-header-btn:hover {
    opacity: 0.8;
    color: #fff;
}

/* Scrollable Content Area */
.qbo-content-area {
    padding: 80px 40px 100px 40px;
    max-width: 800px;
    margin: 0 auto;
}

/* Fixed Footer Bar */
.qbo-footer-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: 64px;
    background: #fff;
    border-top: 1px solid #e0e3e5;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    z-index: 1050;
}
.qbo-footer-left {
    display: flex;
    align-items: center;
    gap: 12px;
}
.qbo-footer-right {
    display: flex;
    align-items: center;
    gap: 12px;
}
.qbo-btn-cancel {
    background: #fff;
    border: 2px solid #2ca01c;
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

/* Form Styles */
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
.qbo-checkbox-row label {
    font-size: 14px;
    color: #393a3d;
}
.qbo-link {
    color: #0077c5;
    text-decoration: none;
    font-size: 13px;
}
.qbo-link:hover {
    text-decoration: underline;
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
    .qbo-content-area { padding: 70px 20px 80px 20px; }
    .qbo-form-row { flex-direction: column; gap: 16px; }
    .qbo-form-group.small, .qbo-form-group.medium, .qbo-form-group.large { flex: 1; }
}
</style>
@endpush

@section('content')
<div class="qbo-fullscreen-container">
    {{-- Green Header Bar --}}
    <div class="qbo-header-bar">
        <h3 class="qbo-header-title">{{ __('Edit personal info') }}</h3>
        <div class="qbo-header-actions">
            <button class="qbo-header-btn" title="Help"><i class="ti ti-help"></i></button>
            <a href="{{ route('employee.show', Crypt::encrypt($employee->id)) }}" class="qbo-header-btn">&times;</a>
        </div>
    </div>
    
    <form action="{{ route('employee.update', Crypt::encrypt($employee->id)) }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="form_type" value="personal_info">
        
        {{-- Scrollable Content Area --}}
        <div class="qbo-content-area">
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
            
            <div class="qbo-show-toggle" id="showDisplayNameToggle" onclick="document.getElementById('displayNameRow').style.display = document.getElementById('displayNameRow').style.display === 'none' ? 'flex' : 'none'">
                <i class="ti ti-eye"></i> {{ __('Show display name') }} <i class="ti ti-info-circle"></i>
            </div>
            
            <div class="qbo-form-row" id="displayNameRow" style="display: none;">
                <div class="qbo-form-group large">
                    <label class="qbo-form-label required">{{ __('Display name') }} <i class="ti ti-info-circle"></i></label>
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
            
            <div class="qbo-form-row">
                <div class="qbo-form-group">
                    <label class="qbo-form-label">{{ __('Address') }}</label>
                    <div style="position: relative;">
                        <i class="ti ti-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #6b6c72;"></i>
                        <input type="text" name="address" class="qbo-form-input" style="padding-left: 36px;" value="{{ $employee->address }}" placeholder="{{ __('Search by address') }}">
                    </div>
                </div>
            </div>
            
            <div class="qbo-form-row">
                <div class="qbo-form-group">
                    <label class="qbo-form-label">{{ __('City') }}</label>
                    <input type="text" name="city" class="qbo-form-input" value="{{ $employee->city }}">
                </div>
                <div class="qbo-form-group">
                    <label class="qbo-form-label">{{ __('State') }}</label>
                    <select name="state" class="qbo-form-select">
                        <option value="">{{ __('Select') }}</option>
                        <option value="{{ $employee->state }}" selected>{{ $employee->state }}</option>
                    </select>
                </div>
                <div class="qbo-form-group">
                    <label class="qbo-form-label">{{ __('ZIP code') }}</label>
                    <input type="text" name="zip" class="qbo-form-input" value="{{ $employee->zip }}">
                </div>
            </div>
            
            <div class="qbo-checkbox-row">
                <input type="checkbox" name="mailing_address_same" id="mailing_address_same" value="1" {{ $employee->mailing_address_same ? 'checked' : '' }}>
                <label for="mailing_address_same">{{ __('Mailing address is the same') }}</label>
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
                <div class="qbo-form-group medium">
                    <label class="qbo-form-label">{{ __('Social Security number') }}</label>
                    <input type="text" name="ssn" class="qbo-form-input" placeholder="XXX-XX-XXXX" value="{{ $employee->ssn }}">
                </div>
                <div class="qbo-form-group medium">
                    <label class="qbo-form-label">{{ __('Confirm Social Security number') }}</label>
                    <input type="text" name="ssn_confirm" class="qbo-form-input" placeholder="XXX-XX-XXXX">
                </div>
            </div>
            <a href="#" class="qbo-link">{{ __('What if they only have an ITIN?') }}</a>
        </div>
        
        {{-- Fixed Footer Bar --}}
        <div class="qbo-footer-bar">
            <div class="qbo-footer-left">
                <a href="{{ route('employee.show', Crypt::encrypt($employee->id)) }}" class="qbo-btn-cancel">{{ __('Cancel') }}</a>
            </div>
            <div class="qbo-footer-right">
                <button type="submit" class="qbo-btn-save">{{ __('Save') }}</button>
            </div>
        </div>
    </form>
</div>
@endsection
