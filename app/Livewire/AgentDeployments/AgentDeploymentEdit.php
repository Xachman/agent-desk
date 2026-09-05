<?php

namespace App\Livewire\AgentDeployments;

use App\Models\Agent;
use App\Models\AgentDeployment;
use App\Services\AgentDeploymentService;
use Illuminate\Support\Str;
use Livewire\Component;

class AgentDeploymentEdit extends Component
{
    public AgentDeployment $deployment;
    public string $name = '';
    public string $slug = '';
    public string $description = '';
    public ?string $agent_id = null;
    public string $image = '';
    public string $namespace = '';
    public string $domain = '';
    public int $replicas = 1;
    public bool $is_active = true;

    public string $model_default = '';
    public string $model_provider = '';
    public string $model_api_mode = '';

    public string $soul_markdown = '';
    public string $agents_markdown = '';
    public string $config_yaml = '';

    public string $env_variables_json = '';
    public string $secrets_json = '';
    public string $resource_limits_json = '';

    public array $agents = [];

    public function mount(AgentDeployment $deployment): void
    {
        $this->deployment = $deployment;
        $this->name = $deployment->name;
        $this->slug = $deployment->slug;
        $this->description = $deployment->description ?? '';
        $this->agent_id = $deployment->agent_id;
        $this->image = $deployment->image;
        $this->namespace = $deployment->namespace;
        $this->domain = $deployment->domain;
        $this->replicas = $deployment->replicas;
        $this->is_active = $deployment->is_active;

        $this->model_default = $deployment->model_config['default'] ?? 'kimi-k2.6:cloud';
        $this->model_provider = $deployment->model_config['provider'] ?? 'ollama-cloud';
        $this->model_api_mode = $deployment->model_config['api_mode'] ?? 'chat_completions';

        $this->soul_markdown = $deployment->soul_markdown ?? '';
        $this->agents_markdown = $deployment->agents_markdown ?? '';
        $this->config_yaml = $deployment->config_yaml ?? '';

        $this->env_variables_json = json_encode($deployment->env_variables ?? [], JSON_PRETTY_PRINT);
        $this->secrets_json = json_encode($deployment->secrets ?? [], JSON_PRETTY_PRINT);
        $this->resource_limits_json = json_encode($deployment->resource_limits ?? [], JSON_PRETTY_PRINT);

        $this->agents = Agent::orderBy('name')->get(['id', 'name'])->toArray();
    }

    public function updatedName(string $value): void
    {
        if (empty($this->slug) || $this->slug === Str::slug($this->name)) {
            $this->slug = Str::slug($value);
        }
    }

    public function update(AgentDeploymentService $service): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:agent_deployments,slug,' . $this->deployment->id . ',id',
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
            'is_active' => 'boolean',
        ]);

        $this->deployment->update([
            'agent_id' => $validated['agent_id'] ?? null,
            'name' => $validated['name'],
            'slug' => Str::slug($validated['slug']),
            'description' => $validated['description'] ?? null,
            'image' => $validated['image'],
            'namespace' => $validated['namespace'],
            'domain' => $validated['domain'],
            'replicas' => $validated['replicas'],
            'is_active' => $validated['is_active'] ?? true,
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

        $this->deployment->refresh();
        $service->generateManifest($this->deployment);

        session()->flash('message', 'Deployment updated successfully.');

        $this->redirectRoute('agent-deployments.index');
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
        return view('livewire.agent-deployments.edit');
    }
}
