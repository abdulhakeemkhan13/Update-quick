<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreatedByToDepositsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deposits', function (Blueprint $table) {
            if (!Schema::hasColumn('deposits', 'created_by')) {
                $table->integer('created_by')->default(0)->after('other_account_id');
            }
            if (!Schema::hasColumn('deposits', 'owned_by')) {
                $table->integer('owned_by')->nullable()->after('created_by');
            }
            if (!Schema::hasColumn('deposits', 'cashback_account_id')) {
                $table->unsignedBigInteger('cashback_account_id')->nullable()->after('owned_by');
            }
            if (!Schema::hasColumn('deposits', 'cashback_amount')) {
                $table->decimal('cashback_amount', 15, 2)->nullable()->after('cashback_account_id');
            }
            if (!Schema::hasColumn('deposits', 'cashback_memo')) {
                $table->string('cashback_memo')->nullable()->after('cashback_amount');
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
        Schema::table('deposits', function (Blueprint $table) {
            if (Schema::hasColumn('deposits', 'created_by')) {
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('deposits', 'owned_by')) {
                $table->dropColumn('owned_by');
            }
            if (Schema::hasColumn('deposits', 'cashback_account_id')) {
                $table->dropColumn('cashback_account_id');
            }
            if (Schema::hasColumn('deposits', 'cashback_amount')) {
                $table->dropColumn('cashback_amount');
            }
            if (Schema::hasColumn('deposits', 'cashback_memo')) {
                $table->dropColumn('cashback_memo');
            }
        });
    }
}
