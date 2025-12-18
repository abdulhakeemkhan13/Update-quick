@extends('layouts.admin')
@section('page-title')
    {{ __('Edit Emergency Contact') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('employee.index') }}">{{ __('Employees') }}</a></li>
    <li class="breadcrumb-item">{{ __('Edit Emergency Contact') }}</li>
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
        <h3 class="qbo-header-title">{{ __('Edit emergency contact') }}</h3>
        <div class="qbo-header-actions">
            <a href="{{ route('employee.show', Crypt::encrypt($employee->id)) }}" class="qbo-header-btn">&times;</a>
        </div>
    </div>
    
    <form action="{{ route('employee.update', Crypt::encrypt($employee->id)) }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="form_type" value="emergency_contact">
        
        {{-- Scrollable Content Area --}}
        <div class="qbo-content-area">
            <h4 class="qbo-section-title">{{ __("Who's") }} {{ $employee->first_name ?? $employee->name }}'s {{ __('emergency contact?') }}</h4>
            
            <div class="qbo-form-row">
                <div class="qbo-form-group medium">
                    <label class="qbo-form-label required">{{ __('First name') }}</label>
                    <input type="text" name="emergency_first_name" class="qbo-form-input" value="{{ $employee->emergency_first_name }}" required>
                </div>
                <div class="qbo-form-group medium">
                    <label class="qbo-form-label required">{{ __('Last name') }}</label>
                    <input type="text" name="emergency_last_name" class="qbo-form-input" value="{{ $employee->emergency_last_name }}" required>
                </div>
            </div>
            
            <div class="qbo-form-row">
                <div class="qbo-form-group medium">
                    <label class="qbo-form-label required">{{ __('Relationship') }}</label>
                    <input type="text" name="emergency_relationship" class="qbo-form-input" value="{{ $employee->emergency_relationship }}" required>
                </div>
            </div>
            
            <div class="qbo-form-row">
                <div class="qbo-form-group medium">
                    <label class="qbo-form-label required">{{ __('Phone number') }}</label>
                    <input type="text" name="emergency_phone" class="qbo-form-input" value="{{ $employee->emergency_phone }}" required>
                </div>
            </div>
            
            <div class="qbo-form-row">
                <div class="qbo-form-group medium">
                    <label class="qbo-form-label">{{ __('Email address') }}</label>
                    <input type="email" name="emergency_email" class="qbo-form-input" value="{{ $employee->emergency_email }}">
                </div>
            </div>
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
