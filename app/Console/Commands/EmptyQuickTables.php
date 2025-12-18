<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EmptyQuickTables extends Command
{
    protected $signature = 'db:empty-quick';
    protected $description = 'Truncate all creativesuite_quick related tables';

    public function handle()
    {
        $this->info('Starting to empty tables from creativesuite_quick...');

        // Your DB connection name (if not default)
        $connection = 'mysql'; // change if needed (e.g. 'creativesuite_quick')

        $tables = [
            // 'vendors',
            // 'customers',
            // 'invoices',
            // 'invoice_payments',
            // 'invoice_products',
            'bills',
            'bill_payments',
            'bill_accounts',
            'bill_products',
            'vendor_credits',
            'vendor_credit_accounts',
            'vendor_credit_products',
            'purchases',
            'purchase_order_accounts',
            'purchase_products',
            'purchase_payments',
            // 'journal_entries',
            // 'journal_items',
            // 'transaction_lines',
            // 'chart_of_accounts',
            // 'chart_of_account_parents',
            // 'chart_of_account_sub_types',
        ];

        // Disable foreign key checks
        Schema::connection($connection)->disableForeignKeyConstraints();

        foreach ($tables as $table) {
            try {
                DB::connection($connection)->table($table)->truncate();
                $this->line("✅ Emptied table: {$table}");
            } catch (\Exception $e) {
                $this->error("❌ Failed to truncate {$table}: " . $e->getMessage());
            }
        }

        // Enable foreign key checks
        Schema::connection($connection)->enableForeignKeyConstraints();

        $this->info('✅ All specified tables have been emptied.');
    }
}
