{{-- 
    All Apps Submenu Sidebar - QBO Style Left Panel
    Include this partial in pages that need the MY APPS sidebar navigation
    
    Usage: @include('partials.admin.allApps-subMenu-Sidebar', [
        'activeSection' => 'accounting',  // Which parent is expanded
        'activeItem' => 'bank_transactions'  // Which item is active
    ])
--}}

@php
    $activeSection = $activeSection ?? 'accounting';
    $activeItem = $activeItem ?? '';
    
    // Define menu structure matching QBO
    $appMenus = [
        'accounting' => [
            'label' => 'Accounting',
            'icon' => 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/accounting/1/0/0/accounting.svg',
            'items' => [
                ['key' => 'bank_transactions', 'label' => 'Bank transactions', 'route' => 'transaction.bankTransactions'],
                ['key' => 'integration_transactions', 'label' => 'Integration transactions', 'route' => ''],
                ['key' => 'receipts', 'label' => 'Receipts', 'route' => 'reciept.index'],
                ['key' => 'reconcile', 'label' => 'Reconcile', 'route' => ''],
                ['key' => 'rules', 'label' => 'Rules', 'route' => ''],
                ['key' => 'chart_of_accounts', 'label' => 'Chart of accounts', 'route' => 'chart-of-account.index'],
                ['key' => 'recurring_transactions', 'label' => 'Recurring transactions', 'route' => ''],
                ['key' => 'my_accountant', 'label' => 'My accountant', 'route' => ''],
            ]
        ],
        'expenses' => [
            'label' => 'Expenses & Bills',
            'icon' => 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/expenses/1/0/0/expenses.svg',
            'items' => [
                ['key' => 'expense_transactions', 'label' => 'Expense transactions', 'route' => 'expense.index'],
                ['key' => 'vendors', 'label' => 'Vendors', 'route' => 'vender.index'],
                ['key' => 'bills', 'label' => 'Bills', 'route' => 'bill.index'],
                ['key' => 'bill_payments', 'label' => 'Bill payments', 'route' => ''],
                ['key' => 'mileage', 'label' => 'Mileage', 'route' => ''],
                ['key' => 'contractors', 'label' => 'Contractors', 'route' => ''],
            ]
        ],
        'sales' => [
            'label' => 'Sales & Get Paid',
            'icon' => 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/sales-payments/1/0/0/sales-payments.svg',
            'items' => [
                ['key' => 'overview', 'label' => 'Overview', 'route' => ''],
                ['key' => 'sales_transactions', 'label' => 'Sales transactions', 'route' => ''],
                ['key' => 'invoices', 'label' => 'Invoices', 'route' => 'invoice.index'],
                ['key' => 'recurring_payments', 'label' => 'Recurring payments', 'route' => ''],
                ['key' => 'sales_receipts', 'label' => 'Sales receipts', 'route' => 'sales-receipt.index'],
                ['key' => 'products_services', 'label' => 'Products & services', 'route' => 'productservice.index'],
            ]
        ],
        'customers' => [
            'label' => 'Customer Hub',
            'icon' => 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/customers/1/0/0/customers.svg',
            'items' => [
                ['key' => 'customers_overview', 'label' => 'Overview', 'route' => ''],
                ['key' => 'customers', 'label' => 'Customers', 'route' => 'customer.index'],
                ['key' => 'estimates', 'label' => 'Estimates', 'route' => 'proposal.index'],
            ]
        ],
        'team' => [
            'label' => 'Team',
            'icon' => 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/team/1/0/0/team.svg',
            'items' => [
                ['key' => 'employees', 'label' => 'Employees', 'route' => 'employee.index'],
                ['key' => 'contractors_team', 'label' => 'Contractors', 'route' => ''],
            ]
        ],
        'time' => [
            'label' => 'Time',
            'icon' => 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/time/1/0/0/time.svg',
            'items' => [
                ['key' => 'time_entries', 'label' => 'Time entries', 'route' => ''],
                ['key' => 'schedule', 'label' => 'Schedule', 'route' => ''],
            ]
        ],
        'inventory' => [
            'label' => 'Inventory',
            'icon' => 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/inventory/1/0/0/inventory.svg',
            'items' => [
                ['key' => 'inventory_overview', 'label' => 'Overview', 'route' => ''],
                ['key' => 'inventory', 'label' => 'Inventory', 'route' => ''],
                ['key' => 'purchase_orders', 'label' => 'Purchase orders', 'route' => 'purchase.index'],
            ]
        ],
        'sales_tax' => [
            'label' => 'Sales Tax',
            'icon' => 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/sales-tax/1/0/0/sales-tax.svg',
            'items' => [
                ['key' => 'sales_tax_overview', 'label' => 'Overview', 'route' => 'taxes.index'],
            ]
        ],
        'business_tax' => [
            'label' => 'Business Tax',
            'icon' => 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/business-tax/1/0/0/business-tax.svg',
            'items' => [
                ['key' => 'business_tax_overview', 'label' => 'Overview', 'route' => ''],
                ['key' => 'tax_summary', 'label' => 'Tax summary', 'route' => ''],
            ]
        ],
        'lending' => [
            'label' => 'Lending',
            'icon' => 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/lending/1/0/0/lending.svg',
            'items' => [
                ['key' => 'lending_overview', 'label' => 'Overview', 'route' => ''],
            ]
        ],
    ];
    
    $premiumItems = [
        ['key' => 'payroll', 'label' => 'Payroll', 'icon' => 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/payroll/1/0/0/payroll.svg', 'route' => ''],
        ['key' => 'marketing', 'label' => 'Marketing', 'icon' => 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/marketing/1/0/0/marketing.svg', 'route' => ''],
    ];
@endphp

<nav class="left-panel" id="myAppsLeftPanel" aria-labelledby="My apps">
    <section class="left-panel-section--container">
        {{-- Header --}}
        <div class="left-panel-section--header-container">
            <h2 class="left-panel-section--header-text">My apps</h2>
            <div class="my-menu-header-buttons">
                <button class="headerItemButton hamburger-button" id="myAppsCollapseBtn" aria-label="Toggle Navigation" type="button">
                    <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="#6b6c72" focusable="false" aria-hidden="true">
                        <path d="m14.011 8.006-10-.012a1 1 0 0 1 0-2l10 .012a1 1 0 1 1 0 2ZM14 13.006l-10-.012a1 1 0 1 1 0-2l10 .012a1 1 0 0 1 0 2ZM14 18.006l-10-.012a1 1 0 1 1 0-2l10 .012a1 1 0 0 1 0 2ZM20.985 10a1 1 0 0 0-1.71-.7l-1.99 2.009a1 1 0 0 0 .006 1.414l2.009 1.99A1 1 0 0 0 21 14l-.015-4Z" fill="currentColor"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        {{-- Accordion Sections --}}
        <div class="left-panel-section-body">
            @foreach($appMenus as $sectionKey => $section)
                <div class="left-panel-accordion">
                    <section class="accordion-item {{ $activeSection === $sectionKey ? 'is-expanded' : '' }}" data-section="{{ $sectionKey }}">
                        {{-- Accordion Header --}}
                        <div class="accordion-item-header" role="heading" aria-level="3">
                            <button type="button" 
                                    class="left-panel--app-item accordion-header-button" 
                                    aria-expanded="{{ $activeSection === $sectionKey ? 'true' : 'false' }}">
                                <div class="left-panel-item-container">
                                    <img src="{{ $section['icon'] }}" class="left-icon-container" alt="{{ $section['label'] }}">
                                    <span class="left-panel-item-text">{{ $section['label'] }}</span>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" color="currentColor" width="16px" height="16px" focusable="false" aria-hidden="true" class="accordion-chevron">
                                    <path fill="currentColor" d="M12.014 16.018a1 1 0 0 1-.708-.294L5.314 9.715A1.001 1.001 0 0 1 6.73 8.3l5.286 5.3 5.3-5.285a1 1 0 0 1 1.413 1.416l-6.009 5.995a1 1 0 0 1-.706.292"></path>
                                </svg>
                            </button>
                        </div>
                        
                        {{-- Accordion Body --}}
                        <section class="accordion-item-body" role="region">
                            <div class="accordion-body-content">
                                @foreach($section['items'] as $item)
                                    @php
                                        $itemUrl = $item['route'] && Route::has($item['route']) ? route($item['route']) : '#';
                                        $isActive = $activeItem === $item['key'];
                                    @endphp
                                    <div class="left-panel-nav-item {{ $isActive ? 'left-panel-nav-item--active' : '' }}">
                                        <a href="{{ $itemUrl }}" aria-label="{{ $item['label'] }}" class="nav-item-anchor" data-key="{{ $item['key'] }}">
                                            <span class="nav-item-label">
                                                <span class="nav-item-label-text">
                                                    <div class="app-nav-item--container">
                                                        <span>{{ $item['label'] }}</span>
                                                    </div>
                                                </span>
                                            </span>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    </section>
                </div>
            @endforeach
        </div>
    </section>
    
    {{-- Premium Section --}}
    <section class="left-panel-section--container top-divider">
        <div class="left-panel-section-body">
            @foreach($premiumItems as $item)
                <div class="left-panel-nav-item left-panel-root">
                    <a href="#" aria-label="{{ $item['label'] }}" class="nav-item-anchor">
                        <span class="nav-item-label">
                            <span class="nav-item-label-text">
                                <div class="app-nav-item--container">
                                    <img src="{{ $item['icon'] }}" class="app-nav-item--img" alt="">
                                    <span>{{ $item['label'] }}</span>
                                </div>
                            </span>
                        </span>
                    </a>
                    <span class="right-icon">
                        <svg width="16px" height="16px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="currentColor" focusable="false" aria-hidden="true" class="left-panel-disc-icon">
                            <path d="m21.782 9.375-4-5A1 1 0 0 0 17 4H7a1 1 0 0 0-.781.375l-4 5a1 1 0 0 0 .074 1.332l9 9a1 1 0 0 0 1.414 0l9-9a1 1 0 0 0 .075-1.332ZM18.92 9h-3.2l-1-3h1.8l2.4 3ZM8.28 11l1.433 4.3L5.414 11H8.28Zm5.333 0L12 15.839 10.387 11h3.226Zm-3.225-2 1-3h1.22l1 3h-3.22Zm5.333 2h2.865l-4.3 4.3 1.435-4.3Zm-8.24-5h1.8l-1 3h-3.2l2.4-3Z" fill="currentColor"></path>
                        </svg>
                    </span>
                </div>
            @endforeach
        </div>
    </section>
</nav>

{{-- Expand button - visible when sidebar is collapsed --}}
<button class="left-panel-expand-btn" id="myAppsExpandBtn" aria-label="Expand Navigation" type="button">
    <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="#6b6c72" focusable="false" aria-hidden="true">
        <path d="m14.011 8.006-10-.012a1 1 0 0 1 0-2l10 .012a1 1 0 1 1 0 2ZM14 13.006l-10-.012a1 1 0 1 1 0-2l10 .012a1 1 0 0 1 0 2ZM14 18.006l-10-.012a1 1 0 1 1 0-2l10 .012a1 1 0 0 1 0 2ZM20.985 10a1 1 0 0 0-1.71-.7l-1.99 2.009a1 1 0 0 0 .006 1.414l2.009 1.99A1 1 0 0 0 21 14l-.015-4Z" fill="currentColor"></path>
    </svg>
</button>

@push('css-page')
<style>
/* =====================================================
   QBO Left Panel - My Apps Sidebar (FIXED POSITIONING)
   ===================================================== */
.left-panel {
    position: fixed;
    left: var(--qbo-sidebar-width, 72px); /* Updated fallback to match qbo-menu.css */
    top: 48px;
    bottom: 0;
    width: 220px;
    background: #fff;
    border-right: 1px solid #e0e3e5;
    overflow-y: auto;
    overflow-x: hidden;
    display: flex;
    flex-direction: column;
    scrollbar-width: none;
    -ms-overflow-style: none;
    z-index: 1030;
    /* DEBUG: bright background to see if element renders */
    /* background: red !important; */
}

.left-panel::-webkit-scrollbar {
    display: none;
}

.left-panel.is-collapsed {
    transform: translateX(-100%);
    opacity: 0;
    pointer-events: none;
}

/* Expand button - fixed position, visible only when sidebar is collapsed */
.left-panel-expand-btn {
    position: fixed;
    left: var(--qbo-sidebar-width, 72px);
    top: 56px;
    width: 36px;
    height: 36px;
    background: #fff;
    border: 1px solid #e0e3e5;
    border-left: none;
    border-radius: 0 4px 4px 0;
    cursor: pointer;
    display: none; /* Hidden by default */
    align-items: center;
    justify-content: center;
    z-index: 1031;
    box-shadow: 2px 2px 4px rgba(0,0,0,0.1);
    transition: background 0.15s ease;
}

.left-panel-expand-btn:hover {
    background: #f4f5f7;
}

/* Show expand button when sidebar has is-collapsed class */
body:has(.left-panel.is-collapsed) .left-panel-expand-btn {
    display: flex;
}

.left-panel-section--container {
    padding: 0;
}

.left-panel-section--container.top-divider {
    border-top: 1px solid #e0e3e5;
    margin-top: 8px;
    padding-top: 8px;
}

.left-panel-section--header-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px 8px;
}

.left-panel-section--header-text {
    font-size: 11px;
    font-weight: 600;
    color: #6b6c72;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    margin: 0;
}

.headerItemButton {
    background: none;
    border: none;
    padding: 4px;
    cursor: pointer;
    color: #6b6c72;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: background 0.15s ease;
}

.headerItemButton:hover {
    background: #f4f5f7;
    color: #393a3d;
}

.left-panel-section-body {
    padding: 0;
}

/* Accordion styles */
.left-panel-accordion {
    margin-bottom: 2px;
}

.accordion-item {
    border: none;
}

.accordion-item-header {
    margin: 0;
}

.left-panel--app-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 8px 16px;
    background: none;
    border: none;
    cursor: pointer;
    text-align: left;
    transition: background 0.15s ease;
}

.left-panel--app-item:hover {
    background: #f4f5f7;
}

.left-panel-item-container {
    display: flex;
    align-items: center;
    gap: 12px;
}

.left-icon-container {
    width: 20px;
    height: 20px;
    object-fit: contain;
}

.left-panel-item-text {
    font-size: 14px;
    font-weight: 500;
    color: #393a3d;
}

.accordion-chevron {
    color: #6b6c72;
    transition: transform 0.2s ease;
    flex-shrink: 0;
}

.accordion-item.is-expanded .accordion-chevron {
    transform: rotate(180deg);
}

/* Accordion body */
.accordion-item-body {
    display: none;
    padding: 0;
}

.accordion-item.is-expanded .accordion-item-body {
    display: block;
}

.accordion-body-content {
    padding: 0;
}

/* Nav items */
.left-panel-nav-item {
    position: relative;
}

.left-panel-nav-item .nav-item-anchor {
    display: flex;
    align-items: center;
    padding: 8px 16px 8px 48px;
    text-decoration: none;
    color: #393a3d;
    font-size: 14px;
    transition: background 0.15s ease;
}

.left-panel-nav-item .nav-item-anchor:hover {
    background: #f4f5f7;
}

.left-panel-nav-item--active {
    background: #2c3e50;
}

.left-panel-nav-item--active .nav-item-anchor {
    color: #fff;
}

.left-panel-nav-item--active .nav-item-anchor:hover {
    background: #34495e;
}

.nav-item-label {
    flex: 1;
}

.nav-item-label-text {
    display: block;
}

.app-nav-item--container {
    display: flex;
    align-items: center;
    gap: 8px;
}

.app-nav-item--img {
    width: 20px;
    height: 20px;
    object-fit: contain;
}

/* Root level items (Payroll, Marketing) */
.left-panel-root .nav-item-anchor {
    padding-left: 16px;
}

.left-panel-root {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-right: 16px;
}

.right-icon {
    display: flex;
    align-items: center;
}

.left-panel-disc-icon {
    color: #9b9b9b;
}

/* Layout wrapper for page with sidebar */
.qbo-page-with-sidebar {
    margin-left: 220px; /* Space for fixed left-panel */
    min-height: calc(100vh - 48px);
}

.qbo-page-content {
    padding: 0;
}

/* Hide the page header when using sidebar layout */
body:has(.left-panel) .page-header {
    display: none;
}

/* Adjust the main content margin when sidebar exists */
body:has(.left-panel) .qbo-main-content {
    margin-left: calc(var(--qbo-sidebar-width, 56px) + 220px) !important;
}

body:has(.left-panel.is-collapsed) .qbo-main-content {
    margin-left: var(--qbo-sidebar-width, 56px) !important;
}
</style>
@endpush

@push('script-page')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const leftPanel = document.getElementById('myAppsLeftPanel');
    const collapseBtn = document.getElementById('myAppsCollapseBtn');
    const expandBtn = document.getElementById('myAppsExpandBtn');
    
    // Debug: log if panel exists
    console.log('MY APPS Left Panel:', leftPanel);
    console.log('Left Panel classList:', leftPanel ? leftPanel.classList : 'NOT FOUND');
    
    // Collapse toggle - hide sidebar
    if (collapseBtn) {
        collapseBtn.addEventListener('click', function() {
            leftPanel.classList.add('is-collapsed');
            localStorage.setItem('myapps-panel-collapsed', 'true');
        });
    }
    
    // Expand toggle - show sidebar
    if (expandBtn) {
        expandBtn.addEventListener('click', function() {
            leftPanel.classList.remove('is-collapsed');
            localStorage.setItem('myapps-panel-collapsed', 'false');
        });
    }
    
    // TEMPORARILY DISABLED: Restore collapsed state from localStorage
    // This was causing the sidebar to auto-collapse
    // if (localStorage.getItem('myapps-panel-collapsed') === 'true') {
    //     leftPanel.classList.add('is-collapsed');
    // }
    
    // Accordion toggle
    document.querySelectorAll('.accordion-header-button').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const section = this.closest('.accordion-item');
            const isExpanded = section.classList.contains('is-expanded');
            
            // Close all sections
            document.querySelectorAll('.accordion-item').forEach(function(item) {
                item.classList.remove('is-expanded');
                item.querySelector('.accordion-header-button').setAttribute('aria-expanded', 'false');
            });
            
            // Toggle clicked section
            if (!isExpanded) {
                section.classList.add('is-expanded');
                this.setAttribute('aria-expanded', 'true');
            }
        });
    });
});
</script>
@endpush