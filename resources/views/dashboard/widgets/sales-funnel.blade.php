{{-- Sales & Get Paid Funnel Widget - QBO Style --}}
<div class="qbo-widget-card">
    <div class="qbo-widget-header">
        <div class="qbo-widget-title">
            <span>{{ __('SALES & GET PAID FUNNEL') }}</span>
        </div>
        <div class="qbo-widget-period">
            <span class="text-muted">{{ __('Last 30 days') }}</span>
            <i class="ti ti-chevron-down"></i>
        </div>
    </div>
    <div class="qbo-widget-body">
        @php
            $invoices = \App\Models\Invoice::where('created_by', \Auth::user()->creatorId());
            $notPaid = $invoices->clone()->whereIn('status', [0, 1, 2])->sum('total_amount');
            $paid = $invoices->clone()->whereIn('status', [3, 4])->sum('total_amount');
            $paidCount = $invoices->clone()->whereIn('status', [3, 4])->count();
            $deposited = $invoices->clone()->where('status', 4)->sum('total_amount');
            $depositedCount = $invoices->clone()->where('status', 4)->count();
        @endphp
        
        <div class="qbo-funnel-container">
            {{-- Create New Payment Request --}}
            <div class="qbo-funnel-stage qbo-funnel-create">
                <div class="qbo-funnel-box">
                    <div class="qbo-funnel-box-content">
                        <strong>{{ __('Create a new') }}</strong>
                        <strong>{{ __('payment request') }}</strong>
                        <p class="text-muted mt-2">{{ __('Send invoices, payment links, and...') }}</p>
                        <div class="qbo-funnel-dropdown mt-3">
                            <button class="qbo-btn-dropdown">
                                {{ __('Request pay...') }}
                                <i class="ti ti-chevron-down"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Not Paid --}}
            <div class="qbo-funnel-stage">
                <div class="qbo-funnel-bar qbo-funnel-bar-warning"></div>
                <div class="qbo-funnel-label">{{ __('Not Paid') }}</div>
                <div class="qbo-funnel-amount">{{ \Auth::user()->priceFormat($notPaid) }}</div>
            </div>

            {{-- Paid --}}
            <div class="qbo-funnel-stage">
                <div class="qbo-funnel-bar qbo-funnel-bar-success"></div>
                <div class="qbo-funnel-label">{{ __('Paid') }}</div>
                <div class="qbo-funnel-amount">{{ \Auth::user()->priceFormat($paid) }}</div>
                <div class="qbo-funnel-count">
                    <i class="ti ti-circle-check text-success"></i>
                    {{ $paidCount }} {{ __('paid') }}
                </div>
            </div>

            {{-- Deposited --}}
            <div class="qbo-funnel-stage">
                <div class="qbo-funnel-bar qbo-funnel-bar-primary"></div>
                <div class="qbo-funnel-label">{{ __('Deposited') }}</div>
                <div class="qbo-funnel-amount">{{ \Auth::user()->priceFormat($deposited) }}</div>
                <div class="qbo-funnel-count">
                    <i class="ti ti-circle-check text-success"></i>
                    {{ $depositedCount }} {{ __('deposited') }}
                </div>
            </div>
        </div>
    </div>
</div>
