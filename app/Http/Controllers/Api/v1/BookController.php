<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\IncreaseStockRequest;
use App\Http\Requests\Api\v1\StoreBookRequest;
use App\Http\Requests\Api\v1\UpdateBookRequest;
use App\Http\Resources\BookListResource;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Models\PhysicalStock;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use App\Http\Requests\Api\v1\BookLoanRequest;
use App\Http\Resources\BookLoanCollection;
use App\Models\BookLoan;
use App\Http\Requests\Api\v1\RequestUpdateDueDateRequest;

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
            $book = Book::with(['category', 'physicalStock'])->find($id);

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
    public function requestBookLoan(BookLoanRequest $request)
    {
        $book = $request->validated('book_id');

        BookLoan::create([
            'user_id' => Auth::user()?->id,
            'book_id' => $book,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        return response()->json([
            'message' => 'Book request submitted successfully!',
        ], 201);
    }

    public function getBookLoans(Request $request)
    {
        $per_page = $request->per_page ?? 10;

        $status = $request?->status;
        $search = $request?->search;

        $bookLoans = BookLoan::latest()->where('user_id', Auth::user()?->id)
            ->with(['book:id,title,author'])
            ->when($status, function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->when($search, function ($query) use ($search) {
                return $query->whereHas('book', function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('author', 'like', "%{$search}%");
                });
            })
            ->paginate($per_page);

        return new BookLoanCollection($bookLoans);
    }

    public function requestUpdateDueDate(RequestUpdateDueDateRequest $request, $id)
    {
        $validatedData = $request->validationData();

        $bookLoan = BookLoan::where('status', 'approved')->find($id);


        if ($bookLoan?->user_id !== Auth::user()?->id) {
            return response()->json([
                'message' => 'There is no book loan with this id.',
            ], 404);
        }

        $bookLoan->dueDateIncreases()->create([
            'user_id' => Auth::user()?->id,
            'requested_at' => now(),
            'new_due_date' => $validatedData['due_date'],
            'reason' => $validatedData['reason'],
            'status' => 'pending',
        ]);


        return response()->json([
            'message' => 'Due date update requested sended successfully! Wait for the admin to approve it.',
            'data' => [
                'book_loan_id' => $bookLoan->id,
                'new_due_date' => $validatedData['due_date'],
                'reason' => $validatedData['reason'],
                'status' => 'pending',
            ],
        ], 201);
    }
}
