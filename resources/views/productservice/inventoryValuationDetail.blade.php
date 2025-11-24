@extends('layouts.admin')

@section('content')
  {{-- Base skin --}}
  <style>
    body {
      background: #f8f9fa;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      font-size: 14px
    }

    .main-container {
      background: #fff;
      box-shadow: 0 1px 3px rgba(0, 0, 0, .1)
    }

    .header-section {
      padding: 16px 24px;
      border-bottom: 1px solid #e9ecef;
      display: flex;
      justify-content: space-between;
      align-items: center
    }

    .header-left h4 {
      margin: 0;
      font-size: 16px;
      font-weight: 600;
      color: #262626
    }

    .header-right {
      display: flex;
      align-items: center;
      gap: 8px
    }

    .btn-icon {
      width: 32px;
      height: 32px;
      border: none;
      background: transparent;
      color: #6b7280;
      border-radius: 4px;
      display: flex;
      align-items: center;
      justify-content: center
    }

    .btn-icon:hover {
      background: #f3f4f6;
      color: #262626
    }

    .btn-save {
      background: #22c55e;
      color: #fff;
      border: none;
      padding: 8px 16px;
      border-radius: 4px;
      font-size: 13px;
      font-weight: 600
    }

    .report-content {
      padding: 24px
    }

    .report-header {
      text-align: center;
      margin-bottom: 24px
    }

    .report-title {
      font-size: 24px;
      font-weight: 700;
      color: #262626;
      margin: 0 0 8px
    }

    .company-name {
      font-size: 16px;
      color: #6b7280;
      margin: 0 0 8px
    }

    .date-range {
      font-size: 14px;
      color: #374151;
      margin: 0
    }

    .table-container {
      margin-top: 16px;
      overflow-x: auto;
      width: 100%
    }

    .ledger-table {
      width: 100%;
      font-size: 13px;
      white-space: nowrap
    }

    .ledger-table thead th {
      background: #f9fafb;
      border-bottom: 2px solid #e5e7eb;
      font-weight: 600;
      color: #374151;
      padding: 12px 8px;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: .025em
    }

    .ledger-table tbody td {
      padding: 12px 16px;
      border-bottom: 1px solid #f3f4f6;
      vertical-align: middle
    }

    .text-right {
      text-align: right
    }

    .text-center {
      text-align: center
    }

    /* Filters header row (styled like your GL example) */
    .filter-controls {
      background: #fff;
      padding: 20px 24px;
      border-bottom: 1px solid #e6e6e6
    }

    .filter-row {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      gap: 20px;
      flex-wrap: nowrap
    }

    .filter-group {
      display: flex;
      align-items: flex-end;
      gap: 14px;
      flex: 1;
      min-width: 0
    }

    .filter-item {
      display: flex;
      flex-direction: column;
      min-width: 140px
    }

    .filter-label {
      font-size: 12px;
      color: #6b7280;
      margin-bottom: 6px;
      font-weight: 500
    }

    .form-control,
    .form-select {
      height: 36px;
      border: 1px solid #d1d5db;
      border-radius: 4px;
      padding: 8px 12px;
      font-size: 13px;
      background: #fff
    }

    .btn-outline {
      background: #fff;
      border: 1px solid #d1d5db;
      color: #374151;
      padding: 8px 12px;
      font-size: 13px;
      border-radius: 4px
    }

    .btn-outline:hover {
      background: #f9fafb;
      border-color: #9ca3af
    }

    .badge {
      background: #e5e7eb;
      color: #374151;
      font-size: 11px;
      padding: 2px 6px;
      border-radius: 10px;
      margin-left: 4px
    }

    /* DT & view toggles */
    div.dataTables_wrapper div.dataTables_scroll {
      width: 100%
    }

    div.dataTables_wrapper .dataTables_scrollHead table,
    div.dataTables_wrapper .dataTables_scrollBody table {
      width: 100% !important
    }

    .table-compact .ledger-table thead th {
      padding: 8px 10px
    }

    .table-compact .ledger-table tbody td {
      padding: 6px 10px
    }

    .table-striped .ledger-table tbody tr:nth-child(odd) {
      background: #fafafa
    }

    .table-wrap .ledger-table td,
    .table-wrap .ledger-table th {
      white-space: normal
    }

    .table-bordered .ledger-table td,
    .table-bordered .ledger-table th {
      border: 1px solid #e5e7eb
    }

    .sticky-head .dataTables_scrollHead {
      position: sticky;
      top: 0;
      z-index: 5;
      background: #fff
    }

    /* Slide-in side drawers (Columns / General / View) */
    .modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, .5);
      z-index: 10000;
      overflow-y: auto;
      opacity: 0;
      transition: opacity .2s ease
    }

    .modal-overlay.open {
      display: block;
      opacity: 1
    }

    .drawer {
      position: fixed;
      top: 0;
      right: 0;
      bottom: 0;
      width: 360px;
      max-width: 92vw;
      background: #fff;
      box-shadow: -2px 0 10px rgba(0, 0, 0, .1);
      overflow-y: auto;
      transform: translateX(100%);
      transition: transform .28s ease;
      will-change: transform
    }

    .modal-overlay.open .drawer {
      transform: translateX(0)
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 16px 20px;
      border-bottom: 1px solid #e6e6e6;
      background: #f9fafb
    }

    .modal-header h5 {
      margin: 0;
      font-size: 16px;
      font-weight: 600;
      color: #262626;
      display: flex;
      align-items: center;
      gap: 8px
    }

    .btn-close {
      background: none;
      border: none;
      font-size: 20px;
      color: #6b7280;
      cursor: pointer;
      line-height: 1
    }

    .btn-close:hover {
      color: #262626
    }

    .modal-content {
      padding: 16px 20px 24px
    }

    @media (max-width:900px) {
      .filter-row {
        flex-direction: column;
        align-items: stretch;
        gap: 12px
      }

      .filter-group {
        flex-wrap: wrap
      }
    }

    @media (max-width:768px) {
      .main-container {
        margin: 0;
        border-radius: 0
      }

      .filter-group {
        flex-direction: column;
        align-items: stretch;
        gap: 10px
      }
    }
  </style>

  <div class="main-container">
    {{-- Header --}}
    <div class="header-section">
      <div class="header-left">
        <h4>{{ __('Inventory Valuation Detail') }}</h4>
      </div>
      <div class="header-right">
        <button class="btn-icon" title="{{ __('Refresh') }}" onclick="refreshData()"><i
            class="fas fa-sync-alt"></i></button>
        <button class="btn-icon" title="{{ __('Print') }}" id="print-btn"><i class="fas fa-print"></i></button>
        <a class="btn-icon" title="{{ __('Export') }}" href="{{ route('productservice.export') }}"><i
            class="fas fa-external-link-alt"></i></a>
        <a class="btn-icon" title="{{ __('Import') }}" href="#" data-url="{{ route('productservice.file.import') }}"
          data-ajax-popup="true"><i class="fas fa-file-import"></i></a>
        <button class="btn-save">{{ __('Save As') }}</button>
      </div>
    </div>

    {{-- Filters header (matches your GL structure) --}}
    <div class="filter-controls">
      <div class="filter-row">
        <div class="filter-group d-flex">
          <div class="col-md-7">
            <div class="row">
              {{-- Report period --}}
              <div class="filter-item col-md-3">
                <label class="filter-label">{{ __('Report period') }}</label>
                <select id="report-period" class="form-select">
                  <option value="this_month_to_date" selected>{{ __('This month to date') }}</option>
                  <option value="today">{{ __('Today') }}</option>
                  <option value="this_week">{{ __('This week') }}</option>
                  <option value="this_month">{{ __('This month') }}</option>
                  <option value="this_quarter">{{ __('This quarter') }}</option>
                  <option value="this_year">{{ __('This year') }}</option>
                  <option value="last_month">{{ __('Last month') }}</option>
                  <option value="last_quarter">{{ __('Last quarter') }}</option>
                  <option value="last_year">{{ __('Last year') }}</option>
                  <option value="custom_date">{{ __('Custom dates') }}</option>
                </select>
              </div>

              {{-- Date Range (single field) --}}
              <div class="filter-item col-md-3">
                <label class="filter-label">{{ __('Date Range') }}</label>
                <input type="text" id="daterange" class="form-control"
                  value="{{ \Carbon\Carbon::now()->startOfMonth()->format('m/d/Y') }} - {{ \Carbon\Carbon::now()->format('m/d/Y') }}">
                <input type="hidden" id="filter-start-date"
                  value="{{ \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d') }}">
                <input type="hidden" id="filter-end-date" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}">
              </div>

              {{-- View options button --}}
              <div class="filter-item col-md-2 mt-4">
                <button class="btn btn-outline"
                  style="border: none !important; border-left: 1px solid #d1d5db !important; border-radius: 0px !important; width: 130px;"
                  id="view-options-btn" type="button">
                  <i class="fa fa-eye"></i> {{ __('View options') }}
                </button>
              </div>
            </div>
          </div>

          {{-- Action buttons --}}
          <div class="col-md-5">
            <div class="row mt-4">
              <div class="d-flex gap-2 justify-content-end align-items-center">
                <button class="btn btn-outline" id="columns-btn" type="button">
                  <i class="fa fa-columns"></i> {{ __('Columns') }} <span class="badge">10</span>
                </button>

                {{-- OPEN FILTERS OFFCANVAS (from side) --}}
                <button class="btn btn-outline" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterSidebar"
                  aria-controls="filterSidebar">
                  <i class="fa fa-filter"></i> {{ __('Filter') }}
                </button>

                <button class="btn btn-outline" id="general-options-btn" type="button">
                  <i class="fa fa-cog"></i> {{ __('General options') }}
                </button>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    {{-- Report --}}
    <div class="report-content">
      <div class="report-header report-title-section">
        <h1 class="report-title">{{ __('Inventory Valuation Detail') }}</h1>
        <p class="company-name">{{ \Auth::user()->name ?? config('app.name') }}</p>
        <p class="date-range">
          <span id="display-date-range">
            {{ \Carbon\Carbon::now()->startOfMonth()->format('F j, Y') }} - {{ \Carbon\Carbon::now()->format('F j, Y') }}
          </span>
        </p>
      </div>

      <div class="table-container" id="table-visual-wrapper">
        <table class="table ledger-table" id="inventory-valuation-detail-table">
          <thead>
            <tr>
              <th>{{ __('Product/Service') }}</th>
              <th class="text-center">{{ __('Transaction Date') }}</th>
              <th>{{ __('Transaction Type') }}</th>
              <th>{{ __('Num') }}</th>
              <th>{{ __('Name') }}</th>
              <th class="text-right">{{ __('Qty') }}</th>
              <th class="text-right">{{ __('Rate') }}</th>
              <th class="text-right">{{ __('Inventory Cost') }}</th>
              <th class="text-right">{{ __('Qty on Hand') }}</th>
              <th class="text-right">{{ __('Asset Value') }}</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- FILTERS: Bootstrap offcanvas (opens from the side, like your GL) --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="filterSidebar" aria-labelledby="filterSidebarLabel">
    <div class="offcanvas-header" style="background:#f9fafb; border-bottom:1px solid #e6e6e6;">
      <h5 class="offcanvas-title" id="filterSidebarLabel">{{ __('Filters') }}</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
        aria-label="{{ __('Close') }}"></button>
    </div>
    <div class="offcanvas-body">
      <div class="filter-item mb-3">
        <label class="filter-label">{{ __('Category') }}</label>
        {{ Form::select('category', $category, $filter['selectedCategory'] ?? '', ['class' => 'form-select', 'id' => 'filter-category']) }}
      </div>
      <div class="filter-item mb-3">
        <label class="filter-label">{{ __('Type') }}</label>
        {{ Form::select('type', $types, $filter['selectedType'] ?? '', ['class' => 'form-select', 'id' => 'filter-type']) }}
      </div>
    </div>
  </div>

  {{-- COLUMNS drawer --}}
  <div class="modal-overlay" id="columns-overlay">
    <div class="drawer">
      <div class="modal-header">
        <h5>{{ __('Columns') }}</h5>
        <button type="button" class="btn-close" id="close-columns">&times;</button>
      </div>
      <div class="modal-content">
        <div class="columns-list">
          @foreach (range(0, 9) as $i)
            @php
              $labels = ['Product/Service', 'Transaction Date', 'Transaction Type', 'Num', 'Name', 'Qty', 'Rate', 'Inventory Cost', 'Qty on Hand', 'Asset Value'];
            @endphp
            <div class="column-item" data-column="{{ $i }}"><label class="checkbox-label"><input type="checkbox" checked>
                {{ __($labels[$i]) }}</label></div>
          @endforeach
        </div>
      </div>
    </div>
  </div>

  {{-- GENERAL OPTIONS drawer --}}
  <div class="modal-overlay" id="general-options-overlay">
    <div class="drawer general-options-modal">
      <div class="modal-header">
        <h5>{{ __('General options') }} <i class="fa fa-info-circle"></i></h5>
        <button type="button" class="btn-close" id="close-general-options">&times;</button>
      </div>
      <div class="modal-content">
        <p class="modal-subtitle">{{ __('Select general options for your report.') }}</p>

        <div class="option-section">
          <h6 class="section-title">{{ __('Number format') }} <i class="fa fa-chevron-up"></i></h6>
          <div class="option-group" style="flex-direction:row;gap:12px">
            <label class="checkbox-label"><input type="checkbox" id="divide-by-1000"> {{ __('Divide by 1000') }}</label>
            <label class="checkbox-label"><input type="checkbox" id="hide-zero-amounts">
              {{ __("Don't show zero amounts") }}</label>
            <label class="checkbox-label"><input type="checkbox" id="round-whole-numbers">
              {{ __('Round to the nearest whole number') }}</label>
          </div>
        </div>

        <div class="option-section">
          <h6 class="section-title">{{ __('Negative numbers') }}</h6>
          <div class="option-group">
            <div class="negative-format-group">
              <select id="negative-format" class="form-control" style="width:100px;">
                <option value="-100" selected>-100</option>
                <option value="(100)">(100)</option>
                <option value="100-">100-</option>
              </select>
              <label class="checkbox-label"><input type="checkbox" id="show-in-red"> {{ __('Show in red') }}</label>
            </div>
          </div>
        </div>

        <div class="option-section">
          <h6 class="section-title">{{ __('Header') }} <i class="fa fa-chevron-up"></i></h6>
          <div class="option-group" style="flex-direction:row;gap:12px">
            <label class="checkbox-label"><input type="checkbox" id="company-logo"> {{ __('Company logo') }}</label>
            <label class="checkbox-label"><input type="checkbox" id="report-period-checkbox" checked>
              {{ __('Report period') }}</label>
            <label class="checkbox-label"><input type="checkbox" id="company-name-checkbox" checked>
              {{ __('Company name') }}</label>
          </div>
          <div class="alignment-group">
            <label class="alignment-label">{{ __('Header alignment') }}</label>
            <select id="header-alignment" class="form-control" style="max-width:150px;">
              <option value="center" selected>{{ __('Center') }}</option>
              <option value="left">{{ __('Left') }}</option>
              <option value="right">{{ __('Right') }}</option>
            </select>
          </div>
        </div>

        <div class="option-section">
          <h6 class="section-title">{{ __('Footer') }} <i class="fa fa-chevron-up"></i></h6>
          <div class="option-group" style="flex-direction:row;gap:12px">
            <label class="checkbox-label"><input type="checkbox" id="date-prepared" checked>
              {{ __('Date prepared') }}</label>
            <label class="checkbox-label"><input type="checkbox" id="time-prepared" checked>
              {{ __('Time prepared') }}</label>
            <label class="checkbox-label"><input type="checkbox" id="report-basis" checked>
              {{ __('Report basis') }}</label>
          </div>
          <div class="alignment-group">
            <label class="alignment-label">{{ __('Footer alignment') }}</label>
            <select id="footer-alignment" class="form-control" style="max-width:150px;">
              <option value="center" selected>{{ __('Center') }}</option>
              <option value="left">{{ __('Left') }}</option>
              <option value="right">{{ __('Right') }}</option>
            </select>
          </div>
        </div>

      </div>
    </div>
  </div>

  {{-- VIEW OPTIONS drawer --}}
  <div class="modal-overlay" id="view-options-overlay">
    <div class="drawer">
      <div class="modal-header">
        <h5>{{ __('View options') }} <i class="fa fa-info-circle"></i></h5>
        <button type="button" class="btn-close" id="close-view-options">&times;</button>
      </div>
      <div class="modal-content">
        <div class="option-section">
          <h6 class="section-title">{{ __('Table density') }}</h6>
          <div class="option-group" style="flex-direction:row;gap:12px">
            <label class="checkbox-label"><input type="checkbox" id="opt-compact"> {{ __('Compact rows') }}</label>
          </div>
        </div>

        <div class="option-section">
          <h6 class="section-title">{{ __('Row style') }}</h6>
          <div class="option-group" style="flex-direction:row;gap:12px;flex-wrap:wrap">
            <label class="checkbox-label"><input type="checkbox" id="opt-striped" checked>
              {{ __('Striped rows') }}</label>
            <label class="checkbox-label"><input type="checkbox" id="opt-borders"> {{ __('Show borders') }}</label>
            <label class="checkbox-label"><input type="checkbox" id="opt-wrap"> {{ __('Wrap long text') }}</label>
            <label class="checkbox-label"><input type="checkbox" id="opt-sticky-head" checked>
              {{ __('Sticky header') }}</label>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('script-page')
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.css">

  <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/colreorder/1.7.0/js/dataTables.colReorder.min.js"></script>
  <script src="https://cdn.datatables.net/fixedheader/3.4.0/js/dataTables.fixedHeader.min.js"></script>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/colreorder/1.7.0/css/colReOrder.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.4.0/css/fixedHeader.dataTables.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  {!! $dataTable->scripts() !!}

  <script>
    // Slide-in drawers (non-BS)
    function openDrawer(overlaySel) { const $ov = $(overlaySel); $ov.show(0); requestAnimationFrame(() => { $ov.addClass('open'); }); }
    function closeDrawer(overlaySel) { const $ov = $(overlaySel); $ov.removeClass('open'); setTimeout(() => { $ov.hide(); }, 220); }

    $(function () {
      // Global options
      window.reportOptions = {
        divideBy1000: false, hideZeroAmounts: false, roundWholeNumbers: false,
        negativeFormat: '-100', showInRed: false, companyLogo: false,
        reportPeriod: true, companyName: true, headerAlignment: 'center',
        datePrepared: true, timePrepared: true, reportBasis: true, footerAlignment: 'center'
      };

      const $visualWrap = $('#table-visual-wrapper');

      function applyViewOptions() {
        $visualWrap.toggleClass('table-compact', $('#opt-compact').prop('checked'));
        $visualWrap.toggleClass('table-striped', $('#opt-striped').prop('checked'));
        $visualWrap.toggleClass('table-wrap', $('#opt-wrap').prop('checked'));
        $visualWrap.toggleClass('table-bordered', $('#opt-borders').prop('checked'));
        $visualWrap.toggleClass('sticky-head', $('#opt-sticky-head').prop('checked'));
        table.columns.adjust().draw(false);
      }

      // DateRangePicker (GL-style)
      $('#daterange').daterangepicker({
        startDate: moment($('#filter-start-date').val()),
        endDate: moment($('#filter-end-date').val()),
        opens: 'left',
        autoApply: true,
        locale: { format: 'MM/DD/YYYY' },
        ranges: {
          '{{ __("Today") }}': [moment(), moment()],
          '{{ __("Last 7 Days") }}': [moment().subtract(6, 'days'), moment()],
          '{{ __("Last 30 Days") }}': [moment().subtract(29, 'days'), moment()],
          '{{ __("This Month") }}': [moment().startOf('month'), moment().endOf('month')],
          '{{ __("Last Month") }}': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
          '{{ __("This Quarter") }}': [moment().startOf('quarter'), moment().endOf('quarter')],
          '{{ __("This Year") }}': [moment().startOf('year'), moment().endOf('year')]
        }
      }, function (start, end) {
        $('#filter-start-date').val(start.format('YYYY-MM-DD'));
        $('#filter-end-date').val(end.format('YYYY-MM-DD'));
        updateHeaderDate();
        table.draw();
      });

      // Report period -> update daterange + hidden dates
      $('#report-period').on('change', function () {
        const v = $(this).val(), today = moment();
        let s, e;
        switch (v) {
          case 'today': s = today.clone(); e = today.clone(); break;
          case 'this_week': s = today.clone().startOf('week'); e = today.clone().endOf('week'); break;
          case 'this_month': s = today.clone().startOf('month'); e = today.clone().endOf('month'); break;
          case 'this_month_to_date': s = today.clone().startOf('month'); e = today.clone(); break;
          case 'this_quarter': s = today.clone().startOf('quarter'); e = today.clone().endOf('quarter'); break;
          case 'this_year': s = today.clone().startOf('year'); e = today.clone().endOf('year'); break;
          case 'last_month': s = today.clone().subtract(1, 'month').startOf('month'); e = today.clone().subtract(1, 'month').endOf('month'); break;
          case 'last_quarter': s = today.clone().subtract(1, 'quarter').startOf('quarter'); e = today.clone().subtract(1, 'quarter').endOf('quarter'); break;
          case 'last_year': s = today.clone().subtract(1, 'year').startOf('year'); e = today.clone().subtract(1, 'year').endOf('year'); break;
          default: s = today.clone().startOf('month'); e = today.clone();
        }
        $('#filter-start-date').val(s.format('YYYY-MM-DD'));
        $('#filter-end-date').val(e.format('YYYY-MM-DD'));
        $('#daterange').data('daterangepicker').setStartDate(s);
        $('#daterange').data('daterangepicker').setEndDate(e);
        updateHeaderDate();
        table.draw();
      });

      function updateHeaderDate() {
        const s = moment($('#filter-start-date').val(), 'YYYY-MM-DD');
        const e = moment($('#filter-end-date').val(), 'YYYY-MM-DD');
        $('#display-date-range').text(s.format('MMMM D, YYYY') + ' - ' + e.format('MMMM D, YYYY'));
      }

      if ($.fn.DataTable.isDataTable('#inventory-valuation-detail-table')) {
        $('#inventory-valuation-detail-table').DataTable().destroy();
      }

      // DataTable
      const table = $('#inventory-valuation-detail-table').DataTable({
        processing: true, serverSide: true, colReorder: true,
        scrollX: true, responsive: false, scrollY: '420px', scrollCollapse: true, fixedHeader: true,
        ajax: {
          url: "{{ route('productservice.inventoryValuationDetail') }}",
          data: function (d) {
            d.report_period = $('#report-period').val() || '';
            d.start_date = $('#filter-start-date').val() || '';
            d.end_date = $('#filter-end-date').val() || '';
            d.category = $('#filter-category').val() || '';
            d.type = $('#filter-type').val() || '';
            d.reportOptions = window.reportOptions || {};
          }
        },
        columns: [
          { data: 'product_service', name: 'product_service' },
          { data: 'transaction_date', name: 'transaction_date', className: 'text-center' },
          { data: 'transaction_type', name: 'transaction_type' },
          { data: 'num', name: 'num' },
          { data: 'name', name: 'name' },
          { data: 'qty', name: 'qty', className: 'text-right' },
          { data: 'rate', name: 'rate', className: 'text-right' },
          { data: 'inventory_cost', name: 'inventory_cost', className: 'text-right' },
          { data: 'qty_on_hand', name: 'qty_on_hand', className: 'text-right' },
          { data: 'asset_value', name: 'asset_value', className: 'text-right' }
        ],
        dom: 't', paging: false, searching: false, info: false, ordering: false
      });

      // Side drawers open/close
      $('#columns-btn').on('click', e => { e.preventDefault(); openDrawer('#columns-overlay'); });
      $('#close-columns').on('click', () => closeDrawer('#columns-overlay'));
      $('#columns-overlay').on('click', e => { if (e.target.id === 'columns-overlay') closeDrawer('#columns-overlay'); });

      $('#general-options-btn').on('click', e => { e.preventDefault(); openDrawer('#general-options-overlay'); });
      $('#close-general-options').on('click', () => closeDrawer('#general-options-overlay'));
      $('#general-options-overlay').on('click', e => { if (e.target.id === 'general-options-overlay') closeDrawer('#general-options-overlay'); });

      $('#view-options-btn').on('click', e => { e.preventDefault(); openDrawer('#view-options-overlay'); });
      $('#close-view-options').on('click', () => closeDrawer('#view-options-overlay'));
      $('#view-options-overlay').on('click', e => { if (e.target.id === 'view-options-overlay') closeDrawer('#view-options-overlay'); });

      // Columns visibility (aware of colReorder mapping)
      function getDT(cb) {
        const tryGet = function (n) {
          const dt = $.fn.dataTable.isDataTable('#inventory-valuation-detail-table') ? $('#inventory-valuation-detail-table').DataTable() : null;
          if (dt) cb(dt); else if (n > 0) setTimeout(() => tryGet(n - 1), 100);
        };
        tryGet(30);
      }
      $('.columns-list input[type="checkbox"]').on('change', function () {
        const originalIndex = $(this).closest('.column-item').data('column');
        const isVisible = $(this).prop('checked');
        if (originalIndex === undefined) return;
        getDT(function (dt) {
          const currentIndex = dt.colReorder && typeof dt.colReorder.transpose === 'function'
            ? dt.colReorder.transpose(originalIndex, 'toCurrent')
            : originalIndex;
          dt.column(currentIndex).visible(isVisible, false);
          dt.columns.adjust().draw(false);
        });
      });

      // General options handlers
      function applyNumberFormatting(options) {
        $('#custom-number-format').remove();
        let css = '<style id="custom-number-format">';
        if (options.showInRed) css += '.negative-amount{color:#dc2626!important}';
        if (options.hideZeroAmounts) css += '.zero-amount{display:none!important}';
        css += '</style>'; $('head').append(css);
      }
      function applyHeaderFooterSettings(options) {
        $('.report-title-section').css('text-align', options.headerAlignment);
        $('.company-name').toggle(!!options.companyName);
        $('.date-range').toggle(!!options.reportPeriod);
        if (!$('.report-footer').length) {
          $('.report-content').append('<div class="report-footer" style="padding:12px 20px;border-top:1px solid #e6e6e6;font-size:12px;color:#6b7280;text-align:' + options.footerAlignment + ';"></div>');
        }
        const now = new Date();
        const parts = [];
        if (options.reportBasis) parts.push('{{ __("Accrual basis") }}');
        if (options.datePrepared || options.timePrepared) {
          const dt = now.toLocaleString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
          parts.push('| ' + dt);
        }
        $('.report-footer').css('text-align', options.footerAlignment).html(parts.join(' '));
      }
      function applyGeneralOptions() {
        window.reportOptions.divideBy1000 = $('#divide-by-1000').prop('checked');
        window.reportOptions.hideZeroAmounts = $('#hide-zero-amounts').prop('checked');
        window.reportOptions.roundWholeNumbers = $('#round-whole-numbers').prop('checked');
        window.reportOptions.negativeFormat = $('#negative-format').val();
        window.reportOptions.showInRed = $('#show-in-red').prop('checked');
        window.reportOptions.companyLogo = $('#company-logo').prop('checked');
        window.reportOptions.reportPeriod = $('#report-period-checkbox').prop('checked');
        window.reportOptions.companyName = $('#company-name-checkbox').prop('checked');
        window.reportOptions.headerAlignment = $('#header-alignment').val();
        window.reportOptions.datePrepared = $('#date-prepared').prop('checked');
        window.reportOptions.timePrepared = $('#time-prepared').prop('checked');
        window.reportOptions.reportBasis = $('#report-basis').prop('checked');
        window.reportOptions.footerAlignment = $('#footer-alignment').val();
        applyNumberFormatting(window.reportOptions);
        applyHeaderFooterSettings(window.reportOptions);
        table.draw(false);
      }
      $('.general-options-modal input, .general-options-modal select').on('change', applyGeneralOptions);

      // Negative/zero styling on draw
      $('#inventory-valuation-detail-table').on('draw.dt', function () {
        $('#inventory-valuation-detail-table tbody tr').each(function () {
          $(this).find('td').each(function () {
            const txt = ($(this).text() || '').trim();
            if (!txt) return;
            if (/^-/.test(txt) || /\((.*?)\)/.test(txt) || /-$/.test(txt)) $(this).addClass('negative-amount');
            if (txt === '0' || txt === '0.0' || txt === '0.00' || txt === '{{ \Auth::user()->priceFormat(0) }}') $(this).addClass('zero-amount');
          });
        });
      });

      // View options toggles + resize
      $('#view-options-overlay input[type="checkbox"]').on('change', function () { applyViewOptions(); resizeInventoryDT(); });

      // Print
      $('#print-btn').on('click', function () { window.print(); });

      // Keep DT within viewport
      function resizeInventoryDT() {
        const hHeader = $('.header-section').outerHeight(true) || 0;
        const hFilter = $('.filter-controls').outerHeight(true) || 0;
        const hReport = $('.report-title-section').outerHeight(true) || 0;
        const padding = 140;
        const available = Math.max(280, window.innerHeight - (hHeader + hFilter + hReport + padding));
        table.settings()[0].oScroll.sY = available + 'px';
        $(table.table().container()).find('.dataTables_scrollBody').css({ 'max-height': available + 'px', 'height': available + 'px' });
        table.columns.adjust().draw(false);
      }

      // Initial apply
      applyGeneralOptions();
      applyViewOptions();
      updateHeaderDate();
      resizeInventoryDT();
      let _rsTimer; $(window).on('resize', function () { clearTimeout(_rsTimer); _rsTimer = setTimeout(resizeInventoryDT, 120); });

      // Offcanvas filters -> redraw on change
      $('#filter-category, #filter-type').on('change', function () { table.draw(); });
    });

    function refreshData() { $('#inventory-valuation-detail-table').DataTable().ajax.reload(null, false); }
  </script>
@endpush