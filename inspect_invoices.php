<?php
// inspect_invoices.php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\Invoice;
use App\Models\InvoiceProduct;

$invoiceNumbers = [
    'INVO00039', // Shara
    'INVO00099', // Travis
    'INVO00049', // Paulsen
    'INVO00042', // Travis
    'INVO00093', // Dukes
    'INVO00103', // Geeta
];

$invoices = Invoice::whereIn('invoice_id', $invoiceNumbers)->get();

foreach ($invoices as $inv) {
    echo "Invoice #{$inv->invoice_id} ({$inv->id})\n";
    echo "Customer: " . ($inv->customer->name ?? 'Unknown') . "\n";
    
    $products = InvoiceProduct::where('invoice_id', $inv->id)->get();
    $subtotal = 0;
    $tax = 0;
    
    foreach ($products as $p) {
        $lineTotal = ($p->price * $p->quantity) - $p->discount;
        $lineTax = 0; // Simplified
        echo " - Product: {$p->product_id} Price: {$p->price} Qty: {$p->quantity} Disc: {$p->discount} Tax: {$p->tax}\n";
        $subtotal += $lineTotal;
    }
    echo "Current Subtotal (calced): $subtotal\n";
    echo "--------------------------\n";
}
