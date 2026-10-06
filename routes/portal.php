<?php

use App\Http\Controllers\PortalDocumentController;
use App\Livewire\Portal\Dashboard;
use App\Livewire\Portal\Documents;
use App\Livewire\Portal\ForgotPassword;
use App\Livewire\Portal\Login;
use App\Livewire\Portal\Products;
use App\Livewire\Portal\Profile;
use App\Livewire\Portal\ResetPassword;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
| The member-facing portal. Entirely separate guard ('member', see
| config/auth.php) from staff's 'web' guard used everywhere else in this
| app — a member session cannot reach a staff route, and a staff session
| cannot reach a member-only action, because the guards are different users
| tables with no overlap.
*/
Route::prefix('portal')->name('portal.')->group(function () {
    Route::middleware('guest:member')->group(function () {
        Route::get('login', Login::class)->name('login');
        Route::get('forgot-password', ForgotPassword::class)->name('password.request');
        Route::get('reset-password/{token}', ResetPassword::class)->name('password.reset');
    });

    Route::middleware('auth:member')->group(function () {
        Route::get('/', Dashboard::class)->name('dashboard');
        Route::get('profile', Profile::class)->name('profile');
        Route::get('documents', Documents::class)->name('documents');
        Route::get('documents/registration', [PortalDocumentController::class, 'registration'])
            ->name('documents.registration');
        Route::get('products', Products::class)->name('products');

        Route::post('logout', function () {
            Auth::guard('member')->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('portal.login');
        })->name('logout');
    });
});
