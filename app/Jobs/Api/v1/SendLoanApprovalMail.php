<?php

namespace App\Jobs\Api\v1;

use App\Mail\Api\v1\LoanRequestApproved;
use App\Models\BookLoan;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendLoanApprovalMail implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(private BookLoan $bookLoan)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->bookLoan->user->email)
            ->send(new LoanRequestApproved($this->bookLoan));
    }
}
