<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\StoreBranchRequest;
use App\Http\Requests\Branch\UpdateBranchRequest;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Services\BranchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BranchController extends Controller
{
    public function __construct(private BranchService $branchService) {}

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAnyBranch');

        return BranchResource::collection($this->branchService->list());
    }

    public function store(StoreBranchRequest $request): JsonResponse
    {
        $this->authorize('createBranch');

        $branch = $this->branchService->create($request->validated());

        return response()->json(['data' => new BranchResource($branch)], 201);
    }

    public function update(UpdateBranchRequest $request, Branch $branch): JsonResponse
    {
        $this->authorize('updateBranch', $branch);

        $updated = $this->branchService->update($branch, $request->validated());

        return response()->json(['data' => new BranchResource($updated)]);
    }

    public function destroy(Branch $branch): JsonResponse
    {
        $this->authorize('deleteBranch', $branch);

        $this->branchService->delete($branch);

        return response()->json(null, 204);
    }
}
