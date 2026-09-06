<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Agent Desk' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
    @livewireStyles
</head>
<body class="bg-gray-100 text-sm">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-slate-800 text-white flex flex-col fixed h-full z-20">
            <div class="h-16 flex items-center px-6 bg-slate-900 border-b border-slate-700">
                <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-robot text-white"></i>
                </div>
                <span class="text-lg font-semibold">Agent Desk</span>
            </div>

            <nav class="flex-1 overflow-y-auto py-4">
                <div class="px-4 mb-2 text-xs font-semibold text-slate-400 uppercase tracking-wider">Main</div>
                <a href="{{ route('dashboard') }}" class="flex items-center px-6 py-3 hover:bg-slate-700 {{ request()->routeIs('dashboard') ? 'bg-slate-700 border-l-4 border-blue-500' : '' }}">
                    <i class="fas fa-tachometer-alt w-5"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('agent-templates.index') }}" class="flex items-center px-6 py-3 hover:bg-slate-700 {{ request()->routeIs('agent-templates.*') ? 'bg-slate-700 border-l-4 border-blue-500' : '' }}">
                    <i class="fas fa-layer-group w-5"></i>
                    <span>Templates</span>
                </a>
                <a href="{{ route('secrets.index') }}" class="flex items-center px-6 py-3 hover:bg-slate-700 {{ request()->routeIs('secrets.*') ? 'bg-slate-700 border-l-4 border-blue-500' : '' }}">
                    <i class="fas fa-key w-5"></i>
                    <span>Secrets</span>
                </a>
                <a href="{{ route('groups.index') }}" class="flex items-center px-6 py-3 hover:bg-slate-700 {{ request()->routeIs('groups.*') ? 'bg-slate-700 border-l-4 border-blue-500' : '' }}">
                    <i class="fas fa-users w-5"></i>
                    <span>Groups</span>
                </a>

                @if(auth()->user()->role === 'admin')
                    <div class="px-4 mt-6 mb-2 text-xs font-semibold text-slate-400 uppercase tracking-wider">Administration</div>
                    <a href="{{ route('admin') }}" class="flex items-center px-6 py-3 hover:bg-slate-700 {{ request()->routeIs('admin') ? 'bg-slate-700 border-l-4 border-blue-500' : '' }}">
                        <i class="fas fa-user-shield w-5"></i>
                        <span>Admin Panel</span>
                    </a>
                    <a href="{{ route('agent-deployments.index') }}" class="flex items-center px-6 py-3 hover:bg-slate-700 {{ request()->routeIs('agent-deployments.*') ? 'bg-slate-700 border-l-4 border-blue-500' : '' }}">
                        <i class="fas fa-server w-5"></i>
                        <span>Deployments</span>
                    </a>
                @endif
            </nav>
        </aside>

        <!-- Main wrapper -->
        <div class="flex-1 flex flex-col ml-64">
            <!-- Top navbar -->
            <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6 sticky top-0 z-10">
                <div class="flex items-center text-gray-500">
                    <i class="fas fa-bars mr-4 cursor-pointer hover:text-gray-700"></i>
                    <nav class="text-sm">
                        @if(isset($breadcrumb))
                            {{ $breadcrumb }}
                        @else
                            <span class="text-gray-700">Home</span>
                            <span class="mx-2">/</span>
                            <span class="text-gray-500">{{ $title ?? 'Dashboard' }}</span>
                        @endif
                    </nav>
                </div>

                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-3 text-gray-500">
                        <i class="fas fa-search hover:text-gray-700 cursor-pointer"></i>
                        <div class="relative">
                            <i class="fas fa-bell hover:text-gray-700 cursor-pointer"></i>
                        </div>
                        <div class="relative">
                            <i class="fas fa-envelope hover:text-gray-700 cursor-pointer"></i>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pl-4 border-l border-gray-200">
                        <div class="text-right">
                            <div class="text-sm font-medium text-gray-700">{{ auth()->user()->name }}</div>
                            <div class="text-xs text-gray-500">{{ auth()->user()->role }}</div>
                        </div>
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-blue-600"></i>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 p-6">
                @if(session('message'))
                    <div class="mb-4 p-4 bg-green-100 text-green-800 rounded border border-green-200">
                        <i class="fas fa-check-circle mr-2"></i>{{ session('message') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 p-4 bg-red-100 text-red-800 rounded border border-red-200">
                        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
                    </div>
                @endif

                {{ $slot }}
            </main>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200 p-4">
                <div class="flex justify-between text-sm text-gray-500">
                    <div>Copyright &copy; {{ date('Y') }} Agent Desk. All rights reserved.</div>
                    <div>Anything you want</div>
                </div>
            </footer>
        </div>
    </div>

    @livewireScripts
</body>
</html>
