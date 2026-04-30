<?php

namespace Database\Factories;

use App\Models\AgentExecution;
use App\Models\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgentExecution>
 */
class AgentExecutionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['pending', 'running', 'completed', 'failed', 'cancelled'];
        $status = $statuses[fake()->numberBetween(0, count($statuses) - 1)];

        return [
            'agent_id' => Agent::factory(),
            'user_id' => Agent::factory()->for($this->realModel, 'agent')->raw(),
            'input' => fake()->text(500),
            'context' => [
                'previous_messages' => [],
            ],
            'status' => $status,
            'progress' => match ($status) {
                'pending' => 0,
                'running' => fake()->numberBetween(10, 90),
                'completed' => 100,
                'failed' => fake()->numberBetween(10, 90),
                'cancelled' => fake()->numberBetween(10, 90),
            },
            'started_at' => match ($status) {
                'running', 'completed', 'failed', 'cancelled' => fake()->dateTimeBetween('-1 hour', 'now'),
                default => null,
            },
            'completed_at' => match ($status) {
                'completed', 'failed', 'cancelled' => fake()->dateTimeBetween('-1 hour', 'now'),
                default => null,
            },
            'error_message' => $status === 'failed' ? fake()->text(200) : null,
            'config_snapshot' => [
                'model' => 'gpt-4',
                'temperature' => 0.7,
            ],
        ];
    }

    /**
     * Indicate a pending execution.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'progress' => 0,
            'started_at' => null,
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate a running execution.
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'running',
            'progress' => fake()->numberBetween(10, 90),
            'started_at' => fake()->dateTimeBetween('-30 minutes', 'now'),
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate a completed execution.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'progress' => 100,
            'started_at' => fake()->dateTimeBetween('-1 hour', 'now'),
            'completed_at' => fake()->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    /**
     * Indicate a failed execution.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'progress' => fake()->numberBetween(10, 90),
            'started_at' => fake()->dateTimeBetween('-1 hour', 'now'),
            'completed_at' => fake()->dateTimeBetween('-1 hour', 'now'),
            'error_message' => fake()->text(200),
        ]);
    }

    /**
     * Indicate a cancelled execution.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'progress' => fake()->numberBetween(10, 90),
            'started_at' => fake()->dateTimeBetween('-30 minutes', 'now'),
            'completed_at' => fake()->dateTimeBetween('-30 minutes', 'now'),
        ]);
    }
}
