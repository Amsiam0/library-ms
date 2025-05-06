<?php

use App\Console\Commands\NotifyBookLoanDueDate;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::command('app:notify-book-loan-due-date')
    ->dailyAt("09:00");
