<div>
    <!-- Stat cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500">My Agents</div>
                    <div class="text-2xl font-bold text-gray-800">{{ $agents->total() }}</div>
                </div>
                <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
                    <i class="fas fa-robot text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500">Executions</div>
                    <div class="text-2xl font-bold text-gray-800">{{ $executions->total() }}</div>
                </div>
                <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center">
                    <i class="fas fa-play-circle text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500">Templates</div>
                    <div class="text-2xl font-bold text-gray-800">{{ $templates->count() }}</div>
                </div>
                <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center">
                    <i class="fas fa-layer-group text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500">Running</div>
                    <div class="text-2xl font-bold text-gray-800">{{ $executions->where('status', 'running')->count() }}</div>
                </div>
                <div class="w-12 h-12 rounded-lg bg-orange-100 flex items-center justify-center">
                    <i class="fas fa-spinner text-orange-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white rounded-lg shadow">
        <div class="border-b border-gray-200 px-6">
            <nav class="-mb-px flex gap-6">
                <button wire:click="setTab('agents')" class="py-4 px-1 border-b-2 font-medium text-sm
                    {{ $activeTab === 'agents' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    <i class="fas fa-robot mr-2"></i>My Agents
                </button>
                <button wire:click="setTab('executions')" class="py-4 px-1 border-b-2 font-medium text-sm
                    {{ $activeTab === 'executions' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    <i class="fas fa-history mr-2"></i>Executions
                </button>
            </nav>
        </div>

        <div class="p-6">
            @if($activeTab === 'agents')
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">My Agents</h2>
                    <button wire:click="createAgent" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>Create Agent
                    </button>
                </div>

                @if($showAgentForm)
                    <form wire:submit="saveAgent" class="mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200 space-y-4">
                        <h3 class="font-semibold text-gray-900">{{ $editingAgentId ? 'Edit Agent' : 'Create Agent' }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Name</label>
                                <input type="text" wire:model="agentName" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2">
                                @error('agentName') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea wire:model="agentDescription" rows="2" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2"></textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Template</label>
                                <select wire:model="agentTemplateId" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2">
                                    <option value="">— None —</option>
                                    @foreach($templates as $template)
                                        <option value="{{ $template->id }}">{{ $template->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Config (JSON)</label>
                                <textarea wire:model="agentConfigJson" rows="5" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 font-mono text-sm"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Environment Variables (JSON)</label>
                                <textarea wire:model="agentEnvJson" rows="5" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 font-mono text-sm"></textarea>
                            </div>
                            <div class="flex items-center md:col-span-2">
                                <input type="checkbox" wire:model="agentIsActive" class="rounded border-gray-300">
                                <span class="ml-2 text-sm text-gray-700">Active</span>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" wire:click="$set('showAgentForm', false)" class="px-4 py-2 border border-gray-300 rounded">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Save</button>
                        </div>
                    </form>
                @endif

                <div class="overflow-x-auto">
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
                                        <div class="flex justify-end gap-3">
                                            @if($agent->deployment)
                                                <a href="{{ route('agent-deployments.show', $agent->deployment->id) }}" class="text-blue-600 hover:text-blue-900" title="View Deployment">
                                                    <i class="fas fa-server"></i>
                                                </a>
                                            @endif
                                            <button wire:click="editAgent('{{ $agent->id }}')" class="text-indigo-600 hover:text-indigo-900" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button wire:click="deleteAgent('{{ $agent->id }}')" wire:confirm="Delete this agent?" class="text-red-600 hover:text-red-900" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
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
                </div>

                <div class="mt-4">{{ $agents->links() }}</div>
            @endif

            @if($activeTab === 'executions')
                <h2 class="text-lg font-semibold text-gray-800 mb-4"><i class="fas fa-history mr-2"></i>Executions</h2>

                <div class="overflow-x-auto">
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
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                                            <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $execution->progress }}%"></div>
                                        </div>
                                        <span class="text-xs">{{ $execution->progress }}%</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $execution->started_at?->diffForHumans() ?? '—' }}</td>
                                    <td class="px-6 py-4 text-right text-sm font-medium">
                                        @if($execution->status === 'running')
                                            <button wire:click="cancelExecution('{{ $execution->id }}')" class="text-orange-600 hover:text-orange-900">
                                                <i class="fas fa-stop"></i>
                                            </button>
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
                </div>

                <div class="mt-4">{{ $executions->links() }}</div>
            @endif
        </div>
    </div>
</div>
