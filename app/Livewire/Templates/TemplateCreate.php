<?php

namespace App\Livewire\Templates;

use App\Models\AgentTemplate;
use Livewire\Component;

class TemplateCreate extends Component
{
    public string $name = '';
    public string $description = '';
    public string $systemPrompt = '';
    public string $userContext = '';
    public string $configJson = '{}';
    public string $envJson = '{}';
    public string $toolDefinitionsJson = '[]';

    public function store(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'systemPrompt' => 'required|string',
            'userContext' => 'nullable|string',
            'configJson' => 'nullable|string',
            'envJson' => 'nullable|string',
            'toolDefinitionsJson' => 'nullable|string',
        ]);

        AgentTemplate::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?: null,
            'system_prompt' => $validated['systemPrompt'],
            'user_context' => $validated['userContext'] ?: null,
            'config' => $this->safeJsonDecode($this->configJson) ?? [],
            'env' => $this->safeJsonDecode($this->envJson) ?? [],
            'tool_definitions' => $this->safeJsonDecode($this->toolDefinitionsJson) ?? [],
        ]);

        session()->flash('message', 'Template created successfully.');
        $this->redirectRoute('agent-templates.index');
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
        return view('livewire.templates.create')->layout('layouts.adminlte', ['title' => 'Create Template']);
    }
}
