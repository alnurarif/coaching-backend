<?php

namespace App\Services;

use App\Models\Batch;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BatchService
{
    public function list(array $filters): LengthAwarePaginator
    {
        $allowedSort = ['name', 'created_at', 'start_date', 'fee_amount', 'status'];
        $sortBy  = in_array($filters['sort_by'] ?? null, $allowedSort, true) ? ($filters['sort_by'] ?? 'created_at') : 'created_at';
        $sortDir = in_array(strtolower($filters['sort_dir'] ?? 'desc'), ['asc', 'desc'], true) ? ($filters['sort_dir'] ?? 'desc') : 'desc';

        return Batch::with(['branch', 'teacher'])
            ->withCount('students')
            ->when($filters['search'] ?? null, fn($q, $v) =>
                $q->where(function ($sq) use ($v) {
                    $sq->where('name', 'like', "%{$v}%")
                       ->orWhere('subject', 'like', "%{$v}%");
                })
            )
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($filters['branch_id'] ?? null, fn($q, $v) => $q->where('branch_id', $v))
            ->orderBy($sortBy, $sortDir)
            ->paginate(min(100, (int) ($filters['per_page'] ?? 15)));
    }

    public function create(array $data): Batch
    {
        $batch = Batch::create([
            ...$data,
            'branch_id' => $data['branch_id'] ?? auth()->user()->branch_id,
            'status'    => $data['status'] ?? 'active',
        ]);

        return $batch->load(['branch', 'teacher']);
    }

    public function update(Batch $batch, array $data): Batch
    {
        $batch->update($data);

        return $batch->fresh(['branch', 'teacher'])->loadCount('students');
    }

    public function show(Batch $batch): Batch
    {
        return $batch->load(['branch', 'teacher', 'students']);
    }

    public function delete(Batch $batch): void
    {
        DB::transaction(function () use ($batch) {
            $batch->students()->detach();
            $batch->delete();
        });
    }

    public function assignStudents(Batch $batch, array $studentIds, string $joinedAt): Batch
    {
        return DB::transaction(function () use ($batch, $studentIds, $joinedAt) {
            // Lock the batch row to prevent concurrent over-enrollment
            $batch = Batch::lockForUpdate()->findOrFail($batch->id);
            $batch->loadCount('students');

            $newCount     = count(array_unique($studentIds));
            $currentCount = $batch->students_count;
            $alreadyIn    = $batch->students()->whereIn('students.id', $studentIds)->count();
            $toAdd        = $newCount - $alreadyIn;

            if (($currentCount + $toAdd) > $batch->capacity) {
                throw ValidationException::withMessages([
                    'student_ids' => [
                        "Batch capacity ({$batch->capacity}) exceeded. Only " .
                        ($batch->capacity - $currentCount) . ' seat(s) available.',
                    ],
                ]);
            }

            $syncData = collect($studentIds)->mapWithKeys(fn($id) => [
                $id => ['joined_at' => $joinedAt],
            ])->toArray();

            $batch->students()->syncWithoutDetaching($syncData);

            return $batch->load(['branch', 'teacher', 'students']);
        });
    }

    public function removeStudent(Batch $batch, int $studentId): void
    {
        $batch->students()->detach($studentId);
    }
}
