<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Sales Receipt') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #007bff;
            margin: 0;
        }
        .receipt-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .receipt-info p {
            margin: 5px 0;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .items-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .total-section {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
        }
        .button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('Sales Receipt') }}</h1>
        <p>{{ __('Receipt #') }}{{ Auth::user()->salesReceiptNumberFormat($salesReceipt->sales_receipt_id) }}</p>
    </div>

    <div class="receipt-info">
        <p><strong>{{ __('Issue Date') }}:</strong> {{ Auth::user()->dateFormat($salesReceipt->issue_date) }}</p>
        <p><strong>{{ __('Customer') }}:</strong> {{ $customer->name }}</p>
        <p><strong>{{ __('Amount Received') }}:</strong> {{ Auth::user()->priceFormat($salesReceipt->amount_received) }}</p>
    </div>

    <h3>{{ __('Items') }}</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th>{{ __('Product') }}</th>
                <th>{{ __('Quantity') }}</th>
                <th>{{ __('Price') }}</th>
                <th>{{ __('Total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($salesReceipt->items as $item)
                <tr>
                    <td>{{ $item->product ? $item->product->name : '' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ Auth::user()->priceFormat($item->price) }}</td>
                    <td>{{ Auth::user()->priceFormat($item->price * $item->quantity - $item->discount + $item->item_tax_price) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        <p><strong>{{ __('Subtotal') }}:</strong> <span class="text-right">{{ Auth::user()->priceFormat($salesReceipt->subtotal) }}</span></p>
        <p><strong>{{ __('Discount') }}:</strong> <span class="text-right">{{ Auth::user()->priceFormat($salesReceipt->total_discount) }}</span></p>
        <p><strong>{{ __('Tax') }}:</strong> <span class="text-right">{{ Auth::user()->priceFormat($salesReceipt->total_tax) }}</span></p>
        <p><strong>{{ __('Total Amount') }}:</strong> <span class="text-right">{{ Auth::user()->priceFormat($salesReceipt->total_amount) }}</span></p>
        <p><strong>{{ __('Amount Received') }}:</strong> <span class="text-right">{{ Auth::user()->priceFormat($salesReceipt->amount_received) }}</span></p>
    </div>

    @if($salesReceipt->memo)
        <div style="margin-top: 20px;">
            <h4>{{ __('Memo') }}</h4>
            <p>{{ $salesReceipt->memo }}</p>
        </div>
    @endif

    @if($salesReceipt->note)
        <div style="margin-top: 20px;">
            <h4>{{ __('Note') }}</h4>
            <p>{{ $salesReceipt->note }}</p>
        </div>
    @endif

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('sales-receipt.link.copy', \Crypt::encrypt($salesReceipt->id)) }}" class="button">{{ __('View Full Receipt') }}</a>
    </div>

    <div class="footer">
        <p>{{ __('Thank you for your business!') }}</p>
        <p>{{ __('This is an automated message. Please do not reply.') }}</p>
    </div>
</body>
</html>