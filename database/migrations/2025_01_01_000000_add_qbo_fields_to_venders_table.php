<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('venders', function (Blueprint $table) {
            // NAME & CONTACT
            $table->string('company_name')->nullable()->after('vender_id');
            $table->string('title', 16)->nullable()->after('company_name');
            $table->string('first_name', 100)->nullable()->after('title');
            $table->string('middle_name', 100)->nullable()->after('first_name');
            $table->string('last_name', 100)->nullable()->after('middle_name');
            $table->string('suffix', 16)->nullable()->after('last_name');
            $table->string('mobile', 30)->nullable()->after('contact');
            $table->string('fax', 30)->nullable()->after('mobile');
            $table->string('other', 30)->nullable()->after('fax');
            $table->string('website', 1000)->nullable()->after('other');
            $table->string('print_on_check_name', 110)->nullable()->after('website');

            // BILLING ADDRESS EXTRA LINE
            $table->string('billing_address_2', 255)->nullable()->after('billing_address');

            // NOTES
            $table->text('notes')->nullable()->after('billing_zip');

            // BILL PAY ACH INFO
            $table->string('bank_account_number', 17)->nullable()->after('notes');
            $table->string('routing_number', 9)->nullable()->after('bank_account_number');

            // ADDITIONAL INFO – TAXES
            $table->string('business_id_no')->nullable()->after('routing_number');
            $table->boolean('track_payments_1099')->default(false)->after('business_id_no');

            // ADDITIONAL INFO – EXPENSE RATES
            $table->decimal('billing_rate', 15, 2)->nullable()->after('track_payments_1099');

            // ADDITIONAL INFO – PAYMENTS
            $table->string('terms')->nullable()->after('billing_rate');
            $table->string('account_no')->nullable()->after('terms');

            // ADDITIONAL INFO – ACCOUNTING
            $table->string('default_expense_category')->nullable()->after('account_no');

            // ADDITIONAL INFO – OPENING BALANCE
            $table->decimal('opening_balance', 15, 2)->nullable()->after('default_expense_category');
            $table->date('opening_balance_as_of')->nullable()->after('opening_balance');
        });
    }

    public function down()
    {
        Schema::table('venders', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'title',
                'first_name',
                'middle_name',
                'last_name',
                'suffix',
                'mobile',
                'fax',
                'other',
                'website',
                'print_on_check_name',
                'billing_address_2',
                'notes',
                'bank_account_number',
                'routing_number',
                'business_id_no',
                'track_payments_1099',
                'billing_rate',
                'terms',
                'account_no',
                'default_expense_category',
                'opening_balance',
                'opening_balance_as_of',
            ]);
        });
    }
};
