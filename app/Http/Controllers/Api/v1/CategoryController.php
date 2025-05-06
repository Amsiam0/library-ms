<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\CategoryRequest;
use App\Http\Resources\Category as CategoryResource;
use App\Repositories\Api\V1\CategoryRepository;
use App\Services\Api\V1\CategoryService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly CategoryRepository $categoryRepository
    ) {}

    public function index(): JsonResponse
    {
        try {
            $categories = $this->categoryRepository->getFilteredCategories(request());
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], $e->getCode());
        }
        return CategoryResource::collection($categories)->response();
    }

    public function store(CategoryRequest $request): JsonResponse
    {
        try {
            $category = $this->categoryService->createCategory($request->validated());
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], $e->getCode());
        }
        return response()->json([
            'message' => 'Category created successfully',
            'data' => new CategoryResource($category)
        ], Response::HTTP_CREATED);
    }

    public function show(string $id): JsonResponse
    {
        try {
            $category = $this->categoryRepository->find($id);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], $e->getCode());
        }

        if (!$category) {
            return response()->json([
                'message' => 'Category not found'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'message' => 'Category retrieved successfully',
            'data' => new CategoryResource($category)
        ], Response::HTTP_OK);
    }

    public function update(CategoryRequest $request, string $id): JsonResponse
    {
        try {
            $category = $this->categoryService->updateCategory($id, $request->validated());
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], $e->getCode());
        }

        return response()->json([
            'message' => 'Category updated successfully',
            'data' => new CategoryResource($category)
        ], Response::HTTP_OK);
    }

    public function destroy(string $id): JsonResponse
    {

        try {
            $this->categoryService->deleteCategory($id);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], $e->getCode());
        }

        return response()->json([
            'message' => 'Category deleted successfully'
        ], Response::HTTP_OK);
    }
}
