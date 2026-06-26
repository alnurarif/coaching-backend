<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('collected_by')->constrained('users')->cascadeOnDelete();
            $table->enum('fee_type', ['admission', 'monthly']);
            $table->string('month', 7)->nullable();
            $table->decimal('amount_due', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('scholarship_amount', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2);
            $table->date('payment_date');
            $table->enum('payment_method', ['cash', 'bkash', 'nagad', 'rocket', 'bank'])->default('cash');
            $table->string('receipt_no', 25)->unique();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'payment_date']);
            $table->index(['tenant_id', 'student_id']);
            $table->index(['tenant_id', 'month']);
            $table->index(['batch_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_collections');
    }
};
