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
        // Add status column to vendor_credits table
        Schema::table('vendor_credits', function (Blueprint $table) {
            $table->string('status')->default('Open')->after('payment_id')
                  ->comment('Status: Open, Partially Paid, Paid');
        });

        // Create vendor_credit_payments table to track payments applied to vendor credits
        Schema::create('vendor_credit_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_credit_id')->comment('Local vendor_credits.id');
            $table->string('vendor_credit_txn_id')->nullable()->comment('QB VendorCredit TxnId');
            $table->string('bill_payment_txn_id')->nullable()->comment('QB BillPayment TxnId that includes this vendor credit');
            $table->unsignedBigInteger('bill_payment_id')->nullable()->comment('Local bill_payments.id if exists');
            $table->decimal('amount', 15, 2)->default(0.00)->comment('Amount of vendor credit applied in this payment');
            $table->date('date')->nullable()->comment('Payment date');
            $table->text('description')->nullable();
            $table->integer('created_by')->default(0);
            $table->integer('owned_by')->default(0);
            $table->timestamps();

            $table->index('vendor_credit_id');
            $table->index('vendor_credit_txn_id');
            $table->index('bill_payment_txn_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vendor_credit_payments');

        Schema::table('vendor_credits', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
