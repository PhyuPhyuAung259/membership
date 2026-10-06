<?php

namespace App\Providers;

use App\Models\User;
use App\Services\ReminderSchedule;
use Illuminate\Support\Facades\Gate;
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
        // Admin bypasses every permission check unconditionally. This is
        // what actually guarantees Admin stays all-powerful — not the
        // permissions attached to the 'Admin' role row, which an admin can
        // still edit or even empty out from the Roles page without locking
        // anyone out.
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole('Admin') ? true : null;
        });
    }
}
