{{-- Transactions Tabs --}}
{{-- <div class="card mt-3"> --}}
<div class="mt-3">
    <div id="printableArea">
        <div class="row">
            <div class="col-12">
                <ul class="nav nav-tabs" id="transactionsTabs" role="tablist" style="padding: 8px 8px">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ Request::route()->getName() == 'transaction.bankTransactions' ? 'active' : '' }}"
                            href="{{ route('transaction.bankTransactions') }}">
                            <i class="ti ti-building-bank me-2"></i>{{ __('Bank transactions') }}
                        </a>
                    </li>
                    {{-- <li class="nav-item" role="presentation">
                        <a class="nav-link {{ Request::route()->getName() == 'transaction.index' || Request::route()->getName() == 'transfer.create' || Request::route()->getName() == 'transaction.edit' ? 'active' : '' }}"
                            href="{{ route('transaction.index') }}">
                            <i class="ti ti-exchange me-2"></i>{{ __('App transactions') }}
                        </a>
                    </li> --}}
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ Request::route()->getName() == 'reciept.index' ? 'active' : '' }}"
                            href="{{ route('reciept.index') }}">
                            <i class="ti ti-receipt me-2"></i>{{ __('Receipts') }}
                        </a>
                    </li>
                    {{-- <li class="nav-item" role="presentation">
                        <a class="nav-link"
                            href="#" onclick="showComingSoon()">
                            <i class="ti ti-refresh me-2"></i>{{ __('Reconcile') }}
                        </a>
                    </li> --}}
                    {{-- <li class="nav-item" role="presentation">
                        <a class="nav-link"
                            href="#" onclick="showComingSoon()">
                            <i class="ti ti-settings me-2"></i>{{ __('Rules') }}
                        </a>
                    </li> --}}
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ Request::route()->getName() == 'chart-of-account.index' || Request::route()->getName() == 'chart-of-account.show' ? 'active' : '' }}"
                            href="{{ route('chart-of-account.index') }}">
                            <i class="ti ti-chart-pie me-2"></i>{{ __('Chart of accounts') }}
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ Request::route()->getName() == 'report.recurring' ? 'active' : '' }}"
                            href="{{ route('report.recurring') }}">
                            <i class="ti ti-refresh me-2"></i>{{ __('Recurring transactions') }}
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
    .nav-tabs {
        border-bottom: 2px solid #e0e0e0;
        gap: 0;
        flex-wrap: nowrap;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        /* Hide scrollbar for Firefox */
        padding: 0 8px;
        background: transparent;
    }

    /* Hide scrollbar for Chrome, Safari and Opera */
    .nav-tabs::-webkit-scrollbar {
        display: none;
    }

    .nav-tabs .nav-item {
        flex-shrink: 0;
        margin-bottom: 0;
    }

    .nav-tabs .nav-link {
        border: none;
        border-radius: 0;
        color: #6c757d;
        font-weight: 500;
        padding: 12px 16px;
        transition: all 0.3s ease;
        background-color: transparent;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        white-space: nowrap;
        min-width: fit-content;
        position: relative;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
    }

    .nav-tabs .nav-link:hover {
        color: #28a745;
        background-color: transparent;
        border-bottom: 3px solid #28a745;
    }

    .nav-tabs .nav-link.active {
        color: #28a745 !important;
        background-color: transparent !important;
        border-bottom: 3px solid #28a745 !important;
        font-weight: 600;
    }

    .nav-tabs .nav-link i {
        font-size: 1rem;
        margin-right: 6px;
    }

    @media (max-width: 768px) {
        .nav-tabs {
            gap: 0;
            padding: 0 4px;
        }

        .nav-tabs .nav-link {
            padding: 10px 12px;
            font-size: 0.875rem;
        }

        .nav-tabs .nav-link i {
            font-size: 0.875rem;
            margin-right: 4px;
        }
    }

    @media (max-width: 576px) {
        .nav-tabs .nav-link {
            padding: 8px 10px;
            font-size: 0.8rem;
        }

        .nav-tabs .nav-link i {
            display: none;
            /* Hide icons on very small screens to save space */
        }
    }
</style>

<script>
    function showComingSoon() {
        if (typeof show_toastr !== 'undefined') {
            show_toastr('info', '{{ __('This feature is coming soon!') }}', 'info');
        } else {
            alert('{{ __('This feature is coming soon!') }}');
        }
    }
</script>
