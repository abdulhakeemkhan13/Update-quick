{{-- Sales & Get Paid Funnel Widget - Exact QBO Style --}}
<div class="qbo-funnel-widget">
    <p class="qbo-funnel-subtitle">{{ __('Send a payment request, get paid, and track all your money-in') }}</p>
    
    @php
        $invoices = \App\Models\Invoice::where('created_by', \Auth::user()->creatorId());
        $notPaid = $invoices->clone()->whereIn('status', [0, 1, 2])->sum('total_amount');
        $paid = $invoices->clone()->whereIn('status', [3, 4])->sum('total_amount');
        $deposited = $invoices->clone()->where('status', 4)->sum('total_amount');
    @endphp
    
    <div class="qbo-funnel-stages">
        {{-- Create New Payment Request --}}
        <div class="qbo-funnel-stage qbo-funnel-create">
            <div class="qbo-funnel-stage-title">{{ __('Create a new payment') }}</div>
            <div class="qbo-funnel-stage-title">{{ __('request') }}</div>
            <p class="qbo-funnel-stage-desc">{{ __('Send invoices, payment links, and more') }}</p>
            <a href="#" class="qbo-funnel-learn-more">{{ __('Learn more') }}</a>
            <div class="qbo-funnel-dropdown-wrapper">
                <div class="dropdown">
                    <button class="qbo-funnel-dropdown-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        {{ __('Request payment') }}
                        <i class="ti ti-chevron-down"></i>
                    </button>
                    <ul class="dropdown-menu qbo-funnel-dropdown-menu">
                        <li><a class="dropdown-item" href="#">{{ __('Invoice') }}</a></li>
                        <li><a class="dropdown-item" href="#">{{ __('Payment link') }}</a></li>
                        <li><a class="dropdown-item" href="#">{{ __('Recurring payment') }}</a></li>
                        <li><a class="dropdown-item" href="#">{{ __('Charge a payment') }}</a></li>
                        <li><a class="dropdown-item" href="#">{{ __('Tap to Pay on iPhone') }}</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        {{-- Not Paid --}}
        <div class="qbo-funnel-stage">
            <div class="qbo-funnel-stage-label">{{ __('Not Paid') }}</div>
            <div class="qbo-funnel-stage-amount">{{ \Auth::user()->priceFormat($notPaid) }}</div>
        </div>
        
        {{-- Paid --}}
        <div class="qbo-funnel-stage">
            <div class="qbo-funnel-stage-label">{{ __('Paid') }}</div>
            <div class="qbo-funnel-stage-amount">{{ \Auth::user()->priceFormat($paid) }}</div>
        </div>
        
        {{-- Deposited --}}
        <div class="qbo-funnel-stage">
            <div class="qbo-funnel-stage-label">{{ __('Deposited') }}</div>
            <div class="qbo-funnel-stage-amount">{{ \Auth::user()->priceFormat($deposited) }}</div>
        </div>
    </div>
</div>

<style>
.qbo-funnel-widget {
    padding: 0;
}

.qbo-funnel-subtitle {
    font-size: 18px;
    font-weight: 400;
    color: #333;
    margin: 0 0 20px 0;
    line-height: 1.4;
}

.qbo-funnel-stages {
    display: flex;
    gap: 16px;
}

.qbo-funnel-stage {
    flex: 1;
    background: #fafafa;
    border-radius: 4px;
    padding: 16px;
    min-height: 140px;
    transition: background-color 0.15s ease, box-shadow 0.15s ease;
    cursor: pointer;
}

.qbo-funnel-stage:hover {
    background: #f0f0f0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.qbo-funnel-create {
    border-left: 4px solid #2ca01c;
    padding-left: 14px;
}

.qbo-funnel-stage-title {
    font-size: 14px;
    font-weight: 600;
    color: #333;
    line-height: 1.3;
}

.qbo-funnel-stage-desc {
    font-size: 12px;
    color: #6b6b6b;
    margin: 8px 0;
    line-height: 1.4;
}

.qbo-funnel-learn-more {
    font-size: 12px;
    color: #0077c5;
    text-decoration: none;
    display: inline-block;
    margin-bottom: 12px;
}

.qbo-funnel-learn-more:hover {
    text-decoration: underline;
    color: #005a94;
}

.qbo-funnel-dropdown-wrapper {
    margin-top: 8px;
}

.qbo-funnel-dropdown-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 12px;
    font-size: 13px;
    font-weight: 400;
    color: #333;
    background: #fff;
    border: 1px solid #babec5;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.15s;
}

.qbo-funnel-dropdown-btn:hover {
    background: #f5f5f5;
    border-color: #8d9096;
}

.qbo-funnel-dropdown-btn i {
    font-size: 12px;
    color: #6b6b6b;
}

.qbo-funnel-dropdown-menu {
    min-width: 180px;
    padding: 4px 0;
    border: 1px solid #e0e0e0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    border-radius: 4px;
}

.qbo-funnel-dropdown-menu .dropdown-item {
    padding: 10px 16px;
    font-size: 13px;
    color: #333;
}

.qbo-funnel-dropdown-menu .dropdown-item:hover {
    background: #f5f5f5;
}

.qbo-funnel-stage-label {
    font-size: 13px;
    color: #6b6b6b;
    margin-bottom: 10px;
}

.qbo-funnel-stage-amount {
    font-size: 20px;
    font-weight: 700;
    color: #333;
}
</style>
