<?php

namespace App\Services;

use App\Models\SlackWorkspace;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackApiService
{
    public function postMessage(SlackWorkspace $workspace, string $channel, string $text, ?string $threadTs = null): array
    {
        $payload = [
            'channel' => $channel,
            'text' => $text,
        ];

        if ($threadTs) {
            $payload['thread_ts'] = $threadTs;
        }

        return $this->apiPost($workspace, 'chat.postMessage', $payload);
    }

    public function postEphemeral(SlackWorkspace $workspace, string $channel, string $user, string $text): array
    {
        return $this->apiPost($workspace, 'chat.postEphemeral', [
            'channel' => $channel,
            'user' => $user,
            'text' => $text,
        ]);
    }

    public function addReaction(SlackWorkspace $workspace, string $channel, string $timestamp, string $reaction): array
    {
        return $this->apiPost($workspace, 'reactions.add', [
            'channel' => $channel,
            'timestamp' => $timestamp,
            'name' => $reaction,
        ]);
    }

    public function apiPost(SlackWorkspace $workspace, string $method, array $payload = []): array
    {
        if (!$workspace->isActive()) {
            throw new Exception("Slack workspace {$workspace->id} is not active.");
        }

        $token = $workspace->slack_bot_token;

        $response = Http::withToken($token, 'Bearer')
            ->timeout(60)
            ->retry(3, 2000, function (Exception $exception) {
                return $exception->getCode() === 429;
            })
            ->post("https://slack.com/api/{$method}", $payload);

        if ($response->status() === 429) {
            $retryAfter = (int) $response->header('Retry-After', 1);
            sleep($retryAfter);

            return $this->apiPost($workspace, $method, $payload);
        }

        $data = $response->json() ?? [];

        if (!($data['ok'] ?? false)) {
            Log::warning('Slack API call failed', [
                'workspace_id' => $workspace->id,
                'method' => $method,
                'error' => $data['error'] ?? 'unknown',
            ]);

            throw new Exception('Slack API ' . $method . ' failed: ' . ($data['error'] ?? 'unknown'));
        }

        return $data;
    }
}
