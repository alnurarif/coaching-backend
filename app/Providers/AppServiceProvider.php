<?php

namespace App\Providers;

use App\Models\Batch;
use App\Models\Student;
use App\Models\User;
use App\Policies\BatchPolicy;
use App\Policies\StudentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Owner is super-admin — bypasses every gate and policy check
        Gate::before(fn(User $user) => $user->hasRole('owner') ? true : null);

        Gate::policy(Student::class, StudentPolicy::class);
        Gate::policy(Batch::class, BatchPolicy::class);

        // ── Teachers ─────────────────────────────────────────────────────────
        Gate::define('viewAnyTeacher', fn(User $user) => $user->hasPermissionTo('teachers.view'));
        Gate::define('viewTeacher', fn(User $user, User $teacher) =>
            $user->tenant_id === $teacher->tenant_id
            && $teacher->hasRole('teacher')
            && $user->hasPermissionTo('teachers.view')
        );
        Gate::define('createTeacher', fn(User $user) => $user->hasPermissionTo('teachers.create'));
        Gate::define('updateTeacher', fn(User $user, User $teacher) =>
            $user->tenant_id === $teacher->tenant_id
            && $teacher->hasRole('teacher')
            && $user->hasPermissionTo('teachers.create')
        );
        Gate::define('deleteTeacher', fn(User $user, User $teacher) =>
            $user->tenant_id === $teacher->tenant_id
            && $teacher->hasRole('teacher')
            && $user->hasPermissionTo('teachers.delete')
        );

        Route::model('teacher', User::class);

        // ── Staff ─────────────────────────────────────────────────────────────
        Gate::define('viewAnyStaff', fn(User $user) => $user->hasPermissionTo('staff.view'));
        Gate::define('createStaff',  fn(User $user) => $user->hasPermissionTo('staff.create'));
        Gate::define('updateStaff',  fn(User $user, User $staff) =>
            $user->tenant_id === $staff->tenant_id
            && $user->hasPermissionTo('staff.create')
            && ! $staff->hasAnyRole(['owner', 'teacher'])
        );
        Gate::define('deleteStaff', fn(User $user, User $staff) =>
            $user->tenant_id === $staff->tenant_id
            && $user->hasPermissionTo('staff.delete')
            && ! $staff->hasAnyRole(['owner', 'teacher'])
        );

        Route::model('staff', User::class);

        // ── Settings ──────────────────────────────────────────────────────────
        Gate::define('updateCenterSettings', fn(User $user) => $user->hasPermissionTo('settings.center'));

        // ── Salary ────────────────────────────────────────────────────────────
        Gate::define('viewSalary',   fn(User $user) => $user->hasPermissionTo('salary.view'));
        Gate::define('createSalary', fn(User $user) => $user->hasPermissionTo('salary.create'));

        // ── Exam configuration ────────────────────────────────────────────────
        Gate::define('manageExamConfig', fn(User $user) => $user->hasPermissionTo('exams.create'));

        // ── Subjects ──────────────────────────────────────────────────────────
        Gate::define('viewAnySubject', fn(User $user) => $user->hasPermissionTo('exams.view'));
        Gate::define('createSubject',  fn(User $user) => $user->hasPermissionTo('exams.create'));
        Gate::define('updateSubject',  fn(User $user, \App\Models\Subject $subject) =>
            $user->tenant_id === $subject->tenant_id && $user->hasPermissionTo('exams.create')
        );
        Gate::define('deleteSubject',  fn(User $user, \App\Models\Subject $subject) =>
            $user->tenant_id === $subject->tenant_id && $user->hasPermissionTo('exams.delete')
        );

        // ── Exams ─────────────────────────────────────────────────────────────
        Gate::define('viewAnyExam', fn(User $user) => $user->hasPermissionTo('exams.view'));
        Gate::define('viewExam',    fn(User $user, \App\Models\Exam $exam) =>
            $user->tenant_id === $exam->tenant_id && $user->hasPermissionTo('exams.view')
        );
        Gate::define('createExam', fn(User $user) => $user->hasPermissionTo('exams.create'));
        Gate::define('updateExam', fn(User $user, \App\Models\Exam $exam) =>
            $user->tenant_id === $exam->tenant_id
            && $user->hasPermissionTo('exams.create')
            && (! $user->hasRole('teacher') || $exam->created_by === $user->id)
        );
        Gate::define('deleteExam', fn(User $user, \App\Models\Exam $exam) =>
            $user->tenant_id === $exam->tenant_id
            && $user->hasPermissionTo('exams.delete')
            && (! $user->hasRole('teacher') || $exam->created_by === $user->id)
        );
        Gate::define('enterMarks', fn(User $user, \App\Models\Exam $exam) =>
            $user->tenant_id === $exam->tenant_id && $user->hasPermissionTo('exams.marks')
        );

        // ── Branches ──────────────────────────────────────────────────────────
        Gate::define('viewAnyBranch', fn(User $user) => $user->hasPermissionTo('settings.center'));
        Gate::define('createBranch',  fn(User $user) => $user->hasPermissionTo('settings.center'));
        Gate::define('updateBranch',  fn(User $user, \App\Models\Branch $branch) =>
            $user->tenant_id === $branch->tenant_id && $user->hasPermissionTo('settings.center')
        );
        Gate::define('deleteBranch',  fn(User $user, \App\Models\Branch $branch) =>
            $user->tenant_id === $branch->tenant_id && $user->hasPermissionTo('settings.center')
        );

        // ── Reports ───────────────────────────────────────────────────────────
        Gate::define('viewFinancialReports',  fn(User $user) => $user->hasPermissionTo('reports.financial'));
        Gate::define('viewExamReports',       fn(User $user) => $user->hasPermissionTo('reports.exam'));
        Gate::define('viewAttendanceReports', fn(User $user) => $user->hasPermissionTo('reports.attendance'));

        // ── Expenses ──────────────────────────────────────────────────────────
        Gate::define('manageExpenseCategories', fn(User $user) => $user->hasPermissionTo('expenses.create'));
        Gate::define('viewAnyExpense', fn(User $user) => $user->hasPermissionTo('expenses.view'));
        Gate::define('createExpense',  fn(User $user) => $user->hasPermissionTo('expenses.create'));
        Gate::define('updateExpense',  fn(User $user, \App\Models\Expense $expense) =>
            $user->tenant_id === $expense->tenant_id && $user->hasPermissionTo('expenses.create')
        );
        Gate::define('deleteExpense',  fn(User $user, \App\Models\Expense $expense) =>
            $user->tenant_id === $expense->tenant_id && $user->hasPermissionTo('expenses.delete')
        );

        // ── Fees ──────────────────────────────────────────────────────────────
        Gate::define('viewAnyFee', fn(User $user) => $user->hasPermissionTo('fees.view'));
        Gate::define('createFee',  fn(User $user) => $user->hasPermissionTo('fees.create'));
        Gate::define('deleteFee',  fn(User $user) => $user->hasPermissionTo('fees.delete'));

        // ── Attendance ────────────────────────────────────────────────────────
        Gate::define('viewAnyAttendance', fn(User $user) => $user->hasPermissionTo('attendance.view'));
        Gate::define('markAttendance',    fn(User $user) => $user->hasPermissionTo('attendance.mark'));

        // ── Roles management (owner only, enforced via Gate::before above) ────
        Gate::define('manageRoles', fn(User $user) => $user->hasRole('owner'));
    }
}
