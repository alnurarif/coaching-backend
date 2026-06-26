<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE batches MODIFY COLUMN status ENUM('active', 'inactive', 'completed') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        DB::statement("UPDATE batches SET status = 'inactive' WHERE status = 'completed'");
        DB::statement("ALTER TABLE batches MODIFY COLUMN status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'");
    }
};
