<div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="bg-white shadow-md rounded-lg p-8 max-w-md w-full">
        <h1 class="text-3xl font-bold text-center mb-8 text-gray-800">Admin Panel</h1>

        @if(session('message'))
            <div class="mb-4 text-green-600 bg-green-100 p-4 rounded">
                {{ session('message') }}
            </div>
        @endif

        <div class="space-y-4">
            <div class="bg-gray-50 p-4 rounded-lg">
                <h2 class="text-xl font-semibold text-gray-800 mb-2">Welcome, {{ auth()->user()->name }}</h2>
                <p class="text-gray-600">This is your protected admin panel.</p>
                <p class="text-sm text-gray-500 mt-2">Email: {{ auth()->user()->email }}</p>
                <p class="text-sm text-gray-500">Role: {{ auth()->user()->role }}</p>
            </div>

            <div class="pt-4">
                <a href="{{ route('logout') }}" wire:click="logout" class="w-full bg-red-500 text-white font-bold py-2 px-4 rounded hover:bg-red-600 transition-colors">
                    Logout
                </a>
            </div>
        </div>

        @if(auth()->user()->role === 'admin')
            <div class="mt-6 p-4 bg-blue-50 border-l-4 border-blue-500">
                <h3 class="font-semibold text-blue-800">Admin Features Available:</h3>
                <ul class="list-disc list-inside mt-2 text-blue-700">
                    <li>User Management</li>
                    <li>Content Management</li>
                    <li>Analytics Dashboard</li>
                    <li>System Settings</li>
                </ul>
            </div>
        @endif
    </div>
</div>