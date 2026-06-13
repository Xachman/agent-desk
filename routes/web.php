<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

// Livewire routes
Route::get('/login', \App\Livewire\Login::class)->name('login');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::view('/admin', 'livewire.admin')->name('admin');
    Route::view('/dashboard', 'livewire.dashboard')->name('dashboard');
});
