<?php

namespace App\Http\Controllers;

use App\Models\AgentExecution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Str as StringHelper;

class WebhookController extends Controller
{
    public function receive(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'execution_id' => 'required|uuid|exists:agent_executions,id',
            'status' => 'required|in:running,completed,failed,cancelled',
            'progress' => 'nullable|integer|min:0|max:100',
            'logs' => 'nullable|string',
            'result' => 'nullable',
            'artifacts' => 'nullable|array',
            'error' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors(),
            ], 400);
        }

        $data = $validator->validated();

        try {
            $execution = AgentExecution::lockForUpdate()->findOrFail($data['execution_id']);

            $execution->update([
                'status' => $data['status'],
                'progress' => $data['progress'] ?? $execution->progress,
                'error_message' => $data['error'] ?? null,
            ]);

            if ($data['status'] === 'completed' || $data['status'] === 'failed' || $data['status'] === 'cancelled') {
                $execution->update([
                    'completed_at' => now(),
                ]);
            }

            if ($data['started_at'] ?? null) {
                $execution->update([
                    'started_at' => now(),
                ]);
            }

            if ($data['logs'] ?? null) {
                $this->persistLog($execution, $data['logs']);
            }

            if ($data['result'] ?? null) {
                $this->persistResult($execution, $data['result']);
            }

            if (isset($data['artifacts'])) {
                foreach ($data['artifacts'] as $artifact) {
                    $this->persistArtifact($execution, $artifact);
                }
            }

            Log::info('Webhook received', [
                'execution_id' => $data['execution_id'],
                'status' => $data['status'],
                'progress' => $data['progress'] ?? null,
            ]);

            return response()->json([
                'message' => 'Webhook processed successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'execution_id' => $data['execution_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to process webhook',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    protected function persistLog(AgentExecution $execution, string $logContent): void
    {
        $logPath = "agent-logs/{$execution->id}/execution.log";

        if (!Storage::exists('agent-logs/' . $execution->id)) {
            Storage::makeDirectory('agent-logs/' . $execution->id);
        }

        Storage::append($logPath, $logContent);

        AgentOutput::updateOrCreate(
            [
                'execution_id' => $execution->id,
                'file_type' => 'log',
            ],
            [
                'file_path' => $logPath,
                'size_bytes' => Str::length($logContent),
            ]
        );
    }

    protected function persistResult(AgentExecution $execution, $result): void
    {
        $resultPath = "agent-outputs/{$execution->id}/result.json";

        if (!Storage::exists('agent-outputs/' . $execution->id)) {
            Storage::makeDirectory('agent-outputs/' . $execution->id);
        }

        $resultContent = is_string($result) ? json_decode($result, true) : $result;

        if (json_validate(json_encode($resultContent))) {
            Storage::put($resultPath, json_encode($resultContent, JSON_PRETTY_PRINT));

            AgentOutput::updateOrCreate(
                [
                    'execution_id' => $execution->id,
                    'file_type' => 'result',
                ],
                [
                    'file_path' => $resultPath,
                    'content' => json_encode($resultContent, JSON_PRETTY_PRINT),
                    'size_bytes' => Str::length(json_encode($resultContent, JSON_PRETTY_PRINT)),
                ]
            );
        }
    }

    protected function persistArtifact(AgentExecution $execution, array $artifact): void
    {
        $artifactPath = "agent-artifacts/{$execution->id}/" . StringHelper::slug($artifact['name'] ?? 'artifact');

        if (!Storage::exists('agent-artifacts/' . $execution->id)) {
            Storage::makeDirectory('agent-artifacts/' . $execution->id);
        }

        $content = $artifact['content'] ?? '';

        $extension = match ($artifact['type'] ?? 'unknown') {
            'code' => $artifact['language'] ?? 'txt',
            'file' => pathinfo($artifact['name'], PATHINFO_EXTENSION) ?: 'txt',
            default => 'txt',
        };

        $fullPath = $artifactPath . '.' . $extension;

        Storage::put($fullPath, $content);

        AgentOutput::updateOrCreate(
            [
                'execution_id' => $execution->id,
                'file_type' => 'artifact',
            ],
            [
                'file_path' => $fullPath,
                'content' => $content,
                'size_bytes' => Str::length($content),
            ]
        );
    }
}
