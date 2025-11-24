{{-- Sales Tabs --}}
<div class="mt-3">
    <div id="printableArea">
        <div class="row">
            <div class="col-12">
                <ul class="nav nav-tabs" id="salesTabs" role="tablist" style="padding: 8px 8px">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ Request::route()->getName() == 'allSales' ? 'active' : '' }}"
                            href="{{ route('allSales') }}">
                            <i class="ti ti-chart-line me-2"></i>{{ __('All Sales') }}
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ in_array(Request::route()->getName(), ['invoice.index','invoice.create','invoice.edit','invoice.show']) ? 'active' : '' }}"
                            href="{{ route('invoice.index') }}">
                            <i class="ti ti-file-invoice me-2"></i>{{ __('Invoices') }}
                        </a>
                    </li>
                    @if (Gate::check('manage proposal'))
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ Request::segment(1) == 'proposal' ? 'active' : '' }}"
                                href="{{ route('proposal.index') }}">
                                <i class="ti ti-file-text me-2"></i>{{ __('Estimates') }}
                            </a>
                        </li>
                    @endif
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" href="#" onclick="showComingSoon()">
                            <i class="ti ti-shopping-cart me-2"></i>{{ __('Sales Orders') }}
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ Request::route()->getName() == 'invoice.recurring-invoices' ? 'active' : '' }}" href="{{ route('invoice.recurring-invoices')}}">
                            <i class="ti ti-refresh me-2"></i>{{ __('Recurring Invoices') }}
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ Request::route()->getName() == 'report.income.summaryTransactionsDeposites' ? 'active' : '' }}"
                             href="{{ route('report.income.summaryTransactionsDeposites') }}">
                            <i class="ti ti-credit-card me-2"></i>{{ __('Deposits') }}
                        </a>
                    </li>
                    @if (Gate::check('manage customer'))
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ Request::segment(1) == 'customer' ? 'active' : '' }}"
                                href="{{ route('customer.index') }}">
                                <i class="ti ti-users me-2"></i>{{ __('Customers') }}
                            </a>
                        </li>
                    @endif
                    @if (Gate::check('manage product & service'))
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ Request::segment(1) == 'productservice' ? 'active' : '' }}"
                                href="{{ route('productservice.index') }}">
                                <i class="ti ti-package me-2"></i>{{ __('Product & Services') }}
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
    .nav-tabs {
        border-bottom: 2px solid #e0e0e0;
        flex-wrap: nowrap;
        overflow-x: auto;              
        overflow-y: hidden;            
        -webkit-overflow-scrolling: touch;
        padding: 0 8px;
        background: transparent;

        scrollbar-width: none; /* hide scrollbar in Firefox */
    }

    /* Hide scrollbar Chrome/Safari/Edge */
    .nav-tabs::-webkit-scrollbar {
        height: 6px;
        display: none;
    }

    /* Show scrollbar only on hover */
    .nav-tabs:hover {
        scrollbar-width: thin;                /* Firefox */
        /* scrollbar-color: #28a745 #f1f1f1; */
    }
    .nav-tabs:hover::-webkit-scrollbar {
        display: block;                       /* Chrome/Safari/Edge */
    }
    .nav-tabs::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    .nav-tabs::-webkit-scrollbar-thumb {
        background-color: #28a745;
        border-radius: 3px;
    }
    .nav-tabs::-webkit-scrollbar-thumb:hover {
        background-color: #218838;
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
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
    }

    .nav-tabs .nav-link:hover {
        color: #28a745;
        border-bottom: 3px solid #28a745;
    }

    .nav-tabs .nav-link.active {
        color: #28a745 !important;
        border-bottom: 3px solid #28a745 !important;
        font-weight: 600;
    }

    .nav-tabs .nav-link i {
        font-size: 1rem;
        margin-right: 6px;
    }

    @media (max-width: 768px) {
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
