<?php

namespace App\Livewire\Secrets;

use App\Models\Agent;
use App\Models\AgentSecret;
use Livewire\Component;

class SecretEdit extends Component
{
    public AgentSecret $secret;
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
        $this->name = $secret->name;
        $this->kubernetesSecretName = $secret->kubernetes_secret_name;
        $this->key = $secret->key;
        $this->value = $secret->value;
        $this->agentId = $secret->agent_id;
        $this->isActive = $secret->is_active;

        $this->agents = Agent::where('user_id', auth()->id())->orderBy('name')->get(['id', 'name'])->toArray();
    }

    public function update(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'kubernetesSecretName' => 'required|string|max:255',
            'key' => 'required|string|max:255',
            'value' => 'required|string',
            'agentId' => 'nullable|uuid|exists:agents,id',
        ]);

        $this->secret->update([
            'agent_id' => $validated['agentId'] ?: null,
            'name' => $validated['name'],
            'kubernetes_secret_name' => $validated['kubernetesSecretName'],
            'key' => $validated['key'],
            'value' => $validated['value'],
            'is_active' => $this->isActive,
        ]);

        session()->flash('message', 'Secret updated successfully.');
        $this->redirectRoute('secrets.index');
    }

    public function render()
    {
        return view('livewire.secrets.edit')
            ->layout('layouts.adminlte', ['title' => 'Edit Secret']);
    }
}
