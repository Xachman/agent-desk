<?php

namespace App\Livewire\AgentDeployments;

use App\Models\AgentDeployment;
use App\Services\AgentDeploymentService;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class AgentDeploymentIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    protected $queryString = ['search', 'statusFilter', 'sortField', 'sortDirection'];

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function delete(string $id, AgentDeploymentService $service): void
    {
        $deployment = AgentDeployment::findOrFail($id);

        try {
            $service->destroy($deployment);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to remove from cluster: ' . $e->getMessage());
            return;
        }

        $deployment->delete();

        session()->flash('message', 'Deployment deleted successfully.');
    }

    public function scale(string $id, int $replicas, AgentDeploymentService $service): void
    {
        $deployment = AgentDeployment::findOrFail($id);

        try {
            $service->scale($deployment, max(0, $replicas));
            session()->flash('message', "Deployment scaled to {$replicas} replicas.");
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to scale: ' . $e->getMessage());
        }
    }

    public function deploy(string $id, AgentDeploymentService $service): void
    {
        $deployment = AgentDeployment::findOrFail($id);

        try {
            $service->deploy($deployment);
            session()->flash('message', 'Deployment applied to cluster.');
        } catch (\Exception $e) {
            session()->flash('error', 'Deploy failed: ' . $e->getMessage());
        }
    }

    public function destroy(string $id, AgentDeploymentService $service): void
    {
        $deployment = AgentDeployment::findOrFail($id);

        try {
            $service->destroy($deployment);
            session()->flash('message', 'Deployment removed from cluster.');
        } catch (\Exception $e) {
            session()->flash('error', 'Destroy failed: ' . $e->getMessage());
        }
    }

    public function refreshStatus(string $id, AgentDeploymentService $service): void
    {
        $deployment = AgentDeployment::findOrFail($id);

        try {
            $service->refreshStatus($deployment);
            session()->flash('message', 'Status refreshed from cluster.');
        } catch (\Exception $e) {
            session()->flash('error', 'Refresh failed: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $query = AgentDeployment::query()
            ->with('user', 'agent')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('slug', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->orderBy($this->sortField, $this->sortDirection);

        return view('livewire.agent-deployments.index', [
            'deployments' => $query->paginate(10),
        ])->layout('layouts.adminlte', ['title' => 'Agent Deployments']);
    }
}
