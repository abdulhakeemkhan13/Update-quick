<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds QBO-style fields to bill_accounts table:
     * - billable: whether this expense is billable to a customer
     * - customer_id: which customer to bill this to
     * - tax: whether tax applies to this account expense
     */
    public function up()
    {
        Schema::table('bill_accounts', function (Blueprint $table) {
            // Billable flag
            if (!Schema::hasColumn('bill_accounts', 'billable')) {
                $table->boolean('billable')->default(0)->after('price');
            }
            
            // Customer for billable items
            if (!Schema::hasColumn('bill_accounts', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('billable');
                $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            }
            
            // Tax flag (checkbox for whether tax applies)
            if (!Schema::hasColumn('bill_accounts', 'tax')) {
                $table->boolean('tax')->default(0)->after('customer_id');
            }
            if (!Schema::hasColumn('bill_accounts', 'order')) {
                $table->integer('order')->default(0)->after('tax');
            }
            if (!Schema::hasColumn('bill_accounts', 'status')) {
                $table->integer('status')->default(0)->after('order');
            }
            
            // Index for performance
            $table->index('customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('bill_accounts', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn([
                'billable',
                'customer_id',
                'tax',
                'order',
                'status'
            ]);
        });
    }
};
