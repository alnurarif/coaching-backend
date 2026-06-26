<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\BulkMarksRequest;
use App\Http\Requests\Exam\StoreExamRequest;
use App\Http\Requests\Exam\UpdateExamRequest;
use App\Http\Resources\ExamResource;
use App\Http\Resources\ExamResultResource;
use App\Models\Exam;
use App\Services\ExamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function __construct(private ExamService $examService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAnyExam');

        $exams = $this->examService->list($request->only([
            'batch_id', 'subject_id', 'exam_type_id', 'status',
            'date_from', 'date_to', 'per_page',
        ]));

        return ExamResource::collection($exams)->response();
    }

    public function store(StoreExamRequest $request): JsonResponse
    {
        $this->authorize('createExam');

        $exam = $this->examService->create($request->validated());

        return ExamResource::make($exam)->response()->setStatusCode(201);
    }

    public function show(Exam $exam): JsonResponse
    {
        $this->authorize('viewExam', $exam);

        return ExamResource::make($this->examService->show($exam))->response();
    }

    public function update(UpdateExamRequest $request, Exam $exam): JsonResponse
    {
        $this->authorize('updateExam', $exam);

        $exam = $this->examService->update($exam, $request->validated());

        return ExamResource::make($exam)->response();
    }

    public function destroy(Exam $exam): JsonResponse
    {
        $this->authorize('deleteExam', $exam);

        $this->examService->delete($exam);

        return response()->json(['message' => 'Exam deleted successfully.']);
    }

    public function entrySheet(Exam $exam): JsonResponse
    {
        $this->authorize('viewExam', $exam);

        $data = $this->examService->getResultsForEntry($exam);

        return response()->json(['data' => $data]);
    }

    public function saveResults(BulkMarksRequest $request, Exam $exam): JsonResponse
    {
        $this->authorize('enterMarks', $exam);

        if ($exam->status === 'completed') {
            return response()->json(['message' => 'Cannot enter marks for a completed exam.'], 422);
        }

        $summary = $this->examService->saveResults($exam, $request->validated('records'));

        return response()->json([
            'data'    => $summary,
            'message' => 'Marks saved successfully.',
        ]);
    }

    public function resultSheet(Exam $exam): JsonResponse
    {
        $this->authorize('viewExam', $exam);

        $result = $this->examService->getResultSheet($exam);

        return response()->json($result);
    }

    public function meritList(Exam $exam): JsonResponse
    {
        $this->authorize('viewExam', $exam);

        $result = $this->examService->getMeritList($exam);

        return response()->json([
            'data'    => ExamResultResource::collection($result['results']),
            'summary' => $result['summary'],
        ]);
    }
}
