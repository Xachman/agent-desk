<?php

namespace App\Livewire\Templates;

use App\Models\AgentTemplate;
use Livewire\Component;
use Livewire\WithPagination;

class TemplateIndex extends Component
{
    use WithPagination;

    public string $search = '';

    protected $queryString = ['search'];

    public function delete(string $id): void
    {
        $template = AgentTemplate::findOrFail($id);
        $template->delete();

        session()->flash('message', 'Template deleted successfully.');
    }

    public function render()
    {
        $query = AgentTemplate::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->withCount('agents')
            ->orderBy('created_at', 'desc');

        return view('livewire.templates.index', [
            'templates' => $query->paginate(10),
        ])->layout('layouts.adminlte', ['title' => 'Templates']);
    }
}
