<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdatePurchaseProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchase_products', function (Blueprint $table) {
            $table->decimal('line_total', 16, 2)->default(0)->after('price');
            $table->integer('tax_rate_id')->nullable()->after('tax');
            $table->integer('account_id')->nullable()->after('tax_rate_id');
            $table->tinyInteger('billable')->default(0)->after('account_id');
            $table->integer('customer_id')->nullable()->after('billable');
            $table->integer('order')->default(0)->after('customer_id');
            $table->tinyInteger('is_closed')->default(0)->after('order');
            
        });
       
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchase_products', function (Blueprint $table) {
            $table->dropColumn([
                'line_total',
                'tax_rate_id',
                'account_id',
                'billable',
                'customer_id',
                'order',
                'is_closed',
            ]);
        });
    }
}
