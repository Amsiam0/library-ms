<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\BookLoanRequest;
use App\Http\Resources\BookLoanCollection;
use App\Models\BookLoan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class BookController extends Controller
{
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

        $bookLoans = BookLoan::where('user_id', Auth::user()?->id)
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
}
