<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentTemplate;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function authenticated_user_can_create_group(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        \Livewire\Livewire::test(\App\Livewire\Groups\GroupCreate::class)
            ->set('name', 'Engineering')
            ->set('slug', 'engineering')
            ->set('description', 'Team workspace')
            ->call('store')
            ->assertRedirect('/groups');

        $this->assertDatabaseHas('groups', [
            'name' => 'Engineering',
            'slug' => 'engineering',
            'owner_id' => $user->id,
        ]);
    }

    #[Test]
    public function owner_can_add_and_remove_members(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner);

        \Livewire\Livewire::test(\App\Livewire\Groups\GroupShow::class, ['group' => $group])
            ->set('memberEmail', $member->email)
            ->set('memberRole', 'member')
            ->call('addMember');

        $this->assertDatabaseHas('group_user', [
            'group_id' => $group->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);

        \Livewire\Livewire::test(\App\Livewire\Groups\GroupShow::class, ['group' => $group])
            ->call('removeMember', $member->id);

        $this->assertDatabaseMissing('group_user', [
            'group_id' => $group->id,
            'user_id' => $member->id,
        ]);
    }

    #[Test]
    public function member_cannot_create_agent_in_group(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);
        $group->addMember($member, 'member');
        $template = AgentTemplate::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($member);

        \Livewire\Livewire::test(\App\Livewire\Dashboard\Dashboard::class)
            ->set('groupId', $group->id)
            ->set('agentName', 'My Agent')
            ->set('agentTemplateId', $template->id)
            ->set('agentConfigJson', '{}')
            ->set('agentEnvJson', '{}')
            ->call('saveAgent', new \App\Services\AgentDeploymentService(app(\App\Services\KubernetesService::class)))
            ->assertForbidden();
    }

    #[Test]
    public function admin_can_create_agent_in_group(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);
        $group->addMember($admin, 'admin');
        $template = AgentTemplate::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($admin);

        \Livewire\Livewire::test(\App\Livewire\Dashboard\Dashboard::class)
            ->set('groupId', $group->id)
            ->set('agentName', 'Group Agent')
            ->set('agentTemplateId', $template->id)
            ->set('agentConfigJson', '{}')
            ->set('agentEnvJson', '{}')
            ->call('saveAgent', new \App\Services\AgentDeploymentService(app(\App\Services\KubernetesService::class)))
            ->assertHasNoErrors();

        $this->assertDatabaseHas('agents', [
            'name' => 'Group Agent',
            'group_id' => $group->id,
            'user_id' => $admin->id,
        ]);
    }

    #[Test]
    public function member_can_view_group_agents_but_not_delete(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);
        $group->addMember($member, 'member');
        $agent = Agent::factory()->create([
            'user_id' => $owner->id,
            'group_id' => $group->id,
        ]);

        $this->actingAs($member)
            ->get('/dashboard?group=' . $group->id)
            ->assertStatus(200)
            ->assertSee($agent->name);

        \Livewire\Livewire::actingAs($member)
            ->test(\App\Livewire\Dashboard\Dashboard::class)
            ->set('groupId', $group->id)
            ->call('deleteAgent', $agent->id)
            ->assertForbidden();
    }
}
