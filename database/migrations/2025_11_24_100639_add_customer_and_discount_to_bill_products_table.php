<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds customer_id per line item (new requirement beyond QBO)
     * Adds discount fields for line-level discounts
     * Adds class_id and project_id for better tracking
     */
    public function up()
    {
        Schema::table('bill_products', function (Blueprint $table) {
            // Customer per line item (NEW REQUIREMENT)
            if (!Schema::hasColumn('bill_products', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('product_id');
                $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            }
            
            // Line-level discount support
            if (!Schema::hasColumn('bill_products', 'discount')) {
                $table->decimal('discount', 15, 2)->default(0)->after('price');
            }
            if (!Schema::hasColumn('bill_products', 'discount_type')) {
                $table->string('discount_type', 10)->default('fixed')->after('discount'); // 'fixed' or 'percent'
            }
            
            // Class and Project tracking
            if (!Schema::hasColumn('bill_products', 'class_id')) {
                $table->unsignedBigInteger('class_id')->nullable()->after('account_id');
            }
            if (!Schema::hasColumn('bill_products', 'project_id')) {
                $table->unsignedBigInteger('project_id')->nullable()->after('class_id');
            }
            
            // Indexes for performance
            $table->index('customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('bill_products', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn([
                'customer_id',
                'discount',
                'discount_type',
                'class_id',
                'project_id'
            ]);
        });
    }
};
