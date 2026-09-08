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

    protected function getDeployment(string $id): AgentDeployment
    {
        $deployment = AgentDeployment::findOrFail($id);
        $user = auth()->user();

        if (!$deployment->canAdmin($user)) {
            abort(403, 'You do not have permission to manage this deployment.');
        }

        return $deployment;
    }

    public function delete(string $id, AgentDeploymentService $service): void
    {
        $deployment = $this->getDeployment($id);

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
        $deployment = $this->getDeployment($id);

        try {
            $service->scale($deployment, max(0, $replicas));
            session()->flash('message', "Deployment scaled to {$replicas} replicas.");
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to scale: ' . $e->getMessage());
        }
    }

    public function deploy(string $id, AgentDeploymentService $service): void
    {
        $deployment = $this->getDeployment($id);

        try {
            $service->deploy($deployment);
            session()->flash('message', 'Deployment applied to cluster.');
        } catch (\Exception $e) {
            session()->flash('error', 'Deploy failed: ' . $e->getMessage());
        }
    }

    public function destroy(string $id, AgentDeploymentService $service): void
    {
        $deployment = $this->getDeployment($id);

        try {
            $service->destroy($deployment);
            session()->flash('message', 'Deployment removed from cluster.');
        } catch (\Exception $e) {
            session()->flash('error', 'Destroy failed: ' . $e->getMessage());
        }
    }

    public function refreshStatus(string $id, AgentDeploymentService $service): void
    {
        $deployment = $this->getDeployment($id);

        try {
            $service->refreshStatus($deployment);
            session()->flash('message', 'Status refreshed from cluster.');
        } catch (\Exception $e) {
            session()->flash('error', 'Refresh failed: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $user = auth()->user();

        $query = AgentDeployment::query()
            ->with('user', 'agent')
            ->when(!$user->isAdmin(), function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                        ->orWhereHas('group', function ($gq) use ($user) {
                            $gq->whereHas('users', function ($uq) use ($user) {
                                $uq->where('user_id', $user->id);
                            });
                        });
                });
            })
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
