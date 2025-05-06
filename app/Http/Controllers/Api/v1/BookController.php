<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\DeleteBookRequest;
use App\Http\Requests\Api\v1\IncreaseStockRequest;
use App\Http\Requests\Api\v1\StoreBookRequest;
use App\Http\Requests\Api\v1\UpdateBookRequest;
use App\Http\Resources\BookListResource;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Models\PhysicalStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class BookController extends Controller
{



    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Book::with('category', 'physicalStock')
                ->withCount(['bookLoans as total_loans' => function ($query) {;
                    $query->where('status', 'approved');
                }]);

            // Filter by category
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->input('category_id'));
            }

            // Search by title
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where('title', 'like', "%{$search}%");
            }

            // Filter by format (ebook, physical, or both)
            if ($request->filled('format')) {
                switch ($request->input('format')) {
                    case 'ebook':
                        $query->where('ebook', '!=', null);
                        break;
                    case 'physical':
                        $query->where('has_physical', true);
                        break;
                    case 'both':
                        $query->where('ebook', '!=', null)
                            ->where('has_physical', true);
                        break;
                }
            }

            // Filter by stock availability
            if ($request->boolean('available')) {
                $query->whereHas('physicalStock', fn($q) => $q->where('quantity', '>', 0));
            }

            $books = $query->latest()->paginate(10);

            return BookListResource::collection($books);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving books',
                'error' => $e->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBookRequest $request)
    {
        try {
            $validatedData = $request->validated();

            // Create the book first
            $book = Book::create([
                'title' => $validatedData['title'],
                'author' => $validatedData['author'],
                'description' => $validatedData['description'] ?? null,
                'category_id' => $validatedData['category_id'],
                'has_physical' => $validatedData['has_physical'],
                'thumbnail' => $validatedData['thumbnail'] ?? null,
                'ebook' => $validatedData['ebook'] ?? null,
            ]);

            // Handle physical book stock
            if ($validatedData['has_physical']) {
                PhysicalStock::create([
                    'book_id' => $book->id,
                    'quantity' => $validatedData['quantity']
                ]);
            }


            return response()->json([
                'message' => 'Book created successfully',
                'data' => new BookResource($book->load('physicalStock'))
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating book',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $book = Book::with([
                'category',
                'physicalStock',
                'bookLoans' => fn($q) => $q->where('status', 'approved')
                    ->select('id', 'user_id', 'book_id', 'due_date')->orderBy('due_date', 'asc')->limit(3),
                'bookLoans.user' => fn($q) => $q->select('id', 'name', 'email')

            ])
                ->find($id);


            if (!$book) {
                return response()->json([
                    'message' => 'Book not found'
                ], 404);
            }

            return response()->json([
                'message' => 'Book retrieved successfully',
                'data' => new BookResource($book)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving book',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBookRequest $request, string $id)
    {
        try {
            $book = Book::findOrFail($id);

            $validatedData = $request->validated();

            // Update book details
            $book->update([
                'title' => $validatedData['title'] ?? $book->title,
                'author' => $validatedData['author'] ?? $book->author,
                'description' => $validatedData['description'] ?? $book->description,
                'category_id' => $validatedData['category_id'] ?? $book->category_id,
                'ebook' => $validatedData['ebook'],
                'thumbnail' => $validatedData['thumbnail'] ?? '',
            ]);

            return response()->json([
                'message' => 'Book updated successfully',
                'data' => new BookResource($book->fresh(['category']))
            ], ResponseAlias::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Book not found'
            ], ResponseAlias::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating book',
                'error' => $e->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeleteBookRequest $request, string $id)
    {
        try {
            $book = Book::findOrFail($id);

            // Check if the book has any loans
            if ($book->bookLoans()->exists()) {
                return response()->json([
                    'message' => 'Cannot delete book with active loans'
                ], ResponseAlias::HTTP_FORBIDDEN);
            }

            $book->delete();

            return response()->json([
                'message' => 'Book deleted successfully'
            ], ResponseAlias::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Book not found'
            ], ResponseAlias::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting book',
                'error' => $e->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function increaseStock(IncreaseStockRequest $request, String $id)
    {
        try {
            $book = Book::findOrFail($id);

            DB::beginTransaction();

            // If book didn't have physical copy before, enable it
            if (!$book->has_physical) {
                $book->update(['has_physical' => true]);
            }

            // Create or update physical stock
            $physicalStock = PhysicalStock::updateOrCreate(
                ['book_id' => $book->id],
                [
                    'quantity' => DB::raw('quantity + ' . $request->validated()['quantity'])
                ]
            );

            DB::commit();

            return response()->json([
                'message' => 'Stock updated successfully',
                'data' => [
                    'current_stock' => $physicalStock->fresh()->quantity
                ]
            ], ResponseAlias::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Book not found'
            ], ResponseAlias::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error updating stock',
                'error' => $e->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
