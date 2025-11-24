@extends('layouts.admin')

@section('page-title')
    {{ __('Taxable Sales Summary') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Taxable Sales Summary') }}</li>
@endsection

@push('css-page')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <style>
        /* ===== Layout ===== */
        .quickbooks-report {
            font-family: 'Inter', sans-serif;
            color: #333;
            width: 100%;
            box-sizing: border-box
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            border-bottom: 1px solid #e0e0e0;
            background: #fff
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 8px
        }

        .last-updated {
            font-size: 12px;
            color: #666;
            margin-right: 16px
        }

        .controls-bar {
            padding: 12px 16px;
            background: #f9f9f9;
            border-bottom: 1px solid #e0e0e0
        }

        .controls-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px
        }

        .report-period-container {
            display: flex;
            align-items: center;
            gap: 8px
        }

        .report-period-label {
            font-size: 14px;
            font-weight: 500;
            margin-right: 4px
        }

        .report-content {
            padding: 24px
        }

        .report-title-section {
            text-align: center;
            margin-bottom: 24px
        }

        .report-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 4px
        }

        .company-name {
            font-size: 16px;
            margin-bottom: 4px
        }

        .date-range {
            font-size: 14px;
            color: #666
        }

        /* ===== Table ===== */
        .table-container {
            margin-top: 24px;
            overflow-x: auto
        }

        #taxable-sales-summary-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse
        }

        #taxable-sales-summary-table th {
            background: #f5f5f5;
            padding: 10px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #e0e0e0;
            position: sticky;
            top: 0;
            z-index: 5
        }

        #taxable-sales-summary-table td {
            padding: 10px;
            border-bottom: 1px solid #e0e0e0;
            white-space: nowrap
        }

        #taxable-sales-summary-table th:first-child,
        #taxable-sales-summary-table td:first-child {
            width: 44px;
            min-width: 44px;
            max-width: 44px
        }

        #taxable-sales-summary-table th:nth-child(3),
        #taxable-sales-summary-table td:nth-child(3) {
            width: 180px
        }

        #taxable-sales-summary-table th:nth-child(4),
        #taxable-sales-summary-table td:nth-child(4) {
            width: 160px
        }

        .text-end {
            text-align: right
        }

        tr.shown {
            background: #fafafa
        }

        /* ===== Child rows: NO header, only amounts aligned to last two cols ===== */
        .child-wrap {
            padding: 4px 0 6px
        }

        .child-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse
        }

        .child-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #eee
        }

        .child-table tr:last-child td {
            border-bottom: 0
        }

        .child-spacer {
            width: 100%
        }

        /* just placeholders under first two parent columns */
        .child-num {
            width: 100%;
            text-align: right;
            white-space: nowrap
        }

        .child-total {
            font-weight: 600;
            background: #f5f5f5
        }

        /* ===== Filter drawer ===== */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .5);
            z-index: 1000
        }

        .modal-overlay.drawer-open {
            display: block
        }

        .filter-modal {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            width: 400px;
            max-width: 95vw;
            background: #fff;
            box-shadow: -2px 0 10px rgba(0, 0, 0, .1);
            transform: translateX(100%);
            transition: transform .24s ease;
            z-index: 1001;
            overflow-y: auto
        }

        .modal-overlay.drawer-open .filter-modal {
            transform: translateX(0)
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            border-bottom: 1px solid #e0e0e0
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer
        }

        .modal-body {
            padding: 16px
        }

        .filter-section {
            margin-bottom: 24px
        }

        .filter-section-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 12px
        }

        .filter-group {
            margin-bottom: 16px
        }

        .filter-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500
        }

        .filter-input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px
        }

        .filter-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            padding: 16px;
            border-top: 1px solid #e0e0e0;
            background: #fff;
            position: sticky;
            bottom: 0
        }

        .btn-filter {
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer
        }

        .btn-reset {
            background: #f0f0f0;
            border: 1px solid #ddd;
            color: #333
        }

        .btn-apply {
            background: #6571ff;
            border: none;
            color: #fff
        }
    </style>
@endpush

@push('script-page')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
@endpush

@section('content')
    <div class="quickbooks-report">
        <div class="report-header">
            <h4 class="mb-0">{{ __('Taxable Sales Summary') }}</h4>
            <div class="header-actions">
                <span class="last-updated">Last updated just now</span>
                <div class="actions">
                    <button class="btn btn-sm btn-light"
                        onclick="exportDataTable('taxable-sales-summary-table', '{{ __('Taxable Sales Summary') }}', 'print')"><i
                            class="fa fa-print"></i></button>
                    <button class="btn btn-sm btn-light" title="Export" id="btn-export"><i
                            class="fa fa-external-link-alt"></i></button>
                    <button class="btn btn-sm btn-light" id="btn-refresh" title="Refresh"><i
                            class="fa fa-sync"></i></button>
                    <button class="btn btn-sm btn-light" id="filter-btn" title="Filter"><i
                            class="fa fa-filter"></i></button>
                </div>
            </div>
        </div>

        <!-- Bootstrap Modal -->
        <div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content p-0">
                    <div class="modal-header">
                        <h5 class="modal-title">Choose Export Format</h5> <button type="button" class="btn-close"
                            data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center row">
                        <div class="col-md-6">
                            <button onclick="exportDataTable('taxable-sales-summary-table', '{{ __('Taxable Sales Summary') }}')"
                                class="btn btn-success mx-auto w-75 justify-content-center text-center"
                                style="background: #6fd943;"
                                data-action="excel">Export to
                                Excel</button>
                        </div>
                        <div class="col-md-6">
                            <button onclick="exportDataTable('taxable-sales-summary-table', '{{ __('Taxable Sales Summary') }}', 'pdf')"
                                class="btn btn-success mx-auto w-75 justify-content-center text-center"
                                style="background: #6fd943;"
                                data-action="pdf">Export to
                                PDF</button>
                        </div>
                        {{-- <button class="btn btn-success mx-auto w-50 text-center" data-action="csv">Export to CSV</button> --}}
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Show modal on export button click
            $('.btn[title="Export"]').on('click', function() {
                $('#exportModal').modal('show');
            });

            // Handle export actions
            $('#exportModal button[data-action]').on('click', function() {
                // Hide modal after action
                $('#exportModal').modal('hide');
            });
        </script>

        <script>
            let csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            {{-- console.log([window.Header, window.footerAlignment]) --}}

            function exportDataTable(tableId, pageTitle, format = 'excel') {
                let table = $('#' + tableId).DataTable();

                // Only get visible columns (skip auto-index)
                let columns = [];
                $('#' + tableId + ' thead th:visible').each(function() {
                    columns.push($(this).text().trim());
                });

                // Get visible data rows
                let data = [];

                const getRealtimeTableData = () => {

                    let data = [];


                    table.rows({
                        search: 'applied'
                    }).every(function() {
                        let rowData = this.data();

                        if (typeof rowData === 'object') {
                            // Only keep values for visible columns
                            let rowArray = [];
                            table.columns(':visible').every(function(colIdx) {
                                let val = rowData[this.dataSrc()] ?? '-';
                                rowArray.push(val);
                            });
                            rowData = rowArray;
                        }
                        data.push(rowData);
                    });

                    return data

                }

                // Get visible data rows (rendered DOM text, not raw data)
                $('#' + tableId + ' tbody tr:visible').each(function() {
                    let rowArray = [];
                    $(this).find('td:visible').each(function() {
                        rowArray.push($(this).text().trim());
                    });
                    data.push(rowArray);
                });



                // Send to universal export route
                $.ajax({
                    url: '{{ route('export.datatable') }}',
                    method: 'POST',
                    data: {
                        columns: columns,
                        data: data,
                        pageTitle: pageTitle,
                        // ReportPeriod: window.reportOptions.reportPeriod ? $(".report-title-section .date-range")[0]
                        //     .textContent : "",
                        // HeaderFooterAlignment: [window.reportOptions.headerAlignment, window.reportOptions
                        //     .footerAlignment
                        // ],
                        format: format,
                        _token: '{{ csrf_token() }}'
                    },
                    xhrFields: {
                        responseType: 'blob'
                    },
                    success: function(blob, status, xhr) {
                        let filename = xhr.getResponseHeader('Content-Disposition')
                            .split('filename=')[1]
                            .replace(/"/g, ''); //"

                        if (format === "print") {
                            let fileURL = URL.createObjectURL(blob);
                            let printWindow = window.open(fileURL);
                            printWindow.onload = function() {
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
                    error: function(xhr) {
                        console.error('Export failed:', xhr.responseText);
                        alert('Export failed! Check console.');
                    }
                });
            }
        </script>

        <div class="controls-bar">
            <div class="controls-inner">
                <div class="d-flex align-items-center">
                    <div class="report-period-container" id="report-period-container">
                        <label for="report-period" class="report-period-label">{{ __('Report Period') }}:</label>
                        <select id="report-period" class="form-select form-select-sm">
                            <option value="all_dates" {{ $filter['reportPeriod'] == 'all_dates' ? 'selected' : '' }}>
                                {{ __('All Dates') }}</option>
                            <option value="today" {{ $filter['reportPeriod'] == 'today' ? 'selected' : '' }}>
                                {{ __('Today') }}</option>
                            <option value="this_week" {{ $filter['reportPeriod'] == 'this_week' ? 'selected' : '' }}>
                                {{ __('This Week') }}</option>
                            <option value="this_month" {{ $filter['reportPeriod'] == 'this_month' ? 'selected' : '' }}>
                                {{ __('This Month') }}</option>
                            <option value="this_quarter" {{ $filter['reportPeriod'] == 'this_quarter' ? 'selected' : '' }}>
                                {{ __('This Quarter') }}</option>
                            <option value="this_year" {{ $filter['reportPeriod'] == 'this_year' ? 'selected' : '' }}>
                                {{ __('This Year') }}</option>
                            <option value="last_month" {{ $filter['reportPeriod'] == 'last_month' ? 'selected' : '' }}>
                                {{ __('Last Month') }}</option>
                            <option value="last_quarter" {{ $filter['reportPeriod'] == 'last_quarter' ? 'selected' : '' }}>
                                {{ __('Last Quarter') }}</option>
                            <option value="last_year" {{ $filter['reportPeriod'] == 'last_year' ? 'selected' : '' }}>
                                {{ __('Last Year') }}</option>
                            <option value="custom" {{ $filter['reportPeriod'] == 'custom' ? 'selected' : '' }}>
                                {{ __('Custom') }}</option>
                        </select>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span id="date-range-display" class="text-muted small">
                        @if (!empty($filter['startDateRange']) && !empty($filter['endDateRange']))
                            {{ \Carbon\Carbon::parse($filter['startDateRange'])->format('F j, Y') }} -
                            {{ \Carbon\Carbon::parse($filter['endDateRange'])->format('F j, Y') }}
                        @else
                            {{ __('All Dates') }}
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <div class="report-content">
            <div class="report-title-section">
                <h2 class="report-title">{{ __('Taxable Sales Summary') }}</h2>
                <p class="company-name">{{ $user->name ?? 'company' }}</p>
            </div>

            <div class="table-container">
                <table class="table" id="taxable-sales-summary-table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>{{ __('Product/Service') }}</th>
                            <th class="text-end">{{ __('Taxable Amount') }}</th>
                            <th class="text-end">{{ __('Tax') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ===== Filter Drawer ===== --}}
    <div class="modal-overlay" id="filter-overlay">
        <div class="filter-modal">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Filter') }}</h5>
                <button type="button" class="modal-close" id="filter-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="filter-section">
                    <div class="filter-section-title">{{ __('Date Range') }}</div>
                    <div class="filter-group">
                        <label class="filter-label" for="accounting-method">{{ __('Accounting Method') }}</label>
                        <select id="accounting-method" class="filter-input form-select">
                            <option value="accrual" {{ $filter['accountingMethod'] == 'accrual' ? 'selected' : '' }}>
                                {{ __('Accrual') }}</option>
                            <option value="cash" {{ $filter['accountingMethod'] == 'cash' ? 'selected' : '' }}>
                                {{ __('Cash') }}</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label" for="start-date">{{ __('From') }}</label>
                        <input type="date" id="start-date" class="filter-input form-control"
                            value="{{ $filter['startDateRange'] }}">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label" for="end-date">{{ __('To') }}</label>
                        <input type="date" id="end-date" class="filter-input form-control"
                            value="{{ $filter['endDateRange'] }}">
                    </div>
                </div>
                <div class="filter-section">
                    <div class="filter-section-title">{{ __('Filter By') }}</div>
                    <div class="filter-group">
                        <label class="filter-label" for="customer-name">{{ __('Customer') }}</label>
                        <select id="customer-name" class="filter-input form-select">
                            <option value="">{{ __('All Customers') }}</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer }}"
                                    {{ $filter['selectedCustomerName'] == $customer ? 'selected' : '' }}>
                                    {{ $customer }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label" for="category">{{ __('Category') }}</label>
                        <select id="category" class="filter-input form-select">
                            @foreach ($categories as $id => $name)
                                <option value="{{ $id }}"
                                    {{ $filter['selectedCategory'] == $id ? 'selected' : '' }}>{{ $name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label" for="type">{{ __('Type') }}</label>
                        <select id="type" class="filter-input form-select">
                            @foreach ($types as $id => $name)
                                <option value="{{ $id }}"
                                    {{ $filter['selectedType'] == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label" for="product-name">{{ __('Product/Service Name') }}</label>
                        <input type="text" id="product-name" class="filter-input form-control"
                            value="{{ $filter['selectedProductName'] }}">
                    </div>
                </div>
            </div>
            <div class="filter-actions">
                <button class="btn-filter btn-reset" id="reset-filter">{{ __('Reset') }}</button>
                <button class="btn-filter btn-apply" id="apply-filter">{{ __('Apply') }}</button>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
    <script>
        $(function() {
            /* ===== Utils ===== */
            function money(sym, val) {
                const n = Number(val || 0);
                const out = Math.abs(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                return (n < 0 ? '-' : '') + sym + out;
            }

            let CHILDREN_MAP = {};
            let CURRENCY = '{{ Auth::user()->currencySymbol() }}';

            /* ===== DataTable ===== */
            const table = $('#taxable-sales-summary-table').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                ajax: {
                    url: "{{ route('report.taxableSalesSummary') }}",
                    data: function(d) {
                        d.start_date = $('#start-date').val();
                        d.end_date = $('#end-date').val();
                        d.accounting_method = $('#accounting-method').val();
                        d.report_period = $('#report-period').val();
                        d.customer_name = $('#customer-name').val();
                        d.category = $('#category').val();
                        d.type = $('#type').val();
                        d.product_name = $('#product-name').val();
                    },
                    dataSrc: function(json) {
                        CHILDREN_MAP = json.children || {};
                        if (json.currency_symbol) CURRENCY = json.currency_symbol;
                        return (json && json.data) ? json.data : [];
                    }
                },
                columns: [{
                        data: 'control',
                        name: 'control',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'product_service',
                        name: 'product_service'
                    },
                    {
                        data: 'taxable_amount',
                        name: 'taxable_amount',
                        className: 'text-end',
                        render: (d, t) => t === 'display' ? money(CURRENCY, d) : d
                    },
                    {
                        data: 'tax_amount',
                        name: 'tax_amount',
                        className: 'text-end',
                        render: (d, t) => t === 'display' ? money(CURRENCY, d) : d
                    },
                ],
                dom: 't',
                paging: false,
                ordering: false,
                searching: false,
                info: false
            });

            /* Hide parent totals (we show numbers only inside children) */
            function stripParentTotals() {
                $('#taxable-sales-summary-table tbody tr').each(function() {
                    const $tr = $(this);
                    if ($tr.find('button.toggle-child').length) {
                        $tr.find('td').eq(2).empty();
                        $tr.find('td').eq(3).empty();
                    }
                });
            }
            table.on('draw', stripParentTotals);
            stripParentTotals();

            /* Expand/collapse: only two numeric columns + subtotal row */
            $('#taxable-sales-summary-table tbody').on('click', 'button.toggle-child', function() {
                const btn = $(this);
                const tr = btn.closest('tr');
                const row = table.row(tr);
                const pid = btn.data('product-id');

                if (row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('shown');
                    btn.find('i').removeClass('fa-angle-down').addClass('fa-angle-right');
                    return;
                }

                const rows = CHILDREN_MAP[pid] || [];
                let subtotalTaxable = 0,
                    subtotalTax = 0;
                rows.forEach(r => {
                    subtotalTaxable += Number(r.taxable_amount || 0);
                    subtotalTax += Number(r.tax_amount || 0);
                });

                let html = '<div class="child-wrap"><table class="child-table"><tbody>';

                rows.forEach(r => {
                    html += `
        <tr>
          <td class="child-spacer"></td>
          <td class="child-spacer"></td>
          <td class="child-num">${money(CURRENCY, r.taxable_amount)}</td>
          <td class="child-num">${money(CURRENCY, r.tax_amount)}</td>
        </tr>`;
                });

                const name = tr.find('td:nth-child(2)').text().trim();
                html += `
      <tr class="child-total">
        <td class="child-spacer" colspan="2">{{ __('Total for') }} ${name}</td>
        <td class="child-num">${money(CURRENCY, subtotalTaxable)}</td>
        <td class="child-num">${money(CURRENCY, subtotalTax)}</td>
      </tr>`;

                html += '</tbody></table></div>';

                row.child(html).show();
                tr.addClass('shown');
                btn.find('i').removeClass('fa-angle-right').addClass('fa-angle-down');
            });

            /* Drawer + refresh */
            $('#filter-btn').on('click', () => $('#filter-overlay').addClass('drawer-open'));
            $('#filter-close').on('click', () => $('#filter-overlay').removeClass('drawer-open'));
            $('#filter-overlay').on('click', function(e) {
                if (e.target === this) $(this).removeClass('drawer-open');
            });

            $('#btn-refresh').on('click', () => table.ajax.reload(null, false));
            $('#apply-filter').on('click', function() {
                table.ajax.reload(null, false);
                $('#filter-overlay').removeClass('drawer-open');
            });
            $('#reset-filter').on('click', function() {
                $('#start-date').val('');
                $('#end-date').val('');
                $('#accounting-method').val('accrual');
                $('#customer-name').val('');
                $('#category').val('');
                $('#type').val('');
                $('#product-name').val('');
                $('#report-period').val('all_dates');
                table.ajax.reload(null, false);
                $('#filter-overlay').removeClass('drawer-open');
            });

            /* Report period quick set */
            $('#report-period').on('change', function() {
                const period = $(this).val();
                let s, e, t = new Date();
                switch (period) {
                    case 'today':
                        s = e = t.toISOString().split('T')[0];
                        break;
                    case 'this_week': {
                        const a = new Date(t);
                        a.setDate(t.getDate() - t.getDay());
                        const b = new Date(a);
                        b.setDate(a.getDate() + 6);
                        s = a.toISOString().split('T')[0];
                        e = b.toISOString().split('T')[0];
                    }
                    break;
                    case 'this_month': {
                        const a = new Date(t.getFullYear(), t.getMonth(), 1);
                        const b = new Date(t.getFullYear(), t.getMonth() + 1, 0);
                        s = a.toISOString().split('T')[0];
                        e = b.toISOString().split('T')[0];
                    }
                    break;
                    case 'this_quarter': {
                        const q = Math.floor(t.getMonth() / 3);
                        const a = new Date(t.getFullYear(), q * 3, 1);
                        const b = new Date(t.getFullYear(), (q + 1) * 3, 0);
                        s = a.toISOString().split('T')[0];
                        e = b.toISOString().split('T')[0];
                    }
                    break;
                    case 'this_year': {
                        const a = new Date(t.getFullYear(), 0, 1);
                        const b = new Date(t.getFullYear(), 11, 31);
                        s = a.toISOString().split('T')[0];
                        e = b.toISOString().split('T')[0];
                    }
                    break;
                    case 'last_month': {
                        const a = new Date(t.getFullYear(), t.getMonth() - 1, 1);
                        const b = new Date(t.getFullYear(), t.getMonth(), 0);
                        s = a.toISOString().split('T')[0];
                        e = b.toISOString().split('T')[0];
                    }
                    break;
                    case 'last_quarter': {
                        let q = Math.floor(t.getMonth() / 3) - 1,
                            y = t.getFullYear();
                        if (q < 0) {
                            q = 3;
                            y--;
                        }
                        const a = new Date(y, q * 3, 1);
                        const b = new Date(y, (q + 1) * 3, 0);
                        s = a.toISOString().split('T')[0];
                        e = b.toISOString().split('T')[0];
                    }
                    break;
                    case 'last_year': {
                        const a = new Date(t.getFullYear() - 1, 0, 1);
                        const b = new Date(t.getFullYear() - 1, 11, 31);
                        s = a.toISOString().split('T')[0];
                        e = b.toISOString().split('T')[0];
                    }
                    break;
                    case 'all_dates':
                        s = '2000-01-01';
                        e = new Date().toISOString().split('T')[0];
                        break;
                    default:
                        return;
                }
                $('#start-date').val(s);
                $('#end-date').val(e);
                table.ajax.reload(null, false);
            });
        });
    </script>
@endpush
