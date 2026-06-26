<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('exam_type_id')->nullable()->constrained('exam_types')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->date('exam_date');
            $table->decimal('total_marks', 8, 2);
            $table->decimal('passing_marks', 8, 2);
            $table->enum('status', ['draft', 'published', 'completed'])->default('draft');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'batch_id']);
            $table->index(['tenant_id', 'exam_date']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
