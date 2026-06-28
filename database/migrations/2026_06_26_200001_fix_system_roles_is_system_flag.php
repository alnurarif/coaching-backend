<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // System roles were seeded with is_system=0 because the column
        // was added after the initial seed. Mark all roles with no tenant as system.
        DB::table('roles')
            ->whereNull('tenant_id')
            ->update(['is_system' => true]);
    }

    public function down(): void
    {
        DB::table('roles')
            ->whereNull('tenant_id')
            ->update(['is_system' => false]);
    }
};
