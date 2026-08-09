<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleSocialiteController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::get('/auth/google/redirect', [GoogleSocialiteController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleSocialiteController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware('auth')->group(function () {
    // M3 の RecordController@index に置き換わる暫定プレースホルダ
    Route::get('/', fn () => Inertia::render('Record/Index'))->name('home');

    // M2 の ProfileController@create に置き換わる暫定プレースホルダ
    Route::get('/profile/register', fn () => Inertia::render('Profile/Register'))->name('profile.register');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');
