<div class="max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Groups</h1>
        <a href="{{ route('groups.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i>Create Group
        </a>
    </div>

    <div class="mb-6">
        <input wire:model.live="search" type="text" placeholder="Search groups..." class="w-full md:w-1/3 border border-gray-300 rounded px-4 py-2">
    </div>

    @if($ownedGroups->isNotEmpty())
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Owned Groups</h2>
        <div class="bg-white shadow rounded-lg overflow-hidden mb-8">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Members</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($ownedGroups as $group)
                        <tr>
                            <td class="px-6 py-4">
                                <a href="{{ route('groups.show', $group->id) }}" class="text-blue-600 hover:underline font-medium">{{ $group->name }}</a>
                                <div class="text-xs text-gray-500">{{ $group->slug }}</div>
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ Str::limit($group->description, 80) }}</td>
                            <td class="px-6 py-4">{{ $group->users()->count() }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('groups.show', $group->id) }}" class="text-blue-600 hover:text-blue-900 mr-3" title="Manage">
                                    <i class="fas fa-users-cog"></i>
                                </a>
                                <button wire:click="delete('{{ $group->id }}')" wire:confirm="Delete this group? All group resources will remain but lose their group association." class="text-red-600 hover:text-red-900" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if($memberGroups->isNotEmpty())
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Member Groups</h2>
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Your Role</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($memberGroups as $group)
                        <tr>
                            <td class="px-6 py-4">
                                <a href="{{ route('groups.show', $group->id) }}" class="text-blue-600 hover:underline font-medium">{{ $group->name }}</a>
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ Str::limit($group->description, 80) }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded text-xs font-medium {{ $group->pivot->role === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($group->pivot->role) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('groups.show', $group->id) }}" class="text-blue-600 hover:text-blue-900 mr-3" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button wire:click="leave('{{ $group->id }}')" wire:confirm="Leave this group?" class="text-red-600 hover:text-red-900" title="Leave">
                                    <i class="fas fa-sign-out-alt"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if($ownedGroups->isEmpty() && $memberGroups->isEmpty())
        <div class="bg-white shadow rounded-lg p-8 text-center text-gray-500">
            No groups found. <a href="{{ route('groups.create') }}" class="text-blue-600 hover:underline">Create one</a>.
        </div>
    @endif
</div>
