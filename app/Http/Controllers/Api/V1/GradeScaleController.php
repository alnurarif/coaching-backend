<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\GradeScale\SyncGradeScaleRequest;
use App\Http\Resources\GradeScaleResource;
use App\Services\GradeScaleService;
use Illuminate\Http\JsonResponse;

class GradeScaleController extends Controller
{
    public function __construct(private GradeScaleService $gradeScaleService) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAnySubject');

        $scales = $this->gradeScaleService->list();

        return GradeScaleResource::collection($scales)->response();
    }

    public function sync(SyncGradeScaleRequest $request): JsonResponse
    {
        $this->authorize('manageExamConfig');

        $scales = $this->gradeScaleService->sync($request->validated('scales'));

        return GradeScaleResource::collection($scales)->response();
    }
}
