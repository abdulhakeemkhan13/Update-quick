{{-- QBO-Style Header --}}
@php
    $setting = \App\Models\Utility::settings();
    $company_logo = $setting['company_logo'] ?? 'logo-dark.png';
    $company_name = $setting['company_name'] ?? 'My Company';
@endphp
<header class="qbo-header" id="qboHeader">
    {{-- Logo & Company Name Section --}}
    <div class="qbo-header-brand">
        {{-- Mobile Menu Toggle --}}
        <button class="qbo-mobile-menu-btn d-md-none" id="qboMobileMenuBtn">
            <i class="ti ti-menu-2"></i>
        </button>
        
        {{-- Logo --}}
        <a href="{{ route('dashboard') }}" class="qbo-header-logo">
            <img src="{{ asset(Storage::url('uploads/logo/')) }}/{{ $company_logo }}" alt="{{ $company_name }}" class="qbo-logo-img">
        </a>
        
        {{-- Company Name --}}
        <a href="{{ route('dashboard') }}" class="qbo-header-company-name">
            {{ $company_name }}
        </a>
    </div>

    {{-- Search Bar --}}
    <div class="qbo-header-search">
        <div class="qbo-search-wrapper">
            <i class="ti ti-search qbo-search-icon"></i>
            <input type="text" class="qbo-search-input" placeholder="Navigate or search for transactions, contacts, reports, and more" id="qboGlobalSearch" autocomplete="off">
            <kbd class="qbo-search-shortcut">/</kbd>
            {{-- Results Dropdown --}}
            <div id="qboSearchResults" class="qbo-search-results"></div>
        </div>
    </div>

    {{-- Header Right Actions - Exact QBO Order --}}
    <div class="qbo-header-actions">
        {{-- User/Account Alert (Red badge like QBO) --}}
        <div class="qbo-header-action">
            <button class="qbo-action-btn qbo-user-alert-btn" title="Account alerts">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10ZM12 14c-5.33 0-8 2.67-8 8h16c0-5.33-2.67-8-8-8Z" fill="currentColor"/>
                </svg>
                <span class="qbo-alert-badge"></span>
            </button>
        </div>

        {{-- QB Assistant / Message --}}
        <div class="qbo-header-action">
            <button class="qbo-action-btn" title="QB Assistant">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2Z" fill="currentColor"/>
                </svg>
            </button>
        </div>

        {{-- Notifications Bell --}}
        <div class="qbo-header-action">
            <button class="qbo-action-btn" id="qboNotificationsBtn" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2Zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2Z" fill="currentColor"/>
                </svg>
                @if(isset($unseenCounter) && $unseenCounter > 0)
                    <span class="qbo-badge">{{ $unseenCounter }}</span>
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

        {{-- Settings Gear --}}
        <div class="qbo-header-action">
            <button class="qbo-action-btn" id="qboSettingsBtn" data-bs-toggle="dropdown" aria-expanded="false" title="Settings">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19.14 12.94c.04-.31.06-.63.06-.94 0-.31-.02-.63-.06-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.04.31-.06.63-.06.94s.02.63.06.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58ZM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6Z" fill="currentColor"/>
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

        {{-- Help --}}
        <div class="qbo-header-action">
            <button class="qbo-action-btn" id="qboHelpBtn" title="Help">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2Zm1 17h-2v-2h2v2Zm2.07-7.75-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25Z" fill="currentColor"/>
                </svg>
            </button>
        </div>

        {{-- User Profile --}}
        <div class="qbo-header-action qbo-user-menu">
            <button class="qbo-user-btn" data-bs-toggle="dropdown" aria-expanded="false" title="Profile">
                @if(Auth::user()->avatar)
                    <img src="{{ asset(Storage::url('uploads/avatar/')) }}/{{ Auth::user()->avatar }}" alt="Avatar" class="qbo-user-avatar">
                @else
                    <div class="qbo-user-initial">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                @endif
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
    let searchTimeout = null;
    let recentFetched = false;
    let recentData = null;

    // Keyboard shortcut
    document.addEventListener('keydown', function(e) {
        if (e.key === '/' && !['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
            e.preventDefault();
            searchInput.focus();
        }
        // Escape to close
        if (e.key === 'Escape') {
            resultsContainer.classList.remove('show');
            searchInput.blur();
        }
    });

    // Close on click outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
            resultsContainer.classList.remove('show');
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

