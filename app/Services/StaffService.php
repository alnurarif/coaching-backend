<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class StaffService
{
    public function list(array $filters): LengthAwarePaginator
    {
        return User::with('roles:id,name')
            ->whereHas('roles')
            ->whereDoesntHave('roles', fn($q) => $q->whereIn('name', ['owner', 'teacher']))
            ->where('tenant_id', auth()->user()->tenant_id)
            ->when($filters['search'] ?? null, fn($q, $v) =>
                $q->where(function ($sq) use ($v) {
                    $sq->where('name', 'like', "%{$v}%")
                       ->orWhere('email', 'like', "%{$v}%")
                       ->orWhere('phone', 'like', "%{$v}%");
                })
            )
            ->when($filters['role'] ?? null, fn($q, $v) =>
                $q->whereHas('roles', fn($rq) => $rq->where('name', $v))
            )
            ->orderBy('name')
            ->paginate(min(100, (int) ($filters['per_page'] ?? 15)));
    }

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $auth = auth()->user();

            $user = User::create([
                'tenant_id' => $auth->tenant_id,
                'branch_id' => $auth->branch_id,
                'name'      => $data['name'],
                'email'     => $data['email'],
                'phone'     => $data['phone'] ?? null,
                'password'  => $data['password'], // User model 'hashed' cast handles bcrypt
                'is_active' => true,
            ]);

            $user->assignRole($data['role']);

            return $user->load('roles:id,name');
        });
    }

    public function update(User $staff, array $data): User
    {
        return DB::transaction(function () use ($staff, $data) {
            $userData = array_filter([
                'name'      => $data['name'] ?? null,
                'email'     => $data['email'] ?? null,
                'phone'     => $data['phone'] ?? null,
                'is_active' => $data['is_active'] ?? null,
            ], fn($v) => $v !== null);

            if (!empty($data['password'])) {
                $userData['password'] = $data['password']; // User model 'hashed' cast handles bcrypt
            }

            $staff->update($userData);

            if (!empty($data['role'])) {
                $staff->syncRoles([$data['role']]);
            }

            return $staff->fresh('roles:id,name');
        });
    }

    public function delete(User $staff): void
    {
        $staff->delete();
    }
}
