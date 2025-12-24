<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds separate mailing address fields for when mailing address differs from physical address.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('mailing_address', 500)->nullable()->after('mailing_address_same');
            $table->string('mailing_city', 100)->nullable()->after('mailing_address');
            $table->string('mailing_state', 50)->nullable()->after('mailing_city');
            $table->string('mailing_zip', 20)->nullable()->after('mailing_state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'mailing_address',
                'mailing_city',
                'mailing_state',
                'mailing_zip',
            ]);
        });
    }
};
