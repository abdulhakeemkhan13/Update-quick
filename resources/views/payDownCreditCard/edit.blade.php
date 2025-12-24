@extends('layouts.admin')
@section('page-title')
    {{ __('Edit Credit Card Payment') }}
@endsection

@section('content')
<style>
    /* Same styles as create - QBO Trowser design */
    .qbo-trowser-header {
        font-size: 15px;
        font-weight: 600;
        height: 55px;
        background: #f4f5f8;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 999;
        padding: 0 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #e4e4e7;
    }
    
    .qbo-header-left { display: flex; align-items: center; gap: 12px; }
    .qbo-header-right { display: flex; align-items: center; gap: 4px; }
    
    .qbo-header-btn {
        background: none;
        border: none;
        padding: 8px;
        cursor: pointer;
        color: #393a3d;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .qbo-header-btn:hover { background: rgba(0,0,0,0.05); }
    .qbo-header-title { font-size: 1.25rem; font-weight: 600; margin: 0; color: #393a3d; }
    
    .qbo-form-container {
        margin-top: 55px;
        padding: 24px 32px;
        background: #f4f5f8;
        min-height: calc(100vh - 55px - 60px);
    }
    
    .qbo-form-wrapper { max-width: 700px; margin: 0 auto; }
    
    .qbo-form-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        padding: 24px;
    }
    
    .qbo-subheading { font-size: 14px; color: #6b7280; margin-bottom: 20px; }
    .qbo-form-group { margin-bottom: 20px; }
    .qbo-form-label { display: block; font-size: 13px; font-weight: 500; color: #393a3d; margin-bottom: 6px; }
    
    .qbo-form-control {
        width: 100%;
        padding: 10px 12px;
        font-size: 14px;
        border: 1px solid #babec5;
        border-radius: 4px;
        background: #fff;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    
    .qbo-form-control:focus { outline: none; border-color: #2ca01c; box-shadow: 0 0 0 2px rgba(44,160,28,0.2); }
    .qbo-form-control::placeholder { color: #9ca3af; }
    
    .qbo-amount-date-row { display: flex; gap: 20px; margin-bottom: 20px; }
    .qbo-amount-date-row .qbo-form-group { margin-bottom: 0; }
    .qbo-amount-field { flex: 0 0 200px; }
    .qbo-date-field { flex: 0 0 185px; }
    
    .qbo-total-paid { position: absolute; right: 32px; top: 24px; text-align: right; }
    .qbo-total-paid-label { font-size: 12px; color: #6b7280; margin-bottom: 4px; }
    .qbo-total-paid-value { font-size: 24px; font-weight: 600; color: #393a3d; }
    
    .qbo-accordion { margin-top: 24px; border-top: 1px solid #e4e4e7; padding-top: 16px; }
    
    .qbo-accordion-header {
        display: flex;
        align-items: center;
        gap: 8px;
        background: none;
        border: none;
        padding: 8px 0;
        cursor: pointer;
        font-size: 15px;
        font-weight: 600;
        color: #393a3d;
        width: 100%;
        text-align: left;
    }
    
    .qbo-accordion-header svg { transition: transform 0.2s; }
    .qbo-accordion-header.expanded svg { transform: rotate(90deg); }
    .qbo-accordion-content { display: none; padding: 16px 0; }
    .qbo-accordion-content.show { display: block; }
    
    .qbo-memo-label { font-size: 13px; color: #6b7280; margin-bottom: 8px; }
    
    .qbo-memo-textarea {
        width: 100%;
        max-width: 400px;
        min-height: 100px;
        padding: 10px 12px;
        font-size: 14px;
        border: 1px solid #babec5;
        border-radius: 4px;
        resize: vertical;
    }
    
    .qbo-memo-textarea:focus { outline: none; border-color: #2ca01c; box-shadow: 0 0 0 2px rgba(44,160,28,0.2); }
    
    .qbo-attachments-section { margin-top: 20px; }
    .qbo-attachments-label { font-size: 13px; color: #6b7280; margin-bottom: 8px; }
    
    .qbo-file-dropzone {
        border: 2px dashed #babec5;
        border-radius: 8px;
        padding: 24px;
        text-align: center;
        cursor: pointer;
        transition: border-color 0.2s, background 0.2s;
    }
    
    .qbo-file-dropzone:hover { border-color: #2ca01c; background: rgba(44,160,28,0.02); }
    .qbo-file-dropzone-text { color: #2ca01c; font-size: 14px; font-weight: 500; }
    .qbo-file-dropzone-hint { color: #6b7280; font-size: 12px; margin-top: 4px; }
    
    .qbo-privacy-link { text-align: center; margin-top: 24px; }
    .qbo-privacy-link a { color: #2ca01c; font-size: 13px; text-decoration: none; }
    .qbo-privacy-link a:hover { text-decoration: underline; }
    
    .qbo-trowser-footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        height: 60px;
        background: #fff;
        border-top: 1px solid #e4e4e7;
        box-shadow: 0 -2px 4px rgba(0,0,0,0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 24px;
        z-index: 998;
    }
    
    .qbo-footer-left { display: flex; align-items: center; }
    .qbo-footer-right { display: flex; align-items: center; gap: 8px; }
    
    .qbo-btn-cancel {
        padding: 8px 16px;
        font-size: 14px;
        font-weight: 500;
        color: #00892e;
        background: transparent;
        border: 1.5px solid #00892e;
        border-radius: 4px;
        cursor: pointer;
        transition: background 0.2s;
    }
    
    .qbo-btn-cancel:hover { background: rgba(0,137,46,0.05); }
    
    .qbo-btn-save {
        padding: 8px 16px;
        font-size: 14px;
        font-weight: 500;
        color: #00892e;
        background: #fff;
        border: 1.5px solid #00892e;
        border-radius: 4px;
        cursor: pointer;
    }
    
    .qbo-btn-save:hover { background: rgba(0,137,46,0.05); }
    
    .qbo-btn-primary-group { display: flex; }
    
    .qbo-btn-primary {
        padding: 8px 16px;
        font-size: 14px;
        font-weight: 500;
        color: #fff;
        background: #00892e;
        border: none;
        border-radius: 4px 0 0 4px;
        cursor: pointer;
    }
    
    .qbo-btn-primary:hover { background: #006b24; }
    
    .qbo-btn-primary-dropdown {
        padding: 8px 8px;
        font-size: 14px;
        color: #fff;
        background: #00892e;
        border: none;
        border-left: 1px solid rgba(255,255,255,0.3);
        border-radius: 0 4px 4px 0;
        cursor: pointer;
    }
    
    .qbo-btn-primary-dropdown:hover { background: #006b24; }
    
    .qbo-dropdown-menu {
        position: absolute;
        bottom: 100%;
        right: 0;
        background: #fff;
        border: 1px solid #e4e4e7;
        border-radius: 4px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        min-width: 160px;
        display: none;
        margin-bottom: 4px;
    }
    
    .qbo-dropdown-menu.show { display: block; }
    
    .qbo-dropdown-item {
        display: block;
        padding: 10px 16px;
        font-size: 14px;
        color: #393a3d;
        text-decoration: none;
        cursor: pointer;
    }
    
    .qbo-dropdown-item:hover { background: #f4f5f8; }
    
    .qbo-form-relative { position: relative; }
    
    .qbo-btn-delete {
        padding: 8px 16px;
        font-size: 14px;
        font-weight: 500;
        color: #dc2626;
        background: transparent;
        border: 1.5px solid #dc2626;
        border-radius: 4px;
        cursor: pointer;
        margin-left: 12px;
    }
    
    .qbo-btn-delete:hover { background: rgba(220,38,38,0.05); }
</style>

<div class="row">
    <!-- Header -->
    <div class="qbo-trowser-header">
        <div class="qbo-header-left">
            <button type="button" class="qbo-header-btn" title="{{ __('History') }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="24" height="24">
                    <path fill="currentColor" d="M13.007 7a1 1 0 0 0-1 1L12 12a1 1 0 0 0 1 1l3.556.006a1 1 0 0 0 0-2L14 11l.005-3a1 1 0 0 0-.998-1"></path>
                    <path fill="currentColor" d="M19.374 5.647A8.94 8.94 0 0 0 13.014 3H13a8.98 8.98 0 0 0-8.98 8.593l-.312-.312a1 1 0 0 0-1.416 1.412l2 2a1 1 0 0 0 1.414 0l2-2a1 1 0 0 0-1.412-1.416l-.272.272A6.984 6.984 0 0 1 13 5h.012A7 7 0 0 1 13 19h-.012a7 7 0 0 1-4.643-1.775 1 1 0 1 0-1.33 1.494A9 9 0 0 0 12.986 21H13a9 9 0 0 0 6.374-15.353"></path>
                </svg>
            </button>
            <h2 class="qbo-header-title">{{ __('Pay down credit card') }} #{{ $payment->id }}</h2>
        </div>
        <div class="qbo-header-right">
            <button type="button" class="qbo-header-btn" title="{{ __('Help') }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="24" height="24">
                    <path fill="currentColor" d="M12 15a1 1 0 1 0 0 2 1 1 0 0 0 0-2M15 10a3.006 3.006 0 0 0-3-3 3 3 0 0 0-2.9 2.27 1 1 0 1 0 1.937.494A1.02 1.02 0 0 1 12 9a1.006 1.006 0 0 1 1 1c0 .013.007.024.007.037s-.007.023-.007.036a.5.5 0 0 1-.276.447l-1.172.584A1 1 0 0 0 11 12v1a1 1 0 1 0 2 0v-.383l.619-.308a2.52 2.52 0 0 0 1.381-2.3z"></path>
                    <path fill="currentColor" d="M19.082 4.94A9.93 9.93 0 0 0 12.016 2H12a10 10 0 0 0-.016 20H12a10 10 0 0 0 7.082-17.06m-1.434 12.725A7.94 7.94 0 0 1 12 20h-.013A8 8 0 1 1 12 4h.012a8 8 0 0 1 5.636 13.665"></path>
                </svg>
            </button>
            <a href="{{ route('expense.index') }}" class="qbo-header-btn" title="{{ __('Close') }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="24" height="24">
                    <path fill="currentColor" d="m13.432 11.984 5.3-5.285a1 1 0 1 0-1.412-1.416l-5.3 5.285-5.285-5.3A1 1 0 1 0 5.319 6.68l5.285 5.3L5.3 17.265a1 1 0 1 0 1.412 1.416l5.3-5.285L17.3 18.7a1 1 0 1 0 1.416-1.412z"></path>
                </svg>
            </a>
        </div>
    </div>

    <!-- Form Container -->
    <div class="qbo-form-container">
        <div class="qbo-form-wrapper">
            {{ Form::open(['route' => ['paydowncreditcard.update', Crypt::encrypt($payment->id)], 'method' => 'PUT', 'id' => 'pay-down-credit-card-form', 'enctype' => 'multipart/form-data']) }}
            @csrf
            
            <div class="qbo-form-card qbo-form-relative">
                <!-- Total Paid Display -->
                <div class="qbo-total-paid">
                    <div class="qbo-total-paid-label">{{ __('Total paid') }}</div>
                    <div class="qbo-total-paid-value" id="totalPaidDisplay">${{ number_format($payment->amount, 2) }}</div>
                </div>
                
                <p class="qbo-subheading">{{ __('Record payments made to your balance') }}</p>
                
                <!-- Credit Card Account -->
                <div class="qbo-form-group">
                    <label class="qbo-form-label">{{ __('Which credit card did you pay?') }}</label>
                    <select name="credit_card_account_id" id="credit_card_account_id" class="qbo-form-control" required>
                        @foreach($creditCardAccounts as $id => $name)
                            <option value="{{ $id }}" {{ $payment->credit_card_account_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Payee (Optional) -->
                <div class="qbo-form-group">
                    <label class="qbo-form-label">{{ __('Payee (optional)') }}</label>
                    <select name="payee_id" id="payee_id" class="qbo-form-control">
                        @foreach($vendors as $id => $name)
                            <option value="{{ $id }}" {{ $payment->payee_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Amount and Date Row -->
                <div class="qbo-amount-date-row">
                    <div class="qbo-form-group qbo-amount-field">
                        <label class="qbo-form-label">{{ __('How much did you pay?') }}</label>
                        <input type="number" name="amount" id="amount" class="qbo-form-control" 
                               value="{{ $payment->amount }}" step="0.01" min="0" required>
                    </div>
                    <div class="qbo-form-group qbo-date-field">
                        <label class="qbo-form-label">{{ __('Date of payment') }}</label>
                        <input type="date" name="payment_date" id="payment_date" class="qbo-form-control" 
                               value="{{ $payment->payment_date->format('Y-m-d') }}" required>
                    </div>
                </div>
                
                <!-- Bank Account -->
                <div class="qbo-form-group">
                    <label class="qbo-form-label">{{ __('What did you use to make this payment?') }}</label>
                    <select name="bank_account_id" id="bank_account_id" class="qbo-form-control" required>
                        @foreach($bankAccounts as $id => $name)
                            <option value="{{ $id }}" {{ $payment->bank_account_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Memo and Attachments Accordion -->
                <div class="qbo-accordion">
                    <button type="button" class="qbo-accordion-header expanded" onclick="toggleAccordion(this)">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="20" height="20" style="transform: rotate(90deg);">
                            <path fill="currentColor" d="M9.009 19.013a1 1 0 0 1-.709-1.708l5.3-5.285-5.281-5.3a1 1 0 1 1 1.416-1.413l5.991 6.01a1 1 0 0 1 0 1.413l-6.011 5.991a1 1 0 0 1-.706.292"></path>
                        </svg>
                        <span>{{ __('Memo and attachments') }}</span>
                    </button>
                    <div class="qbo-accordion-content show">
                        <div class="qbo-memo-label">{{ __('Memo') }}</div>
                        <textarea name="memo" id="memo" class="qbo-memo-textarea">{{ $payment->memo }}</textarea>
                        
                        <div class="qbo-attachments-section">
                            <div class="qbo-attachments-label">{{ __('Attachments') }}</div>
                            <div class="qbo-file-dropzone" onclick="document.getElementById('attachments').click()">
                                <input type="file" name="attachments[]" id="attachments" multiple style="display: none;">
                                <div class="qbo-file-dropzone-text">{{ __('Add attachment') }}</div>
                                <div class="qbo-file-dropzone-hint">{{ __('Max file size: 20 MB') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Privacy Link -->
                <div class="qbo-privacy-link">
                    <a href="https://www.intuit.com/privacy/" target="_blank">{{ __('Privacy') }}</a>
                </div>
            </div>
            
            {{ Form::close() }}
        </div>
    </div>

    <!-- Footer -->
    <div class="qbo-trowser-footer">
        <div class="qbo-footer-left">
            <a href="{{ route('expense.index') }}" class="qbo-btn-cancel">{{ __('Cancel') }}</a>
            <form action="{{ route('paydowncreditcard.destroy', Crypt::encrypt($payment->id)) }}" method="POST" style="display: inline;" onsubmit="return confirm('{{ __('Are you sure you want to delete this payment?') }}');">
                @csrf
                @method('DELETE')
                <button type="submit" class="qbo-btn-delete">{{ __('Delete') }}</button>
            </form>
        </div>
        <div class="qbo-footer-right">
            <button type="button" class="qbo-btn-save" onclick="saveForm()">{{ __('Save') }}</button>
            <div class="qbo-btn-primary-group">
                <button type="button" class="qbo-btn-primary" onclick="saveForm()">{{ __('Save and close') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script-page')
<script>
    document.getElementById('amount').addEventListener('input', function() {
        var amount = parseFloat(this.value) || 0;
        var formatted = new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
        document.getElementById('totalPaidDisplay').textContent = formatted;
    });
    
    function toggleAccordion(btn) {
        btn.classList.toggle('expanded');
        var content = btn.nextElementSibling;
        content.classList.toggle('show');
        var svg = btn.querySelector('svg');
        if (btn.classList.contains('expanded')) {
            svg.style.transform = 'rotate(90deg)';
        } else {
            svg.style.transform = 'rotate(0deg)';
        }
    }
    
    function saveForm() {
        document.getElementById('pay-down-credit-card-form').submit();
    }
</script>
@endpush
