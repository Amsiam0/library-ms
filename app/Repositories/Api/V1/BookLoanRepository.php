<?php

namespace App\Repositories\Api\V1;

use App\Models\Book;
use App\Models\BookLoan;
use App\Models\DueDateIncrease;
use App\Traits\Api\V1\PaginationTrait;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class BookLoanRepository
{
    use PaginationTrait;

    public function getFilteredBookLoans(Request $request)
    {
        $per_page = $this->sanitizePerPage($request->get('per_page', 10));
        $status = $request->status;
        $search = $request->search;
        $due_date = $request->due_date;

        return BookLoan::latest()
            ->when(Auth::user()->role === 'admin', fn($query) => $query->with(['user:id,name,email']))
            ->when(Auth::user()->role === 'user', fn($query) => $query->where('user_id', Auth::user()->id))
            ->with(['book:id,title,author'])
            ->when($status, fn($query) => $query->where('status', $status))
            ->when($due_date, fn($query) => $this->applyDueDateFilter($query, $due_date))
            ->when($search, fn($query) => $query->where(fn($query) => $query->whereHas('book', fn($q) => $q->where('title', 'like', "%{$search}%")->orWhere('author', 'like', "%{$search}%"))))
            ->paginate($per_page);
    }

    public function getFilteredDueDateIncreases(Request $request): LengthAwarePaginator
    {
        $per_page = $this->sanitizePerPage($request->get('per_page', 10));
        $status = $request->status;
        $search = $request->search;



        return DueDateIncrease::with('user', 'bookLoan', 'bookLoan.book:id,title,author')
            ->latest()
            ->when(Auth::user()->role === 'user', fn($query) => $query->where('user_id', Auth::user()->id))
            ->when($status, fn($query) => $query->where('status', $status))
            ->when($search, fn($query) => $query->whereHas('bookLoan.book', fn($q) => $q->where('title', 'like', "%{$search}%")->orWhere('author', 'like', "%{$search}%")))->orWhereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
            ->paginate($per_page);
    }

    public function find(string $id): ?BookLoan
    {
        return BookLoan::find($id);
    }


    public function findWithRelations(string $id): ?BookLoan
    {
        return BookLoan::with(['book.physicalStock'])->find($id);
    }

    public function findDueDateIncrease(string $id): ?DueDateIncrease
    {
        return DueDateIncrease::with('user', 'bookLoan')->where('status', 'pending')->find($id);
    }

    public function create(array $data): BookLoan
    {
        return BookLoan::create($data);
    }

    public function update(BookLoan $bookLoan, array $data): void
    {
        $bookLoan->update($data);
    }

    public function createDueDateIncrease(BookLoan $bookLoan, array $data): DueDateIncrease
    {
        return $bookLoan->dueDateIncreases()->create($data);
    }

    public function updateDueDateIncrease(DueDateIncrease $dueDateIncrease, array $data): void
    {
        $dueDateIncrease->update($data);
    }

    public function decrementStock(Book $book): void
    {
        $book->physicalStock->decrement('quantity');
    }

    public function incrementStock(Book $book): void
    {
        $book->physicalStock->increment('quantity');
    }

    private function applyDueDateFilter($query, string $due_date): void
    {
        match ($due_date) {
            'overdue' => $query->where('due_date', '<', now())->where('status', 'approved'),
            'today' => $query->whereDate('due_date', now())->where('status', 'approved'),
            'upcoming' => $query->where('due_date', '>', now())->where('status', 'approved'),
            default => null,
        };
    }
}
