<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\ExamType;
use App\Models\ExpenseCategory;
use App\Models\GradeScale;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::create([
            'name'      => 'Bright Future Coaching Center',
            'slug'      => 'bright-future',
            'phone'     => '01700000000',
            'email'     => 'info@brightfuture.com',
            'address'   => 'Mirpur, Dhaka',
            'is_active' => true,
        ]);

        Branch::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Main Branch',
            'phone'     => '01700000001',
            'address'   => 'Mirpur-10, Dhaka',
            'is_active' => true,
        ]);

        $examTypes = [
            ['name' => 'Weekly Exam',     'sort_order' => 1],
            ['name' => 'Monthly Exam',    'sort_order' => 2],
            ['name' => 'Model Test',      'sort_order' => 3],
            ['name' => 'Admission Test',  'sort_order' => 4],
            ['name' => 'Final Exam',      'sort_order' => 5],
        ];

        foreach ($examTypes as $type) {
            ExamType::create(['tenant_id' => $tenant->id, ...$type, 'is_active' => true]);
        }

        $gradeScales = [
            ['label' => 'A+', 'min_percent' => 90.00, 'max_percent' => 100.00, 'gpa' => 5.00, 'sort_order' => 0],
            ['label' => 'A',  'min_percent' => 80.00, 'max_percent' =>  89.99, 'gpa' => 4.00, 'sort_order' => 1],
            ['label' => 'A-', 'min_percent' => 70.00, 'max_percent' =>  79.99, 'gpa' => 3.50, 'sort_order' => 2],
            ['label' => 'B',  'min_percent' => 60.00, 'max_percent' =>  69.99, 'gpa' => 3.00, 'sort_order' => 3],
            ['label' => 'C',  'min_percent' => 50.00, 'max_percent' =>  59.99, 'gpa' => 2.00, 'sort_order' => 4],
            ['label' => 'D',  'min_percent' => 40.00, 'max_percent' =>  49.99, 'gpa' => 1.00, 'sort_order' => 5],
            ['label' => 'F',  'min_percent' =>  0.00, 'max_percent' =>  39.99, 'gpa' => 0.00, 'sort_order' => 6],
        ];

        foreach ($gradeScales as $scale) {
            GradeScale::create(['tenant_id' => $tenant->id, ...$scale]);
        }

        $expenseCategories = [
            ['name' => 'Rent',        'color' => '#6366F1'],
            ['name' => 'Utilities',   'color' => '#F59E0B'],
            ['name' => 'Salaries',    'color' => '#10B981'],
            ['name' => 'Marketing',   'color' => '#3B82F6'],
            ['name' => 'Supplies',    'color' => '#EC4899'],
            ['name' => 'Maintenance', 'color' => '#EF4444'],
            ['name' => 'Equipment',   'color' => '#8B5CF6'],
            ['name' => 'Other',       'color' => '#6B7280'],
        ];

        foreach ($expenseCategories as $cat) {
            ExpenseCategory::create(['tenant_id' => $tenant->id, ...$cat, 'is_active' => true]);
        }
    }
}
