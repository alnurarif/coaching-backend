<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Student;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class PlanService
{
    public function checkLimit(string $resource): void
    {
        $tenant = auth()->user()->tenant;

        if (! $tenant || ! $tenant->plan) {
            return;
        }

        $plan  = $tenant->plan;
        $limit = $plan->{$resource . '_limit'};

        if ($limit === null) {
            return; // Unlimited (Enterprise)
        }

        $count = match ($resource) {
            'students' => Student::where('tenant_id', $tenant->id)->count(),
            'branches' => Branch::where('tenant_id', $tenant->id)->count(),
            'staff'    => User::where('tenant_id', $tenant->id)->where('is_active', true)
                              ->whereDoesntHave('roles', fn($q) => $q->where('name', 'owner'))
                              ->count(),
            default    => 0,
        };

        if ($count >= $limit) {
            throw ValidationException::withMessages([
                $resource => ["Your {$plan->name} plan allows a maximum of {$limit} {$resource}. Please upgrade your plan."],
            ]);
        }
    }

    public function canExport(): bool
    {
        $plan = auth()->user()?->tenant?->plan;

        return $plan?->can_export ?? false;
    }

    public function getReportsLevel(): string
    {
        $plan = auth()->user()?->tenant?->plan;

        return $plan?->reports_level ?? 'basic';
    }
}
