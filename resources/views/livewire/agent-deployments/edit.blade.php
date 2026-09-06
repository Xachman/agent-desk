<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-edit mr-2"></i>Edit Agent Deployment</h1>
        <a href="{{ route('agent-deployments.index') }}" class="text-gray-600 hover:text-gray-900"><i class="fas fa-arrow-left mr-1"></i>Back</a>
    </div>

    <div class="bg-white rounded-lg shadow">
        <form wire:submit="update" class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" wire:model.live="name" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2">
                    @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Slug</label>
                    <input type="text" wire:model="slug" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2">
                    @error('slug') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea wire:model="description" rows="3" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Linked Agent (optional)</label>
                    <select wire:model="agent_id" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2">
                        <option value="">— None —</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent['id'] }}" @selected($agent['id'] === $agent_id)>{{ $agent['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Image</label>
                    <input type="text" wire:model="image" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Namespace</label>
                    <input type="text" wire:model="namespace" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Domain</label>
                    <input type="text" wire:model="domain" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Replicas</label>
                    <input type="number" min="0" max="10" wire:model="replicas" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2">
                </div>

                <div class="flex items-center">
                    <label class="flex items-center">
                        <input type="checkbox" wire:model="is_active" class="rounded border-gray-300">
                        <span class="ml-2 text-sm text-gray-700">Active</span>
                    </label>
                </div>
            </div>

            <div class="border-t border-gray-200 pt-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4"><i class="fas fa-brain mr-2"></i>Model Configuration</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Default Model</label>
                        <input type="text" wire:model="model_default" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Provider</label>
                        <input type="text" wire:model="model_provider" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">API Mode</label>
                        <input type="text" wire:model="model_api_mode" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2">
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 pt-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4"><i class="fas fa-file-alt mr-2"></i>Agent Files</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">SOUL.md</label>
                        <textarea wire:model="soul_markdown" rows="8" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 font-mono text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">AGENTS.md</label>
                        <textarea wire:model="agents_markdown" rows="8" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 font-mono text-sm"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">config.yaml (optional; leave blank for default)</label>
                        <textarea wire:model="config_yaml" rows="10" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 font-mono text-sm"></textarea>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 pt-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4"><i class="fas fa-cogs mr-2"></i>Environment, Secrets, and Resources</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Environment Variables (JSON)</label>
                        <textarea wire:model="env_variables_json" rows="5" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 font-mono text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Secrets (JSON array of {name, secret_name, key})</label>
                        <textarea wire:model="secrets_json" rows="5" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 font-mono text-sm"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Resource Limits (JSON)</label>
                        <textarea wire:model="resource_limits_json" rows="10" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 font-mono text-sm"></textarea>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                <a href="{{ route('agent-deployments.index') }}" class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"><i class="fas fa-save mr-2"></i>Update Deployment</button>
            </div>
        </form>
    </div>
</div>
