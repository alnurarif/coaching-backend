<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseCategory\StoreExpenseCategoryRequest;
use App\Http\Requests\ExpenseCategory\UpdateExpenseCategoryRequest;
use App\Http\Resources\ExpenseCategoryResource;
use App\Models\ExpenseCategory;
use App\Services\ExpenseCategoryService;
use Illuminate\Http\JsonResponse;

class ExpenseCategoryController extends Controller
{
    public function __construct(private ExpenseCategoryService $service) {}

    public function index(): JsonResponse
    {
        $this->authorize('manageExpenseCategories');

        return response()->json([
            'data' => ExpenseCategoryResource::collection($this->service->list()),
        ]);
    }

    public function store(StoreExpenseCategoryRequest $request): JsonResponse
    {
        $this->authorize('manageExpenseCategories');

        $category = $this->service->create($request->validated());

        return response()->json(['data' => new ExpenseCategoryResource($category)], 201);
    }

    public function update(UpdateExpenseCategoryRequest $request, ExpenseCategory $expenseCategory): JsonResponse
    {
        $this->authorize('manageExpenseCategories');

        $category = $this->service->update($expenseCategory, $request->validated());

        return response()->json(['data' => new ExpenseCategoryResource($category)]);
    }

    public function destroy(ExpenseCategory $expenseCategory): JsonResponse
    {
        $this->authorize('manageExpenseCategories');

        $this->service->delete($expenseCategory);

        return response()->json(null, 204);
    }
}
