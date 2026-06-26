<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Requests\Expense\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function __construct(private ExpenseService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAnyExpense');

        $expenses = $this->service->list($request->only([
            'category_id', 'branch_id', 'date_from', 'date_to', 'per_page',
        ]));

        return response()->json([
            'data' => ExpenseResource::collection($expenses->items()),
            'meta' => [
                'current_page' => $expenses->currentPage(),
                'last_page'    => $expenses->lastPage(),
                'per_page'     => $expenses->perPage(),
                'total'        => $expenses->total(),
            ],
        ]);
    }

    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $this->authorize('createExpense');

        $expense = $this->service->create($request->validated());

        return response()->json(['data' => new ExpenseResource($expense)], 201);
    }

    public function show(Expense $expense): JsonResponse
    {
        $this->authorize('viewAnyExpense');

        return response()->json(['data' => new ExpenseResource(
            $expense->load(['category:id,name,color', 'branch:id,name', 'recordedBy:id,name'])
        )]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): JsonResponse
    {
        $this->authorize('updateExpense', $expense);

        $updated = $this->service->update($expense, $request->validated());

        return response()->json(['data' => new ExpenseResource($updated)]);
    }

    public function destroy(Expense $expense): JsonResponse
    {
        $this->authorize('deleteExpense', $expense);

        $this->service->delete($expense);

        return response()->json(null, 204);
    }
}
