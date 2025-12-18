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
        Schema::table('bill_accounts', function (Blueprint $table) {
            $table->timestamp('invoiced_at')->nullable()->after('status');
            $table->unsignedBigInteger('invoice_id')->nullable()->after('invoiced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bill_accounts', function (Blueprint $table) {
            $table->dropColumn(['invoiced_at', 'invoice_id']);
        });
    }
};
