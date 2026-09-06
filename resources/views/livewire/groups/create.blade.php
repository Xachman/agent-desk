<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Create Group</h1>

    <form wire:submit="store" class="bg-white shadow rounded-lg p-6 space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Name</label>
            <input type="text" wire:model.live="name" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2" required>
            @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Slug</label>
            <input type="text" wire:model="slug" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2" required>
            @error('slug') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Description</label>
            <textarea wire:model="description" rows="4" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2"></textarea>
            @error('description') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('groups.index') }}" class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Create Group</button>
        </div>
    </form>
</div>
