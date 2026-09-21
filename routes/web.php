<?php

use App\Http\Controllers\MemberDirectoryController;
use App\Http\Controllers\UnsubscribeController;
use App\Livewire\Actions\Logout;
use App\Livewire\Announcements;
use App\Livewire\Dashboard;
use App\Livewire\Events;
use App\Livewire\Members;
use Illuminate\Support\Facades\Route;

/*
| Unsubscribe is public and signed. 'signed' middleware verifies the
| signature Laravel put on the link, so no token column is needed and a
| tampered member id is rejected before the controller runs.
*/
Route::get('/unsubscribe/test', [UnsubscribeController::class, 'test']);

Route::middleware('signed')->group(function () {
    Route::get('/unsubscribe/{member}', [UnsubscribeController::class, 'show'])
        ->name('unsubscribe.show');

    Route::post('/unsubscribe/{member}', [UnsubscribeController::class, 'store'])
        ->name('unsubscribe.store');
});

/*
| The public directory. Unsigned and unauthenticated on purpose — this is
| the page a company shares with its own customers, so the link needs to
| work forever, not just for as long as a signature stays valid.
*/
Route::get('/directory/{member}', [MemberDirectoryController::class, 'show'])
    ->name('directory.show');

/*
| Everything else is staff-only. There is no public sign-up: this tool has a
| small, known set of operators and a registration form would only ever be a
| liability. Create admins with `php artisan make:filament-user` style
| seeding or the tinker snippet in the README.
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/members', Members::class)->name('members');
    Route::get('/events', Events::class)->name('events');
    Route::get('/announcements', Announcements::class)->name('announcements');

    Route::get('/profile', function () {
        return view('profile');
    })->name('profile');

    Route::post('/logout', function (Logout $logout) {
        $logout();

        return redirect('/');
    })->name('logout');
});

require __DIR__ . '/auth.php';
