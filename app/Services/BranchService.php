<?php

namespace App\Services;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class BranchService
{
    public function __construct(private PlanService $planService) {}

    public function list(): Collection
    {
        return Branch::withCount(['students', 'users'])
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): Branch
    {
        $this->planService->checkLimit('branches');

        // BelongsToTenant trait auto-sets tenant_id on creating
        return Branch::create([
            'name'      => $data['name'],
            'phone'     => $data['phone'] ?? null,
            'address'   => $data['address'] ?? null,
            'is_active' => true,
        ]);
    }

    public function update(Branch $branch, array $data): Branch
    {
        $fillable = array_filter([
            'name'      => $data['name'] ?? null,
            'phone'     => $data['phone'] ?? null,
            'address'   => $data['address'] ?? null,
            'is_active' => $data['is_active'] ?? null,
        ], fn($v) => $v !== null);

        $branch->update($fillable);

        return $branch->fresh();
    }

    public function delete(Branch $branch): void
    {
        if ($branch->batches()->exists()) {
            throw ValidationException::withMessages([
                'branch' => 'Cannot delete a branch that has existing batches. Reassign or delete the batches first.',
            ]);
        }

        if ($branch->users()->exists()) {
            throw ValidationException::withMessages([
                'branch' => 'Cannot delete a branch that has assigned staff or teachers.',
            ]);
        }

        $branch->delete();
    }
}
