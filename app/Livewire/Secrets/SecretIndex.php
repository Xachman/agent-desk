<?php

namespace App\Livewire\Secrets;

use App\Models\Agent;
use App\Models\AgentSecret;
use Livewire\Component;
use Livewire\WithPagination;

class SecretIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public ?string $agentFilter = '';

    protected $queryString = ['search', 'agentFilter'];

    public function delete(string $id): void
    {
        $secret = AgentSecret::where('user_id', auth()->id())->findOrFail($id);
        $secret->delete();

        session()->flash('message', 'Secret deleted successfully.');
    }

    public function render()
    {
        $query = AgentSecret::where('user_id', auth()->id())
            ->with('agent')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('kubernetes_secret_name', 'like', '%' . $this->search . '%')
                        ->orWhere('key', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->agentFilter, function ($query) {
                $query->where('agent_id', $this->agentFilter);
            })
            ->orderBy('created_at', 'desc');

        return view('livewire.secrets.index', [
            'secrets' => $query->paginate(10),
            'agents' => Agent::where('user_id', auth()->id())->orderBy('name')->get(),
        ])->layout('layouts.adminlte', ['title' => 'Secrets']);
    }
}
