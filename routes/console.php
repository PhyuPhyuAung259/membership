<?php

use App\Console\Commands\SendDuesReminders;
use Illuminate\Support\Facades\Schedule;

/*
| Dues reminders run once a day, in the EARLY AFTERNOON.
|
| This is the most important operational choice in the system, and it is not
| about load. Running at 06:00 means members get chased for money they sent
| yesterday, because nobody has keyed in the bank transfers yet. Running at
| 14:30 gives whoever records payments the whole morning to clear the backlog
| first.
|
| withoutOverlapping() guards against a long queue backlog colliding with the
| next day's run. onOneServer() matters only if you scale to more than one
| app server; it is harmless now and easy to forget later.
*/
Schedule::command(SendDuesReminders::class)
    ->dailyAt('14:30')
    ->withoutOverlapping()
    ->onOneServer();
