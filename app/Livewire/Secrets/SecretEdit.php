<?php

namespace App\Livewire\Secrets;

use App\Models\Agent;
use App\Models\AgentSecret;
use App\Models\Group;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class SecretEdit extends Component
{
    public AgentSecret $secret;
    public string $groupId = '';

    public string $name = '';
    public string $kubernetesSecretName = '';
    public string $key = '';
    public string $value = '';
    public ?string $agentId = '';
    public bool $isActive = true;

    public array $agents = [];

    public function mount(AgentSecret $secret): void
    {
        $this->secret = $secret;
        $this->groupId = request()->query('group') ?: $secret->group_id;

        $group = Group::find($this->groupId);

        if (!$group || !Gate::allows('member-group', $group)) {
            abort(403);
        }

        if (! Gate::allows('update-secret', $secret)) {
            abort(403);
        }

        $this->agents = Agent::where('group_id', $this->groupId)->orderBy('name')->get(['id', 'name'])->toArray();

        $this->name = $secret->name;
        $this->kubernetesSecretName = $secret->kubernetes_secret_name;
        $this->key = $secret->key;
        $this->value = $secret->value;
        $this->agentId = $secret->agent_id;
        $this->isActive = $secret->is_active;
    }

    public function update(): void
    {
        if (! Gate::allows('update-secret', $this->secret)) {
            abort(403);
        }

        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'kubernetesSecretName' => 'required|string|max:255',
            'key' => 'required|string|max:255',
            'value' => 'required|string',
            'agentId' => 'nullable|uuid|exists:agents,id',
        ]);

        if ($this->agentId) {
            Agent::where('group_id', $this->groupId)->findOrFail($this->agentId);
        }

        $this->secret->update([
            'agent_id' => $validated['agentId'] ?: null,
            'name' => $validated['name'],
            'kubernetes_secret_name' => $validated['kubernetesSecretName'],
            'key' => $validated['key'],
            'value' => $validated['value'],
            'is_active' => $this->isActive,
        ]);

        session()->flash('message', 'Secret updated successfully.');
        $this->redirect('/groups/' . $this->groupId . '?tab=secrets');
    }

    public function render()
    {
        $group = Group::find($this->groupId);
        $canAdmin = $this->secret->canAdmin(auth()->user());

        return view('livewire.secrets.edit', [
            'group' => $group,
            'canAdmin' => $canAdmin,
        ])->layout('layouts.adminlte', ['title' => "Edit Secret: {$group->name}"]);
    }
}
