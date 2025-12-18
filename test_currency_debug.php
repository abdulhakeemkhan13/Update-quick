<?php
// Load Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

// Fake login for context (User 1 is usually admin/owner)
$user = \App\Models\User::first();
if ($user) {
    \Auth::login($user);
    echo "User ID: " . $user->id . "\n";
    
    $symbol = $user->currencySymbol();
    echo "Currency Symbol: '{$symbol}'\n";
    echo "Symbol Hex: " . bin2hex($symbol) . "\n";

    $val0 = $user->priceFormat(0);
    echo "priceFormat(0): '{$val0}'\n";
    
    $val600 = $user->priceFormat(600);
    echo "priceFormat(600): '{$val600}'\n";

    echo "strpos(val0, symbol): " . (strpos($val0, $symbol) === false ? 'false' : strpos($val0, $symbol)) . "\n";
    echo "strpos(val600, symbol): " . (strpos($val600, $symbol) === false ? 'false' : strpos($val600, $symbol)) . "\n";
} else {
    echo "No user found.\n";
}
