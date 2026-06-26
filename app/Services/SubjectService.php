<?php

namespace App\Services;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Collection;

class SubjectService
{
    public function list(array $filters = []): Collection
    {
        return Subject::withCount('exams')
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', (bool) $filters['is_active']))
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): Subject
    {
        return Subject::create([
            'name'      => $data['name'],
            'code'      => $data['code'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function update(Subject $subject, array $data): Subject
    {
        $subject->update(array_filter([
            'name'      => $data['name']      ?? null,
            'code'      => $data['code']      ?? null,
            'is_active' => $data['is_active'] ?? null,
        ], fn($v) => $v !== null));

        return $subject->fresh();
    }

    public function delete(Subject $subject): void
    {
        $subject->delete();
    }
}
