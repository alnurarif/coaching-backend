<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') return;

        DB::statement("ALTER TABLE fee_collections MODIFY payment_method ENUM('cash','bkash','nagad','rocket','bank_transfer') NOT NULL DEFAULT 'cash'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') return;

        DB::statement("ALTER TABLE fee_collections MODIFY payment_method ENUM('cash','bkash','nagad','rocket','bank') NOT NULL DEFAULT 'cash'");
    }
};
