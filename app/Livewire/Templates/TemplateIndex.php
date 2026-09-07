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
    public string $groupId = '';

    protected $queryString = ['search', 'groupId'];

    public function mount(): void
    {
        $requestedGroupId = request()->query('group');
        $this->groupId = $this->resolveGroupId($requestedGroupId);
    }

    protected function resolveGroupId(?string $requested): string
    {
        if ($requested) {
            $group = Group::find($requested);

            if ($group && Gate::allows('member-group', $group)) {
                return $group->id;
            }

            abort(403);
        }

        $personalGroup = auth()->user()->personalGroup;

        if (! $personalGroup) {
            abort(403, 'No personal group found.');
        }

        return $personalGroup->id;
    }

    public function delete(string $id): void
    {
        $template = AgentTemplate::where('group_id', $this->groupId)->findOrFail($id);

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
            ->where('group_id', $this->groupId)
            ->orderBy('created_at', 'desc');

        $group = Group::find($this->groupId);
        $canCreate = Gate::allows('admin-group', $group);

        return view('livewire.templates.index', [
            'templates' => $query->paginate(10),
            'group' => $group,
            'canCreate' => $canCreate,
        ])->layout('layouts.adminlte', ['title' => "Templates: {$group->name}"]);
    }
}
