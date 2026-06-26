<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreTeacherRequest;
use App\Http\Requests\Teacher\UpdateTeacherRequest;
use App\Http\Resources\TeacherResource;
use App\Models\User;
use App\Services\TeacherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function __construct(private TeacherService $teacherService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAnyTeacher');

        $teachers = $this->teacherService->list($request->only([
            'search', 'is_active', 'per_page',
        ]));

        return TeacherResource::collection($teachers)->response();
    }

    public function store(StoreTeacherRequest $request): JsonResponse
    {
        $this->authorize('createTeacher');

        $teacher = $this->teacherService->create($request->validated());

        return TeacherResource::make($teacher)->response()->setStatusCode(201);
    }

    public function show(User $teacher): JsonResponse
    {
        $this->authorize('viewTeacher', $teacher);

        return TeacherResource::make(
            $this->teacherService->show($teacher)
        )->response();
    }

    public function update(UpdateTeacherRequest $request, User $teacher): JsonResponse
    {
        $this->authorize('updateTeacher', $teacher);

        $teacher = $this->teacherService->update($teacher, $request->validated());

        return TeacherResource::make($teacher)->response();
    }

    public function destroy(User $teacher): JsonResponse
    {
        $this->authorize('deleteTeacher', $teacher);

        $this->teacherService->delete($teacher);

        return response()->json(['message' => 'Teacher removed successfully.']);
    }
}
