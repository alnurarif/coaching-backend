<?php

namespace App\Services;

use App\Models\ExamType;
use Illuminate\Database\Eloquent\Collection;

class ExamTypeService
{
    public function list(): Collection
    {
        return ExamType::orderBy('sort_order')->orderBy('name')->get();
    }

    public function create(array $data): ExamType
    {
        return ExamType::create([
            'name'       => $data['name'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active'  => $data['is_active'] ?? true,
        ]);
    }

    public function update(ExamType $examType, array $data): ExamType
    {
        $examType->update(array_filter([
            'name'       => $data['name']       ?? null,
            'sort_order' => $data['sort_order'] ?? null,
            'is_active'  => $data['is_active']  ?? null,
        ], fn($v) => $v !== null));

        return $examType->fresh();
    }

    public function delete(ExamType $examType): void
    {
        $examType->delete();
    }
}
