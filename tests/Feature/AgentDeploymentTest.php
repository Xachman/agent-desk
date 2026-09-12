<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentDeployment;
use App\Models\User;
use App\Services\AgentDeploymentService;
use App\Services\KubernetesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AgentDeploymentTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function admin_can_view_deployments_index(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/agent-deployments')
            ->assertStatus(200);
    }

    #[Test]
    public function non_admin_can_view_own_deployments_index(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        AgentDeployment::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get('/agent-deployments')
            ->assertStatus(200);
    }

    #[Test]
    public function non_admin_cannot_store_deployments(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        \Livewire\Livewire::test(\App\Livewire\AgentDeployments\AgentDeploymentCreate::class)
            ->set('name', 'Tester')
            ->set('slug', 'tester')
            ->set('image', 'nousresearch/hermes-agent:v2026.4.30')
            ->set('namespace', 'agent-desk')
            ->set('domain', 'agent-services.example.com')
            ->call('store')
            ->assertStatus(403);
    }

    #[Test]
    public function guest_is_redirected_from_deployments_index(): void
    {
        $this->get('/agent-deployments')
            ->assertRedirect('/login');
    }

    #[Test]
    public function admin_can_create_a_deployment_via_livewire(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        \Livewire\Livewire::test(\App\Livewire\AgentDeployments\AgentDeploymentCreate::class)
            ->set('name', 'Tester')
            ->set('slug', 'tester')
            ->set('description', 'Test deployment')
            ->set('image', 'nousresearch/hermes-agent:v2026.4.30')
            ->set('namespace', 'agent-desk')
            ->set('domain', 'agent-services.example.com')
            ->set('replicas', 1)
            ->set('model_default', 'kimi-k2.6:cloud')
            ->set('model_provider', 'ollama-cloud')
            ->set('model_api_mode', 'chat_completions')
            ->call('store')
            ->assertRedirect('/agent-deployments');

        $this->assertDatabaseHas('agent_deployments', [
            'slug' => 'tester',
            'name' => 'Tester',
            'user_id' => $admin->id,
        ]);
    }

    #[Test]
    public function service_generates_full_manifest(): void
    {
        $deployment = AgentDeployment::factory()->create();
        $service = app(AgentDeploymentService::class);

        $manifest = $service->generateManifest($deployment);

        $kinds = collect($manifest)->pluck('kind')->all();

        $this->assertContains('PersistentVolumeClaim', $kinds);
        $this->assertContains('ConfigMap', $kinds);
        $this->assertContains('Service', $kinds);
        $this->assertContains('Deployment', $kinds);
        $this->assertContains('IngressRoute', $kinds);
        $this->assertNotEmpty($deployment->yaml_snapshot);
    }

    #[Test]
    public function deleting_agent_deletes_kubernetes_deployment(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $agent = Agent::factory()->create(['user_id' => $user->id, 'group_id' => $user->personalGroup->id]);
        $deployment = AgentDeployment::factory()->create([
            'user_id' => $user->id,
            'group_id' => $user->personalGroup->id,
            'agent_id' => $agent->id,
            'slug' => 'my-test-agent',
        ]);

        $mock = Mockery::mock(KubernetesService::class);
        $mock->shouldReceive('deleteManifest')
            ->once()
            ->withArgs(function (string $yaml) {
                $this->assertStringContainsString('my-test-agent', $yaml);
                $this->assertStringContainsString('Deployment', $yaml);
                $this->assertStringContainsString('PersistentVolumeClaim', $yaml);

                return true;
            })
            ->andReturn(['output' => 'deleted', 'error' => '', 'exit_code' => 0]);

        $this->app->instance(KubernetesService::class, $mock);

        $agent->delete();

        $this->assertDatabaseMissing('agents', ['id' => $agent->id]);
        $this->assertDatabaseMissing('agent_deployments', ['id' => $deployment->id]);
    }

    #[Test]
    public function deleting_agent_without_deployment_does_not_fail(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $agent = Agent::factory()->create(['user_id' => $user->id, 'group_id' => $user->personalGroup->id]);

        $agent->delete();

        $this->assertDatabaseMissing('agents', ['id' => $agent->id]);
    }
}
