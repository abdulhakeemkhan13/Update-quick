<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds billable flag to bill_products table
     */
    public function up()
    {
        Schema::table('bill_products', function (Blueprint $table) {
            if (!Schema::hasColumn('bill_products', 'billable')) {
                $table->boolean('billable')->default(0)->after('price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('bill_products', function (Blueprint $table) {
            $table->dropColumn('billable');
        });
    }
};
