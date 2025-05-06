<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\BookLoanRequest;
use App\Http\Requests\Api\v1\RequestUpdateDueDateRequest;
use App\Http\Requests\Api\v1\UpdateReturnDateRequest;
use App\Http\Resources\BookLoanCollection;
use App\Http\Resources\DueDateIncreaseResource;
use App\Jobs\Api\v1\SendDueDateUpdateMail;
use App\Jobs\Api\v1\SendLoanApprovalMail;
use App\Jobs\Api\v1\SendLoanRejectionMail;
use App\Mail\DueDateIncreaseNotification;
use App\Models\BookLoan;
use App\Models\DueDateIncrease;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;

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
                'message' => 'Approved',
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

        $bookLoans = BookLoan::latest()
            ->when(Auth::user()->role === 'admin', function ($query) {
                return $query->with(['user:id,name,email']);
            })->when(Auth::user()->role === 'user', function ($query) {
                return $query->where('user_id', Auth::user()->id);
            })
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

    public function requestUpdateDueDate(RequestUpdateDueDateRequest $request, $id)
    {
        $validatedData = $request->validationData();

        $bookLoan = BookLoan::where('status', 'approved')->find($id);


        if ($bookLoan?->user_id !== Auth::user()?->id) {
            return response()->json([
                'message' => 'There is no book loan with this id.',
            ], 404);
        }

        $bookLoan->dueDateIncreases()->create([
            'user_id' => Auth::user()?->id,
            'requested_at' => now(),
            'new_due_date' => $validatedData['due_date'],
            'reason' => $validatedData['reason'],
            'status' => 'pending',
        ]);


        return response()->json([
            'message' => 'Due date update requested sended successfully! Wait for the admin to approve it.',
            'data' => [
                'book_loan_id' => $bookLoan->id,
                'new_due_date' => $validatedData['due_date'],
                'reason' => $validatedData['reason'],
                'status' => 'pending',
            ],
        ], 201);
    }

    //updateduedatelist

    public function updateDueDateRequestList(Request $request)
    {
        $per_page = $request->per_page ?? 10;
        $per_page = (int)$per_page ?? 10;
        $per_page = $per_page > 100 ? 100 : $per_page;
        $per_page = $per_page < 1 ? 1 : $per_page;

        $status = $request->status;
        $search = $request->search;

        $dueDateIncreases = DueDateIncrease::with('user', 'bookLoan', 'bookLoan.book:id,title,author')->latest()
            ->when(Auth::user()->role === 'user', function ($query) {
                return $query->where('user_id', Auth::user()->id);
            })
            ->when($status, function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->when($search, function ($query) use ($search) {
                return $query->whereHas('bookLoan.book', function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('author', 'like', "%{$search}%");
                });
            })
            ->paginate($per_page);

        return  DueDateIncreaseResource::collection($dueDateIncreases);
    }

    public function actionDueDateRequest(Request $request, $id, $status)
    {

        if (!($status === 'approved' || $status === 'rejected')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid status'
            ], 422);
        }


        $dueDateIncrease = DueDateIncrease::where('status', 'pending')->find($id);

        if (! $dueDateIncrease) {
            return response()->json(['status' => 'error', 'message' => 'Increase due date request not found'], 404);
        }


        if ($status === 'approved') {
            try {
                DB::beginTransaction();
                $dueDateIncrease->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                ]);
                BookLoan::where('id', $dueDateIncrease->book_loan_id)->update([
                    'due_date' => $dueDateIncrease->new_due_date,
                ]);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
            }
        } else if ($status === 'rejected') {
            $dueDateIncrease->update([
                'status' => 'rejected',
            ]);
        }

        $dueDateIncrease->load(['user', 'bookLoan', 'bookLoan.book:id,title,author']);

        Mail::to($dueDateIncrease->user?->email)->send(new DueDateIncreaseNotification($dueDateIncrease, $status));

        return response()->json(['status' => $status, 'message' => 'Due date increase request ' . $status . ' successfully!'], 200);
    }
    public function returnBook(string $id)
    {
        try {
            $bookLoan = BookLoan::findOrFail($id);

            if ($bookLoan->status !== 'approved') {
                return response()->json(['message' => 'Only approved book loans can be returned.'], 422);
            }

            $bookLoan->update([
                'status' => 'returned',
                'returned_at' => now(),
            ]);

            // Increase the physical stock of the book
            $bookLoan->book->physicalStock->increment('quantity');

            return response()->json(['message' => 'Book has been returned.'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Book loan not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error returning book', 'error' => $e->getMessage()], 500);
        }
    }
}
