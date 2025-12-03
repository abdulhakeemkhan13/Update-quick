<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoice_products', function (Blueprint $table) {
            $table->unsignedBigInteger('estimate_id')->nullable()->after('amount');
            $table->string('line_type', 50)->nullable()->after('estimate_id');
            
            // Add index for faster lookups
            $table->index('estimate_id');
            $table->index('line_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoice_products', function (Blueprint $table) {
            $table->dropIndex(['estimate_id']);
            $table->dropIndex(['line_type']);
            $table->dropColumn(['estimate_id', 'line_type']);
        });
    }
};

