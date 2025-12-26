<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryAdjustmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('adjustment_id')->comment('QuickBooks InventoryAdjustment ID');
            $table->date('txn_date');
            $table->string('ref_number')->nullable();
            $table->unsignedBigInteger('adjustment_account_id')->nullable()->comment('Chart of Account ID');
            $table->text('private_note')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->integer('created_by')->default(0);
            $table->integer('owned_by')->nullable();
            $table->timestamps();
            
            $table->index('adjustment_id');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventory_adjustments');
    }
}
