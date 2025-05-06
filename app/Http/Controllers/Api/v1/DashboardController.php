<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Repositories\Api\V1\BookRepository;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly BookRepository $bookRepository
    ) {}

    public function getAnalytics(): JsonResponse
    {
        try {
            $stats = $this->bookRepository->getDashboardStats();

            return response()->json([
                'message' => 'Dashboard statistics retrieved successfully',
                'data' => $stats
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving dashboard statistics',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
