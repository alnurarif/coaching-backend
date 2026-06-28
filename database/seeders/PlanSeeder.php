<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name'           => 'Free',
                'slug'           => 'free',
                'price'          => 0.00,
                'students_limit' => 30,
                'branches_limit' => 1,
                'staff_limit'    => 3,
                'reports_level'  => 'basic',
                'can_export'     => false,
                'support_level'  => 'community',
                'is_active'      => true,
                'sort_order'     => 1,
            ],
            [
                'name'           => 'Starter',
                'slug'           => 'starter',
                'price'          => 999.00,
                'students_limit' => 150,
                'branches_limit' => 1,
                'staff_limit'    => 10,
                'reports_level'  => 'full',
                'can_export'     => true,
                'support_level'  => 'email',
                'is_active'      => true,
                'sort_order'     => 2,
            ],
            [
                'name'           => 'Growth',
                'slug'           => 'growth',
                'price'          => 2499.00,
                'students_limit' => 500,
                'branches_limit' => 3,
                'staff_limit'    => 30,
                'reports_level'  => 'advanced',
                'can_export'     => true,
                'support_level'  => 'priority',
                'is_active'      => true,
                'sort_order'     => 3,
            ],
            [
                'name'           => 'Enterprise',
                'slug'           => 'enterprise',
                'price'          => 0.00,
                'students_limit' => null,
                'branches_limit' => null,
                'staff_limit'    => null,
                'reports_level'  => 'advanced',
                'can_export'     => true,
                'support_level'  => 'dedicated',
                'is_active'      => true,
                'sort_order'     => 4,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
