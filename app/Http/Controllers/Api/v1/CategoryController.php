<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\CategoryRequest;
use App\Http\Resources\Category as CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $perPage = request()->get('per_page', 10);
        $search = request()->get('search', null);

        $categories = Category::latest()
            ->when($search, function ($query) use ($search) {
                return $query->where('name', 'like', "%{$search}%");
            })
            ->withCount('books')
            ->paginate($perPage);

        return CategoryResource::collection($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoryRequest $request)
    {
        $category = Category::create($request->validated());

        return new CategoryResource($category);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        return new CategoryResource($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CategoryRequest $request, Category $category)
    {
        $category->update($request->validated());

        return new CategoryResource($category);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        if (!(Auth::check() && Auth::user()->role === 'admin')) {
            return response()->json([
                'message' => 'You are not authorized to delete this category.',
            ], 403);
        }
        // Check if the category is used in any book
        if ($category->books()->exists()) {
            return response()->json([
                'message' => 'Category cannot be deleted because it is associated with books.',
            ], 422);
        }

        $category->delete();

        return response()->noContent();
    }
}
