<?php

namespace App\Livewire\Secrets;

use App\Models\Agent;
use App\Models\AgentSecret;
use App\Models\Group;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class SecretIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public ?string $agentFilter = '';
    public string $groupId = '';

    protected $queryString = ['search', 'agentFilter', 'groupId'];

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
        $secret = AgentSecret::where('group_id', $this->groupId)->findOrFail($id);

        if (! Gate::allows('delete-secret', $secret)) {
            abort(403);
        }

        $secret->delete();
        session()->flash('message', 'Secret deleted successfully.');
    }

    public function render()
    {
        $secretQuery = AgentSecret::query()
            ->with('agent')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('kubernetes_secret_name', 'like', '%' . $this->search . '%')
                        ->orWhere('key', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->agentFilter, function ($query) {
                $query->where('agent_id', $this->agentFilter);
            })
            ->where('group_id', $this->groupId)
            ->orderBy('created_at', 'desc');

        $agents = Agent::where('group_id', $this->groupId)->orderBy('name')->get();

        $group = Group::find($this->groupId);
        $canCreate = Gate::allows('admin-group', $group);

        return view('livewire.secrets.index', [
            'secrets' => $secretQuery->paginate(10),
            'agents' => $agents,
            'group' => $group,
            'canCreate' => $canCreate,
        ])->layout('layouts.adminlte', ['title' => "Secrets: {$group->name}"]);
    }
}
