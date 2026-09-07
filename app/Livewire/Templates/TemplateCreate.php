<?php

namespace App\Livewire\Templates;

use App\Models\AgentTemplate;
use App\Models\Group;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class TemplateCreate extends Component
{
    public string $groupId = '';

    public string $name = '';
    public string $description = '';
    public string $systemPrompt = '';
    public string $userContext = '';
    public string $configJson = '{}';
    public string $envJson = '{}';
    public string $toolDefinitionsJson = '[]';

    public function mount(): void
    {
        $requestedGroupId = request()->query('group');
        $this->groupId = $this->resolveGroupId($requestedGroupId);
    }

    protected function resolveGroupId(?string $requested): string
    {
        if ($requested) {
            $group = Group::find($requested);

            if ($group && Gate::allows('admin-group', $group)) {
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

        $template = AgentTemplate::create([
            'group_id' => $this->groupId,
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?: null,
            'system_prompt' => $validated['systemPrompt'],
            'user_context' => $validated['userContext'] ?: null,
            'config' => $this->safeJsonDecode($this->configJson) ?? [],
            'env' => $this->safeJsonDecode($this->envJson) ?? [],
            'tool_definitions' => $this->safeJsonDecode($this->toolDefinitionsJson) ?? [],
        ]);

        session()->flash('message', 'Template created successfully.');
        $this->redirect('/groups/' . $this->groupId . '?tab=templates');
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
        $group = Group::find($this->groupId);

        return view('livewire.templates.create', [
            'group' => $group,
        ])->layout('layouts.adminlte', ['title' => "Create Template: {$group->name}"]);
    }
}
