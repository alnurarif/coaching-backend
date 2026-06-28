<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_payments', function (Blueprint $table) {
            // Add plain index on user_id first so the FK has a covering index
            // before we drop the compound unique that MySQL was using for it
            $table->index('user_id', 'salary_payments_user_id_index');
            $table->dropUnique('salary_payments_user_month_unique');
        });
    }

    public function down(): void
    {
        Schema::table('salary_payments', function (Blueprint $table) {
            $table->unique(['user_id', 'month'], 'salary_payments_user_month_unique');
            $table->dropIndex('salary_payments_user_id_index');
        });
    }
};
