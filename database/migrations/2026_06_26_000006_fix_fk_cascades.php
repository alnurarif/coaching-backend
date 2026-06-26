<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // fee_collections.collected_by → nullOnDelete (deleting a user should not delete fee records)
        Schema::table('fee_collections', function (Blueprint $table) {
            $table->dropForeign(['collected_by']);
            $table->unsignedBigInteger('collected_by')->nullable()->change();
            $table->foreign('collected_by')->references('id')->on('users')->nullOnDelete();
        });

        // exams.created_by → nullOnDelete (deleting exam creator should not delete the exam)
        Schema::table('exams', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->unsignedBigInteger('created_by')->nullable()->change();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        // teacher_attendances.user_id → nullOnDelete (preserve attendance history when teacher is removed)
        Schema::table('teacher_attendances', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id', 'date']);
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->unique(['user_id', 'date']);
        });

        // salary_payments — add missing FK constraints
        Schema::table('salary_payments', function (Blueprint $table) {
            // tenant_id: cascade (if tenant deleted, all salary records go with it)
            $table->foreignId('tenant_id')->change()->constrained('tenants')->cascadeOnDelete();

            // user_id: already has FK (RESTRICT) → change to nullOnDelete
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            // paid_by: no FK yet → add as nullOnDelete
            $table->unsignedBigInteger('paid_by')->nullable()->change();
            $table->foreign('paid_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('salary_payments', function (Blueprint $table) {
            $table->dropForeign(['paid_by']);
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('paid_by')->nullable(false)->change();
        });

        Schema::table('teacher_attendances', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id', 'date']);
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'date']);
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->unsignedBigInteger('created_by')->nullable(false)->change();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('fee_collections', function (Blueprint $table) {
            $table->dropForeign(['collected_by']);
            $table->unsignedBigInteger('collected_by')->nullable(false)->change();
            $table->foreign('collected_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
