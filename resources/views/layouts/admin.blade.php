@php
    use App\Models\Utility;
    $setting = \App\Models\Utility::settings();

    $logo = \App\Models\Utility::get_file('uploads/logo');

    $company_favicon = $setting['company_favicon'] ?? '';

    $color = !empty($setting['color']) ? $setting['color'] : 'theme-3';

    if (isset($setting['color_flag']) && $setting['color_flag'] == 'true') {
        $themeColor = 'custom-color';
    } else {
        $themeColor = $color;
    }

    $SITE_RTL = $setting['SITE_RTL'] ?? '';

    $lang = \App::getLocale('lang');
    if ($lang == 'ar' || $lang == 'he') {
        $SITE_RTL = 'on';
    }

    $metatitle = isset($setting['meta_title']) ? $setting['meta_title'] : '';
    $metsdesc = isset($setting['meta_desc']) ? $setting['meta_desc'] : '';
    $meta_image = \App\Models\Utility::get_file('uploads/meta/');
    $meta_logo = isset($setting['meta_image']) ? $setting['meta_image'] : '';

@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $SITE_RTL == 'on' ? 'rtl' : '' }}">

<meta name="csrf-token" id="csrf-token" content="{{ csrf_token() }}">

<head>
    <title>{{ $setting['title_text'] ? $setting['title_text'] : config('app.name', 'Creative Suite') }} -
        @yield('page-title')
    </title>

    <meta name="title" content="{{ $metatitle }}">
    <meta name="description" content="{{ $metsdesc }}">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ env('APP_URL') }}">
    <meta property="og:title" content="{{ $metatitle }}">
    <meta property="og:description" content="{{ $metsdesc }}">
    <meta property="og:image" content="{{ $meta_image . $meta_logo }}">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ env('APP_URL') }}">
    <meta property="twitter:title" content="{{ $metatitle }}">
    <meta property="twitter:description" content="{{ $metsdesc }}">
    <meta property="twitter:image" content="{{ $meta_image . $meta_logo }}">


    <script src="{{ asset('js/html5shiv.js') }}"></script>

    {{--
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script> --}}

    <!-- Meta -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="url" content="{{ url('') . '/' . config('chatify.path') }}" data-user="{{ Auth::user()->id }}">
    <link rel="icon"
        href="{{ $logo . '/' . (isset($company_favicon) && !empty($company_favicon) ? $company_favicon : 'favicon.png') }}"
        type="image" sizes="16x16">


    <!-- Favicon icon -->
    {{--
    <link rel="icon" href="{{ asset('assets/images/favicon.svg') }}" type="image/x-icon" /> --}}
    <!-- Calendar-->
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/main.css') }}">

    <link rel="stylesheet" href="{{ asset('assets/css/plugins/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/flatpickr.min.css') }}">

    <link rel="stylesheet" href="{{ asset('assets/css/plugins/animate.min.css') }}">

    <!-- font css -->
    <link rel="stylesheet" href="{{ asset('assets/fonts/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/feather.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/fontawesome.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/material.css') }}">

    <!--bootstrap switch-->
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/bootstrap-switch-button.min.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- vendor css -->

    @if ($SITE_RTL == 'on')
        <link rel="stylesheet" href="{{ asset('assets/css/style-rtl.css') }}">
    @endif

    @if ($setting['cust_darklayout'] == 'on')
        <link rel="stylesheet" href="{{ asset('assets/css/style-dark.css') }}" id="main-style-link">
    @endif

    @if ($SITE_RTL != 'on' && $setting['cust_darklayout'] != 'on')
        <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" id="main-style-link">
    @endif

    <link rel="stylesheet" href="{{ asset('assets/css/customizer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

    @if ($setting['cust_darklayout'] == 'on')
        <link rel="stylesheet" href="{{ asset('css/custom-dark.css') }}">
    @endif

    <style>
        :root {
            --color-customColor:
                <?= $color ?>
            ;
        }

        /* Make modal behave like a drawer */
        #globalAddNewModal.modal {
            padding: 0 !important;
        }

        /* Drawer container (modal-dialog) */
        #globalAddNewModal .modal-dialog {
            position: fixed !important;
            top: 0 !important;
            bottom: 0 !important;
            right: -520px !important; /* start hidden */
            margin: 0 !important;
            height: 100%;
            width: 520px !important;
            max-width: 520px !important;

            transform: none !important; /* override BS5 fade */
            transition: right 0.35s ease-in-out;
        }

        /* When modal is shown → slide in */
        #globalAddNewModal.show .modal-dialog {
            right: 0 !important;
        }

        /* Drawer content styling */
        #globalAddNewModal .modal-content {
            height: 100%;
            border-radius: 0 !important;
            border: none !important;
            overflow-y: auto;
            box-shadow: -2px 0 12px rgba(0,0,0,0.18);
        }

        /* Clean header/body */
        #globalAddNewModal .modal-header {
            border-bottom: 1px solid #ddd;
        }

        #globalAddNewModal .modal-body {
            padding: 16px;
        }

        /* Backdrop override */
        .modal-backdrop.show {
            opacity: 0.35 !important;
        }

        /* Kill Bootstrap’s fade transform interference */
        #globalAddNewModal.fade .modal-dialog {
            transform: none !important;
        }


    </style>

    <link rel="stylesheet" href="{{ asset('css/custom-color.css') }}">
    @stack('css-page')


</head>



<body class="{{ $themeColor }}">

    <!-- [ Pre-loader ] start -->
    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>

    @include('partials.admin.menu')
    <!-- [ navigation menu ] end -->
    <!-- [ Header ] start -->
    @include('partials.admin.header')

    <!-- Modal -->
    <div class="modal notification-modal fade" id="notification-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <button type="button" class="btn-close float-end" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                    <h6 class="mt-2">
                        <i data-feather="monitor" class="me-2"></i>Desktop settings
                    </h6>
                    <hr />
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="pcsetting1" checked />
                        <label class="form-check-label f-w-600 pl-1" for="pcsetting1">Allow desktop notification</label>
                    </div>
                    <p class="text-muted ms-5">
                        you get lettest content at a time when data will updated
                    </p>
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="pcsetting2" />
                        <label class="form-check-label f-w-600 pl-1" for="pcsetting2">Store Cookie</label>
                    </div>
                    <h6 class="mb-0 mt-5">
                        <i data-feather="save" class="me-2"></i>Application settings
                    </h6>
                    <hr />
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="pcsetting3" />
                        <label class="form-check-label f-w-600 pl-1" for="pcsetting3">Backup Storage</label>
                    </div>
                    <p class="text-muted mb-4 ms-5">
                        Automaticaly take backup as par schedule
                    </p>
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="pcsetting4" />
                        <label class="form-check-label f-w-600 pl-1" for="pcsetting4">Allow guest to print
                            file</label>
                    </div>
                    <h6 class="mb-0 mt-5">
                        <i data-feather="cpu" class="me-2"></i>System settings
                    </h6>
                    <hr />
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="pcsetting5" checked />
                        <label class="form-check-label f-w-600 pl-1" for="pcsetting5">View other user chat</label>
                    </div>
                    <p class="text-muted ms-5">Allow to show public user message</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-danger btn-sm" data-bs-dismiss="modal">
                        Close
                    </button>
                    <button type="button" class="btn btn-light-primary btn-sm">
                        Save changes
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- [ Header ] end -->

    <!-- [ Main Content ] start -->
    <div class="dash-container">
        <div class="dash-content">
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="page-header-title">
                                <h4 class="m-b-10">@yield('page-title')</h4>
                            </div>
                            <ul class="breadcrumb">
                                @yield('breadcrumb')
                            </ul>
                        </div>
                        <div class="col action-btn-col">
                            @yield('action-btn')
                        </div>
                    </div>
                </div>
            </div>
            @yield('content')
            <!-- [ Main Content ] end -->
        </div>
    </div>
    <div class="modal fade" id="commonModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="body">
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="commonModalOver" tabindex="-1" role="dialog" aria-labelledby="commonModalLabel"
        aria-hidden="true">
        <div class="modal-dialog " role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="commonModalLabel"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                </div>
            </div>
        </div>
    </div>
    

    <div class="position-fixed top-0 end-0 p-3" style="z-index: 99999">
        <div id="liveToast" class="toast text-white fade" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>
    </div>
    @include('partials.admin.footer')
    @include('Chatify::layouts.footerLinks')
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let modalDialog = modal.find('.modal-dialog');
        if ($(this).data('size') === 'fullscreen') {
            modalDialog.addClass('modal-fullscreen');
        } else {
            modalDialog.removeClass('modal-fullscreen');
        }
        $(document).on('click', '[data-ajax-popup="true"]', function(e) {
            e.preventDefault();

            let url = $(this).data('url');
            let title = $(this).data('title') || '';

            let modal = $('#commonModalOver');
            modal.find('.modal-title').text(title);
            modal.find('.modal-body').html('<div class="text-center py-5">Loading...</div>'); // optional loader

            $.ajax({
                url: url,
                method: 'GET',
                success: function(data) {
                    modal.find('.modal-body').html(data);
                    modal.modal('show');
                },
                error: function() {
                    modal.find('.modal-body').html('<p class="text-danger text-center">Error loading content.</p>');
                }
            });
        });


        //new export function

        function exportDataTable(tableId, pageTitle, format = "excel") {
            let table = $('#' + tableId).DataTable();

            // Get visible columns
            let columns = [];
            $('#' + tableId + ' thead th:visible').each(function () {
                columns.push($(this).text().trim());
            });

            // Get visible data rows
            let data = [];
            table.rows({ search: 'applied' }).every(function () {
                let rowData = this.data();
                if (typeof rowData === 'object') {
                    let rowArray = [];
                    table.columns(':visible').every(function (colIdx) {
                        let val = rowData[this.dataSrc()] ?? '-';
                        rowArray.push(val);
                    });
                    rowData = rowArray;
                }
                data.push(rowData);
            });

            // console.log(data)
            if (data.length === 0) {
                alert('No Data Found')
                return
            }
            // Send as JSON string to avoid PHP input limit
            $.ajax({
                url: '{{ route('export.datatable') }}',
                method: 'POST',
                contentType: 'application/json', // Important!
                data: JSON.stringify({
                    columns: columns,
                    data: data,
                    pageTitle: pageTitle,
                    format: format,
                    _token: '{{ csrf_token() }}'
                }),
                xhrFields: { responseType: 'blob' },
                success: function (blob, status, xhr) {
                    let filename = xhr.getResponseHeader('Content-Disposition')
                        ?.split('filename=')[1]
                        ?.replace(/"/g, '') ?? `${pageTitle}.xlsx`;

                    if (format === "print") {
                        let fileURL = URL.createObjectURL(blob);
                        let printWindow = window.open(fileURL);
                        printWindow.onload = function () {
                            printWindow.focus();
                            printWindow.print();
                        };
                    } else {
                        let link = document.createElement('a');
                        link.href = window.URL.createObjectURL(blob);
                        link.download = filename;
                        link.click();
                    }
                },
                error: function (xhr) {
                    console.error('Export failed:', xhr.responseText);
                    alert('Export failed! Check console.');
                }
            });
        }
    </script>

</body>

</html>