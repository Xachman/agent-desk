<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800"><i class="fab fa-slack mr-2"></i>Slack Settings</h1>
        <a href="{{ route('dashboard') }}" class="text-gray-600 hover:text-gray-900"><i class="fas fa-arrow-left mr-1"></i>Back</a>
    </div>

    @if (session()->has('success'))
        <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6 space-y-6">
        <div>
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Agent</h2>
            <p class="text-gray-700">{{ $agent->name }}</p>
        </div>

        @if ($workspace)
            <div class="border-t border-gray-200 pt-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Connected Workspace</h2>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="font-medium text-gray-500">Status</dt>
                        <dd class="text-gray-900">{{ $workspace->status }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Team</dt>
                        <dd class="text-gray-900">{{ $workspace->slack_team_name ?: 'Unknown' }} ({{ $workspace->slack_team_id ?: 'N/A' }})</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Slack App ID</dt>
                        <dd class="text-gray-900">{{ $workspace->slack_app_id ?: 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Events URL</dt>
                        <dd class="text-gray-900 break-all">{{ $workspace->getEventsRequestUrl() }}</dd>
                    </div>
                </dl>

                <button wire:click="disconnect" type="button" class="mt-6 px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                    Disconnect
                </button>
            </div>
        @else
            <div class="border-t border-gray-200 pt-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Connect to Slack</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Bot Display Name</label>
                        <input type="text" wire:model="customName" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2">
                        @error('customName') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Short Description</label>
                        <input type="text" wire:model="customDescription" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2">
                        @error('customDescription') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>

                <button wire:click="addToSlack" type="button" class="mt-6 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    <i class="fab fa-slack mr-2"></i>Add to Slack
                </button>

                @error('slack')
                    <p class="mt-2 text-red-600 text-sm">{{ $message }}</p>
                @enderror
            </div>
        @endif
    </div>
</div>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('open-window', (event) => {
            const url = event[0]?.url;
            if (url) {
                window.open(url, '_blank', 'width=600,height=700');
            }
        });
    });
</script>
