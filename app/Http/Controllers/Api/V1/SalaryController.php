<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Salary\StoreSalaryPaymentRequest;
use App\Http\Resources\SalaryPaymentResource;
use App\Services\SalaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
    public function __construct(private SalaryService $salaryService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewSalary');

        $payments = $this->salaryService->list($request->only([
            'user_id', 'month', 'per_page',
        ]));

        return SalaryPaymentResource::collection($payments)->response();
    }

    public function store(StoreSalaryPaymentRequest $request): JsonResponse
    {
        $this->authorize('createSalary');

        $payment = $this->salaryService->collect($request->validated());

        return SalaryPaymentResource::make($payment)->response()->setStatusCode(201);
    }

    public function dues(Request $request): JsonResponse
    {
        $this->authorize('viewSalary');

        $request->validate([
            'month' => ['required', 'date_format:Y-m'],
        ]);

        $dues = $this->salaryService->getDues(
            $request->input('month'),
            $request->user()->tenant_id,
        );

        return response()->json(['data' => $dues]);
    }
}
