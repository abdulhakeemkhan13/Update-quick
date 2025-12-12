<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('unapplied_payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('qb_payment_id')->nullable()->index();
            $table->string('reference')->nullable()->index();


            $table->unsignedBigInteger('vendor_id')->nullable()->index();
            $table->string('vendor_qb_id')->nullable()->index();


            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('applied_amount', 15, 2)->default(0);
            $table->decimal('unapplied_amount', 15, 2)->default(0);


            $table->date('txn_date')->nullable()->index();


            $table->unsignedBigInteger('account_id')->nullable()->index();
            $table->unsignedBigInteger('chart_account_id')->nullable()->index();


            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('owned_by')->nullable()->index();


            $table->json('linked_bill_txns')->nullable();
            $table->longText('raw')->nullable();


            $table->timestamps();


            $table->unique(['qb_payment_id', 'reference'], 'unapplied_qb_ref_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unapplied_payments');
    }
};
