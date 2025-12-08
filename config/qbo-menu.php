<?php

return [
    /*
    |--------------------------------------------------------------------------
    | QBO Menu Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the default menu items for the QBO-style sidebar.
    | Items can be customized per-user via the database.
    |
    */

    'items' => [
        // Main navigation
        [
            'key' => 'home',
            'label' => 'Home',
            'route' => 'dashboard',
            'icon' => 'ti ti-home',
            'color' => '#1E88E5',
            'type' => 'menu',
            'position' => 0,
            'is_visible' => true,
        ],
        [
            'key' => 'feed',
            'label' => 'Feed',
            'route' => null,
            'icon' => 'ti ti-sparkles',
            'color' => '#F9A825',
            'type' => 'menu',
            'position' => 1,
            'is_visible' => true,
        ],
        [
            'key' => 'reports',
            'label' => 'Reports',
            'route' => null,
            'icon' => 'ti ti-chart-bar',
            'color' => '#43A047',
            'type' => 'menu',
            'position' => 2,
            'is_visible' => true,
        ],
        
        // Apps (can be pinned)
        [
            'key' => 'accounting',
            'label' => 'Accounting',
            'route' => 'dashboard',
            'icon' => 'ti ti-calculator',
            'color' => 'linear-gradient(135deg, #1E88E5, #1565C0)',
            'type' => 'menu',
            'position' => 10,
            'is_visible' => true,
            'pinnable' => true,
        ],
        [
            'key' => 'expenses',
            'label' => 'Expenses & Pay Bills',
            'route' => null,
            'icon' => 'ti ti-receipt-2',
            'color' => 'linear-gradient(135deg, #43A047, #2E7D32)',
            'type' => 'menu',
            'position' => 11,
            'is_visible' => true,
            'pinnable' => true,
        ],
        [
            'key' => 'sales',
            'label' => 'Sales & Get Paid',
            'route' => null,
            'icon' => 'ti ti-currency-dollar',
            'color' => 'linear-gradient(135deg, #00897B, #00695C)',
            'type' => 'menu',
            'position' => 12,
            'is_visible' => true,
            'pinnable' => true,
        ],
        [
            'key' => 'customers',
            'label' => 'Customers',
            'route' => 'customer.index',
            'icon' => 'ti ti-users',
            'color' => 'linear-gradient(135deg, #00ACC1, #00838F)',
            'type' => 'menu',
            'position' => 13,
            'is_visible' => true,
            'pinnable' => true,
        ],
        [
            'key' => 'team',
            'label' => 'Team',
            'route' => 'employee.index',
            'icon' => 'ti ti-briefcase',
            'color' => 'linear-gradient(135deg, #5E35B1, #4527A0)',
            'type' => 'menu',
            'position' => 14,
            'is_visible' => true,
            'pinnable' => true,
        ],
        [
            'key' => 'time',
            'label' => 'Time',
            'route' => null,
            'icon' => 'ti ti-clock',
            'color' => 'linear-gradient(135deg, #3949AB, #283593)',
            'type' => 'menu',
            'position' => 15,
            'is_visible' => true,
            'pinnable' => true,
        ],
        [
            'key' => 'inventory',
            'label' => 'Inventory',
            'route' => 'productservice.index',
            'icon' => 'ti ti-archive',
            'color' => 'linear-gradient(135deg, #039BE5, #0277BD)',
            'type' => 'menu',
            'position' => 16,
            'is_visible' => true,
            'pinnable' => true,
        ],
        [
            'key' => 'sales_tax',
            'label' => 'Sales Tax',
            'route' => 'taxes.index',
            'icon' => 'ti ti-receipt-tax',
            'color' => 'linear-gradient(135deg, #E53935, #C62828)',
            'type' => 'menu',
            'position' => 17,
            'is_visible' => true,
            'pinnable' => true,
        ],
    ],

    // Create menu items
    'create_items' => [
        [
            'key' => 'create_invoice',
            'label' => 'Invoice',
            'route' => 'invoice.create',
            'icon' => 'ti ti-file-invoice',
        ],
        [
            'key' => 'create_expense',
            'label' => 'Expense',
            'route' => 'expense.create',
            'icon' => 'ti ti-receipt',
        ],
        [
            'key' => 'create_bill',
            'label' => 'Bill',
            'route' => 'bill.create',
            'icon' => 'ti ti-file-text',
        ],
        [
            'key' => 'create_customer',
            'label' => 'Customer',
            'route' => 'customer.create',
            'icon' => 'ti ti-user-plus',
        ],
        [
            'key' => 'create_vendor',
            'label' => 'Vendor',
            'route' => 'vender.create',
            'icon' => 'ti ti-building',
        ],
        [
            'key' => 'create_product',
            'label' => 'Product/Service',
            'route' => 'productservice.create',
            'icon' => 'ti ti-package',
        ],
    ],
];
