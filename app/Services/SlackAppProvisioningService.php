<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\SlackWorkspace;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackAppProvisioningService
{
    protected SlackConfigTokenService $configTokenService;
    protected SlackIconService $iconService;

    public function __construct(
        SlackConfigTokenService $configTokenService,
        SlackIconService $iconService
    ) {
        $this->configTokenService = $configTokenService;
        $this->iconService = $iconService;
    }

    public function provision(Agent $agent, ?string $customName = null, ?string $customDescription = null, ?string $iconPath = null): SlackWorkspace
    {
        $workspace = SlackWorkspace::firstOrNew([
            'agent_id' => $agent->id,
        ], [
            'user_id' => $agent->user_id,
            'status' => 'pending',
        ]);

        // Save once so the workspace has an ID for route generation.
        $workspace->status = 'pending';
        $workspace->save();

        $manifest = $this->buildManifest($workspace, $agent, $customName, $customDescription);
        $workspace->manifest_json = $manifest;
        $workspace->save();

        $response = $this->configTokenService->apiPost('apps.manifest.create', [
            'manifest' => json_encode($manifest),
        ]);

        $credentials = $response['credentials'] ?? [];

        $workspace->update([
            'slack_app_id' => $response['app_id'] ?? null,
            'slack_client_id' => $credentials['client_id'] ?? null,
            'slack_client_secret' => $credentials['client_secret'] ?? null,
            'slack_signing_secret' => $credentials['signing_secret'] ?? null,
            'slack_verification_token' => $credentials['verification_token'] ?? null,
            'metadata_json' => [
                'oauth_authorize_url' => $response['oauth_authorize_url'] ?? null,
                'manifest_response' => $response,
            ],
        ]);

        if ($iconPath || (!$workspace->icon_path && config('services.slack.platform.auto_generate_icons', true))) {
            $this->setIcon($workspace, $iconPath, $agent->name);
        }

        Log::info('Slack app provisioned for agent', [
            'agent_id' => $agent->id,
            'workspace_id' => $workspace->id,
            'slack_app_id' => $workspace->slack_app_id,
        ]);

        return $workspace->fresh();
    }

    public function getAuthorizeUrl(SlackWorkspace $workspace): string
    {
        $clientId = $workspace->slack_client_id;
        $redirectUrl = $workspace->getOauthRedirectUrl();
        $scopes = $this->scopes();
        $state = $this->generateState($workspace);

        return 'https://slack.com/oauth/v2/authorize?' . http_build_query([
            'client_id' => $clientId,
            'scope' => implode(',', $scopes),
            'redirect_uri' => $redirectUrl,
            'state' => $state,
        ]);
    }

    public function exchangeCode(string $code, string $state): SlackWorkspace
    {
        $workspace = $this->verifyState($state);

        $response = Http::asForm()
            ->timeout(60)
            ->post('https://slack.com/api/oauth.v2.access', [
                'client_id' => $workspace->slack_client_id,
                'client_secret' => $workspace->slack_client_secret,
                'code' => $code,
                'redirect_uri' => $workspace->getOauthRedirectUrl(),
            ]);

        $data = $response->json();

        if (!($data['ok'] ?? false)) {
            throw new Exception('Slack OAuth exchange failed: ' . ($data['error'] ?? 'unknown'));
        }

        $workspace->update([
            'slack_bot_token' => $data['access_token'] ?? null,
            'slack_team_id' => $data['team']['id'] ?? null,
            'slack_team_name' => $data['team']['name'] ?? null,
            'slack_bot_user_id' => $data['bot_user_id'] ?? null,
            'oauth_response_json' => $data,
            'status' => 'active',
        ]);

        Log::info('Slack workspace connected', [
            'workspace_id' => $workspace->id,
            'slack_team_id' => $workspace->slack_team_id,
        ]);

        return $workspace->fresh();
    }

    public function setIcon(SlackWorkspace $workspace, ?string $iconPath = null, ?string $agentName = null): void
    {
        if (!$workspace->slack_app_id) {
            return;
        }

        if (!$iconPath) {
            $iconPath = $this->iconService->generateForAgent($agentName ?? $workspace->agent->name ?? 'Agent', $workspace->id);
        }

        if (!$iconPath) {
            return;

        }

        $base64 = $this->iconService->getIconBase64($iconPath);

        if (!$base64) {
            return;
        }

        try {
            $this->configTokenService->apiPost('apps.icon.set', [
                'app_id' => $workspace->slack_app_id,
                'image' => $base64,
            ]);

            $workspace->update(['icon_path' => $iconPath]);
        } catch (Exception $e) {
            Log::warning('Failed to set Slack app icon', [
                'workspace_id' => $workspace->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function refreshManifestEventUrl(SlackWorkspace $workspace): void
    {
        if (!$workspace->slack_app_id || !$workspace->manifest_json) {
            return;
        }

        $manifest = $workspace->manifest_json;
        $manifest['settings']['event_subscriptions']['request_url'] = $workspace->getEventsRequestUrl();

        $this->configTokenService->apiPost('apps.manifest.update', [
            'app_id' => $workspace->slack_app_id,
            'manifest' => json_encode($manifest),
        ]);

        $workspace->update(['manifest_json' => $manifest]);
    }

    protected function buildManifest(SlackWorkspace $workspace, Agent $agent, ?string $customName, ?string $customDescription): array
    {
        $name = $customName ?: $agent->name;
        $description = $customDescription ?: ($agent->description ?: 'An autonomous agent powered by Agent Desk');
        $eventsUrl = $workspace->getEventsRequestUrl();
        $redirectUrl = $workspace->getOauthRedirectUrl();
        $scopes = $this->scopes();

        return [
            'display_information' => [
                'name' => $this->truncate($name, 35),
                'description' => $this->truncate($description, 140),
                'background_color' => '#111827',
            ],
            'features' => [
                'bot_user' => [
                    'display_name' => $this->truncate($name, 80),
                    'always_online' => false,
                ],
                'events' => [
                    'request_url' => $eventsUrl,
                    'bot_events' => [
                        'app_mention',
                        'message.im',
                    ],
                ],
            ],
            'oauth_config' => [
                'redirect_urls' => [$redirectUrl],
                'scopes' => [
                    'bot' => $scopes,
                ],
            ],
            'settings' => [
                'event_subscriptions' => [
                    'request_url' => $eventsUrl,
                    'bot_events' => [
                        'app_mention',
                        'message.im',
                    ],
                ],
            ],
        ];
    }

    protected function scopes(): array
    {
        return config('services.slack.scopes', [
            'app_mentions:read',
            'chat:write',
            'im:read',
            'im:write',
            'users:read',
        ]);
    }

    protected function generateState(SlackWorkspace $workspace): string
    {
        $state = bin2hex(random_bytes(16));

        cache()->put(
            "slack.oauth.state.{$state}",
            $workspace->id,
            now()->addMinutes(15)
        );

        return $state;
    }

    protected function verifyState(string $state): SlackWorkspace
    {
        $workspaceId = cache()->pull("slack.oauth.state.{$state}");

        if (!$workspaceId) {
            throw new Exception('Invalid or expired Slack OAuth state.');
        }

        return SlackWorkspace::findOrFail($workspaceId);
    }

    protected function truncate(string $value, int $limit): string
    {
        return mb_strimwidth($value, 0, $limit, '…');
    }
}
