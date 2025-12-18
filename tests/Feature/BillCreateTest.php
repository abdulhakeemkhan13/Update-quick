<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\User;
use App\Models\Vender;
use App\Models\ProductService;
use App\Models\Tax;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;

class BillCreateTest extends TestCase
{
    // use RefreshDatabase; // Commented out to avoid wiping existing DB if not configured for testing

    public function test_can_create_bill_with_qbo_fields()
    {
        $user = User::where('type', 'company')->first();
        if(!$user) {
            $this->markTestSkipped('No company user found.');
        }
        
        $this->actingAs($user);
        
        // Assign permission if using Spatie Permission
        // Assuming the user model uses HasRoles trait
        // We might need to create the permission first if it doesn't exist in test DB
        // But since we are using existing DB (no RefreshDatabase), we assume it exists or we mock it.
        // However, the controller checks $user->can('create bill').
        // If the user is 'company', they usually have all permissions or we need to give it.
        // Let's try to mock the can method or just assign it.
        
        // Since we can't easily mock the user instance returned by Auth::user() inside the controller without more complex setup,
        // we will try to assign the permission if the package is installed.
        
        try {
            $permission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'create bill']);
            $user->givePermissionTo($permission);
        } catch (\Exception $e) {
            // If permission tables don't exist or other issue, we might need another way.
            // But for now let's hope this works.
        }

        $vendor = Vender::create([
            'name' => 'Test Vendor',
            'email' => 'vendor@test.com',
            'contact' => '1234567890',
            'created_by' => $user->creatorId(),
            'billing_name' => 'Test Vendor',
            'billing_country' => 'US',
            'billing_state' => 'NY',
            'billing_city' => 'New York',
            'billing_phone' => '1234567890',
            'billing_zip' => '10001',
            'billing_address' => '123 Test St',
        ]);

        $product = ProductService::create([
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'sale_price' => 100,
            'purchase_price' => 50,
            'tax_id' => 0,
            'category_id' => 0,
            'unit_id' => 0,
            'type' => 'product',
            'created_by' => $user->creatorId(),
        ]);

        \Config::set('app.url', 'http://localhost');
        
        $response = $this->post('http://localhost/bill', [
            'vender_id' => $vendor->id,
            'bill_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'bill_number' => 'BILL-TEST-001',
            'terms' => 'Net 30',
            'currency' => 'USD',
            'exchange_rate' => 1.0,
            'items' => [
                [
                    'item' => $product->id,
                    'quantity' => 2,
                    'price' => 50,
                    'amount' => 100,
                    'description' => 'Test Item',
                ]
            ],
            'subtotal' => 100,
            'total' => 100,
            'notes' => 'Test Memo',
            'recurring' => 'on',
            'frequency' => 'monthly',
            'next_date' => date('Y-m-d', strtotime('+1 month')),
        ]);

        $response->assertRedirect();
        if (session('error')) {
            dump(session('error'));
        }
        if (session('errors')) {
            dump(session('errors')->all());
        }
        $response->assertSessionHas('success');

        $bill = Bill::latest()->first();
        $this->assertEquals($vendor->id, $bill->vender_id);
        $this->assertEquals('Net 30', $bill->terms);
        $this->assertEquals(100, $bill->total);
        
        $this->assertDatabaseHas('bill_products', [
            'bill_id' => $bill->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('recurring_bills', [
            'bill_id' => $bill->id,
            'frequency' => 'monthly',
        ]);
        
        // Clean up
        $bill->delete();
        $vendor->delete();
        $product->delete();
    }
}
