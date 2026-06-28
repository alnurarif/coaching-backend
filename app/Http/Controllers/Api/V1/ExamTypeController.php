<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExamType\StoreExamTypeRequest;
use App\Http\Requests\ExamType\UpdateExamTypeRequest;
use App\Http\Resources\ExamTypeResource;
use App\Models\ExamType;
use App\Services\ExamTypeService;
use Illuminate\Http\JsonResponse;

class ExamTypeController extends Controller
{
    public function __construct(private ExamTypeService $examTypeService) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAnySubject');

        $examTypes = $this->examTypeService->list();

        return ExamTypeResource::collection($examTypes)->response();
    }

    public function store(StoreExamTypeRequest $request): JsonResponse
    {
        $this->authorize('manageExamConfig');

        $examType = $this->examTypeService->create($request->validated());

        return ExamTypeResource::make($examType)->response()->setStatusCode(201);
    }

    public function update(UpdateExamTypeRequest $request, ExamType $examType): JsonResponse
    {
        $this->authorize('manageExamConfig');

        $examType = $this->examTypeService->update($examType, $request->validated());

        return ExamTypeResource::make($examType)->response();
    }

    public function destroy(ExamType $examType): JsonResponse
    {
        $this->authorize('manageExamConfig');

        $this->examTypeService->delete($examType);

        return response()->json(null, 204);
    }
}
