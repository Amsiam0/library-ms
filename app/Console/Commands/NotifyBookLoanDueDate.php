<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyBookLoanDueDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:notify-book-loan-due-date';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify users of book loans due in 3 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $bookLoans = \App\Models\BookLoan::where('due_date', today()->addDays(3))
            ->where('status', 'approved')
            ->get();

        foreach ($bookLoans as $bookLoan) {
            Mail::to($bookLoan->user->email)
                ->send(new \App\Mail\BookLoanDueDateNotification($bookLoan));
        }

        Log::info('Book loan due date notifications sent!');
    }
}
