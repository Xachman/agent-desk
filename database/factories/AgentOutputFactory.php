<?php

namespace Database\Factories;

use App\Models\AgentOutput;
use App\Models\AgentExecution;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AgentOutput>
 */
class AgentOutputFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fileTypes = ['result', 'log', 'artifact', 'metadata'];
        $fileType = $fileTypes[fake()->numberBetween(0, count($fileTypes) - 1)];

        $content = match ($fileType) {
            'result' => json_encode([
                'status' => 'success',
                'output' => fake()->text(500),
            ], JSON_PRETTY_PRINT),
            'log' => fake()->text(500),
            'artifact' => [
                'type' => 'code',
                'language' => 'python',
                'content' => fake()->codeSnippet(),
            ],
            'metadata' => [
                'execution_time' => fake()->numberBetween(1, 100),
                'tokens_used' => fake()->numberBetween(100, 2000),
                'api_calls' => fake()->numberBetween(1, 10),
            ],
        };

        return [
            'execution_id' => AgentExecution::factory(),
            'file_type' => $fileType,
            'file_path' => match ($fileType) {
                'result' => '/outputs/result.json',
                'log' => '/logs/execution.log',
                'artifact' => '/artifacts/task_code.py',
                'metadata' => '/metadata/execution_metadata.json',
            },
            'content' => is_string($content) ? $content : json_encode($content, JSON_PRETTY_PRINT),
            'size_bytes' => Str::length(json_encode($content, JSON_PRETTY_PRINT)),
        ];
    }

    /**
     * Indicate an artifact output.
     */
    public function artifact(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_type' => 'artifact',
            'file_path' => '/artifacts/generated_code.py',
        ]);
    }

    /**
     * Indicate a log output.
     */
    public function log(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_type' => 'log',
            'file_path' => '/logs/execution.log',
        ]);
    }
}
