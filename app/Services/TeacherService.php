<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TeacherService
{
    public function list(array $filters): LengthAwarePaginator
    {
        return User::with(['teacherProfile', 'branch'])
            ->withCount('batches')
            ->whereHas('roles', fn($q) => $q->where('name', 'teacher'))
            ->where('tenant_id', auth()->user()->tenant_id)
            ->when($filters['search'] ?? null, fn($q, $v) =>
                $q->where(function ($sq) use ($v) {
                    $sq->where('name', 'like', "%{$v}%")
                       ->orWhere('email', 'like', "%{$v}%")
                       ->orWhere('phone', 'like', "%{$v}%");
                })
            )
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', (bool) $filters['is_active']))
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
                'password'  => $data['password'],
                'is_active' => true,
            ]);

            $user->assignRole('teacher');

            $user->teacherProfile()->create([
                'subject'       => $data['subject'] ?? null,
                'qualification' => $data['qualification'] ?? null,
                'address'       => $data['address'] ?? null,
                'join_date'     => $data['join_date'] ?? null,
                'base_salary'   => $data['base_salary'] ?? 0,
            ]);

            return $user->load(['teacherProfile', 'branch']);
        });
    }

    public function update(User $teacher, array $data): User
    {
        return DB::transaction(function () use ($teacher, $data) {
            $userData = array_filter([
                'name'      => $data['name'] ?? null,
                'email'     => $data['email'] ?? null,
                'phone'     => $data['phone'] ?? null,
                'is_active' => $data['is_active'] ?? null,
            ], fn($v) => $v !== null);

            if (!empty($data['password'])) {
                $userData['password'] = $data['password'];
            }

            $teacher->update($userData);

            $profileData = array_filter([
                'subject'       => $data['subject'] ?? null,
                'qualification' => $data['qualification'] ?? null,
                'address'       => $data['address'] ?? null,
                'join_date'     => $data['join_date'] ?? null,
                'base_salary'   => isset($data['base_salary']) ? $data['base_salary'] : null,
            ], fn($v) => $v !== null);

            if (!empty($profileData)) {
                $teacher->teacherProfile()->updateOrCreate(
                    ['user_id' => $teacher->id],
                    $profileData,
                );
            }

            return $teacher->fresh(['teacherProfile', 'branch'])->loadCount('batches');
        });
    }

    public function show(User $teacher): User
    {
        return $teacher->load(['teacherProfile', 'branch', 'batches']);
    }

    public function delete(User $teacher): void
    {
        $teacher->delete();
    }
}
