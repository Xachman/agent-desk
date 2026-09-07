<?php

namespace App\Livewire\Secrets;

use App\Models\Agent;
use App\Models\AgentSecret;
use App\Models\Group;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class SecretCreate extends Component
{
    public string $groupId = '';

    public string $name = '';
    public string $kubernetesSecretName = '';
    public string $key = '';
    public string $value = '';
    public ?string $agentId = '';
    public bool $isActive = true;

    public array $agents = [];

    public function mount(): void
    {
        $requestedGroupId = request()->query('group');
        $this->groupId = $this->resolveGroupId($requestedGroupId);

        $this->agents = Agent::where('group_id', $this->groupId)->orderBy('name')->get(['id', 'name'])->toArray();
    }

    protected function resolveGroupId(?string $requested): string
    {
        if ($requested) {
            $group = Group::find($requested);

            if ($group && Gate::allows('admin-group', $group)) {
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

        if ($this->agentId) {
            Agent::where('group_id', $this->groupId)->findOrFail($this->agentId);
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
        $this->redirect('/groups/' . $this->groupId . '?tab=secrets');
    }

    public function render()
    {
        $group = Group::find($this->groupId);

        return view('livewire.secrets.create', [
            'group' => $group,
        ])->layout('layouts.adminlte', ['title' => "Create Secret: {$group->name}"]);
    }
}
