<?php

namespace App\Livewire\Groups;

use App\Models\Group;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class GroupIndex extends Component
{
    use WithPagination;

    public string $search = '';

    protected $queryString = ['search'];

    public function leave(string $id): void
    {
        $group = Group::findOrFail($id);

        if ($group->owner_id === auth()->id()) {
            session()->flash('error', 'Owners cannot leave their own group. Transfer ownership or delete the group.');
            return;
        }

        $group->removeMember(auth()->user());
        session()->flash('message', 'You left the group.');
    }

    public function delete(string $id): void
    {
        $group = Group::findOrFail($id);

        if (! Gate::allows('manage-group', $group)) {
            abort(403);
        }

        $group->delete();
        session()->flash('message', 'Group deleted.');
    }

    public function render()
    {
        $user = auth()->user();

        $owned = $user->ownedGroups()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->get();

        $member = $user->groups()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->get();

        return view('livewire.groups.index', [
            'ownedGroups' => $owned,
            'memberGroups' => $member,
        ])->layout('layouts.adminlte', ['title' => 'Groups']);
    }
}
