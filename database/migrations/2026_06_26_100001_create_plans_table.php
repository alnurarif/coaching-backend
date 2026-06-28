<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedInteger('students_limit')->nullable();
            $table->unsignedInteger('branches_limit')->nullable();
            $table->unsignedInteger('staff_limit')->nullable();
            $table->enum('reports_level', ['basic', 'full', 'advanced'])->default('basic');
            $table->boolean('can_export')->default(false);
            $table->enum('support_level', ['community', 'email', 'priority', 'dedicated'])->default('community');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
