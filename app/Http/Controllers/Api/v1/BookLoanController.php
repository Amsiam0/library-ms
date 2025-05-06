<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\UpdateReturnDateRequest;
use App\Http\Resources\BookLoanCollection;
use App\Jobs\Api\v1\SendDueDateUpdateMail;
use App\Jobs\Api\v1\SendLoanApprovalMail;
use App\Jobs\Api\v1\SendLoanRejectionMail;
use App\Models\BookLoan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class BookLoanController extends Controller
{
    public function index(Request $request)
    {
        $per_page = $request->per_page ?? 10;
        $status = $request->status;
        $search = $request->search;
        $due_date = $request->due_date;

        $bookLoans = BookLoan::latest()
            ->when(Auth::user()->role === 'admin', function ($query) {
                return $query->with(['user:id,name,email']);
            })
            ->when(Auth::user()->role === 'user', function ($query) {
                return $query->where('user_id', Auth::user()->id);
            })
            ->with(['book:id,title,author'])
            ->when(Auth::user()->role === 'admin' && $status, function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->when(Auth::user()->role === 'admin' && $due_date, function ($query) use ($due_date) {
                switch ($due_date) {
                    case 'overdue':
                        return $query->where('due_date', '<', now())
                            ->where('status', 'approved');
                    case 'today':
                        return $query->whereDate('due_date', now())
                            ->where('status', 'approved');
                    case 'upcoming':
                        return $query->where('due_date', '>', now())
                            ->where('status', 'approved');
                }
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

    public function approve(string $id)
    {
        try {
            $bookLoan = BookLoan::with(['book.physicalStock'])->findOrFail($id);

            if ($bookLoan->status !== 'pending') {
                return response()->json([
                    'message' => 'Only pending requests can be approved'
                ], 422);
            }

            if (!$bookLoan->book?->physicalStock || $bookLoan->book?->physicalStock->quantity <= 0) {
                return response()->json([
                    'message' => 'Book is out of stock'
                ], 422);
            }

            DB::beginTransaction();

            $bookLoan->update([
                'status' => 'pre-approved',
                'approved_at' => now(),
            ]);

            DB::commit();

            // Dispatch email job
            SendLoanApprovalMail::dispatch($bookLoan);

            return response()->json([
                'message' => 'Your book is ready for collection, please collect as soon as possible.',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Book loan not found'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error approving loan request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function reject(string $id)
    {

        try {
            $bookLoan = BookLoan::with(['book.physicalStock'])->findOrFail($id);
            if ($bookLoan->status !== 'pending') {
                return response()->json(['message' => 'Only pending requests can be rejected.'], 422);
            }

            $bookLoan->update(['status' => 'rejected']);

            SendLoanRejectionMail::dispatch($bookLoan);

            return response()->json(['message' => 'Loan rejected.'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Book loan not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error rejecting loan', 'error' => $e->getMessage()], 500);
        }
    }

    public function distribute(string $id)
    {

        try {
            $bookLoan = BookLoan::with(['book.physicalStock'])->findOrFail($id);
            if ($bookLoan->status !== 'pre-approved') {
                return response()->json(['message' => 'Only approved book can be distributed'], 422);
            }

            $bookLoan->update([
                'status' => 'approved',
                'due_date' => now()->addDays(7),
            ]);

            // Decrease the physical stock of the book
            $bookLoan->book->physicalStock->decrement('quantity');

            return response()->json(['message' => 'Book has been distributed.'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Book loan not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error distributing book', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateReturnDate(UpdateReturnDateRequest $request, String $id)
    {
        try{
            $bookLoan = BookLoan::findOrFail($id);

            if ($bookLoan->status !== 'approved') {
                return response()->json([
                    'message' => 'Only approved book loans can be updated.'
                ], 422);
            }

            $bookLoan->update([
                'due_date' => $request->due_date,
            ]);

            SendDueDateUpdateMail::dispatch($bookLoan);

            return response()->json([
                'message' => 'Due date updated successfully'
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Book loan not found'
            ], 404);
        }
    }

}
