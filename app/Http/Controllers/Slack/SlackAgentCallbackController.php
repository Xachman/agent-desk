<?php

namespace App\Http\Controllers\Slack;

use App\Http\Controllers\Controller;
use App\Models\SlackEvent;
use App\Models\SlackWorkspace;
use App\Services\SlackApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SlackAgentCallbackController extends Controller
{
    protected SlackApiService $slackApi;

    public function __construct(SlackApiService $slackApi)
    {
        $this->slackApi = $slackApi;
    }

    public function receive(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'slack_event_id' => 'required|uuid|exists:slack_events,id',
            'slack_workspace_id' => 'required|uuid|exists:slack_workspaces,id',
            'actions' => 'nullable|array',
            'actions.*.type' => 'required_with:actions|in:post_message,post_ephemeral,add_reaction',
            'actions.*.channel' => 'required_with:actions|string',
            'actions.*.text' => 'nullable|string',
            'actions.*.user' => 'nullable|string',
            'actions.*.thread_ts' => 'nullable|string',
            'actions.*.timestamp' => 'nullable|string',
            'actions.*.reaction' => 'nullable|string',
            'response_payload' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors(),
            ], 400);
        }

        $data = $validator->validated();

        try {
            $workspace = SlackWorkspace::findOrFail($data['slack_workspace_id']);
            $event = SlackEvent::findOrFail($data['slack_event_id']);

            $results = [];

            foreach ($data['actions'] ?? [] as $action) {
                $results[] = $this->executeAction($workspace, $action);
            }

            $event->update([
                'response_payload' => array_merge($event->response_payload ?? [], [
                    'callback' => $data['response_payload'] ?? null,
                    'results' => $results,
                    'received_at' => now()->toIso8601String(),
                ]),
            ]);

            return response()->json([
                'ok' => true,
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            Log::error('Slack agent callback failed', [
                'slack_event_id' => $data['slack_event_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to process callback',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    protected function executeAction(SlackWorkspace $workspace, array $action): array
    {
        try {
            return match ($action['type']) {
                'post_message' => [
                    'type' => 'post_message',
                    'result' => $this->slackApi->postMessage(
                        $workspace,
                        $action['channel'],
                        $action['text'] ?? '',
                        $action['thread_ts'] ?? null
                    ),
                ],
                'post_ephemeral' => [
                    'type' => 'post_ephemeral',
                    'result' => $this->slackApi->postEphemeral(
                        $workspace,
                        $action['channel'],
                        $action['user'] ?? '',
                        $action['text'] ?? ''
                    ),
                ],
                'add_reaction' => [
                    'type' => 'add_reaction',
                    'result' => $this->slackApi->addReaction(
                        $workspace,
                        $action['channel'],
                        $action['timestamp'] ?? '',
                        $action['reaction'] ?? ''
                    ),
                ],
                default => ['type' => $action['type'], 'error' => 'Unsupported action type'],
            };
        } catch (\Exception $e) {
            return [
                'type' => $action['type'],
                'error' => $e->getMessage(),
            ];
        }
    }
}
