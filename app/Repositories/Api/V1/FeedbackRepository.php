<?php

namespace App\Repositories\Api\V1;

use App\Models\Book;
use App\Models\Feedback;
use App\Traits\Api\V1\PaginationTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class FeedbackRepository
{
    use PaginationTrait;

    public function findBook(string $bookId): ?Book
    {
        return Book::find($bookId);
    }

    public function create(array $data): Feedback
    {
        return Feedback::create($data);
    }

    public function getLatestFeedback($limit = 10): Collection
    {
        return Feedback::latest()->with('book', 'user')->take($limit)->get();
    }

    public function getBookFeedback(Request $request, string $bookId): LengthAwarePaginator
    {
        return Feedback::with('user')
            ->where('book_id', $bookId)
            ->latest()
            ->paginate($this->sanitizePerPage($request->get('per_page', 10)));
    }
}
