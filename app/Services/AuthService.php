<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $freePlan = Plan::where('slug', 'free')->firstOrFail();

            $tenant = Tenant::create([
                'name'      => $data['center_name'],
                'slug'      => $this->uniqueSlug($data['center_name']),
                'email'     => $data['email'],
                'phone'     => $data['phone'] ?? null,
                'is_active' => true,
                'plan_id'   => $freePlan->id,
            ]);

            $branch = Branch::create([
                'tenant_id' => $tenant->id,
                'name'      => $data['center_name'],
                'is_active' => true,
            ]);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'name'      => $data['name'],
                'email'     => $data['email'],
                'phone'     => $data['phone'] ?? null,
                'password'  => $data['password'],
                'is_active' => true,
            ]);

            $user->assignRole('owner');

            TenantSubscription::create([
                'tenant_id'  => $tenant->id,
                'plan_id'    => $freePlan->id,
                'status'     => 'active',
                'started_at' => now(),
                'ends_at'    => null,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            $user->load(['tenant.plan', 'branch']);

            return ['user' => $user, 'token' => $token];
        });
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 1;

        while (Tenant::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public function login(array $data): array
    {
        $user = User::with(['tenant.plan', 'branch'])
            ->where('email', $data['email'])
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated.'],
            ]);
        }

        if (! $user->tenant?->is_active) {
            throw ValidationException::withMessages([
                'email' => ['This account is suspended. Please contact support.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    public function me(User $user): User
    {
        return $user->load(['tenant.plan', 'branch']);
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
