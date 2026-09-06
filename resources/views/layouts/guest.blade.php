<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Desk - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
    @livewireStyles
</head>
<body class="bg-gradient-to-br from-slate-800 to-slate-900 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-blue-500 rounded-xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                <i class="fas fa-robot text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-white">Agent Desk</h1>
            <p class="text-slate-400">Sign in to manage your AI agents</p>
        </div>

        <div class="bg-white rounded-xl shadow-2xl overflow-hidden">
            <div class="px-8 py-6 bg-slate-50 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Welcome back</h2>
                <p class="text-sm text-gray-500">Enter your credentials to continue</p>
            </div>

            <div class="p-8">
                {{ $slot }}
            </div>
        </div>

        <p class="text-center text-slate-400 text-sm mt-6">
            Copyright &copy; {{ date('Y') }} Agent Desk. All rights reserved.
        </p>
    </div>

    @livewireScripts
</body>
</html>
