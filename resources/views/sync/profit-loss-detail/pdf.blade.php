<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Profit and Loss - Detail</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; }
        .header { text-align: center; margin-bottom: 30px; }
        .company-name { font-size: 16px; font-weight: bold; }
        .report-title { font-size: 14px; font-weight: bold; margin: 5px 0; }
        .date-range { font-size: 12px; margin-bottom: 20px; }
        
        table { width: 100%; border-collapse: collapse; }
        .section-header { font-weight: bold; font-size: 11px; text-transform: uppercase; padding: 8px 0; }
        .account-name { padding-left: 20px; font-weight: 500; }
        .transaction-detail { padding-left: 40px; font-size: 9px; color: #666; }
        .subtotal { font-weight: bold; border-top: 1px solid #000; padding-top: 2px; }
        .total { font-weight: bold; border-top: 2px solid #000; border-bottom: 3px double #000; padding: 4px 0; }
        .amount { text-align: right; }
        .spacing { height: 8px; }
        .positive { color: #000; }
        .negative { color: #666; }
        .net-income-positive { color: #28a745; font-weight: bold; }
        .net-income-negative { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $company->company_name }}</div>
        <div class="report-title">Profit and Loss - Detail</div>
        <div class="date-range">{{ $fromDate->format('F j, Y') }} to {{ $toDate->format('F j, Y') }}</div>
    </div>

    <table>
        @php
            $incomeAccounts = $accounts->where('type_name', 'Income');
            $cogsAccounts = $accounts->where('type_name', 'Cost of Sales');
            $expenseAccounts = $accounts->where('type_name', 'Expense');

            $incomeAccounts = $incomeAccounts->map(function($account) {
                $account->balance = $account->total_credit - $account->total_debit;
                return $account;
            });

            $cogsAccounts = $cogsAccounts->map(function($account) {
                $account->balance = $account->total_debit - $account->total_credit;
                return $account;
            });

            $expenseAccounts = $expenseAccounts->map(function($account) {
                $account->balance = $account->total_debit - $account->total_credit;
                return $account;
            });

            $totalIncome = $incomeAccounts->sum('balance');
            $totalCOGS = $cogsAccounts->sum('balance');
            $totalExpenses = $expenseAccounts->sum('balance');
            $grossProfit = $totalIncome - $totalCOGS;
            $netIncome = $grossProfit - $totalExpenses;
        @endphp

        <!-- INCOME -->
        @if($incomeAccounts->where('balance', '!=', 0)->isNotEmpty())
        <tr><td class="section-header">INCOME</td><td></td></tr>
        
        @foreach($incomeAccounts->where('balance', '!=', 0) as $account)
        <tr>
            <td class="account-name">{{ $account->name }}</td>
            <td class="amount">{{ number_format($account->balance, 2) }}</td>
        </tr>
        
        @if(isset($transactions[$account->id]))
            @foreach($transactions[$account->id] as $transaction)
            <tr>
                <td class="transaction-detail">
                    {{ \Carbon\Carbon::parse($transaction->date)->format('m/d/Y') }} - {{ $transaction->description ?? 'Journal Entry' }}
                    @if($transaction->reference) ({{ $transaction->reference }}) @endif
                </td>
                <td class="amount">
                    @php $netAmount = $transaction->credit - $transaction->debit; @endphp
                    @if($netAmount != 0)
                        <span class="{{ $netAmount > 0 ? 'positive' : 'negative' }}">{{ number_format(abs($netAmount), 2) }}</span>
                    @endif
                </td>
            </tr>
            @endforeach
        @endif
        @endforeach
        
        <tr><td class="spacing"></td><td></td></tr>
        <tr>
            <td class="subtotal">Total Income</td>
            <td class="amount subtotal">{{ number_format($totalIncome, 2) }}</td>
        </tr>
        
        <tr><td class="spacing"></td><td></td></tr>
        @endif

        <!-- COST OF GOODS SOLD -->
        @if($cogsAccounts->where('balance', '!=', 0)->isNotEmpty())
        <tr><td class="section-header">COST OF GOODS SOLD</td><td></td></tr>
        
        @foreach($cogsAccounts->where('balance', '!=', 0) as $account)
        <tr>
            <td class="account-name">{{ $account->name }}</td>
            <td class="amount">{{ number_format($account->balance, 2) }}</td>
        </tr>
        
        @if(isset($transactions[$account->id]))
            @foreach($transactions[$account->id] as $transaction)
            <tr>
                <td class="transaction-detail">
                    {{ \Carbon\Carbon::parse($transaction->date)->format('m/d/Y') }} - {{ $transaction->description ?? 'Journal Entry' }}
                    @if($transaction->reference) ({{ $transaction->reference }}) @endif
                </td>
                <td class="amount">
                    @php $netAmount = $transaction->debit - $transaction->credit; @endphp
                    @if($netAmount != 0)
                        <span class="{{ $netAmount > 0 ? 'positive' : 'negative' }}">{{ number_format(abs($netAmount), 2) }}</span>
                    @endif
                </td>
            </tr>
            @endforeach
        @endif
        @endforeach
        
        <tr><td class="spacing"></td><td></td></tr>
        <tr>
            <td class="subtotal">Total Cost of Goods Sold</td>
            <td class="amount subtotal">{{ number_format($totalCOGS, 2) }}</td>
        </tr>
        
        <tr><td class="spacing"></td><td></td></tr>
        @endif

        <!-- GROSS PROFIT -->
        <tr>
            <td class="total">GROSS PROFIT</td>
            <td class="amount total {{ $grossProfit >= 0 ? 'net-income-positive' : 'net-income-negative' }}">{{ number_format($grossProfit, 2) }}</td>
        </tr>
        
        <tr><td class="spacing"></td><td></td></tr>

        <!-- EXPENSES -->
        @if($expenseAccounts->where('balance', '!=', 0)->isNotEmpty())
        <tr><td class="section-header">EXPENSES</td><td></td></tr>
        
        @foreach($expenseAccounts->where('balance', '!=', 0) as $account)
        <tr>
            <td class="account-name">{{ $account->name }}</td>
            <td class="amount">{{ number_format($account->balance, 2) }}</td>
        </tr>
        
        @if(isset($transactions[$account->id]))
            @foreach($transactions[$account->id] as $transaction)
            <tr>
                <td class="transaction-detail">
                    {{ \Carbon\Carbon::parse($transaction->date)->format('m/d/Y') }} - {{ $transaction->description ?? 'Journal Entry' }}
                    @if($transaction->reference) ({{ $transaction->reference }}) @endif
                </td>
                <td class="amount">
                    @php $netAmount = $transaction->debit - $transaction->credit; @endphp
                    @if($netAmount != 0)
                        <span class="{{ $netAmount > 0 ? 'positive' : 'negative' }}">{{ number_format(abs($netAmount), 2) }}</span>
                    @endif
                </td>
            </tr>
            @endforeach
        @endif
        @endforeach
        
        <tr><td class="spacing"></td><td></td></tr>
        <tr>
            <td class="subtotal">Total Expenses</td>
            <td class="amount subtotal">{{ number_format($totalExpenses, 2) }}</td>
        </tr>
        
        <tr><td class="spacing"></td><td></td></tr>
        @endif

        <!-- NET INCOME -->
        <tr>
            <td class="total">NET INCOME</td>
            <td class="amount total {{ $netIncome >= 0 ? 'net-income-positive' : 'net-income-negative' }}">{{ number_format($netIncome, 2) }}</td>
        </tr>
    </table>
</body>
</html>