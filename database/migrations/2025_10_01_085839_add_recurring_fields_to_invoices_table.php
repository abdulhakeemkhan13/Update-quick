<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // master/series flags + schedule
            $table->boolean('is_recurring')->default(false)->after('status');
            $table->enum('recurring_repeat', ['monthly', 'quarterly', '6months', 'yearly'])->nullable()->after('is_recurring');
            $table->unsignedSmallInteger('recurring_every_n')->default(1)->after('recurring_repeat'); // e.g., every 1 month (or every 3 months, etc.)

            $table->enum('recurring_end_type', ['never','by'])->default('never')->after('recurring_every_n');
            $table->date('recurring_start_date')->nullable()->after('recurring_end_type');
            $table->date('recurring_end_date')->nullable()->after('recurring_start_date');

            // next run
            $table->dateTime('next_run_at')->nullable()->after('recurring_end_date');

            // link: children invoices point to the master
            $table->unsignedBigInteger('recurring_parent_id')->nullable()->index()->after('next_run_at');
            $table->foreign('recurring_parent_id')->references('id')->on('invoices')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['recurring_parent_id']);
            $table->dropColumn([
                'is_recurring',
                'recurring_repeat',
                'recurring_every_n',
                'recurring_end_type',
                'recurring_start_date',
                'recurring_end_date',
                'next_run_at',
                'recurring_parent_id',
            ]);
        });
    }
};
