<?php

namespace App\Repositories\Api\V1;

use App\Models\Category;
use App\Traits\Api\V1\PaginationTrait;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoryRepository
{
    use PaginationTrait;
    public function getFilteredCategories(Request $request): LengthAwarePaginator
    {
        $perPage = $this->sanitizePerPage($request->get('per_page', 10));
        $search = $request->get('search');

        return Category::latest()
            ->when($search, fn($query) => $query->where('name', 'like', "%{$search}%"))
            ->withCount('books')
            ->paginate($perPage);
    }

    public function find(string $id): ?Category
    {
        return Category::find($id);
    }

    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);
        return $category->fresh();
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }

    public function hasBooks(Category $category): bool
    {
        return $category->books()->exists();
    }
}
