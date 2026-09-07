<?php

namespace App\Livewire\Dashboard;

use App\Models\Agent;
use App\Models\AgentExecution;
use App\Models\AgentTemplate;
use App\Models\Group;
use App\Services\AgentDeploymentService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class Dashboard extends Component
{
    use WithPagination;

    public string $activeTab = 'agents';
    public string $groupId = '';

    // Agent form
    public bool $showAgentForm = false;
    public ?string $editingAgentId = null;
    public string $agentName = '';
    public string $agentDescription = '';
    public ?string $agentTemplateId = '';
    public string $agentConfigJson = '{}';
    public string $agentEnvJson = '{}';
    public bool $agentIsActive = true;

    protected $queryString = ['activeTab', 'groupId'];

    public function mount(): void
    {
        $this->activeTab = request()->query('tab', 'agents');
        $requestedGroupId = request()->query('group');

        $this->groupId = $this->resolveGroupId($requestedGroupId);
    }

    protected function resolveGroupId(?string $requested): string
    {
        if ($requested) {
            $group = Group::find($requested);

            if ($group && Gate::allows('member-group', $group)) {
                return $group->id;
            }

            abort(403);
        }

        $personalGroup = auth()->user()->personalGroup;

        if (! $personalGroup) {
            abort(403, 'No personal group found.');
        }

        return $personalGroup->id;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->showAgentForm = false;
        $this->resetForm();
    }

    public function createAgent(): void
    {
        if (! $this->canCreateAgent()) {
            abort(403);
        }

        $this->resetForm();
        $this->showAgentForm = true;
        $this->editingAgentId = null;
    }

    public function editAgent(string $id): void
    {
        $agent = $this->findAgent($id);

        if (! Gate::allows('update-agent', $agent)) {
            abort(403);
        }

        $this->editingAgentId = $id;
        $this->agentName = $agent->name;
        $this->agentDescription = $agent->description ?? '';
        $this->agentTemplateId = $agent->template_id;
        $this->agentConfigJson = json_encode($agent->agent_config ?? [], JSON_PRETTY_PRINT);
        $this->agentEnvJson = json_encode($agent->env_variables ?? [], JSON_PRETTY_PRINT);
        $this->agentIsActive = $agent->is_active;
        $this->showAgentForm = true;
    }

    public function saveAgent(AgentDeploymentService $deploymentService): void
    {
        $validated = $this->validate([
            'agentName' => 'required|string|max:255',
            'agentDescription' => 'nullable|string',
            'agentTemplateId' => 'nullable|uuid|exists:agent_templates,id',
            'agentConfigJson' => 'nullable|string',
            'agentEnvJson' => 'nullable|string',
        ]);

        $agent = $this->editingAgentId
            ? $this->findAgent($this->editingAgentId)
            : new Agent();

        if ($this->editingAgentId) {
            if (! Gate::allows('update-agent', $agent)) {
                abort(403);
            }
        } else {
            if (! $this->canCreateAgent()) {
                abort(403);
            }
        }

        if ($this->agentTemplateId) {
            $template = AgentTemplate::find($this->agentTemplateId);

            if ($template && ! $this->canUseTemplate($template)) {
                abort(403);
            }
        }

        $data = [
            'user_id' => auth()->id(),
            'group_id' => $this->groupId,
            'template_id' => $validated['agentTemplateId'] ?: null,
            'name' => $validated['agentName'],
            'description' => $validated['agentDescription'] ?: null,
            'agent_config' => $this->safeJsonDecode($this->agentConfigJson) ?? [],
            'env_variables' => $this->safeJsonDecode($this->agentEnvJson) ?? [],
            'config_template' => [],
            'is_active' => $this->agentIsActive,
        ];

        if ($this->editingAgentId) {
            $agent->update($data);
            session()->flash('message', 'Agent updated successfully.');
        } else {
            $agent = Agent::create($data);
            session()->flash('message', 'Agent created successfully.');
        }

        try {
            $deployment = $this->editingAgentId
                ? ($agent->deployment ?? $deploymentService->createDeploymentFromAgent($agent))
                : $deploymentService->createDeploymentFromAgent($agent);

            $deploymentService->deploy($deployment);
            session()->flash('message', session('message') . ' Deployment applied to cluster: ' . $deployment->slug);
        } catch (\Exception $e) {
            Log::error('Auto-deploy agent failed', [
                'agent_id' => $agent->id,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Agent saved but deployment failed: ' . $e->getMessage());
        }

        $this->showAgentForm = false;
        $this->resetForm();
    }

    public function deleteAgent(string $id): void
    {
        $agent = $this->findAgent($id);

        if (! Gate::allows('delete-agent', $agent)) {
            abort(403);
        }

        $agent->delete();
        session()->flash('message', 'Agent deleted successfully.');
    }

    public function cancelExecution(string $id): void
    {
        $execution = $this->findExecution($id);

        if (! $execution) {
            abort(403);
        }

        $agent = $execution->agent;

        if ($agent && ! Gate::allows('member-group', $agent->group)) {
            abort(403);
        }

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

    protected function findAgent(string $id): Agent
    {
        return Agent::where('group_id', $this->groupId)->findOrFail($id);
    }

    protected function findExecution(string $id): ?AgentExecution
    {
        $groupIds = auth()->user()->groups()->pluck('groups.id')->all();

        return AgentExecution::where('id', $id)
            ->whereHas('agent', function ($query) use ($groupIds) {
                $query->whereIn('group_id', $groupIds);
            })
            ->first();
    }

    protected function canCreateAgent(): bool
    {
        $group = Group::find($this->groupId);

        return $group && Gate::allows('admin-group', $group);
    }

    protected function canUseTemplate(AgentTemplate $template): bool
    {
        return Gate::allows('member-group', $template->group);
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
        $user = auth()->user();
        $userId = $user->id;

        $agents = Agent::where('group_id', $this->groupId)
            ->with(['template', 'deployment'])
            ->orderBy('created_at', 'desc')
            ->paginate(10, pageName: 'agentsPage');

        $executions = AgentExecution::query()
            ->with('agent')
            ->whereHas('agent', function ($query) use ($userId) {
                $query->whereHas('group.users', function ($gq) use ($userId) {
                    $gq->where('users.id', $userId);
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10, pageName: 'executionsPage');

        $templates = AgentTemplate::query()
            ->whereHas('group.users', function ($query) use ($userId) {
                $query->where('users.id', $userId);
            })
            ->orderBy('name')
            ->get();

        $group = Group::find($this->groupId);
        $canCreate = $this->canCreateAgent();
        $canAdmin = Gate::allows('admin-group', $group);

        return view('livewire.dashboard.dashboard', [
            'agents' => $agents,
            'executions' => $executions,
            'templates' => $templates,
            'group' => $group,
            'canCreate' => $canCreate,
            'canAdmin' => $canAdmin,
        ])->layout('layouts.adminlte', ['title' => "Dashboard: {$group->name}"]);
    }
}
