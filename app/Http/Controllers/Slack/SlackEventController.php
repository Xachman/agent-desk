<?php

namespace App\Http\Controllers\Slack;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessSlackEvent;
use App\Models\SlackEvent;
use App\Models\SlackWorkspace;
use App\Services\SlackSignatureVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SlackEventController extends Controller
{
    protected SlackSignatureVerifier $verifier;

    public function __construct(SlackSignatureVerifier $verifier)
    {
        $this->verifier = $verifier;
    }

    public function receive(Request $request, SlackWorkspace $workspace): JsonResponse
    {
        if (!$this->verifier->verify($request, $workspace)) {
            Log::warning('Invalid Slack event signature', [
                'workspace_id' => $workspace->id,
            ]);

            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $payload = $request->all();
        $type = $payload['type'] ?? null;

        if ($type === 'url_verification') {
            return response()->json([
                'challenge' => $payload['challenge'] ?? null,
            ]);
        }

        $event = $payload['event'] ?? null;

        if (!$event || !$this->shouldProcess($event)) {
            return response()->json(['ok' => true]);
        }

        $slackEventId = $payload['event_id'] ?? null;

        // Deduplicate by Slack event id.
        if ($slackEventId && SlackEvent::where('slack_event_id', $slackEventId)->exists()) {
            return response()->json(['ok' => true]);
        }

        $slackEvent = SlackEvent::create([
            'slack_workspace_id' => $workspace->id,
            'slack_event_id' => $slackEventId,
            'type' => $event['type'] ?? 'unknown',
            'subtype' => $event['subtype'] ?? null,
            'channel_id' => $event['channel'] ?? null,
            'user_id' => $event['user'] ?? null,
            'text' => $event['text'] ?? null,
            'payload' => $payload,
            'status' => 'received',
        ]);

        ProcessSlackEvent::dispatch($slackEvent);

        return response()->json(['ok' => true]);
    }

    protected function shouldProcess(array $event): bool
    {
        $type = $event['type'] ?? null;

        if (!in_array($type, ['app_mention', 'message'], true)) {
            return false;
        }

        // Ignore bot messages and message changes.
        if (!empty($event['bot_id']) || !empty($event['subtype'])) {
            return false;
        }

        if ($type === 'message' && ($event['channel_type'] ?? null) !== 'im') {
            return false;
        }

        return true;
    }
}
