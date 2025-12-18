@extends('layouts.admin')
@push('script-page')
@endpush
@section('page-title')
    {{ __('Manage Customer-Detail') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customer.index') }}">{{ __('Customer') }}</a></li>
    <li class="breadcrumb-item">{{ $customer['name'] }}</li>
@endsection

@push('script-page')
    {{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script> --}}
    <script>
        function copyToClipboard(element) {
            var copyText = element.id;
            navigator.clipboard.writeText(copyText);
            show_toastr('success', 'Url copied to clipboard', 'success');
        }
    </script>
@endpush

@section('action-btn')
    <div class="float-end d-flex gap-2 align-items-center">
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-success dropdown-toggle" data-bs-toggle="dropdown"
                aria-expanded="false">
                <i class="ti ti-plus me-2"></i>{{ __('New Transaction') }}
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                @can('create invoice')
                    <li><a class="dropdown-item" href="{{ route('invoice.create', $customer->id) }}">
                            <i class="ti ti-file me-2"></i>{{ __('Invoice') }}
                        </a></li>
                @endcan
                @can('create proposal')
                    <li><a class="dropdown-item" href="{{ route('proposal.create', $customer->id) }}">
                            <i class="ti ti-clipboard me-2"></i>{{ __('Proposal') }}
                        </a></li>
                @endcan
                <li><a class="dropdown-item" href="{{ route('receive-payment.create', $customer->id) }}">
                        <i class="ti ti-clipboard me-2"></i>{{ __('Payment') }}
                    </a></li>
                </a>
            </ul>
        </div>

        <!-- Edit button - opens customer edit modal -->
        <a href="#" data-size="lg" data-url="{{ route('customer.edit', $customer->id) }}" data-ajax-popup="true"
            data-bs-toggle="tooltip" title="{{ __('Edit Customer') }}" data-title="{{ __('Edit Customer') }}"
            class="btn btn-sm btn-primary">
            <i class="ti ti-pencil"></i>
        </a>


        @can('delete customer')
            {!! Form::open([
                'method' => 'DELETE',
                'class' => 'delete-form-btn',
                'route' => ['customer.destroy', $customer['id']],
            ]) !!}
            <a href="#" data-bs-toggle="tooltip" title="{{ __('Delete Customer') }}"
                data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                data-confirm-yes="document.getElementById('delete-form-{{ $customer['id'] }}').submit();"
                class="btn btn-sm btn-danger bs-pass-para">
                <i class="ti ti-trash text-white"></i>
            </a>
            {!! Form::close() !!}
        @endcan
    </div>
@endsection

@section('content')
    @php
        $totalInvoiceSum = $customer->customerTotalInvoiceSum($customer['id']);
        $totalInvoice = $customer->customerTotalInvoice($customer['id']);
        $averageSale = $totalInvoiceSum != 0 ? $totalInvoiceSum / $totalInvoice : 0;
        $overdue = $customer->customerOverdue($customer['id']);
    @endphp

    <!-- Customer Info Section - QuickBooks Style -->
    {{-- <div class="qb-customer-header mb-4 mt-4">
        <div class="row g-3 qb-info-card">
            <!-- Customer Name & Icon -->
            <div class="col-lg-3" style="border-right: 2px solid #ccc;">
                <div class="">
                    <div class="d-flex flex-column align-items-center mb-3">
                        <div class="avatar-wrapper me-3">
                            <div class="avatar avatar-lg bg-primary rounded-circle d-flex align-items-center justify-content-center"
                                style="width: 70px; height: 70px; flex-shrink: 0;">
                                <span
                                    class="text-white fw-bold fs-4">{{ strtoupper(substr($customer['name'], 0, 1)) }}</span>
                            </div>
                        </div>
                        <div class="flex-grow-1 text-center">
                            <h4 class="mb-1 fw-bold">
                                {{ $customer['name'] ?: __('(No name)') }}
                                <!-- edit icon next to name (always editable) -->
                                <a href="#" id="editNameField" class="text-primary small ms-2"
                                    title="{{ __('Edit name') }}">
                                    <i class="ti ti-pencil"></i>
                                </a>
                            </h4>

                            <p class="text-muted small mb-0">{{ __('Customer') }}</p>

                            <!-- Company -->
                            <a href="#" class="text-primary small mt-2 d-inline-block" id="openCompanyField">
                                {{ $customer['company'] ? $customer['company'] : __('Add company name') }}
                            </a>
                        </div>
                    </div>
                    <div class="contact-icons d-flex justify-content-center align-items-center mt-3 pt-3 ">
                        <a href="mailto:{{ $customer['email'] }}" class="btn btn-sm btn-light me-2"
                            data-bs-toggle="tooltip" title="{{ __('Email') }}">
                            <i class="ti ti-mail"></i>
                        </a>
                        <a href="tel:{{ $customer['contact'] }}" class="btn btn-sm btn-light me-2" data-bs-toggle="tooltip"
                            title="{{ __('Call') }}">
                            <i class="ti ti-phone"></i>
                        </a>
                        <a href="#" class="btn btn-sm btn-light" data-bs-toggle="tooltip"
                            title="{{ __('Message') }}">
                            <i class="ti ti-message"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Email & Billing Address -->
            <div class="col-lg-4 ps-4">
                <div class="">
                    <p class="text-muted small fw-semibold mb-2">{{ __('Email') }}</p>
                    <p class="mb-3">{{ $customer['email'] }}</p>

                    <p class="text-muted small fw-semibold mb-2">{{ __('Billing address') }}</p>
                    <p class="small mb-0">
                        {{ $customer['billing_address'] }}<br>
                        {{ $customer['billing_city'] }}, {{ $customer['billing_state'] }} {{ $customer['billing_zip'] }}
                    </p>

                    <p class="text-muted small fw-semibold mb-2 mt-3">{{ __('Notes') }}</p>
                    <a href="#" class="text-primary small" id="openNotesSection" data-bs-toggle="offcanvas"
                        data-bs-target="#customerEditSidebar">
                        {{ __('Add notes') }}
                    </a>
                </div>
            </div>

            <!-- Phone & Shipping Address -->
            <div class="col-lg-3">
                <div class="">
                    <p class="text-muted small fw-semibold mb-2">{{ __('Phone') }}</p>
                    <p class="mb-3">
                        @if (!empty($customer['contact']))
                            <a href="tel:{{ $customer['contact'] }}" class="text-primary">{{ $customer['contact'] }}</a>
                        @else
                            <a href="#" class="text-primary small">{{ __('Add phone number') }}</a>
                        @endif
                    </p>

                    <p class="text-muted small fw-semibold mb-2">{{ __('Shipping address (same as billing address)') }}</p>
                    <p class="small mb-0">
                        {{ $customer['shipping_address'] }}<br>
                        {{ $customer['shipping_city'] }}, {{ $customer['shipping_state'] }}
                        {{ $customer['shipping_zip'] }}
                    </p>

                    <p class="text-muted small fw-semibold mb-2 mt-3">{{ __('Custom Fields') }}</p>
                    <a href="#" class="text-primary small" id="openCustomFields">
                        <i class="ti ti-pencil"></i>
                    </a>

                </div>
            </div>

            <!-- Financial Summary -->
            <div class="col-lg-2 qb-financial-summary">
                <div class="">
                    <div class="d-flex align-items-center mb-3">
                        <i class="ti ti-chart-line fs-5 text-primary me-2"></i>
                        <h6 class="mb-0">{{ __('Financial summary') }}</h6>
                    </div>

                    <div class="financial-item mb-3 pb-3 border-bottom">
                        <p class="text-muted small mb-1">
                            <i class="ti ti-circle text-warning me-1"
                                style="font-size: 8px; background-color: currentColor; border-radius:50px; fill: currentColor;"></i>
                            {{ __('Open balance') }}
                        </p>
                        <h5 class="mb-0 text-primary fw-bold">{{ \Auth::user()->priceFormat($customer['balance']) }}</h5>
                    </div>

                    <div class="financial-item mb-3 pb-3 border-bottom">
                        <p class="text-muted small mb-1"><i class="ti ti-circle text-danger me-1"
                                style="font-size: 8px; background-color: currentColor; border-radius:50px; fill: currentColor;"></i>
                            {{ __('Overdue payment') }}</p>
                        <h5 class="mb-0 text-danger fw-bold">{{ \Auth::user()->priceFormat($overdue) }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}
    <div class="qb-customer-header mb-4 mt-4">
        <div class="row g-3 qb-info-card">

            <!-- Customer Name & Icon -->
            <div class="col-lg-3" style="border-right: 2px solid #ccc;">
                <div class="">
                    <div class="d-flex flex-column align-items-center mb-3">
                        <div class="avatar-wrapper me-3">
                            <div class="avatar avatar-lg bg-primary rounded-circle d-flex align-items-center justify-content-center"
                                style="width: 70px; height: 70px; flex-shrink: 0;">
                                <span
                                    class="text-white fw-bold fs-4">{{ strtoupper(substr($customer['name'], 0, 1)) }}</span>
                            </div>
                        </div>
                        <div class="flex-grow-1 text-center">
                            <h4 class="mb-1 fw-bold">
                                {{ $customer['name'] ?: __('(No name)') }}
                                <a href="#" data-size="lg" data-url="{{ route('customer.edit', $customer->id) }}" 
                                    data-ajax-popup="true" data-title="{{ __('Edit Customer') }}" 
                                    class="text-primary small ms-2" title="{{ __('Edit name') }}">
                                    <i class="ti ti-pencil"></i>
                                </a>
                            </h4>

                            <p class="text-muted small mb-0">{{ __('Customer') }}</p>

                            <a href="#" class="text-primary small mt-2 d-inline-block" id="openCompanyField">
                                {{ $customer['company'] ?: __('Add company name') }}
                            </a>
                        </div>
                    </div>

                    <div class="contact-icons d-flex justify-content-center align-items-center mt-3 pt-3">
                        <a href="mailto:{{ $customer['email'] }}" class="btn btn-sm btn-light me-2"
                            data-bs-toggle="tooltip" title="{{ __('Email') }}">
                            <i class="ti ti-mail"></i>
                        </a>
                        <a href="tel:{{ $customer['contact'] }}" class="btn btn-sm btn-light me-2" data-bs-toggle="tooltip"
                            title="{{ __('Call') }}">
                            <i class="ti ti-phone"></i>
                        </a>
                        <a href="#" class="btn btn-sm btn-light" data-bs-toggle="tooltip"
                            title="{{ __('Message') }}">
                            <i class="ti ti-message"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Email & Billing Address -->
            <div class="col-lg-4 ps-4">
                <div class="">
                    <p class="text-muted small fw-semibold mb-2">{{ __('Email') }}</p>
                    <p class="mb-3">
                        {{ $customer['email'] }}
                        <a href="#" data-size="lg" data-url="{{ route('customer.edit', $customer->id) }}" 
                            data-ajax-popup="true" data-title="{{ __('Edit Customer') }}" 
                            class="text-primary small ms-2" title="{{ __('Edit email') }}">
                            <i class="ti ti-pencil"></i>
                        </a>
                    </p>

                    <p class="text-muted small fw-semibold mb-2">{{ __('Billing address') }}</p>
                    <p class="small mb-0">
                        {{ $customer['billing_address'] }}<br>
                        {{ $customer['billing_city'] }}, {{ $customer['billing_state'] }} {{ $customer['billing_zip'] }}
                        <a href="#" data-size="lg" data-url="{{ route('customer.edit', $customer->id) }}" 
                            data-ajax-popup="true" data-title="{{ __('Edit Customer') }}" 
                            class="text-primary small ms-2" title="{{ __('Edit billing address') }}">
                            <i class="ti ti-pencil"></i>
                        </a>
                    </p>

                    <p class="text-muted small fw-semibold mb-2 mt-3">{{ __('Notes') }}</p>
                    <a href="#" data-size="lg" data-url="{{ route('customer.edit', $customer->id) }}" 
                        data-ajax-popup="true" data-title="{{ __('Edit Customer') }}" class="text-primary small">
                        {{ __('Add notes') }}
                    </a>
                </div>
            </div>

            <!-- Phone & Shipping Address -->
            <div class="col-lg-3">
                <div class="">
                    <p class="text-muted small fw-semibold mb-2">{{ __('Phone') }}</p>
                    <p class="mb-3">
                        {{ $customer['contact'] }}
                        <a href="#" data-size="lg" data-url="{{ route('customer.edit', $customer->id) }}" 
                            data-ajax-popup="true" data-title="{{ __('Edit Customer') }}" 
                            class="text-primary small ms-2" title="{{ __('Edit phone') }}">
                            <i class="ti ti-pencil"></i>
                        </a>
                    </p>

                    <p class="text-muted small fw-semibold mb-2">{{ __('Shipping address (same as billing address)') }}
                    </p>
                    <p class="small mb-0">
                        {{ $customer['shipping_address'] }}<br>
                        {{ $customer['shipping_city'] }}, {{ $customer['shipping_state'] }}
                        {{ $customer['shipping_zip'] }}
                        <a href="#" data-size="lg" data-url="{{ route('customer.edit', $customer->id) }}" 
                            data-ajax-popup="true" data-title="{{ __('Edit Customer') }}" 
                            class="text-primary small ms-2" title="{{ __('Edit shipping address') }}">
                            <i class="ti ti-pencil"></i>
                        </a>
                    </p>

                    <p class="text-muted small fw-semibold mb-2 mt-3">{{ __('Custom Fields') }}</p>
                    <a href="#" data-size="lg" data-url="{{ route('customer.edit', $customer->id) }}" 
                        data-ajax-popup="true" data-title="{{ __('Edit Customer') }}" class="text-primary small">
                        <i class="ti ti-pencil"></i>
                    </a>
                </div>
            </div>

            <!-- Financial Summary -->
            <div class="col-lg-2 qb-financial-summary">
                <div class="">
                    <div class="d-flex align-items-center mb-3">
                        <i class="ti ti-chart-line fs-5 text-primary me-2"></i>
                        <h6 class="mb-0">{{ __('Financial summary') }}</h6>
                    </div>

                    <div class="financial-item mb-3 pb-3 border-bottom">
                        <p class="text-muted small mb-1">
                            <i class="ti ti-circle text-warning me-1"
                                style="font-size: 8px; background-color: currentColor; border-radius:50px; fill: currentColor;"></i>
                            {{ __('Open balance') }}
                        </p>
                        <h5 class="mb-0 text-primary fw-bold">{{ \Auth::user()->priceFormat($customer['balance']) }}</h5>
                    </div>

                    <div class="financial-item mb-3 pb-3 border-bottom">
                        <p class="text-muted small mb-1"><i class="ti ti-circle text-danger me-1"
                                style="font-size: 8px; background-color: currentColor; border-radius:50px; fill: currentColor;"></i>
                            {{ __('Overdue payment') }}</p>
                        <h5 class="mb-0 text-danger fw-bold">{{ \Auth::user()->priceFormat($overdue) }}</h5>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Tabs Section - Full Width -->
    <div class="card border-0 shadow-sm">
        <!-- Tab Navigation -->
        <div class="card-header bg-white border-bottom-2 px-4 py-0">
            <ul class="nav nav-tabs qb-nav-tabs" id="customerTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="transactions-tab" data-bs-toggle="tab"
                        data-bs-target="#transactions" type="button" role="tab" aria-controls="transactions"
                        aria-selected="true">
                        {{ __('Transaction List') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="statements-tab" data-bs-toggle="tab" data-bs-target="#statements"
                        type="button" role="tab" aria-controls="statements" aria-selected="false">
                        {{ __('Statements') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="details-tab" data-bs-toggle="tab" data-bs-target="#details"
                        type="button" role="tab" aria-controls="details" aria-selected="false">
                        {{ __('Customer Details') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="notes-tab" data-bs-toggle="tab" data-bs-target="#notes" type="button"
                        role="tab" aria-controls="notes" aria-selected="false">
                        {{ __('Notes') }}
                    </button>
                </li>
            </ul>
        </div>

        <!-- Tab Content -->
        <div class="tab-content" id="customerTabContent">
            <!-- Transaction List Tab -->
            <div class="tab-pane fade show active" id="transactions" role="tabpanel" aria-labelledby="transactions-tab">
                <div class="card-body">
                    <!-- Filters Section -->
                    <div class="qb-filters-section mb-4">
                        <div class="row align-items-center g-3">
                            <div class="col-auto">
                                <div class="d-flex gap-2 align-items-center">
                                    <label class="text-muted small fw-semibold">{{ __('Type') }}</label>
                                    <select class="form-select form-select-sm qb-filter-select">
                                        <option value="">{{ __('All plus deposits') }}</option>
                                        <option value="invoice">{{ __('Invoice') }}</option>
                                        <option value="proposal">{{ __('Proposal') }}</option>
                                        <option value="deposit">{{ __('Deposit') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-auto">
                                <div class="d-flex gap-2 align-items-center">
                                    <label class="text-muted small fw-semibold">{{ __('Status') }}</label>
                                    <select class="form-select form-select-sm qb-filter-select">
                                        <option value="">{{ __('All') }}</option>
                                        <option value="paid">{{ __('Paid') }}</option>
                                        <option value="pending">{{ __('Pending') }}</option>
                                        <option value="overdue">{{ __('Overdue') }}</option>
                                        <option value="draft">{{ __('Draft') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-auto">
                                <div class="d-flex gap-2 align-items-center">
                                    <label class="text-muted small fw-semibold">{{ __('Date') }}</label>
                                    <select class="form-select form-select-sm qb-filter-select">
                                        <option value="">{{ __('All') }}</option>
                                        <option value="today">{{ __('Today') }}</option>
                                        <option value="week">{{ __('This Week') }}</option>
                                        <option value="month">{{ __('This Month') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-auto ms-auto">
                                <div class="d-flex gap-2">
                                    <a href="#"
                                        class="text-primary small text-decoration-none">{{ __('View Recurring Templates') }}</a>
                                    <a href="#" class="text-primary small text-decoration-none ms-3"><i
                                            class="ti ti-help me-1"></i>{{ __('Feedback') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction Table -->
                    @php
                        $items = [];

                        /* ----------------------------------------------------------
        INVOICES
    ---------------------------------------------------------- */
                        foreach ($customer->customerInvoice($customer->id) as $invoice) {
                            $items[] = [
                                'created_at' => $invoice->created_at,
                                'date' => $invoice->issue_date,
                                'type' => 'Invoice',
                                'no' => Auth::user()->invoiceNumberFormat($invoice->invoice_id),
                                'id' => $invoice->id,
                                'amount' => $invoice->getDue(),
                                'memo' => '-',
                                'status' => \App\Models\Invoice::$statues[$invoice->status],
                                'status_class' => match ($invoice->status) {
                                    0 => 'bg-primary',
                                    1 => 'bg-warning text-dark',
                                    2 => 'bg-danger',
                                    3 => 'bg-info',
                                    4 => 'bg-success',
                                },
                                'link' => route('invoice.edit', Crypt::encrypt($invoice->id)),
                                'action_type' => 'invoice',
                                'show_payment' => $invoice->status != 4 && $invoice->getDue() > 0,
                                'credit_amount' => null,
                            ];
                        }

                        /* ----------------------------------------------------------
        PROPOSALS
    ---------------------------------------------------------- */
                        foreach ($customer->customerProposal($customer->id) as $proposal) {
                            $items[] = [
                                'created_at' => $proposal->created_at,
                                'date' => $proposal->issue_date,
                                'type' => 'Estimate',
                                'no' => Auth::user()->proposalNumberFormat($proposal->proposal_id),
                                'id' => $proposal->id,
                                'amount' => $proposal->getTotal(),
                                'memo' => '-',
                                'status' => \App\Models\Proposal::$statues[$proposal->status],
                                'status_class' => match ($proposal->status) {
                                    0 => 'bg-primary',
                                    1 => 'bg-warning text-dark',
                                    2 => 'bg-danger',
                                    3 => 'bg-info',
                                    4 => 'bg-success',
                                },
                                'link' => route('proposal.edit', Crypt::encrypt($proposal->id)),
                                'action_type' => 'proposal',
                                'credit_amount' => null,
                            ];
                        }

                        /* ----------------------------------------------------------
        DEPOSITS
    ---------------------------------------------------------- */
                        foreach ($customer->customerDeposits($customer->id) as $deposit) {
                            $items[] = [
                                'created_at' => $deposit->created_at,
                                'date' => $deposit->txn_date,
                                'type' => 'Deposit',
                                'no' => $deposit->doc_number,
                                'id' => null,
                                'amount' => $deposit->total_amt,
                                'memo' => $deposit->private_note ?? '-',
                                'status' => 'Completed',
                                'status_class' => 'bg-success',
                                'link' => '#',
                                'action_type' => 'deposit',
                                'credit_amount' => null,
                            ];
                        }

                        /* ----------------------------------------------------------
        PAYMENTS
    ---------------------------------------------------------- */
                        $user = Auth::user();
                        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();

                        $paymentGroups = \App\Models\Transaction::where('user_id', $customer->id)
                            ->where('user_type', 'Customer')
                            ->whereNotNull('payment_no')
                            ->where('owned_by', $ownerId)
                            ->orderBy('created_at', 'desc')
                            ->get()
                            ->groupBy('payment_no');

                        foreach ($paymentGroups as $paymentNo => $transactions) {
                            $first = $transactions->first();

                            $invoicePayment = $transactions->where('type', 'Partial')->sum('amount');
                            $creditAmount = $transactions
                                ->where('type', 'credit')
                                ->where('category', 'Customer Credit')
                                ->sum('amount');

                            $status = $invoicePayment > 0 ? 'Payment' : ($creditAmount > 0 ? 'Credit' : 'Payment');
                            $statusClass =
                                $invoicePayment > 0 ? 'bg-success' : ($creditAmount > 0 ? 'bg-info' : 'bg-primary');

                            $items[] = [
                                'created_at' => $first->created_at,
                                'date' => $first->date,
                                'type' => 'Payment',
                                'no' => Auth::user()->paymentNumberFormat($paymentNo),
                                'id' => null,
                                'amount' => $transactions->sum('amount'),
                                'memo' => $first->description ?? '-',
                                'status' => $status,
                                'status_class' => $statusClass,
                                'link' => '#',
                                'action_type' => 'payment',
                                'credit_amount' => $creditAmount,
                            ];
                        }

                        /* ----------------------------------------------------------
        FINAL SORTING BY created_at DESC
    ---------------------------------------------------------- */
                        $items = collect($items)->sortByDesc('created_at')->values();
                    @endphp


                    <div class="table-responsive">
                        <table class="table table-hover qb-transactions-table">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%"><input class="form-check-input" type="checkbox"></th>
                                    <th>{{ __('DATE') }} <i class="ti ti-arrow-up-down"></i></th>
                                    <th>{{ __('TYPE') }}</th>
                                    <th>{{ __('NO.') }}</th>
                                    <th>{{ __('CUSTOMER') }}</th>
                                    <th>{{ __('MEMO') }}</th>
                                    <th>{{ __('AMOUNT') }}</th>
                                    <th>{{ __('STATUS') }}</th>
                                    <th>{{ __('ACTION') }}</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($items as $item)
                                    <tr>
                                        <td><input class="form-check-input" type="checkbox"></td>

                                        <td class="fw-semibold">{{ Auth::user()->dateFormat($item['date']) }}</td>

                                        <td><span class="badge bg-light text-dark fw-normal">{{ $item['type'] }}</span>
                                        </td>

                                        <td class="fw-semibold">{{ $item['no'] }}</td>

                                        <td>{{ $customer['name'] }}</td>

                                        <td class="text-muted small">{{ $item['memo'] }}</td>

                                        <td class="fw-bold">{{ Auth::user()->priceFormat($item['amount']) }}</td>

                                        <td>
                                            <span class="badge {{ $item['status_class'] }}">{{ $item['status'] }}</span>

                                            {{-- SEPARATE CREDIT BADGE --}}
                                            @if ($item['credit_amount'])
                                                <span class="text-dark ms-1" style="font-size:9px;">
                                                    <br> + Credit {{ Auth::user()->priceFormat($item['credit_amount']) }}
                                                </span>
                                            @endif
                                        </td>

                                        <td>
                                            <div class="d-flex gap-2 justify-content-end">
                                                <a href="{{ $item['link'] }}"
                                                    class="text-primary small text-decoration-none">
                                                    {{ in_array($item['action_type'], ['invoice', 'proposal']) ? 'View/Edit' : 'View' }}
                                                </a>

                                                @if ($item['action_type'] == 'invoice' && $item['show_payment'])
                                                    <span class="text-muted">|</span>
                                                    <a href="{{ route('receive-payment.create', $customer->id) }}?invoice_id={{ $item['id'] }}"
                                                        class="text-primary small text-decoration-none">
                                                        Receive payment
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>


                </div>
            </div>

            <!-- Statements Tab -->
            <div class="tab-pane fade" id="statements" role="tabpanel" aria-labelledby="statements-tab">
                <div class="card-body">
                    <div class="alert alert-info" role="alert">
                        <i class="ti ti-info-circle me-2"></i>{{ __('Customer statements will appear here') }}
                    </div>
                </div>
            </div>

            <!-- Customer Details Tab -->
            <div class="tab-pane fade" id="details" role="tabpanel" aria-labelledby="details-tab">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <h6 class="mb-3 fw-bold">{{ __('Billing Information') }}</h6>
                            <p class="mb-2"><strong>{{ __('Name') }}</strong></p>
                            <p class="text-muted mb-3">{{ $customer['billing_name'] }}</p>
                            <p class="mb-2"><strong>{{ __('Address') }}</strong></p>
                            <p class="text-muted mb-3">
                                {{ $customer['billing_address'] }}<br>
                                {{ $customer['billing_city'] }}, {{ $customer['billing_state'] }}
                                {{ $customer['billing_zip'] }}<br>
                                {{ $customer['billing_country'] }}
                            </p>
                            <p class="mb-2"><strong>{{ __('Phone') }}</strong></p>
                            <p class="text-muted">{{ $customer['billing_phone'] }}</p>
                        </div>
                        <div class="col-md-6 mb-4">
                            <h6 class="mb-3 fw-bold">{{ __('Shipping Information') }}</h6>
                            <p class="mb-2"><strong>{{ __('Name') }}</strong></p>
                            <p class="text-muted mb-3">{{ $customer['shipping_name'] }}</p>
                            <p class="mb-2"><strong>{{ __('Address') }}</strong></p>
                            <p class="text-muted mb-3">
                                {{ $customer['shipping_address'] }}<br>
                                {{ $customer['shipping_city'] }}, {{ $customer['shipping_state'] }}
                                {{ $customer['shipping_zip'] }}<br>
                                {{ $customer['shipping_country'] }}
                            </p>
                            <p class="mb-2"><strong>{{ __('Phone') }}</strong></p>
                            <p class="text-muted">{{ $customer['shipping_phone'] }}</p>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row mt-4">
                        <div class="col-md-3">
                            <p class="text-muted small fw-semibold">{{ __('CUSTOMER ID') }}</p>
                            <h6>{{ Auth::user()->customerNumberFormat($customer['customer_id']) }}</h6>
                        </div>
                        <div class="col-md-3">
                            <p class="text-muted small fw-semibold">{{ __('DATE OF CREATION') }}</p>
                            <h6>{{ Auth::user()->dateFormat($customer['created_at']) }}</h6>
                        </div>
                        <div class="col-md-3">
                            <p class="text-muted small fw-semibold">{{ __('TOTAL INVOICES') }}</p>
                            <h6>{{ $totalInvoice }}</h6>
                        </div>
                        <div class="col-md-3">
                            <p class="text-muted small fw-semibold">{{ __('AVERAGE SALES') }}</p>
                            <h6>{{ Auth::user()->priceFormat($averageSale) }}</h6>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes Tab -->
            <div class="tab-pane fade" id="notes" role="tabpanel" aria-labelledby="notes-tab">
                <div class="card-body">
                    <div class="alert alert-info" role="alert">
                        <i
                            class="ti ti-info-circle me-2"></i>{{ __('Notes section for additional customer information') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== Edit Sidebar (Offcanvas) ========== -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="customerEditSidebar"
        aria-labelledby="customerEditSidebarLabel">
        <div class="offcanvas-header">
            <h5 id="customerEditSidebarLabel" class="offcanvas-title">{{ __('Edit Customer') }}</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        <div class="offcanvas-body p-0">
            <form action="{{ route('customer.update', $customer['id']) }}" method="POST" enctype="multipart/form-data"
                id="customerEditForm">
                @csrf
                @method('PUT')

                <div class="p-3" style="max-height: calc(100vh - 120px); overflow:auto;">
                    <div class="accordion" id="customerEditAccordion">

                        <!-- 1) Name & Contact -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingNameContact">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseNameContact" aria-expanded="true"
                                    aria-controls="collapseNameContact">
                                    {{ __('Name & Contact') }}
                                </button>
                            </h2>
                            <div id="collapseNameContact" class="accordion-collapse collapse show"
                                aria-labelledby="headingNameContact" data-bs-parent="#customerEditAccordion">
                                <div class="accordion-body">
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Full Name') }}</label>
                                        <input type="text" name="name" class="form-control"
                                            value="{{ old('name', $customer['name']) }}" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Company') }}</label>
                                        <input type="text" name="company" class="form-control"
                                            value="{{ old('company', $customer['company'] ?? '') }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Email') }}</label>
                                        <input type="email" name="email" class="form-control"
                                            value="{{ old('email', $customer['email']) }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Phone') }}</label>
                                        <input type="text" name="contact" class="form-control"
                                            value="{{ old('contact', $customer['contact']) }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2) Addresses -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingAddresses">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseAddresses" aria-expanded="false"
                                    aria-controls="collapseAddresses">
                                    {{ __('Addresses') }}
                                </button>
                            </h2>
                            <div id="collapseAddresses" class="accordion-collapse collapse"
                                aria-labelledby="headingAddresses" data-bs-parent="#customerEditAccordion">
                                <div class="accordion-body">
                                    <h6 class="mb-2 fw-semibold">{{ __('Billing') }}</h6>
                                    <div class="mb-2">
                                        <input type="text" name="billing_name" class="form-control mb-2"
                                            placeholder="{{ __('Name') }}"
                                            value="{{ old('billing_name', $customer['billing_name']) }}">
                                        <input type="text" name="billing_address" class="form-control mb-2"
                                            placeholder="{{ __('Address') }}"
                                            value="{{ old('billing_address', $customer['billing_address']) }}">
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <input type="text" name="billing_city" class="form-control mb-2"
                                                    placeholder="{{ __('City') }}"
                                                    value="{{ old('billing_city', $customer['billing_city']) }}">
                                            </div>
                                            <div class="col-6">
                                                <input type="text" name="billing_state" class="form-control mb-2"
                                                    placeholder="{{ __('State') }}"
                                                    value="{{ old('billing_state', $customer['billing_state']) }}">
                                            </div>
                                            <div class="col-6">
                                                <input type="text" name="billing_zip" class="form-control mb-2"
                                                    placeholder="{{ __('Zip') }}"
                                                    value="{{ old('billing_zip', $customer['billing_zip']) }}">
                                            </div>
                                            <div class="col-6">
                                                <input type="text" name="billing_country" class="form-control mb-2"
                                                    placeholder="{{ __('Country') }}"
                                                    value="{{ old('billing_country', $customer['billing_country']) }}">
                                            </div>
                                        </div>
                                        <input type="text" name="billing_phone" class="form-control mb-2"
                                            placeholder="{{ __('Phone') }}"
                                            value="{{ old('billing_phone', $customer['billing_phone']) }}">
                                    </div>

                                    <hr>

                                    <h6 class="mb-2 fw-semibold">{{ __('Shipping') }}</h6>
                                    <div class="mb-2">
                                        <input type="text" name="shipping_name" class="form-control mb-2"
                                            placeholder="{{ __('Name') }}"
                                            value="{{ old('shipping_name', $customer['shipping_name']) }}">
                                        <input type="text" name="shipping_address" class="form-control mb-2"
                                            placeholder="{{ __('Address') }}"
                                            value="{{ old('shipping_address', $customer['shipping_address']) }}">
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <input type="text" name="shipping_city" class="form-control mb-2"
                                                    placeholder="{{ __('City') }}"
                                                    value="{{ old('shipping_city', $customer['shipping_city']) }}">
                                            </div>
                                            <div class="col-6">
                                                <input type="text" name="shipping_state" class="form-control mb-2"
                                                    placeholder="{{ __('State') }}"
                                                    value="{{ old('shipping_state', $customer['shipping_state']) }}">
                                            </div>
                                            <div class="col-6">
                                                <input type="text" name="shipping_zip" class="form-control mb-2"
                                                    placeholder="{{ __('Zip') }}"
                                                    value="{{ old('shipping_zip', $customer['shipping_zip']) }}">
                                            </div>
                                            <div class="col-6">
                                                <input type="text" name="shipping_country" class="form-control mb-2"
                                                    placeholder="{{ __('Country') }}"
                                                    value="{{ old('shipping_country', $customer['shipping_country']) }}">
                                            </div>
                                        </div>
                                        <input type="text" name="shipping_phone" class="form-control mb-2"
                                            placeholder="{{ __('Phone') }}"
                                            value="{{ old('shipping_phone', $customer['shipping_phone']) }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3) Notes & Attachments -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingNotesAttachments">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseNotesAttachments" aria-expanded="false"
                                    aria-controls="collapseNotesAttachments">
                                    {{ __('Notes & Attachments') }}
                                </button>
                            </h2>
                            <div id="collapseNotesAttachments" class="accordion-collapse collapse"
                                aria-labelledby="headingNotesAttachments" data-bs-parent="#customerEditAccordion">
                                <div class="accordion-body">
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Private Notes') }}</label>
                                        <textarea class="form-control" name="notes" rows="4">{{ old('notes', $customer['notes'] ?? '') }}</textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Attachments') }}</label>
                                        <input type="file" name="attachments[]" class="form-control" multiple>
                                        @if (isset($customer->attachments) && count($customer->attachments))
                                            <div class="mt-2">
                                                <label
                                                    class="form-label small text-muted">{{ __('Existing Attachments') }}</label>
                                                <ul class="list-unstyled small">
                                                    @foreach ($customer->attachments as $att)
                                                        <li>
                                                            <a href="{{ asset('storage/' . $att->path) }}"
                                                                target="_blank">{{ $att->filename }}</a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4) Payments -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingPayments">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapsePayments" aria-expanded="false"
                                    aria-controls="collapsePayments">
                                    {{ __('Payments') }}
                                </button>
                            </h2>
                            <div id="collapsePayments" class="accordion-collapse collapse"
                                aria-labelledby="headingPayments" data-bs-parent="#customerEditAccordion">
                                <div class="accordion-body">
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Default Payment Method') }}</label>
                                        <input type="text" name="payment_method" class="form-control"
                                            value="{{ old('payment_method', $customer['payment_method'] ?? '') }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Payment Terms (days)') }}</label>
                                        <input type="number" name="payment_terms" class="form-control"
                                            value="{{ old('payment_terms', $customer['payment_terms'] ?? '') }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Credit Limit') }}</label>
                                        <input type="text" name="credit_limit" class="form-control"
                                            value="{{ old('credit_limit', $customer['credit_limit'] ?? '') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 5) Additional Info -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingAdditional">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseAdditional" aria-expanded="false"
                                    aria-controls="collapseAdditional">
                                    {{ __('Additional info') }}
                                </button>
                            </h2>
                            <div id="collapseAdditional" class="accordion-collapse collapse"
                                aria-labelledby="headingAdditional" data-bs-parent="#customerEditAccordion">
                                <div class="accordion-body">
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Website') }}</label>
                                        <input type="url" name="website" class="form-control"
                                            value="{{ old('website', $customer['website'] ?? '') }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">{{ __('VAT / Tax ID') }}</label>
                                        <input type="text" name="tax_id" class="form-control"
                                            value="{{ old('tax_id', $customer['tax_id'] ?? '') }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Industry') }}</label>
                                        <input type="text" name="industry" class="form-control"
                                            value="{{ old('industry', $customer['industry'] ?? '') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 6) Custom Fields -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingCustom">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseCustom" aria-expanded="false"
                                    aria-controls="collapseCustom">
                                    {{ __('Custom fields') }}
                                </button>
                            </h2>
                            <div id="collapseCustom" class="accordion-collapse collapse" aria-labelledby="headingCustom"
                                data-bs-parent="#customerEditAccordion">
                                <div class="accordion-body">
                                    <div id="customFieldsWrapper">
                                        @php
                                            $customs = old('custom_fields', $customer->custom_fields ?? []);
                                        @endphp
                                        @if (is_array($customs) && count($customs))
                                            @foreach ($customs as $k => $cf)
                                                <div class="input-group mb-2 custom-field-row">
                                                    <input type="text"
                                                        name="custom_fields[{{ $k }}][label]"
                                                        class="form-control" placeholder="Label"
                                                        value="{{ $cf['label'] ?? '' }}">
                                                    <input type="text"
                                                        name="custom_fields[{{ $k }}][value]"
                                                        class="form-control" placeholder="Value"
                                                        value="{{ $cf['value'] ?? '' }}">
                                                    <button class="btn btn-outline-danger remove-custom-field"
                                                        type="button">&times;</button>
                                                </div>
                                            @endforeach
                                        @else
                                            <!-- empty initial -->
                                        @endif
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="button" id="addCustomFieldBtn"
                                            class="btn btn-sm btn-outline-primary">
                                            {{ __('Add custom field') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div> <!-- end accordion -->

                    <div class="mt-4 d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Save changes') }}</button>
                    </div>
                </div> <!-- p-3 -->
            </form>
        </div>
    </div>
    <!-- ========== End Edit Sidebar ========== -->

    <style>
        /* QuickBooks Style Tabs */
        .qb-nav-tabs {
            border-bottom: 2px solid #e5e5e5;
            gap: 0;
        }

        .qb-nav-tabs .nav-link {
            color: #5a5a5a;
            border: none;
            border-bottom: 3px solid transparent;
            font-weight: 500;
            font-size: 0.95rem;
            padding: 1rem 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            bottom: -2px;
        }

        .qb-nav-tabs .nav-link:hover {
            color: #2d2d2d;
            border-bottom-color: #ddd;
        }

        .qb-nav-tabs .nav-link.active {
            color: #0d6efd;
            border-bottom-color: #0d6efd;
            background-color: transparent;
        }

        /* Customer Info Cards */
        .qb-info-card {
            background: #fff;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            border: 1px solid #e5e5e5;
            height: 100%;
        }

        .qb-financial-summary {
            background: linear-gradient(135deg, #f0f4ff 0%, #e8f0ff 100%);
            border: 1px solid #d9e5ff;
            padding: 10px;
        }

        /* Filter Section */
        .qb-filters-section {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-left: -1.5rem;
            margin-right: -1.5rem;
            margin-top: -1.5rem;
            margin-bottom: 1.5rem;
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }

        .qb-filter-select {
            border: 1px solid #d5d5d5;
            background-color: #fff;
            padding: 0.5rem 2rem 0.5rem 0.75rem;
            font-size: 0.9rem;
        }

        .qb-filter-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        /* Transaction Table */
        .qb-transactions-table {
            font-size: 0.95rem;
        }

        .qb-transactions-table thead th {
            font-weight: 600;
            color: #5a5a5a;
            border-bottom: 1px solid #e5e5e5;
            padding: 1rem 0.75rem;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        .qb-transactions-table tbody td {
            padding: 1rem 0.75rem;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }

        .qb-transactions-table tbody tr:hover {
            background-color: #f9f9f9;
        }

        /* Avatar Styles */
        .avatar-wrapper {
            min-width: 70px;
        }

        /* Offcanvas width / style tweaks (keeps existing layout intact) */
        .offcanvas.offcanvas-end {
            width: 480px;
            max-width: 100%;
        }

        .offcanvas .offcanvas-body {
            padding: 0;
        }

        .accordion-button {
            font-weight: 600;
        }

        .custom-field-row input {
            min-width: 0;
        }

        .remove-custom-field {
            width: 44px;
        }

        /* General Adjustments */
        .border-bottom-2 {
            border-bottom: 2px solid #e5e5e5 !important;
        }
    </style>
@endsection

@push('script-page')
    <script>
        (function() {
            function openSidebarSection(collapseId, focusSelector) {
                const offcanvasEl = document.getElementById('customerEditSidebar');
                const offcanvas = bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl);
                offcanvas.show();

                setTimeout(function() {
                    if (collapseId) {
                        const section = document.getElementById(collapseId);
                        if (section) {
                            const collapseInstance = bootstrap.Collapse.getOrCreateInstance(section, {
                                toggle: false
                            });
                            collapseInstance.show();
                            section.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }
                    }
                    if (focusSelector) {
                        const el = document.getElementById('customerEditSidebar').querySelector(focusSelector);
                        if (el) el.focus();
                    }
                }, 320);
            }

            const shortcuts = [{
                    btn: 'editNameField',
                    collapse: 'collapseNameContact',
                    focus: 'input[name="name"]'
                },
                {
                    btn: 'openCompanyField',
                    collapse: 'collapseNameContact',
                    focus: 'input[name="company"]'
                },
                {
                    btn: 'editPhoneField',
                    collapse: 'collapseNameContact',
                    focus: 'input[name="contact"]'
                },
                {
                    btn: 'editEmailField',
                    collapse: 'collapseNameContact',
                    focus: 'input[name="email"]'
                },
                {
                    btn: 'editBillingAddress',
                    collapse: 'collapseAddresses',
                    focus: 'input[name="billing_address"]'
                },
                {
                    btn: 'editShippingAddress',
                    collapse: 'collapseAddresses',
                    focus: 'input[name="shipping_address"]'
                },
                {
                    btn: 'openNotesSection',
                    collapse: 'collapseNotesAttachments',
                    focus: 'textarea[name="notes"]'
                },
                {
                    btn: 'openCustomFields',
                    collapse: 'collapseCustom',
                    focus: '#customFieldsWrapper input'
                }
            ];

            shortcuts.forEach(function(s) {
                const el = document.getElementById(s.btn);
                if (el) {
                    el.addEventListener('click', function(e) {
                        e.preventDefault();
                        openSidebarSection(s.collapse, s.focus);
                    });
                }
            });
        })();
    </script>

    <script>
        (function() {
            // Add custom field row
            let customIndex = document.querySelectorAll('#customFieldsWrapper .custom-field-row').length || 0;
            document.getElementById('addCustomFieldBtn').addEventListener('click', function() {
                const wrapper = document.getElementById('customFieldsWrapper');
                const idx = customIndex++;
                const row = document.createElement('div');
                row.className = 'input-group mb-2 custom-field-row';
                row.innerHTML = `
                        <input type="text" name="custom_fields[${idx}][label]" class="form-control" placeholder="Label">
                        <input type="text" name="custom_fields[${idx}][value]" class="form-control" placeholder="Value">
                        <button class="btn btn-outline-danger remove-custom-field" type="button">&times;</button>
                    `;
                wrapper.appendChild(row);
            });

            // Delegate remove custom field
            document.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('remove-custom-field')) {
                    const row = e.target.closest('.custom-field-row');
                    if (row) row.remove();
                }
            });

            // Prevent the offcanvas closing on form submit until request completes if you later handle via ajax
            document.getElementById('customerEditForm').addEventListener('submit', function() {
                // Default is full submit — server will redirect/refresh.
                // If you want AJAX handling, replace with AJAX code here.
            });

            // Enable bootstrap tooltips (if disabled on page)
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            tooltipTriggerList.forEach(function(tooltipTriggerEl) {
                new bootstrap.Tooltip(tooltipTriggerEl)
            });
        })();
    </script>
@endpush
