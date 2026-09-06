<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-edit mr-2"></i>
            @if($group)
                Edit Secret: {{ $group->name }}
            @else
                Edit Secret
            @endif
        </h1>
        <a href="{{ $group ? route('groups.show', ['group' => $group->id, 'tab' => 'secrets']) : route('secrets.index') }}" class="text-gray-600 hover:text-gray-900"><i class="fas fa-arrow-left mr-1"></i>Back</a>
    </div>

    <div class="bg-white rounded-lg shadow">
        <form wire:submit="update" class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Display Name</label>
                    <input type="text" wire:model="name" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2">
                    @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Kubernetes Secret Name</label>
                    <input type="text" wire:model="kubernetesSecretName" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 font-mono text-sm">
                    @error('kubernetesSecretName') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Secret Key</label>
                    <input type="text" wire:model="key" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 font-mono text-sm">
                    @error('key') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Secret Value</label>
                    <input type="text" wire:model="value" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 font-mono text-sm">
                    @error('value') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Linked Agent (optional)</label>
                    <select wire:model="agentId" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2">
                        <option value="">— None —</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent['id'] }}" @selected($agent['id'] === $agentId)>{{ $agent['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center md:col-span-2">
                    <input type="checkbox" wire:model="isActive" class="rounded border-gray-300">
                    <span class="ml-2 text-sm text-gray-700">Active</span>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                <a href="{{ $group ? route('groups.show', ['group' => $group->id, 'tab' => 'secrets']) : route('secrets.index') }}" class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50">Cancel</a>
                @if($canAdmin)
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"><i class="fas fa-save mr-2"></i>Update Secret</button>
                @endif
            </div>
        </form>
    </div>
</div>
