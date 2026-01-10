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
        Schema::table('proposals', function (Blueprint $table) {
            if (!Schema::hasColumn('proposals', 'accepted_at')) {
                $table->date('accepted_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('proposals', 'accepted_date')) {
                $table->date('accepted_date')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            if (Schema::hasColumn('proposals', 'accepted_date')) {
                $table->dropColumn('accepted_date');
            }
        });
    }
};
