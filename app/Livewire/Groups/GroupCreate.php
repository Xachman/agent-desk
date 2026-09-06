<?php

namespace App\Livewire\Groups;

use App\Models\Group;
use Illuminate\Support\Str;
use Livewire\Component;

class GroupCreate extends Component
{
    public string $name = '';
    public string $slug = '';
    public string $description = '';

    public function updatedName(string $value): void
    {
        if (empty($this->slug) || $this->slug === Str::slug($this->name)) {
            $this->slug = Str::slug($value);
        }
    }

    public function store(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:groups,slug',
            'description' => 'nullable|string',
        ]);

        $group = Group::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['slug']),
            'description' => $validated['description'] ?: null,
            'owner_id' => auth()->id(),
        ]);

        session()->flash('message', 'Group created successfully.');
        $this->redirectRoute('groups.index');
    }

    public function render()
    {
        return view('livewire.groups.create')
            ->layout('layouts.adminlte', ['title' => 'Create Group']);
    }
}
