<div>
    @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            @foreach($errors->all() as $error)
                <p><i class="fas fa-exclamation-circle mr-2"></i>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
    @endif

    @if($errorMessage)
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ $errorMessage }}
        </div>
    @endif

    <form wire:submit.prevent="login">
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2" for="username">Email</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                    <i class="fas fa-envelope"></i>
                </span>
                <input
                    class="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    id="username"
                    type="text"
                    wire:model="username"
                    placeholder="you@example.com"
                    required
                    autofocus
                >
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2" for="password">Password</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                    <i class="fas fa-lock"></i>
                </span>
                <input
                    class="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    id="password"
                    type="password"
                    wire:model="password"
                    placeholder="••••••••"
                    required
                >
            </div>
        </div>

        <div class="mb-6 flex items-center justify-between">
            <label class="flex items-center">
                <input
                    type="checkbox"
                    wire:model="remember"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                >
                <span class="ml-2 text-sm text-gray-600">Remember me</span>
            </label>
        </div>

        <button
            type="submit"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-4 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
        >
            <i class="fas fa-sign-in-alt mr-2"></i>Sign In
        </button>
    </form>

    @if(session('message'))
        <div class="mt-4 p-4 bg-green-50 text-green-700 rounded-lg border border-green-200 text-center">
            <i class="fas fa-check-circle mr-2"></i>{{ session('message') }}
        </div>
    @endif
</div>
