<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE fee_collections
            MODIFY COLUMN fee_type
            ENUM('admission', 'monthly', 'exam', 'other') NOT NULL
        ");
    }

    public function down(): void
    {
        // Remove rows with the new values before shrinking the enum
        DB::statement("
            UPDATE fee_collections
            SET fee_type = 'monthly'
            WHERE fee_type IN ('exam', 'other')
        ");

        DB::statement("
            ALTER TABLE fee_collections
            MODIFY COLUMN fee_type
            ENUM('admission', 'monthly') NOT NULL
        ");
    }
};
