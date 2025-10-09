<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule overdue loan status update to run daily at midnight
Schedule::command('loans:update-overdue')->daily();

// Schedule loan reminder emails (3 days before due) to run daily at 9 AM
Schedule::command('loans:send-reminders')->dailyAt('09:00');

// Schedule overdue interest calculation and emails to run daily at 1 AM
Schedule::command('loans:calculate-overdue-interest')->dailyAt('01:00');

// Schedule customer credit score calculation to run weekly on Sunday at 2 AM
Schedule::command('customers:calculate-credit-scores')->weekly()->sundays()->at('02:00');
