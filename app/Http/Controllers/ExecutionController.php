<?php

namespace App\Http\Controllers;

use App\Models\AgentExecution;
use App\Models\AgentOutput;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExecutionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AgentExecution::query()->with(['agent', 'user']);

        if ($request->has('agent_id')) {
            $query->where('agent_id', $request->agent_id);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('started_after')) {
            $query->where('started_at', '>=', $request->started_after);
        }

        if ($request->has('started_before')) {
            $query->where('started_at', '<=', $request->started_before);
        }

        $executions = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($executions);
    }

    public function show($id): JsonResponse
    {
        $execution = AgentExecution::with(['agent', 'user', 'outputs'])
            ->findOrFail($id);

        return response()->json($execution);
    }

    public function logs($id): BinaryFileResponse
    {
        $execution = AgentExecution::findOrFail($id);

        $logOutput = AgentOutput::where('execution_id', $id)
            ->where('file_type', 'log')
            ->first();

        if (!$logOutput) {
            return response()->json([
                'error' => 'No logs available',
            ], 404);
        }

        return Storage::download($logOutput->file_path);
    }

    public function results($id): JsonResponse
    {
        $execution = AgentExecution::with(['agent', 'outputs'])
            ->findOrFail($id);

        $resultOutput = $execution->outputs
            ->where('file_type', 'result')
            ->first();

        if (!$resultOutput) {
            return response()->json([
                'error' => 'No results available',
            ], 404);
        }

        return response()->json(json_decode($resultOutput->content, true));
    }

    public function cancel($id): JsonResponse
    {
        $execution = AgentExecution::findOrFail($id);

        if ($execution->status !== 'running') {
            return response()->json([
                'error' => 'Only running executions can be cancelled',
            ], Response::HTTP_BAD_REQUEST);
        }

        $execution->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);

        Log::info('Execution cancelled', [
            'execution_id' => $id,
            'agent_id' => $execution->agent_id,
        ]);

        return response()->json([
            'message' => 'Execution cancelled successfully',
            'execution' => $execution,
        ]);
    }

    public function restart($id): JsonResponse
    {
        $execution = AgentExecution::with(['agent', 'outputs'])->findOrFail($id);

        if (!in_array($execution->status, ['completed', 'failed', 'cancelled'])) {
            return response()->json([
                'error' => 'Can only restart completed, failed, or cancelled executions',
            ], Response::HTTP_BAD_REQUEST);
        }

        $newExecution = AgentExecution::create([
            'agent_id' => $execution->agent_id,
            'user_id' => $execution->user_id,
            'input' => $execution->input,
            'context' => $execution->context,
            'status' => 'pending',
            'progress' => 0,
            'config_snapshot' => $execution->config_snapshot,
        ]);

        Log::info('Execution restarted', [
            'new_execution_id' => $newExecution->id,
            'original_execution_id' => $id,
            'agent_id' => $execution->agent_id,
        ]);

        return response()->json([
            'message' => 'Execution restarted successfully',
            'execution' => $newExecution,
        ]);
    }
}
