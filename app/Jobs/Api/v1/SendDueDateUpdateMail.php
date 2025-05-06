<?php

namespace App\Jobs\Api\v1;

use App\Mail\Api\v1\LoanDueDateUpdated;
use App\Models\BookLoan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendDueDateUpdateMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private BookLoan $bookLoan)
    {
    }

    public function handle(): void
    {
        Mail::to($this->bookLoan->user->email)
            ->send(new LoanDueDateUpdated($this->bookLoan));
    }
}
