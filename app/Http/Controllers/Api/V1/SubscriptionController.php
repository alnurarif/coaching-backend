<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(private SubscriptionService $subscriptionService) {}

    public function current(Request $request): JsonResponse
    {
        $data = $this->subscriptionService->current($request->user());

        return response()->json(['data' => $data]);
    }

    public function checkout(Request $request): JsonResponse
    {
        if (! $request->user()->hasRole('owner')) {
            abort(403, 'Only the account owner can manage subscriptions.');
        }

        $request->validate(['plan_id' => ['required', 'integer', 'exists:plans,id']]);

        $paymentUrl = $this->subscriptionService->initiateCheckout(
            $request->user(),
            $request->integer('plan_id')
        );

        return response()->json(['data' => ['payment_url' => $paymentUrl]]);
    }
}
