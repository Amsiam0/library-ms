<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\FeedbackRequest;
use App\Http\Resources\Feedback as FeedbackResource;
use App\Repositories\Api\V1\FeedbackRepository;
use App\Services\Api\V1\FeedbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;


class FeedbackController extends Controller
{
    public function __construct(
        private readonly FeedbackService $feedbackService,
        private readonly FeedbackRepository $feedbackRepository
    ) {}

    public function store(FeedbackRequest $request, string $bookId): JsonResponse
    {
        try {
            $feedback = $this->feedbackService->createFeedback($bookId, $request->validated());
        } catch (\Exception $e) {
            return response()->json(["message" => $e->getMessage()], $e->getCode());
        }

        return response()->json([
            'message' => 'Feedback created successfully',
            'data' => new FeedbackResource($feedback)
        ], Response::HTTP_CREATED);
    }

    public function getLatestFeedback(): JsonResponse
    {
        $feedback = $this->feedbackRepository->getLatestFeedback();
        return response()->json([
            'message' => 'Latest feedback retrieved successfully',
            'data' => FeedbackResource::collection($feedback)
        ], Response::HTTP_OK);
    }

    public function getBookFeedback(Request $request, string $bookId): JsonResponse
    {
        $feedback = $this->feedbackRepository->getBookFeedback($request, $bookId);
        return response()->json([
            'message' => 'Book feedback retrieved successfully',
            'data' => FeedbackResource::collection($feedback)
        ], Response::HTTP_OK);
    }
}
