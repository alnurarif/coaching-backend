<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Exam;
use App\Models\ExamType;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FeeCollection;
use App\Models\GradeScale;
use App\Models\Guardian;
use App\Models\SalaryPayment;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    private Tenant $tenant;
    private int $branchId;
    private User $owner;
    private array $teachers = [];
    private array $batches = [];
    private array $students = [];
    private array $gradeScales = [];
    private int $receiptCounter = 1;
    private int $salaryReceiptCounter = 1;

    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
            TenantSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
        ]);

        $this->tenant = Tenant::first();
        $this->branchId = DB::table('branches')->where('tenant_id', $this->tenant->id)->value('id');
        $this->owner = User::whereHas('roles', fn ($q) => $q->where('name', 'owner'))->first();
        $this->gradeScales = GradeScale::where('tenant_id', $this->tenant->id)
            ->orderBy('min_percent', 'desc')
            ->get()
            ->toArray();

        $this->seedStaff();
        $this->seedSubjects();
        $this->seedBatches();
        $this->seedStudents();
        $this->enrollStudents();
        $this->seedAttendance();
        $this->seedFeeCollections();
        $this->seedSalaryPayments();
        $this->seedExpenses();
        $this->seedExams();
    }

    // -------------------------------------------------------------------------
    // Staff
    // -------------------------------------------------------------------------

    private function seedStaff(): void
    {
        $staffData = [
            [
                'name' => 'Md. Rafiqul Islam',
                'email' => 'rafiq@brightfuture.com',
                'phone' => '01711234567',
                'role' => 'teacher',
                'base_salary' => 18000,
                'profile' => [
                    'subject' => 'Physics',
                    'qualification' => 'M.Sc. Physics, University of Dhaka',
                    'address' => 'Mirpur-1, Dhaka',
                    'join_date' => '2024-01-15',
                    'base_salary' => 18000,
                ],
            ],
            [
                'name' => 'Farida Khanam',
                'email' => 'farida@brightfuture.com',
                'phone' => '01811234568',
                'role' => 'teacher',
                'base_salary' => 16000,
                'profile' => [
                    'subject' => 'Chemistry',
                    'qualification' => 'M.Sc. Chemistry, BUET',
                    'address' => 'Mohammadpur, Dhaka',
                    'join_date' => '2024-03-01',
                    'base_salary' => 16000,
                ],
            ],
            [
                'name' => 'Md. Jahangir Alam',
                'email' => 'jahangir@brightfuture.com',
                'phone' => '01911234569',
                'role' => 'teacher',
                'base_salary' => 20000,
                'profile' => [
                    'subject' => 'Mathematics',
                    'qualification' => 'M.Sc. Mathematics, BUET',
                    'address' => 'Dhanmondi, Dhaka',
                    'join_date' => '2023-07-01',
                    'base_salary' => 20000,
                ],
            ],
            [
                'name' => 'Shahanara Begum',
                'email' => 'shahana@brightfuture.com',
                'phone' => '01611234570',
                'role' => 'teacher',
                'base_salary' => 15000,
                'profile' => [
                    'subject' => 'Biology',
                    'qualification' => 'M.Sc. Biology, University of Dhaka',
                    'address' => 'Tejgaon, Dhaka',
                    'join_date' => '2024-06-01',
                    'base_salary' => 15000,
                ],
            ],
            [
                'name' => 'Md. Karimul Hasan',
                'email' => 'karim@brightfuture.com',
                'phone' => '01711234571',
                'role' => 'manager',
                'base_salary' => 25000,
                'profile' => null,
            ],
            [
                'name' => 'Rupa Akter',
                'email' => 'rupa@brightfuture.com',
                'phone' => '01811234572',
                'role' => 'accountant',
                'base_salary' => 18000,
                'profile' => null,
            ],
            [
                'name' => 'Nasrin Sultana',
                'email' => 'nasrin@brightfuture.com',
                'phone' => '01911234573',
                'role' => 'receptionist',
                'base_salary' => 12000,
                'profile' => null,
            ],
        ];

        foreach ($staffData as $data) {
            $user = User::create([
                'tenant_id' => $this->tenant->id,
                'branch_id' => $this->branchId,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => bcrypt('password123'),
                'is_active' => true,
                'base_salary' => $data['base_salary'],
            ]);

            $user->assignRole($data['role']);

            if ($data['profile']) {
                TeacherProfile::create(['user_id' => $user->id, ...$data['profile']]);
                $this->teachers[] = $user;
            }
        }
    }

    // -------------------------------------------------------------------------
    // Subjects
    // -------------------------------------------------------------------------

    private function seedSubjects(): void
    {
        $subjects = [
            ['name' => 'Physics', 'code' => 'PHY'],
            ['name' => 'Chemistry', 'code' => 'CHE'],
            ['name' => 'Mathematics', 'code' => 'MAT'],
            ['name' => 'Higher Mathematics', 'code' => 'HMT'],
            ['name' => 'Biology', 'code' => 'BIO'],
            ['name' => 'English', 'code' => 'ENG'],
            ['name' => 'Bangla', 'code' => 'BAN'],
            ['name' => 'ICT', 'code' => 'ICT'],
        ];

        foreach ($subjects as $s) {
            Subject::create(['tenant_id' => $this->tenant->id, ...$s, 'is_active' => true]);
        }
    }

    // -------------------------------------------------------------------------
    // Batches
    // -------------------------------------------------------------------------

    private function seedBatches(): void
    {
        $subjects = Subject::where('tenant_id', $this->tenant->id)->get()->keyBy('code');
        [$t0, $t1, $t2, $t3] = $this->teachers; // Rafiqul, Farida, Jahangir, Shahanara

        $batchData = [
            [
                'name' => 'SSC Physics - Morning',
                'subject_id' => $subjects['PHY']->id,
                'teacher_id' => $t0->id,
                'capacity' => 25,
                'fee_amount' => 1200,
                'schedule' => [
                    ['day' => 'Sunday', 'time' => '08:00-09:30'],
                    ['day' => 'Tuesday', 'time' => '08:00-09:30'],
                    ['day' => 'Thursday', 'time' => '08:00-09:30'],
                ],
                'start_date' => '2026-01-01',
            ],
            [
                'name' => 'HSC Physics - Evening',
                'subject_id' => $subjects['PHY']->id,
                'teacher_id' => $t0->id,
                'capacity' => 20,
                'fee_amount' => 1500,
                'schedule' => [
                    ['day' => 'Monday', 'time' => '05:00-06:30'],
                    ['day' => 'Wednesday', 'time' => '05:00-06:30'],
                    ['day' => 'Saturday', 'time' => '05:00-06:30'],
                ],
                'start_date' => '2026-01-01',
            ],
            [
                'name' => 'SSC Chemistry Batch',
                'subject_id' => $subjects['CHE']->id,
                'teacher_id' => $t1->id,
                'capacity' => 25,
                'fee_amount' => 1200,
                'schedule' => [
                    ['day' => 'Sunday', 'time' => '10:00-11:30'],
                    ['day' => 'Tuesday', 'time' => '10:00-11:30'],
                    ['day' => 'Thursday', 'time' => '10:00-11:30'],
                ],
                'start_date' => '2026-01-01',
            ],
            [
                'name' => 'SSC Mathematics Batch',
                'subject_id' => $subjects['MAT']->id,
                'teacher_id' => $t2->id,
                'capacity' => 30,
                'fee_amount' => 1000,
                'schedule' => [
                    ['day' => 'Sunday', 'time' => '02:00-03:30'],
                    ['day' => 'Tuesday', 'time' => '02:00-03:30'],
                    ['day' => 'Thursday', 'time' => '02:00-03:30'],
                ],
                'start_date' => '2026-01-15',
            ],
            [
                'name' => 'HSC Higher Math Batch',
                'subject_id' => $subjects['HMT']->id,
                'teacher_id' => $t2->id,
                'capacity' => 20,
                'fee_amount' => 1500,
                'schedule' => [
                    ['day' => 'Monday', 'time' => '03:00-04:30'],
                    ['day' => 'Wednesday', 'time' => '03:00-04:30'],
                    ['day' => 'Saturday', 'time' => '03:00-04:30'],
                ],
                'start_date' => '2026-01-15',
            ],
            [
                'name' => 'SSC Biology Batch',
                'subject_id' => $subjects['BIO']->id,
                'teacher_id' => $t3->id,
                'capacity' => 25,
                'fee_amount' => 1200,
                'schedule' => [
                    ['day' => 'Monday', 'time' => '10:00-11:30'],
                    ['day' => 'Wednesday', 'time' => '10:00-11:30'],
                    ['day' => 'Saturday', 'time' => '10:00-11:30'],
                ],
                'start_date' => '2026-02-01',
            ],
        ];

        foreach ($batchData as $data) {
            $this->batches[] = Batch::create([
                'tenant_id' => $this->tenant->id,
                'branch_id' => $this->branchId,
                'status' => 'active',
                ...$data,
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Students
    // -------------------------------------------------------------------------

    private function seedStudents(): void
    {
        $names = [
            ['Arif Hossain', 'male', 'Md. Hossain Ali', 'father'],
            ['Nusrat Jahan', 'female', 'Md. Jahan Mia', 'father'],
            ['Sakib Ahmed', 'male', 'Md. Nurul Ahmed', 'father'],
            ['Sumaiya Akter', 'female', 'Md. Abul Kalam', 'father'],
            ['Rakibul Islam', 'male', 'Md. Shafiqul Islam', 'father'],
            ['Tamanna Haque', 'female', 'Md. Abdul Haque', 'father'],
            ['Imtiaz Ahmed', 'male', 'Md. Iqbal Ahmed', 'father'],
            ['Sabrina Sultana', 'female', 'Md. Kamal Uddin', 'father'],
            ['Mahmudul Hasan', 'male', 'Md. Matiur Rahman', 'father'],
            ['Rabeya Begum', 'female', 'Md. Aminul Islam', 'father'],
            ['Nafis Rahman', 'male', 'Md. Habibur Rahman', 'father'],
            ['Afsana Islam', 'female', 'Md. Rezaul Islam', 'father'],
            ['Tahmid Hossain', 'male', 'Md. Sirajul Hossain', 'father'],
            ['Farhana Yasmin', 'female', 'Md. Nurul Huda', 'father'],
            ['Masum Billah', 'male', 'Md. Billah Mia', 'father'],
            ['Sharmin Akter', 'female', 'Md. Alauddin', 'father'],
            ['Zahid Hasan', 'male', 'Md. Ziaul Hasan', 'father'],
            ['Mithila Chowdhury', 'female', 'Md. Azizul Chowdhury', 'father'],
            ['Tanvir Ahmed', 'male', 'Md. Jahirul Ahmed', 'father'],
            ['Lubna Akter', 'female', 'Md. Rashedul Islam', 'father'],
            ['Asif Mahmud', 'male', 'Md. Mostafa Kamal', 'father'],
            ['Shapla Begum', 'female', 'Md. Anwarul Islam', 'father'],
            ['Rashed Khan', 'male', 'Md. Faruq Khan', 'father'],
            ['Mousumi Akhter', 'female', 'Md. Selim Akhter', 'father'],
            ['Shahriar Hossain', 'male', 'Md. Shahab Hossain', 'father'],
            ['Israt Jahan', 'female', 'Md. Saiful Islam', 'father'],
            ['Mehedi Hasan', 'male', 'Md. Yunus Ali', 'father'],
            ['Puja Rani Das', 'female', 'Bimal Das', 'guardian'],
            ['Sojib Hossain', 'male', 'Md. Khairul Hossain', 'father'],
            ['Ritu Akhter', 'female', 'Md. Bellal Hossain', 'father'],
            ['Towhid Ahmed', 'male', 'Md. Monir Ahmed', 'father'],
            ['Shirin Akter', 'female', 'Md. Sohrab Ali', 'father'],
            ['Ripon Mia', 'male', 'Md. Jalal Mia', 'father'],
            ['Jannat Ara', 'female', 'Md. Mamun Rashid', 'father'],
            ['Farhan Islam', 'male', 'Md. Shahidul Islam', 'father'],
            ['Khadija Begum', 'female', 'Md. Mojibur Rahman', 'father'],
            ['Sajjad Hussain', 'male', 'Md. Badrul Hussain', 'father'],
            ['Mou Akter', 'female', 'Md. Rafique Ullah', 'father'],
            ['Nurul Amin', 'male', 'Md. Lokman Hossain', 'father'],
            ['Shiuly Begum', 'female', 'Md. Ataur Rahman', 'father'],
        ];

        $addresses = [
            'Mirpur-1, Dhaka', 'Mirpur-2, Dhaka', 'Mirpur-6, Dhaka', 'Mirpur-10, Dhaka',
            'Mohammadpur, Dhaka', 'Dhanmondi, Dhaka', 'Shyamoli, Dhaka', 'Rayer Bazar, Dhaka',
            'Adabor, Dhaka', 'Pallabi, Dhaka', 'Kafrul, Dhaka', 'Tejgaon, Dhaka',
        ];

        $occupations = ['Business', 'Service', 'Teaching', 'Doctor', 'Engineer', 'Farming'];
        $prefixes = ['017', '018', '019', '016', '015'];

        foreach ($names as $i => [$name, $gender, $guardianName, $relation]) {
            $phone = $prefixes[array_rand($prefixes)] . str_pad(mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
            $dob = Carbon::create(mt_rand(2005, 2009), mt_rand(1, 12), mt_rand(1, 28));
            $admissionDate = Carbon::create(2026, mt_rand(1, 2), mt_rand(1, 25));

            $student = Student::create([
                'tenant_id' => $this->tenant->id,
                'branch_id' => $this->branchId,
                'student_id' => 'STU-2026-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'name' => $name,
                'gender' => $gender,
                'date_of_birth' => $dob->toDateString(),
                'phone' => $phone,
                'address' => $addresses[array_rand($addresses)],
                'admission_date' => $admissionDate->toDateString(),
                'status' => $i < 37 ? 'active' : 'inactive',
            ]);

            Guardian::create([
                'student_id' => $student->id,
                'name' => $guardianName,
                'relation' => $relation,
                'phone' => $prefixes[array_rand($prefixes)] . str_pad(mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                'occupation' => $occupations[array_rand($occupations)],
            ]);

            $this->students[] = $student;
        }
    }

    private function enrollStudents(): void
    {
        // Which students go in which batch (by index)
        $plan = [
            0 => range(0, 19),   // SSC Physics Morning: 20 students
            1 => range(20, 34),  // HSC Physics Evening: 15 students
            2 => range(0, 17),   // SSC Chemistry: 18 students (overlap)
            3 => range(5, 29),   // SSC Mathematics: 25 students
            4 => range(20, 34),  // HSC Higher Math: 15 students (overlap with HSC Physics)
            5 => range(0, 21),   // SSC Biology: 22 students
        ];

        $inserted = [];
        foreach ($plan as $batchIdx => $studentIndexes) {
            $batchId = $this->batches[$batchIdx]->id;
            $startDate = $this->batches[$batchIdx]->start_date->toDateString();
            foreach ($studentIndexes as $si) {
                if (!isset($this->students[$si])) continue;
                $studentId = $this->students[$si]->id;
                $key = "$batchId-$studentId";
                if (isset($inserted[$key])) continue;
                $inserted[$key] = true;

                DB::table('batch_students')->insert([
                    'batch_id' => $batchId,
                    'student_id' => $studentId,
                    'joined_at' => $startDate,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Attendance
    // -------------------------------------------------------------------------

    private function seedAttendance(): void
    {
        $start = Carbon::now()->subDays(60)->startOfDay();
        $end = Carbon::yesterday();

        $enrollments = DB::table('batch_students')->get();
        $allStaff = User::where('tenant_id', $this->tenant->id)->pluck('id');

        $studentRows = [];
        $teacherRows = [];

        $day = $start->copy();
        while ($day->lte($end)) {
            if ($day->dayOfWeek !== Carbon::FRIDAY) {
                $dateStr = $day->toDateString();
                $now = now();

                foreach ($enrollments as $e) {
                    $r = mt_rand(1, 100);
                    $studentRows[] = [
                        'tenant_id' => $this->tenant->id,
                        'batch_id' => $e->batch_id,
                        'student_id' => $e->student_id,
                        'date' => $dateStr,
                        'status' => $r <= 80 ? 'present' : ($r <= 92 ? 'absent' : 'late'),
                        'note' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                foreach ($allStaff as $userId) {
                    $r = mt_rand(1, 100);
                    $teacherRows[] = [
                        'tenant_id' => $this->tenant->id,
                        'branch_id' => $this->branchId,
                        'user_id' => $userId,
                        'date' => $dateStr,
                        'status' => $r <= 88 ? 'present' : ($r <= 96 ? 'absent' : 'late'),
                        'note' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
            $day->addDay();
        }

        foreach (array_chunk($studentRows, 500) as $chunk) {
            DB::table('student_attendances')->insert($chunk);
        }
        foreach (array_chunk($teacherRows, 500) as $chunk) {
            DB::table('teacher_attendances')->insert($chunk);
        }
    }

    // -------------------------------------------------------------------------
    // Fee Collections
    // -------------------------------------------------------------------------

    private function seedFeeCollections(): void
    {
        $enrollments = DB::table('batch_students')->get()->groupBy('student_id');
        $methods = ['cash', 'bkash', 'nagad', 'rocket'];
        $now = now();

        // Admission fee per student (once, on first batch)
        foreach ($this->students as $student) {
            $studentEnrollments = $enrollments->get($student->id);
            if (!$studentEnrollments) continue;
            $batchId = $studentEnrollments->first()->batch_id;

            FeeCollection::create([
                'tenant_id' => $this->tenant->id,
                'student_id' => $student->id,
                'batch_id' => $batchId,
                'collected_by' => $this->owner->id,
                'fee_type' => 'admission',
                'month' => null,
                'amount_due' => 500,
                'discount_amount' => 0,
                'scholarship_amount' => 0,
                'amount_paid' => 500,
                'payment_date' => $student->admission_date->toDateString(),
                'payment_method' => $methods[array_rand($methods)],
                'receipt_no' => 'ADM-' . str_pad($this->receiptCounter++, 5, '0', STR_PAD_LEFT),
                'note' => null,
            ]);
        }

        // Monthly fees Jan–Jun 2026
        $months = ['2026-01', '2026-02', '2026-03', '2026-04', '2026-05', '2026-06'];

        foreach ($this->batches as $batch) {
            $batchStart = $batch->start_date;
            $batchEnrollments = DB::table('batch_students')
                ->where('batch_id', $batch->id)
                ->get();

            foreach ($months as $month) {
                $monthDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
                if ($monthDate->lt($batchStart->copy()->startOfMonth())) continue;

                foreach ($batchEnrollments as $enrollment) {
                    $student = $this->findStudent($enrollment->student_id);
                    if ($student && $student->status === 'inactive') continue;

                    // June is current month — only 65% paid so far
                    if ($month === '2026-06' && mt_rand(1, 100) > 65) continue;

                    $discount = mt_rand(1, 10) === 1 ? round($batch->fee_amount * 0.05, 2) : 0;
                    $scholarship = mt_rand(1, 20) === 1 ? round($batch->fee_amount * 0.10, 2) : 0;
                    $amountDue = $batch->fee_amount;
                    $amountPaid = $amountDue - $discount - $scholarship;
                    $payDay = mt_rand(1, 20);
                    // Make sure payment day is valid for the month
                    $payDay = min($payDay, $monthDate->daysInMonth);

                    FeeCollection::create([
                        'tenant_id' => $this->tenant->id,
                        'student_id' => $enrollment->student_id,
                        'batch_id' => $batch->id,
                        'collected_by' => $this->owner->id,
                        'fee_type' => 'monthly',
                        'month' => $month,
                        'amount_due' => $amountDue,
                        'discount_amount' => $discount,
                        'scholarship_amount' => $scholarship,
                        'amount_paid' => $amountPaid,
                        'payment_date' => $monthDate->setDay($payDay)->toDateString(),
                        'payment_method' => $methods[array_rand($methods)],
                        'receipt_no' => 'FEE-' . str_pad($this->receiptCounter++, 5, '0', STR_PAD_LEFT),
                        'note' => null,
                    ]);
                }
            }
        }
    }

    private function findStudent(int $id): ?Student
    {
        foreach ($this->students as $s) {
            if ($s->id === $id) return $s;
        }
        return null;
    }

    // -------------------------------------------------------------------------
    // Salary Payments
    // -------------------------------------------------------------------------

    private function seedSalaryPayments(): void
    {
        $allStaff = User::where('tenant_id', $this->tenant->id)->get();
        // Fully paid: March, April, May; June not yet paid
        $months = ['2026-03', '2026-04', '2026-05'];

        foreach ($allStaff as $staff) {
            foreach ($months as $month) {
                $baseSalary = $staff->base_salary ?? 15000;
                $bonus = $month === '2026-05' ? 2000 : 0; // Eid bonus
                $deduction = mt_rand(0, 3) === 0 ? mt_rand(500, 1000) : 0;
                $amountPaid = $baseSalary + $bonus - $deduction;
                $payDay = mt_rand(25, 28);
                $monthDate = Carbon::createFromFormat('Y-m', $month);

                SalaryPayment::create([
                    'tenant_id' => $this->tenant->id,
                    'user_id' => $staff->id,
                    'month' => $month,
                    'base_salary' => $baseSalary,
                    'bonus' => $bonus,
                    'deduction' => $deduction,
                    'amount_paid' => $amountPaid,
                    'payment_date' => $monthDate->setDay($payDay)->toDateString(),
                    'payment_method' => 'bank',
                    'receipt_no' => 'SAL-' . str_pad($this->salaryReceiptCounter++, 5, '0', STR_PAD_LEFT),
                    'paid_by' => $this->owner->id,
                    'note' => $bonus > 0 ? 'Includes Eid bonus' : null,
                ]);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Expenses
    // -------------------------------------------------------------------------

    private function seedExpenses(): void
    {
        $categories = ExpenseCategory::where('tenant_id', $this->tenant->id)->get()->keyBy('name');

        $rows = [
            // January 2026
            ['Rent', 'January Office Rent', 35000, '2026-01-01', 'bank_transfer'],
            ['Utilities', 'Electricity Bill - January', 3800, '2026-01-05', 'cash'],
            ['Utilities', 'Internet Bill - January', 1200, '2026-01-05', 'bkash'],
            ['Supplies', 'Whiteboard & Markers', 950, '2026-01-08', 'cash'],
            ['Marketing', 'Admission Banner & Leaflets', 4500, '2026-01-10', 'cash'],
            ['Other', 'Tea & Refreshments - January', 600, '2026-01-28', 'cash'],
            // February 2026
            ['Rent', 'February Office Rent', 35000, '2026-02-01', 'bank_transfer'],
            ['Utilities', 'Electricity Bill - February', 4100, '2026-02-05', 'cash'],
            ['Utilities', 'Internet Bill - February', 1200, '2026-02-05', 'bkash'],
            ['Maintenance', 'Fan Repair - Classroom 1 & 2', 1800, '2026-02-12', 'cash'],
            ['Supplies', 'Printed Student Worksheets', 1200, '2026-02-18', 'cash'],
            ['Other', 'Tea & Refreshments - February', 600, '2026-02-26', 'cash'],
            // March 2026
            ['Rent', 'March Office Rent', 35000, '2026-03-01', 'bank_transfer'],
            ['Utilities', 'Electricity Bill - March', 4500, '2026-03-05', 'cash'],
            ['Utilities', 'Internet Bill - March', 1200, '2026-03-05', 'bkash'],
            ['Marketing', 'Facebook Ad Campaign - March', 3000, '2026-03-12', 'bkash'],
            ['Maintenance', 'AC Service & Repair', 2500, '2026-03-15', 'cash'],
            ['Supplies', 'Printed Student Materials', 1500, '2026-03-20', 'cash'],
            ['Other', 'Tea & Refreshments - March', 600, '2026-03-28', 'cash'],
            // April 2026
            ['Rent', 'April Office Rent', 35000, '2026-04-01', 'bank_transfer'],
            ['Utilities', 'Electricity Bill - April', 5200, '2026-04-05', 'cash'],
            ['Utilities', 'Internet Bill - April', 1200, '2026-04-05', 'bkash'],
            ['Equipment', 'New Projector Purchase', 45000, '2026-04-08', 'bank_transfer'],
            ['Marketing', 'Admission Brochure Printing', 4500, '2026-04-10', 'cash'],
            ['Supplies', 'Exam Answer Sheets (500 pcs)', 2000, '2026-04-15', 'cash'],
            ['Maintenance', 'Plumbing Repair - Bathroom', 1800, '2026-04-18', 'cash'],
            ['Other', 'Tea & Refreshments - April', 600, '2026-04-28', 'cash'],
            // May 2026
            ['Rent', 'May Office Rent', 35000, '2026-05-01', 'bank_transfer'],
            ['Utilities', 'Electricity Bill - May', 6000, '2026-05-05', 'cash'],
            ['Utilities', 'Internet Bill - May', 1200, '2026-05-05', 'bkash'],
            ['Marketing', 'Google Ads Campaign - May', 2500, '2026-05-10', 'bank_transfer'],
            ['Supplies', 'HSC Preparatory Books (set)', 8000, '2026-05-12', 'cash'],
            ['Equipment', 'Classroom Chairs - 10 pcs', 12000, '2026-05-14', 'bank_transfer'],
            ['Maintenance', 'Classroom 2 Painting', 5500, '2026-05-18', 'cash'],
            ['Other', 'Eid Celebration Expenses', 3500, '2026-05-22', 'cash'],
            ['Other', 'Tea & Refreshments - May', 600, '2026-05-28', 'cash'],
            // June 2026
            ['Rent', 'June Office Rent', 35000, '2026-06-01', 'bank_transfer'],
            ['Utilities', 'Electricity Bill - June', 5800, '2026-06-05', 'cash'],
            ['Utilities', 'Internet Bill - June', 1200, '2026-06-05', 'bkash'],
            ['Marketing', 'New Batch Admission Notice', 3500, '2026-06-10', 'bkash'],
            ['Supplies', 'SSC Exam Question Paper Sets', 1500, '2026-06-15', 'cash'],
            ['Maintenance', 'Projector Lamp Replacement', 3200, '2026-06-20', 'cash'],
        ];

        foreach ($rows as [$catName, $title, $amount, $date, $method]) {
            Expense::create([
                'tenant_id' => $this->tenant->id,
                'expense_category_id' => $categories->get($catName)?->id,
                'branch_id' => $this->branchId,
                'recorded_by' => $this->owner->id,
                'title' => $title,
                'amount' => $amount,
                'expense_date' => $date,
                'payment_method' => $method,
                'reference_no' => null,
                'notes' => null,
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Exams & Results
    // -------------------------------------------------------------------------

    private function seedExams(): void
    {
        $examTypes = ExamType::where('tenant_id', $this->tenant->id)->get()->keyBy('name');
        $subjects = Subject::where('tenant_id', $this->tenant->id)->get()->keyBy('name');

        $configs = [
            [0, 'Monthly Exam', 'Physics', 'March Monthly Exam - SSC Physics', '2026-03-31', 100, 33],
            [0, 'Monthly Exam', 'Physics', 'April Monthly Exam - SSC Physics', '2026-04-28', 100, 33],
            [0, 'Model Test', 'Physics', 'SSC Physics Model Test', '2026-05-20', 100, 33],
            [1, 'Monthly Exam', 'Physics', 'March Monthly Exam - HSC Physics', '2026-03-30', 100, 33],
            [1, 'Monthly Exam', 'Physics', 'April Monthly Exam - HSC Physics', '2026-04-27', 100, 33],
            [1, 'Model Test', 'Physics', 'HSC Physics First Model Test', '2026-05-22', 100, 33],
            [2, 'Monthly Exam', 'Chemistry', 'March Monthly Exam - SSC Chemistry', '2026-03-28', 100, 33],
            [2, 'Weekly Exam', 'Chemistry', 'Chemistry Weekly Test - Chapter 3', '2026-04-10', 30, 12],
            [2, 'Monthly Exam', 'Chemistry', 'April Monthly Exam - SSC Chemistry', '2026-04-30', 100, 33],
            [3, 'Monthly Exam', 'Mathematics', 'March Monthly Exam - SSC Math', '2026-03-29', 100, 33],
            [3, 'Monthly Exam', 'Mathematics', 'April Monthly Exam - SSC Math', '2026-04-29', 100, 33],
            [3, 'Model Test', 'Mathematics', 'SSC Math Final Model Test', '2026-05-25', 100, 33],
            [4, 'Monthly Exam', 'Higher Mathematics', 'March Monthly Exam - HSC Higher Math', '2026-03-31', 100, 33],
            [4, 'Monthly Exam', 'Higher Mathematics', 'April Monthly Exam - HSC Higher Math', '2026-04-26', 100, 33],
            [5, 'Monthly Exam', 'Biology', 'March Monthly Exam - SSC Biology', '2026-03-27', 100, 33],
            [5, 'Weekly Exam', 'Biology', 'Biology Weekly Test - Chapter 5', '2026-04-12', 30, 12],
            [5, 'Monthly Exam', 'Biology', 'April Monthly Exam - SSC Biology', '2026-04-28', 100, 33],
        ];

        foreach ($configs as [$batchIdx, $typeName, $subjectName, $title, $date, $total, $pass]) {
            $batch = $this->batches[$batchIdx];
            $exam = Exam::create([
                'tenant_id' => $this->tenant->id,
                'batch_id' => $batch->id,
                'subject_id' => $subjects->get($subjectName)?->id,
                'exam_type_id' => $examTypes->get($typeName)?->id,
                'created_by' => $batch->teacher_id ?? $this->owner->id,
                'title' => $title,
                'exam_date' => $date,
                'total_marks' => $total,
                'passing_marks' => $pass,
                'status' => 'completed',
            ]);

            $this->generateResults($exam, $batch->id, $total);
        }
    }

    private function generateResults(Exam $exam, int $batchId, float $total): void
    {
        $enrollments = DB::table('batch_students')->where('batch_id', $batchId)->get();
        $rows = [];

        foreach ($enrollments as $e) {
            $isAbsent = mt_rand(1, 10) === 1;
            $marks = null;
            $grade = null;

            if (!$isAbsent) {
                // Skewed toward 50-85 range to feel realistic
                $marks = (float) round(min($total, max(0, mt_rand(30, 90) + (mt_rand(-5, 10)))), 2);
                $grade = $this->calcGrade(($marks / $total) * 100);
            }

            $rows[] = [
                'tenant_id' => $this->tenant->id,
                'exam_id' => $exam->id,
                'student_id' => $e->student_id,
                'marks_obtained' => $marks,
                'is_absent' => $isAbsent,
                'grade' => $grade,
                'position' => null,
                'remarks' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Assign positions to present students ranked by marks
        $present = array_filter($rows, fn ($r) => !$r['is_absent']);
        usort($present, fn ($a, $b) => $b['marks_obtained'] <=> $a['marks_obtained']);
        $pos = 1;
        $positions = [];
        foreach ($present as $r) {
            $positions[$r['student_id']] = $pos++;
        }

        foreach ($rows as &$row) {
            if (!$row['is_absent']) {
                $row['position'] = $positions[$row['student_id']] ?? null;
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('exam_results')->insert($chunk);
        }
    }

    private function calcGrade(float $percent): string
    {
        foreach ($this->gradeScales as $scale) {
            if ($percent >= $scale['min_percent'] && $percent <= $scale['max_percent']) {
                return $scale['label'];
            }
        }
        return 'F';
    }
}
