<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fee\StoreFeeCollectionRequest;
use App\Http\Resources\FeeCollectionResource;
use App\Models\FeeCollection;
use App\Services\FeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeeController extends Controller
{
    public function __construct(private FeeService $feeService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAnyFee');

        $fees = $this->feeService->list($request->only([
            'search', 'student_id', 'batch_id', 'fee_type', 'month',
            'payment_method', 'date_from', 'date_to', 'per_page',
        ]));

        return FeeCollectionResource::collection($fees)->response();
    }

    public function store(StoreFeeCollectionRequest $request): JsonResponse
    {
        $this->authorize('createFee');

        $fee = $this->feeService->collect($request->validated());

        return FeeCollectionResource::make($fee)->response()->setStatusCode(201);
    }

    public function show(FeeCollection $fee): JsonResponse
    {
        $this->authorize('viewAnyFee');

        return FeeCollectionResource::make(
            $this->feeService->show($fee)
        )->response();
    }

    public function dues(Request $request): JsonResponse
    {
        $this->authorize('viewAnyFee');

        $request->validate([
            'month'    => ['required', 'date_format:Y-m'],
            'batch_id' => ['nullable', 'integer'],
        ]);

        $dues = $this->feeService->getDues(
            $request->input('month'),
            $request->user()->tenant_id,
            $request->integer('batch_id') ?: null,
        );

        return response()->json(['data' => $dues]);
    }

    public function summary(Request $request): JsonResponse
    {
        $this->authorize('viewAnyFee');

        $summary = $this->feeService->getSummary($request->user()->tenant_id);

        return response()->json(['data' => $summary]);
    }
}
