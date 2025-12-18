@extends('layouts.admin')
@section('page-title')
    {{ __('Sales Receipt Detail') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('sales-receipt.index') }}">{{ __('Sales Receipt') }}</a></li>
    <li class="breadcrumb-item">{{ Auth::user()->salesReceiptNumberFormat($salesReceipt->sales_receipt_id) }}</li>
@endsection
@php
    $settings = Utility::settings();
@endphp

@section('content')

    @if (Gate::check('show invoice'))
        @if ($salesReceipt->status != 0)
            <div class="row justify-content-between align-items-center mb-3">
                <div class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end">
                    <div class="all-button-box mr-2">
                        <a href="{{ route('sales-receipt.resent', $salesReceipt->id) }}"
                            class="btn btn-sm btn-primary me-2">{{ __('Resend Sales Receipt') }}</a>
                    </div>
                    <div class="all-button-box">
                        <a href="{{ route('sales-receipt.pdf', Crypt::encrypt($salesReceipt->id)) }}" target="_blank"
                            class="btn btn-sm btn-primary">{{ __('Download') }}</a>
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
                                    <h4>{{ __('Sales Receipt') }}</h4>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12 text-end">
                                    <h4 class="invoice-number">
                                        {{ Auth::user()->salesReceiptNumberFormat($salesReceipt->sales_receipt_id) }}</h4>
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
                                                {{ \Auth::user()->dateFormat($salesReceipt->issue_date) }}<br><br>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">

                                <div class="col">
                                    <small class="font-style">
                                        <strong>{{ __('Billed To') }} :</strong><br>
                                        @if (!empty($customer->billing_name))
                                            {{ !empty($customer->billing_name) ? $customer->billing_name : '' }}<br>
                                            {{ !empty($customer->billing_address) ? $customer->billing_address : '' }}<br>
                                            {{ !empty($customer->billing_city) ? $customer->billing_city : '' . ', ' }}<br>
                                            {{ !empty($customer->billing_state) ? $customer->billing_state . ', ' : '' }}
                                            {{ !empty($customer->billing_zip) ? $customer->billing_zip : '' }}<br>
                                            {{ !empty($customer->billing_country) ? $customer->billing_country : '' }}<br>
                                            {{ !empty($customer->billing_phone) ? $customer->billing_phone : '' }}<br>
                                            @if ($settings['vat_gst_number_switch'] == 'on')
                                                <strong>{{ __('Tax Number ') }} :
                                                </strong>{{ !empty($customer->tax_number) ? $customer->tax_number : '' }}
                                            @endif
                                        @else
                                            -
                                        @endif

                                    </small>
                                </div>

                                <div class="col">
                                    <div class="float-end mt-3">
                                        {!! DNS2D::getBarcodeHTML(
                                            route('sales-receipt.link.copy', \Illuminate\Support\Facades\Crypt::encrypt($salesReceipt->id)),
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
                                        @if ($salesReceipt->status == 0)
                                            <span
                                                class="badge bg-primary">{{ __('Draft') }}</span>
                                        @elseif($salesReceipt->status == 1)
                                            <span
                                                class="badge bg-success">{{ __('Approved') }}</span>
                                        @endif
                                    </small>
                                </div>

                                @if (!empty($customFields) && count($salesReceipt->customField) > 0)
                                    @foreach ($customFields as $field)
                                        <div class="col text-md-right">
                                            <small>
                                                <strong>{{ $field->name }} :</strong><br>
                                                {{ !empty($salesReceipt->customField) ? $salesReceipt->customField[$field->id] : '-' }}
                                                <br><br>
                                            </small>
                                        </div>
                                    @endforeach
                                @endif
                            </div>

                            <!-- Payment Details -->
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="font-weight-bold">{{ __('Payment Details') }}</div>
                                    <div class="table-responsive mt-2">
                                        <table class="table table-bordered">
                                            <tr>
                                                <td><strong>{{ __('Payment Type') }}:</strong></td>
                                                <td>{{ $salesReceipt->payment_type ?? '-' }}</td>
                                                <td><strong>{{ __('Payment Method') }}:</strong></td>
                                                <td>{{ $salesReceipt->payment_method ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>{{ __('Deposit To') }}:</strong></td>
                                                <td>{{ $salesReceipt->deposit_to ?? '-' }}</td>
                                                <td><strong>{{ __('Location of Sale') }}:</strong></td>
                                                <td>{{ $salesReceipt->location_of_sale ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>{{ __('Reference Number') }}:</strong></td>
                                                <td>{{ $salesReceipt->ref_number ?? '-' }}</td>
                                                <td><strong>{{ __('Amount Received') }}:</strong></td>
                                                <td>{{ \Auth::user()->priceFormat($salesReceipt->amount_received) }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="font-weight-bold">{{ __('Product Summary') }}</div>
                                    <small>{{ __('All items here cannot be deleted.') }}</small>
                                    <div class="table-responsive mt-2">
                                        <table class="table mb-0 table-striped">
                                            <tr>
                                                <th data-width="40" class="text-dark">#</th>
                                                <th class="text-dark">{{ __('Product') }}</th>
                                                <th class="text-dark">{{ __('Quantity') }}</th>
                                                <th class="text-dark">{{ __('Rate') }}</th>
                                                <th class="text-dark">{{ __('Discount') }}</th>
                                                <th class="text-dark">{{ __('Tax') }}</th>
                                                <th class="text-dark">{{ __('Description') }}</th>
                                                <th class="text-end text-dark" width="12%">{{ __('Price') }}<br>
                                                    <small
                                                        class="text-danger font-weight-bold">{{ __('after tax & discount') }}</small>
                                                </th>
                                            </tr>
                                            @php
                                                $totalQuantity = 0;
                                                $totalRate = 0;
                                                $totalTaxPrice = 0;
                                                $totalDiscount = 0;
                                                $taxesData = [];
                                            @endphp
                                            @foreach ($iteams as $key => $iteam)
                                                <tr>
                                                    <td>{{ $key + 1 }}</td>
                                                    @php
                                                        $productName = $iteam->product;
                                                        $totalRate += $iteam->price;
                                                        $totalQuantity += $iteam->quantity;
                                                        $totalDiscount += $iteam->discount;
                                                    @endphp
                                                    <td>{{ !empty($productName) ? $productName->name : '' }}</td>
                                                    <td>{{ $iteam->quantity . ' (' . (isset($productName->unit) ? $productName->unit->name : 'No unit') . ')' }}
                                                    </td>
                                                    <td>{{ \Auth::user()->priceFormat($iteam->price) }}</td>
                                                    <td>{{ \Auth::user()->priceFormat($iteam->discount) }}</td>

                                                    <td>
                                                        @if (!empty($iteam->tax))
                                                            <table>
                                                                @php
                                                                    $itemTaxes = [];
                                                                    $getTaxData = Utility::getTaxData();

                                                                    if (!empty($iteam->tax)) {
                                                                        foreach (explode(',', $iteam->tax) as $tax) {
                                                                            $taxPrice = \Utility::taxRate(
                                                                                $getTaxData[$tax]['rate'],
                                                                                $iteam->price,
                                                                                $iteam->quantity,
                                                                            );
                                                                            $totalTaxPrice += $taxPrice;
                                                                            $itemTax['name'] =
                                                                                $getTaxData[$tax]['name'];
                                                                            $itemTax['rate'] =
                                                                                $getTaxData[$tax]['rate'] . '%';
                                                                            $itemTax[
                                                                                'price'
                                                                            ] = \Auth::user()->priceFormat($taxPrice);

                                                                            $itemTaxes[] = $itemTax;
                                                                            if (
                                                                                array_key_exists(
                                                                                    $getTaxData[$tax]['name'],
                                                                                    $taxesData,
                                                                                )
                                                                            ) {
                                                                                $taxesData[$getTaxData[$tax]['name']] =
                                                                                    $taxesData[
                                                                                        $getTaxData[$tax]['name']
                                                                                    ] + $taxPrice;
                                                                            } else {
                                                                                $taxesData[
                                                                                    $getTaxData[$tax]['name']
                                                                                ] = $taxPrice;
                                                                            }
                                                                        }
                                                                        $iteam->itemTax = $itemTaxes;
                                                                    } else {
                                                                        $iteam->itemTax = [];
                                                                    }
                                                                @endphp
                                                                @foreach ($iteam->itemTax as $tax)
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

                                                    <td>{{ !empty($iteam->description) ? $iteam->description : '-' }}</td>
                                                    <td class="text-end">
                                                        {{ \Auth::user()->priceFormat($iteam->price * $iteam->quantity - $iteam->discount + $totalTaxPrice) }}
                                                    </td>
                                                </tr>
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
                                                </tr>
                                                <tr>
                                                    <td colspan="6"></td>
                                                    <td class="text-end"><b>{{ __('Sub Total') }}</b></td>
                                                    <td class="text-end">
                                                        {{ \Auth::user()->priceFormat($salesReceipt->getSubTotal()) }}</td>
                                                </tr>

                                                <tr>
                                                    <td colspan="6"></td>
                                                    <td class="text-end"><b>{{ __('Discount') }}</b></td>
                                                    <td class="text-end">
                                                        {{ \Auth::user()->priceFormat($salesReceipt->getTotalDiscount()) }}
                                                    </td>
                                                </tr>

                                                @if (!empty($taxesData))
                                                    @foreach ($taxesData as $taxName => $taxPrice)
                                                        <tr>
                                                            <td colspan="6"></td>
                                                            <td class="text-end"><b>{{ $taxName }}</b></td>
                                                            <td class="text-end">
                                                                {{ \Auth::user()->priceFormat($taxPrice) }}</td>
                                                        </tr>
                                                    @endforeach
                                                @endif
                                                <tr>
                                                    <td colspan="6"></td>
                                                    <td class="blue-text text-end"><b>{{ __('Total') }}</b></td>
                                                    <td class="blue-text text-end">
                                                        {{ \Auth::user()->priceFormat($salesReceipt->getTotal()) }}</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="6"></td>
                                                    <td class="text-end"><b>{{ __('Amount Received') }}</b></td>
                                                    <td class="text-end">
                                                        {{ \Auth::user()->priceFormat($salesReceipt->amount_received) }}</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            @if (!empty($salesReceipt->memo) || !empty($salesReceipt->note))
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        @if (!empty($salesReceipt->memo))
                                            <div class="font-weight-bold">{{ __('Memo') }}</div>
                                            <p>{{ $salesReceipt->memo }}</p>
                                        @endif
                                    </div>
                                    <div class="col-md-6">
                                        @if (!empty($salesReceipt->note))
                                            <div class="font-weight-bold">{{ __('Note') }}</div>
                                            <p>{{ $salesReceipt->note }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection