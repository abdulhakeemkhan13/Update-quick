{{-- Bank Accounts Widget - QBO Style --}}
<div class="qbo-widget-card">
    <div class="qbo-widget-header">
        <div class="qbo-widget-title">
            <span>{{ __('BANK ACCOUNTS') }}</span>
        </div>
        <div class="qbo-widget-period">
            <span class="text-muted">{{ __('As of today') }}</span>
        </div>
    </div>
    <div class="qbo-widget-body">
        @php
            $bankAccounts = \App\Models\BankAccount::where('created_by', \Auth::user()->creatorId())->get();
            $totalBalance = $bankAccounts->sum('opening_balance');
        @endphp
        
        <div class="qbo-bank-header">
            <div class="qbo-bank-label">{{ __("Today's bank balance") }}</div>
            <div class="qbo-bank-total {{ $totalBalance >= 0 ? '' : 'text-danger' }}">
                {{ \Auth::user()->priceFormat($totalBalance) }}
                <i class="ti ti-info-circle text-primary"></i>
            </div>
        </div>

        <div class="qbo-bank-list">
            @forelse($bankAccounts->take(3) as $account)
                <div class="qbo-bank-item">
                    <div class="qbo-bank-icon">
                        <i class="ti ti-building-bank"></i>
                    </div>
                    <div class="qbo-bank-details">
                        <div class="qbo-bank-name">{{ $account->bank_name }}</div>
                        <div class="qbo-bank-meta">
                            <span>{{ __('Bank balance') }}</span>
                            <span>{{ __('In QuickBooks') }}</span>
                            <span class="text-muted">{{ __('Updated moments ago') }}</span>
                        </div>
                    </div>
                    <div class="qbo-bank-amounts">
                        <div class="qbo-bank-balance {{ $account->opening_balance >= 0 ? '' : 'text-danger' }}">
                            {{ \Auth::user()->priceFormat($account->opening_balance) }}
                        </div>
                        <div class="qbo-bank-qb">
                            {{ \Auth::user()->priceFormat($account->opening_balance) }}
                        </div>
                        <div class="qbo-bank-review text-primary">
                            {{ rand(5, 30) }} {{ __('to review') }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="qbo-empty-state">
                    <i class="ti ti-building-bank"></i>
                    <p>{{ __('No bank accounts connected') }}</p>
                </div>
            @endforelse
        </div>

        <div class="qbo-widget-footer">
            <a href="{{ route('bank-account.index') }}" class="qbo-link">{{ __('Go to registers') }}</a>
            <div class="qbo-footer-actions">
                <button class="qbo-icon-btn"><i class="ti ti-settings"></i></button>
                <button class="qbo-menu-btn"><i class="ti ti-dots-vertical"></i></button>
            </div>
        </div>
    </div>
</div>
