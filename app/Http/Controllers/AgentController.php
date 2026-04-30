<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class AgentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Agent::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        if ($request->has('template_id')) {
            $query->where('template_id', $request->template_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $query->with(['user', 'template', 'executions'])
            ->orderBy('created_at', 'desc');

        $agents = $query->paginate($request->get('per_page', 15));

        return response()->json($agents);
    }

    public function show($id): JsonResponse
    {
        $agent = Agent::with(['user', 'template', 'executions', 'outputs'])->findOrFail($id);

        return response()->json($agent);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'template_id' => 'nullable|uuid|exists:agent_templates,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'agent_config' => 'nullable|array',
            'env_variables' => 'nullable|array',
            'config_template' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $agent = Agent::create([
            'user_id' => $request->user()->id,
            'template_id' => $validated['template_id'] ?? null,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'agent_config' => $validated['agent_config'] ?? [],
            'env_variables' => $validated['env_variables'] ?? [],
            'config_template' => $validated['config_template'] ?? [],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json($agent, Response::HTTP_CREATED);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $agent = Agent::findOrFail($id);

        $validated = $request->validate([
            'template_id' => 'nullable|uuid|exists:agent_templates,id',
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'agent_config' => 'nullable|array',
            'env_variables' => 'nullable|array',
            'config_template' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $agent->update($validated);

        $agent->refresh();

        return response()->json($agent);
    }

    public function destroy($id): JsonResponse
    {
        $agent = Agent::findOrFail($id);

        $agent->delete();

        return response()->json([
            'message' => 'Agent deleted successfully',
        ]);
    }

    public function templates(): JsonResponse
    {
        $templates = AgentTemplate::with('agents')->get();

        return response()->json($templates);
    }

    public function run(Request $request, $id): JsonResponse
    {
        $agent = Agent::with(['user', 'template'])->findOrFail($id);

        if (!$agent->is_active) {
            return response()->json([
                'error' => 'Agent is not active',
            ], Response::HTTP_BAD_REQUEST);
        }

        $execution = AgentExecution::create([
            'agent_id' => $id,
            'user_id' => $request->user()->id,
            'input' => $request->input ?? [],
            'context' => $request->context ?? [],
            'status' => 'pending',
            'progress' => 0,
            'config_snapshot' => $agent->agent_config,
        ]);

        Log::info('Agent execution started', [
            'execution_id' => $execution->id,
            'agent_id' => $id,
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Execution started',
            'execution' => $execution,
        ], Response::HTTP_ACCEPTED);
    }
}
