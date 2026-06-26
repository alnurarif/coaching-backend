<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('month', 7);              // YYYY-MM
            $table->decimal('base_salary', 15, 2);
            $table->decimal('bonus', 15, 2)->default(0);
            $table->decimal('deduction', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2);
            $table->date('payment_date');
            $table->string('payment_method');
            $table->string('receipt_no')->unique();
            $table->unsignedBigInteger('paid_by');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->index(['tenant_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_payments');
    }
};
