<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private SubscriptionService $subscriptionService) {}

    public function ipn(Request $request): void
    {
        $this->subscriptionService->handleIPN($request->all());
    }

    public function success(Request $request): RedirectResponse
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:5173');

        return redirect("{$frontendUrl}/payment/success?tran_id={$request->query('tran_id')}");
    }

    public function fail(Request $request): RedirectResponse
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:5173');

        return redirect("{$frontendUrl}/payment/fail");
    }

    public function cancel(Request $request): RedirectResponse
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:5173');

        return redirect("{$frontendUrl}/payment/fail?cancelled=1");
    }
}
