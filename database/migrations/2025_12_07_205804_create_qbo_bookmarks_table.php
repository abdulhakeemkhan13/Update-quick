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
        Schema::create('qbo_bookmarks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('key');              // Unique identifier for the menu item
            $table->string('label');            // Display name
            $table->string('route')->nullable(); // Route name
            $table->string('icon')->nullable();  // Icon class (e.g., ti ti-home)
            $table->string('color')->nullable(); // Gradient/color for icon
            $table->enum('type', ['bookmark', 'pinned', 'menu'])->default('menu');
            $table->integer('position')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'key', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qbo_bookmarks');
    }
};
