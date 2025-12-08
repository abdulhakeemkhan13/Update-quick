<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('key');           // e.g. 'profit_loss', 'expenses', 'invoices'
            $table->integer('x')->default(0);
            $table->integer('y')->default(0);
            $table->integer('w')->default(3);
            $table->integer('h')->default(2);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'key']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_widgets');
    }
};
