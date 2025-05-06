<?php

namespace App\Services\Api\V1;

use App\Models\Feedback;
use App\Repositories\Api\V1\FeedbackRepository;
use App\Repositories\FeedbackRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;

class FeedbackService
{
    public function __construct(
        private readonly FeedbackRepository $feedbackRepository
    ) {}

    public function createFeedback(string $bookId, array $data): Feedback
    {
        $book = $this->feedbackRepository->findBook($bookId);

        if (!$book) {
            throw new ModelNotFoundException('Book not found', 404);
        }

        return $this->feedbackRepository->create([
            'book_id' => $book->id,
            'user_id' => Auth::id(),
            'rating' => $data['rating'],
            'review' => $data['comment'],
            'submitted_at' => now(),
        ]);
    }
}
