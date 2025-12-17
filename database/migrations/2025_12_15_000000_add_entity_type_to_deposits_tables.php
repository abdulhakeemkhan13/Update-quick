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
        Schema::table('deposits', function (Blueprint $table) {
            $table->string('entity_type')->nullable()->after('customer_id');
        });

        Schema::table('deposit_lines', function (Blueprint $table) {
            $table->string('entity_type')->nullable()->after('customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropColumn('entity_type');
        });

        Schema::table('deposit_lines', function (Blueprint $table) {
            $table->dropColumn('entity_type');
        });
    }
};
