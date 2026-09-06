<?php

namespace App\Livewire\Dashboard;

use App\Models\Agent;
use App\Models\AgentDeployment;
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
    public ?string $groupId = null;

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
        $this->groupId = request()->query('group');

        if ($this->groupId) {
            $group = Group::find($this->groupId);

            if (!$group || !Gate::allows('member-group', $group)) {
                $this->groupId = null;
                abort(403);
            }
        }
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

        if ($agent && $agent->group_id !== null) {
            if (! Gate::allows('member-group', $agent->group)) {
                abort(403);
            }
        } elseif ($agent && $agent->user_id !== auth()->id()) {
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
        $query = Agent::query();

        if ($this->groupId) {
            $query->where('group_id', $this->groupId);
        } else {
            $query->whereNull('group_id')->where('user_id', auth()->id());
        }

        return $query->findOrFail($id);
    }

    protected function findExecution(string $id): ?AgentExecution
    {
        $userId = auth()->id();
        $groupIds = auth()->user()->groups()->pluck('groups.id')->all();

        return AgentExecution::where('id', $id)
            ->whereHas('agent', function ($query) use ($userId, $groupIds) {
                $query->where(function ($q) use ($userId) {
                    $q->whereNull('group_id')->where('user_id', $userId);
                })->orWhere(function ($q) use ($groupIds) {
                    $q->whereIn('group_id', $groupIds);
                });
            })
            ->first();
    }

    protected function canCreateAgent(): bool
    {
        if (! $this->groupId) {
            return true;
        }

        $group = Group::find($this->groupId);

        return $group && Gate::allows('admin-group', $group);
    }

    protected function canUseTemplate(AgentTemplate $template): bool
    {
        if ($template->group_id === null) {
            return true;
        }

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

        $agentQuery = Agent::query()
            ->with(['template', 'deployment'])
            ->when($this->groupId, function ($query) {
                $query->where('group_id', $this->groupId);
            }, function ($query) use ($userId) {
                $query->whereNull('group_id')->where('user_id', $userId);
            })
            ->orderBy('created_at', 'desc');

        $agents = $agentQuery->paginate(10, pageName: 'agentsPage');

        $executionQuery = AgentExecution::query()
            ->with('agent')
            ->whereHas('agent', function ($query) use ($userId) {
                $query->where(function ($q) use ($userId) {
                    $q->whereNull('group_id')->where('user_id', $userId);
                })->orWhere(function ($q) {
                    $q->whereNotNull('group_id')->whereHas('group.users', function ($gq) {
                        $gq->where('users.id', auth()->id());
                    });
                });
            })
            ->orderBy('created_at', 'desc');

        $executions = $executionQuery->paginate(10, pageName: 'executionsPage');

        $templateQuery = AgentTemplate::query()
            ->where(function ($query) use ($userId) {
                $query->whereNull('group_id');
            })
            ->orWhereHas('group.users', function ($query) use ($userId) {
                $query->where('users.id', $userId);
            })
            ->orderBy('name');

        $templates = $templateQuery->get();

        $group = $this->groupId ? Group::find($this->groupId) : null;
        $canCreate = $this->canCreateAgent();
        $canAdmin = $group ? Gate::allows('admin-group', $group) : true;

        return view('livewire.dashboard.dashboard', [
            'agents' => $agents,
            'executions' => $executions,
            'templates' => $templates,
            'group' => $group,
            'canCreate' => $canCreate,
            'canAdmin' => $canAdmin,
        ])->layout('layouts.adminlte', ['title' => $group ? "Dashboard: {$group->name}" : 'Dashboard']);
    }
}
