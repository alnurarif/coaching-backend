<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Plan;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    public function current(User $user): array
    {
        $tenant       = $user->tenant()->with(['plan', 'subscriptions' => fn($q) => $q->latest()])->firstOrFail();
        $subscription = $tenant->subscriptions->first();

        return [
            'plan'         => $tenant->plan,
            'subscription' => $subscription,
            'usage'        => [
                'students' => Student::where('tenant_id', $tenant->id)->count(),
                'branches' => Branch::where('tenant_id', $tenant->id)->count(),
                'staff'    => User::where('tenant_id', $tenant->id)->where('is_active', true)->count(),
            ],
        ];
    }

    public function initiateCheckout(User $user, int $planId): string
    {
        $plan = Plan::findOrFail($planId);

        if ($plan->slug === 'free') {
            throw ValidationException::withMessages(['plan_id' => ['Cannot checkout the free plan.']]);
        }

        if ($plan->slug === 'enterprise') {
            throw ValidationException::withMessages(['plan_id' => ['Please contact sales for the Enterprise plan.']]);
        }

        $transactionId = 'SUB-' . strtoupper(Str::random(12));
        $tenant        = $user->tenant;

        // Create a pending subscription record
        $subscription = TenantSubscription::create([
            'tenant_id'      => $tenant->id,
            'plan_id'        => $plan->id,
            'status'         => 'pending',
            'payment_method' => 'sslcommerz',
            'transaction_id' => $transactionId,
            'amount_paid'    => $plan->price,
        ]);

        $backendUrl  = config('app.url');
        $paymentData = [
            'store_id'       => config('sslcommerz.store_id'),
            'store_passwd'   => config('sslcommerz.store_password'),
            'total_amount'   => number_format($plan->price, 2, '.', ''),
            'currency'       => 'BDT',
            'tran_id'        => $transactionId,
            'success_url'    => "{$backendUrl}/api/v1/payment/success?tran_id={$transactionId}",
            'fail_url'       => "{$backendUrl}/api/v1/payment/fail",
            'cancel_url'     => "{$backendUrl}/api/v1/payment/cancel",
            'ipn_url'        => "{$backendUrl}/api/v1/payment/ipn",
            'cus_name'       => $user->name,
            'cus_email'      => $user->email,
            'cus_phone'      => $user->phone ?? '01700000000',
            'cus_add1'       => $tenant->address ?? 'Bangladesh',
            'cus_city'       => 'Dhaka',
            'cus_country'    => 'Bangladesh',
            'product_name'   => $plan->name . ' Plan Subscription',
            'product_category' => 'SaaS Subscription',
            'product_profile'  => 'non-physical-goods',
            'shipping_method'  => 'NO',
        ];

        $response = Http::asForm()->post(config('sslcommerz.init_url'), $paymentData);

        if (! $response->successful()) {
            $subscription->delete();
            throw ValidationException::withMessages(['payment' => ['Payment gateway connection failed. Please try again.']]);
        }

        $result = $response->json();

        if (($result['status'] ?? '') !== 'SUCCESS') {
            $subscription->delete();
            $error = $result['failedreason'] ?? 'Payment initiation failed.';
            throw ValidationException::withMessages(['payment' => [$error]]);
        }

        return $result['GatewayPageURL'];
    }

    public function handleIPN(array $data): void
    {
        $transactionId = $data['tran_id'] ?? null;
        $valId         = $data['val_id'] ?? null;
        $status        = $data['status'] ?? null;

        if (! $transactionId || ! $valId || $status !== 'VALID') {
            return;
        }

        // Validate with SSLCommerz
        $validationResponse = Http::get(config('sslcommerz.validate_url'), [
            'val_id'       => $valId,
            'store_id'     => config('sslcommerz.store_id'),
            'store_passwd' => config('sslcommerz.store_password'),
            'format'       => 'json',
        ]);

        if (! $validationResponse->successful()) {
            Log::warning('SSLCommerz IPN validation request failed', ['tran_id' => $transactionId]);
            return;
        }

        $validated = $validationResponse->json();

        if (! in_array($validated['status'] ?? '', ['VALID', 'VALIDATED'], true)) {
            Log::warning('SSLCommerz IPN invalid status', ['tran_id' => $transactionId, 'response' => $validated]);
            return;
        }

        $subscription = TenantSubscription::where('transaction_id', $transactionId)
            ->where('status', 'pending')
            ->first();

        if (! $subscription) {
            return;
        }

        DB::transaction(function () use ($subscription) {
            // Deactivate old subscriptions
            TenantSubscription::where('tenant_id', $subscription->tenant_id)
                ->where('id', '!=', $subscription->id)
                ->where('status', 'active')
                ->update(['status' => 'expired']);

            $subscription->update([
                'status'     => 'active',
                'started_at' => now(),
                'ends_at'    => now()->addMonth(),
            ]);

            Tenant::where('id', $subscription->tenant_id)->update([
                'plan_id' => $subscription->plan_id,
            ]);
        });

        Log::info('SSLCommerz subscription activated', ['tran_id' => $transactionId, 'tenant_id' => $subscription->tenant_id]);
    }
}
