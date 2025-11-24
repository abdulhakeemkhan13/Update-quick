@php
    $users = \Auth::user();
    $profile = \App\Models\Utility::get_file('uploads/avatar/');
    $languages = \App\Models\Utility::languages();

    $lang = isset($users->lang) ? $users->lang : 'en';
    if ($lang == null) {
        $lang = 'en';
    }

    $notifications = \App\Models\Notification::where('user_id', Auth::user()->id)
        ->where('is_read', 0)
        ->get();
    // dd($notifications);
    // $LangName = \App\Models\Language::where('code',$lang)->first();
    // $LangName =\App\Models\Language::languageData($lang);
    $LangName = cache()->remember('full_language_data_' . $lang, now()->addHours(24), function () use ($lang) {
        return \App\Models\Language::languageData($lang);
    });

    $setting = \App\Models\Utility::settings();

    $unseenCounter = App\Models\ChMessage::where('to_id', Auth::user()->id)
        ->where('seen', 0)
        ->count();
@endphp
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $(document).ready(function() {
        // Toggle dropdown visibility and fetch notifications when shown
        function toggleDropdown() {
            const dropdownContent = $('.dropdown-content');

            setTimeout(() => {
                const isVisible = dropdownContent.is(':visible');
                dropdownContent.css('display', isVisible ? 'none' : 'block');
            }, 100); // Adjust delay if needed
        }

        // Mark a notification as seen


        // Fetch notifications and render them in the dropdown
        // function fetchNotifications() {
        //     $.ajax({
        //         url: 'notifications', // Adjust the URL as necessary
        //         type: 'GET',
        //         success: function(response) {
        //             const notificationList = $('.notifications_1');
        //             // notificationList.empty();

        //             if (response.length > 0) {
        //                 $('.noti').empty().append(response.length);
        //                 response.forEach(notification => {
        //                     const notificationHTML = `
        //                         <li class=" pb-3 border-bottom border-separator-light d-flex notification-item" style="margin: 5px 12px !important;  width: 225px; !important;"
        //                             data-id="${notification.id}">
        //                             <div class="align-self-start">
        //                                 <a href="#">
        //                                     ${notification.title} just
        //                                     <span>${notification.log_type}d</span>
        //                                     <span>a ${notification.type}</span>
        //                                 </a>
        //                             </div>
        //                         </li>`;
        //                     // notificationList.append(notificationHTML);
        //                 });
        //             } else {
        //                 const noNotificationsHTML = `
        //                     <li class=" pb-3 border-bottom border-separator-light d-flex" style="margin: 5px 12px !important; width: 225px; !important;">
        //                         <div class="align-self-center">
        //                             <a href="#">You got no new Notifications</a>
        //                         </div>
        //                     </li>`;
        //                 notificationList.append(noNotificationsHTML);
        //             }
        //         },
        //         error: function(xhr, status, error) {
        //             console.error('Error fetching notifications:', error);
        //         }
        //     });
        // }

        // Event listener for notification click
        $(document).on('click', '.notification-item', function() {
            const notificationId = $(this).data('id');
            markAsSeen(notificationId);
        });

        // Attach dropdown toggle to relevant event
        $('.drp-notifi').on('click', toggleDropdown);

        // Hide dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.querySelector('.dropdown-content');

            // Check if the dropdown exists and the click is outside of the dropdown or its toggle button
            if (dropdown && !dropdown.contains(event.target) && !event.target.closest('.drp-notifi')) {
                dropdown.style.display = 'none'; // Set display to 'none' to hide dropdown
            }
        });

    });
</script>
@if (isset($setting['cust_theme_bg']) && $setting['cust_theme_bg'] == 'on')
    <header class="dash-header transprent-bg">
    @else
        <header class="dash-header">
@endif
<style>
    /* Style for the notification dropdown */
    #notification-dropdown-content {
        display: none;
        position: absolute;
        top: 50px;
        /* background-color: #f1f1f1; */
        /* border: 1px solid #ccc; */
        /* box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); */
        border-radius: 5px;
    }

    #notification-dropdown-content .notification-content {
        list-style: none !important;
        margin: 0 !important;
        padding: 10px !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: flex-start !important;
        align-content: center !important;
        width: max-content;
        height: 400px;
        overflow-y: scroll;
    }

    .notification-content .notification-item {
        padding: 8px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        padding: 9px 10px;
    }

    .notification-content .notification-item:hover {
        border-bottom: 1px solid #48494b;
        cursor: pointer;
    }

    @media (max-width: 600px) {
        .dash-header .dash-h-item.drp-search {
            display: none;
        }
    }

    .noti {
        top: 2px !important;
        left: 4px;
    }

    @media (min-width: 280px) and (max-width: 479px) {
        .noti {
            left: -80px;
            max-width: 300px;
        }
    }

    @media (min-width: 480px) and (max-width: 767px) {
        .noti {
            left: -50px;
            max-width: 300px;
        }
    }

    @media (min-width: 768px) and (max-width: 1023px) {
        .noti {
            left: -15px;
            max-width: 300px;
        }
    }

    @media (min-width: 1025px) and (max-width: 1440px) {
        .noti {
            left: 0px;
            max-width: 300px;
        }
    }
</style>

<div class="header-wrapper border_bg border_radius_20">
    <div class="dash-mob-drp">
        <ul class="list-unstyled">
            <li class="dash-h-item mob-hamburger">
                <a href="#!" class="dash-head-link" id="mobile-collapse">
                    <div class="hamburger hamburger--arrowturn">
                        <div class="hamburger-box">
                            <div class="hamburger-inner"></div>
                        </div>
                    </div>
                </a>
            </li>

            {{-- <li class="dropdown dash-h-item drp-company">
                <h4>Dashboard Overview</h4>
            </li> --}}
            <li class="dropdown dash-h-item drp-search">
                <div class="search_group">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                        class="bi bi-search" viewBox="0 0 16 16">
                        <path
                            d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0" />
                    </svg>
                    <input type="search" placeholder="search something.." style="width: 250px;">
                </div>
            </li>


        </ul>
    </div>
    <div class="">
        <ul class="list-unstyled">
            <li class=" dash-h-item drp-notifi drp-notification" id="drop-down-notification">
                <a class="dash-head-link arrow-none me-0" aria-haspopup="false" aria-expanded="false"
                    id="notification-dropdown">
                    <i class="fa fa-bell" style="color: #48494b;"></i>
                    <span
                        class="bg-danger dash-h-badge message-toggle-msg message-counter custom_messanger_counter beep">
                        {{ $notifications->count() }}
                        <span class="sr-only"></span>
                    </span>
                </a>
            </li>
            {{-- Tasks button --}}
            <li class="dropdown dash-h-item drp-company">
                <a class="dash-head-link dropdown-toggle arrow-none me-0 border_none" id="openTaskModal"
                    href="javascript:void(0);" data-size="lg" data-ajax-popup="true"
                    data-url="{{ route('task-Creation-On-Dashboard-Create') }}">
                    <i class="ti ti-list" style="font-size: 20px; color: var(--used-color);"></i>
                </a>
            </li>
            @if (\Auth::user()->type == 'super admin')
                @if (Gate::check('manage system settings'))
                    <li class="dropdown dash-h-item drp-company">
                        <a class="dash-head-link dropdown-toggle arrow-none me-0 border_none" data-bs-toggle="dropdown"
                            href="#" role="button" aria-haspopup="false" aria-expanded="false">
                            <i class="ti ti-settings" style="font-size: 20px; color: var(--used-color);"></i>

                        </a>
                        <div class="dropdown-menu dash-h-dropdown">
                            @if (Gate::check('manage company settings'))
                                <a href="{{ route('settings') }}" class="dropdown-item">
                                    <i class="ti ti-settings text-dark"></i><span>{{ __('System Settings') }}</span>
                                </a>
                            @endif
                            @if (Gate::check('manage company plan'))
                                <a href="{{ route('plans.index') }}" class="dropdown-item">
                                    <i
                                        class="ti ti-credit-card text-dark"></i><span>{{ __('Setup Subscription Plan') }}</span>
                                </a>
                            @endif
                            @if (Gate::check('manage order') && Auth::user()->type == 'company')
                                <a href="{{ route('order.index') }}" class="dropdown-item">
                                    <i class="ti ti-shopping-cart text-dark"></i><span>{{ __('Order') }}</span>
                                </a>
                            @endif
                        </div>
                    </li>

                @endif
            @endif
            <style>
                .icon_header {
                    font-size: 25px !important;
                    color: #525b69 !important;
                    /* padding: 5px; */
                }
            </style>

            @if (\Auth::user()->type != 'super admin')
                @if (Gate::check('manage company plan') || Gate::check('manage order') || Gate::check('manage company settings'))
                    <li class="dropdown dash-h-item drp-company">
                        <a class="dash-head-link dropdown-toggle arrow-none me-0 " data-bs-toggle="dropdown"
                            href="#" role="button" aria-haspopup="false" aria-expanded="false">
                            <i class="ti ti-settings icon_header"></i>

                        </a>
                        <div class="dropdown-menu dash-h-dropdown">
                            @if (Gate::check('manage company settings'))
                                <a href="{{ route('settings') }}" class="dropdown-item">
                                    <i class="ti ti-settings text-dark "></i><span>{{ __('System Settings') }}</span>
                                </a>
                            @endif
                            @if (Gate::check('manage company plan'))
                                <a href="{{ route('plans.index') }}" class="dropdown-item">
                                    <i
                                        class="ti ti-credit-card text-dark"></i><span>{{ __('Setup Subscription Plan') }}</span>
                                </a>
                            @endif
                            @if (Gate::check('manage order') && Auth::user()->type == 'company')
                                <a href="{{ route('order.index') }}" class="dropdown-item">
                                    <i class="ti ti-shopping-cart text-dark"></i><span>{{ __('Order') }}</span>
                                </a>
                            @endif
                        </div>
                    </li>

                @endif
            @endif
            <div id="notification-dropdown-content"
                class="dropdown-content dropdown-menu dropdown-menu-end dash-h-dropdown"
                style="position: absolute ; right: 12% !important;padding: 10px; background: transparent; box-shadow: none;">
                <ul class="dropdown-menu dropdown-menu-end notification-content noti"
                    aria-labelledby="notificationsDropdown" style=" top: 2px !important; left: 4px;">
                    <li style="max-width: 300px;">
                        @foreach (@$notifications as $notification)
                            @php
                                echo $notification->toHtml();
                            @endphp
                        @endforeach
                    </li>
                    {{-- <li>
                        <a href="#" class="dropdown-item text-center">View All Notifications</a>
                    </li> --}}
                </ul>
            </div>
            <li class="dropdown dash-h-item drp-company">
                <a class="dash-head-link dropdown-toggle arrow-none me-0 border_none" data-bs-toggle="dropdown"
                    href="#" role="button" aria-haspopup="false" aria-expanded="false">
                    <span class="theme-avtar">
                        <img src="{{ !empty(\Auth::user()->avatar) ? $profile . \Auth::user()->avatar : $profile . 'avatar.png' }}"
                            class="img-fluid rounded-circle">
                    </span>
                </a>
                <div class="dropdown-menu dash-h-dropdown">

                    <a href="{{ route('profile') }}" class="dropdown-item">
                        <i class="ti ti-user text-dark"></i><span>{{ __('Profile') }}</span>
                    </a>

                    <a href="{{ route('logout') }}"
                        onclick="event.preventDefault(); document.getElementById('frm-logout').submit();"
                        class="dropdown-item">
                        <i class="ti ti-power text-dark"></i><span>{{ __('Logout') }}</span>
                    </a>

                    <form id="frm-logout" action="{{ route('logout') }}" method="POST" class="d-none">
                        {{ csrf_field() }}
                    </form>

                </div>
            </li>
        </ul>
    </div>
</div>

<!-- Notification Modal -->
{{-- <div class="modal fade" id="notificationModal" tabindex="-1" role="dialog" aria-labelledby="notificationModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="notificationModalLabel">Notification Action</h5>
                </div>
                <div class="modal-body">
                    <p>Would you like to approve or reject this notification?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Reject</button>
                    <a id="approveBtn" href="#" class="btn btn-success">Approve</a>
                </div>
            </div>
        </div>
    </div> --}}
</header>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        function getBaseUrl() {
            const {
                protocol,
                host,
                pathname
            } = window.location;
            const pathArray = pathname.split('/').filter(part => part);
            const basePath = pathArray.length > 0 ? '' : '';
            return `${protocol}//${host}${basePath}`;
        }

        function markAsSeen(notificationId) {
            const notification = $(`.notification-item[data-id="${notificationId}"]`);
            notification.css('opacity', '0.7');

            setTimeout(() => notification.addClass('d-none'), 500);

            const baseUrl = getBaseUrl();
            $.ajax({
                url: `${baseUrl}/has_Seen/${notificationId}`,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    console.log('Notification marked as seen:', response);
                },
                error: function(xhr, status, error) {
                    console.error('Error marking notification as seen:', error);
                }
            });
        }

        // Handle approve/reject button clicks
        document.querySelectorAll('.notification_model form').forEach(function(form) {
            form.addEventListener('submit', function(event) {
                event.preventDefault();

                const formData = new FormData(this);
                const actionUrl = this.getAttribute('action');
                const button = this.querySelector('button[type="submit"]');
                const originalText = button.textContent;
                const notificationElement = this.closest('.notification_model');
                const notificationId = notificationElement.getAttribute('data-notificationId');

                // Disable button and show loading state
                button.disabled = true;
                button.textContent = 'Processing...';

                $.ajax({
                    url: actionUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        // Mark notification as seen
                        markAsSeen(notificationId);

                        // Show success message
                        if (response.message) {
                            alert(response.message);
                        } else {
                            alert('Action processed successfully.');
                        }

                        // Remove the notification from view
                        notificationElement.style.opacity = '0.5';
                        setTimeout(() => {
                            notificationElement.remove();
                        }, 300);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error processing action:', error);

                        // Re-enable button
                        button.disabled = false;
                        button.textContent = originalText;

                        // Show error message
                        let errorMessage = 'An error occurred while processing the action.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        alert(errorMessage);
                    }
                });
            });
        });

        // Prevent event bubbling for buttons
        document.querySelectorAll('.notification_model button').forEach(function(button) {
            button.addEventListener('click', function(event) {
                event.stopPropagation();
            });
        });
    });
</script>
