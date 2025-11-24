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
        Schema::create('workflow_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('workflow_id');
            $table->unsignedInteger('level_id');
            $table->string('node_id');
            $table->string('node_actual_id');
            $table->string('type')->nullable();
            $table->json('inputs')->nullable();
            $table->json('outputs')->nullable();
            $table->json('assigned_users')->nullable();
            $table->json('applied_conditions')->nullable();
            $table->boolean('status')->default(true);
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
        Schema::dropIfExists('workflow_actions');
    }
};
