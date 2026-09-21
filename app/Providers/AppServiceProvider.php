<?php

namespace App\Providers;

use App\Services\ReminderSchedule;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ReminderSchedule::class, function () {
            return new ReminderSchedule(config('membership.reminders'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
