<?php

namespace App\Livewire\AgentDeployments;

use App\Models\AgentDeployment;
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

    public function delete(string $id): void
    {
        $deployment = AgentDeployment::findOrFail($id);
        $deployment->delete();

        session()->flash('message', 'Deployment deleted successfully.');
    }

    public function scale(string $id, int $replicas): void
    {
        $deployment = AgentDeployment::findOrFail($id);
        $deployment->update(['replicas' => max(0, $replicas)]);

        session()->flash('message', "Deployment scaled to {$replicas} replicas.");
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
