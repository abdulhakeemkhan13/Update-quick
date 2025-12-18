<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTaxableFieldsToInvoiceProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoice_products', function (Blueprint $table) {
            $table->boolean('taxable')->default(0)->after('description');
            $table->decimal('item_tax_price', 15, 2)->default(0.00)->after('taxable');
            $table->decimal('item_tax_rate', 15, 2)->default(0.00)->after('item_tax_price');
            $table->decimal('amount', 15, 2)->default(0.00)->after('item_tax_rate');
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
            $table->dropColumn([
                'taxable',
                'item_tax_price',
                'item_tax_rate',
                'amount'
            ]);
        });
    }
}