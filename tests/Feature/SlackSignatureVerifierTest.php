<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\SlackWorkspace;
use App\Models\User;
use App\Services\SlackSignatureVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SlackSignatureVerifierTest extends TestCase
{
    use RefreshDatabase;

    protected SlackSignatureVerifier $verifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->verifier = app(SlackSignatureVerifier::class);
    }

    protected function createWorkspace(): SlackWorkspace
    {
        $user = User::factory()->create();
        $agent = Agent::factory()->create(['user_id' => $user->id]);

        return SlackWorkspace::create([
            'user_id' => $user->id,
            'agent_id' => $agent->id,
            'slack_signing_secret' => 'super-secret',
        ]);
    }

    public function test_valid_signature_is_accepted(): void
    {
        $workspace = $this->createWorkspace();
        $timestamp = time();
        $body = json_encode(['hello' => 'world']);
        $signature = 'v0=' . hash_hmac('sha256', "v0:{$timestamp}:{$body}", $workspace->slack_signing_secret);

        $request = Request::create('/slack/events/' . $workspace->id, 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $body);
        $request->headers->set('X-Slack-Request-Timestamp', $timestamp);
        $request->headers->set('X-Slack-Signature', $signature);

        $this->assertTrue($this->verifier->verify($request, $workspace));
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $workspace = $this->createWorkspace();
        $body = json_encode(['hello' => 'world']);

        $request = Request::create('/slack/events/' . $workspace->id, 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $body);
        $request->headers->set('X-Slack-Request-Timestamp', time());
        $request->headers->set('X-Slack-Signature', 'v0=invalid');

        $this->assertFalse($this->verifier->verify($request, $workspace));
    }

    public function test_old_timestamp_is_rejected(): void
    {
        $workspace = $this->createWorkspace();
        $timestamp = time() - 400;
        $body = json_encode(['hello' => 'world']);
        $signature = 'v0=' . hash_hmac('sha256', "v0:{$timestamp}:{$body}", $workspace->slack_signing_secret);

        $request = Request::create('/slack/events/' . $workspace->id, 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $body);
        $request->headers->set('X-Slack-Request-Timestamp', $timestamp);
        $request->headers->set('X-Slack-Signature', $signature);

        $this->assertFalse($this->verifier->verify($request, $workspace));
    }
}
