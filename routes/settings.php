<?php

use App\Http\Controllers\Settings\MailAccountController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Support\Facades\Route;

/*
| Settings stay reachable before the login email is verified; only sending
| applications requires a verified email (see ApplicationController::send).
*/

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::get('settings/email', [MailAccountController::class, 'edit'])->name('mail-account.edit');
    Route::put('settings/email', [MailAccountController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('mail-account.update');
    Route::delete('settings/email', [MailAccountController::class, 'destroy'])->name('mail-account.destroy');

    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');
});
