@extends('layouts.admin')
@section('page-title')
    <h3 class="m-b-10" style="
    margin: 10px 0px;
    font-size: 24px;
    color: #333333;
    font-weight: 500;
">    Vendors
</h3>
@endsection
@section('css')
    <style>
        .sc:hover{
            border-bottom: 6px solid rgba(0, 0, 0, 0.35);
            height: 68px;
            margin: 0px;
        }
        .dropdown-toggle::after {
            /* This removes the element entirely */
            content: none !important; 
            
            /* You can technically remove the rest of these lines 
            as they won't render without content, 
            but keeping them clean is better: */
            display: none !important; 
            margin-left: 0 !important;
            vertical-align: 0 !important;
            border: none !important;
        }
    </style>
@endsection

@section('action-btn')
    <div class="d-flex align-items-center justify-content-end gap-2">
        <!-- Give Feedback -->
        <a href="#" class="fw-bold me-4 text-decoration-none" style="color: #00892E; font-size: 16px;">
            <svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="currentColor" focusable="false" aria-hidden="true"><path d="M14.35 2a1 1 0 0 1 0 2H6.49a2.54 2.54 0 0 0-2.57 2.5v7A2.538 2.538 0 0 0 6.49 16h1.43a1 1 0 0 1 1 1v1.74l2.727-2.48c.184-.167.424-.26.673-.26h5.03a2.538 2.538 0 0 0 2.57-2.5v-4a1 1 0 0 1 2 0v4a4.54 4.54 0 0 1-4.57 4.5h-4.643l-4.114 3.74A1.002 1.002 0 0 1 6.92 21v-3h-.43a4.541 4.541 0 0 1-4.57-4.5v-7A4.541 4.541 0 0 1 6.49 2h7.86Zm6.414.6.725.726c.79.791.79 2.074 0 2.865l-5.812 5.794c-.128.128-.29.219-.465.263l-2.9.721a.998.998 0 0 1-1.215-1.213l.728-2.9a.993.993 0 0 1 .264-.463L17.9 2.6a2.027 2.027 0 0 1 2.864 0Zm-1.412 1.413-.763.724L13.7 9.612l-.255 1.015 1.016-.252 5.616-5.6V4.74l-.725-.727Z" fill="currentColor"></path></svg>
            {{ __('Give feedback') }}
        </a>

        <!-- Prepare 1099s Group -->
        <div class="btn-group">
            <button type="button" class="btn btn-light" style="border: 2px solid #00892E; color: #00892E; background-color: white; font-weight: 600;">
                {{ __('Pay vendors') }}
            </button>
            <button type="button" class="btn btn-light dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false" style="border: 2px solid #00892E; border-left: none; color: #00892E; background-color: white;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" color="currentColor" width="24px" height="24px" focusable="false" aria-hidden="true" class="btnIcon"><path fill="currentColor" d="M12.014 16.018a1 1 0 0 1-.708-.294L5.314 9.715A1.001 1.001 0 0 1 6.73 8.3l5.286 5.3 5.3-5.285a1 1 0 0 1 1.413 1.416l-6.009 5.995a1 1 0 0 1-.706.292"></path></svg>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#">{{ __('Prepare 1099s') }}</a></li>
                <li><a class="dropdown-item" href="#">{{ __('Order checks') }}</a></li>
            </ul>
        </div>

        <!-- New Vendor Group -->
        <div class="btn-group ms-2">
             <a href="#" data-size="lg" data-url="{{ route('vender.create') }}" data-ajax-popup="true" data-title="{{__('Create New Vendor')}}" class="btn btn-success" style="background-color: #00892E; border-color: #00892E; font-weight: 600;">
                {{__('New Vendor')}}
            </a>
            <button type="button" class="btn btn-success dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: #00892E; border-color: #00892E; border-left: 1px solid rgba(255,255,255,0.3);">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" color="currentColor" width="24px" height="24px" focusable="false" aria-hidden="true" class="btnIcon"><path fill="currentColor" d="M12.014 16.018a1 1 0 0 1-.708-.294L5.314 9.715A1.001 1.001 0 0 1 6.73 8.3l5.286 5.3 5.3-5.285a1 1 0 0 1 1.413 1.416l-6.009 5.995a1 1 0 0 1-.706.292"></path></svg>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="#">{{ __('Import Vendors') }}</a></li>
                <li><a class="dropdown-item" href="#">{{ __('Multiple Vendors') }}</a></li>
            </ul>
        </div>
    </div>
@endsection

@section('content')

<!-- Style -->
     <style>
        .sc1:hover{
            border-bottom: 6px solid rgba(0, 0, 0, 0.35);
            height: 68px;
            margin: 0px;
        }
        .sc2:hover{
            border-bottom: 6px solid rgba(0, 0, 0, 0.35);
            height: 68px;
            margin: 0px;
        }
        .sc3:hover{
            border-bottom: 6px solid rgba(0, 0, 0, 0.35);
            height: 68px;
            margin: 0px;
        }
        .sc4:hover{
            border-bottom: 6px solid rgba(0, 0, 0, 0.35);
            height: 68px;
            margin: 0px;
        }
        .dropdown-toggle::after {
            /* This removes the element entirely */
            content: none !important; 
            
            /* You can technically remove the rest of these lines 
            as they won't render without content, 
            but keeping them clean is better: */
            display: none !important; 
            margin-left: 0 !important;
            vertical-align: 0 !important;
            border: none !important;
        }
        body.theme-6 .form-check-input:focus, body.theme-6 .form-select:focus, body.theme-6 .form-control:focus, body.theme-6 .custom-select:focus, body.theme-6 .dataTable-selector:focus, body.theme-6 .dataTable-input:focus {
            border-color: #00892E;
            box-shadow: 0 0 0 0 #00892E;
        }
        body.theme-6 .form-check-input:checked {
            background-color: #00892E;
            border-color: #00892E;
        }

    /* strip behind the 4 summary cards */
    .vendor-summary-bar{
        background: transparent;
        padding:8px 0 0;
        border-radius:0;
    }

    .summary-card{
        cursor:pointer;
    }

/* slimmer grey strip above cards */
.vendor-summary-bar{
    background: transparent;
    padding:2px 0 0;   /* was 8px */
    border-radius:0;
}

/* small label, aligned to the RIGHT of each card */
.summary-label{
    font-size:12px;       /* a bit smaller */
    color:#6B6C72;
    margin:0 12px 2px 0;  /* right margin instead of left */
    line-height:1;
    display:block;
    text-align:right;     /* push text to the right side */
}


    /* colored card itself */
    .summary-box{
        padding:12px 16px 10px;
        color:#fff;
        position:relative;
        border-radius:0;
        border-right:1px solid rgba(255,255,255,.25);
        transition:all .15s ease-in-out;
    }

    .summary-box h3{
        margin:0;
        font-size:20px;
        font-weight:700;
    }

    .summary-box small{
        font-size:11px;
    }

    .summary-card:last-child .summary-box{
        border-right:none;
    }

    /* hover – only when not active */
    .summary-card:not(.active) .summary-box:hover{
        box-shadow:0 6px 0 rgba(0,0,0,.35);
        transform:translateY(-1px);
    }

    /* active filter – “pressed / 2D” look */
    .summary-card.active .summary-box{
        box-shadow:none;
        transform:translateY(2px);
    }

    /* remove the bootstrap caret in split buttons (you already had this) */
    .dropdown-toggle::after{
        content:none !important;
        display:none !important;
        margin-left:0 !important;
        vertical-align:0 !important;
        border:none !important;
    }

    body.theme-6 .form-check-input:focus,
    body.theme-6 .form-select:focus,
    body.theme-6 .form-control:focus,
    body.theme-6 .custom-select:focus,
    body.theme-6 .dataTable-selector:focus,
    body.theme-6 .dataTable-input:focus{
        border-color:#00892E;
        box-shadow:0 0 0 0 #00892E;
    }
    body.theme-6 .form-check-input:checked{
        background-color:#00892E;
        border-color:#00892E;
    }
    /* hover – only when not active */
.summary-card:not(.active) .summary-box:hover {
    box-shadow: 0 6px 0 rgba(0, 0, 0, .35);
    transform: translateY(-1px);
}

/* active – same look as hover */
.summary-card.active .summary-box {
    box-shadow: 0 6px 0 rgba(0, 0, 0, .35);
    transform: translateY(-1px);
}

</style>


<!-- style end -->
<div class="row mb-4 mt-4">
    <div class="col-12">
        <div class="card shadow-none bg-transparent border-0 mb-0">
            <div class="card-body p-0 vendor-summary-bar collapse show" id="summary-accordion">
                <div class="row g-1">
                    {{-- Unbilled --}}
                    <div class="col-md-4 col-sm-6 summary-card" data-filter="unbilled">
                        <div class="summary-label">Unbilled Last 365 Days</div>
                        <div class="summary-box" style="background-color:#0077c5;">
                            <h3 class="text-white fw-bold">
                                {{ \Auth::user()->priceFormat($purchaseOrderAmount) }}
                            </h3>
                            <small>{{ $purchaseOrderCount }} PURCHASE ORDER</small>
                            <div class="active-indicator d-none"></div>
                        </div>
                    </div>

                    {{-- Overdue --}}
                    <div class="col-md-2 col-sm-6 summary-card" data-filter="overdue">
                        <div class="summary-label">Unpaid Last 365 Days</div>
                        <div class="summary-box" style="background-color:#ff8000;">
                            <h3 class="text-white fw-bold">
                                {{ \Auth::user()->priceFormat($overdueAmount) }}
                            </h3>
                            <small>{{ $overdueCount }} OVERDUE</small>
                            <div class="active-indicator d-none"></div>
                        </div>
                    </div>

                    {{-- Open bills --}}
                    <div class="col-md-2 col-sm-6 summary-card" data-filter="open">
                        <div class="summary-label">&nbsp;</div>
                        <div class="summary-box" style="background-color:#babbbc;">
                            <h3 class="text-white fw-bold">
                                {{ \Auth::user()->priceFormat($openBillAmount) }}
                            </h3>
                            <small>{{ $openBillCount }} OPEN BILLS</small>
                            <div class="active-indicator d-none"></div>
                        </div>
                    </div>

                    {{-- Paid --}}
                    <div class="col-md-4 col-sm-6 summary-card" data-filter="paid">
                        <div class="summary-label">Paid</div>
                        <div class="summary-box" style="background-color:#88c306;">
                            <h3 class="text-white fw-bold">
                                {{ \Auth::user()->priceFormat($paidAmount) }}
                            </h3>
                            <small>{{ $paidCount }} PAID LAST 30 DAYS</small>
                            <div class="active-indicator d-none"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end mb-2">
                <button class="btn btn-sm btn-light"
                        style="background-color:#E3E5E8 !important;"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#summary-accordion"
                        aria-expanded="true"
                        aria-controls="summary-accordion"
                        id="accordion-toggle">
                    <i class="ti ti-chevron-up"></i>
                </button>
            </div>
        </div>
    </div>
</div>

    <div class="row mb-3 align-items-center">
        <div class="col-12 mb-2 d-none" id="filter-bar">
            <span class="text-muted">Filter:</span> 
            <span class="badge bg-primary p-2 ms-1" id="active-filter-name" style="font-size: 12px; border-radius: 2px;"></span>
            <a href="#" class="ms-2 text-primary small" id="clear-filter" style="text-decoration: none;">Clear filter / View all</a>
        </div>
        <div class="col-md-4">
            <div class="input-group">
                <!-- <span class="input-group-text bg-white border-end-0 ps-3" id="basic-addon1"><i class="ti ti-search text-muted"></i></span> -->
                <input type="text" class="form-control" id="custom-search-input" placeholder="Search" aria-label="Search" aria-describedby="basic-addon1">
            </div>
        </div>
        <div class="col-md-8 text-end">
            <a href="#" class="btn btn-link text-muted p-1" data-bs-toggle="tooltip" title="{{ __('Print') }}"><i class="ti ti-printer fs-4"></i></a>
            <a href="{{ route('vender.export') }}" class="btn btn-link text-muted p-1" data-bs-toggle="tooltip" title="{{ __('Export') }}"><i class="ti ti-file-export fs-4"></i></a>
            <a href="#" class="btn btn-link text-muted p-1" data-bs-toggle="tooltip" title="{{ __('Settings') }}"><i class="ti ti-settings fs-4"></i></a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="border-0" style="border-radius: 8px;">
                <div class="card-body table-border-style p-0">
                    <div class="table-responsive">
                        {{ $dataTable->table(['class' => 'table table-hover align-middle mb-0', 'style' => 'width:100%;']) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css-page')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
@endpush

@push('script-page')
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    {{ $dataTable->scripts() }}
    <script>
        $(document).ready(function() {
            // Hide default search
            $('.dataTables_filter').hide();
            
            // Custom search
            $('#custom-search-input').on('keyup', function() {
                $('#vendors-table').DataTable().search($(this).val()).draw();
            });

            // Handle select all checkbox
            $(document).on('click', '#select-all', function() {
                $('.row-checkbox').prop('checked', this.checked);
            });

            // Accordion Toggle Icon
            $('#summary-accordion').on('shown.bs.collapse', function () {
                $('#accordion-toggle i').removeClass('ti-chevron-down').addClass('ti-chevron-up');
            });
            $('#summary-accordion').on('hidden.bs.collapse', function () {
                $('#accordion-toggle i').removeClass('ti-chevron-up').addClass('ti-chevron-down');
            });

// Filter Logic
$('.summary-card').on('click', function () {
    var filter = $(this).data('filter');
    var filterName = '';

    // active visual state
    $('.summary-card').removeClass('active');
    $(this).addClass('active');

    $('.summary-card .active-indicator').addClass('d-none');
    $(this).find('.active-indicator').removeClass('d-none');

    // label in filter bar
    if (filter === 'unbilled') filterName = 'PURCHASE ORDERS';
    else if (filter === 'overdue') filterName = 'OVERDUE';
    else if (filter === 'open') filterName = 'OPEN BILLS';
    else if (filter === 'paid') filterName = 'RECENTLY PAID';

    $('#active-filter-name').text(filterName);
    $('#filter-bar').removeClass('d-none');

    // send filter param with ajax
    $('#vendors-table').on('preXhr.dt', function (e, settings, data) {
        data.filter = filter;
    });

    $('#vendors-table').DataTable().ajax.reload();
});

// Clear Filter
$('#clear-filter').on('click', function (e) {
    e.preventDefault();

    $('.summary-card').removeClass('active');
    $('.summary-card .active-indicator').addClass('d-none');
    $('#filter-bar').addClass('d-none');

    $('#vendors-table').on('preXhr.dt', function (e, settings, data) {
        delete data.filter;
    });
    $('#vendors-table').DataTable().ajax.reload();
});

        });
    </script>
@endpush
