<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\StoreStaffRequest;
use App\Http\Requests\Staff\UpdateStaffRequest;
use App\Http\Resources\StaffResource;
use App\Models\User;
use App\Services\StaffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StaffController extends Controller
{
    public function __construct(private StaffService $staffService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAnyStaff');

        $staff = $this->staffService->list($request->only(['search', 'role', 'per_page']));

        return StaffResource::collection($staff);
    }

    public function store(StoreStaffRequest $request): JsonResponse
    {
        $this->authorize('createStaff');

        $staff = $this->staffService->create($request->validated());

        return response()->json(['data' => new StaffResource($staff)], 201);
    }

    public function update(UpdateStaffRequest $request, User $staff): JsonResponse
    {
        $this->authorize('updateStaff', $staff);

        $updated = $this->staffService->update($staff, $request->validated());

        return response()->json(['data' => new StaffResource($updated)]);
    }

    public function destroy(User $staff): JsonResponse
    {
        $this->authorize('deleteStaff', $staff);

        $this->staffService->delete($staff);

        return response()->json(null, 204);
    }
}
