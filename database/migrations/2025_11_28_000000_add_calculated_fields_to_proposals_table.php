<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCalculatedFieldsToProposalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->decimal('subtotal', 15, 2)->nullable()->after('created_by');
            $table->decimal('taxable_subtotal', 15, 2)->nullable()->after('subtotal');
            $table->decimal('total_discount', 15, 2)->nullable()->after('taxable_subtotal');
            $table->decimal('total_tax', 15, 2)->nullable()->after('total_discount');
            $table->decimal('sales_tax_amount', 15, 2)->nullable()->after('total_tax');
            $table->decimal('total_amount', 15, 2)->nullable()->after('sales_tax_amount');
            $table->string('logo')->nullable()->after('total_amount');
            $table->json('attachments')->nullable()->after('logo');
            $table->text('bill_to')->nullable()->after('attachments');
            $table->text('memo')->nullable()->after('bill_to');
            $table->text('terms')->nullable()->after('memo');
            $table->text('note')->nullable()->after('terms');
            $table->text('accepted_by')->nullable()->after('note');
            $table->date('accepted_at')->nullable()->after('accepted_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn([
                'subtotal',
                'taxable_subtotal',
                'total_discount',
                'total_tax',
                'sales_tax_amount',
                'total_amount',
                'logo',
                'attachments',
                'bill_to',
                'memo',
                'terms',
                'note',
                'accepted_by'
            ]);
        });
    }
}

