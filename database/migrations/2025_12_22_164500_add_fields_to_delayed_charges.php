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
        // Add missing columns to delayed_charges table
        Schema::table('delayed_charges', function (Blueprint $table) {
            if (!Schema::hasColumn('delayed_charges', 'total_amount')) {
                $table->decimal('total_amount', 15, 2)->default(0.00)->after('amount');
            }
            if (!Schema::hasColumn('delayed_charges', 'memo')) {
                $table->text('memo')->nullable()->after('description');
            }
            if (!Schema::hasColumn('delayed_charges', 'attachments')) {
                $table->json('attachments')->nullable()->after('memo');
            }
        });

        // Add tax column to delayed_charge_lines table
        Schema::table('delayed_charge_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('delayed_charge_lines', 'tax')) {
                $table->boolean('tax')->default(false)->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delayed_charges', function (Blueprint $table) {
            if (Schema::hasColumn('delayed_charges', 'total_amount')) {
                $table->dropColumn('total_amount');
            }
            if (Schema::hasColumn('delayed_charges', 'memo')) {
                $table->dropColumn('memo');
            }
            if (Schema::hasColumn('delayed_charges', 'attachments')) {
                $table->dropColumn('attachments');
            }
        });

        Schema::table('delayed_charge_lines', function (Blueprint $table) {
            if (Schema::hasColumn('delayed_charge_lines', 'tax')) {
                $table->dropColumn('tax');
            }
        });
    }
};
