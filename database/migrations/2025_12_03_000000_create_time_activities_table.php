<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('time_activities', function (Blueprint $table) {
            $table->id();
            $table->string('user_type')->nullable(); // employee, vendor
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('customer_id')->nullable(); // customer or project
            $table->unsignedBigInteger('service_id')->nullable(); // product_service_id
            $table->date('date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('duration')->nullable(); // Store as string "HH:MM" or similar
            $table->string('break_duration')->nullable();
            $table->boolean('billable')->default(false);
            $table->decimal('rate', 15, 2)->nullable();
            $table->boolean('taxable')->default(false);
            $table->text('notes')->nullable();
            $table->integer('created_by')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('time_activities');
    }
};
