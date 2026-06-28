<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterTenantRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class TenantRegistrationController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function store(RegisterTenantRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return response()->json([
            'data'    => UserResource::make($result['user']),
            'token'   => $result['token'],
            'message' => 'Registration successful. Welcome to your coaching center!',
        ], 201);
    }
}
