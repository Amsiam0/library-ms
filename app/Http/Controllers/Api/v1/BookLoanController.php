<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\BookLoanRequest;
use App\Http\Requests\Api\v1\RequestUpdateDueDateRequest;
use App\Http\Resources\BookLoanCollection;
use App\Http\Resources\DueDateIncreaseResource;
use App\Repositories\Api\V1\BookLoanRepository;
use App\Services\Api\V1\BookLoanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BookLoanController extends Controller
{
    public function __construct(
        private readonly BookLoanService $bookLoanService,
        private readonly BookLoanRepository $bookLoanRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $bookLoans = $this->bookLoanRepository->getFilteredBookLoans($request);
            return response()->json([
                'message' => 'Book loans retrieved successfully',
                'data' => new BookLoanCollection($bookLoans)
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(["message" => $e->getMessage()], $e->getCode());
        }
    }

    public function approve(string $id): JsonResponse
    {
        try {
            $this->bookLoanService->approveLoan($id);
            return response()->json([
                'message' => 'Loan approved successfully'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(["message" => $e->getMessage()], $e->getCode());
        }
    }

    public function reject(string $id): JsonResponse
    {
        try {
            $this->bookLoanService->rejectLoan($id);
            return response()->json([
                'message' => 'Loan rejected successfully'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(["message" => $e->getMessage()], $e->getCode());
        }
    }

    public function distribute(string $id): JsonResponse
    {
        try {
            $this->bookLoanService->distributeLoan($id);
            return response()->json([
                'message' => 'Book distributed successfully'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(["message" => $e->getMessage()], $e->getCode());
        }
    }

    public function requestBookLoan(BookLoanRequest $request): JsonResponse
    {
        try {
            $this->bookLoanService->requestLoan($request->validated()['book_id']);
            return response()->json([
                'message' => 'Book loan request submitted successfully'
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json(["message" => $e->getMessage()], $e->getCode());
        }
    }

    public function getBookLoans(Request $request): JsonResponse
    {
        try {
            $bookLoans = $this->bookLoanRepository->getFilteredBookLoans($request);
            return response()->json([
                'message' => 'Book loans retrieved successfully',
                'data' => new BookLoanCollection($bookLoans)
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(["message" => $e->getMessage()], $e->getCode());
        }
    }

    public function requestUpdateDueDate(RequestUpdateDueDateRequest $request, string $id): JsonResponse
    {

        try {
            $data = $this->bookLoanService->requestDueDateUpdate($id, $request->validated());
            return response()->json([
                'message' => 'Due date update request submitted successfully',
                'data' => $data
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json(["message" => $e->getMessage()], $e->getCode() ?? 500);
        }
    }

    public function updateDueDateRequestList(Request $request): JsonResponse
    {
        try {
            $dueDateIncreases = $this->bookLoanRepository->getFilteredDueDateIncreases($request);
            return response()->json([
                'message' => 'Due date increase requests retrieved successfully',
                'data' => DueDateIncreaseResource::collection($dueDateIncreases)
            ], Response::HTTP_OK);
        } catch (\Exception $e) {

            return response()->json(["message" => $e->getMessage()], $e->getCode());
        }
    }

    public function actionDueDateRequest(Request $request, string $id, string $status): JsonResponse
    {
        try {
            $this->bookLoanService->actionDueDateRequest($id, $status);
            return response()->json([
                'message' => "Due date increase request {$status} successfully"
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(["message" => $e->getMessage()], $e->getCode());
        }
    }

    public function returnBook(string $id): JsonResponse
    {
        try {
            $this->bookLoanService->returnBook($id);
            return response()->json([
                'message' => 'Book returned successfully'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(["message" => $e->getMessage()], $e->getCode());
        }
    }
}
