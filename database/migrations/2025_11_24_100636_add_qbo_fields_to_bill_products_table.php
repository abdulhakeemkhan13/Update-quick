<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('bill_products', function (Blueprint $table) {
            $table->integer('account_id')->nullable()->after('product_id');
            $table->integer('tax_rate_id')->nullable()->after('tax');
            $table->decimal('line_total', 15, 2)->default(0)->after('price');
            $table->integer('order')->default(0)->after('line_total');
            $table->integer('status')->default(0)->after('order');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bill_products', function (Blueprint $table) {
            $table->dropColumn(['account_id', 'tax_rate_id', 'line_total', 'order', 'status']);
        });
    }
};
