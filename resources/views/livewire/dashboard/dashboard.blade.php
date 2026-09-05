<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
                <p class="text-gray-600">Welcome, {{ auth()->user()->name }}</p>
            </div>
            <div class="flex gap-3">
                @if(auth()->user()->role === 'admin')
                    <a href="{{ route('admin') }}" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Admin Panel</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Logout</button>
                </form>
            </div>
        </div>

        @if(session('message'))
            <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">{{ session('message') }}</div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">{{ session('error') }}</div>
        @endif

        <!-- Tabs -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex gap-6">
                <button wire:click="setTab('agents')" class="py-2 px-1 border-b-2 font-medium text-sm
                    {{ $activeTab === 'agents' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">My Agents</button>
                <button wire:click="setTab('executions')" class="py-2 px-1 border-b-2 font-medium text-sm
                    {{ $activeTab === 'executions' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">Executions</button>
            </nav>
        </div>

        @if($activeTab === 'agents')
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">My Agents</h2>
                    <button wire:click="createAgent" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Create Agent</button>
                </div>

                @if($showAgentForm)
                    <form wire:submit="saveAgent" class="mb-6 bg-gray-50 p-4 rounded-lg space-y-4">
                        <h3 class="font-semibold text-gray-900">{{ $editingAgentId ? 'Edit Agent' : 'Create Agent' }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Name</label>
                                <input type="text" wire:model="agentName" class="mt-1 block w-full border rounded px-3 py-2">
                                @error('agentName') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea wire:model="agentDescription" rows="2" class="mt-1 block w-full border rounded px-3 py-2"></textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Template</label>
                                <select wire:model="agentTemplateId" class="mt-1 block w-full border rounded px-3 py-2">
                                    <option value="">— None —</option>
                                    @foreach($templates as $template)
                                        <option value="{{ $template->id }}">{{ $template->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Config (JSON)</label>
                                <textarea wire:model="agentConfigJson" rows="5" class="mt-1 block w-full border rounded px-3 py-2 font-mono text-sm"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Environment Variables (JSON)</label>
                                <textarea wire:model="agentEnvJson" rows="5" class="mt-1 block w-full border rounded px-3 py-2 font-mono text-sm"></textarea>
                            </div>
                            <div class="flex items-center md:col-span-2">
                                <input type="checkbox" wire:model="agentIsActive" class="rounded border-gray-300">
                                <span class="ml-2 text-sm text-gray-700">Active</span>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" wire:click="$set('showAgentForm', false)" class="px-4 py-2 border rounded">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Save</button>
                        </div>
                    </form>
                @endif

                @if($showRunForm)
                    <form wire:submit="executeAgent" class="mb-6 bg-blue-50 p-4 rounded-lg space-y-4">
                        <h3 class="font-semibold text-gray-900">Run Agent</h3>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Input (JSON)</label>
                            <textarea wire:model="runInputJson" rows="5" class="mt-1 block w-full border rounded px-3 py-2 font-mono text-sm"></textarea>
                            @error('runInputJson') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Context (JSON, optional)</label>
                            <textarea wire:model="runContextJson" rows="3" class="mt-1 block w-full border rounded px-3 py-2 font-mono text-sm"></textarea>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" wire:click="$set('showRunForm', false)" class="px-4 py-2 border rounded">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded">Run</button>
                        </div>
                    </form>
                @endif

                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Template</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($agents as $agent)
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $agent->name }}</div>
                                    <div class="text-sm text-gray-500">{{ Str::limit($agent->description, 60) }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $agent->template?->name ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $agent->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $agent->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $agent->created_at->diffForHumans() }}</td>
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <div class="flex justify-end gap-2">
                                        <button wire:click="runAgentModal('{{ $agent->id }}')" class="text-green-600 hover:text-green-900">Run</button>
                                        <button wire:click="editAgent('{{ $agent->id }}')" class="text-indigo-600 hover:text-indigo-900">Edit</button>
                                        <button wire:click="deleteAgent('{{ $agent->id }}')" wire:confirm="Delete this agent?" class="text-red-600 hover:text-red-900">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">No agents yet. Create one to get started.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">{{ $agents->links() }}</div>
            </div>
        @endif

        @if($activeTab === 'executions')
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Executions</h2>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Agent</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progress</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Started</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($executions as $execution)
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $execution->agent?->name ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        @if($execution->status === 'running') bg-green-100 text-green-800
                                        @elseif($execution->status === 'pending') bg-yellow-100 text-yellow-800
                                        @elseif($execution->status === 'failed') bg-red-100 text-red-800
                                        @elseif($execution->status === 'cancelled') bg-gray-100 text-gray-800
                                        @else bg-blue-100 text-blue-800
                                        @endif">
                                        {{ $execution->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $execution->progress }}%</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $execution->started_at?->diffForHumans() ?? '—' }}</td>
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    @if($execution->status === 'running')
                                        <button wire:click="cancelExecution('{{ $execution->id }}')" class="text-orange-600 hover:text-orange-900">Cancel</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">No executions yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">{{ $executions->links() }}</div>
            </div>
        @endif
    </div>
</div>
