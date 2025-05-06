<?php

namespace App\Services\Api\V1;

use App\Jobs\Api\v1\SendDueDateUpdateMail;
use App\Jobs\Api\v1\SendLoanApprovalMail;
use App\Jobs\Api\v1\SendLoanRejectionMail;
use App\Mail\DueDateIncreaseNotification;
use App\Repositories\Api\V1\BookLoanRepository;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;

class BookLoanService
{
    public function __construct(
        private readonly BookLoanRepository $bookLoanRepository
    ) {}

    public function approveLoan(string $id): void
    {
        $bookLoan = $this->bookLoanRepository->findWithRelations($id);

        if (!$bookLoan) {
            throw new ModelNotFoundException('Book loan not found', Response::HTTP_NOT_FOUND);
        }

        if ($bookLoan->status !== 'pending') {
            throw new Exception('Only pending requests can be approved', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$bookLoan->book?->physicalStock || $bookLoan->book?->physicalStock->quantity <= 0) {
            throw new Exception('Book is out of stock', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();
        try {
            $this->bookLoanRepository->update($bookLoan, [
                'status' => 'pre-approved',
                'approved_at' => now(),
            ]);
            DB::commit();
            SendLoanApprovalMail::dispatch($bookLoan);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Error approving loan: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function rejectLoan(string $id): void
    {
        $bookLoan = $this->bookLoanRepository->find($id);

        if (!$bookLoan) {
            throw new ModelNotFoundException('Book loan not found', Response::HTTP_NOT_FOUND);
        }

        if ($bookLoan->status !== 'pending') {
            throw new Exception('Only pending requests can be rejected', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->bookLoanRepository->update($bookLoan, ['status' => 'rejected']);
        SendLoanRejectionMail::dispatch($bookLoan);
    }

    public function distributeLoan(string $id): void
    {
        $bookLoan = $this->bookLoanRepository->findWithRelations($id);

        if (!$bookLoan) {
            throw new ModelNotFoundException('Book loan not found', Response::HTTP_NOT_FOUND);
        }

        if ($bookLoan->status !== 'pre-approved') {
            throw new Exception('Only pre-approved loans can be distributed', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->bookLoanRepository->update($bookLoan, [
            'status' => 'approved',
            'due_date' => now()->addDays(7),
        ]);
        $this->bookLoanRepository->decrementStock($bookLoan->book);
    }

    public function requestLoan(string $bookId): void
    {
        $this->bookLoanRepository->create([
            'user_id' => Auth::id(),
            'book_id' => $bookId,
            'status' => 'pending',
            'requested_at' => now(),
        ]);
    }

    public function requestDueDateUpdate(string $id, array $data): array
    {
        $bookLoan = $this->bookLoanRepository->find($id);

        if (!$bookLoan) {
            throw new ModelNotFoundException('Book loan not found', Response::HTTP_NOT_FOUND);
        }

        if ($bookLoan->status !== 'approved' || $bookLoan->user_id !== Auth::id()) {
            throw new Exception('Invalid book loan for due date update', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $dueDateIncrease = $this->bookLoanRepository->createDueDateIncrease($bookLoan, [
            'user_id' => Auth::id(),
            'requested_at' => now(),
            'new_due_date' => $data['due_date'],
            'reason' => $data['reason'],
            'status' => 'pending',
        ]);

        return [
            'book_loan_id' => $bookLoan->id,
            'new_due_date' => $data['due_date'],
            'reason' => $data['reason'],
            'status' => 'pending',
        ];
    }

    public function actionDueDateRequest(string $id, string $status): void
    {
        if (!in_array($status, ['approved', 'rejected'])) {
            throw new Exception('Invalid status', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $dueDateIncrease = $this->bookLoanRepository->findDueDateIncrease($id);

        if (!$dueDateIncrease) {
            throw new ModelNotFoundException('Due date increase request not found', Response::HTTP_NOT_FOUND);
        }

        if ($dueDateIncrease->status !== 'pending') {
            throw new Exception('Only pending due date requests can be processed', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($status === 'approved') {
            DB::beginTransaction();
            try {
                $this->bookLoanRepository->updateDueDateIncrease($dueDateIncrease, [
                    'status' => 'approved',
                    'approved_at' => now(),
                ]);
                $this->bookLoanRepository->update($dueDateIncrease->bookLoan, [
                    'due_date' => $dueDateIncrease->new_due_date,
                ]);
                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                throw new Exception('Error processing due date request: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } else {
            $this->bookLoanRepository->updateDueDateIncrease($dueDateIncrease, [
                'status' => 'rejected',
            ]);
        }


        Mail::to($dueDateIncrease->user->email)->send(new DueDateIncreaseNotification($dueDateIncrease, $status));
    }

    public function returnBook(string $id): void
    {
        $bookLoan = $this->bookLoanRepository->find($id);

        if (!$bookLoan) {
            throw new ModelNotFoundException('Book loan not found', Response::HTTP_NOT_FOUND);
        }

        if ($bookLoan->status !== 'approved') {
            throw new Exception('Only approved loans can be returned', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->bookLoanRepository->update($bookLoan, [
            'status' => 'returned',
            'returned_at' => now(),
        ]);
        $this->bookLoanRepository->incrementStock($bookLoan->book);
    }
}
