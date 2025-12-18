<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migration: create_delayed_credits_table
        Schema::create('delayed_credits', function (Blueprint $table) {
            $table->id();
            $table->string('credit_id')->unique(); // QBO CreditMemo ID
            $table->string('type')->default('CreditMemo'); // 'CreditMemo' or 'DelayedCredit'
            $table->unsignedBigInteger('customer_id');
            $table->date('date');
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->decimal('remaining_balance', 15, 2)->default(0.00);
            $table->text('private_note')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('owned_by')->nullable();
            $table->timestamps();
        });

        // Migration: create_delayed_credit_lines_table
        Schema::create('delayed_credit_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delayed_credit_id');
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
        Schema::dropIfExists('delayed_credits');
        Schema::dropIfExists('delayed_credit_lines');
    }
};
