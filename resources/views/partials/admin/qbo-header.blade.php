{{-- QBO-Style Header --}}
@php
    $setting = \App\Models\Utility::settings();
    $company_logo = $setting['company_logo'] ?? 'logo-dark.png';
    $company_name = $setting['company_name'] ?? 'My Company';
@endphp
<header class="qbo-header" id="qboHeader" role="banner">
    <nav class="qbo-header-nav" aria-label="Tools">
        {{-- Left Container: Logo + Divider + Company Name --}}
        <div class="qbo-header-container qbo-container-left">
            {{-- Mobile Menu Toggle --}}
            <button class="qbo-mobile-menu-btn d-md-none" id="qboMobileMenuBtn">
                <i class="ti ti-menu-2"></i>
            </button>
            
            {{-- Logo --}}
            <div class="qbo-header-item">
                <a href="{{ route('dashboard') }}" class="qbo-app-logo" aria-label="Dashboard">
                    <div class="qbo-logo-wrapper">
                        {{-- QBO Ball Logo SVG --}}
                        <svg class="qbo-logo-icon" width="32" height="32" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="40" height="40" rx="4" fill="#2CA01C"/>
                            <path d="M20 8C13.373 8 8 13.373 8 20C8 26.627 13.373 32 20 32C26.627 32 32 26.627 32 20C32 13.373 26.627 8 20 8ZM24.5 25C24.5 25.828 23.828 26.5 23 26.5H17C16.172 26.5 15.5 25.828 15.5 25V15C15.5 14.172 16.172 13.5 17 13.5H23C23.828 13.5 24.5 14.172 24.5 15V25Z" fill="white"/>
                        </svg>
                        {{-- Company Logo Image --}}
                        <img src="{{ asset(Storage::url('uploads/logo/')) }}/{{ $company_logo }}" alt="{{ $company_name }}" class="qbo-logo-img">
                    </div>
                </a>
            </div>
            
            {{-- Vertical Divider --}}
            <div class="qbo-header-item">
                <div class="qbo-header-divider" role="separator"></div>
            </div>
            
            {{-- Company Name --}}
            <div class="qbo-header-item">
                <span class="qbo-company-name" role="heading" aria-level="2">
                    {{ $company_name ?: 'Sample Company' }}
                </span>
            </div>
        </div>

        {{-- Center Container: Search Bar --}}
        <div class="qbo-header-container qbo-container-center">
            <div class="qbo-header-item">
                <div class="qbo-global-search-container" role="search">
                    <div class="qbo-search-input-wrapper">
                        {{-- Search Icon SVG (exact QBO) --}}
                        <svg class="qbo-search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="m21.694 20.307-6.239-6.258A7.495 7.495 0 0 0 9.515 2H9.5a7.5 7.5 0 1 0 4.535 13.465l6.24 6.259a1.001 1.001 0 0 0 1.416-1.413l.003-.004ZM5.609 13.38A5.5 5.5 0 0 1 9.5 4h.009a5.5 5.5 0 1 1-3.9 9.384v-.004Z" fill="currentColor"></path>
                        </svg>
                        <input type="search" 
                               id="qboGlobalSearch" 
                               class="qbo-search-input" 
                               placeholder="Navigate or search for transactions, contacts, reports, and more" 
                               autocomplete="off"
                               aria-label="search">
                    </div>
                    {{-- Search Results Dropdown --}}
                    <div id="qboSearchResults" class="qbo-search-results"></div>
                </div>
            </div>
        </div>

        {{-- Right Container: Action Icons - EXACT QBO ORDER --}}
        <div class="qbo-header-container qbo-container-right">
            {{-- 1. User Account Avatar (with red badge) --}}
            <div class="qbo-header-item">
                <button type="button" class="qbo-header-item-button qbo-oia-btn" title="Intuit account">
                    <span class="qbo-avatar qbo-avatar-alert">
                        @if(Auth::user()->avatar)
                            <img src="{{ asset(Storage::url('uploads/avatar/')) }}/{{ Auth::user()->avatar }}" alt="Avatar">
                        @else
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        @endif
                    </span>
                </button>
            </div>

            {{-- 2. QB Assistant / Message - QBO Exact Icon --}}
            <div class="qbo-header-item" title="QB Assistant">
                <button class="qbo-header-item-button" aria-label="QB Assistant">
                    {{-- QBO Chat/Monitor Icon --}}
                    <svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="#6b6c72" focusable="false" aria-hidden="true" class="global-header-item-icon"><path fill-rule="evenodd" clip-rule="evenodd" d="M11 2a3 3 0 0 1 3 3v6a3 3 0 0 1-3 3H9v2a1 1 0 0 0 1 1h1a3 3 0 0 1 3-3h5a3 3 0 0 1 3 3v2a3 3 0 0 1-3 3h-5a3 3 0 0 1-3-3h-1a3 3 0 0 1-3-3v-2H5a3 3 0 0 1-3-3V5a3 3 0 0 1 3-3h6Zm3 14a1 1 0 0 0-1 1v2a1 1 0 0 0 .898.995L14 20h5l.102-.005a1 1 0 0 0 .893-.893L20 19v-2a1 1 0 0 0-1-1h-5ZM5 4a1 1 0 0 0-1 1v6a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V5a1 1 0 0 0-1-1H5Z" fill="currentColor"></path><path fill-rule="evenodd" clip-rule="evenodd" d="M19 4a3 3 0 0 1 3 3v3a3 3 0 0 1-3 3h-1a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1Zm-1 2a1 1 0 0 0-1 1v3l.005.102a1 1 0 0 0 .893.893L18 11h1l.102-.005A1 1 0 0 0 20 10V7a1 1 0 0 0-1-1h-1Z" fill="currentColor"></path></svg>
                </button>
            </div>

            {{-- 3. Notifications Bell --}}
            <div class="qbo-header-item" title="Notifications">
                <button class="qbo-header-item-button qbo-notifications-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="qbo-header-item-icon">
                        <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z" fill="currentColor"/>
                    </svg>
                    @if(isset($unseenCounter) && $unseenCounter > 0)
                        <span class="qbo-notification-badge">{{ $unseenCounter }}</span>
                    @endif
                </button>
                <div class="dropdown-menu dropdown-menu-end qbo-dropdown-menu qbo-notifications-dropdown">
                    <div class="qbo-dropdown-header">
                        <h6>Notifications</h6>
                        <a href="#" class="qbo-mark-all-read">Mark all as read</a>
                    </div>
                    <div class="qbo-dropdown-body">
                        @if(isset($notifications) && count($notifications) > 0)
                            @foreach($notifications as $notification)
                                <a href="#" class="qbo-notification-item">
                                    <div class="qbo-notification-icon">
                                        <i class="ti ti-bell"></i>
                                    </div>
                                    <div class="qbo-notification-content">
                                        <p>{{ $notification->data['title'] ?? 'Notification' }}</p>
                                        <span class="qbo-notification-time">{{ $notification->created_at->diffForHumans() }}</span>
                                    </div>
                                </a>
                            @endforeach
                        @else
                            <div class="qbo-empty-state">
                                <i class="ti ti-bell-off"></i>
                                <p>No new notifications</p>
                            </div>
                        @endif
                    </div>
                    <div class="qbo-dropdown-footer">
                        <a href="#">View all notifications</a>
                    </div>
                </div>
            </div>

            {{-- 4. Settings Gear --}}
            <div class="qbo-header-item" title="Settings">
                <button class="qbo-header-item-button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Settings">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="qbo-header-item-icon">
                        <path d="M12.024 7.982h-.007a4 4 0 1 0 0 8 4 4 0 1 0 .007-8Zm-.006 6a2 2 0 0 1 .002-4 2 2 0 1 1 0 4h-.002Z" fill="currentColor"></path>
                        <path d="m20.444 13.4-.51-.295a7.557 7.557 0 0 0 0-2.214l.512-.293a2.005 2.005 0 0 0 .735-2.733l-1-1.733a2.005 2.005 0 0 0-2.731-.737l-.512.295a8.071 8.071 0 0 0-1.915-1.113v-.59a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v.6a8.016 8.016 0 0 0-1.911 1.1l-.52-.3a2 2 0 0 0-2.725.713l-1 1.73a2 2 0 0 0 .728 2.733l.509.295a7.75 7.75 0 0 0-.004 2.22l-.51.293a2 2 0 0 0-.738 2.73l1 1.732a2 2 0 0 0 2.73.737l.513-.295A8.07 8.07 0 0 0 9.01 19.39v.586a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2V19.4a8.014 8.014 0 0 0 1.918-1.107l.51.3a2 2 0 0 0 2.734-.728l1-1.73a2 2 0 0 0-.728-2.735Zm-2.593-2.8a5.8 5.8 0 0 1 0 2.78 1 1 0 0 0 .472 1.1l1.122.651-1 1.73-1.123-.65a1 1 0 0 0-1.187.137 6.02 6.02 0 0 1-2.4 1.387 1 1 0 0 0-.716.957v1.294h-2v-1.293a1 1 0 0 0-.713-.96 5.991 5.991 0 0 1-2.4-1.395 1.006 1.006 0 0 0-1.188-.142l-1.125.648-1-1.733 1.125-.647a1 1 0 0 0 .475-1.1 5.945 5.945 0 0 1-.167-1.387c.003-.467.06-.933.17-1.388a1 1 0 0 0-.471-1.1l-1.123-.65 1-1.73 1.124.651c.019.011.04.01.06.02a.97.97 0 0 0 .186.063.9.9 0 0 0 .2.04c.02 0 .039.011.059.011a1.08 1.08 0 0 0 .136-.025.98.98 0 0 0 .17-.032c.057-.024.111-.053.163-.087a.986.986 0 0 0 .157-.1c.015-.013.034-.017.048-.03a6.011 6.011 0 0 1 2.4-1.39.453.453 0 0 0 .049-.026.938.938 0 0 0 .183-.1.87.87 0 0 0 .15-.1.953.953 0 0 0 .122-.147c.038-.049.071-.1.1-.156a1.01 1.01 0 0 0 .055-.173c.02-.065.034-.132.04-.2 0-.018.012-.034.012-.053V3.981h2v1.294a1 1 0 0 0 .713.96c.897.273 1.72.75 2.4 1.395a1 1 0 0 0 1.186.141l1.126-.647 1 1.733-1.125.647a1 1 0 0 0-.465 1.096Z" fill="currentColor"></path>
                    </svg>
                </button>
                <div class="dropdown-menu dropdown-menu-end qbo-dropdown-menu">
                    <a class="dropdown-item" href="{{ route('settings') }}">
                        <i class="ti ti-settings"></i> Company Settings
                    </a>
                    <a class="dropdown-item" href="{{ route('chart-of-account.index') }}">
                        <i class="ti ti-list-tree"></i> Chart of Accounts
                    </a>
                    <a class="dropdown-item" href="{{ route('payment-terms.index') }}">
                        <i class="ti ti-calendar"></i> Payment Terms
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{{ route('users.index') }}">
                        <i class="ti ti-users"></i> Manage Users
                    </a>
                </div>
            </div>

            {{-- 5. Help - QBO Exact Icon --}}
            <div class="qbo-header-item" title="Help">
                <button type="button" class="qbo-header-item-button" aria-label="Help">
                    {{-- QBO Help/Question Mark Circle Outline Icon --}}
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="qbo-header-item-icon">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z" fill="currentColor"/>
                        <path d="M12.5 7.09c.18.04.34.1.48.19.14.09.27.19.38.32.11.13.19.27.25.43.06.15.09.32.09.49 0 .28-.07.53-.21.74-.14.21-.35.4-.63.57-.14.08-.25.18-.33.28-.08.1-.14.21-.17.33-.03.11-.04.23-.04.35v.41h-1.64v-.55c0-.26.04-.49.12-.7.08-.21.19-.38.33-.53.14-.15.3-.28.48-.39.16-.09.29-.2.38-.32.09-.12.14-.27.14-.45 0-.14-.03-.27-.1-.37-.07-.1-.15-.19-.24-.25-.1-.06-.2-.11-.32-.14-.12-.03-.23-.04-.34-.04-.27 0-.5.06-.7.19-.2.13-.38.3-.54.51l-1.17-.88c.26-.38.59-.68.99-.89.4-.21.88-.31 1.44-.31.28 0 .55.04.82.11zm-1.63 5.73h1.64v1.64h-1.64v-1.64z" fill="currentColor"/>
                    </svg>
                </button>
            </div>

            {{-- 6. User Profile Avatar (final) --}}
            <div class="qbo-header-item">
                <button type="button" class="qbo-header-item-button qbo-user-profile-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="User profile">
                    <span class="qbo-avatar qbo-avatar-profile">
                        @if(Auth::user()->avatar)
                            <img src="{{ asset(Storage::url('uploads/avatar/')) }}/{{ Auth::user()->avatar }}" alt="Avatar">
                        @else
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        @endif
                    </span>
                </button>
                <div class="dropdown-menu dropdown-menu-end qbo-dropdown-menu qbo-user-dropdown">
                    <div class="qbo-user-info">
                        <div class="qbo-user-avatar-lg">
                            @if(Auth::user()->avatar)
                                <img src="{{ asset(Storage::url('uploads/avatar/')) }}/{{ Auth::user()->avatar }}" alt="Avatar">
                            @else
                                <div class="qbo-user-initial-lg">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div class="qbo-user-details">
                            <h6>{{ Auth::user()->name }}</h6>
                            <p>{{ Auth::user()->email }}</p>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{{ route('profile') }}">
                        <i class="ti ti-user"></i> My Profile
                    </a>
                    <a class="dropdown-item" href="{{ route('settings') }}">
                        <i class="ti ti-settings"></i> Settings
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="ti ti-logout"></i> Sign Out
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </div>
            </div>
        </div>
    </nav>
</header>
<style>
/* Search Results Style */
.qbo-search-results {
    position: absolute;
    top: 100%;
    left: 0;
    width: 100%;
    min-width: 400px;
    background: white;
    border: 1px solid #dfe1e5;
    border-top: none;
    border-radius: 0 0 8px 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    max-height: 450px;
    overflow-y: auto;
    z-index: 1050;
    display: none;
}
.qbo-search-results.show {
    display: block;
}
.qbo-search-header {
    padding: 10px 16px;
    font-size: 12px;
    font-weight: 600;
    color: #6b6c72;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.qbo-search-item {
    display: flex;
    align-items: center;
    padding: 10px 16px;
    border-bottom: 1px solid #f1f1f1;
    text-decoration: none !important;
    color: #393a3d;
    transition: background-color 0.15s;
    cursor: pointer;
}
.qbo-search-item:last-child {
    border-bottom: none;
}
.qbo-search-item:hover {
    background-color: #f0fdf4;
    color: #108000;
}
.qbo-item-icon {
    width: 28px;
    height: 28px;
    background: #f4f5f8;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    color: #5d606b;
    font-size: 14px;
}
.qbo-search-item:hover .qbo-item-icon {
    background: #dcfce7;
    color: #108000;
}
.qbo-item-details {
    flex: 1;
    min-width: 0;
}
.qbo-item-label {
    font-weight: 500;
    font-size: 13px;
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.qbo-item-sub {
    font-size: 11px;
    color: #6b6c72;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.qbo-item-amount {
    font-weight: 600;
    font-size: 13px;
    color: #374151;
    margin-left: 12px;
    white-space: nowrap;
}
.qbo-search-empty {
    padding: 20px 16px;
    text-align: center;
    color: #6b6c72;
    font-size: 13px;
}
.qbo-search-footer {
    padding: 10px 16px;
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
    text-align: center;
}
.qbo-search-footer a {
    color: #2563eb;
    font-size: 12px;
    text-decoration: none;
    font-weight: 500;
}
.qbo-search-footer a:hover {
    text-decoration: underline;
}
.qbo-search-hints {
    padding: 12px 16px;
    background: #fffbeb;
    border-bottom: 1px solid #fef3c7;
    font-size: 11px;
    color: #92400e;
}
.qbo-search-hints strong {
    color: #78350f;
}
.qbo-search-loading {
    padding: 20px;
    text-align: center;
    color: #6b6c72;
}
.qbo-search-loading i {
    animation: spin 1s linear infinite;
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
/* AI Answer Styling */
.qbo-search-item.ai-answer {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border-left: 3px solid #22c55e;
}
.qbo-search-item.ai-answer .qbo-item-icon {
    background: #22c55e;
    color: white;
}
.qbo-search-item.ai-answer .qbo-item-sub {
    font-size: 16px;
    font-weight: 700;
    color: #16a34a;
}
.qbo-ai-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
    color: white;
    font-size: 10px;
    font-weight: 600;
    border-radius: 12px;
    margin-left: 8px;
}
.qbo-ai-badge i {
    font-size: 10px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuBtn = document.getElementById('qboMobileMenuBtn');
    const sidebar = document.getElementById('qboSidebar');
    const overlay = document.getElementById('qboSidebarOverlay');

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function() {
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('show');
        });
    }

    // Global search
    const searchInput = document.getElementById('qboGlobalSearch');
    const resultsContainer = document.getElementById('qboSearchResults');
    const searchContainer = document.querySelector('.qbo-global-search-container');
    let searchTimeout = null;
    let recentFetched = false;
    let recentData = null;

    // Expand search on focus (like QBO)
    if (searchInput && searchContainer) {
        searchInput.addEventListener('focus', function() {
            searchContainer.classList.add('is-focused');
        });
        
        searchInput.addEventListener('blur', function(e) {
            // Delay to allow click on results
            setTimeout(() => {
                if (!searchContainer.contains(document.activeElement) && 
                    !resultsContainer.contains(document.activeElement)) {
                    searchContainer.classList.remove('is-focused');
                }
            }, 150);
        });
    }

    // Keyboard shortcut
    document.addEventListener('keydown', function(e) {
        if (e.key === '/' && !['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
            e.preventDefault();
            searchInput.focus();
        }
        // Escape to close
        if (e.key === 'Escape') {
            resultsContainer.classList.remove('show');
            searchContainer.classList.remove('is-focused');
            searchInput.blur();
        }
    });

    // Close on click outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target) && !searchContainer.contains(e.target)) {
            resultsContainer.classList.remove('show');
            searchContainer.classList.remove('is-focused');
        }
    });


    // Render recent transactions
    function renderRecent(data) {
        let html = '';
        
        // NLP Hints
        html += `<div class="qbo-search-hints">
            <strong>Try:</strong> "2024 invoices", "bills over $500", "show profile", "last month transactions"
        </div>`;
        
        if (data.recent && data.recent.length > 0) {
            html += '<div class="qbo-search-header">Recent transactions</div>';
            data.recent.forEach(item => {
                html += `
                    <a href="${item.url}" class="qbo-search-item">
                        <div class="qbo-item-icon">
                            <i class="${item.icon}"></i>
                        </div>
                        <div class="qbo-item-details">
                            <div class="qbo-item-label">${item.title}</div>
                            <div class="qbo-item-sub">${item.date}</div>
                        </div>
                        <div class="qbo-item-amount">${item.amount}</div>
                    </a>
                `;
            });
        } else {
            html += '<div class="qbo-search-empty">No recent transactions</div>';
        }
        
        html += `<div class="qbo-search-footer">
            <a href="{{ route('invoice.index') }}">Advanced transactions search for more results</a>
        </div>`;
        
        return html;
    }

    // Render search results
    function renderResults(data, query) {
        let html = '';
        
        if (data.length > 0) {
            // Check for AI answers first
            const aiAnswers = data.filter(item => item.is_answer);
            const regularResults = data.filter(item => !item.is_answer);
            
            // Render AI answers at top
            if (aiAnswers.length > 0) {
                html += '<div class="qbo-search-header">AI Answer <span class="qbo-ai-badge"><i class="ti ti-sparkles"></i> AI</span></div>';
                aiAnswers.forEach(item => {
                    html += `
                        <div class="qbo-search-item ai-answer">
                            <div class="qbo-item-icon">
                                <i class="${item.icon}"></i>
                            </div>
                            <div class="qbo-item-details">
                                <div class="qbo-item-label">${item.label}</div>
                                <div class="qbo-item-sub">${item.sub_label}</div>
                            </div>
                        </div>
                    `;
                });
            }
            
            // Group regular results by type
            const grouped = {};
            regularResults.forEach(item => {
                if (!grouped[item.type]) grouped[item.type] = [];
                grouped[item.type].push(item);
            });
            
            for (const [type, items] of Object.entries(grouped)) {
                html += `<div class="qbo-search-header">${type}s</div>`;
                items.forEach(item => {
                    html += `
                        <a href="${item.url}" class="qbo-search-item">
                            <div class="qbo-item-icon">
                                <i class="${item.icon}"></i>
                            </div>
                            <div class="qbo-item-details">
                                <div class="qbo-item-label">${item.label}</div>
                                <div class="qbo-item-sub">${item.sub_label || item.type}</div>
                            </div>
                        </a>
                    `;
                });
            }
        } else {
            html = `<div class="qbo-search-empty">No results found for "${query}"</div>`;
        }
        
        html += `<div class="qbo-search-footer">
            <a href="{{ route('invoice.index') }}">Advanced transactions search for more results</a>
        </div>`;
        
        return html;
    }

    if (searchInput) {
        // On focus - show recent transactions
        searchInput.addEventListener('focus', function() {
            const query = this.value.trim();
            
            if (query.length < 2) {
                // Show recent transactions
                if (recentData) {
                    resultsContainer.innerHTML = renderRecent(recentData);
                    resultsContainer.classList.add('show');
                } else if (!recentFetched) {
                    recentFetched = true;
                    resultsContainer.innerHTML = '<div class="qbo-search-loading"><i class="ti ti-loader"></i> Loading...</div>';
                    resultsContainer.classList.add('show');
                    
                    fetch('{{ route("global.search.recent") }}')
                        .then(response => response.json())
                        .then(data => {
                            recentData = data;
                            resultsContainer.innerHTML = renderRecent(data);
                        })
                        .catch(error => {
                            console.error('Recent fetch error:', error);
                            resultsContainer.innerHTML = '<div class="qbo-search-empty">Unable to load recent transactions</div>';
                        });
                } else if (recentData) {
                    resultsContainer.innerHTML = renderRecent(recentData);
                    resultsContainer.classList.add('show');
                }
            } else if (resultsContainer.innerHTML !== '') {
                resultsContainer.classList.add('show');
            }
        });

        // On input - search
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length < 2) {
                // Show recent instead
                if (recentData) {
                    resultsContainer.innerHTML = renderRecent(recentData);
                    resultsContainer.classList.add('show');
                }
                return;
            }

            searchTimeout = setTimeout(() => {
                resultsContainer.innerHTML = '<div class="qbo-search-loading"><i class="ti ti-loader"></i> Searching...</div>';
                resultsContainer.classList.add('show');
                
                fetch(`{{ route('global.search') }}?query=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        resultsContainer.innerHTML = renderResults(data, query);
                        resultsContainer.classList.add('show');
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        resultsContainer.innerHTML = '<div class="qbo-search-empty">Error loading results</div>';
                        resultsContainer.classList.add('show');
                    });
            }, 300);
        });
    }

    // Mark all notifications as read
    const markAllRead = document.querySelector('.qbo-mark-all-read');
    if (markAllRead) {
        markAllRead.addEventListener('click', function(e) {
            e.preventDefault();
        });
    }
});
</script>

