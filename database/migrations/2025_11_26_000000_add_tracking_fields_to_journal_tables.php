<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // journal_entries
        Schema::table('journal_entries', function (Blueprint $table) {

            if (!Schema::hasColumn('journal_entries', 'owned_by')) {
                $table->integer('owned_by')->default(0)->after('created_by');
            }

            if (!Schema::hasColumn('journal_entries', 'status')) {
                $table->integer('status')->default(1)
                    ->after('owned_by')
                    ->comment('0=draft, 1=active, 2=cancelled');
            }

            if (!Schema::hasColumn('journal_entries', 'module')) {
                $table->string('module', 50)->nullable()
                    ->after('category')
                    ->comment('Source module: bill, vendor_credit, expense');
            }

            if (!Schema::hasColumn('journal_entries', 'source')) {
                $table->string('source', 100)->nullable()->after('module');
            }

        });

        // Add prefix index safely (utf8mb4 max 191 for InnoDB)
        DB::statement('CREATE INDEX journal_entries_reference_id_category_index 
                       ON journal_entries (reference_id, category(191))');

        // journal_items
        Schema::table('journal_items', function (Blueprint $table) {

            if (!Schema::hasColumn('journal_items', 'created_by')) {
                $table->integer('created_by')->default(0)->after('employee_id');
            }

            if (!Schema::hasColumn('journal_items', 'company_id')) {
                $table->integer('company_id')->nullable()->after('created_by');
            }

            if (!Schema::hasColumn('journal_items', 'prod_tax_id')) {
                $table->integer('prod_tax_id')->nullable()->after('product_ids');
            }
            if (!Schema::hasColumn('journal_items', 'created_user')) {
                $table->integer('created_user')->nullable()->after('created_by');
            }

            $table->index('created_by', 'journal_items_created_by_index');
            $table->index('company_id', 'journal_items_company_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropColumn(['owned_by', 'status', 'module', 'source']);
        });

        // Drop the custom prefix index
        DB::statement('DROP INDEX journal_entries_reference_id_category_index ON journal_entries');

        Schema::table('journal_items', function (Blueprint $table) {
            $table->dropIndex('journal_items_created_by_index');
            $table->dropIndex('journal_items_company_id_index');
            $table->dropColumn(['created_by', 'company_id', 'prod_tax_id']);
        });
    }
};
