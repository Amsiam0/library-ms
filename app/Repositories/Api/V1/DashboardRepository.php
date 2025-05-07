<?php

namespace App\Repositories\Api\V1;

use App\Models\Book;
use App\Models\BookLoan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardRepository
{
    public function getDashboardStats(): array
    {
        return [
            ...$this->getBasicStats(),
            'top_borrowed_books' => $this->getTopBorrowedBooks(),
            'last_seven_days_loans' => $this->getLastSevenDaysStats()
        ];
    }

    private function getBasicStats(): array
    {
        return [
            'total_books' => Book::count(),
            'physical_books' => Book::where('has_physical', true)->count(),
            'ebooks' => Book::whereNotNull('ebook')->count(),
            'active_loans' => BookLoan::where('status', 'approved')
                ->whereNull('returned_at')
                ->count(),
            'total_users' => User::where('role', 'user')->count(),
        ];
    }

    private function getTopBorrowedBooks(): array
    {
        $books = Book::withCount(['bookLoans' => function($query) {
            $query->where('status', 'approved');
        }])
            ->having('book_loans_count', '>', 0)
            ->orderByDesc('book_loans_count')
            ->limit(5)
            ->get(['id', 'title', 'author', 'thumbnail'])
            ->map(fn($book) => [
                'id' => $book->id,
                'title' => $book->title,
                'author' => $book->author,
                'thumbnail' => $book->thumbnail,
                'totalBorrows' => $book->book_loans_count
            ]);

        $counts = collect($books)->pluck('total_borrows');

        return [
            'minCount' => $counts->min(),
            'maxCount' => $counts->max(),
            'data' => $books
        ];
    }

    private function getLastSevenDaysStats(): array
    {
        $lastSevenDays = $this->getLastSevenDaysLoans();
        $dateRange = $this->generateDateRange($lastSevenDays);
        $counts = $dateRange->pluck('count');

        return [
            'minCount' => $counts->min(),
            'maxCount' => $counts->max(),
            'data' => $dateRange
        ];
    }

    private function getLastSevenDaysLoans()
    {
        return DB::table('book_loans')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('status', 'approved')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy('date')
            ->map(fn($item) => $item->count);
    }

    private function generateDateRange($lastSevenDays)
    {
        return collect(range(0, 6))
            ->map(function ($days) use ($lastSevenDays) {
                $date = now()->subDays($days)->format('d M');
                return [
                    'date' => $date,
                    'count' => $lastSevenDays[$date] ?? 0
                ];
            })
            ->reverse()
            ->values();
    }
}
