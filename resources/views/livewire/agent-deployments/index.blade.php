<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Agent Deployments</h1>
            <a href="{{ route('agent-deployments.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                New Deployment
            </a>
        </div>

        @if(session('message'))
            <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
                {{ session('message') }}
            </div>
        @endif

        <div class="bg-white shadow rounded-lg p-4 mb-6">
            <div class="flex flex-col sm:flex-row gap-4">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search deployments..."
                    class="border border-gray-300 rounded px-4 py-2 w-full sm:w-1/2">

                <select wire:model.live="statusFilter" class="border border-gray-300 rounded px-4 py-2">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="running">Running</option>
                    <option value="stopped">Stopped</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th wire:click="sortBy('name')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer">
                            Name @if($sortField === 'name') {{ $sortDirection === 'asc' ? '↑' : '↓' }} @endif
                        </th>
                        <th wire:click="sortBy('status')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer">
                            Status @if($sortField === 'status') {{ $sortDirection === 'asc' ? '↑' : '↓' }} @endif
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Replicas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Domain</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($deployments as $deployment)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $deployment->name }}</div>
                                <div class="text-sm text-gray-500">{{ $deployment->slug }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    @if($deployment->status === 'running') bg-green-100 text-green-800
                                    @elseif($deployment->status === 'pending') bg-yellow-100 text-yellow-800
                                    @elseif($deployment->status === 'failed') bg-red-100 text-red-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ $deployment->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $deployment->replicas }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 truncate max-w-xs">
                                {{ $deployment->image }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $deployment->domain }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('agent-deployments.show', $deployment->id) }}" class="text-blue-600 hover:text-blue-900">View</a>
                                    <a href="{{ route('agent-deployments.edit', $deployment->id) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>

                                    @if($deployment->status !== 'running')
                                        <button wire:click="scale('{{ $deployment->id }}', 1)" class="text-green-600 hover:text-green-900">Start</button>
                                    @else
                                        <button wire:click="scale('{{ $deployment->id }}', 0)" class="text-orange-600 hover:text-orange-900">Stop</button>
                                    @endif

                                    <button wire:click="delete('{{ $deployment->id }}')" wire:confirm="Are you sure you want to delete this deployment?"
                                        class="text-red-600 hover:text-red-900">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">No deployments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $deployments->links() }}
        </div>
    </div>
</div>
