<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Admin extends Component
{
    public function render()
    {
        return view('livewire.admin')
            ->layout('layouts.adminlte', ['title' => 'Admin Panel']);
    }

    public function logout()
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }
}
