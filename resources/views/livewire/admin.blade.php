<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Admin Panel</h1>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <div class="text-sm text-gray-500">Users</div>
                    <div class="text-2xl font-bold text-gray-800">{{ \App\Models\User::count() }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center">
                    <i class="fas fa-robot text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <div class="text-sm text-gray-500">Agents</div>
                    <div class="text-2xl font-bold text-gray-800">{{ \App\Models\Agent::count() }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center">
                    <i class="fas fa-layer-group text-purple-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <div class="text-sm text-gray-500">Templates</div>
                    <div class="text-2xl font-bold text-gray-800">{{ \App\Models\AgentTemplate::count() }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-lg bg-orange-100 flex items-center justify-center">
                    <i class="fas fa-server text-orange-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <div class="text-sm text-gray-500">Deployments</div>
                    <div class="text-2xl font-bold text-gray-800">{{ \App\Models\AgentDeployment::count() }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Welcome, {{ auth()->user()->name }}</h3>
            </div>
            <div class="p-6 space-y-3">
                <p class="text-gray-600"><i class="fas fa-envelope mr-2 text-gray-400"></i>{{ auth()->user()->email }}</p>
                <p class="text-gray-600"><i class="fas fa-id-badge mr-2 text-gray-400"></i>Role: <span class="font-semibold">{{ auth()->user()->role }}</span></p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Admin Features</h3>
            </div>
            <div class="p-6">
                <ul class="space-y-3">
                    <li>
                        <a href="{{ route('agent-deployments.index') }}" class="flex items-center text-blue-600 hover:text-blue-800">
                            <i class="fas fa-server w-6"></i>
                            <span>Manage Kubernetes Agent Deployments</span>
                        </a>
                    </li>
                    <li class="flex items-center text-gray-500">
                        <i class="fas fa-users w-6"></i>
                        <span>User Management</span>
                    </li>
                    <li class="flex items-center text-gray-500">
                        <i class="fas fa-chart-line w-6"></i>
                        <span>Analytics Dashboard</span>
                    </li>
                    <li class="flex items-center text-gray-500">
                        <i class="fas fa-cog w-6"></i>
                        <span>System Settings</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
