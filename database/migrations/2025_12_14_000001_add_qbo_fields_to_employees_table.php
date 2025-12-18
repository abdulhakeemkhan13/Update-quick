<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds QBO-style employee fields to the existing employees table.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Personal Info - QBO Style
            $table->string('title', 50)->nullable()->after('name'); // Honorific (Mr., Mrs., etc.)
            $table->string('first_name', 100)->nullable()->after('title');
            $table->string('middle_initial', 1)->nullable()->after('first_name');
            $table->string('last_name', 100)->nullable()->after('middle_initial');
            $table->string('preferred_first_name', 100)->nullable()->after('last_name');
            $table->string('display_name', 200)->nullable()->after('preferred_first_name');
            
            // Contact Info - Extended
            $table->string('home_phone', 30)->nullable()->after('phone');
            $table->string('home_phone_ext', 10)->nullable()->after('home_phone');
            $table->string('work_phone', 30)->nullable()->after('home_phone_ext');
            $table->string('work_phone_ext', 10)->nullable()->after('work_phone');
            $table->string('mobile_phone', 30)->nullable()->after('work_phone_ext');
            
            // Address - Extended
            $table->string('city', 100)->nullable()->after('address');
            $table->string('state', 50)->nullable()->after('city');
            $table->string('zip', 20)->nullable()->after('state');
            $table->boolean('mailing_address_same')->default(true)->after('zip');
            
            // Personal Details
            $table->date('birth_date')->nullable()->after('mailing_address_same');
            $table->string('ssn', 100)->nullable()->after('gender'); // Will be encrypted
            
            // Employment Status
            $table->string('status', 20)->default('Active')->after('is_active'); // Active/Inactive
            $table->date('hire_date')->nullable()->after('status');
            $table->unsignedBigInteger('manager_id')->nullable()->after('hire_date');
            $table->string('department_name', 100)->nullable()->after('manager_id'); // QBO style department
            $table->string('job_title', 100)->nullable()->after('department_name');
            $table->string('name_on_checks', 200)->nullable()->after('job_title');
            $table->decimal('billing_rate', 10, 2)->nullable()->after('name_on_checks');
            $table->boolean('billable_by_default')->default(false)->after('billing_rate');
            
            // Emergency Contact
            $table->string('emergency_first_name', 100)->nullable()->after('billable_by_default');
            $table->string('emergency_last_name', 100)->nullable()->after('emergency_first_name');
            $table->string('emergency_relationship', 50)->nullable()->after('emergency_last_name');
            $table->string('emergency_phone', 30)->nullable()->after('emergency_relationship');
            $table->string('emergency_email', 255)->nullable()->after('emergency_phone');
            
            // Add index for manager relationship
            $table->foreign('manager_id')->references('id')->on('employees')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
            
            $table->dropColumn([
                'title',
                'first_name',
                'middle_initial',
                'last_name',
                'preferred_first_name',
                'display_name',
                'home_phone',
                'home_phone_ext',
                'work_phone',
                'work_phone_ext',
                'mobile_phone',
                'city',
                'state',
                'zip',
                'mailing_address_same',
                'birth_date',
                'ssn',
                'status',
                'hire_date',
                'manager_id',
                'department_name',
                'job_title',
                'name_on_checks',
                'billing_rate',
                'billable_by_default',
                'emergency_first_name',
                'emergency_last_name',
                'emergency_relationship',
                'emergency_phone',
                'emergency_email',
            ]);
        });
    }
};
