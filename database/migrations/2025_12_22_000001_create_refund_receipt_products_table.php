<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefundReceiptProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('refund_receipt_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('refund_receipt_id');
            $table->integer('product_id')->nullable();
            $table->integer('quantity');
            $table->string('tax', '50')->nullable();
            $table->float('discount')->default('0.00');
            $table->decimal('price', 16, 2)->default('0.0');
            $table->text('description')->nullable();
            $table->boolean('taxable')->default(0);
            $table->decimal('item_tax_price', 15, 2)->default(0.00);
            $table->decimal('item_tax_rate', 15, 2)->default(0.00);
            $table->decimal('amount', 15, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('refund_receipt_products');
    }
}
