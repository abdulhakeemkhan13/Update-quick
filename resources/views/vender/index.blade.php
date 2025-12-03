@extends('layouts.admin')
@section('page-title')
    {{ __('Vendors') }}
@endsection
{{-- Breadcrumbs removed as requested --}}

@section('action-btn')
    <div class="float-end">
        <a href="#" class="btn btn-outline-secondary me-2" style="border-radius: 4px;">{{ __('Prepare 1099s') }} <i class="ti ti-chevron-down ms-1"></i></a>
        <a href="#" data-size="lg" data-url="{{ route('vender.create') }}" data-ajax-popup="true" data-title="{{__('Create New Vendor')}}" class="btn btn-success" style="background-color: #2ca01c; border-color: #2ca01c; border-radius: 4px;">
            {{__('New Vendor')}}
        </a>
    </div>
@endsection

@section('content')
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-none bg-transparent border-0 mb-0">
                <div class="card-body p-0">
                    <div class="d-flex justify-content-end mb-2">
                        <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#summary-accordion" aria-expanded="true" aria-controls="summary-accordion" id="accordion-toggle">
                            <i class="ti ti-chevron-up"></i>
                        </button>
                    </div>
                    <div class="collapse show" id="summary-accordion">
                        <div class="row g-0">
                            <style>
                                .sc:hover{
                                    border-bottom: 6px solid rgba(0, 0, 0, 0.35);
    height: 68px;
    margin: 0px;
                                }
                            </style>
                            <div class="col-md-3 col-sm-6 summary-card" data-filter="unbilled" style="cursor: pointer;">
                                <div class="p-3 h-100 position-relative sc" style="background-color: #0077c5; color: white; border-right: 1px solid rgba(255,255,255,0.2);">
                                    <div class="mb-2" style="font-size: 13px;">Unbilled Last 365 Days</div>
                                    <h3 class="mb-0 text-white fw-bold">{{ \Auth::user()->priceFormat($purchaseOrderAmount) }}</h3>
                                    <small style="font-size: 11px;">{{ $purchaseOrderCount }} PURCHASE ORDER</small>
                                    <div class="active-indicator d-none" style="position: absolute; bottom: -10px; left: 50%; transform: translateX(-50%); width: 0; height: 0; border-left: 10px solid transparent; border-right: 10px solid transparent; border-top: 10px solid #0077c5;"></div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 summary-card" data-filter="overdue" style="cursor: pointer;">
                                <div class="p-3 h-100 position-relative sc" style="background-color: #ff8000; color: white; border-right: 1px solid rgba(255,255,255,0.2);">
                                    <div class="mb-2" style="font-size: 13px;">Unpaid Last 365 Days</div>
                                    <h3 class="mb-0 text-white fw-bold">{{ \Auth::user()->priceFormat($overdueAmount) }}</h3>
                                    <small style="font-size: 11px;">{{ $overdueCount }} OVERDUE</small>
                                    <div class="active-indicator d-none" style="position: absolute; bottom: -10px; left: 50%; transform: translateX(-50%); width: 0; height: 0; border-left: 10px solid transparent; border-right: 10px solid transparent; border-top: 10px solid #ff8000;"></div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 summary-card" data-filter="open" style="cursor: pointer;">
                                <div class="p-3 h-100 position-relative sc" style="background-color: #babbbc; color: white; border-right: 1px solid rgba(255,255,255,0.2);">
                                    <div class="mb-2" style="font-size: 13px;">&nbsp;</div>
                                    <h3 class="mb-0 text-white fw-bold">{{ \Auth::user()->priceFormat($openBillAmount) }}</h3>
                                    <small style="font-size: 11px;">{{ $openBillCount }} OPEN BILLS</small>
                                    <div class="active-indicator d-none" style="position: absolute; bottom: -10px; left: 50%; transform: translateX(-50%); width: 0; height: 0; border-left: 10px solid transparent; border-right: 10px solid transparent; border-top: 10px solid #babbbc;"></div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 summary-card" data-filter="paid" style="cursor: pointer;">
                                <div class="p-3 h-100 position-relative sc" style="background-color: #88c306; color: white;">
                                    <div class="mb-2" style="font-size: 13px;">Paid</div>
                                    <h3 class="mb-0 text-white fw-bold">{{ \Auth::user()->priceFormat($paidAmount) }}</h3>
                                    <small style="font-size: 11px;">{{ $paidCount }} PAID LAST 30 DAYS</small>
                                    <div class="active-indicator d-none" style="position: absolute; bottom: -10px; left: 50%; transform: translateX(-50%); width: 0; height: 0; border-left: 10px solid transparent; border-right: 10px solid transparent; border-top: 10px solid #88c306;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
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
                <span class="input-group-text bg-white border-end-0 ps-3" id="basic-addon1"><i class="ti ti-search text-muted"></i></span>
                <input type="text" class="form-control border-start-0" id="custom-search-input" placeholder="Search" aria-label="Search" aria-describedby="basic-addon1">
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
            <div class="card shadow-sm border-0" style="border-radius: 8px;">
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
            $('.summary-card').on('click', function() {
                var filter = $(this).data('filter');
                var filterName = '';

                // Visual updates
                $('.summary-card .active-indicator').addClass('d-none');
                $(this).find('.active-indicator').removeClass('d-none');
                
                // Set filter name
                if(filter === 'unbilled') filterName = 'PURCHASE ORDERS';
                else if(filter === 'overdue') filterName = 'OVERDUE';
                else if(filter === 'open') filterName = 'OPEN BILLS';
                else if(filter === 'paid') filterName = 'RECENTLY PAID';

                $('#active-filter-name').text(filterName);
                $('#filter-bar').removeClass('d-none');

                // Reload DataTable
                $('#vendors-table').on('preXhr.dt', function ( e, settings, data ) {
                    data.filter = filter;
                });
                $('#vendors-table').DataTable().ajax.reload();
            });

            // Clear Filter
            $('#clear-filter').on('click', function(e) {
                e.preventDefault();
                $('.summary-card .active-indicator').addClass('d-none');
                $('#filter-bar').addClass('d-none');
                
                // Clear filter param
                $('#vendors-table').on('preXhr.dt', function ( e, settings, data ) {
                    delete data.filter;
                });
                $('#vendors-table').DataTable().ajax.reload();
            });
        });
    </script>
@endpush
