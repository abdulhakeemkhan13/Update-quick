<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePurchasesTableToPurchaseOrdersSchema extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->date('expected_date')->nullable()->after('purchase_date');
            $table->text('ship_to_address')->nullable()->after('expected_date');
            $table->string('ship_via')->nullable()->after('ship_to_address');
            $table->string('ref_no')->nullable()->after('ship_via');
            $table->string('ship_to')->nullable()->after('ref_no');
            $table->string('mailing_address')->nullable()->after('ship_to');
            $table->string('terms')->nullable()->after('mailing_address');
            $table->text('notes')->nullable()->after('terms');
            $table->text('vendor_message')->nullable()->after('notes');
            $table->string('type')->default('Purchase Order')->after('status');

            // Financial fields
            $table->decimal('subtotal', 16, 2)->default(0)->after('type');
            $table->decimal('tax_total', 16, 2)->default(0)->after('subtotal');
            $table->decimal('shipping', 16, 2)->default(0)->after('tax_total');
            $table->decimal('total', 16, 2)->default(0)->after('shipping');
            $table->integer('txn_id')->nullable()->after('total');
            $table->integer('txn_type')->nullable()->after('txn_id');

            $table->integer('category_id')->nullable()->change();
            $table->integer('warehouse_id')->nullable()->change();
            // Add new ownership field
            $table->integer('owned_by')->default(0)->after('created_by');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn([
                'expected_date',
                'ship_to_address',
                'terms',
                'notes',
                'vendor_message',
                'type',
                'subtotal',
                'tax_total',
                'shipping',
                'total',
                'bill_id',
                'owned_by',
            ]);
        });
    }
}
