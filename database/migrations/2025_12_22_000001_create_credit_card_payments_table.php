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
        Schema::create('credit_card_payments', function (Blueprint $table) {
            $table->id();
            
            // Credit card account (liability account)
            $table->unsignedBigInteger('credit_card_account_id');
            
            // Bank account used for payment
            $table->unsignedBigInteger('bank_account_id');
            
            // Payee (optional) - usually the credit card company
            $table->unsignedBigInteger('payee_id')->nullable();
            $table->string('payee_type')->nullable(); // 'vendor', 'customer', etc.
            
            // Payment details
            $table->decimal('amount', 15, 2)->default(0);
            $table->date('payment_date');
            $table->string('reference')->nullable();
            $table->text('memo')->nullable();
            
            // Attachments (JSON array of file paths)
            $table->json('attachments')->nullable();
            
            // Status: 0=draft, 1=cleared, 2=reconciled
            $table->integer('status')->default(0);
            
            // Reconciliation
            $table->string('cleared_status')->default('uncleared'); // uncleared, cleared, reconciled
            
            // Multi-currency support
            $table->string('currency')->nullable();
            $table->decimal('exchange_rate', 15, 6)->default(1);
            
            // Ownership
            $table->integer('created_by')->default(0);
            $table->integer('owned_by')->default(0);
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('credit_card_account_id')->references('id')->on('chart_of_accounts')->onDelete('cascade');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('credit_card_payments');
    }
};
