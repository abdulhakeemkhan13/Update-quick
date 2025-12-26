<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefundReceiptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('refund_receipts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('refund_receipt_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('customer_email')->nullable();
            $table->date('issue_date');
            $table->integer('category_id')->nullable();
            $table->text('ref_number')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('refund_from')->nullable();
            $table->string('location_of_sale')->nullable();
            $table->text('billing_address')->nullable();
            $table->integer('status')->default('0');
            $table->string('bill_to')->nullable();
            $table->string('ship_to')->nullable();
            $table->integer('created_by')->default('0');
            $table->integer('owned_by')->nullable();
            $table->decimal('subtotal', 15, 2)->nullable();
            $table->decimal('taxable_subtotal', 15, 2)->nullable();
            $table->string('discount_type')->nullable();
            $table->decimal('discount_value', 15, 2)->nullable();
            $table->decimal('total_discount', 15, 2)->nullable();
            $table->string('sales_tax_rate')->nullable();
            $table->decimal('total_tax', 15, 2)->nullable();
            $table->decimal('sales_tax_amount', 15, 2)->nullable();
            $table->decimal('total_amount', 15, 2)->nullable();
            $table->decimal('total_amount_refunded', 15, 2)->nullable();
            $table->string('logo')->nullable();
            $table->json('attachments')->nullable();
            $table->text('memo')->nullable(); // Message displayed on refund receipt
            $table->text('statement_memo')->nullable(); // Message displayed on statement
            $table->unsignedBigInteger('voucher_id')->nullable();
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
        Schema::dropIfExists('refund_receipts');
    }
}
