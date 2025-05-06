<?php

namespace App\Repositories\Api\V1;

use App\Models\Book;
use App\Models\BookLoan;
use App\Models\PhysicalStock;
use App\Models\User;
use App\Traits\Api\V1\PaginationTrait;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BookRepository
{
    use PaginationTrait;

    public function getFilteredBooks(Request $request): LengthAwarePaginator
    {
        $perPage = $this->sanitizePerPage($request->get('per_page', 10));

        $query = Book::with('category', 'physicalStock')
            ->withCount(['bookLoans as total_loans' => function ($query) {
                $query->where('status', 'approved');
            }]);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('title', 'like', "%{$search}%");
        }

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

        if ($request->boolean('available')) {
            $query->whereHas('physicalStock', fn($q) => $q->where('quantity', '>', 0));
        }

        return $query->latest()->paginate($perPage);
    }

    public function getDashboardStats()
    {
        return [
            'total_books' => Book::count(),
            'physical_books' => Book::where('has_physical', true)->count(),
            'ebooks' => Book::whereNotNull('ebook')->count(),
            'active_loans' => BookLoan::where('status', 'approved')
                ->whereNull('returned_at')
                ->count(),
            'total_users' => User::where('role', 'user')->count(),
            'top_borrowed_books' => Book::withCount(['bookLoans' => function($query) {
                $query->where('status', 'approved');
            }])
                ->having('book_loans_count', '>', 0)
                ->orderByDesc('book_loans_count')
                ->limit(5)
                ->get(['id', 'title', 'author', 'thumbnail'])
                ->map(function($book) {
                    return [
                        'id' => $book->id,
                        'title' => $book->title,
                        'author' => $book->author,
                        'thumbnail' => $book->thumbnail,
                        'total_borrows' => $book->book_loans_count
                    ];
                })
        ];
    }

    public function find(string $id): ?Book
    {
        return Book::find($id);
    }

    public function findWithDetails(string $id): ?Book
    {
        return Book::with([
            'category',
            'physicalStock',
            'bookLoans' => fn($q) => $q->where('status', 'approved')
                ->select('id', 'user_id', 'book_id', 'due_date')
                ->orderBy('due_date', 'asc')
                ->limit(3),
            'bookLoans.user' => fn($q) => $q->select('id', 'name', 'email')
        ])->find($id);
    }

    public function create(array $data): Book
    {
        return Book::create($data);
    }

    public function update(Book $book, array $data): Book
    {
        $book->update($data);
        return $book->fresh(['category']);
    }

    public function delete(Book $book): void
    {
        $book->delete();
    }

    public function hasLoans(Book $book): bool
    {
        return $book->bookLoans()->exists();
    }

    public function createPhysicalStock(Book $book, int $quantity): PhysicalStock
    {
        return PhysicalStock::create([
            'book_id' => $book->id,
            'quantity' => $quantity
        ]);
    }

    public function updateOrCreatePhysicalStock(Book $book, int $quantity): PhysicalStock
    {
        return PhysicalStock::updateOrCreate(
            ['book_id' => $book->id],
            ['quantity' => DB::raw('quantity + ' . $quantity)]
        );
    }
}
