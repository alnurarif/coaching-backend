<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateAccountRequest;
use App\Http\Requests\Settings\UpdateCenterRequest;
use App\Http\Resources\UserResource;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    public function __construct(private SettingsService $settingsService) {}

    public function updateCenter(UpdateCenterRequest $request): JsonResponse
    {
        $this->authorize('updateCenterSettings');

        $tenant = $this->settingsService->updateCenter($request->validated());

        return response()->json([
            'data' => [
                'id'      => $tenant->id,
                'name'    => $tenant->name,
                'slug'    => $tenant->slug,
                'phone'   => $tenant->phone,
                'email'   => $tenant->email,
                'address' => $tenant->address,
                'logo'    => $tenant->logo,
            ],
        ]);
    }

    public function updateAccount(UpdateAccountRequest $request): JsonResponse
    {
        $user = $this->settingsService->updateAccount($request->validated());

        return response()->json(['data' => new UserResource($user)]);
    }
}
