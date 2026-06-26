<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subject\StoreSubjectRequest;
use App\Http\Requests\Subject\UpdateSubjectRequest;
use App\Http\Resources\SubjectResource;
use App\Models\Subject;
use App\Services\SubjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function __construct(private SubjectService $subjectService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAnySubject');

        $subjects = $this->subjectService->list($request->only(['is_active']));

        return SubjectResource::collection($subjects)->response();
    }

    public function store(StoreSubjectRequest $request): JsonResponse
    {
        $this->authorize('createSubject');

        $subject = $this->subjectService->create($request->validated());

        return SubjectResource::make($subject)->response()->setStatusCode(201);
    }

    public function update(UpdateSubjectRequest $request, Subject $subject): JsonResponse
    {
        $this->authorize('updateSubject', $subject);

        $subject = $this->subjectService->update($subject, $request->validated());

        return SubjectResource::make($subject)->response();
    }

    public function destroy(Subject $subject): JsonResponse
    {
        $this->authorize('deleteSubject', $subject);

        $this->subjectService->delete($subject);

        return response()->json(null, 204);
    }
}
