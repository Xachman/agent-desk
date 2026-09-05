<?php

namespace App\Http\Controllers;

use App\Models\AgentTemplate;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AgentTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AgentTemplate::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $templates = $query->withCount('agents')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($templates);
    }

    public function show($id): JsonResponse
    {
        $template = AgentTemplate::with(['agents', 'executions'])->findOrFail($id);

        return response()->json($template);
    }

    public function create(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Template creation form',
            'fields' => [
                'name' => 'Template name',
                'description' => 'Template description',
                'system_prompt' => 'AGENT.md content',
                'user_context' => 'USER.md content',
                'config' => 'Default configuration',
                'env' => 'Default environment variables',
                'tool_definitions' => 'Available tools',
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'system_prompt' => 'required|string',
            'user_context' => 'nullable|string',
            'config' => 'nullable|array',
            'env' => 'nullable|array',
            'tool_definitions' => 'nullable|array',
        ]);

        $template = AgentTemplate::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'system_prompt' => $validated['system_prompt'],
            'user_context' => $validated['user_context'] ?? null,
            'config' => $validated['config'] ?? [],
            'env' => $validated['env'] ?? [],
            'tool_definitions' => $validated['tool_definitions'] ?? [],
        ]);

        return response()->json($template, Response::HTTP_CREATED);
    }

    public function edit($id): JsonResponse
    {
        $template = AgentTemplate::findOrFail($id);

        return response()->json($template);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $template = AgentTemplate::findOrFail($id);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'system_prompt' => 'required|string',
            'user_context' => 'nullable|string',
            'config' => 'nullable|array',
            'env' => 'nullable|array',
            'tool_definitions' => 'nullable|array',
        ]);

        $template->update($validated);

        $template->refresh();

        return response()->json($template);
    }

    public function destroy($id): JsonResponse
    {
        $template = AgentTemplate::findOrFail($id);

        $template->delete();

        return response()->json([
            'message' => 'Template deleted successfully',
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:json,yaml,yml,toml',
        ]);

        try {
            $file = $request->file('file');

            $content = match ($file->extension()) {
                'json' => json_decode($file->get(), true),
                'yaml', 'yml' => yaml_parse_file($file->getRealPath()),
                'toml' => toml_parse_file($file->getRealPath()),
                default => null,
            };

            if (!is_array($content)) {
                return response()->json([
                    'error' => 'Invalid template file format',
                ], Response::HTTP_BAD_REQUEST);
            }

            $template = AgentTemplate::create([
                'name' => $content['name'] ?? 'Imported Template',
                'description' => $content['description'] ?? null,
                'system_prompt' => $content['system_prompt'] ?? '',
                'user_context' => $content['user_context'] ?? null,
                'config' => $content['config'] ?? $content['config_defaults'] ?? [],
                'env' => $content['env'] ?? $content['env_defaults'] ?? [],
                'tool_definitions' => $content['tool_definitions'] ?? [],
            ]);

            return response()->json($template, Response::HTTP_CREATED);

        } catch (Exception $e) {
            Log::error('Template import failed', ['error' => $e->getMessage()]);

            return response()->json([
                'error' => 'Failed to import template: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function export($id): Response
    {
        try {
            $template = AgentTemplate::findOrFail($id);

            $exportData = [
                'name' => $template->name,
                'description' => $template->description,
                'system_prompt' => $template->system_prompt,
                'user_context' => $template->user_context,
                'config' => $template->config,
                'env' => $template->env,
                'tool_definitions' => $template->tool_definitions,
            ];

            $filename = 'agent-template-' . $template->id . '.json';

            return response()->json($exportData)
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (Exception $e) {
            Log::error('Template export failed', ['error' => $e->getMessage()]);

            return response()->json([
                'error' => 'Failed to export template: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
