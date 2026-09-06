<?php

namespace App\Livewire\AgentDeployments;

use App\Models\Agent;
use App\Models\AgentDeployment;
use App\Services\AgentDeploymentService;
use Illuminate\Support\Str;
use Livewire\Component;

class AgentDeploymentCreate extends Component
{
    public string $name = '';
    public string $slug = '';
    public string $description = '';
    public ?string $agent_id = null;
    public string $image = 'nousresearch/hermes-agent:v2026.4.30';
    public string $namespace = 'agent-desk';
    public string $domain = 'agent-services.example.com';
    public int $replicas = 1;
    public string $status = 'pending';
    public bool $is_active = true;

    public string $model_default = 'kimi-k2.6:cloud';
    public string $model_provider = 'ollama-cloud';
    public string $model_api_mode = 'chat_completions';

    public string $soul_markdown = '';
    public string $agents_markdown = '';
    public string $config_yaml = '';

    public string $env_variables_json = '{"DOCKER_HOST": "tcp://localhost:2375"}';
    public string $secrets_json = '[]';
    public string $resource_limits_json = '{
    "agent": {
        "limits": {"memory": "8000Mi", "cpu": "2000m"},
        "requests": {"memory": "500Mi", "cpu": "500m"}
    },
    "dind": {
        "limits": {"memory": "4000Mi", "cpu": "2000m"},
        "requests": {"memory": "1000Mi", "cpu": "500m"}
    }
}';

    public array $agents = [];

    public function mount(): void
    {
        $this->agents = Agent::orderBy('name')->get(['id', 'name'])->toArray();
    }

    public function updatedName(string $value): void
    {
        if (empty($this->slug) || $this->slug === Str::slug($this->name)) {
            $this->slug = Str::slug($value);
        }
    }

    public function store(AgentDeploymentService $service): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:agent_deployments,slug',
            'description' => 'nullable|string',
            'agent_id' => 'nullable|uuid|exists:agents,id',
            'image' => 'required|string|max:255',
            'namespace' => 'required|string|max:255',
            'domain' => 'required|string|max:255',
            'replicas' => 'required|integer|min:0|max:10',
            'model_default' => 'required|string|max:255',
            'model_provider' => 'required|string|max:255',
            'model_api_mode' => 'required|string|max:255',
            'soul_markdown' => 'nullable|string',
            'agents_markdown' => 'nullable|string',
            'config_yaml' => 'nullable|string',
            'env_variables_json' => 'nullable|string',
            'secrets_json' => 'nullable|string',
            'resource_limits_json' => 'nullable|string',
        ]);

        $deployment = $this->buildDeployment($validated);
        $deployment->save();

        try {
            $service->deploy($deployment);
            session()->flash('message', 'Deployment created and applied to cluster.');
        } catch (\Exception $e) {
            $service->generateManifest($deployment);
            session()->flash('error', 'Created but failed to apply to cluster: ' . $e->getMessage());
        }

        $this->redirectRoute('agent-deployments.index');
    }

    protected function buildDeployment(array $validated): AgentDeployment
    {
        $deployment = new AgentDeployment();
        $deployment->forceFill([
            'user_id' => auth()->id(),
            'agent_id' => $validated['agent_id'] ?? null,
            'name' => $validated['name'],
            'slug' => Str::slug($validated['slug']),
            'description' => $validated['description'] ?? null,
            'image' => $validated['image'],
            'namespace' => $validated['namespace'],
            'domain' => $validated['domain'],
            'replicas' => $validated['replicas'],
            'status' => 'pending',
            'is_active' => true,
            'model_config' => [
                'default' => $validated['model_default'],
                'provider' => $validated['model_provider'],
                'api_mode' => $validated['model_api_mode'],
            ],
            'soul_markdown' => $validated['soul_markdown'] ?: null,
            'agents_markdown' => $validated['agents_markdown'] ?: null,
            'config_yaml' => $validated['config_yaml'] ?: null,
            'env_variables' => $this->safeJsonDecode($this->env_variables_json),
            'secrets' => $this->safeJsonDecode($this->secrets_json),
            'resource_limits' => $this->safeJsonDecode($this->resource_limits_json),
        ]);

        return $deployment;
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
        return view('livewire.agent-deployments.create')
            ->layout('layouts.adminlte', ['title' => 'Create Deployment']);
    }
}
