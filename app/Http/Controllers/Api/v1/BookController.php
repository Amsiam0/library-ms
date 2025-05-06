<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\DeleteBookRequest;
use App\Http\Requests\Api\v1\IncreaseStockRequest;
use App\Http\Requests\Api\v1\StoreBookRequest;
use App\Http\Requests\Api\v1\UpdateBookRequest;
use App\Http\Resources\BookListResource;
use App\Http\Resources\BookResource;
use App\Repositories\Api\V1\BookRepository;
use App\Services\Api\V1\BookService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BookController extends Controller
{
    public function __construct(
        private readonly BookService $bookService,
        private readonly BookRepository $bookRepository
    ) {}

    public function index(): JsonResponse
    {
        try {
            $books = $this->bookRepository->getFilteredBooks(request());
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], $e->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        return BookListResource::collection($books)->response();
    }

    public function store(StoreBookRequest $request): JsonResponse
    {
        try {
            $book = $this->bookService->createBook($request->validated());
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], $e->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        return response()->json([
            'message' => 'Book created successfully',
            'data' => new BookResource($book)
        ], Response::HTTP_CREATED);
    }

    public function show(string $id): JsonResponse
    {
        try {
            $book = $this->bookRepository->findWithDetails($id);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], $e->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (!$book) {
            return response()->json([
                'message' => 'Book not found'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'message' => 'Book retrieved successfully',
            'data' => new BookResource($book)
        ], Response::HTTP_OK);
    }

    public function update(UpdateBookRequest $request, string $id): JsonResponse
    {
        try {
            $book = $this->bookService->updateBook($id, $request->validated());
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], $e->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'message' => 'Book updated successfully',
            'data' => new BookResource($book)
        ], Response::HTTP_OK);
    }

    public function destroy(DeleteBookRequest $request, string $id): JsonResponse
    {
        try {
            $this->bookService->deleteBook($id);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], $e->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'message' => 'Book deleted successfully'
        ], Response::HTTP_OK);
    }

    public function increaseStock(IncreaseStockRequest $request, string $id): JsonResponse
    {
        try {
            $stockData = $this->bookService->increaseStock($id, $request->validated()['quantity']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], $e->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'message' => 'Stock updated successfully',
            'data' => [
                'current_stock' => $stockData['quantity']
            ]
        ], Response::HTTP_OK);
    }
}
