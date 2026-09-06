<?php

namespace Database\Factories;

use App\Models\AgentSecret;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgentSecret>
 */
class AgentSecretFactory extends Factory
{
    protected $model = AgentSecret::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'kubernetes_secret_name' => fake()->slug(2) . '-secret',
            'key' => 'token',
            'value' => fake()->uuid(),
            'is_active' => true,
        ];
    }
}
