<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Agent>
 */
class AgentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create();

        return [
            'user_id' => $user->id,
            'group_id' => $user->personalGroup?->id ?? Group::factory()->create(['owner_id' => $user->id])->id,
            'template_id' => null,
            'name' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'agent_config' => [
                'model' => 'gpt-4',
                'temperature' => 0.7,
                'max_tokens' => 2000,
            ],
            'env_variables' => [
                'OPENAI_API_KEY' => 'sk-test',
            ],
            'config_template' => [
                'workspace_path' => '/tmp/agent-workspace',
                'timeout' => 3600,
            ],
            'is_active' => true,
        ];
    }

    /**
     * Indicate the agent should be inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
