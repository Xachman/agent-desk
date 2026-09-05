<?php

namespace Tests\Feature;

use App\Models\AgentDeployment;
use App\Models\User;
use App\Services\AgentDeploymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AgentDeploymentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_view_deployments_index(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/agent-deployments')
            ->assertStatus(200);
    }

    #[Test]
    public function non_admin_is_redirected_from_deployments_index(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get('/agent-deployments')
            ->assertRedirect('/login');
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
}
