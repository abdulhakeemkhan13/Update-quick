<?php

return [
    'api_base' => env('YODLEE_API_BASE', 'https://sandbox.api.yodlee.com/ysl'),
    'fastlink_url' => env('YODLEE_FASTLINK_URL', 'https://fl4.sandbox.yodlee.com/authenticate/restserver/fastlink'),
    'client_id' => env('YODLEE_CLIENT_ID'),
    'client_secret' => env('YODLEE_CLIENT_SECRET'),
    'admin_login_name' => env('YODLEE_ADMIN_LOGINNAME'),
    'api_version' => env('YODLEE_API_VERSION', '1.1'),
    'sandbox_user' => env('YODLEE_SANDBOX_USER', 'sbMemc8ef21052da091'), // Your test user
];