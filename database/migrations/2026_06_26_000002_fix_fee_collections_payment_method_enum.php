<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Rename 'bank' → 'bank_transfer' to match expenses table and request validation
        DB::statement("ALTER TABLE fee_collections MODIFY payment_method ENUM('cash','bkash','nagad','rocket','bank_transfer') NOT NULL DEFAULT 'cash'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE fee_collections MODIFY payment_method ENUM('cash','bkash','nagad','rocket','bank') NOT NULL DEFAULT 'cash'");
    }
};
