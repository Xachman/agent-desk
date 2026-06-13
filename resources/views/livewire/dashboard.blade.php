@extends('layouts.app')

@section('content')
    <div class="min-h-screen flex items-center justify-center bg-gray-100">
        <div class="bg-white shadow-md rounded-lg p-8 max-w-md w-full">
            <h1 class="text-3xl font-bold text-center mb-8 text-gray-800">Admin Dashboard</h1>

            @if(session('status'))
                <div class="mb-4 text-green-600">{{ session('status') }}</div>
            @endif

            <div class="space-y-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Welcome, Admin!</label>
                    <p class="text-gray-600">This is your protected admin dashboard.</p>
                </div>

                <div class="pt-4">
                    <a href="{{ route('logout') }}" class="w-full bg-red-500 text-white font-bold py-2 px-4 rounded hover:bg-red-600 transition-colors">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection