<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\SlackWorkspace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SlackEventControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function createWorkspace(): SlackWorkspace
    {
        $user = User::factory()->create();
        $agent = Agent::factory()->create(['user_id' => $user->id]);

        return SlackWorkspace::create([
            'user_id' => $user->id,
            'agent_id' => $agent->id,
            'slack_signing_secret' => 'secret',
            'status' => 'active',
        ]);
    }

    protected function signPayload(array $payload, SlackWorkspace $workspace, ?int $timestamp = null): array
    {
        $timestamp = $timestamp ?: time();
        $body = json_encode($payload);
        $base = "v0:{$timestamp}:{$body}";
        $signature = 'v0=' . hash_hmac('sha256', $base, $workspace->slack_signing_secret);

        return [
            'X-Slack-Request-Timestamp' => $timestamp,
            'X-Slack-Signature' => $signature,
        ];
    }

    public function test_url_verification_responds_with_challenge(): void
    {
        $workspace = $this->createWorkspace();
        $payload = ['type' => 'url_verification', 'challenge' => 'abc123'];

        $this->postJson(
            route('slack.events', $workspace),
            $payload,
            $this->signPayload($payload, $workspace)
        )
            ->assertOk()
            ->assertJson(['challenge' => 'abc123']);
    }

    public function test_invalid_signature_returns_403(): void
    {
        $workspace = $this->createWorkspace();
        $payload = ['type' => 'url_verification', 'challenge' => 'abc123'];

        $this->postJson(
            route('slack.events', $workspace),
            $payload,
            [
                'X-Slack-Request-Timestamp' => time(),
                'X-Slack-Signature' => 'v0=invalid',
            ]
        )->assertForbidden();
    }

    public function test_app_mention_queues_process_slack_event_job(): void
    {
        Queue::fake();
        $workspace = $this->createWorkspace();

        $payload = [
            'token' => 'test',
            'team_id' => 'T123',
            'event_id' => 'Ev123',
            'type' => 'event_callback',
            'event' => [
                'type' => 'app_mention',
                'channel' => 'C123',
                'user' => 'U123',
                'text' => 'Hello agent',
                'ts' => '123456.789',
            ],
        ];

        $this->postJson(
            route('slack.events', $workspace),
            $payload,
            $this->signPayload($payload, $workspace)
        )
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('slack_events', [
            'slack_workspace_id' => $workspace->id,
            'slack_event_id' => 'Ev123',
            'type' => 'app_mention',
            'status' => 'received',
        ]);

        Queue::assertPushed(\App\Jobs\ProcessSlackEvent::class);
    }

    public function test_bot_messages_are_ignored(): void
    {
        Queue::fake();
        $workspace = $this->createWorkspace();

        $payload = [
            'event_id' => 'EvBot',
            'type' => 'event_callback',
            'event' => [
                'type' => 'app_mention',
                'bot_id' => 'B123',
                'channel' => 'C123',
                'user' => 'U123',
                'text' => 'Hello',
            ],
        ];

        $this->postJson(
            route('slack.events', $workspace),
            $payload,
            $this->signPayload($payload, $workspace)
        )
            ->assertOk()
            ->assertJson(['ok' => true]);

        Queue::assertNothingPushed();
    }

    public function test_duplicate_event_id_is_deduplicated(): void
    {
        Queue::fake();
        $workspace = $this->createWorkspace();

        $payload = [
            'event_id' => 'EvDup',
            'type' => 'event_callback',
            'event' => [
                'type' => 'app_mention',
                'channel' => 'C123',
                'user' => 'U123',
                'text' => 'Hello',
            ],
        ];

        $headers = $this->signPayload($payload, $workspace);

        $this->postJson(route('slack.events', $workspace), $payload, $headers)->assertOk();
        $this->postJson(route('slack.events', $workspace), $payload, $headers)->assertOk();

        Queue::assertPushed(\App\Jobs\ProcessSlackEvent::class, 1);
    }
}
