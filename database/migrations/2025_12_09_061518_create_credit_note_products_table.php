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
        Schema::create('credit_note_products', function (Blueprint $table) {
            $table->id();
            $table->integer('credit_note_id');
            $table->integer('product_id');
            $table->decimal('quantity', 15, 2);
            $table->decimal('tax', 15, 2)->default(0.00);
            $table->decimal('discount', 15, 2)->default(0.00);
            $table->decimal('price', 15, 2);
            $table->text('description')->nullable();
            $table->timestamps();
        });
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->string('credit_note_id')->nullable()->after('id');
            $table->string('payment_id')->nullable()->after('credit_note_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_note_products');
    }
};
