<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') return;

        DB::statement("ALTER TABLE batches MODIFY COLUMN status ENUM('active', 'inactive', 'completed') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') return;

        DB::statement("UPDATE batches SET status = 'inactive' WHERE status = 'completed'");
        DB::statement("ALTER TABLE batches MODIFY COLUMN status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'");
    }
};
