<?php

namespace App\Livewire\Dashboard;

use App\Models\Agent;
use App\Models\AgentExecution;
use App\Models\AgentTemplate;
use App\Services\AgentOrchestrator;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class Dashboard extends Component
{
    use WithPagination;

    public string $activeTab = 'agents';

    // Agent form
    public bool $showAgentForm = false;
    public ?string $editingAgentId = null;
    public string $agentName = '';
    public string $agentDescription = '';
    public ?string $agentTemplateId = '';
    public string $agentConfigJson = '{}';
    public string $agentEnvJson = '{}';
    public bool $agentIsActive = true;

    // Run form
    public bool $showRunForm = false;
    public ?string $runningAgentId = null;
    public string $runInputJson = '{"prompt": ""}';
    public string $runContextJson = '{}';

    public function mount(): void
    {
        $this->activeTab = request()->query('tab', 'agents');
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->showAgentForm = false;
        $this->showRunForm = false;
        $this->resetForm();
    }

    public function createAgent(): void
    {
        $this->resetForm();
        $this->showAgentForm = true;
        $this->editingAgentId = null;
    }

    public function editAgent(string $id): void
    {
        $agent = Agent::findOrFail($id);

        $this->editingAgentId = $id;
        $this->agentName = $agent->name;
        $this->agentDescription = $agent->description ?? '';
        $this->agentTemplateId = $agent->template_id;
        $this->agentConfigJson = json_encode($agent->agent_config ?? [], JSON_PRETTY_PRINT);
        $this->agentEnvJson = json_encode($agent->env_variables ?? [], JSON_PRETTY_PRINT);
        $this->agentIsActive = $agent->is_active;
        $this->showAgentForm = true;
    }

    public function saveAgent(): void
    {
        $validated = $this->validate([
            'agentName' => 'required|string|max:255',
            'agentDescription' => 'nullable|string',
            'agentTemplateId' => 'nullable|uuid|exists:agent_templates,id',
            'agentConfigJson' => 'nullable|string',
            'agentEnvJson' => 'nullable|string',
        ]);

        $data = [
            'user_id' => auth()->id(),
            'template_id' => $validated['agentTemplateId'] ?: null,
            'name' => $validated['agentName'],
            'description' => $validated['agentDescription'] ?: null,
            'agent_config' => $this->safeJsonDecode($this->agentConfigJson) ?? [],
            'env_variables' => $this->safeJsonDecode($this->agentEnvJson) ?? [],
            'config_template' => [],
            'is_active' => $this->agentIsActive,
        ];

        if ($this->editingAgentId) {
            Agent::findOrFail($this->editingAgentId)->update($data);
            session()->flash('message', 'Agent updated successfully.');
        } else {
            Agent::create($data);
            session()->flash('message', 'Agent created successfully.');
        }

        $this->showAgentForm = false;
        $this->resetForm();
    }

    public function deleteAgent(string $id): void
    {
        $agent = Agent::where('user_id', auth()->id())->findOrFail($id);
        $agent->delete();

        session()->flash('message', 'Agent deleted successfully.');
    }

    public function runAgentModal(string $id): void
    {
        $this->runningAgentId = $id;
        $this->runInputJson = '{"prompt": ""}';
        $this->runContextJson = '{}';
        $this->showRunForm = true;
    }

    public function executeAgent(): void
    {
        $validated = $this->validate([
            'runInputJson' => 'required|string',
            'runContextJson' => 'nullable|string',
        ]);

        $agent = Agent::where('user_id', auth()->id())->findOrFail($this->runningAgentId);

        if (!$agent->is_active) {
            session()->flash('error', 'Agent is not active.');
            return;
        }

        $input = $this->safeJsonDecode($this->runInputJson) ?? [];
        $context = $this->safeJsonDecode($this->runContextJson);

        try {
            $orchestrator = app(AgentOrchestrator::class);
            $execution = $orchestrator->executeAgent((int) $agent->id, $input, $context);

            session()->flash('message', 'Execution started: ' . $execution->id);
        } catch (\Exception $e) {
            Log::error('Dashboard agent execution failed', [
                'agent_id' => $agent->id,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Execution failed: ' . $e->getMessage());
        }

        $this->showRunForm = false;
    }

    public function cancelExecution(string $id): void
    {
        $execution = AgentExecution::whereHas('agent', fn ($q) => $q->where('user_id', auth()->id()))->findOrFail($id);

        if ($execution->status !== 'running') {
            session()->flash('error', 'Only running executions can be cancelled.');
            return;
        }

        $execution->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);

        session()->flash('message', 'Execution cancelled.');
    }

    protected function resetForm(): void
    {
        $this->agentName = '';
        $this->agentDescription = '';
        $this->agentTemplateId = '';
        $this->agentConfigJson = '{}';
        $this->agentEnvJson = '{}';
        $this->agentIsActive = true;
        $this->editingAgentId = null;
        $this->runningAgentId = null;
    }

    protected function safeJsonDecode(?string $json): ?array
    {
        if (empty($json)) {
            return null;
        }

        $decoded = json_decode($json, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    public function render()
    {
        $userId = auth()->id();

        $agents = Agent::where('user_id', $userId)
            ->with('template')
            ->orderBy('created_at', 'desc')
            ->paginate(10, pageName: 'agentsPage');

        $executions = AgentExecution::where('user_id', $userId)
            ->with('agent')
            ->orderBy('created_at', 'desc')
            ->paginate(10, pageName: 'executionsPage');

        $templates = AgentTemplate::orderBy('name')->get();

        return view('livewire.dashboard.dashboard', [
            'agents' => $agents,
            'executions' => $executions,
            'templates' => $templates,
        ])->layout('layouts.adminlte', ['title' => 'Dashboard']);
    }
}
