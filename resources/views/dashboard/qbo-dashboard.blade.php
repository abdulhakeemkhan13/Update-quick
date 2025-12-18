@extends('layouts.admin')

@push('css-page')
    {{-- Gridstack CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/gridstack@10.3.1/dist/gridstack.min.css">
    {{-- QBO Dashboard CSS --}}
    <link rel="stylesheet" href="{{ asset('css/qbo-dashboard.css') }}">
@endpush

@section('content')
    @if (\Auth::user()->can('show account dashboard'))
    {{-- Fixed Header Section: Welcome + Navigation --}}
    <div class="qbo-fixed-header">
        <div class="container-fluid">
            {{-- Welcome Title --}}
            <div class="qbo-welcome-row">
                <h2 class="qbo-welcome-title">{{ __('Welcome!') }}</h2>
            </div>
            
            {{-- Navigation Chips Row --}}
            <div class="qbo-nav-row">
                <div class="qbo-nav-container">
                    <a href="{{ route('transaction.bankTransactions') }}" class="qbo-nav-chip">
                        <span class="qbo-nav-icon" style="background: linear-gradient(135deg, #1E88E5, #1565C0);"><img src="https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/accounting/1/0/0/accounting.svg" class="chip-icon" alt="" style="width: 28px;"></span>
                        <span>{{ __('Accounting') }}</span>
                    </a>
                    <a href="#" class="qbo-nav-chip">
                        <span class="qbo-nav-icon" style="background: linear-gradient(135deg, #43A047, #2E7D32);"><img style="width: 28px;" src="https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/expenses/1/0/0/expenses.svg" class="chip-icon" alt=""></span>
                        <span>{{ __('Expenses & Pay Bills') }}</span>
                    </a>
                    <a href="#" class="qbo-nav-chip">
                        <span class="qbo-nav-icon" style="background: linear-gradient(135deg, #00897B, #00695C);"><img style="width: 28px;" src="https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/sales-payments/1/0/0/sales-payments.svg" class="chip-icon" alt=""></span>
                        <span>{{ __('Sales & Get Paid') }}</span>
                    </a>
                    <a href="#" class="qbo-nav-chip">
                        <span class="qbo-nav-icon" style="background: linear-gradient(135deg, #00ACC1, #00838F);"><img style="width: 28px;" src="https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/customers/1/0/0/customers.svg" class="chip-icon" alt=""></span>
                        <span>{{ __('Customers') }}</span>
                    </a>
                    <a href="#" class="qbo-nav-chip">
                        <span class="qbo-nav-icon" style="background: linear-gradient(135deg, #5E35B1, #4527A0);"><img style="width: 28px;" src="https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/team/1/0/0/team.svg" class="chip-icon" alt=""></span>
                        <span>{{ __('Team') }}</span>
                    </a>
                    <a href="#" class="qbo-nav-chip">
                        <span class="qbo-nav-icon" style="background: linear-gradient(135deg, #3949AB, #283593);"><img style="width: 28px;" src="https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/time/1/0/0/time.svg" class="chip-icon" alt=""></span>
                        <span>{{ __('Time') }}</span>
                    </a>
                    <a href="#" class="qbo-nav-chip">
                        <span class="qbo-nav-icon" style="background: linear-gradient(135deg, #039BE5, #0277BD);"><img style="width: 28px;" src="https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/inventory/1/0/0/inventory.svg" class="chip-icon" alt=""></span>
                        <span>{{ __('Inventory') }}</span>
                    </a>
                    <a href="#" class="qbo-nav-chip">
                        <span class="qbo-nav-icon" style="background: linear-gradient(135deg, #E53935, #C62828);"><img style="width: 28px;" src="https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/sales-tax/1/0/0/sales-tax.svg" class="chip-icon" alt=""></span>
                        <span>{{ __('Sales Tax') }}</span>
                    </a>
                    <a href="#" class="qbo-nav-chip">
                        <span class="qbo-nav-icon" style="background: linear-gradient(135deg, #8E24AA, #6A1B9A);"><img style="width: 28px;" src="https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/business-tax/1/0/0/business-tax.svg" class="chip-icon" alt=""></span>
                        <span>{{ __('Business Tax') }}</span>
                    </a>
                    <a href="#" class="qbo-nav-chip">
                        <span class="qbo-nav-icon" style="background: linear-gradient(135deg, #8E24AA, #6A1B9A);"><img style="width: 28px;" src="https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/lending/1/0/0/lending.svg" class="chip-icon" alt=""></span>
                        <span>{{ __('Lending') }}</span>
                    </a>
                    <a href="#" class="qbo-nav-chip">
                        <span class="qbo-nav-icon" style="background: linear-gradient(135deg, #8E24AA, #6A1B9A);"><img style="width: 28px;" src="https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/payroll/1/0/0/payroll.svg" class="chip-icon" alt=""></span>
                        <span>{{ __('Payroll') }}</span>
                        <!-- premium svg -->
                        <span class="qbo-nav-premium"><svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false" aria-hidden="true"><path d="m21.782 9.375-4-5A1 1 0 0 0 17 4H7a1 1 0 0 0-.781.375l-4 5a1 1 0 0 0 .074 1.332l9 9a1 1 0 0 0 1.414 0l9-9a1 1 0 0 0 .075-1.332ZM18.92 9h-3.2l-1-3h1.8l2.4 3ZM8.28 11l1.433 4.3L5.414 11H8.28Zm5.333 0L12 15.839 10.387 11h3.226Zm-3.225-2 1-3h1.22l1 3h-3.22Zm5.333 2h2.865l-4.3 4.3 1.435-4.3Zm-8.24-5h1.8l-1 3h-3.2l2.4-3Z" fill="currentColor"></path></svg></span>
                    </a>
                    <a href="#" class="qbo-nav-chip">
                        <span class="qbo-nav-icon" style="background: linear-gradient(135deg, #8E24AA, #6A1B9A);"><img style="width: 28px;" src="https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/marketing/1/0/0/marketing.svg" class="chip-icon" alt=""></span>
                        <span>{{ __('Marketing') }}</span>
                        <!-- premium svg -->
                        <span class="qbo-nav-premium"><svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false" aria-hidden="true"><path d="m21.782 9.375-4-5A1 1 0 0 0 17 4H7a1 1 0 0 0-.781.375l-4 5a1 1 0 0 0 .074 1.332l9 9a1 1 0 0 0 1.414 0l9-9a1 1 0 0 0 .075-1.332ZM18.92 9h-3.2l-1-3h1.8l2.4 3ZM8.28 11l1.433 4.3L5.414 11H8.28Zm5.333 0L12 15.839 10.387 11h3.226Zm-3.225-2 1-3h1.22l1 3h-3.22Zm5.333 2h2.865l-4.3 4.3 1.435-4.3Zm-8.24-5h1.8l-1 3h-3.2l2.4-3Z" fill="currentColor"></path></svg></span>
                    </a>
                </div>
                <button class="qbo-nav-scroll-btn">
                    <i class="ti ti-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- Scrollable Content Area --}}
    <div class="qbo-scrollable-content">
        <div class="container-fluid">
            {{-- Business Feed Section --}}
            <div class="qbo-business-feed">
                <div class="qbo-feed-header">
                    <div class="qbo-feed-title">
                        <i class="ti ti-sparkles"></i>
                        <span>{{ __('Business Feed') }}</span>
                    </div>
                    <div class="qbo-feed-pagination">
                        <button class="qbo-feed-nav-btn" disabled><i class="ti ti-chevron-left"></i></button>
                        <span class="qbo-feed-page">1 of 2</span>
                        <button class="qbo-feed-nav-btn"><i class="ti ti-chevron-right"></i></button>
                        <a href="#" class="qbo-feed-view-all">{{ __('View all') }}</a>
                    </div>
                </div>
                <div class="qbo-feed-cards">
                    <div class="qbo-feed-card">
                        <div class="qbo-feed-card-header">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" color="currentColor" width="24px" height="24px" focusable="false" aria-hidden="true" class="IglFFVfJjdri6o0yYcmONA=="><path fill="currentColor" d="M18.007 21.99H18l-12-.017a3 3 0 0 1-2.995-3l.022-14a3 3 0 0 1 3-3l12 .018a3 3 0 0 1 3 3l-.022 14a3 3 0 0 1-3 3zm0-2a1 1 0 0 0 1-1l.022-14a1 1 0 0 0-1-1l-12-.017a1 1 0 0 0-1 1l-.022 14a1 1 0 0 0 1 1z"></path><path fill="currentColor" d="M10.027 6.979h-3a1 1 0 0 1 0-2h3a1 1 0 0 1 0 2M17.009 18.99h-3a1 1 0 1 1 0-2h3a1 1 0 1 1 0 2M16.025 7.988l-8-.012a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2l5 .007h3a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-1.995m-8 1.988 4 .006v3l-4-.006zm8 3.012h-2v-3h2z"></path></svg>
                            <span>{{ __('Overdue invoices') }}</span>
                            <button class="qbo-card-menu"><i class="ti ti-dots-vertical"></i></button>
                        </div>
                        <p class="qbo-feed-card-text">Over $1,525.50 worth of invoice reminders are ready for you to revie...</p>
                        <a href="#" class="qbo-feed-card-link">{{ __('Review all') }}</a>
                    </div>
                    <div class="qbo-feed-card">
                        <div class="qbo-feed-card-header">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" color="currentColor" width="24px" height="24px" focusable="false" aria-hidden="true" class="IglFFVfJjdri6o0yYcmONA=="><path fill="currentColor" d="M20.988 8.939a1 1 0 0 0-.054-.265.973.973 0 0 0-.224-.374v-.005l-6-6a1 1 0 0 0-.283-.191c-.031-.014-.064-.022-.1-.034a1 1 0 0 0-.259-.052C14.042 2.011 14.023 2 14 2H6a3 3 0 0 0-3 3v14a3 3 0 0 0 3 3h12a3 3 0 0 0 3-3V9c0-.022-.011-.04-.012-.061M15 5.414 17.586 8H16a1 1 0 0 1-1-1zM18 20H6a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h7v3a3 3 0 0 0 3 3h3v9a1 1 0 0 1-1 1"></path><path fill="currentColor" d="M7 10h3a1 1 0 1 0 0-2H7a1 1 0 0 0 0 2M14 13H7a1 1 0 0 0 0 2h7a1 1 0 0 0 0-2M14 16H7a1 1 0 0 0 0 2h7a1 1 0 0 0 0-2"></path></svg>
                            <span>{{ __('Profit and loss') }}</span>
                            <button class="qbo-card-menu"><i class="ti ti-dots-vertical"></i></button>
                        </div>
                        <p class="qbo-feed-card-text">Your net profit for November was -$700.</p>
                        <a href="#" class="qbo-feed-card-link">{{ __('View full report') }}</a>
                    </div>
                    <div class="qbo-feed-card qbo-feed-card-highlight">
                        <div class="qbo-feed-card-header">
                            <button class="qbo-card-menu"><i class="ti ti-dots-vertical"></i></button>
                        </div>
                        <p class="qbo-feed-card-text">{{ __('Autofilling expenses keeps your records organized') }}</p>
                        <a href="#" class="qbo-feed-card-link">{{ __('Learn more') }}</a>
                    </div>
                    <div class="qbo-feed-card">
                        <div class="qbo-feed-card-header">
                            <i class="ti ti-file-check"></i>
                            <span>{{ __('Invoices paid') }}</span>
                            <button class="qbo-card-menu"><i class="ti ti-dots-vertical"></i></button>
                        </div>
                        <p class="qbo-feed-card-text">$2,235.55 in payments were made this week</p>
                        <a href="#" class="qbo-feed-card-link">{{ __('Go to payments') }}</a>
                    </div>
                </div>
            </div>

            {{-- Create Actions Section --}}
            <div class="qbo-create-actions">
                <span class="qbo-actions-title">{{ __('Create actions') }}</span>
                <a href="#" class="qbo-create-action-btn">{{ __('Get paid online') }}</a>
                <a href="#" class="qbo-create-action-btn">{{ __('Create invoice') }}</a>
                <a href="#" class="qbo-create-action-btn">{{ __('Record expense') }}</a>
                <a href="#" class="qbo-create-action-btn">{{ __('Add bank deposit') }}</a>
                <a href="#" class="qbo-create-action-btn">{{ __('Create check') }}</a>
                <a href="#" class="qbo-action-show-all">{{ __('Show all') }}</a>
            </div>

            {{-- Business at a Glance Section --}}
            <div class="qbo-glance-section">
                <div class="qbo-glance-header">
                    <h5 class="qbo-glance-title">{{ __('Business at a glance') }}</h5>
                    <div class="qbo-glance-actions">
                        <button type="button" class="btn btn-sm qbo-customize-btn" id="btn-customize-layout">
                            <i class="ti ti-adjustments-horizontal"></i>
                        </button>
                        <button type="button" class="btn btn-sm qbo-customize-btn">
                            <i class="ti ti-eye-off"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Dashboard Widgets Container --}}
            <div class="qbo-dashboard">
                <div class="row">
                    <div class="col-12">
                        {{-- Gridstack Dashboard --}}
                        <div class="grid-stack" id="dashboard-grid">
                        @foreach($widgets as $widget)
                            @if(isset($widgetDefs[$widget->key]))
                                <div class="grid-stack-item" 
                                     gs-id="{{ $widget->id }}"
                                     data-widget-key="{{ $widget->key }}"
                                     gs-x="{{ $widget->x }}" 
                                     gs-y="{{ $widget->y }}" 
                                     gs-w="{{ $widget->w }}" 
                                     gs-h="{{ $widget->h }}"
                                     gs-min-w="2"
                                     gs-min-h="1">
                                    <div class="grid-stack-item-content">
                                        {{-- QBO Edit Overlay (visible in edit mode) --}}
                                        <div class="qbo-edit-overlay">
                                            {{-- Resize Handles - dots on all 4 sides --}}
                                            <div class="qbo-resize-handle qbo-resize-top"></div>
                                            <div class="qbo-resize-handle qbo-resize-right"></div>
                                            <div class="qbo-resize-handle qbo-resize-bottom"></div>
                                            <div class="qbo-resize-handle qbo-resize-left"></div>
                                            
                                            {{-- Move Container with 4 arrows --}}
                                            <div class="qbo-move-container">
                                                {{-- Arrow Up --}}
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="24px" height="24px" class="qbo-arrow-up">
                                                    <path fill="currentColor" d="m19.8 11.394-7.06-7.082a1 1 0 0 0-1.414 0L4.241 11.37a1 1 0 0 0 1.412 1.416l5.372-5.356-.018 11.586a1 1 0 1 0 2 0l.018-11.587 5.356 5.373a1 1 0 0 0 1.419-1.41"></path>
                                                </svg>
                                                {{-- Arrow Right --}}
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="24px" height="24px" class="qbo-arrow-right">
                                                    <path fill="currentColor" d="m19.726 11.287-7.061-7.082a1 1 0 1 0-1.416 1.412l5.356 5.372-11.585-.017a1 1 0 1 0 0 2l11.586.017-5.376 5.356a1 1 0 1 0 1.412 1.416l7.082-7.06a1 1 0 0 0 0-1.414z"></path>
                                                </svg>
                                                {{-- Arrow Down --}}
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="24px" height="24px" class="qbo-arrow-down">
                                                    <path fill="currentColor" d="M19.761 11.251a1 1 0 0 0-1.414 0l-5.371 5.355.016-11.586a1 1 0 0 0-2 0l-.016 11.58-5.357-5.37A1.001 1.001 0 0 0 4.2 12.643l7.061 7.081a1 1 0 0 0 1.413.002l7.081-7.06a1 1 0 0 0 .006-1.415"></path>
                                                </svg>
                                                {{-- Arrow Left --}}
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="24px" height="24px" class="qbo-arrow-left">
                                                    <path fill="currentColor" d="M19.02 11.028 7.435 11.01l5.371-5.355a1 1 0 0 0-1.412-1.416L4.312 11.3a1 1 0 0 0 0 1.414L11.37 19.8a1 1 0 1 0 1.416-1.412L7.429 13.01l11.587.018a1 1 0 1 0 0-2z"></path>
                                                </svg>
                                                <div class="qbo-move-tooltip">{{ __('To move a tile, select and drag it to a new spot.') }}</div>
                                            </div>
                                            
                                            {{-- Delete Button --}}
                                            <div class="qbo-delete-container" data-widget-id="{{ $widget->id }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="20px" height="24px">
                                                    <path fill="currentColor" d="m21.011 5.013-5-.007v-2a2 2 0 0 0-2-2l-4-.006a2 2 0 0 0-2 2v2h-5a1 1 0 0 0 0 2h1l-.019 13a3 3 0 0 0 2.995 3l10 .016a3 3 0 0 0 3-3l.019-13h1a1 1 0 1 0 0-2zM10.013 3l4 .006v2h-4zm7.975 17.012a1 1 0 0 1-1 1l-10-.016a1 1 0 0 1-1-1l.019-13 6 .009h6z"></path>
                                                    <path fill="currentColor" d="M15 9.005a1 1 0 0 0-1 1l-.012 8a1 1 0 0 0 2 0l.012-8a1 1 0 0 0-1-1"></path>
                                                </svg>
                                                <div class="qbo-delete-tooltip">{{ __('Hide widget') }}</div>
                                            </div>
                                        </div>
                                        
                                        {{-- Widget Content --}}
                                        <div class="qbo-widget-card">
                                            <div class="qbo-widget-header">
                                                <span class="qbo-widget-title">{{ $widgetDefs[$widget->key]['name'] }}</span>
                                            </div>
                                            <div class="qbo-widget-body">
                                                @include($widgetDefs[$widget->key]['view'])
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach

                        {{-- Add Widgets Placeholder Card - positioned with other widgets --}}
                        @php
                            // Calculate position for Add Widget card (next available position)
                            $maxY = $widgets->max('y') ?? 0;
                            $addWidgetY = $maxY + 1;
                        @endphp
                        <div class="grid-stack-item qbo-add-widget-card" 
                             gs-x="0" 
                             gs-y="{{ $addWidgetY }}" 
                             gs-w="3" 
                             gs-h="1"
                             gs-min-w="2"
                             gs-min-h="1"
                             gs-id="add-widget">
                            <div class="grid-stack-item-content">
                                {{-- Edit overlay for Add Widget (no delete button) --}}
                                <div class="qbo-edit-overlay qbo-no-delete">
                                    <div class="qbo-resize-handle qbo-resize-top"></div>
                                    <div class="qbo-resize-handle qbo-resize-right"></div>
                                    <div class="qbo-resize-handle qbo-resize-bottom"></div>
                                    <div class="qbo-resize-handle qbo-resize-left"></div>
                                    
                                    <div class="qbo-move-container">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="24px" height="24px" class="qbo-arrow-up">
                                            <path fill="currentColor" d="m19.8 11.394-7.06-7.082a1 1 0 0 0-1.414 0L4.241 11.37a1 1 0 0 0 1.412 1.416l5.372-5.356-.018 11.586a1 1 0 1 0 2 0l.018-11.587 5.356 5.373a1 1 0 0 0 1.419-1.41"></path>
                                        </svg>
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="24px" height="24px" class="qbo-arrow-right">
                                            <path fill="currentColor" d="m19.726 11.287-7.061-7.082a1 1 0 1 0-1.416 1.412l5.356 5.372-11.585-.017a1 1 0 1 0 0 2l11.586.017-5.376 5.356a1 1 0 1 0 1.412 1.416l7.082-7.06a1 1 0 0 0 0-1.414z"></path>
                                        </svg>
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="24px" height="24px" class="qbo-arrow-down">
                                            <path fill="currentColor" d="M19.761 11.251a1 1 0 0 0-1.414 0l-5.371 5.355.016-11.586a1 1 0 0 0-2 0l-.016 11.58-5.357-5.37A1.001 1.001 0 0 0 4.2 12.643l7.061 7.081a1 1 0 0 0 1.413.002l7.081-7.06a1 1 0 0 0 .006-1.415"></path>
                                        </svg>
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="24px" height="24px" class="qbo-arrow-left">
                                            <path fill="currentColor" d="M19.02 11.028 7.435 11.01l5.371-5.355a1 1 0 0 0-1.412-1.416L4.312 11.3a1 1 0 0 0 0 1.414L11.37 19.8a1 1 0 1 0 1.416-1.412L7.429 13.01l11.587.018a1 1 0 1 0 0-2z"></path>
                                        </svg>
                                        <div class="qbo-move-tooltip">{{ __('To move a tile, select and drag it to a new spot.') }}</div>
                                    </div>
                                </div>
                                
                                {{-- Add Widget Content --}}
                                <div class="qbo-add-widget-content" data-bs-toggle="modal" data-bs-target="#addWidgetsModal">
                                    <div class="qbo-add-icon">
                                        <i class="ti ti-plus"></i>
                                    </div>
                                    <div class="qbo-add-title">{{ __('Add widgets') }}</div>
                                    <hr>
                                    <div class="qbo-suggestions-section">
                                        <div class="qbo-suggestions-title">
                                            <i class="ti ti-wand"></i>
                                            {{ __('Smart suggestions') }}
                                        </div>
                                        <div class="qbo-suggestions-icon">
                                            <i class="ti ti-coffee"></i>
                                        </div>
                                        <p class="qbo-suggestions-text">{{ __('Nothing new here yet. Check back later for new suggestions.') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Add Widgets Modal (Right Drawer) --}}
        <div class="modal fade" id="addWidgetsModal" tabindex="-1" aria-labelledby="addWidgetsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-right">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addWidgetsModalLabel">{{ __('Add widgets') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-4">{{ __('Select widgets for your dashboard') }}</p>
                        <h6 class="text-muted mb-3">{{ __('All widgets') }}</h6>
                        
                        <div class="qbo-widget-list">
                            @foreach(config('dashboard.widgets') as $key => $def)
                                @php
                                    $isEnabled = $widgets->where('key', $key)->where('enabled', true)->count() > 0;
                                @endphp
                                <div class="qbo-widget-list-item">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input widget-toggle" 
                                               type="checkbox" 
                                               id="widget-{{ $key }}"
                                               data-widget-key="{{ $key }}"
                                               {{ $isEnabled ? 'checked' : '' }}>
                                        <label class="form-check-label" for="widget-{{ $key }}">
                                            {{ $def['name'] }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="button" class="btn btn-primary" id="saveWidgetSelection">{{ __('Save') }}</button>
                    </div>
                </div>
            </div>
        </div>
        </div>{{-- End qbo-dashboard --}}
        </div>{{-- End col-12 --}}
        </div>{{-- End row --}}
        </div>{{-- End container-fluid --}}
    </div>{{-- End qbo-scrollable-content --}}
    @endif
@endsection

@push('script-page')
    {{-- Gridstack JS --}}
    <script src="https://cdn.jsdelivr.net/npm/gridstack@10.3.1/dist/gridstack-all.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Navigation scroll button functionality
        const navContainer = document.querySelector('.qbo-nav-container');
        const scrollBtn = document.querySelector('.qbo-nav-scroll-btn');
        
        if (scrollBtn && navContainer) {
            scrollBtn.addEventListener('click', function() {
                // Scroll 200px to the right
                navContainer.scrollBy({
                    left: 200,
                    behavior: 'smooth'
                });
            });
            
            // Update button visibility based on scroll position
            function updateScrollButton() {
                const maxScroll = navContainer.scrollWidth - navContainer.clientWidth;
                if (navContainer.scrollLeft >= maxScroll - 10) {
                    // At the end, scroll back to start
                    scrollBtn.querySelector('i').className = 'ti ti-chevron-left';
                    scrollBtn.onclick = function() {
                        navContainer.scrollTo({ left: 0, behavior: 'smooth' });
                    };
                } else {
                    scrollBtn.querySelector('i').className = 'ti ti-chevron-right';
                    scrollBtn.onclick = function() {
                        navContainer.scrollBy({ left: 200, behavior: 'smooth' });
                    };
                }
            }
            
            navContainer.addEventListener('scroll', updateScrollButton);
        }
        
        // Initialize Gridstack with 12 columns (Bootstrap grid system)
        let grid = GridStack.init({
            column: 12,          // 12 columns like Bootstrap grid
            cellHeight: 280,     // Height per row in pixels (smaller for laptops)
            margin: 10,          // Gap between widgets in pixels
            float: false,        // Prevent widgets from overlapping
            disableDrag: true,   // Disabled by default, enabled in edit mode
            disableResize: true, // Disabled by default, enabled in edit mode
            animate: true,
            resizable: {
                handles: 'n,e,s,w' // Only edge handles like QBO (not corners)
            }
        });

        let isEditing = false;
        const customizeBtn = document.getElementById('btn-customize-layout');
        const gridContainer = document.getElementById('dashboard-grid');

        // Auto-save on any change (resize or move)
        grid.on('change', function(event, items) {
            if (isEditing && items && items.length > 0) {
                saveLayout();
            }
        });

        // Toggle edit mode
        customizeBtn.addEventListener('click', function() {
            isEditing = !isEditing;
            
            if (isEditing) {
                grid.enableMove(true);
                grid.enableResize(true);
                gridContainer.classList.add('editing');
                customizeBtn.classList.add('active');
                customizeBtn.querySelector('span').textContent = '{{ __("Save Layout") }}';
                customizeBtn.querySelector('i').className = 'ti ti-device-floppy';
            } else {
                // Save and exit edit mode
                saveLayout();
                grid.enableMove(false);
                grid.enableResize(false);
                gridContainer.classList.remove('editing');
                customizeBtn.classList.remove('active');
                customizeBtn.querySelector('span').textContent = '{{ __("Customize Layout") }}';
                customizeBtn.querySelector('i').className = 'ti ti-layout';
            }
        });

        // Delete widget button handler
        document.querySelectorAll('.qbo-delete-container').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const widgetId = this.getAttribute('data-widget-id');
                const item = this.closest('.grid-stack-item');
                
                if (confirm('{{ __("Remove this widget from dashboard?") }}')) {
                    // Remove from grid
                    grid.removeWidget(item);
                    
                    // Update server
                    fetch('{{ route("dashboard.widget.toggle") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ widget_id: widgetId, enabled: false })
                    });
                }
            });
        });

        // Save layout function
        function saveLayout() {
            const items = grid.getGridItems();
            const widgetsData = [];
            
            items.forEach(function(el) {
                const node = el.gridstackNode;
                const widgetId = el.getAttribute('gs-id');
                if (node && widgetId !== 'add-widget') {
                    widgetsData.push({
                        id: parseInt(widgetId),
                        x: node.x,
                        y: node.y,
                        w: node.w,
                        h: node.h
                    });
                }
            });

            if (widgetsData.length > 0) {
                fetch('{{ route("dashboard.account.layout") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ widgets: widgetsData })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('success', '{{ __("Layout saved successfully") }}');
                    }
                })
                .catch(error => {
                    console.error('Error saving layout:', error);
                    showToast('danger', '{{ __("Error saving layout") }}');
                });
            }
        }

        // Widget modal save handler
        document.getElementById('saveWidgetSelection')?.addEventListener('click', function() {
            const toggles = document.querySelectorAll('.widget-toggle');
            const selections = [];
            
            toggles.forEach(toggle => {
                selections.push({
                    key: toggle.getAttribute('data-widget-key'),
                    enabled: toggle.checked
                });
            });

            fetch('{{ route("dashboard.widgets.update") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ widgets: selections })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                }
            });
        });

        // Toast notification helper
        function showToast(type, message) {
            const toast = document.getElementById('liveToast');
            if (toast) {
                toast.classList.remove('bg-success', 'bg-danger', 'bg-warning');
                toast.classList.add('bg-' + type);
                toast.querySelector('.toast-body').textContent = message;
                const bsToast = new bootstrap.Toast(toast);
                bsToast.show();
            }
        }
    });
    </script>

    {{-- Initialize ApexCharts for widgets --}}
    <script>
    // Expenses donut chart
    (function() {
        const chartEl = document.querySelector('.qbo-donut-chart');
        if (chartEl && typeof ApexCharts !== 'undefined') {
            const options = {
                series: [35, 25, 20, 20],
                chart: {
                    type: 'donut',
                    height: 140
                },
                labels: ['Maintenance', 'Cost of Goods', 'Legal & Prof.', 'Other'],
                colors: ['#2ca01c', '#17a2b8', '#ffc107', '#6c757d'],
                plotOptions: {
                    pie: {
                        donut: {
                            size: '65%'
                        }
                    }
                },
                dataLabels: { enabled: false },
                legend: { show: false },
                stroke: { width: 0 }
            };
            new ApexCharts(chartEl, options).render();
        }
    })();
    </script>
@endpush
