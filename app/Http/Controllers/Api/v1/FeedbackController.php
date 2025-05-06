<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\FeedbackRequest;
use App\Http\Resources\Feedback as FeedbackResource;
use App\Http\Resources\FeedbackCollection;
use App\Models\Book;
use App\Models\Feedback;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(FeedbackRequest $request, string $bookId)
    {
        $validatedData = $request->validated();


        $book = Book::findOrFail($bookId);

        $feedback = Feedback::create([
            'book_id' => $book->id,
            'user_id' => Auth::id(),
            'rating' => $validatedData['rating'],
            'review' => $validatedData['comment'],
            'submitted_at' => now(),
        ]);

        return new FeedbackResource($feedback);
    }

    public function getLatestFeedback()
    {
        $feedback = Feedback::latest()->with('book', 'user')->take(10)->get();

        return  FeedbackResource::collection($feedback);
    }

    public function getBookFeedback(string $bookId)
    {
        $feedback = Feedback::where('book_id', $bookId)->latest()->paginate(10);

        return  FeedbackResource::collection($feedback);
    }
}
