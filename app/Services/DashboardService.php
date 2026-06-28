<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Exam;
use App\Models\Expense;
use App\Models\FeeCollection;
use App\Models\SalaryPayment;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\User;

class DashboardService
{
    public function getSummary(): array
    {
        $user       = auth()->user();
        $tenantId   = $user->tenant_id;
        $today      = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd   = now()->endOfMonth()->toDateString();

        $canViewFinance     = $user->hasPermissionTo('reports.financial');
        $canViewFees        = $user->hasPermissionTo('fees.view');
        $canViewAttendance  = $user->hasPermissionTo('attendance.view');
        $canViewExams       = $user->hasPermissionTo('exams.view');

        // ── Operations (all authenticated roles) ──────────────────────────────
        $totalStudents = Student::where('tenant_id', $tenantId)->where('status', 'active')->count();
        $newAdmissions = Student::where('tenant_id', $tenantId)
                                ->whereDate('admission_date', '>=', $monthStart)
                                ->whereDate('admission_date', '<=', $monthEnd)
                                ->count();
        $activeBatches = Batch::where('tenant_id', $tenantId)->where('status', 'active')->count();
        $teachersCount = User::whereHas('roles', fn($q) => $q->where('name', 'teacher'))
                             ->where('tenant_id', $tenantId)
                             ->where('is_active', true)
                             ->count();

        // ── Attendance (only for roles with attendance.view) ──────────────────
        $todayRate = null;
        if ($canViewAttendance) {
            $todayAttendance = StudentAttendance::where('tenant_id', $tenantId)->whereDate('date', $today);
            $todayTotal      = (clone $todayAttendance)->count();
            $todayPresent    = (clone $todayAttendance)->where('status', 'present')->count();
            $todayRate       = $todayTotal > 0 ? round($todayPresent / $todayTotal * 100, 1) : null;
        }

        // ── Financial data (reports.financial permission required) ────────────
        $todayCollections  = null;
        $monthCollections  = null;
        $monthExpenses     = null;
        $monthSalary       = null;
        $monthNet          = null;
        $outstandingDues   = null;
        $outstandingAmount = null;
        $trend             = null;
        $recentCollections = null;

        if ($canViewFinance) {
            $todayCollections = (float) FeeCollection::where('tenant_id', $tenantId)
                                                      ->whereDate('payment_date', $today)
                                                      ->sum('amount_paid');

            $monthCollections = (float) FeeCollection::where('tenant_id', $tenantId)
                                                      ->whereDate('payment_date', '>=', $monthStart)
                                                      ->whereDate('payment_date', '<=', $monthEnd)
                                                      ->sum('amount_paid');

            $monthExpenses = (float) Expense::where('tenant_id', $tenantId)
                                            ->whereDate('expense_date', '>=', $monthStart)
                                            ->whereDate('expense_date', '<=', $monthEnd)
                                            ->sum('amount');

            $monthSalary = (float) SalaryPayment::where('tenant_id', $tenantId)
                                                ->whereDate('payment_date', '>=', $monthStart)
                                                ->whereDate('payment_date', '<=', $monthEnd)
                                                ->sum('amount_paid');

            $monthNet = round($monthCollections - $monthExpenses - $monthSalary, 2);

            $duesBase = FeeCollection::where('tenant_id', $tenantId)
                ->whereRaw('(amount_due - discount_amount - scholarship_amount - amount_paid) > 0.009');

            $outstandingDues   = (clone $duesBase)->count();
            $outstandingAmount = round((float) (clone $duesBase)
                ->selectRaw('SUM(amount_due - discount_amount - scholarship_amount - amount_paid) as total')
                ->value('total'), 2);

            // 6-month trend (3 bulk queries, no N+1)
            $sixMonthsAgo = now()->subMonths(5)->startOfMonth()->toDateString();

            $incomeByMonth = FeeCollection::where('tenant_id', $tenantId)
                ->whereDate('payment_date', '>=', $sixMonthsAgo)
                ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') as ym, SUM(amount_paid) as total")
                ->groupBy('ym')->pluck('total', 'ym');

            $expensesByMonth = Expense::where('tenant_id', $tenantId)
                ->whereDate('expense_date', '>=', $sixMonthsAgo)
                ->selectRaw("DATE_FORMAT(expense_date, '%Y-%m') as ym, SUM(amount) as total")
                ->groupBy('ym')->pluck('total', 'ym');

            $salaryByMonth = SalaryPayment::where('tenant_id', $tenantId)
                ->whereDate('payment_date', '>=', $sixMonthsAgo)
                ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') as ym, SUM(amount_paid) as total")
                ->groupBy('ym')->pluck('total', 'ym');

            $trend = [];
            for ($i = 5; $i >= 0; $i--) {
                $ym    = now()->subMonths($i)->format('Y-m');
                $trend[] = [
                    'month'  => now()->subMonths($i)->format('M'),
                    'income' => (float) ($incomeByMonth[$ym] ?? 0),
                    'costs'  => (float) ($expensesByMonth[$ym] ?? 0) + (float) ($salaryByMonth[$ym] ?? 0),
                ];
            }

            $recentCollections = FeeCollection::with(['student:id,name,student_id', 'batch:id,name'])
                ->where('tenant_id', $tenantId)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get()
                ->map(fn($f) => [
                    'id'             => $f->id,
                    'receipt_no'     => $f->receipt_no,
                    'student_name'   => $f->student?->name,
                    'student_sid'    => $f->student?->student_id,
                    'batch_name'     => $f->batch?->name,
                    'fee_type'       => $f->fee_type,
                    'amount_paid'    => (float) $f->amount_paid,
                    'payment_method' => $f->payment_method,
                    'payment_date'   => $f->payment_date?->toDateString(),
                ]);

        } elseif ($canViewFees) {
            // Receptionist: today's collections only (they process fees but not reports)
            $todayCollections = (float) FeeCollection::where('tenant_id', $tenantId)
                                                      ->whereDate('payment_date', $today)
                                                      ->sum('amount_paid');
        }

        // ── Upcoming exams (roles with exams.view) ────────────────────────────
        $upcomingExams = null;
        if ($canViewExams) {
            $upcomingExams = Exam::with(['batch:id,name', 'subject:id,name', 'examType:id,name'])
                ->where('tenant_id', $tenantId)
                ->whereDate('exam_date', '>=', $today)
                ->where('status', '!=', 'completed')
                ->orderBy('exam_date')
                ->limit(5)
                ->get()
                ->map(fn($e) => [
                    'id'           => $e->id,
                    'title'        => $e->title,
                    'exam_date'    => $e->exam_date?->toDateString(),
                    'batch_name'   => $e->batch?->name,
                    'subject_name' => $e->subject?->name,
                    'exam_type'    => $e->examType?->name,
                    'total_marks'  => (float) $e->total_marks,
                    'status'       => $e->status,
                ]);
        }

        return [
            // Operations (all roles)
            'total_students'       => $totalStudents,
            'new_admissions'       => $newAdmissions,
            'active_batches'       => $activeBatches,
            'teachers_count'       => $teachersCount,
            // Attendance (attendance.view roles)
            'today_attendance'     => $todayRate,
            // Collections (reports.financial or fees.view)
            'today_collections'    => $todayCollections,
            // Financial (reports.financial only)
            'month_collections'    => $monthCollections,
            'month_expenses'       => $monthExpenses,
            'month_salary'         => $monthSalary,
            'month_net'            => $monthNet,
            'outstanding_dues'     => $outstandingDues,
            'outstanding_amount'   => $outstandingAmount,
            'trend'                => $trend,
            'recent_collections'   => $recentCollections,
            // Exams (exams.view roles)
            'upcoming_exams'       => $upcomingExams,
        ];
    }
}
