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
        Schema::create('deposit_lines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('deposit_id')->index();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('detail_type')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('chart_account_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('check_num')->nullable();
            $table->json('linked_txns')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deposit_lines');
    }
};
