<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Sales Receipt') }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #2c3e50;
        }
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .info-block {
            width: 48%;
        }
        .info-block h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #7f8c8d;
            text-transform: uppercase;
        }
        .info-block p {
            margin: 5px 0;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #bdc3c7;
            padding: 8px;
            text-align: left;
            font-size: 11px;
        }
        th {
            background-color: #ecf0f1;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-section {
            margin-top: 20px;
            text-align: right;
        }
        .total-section table {
            width: 300px;
            margin-left: auto;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('Sales Receipt') }}</h1>
        <p>{{ Auth::user()->salesReceiptNumberFormat($salesReceipt->sales_receipt_id) }}</p>
    </div>

    <div class="info-section">
        <div class="info-block">
            <h3>{{ __('From') }}</h3>
            <p><strong>{{ $settings['company_name'] ?? 'Company Name' }}</strong></p>
            <p>{{ $settings['company_address'] ?? '' }}</p>
            <p>{{ $settings['company_city'] ?? '' }}, {{ $settings['company_state'] ?? '' }} {{ $settings['company_zipcode'] ?? '' }}</p>
            <p>{{ $settings['company_country'] ?? '' }}</p>
            <p>{{ __('Phone') }}: {{ $settings['company_telephone'] ?? '' }}</p>
        </div>

        <div class="info-block">
            <h3>{{ __('To') }}</h3>
            @if($customer)
                <p><strong>{{ $customer->name }}</strong></p>
                <p>{{ $customer->billing_address ?? '' }}</p>
                <p>{{ $customer->billing_city ?? '' }}, {{ $customer->billing_state ?? '' }} {{ $customer->billing_zip ?? '' }}</p>
                <p>{{ $customer->billing_country ?? '' }}</p>
                <p>{{ __('Phone') }}: {{ $customer->billing_phone ?? '' }}</p>
                <p>{{ __('Email') }}: {{ $customer->email ?? '' }}</p>
            @endif
        </div>
    </div>

    <div class="info-section">
        <div class="info-block">
            <h3>{{ __('Receipt Details') }}</h3>
            <p><strong>{{ __('Issue Date') }}:</strong> {{ Auth::user()->dateFormat($salesReceipt->issue_date) }}</p>
            <p><strong>{{ __('Status') }}:</strong> {{ $salesReceipt->status == 1 ? __('Approved') : __('Draft') }}</p>
        </div>

        <div class="info-block">
            <h3>{{ __('Payment Details') }}</h3>
            <p><strong>{{ __('Payment Type') }}:</strong> {{ $salesReceipt->payment_type ?? '-' }}</p>
            <p><strong>{{ __('Payment Method') }}:</strong> {{ $salesReceipt->payment_method ?? '-' }}</p>
            <p><strong>{{ __('Amount Received') }}:</strong> {{ Auth::user()->priceFormat($salesReceipt->amount_received) }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('Product') }}</th>
                <th>{{ __('Quantity') }}</th>
                <th>{{ __('Rate') }}</th>
                <th>{{ __('Discount') }}</th>
                <th>{{ __('Tax') }}</th>
                <th>{{ __('Amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalQuantity = 0;
                $totalRate = 0;
                $totalDiscount = 0;
                $totalTax = 0;
            @endphp
            @foreach($iteams as $key => $item)
                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>{{ $item->product ? $item->product->name : '' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ Auth::user()->priceFormat($item->price) }}</td>
                    <td>{{ Auth::user()->priceFormat($item->discount) }}</td>
                    <td>{{ Auth::user()->priceFormat($item->item_tax_price) }}</td>
                    <td>{{ Auth::user()->priceFormat(($item->price * $item->quantity) - $item->discount + $item->item_tax_price) }}</td>
                </tr>
                @php
                    $totalQuantity += $item->quantity;
                    $totalRate += $item->price;
                    $totalDiscount += $item->discount;
                    $totalTax += $item->item_tax_price;
                @endphp
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2"><strong>{{ __('Total') }}</strong></td>
                <td><strong>{{ $totalQuantity }}</strong></td>
                <td><strong>{{ Auth::user()->priceFormat($totalRate) }}</strong></td>
                <td><strong>{{ Auth::user()->priceFormat($totalDiscount) }}</strong></td>
                <td><strong>{{ Auth::user()->priceFormat($totalTax) }}</strong></td>
                <td><strong>{{ Auth::user()->priceFormat($salesReceipt->total_amount) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <div class="total-section">
        <table>
            <tr>
                <td><strong>{{ __('Subtotal') }}:</strong></td>
                <td>{{ Auth::user()->priceFormat($salesReceipt->subtotal) }}</td>
            </tr>
            <tr>
                <td><strong>{{ __('Discount') }}:</strong></td>
                <td>{{ Auth::user()->priceFormat($salesReceipt->total_discount) }}</td>
            </tr>
            <tr>
                <td><strong>{{ __('Tax') }}:</strong></td>
                <td>{{ Auth::user()->priceFormat($salesReceipt->total_tax) }}</td>
            </tr>
            <tr>
                <td><strong>{{ __('Total Amount') }}:</strong></td>
                <td>{{ Auth::user()->priceFormat($salesReceipt->total_amount) }}</td>
            </tr>
            <tr>
                <td><strong>{{ __('Amount Received') }}:</strong></td>
                <td>{{ Auth::user()->priceFormat($salesReceipt->amount_received) }}</td>
            </tr>
        </table>
    </div>

    @if($salesReceipt->memo || $salesReceipt->note)
        <div style="margin-top: 30px;">
            @if($salesReceipt->memo)
                <h4>{{ __('Memo') }}</h4>
                <p>{{ $salesReceipt->memo }}</p>
            @endif
            @if($salesReceipt->note)
                <h4>{{ __('Note') }}</h4>
                <p>{{ $salesReceipt->note }}</p>
            @endif
        </div>
    @endif

    <div class="footer">
        <p>{{ __('Thank you for your business!') }}</p>
        <p>{{ __('Generated on') }} {{ date('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>