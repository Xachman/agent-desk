<div class="max-w-6xl mx-auto">
    <div class="flex justify-between items-start mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $group->name }}</h1>
            <p class="text-gray-600">{{ $group->description }}</p>
            <div class="mt-2 flex gap-2">
                <span class="px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">Owner: {{ $group->owner->name }}</span>
                <span class="px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800">{{ $group->users->count() }} members</span>
            </div>
        </div>
        <a href="{{ route('groups.index') }}" class="text-blue-600 hover:underline">← Back to groups</a>
    </div>

    <div class="border-b border-gray-200 mb-6">
        <nav class="flex gap-6">
            <button wire:click="setTab('members')" class="pb-2 px-1 {{ $activeTab === 'members' ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-500' }}">Members</button>
            <button wire:click="setTab('agents')" class="pb-2 px-1 {{ $activeTab === 'agents' ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-500' }}">Agents</button>
            <button wire:click="setTab('templates')" class="pb-2 px-1 {{ $activeTab === 'templates' ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-500' }}">Templates</button>
            <button wire:click="setTab('secrets')" class="pb-2 px-1 {{ $activeTab === 'secrets' ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-500' }}">Secrets</button>
            <button wire:click="setTab('deployments')" class="pb-2 px-1 {{ $activeTab === 'deployments' ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-500' }}">Deployments</button>
        </nav>
    </div>

    @if($activeTab === 'members')
        <div class="bg-white shadow rounded-lg p-6">
            @if($canAdmin)
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Add Member</h2>
                <form wire:submit="addMember" class="flex gap-3 mb-8">
                    <input type="email" wire:model="memberEmail" placeholder="User email" class="border border-gray-300 rounded px-3 py-2 flex-1" required>
                    <select wire:model="memberRole" class="border border-gray-300 rounded px-3 py-2">
                        <option value="member">Member</option>
                        <option value="admin">Admin</option>
                    </select>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Add</button>
                </form>
            @endif

            <h2 class="text-lg font-semibold text-gray-800 mb-4">Members</h2>
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                        @if($canAdmin)
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr class="bg-blue-50">
                        <td class="px-6 py-4 font-medium">{{ $group->owner->name }} (Owner)</td>
                        <td class="px-6 py-4">{{ $group->owner->email }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-800">Owner</span>
                        </td>
                        @if($canAdmin)
                            <td></td>
                        @endif
                    </tr>
                    @foreach($members as $member)
                        @if($member->id !== $group->owner_id)
                            <tr>
                                <td class="px-6 py-4">{{ $member->name }}</td>
                                <td class="px-6 py-4">{{ $member->email }}</td>
                                <td class="px-6 py-4">
                                    @if($canManage && $member->pivot->role !== 'owner')
                                        <select wire:change="changeRole('{{ $member->id }}', $event.target.value)" class="border border-gray-300 rounded px-2 py-1 text-sm">
                                            <option value="member" {{ $member->pivot->role === 'member' ? 'selected' : '' }}>Member</option>
                                            <option value="admin" {{ $member->pivot->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                        </select>
                                    @else
                                        <span class="px-2 py-1 rounded text-xs font-medium {{ $member->pivot->role === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ ucfirst($member->pivot->role) }}
                                        </span>
                                    @endif
                                </td>
                                @if($canAdmin)
                                    <td class="px-6 py-4 text-right">
                                        <button wire:click="removeMember('{{ $member->id }}')" wire:confirm="Remove this member?" class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                @endif
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if($activeTab === 'agents')
        <div>
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Group Agents</h2>
                @if($canAdmin)
                    <a href="{{ route('dashboard', ['group' => $group->id, 'tab' => 'agents']) }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>Create Agent
                    </a>
                @endif
            </div>
            @include('livewire.groups.partials.resource-list', ['resources' => $agents, 'type' => 'agent', 'canAdmin' => $canAdmin])
        </div>
    @endif

    @if($activeTab === 'templates')
        <div>
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Group Templates</h2>
                @if($canAdmin)
                    <a href="{{ route('agent-templates.create', ['group' => $group->id]) }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>Create Template
                    </a>
                @endif
            </div>
            @include('livewire.groups.partials.resource-list', ['resources' => $templates, 'type' => 'template', 'canAdmin' => $canAdmin])
        </div>
    @endif

    @if($activeTab === 'secrets')
        <div>
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Group Secrets</h2>
                @if($canAdmin)
                    <a href="{{ route('secrets.create', ['group' => $group->id]) }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>Create Secret
                    </a>
                @endif
            </div>
            @include('livewire.groups.partials.resource-list', ['resources' => $secrets, 'type' => 'secret', 'canAdmin' => $canAdmin])
        </div>
    @endif

    @if($activeTab === 'deployments')
        <div>
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Group Deployments</h2>
            </div>
            @include('livewire.groups.partials.resource-list', ['resources' => $deployments, 'type' => 'deployment', 'canAdmin' => $canAdmin])
        </div>
    @endif
</div>
