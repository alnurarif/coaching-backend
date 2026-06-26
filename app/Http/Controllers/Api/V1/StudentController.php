<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreStudentRequest;
use App\Http\Requests\Student\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function __construct(private StudentService $studentService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Student::class);

        $students = $this->studentService->list($request->only([
            'search', 'status', 'branch_id', 'per_page', 'sort_by', 'sort_dir',
        ]));

        return StudentResource::collection($students)->response();
    }

    public function store(StoreStudentRequest $request): JsonResponse
    {
        $this->authorize('create', Student::class);

        $student = $this->studentService->create($request->validated());

        return StudentResource::make($student)->response()->setStatusCode(201);
    }

    public function show(Student $student): JsonResponse
    {
        $this->authorize('view', $student);

        return StudentResource::make(
            $this->studentService->show($student)
        )->response();
    }

    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        $this->authorize('update', $student);

        $student = $this->studentService->update($student, $request->validated());

        return StudentResource::make($student)->response();
    }

    public function uploadPhoto(Request $request, Student $student): JsonResponse
    {
        $this->authorize('update', $student);

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $path = $this->studentService->uploadPhoto($student, $request->file('photo'));

        return response()->json([
            'data' => ['photo' => asset('storage/' . $path)],
        ]);
    }

    public function deletePhoto(Student $student): JsonResponse
    {
        $this->authorize('update', $student);

        $this->studentService->deletePhoto($student);

        return response()->json(['data' => ['photo' => null]]);
    }

    public function destroy(Student $student): JsonResponse
    {
        $this->authorize('delete', $student);

        $this->studentService->delete($student);

        return response()->json(['message' => 'Student deleted successfully.']);
    }
}
