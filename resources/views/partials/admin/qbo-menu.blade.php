@php
    $setting = \App\Models\Utility::settings();
    $company_logo = $setting['company_logo'] ?? 'logo-dark.png';
@endphp
{{-- QBO-Style Left Sidebar Menu - Exact Replica --}}
<nav class="qbo-sidebar" id="qboSidebar" aria-label="Side">
    {{-- Create & Bookmarks Section --}}
    <section class="qbo-sidebar-section">
        {{-- Create Button --}}
        <button class="qbo-sidebar-item qbo-create-btn" id="qboCreateBtn" aria-label="Create" type="button">
            <div class="qbo-sidebar-item-icon">
                <svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 11h-2V9a1 1 0 0 0-2 0v2H9a1 1 0 0 0 0 2h2v2a1 1 0 0 0 2 0v-2h2a1 1 0 0 0 0-2Z" fill="currentColor"></path>
                    <path d="M12.015 2H12a10 10 0 1 0-.015 20H12a10 10 0 0 0 .015-20ZM12 20h-.012A8 8 0 0 1 12 4h.012A8 8 0 0 1 12 20Z" fill="currentColor"></path>
                </svg>
            </div>
            <span class="qbo-sidebar-item-label">Create</span>
        </button>

        {{-- Bookmarks Button --}}
        <button class="qbo-sidebar-item" id="qboBookmarksBtn" aria-label="Bookmarks" type="button">
            <div class="qbo-sidebar-item-icon">
                <svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 21.28c-.348 0-.69-.093-.992-.267L12 18.152l-5.008 2.861A2 2 0 0 1 4 19.277V5a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v14.277a2.006 2.006 0 0 1-2 2v.003ZM7 4a1 1 0 0 0-1 1v14.282l5.008-2.867a2.011 2.011 0 0 1 1.984 0L18 19.277V5a1 1 0 0 0-1-1H7Z" fill="currentColor"></path>
                </svg>
            </div>
            <span class="qbo-sidebar-item-label">Bookmarks</span>
        </button>
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

        {{-- Reports --}}
        <a href="{{ route('allReports') }}" class="qbo-sidebar-item" aria-label="Reports">
            <div class="qbo-sidebar-item-icon">
                <svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="m20.989 20.013-16-.023a1 1 0 0 1-1-1l.023-16a1 1 0 0 0-1-1 1 1 0 0 0-1 1l-.023 16a3 3 0 0 0 3 3l16 .023a1 1 0 1 0 0-2Z" fill="currentColor"></path>
                    <path d="M5.46 18.838a.999.999 0 0 0 1.379-.316l4.236-6.757L13.2 14.6a1.019 1.019 0 0 0 .867.4 1 1 0 0 0 .806-.511l4.172-7.483a2.017 2.017 0 1 0-1.745-.977l-3.424 6.14-2.076-2.77a1 1 0 0 0-1.648.068L5.144 17.46a1 1 0 0 0 .316 1.378Z" fill="currentColor"></path>
                </svg>
            </div>
            <span class="qbo-sidebar-item-label">Reports</span>
        </a>

        {{-- All Apps --}}
        <a href="#" class="qbo-sidebar-item" id="qboAllAppsBtn" aria-label="All apps">
            <div class="qbo-sidebar-item-icon">
                <svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="m10.636 4.565-.07.071a1.929 1.929 0 0 0 0 2.728l.07.07a1.929 1.929 0 0 0 2.728 0l.07-.07a1.929 1.929 0 0 0 0-2.728l-.07-.07a1.929 1.929 0 0 0-2.728 0ZM4.636 4.565l-.07.071a1.929 1.929 0 0 0 0 2.728l.07.07a1.929 1.929 0 0 0 2.728 0l.071-.07a1.929 1.929 0 0 0 0-2.728l-.07-.07a1.929 1.929 0 0 0-2.729 0ZM16.636 4.565l-.071.071a1.929 1.929 0 0 0 0 2.728l.07.07a1.929 1.929 0 0 0 2.728 0l.071-.07a1.929 1.929 0 0 0 0-2.728l-.07-.07a1.929 1.929 0 0 0-2.729 0ZM10.636 10.565l-.071.071a1.929 1.929 0 0 0 0 2.728l.07.07a1.929 1.929 0 0 0 2.729 0l.07-.07a1.929 1.929 0 0 0 0-2.728l-.07-.07a1.929 1.929 0 0 0-2.729 0ZM4.636 10.565l-.07.071a1.929 1.929 0 0 0 0 2.728l.07.07a1.929 1.929 0 0 0 2.728 0l.07-.07a1.929 1.929 0 0 0 0-2.728l-.07-.07a1.929 1.929 0 0 0-2.728 0ZM16.636 10.565l-.07.071a1.929 1.929 0 0 0 0 2.728l.07.07a1.929 1.929 0 0 0 2.728 0l.07-.07a1.929 1.929 0 0 0 0-2.728l-.07-.07a1.929 1.929 0 0 0-2.728 0ZM10.636 16.565l-.07.071a1.929 1.929 0 0 0 0 2.728l.07.07a1.929 1.929 0 0 0 2.728 0l.07-.07a1.929 1.929 0 0 0 0-2.728l-.07-.07a1.929 1.929 0 0 0-2.728 0ZM4.636 16.565l-.071.071a1.929 1.929 0 0 0 0 2.728l.07.07a1.929 1.929 0 0 0 2.729 0l.07-.07a1.929 1.929 0 0 0 0-2.728l-.07-.07a1.929 1.929 0 0 0-2.728 0ZM16.636 16.565l-.07.071a1.929 1.929 0 0 0 0 2.728l.07.07a1.929 1.929 0 0 0 2.728 0l.07-.07a1.929 1.929 0 0 0 0-2.728l-.07-.07a1.929 1.929 0 0 0-2.728 0Z" fill="currentColor"></path>
                </svg>
            </div>
            <span class="qbo-sidebar-item-label">All apps</span>
        </a>
    </section>

    {{-- Pinned Section --}}
    <section class="qbo-sidebar-section qbo-pinned-section">
        <span class="qbo-pinned-label">PINNED</span>
        {{-- More Button --}}
        <a href="#" class="qbo-sidebar-item" id="qboMoreBtn" aria-label="More">
            <div class="qbo-sidebar-item-icon">
                <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM6 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM18 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" fill="currentColor"></path>
                </svg>
            </div>
            <span class="qbo-sidebar-item-label">More</span>
        </a>
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

{{-- Create Menu Flyout --}}
<div class="qbo-create-flyout" id="qboCreateFlyout">
    <div class="qbo-flyout-header">
        <h4>Create</h4>
        <button class="qbo-flyout-close" id="qboCreateFlyoutClose">
            <i class="ti ti-x"></i>
        </button>
    </div>
    <div class="qbo-flyout-body">
        <div class="qbo-create-section">
            <h5>Customers</h5>
            <ul class="qbo-create-list">
                <li><a href="{{ route('invoice.create', 0) }}"><i class="ti ti-file-invoice"></i> Invoice</a></li>
                <li><a href="{{ route('receive-payment.create') }}"><i class="ti ti-cash"></i> Receive Payment</a></li>
                <li><a href="{{ route('proposal.create', 0) }}"><i class="ti ti-file-text"></i> Estimate</a></li>
                <li><a href="{{ route('creditmemo.create', 0) }}"><i class="ti ti-credit-card"></i> Credit Memo</a></li>
                <li><a href="{{ route('sales-receipt.create') }}"><i class="ti ti-receipt"></i> Sales Receipt</a></li>
            </ul>
        </div>
        <div class="qbo-create-section">
            <h5>Vendors</h5>
            <ul class="qbo-create-list">
                <li><a href="{{ route('expense.index') }}"><i class="ti ti-receipt-2"></i> Expense</a></li>
                <li><a href="{{ route('checks.create') }}"><i class="ti ti-checkup-list"></i> Check</a></li>
                <li><a href="{{ route('bill.create', 0) }}"><i class="ti ti-file-text"></i> Bill</a></li>
                <li><a href="{{ route('purchase.create', 0) }}"><i class="ti ti-shopping-cart"></i> Purchase Order</a></li>
            </ul>
        </div>
        <div class="qbo-create-section">
            <h5>Team</h5>
            <ul class="qbo-create-list">
                <li><a href="{{ route('timeActivity.create') }}"><i class="ti ti-clock"></i> Single Time Activity</a></li>
                <li><a href="#"><i class="ti ti-calendar"></i> Weekly Time Sheet</a></li>
            </ul>
        </div>
        <div class="qbo-create-section">
            <h5>Other</h5>
            <ul class="qbo-create-list">
                <li><a href="{{ route('bank-transfer.create') }}"><i class="ti ti-arrows-exchange"></i> Transfer</a></li>
                <li><a href="{{ route('journal-entry.create') }}"><i class="ti ti-notebook"></i> Journal Entry</a></li>
                <li><a href="#"><i class="ti ti-file-import"></i> Statement</a></li>
                <li><a href="{{ route('productservice.create') }}"><i class="ti ti-package"></i> Inventory Qty Adjustment</a></li>
            </ul>
        </div>
    </div>
</div>

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
                <button type="button" class="btn btn-link text-danger" id="qboResetDefault">Reset to default</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="qboSaveCustomize">Save</button>
            </div>
        </div>
    </div>
</div>

{{-- Sidebar Overlay for Mobile --}}
<div class="qbo-sidebar-overlay" id="qboSidebarOverlay"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const createBtn = document.getElementById('qboCreateBtn');
    const createFlyout = document.getElementById('qboCreateFlyout');
    const createFlyoutClose = document.getElementById('qboCreateFlyoutClose');
    const customizeBtn = document.getElementById('qboCustomizeBtn');
    const overlay = document.getElementById('qboSidebarOverlay');

    // Create button click - toggle flyout
    if (createBtn && createFlyout) {
        createBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            createFlyout.classList.toggle('show');
        });
    }

    // Close create flyout
    if (createFlyoutClose) {
        createFlyoutClose.addEventListener('click', function() {
            createFlyout.classList.remove('show');
        });
    }

    // Close flyout when clicking outside
    document.addEventListener('click', function(e) {
        if (createFlyout && !createFlyout.contains(e.target) && createBtn && !createBtn.contains(e.target)) {
            createFlyout.classList.remove('show');
        }
    });

    // Customize button - open modal
    if (customizeBtn) {
        customizeBtn.addEventListener('click', function() {
            loadCustomizeItems();
            new bootstrap.Modal(document.getElementById('qboCustomizeModal')).show();
        });
    }

    // Load customize items
    function loadCustomizeItems() {
        fetch('{{ route("qbo.menu.config") }}')
            .then(response => response.json())
            .then(data => {
                renderCustomizeItems(data.items);
            })
            .catch(error => console.error('Error loading menu config:', error));
    }

    // Render customize items
    function renderCustomizeItems(items) {
        const list = document.getElementById('qboCustomizeList');
        if (!list) return;
        list.innerHTML = '';

        items.forEach((item, index) => {
            const div = document.createElement('div');
            div.className = 'qbo-customize-item';
            div.dataset.key = item.key;
            div.dataset.position = index;
            div.innerHTML = `
                <div class="qbo-customize-drag">
                    <i class="ti ti-grip-vertical"></i>
                </div>
                <div class="qbo-customize-icon" style="background: ${item.color || '#666'}">
                    <i class="${item.icon || 'ti ti-circle'}"></i>
                </div>
                <div class="qbo-customize-label">${item.label}</div>
                <div class="qbo-customize-actions">
                    <button class="qbo-pin-btn ${item.type === 'pinned' ? 'pinned' : ''}" data-key="${item.key}">
                        <i class="ti ti-pin"></i>
                    </button>
                    <div class="form-check form-switch">
                        <input class="form-check-input qbo-visibility-toggle" type="checkbox" data-key="${item.key}" ${item.is_visible !== false ? 'checked' : ''}>
                    </div>
                </div>
            `;
            list.appendChild(div);
        });

        // Initialize sortable if available
        if (typeof Sortable !== 'undefined') {
            new Sortable(list, {
                animation: 150,
                handle: '.qbo-customize-drag',
                ghostClass: 'qbo-sortable-ghost'
            });
        }
    }

    // Reset to default
    const resetBtn = document.getElementById('qboResetDefault');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
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
            .catch(error => console.error('Error resetting menu:', error));
        });
    }

    // Save customization
    const saveBtn = document.getElementById('qboSaveCustomize');
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            const items = [];
            document.querySelectorAll('.qbo-customize-item').forEach((el, index) => {
                items.push({
                    key: el.dataset.key,
                    position: index,
                    is_visible: el.querySelector('.qbo-visibility-toggle').checked
                });
            });

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
                bootstrap.Modal.getInstance(document.getElementById('qboCustomizeModal')).hide();
                if (typeof toastr !== 'undefined') {
                    toastr.success('Menu customization saved');
                }
                location.reload();
            })
            .catch(error => console.error('Error saving menu:', error));
        });
    }

    // Mobile overlay click
    if (overlay) {
        overlay.addEventListener('click', function() {
            document.getElementById('qboSidebar').classList.remove('mobile-open');
            overlay.classList.remove('show');
        });
    }
});
</script>
