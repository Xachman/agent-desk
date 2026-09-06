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
    public ?string $groupId = null;

    protected $queryString = ['search', 'agentFilter', 'groupId'];

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
        $secret = AgentSecret::findOrFail($id);

        if (! Gate::allows('delete-secret', $secret)) {
            abort(403);
        }

        $secret->delete();
        session()->flash('message', 'Secret deleted successfully.');
    }

    public function render()
    {
        $userId = auth()->id();

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
            ->when($this->groupId, function ($query) {
                $query->where('group_id', $this->groupId);
            }, function ($query) use ($userId) {
                $query->where(function ($q) use ($userId) {
                    $q->whereNull('group_id')->where('user_id', $userId);
                })->orWhere(function ($q) use ($userId) {
                    $q->whereNotNull('group_id')
                        ->whereHas('group.users', function ($gq) use ($userId) {
                            $gq->where('users.id', $userId);
                        });
                });
            })
            ->orderBy('created_at', 'desc');

        $agents = $this->groupId
            ? Agent::where('group_id', $this->groupId)->orderBy('name')->get()
            : Agent::whereNull('group_id')->where('user_id', $userId)->orderBy('name')->get();

        $group = $this->groupId ? Group::find($this->groupId) : null;
        $canCreate = $group ? Gate::allows('admin-group', $group) : true;

        return view('livewire.secrets.index', [
            'secrets' => $secretQuery->paginate(10),
            'agents' => $agents,
            'group' => $group,
            'canCreate' => $canCreate,
        ])->layout('layouts.adminlte', ['title' => $group ? "Secrets: {$group->name}" : 'Secrets']);
    }
}
