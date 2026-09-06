<?php

namespace App\Livewire\AgentDeployments;

use App\Models\AgentDeployment;
use App\Services\AgentDeploymentService;
use Livewire\Component;

class AgentDeploymentShow extends Component
{
    public AgentDeployment $deployment;
    public string $yaml = '';

    public function mount(AgentDeployment $deployment, AgentDeploymentService $service): void
    {
        $this->deployment = $deployment;
        $manifest = $service->generateManifest($deployment);
        $this->yaml = $service->toYaml($manifest);
    }

    public function scale(int $replicas): void
    {
        $this->deployment->update(['replicas' => max(0, $replicas)]);
        session()->flash('message', "Deployment scaled to {$replicas} replicas.");
    }

    public function setStatus(string $status): void
    {
        $this->deployment->update(['status' => $status]);
        session()->flash('message', "Status set to {$status}.");
    }

    public function render()
    {
        return view('livewire.agent-deployments.show')
            ->layout('layouts.adminlte', ['title' => $this->deployment->name]);
    }
}
