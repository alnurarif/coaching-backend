<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('students.view');
    }

    public function view(User $user, Student $student): bool
    {
        return $user->tenant_id === $student->tenant_id
            && $user->hasPermissionTo('students.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('students.create');
    }

    public function update(User $user, Student $student): bool
    {
        return $user->tenant_id === $student->tenant_id
            && $user->hasPermissionTo('students.create');
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->tenant_id === $student->tenant_id
            && $user->hasPermissionTo('students.delete');
    }
}
