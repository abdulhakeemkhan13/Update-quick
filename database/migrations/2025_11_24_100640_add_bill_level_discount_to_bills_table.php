<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds bill-level discount and balance tracking
     */
    public function up()
    {
        Schema::table('bills', function (Blueprint $table) {
            if (!Schema::hasColumn('bills', 'discount')) {
                $table->decimal('discount', 15, 2)->default(0)->after('subtotal');
            }
            if (!Schema::hasColumn('bills', 'discount_type')) {
                $table->string('discount_type', 10)->default('fixed')->after('discount');
            }
            if (!Schema::hasColumn('bills', 'balance_due')) {
                $table->decimal('balance_due', 15, 2)->default(0)->after('paid_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn([
                'discount',
                'discount_type',
                'balance_due'
            ]);
        });
    }
};
