<?php

namespace App\Livewire\AgentDeployments;

use App\Models\AgentDeployment;
use App\Models\AgentSecret;
use App\Services\AgentDeploymentService;
use Livewire\Component;

class AgentDeploymentShow extends Component
{
    public AgentDeployment $deployment;
    public string $yaml = '';
    public ?array $clusterStatus = null;
    public array $podStatuses = [];
    public array $clusterSecrets = [];

    public function mount(AgentDeployment $deployment, AgentDeploymentService $service): void
    {
        $this->deployment = $deployment;
        $manifest = $service->generateManifest($deployment);
        $this->yaml = $service->toYaml($manifest);

        $this->loadClusterStatus($service);
        $this->loadSecrets();
    }

    public function scale(int $replicas, AgentDeploymentService $service): void
    {
        try {
            $service->scale($this->deployment, max(0, $replicas));
            $this->deployment->refresh();
            $this->loadClusterStatus($service);
            session()->flash('message', "Deployment scaled to {$replicas} replicas.");
        } catch (\Exception $e) {
            session()->flash('error', 'Scale failed: ' . $e->getMessage());
        }
    }

    public function deploy(AgentDeploymentService $service): void
    {
        try {
            $service->deploy($this->deployment);
            $this->deployment->refresh();
            $this->loadClusterStatus($service);
            session()->flash('message', 'Deployment applied to cluster.');
        } catch (\Exception $e) {
            session()->flash('error', 'Deploy failed: ' . $e->getMessage());
        }
    }

    public function destroy(AgentDeploymentService $service): void
    {
        try {
            $service->destroy($this->deployment);
            $this->deployment->refresh();
            $this->loadClusterStatus($service);
            session()->flash('message', 'Deployment removed from cluster.');
        } catch (\Exception $e) {
            session()->flash('error', 'Destroy failed: ' . $e->getMessage());
        }
    }

    public function refreshStatus(AgentDeploymentService $service): void
    {
        try {
            $this->deployment = $service->refreshStatus($this->deployment);
            $this->loadClusterStatus($service);
            session()->flash('message', 'Status refreshed from cluster.');
        } catch (\Exception $e) {
            session()->flash('error', 'Refresh failed: ' . $e->getMessage());
        }
    }

    protected function loadClusterStatus(AgentDeploymentService $service): void
    {
        $this->clusterStatus = $service->kubernetes->getDeploymentStatus("{$this->deployment->slug}-agent");
        $this->podStatuses = $service->kubernetes->getPods("app=agent");
    }

    protected function loadSecrets(): void
    {
        $this->clusterSecrets = AgentSecret::where('is_active', true)
            ->where(function ($query) {
                $query->where('user_id', $this->deployment->user_id);

                if ($this->deployment->agent_id) {
                    $query->orWhere('agent_id', $this->deployment->agent_id);
                }
            })
            ->get()
            ->toArray();
    }

    public function render()
    {
        return view('livewire.agent-deployments.show')
            ->layout('layouts.adminlte', ['title' => $this->deployment->name]);
    }
}
