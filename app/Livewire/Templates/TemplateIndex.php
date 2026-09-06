<?php

namespace App\Livewire\Templates;

use App\Models\AgentTemplate;
use App\Models\Group;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class TemplateIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public ?string $groupId = null;

    protected $queryString = ['search', 'groupId'];

    public function mount(): void
    {
        $this->groupId = request()->query('group');

        if ($this->groupId) {
            $group = Group::find($this->groupId);

            if (!$group || !Gate::allows('member-group', $group)) {
                $this->groupId = null;
                abort(403);
            }
        }
    }

    public function delete(string $id): void
    {
        $template = AgentTemplate::findOrFail($id);

        if (! Gate::allows('delete-template', $template)) {
            abort(403);
        }

        $template->delete();
        session()->flash('message', 'Template deleted successfully.');
    }

    public function render()
    {
        $userId = auth()->id();

        $query = AgentTemplate::query()
            ->withCount('agents')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->groupId, function ($query) {
                $query->where('group_id', $this->groupId);
            }, function ($query) use ($userId) {
                $query->where(function ($q) use ($userId) {
                    $q->whereNull('group_id');
                })->orWhereHas('group.users', function ($q) use ($userId) {
                    $q->where('users.id', $userId);
                });
            })
            ->orderBy('created_at', 'desc');

        $group = $this->groupId ? Group::find($this->groupId) : null;
        $canCreate = $group ? Gate::allows('admin-group', $group) : true;

        return view('livewire.templates.index', [
            'templates' => $query->paginate(10),
            'group' => $group,
            'canCreate' => $canCreate,
        ])->layout('layouts.adminlte', ['title' => $group ? "Templates: {$group->name}" : 'Templates']);
    }
}
