<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->unsignedBigInteger('voucher_id')->nullable()->after('status');
            // Optional FK if your journal_entries table uses `id` as PK:
            // $table->foreign('voucher_id')->references('id')->on('journal_entries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            // If you created the FK above, drop it first:
            // $table->dropForeign(['voucher_id']);
            $table->dropColumn('voucher_id');
        });
    }
};

