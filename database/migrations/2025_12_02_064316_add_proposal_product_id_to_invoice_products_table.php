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
        Schema::table('invoice_products', function (Blueprint $table) {
            // Add proposal_product_id to track specific line items from estimates
            $table->unsignedBigInteger('proposal_product_id')->nullable()->after('line_type');
            $table->index('proposal_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_products', function (Blueprint $table) {
            $table->dropIndex(['proposal_product_id']);
            $table->dropColumn('proposal_product_id');
        });
    }
};
