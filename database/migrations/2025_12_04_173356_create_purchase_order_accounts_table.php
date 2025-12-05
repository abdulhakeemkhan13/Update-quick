<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePurchaseOrderAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_order_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('ref_id'); // purchase_order_id
            $table->string('type')->default('Purchase Order'); // Type identifier
            $table->integer('chart_account_id')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 16, 2)->default(0);
            
            // Tax
            $table->tinyInteger('tax')->default(0); // 1 if taxable
            $table->integer('tax_rate_id')->nullable();
            
            // Billable tracking (for account-based expenses)
            $table->tinyInteger('billable')->default(0);
            $table->integer('customer_id')->nullable();
            
            // Quantity tracking for account-based items
            $table->decimal('quantity_ordered', 16, 2)->default(0);
            $table->decimal('quantity_received', 16, 2)->default(0);
            
            // Display order
            $table->integer('order')->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_order_accounts');
    }
}
