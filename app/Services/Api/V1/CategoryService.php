<?php

namespace App\Services\Api\V1;

use App\Models\Category;
use App\Repositories\Api\V1\CategoryRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Exception;

class CategoryService
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository
    ) {}

    public function createCategory(array $data): Category
    {
        return $this->categoryRepository->create($data);
    }

    public function updateCategory(string $id, array $data): Category
    {
        $category = $this->categoryRepository->find($id);

        if (!$category) {
            throw new ModelNotFoundException('Category not found');
        }

        return $this->categoryRepository->update($category, $data);
    }

    public function deleteCategory(string $id): void
    {
        $category = $this->categoryRepository->find($id);

        if (!$category) {
            throw new ModelNotFoundException('Category not found');
        }

        if (!Auth::check() || Auth::user()->role !== 'admin') {
            throw new Exception('You are not authorized to delete this category', Response::HTTP_FORBIDDEN);
        }

        if ($this->categoryRepository->hasBooks($category)) {
            throw new Exception('Category cannot be deleted because it is associated with books', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->categoryRepository->delete($category);
    }
}
