<?php

namespace Database\Factories;

use App\Models\AgentSecret;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgentSecret>
 */
class AgentSecretFactory extends Factory
{
    protected $model = AgentSecret::class;

    public function definition(): array
    {
        $user = User::factory()->create();

        return [
            'user_id' => $user->id,
            'group_id' => $user->personalGroup?->id ?? Group::factory()->create(['owner_id' => $user->id])->id,
            'name' => fake()->words(2, true),
            'kubernetes_secret_name' => fake()->slug(2) . '-secret',
            'key' => 'token',
            'value' => fake()->uuid(),
            'is_active' => true,
        ];
    }
}
