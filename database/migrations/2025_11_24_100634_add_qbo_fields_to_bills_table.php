<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->string('terms')->nullable()->after('due_date');
            $table->decimal('exchange_rate', 15, 4)->default(1.0)->after('terms');
            $table->decimal('subtotal', 15, 2)->default(0)->after('exchange_rate');
            $table->decimal('tax_total', 15, 2)->default(0)->after('subtotal');
            $table->decimal('shipping', 15, 2)->default(0)->after('tax_total');
            $table->decimal('adjustments', 15, 2)->default(0)->after('shipping');
            $table->decimal('total', 15, 2)->default(0)->after('adjustments');
            $table->decimal('paid_amount', 15, 2)->default(0)->after('total');
            $table->text('notes')->nullable()->after('paid_amount');
            $table->string('currency')->nullable()->after('notes');
            $table->string('ref_number')->nullable()->after('currency');


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn([
                'terms',
                'exchange_rate',
                'subtotal',
                'tax_total',
                'shipping',
                'adjustments',
                'total',
                'paid_amount',
                'notes'
            ]);
        });
    }
};
