<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SettingsService
{
    public function updateCenter(array $data): Tenant
    {
        $tenant = auth()->user()->tenant;

        $tenant->update(array_filter([
            'name'    => $data['name']    ?? null,
            'phone'   => $data['phone']   ?? null,
            'email'   => $data['email']   ?? null,
            'address' => $data['address'] ?? null,
        ], fn($v) => $v !== null));

        return $tenant->fresh();
    }

    public function updateAccount(array $data): User
    {
        $user = auth()->user();

        $userData = array_filter([
            'name'  => $data['name']  ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
        ], fn($v) => $v !== null);

        if (!empty($data['new_password'])) {
            $userData['password'] = $data['new_password']; // User model 'hashed' cast handles bcrypt
        }

        $user->update($userData);

        return $user->fresh(['tenant', 'branch']);
    }
}
