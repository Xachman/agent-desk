<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

// Livewire routes
Route::get('/login', \App\Livewire\Login::class)->name('login');
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->name('logout');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::view('/admin', 'livewire.admin')->name('admin');
    Route::view('/dashboard', 'livewire.dashboard')->name('dashboard');
});
