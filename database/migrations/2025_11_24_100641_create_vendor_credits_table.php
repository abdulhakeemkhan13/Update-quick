<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Vendor Credits Header Table
        Schema::create('vendor_credits', function (Blueprint $table) {
            $table->id();
            $table->string('vendor_credit_id')->comment('QuickBooks ID'); 
            $table->integer('vender_id');
            $table->date('date');
            $table->double('amount', 15, 2)->default(0.00);
            $table->text('memo')->nullable();
            $table->integer('created_by')->default(0);
            $table->integer('owned_by')->default(0);
            $table->timestamps();
        });

        // 2. Vendor Credit Products (Lines for ItemBasedExpenseLineDetail)
        Schema::create('vendor_credit_products', function (Blueprint $table) {
            $table->id();
            $table->integer('vendor_credit_id');
            $table->integer('product_id');
            $table->double('quantity', 15, 2)->default(0.00);
            $table->double('price', 15, 2)->default(0.00);
            $table->text('description')->nullable();
            $table->double('tax', 15, 2)->default(0.00);
            $table->boolean('billable')->default(0);
            $table->integer('customer_id')->nullable();
            $table->timestamps();
        });

        // 3. Vendor Credit Accounts (Lines for AccountBasedExpenseLineDetail)
        Schema::create('vendor_credit_accounts', function (Blueprint $table) {
            $table->id();
            $table->integer('vendor_credit_id');
            $table->integer('chart_account_id');
            $table->double('price', 15, 2)->default(0.00);
            $table->text('description')->nullable();
            $table->double('tax', 15, 2)->default(0.00);
            $table->boolean('billable')->default(0);
            $table->integer('customer_id')->nullable();
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
        Schema::dropIfExists('vendor_credit_accounts');
        Schema::dropIfExists('vendor_credit_products');
        Schema::dropIfExists('vendor_credits');
    }
};