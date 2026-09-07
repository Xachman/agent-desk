<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function authenticated_user_can_view_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertStatus(200)
            ->assertSee('Dashboard');
    }

    #[Test]
    public function guest_is_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')
            ->assertRedirect('/login');
    }

    #[Test]
    public function user_can_create_agent_from_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        \Livewire\Livewire::test(\App\Livewire\Dashboard\Dashboard::class)
            ->set('groupId', $user->personalGroup->id)
            ->set('agentName', 'My Agent')
            ->set('agentDescription', 'Test agent')
            ->set('agentConfigJson', '{"model":"gpt-4"}')
            ->set('agentEnvJson', '{}')
            ->set('agentIsActive', true)
            ->call('saveAgent')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('agents', [
            'name' => 'My Agent',
            'user_id' => $user->id,
            'group_id' => $user->personalGroup->id,
        ]);
    }

    #[Test]
    public function user_cannot_delete_another_users_agent(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $other = User::factory()->create();
        $agent = Agent::factory()->create(['user_id' => $other->id, 'group_id' => $other->personalGroup->id]);

        $this->actingAs($user);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        \Livewire\Livewire::test(\App\Livewire\Dashboard\Dashboard::class)
            ->set('groupId', $user->personalGroup->id)
            ->call('deleteAgent', $agent->id);
    }
}
