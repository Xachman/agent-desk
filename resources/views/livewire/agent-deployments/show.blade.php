<div>
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-server mr-2"></i>{{ $deployment->name }}</h1>
            <p class="text-sm text-gray-500">{{ $deployment->slug }} · {{ $deployment->status }}</p>
        </div>
        <div class="flex gap-3">
            <button wire:click="refreshStatus" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                <i class="fas fa-sync-alt mr-2"></i>Refresh
            </button>
            <button wire:click="deploy" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                <i class="fas fa-rocket mr-2"></i>Deploy
            </button>
            <button wire:click="destroy" wire:confirm="Remove this deployment from the cluster?" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700">
                <i class="fas fa-trash-alt mr-2"></i>Remove
            </button>
            <a href="{{ route('agent-deployments.index') }}" class="text-gray-600 hover:text-gray-900 self-center">
                <i class="fas fa-arrow-left mr-1"></i>Back
            </a>
            <a href="{{ route('agent-deployments.edit', $deployment->id) }}" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                <i class="fas fa-edit mr-2"></i>Edit
            </a>
        </div>
    </div>

    @if($clusterStatus)
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-sm text-gray-500">Desired Replicas</div>
                <div class="text-2xl font-bold text-gray-800">{{ $clusterStatus['spec']['replicas'] ?? 0 }}</div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-sm text-gray-500">Ready Replicas</div>
                <div class="text-2xl font-bold text-gray-800">{{ $clusterStatus['status']['readyReplicas'] ?? 0 }}</div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-sm text-gray-500">Available Replicas</div>
                <div class="text-2xl font-bold text-gray-800">{{ $clusterStatus['status']['availableReplicas'] ?? 0 }}</div>
            </div>
        </div>
    @endif

    @if(!empty($podStatuses))
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800"><i class="fas fa-cubes mr-2"></i>Pods</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phase</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ready</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Restarts</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Age</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($podStatuses as $pod)
                            <tr>
                                <td class="px-6 py-4 text-sm font-mono text-gray-900">{{ $pod['metadata']['name'] }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        @if(($pod['status']['phase'] ?? '') === 'Running') bg-green-100 text-green-800
                                        @elseif(($pod['status']['phase'] ?? '') === 'Pending') bg-yellow-100 text-yellow-800
                                        @else bg-red-100 text-red-800
                                        @endif">
                                        {{ $pod['status']['phase'] ?? 'Unknown' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $pod['status']['containerStatuses'][0]['ready'] ?? false ? 'Yes' : 'No' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $pod['status']['containerStatuses'][0]['restartCount'] ?? 0 }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $pod['metadata']['creationTimestamp'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

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

                        <dt class="font-medium text-gray-700 pt-3">Slack</dt>
                        <dd class="text-gray-600">
                            <a href="{{ route('slack.settings', $deployment->agent->id) }}" class="text-blue-600 hover:underline">
                                <i class="fab fa-slack mr-1"></i>Configure Slack integration
                            </a>
                        </dd>
                    @endif
                </dl>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow lg:col-span-2">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800"><i class="fas fa-tools mr-2"></i>Scale</h2>
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
                </div>
            </div>
        </div>
    </div>

    @if(!empty($clusterSecrets))
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800"><i class="fas fa-key mr-2"></i>Secrets Included in Manifest</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">K8s Secret</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Key</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($clusterSecrets as $secret)
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $secret['name'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 font-mono">{{ $secret['kubernetes_secret_name'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 font-mono">{{ $secret['key'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800"><i class="fas fa-code mr-2"></i>Generated Kubernetes Manifest</h2>
        </div>
        <div class="p-6">
            <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto text-sm font-mono"><code>{{ $yaml }}</code></pre>
        </div>
    </div>
</div>
