{{-- QBO-Style Header --}}
<header class="qbo-header" id="qboHeader">
    {{-- Mobile Menu Toggle --}}
    <button class="qbo-mobile-menu-btn d-md-none" id="qboMobileMenuBtn">
        <i class="ti ti-menu-2"></i>
    </button>

    {{-- Search Bar --}}
    <div class="qbo-header-search">
        <div class="qbo-search-wrapper">
            <i class="ti ti-search qbo-search-icon"></i>
            <input type="text" class="qbo-search-input" placeholder="Search transactions, contacts, and more" id="qboGlobalSearch">
            <kbd class="qbo-search-shortcut">/</kbd>
        </div>
    </div>

    {{-- Header Right Actions --}}
    <div class="qbo-header-actions">
        {{-- Notifications --}}
        <div class="qbo-header-action">
            <button class="qbo-action-btn" id="qboNotificationsBtn" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="ti ti-bell"></i>
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

        {{-- Settings --}}
        <div class="qbo-header-action">
            <button class="qbo-action-btn" id="qboSettingsBtn" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="ti ti-settings"></i>
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
            <button class="qbo-action-btn" id="qboHelpBtn">
                <i class="ti ti-help-circle"></i>
            </button>
        </div>

        {{-- User Profile --}}
        <div class="qbo-header-action qbo-user-menu">
            <button class="qbo-user-btn" data-bs-toggle="dropdown" aria-expanded="false">
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

    // Global search shortcut
    document.addEventListener('keydown', function(e) {
        if (e.key === '/' && !['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
            e.preventDefault();
            document.getElementById('qboGlobalSearch').focus();
        }
    });

    // Mark all notifications as read
    const markAllRead = document.querySelector('.qbo-mark-all-read');
    if (markAllRead) {
        markAllRead.addEventListener('click', function(e) {
            e.preventDefault();
            // Implement notification mark as read
        });
    }
});
</script>
