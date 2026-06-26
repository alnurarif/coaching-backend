<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Batch\AssignStudentsRequest;
use App\Http\Requests\Batch\StoreBatchRequest;
use App\Http\Requests\Batch\UpdateBatchRequest;
use App\Http\Resources\BatchResource;
use App\Models\Batch;
use App\Services\BatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    public function __construct(private BatchService $batchService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Batch::class);

        $batches = $this->batchService->list($request->only([
            'search', 'status', 'branch_id', 'per_page', 'sort_by', 'sort_dir',
        ]));

        return BatchResource::collection($batches)->response();
    }

    public function store(StoreBatchRequest $request): JsonResponse
    {
        $this->authorize('create', Batch::class);

        $batch = $this->batchService->create($request->validated());

        return BatchResource::make($batch)->response()->setStatusCode(201);
    }

    public function show(Batch $batch): JsonResponse
    {
        $this->authorize('view', $batch);

        return BatchResource::make(
            $this->batchService->show($batch)
        )->response();
    }

    public function update(UpdateBatchRequest $request, Batch $batch): JsonResponse
    {
        $this->authorize('update', $batch);

        $batch = $this->batchService->update($batch, $request->validated());

        return BatchResource::make($batch)->response();
    }

    public function destroy(Batch $batch): JsonResponse
    {
        $this->authorize('delete', $batch);

        $this->batchService->delete($batch);

        return response()->json(['message' => 'Batch deleted successfully.']);
    }

    public function assignStudents(AssignStudentsRequest $request, Batch $batch): JsonResponse
    {
        $this->authorize('update', $batch);

        $batch = $this->batchService->assignStudents(
            $batch,
            $request->validated('student_ids'),
            $request->validated('joined_at'),
        );

        return BatchResource::make($batch)->response();
    }

    public function removeStudent(Batch $batch, int $studentId): JsonResponse
    {
        $this->authorize('update', $batch);

        $this->batchService->removeStudent($batch, $studentId);

        return response()->json(['message' => 'Student removed from batch.']);
    }
}
