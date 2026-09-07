<?php

namespace Database\Factories;

use App\Models\AgentTemplate;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgentTemplate>
 */
class AgentTemplateFactory extends Factory
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
            'group_id' => $user->personalGroup?->id ?? Group::factory()->create(['owner_id' => $user->id])->id,
            'user_id' => $user->id,
            'name' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'system_prompt' => fake()->text(1000),
            'user_context' => fake()->text(500),
            'config' => [
                'model' => 'gpt-4',
                'temperature' => 0.7,
                'max_tokens' => 2000,
            ],
            'env' => [
                'OPENAI_API_KEY' => 'sk-test',
            ],
            'tool_definitions' => [
                [
                    'name' => 'web_search',
                    'description' => 'Search the web for information',
                    'parameters' => [],
                ],
            ],
        ];
    }

    /**
     * Indicate a template without user context.
     */
    public function noUserContext(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_context' => null,
        ]);
    }
}
