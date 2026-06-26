<?php

namespace App\Services;

use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Collection;

class ExpenseCategoryService
{
    public function list(): Collection
    {
        return ExpenseCategory::orderBy('name')->get();
    }

    public function create(array $data): ExpenseCategory
    {
        return ExpenseCategory::create([
            'tenant_id' => auth()->user()->tenant_id,
            'name'      => $data['name'],
            'color'     => $data['color'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function update(ExpenseCategory $category, array $data): ExpenseCategory
    {
        $category->update($data);
        return $category->fresh();
    }

    public function delete(ExpenseCategory $category): void
    {
        if ($category->expenses()->exists()) {
            abort(422, 'Cannot delete a category that has existing expenses. Reassign expenses first.');
        }

        $category->delete();
    }
}
