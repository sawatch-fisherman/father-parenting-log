<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleSocialiteController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\EnsureProfileIsComplete;
use App\Http\Middleware\RedirectIfProfileIsComplete;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::get('/auth/google/redirect', [GoogleSocialiteController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleSocialiteController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware('auth')->group(function () {
    Route::middleware(RedirectIfProfileIsComplete::class)->group(function () {
        Route::get('/profile/register', [ProfileController::class, 'create'])->name('profile.register');
        Route::post('/profile', [ProfileController::class, 'store'])->name('profile.store');
    });

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::middleware(EnsureProfileIsComplete::class)->group(function () {
        // M3 の RecordController@index に置き換わる暫定プレースホルダ
        Route::get('/', fn () => Inertia::render('Record/Index'))->name('home');

        Route::get('/settings/profile', [ProfileController::class, 'edit'])->name('settings.profile.edit');
        Route::patch('/settings/profile', [ProfileController::class, 'update'])->name('settings.profile.update');
    });
});

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');
