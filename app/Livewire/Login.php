<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Login extends Component
{
    public string $username = '';
    public string $password = '';
    public bool $remember = false;
    public string $errorMessage = '';

    public function render()
    {
        return view('livewire.login')
            ->layout('layouts.app');
    }

    public function login()
    {
        $credentials = [
            'email' => $this->username,
            'password' => $this->password,
        ];

        if (Auth::attempt($credentials, $this->remember)) {
            $request = request();
            $request->session()->regenerate();

            if (Auth::user()->role === 'admin') {
                return redirect()->route('admin');
            }

            return redirect()->route('dashboard');
        }

        $this->errorMessage = 'Invalid username or password.';
    }

    public function logout()
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }
}