<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\IncreaseStockRequest;
use App\Http\Requests\Api\v1\StoreBookRequest;
use App\Http\Requests\Api\v1\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Models\PhysicalStock;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Book::with('category', 'physicalStock');

            // Filter by category
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->input('category_id'));
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
                $query->whereHas('physicalStock', fn ($q) => $q->where('quantity', '>', 0));
            }

            $books = $query->latest()->paginate(10);

            return BookResource::collection($books);
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
        return new BookResource($book->load('category', 'physicalStock'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBookRequest $request, string $id)
    {
        $this->authorize('update', $book);

        $book->update($request->validated());

        return (new BookResource($book->fresh()))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->authorize('delete', $book);

        $book->delete();

        return response()->json(
            ['message' => 'Book deleted successfully'],
            200
        );
    }

    public function increaseStock(IncreaseStockRequest $request, Book $book)
    {
        $this->authorize('update', $book);

        $stock = $book->physicalStock()->firstOrCreate(['book_id' => $book->id]);
        $stock->increment('quantity', $request->quantity);

        return response()->json([
            'message' => 'Stock increased successfully',
            'new_quantity' => $stock->quantity
        ], 200);
    }
}
