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

        // Schema::table('users', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_at');
        // });
        // Schema::table('roles', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('pipelines', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('leads', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('deals', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('form_builders', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('contract_types', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('contracts', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('projects', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('lead_stages', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('sources', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('labels', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('stages', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('bugs', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('project_tasks', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('task_stages', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('product_service_categories', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('product_services', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('employees', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('departments', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('designations', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('leave_types', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('leaves', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('payslip_types', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('allowance_options', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('allowances', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('loan_options', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('loans', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('deduction_options', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('goal_types', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('goals', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('training_types', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('trainings', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('awards', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('award_types', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('terminations', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('termination_types', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('job_categories', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('jobs', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('performance_types', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('competencies', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('branches', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('documents', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('job_stages', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('company_policies', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('ducument_uploads', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('assets', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('meetings', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('employee_documents', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('resignations', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('pay_slips', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('attendance_employees', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('indicators', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('appraisals', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('goal_trackings', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('trainers', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('generate_offer_letters', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('job_applications', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('job_application_notes', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('job_application_notes', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('job_on_boards', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('job_stages', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('interview_schedules', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('transfers', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('travels', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('promotions', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('complaints', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('warnings', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('announcements', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('announcement_employees', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('holidays', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('customers', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('proposals', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('invoices', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('revenues', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('product_service_units', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('taxes', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('venders', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('bills', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('expenses', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('payments', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });
        // Schema::table('transactions', function (Blueprint $table) {
        //     $table->integer('owned_by')->nullable()->after('created_by');
        // });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::table('users', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('roles', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('pipelines', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('leads', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('deals', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('form_builders', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('contracts', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('contract_types', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('projects', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('lead_stages', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('sources', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('labels', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('stages', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('bugs', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('project_tasks', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('task_stages', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('product_service_categories', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('product_services', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('employees', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('departments', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('designations', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('leaves', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('leave_types', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('payslip_types', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('allowance_options', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('allowances', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('loan_options', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('loans', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('deduction_options', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('goal_types', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('goals', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('training_types', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('trainings', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('awards', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('award_types', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('terminations', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('termination_types', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('job_categories', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('jobs', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('performance_types', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('competencies', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('branches', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('documents', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('job_stages', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('company_policies', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('ducument_uploadsa', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('meetings', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('assets', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('employee_documents', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('resignations', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('pay_slips', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('attendance_employees', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('indicators', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('appraisals', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('goal_trackings', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('trainers', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('generate_offer_letters', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('job_applications', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('job_application_notes', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('job_on_boards', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('interview_schedules', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('transfers', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('travels', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('promotions', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('complaints', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('warnings', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('announcement_employees', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('announcements', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('holidays', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('customers', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('proposals', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('invoices', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('revenues', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('product_service_units', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('taxes', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('venders', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('bills', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('expenses', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('payments', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
        // Schema::table('transactions', function (Blueprint $table) {
        //     $table->dropColumn('owned_by');
        // });
    }
};
