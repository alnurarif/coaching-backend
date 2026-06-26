<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();
        $branch = Branch::first();

        $owner = User::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'name'      => 'Center Owner',
            'email'     => 'owner@test.com',
            'phone'     => '01700000000',
            'password'  => bcrypt('password123'),
            'is_active' => true,
        ]);

        $owner->assignRole('owner');
    }
}
