<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesReceiptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_receipts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sales_receipt_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('customer_email')->nullable();
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->date('send_date')->nullable();
            $table->integer('category_id')->nullable();
            $table->text('ref_number')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('deposit_to')->nullable();
            $table->string('location_of_sale')->nullable();
            $table->text('bill_to')->nullable();
            $table->integer('status')->default('0');
            $table->integer('shipping_display')->default('1');
            $table->integer('discount_apply')->default('0');
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
            $table->decimal('amount_received', 15, 2)->nullable();
            $table->decimal('balance_due', 15, 2)->nullable();
            $table->string('logo')->nullable();
            $table->json('attachments')->nullable();
            $table->text('memo')->nullable();
            $table->text('terms')->nullable();
            $table->text('note')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurring_repeat', ['monthly', 'quarterly', '6months', 'yearly'])->nullable();
            $table->unsignedSmallInteger('recurring_every_n')->default(1);
            $table->enum('recurring_end_type', ['never','by'])->default('never');
            $table->date('recurring_start_date')->nullable();
            $table->date('recurring_end_date')->nullable();
            $table->dateTime('next_run_at')->nullable();
            $table->unsignedBigInteger('recurring_parent_id')->nullable()->index();
            $table->foreign('recurring_parent_id')->references('id')->on('sales_receipts')->onDelete('cascade');
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
        Schema::dropIfExists('sales_receipts');
    }
}