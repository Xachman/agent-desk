<?php

namespace App\Services;

use App\Models\SlackPlatformConfig;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackConfigTokenService
{
    public function getActiveConfig(): SlackPlatformConfig
    {
        $config = SlackPlatformConfig::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('name')
                    ->orWhere('name', 'default');
            })
            ->first();

        if (!$config) {
            $config = $this->bootstrapFromEnvironment();
        }

        if ($config->isTokenExpired()) {
            $this->rotate($config);
        }

        return $config->fresh();
    }

    public function bootstrapFromEnvironment(): SlackPlatformConfig
    {
        $expiresAt = config('services.slack.platform.config_token_expires_at');

        return SlackPlatformConfig::create([
            'name' => 'default',
            'config_token' => config('services.slack.platform.config_token'),
            'refresh_token' => config('services.slack.platform.refresh_token'),
            'config_token_expires_at' => $expiresAt ? now()->parse($expiresAt) : null,
            'team_id' => config('services.slack.platform.team_id') ?: null,
            'user_id' => config('services.slack.platform.user_id') ?: null,
            'is_active' => true,
        ]);
    }

    public function rotate(SlackPlatformConfig $config): void
    {
        if (!$config->refresh_token) {
            throw new Exception('Slack platform config refresh token is missing.');
        }

        $response = Http::asForm()
            ->timeout(30)
            ->retry(3, 2000)
            ->post('https://slack.com/api/tooling.tokens.rotate', [
                'refresh_token' => $config->refresh_token,
            ]);

        $data = $response->json();

        if (!($data['ok'] ?? false)) {
            throw new Exception('Slack config token rotation failed: ' . ($data['error'] ?? 'unknown'));
        }

        $config->update([
            'config_token' => $data['token'],
            'refresh_token' => $data['refresh_token'],
            'config_token_expires_at' => $this->expiresAtFromResponse($data),
            'team_id' => $data['team_id'] ?? $config->team_id,
            'user_id' => $data['user_id'] ?? $config->user_id,
        ]);

        Log::info('Slack platform config token rotated', [
            'team_id' => $config->team_id,
            'expires_at' => $config->fresh()->config_token_expires_at,
        ]);
    }

    public function apiPost(string $method, array $payload = []): array
    {
        $config = $this->getActiveConfig();

        $response = Http::withToken($config->config_token, 'Bearer')
            ->timeout(60)
            ->post("https://slack.com/api/{$method}", $payload);

        $data = $response->json() ?? [];

        if (!($data['ok'] ?? false) && ($data['error'] ?? '') === 'token_expired') {
            $this->rotate($config);
            return $this->apiPost($method, $payload);
        }

        if (!($data['ok'] ?? false)) {
            $error = $data['error'] ?? 'unknown';
            $details = $data['errors'] ?? [];
            $detailsJson = $details ? ' ' . json_encode($details) : '';

            throw new Exception("Slack API {$method} failed: {$error}{$detailsJson}");
        }

        return $data;
    }

    protected function expiresAtFromResponse(array $data): ?\Illuminate\Support\Carbon
    {
        if (!empty($data['exp'])) {
            return now()->setTimestamp($data['exp']);
        }

        if (!empty($data['expires_at'])) {
            return now()->parse($data['expires_at']);
        }

        // Slack config tokens expire after 12 hours by default.
        return now()->addHours(12);
    }
}
