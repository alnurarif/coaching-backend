<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\MarkStudentAttendanceRequest;
use App\Http\Requests\Attendance\MarkTeacherAttendanceRequest;
use App\Models\Batch;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $attendanceService) {}

    public function getStudentAttendance(Request $request): JsonResponse
    {
        $this->authorize('viewAnyAttendance');

        $request->validate([
            'batch_id' => ['required', 'integer'],
            'date'     => ['required', 'date'],
        ]);

        $batch = Batch::findOrFail($request->integer('batch_id'));

        if ($batch->status !== 'active') {
            return response()->json(['message' => 'Attendance can only be marked for active batches.'], 422);
        }

        $records = $this->attendanceService->getStudentAttendance(
            $batch->id,
            $request->input('date'),
        );

        return response()->json(['data' => $records]);
    }

    public function markStudentAttendance(MarkStudentAttendanceRequest $request): JsonResponse
    {
        $this->authorize('markAttendance');

        $summary = $this->attendanceService->markStudentAttendance($request->validated());

        return response()->json([
            'data'    => $summary,
            'message' => 'Attendance saved successfully.',
        ]);
    }

    public function getTeacherAttendance(Request $request): JsonResponse
    {
        $this->authorize('viewAnyAttendance');

        $request->validate(['date' => ['required', 'date']]);

        $records = $this->attendanceService->getTeacherAttendance(
            $request->input('date'),
            $request->user()->tenant_id,
        );

        return response()->json(['data' => $records]);
    }

    public function markTeacherAttendance(MarkTeacherAttendanceRequest $request): JsonResponse
    {
        $this->authorize('markAttendance');

        $summary = $this->attendanceService->markTeacherAttendance($request->validated());

        return response()->json([
            'data'    => $summary,
            'message' => 'Teacher attendance saved successfully.',
        ]);
    }

    public function absentList(Request $request): JsonResponse
    {
        $this->authorize('viewAnyAttendance');

        $request->validate(['date' => ['required', 'date']]);

        $list = $this->attendanceService->getAbsentList(
            $request->input('date'),
            $request->user()->tenant_id,
        );

        return response()->json(['data' => $list]);
    }

    public function studentReport(Request $request): JsonResponse
    {
        $this->authorize('viewAnyAttendance');

        $request->validate([
            'batch_id'   => ['nullable', 'integer'],
            'student_id' => ['nullable', 'integer'],
            'date_from'  => ['nullable', 'date'],
            'date_to'    => ['nullable', 'date', 'after_or_equal:date_from'],
            'status'     => ['nullable', 'in:present,absent,late'],
            'per_page'   => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'       => ['nullable', 'integer', 'min:1'],
        ]);

        $result = $this->attendanceService->getStudentReport($request->only([
            'batch_id', 'student_id', 'date_from', 'date_to', 'status', 'per_page', 'page',
        ]));

        return response()->json($result);
    }
}
