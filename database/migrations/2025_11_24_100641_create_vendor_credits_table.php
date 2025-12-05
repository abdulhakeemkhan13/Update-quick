<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates vendor_credits table for tracking credits that can be applied to bills
     */
    public function up()
    {
        if (!Schema::hasTable('vendor_credits')) {
            Schema::create('vendor_credits', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('vendor_id');
                $table->string('credit_number')->unique();
                $table->date('credit_date');
                $table->decimal('amount', 18, 2);
                $table->decimal('remaining_amount', 18, 2);
                $table->text('reason')->nullable();
                $table->string('status', 20)->default('available'); // available, applied, expired
                $table->unsignedBigInteger('created_by');
                $table->unsignedBigInteger('owned_by')->nullable();
                $table->timestamps();
                
                $table->foreign('vendor_id')->references('id')->on('venders')->onDelete('cascade');
                $table->index(['vendor_id', 'status']);
                $table->index('created_by');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('vendor_credits');
    }
};
