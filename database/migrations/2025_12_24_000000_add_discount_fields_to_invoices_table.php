<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDiscountFieldsToInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'discount_type')) {
                $table->string('discount_type')->nullable()->after('total_discount');
            }
            if (!Schema::hasColumn('invoices', 'discount_value')) {
                $table->decimal('discount_value', 15, 2)->nullable()->after('discount_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'discount_type')) {
                $table->dropColumn('discount_type');
            }
            if (Schema::hasColumn('invoices', 'discount_value')) {
                $table->dropColumn('discount_value');
            }
        });
    }
}
