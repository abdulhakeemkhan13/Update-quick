@extends('layouts.admin')
@section('page-title')
    {{ __('Bill Detail') }}
@endsection
@push('script-page')
    <script>
        $(document).on('click', '#shipping', function() {
            var url = $(this).data('url');
            var is_display = $("#shipping").is(":checked");
            $.ajax({
                    url: url,
                    type: 'get',
                    data: 'is_display': is_display,
                },
                success: function(data) {
                    // console.log(data);
                }
            });
        })
    </script>
@endpush
@php
    $settings = Utility::settings();
@endphp
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bill.index') }}">{{ __('Bill') }}</a></li>
    <li class="breadcrumb-item">{{ Auth::user()->billNumberFormat($bill->bill_id) }}</li>
@endsection

@section('content')

    @can('send bill')
        @if ($bill->status != 4)
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row timeline-wrapper">
                                <!-- Create Bill Section -->
                                <div class="col-md-6 col-lg-3 col-xl-3 create_bill">
                                    <div class="timeline-icons"><span class="timeline-dots"></span>
                                        <i class="ti ti-plus text-primary"></i>
                                    </div>
                                    <h6 class="text-primary my-3">{{ __('Create Bill') }}</h6>
                                    <p class="text-muted text-sm mb-3"><i
                                            class="ti ti-clock mr-2"></i>{{ __('Created on ') }}{{ \Auth::user()->dateFormat($bill->bill_date) }}
                                    </p>
                                    @can('edit bill')
                                        <a href="{{ route('bill.edit', \Crypt::encrypt($bill->id)) }}"
                                            class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                                            data-original-title="{{ __('Edit') }}"><i
                                                class="ti ti-pencil mr-2"></i>{{ __('Edit') }}</a>
                                    @endcan
                                </div>

                                <!-- Approval Bill Section -->
                                <div class="col-md-6 col-lg-3 col-xl-3 approval_bill">
                                    <div class="timeline-icons"><span class="timeline-dots"></span>
                                        @if ($bill->status == 6 || in_array($bill->status, [1, 2, 3, 4]))
                                            <i class="ti ti-check text-success"></i>
                                        @elseif($bill->status == 7)
                                            <i class="ti ti-x text-danger"></i>
                                        @elseif($bill->status == 5)
                                            <i class="ti ti-clock text-warning"></i>
                                        @else
                                            <i class="ti ti-file-text text-info"></i>
                                        @endif
                                    </div>

                                    @if ($bill->status == 6)
                                        {{-- Approved --}}
                                        <h6 class="text-success my-3">{{ __('Approved') }}</h6>
                                        <p class="text-muted text-sm mb-3">
                                            <i class="ti ti-check mr-2"></i>{{ __('Approved - Ready to send') }}
                                        </p>
                                    @elseif(in_array($bill->status, [1, 2, 3, 4]))
                                        {{-- Already Approved and Sent/Paid --}}
                                        <h6 class="text-success my-3">{{ __('Approved') }}</h6>
                                        <p class="text-muted text-sm mb-3">
                                            <i class="ti ti-check mr-2"></i>{{ __('Approved') }}
                                            @if ($bill->approved_at)
                                                {{ __('on') }} {{ \Auth::user()->dateFormat($bill->approved_at) }}
                                            @endif
                                        </p>
                                        <span class="badge bg-success">{{ __('Approved & Sent') }}</span>
                                    @elseif($bill->status == 7)
                                        {{-- Rejected --}}
                                        <h6 class="text-danger my-3">{{ __('Rejected') }}</h6>
                                        <p class="text-muted text-sm mb-3">
                                            <i class="ti ti-x mr-2"></i>{{ __('Rejected') }}
                                        </p>
                                        @can('edit bill')
                                            <form method="POST" action="{{ route('bill.send-for-approval', $bill->id) }}"
                                                style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-warning" data-bs-toggle="tooltip"
                                                    data-original-title="{{ __('Request Approval') }}"
                                                    onclick="return confirm('{{ __('Are you sure you want to send this bill for approval?') }}')">
                                                    <i class="ti ti-send mr-2"></i>{{ __('Request Approval') }}
                                                </button>
                                            </form>
                                        @endcan
                                    @elseif($bill->status == 5)
                                        {{-- Pending Approval --}}
                                        <h6 class="text-warning my-3">{{ __('Pending Approval') }}</h6>
                                        <p class="text-muted text-sm mb-3">
                                            <i class="ti ti-clock mr-2"></i>{{ __('Waiting for approval') }}
                                        </p>
                                        @can('approve bill')
                                            <form method="POST" action="{{ route('bill.approve', $bill->id) }}"
                                                style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success mb-2"
                                                    onclick="return confirm('{{ __('Are you sure you want to approve this bill?') }}')"
                                                    data-bs-toggle="tooltip" data-original-title="{{ __('Approve') }}">
                                                    <i class="ti ti-check mr-2"></i>{{ __('Approve') }}
                                                </button>
                                            </form>
                                            <br>
                                            <a href="#" data-url="{{ route('bill.reject', $bill->id) }}"
                                                data-ajax-popup="true" data-title="{{ __('Reject Bill') }}"
                                                class="btn btn-sm btn-danger" data-bs-toggle="tooltip"
                                                data-original-title="{{ __('Reject') }}">
                                                <i class="ti ti-x mr-2"></i>{{ __('Reject') }}
                                            </a>
                                        @else
                                            <small class="text-muted">{{ __('Status: Under review') }}</small>
                                        @endcan
                                    @else
                                        {{-- Draft (0) or any other status --}}
                                        @if ($bill->status == 0)
                                            <h6 class="text-info my-3">{{ __('Draft') }}</h6>
                                            <p class="text-muted text-sm mb-3">
                                                <i class="ti ti-file-text mr-2"></i>{{ __('Bill is in draft') }}
                                            </p>
                                        @else
                                            <h6 class="text-secondary my-3">{{ __('Approval Required') }}</h6>
                                            <p class="text-muted text-sm mb-3">
                                                <small>{{ __('Status: Not submitted for approval') }}</small>
                                            </p>
                                        @endif
                                        @can('edit bill')
                                            <form method="POST" action="{{ route('bill.send-for-approval', $bill->id) }}"
                                                style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-info" data-bs-toggle="tooltip"
                                                    data-original-title="{{ __('Send for Approval') }}"
                                                    onclick="return confirm('{{ __('Are you sure you want to send this bill for approval?') }}')">
                                                    <i class="ti ti-send mr-2"></i>{{ __('Send for Approval') }}
                                                </button>
                                            </form>
                                        @endcan
                                    @endif
                                </div>

                                <!-- Send Bill Section -->
                                <div class="col-md-6 col-lg-3 col-xl-3 send_bill">
                                    <div class="timeline-icons"><span class="timeline-dots"></span>
                                        <i class="ti ti-mail text-warning"></i>
                                    </div>
                                    <h6 class="text-warning my-3">{{ __('Send Bill') }}</h6>
                                    <p class="text-muted text-sm mb-3">
                                        @if (in_array($bill->status, [1, 2, 3, 4]))
                                            <i class="ti ti-check mr-2"></i>{{ __('Sent on') }}
                                            {{ $bill->send_date ? \Auth::user()->dateFormat($bill->send_date) : __('N/A') }}
                                        @elseif($bill->status == 6)
                                            <small>{{ __('Status') }} : {{ __('Ready to Send') }}</small>
                                        @else
                                            <small>{{ __('Status') }} : {{ __('Waiting for Approval') }}</small>
                                        @endif
                                    </p>

                                    {{-- Only show send button if status is Approved (6) and not yet sent --}}
                                    @if ($bill->status == 6)
                                        @can('send bill')
                                            <a href="{{ route('bill.sent', $bill->id) }}" class="btn btn-sm btn-warning"
                                                data-bs-toggle="tooltip" data-original-title="{{ __('Mark Sent') }}">
                                                <i class="ti ti-send mr-2"></i>{{ __('Send') }}
                                            </a>
                                        @endcan
                                    @elseif(in_array($bill->status, [1, 2, 3, 4]))
                                        {{-- Already sent, show status --}}
                                        <span class="badge bg-success">{{ __('Sent') }}</span>
                                    @else
                                        {{-- Not approved yet or rejected/draft/pending --}}
                                        <button class="btn btn-sm btn-secondary" disabled data-bs-toggle="tooltip"
                                            data-original-title="{{ __('Approval required before sending') }}">
                                            <i class="ti ti-send mr-2"></i>{{ __('Send') }}
                                        </button>
                                    @endif
                                </div>

                                <!-- Payment Section -->
                                <div class="col-md-6 col-lg-3 col-xl-3 get_paid_bill">
                                    <div class="timeline-icons"><span class="timeline-dots"></span>
                                        <i class="ti ti-report-money text-info"></i>
                                    </div>
                                    <h6 class="text-info my-3">{{ __('Get Paid') }}</h6>
                                    <p class="text-muted text-sm mb-3">
                                        @if ($bill->status == 4)
                                            {{ __('Status') }} : {{ __('Paid') }}
                                        @elseif($bill->status == 3)
                                            {{ __('Status') }} : {{ __('Partially Paid') }}
                                        @elseif($bill->status == 2)
                                            {{ __('Status') }} : {{ __('Unpaid') }}
                                        @elseif($bill->status == 1)
                                            {{ __('Status') }} : {{ __('Sent - Awaiting payment') }}
                                        @else
                                            {{ __('Status') }} : {{ __('Not Available') }}
                                        @endif
                                    </p>

                                    {{-- Only show payment button if bill is Sent (1), Unpaid (2), or Partially Paid (3) --}}
                                    @if (in_array($bill->status, [1, 2, 3]))
                                        @can('create payment bill')
                                            <a href="#" data-url="{{ route('bill.payment', $bill->id) }}"
                                                data-ajax-popup="true" data-title="{{ __('Add Payment') }}"
                                                class="btn btn-sm btn-info" data-original-title="{{ __('Add Payment') }}">
                                                <i class="ti ti-report-money mr-2"></i>{{ __('Add Payment') }}
                                            </a>
                                        @endcan
                                    @elseif($bill->status == 4)
                                        <span class="badge bg-success">{{ __('Payment Completed') }}</span>
                                    @else
                                        <button class="btn btn-sm btn-secondary" disabled data-bs-toggle="tooltip"
                                            data-original-title="{{ __('Bill must be sent before adding payment') }}">
                                            <i class="ti ti-report-money mr-2"></i>{{ __('Add Payment') }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endcan

    @if (\Auth::user()->type == 'company')
        @if ($bill->status != 0)
            <div class="row justify-content-between align-items-center mb-3">
                <div class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end">
                    @if (!empty($billPayment))
                        <div class="all-button-box mx-2">
                            <a href="#" data-url="{{ route('bill.debit.note', $bill->id) }}"
                                data-ajax-popup="true" data-title="{{ __('Add Debit Note') }}"
                                class="btn btn-sm btn-primary">
                                {{ __('Add Debit Note') }}
                            </a>
                        </div>
                    @endif
                    <div class="all-button-box mx-2">
                        <a href="{{ route('bill.resent', $bill->id) }}" class="btn btn-sm btn-primary">
                            {{ __('Resend Bill') }}
                        </a>
                    </div>
                    <div class="all-button-box">
                        <a href="{{ route('bill.pdf', Crypt::encrypt($bill->id)) }}" target="_blank"
                            class="btn btn-sm btn-primary">
                            {{ __('Download') }}
                        </a>
                    </div>
                </div>
            </div>
        @endif

    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="invoice">
                        <div class="invoice-print">
                            <div class="row invoice-title mt-2">
                                <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12">
                                    <h4>{{ __('Bill') }}</h4>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12 text-end">
                                    <h4 class="invoice-number">{{ Auth::user()->billNumberFormat($bill->bill_id) }}</h4>
                                </div>
                                <div class="col-12">
                                    <hr>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col text-end">
                                    <div class="d-flex align-items-center justify-content-end">
                                        <div class="me-4">
                                            <small>
                                                <strong>{{ __('Issue Date') }} :</strong><br>
                                                {{ \Auth::user()->dateFormat($bill->bill_date) }}<br><br>
                                            </small>
                                        </div>
                                        <div>
                                            <small>
                                                <strong>{{ __('Due Date') }} :</strong><br>
                                                {{ \Auth::user()->dateFormat($bill->due_date) }}<br><br>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col">
                                    <small class="font-style">
                                        <strong>{{ __('Billed To') }} :</strong><br>
                                        @if (!empty($vendor->billing_name))
                                            {{ !empty($vendor->billing_name) ? $vendor->billing_name : '' }}<br>
                                            {{ !empty($vendor->billing_address) ? $vendor->billing_address : '' }}<br>
                                            {{ !empty($vendor->billing_city) ? $vendor->billing_city : '' . ', ' }}<br>
                                            {{ !empty($vendor->billing_state) ? $vendor->billing_state : '', ', ' }},
                                            {{ !empty($vendor->billing_zip) ? $vendor->billing_zip : '' }}<br>
                                            {{ !empty($vendor->billing_country) ? $vendor->billing_country : '' }}<br>
                                            {{ !empty($vendor->billing_phone) ? $vendor->billing_phone : '' }}<br>
                                            @if ($settings['vat_gst_number_switch'] == 'on')
                                                <strong>{{ __('Tax Number ') }} :
                                                </strong>{{ !empty($vendor->tax_number) ? $vendor->tax_number : '' }}
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </small>
                                </div>

                                @if (App\Models\Utility::getValByName('shipping_display') == 'on')
                                    <div class="col">
                                        <small>
                                            <strong>{{ __('Shipped To') }} :</strong><br>
                                            @if (!empty($vendor->shipping_name))
                                                {{ !empty($vendor->shipping_name) ? $vendor->shipping_name : '' }}<br>
                                                {{ !empty($vendor->shipping_address) ? $vendor->shipping_address : '' }}<br>
                                                {{ !empty($vendor->shipping_city) ? $vendor->shipping_city : '' . ', ' }}<br>
                                                {{ !empty($vendor->shipping_state) ? $vendor->shipping_state : '' . ', ' }},
                                                {{ !empty($vendor->shipping_zip) ? $vendor->shipping_zip : '' }}<br>
                                                {{ !empty($vendor->shipping_country) ? $vendor->shipping_country : '' }}<br>
                                                {{ !empty($vendor->shipping_phone) ? $vendor->shipping_phone : '' }}<br>
                                            @else
                                                -
                                            @endif
                                        </small>
                                    </div>
                                @endif

                                <div class="col">
                                    <div class="float-end mt-3">
                                        {!! DNS2D::getBarcodeHTML(
                                            route('bill.link.copy', \Illuminate\Support\Facades\Crypt::encrypt($bill->id)),
                                            'QRCODE',
                                            2,
                                            2,
                                        ) !!}
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col">
                                    <small>
                                        <strong>{{ __('Status') }} :</strong><br>
                                        @if ($bill->status == 0)
                                            <span
                                                class="badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                        @elseif($bill->status == 1)
                                            <span
                                                class="badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                        @elseif($bill->status == 2)
                                            <span
                                                class="badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                        @elseif($bill->status == 3)
                                            <span
                                                class="badge bg-info p-2 px-3 rounded">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                        @elseif($bill->status == 4)
                                            <span
                                                class="badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                        @endif
                                    </small>
                                </div>


                                @if (!empty($customFields) && count($bill->customField) > 0)
                                    @foreach ($customFields as $field)
                                        <div class="col text-md-end">
                                            <small>
                                                <strong>{{ $field->name }} :</strong><br>
                                                {{ !empty($bill->customField) ? $bill->customField[$field->id] : '-' }}
                                                <br><br>
                                            </small>
                                        </div>
                                    @endforeach
                                @endif
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="font-bold mb-2">{{ __('Product Summary') }}</div>
                                    <small class="mb-2">{{ __('All items here cannot be deleted.') }}</small>
                                    <div class="table-responsive mt-3">
                                        <table class="table mb-0 table-striped">
                                            <tr>
                                                <th class="text-dark" data-width="40">#</th>
                                                <th class="text-dark">{{ __('Product') }}</th>
                                                <th class="text-dark">{{ __('Quantity') }}</th>
                                                <th class="text-dark">{{ __('Rate') }}</th>
                                                <th class="text-dark">{{ __('Discount') }}</th>
                                                <th class="text-dark">{{ __('Tax') }}</th>
                                                <th class="text-dark">{{ __('Chart Of Account') }}</th>
                                                <th class="text-dark">{{ __('Account Amount') }}</th>
                                                <th class="text-dark">{{ __('Description') }}</th>
                                                <th class="text-end text-dark" width="12%">{{ __('Price') }}<br>
                                                    <small
                                                        class="text-danger font-weight-bold">{{ __('after tax & discount') }}</small>
                                                </th>
                                                <th></th>
                                            </tr>
                                            @php
                                                $totalQuantity = 0;
                                                $totalRate = 0;
                                                $totalTaxPrice = 0;
                                                $totalDiscount = 0;
                                                $taxesData = [];
                                            @endphp



                                            @foreach ($items as $key => $item)
                                                {{-- @if (!empty($item->tax))
                                                    @php
                                                        $taxes=App\Models\Utility::tax($item->tax);
                                                        $totalQuantity+=$item->quantity;
                                                        $totalRate+=$item->price;
                                                        $totalDiscount+=$item->discount;
                                                        foreach($taxes as $taxe){
                                                            $taxDataPrice=App\Models\Utility::taxRate($taxe->rate,$item->price,$item->quantity,$item->discount);
                                                            if (array_key_exists($taxe->name,$taxesData))
                                                            {
                                                                $taxesData[$taxe->name] = $taxesData[$taxe->name]+$taxDataPrice;
                                                            }
                                                            else
                                                            {
                                                                $taxesData[$taxe->name] = $taxDataPrice;
                                                            }
                                                        }
                                                    @endphp
                                                @endif --}}

                                                @if (!empty($item->product_id))
                                                    <tr>
                                                        <td>{{ $key + 1 }}</td>

                                                        @php
                                                            $productName = $item->product;
                                                            $totalQuantity += $item->quantity;
                                                            $totalRate += $item->price;
                                                            $totalDiscount += $item->discount;
                                                        @endphp
                                                        <td>{{ !empty($productName) ? $productName->name : '-' }}</td>
                                                        <td>{{ $item->quantity . ' (' . $productName->unit->name . ')' }}
                                                        </td>
                                                        <td>{{ \Auth::user()->priceFormat($item->price) }}</td>
                                                        <td>{{ \Auth::user()->priceFormat($item->discount) }}</td>

                                                        <td>
                                                            @if (!empty($item->tax))
                                                                <table>
                                                                    @php
                                                                        $itemTaxes = [];
                                                                        $getTaxData = Utility::getTaxData();

                                                                        if (!empty($item->tax)) {
                                                                            foreach (explode(',', $item->tax) as $tax) {
                                                                                $taxPrice = \Utility::taxRate(
                                                                                    $getTaxData[$tax]['rate'],
                                                                                    $item->price,
                                                                                    $item->quantity,
                                                                                );
                                                                                $totalTaxPrice += $taxPrice;
                                                                                $itemTax['name'] =
                                                                                    $getTaxData[$tax]['name'];
                                                                                $itemTax['rate'] =
                                                                                    $getTaxData[$tax]['rate'] . '%';
                                                                                $itemTax[
                                                                                    'price'
                                                                                ] = \Auth::user()->priceFormat(
                                                                                    $taxPrice,
                                                                                );

                                                                                $itemTaxes[] = $itemTax;
                                                                                if (
                                                                                    array_key_exists(
                                                                                        $getTaxData[$tax]['name'],
                                                                                        $taxesData,
                                                                                    )
                                                                                ) {
                                                                                    $taxesData[
                                                                                        $getTaxData[$tax]['name']
                                                                                    ] =
                                                                                        $taxesData[
                                                                                            $getTaxData[$tax]['name']
                                                                                        ] + $taxPrice;
                                                                                } else {
                                                                                    $taxesData[
                                                                                        $getTaxData[$tax]['name']
                                                                                    ] = $taxPrice;
                                                                                }
                                                                            }
                                                                            $item->itemTax = $itemTaxes;
                                                                        } else {
                                                                            $item->itemTax = [];
                                                                        }
                                                                    @endphp
                                                                    @foreach ($item->itemTax as $tax)
                                                                        <tr>
                                                                            <td>{{ $tax['name'] . ' (' . $tax['rate'] . '%)' }}
                                                                            </td>
                                                                            <td>{{ $tax['price'] }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                </table>
                                                            @else
                                                                -
                                                            @endif
                                                        </td>

                                                        @php
                                                            $chartAccount = \App\Models\ChartOfAccount::find(
                                                                $item->chart_account_id,
                                                            );
                                                        @endphp

                                                        <td>{{ !empty($chartAccount) ? $chartAccount->name : '-' }}</td>
                                                        <td>{{ \Auth::user()->priceFormat($item->amount) }}</td>

                                                        <td>{{ !empty($item->description) ? $item->description : '-' }}
                                                        </td>

                                                        <td class="text-end">
                                                            {{ \Auth::user()->priceFormat($item->price * $item->quantity - $item->discount + $totalTaxPrice) }}
                                                        </td>
                                                        <td></td>
                                                    </tr>
                                                @else
                                                    <tr>
                                                        <td>{{ $key + 1 }}</td>
                                                        <td>-</td>
                                                        <td>-</td>
                                                        <td>-</td>
                                                        <td>-</td>
                                                        <td>-</td>
                                                        @php
                                                            $chartAccount = \App\Models\ChartOfAccount::find(
                                                                $item['chart_account_id'],
                                                            );
                                                        @endphp
                                                        <td>{{ !empty($chartAccount) ? $chartAccount->name : '-' }}</td>
                                                        <td>{{ \Auth::user()->priceFormat($item['amount']) }}</td>
                                                        <td>-</td>
                                                        <td class="text-end">
                                                            {{ \Auth::user()->priceFormat($item['amount']) }}</td>
                                                        <td></td>


                                                    </tr>
                                                @endif
                                            @endforeach
                                            <tfoot>
                                                <tr>
                                                    <td></td>
                                                    <td><b>{{ __('Total') }}</b></td>
                                                    <td><b>{{ $totalQuantity }}</b></td>
                                                    <td><b>{{ \Auth::user()->priceFormat($totalRate) }}</b></td>
                                                    <td><b>{{ \Auth::user()->priceFormat($totalDiscount) }}</b></td>
                                                    <td><b>{{ \Auth::user()->priceFormat($totalTaxPrice) }}</b></td>
                                                    <td></td>
                                                    <td><b>{{ \Auth::user()->priceFormat($bill->getAccountTotal()) }}</b>
                                                    </td>

                                                </tr>
                                                <tr>
                                                    <td colspan="8"></td>
                                                    <td class="text-end"><b>{{ __('Sub Total') }}</b></td>
                                                    <td class="text-end">
                                                        {{ \Auth::user()->priceFormat($bill->getSubTotal()) }}</td>
                                                </tr>

                                                <tr>
                                                    <td colspan="8"></td>
                                                    <td class="text-end"><b>{{ __('Discount') }}</b></td>
                                                    <td class="text-end">
                                                        {{ \Auth::user()->priceFormat($bill->getTotalDiscount()) }}</td>
                                                </tr>

                                                @if (!empty($taxesData))
                                                    @foreach ($taxesData as $taxName => $taxPrice)
                                                        <tr>
                                                            <td colspan="8"></td>
                                                            <td class="text-end"><b>{{ $taxName }}</b></td>
                                                            <td class="text-end">
                                                                {{ \Auth::user()->priceFormat($taxPrice) }}</td>
                                                        </tr>
                                                    @endforeach
                                                @endif
                                                <tr>
                                                    <td colspan="8"></td>
                                                    <td class="blue-text text-end"><b>{{ __('Total') }}</b></td>
                                                    <td class="blue-text text-end">
                                                        {{ \Auth::user()->priceFormat($bill->getTotal()) }}</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="8"></td>
                                                    <td class="text-end"><b>{{ __('Paid') }}</b></td>
                                                    <td class="text-end">
                                                        {{ \Auth::user()->priceFormat($bill->getTotal() - $bill->getDue() - $bill->billTotalDebitNote()) }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td colspan="8"></td>
                                                    <td class="text-end"><b>{{ __('Debit Note') }}</b></td>
                                                    <td class="text-end">
                                                        {{ \Auth::user()->priceFormat($bill->billTotalDebitNote()) }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td colspan="8"></td>
                                                    <td class="text-end"><b>{{ __('Due') }}</b></td>
                                                    <td class="text-end">
                                                        {{ \Auth::user()->priceFormat($bill->getDue()) }}</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <h5 class=" d-inline-block mb-5">{{ __('Payment Summary') }}</h5>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th class="text-dark">{{ __('Payment Receipt') }}</th>
                                    <th class="text-dark">{{ __('Date') }}</th>
                                    <th class="text-dark">{{ __('Amount') }}</th>
                                    <th class="text-dark">{{ __('Account') }}</th>
                                    <th class="text-dark">{{ __('Reference') }}</th>
                                    <th class="text-dark">{{ __('Description') }}</th>
                                    @can('delete payment bill')
                                        <th class="text-dark">{{ __('Action') }}</th>
                                    @endcan
                                </tr>
                            </thead>
                            @forelse($bill->payments as $key =>$payment)
                                <tr>
                                    <td>
                                        @if (!empty($payment->add_receipt))
                                            <a href="{{ asset(Storage::url('uploads/payment')) . '/' . $payment->add_receipt }}"
                                                download="" class="btn btn-sm btn-secondary btn-icon rounded-pill"
                                                target="_blank"><span class="btn-inner--icon"><i
                                                        class="ti ti-download"></i></span></a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ \Auth::user()->dateFormat($payment->date) }}</td>
                                    <td>{{ \Auth::user()->priceFormat($payment->amount) }}</td>
                                    <td>{{ !empty($payment->bankAccount) ? $payment->bankAccount->bank_name . ' ' . $payment->bankAccount->holder_name : '' }}
                                    </td>
                                    <td>{{ $payment->reference }}</td>
                                    <td>{{ $payment->description }}</td>
                                    <td class="text-dark">
                                        @can('delete bill product')
                                            <div class="action-btn bg-danger ms-2">
                                                {!! Form::open([
                                                    'method' => 'post',
                                                    'route' => ['bill.payment.destroy', $bill->id, $payment->id],
                                                    'id' => 'delete-form-' . $payment->id,
                                                ]) !!}
                                                <a href="#" class="mx-3 btn btn-sm  align-items-center bs-pass-para"
                                                    data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                    data-original-title="{{ __('Delete') }}"
                                                    data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                    data-confirm-yes="document.getElementById('delete-form-{{ $payment->id }}').submit();">
                                                    <i class="ti ti-trash text-white text-white text-white"></i>
                                                </a>
                                                {!! Form::close() !!}
                                            </div>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-dark">
                                        <p>{{ __('No Data Found') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <h5 class="d-inline-block mb-5">{{ __('Debit Note Summary') }}</h5>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th class="text-dark">{{ __('Date') }}</th>
                                    <th class="text-dark">{{ __('Amount') }}</th>
                                    <th class="text-dark">{{ __('Description') }}</th>
                                    @if (Gate::check('edit debit note') || Gate::check('delete debit note'))
                                        <th class="text-dark">{{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            @forelse($bill->debitNote as $key =>$debitNote)
                                <tr>
                                    <td>{{ \Auth::user()->dateFormat($debitNote->date) }}</td>
                                    <td>{{ \Auth::user()->priceFormat($debitNote->amount) }}</td>
                                    <td>{{ $debitNote->description }}</td>
                                    <td>
                                        @can('edit debit note')
                                            <div class="action-btn bg-primary ms-2">
                                                <a data-url="{{ route('bill.edit.debit.note', [$debitNote->bill, $debitNote->id]) }}"
                                                    data-ajax-popup="true" data-title="{{ __('Edit Debit Note') }}"
                                                    href="#" class="mx-3 btn btn-sm align-items-center"
                                                    data-bs-toggle="tooltip" data-original-title="{{ __('Edit') }}">
                                                    <i class="ti ti-pencil text-white"></i>
                                                </a>
                                            </div>
                                        @endcan
                                        @can('delete debit note')
                                            <div class="action-btn bg-danger ms-2">
                                                {!! Form::open([
                                                    'method' => 'DELETE',
                                                    'route' => ['bill.delete.debit.note', $debitNote->bill, $debitNote->id],
                                                    'id' => 'delete-form-' . $debitNote->id,
                                                ]) !!}
                                                <a href="#" class="mx-3 btn btn-sm  align-items-center bs-pass-para"
                                                    data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                    data-original-title="{{ __('Delete') }}"
                                                    data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                    data-confirm-yes="document.getElementById('delete-form-{{ $debitNote->id }}').submit();">
                                                    <i class="ti ti-trash text-white text-white"></i>
                                                </a>
                                                {!! Form::close() !!}
                                            </div>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-dark">
                                        <p>{{ __('No Data Found') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
