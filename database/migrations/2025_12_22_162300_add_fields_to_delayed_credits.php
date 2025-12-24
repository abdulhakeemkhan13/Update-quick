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
        // Add missing columns to delayed_credits table
        Schema::table('delayed_credits', function (Blueprint $table) {
            if (!Schema::hasColumn('delayed_credits', 'memo')) {
                $table->text('memo')->nullable()->after('private_note');
            }
            if (!Schema::hasColumn('delayed_credits', 'attachments')) {
                $table->json('attachments')->nullable()->after('memo');
            }
        });

        // Add tax column to delayed_credit_lines table
        Schema::table('delayed_credit_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('delayed_credit_lines', 'tax')) {
                $table->boolean('tax')->default(false)->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delayed_credits', function (Blueprint $table) {
            if (Schema::hasColumn('delayed_credits', 'memo')) {
                $table->dropColumn('memo');
            }
            if (Schema::hasColumn('delayed_credits', 'attachments')) {
                $table->dropColumn('attachments');
            }
        });

        Schema::table('delayed_credit_lines', function (Blueprint $table) {
            if (Schema::hasColumn('delayed_credit_lines', 'tax')) {
                $table->dropColumn('tax');
            }
        });
    }
};
