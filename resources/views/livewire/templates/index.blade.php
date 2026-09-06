<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-layer-group mr-2"></i>
            @if($group)
                {{ $group->name }} Templates
            @else
                Templates
            @endif
        </h1>
        @if($canCreate)
            <a href="{{ $group ? route('agent-templates.create', ['group' => $group->id]) : route('agent-templates.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>New Template
            </a>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b border-gray-200">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search templates..."
                class="border border-gray-300 rounded px-4 py-2 w-full sm:w-1/3">
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Agents Using</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($templates as $template)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ $template->name }}</div>
                                <div class="text-sm text-gray-500">{{ Str::limit($template->description, 80) }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">{{ $template->agents_count }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $template->created_at->diffForHumans() }}</td>
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <div class="flex justify-end gap-3">
                                    <a href="{{ $group ? route('agent-templates.edit', ['template' => $template->id, 'group' => $group->id]) : route('agent-templates.edit', $template->id) }}" class="text-indigo-600 hover:text-indigo-900" title="{{ $template->canAdmin(auth()->user()) ? 'Edit' : 'View' }}">
                                        <i class="fas {{ $template->canAdmin(auth()->user()) ? 'fa-edit' : 'fa-eye' }}"></i>
                                    </a>
                                    @if($template->canAdmin(auth()->user()))
                                        <button wire:click="delete('{{ $template->id }}')" wire:confirm="Delete this template?" class="text-red-600 hover:text-red-900" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">No templates yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $templates->links() }}</div>
</div>
