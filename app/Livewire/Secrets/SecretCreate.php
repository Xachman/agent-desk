<?php

namespace App\Livewire\Secrets;

use App\Models\Agent;
use App\Models\AgentSecret;
use App\Models\Group;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class SecretCreate extends Component
{
    public ?string $groupId = null;

    public string $name = '';
    public string $kubernetesSecretName = '';
    public string $key = '';
    public string $value = '';
    public ?string $agentId = '';
    public bool $isActive = true;

    public array $agents = [];

    public function mount(): void
    {
        $this->groupId = request()->query('group');

        if ($this->groupId) {
            $group = Group::find($this->groupId);

            if (!$group || !Gate::allows('admin-group', $group)) {
                abort(403);
            }

            $this->agents = Agent::where('group_id', $this->groupId)->orderBy('name')->get(['id', 'name'])->toArray();
        } else {
            $this->agents = Agent::whereNull('group_id')->where('user_id', auth()->id())->orderBy('name')->get(['id', 'name'])->toArray();
        }
    }

    public function updatedName(string $value): void
    {
        if (empty($this->kubernetesSecretName)) {
            $this->kubernetesSecretName = str($value)->slug() . '-secret';
        }
    }

    public function store(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'kubernetesSecretName' => 'required|string|max:255',
            'key' => 'required|string|max:255',
            'value' => 'required|string',
            'agentId' => 'nullable|uuid|exists:agents,id',
        ]);

        if ($this->groupId) {
            $group = Group::find($this->groupId);

            if (!$group || !Gate::allows('admin-group', $group)) {
                abort(403);
            }

            if ($this->agentId) {
                $agent = Agent::where('group_id', $this->groupId)->findOrFail($this->agentId);
            }
        }

        AgentSecret::create([
            'user_id' => auth()->id(),
            'group_id' => $this->groupId,
            'agent_id' => $validated['agentId'] ?: null,
            'name' => $validated['name'],
            'kubernetes_secret_name' => $validated['kubernetesSecretName'],
            'key' => $validated['key'],
            'value' => $validated['value'],
            'is_active' => $this->isActive,
        ]);

        session()->flash('message', 'Secret created successfully.');

        if ($this->groupId) {
            $this->redirect('/groups/' . $this->groupId . '?tab=secrets');
        } else {
            $this->redirect('/secrets');
        }
    }

    public function render()
    {
        $group = $this->groupId ? Group::find($this->groupId) : null;

        return view('livewire.secrets.create', [
            'group' => $group,
        ])->layout('layouts.adminlte', ['title' => $group ? "Create Secret: {$group->name}" : 'Create Secret']);
    }
}
