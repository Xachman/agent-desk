<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-plus mr-2"></i>Create Template</h1>
        <a href="{{ route('agent-templates.index') }}" class="text-gray-600 hover:text-gray-900"><i class="fas fa-arrow-left mr-1"></i>Back</a>
    </div>

    <div class="bg-white rounded-lg shadow">
        <form wire:submit="store" class="p-6 space-y-6">
            <div class="grid grid-cols-1 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" wire:model="name" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2">
                    @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea wire:model="description" rows="3" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">System Prompt (AGENT.md content)</label>
                    <textarea wire:model="systemPrompt" rows="10" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 font-mono text-sm"></textarea>
                    @error('systemPrompt') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">User Context (USER.md content, optional)</label>
                    <textarea wire:model="userContext" rows="6" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 font-mono text-sm"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Config (JSON)</label>
                        <textarea wire:model="configJson" rows="6" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 font-mono text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Environment Variables (JSON)</label>
                        <textarea wire:model="envJson" rows="6" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 font-mono text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tool Definitions (JSON array)</label>
                        <textarea wire:model="toolDefinitionsJson" rows="6" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 font-mono text-sm"></textarea>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                <a href="{{ route('agent-templates.index') }}" class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"><i class="fas fa-save mr-2"></i>Create Template</button>
            </div>
        </form>
    </div>
</div>
