<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates junction table for tracking which credits were applied to which bills
     */
    public function up()
    {
        if (!Schema::hasTable('bill_credit_applications')) {
            Schema::create('bill_credit_applications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bill_id');
                $table->unsignedBigInteger('vendor_credit_id');
                $table->decimal('amount_applied', 18, 2);
                $table->unsignedBigInteger('applied_by');
                $table->timestamps();
                
                $table->foreign('bill_id')->references('id')->on('bills')->onDelete('cascade');
                $table->foreign('vendor_credit_id')->references('id')->on('vendor_credits')->onDelete('cascade');
                $table->index('bill_id');
                $table->index('vendor_credit_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('bill_credit_applications');
    }
};
