@extends('layouts.admin')
@section('page-title')
    {{ __('Edit Employment Details') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('employee.index') }}">{{ __('Employees') }}</a></li>
    <li class="breadcrumb-item">{{ __('Edit Employment Details') }}</li>
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
        <h3 class="qbo-header-title">{{ __('Edit employment details') }}</h3>
        <div class="qbo-header-actions">
            <button class="qbo-header-btn" title="Help"><i class="ti ti-help"></i></button>
            <a href="{{ route('employee.show', Crypt::encrypt($employee->id)) }}" class="qbo-header-btn">&times;</a>
        </div>
    </div>
    
    <form action="{{ route('employee.update', Crypt::encrypt($employee->id)) }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="form_type" value="employment_details">
        
        {{-- Scrollable Content Area --}}
        <div class="qbo-content-area">
            <h4 class="qbo-section-title">{{ __("Let's get down to") }} {{ $employee->first_name ?? $employee->name }}'s {{ __('employment specifics') }}</h4>
            
            <div class="qbo-form-row">
                <div class="qbo-form-group medium">
                    <label class="qbo-form-label">{{ __('Status') }}</label>
                    <select name="status" class="qbo-form-select">
                        <option value="Active" {{ ($employee->status ?? 'Active') == 'Active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                        <option value="Inactive" {{ $employee->status == 'Inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                    </select>
                </div>
            </div>
            
            <div class="qbo-form-row">
                <div class="qbo-form-group medium">
                    <label class="qbo-form-label">{{ __('Hire date') }}</label>
                    <input type="date" name="hire_date" class="qbo-form-input" value="{{ $employee->hire_date ?? $employee->company_doj }}">
                </div>
            </div>
            
            <div class="qbo-form-row">
                <div class="qbo-form-group large">
                    <label class="qbo-form-label">{{ __('Manager') }}</label>
                    <select name="manager_id" class="qbo-form-select">
                        <option value="">{{ __('Select a manager') }}</option>
                        @php
                            $user = \Auth::user();
                            $column = $user->type == 'company' ? 'created_by' : 'owned_by';
                            $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
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
            
            <div class="qbo-form-row">
                <div class="qbo-form-group large">
                    <label class="qbo-form-label">{{ __('Department') }}</label>
                    <select name="department_name" class="qbo-form-select">
                        <option value="">{{ __('Select a department') }}</option>
                        @if(isset($departments))
                            @foreach($departments as $id => $name)
                                <option value="{{ $name }}" {{ $employee->department_name == $name ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        @endif
                        @if($employee->department_name && !isset($departments[$employee->department_name]))
                            <option value="{{ $employee->department_name }}" selected>{{ $employee->department_name }}</option>
                        @endif
                    </select>
                </div>
            </div>
            
            <div class="qbo-form-row">
                <div class="qbo-form-group medium">
                    <label class="qbo-form-label">{{ __('Job title') }}</label>
                    <input type="text" name="job_title" class="qbo-form-input" value="{{ $employee->job_title }}">
                </div>
            </div>
            
            <div class="qbo-form-row">
                <div class="qbo-form-group medium">
                    <label class="qbo-form-label">{{ __('Employee ID') }}</label>
                    <input type="text" name="employee_id" class="qbo-form-input" value="{{ $employee->employee_id }}">
                </div>
            </div>
            
            <div class="qbo-form-row">
                <div class="qbo-form-group medium">
                    <label class="qbo-form-label">{{ __('Name to print on checks') }}</label>
                    <input type="text" name="name_on_checks" class="qbo-form-input" value="{{ $employee->name_on_checks ?? $employee->display_name ?? $employee->name }}">
                </div>
            </div>
            
            <div class="qbo-form-row">
                <div class="qbo-form-group medium">
                    <label class="qbo-form-label">{{ __('Billing rate (per hour)') }} <i class="ti ti-info-circle"></i></label>
                    <input type="text" name="billing_rate" class="qbo-form-input" value="{{ $employee->billing_rate ?? '0' }}" placeholder="$0">
                </div>
            </div>
            <a href="#" class="qbo-link">{{ __('Find out more') }}</a>
            
            <div class="qbo-checkbox-row">
                <input type="checkbox" name="billable_by_default" id="billable_by_default" value="1" {{ $employee->billable_by_default ? 'checked' : '' }}>
                <label for="billable_by_default">{{ __('Billable by default') }}</label>
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
