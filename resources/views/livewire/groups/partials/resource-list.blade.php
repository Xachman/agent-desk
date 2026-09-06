<div class="bg-white shadow rounded-lg overflow-hidden">
    @if($resources->isEmpty())
        <div class="p-8 text-center text-gray-500">No {{ $type }}s found in this group.</div>
    @else
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($resources as $resource)
                    <tr>
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $resource->name }}</td>
                        <td class="px-6 py-4 text-gray-600">
                            @if($type === 'agent')
                                Template: {{ $resource->template?->name ?? 'None' }}
                            @elseif($type === 'template')
                                {{ Str::limit($resource->system_prompt, 50) }}
                            @elseif($type === 'secret')
                                Key: {{ $resource->key }}
                            @elseif($type === 'deployment')
                                Status: {{ $resource->status }} | Replicas: {{ $resource->replicas }}
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if($type === 'agent')
                                <a href="{{ route('dashboard', ['edit' => $resource->id, 'group' => $group->id, 'tab' => 'agents']) }}" class="text-indigo-600 hover:text-indigo-900 mr-3" title="{{ $canAdmin ? 'Edit' : 'View' }}">
                                    <i class="fas {{ $canAdmin ? 'fa-edit' : 'fa-eye' }}"></i>
                                </a>
                            @elseif($type === 'template')
                                <a href="{{ route('agent-templates.edit', ['template' => $resource->id, 'group' => $group->id]) }}" class="text-indigo-600 hover:text-indigo-900 mr-3" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                            @elseif($type === 'secret')
                                <a href="{{ route('secrets.edit', ['secret' => $resource->id, 'group' => $group->id]) }}" class="text-indigo-600 hover:text-indigo-900 mr-3" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                            @elseif($type === 'deployment')
                                <a href="{{ route('agent-deployments.show', $resource->id) }}" class="text-blue-600 hover:text-blue-900 mr-3" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                            @endif

                            @if($canAdmin)
                                <button class="text-red-600 hover:text-red-900" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
