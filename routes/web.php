<?php

use App\Http\Controllers\MemberDirectoryController;
use App\Http\Controllers\MemberDocumentController;
use App\Http\Controllers\UnsubscribeController;
use App\Livewire\Actions\Logout;
use App\Livewire\Announcements;
use App\Livewire\BusinessTypes;
use App\Livewire\Dashboard;
use App\Livewire\Events;
use App\Livewire\MemberCreate;
use App\Livewire\Members;
use App\Livewire\MemberTypes;
use App\Livewire\Roles;
use App\Livewire\Users;
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
| work forever, not just for as long as a signature stays valid. The index
| is the browsable front door; /directory/{member} is the direct link to one
| company's page that the index (and every shared link) points at.
*/
Route::get('/directory', [MemberDirectoryController::class, 'index'])
    ->name('directory.index');

Route::get('/directory/{member}', [MemberDirectoryController::class, 'show'])
    ->name('directory.show');

/*
| Public self-registration. Unauthenticated on purpose — this is the link
| shared with a prospective member so they can join without staff typing
| their details in. MemberCreate itself decides what happens on submit: a
| signed-in staff member creates an active member; a public visitor lands
| as 'pending' for staff to review on the Members page.
*/
Route::get('/members/create', MemberCreate::class)->name('members.create');

/*
| Everything else is staff-only.
*/
Route::middleware(['auth:web'])->group(function () {
    // Open to anyone signed in, regardless of permissions — an overview
    // landing page, not a place that does anything to member data.
    Route::get('/', Dashboard::class)->name('dashboard');

    Route::get('/profile', function () {
        return view('profile');
    })->name('profile');

    Route::post('/logout', function (Logout $logout) {
        $logout();

        return redirect('/');
    })->name('logout');

    // Everything below is gated by a specific permission, checked again
    // inside the component's own mount() — belt-and-braces, same pattern as
    // the rest of this app (see Members::startPayment).
    Route::middleware('can:manage-members')->group(function () {
        Route::get('/members', Members::class)->name('members');
        Route::get('/members/{member}/registration', [MemberDocumentController::class, 'registration'])
            ->name('members.registration');
    });

    Route::middleware('can:manage-business-types')->group(function () {
        Route::get('/business-types', BusinessTypes::class)->name('business-types');
    });

    Route::middleware('can:manage-events')->group(function () {
        Route::get('/events', Events::class)->name('events');
    });

    Route::middleware('can:send-announcements')->group(function () {
        Route::get('/announcements', Announcements::class)->name('announcements');
    });

    Route::middleware('can:manage-member-types')->group(function () {
        Route::get('/member-types', MemberTypes::class)->name('member-types');
    });

    Route::middleware('can:manage-staff')->group(function () {
        Route::get('/users', Users::class)->name('users');
    });

    Route::middleware('can:manage-roles')->group(function () {
        Route::get('/roles', Roles::class)->name('roles');
    });
});

require __DIR__ . '/auth.php';
require __DIR__ . '/portal.php';
