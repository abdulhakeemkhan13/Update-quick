@extends('layouts.admin')
@section('page-title') {{ __('Manage Reports') }} @endsection

@push('style')
  <style>
    .accordion-item {
      border: 1px solid #e0e0e0;
      margin-bottom: .7rem
    }

    .accordion-button {
      background: #f8f9fa;
      color: #495057;
      font-weight: 500;
      padding: 1rem 1.25rem;
      border: none;
      box-shadow: none
    }

    .accordion-button:not(.collapsed) {
      background: #e3f2fd;
      color: #1976d2;
      box-shadow: none
    }

    .accordion-button:focus {
      box-shadow: none;
      border-color: transparent
    }

    .accordion-button::after {
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23212529'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e")
    }

    .accordion-body {
      background: #fff
    }

    .report-link {
      text-decoration: none;
      color: #495057;
      transition: color .2s
    }

    .report-link:hover {
      text-decoration: none;
      color: #1976d2
    }

    .report-actions i {
      font-size: .875rem;
      opacity: .6;
      transition: opacity .2s
    }

    .report-item:hover .report-actions i {
      opacity: 1
    }

    .accordion {
      --bs-accordion-border-width: 0;
      --bs-accordion-border-radius: .375rem;
      padding-bottom: 1rem;
      border-radius: .375rem
    }

    @media (max-width:767.98px) {
      .accordion-body {
        padding: 1rem !important
      }

      .report-link {
        font-size: .9rem
      }
    }

    @media (min-width:768px) {
      .row .col-md-6:nth-child(even) .report-item {
        border-right: none
      }

      .row:not(:last-child) .report-item {
        border-bottom: 1px solid #f0f0f0
      }
    }

    .accordion-item:first-child .accordion-button {
      border-top-left-radius: .375rem;
      border-top-right-radius: .375rem
    }

    .accordion-item:last-child .accordion-button.collapsed {
      border-bottom-left-radius: .375rem;
      border-bottom-right-radius: .375rem
    }

    /* rows */
    .report-item {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      border-bottom: 1px solid #f0f0f0;
      padding-left: 1rem;
      padding-right: .5rem;
    }

    .report-item:last-child {
      border-bottom: none
    }

    .report-item.active {
      background: #e3f2fd
    }

    .report-item.active .report-link {
      color: #1976d2;
      font-weight: 500
    }

    .report-link {
      display: flex;
      align-items: center;
      padding: .75rem 0
    }

    .report-help {
      opacity: 0;
      visibility: hidden;
      transition: opacity .18s ease, visibility .18s ease, color .18s ease;
      font-size: .95rem;
      line-height: 1;
      color: #6c757d;
      cursor: pointer;
      margin-left: .375rem;
    }

    .report-item:hover .report-help {
      opacity: 1;
      visibility: visible
    }

    .report-help:hover {
      color: #0d6efd
    }

    .report-actions {
      display: inline-flex;
      align-items: center;
      gap: .5rem
    }

    /* inline description when ? clicked */
    .report-desc {
      order: 9;
      flex-basis: 100%;
      padding: .25rem 0 1rem 0;
      color: #6c757d;
      font-size: .875rem
    }
  </style>
@endpush

@push('script-page')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // open all accordions visually
      document.querySelectorAll('#reportsAccordion .accordion-collapse').forEach(el => el.classList.add('show'));
      document.querySelectorAll('#reportsAccordion .accordion-button').forEach(btn => { btn.classList.remove('collapsed'); btn.setAttribute('aria-expanded', 'true'); });

      // ----- inline description on ? -----
      document.addEventListener('click', function (e) {
        const help = e.target.closest('.report-help'); if (!help) return;
        e.preventDefault(); e.stopPropagation();
        const row = help.closest('li.report-item'); if (!row) return;
        row.parentElement.querySelectorAll('.report-desc').forEach(d => { if (!row.contains(d)) d.remove(); });
        let descEl = row.querySelector(':scope > .report-desc');
        if (descEl) { descEl.remove(); return; }
        descEl = document.createElement('div');
        descEl.className = 'report-desc';
        descEl.textContent = help.getAttribute('data-desc') || '';
        row.appendChild(descEl);
      });

      // ===== Favorites (localStorage) =====
      const FAV_KEY = 'reportFavorites:v1';
      const defaultKeys = ['balance-sheet.index', 'reports.profit_loss', 'ledger.index'];

      const getFavs = () => {
        try { return JSON.parse(localStorage.getItem(FAV_KEY) || '[]'); } catch (e) { return []; }
      };
      const setFavs = (arr) => localStorage.setItem(FAV_KEY, JSON.stringify(arr));

      // Build a map from DOM on demand
      const infoByKey = (key) => {
        const li = document.querySelector(`li.report-item[data-key="${key}"]`);
        if (!li) return null;
        const a = li.querySelector('a.report-link');
        const label = a ? a.textContent.trim() : key;
        const href = a ? a.getAttribute('href') : '#';
        return { key, label, href };
      };

      let favs = getFavs();
      if (!Array.isArray(favs)) favs = [];

      // Seed defaults if empty
      if (favs.length === 0) {
        // only add defaults that exist in the DOM
        const available = new Set(Array.from(document.querySelectorAll('li.report-item')).map(li => li.dataset.key).filter(Boolean));
        favs = defaultKeys.filter(k => available.has(k));
        setFavs(favs);
      }

      const renderStars = () => {
        document.querySelectorAll('.fav-toggle').forEach(icon => {
          const key = icon.dataset.key;
          const isFav = favs.includes(key);
          icon.classList.toggle('bi-star-fill', isFav);
          icon.classList.toggle('bi-star', !isFav);
          icon.classList.toggle('text-success', isFav);
          icon.classList.toggle('text-muted', !isFav);
        });
      };

      const favoritesUL = document.getElementById('favoritesList');
      const favoritesEmpty = document.getElementById('favoritesEmpty');

      const attachFavHandlers = (scope = document) => {
        scope.querySelectorAll('.fav-toggle').forEach(icon => {
          // prevent duplicate listeners
          if (icon.dataset.bound === '1') return;
          icon.dataset.bound = '1';
          icon.addEventListener('click', (e) => {
            e.preventDefault(); e.stopPropagation();
            const key = icon.dataset.key;
            const idx = favs.indexOf(key);
            if (idx > -1) favs.splice(idx, 1); else favs.push(key);
            setFavs(favs);
            renderStars();
            renderFavorites();
          });
        });
      };

      const renderFavorites = () => {
        if (!favoritesUL) return;
        favoritesUL.innerHTML = '';
        favs.forEach(k => {
          const info = infoByKey(k);
          if (!info) return;
          const li = document.createElement('li');
          li.className = 'col-6 report-item';
          li.setAttribute('data-key', info.key);
          li.innerHTML = `
          <div class="d-flex align-items-center w-100" style="min-width:0;">
            <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
              <a class="report-link d-flex align-items-center py-3 pe-2" href="${info.href}">
                <span class="d-flex align-items-center"><i class="bi bi-file-earmark-text me-2"></i>${info.label}</span>
              </a>
            </div>
            <span class="report-actions d-inline-flex align-items-center ms-auto pe-3">
              <i class="bi bi-star-fill text-success fav-toggle" data-key="${info.key}" role="button" aria-label="Toggle favorite"></i>
              <i class="bi bi-three-dots-vertical text-muted ms-1"></i>
            </span>
          </div>`;
          favoritesUL.appendChild(li);
        });
        if (favoritesEmpty) {
          favoritesEmpty.classList.toggle('d-none', favs.length !== 0);
        }
        attachFavHandlers(favoritesUL);
      };

      // initial render
      renderStars();
      renderFavorites();
      attachFavHandlers(document);
    });
  </script>
@endpush

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
  <li class="breadcrumb-item">{{ __('All Reports') }}</li>
@endsection

@section('content')

  @php
    $reportDescriptions = [
      'balance-sheet.index' => "What you own (assets), what you owe (liabilities), and what you invested (equity).",
      'reports.profit_loss' => "Income, expenses, and net income for a chosen period.",
      'balance-sheet-detail.index' => "Drill-down detail behind each balance sheet line.",
      'profit-loss-detail.index' => "Detail behind each income and expense line.",
      'balance-sheet-standard.index' => "A summarized balance sheet view.",
      'profit-loss-by-month' => "Month-by-month Profit & Loss view.",
      'balance-sheet-comparison.index' => "Compare balances for two periods.",
      'profit-loss-comparison' => "Compare income and expenses across two periods.",
      'ledger.index' => "All transactions by account in chronological order.",
      'profit-loss-quaterly' => "Quarterly Profit & Loss summary.",
      'cash-flow.index' => "Cash in/out by operating, investing, and financing activities.",
      'receivables.aging_summary' => "Unpaid customer balances grouped by aging buckets.",
      'receivables.invoices_received_payments' => "Invoices and payments received during the period.",
      'receivables.aging_details' => "Customer-level aging detail for receivables.",
      'receivables.open_invoice_list' => "All unpaid invoices.",
      'receivables.collection_details' => "Collection activity and overdue details.",
      'receivables.invoice_list' => "Full invoice list with statuses.",
      'receivables.customer_balance' => "Customer balance totals.",
      'receivables.customer_balance_detail' => "Customer balance drill-down.",
      'report.salesbyCustomerTypeDetail' => "Sales grouped by customer type with detail.",
      'productservice.inventoryValuationSummary' => "Inventory value and quantities by item.",
      'productservice.incomeByCustomerSummary' => "Product/Service list and income allocation.",
      'customercontact.list' => "Customer contact directory.",
      'productservice.SalesByProductServiceSummary' => "Sales by product/service (summary).",
      'productservice.incomeByCustomerSummaryTwo' => "Income by customer (summary).",
      'productservice.SalesByProductServiceDetail' => "Sales by product/service (detail).",
      'customercontact.list.phone.numbers' => "Customer phone list.",
      'report.sales.salesByCustomerSummary' => "Sales by customer (summary).",
      'report.sales.salesByCustomerDetail' => "Sales by customer (detail).",
      'report.depositDetail' => "Deposit transactions detail.",
      'productservice.transactionListByCustomer' => "Transactions grouped by customer.",
      'productservice.estimatesByCustomer' => "Estimates per customer.",
      'productservice.inventoryValuationDetail' => "Inventory valuation detail per item.",
      'payables.aging_summary' => "Unpaid vendor balances by aging bucket.",
      'payables.unpaid_bills_report' => "All unpaid bills.",
      'payables.aging_details' => "Vendor-level aging detail.",
      'payables.vendor_balance_summary' => "Vendor balance totals.",
      'payables.bills_payments' => "Bills and payments applied.",
      'payables.vendor_balance_detail' => "Vendor balance drill-down.",
      'payables.bill_payment_list' => "Bill payments list.",
      'expenses.open_purchase_order_detail' => "Open purchase orders with line details.",
      'expenses.vendors_phone_list' => "Vendor phone list.",
      'expenses.open_purchase_order_list' => "Open purchase orders list.",
      'expenses.transaction_list_by_vendor' => "Transactions grouped by vendor.",
      'expenses.purchase_list' => "Purchases across the period.",
      'expenses.expenses_by_vendor_summary' => "Expense totals by vendor.",
      'expenses.purchase_by_vendor' => "Purchase detail by vendor.",
      'expenses.vendors_contact_list' => "Vendor contact directory.",
      'SalesTaxLiabilityReport' => "Taxable sales, tax collected, and liabilities owed.",
      'report.taxableSalesSummary' => "Summary of taxable sales.",
      'report.taxableSalesDetail' => "Line-level taxable sales detail.",
      'employees.employeecontactlist' => "Employee contact directory.",
      'report.account.statement' => "Chart of accounts / account list.",
      'report.invoice.summary' => "Invoice totals by status/date.",
      'report.sales' => "Sales KPI overview.",
      'report.receivables' => "Receivables KPI overview.",
      'report.payables' => "Payables KPI overview.",
      'report.bill.summary' => "Bills summary.",
      'report.product.stock.report' => "Product stock levels.",
      'report.monthly.cashflow' => "Monthly cash-flow trends.",
      'report.income.summary' => "Income summary.",
      'report.expense.summary' => "Expense summary.",
      'report.income.vs.expense.summary' => "Income vs Expense comparison.",
      'report.tax.summary' => "Tax summary across periods.",
      'transaction.index' => "All transactions ledger.",
      'transaction.bankTransactions' => "Bank feed / bank transactions.",
      'reciept.index' => "Receipt entries.",
      'report.ledger' => "Ledger summary by account.",
      'report.profit.loss' => "Profit & Loss (alternate route).",
      'trial-balance.index' => "Debits/credits by account at a point in time.",
      'budget.index' => "Budget planning and tracking.",
      'goal.index' => "Financial goals tracker.",
    ];
  @endphp
  <div class="dash-section">{{-- Filter --}}
    <div class="row">
      <div class="col-xl-12">
        <div class="card">
          <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            @include('allReports.report-selector')
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ACCORDIONS --}}
  <div class="row">
    <div class="col-xl-12">
      <div class="card">
        <div class="card-body table-border-style">

          {{-- ================== Favorites ================== --}}
          <div class="accordion pb-2" id="reportsAccordion">
            <div class="accordion-item">
              <h2 class="accordion-header" id="FavoritesOverviewHeading">
                <button class="accordion-button {{ Request::segment(1) == 'report' ? '' : 'collapsed' }}" type="button"
                  data-bs-toggle="collapse" data-bs-target="#FavoritesOverviewCollapse"
                  aria-expanded="{{ Request::segment(1) == 'report' ? 'true' : 'false' }}"
                  aria-controls="FavoritesOverviewCollapse">
                  <i class="bi bi-file-earmark-text me-2"></i>{{ __('Favorites') }}
                </button>
              </h2>

              <div id="FavoritesOverviewCollapse"
                class="accordion-collapse collapse {{ Request::segment(1) == 'report' ? 'show' : '' }}"
                aria-labelledby="FavoritesOverviewHeading" data-bs-parent="#reportsAccordion">
                <div class="accordion-body p-0">
                  <p id="favoritesEmpty" class="text-muted px-3 d-none mb-0">
                    {{ __('No favorites yet. Click the star on any report to add it here.') }}</p>
                  <ul id="favoritesList" class="list-unstyled mb-0 ms-3 row g-0"><!-- filled by JS --></ul>
                </div>
              </div>
            </div>
          </div>

          {{-- ================== Business overview ================== --}}
          <div class="accordion pb-2" id="reportsAccordion">
            <div class="accordion-item">
              <h2 class="accordion-header" id="businessOverviewHeading">
                <button class="accordion-button {{ Request::segment(1) == 'report' ? '' : 'collapsed' }}" type="button"
                  data-bs-toggle="collapse" data-bs-target="#businessOverviewCollapse"
                  aria-expanded="{{ Request::segment(1) == 'report' ? 'true' : 'false' }}"
                  aria-controls="businessOverviewCollapse">
                  <i class="bi bi-file-earmark-text me-2"></i>{{ __('Business Overview') }}
                </button>
              </h2>

              <div id="businessOverviewCollapse"
                class="accordion-collapse collapse {{ Request::segment(1) == 'report' ? 'show' : '' }}"
                aria-labelledby="businessOverviewHeading" data-bs-parent="#reportsAccordion">
                <div class="accordion-body p-0">
                  <ul class="list-unstyled mb-0 ms-3 row g-0">

                    <li
                      class="col-6 report-item {{ Request::route()->getName() == 'balance-sheet.index' ? 'active' : '' }}"
                      data-key="balance-sheet.index">
                      <div class="d-flex align-items-center w-100" style="min-width:0;">
                        <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
                          <a class="report-link d-flex align-items-center py-3 pe-2"
                            href="{{ route('balance-sheet.index') }}">
                            <span class="d-flex align-items-center"><i
                                class="bi bi-file-earmark-text me-2"></i>{{ __('Balance Sheet') }}</span>
                          </a>
                          @include('allReports._help', ['routeKey' => 'balance-sheet.index'])
                        </div>
                        <span class="report-actions d-inline-flex align-items-center ms-auto pe-3">
                          <i class="bi bi-star text-muted fav-toggle" data-key="balance-sheet.index" role="button"
                            aria-label="Toggle favorite"></i>
                          <i class="bi bi-three-dots-vertical text-muted ms-1"></i>
                        </span>
                      </div>
                    </li>

                    <li
                      class="col-6 report-item {{ Request::route()->getName() == 'reports.profit_loss' ? 'active' : '' }}"
                      data-key="reports.profit_loss">
                      <div class="d-flex align-items-center w-100" style="min-width:0;">
                        <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
                          <a class="report-link d-flex align-items-center py-3 pe-2"
                            href="{{ route('reports.profit_loss') }}">
                            <span class="d-flex align-items-center"><i
                                class="bi bi-file-earmark-text me-2"></i>{{ __('Profit and Loss') }}</span>
                          </a>
                          @include('allReports._help', ['routeKey' => 'reports.profit_loss'])
                        </div>
                        <span class="report-actions d-inline-flex align-items-center ms-auto pe-3">
                          <i class="bi bi-star text-muted fav-toggle" data-key="reports.profit_loss" role="button"
                            aria-label="Toggle favorite"></i>
                          <i class="bi bi-three-dots-vertical text-muted ms-1"></i>
                        </span>
                      </div>
                    </li>

                    <li
                      class="col-6 report-item {{ Request::route()->getName() == 'balance-sheet-detail.index' ? 'active' : '' }}"
                      data-key="balance-sheet-detail.index">
                      <div class="d-flex align-items-center w-100" style="min-width:0;">
                        <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
                          <a class="report-link d-flex align-items-center py-3 pe-2"
                            href="{{ route('balance-sheet-detail.index') }}">
                            <span class="d-flex align-items-center"><i
                                class="bi bi-file-earmark-text me-2"></i>{{ __('Balance Sheet Detail') }}</span>
                          </a>
                          @include('allReports._help', ['routeKey' => 'balance-sheet-detail.index'])
                        </div>
                        <span class="report-actions d-inline-flex align-items-center ms-auto pe-3">
                          <i class="bi bi-star text-muted fav-toggle" data-key="balance-sheet-detail.index" role="button"
                            aria-label="Toggle favorite"></i>
                          <i class="bi bi-three-dots-vertical text-muted ms-1"></i>
                        </span>
                      </div>
                    </li>

                    <li
                      class="col-6 report-item {{ Request::route()->getName() == 'profit-loss-detail.index' ? 'active' : '' }}"
                      data-key="profit-loss-detail.index">
                      <div class="d-flex align-items-center w-100" style="min-width:0;">
                        <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
                          <a class="report-link d-flex align-items-center py-3 pe-2"
                            href="{{ route('profit-loss-detail.index') }}">
                            <span class="d-flex align-items-center"><i
                                class="bi bi-file-earmark-text me-2"></i>{{ __('Profit and Loss Detail') }}</span>
                          </a>
                          @include('allReports._help', ['routeKey' => 'profit-loss-detail.index'])
                        </div>
                        <span class="report-actions d-inline-flex align-items-center ms-auto pe-3">
                          <i class="bi bi-star text-muted fav-toggle" data-key="profit-loss-detail.index" role="button"
                            aria-label="Toggle favorite"></i>
                          <i class="bi bi-three-dots-vertical text-muted ms-1"></i>
                        </span>
                      </div>
                    </li>

                    <li
                      class="col-6 report-item {{ Request::route()->getName() == 'balance-sheet-standard.index' ? 'active' : '' }}"
                      data-key="balance-sheet-standard.index">
                      <div class="d-flex align-items-center w-100" style="min-width:0;">
                        <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
                          <a class="report-link d-flex align-items-center py-3 pe-2"
                            href="{{ route('balance-sheet-standard.index') }}">
                            <span class="d-flex align-items-center"><i
                                class="bi bi-file-earmark-text me-2"></i>{{ __('Balance Sheet Summary') }}</span>
                          </a>
                          @include('allReports._help', ['routeKey' => 'balance-sheet-standard.index'])
                        </div>
                        <span class="report-actions d-inline-flex align-items-center ms-auto pe-3">
                          <i class="bi bi-star text-muted fav-toggle" data-key="balance-sheet-standard.index"
                            role="button" aria-label="Toggle favorite"></i>
                          <i class="bi bi-three-dots-vertical text-muted ms-1"></i>
                        </span>
                      </div>
                    </li>

                    <li
                      class="col-6 report-item {{ Request::route()->getName() == 'profit-loss-by-month' ? 'active' : '' }}"
                      data-key="profit-loss-by-month">
                      <div class="d-flex align-items-center w-100" style="min-width:0;">
                        <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
                          <a class="report-link d-flex align-items-center py-3 pe-2"
                            href="{{ route('profit-loss-by-month') }}">
                            <span class="d-flex align-items-center"><i
                                class="bi bi-file-earmark-text me-2"></i>{{ __('Profit and Loss By Month') }}</span>
                          </a>
                          @include('allReports._help', ['routeKey' => 'profit-loss-by-month'])
                        </div>
                        <span class="report-actions d-inline-flex align-items-center ms-auto pe-3">
                          <i class="bi bi-star text-muted fav-toggle" data-key="profit-loss-by-month" role="button"
                            aria-label="Toggle favorite"></i>
                          <i class="bi bi-three-dots-vertical text-muted ms-1"></i>
                        </span>
                      </div>
                    </li>

                    <li
                      class="col-6 report-item {{ Request::route()->getName() == 'balance-sheet-comparison.index' ? 'active' : '' }}"
                      data-key="balance-sheet-comparison.index">
                      <div class="d-flex align-items-center w-100" style="min-width:0;">
                        <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
                          <a class="report-link d-flex align-items-center py-3 pe-2"
                            href="{{ route('balance-sheet-comparison.index') }}">
                            <span class="d-flex align-items-center"><i
                                class="bi bi-file-earmark-text me-2"></i>{{ __('Balance Sheet Comparison') }}</span>
                          </a>
                          @include('allReports._help', ['routeKey' => 'balance-sheet-comparison.index'])
                        </div>
                        <span class="report-actions d-inline-flex align-items-center ms-auto pe-3">
                          <i class="bi bi-star text-muted fav-toggle" data-key="balance-sheet-comparison.index"
                            role="button" aria-label="Toggle favorite"></i>
                          <i class="bi bi-three-dots-vertical text-muted ms-1"></i>
                        </span>
                      </div>
                    </li>

                    <li
                      class="col-6 report-item {{ Request::route()->getName() == 'profit-loss-comparison' ? 'active' : '' }}"
                      data-key="profit-loss-comparison">
                      <div class="d-flex align-items-center w-100" style="min-width:0;">
                        <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
                          <a class="report-link d-flex align-items-center py-3 pe-2"
                            href="{{ route('profit-loss-comparison') }}">
                            <span class="d-flex align-items-center"><i
                                class="bi bi-file-earmark-text me-2"></i>{{ __('Profit and Loss Comparison') }}</span>
                          </a>
                          @include('allReports._help', ['routeKey' => 'profit-loss-comparison'])
                        </div>
                        <span class="report-actions d-inline-flex align-items-center ms-auto pe-3">
                          <i class="bi bi-star text-muted fav-toggle" data-key="profit-loss-comparison" role="button"
                            aria-label="Toggle favorite"></i>
                          <i class="bi bi-three-dots-vertical text-muted ms-1"></i>
                        </span>
                      </div>
                    </li>

                    <li class="col-6 report-item {{ Request::route()->getName() == 'ledger.index' ? 'active' : '' }}"
                      data-key="ledger.index">
                      <div class="d-flex align-items-center w-100" style="min-width:0;">
                        <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
                          <a class="report-link d-flex align-items-center py-3 pe-2" href="{{ route('ledger.index') }}">
                            <span class="d-flex align-items-center"><i
                                class="bi bi-file-earmark-text me-2"></i>{{ __('General Ledger') }}</span>
                          </a>
                          @include('allReports._help', ['routeKey' => 'ledger.index'])
                        </div>
                        <span class="report-actions d-inline-flex align-items-center ms-auto pe-3">
                          <i class="bi bi-star text-muted fav-toggle" data-key="ledger.index" role="button"
                            aria-label="Toggle favorite"></i>
                          <i class="bi bi-three-dots-vertical text-muted ms-1"></i>
                        </span>
                      </div>
                    </li>

                    <li
                      class="col-6 report-item {{ Request::route()->getName() == 'profit-loss-quaterly' ? 'active' : '' }}"
                      data-key="profit-loss-quaterly">
                      <div class="d-flex align-items-center w-100" style="min-width:0;">
                        <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
                          <a class="report-link d-flex align-items-center py-3 pe-2"
                            href="{{ route('profit-loss-quaterly') }}">
                            <span class="d-flex align-items-center"><i
                                class="bi bi-file-earmark-text me-2"></i>{{ __('Profit and Loss Quarterly') }}</span>
                          </a>
                          @include('allReports._help', ['routeKey' => 'profit-loss-quaterly'])
                        </div>
                        <span class="report-actions d-inline-flex align-items-center ms-auto pe-3">
                          <i class="bi bi-star text-muted fav-toggle" data-key="profit-loss-quaterly" role="button"
                            aria-label="Toggle favorite"></i>
                          <i class="bi bi-three-dots-vertical text-muted ms-1"></i>
                        </span>
                      </div>
                    </li>

                    <li class="col-6 report-item {{ Request::route()->getName() == 'cash-flow.index' ? 'active' : '' }}"
                      data-key="cash-flow.index">
                      <div class="d-flex align-items-center w-100" style="min-width:0;">
                        <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
                          <a class="report-link d-flex align-items-center py-3 pe-2"
                            href="{{ route('cash-flow.index') }}">
                            <span class="d-flex align-items-center"><i
                                class="bi bi-file-earmark-text me-2"></i>{{ __('Statement of Cash Flows') }}</span>
                          </a>
                          @include('allReports._help', ['routeKey' => 'cash-flow.index'])
                        </div>
                        <span class="report-actions d-inline-flex align-items-center ms-auto pe-3">
                          <i class="bi bi-star text-muted fav-toggle" data-key="cash-flow.index" role="button"
                            aria-label="Toggle favorite"></i>
                          <i class="bi bi-three-dots-vertical text-muted ms-1"></i>
                        </span>
                      </div>
                    </li>

                  </ul>
                </div>
              </div>
            </div>
          </div>

          {{-- ================== Who owes you ================== --}}
          <div class="accordion pb-2" id="reportsAccordion">
            <div class="accordion-item">
              <h2 class="accordion-header" id="whoOwesYouHeading">
                <button class="accordion-button {{ Request::segment(1) == 'report' ? '' : 'collapsed' }}" type="button"
                  data-bs-toggle="collapse" data-bs-target="#whoOwesYouCollapse"
                  aria-expanded="{{ Request::segment(1) == 'report' ? 'true' : 'false' }}"
                  aria-controls="whoOwesYouCollapse">
                  <i class="bi bi-file-earmark-text me-2"></i>{{ __('Who Owes You') }}
                </button>
              </h2>
              <div id="whoOwesYouCollapse"
                class="accordion-collapse collapse {{ Request::segment(1) == 'report' ? 'show' : '' }}"
                aria-labelledby="whoOwesYouHeading" data-bs-parent="#reportsAccordion">
                <div class="accordion-body p-0">
                  <ul class="list-unstyled ms-3 mb-0 row g-0">
                    @php
                      $receivableReports = [
                        ['route' => 'receivables.aging_summary', 'label' => 'Accounts receivable aging summary'],
                        ['route' => 'receivables.invoices_received_payments', 'label' => 'Invoices and received payments'],
                        ['route' => 'receivables.aging_details', 'label' => 'Accounts receivable aging details'],
                        ['route' => 'receivables.open_invoice_list', 'label' => 'Open Invoice'],
                        ['route' => 'receivables.collection_details', 'label' => 'Collection Report'],
                        ['route' => 'receivables.invoice_list', 'label' => 'Invoice list'],
                        ['route' => 'receivables.customer_balance', 'label' => 'Customer balance summary'],
                        ['route' => 'receivables.customer_balance_detail', 'label' => 'Customer balance detail report'],
                      ];
                    @endphp

                    @foreach ($receivableReports as $r)
                      <li class="col-6 report-item {{ Request::route()->getName() == $r['route'] ? 'active' : '' }}"
                        data-key="{{ $r['route'] }}">
                        <div class="d-flex align-items-center w-100" style="min-width:0;">
                          <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
                            <a class="report-link d-flex align-items-center py-3 pe-2" href="{{ route($r['route']) }}">
                              <span class="d-flex align-items-center"><i
                                  class="bi bi-file-earmark-text me-2"></i>{{ __($r['label']) }}</span>
                            </a>
                            @include('allReports._help', ['routeKey' => $r['route']])
                          </div>
                          <span class="report-actions d-inline-flex align-items-center ms-auto pe-3">
                            <i class="bi bi-star text-muted fav-toggle" data-key="{{ $r['route'] }}" role="button"
                              aria-label="Toggle favorite"></i>
                            <i class="bi bi-three-dots-vertical text-muted ms-1"></i>
                          </span>
                        </div>
                      </li>
                    @endforeach

                  </ul>
                </div>
              </div>
            </div>
          </div>

          {{-- ================== Sales and Customers ================== --}}
          @php
            $salesAndCustomersReports = [
              ['route' => 'report.salesbyCustomerTypeDetail', 'label' => 'Sales by Customer Type Detail'],
              ['route' => 'productservice.inventoryValuationSummary', 'label' => 'Inventory Valuation Summary'],
              ['route' => 'productservice.incomeByCustomerSummary', 'label' => 'Product/Service List'],
              ['route' => 'customer.contact.list', 'label' => 'Customer Contact List'],
              ['route' => 'productservice.SalesByProductServiceSummary', 'label' => 'Sales by Product/Service Summary'],
              ['route' => 'productservice.incomeByCustomerSummaryTwo', 'label' => 'Income by Customer Summary'],
              ['route' => 'productservice.SalesByProductServiceDetail', 'label' => 'Sales by Product/Service Detail'],
              ['route' => 'customer.contact.list.phone.numbers', 'label' => 'Customer Phone List'],
              ['route' => 'report.sales.salesByCustomerSummary', 'label' => 'Sales by Customer Summary'],
              ['route' => 'report.sales.salesByCustomerDetail', 'label' => 'Sales by Customer Detail'],
              ['route' => 'report.depositDetail', 'label' => 'Deposit Detail'],
              ['route' => 'productservice.transactionListByCustomer', 'label' => 'Transaction List by Customer'],
              ['route' => 'productservice.estimatesByCustomer', 'label' => 'Estimates by Customer'],
              ['route' => 'productservice.inventoryValuationDetail', 'label' => 'Inventory Valuation Detail'],
            ];
          @endphp
          <div class="accordion pb-2" id="reportsAccordion">
            <div class="accordion-item">
              <h2 class="accordion-header" id="salesAndCustomersHeading">
                <button class="accordion-button {{ Request::segment(1) == 'report' ? '' : 'collapsed' }}" type="button"
                  data-bs-toggle="collapse" data-bs-target="#salesAndCustomers"
                  aria-expanded="{{ Request::segment(1) == 'report' ? 'true' : 'false' }}"
                  aria-controls="salesAndCustomers">
                  <i class="bi bi-file-earmark-text me-2"></i>{{ __('Sales and Customers') }}
                </button>
              </h2>
              <div id="salesAndCustomers"
                class="accordion-collapse collapse {{ Request::segment(1) == 'report' ? 'show' : '' }}"
                aria-labelledby="salesAndCustomersHeading" data-bs-parent="#reportsAccordion">
                <div class="accordion-body p-0">
                  <ul class="list-unstyled ms-3 mb-0 row g-0">
                    @foreach ($salesAndCustomersReports as $r)
                      <li
                        class="col-6 report-item {{ $r['route'] && Request::route()->getName() == $r['route'] ? 'active' : '' }}"
                        data-key="{{ $r['route'] }}">
                        <div class="d-flex align-items-center w-100" style="min-width:0;">
                          <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
                            <a class="report-link d-flex align-items-center py-3 pe-2"
                              href="{{ $r['route'] ? route($r['route']) : 'javascript:void(0)' }}">
                              <span class="d-flex align-items-center"><i
                                  class="bi bi-file-earmark-text me-2"></i>{{ __($r['label']) }}</span>
                            </a>
                            @include('allReports._help', ['routeKey' => $r['route']])
                          </div>
                          <span class="report-actions d-inline-flex align-items-center ms-auto pe-3">
                            <i class="bi bi-star text-muted fav-toggle" data-key="{{ $r['route'] }}" role="button"
                              aria-label="Toggle favorite"></i>
                            <i class="bi bi-three-dots-vertical text-muted ms-1"></i>
                          </span>
                        </div>
                      </li>
                    @endforeach
                  </ul>
                </div>
              </div>
            </div>
          </div>

          {{-- ================== Who You Owe ================== --}}
          @php
            $payableReports = [
              ['route' => 'payables.aging_summary', 'label' => 'Accounts payable aging summary'],
              ['route' => 'payables.unpaid_bills_report', 'label' => 'Unpaid Bills'],
              ['route' => 'payables.aging_details', 'label' => 'Accounts payable aging details'],
              ['route' => 'payables.vendor_balance_summary', 'label' => 'Vendor balance summary'],
              ['route' => 'payables.bills_payments', 'label' => 'Bills and Applied payments'],
              ['route' => 'payables.vendor_balance_detail', 'label' => 'Vendor balance detail'],
              ['route' => 'payables.bill_payment_list', 'label' => 'Bill Payment List'],
             
            ];
          @endphp
          <div class="accordion pb-2" id="reportsAccordion">
            <div class="accordion-item">
              <h2 class="accordion-header" id="whoYouOweHeading">
                <button class="accordion-button {{ Request::segment(1) == 'report' ? '' : 'collapsed' }}" type="button"
                  data-bs-toggle="collapse" data-bs-target="#whoYouOweCollapse"
                  aria-expanded="{{ Request::segment(1) == 'report' ? 'true' : 'false' }}"
                  aria-controls="whoYouOweCollapse">
                  <i class="bi bi-file-earmark-text me-2"></i>{{ __('Who You Owe') }}
                </button>
              </h2>
              <div id="whoYouOweCollapse"
                class="accordion-collapse collapse {{ Request::segment(1) == 'report' ? 'show' : '' }}"
                aria-labelledby="whoYouOweHeading" data-bs-parent="#reportsAccordion">
                <div class="accordion-body p-0">
                  <ul class="list-unstyled ms-3 mb-0 row g-0">
                    @foreach ($payableReports as $r)
                      <li class="col-6 report-item {{ Request::route()->getName() == $r['route'] ? 'active' : '' }}"
                        data-key="{{ $r['route'] }}">
                        <div class="d-flex align-items-center w-100" style="min-width:0;">
                          <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
                            <a class="report-link d-flex align-items-center py-3 pe-2" href="{{ route($r['route']) }}">
                              <span class="d-flex align-items-center"><i
                                  class="bi bi-file-earmark-text me-2"></i>{{ __($r['label']) }}</span>
                            </a>
                            @include('allReports._help', ['routeKey' => $r['route']])
                          </div>
                          <span class="report-actions d-inline-flex align-items-center ms-auto pe-3">
                            <i class="bi bi-star text-muted fav-toggle" data-key="{{ $r['route'] }}" role="button"
                              aria-label="Toggle favorite"></i>
                            <i class="bi bi-three-dots-vertical text-muted ms-1"></i>
                          </span>
                        </div>
                      </li>
                    @endforeach
                  </ul>
                </div>
              </div>
            </div>
          </div>

          {{-- ================== Expenses & Vendors ================== --}}
          @php
            $expenseReports = [
              ['route' => 'expenses.purchase_by_vendor', 'label' => 'Purchase by Vendor Detail'],
              ['route' => 'expenses.purchases_by_product_service_detail', 'label' => 'Purchases by Product Service Detail'],
              ['route' => 'expenses.vendors_contact_list', 'label' => 'Vendors Contact List'],
              ['route' => 'expenses.open_purchase_order_detail', 'label' => 'Open Purchase Order Detail'],
              ['route' => 'expenses.expenses_by_vendor_summary', 'label' => 'Expenses by Vendor Summary'],
              ['route' => 'expenses.open_purchase_order_list', 'label' => 'Open Purchase Order List'],
              ['route' => 'expenses.vendors_phone_list', 'label' => 'Vendors Phone List'],
              ['route' => 'expenses.purchase_list', 'label' => 'Purchase List'],
              ['route' => 'expenses.transaction_list_by_vendor', 'label' => 'Transaction List by Vendor'],
            ];
          @endphp
          <div class="accordion pb-2" id="reportsAccordion">
            <div class="accordion-item">
              <h2 class="accordion-header" id="expensesAndVendorsHeading">
                <button class="accordion-button {{ Request::segment(1) == 'report' ? '' : 'collapsed' }}" type="button"
                  data-bs-toggle="collapse" data-bs-target="#expensesAndVendors"
                  aria-expanded="{{ Request::segment(1) == 'report' ? 'true' : 'false' }}"
                  aria-controls="expensesAndVendors">
                  <i class="bi bi-file-earmark-text me-2"></i>{{ __('Expenses And Vendors') }}
                </button>
              </h2>
              <div id="expensesAndVendors"
                class="accordion-collapse collapse {{ Request::segment(1) == 'report' ? 'show' : '' }}"
                aria-labelledby="expensesAndVendors" data-bs-parent="#reportsAccordion">
                <div class="accordion-body p-0">
                  <ul class="list-unstyled ms-3 mb-0 row g-0">
                    @foreach ($expenseReports as $r)
                      <li class="col-6 report-item {{ Request::route()->getName() == $r['route'] ? 'active' : '' }}"
                        data-key="{{ $r['route'] }}">
                        <div class="d-flex align-items-center w-100" style="min-width:0;">
                          <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
                            <a class="report-link d-flex align-items-center py-3 pe-2" href="{{ route($r['route']) }}">
                              <span class="d-flex align-items-center"><i
                                  class="bi bi-file-earmark-text me-2"></i>{{ __($r['label']) }}</span>
                            </a>
                            @include('allReports._help', ['routeKey' => $r['route']])
                          </div>
                          <span class="report-actions d-inline-flex align-items-center ms-auto pe-3">
                            <i class="bi bi-star text-muted fav-toggle" data-key="{{ $r['route'] }}" role="button"
                              aria-label="Toggle favorite"></i>
                            <i class="bi bi-three-dots-vertical text-muted ms-1"></i>
                          </span>
                        </div>
                      </li>
                    @endforeach
                  </ul>
                </div>
              </div>
            </div>
          </div>

          {{-- ================== Sales Tax ================== --}}
          <div class="accordion pb-2" id="reportsAccordion">
            <div class="accordion-item">
              <h2 class="accordion-header" id="salesTaxHeading">
                <button class="accordion-button {{ Request::segment(1) == 'report' ? '' : 'collapsed' }}" type="button"
                  data-bs-toggle="collapse" data-bs-target="#salesTaxCollapse"
                  aria-expanded="{{ Request::segment(1) == 'report' ? 'true' : 'false' }}"
                  aria-controls="salesTaxCollapse">
                  <i class="bi bi-file-earmark-text me-2"></i>{{ __('Sales Tax') }}
                </button>
              </h2>
              <div id="salesTaxCollapse"
                class="accordion-collapse collapse {{ Request::segment(1) == 'report' ? 'show' : '' }}"
                aria-labelledby="salesTaxHeading" data-bs-parent="#reportsAccordion">
                <div class="accordion-body p-0">
                  <ul class="list-unstyled ms-3 mb-0 row g-0">

                    <li
                      class="col-6 report-item {{ Request::route()->getName() == 'SalesTaxLiabilityReport' ? 'active' : '' }}"
                      data-key="SalesTaxLiabilityReport">
                      <div class="d-flex align-items-center w-100" style="min-width:0;">
                        <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
                          <a class="report-link d-flex align-items-center py-3 pe-2"
                            href="{{ route('SalesTaxLiabilityReport') }}">
                            <span class="d-flex align-items-center"><i
                                class="bi bi-file-earmark-text me-2"></i>{{ __('Sales Tax Liability Report') }}</span>
                          </a>
                          @include('allReports._help', ['routeKey' => 'SalesTaxLiabilityReport'])
                        </div>
                        <span class="report-actions d-inline-flex align-items-center ms-auto pe-3">
                          <i class="bi bi-star text-muted fav-toggle" data-key="SalesTaxLiabilityReport" role="button"
                            aria-label="Toggle favorite"></i>
                          <i class="bi bi-three-dots-vertical text-muted ms-1"></i>
                        </span>
                      </div>
                    </li>

                    <li
                      class="col-6 report-item {{ Request::route()->getName() == 'report.taxableSalesSummary' ? 'active' : '' }}"
                      data-key="report.taxableSalesSummary">
                      <div class="d-flex align-items-center w-100" style="min-width:0%;">
                        <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
                          <a class="report-link d-flex align-items-center py-3 pe-2"
                            href="{{ route('report.taxableSalesSummary') }}">
                            <span class="d-flex align-items-center"><i
                                class="bi bi-file-earmark-text me-2"></i>{{ __('Taxable Sales Summary') }}</span>
                          </a>
                          @include('allReports._help', ['routeKey' => 'report.taxableSalesSummary'])
                        </div>
                        <span class="report-actions d-inline-flex align-items-center ms-auto pe-3">
                          <i class="bi bi-star text-muted fav-toggle" data-key="report.taxableSalesSummary" role="button"
                            aria-label="Toggle favorite"></i>
                          <i class="bi bi-three-dots-vertical text-muted ms-1"></i>
                        </span>
                      </div>
                    </li>

                    <li
                      class="col-6 report-item {{ Request::route()->getName() == 'report.taxableSalesDetail' ? 'active' : '' }}"
                      data-key="report.taxableSalesDetail">
                      <div class="d-flex align-items-center w-100" style="min-width:0;">
                        <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
                          <a class="report-link d-flex align-items-center py-3 pe-2"
                            href="{{ route('report.taxableSalesDetail') }}">
                            <span class="d-flex align-items-center"><i
                                class="bi bi-file-earmark-text me-2"></i>{{ __('Taxable Sales Detail') }}</span>
                          </a>
                          @include('allReports._help', ['routeKey' => 'report.taxableSalesDetail'])
                        </div>
                        <span class="report-actions d-inline-flex align-items-center ms-auto pe-3">
                          <i class="bi bi-star text-muted fav-toggle" data-key="report.taxableSalesDetail" role="button"
                            aria-label="Toggle favorite"></i>
                          <i class="bi bi-three-dots-vertical text-muted ms-1"></i>
                        </span>
                      </div>
                    </li>

                  </ul>
                </div>
              </div>
            </div>
          </div>

          {{-- ================== Employees ================== --}}
          @php $employeeReports = [['route' => 'employees.employeecontactlist', 'label' => 'Employee Contact List']]; @endphp
          <div class="accordion pb-2" id="reportsAccordion">
            <div class="accordion-item">
              <h2 class="accordion-header" id="EmployeesHeading">
                <button class="accordion-button {{ Request::segment(1) == 'report' ? '' : 'collapsed' }}" type="button"
                  data-bs-toggle="collapse" data-bs-target="#Employees"
                  aria-expanded="{{ Request::segment(1) == 'report' ? 'true' : 'false' }}" aria-controls="Employees">
                  <i class="bi bi-file-earmark-text me-2"></i>{{ __('Employees') }}
                </button>
              </h2>
              <div id="Employees"
                class="accordion-collapse collapse {{ Request::segment(1) == 'report' ? 'show' : 'false' }}"
                aria-labelledby="Employees" data-bs-parent="#reportsAccordion">
                <div class="accordion-body p-0">
                  <ul class="list-unstyled ms-3 mb-0 row g-0">
                    @foreach ($employeeReports as $r)
                      <li class="col-6 report-item {{ Request::route()->getName() == $r['route'] ? 'active' : '' }}"
                        data-key="{{ $r['route'] }}">
                        <div class="d-flex align-items-center w-100" style="min-width:0;">
                          <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
                            <a class="report-link d-flex align-items-center py-3 pe-2" href="{{ route($r['route']) }}">
                              <span class="d-flex align-items-center"><i
                                  class="bi bi-file-earmark-text me-2"></i>{{ __($r['label']) }}</span>
                            </a>
                            @include('allReports._help', ['routeKey' => $r['route']])
                          </div>
                          <span class="report-actions d-inline-flex align-items-center ms-auto pe-3">
                            <i class="bi bi-star text-muted fav-toggle" data-key="{{ $r['route'] }}" role="button"
                              aria-label="Toggle favorite"></i>
                            <i class="bi bi-three-dots-vertical text-muted ms-1"></i>
                          </span>
                        </div>
                      </li>
                    @endforeach
                  </ul>
                </div>
              </div>
            </div>
          </div>

          {{-- ================== For my accountant ================== --}}
           {{-- ================== For my accountant ================== --}}
                    @php
                        $accountingreportsOld = [
                            ['route' => 'report.account.statement', 'name' => __('Account List')],
                            ['route' => 'report.invoice.summary', 'name' => __('Invoice Summary')],
                            ['route' => 'report.sales', 'name' => __('Sales Report')],
                            ['route' => 'report.receivables', 'name' => __('Receivables')],
                            ['route' => 'report.payables', 'name' => __('Payables')],
                            ['route' => 'report.bill.summary', 'name' => __('Bill Summary')],
                            ['route' => 'report.product.stock.report', 'name' => __('Product Stock')],
                            ['route' => 'report.monthly.cashflow', 'name' => __('Cash Flow')],
                            ['route' => 'report.income.summary', 'name' => __('Income Summary')],
                            ['route' => 'report.expense.summary', 'name' => __('Expense Summary')],
                            ['route' => 'report.income.vs.expense.summary', 'name' => __('Income VS Expense')],
                            ['route' => 'report.tax.summary', 'name' => __('Tax Summary')],
                            ['route' => 'transaction.index', 'name' => __('All Transactions')],
                            ['route' => 'transaction.bankTransactions', 'name' => __('Bank Transactions')],
                            ['route' => 'reciept.index', 'name' => __('Receipts')],
                            ['route' => 'report.ledger', 'name' => __('Ledger Summary'), 'params' => [0]],
                            ['route' => 'report.profit.loss', 'name' => __('Profit & Loss')],
                            ['route' => 'trial-balance.index', 'name' => __('Trial Balance')],
                            ['route' => 'budget.index', 'name' => __('Budget Planner')],
                            ['route' => 'goal.index', 'name' => __('Financial Goal')],
                        ];

                        $accountingreports = [
                            'right' => [
                              ['route' => 'reports.profit_loss', 'name' => __('Profit and Loss')],
                              ['route' => 'profit-loss-detail.index', 'name' => __('Profit and Loss Detail')],
                              ['route' => 'profit-loss-by-month', 'name' => __('Profit and Loss by Month')],
                              ['route' => 'profit-loss-comparison', 'name' => __('Profit and Loss Comparison')],
                              ['route' => 'profit-loss-quaterly', 'name' => __('Quarterly Profit and Loss')],
                              ['route' => 'trial-balance.index', 'name' => __('Trial Balance')],
                            ],
                            'left' => [
                              ['route' => 'formyaccountant.account-list', 'name' => __('Account List')],
                              ['route' => 'balance-sheet.index', 'name' => __('Balance Sheet')],
                              ['route' => 'balance-sheet-comparison.index', 'name' => __('Balance Sheet Comparison')],
                              ['route' => 'balance-sheet-detail.index', 'name' => __('Balance Sheet Detail')],
                              ['route' => 'balance-sheet-standard.index', 'name' => __('Standard Balance Sheet')],
                              ['route' => 'ledger.index', 'name' => __('General Ledger')],
                              ['route' => 'general-journal.index', 'name' => __('General Journal')],
                              ['route' => 'Journalledger.index' , 'name' => 'Journal']
                            ],
                        ];

                    @endphp
                    <div class="accordion pb-2" id="reportsAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="accountingHeading">
                                <button class="accordion-button {{ Request::segment(1) == 'report' ? '' : 'collapsed' }}"
                                    type="button" data-bs-toggle="collapse" data-bs-target="#accountingCollapse"
                                    aria-expanded="{{ Request::segment(1) == 'report' ? 'true' : 'false' }}"
                                    aria-controls="accountingCollapse">
                                    <i class="bi bi-file-earmark-text me-2"></i>{{ __('For my accountant') }}
                                </button>
                            </h2>

                            <div id="accountingCollapse"
                                class="accordion-collapse collapse {{ Request::segment(1) == 'report' ? 'show' : '' }}"
                                aria-labelledby="accountingHeading" data-bs-parent="#reportsAccordion">
                                <div class="accordion-body p-0">
                                    <div class="row g-0">
                                        <!-- Left Column -->
                                        <div class="col-12 col-md-6">
                                            <ul class="list-unstyled ms-3 mb-0">
                                                @foreach ($accountingreports['left'] as $r)
                                                    @php
                                                        $isActive =
                                                            Request::route()->getName() == $r['route'] ? 'active' : '';
                                                        $params = $r['params'] ?? [];
                                                    @endphp
                                                    <li class="report-item {{ $isActive }}"
                                                        data-key="{{ $r['route'] }}">
                                                        <div class="d-flex align-items-center w-100" style="min-width:0;">
                                                            <div class="d-flex align-items-center flex-grow-1"
                                                                style="min-width:0;">
                                                                <a class="report-link d-flex align-items-center py-3 pe-2"
                                                                    href="{{ route($r['route'], $params) }}">
                                                                    <span class="d-flex align-items-center">
                                                                        <i
                                                                            class="bi bi-file-earmark-text me-2"></i>{{ $r['name'] }}
                                                                    </span>
                                                                </a>
                                                                @include('allReports._help', [
                                                                    'routeKey' => $r['route'],
                                                                ])
                                                            </div>
                                                            <span
                                                                class="report-actions d-inline-flex align-items-center ms-auto pe-3">
                                                                <i class="bi bi-star text-muted fav-toggle"
                                                                    data-key="{{ $r['route'] }}" role="button"
                                                                    aria-label="Toggle favorite"></i>
                                                                <i class="bi bi-three-dots-vertical text-muted ms-1"></i>
                                                            </span>
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>

                                        <!-- Right Column -->
                                        <div class="col-12 col-md-6">
                                            <ul class="list-unstyled ms-3 mb-0">
                                                @foreach ($accountingreports['right'] as $r)
                                                    @php
                                                        $isActive =
                                                            Request::route()->getName() == $r['route'] ? 'active' : '';
                                                        $params = $r['params'] ?? [];
                                                    @endphp
                                                    <li class="report-item {{ $isActive }}"
                                                        data-key="{{ $r['route'] }}">
                                                        <div class="d-flex align-items-center w-100" style="min-width:0;">
                                                            <div class="d-flex align-items-center flex-grow-1"
                                                                style="min-width:0;">
                                                                <a class="report-link d-flex align-items-center py-3 pe-2"
                                                                    href="{{ route($r['route'], $params) }}">
                                                                    <span class="d-flex align-items-center">
                                                                        <i
                                                                            class="bi bi-file-earmark-text me-2"></i>{{ $r['name'] }}
                                                                    </span>
                                                                </a>
                                                                @include('allReports._help', [
                                                                    'routeKey' => $r['route'],
                                                                ])
                                                            </div>
                                                            <span
                                                                class="report-actions d-inline-flex align-items-center ms-auto pe-3">
                                                                <i class="bi bi-star text-muted fav-toggle"
                                                                    data-key="{{ $r['route'] }}" role="button"
                                                                    aria-label="Toggle favorite"></i>
                                                                <i class="bi bi-three-dots-vertical text-muted ms-1"></i>
                                                            </span>
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


          {{-- ================== POS ================== --}}
          <div class="accordion pb-2" id="reportsAccordion">
            <div class="accordion-item">
              <h2 class="accordion-header" id="posHeading">
                <button
                  class="accordion-button {{ Request::segment(1) == 'reports-warehouse' || Request::segment(1) == 'reports-daily-purchase' || Request::segment(1) == 'reports-monthly-purchase' || Request::segment(1) == 'reports-daily-pos' || Request::segment(1) == 'reports-monthly-pos' || Request::segment(1) == 'reports-pos-vs-purchase' ? '' : 'collapsed' }}"
                  type="button" data-bs-toggle="collapse" data-bs-target="#posCollapse"
                  aria-expanded="{{ Request::segment(1) == 'reports-warehouse' || Request::segment(1) == 'reports-daily-purchase' || Request::segment(1) == 'reports-monthly-purchase' || Request::segment(1) == 'reports-daily-pos' || Request::segment(1) == 'reports-monthly-pos' || Request::segment(1) == 'reports-pos-vs-purchase' ? 'true' : 'false' }}"
                  aria-controls="posCollapse">
                  <i class="bi bi-shop me-2"></i>{{ __('POS Reports') }}
                </button>
              </h2>
              <div id="posCollapse"
                class="accordion-collapse collapse {{ Request::segment(1) == 'reports-warehouse' || Request::segment(1) == 'reports-daily-purchase' || Request::segment(1) == 'reports-monthly-purchase' || Request::segment(1) == 'reports-daily-pos' || Request::segment(1) == 'reports-monthly-pos' || Request::segment(1) == 'reports-pos-vs-purchase' ? 'show' : '' }}"
                aria-labelledby="posHeading" data-bs-parent="#reportsAccordion">
                <div class="accordion-body p-0">
                  <ul class="list-unstyled ms-3 mb-0 row g-0">

                    <li class="col-6 report-item {{ request()->is('reports-warehouse') ? 'active' : '' }}"
                      data-key="report.warehouse">
                      <div class="d-flex align-items-center w-100" style="min-width:0;">
                        <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
                          <a class="report-link d-flex align-items-center py-3 pe-2"
                            href="{{ route('report.warehouse') }}">
                            <span class="d-flex align-items-center"><i
                                class="bi bi-file-earmark-text me-2"></i>{{ __('Warehouse Report') }}</span>
                          </a>
                          @include('allReports._help', ['routeKey' => 'report.warehouse'])
                        </div>
                        <span class="report-actions d-inline-flex align-items-center ms-auto pe-3">
                          <i class="bi bi-star text-muted fav-toggle" data-key="report.warehouse" role="button"
                            aria-label="Toggle favorite"></i>
                          <i class="bi bi-three-dots-vertical text-muted ms-1"></i>
                        </span>
                      </div>
                    </li>

                    <li
                      class="col-6 report-item {{ request()->is('reports-daily-purchase') || request()->is('reports-monthly-purchase') ? 'active' : '' }}"
                      data-key="report.daily.purchase">
                      <div class="d-flex align-items-center w-100" style="min-width:0;">
                        <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
                          <a class="report-link d-flex align-items-center py-3 pe-2"
                            href="{{ route('report.daily.purchase') }}">
                            <span class="d-flex align-items-center"><i
                                class="bi bi-file-earmark-text me-2"></i>{{ __('Purchase Daily/Monthly Report') }}</span>
                          </a>
                          @include('allReports._help', ['routeKey' => 'report.daily.purchase'])
                        </div>
                        <span class="report-actions d-inline-flex align-items-center ms-auto pe-3">
                          <i class="bi bi-star text-muted fav-toggle" data-key="report.daily.purchase" role="button"
                            aria-label="Toggle favorite"></i>
                          <i class="bi bi-three-dots-vertical text-muted ms-1"></i>
                        </span>
                      </div>
                    </li>

                    <li
                      class="col-6 report-item {{ request()->is('reports-daily-pos') || request()->is('reports-monthly-pos') ? 'active' : '' }}"
                      data-key="report.daily.pos">
                      <div class="d-flex align-items-center w-100" style="min-width:0;">
                        <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
                          <a class="report-link d-flex align-items-center py-3 pe-2"
                            href="{{ route('report.daily.pos') }}">
                            <span class="d-flex align-items-center"><i
                                class="bi bi-file-earmark-text me-2"></i>{{ __('POS Daily/Monthly Report') }}</span>
                          </a>
                          @include('allReports._help', ['routeKey' => 'report.daily.pos'])
                        </div>
                        <span class="report-actions d-inline-flex align-items-center ms-auto pe-3">
                          <i class="bi bi-star text-muted fav-toggle" data-key="report.daily.pos" role="button"
                            aria-label="Toggle favorite"></i>
                          <i class="bi bi-three-dots-vertical text-muted ms-1"></i>
                        </span>
                      </div>
                    </li>

                    <li class="col-6 report-item {{ request()->is('reports-pos-vs-purchase') ? 'active' : '' }}"
                      data-key="report.pos.vs.purchase">
                      <div class="d-flex align-items-center w-100" style="min-width:0;">
                        <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
                          <a class="report-link d-flex align-items-center py-3 pe-2"
                            href="{{ route('report.pos.vs.purchase') }}">
                            <span class="d-flex align-items-center"><i
                                class="bi bi-file-earmark-text me-2"></i>{{ __('POS VS Purchase Report') }}</span>
                          </a>
                          @include('allReports._help', ['routeKey' => 'report.pos.vs.purchase'])
                        </div>
                        <span class="report-actions d-inline-flex align-items-center ms-auto pe-3">
                          <i class="bi bi-star text-muted fav-toggle" data-key="report.pos.vs.purchase" role="button"
                            aria-label="Toggle favorite"></i>
                          <i class="bi bi-three-dots-vertical text-muted ms-1"></i>
                        </span>
                      </div>
                    </li>

                  </ul>
                </div>
              </div>
            </div>
          </div>

        </div> {{-- card-body --}}
      </div>
    </div>
  </div>
</div>

@endsection