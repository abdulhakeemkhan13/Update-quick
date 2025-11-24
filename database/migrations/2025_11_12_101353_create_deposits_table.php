<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('deposits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('deposit_id')->nullable()->index()->comment('QuickBooks Deposit Id');
            $table->string('doc_number')->nullable();
            $table->date('txn_date')->nullable();
            $table->decimal('total_amt', 15, 2)->default(0);
            $table->text('private_note')->nullable();
            $table->string('currency')->nullable();

            // Foreign keys / references
            $table->unsignedBigInteger('bank_id')->nullable(); // from banks table
            $table->unsignedBigInteger('customer_id')->nullable(); // primary/first customer for the deposit
            $table->unsignedBigInteger('chart_account_id')->nullable(); // maybe the primary account for deposit
            $table->unsignedBigInteger('other_account_id')->nullable(); // receivable/expense account

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deposits');
    }
};
