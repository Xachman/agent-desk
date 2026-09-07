<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentSecret;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SecretTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function authenticated_user_can_view_secrets_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/secrets')
            ->assertStatus(200)
            ->assertSee($user->personalGroup->name . ' Secrets');
    }

    #[Test]
    public function authenticated_user_can_create_secret(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        \Livewire\Livewire::test(\App\Livewire\Secrets\SecretCreate::class)
            ->set('name', 'OpenAI Key')
            ->set('kubernetesSecretName', 'agent-openai-key')
            ->set('key', 'OPENAI_API_KEY')
            ->set('value', 'sk-test123')
            ->set('isActive', true)
            ->call('store')
            ->assertRedirect('/groups/' . $user->personalGroup->id . '?tab=secrets');

        $this->assertDatabaseHas('agent_secrets', [
            'name' => 'OpenAI Key',
            'kubernetes_secret_name' => 'agent-openai-key',
            'key' => 'OPENAI_API_KEY',
            'value' => 'sk-test123',
            'user_id' => $user->id,
            'group_id' => $user->personalGroup->id,
        ]);
    }

    #[Test]
    public function authenticated_user_can_edit_secret(): void
    {
        $user = User::factory()->create();
        $secret = AgentSecret::factory()->create(['user_id' => $user->id, 'group_id' => $user->personalGroup->id]);
        $this->actingAs($user);

        \Livewire\Livewire::test(\App\Livewire\Secrets\SecretEdit::class, ['secret' => $secret])
            ->set('name', 'Updated Secret')
            ->set('value', 'new-value')
            ->call('update')
            ->assertRedirect('/groups/' . $user->personalGroup->id . '?tab=secrets');

        $this->assertDatabaseHas('agent_secrets', [
            'id' => $secret->id,
            'name' => 'Updated Secret',
            'value' => 'new-value',
        ]);
    }

    #[Test]
    public function secrets_are_included_in_deployment_manifest(): void
    {
        $user = User::factory()->create();
        $agent = Agent::factory()->create(['user_id' => $user->id]);
        $secret = AgentSecret::factory()->create([
            'user_id' => $user->id,
            'agent_id' => $agent->id,
            'kubernetes_secret_name' => 'my-k8s-secret',
            'key' => 'API_KEY',
            'value' => 'secret-value',
        ]);

        $deployment = \App\Models\AgentDeployment::factory()->create([
            'user_id' => $user->id,
            'agent_id' => $agent->id,
        ]);

        $service = app(\App\Services\AgentDeploymentService::class);
        $manifest = $service->generateManifest($deployment);

        $kinds = collect($manifest)->pluck('kind')->all();
        $this->assertContains('Secret', $kinds);

        $secretDoc = collect($manifest)->first(fn ($d) => ($d['kind'] ?? '') === 'Secret');
        $this->assertEquals('my-k8s-secret', $secretDoc['metadata']['name']);
        $this->assertEquals('secret-value', $secretDoc['stringData']['API_KEY']);
    }
}
