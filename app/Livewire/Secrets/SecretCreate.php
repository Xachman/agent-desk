<?php

namespace App\Livewire\Secrets;

use App\Models\Agent;
use App\Models\AgentSecret;
use Livewire\Component;

class SecretCreate extends Component
{
    public string $name = '';
    public string $kubernetesSecretName = '';
    public string $key = '';
    public string $value = '';
    public ?string $agentId = '';
    public bool $isActive = true;

    public array $agents = [];

    public function mount(): void
    {
        $this->agents = Agent::where('user_id', auth()->id())->orderBy('name')->get(['id', 'name'])->toArray();
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

        AgentSecret::create([
            'user_id' => auth()->id(),
            'agent_id' => $validated['agentId'] ?: null,
            'name' => $validated['name'],
            'kubernetes_secret_name' => $validated['kubernetesSecretName'],
            'key' => $validated['key'],
            'value' => $validated['value'],
            'is_active' => $this->isActive,
        ]);

        session()->flash('message', 'Secret created successfully.');
        $this->redirectRoute('secrets.index');
    }

    public function render()
    {
        return view('livewire.secrets.create')
            ->layout('layouts.adminlte', ['title' => 'Create Secret']);
    }
}
