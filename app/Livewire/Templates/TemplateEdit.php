<?php

namespace App\Livewire\Templates;

use App\Models\AgentTemplate;
use Livewire\Component;

class TemplateEdit extends Component
{
    public AgentTemplate $template;
    public string $name = '';
    public string $description = '';
    public string $systemPrompt = '';
    public string $userContext = '';
    public string $configJson = '{}';
    public string $envJson = '{}';
    public string $toolDefinitionsJson = '[]';

    public function mount(AgentTemplate $template): void
    {
        $this->template = $template;
        $this->name = $template->name;
        $this->description = $template->description ?? '';
        $this->systemPrompt = $template->system_prompt;
        $this->userContext = $template->user_context ?? '';
        $this->configJson = json_encode($template->config ?? [], JSON_PRETTY_PRINT);
        $this->envJson = json_encode($template->env ?? [], JSON_PRETTY_PRINT);
        $this->toolDefinitionsJson = json_encode($template->tool_definitions ?? [], JSON_PRETTY_PRINT);
    }

    public function update(): void
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

        $this->template->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?: null,
            'system_prompt' => $validated['systemPrompt'],
            'user_context' => $validated['userContext'] ?: null,
            'config' => $this->safeJsonDecode($this->configJson) ?? [],
            'env' => $this->safeJsonDecode($this->envJson) ?? [],
            'tool_definitions' => $this->safeJsonDecode($this->toolDefinitionsJson) ?? [],
        ]);

        session()->flash('message', 'Template updated successfully.');
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
        return view('livewire.templates.edit')->layout('layouts.adminlte', ['title' => 'Edit Template']);
    }
}
