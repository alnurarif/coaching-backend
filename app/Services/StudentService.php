<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StudentService
{
    public function __construct(private PlanService $planService) {}

    public function list(array $filters): LengthAwarePaginator
    {
        $allowedSort = ['name', 'created_at', 'admission_date', 'student_id', 'status'];
        $sortBy  = in_array($filters['sort_by'] ?? null, $allowedSort, true) ? ($filters['sort_by'] ?? 'created_at') : 'created_at';
        $sortDir = in_array(strtolower($filters['sort_dir'] ?? 'desc'), ['asc', 'desc'], true) ? ($filters['sort_dir'] ?? 'desc') : 'desc';

        $query = Student::with(['branch', 'guardian'])
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('student_id', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($filters['branch_id'] ?? null, fn($q, $v) => $q->where('branch_id', $v))
            ->orderBy($sortBy, $sortDir);

        return $query->paginate(min(100, (int) ($filters['per_page'] ?? 15)));
    }

    public function create(array $data): Student
    {
        $this->planService->checkLimit('students');

        return DB::transaction(function () use ($data) {
            $guardianData = $data['guardian'] ?? null;
            unset($data['guardian']);

            $photoPath = null;
            if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
                $photoPath = $data['photo']->store('students/photos', 'public');
            }
            unset($data['photo']);

            $student = Student::create([
                ...$data,
                'student_id' => $this->generateStudentId(),
                'photo'      => $photoPath,
                'status'     => $data['status'] ?? 'active',
                'branch_id'  => $data['branch_id'] ?? auth()->user()->branch_id,
            ]);

            if ($guardianData) {
                $student->guardian()->create($guardianData);
            }

            return $student->load(['branch', 'guardian']);
        });
    }

    public function update(Student $student, array $data): Student
    {
        return DB::transaction(function () use ($student, $data) {
            $guardianData = $data['guardian'] ?? null;
            unset($data['guardian']);

            $oldPhoto = null;
            if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
                $oldPhoto      = $student->photo;
                $data['photo'] = $data['photo']->store('students/photos', 'public');
            }

            $student->update($data);

            if ($oldPhoto) {
                Storage::disk('public')->delete($oldPhoto);
            }

            if ($guardianData !== null) {
                $student->guardian()->updateOrCreate(
                    ['student_id' => $student->id],
                    $guardianData,
                );
            }

            return $student->fresh(['branch', 'guardian']);
        });
    }

    public function uploadPhoto(Student $student, UploadedFile $file): string
    {
        $oldPath = $student->photo;
        $path    = $file->store('students/photos', 'public');
        $student->update(['photo' => $path]);
        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }
        return $path;
    }

    public function deletePhoto(Student $student): void
    {
        if ($student->photo) {
            $path = $student->photo;
            $student->update(['photo' => null]);
            Storage::disk('public')->delete($path);
        }
    }

    public function delete(Student $student): void
    {
        DB::transaction(function () use ($student) {
            $student->batches()->detach();
            $student->delete();
        });
    }

    public function show(Student $student): Student
    {
        return $student->load(['branch', 'guardian']);
    }

    private function generateStudentId(): string
    {
        $year = now()->year;

        return DB::transaction(function () use ($year) {
            $last = DB::table('students')
                ->where('tenant_id', auth()->user()->tenant_id)
                ->where('student_id', 'like', "CS-{$year}-%")
                ->lockForUpdate()
                ->orderByDesc('student_id')
                ->value('student_id');

            $next = $last ? ((int) substr($last, -4)) + 1 : 1;

            return sprintf('CS-%d-%04d', $year, $next);
        });
    }
}
