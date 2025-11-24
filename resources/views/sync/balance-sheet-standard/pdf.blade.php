<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Balance Sheet - Standard</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        .header { text-align: center; margin-bottom: 30px; }
        .company-name { font-size: 16px; font-weight: bold; }
        .report-title { font-size: 14px; font-weight: bold; margin: 5px 0; }
        .as-of-date { font-size: 12px; margin-bottom: 20px; }
        
        table { width: 100%; border-collapse: collapse; }
        .section-header { font-weight: bold; font-size: 12px; text-transform: uppercase; padding: 8px 0; }
        .account-name { padding-left: 20px; }
        .subtotal { font-weight: bold; border-top: 1px solid #000; padding-top: 2px; }
        .total { font-weight: bold; border-top: 2px solid #000; border-bottom: 3px double #000; padding: 4px 0; }
        .amount { text-align: right; }
        .spacing { height: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $company->company_name }}</div>
        <div class="report-title">Balance Sheet - Standard</div>
        <div class="as-of-date">As of {{ $asOfDate->format('F j, Y') }}</div>
    </div>

    <table>
        @php
            $assetAccounts = $accounts->where('account_type', 'Asset');
            $liabilityAccounts = $accounts->where('account_type', 'Liability');
            $equityAccounts = $accounts->where('account_type', 'Equity');

            $assetAccounts = $assetAccounts->map(function($account) {
                $account->balance = $account->total_debit - $account->total_credit;
                return $account;
            });

            $liabilityAccounts = $liabilityAccounts->map(function($account) {
                $account->balance = $account->total_credit - $account->total_debit;
                return $account;
            });

            $equityAccounts = $equityAccounts->map(function($account) {
                $account->balance = $account->total_credit - $account->total_debit;
                return $account;
            });

            $totalAssets = $assetAccounts->sum('balance');
            $totalLiabilities = $liabilityAccounts->sum('balance');
            $totalEquity = $equityAccounts->sum('balance');
        @endphp

        <!-- ASSETS -->
        <tr><td class="section-header">ASSETS</td><td></td></tr>
        
        @foreach($assetAccounts->where('balance', '!=', 0) as $account)
        <tr>
            <td class="account-name">{{ $account->name }}</td>
            <td class="amount">{{ number_format($account->balance, 2) }}</td>
        </tr>
        @endforeach
        
        <tr><td class="spacing"></td><td></td></tr>
        <tr>
            <td class="subtotal">TOTAL ASSETS</td>
            <td class="amount subtotal">{{ number_format($totalAssets, 2) }}</td>
        </tr>
        
        <tr><td class="spacing"></td><td></td></tr>
        
        <!-- LIABILITIES AND EQUITY -->
        <tr><td class="section-header">LIABILITIES AND EQUITY</td><td></td></tr>
        
        <tr><td class="section-header">Liabilities</td><td></td></tr>
        @foreach($liabilityAccounts->where('balance', '!=', 0) as $account)
        <tr>
            <td class="account-name">{{ $account->name }}</td>
            <td class="amount">{{ number_format($account->balance, 2) }}</td>
        </tr>
        @endforeach
        
        <tr>
            <td class="subtotal">Total Liabilities</td>
            <td class="amount subtotal">{{ number_format($totalLiabilities, 2) }}</td>
        </tr>
        
        <tr><td class="spacing"></td><td></td></tr>
        
        <tr><td class="section-header">Equity</td><td></td></tr>
        @foreach($equityAccounts->where('balance', '!=', 0) as $account)
        <tr>
            <td class="account-name">{{ $account->name }}</td>
            <td class="amount">{{ number_format($account->balance, 2) }}</td>
        </tr>
        @endforeach
        
        <tr>
            <td class="subtotal">Total Equity</td>
            <td class="amount subtotal">{{ number_format($totalEquity, 2) }}</td>
        </tr>
        
        <tr><td class="spacing"></td><td></td></tr>
        
        <tr>
            <td class="total">TOTAL LIABILITIES AND EQUITY</td>
            <td class="amount total">{{ number_format($totalLiabilities + $totalEquity, 2) }}</td>
        </tr>
    </table>
</body>
</html>