<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dashboard Widgets Configuration
    |--------------------------------------------------------------------------
    |
    | Each widget maps a key to a Blade view and display name.
    | The key must match what's stored in dashboard_widgets table.
    |
    */

    'widgets' => [
        'profit_loss' => [
            'name' => 'Profit & Loss',
            'view' => 'dashboard.widgets.profit-loss',
            'icon' => 'ti ti-chart-bar',
        ],
        'expenses' => [
            'name' => 'Expenses',
            'view' => 'dashboard.widgets.expenses',
            'icon' => 'ti ti-receipt',
        ],
        'invoices' => [
            'name' => 'Invoices',
            'view' => 'dashboard.widgets.invoices',
            'icon' => 'ti ti-file-invoice',
        ],
        'bank_accounts' => [
            'name' => 'Bank Accounts',
            'view' => 'dashboard.widgets.bank-accounts',
            'icon' => 'ti ti-building-bank',
        ],
        'sales_funnel' => [
            'name' => 'Sales & Get Paid Funnel',
            'view' => 'dashboard.widgets.sales-funnel',
            'icon' => 'ti ti-chart-infographic',
        ],
        'cashflow' => [
            'name' => 'Cash Flow',
            'view' => 'dashboard.widgets.cashflow',
            'icon' => 'ti ti-arrows-exchange',
        ],
        'accounts_receivable' => [
            'name' => 'Accounts Receivable',
            'view' => 'dashboard.widgets.accounts-receivable',
            'icon' => 'ti ti-coin',
        ],
        'accounts_payable' => [
            'name' => 'Accounts Payable',
            'view' => 'dashboard.widgets.accounts-payable',
            'icon' => 'ti ti-credit-card',
        ],
        'sales_trend' => [
            'name' => 'Sales',
            'view' => 'dashboard.widgets.sales-trend',
            'icon' => 'ti ti-trending-up',
        ],
    ],
];
