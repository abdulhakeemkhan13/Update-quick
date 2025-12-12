@php
    $setting = \App\Models\Utility::settings();
    $company_logo = $setting['company_logo'] ?? 'logo-dark.png';
@endphp
{{-- QBO-Style Left Sidebar Menu - Exact Replica --}}
<nav class="qbo-sidebar" id="qboSidebar" aria-label="Side">
    {{-- Create & Bookmarks Section --}}
    <section class="qbo-sidebar-section">
        {{-- Create Button with Hover Flyout --}}
        <div class="qbo-create-wrapper">
            <button class="qbo-sidebar-item qbo-create-btn" id="qboCreateBtn" aria-label="Create" type="button">
                <div class="qbo-sidebar-item-icon">
                    <svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15 11h-2V9a1 1 0 0 0-2 0v2H9a1 1 0 0 0 0 2h2v2a1 1 0 0 0 2 0v-2h2a1 1 0 0 0 0-2Z" fill="currentColor"></path>
                        <path d="M12.015 2H12a10 10 0 1 0-.015 20H12a10 10 0 0 0 .015-20ZM12 20h-.012A8 8 0 0 1 12 4h.012A8 8 0 0 1 12 20Z" fill="currentColor"></path>
                    </svg>
                </div>
                <span class="qbo-sidebar-item-label">Create</span>
            </button>
            
            {{-- Create Menu Flyout (appears on hover) - QBO Multi-Column Layout --}}
            <div class="qbo-create-flyout" id="qboCreateFlyout">
                <div class="qbo-flyout-grid">
                    {{-- Customers Column --}}
                    <div class="qbo-flyout-column">
                        <h5 class="qbo-flyout-heading">Customers</h5>
                        <ul class="qbo-flyout-list">
                            <li><a href="{{ route('invoice.create', 0) }}">Invoice</a></li>
                            <li><a href="{{ route('receive-payment.create') }}">Receive payment</a></li>
                            <li><a href="#">Statement</a></li>
                            <li><a href="{{ route('proposal.create', 0) }}">Estimate</a></li>
                            <li><a href="#">Sales order</a></li>
                            <li><a href="{{ route('creditmemo.create', 0) }}">Credit memo</a></li>
                            <li><a href="{{ route('sales-receipt.create') }}">Sales receipt</a></li>
                            <li><a href="#">Recurring payment</a></li>
                            <li><a href="#">Refund receipt</a></li>
                            <li><a href="#">Delayed credit</a></li>
                            <li><a href="#">Delayed charge</a></li>
                            <li><a href="{{ route('customer.create') }}">Add customer</a></li>
                        </ul>
                    </div>
                    {{-- Vendors Column --}}
                    <div class="qbo-flyout-column">
                        <h5 class="qbo-flyout-heading">Vendors</h5>
                        <ul class="qbo-flyout-list">
                            <li><a href="{{ route('expense.index') }}">Expense</a></li>
                            <li><a href="{{ route('checks.create') }}">Check</a></li>
                            <li><a href="{{ route('bill.create', 0) }}">Bill</a></li>
                            <li><a href="#">Pay bills</a></li>
                            <li><a href="{{ route('purchase.create', 0) }}">Purchase order</a></li>
                            <li><a href="#">Vendor credit</a></li>
                            <li><a href="#">Credit card credit</a></li>
                            <li><a href="#">Print checks</a></li>
                            <li><a href="{{ route('vender.create') }}">Add vendor</a></li>
                        </ul>
                    </div>
                    {{-- Team Column --}}
                    <div class="qbo-flyout-column">
                        <h5 class="qbo-flyout-heading">Team</h5>
                        <ul class="qbo-flyout-list">
                            <li><a href="#">Payroll
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    width="20"
                                    height="20"
                                    viewBox="0 0 120 120">
                                    <path
                                        fill="#9B9B9B"
                                        d="m56.64,50.06l19.903,0l-33.437,-32.828l-33.648,32.828l17.548,0c-0.612,43.89 -13.118,48.748 -13.256,48.795l1.63,6.355c19.376,-4.308 39.86,-24.972 41.26,-55.15l0,0z"
                                    />
                                </svg>
                            </a></li>
                            <li><a href="{{ route('timeActivity.create') }}">Single time activity</a></li>
                            <li><a href="#">Weekly timesheet</a></li>
                            <li><a href="#">Review time</a></li>
                            <li><a href="#">Add contractor</a></li>
                        </ul>
                    </div>
                    {{-- Other Column --}}
                    <div class="qbo-flyout-column">
                        <h5 class="qbo-flyout-heading">Other</h5>
                        <ul class="qbo-flyout-list">
                            <li><a href="#">Task</a></li>
                            <li><a href="#">Bank deposit</a></li>
                            <li><a href="{{ route('bank-transfer.create') }}">Transfer</a></li>
                            <li><a href="{{ route('journal-entry.create') }}">Journal entry</a></li>
                            <li><a href="{{ route('productservice.create') }}">Inventory qty adjustment</a></li>
                            <li><a href="#">Pay down credit card</a></li>
                            <li><a href="{{ route('productservice.create') }}">Add product/service</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bookmarks Button with Hover Flyout --}}
        <div class="qbo-bookmarks-wrapper">
            <button class="qbo-sidebar-item" id="qboBookmarksBtn" aria-label="Bookmarks" type="button">
                <div class="qbo-sidebar-item-icon">
                    <svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 21.28c-.348 0-.69-.093-.992-.267L12 18.152l-5.008 2.861A2 2 0 0 1 4 19.277V5a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v14.277a2.006 2.006 0 0 1-2 2v.003ZM7 4a1 1 0 0 0-1 1v14.282l5.008-2.867a2.011 2.011 0 0 1 1.984 0L18 19.277V5a1 1 0 0 0-1-1H7Z" fill="currentColor"></path>
                    </svg>
                </div>
                <span class="qbo-sidebar-item-label">Bookmarks</span>
            </button>
            
            {{-- Bookmarks Flyout --}}
            <div class="qbo-bookmarks-flyout" id="qboBookmarksFlyout">
                <div class="qbo-bookmarks-flyout-header">
                    <span class="qbo-bookmarks-title">Bookmarks</span>
                    <button type="button" class="qbo-bookmarks-edit-btn" id="qboOpenCustomizeBookmarks" title="Customize bookmarks">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" color="currentColor" width="20px" height="20px" focusable="false" aria-hidden="true" class=""><path fill="currentColor" d="M20.912 4.527 19.5 3.111a2.98 2.98 0 0 0-2.12-.88 2.98 2.98 0 0 0-2.118.873L3.927 14.401a1 1 0 0 0-.227.374c-.01.03-.026.056-.034.085L2.24 20.52a1 1 0 0 0 1.211 1.215L9.11 20.33c.033-.008.062-.026.094-.037a1 1 0 0 0 .367-.223l11.331-11.3a3 3 0 0 0 .01-4.243M5.171 17.067l1.662 1.666.081.08-2.328.579zm3.7.878-1.412-1.416-1.412-1.418 8.5-8.472 1.412 1.416 1.412 1.416zM19.494 7.353l-.709.706-1.412-1.416-1.412-1.417.708-.706a1 1 0 0 1 1.412.003L19.5 5.94a1 1 0 0 1-.006 1.414"></path></svg>
                    </button>
                </div>
                <div class="qbo-bookmarks-flyout-body" id="qboBookmarksList">
                    {{-- Empty state --}}
                    <div class="qbo-bookmarks-empty" id="qboBookmarksEmpty">
                        <p>Add a bookmark to get started.</p>
                    </div>
                    {{-- Bookmarks list (populated via JS) --}}
                    <div class="qbo-bookmarks-items" id="qboBookmarksItems" style="display: none;"></div>
                </div>
                {{-- Inline edit form (hidden by default) --}}
                <div class="qbo-bookmark-edit-form" id="qboBookmarkEditForm" style="display: none;">
                    <div class="qbo-bookmark-edit-header">Edit bookmark</div>
                    <label class="qbo-bookmark-edit-label">Bookmark label</label>
                    <input type="text" class="qbo-bookmark-edit-input" id="qboBookmarkEditInput">
                    <input type="hidden" id="qboBookmarkEditId">
                    <div class="qbo-bookmark-edit-actions">
                        <button type="button" class="qbo-bookmark-remove-btn" id="qboBookmarkRemoveBtn">Remove</button>
                        <button type="button" class="qbo-bookmark-save-btn" id="qboBookmarkSaveBtn">Save</button>
                    </div>
                </div>
                <div class="qbo-bookmarks-flyout-footer">
                    <button type="button" class="qbo-bookmark-current-btn" id="qboBookmarkCurrentPage">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="currentColor" focusable="false" aria-hidden="true"><path d="m17.983 11.027-5-.007.007-5a1 1 0 0 0-2 0l-.007 5-5-.008a1 1 0 0 0 0 2l5 .008-.008 5a1 1 0 1 0 2 0l.008-5 5 .007a1 1 0 1 0 0-2Z" fill="currentColor"></path></svg>
                        Bookmark current page
                    </button>
                </div>
            </div>
        </div>
    </section>

    {{-- Main Navigation Section --}}
    <section class="qbo-sidebar-section qbo-sidebar-group-divider">
        {{-- Home --}}
        <a href="{{ route('dashboard') }}" class="qbo-sidebar-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" aria-label="Home">
            <div class="qbo-sidebar-item-icon">
                <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="m21.579 8.2-8.992-6.013a1 1 0 0 0-1.109 0L2.469 8.172a1 1 0 1 0 1.107 1.666L4 9.558 3.98 21a1 1 0 0 0 1 1l5.02.01a1 1 0 0 0 1-1l.01-4.01A1 1 0 1 1 13 17l-.01 4a1 1 0 0 0 1 1l5.01.01c.263-.012.513-.115.708-.29a1 1 0 0 0 .292-.708l.018-11.449.448.3A1 1 0 1 0 21.579 8.2ZM18 20.008 14.992 20 15 17a3.009 3.009 0 0 0-2.932-3h-.08a3.01 3.01 0 0 0-2.978 3L9 20.008 5.981 20 6 8.227l6.029-4.007 5.991 4.007L18 20.008Z" fill="currentColor"></path>
                </svg>
            </div>
            <span class="qbo-sidebar-item-label">Home</span>
        </a>

        {{-- Feed --}}
        <a href="#" class="qbo-sidebar-item" aria-label="Feed">
            <div class="qbo-sidebar-item-icon">
                <svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="m6.65 22.122-1.686-.986 3.1-5.346a2.091 2.091 0 0 1 1.835-1.055 2.091 2.091 0 0 1 1.827 1.069l3.06 5.369-1.693.974-3.06-5.37a.162.162 0 0 0-.282 0l-3.1 5.345H6.65ZM18.055 21.795l-4.08-4.634a2.109 2.109 0 0 1-.421-2.082 2.096 2.096 0 0 1 1.583-1.407l6.037-1.234.388 1.921-6.037 1.234a.15.15 0 0 0-.12.108c-.02.06-.01.113.032.16l4.08 4.635-1.462 1.299ZM16.287 12.239a2.1 2.1 0 0 1-1.234-.419 2.107 2.107 0 0 1-.844-1.948l.695-6.147 1.939.22-.696 6.148a.153.153 0 0 0 .065.149c.05.036.106.043.163.018l5.648-2.47.778 1.797-5.648 2.47c-.28.123-.575.18-.866.178v.004ZM11.101 9.755c-.29 0-.585-.062-.865-.186L4.609 7.052l.794-1.79 5.627 2.517c.057.026.112.02.162-.016a.154.154 0 0 0 .067-.149l-.646-6.156 1.94-.204.644 6.153a2.11 2.11 0 0 1-.86 1.94c-.37.271-.802.408-1.237.408h.001ZM4.562 17.823l-1.437-1.327 4.17-4.554a.152.152 0 0 0 .036-.16.156.156 0 0 0-.12-.11l-6.01-1.348.425-1.913 6.01 1.351A2.095 2.095 0 0 1 9.193 11.2a2.113 2.113 0 0 1-.46 2.073l-4.17 4.553v-.003Z" fill="currentColor"></path>
                </svg>
            </div>
            <span class="qbo-sidebar-item-label">Feed</span>
        </a>

        {{-- Reports with Hover Flyout --}}
        <div class="qbo-reports-wrapper">
            <a href="{{ route('allReports') }}" class="qbo-sidebar-item" aria-label="Reports">
                <div class="qbo-sidebar-item-icon">
                    <svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="m20.989 20.013-16-.023a1 1 0 0 1-1-1l.023-16a1 1 0 0 0-1-1 1 1 0 0 0-1 1l-.023 16a3 3 0 0 0 3 3l16 .023a1 1 0 1 0 0-2Z" fill="currentColor"></path>
                        <path d="M5.46 18.838a.999.999 0 0 0 1.379-.316l4.236-6.757L13.2 14.6a1.019 1.019 0 0 0 .867.4 1 1 0 0 0 .806-.511l4.172-7.483a2.017 2.017 0 1 0-1.745-.977l-3.424 6.14-2.076-2.77a1 1 0 0 0-1.648.068L5.144 17.46a1 1 0 0 0 .316 1.378Z" fill="currentColor"></path>
                    </svg>
                </div>
                <span class="qbo-sidebar-item-label">Reports</span>
            </a>
            
            {{-- Reports Flyout --}}
            <div class="qbo-reports-flyout" id="qboReportsFlyout">
                <div class="qbo-reports-flyout-header">REPORTS</div>
                <div class="qbo-reports-flyout-body">
                    <div class="qbo-reports-item" data-key="standard_reports" data-label="Standard reports" data-route="allReports">
                        <a href="{{ route('allReports') }}" class="qbo-reports-link">Standard reports</a>
                        <button type="button" class="qbo-reports-bookmark-btn" data-key="standard_reports" data-label="Standard reports" data-route="allReports" title="Bookmark this page">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18">
                                <path fill="currentColor" d="M18 21.28c-.348 0-.69-.093-.992-.267L12 18.152l-5.008 2.861A2 2 0 0 1 4 19.277V5a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v14.277a2.006 2.006 0 0 1-2 2v.003ZM7 4a1 1 0 0 0-1 1v14.282l5.008-2.867a2.011 2.011 0 0 1 1.984 0L18 19.277V5a1 1 0 0 0-1-1H7Z"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="qbo-reports-item" data-key="custom_reports" data-label="Custom reports" data-route="">
                        <a href="#" class="qbo-reports-link">Custom reports</a>
                        <button type="button" class="qbo-reports-bookmark-btn" data-key="custom_reports" data-label="Custom reports" data-route="" title="Bookmark this page">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18">
                                <path fill="currentColor" d="M18 21.28c-.348 0-.69-.093-.992-.267L12 18.152l-5.008 2.861A2 2 0 0 1 4 19.277V5a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v14.277a2.006 2.006 0 0 1-2 2v.003ZM7 4a1 1 0 0 0-1 1v14.282l5.008-2.867a2.011 2.011 0 0 1 1.984 0L18 19.277V5a1 1 0 0 0-1-1H7Z"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="qbo-reports-item" data-key="management_reports" data-label="Management reports" data-route="">
                        <a href="#" class="qbo-reports-link">Management reports</a>
                        <button type="button" class="qbo-reports-bookmark-btn" data-key="management_reports" data-label="Management reports" data-route="" title="Bookmark this page">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18">
                                <path fill="currentColor" d="M18 21.28c-.348 0-.69-.093-.992-.267L12 18.152l-5.008 2.861A2 2 0 0 1 4 19.277V5a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v14.277a2.006 2.006 0 0 1-2 2v.003ZM7 4a1 1 0 0 0-1 1v14.282l5.008-2.867a2.011 2.011 0 0 1 1.984 0L18 19.277V5a1 1 0 0 0-1-1H7Z"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="qbo-reports-item qbo-reports-submenu" data-key="financial_planning" data-label="Financial planning" data-route="">
                        <a href="#" class="qbo-reports-link">Financial planning</a>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="16" height="16" class="qbo-reports-chevron">
                            <path fill="currentColor" d="M10.707 17.707a1 1 0 0 1-1.414-1.414L13.586 12 9.293 7.707a1 1 0 1 1 1.414-1.414l5 5a1 1 0 0 1 0 1.414l-5 5Z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- All Apps with Hover Flyout and Submenus --}}
        <div class="qbo-allapps-wrapper">
            <a href="#" class="qbo-sidebar-item" id="qboAllAppsBtn" aria-label="All apps">
                <div class="qbo-sidebar-item-icon">
                    <svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="m10.636 4.565-.07.071a1.929 1.929 0 0 0 0 2.728l.07.07a1.929 1.929 0 0 0 2.728 0l.07-.07a1.929 1.929 0 0 0 0-2.728l-.07-.07a1.929 1.929 0 0 0-2.728 0ZM4.636 4.565l-.07.071a1.929 1.929 0 0 0 0 2.728l.07.07a1.929 1.929 0 0 0 2.728 0l.071-.07a1.929 1.929 0 0 0 0-2.728l-.07-.07a1.929 1.929 0 0 0-2.729 0ZM16.636 4.565l-.071.071a1.929 1.929 0 0 0 0 2.728l.07.07a1.929 1.929 0 0 0 2.728 0l.071-.07a1.929 1.929 0 0 0 0-2.728l-.07-.07a1.929 1.929 0 0 0-2.729 0ZM10.636 10.565l-.071.071a1.929 1.929 0 0 0 0 2.728l.07.07a1.929 1.929 0 0 0 2.729 0l.07-.07a1.929 1.929 0 0 0 0-2.728l-.07-.07a1.929 1.929 0 0 0-2.729 0ZM4.636 10.565l-.07.071a1.929 1.929 0 0 0 0 2.728l.07.07a1.929 1.929 0 0 0 2.728 0l.07-.07a1.929 1.929 0 0 0 0-2.728l-.07-.07a1.929 1.929 0 0 0-2.728 0ZM16.636 10.565l-.07.071a1.929 1.929 0 0 0 0 2.728l.07.07a1.929 1.929 0 0 0 2.728 0l.07-.07a1.929 1.929 0 0 0 0-2.728l-.07-.07a1.929 1.929 0 0 0-2.728 0ZM10.636 16.565l-.07.071a1.929 1.929 0 0 0 0 2.728l.07.07a1.929 1.929 0 0 0 2.728 0l.07-.07a1.929 1.929 0 0 0 0-2.728l-.07-.07a1.929 1.929 0 0 0-2.728 0ZM4.636 16.565l-.071.071a1.929 1.929 0 0 0 0 2.728l.07.07a1.929 1.929 0 0 0 2.729 0l.07-.07a1.929 1.929 0 0 0 0-2.728l-.07-.07a1.929 1.929 0 0 0-2.728 0ZM16.636 16.565l-.07.071a1.929 1.929 0 0 0 0 2.728l.07.07a1.929 1.929 0 0 0 2.728 0l.07-.07a1.929 1.929 0 0 0 0-2.728l-.07-.07a1.929 1.929 0 0 0-2.728 0Z" fill="currentColor"></path>
                    </svg>
                </div>
                <span class="qbo-sidebar-item-label">All apps</span>
            </a>
            
            {{-- All Apps Flyout --}}
            <div class="qbo-allapps-flyout" id="qboAllAppsFlyout">
                <div class="qbo-allapps-flyout-header">All apps</div>
                <div class="qbo-allapps-flyout-body">
                    {{-- Accounting --}}
                    <div class="qbo-allapps-item qbo-allapps-parent" data-submenu="accounting">
                        <div class="qbo-allapps-item-content">
                            <img src="https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/accounting/1/0/0/accounting.svg" class="qbo-allapps-icon" alt="">
                            <span class="qbo-allapps-label">Accounting</span>
                        </div>
                        <svg width="16px" height="16px" viewBox="0 0 24 24" fill="currentColor" class="qbo-allapps-chevron"><path d="M9.009 19.013a1 1 0 0 1-.709-1.708l5.3-5.285-5.281-5.3a1 1 0 1 1 1.416-1.413l5.991 6.01a1 1 0 0 1 0 1.413l-6.011 5.991a.994.994 0 0 1-.706.292Z"></path></svg>
                        {{-- Accounting Submenu --}}
                        <div class="qbo-allapps-submenu" id="submenu-accounting">
                            <div class="qbo-submenu-item" data-key="bank_transactions" data-label="Bank transactions" data-route="">
                                <a href="{{ route('transaction.bankTransactions') }}" class="qbo-submenu-link">Bank transactions</a>
                                <button type="button" class="qbo-submenu-bookmark-btn" data-key="bank_transactions" data-label="Bank transactions"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M18 21.28c-.348 0-.69-.093-.992-.267L12 18.152l-5.008 2.861A2 2 0 0 1 4 19.277V5a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v14.277a2.006 2.006 0 0 1-2 2v.003ZM7 4a1 1 0 0 0-1 1v14.282l5.008-2.867a2.011 2.011 0 0 1 1.984 0L18 19.277V5a1 1 0 0 0-1-1H7Z"></path></svg></button>
                            </div>
                            <div class="qbo-submenu-item" data-key="receipts" data-label="Receipts" data-route="">
                                <a href="{{ route('reciept.index') }}" class="qbo-submenu-link">Receipts</a>
                                <button type="button" class="qbo-submenu-bookmark-btn" data-key="receipts" data-label="Receipts"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M18 21.28c-.348 0-.69-.093-.992-.267L12 18.152l-5.008 2.861A2 2 0 0 1 4 19.277V5a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v14.277a2.006 2.006 0 0 1-2 2v.003ZM7 4a1 1 0 0 0-1 1v14.282l5.008-2.867a2.011 2.011 0 0 1 1.984 0L18 19.277V5a1 1 0 0 0-1-1H7Z"></path></svg></button>
                            </div>
                            <div class="qbo-submenu-item" data-key="reconcile" data-label="Reconcile" data-route="">
                                <a href="#" class="qbo-submenu-link">Reconcile</a>
                                <button type="button" class="qbo-submenu-bookmark-btn" data-key="reconcile" data-label="Reconcile"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M18 21.28c-.348 0-.69-.093-.992-.267L12 18.152l-5.008 2.861A2 2 0 0 1 4 19.277V5a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v14.277a2.006 2.006 0 0 1-2 2v.003ZM7 4a1 1 0 0 0-1 1v14.282l5.008-2.867a2.011 2.011 0 0 1 1.984 0L18 19.277V5a1 1 0 0 0-1-1H7Z"></path></svg></button>
                            </div>
                            <div class="qbo-submenu-item" data-key="chart_of_accounts" data-label="Chart of accounts" data-route="chartofaccount.index">
                                <a href="{{ route('chart-of-account.index') }}" class="qbo-submenu-link">Chart of accounts</a>
                                <button type="button" class="qbo-submenu-bookmark-btn" data-key="chart_of_accounts" data-label="Chart of accounts" data-route="chartofaccount.index"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M18 21.28c-.348 0-.69-.093-.992-.267L12 18.152l-5.008 2.861A2 2 0 0 1 4 19.277V5a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v14.277a2.006 2.006 0 0 1-2 2v.003ZM7 4a1 1 0 0 0-1 1v14.282l5.008-2.867a2.011 2.011 0 0 1 1.984 0L18 19.277V5a1 1 0 0 0-1-1H7Z"></path></svg></button>
                            </div>
                            <div class="qbo-submenu-item" data-key="journal_entry" data-label="Journal entry" data-route="journal-entry.index">
                                <a href="{{ route('journal-entry.index') }}" class="qbo-submenu-link">Journal entry</a>
                                <button type="button" class="qbo-submenu-bookmark-btn" data-key="journal_entry" data-label="Journal entry" data-route="journal-entry.index"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M18 21.28c-.348 0-.69-.093-.992-.267L12 18.152l-5.008 2.861A2 2 0 0 1 4 19.277V5a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v14.277a2.006 2.006 0 0 1-2 2v.003ZM7 4a1 1 0 0 0-1 1v14.282l5.008-2.867a2.011 2.011 0 0 1 1.984 0L18 19.277V5a1 1 0 0 0-1-1H7Z"></path></svg></button>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Expenses & Bills --}}
                    <div class="qbo-allapps-item qbo-allapps-parent" data-submenu="expenses">
                        <div class="qbo-allapps-item-content">
                            <img src="https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/expenses/1/0/0/expenses.svg" class="qbo-allapps-icon" alt="">
                            <span class="qbo-allapps-label">Expenses & Bills</span>
                        </div>
                        <svg width="16px" height="16px" viewBox="0 0 24 24" fill="currentColor" class="qbo-allapps-chevron"><path d="M9.009 19.013a1 1 0 0 1-.709-1.708l5.3-5.285-5.281-5.3a1 1 0 1 1 1.416-1.413l5.991 6.01a1 1 0 0 1 0 1.413l-6.011 5.991a.994.994 0 0 1-.706.292Z"></path></svg>
                        <div class="qbo-allapps-submenu" id="submenu-expenses">
                            <div class="qbo-submenu-item" data-key="expense_transactions" data-label="Expense transactions" data-route="expense.index">
                                <a href="{{ route('expense.index') }}" class="qbo-submenu-link">Expense transactions</a>
                                <button type="button" class="qbo-submenu-bookmark-btn" data-key="expense_transactions" data-label="Expense transactions" data-route="expense.index"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M18 21.28c-.348 0-.69-.093-.992-.267L12 18.152l-5.008 2.861A2 2 0 0 1 4 19.277V5a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v14.277a2.006 2.006 0 0 1-2 2v.003ZM7 4a1 1 0 0 0-1 1v14.282l5.008-2.867a2.011 2.011 0 0 1 1.984 0L18 19.277V5a1 1 0 0 0-1-1H7Z"></path></svg></button>
                            </div>
                            <div class="qbo-submenu-item" data-key="vendors" data-label="Vendors" data-route="vender.index">
                                <a href="{{ route('vender.index') }}" class="qbo-submenu-link">Vendors</a>
                                <button type="button" class="qbo-submenu-bookmark-btn" data-key="vendors" data-label="Vendors" data-route="vender.index"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M18 21.28c-.348 0-.69-.093-.992-.267L12 18.152l-5.008 2.861A2 2 0 0 1 4 19.277V5a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v14.277a2.006 2.006 0 0 1-2 2v.003ZM7 4a1 1 0 0 0-1 1v14.282l5.008-2.867a2.011 2.011 0 0 1 1.984 0L18 19.277V5a1 1 0 0 0-1-1H7Z"></path></svg></button>
                            </div>
                            <div class="qbo-submenu-item" data-key="bills" data-label="Bills" data-route="bill.index">
                                <a href="{{ route('bill.index') }}" class="qbo-submenu-link">Bills</a>
                                <button type="button" class="qbo-submenu-bookmark-btn" data-key="bills" data-label="Bills" data-route="bill.index"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M18 21.28c-.348 0-.69-.093-.992-.267L12 18.152l-5.008 2.861A2 2 0 0 1 4 19.277V5a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v14.277a2.006 2.006 0 0 1-2 2v.003ZM7 4a1 1 0 0 0-1 1v14.282l5.008-2.867a2.011 2.011 0 0 1 1.984 0L18 19.277V5a1 1 0 0 0-1-1H7Z"></path></svg></button>
                            </div>
                            <div class="qbo-submenu-item" data-key="bill_payments" data-label="Bill payments" data-route="">
                                <a href="#" class="qbo-submenu-link">Bill payments</a>
                                <button type="button" class="qbo-submenu-bookmark-btn" data-key="bill_payments" data-label="Bill payments"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M18 21.28c-.348 0-.69-.093-.992-.267L12 18.152l-5.008 2.861A2 2 0 0 1 4 19.277V5a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v14.277a2.006 2.006 0 0 1-2 2v.003ZM7 4a1 1 0 0 0-1 1v14.282l5.008-2.867a2.011 2.011 0 0 1 1.984 0L18 19.277V5a1 1 0 0 0-1-1H7Z"></path></svg></button>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Sales & Get Paid --}}
                    <div class="qbo-allapps-item qbo-allapps-parent" data-submenu="sales">
                        <div class="qbo-allapps-item-content">
                            <img src="https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/sales-payments/1/0/0/sales-payments.svg" class="qbo-allapps-icon" alt="">
                            <span class="qbo-allapps-label">Sales & Get Paid</span>
                        </div>
                        <svg width="16px" height="16px" viewBox="0 0 24 24" fill="currentColor" class="qbo-allapps-chevron"><path d="M9.009 19.013a1 1 0 0 1-.709-1.708l5.3-5.285-5.281-5.3a1 1 0 1 1 1.416-1.413l5.991 6.01a1 1 0 0 1 0 1.413l-6.011 5.991a.994.994 0 0 1-.706.292Z"></path></svg>
                        <div class="qbo-allapps-submenu" id="submenu-sales">
                            <div class="qbo-submenu-item" data-key="invoices" data-label="Invoices" data-route="invoice.index">
                                <a href="{{ route('invoice.index') }}" class="qbo-submenu-link">Invoices</a>
                                <button type="button" class="qbo-submenu-bookmark-btn" data-key="invoices" data-label="Invoices" data-route="invoice.index"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M18 21.28c-.348 0-.69-.093-.992-.267L12 18.152l-5.008 2.861A2 2 0 0 1 4 19.277V5a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v14.277a2.006 2.006 0 0 1-2 2v.003ZM7 4a1 1 0 0 0-1 1v14.282l5.008-2.867a2.011 2.011 0 0 1 1.984 0L18 19.277V5a1 1 0 0 0-1-1H7Z"></path></svg></button>
                            </div>
                            <div class="qbo-submenu-item" data-key="estimates" data-label="Estimates" data-route="proposal.index">
                                <a href="{{ route('proposal.index') }}" class="qbo-submenu-link">Estimates</a>
                                <button type="button" class="qbo-submenu-bookmark-btn" data-key="estimates" data-label="Estimates" data-route="proposal.index"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M18 21.28c-.348 0-.69-.093-.992-.267L12 18.152l-5.008 2.861A2 2 0 0 1 4 19.277V5a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v14.277a2.006 2.006 0 0 1-2 2v.003ZM7 4a1 1 0 0 0-1 1v14.282l5.008-2.867a2.011 2.011 0 0 1 1.984 0L18 19.277V5a1 1 0 0 0-1-1H7Z"></path></svg></button>
                            </div>
                            <div class="qbo-submenu-item" data-key="sales_receipts" data-label="Sales receipts" data-route="sales-receipt.index">
                                <a href="{{ route('sales-receipt.index') }}" class="qbo-submenu-link">Sales receipts</a>
                                <button type="button" class="qbo-submenu-bookmark-btn" data-key="sales_receipts" data-label="Sales receipts" data-route="sales-receipt.index"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M18 21.28c-.348 0-.69-.093-.992-.267L12 18.152l-5.008 2.861A2 2 0 0 1 4 19.277V5a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v14.277a2.006 2.006 0 0 1-2 2v.003ZM7 4a1 1 0 0 0-1 1v14.282l5.008-2.867a2.011 2.011 0 0 1 1.984 0L18 19.277V5a1 1 0 0 0-1-1H7Z"></path></svg></button>
                            </div>
                            <div class="qbo-submenu-item" data-key="credit_memos" data-label="Credit memos" data-route="creditmemo.index">
                                <a href="{{ route('creditmemo.index') }}" class="qbo-submenu-link">Credit memos</a>
                                <button type="button" class="qbo-submenu-bookmark-btn" data-key="credit_memos" data-label="Credit memos" data-route="creditmemo.index"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M18 21.28c-.348 0-.69-.093-.992-.267L12 18.152l-5.008 2.861A2 2 0 0 1 4 19.277V5a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v14.277a2.006 2.006 0 0 1-2 2v.003ZM7 4a1 1 0 0 0-1 1v14.282l5.008-2.867a2.011 2.011 0 0 1 1.984 0L18 19.277V5a1 1 0 0 0-1-1H7Z"></path></svg></button>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Customer Hub --}}
                    <div class="qbo-allapps-item qbo-allapps-parent" data-submenu="customers">
                        <div class="qbo-allapps-item-content">
                            <img src="https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/customers/1/0/0/customers.svg" class="qbo-allapps-icon" alt="">
                            <span class="qbo-allapps-label">Customer Hub</span>
                        </div>
                        <svg width="16px" height="16px" viewBox="0 0 24 24" fill="currentColor" class="qbo-allapps-chevron"><path d="M9.009 19.013a1 1 0 0 1-.709-1.708l5.3-5.285-5.281-5.3a1 1 0 1 1 1.416-1.413l5.991 6.01a1 1 0 0 1 0 1.413l-6.011 5.991a.994.994 0 0 1-.706.292Z"></path></svg>
                        <div class="qbo-allapps-submenu" id="submenu-customers">
                            <div class="qbo-submenu-item" data-key="customers" data-label="Customers" data-route="customer.index">
                                <a href="{{ route('customer.index') }}" class="qbo-submenu-link">Customers</a>
                                <button type="button" class="qbo-submenu-bookmark-btn" data-key="customers" data-label="Customers" data-route="customer.index"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M18 21.28c-.348 0-.69-.093-.992-.267L12 18.152l-5.008 2.861A2 2 0 0 1 4 19.277V5a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v14.277a2.006 2.006 0 0 1-2 2v.003ZM7 4a1 1 0 0 0-1 1v14.282l5.008-2.867a2.011 2.011 0 0 1 1.984 0L18 19.277V5a1 1 0 0 0-1-1H7Z"></path></svg></button>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Team --}}
                    <div class="qbo-allapps-item qbo-allapps-parent" data-submenu="team">
                        <div class="qbo-allapps-item-content">
                            <img src="https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/team/1/0/0/team.svg" class="qbo-allapps-icon" alt="">
                            <span class="qbo-allapps-label">Team</span>
                        </div>
                        <svg width="16px" height="16px" viewBox="0 0 24 24" fill="currentColor" class="qbo-allapps-chevron"><path d="M9.009 19.013a1 1 0 0 1-.709-1.708l5.3-5.285-5.281-5.3a1 1 0 1 1 1.416-1.413l5.991 6.01a1 1 0 0 1 0 1.413l-6.011 5.991a.994.994 0 0 1-.706.292Z"></path></svg>
                        <div class="qbo-allapps-submenu" id="submenu-team">
                            <div class="qbo-submenu-item" data-key="employees" data-label="Employees" data-route="employee.index">
                                <a href="{{ route('employee.index') }}" class="qbo-submenu-link">Employees</a>
                                <button type="button" class="qbo-submenu-bookmark-btn" data-key="employees" data-label="Employees" data-route="employee.index"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M18 21.28c-.348 0-.69-.093-.992-.267L12 18.152l-5.008 2.861A2 2 0 0 1 4 19.277V5a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v14.277a2.006 2.006 0 0 1-2 2v.003ZM7 4a1 1 0 0 0-1 1v14.282l5.008-2.867a2.011 2.011 0 0 1 1.984 0L18 19.277V5a1 1 0 0 0-1-1H7Z"></path></svg></button>
                            </div>
                            <div class="qbo-submenu-item" data-key="time_entries" data-label="Time entries" data-route="">
                                <a href="#" class="qbo-submenu-link">Time entries</a>
                                <button type="button" class="qbo-submenu-bookmark-btn" data-key="time_entries" data-label="Time entries"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M18 21.28c-.348 0-.69-.093-.992-.267L12 18.152l-5.008 2.861A2 2 0 0 1 4 19.277V5a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v14.277a2.006 2.006 0 0 1-2 2v.003ZM7 4a1 1 0 0 0-1 1v14.282l5.008-2.867a2.011 2.011 0 0 1 1.984 0L18 19.277V5a1 1 0 0 0-1-1H7Z"></path></svg></button>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Time --}}
                    <div class="qbo-allapps-item qbo-allapps-parent" data-submenu="time">
                        <div class="qbo-allapps-item-content">
                            <img src="https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/time/1/0/0/time.svg" class="qbo-allapps-icon" alt="">
                            <span class="qbo-allapps-label">Time</span>
                        </div>
                        <svg width="16px" height="16px" viewBox="0 0 24 24" fill="currentColor" class="qbo-allapps-chevron"><path d="M9.009 19.013a1 1 0 0 1-.709-1.708l5.3-5.285-5.281-5.3a1 1 0 1 1 1.416-1.413l5.991 6.01a1 1 0 0 1 0 1.413l-6.011 5.991a.994.994 0 0 1-.706.292Z"></path></svg>
                        <div class="qbo-allapps-submenu" id="submenu-time">
                            <div class="qbo-submenu-item" data-key="single_time_activity" data-label="Single time activity" data-route="timeActivity.create">
                                <a href="{{ route('timeActivity.create') }}" class="qbo-submenu-link">Single time activity</a>
                                <button type="button" class="qbo-submenu-bookmark-btn" data-key="single_time_activity" data-label="Single time activity" data-route="timeActivity.create"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M18 21.28c-.348 0-.69-.093-.992-.267L12 18.152l-5.008 2.861A2 2 0 0 1 4 19.277V5a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v14.277a2.006 2.006 0 0 1-2 2v.003ZM7 4a1 1 0 0 0-1 1v14.282l5.008-2.867a2.011 2.011 0 0 1 1.984 0L18 19.277V5a1 1 0 0 0-1-1H7Z"></path></svg></button>
                            </div>
                            <div class="qbo-submenu-item" data-key="weekly_timesheet" data-label="Weekly timesheet" data-route="">
                                <a href="#" class="qbo-submenu-link">Weekly timesheet</a>
                                <button type="button" class="qbo-submenu-bookmark-btn" data-key="weekly_timesheet" data-label="Weekly timesheet"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M18 21.28c-.348 0-.69-.093-.992-.267L12 18.152l-5.008 2.861A2 2 0 0 1 4 19.277V5a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v14.277a2.006 2.006 0 0 1-2 2v.003ZM7 4a1 1 0 0 0-1 1v14.282l5.008-2.867a2.011 2.011 0 0 1 1.984 0L18 19.277V5a1 1 0 0 0-1-1H7Z"></path></svg></button>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Inventory --}}
                    <div class="qbo-allapps-item qbo-allapps-parent" data-submenu="inventory">
                        <div class="qbo-allapps-item-content">
                            <img src="https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/inventory/1/0/0/inventory.svg" class="qbo-allapps-icon" alt="">
                            <span class="qbo-allapps-label">Inventory</span>
                        </div>
                        <svg width="16px" height="16px" viewBox="0 0 24 24" fill="currentColor" class="qbo-allapps-chevron"><path d="M9.009 19.013a1 1 0 0 1-.709-1.708l5.3-5.285-5.281-5.3a1 1 0 1 1 1.416-1.413l5.991 6.01a1 1 0 0 1 0 1.413l-6.011 5.991a.994.994 0 0 1-.706.292Z"></path></svg>
                        <div class="qbo-allapps-submenu" id="submenu-inventory">
                            <div class="qbo-submenu-item" data-key="products_services" data-label="Products & services" data-route="productservice.index">
                                <a href="{{ route('productservice.index') }}" class="qbo-submenu-link">Products & services</a>
                                <button type="button" class="qbo-submenu-bookmark-btn" data-key="products_services" data-label="Products & services" data-route="productservice.index"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M18 21.28c-.348 0-.69-.093-.992-.267L12 18.152l-5.008 2.861A2 2 0 0 1 4 19.277V5a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v14.277a2.006 2.006 0 0 1-2 2v.003ZM7 4a1 1 0 0 0-1 1v14.282l5.008-2.867a2.011 2.011 0 0 1 1.984 0L18 19.277V5a1 1 0 0 0-1-1H7Z"></path></svg></button>
                            </div>
                            <div class="qbo-submenu-item" data-key="purchase_orders" data-label="Purchase orders" data-route="purchase.index">
                                <a href="{{ route('purchase.index') }}" class="qbo-submenu-link">Purchase orders</a>
                                <button type="button" class="qbo-submenu-bookmark-btn" data-key="purchase_orders" data-label="Purchase orders" data-route="purchase.index"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M18 21.28c-.348 0-.69-.093-.992-.267L12 18.152l-5.008 2.861A2 2 0 0 1 4 19.277V5a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v14.277a2.006 2.006 0 0 1-2 2v.003ZM7 4a1 1 0 0 0-1 1v14.282l5.008-2.867a2.011 2.011 0 0 1 1.984 0L18 19.277V5a1 1 0 0 0-1-1H7Z"></path></svg></button>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Sales Tax --}}
                    <div class="qbo-allapps-item qbo-allapps-parent" data-submenu="salestax">
                        <div class="qbo-allapps-item-content">
                            <img src="https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/sales-tax/1/0/0/sales-tax.svg" class="qbo-allapps-icon" alt="">
                            <span class="qbo-allapps-label">Sales Tax</span>
                        </div>
                        <svg width="16px" height="16px" viewBox="0 0 24 24" fill="currentColor" class="qbo-allapps-chevron"><path d="M9.009 19.013a1 1 0 0 1-.709-1.708l5.3-5.285-5.281-5.3a1 1 0 1 1 1.416-1.413l5.991 6.01a1 1 0 0 1 0 1.413l-6.011 5.991a.994.994 0 0 1-.706.292Z"></path></svg>
                        <div class="qbo-allapps-submenu" id="submenu-salestax">
                            <div class="qbo-submenu-item" data-key="sales_tax_overview" data-label="Sales Tax overview" data-route="taxes.index">
                                <a href="{{ route('taxes.index') }}" class="qbo-submenu-link">Sales Tax overview</a>
                                <button type="button" class="qbo-submenu-bookmark-btn" data-key="sales_tax_overview" data-label="Sales Tax overview" data-route="taxes.index"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M18 21.28c-.348 0-.69-.093-.992-.267L12 18.152l-5.008 2.861A2 2 0 0 1 4 19.277V5a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v14.277a2.006 2.006 0 0 1-2 2v.003ZM7 4a1 1 0 0 0-1 1v14.282l5.008-2.867a2.011 2.011 0 0 1 1.984 0L18 19.277V5a1 1 0 0 0-1-1H7Z"></path></svg></button>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Business Tax --}}
                    <div class="qbo-allapps-item qbo-allapps-parent" data-submenu="businesstax">
                        <div class="qbo-allapps-item-content">
                            <img src="https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/business-tax/1/0/0/business-tax.svg" class="qbo-allapps-icon" alt="">
                            <span class="qbo-allapps-label">Business Tax</span>
                        </div>
                        <svg width="16px" height="16px" viewBox="0 0 24 24" fill="currentColor" class="qbo-allapps-chevron"><path d="M9.009 19.013a1 1 0 0 1-.709-1.708l5.3-5.285-5.281-5.3a1 1 0 1 1 1.416-1.413l5.991 6.01a1 1 0 0 1 0 1.413l-6.011 5.991a.994.994 0 0 1-.706.292Z"></path></svg>
                        <div class="qbo-allapps-submenu" id="submenu-businesstax">
                            <div class="qbo-submenu-item" data-key="business_tax_overview" data-label="Business Tax overview" data-route="">
                                <a href="#" class="qbo-submenu-link">Business Tax overview</a>
                                <button type="button" class="qbo-submenu-bookmark-btn" data-key="business_tax_overview" data-label="Business Tax overview"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M18 21.28c-.348 0-.69-.093-.992-.267L12 18.152l-5.008 2.861A2 2 0 0 1 4 19.277V5a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v14.277a2.006 2.006 0 0 1-2 2v.003ZM7 4a1 1 0 0 0-1 1v14.282l5.008-2.867a2.011 2.011 0 0 1 1.984 0L18 19.277V5a1 1 0 0 0-1-1H7Z"></path></svg></button>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Lending --}}
                    <div class="qbo-allapps-item qbo-allapps-parent" data-submenu="lending">
                        <div class="qbo-allapps-item-content">
                            <img src="https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/lending/1/0/0/lending.svg" class="qbo-allapps-icon" alt="">
                            <span class="qbo-allapps-label">Lending</span>
                        </div>
                        <svg width="16px" height="16px" viewBox="0 0 24 24" fill="currentColor" class="qbo-allapps-chevron"><path d="M9.009 19.013a1 1 0 0 1-.709-1.708l5.3-5.285-5.281-5.3a1 1 0 1 1 1.416-1.413l5.991 6.01a1 1 0 0 1 0 1.413l-6.011 5.991a.994.994 0 0 1-.706.292Z"></path></svg>
                        <div class="qbo-allapps-submenu" id="submenu-lending">
                            <div class="qbo-submenu-item" data-key="lending_overview" data-label="Lending overview" data-route="">
                                <a href="#" class="qbo-submenu-link">Lending overview</a>
                                <button type="button" class="qbo-submenu-bookmark-btn" data-key="lending_overview" data-label="Lending overview"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M18 21.28c-.348 0-.69-.093-.992-.267L12 18.152l-5.008 2.861A2 2 0 0 1 4 19.277V5a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v14.277a2.006 2.006 0 0 1-2 2v.003ZM7 4a1 1 0 0 0-1 1v14.282l5.008-2.867a2.011 2.011 0 0 1 1.984 0L18 19.277V5a1 1 0 0 0-1-1H7Z"></path></svg></button>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="qbo-allapps-divider">
                    
                    {{-- Payroll (Premium) --}}
                    <div class="qbo-allapps-item qbo-allapps-standalone">
                        <div class="qbo-allapps-item-content">
                            <img src="https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/payroll/1/0/0/payroll.svg" class="qbo-allapps-icon" alt="">
                            <span class="qbo-allapps-label">Payroll</span>
                        </div>
                        <svg width="16px" height="16px" viewBox="0 0 24 24" fill="#9B9B9B" class="qbo-allapps-premium-icon"><path d="m21.782 9.375-4-5A1 1 0 0 0 17 4H7a1 1 0 0 0-.781.375l-4 5a1 1 0 0 0 .074 1.332l9 9a1 1 0 0 0 1.414 0l9-9a1 1 0 0 0 .075-1.332ZM18.92 9h-3.2l-1-3h1.8l2.4 3ZM8.28 11l1.433 4.3L5.414 11H8.28Zm5.333 0L12 15.839 10.387 11h3.226Zm-3.225-2 1-3h1.22l1 3h-3.22Zm5.333 2h2.865l-4.3 4.3 1.435-4.3Zm-8.24-5h1.8l-1 3h-3.2l2.4-3Z"></path></svg>
                    </div>
                    
                    {{-- Marketing (Premium) --}}
                    <div class="qbo-allapps-item qbo-allapps-standalone">
                        <div class="qbo-allapps-item-content">
                            <img src="https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/marketing/1/0/0/marketing.svg" class="qbo-allapps-icon" alt="">
                            <span class="qbo-allapps-label">Marketing</span>
                        </div>
                        <svg width="16px" height="16px" viewBox="0 0 24 24" fill="#9B9B9B" class="qbo-allapps-premium-icon"><path d="m21.782 9.375-4-5A1 1 0 0 0 17 4H7a1 1 0 0 0-.781.375l-4 5a1 1 0 0 0 .074 1.332l9 9a1 1 0 0 0 1.414 0l9-9a1 1 0 0 0 .075-1.332ZM18.92 9h-3.2l-1-3h1.8l2.4 3ZM8.28 11l1.433 4.3L5.414 11H8.28Zm5.333 0L12 15.839 10.387 11h3.226Zm-3.225-2 1-3h1.22l1 3h-3.22Zm5.333 2h2.865l-4.3 4.3 1.435-4.3Zm-8.24-5h1.8l-1 3h-3.2l2.4-3Z"></path></svg>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Pinned Section --}}
    <section class="qbo-sidebar-section qbo-pinned-section">
        <span class="qbo-pinned-label">PINNED</span>
        {{-- More Button with Pinned Flyout (shows pinned items on hover) --}}
        <div class="qbo-pinned-wrapper">
            <a href="#" class="qbo-sidebar-item" id="qboMoreBtn" aria-label="More">
                <div class="qbo-sidebar-item-icon">
                    <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM6 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM18 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" fill="currentColor"></path>
                    </svg>
                </div>
                <span class="qbo-sidebar-item-label">More</span>
            </a>
            {{-- Pinned Items Flyout --}}
            <div class="qbo-pinned-flyout" id="qboPinnedFlyout">
                <!-- <div class="qbo-pinned-flyout-header">Pinned</div> -->
                <div class="qbo-pinned-flyout-body" id="qboPinnedFlyoutBody">
                    {{-- Pinned items will be loaded dynamically --}}
                    <div class="qbo-pinned-empty" id="qboPinnedEmpty">
                        <p>No pinned items yet.</p>
                        <p class="qbo-pinned-empty-hint">Pin apps from the Customize menu to see them here.</p>
                    </div>
                    <div class="qbo-pinned-items" id="qboPinnedItems" style="display:none;">
                        {{-- Items loaded dynamically --}}
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Footer Section --}}
    <section class="qbo-sidebar-section qbo-sidebar-footer">
        {{-- Customize Button --}}
        <button class="qbo-sidebar-item" id="qboCustomizeBtn" aria-label="Customize" type="button">
            <div class="qbo-sidebar-item-icon">
                <svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="m20.036 15.036-9.19-.01a2.98 2.98 0 0 0-5.62-.01h-1.19a1 1 0 1 0 0 2h1.18a2.981 2.981 0 0 0 5.64.01l9.18.01a1 1 0 0 0 0-2Zm-11.28 1.68-.04.04a.972.972 0 0 1-1.36 0l-.04-.04a.972.972 0 0 1 0-1.36l.04-.04a.972.972 0 0 1 1.36 0l.04.04a.972.972 0 0 1 0 1.36ZM20.046 7.056h-1.18a2.992 2.992 0 0 0-2.81-2.02h-.01a2.973 2.973 0 0 0-2.82 2.01l-9.18-.01a1 1 0 1 0 0 2l9.19.01c.142.418.378.798.69 1.11a2.938 2.938 0 0 0 2.12.88 3 3 0 0 0 2.81-1.98h1.19a1 1 0 1 0 0-2Zm-3.29 1.66-.04.04a.972.972 0 0 1-1.36 0l-.04-.04a.972.972 0 0 1 0-1.36l.04-.04a.972.972 0 0 1 1.36 0l.04.04a.972.972 0 0 1 0 1.36Z" fill="currentColor"></path>
                </svg>
            </div>
            <span class="qbo-sidebar-item-label">Customize</span>
        </button>
    </section>
</nav>

{{-- Customize Modal --}}
<div class="modal fade" id="qboCustomizeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Customize your app menus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Drag to reorder, pin items to show in the sidebar, or toggle visibility.</p>
                <div class="qbo-customize-list" id="qboCustomizeList">
                    {{-- Items will be loaded dynamically --}}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link" id="qboResetDefault">Reset to default</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="qboSaveCustomize">Save</button>
            </div>
        </div>
    </div>
</div>

{{-- Customize Bookmarks Modal - QBO Style --}}
<div class="qbo-bookmarks-modal-overlay" id="qboBookmarksModalOverlay" style="display: none;">
    <div class="qbo-bookmarks-modal" id="qboBookmarksModal">
        <button type="button" class="qbo-bookmarks-modal-close" id="qboBookmarksModalClose">
            <svg width="28px" height="28px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="m13.432 11.984 5.3-5.285a1 1 0 1 0-1.412-1.416l-5.3 5.285-5.285-5.3A1 1 0 1 0 5.319 6.68l5.285 5.3L5.3 17.265a1 1 0 1 0 1.412 1.416l5.3-5.285L17.3 18.7a1 1 0 1 0 1.416-1.412l-5.284-5.304Z" fill="currentColor"></path>
            </svg>
        </button>
        <div class="qbo-bookmarks-modal-header">
            <span>Customize your bookmarks</span>
        </div>
        <div class="qbo-bookmarks-modal-body">
            <p class="qbo-bookmarks-modal-instructions">Select pages to bookmark, and drag and reorder them to fit the way you work:</p>
            <div class="qbo-bookmarks-search-wrapper">
                <div class="qbo-bookmarks-search-icon">
                    <svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="#6b6c72">
                        <path d="m21.694 20.307-6.239-6.258A7.495 7.495 0 0 0 9.515 2H9.5a7.5 7.5 0 1 0 4.535 13.465l6.24 6.259a1.001 1.001 0 0 0 1.416-1.413l.003-.004ZM5.609 13.38A5.5 5.5 0 0 1 9.5 4h.009a5.5 5.5 0 1 1-3.9 9.384v-.004Z" fill="currentColor"></path>
                    </svg>
                </div>
                <input type="text" class="qbo-bookmarks-search-input" id="qboBookmarksSearchInput" placeholder="Search pages to bookmark" autocomplete="off">
                <button type="button" class="qbo-bookmarks-search-clear" id="qboBookmarksSearchClear" style="display: none;">
                    <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="m13.432 11.984 5.3-5.285a1 1 0 1 0-1.412-1.416l-5.3 5.285-5.285-5.3A1 1 0 1 0 5.319 6.68l5.285 5.3L5.3 17.265a1 1 0 1 0 1.412 1.416l5.3-5.285L17.3 18.7a1 1 0 1 0 1.416-1.412l-5.284-5.304Z" fill="currentColor"></path>
                    </svg>
                </button>
            </div>
            <button type="button" class="qbo-bookmarks-reset-btn" id="qboBookmarksResetBtn">
                <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20.012 4.012a1 1 0 0 0-1 1v1.374A8.918 8.918 0 0 0 12.013 3H12a9.012 9.012 0 0 0-9 8.986 1 1 0 1 0 2 0A7.009 7.009 0 0 1 12 5h.009a6.95 6.95 0 0 1 5.737 3.009h-1.74a1 1 0 1 0 0 2l4 .006a1 1 0 0 0 1-1l.006-4a1 1 0 0 0-1-1.003ZM20 11.012a1 1 0 0 0-1 1A7.008 7.008 0 0 1 12 19h-.012a6.974 6.974 0 0 1-5.735-3.01H8a1 1 0 1 0 0-2l-4-.005a1 1 0 0 0-1 1l-.006 4a1 1 0 0 0 1 1 1 1 0 0 0 1-1v-1.373A8.954 8.954 0 0 0 11.986 21H12a9.01 9.01 0 0 0 9-8.986 1 1 0 0 0-1-1.002Z" fill="currentColor"></path>
                </svg>
                Reset to last arrangement
            </button>
            <div class="qbo-bookmarks-checkbox-wrapper" id="qboBookmarksCheckboxWrapper">
                {{-- Selected bookmarks with drag handles --}}
                <div class="qbo-bookmarks-selected" id="qboBookmarksSelected"></div>
                {{-- Unselected pages --}}
                <div class="qbo-bookmarks-not-selected" id="qboBookmarksNotSelected"></div>
            </div>
        </div>
        <div class="qbo-bookmarks-modal-footer">
            <button type="button" class="qbo-bookmarks-cancel-btn" id="qboBookmarksCancelBtn">Cancel</button>
            <button type="button" class="qbo-bookmarks-save-btn" id="qboBookmarksSaveBtn">Save</button>
        </div>
    </div>
</div>

{{-- Sidebar Overlay for Mobile --}}
<div class="qbo-sidebar-overlay" id="qboSidebarOverlay"></div>

{{-- SortableJS for drag and drop --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
// ==================== APP MENU CUSTOMIZE - QBO Style ====================

document.addEventListener('DOMContentLoaded', function() {
    const customizeBtn = document.getElementById('qboCustomizeBtn');
    const overlay = document.getElementById('qboSidebarOverlay');
    let sortableInstance = null;

    // Open customize modal
    if (customizeBtn) {
        customizeBtn.addEventListener('click', function() {
            loadCustomizeItems();
            const modal = new bootstrap.Modal(document.getElementById('qboCustomizeModal'));
            modal.show();
        });
    }

    // Load customize items from server
    function loadCustomizeItems() {
        const list = document.getElementById('qboCustomizeList');
        if (!list) return;
        
        // Show loading state
        list.innerHTML = '<div class="qbo-loading-spinner"></div>';
        list.classList.add('loading');
        
        fetch('{{ route("qbo.menu.config") }}')
            .then(response => response.json())
            .then(data => {
                list.classList.remove('loading');
                renderCustomizeItems(data.items);
            })
            .catch(error => {
                console.error('Error loading menu config:', error);
                list.classList.remove('loading');
                list.innerHTML = '<div class="qbo-customize-empty"><p>Error loading menu items</p></div>';
            });
    }

    // Render customize items - QBO Style
    function renderCustomizeItems(items) {
        const list = document.getElementById('qboCustomizeList');
        if (!list) return;
        
        list.innerHTML = '';

        // Icon map for QBO style icons from Intuit CDN
        const iconMap = {
            'accounting': 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/accounting/1/0/0/accounting.svg',
            'expenses': 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/expenses/1/0/0/expenses.svg',
            'sales': 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/sales-payments/1/0/0/sales-payments.svg',
            'customers': 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/customers/1/0/0/customers.svg',
            'team': 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/team/1/0/0/team.svg',
            'time': 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/time/1/0/0/time.svg',
            'inventory': 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/inventory/1/0/0/inventory.svg',
            'sales_tax': 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/sales-tax/1/0/0/sales-tax.svg',
            'business_tax': 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/business-tax/1/0/0/business-tax.svg',
            'lending': 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/lending/1/0/0/lending.svg'
        };

        items.forEach((item, index) => {
            const div = document.createElement('div');
            div.className = 'qbo-customize-item' + (item.is_visible === false ? ' item-hidden' : '');
            div.dataset.key = item.key;
            div.dataset.position = index;
            div.dataset.visible = item.is_visible !== false ? '1' : '0';
            
            // Use icon_url from config, or fall back to iconMap
            const iconUrl = item.icon_url || iconMap[item.key] || '';
            const isPinned = item.type === 'pinned';
            
            // Visibility toggle button commented out per user request:
            // <button class="qbo-visibility-toggle-btn" data-key="${item.key}" title="..." type="button">...</button>
            
            div.innerHTML = `
                <div class="qbo-customize-drag">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <circle cx="8" cy="5" r="2"/>
                        <circle cx="16" cy="5" r="2"/>
                        <circle cx="8" cy="12" r="2"/>
                        <circle cx="16" cy="12" r="2"/>
                        <circle cx="8" cy="19" r="2"/>
                        <circle cx="16" cy="19" r="2"/>
                    </svg>
                </div>
                <button class="qbo-pin-btn ${isPinned ? 'pinned' : ''}" data-key="${item.key}" title="${isPinned ? 'Unpin from sidebar' : 'Pin to sidebar'}" type="button">
                    <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="currentColor" focusable="false" aria-hidden="true" class="IconControl-iconMarginRight-ccf7960"><path fill-rule="evenodd" clip-rule="evenodd" d="M17 2a1 1 0 1 1 0 2h-1v3.333c1.818.898 3 2.76 3 4.83V13a1 1 0 0 1-1 1h-4.563L13 21c-.016.25-.135.548-.322.725A.992.992 0 0 1 12 22c-.25 0-.49-.1-.678-.275-.187-.177-.306-.476-.322-.725l-.438-7H6a1 1 0 0 1-1-1l.004-1.055A5.388 5.388 0 0 1 8 7.333V4H7a1 1 0 0 1 0-2h10Z" fill="currentColor"></path></svg>
                </button>
                ${iconUrl ? `<img src="${iconUrl}" class="qbo-customize-icon-img" alt="${item.label}">` : `<div class="qbo-customize-icon" style="background: ${item.color || '#666'}"><i class="${item.icon || 'ti ti-circle'}"></i></div>`}
                <div class="qbo-customize-label">${item.label}</div>
            `;
            list.appendChild(div);
        });

        // Attach event handlers
        attachCustomizeHandlers();

        // Initialize sortable if available
        console.log('Sortable available:', typeof Sortable !== 'undefined');
        if (typeof Sortable !== 'undefined') {
            if (sortableInstance) {
                sortableInstance.destroy();
            }
            sortableInstance = new Sortable(list, {
                animation: 150,
                handle: '.qbo-customize-drag',
                ghostClass: 'qbo-sortable-ghost',
                dragClass: 'qbo-sortable-drag'
            });
            console.log('Sortable initialized successfully');
        } else {
            console.warn('Sortable.js not loaded - drag and drop will not work');
        }
    }

    // Attach event handlers to customize items
    function attachCustomizeHandlers() {
        // Visibility toggle buttons
        document.querySelectorAll('.qbo-visibility-toggle-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const item = this.closest('.qbo-customize-item');
                const isVisible = item.dataset.visible === '1';
                
                // Toggle visibility
                item.dataset.visible = isVisible ? '0' : '1';
                item.classList.toggle('item-hidden');
                
                // Update button title
                this.title = isVisible ? 'Show' : 'Hide';
            });
        });

        // Pin buttons
        document.querySelectorAll('.qbo-pin-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                this.classList.toggle('pinned');
                const isPinned = this.classList.contains('pinned');
                this.title = isPinned ? 'Unpin from sidebar' : 'Pin to sidebar';
            });
        });
    }

    // Reset to default
    const resetBtn = document.getElementById('qboResetDefault');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            if (!confirm('Reset to default menu configuration?')) return;
            
            fetch('{{ route("qbo.menu.reset") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                renderCustomizeItems(data.items);
                if (typeof toastr !== 'undefined') {
                    toastr.success('Menu reset to default');
                }
            })
            .catch(error => {
                console.error('Error resetting menu:', error);
                if (typeof toastr !== 'undefined') {
                    toastr.error('Error resetting menu');
                }
            });
        });
    }

    // Save customization
    const saveBtn = document.getElementById('qboSaveCustomize');
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            const items = [];
            const list = document.getElementById('qboCustomizeList');
            
            list.querySelectorAll('.qbo-customize-item').forEach((el, index) => {
                const isPinned = el.querySelector('.qbo-pin-btn').classList.contains('pinned');
                items.push({
                    key: el.dataset.key,
                    position: index,
                    is_visible: el.dataset.visible === '1',
                    type: isPinned ? 'pinned' : 'menu'
                });
            });

            // Show loading
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';

            fetch('{{ route("qbo.menu.config.save") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ items: items })
            })
            .then(response => response.json())
            .then(data => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('qboCustomizeModal'));
                modal.hide();
                
                if (typeof toastr !== 'undefined') {
                    toastr.success('Menu customization saved');
                }
                
                // Reload page to reflect changes
                setTimeout(() => location.reload(), 500);
            })
            .catch(error => {
                console.error('Error saving menu:', error);
                if (typeof toastr !== 'undefined') {
                    toastr.error('Error saving menu configuration');
                }
            })
            .finally(() => {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save';
            });
        });
    }

    // Mobile overlay click
    if (overlay) {
        overlay.addEventListener('click', function() {
            document.getElementById('qboSidebar').classList.remove('mobile-open');
            overlay.classList.remove('show');
        });
    }

    // ==================== PINNED ITEMS FLYOUT ====================
    // Load pinned items on page load
    loadPinnedItems();

    // Load pinned items from server
    function loadPinnedItems() {
        const pinnedContainer = document.getElementById('qboPinnedItems');
        const emptyContainer = document.getElementById('qboPinnedEmpty');
        
        if (!pinnedContainer || !emptyContainer) return;

        // Icon map for QBO style icons
        const iconMap = {
            'accounting': 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/accounting/1/0/0/accounting.svg',
            'expenses': 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/expenses/1/0/0/expenses.svg',
            'sales': 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/sales-payments/1/0/0/sales-payments.svg',
            'customers': 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/customers/1/0/0/customers.svg',
            'team': 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/team/1/0/0/team.svg',
            'time': 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/time/1/0/0/time.svg',
            'inventory': 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/inventory/1/0/0/inventory.svg',
            'sales_tax': 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/sales-tax/1/0/0/sales-tax.svg',
            'business_tax': 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/business-tax/1/0/0/business-tax.svg',
            'lending': 'https://asset-service-cdn-prdasset-prd.a.intuit.com/navigationfusionga/lending/1/0/0/lending.svg'
        };

        fetch('{{ route("qbo.menu.pinned") }}')
            .then(response => response.json())
            .then(data => {
                const pinnedItems = data.pinned || [];
                
                if (pinnedItems.length === 0) {
                    emptyContainer.style.display = 'block';
                    pinnedContainer.style.display = 'none';
                    return;
                }

                emptyContainer.style.display = 'none';
                pinnedContainer.style.display = 'block';
                pinnedContainer.innerHTML = '';

                pinnedItems.forEach(item => {
                    const iconUrl = item.icon_url || iconMap[item.key] || '';
                    const routeUrl = item.route ? ('{{ url("") }}/' + item.route) : '#';
                    
                    const itemEl = document.createElement('a');
                    itemEl.href = routeUrl;
                    itemEl.className = 'qbo-pinned-item';
                    itemEl.innerHTML = `
                        <div class="qbo-pinned-item-icon">
                            ${iconUrl ? `<img src="${iconUrl}" alt="">` : ''}
                        </div>
                        <span class="qbo-pinned-item-label">${item.label}</span>
                    `;
                    pinnedContainer.appendChild(itemEl);
                });
            })
            .catch(error => {
                console.error('Error loading pinned items:', error);
            });
    }

    // ==================== BOOKMARKS FUNCTIONALITY ====================
    let bookmarksData = [];
    let allPages = [];
    let selectedBookmarks = [];
    let bookmarksSortable = null;

    // Load bookmarks on page load
    loadBookmarks();

    // Load bookmarks from server
    function loadBookmarks() {
        fetch('{{ route("qbo.menu.bookmarks") }}')
            .then(response => response.json())
            .then(data => {
                bookmarksData = data.bookmarks || [];
                renderBookmarksFlyout();
            })
            .catch(error => console.error('Error loading bookmarks:', error));
    }

    // Render bookmarks in flyout
    function renderBookmarksFlyout() {
        const emptyEl = document.getElementById('qboBookmarksEmpty');
        const itemsEl = document.getElementById('qboBookmarksItems');
        
        if (bookmarksData.length === 0) {
            emptyEl.style.display = 'block';
            itemsEl.style.display = 'none';
        } else {
            emptyEl.style.display = 'none';
            itemsEl.style.display = 'block';
            itemsEl.innerHTML = '';
            
            bookmarksData.forEach(bookmark => {
                const item = document.createElement('div');
                item.className = 'qbo-bookmark-item';
                item.dataset.id = bookmark.id;
                item.innerHTML = `
                    <div class="qbo-bookmark-icon">
                        <svg width="16px" height="16px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18 21.28c-.348 0-.69-.093-.992-.267L12 18.152l-5.008 2.861A2 2 0 0 1 4 19.277V5a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v14.277a2.006 2.006 0 0 1-2 2v.003Z" fill="currentColor"></path>
                        </svg>
                    </div>
                    <a href="${bookmark.route ? '{{ url('/') }}/' + bookmark.route : '#'}" class="qbo-bookmark-label">${bookmark.label}</a>
                    <button type="button" class="qbo-bookmark-edit-icon" data-id="${bookmark.id}" data-label="${bookmark.label}" title="Edit bookmark">
<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" color="currentColor" width="20px" height="20px" focusable="false" aria-hidden="true" class=""><path fill="currentColor" d="M20.912 4.527 19.5 3.111a2.98 2.98 0 0 0-2.12-.88 2.98 2.98 0 0 0-2.118.873L3.927 14.401a1 1 0 0 0-.227.374c-.01.03-.026.056-.034.085L2.24 20.52a1 1 0 0 0 1.211 1.215L9.11 20.33c.033-.008.062-.026.094-.037a1 1 0 0 0 .367-.223l11.331-11.3a3 3 0 0 0 .01-4.243M5.171 17.067l1.662 1.666.081.08-2.328.579zm3.7.878-1.412-1.416-1.412-1.418 8.5-8.472 1.412 1.416 1.412 1.416zM19.494 7.353l-.709.706-1.412-1.416-1.412-1.417.708-.706a1 1 0 0 1 1.412.003L19.5 5.94a1 1 0 0 1-.006 1.414"></path></svg>
                    </button>
                `;
                itemsEl.appendChild(item);
            });

            // Add click handlers for edit buttons
            itemsEl.querySelectorAll('.qbo-bookmark-edit-icon').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    showEditForm(this.dataset.id, this.dataset.label);
                });
            });
        }
    }

    // Show inline edit form
    function showEditForm(id, label) {
        const editForm = document.getElementById('qboBookmarkEditForm');
        const editInput = document.getElementById('qboBookmarkEditInput');
        const editId = document.getElementById('qboBookmarkEditId');
        
        editInput.value = label;
        editId.value = id;
        editForm.style.display = 'block';
        editInput.focus();
        editInput.select();
    }

    // Hide edit form
    function hideEditForm() {
        const editForm = document.getElementById('qboBookmarkEditForm');
        editForm.style.display = 'none';
    }

    // Save bookmark edit
    document.getElementById('qboBookmarkSaveBtn')?.addEventListener('click', function() {
        const id = document.getElementById('qboBookmarkEditId').value;
        const label = document.getElementById('qboBookmarkEditInput').value;
        
        fetch(`{{ url('/qbo-menu/bookmarks') }}/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ label: label })
        })
        .then(response => response.json())
        .then(data => {
            hideEditForm();
            loadBookmarks();
            if (typeof toastr !== 'undefined') {
                toastr.success('Bookmark updated');
            }
        })
        .catch(error => console.error('Error updating bookmark:', error));
    });

    // Remove bookmark
    document.getElementById('qboBookmarkRemoveBtn')?.addEventListener('click', function() {
        const id = document.getElementById('qboBookmarkEditId').value;
        
        fetch(`{{ url('/qbo-menu/bookmarks') }}/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            hideEditForm();
            loadBookmarks();
            if (typeof toastr !== 'undefined') {
                toastr.success('Bookmark removed');
            }
        })
        .catch(error => console.error('Error removing bookmark:', error));
    });

    // ==================== CUSTOMIZE BOOKMARKS MODAL ====================
    const bookmarksModalOverlay = document.getElementById('qboBookmarksModalOverlay');
    const openCustomizeBtn = document.getElementById('qboOpenCustomizeBookmarks');
    const closeModalBtn = document.getElementById('qboBookmarksModalClose');
    const cancelBtn = document.getElementById('qboBookmarksCancelBtn');
    const saveBookmarksBtn = document.getElementById('qboBookmarksSaveBtn');
    const searchInput = document.getElementById('qboBookmarksSearchInput');
    const searchClear = document.getElementById('qboBookmarksSearchClear');
    const resetBookmarksBtn = document.getElementById('qboBookmarksResetBtn');

    // Open customize bookmarks modal
    openCustomizeBtn?.addEventListener('click', function() {
        loadAvailablePages();
        bookmarksModalOverlay.style.display = 'flex';
    });

    // Close modal
    closeModalBtn?.addEventListener('click', closeBookmarksModal);
    cancelBtn?.addEventListener('click', closeBookmarksModal);
    bookmarksModalOverlay?.addEventListener('click', function(e) {
        if (e.target === bookmarksModalOverlay) {
            closeBookmarksModal();
        }
    });

    function closeBookmarksModal() {
        bookmarksModalOverlay.style.display = 'none';
        searchInput.value = '';
        searchClear.style.display = 'none';
    }

    // Load available pages
    function loadAvailablePages() {
        fetch('{{ route("qbo.menu.available.pages") }}')
            .then(response => response.json())
            .then(data => {
                allPages = data.pages || [];
                // Get current bookmarks
                fetch('{{ route("qbo.menu.bookmarks") }}')
                    .then(response => response.json())
                    .then(bData => {
                        selectedBookmarks = (bData.bookmarks || []).map(b => ({
                            ...b,
                            key: b.key,
                            label: b.label,
                            route: b.route
                        }));
                        renderBookmarksModal();
                    });
            })
            .catch(error => console.error('Error loading available pages:', error));
    }

    // Render bookmarks modal
    function renderBookmarksModal(filterText = '') {
        const selectedContainer = document.getElementById('qboBookmarksSelected');
        const notSelectedContainer = document.getElementById('qboBookmarksNotSelected');
        
        selectedContainer.innerHTML = '';
        notSelectedContainer.innerHTML = '';
        
        const selectedKeys = selectedBookmarks.map(b => b.key);
        const filter = filterText.toLowerCase();

        // Render selected bookmarks with drag handles
        selectedBookmarks.forEach(bookmark => {
            if (filter && !bookmark.label.toLowerCase().includes(filter)) return;
            
            const item = createBookmarkCheckbox(bookmark, true);
            selectedContainer.appendChild(item);
        });

        // Render unselected pages
        allPages.forEach(page => {
            if (selectedKeys.includes(page.key)) return;
            if (filter && !page.label.toLowerCase().includes(filter)) return;
            
            const item = createBookmarkCheckbox(page, false);
            notSelectedContainer.appendChild(item);
        });

        // Initialize sortable for selected items
        if (typeof Sortable !== 'undefined' && selectedContainer.children.length > 0) {
            if (bookmarksSortable) {
                bookmarksSortable.destroy();
            }
            bookmarksSortable = new Sortable(selectedContainer, {
                animation: 150,
                handle: '.qbo-bookmark-drag-handle',
                ghostClass: 'qbo-sortable-ghost',
                onEnd: function() {
                    // Update order in selectedBookmarks array
                    const newOrder = [];
                    selectedContainer.querySelectorAll('.qbo-bookmark-modal-item').forEach(item => {
                        const key = item.dataset.key;
                        const bookmark = selectedBookmarks.find(b => b.key === key);
                        if (bookmark) newOrder.push(bookmark);
                    });
                    selectedBookmarks = newOrder;
                }
            });
        }
    }

    // Create bookmark checkbox item
    function createBookmarkCheckbox(page, isSelected) {
        const div = document.createElement('label');
        div.className = 'qbo-bookmark-modal-item' + (isSelected ? ' selected' : '');
        div.dataset.key = page.key;
        
        if (isSelected) {
            div.innerHTML = `
                <span class="qbo-bookmark-drag-handle">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="#6b6c72">
                        <path d="M8 6a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm0 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm0 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm8-16a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm0 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm0 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4z"/>
                    </svg>
                </span>
                <input type="checkbox" class="qbo-bookmark-checkbox" data-key="${page.key}" checked>
                <span class="qbo-bookmark-checkbox-label">${page.label}</span>
            `;
        } else {
            div.innerHTML = `
                <input type="checkbox" class="qbo-bookmark-checkbox" data-key="${page.key}">
                <span class="qbo-bookmark-checkbox-label">${page.label}</span>
            `;
        }

        // Add change handler
        const checkbox = div.querySelector('.qbo-bookmark-checkbox');
        checkbox.addEventListener('change', function() {
            const key = this.dataset.key;
            if (this.checked) {
                // Add to selected
                const pageData = allPages.find(p => p.key === key);
                if (pageData && !selectedBookmarks.find(b => b.key === key)) {
                    selectedBookmarks.push(pageData);
                }
            } else {
                // Remove from selected
                selectedBookmarks = selectedBookmarks.filter(b => b.key !== key);
            }
            renderBookmarksModal(searchInput.value);
        });

        return div;
    }

    // Search functionality
    searchInput?.addEventListener('input', function() {
        const val = this.value;
        searchClear.style.display = val ? 'block' : 'none';
        renderBookmarksModal(val);
    });

    searchClear?.addEventListener('click', function() {
        searchInput.value = '';
        searchClear.style.display = 'none';
        renderBookmarksModal();
    });

    // Reset bookmarks
    resetBookmarksBtn?.addEventListener('click', function() {
        selectedBookmarks = [];
        renderBookmarksModal(searchInput.value);
    });

    // Save bookmarks
    saveBookmarksBtn?.addEventListener('click', function() {
        const bookmarks = selectedBookmarks.map((b, i) => ({
            key: b.key,
            label: b.label,
            route: b.route,
            position: i
        }));

        fetch('{{ route("qbo.menu.bookmarks.save") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ bookmarks: bookmarks })
        })
        .then(response => response.json())
        .then(data => {
            closeBookmarksModal();
            loadBookmarks();
            if (typeof toastr !== 'undefined') {
                toastr.success('Bookmarks saved');
            }
        })
        .catch(error => console.error('Error saving bookmarks:', error));
    });

    // Bookmark current page button
    document.getElementById('qboBookmarkCurrentPage')?.addEventListener('click', function() {
        loadAvailablePages();
        bookmarksModalOverlay.style.display = 'flex';
    });

    // ==================== REPORTS FLYOUT BOOKMARK BUTTONS ====================
    // Handle clicking bookmark buttons in Reports flyout
    document.querySelectorAll('.qbo-reports-bookmark-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const key = this.dataset.key;
            const label = this.dataset.label;
            
            // Get the actual href from the sibling link element
            const parentItem = this.closest('.qbo-reports-item');
            const linkEl = parentItem.querySelector('.qbo-reports-link');
            let route = '';
            
            if (linkEl && linkEl.href && linkEl.href !== '#' && !linkEl.href.endsWith('#')) {
                // Extract just the path from the full URL (remove domain)
                const url = new URL(linkEl.href);
                route = url.pathname.replace(/^\//, ''); // Remove leading slash
            }
            
            // Check if already bookmarked
            const isBookmarked = this.classList.contains('bookmarked');
            
            if (isBookmarked) {
                // Remove from bookmarks - find by key and delete
                const bookmark = bookmarksData.find(b => b.key === key);
                if (bookmark) {
                    fetch(`{{ url('/qbo-menu/bookmarks') }}/${bookmark.id}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        this.classList.remove('bookmarked');
                        loadBookmarks();
                        updateReportsBookmarkStates();
                        if (typeof toastr !== 'undefined') {
                            toastr.success('Bookmark removed');
                        }
                    })
                    .catch(error => console.error('Error removing bookmark:', error));
                }
            } else {
                // Add to bookmarks
                const newBookmarks = [...bookmarksData.map((b, i) => ({
                    key: b.key,
                    label: b.label,
                    route: b.route,
                    position: i
                })), {
                    key: key,
                    label: label,
                    route: route,
                    position: bookmarksData.length
                }];
                
                fetch('{{ route("qbo.menu.bookmarks.save") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ bookmarks: newBookmarks })
                })
                .then(response => response.json())
                .then(data => {
                    this.classList.add('bookmarked');
                    loadBookmarks();
                    if (typeof toastr !== 'undefined') {
                        toastr.success('Page bookmarked');
                    }
                })
                .catch(error => console.error('Error adding bookmark:', error));
            }
        });
    });

    // Update bookmark button states based on current bookmarks
    function updateReportsBookmarkStates() {
        const bookmarkedKeys = bookmarksData.map(b => b.key);
        document.querySelectorAll('.qbo-reports-bookmark-btn').forEach(btn => {
            const key = btn.dataset.key;
            if (bookmarkedKeys.includes(key)) {
                btn.classList.add('bookmarked');
            } else {
                btn.classList.remove('bookmarked');
            }
        });
    }

    // Call update states after loading bookmarks
    const originalLoadBookmarks = loadBookmarks;
    loadBookmarks = function() {
        fetch('{{ route("qbo.menu.bookmarks") }}')
            .then(response => response.json())
            .then(data => {
                bookmarksData = data.bookmarks || [];
                renderBookmarksFlyout();
                updateReportsBookmarkStates();
                updateSubmenuBookmarkStates();
            })
            .catch(error => console.error('Error loading bookmarks:', error));
    };

    // ==================== ALL APPS SUBMENU BOOKMARK BUTTONS ====================
    // Handle clicking bookmark buttons in All Apps submenu
    document.querySelectorAll('.qbo-submenu-bookmark-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const key = this.dataset.key;
            const label = this.dataset.label;
            
            // Get the actual href from the sibling link element
            const parentItem = this.closest('.qbo-submenu-item');
            const linkEl = parentItem.querySelector('.qbo-submenu-link');
            let route = '';
            
            if (linkEl && linkEl.href && linkEl.href !== '#' && !linkEl.href.endsWith('#')) {
                // Extract just the path from the full URL (remove domain)
                const url = new URL(linkEl.href);
                route = url.pathname.replace(/^\//, ''); // Remove leading slash
            }
            
            // Check if already bookmarked
            const isBookmarked = this.classList.contains('bookmarked');
            
            if (isBookmarked) {
                // Remove from bookmarks - find by key and delete
                const bookmark = bookmarksData.find(b => b.key === key);
                if (bookmark) {
                    fetch(`{{ url('/qbo-menu/bookmarks') }}/${bookmark.id}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        this.classList.remove('bookmarked');
                        loadBookmarks();
                        if (typeof toastr !== 'undefined') {
                            toastr.success('Bookmark removed');
                        }
                    })
                    .catch(error => console.error('Error removing bookmark:', error));
                }
            } else {
                // Add to bookmarks
                const newBookmarks = [...bookmarksData.map((b, i) => ({
                    key: b.key,
                    label: b.label,
                    route: b.route,
                    position: i
                })), {
                    key: key,
                    label: label,
                    route: route,
                    position: bookmarksData.length
                }];
                
                fetch('{{ route("qbo.menu.bookmarks.save") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ bookmarks: newBookmarks })
                })
                .then(response => response.json())
                .then(data => {
                    this.classList.add('bookmarked');
                    loadBookmarks();
                    if (typeof toastr !== 'undefined') {
                        toastr.success('Page bookmarked');
                    }
                })
                .catch(error => console.error('Error adding bookmark:', error));
            }
        });
    });

    // Update submenu bookmark button states based on current bookmarks
    function updateSubmenuBookmarkStates() {
        const bookmarkedKeys = bookmarksData.map(b => b.key);
        document.querySelectorAll('.qbo-submenu-bookmark-btn').forEach(btn => {
            const key = btn.dataset.key;
            if (bookmarkedKeys.includes(key)) {
                btn.classList.add('bookmarked');
            } else {
                btn.classList.remove('bookmarked');
            }
    });
    }
    
    // Initial load
    loadBookmarks();

    // ==================== ALL APPS SUBMENU POSITIONING ====================
    // Position fixed submenus when hovering over parent items
    document.querySelectorAll('.qbo-allapps-parent').forEach(parent => {
        const submenu = parent.querySelector('.qbo-allapps-submenu');
        if (!submenu) return;
        
        parent.addEventListener('mouseenter', function() {
            const parentRect = parent.getBoundingClientRect();
            const flyout = document.getElementById('qboAllAppsFlyout');
            const flyoutRect = flyout.getBoundingClientRect();
            
            // Position submenu to the right of the parent flyout
            submenu.style.left = (flyoutRect.right + 4) + 'px';
            
            // Position vertically - align with the parent item
            let top = parentRect.top - 8;
            
            // Make sure it doesn't go off screen
            const submenuHeight = submenu.offsetHeight || 200;
            const windowHeight = window.innerHeight;
            
            if (top + submenuHeight > windowHeight - 20) {
                top = windowHeight - submenuHeight - 20;
            }
            if (top < 60) {
                top = 60;
            }
            
            submenu.style.top = top + 'px';
        });
    });
});
</script>
