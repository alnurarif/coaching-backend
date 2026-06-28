<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ExamReportService;
use App\Services\FinanceReportService;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private ReportService        $reportService,
        private ExamReportService    $examReportService,
        private FinanceReportService $financeReportService,
    ) {}

    public function collection(Request $request): JsonResponse
    {
        $this->authorize('viewFinancialReports');

        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date', 'after_or_equal:date_from'],
            'batch_id'  => ['nullable', 'integer'],
            'fee_type'  => ['nullable', 'string', 'in:admission,monthly,exam,other'],
            'per_page'  => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'      => ['nullable', 'integer', 'min:1'],
        ]);

        $result = $this->reportService->collectionReport($request->only(['date_from', 'date_to', 'batch_id', 'fee_type', 'per_page', 'page']));

        return response()->json($result);
    }

    public function dues(Request $request): JsonResponse
    {
        $this->authorize('viewFinancialReports');

        $request->validate([
            'batch_id' => ['nullable', 'integer'],
            'month'    => ['nullable', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'     => ['nullable', 'integer', 'min:1'],
        ]);

        $result = $this->reportService->duesReport($request->only(['batch_id', 'month', 'per_page', 'page']));

        return response()->json($result);
    }

    public function attendance(Request $request): JsonResponse
    {
        $this->authorize('viewAttendanceReports');

        $request->validate([
            'batch_id'  => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page'  => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'      => ['nullable', 'integer', 'min:1'],
        ]);

        $result = $this->reportService->attendanceReport($request->only(['batch_id', 'date_from', 'date_to', 'per_page', 'page']));

        return response()->json($result);
    }

    public function students(Request $request): JsonResponse
    {
        $this->authorize('viewAttendanceReports');

        $request->validate([
            'status'   => ['nullable', 'string', 'in:active,inactive,withdrawn'],
            'batch_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'     => ['nullable', 'integer', 'min:1'],
        ]);

        $result = $this->reportService->studentListReport($request->only(['status', 'batch_id', 'per_page', 'page']));

        return response()->json($result);
    }

    public function examProgress(Request $request): JsonResponse
    {
        $this->authorize('viewExamReports');

        $request->validate([
            'student_id' => ['required', 'integer'],
            'batch_id'   => ['nullable', 'integer'],
            'subject_id' => ['nullable', 'integer'],
            'date_from'  => ['nullable', 'date'],
            'date_to'    => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $result = $this->examReportService->studentProgress($request->only([
            'student_id', 'batch_id', 'subject_id', 'date_from', 'date_to',
        ]));

        return response()->json($result);
    }

    public function batchAnalytics(Request $request): JsonResponse
    {
        $this->authorize('viewExamReports');

        $request->validate([
            'batch_id'  => ['required', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $result = $this->examReportService->batchAnalytics($request->only([
            'batch_id', 'date_from', 'date_to',
        ]));

        return response()->json($result);
    }

    public function profitLoss(Request $request): JsonResponse
    {
        $this->authorize('viewFinancialReports');

        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $result = $this->financeReportService->profitLoss($request->only(['date_from', 'date_to']));

        return response()->json($result);
    }
}
