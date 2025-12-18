<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('delayed_charges', function (Blueprint $table) {
            $table->id();
            $table->string('charge_id')->unique(); // QBO Charge ID
            $table->unsignedBigInteger('customer_id');
            $table->date('date');
            $table->decimal('amount', 15, 2)->default(0.00);
            $table->text('description')->nullable();
            $table->integer('is_invoiced')->default(0); // 1 if linked to invoice
            $table->integer('created_by')->nullable();
            $table->integer('owned_by')->nullable();
            $table->timestamps();
        });
        Schema::create('delayed_charge_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delayed_charge_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->decimal('quantity', 15, 2)->default(1);
            $table->decimal('rate', 15, 2)->default(0.00);
            $table->decimal('amount', 15, 2)->default(0.00);
            $table->text('description')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('owned_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delayed_charges');
        Schema::dropIfExists('delayed_charge_lines');
    }
};
