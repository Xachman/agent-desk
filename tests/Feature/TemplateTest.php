<?php

namespace Tests\Feature;

use App\Models\AgentTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function authenticated_user_can_view_templates_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/templates')
            ->assertStatus(200)
            ->assertSee('Templates');
    }

    #[Test]
    public function authenticated_user_can_create_template(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        \Livewire\Livewire::test(\App\Livewire\Templates\TemplateCreate::class)
            ->set('name', 'Support Agent')
            ->set('description', 'Customer support template')
            ->set('systemPrompt', 'You are a helpful support agent.')
            ->set('configJson', '{"model":"gpt-4"}')
            ->set('envJson', '{}')
            ->set('toolDefinitionsJson', '[]')
            ->call('store')
            ->assertRedirect('/templates');

        $this->assertDatabaseHas('agent_templates', [
            'name' => 'Support Agent',
            'system_prompt' => 'You are a helpful support agent.',
        ]);
    }

    #[Test]
    public function authenticated_user_can_edit_template(): void
    {
        $user = User::factory()->create();
        $template = AgentTemplate::factory()->create();
        $this->actingAs($user);

        \Livewire\Livewire::test(\App\Livewire\Templates\TemplateEdit::class, ['template' => $template])
            ->set('name', 'Updated Name')
            ->set('systemPrompt', 'Updated prompt.')
            ->call('update')
            ->assertRedirect('/templates');

        $this->assertDatabaseHas('agent_templates', [
            'id' => $template->id,
            'name' => 'Updated Name',
        ]);
    }
}
