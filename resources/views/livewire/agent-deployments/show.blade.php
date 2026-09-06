<div>
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-server mr-2"></i>{{ $deployment->name }}</h1>
            <p class="text-sm text-gray-500">{{ $deployment->slug }} · {{ $deployment->status }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('agent-deployments.index') }}" class="text-gray-600 hover:text-gray-900 self-center">
                <i class="fas fa-arrow-left mr-1"></i>Back
            </a>
            <a href="{{ route('agent-deployments.edit', $deployment->id) }}" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                <i class="fas fa-edit mr-2"></i>Edit
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800"><i class="fas fa-info-circle mr-2"></i>Details</h2>
            </div>
            <div class="p-6">
                <dl class="space-y-3 text-sm">
                    <dt class="font-medium text-gray-700">Image</dt>
                    <dd class="text-gray-600 truncate">{{ $deployment->image }}</dd>

                    <dt class="font-medium text-gray-700">Namespace</dt>
                    <dd class="text-gray-600">{{ $deployment->namespace }}</dd>

                    <dt class="font-medium text-gray-700">Domain</dt>
                    <dd class="text-gray-600">{{ $deployment->domain }}</dd>

                    <dt class="font-medium text-gray-700">Replicas</dt>
                    <dd class="text-gray-600">{{ $deployment->replicas }}</dd>

                    <dt class="font-medium text-gray-700">Active</dt>
                    <dd class="text-gray-600">{{ $deployment->is_active ? 'Yes' : 'No' }}</dd>

                    @if($deployment->agent)
                        <dt class="font-medium text-gray-700">Linked Agent</dt>
                        <dd class="text-gray-600">{{ $deployment->agent->name }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow lg:col-span-2">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800"><i class="fas fa-tools mr-2"></i>Actions</h2>
            </div>
            <div class="p-6">
                <div class="flex flex-wrap gap-3">
                    @if($deployment->replicas === 0)
                        <button wire:click="scale(1)" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                            <i class="fas fa-play mr-2"></i>Start (1 replica)
                        </button>
                    @else
                        <button wire:click="scale(0)" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700">
                            <i class="fas fa-stop mr-2"></i>Stop (0 replicas)
                        </button>
                        <button wire:click="scale({{ min($deployment->replicas + 1, 10) }})" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            <i class="fas fa-plus mr-2"></i>Scale Up
                        </button>
                        @if($deployment->replicas > 1)
                            <button wire:click="scale({{ max($deployment->replicas - 1, 1) }})" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                                <i class="fas fa-minus mr-2"></i>Scale Down
                            </button>
                        @endif
                    @endif

                    <button wire:click="setStatus('running')" class="px-4 py-2 border border-green-600 text-green-600 rounded hover:bg-green-50">Mark Running</button>
                    <button wire:click="setStatus('stopped')" class="px-4 py-2 border border-gray-600 text-gray-600 rounded hover:bg-gray-50">Mark Stopped</button>
                    <button wire:click="setStatus('failed')" class="px-4 py-2 border border-red-600 text-red-600 rounded hover:bg-red-50">Mark Failed</button>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800"><i class="fas fa-code mr-2"></i>Generated Kubernetes Manifest</h2>
        </div>
        <div class="p-6">
            <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto text-sm font-mono"><code>{{ $yaml }}</code></pre>
        </div>
    </div>
</div>
