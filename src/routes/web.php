<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleSocialiteController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecordController;
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
        Route::get('/', [RecordController::class, 'index'])->name('home');

        Route::get('/settings/profile', [ProfileController::class, 'edit'])->name('settings.profile.edit');
        Route::patch('/settings/profile', [ProfileController::class, 'update'])->name('settings.profile.update');

        // M8 の SettingsController@index に置き換わる暫定プレースホルダ
        Route::get('/settings', fn () => Inertia::render('Settings/Index'))->name('settings.index');

        // M7 の StatsController@index に置き換わる暫定プレースホルダ
        Route::get('/stats', fn () => Inertia::render('Stats/Index'))->name('stats.index');

        // M6 の HistoryController@index に置き換わる暫定プレースホルダ
        Route::get('/history', fn () => Inertia::render('History/Index'))->name('history.index');
    });
});

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');
