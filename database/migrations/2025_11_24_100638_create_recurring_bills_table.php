<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('recurring_bills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bill_id')->nullable(); // Template bill
            $table->string('frequency'); // weekly, monthly, etc.
            $table->date('next_date');
            $table->date('end_date')->nullable();
            $table->boolean('active')->default(true);
            $table->json('template_fields')->nullable(); // Store dynamic fields
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
        Schema::dropIfExists('recurring_bills');
    }
};
