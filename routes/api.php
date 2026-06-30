<?php

use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\RolesController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BatchController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\ExamController;
use App\Http\Controllers\Api\V1\ExamTypeController;
use App\Http\Controllers\Api\V1\ExpenseCategoryController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\GradeScaleController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PlanController;
use App\Http\Controllers\Api\V1\SettingsController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\StaffController;
use App\Http\Controllers\Api\V1\FeeController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SalaryController;
use App\Http\Controllers\Api\V1\StudentController;
use App\Http\Controllers\Api\V1\SubjectController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\TeacherController;
use App\Http\Controllers\Api\V1\TenantRegistrationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Public routes (no auth)
    Route::post('/login',    [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/register', [TenantRegistrationController::class, 'store'])->middleware('throttle:5,1');
    Route::get('/plans',     [PlanController::class, 'index']);

    // SSLCommerz callbacks (no auth, no CSRF)
    Route::match(['get', 'post'], '/payment/ipn',     [PaymentController::class, 'ipn']);
    Route::match(['get', 'post'], '/payment/success', [PaymentController::class, 'success']);
    Route::match(['get', 'post'], '/payment/fail',    [PaymentController::class, 'fail']);
    Route::match(['get', 'post'], '/payment/cancel',  [PaymentController::class, 'cancel']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::apiResource('students', StudentController::class);
        Route::post('students/{student}/photo',   [StudentController::class, 'uploadPhoto']);
        Route::delete('students/{student}/photo', [StudentController::class, 'deletePhoto']);

        Route::apiResource('batches', BatchController::class);
        Route::post('batches/{batch}/students', [BatchController::class, 'assignStudents']);
        Route::delete('batches/{batch}/students/{student}', [BatchController::class, 'removeStudent']);

        Route::prefix('attendance')->group(function () {
            Route::get('students',        [AttendanceController::class, 'getStudentAttendance']);
            Route::post('students',       [AttendanceController::class, 'markStudentAttendance']);
            Route::get('teachers',        [AttendanceController::class, 'getTeacherAttendance']);
            Route::post('teachers',       [AttendanceController::class, 'markTeacherAttendance']);
            Route::get('absent',          [AttendanceController::class, 'absentList']);
            Route::get('students/report', [AttendanceController::class, 'studentReport']);
        });

        Route::get('dashboard', [DashboardController::class, 'index']);

        Route::put('settings/center',  [SettingsController::class, 'updateCenter']);
        Route::put('settings/account', [SettingsController::class, 'updateAccount']);

        Route::apiResource('branches', BranchController::class)->except(['show', 'index', 'store'])
             ->parameters(['branches' => 'branch']);
        Route::get('branches',  [BranchController::class, 'index']);
        Route::post('branches', [BranchController::class, 'store']);

        Route::get('reports/collection',     [ReportController::class, 'collection']);
        Route::get('reports/dues',           [ReportController::class, 'dues']);
        Route::get('reports/attendance',     [ReportController::class, 'attendance']);
        Route::get('reports/students',       [ReportController::class, 'students']);
        Route::get('reports/exam-progress',  [ReportController::class, 'examProgress']);
        Route::get('reports/batch-analytics',[ReportController::class, 'batchAnalytics']);
        Route::get('reports/profit-loss',    [ReportController::class, 'profitLoss']);

        // Expense Management
        Route::apiResource('expense-categories', ExpenseCategoryController::class)->except(['show'])
             ->parameters(['expense-categories' => 'expenseCategory']);
        Route::apiResource('expenses', ExpenseController::class);

        Route::get('fees/dues',    [FeeController::class, 'dues']);
        Route::get('fees/summary', [FeeController::class, 'summary']);
        Route::apiResource('fees', FeeController::class)->only(['index', 'store', 'show']);

        Route::apiResource('teachers', TeacherController::class);

        Route::apiResource('staff', StaffController::class)->except(['index', 'store', 'show'])
             ->parameters(['staff' => 'staff']);
        Route::get('staff',  [StaffController::class, 'index']);
        Route::post('staff', [StaffController::class, 'store']);

        Route::get('salaries/monthly-status', [SalaryController::class, 'monthlyStatus']);
        Route::get('salaries/dues',           [SalaryController::class, 'dues']);
        Route::get('salaries',                [SalaryController::class, 'index']);
        Route::post('salaries',               [SalaryController::class, 'store']);

        // Exam & Result Management
        Route::apiResource('subjects',   SubjectController::class)->except(['show']);
        Route::apiResource('exam-types', ExamTypeController::class)->except(['show'])
             ->parameters(['exam-types' => 'examType']);

        Route::get('grade-scales',  [GradeScaleController::class, 'index']);
        Route::put('grade-scales',  [GradeScaleController::class, 'sync']);

        Route::apiResource('exams', ExamController::class);
        Route::get('exams/{exam}/entry',       [ExamController::class, 'entrySheet']);
        Route::post('exams/{exam}/results',    [ExamController::class, 'saveResults']);
        Route::get('exams/{exam}/result-sheet',[ExamController::class, 'resultSheet']);
        Route::get('exams/{exam}/merit-list',  [ExamController::class, 'meritList']);

        // Roles & Permissions management (owner only)
        Route::get('roles/permissions',              [RolesController::class, 'permissions']);
        Route::get('roles',                          [RolesController::class, 'index']);
        Route::post('roles',                         [RolesController::class, 'store']);
        Route::put('roles/{role}/permissions',       [RolesController::class, 'syncPermissions']);
        Route::delete('roles/{role}',                [RolesController::class, 'destroy']);

        // Subscription management
        Route::get('subscription',          [SubscriptionController::class, 'current']);
        Route::post('subscription/checkout',[SubscriptionController::class, 'checkout']);
    });

});
