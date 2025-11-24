@extends('layouts.admin')

@section('page-title')
    {{ __('Customer Phone List') }}
@endsection

@section('content')
    <style>
        /* ===== Base / Layout ===== */
        .quickbooks-report{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f6fa;min-height:100vh;color:#262626;}
        /* Header */
        .report-header{background:#fff;padding:16px 24px;border-bottom:1px solid #e6e6e6;display:flex;justify-content:space-between;align-items:center;}
        .report-header h4{margin:0;font-size:18px;font-weight:600;}
        .header-actions{display:flex;align-items:center;gap:16px;}
        .last-updated{color:#6b7280;font-size:13px;}
        .actions{display:flex;align-items:center;gap:8px;}
        .btn{border:none;border-radius:4px;padding:8px 12px;font-size:13px;cursor:pointer;display:flex;align-items:center;gap:6px;transition:.2s;}
        .btn-icon{background:transparent;color:#6b7280;width:32px;height:32px;justify-content:center;}
        .btn-icon:hover{background:#f3f4f6;color:#262626;}
        .btn-success{background:#22c55e;color:#fff;font-weight:500;}
        .btn-success:hover{background:#16a34a;}
        .btn-save{padding:8px 16px;}

        /* ===== Controls row (one line, no wrapping) ===== */
        .controls-bar{background:#fff;padding:12px 24px;border-bottom:1px solid #e6e6e6;}
        .controls-inner{display:flex;align-items:flex-end;gap:12px;white-space:nowrap;overflow:hidden;}
        .controls-spacer{flex:1 1 auto;} /* pushes right controls to the end */
        .btn-outline{background:#fff;color:#374151;padding:8px 12px;font-size:13px;border:1px solid #d1d5db;border-radius:4px;}
        .btn-outline:hover{background:#f9fafb;border-color:#9ca3af;}
        .left-controls,.right-controls{display:flex;align-items:flex-end;gap:8px;flex-wrap:nowrap;}
        .filter-item{display:flex;flex-direction:column;gap:4px;}
        .filter-label{font-size:12px;color:#374151;font-weight:600;}
        .form-select,.form-control{border:1px solid #d1d5db;border-radius:4px;padding:6px 8px;font-size:13px;height:34px;background:#fff;color:#374151;}
        .btn-qb-option{background:transparent;border:none;color:#0066cc;padding:0 8px 6px;cursor:pointer;display:inline-flex;align-items:center;}
        .btn-qb-option i{margin-right:6px;font-size:12px;}

        /* Report content */
        .report-content{background:#fff;margin:24px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.1);overflow:hidden;}
        .report-title-section{text-align:center;padding:32px 24px 24px;border-bottom:1px solid #e6e6e6;}
        .report-title{font-size:24px;font-weight:700;margin:0 0 8px;}
        .company-name{font-size:16px;color:#6b7280;margin:0 0 12px;}
        .date-range{font-size:14px;color:#374151;margin:0;}

        /* Table */
        .table-container{overflow-x:auto;background:#fff;}
        .customer-contact-table{width:100%;border-collapse:collapse;font-size:13px;}
        .customer-contact-table th{background:#f9fafb;border-bottom:2px solid #e5e7eb;padding:12px 16px;text-align:left;font-weight:600;color:#374151;font-size:12px;text-transform:uppercase;letter-spacing:.025em;}
        .customer-contact-table td{padding:12px 16px;border-bottom:1px solid #f3f4f6;color:#262626;}
        .customer-contact-table tbody tr:hover{background:#f9fafb;}

        /* ===== Drawer-style Modals ===== */
        .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1050;overflow-y:auto;}
        .filter-modal,.general-options-modal,.columns-modal,.view-options-modal{background:#fff;margin:50px auto;width:90%;max-width:600px;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,.3);}
        .modal-header{padding:20px 25px 15px;border-bottom:1px solid #e9ecef;display:flex;justify-content:space-between;align-items:center;}
        .modal-header h5{margin:0;font-size:18px;font-weight:600;color:#2c3e50;}
        .btn-close{background:none;border:none;font-size:24px;color:#999;cursor:pointer;padding:0;width:30px;height:30px;display:flex;align-items:center;justify-content:center;}
        .btn-close:hover{color:#666;}
        .modal-content{padding:20px 25px 25px;}
        .modal-subtitle{color:#666;margin-bottom:20px;font-size:14px;}
        .option-section{margin-bottom:20px;border:1px solid #e9ecef;border-radius:4px;}
        .section-title{background:#f8f9fa;padding:12px 15px;margin:0;font-size:14px;font-weight:600;color:#2c3e50;cursor:pointer;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #e9ecef;}
        .option-group{padding:15px;}
        .checkbox-label{display:flex;align-items:center;margin-bottom:10px;font-size:13px;color:#2c3e50;}
        .checkbox-label input{margin-right:8px;}

        /* Drawer override (slide from right) */
        .modal-overlay.drawer-open{display:block;}
        .modal-overlay.drawer-open .filter-modal,
        .modal-overlay.drawer-open .general-options-modal,
        .modal-overlay.drawer-open .columns-modal,
        .modal-overlay.drawer-open .view-options-modal{
            position:fixed;top:0;right:0;bottom:0;height:100%;width:360px;max-width:90vw;margin:0;border-radius:0;box-shadow:-2px 0 10px rgba(0,0,0,.1);overflow-y:auto;animation:slideInRight .18s ease-out;
        }
        @keyframes slideInRight{from{transform:translateX(20px);opacity:0;}to{transform:translateX(0);opacity:1;}}

        /* Print */
        @media print{
            .report-header,.controls-bar{display:none!important;}
            .quickbooks-report{background:#fff!important;}
            .report-content{box-shadow:none!important;margin:0!important;}
            .customer-contact-table{font-size:11px;}
            .customer-contact-table th,.customer-contact-table td{padding:6px 4px;}
        }
        @media(max-width:768px){.report-content{margin:12px;}}
    </style>

    <div class="quickbooks-report">
        <!-- Header with actions -->
        <div class="report-header">
            <h4 class="mb-0">{{ $pageTitle }}</h4>
            <div class="header-actions">
                <span class="last-updated">Last updated just now</span>
                <div class="actions">
                    <button class="btn btn-icon" title="Refresh" id="btn-refresh"><i class="fa fa-sync"></i></button>
                    <button class="btn btn-icon" title="Print" id="btn-print"><i class="fa fa-print"></i></button>
                    <button class="btn btn-icon" title="Export" id="btn-export"><i class="fa fa-external-link-alt"></i></button>
                    <button class="btn btn-icon" title="More options" id="btn-more"><i class="fa fa-ellipsis-v"></i></button>
                    <button class="btn btn-success btn-save" id="btn-save">Save As</button>
                </div>
            </div>
        </div>

        <!-- Controls row: one line -->
        <div class="controls-bar">
            <div class="controls-inner">
                <div class="left-controls">
                    <div class="filter-item">
                        <label class="filter-label">Report period</label>
                        <select class="form-select" id="report-period" style="width:130px">
                            <option value="all_dates">All Dates</option>
                            <option value="today">Today</option>
                            <option value="this_week">This week</option>
                            <option value="this_month">This month</option>
                            <option value="this_year">This year</option>
                            <option value="last_month">Last month</option>
                            <option value="last_7_days">Last 7 days</option>
                            <option value="last_30_days">Last 30 days</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label class="filter-label">From</label>
                        <input type="date" class="form-control" id="start-date" value="{{ $filter['startDateRange'] ?? '' }}" style="width:120px">
                    </div>
                    <div class="filter-item">
                        <label class="filter-label">To</label>
                        <input type="date" class="form-control" id="end-date" value="{{ $filter['endDateRange'] ?? '' }}" style="width:120px">
                    </div>
                    <div class="filter-item">
                        <label class="filter-label">Accounting method</label>
                        <select class="form-select" id="accounting-method" style="width:110px">
                            <option value="accrual" {{ ($filter['accountingMethod'] ?? 'accrual') == 'accrual' ? 'selected' : '' }}>Accrual</option>
                            <option value="cash" {{ ($filter['accountingMethod'] ?? 'accrual') == 'cash' ? 'selected' : '' }}>Cash</option>
                        </select>
                    </div>
                    <button class="btn btn-qb-option" id="view-options-btn"><i class="fa fa-eye"></i>View options</button>
                </div>

                <div class="controls-spacer"></div>

                <div class="right-controls">
                    <button class="btn btn-outline" id="columns-btn"><i class="fa fa-table-columns"></i> Columns</button>
                    <button class="btn btn-outline" id="filter-btn"><i class="fa fa-filter"></i> Filter</button>
                    <button class="btn btn-outline" id="general-options-btn"><i class="fa fa-cog"></i> General options</button>
                </div>
            </div>
        </div>

        <!-- Report -->
        <div class="report-content">
            <div class="report-title-section">
                <h2 class="report-title">{{ $pageTitle }}</h2>
                <p class="company-name">{{ config('app.name', 'Your Company Name') }}</p>
                <p class="date-range"><span id="date-range-display">
                    {{ ($filter['selectedCustomerName'] ?? '') ? ('Customer: ' . $filter['selectedCustomerName']) : 'All Customers' }}
                </span></p>
            </div>

            <div class="table-container p-2">
                {!! $dataTable->table(['class' => 'table customer-contact-table', 'id' => 'customer-contact-table']) !!}
            </div>
            <!-- Footer injected by JS -->
        </div>
    </div>

    <!-- Filter Modal -->
    <div class="modal-overlay" id="filter-overlay">
        <div class="filter-modal">
            <div class="modal-header">
                <h5>Filter <i class="fa fa-info-circle" title="Filter by customer name"></i></h5>
                <button type="button" class="btn-close" id="close-filter">&times;</button>
            </div>
            <div class="modal-content">
                <p class="modal-subtitle">Choose a customer to filter the report. Updates immediately.</p>
                <div class="filter-group">
                    <label for="filter-customer-name">Customer Name</label>
                    <select id="filter-customer-name" class="form-control">
                        <option value="">All Customers</option>
                        @foreach($customers as $cname)
                            <option value="{{ $cname }}" {{ ($filter['selectedCustomerName'] ?? '') === $cname ? 'selected' : '' }}>{{ $cname }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- General Options Modal -->
    <div class="modal-overlay" id="general-options-overlay">
        <div class="general-options-modal">
            <div class="modal-header">
                <h5>General options <i class="fa fa-info-circle" title="Configure report settings"></i></h5>
                <button type="button" class="btn-close" id="close-general-options">&times;</button>
            </div>
            <div class="modal-content">
                <p class="modal-subtitle">Select general options for your report.</p>

                <div class="option-section">
                    <h6 class="section-title">Number format <i class="fa fa-chevron-up"></i></h6>
                    <div class="option-group">
                        <label class="checkbox-label"><input type="checkbox" id="divide-by-1000"> Divide by 1000</label>
                        <label class="checkbox-label"><input type="checkbox" id="hide-zero-amounts"> Don't show zero amounts</label>
                        <label class="checkbox-label"><input type="checkbox" id="round-whole-numbers"> Round to whole numbers</label>
                    </div>
                </div>

                <div class="option-section">
                    <h6 class="section-title">Negative numbers <i class="fa fa-chevron-up"></i></h6>
                    <div class="option-group" style="display:flex;gap:12px;align-items:center;">
                        <label class="checkbox-label" style="margin:0;">
                            <span style="min-width:110px;display:inline-block;">Format</span>
                            <select id="negative-format" class="form-control" style="width:110px;">
                                <option value="-100" selected>-100</option>
                                <option value="(100)">(100)</option>
                                <option value="100-">100-</option>
                            </select>
                        </label>
                        <label class="checkbox-label" style="margin:0;"><input type="checkbox" id="show-in-red"> Show in red</label>
                    </div>
                </div>

                <div class="option-section">
                    <h6 class="section-title">Header <i class="fa fa-chevron-up"></i></h6>
                    <div class="option-group">
                        <label class="checkbox-label"><input type="checkbox" id="company-logo"> Company logo</label>
                        <label class="checkbox-label"><input type="checkbox" id="report-title" checked> Report title</label>
                        <label class="checkbox-label"><input type="checkbox" id="company-name" checked> Company name</label>
                        <label class="checkbox-label"><input type="checkbox" id="report-period" checked> Report period</label>
                        <div class="alignment-group" style="margin-top:8px;">
                            <label class="checkbox-label" style="gap:8px; margin:0;">
                                <span style="min-width:120px;">Header alignment</span>
                                <select id="header-alignment" class="form-control" style="max-width:180px;">
                                    <option value="center" selected>Center</option>
                                    <option value="left">Left</option>
                                    <option value="right">Right</option>
                                </select>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="option-section">
                    <h6 class="section-title">Footer <i class="fa fa-chevron-up"></i></h6>
                    <div class="option-group">
                        <label class="checkbox-label"><input type="checkbox" id="date-prepared" checked> Date prepared</label>
                        <label class="checkbox-label"><input type="checkbox" id="time-prepared" checked> Time prepared</label>
                        <label class="checkbox-label"><input type="checkbox" id="show-report-basis" checked> Report basis</label>
                        <label class="checkbox-label" style="gap:8px;">
                            <span style="min-width:120px;">Basis</span>
                            <select id="report-basis" class="form-control" style="max-width:180px;">
                                <option value="Accrual" selected>Accrual</option>
                                <option value="Cash">Cash</option>
                            </select>
                        </label>
                        <label class="checkbox-label" style="gap:8px;">
                            <span style="min-width:120px;">Footer alignment</span>
                            <select id="footer-alignment" class="form-control" style="max-width:180px;">
                                <option value="center" selected>Center</option>
                                <option value="left">Left</option>
                                <option value="right">Right</option>
                            </select>
                        </label>
                    </div>
                </div>
            </div>

            <div class="modal-footer" style="padding:15px 25px;border-top:1px solid #e9ecef;display:flex;justify-content:flex-end;gap:10px;">
                <button type="button" class="btn btn-outline" id="cancel-general-options">Cancel</button>
                <button type="button" class="btn btn-success" id="apply-general-options">Apply</button>
            </div>
        </div>
    </div>

    <!-- View Options Modal -->
    <div class="modal-overlay" id="view-options-overlay">
        <div class="view-options-modal">
            <div class="modal-header">
                <h5>View options <i class="fa fa-info-circle" title="Adjust how the report looks"></i></h5>
                <button type="button" class="btn-close" id="close-view-options">&times;</button>
            </div>
            <div class="modal-content">
                <p class="modal-subtitle">Choose display preferences. These do not affect data.</p>
                <div class="option-section">
                    <h6 class="section-title">Table density</h6>
                    <div class="option-group">
                        <label class="checkbox-label"><input type="checkbox" id="opt-compact"> Compact rows</label>
                        <label class="checkbox-label"><input type="checkbox" id="opt-hover" checked> Row hover effects</label>
                    </div>
                </div>
                <div class="option-section">
                    <h6 class="section-title">Row style</h6>
                    <div class="option-group">
                        <label class="checkbox-label"><input type="checkbox" id="opt-striped" checked> Striped rows</label>
                        <label class="checkbox-label"><input type="checkbox" id="opt-borders"> Show borders</label>
                        <label class="checkbox-label"><input type="checkbox" id="opt-wrap"> Wrap long text</label>
                        <label class="checkbox-label"><input type="checkbox" id="opt-sticky-head" checked> Sticky header</label>
                    </div>
                </div>
                <div class="option-section">
                    <h6 class="section-title">Column width</h6>
                    <div class="option-group">
                        <label class="checkbox-label"><input type="checkbox" id="opt-auto-width" checked> Auto-fit columns</label>
                        <label class="checkbox-label"><input type="checkbox" id="opt-equal-width"> Equal column widths</label>
                    </div>
                </div>
                <div class="option-section">
                    <h6 class="section-title">Font size</h6>
                    <div class="option-group">
                        <label class="checkbox-label" style="gap:12px;">
                            <span>Table font size</span>
                            <select id="font-size" class="form-control" style="width:160px;">
                                <option value="11px">Small (11px)</option>
                                <option value="13px" selected>Normal (13px)</option>
                                <option value="15px">Large (15px)</option>
                                <option value="17px">Extra Large (17px)</option>
                            </select>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Columns Modal -->
    <div class="modal-overlay" id="columns-overlay">
        <div class="columns-modal">
            <div class="modal-header">
                <h5>Columns <i class="fa fa-info-circle"></i></h5>
                <button type="button" class="btn-close" id="close-columns">&times;</button>
            </div>
            <div class="modal-content">
                <p class="modal-subtitle">Drag to reorder, uncheck to hide columns.</p>
                <div class="columns-list" id="sortable-columns">
                    <div class="column-item" data-column="0"><i class="fa fa-grip-vertical handle"></i>
                        <label class="checkbox-label"><input type="checkbox" data-col="0" checked> Customer Full Name</label>
                    </div>
                    <div class="column-item" data-column="1"><i class="fa fa-grip-vertical handle"></i>
                        <label class="checkbox-label"><input type="checkbox" data-col="1" checked> Phone Numbers</label>
                    </div>
                    <div class="column-item" data-column="2"><i class="fa fa-grip-vertical handle"></i>
                        <label class="checkbox-label"><input type="checkbox" data-col="2"> Email</label>
                    </div>
                    <div class="column-item" data-column="3"><i class="fa fa-grip-vertical handle"></i>
                        <label class="checkbox-label"><input type="checkbox" data-col="3"> Full Name</label>
                    </div>
                    <div class="column-item" data-column="4"><i class="fa fa-grip-vertical handle"></i>
                        <label class="checkbox-label"><input type="checkbox" data-col="4"> Bill Address</label>
                    </div>
                    <div class="column-item" data-column="5"><i class="fa fa-grip-vertical handle"></i>
                        <label class="checkbox-label"><input type="checkbox" data-col="5"> Ship Address</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/colreorder/1.7.0/js/dataTables.colReorder.min.js"></script>
    <script src="https://cdn.datatables.net/fixedheader/3.4.0/js/dataTables.fixedHeader.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/colreorder/1.7.0/css/colReOrder.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.4.0/css/fixedHeader.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    {!! $dataTable->scripts() !!}

    <script>
        $(function () {
            /* ================= Last Updated ticker ================= */
            const $last = $('.last-updated');
            let lastUpdatedAt = Date.now(), tickerId = null;
            function rel(ts){ const s=Math.floor((Date.now()-ts)/1000); if(s<5)return'just now'; if(s<60)return`${s} seconds ago`; const m=Math.floor(s/60); if(m<60)return m===1?'1 minute ago':`${m} minutes ago`; const h=Math.floor(m/60); if(h<24)return h===1?'1 hour ago':`${h} hours ago`; const d=Math.floor(h/24); return d===1?'1 day ago':`${d} days ago`; }
            function renderLast(){ $last.text(`Last updated ${rel(lastUpdatedAt)}`); }
            function markNow(){ lastUpdatedAt=Date.now(); renderLast(); clearInterval(tickerId); tickerId=setInterval(renderLast,30_000); }
            markNow();

            /* ===== Drawers ===== */
            $('#view-options-btn').on('click', ()=>$('#view-options-overlay').addClass('drawer-open'));
            $('#filter-btn').on('click', ()=>$('#filter-overlay').addClass('drawer-open'));
            $('#general-options-btn').on('click', ()=>$('#general-options-overlay').addClass('drawer-open'));
            $('#close-view-options').on('click', ()=>$('#view-options-overlay').removeClass('drawer-open'));
            $('#close-filter').on('click', ()=>$('#filter-overlay').removeClass('drawer-open'));
            $('#close-general-options,#cancel-general-options').on('click', ()=>$('#general-options-overlay').removeClass('drawer-open'));
            $('#view-options-overlay,#filter-overlay,#general-options-overlay,#columns-overlay').on('click', function(e){ if(e.target===this) $(this).removeClass('drawer-open'); });

            /* ===== Header actions ===== */
            $('#btn-refresh').on('click', function(){ $(this).find('i').addClass('fa-spin'); const dt=window.LaravelDataTables&&window.LaravelDataTables["customer-contact-table"]; if(dt) dt.ajax.reload(null,false); });
            $('#customer-contact-table').on('xhr.dt', function(){ markNow(); $('#btn-refresh i').removeClass('fa-spin'); });
            $('#btn-print').on('click', ()=>window.print());
            $('#btn-export').on('click', ()=>alert('Export action triggered'));
            $('#btn-more').on('click', ()=>alert('More options clicked'));
            $('#btn-save').on('click', function(){ const n=prompt('Enter report name:','Customer Contact List - '+new Date().toISOString().slice(0,10)); if(n) alert('Report "'+n+'" would be saved.'); });

            /* ===== LIVE Filter (customer) ===== */
            $('#filter-customer-name').on('change', function(){
                const name=$(this).val(); const url=new URL(window.location);
                if(name) url.searchParams.set('customer_name',name); else url.searchParams.delete('customer_name');
                const dt=window.LaravelDataTables&&window.LaravelDataTables["customer-contact-table"]; if(dt) dt.ajax.url(url.href).load();
                $('#date-range-display').text(name?`Customer: ${name}`:'All Customers');
                $('#filter-overlay').removeClass('drawer-open');
            });

            /* ===== General options (appearance only) ===== */
            window.reportOptions={divideBy1000:false,hideZeroAmounts:false,roundWholeNumbers:false,negativeFormat:'-100',showInRed:false,companyLogo:false,reportTitle:true,companyName:true,reportPeriod:true,headerAlignment:'center',datePrepared:true,timePrepared:true,showReportBasis:true,reportBasis:'Accrual',footerAlignment:'center'};
            function numberCSS(o){ $('#custom-number-format').remove(); let css='<style id="custom-number-format">'; if(o.showInRed) css+='.negative-amount{color:#dc2626!important;}'; if(o.hideZeroAmounts) css+='.zero-amount{display:none!important;}'; css+='</style>'; $('head').append(css); }
            function headerApply(o){ $('.report-title')[o.reportTitle?'show':'hide'](); $('.company-name')[o.companyName?'show':'hide'](); $('.date-range')[o.reportPeriod?'show':'hide'](); $('.report-title-section').css('text-align', o.headerAlignment||'center'); }
            function ensureFooter(){ if($('.report-footer').length) return; $('.report-content').append('<div class="report-footer" style="padding:20px;border-top:1px solid #e6e6e6;text-align:center;font-size:12px;color:#6b7280;"></div>'); }
            function footerRender(o){ ensureFooter(); const now=new Date(); const parts=[]; if(o.datePrepared) parts.push(`Date Prepared: ${now.toLocaleDateString()}`); if(o.timePrepared) parts.push(`Time Prepared: ${now.toLocaleTimeString()}`); if(o.showReportBasis) parts.push(`Report Basis: ${o.reportBasis} Basis`); $('.report-footer').css('text-align',o.footerAlignment||'center').html(parts.map(p=>`<div>${p}</div>`).join('')); }
            function applyGeneralOptions(){ const o=window.reportOptions;
                o.divideBy1000=$('#divide-by-1000').prop('checked'); o.hideZeroAmounts=$('#hide-zero-amounts').prop('checked'); o.roundWholeNumbers=$('#round-whole-numbers').prop('checked');
                o.negativeFormat=$('#negative-format').val(); o.showInRed=$('#show-in-red').prop('checked');
                o.companyLogo=$('#company-logo').prop('checked'); o.reportTitle=$('#report-title').prop('checked'); o.companyName=$('#company-name').prop('checked'); o.reportPeriod=$('#report-period').prop('checked'); o.headerAlignment=$('#header-alignment').val();
                o.datePrepared=$('#date-prepared').prop('checked'); o.timePrepared=$('#time-prepared').prop('checked'); o.showReportBasis=$('#show-report-basis').prop('checked'); o.reportBasis=$('#report-basis').val(); o.footerAlignment=$('#footer-alignment').val();
                numberCSS(o); headerApply(o); footerRender(o);
                const dt=window.LaravelDataTables&&window.LaravelDataTables["customer-contact-table"]; if(dt) dt.draw(false);
            }
            $('#apply-general-options').on('click', function(){ applyGeneralOptions(); $('#general-options-overlay').removeClass('drawer-open'); });
            $('#cancel-general-options').on('click', function(){ $('#general-options-overlay').removeClass('drawer-open'); });
            $('.general-options-modal input, .general-options-modal select').on('change', applyGeneralOptions);
            $('.section-title').on('click', function(){ $(this).next('.option-group').slideToggle(120); $(this).find('.fa-chevron-up, .fa-chevron-down').toggleClass('fa-chevron-up fa-chevron-down'); });

            /* ===== View options ===== */
            function applyViewOptions(){
                $('#custom-view-styles').remove();
                let css='<style id="custom-view-styles">';
                css+=$('#opt-compact').prop('checked')?'.customer-contact-table th,.customer-contact-table td{padding:8px 12px;}':'.customer-contact-table th,.customer-contact-table td{padding:12px 16px;}';
                css+=$('#opt-hover').prop('checked')?'.customer-contact-table tbody tr:hover{background:#f9fafb;}':'.customer-contact-table tbody tr:hover{background:inherit;}';
                if($('#opt-striped').prop('checked')) css+='.customer-contact-table tbody tr:nth-child(even){background-color:#f8f9fa;}';
                css+=$('#opt-borders').prop('checked')?'.customer-contact-table th,.customer-contact-table td{border:1px solid #e5e7eb;}':'.customer-contact-table th,.customer-contact-table td{border:none;border-bottom:1px solid #f3f4f6;}';
                css+=$('#opt-wrap').prop('checked')?'.customer-contact-table th,.customer-contact-table td{white-space:normal;word-wrap:break-word;}':'.customer-contact-table th,.customer-contact-table td{white-space:nowrap;}';
                css+=$('#opt-auto-width').prop('checked')?'.customer-contact-table{table-layout:auto;}':'.customer-contact-table{table-layout:fixed;}';
                if($('#opt-equal-width').prop('checked')) css+='.customer-contact-table th,.customer-contact-table td{width:16.67%;}';
                css+=`.customer-contact-table,.customer-contact-table th,.customer-contact-table td{font-size:${$('#font-size').val()};}`; css+='</style>'; $('head').append(css);
                if($('#opt-sticky-head').prop('checked')){ $('.table-container').css({'max-height':'500px','overflow-y':'auto'}); $('.customer-contact-table thead th').css({'position':'sticky','top':'0','z-index':'10'}); } else { $('.table-container').css({'max-height':'none','overflow-y':'visible'}); $('.customer-contact-table thead th').css({'position':'static'}); }
            }
            $('#view-options-overlay input,#view-options-overlay select').on('change', applyViewOptions);

            /* ===== Columns (default visibility + sync + reorder) ===== */
            function syncColumnCheckboxes(){
                const dt=window.LaravelDataTables&&window.LaravelDataTables['customer-contact-table']; if(!dt) return;
                $('#columns-overlay input[type="checkbox"][data-col]').each(function(){
                    const idx=parseInt(this.dataset.col,10);
                    $(this).prop('checked', dt.column(idx).visible());
                });
            }

            // Open columns drawer and sync
            $('#columns-btn').on('click', function(){
                syncColumnCheckboxes();
                $('#columns-overlay').addClass('drawer-open');
            });
            $('#close-columns').on('click', ()=>$('#columns-overlay').removeClass('drawer-open'));

            // Initialize Sortable drag list
            if(document.getElementById('sortable-columns')){
                new Sortable(document.getElementById('sortable-columns'),{
                    animation:150, handle:'.handle',
                    onEnd:function(){
                        const order=[]; $('#sortable-columns .column-item').each(function(){ order.push(parseInt($(this).data('column'),10)); });
                        localStorage.setItem('customer-contact-column-order', JSON.stringify(order));
                        const dt=window.LaravelDataTables&&window.LaravelDataTables["customer-contact-table"];
                        if(dt && typeof dt.colReorder!=='undefined'){ try{ dt.colReorder.order(order,true); }catch(e){} }
                        syncColumnCheckboxes();
                    }
                });
            }

            // Toggle visibility from modal
            $('#columns-overlay').on('change','input[type="checkbox"][data-col]',function(){
                const colIdx=parseInt(this.dataset.col,10);
                const visible=$(this).is(':checked');
                const dt=window.LaravelDataTables&&window.LaravelDataTables["customer-contact-table"];
                if(dt){ try{ dt.column(colIdx).visible(visible,false); dt.columns.adjust().draw(false); }catch(e){} }
            });

            // Keep checkboxes synced if columns change elsewhere
            $('#customer-contact-table').on('column-visibility.dt column-reorder.dt', syncColumnCheckboxes);

            // Default visibility after DT is ready: show [0,1], hide [2..5]
            setTimeout(function(){
                const dt=window.LaravelDataTables&&window.LaravelDataTables['customer-contact-table']; if(!dt) return;
                [0,1].forEach(i=>{ try{ dt.column(i).visible(true,false); }catch(e){} });
                [2,3,4,5].forEach(i=>{ try{ dt.column(i).visible(false,false); }catch(e){} });
                dt.columns.adjust().draw(false);
                syncColumnCheckboxes();
            }, 100);

            /* ===== Keyboard ===== */
            $(document).on('keydown', e=>{ if(e.key==='Escape') $('.modal-overlay').removeClass('drawer-open'); });

            /* ===== Init ===== */
            setTimeout(function(){ applyGeneralOptions(); applyViewOptions(); footerRender(window.reportOptions); }, 120);
        });
    </script>
@endpush
