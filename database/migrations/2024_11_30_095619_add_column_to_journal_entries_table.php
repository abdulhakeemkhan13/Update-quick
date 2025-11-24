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
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->text('voucher_type',50)->default('JV')->after('journal_id');
            $table->unsignedInteger('reference_id')->nullable()->after('voucher_type');
            $table->unsignedInteger('prod_id')->nullable()->after('reference_id');
            $table->text('category',50)->nullable()->after('prod_id');
        });
        Schema::table('journal_items', function (Blueprint $table) {
            $table->unsignedInteger('product_id')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropColumn('voucher_type');
            $table->dropColumn('reference_id');
            $table->dropColumn('prod_id');
            $table->dropColumn('category');
        });
        Schema::table('journal_items', function (Blueprint $table) {
            $table->dropColumn('product_id');
        });
    }
};
