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
        Schema::create('customer_types', function (Blueprint $table) {
            $table->id();
            $table->string('qb_type_id')->unique();   // QBO CustomerTypeRef.value
            $table->string('name');                   // Type name from QBO
            $table->timestamps();
        });

        // Add type_id column in customers table
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('type_id')->nullable()->after('qb_balance');
            $table->foreign('type_id')->references('id')->on('customer_types')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['type_id']);
            $table->dropColumn('type_id');
        });

        Schema::dropIfExists('customer_types');
    }
};
